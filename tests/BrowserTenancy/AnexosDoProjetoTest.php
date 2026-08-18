<?php

use App\Models\Projeto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\UploadedFile;

/**
 * A camada de mídia funcionando na tela, e não só no model.
 *
 * O que os CT de componente já provam (`tests/Tenancy/PacotesTierSTenancyTest.php`): a
 * coleção existe, o arquivo anexa, o escopo por organização isola, a conversão não é
 * enfileirada. Tudo isso é PHP, e roda em milissegundos.
 *
 * O que só o navegador prova, e é o motivo destes dois cenários existirem:
 *
 *   1. O `SpatieMediaLibraryFileUpload` é FilePond — um componente JavaScript. Ele pode
 *      estar no HTML e não inicializar, e o campo vira uma caixa morta sem erro de PHP.
 *   2. O `->simpleLightbox()` da coluna de mídia depende de um MACRO registrado no
 *      `boot(Panel)` do plugin, e de um script injetado por painel. Os dois podem sumir
 *      num upgrade sem quebrar nenhum teste de componente.
 *
 * Aqui e não em `tests/Browser` porque `projetos.tenant_id` é NOT NULL: sem organização
 * não há projeto para anexar arquivo.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    /*
     * O resource de Projetos e a demonstracao: `ProjetoResource::canAccess()` exige
     * `kit.demo` E `kit.tenancy.enabled`, e o phpunit.xml fixa `KIT_DEMO=false` para o
     * menu do painel nascer vazio no resto da suite. Sem esta linha a tela responde 403,
     * e o cenario mediria a guarda do resource em vez da camada de midia.
     */
    config(['kit.demo' => true]);
});

/*
 * Um cenário, não dois.
 *
 * A primeira versão tinha também um caso que abria a listagem VAZIA só para checar erro
 * de JS. Ele estourava os 45s de timeout e não provava nada que o caso abaixo não provasse
 * melhor: com a tabela vazia a coluna de mídia sequer renderiza, então o cenário passaria
 * verde com o `simpleLightbox()` quebrado. O caso com anexo cobre os dois.
 */

/**
 * A coluna de mídia com o lightbox, numa listagem que TEM anexo.
 *
 * Com a tabela vazia a coluna não renderiza, e o cenário passaria sem provar nada — por
 * isso o projeto é criado com arquivo antes do `visit()`.
 *
 * `assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a tela é de painel, cheia de
 * script de plugin de terceiro, e `console.log` alheio deixaria o caso vermelho por
 * dívida que não é do kit.
 */
it('renderiza a coluna de anexos na listagem', function (): void {
    $organizacao = tenant('Acme', 'acme');
    $user        = usuarioComPapel('master_global');
    $user->tenants()->attach($organizacao);

    noPainelDa($organizacao);

    Projeto::create(['nome' => 'Com anexo'])
        ->addMedia(UploadedFile::fake()->image('planta.png', 400, 300))
        ->toMediaCollection('anexos');

    $this->actingAs($user);

    visit("/app/{$organizacao->slug}/projetos")
        ->assertSee('Com anexo')
        ->assertNoJavaScriptErrors();
})->group('browser');
