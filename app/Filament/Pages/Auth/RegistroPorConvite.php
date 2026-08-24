<?php

namespace App\Filament\Pages\Auth;

use App\Models\Convite;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RegistroAberto;
use Caresome\FilamentAuthDesigner\Pages\Auth\Register;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SensitiveParameter;

/**
 * A tela `/app/register`, que atende DOIS modos: aceite de convite e registro aberto.
 *
 * **O nome da classe descreve o modo original, não os dois — e isso é decisão registrada,
 * não descuido.** Renomear para `TelaRegistro` custaria ~10 arquivos (esta página, três
 * pontos no `AppPanelProvider`, quatro arquivos de teste e `wikis/arquitetura.md`) sem mudar
 * uma linha de comportamento, e dois desses arquivos são asserções do prefixo de log
 * `[RegistroPorConvite@mount]` em testes do convite — ou seja, risco de regressão na porta
 * que menos pode quebrar, em troca de estética de nome. Ver ADR-04 em
 * `wikis/specs/feat/registro-e-aprovacao/registro-e-aprovacao/`. Este docblock é o que
 * substitui o nome como documentação: se você chegou aqui procurando onde fica o registro
 * aberto, é aqui.
 *
 * ## O garfo é por AUSÊNCIA de token, nunca por token inválido
 *
 * | Query string | Caminho |
 * |---|---|
 * | `?token=` válido | convite — idêntico ao de sempre, sem consultar o registro aberto |
 * | `?token=` inválido, expirado ou usado | `recusar()`, **mesmo com o registro aberto ligado** |
 * | sem `token`, registro aberto DESLIGADO (o default) | `recusar()` — idêntico ao de sempre |
 * | sem `token`, registro aberto LIGADO | formulário de cadastro aberto |
 *
 * A terceira linha é a que não pode mudar de forma: se o garfo fosse *"não achei convite
 * válido, então cai no modo aberto"*, `?token=lixo` viraria uma **segunda porta** para o
 * cadastro aberto — e justamente a que não passa pelo throttle de log de `recusar()` nem pela
 * mensagem genérica que não revela qual dos três motivos de recusa aconteceu. Ver ADR-03.
 *
 * ## O que cada modo faz nos quatro ganchos
 *
 * | Gancho | Convite | Aberto |
 * |---|---|---|
 * | `getEmailFormComponent()` | e-mail do convite, DESABILITADO | campo normal, habilitado |
 * | `mutateFormDataBeforeRegister()` | força o e-mail do convite | não mexe |
 * | `handleRegistration()` | `Convite::aceitar()` | `RegistroAberto::registrar()` |
 * | `getHeading()` | "Aceitar convite" | "Criar conta" |
 *
 * Transação, hash de senha, throttle e auto-login continuam vindo todos da página do
 * Filament, nos dois modos. Ver ADR-01 da wiki `convite-de-usuario`.
 *
 * **Sobre rate limit, com precisão**: o do Filament (por IP e por e-mail) vive dentro de
 * `register()`, o envio do formulário — `Register.php:73` e `:135-148`. O `mount()` do
 * Filament (`:57-63`) não tem nenhum, e o `mount()` do kit chama `recusar()` **antes** do
 * `parent::mount()`. Logo o caminho de recusa nunca alcançou o throttle do vendor, e ganhou
 * um próprio: ver o comentário em `recusar()`. A versão anterior deste docblock dizia que o
 * rate limit vinha todo do Filament — verdade para o formulário, falso para a recusa, e foi
 * essa imprecisão que sustentou a decisão de não escrever throttle nenhum (QA-01).
 */
class RegistroPorConvite extends Register
{
    /**
     * A classe pai já declara a propriedade
     * (`vendor/caresome/filament-auth-designer/src/Pages/Auth/Register.php:14`), então o
     * vazamento de `HasAuthDesignerLayout::boot()` já está contido lá. A redeclaração
     * aqui é a regra do kit (`.ai/rules/auth.md`) e o que o par de testes cobra: uma
     * linha custa menos que descobrir de novo por que a página de 2FA do Breezy morreu.
     */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    public ?Convite $convite = null;

    /** A organização de destino no modo aberto com tenancy. Nula no modo convite. */
    public ?Tenant $organizacao = null;

