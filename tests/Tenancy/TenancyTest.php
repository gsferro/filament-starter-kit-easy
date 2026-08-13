<?php

use App\Models\Projeto;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoTenancySeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;
use Spatie\Permission\PermissionRegistrar;

/**
 * Isolamento entre tenants — a promessa central do modo multi-tenant.
 *
 * Roda com a tenancy LIGADA (Tests\TenancyTestCase, amarrado a esta pasta em
 * tests/Pest.php); o resto da suíte do kit continua em single-tenant, que é o
 * default do kit.
 */
function tenant(string $nome, string $slug, bool $ativo = true): Tenant
{
    return Tenant::create(['nome' => $nome, 'slug' => $slug, 'ativo' => $ativo]);
}

function usuario(string $email = 'user@example.com'): User
{
    return User::create(['name' => 'Usuário', 'email' => $email, 'password' => 'password']);
}

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

it('mantém admin e infra fora do escopo de tenant', function (string $rota): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', config('kit.admin.email'))->firstOrFail();

    $this->actingAs($master)->get($rota)->assertSuccessful();
})->with([
    '/admin/users',
    '/infra/health-check-results',
    '/infra/logs',
]);

it('registra o cadastro de tenants no painel admin', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', config('kit.admin.email'))->firstOrFail();
    $slug   = config('kit.tenancy.slug');

    $this->actingAs($master)->get("/admin/{$slug}")->assertSuccessful();
});

it('cria o cenário completo da demo, de forma idempotente', function (): void {
    $this->seed(DemoTenancySeeder::class);
    $this->seed(DemoTenancySeeder::class);

    expect(Tenant::whereIn('slug', ['acme', 'globex'])->count())->toBe(2)
        ->and(Projeto::withoutGlobalScope('tenant')->count())->toBe(4)
        ->and(User::where('email', 'carla@example.com')->firstOrFail()->tenants()->count())->toBe(2)
        ->and(User::where('email', 'ana@example.com')->firstOrFail()->tenants()->count())->toBe(1);
});

/** Cria um projeto sem depender do tenant corrente (o seeder faz igual). */
function projeto(Tenant $tenant, string $nome): Projeto
{
    $projeto            = new Projeto(['nome' => $nome]);
    $projeto->tenant_id = $tenant->id;
    $projeto->save();

    return $projeto;
}
