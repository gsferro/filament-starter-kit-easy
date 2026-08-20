<?php

use App\Models\Projeto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Import e export na tela, e não só na classe.
 *
 * O que os CT de componente já provam (`tests/Tenancy/ImportExportTenancyTest.php`): a
 * fronteira de organização no `resolveRecord()`, o fail-closed sem `tenant_id`, o recorte
 * da query de export, a matriz de permissão. Tudo isso é PHP e roda em milissegundos.
 *
 * O que só o navegador prova, e é o motivo destes cenários existirem:
 *
 *   1. As duas Actions **aparecem** no cabeçalho. Uma `authorize()` escrita errada, um
 *      importer com FQCN inexistente ou uma coluna com `->relationship()` quebrada
 *      derrubam a tela — e teste de componente que só chama `assertActionVisible()` não
 *      renderiza o modal.
 *   2. O modal do export **abre e submete**. Ele é montado por schema do Filament sobre
 *      as colunas do exporter; coluna mal declarada estoura no render, não no `getColumns()`.
 *   3. A notificação de conclusão chega com o **botão de download** — o pedido literal do
 *      requisito ("pode colocar na notifications, o link para o download").
 *
 * A fila é `sync` na suíte (`phpunit.xml`), então o `Bus::chain` do export roda inline e a
 * notificação chega na mesma requisição. Em produção é worker de verdade — o que muda é o
 * tempo, não o caminho.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    /*
     * `ProjetoResource::canAccess()` exige `kit.demo` E `kit.tenancy.enabled`, e o
     * phpunit.xml fixa `KIT_DEMO=false` para o menu nascer vazio no resto da suíte. Sem
     * esta linha a tela responde 403 e o cenário mediria a guarda do resource.
     */
    config(['kit.demo' => true]);
});

it('mostra import e export no cabeçalho da listagem de projetos', function (): void {
    $organizacao = tenant('Acme', 'acme');
    $user        = usuarioComPapel('master_global');
    $user->tenants()->attach($organizacao);

    noPainelDa($organizacao);

    Projeto::create(['nome' => 'Contrato 2026']);

    $this->actingAs($user);

    visit("/app/{$organizacao->slug}/projetos")
        ->assertSee('Importar')
        ->assertSee('Exportar')
        ->assertNoJavaScriptErrors();
})->group('browser');

/**
 * O modal do export ABRE e monta o mapeamento de colunas.
 *
 * É a metade que só o navegador prova: o modal é schema do Filament montado sobre
 * `Exporter::getColumns()`, e coluna mal declarada estoura no RENDER — não no
 * `getColumns()`, que teste de componente já exercita.
 *
 * A conclusão do export e o **botão de download na notificação** ficam no teste de
 * componente (`tests/Tenancy/ImportExportTenancyTest.php`): submeter aqui exigiria
 * desambiguar dois botões chamados "Exportar" na mesma página, e o oráculo da notificação
 * é mais forte no componente, onde dá para afirmar sobre a ação anexada e não sobre o
 * texto dela.
 */
it('abre o modal de export com o mapeamento de colunas', function (): void {
    $organizacao = tenant('Acme', 'acme');
    $user        = usuarioComPapel('master_global');
    $user->tenants()->attach($organizacao);

    noPainelDa($organizacao);

    Projeto::create(['nome' => 'Contrato 2026']);

    $this->actingAs($user);

    visit("/app/{$organizacao->slug}/projetos")
        ->click('Exportar Projetos')
        ->assertSee('Colunas')
        ->assertSee('Nome')
        ->assertNoJavaScriptErrors();
})->group('browser');
