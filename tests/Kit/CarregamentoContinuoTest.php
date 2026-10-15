<?php

use App\Filament\Admin\Resources\AgentesIa\Pages\ListAgentesIa;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Infra\Resources\AiRuns\Pages\ListAiRuns;
use App\Filament\Infra\Resources\ComposerReleasePackages\Pages\ListComposerReleasePackages;
use App\Models\User;
use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
use Asignua\FilamentInfiniteScroll\InfiniteScrollRegistry;
use BezhanSalleh\FilamentExceptions\Resources\ExceptionResource\Pages\ListExceptions;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tapp\FilamentAuditing\Filament\Resources\Audits\Pages\ListAudits;

/** Fixture local: o lote permite somente este arquivo, sem criar outro em Fixtures. */
class ListUsersAgrupaveisL7 extends ListUsers
{
    public function table(Table $table): Table
    {
        return parent::table($table)->groups(['name']);
    }
}

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Inclui a persona no total, sem eventos falsificados e com inserção em lote. */
function usuariosDoCarregamentoL7(int $total, string $papel = 'master_global'): User
{
    $usuario = usuarioDoKit($papel, 'ana@example.com');
    $usuario->update(['name' => 'Pessoa Ana']);

    DB::table('users')->insert(array_map(fn (int $indice): array => [
        'uuid'              => (string) Str::uuid(),
        'name'              => sprintf('Pessoa %03d', $indice),
        'email'             => sprintf('pessoa%03d@example.com', $indice),
        'password'          => $usuario->getRawOriginal('password'),
        'email_verified_at' => now(),
        'created_at'        => now(),
        'updated_at'        => now(),
    ], range(1, $total - 1)));

    expect(User::query()->count())->toBe($total);

    return $usuario;
}

function abrirUsuariosDoCarregamentoL7(User $usuario, string $pagina = ListUsers::class): Testable
{
    test()->actingAs($usuario)->get('/admin/users')->assertOk();
    noPainelBootado('admin');

    return Livewire::test($pagina)->loadTable();
}

/** Conta as linhas renderizadas, pois assertCountTableRecords() conta o total da consulta. */
function linhasDoCarregamentoL7(Testable $componente): int
{
    $prefixo = $componente->instance()->getId().'.table.records.';

    return elementosDoCarregamentoL7($componente->html(), '//tr[starts-with(@*[name()="wire:key"], "'.$prefixo.'")]')->length;
}

/** Consulta o HTML com o DOM nativo, sem dependência adicional de teste. */
function elementosDoCarregamentoL7(string|DOMNode $html, string $expressao): DOMNodeList
{
    if ($html instanceof DOMNode) {
        return (new DOMXPath($html->ownerDocument))->query($expressao, $html);
    }

    $documento       = new DOMDocument;
    $errosAnteriores = libxml_use_internal_errors(true);
    try {
        $documento->loadHTML('<?xml encoding="UTF-8">'.$html);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($errosAnteriores);
    }

    return (new DOMXPath($documento))->query($expressao);
}

function rodapeDoCarregamentoL7(Testable $componente): DOMElement
{
    $rodape = elementosDoCarregamentoL7($componente->html(), '//*[contains(concat(" ", normalize-space(@class), " "), " fi-ta-infinite-scroll ")]');
    expect($rodape->length)->toBe(1);

    return $rodape->item(0);
}

function pedirBlocosDoCarregamentoL7(Testable $componente, int $vezes): void
{
    for ($pedido = 0; $pedido < $vezes; $pedido++) {
        $componente->call('infiniteScrollLoadMore');
    }
}

function afirmarTextoDoCarregamentoL7(DOMElement $rodape, bool $teto, int $total): void
{
    $texto = $rodape->textContent;
    if ($teto) {
        expect($texto)->toMatch('/busca|filtro/iu');
    } else {
        expect($texto)->toContain("Todos os {$total} registros foram carregados");
        expect($texto)->not->toMatch('/busca|filtro/iu');
    }
}