    public function mount(): void
    {
        $token = request()->query('token');

        // Token na URL: é convite, e o caminho é o de sempre — nem consulta o registro aberto.
        if (filled($token)) {
            $this->montarComoConvite(is_string($token) ? $token : null);

            return;
        }

        // Sem token: só é caminho legítimo se a instalação liberou o cadastro aberto. Com o
        // default (`false`), esta linha reproduz o comportamento que o kit sempre teve.
        if (! RegistroAberto::habilitado()) {
            $this->recusar();
        }

        /*
         * Com tenancy, a organização é OBRIGATÓRIA e vem do `?org={slug}`.
         *
         * A rota de registro do Filament é do PAINEL, não do tenant, então não existe
         * organização no caminho da URL. E registrar alguém sem organização nenhuma num painel
         * multi-organização o deixa num estado inalcançável: o Filament procura o tenant de
         * destino e não acha. Recusar com mensagem é melhor que criar conta que não abre nada.
         * Ver ADR-07.
         */
        if (config('kit.tenancy.enabled')) {
            $this->organizacao = RegistroAberto::organizacao(
                is_string($org = request()->query('org')) ? $org : null,
            );

            if (! $this->organizacao instanceof Tenant) {
                $this->recusar();
            }
        }

        parent::mount();
    }

    /** O `mount()` do modo convite, extraído só para o garfo caber numa leitura. */
    private function montarComoConvite(?string $token): void
    {
        $this->convite = Convite::valido($token);

        if (! $this->convite instanceof Convite) {
            $this->recusar();
        }

        // Já tem conta? Não há o que registrar. O token abre a porta; a senha que a pessoa
        // já tem é que atravessa. Ver ADR-01 e ADR-02 de
        // `wikis/specs/main/convite-para-usuario-existente/`.
        if (($existente = $this->convite->usuarioExistente()) !== null) {
            $this->desviarParaAceite($existente);
        }

        parent::mount();
    }

    /**
     * Desfaz o login que o vendor acabou de fazer, quando o cadastro nasceu pendente.
     *
     * `Register::register()` termina em `Filament::auth()->login($user)` e devolve um
     * `RegistrationResponse` que redireciona ao painel (`Register.php:106-112`). Um cadastro
     * pendente não entra em painel nenhum, então sem esta sobrescrita a pessoa receberia um
     * **403** logo depois de um cadastro que funcionou — ela não fez nada errado, e precisa de
     * uma frase, não de um código de erro.
     *
     * Só o último passo muda: throttle, transação, `saveRelationships()`, o evento `Registered`
     * e a regeneração de sessão continuam sendo os do vendor. Ver ADR-10.
     *
     * O `null` no começo NÃO é zelo: `parent::register()` devolve `null` quando o throttle
     * dispara, e ler `->aprovacao_pendente` de um `null` transformaria o 429 em 500.
     *
     * Aqui `redirect()` é seguro, ao contrário de `recusar()`: estamos numa AÇÃO Livewire, não
     * no `mount()`. A armadilha do Redirector do Livewire onde o Laravel espera código HTTP é
     * específica do `mount()`.
     */
    public function register(): ?RegistrationResponse
    {
        $resposta = parent::register();

        $usuario = Filament::auth()->user();

        if ($resposta === null || ! $usuario instanceof User || ! $usuario->aprovacao_pendente) {
            return $resposta;
        }

        Log::channel('autenticacao')->warning(
            "[RegistroPorConvite@register] Registro pendente de aprovacao — sessao encerrada | user: {$usuario->id}",
            [
                'user_id'   => $usuario->id,
                'email'     => Str::mask($usuario->email, '*', 3),
                'tenant_id' => $this->organizacao?->getKey(),
                'motivo'    => 'aprovacao_pendente',
            ],
        );

        Filament::auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        Notification::make()
            ->title('Cadastro recebido')
            ->body('Sua conta foi criada e aguarda aprovação de quem administra o sistema. Você poderá entrar assim que ela for liberada.')
            ->success()
            ->persistent()
            ->send();

        $this->redirect(Filament::getPanel('app')->getLoginUrl());

        return null;
    }

