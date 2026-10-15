<?php

use App\Filament\Admin\Resources\Users\UserResource as UserResourceDoAdmin;
use App\Filament\App\Pages\HubDoNegocio;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\App\Resources\Users\UserResource as UserResourceDoApp;
use App\Filament\Pages\DashboardClassico;
use App\Models\Role;
use App\Models\User;
use App\Support\Paineis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

/**
 * O dock de atalhos — a suíte multi-organização (`permission.teams` ligado).
 *
 * Os IDs de CT são os de `wikis/specs/main/dock-de-acoes-rapidas/04-casos-de-teste.md`. Os helpers
 * (`ligarDock()`, `concederDock()`, `chaveDeAtalho()`, `gravarAtalhos()`, `dockNaPagina()`) moram em
 * `tests/Pest.php`, compartilhados com `tests/Kit/DockDeAtalhosTest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| R1 — a permissão vale na organização em que foi concedida
|--------------------------------------------------------------------------
*/

it('[CT-04] a permissão vale na organização em que foi concedida', function (string $organizacao, bool $contem): void {
    config(['kit.hub' => true]);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    // dani: panel_user na Acme e admin_app na Globex. O papel da Globex perde o dock — a única
    // variável entre as duas organizações é a permissão do papel daquela organização.
    $dani = usuarioComPapel('panel_user', $acme, 'dani@example.com');
    papelNaOrganizacao($dani, 'admin_app', $globex);
    $dani->tenants()->attach([$acme->id, $globex->id]);

    concederDock('panel_user', 'admin_app');
    semAPermissao('admin_app', 'Usar:Dock');

    gravarAtalhos($dani, [
        chaveDeAtalho('app', HubDoNegocio::class, $acme->getKey()),
        chaveDeAtalho('app', HubDoNegocio::class, $globex->getKey()),
    ]);

    ligarDock(['app']);

    $alvo = $organizacao === 'Acme' ? $acme : $globex;

    $this->actingAs($dani);

    // O /app só boota por um request real, antes do primeiro Livewire::test() (`.ai/rules/testes.md`).
    $this->get("/app/{$alvo->slug}")->assertOk();

    noPainelDa($alvo);
    Filament::setCurrentPanel('app');

    $dock = dockNaPagina('app', $alvo);
    $url  = HubDoNegocio::getUrl(panel: 'app', tenant: $alvo);

    if ($contem) {
        $dock->assertSee($url, false);
    } else {
        $dock->assertDontSee($url, false);
    }
})->with([
    'Globex, papel sem a permissão' => ['Globex', false],
    'Acme, controle positivo'       => ['Acme', true],
]);

/*
|--------------------------------------------------------------------------
| R7 — o catálogo aberto numa organização respeita papel global e vínculo
|--------------------------------------------------------------------------
*/

it('[CT-22] o catálogo aberto numa organização respeita papel global e vínculo', function (string $persona, bool $inclui): void {
    $globex = null;

    if ($persona === 'usuario de duasOrganizacoes (admin global)') {
        ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario] = duasOrganizacoes();
    } else {
        $acme   = tenant('Acme', 'acme');
        $globex = tenant('Globex', 'globex');

        $usuario = usuarioComPapel('panel_user', $acme, 'ana@example.com');
        $usuario->tenants()->attach([$acme->id]);
    }

    concederDock('panel_user', 'admin');
    ligarDock(['app']);

    $this->actingAs($usuario);

    // O /app só boota por um request real, antes do primeiro Livewire::test() (`.ai/rules/testes.md`).
    $this->get("/app/{$acme->slug}")->assertOk();

    noPainelDa($acme);
    Filament::setCurrentPanel('app');

    $opcoes = array_keys(opcoesDoSlideover(dockNaPagina('app', $acme)));

    if ($inclui) {
        expect($opcoes)->toContain(chaveDeAtalho('admin', UserResourceDoAdmin::class));

        return;
    }

    // Controle: o catálogo da Acme não está vazio (o vínculo dela vale).
    expect($opcoes)->toContain(chaveDeAtalho('app', DashboardClassico::class, $acme->getKey()));

    $daGlobex = array_filter($opcoes, static fn (string $chave): bool => str_starts_with($chave, 'app|') && str_ends_with($chave, '|'.$globex->getKey()));

    expect($daGlobex)->toBe([]);
})->with([
    'admin global, lista de usuários do /admin' => ['usuario de duasOrganizacoes (admin global)', true],
    'só da Acme, telas do /app na Globex'       => ['usuária só da Acme', false],
]);

