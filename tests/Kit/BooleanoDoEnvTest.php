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
