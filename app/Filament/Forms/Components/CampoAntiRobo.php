<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components;

use App\Support\ConfiguracaoDoLogin;
use Closure;
use Ddr\FilamentCaptcha\Forms\Components\Captcha;
use Filament\Schemas\Schema;

/**
 * O desafio anti-robô das telas públicas — o `Captcha` do `ddr/filament-captcha` com as regras do kit.
 *
 * ## O que ele é
 *
 * Uma subclasse do componente do pacote (`vendor/ddr/filament-captcha/src/Forms/Components/Captcha.php`).
 * Widget, script, `siteverify` e a pontuação do reCAPTCHA v3 são do pacote; o kit acrescenta o que
 * a wiki `recaptcha-nas-telas-publicas` decidiu e o pacote não faz: a decisão de aparecer vinda
 * de `ConfiguracaoDoLogin::antiRobo()`, o rótulo de validação em português, os seletores que os
 * testes de navegador usam, e a redefinição do widget depois de cada verificação. O driver que o
 * pacote resolve é o do kit — `App\Support\GerenciadorAntiRobo`, com falha fechada e log.
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
 * O pacote já faz `->dehydrated(false)` (ADR-06: o token não pode chegar a `User::create()`) e o
 * `->required()` fica reafirmado aqui de propósito: o Laravel só executa regra de objeto ou
 * closure quando o campo está presente, e sem `required()` um envio SEM token pularia a
 * verificação inteira. É o mutante mais barato desta classe (M14 do `04`).
 *
 * ## O token é de uso único
 *
 * Depois de uma senha errada, o Filament re-renderiza o formulário e o widget continua marcado —
 * com um token que a verificação já gastou. Por isso há uma regra a mais, depois da do pacote,
 * que em QUALQUER resultado despacha `kit-anti-robo-redefinir`; as views publicadas em
 * `resources/views/vendor/filament-captcha/drivers/` escutam na janela e chamam `reset()` do
 * provedor (o v3 pede um token novo). ADR-06.
 */
final class CampoAntiRobo extends Captcha
{
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
            ->required(fn (): bool => ConfiguracaoDoLogin::antiRobo() !== null)
            ->extraFieldWrapperAttributes(fn (): array => [
                'class'          => 'fi-fo-anti-robo',
                'data-anti-robo' => ConfiguracaoDoLogin::antiRobo()?->value,
            ], merge: true)
            ->rules([
                // Em qualquer resultado: o token foi gasto, o widget precisa de outro.
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    $this->getLivewire()->dispatch(self::EVENTO_REDEFINIR);
                },
            ]);
    }
}
