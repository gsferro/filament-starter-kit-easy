<?php

use Illuminate\Support\Facades\File;

/**
 * `kit:cobertura` — o comando que decide se a cobertura reprova o build.
 *
 * ## Por que este arquivo existe
 *
 * Os caminhos de saída do comando foram verificados **à mão** quando ele nasceu, e a verificação
 * não foi versionada. A medição seguinte cobrou a conta: o arquivo entrou com **0 de 105
 * statements** cobertos, e derrubou a cobertura do kit de 79,79 % para 78,84 % sozinho.
 *
 * Há uma ironia útil aí, e ela é o motivo de este parágrafo estar escrito: o comando que mede
 * cobertura foi o maior buraco de cobertura da entrega que o criou.
 *
 * ## O que estes casos protegem, além do óbvio
 *
 * Um comando de gate tem uma assimetria: falhar quando deveria passar é barulhento e alguém
 * conserta em minutos; **passar quando deveria falhar é silencioso e dura meses**. Por isso a
 * maior parte dos casos abaixo é sobre a segunda metade — entrada malformada que poderia desligar
 * o piso sem avisar.
 */
beforeEach(function (): void {
    $this->pasta = base_path('storage/framework/testing/cobertura-'.bin2hex(random_bytes(4)));
    File::ensureDirectoryExists($this->pasta);

    /*
     * A raiz da aplicacao vai para a pasta temporaria em TODOS os casos, e nao so nos de badge.
     *
     * Sem isso, o comando enxergaria o `.github/badges/cobertura.json` de verdade do kit e o
     * compararia com o percentual do Clover falso — os casos de piso reprovariam por divergencia
     * de badge, que nao e o que eles afirmam. Cada caso que PRECISA de badge cria o diretorio
     * dentro da pasta temporaria; os demais rodam num mundo sem badge, que e tambem o mundo de
     * quem instala o kit.
     */
    $this->app->setBasePath($this->pasta);
});

afterEach(function (): void {
    File::deleteDirectory($this->pasta);
});

/**
 * Escreve um Clover mínimo com as métricas pedidas e devolve o caminho.
 *
 * Só `<project><metrics>` importa para o comando — o resto do formato é ruído aqui, e imitá-lo
 * inteiro tornaria o caso frágil a detalhes que ele não afirma nada sobre.
 */
function cloverCom(string $pasta, ?int $cobertas, ?int $total): string
{
    $caminho = $pasta.'/clover.xml';

    $metricas = $cobertas === null && $total === null
        ? ''
        : sprintf('<metrics statements="%d" coveredstatements="%d"/>', $total ?? 0, $cobertas ?? 0);

    File::put($caminho, '<?xml version="1.0" encoding="UTF-8"?><coverage><project timestamp="0">'.$metricas.'</project></coverage>');

    return $caminho;
}

it('[CT-01] aprova quando a cobertura esta acima do piso', function (): void {
    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 80, 100), '--min' => '78'])
        ->assertSuccessful();
})->group('kit');

it('[CT-02] reprova quando a cobertura esta abaixo do piso', function (): void {
    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 70, 100), '--min' => '78'])
        ->assertFailed();
})->group('kit');

/**
 * O piso é **estrito**: exatamente no piso passa, um fio abaixo reprova.
 *
 * Valor limite escrito de propósito. `<` e `<=` são o mutante mais fácil de sobreviver num gate
 * numérico, e a diferença entre os dois é uma build vermelha por dia em quem vive na fronteira.
 */
it('[CT-03] trata o piso como limite inclusivo', function (int $cobertas, bool $passa): void {
    $execucao = $this->artisan('kit:cobertura', [
        'clover' => cloverCom($this->pasta, $cobertas, 10_000),
        '--min'  => '78',
    ]);

    $passa ? $execucao->assertSuccessful() : $execucao->assertFailed();
})->with([
    'um fio abaixo do piso' => [7799, false],
    'exatamente no piso'    => [7800, true],
    'um fio acima'          => [7801, true],
])->group('kit');

/**
 * `--min` ilegível PRECISA falhar, e este é o caso mais importante do arquivo.
 *
 * A primeira redação fazia `(float) $bruto`, e `(float) 'abc'` é `0.0`: o piso virava zero, tudo
 * passava, e o comando ainda imprimia *"o piso foi respeitado"*. Fail-open **com mensagem de
 * sucesso** — a pior combinação possível num gate.
 *
 * Achado pela revisão cega do diff (RD-02) antes de chegar à `main`.
 */
