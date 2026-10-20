<?php

use App\Filament\Admin\Pages\HubDeAdministracao;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\UserResource as UserResourceDoAdmin;
use App\Filament\App\Pages\HubDoNegocio;
use App\Filament\App\Resources\Users\UserResource;
use App\Filament\Dock\PluginDoDock;
use App\Filament\Infra\Pages\Dashboard as DashboardDoInfra;
use App\Filament\Pages\DashboardClassico;
use App\Models\User;
use App\Settings\ConfiguracoesDoKit;
use App\Support\DockDeAtalhos;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Filament\View\PanelsRenderHook;
use HoceineEl\QuickActionDock\Enums\DockLayout;
use HoceineEl\QuickActionDock\Livewire\QuickActionDock;
use HoceineEl\QuickActionDock\QuickActionDockPlugin;
use HoceineEl\QuickActionDock\Support\DockContext;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\Kit\fixtures\PaginaDoPainelNovoDoDock;

/**
 * O dock de atalhos (Quick Action Dock) — a suíte single-tenant.
 *
 * Os IDs de CT são os de `wikis/specs/main/dock-de-acoes-rapidas/04-casos-de-teste.md`. Os casos de
 * organização vivem em `tests/Tenancy/DockDeAtalhosTenancyTest.php`.
 *
 * Os helpers compartilhados com a suíte Tenancy (`ligarDock()`, `concederDock()`, `chaveDeAtalho()`,
 * `gravarAtalhos()`, `atalhosGravados()`, `dockNaPagina()`) moram em `tests/Pest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * O dock não oferece a action: ela está oculta ou nem existe no componente.
 *
 * As duas formas servem a "não está disponível". Se a action estiver visível, as duas asserções
 * falham e o caso reprova.
 */
function actionDoDockIndisponivel(Testable $dock, string $acao): void
{
    try {
        $dock->assertActionHidden($acao);
    } catch (Throwable) {
        $dock->assertActionDoesNotExist($acao);
    }
}

/**
 * A chamada que um cliente adulterado faria: o estado `mountedActions` forjado e o
 * `callMountedAction` direto, sem passar por `mountAction()` e sem a checagem de visibilidade que
 * o `callAction()` do helper de teste faz antes de chamar.
 *
 * @param  array<string, mixed>  $dados
 */
function chamarActionDoDockDireto(Testable $dock, string $acao, array $dados = []): Testable
{
    return $dock
        ->set('mountedActions', [['name' => $acao, 'arguments' => [], 'context' => [], 'data' => $dados]])
        ->call('callMountedAction');
}

/** Quantas vezes o HTML tem o link exato para a URL. */
function ocorrenciasDoLink(string $html, string $url): int
{
    return preg_match_all(padraoDoLink($url), $html);
}

/**
 * O link pelo caminho, com qualquer host: a URL esperada pode ter sido gerada num request
 * (`http://localhost:8000`) e o dock renderizado noutro (`dockEmRequestNovo()`, `http://localhost`).
 */
function padraoDoLink(string $url): string
{
    $caminho = (string) (parse_url($url, PHP_URL_PATH) ?? $url);

    return '~href="(?:https?://[^/"]+)?'.preg_quote($caminho, '~').'"~';
}

/*
|--------------------------------------------------------------------------
| R1 — só quem tem Usar:Dock vê e usa o dock, no painel ligado
|--------------------------------------------------------------------------
*/

it('[CT-01] o dock renderiza só com a permissão e o painel ligado', function (string $persona, bool $ligado, bool $contem, bool $gerencia): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole('panel_user');

    concederDock('admin', 'panel_user');

    if ($persona === 'bruno sem Usar:Dock') {
        semAPermissao('admin', 'Usar:Dock');
        semAPermissao('panel_user', 'Usar:Dock');
    }

    $usuario = $persona === 'master_global'
        ? usuarioDoKit('master_global', 'master@example.com')
        : $bruno;

    gravarAtalhos($usuario, [chaveDeAtalho('admin', UserResourceDoAdmin::class)]);

    // Desligado = o /admin fora da lista; os outros painéis ligados isolam a variável.
    ligarDock($ligado ? ['admin'] : ['app', 'infra']);

    noPainelBootado('admin');
    $this->actingAs($usuario);

    $dock = dockNaPagina('admin');
    $url  = UserResourceDoAdmin::getUrl('index', panel: 'admin');

    if ($contem) {
        $dock->assertSee($url, false);
    } else {
        $dock->assertDontSee($url, false);
    }

    if ($gerencia) {
        $dock->assertActionExists('gerenciarAtalhos')->assertActionVisible('gerenciarAtalhos');
    } else {
        actionDoDockIndisponivel($dock, 'gerenciarAtalhos');
    }
})->with([
    'ligado x com permissão'    => ['bruno com Usar:Dock', true, true, true],
    'ligado x sem permissão'    => ['bruno sem Usar:Dock', true, false, false],
    'desligado x com permissão' => ['bruno com Usar:Dock', false, false, false],
    'master_global (controle)'  => ['master_global', true, true, true],
]);

it('[CT-02] a página do painel entrega o dock a quem tem a permissão e não a quem não tem', function (string $persona, bool $ligado, bool $contem): void {
    config(['kit.hub' => true]);

    $comoBruno = $persona === 'bruno, só admin pela semeadura';

    if ($comoBruno) {
        // Semeadura real (RQ-24): `admin` tem a permissão, `panel_user` não — nenhum `concederDock()`.
        $usuario = usuarioDoKit('admin', 'bruno@example.com');
        $usuario->assignRole('panel_user');
    } else {
        $usuario = usuarioDoKit('panel_user', 'ana@example.com');

        concederDock('panel_user');

        if ($persona === 'ana sem o dock') {
            semAPermissao('panel_user', 'Usar:Dock');
        }
    }

    ligarDock($ligado ? ['app'] : ['admin', 'infra']);

    $url   = HubDoNegocio::getUrl(panel: 'app');
    $chave = chaveDeAtalho('app', HubDoNegocio::class);

    $this->actingAs($usuario);

    // O link da tela favorita também existe no menu da página: a prova é o que o favorito ACRESCENTA.
    $antes = $this->get('/app')->assertOk()->getContent();

    gravarAtalhos($usuario, [$chave]);
    fronteiraDeRequest();

    $html = $this->get('/app')->assertOk()->getContent();

    if ($contem) {
        expect($html)->toContain('wire:name="quick-action-dock"')
            ->and(ocorrenciasDoLink($html, $url))->toBeGreaterThan(ocorrenciasDoLink($antes, $url));
    } else {
        expect($html)->not->toContain('wire:name="quick-action-dock"')
            ->and(ocorrenciasDoLink($html, $url))->toBe(ocorrenciasDoLink($antes, $url));
    }
})->with([
    'ana com Usar:Dock, ligado'                 => ['ana com Usar:Dock', true, true],
    'ana sem o dock, ligado'                    => ['ana sem o dock', true, false],
    'ana com Usar:Dock, desligado'              => ['ana com Usar:Dock', false, false],
    'bruno, Usar:Dock só no admin, ligado'      => ['bruno, só admin pela semeadura', true, true],
]);

it('[CT-03] sem a permissão, a chamada direta de action do dock não grava nada', function (string $acao, bool $comDados): void {
    config(['kit.hub' => true]);

    $ana = usuarioDoKit('panel_user', 'ana@example.com');

    concederDock('panel_user');
    semAPermissao('panel_user', 'Usar:Dock');

    $x      = chaveDeAtalho('app', DashboardClassico::class);
    $atual  = chaveDeAtalho('app', HubDoNegocio::class);

    gravarAtalhos($ana, [$x]);
    ligarDock(['app']);

    $this->actingAs($ana);

    // O /app só boota por um request real, antes do primeiro Livewire::test() (`.ai/rules/testes.md`).
    $this->get('/app')->assertOk();
    Filament::setCurrentPanel('app');

    $dock = dockNaPagina('app', pagina: HubDoNegocio::class);

    chamarActionDoDockDireto($dock, $acao, $comDados ? ['atalhos' => [$x, $atual]] : []);

    expect(atalhosGravados($ana))->toBe([$x]);
})->with([
    'gerenciarAtalhos'     => ['gerenciarAtalhos', true],
    'adicionarPaginaAtual' => ['adicionarPaginaAtual', false],
]);

it('[CT-05] action fora da lista do dock não executa nada', function (mixed $nome): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    concederDock('admin');

    $x = chaveDeAtalho('admin', UserResourceDoAdmin::class);

    gravarAtalhos($bruno, [$x]);
    ligarDock(['admin']);

    noPainelBootado('admin');
    $this->actingAs($bruno);

    $dock = dockNaPagina('admin');

    $dock->call('mountAction', $nome)->assertOk();

    expect(atalhosGravados($bruno))->toBe([$x]);
})->with([
    'nome fora da lista'           => ['apagarTudo'],
    'índice de atalho inexistente' => ['atalho_99'],
    'tipo errado (array)'          => [['gerenciarAtalhos', 'x']],
]);

/*
|--------------------------------------------------------------------------
| R2 — nenhum painel nasce ligado; a lista gravada decide
|--------------------------------------------------------------------------
*/

