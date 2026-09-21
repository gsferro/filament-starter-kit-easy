<?php

/**
 * A ordem das cascade layers da página, que o kit fixa no `STYLES_BEFORE`.
 *
 * O modo de falhar desta família é o mais silencioso que o kit já teve: o HTML sai correto, TODAS
 * as folhas respondem 200, o console fica limpo, e o botão aparece sem padding, sem fundo e sem
 * borda. Nada acusa — nem status, nem console, nem asserção de conteúdo.
 *
 * A causa está no docblock de `KitServiceProvider::configureOrdemDasCascadeLayers()`. Em uma
 * frase: o `@filamentStyles` emite as folhas dos plugins antes da `app.css` do Filament, e a folha
 * do `croustibat/filament-jobs-monitor` está inteira em `@layer components{…}`. Sendo a primeira a
 * declarar layer, é ela quem fixa a ordem da página — e `base` passa a cair depois de `components`,
 * fazendo o preflight do `button` derrotar o `.fi-btn`.
 *
 * Por isso o `[CT-02]` existe: sem ele, este arquivo ficaria verde para sempre no dia em que os
 * plugins parassem de declarar layer — afirmando que a correção funciona num mundo onde ela não
 * faz mais nada.
 */
const ORDEM_ESPERADA = '@layer properties, theme, base, components, utilities;';

/**
 * As folhas de CSS publicadas que o navegador recebe, por caminho.
 *
 * Lidas do disco em runtime, e não de uma lista congelada: é o que faz o `[CT-02]` reagir a um
 * `composer update` que troque o comportamento de um pacote.
 *
 * @return list<string>
 */
function folhasPublicadasDoKit(): array
{
    $achadas = glob(public_path('css/*/*/*.css')) ?: [];

    return array_values(array_merge($achadas, glob(public_path('css/*/*.css')) ?: []));
}

it('[CT-01] declara a ordem das layers antes da primeira folha, em todos os paineis', function (string $rota): void {
    $html = $this->get($rota)->assertOk()->getContent();

    $posicaoDaDeclaracao = strpos($html, '@layer');

    /*
     * A folha é procurada por regex, e não por `<link rel="stylesheet"` literal.
     *
     * O Filament emite o elemento em várias linhas e com o `href` ANTES do `rel`
     * (`vendor/filament/support/src/Assets/Css.php:48-52`), então a busca literal não acha nada e o
     * caso passaria por não encontrar folha alguma — verde por cegueira, que é o modo de falha que
     * este arquivo inteiro existe para não repetir.
     */
    $achouFolha = preg_match('~<link[^>]*rel="stylesheet"~i', $html, $achado, PREG_OFFSET_CAPTURE);

    $posicaoDaPrimeiraFolha = $achouFolha === 1 ? $achado[0][1] : false;

    expect($posicaoDaDeclaracao)->not->toBeFalse('a pagina nao declara @layer em lugar nenhum');

    /*
     * A PRIMEIRA ocorrência precisa ser a nossa. Uma declaração correta emitida depois da folha do
     * plugin não corrige nada — o navegador já fixou a ordem na primeira que leu.
     */
    expect(substr($html, $posicaoDaDeclaracao, strlen(ORDEM_ESPERADA)))
        ->toBe(ORDEM_ESPERADA, 'a primeira declaracao de @layer da pagina nao e a do kit');

    expect($posicaoDaPrimeiraFolha)->not->toBeFalse('a pagina nao carrega folha nenhuma');
    expect($posicaoDaDeclaracao)->toBeLessThan(
        $posicaoDaPrimeiraFolha,
        'a ordem das layers e declarada DEPOIS da primeira folha, entao nao vale',
    );
})->with(['/admin/login', '/app/login', '/infra/login']);

it('[CT-02] alguma folha de plugin ainda declara layer, senao a correcao virou inocua', function (): void {
    $folhas = folhasPublicadasDoKit();

    expect($folhas)->not->toBeEmpty('nenhuma folha publicada foi encontrada — rode php artisan filament:assets');

    $declaram = [];

    foreach ($folhas as $folha) {
        // A `app.css` do proprio Filament declara layer por desenho; ela nao e o risco.
        if (str_contains(str_replace('\\', '/', $folha), 'css/filament/filament/')) {
            continue;
        }

        if (str_contains((string) file_get_contents($folha), '@layer')) {
            $declaram[] = basename($folha);
        }
    }

    /*
     * Controle positivo do detector, e a razão de ele não ser uma asserção de ausência.
     *
     * Hoje quem declara é o `filament-jobs-monitor` e o `dotswan/filament-laravel-pulse`. Se um dia
     * nenhum declarar, o `[CT-01]` continuaria verde — mas estaria provando que uma correção
     * inócua "funciona". Este caso fica VERMELHO nessa hora, pedindo reavaliação, em vez de deixar
     * o kit carregando para sempre uma correção que ninguém sabe mais por que existe.
     */
    expect($declaram)->not->toBeEmpty(
        'nenhuma folha de plugin declara mais @layer: a correcao de ordem das layers pode ter virado '
        .'inocua e merece reavaliacao (ver KitServiceProvider::configureOrdemDasCascadeLayers)',
    );
});
