<?php

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use Asmit\ResizedColumn\ResizedColumnTableRegistry;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Tables\Table;

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