/*
|--------------------------------------------------------------------------
| R21 — o rótulo traz o painel (e a organização) antes do grupo e da tela
|--------------------------------------------------------------------------
*/

it('[CT-50] destinos homônimos se distinguem pelo painel e pela organização no rótulo', function (): void {
    $acme   = tenant('Acme', 'acme');
    $pessoa = usuarioComPapel('admin_app', $acme, 'pessoa@example.com');

    papelNaOrganizacao($pessoa, 'admin');
    $pessoa->tenants()->attach([$acme->id]);

    concederDock('admin', 'admin_app');
    ligarDock(['app']);

    $this->actingAs($pessoa);

    // O /app só boota por um request real, antes do primeiro Livewire::test() (`.ai/rules/testes.md`).
    $this->get("/app/{$acme->slug}")->assertOk();

    noPainelDa($acme);
    Filament::setCurrentPanel('app');

    $opcoes = opcoesDoSlideover(dockNaPagina('app', $acme));

    $chaveDoAdmin = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $chaveDoApp   = chaveDeAtalho('app', UserResourceDoApp::class, $acme->getKey());

    expect($opcoes)->toHaveKeys([$chaveDoAdmin, $chaveDoApp]);

    $rotuloDoAdmin = $opcoes[$chaveDoAdmin];
    $rotuloDoApp   = $opcoes[$chaveDoApp];

    expect($rotuloDoAdmin)->not->toBe($rotuloDoApp);

    /**
     * Os segmentos esperados, na ordem, como o kit os exibe (lidos do painel e do Resource).
     *
     * @param  list<string>  $segmentos
     */
    $naOrdem = function (string $rotulo, array $segmentos): void {
        $anterior = -1;

        foreach (array_filter($segmentos, static fn (string $segmento): bool => $segmento !== '') as $segmento) {
            $posicao = mb_strpos($rotulo, $segmento);

            expect($posicao)->not->toBeFalse("o rótulo [{$rotulo}] não contém [{$segmento}]")
                ->and($posicao)->toBeGreaterThan($anterior, "[{$segmento}] fora de ordem em [{$rotulo}]");

            $anterior = $posicao;
        }
    };

    $grupoDoAdmin = UserResourceDoAdmin::getNavigationGroup();
    $grupoDoApp   = UserResourceDoApp::getNavigationGroup();

    $naOrdem($rotuloDoAdmin, [
        Paineis::rotulo(Filament::getPanel('admin')),
        is_string($grupoDoAdmin) ? $grupoDoAdmin : '',
        (string) UserResourceDoAdmin::getNavigationLabel(),
    ]);

    $naOrdem($rotuloDoApp, [
        Paineis::rotulo(Filament::getPanel('app')),
        'Acme',
        is_string($grupoDoApp) ? $grupoDoApp : '',
        (string) UserResourceDoApp::getNavigationLabel(),
    ]);
});

it('[CT-26] o favorito gravado pelo slideover na Globex abre nela de qualquer painel', function (string $onde): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    abrirDockTenancy('app', $globex)->callAction('gerenciarAtalhos', ['atalhos' => [$chaves['PG']]])->assertHasNoErrors();
    expect(atalhosGravados($usuario))->toBe([$chaves['PG']]);

    $dock = abrirDockTenancy($onde, $onde === 'app' ? $acme : null);

    $dock->assertSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $globex), false)
        ->assertDontSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $acme), false);
})->with(['outra organização corrente' => ['app'], 'admin sem organização' => ['admin']]);

