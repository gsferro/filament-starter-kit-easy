<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command;

/**
 * `kit:cobertura` — o comando que decide se a cobertura reprova o build.
 *
 * IDs de CT em `wikis/specs/feat/cobertura-de-testes/cobertura-de-testes/04-casos-de-teste.md`.
 * Renumerado a partir do mapa da seção `## Reconciliação com os testes existentes` daquele `04`
 * (tabela "(c) Cada teste existente → existe CT?"): os antigos CT-01..CT-12 que existiam aqui
 * foram escritos a partir do comando, sem cenário no `04`, e viraram os IDs abaixo.
 *
 * ## Por que este arquivo existe
 *
 * Os caminhos de saída do comando foram verificados **à mão** quando ele nasceu, e a verificação
 * não foi versionada. A medição seguinte cobrou a conta: o arquivo entrou com **0 de 105
 * statements** cobertos, e derrubou a cobertura do kit de 79,79 % para 78,84 % sozinho.
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

/**
 * A meta de cobertura VIVA do kit, lida do `composer.json` real — nunca um literal aqui.
 *
 * O `04` proíbe citar o número do piso como oráculo (`## Fronteira com o Plano`): ele precisa ser
 * **lido**, não copiado. Lido pelo caminho ABSOLUTO do projeto (`dirname(__DIR__, 2)`), e não por
 * `base_path()`: o `beforeEach` deste arquivo relocaliza a raiz da aplicação para a pasta
 * temporária em TODOS os casos, e `base_path()` responderia sobre ela, não sobre o `composer.json`
 * de verdade.
 */
function metaDoKit(): float
{
    $composer = (string) file_get_contents(dirname(__DIR__, 2).'/composer.json');

    preg_match('~kit:cobertura[^"]*--min=(\d+(?:\.\d+)?)~', $composer, $casado);

    return (float) ($casado[1] ?? 0.0);
}

/**
 * Relatório cuja razão é a META LIDA EM TEMPO DE EXECUÇÃO, deslocada por `$desvioPP` pontos
 * percentuais (positivo = abaixo da meta, negativo = acima).
 *
 * `mensuraveis = 100.000` torna 0,001 pp representável em número INTEIRO de statements — é o que
 * faz as bordas do BVA de R3 saírem exatas, sem que o arredondamento da própria fixture mascare o
 * que o comparador faz.
 */
function relatorioComDesvio(string $pasta, float $desvioPP): string
{
    $mensuraveis = 100_000;
    $razao       = metaDoKit() - $desvioPP;
    $cobertas    = (int) round($razao / 100 * $mensuraveis);

    return cloverCom($pasta, $cobertas, $mensuraveis);
}

/**
 * Clover com dois `<file>` de tamanhos DESIGUAIS, mais o `<project><metrics>` agregado — CT-08.
 *
 * `metricasDo()` só lê o agregado; os `<file>` existem pela fidelidade da fixture ao Gherkin
 * ("um arquivo de 10 linhas... e outro de 990..."), não porque o comando precise deles hoje.
 *
 * @param  array<string, array{0: int, 1: int}>  $arquivos  nome => [cobertas, total]
 */
function relatorioComArquivos(string $pasta, array $arquivos, int $cobertasAgregado, int $totalAgregado): string
{
    $caminho = $pasta.'/clover.xml';

    $xmlArquivos = '';

    foreach ($arquivos as $nome => [$cobertas, $total]) {
        $xmlArquivos .= sprintf(
            '<file name="%s"><metrics statements="%d" coveredstatements="%d"/></file>',
            $nome,
            $total,
            $cobertas,
        );
    }

    File::put($caminho, sprintf(
        '<?xml version="1.0" encoding="UTF-8"?><coverage><project timestamp="0">%s<metrics statements="%d" coveredstatements="%d"/></project></coverage>',
        $xmlArquivos,
        $totalAgregado,
        $cobertasAgregado,
    ));

    return $caminho;
}

