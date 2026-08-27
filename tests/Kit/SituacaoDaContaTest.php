<?php

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Pages\Auth\TelaLogin;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Status ativo/inativo e exclusão lógica de usuário — a negação, o aviso e as ações.
 *
 * Os IDs de CT são os do `04-casos-de-teste.md` da wiki
 * `wikis/specs/feat/status-e-exclusao-logica-de-usuario/`. O login social está em
 * `LoginSocialContaIndisponivelTest`; a lixeira e a restauração em `LixeiraTest`.
 *
 * O que mais importa saber para mexer aqui: a negação é `User::canAccessPanel()`; a tela de login
 * só EXPLICA. Por isso R1 prova o model e R2/R4 provam o formulário — são duas barreiras, e um
 * caso que passasse só pela tela ficaria verde com a barreira do model removida (CT-34 é o que
 * pega isso: o middleware do painel pergunta ao model, não à tela).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Um usuário com o papel do painel e o estado pedido. */
function usuarioNoEstado(string $papel, string $email, bool $ativo = true): User
{
    $user = usuarioDoKit($papel, $email);

    if (! $ativo) {
        $user->forceFill(['ativo' => false])->save();
    }

    return $user;
}

/**
 * Envia o formulário de login do painel — como um visitante, sem sessão anterior.
 *
 * O `logout()` não é higiene: `Login::mount()` redireciona quem já está autenticado, e o
 * `fillForm()` morreria sem formulário. Cada chamada é um visitante novo.
 */
function enviarLogin(string $painel, string $email, string $senha): Testable
{
    Filament::auth()->logout();
    Filament::setCurrentPanel($painel);

    return Livewire::test(TelaLogin::class)
        ->fillForm(['email' => $email, 'password' => $senha])
        ->call('authenticate');
}

/** A mensagem genérica do Filament — a mesma para "não existe" e "senha errada". */
function erroGenericoDeLogin(): string
{
    return __('filament-panels::auth/pages/login.messages.failed');
}

/*
|--------------------------------------------------------------------------
| R1 — conta inativa ou excluída não abre painel nenhum, em qualquer caminho
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a linha do `master_global` é a que mata "guarda depois do atalho": o atalho devolve
 * `true` sem consultar mais nada.
 */
it('[CT-01] nega o painel do próprio papel a quem está inativo', function (string $papel, string $painel): void {
    $user = usuarioNoEstado($papel, "{$papel}@example.com", ativo: false);

    expect($user->canAccessPanel(Filament::getPanel($painel)))->toBeFalse();
})->with([
    ['panel_user', 'app'],
    ['admin', 'admin'],
    ['infra', 'infra'],
    ['master_global', 'admin'],
])->group('kit');

