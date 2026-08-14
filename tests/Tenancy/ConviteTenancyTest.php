<?php

use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * O convite com a tenancy LIGADA — onde `model_has_roles.team_id` existe e o contexto da
 * atribuição deixa de ser detalhe.
 *
 * É aqui que ADR-07 se prova nas duas pontas: papel de `/app` nasce dentro da organização
 * do convite, e papel de painel sem tenancy nasce no contexto global mesmo quando o
 * convite carrega uma organização.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * @return array{0: Convite, 1: string}
 */
function conviteTenancyCom(string $papel, ?Tenant $tenant = null, ?string $email = null): array
{
    $convite = Convite::factory()->create([
        'email'     => $email ?? 'convidado@example.com',
        'role_id'   => Role::findByName($papel)->getKey(),
        'tenant_id' => $tenant?->getKey(),
    ]);

    return [$convite, $convite->enviar()];
}

function aceitarConviteTenancy(string $token): Testable
{
    Filament::setCurrentPanel('app');

    return Livewire::withQueryParams(['token' => $token])
        ->test(RegistroPorConvite::class)
        ->fillForm([
            'name'                 => 'Fulano',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');
}

it('vincula o usuario a organizacao do convite', function (): void {
    $acme   = Tenant::factory()->create(['slug' => 'acme']);
    $globex = Tenant::factory()->create(['slug' => 'globex']);

    [, $token] = conviteTenancyCom('panel_user', $acme);

    aceitarConviteTenancy($token)->assertHasNoFormErrors();

    $novo = User::where('email', 'convidado@example.com')->firstOrFail();

    $this->assertDatabaseHas('tenant_user', ['tenant_id' => $acme->id, 'user_id' => $novo->id]);
    $this->assertDatabaseMissing('tenant_user', ['tenant_id' => $globex->id, 'user_id' => $novo->id]);

    expect($novo->getTenants(Filament::getPanel('app'))->pluck('id')->all())->toBe([$acme->id]);
});

it('atribui papel de app no contexto da organizacao do convite', function (): void {
    $acme = Tenant::factory()->create(['slug' => 'acme']);

    [, $token] = conviteTenancyCom('panel_user', $acme);

    aceitarConviteTenancy($token)->assertHasNoFormErrors();

    $novo = User::where('email', 'convidado@example.com')->firstOrFail();

    // team_id é o id da organização, não CONTEXTO_GLOBAL: é a metade de ADR-07 que faz o
    // painel de negócio funcionar.
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $novo->id,
        'role_id'  => Role::findByName('panel_user')->getKey(),
        'team_id'  => $acme->id,
    ]);

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $novo->id,
        'team_id'  => Tenant::CONTEXTO_GLOBAL,
    ]);
});

/**
 * O caso de SEGURANÇA de ADR-07, e por isso o convite carrega uma organização que deve
 * ser ignorada para o papel: se alguém "simplificar" `aceitar()` para usar sempre o
 * `tenant_id` do convite, o papel `admin` nasceria dentro da Acme,
 * `canAccessPanel('admin')` devolveria false e o usuário viraria um administrador que não
 * administra. O caso falha alto nas duas pontas.
 */
it('atribui papel de admin no contexto global mesmo com organizacao no convite', function (): void {
    $acme = Tenant::factory()->create(['slug' => 'acme']);

    [, $token] = conviteTenancyCom('admin', $acme);

    aceitarConviteTenancy($token)->assertHasNoFormErrors();

    $novo = User::where('email', 'convidado@example.com')->firstOrFail();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $novo->id,
        'team_id'  => Tenant::CONTEXTO_GLOBAL,
    ]);

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $novo->id,
        'team_id'  => $acme->id,
    ]);

    expect($novo->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

/**
 * A rota de registro do Filament fica dentro do `->prefix($panel->getPath())`
 * (`vendor/filament/filament/routes/web.php:30`) e FORA do grupo do tenant (`:119-137`).
 * É isso que torna o link do convite auto-suficiente: a organização vem do token, não do
 * endereço. Se um upgrade mover o bloco para dentro do grupo, todo link já enviado passa
 * a dar 404 — este caso é o alarme.
 */
it('mantem a url de aceite fora do segmento de organizacao', function (): void {
    [, $token] = conviteTenancyCom('panel_user', Tenant::factory()->create(['slug' => 'acme']));

    $url = Filament::getPanel('app')->route('auth.register', ['token' => $token]);

    expect($url)->toContain('/app/register')
        ->and($url)->not->toContain('/acme');

    // Sem autenticação e sem organização na URL, com a tenancy ligada.
    $this->get("/app/register?token={$token}")->assertOk();
});
