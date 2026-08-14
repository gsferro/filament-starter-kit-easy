<?php

namespace App\Filament\Pages\Auth;

use App\Models\Convite;
use App\Models\User;
use Caresome\FilamentAuthDesigner\Pages\Auth\Register;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;

/**
 * A tela de aceite do convite — que é a página de REGISTRO nativa do Filament, com uma
 * guarda no `mount()`.
 *
 * Registro e convite passam a ser a mesma coisa: sem token válido na query string a
 * página recusa, então `/app/register` nunca vira cadastro aberto. Rate limit (por IP e
 * por e-mail), transação, hash de senha e auto-login vêm todos da página do Filament —
 * o kit escreve dois métodos de comportamento e dois de apresentação. Ver ADR-01.
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

    public function mount(): void
    {
        $this->convite = Convite::valido(request()->query('token'));

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
        $data['email'] = $this->convite()->email;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        return $this->convite()->aceitar($data);
    }

    /**
     * Mostrar o e-mail é o que faz a pessoa saber que chegou ao lugar certo. Estar
     * desabilitado NÃO é a segurança — a segurança é `mutateFormDataBeforeRegister()`.
     */
    protected function getEmailFormComponent(): Component
    {
        // Reusa o campo do Filament — inclusive o `->unique()` dele, que é quem recusa o
        // e-mail que virou usuário entre o convite e o clique.
        $campo = parent::getEmailFormComponent()
            ->default(fn (): string => $this->convite->email ?? '')
            ->disabled();

        // `helperText()` vive em `Field`, e a assinatura do Filament promete `Component`.
        return $campo instanceof Field
            ? $campo->helperText('O convite foi enviado para este endereço.')
            : $campo;
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Aceitar convite';
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
        Log::channel('autenticacao')->warning(
            '[RegistroPorConvite@mount] Registro sem convite valido recusado',
            [
                'motivo' => 'convite_invalido',
                'ip'     => request()->ip(),
            ],
        );

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