/**
 * Clover em que a razão de LINHAS e a de ELEMENTOS divergem — CT-09, `@premissa` de mecanismo.
 */
function relatorioComRazoesDivergentes(string $pasta, float $linhas, float $elementos): string
{
    $caminho = $pasta.'/clover.xml';
    $total   = 10_000;

    File::put($caminho, sprintf(
        '<?xml version="1.0" encoding="UTF-8"?><coverage><project timestamp="0">'
        .'<metrics statements="%d" coveredstatements="%d" elements="%d" coveredelements="%d"/>'
        .'</project></coverage>',
        $total,
        (int) round($linhas / 100 * $total),
        $total,
        (int) round($elementos / 100 * $total),
    ));

    return $caminho;
}

/** Cria `.github/badges/cobertura.json` na árvore temporária, com um valor conhecido e diferente. */
function badgeDePartida(string $pasta, string $mensagem = '42%', string $cor = 'red'): string
{
    $badges = $pasta.'/.github/badges';
    File::ensureDirectoryExists($badges);

    $caminho = $badges.'/cobertura.json';
    File::put($caminho, json_encode([
        'schemaVersion' => 1,
        'label'         => 'cobertura',
        'message'       => $mensagem,
        'color'         => $cor,
    ]));

    return $caminho;
}

/**
 * [CT-06] O piso é limite inclusivo, e a comparação usa a razão EXATA — sem arredondar antes.
 *
 * As quatro linhas usam a META LIDA EM TEMPO DE EXECUÇÃO (`metaDoKit()`), nunca um literal. A
 * linha `meta−0,004` é a que mata `round()`: 0,004 pp abaixo arredonda PARA a meta, mas está
 * abaixo dela. "A razão exibida é exatamente <razao>" é conferido pela contagem EXATA de
 * statements que a saída imprime — não pelo `%.2f` de exibição, que arredondaria justamente essa
 * linha para o mesmo texto da borda exata.
 */
it('[CT-06] o piso é inclusivo na borda, e a comparação usa a meta lida em tempo de execução', function (float $desvioPP, string $veredito): void {
    $mensuraveis = 100_000;
    $meta        = metaDoKit();
    $cobertas    = (int) round(($meta - $desvioPP) / 100 * $mensuraveis);
    $clover      = cloverCom($this->pasta, $cobertas, $mensuraveis);

    $execucao = $this->artisan('kit:cobertura', [
        'clover' => $clover,
        '--min'  => (string) $meta,
    ])->expectsOutputToContain("({$cobertas} / {$mensuraveis} statements)");

    $veredito === 'aprova' ? $execucao->assertSuccessful() : $execucao->assertFailed();
})->with([
    'borda−1, no incremento do tipo (0,01 pp)'               => [0.01, 'reprova'],
    'arredonda para a meta, mas está abaixo — mata round()'  => [0.004, 'reprova'],
    'borda exata — o piso é inclusivo'                       => [0.0, 'aprova'],
    'borda+1'                                                => [-0.01, 'aprova'],
])->group('kit');

/**
 * [CT-06] Piso FRACIONÁRIO: truncar antes de comparar aprovaria 78,40 contra um piso de 78,50.
 *
 * As duas linhas usam números LITERAIS, e são a única forma de existirem: com meta inteira,
 * `round()` e `floor()` concordam em toda borda inteira — só um piso com casa decimal separa
 * truncamento de arredondamento (M17). A notação do `--min` é a da CLI (ponto), não a do valor em
 * pt-BR do Gherkin (vírgula).
 */
