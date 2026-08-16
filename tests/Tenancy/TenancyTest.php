<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Projeto;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoTenancySeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\TenantsSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Isolamento entre tenants — a promessa central do modo multi-tenant.
 *
 * Roda com a tenancy LIGADA (Tests\TenancyTestCase, amarrado a esta pasta em
 * tests/Pest.php); o resto da suíte do kit continua em single-tenant, que é o
 * default do kit.
 *
 * `tenant()`, `usuario()`, `usuarioComPapel()` e `papelNaOrganizacao()` moram em
 * `tests/Pest.php`: três arquivos os usam, e em Pest a função é global no processo —
 * declará-la duas vezes é fatal error de redeclaração.
 */
it('cria as tabelas de permissão com a coluna de tenant', function (): void {
    $tabela = config('permission.table_names.model_has_roles', 'model_has_roles');
    $coluna = config('permission.column_names.team_foreign_key', 'team_id');

    // Invariante que já quebrou de verdade: o `kit:tenancy` rodava
    // `migrate:fresh` no mesmo processo, com a config ainda em memória dizendo
    // teams=false, e as tabelas nasciam sem a coluna. O banco ficava de pé e o
    // erro só aparecia no primeiro login ("no such column").
    expect(Schema::hasColumn($tabela, $coluna))->toBeTrue()
        ->and(Schema::hasColumn(config('permission.table_names.roles', 'roles'), $coluna))->toBeTrue();
});

it('atribui papel no contexto global sem violar a constraint', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', config('kit.admin.email'))->firstOrFail();

    // O seeder roda fora de request: sem o contexto global fixado, o spatie
    // gravaria team_id nulo e estouraria NOT NULL.
    expect($master->isMasterGlobal())->toBeTrue();
});

it('lista apenas os tenants vinculados ao usuário', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $user = usuario();
    $user->tenants()->attach($acme);

    $tenants = $user->getTenants(Filament::getPanel('app'));

    expect($tenants)->toHaveCount(1)
        ->and($tenants->first()->id)->toBe($acme->id)
        ->and($tenants->pluck('id'))->not->toContain($globex->id);
});

it('esconde tenant inativo da lista', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex', ativo: false);

    $user = usuario();
    $user->tenants()->attach([$acme->id, $globex->id]);

    expect($user->getTenants(Filament::getPanel('app'))->pluck('id')->all())->toBe([$acme->id]);
});

it('nega acesso a tenant ao qual o usuário não pertence', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $user = usuario();
    $user->tenants()->attach($acme);

    expect($user->canAccessTenant($acme))->toBeTrue()
        ->and($user->canAccessTenant($globex))->toBeFalse();
});

it('registra em log a tentativa de acesso a tenant não vinculado', function (): void {
    $canal = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('tenancy')->andReturn($canal);

    $globex = tenant('Globex', 'globex');
    $user   = usuario();

    $user->canAccessTenant($globex);

    // Nível warning (não error): tentativa de acesso indevido é condição
    // esperada de negação, não falha de sistema.
    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[User@canAccessTenant]')
            && $contexto['motivo'] === 'sem_vinculo'
            && $contexto['tenant_id'] === $globex->id)
        ->once();
});

it('deixa o master_global acessar qualquer tenant', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $acme   = tenant('Acme', 'acme');
    $master = User::where('email', config('kit.admin.email'))->firstOrFail();

    // Sem nenhum vínculo no pivot: o acesso vem do papel, não da tabela.
    expect($master->tenants()->count())->toBe(0)
        ->and($master->canAccessTenant($acme))->toBeTrue()
        ->and($master->getTenants(Filament::getPanel('app')))->toHaveCount(1);
});

it('recorta as queries pelo tenant corrente', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    projeto($acme, 'Portal');
    projeto($acme, 'Migração');
    projeto($globex, 'App de vendas');

    Filament::setTenant($acme, isQuiet: true);
    expect(Projeto::orderBy('id')->pluck('nome')->all())->toBe(['Portal', 'Migração']);

    Filament::setTenant($globex, isQuiet: true);
    expect(Projeto::orderBy('id')->pluck('nome')->all())->toBe(['App de vendas']);
});

it('preenche o tenant corrente ao criar um registro', function (): void {
    $acme = tenant('Acme', 'acme');

    Filament::setTenant($acme, isQuiet: true);

    $projeto = Projeto::create(['nome' => 'Novo']);

    expect($projeto->tenant_id)->toBe($acme->id);
});

it('volta a enxergar tudo quando não há tenant corrente', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    projeto($acme, 'Portal');
    projeto($globex, 'App de vendas');

    Filament::setTenant(null, isQuiet: true);

    // Comportamento deliberado: job/comando/seeder rodam sem tenant e precisam
    // enxergar todos. Documentado em App\Traits\BelongsToTenant.
    expect(Projeto::count())->toBe(2);
});

