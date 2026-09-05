<?php

use App\Filament\Admin\Resources\Users\UserResource as UserResourceDoAdmin;
use App\Filament\App\Resources\Users\Pages\CreateUser;
use App\Filament\App\Resources\Users\Pages\EditUser;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A fronteira do `admin_app`: quem governa a instalação não existe para o painel da organização.
 *
 * IDs de CT em `wikis/specs/feat/admin-app-nao-alcanca-master-global/…/04-casos-de-teste.md`.
 *
 * O `/app` já recortava por organização e já travava a concessão de papel de outro painel. O que
 * faltava era o ALVO: o `TenantsSeeder` vincula o `master_global` a toda organização, e o
 * `admin_app` abria a ficha dele e trocava a senha. "Governa a instalação" é ter papel de painel
 * sem tenancy (`roles.painel` nulo ou ≠ `app`) no contexto global — a mesma definição de
 * `User::canAccessPanel()`, e não uma lista de nomes.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * A organização com gente de fora dentro — onze pessoas, uma por célula da matriz papel × contexto.
 *
 * Cada linha existe para matar uma implementação errada específica (ver o `04`): Ada e Ivo matam
 * "só master_global"; Aldo (papel criado aqui, `painel = infra`) mata a lista de nomes; Leo e Rui
 * matam "ignora o contexto" e "contexto ≠ corrente"; Nina mata "qualquer papel no global"; Gil com
 * papel misto e Ana (em CT-08) matam a exceção para papel de app ou para si mesma; Nino é o vazio.
 *
 * Visíveis na Acme para a admin_app: Ana, Beto, Leo, Rui, Nina, Nino — **6**.
 * Não governam a instalação (predicado, sem olhar organização): os seis mais Bruno — **7**.
 *
 * @return array<string, Tenant|User>
 */
function organizacaoComGenteDeForaDentro(): array
{
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    Role::create(['name' => 'auditor', 'guard_name' => 'web', 'painel' => 'infra']);

    $ana   = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $beto  = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    $gil   = papelNaOrganizacao(papelNaOrganizacao(usuario('gil@example.com'), 'master_global'), 'panel_user', $acme);
    $ada   = papelNaOrganizacao(usuario('ada@example.com'), 'admin');
    $ivo   = papelNaOrganizacao(usuario('ivo@example.com'), 'infra');
    $aldo  = papelNaOrganizacao(usuario('aldo@example.com'), 'auditor');
    $leo   = papelNaOrganizacao(usuario('leo@example.com'), 'admin', $acme);
    $rui   = papelNaOrganizacao(usuario('rui@example.com'), 'admin', $globex);
    $nina  = papelNaOrganizacao(usuario('nina@example.com'), 'panel_user');
    $nino  = usuario('nino@example.com');
    $bruno = papelNaOrganizacao(usuario('bruno@example.com'), 'admin_app', $globex);

    foreach ([$ana, $beto, $gil, $ada, $ivo, $aldo, $leo, $rui, $nina, $nino] as $pessoa) {
        $pessoa->tenants()->attach($acme);
    }

    $rui->tenants()->attach($globex);
    $bruno->tenants()->attach($globex);

    return compact('acme', 'globex', 'ana', 'beto', 'gil', 'ada', 'ivo', 'aldo', 'leo', 'rui', 'nina', 'nino', 'bruno');
}

/*
|--------------------------------------------------------------------------
| R1 — quem governa a instalação não existe para o /app
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — presença E ausência na MESMA carga da tabela. `assertCanNotSeeTableRecords` sozinho
 * passa numa tabela vazia por erro de query engolido; os seis presentes são o controle.
 */
it('[CT-01] lista exatamente quem nao governa a instalacao', function (): void {
    $p = organizacaoComGenteDeForaDentro();

    // A tabela usa o macro `simpleLightbox()`, registrado no boot do plugin — que teste de
    // componente não atravessa. Um request real boota o painel pelo middleware, com rota;
    // `noPainelBootado()` sem rota derruba o Breezy (`route()->parameter()` em null).
    $this->actingAs($p['ana'])->get('/app/acme/users')->assertOk();
    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$p['ana'], $p['beto'], $p['leo'], $p['rui'], $p['nina'], $p['nino']])
        ->assertCanNotSeeTableRecords([$p['gil'], $p['ada'], $p['ivo'], $p['aldo'], $p['bruno']]);
});

/**
 * CT-02 — a URL direta decide pelo alvo: 404 (não 403 — "não pode NUNCA ver") para quem governa,
 * e para quem não governa a ficha DELE, não qualquer 200. Quem barra é o route binding, que
 * consulta `getEloquentQuery()` — uma implementação que recortasse só a `table()` abriria aqui.
 */