it('[CT-06] o piso fracionário não é truncado nem arredondado antes da comparação', function (int $cobertas, string $piso, string $veredito): void {
    $execucao = $this->artisan('kit:cobertura', [
        'clover' => cloverCom($this->pasta, $cobertas, 10_000),
        '--min'  => $piso,
    ])->expectsOutputToContain("({$cobertas} / 10000 statements)");

    $veredito === 'aprova' ? $execucao->assertSuccessful() : $execucao->assertFailed();
})->with([
    'truncar para 78 aprovaria' => [7840, '78.5', 'reprova'],
    'borda exata fracionária'   => [7850, '78.5', 'aprova'],
])->group('kit');

/**
 * [CT-07] Sem `--min`, o verificador mede e NÃO reprova por cobertura — e não afirma piso nenhum.
 *
 * (corrigido em 2026-09-26, junto do `04`: a reconciliação de 2026-09-25 mostrou que não existe
 * piso padrão — a meta mora no `--min` do `composer.json` e do `ci.yml`, travada por CT-18. A
 * redação anterior deste cenário assumia um default que a implementação nunca teve.)
 */
it('[CT-07] sem piso informado, o verificador mede e não reprova por cobertura', function (): void {
    $clover = relatorioComDesvio($this->pasta, 0.01);

    $this->artisan('kit:cobertura', ['clover' => $clover])
        ->doesntExpectOutputToContain('piso foi respeitado')
        ->assertSuccessful();
})->group('kit');

/**
 * [CT-08] A razão é PONDERADA pelo tamanho dos arquivos, não a média dos percentuais por arquivo.
 *
 * Guarda de regressão, por construção: `metricasDo()` só lê `<project><metrics>` — já agregado —,
 * então um mutante de "média por arquivo" não é expressável nesta redação (achado da
 * reconciliação). Os `<file>` existem pela fidelidade ao Gherkin.
 */
it('[CT-08] a razão é ponderada pelo tamanho dos arquivos, não a média dos percentuais', function (): void {
    $clover = relatorioComArquivos(
        $this->pasta,
        ['A.php' => [10, 10], 'B.php' => [495, 990]],
        cobertasAgregado: 505,
        totalAgregado: 1000,
    );

    $this->artisan('kit:cobertura', ['clover' => $clover, '--min' => '60'])
        ->expectsOutputToContain('50.50%')
        ->assertFailed();
})->group('kit');

/**
 * [CT-09] A razão comparada é a de LINHAS, e não a de elementos/métodos — `@premissa` de mecanismo.
 *
 * Risco real: o Clover também traz `elements`/`coveredelements`, e a fixture das linhas mensuráveis
 * diverge de propósito das de elementos (60 % × 90 %) para que uma implementação que lesse o par
 * errado APROVASSE contra o piso 80.
 */
it('[CT-09] a razão comparada é a de linhas, e não a de elementos', function (): void {
    $clover = relatorioComRazoesDivergentes($this->pasta, linhas: 60.0, elementos: 90.0);

    $this->artisan('kit:cobertura', ['clover' => $clover, '--min' => '80'])
        ->expectsOutputToContain('60.00%')
        ->assertFailed();
})->group('kit');

/**
 * [CT-10] Cada valor de `--min` é aceito ou recusado NO PONTO em que é informado.
 *
 * Fixture 100 % coberta (achado da revisão adversarial no `04`): com uma fixture abaixo da meta,
 * "recusa o piso" e "reprova a cobertura" produzem o MESMO código de saída, e três linhas do
 * `Esquema` não matavam nada. Aqui um piso válido APROVA, e a recusa se distingue por veredito e
 * por mensagem — que nomeia `--min`, e não a cobertura.
 */
