<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ConfiguracaoDoLogin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as UsuarioDoProvedor;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as RespostaDeRedirecionamento;
use Throwable;

/**
 * As duas pontas do login com Google: a saída para o provedor e a volta dele.
 *
 * O que este controller **não** faz, e é o ponto dele: ele não cadastra ninguém. O exemplo da
 * documentação do Socialite faz `User::updateOrCreate()` no callback, e copiado para este kit
 * isso significaria que qualquer pessoa com uma conta Google se torna usuária do sistema —
 * contornando o convite, que é a única porta de entrada (`RegistroPorConvite`,
 * `config/kit.php` → bloco de convites). Criar conta aqui só acontece com o registro aberto
 * ligado, e ele nasce desligado. Ver ADR-06.
 *
 * A ordem das barreiras do `retorno()` importa, e cada uma existe por um motivo escrito ao
 * lado dela. Nenhuma mensagem devolvida ao usuário diz QUAL barreira reprovou além do
 * necessário: detalhar o motivo na tela é dizer a um atacante em qual delas ele encostou. O
 * motivo vai para o log, com o e-mail mascarado e sem o segredo do OAuth. Ver ADR-09.
 *
 * Wiki: `wikis/specs/feat/login-social-google/login-social-google/`.
 */
final class LoginComGoogleController extends Controller
{
    /**
     * Manda a pessoa para o Google.
     *
     * Nada de `->stateless()`. O `state` de CSRF é do Socialite e fica LIGADO: o
     * `AbstractProvider` nasce com `$stateless = false`, o `redirect()` grava o `state` na
     * sessão e o `user()` compara com `hash_equals`, lançando `InvalidStateException` quando
     * não casa (`vendor/laravel/socialite/src/Two/AbstractProvider.php:83,166,236-237,288-290`).
     * Desligar isso "para simplificar o teste" abriria um CSRF de login — e há caso de teste
     * que reprova exatamente esse atalho.
     */
    public function redirecionar(): RedirectResponse|RespostaDeRedirecionamento
    {
        abort_unless(ConfiguracaoDoLogin::googleDisponivel(), 404);

        Log::channel('autenticacao')->info(
            '[LoginComGoogleController@redirecionar] Redirecionando para o Google | ip: '.request()->ip(),
            [
                'ip'       => request()->ip(),
                'provedor' => ConfiguracaoDoLogin::PROVEDOR_GOOGLE,
            ],
        );

        return Socialite::driver(ConfiguracaoDoLogin::PROVEDOR_GOOGLE)->redirect();
    }

