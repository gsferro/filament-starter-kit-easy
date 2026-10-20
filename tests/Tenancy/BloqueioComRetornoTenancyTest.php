<?php

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Desbloqueio da sessão com a volta à página, no painel `/app` COM organização.
 *
 * IDs de CT em `wikis/specs/feat/desbloqueio-volta-a-pagina/…/04-casos-de-teste.md` (G2). O `/app`
 * com tenancy só existe no `TenancyTestCase`; os casos sem tenancy ficam em
 * `tests/Kit/BloqueioDeSessaoTest.php`. A pessoa é `admin_app` (e não `panel_user`) porque o
 * cenário exige que `/app/acme/users` responda 200 para ela.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * A pessoa da organização acme, e a globex, à qual ela não pertence. O GET real ao painel boota o
 * `/app` antes do primeiro componente Livewire (`.ai/rules/testes.md`).
 *
 * @return array{acme: Tenant, globex: Tenant, pessoa: User}
 */
function pessoaDaAcme(): array
{
    $acme   = organizacaoComRegistro('acme');
    $globex = organizacaoComRegistro('globex');

    $pessoa = usuarioComPapel('admin_app', $acme);
    $pessoa->tenants()->attach($acme);

    test()->actingAs($pessoa)->get('/app/acme')->assertOk();

    return compact('acme', 'globex', 'pessoa');
}

it('[CT-02] com tenancy, a sessão travada por inatividade volta à página da organização pedida', function (): void {
    ['pessoa' => $pessoa] = pessoaDaAcme();

    travadaPorInatividadeAoPedir('/app/acme/users', ['Referer' => url('/app/acme')], $pessoa);

    $componente = desbloquearPorSenha('app');

    expect(destinoDoComponente($componente))->toBe(enderecoDe('/app/acme/users'))
        ->and(session('lockscreen'))->toBeFalse();
});

it('[CT-30] com tenancy, sem página a que voltar, o desbloqueio fica no painel app e na organização da sessão', function (): void {
    ['acme' => $acme] = pessoaDaAcme();

    session(['lockscreen' => true]);

    $destino = destinoDoComponente(desbloquearPorSenha('app'));

    expect($destino)->toStartWith(enderecoDe('/app'))
        ->and($destino)->not->toBe(enderecoDe('/'));

    $this->followingRedirects()->get($destino)->assertOk();

    expect(request()->path())->toStartWith('app/acme')
        ->and((string) session('tenant_corrente'))->toBe((string) $acme->getKey());
});

it('[CT-21] o retorno a uma organização da qual a pessoa não participa continua recusado pelo painel', function (): void {
    ['acme' => $acme, 'pessoa' => $pessoa] = pessoaDaAcme();

    travadaPorInatividadeAoPedir('/app/globex/users', [], $pessoa);

    $componente = desbloquearPorSenha('app');
    $destino    = destinoDoComponente($componente);

    $seguidoGlobex = $this->get($destino);
    $seguidoAcme   = $this->get('/app/acme/users');

    expect($destino)->toBe(enderecoDe('/app/globex/users'));

    $seguidoGlobex->assertNotFound();
    $seguidoAcme->assertOk();

    expect((string) session('tenant_corrente'))->toBe((string) $acme->getKey());
});

it('[CT-34] sem página guardada, o desbloqueio volta à organização em que a pessoa estava', function (): void {
    $acme   = organizacaoComRegistro('acme');
    $globex = organizacaoComRegistro('globex');

    $pessoa = usuarioComPapel('admin_app', $acme);
    papelNaOrganizacao($pessoa, 'admin_app', $globex);
    $pessoa->tenants()->attach([$acme->id, $globex->id]);

    $this->actingAs($pessoa)->get('/app/globex/users')->assertOk();

    expect((string) session('tenant_corrente'))->toBe((string) $globex->getKey());

    session(['lockscreen' => true]);
    $this->get(route('lockscreen.app.page'))->assertOk();

    $destino = destinoDoComponente(desbloquearPorSenha('app'));

    expect($destino)->toStartWith(enderecoDe('/app/globex'))
        ->and($destino)->not->toStartWith(enderecoDe('/app/acme'));

    $this->followingRedirects()->get($destino)->assertOk();

    expect(request()->path())->toStartWith('app/globex');
});

it('[CT-37] sem página guardada, a organização da sessão que a pessoa perdeu não é destino: vale a raiz que ela acessa', function (): void {
    $acme   = organizacaoComRegistro('acme');
    $globex = organizacaoComRegistro('globex');

    $pessoa = usuarioComPapel('admin_app', $acme);
    papelNaOrganizacao($pessoa, 'admin_app', $globex);
    $pessoa->tenants()->attach([$acme->id, $globex->id]);

    $this->actingAs($pessoa)->get('/app/globex/users')->assertOk();

    expect((string) session('tenant_corrente'))->toBe((string) $globex->getKey());

    session(['lockscreen' => true]);
    $this->get(route('lockscreen.app.page'))->assertOk();

    // Removida de globex pelo caminho de papéis do kit (pivô de papéis) e pelo vínculo de organização.
    DB::table(pivotDePapeis())
        ->where('model_id', $pessoa->getKey())
        ->where('team_id', $globex->getKey())
        ->delete();
    $pessoa->tenants()->detach($globex->id);
    $pessoa->unsetRelation('roles');
    $pessoa->unsetRelation('tenants');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $destino = destinoDoComponente(desbloquearPorSenha('app'));

    expect($destino)->not->toStartWith(enderecoDe('/app/globex'));

    $this->followingRedirects()->get($destino)->assertOk();

    expect(request()->path())->toStartWith('app/acme');
});
