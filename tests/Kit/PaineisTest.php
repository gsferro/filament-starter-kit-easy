<?php

use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\App\Resources\Convites\ConviteResource;
use App\Models\User;
use App\Support\Paineis;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;
use Livewire\Livewire;
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

/**
 * CT-11 — gravar papel preserva o `painel` e NÃO cria permission fantasma.
 *
 * O `04-casos-de-teste.md` desta wiki chama isto de "a falha silenciosa mais provável de todo
 * este plano", e até aqui era o único CT dela sem teste — `grep -rn 'CreateRole\|EditRole'
 * tests/` voltava vazio (QA-01 do `06-relatorio-qa.md`).
 *
 * O mecanismo: o formulário de papel do Shield trata cada chave do estado como uma permission
 * a sincronizar. A coluna `painel` é campo do kit, não permission — se ela não estiver nas
 * listas de exclusão de `CreateRole::mutateFormDataBeforeCreate()` (`:28` e `:34-37`) e do
 * `EditRole` (`:36` e `:42-45`), o `afterCreate` do Shield cria uma permission **chamada
 * `app`**. Nada falha: o papel grava, a tela responde, e a matriz de permissões ganha uma
 * linha que não é de ninguém.
 *
 * Por isso o caso assere as duas coisas juntas — o `painel` gravado E a ausência da
 * permission. Asserir só a primeira deixaria o defeito passar inteiro.
 */
it('salva o painel do papel sem virar permission', function (): void {
    // As tres paginas sao do /admin, e teste de componente Livewire nao atravessa o
    // middleware que define e BOOTA o painel: sem isto o painel corrente e o default e a
    // pagina morre em "Plugin [filament-shield] is not registered for panel [infra]".
    noPainelBootado('admin');
    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'app'])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(config('permission.table_names.roles', 'roles'), [
        'name'   => 'suporte',
        'painel' => 'app',
    ]);

    expect(Permission::where('name', 'app')->exists())->toBeFalse();
});

/**
 * A metade `EditRole` do CT-11 — e ela não é redundante.
 *
 * São duas classes com duas listas de exclusão **separadas** (`CreateRole:28,34-37` e
 * `EditRole:36,42-45`). Corrigir uma e esquecer a outra é o cenário realista, e produz o
 * defeito só na edição: quem criar o papel pela tela de criação nunca vê nada errado.
 *
 * Sem `assertRedirect()`: tela de edição do Filament não redireciona depois de salvar.
 */
it('salva o painel do papel na edicao sem virar permission', function (): void {
    // As tres paginas sao do /admin, e teste de componente Livewire nao atravessa o
    // middleware que define e BOOTA o painel: sem isto o painel corrente e o default e a
    // pagina morre em "Plugin [filament-shield] is not registered for panel [infra]".
    noPainelBootado('admin');
    $this->actingAs(usuarioCom('master_global'));

    $papel = Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'app']);

    Livewire::test(EditRole::class, ['record' => $papel->getKey()])
        ->fillForm(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'infra'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(config('permission.table_names.roles', 'roles'), [
        'id'     => $papel->getKey(),
        'painel' => 'infra',
    ]);

    expect(Permission::where('name', 'infra')->exists())->toBeFalse();
});

/**
 * CT-14 — criar usuário sem papel é erro de formulário, não conta órfã.
 *
 * Papel é o que dá acesso a painel (`User::canAccessPanel()` lê `roles.painel`), então
 * usuário sem papel é conta morta: autentica na tela de login e leva 403 nos três painéis.
 * O `->required()` do Select vive em `UserResource:70`; este caso é o que impede alguém de
 * removê-lo por achar que papel é opcional.
 *
 * A segunda asserção importa tanto quanto a primeira: erro de formulário que ainda assim
 * grava o usuário seria pior que nenhum erro.
 */
it('exige papel ao criar usuario', function (): void {
    // As tres paginas sao do /admin, e teste de componente Livewire nao atravessa o
    // middleware que define e BOOTA o painel: sem isto o painel corrente e o default e a
    // pagina morre em "Plugin [filament-shield] is not registered for panel [infra]".
    noPainelBootado('admin');
    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name'     => 'Fulano',
            'email'    => 'fulano@example.com',
            'password' => 'secret1234',
        ])
        ->call('create')
        ->assertHasFormErrors(['roles' => 'required']);

    expect(User::where('email', 'fulano@example.com')->exists())->toBeFalse();
});
