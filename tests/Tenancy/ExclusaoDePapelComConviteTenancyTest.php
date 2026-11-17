<?php

use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Models\Convite;
use App\Models\Role;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Os convites de todas as organizações contam (wiki `excluir-papel-com-convites`, CT-11 e CT-12).
 *
 * A Acme é a organização corrente do contexto e o `/admin` é o painel da tela: o escopo de
 * organização do `Convite` é forçado a valer, que é o que faz o cenário discriminar. Os convites
 * nascem ANTES de fixar a Acme: o `refresh()` de `ofertaPara()` passa pelo escopo e não acharia o
 * convite da Globex.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    // Sem isto `audits` fica vazia e a asserção de "deleted" por convite é vácua.
    config(['audit.console' => true]);

    $this->acme   = tenant('Acme', 'acme');
    $this->globex = tenant('Globex', 'globex');

    Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'app']);
});

/**
 * A Acme corrente no `/admin`, com o escopo de organização do `Convite` FORÇADO a valer.
 *
 * Lacuna de arnês registrada (`04`, R7): o escopo do Filament só morde com o painel `app` corrente
 * (`BelongsToTenant::registerTenancyModelGlobalScope()`, `vendor/filament/filament/src/Resources/
 * Resource/Concerns/BelongsToTenant.php:144-147`), e a tela de papéis é do `/admin` — com o `app`
 * corrente ela morre em "Plugin [filament-shield] is not registered for panel [app]". Então o escopo
 * é instalado à mão, com o MESMO nome do escopo do painel `app` (quem o remove por nome, como o
 * model faz, remove este também), e restaurado no `afterEach`: o registro é estático no processo.
 */
function prepararContextoDaAcme(): void
{
    noPainelBootado('admin');
    forcarEscopoDaAcme();

    test()->actingAs(usuarioComPapel('master_global', null, 'master@example.com'));
}

/** A Acme corrente e o escopo do `Convite` forçado; separado do login para o CT-19 religar depois da revogação. */
function forcarEscopoDaAcme(): void
{
    Filament::setTenant(test()->acme, isQuiet: true);

    $nome = Filament::getPanel('app')->getTenancyScopeName();

    // Em `$GLOBALS`, e não em `test()->…`: o `isset()` sobre o proxy do `test()` é sempre falso.
    $GLOBALS['escopoOriginalDoConvite'] ??= [$nome, (new Convite)->getGlobalScopes()[$nome] ?? null];

    $acme = test()->acme->getKey();

    Convite::addGlobalScope($nome, fn (Builder $consulta): Builder => $consulta->where('convites.tenant_id', $acme));
}

afterEach(fn () => restaurarEscopoDoConvite());

/** Desfaz o escopo forçado por `prepararContextoDaAcme()` — o `/admin` real não tem escopo de organização. */
function restaurarEscopoDoConvite(): void
{
    if (! isset($GLOBALS['escopoOriginalDoConvite'])) {
        return;
    }

    [$nome, $original] = $GLOBALS['escopoOriginalDoConvite'];
    unset($GLOBALS['escopoOriginalDoConvite']);

    if ($original !== null) {
        Convite::addGlobalScope($nome, $original);

        return;
    }

    $propriedade = new ReflectionProperty(Model::class, 'globalScopes');
    $escopos     = $propriedade->getValue();

    unset($escopos[Convite::class][$nome]);
    $propriedade->setValue(null, $escopos);
}

/** Exclui o papel "suporte" pela linha, pela edição ou pela massa (só ele selecionado), sem tratar exceção. */
function excluirSuportePeloCaminhoDoAdmin(string $caminho): void
{
    $papel = Role::query()->where('name', 'suporte')->firstOrFail();

    if ($caminho === 'edicao') {
        Livewire::test(EditRole::class, ['record' => $papel->getRouteKey()])
            ->mountAction(TestAction::make(DeleteAction::class))
            ->callMountedAction();

        return;
    }

    $lista = Livewire::test(ListRoles::class)->loadTable();

    if ($caminho === 'linha') {
        $lista->mountAction(TestAction::make(DeleteAction::class)->table($papel))->callMountedAction();

        return;
    }

    $lista
        ->selectTableRecords([$papel->getKey()])
        ->mountAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
        ->callMountedAction();
}