it('[CT-06] a configuração de fábrica não liga o dock em painel nenhum', function (): void {
    $config = kitConfigCom('KIT_DOCK_PAINEIS', null);

    expect($config['dock']['paineis'])->toBe([])
        ->and(configuracaoGravada('dock_paineis'))->toBe([]);
});

it('[CT-07] a lista gravada no settings decide em que painel o dock renderiza', function (array $lista, string $painel, bool $contem): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole(['panel_user', 'infra']);

    concederDock('admin', 'panel_user', 'infra');

    $url = UserResourceDoAdmin::getUrl('index', panel: 'admin');

    gravarAtalhos($bruno, [chaveDeAtalho('admin', UserResourceDoAdmin::class)]);

    // O caminho real settings -> config, como o boot faria.
    gravarConfiguracao('dock_paineis', $lista);
    alinharConfiguracoesDoKit();

    $this->actingAs($bruno);

    if ($painel === 'app') {
        // O /app só boota por um request real (`.ai/rules/testes.md`).
        $this->get('/app')->assertOk();
        Filament::setCurrentPanel('app');
    } else {
        noPainelBootado($painel);
    }

    $dock = dockNaPagina($painel);

    if ($contem) {
        $dock->assertSee($url, false);
    } else {
        $dock->assertDontSee($url, false);
    }
})->with([
    'vazia, admin'             => [[], 'admin', false],
    'vazia, app'               => [[], 'app', false],
    'vazia, infra'             => [[], 'infra', false],
    'um painel, dentro'        => [['app'], 'app', true],
    'um painel, fora'          => [['app'], 'admin', false],
    'dois painéis, o segundo'  => [['admin', 'infra'], 'infra', true],
    'dois painéis, fora'       => [['admin', 'infra'], 'app', false],
    'id inexistente'           => [['financeiro'], 'app', false],
]);

/*
|--------------------------------------------------------------------------
| R14 — conceder o dock pela tela de papéis faz o dock aparecer
|--------------------------------------------------------------------------
*/

