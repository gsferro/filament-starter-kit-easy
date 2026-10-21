<?php

/*
 * O dock na altura da área visível, mesmo quando o navegador reporta `innerHeight` maior que ela.
 *
 * Medido no Chrome do solicitante (2026-10-20): `innerHeight` 867 = `outerHeight`, com a área
 * visível (`documentElement.clientHeight` = `visualViewport.height`) de 780. O pacote calcula a
 * altura pelo `innerHeight` e, ao reposicionar, levava o `bottom-center` para y = 867 − 56 − 24 =
 * 787 — fora da tela. Os casos simulam o mesmo navegador sobrepondo `window.innerHeight` e
 * disparando o `resize`, que é um dos gatilhos do reposicionamento do pacote. Wiki:
 * `wikis/specs/fix/dock-altura-do-viewport/`.
 */

/** @return array{top: float, bottom: float, alturaVisivel: int, innerHeight: int} */
function medirDockNaAlturaDoViewport(object $pagina, int $excesso): array
{
    // A sentinela no `--qad-transform` prova que o pacote REPOSICIONOU depois do resize: a medida
    // só é lida quando o `place()` a sobrescreveu, em vez de esperar um tempo fixo.
    $pagina->script(<<<JS
        (() => {
            const real = document.documentElement.clientHeight;
            Object.defineProperty(window, 'innerHeight', {configurable: true, get: () => real + {$excesso}});
            document.querySelector('.qad-root').style.setProperty('--qad-transform', 'sentinela');
            window.dispatchEvent(new Event('resize'));
        })()
    JS);

    $pagina->assertScript('() => document.querySelector(".qad-root").style.getPropertyValue("--qad-transform") !== "sentinela"');

    return json_decode((string) $pagina->script(<<<'JS'
        JSON.stringify((() => {
            const b = document.querySelector('.qad-root .qad-trigger').getBoundingClientRect();
            return {top: b.top, bottom: b.bottom, alturaVisivel: document.documentElement.clientHeight, innerHeight: window.innerHeight};
        })())
    JS), true, flags: JSON_THROW_ON_ERROR);
}

it('[CT-B01] com innerHeight maior que a área visível, o dock reposicionado fica a 24 px da borda inferior visível', function (string $painel): void {
    $pagina = abrirPainelDaPilha($painel, chatEm: []);

    $medida = medirDockNaAlturaDoViewport($pagina, 87);

    expect($medida['innerHeight'])->toBe($medida['alturaVisivel'] + 87)
        ->and($medida['bottom'])->toBeLessThanOrEqual($medida['alturaVisivel'])
        ->and(abs($medida['alturaVisivel'] - $medida['bottom'] - 24))->toBeLessThanOrEqual(1);
})->with(['/admin', '/app']);

it('[CT-B02] com innerHeight igual à área visível, a posição não muda', function (): void {
    $pagina = abrirPainelDaPilha('/admin', chatEm: []);

    $medida = medirDockNaAlturaDoViewport($pagina, 0);

    expect(abs($medida['alturaVisivel'] - $medida['bottom'] - 24))->toBeLessThanOrEqual(1);
});
