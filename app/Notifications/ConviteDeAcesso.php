<?php

namespace App\Notifications;

use App\Models\Convite;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/**
 * O e-mail do convite, com o link de aceite.
 *
 * `Notification` e não `Mailable` porque o destinatário ainda NÃO é um `User`: o envio é
 * `Notification::route('mail', $email)->notify(...)`, a rota on-demand do Laravel.
 *
 * `ShouldQueue` já é o job — o Laravel embrulha em `SendQueuedNotifications`. Com
 * `QUEUE_CONNECTION=database` (o default do kit) **o convite não sai sem um worker
 * rodando**; a fila parada aparece no monitor do /infra. Ver ADR-05.
 *
 * **Zero log aqui, de propósito.** Quem loga o envio é `Convite::enviar()`, com o
 * contexto todo. Este é justamente o escopo onde o token em claro existe — um `$context`
 * descuidado escrito aqui vazaria a credencial no arquivo que o Logs Explorer exibe.
 */
class ConviteDeAcesso extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Convite $convite,
        #[SensitiveParameter] public readonly string $token,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mensagem = (new MailMessage)
            ->subject('Você foi convidado para o '.config('app.name'))
            ->greeting('Olá!')
            ->line('Você recebeu um convite para acessar o '.config('app.name').'.');

        $organizacao = $this->convite->tenant?->nome;

        if ($organizacao !== null) {
            $rotulo = mb_strtolower((string) config('kit.tenancy.label', 'Organização'));

            $mensagem->line("O acesso é para a {$rotulo}: {$organizacao}.");
        }

        return $mensagem
            ->action('Aceitar convite', $this->url())
            ->line('Este convite expira em '.$this->convite->expira_em?->format('d/m/Y H:i').'.')
            ->line('Se você não esperava este convite, ignore esta mensagem.')
            ->salutation('Atenciosamente, '.config('app.name'));
    }

    /**
     * A URL de aceite, montada pelo painel — nunca por string literal.
     *
     * O slug vem de `getRegistrationRouteSlug()` e o path do painel de `->path('app')`:
     * duas coisas configuráveis que um literal dessincronizaria. E a rota de registro
     * fica FORA do grupo do tenant, então o link não carrega slug de organização — a
     * organização vem do token.
     */
    private function url(): string
    {
        return Filament::getPanel('app')->route('auth.register', ['token' => $this->token]);
    }
}