it('resolve papéis por tenant', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $user = usuario();
    $user->tenants()->attach([$acme->id, $globex->id]);

    $registrar = app(PermissionRegistrar::class);

    $registrar->setPermissionsTeamId($acme->id);
    $user->assignRole('admin');

    expect($user->fresh()->hasRole('admin'))->toBeTrue();

    $registrar->setPermissionsTeamId($globex->id);
    $registrar->forgetCachedPermissions();

    expect($user->fresh()->hasRole('admin'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Requisições HTTP de verdade
|--------------------------------------------------------------------------
| Os casos acima exercitam models e escopo; estes sobem a PÁGINA. A diferença
| importa: foi só ao renderizar o menu de tenant que apareceu o TypeError de
| `getTenantName()` — o Filament resolve o nome por `getAttributeValue('name')`
| e a coluna do kit é `nome`. Nenhum teste de model chegaria lá.
*/

it('abre o painel de negócio no tenant vinculado', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);
    $this->seed(TenantsSeeder::class);

    $master = User::where('email', config('kit.admin.email'))->firstOrFail();
    $tenant = Tenant::where('slug', 'padrao')->firstOrFail();

    $this->actingAs($master)->get("/app/{$tenant->slug}")->assertSuccessful();
});

it('responde 404 — e não 403 — no painel de um tenant não vinculado', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $globex = tenant('Globex', 'globex');

    // COM papel do painel app, mas SEM vínculo com a Globex: é o recorte que interessa.
    // Sem o papel o request morreria antes, em canAccessPanel(), e o 403 esconderia a
    // propriedade que este caso trava.
    $user = usuarioComPapel('panel_user', $globex);
    $user->tenants()->detach();

    // 404 é deliberado do Filament (IdentifyTenant faz `abort(404)`): um 403
    // confirmaria que o tenant EXISTE, e bastaria varrer slugs para enumerar
    // os clientes da instalação. O teste trava essa propriedade — se um dia
    // alguém "corrigir" para 403, a regressão aparece aqui.
    $this->actingAs($user)->get('/app/globex')->assertNotFound();
});

it('exige papel no contexto global para os painéis da instalação', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $acme = tenant('Acme', 'acme');

    // Papel `admin` atribuído DENTRO de uma organização não é credencial para
    // administrar a instalação. Sem esta barreira, promover alguém a admin da própria
    // organização abriria o /admin da aplicação inteira.
    $user = usuarioComPapel('admin', $acme);
    $user->tenants()->syncWithoutDetaching([$acme->id]);

    $this->assertDatabaseHas(config('permission.table_names.model_has_roles', 'model_has_roles'), [
        'model_id' => $user->id,
        'team_id'  => $acme->id,
    ]);

    $this->actingAs($user)->get('/admin')->assertForbidden();

    // O mesmo papel, no contexto global, abre.
    $global = usuarioComPapel('admin', null, 'global@example.com');
    $this->actingAs($global)->get('/admin')->assertSuccessful();
});

it('mantém admin e infra fora do escopo de tenant', function (string $rota): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', config('kit.admin.email'))->firstOrFail();

    $this->actingAs($master)->get($rota)->assertSuccessful();
})->with([
    '/admin/users',
    '/infra/health-check-results',
    '/infra/logs',
]);

it('salva os papéis do usuário no painel admin', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', config('kit.admin.email'))->firstOrFail();
    $alvo   = usuario('alvo@example.com');
    $papel  = Role::findByName('admin');
    $acme   = tenant('Acme', 'acme');   // o form exige organização com a tenancy ligada

    Filament::setCurrentPanel('admin');

    $this->actingAs($master);

    Livewire::test(EditUser::class, ['record' => $alvo->getRouteKey()])
        ->fillForm(['roles' => [$papel->getKey()], 'tenants' => [$acme->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    /*
     * A pivot precisa nascer COM o team_id. O `->relationship()` do Filament
     * grava por `$relationship->sync()`, que escreve só as colunas da chave —
     * e a tela devolvia 500 (`NOT NULL constraint failed:
     * model_has_roles.team_id`). Só um teste que GRAVA pega isso: carregar
     * /admin/users passava.
     */
    $this->assertDatabaseHas(config('permission.table_names.model_has_roles', 'model_has_roles'), [
        'model_id' => $alvo->id,
        'role_id'  => $papel->getKey(),
        'team_id'  => Tenant::CONTEXTO_GLOBAL,
    ]);

    expect($alvo->fresh()->hasRole('admin'))->toBeTrue();
});

it('registra o cadastro de tenants no painel admin', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', config('kit.admin.email'))->firstOrFail();
    $slug   = config('kit.tenancy.slug');

    $this->actingAs($master)->get("/admin/{$slug}")->assertSuccessful();
});

it('cria o cenário completo da demo, de forma idempotente', function (): void {
    $this->seed(DemoTenancySeeder::class);
    $this->seed(DemoTenancySeeder::class);

    $acme = Tenant::where('slug', 'acme')->firstOrFail();
    $ana  = User::where('email', 'ana@example.com')->firstOrFail();

    expect(Tenant::whereIn('slug', ['acme', 'globex'])->count())->toBe(2)
        ->and(Projeto::withoutGlobalScope('tenant')->count())->toBe(4)
        ->and(User::where('email', 'carla@example.com')->firstOrFail()->tenants()->count())->toBe(2)
        ->and($ana->tenants()->count())->toBe(1);

    // A demo mostra a persona: Ana administra a Acme. Rodar o seeder duas vezes não pode
    // duplicar a atribuição — `assignRole()` é idempotente, mas a asserção é o alarme.
    expect(
        DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
            ->where('model_id', $ana->id)
            ->where('role_id', Role::findByName('admin_app')->getKey())
            ->where('team_id', $acme->id)
            ->count()
    )->toBe(1);
});

/** Cria um projeto sem depender do tenant corrente (o seeder faz igual). */
function projeto(Tenant $tenant, string $nome): Projeto
{
    $projeto            = new Projeto(['nome' => $nome]);
    $projeto->tenant_id = $tenant->id;
    $projeto->save();

    return $projeto;
}
