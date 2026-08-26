<?php

namespace App\Notifications;

use App\Support\ProvedorSocial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

/**
 * "Confirme a entrada pelo Google."
 *
 * O modo estrito (`KIT_SOCIALITE_VINCULO_CONFIRMAR=true`): a primeira entrada de um provedor
 * numa conta que já existia NÃO vira sessão. A pessoa recebe este e-mail com um link assinado de
 * 30 minutos (`auth.social.confirmar`); ao abri-lo, o vínculo nasce e ela entra. É a mesma prova
 * do "Esqueceu a senha?" — a caixa postal —, exigida no momento em que ela importa. ADR-03 da
 * wiki `vinculo-de-provedor-social`.
 */
class ConfirmarVinculoSocial extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProvedorSocial $provedor,
        #[SensitiveParameter] public readonly string $url,
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
            ->subject("Confirme a entrada no {$app} pelo {$rotulo}")
            ->greeting('Olá!')
            ->line("Alguém tentou entrar na sua conta do {$app} usando uma conta do {$rotulo} com o mesmo e-mail. Como é a primeira vez por esse caminho, precisamos que você confirme.")
            ->line('Se foi você, confirme pelo botão abaixo: o vínculo fica gravado e você entra. O link vale 30 minutos.')
            ->line('Se NÃO foi você, ignore esta mensagem — nada muda na sua conta.')
            ->action('Confirmar e entrar', $this->url)
            ->salutation('Atenciosamente, '.$app);
    }
}