    /**
     * O convite é de quem já tem conta — três destinos, nenhum deles o formulário.
     *
     * Sai sempre por `HttpResponseException`, como `recusar()`, e pelo mesmo motivo: dentro
     * de `mount()` de página Livewire um `redirect()` solto devolve o Redirector do Livewire
     * onde o Laravel espera um código HTTP, e o request morre em 500. O
     * `Register::mount()` do próprio Filament faz isso em `:58-62` — nós rodamos antes dele.
     */
    private function desviarParaAceite(User $convidado): never
    {
        $autenticado = Filament::auth()->user();

        // Ninguém autenticado: manda entrar. NÃO consome o convite, e não guarda o token
        // em sessão — o aceite é ato explícito, e depois do login a oferta aparece no item
        // de menu com contagem. Um redirecionamento que vincula alguém a uma organização no
        // primeiro login é exatamente o que `requiresConfirmation()` existe para evitar.
        if (! $autenticado instanceof User) {
            $this->sair(
                'Entre para aceitar o convite',
                'Você já tem conta neste sistema. Entre com a sua senha — o convite aparece no menu do seu usuário.',
                'info',
                Filament::getPanel('app')->getLoginUrl(),
            );
        }

        // Autenticado com OUTRA conta: explica e mantém a sessão. Derrubar o login de
        // alguém por causa de um link é pior que explicar.
        if ($autenticado->getKey() !== $convidado->getKey()) {
            $this->sair(
                'Este convite não é para esta conta',
                'O convite foi enviado para outro endereço. Saia e entre com a conta convidada, ou peça um convite novo.',
                'warning',
                Filament::getPanel('app')->getUrl(),
            );
        }

        // É a pessoa certa, já autenticada: aceita na hora. A asserção de e-mail acontece
        // de novo dentro do model — de propósito (ADR-03).
        $this->convite->aceitarComoUsuarioExistente($autenticado);

        $this->sair(
            'Convite aceito',
            'Você agora faz parte de '.($this->convite()->tenant->nome ?? config('app.name')).'.',
            'success',
            $this->urlDaOrganizacao(),
        );
    }

    /** Para onde mandar depois de aceitar: a organização do convite, se houver. */
    private function urlDaOrganizacao(): string
    {
        $painel = Filament::getPanel('app');

        return $this->convite()->tenant !== null
            ? $painel->getUrl($this->convite()->tenant)
            : $painel->getUrl();
    }

    private function sair(string $titulo, string $corpo, string $tom, string $destino): never
    {
        Notification::make()->title($titulo)->body($corpo)->{$tom}()->persistent()->send();

        throw new HttpResponseException(new RedirectResponse($destino));
    }

    /**
     * Gancho do Filament em `Register.php:91`, dentro da transação.
     *
     * O campo de e-mail é exibido desabilitado, e estado de Livewire é do cliente: a
     * autoridade sobre QUEM está sendo cadastrado é o convite, não o formulário.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(#[SensitiveParameter] array $data): array
    {
        // No modo aberto o e-mail é do formulário, e quem recusa endereço já cadastrado é o
        // `->unique()` do campo do Filament. Só o convite tem autoridade externa sobre o e-mail.
        if ($this->convite instanceof Convite) {
            $data['email'] = $this->convite->email;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        return $this->convite instanceof Convite
            ? $this->convite->aceitar($data)
            : RegistroAberto::registrar($data, $this->organizacao);
    }

    /**
     * Mostrar o e-mail é o que faz a pessoa saber que chegou ao lugar certo. Estar
     * desabilitado NÃO é a segurança — a segurança é `mutateFormDataBeforeRegister()`.
     */
    protected function getEmailFormComponent(): Component
    {
        // Reusa o campo do Filament — inclusive o `->unique()` dele, que é quem recusa o
        // e-mail que virou usuário entre o convite e o clique. No modo aberto é ele, sozinho,
        // que impede cadastro duplicado.
        $campo = parent::getEmailFormComponent();

        // No modo aberto o campo é normal: a pessoa escolhe o próprio endereço.
        if (! $this->convite instanceof Convite) {
            return $campo;
        }

        $campo = $campo
            ->default(fn (): string => $this->convite->email ?? '')
            ->disabled();

        // `helperText()` vive em `Field`, e a assinatura do Filament promete `Component`.
        return $campo instanceof Field
            ? $campo->helperText('O convite foi enviado para este endereço.')
            : $campo;
    }