function textoDosAvisosDeExclusaoTenancy(): string
{
    return implode("\n", array_map(
        fn (array $aviso): string => strip_tags(($aviso['title'] ?? '').' '.($aviso['body'] ?? '')),
        avisosDoFilament(),
    ));
}

it('[CT-11] o convite Pendente de outra organização também recusa a exclusão', function (string $caminho): void {
    $carla = ofertaPara('carla@example.com', $this->globex, 'suporte');

    prepararContextoDaAcme();

    // Sanidade do arranjo: com a Acme corrente o escopo esconde o convite da Globex.
    expect(Convite::query()->count())->toBe(0);

    // Termina sem exceção: uma `QueryException` da chave estrangeira estoura aqui.
    excluirSuportePeloCaminhoDoAdmin($caminho);

    expect(textoDosAvisosDeExclusaoTenancy())->toContain('carla@example.com')
        ->and(DB::table('roles')->where('name', 'suporte')->exists())->toBeTrue()
        ->and(DB::table('convites')->where('id', $carla->getKey())->exists())->toBeTrue();
})->with([
    'ação Excluir da linha'               => ['linha'],
    'ação Excluir do cabeçalho da edição' => ['edicao'],
    'exclusão em massa só com o suporte'  => ['massa'],
]);

it('[CT-12] os convites encerrados das duas organizações somem com o papel, auditados', function (string $caminho): void {
    $ana = ofertaPara('ana@example.com', $this->acme, 'suporte', ['aceito_em' => now()]);
    $gil = ofertaPara('gil@example.com', $this->globex, 'suporte', ['expira_em' => now()->subDay()]);

    prepararContextoDaAcme();

    // Sanidade do arranjo: o escopo está mordendo (só o da Acme é visível).
    expect(Convite::query()->count())->toBe(1);

    excluirSuportePeloCaminhoDoAdmin($caminho);

    expect(DB::table('roles')->where('name', 'suporte')->exists())->toBeFalse()
        ->and(DB::table('convites')->where('id', $ana->getKey())->exists())->toBeFalse()
        ->and(DB::table('convites')->where('id', $gil->getKey())->exists())->toBeFalse();

    foreach ([$ana, $gil] as $convite) {
        expect(DB::table('audits')
            ->where('auditable_type', Convite::class)
            ->where('auditable_id', $convite->getKey())
            ->where('event', 'deleted')
            ->count())->toBe(1);
    }
})->with([
    'ação Excluir da linha'               => ['linha'],
    'ação Excluir do cabeçalho da edição' => ['edicao'],
    'exclusão em massa só com o suporte'  => ['massa'],
]);

it('[CT-19] revogado no /admin o Pendente de outra organização, a nova exclusão passa', function (): void {
    $carla = ofertaPara('carla@example.com', $this->globex, 'suporte');
    $papel = Role::query()->where('name', 'suporte')->firstOrFail();

    prepararContextoDaAcme();

    $lista = Livewire::test(ListRoles::class)->loadTable();

    $lista->mountAction(TestAction::make(DeleteAction::class)->table($papel))->callMountedAction();

    expect(DB::table('roles')->where('name', 'suporte')->exists())->toBeTrue()
        ->and(textoDosAvisosDeExclusaoTenancy())->toContain('carla@example.com');

    // A saída pela tela de Convites do /admin alcança o convite da Globex. No /admin real o escopo
    // de organização não morde (só com o painel `app` corrente), então ele sai antes da revogação;
    // volta para a nova exclusão, que é onde a correção tem de resistir a ele.
    restaurarEscopoDoConvite();
    Filament::setTenant(null, isQuiet: true);

    Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('delete')->table($carla));

    expect(DB::table('convites')->where('id', $carla->getKey())->exists())->toBeFalse();

    limparAvisosDoFilament();
    forcarEscopoDaAcme();

    // No mesmo componente da tabela de papéis que recusou.
    $lista->mountAction(TestAction::make(DeleteAction::class)->table($papel))->callMountedAction();

    expect(DB::table('roles')->where('name', 'suporte')->exists())->toBeFalse()
        ->and(DB::table('convites')->where('id', $carla->getKey())->exists())->toBeFalse()
        ->and(textoDosAvisosDeExclusaoTenancy())->not->toContain('carla@example.com');
});
