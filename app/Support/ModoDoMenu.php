<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Support\Contracts\HasLabel;

/**
 * Como o menu lateral recolhe no computador — padrão da instalação e escolha de cada pessoa (wiki
 * `feat/preferencias-de-aparencia`, P-09).
 *
 * A única das quatro preferências de aparência que não sai de variável CSS: é o par
 * `->sidebarCollapsibleOnDesktop()` / `->sidebarFullyCollapsibleOnDesktop()` do painel. Os dois
 * aceitam Closure e são avaliados no render do layout
 * (`vendor/filament/filament/resources/views/components/layout/index.blade.php`), então a escolha
 * vale por request, como a densidade.
 */
enum ModoDoMenu: string implements HasLabel
{
    /** Recolhido, o menu continua mostrando os ícones — o comportamento de sempre do kit. */
    case Icones = 'icones';

    /** Recolhido, o menu some inteiro; o botão para reabrir fica no topo. */
    case Oculto = 'oculto';

    public function getLabel(): string
    {
        return match ($this) {
            self::Icones => 'Manter os ícones ao recolher',
            self::Oculto => 'Esconder tudo ao recolher',
        };
    }

    /**
     * As opções de um Select, como `valor => rótulo` — array cru, pelo motivo de
     * `DensidadeDoLayout::opcoes()`.
     *
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $opcoes, self $modo): array => [...$opcoes, $modo->value => $modo->getLabel()],
            [],
        );
    }

    /** O padrão gravado nas configurações, lido POR REQUEST. */
    public static function deConfig(): self
    {
        return self::coagir(config('kit.modo_do_menu'));
    }

    /** Qualquer coisa vinda de fora → um modo válido, sem estourar (P-12). */
    public static function coagir(mixed $bruto): self
    {
        return (is_string($bruto) ? self::tryFrom($bruto) : null) ?? self::padrao();
    }

    /** O valor de fábrica, numa cópia só: o comportamento que o kit sempre teve. */
    public static function padrao(): self
    {
        return self::Icones;
    }
}