it('[CT-27] favorito sem acesso à organização some por HTTP e volta após revínculo', function (string $situacao, bool $contem): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA'], $chaves['PG']]);
    if ($situacao === 'papel') {
        noPainelDa($globex);
        $usuario->unsetRelation('roles')->syncRoles(['admin_app']);
        semAPermissao('admin_app', 'ViewAny:Projeto');
    } else {
        $usuario->tenants()->detach($globex);
        if ($situacao === 'revinculo') {
            fronteiraDeRequest();
            $intermediario = $this->get('/app/acme')->assertOk();
            $intermediario->assertDontSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $globex), false);
            expect(atalhosGravados($usuario))->toBe([$chaves['PA'], $chaves['PG']]);
            $usuario->tenants()->attach($globex);
        }
    }
    fronteiraDeRequest();

    $resposta = $this->get('/app/acme')->assertOk();

    $resposta->assertSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $acme), false);
    $url = ProjetoResource::getUrl('index', panel: 'app', tenant: $globex);
    if ($contem) {
        $resposta->assertSee($url, false);
    } else {
        $resposta->assertDontSee($url, false);
    }
    expect(atalhosGravados($usuario))->toBe([$chaves['PA'], $chaves['PG']]);
})->with(['sem vínculo' => ['desvinculado', false], 'papel sem listar projetos' => ['papel', false], '2-switch observado' => ['revinculo', true]]);

it('[CT-28] slideover recusa chave de organização sem vínculo ou inexistente', function (string $particao): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    $usuario->tenants()->detach($globex);
    gravarAtalhos($usuario, [$chaves['PA']]);
    $id = match ($particao) {
        'sem vínculo'     => $globex->getKey(),
        'sem organização' => null,
        'inexistente'     => $globex->getKey() + 9999,
    };
    $chave = chaveDeAtalho('app', ProjetoResource::class, $id);
    $dock  = abrirDockTenancy('app', $acme);

    $dock->set('mountedActions', [['name' => 'gerenciarAtalhos', 'arguments' => [], 'context' => [], 'data' => ['atalhos' => [$chaves['PA'], $chave]]]])->call('callMountedAction');

    expect(atalhosGravados($usuario))->toBe([$chaves['PA']]);
})->with(['IDOR' => ['sem vínculo'], 'sem organização' => ['sem organização'], 'inexistente' => ['inexistente']]);

class PaginaComFalhaDeAcessoDoDock extends Page
{
    public static ?string $painelAvaliado = null;

    public static function canAccess(): bool
    {
        static::$painelAvaliado = Filament::getCurrentPanel()->getId();
        throw new RuntimeException('Falha de acesso deliberada do CT-29');
    }
}

it('[CT-29] avaliação de favoritos preserva painel organização team e permissão do request', function (string $favorito): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');
    $dani   = usuarioComPapel('panel_user', $acme, 'dani@example.com');
    papelNaOrganizacao($dani, 'admin_app', $globex);
    papelNaOrganizacao($dani, 'admin');
    $dani->tenants()->attach([$acme->id, $globex->id]);
    concederDock('panel_user', 'admin_app', 'admin');
    papelDoKit('admin_app')->givePermissionTo('ViewAny:User');
    papelDoKit('admin')->givePermissionTo('ViewAny:User');
    semAPermissao('panel_user', 'ViewAny:User');
    ligarDock(['app', 'admin']);
    $this->actingAs($dani);
    $chave = match ($favorito) {
        'outro painel'      => chaveDeAtalho('admin', UserResourceDoAdmin::class),
        'outra organização' => chaveDeAtalho('app', UserResourceDoApp::class, $globex->id),
        'excluída'          => chaveDeAtalho('app', ProjetoResource::class, $globex->id),
        'exceção'           => chaveDeAtalho('falhadock', PaginaComFalhaDeAcessoDoDock::class),
    };
    if ($favorito === 'exceção') {
        painelRegistradoEmTeste('falhadock')->pages([PaginaComFalhaDeAcessoDoDock::class]);
        Role::create(['name' => 'falhadock', 'guard_name' => 'web', 'painel' => 'falhadock']);
        papelNaOrganizacao($dani, 'falhadock');
        PaginaComFalhaDeAcessoDoDock::$painelAvaliado = null;
    }
    gravarAtalhos($dani, [$chave]);
    if ($favorito === 'excluída') {
        $globex->delete();
    }
    noPainelDa($acme);
    $dani->unsetRelation('roles')->unsetRelation('permissions');
    expect($dani->can('ViewAny:User'))->toBeFalse();

    try {
        abrirDockTenancy('app', $acme)->html();
    } catch (RuntimeException $erro) {
        expect($favorito)->toBe('exceção');
        expect($erro->getMessage())->toBe('Falha de acesso deliberada do CT-29');
    }

    expect(Filament::getCurrentPanel()->getId())->toBe('app');
    expect(Filament::getTenant()->getKey())->toBe($acme->getKey());
    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe($acme->getKey());
    expect($dani->can('ViewAny:User'))->toBeFalse();
    if ($favorito === 'exceção') {
        expect(PaginaComFalhaDeAcessoDoDock::$painelAvaliado)->toBe('falhadock');
    }
})->with(['outro painel' => ['outro painel'], 'outra organização' => ['outra organização'], 'organização excluída' => ['excluída'], 'exceção após troca' => ['exceção']]);