it('[CT-04] recusa --min ilegivel em vez de desligar o piso em silencio', function (string $min, string $pedaco): void {
    /*
     * A mensagem é afirmada junto, e não só o código de saída: quem passa `--mim=78` por engano
     * precisa ler POR QUE reprovou. Sem esta parte, apagar a linha que imprime o erro deixaria o
     * comando falhando em silêncio — e o mutante que faz isso sobrevivia à primeira redação.
     */
    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 1, 100), '--min' => $min])
        ->expectsOutputToContain($pedaco)
        ->assertFailed();
})->with([
    'texto'        => ['abc', '"abc"'],
    'vazio'        => ['', 'precisa ser um número'],
    'negativo'     => ['-1', 'entre 0 e 100'],
    'acima de cem' => ['101', 'entre 0 e 100'],
])->group('kit');

/**
 * Sem `--min`, o comando não inventa piso nenhum.
 *
 * É o modo "só me diga o número", e ele precisa continuar existindo: é como se mede antes de
 * escolher a meta. Cobertura de 1 % passa, porque nada foi exigido.
 */
it('[CT-05] sem --min nao aplica piso', function (): void {
    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 1, 100)])
        ->assertSuccessful();
})->group('kit');

/**
 * Relatorio que nao da para medir reprova, em vez de virar zero por cento.
 *
 * As quatro formas de nao dar para medir levam ao MESMO desfecho de proposito. Um Clover ilegivel
 * que virasse "0 %" reprovaria o build com a mensagem errada — mandando procurar teste faltando
 * onde o que falta e o arquivo.
 */
it('[CT-06] recusa relatorio ausente, ilegivel, sem metricas ou com zero statements', function (string $defeito): void {
    $clover = match ($defeito) {
        'ausente'         => $this->pasta.'/nao-existe.xml',
        'ilegivel'        => tap($this->pasta.'/quebrado.xml', fn (string $c) => File::put($c, '<coverage><project>')),
        'sem metricas'    => cloverCom($this->pasta, null, null),
        'zero statements' => cloverCom($this->pasta, 0, 0),
    };

    $this->artisan('kit:cobertura', ['clover' => $clover, '--min' => '78'])
        ->assertFailed();
})->with(['ausente', 'ilegivel', 'sem metricas', 'zero statements'])->group('kit');

/**
 * O badge guarda o percentual INTEIRO TRUNCADO — 79,99 % vira `79%`, não `80%`.
 *
 * Truncar, e não arredondar, é o que mantém o badge do lado seguro: ele nunca anuncia mais
 * cobertura do que existe.
 */
it('[CT-07] grava o badge com o inteiro truncado e a cor da faixa', function (int $cobertas, string $mensagem, string $cor): void {
    $badges = $this->pasta.'/.github/badges';
    File::ensureDirectoryExists($badges);

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, $cobertas, 10_000), '--write' => true])
        ->assertSuccessful();

    $badge = json_decode((string) File::get($badges.'/cobertura.json'), true);

    expect($badge)->toMatchArray([
        'schemaVersion' => 1,
        'label'         => 'cobertura',
        'message'       => $mensagem,
        'color'         => $cor,
    ]);
})->with([
    'trunca, nao arredonda'               => [7999, '79%', 'green'],
    'oitenta ou mais e brightgreen'       => [8000, '80%', 'brightgreen'],
    'setenta a setenta e nove e green'    => [7000, '70%', 'green'],
    'sessenta a sessenta e nove e yellow' => [6000, '60%', 'yellow'],
    'abaixo de sessenta e red'            => [5999, '59%', 'red'],
])->group('kit');

/**
 * Conferindo (sem `--write`), o badge que mente reprova — e o que só está reformatado, não.
 *
 * A redação anterior comparava o JSON **byte a byte**, então reindentar o arquivo produzia a
 * mensagem autocontraditória *"o badge commitado diz 79% e o medido é 79%"*. Guarda que reprova
 * por ruído vira alarme falso, e alarme falso é como guarda morre. Achado RD-04.
 */
it('[CT-08] confere o badge pelo valor, e nao pelos bytes', function (string $json, bool $passa): void {
    $badges = $this->pasta.'/.github/badges';
    File::ensureDirectoryExists($badges);
    File::put($badges.'/cobertura.json', $json);

    $execucao = $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 7900, 10_000)]);

    $passa ? $execucao->assertSuccessful() : $execucao->assertFailed();
})->with([
    'canonico'                 => ['{"schemaVersion":1,"label":"cobertura","message":"79%","color":"green"}', true],
    'reindentado e reordenado' => ["{\n        \"color\": \"green\",\n        \"message\": \"79%\",\n        \"label\": \"cobertura\",\n        \"schemaVersion\": 1\n}", true],
    'percentual diferente'     => ['{"schemaVersion":1,"label":"cobertura","message":"91%","color":"green"}', false],
    'cor diferente'            => ['{"schemaVersion":1,"label":"cobertura","message":"79%","color":"red"}', false],
    'ilegivel'                 => ['{nao e json', false],
])->group('kit');