it('[CT-59] conceder o dock pela tela de papéis faz o dock aparecer para quem tem o papel', function (): void {
    config(['kit.hub' => true]);

    $ana = usuarioDoKit('panel_user', 'ana@example.com');

    // Partida: ana do papel `panel_user` SEM a permissão (a única variável é a concessão).
    concederDock('panel_user');
    semAPermissao('panel_user', 'Usar:Dock');

    ligarDock(['app']);

    $url   = HubDoNegocio::getUrl(panel: 'app');
    $chave = chaveDeAtalho('app', HubDoNegocio::class);

    gravarAtalhos($ana, [$chave]);

    $this->actingAs($ana);
    $antes = $this->get('/app')->assertOk()->getContent();

    expect($antes)->not->toContain('wire:name="quick-action-dock"');

    // O administrador marca Usar:Dock no papel pela tela de papéis e salva.
    $papel = papelDoKit('panel_user');

    $this->actingAs(usuarioCom('master_global'));
    noPainelDoShield('admin');
    noPainelBootado('admin');

    Livewire::test(EditRole::class, ['record' => $papel->getRouteKey()])
        ->fillForm([
            'name'                   => 'panel_user',
            'guard_name'             => 'web',
            'painel'                 => 'app',
            // As duas custom permissions que o papel já tinha seguem marcadas, mais a do dock.
            'custom_permissions_tab' => ['Aceitar:Convite', 'Recusar:Convite', 'Usar:Dock'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($papel->fresh()->hasPermissionTo('Usar:Dock'))->toBeTrue();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    fronteiraDeRequest();

    $this->actingAs($ana->fresh());

    $html = $this->get('/app')->assertOk()->getContent();

    expect($html)->toContain('wire:name="quick-action-dock"')
        ->and(ocorrenciasDoLink($html, $url))->toBeGreaterThan(ocorrenciasDoLink($antes, $url));
});

/*
|--------------------------------------------------------------------------
| R13 — decidir se o dock aparece e como aparece não acrescenta consulta ao banco
|--------------------------------------------------------------------------
*/

/**
 * Executa `$acao` registrando cada consulta ao banco com os arquivos da pilha em que ela nasceu.
 *
 * O `DB::listen()` não tem como ser removido: o listener fica no container e, depois da medida,
 * `$ativo` o desliga. A pilha é lida DENTRO do listener, que roda no ponto da consulta.
 *
 * @return list<array{sql: string, arquivos: list<string>}>
 */
function consultasDuranteACaptura(Closure $acao): array
{
    $consultas = [];
    $ativo     = true;

    DB::listen(function ($consulta) use (&$consultas, &$ativo): void {
        if (! $ativo) {
            return;
        }

        $consultas[] = [
            'sql'      => $consulta->sql,
            'arquivos' => array_column(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 'file'),
        ];
    });

    try {
        $acao();
    } finally {
        $ativo = false;
    }

    return $consultas;
}

/** A consulta lê ou grava a tabela `settings` (e não uma tabela que só a cita no nome). */
function consultaAoSettings(string $sql): bool
{
    return preg_match('/\b(from|into|update|join)\s+[`"\[]?settings[`"\]]?(\s|$)/i', $sql) === 1;
}

/**
 * Um request novo para o estado do container: o objeto de settings não fica resolvido de uma
 * medida para a outra, senão a segunda leria de memória o que a primeira leu do banco.
 */
function novoRequestDoDock(): void
{
    fronteiraDeRequest();
    app()->forgetInstance(ConfiguracoesDoKit::class);
}

/**
 * Abre a página inicial do /admin como o usuário e devolve as consultas feitas, com o dock
 * ligado ou desligado.
 *
 * @return list<array{sql: string, arquivos: list<string>}>
 */
function consultasDaPaginaInicialDoAdmin(bool $dockLigado): array
{
    ligarDock($dockLigado ? ['admin'] : ['app', 'infra']);
    novoRequestDoDock();

    // Em produção o usuário autenticado vem do banco com todas as colunas; o que a fixture criou
    // em memória não traz `dock_atalhos`, e o dock leria a coluna com uma consulta que o request
    // real não faz.
    test()->actingAs(User::query()->findOrFail(auth()->id()));

    return consultasDuranteACaptura(fn () => test()->get('/admin')->assertOk());
}

/**
 * O arranjo de R13: bruno com `Usar:Dock`, `$quantosFavoritos` favoritos e layout próprio gravado.
 */
function brunoDoDockComLayoutProprio(int $quantosFavoritos): void
{
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole('panel_user');

    concederDock('admin', 'panel_user');

    $favoritos = array_slice([
        chaveDeAtalho('admin', UserResourceDoAdmin::class),
        chaveDeAtalho('admin', DashboardClassico::class),
        chaveDeAtalho('admin', EditRole::class),
    ], 0, $quantosFavoritos);

    gravarAtalhos($bruno, $favoritos);
    DB::table('users')->where('id', $bruno->getKey())->update(['dock_layout' => 'bar']);

    test()->actingAs($bruno);
}

it('[CT-35] o dock não consulta a tabela de settings para decidir nem para desenhar', function (bool $ligado): void {
    brunoDoDockComLayoutProprio(3);

    // Aquecimento: a primeira abertura da página aquece caches que não são do dock.
    consultasDaPaginaInicialDoAdmin($ligado);

    $consultas = consultasDaPaginaInicialDoAdmin($ligado);

    // Controle positivo: o request de fato foi medido.
    expect($consultas)->not->toBeEmpty();

    $doDock = array_values(array_filter(
        $consultas,
        static fn (array $consulta): bool => consultaAoSettings($consulta['sql']) && pilhaComCodigoDoDock($consulta['arquivos']),
    ));

    expect(array_column($doDock, 'sql'))->toBe([]);
})->with([
    'ligado (decidir + como aparece)' => [true],
    'desligado (só decidir)'          => [false],
]);

it('[CT-36] ligar o dock não acrescenta consulta à tabela de settings', function (): void {
    brunoDoDockComLayoutProprio(3);

    $contarSettings = static fn (array $consultas): int => count(array_filter(
        $consultas,
        static fn (array $consulta): bool => consultaAoSettings($consulta['sql']),
    ));

    // Aquecimento: a página já foi aberta uma vez antes das medidas.
    consultasDaPaginaInicialDoAdmin(true);

    // As duas ordens: o que a primeira medida aquece não pode favorecer um lado só.
    $desligadoAntes  = $contarSettings(consultasDaPaginaInicialDoAdmin(false));
    $ligadoDepois    = $contarSettings(consultasDaPaginaInicialDoAdmin(true));
    $ligadoAntes     = $contarSettings(consultasDaPaginaInicialDoAdmin(true));
    $desligadoDepois = $contarSettings(consultasDaPaginaInicialDoAdmin(false));

    expect($ligadoDepois)->toBe($desligadoAntes)
        ->and($ligadoAntes)->toBe($desligadoDepois);
});

it('[CT-56] ligar o dock não acrescenta consulta nenhuma ao banco', function (): void {
    brunoDoDockComLayoutProprio(0);

    // Aquecimento: a página já foi aberta uma vez antes das medidas.
    consultasDaPaginaInicialDoAdmin(true);

    // Ordem desligado, ligado, ligado, desligado. Medido: dentro de UM processo de teste cada
    // request seguinte faz +6 consultas (o total sobe 34, 40, 46... com o dock fora da conta,
    // sem relação com ele — em produção cada request é um processo novo). Uma deriva linear de
    // `d` por request soma `(1 + 2) d` nos dois ligados e `(0 + 3) d` nos dois desligados: a
    // igualdade das SOMAS é a de "exatamente T" com a deriva cancelada, e o dock que acrescenta
    // `k` consultas por request desequilibra a soma em `2k`.
    $desligadoAntes  = count(consultasDaPaginaInicialDoAdmin(false));
    $ligadoDepois    = count(consultasDaPaginaInicialDoAdmin(true));
    $ligadoAntes     = count(consultasDaPaginaInicialDoAdmin(true));
    $desligadoDepois = count(consultasDaPaginaInicialDoAdmin(false));

    expect($desligadoAntes)->toBeGreaterThan(0)
        ->and($ligadoDepois + $ligadoAntes)->toBe($desligadoAntes + $desligadoDepois);
});

it('[CT-65] o painel com o dock desligado consulta a settings tanto quanto um painel sem o plugin', function (): void {
    brunoDoDockComLayoutProprio(0);

    painelRegistradoEmTeste('semdock');

    // Dock desligado no /admin, que mantém o plugin registrado.
    ligarDock(['app', 'infra']);

    // O `ViewManager` guarda os ganchos de todo painel bootado: sem renová-lo, o gancho do painel
    // anterior contaminaria a medida do seguinte.
    $bootarERenderizar = static function (string $painel): int {
        novoRequestDoDock();
        app()->forgetInstance(ViewManager::class);
        Facade::clearResolvedInstance(ViewManager::class);

        return count(array_filter(
            consultasDuranteACaptura(function () use ($painel): void {
                Filament::setCurrentPanel($painel);
                Filament::bootCurrentPanel();

                (string) FilamentView::renderHook(PanelsRenderHook::BODY_END);
            }),
            static fn (array $consulta): bool => consultaAoSettings($consulta['sql']),
        ));
    };

    // Aquecimento: os dois painéis bootados e renderizados uma vez antes das medidas.
    $bootarERenderizar('semdock');
    $bootarERenderizar('admin');

    // As duas ordens.
    $semPluginAntes  = $bootarERenderizar('semdock');
    $comPlugin       = $bootarERenderizar('admin');
    $semPluginDepois = $bootarERenderizar('semdock');

    expect($comPlugin)->toBe($semPluginAntes)
        ->and($semPluginDepois)->toBe($semPluginAntes);
});

/*
|--------------------------------------------------------------------------
| R5, R6, R7 — favoritos por usuário, gravação pelo slideover e catálogo
|--------------------------------------------------------------------------
*/

/** A URL que o dock abre para o destino: a lista do Resource ou a página avulsa. */
function urlDoDestinoDoDock(string $painel, string $destino): string
{
    return is_subclass_of($destino, Resource::class)
        ? $destino::getUrl('index', panel: $painel)
        : $destino::getUrl(panel: $painel);
}

/**
 * Destinos que o usuário abre de verdade, em qualquer painel (só para ARRANJAR favoritos válidos
 * em volume — o que o dock mostra continua sendo medido pelo HTML).
 *
 * @return list<array{chave: string, url: string}>
 */
function destinosAbriveisDoDock(User $usuario): array
{
    $destinos = [];

    foreach (Filament::getPanels() as $painel) {
        foreach ([...$painel->getResources(), ...$painel->getPages()] as $destino) {
            $chave = chaveDeAtalho($painel->getId(), $destino);

            if (DockDeAtalhos::podeAbrir($usuario, $chave)) {
                $destinos[] = ['chave' => $chave, 'url' => urlDoDestinoDoDock($painel->getId(), $destino)];
            }
        }
    }

    return $destinos;
}

/**
 * O componente montado num request NOVO: o dock memoiza os favoritos visíveis por `Request` e,
 * dentro do processo de teste, o request é um só — em produção, ler depois de gravar é outro request.
 */
function dockEmRequestNovo(string $painel, string $pagina = DashboardClassico::class): Testable
{
    app()->instance('request', Request::create('/'));

    return dockNaPagina($painel, pagina: $pagina);
}

it('[CT-13] o dock de um usuário não mostra o favorito de outro', function (): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $carla = usuarioDoKit('admin', 'carla@example.com');

    concederDock('admin');

    $urlDeUsuarios = UserResourceDoAdmin::getUrl('index', panel: 'admin');
    $urlDePapeis   = RoleResource::getUrl('index', panel: 'admin');

    gravarAtalhos($bruno, [chaveDeAtalho('admin', UserResourceDoAdmin::class)]);
    gravarAtalhos($carla, [chaveDeAtalho('admin', RoleResource::class)]);

    ligarDock(['admin']);
    noPainelBootado('admin');

    // carla renderiza primeiro, no mesmo processo (controle positivo: a lista dela aparece).
    $this->actingAs($carla);
    $paraCarla = dockNaPagina('admin')->html();

    expect(ocorrenciasDoLink($paraCarla, $urlDePapeis))->toBe(1);

    $this->actingAs($bruno);
    $paraBruno = dockNaPagina('admin')->html();

    expect(ocorrenciasDoLink($paraBruno, $urlDeUsuarios))->toBe(1)
        ->and(ocorrenciasDoLink($paraBruno, $urlDePapeis))->toBe(0);
});

it('[CT-14] o dock aparece com 0 a 10 favoritos e sempre oferece o gerenciamento', function (?int $quantos, int $esperados): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole(['panel_user', 'infra']);

    concederDock('admin', 'panel_user', 'infra');

    ligarDock(['admin']);
    noPainelBootado('admin');
    $this->actingAs($bruno);

    $destinos = destinosAbriveisDoDock($bruno);

    // Arranjo: bruno abre os 10 destinos que a linha do teto precisa.
    expect(count($destinos))->toBeGreaterThanOrEqual(10);

    $escolhidos = $quantos === null ? [] : array_slice($destinos, 0, $quantos);

    if ($quantos !== null) {
        gravarAtalhos($bruno, array_column($escolhidos, 'chave'));
    }

    $dock = dockNaPagina('admin');
    $html = $dock->html();

    $links = array_sum(array_map(
        static fn (array $destino): int => ocorrenciasDoLink($html, $destino['url']),
        $escolhidos,
    ));

    expect($links)->toBe($esperados);

    $dock->assertActionExists('gerenciarAtalhos')->assertActionVisible('gerenciarAtalhos');
})->with([
    'nunca gravados (nulo)' => [null, 0],
    'lista vazia []'        => [0, 0],
    '1 destino'             => [1, 1],
    '10 destinos'           => [10, 10],
]);

it('[CT-15] o usuário grava dois destinos do catálogo pelo slideover, na ordem', function (): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    concederDock('admin');

    $a = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $b = chaveDeAtalho('admin', DashboardClassico::class);

    ligarDock(['admin']);
    noPainelBootado('admin');
    $this->actingAs($bruno);

    expect(atalhosGravados($bruno))->toBe([]);

    dockNaPagina('admin')
        ->callAction('gerenciarAtalhos', ['atalhos' => [$a, $b]])
        ->assertHasNoErrors();

    expect(atalhosGravados($bruno))->toBe([$a, $b]);

    $html = dockEmRequestNovo('admin')->html();

    expect(ocorrenciasDoLink($html, UserResourceDoAdmin::getUrl('index', panel: 'admin')))->toBe(1)
        ->and(ocorrenciasDoLink($html, DashboardClassico::getUrl(panel: 'admin')))->toBe(1);
});

it('[CT-16] o slideover regrava a escolha: troca, esvazia ou mantém', function (array $envio, array $gravados): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    concederDock('admin');

    $chaves = [
        'A' => chaveDeAtalho('admin', UserResourceDoAdmin::class),
        'B' => chaveDeAtalho('admin', DashboardClassico::class),
        'C' => chaveDeAtalho('admin', RoleResource::class),
    ];

    $emChaves = static fn (array $nomes): array => array_map(static fn (string $nome): string => $chaves[$nome], $nomes);

    gravarAtalhos($bruno, $emChaves(['A', 'B']));

    ligarDock(['admin']);
    noPainelBootado('admin');
    $this->actingAs($bruno);

    dockNaPagina('admin')
        ->callAction('gerenciarAtalhos', ['atalhos' => $emChaves($envio)])
        ->assertHasNoErrors();

    expect(atalhosGravados($bruno))->toBe($emChaves($gravados));
})->with([
    'troca (remove A, mantém B, acrescenta C)' => [['B', 'C'], ['B', 'C']],
    'remove todos'                             => [[], []],
    'mesma lista (idempotente)'                => [['A', 'B'], ['A', 'B']],
]);