/** CT-02 — controle: ativo com o papel entra. Mata a guarda invertida. */
it('[CT-02] deixa entrar quem está ativo com o papel do painel', function (): void {
    expect(usuarioNoEstado('admin', 'admin@example.com')->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
})->group('kit');

/** CT-03 — excluído reconstruído com `withTrashed()` continua negado: `ativo` sozinho não basta. */
it('[CT-03] nega o painel a um excluído mesmo carregado com trashed', function (): void {
    $user = usuarioNoEstado('admin', 'admin@example.com');
    $user->delete();

    $trashed = User::withTrashed()->findOrFail($user->getKey());

    expect($trashed->ativo)->toBeTrue('Controle: o excluído continua "ativo" na coluna — é o trashed() que nega.')
        ->and($trashed->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
})->group('kit');

/**
 * CT-34 — quem já estava dentro é barrado no request seguinte. O controle (200 antes) é o que
 * separa "negado por inativo" de "negado por qualquer permissão do Shield".
 */
it('[CT-34] barra no request seguinte quem é desativado com a sessão aberta', function (): void {
    $admin = usuarioNoEstado('admin', 'admin@example.com');

    $this->actingAs($admin)->get('/admin')->assertOk();

    $admin->forceFill(['ativo' => false])->save();

    $this->get('/admin')->assertForbidden();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — inativo com a senha certa cai no aviso; senha errada recebe o genérico
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — nos TRÊS painéis (achado da revisão adversarial: a tela registrada só no /admin
 * passaria num caso que só usasse o /admin). O conteúdo é lido no GET seguinte, na MESMA
 * sessão, sem `withSession()` — é a cadeia redirect → flash → página que se prova.
 */
it('[CT-04] leva o inativo com a senha certa ao aviso, sem sessão, em cada painel', function (string $papel, string $painel): void {
    usuarioNoEstado($papel, "{$papel}@example.com", ativo: false);

    enviarLogin($painel, "{$papel}@example.com", 'password')
        ->assertHasNoFormErrors()
        ->assertRedirect(route('auth.conta-indisponivel'));

    $this->assertGuest();

    $this->get(route('auth.conta-indisponivel'))
        ->assertStatus(403)
        ->assertSee('Conta desativada')
        ->assertSee('Entre em contato com o administrador para reativá-la');
})->with([
    ['panel_user', 'app'],
    ['admin', 'admin'],
    ['infra', 'infra'],
])->group('kit');

/**
 * CT-05 — a mensagem é a MESMA do e-mail inexistente (string idêntica), senão o interceptor
 * reabre a enumeração com outra roupa. E a tentativa do inativo com senha errada já fica na trilha:
 * o Filament acha o usuário, a senha falha, e o `Failed` sai com ele.
 */
it('[CT-05] devolve o erro genérico para senha errada em conta inativa e para e-mail inexistente', function (string $arranjo): void {
    $inativo = $arranjo === 'inativo' ? usuarioNoEstado('admin', 'admin@example.com', ativo: false) : null;
    $email   = $inativo?->email ?? 'ninguem@example.com';

    $tela = enviarLogin('admin', $email, 'errada')
        ->assertHasFormErrors(['email'])
        ->assertNoRedirect();

    expect($tela->errors()->first('data.email'))->toBe(erroGenericoDeLogin());

    $this->assertGuest();

    if ($inativo instanceof User) {
        expect($inativo->authentications()->where('login_successful', false)->count())->toBe(1);
    }
})->with(['inativo', 'inexistente'])->group('kit');

/** CT-06 — regressão: o ativo entra pelo formulário, e a trilha dele não tem falha. */
it('[CT-06] deixa o ativo entrar pelo formulário', function (): void {
    $admin = usuarioNoEstado('admin', 'admin@example.com');

    enviarLogin('admin', 'admin@example.com', 'password')->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($admin);

    expect($admin->authentications()->where('login_successful', false)->count())->toBe(0);
})->group('kit');

/** CT-07 — a página não existe "solta": sem aviso na sessão, volta para a raiz. */
it('[CT-07] redireciona para a raiz quem abre o aviso sem aviso pendente', function (): void {
    $this->get(route('auth.conta-indisponivel'))->assertRedirect('/');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — a tentativa do inativo fica registrada, no log e na trilha, uma vez
|--------------------------------------------------------------------------
*/

/** CT-08 — o channel distingue o caso e identifica o usuário. */
it('[CT-08] registra a tentativa do inativo no channel de autenticação com o motivo', function (): void {
    $inativo = usuarioNoEstado('admin', 'admin@example.com', ativo: false);
    $canal   = espiarAutenticacao();

    enviarLogin('admin', 'admin@example.com', 'password');

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto = []): bool => str_starts_with($mensagem, '[TelaLogin@authenticate]')
            && ($contexto['motivo'] ?? null) === 'conta_inativa'
            && ($contexto['user_id'] ?? null) === $inativo->getKey())
        ->once();
})->group('kit');

/**
 * CT-09 — EXATAMENTE uma linha malsucedida: o Filament já dispara `Failed` com o usuário quando
 * `canAccessPanel()` nega; um segundo disparo à mão duplicaria a linha.
 */
it('[CT-09] grava exatamente uma falha na trilha de acessos do inativo', function (): void {
    $inativo = usuarioNoEstado('admin', 'admin@example.com', ativo: false);

    enviarLogin('admin', 'admin@example.com', 'password');

    expect($inativo->authentications()->count())->toBe(1)
        ->and($inativo->authentications()->where('login_successful', false)->count())->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — excluído com a senha certa vê a data; senha errada recebe o genérico
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — a data é a da EXCLUSÃO, não a de hoje: o relógio anda oito dias entre o `delete()` e o
 * login, e o aviso não pode mostrar o "agora" (achado da revisão adversarial).
 */
it('[CT-10] mostra ao excluído com a senha certa a data em que foi excluído', function (): void {
    $user = usuarioNoEstado('admin', 'admin@example.com');

    $this->travelTo(Carbon::parse('2026-08-12 10:00:00'));
    $user->delete();
    $this->travelTo(Carbon::parse('2026-08-20 10:00:00'));

    enviarLogin('admin', 'admin@example.com', 'password')
        ->assertRedirect(route('auth.conta-indisponivel'));

    $this->assertGuest();

    $this->get(route('auth.conta-indisponivel'))
        ->assertStatus(403)
        ->assertSee('Conta excluída')
        ->assertSee('12/08/2026')
        ->assertDontSee('20/08/2026')
        ->assertSee('Entre em contato com o administrador para restaurá-la');
})->group('kit');

/** CT-11 — senha errada em conta excluída: o genérico, idêntico ao do inexistente. */
it('[CT-11] devolve o erro genérico para senha errada em conta excluída', function (): void {
    usuarioNoEstado('admin', 'admin@example.com')->delete();

    $tela = enviarLogin('admin', 'admin@example.com', 'errada')
        ->assertHasFormErrors(['email'])
        ->assertNoRedirect();

    expect($tela->errors()->first('data.email'))->toBe(erroGenericoDeLogin());

    $this->assertGuest();
})->group('kit');

/**
 * CT-12 — o alvo está excluído E inativo: excluído vence, e a trilha ganha a linha que o Filament
 * não gravaria (ele não acha o excluído — o `Failed` sai à mão, uma vez).
 */
it('[CT-12] registra a tentativa do excluído com o motivo e uma linha de falha na trilha', function (): void {
    $user = usuarioNoEstado('admin', 'admin@example.com', ativo: false);
    $user->delete();

    $canal = espiarAutenticacao();

    enviarLogin('admin', 'admin@example.com', 'password');

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto = []): bool => str_starts_with($mensagem, '[TelaLogin@authenticate]')
            && ($contexto['motivo'] ?? null) === 'conta_excluida'
            && ($contexto['user_id'] ?? null) === $user->getKey())
        ->once();

    expect($user->authentications()->count())->toBe(1)
        ->and($user->authentications()->where('login_successful', false)->count())->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — desativar e reativar: permissão própria, idempotência, guardas
|--------------------------------------------------------------------------
*/

/** Um administrador autenticado com a tela de usuários do /admin pronta para ser montada. */
function administradorNaListaDeUsuarios(): User
{
    $admin = usuarioNoEstado('admin', 'admin@example.com');

    test()->actingAs($admin);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    return $admin;
}

/** CT-18 — desativar pela ação, com log de quem fez. */
it('[CT-18] desativa um usuário pela lista do /admin e registra executor e alvo', function (): void {
    $admin = administradorNaListaDeUsuarios();
    $alvo  = usuario('alvo@example.com');
    $canal = espiarAutenticacao();

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('desativar')->table($alvo))
        ->assertNotified('Usuário desativado');

    expect($alvo->fresh()?->ativo)->toBeFalse();

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto = []): bool => str_starts_with($mensagem, '[User@desativar]')
            && ($contexto['alvo_id'] ?? null) === $alvo->getKey()
            && ($contexto['executor_id'] ?? null) === $admin->getKey())
        ->once();
})->group('kit');

/**
 * CT-19 — sem a permissão a ação existe na definição e fica oculta; `edit` na mesma linha
 * continua visível (mata "a permissão foi posta na ação errada").
 */
it('[CT-19] esconde a ação de quem perdeu a permissão dela, e o alvo não muda', function (string $permissao, string $acao, bool $ativo): void {
    semAPermissao('admin', $permissao);
    administradorNaListaDeUsuarios();
    $alvo = usuarioNoEstado('panel_user', 'alvo@example.com', ativo: $ativo);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertActionExists(TestAction::make($acao)->table($alvo))
        ->assertActionHidden(TestAction::make($acao)->table($alvo))
        ->assertActionVisible(TestAction::make('edit')->table($alvo));

    expect($alvo->fresh()?->ativo)->toBe($ativo);
})->with([
    'desativar' => ['Desativar:User', 'desativar', true],
    'reativar'  => ['Reativar:User', 'reativar', false],
])->group('kit');

/** CT-20 — reativar pela ação devolve o acesso ao painel do papel. */
it('[CT-20] reativa um usuário pela lista do /admin e ele volta a poder entrar', function (): void {
    administradorNaListaDeUsuarios();
    $alvo = usuarioNoEstado('admin', 'alvo@example.com', ativo: false);

    expect($alvo->canAccessPanel(Filament::getPanel('admin')))->toBeFalse('Controle.');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('reativar')->table($alvo))
        ->assertNotified('Usuário reativado');

    $alvo = $alvo->fresh();

    expect($alvo?->ativo)->toBeTrue()
        ->and($alvo?->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
})->group('kit');

/** CT-21 — a transição repetida é no-op: um `info` só, por transição. */
it('[CT-21] não repete efeito nem log na transição repetida', function (bool $ativoInicial, string $metodo, bool $ativoFinal): void {
    $alvo  = usuarioNoEstado('panel_user', 'alvo@example.com', ativo: $ativoInicial);
    $canal = espiarAutenticacao();

    $alvo->{$metodo}();
    $alvo->{$metodo}();

    expect($alvo->fresh()?->ativo)->toBe($ativoFinal);

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem): bool => str_starts_with($mensagem, "[User@{$metodo}]"))
        ->once();
})->with([
    'desativar duas vezes' => [true, 'desativar', false],
    'reativar duas vezes'  => [false, 'reativar', true],
])->group('kit');

/** CT-22 — a própria conta: a guarda vale na chamada direta, e a conta continua ativa. */
it('[CT-22] recusa desativar a própria conta, direto no model', function (): void {
    $admin = usuarioNoEstado('admin', 'admin@example.com');
    $this->actingAs($admin);

    expect(fn () => $admin->desativar())->toThrow(RuntimeException::class)
        ->and($admin->fresh()?->ativo)->toBeTrue();
})->group('kit');

/** CT-37 — e a tela espelha a guarda: oculta na própria linha, visível na do outro. */
it('[CT-37] esconde a ação de desativar na própria linha e a mostra na linha de outro ativo', function (): void {
    $admin = administradorNaListaDeUsuarios();
    $outro = usuario('outro@example.com');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('desativar')->table($admin))
        ->assertActionVisible(TestAction::make('desativar')->table($outro));
})->group('kit');

