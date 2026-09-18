<?php

declare(strict_types=1);

/**
 * O rótulo do item ATIVO da navegação no topo tem de passar o mínimo WCAG AA nos DOIS temas.
 *
 * Um painel com `->topNavigation()` pinta o item ativo com `text-primary-600` sobre
 * `bg-gray-50` (`vendor/filament/filament/resources/css/components/topbar.css:80-82` e `:73`),
 * e o par não alcança 4,5:1 em nove das vinte e uma paletas do Filament — `Amber`, que é o
 * default quando `KIT_COR_PRIMARIA` está vazio, dá 3,06:1. Ou seja: a instalação recém-feita
 * cai justamente no pior caso.
 *
 * Com o menu lateral, que é o padrão do kit, nada disto se aplica — o Filament não emite
 * `.fi-topbar-item` nenhum. A correção é inerte por construção em quem não usa o topo.
 *
 * A correção é UMA linha de CSS em `resources/css/filament/kit.css`, e uma linha de CSS não
 * tem teste óbvio. Este arquivo é o mesmo desenho de três elos que
 * `ContrasteDoIndicadorDeAmbienteTest` já usava:
 *
 *   1. o `kit.css` é a fonte do degrau — o teste LÊ o número de lá, não o redigita;
 *   2. o contraste é recalculado a partir das paletas do próprio Filament;
 *   3. a folha do vendor ainda declara as classes que o seletor do kit usa — sem isto a
 *      regra viraria CSS morto sem nada acusar.
 *
 * O elo que falta — "a regra vence de fato na tela renderizada" — é do axe-core, em
 * `TemaEscuroTest`. Aqui não dá para medir especificidade.
 */

use Filament\Support\Colors\Color;

/**
 * As vinte e uma paletas nomeadas do Filament.
 *
 * A cor primária do kit pode ser QUALQUER hexadecimal (`kit.cor_primaria_hex`), então nenhum
 * teste cobre o domínio inteiro. O que dá para garantir é o conjunto fechado que o
 * `CustomizadorDaInstalacao` oferece e que o Filament usa como default — e é nele que o
 * defeito apareceu.
 *
 * @return array<string, array<int, string>>
 */
function paletasNomeadasDoFilament(): array
{
    $nomes = [
        'Amber', 'Blue', 'Cyan', 'Emerald', 'Fuchsia', 'Green', 'Indigo', 'Lime', 'Neutral',
        'Orange', 'Pink', 'Purple', 'Red', 'Rose', 'Sky', 'Slate', 'Stone', 'Teal', 'Violet',
        'Yellow', 'Zinc',
    ];

    $paletas = [];

    foreach ($nomes as $nome) {
        /** @var array<int, string> $paleta */
        $paleta         = constant(Color::class.'::'.$nome);
        $paletas[$nome] = $paleta;
    }

    return $paletas;
}

/**
 * O degrau de `--primary-*` que o `kit.css` aplica ao rótulo ativo.
 *
 * Lido do arquivo, e não redigitado: redigitado, o teste concordaria consigo mesmo e não com
 * a tela. O comentário do bloco cita as medições `600/50` e `700/50` para explicar a escolha,
 * e **citar não é aplicar** — por isso os comentários saem antes do `preg_match`
 * (`.ai/rules/testes.md`).
 */
function regrasDoRotuloAtivoNoTopo(): string
{
    return preg_replace(
        '~/\*.*?\*/~s',
        '',
        (string) file_get_contents(resource_path('css/filament/kit.css')),
    ) ?? '';
}

function degrauDoRotuloAtivoNoTopo(): int
{
    $regras = regrasDoRotuloAtivoNoTopo();

    expect($regras)->toMatch('~\.fi-topbar-item\.fi-active\s+\.fi-topbar-item-label\s*\{[^}]*color:\s*var\(--primary-\d{3}\)~');

    preg_match('~\.fi-topbar-item\.fi-active\s+\.fi-topbar-item-label\s*\{[^}]*color:\s*var\(--primary-(\d{3})\)~', $regras, $achado);

    return (int) $achado[1];
}

