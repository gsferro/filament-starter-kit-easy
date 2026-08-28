<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Convite;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VinculoSocial;
use App\Notifications\ConfirmarVinculoSocial;
use App\Notifications\PrimeiroAcessoSocial;
use App\Support\ConfiguracaoDoLogin;
use App\Support\ProvedorSocial;
use App\Support\RegistroAberto;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Laravel\Socialite\Socialite;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirecionamentoDoProvedor;
use Throwable;

/**
 * As duas pontas do login social: a saída para o provedor e a volta dele. Para os QUATRO.
 *
 * Este controller era `LoginComGoogleController` e servia um provedor. O nome mudou quando o
 * segundo entrou, porque o antigo passaria a mentir. O que NÃO mudou é o corpo das barreiras:
 * elas estavam certas com um provedor e continuam certas com quatro — o que ficou por provedor
 * é qual driver chamar e como conferir a verificação do e-mail, e as duas coisas moram em
 * `App\Support\ProvedorSocial`.
 *
 * O que este controller **não** faz, e é o ponto dele: ele não cadastra ninguém. O exemplo da
 * documentação do Socialite faz `User::updateOrCreate()` no callback, e copiado para este kit
 * isso significaria que qualquer pessoa com uma conta em qualquer um dos quatro provedores se
 * torna usuária do sistema — contornando o convite, que é a única porta de entrada
 * (`RegistroPorConvite`, `config/kit.php` → bloco de convites). Criar conta aqui só acontece
 * com o registro aberto ligado, e ele nasce desligado. Ver ADR-06 da wiki
 * `login-social-google`.
 *
 * A ordem das barreiras do `retorno()` importa, e cada uma existe por um motivo escrito ao
 * lado dela. Nenhuma mensagem devolvida ao usuário diz QUAL barreira reprovou além do
 * necessário: detalhar o motivo na tela é dizer a um atacante em qual delas ele encostou. O
 * motivo vai para o log, com o e-mail mascarado e sem o segredo do OAuth.
 *
 * Wiki: `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/`.
 */
final class LoginSocialController extends Controller
{
    /**
     * Manda a pessoa para o provedor.
     *
     * O `$provedor` chega pelo implicit enum binding do Laravel — o que significa que segmento
     * fora do enum **nunca chega aqui**: o roteador devolve 404 antes. É essa a lista branca, e
     * ela não é código que alguém mantém em sincronia (ADR-02).
     *
     * Nada de `->stateless()`. O `state` de CSRF é do Socialite e fica LIGADO: o
     * `AbstractProvider` nasce com `$stateless = false`, o `redirect()` grava o `state` na
     * sessão e o `user()` compara com `hash_equals`, lançando `InvalidStateException` quando
     * não casa (`vendor/laravel/socialite/src/Two/AbstractProvider.php:83,166,236-237,288-290`).
     * Desligar isso "para simplificar o teste" abriria um CSRF de login — e há caso de teste
     * que reprova exatamente esse atalho. No X é pior ainda: o `getCodeFields()` dele manda a
     * string literal `'state'` quando stateless (`.../Two/TwitterProvider.php:116-125`).
     */
    public function redirecionar(ProvedorSocial $provedor): RedirecionamentoDoProvedor
    {
        /*
         * O tipo e o do Symfony, e nao o `RedirectResponse` do Laravel que estende dele: e o
         * que o contrato `Socialite\Contracts\Provider::redirect()` promete. Estreitar para o
         * do Laravel reprova no PHPStan, e uma uniao dos dois e tautologia.
         */
        abort_unless(ConfiguracaoDoLogin::disponivel($provedor), 404);

        // `org`/`token` da tela de registro viajam pela sessão até a volta (ADR-02 da wiki
        // cadastro-social-por-convite-e-organizacao). O token NÃO vai para o log.
        $contexto = $this->contextoDeCadastro();
        session()->put('login_social.contexto', $contexto);

        Log::channel('autenticacao')->info(
            "[LoginSocialController@redirecionar] Redirecionando para o provedor | provedor: {$provedor->value} - ip: ".request()->ip(),
            [
                'ip'       => request()->ip(),
                'provedor' => $provedor->value,
                'contexto' => ['org' => $contexto['org'] ?? null, 'com_token' => isset($contexto['token'])],
            ],
        );

        return Socialite::driver($provedor->value)->redirect();
    }