it('[CT-10] cada valor de piso é aceito ou recusado no ponto em que é informado', function (string $piso, string $veredito): void {
    $clover = cloverCom($this->pasta, 100, 100);

    $execucao = $this->artisan('kit:cobertura', ['clover' => $clover, '--min' => $piso]);

    if ($veredito === 'recusa') {
        $execucao = $execucao
            ->expectsOutputToContain('`--min`')
            ->doesntExpectOutputToContain('Cobertura de');
    }

    $veredito === 'recusa' ? $execucao->assertFailed() : $execucao->assertSuccessful();
})->with([
    'abaixo do domínio'                                                  => ['-1', 'recusa'],
    '@premissa: piso que não reprova ninguém não é piso (falha fechado)' => ['0', 'recusa'],
    'não numérico — a coerção silenciosa para 0 é o defeito'             => ['abc', 'recusa'],
    'vazio ≠ ausente: ausente cai em CT-07'                              => ['', 'recusa'],
    'acima do domínio'                                                   => ['101', 'recusa'],
    'sufixo: aceitar e truncar é coerção silenciosa'                     => ['78%', 'recusa'],
    'espaços nas bordas: normaliza para 78 — nunca vira 0'               => [' 78 ', 'aceito'],
    'notação científica vale 100: não abre a guarda'                     => ['1e2', 'aceito'],
    '@premissa de mecanismo: piso fracionário é válido'                  => ['78.5', 'aceito'],
    'borda superior válida — e, com este relatório, aprova'              => ['100', 'aceito'],
])->group('kit');

/**
 * [CT-11] Piso inválido NUNCA transforma uma medição abaixo da meta em aprovação, nem toca o badge.
 *
 * Invariante extraído da premissa de CT-10 pela revisão adversarial: seja qual for a resposta
 * sobre o piso `0`, isto vale sempre.
 */
it('[CT-11] piso inválido nunca aprova uma medição abaixo da meta, nem toca o badge', function (): void {
    $badge  = badgeDePartida($this->pasta);
    $clover = relatorioComDesvio($this->pasta, 0.01);

    $codigo = Artisan::call('kit:cobertura', ['clover' => $clover, '--min' => 'abc']);
    $saida  = Artisan::output();

    expect($codigo)->not->toBe(0);
    $this->assertStringNotContainsString('Badge confere', $saida, 'a saída afirmou aprovação com um piso inválido');

    expect(json_decode((string) File::get($badge), true))->toMatchArray(['message' => '42%', 'color' => 'red']);
})->group('kit');

/**
 * [CT-12] Caminho de relatório que não É um relatório recusa, NOMEANDO o caminho.
 *
 * A linha `espaco` é a ÚNICA partição VÁLIDA do Esquema, de propósito: sem ela, uma implementação
 * que recusasse todo caminho passaria nas outras duas. Ela roda SEM badge de partida — criar um
 * badge divergente derrubaria esta linha por um motivo que não é dela (CT-11/CT-30 são quem
 * cobre badge divergente).
 *
 * A saída nomeia o caminho RELATIVO ao `base_path()` corrente, não o absoluto: o próprio Artisan
 * (`Illuminate\Console\View\Components\Mutators\EnsureRelativePaths`) reescreve toda mensagem de
 * `components->error()`/`success()` removendo o prefixo de `base_path()` — comportamento do
 * framework, não desta feature.
 *
 * A linha "ausente" também é reforçada pelos CT-27/CT-28 da wiki phpstan-nivel-8: código de saída
 * INVALID (2), a dica `composer test:coverage` presente, e "Relatório Clover ilegível" ausente —
 * a recusa para na PRIMEIRA causa e não segue para a próxima verificação.
 */
