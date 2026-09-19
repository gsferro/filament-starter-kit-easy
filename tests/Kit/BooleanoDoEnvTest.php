<?php

use App\Support\BooleanoDoEnv;

/**
 * O booleano do `.env` com o caso VAZIO tratado — e o defeito que ele conserta.
 *
 * Este arquivo é o par de `NumeroDoEnvTest`, e existe porque o defeito foi medido
 * duas vezes nesta feature, na mesma tarde:
 *
 * 1. `(bool) env('KIT_TABELA_LISTRADA', true)` — o defeito conhecido, que
 *    `.ai/rules/config.md` documenta para inteiros. Chave presente e vazia devolve
 *    string vazia, `(bool) ''` é `false`, e o default `true` nunca entra.
 * 2. `filter_var($bruto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true`
 *    — a **correção óbvia**, que reproduz o defeito. Foi escrita, revisada e
 *    commitada; as três chaves de tabela nasceram DESLIGADAS com ela, e só apareceu
 *    ao rodar o `migrate` e olhar as linhas semeadas.
 *
 * O motivo de (2) falhar é o que este arquivo trava: o filtro do PHP trata `null` e
 * `''` como **false**, não como falha, então o `??` só dispara em texto ilegível.
 * As duas primeiras linhas do primeiro caso são as que reprovam a correção ingênua.
 */
it('trata ausente e vazio como ausente, caindo no default', function (mixed $bruto): void {
    expect(BooleanoDoEnv::comPadrao($bruto, true))->toBeTrue()
        ->and(BooleanoDoEnv::comPadrao($bruto, false))->toBeFalse();
})->with([
    'chave ausente'          => null,
    'chave presente e vazia' => '',
])->group('kit');

/**
 * O vocabulário que o `.env` aceita, nos dois sentidos.
 *
 * `'0'` e `'false'` precisam devolver `false` mesmo com default `true` — é a
 * diferença entre "não configurou" e "configurou como desligado", e é ela que
 * torna a feature desligável pelo arquivo.
 */
it('respeita o vocabulario de booleano do env', function (string $bruto, bool $esperado): void {
    expect(BooleanoDoEnv::comPadrao($bruto, ! $esperado))->toBe($esperado);
})->with([
    'true'  => ['true', true],
    '1'     => ['1', true],
    'on'    => ['on', true],
    'yes'   => ['yes', true],
    'false' => ['false', false],
    '0'     => ['0', false],
    'off'   => ['off', false],
    'no'    => ['no', false],
])->group('kit');

/**
 * Texto ilegível cai no DEFAULT, não em `false`.
 *
 * A escolha é diferente da de `NumeroDoEnv::positivo()`, que joga texto em `1`
 * para produzir "um valor curto e visível que faz alguém corrigir o .env". Aqui não
 * existe valor visível: um booleano errado é indistinguível de um booleano
 * escolhido. Entregar o comportamento que o kit promete é o único resultado que não
 * mente para quem lê o `.env.example`.
 */
it('cai no default quando o valor nao e vocabulario de booleano', function (): void {
    expect(BooleanoDoEnv::comPadrao('talvez', true))->toBeTrue()
        ->and(BooleanoDoEnv::comPadrao('talvez', false))->toBeFalse();
})->group('kit');

/**
 * A guarda de regressão do defeito de (2), escrita como contraste explícito.
 *
 * Se alguém "simplificar" `comPadrao()` de volta para o `filter_var` com `??`,
 * este caso é o que fica vermelho — e a mensagem diz o que aconteceu.
 */
it('difere do filter_var cru justamente em ausente e vazio', function (mixed $bruto): void {
    $cru = filter_var($bruto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

    expect($cru)->toBeFalse('O filtro cru trata ausente/vazio como false — é o defeito.')
        ->and(BooleanoDoEnv::comPadrao($bruto, true))->toBeTrue();
})->with([
    'chave ausente'          => null,
    'chave presente e vazia' => '',
])->group('kit');

/**
 * As três chaves de tabela do kit nascem LIGADAS.
 *
 * É a asserção de ponta que fecha o defeito medido: com a correção ingênua, estas
 * três eram `false` numa instalação sem nenhuma das chaves no `.env` — que é como
 * o kit nasce.
 */
it('entrega os defaults de tabela do kit ligados numa instalacao sem as chaves', function (string $chave): void {
    expect(config($chave))->toBeTrue();
})->with([
    'kit.tabelas.listrada',
    'kit.tabelas.persistir_filtros',
    'kit.tabelas.colunas_redimensionaveis',
])->group('kit');

/*
|--------------------------------------------------------------------------
| ouNulo() — a chave de TRÊS estados
|--------------------------------------------------------------------------
| QA-17 do quality gate (cenários em `wikis/specs/fix/url-sem-prefixo-public/`, R7): `ouNulo()` nasceu sem um único caso, e a feature que o
| introduziu o exercita só por `config()->set()`, que pula a linha do
| `config/kit.php`. Apagar o guard dele fazia `KIT_ALGO=` virar `false` —
| desligando a correção de URL — com a suíte inteira verde. É o mesmo mutante
| que o CT-13 fecha do outro lado.
*/

it('[CT-14] ouNulo trata ausente e vazio como AUSENTE, nao como desligado', function (mixed $bruto): void {
    expect(BooleanoDoEnv::ouNulo($bruto))->toBeNull();
})->with([
    'chave ausente'          => null,
    'chave presente e vazia' => '',
])->group('kit');

/**
 * O que separa `ouNulo()` de `comPadrao()`: aqui `null` é resposta, não falta de resposta.
 *
 * Numa chave tri-estado, valor ilegível tem de cair no comportamento padrão — nunca no `false`
 * que o `filter_var` cru produziria, porque `false` é uma das três respostas e significa
 * "desligado de propósito".
 */
it('[CT-15] ouNulo trata valor ilegivel como ausente, nunca como false', function (string $bruto): void {
    expect(BooleanoDoEnv::ouNulo($bruto))->toBeNull();
})->with(['talvez', 'sim', '2', 'null'])->group('kit');

it('[CT-16] ouNulo declara quando o valor e legivel', function (mixed $bruto, bool $esperado): void {
    expect(BooleanoDoEnv::ouNulo($bruto))->toBe($esperado);
})->with([
    ['true', true],
    ['1', true],
    ['on', true],
    [true, true],
    ['false', false],
    ['0', false],
    ['off', false],
    [false, false],
])->group('kit');