    /**
     * A volta do provedor.
     *
     * Seis barreiras, nesta ordem: o provedor está no ar; ele devolveu alguém; o e-mail existe;
     * o e-mail está verificado NO PROVEDOR; há conta com ele; e — quando não há — o registro
     * aberto permite criar.
     */
    public function retorno(ProvedorSocial $provedor): RedirectResponse
    {
        abort_unless(ConfiguracaoDoLogin::disponivel($provedor), 404);

        try {
            $doProvedor = Socialite::driver($provedor->value)->user();
        } catch (Throwable $e) {
            /*
             * Uma cláusula para os três casos — `state` inválido, rede fora e credencial
             * recusada pelo provedor — porque a resposta ao usuário é a mesma nos três, e
             * distingui-la na tela é entregar informação de reconhecimento. O `motivo` no log é
             * o que o operador usa para descobrir qual foi.
             */
            Log::channel('autenticacao')->warning(
                "[LoginSocialController@retorno] Falha ao obter o usuário no provedor | provedor: {$provedor->value} - ip: ".request()->ip(),
                [
                    'exception' => $e,
                    'motivo'    => 'falha_no_provedor',
                    'ip'        => request()->ip(),
                    'provedor'  => $provedor->value,
                ],
            );

            return $this->recusar("Não foi possível concluir a entrada com o {$provedor->rotulo()}. Tente novamente.");
        }

        /*
         * O contexto da tela de registro (`org`, `token`) morre AQUI, em qualquer desfecho — inclusive
         * recusa. Só o ramo "sem conta" usa `org`; o `token` vale também para conta existente. ADR-02.
         */
        $contexto = is_array($c = session()->pull('login_social.contexto', [])) ? $c : [];

        $email     = mb_strtolower(trim((string) $doProvedor->getEmail()));
        $mascarado = Str::mask($email, '*', 3);

        if ($email === '') {
            Log::channel('autenticacao')->warning(
                "[LoginSocialController@retorno] Recusado: provedor não devolveu e-mail | provedor: {$provedor->value} - ip: ".request()->ip(),
                [
                    'motivo'   => 'email_ausente',
                    'ip'       => request()->ip(),
                    'provedor' => $provedor->value,
                ],
            );

            return $this->recusar("O {$provedor->rotulo()} não informou um e-mail para esta conta.");
        }

        /*
         * A barreira que MUDA de provedor para provedor, e a única que muda — é por isso que
         * ela mora no enum e não aqui. A tabela de como cada um expõe (ou esconde) a
         * verificação, com `file:line` do vendor, está no ADR-03 desta wiki.
         *
         * Falha FECHADO em todos os ramos. Casar conta por e-mail que o provedor não verificou
         * é a tomada de conta clássica do login social, e com o registro do kit fechado — o
         * default — o caminho principal é exatamente o casamento com conta existente.
         */
        if (! $provedor->emailVerificado($doProvedor)) {
            Log::channel('autenticacao')->warning(
                "[LoginSocialController@retorno] Recusado: e-mail não verificado no provedor | provedor: {$provedor->value} - email: ".$mascarado,
                [
                    'motivo'   => 'email_nao_verificado',
                    'email'    => $mascarado,
                    'provedor' => $provedor->value,
                ],
            );

            return $this->recusar("A sua conta do {$provedor->rotulo()} não tem o e-mail verificado.");
        }

        /*
         * O vínculo decide antes do e-mail. Quem já entrou por este provedor é reconhecido pela
         * identidade nele (`sub`), estável quando o e-mail muda — um endereço reciclado no correio
         * não leva a outra conta. Sem vínculo, vale o e-mail verificado (a mesma prova do
         * "Esqueceu a senha?") e o vínculo nasce aqui. ADR-01/02/03 de vinculo-de-provedor-social.
         */
        $sub     = trim((string) $doProvedor->getId());
        $vinculo = $sub !== '' ? VinculoSocial::de($provedor, $sub) : null;
        $user    = $vinculo?->user;
        $novo    = false;

        // `$user instanceof User` além do vínculo: a FK apaga em cascata, mas o tipo não sabe.
        if ($vinculo instanceof VinculoSocial && $user instanceof User) {
            if (($redirecionamento = $this->redirecionarSeIndisponivel($user, $mascarado, $provedor)) !== null) {
                return $redirecionamento;
            }

            $vinculo->registrarAcesso();
            $this->aceitarConviteSeHouver($contexto, $user, $email, $provedor);

            Log::channel('autenticacao')->info(
                "[LoginSocialController@retorno] Conta reconhecida pelo vínculo | provedor: {$provedor->value} - user: {$user->getKey()}",
                ['user_id' => $user->getKey(), 'provedor' => $provedor->value, 'vinculo_id' => $vinculo->getKey()],
            );
        } else {
            $user = $this->contaCom($email);

            if ($user instanceof User) {
                $this->aceitarConviteSeHouver($contexto, $user, $email, $provedor);
            }

            if (! $user instanceof User) {
                /*
                 * Duas portas de criação, as MESMAS do formulário (ADR-01): o convite válido do
                 * `?token=` (organização e papel do convite, e-mail tem de ser o convidado) ou o
                 * registro aberto com a organização do `?org=` (com tenancy, sem ela a porta recusa).
                 */
                $convite = Convite::valido($contexto['token'] ?? null);

                if ($convite instanceof Convite && ! $this->conviteEhPara($convite, $email)) {
                    Log::channel('autenticacao')->warning(
                        "[LoginSocialController@retorno] Recusado: o convite é para outro e-mail | provedor: {$provedor->value} - convite: {$convite->getKey()} - email: ".$mascarado,
                        ['motivo' => 'convite_para_outro_email', 'convite_id' => $convite->getKey(), 'email' => $mascarado, 'provedor' => $provedor->value],
                    );

                    return $this->recusar('Este convite é para outro e-mail. Entre com a conta do provedor que usa o e-mail convidado, ou cadastre-se pelo formulário.');
                }

                if (! $convite instanceof Convite && ! ConfiguracaoDoLogin::registroAberto()) {
                    /*
                     * A barreira do convite. Criar a conta aqui — o `updateOrCreate` da documentação do
                     * Socialite — transformaria qualquer pessoa com conta no provedor em usuária do
                     * sistema. Ver ADR-06 da wiki login-social-google.
                     */
                    Log::channel('autenticacao')->warning(
                        "[LoginSocialController@retorno] Recusado: não há conta e o registro está fechado | provedor: {$provedor->value} - email: ".$mascarado,
                        [
                            'motivo'   => 'conta_inexistente_registro_fechado',
                            'email'    => $mascarado,
                            'provedor' => $provedor->value,
                        ],
                    );

                    return $this->recusar('Não há conta com este e-mail. O acesso a este sistema é por convite.');
                }

                try {
                    $user = $convite instanceof Convite
                        ? $this->criarContaPorConvite($provedor, $convite, $email, $mascarado, $doProvedor->getName())
                        : $this->criarConta($provedor, $email, $mascarado, $doProvedor->getName(), RegistroAberto::organizacao($contexto['org'] ?? null));
                } catch (RuntimeException $e) {
                    /*
                     * `RegistroAberto::registrar()` recusa o que a porta do formulário recusa — com a
                     * tenancy ligada, uma conta sem organização não tem /app para entrar — e já
                     * registrou o motivo. Aqui só se fecha a volta com a mesma mensagem da conta
                     * inexistente: quem está do outro lado não escolheu organização nenhuma.
                     */
                    Log::channel('autenticacao')->warning(
                        "[LoginSocialController@retorno] Recusado: o registro aberto não aceitou a conta | provedor: {$provedor->value} - email: ".$mascarado,
                        [
                            'motivo'    => 'registro_aberto_recusou',
                            'email'     => $mascarado,
                            'provedor'  => $provedor->value,
                            'exception' => $e,
                        ],
                    );

                    return $this->recusar('Não há conta com este e-mail. O acesso a este sistema é por convite.');
                }

                $novo = true;
            } elseif (($redirecionamento = $this->redirecionarSeIndisponivel($user, $mascarado, $provedor)) !== null) {
                return $redirecionamento;
            } elseif (ConfiguracaoDoLogin::vinculoExigeConfirmacao()) {
                return $this->pedirConfirmacaoDoVinculo($provedor, $user, $sub, $mascarado);
            } else {
                $user->notify(new PrimeiroAcessoSocial($provedor, (string) request()->ip()));

                Log::channel('autenticacao')->info(
                    "[LoginSocialController@retorno] Primeiro acesso por este provedor — vínculo criado e aviso enviado | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado,
                    ['user_id' => $user->getKey(), 'email' => $mascarado, 'provedor' => $provedor->value, 'ip' => request()->ip()],
                );
            }

            if ($sub !== '') {
                VinculoSocial::vincular($user, $provedor, $sub);
            } else {
                Log::channel('autenticacao')->warning(
                    "[LoginSocialController@retorno] Provedor não devolveu identificador; sem vínculo, valendo o e-mail | provedor: {$provedor->value} - user: {$user->getKey()}",
                    ['user_id' => $user->getKey(), 'provedor' => $provedor->value, 'motivo' => 'sub_ausente'],
                );
            }
        }

        if (($redirecionamento = $this->redirecionarSeIndisponivel($user, $mascarado, $provedor)) !== null) {
            return $redirecionamento;
        }

        // Cadastro pendente de aprovação não abre sessão — conta nova OU existente.
        if ($user->aprovacao_pendente) {
            return $this->aguardarAprovacao($provedor, $user, $mascarado);
        }

        Auth::login($user);

        /*
         * A volta do provedor também DESTRAVA o bloqueio de sessão: `TelaBloqueio` oferece os
         * mesmos botões do login, para quem não tem senha local. Espelha o que o desbloqueio
         * por senha faz (`LockerScreen::sessionRegenerate()`,
         * vendor/marjose123/filament-lockscreen/src/Http/Livewire/LockerScreen.php:118-123);
         * o `Auth::login()` acima já migrou o id da sessão.
         */
        session()->put('lockscreen', false);
        session()->put('session_last_activity', time());

        Log::channel('autenticacao')->info(
            "[LoginSocialController@retorno] Autenticado pelo provedor | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado,
            [
                'user_id'    => $user->getKey(),
                'email'      => $mascarado,
                'conta_nova' => $novo,
                'provedor'   => $provedor->value,
            ],
        );

        return redirect()->to($novo ? $this->urlDoPerfil($user) : $this->urlDoPainel());
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
        return User::withTrashed()
            ->whereRaw('lower(email) = ?', [$email])
            ->first();
    }

