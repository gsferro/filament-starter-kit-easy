<?php

use App\Filament\Exports\AgenteIaExporter;
use App\Filament\Exports\AiRunExporter;
use App\Filament\Exports\ConviteExporter;
use App\Filament\Exports\ProjetoExporter;
use App\Filament\Exports\TenantExporter;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\AgenteIaImporter;
use App\Filament\Imports\ProjetoImporter;
use App\Models\AgenteIa;
use App\Support\ImportExport\ExportadorDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Schedule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Import e export no modo SINGLE-TENANT, e o que vale nos dois modos.
 *
 * A suíte `Tenancy` prova a fronteira de organização. Esta prova o contrário: que a
 * fronteira não engessa a instalação sem tenancy — o requisito pediu "ambos os cenários".
 */

/*
|--------------------------------------------------------------------------
| Single-tenant: a fronteira não exige organização onde não há
|--------------------------------------------------------------------------
*/

/**
 * `AgenteIa` não usa `BelongsToTenant` e a tenancy está desligada nesta suíte: o
 * `ImportadorDoKit` não pode pedir `tenant_id` nenhum.
 *
 * Sem este caso, um fail-closed escrito largo (exigir organização sempre) mataria a
 * feature inteira no modo single-tenant — e passaria toda a suíte `Tenancy`.
 */
it('importa sem exigir organização quando não há tenancy', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $import = Import::create([
        'importer'   => AgenteIaImporter::class,
        'file_name'  => 'agentes.csv',
        'file_path'  => 'agentes.csv',
        'total_rows' => 1,
        // `admin` porque o importador consulta a policy do model por linha (Create/Update).
        'user_id'    => usuarioDoKit('admin', 'operador@example.com')->getKey(),
    ]);

    $importador = new AgenteIaImporter($import, [
        'slug'       => 'slug',
        'nome'       => 'nome',
        'instrucoes' => 'instrucoes',
    ], []);

    $importador([
        'slug'       => 'revisor',
        'nome'       => 'Revisor',
        'instrucoes' => 'Revise o texto.',
    ]);

    expect(AgenteIa::query()->where('slug', 'revisor')->exists())->toBeTrue();
});

/** Reimportar o mesmo slug atualiza — a resolução é por `colunaDeResolucao()`, não por ID. */
it('reimportar o mesmo slug atualiza em vez de duplicar', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $import = Import::create([
        'importer'   => AgenteIaImporter::class,
        'file_name'  => 'agentes.csv',
        'file_path'  => 'agentes.csv',
        'total_rows' => 2,
        // `admin` porque o importador consulta a policy do model por linha (Create/Update).
        'user_id'    => usuarioDoKit('admin', 'operador@example.com')->getKey(),
    ]);

    $mapa       = ['slug' => 'slug', 'nome' => 'nome', 'instrucoes' => 'instrucoes'];
    $importador = new AgenteIaImporter($import, $mapa, []);

    $importador(['slug' => 'revisor', 'nome' => 'Revisor', 'instrucoes' => 'v1']);
    $importador(['slug' => 'revisor', 'nome' => 'Revisor Sênior', 'instrucoes' => 'v2']);

    expect(AgenteIa::query()->where('slug', 'revisor')->count())->toBe(1)
        ->and(AgenteIa::query()->where('slug', 'revisor')->firstOrFail()->nome)->toBe('Revisor Sênior');
});

/*
|--------------------------------------------------------------------------
| Formula injection: ligada em toda coluna, de todo exportador do kit
|--------------------------------------------------------------------------
*/

/**
 * O `preventFormulaInjection()` do Filament nasce DESLIGADO, por coluna
 * (`Exports\Concerns\CanFormatState:27`). Célula começando em `=` é fórmula quando alguém
 * abre o CSV no Excel, e o dado veio de formulário de usuário.
 *
 * O caso varre TODO exportador do kit, e não só o de demonstração: um exportador novo que
 * estenda `Exporter` direto, pulando o `ExportadorDoKit`, é o jeito de reabrir isso — e
 * este é o caso que reprova.
 */
it('neutraliza fórmula em toda coluna de todo exportador do kit', function (string $exportador): void {
    expect(is_subclass_of($exportador, ExportadorDoKit::class))->toBeTrue();

    $colunas = $exportador::getColumns();

    expect($colunas)->not->toBeEmpty();

    foreach ($colunas as $coluna) {
        expect($coluna->shouldPreventFormulaInjection())->toBeTrue();
    }
})->with([
    ProjetoExporter::class,
    AgenteIaExporter::class,
    TenantExporter::class,
    UserExporter::class,
    ConviteExporter::class,
    AiRunExporter::class,
]);