it('[CT-41] toda tabela recebe o carregamento continuo no modo escolhido', function (string $rota, string $pagina, string $modo, InfiniteScrollMode $modoPacote): void {
    ligarCarregamento($modo);
    $painel = str_starts_with($rota, '/infra') ? 'infra' : 'admin';
    $this->actingAs(usuarioDoKit('master_global'))->get($rota)->assertOk();
    noPainelBootado($painel);

    $componente = Livewire::test($pagina)->loadTable();
    $tabela     = $componente->instance()->getTable();

    expect(app(InfiniteScrollRegistry::class)->get($tabela)?->mode)->toBe($modoPacote);
    expect($tabela->getPaginationPageOptions())->toBe([7]);
})->with([
    '/admin/users | master_global | rolagem | Scroll | kit'                     => ['/admin/users', ListUsers::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/admin/convites | master_global | rolagem | Scroll | kit'                  => ['/admin/convites', ListConvites::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/admin/agentes-ia | master_global | rolagem | Scroll | kit'                => ['/admin/agentes-ia', ListAgentesIa::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/admin/shield/roles | master_global | rolagem | Scroll | kit'              => ['/admin/shield/roles', ListRoles::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/infra/ai-runs | master_global | rolagem | Scroll | kit'                   => ['/infra/execucoes-ia', ListAiRuns::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/infra/composer-release-packages | master_global | rolagem | Scroll | kit' => ['/infra/composer-release-packages', ListComposerReleasePackages::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/infra/audits | master_global | rolagem | Scroll | plugin'                 => ['/infra/audits', ListAudits::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/infra/exceptions | master_global | rolagem | Scroll | plugin'             => ['/infra/exceptions', ListExceptions::class, 'rolagem', InfiniteScrollMode::Scroll],
    '/admin/users | master_global | botao | Button | kit'                       => ['/admin/users', ListUsers::class, 'botao', InfiniteScrollMode::Button],
    '/infra/audits | master_global | botao | Button | plugin'                   => ['/infra/audits', ListAudits::class, 'botao', InfiniteScrollMode::Button],
])->group('kit');

it('[CT-42] o rodape mostra o modo escolhido e o primeiro bloco sem seletor', function (string $modo, string $modoPacote): void {
    ligarCarregamento($modo);
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7(10));

    expect(linhasDoCarregamentoL7($componente))->toBe(7);
    $rodape = rodapeDoCarregamentoL7($componente);
    expect($rodape->getAttribute('data-mode'))->toBe($modoPacote);
    expect($rodape->getAttribute('data-loaded'))->toBe('7');
    if ($modo === 'rolagem') {
        expect(elementosDoCarregamentoL7($rodape, './/*[@*[starts-with(name(), "x-intersect")]] | self::*[@*[starts-with(name(), "x-intersect")]]')->length)->toBe(1);
        expect($rodape->textContent)->not->toContain('Carregar mais');
    } else {
        $botao = elementosDoCarregamentoL7($rodape, './/button[@*[name()="wire:click"]="infiniteScrollLoadMore"]');
        expect($botao->length)->toBe(1);
        expect($botao->item(0)->textContent)->toContain('Carregar mais');
    }
    expect(elementosDoCarregamentoL7($componente->html(), '//select[@*[name()="wire:model.live"]="tableRecordsPerPage"]')->length)->toBe(0);
})->with([
    'rolagem | scroll | o rodapé tem o sentinela de interseção e não tem o botão "Carregar mais"' => ['rolagem', 'scroll'],
    'botao | button | o rodapé tem o botão "Carregar mais" ligado ao pedido do próximo bloco'     => ['botao', 'button'],
])->group('kit');

it('[CT-43] cada carregamento acrescenta um bloco de Linhas por pagina', function (int $vezes, int $linhas): void {
    ligarCarregamento('botao');
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7(30));
    expect(linhasDoCarregamentoL7($componente))->toBe(7);

    pedirBlocosDoCarregamentoL7($componente, $vezes);

    expect(linhasDoCarregamentoL7($componente))->toBe($linhas);
})->with([
    '1 | 14 | segundo bloco'                                   => [1, 14],
    '2 | 21 | terceiro bloco (dobrar daria 28)'                => [2, 21],
    '4 | 30 | último bloco incompleto, e pedido além do total' => [4, 30],
])->group('kit');

it('[CT-44] para em 500 e so pede busca ou filtro quando ha mais registros', function (int $total, int $bloco, int $vezes, ?int $linhas, string $estado): void {
    expect(config('filament-infinite-scroll.max_records'))->toBe(500);
    ligarCarregamento('botao', $bloco);
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7($total));

    pedirBlocosDoCarregamentoL7($componente, $vezes);

    if ($linhas === null) {
        expect(linhasDoCarregamentoL7($componente))->toBeLessThanOrEqual(500);
    } else {
        expect(linhasDoCarregamentoL7($componente))->toBe($linhas);
    }
    $rodape = rodapeDoCarregamentoL7($componente);
    if ($estado === 'proximo') {
        $botao = elementosDoCarregamentoL7($rodape, './/button[@*[name()="wire:click"]="infiniteScrollLoadMore"]');
        expect($botao->length)->toBe(1);
        expect($botao->item(0)->textContent)->toContain('Carregar mais');
        expect($rodape->textContent)->not->toMatch('/busca|filtro/iu');
    } else {
        afirmarTextoDoCarregamentoL7($rodape, $estado === 'teto', $total);
    }
})->with([
    '501 | 100 | 3 | 400 | oferece o próximo bloco e não pede busca nem filtro | linhas: borda−1 (um bloco antes)'  => [501, 100, 3, 400, 'proximo'],
    '501 | 100 | 4 | 500 | pede busca ou filtro para restringir a lista | linhas: borda; total: borda+1'            => [501, 100, 4, 500, 'teto'],
    '501 | 100 | 5 | 500 | pede busca ou filtro para restringir a lista | linhas: borda+1 (pedido além do teto)'    => [501, 100, 5, 500, 'teto'],
    '501 | 99 | 6 | no máximo 500 | pede busca ou filtro para restringir a lista | bloco que não divide o teto'     => [501, 99, 6, null, 'teto'],
    '499 | 100 | 4 | 499 | diz que todos os 499 foram carregados e não pede busca nem filtro | total: borda−1'      => [499, 100, 4, 499, 'fim'],
    '500 | 100 | 4 | 500 | diz que todos os 500 foram carregados e não pede busca nem filtro | total: borda (P-23)' => [500, 100, 4, 500, 'fim'],
])->group('kit');

it('[CT-45] entrada forjada preserva os sete primeiros usuarios sem excecao', function (string $propriedade, mixed $valor): void {
    ligarCarregamento('rolagem');
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7(30));
    expect(linhasDoCarregamentoL7($componente))->toBe(7);
    $primeiros = $componente->instance()->getTableRecords()->getCollection();
    expect($primeiros)->toHaveCount(7);

    $componente->set($propriedade, $valor)->assertSuccessful()->assertHasNoErrors();

    expect(linhasDoCarregamentoL7($componente))->toBe(7);
    $componente->assertCanSeeTableRecords($primeiros, inOrder: true);
    expect($componente->instance()->getTableRecords()->modelKeys())->toBe($primeiros->modelKeys());
})->with([
    'tableRecordsPerPage | 50000 | acima do teto'                         => ['tableRecordsPerPage', 50000],
    'tableRecordsPerPage | 501 | teto+1'                                  => ['tableRecordsPerPage', 501],
    'tableRecordsPerPage | 21 | vários blocos de uma vez'                 => ['tableRecordsPerPage', 21],
    'tableRecordsPerPage | 15 | não múltiplo do bloco'                    => ['tableRecordsPerPage', 15],
    'tableRecordsPerPage | 0 | zero'                                      => ['tableRecordsPerPage', 0],
    'tableRecordsPerPage | -7 | negativo'                                 => ['tableRecordsPerPage', -7],
    'tableRecordsPerPage | "abc" | tipo errado (texto)'                   => ['tableRecordsPerPage', 'abc'],
    'tableRecordsPerPage | [7] | tipo errado (lista)'                     => ['tableRecordsPerPage', [7]],
    'tableRecordsPerPage | "all" | valor especial de "todos" do Filament' => ['tableRecordsPerPage', 'all'],
    'paginators.page | 3 | página forjada no modo contínuo'               => ['paginators.page', 3],
])->group('kit');

