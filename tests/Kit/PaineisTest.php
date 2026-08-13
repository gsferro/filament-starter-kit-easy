<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;

/**
 * Contrato de acesso do kit: cada painel abre para quem deve e fecha para o
 * resto. É o teste que pega uma regressão em User::canAccessPanel() ou num
 * plugin que derrube a página inteira.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function usuarioCom(?string $papel): User
{
    $user = User::create([
        'name'     => 'Teste',
        'email'    => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    if ($papel !== null) {
        $user->assignRole($papel);
    }

    return $user;
}

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

it('abre o painel app para qualquer usuário autenticado', function (): void {
    expect(usuarioCom(null)->canAccessPanel(Filament::getPanel('app')))->toBeTrue();
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
