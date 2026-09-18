<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * A versão no rodapé dos painéis.
 *
 * **Duas versões, e confundi-las foi o erro que o Adendo 2 do requisito corrigiu:**
 *
 *   `config('app.version')` → a versão do PRODUTO que nasceu do kit. É a que o rodapé mostra.
 *   `config('kit.version')` → a versão do STARTER KIT. Métrica interna, só aparece sob toggle.
 *
 * O caso que vale mais neste arquivo é o do visitante: o render hook `FOOTER` é emitido também
 * pelo layout `simple` (`vendor/filament/filament/resources/views/components/layout/simple.blade.php:58`),
 * que é o das telas de login, registro e recuperação de senha — e versão exata de uma instalação
 * é o mapa de CVEs aplicáveis a ela.
 *
 * Ver ADR-04 de `wikis/specs/feat/estudo-de-pacotes-rodada-2/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * As duas chaves são lidas por request (`config()` dentro da blade), então `config()->set()` no
 * caso é o arranjo fiel — não há nada congelado no boot do painel a contornar.
 */
function comVersoes(?string $sistema, bool $exibirKit): void
{
    config()->set('app.version', $sistema);
    config()->set('kit.exibir_versao', $exibirKit);
}

/*
|--------------------------------------------------------------------------
| A fronteira: visitante nunca vê versão
|--------------------------------------------------------------------------
*/

it('nao mostra versao nenhuma para quem nao entrou', function (string $rota): void {
    comVersoes('9.9.9-secreta', exibirKit: true);

    $this->get($rota)
        ->assertOk()
        ->assertDontSee('9.9.9-secreta')
        ->assertDontSee('kit '.config('kit.version'));
})->with([
    'login do admin' => ['/admin/login'],
    'login do app'   => ['/app/login'],
    'login do infra' => ['/infra/login'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| A tabela de decisão das duas chaves
|--------------------------------------------------------------------------
*/

/**
 * Quatro combinações, produto cartesiano fechado das duas chaves.
 *
 * O par que mata o mutante mais provável — "mostrar a do kit sempre" — é
 * `só sistema` × `as duas`: o primeiro exige a AUSÊNCIA da versão do kit, o segundo a presença.
 */
it('compoe o rodape conforme as duas chaves', function (?string $sistema, bool $exibirKit, bool $esperaSistema, bool $esperaKit): void {
    comVersoes($sistema, $exibirKit);

    /*
     * O rótulo do kit é montado AQUI, e não no dataset: dataset é resolvido fora do ciclo de vida
     * da aplicação, e `config()` ali estoura `Target class [config] does not exist`.
     */
    $rotuloDoKit = 'kit '.config('kit.version');

    $resposta = $this->actingAs(usuarioDoKit('admin'))->get('/admin');

    $resposta->assertOk();

    $esperaSistema
        ? $resposta->assertSee('v'.$sistema, escape: false)
        : $resposta->assertDontSee('v'.($sistema ?? '—'), escape: false);

    $esperaKit
        ? $resposta->assertSee($rotuloDoKit, escape: false)
        : $resposta->assertDontSee($rotuloDoKit, escape: false);
})->with([
    'nenhuma das duas' => [null, false, false, false],
    'só o sistema'     => ['2.4.1', false, true, false],
    'só o kit'         => [null, true, false, true],
    'as duas'          => ['2.4.1', true, true, true],
])->group('kit');

/**
 * Versão vazia e toggle desligado: o rodapé não renderiza NADA.
 *
 * Caso separado da tabela acima de propósito: ali a asserção é sobre texto, aqui é sobre o
 * elemento. Um mutante que renderizasse `<div class="kit-versao"></div>` vazio passaria na
 * tabela e falha aqui — e um rodapé vazio ocupando espaço é defeito visual, não detalhe.
 */
it('nao renderiza o elemento quando nao ha o que mostrar', function (): void {
    comVersoes(null, exibirKit: false);

    $this->actingAs(usuarioDoKit('admin'))
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('kit-versao');
})->group('kit');

/*
|--------------------------------------------------------------------------
| Vale nos três painéis
|--------------------------------------------------------------------------
*/

/**
 * O hook é registrado **sem `scopes:`** em `ConfiguraFilamentGlobal`, o que o faz valer em
 * qualquer painel. Este caso é o que fica vermelho se alguém acrescentar um escopo.
 */
it('mostra a versao do sistema nos tres paineis', function (string $painel, string $papel): void {
    comVersoes('3.1.4', exibirKit: false);

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"))
        ->get("/{$painel}")
        ->assertOk()
        ->assertSee('v3.1.4');
})->with([
    /*
     * O `app` NÃO pode faltar, e a primeira redação deste caso o omitiu. Ele é o painel com
     * tenancy, o mais provável de resolver o hook de forma diferente — sem ele, acrescentar
     * `scopes: ['admin', 'infra']` ao registro do hook deixaria o caso VERDE e sumiria com o
     * rodapé do /app em silêncio, que é exatamente o mutante que ele diz matar. Achado pelo
     * `/code-review` do diff.
     */
    'app'   => ['app', 'panel_user'],
    'admin' => ['admin', 'admin'],
    'infra' => ['infra', 'infra'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| A fonte é a config, não o git
|--------------------------------------------------------------------------
*/

/**
 * A decisão de NÃO ler `.git` em tempo de execução está na ADR-04, e o motivo é operacional:
 * imagem de produção costuma não ter `.git`.
 *
 * Este caso a enforça por varredura: nenhum arquivo do kit pode resolver versão por `shell_exec`,
 * `exec` ou leitura de `.git`. Sem ele, a decisão seria só prosa na ADR.
 */
it('nao le o git para resolver a versao', function (): void {
    $blade = (string) file_get_contents(resource_path('views/filament/versao-do-kit.blade.php'));

    /*
     * O comentário da blade EXPLICA a decisão, e portanto cita `.git`. Varrer o arquivo inteiro
     * reprovaria a documentação da própria decisão — o recorte é o código, depois do `--}}`.
     */
    $codigo = substr($blade, (int) strpos($blade, '--}}'));

    expect($codigo)
        ->not->toContain('shell_exec')
        ->not->toContain('exec(')
        ->not->toContain('.git')
        ->toContain("config('app.version')")
        ->toContain("config('kit.version')");
})->group('kit');