it('[CT-02] a url direta responde 404 para quem governa e abre a ficha certa para quem nao governa', function (string $pessoa, int $status): void {
    $p = organizacaoComGenteDeForaDentro();

    $resposta = $this->actingAs($p['ana'])->get("/app/acme/users/{$p[$pessoa]->uuid}/edit");

    $resposta->assertStatus($status);

    if ($status === 200) {
        $resposta->assertSee($p[$pessoa]->email);
    } else {
        $resposta->assertDontSee($p[$pessoa]->email);
    }
})->with([
    'gil, master_global com papel misto'  => ['gil', 404],
    'aldo, papel de instalacao novo'      => ['aldo', 404],
    'beto, usuario comum'                 => ['beto', 200],
]);

/** CT-03 — a busca global do resource passa por `getEloquentQuery()`, e o dado do ⌘K sai dela. */
it('[CT-03] a busca global nao encontra quem governa a instalacao', function (string $termo, array $esperados): void {
    $p = organizacaoComGenteDeForaDentro();

    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    $titulos = UserResource::getGlobalSearchResults($termo)->map(fn ($resultado) => $resultado->title)->all();

    expect($titulos)->toBe($esperados);
})->with([
    'gil (governa)'         => ['gil@example.com', []],
    'ada (governa)'         => ['ada@example.com', []],
    'beto (nao governa)'    => ['beto@example.com', ['Usuário']],
    'rui (admin na Globex)' => ['rui@example.com', ['Usuário']],
]);

/** CT-07 — o número vem da fixture (6), não de outro cenário. */
it('[CT-07] o badge do menu conta so quem nao governa a instalacao', function (): void {
    $p = organizacaoComGenteDeForaDentro();

    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    expect(UserResource::getEloquentQuery()->count())->toBe(6)
        ->and((string) UserResource::getNavigationBadge())->toContain('6');
});

/**
 * CT-08 — a regra não tem exceção para quem executa (ADR-01): a admin_app que também é `admin` da
 * instalação some da própria listagem. Um `orWhere('id', auth()->id())` passaria em todo o resto.
 */
it('[CT-08] a administradora que tambem governa a instalacao some da propria listagem', function (): void {
    $p = organizacaoComGenteDeForaDentro();

    papelNaOrganizacao($p['ana'], 'admin');

    $this->actingAs($p['ana'])->get('/app/acme/users')->assertOk();
    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$p['beto'], $p['leo'], $p['rui'], $p['nina'], $p['nino']])
        ->assertCanNotSeeTableRecords([$p['ana']]);
});

/*
|--------------------------------------------------------------------------
| R2 — a edição é recusada por qualquer caminho, sem mudar a conta, com registro
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — a resposta de autorização chamada DIRETO, sem passar pela listagem: é ela que
 * `EditRecord::mount()` e a `EditAction` da tabela lêem. Chaves nomeadas no contexto do log
 * (ids trocados reprovam) e canal explícito; nas linhas permitidas, nenhum warning.
 */
it('[CT-04] a resposta de autorizacao de edicao decide pelo alvo e registra so a recusa', function (string $pessoa, bool $permitida): void {
    $p = organizacaoComGenteDeForaDentro();

    $canal = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    $resposta = UserResource::getEditAuthorizationResponse($p[$pessoa]);

    expect($resposta->allowed())->toBe($permitida);

    if ($permitida) {
        $canal->shouldNotHaveReceived('warning');
    } else {
        $canal->shouldHaveReceived('warning')
            ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[UserResource@getEditAuthorizationResponse]')
                && $contexto['alvo_id'] === $p[$pessoa]->id
                && $contexto['executor_id'] === $p['ana']->id
                && $contexto['tenant_id'] === $p['acme']->id);
    }
})->with([
    'gil, master_global com papel misto'  => ['gil', false],
    'ada, admin da instalacao'            => ['ada', false],
    'aldo, papel de instalacao novo'      => ['aldo', false],
    'leo, admin so dentro da acme'        => ['leo', true],
    'nina, panel_user no contexto global' => ['nina', true],
    'beto, usuario comum'                 => ['beto', true],
]);

/**
 * CT-10 — o bypass mais fácil de escrever: montar o componente de edição com o alvo em mãos e
 * pedir gravação. O oráculo é o DADO: hash da senha e e-mail inalterados. 404 (a query recortou no
 * binding) e 403 (a resposta negou) são ambos legítimos; 200 com gravação, não.
 */
