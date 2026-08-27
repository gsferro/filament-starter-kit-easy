<?php

namespace App\Notifications;

use App\Support\ProvedorSocial;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Sua conta foi acessada pelo Google pela primeira vez."
 *
 * Vai para o e-mail da conta quando um provedor aparece pela PRIMEIRA vez numa conta que já
 * existia — no modo padrão, em que a entrada acontece (ADR-01 e ADR-03 da wiki
 * `vinculo-de-provedor-social`). É detecção: se não foi a pessoa, ela troca (ou define) a senha
 * e avisa quem administra. Nada automático além do aviso.
 *
 * `ShouldQueue` como `ConviteDeAcesso`: sem worker de fila nada sai — `composer dev` sobe um.
 */
class PrimeiroAcessoSocial extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProvedorSocial $provedor,
        public readonly string $ip,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rotulo = $this->provedor->rotulo();
        $app    = (string) config('app.name');

        return (new MailMessage)
            ->subject("Sua conta no {$app} foi acessada pelo {$rotulo} pela primeira vez")
            ->greeting('Olá!')
            ->line("Alguém acabou de entrar na sua conta do {$app} usando uma conta do {$rotulo} com o mesmo e-mail — e foi a primeira vez que esse caminho foi usado.")
            ->line('Quando: '.now()->format('d/m/Y H:i').' · IP: '.$this->ip)
            ->line('Se foi você, não há nada a fazer: nas próximas vezes essa conta do '.$rotulo.' entra direto.')
            ->line('Se NÃO foi você, troque a sua senha agora (ou defina uma, pelo bloco "Definir senha por e-mail" do seu perfil) e avise quem administra o sistema.')
            ->action('Abrir o painel', Filament::getPanel('app')->getUrl())
            ->salutation('Atenciosamente, '.$app);
    }
}