    /**
     * A volta do Google.
     *
     * Seis barreiras, nesta ordem: a feature está no ar; o provedor devolveu alguém; o
     * e-mail está verificado NO PROVEDOR; o e-mail existe; há conta com ele; e — quando não
     * há — o registro aberto permite criar.
     */
    public function retorno(): RedirectResponse
    {
        abort_unless(ConfiguracaoDoLogin::googleDisponivel(), 404);

        try {
            $doProvedor = Socialite::driver(ConfiguracaoDoLogin::PROVEDOR_GOOGLE)->user();
        } catch (Throwable $e) {
            /*
             * Uma cláusula para os três casos — `state` inválido, rede fora e credencial
             * recusada pelo Google — porque a resposta ao usuário é a mesma nos três, e
             * distingui-la na tela é entregar informação de reconhecimento. O `motivo` no
             * log é o que o operador usa para descobrir qual foi.
             */
            Log::channel('autenticacao')->warning(
                '[LoginComGoogleController@retorno] Falha ao obter o usuário no Google | ip: '.request()->ip(),
                [
                    'exception' => $e,
                    'motivo'    => 'falha_no_provedor',
                    'ip'        => request()->ip(),
                    'provedor'  => ConfiguracaoDoLogin::PROVEDOR_GOOGLE,
                ],
            );

            return $this->recusar('Não foi possível concluir a entrada com o Google. Tente novamente.');
        }

        $email = mb_strtolower(trim((string) $doProvedor->getEmail()));

        if ($email === '') {
            Log::channel('autenticacao')->warning(
                '[LoginComGoogleController@retorno] Recusado: provedor não devolveu e-mail | ip: '.request()->ip(),
                [
                    'motivo'   => 'email_ausente',
                    'ip'       => request()->ip(),
                    'provedor' => ConfiguracaoDoLogin::PROVEDOR_GOOGLE,
                ],
            );

            return $this->recusar('O Google não informou um e-mail para esta conta.');
        }

        if (! $this->emailVerificadoNoProvedor($doProvedor)) {
            Log::channel('autenticacao')->warning(
                '[LoginComGoogleController@retorno] Recusado: e-mail não verificado no Google | email: '.Str::mask($email, '*', 3),
                [
                    'motivo'   => 'email_nao_verificado',
                    'email'    => Str::mask($email, '*', 3),
                    'provedor' => ConfiguracaoDoLogin::PROVEDOR_GOOGLE,
                ],
            );

            return $this->recusar('A sua conta do Google não tem o e-mail verificado.');
        }

        $user = $this->contaCom($email);
        $novo = false;

        if (! $user instanceof User) {
            if (! ConfiguracaoDoLogin::registroAberto()) {
                /*
                 * A barreira do convite. Sem ela o login social vira cadastro aberto, e o
                 * kit deixa de ser o que ele diz que é.
                 */
                Log::channel('autenticacao')->warning(
                    '[LoginComGoogleController@retorno] Recusado: não há conta e o registro está fechado | email: '.Str::mask($email, '*', 3),
                    [
                        'motivo'   => 'conta_inexistente_registro_fechado',
                        'email'    => Str::mask($email, '*', 3),
                        'provedor' => ConfiguracaoDoLogin::PROVEDOR_GOOGLE,
                    ],
                );

                return $this->recusar('Não há conta com este e-mail. O acesso a este sistema é por convite.');
            }

            $user = $this->criarConta($email, $doProvedor->getName());
            $novo = true;
        }

        /*
         * `Auth::login()` e não uma escrita na sessão à mão: é ele que dispara
         * `Illuminate\Auth\Events\Login`, que o `rappasoft/laravel-authentication-log` escuta
         * (`LaravelAuthenticationLogServiceProvider.php:35`) para gravar a trilha de acesso
         * que o painel /infra exibe. Abrir a sessão por fora funciona e desaparece da
         * trilha, sem erro nenhum — há caso de teste só para isso.
         *
         * Fixação de sessão não precisa de linha própria: o `SessionGuard::login()` já faz
         * `migrate(true)`, que regenera o id da sessão.
         *
         * E isto não contorna o segundo fator: o middleware `MustTwoFactor` do Breezy
         * redireciona para o desafio sempre que a conta tem 2FA confirmado e a sessão de 2FA
         * não está aberta (`filament-breezy/src/Middleware/MustTwoFactor.php:42-43`).
         */
        Auth::login($user);

        Log::channel('autenticacao')->info(
            "[LoginComGoogleController@retorno] Autenticado pelo Google | user: {$user->getKey()} - email: ".Str::mask($email, '*', 3),
            [
                'user_id'    => $user->getKey(),
                'email'      => Str::mask($email, '*', 3),
                'conta_nova' => $novo,
                'provedor'   => ConfiguracaoDoLogin::PROVEDOR_GOOGLE,
            ],
        );

        return redirect()->to($novo ? $this->urlDoPerfil($user) : $this->urlDoPainel());
    }