it('[CT-10] montar a tela de edicao com o alvo em maos nao altera a conta de quem governa', function (string $pessoa): void {
    $p    = organizacaoComGenteDeForaDentro();
    $alvo = $p[$pessoa];

    $hashAntes  = $alvo->password;
    $emailAntes = $alvo->email;

    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    $barrado = null;

    try {
        Livewire::test(EditUser::class, ['record' => $alvo->uuid])
            ->fillForm(['email' => 'sequestrado@example.com', 'password' => 'senha-nova-12345'])
            ->call('save');
    } catch (ModelNotFoundException|NotFoundHttpException|AuthorizationException|HttpException $e) {
        $barrado = $e;
    }

    expect($barrado)->not->toBeNull('o componente montou com o alvo e aceitou a gravação');

    $alvo->refresh();

    expect($alvo->password)->toBe($hashAntes)
        ->and($alvo->email)->toBe($emailAntes);
})->with(['gil', 'ada']);

/*
|--------------------------------------------------------------------------
| R3 — predicado e recorte dizem a mesma coisa
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — o predicado avaliado sobre as ONZE pessoas (não sobre a saída do scope, que seria
 * circular) e o recorte sobre a base inteira. O segundo `expect` impede as duas formas de estarem
 * erradas juntas: Leo, Rui, Nina e Aldo são quem decide.
 */
it('[CT-05] o recorte da query e o predicado concordam em toda a fixture', function (): void {
    $p = organizacaoComGenteDeForaDentro();

    $peloPredicado = User::all()->reject(fn (User $u): bool => $u->governaAInstalacao())->pluck('email')->sort()->values()->all();
    $peloRecorte   = User::query()->queNaoGovernamAInstalacao()->pluck('email')->sort()->values()->all();

    expect($peloRecorte)->toBe($peloPredicado)
        ->and($peloRecorte)->toBe([
            'ana@example.com', 'beto@example.com', 'bruno@example.com', 'leo@example.com',
            'nina@example.com', 'nino@example.com', 'rui@example.com',
        ]);
});

/*
|--------------------------------------------------------------------------
| R4 — o admin_app continua vendo, criando e gravando os seus
|--------------------------------------------------------------------------
*/

/** CT-06 — uma tela aberta não é uma tela que grava: a edição do usuário comum acontece. */
it('[CT-06] a admin_app edita um usuario comum e a gravacao acontece', function (): void {
    $p = organizacaoComGenteDeForaDentro();

    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    Livewire::test(EditUser::class, ['record' => $p['beto']->uuid])
        ->fillForm(['name' => 'Beto Silva'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($p['beto']->fresh()->name)->toBe('Beto Silva')
        ->and($p['beto']->tenants()->whereKey($p['acme']->id)->exists())->toBeTrue();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $p['beto']->id,
        'role_id'  => Role::findByName('panel_user')->getKey(),
        'team_id'  => $p['acme']->id,
    ]);
});

/** CT-09 — verbo irmão não herda evidência: criar também precisa gravar e aparecer. */
it('[CT-09] a admin_app cria um usuario comum e ele aparece na listagem', function (): void {
    $p = organizacaoComGenteDeForaDentro();

    noPainelDa($p['acme']);
    $this->actingAs($p['ana']);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name'     => 'Dora',
            'email'    => 'dora@example.com',
            'password' => 'password1234',
            'roles'    => [Role::findByName('panel_user')->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $dora = User::where('email', 'dora@example.com')->firstOrFail();

    $this->assertDatabaseHas('tenant_user', ['user_id' => $dora->id, 'tenant_id' => $p['acme']->id]);
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $dora->id,
        'role_id'  => Role::findByName('panel_user')->getKey(),
        'team_id'  => $p['acme']->id,
    ]);

    expect(UserResource::getEloquentQuery()->pluck('email')->all())->toContain('dora@example.com');
});

/*
|--------------------------------------------------------------------------
| R5 — a regra é do /app: fora dele ninguém some
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — o recorte é scope local, nunca global: um `addGlobalScope` no `User` passaria em todos
 * os casos acima e apagaria o `master_global` do `/admin` e do guard de autenticação.
 */
it('[CT-11] fora do painel da organizacao quem governa a instalacao continua existindo', function (): void {
    $p = organizacaoComGenteDeForaDentro();

    expect(User::query()->count())->toBe(11);

    noPainelBootado('admin');
    $this->actingAs($p['gil']);

    expect(UserResourceDoAdmin::getEloquentQuery()->pluck('email')->all())
        ->toContain('gil@example.com', 'ada@example.com', 'ivo@example.com', 'aldo@example.com');
});