it('[CT-43] novas opções do app são só da organização corrente dentro do app', function (string $onde, string $organizacao, bool $inclui): void {
    ['acme' => $acme, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();

    $opcoes = array_keys(opcoesDoSlideover(abrirDockTenancy($onde, $onde === 'app' ? $acme : null)));

    expect($opcoes)->not->toBeEmpty();
    if ($inclui) {
        expect($opcoes)->toContain($chaves[$organizacao]);
    } else {
        expect($opcoes)->not->toContain($chaves[$organizacao]);
    }
})->with(['app Acme inclui Acme' => ['app', 'PA', true], 'app Acme não inclui Globex' => ['app', 'PG', false], 'admin não inclui Acme' => ['admin', 'PA', false], 'admin não inclui Globex' => ['admin', 'PG', false]]);

it('[CT-44] favorito de outra organização sobrevive ao salvar e ocupa vaga no teto', function (string $operacao): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    if ($operacao === 'teto') {
        $painel  = Filament::getPanel('app');
        $paginas = [PaginaTetoDock1::class, PaginaTetoDock2::class, PaginaTetoDock3::class, PaginaTetoDock4::class, PaginaTetoDock5::class, PaginaTetoDock6::class, PaginaTetoDock7::class, PaginaTetoDock8::class, PaginaTetoDock9::class];
        $painel->pages($paginas);
        foreach ($paginas as $indice => $pagina) {
            Route::get('/app/{tenant}/teto-dock-'.$indice, static fn (): string => '')->name($pagina::getRouteName($painel));
        }
        Route::getRoutes()->refreshNameLookups();
        $opcoes = array_keys(opcoesDoSlideover(abrirDockTenancy('app', $acme)));
        $daAcme = array_values(array_filter($opcoes, static fn (string $chave): bool => str_starts_with($chave, 'app|') && str_ends_with($chave, '|'.$acme->getKey()) && $chave !== $chaves['PA']));
        expect(count($daAcme))->toBeGreaterThanOrEqual(9);
        $antes = [...array_slice($daAcme, 0, 9), $chaves['PG']];
    } else {
        $antes = [$chaves['PA'], $chaves['PG']];
    }
    gravarAtalhos($usuario, $antes);
    session()->forget(['filament.notifications', 'filament.claimed_notifications']);
    $dock = abrirDockTenancy('app', $acme, ProjetoResource::class);

    if ($operacao === 'teto') {
        $dock->callAction('adicionarPaginaAtual');

        // Antes do `assertNotified()`, que consome a sessão; o dehydrate move para `claimed_notifications`.
        $avisos = [...session()->get('filament.notifications', []), ...session()->get('filament.claimed_notifications', [])];
        expect(array_filter($avisos, static fn (array $aviso): bool => str_contains(($aviso['title'] ?? '').' '.($aviso['body'] ?? ''), '10')))->not->toBeEmpty();
        $dock->assertNotified();
    } else {
        opcoesDoSlideover($dock);
        $escolhidos = $dock->get('mountedActions.0.data.atalhos');
        $dock->callMountedAction(['atalhos' => $escolhidos])->assertHasNoErrors();
    }

    expect(atalhosGravados($usuario))->toBe($antes);
    $renderizado = abrirDockTenancy('app', $acme);
    $renderizado->assertSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $globex), false);
})->with(['salva sem tocar' => ['salvar'], '10 favoritos recusa o décimo primeiro' => ['teto']]);

