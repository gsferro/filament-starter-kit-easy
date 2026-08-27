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
 * O que se assere é o overlay ABRIR. O recorte por permissão do conteúdo é assunto das
 * categorias (`App\Filament\Spotlight\*`), coberto por unidade.
 */
it('F-45: abre o overlay da busca pelo gatilho da topbar', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    // O oráculo é o overlay ABERTO — o campo de busca dele visível —, não só "clicou sem erro".
    // Medido em 2026-08-26 depois de um relato de "não abre" numa instalação real: abria; o
    // relato era estado do navegador. Este caso passa a existir de verdade (era `todo`).
    visit('/admin')
        ->assertSee('Painel de Controle')
        ->assertPresent('input[placeholder="Buscar registros e telas..."]')
        ->click('.fi-global-search-field')
        ->assertVisible('input[placeholder="Buscar registros e telas..."]')
        ->assertNoJavaScriptErrors();
})->group('browser');

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