    /**
     * Cria a conta de quem chegou pelo provedor, com o registro aberto ligado.
     *
     * Senha aleatória e descartada: a conta nasce sem senha utilizável, e quem quiser uma
     * usa a recuperação de senha. Guardar o token de acesso do provedor seria mais um segredo
     * em repouso sem nenhum uso — o kit não chama API de provedor em nome de ninguém. (A
     * consulta a `/user/emails` do GitHub usa o token do request e o descarta.)
     *
     * **Nenhum papel é atribuído**, e isso é deliberado: papel é o que dá acesso a painel
     * (`User::canAccessPanel()`), e decidir qual papel um registro aberto concede é da
     * feature de registro e aprovação, não desta. Conta sem papel não abre painel algum, e
     * esse é o comportamento correto do kit. Ver ADR-06 da wiki `login-social-google`.
     */
    private function criarConta(ProvedorSocial $provedor, string $email, string $mascarado, ?string $nome, ?Tenant $organizacao = null): User
    {
        /*
         * A MESMA porta do formulário. `RegistroAberto::registrar()` concede o papel único do
         * registro aberto, marca a pendência de aprovação quando ela está ligada, e recusa o que
         * a porta recusa. Medido numa instalação real: o `User::create()` cru que morava aqui
         * criava a conta SEM papel, e a tela de perfil para onde ela era mandada respondia 403 —
         * `canAccessPanel()` exige papel do painel. O README prometia o perfil.
         *
         * A organização vem do `?org=` da tela de registro, pela sessão (ADR-02 da wiki
         * cadastro-social-por-convite-e-organizacao). Sem ela, com a tenancy ligada, a porta recusa
         * (`sem_organizacao`) e quem chama trata.
         */
        $user = RegistroAberto::registrar([
            'name'     => filled($nome) ? $nome : $email,
            'email'    => $email,
            'password' => Str::password(32),
        ], $organizacao);

        /*
         * O provedor JÁ provou que o endereço está verificado — é a barreira que a pessoa
         * atravessou para chegar a esta linha. Pedir verificação depois disso é pedir a mesma
         * prova duas vezes, e a segunda por e-mail.
         *
         * É o mesmo argumento e a mesma linha de `Convite::aceitar()`, que grava a coluna porque
         * o token prova posse do endereço (ver o comentário de lá). O `config/kit.php` diz por
         * escrito que "quem vem de convite nunca é afetado" pelo middleware de e-mail
         * verificado; quem vem de login social tem prova igual ou melhor, e merece o mesmo.
         *
         * `email_verified_at` está FORA do `$fillable` do User — é estado, não entrada —, então
         * mass assignment o descartaria em silêncio. Daí o `forceFill`.
         *
         * Sem esta linha, com KIT_REGISTRO_VERIFICAR_EMAIL ligado, a conta nasce presa na tela
         * de "verifique seu e-mail" no instante seguinte a um OAuth bem-sucedido. Foi um caso de
         * teste que pegou.
         */
        // `origem` = o driver: a porta gravou 'registro', mas quem trouxe a pessoa foi o provedor.
        $user->forceFill(['email_verified_at' => now(), 'origem' => $provedor->value])->save();

        Log::channel('autenticacao')->info(
            "[LoginSocialController@criarConta] Conta criada por login social | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado,
            [
                'user_id'  => $user->getKey(),
                'email'    => $mascarado,
                'motivo'   => 'conta_criada_por_login_social',
                'provedor' => $provedor->value,
            ],
        );

        return $user;
    }