it('[CT-46] pedir o proximo bloco numa tabela paginada nao muda nada', function (array $argumentos): void {
    ligarCarregamento('paginacao', 5);
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7(30));
    expect(linhasDoCarregamentoL7($componente))->toBe(5);

    expect(fn () => $componente->call('infiniteScrollLoadMore', ...$argumentos))
        ->toThrow(MethodNotFoundException::class, 'Unable to call component method. Public method [infiniteScrollLoadMore] not found on component');

    expect(linhasDoCarregamentoL7($componente))->toBe(5);
    expect(count($componente->instance()->getTable()->getPaginationPageOptions()))->toBeGreaterThan(1);
})->with([
    'sem argumento'       => [[]],
    'com o argumento 999' => [[999]],
])->group('kit');

it('[CT-47] mudar o recorte depois de tres blocos volta a um bloco', function (string $mudanca): void {
    ligarCarregamento('botao');
    ligarVisoesDeTabela();
    concederVisoesDeTabela('admin');
    $ana = usuariosDoCarregamentoL7($mudanca === 'aba' ? 31 : 30, 'admin');
    if ($mudanca === 'aba') {
        DB::table('users')->where('id', '!=', $ana->getKey())->update(['aprovacao_pendente' => true]);
    }
    $pagina = $mudanca === 'agrupa' ? ListUsersAgrupaveisL7::class : ListUsers::class;
    $this->actingAs($ana)->get('/admin/users')->assertOk();
    noPainelBootado('admin');
    $visao      = visaoGravada($ana, $pagina, ['search' => 'Pessoa'], ['name' => 'Todos', 'table_key' => chaveDaTabela($pagina)]);
    $componente = Livewire::test($pagina)->loadTable();
    if ($mudanca === 'aba') {
        $componente->set('activeTab', 'pendentes');
    }
    if ($mudanca === 'limpa') {
        $componente->filterTable('trashed', true);
    }
    pedirBlocosDoCarregamentoL7($componente, 2);
    expect(linhasDoCarregamentoL7($componente))->toBe(21);

    match ($mudanca) {
        'busca'  => $componente->searchTable('Pessoa'),
        'filtro' => $componente->filterTable('trashed', true),
        'ordena' => $componente->sortTable('email', 'desc'),
        'agrupa' => $componente->set('tableGrouping', 'name:asc'),
        'aba'    => $componente->set('activeTab', 'todos'),
        'visao'  => $componente->call('applyTableView', (string) $visao->getKey()),
        'limpa'  => $componente->callAction('resetTableView'),
    };

    expect(linhasDoCarregamentoL7($componente))->toBe(7);
})->with([
    'busca por um termo que casa com os 30'                                                        => ['busca'],
    'aplica um filtro que casa com os 30'                                                          => ['filtro'],
    'ordena por outra coluna'                                                                      => ['ordena'],
    'agrupa por uma coluna'                                                                        => ['agrupa'],
    'troca para uma aba que lista os 30'                                                           => ['aba'],
    'aplica a visão "Todos"'                                                                       => ['visao'],
    'limpa a tabela, que estava com um filtro que casa com os 30, aplicado antes dos dois pedidos' => ['limpa'],
])->group('kit');