/**
 * A razão de contraste WCAG entre duas cores do Filament.
 *
 * A conversão de espaço de cor é do próprio Filament: `Color::convertToRgb()` já faz
 * oklch → sRGB. Aqui entra só a linearização da WCAG.
 */
function contrasteWcagNoTopo(string $texto, string $fundo): float
{
    $luminancia = static function (string $cor): float {
        sscanf(Color::convertToRgb($cor), 'rgb(%d, %d, %d)', $vermelho, $verde, $azul);

        [$vermelho, $verde, $azul] = array_map(static function (int $canal): float {
            $s = $canal / 255;

            return $s <= 0.04045 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
        }, [$vermelho, $verde, $azul]);

        return 0.2126 * $vermelho + 0.7152 * $verde + 0.0722 * $azul;
    };

    $a = $luminancia($texto);
    $b = $luminancia($fundo);

    return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
}

it('passa o minimo AA no tema claro com o degrau que o kit.css aplica', function (): void {
    $degrau = degrauDoRotuloAtivoNoTopo();

    // O fundo é o do botão ativo: `bg-gray-50` (topbar.css:73).
    $fundo = Color::Gray[50];

    foreach (paletasNomeadasDoFilament() as $nome => $paleta) {
        // 4,5:1 é o mínimo WCAG 2.1 AA para texto pequeno — o rótulo é 14 px.
        expect(round(contrasteWcagNoTopo($paleta[$degrau], $fundo), 2))
            ->toBeGreaterThanOrEqual(4.5, "{$nome} {$degrau} sobre gray-50 não alcança 4,5:1.");
    }
});

/**
 * O outro lado da medição: sem a regra do kit, o rótulo reprova.
 *
 * Não é enfeite — é o que impede a regra de virar CSS supérfluo que ninguém ousa remover. Se
 * este caso ficar vermelho, o `--primary-600` do Filament passou a bastar e o bloco do
 * `kit.css` pode sair.
 *
 * Medido em 2026-09-17: Amber 3,06 · Yellow 2,81 · Lime 2,93 · Green 3,08 · Cyan 3,46 ·
 * Teal 3,51 · Emerald 3,50 · Orange 3,44 · Sky 3,85 — nove das vinte e uma.
 */
it('reprova no degrau 600 que a folha do Filament aplica', function (): void {
    $fundo = Color::Gray[50];

    $reprovadas = collect(paletasNomeadasDoFilament())
        ->filter(static fn (array $paleta): bool => contrasteWcagNoTopo($paleta[600], $fundo) < 4.5)
        ->keys()
        ->all();

    expect($reprovadas)->not->toBeEmpty(
        'nenhuma paleta reprova no 600 — o Filament corrigiu, e o bloco do kit.css pode sair.',
    )->and($reprovadas)->toContain('Amber');
});

/**
 * O elo que faltaria: o seletor do kit tem de casar com o que a folha do vendor emite.
 *
 * Um upgrade do Filament que renomeie `.fi-topbar-item-label` ou `.fi-active`, ou que troque o
 * fundo do botão ativo, apaga o efeito da regra sem mover teste nenhum — este caso é o que move.
 */
it('mantem as classes e o fundo que o seletor do kit pressupoe', function (): void {
    $folhaDoVendor = (string) file_get_contents(
        base_path('vendor/filament/filament/resources/css/components/topbar.css'),
    );

    expect($folhaDoVendor)
        ->toContain('.fi-topbar-item-label')
        ->toContain('.fi-active')
        // O fundo do botão ativo, que é o outro lado do par medido acima.
        ->toMatch('~&\.fi-active\s*\{[^}]*\.fi-topbar-item-btn\s*\{\s*@apply bg-gray-50~s');
});