it('[CT-51] adicionar página atual grava organização da rota em vez da sessão', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    $this->get('/app/acme')->assertOk();
    noPainelDa($acme);
    expect($usuario->tenants()->first()->getKey())->toBe($acme->getKey());

    abrirDockTenancy('app', $globex, ProjetoResource::class)->callAction('adicionarPaginaAtual')->assertHasNoErrors();

    expect(atalhosGravados($usuario))->toBe([$chaves['PG']]);
    $dock = abrirDockTenancy('app', $acme);
    $dock->assertSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $globex), false)
        ->assertDontSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $acme), false);
});

it('[CT-52] envio aceita favoritos gravados abríveis e recusa novos fora do catálogo corrente', function (string $onde, array $antes, array $envio, array $depois): void {
    ['acme' => $acme, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    $resolver                                                     = static fn (array $nomes): array => array_map(static fn (string $nome): string => $chaves[$nome], $nomes);
    gravarAtalhos($usuario, $resolver($antes));
    $dock = abrirDockTenancy($onde, $onde === 'app' ? $acme : null);

    $dock->set('mountedActions', [['name' => 'gerenciarAtalhos', 'arguments' => [], 'context' => [], 'data' => ['atalhos' => $resolver($envio)]]])->call('callMountedAction');

    expect(atalhosGravados($usuario))->toBe($resolver($depois));
})->with([
    'app recusa PG novo'            => ['app', ['PA'], ['PA', 'PG'], ['PA']],
    'app recusa PG2 novo'           => ['app', ['PA', 'PG'], ['PA', 'PG', 'PG2'], ['PA', 'PG']],
    'app reordena gravados'         => ['app', ['PA', 'PG'], ['PG', 'PA'], ['PG', 'PA']],
    'app remove PG'                 => ['app', ['PA', 'PG'], ['PA'], ['PA']],
    'admin recusa PA novo'          => ['admin', ['A'], ['A', 'PA'], ['A']],
    'admin remove favoritos do app' => ['admin', ['A', 'PA', 'PG'], ['A'], ['A']],
    'admin mantém favoritos do app' => ['admin', ['A', 'PA', 'PG'], ['A', 'PA', 'PG'], ['A', 'PA', 'PG']],
]);

it('[CT-55] escolhidos de qualquer organização têm rótulo inteiro sem ampliar novas opções', function (string $onde, array $nomes): void {
    ['acme' => $acme, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    $gravados                                                     = array_map(static fn (string $nome): string => $chaves[$nome], $nomes);
    gravarAtalhos($usuario, $gravados);
    $dock = abrirDockTenancy($onde, $onde === 'app' ? $acme : null);

    $opcoes = opcoesDoSlideover($dock);

    expect($dock->get('mountedActions.0.data.atalhos'))->toBe($gravados);
    expect($opcoes)->toHaveKey($chaves['PG']);
    $rotulo = $opcoes[$chaves['PG']];
    expect($rotulo)->not->toContain('Acme');
    $grupo     = ProjetoResource::getNavigationGroup();
    $segmentos = [Paineis::rotulo(Filament::getPanel('app')), 'Globex', is_string($grupo) ? $grupo : '', ProjetoResource::getNavigationLabel()];
    $anterior  = -1;
    foreach (array_filter($segmentos) as $segmento) {
        $posicao = mb_strpos($rotulo, $segmento);
        expect($posicao)->not->toBeFalse()->toBeGreaterThan($anterior);
        $anterior = $posicao;
    }
    $novas = array_values(array_diff(array_keys($opcoes), $gravados));
    expect($novas)->not->toContain($chaves['PG2']);
    foreach ($novas as $chave) {
        if (str_starts_with($chave, 'app|')) {
            expect($onde)->toBe('app');
            expect($chave)->toEndWith('|'.$acme->getKey());
        }
    }
    if ($onde === 'admin') {
        expect($novas)->not->toContain($chaves['PA2']);
    }
})->with(['app Acme' => ['app', ['PA', 'PG']], 'admin' => ['admin', ['A', 'PA', 'PG']]]);

it('[CT-58] admin oculta favorito de organização desvinculada sem apagá-lo', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA'], $chaves['PG']]);
    $usuario->tenants()->detach($globex);

    $resposta = $this->get('/admin')->assertOk();

    $resposta->assertSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $acme), false)
        ->assertDontSee(ProjetoResource::getUrl('index', panel: 'app', tenant: $globex), false);
    expect(atalhosGravados($usuario))->toBe([$chaves['PA'], $chaves['PG']]);
});