    /** Onde quem já tinha conta cai: o painel de negócio, com a organização default resolvida. */
    /**
     * Conta criada com a aprovação manual ligada: nasce sem papel e sem sessão.
     *
     * Espelha `RegistroPorConvite::register()`, mesma mensagem — quem se cadastra pelo
     * formulário e quem se cadastra pelo provedor esperam a mesma coisa. Logar aqui seria
     * mandar a pessoa para um 403: `canAccessPanel()` nega cadastro pendente.
     */
    private function aguardarAprovacao(ProvedorSocial $provedor, User $user, string $mascarado): RedirectResponse
    {
        Log::channel('autenticacao')->warning(
            "[LoginSocialController@retorno] Conta criada, pendente de aprovação — sem sessão | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado,
            [
                'user_id'  => $user->getKey(),
                'email'    => $mascarado,
                'motivo'   => 'aprovacao_pendente',
                'provedor' => $provedor->value,
            ],
        );

        Notification::make()
            ->title('Cadastro recebido')
            ->body('Sua conta foi criada e aguarda aprovação de quem administra o sistema. Você poderá entrar assim que ela for liberada.')
            ->success()
            ->persistent()
            ->send();

        return redirect()->to(Filament::getPanel('app')->getLoginUrl());
    }

    /**
     * Modo estrito: a primeira entrada de um provedor numa conta existente não vira sessão.
     *
     * Envia o link assinado (30 minutos) para o e-mail DA CONTA — a mesma prova do "Esqueceu a
     * senha?", exigida no momento em que importa — e devolve a pessoa ao login com o aviso.
     * ADR-03 da wiki vinculo-de-provedor-social.
     */
    private function pedirConfirmacaoDoVinculo(ProvedorSocial $provedor, User $user, string $sub, string $mascarado): RedirectResponse
    {
        $url = URL::temporarySignedRoute('auth.social.confirmar', now()->addMinutes(30), [
            'provedor' => $provedor->value,
            'user'     => $user->getKey(),
            'sub'      => $sub,
        ]);

        $user->notify(new ConfirmarVinculoSocial($provedor, $url));

        Log::channel('autenticacao')->warning(
            "[LoginSocialController@retorno] Primeira entrada por este provedor aguarda confirmação por e-mail | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado,
            ['user_id' => $user->getKey(), 'email' => $mascarado, 'provedor' => $provedor->value, 'motivo' => 'vinculo_aguardando_confirmacao', 'ip' => request()->ip()],
        );

        Notification::make()
            ->title('Confirme pelo e-mail')
            ->body("Esta é a primeira entrada pelo {$provedor->rotulo()} nesta conta. Enviamos um link para o e-mail dela — abra-o para confirmar e entrar. O link vale 30 minutos.")
            ->info()
            ->persistent()
            ->send();

        return redirect()->to(Filament::getPanel('app')->getLoginUrl());
    }