    public function getHeading(): string|Htmlable|null
    {
        if ($this->convite instanceof Convite) {
            return 'Aceitar convite';
        }

        // O nome da organização no título é o que diz à pessoa ONDE ela está entrando — com
        // tenancy o cadastro sempre pertence a uma, e o `?org=` não é legível na URL.
        return $this->organizacao instanceof Tenant
            ? "Criar conta em {$this->organizacao->nome}"
            : 'Criar conta';
    }

    /** O convite desta tela, ou a saída — nunca um null que vaze para o resto do fluxo. */
    private function convite(): Convite
    {
        return $this->convite ?? $this->recusar();
    }

    /**
     * Sem convite válido não existe cadastro.
     *
     * Sai por `HttpResponseException`, e não por `redirect()` solto: dentro de `mount()`
     * de página Livewire o `redirect()` devolve o Redirector do Livewire onde o Laravel
     * espera um código HTTP, e o request morre em 500 — foi o bug de `TelaBloqueio` (ver
     * a nota em `:74-85` dela). O `Register::mount()` do Filament faz exatamente isso em
     * `:60`; aqui não.
     *
     * Resposta ÚNICA para os três motivos: quem tem o link não descobre se o token não
     * existe, se expirou ou se já foi usado. E o destino é o LOGIN, não um 403: quem
     * clica num convite vencido precisa ir para lá de qualquer forma — inclusive no caso
     * "já tenho conta". Ver ADR-02 e ADR-03.
     */
    private function recusar(): never
    {
        /*
         * O throttle protege o LOG, não a resposta — e essa escolha tem motivo.
         *
         * A recusa é o único caminho desta tela que grava sem autenticação nenhuma: um `curl`
         * em laço com token inventado escrevia uma linha de `warning` por request, no channel
         * `autenticacao`, que é driver `daily` com 14 dias de retenção — e é o mesmo arquivo
         * que o Logs Explorer do `/infra` abre. Medido em QA-01 do relatório desta wiki: 12
         * GETs anônimos, 12 linhas, nenhum 429.
         *
         * Por que não throttle na rota nem `TooManyRequestsException` para fora: quem tem
         * token VÁLIDO não pode ser barrado por causa do vizinho de NAT, e um 429 numa tela
         * de aceite troca uma mensagem clara ("convite inválido ou expirado") por uma tela de
         * erro. Então o limite não muda o que a pessoa vê — muda quantas vezes o kit escreve
         * em disco.
         *
         * `rateLimit()` vem da trait `WithRateLimiting`, que já está na classe pai
         * (`vendor/filament/filament/src/Auth/Pages/Register.php:45`) e é o mesmo mecanismo que
         * o Filament usa no envio do formulário (`:73`). A chave é
         * `componente|método|IP`, então o limite é por IP.
         *
         * Cinco por dez minutos por IP: passar disso não some com o sinal de abuso — a janela
         * reabre, e as cinco primeiras de cada janela continuam registradas. O que morre é o
         * crescimento sem teto.
         *
         * O que este throttle NÃO é: defesa contra adivinhar token. `Str::random(64)` sobre 62
         * caracteres não é força-brutável, e a resposta é idêntica para os três motivos de
         * recusa (ADR-02), então não há oráculo de estado. Ver a hipótese rejeitada em QA-01.
         */
        try {
            $this->rateLimit(maxAttempts: 5, decaySeconds: 600, method: 'recusar');

            Log::channel('autenticacao')->warning(
                '[RegistroPorConvite@mount] Registro sem convite valido recusado',
                [
                    'motivo' => 'convite_invalido',
                    'ip'     => request()->ip(),
                ],
            );
        } catch (TooManyRequestsException) {
            // De propósito sem log: escrever aqui recriaria a amplificação que o limite existe
            // para cortar. A pessoa continua vendo a mesma mensagem.
        }

        Notification::make()
            ->title('Convite inválido ou expirado')
            ->body('Peça um convite novo a quem administra o sistema. Se você já tem conta, entre por aqui.')
            ->danger()
            ->persistent()
            ->send();

        throw new HttpResponseException(
            new RedirectResponse(Filament::getPanel('app')->getLoginUrl()),
        );
    }
}
