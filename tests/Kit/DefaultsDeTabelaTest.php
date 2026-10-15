<?php

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use Asignua\FilamentInfiniteScroll\InfiniteScrollRegistry;
use Asmit\ResizedColumn\ResizedColumnTableRegistry;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Tables\Table;
use Livewire\Livewire;
use Tapp\FilamentAuditing\Filament\Resources\Audits\Pages\ListAudits;

/**
 * Os defaults de TODA tabela do projeto, agora vindos da configuração.
 *
 * IDs de CT em `wikis/specs/feat/settings-do-kit/settings-do-kit/04-casos-de-teste.md`.
 * Estas quatro chaves fecham o TODO que vivia no topo de `ConfiguraFilamentGlobal`
 * — com a ressalva de ADR-09: "densidade de tabela" não existe no Filament 5, e o
 * que ficou configurável no lugar é o `striped()`.
 *
 * O que se observa é o objeto `Table` depois do `Table::configureUsing()` global,
 * porque é ele que a tela renderiza. `Table::make()` aplica os `configureUsing`
 * registrados, então não é preciso montar componente Livewire nem bootar painel:
 * a asserção é sobre a configuração resultante, que é exatamente o que muda.
 *
 * A ponta de ponta a ponta — config gravada no banco chegando à tabela — é CT-30,
 * em `ConfiguracoesDoKitTest`.
 */

/** Uma tabela do kit, já passada pelo `configureUsing` global. */
function tabelaDoKit(): Table
{
    ResizedColumnTableRegistry::reset();

    return Table::make(new ListUsers);
}

/**
 * CT-17 — a paginação default é a configurada, inclusive nas bordas.
 *
 * `10` está na tabela de propósito, e não por preguiça: é o valor de fábrica de
 * hoje (`ConfiguraFilamentGlobal.php`, antes desta feature), então é a linha que
 * NÃO discrimina — ela existe como âncora de regressão. Quem discrimina são `25`,
 * `1` e `100`.
 */
it('usa a paginacao configurada como default da tabela', function (int $paginacao): void {
    config(['kit.tabelas.paginacao' => $paginacao]);

    expect(tabelaDoKit()->getDefaultPaginationPageOption())->toBe($paginacao);
})->with([
    'mínimo'                     => 1,
    'valor de fábrica de hoje'   => 10,
    'diferente do de fábrica'    => 25,
    'máximo oferecido na tela'   => 100,
])->group('kit');

/**
 * CT-18 — cada interruptor liga e desliga o seu efeito.
 *
 * Um interruptor por linha, com os dois estados. `persistir_filtros` afere os
 * QUATRO `persist*` de uma vez porque eles são a mesma promessa ("o que eu
 * filtrei continua filtrado"): ligar dois e esquecer dois produz uma tela que
 * lembra o filtro e esquece a busca, que é pior que não lembrar nada.
 */
it('liga e desliga as linhas listradas conforme a configuracao', function (bool $ligado): void {
    config(['kit.tabelas.listrada' => $ligado]);

    expect(tabelaDoKit()->isStriped())->toBe($ligado);
})->with([
    'ligado'    => true,
    'desligado' => false,
])->group('kit');

it('liga e desliga as quatro persistencias de recorte juntas', function (bool $ligado): void {
    config(['kit.tabelas.persistir_filtros' => $ligado]);

    $tabela = tabelaDoKit();

    expect($tabela->persistsFiltersInSession())->toBe($ligado)
        ->and($tabela->persistsSearchInSession())->toBe($ligado)
        ->and($tabela->persistsSortInSession())->toBe($ligado)
        ->and($tabela->persistsColumnSearchesInSession())->toBe($ligado);
})->with([
    'ligado'    => true,
    'desligado' => false,
])->group('kit');

/**
 * As colunas arrastáveis são macro do `asmit/resized-column`, registrada em
 * runtime — o observável é o registry do pacote, não um getter da `Table`.
 */
it('liga e desliga as colunas arrastaveis conforme a configuracao', function (bool $ligado): void {
    config(['kit.tabelas.colunas_redimensionaveis' => $ligado]);

    tabelaDoKit();

    expect(ResizedColumnTableRegistry::isReorderable(ListUsers::class))->toBe($ligado);
})->with([
    'ligado'    => true,
    'desligado' => false,
])->group('kit');

