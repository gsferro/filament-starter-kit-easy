<?php

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Models\User;
use App\Support\Paineis;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Contrato de acesso do kit: cada painel abre para quem deve e fecha para o
 * resto. É o teste que pega uma regressão em User::canAccessPanel() ou num
 * plugin que derrube a página inteira.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('serve as telas de login dos três painéis sem autenticação', function (string $painel): void {
    $this->get("/{$painel}/login")->assertSuccessful();
})->with(['app', 'admin', 'infra']);

it('deixa o master_global entrar em todos os painéis', function (string $painel): void {
    $user = usuarioCom('master_global');

    expect($user->canAccessPanel(Filament::getPanel($painel)))->toBeTrue();
})->with(['app', 'admin', 'infra']);

it('recorta admin e infra por papel', function (): void {
    $admin = usuarioCom('admin');
    $infra = usuarioCom('infra');

    expect($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and($admin->canAccessPanel(Filament::getPanel('infra')))->toBeFalse()
        ->and($infra->canAccessPanel(Filament::getPanel('infra')))->toBeTrue()
        ->and($infra->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('abre o painel app para quem tem papel do painel app', function (): void {
    expect(usuarioCom('panel_user')->canAccessPanel(Filament::getPanel('app')))->toBeTrue();
});

/**
 * O contrário do que o kit fazia até a 0.10.0, quando `canAccessPanel()` devolvia
 * `'app' => true` e qualquer autenticado entrava no painel de negócio. Agora o acesso é
 * um ATO: alguém escolhe um papel, e o papel declara o painel (`roles.painel`).
 */
it('fecha os três painéis para quem não tem papel nenhum', function (string $painel): void {
    expect(usuarioCom(null)->canAccessPanel(Filament::getPanel($painel)))->toBeFalse();
})->with(['app', 'admin', 'infra']);

it('não trata painel nulo como coringa', function (): void {
    // `roles.painel` nulo NÃO abre painel: quem entra em tudo é o master_global, pelo
    // Gate::before. Se alguém implementar nulo como "vale em qualquer painel", um papel
    // criado sem painel na tela do Shield vira chave-mestra em silêncio.
    Role::create(['name' => 'auditor', 'guard_name' => 'web', 'painel' => null]);

    expect(usuarioCom('auditor')->canAccessPanel(Filament::getPanel('app')))->toBeFalse();
});

it('nega painel registrando o motivo no log', function (): void {
    Log::shouldReceive('channel')->with('autenticacao')->andReturnSelf();
    Log::shouldReceive('warning')->once()->withArgs(
        fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[User@canAccessPanel]')
            && $contexto['motivo'] === 'sem_papel_do_painel'
            && $contexto['painel'] === 'admin',
    );

    usuarioCom('panel_user')->canAccessPanel(Filament::getPanel('admin'));
});

/**
 * Premissa desta suíte, em cada uma das três chaves que a compõem.
 *
 * Quebrada, ela não falha dizendo o nome: `kit.tenancy.enabled` ligada faz o
 * painel virar `/app/{tenant}` e todo `GET /app` responder 404; `permission.teams`
 * ligada faz atribuir papel estourar `NOT NULL constraint failed:
 * model_has_roles.team_id`. Nenhuma das duas mensagens aponta para a causa —
 * daí este teste vir antes.
 *
 * As três saem de lugares diferentes (env, `config/permission.php`,
 * `config/filament-shield.php`) e são alinhadas em `Tests\TestCase`. Os testes
 * de tenancy são os de `tests/Tenancy`.
 */
it('roda em modo single-tenant', function (): void {
    $causa = 'A suíte tests/Kit pressupõe o modo single-tenant nas três chaves. '
        .'Se a tenancy vazou para cá, algo passou por cima do que o '
        .'Tests\TestCase::createApplication() alinha.';

    expect(config('kit.tenancy.enabled'))->toBeFalse($causa)
        ->and(config('permission.teams'))->toBeFalse($causa)
        ->and(config('filament-shield.tenant_model'))->toBeNull($causa);
});

it('carrega o dashboard de cada painel autenticado', function (string $painel): void {
    $this->actingAs(usuarioCom('master_global'))
        ->get("/{$painel}")
        ->assertSuccessful();
})->with(['app', 'admin', 'infra']);

it('dá 403 no painel errado', function (): void {
    $this->actingAs(usuarioCom('infra'))
        ->get('/admin')
        ->assertForbidden();
});

it('gera permission para os três painéis, não só para o admin', function (): void {
    // Até a 0.10.0 o ShieldPermissionsSeeder rodava `shield:generate --panel=admin` e
    // mais nada: as telas de /app e /infra não tinham permission nenhuma no banco e só
    // abriam para o master_global.
    expect(Permission::where('name', 'ViewAny:Projeto')->exists())->toBeTrue()
        ->and(Paineis::permissoes('admin')->all())->toContain('ViewAny:User')
        ->and(Paineis::permissoes('infra')->all())->not->toContain('ViewAny:User')
        ->and(Paineis::permissoes('app')->all())->not->toBe(Paineis::permissoes('admin')->all());
});

it('recorta a matriz do papel pelo painel', function (): void {
    expect(Role::findByName('master_global')->permissions)->toHaveCount(0)
        ->and(Role::findByName('admin')->painel)->toBe('admin')
        ->and(Role::findByName('master_global')->painel)->toBeNull();

    $doAdmin = Role::findByName('admin')->permissions->pluck('name');

    expect($doAdmin->all())->toContain('ViewAny:User')
        ->and($doAdmin->filter(fn (string $p): bool => str_contains($p, 'AiRun'))->all())->toBeEmpty();
});

/**
 * A subtração do `panel_user` cobre as TRÊS famílias de entidade, não só Resource.
 *
 * A matriz do painel vem de `getEntitiesPermissions()`, que mistura Resource, Page e Widget;
 * a subtração vinha de `Paineis::resources()`, que só enxerga Resource. Enquanto a única
 * permission de Page do painel `app` era a de perfil (que deve mesmo ser de todos), o furo era
 * inofensivo — e mecanismo aberto para a próxima Page de administração. Ver ADR-06 da wiki
 * convite-em-massa.
 */
it('alcanca Page e Widget na subtracao do painel app', function (): void {
    $daPagina    = Paineis::permissoesDe('app', [MyProfilePage::class]);
    $doResource  = Paineis::permissoesDe('app', [ConviteResource::class]);
    $doPanelUser = Role::findByName('panel_user')->permissions->pluck('name');

    // A metade nova: com `array_column($e['permissions'], 'key')` — o formato de Resource —
    // esta coleção volta VAZIA, sem erro nenhum. É a única asserção que acusa.
    expect($daPagina->all())->toContain('View:MyProfilePage')
        ->and($doResource->all())->toContain('Create:Convite', 'ViewAny:Convite')
        // A subtração continua subtraindo o que deve...
        ->and($doPanelUser->all())->not->toContain('Create:Convite')
        // ...e não subtrai o que não deve: a página de perfil é de todos, e não está na
        // lista de FQCN de administração.
        ->and($doPanelUser->all())->toContain('View:MyProfilePage')
        ->and(Paineis::permissoesDe('app', ['App\\Nada'])->isEmpty())->toBeTrue();
});

it('registra o RoleResource publicado, não o do vendor', function (): void {
    // Enquanto esta asserção valer, a tela agrupada por painel está no ar. Um upgrade do
    // Shield que devolva o Resource ao vendor some com o agrupamento em silêncio.
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)->toContain(App\Filament\Admin\Resources\Roles\RoleResource::class)
        ->and($resources)->not->toContain(RoleResource::class);
});

it('agrupa as permissões por painel na tela de papéis', function (): void {
    $this->actingAs(usuarioCom('master_global'))
        ->get('/admin/shield/roles/create')
        ->assertSuccessful()
        ->assertSee('Painel /admin')
        ->assertSee('Painel /app')
        ->assertSee('Painel /infra');
});
