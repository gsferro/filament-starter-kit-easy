<?php

/**
 * A guarda do `resources/css/filament/voltar-ao-topo.css`.
 *
 * O botão "Voltar ao topo" é blade do KIT renderizada por render hook `BODY_END` em toda
 * tela de painel — e o painel não carrega o Vite da aplicação, então as utilitárias que a
 * blade emite precisam de regra nesta folha, escopadas no `data-voltar-ao-topo` da raiz.
 * Sem elas o botão sai sem `fixed`, posição e cor: um `<button>` cru no fim do `<body>`.
 *
 * O desenho segue `tests/Kit/CardsCssTest.php`: lê o HTML RENDERIZADO (a `/`, pública, já
 * traz o botão) em vez de uma lista congelada — se a blade ganhar classe nova, o caso apita.
 */
const ESCOPO_DO_VOLTAR_AO_TOPO = '[data-voltar-ao-topo]';

/**
 * As classes do botão e do ícone dentro dele, lidas do HTML renderizado.
 *
 * Regex sobre o fonte, e não `DOMDocument`: os atributos Alpine da blade
 * (`@scroll.window.passive="…scrollY > 400"`, `@click="window.scrollTo({…})"`) levam `>`
 * DENTRO do valor, e o parser encerra a tag ali — o `class` vira texto e some. O recorte
 * `data-voltar-ao-topo` → `</button>` isola o botão e o ícone sem depender de tag bem
 * formada.
 *
 * @return list<string>
 */
function classesDoBotaoVoltarAoTopo(string $html): array
{
    $inicio = strpos($html, 'data-voltar-ao-topo');
    $fim    = $inicio === false ? false : strpos($html, '</button>', $inicio);

    if ($inicio === false || $fim === false) {
        return [];
    }

    preg_match_all('/class="([^"]+)"/', substr($html, $inicio, $fim - $inicio), $achados);

    $classes = [];

    foreach ($achados[1] as $lista) {
        foreach (preg_split('/\s+/', trim($lista)) ?: [] as $classe) {
            if ($classe !== '') {
                $classes[$classe] = true;
            }
        }
    }

    return array_keys($classes);
}

/** O CSS sem os blocos de comentário — para asserção de AUSÊNCIA (`.ai/rules/testes.md`). */
function cssDoVoltarAoTopoSemComentario(): string
{
    return (string) preg_replace(
        '~/\*.*?\*/~s',
        '',
        (string) file_get_contents(base_path('resources/css/filament/voltar-ao-topo.css')),
    );
}

/**
 * A classe tem regra sob `[data-voltar-ao-topo]`?
 *
 * A raiz é o próprio elemento estilizado, então a forma é COMPOSTA
 * (`[data-voltar-ao-topo].fixed`) — e o ícone, descendente (`[data-voltar-ao-topo] .h-5`).
 * A classe é procurada já ESCAPADA como o arquivo a escreve (`hover\:bg-primary-700`).
 */
function classeCobertaNoCssDoVoltarAoTopo(string $classe, string $css): bool
{
    $escapada = preg_quote(addcslashes($classe, ':/[].'), '~');
    $escopo   = preg_quote(ESCOPO_DO_VOLTAR_AO_TOPO, '~');

    return preg_match('~'.$escopo.'\s*\.'.$escapada.'(?![\w-])~', $css) === 1
        || preg_match('~'.$escopo.'\s+\.'.$escapada.'(?![\w-])~', $css) === 1;
}

/**
 * A classe existe na CSS pré-compilada do Filament? É onde moram as `fi-*` — o ícone do
 * botão é `<x-filament::icon>`, que emite `fi-icon fi-size-md`, já com estilo lá.
 */
function classeCobertaPeloFilamentNoVoltarAoTopo(string $classe, string $appCss): bool
{
    $escapada = preg_quote(addcslashes($classe, ':/[].'), '~');

    return preg_match('~\.'.$escapada.'(?![\w-])~', $appCss) === 1;
}

/**
 * CT-01 — nenhuma classe do botão fica sem regra sob o escopo.
 *
 * O piso de 15 é controle positivo do detector: um recorte quebrado devolve lista vazia, e
 * "toda classe coberta" sobre conjunto vazio é verdadeiro. Medido: ~20 entre o botão e o
 * ícone. Fica abaixo para a blade poder REMOVER classes sem reprovar — é acrescentar que
 * quebra o kit.
 */
it('declara sob o escopo toda classe que o botao voltar ao topo emite', function (): void {
    $html    = (string) $this->get('/')->assertOk()->getContent();
    $classes = classesDoBotaoVoltarAoTopo($html);
    $css     = cssDoVoltarAoTopoSemComentario();
    $appCss  = (string) file_get_contents(public_path('css/filament/filament/app.css'));

    $semDeclaracao = array_values(array_filter(
        $classes,
        static fn (string $classe): bool => ! classeCobertaNoCssDoVoltarAoTopo($classe, $css)
            && ! classeCobertaPeloFilamentNoVoltarAoTopo($classe, $appCss),
    ));

    expect(count($classes))->toBeGreaterThanOrEqual(15)
        ->and($semDeclaracao)->toBe([], 'Classe do botão Voltar ao topo sem regra em voltar-ao-topo.css');
});

/**
 * CT-02 — os dois literais de `bottom-*` estão cobertos, e nenhuma regra escapa do escopo.
 *
 * A blade escolhe `bottom-24` ou `bottom-6` pelo painel (`$recuoDoBotao`) — só um dos dois
 * aparece no HTML de cada vez, então os dois se afirmam no ARQUIVO, não no render. E toda
 * regra começa pelo atributo: um `.fixed` global mudaria toda blade de vendor que hoje
 * emite `fixed` sem estilo.
 */
it('cobre as duas variantes de recuo e mantem toda regra sob o escopo', function (): void {
    $css = cssDoVoltarAoTopoSemComentario();

    expect($css)
        ->toContain('[data-voltar-ao-topo].bottom-24')
        ->toContain('[data-voltar-ao-topo].bottom-6');

    preg_match_all('/([^{}]+)\{/', $css, $regras);

    $foraDoEscopo = [];

    foreach ($regras[1] as $lista) {
        foreach (explode(',', $lista) as $seletor) {
            $seletor = trim($seletor);

            if ($seletor === '' || str_starts_with($seletor, '@')) {
                continue;
            }

            if (! str_starts_with($seletor, ESCOPO_DO_VOLTAR_AO_TOPO)) {
                $foraDoEscopo[] = $seletor;
            }
        }
    }

    expect($foraDoEscopo)->toBe([], 'Seletor fora do escopo [data-voltar-ao-topo]: atropela outros plugins');
});