/**
 * O degrau do tema ESCURO, que a primeira versão desta correção esqueceu.
 *
 * É a armadilha mais cara desta família, e vale lida inteira: a regra
 * do kit nasceu como `:root .fi-topbar-item.fi-active .fi-topbar-item-label`, que é
 * **(0,4,0)**. A regra escura do Filament é
 * `.fi-topbar-item.fi-active .fi-topbar-item-label:where(.dark,.dark *)` — e
 * **`:where()` contribui ZERO especificidade**, então ela é (0,3,0) e vence a clara
 * só por ordem de origem.
 *
 * Resultado: a regra do kit vencia nos **dois** temas e trocava `--primary-400` por
 * `--primary-700` sobre fundo escuro. O rótulo ativo caía de ~6–10:1 para ~2,3–3,2:1 —
 * **pior do que o defeito que ela existia para corrigir**. E o comentário do bloco
 * afirmava o contrário ("só o tema claro é tocado").
 *
 * Nenhum teste pegava: o caso do tema claro mede só sobre `gray-50`, a auditoria de
 * acessibilidade do `TemaEscuroTest` roda `->inLightMode()` (dívida DT-12) e o caso de
 * navegador do escuro só faz `assertSee`. A suíte inteira verde não cobria esta célula — e é
 * por isso que os dois casos escuros aqui são ESTÁTICOS, lendo o CSS e recalculando contraste.
 *
 * O fundo do botão ativo no escuro é `#ffffff0d` — branco a 5% sobre o painel escuro.
 * A aproximação por `gray-900` é conservadora: o overlay clareia o fundo, o que só
 * ajuda o contraste de um texto claro.
 */
it('o tema escuro tem degrau proprio, e ele passa o minimo AA', function (): void {
    $regras = regrasDoRotuloAtivoNoTopo();

    /*
     * `\.dark:root` LITERAL, e não um `.dark` qualquer antes da cadeia.
     *
     * A primeira versão desta regex aceitava
     * `.dark :root …` e `:root .dark …`, que são **letra morta** — `:root` é o
     * `<html>`, não pode ser descendente de nada nem conter a classe que o
     * Filament escreve nele próprio. Com a regex frouxa, um bloco só com as
     * formas inertes passava verde e o defeito A1 voltava inteiro.
     */
    expect($regras)->toMatch(
        '~\.dark:root\s+\.fi-topbar-item\.fi-active\s+\.fi-topbar-item-label\s*\{[^}]*color:\s*var\(--primary-\d{3}\)~',
        'o bloco do kit precisa declarar o par ESCURO com `.dark:root` — a classe na PRÓPRIA raiz. '
        .'Sem ele a regra clara vence nos dois temas, porque o `:where(.dark, .dark *)` do Filament '
        .'não soma especificidade; e com `.dark :root` ou `:root .dark` o seletor nunca casa.',
    );

    preg_match(
        '~\.dark:root\s+\.fi-topbar-item\.fi-active\s+\.fi-topbar-item-label\s*\{[^}]*color:\s*var\(--primary-(\d{3})\)~',
        $regras,
        $achado,
    );

    $degrau = (int) $achado[1];
    $fundo  = Color::Gray[900];

    foreach (paletasNomeadasDoFilament() as $nome => $paleta) {
        expect(round(contrasteWcagNoTopo($paleta[$degrau], $fundo), 2))
            ->toBeGreaterThanOrEqual(4.5, "{$nome} {$degrau} sobre gray-900 não alcança 4,5:1.");
    }
});

/**
 * O degrau CLARO, aplicado ao escuro, reprova.
 *
 * O par do caso anterior, e a prova de que a separação entre os dois temas não é decorativa:
 * se este caso ficar verde, o mesmo degrau serve aos dois e o bloco pode simplificar.
 */
it('o degrau claro sobre fundo escuro reprova', function (): void {
    $claro = degrauDoRotuloAtivoNoTopo();
    $fundo = Color::Gray[900];

    $reprovadas = collect(paletasNomeadasDoFilament())
        ->filter(static fn (array $paleta): bool => contrasteWcagNoTopo($paleta[$claro], $fundo) < 4.5)
        ->keys()
        ->all();

    expect($reprovadas)->not->toBeEmpty(
        "o degrau claro ({$claro}) passaria no escuro — o bloco do kit.css pode usar um só.",
    );
});
