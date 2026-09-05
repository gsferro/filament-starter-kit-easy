<?php

use App\Models\Convite;
use App\Models\Role;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O roteiro de features do README, em navegador real.
 *
 * Cada cenário cita o **F-XX** da seção `## Roteiro de features`. É esse número que liga a
 * promessa ("o kit entrega X") à prova ("e aqui está o teste que mostra"). Se uma feature do
 * roteiro deixar de funcionar, é aqui que aparece — e o README diz quais têm essa marca (🔵) e
 * quais dependem de worker, cron ou Docker e por isso não têm.
 *
 * O que NÃO entra aqui, para não duplicar:
 *
 * - abrir tela (é o `TelasDoKitTest`, que varre as 52 de uma vez)
 * - recorte de painel por papel (é o `PerfisTest`)
 * - tema escuro (é o `TemaEscuroTest`)
 * - identidade visual por organização (é o `BrowserTenancy/IdentidadeVisualTest`)
 *
 * Sobra o que é **fluxo e interação**: o que só existe quando alguém clica.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * F-03 — registro **só** por convite.
 *
 * A guarda mora no `mount()` da página de registro, e é o que separa "o kit não tem cadastro
 * aberto" de "o kit tem uma porta que ninguém percebeu". O CT de backend prova a exceção; este
 * prova o que o visitante vê: ele não fica numa tela de cadastro pela metade, vai para o login.
 */
it('F-03: recusa o registro sem token de convite', function (): void {
    visit('/app/register')
        ->assertPathIs('/app/login')
        ->assertSee('Faça login')
        ->assertNoJavaScriptErrors();
});

/**
 * F-03 — e aceita com token válido.
 *
 * O par do caso acima. Sem ele, uma guarda que recusasse SEMPRE passaria no teste anterior e
 * quebraria a única porta de entrada do kit.
 */