/**
 * Fora da árvore do kit, o comando não escreve nem confere badge nenhum — e o piso continua valendo.
 *
 * É a degradação que torna o comando entregável: `.github/` é `export-ignore`, então em projeto
 * nascido de `composer create-project` o diretório não existe. Sem este comportamento, o comando
 * reprovaria todo projeto instalado com *"o badge commitado diz (ausente)"* — cobrando do usuário
 * um badge que nunca foi dele.
 */
it('[CT-09] fora da arvore do kit ignora o badge e mantem o piso', function (): void {
    expect(File::exists($this->pasta.'/.github/badges'))->toBeFalse();

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 80, 100), '--min' => '78'])
        ->assertSuccessful();

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 70, 100), '--min' => '78'])
        ->assertFailed();

    expect(File::exists($this->pasta.'/.github/badges/cobertura.json'))->toBeFalse();
})->group('kit');

/**
 * A mensagem nomeia o piso que a comparação usou, e não um arredondamento dele.
 *
 * `%.0f` transformava `--min=79.9` em *"abaixo do piso de 80%"*. A mensagem é a única coisa que o
 * operador lê; nomear um piso que não é o aplicado manda a investigação para o lado errado.
 * Achado RD-03.
 */
it('[CT-10] a mensagem de reprovacao cita o piso como ele foi pedido', function (): void {
    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 7000, 10_000), '--min' => '79.9'])
        ->expectsOutputToContain('79.9%')
        ->assertFailed();
})->group('kit');

/**
 * As mensagens de badge divergente dizem **os dois** valores, e distinguem ausente de diferente.
 *
 * Parece detalhe e não é: a mensagem é a única coisa que o operador lê quando o CI reprova, e a
 * redação anterior conseguia imprimir *"o badge commitado diz 79% e o medido é 79%"* — ou seja,
 * acusava divergência entre dois números iguais, porque comparava bytes. Quem lesse isso iria
 * procurar defeito no lugar errado.
 *
 * Os casos abaixo travam as três formas: badge ausente, percentual diferente e cor diferente.
 */
it('[CT-11] a mensagem de badge divergente nomeia o commitado e o medido', function (?string $json, string $pedaco): void {
    $badges = $this->pasta.'/.github/badges';
    File::ensureDirectoryExists($badges);

    if ($json !== null) {
        File::put($badges.'/cobertura.json', $json);
    }

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 7900, 10_000)])
        ->expectsOutputToContain($pedaco)
        ->assertFailed();
})->with([
    'ausente distingue de diferente' => [null, '(ausente ou ilegível)'],
    'ilegivel tambem'                => ['{nao e json', '(ausente ou ilegível)'],
    'diz o percentual commitado'     => ['{"schemaVersion":1,"label":"cobertura","message":"91%","color":"green"}', '"91%"'],
    'e diz o medido'                 => ['{"schemaVersion":1,"label":"cobertura","message":"91%","color":"green"}', '79%'],
    'cor: diz a commitada'           => ['{"schemaVersion":1,"label":"cobertura","message":"79%","color":"red"}', '`red`'],
    'cor: e diz a medida'            => ['{"schemaVersion":1,"label":"cobertura","message":"79%","color":"red"}', '`green`'],
])->group('kit');

/**
 * Gravar o badge não reclama do badge que estava lá — `--write` substitui, não confere.
 *
 * Sem este caso, remover o `return` que encerra o caminho de escrita passaria despercebido: o
 * comando cairia na conferência logo depois de ter gravado, e como o que ele acabou de gravar
 * confere consigo mesmo, ninguém notaria — até o dia em que alguém rodasse `--write` sobre um
 * badge antigo de outro valor e levasse um erro por ter feito exatamente o que devia.
 */
it('[CT-12] --write substitui um badge divergente sem reclamar dele', function (): void {
    $badges = $this->pasta.'/.github/badges';
    File::ensureDirectoryExists($badges);
    File::put($badges.'/cobertura.json', '{"schemaVersion":1,"label":"cobertura","message":"12%","color":"red"}');

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 7900, 10_000), '--write' => true])
        ->assertSuccessful();

    expect(json_decode((string) File::get($badges.'/cobertura.json'), true))
        ->toMatchArray(['message' => '79%', 'color' => 'green']);
})->group('kit');
