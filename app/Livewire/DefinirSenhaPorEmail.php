<?php

namespace App\Livewire;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;
use SensitiveParameter;

/**
 * Bloco do perfil: "Definir senha por e-mail".
 *
 * Existe porque a conta criada por login social tem uma senha aleatória que ninguém conhece
 * (`LoginSocialController::criarConta()` → `Str::password(32)`), e TRÊS coisas do kit exigem a
 * senha atual: a troca de senha do Breezy (`current_password`), ligar o 2FA
 * (`PasswordButtonAction`) e desbloquear a sessão (lockscreen). Medido no navegador do
 * solicitante, numa instalação real: a pessoa entrava pelo Google, caía no perfil, e não
 * conseguia nenhuma das três.
 *
 * O bloco não tenta descobrir se a conta veio de um provedor — não há coluna para isso, por
 * decisão registrada no README ("Cria coluna nova em `users`: nenhuma") — e nem precisa: vale
 * para quem não tem senha E para quem não lembra a atual. A prova de identidade é a mesma do
 * "Esqueceu a senha?": o e-mail. Por isso o fluxo é o do Filament, inteiro — o mesmo broker, a
 * mesma notificação, a mesma URL assinada — e não uma troca de senha sem a atual, que
 * transformaria qualquer sessão esquecida aberta numa troca de credencial.
 *
 * A sessão termina depois do envio, e isso não é estilo: a página de redefinição do Filament
 * recusa quem está logado (`ResetPassword::mount()` redireciona para o painel), então o link
 * chegaria a uma pessoa que não consegue abri-lo. O aviso persistente diz o que fazer.
 */
class DefinirSenhaPorEmail extends MyProfileComponent
{
    use WithRateLimiting;

    /**
     * Logo abaixo do bloco "Senha" do Breezy (20): é a alternativa a ele. Sem tipo nativo porque
     * o pai declara a propriedade sem tipo, e o PHP proíbe o filho de acrescentar um.
     *
     * @var int
     */
    public static $sort = 21;

    protected string $view = 'livewire.definir-senha-por-email';

    public function enviar(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException) {
            Notification::make()
                ->title('Aguarde um instante antes de pedir outro link.')
                ->danger()
                ->send();

            return;
        }

        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $mascarado = Str::mask($user->email, '*', 3);

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            ['email' => $user->email],
            function (CanResetPassword $destinatario, #[SensitiveParameter] string $token): void {
                $notification      = app(ResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $destinatario);

                $destinatario->notify($notification);
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            Log::channel('autenticacao')->warning(
                "[DefinirSenhaPorEmail@enviar] Link de definição de senha não enviado | user: {$user->getKey()} - status: {$status}",
                ['user_id' => $user->getKey(), 'email' => $mascarado, 'status' => $status],
            );

            Notification::make()
                ->title('Não foi possível enviar o link agora.')
                ->body('Tente de novo em alguns minutos.')
                ->danger()
                ->send();

            return;
        }

        Log::channel('autenticacao')->info(
            "[DefinirSenhaPorEmail@enviar] Link de definição de senha enviado, sessão encerrada | user: {$user->getKey()} - email: {$mascarado}",
            ['user_id' => $user->getKey(), 'email' => $mascarado, 'painel' => Filament::getCurrentOrDefaultPanel()->getId()],
        );

        $loginUrl = Filament::getCurrentOrDefaultPanel()->getLoginUrl();

        Filament::auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        Notification::make()
            ->title('Link enviado')
            ->body("Enviamos um link para {$user->email}. Abra-o para definir a sua senha e entre de novo.")
            ->success()
            ->persistent()
            ->send();

        $this->redirect($loginUrl);
    }
}