class PaginaTetoDock1 extends Page {}
class PaginaTetoDock2 extends Page {}
class PaginaTetoDock3 extends Page {}
class PaginaTetoDock4 extends Page {}
class PaginaTetoDock5 extends Page {}
class PaginaTetoDock6 extends Page {}
class PaginaTetoDock7 extends Page {}
class PaginaTetoDock8 extends Page {}
class PaginaTetoDock9 extends Page {}

it('[CT-61] favorito de organização sem acesso fica fora dos escolhidos e das opções', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA'], $chaves['PG']]);
    $usuario->tenants()->detach($globex);
    $dock = abrirDockTenancy('app', $acme);

    $opcoes = array_keys(opcoesDoSlideover($dock));

    expect($opcoes)->toContain($chaves['PA'])->not->toContain($chaves['PG']);
    expect($dock->get('mountedActions.0.data.atalhos'))->toContain($chaves['PA'])->not->toContain($chaves['PG']);
});

it('[CT-62] salvar preserva favorito oculto de organização sem acesso', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA'], $chaves['PG']]);
    $usuario->tenants()->detach($globex);

    abrirDockTenancy('app', $acme)->callAction('gerenciarAtalhos', ['atalhos' => [$chaves['PA']]])->assertHasNoErrors();

    expect(atalhosGravados($usuario))->toHaveCount(2)->toEqualCanonicalizing([$chaves['PA'], $chaves['PG']]);
});

it('[CT-63] mesma tela em outra organização oferece adicionar página atual', function (): void {
    ['globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA']]);

    $dock = abrirDockTenancy('app', $globex, ProjetoResource::class);

    $dock->assertActionExists('adicionarPaginaAtual')->assertActionVisible('adicionarPaginaAtual');
});

it('[CT-64] mesma tela em outra organização grava novo favorito no fim', function (): void {
    ['globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA']]);

    abrirDockTenancy('app', $globex, ProjetoResource::class)->callAction('adicionarPaginaAtual')->assertHasNoErrors();

    expect(atalhosGravados($usuario))->toBe([$chaves['PA'], $chaves['PG']]);
});

use App\Settings\ConfiguracoesDoKit;

