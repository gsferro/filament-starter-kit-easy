<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O CSS que o kit escreve para o overlay da busca ⌘K (`wezlo/filament-search-spotlight`).
 *
 * IDs de CT em `wikis/specs/fix/spotlight-sem-estilo/spotlight-sem-estilo/04-casos-de-teste.md`.
 *
 * O modo de falhar desta família é silencioso (`.ai/rules/css-filament.md`): a blade do pacote
 * emite utilitárias Tailwind, a CSS do Filament não as tem, e o HTML sai byte a byte correto
 * sem estilo. Estes casos lêem a blade do VENDOR em runtime — não uma lista congelada —, então
 * são eles que ficam vermelhos num `composer update` do pacote, antes de alguém abrir a tela.
 * A geometria do overlay aberto é assunto do F-45 em `tests/Browser/RoteiroDoKitTest.php`.
 */
const ESCOPO_DO_SPOTLIGHT = '[x-on\\:open-spotlight\\.window]';

/**
 * As classes que as blades do pacote emitem em `class="…"` estáticos.
 *
 * @return list<string>
 */
function classesDaBladeDoSpotlight(): array
{
    $classes = [];

    foreach (glob(base_path('vendor/wezlo/filament-search-spotlight/resources/views/{livewire,partials}/*.blade.php'), GLOB_BRACE) ?: [] as $blade) {
        preg_match_all('/class="([^"{]*)"/', (string) file_get_contents($blade), $achados);

        foreach ($achados[1] as $lista) {
            foreach (preg_split('/\s+/', trim($lista)) ?: [] as $classe) {
                if ($classe !== '') {
                    $classes[$classe] = true;
                }
            }
        }
    }

    return array_keys($classes);
}

function cssDoSpotlight(): string
{
    return (string) file_get_contents(base_path('resources/css/filament/spotlight.css'));
}

/** O CSS sem os blocos de comentário — para asserção de AUSÊNCIA (`.ai/rules/testes.md`). */
function cssDoSpotlightSemComentario(): string
{
    return (string) preg_replace('~/\*.*?\*/~s', '', cssDoSpotlight());
}

/**
 * CT-01 — nenhuma classe da blade do vendor fica sem declaração no CSS do kit, sob o escopo.
 *
 * A classe é procurada já ESCAPADA (`dark\:bg-gray-900`, `bg-gray-900\/70`, `max-h-\[60vh\]`,
 * `px-1\.5`), imediatamente após o seletor de escopo — composta ou descendente. É isso que
 * distingue "a regra existe" de "a regra existe com o nome errado" (M8), e "no escopo" de
 * "global" (M9 fica para CT-02).
 *
 * O piso de 60 é controle positivo do detector: um regex quebrado devolve lista vazia, e "toda
 * classe está declarada" sobre conjunto vazio é verdadeiro. Medido em 2026-09-02: 66. Fica
 * abaixo para o pacote poder REMOVER classes sem reprovar — é acrescentar que quebra o kit.
 */
it('[CT-01] declara no css do kit toda classe que a blade do vendor emite, sob o escopo', function (): void {
    $classes = classesDaBladeDoSpotlight();
    $css     = cssDoSpotlightSemComentario();

    $semDeclaracao = array_values(array_filter($classes, function (string $classe) use ($css): bool {
        $escapada = preg_quote(ESCOPO_DO_SPOTLIGHT, '~').'\s?\.'.preg_quote(addcslashes($classe, ':/[].'), '~');

        return preg_match('~'.$escapada.'(?![\w-])~', $css) !== 1;
    }));

    expect(count($classes))->toBeGreaterThanOrEqual(60)
        ->and($semDeclaracao)->toBe([], 'Classes da blade do vendor sem regra em spotlight.css (upgrade do pacote?)');
});

/**
 * CT-02 — o âncora de escopo é contrato dos dois lados, e nada no CSS escapa dele.
 *
 * O atributo da raiz do componente é o evento que o gatilho do kit dispara: se o pacote o
 * renomear, o gatilho quebra E o CSS perde o escopo — os dois de uma vez (M11). E toda regra do
 * arquivo começa pelo escopo (ou por `.dark` seguido dele): um `.flex { display: flex }` global
 * passaria por CT-01 e mudaria toda blade de vendor que hoje emite `flex` sem estilo (M9).
 */
it('[CT-02] o atributo de escopo existe na blade do vendor, o gatilho do kit dispara o evento, e nenhuma regra fica fora do escopo', function (): void {
    $bladeDoPacote = (string) file_get_contents(base_path('vendor/wezlo/filament-search-spotlight/resources/views/livewire/spotlight.blade.php'));
    $gatilhoDoKit  = (string) file_get_contents(resource_path('views/filament/spotlight-trigger.blade.php'));

    expect($bladeDoPacote)->toContain('x-on:open-spotlight.window')
        ->and($gatilhoDoKit)->toContain("new CustomEvent('open-spotlight')");

    // Cada seletor (o que precede `{`), fora de comentário. Vírgula separa seletores da mesma regra.
    preg_match_all('/([^{}]+)\{/', cssDoSpotlightSemComentario(), $regras);

    $foraDoEscopo = [];

    foreach ($regras[1] as $lista) {
        foreach (explode(',', $lista) as $seletor) {
            $seletor = trim($seletor);

            if ($seletor !== '' && ! str_starts_with($seletor, ESCOPO_DO_SPOTLIGHT) && ! str_starts_with($seletor, '.dark '.ESCOPO_DO_SPOTLIGHT)) {
                $foraDoEscopo[] = $seletor;
            }
        }
    }

    expect($foraDoEscopo)->toBe([], 'Seletor fora do escopo do Spotlight: atropela outros plugins');
});

/**
 * CT-03 — cada painel carrega a folha do Spotlight, e as duas anteriores continuam.
 *
 * `FilamentAsset::register()` sem escopo de painel vale para os três — mas a regra é do
 * requisito ("em nenhuma das instalações"), então se afirma por painel, não se infere do
 * mecanismo (M12). O terceiro `Então` é regressão: acrescentar o asset não pode substituir o
 * array e derrubar `kit-cards`/`kit-correcoes` (M13) — `BoasVindasTest` CT-05 já assere um deles.
 */
it('[CT-03] serve a folha do spotlight em cada painel, sem perder as anteriores', function (string $painel): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('master_global'))
        ->get($painel)
        ->assertOk()
        ->assertSee('kit-spotlight.css')
        ->assertSee('kit-cards.css')
        ->assertSee('kit-correcoes.css');
})->with(['/admin', '/app', '/infra']);