/**
 * CT-19 — desligar um interruptor não desliga os outros.
 *
 * Este é o caso que separa "três condições" de "uma": um `if` só governando o
 * bloco inteiro passaria em TODAS as linhas de CT-18, porque cada linha afirma
 * apenas sobre o seu próprio efeito.
 */
it('desliga um interruptor sem desligar os outros', function (): void {
    config([
        'kit.tabelas.listrada'                 => false,
        'kit.tabelas.persistir_filtros'        => true,
        'kit.tabelas.colunas_redimensionaveis' => true,
        'kit.tabelas.paginacao'                => 25,
    ]);

    $tabela = tabelaDoKit();

    expect($tabela->isStriped())->toBeFalse()
        ->and($tabela->persistsFiltersInSession())->toBeTrue()
        ->and($tabela->getDefaultPaginationPageOption())->toBe(25)
        ->and(ResizedColumnTableRegistry::isReorderable(ListUsers::class))->toBeTrue();
})->group('kit');

/**
 * CT-20 — as telas de tabela continuam de pé com os defaults desligados.
 *
 * A fumaça de telas de VENDOR, e é a única coisa que este caso afirma — a rodada 2
 * da revisão adversarial cobrou com razão a asserção dos interruptores que eu
 * havia acrescentado aqui: ela é o oráculo de CT-18 e CT-19 repetido, e tirava
 * deste caso o que o torna único.
 *
 * O motivo dele existir está escrito em `ConfiguraFilamentGlobal.php`: uma
 * configuração global aplicada às tabelas de plugin de terceiro já derrubou OITO
 * telas de /infra em 500. Configuração condicional é exatamente a classe de
 * mudança que reintroduz aquilo.
 */
it('mantem as telas de tabela de pe com os defaults desligados', function (string $rota): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config([
        'kit.tabelas.listrada'                 => false,
        'kit.tabelas.persistir_filtros'        => false,
        'kit.tabelas.colunas_redimensionaveis' => false,
    ]);

    $this->actingAs(usuarioDoKit('master_global'));

    $this->get($rota)->assertOk();
})->with([
    // Uma de autoria própria, e duas de plugin de terceiro — que é onde o
    // estrago aparece.
    'usuários do /admin'    => '/admin/users',
    'auditoria do /infra'   => '/infra/audits',
    'exceções do /infra'    => '/infra/exceptions',
])->group('kit');

it('[CT-01] nasce desligado em cada fonte de configuracao', function (string $fonte, ?string $variavel, string $chave, mixed $esperado): void {
    if ($fonte === '.env.example (linha comentada)') {
        // O `.env.example` do kit traz toda chave de tabela COMENTADA com o default (`# CHAVE=valor`):
        // o banco vence e o arquivo é só semente — uma linha, e nenhuma ativa (CT-01 alterado em 2026-10-14).
        $linhas     = file(base_path('.env.example'), FILE_IGNORE_NEW_LINES);
        $comentadas = array_values(array_filter($linhas, fn (string $linha): bool => preg_match('/^#\s*'.preg_quote($chave, '/').'=/', trim($linha)) === 1));
        $ativas     = array_values(array_filter($linhas, fn (string $linha): bool => str_starts_with(trim($linha), $chave.'=')));

        expect($comentadas)->toHaveCount(1);
        expect($ativas)->toBe([]);
        expect(trim(explode('=', $comentadas[0], 2)[1], " \t\"'"))->toBe($esperado);

        return;
    }

    $anteriorEnv      = $_ENV[$variavel] ?? null;
    $anteriorServer   = $_SERVER[$variavel] ?? null;
    $anteriorProcesso = getenv($variavel);
    unset($_ENV[$variavel], $_SERVER[$variavel]);
    putenv($variavel);

    try {
        expect(env($variavel))->toBeNull();

        $configuracao = require base_path($fonte);

        expect(data_get($configuracao, $chave))->toBe($esperado);
    } finally {
        if ($anteriorEnv !== null) {
            $_ENV[$variavel] = $anteriorEnv;
        }
        if ($anteriorServer !== null) {
            $_SERVER[$variavel] = $anteriorServer;
        }
        if ($anteriorProcesso !== false) {
            putenv($variavel.'='.$anteriorProcesso);
        }
    }
})->with([
    'config/kit.php | KIT_TABELA_CARREGAMENTO | tabelas.carregamento | paginacao'               => ['config/kit.php', 'KIT_TABELA_CARREGAMENTO', 'tabelas.carregamento', 'paginacao'],
    'config/kit.php | KIT_TABELA_VISOES_SALVAS | tabelas.visoes_salvas | false'                 => ['config/kit.php', 'KIT_TABELA_VISOES_SALVAS', 'tabelas.visoes_salvas', false],
    'config/remember-table.php | KIT_TABELA_VISOES_COMPARTILHADAS | allow_public_views | false' => ['config/remember-table.php', 'KIT_TABELA_VISOES_COMPARTILHADAS', 'allow_public_views', false],
    '.env.example (linha comentada) | — | KIT_TABELA_CARREGAMENTO | paginacao'                  => ['.env.example (linha comentada)', null, 'KIT_TABELA_CARREGAMENTO', 'paginacao'],
    '.env.example (linha comentada) | — | KIT_TABELA_VISOES_SALVAS | false'                     => ['.env.example (linha comentada)', null, 'KIT_TABELA_VISOES_SALVAS', 'false'],
    '.env.example (linha comentada) | — | KIT_TABELA_VISOES_COMPARTILHADAS | false'             => ['.env.example (linha comentada)', null, 'KIT_TABELA_VISOES_COMPARTILHADAS', 'false'],
])->group('kit');