    /**
     * O link do e-mail de confirmação (modo estrito). A assinatura já foi checada pelo `signed`.
     *
     * `sub` já vinculado a OUTRA conta (dois links em corrida) recusa em vez de re-vincular: uma
     * identidade de provedor pertence a uma conta só.
     */
    public function confirmarVinculo(ProvedorSocial $provedor, Request $request): RedirectResponse
    {
        abort_unless(ConfiguracaoDoLogin::disponivel($provedor), 404);

        $user = User::withTrashed()->find($request->integer('user'));
        $sub  = trim((string) $request->query('sub'));

        if (! $user instanceof User || $sub === '') {
            return $this->recusar('Este link não é válido.');
        }

        $existente = VinculoSocial::de($provedor, $sub);

        if ($existente instanceof VinculoSocial && $existente->user_id !== $user->getKey()) {
            Log::channel('autenticacao')->warning(
                "[LoginSocialController@confirmarVinculo] Recusado: identidade do provedor já vinculada a outra conta | provedor: {$provedor->value} - user: {$user->getKey()}",
                ['user_id' => $user->getKey(), 'provedor' => $provedor->value, 'vinculo_de' => $existente->user_id, 'motivo' => 'sub_ja_vinculado'],
            );

            return $this->recusar("Esta conta do {$provedor->rotulo()} já está vinculada a outro usuário.");
        }

        $mascarado = Str::mask((string) $user->email, '*', 3);

        if (($redirecionamento = $this->redirecionarSeIndisponivel($user, $mascarado, $provedor)) !== null) {
            return $redirecionamento;
        }

        VinculoSocial::vincular($user, $provedor, $sub);

        if ($user->aprovacao_pendente) {
            return $this->aguardarAprovacao($provedor, $user, $mascarado);
        }

        Auth::login($user);

        session()->put('lockscreen', false);
        session()->put('session_last_activity', time());

        Log::channel('autenticacao')->info(
            "[LoginSocialController@confirmarVinculo] Vínculo confirmado pelo e-mail | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado,
            ['user_id' => $user->getKey(), 'email' => $mascarado, 'provedor' => $provedor->value],
        );

        return redirect()->to($this->urlDoPainel());
    }