/**
 * CT-23 — o último `master_global` ATIVO. A terceira linha mata "contar o inativo como reserva";
 * a primeira mata "esqueceu o `whereKeyNot` e nunca é o último".
 */
it('[CT-23] recusa desativar o último master_global ativo e aceita quando há outro', function (int $masters, bool $segundoInativo, bool $recusa): void {
    $this->actingAs(usuarioNoEstado('admin', 'admin@example.com'));

    $primeiro = usuarioNoEstado('master_global', 'master1@example.com');

    if ($masters === 2) {
        usuarioNoEstado('master_global', 'master2@example.com', ativo: ! $segundoInativo);
    }

    if ($recusa) {
        expect(fn () => $primeiro->desativar())->toThrow(RuntimeException::class);
    } else {
        $primeiro->desativar();
    }

    expect($primeiro->fresh()?->ativo)->toBe($recusa);
})->with([
    'um master'                      => [1, false, true],
    'dois masters ativos'            => [2, false, false],
    'dois masters, o outro inativo'  => [2, true, true],
])->group('kit');

/** CT-24 — as permissões existem e nascem no papel certo, e em nenhum outro. */
it('[CT-24] cria Desativar:User e Reativar:User e as entrega só ao admin', function (string $permissao): void {
    expect(Permission::query()->where('name', $permissao)->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(papelDoKit('admin')->hasPermissionTo($permissao))->toBeTrue()
        ->and(papelDoKit('panel_user')->hasPermissionTo($permissao))->toBeFalse()
        ->and(papelDoKit('infra')->hasPermissionTo($permissao))->toBeFalse();
})->with(['Desativar:User', 'Reativar:User'])->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — a tela mostra o estado e filtra por ele
|--------------------------------------------------------------------------
*/

/** CT-30 — partição exaustiva do estado exibido, lida na célula da linha (não no HTML inteiro). */
it('[CT-30] mostra Pendente, Inativo ou Ativo na coluna de situação', function (string $estado, string $rotulo): void {
    administradorNaListaDeUsuarios();
    $alvo = usuario('alvo@example.com');

    match ($estado) {
        'pendente' => $alvo->forceFill(['aprovacao_pendente' => true])->save(),
        'inativo'  => $alvo->forceFill(['ativo' => false])->save(),
        default    => null,
    };

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertTableColumnStateSet('situacao', $rotulo, $alvo);
})->with([
    ['pendente', 'Pendente'],
    ['inativo', 'Inativo'],
    ['ativo', 'Ativo'],
])->group('kit');

/** CT-31 — o filtro de inativos mostra só os inativos. */
it('[CT-31] filtra só os inativos', function (): void {
    administradorNaListaDeUsuarios();
    $ativo   = usuario('ativo@example.com');
    $inativo = usuarioNoEstado('panel_user', 'inativo@example.com', ativo: false);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->filterTable('inativos')
        ->assertCanSeeTableRecords([$inativo])
        ->assertCanNotSeeTableRecords([$ativo]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| Taxonomia — mass assignment
|--------------------------------------------------------------------------
*/

/** CT-35 — `ativo` não se escreve por atribuição em massa; a transição é por método. */
it('[CT-35] ignora ativo na atribuição em massa', function (): void {
    $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'ativo' => false]);

    expect($user->fresh()?->ativo)->toBeTrue()
        ->and(in_array('ativo', (new User)->getFillable(), true))->toBeFalse();
})->group('kit');