it('[CT-17] envio com chave fora do catálogo é recusado por inteiro', function (mixed $chave, ?string $urlDaChave): void {
    config(['kit.hub' => true]);

    $ana = usuarioDoKit('panel_user', 'ana@example.com');

    concederDock('panel_user');

    $x = chaveDeAtalho('app', HubDoNegocio::class);
    $y = chaveDeAtalho('app', DashboardClassico::class);

    gravarAtalhos($ana, [$x]);
    ligarDock(['app']);

    $this->actingAs($ana);

    // O /app só boota por um request real, antes do primeiro Livewire::test() (`.ai/rules/testes.md`).
    $this->get('/app')->assertOk();
    Filament::setCurrentPanel('app');

    // Controle: Y é de fato um destino do catálogo de ana, diferente de X.
    expect(array_keys(opcoesDoSlideover(dockNaPagina('app'))))->toContain($y);

    dockNaPagina('app')->callAction('gerenciarAtalhos', ['atalhos' => [$y, $chave]]);

    expect(atalhosGravados($ana))->toBe([$x]);

    $html = dockEmRequestNovo('app')->html();

    // Depois da troca de request: o host das URLs segue o request corrente.
    $urlDeX = HubDoNegocio::getUrl(panel: 'app');
    $urlDeY = DashboardClassico::getUrl(panel: 'app');

    expect(ocorrenciasDoLink($html, $urlDeX))->toBe(1)
        ->and(ocorrenciasDoLink($html, $urlDeY))->toBe(0);

    if ($urlDaChave !== null) {
        expect(ocorrenciasDoLink($html, $urlDaChave))->toBe(0);
    }
})->with([
    'URL externa'                         => ['https://exemplo.invalido/x', null],
    'URL interna digitada'                => ['/admin/users', null],
    'destino sem acesso (painel)'         => ['admin|'.UserResourceDoAdmin::class.'|', 'http://localhost/admin/users'],
    'classe inexistente'                  => ['admin|App\Nada\Inexistente|', null],
    'classe que não é tela'               => ['admin|App\Models\User|', null],
    'página de registro'                  => ['admin|'.EditUser::class.'|', null],
    'painel inexistente'                  => ['financeiro|'.UserResourceDoAdmin::class.'|', null],
    'malformada'                          => ['xpto', null],
    'tipo errado (inteiro)'               => [42, null],
    'tipo errado (lista dentro da lista)' => [['a'], null],
]);

it('[CT-18] chave inválida já gravada não aparece nem quebra a página', function (string $chave): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    concederDock('admin');
    ligarDock(['admin']);

    $a   = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $url = UserResourceDoAdmin::getUrl('index', panel: 'admin');

    $this->actingAs($bruno);

    // O link de A também existe no menu da página: a prova é o que o favorito ACRESCENTA.
    $antes = $this->get('/admin')->assertOk()->getContent();

    gravarAtalhos($bruno, [$a, $chave]);
    fronteiraDeRequest();

    $html = $this->get('/admin')->assertOk()->getContent();

    expect(ocorrenciasDoLink($html, $url))->toBeGreaterThan(ocorrenciasDoLink($antes, $url));

    $this->assertStringNotContainsString('exemplo.invalido', $html);
    $this->assertStringNotContainsString('Nada\\Inexistente', $html);
    $this->assertStringNotContainsString('Models\\User|', $html);
})->with([
    'URL externa'           => ['https://exemplo.invalido/x'],
    'classe inexistente'    => ['admin|App\Nada\Inexistente|'],
    'classe que não é tela' => ['admin|App\Models\User|'],
]);

it('[CT-19] os favoritos e o layout do usuário não entram por atribuição em massa', function (array $atributos): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    try {
        $bruno->update($atributos);
    } catch (MassAssignmentException) {
        // Recusar com exceção também é não gravar: o Então é sobre o estado gravado.
    }

    $linha = DB::table('users')->where('id', $bruno->getKey())->first();

    expect(atalhosGravados($bruno))->toBe([])
        ->and($linha->dock_layout)->toBeNull();
})->with([
    'favoritos'      => [['dock_atalhos' => ['https://exemplo.invalido/x']]],
    'layout próprio' => [['dock_layout' => 'xpto']],
]);

it('[CT-20] as opções do slideover seguem o acesso de quem o abre', function (string $persona, string $painel, string $tela, bool $inclui, ?string $semAPermissaoDe): void {
    $usuario = $persona === 'ana'
        ? usuarioDoKit('panel_user', 'ana@example.com')
        : usuarioDoKit('admin', 'bruno@example.com');

    if ($persona !== 'ana') {
        $usuario->assignRole('panel_user');
    }

    concederDock('admin', 'panel_user');

    if ($semAPermissaoDe !== null) {
        semAPermissao('admin', $semAPermissaoDe);
    }

    ligarDock(['admin', 'app']);

    $this->actingAs($usuario);

    if ($painel === 'app') {
        // O /app só boota por um request real (`.ai/rules/testes.md`).
        $this->get('/app')->assertOk();
        Filament::setCurrentPanel('app');
    } else {
        noPainelBootado($painel);
    }

    $opcoes = array_keys(opcoesDoSlideover(dockNaPagina($painel)));

    // Controle: o slideover oferece alguma coisa — a ausência abaixo não é um catálogo vazio.
    expect($opcoes)->not->toBeEmpty();

    if ($inclui) {
        expect($opcoes)->toContain($tela);
    } else {
        expect($opcoes)->not->toContain($tela);
    }
})->with([
    'bruno no /app: lista de usuários do /admin' => ['bruno', 'app', 'admin|'.UserResourceDoAdmin::class.'|', true, null],
    'bruno no /admin: Page do /infra'            => ['bruno', 'admin', 'infra|'.DashboardDoInfra::class.'|', false, null],
    'ana no /app: lista de usuários do /admin'   => ['ana', 'app', 'admin|'.UserResourceDoAdmin::class.'|', false, null],
    'bruno sem a permissão de listar usuários'   => ['bruno', 'admin', 'admin|'.UserResourceDoAdmin::class.'|', false, 'ViewAny:User'],
]);

it('[CT-21] a tela de um painel novo entra no catálogo sem configuração', function (): void {
    painelRegistradoEmTeste('novopainel')->pages([PaginaDoPainelNovoDoDock::class]);

    $master = usuarioDoKit('master_global', 'master@example.com');

    ligarDock(['admin']);
    noPainelBootado('admin');
    $this->actingAs($master);

    $opcoes = array_keys(opcoesDoSlideover(dockNaPagina('admin')));

    expect($opcoes)->toContain(chaveDeAtalho('novopainel', PaginaDoPainelNovoDoDock::class));
});

/**
 * Marcas do pacote: vendor/hoceineel/filament-quick-action-dock/resources/views/dock.blade.php:data-layout:53; data-qad-style:57.
 */
function afirmarLayoutEEstiloDoDock(Testable $dock, string $layout, string $estilo): void
{
    $dock->assertSee('data-layout="'.$layout.'"', false)
        ->assertSee('data-qad-style="'.$estilo.'"', false);
}

it('[CT-10] o dock usa o layout do usuário e sempre o estilo do settings', function (?string $layoutDoUsuario, string $esperado): void {
    gravarConfiguracao('dock_layout', 'bar');
    gravarConfiguracao('dock_estilo', 'outline');
    alinharConfiguracoesDoKit();
    ligarDock(['admin']);
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    concederDock('admin');
    $bruno->forceFill(['dock_layout' => $layoutDoUsuario])->save();
    noPainelBootado('admin');
    $this->actingAs($bruno);

    afirmarLayoutEEstiloDoDock(dockNaPagina('admin'), $esperado, 'outline');
})->with([
    'sem preferência usa settings'     => [null, 'bar'],
    'preferência sobrescreve settings' => ['speed-dial', 'speed-dial'],
]);

it('[CT-11] todo layout do pacote pode ser escolhido no slideover', function (string $layout): void {
    ligarDock(['admin']);
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    concederDock('admin');
    $bruno->forceFill(['dock_layout' => null])->save();
    noPainelBootado('admin');
    $this->actingAs($bruno);

    dockNaPagina('admin')
        ->mountAction('gerenciarAtalhos')
        ->assertFormFieldExists('layout', 'mountedActionSchema0', function (Select $field) use ($layout): bool {
            return array_key_exists($layout, $field->getOptions());
        })
        ->fillForm(['atalhos' => [], 'layout' => $layout], 'mountedActionSchema0')
        ->callMountedAction()
        ->assertHasNoErrors();

    expect($bruno->fresh()->getRawOriginal('dock_layout'))->toBe($layout);
    afirmarLayoutEEstiloDoDock(dockEmRequestNovo('admin'), $layout, config('kit.dock.estilo'));
})->with(
    // vendor/hoceineel/filament-quick-action-dock/src/Enums/DockLayout.php:SpeedDial:9-10.
    collect(DockLayout::cases())
        ->mapWithKeys(fn (DockLayout $layout): array => [$layout->value => [$layout->value]])
        ->all(),
);

