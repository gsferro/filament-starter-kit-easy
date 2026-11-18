<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Support\Contracts\HasLabel;

/**
 * O tamanho da fonte dos três painéis — padrão da instalação e escolha de cada pessoa (wiki
 * `feat/preferencias-de-aparencia`, P-08).
 *
 * ## O mecanismo é o mesmo da densidade
 *
 * A CSS do Filament 5 é Tailwind 4: todo tamanho de texto é `font-size:var(--text-*)` (o
 * `--text-sm` sozinho aparece 125 vezes em `public/css/filament/filament/app.css`), e as sete
 * variáveis são declaradas uma vez só, dentro de `@layer theme`. Redeclará-las **fora de layer**
 * vence aquela layer sem `!important` e sem CSS por classe `fi-*` — o mesmo motivo medido em
 * `DensidadeDoLayout`. As alturas de linha (`--text-sm--line-height` e irmãs) são razões sem
 * unidade (`calc(1.25 / .875)`), então acompanham o tamanho sozinhas.
 *
 * O texto escala e o espaçamento não: quem quer as duas coisas combina com a densidade.
 */
enum TamanhoDaFonte: string implements HasLabel
{
    /** 87,5% — cabe mais texto, para tela grande e vista boa. */
    case Pequena = 'pequena';

    /** O padrão do Filament: nenhuma declaração é emitida. */
    case Normal = 'normal';

    /** 112,5%. */
    case Grande = 'grande';

    /** 125%. */
    case MuitoGrande = 'muito-grande';

    /**
     * Os tamanhos que o Filament declara, em rem, na ordem de `@layer theme` da folha publicada.
     *
     * @var array<string, float>
     */
    private const BASE = [
        'xs'   => 0.75,
        'sm'   => 0.875,
        'base' => 1.0,
        'lg'   => 1.125,
        'xl'   => 1.25,
        '2xl'  => 1.5,
        '3xl'  => 1.875,
    ];

    public function getLabel(): string
    {
        return match ($this) {
            self::Pequena     => 'Pequena (87,5%)',
            self::Normal      => 'Normal — o padrão do Filament',
            self::Grande      => 'Grande (112,5%)',
            self::MuitoGrande => 'Muito grande (125%)',
        };
    }

    /**
     * As opções de um Select, como `valor => rótulo` — array cru, e não a classe, pelo motivo
     * documentado em `DensidadeDoLayout::opcoes()`.
     *
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $opcoes, self $tamanho): array => [...$opcoes, $tamanho->value => $tamanho->getLabel()],
            [],
        );
    }

    /** O fator sobre o tamanho do Filament. */
    public function fator(): float
    {
        return match ($this) {
            self::Pequena     => 0.875,
            self::Normal      => 1.0,
            self::Grande      => 1.125,
            self::MuitoGrande => 1.25,
        };
    }

    /**
     * As declarações `--text-*` deste tamanho, ou `null` quando não há nada a declarar.
     *
     * `null` no normal é o mesmo contrato de `DensidadeDoLayout::espacamento()`: nenhum `<style>`
     * inútil em quem nunca mexeu nisto.
     */
    public function declaracoes(): ?string
    {
        if ($this === self::Normal) {
            return null;
        }

        $declaracoes = [];

        foreach (self::BASE as $nome => $rem) {
            $declaracoes[] = '--text-'.$nome.':'.round($rem * $this->fator(), 4).'rem';
        }

        return implode(';', $declaracoes);
    }

    /** O padrão gravado nas configurações, lido POR REQUEST (ver `DensidadeDoLayout::deConfig()`). */
    public static function deConfig(): self
    {
        return self::coagir(config('kit.tamanho_da_fonte'));
    }

    /** Qualquer coisa vinda de fora → um tamanho válido, sem estourar (P-12). */
    public static function coagir(mixed $bruto): self
    {
        return (is_string($bruto) ? self::tryFrom($bruto) : null) ?? self::padrao();
    }

    /** O valor de fábrica, numa cópia só. */
    public static function padrao(): self
    {
        return self::Normal;
    }
}
