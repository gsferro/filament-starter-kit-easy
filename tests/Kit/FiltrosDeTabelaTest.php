<?php

use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Infra\Resources\AiRuns\Pages\ListAiRuns;
use App\Models\Convite;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Fomvasss\AiTasks\Models\AiRun;
use Livewire\Livewire;

/**
 * Os filtros de tabela do kit que nunca foram acionados por teste.
 *
 * A auditoria de aderência ao Blueprint (N-33) listou quatro filtros declarados e nunca
 * exercitados: `pendente` de `ConvitesTable`, e `status`/`task`/`driver` de `AiRunsTable`. Um
 * filtro declarado e não testado fica verde com o `->query()`/`->queries()` apagado — a tabela
 * segue renderizando o filtro, e ele não filtra nada.
 *
 * Cada caso cria um registro de CADA lado do filtro: só o `assertCanNotSeeTableRecords` separa "o
 * filtro filtra" de "a tabela mostra tudo". O filtro `ativo` de `TenantsTable` vive em
 * `tests/Tenancy/FiltrosDeTabelaTenancyTest.php`, porque `TenantResource` exige a tenancy ligada.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Uma execução de IA com os três atributos filtráveis.
 *
 * `tenant_id` e `modality` são NOT NULL na migration do pacote e não são o assunto — mesmos
 * valores de `tests/Kit/GraficosDoDashboardTest.php`.
 */
function execucaoDeIa(string $status, string $task, string $driver): AiRun
{
    return AiRun::create([
        'tenant_id' => '1',
        'task'      => $task,
        'driver'    => $driver,
        'modality'  => 'text',
        'status'    => $status,
    ]);
}

/**
 * O filtro `pendente` (`ConvitesTable.php:60`), que tem `->queries()` próprio sobre `aceito_em`.
 *
 * Os dois lados, porque o `TernaryFilter` tem TRÊS ramos e o `blank` devolve a query intocada:
 * um caso só com `true` ficaria verde com `false` apontando para o mesmo ramo.
 */
it('filtra os convites por pendente nos dois sentidos', function (bool $valor): void {
    $pendente = ofertaPara('pendente@example.com');
    $aceito   = ofertaPara('aceito@example.com', atributos: ['aceito_em' => now()]);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    noPainelDoShield('admin');

    [$visivel, $oculto] = $valor ? [$pendente, $aceito] : [$aceito, $pendente];

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->filterTable('pendente', $valor)
        ->assertCanSeeTableRecords([$visivel])
        ->assertCanNotSeeTableRecords([$oculto]);

    // Sanidade do arranjo: os dois convites existem e estão em lados opostos.
    expect(Convite::count())->toBe(2, 'o arranjo deveria ter exatamente um convite de cada lado do filtro');
})->with([
    'só os pendentes' => [true],
    'só os aceitos'   => [false],
]);

/**
 * Os três `SelectFilter` do ledger de IA (`AiRunsTable.php:49,60,62`).
 *
 * A execução A casa com o valor filtrado nos três atributos e a B não casa em nenhum — assim uma
 * linha do dataset basta por filtro, e a mesma dupla serve às três. A persona é `infra`, que tem o
 * gate `ver-ai-tasks` e `ViewAny:AiRun`, os dois que `AiRunResource::canAccess()` exige.
 *
 * `noPainelBootado('infra')` porque a tabela usa `OdometerColumn` e `recordUrl()` resolve
 * `AiRunResource::getUrl('view')` — os dois precisam do painel corrente de verdade.
 */
it('filtra o ledger de IA por status, tarefa e driver', function (string $filtro, string $valor): void {
    $casa    = execucaoDeIa(status: 'ok', task: 'resumo', driver: 'openai');
    $naoCasa = execucaoDeIa(status: 'error', task: 'traducao', driver: 'anthropic');

    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));
    noPainelDoShield('infra');
    noPainelBootado('infra');

    Livewire::test(ListAiRuns::class)
        ->loadTable()
        ->filterTable($filtro, $valor)
        ->assertCanSeeTableRecords([$casa])
        ->assertCanNotSeeTableRecords([$naoCasa]);
})->with([
    '`status`' => ['status', 'ok'],
    '`task`'   => ['task', 'resumo'],
    '`driver`' => ['driver', 'openai'],
]);
