<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Support\ConfiguracaoDoLogin;
use App\Support\ProvedorAntiRobo;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * O desafio anti-robô das telas públicas — um campo para os três provedores e as três telas.
 *
 * ## O que ele é
 *
 * Um `Field` cuja view renderiza o widget do provedor (`App\Support\ProvedorAntiRobo`) e cuja
 * regra de validação leva o token que o widget devolveu ao `siteverify` do provedor, com a chave
 * secreta. `success: true` passa; qualquer outra coisa — token recusado, provedor fora do ar,
 * resposta 5xx — reprova com a mesma mensagem (falha FECHADA, ADR-04) e um `warning` distinto no
 * canal `autenticacao`.
 *
 * ## Por que `->visible()` e não um `if` na página
 *
 * Componente oculto no Filament não é renderizado e é pulado por `Schema::getValidationRules()`
 * (`vendor/filament/schemas/src/Concerns/CanBeValidated.php:75-79`, via
 * `isNeitherDehydratedNorValidated()` em `.../Components/Concerns/HasState.php:801-821`). Com a
 * proteção desligada, o resultado é idêntico ao de o campo não existir — sem script externo, sem
 * `<div>`, sem regra — mas a decisão é avaliada no RENDER e na VALIDAÇÃO, não na montagem do
 * formulário. É esse detalhe que deixa a chave viver na tela de Settings (`.ai/rules/settings.md`),
 * e é por isso que as três páginas chamam `acrescentarA()` sempre, sem condição. ADR-05.
 *
 * ## Por que `->dehydrated(false)`
 *
 * `Register::register()` entrega `$this->form->getState()` a `handleRegistration()`, que no kit
 * vira `Convite::aceitar($data)` ou `RegistroAberto::registrar($data)`, que chegam a
 * `User::create()`. Uma chave a mais nesse array não é bem-vinda. O campo continua VALIDADO:
 * `isNeitherDehydratedNorValidated()` devolve `false` quando `isValidatedWhenNotDehydrated()` é
 * verdadeiro, que é o default (`HasState.php:796-810`). ADR-06.
 *
 * ## O token é de uso único
 *
 * Depois de uma senha errada, o Filament re-renderiza o formulário e o widget continua marcado —
 * com um token que a nossa verificação já gastou. O segundo envio falharia por "token já usado".
 * Por isso a regra, em QUALQUER resultado, despacha `kit-anti-robo-redefinir`; a view escuta na
 * janela e chama `reset()` do provedor. ADR-06.
 *
 * ## `required()` não é redundante com a regra
 *
 * O Laravel só executa regra de closure quando o campo está presente; sem `required()`, um envio
 * SEM token pularia a verificação inteira. É o mutante mais barato desta classe (M14 do `04`).
 */
final class CampoAntiRobo extends Field
{
    protected string $view = 'filament.forms.components.campo-anti-robo';

    public const string EVENTO_REDEFINIR = 'kit-anti-robo-redefinir';

    public static function getDefaultName(): string
    {
        return 'anti_robo';
    }

    /** O campo no fim do formulário de uma tela pública. A única linha que as três páginas chamam. */
    public static function acrescentarA(Schema $schema): Schema
    {
        if (ConfiguracaoDoLogin::antiRobo() === null) {
            return $schema;
        }

        return $schema->components([
            ...$schema->getComponents(withHidden: true),
            self::make(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->validationAttribute('verificação anti-robô')
            ->visible(fn (): bool => ConfiguracaoDoLogin::antiRobo() !== null)
            ->required()
            ->dehydrated(false)
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    $confirmado = $this->confirmarToken(is_string($value) ? $value : '');

                    // Em qualquer resultado: o token foi gasto, o widget precisa de outro.
                    $this->getLivewire()->dispatch(self::EVENTO_REDEFINIR);

                    if (! $confirmado) {
                        $fail('Não foi possível confirmar que você não é um robô. Marque a caixa de novo e tente outra vez.');
                    }
                },
            ]);
    }

    /** Para a view. Só é chamado com o campo visível, ou seja, com `antiRobo()` não nulo. */
    public function getProvedor(): ProvedorAntiRobo
    {
        return ConfiguracaoDoLogin::antiRobo() ?? ProvedorAntiRobo::Recaptcha;
    }

    /** Para a view. A chave PÚBLICA — a secreta não tem método aqui de propósito. */
    public function getChaveDoSite(): string
    {
        return ConfiguracaoDoLogin::chaveDoSiteAntiRobo();
    }

    /**
     * O `POST` ao `siteverify` do provedor. `true` só com HTTP 2xx E `success: true` — o Google
     * responde 200 com `success: false` para token inválido, então `successful()` sozinho não basta.
     *
     * `timeout(5)` e sem `retry`: a pessoa está esperando numa tela pública, e três tentativas
     * sobre um provedor caído triplicariam o tempo até o erro (ADR-04). `asForm()` porque o Google
     * exige `application/x-www-form-urlencoded`; os outros dois aceitam os dois formatos.
     *
     * Nem o token nem a chave secreta entram no log — o token é credencial de uso único e a chave
     * é segredo.
     */
    private function confirmarToken(string $token): bool
    {
        $provedor = $this->getProvedor();
        $ip       = request()->ip();

        try {
            $resposta = Http::asForm()
                ->timeout(5)
                ->post($provedor->urlDeVerificacao(), [
                    'secret'   => ConfiguracaoDoLogin::chaveSecretaAntiRobo(),
                    'response' => $token,
                    'remoteip' => $ip,
                ]);
        } catch (Throwable $e) {
            Log::channel('autenticacao')->warning(
                "[CampoAntiRobo@confirmarToken] Verificação anti-robô indisponível — envio recusado | provedor: {$provedor->value} - ip: {$ip}",
                [
                    'motivo'    => 'verificacao_indisponivel',
                    'provedor'  => $provedor->value,
                    'ip'        => $ip,
                    'exception' => $e,
                ],
            );

            return false;
        }

        if ($resposta->successful() && $resposta->json('success') === true) {
            return true;
        }

        Log::channel('autenticacao')->warning(
            "[CampoAntiRobo@confirmarToken] Token anti-robô recusado pelo provedor | provedor: {$provedor->value} - ip: {$ip}",
            [
                'motivo'   => $resposta->successful() ? 'token_invalido' : 'verificacao_indisponivel',
                'provedor' => $provedor->value,
                'ip'       => $ip,
                'status'   => $resposta->status(),
                'erros'    => $resposta->json('error-codes'),
            ],
        );

        return false;
    }
}