it('[CT-12] o slideover não aceita layout fora do pacote nem estilo do usuário', function (string $campo, mixed $valor): void {
    gravarConfiguracao('dock_layout', 'bar');
    gravarConfiguracao('dock_estilo', 'outline');
    alinharConfiguracoesDoKit();
    ligarDock(['admin']);
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    concederDock('admin');
    $bruno->forceFill(['dock_layout' => null])->save();
    noPainelBootado('admin');
    $this->actingAs($bruno);

    chamarActionDoDockDireto(dockNaPagina('admin'), 'gerenciarAtalhos', ['atalhos' => [], $campo => $valor]);

    expect($bruno->fresh()->getRawOriginal('dock_layout'))->toBeNull();
    afirmarLayoutEEstiloDoDock(dockEmRequestNovo('admin'), 'bar', 'outline');
})->with([
    'layout fora do enum'               => ['layout', 'xpto'],
    'layout como lista'                 => ['layout', ['speed-dial']],
    'estilo válido proibido ao usuário' => ['estilo', 'contrast'],
]);

/** Bruno com as permissões e o painel necessários aos cenários de adição. */
function brunoParaAdicionarNoDock(bool $comInfra = false): User
{
    config(['kit.hub' => true]);
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole($comInfra ? ['panel_user', 'infra'] : ['panel_user']);
    concederDock('admin', 'panel_user', ...($comInfra ? ['infra'] : []));
    ligarDock(['admin']);
    noPainelBootado('admin');
    test()->actingAs($bruno);

    return $bruno;
}

/** Informa a rota real ao contexto antes de montar o componente, inclusive para telas de registro. */
function dockNaUrlAtual(string $url): Testable
{
    $rota = app('router')->getRoutes()->match(Request::create($url));

    DockContext::current()->set(
        $rota->getName(),
        $rota->parameters(),
        (string) parse_url($url, PHP_URL_PATH),
    );

    return Livewire::test(QuickActionDock::class);
}

/** @return list<array<string, mixed>> */
function avisosDoLimiteDoDock(): array
{
    return array_values(array_filter(
        // Lidas sem consumir (Filament 5.10 guarda em `NotificationMailbox`, não na sessão):
        // o `assertNotified()` esvazia a caixa, então leia antes dele.
        avisosDoFilament(),
        static fn (array $aviso): bool => str_contains((string) ($aviso['title'] ?? '').' '.(string) ($aviso['body'] ?? ''), '10'),
    ));
}

/** @param list<string> $urls */
function afirmarOrdemDosLinksDoDock(string $html, array $urls): void
{
    $anterior = -1;

    foreach ($urls as $url) {
        expect(ocorrenciasDoLink($html, $url))->toBe(1);
        $posicao = preg_match(padraoDoLink($url), $html, $achado, PREG_OFFSET_CAPTURE) ? $achado[0][1] : false;
        expect($posicao)->toBeGreaterThan($anterior);
        $anterior = $posicao;
    }
}

it('[CT-30] a página atual do catálogo vira favorito e seu link aparece no dock', function (string $destino): void {
    $bruno = brunoParaAdicionarNoDock();
    $chave = chaveDeAtalho('admin', $destino);
    $url   = urlDoDestinoDoDock('admin', $destino);

    expect(atalhosGravados($bruno))->toBe([]);
    expect(array_keys(opcoesDoSlideover(dockNaPagina('admin'))))->toContain($chave);

    dockNaUrlAtual($url)->callAction('adicionarPaginaAtual');

    expect(atalhosGravados($bruno))->toBe([$chave]);
    expect(ocorrenciasDoLink(dockEmRequestNovo('admin')->html(), $url))->toBe(1);
})->with([
    'lista de Resource' => [UserResourceDoAdmin::class],
    'Page avulsa'       => [DashboardClassico::class],
]);

it('[CT-31] a página atual sem acesso não vira favorito por chamada direta', function (): void {
    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    concederDock('panel_user');
    ligarDock(['app']);
    $this->actingAs($ana);
    $this->get('/app')->assertOk();
    Filament::setCurrentPanel('app');

    expect(atalhosGravados($ana))->toBe([]);
    expect(array_keys(opcoesDoSlideover(dockNaPagina('app'))))
        ->not->toContain(chaveDeAtalho('admin', UserResourceDoAdmin::class));

    chamarActionDoDockDireto(
        dockNaUrlAtual(UserResourceDoAdmin::getUrl('index', panel: 'admin')),
        'adicionarPaginaAtual',
    );

    expect(atalhosGravados($ana))->toBe([]);
});

it('[CT-32] o cliente não altera as propriedades do contexto da página atual', function (string $propriedade, mixed $valor): void {
    $bruno = brunoParaAdicionarNoDock();
    $dock  = dockNaUrlAtual(DashboardClassico::getUrl(panel: 'admin'));

    expect(fn () => $dock->set($propriedade, $valor))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    expect(atalhosGravados($bruno))->toBe([]);
})->with([
    'routeName: rota sem acesso'           => ['routeName', 'filament.infra.pages.backup-runs-page'],
    'routeParameters: organização forjada' => ['routeParameters', ['tenant' => 'globex']],
    'path: tipo errado'                    => ['path', ['array']],
]);

it('[CT-33] adicionar a página atual respeita o teto de 10 sem mudar a ordem', function (int $quantos, bool $recusa): void {
    $bruno  = brunoParaAdicionarNoDock(comInfra: true);
    $pagina = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $opcoes = array_keys(opcoesDoSlideover(dockNaPagina('admin')));
    expect($opcoes)->toContain($pagina);
    $outros = array_values(array_diff($opcoes, [$pagina]));
    expect(count($outros))->toBeGreaterThanOrEqual(10);
    $antes = array_slice(array_reverse($outros), 0, $quantos);
    gravarAtalhos($bruno, $antes);
    app()->instance('request', Request::create('/'));
    limparAvisosDoFilament();

    $dock = dockNaUrlAtual(UserResourceDoAdmin::getUrl('index', panel: 'admin'))
        ->callAction('adicionarPaginaAtual');

    expect(atalhosGravados($bruno))->toBe($recusa ? $antes : [...$antes, $pagina]);

    if ($recusa) {
        expect(avisosDoLimiteDoDock())->not->toBeEmpty();
        $dock->assertNotified();
    } else {
        expect(avisosDoLimiteDoDock())->toBe([]);
    }
})->with([
    '8 gravados: aceita o nono'                       => [8, false],
    '9 gravados: aceita o décimo'                     => [9, false],
    '10 gravados: recusa o décimo primeiro com aviso' => [10, true],
]);

it('[CT-34] o slideover aceita até 10 e recusa 11 com aviso sem substituir A', function (int $quantos, bool $recusa): void {
    $bruno  = brunoParaAdicionarNoDock(comInfra: true);
    $a      = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $opcoes = array_keys(opcoesDoSlideover(dockNaPagina('admin')));
    expect($opcoes)->toContain($a);
    expect(count($opcoes))->toBeGreaterThanOrEqual(12);
    $envio = array_slice(array_reverse(array_values(array_diff($opcoes, [$a]))), 0, $quantos);
    expect($envio)->toHaveCount($quantos)->not->toContain($a);
    gravarAtalhos($bruno, [$a]);
    app()->instance('request', Request::create('/'));
    limparAvisosDoFilament();

    $dock = dockNaPagina('admin')->callAction('gerenciarAtalhos', ['atalhos' => $envio]);

    expect(atalhosGravados($bruno))->toBe($recusa ? [$a] : $envio);

    if ($recusa) {
        expect(avisosDoLimiteDoDock())->not->toBeEmpty();
        $dock->assertNotified();
    } else {
        $dock->assertHasNoErrors();
        expect(avisosDoLimiteDoDock())->toBe([]);
    }
})->with([
    '9 enviados'                    => [9, false],
    '10 enviados'                   => [10, false],
    '11 enviados: mantém A e avisa' => [11, true],
]);

it('[CT-38] adicionar esta página só aparece no catálogo e fora dos favoritos', function (string $pagina, bool $visivel): void {
    $bruno = brunoParaAdicionarNoDock();
    $f     = chaveDeAtalho('admin', DashboardClassico::class);
    gravarAtalhos($bruno, [$f]);
    $fora = App\Filament\Admin\Pages\ConfiguracoesDoKit::class;

    if ($pagina === 'fora') {
        semAPermissao('admin', 'View:ConfiguracoesDoKit');
    }

    $opcoes = array_keys(opcoesDoSlideover(dockNaPagina('admin')));
    expect($opcoes)->toContain($f, chaveDeAtalho('admin', UserResourceDoAdmin::class));

    if ($pagina === 'fora') {
        expect($opcoes)->not->toContain(chaveDeAtalho('admin', $fora));
    }

    $url = match ($pagina) {
        'lista'         => UserResourceDoAdmin::getUrl('index', panel: 'admin'),
        'page'          => HubDeAdministracao::getUrl(panel: 'admin'),
        'criar'         => UserResourceDoAdmin::getUrl('create', panel: 'admin'),
        'editar', 'ver' => UserResourceDoAdmin::getUrl($pagina === 'editar' ? 'edit' : 'view', ['record' => $bruno], panel: 'admin'),
        'fora'          => $fora::getUrl(panel: 'admin'),
        'favorita'      => DashboardClassico::getUrl(panel: 'admin'),
    };

    if ($pagina === 'page') {
        expect($opcoes)->toContain(chaveDeAtalho('admin', HubDeAdministracao::class));
    }

    $dock = dockNaUrlAtual($url);

    if ($visivel) {
        $dock->assertActionExists('adicionarPaginaAtual')->assertActionVisible('adicionarPaginaAtual');
    } else {
        actionDoDockIndisponivel($dock, 'adicionarPaginaAtual');
    }
})->with([
    'lista de Resource'     => ['lista', true],
    'outra Page avulsa'     => ['page', true],
    'criar registro'        => ['criar', false],
    'editar registro'       => ['editar', false],
    'ver registro'          => ['ver', false],
    'Page fora do catálogo' => ['fora', false],
    'Page já favorita F'    => ['favorita', false],
]);