/*
|--------------------------------------------------------------------------
| Colunas que NÃO podem estar no arquivo
|--------------------------------------------------------------------------
*/

/**
 * `token` e `token_lembrete` fora do export de convites.
 *
 * `Convite::aceitar()` valida o token e vincula o usuário à organização com o papel do
 * convite: um CSV com essa coluna é uma planilha de chaves de entrada. O gerador do
 * Filament as inclui por serem colunas do banco — este caso é o que impede que voltem num
 * `make:filament-exporter --force`.
 */
it('não exporta token de convite', function (): void {
    $nomes = array_map(fn ($coluna): string => $coluna->getName(), ConviteExporter::getColumns());

    expect($nomes)->not->toContain('token')
        ->not->toContain('token_lembrete');
});

/**
 * `request` e `response` fora do export do ledger de IA: são o prompt e a resposta
 * completos, de qualquer organização, e o `/infra` não tem tenant na rota para recortar.
 */
it('não exporta prompt nem resposta do ledger de IA', function (): void {
    $nomes = array_map(fn ($coluna): string => $coluna->getName(), AiRunExporter::getColumns());

    expect($nomes)->not->toContain('request')
        ->not->toContain('response');
});

/**
 * O import não aceita a organização como coluna do CSV.
 *
 * Se ela entrar, a fronteira do `ImportadorDoKit` fica decorativa: a linha escolhe o
 * destino. O gerador do Filament cria essa coluna para toda FK, então o caso vale como
 * guarda contra regenerar o arquivo.
 */
it('não aceita a organização como coluna do CSV de import', function (): void {
    $nomes = array_map(
        fn ($coluna): string => $coluna->getName(),
        ProjetoImporter::getColumns(),
    );

    expect($nomes)->not->toContain('tenant')
        ->not->toContain('tenant_id');
});

/*
|--------------------------------------------------------------------------
| Matriz de permissão
|--------------------------------------------------------------------------
*/

/**
 * `panel_user` NÃO nasce com import nem export.
 *
 * A subtração é por prefixo da ação, não por FQCN de resource: resource novo no `/app`
 * nasce com as duas fora do usuário comum sem ninguém lembrar de acrescentá-lo a lista
 * nenhuma. Sem isso, registrar um resource promoveria todo usuário de painel a exportador
 * da organização inteira, em silêncio.
 */
it('panel_user não nasce com permissão de import nem de export', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $permissoes = Role::query()
        ->where('name', config('filament-shield.panel_user.name', 'panel_user'))
        ->firstOrFail()
        ->permissions
        ->pluck('name');

    expect($permissoes)->not->toBeEmpty()
        ->and($permissoes->filter(fn (string $p): bool => str_starts_with($p, 'Import:'))->all())->toBe([])
        ->and($permissoes->filter(fn (string $p): bool => str_starts_with($p, 'Export:'))->all())->toBe([]);
});

/** As permissões existem no banco — sem elas a Action desaparece da tela, sem erro nenhum. */
it('as permissões de import e export são geradas para os resources do kit', function (): void {
    $this->seed([ShieldPermissionsSeeder::class]);

    expect(Permission::query()->whereIn('name', [
        'Import:Projeto', 'Export:Projeto',
        'Import:AgenteIa', 'Export:AgenteIa',
        'Export:Tenant', 'Export:User', 'Export:Convite',
    ])->count())->toBe(7);
});

/*
|--------------------------------------------------------------------------
| Retenção
|--------------------------------------------------------------------------
*/

/**
 * As duas podas estão AGENDADAS — sem isso `imports`/`exports` crescem para sempre, e o
 * `exports` guarda arquivo em disco.
 *
 * O oráculo é o agendamento, não a config: chave de retenção sem agendamento é a falha
 * silenciosa clássica — o número está escrito e nada o executa.
 */
it('agenda a poda do histórico de import e de export', function (): void {
    $nomes = collect(Schedule::events())->map(fn ($evento): ?string => $evento->description);

    expect($nomes)->toContain('kit:limpar-historico-de-importacoes')
        ->toContain('kit:limpar-historico-de-exportacoes');
});
