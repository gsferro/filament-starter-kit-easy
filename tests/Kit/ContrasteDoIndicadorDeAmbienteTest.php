<?php

/**
 * DT-02 — o badge de ambiente tem de passar o mínimo WCAG AA no tema claro.
 *
 * A correção é UMA linha de CSS em `resources/css/filament/kit.css`, e uma linha de CSS
 * não tem teste óbvio: `assertSee` passa com texto branco em fundo branco, e o axe-core só
 * existe dentro do navegador — onde a suíte roda em série, num job separado, e o lote
 * aborta na primeira falha (ver DT-01).
 *
 * O que este arquivo faz em vez disso é fechar a corrente em três elos, todos verificáveis
 * em PHP:
 *
 *   1. o `kit.css` é a fonte do degrau — o teste LÊ o número de lá, não o redigita;
 *   2. o contraste é recalculado a partir das paletas do próprio Filament, para as quatro
 *      cores que o plugin pode escolher;
 *   3. a blade do vendor ainda emite as classes que o seletor do kit usa — sem isto a
 *      regra viraria CSS morto sem nada acusar.
 *
 * Quebra se alguém baixar o degrau, apagar a regra, ou se um upgrade do plugin renomear a
 * classe do badge.
 */

use Filament\Support\Colors\Color;

/**
 * As quatro cores de `EnvironmentIndicatorPlugin::make()`
 * (`vendor/pxlrbt/filament-environment-indicator/src/EnvironmentIndicatorPlugin.php:54-59`).
 *
 * `Pink` é o default, e é o caso que a sonda de acessibilidade pegou: o ambiente de
 * desenvolvimento não é `production`, `staging` nem `development`.
 */
function paletasDoIndicadorDeAmbiente(): array
{
    return ['Pink' => Color::Pink, 'Orange' => Color::Orange, 'Blue' => Color::Blue, 'Red' => Color::Red];
}

/**
 * A razão de contraste WCAG entre duas cores do Filament, na forma `N:1`.
 *
 * A conversão de espaço de cor é do próprio Filament: `Color::convertToRgb()`
 * (`vendor/filament/support/src/Colors/Color.php:467-521`) já faz oklch → sRGB. Aqui entra
 * só o que ele não tem — a linearização da WCAG.
 */
function contrasteWcag(string $texto, string $fundo): float
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

/**
 * O par exato que o tema pinta: `.fi-badge.fi-color` põe o fundo em `--color-50` e o texto
 * em `--text` — que é a variável que a regra do kit sobrescreve.
 *
 * O degrau é lido do CSS, não redigitado: redigitado, o teste concordaria consigo mesmo e
 * não com a tela. Só as REGRAS contam — o cabeçalho do bloco cita as medições `600/50` e
 * `700/50` para explicar a escolha, e citar não é aplicar (ver `.ai/rules/testes.md`).
 */
it('passa o mínimo AA no tema claro com o degrau que o kit.css aplica', function (): void {
    $regras = preg_replace(
        '~/\*.*?\*/~s',
        '',
        (string) file_get_contents(resource_path('css/filament/kit.css')),
    ) ?? '';

    expect($regras)->toMatch('~\.environment-indicator\.fi-badge\s*\{[^}]*--text:\s*var\(--color-\d{3}\)~');

    preg_match('~\.environment-indicator\.fi-badge\s*\{[^}]*--text:\s*var\(--color-(\d{3})\)~', $regras, $achado);

    $degrau = (int) $achado[1];

    foreach (paletasDoIndicadorDeAmbiente() as $nome => $paleta) {
        // 4,5:1 é o mínimo WCAG 2.1 AA para texto pequeno — o badge é 12 px.
        expect(round(contrasteWcag($paleta[$degrau], $paleta[50]), 2))
            ->toBeGreaterThanOrEqual(4.5, "{$nome} {$degrau} sobre 50 não alcança 4,5:1.");
    }
});

/**
 * O outro lado da medição: sem a regra do kit, o badge reprova.
 *
 * Não é enfeite — é o que impede a regra de virar CSS supérfluo que ninguém ousa remover.
 * Se este caso ficar vermelho, o `--color-600` do Filament passou a bastar e o bloco do
 * `kit.css` pode sair.
 *
 * Medido: Pink 4,16:1, Orange 3,39:1, Red 4,36:1 — e Blue 4,82:1, que já passava.
 */
it('reprova no degrau 600 que a blade do vendor pede', function (): void {
    $reprovadas = collect(paletasDoIndicadorDeAmbiente())
        ->filter(fn (array $paleta): bool => contrasteWcag($paleta[600], $paleta[50]) < 4.5)
        ->keys()
        ->all();

    expect($reprovadas)->toBe(['Pink', 'Orange', 'Red']);
});

/**
 * O elo que faltaria: o seletor do kit tem de casar com o HTML que o vendor emite.
 *
 * `.environment-indicator.fi-badge` são duas classes que a blade do pacote escreve juntas
 * (`badge.blade.php:4-11`). Um upgrade que renomeie qualquer uma delas apaga o efeito da
 * regra sem mover teste nenhum — este caso é o que move.
 */
it('mantém as classes que o seletor do kit usa na blade do vendor', function (): void {
    $html = view('filament-environment-indicator::badge', [
        'color'       => Color::Pink,
        'environment' => 'Local',
        'branch'      => null,
    ])->render();

    expect($html)
        ->toContain('environment-indicator')
        ->toContain('fi-badge')
        ->toContain('fi-color')
        // O lado escuro usa outra variável (`--dark-text`), e é por isso que a regra do
        // kit toca só `--text`.
        ->toContain('dark:fi-text-color-400');
});
