<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Admin\Resources\AgentesIa\Pages\ListAgentesIa;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\App\Resources\Users\Pages\ListUsers as ListUsersApp;
use App\Filament\Infra\Resources\AiRuns\Pages\ListAiRuns;
use App\Filament\Infra\Resources\ComposerReleasePackages\Pages\ListComposerReleasePackages;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Sehsah\FilamentRememberTable\Models\TableView;
use Tests\Kit\fixtures\ListUsersAgrupavel;

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Monta a página no painel real; o retorno é também a costura de inspeção do estado.
 *
 * @param  class-string  $pagina
 * @param  array{view?: string|array<string>, search?: string, filters?: array<string, array{value: string}>, sort?: string, tab?: string}  $consulta
 */
function tabelaL5(User $usuario, string $pagina = ListUsers::class, array $consulta = [], string $painel = 'admin'): Testable
{
    test()->actingAs($usuario);
    noPainelBootado($painel);

    return Livewire::withQueryParams($consulta)->test($pagina)->loadTable();
}

/**
 * Usa a chave pública da página, sem pressupor o formato da string nem alterar D0.
 *
 * @param  array{v?: int, search?: string, filters?: array<string, mixed>, sort?: string, grouping?: string, tab?: string, perPage?: int, columns?: array<array{name: string, toggled: bool}>}  $estado
 * @param  array{is_default?: bool, is_public?: bool, sort_order?: int}  $atributos
 * @param  class-string  $pagina
 */
function visaoL5(User $dono, string $nome, array $estado = [], array $atributos = [], string $pagina = ListUsers::class): TableView
{
    $chave = app($pagina)->getTableViewsKey();

    return visaoGravada($dono, $chave, $estado, ['name' => $nome, ...$atributos]);
}

function aplicarL5(TableView $visao): string
{
    return 'applyTableView_'.md5((string) $visao->getKey());
}

/**
 * A oferta vem das ações do cabeçalho, incluindo a visibilidade de cada ação filha.
 *
 * @return list<string>
 */
function oferecidasL5(Testable $tabela): array
{
    $nomes = [];
    foreach ($tabela->instance()->getCachedHeaderActions() as $acao) {
        if ($acao->isHidden()) {
            continue;
        }
        foreach ($acao instanceof ActionGroup ? $acao->getFlatActions() : [$acao] as $filha) {
            if ($filha->isVisible()) {
                $nomes[] = $filha->getName();
            }
        }
    }

    return $nomes;
}

/**
 * Acesso pelo nome, sem o pré-requisito de visibilidade imposto pelo helper callAction().
 *
 * @param  array{name?: string, views?: array<array-key, array<string, mixed>>}  $dados
 */
function acionarL5(Testable $tabela, string $acao, array $dados = []): Testable
{
    $tabela->mountAction($acao);
    if ($tabela->instance()->getMountedAction() !== null) {
        if ($dados !== []) {
            $tabela->fillForm($dados);
        }
        $tabela->callMountedAction();
    }

    return $tabela;
}

/**
 * @param  array{name?: string, is_default?: bool, is_public?: bool, user_id?: int|string, table_key?: string, tenant_id?: string}  $mudancas
 * @return array{id: int|string, name: string, is_default: bool, is_public: bool, user_id?: int|string, table_key?: string, tenant_id?: string}
 */
function itemL5(TableView $visao, array $mudancas = []): array
{
    return ['id' => $visao->id, 'name' => $visao->name, 'is_default' => $visao->is_default, 'is_public' => $visao->is_public, ...$mudancas];
}

function alternarVisoesL5(User $admin, bool $ligadas): void
{
    test()->actingAs($admin);
    noPainelBootado('admin');
    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['visoes_de_tabela' => $ligadas])->call('save')->assertHasNoFormErrors();
    alinharConfiguracoesDoKit();
}

