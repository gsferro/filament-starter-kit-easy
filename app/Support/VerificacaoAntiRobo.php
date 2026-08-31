<?php

declare(strict_types=1);

namespace App\Support;

use Ddr\FilamentCaptcha\Drivers\CaptchaDriver;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * O driver do pacote embrulhado com o que ele não tem: falha FECHADA e log.
 *
 * Os quatro drivers do `ddr/filament-captcha` fazem o `POST` ao `siteverify` sem `try/catch`
 * (`vendor/ddr/filament-captcha/src/Drivers/RecaptchaV2Driver.php:30-36`, e igual nos outros
 * três): provedor fora do ar vira `ConnectionException`, que vira 500 na tela de login. A ADR-04
 * da wiki `recaptcha-nas-telas-publicas` exige o contrário — provedor caído recusa o envio, com um
 * `warning` distinto no canal `autenticacao`, e a pessoa vê o erro de validação, não uma tela de
 * erro. Este decorator devolve isso sem tocar nos drivers (ADR-03 da wiki
 * `adotar-ddr-filament-captcha`).
 *
 * `token_invalido` cobre também a resposta 5xx: o driver do pacote junta "HTTP não-2xx" e
 * "`success: false`" num único `false` (`RecaptchaV2Driver.php:38`), e o kit não vê o status. Se
 * a trilha precisar separar os dois, o caminho é estender o driver, não este decorator.
 *
 * Nem o token nem a chave secreta entram no log — o token é credencial de uso único e a chave é
 * segredo. O IP entra, porque é a trilha que o `/infra` abre quando alguém reclama de bloqueio.
 *
 * ponytail: sem `timeout` próprio — o pacote usa o `Http::` com o default do Laravel (30 s). Se
 * um provedor lento virar reclamação, o ajuste é `Http::globalOptions(['timeout' => 5])` num
 * provider, não aqui.
 */
final class VerificacaoAntiRobo extends CaptchaDriver
{
    public function __construct(
        private readonly CaptchaDriver $driver,
        private readonly string $provedor,
    ) {
        parent::__construct([]);
    }

    public function getSiteKey(): ?string
    {
        return $this->driver->getSiteKey();
    }

    public function getScriptUrl(): string
    {
        return $this->driver->getScriptUrl();
    }

    public function getView(): string
    {
        return $this->driver->getView();
    }

    public function verify(string $token): bool
    {
        $ip = request()->ip();

        try {
            $aceito = $this->driver->verify($token);
        } catch (Throwable $e) {
            Log::channel('autenticacao')->warning(
                "[VerificacaoAntiRobo@verify] Verificação anti-robô indisponível — envio recusado | provedor: {$this->provedor} - ip: {$ip}",
                [
                    'motivo'    => 'verificacao_indisponivel',
                    'provedor'  => $this->provedor,
                    'ip'        => $ip,
                    'exception' => $e,
                ],
            );

            return false;
        }

        if (! $aceito) {
            Log::channel('autenticacao')->warning(
                "[VerificacaoAntiRobo@verify] Token anti-robô recusado pelo provedor | provedor: {$this->provedor} - ip: {$ip}",
                [
                    'motivo'   => 'token_invalido',
                    'provedor' => $this->provedor,
                    'ip'       => $ip,
                ],
            );
        }

        return $aceito;
    }
}