it('[CT-12] caminho de relatório que não é um relatório recusa, nomeando o caminho', function (string $tipo): void {
    if ($tipo === 'espaco') {
        $clover = $this->pasta.'/relatorio com espaco.xml';
        File::put($clover, '<?xml version="1.0" encoding="UTF-8"?><coverage><project timestamp="0"><metrics statements="10" coveredstatements="10"/></project></coverage>');

        $this->artisan('kit:cobertura', ['clover' => $clover])->assertSuccessful();

        return;
    }

    $badge  = badgeDePartida($this->pasta);
    $clover = $tipo === 'diretorio' ? $this->pasta : $this->pasta.'/nao-existe.xml';

    $codigo = Artisan::call('kit:cobertura', ['clover' => $clover]);
    $saida  = Artisan::output();

    $caminhoExibido = str_replace(base_path().'/', '', $clover);

    expect($codigo)->not->toBe(0);
    $this->assertStringContainsString($caminhoExibido, $saida, 'a saída não nomeou o caminho recebido');
    $this->assertStringContainsString('não encontrado', $saida, 'a saída não disse que não encontrou um relatório legível');
    $this->assertDoesNotMatchRegularExpression('~\d+(\.\d+)?%~', $saida, 'a saída exibiu um percentual sem ter medido nada');

    expect(json_decode((string) File::get($badge), true))->toMatchArray(['message' => '42%', 'color' => 'red']);

    if ($tipo === 'ausente') {
        expect($codigo)->toBe(Command::INVALID);
        // Sem o ponto final: `components->bulletList()` roda todo elemento pelo mutator
        // `EnsureNoPunctuation` (vendor/laravel/framework/.../Mutators/EnsureNoPunctuation.php),
        // que remove pontuação final de QUALQUER bullet — o ponto do texto-fonte nunca chega
        // ao terminal.
        $this->assertStringContainsString('Gere-o com `composer test:coverage`', $saida, 'a saída não trouxe a dica do comando');
        $this->assertStringNotContainsString('Relatório Clover ilegível', $saida, 'a recusa não parou na primeira causa');
    }
})->with([
    'ausente'                     => ['ausente'],
    'existe, e não é arquivo'     => ['diretorio'],
    'Windows: aceito, sucesso'    => ['espaco'],
])->group('kit');

/**
 * [CT-13] Relatório ILEGÍVEL ou sem `<project><metrics>` recusa — as duas mensagens são guardadas.
 *
 * O comando tem três mensagens de recusa distintas; apagar qualquer uma deixa a suíte verde
 * (achado da reconciliação). Esta cobre as duas primeiras; a terceira (zero statements) é CT-14,
 * porque só ela tem o `Então` extra "não vale 100 %".
 *
 * A linha "ilegivel" também é reforçada pelos CT-27/CT-28 da wiki phpstan-nivel-8: a saída não
 * reclama de métricas — a recusa para na causa "ilegível" e não segue para a próxima verificação.
 */
it('[CT-13] relatório ilegível ou sem métricas recusa, sem aprovar e sem publicar número', function (string $defeito, string $mensagem): void {
    $badge = badgeDePartida($this->pasta);

    $clover = match ($defeito) {
        'ilegivel'     => tap($this->pasta.'/quebrado.xml', fn (string $c) => File::put($c, '<coverage><project>')),
        'sem metricas' => cloverCom($this->pasta, null, null),
    };

    $codigo = Artisan::call('kit:cobertura', ['clover' => $clover]);
    $saida  = Artisan::output();

    expect($codigo)->not->toBe(0);
    $this->assertStringContainsString($mensagem, $saida);
    $this->assertDoesNotMatchRegularExpression('~\d+(\.\d+)?%~', $saida, 'a saída exibiu um percentual sem ter lido o relatório');

    expect(json_decode((string) File::get($badge), true))->toMatchArray(['message' => '42%', 'color' => 'red']);

    if ($defeito === 'ilegivel') {
        $this->assertStringNotContainsString('Relatório sem `<project><metrics>`', $saida, 'a recusa não parou na primeira causa');
    }
})->with([
    'ilegivel'     => ['ilegivel', 'Relatório Clover ilegível'],
    'sem metricas' => ['sem metricas', 'sem `<project><metrics>`'],
])->group('kit');

/**
 * [CT-14] Relatório sem NENHUMA linha mensurável recusa, e NÃO vale 100 % — a divisão `0/0`.
 *
 * O cenário mais importante do conjunto: numa implementação ingênua, `0 mensuráveis` vira `0/0`
 * vira `100 %`, e aprova exatamente o relatório que um driver de cobertura não carregado produz.
 */