it('[CT-04] oferece o botão e as quatro ações conforme o interruptor em cada listagem', function (array $c): void {
    $paginas = [
        '/admin/users'                     => ListUsers::class,
        '/admin/convites'                  => ListConvites::class,
        '/admin/agentes-ia'                => ListAgentesIa::class,
        '/admin/shield/roles'              => ListRoles::class,
        '/infra/ai-runs'                   => ListAiRuns::class,
        '/infra/composer-release-packages' => ListComposerReleasePackages::class,
    ];
    $ligadas = $c['visoes'] === 'ligadas';
    ligarVisoesDeTabela($ligadas, $c['compartilhamento'] === 'ligado');
    $painel = str_starts_with($c['listagem'], '/infra') ? 'infra' : 'admin';
    $ana    = usuarioDoKit($painel);
    concederVisoesDeTabela($painel);
    $visao  = visaoL5($ana, 'Ativos', ['search' => 'zz'], pagina: $paginas[$c['listagem']]);
    $tabela = tabelaL5($ana, $paginas[$c['listagem']], painel: $painel)
        ->set('activeTableView', (string) $visao->id)->searchTable('ab');

    expect($tabela->instance()->getTableViewActions() !== [])->toBe($ligadas);
    foreach (['saveTableView', 'updateTableView', 'manageTableViews', 'resetTableView'] as $acao) {
        expect(in_array($acao, oferecidasL5($tabela), true))->toBe($ligadas);
    }
})->with([
    '/admin/users | Ana (admin) | desligadas | desligado | não é'                     => [['listagem' => '/admin/users', 'persona' => 'Ana (admin)', 'visoes' => 'desligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'não é']],
    '/admin/users | Ana (admin) | desligadas | ligado | não é'                        => [['listagem' => '/admin/users', 'persona' => 'Ana (admin)', 'visoes' => 'desligadas', 'compartilhamento' => 'ligado', 'oferecido' => 'não é']],
    '/admin/convites | Ana (admin) | desligadas | desligado | não é'                  => [['listagem' => '/admin/convites', 'persona' => 'Ana (admin)', 'visoes' => 'desligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'não é']],
    '/admin/agentes-ia | Ana (admin) | desligadas | desligado | não é'                => [['listagem' => '/admin/agentes-ia', 'persona' => 'Ana (admin)', 'visoes' => 'desligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'não é']],
    '/admin/shield/roles | Ana (admin) | desligadas | desligado | não é'              => [['listagem' => '/admin/shield/roles', 'persona' => 'Ana (admin)', 'visoes' => 'desligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'não é']],
    '/infra/ai-runs | Ivo (infra) | desligadas | desligado | não é'                   => [['listagem' => '/infra/ai-runs', 'persona' => 'Ivo (infra)', 'visoes' => 'desligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'não é']],
    '/infra/composer-release-packages | Ivo (infra) | desligadas | desligado | não é' => [['listagem' => '/infra/composer-release-packages', 'persona' => 'Ivo (infra)', 'visoes' => 'desligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'não é']],
    '/admin/users | Ana (admin) | ligadas | desligado | é'                            => [['listagem' => '/admin/users', 'persona' => 'Ana (admin)', 'visoes' => 'ligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'é']],
    '/admin/convites | Ana (admin) | ligadas | desligado | é'                         => [['listagem' => '/admin/convites', 'persona' => 'Ana (admin)', 'visoes' => 'ligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'é']],
    '/admin/agentes-ia | Ana (admin) | ligadas | desligado | é'                       => [['listagem' => '/admin/agentes-ia', 'persona' => 'Ana (admin)', 'visoes' => 'ligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'é']],
    '/admin/shield/roles | Ana (admin) | ligadas | desligado | é'                     => [['listagem' => '/admin/shield/roles', 'persona' => 'Ana (admin)', 'visoes' => 'ligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'é']],
    '/infra/ai-runs | Ivo (infra) | ligadas | desligado | é'                          => [['listagem' => '/infra/ai-runs', 'persona' => 'Ivo (infra)', 'visoes' => 'ligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'é']],
    '/infra/composer-release-packages | Ivo (infra) | ligadas | desligado | é'        => [['listagem' => '/infra/composer-release-packages', 'persona' => 'Ivo (infra)', 'visoes' => 'ligadas', 'compartilhamento' => 'desligado', 'oferecido' => 'é']],
])->group('kit');

it('[CT-06] mede as consultas a visões no request com controle positivo', function (array $c): void {
    $ana = usuarioDoKit('admin');
    visaoL5($ana, 'Minha');
    gravarConfiguracao('visoes_de_tabela', $c['visoes'] === 'ligadas');
    alinharConfiguracoesDoKit();
    $consultas = [];
    DB::listen(function (QueryExecuted $query) use (&$consultas): void {
        if (str_contains($query->sql, 'remember_table_views')) {
            $consultas[] = $query->sql;
        }
    });

    $this->actingAs($ana)->get('/admin/users')->assertOk();

    $c['visoes'] === 'ligadas' ? expect(count($consultas))->toBeGreaterThanOrEqual(1) : expect($consultas)->toBeEmpty();
})->with([
    'desligadas | 0 | prova'          => [['visoes' => 'desligadas', 'consultas' => '0']],
    'ligadas | ao menos 1 | controle' => [['visoes' => 'ligadas', 'consultas' => 'ao menos 1']],
])->group('kit');

it('[CT-07] desligadas não aplicam visão padrão por URL ou pelo método público', function (array $c): void {
    ligarVisoesDeTabela(false, true);
    $ana      = usuarioDoKit('admin');
    $dona     = str_contains($c['visao'], 'Bia') ? usuarioCom('admin') : $ana;
    $visao    = visaoL5($dona, 'Alvo desligado', ['search' => 'zz'], ['is_default' => str_contains($c['visao'], 'padrão'), 'is_public' => $dona !== $ana]);
    $consulta = str_contains($c['acao'], '?view') ? ['view' => (string) $visao->id] : [];

    $tabela = tabelaL5($ana, consulta: $consulta);
    if (str_contains($c['acao'], 'chama')) {
        $tabela->call('applyTableView', (string) $visao->id);
    }

    $tabela->assertSet('tableSearch', '')->assertDontSee('Alvo desligado');
})->with([
    'a visão padrão da Ana em /admin/users | abre /admin/users'                                    => [['visao' => 'a visão padrão da Ana em /admin/users', 'acao' => 'abre /admin/users']],
    'a visão da Ana em /admin/users | abre /admin/users com ?view={id da visão}'                   => [['visao' => 'a visão da Ana em /admin/users', 'acao' => 'abre /admin/users com ?view={id da visão}']],
    'a visão da Ana em /admin/users | chama applyTableView com o id da visão'                      => [['visao' => 'a visão da Ana em /admin/users', 'acao' => 'chama applyTableView com o id da visão']],
    'a visão compartilhada da Bia em /admin/users | chama applyTableView com o id da visão da Bia' => [['visao' => 'a visão compartilhada da Bia em /admin/users', 'acao' => 'chama applyTableView com o id da visão da Bia']],
])->group('kit');

it('[CT-08] desligadas recusam cada escrita pelo nome e conservam a busca operável', function (array $c): void {
    ligarVisoesDeTabela(false);
    $ana = usuarioDoKit('admin');
    concederVisoesDeTabela('admin');
    $alvo   = User::factory()->create(['name' => 'ab encontrado', 'email' => 'alvo@example.net']);
    $fora   = User::factory()->create(['name' => 'fora', 'email' => 'fora@example.net']);
    $visao  = visaoL5($ana, 'Ativos', ['search' => 'zz']);
    $tabela = tabelaL5($ana)->set('activeTableView', (string) $visao->id)->searchTable('ab');
    $dados  = match ($c['acao']) {
        'saveTableView'    => ['name' => 'Nova'],
        'manageTableViews' => ['views' => str_contains($c['dados'], 'vazia') ? [] : [itemL5($visao, ['name' => 'X'])]],
        default            => [],
    };

    acionarL5($tabela, $c['acao'], $dados);

    expect(TableView::count())->toBe(1);
    expect($visao->fresh())->name->toBe('Ativos')->state->toMatchArray(['search' => 'zz']);
    $tabela->assertSet('tableSearch', 'ab')->assertCanSeeTableRecords([$alvo])->assertCanNotSeeTableRecords([$fora]);
})->with([
    'saveTableView | nome "Nova" | remember_table_views continua com 1 linha'                        => [['acao' => 'saveTableView', 'dados' => 'nome "Nova"', 'nao_efeito' => 'remember_table_views continua com 1 linha']],
    'updateTableView | — (com "Ativos" ativa) | o state de "Ativos" continua com a busca "zz"'       => [['acao' => 'updateTableView', 'dados' => '— (com "Ativos" ativa)', 'nao_efeito' => 'o state de "Ativos" continua com a busca "zz"']],
    'manageTableViews | "Ativos" renomeada para "X" | "Ativos" continua gravada com o nome "Ativos"' => [['acao' => 'manageTableViews', 'dados' => '"Ativos" renomeada para "X"', 'nao_efeito' => '"Ativos" continua gravada com o nome "Ativos"']],
    'manageTableViews | a lista vazia (pedido de apagar "Ativos") | "Ativos" continua gravada'       => [['acao' => 'manageTableViews', 'dados' => 'a lista vazia (pedido de apagar "Ativos")', 'nao_efeito' => '"Ativos" continua gravada']],
    'resetTableView | — | a busca da tabela continua "ab"'                                           => [['acao' => 'resetTableView', 'dados' => '—', 'nao_efeito' => 'a busca da tabela continua "ab"']],
])->group('kit');

it('[CT-20] sem permissão cada escrita fica oculta e não executa pelo nome', function (array $c): void {
    ligarVisoesDeTabela(true, true);
    semAPermissao('admin', 'Usar:VisoesDeTabela');
    $ana    = usuarioDoKit('admin');
    $alvo   = User::factory()->create(['name' => 'ab encontrado', 'email' => 'alvo@example.net']);
    $fora   = User::factory()->create(['name' => 'fora', 'email' => 'fora@example.net']);
    $visao  = visaoL5($ana, 'Ativos', ['search' => 'zz']);
    $tabela = tabelaL5($ana)->call('applyTableView', (string) $visao->id)->searchTable('ab');
    expect(oferecidasL5($tabela))->not->toContain($c['acao']);
    $dados = match ($c['acao']) {
        'saveTableView'    => ['name' => 'Nova'],
        'manageTableViews' => ['views' => str_contains($c['dados'], 'vazia') ? [] : [itemL5($visao, ['name' => 'X'])]],
        default            => [],
    };

    acionarL5($tabela, $c['acao'], $dados);

    expect(TableView::count())->toBe(1);
    expect($visao->fresh())->name->toBe('Ativos')->state->toMatchArray(['search' => 'zz']);
    $tabela->assertSet('tableSearch', 'ab')->assertCanSeeTableRecords([$alvo])->assertCanNotSeeTableRecords([$fora]);
})->with([
    'saveTableView | nome "Nova" | remember_table_views continua com 1 linha'                        => [['acao' => 'saveTableView', 'dados' => 'nome "Nova"', 'nao_efeito' => 'remember_table_views continua com 1 linha']],
    'updateTableView | — | o state de "Ativos" continua com a busca "zz"'                            => [['acao' => 'updateTableView', 'dados' => '—', 'nao_efeito' => 'o state de "Ativos" continua com a busca "zz"']],
    'manageTableViews | "Ativos" renomeada para "X" | "Ativos" continua gravada com o nome "Ativos"' => [['acao' => 'manageTableViews', 'dados' => '"Ativos" renomeada para "X"', 'nao_efeito' => '"Ativos" continua gravada com o nome "Ativos"']],
    'manageTableViews | a lista vazia (pedido de apagar "Ativos") | "Ativos" continua gravada'       => [['acao' => 'manageTableViews', 'dados' => 'a lista vazia (pedido de apagar "Ativos")', 'nao_efeito' => '"Ativos" continua gravada']],
    'resetTableView | — | a busca da tabela continua "ab"'                                           => [['acao' => 'resetTableView', 'dados' => '—', 'nao_efeito' => 'a busca da tabela continua "ab"']],
])->group('kit');

it('[CT-21] com permissão oferece e executa cada ação de escrita', function (array $c): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana    = usuarioDoKit('admin');
    $visao  = visaoL5($ana, 'Ativos', ['search' => 'zz']);
    $tabela = tabelaL5($ana)->call('applyTableView', (string) $visao->id)->searchTable('ab');
    expect(oferecidasL5($tabela))->toContain($c['acao']);
    $dados = match ($c['acao']) {
        'saveTableView'    => ['name' => 'Nova'],
        'manageTableViews' => ['views' => [itemL5($visao, ['name' => 'Meus'])]],
        default            => [],
    };

    $tabela->callAction($c['acao'], $dados)->assertHasNoFormErrors();

    match ($c['acao']) {
        'saveTableView'    => expect(TableView::count())->toBe(2)->and(TableView::where('name', 'Nova')->exists())->toBeTrue(),
        'updateTableView'  => expect($visao->fresh()->state['search'])->toBe('ab'),
        'manageTableViews' => expect($visao->fresh()->name)->toBe('Meus'),
        'resetTableView'   => $tabela->assertSet('tableSearch', ''),
    };
})->with([
    'saveTableView | nome "Nova" | remember_table_views tem 2 linhas, uma "Nova"'             => [['acao' => 'saveTableView', 'dados' => 'nome "Nova"', 'efeito' => 'remember_table_views tem 2 linhas, uma "Nova"']],
    'updateTableView | — | o state de "Ativos" passa a ter a busca "ab"'                      => [['acao' => 'updateTableView', 'dados' => '—', 'efeito' => 'o state de "Ativos" passa a ter a busca "ab"']],
    'manageTableViews | "Ativos" renomeada "Meus" | a visão gravada passa a se chamar "Meus"' => [['acao' => 'manageTableViews', 'dados' => '"Ativos" renomeada "Meus"', 'efeito' => 'a visão gravada passa a se chamar "Meus"']],
    'resetTableView | — | a busca da tabela fica vazia'                                       => [['acao' => 'resetTableView', 'dados' => '—', 'efeito' => 'a busca da tabela fica vazia']],
])->group('kit');

it('[CT-22] sem permissão aplica compartilhada e oferece o botão e a opção de aplicar', function (): void {
    ligarVisoesDeTabela(true, true);
    semAPermissao('admin', 'Usar:VisoesDeTabela');
    $ana    = usuarioDoKit('admin');
    $visao  = visaoL5(usuarioCom('admin'), 'Pendentes', ['search' => 'zz'], ['is_public' => true]);
    $tabela = tabelaL5($ana);

    $tabela->callAction(aplicarL5($visao))->assertSet('tableSearch', 'zz');

    expect($tabela->instance()->getTableViewActions())->not->toBeEmpty();
    expect(oferecidasL5($tabela))->toContain(aplicarL5($visao))->not->toContain('saveTableView', 'updateTableView', 'manageTableViews', 'resetTableView');
})->group('kit');

it('[CT-24] salva recorte dona e página sem compartilhar nem guardar tamanho de página', function (): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana    = usuarioDoKit('admin', 'ana@example.com');
    $tabela = tabelaL5($ana)->searchTable('ana')->sortTable('email')->set('tableRecordsPerPage', 25);

    $tabela->callAction('saveTableView', ['name' => 'Minha visão'])->assertHasNoFormErrors();

    $visao = TableView::where('name', 'Minha visão')->sole();
    expect((string) $visao->user_id)->toBe((string) $ana->id);
    expect($visao->table_key)->toBe($tabela->instance()->getTableViewsKey());
    expect($visao->tenant_id)->toBeNull();
    expect($visao->state)->toMatchArray(['search' => 'ana', 'sort' => 'email:asc'])->not->toHaveKey('perPage');
    expect($visao->is_public)->toBeFalse();
})->group('kit');

it('[CT-25] ignora campos forjados no salvamento', function (array $c): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana    = usuarioDoKit('admin');
    $bia    = usuarioCom('admin');
    $tabela = tabelaL5($ana);
    $valor  = match ($c['campo']) {
        'user_id'   => $bia->id,
        'table_key' => app(ListConvites::class)->getTableViewsKey(),
        'is_public' => true,
    };
    $dados = ['name' => 'Forjada'];
    if ($c['valor'] !== 'ausente do payload') {
        $dados[$c['campo']] = $valor;
    }

    $tabela->callAction('saveTableView', $dados)->assertHasNoFormErrors();

    $visao    = TableView::where('name', 'Forjada')->sole();
    $esperado = match ($c['campo']) {
        'user_id'   => (string) $ana->id,
        'table_key' => $tabela->instance()->getTableViewsKey(),
        'is_public' => false,
    };
    expect($c['campo'] === 'user_id' ? (string) $visao->user_id : $visao->{$c['campo']})->toBe($esperado);
})->with([
    'user_id | o id da Bia | o id da Ana'                                                          => [['campo' => 'user_id', 'valor' => 'o id da Bia', 'gravado' => 'o id da Ana']],
    'table_key | a chave que a página /admin/convites usa | a chave que a página /admin/users usa' => [['campo' => 'table_key', 'valor' => 'a chave que a página /admin/convites usa', 'gravado' => 'a chave que a página /admin/users usa']],
    'is_public | true | false'                                                                     => [['campo' => 'is_public', 'valor' => 'true', 'gravado' => 'false']],
    'is_public | ausente do payload | false'                                                       => [['campo' => 'is_public', 'valor' => 'ausente do payload', 'gravado' => 'false']],
])->group('kit');

it('[CT-27] gerencia nome ordem e exclusão das próprias visões', function (): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana = usuarioDoKit('admin');
    $a   = visaoL5($ana, 'A', atributos: ['sort_order' => 1]);
    $b   = visaoL5($ana, 'B', atributos: ['sort_order' => 2]);
    $c   = visaoL5($ana, 'C', atributos: ['sort_order' => 3]);

    tabelaL5($ana)->callAction('manageTableViews', ['views' => [itemL5($c, ['name' => 'Primeira']), itemL5($a)]])->assertHasNoFormErrors();

    expect($c->fresh()->name)->toBe('Primeira');
    expect($c->fresh()->sort_order)->toBeLessThan($a->fresh()->sort_order);
    expect(TableView::min('sort_order'))->toBe($c->fresh()->sort_order);
    $this->assertModelMissing($b);
})->group('kit');

it('[CT-28] gestão com ids alheios ou inexistentes preserva a própria e a alheia', function (array $c): void {
    ligarVisoesDeTabela(true, $c['compartilhamento'] === 'ligado');
    concederVisoesDeTabela('admin');
    $ana    = usuarioDoKit('admin');
    $minha  = visaoL5($ana, 'Minha');
    $alheia = visaoL5(usuarioCom('admin'), 'Da Bia', atributos: ['is_public' => true]);
    $items  = match (true) {
        str_contains($c['payload'], '999999')    => [itemL5($minha), ['id' => 999999, 'name' => 'Fantasma']],
        str_contains($c['payload'], 'Roubada')   => [itemL5($minha), itemL5($alheia, ['name' => 'Roubada'])],
        str_contains($c['payload'], 'is_public') => [itemL5($minha, ['is_public' => true])],
        default                                  => [itemL5($minha)],
    };

    $gravadasAntes = TableView::query()->count();

    tabelaL5($ana)->callAction('manageTableViews', ['views' => $items])->assertHasNoFormErrors();

    expect($alheia->fresh())->name->toBe('Da Bia')->is_public->toBeTrue();
    $this->assertModelExists($minha);
    // QA-07 do gate: id alheio ou inexistente não cria visão nova nem com o nome forjado.
    expect(TableView::query()->count())->toBe($gravadasAntes)
        ->and(TableView::query()->whereIn('name', ['Roubada', 'Fantasma'])->exists())->toBeFalse();
    if (str_contains($c['payload'], 'is_public')) {
        expect($minha->fresh()->is_public)->toBeFalse();
    }
})->with([
    'desligado | "Minha" mais o id de "Da Bia" renomeada para "Roubada" | "Minha" continua gravada | "Da Bia" invisível à Ana'              => [['compartilhamento' => 'desligado', 'payload' => '"Minha" mais o id de "Da Bia" renomeada para "Roubada"', 'proprio' => '"Minha" continua gravada']],
    'ligado | "Minha" mais o id de "Da Bia" renomeada para "Roubada" | "Minha" continua gravada | "Da Bia" visível à Ana, mas não é dela'   => [['compartilhamento' => 'ligado', 'payload' => '"Minha" mais o id de "Da Bia" renomeada para "Roubada"', 'proprio' => '"Minha" continua gravada']],
    'desligado | só "Minha" na lista (pedido de apagar as demais) | "Minha" continua gravada | invisível'                                   => [['compartilhamento' => 'desligado', 'payload' => 'só "Minha" na lista (pedido de apagar as demais)', 'proprio' => '"Minha" continua gravada']],
    'ligado | só "Minha" na lista (pedido de apagar as demais) | "Minha" continua gravada | visível, mas não é dela'                        => [['compartilhamento' => 'ligado', 'payload' => 'só "Minha" na lista (pedido de apagar as demais)', 'proprio' => '"Minha" continua gravada']],
    'desligado | "Minha" mais o id 999999 (inexistente) renomeado para "Fantasma" | "Minha" continua gravada | inexistente'                 => [['compartilhamento' => 'desligado', 'payload' => '"Minha" mais o id 999999 (inexistente) renomeado para "Fantasma"', 'proprio' => '"Minha" continua gravada']],
    'ligado | "Minha" mais o id 999999 (inexistente) renomeado para "Fantasma" | "Minha" continua gravada | inexistente'                    => [['compartilhamento' => 'ligado', 'payload' => '"Minha" mais o id 999999 (inexistente) renomeado para "Fantasma"', 'proprio' => '"Minha" continua gravada']],
    'desligado | "Minha" com is_public true | "Minha" continua com is_public false | forjado com o compartilhamento desligado'              => [['compartilhamento' => 'desligado', 'payload' => '"Minha" com is_public true', 'proprio' => '"Minha" continua com is_public false']],
])->group('kit');

it('[CT-29] atualização pelo nome só muda a própria visão', function (array $c): void {
    ligarVisoesDeTabela(true, true);
    concederVisoesDeTabela('admin');
    $ana     = usuarioDoKit('admin');
    $propria = str_contains($c['ativa'], 'Minha');
    $visao   = visaoL5($propria ? $ana : usuarioCom('admin'), $propria ? 'Minha' : 'Da Bia', ['search' => 'zz'], ['is_public' => ! $propria]);
    $tabela  = tabelaL5($ana)->call('applyTableView', (string) $visao->id)->searchTable('ab');

    acionarL5($tabela, 'updateTableView');

    expect($visao->fresh()->state['search'])->toBe(trim($c['busca'], '"'));
})->with([
    'a visão "Minha" da Ana | "ab"'                => [['ativa' => 'a visão "Minha" da Ana', 'busca' => '"ab"']],
    'a visão "Da Bia" compartilhada da Bia | "zz"' => [['ativa' => 'a visão "Da Bia" compartilhada da Bia', 'busca' => '"zz"']],
])->group('kit');

it('[CT-30] lista e oferece aplicação somente das visões visíveis nesta página', function (array $c): void {
    ligarVisoesDeTabela(true, $c['compartilhamento'] === 'ligado');
    $ana   = usuarioDoKit('admin');
    $visao = visaoL5($c['dona'] === 'Ana' ? $ana : usuarioCom('admin'), 'Alvo', atributos: ['is_public' => $c['marcada'] === 'compartilhada'], pagina: $c['pagina_gravada'] === '/admin/users' ? ListUsers::class : ListConvites::class);

    $tabela = tabelaL5($ana);

    expect($tabela->instance()->getTableViews()->contains('id', $visao->id))->toBe($c['aparece'] === 'aparece');
    expect(in_array(aplicarL5($visao), oferecidasL5($tabela), true))->toBe($c['existe'] === 'existe');
})->with([
    'a | Ana | privada | /admin/users | desligado | aparece | existe'               => [['regra' => 'a', 'dona' => 'Ana', 'marcada' => 'privada', 'pagina_gravada' => '/admin/users', 'compartilhamento' => 'desligado', 'aparece' => 'aparece', 'existe' => 'existe']],
    'a | Ana | privada | /admin/users | ligado | aparece | existe'                  => [['regra' => 'a', 'dona' => 'Ana', 'marcada' => 'privada', 'pagina_gravada' => '/admin/users', 'compartilhamento' => 'ligado', 'aparece' => 'aparece', 'existe' => 'existe']],
    'b | Bia | privada | /admin/users | ligado | não aparece | não existe'          => [['regra' => 'b', 'dona' => 'Bia', 'marcada' => 'privada', 'pagina_gravada' => '/admin/users', 'compartilhamento' => 'ligado', 'aparece' => 'não aparece', 'existe' => 'não existe']],
    'c | Ana | privada | /admin/convites | desligado | não aparece | não existe'    => [['regra' => 'c', 'dona' => 'Ana', 'marcada' => 'privada', 'pagina_gravada' => '/admin/convites', 'compartilhamento' => 'desligado', 'aparece' => 'não aparece', 'existe' => 'não existe']],
    'e | Bia | compartilhada | /admin/users | ligado | aparece | existe'            => [['regra' => 'e', 'dona' => 'Bia', 'marcada' => 'compartilhada', 'pagina_gravada' => '/admin/users', 'compartilhamento' => 'ligado', 'aparece' => 'aparece', 'existe' => 'existe']],
    'f | Bia | compartilhada | /admin/users | desligado | não aparece | não existe' => [['regra' => 'f', 'dona' => 'Bia', 'marcada' => 'compartilhada', 'pagina_gravada' => '/admin/users', 'compartilhamento' => 'desligado', 'aparece' => 'não aparece', 'existe' => 'não existe']],
])->group('kit');

it('[CT-32] entrada ativa forjada não aplica visão invisível e recusa tipo errado', function (array $c): void {
    ligarVisoesDeTabela();
    $ana      = usuarioDoKit('admin');
    $minha    = visaoL5($ana, 'Minha', ['search' => 'ok']);
    $bia      = visaoL5(usuarioCom('admin'), 'Da Bia', ['search' => 'zz']);
    $convites = visaoL5($ana, 'Convites', ['search' => 'zz'], pagina: ListConvites::class);
    $apagada  = visaoL5($ana, 'Apagada');
    $apagada->delete();
    $valor = match (true) {
        str_contains($c['valor'], 'Minha')    => (string) $minha->id,
        str_contains($c['valor'], 'Da Bia')   => (string) $bia->id,
        str_contains($c['valor'], 'Convites') => (string) $convites->id,
        str_contains($c['valor'], 'apagada')  => (string) $apagada->id,
        str_contains($c['valor'], '[')        => ['1'],
        default                               => trim($c['valor'], '"'),
    };
    $tabela = tabelaL5($ana);

    if (is_array($valor)) {
        expect(fn () => $tabela->set('activeTableView', $valor))->toThrow(TypeError::class);
        $tabela->assertSet('tableSearch', trim($c['busca'], '"'));

        return;
    }

    str_contains($c['entrada'], 'define') ? $tabela->set('activeTableView', $valor) : $tabela->call('applyTableView', $valor);

    $tabela->assertSuccessful()->assertSet('tableSearch', trim($c['busca'], '"'));
})->with([
    'chama applyTableView com | o id de "Minha" | "ok" | controle'                      => [['entrada' => 'chama applyTableView com', 'valor' => 'o id de "Minha"', 'busca' => '"ok"']],
    'chama applyTableView com | o id de "Da Bia" | "" | outro usuário'                  => [['entrada' => 'chama applyTableView com', 'valor' => 'o id de "Da Bia"', 'busca' => '""']],
    'chama applyTableView com | o id de "Convites" da Ana | "" | dona, outra listagem'  => [['entrada' => 'chama applyTableView com', 'valor' => 'o id de "Convites" da Ana', 'busca' => '""']],
    'define activeTableView como | o id de "Da Bia" | "" | outro usuário (propriedade)' => [['entrada' => 'define activeTableView como', 'valor' => 'o id de "Da Bia"', 'busca' => '""']],
    'chama applyTableView com | o id de uma visão apagada | "" | registro removido'     => [['entrada' => 'chama applyTableView com', 'valor' => 'o id de uma visão apagada', 'busca' => '""']],
    'chama applyTableView com | "preset-xpto" | "" | preset inexistente'                => [['entrada' => 'chama applyTableView com', 'valor' => '"preset-xpto"', 'busca' => '""']],
    'define activeTableView como | "abc" | "" | fora do domínio'                        => [['entrada' => 'define activeTableView como', 'valor' => '"abc"', 'busca' => '""']],
    'define activeTableView como | "-1" | "" | fora do domínio'                         => [['entrada' => 'define activeTableView como', 'valor' => '"-1"', 'busca' => '""']],
    'define activeTableView como | ["1"] | "" | tipo errado'                            => [['entrada' => 'define activeTableView como', 'valor' => '["1"]', 'busca' => '""']],
])->group('kit');

it('[CT-33] URL aplica somente visão visível e não revela nome de visão invisível', function (array $c): void {
    $compartilhar = $c['compartilhamento'] === 'ligado';
    gravarConfiguracao('visoes_de_tabela', true);
    gravarConfiguracao('visoes_de_tabela_compartilhadas', $compartilhar);
    alinharConfiguracoesDoKit();
    $ana    = usuarioDoKit('admin');
    $visoes = [
        'Recorte da Ana'  => visaoL5($ana, 'Recorte da Ana', ['search' => 'zz']),
        'Recorte da Bia'  => visaoL5(usuarioCom('admin'), 'Recorte da Bia', ['search' => 'zz'], ['is_public' => $c['marcada'] === 'compartilhada']),
        'Convites da Ana' => visaoL5($ana, 'Convites da Ana', ['search' => 'zz'], pagina: ListConvites::class),
    ];
    $nome = trim($c['visao'], '"');
    $id   = (string) $visoes[$nome]->id;

    $resposta = $this->actingAs($ana)->get('/admin/users?view='.$id)->assertOk();
    $tabela   = tabelaL5($ana, consulta: ['view' => $id]);

    $tabela->assertSet('tableSearch', trim($c['busca'], '"'));
    $c['contem'] === 'contém' ? $resposta->assertSee($nome) : $resposta->assertDontSee($nome);
})->with([
    '"Recorte da Ana" | privada | desligado | "zz" | contém'         => [['visao' => '"Recorte da Ana"', 'marcada' => 'privada', 'compartilhamento' => 'desligado', 'busca' => '"zz"', 'contem' => 'contém']],
    '"Recorte da Bia" | privada | desligado | "" | não contém'       => [['visao' => '"Recorte da Bia"', 'marcada' => 'privada', 'compartilhamento' => 'desligado', 'busca' => '""', 'contem' => 'não contém']],
    '"Recorte da Bia" | compartilhada | desligado | "" | não contém' => [['visao' => '"Recorte da Bia"', 'marcada' => 'compartilhada', 'compartilhamento' => 'desligado', 'busca' => '""', 'contem' => 'não contém']],
    '"Recorte da Bia" | compartilhada | ligado | "zz" | contém'      => [['visao' => '"Recorte da Bia"', 'marcada' => 'compartilhada', 'compartilhamento' => 'ligado', 'busca' => '"zz"', 'contem' => 'contém']],
    '"Convites da Ana" | privada | desligado | "" | não contém'      => [['visao' => '"Convites da Ana"', 'marcada' => 'privada', 'compartilhamento' => 'desligado', 'busca' => '""', 'contem' => 'não contém']],
])->group('kit');

it('[CT-35] compartilhamento acompanha o interruptor na criação e na gestão', function (array $c): void {
    $ligado = $c['compartilhamento'] === 'ligado';
    ligarVisoesDeTabela(true, $ligado);
    concederVisoesDeTabela('admin');
    $ana    = usuarioDoKit('admin');
    $bia    = usuarioCom('admin');
    $minha  = visaoL5($ana, 'Minha');
    $antiga = visaoL5($ana, 'Antiga', atributos: ['is_public' => true]);
    $salvar = str_starts_with($c['acao'], 'salva');
    $acao   = $salvar ? 'saveTableView' : 'manageTableViews';
    $nome   = match (true) {
        str_contains($c['acao'], 'Forjada')     => 'Forjada',
        str_contains($c['acao'], 'Pública')     => 'Pública',
        str_contains($c['acao'], 'Só minha')    => 'Só minha',
        str_contains($c['acao'], 'Antiga 2')    => 'Antiga 2',
        str_contains($c['gravado'], '"Antiga"') => 'Antiga',
        default                                 => 'Minha',
    };
    $publica = str_contains($c['gravado'], 'is_public true');
    $tabela  = tabelaL5($ana)->mountAction($acao);
    $campos  = $tabela->instance()->getSchema($tabela->instance()->getMountedActionSchemaName())->getFlatFields(withHidden: true);
    $toggles = collect($campos)->filter(fn ($campo): bool => $campo->getName() === 'is_public');
    expect($toggles)->not->toBeEmpty();
    foreach ($toggles as $toggle) {
        expect($toggle->isVisible())->toBe($ligado);
    }
    $items = [itemL5($minha), itemL5($antiga)];
    if (! $salvar) {
        if ($nome === 'Antiga 2') {
            $items[1]['name'] = 'Antiga 2';
        } elseif (str_contains($c['acao'], 'desmarcando')) {
            $items[1]['is_public'] = false;
        } else {
            $items[0]['name']      = str_contains($c['acao'], 'renomeando só') ? 'Minha 2' : 'Minha';
            $items[0]['is_public'] = true;
        }
    }
    $dados = $salvar ? ['name' => $nome] : ['views' => $items];
    if ($salvar && ! str_contains($c['acao'], 'sem marcar')) {
        $dados['is_public'] = true;
    }

    $tabela->fillForm($dados)->callMountedAction()->assertHasNoFormErrors();

    $visao = TableView::where('name', $nome)->sole();
    expect($visao->is_public)->toBe($publica);
    if (str_contains($c['gravado'], 'Bia não a vê')) {
        expect(tabelaL5($bia)->instance()->getTableViews()->contains('id', $visao->id))->toBeFalse();
    }
})->with([
    'desligado | salva a visão "Forjada" com Compartilhar com todos forjado como true | não existe | "Forjada" está gravada com is_public false'                => [['compartilhamento' => 'desligado', 'acao' => 'salva a visão "Forjada" com Compartilhar com todos forjado como true', 'campo' => 'não existe', 'gravado' => '"Forjada" está gravada com is_public false']],
    'desligado | gerencia enviando "Minha" com Compartilhar com todos forjado como true | não existe | "Minha" está gravada com is_public false'                => [['compartilhamento' => 'desligado', 'acao' => 'gerencia enviando "Minha" com Compartilhar com todos forjado como true', 'campo' => 'não existe', 'gravado' => '"Minha" está gravada com is_public false']],
    'desligado | gerencia renomeando "Antiga" para "Antiga 2" | não existe | "Antiga 2" está gravada com is_public false (P-26)'                                => [['compartilhamento' => 'desligado', 'acao' => 'gerencia renomeando "Antiga" para "Antiga 2"', 'campo' => 'não existe', 'gravado' => '"Antiga 2" está gravada com is_public false (P-26)']],
    'desligado | gerencia renomeando só "Minha", com "Antiga" reenviada sem alteração | não existe | "Antiga" está gravada com is_public false (P-26: enviada)' => [['compartilhamento' => 'desligado', 'acao' => 'gerencia renomeando só "Minha", com "Antiga" reenviada sem alteração', 'campo' => 'não existe', 'gravado' => '"Antiga" está gravada com is_public false (P-26: enviada)']],
    'ligado | salva a visão "Pública" marcando Compartilhar com todos | existe | "Pública" está gravada com is_public true'                                     => [['compartilhamento' => 'ligado', 'acao' => 'salva a visão "Pública" marcando Compartilhar com todos', 'campo' => 'existe', 'gravado' => '"Pública" está gravada com is_public true']],
    'ligado | salva a visão "Só minha" sem marcar Compartilhar com todos | existe | "Só minha" está gravada com is_public false e a Bia não a vê'               => [['compartilhamento' => 'ligado', 'acao' => 'salva a visão "Só minha" sem marcar Compartilhar com todos', 'campo' => 'existe', 'gravado' => '"Só minha" está gravada com is_public false e a Bia não a vê']],
    'ligado | gerencia marcando "Minha" como Compartilhar com todos | existe | "Minha" está gravada com is_public true'                                         => [['compartilhamento' => 'ligado', 'acao' => 'gerencia marcando "Minha" como Compartilhar com todos', 'campo' => 'existe', 'gravado' => '"Minha" está gravada com is_public true']],
    'ligado | gerencia desmarcando o compartilhamento de "Antiga" | existe | "Antiga" está gravada com is_public false e a Bia não a vê'                        => [['compartilhamento' => 'ligado', 'acao' => 'gerencia desmarcando o compartilhamento de "Antiga"', 'campo' => 'existe', 'gravado' => '"Antiga" está gravada com is_public false e a Bia não a vê']],
])->group('kit');

it('[CT-36] escapa o nome compartilhado na lista e no rótulo ativo', function (array $c): void {
    ligarVisoesDeTabela(true, true);
    $ana    = usuarioDoKit('admin');
    $nome   = '<img src=x onerror=alert(1)>';
    $visao  = visaoL5(usuarioCom('admin'), $nome, atributos: ['is_public' => true]);
    $tabela = tabelaL5($ana);

    if (str_starts_with($c['acao'], 'aplica')) {
        $tabela->callAction(aplicarL5($visao));
    }

    expect($tabela->html())->toContain('&lt;img src=x onerror=alert(1)&gt;')->not->toContain($nome);
})->with([
    'abre /admin/users com a lista de visões | item da lista'              => [['acao' => 'abre /admin/users com a lista de visões']],
    'aplica a visão compartilhada da Bia | rótulo da visão ativa no botão' => [['acao' => 'aplica a visão compartilhada da Bia']],
])->group('kit');

it('[CT-37] desligar pela tela esconde sem apagar linhas nem marcas', function (): void {
    gravarConfiguracao('visoes_de_tabela', true);
    alinharConfiguracoesDoKit();
    $ana     = usuarioDoKit('admin');
    $padrao  = visaoL5($ana, 'Padrão', atributos: ['is_default' => true]);
    $publica = visaoL5(usuarioCom('admin'), 'Compartilhada', atributos: ['is_public' => true]);

    alternarVisoesL5($ana, false);

    expect(tabelaL5($ana)->instance()->getTableViewActions())->toBeEmpty();
    expect(TableView::count())->toBe(2);
    expect($padrao->fresh()->is_default)->toBeTrue();
    expect($publica->fresh()->is_public)->toBeTrue();
})->group('kit');

it('[CT-38] religar pela tela devolve as visões e seu estado original', function (): void {
    gravarConfiguracao('visoes_de_tabela', true);
    alinharConfiguracoesDoKit();
    $ana = usuarioDoKit('admin');
    $uma = visaoL5($ana, 'Uma', ['search' => 'zz']);
    visaoL5($ana, 'Outra');
    alternarVisoesL5($ana, false);

    alternarVisoesL5($ana, true);

    $tabela = tabelaL5($ana);
    expect($tabela->instance()->getTableViewActions())->not->toBeEmpty();
    $tabela->assertSee('Uma')->assertSee('Outra');
    expect(oferecidasL5($tabela))->toContain(aplicarL5($uma));
    expect($uma->fresh()->state['search'])->toBe('zz');
})->group('kit');

it('[CT-39] ignora partes removidas e tamanho de página mas aplica a busca', function (array $c): void {
    ligarVisoesDeTabela();
    ligarCarregamento('paginacao', 10);
    $ana    = usuarioDoKit('admin');
    $estado = match ($c['parte']) {
        'um filtro'           => ['filters' => ['xpto_removido' => ['value' => '1']]],
        'a ordenação'         => ['sort' => 'xpto_removida:asc'],
        'o agrupamento'       => ['grouping' => 'xpto_removido:asc'],
        'a aba'               => ['tab' => 'xpto_removida'],
        'o tamanho de página' => ['perPage' => str_starts_with($c['valor'], '9999') ? 9999 : 25],
        'as colunas visíveis' => ['columns' => [['name' => 'name', 'toggled' => true]]],
    };
    $visao  = visaoL5($ana, 'Antiga', ['search' => 'zz', ...$estado]);
    $tabela = tabelaL5($ana)->set('tableRecordsPerPage', 10);
    $padrao = $tabela->get('tableSort');
    $aba    = $tabela->get('activeTab');

    $tabela->call('applyTableView', (string) $visao->id)->assertSuccessful()->assertSet('tableSearch', 'zz');

    match ($c['parte']) {
        'um filtro'           => expect($tabela->get('tableFilters'))->not->toHaveKey('xpto_removido'),
        'a ordenação'         => $tabela->assertSet('tableSort', $padrao),
        'o agrupamento'       => $tabela->assertSet('tableGrouping', null),
        'a aba'               => $tabela->assertSet('activeTab', $aba),
        'o tamanho de página' => $tabela->assertSet('tableRecordsPerPage', 10),
        'as colunas visíveis' => expect($tabela->instance()->isTableColumnToggledHidden('email'))->toBeFalse(),
    };
})->with([
    'um filtro | "xpto_removido" => "1" | nenhum filtro "xpto_removido" na tabela'                             => [['parte' => 'um filtro', 'valor' => '"xpto_removido" => "1"', 'efeito' => 'nenhum filtro "xpto_removido" na tabela']],
    'a ordenação | a coluna "xpto_removida" | a ordenação da tabela é a ordenação padrão da listagem'          => [['parte' => 'a ordenação', 'valor' => 'a coluna "xpto_removida"', 'efeito' => 'a ordenação da tabela é a ordenação padrão da listagem']],
    'o agrupamento | o grupo "xpto_removido" | a tabela fica sem agrupamento'                                  => [['parte' => 'o agrupamento', 'valor' => 'o grupo "xpto_removido"', 'efeito' => 'a tabela fica sem agrupamento']],
    'a aba | "xpto_removida" | a aba ativa é a aba padrão da listagem'                                         => [['parte' => 'a aba', 'valor' => '"xpto_removida"', 'efeito' => 'a aba ativa é a aba padrão da listagem']],
    'o tamanho de página | 9999 (fora das opções) | o tamanho de página continua 10, o de antes de aplicar'    => [['parte' => 'o tamanho de página', 'valor' => '9999 (fora das opções)', 'efeito' => 'o tamanho de página continua 10, o de antes de aplicar']],
    'o tamanho de página | 25 (uma das opções) | o tamanho de página continua 10, o de antes de aplicar'       => [['parte' => 'o tamanho de página', 'valor' => '25 (uma das opções)', 'efeito' => 'o tamanho de página continua 10, o de antes de aplicar']],
    'as colunas visíveis | a lista sem a coluna "email" | a coluna "email", que a visão não conhecia, aparece' => [['parte' => 'as colunas visíveis', 'valor' => 'a lista sem a coluna "email"', 'efeito' => 'a coluna "email", que a visão não conhecia, aparece']],
])->group('kit');

it('[CT-40] aplica o recorte sobre os registros atuais', function (): void {
    ligarVisoesDeTabela();
    $ana    = usuarioDoKit('admin');
    $antigo = User::factory()->create(['name' => 'zz-antigo', 'email' => 'antigo@example.net']);
    $visao  = visaoL5($ana, 'Com zz', ['search' => 'zz']);
    $novo   = User::factory()->create(['name' => 'zz-novo', 'email' => 'novo@example.net']);
    $antigo->update(['name' => 'outro']);

    tabelaL5($ana)->call('applyTableView', (string) $visao->id)
        ->assertCanSeeTableRecords([$novo])->assertCanNotSeeTableRecords([$antigo]);
})->group('kit');

it('[CT-53] valida o nome novo em caracteres e recusa vazio após trim', function (array $c): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana = usuarioDoKit('admin');
    visaoL5($ana, 'Minha');
    $nome = match (true) {
        str_contains($c['nome'], '99 vezes')  => str_repeat('a', 99),
        str_contains($c['nome'], '100 vezes') => str_repeat('ã', 100),
        str_contains($c['nome'], '101 vezes') => str_repeat('a', 101),
        str_contains($c['nome'], 'espaços')   => '   ',
        default                               => '',
    };

    $tabela = tabelaL5($ana)->callAction('saveTableView', ['name' => $nome]);

    $c['resultado'] === 'aceito' ? $tabela->assertHasNoFormErrors() : $tabela->assertHasFormErrors(['name']);
    expect(TableView::where('user_id', $ana->id)->count())->toBe((int) $c['linhas']);
})->with([
    '"" | recusado com erro no nome | 1 | vazio'                                       => [['nome' => '""', 'resultado' => 'recusado com erro no nome', 'linhas' => '1']],
    '"   " (só espaços) | recusado com erro no nome | 1 | vazio depois de trim (P-21)' => [['nome' => '"   " (só espaços)', 'resultado' => 'recusado com erro no nome', 'linhas' => '1']],
    '99 vezes "a" | aceito | 2 | borda−1'                                              => [['nome' => '99 vezes "a"', 'resultado' => 'aceito', 'linhas' => '2']],
    '100 vezes "ã" | aceito | 2 | borda, em caractere multibyte'                       => [['nome' => '100 vezes "ã"', 'resultado' => 'aceito', 'linhas' => '2']],
    '101 vezes "a" | recusado com erro no nome | 1 | borda+1'                          => [['nome' => '101 vezes "a"', 'resultado' => 'recusado com erro no nome', 'linhas' => '1']],
])->group('kit');

it('[CT-54] aplica as mesmas regras de nome no gerenciamento', function (array $c): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana   = usuarioDoKit('admin');
    $visao = visaoL5($ana, 'Minha');
    $nome  = match (true) {
        str_contains($c['nome'], '100 vezes') => str_repeat('ã', 100),
        str_contains($c['nome'], '101 vezes') => str_repeat('a', 101),
        str_contains($c['nome'], 'espaços')   => '   ',
        default                               => '',
    };
    $tabela = tabelaL5($ana)->mountAction('manageTableViews');
    $tabela->fillForm(['views' => [itemL5($visao, ['name' => $nome])]]);
    $chave = array_key_first($tabela->instance()->getSchema($tabela->instance()->getMountedActionSchemaName())->getRawState()['views']);

    $tabela->callMountedAction();

    $c['resultado'] === 'aceito' ? $tabela->assertHasNoFormErrors() : $tabela->assertHasFormErrors(['views.'.$chave.'.name']);
    expect($visao->fresh()->name)->toBe($c['resultado'] === 'aceito' ? $nome : 'Minha');
})->with([
    '"" | recusado com erro no nome | "Minha"'                 => [['nome' => '""', 'resultado' => 'recusado com erro no nome', 'gravado' => '"Minha"']],
    '"   " (só espaços) | recusado com erro no nome | "Minha"' => [['nome' => '"   " (só espaços)', 'resultado' => 'recusado com erro no nome', 'gravado' => '"Minha"']],
    '100 vezes "ã" | aceito | 100 vezes "ã"'                   => [['nome' => '100 vezes "ã"', 'resultado' => 'aceito', 'gravado' => '100 vezes "ã"']],
    '101 vezes "a" | recusado com erro no nome | "Minha"'      => [['nome' => '101 vezes "a"', 'resultado' => 'recusado com erro no nome', 'gravado' => '"Minha"']],
])->group('kit');

it('[CT-55] recusa a 51ª visão contando somente as do usuário nesta página', function (array $c): void {
    expect(config('remember-table.max_views_per_table'))->toBe(50);
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana = usuarioDoKit('admin');
    for ($i = 0; $i < (int) $c['da_ana']; $i++) {
        visaoL5($ana, 'Visão '.$i);
    }
    if ($c['outras'] !== 'nenhuma outra visão') {
        $bia = usuarioCom('admin');
        for ($i = 0; $i < 5; $i++) {
            visaoL5($ana, 'Convites '.$i, pagina: ListConvites::class);
            visaoL5($bia, 'Bia '.$i);
        }
    }

    $tabela = tabelaL5($ana)->callAction('saveTableView', ['name' => 'Mais uma']);

    $chave = $tabela->instance()->getTableViewsKey();
    expect(TableView::where('user_id', $ana->id)->where('table_key', $chave)->count())->toBe((int) $c['total']);
    if (str_starts_with($c['resultado'], 'recusado')) {
        $tabela->assertActionHalted('saveTableView');
        expect(array_column(avisosDoFilament(), 'title'))->toContain(__('filament-remember-table::remember-table.notifications.limit_reached', ['limit' => 50]));
    } else {
        $tabela->assertHasNoFormErrors();
        $this->assertDatabaseHas('remember_table_views', ['user_id' => $ana->id, 'table_key' => $chave, 'name' => 'Mais uma']);
    }
})->with([
    '48 | nenhuma outra visão | aceito | 49 | borda−1'                                                        => [['da_ana' => '48', 'outras' => 'nenhuma outra visão', 'resultado' => 'aceito', 'total' => '49']],
    '49 | nenhuma outra visão | aceito | 50 | borda'                                                          => [['da_ana' => '49', 'outras' => 'nenhuma outra visão', 'resultado' => 'aceito', 'total' => '50']],
    '50 | nenhuma outra visão | recusado, com o aviso de limite | 50 | borda+1'                               => [['da_ana' => '50', 'outras' => 'nenhuma outra visão', 'resultado' => 'recusado, com o aviso de limite', 'total' => '50']],
    '49 | 5 visões da Ana em /admin/convites e 5 da Bia em /admin/users | aceito | 50 | as outras não contam' => [['da_ana' => '49', 'outras' => '5 visões da Ana em /admin/convites e 5 da Bia em /admin/users', 'resultado' => 'aceito', 'total' => '50']],
])->group('kit');

it('[CT-56] nomes repetidos convivem com recortes e ações distintos', function (): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana      = usuarioDoKit('admin');
    $primeira = visaoL5($ana, 'Ativos', ['search' => 'aa']);

    $tabela = tabelaL5($ana)->searchTable('bb')->callAction('saveTableView', ['name' => 'Ativos'])->assertHasNoFormErrors();

    $visoes = TableView::where('user_id', $ana->id)->where('name', 'Ativos')->orderBy('id')->get();
    expect($visoes)->toHaveCount(2);
    expect($primeira->fresh()->state['search'])->toBe('aa');
    expect($visoes->last()->state['search'])->toBe('bb');
    expect(aplicarL5($primeira))->not->toBe(aplicarL5($visoes->last()));
    expect(oferecidasL5($tabela))->toContain(aplicarL5($primeira), aplicarL5($visoes->last()));
})->group('kit');

it('[CT-57] aplica só a própria padrão e respeita cada recorte da URL', function (array $c): void {
    ligarVisoesDeTabela(true, true);
    $ana = usuarioDoKit('admin');
    visaoL5(str_contains($c['padrao'], 'Bia') ? usuarioCom('admin') : $ana, 'Padrão', ['search' => 'zz'], ['is_default' => true, 'is_public' => str_contains($c['padrao'], 'Bia')], str_contains($c['padrao'], '/admin/convites') ? ListConvites::class : ListUsers::class);
    $consulta = match (true) {
        str_contains($c['abertura'], '?view')     => ['view' => (string) visaoL5($ana, 'Outra', ['search' => 'ok'])->id],
        str_contains($c['abertura'], 'busca')     => ['search' => 'ab'],
        str_contains($c['abertura'], 'filtro')    => ['filters' => ['trashed' => ['value' => '1']]],
        str_contains($c['abertura'], 'ordenação') => ['sort' => 'email:asc'],
        str_contains($c['abertura'], 'aba')       => ['tab' => 'pendentes'],
        default                                   => [],
    };

    $tabela = tabelaL5($ana, consulta: $consulta)->assertSet('tableSearch', trim($c['busca'], '"'));

    if (isset($consulta['view'])) {
        $tabela->assertSet('activeTableView', $consulta['view']);
    } elseif (isset($consulta['filters'])) {
        expect((int) $tabela->get('tableFilters')['trashed']['value'])->toBe(1);
    } elseif (isset($consulta['sort'])) {
        $tabela->assertSet('tableSort', 'email:asc');
    } elseif (isset($consulta['tab'])) {
        $tabela->assertSet('activeTab', 'pendentes');
    } elseif (isset($consulta['search'])) {
        $tabela->assertSet('tableSearch', 'ab');
    }
})->with([
    'a visão padrão da Ana em /admin/users com a busca "zz" | /admin/users | "zz" | nada vem da URL | a própria, nesta listagem'                                                                   => [['padrao' => 'a visão padrão da Ana em /admin/users com a busca "zz"', 'abertura' => '/admin/users', 'busca' => '"zz"', 'recorte_da_url' => 'nada vem da URL']],
    'a visão padrão compartilhada da Bia em /admin/users com a busca "zz" | /admin/users | "" | nada vem da URL | de colega'                                                                       => [['padrao' => 'a visão padrão compartilhada da Bia em /admin/users com a busca "zz"', 'abertura' => '/admin/users', 'busca' => '""', 'recorte_da_url' => 'nada vem da URL']],
    'a visão padrão da Ana em /admin/convites com a busca "zz" | /admin/users | "" | nada vem da URL | a própria, de outra listagem'                                                               => [['padrao' => 'a visão padrão da Ana em /admin/convites com a busca "zz"', 'abertura' => '/admin/users', 'busca' => '""', 'recorte_da_url' => 'nada vem da URL']],
    'a visão padrão da Ana com a busca "zz" e a visão "Outra" com a busca "ok", em /admin/users | /admin/users?view={id de "Outra"} | "ok" | "Outra" é a visão ativa | visão pedida na URL (P-22)' => [['padrao' => 'a visão padrão da Ana com a busca "zz" e a visão "Outra" com a busca "ok", em /admin/users', 'abertura' => '/admin/users?view={id de "Outra"}', 'busca' => '"ok"', 'recorte_da_url' => '"Outra" é a visão ativa']],
    'a visão padrão da Ana em /admin/users com a busca "zz" | /admin/users com a busca "ab" na URL | "ab" | a busca da URL vale | busca na URL (P-14)'                                             => [['padrao' => 'a visão padrão da Ana em /admin/users com a busca "zz"', 'abertura' => '/admin/users com a busca "ab" na URL', 'busca' => '"ab"', 'recorte_da_url' => 'a busca da URL vale']],
    'a visão padrão da Ana em /admin/users com a busca "zz" | /admin/users com um filtro da tabela na URL | "" | o filtro da URL está ativo | filtro na URL (P-14)'                                => [['padrao' => 'a visão padrão da Ana em /admin/users com a busca "zz"', 'abertura' => '/admin/users com um filtro da tabela na URL', 'busca' => '""', 'recorte_da_url' => 'o filtro da URL está ativo']],
    'a visão padrão da Ana em /admin/users com a busca "zz" | /admin/users com a ordenação por e-mail na URL | "" | a ordenação é por e-mail | ordenação na URL (P-14)'                            => [['padrao' => 'a visão padrão da Ana em /admin/users com a busca "zz"', 'abertura' => '/admin/users com a ordenação por e-mail na URL', 'busca' => '""', 'recorte_da_url' => 'a ordenação é por e-mail']],
    'a visão padrão da Ana em /admin/users com a busca "zz" | /admin/users com uma aba que não é a padrão na URL | "" | a aba ativa é a da URL | aba na URL (P-14)'                                => [['padrao' => 'a visão padrão da Ana em /admin/users com a busca "zz"', 'abertura' => '/admin/users com uma aba que não é a padrão na URL', 'busca' => '""', 'recorte_da_url' => 'a aba ativa é a da URL']],
])->group('kit');

it('[CT-59] religar pela tela conserva compartilhamento desligado sem apagar a marca', function (): void {
    gravarConfiguracao('visoes_de_tabela', true);
    gravarConfiguracao('visoes_de_tabela_compartilhadas', true);
    alinharConfiguracoesDoKit();
    $ana   = usuarioDoKit('admin');
    $visao = visaoL5(usuarioCom('admin'), 'Do time', atributos: ['is_public' => true]);
    alternarVisoesL5($ana, false);

    alternarVisoesL5($ana, true);

    expect(configuracaoGravada('visoes_de_tabela_compartilhadas'))->toBeFalse();
    expect(tabelaL5($ana)->instance()->getTableViews()->contains('id', $visao->id))->toBeFalse();
    expect($visao->fresh()->is_public)->toBeTrue();
})->group('kit');

it('[CT-63] restaura filtro ordenação coluna oculta e aba gravados', function (): void {
    ligarVisoesDeTabela();
    $ana    = usuarioDoKit('admin');
    $visao  = visaoL5($ana, 'Completa', ['filters' => ['trashed' => ['value' => '1']], 'sort' => 'email:desc', 'columns' => [['name' => 'origem', 'toggled' => false]], 'tab' => 'pendentes']);
    $tabela = tabelaL5($ana);
    expect($tabela->instance()->isTableColumnToggledHidden('origem'))->toBeFalse();
    $tabela->assertSet('activeTab', 'todos');

    $tabela->call('applyTableView', (string) $visao->id);

    expect((int) $tabela->get('tableFilters')['trashed']['value'])->toBe(1);
    $tabela->assertSet('tableSort', 'email:desc')->assertSet('activeTab', 'pendentes');
    expect($tabela->instance()->isTableColumnToggledHidden('origem'))->toBeTrue();
})->group('kit');

it('[CT-64] gestão ignora dono página e organização forjados', function (array $c): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana     = usuarioDoKit('admin');
    $bia     = usuarioCom('admin');
    $visao   = visaoL5($ana, 'Minha', ['search' => 'zz']);
    $campo   = str_starts_with($c['campo'], 'user_id') ? 'user_id' : $c['campo'];
    $forjado = match ($campo) {
        'user_id'   => $bia->id,
        'table_key' => app(ListConvites::class)->getTableViewsKey(),
        'tenant_id' => '999',
    };

    $tabela = tabelaL5($ana)->callAction('manageTableViews', ['views' => [itemL5($visao, [$campo => $forjado, 'is_default' => $campo === 'user_id'])]])->assertHasNoFormErrors();

    $esperado = match ($campo) {
        'user_id'   => (string) $ana->id,
        'table_key' => $tabela->instance()->getTableViewsKey(),
        'tenant_id' => null,
    };
    expect($campo === 'user_id' ? (string) $visao->fresh()->user_id : $visao->fresh()->{$campo})->toBe($esperado);
    if ($campo === 'user_id') {
        tabelaL5($bia)->assertSet('tableSearch', '');
    } else {
        expect(oferecidasL5($tabela))->toContain(aplicarL5($visao));
    }
})->with([
    'user_id (com is_default true) | o id da Bia | o id da Ana | a Bia, ao abrir /admin/users, fica com a busca vazia'                                           => [['campo' => 'user_id (com is_default true)', 'valor' => 'o id da Bia', 'gravado' => 'o id da Ana', 'efeito' => 'a Bia, ao abrir /admin/users, fica com a busca vazia']],
    'table_key | a chave que a página /admin/convites usa | a chave que a página /admin/users usa | a ação de aplicar "Minha" existe para a Ana em /admin/users' => [['campo' => 'table_key', 'valor' => 'a chave que a página /admin/convites usa', 'gravado' => 'a chave que a página /admin/users usa', 'efeito' => 'a ação de aplicar "Minha" existe para a Ana em /admin/users']],
    'tenant_id | "999" | nulo | a ação de aplicar "Minha" existe para a Ana em /admin/users'                                                                     => [['campo' => 'tenant_id', 'valor' => '"999"', 'gravado' => 'nulo', 'efeito' => 'a ação de aplicar "Minha" existe para a Ana em /admin/users']],
])->group('kit');

it('[CT-72] master global também não altera a visão alheia', function (array $c): void {
    ligarVisoesDeTabela(true, true);
    $visao  = visaoL5(usuarioCom('admin'), 'Da Bia', ['search' => 'zz'], ['is_public' => true]);
    $tabela = tabelaL5(usuarioCom('master_global'))->call('applyTableView', (string) $visao->id)->searchTable('ab');

    if (str_contains($c['acao'], 'updateTableView')) {
        acionarL5($tabela, 'updateTableView');
    } else {
        acionarL5($tabela, 'manageTableViews', ['views' => str_contains($c['acao'], 'vazia') ? [] : [itemL5($visao, ['name' => 'Do master'])]]);
    }

    expect($visao->fresh())->name->toBe('Da Bia')->state->toMatchArray(['search' => 'zz']);
})->with([
    'gerencia as visões enviando "Da Bia" renomeada para "Do master"' => [['acao' => 'gerencia as visões enviando "Da Bia" renomeada para "Do master"']],
    'gerencia as visões com a lista vazia (pedido de apagar)'         => [['acao' => 'gerencia as visões com a lista vazia (pedido de apagar)']],
    'aciona pelo nome a ação updateTableView'                         => [['acao' => 'aciona pelo nome a ação updateTableView']],
])->group('kit');

it('[CT-73] ao reabrir perde a compartilhada que deixou de ser visível', function (array $c): void {
    gravarConfiguracao('visoes_de_tabela', true);
    gravarConfiguracao('visoes_de_tabela_compartilhadas', true);
    alinharConfiguracoesDoKit();
    $ana   = usuarioDoKit('admin');
    $bia   = usuarioCom('admin');
    $visao = visaoL5($bia, 'Pendentes', ['search' => 'zz'], ['is_public' => true]);
    tabelaL5($ana)->call('applyTableView', (string) $visao->id)->assertSet('activeTableView', (string) $visao->id);
    $visao->update(['state' => ['v' => 1, 'search' => 'yy']]);
    if (str_contains($c['evento'], 'desmarcou')) {
        tabelaL5($bia)->callAction('manageTableViews', ['views' => [itemL5($visao, ['is_public' => false])]]);
    } elseif (str_contains($c['evento'], 'apagou')) {
        tabelaL5($bia)->callAction('manageTableViews', ['views' => []]);
    } else {
        $this->actingAs($ana);
        Livewire::test(ConfiguracoesDoKit::class)->fillForm(['visoes_de_tabela_compartilhadas' => false])->call('save')->assertHasNoFormErrors();
        alinharConfiguracoesDoKit();
    }

    $tabela = tabelaL5($ana, consulta: ['view' => (string) $visao->id]);

    expect($tabela->get('activeTableView'))->not->toBe((string) $visao->id);
    expect($tabela->instance()->getTableViews()->contains('id', $visao->id))->toBeFalse();
    expect(oferecidasL5($tabela))->not->toContain(aplicarL5($visao));
    expect($tabela->get('tableSearch'))->not->toBe('yy');
})->with([
    'a Bia desmarcou o compartilhamento de "Pendentes"' => [['evento' => 'a Bia desmarcou o compartilhamento de "Pendentes"']],
    'a Bia apagou "Pendentes"'                          => [['evento' => 'a Bia apagou "Pendentes"']],
    'o compartilhamento foi desligado na aba Tabelas'   => [['evento' => 'o compartilhamento foi desligado na aba Tabelas']],
])->group('kit');

it('[CT-74] URL com visão removida inválida ou array abre sem visão', function (array $c): void {
    gravarConfiguracao('visoes_de_tabela', true);
    alinharConfiguracoesDoKit();
    $ana = usuarioDoKit('admin');
    visaoL5($ana, 'Minha', ['search' => 'zz']);
    $apagada = visaoL5($ana, 'Apagada');
    $apagada->delete();
    $consulta = match (true) {
        str_contains($c['consulta'], 'Apagada') => ['view' => (string) $apagada->id],
        str_contains($c['consulta'], '[]')      => ['view' => ['1']],
        default                                 => ['view' => 'abc'],
    };

    $this->actingAs($ana)->get('/admin/users?'.http_build_query($consulta))->assertOk();

    tabelaL5($ana, consulta: $consulta)->assertSet('tableSearch', '');
})->with([
    'view={id de "Apagada"} | registro removido' => [['consulta' => 'view={id de "Apagada"}']],
    'view=abc | fora do domínio'                 => [['consulta' => 'view=abc']],
    'view[]=1 | tipo errado'                     => [['consulta' => 'view[]=1']],
])->group('kit');

it('[CT-75] restaura agrupamento na listagem agrupável de teste', function (): void {
    ligarVisoesDeTabela();
    $ana    = usuarioDoKit('admin');
    $visao  = visaoL5($ana, 'Agrupada', ['grouping' => 'email:asc'], pagina: ListUsersAgrupavel::class);
    $tabela = tabelaL5($ana, ListUsersAgrupavel::class)->assertSet('tableGrouping', null);

    $tabela->call('applyTableView', (string) $visao->id);

    $tabela->assertSet('tableGrouping', 'email:asc');
    expect($tabela->instance()->getTableGrouping()->getId())->toBe('email');
})->group('kit');

it('[CT-77] master global não vê nem aplica visão privada alheia', function (array $c): void {
    ligarVisoesDeTabela();
    $visao    = visaoL5(usuarioCom('admin'), 'Da Bia', ['search' => 'zz']);
    $consulta = str_contains($c['entrada'], '?view') ? ['view' => (string) $visao->id] : [];
    $tabela   = tabelaL5(usuarioCom('master_global'), consulta: $consulta);

    if (str_contains($c['entrada'], 'chama')) {
        $tabela->call('applyTableView', (string) $visao->id);
    }

    $tabela->assertSet('tableSearch', '');
    expect($tabela->instance()->getTableViews()->contains('id', $visao->id))->toBeFalse();
})->with([
    'abre /admin/users'                         => [['entrada' => 'abre /admin/users']],
    'abre /admin/users?view={id de "Da Bia"}'   => [['entrada' => 'abre /admin/users?view={id de "Da Bia"}']],
    'chama applyTableView com o id de "Da Bia"' => [['entrada' => 'chama applyTableView com o id de "Da Bia"']],
])->group('kit');

it('[CT-78] páginas têm chaves distintas e a visão salva leva a da página aberta', function (): void {
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $tabela = tabelaL5(usuarioDoKit('admin'));

    $tabela->callAction('saveTableView', ['name' => 'Desta página'])->assertHasNoFormErrors();

    $chaves = [$tabela->instance()->getTableViewsKey(), app(ListUsersApp::class)->getTableViewsKey(), app(ListConvites::class)->getTableViewsKey()];
    expect(array_unique($chaves))->toHaveCount(3);
    expect(TableView::where('name', 'Desta página')->sole()->table_key)->toBe($chaves[0]);
})->group('kit');

it('[CT-79] Atualizar e Gerenciar preservam a visibilidade do pacote sob o interruptor', function (array $c): void {
    ligarVisoesDeTabela(true, true);
    concederVisoesDeTabela('admin');
    $ana    = usuarioDoKit('admin', 'ana@example.com');
    $alheia = str_contains($c['situacao'], 'Bia');
    $visao  = $alheia
        ? visaoL5(usuarioCom('admin'), 'Da Bia', ['search' => 'zz'], ['is_public' => true])
        : visaoL5($ana, 'Minha', ['search' => 'zz']);
    $tabela = tabelaL5($ana);

    if ($c['ativa']) {
        $tabela->call('applyTableView', (string) $visao->id)
            ->assertSet('activeTableView', (string) $visao->id)
            ->set('tableSearch', 'x');
    }
    ligarVisoesDeTabela($c['visoes'], true);

    if ($c['visivel']) {
        $tabela->assertActionVisible($c['acao']);
    } else {
        $tabela->assertActionHidden($c['acao']);
    }
})->with([
    'ligadas | sem visão ativa | updateTableView | oculta'               => [['visoes' => true, 'situacao' => 'Minha sem visão ativa', 'ativa' => false, 'acao' => 'updateTableView', 'visivel' => false]],
    'ligadas | visão ativa alheia | updateTableView | oculta'            => [['visoes' => true, 'situacao' => 'Da Bia ativa e modificada', 'ativa' => true, 'acao' => 'updateTableView', 'visivel' => false]],
    'ligadas | própria ativa e modificada | updateTableView | visível'   => [['visoes' => true, 'situacao' => 'Minha ativa e modificada', 'ativa' => true, 'acao' => 'updateTableView', 'visivel' => true]],
    'ligadas | sem visão própria | manageTableViews | oculta'            => [['visoes' => true, 'situacao' => 'Da Bia compartilhada visível', 'ativa' => false, 'acao' => 'manageTableViews', 'visivel' => false]],
    'ligadas | com visão própria | manageTableViews | visível'           => [['visoes' => true, 'situacao' => 'Minha', 'ativa' => false, 'acao' => 'manageTableViews', 'visivel' => true]],
    'desligadas | própria ativa e modificada | updateTableView | oculta' => [['visoes' => false, 'situacao' => 'Minha ativa e modificada', 'ativa' => true, 'acao' => 'updateTableView', 'visivel' => false]],
    'desligadas | com visão própria | manageTableViews | oculta'         => [['visoes' => false, 'situacao' => 'Minha', 'ativa' => false, 'acao' => 'manageTableViews', 'visivel' => false]],
])->group('kit');

/**
 * @return array<int, array{query: string}>
 */
function consultasAsVisoesL5(): array
{
    return array_values(array_filter(DB::getQueryLog(), static fn (array $consulta): bool => str_contains(strtolower($consulta['query']), 'remember_table_views')));
}

it('[CT-80] aliases do pacote não são públicos nem alcançáveis pelo cliente e o nome público é guardado', function (array $c): void {
    ligarVisoesDeTabela($c['visoes']);
    concederVisoesDeTabela('admin');
    $ana = usuarioDoKit('admin', 'ana@example.com');
    visaoL5($ana, 'Ativos', ['search' => 'zz']);
    $tabela   = tabelaL5($ana);
    $controle = $c['metodo'] === 'getTableViews';

    if ($controle) {
        expect(fn () => $tabela->call('getHeaderActions'))
            ->toThrow(MethodNotFoundException::class);
    }
    DB::enableQueryLog();
    DB::flushQueryLog();

    // Com as visões ligadas, todo request consulta a tabela no `booted` (a visão padrão): a linha
    // de referência é um `$refresh`, e a chamada recusada não pode consultar mais do que ele.
    $consultasDeReferencia = 0;

    if ($c['visoes']) {
        $tabela->call('$refresh');
        $consultasDeReferencia = count(consultasAsVisoesL5());
        DB::flushQueryLog();
    }

    try {
        if ($controle) {
            $tabela->call($c['metodo']);
            expect($tabela->instance()->getTableViews())->toBeEmpty();
        } else {
            expect(fn () => $tabela->call($c['metodo']))
                ->toThrow(MethodNotFoundException::class);
        }

        expect(count(consultasAsVisoesL5()))->toBeLessThanOrEqual($consultasDeReferencia);
        expect((new ReflectionMethod(ListUsers::class, $c['metodo']))->isPublic())->toBe($controle);
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
})->with([
    'desligadas | getTableViewsDoPacote'            => [['visoes' => false, 'metodo' => 'getTableViewsDoPacote']],
    'desligadas | getTableViewActionsDoPacote'      => [['visoes' => false, 'metodo' => 'getTableViewActionsDoPacote']],
    'desligadas | bootedHasRememberTableDoPacote'   => [['visoes' => false, 'metodo' => 'bootedHasRememberTableDoPacote']],
    'desligadas | saveTableViewActionDoPacote'      => [['visoes' => false, 'metodo' => 'saveTableViewActionDoPacote']],
    'desligadas | updateTableViewActionDoPacote'    => [['visoes' => false, 'metodo' => 'updateTableViewActionDoPacote']],
    'desligadas | manageTableViewsActionDoPacote'   => [['visoes' => false, 'metodo' => 'manageTableViewsActionDoPacote']],
    'desligadas | resetTableViewActionDoPacote'     => [['visoes' => false, 'metodo' => 'resetTableViewActionDoPacote']],
    'ligadas | getTableViewsDoPacote'               => [['visoes' => true, 'metodo' => 'getTableViewsDoPacote']],
    'ligadas | getTableViewActionsDoPacote'         => [['visoes' => true, 'metodo' => 'getTableViewActionsDoPacote']],
    'desligadas | getTableViews | controle público' => [['visoes' => false, 'metodo' => 'getTableViews']],
])->group('kit');
