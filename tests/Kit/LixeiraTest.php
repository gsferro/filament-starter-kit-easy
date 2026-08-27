<?php

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Pages\Auth\TelaLogin;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\SoftDeletes;
use Livewire\Livewire;
use Promethys\Revive\Concerns\Recyclable;
use Promethys\Revive\Models\RecycleBinItem;
use Promethys\Revive\Pages\RecycleBin;
use Promethys\Revive\RevivePlugin;
use Promethys\Revive\Tables\RecycleBin as RecycleBinTable;

/**
 * Exclusão lógica de usuário e a Lixeira — `wikis/specs/feat/status-e-exclusao-logica-de-usuario/`.
 *
 * O achado que deu origem ao CT-29: a Lixeira (`promethys/revive`) lista `recycle_bin_items`
 * (`vendor/promethys/revive/src/Tables/RecycleBin.php:120-124`), e quem grava a linha ali é o
 * evento `deleted` da trait `Recyclable` (`.../Concerns/Recyclable.php:29-45`). `Projeto` tinha
 * `SoftDeletes` e não tinha a trait — a tela listou vazio da 0.17.0 até esta feature. A rule de
 * `.ai/rules/models.md` dizia metade ("entra em `models()`"); a outra metade agora é este caso.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| R7 — excluir é lógico
|--------------------------------------------------------------------------
*/

/** CT-25 — a DeleteAction do /admin marca a data, esconde das consultas e cria o item da lixeira. */
it('[CT-25] exclui logicamente pela lista do /admin e cria o item da lixeira', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = usuario('alvo@example.com');

    $this->actingAs($admin);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('delete')->table($alvo))
        ->assertNotified();

    $linha = User::withTrashed()->find($alvo->getKey());

    expect($linha)->not->toBeNull('A linha tem de continuar em `users`.')
        ->and($linha->deleted_at)->not->toBeNull()
        ->and(User::query()->comEmail('alvo@example.com')->exists())->toBeFalse('Consulta comum não acha excluído.')
        ->and(RecycleBinItem::query()->where('model_type', $alvo->getMorphClass())->where('model_id', $alvo->getKey())->value('deleted_by'))
        ->toBe($admin->getKey());
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — restaurar pelo /admin e pelo /infra
|--------------------------------------------------------------------------
*/

/**
 * CT-26 — 2-switch: excluir → restaurar → entrar. O oráculo final é a sessão, não `deleted_at`:
 * restaurar sem devolver o acesso passaria num caso que só olhasse a coluna.
 */
it('[CT-26] restaura pela lista do /admin e a pessoa volta a entrar', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = usuarioDoKit('admin', 'alvo@example.com');
    $alvo->delete();

    $this->actingAs($admin);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->filterTable('trashed', false) // só excluídos
        ->assertCanSeeTableRecords([$alvo])
        ->callAction(TestAction::make('restore')->table($alvo))
        ->assertNotified();

    expect($alvo->fresh()?->deleted_at)->toBeNull()
        ->and(RecycleBinItem::query()->where('model_type', $alvo->getMorphClass())->where('model_id', $alvo->getKey())->exists())->toBeFalse();

    Filament::auth()->logout();
    Filament::setCurrentPanel('admin');

    Livewire::test(TelaLogin::class)
        ->fillForm(['email' => 'alvo@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($alvo->fresh());
})->group('kit');

/** CT-27 — sem `Restore:User` a ação existe na tela e fica oculta; o excluído continua excluído. */
it('[CT-27] esconde a restauração de quem perdeu Restore:User', function (): void {
    semAPermissao('admin', 'Restore:User');

    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = usuario('alvo@example.com');
    $alvo->delete();

    $this->actingAs($admin);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->filterTable('trashed', false)
        ->assertActionExists(TestAction::make('restore')->table($alvo))
        ->assertActionHidden(TestAction::make('restore')->table($alvo));

    expect(User::withTrashed()->find($alvo->getKey())?->deleted_at)->not->toBeNull();
})->group('kit');

/**
 * CT-28 — a Lixeira do /infra lista o usuário e o restaura.
 *
 * A ação do Revive se chama `restore` e recebe o `RecycleBinItem`, não o usuário
 * (`vendor/promethys/revive/src/Tables/RecycleBin.php`). `noPainelBootado('infra')` porque a Page
 * lê a configuração do plugin do painel corrente.
 */
it('[CT-28] a Lixeira do /infra lista o usuário excluído e o restaura', function (): void {
    $operador = usuarioDoKit('infra', 'infra@example.com');
    $alvo     = usuarioDoKit('admin', 'alvo@example.com');
    $alvo->delete();

    $item = RecycleBinItem::query()->where('model_type', $alvo->getMorphClass())->where('model_id', $alvo->getKey())->firstOrFail();

    $this->actingAs($operador);
    noPainelDoShield('infra');
    noPainelBootado('infra');

    Livewire::test(RecycleBinTable::class, ['showAllRecords' => true])
        ->loadTable()
        ->assertCanSeeTableRecords([$item])
        ->callAction(TestAction::make('restore')->table($item));

    $restaurado = $alvo->fresh();

    expect($restaurado?->deleted_at)->toBeNull()
        ->and(RecycleBinItem::query()->whereKey($item->getKey())->exists())->toBeFalse()
        ->and($restaurado?->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
})->group('kit');

/**
 * CT-29 — a guarda arquitetural: toda model com `SoftDeletes` usa `Recyclable` E está na Lixeira.
 *
 * Fica vermelho nomeando a model. É a metade que faltava à rule de `.ai/rules/models.md`.
 */
it('[CT-29] toda model com SoftDeletes é Recyclable e está na Lixeira do /infra', function (): void {
    noPainelBootado('infra');

    /** @var RevivePlugin $lixeira */
    $lixeira   = Filament::getPanel('infra')->getPlugin('revive');
    $listadas  = $lixeira->getModels();
    $comSoft   = [];

    foreach (glob(app_path('Models/*.php')) ?: [] as $arquivo) {
        $classe = 'App\\Models\\'.pathinfo($arquivo, PATHINFO_FILENAME);

        if (! class_exists($classe) || ! in_array(SoftDeletes::class, class_uses_recursive($classe), true)) {
            continue;
        }

        $comSoft[] = $classe;

        expect(in_array(Recyclable::class, class_uses_recursive($classe), true))
            ->toBeTrue("{$classe} tem SoftDeletes e não tem Recyclable — a Lixeira nunca vai listá-la.");

        expect(in_array($classe, $listadas, true))
            ->toBeTrue("{$classe} tem SoftDeletes e não está em RevivePlugin::models() do /infra.");
    }

    // Âncora de população: se a varredura não achar ninguém, o caso está medindo o vazio.
    expect(in_array(User::class, $comSoft, true))->toBeTrue();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R10 — e-mail de conta na lixeira fica reservado
|--------------------------------------------------------------------------
*/

/** CT-32 — o `unique` inclui soft-deleted por default: criar de novo é recusado pela validação. */
it('[CT-32] recusa criar usuário com o e-mail de uma conta excluída', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    usuario('alvo@example.com')->delete();

    $this->actingAs($admin);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name'     => 'Outra Pessoa',
            'email'    => 'alvo@example.com',
            'password' => 'password',
            'roles'    => [papelDoKit('admin')->getKey()],
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);

    expect(User::withTrashed()->where('email', 'alvo@example.com')->count())->toBe(1)
        ->and(User::withTrashed()->where('email', 'alvo@example.com')->first()?->deleted_at)->not->toBeNull();
})->group('kit');