it('[CT-69] ignora o argumento do pedido do proximo bloco no modo continuo', function (): void {
    ligarCarregamento('rolagem');
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7(30));
    expect(linhasDoCarregamentoL7($componente))->toBe(7);

    $componente->call('infiniteScrollLoadMore', 999);

    expect(linhasDoCarregamentoL7($componente))->toBe(14);
})->group('kit');

it('[CT-70] nao oferece pedidos no teto nem no fim da lista', function (string $modo, int $bloco, int $total, int $vezes, bool $teto): void {
    ligarCarregamento($modo, $bloco);
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7($total));

    pedirBlocosDoCarregamentoL7($componente, $vezes);

    $rodape = rodapeDoCarregamentoL7($componente);
    expect($rodape->getAttribute('data-has-more'))->toBe('0');
    expect(elementosDoCarregamentoL7($rodape, './/*[@*[starts-with(name(), "x-intersect")]] | self::*[@*[starts-with(name(), "x-intersect")]]')->length)->toBe(0);
    expect(elementosDoCarregamentoL7($rodape, './/button')->length)->toBe(0);
    expect($rodape->textContent)->not->toContain('Carregar mais');
    afirmarTextoDoCarregamentoL7($rodape, $teto, $total);
})->with([
    'rolagem | 100 | 501 | 4 | pede busca ou filtro para restringir a lista | teto' => ['rolagem', 100, 501, 4, true],
    'botao | 100 | 501 | 4 | pede busca ou filtro para restringir a lista | teto'   => ['botao', 100, 501, 4, true],
    'rolagem | 7 | 30 | 4 | diz que todos os 30 foram carregados | fim da lista'    => ['rolagem', 7, 30, 4, false],
    'botao | 7 | 30 | 4 | diz que todos os 30 foram carregados | fim da lista'      => ['botao', 7, 30, 4, false],
])->group('kit');

it('[CT-71] em paginacao mostra cinco usuarios com paginas e seletor sem rodape continuo', function (): void {
    ligarCarregamento('paginacao', 5);
    $componente = abrirUsuariosDoCarregamentoL7(usuariosDoCarregamentoL7(10));

    expect(linhasDoCarregamentoL7($componente))->toBe(5);
    $paginas = elementosDoCarregamentoL7($componente->html(), '//ol[contains(@class,"fi-pagination-items")]');
    expect($paginas->length)->toBe(1);
    expect($paginas->item(0)->textContent)->toContain('1', '2');
    expect(elementosDoCarregamentoL7($componente->html(), '//*[contains(@class,"fi-ta-infinite-scroll")]')->length)->toBe(0);
    expect($componente->html())->not->toContain('Carregar mais');
    expect(elementosDoCarregamentoL7($componente->html(), '//select[@*[name()="wire:model.live"]="tableRecordsPerPage"]')->length)->toBeGreaterThan(0);
})->group('kit');
