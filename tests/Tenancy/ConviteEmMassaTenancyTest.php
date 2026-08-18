<?php

use App\Filament\App\Resources\Convites\Pages\ListConvites;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * O convite em massa com a tenancy LIGADA — onde a organização deixa de ser detalhe.
 *
 * Três coisas se provam só aqui: quem já é membro da organização do lote é pulado, o
 * `tenant_id` do lote do `/app` vem do PAINEL mesmo quando o state do Livewire traz outro, e um
 * papel de outro painel forjado no lote é recusado pela validação.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function papelDoLoteTenancy(string $papel = 'panel_user'): int
{
    return (int) Role::findByName($papel)->getKey();
}

it('pula quem ja e membro da organizacao do lote', function (): void {
    Notification::fake();

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    usuario('dentro@example.com')->tenants()->attach($acme);
    usuario('fora@example.com')->tenants()->attach($globex);

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("dentro@example.com\nfora@example.com\nnova@example.com"),
        papelDoLoteTenancy(),
        $acme->getKey(),
        null,
    );

    /*
     * Ser membro de OUTRA organização não é motivo de nada: é justamente o caso de uso da
     * feature — a consultora que atende dois clientes.
     */
    expect($resultado['falhas'])->toBe([['email' => 'dentro@example.com', 'motivo' => 'ja_e_membro']])
        ->and($resultado['enviados'])->toBe(['fora@example.com', 'nova@example.com'])
        ->and(Convite::count())->toBe(2);

    // E sem organização a pergunta não existe: o mesmo endereço não é pulado por nada.
    $semOrganizacao = Convite::convidarEmMassa(
        Convite::separarEmails('dentro@example.com'),
        papelDoLoteTenancy(),
        null,
        null,
    );

    expect($semOrganizacao['enviados'])->toBe(['dentro@example.com'])
        ->and($semOrganizacao['falhas'])->toBeEmpty();
});

it('carimba a organizacao corrente no lote do admin da organizacao', function (): void {
    Notification::fake();

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $ana = usuarioComPapel('admin_app', $acme, 'ana@example.com');
    $ana->tenants()->attach($acme);

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('convidarEmMassa'), [
            'emails'  => "uma@example.com\noutra@example.com",
            'role_id' => papelDoLoteTenancy(),
            // Forjado no state do Livewire: o formulário do /app NÃO tem este campo.
            'tenant_id' => $globex->getKey(),
        ])
        ->assertHasNoFormErrors();

    /*
     * É a barreira 6 da wiki `admin-da-organizacao`, e não vem de graça: `Convite` tem
     * `tenant_id` DENTRO do `$fillable` e não usa `BelongsToTenant`, então o mass assignment
     * aceitaria o valor forjado. Quem sobrescreve é o trait do lote.
     */
    expect(Convite::count())->toBe(2)
        ->and(Convite::pluck('tenant_id')->unique()->all())->toBe([$acme->id])
        ->and(Convite::where('tenant_id', $globex->id)->doesntExist())->toBeTrue();
});

/**
 * O outro lado do carimbo: no `/admin` a organização é ESCOLHIDA, e o campo só existe com tenancy
 * ligada.
 *
 * Este caso é o único que monta a ação do `/admin` com tenancy no ar, e é o que exercita o
 * `Select::make('tenant_id')->relationship('tenant', 'nome')->preload()` dentro de uma modal cujo
 * formulário não tem registro — o resto da suíte do lote roda em single-tenant, onde o campo é
 * invisível.
 */
it('usa a organizacao escolhida no lote do admin', function (): void {
    Notification::fake();

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(App\Filament\Admin\Resources\Convites\Pages\ListConvites::class)
        ->callAction(TestAction::make('convidarEmMassa'), [
            'emails'    => "uma@example.com\noutra@example.com",
            'role_id'   => papelDoLoteTenancy(),
            'tenant_id' => $globex->getKey(),
        ])
        ->assertHasNoFormErrors();

    expect(Convite::count())->toBe(2)
        ->and(Convite::pluck('tenant_id')->unique()->all())->toBe([$globex->id])
        ->and(Convite::where('tenant_id', $acme->id)->doesntExist())->toBeTrue();
});

/**
 * O caso mais perigoso da feature: sem a trava, quem administra UMA organização criaria um lote
 * de trinta `admin` da instalação. A barreira de UX (o Select filtrado) não conta — state de
 * Livewire chega do cliente.
 */
it('recusa papel de outro painel no lote do painel de negocio', function (): void {
    Notification::fake();

    $acme = tenant('Acme', 'acme');
    $ana  = usuarioComPapel('admin_app', $acme, 'ana@example.com');
    $ana->tenants()->attach($acme);

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('convidarEmMassa'), [
            'emails' => "uma@example.com\noutra@example.com",
            // O papel de /admin, forjado.
            'role_id' => papelDoLoteTenancy('admin'),
        ])
        ->assertHasFormErrors(['role_id']);

    expect(Convite::count())->toBe(0);

    // E o caminho legítimo passa: dois convites.
    Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('convidarEmMassa'), [
            'emails'  => "uma@example.com\noutra@example.com",
            'role_id' => papelDoLoteTenancy(),
        ])
        ->assertHasNoFormErrors();

    expect(Convite::count())->toBe(2);
});
