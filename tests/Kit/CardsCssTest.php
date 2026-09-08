<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * A guarda do `resources/css/filament/cards.css` — a dívida que `.ai/rules/css-filament.md`
 * registrava e que o upgrade `harvirsidhu/filament-cards` 1.0.9 → 1.1.0 cobrou: a blade do
 * pacote trocou a grade interpolada (`lg:grid-cols-3`, que nunca teve CSS) pelo macro
 * `grid()` do Filament e passou a emitir utilitárias novas (`size-10`, `border-s-color-500`,
 * `hover:shadow-md`…), e nada apitou até o CT-05 de `BoasVindasTest` falhar no CI.
 *
 * O desenho segue `tests/Kit/SpotlightCssTest.php`, com UMA diferença: em vez de ler a blade
 * do vendor, os casos leem o HTML RENDERIZADO dentro de `.fi-cards-page`. A blade emite
 * classes condicionais de recursos que o kit não usa (`compact()`, `collapsible()`,
 * `actions()`, alinhamentos…) e uma lista vinda dela exigiria uma allowlist de exceções que
 * envelheceria em silêncio; o HTML renderizado só contém o que a configuração do kit emite
 * de fato — e se alguém ligar um recurso novo, as classes dele aparecem aqui e o caso apita.
 *
 * São duas páginas porque o markup muda: `/` (BoasVindas, pública, cartões com `->color()`)
 * e o hub de /admin (o único com `$searchable`, que é onde vivem o campo de busca, o botão
 * de limpar e o empty state).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * As classes de TODOS os elementos dentro de `.fi-cards-page` no HTML renderizado.
 *
 * `DOMDocument` e não regex sobre o fonte: o que interessa é a árvore que o navegador
 * recebe, e só a ancestralidade separa o markup dos cartões do restante da página (botão
 * "Voltar ao topo", overlay do Spotlight, widget do chat — todos fora do escopo).
 *
 * @return list<string>
 */
function classesDoMarkupDosCartoes(string $html): array
{
    $dom = new DOMDocument;

    $interno = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_use_internal_errors($interno);

    $xpath  = new DOMXPath($dom);
    $raizes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' fi-cards-page ')]");

    $classes = [];

    if ($raizes !== false) {
        foreach ($raizes as $raiz) {
            $descendentes = $xpath->query('.//*[@class]', $raiz);

            if ($descendentes === false) {
                continue;
            }

            foreach ($descendentes as $elemento) {
                foreach (preg_split('/\s+/', trim($elemento->getAttribute('class'))) ?: [] as $classe) {
                    if ($classe !== '') {
                        $classes[$classe] = true;
                    }
                }
            }
        }
    }

    return array_keys($classes);
}

/** O CSS sem os blocos de comentário — para asserção de AUSÊNCIA (`.ai/rules/testes.md`). */
function cssDosCartoesSemComentario(): string
{
    return (string) preg_replace(
        '~/\*.*?\*/~s',
        '',
        (string) file_get_contents(base_path('resources/css/filament/cards.css')),
    );
}

/**
 * A classe tem regra sob `.kit-cards-page` no cards.css?
 *
 * A classe é procurada já ESCAPADA como o arquivo a escreve (`dark\:text-gray-200`,
 * `ring-gray-950\/5`, `sm\:w-72`), num seletor que COMEÇA no escopo — composta ou
 * descendente, inclusive com o `.dark` de tema e com `.group:hover` no meio do caminho
 * (`group-hover:*`). É isso que distingue "a regra existe" de "a regra existe com o nome
 * errado" e "no escopo" de "global".
 */
function classeCobertaNoCssDosCartoes(string $classe, string $css): bool
{
    $escapada = preg_quote(addcslashes($classe, ':/[].'), '~');

    return preg_match(
        '~(?:^|,)\s*(?:\.dark\s+)?\.kit-cards-page\s[^{,]*\.'.$escapada.'(?![\w-])~m',
        $css,
    ) === 1;
}

