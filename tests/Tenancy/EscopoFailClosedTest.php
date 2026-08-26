<?php

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\Projeto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Resources\Resource;

/**
 * Todo resource do painel `/app` FECHA a query quando não há organização corrente.
 *
 * ## O achado que isto conserta (N-04 da auditoria de aderência ao Blueprint)
 *
 * Três resources no mesmo painel, dois fechando e um não. `UserResource` e `ConviteResource`
 * sobrescrevem `getEloquentQuery()` com `whereRaw('1 = 0')` sem tenant; `ProjetoResource` confiava
 * no escopo global de `BelongsToTenant`, que **falha aberto por desenho** — sem tenant, nenhum
 * `where` é aplicado. Medido numa instalação real com o demo ligado: sem tenant corrente,
 * `ProjetoResource::getEloquentQuery()->count()` devolvia 4 de 4 (todas as organizações).
 *
 * Em request de painel isso nunca acontece — o middleware identifica a organização antes. O
 * alcance é fora de request: job, comando, busca sem contexto. É pouco, e é exatamente o tipo de
 * fresta que uma feature nova alarga sem perceber.
 *
 * ## Por que um sweep e não um caso por resource
 *
 * Porque o defeito era de **assimetria**: nada escrito decidia se o padrão era fechar ou confiar
 * na trait, e o terceiro resource seguiu o caminho que não estava testado. Este caso percorre todos
 * os resources do painel — resource novo que não feche fica vermelho aqui, com o nome dele.
 *
 * Ver `wikis/specs/feat/aderencia-ao-blueprint/aderencia-ao-blueprint/` (ADR-04).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * @return list<class-string<\Filament\Resources\Resource>>
 */
function resourcesDoPainelApp(): array
{
    return array_values(Filament::getPanel('app')->getResources());
}

it('faz todo resource do /app devolver vazio sem organizacao corrente', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    // O `tenant_id` vem da trait, do tenant corrente no `creating` — por isso se cria DENTRO de
    // cada organização, e só depois se sai de todas.
    noPainelDa($acme);
    Projeto::create(['nome' => 'Acme 1']);
    Projeto::create(['nome' => 'Acme 2']);
    noPainelDa($globex);
    Projeto::create(['nome' => 'Globex 1']);
    Projeto::create(['nome' => 'Globex 2']);

    Filament::setCurrentPanel('app');
    Filament::setTenant(null, isQuiet: true);
    expect(Projeto::withoutGlobalScopes()->count())->toBe(4, 'Pré-condição: há projetos de duas organizações no banco.');

    $resources = resourcesDoPainelApp();

    /*
     * Âncora de população: hoje são exatamente três. Se o número mudou, a lista abaixo tem de
     * cobrir o novo — e se caiu a zero, a descoberta quebrou e o `foreach` ficaria verde para
     * sempre.
     */
    expect($resources)->toContain(ProjetoResource::class, UserResource::class, ConviteResource::class)
        ->and(count($resources))->toBeGreaterThanOrEqual(3);

    $abertos = [];

    foreach ($resources as $resource) {
        if ($resource::getEloquentQuery()->count() > 0) {
            $abertos[] = class_basename($resource);
        }
    }

    expect($abertos)->toBe([],
        'Resources do /app que devolvem registros SEM organização corrente (falha aberta): '
        .implode(', ', $abertos).'. O padrão é `whereRaw(\'1 = 0\')` — ver App/Users/UserResource.php.'
    );
})->group('tenancy');

/**
 * A metade válida: com organização, o resource devolve só o dela.
 *
 * Sem este caso, uma correção que fechasse SEMPRE (`1 = 0` incondicional) passaria no caso acima e
 * quebraria o painel inteiro.
 */
it('faz ProjetoResource devolver so os projetos da organizacao corrente', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    noPainelDa($globex);
    foreach (['G1', 'G2', 'G3'] as $nome) {
        Projeto::create(['nome' => $nome]);
    }

    noPainelDa($acme);
    $daAcme = collect([Projeto::create(['nome' => 'A1']), Projeto::create(['nome' => 'A2'])]);

    $ids = ProjetoResource::getEloquentQuery()->pluck('id')->all();

    expect($ids)->toHaveCount(2)
        ->and($ids)->toEqualCanonicalizing($daAcme->pluck('id')->all());
})->group('tenancy');