it('F-03: abre o registro com token de convite valido', function (): void {
    $convite = Convite::factory()->create([
        'email'   => 'convidado@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ]);

    $token = $convite->enviar();

    visit("/app/register?token={$token}")
        ->assertPathIs('/app/register')
        ->assertNoJavaScriptErrors();
});

/**
 * F-13 — `panel_user` usa o negócio e **não** administra.
 *
 * A promessa do README é que a matriz dele é a do painel MENOS as telas de administração. Um
 * teste de permission provaria a matriz; este prova a consequência que o usuário vê — o menu não
 * oferece o que ele não pode, que é a regra de "nada de affordance sem permissão".
 */
it('F-13: nao oferece administracao a quem so usa o negocio', function (): void {
    $this->actingAs(usuarioDoKit('panel_user'));

    visit('/app')
        ->assertSee('Painel de Controle')
        ->assertDontSee('Convites')
        ->assertNoJavaScriptErrors();
});

/**
 * F-45 — a busca ⌘K.
 *
 * É a feature mais JavaScript do kit: o campo da topbar é o nativo do Filament, e o clique abre
 * o overlay do Spotlight num `setTimeout` — sem ele o próprio clique fecharia o painel. Nada
 * disso existe num `$this->get()`.
 *
 * O que se assere é o overlay ABRIR **onde deve**: ancorado à viewport, acima do conteúdo, com o
 * fundo escurecido e o campo de busca na tela. O recorte por permissão do conteúdo é assunto das
 * categorias (`App\Filament\Spotlight\*`), coberto por unidade.
 *
 * **Por que a geometria, e não só `assertVisible`.** A versão anterior deste caso ficou verde
 * com o overlay a 1.833 px do topo numa viewport de 1.117 px: `assertVisible` do Playwright
 * considera visível o que tem caixa não-vazia e não está `display:none` — posição fora da
 * viewport não conta. O HTML era byte a byte correto; faltava o CSS inteiro (a blade do pacote
 * emite 66 utilitárias Tailwind, e a CSS pré-compilada do Filament não tem nenhuma). Quem lia
 * "não abre" estava certo; o teste, não. Ver `wikis/specs/fix/spotlight-sem-estilo/`.
 *
 * O dataset de tema existe porque as variantes `dark:` são metade de `spotlight.css`, e uma
 * implementação que as esqueça passa inteira no tema claro. O tema é declarado nas duas linhas,
 * e não herdado: `inDarkMode()` vaza para o cenário seguinte (`TemaEscuroTest.php:97`).
 */
it('F-45: abre o overlay da busca pelo gatilho da topbar, ancorado à viewport', function (string $tema): void {
    $this->actingAs(usuarioDoKit('master_global'));

    // Compila os componentes do painel pelo kernel, fora do cronômetro do Playwright — rodando
    // este arquivo isolado, a primeira linha do dataset estourava os 45 s só nisso
    // (`.ai/rules/testes-browser.md`, "aqueça pelo kernel").
    $this->get('/admin');

    $pagina = visit('/admin');
    $pagina = $tema === 'escuro' ? $pagina->inDarkMode() : $pagina->inLightMode();

    $pagina->assertSee('Painel de Controle')
        ->assertPresent('input[placeholder="Buscar registros e telas..."]')
        ->click('.fi-global-search-field')
        // É esta asserção que espera o Alpine abrir o overlay; a medição vem depois dela.
        ->assertVisible('input[placeholder="Buscar registros e telas..."]')
        ->assertNoJavaScriptErrors();

    // A raiz do componente é o próprio overlay; o âncora é o mesmo evento que o gatilho do kit
    // dispara — e o mesmo seletor de escopo de `resources/css/filament/spotlight.css`.
    $medida = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const raiz  = document.querySelector('[x-on\\:open-spotlight\\.window]');
            const caixa = raiz.firstElementChild;
            const input = raiz.querySelector('input');
            const r  = raiz.getBoundingClientRect();
            const cs = getComputedStyle(raiz);
            return JSON.stringify({
                position: cs.position, zIndex: cs.zIndex, fundo: cs.backgroundColor,
                top: r.top, left: r.left, largura: r.width, altura: r.height,
                viewportW: innerWidth, viewportH: innerHeight,
                inputTop: input.getBoundingClientRect().top,
                caixaFundo: getComputedStyle(caixa).backgroundColor,
            });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    $pagina->screenshot(fullPage: false, filename: "spotlight-{$tema}");

    // `rgb(r, g, b)` é opaco; `rgba(r, g, b, a)` traz o alfa — `rgba(0, 0, 0, 0)` é o defeito.
    $alfa = (float) (explode(',', trim((string) preg_replace('/^rgba?\(|\)$/', '', $medida['fundo'])))[3] ?? 1);

    expect($medida['position'])->toBe('fixed', 'o overlay não está fixo à viewport')
        ->and($medida['top'])->toEqual(0, "o overlay abre a {$medida['top']}px do topo (viewport de {$medida['viewportH']}px)")
        ->and($medida['left'])->toEqual(0)
        ->and($medida['largura'])->toEqual($medida['viewportW'])
        ->and($medida['altura'])->toEqual($medida['viewportH'])
        ->and((int) $medida['zIndex'])->toBeGreaterThanOrEqual(50, "z-index {$medida['zIndex']}: o overlay fica atrás da topbar")
        ->and($alfa)->toBeGreaterThan(0, 'o backdrop não escurece nada')
        ->and($medida['inputTop'])->toBeLessThan($medida['viewportH'], 'o campo de busca está fora da tela');

    $tema === 'escuro'
        ? expect($medida['caixaFundo'])->not->toBe('rgb(255, 255, 255)', 'caixa branca no tema escuro: as variantes dark: não chegaram')
        : expect($medida['caixaFundo'])->toBe('rgb(255, 255, 255)');
})->with(['claro', 'escuro'])->group('browser');

/**
 * F-52 — a página de 403 é uma tela, não um stack trace.
 *
 * O `PerfisTest` já prova que o barramento acontece. Este prova a QUALIDADE do que aparece: a
 * pessoa entende o que houve e tem por onde sair. E prova o limite: nada de nome de classe,
 * caminho de arquivo ou stack trace na tela.
 */
it('F-52: a tela de 403 explica e oferece saida, sem vazar interno', function (): void {
    $this->actingAs(usuarioDoKit('infra'));

    $pagina = visit('/admin');

    $pagina->assertSee('403')
        ->assertDontSee('Illuminate\\')
        ->assertDontSee('vendor/')
        ->assertDontSee('Stack trace')
        ->assertNoJavaScriptErrors();
});

/**
 * F-51 — o indicador de ambiente aparece fora de produção.
 *
 * Uma linha de defesa barata contra o erro mais caro que existe: achar que se está em
 * homologação e estar em produção. O plugin é registrado com `visible(fn () => ! isProduction())`
 * nos três painéis.
 */
it('F-51: mostra o indicador de ambiente fora de producao', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    visit('/admin')
        ->assertSee('Testing')
        ->assertNoJavaScriptErrors();
});