    /**
     * `org` e `token` da tela de registro, para a volta do OAuth. Só strings não vazias.
     *
     * @return array{org?: string, token?: string}
     */
    private function contextoDeCadastro(): array
    {
        $contexto = [];

        foreach (['org', 'token'] as $chave) {
            $valor = request()->query($chave);

            if (is_string($valor) && trim($valor) !== '') {
                $contexto[$chave] = trim($valor);
            }
        }

        return $contexto;
    }

    private function conviteEhPara(Convite $convite, string $email): bool
    {
        return mb_strtolower(trim((string) $convite->email)) === $email;
    }

    /**
     * Se a conta não pode entrar, grava o aviso e manda para a tela de conta indisponível.
     */
    private function redirecionarSeIndisponivel(User $user, string $mascarado, ProvedorSocial $provedor): ?RedirectResponse
    {
        $motivo = $user->motivoDeIndisponibilidade();

        if ($motivo === null) {
            return null;
        }

        Log::channel('autenticacao')->warning(
            "[LoginSocialController@retorno] Login recusado: {$motivo} | provedor: {$provedor->value} - user: {$user->getKey()} - email: ".$mascarado,
            [
                'user_id'     => $user->getKey(),
                'email'       => $mascarado,
                'motivo'      => $motivo,
                'provedor'    => $provedor->value,
                'excluida_em' => $user->deleted_at?->toIso8601String(),
            ],
        );

        event(new Failed(Filament::getAuthGuard(), $user, []));

        return redirect()->to(
            ContaIndisponivelController::redirecionar($user, Filament::getPanel('app')->getLoginUrl()),
        );
    }