it('[CT-02] remove os interruptores no rollback e semeia desligados ao reaplicar', function (bool $reaplicar): void {
    $migration = require base_path('database/settings/2026_10_14_200000_add_tabelas_rodada_3_to_kit_settings.php');

    $migration->down();
    if ($reaplicar) {
        $migration->up();
    }

    foreach (['tabela_carregamento' => '"paginacao"', 'visoes_de_tabela' => 'false', 'visoes_de_tabela_compartilhadas' => 'false'] as $propriedade => $payload) {
        if ($reaplicar) {
            $this->assertDatabaseHas('settings', ['group' => 'kit', 'name' => $propriedade, 'payload' => $payload]);
        } else {
            $this->assertDatabaseMissing('settings', ['group' => 'kit', 'name' => $propriedade]);
        }
    }
})->with([
    'o down() | tabela_carregamento, visoes_de_tabela e visoes_de_tabela_compartilhadas não existem no grupo kit'        => [false],
    'o down() e depois o up() | tabela_carregamento está gravada como a string "paginacao", e as outras duas como false' => [true],
])->group('kit');

it('[CT-03] conserva a paginacao ao montar a tabela em paginacao', function (string $pagina, string $painel, string $rota): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    // 25 e não 7: 7 não está nas opções de página e a tabela começaria pela primeira (comportamento
    // de hoje) — o tamanho inicial só é falsificável com um valor da lista (CT-03 alterado em 2026-10-14).
    ligarCarregamento('paginacao', 25);
    $this->actingAs(usuarioDoKit('master_global'))->get($rota)->assertOk();
    noPainelBootado($painel);

    $componente = Livewire::test($pagina)->loadTable();
    $tabela     = $componente->instance()->getTable();

    expect(app(InfiniteScrollRegistry::class)->get($tabela))->toBeNull();
    expect($tabela->getPaginationPageOptions())->toBe([5, 10, 25, 50]);
    $componente->assertSet('tableRecordsPerPage', 25);
})->with([
    'usuários do /admin (ListUsers) | do kit'  => [ListUsers::class, 'admin', '/admin/users'],
    'auditoria do /infra | plugin de terceiro' => [ListAudits::class, 'infra', '/infra/audits'],
])->group('kit');

it('[CT-11] abre a listagem paginada com carregamento ilegivel', function (string $fonte): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    if ($fonte === 'banco') {
        gravarConfiguracao('tabela_carregamento', 'xpto');
        alinharConfiguracoesDoKit();
    } else {
        $configuracao = kitConfigCom('KIT_TABELA_CARREGAMENTO', 'xpto');
        config(['kit.tabelas.carregamento' => $configuracao['tabelas']['carregamento']]);
    }
    $this->actingAs(usuarioDoKit('master_global'));

    $this->get('/admin/users')->assertOk()->assertSeeLivewire(ListUsers::class);
    $componente = Livewire::test(ListUsers::class)->loadTable();

    expect(app(InfiniteScrollRegistry::class)->get($componente->instance()->getTable()))->toBeNull();
})->with([
    'o banco (gravarConfiguracao + alinhamento do boot)'          => ['banco'],
    'a variável KIT_TABELA_CARREGAMENTO relida em config/kit.php' => ['env'],
])->group('kit');