    /**
     * O e-mail está verificado no provedor?
     *
     * Falha FECHADO: ausente, textual-falso ou não-booleano recusa. Casar conta por e-mail
     * que o provedor não verificou é a tomada de conta clássica do login social — bastaria
     * criar uma conta Google com o e-mail de outra pessoa.
     *
     * As duas chaves porque o `GoogleProvider` popula as duas: `email_verified` é a do
     * userinfo v3 e `verified_email` é o alias que ele mantém por compatibilidade
     * (`vendor/laravel/socialite/src/Two/GoogleProvider.php:90-92`).
     *
     * `filter_var` e não um cast de bool, pelo mesmo motivo do interruptor em
     * `config/kit.php`: o valor chega do JSON do provedor e a string "false" viraria `true`.
     */
    private function emailVerificadoNoProvedor(UsuarioDoProvedor $doProvedor): bool
    {
        /*
         * `getRaw()` NAO esta no contrato `Socialite\Contracts\User` — ele e de `AbstractUser`.
         * Provedor que nao exponha o payload bruto nao permite conferir a verificacao, e ai a
         * resposta e NAO, nunca "assume que sim". E a mesma falha fechada do resto do metodo, e
         * foi o PHPStan que a tornou explicita: a estreiteza do tipo aqui e a decisao de
         * seguranca, nao um contorno dela.
         */
        if (! $doProvedor instanceof AbstractUser) {
            return false;
        }

        $bruto = $doProvedor->getRaw();

        $verificado = $bruto['email_verified'] ?? $bruto['verified_email'] ?? false;

        return filter_var($verificado, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * A conta com este e-mail, comparada de forma normalizada nos dois lados.
     *
     * `lower()` no SQL e `mb_strtolower()` no valor: e-mail não é case-sensitive na prática,
     * e a comparação crua deixaria `JA.TEM@EXAMPLE.COM` sem casar com a conta gravada em
     * minúsculas — o que, com o registro aberto ligado, criaria uma SEGUNDA conta para a
     * mesma pessoa. É a mesma régua de `Convite::exigirDono()`.
     *
     * Em MySQL com collation `_ci` o `lower()` é redundante; em SQLite e Postgres não é. A
     * redundância é de propósito: o kit roda nos três.
     */
    private function contaCom(string $email): ?User
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->first();
    }

    /**
     * Cria a conta de quem chegou pelo Google, com o registro aberto ligado.
     *
     * Senha aleatória e descartada: a conta nasce sem senha utilizável, e quem quiser uma
     * usa a recuperação de senha. Guardar o token de acesso do Google seria mais um segredo
     * em repouso sem nenhum uso — o kit não chama API do Google em nome de ninguém.
     *
     * **Nenhum papel é atribuído**, e isso é deliberado: papel é o que dá acesso a painel
     * (`User::canAccessPanel()`), e decidir qual papel um registro aberto concede é da
     * feature de registro e aprovação, não desta. Conta sem papel não abre painel algum, e
     * esse é o comportamento correto do kit. Ver ADR-06 e as Ambiguidades do `00-requisito`.
     */
    private function criarConta(string $email, ?string $nome): User
    {
        $user = User::create([
            'name'     => filled($nome) ? $nome : $email,
            'email'    => $email,
            'password' => Str::password(32),
        ]);

        Log::channel('autenticacao')->info(
            "[LoginComGoogleController@criarConta] Conta criada por login social | user: {$user->getKey()} - email: ".Str::mask($email, '*', 3),
            [
                'user_id'  => $user->getKey(),
                'email'    => Str::mask($email, '*', 3),
                'motivo'   => 'conta_criada_por_login_social',
                'provedor' => ConfiguracaoDoLogin::PROVEDOR_GOOGLE,
            ],
        );

        return $user;
    }

    /** Onde quem já tinha conta cai: o painel de negócio, com a organização default resolvida. */
    private function urlDoPainel(): string
    {
        return Filament::getPanel('app')->getUrl() ?? url('/');
    }

    /**
     * A tela do próprio perfil — o destino de quem acabou de se registrar pelo Google.
     *
     * O requisito pede isto porque quem entra por login social pode ainda ter dados a
     * preencher. O nome da rota sai da FÓRMULA do próprio Breezy, e não de uma string fixa:
     * é a mesma que o middleware dele monta (`MustTwoFactor.php:26`), então trocar o `slug:`
     * no PanelProvider não quebra este destino em silêncio.
     *
     * ponytail: conta recém-criada por login social não pertence a organização nenhuma, e
     * com multi-tenancy ligada a URL do perfil exige o slug de uma. Sem organização, o
     * destino é o painel — que é quem sabe o que fazer com quem não tem nenhuma. Teto
     * conhecido: quando a feature de registro aberto definir a organização de destino, é
     * aqui que ela entra.
     */
    private function urlDoPerfil(User $user): string
    {
        $painel = Filament::getPanel('app');
        $breezy = $painel->getPlugin('filament-breezy');
        $slug   = $breezy instanceof BreezyCore ? $breezy->slug() : 'meu-perfil';
        $rota   = "filament.{$painel->getId()}.pages.{$slug}";

        $organizacao = $painel->hasTenancy() ? $user->getTenants($painel)->first() : null;

        if (! Route::has($rota) || ($painel->hasTenancy() && $organizacao === null)) {
            return $this->urlDoPainel();
        }

        return route($rota, $organizacao ? ['tenant' => $organizacao] : []);
    }

    /**
     * Recusa: volta para o login com um aviso, sem autenticar e sem gravar nada.
     *
     * `Notification::send()` fora do Livewire é um `session()->push()`
     * (`vendor/filament/notifications/src/Notification.php`), então a mensagem aparece na
     * tela de login do redirecionamento seguinte.
     */
    private function recusar(string $mensagem): RedirectResponse
    {
        Notification::make()
            ->title($mensagem)
            ->danger()
            ->send();

        return redirect()->to(Filament::getPanel('app')->getLoginUrl());
    }
}