it('[CT-39] chamar adicionar esta página direto fora do catálogo mantém somente F', function (string $pagina): void {
    $bruno = brunoParaAdicionarNoDock();
    $f     = chaveDeAtalho('admin', DashboardClassico::class);
    gravarAtalhos($bruno, [$f]);

    if ($pagina === 'fora') {
        semAPermissao('admin', 'View:ConfiguracoesDoKit');
        expect(array_keys(opcoesDoSlideover(dockNaPagina('admin'))))
            ->not->toContain(chaveDeAtalho('admin', App\Filament\Admin\Pages\ConfiguracoesDoKit::class));
        $url = App\Filament\Admin\Pages\ConfiguracoesDoKit::getUrl(panel: 'admin');
    } else {
        $url = UserResourceDoAdmin::getUrl($pagina, $pagina === 'edit' ? ['record' => $bruno] : [], panel: 'admin');
    }

    chamarActionDoDockDireto(dockNaUrlAtual($url), 'adicionarPaginaAtual');

    expect(atalhosGravados($bruno))->toBe([$f]);
})->with([
    'editar registro'       => ['edit'],
    'criar registro'        => ['create'],
    'Page fora do catálogo' => ['fora'],
]);

it('[CT-48] o dock segue a ordem escolhida e a reordenação do slideover', function (array $ordem): void {
    $bruno  = brunoParaAdicionarNoDock(comInfra: true);
    $chaves = [
        'C' => chaveDeAtalho('infra', DashboardClassico::class),
        'A' => chaveDeAtalho('admin', UserResourceDoAdmin::class),
        'B' => chaveDeAtalho('admin', DashboardClassico::class),
    ];
    $urls = [
        'C' => DashboardClassico::getUrl(panel: 'infra'),
        'A' => UserResourceDoAdmin::getUrl('index', panel: 'admin'),
        'B' => DashboardClassico::getUrl(panel: 'admin'),
    ];
    gravarAtalhos($bruno, [$chaves['C'], $chaves['A'], $chaves['B']]);
    expect(array_keys(opcoesDoSlideover(dockNaPagina('admin'))))->toContain(...array_values($chaves));
    $envio = array_map(static fn (string $nome): string => $chaves[$nome], $ordem);

    dockNaPagina('admin')->callAction('gerenciarAtalhos', ['atalhos' => $envio])->assertHasNoErrors();

    expect(atalhosGravados($bruno))->toBe($envio);
    afirmarOrdemDosLinksDoDock(
        dockEmRequestNovo('admin')->html(),
        array_map(static fn (string $nome): string => $urls[$nome], ordemExibidaNoDock($ordem, ['C' => 'infra', 'A' => 'admin', 'B' => 'admin'])),
    );
})->with([
    'regrava C, A, B'  => [['C', 'A', 'B']],
    'reordena B, C, A' => [['B', 'C', 'A']],
]);

it('[CT-49] adicionar a página atual entra no fim sem reordenar C, A, B', function (): void {
    $bruno = brunoParaAdicionarNoDock(comInfra: true);
    $antes = [
        chaveDeAtalho('infra', DashboardClassico::class),
        chaveDeAtalho('admin', UserResourceDoAdmin::class),
        chaveDeAtalho('admin', DashboardClassico::class),
    ];
    $d      = chaveDeAtalho('admin', HubDeAdministracao::class);
    $urlDeD = HubDeAdministracao::getUrl(panel: 'admin');
    gravarAtalhos($bruno, $antes);
    expect(array_keys(opcoesDoSlideover(dockNaPagina('admin'))))->toContain($d);

    dockNaUrlAtual($urlDeD)->callAction('adicionarPaginaAtual');

    expect(atalhosGravados($bruno))->toBe([...$antes, $d]);
    // Exibição agrupada por painel (Adendo 6, RQ-36): o /admin antes do /infra, e D no fim do grupo dele.
    afirmarOrdemDosLinksDoDock(dockEmRequestNovo('admin')->html(), [
        UserResourceDoAdmin::getUrl('index', panel: 'admin'),
        DashboardClassico::getUrl(panel: 'admin'),
        $urlDeD,
        DashboardClassico::getUrl(panel: 'infra'),
    ]);
});

/**
 * A ordem em que o dock EXIBE os favoritos (Adendo 6, RQ-36): agrupados por painel, na ordem
 * app → admin → infra, e na ordem gravada dentro de cada painel.
 *
 * A ordem dos painéis está escrita aqui, e não lida de `Paineis::rotulos()`: o oráculo não sai da
 * implementação.
 *
 * @param  list<string>  $ordem  os nomes, na ordem gravada
 * @param  array<string, string>  $painelDe  nome => id do painel
 * @return list<string>
 */
function ordemExibidaNoDock(array $ordem, array $painelDe): array
{
    $paineis = ['app', 'admin', 'infra'];

    return array_merge(...array_map(
        static fn (string $painel): array => array_values(array_filter($ordem, static fn (string $nome): bool => $painelDe[$nome] === $painel)),
        $paineis,
    ));
}

/** Bruno no /app, com U e P gravados e acesso ao dock. */
function brunoComFavoritosEntrePaineis(): User
{
    config(['kit.hub' => true]);
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole('panel_user');
    concederDock('admin', 'panel_user');
    ligarDock(['app']);
    gravarAtalhos($bruno, [
        chaveDeAtalho('admin', UserResourceDoAdmin::class),
        chaveDeAtalho('app', DashboardClassico::class),
    ]);
    test()->actingAs($bruno);
    test()->get('/app')->assertOk();
    noPainelDoShield('app');

    return $bruno;
}

it('[CT-23] favorito do admin aparece no app com dock desligado na origem', function (): void {
    $bruno = brunoComFavoritosEntrePaineis();
    $u     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    gravarAtalhos($bruno, [$u]);

    $dock = dockEmRequestNovo('app');

    expect(ocorrenciasDoLink($dock->html(), UserResourceDoAdmin::getUrl('index', panel: 'admin')))->toBe(1);
    expect(ocorrenciasDoLink($dock->html(), UserResource::getUrl('index', panel: 'app')))->toBe(0);
});