    /**
     * A porta do convite, a mesma do formulário: o e-mail é o do convite (já conferido), a
     * organização e o papel são os dele, e o convite é consumido. O provedor provou o e-mail.
     */
    private function criarContaPorConvite(ProvedorSocial $provedor, Convite $convite, string $email, string $mascarado, ?string $nome): User
    {
        $user = $convite->aceitar([
            'name'     => filled($nome) ? $nome : $email,
            'email'    => $email,
            'password' => Str::password(32),
        ]);

        $user->forceFill(['email_verified_at' => now(), 'origem' => $provedor->value])->save();

        Log::channel('autenticacao')->info(
            "[LoginSocialController@criarContaPorConvite] Conta criada por login social a partir de convite | provedor: {$provedor->value} - user: {$user->getKey()} - convite: {$convite->getKey()} - email: ".$mascarado,
            ['user_id' => $user->getKey(), 'convite_id' => $convite->getKey(), 'tenant_id' => $convite->tenant_id, 'email' => $mascarado, 'provedor' => $provedor->value, 'motivo' => 'conta_criada_por_convite_social'],
        );

        return $user;
    }

    /**
     * Conta existente (ou reconhecida pelo vínculo) que voltou com um convite válido para o SEU
     * e-mail aceita o convite — organização e papel — como o "Entrar e aceitar" do formulário.
     * Falha aqui não barra a entrada: só registra. ADR-04.
     *
     * @param  array<string, mixed>  $contexto
     */
    private function aceitarConviteSeHouver(array $contexto, User $user, string $email, ProvedorSocial $provedor): void
    {
        $convite = Convite::valido(is_string($contexto['token'] ?? null) ? $contexto['token'] : null);

        if (! $convite instanceof Convite || ! $this->conviteEhPara($convite, $email)) {
            return;
        }

        try {
            $convite->aceitarComoUsuarioExistente($user);

            Log::channel('autenticacao')->info(
                "[LoginSocialController@retorno] Convite aceito na volta do provedor por conta existente | provedor: {$provedor->value} - user: {$user->getKey()} - convite: {$convite->getKey()}",
                ['user_id' => $user->getKey(), 'convite_id' => $convite->getKey(), 'tenant_id' => $convite->tenant_id, 'provedor' => $provedor->value],
            );
        } catch (RuntimeException $e) {
            Log::channel('autenticacao')->warning(
                "[LoginSocialController@retorno] Convite não aceito na volta do provedor | provedor: {$provedor->value} - user: {$user->getKey()} - convite: {$convite->getKey()}",
                ['user_id' => $user->getKey(), 'convite_id' => $convite->getKey(), 'provedor' => $provedor->value, 'exception' => $e],
            );
        }
    }

    private function urlDoPainel(): string
    {
        return Filament::getPanel('app')->getUrl() ?? url('/');
    }

    /**
     * A tela do próprio perfil — o destino de quem acabou de se registrar pelo provedor.
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
