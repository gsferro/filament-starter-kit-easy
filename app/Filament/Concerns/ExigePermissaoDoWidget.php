<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

/**
 * A permissão `View:{Widget}` passa a DECIDIR a exibição do widget.
 *
 * Mesmo buraco das Pages, e pelo mesmo motivo: o Shield gerava `View:{Widget}` para os 23 widgets
 * dos painéis `/admin` e `/infra`, o `PapeisSeeder` as entregava, a tela de papéis as mostrava — e
 * nada as consultava. `vendor/filament/widgets/src/Widget.php:34-37` é
 * `canView(): bool { return true; }`.
 *
 * O que o kit tinha em 18 dos 23 widgets **não era autorização**: era
 * `rescue(fn () => Schema::hasTable(...), false)`, que responde "a tabela desta fonte existe?" —
 * necessário, porque widget que estoura derruba o dashboard inteiro, e várias fontes são de plugin
 * opcional. A barreira efetiva de todos eles era só o `User::canAccessPanel()`.
 *
 * ## Por que não `use HasWidgetShield;` direto
 *
 * **Método de classe vence método de trait, em silêncio.** Nos 18 widgets que já declaram
 * `canView()`, a linha seria no-op: nada quebraria, nada avisaria, e a permissão continuaria sem ser
 * consultada. Foi o defeito que ADR-01 desta feature existe para evitar.
 *
 * ## Como usar
 *
 * Widget sem regra própria: só `use ExigePermissaoDoWidget;`.
 *
 * Widget cuja fonte é tabela opcional: `use ExigePermissaoDoWidget;` e o corpo da checagem em
 * `fonteDeDadosDisponivel()` — **não** em `canView()`, que é o método que este trait publica.
 *
 * ```php
 * protected static function fonteDeDadosDisponivel(): bool
 * {
 *     return (bool) rescue(fn (): bool => Schema::hasTable('minha_tabela'), false);
 * }
 * ```
 *
 * As duas condições são `&&`: sem a permissão o widget não aparece nem com a tabela presente; sem a
 * tabela ele não aparece nem para quem tem a permissão. O segundo lado é o que impede o dashboard de
 * morrer numa instalação sem o plugin.
 *
 * Fail-open herdado do vendor (chave não resolvida ou usuário nulo caem em `parent::canView()`):
 * mesma análise de `ExigePermissaoDaTela`, ADR-01.
 */
trait ExigePermissaoDoWidget
{
    use HasWidgetShield {
        canView as protected visivelPelaPermissao;
    }

    public static function canView(): bool
    {
        return static::visivelPelaPermissao() && static::fonteDeDadosDisponivel();
    }

    /**
     * A fonte de dados deste widget está disponível?
     *
     * Sobrescreva ESTE método, nunca o `canView()`: a sobrescrita do `canView()` na classe vence o
     * deste trait em silêncio e desliga a permissão.
     */
    protected static function fonteDeDadosDisponivel(): bool
    {
        return true;
    }
}