it('[CT-14] relatório sem nenhuma linha mensurável recusa, e não vale 100 por cento', function (): void {
    $badge  = badgeDePartida($this->pasta);
    $clover = cloverCom($this->pasta, 0, 0);

    $codigo = Artisan::call('kit:cobertura', ['clover' => $clover, '--min' => (string) metaDoKit()]);
    $saida  = Artisan::output();

    expect($codigo)->not->toBe(0);
    $this->assertStringContainsString('zero statements', $saida);
    $this->assertStringNotContainsString('100', $saida, 'o comando tratou 0/0 como 100% e aprovou um relatório vazio');

    expect(json_decode((string) File::get($badge), true))->toMatchArray(['message' => '42%', 'color' => 'red']);
})->group('kit');

/**
 * [CT-15] A 1ª verificação MOVE o badge; a 2ª não move mais nada.
 *
 * A direção POSITIVA vem primeiro (o badge SAI de 42 % e CHEGA à razão do relatório) — sem ela, um
 * escritor no-op ou `max(medido, meta)` provaria "idempotência" por não fazer nada (M33/M34).
 */
it('[CT-15] a primeira verificação move o badge, e a segunda não move mais nada', function (): void {
    $badge  = badgeDePartida($this->pasta);
    $clover = relatorioComDesvio($this->pasta, -5.0);

    $conteudoAntes = (string) File::get($clover);

    $codigo1 = Artisan::call('kit:cobertura', ['clover' => $clover, '--write' => true]);
    $saida1  = Artisan::output();
    expect($codigo1)->toBe(0);

    $badgeApos1 = json_decode((string) File::get($badge), true);
    expect($badgeApos1['message'])->not->toBe('42%', 'a primeira execução não moveu o badge');

    $codigo2 = Artisan::call('kit:cobertura', ['clover' => $clover, '--write' => true]);
    $saida2  = Artisan::output();
    expect($codigo2)->toBe(0);

    preg_match('~\d+\.\d{2}% \(\d+ / \d+ statements\)~', $saida1, $razao1);
    preg_match('~\d+\.\d{2}% \(\d+ / \d+ statements\)~', $saida2, $razao2);

    expect($razao1[0] ?? null)->not->toBeNull('não achei a razão exibida na 1ª execução')
        ->and($razao2[0] ?? null)->toBe($razao1[0] ?? null, 'as duas razões exibidas divergem');

    $this->assertSame(
        (string) File::get($badge),
        json_encode($badgeApos1, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        'o badge depois da segunda execução não é byte a byte igual ao de depois da primeira',
    );
    $this->assertSame($conteudoAntes, (string) File::get($clover), 'a verificação alterou o relatório de cobertura');
})->group('kit');

/**
 * [CT-16] Ao reprovar, a saída diz o MEDIDO, o PISO aplicado, e um próximo passo que EXISTE.
 */
it('[CT-16] ao reprovar, a saída exibe o medido, o piso aplicado e um próximo passo que existe', function (): void {
    badgeDePartida($this->pasta);

    $meta   = metaDoKit();
    $clover = relatorioComDesvio($this->pasta, 0.01);

    $codigo = Artisan::call('kit:cobertura', ['clover' => $clover, '--min' => (string) $meta]);
    $saida  = Artisan::output();

    expect($codigo)->not->toBe(0);

    $this->assertMatchesRegularExpression('~\d{1,3}\.\d{2}%~', $saida, 'a saída não exibiu a razão medida');
    $this->assertStringContainsString((string) (int) $meta, $saida, 'a saída não citou o piso aplicado');
    $this->assertStringContainsString('composer test:coverage', $saida, 'a saída não indicou um próximo passo');

    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);
    $this->assertArrayHasKey(
        'test:coverage',
        $composer['scripts'] ?? [],
        'o alvo indicado na mensagem não existe entre os declarados do projeto',
    );
})->group('kit');