it('[CT-72] três favoritos da organização corrente não recarregam papéis nem buscam a organização e custam no máximo uma consulta cada', function (string $afirmacao): void {
    config(['kit.hub' => true]);
    ['acme' => $acme, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    $tres                                                         = [
        $chaves['PA'],
        $chaves['PA2'],
        chaveDeAtalho('app', HubDoNegocio::class, $acme->getKey()),
    ];
    $medir = function (array $favoritos) use ($usuario, $acme): array {
        gravarAtalhos($usuario, $favoritos);
        fronteiraDeRequest();
        app()->forgetInstance(ConfiguracoesDoKit::class);
        noPainelDa($acme);
        $this->actingAs(User::query()->findOrFail($usuario->getKey()));

        return consultasDoGet('/app/'.$acme->slug);
    };
    $medir([]);
    $medir($tres);

    // Como CT-56, as somas simétricas cancelam a deriva linear dos requests no mesmo processo.
    $zeroAntes  = $medir([]);
    $tresDepois = $medir($tres);
    $tresAntes  = $medir($tres);
    $zeroDepois = $medir([]);

    expect($zeroAntes)->not->toBeEmpty();

    if ($afirmacao === 'limite') {
        expect(count($tresDepois) + count($tresAntes))->toBeLessThanOrEqual(count($zeroAntes) + count($zeroDepois) + 6);

        return;
    }

    $proibidas = array_values(array_filter(
        [...$tresDepois, ...$tresAntes],
        static fn (array $consulta): bool => pilhaComCodigoDoDock($consulta['arquivos']) && ($afirmacao === 'papeis'
            ? preg_match('/^\s*select\s+(?!exists\b).*\bfrom\s+[`"\[]?roles[`"\]]?(\s|$)/is', $consulta['sql']) === 1 && str_contains($consulta['sql'], 'model_has_roles')
            : preg_match('/\b(from|into|update|join)\s+[`"\[]?tenants[`"\]]?(\s|$)/i', $consulta['sql']) === 1),
    ));

    expect(array_column($proibidas, 'sql'))->toBe([]);
})->with([
    'nenhuma recarga de papéis pelo dock'    => ['papeis'],
    'nenhuma busca da organização pelo dock' => ['organizacao'],
    'no máximo T + 3 consultas'              => ['limite'],
]);

it('[CT-73] favorito de organização excluída fica fora das opções e dos escolhidos', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA'], $chaves['PG']]);
    $globex->delete();
    $dock = abrirDockTenancy('app', $acme);

    $opcoes = array_keys(opcoesDoSlideover($dock));

    $dock->assertHasNoErrors();
    expect($opcoes)->toContain($chaves['PA'])->not->toContain($chaves['PG']);
    expect($dock->get('mountedActions.0.data.atalhos'))->toContain($chaves['PA'])->not->toContain($chaves['PG']);
});

it('[CT-74] salvar descarta organização excluída e libera sua vaga sem aviso de limite', function (string $celula): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    $envio                                                                             = [$chaves['PA']];
    if ($celula === 'teto') {
        papelNaOrganizacao($usuario, 'admin_app', $acme);
        $painel  = Filament::getPanel('app');
        $paginas = [PaginaTetoDock1::class, PaginaTetoDock2::class, PaginaTetoDock3::class, PaginaTetoDock4::class, PaginaTetoDock5::class, PaginaTetoDock6::class, PaginaTetoDock7::class, PaginaTetoDock8::class, PaginaTetoDock9::class];
        $painel->pages($paginas);
        foreach ($paginas as $indice => $pagina) {
            Route::get('/app/{tenant}/teto-dock-'.$indice, static fn (): string => '')->name($pagina::getRouteName($painel));
        }
        Route::getRoutes()->refreshNameLookups();
        $opcoes = array_keys(opcoesDoSlideover(abrirDockTenancy('app', $acme)));
        $daAcme = array_values(array_filter($opcoes, static fn (string $chave): bool => str_starts_with($chave, 'app|') && str_ends_with($chave, '|'.$acme->getKey())));
        expect(count($daAcme))->toBeGreaterThanOrEqual(10);
        $envio = array_slice($daAcme, 0, 10);
    }
    $antes = [...array_slice($envio, 0, $celula === 'teto' ? 9 : 1), $chaves['PG']];
    gravarAtalhos($usuario, $antes);
    $globex->delete();
    $dock = abrirDockTenancy('app', $acme);
    session()->forget(['filament.notifications', 'filament.claimed_notifications']);

    $dock->callAction('gerenciarAtalhos', ['atalhos' => $envio])->assertHasNoErrors();

    $avisos = [...session()->get('filament.notifications', []), ...session()->get('filament.claimed_notifications', [])];
    expect(array_filter($avisos, static fn (array $aviso): bool => str_contains(($aviso['title'] ?? '').' '.($aviso['body'] ?? ''), '10')))->toBe([]);
    expect(atalhosGravados($usuario))->toBe($envio);
})->with([
    'descarte ao salvar PA'                          => ['descarte'],
    'nove destinos e excluída permitem dez enviados' => ['teto'],
]);