/**
 * A classe existe na CSS pré-compilada do Filament?
 *
 * É onde moram as `fi-*` e, desde o 1.1.0, a grade (`fi-grid`, `lg\:fi-grid-cols`) e o
 * pipeline de cor (`fi-color-*`, `fi-text-color-*`). O lookahead impede que `.fi-grid`
 * case dentro de `.fi-grid-col`.
 */
function classeCobertaPeloFilament(string $classe, string $appCss): bool
{
    $escapada = preg_quote(addcslashes($classe, ':/[].'), '~');

    return preg_match('~\.'.$escapada.'(?![\w-])~', $appCss) === 1;
}

/**
 * CT-01 — nenhuma classe do markup dos cartões fica sem estilo.
 *
 * Coberta vale: regra escopada no `cards.css` do kit OU classe compilada na CSS do
 * Filament. Fora disso só entram os ganchos semânticos `fi-cards-*` (são identificadores
 * da blade, não utilitárias — o pacote não registra CSS para eles) e `group`, o marcador
 * que os `group-hover:*` consultam e que não carrega estilo nenhum.
 *
 * O piso de 40 é controle positivo do detector: um XPath quebrado devolve lista vazia, e
 * "toda classe coberta" sobre conjunto vazio é verdadeiro. Medido no 1.1.0: ~80 entre as
 * duas páginas. Fica abaixo para o pacote poder REMOVER classes sem reprovar — é
 * acrescentar que quebra o kit.
 */
it('declara estilo para toda classe que o markup dos cartoes emite', function (string $rota, bool $hub): void {
    config(['kit.hub' => $hub]);

    if ($hub) {
        $this->actingAs(usuarioDoKit('master_global'));
    }

    $html    = (string) $this->get($rota)->assertOk()->getContent();
    $classes = classesDoMarkupDosCartoes($html);
    $css     = cssDosCartoesSemComentario();
    $appCss  = (string) file_get_contents(public_path('css/filament/filament/app.css'));

    $semEstilo = array_values(array_filter(
        $classes,
        static fn (string $classe): bool => ! str_starts_with($classe, 'fi-cards-')
            && $classe !== 'group'
            && ! classeCobertaNoCssDosCartoes($classe, $css)
            && ! classeCobertaPeloFilament($classe, $appCss),
    ));

    expect(count($classes))->toBeGreaterThanOrEqual(40)
        ->and($semEstilo)->toBe([], 'Classe do markup dos cartões sem regra em cards.css nem na CSS do Filament (upgrade do pacote?)');
})->with([
    'boas-vindas (pública, cartões com cor)' => ['/', false],
    'hub de admin (com busca)'               => ['/admin/hub-de-administracao', true],
]);

/**
 * CT-02 — nenhuma regra do cards.css escapa do escopo.
 *
 * Toda regra começa por `.kit-cards-page` (ou por `.dark` seguido dele): um
 * `.flex { display: flex }` global mudaria toda blade de vendor que hoje emite `flex`
 * sem estilo. Os blocos `@media` são pulados — o que eles embrulham é conferido pelo
 * seletor interno, que o regex lê como regra própria.
 */
it('mantem toda regra do cards.css sob o escopo dos cartoes', function (): void {
    preg_match_all('/([^{}]+)\{/', cssDosCartoesSemComentario(), $regras);

    $foraDoEscopo = [];

    foreach ($regras[1] as $lista) {
        foreach (explode(',', $lista) as $seletor) {
            $seletor = trim($seletor);

            if ($seletor === '' || str_starts_with($seletor, '@')) {
                continue;
            }

            if (! str_starts_with($seletor, '.kit-cards-page') && ! str_starts_with($seletor, '.dark .kit-cards-page')) {
                $foraDoEscopo[] = $seletor;
            }
        }
    }

    expect($foraDoEscopo)->toBe([], 'Seletor fora do escopo .kit-cards-page: atropela outros plugins');
});