/**
 * [CT-17] O badge publicado é a razão do relatório verificado — qualquer que seja ela.
 *
 * Sem este cenário o eixo do badge era tautológico: `badge >= meta` (CT-20) é satisfeito por um
 * badge estático, por um número escrito à mão ou por `max(medido, meta)`. Dois relatórios de
 * razões diferentes têm de produzir dois badges diferentes (M38).
 */
it('[CT-17] o badge acompanha a razão do relatório verificado, acima da meta e na borda', function (float $desvioPP): void {
    $badge  = badgeDePartida($this->pasta);
    $meta   = metaDoKit();
    $clover = relatorioComDesvio($this->pasta, $desvioPP);

    $esperado = ((int) floor($meta - $desvioPP)).'%';

    $this->artisan('kit:cobertura', ['clover' => $clover, '--write' => true])->assertSuccessful();

    expect(json_decode((string) File::get($badge), true)['message'])->toBe($esperado);
})->with([
    'acima da meta — mata o badge constante e o no-op' => [-5.0],
    'na borda — mata o badge que só sobe'              => [0.0],
])->group('kit');

/**
 * [CT-29] O badge guarda o INTEIRO TRUNCADO e a COR DA FAIXA — mecanismo detalhado de CT-17.
 *
 * Truncar, e não arredondar, é o que mantém o badge do lado seguro: ele nunca anuncia mais
 * cobertura do que existe.
 */
it('[CT-29] grava o badge com o inteiro truncado e a cor da faixa', function (int $cobertas, string $mensagem, string $cor): void {
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
 * [CT-30] Conferindo (sem `--write`), o badge que MENTE reprova — e o que só está reformatado, não.
 *
 * A redação anterior comparava o JSON byte a byte, e reindentar o arquivo produzia a mensagem
 * autocontraditória "o badge commitado diz 79% e o medido é 79%". Guarda que reprova por ruído
 * vira alarme falso.
 */
it('[CT-30] confere o badge pelo valor, e não pelos bytes', function (string $json, bool $passa): void {
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
 * [CT-30] As mensagens de badge divergente dizem OS DOIS valores, e distinguem ausente de diferente.
 */
it('[CT-30] a mensagem de badge divergente nomeia o commitado e o medido', function (?string $json, string $pedaco): void {
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
 * [CT-31] Fora da árvore do kit não há badge, e o piso continua valendo.
 *
 * `.github/` é `export-ignore`: num projeto instalado o diretório de badges não existe, e o
 * comando não escreve nem confere badge nenhum — sem cobrar do usuário um badge que nunca foi dele.
 */
it('[CT-31] fora da árvore do kit ignora o badge e mantém o piso', function (): void {
    expect(File::exists($this->pasta.'/.github/badges'))->toBeFalse();

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 80, 100), '--min' => '78'])
        ->assertSuccessful();

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 70, 100), '--min' => '78'])
        ->assertFailed();

    expect(File::exists($this->pasta.'/.github/badges/cobertura.json'))->toBeFalse();
})->group('kit');

/**
 * [CT-32] Escrever o badge SUBSTITUI o anterior, sem reclamar dele.
 *
 * Sem este caso, remover o `return` que encerra o caminho de escrita passaria despercebido: o
 * comando cairia na conferência logo depois de gravar, e como o que ele acabou de gravar confere
 * consigo mesmo, ninguém notaria.
 */
it('[CT-32] --write substitui um badge divergente sem reclamar dele', function (): void {
    badgeDePartida($this->pasta, '12%', 'red');

    $this->artisan('kit:cobertura', ['clover' => cloverCom($this->pasta, 7900, 10_000), '--write' => true])
        ->assertSuccessful();

    expect(json_decode((string) File::get($this->pasta.'/.github/badges/cobertura.json'), true))
        ->toMatchArray(['message' => '79%', 'color' => 'green']);
})->group('kit');