it('[CT-24] sem acesso ao painel ou à tela o favorito some por HTTP e continua gravado', function (string $perda): void {
    config(['kit.hub' => true]);
    $usuario = usuarioDoKit($perda === 'permissao sem painel' ? 'panel_user' : 'admin');
    $usuario->assignRole('panel_user');
    concederDock('admin', 'panel_user');
    ligarDock(['app']);
    $u = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $p = chaveDeAtalho('app', DashboardClassico::class);
    gravarAtalhos($usuario, [$u, $p]);

    if ($perda === 'tela') {
        semAPermissao('admin', 'ViewAny:User');
    } elseif ($perda === 'painel') {
        $usuario->removeRole('admin');
    } else {
        papelDoKit('panel_user')->givePermissionTo('ViewAny:User');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    $this->actingAs($usuario);
    $resposta = $this->get('/app')->assertOk();

    expect(ocorrenciasDoLink($resposta->getContent(), DashboardClassico::getUrl(panel: 'app')))->toBeGreaterThan(0);
    expect(ocorrenciasDoLink($resposta->getContent(), UserResourceDoAdmin::getUrl('index', panel: 'admin')))->toBe(0);
    expect(atalhosGravados($usuario))->toBe([$u, $p]);
})->with([
    'bruno perdeu a tela'                    => ['tela'],
    'bruno perdeu o painel'                  => ['painel'],
    'ana tem permissão sem acesso ao painel' => ['permissao sem painel'],
]);

it('[CT-25] o favorito oculto volta depois da renderização sem permissão e da restauração', function (): void {
    $bruno = brunoComFavoritosEntrePaineis();
    $u     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    gravarAtalhos($bruno, [$u]);
    semAPermissao('admin', 'ViewAny:User');

    $oculto = dockEmRequestNovo('app');
    expect(ocorrenciasDoLink($oculto->html(), UserResourceDoAdmin::getUrl('index', panel: 'admin')))->toBe(0);
    expect(atalhosGravados($bruno))->toBe([$u]);

    papelDoKit('admin')->givePermissionTo('ViewAny:User');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $visivel = dockEmRequestNovo('app');

    expect(ocorrenciasDoLink($visivel->html(), UserResourceDoAdmin::getUrl('index', panel: 'admin')))->toBe(1);
    expect(atalhosGravados($bruno))->toBe([$u]);
});

it('[CT-45] o favorito oculto fica fora das opções e dos escolhidos do slideover', function (): void {
    brunoComFavoritosEntrePaineis();
    $u = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $p = chaveDeAtalho('app', DashboardClassico::class);
    semAPermissao('admin', 'ViewAny:User');
    $dock = dockEmRequestNovo('app');

    $opcoes = array_keys(opcoesDoSlideover($dock));

    expect($opcoes)->toContain($p)->not->toContain($u);
    expect($dock->get('mountedActions.0.data.atalhos'))->toContain($p)->not->toContain($u);
});

it('[CT-60] o favorito oculto de outro painel sobrevive ao salvar P e Q', function (): void {
    $bruno = brunoComFavoritosEntrePaineis();
    $u     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $p     = chaveDeAtalho('app', DashboardClassico::class);
    $q     = chaveDeAtalho('app', HubDoNegocio::class);
    semAPermissao('admin', 'ViewAny:User');
    expect(array_keys(opcoesDoSlideover(dockEmRequestNovo('app'))))->toContain($p, $q);

    dockEmRequestNovo('app')->callAction('gerenciarAtalhos', ['atalhos' => [$p, $q]])->assertHasNoErrors();

    expect(atalhosGravados($bruno))->toHaveCount(3)->toEqualCanonicalizing([$u, $p, $q]);
});

it('[CT-53] o favorito visível de outro painel sai quando removido pelo slideover', function (): void {
    $bruno = brunoComFavoritosEntrePaineis();
    $u     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $p     = chaveDeAtalho('app', DashboardClassico::class);
    expect(array_keys(opcoesDoSlideover(dockEmRequestNovo('app'))))->toContain($u, $p);

    dockEmRequestNovo('app')->callAction('gerenciarAtalhos', ['atalhos' => [$p]])->assertHasNoErrors();

    expect(atalhosGravados($bruno))->toBe([$p]);
    $dock = dockEmRequestNovo('app');
    expect(ocorrenciasDoLink($dock->html(), UserResourceDoAdmin::getUrl('index', panel: 'admin')))->toBe(0);
});

it('[CT-46] a chave que nunca foi destino sai ao salvar o slideover', function (array $nomes): void {
    $bruno  = brunoParaAdicionarNoDock();
    $chaves = [
        'A' => chaveDeAtalho('admin', UserResourceDoAdmin::class),
        'B' => chaveDeAtalho('admin', DashboardClassico::class),
        'C' => chaveDeAtalho('admin', HubDeAdministracao::class),
    ];
    gravarAtalhos($bruno, [$chaves['A'], 'https://exemplo.invalido/x', $chaves['B']]);
    $envio = array_map(static fn (string $nome): string => $chaves[$nome], $nomes);
    expect(array_keys(opcoesDoSlideover(dockEmRequestNovo('admin'))))->toContain(...$envio);

    dockEmRequestNovo('admin')->callAction('gerenciarAtalhos', ['atalhos' => $envio])->assertHasNoErrors();

    expect(atalhosGravados($bruno))->toBe($envio);
})->with([
    'regrava A e B'   => [['A', 'B']],
    'troca por B e C' => [['B', 'C']],
]);

it('[CT-54] reenviar a chave inválida já gravada recusa o envio inteiro', function (bool $incluiB): void {
    $bruno = brunoParaAdicionarNoDock();
    $a     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $b     = chaveDeAtalho('admin', DashboardClassico::class);
    $antes = [$a, 'https://exemplo.invalido/x', $b];
    gravarAtalhos($bruno, $antes);
    $envio = $incluiB ? $antes : [$a, 'https://exemplo.invalido/x'];

    chamarActionDoDockDireto(dockEmRequestNovo('admin'), 'gerenciarAtalhos', ['atalhos' => $envio]);

    expect(atalhosGravados($bruno))->toBe($antes);
})->with([
    'inválida mais A'   => [false],
    'todos os gravados' => [true],
]);

it('[CT-47] oculto e inválida gravada ocupam vaga no teto e a inválida sai ao salvar', function (int $visiveis, string $extra, string $operacao, int $enviados, bool $recusa): void {
    $bruno  = brunoParaAdicionarNoDock(comInfra: true);
    $atual  = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $oculto = chaveDeAtalho('admin', RoleResource::class);
    semAPermissao('admin', 'ViewAny:Role');
    $opcoes = array_keys(opcoesDoSlideover(dockEmRequestNovo('admin')));
    expect($opcoes)->toContain($atual)->not->toContain($oculto);
    $outros = array_values(array_diff($opcoes, [$atual]));
    expect(count($outros))->toBeGreaterThanOrEqual(10);
    $selecionados = array_slice($outros, 0, $visiveis);
    $entrada      = $extra === 'oculta' ? $oculto : 'https://exemplo.invalido/x';
    $antes        = [...$selecionados, $entrada];
    gravarAtalhos($bruno, $antes);
    limparAvisosDoFilament();

    if ($operacao === 'adicionar') {
        app()->instance('request', Request::create('/'));
        $dock      = dockNaUrlAtual(UserResourceDoAdmin::getUrl('index', panel: 'admin'))->callAction('adicionarPaginaAtual');
        $esperados = $recusa ? $antes : [...$antes, $atual];
    } else {
        $envio     = array_slice($outros, 0, $enviados);
        $dock      = dockEmRequestNovo('admin')->callAction('gerenciarAtalhos', ['atalhos' => $envio]);
        $esperados = $recusa ? $antes : ($extra === 'oculta' ? [...$envio, $entrada] : $envio);
    }

    $gravados = atalhosGravados($bruno);
    if ($operacao === 'adicionar' || $recusa || $extra === 'inválida') {
        expect($gravados)->toBe($esperados);
    } else {
        expect($gravados)->toHaveCount(count($esperados))->toEqualCanonicalizing($esperados);
    }
    if ($recusa) {
        $avisos = avisosDoLimiteDoDock();
        $dock->assertNotified();
        expect($avisos)->not->toBeEmpty();
    } else {
        $dock->assertHasNoErrors();
        expect(avisosDoLimiteDoDock())->toBe([]);
    }
})->with([
    '8 visíveis e oculto: adiciona o décimo'               => [8, 'oculta', 'adicionar', 0, false],
    '9 visíveis e oculto: recusa adicionar'                => [9, 'oculta', 'adicionar', 0, true],
    '9 visíveis e oculto: regrava 9 sem aviso'             => [9, 'oculta', 'salvar', 9, false],
    '9 visíveis e oculto: recusa envio de 10'              => [9, 'oculta', 'salvar', 10, true],
    '8 visíveis e inválida: adiciona o décimo'             => [8, 'inválida', 'adicionar', 0, false],
    '9 visíveis e inválida: recusa adicionar'              => [9, 'inválida', 'adicionar', 0, true],
    '9 visíveis e inválida: grava 10 descartando inválida' => [9, 'inválida', 'salvar', 10, false],
]);

it('[CT-40] o mesmo destino nunca é gravado duas vezes', function (string $operacao, int $maximo): void {
    $bruno = brunoParaAdicionarNoDock();
    $a     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $b     = chaveDeAtalho('admin', DashboardClassico::class);
    gravarAtalhos($bruno, [$a, $b]);
    app()->instance('request', Request::create('/'));
    $dock = dockNaUrlAtual(UserResourceDoAdmin::getUrl('index', panel: 'admin'));

    if ($operacao === 'slideover') {
        chamarActionDoDockDireto($dock, 'gerenciarAtalhos', ['atalhos' => [$a, $a, $b]]);
    } else {
        chamarActionDoDockDireto($dock, 'adicionarPaginaAtual');
        if ($operacao === 'duplo clique') {
            chamarActionDoDockDireto($dock, 'adicionarPaginaAtual');
        }
    }

    $gravados = atalhosGravados($bruno);
    expect(array_count_values($gravados)[$a] ?? 0)->toBe(1);
    expect(count($gravados))->toBeLessThanOrEqual($maximo);
})->with([
    'página atual já favorita'    => ['direto', 2],
    'duplo clique'                => ['duplo clique', 2],
    'chave repetida no slideover' => ['slideover', 2],
]);

use App\Filament\Infra\Pages\BackupRunsPage;
use App\Filament\Infra\Pages\Pulse;

/** Bruno nos dois painéis, sem limpar o mapa de Pages do Shield. */
function brunoComPageDoInfraRevogada(): User
{
    config(['kit.hub' => true]);
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole('infra');
    concederDock('admin', 'infra');
    ligarDock(['admin']);
    semAPermissao('infra', 'View:Pulse');
    test()->actingAs($bruno);

    return $bruno;
}

it('[CT-66] Pulse sem permissão some do admin com a navegação já renderizada', function (): void {
    $bruno = brunoComPageDoInfraRevogada();
    gravarAtalhos($bruno, [
        chaveDeAtalho('infra', Pulse::class),
        chaveDeAtalho('infra', BackupRunsPage::class),
    ]);

    $resposta = $this->get('/admin')->assertOk();

    expect(ocorrenciasDoLink($resposta->getContent(), BackupRunsPage::getUrl(panel: 'infra')))->toBeGreaterThan(0);
    expect(ocorrenciasDoLink($resposta->getContent(), Pulse::getUrl(panel: 'infra')))->toBe(0);
});

it('[CT-67] o catálogo recusa Pulse com o mapa de Pages do admin montado', function (): void {
    brunoComPageDoInfraRevogada();
    $this->get('/admin')->assertOk();
    Filament::getNavigation();

    $opcoes = array_keys(opcoesDoSlideover(dockEmRequestNovo('admin')));

    expect($opcoes)->not->toContain(chaveDeAtalho('infra', Pulse::class));
    expect($opcoes)->toContain(chaveDeAtalho('infra', BackupRunsPage::class));
});

it('[CT-68] o envio de Pulse sem permissão preserva X com o mapa do admin montado', function (): void {
    $bruno = brunoComPageDoInfraRevogada();
    $x     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    gravarAtalhos($bruno, [$x]);
    $this->get('/admin')->assertOk();
    Filament::getNavigation();

    chamarActionDoDockDireto(dockEmRequestNovo('admin'), 'gerenciarAtalhos', [
        'atalhos' => [$x, chaveDeAtalho('infra', Pulse::class)],
    ]);

    expect(atalhosGravados($bruno))->toBe([$x]);
});

it('[CT-69] salvar C e A re-renderiza o mesmo dock nessa ordem e remove B', function (): void {
    $bruno = brunoParaAdicionarNoDock();
    $a     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    $b     = chaveDeAtalho('admin', DashboardClassico::class);
    $c     = chaveDeAtalho('admin', HubDeAdministracao::class);
    gravarAtalhos($bruno, [$a, $b]);
    $dock = dockEmRequestNovo('admin');
    $urlA = UserResourceDoAdmin::getUrl('index', panel: 'admin');
    $urlB = DashboardClassico::getUrl(panel: 'admin');
    $urlC = HubDeAdministracao::getUrl(panel: 'admin');
    expect(ocorrenciasDoLink($dock->html(), $urlA))->toBe(1, 'A aparece no componente antes de salvar');
    expect(ocorrenciasDoLink($dock->html(), $urlB))->toBe(1, 'B aparece no componente antes de salvar');

    $dock->callAction('gerenciarAtalhos', ['atalhos' => [$c, $a]])->assertHasNoErrors();

    expect(atalhosGravados($bruno))->toBe([$c, $a]);
    afirmarOrdemDosLinksDoDock($dock->html(), [$urlC, $urlA]);
    expect(ocorrenciasDoLink($dock->html(), $urlB))->toBe(0);
});

it('[CT-70] chamada pública forjada não estoura nem altera X', function (array $chamadas): void {
    $bruno = brunoParaAdicionarNoDock();
    $x     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    gravarAtalhos($bruno, [$x]);
    $dock = dockEmRequestNovo('admin');

    foreach ($chamadas as [$metodo, $argumentos]) {
        $dock->call($metodo, ...$argumentos);
    }

    expect(atalhosGravados($bruno))->toBe([$x]);
})->with([
    'mountAction com argumentos como texto'        => [[['mountAction', ['gerenciarAtalhos', 'x']]]],
    'callMountedAction com texto sem action'       => [[['callMountedAction', ['x']]]],
    'payload sem action montada'                   => [[['callMountedAction', [['atalhos' => ['https://exemplo.invalido/x']]]]]],
    'favoritos nos argumentos e não no formulário' => [[
        ['mountAction', ['gerenciarAtalhos', ['atalhos' => ['https://exemplo.invalido/x']]]],
        ['callMountedAction', []],
    ]],
    'nome da action de tipo errado' => [[['mountAction', [42]]]],
]);

it('[CT-71] três favoritos do admin não recarregam papéis e custam no máximo uma consulta cada', function (string $afirmacao): void {
    $bruno = brunoParaAdicionarNoDock();
    $tres  = [
        chaveDeAtalho('admin', UserResourceDoAdmin::class),
        chaveDeAtalho('admin', DashboardClassico::class),
        chaveDeAtalho('admin', HubDeAdministracao::class),
    ];
    $medir = function (array $chaves) use ($bruno): array {
        gravarAtalhos($bruno, $chaves);

        return consultasDaPaginaInicialDoAdmin(true);
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
        static fn (array $consulta): bool => preg_match('/^\s*select\s+(?!exists\b).*\bfrom\s+[`"\[]?roles[`"\]]?(\s|$)/is', $consulta['sql']) === 1
            && str_contains($consulta['sql'], 'model_has_roles')
            && pilhaComCodigoDoDock($consulta['arquivos']),
    ));

    expect(array_column($proibidas, 'sql'))->toBe([]);
})->with([
    'nenhuma recarga de papéis pelo dock' => ['papeis'],
    'no máximo T + 3 consultas'           => ['limite'],
]);

it('[CT-75] adicionar uma página que deixou de poder ser adicionada avisa sem gravar', function (string $mudanca): void {
    $bruno = brunoParaAdicionarNoDock();
    $b     = chaveDeAtalho('admin', DashboardClassico::class);
    $a     = chaveDeAtalho('admin', UserResourceDoAdmin::class);
    gravarAtalhos($bruno, [$b]);
    $dock = dockNaUrlAtual(UserResourceDoAdmin::getUrl('index', panel: 'admin'));
    $dock->assertActionVisible('adicionarPaginaAtual');
    $esperados = [$b];
    if ($mudanca === 'duas abas') {
        $esperados = [$b, $a];
        gravarAtalhos($bruno, $esperados);
    } else {
        semAPermissao('admin', 'ViewAny:User');
    }
    limparAvisosDoFilament();

    chamarActionDoDockDireto($dock, 'adicionarPaginaAtual');

    $avisos = avisosDoFilament();
    expect(atalhosGravados($bruno))->toBe($esperados);
    expect(array_filter($avisos, static fn (array $aviso): bool => ($aviso['status'] ?? null) === 'warning'))->not->toBeEmpty();
    $dock->assertNotified();
})->with([
    'já favorita em outra aba'      => ['duas abas'],
    'destino deixou de ser abrível' => ['permissão revogada'],
]);

/*
| Favoritos agrupados por painel, com o rótulo do painel como separador no dock.
*/

it('agrupa os favoritos por painel, com o rótulo do painel e a ordem gravada dentro do grupo', function (): void {
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole('infra');

    concederDock('admin', 'infra');
    ligarDock(['admin', 'infra']);
    noPainelBootado('admin');
    $this->actingAs($bruno);

    $porPainel = collect(destinosAbriveisDoDock($bruno))
        ->groupBy(static fn (array $destino): string => DockDeAtalhos::painelDaChave($destino['chave']));

    // Arranjo: dois destinos do /admin e um do /infra, gravados intercalados.
    expect(count($porPainel->get('admin', [])))->toBeGreaterThanOrEqual(2)
        ->and(count($porPainel->get('infra', [])))->toBeGreaterThanOrEqual(1);

    gravarAtalhos($bruno, [
        $porPainel['admin'][0]['chave'],
        $porPainel['infra'][0]['chave'],
        $porPainel['admin'][1]['chave'],
    ]);

    $grupos = array_map(
        static fn (array $grupo): array => [$grupo['label'], array_keys($grupo['actions'])],
        dockNaPagina('admin')->instance()->getDockGroups(),
    );

    expect(array_slice($grupos, 0, 2))->toBe([
        ['Administração', ['atalho_0', 'atalho_2']],
        ['Infraestrutura', ['atalho_1']],
    ])
        ->and(array_column($grupos, 0))->toBe(['Administração', 'Infraestrutura', 'Atalhos']);
});

it('não mostra separador de painel sem favorito visível', function (): void {
    $master = usuarioDoKit('master_global', 'master@example.com');

    ligarDock(['admin']);
    gravarAtalhos($master, [chaveDeAtalho('admin', UserResourceDoAdmin::class)]);

    noPainelBootado('admin');
    $this->actingAs($master);

    expect(array_column(dockNaPagina('admin')->instance()->getDockGroups(), 'label'))
        ->toBe(['Administração', 'Atalhos']);
});

/*
| Projeto atualizado pelo `kit:update` antes do `composer require` do pacote (validação da v0.48.0,
| cenários 3 e 4): o código do dock chega e o pacote não, porque o `composer.json` nunca é aplicado.
*/

it('reconhece se o pacote do dock está instalado pela classe do plugin', function (): void {
    expect(DockDeAtalhos::pacoteInstalado())->toBeTrue()
        ->and(DockDeAtalhos::pacoteInstalado('HoceineEl\QuickActionDock\PluginQueNaoExiste'))->toBeFalse();
});

it('monta o plugin do pacote quando ele está instalado', function (): void {
    expect(PluginDoDock::make())->toBeInstanceOf(QuickActionDockPlugin::class);
});

it('sem o pacote, entrega um plugin inerte que o painel registra e inicializa', function (): void {
    $plugin = PluginDoDock::make(pacoteInstalado: false);
    $painel = Panel::make()->id('painel-sem-dock');

    $plugin->register($painel);
    $plugin->boot($painel);

    expect($plugin)->not->toBeInstanceOf(QuickActionDockPlugin::class)
        ->and($plugin->getId())->not->toBe(QuickActionDockPlugin::make()->getId())
        ->and($painel->getPlugins())->toBe([]);
});
