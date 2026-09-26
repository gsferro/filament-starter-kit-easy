<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Guardas de documentação, `composer.json`, `ci.yml` e `phpunit.xml` para a feature de cobertura
 * de testes medida — IDs de
 * `wikis/specs/feat/cobertura-de-testes/cobertura-de-testes/04-casos-de-teste.md`.
 *
 * Por que um arquivo à parte de `KitCoberturaTest.php`: aquele relocaliza a raiz da aplicação num
 * `beforeEach` global (para rodar `kit:cobertura` num mundo sem `.github/badges` de verdade). Os
 * casos daqui NÃO chamam o comando — leem arquivo e configuração — e usam `base_path()`
 * normalmente, sem a relocalização.
 */

/** O trecho de YAML sem as linhas de comentário (`#`) — para asserção de AUSÊNCIA, `.ai/rules/testes.md`. */
function semComentarioYaml(string $yaml): string
{
    return implode("\n", array_filter(
        explode("\n", $yaml),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '#'),
    ));
}

it('[CT-19] a documentação em inglês declara os mesmos quatro números que a em português', function (): void {
    $pt = (string) file_get_contents(base_path('docs/pt/referencia/qualidade-de-codigo.md'));
    $en = (string) file_get_contents(base_path('docs/en/referencia/qualidade-de-codigo.md'));

    // a meta de cobertura
    preg_match('~### O piso é (\d{1,3}) %~', $pt, $metaPt);
    preg_match('~### The floor is (\d{1,3})%~', $en, $metaEn);
    $this->assertNotEmpty($metaPt, 'não achei a meta rotulada na doc pt');
    $this->assertSame($metaPt[1], $metaEn[1] ?? null, 'a meta de cobertura diverge entre pt e en');

    // o tempo medido (local e CI)
    preg_match_all('~\*\*(\d+) min\*\*~', $pt, $temposPt);
    preg_match_all('~\*\*(\d+) min\*\*~', $en, $temposEn);
    $this->assertNotEmpty($temposPt[1], 'não achei o tempo medido na doc pt');
    $this->assertSame($temposPt[1], $temposEn[1] ?? [], 'o tempo medido da suíte com cobertura diverge entre pt e en');

    // o custo de manter o Xdebug carregado
    preg_match('~\*\*\+(\d+)\s?%\*\*~', $pt, $custoPt);
    preg_match('~\*\*\+(\d+)\s?%\*\*~', $en, $custoEn);
    $this->assertNotEmpty($custoPt, 'não achei o custo do Xdebug na doc pt');
    $this->assertSame($custoPt[1], $custoEn[1] ?? null, 'o custo de manter o Xdebug carregado diverge entre pt e en');

    // o comando de medição — mesma grafia, e PAREADO à mesma meta que as duas acabaram de declarar
    $comandoEsperado = "php artisan kit:cobertura cobertura.xml --min={$metaPt[1]}";
    $this->assertStringContainsString('composer test:coverage', $pt);
    $this->assertStringContainsString('composer test:coverage', $en);
    $this->assertStringContainsString($comandoEsperado, $pt, 'o comando de medição citado na doc pt não bate com a meta que ela mesma declara');
    $this->assertStringContainsString($comandoEsperado, $en, 'a doc en não cita o mesmo comando de medição que a pt');
})->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório do site é export-ignore: não existe em projeto nascido de create-project.')->group('kit');

it('[CT-20] o badge do readme aponta para um alvo que existe, e não contradiz a meta documentada', function (): void {
    $readme = (string) file_get_contents(base_path('README.md'));

    preg_match('~!\[Cobertura\]\(https://img\.shields\.io/endpoint\?url=([^&)]+)~', $readme, $casado);
    $this->assertNotEmpty($casado, 'não achei o badge de cobertura no README');

    $url = urldecode($casado[1]);

    preg_match('~githubusercontent\.com/[^/]+/[^/]+/main/(.+)$~', $url, $caminhoCasado);
    $this->assertNotEmpty($caminhoCasado, 'a url do badge não aponta para um caminho reconhecível na árvore do repositório');

    $caminho = $caminhoCasado[1];
    $this->assertTrue(File::exists(base_path($caminho)), "o alvo do badge (`{$caminho}`) não existe na árvore do kit");

    $badge      = json_decode((string) File::get(base_path($caminho)), true);
    $percentual = (int) rtrim((string) ($badge['message'] ?? '0%'), '%');

    $doc = (string) file_get_contents(base_path('docs/pt/referencia/qualidade-de-codigo.md'));
    preg_match('~### O piso é (\d{1,3}) %~', $doc, $metaCasado);
    $meta = (int) ($metaCasado[1] ?? 0);

    $this->assertGreaterThanOrEqual($meta, $percentual, "o badge do README exibe {$percentual}% e a meta documentada é {$meta}%");
})->skip(fn (): bool => ! naArvoreDoKit(), '.github/ e o diretório do site são export-ignore.')->group('kit');

/**
 * [CT-21] Cada item obrigatório está documentado na FORMA exigida, no idioma.
 *
 * A coluna `forma` é o que impede o cenário de degenerar em `str_contains` solto: um documento que
 * listasse nomes de ferramentas, publicasse como "tempo com cobertura" o tempo SEM cobertura, ou
 * citasse a meta numa tabela comparativa, passaria numa checagem só de presença.
 */
it('[CT-21] cada item obrigatório está presente na seção do assunto, na forma exigida', function (string $idioma, array $trechosExigidos, string $mensagem): void {
    $doc = (string) file_get_contents(base_path("docs/{$idioma}/referencia/qualidade-de-codigo.md"));

    foreach ($trechosExigidos as $trecho) {
        $this->assertStringContainsString($trecho, $doc, $mensagem);
    }
})->with([
    'pt: o comando de medição de cobertura' => [
        'pt', ['composer test:coverage'], 'não achei o comando de medição documentado em pt',
    ],
    'en: o comando de medição de cobertura' => [
        'en', ['composer test:coverage'], 'the coverage measurement command is not documented in en',
    ],
    'pt: quando e onde medir' => [
        'pt', ['disparo manual', 'não em toda PR'], 'não achei o quando/onde medir em pt',
    ],
    'en: quando e onde medir' => [
        'en', ['manual dispatch', 'not on every PR'], 'when/where to measure is not documented in en',
    ],
    'pt: o tempo medido, pareado ao comando com cobertura' => [
        'pt', ['composer test:coverage    # mede, grava o badge e aplica o piso'], 'o tempo publicado em pt não está pareado ao comando COM cobertura',
    ],
    'en: o tempo medido, pareado ao comando com cobertura' => [
        'en', ['composer test:coverage    # measures, writes the badge and applies the floor'], 'the published time in en is not paired with the WITH-coverage command',
    ],
    'pt: a meta de cobertura, com justificativa' => [
        'pt', ['### O piso é', 'não é folclore'], 'não achei a meta rotulada com justificativa em pt',
    ],
    'en: a meta de cobertura, com justificativa' => [
        'en', ['### The floor is', 'not folklore'], 'the labelled floor with justification is missing in en',
    ],
    'pt: o custo de manter o Xdebug carregado, com e sem' => [
        'pt', ['pcov.enabled=0', 'liga só na invocação'], 'não achei os dois números e a decisão sobre o custo do Xdebug em pt',
    ],
    'en: o custo de manter o Xdebug carregado, com e sem' => [
        'en', ['pcov.enabled=0', 'switched on per invocation'], 'the two numbers and the Xdebug decision are missing in en',
    ],
    'pt: o levantamento de níveis de qualidade, por candidato' => [
        'pt', ['Os três critérios de corte', '**(a)**', '**(b)**', '**(c)**', 'roadmap', 'adotado'], 'não achei o levantamento com as três perguntas de corte em pt',
    ],
    'en: o levantamento de níveis de qualidade, por candidato' => [
        'en', ['The three cut-off criteria', '**(a)**', '**(b)**', '**(c)**', 'roadmap', 'adopted'], 'the survey with the three cut-off criteria is missing in en',
    ],
])->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório do site é export-ignore.')->group('kit');

/**
 * [CT-22] O comando de medição documentado MEDE DE VERDADE.
 *
 * Fecha o laço configuração → comando → relatório → verificador: um alvo com o nome certo que não
 * ligasse a coleta, gravasse em outro formato ou medisse outro recorte passaria numa checagem que
 * só conferisse a GRAFIA do nome (o que a rodada 1 do `04` fazia).
 */
it('[CT-22] o comando de medição documentado mede de verdade', function (): void {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

    $script = $composer['scripts']['test:coverage'] ?? null;
    $this->assertNotNull($script, '`composer.json` não declara mais o `test:coverage` — o `04` (CT-22) precisa mudar junto');

    $linhaPest = collect($script)->first(fn (string $linha): bool => str_contains($linha, 'vendor/bin/pest'));
    $this->assertNotNull($linhaPest, 'não achei a chamada ao pest dentro de `test:coverage`');

    $this->assertStringContainsString('pcov.enabled=1', $linhaPest, 'o comando documentado não liga a coleta de cobertura');
    $this->assertStringContainsString('--coverage-clover=', $linhaPest, 'o comando documentado não grava Clover — o formato que o verificador lê');
    $this->assertStringNotContainsString('--coverage-filter', $linhaPest, 'o comando documentado estreita o recorte na linha de comando — CT-04/CT-05 deixariam de valer para ele');

    $linhaPestSemArroba = ltrim($linhaPest, '@');

    $doc = (string) file_get_contents(base_path('docs/pt/referencia/qualidade-de-codigo.md'));
    $this->assertStringContainsString('composer test:coverage', $doc, 'a documentação não cita `composer test:coverage`, com a mesma grafia do `composer.json`');
    $this->assertStringContainsString($linhaPestSemArroba, $doc, 'a documentação não reproduz a mesma linha de medição que o `composer.json` declara — o recorte ou a coleta podem ter divergido');
})->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório do site é export-ignore.')->group('kit');

/**
 * [CT-23] Nenhuma etapa da cadeia de CI descarta o código de saída do verificador de cobertura.
 *
 * Asserção de AUSÊNCIA sobre arquivo documentado: o `ci.yml` explica, em comentário, por que o
 * job não roda em pull request — filtrar comentário evita reprovar pela própria documentação
 * (`.ai/rules/testes.md`).
 *
 * (alterado em 2026-09-26, junto do `04`: a cláusula do GATILHO saiu — o job de cobertura leva
 * ~52 min no runner e não roda em PR por decisão registrada no próprio `ci.yml`; M56 continua sem
 * matador, e declarado no `04`.)
 */
it('[CT-23] nenhuma etapa da cadeia descarta o código de saída do verificador', function (): void {
    $ci = (string) file_get_contents(base_path('.github/workflows/ci.yml'));

    preg_match('~\n  cobertura:.*?(?=\n  [a-z][\w-]*:\n|\z)~s', $ci, $job);
    $this->assertNotEmpty($job, 'não achei o job `cobertura` no `ci.yml`');

    $jobSemComentario = semComentarioYaml($job[0]);

    $this->assertStringNotContainsString(
        'continue-on-error',
        $jobSemComentario,
        'o job `cobertura`, ou algum dos seus passos, está marcado como tolerante a erro',
    );

    preg_match('~Medir a cobertura e conferir o badge.*?run: \|\n(.*?)(?=\n  - name:|\n  [a-z][\w-]*:\n|\z)~s', $job[0], $passo);
    $this->assertNotEmpty($passo, 'não achei o passo que aplica o piso de cobertura');

    $comando = semComentarioYaml($passo[1]);

    $this->assertStringNotContainsString('|| true', $comando, 'o comando descarta o código de saída com `|| true`');
    $this->assertStringNotContainsString('|| exit 0', $comando, 'o comando descarta o código de saída com `|| exit 0`');
    $this->assertStringNotContainsString('|', $comando, 'o comando entra em tubulação — o código de saída do processo interno pode não propagar');
})->skip(fn (): bool => ! naArvoreDoKit(), '.github/ é export-ignore.')->group('kit');

/**
 * [CT-24] O piso é aplicado sobre um relatório produzido NA MESMA execução, pelo comando documentado.
 */
it('[CT-24] o piso é aplicado sobre um relatório da mesma execução, e não um arquivo versionado', function (): void {
    $ci = (string) file_get_contents(base_path('.github/workflows/ci.yml'));

    preg_match('~\n  cobertura:.*?(?=\n  [a-z][\w-]*:\n|\z)~s', $ci, $job);
    $this->assertNotEmpty($job, 'não achei o job `cobertura`');

    $texto = $job[0];

    $this->assertStringContainsString('vendor/bin/pest', $texto, 'o job de cobertura não mede com o alvo que CT-22 valida');
    $this->assertStringContainsString('kit:cobertura', $texto, 'o job de cobertura não aplica o piso');

    preg_match('~coverage-clover=(\S+)~', $texto, $arquivoGerado);
    preg_match('~kit:cobertura\s+(\S+)~', $texto, $arquivoConsumido);

    $this->assertNotEmpty($arquivoGerado, 'não achei o arquivo que a medição gera');
    $this->assertNotEmpty($arquivoConsumido, 'não achei o arquivo que o piso consome');
    $this->assertSame(
        $arquivoGerado[1],
        $arquivoConsumido[1],
        'o piso é aplicado sobre um arquivo diferente do que a medição acabou de produzir, no mesmo job',
    );

    $processo = new Process(['git', 'ls-files', '--', $arquivoConsumido[1]], base_path());
    $processo->run();

    $this->assertSame(
        '',
        trim($processo->getOutput()),
        "`{$arquivoConsumido[1]}` está versionado na árvore — o piso deixaria de ser aplicado sobre a medição da própria execução",
    );
})->skip(fn (): bool => ! naArvoreDoKit(), '.github/ é export-ignore.')->group('kit');

/**
 * [CT-26] O plugin de mutação é dependência DIRETA, e a opção é reconhecida pelo executor de testes.
 *
 * O plugin costuma existir em `vendor/` como dependência TRANSITIVA do Pest — o comando funciona
 * por acidente da árvore de dependências e desaparece num `composer update`, sem nada ficar
 * vermelho.
 */
it('[CT-26] o plugin de mutação é dependência direta, e a opção é reconhecida pelo executor', function (): void {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

    $restricao = $composer['require-dev']['pestphp/pest-plugin-mutate'] ?? null;

    $this->assertNotNull($restricao, '`pestphp/pest-plugin-mutate` não está em `require-dev` — pode ser dependência transitiva do Pest');
    $this->assertNotSame('*', $restricao, 'a restrição de versão do plugin de mutação é frouxa demais (`*`) para travar nada');

    $processo = new Process(['php', 'vendor/bin/pest', '--help'], base_path());
    $processo->run();

    $this->assertStringContainsString(
        '--mutate',
        $processo->getOutput().$processo->getErrorOutput(),
        'o executor de testes do projeto não reconhece a opção `--mutate`',
    );
})->skip(fn (): bool => ! naArvoreDoKit(), 'vendor/bin/pest só existe com as dependências de desenvolvimento instaladas.')->group('kit');

/**
 * [CT-27] O mutation score publicado vem com DURAÇÃO, PLATAFORMA e sobreviventes NOMEADOS.
 *
 * A rodada 1 do `04` exigia só "duração" e "sobreviventes" — e o 100 % falso do Windows (o plugin
 * relança `argv[0]`, o `cmd` não executa um script `sh`, e toda saída não-zero é contada como
 * mutante morto) satisfazia as duas. Aqui a exigência inclui a PLATAFORMA e os sobreviventes
 * nomeados por arquivo e linha, quando existem.
 */
it('[CT-27] o mutation score publicado vem com duração, plataforma e sobreviventes nomeados', function (): void {
    $doc = (string) file_get_contents(base_path('docs/pt/referencia/qualidade-de-codigo.md'));

    preg_match('~## O mutation score.*?(?=\n## |\z)~s', $doc, $secao);
    $this->assertNotEmpty($secao, 'não achei a seção do mutation score');

    $texto = $secao[0];

    $this->assertMatchesRegularExpression('~\d+[,.]?\d*\s*s\b~', $texto, 'a seção não publica a duração da execução');
    $this->assertMatchesRegularExpression('~Windows|Linux|macOS~', $texto, 'a seção não publica a plataforma em que a execução rodou');

    preg_match_all('~(\d+)\s+não testados~', $texto, $contagens);
    $this->assertNotEmpty($contagens[1], 'a seção não publica a contagem de mutantes sobreviventes');

    foreach ($contagens[1] as $quantidade) {
        if ((int) $quantidade === 0) {
            continue;
        }

        $this->assertMatchesRegularExpression(
            '~app/\S+\.php:\d+~',
            $texto,
            "há {$quantidade} mutante(s) sobrevivente(s) publicado(s) sem nomear arquivo:linha — RQ-14 pede sobreviventes auditáveis, não só contados",
        );
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'o diretório do site é export-ignore.')->group('kit');

/**
 * [CT-18] O piso do `composer.json` e do `ci.yml` é o MESMO número que a documentação declara.
 *
 * Movido do antigo CT-51 de `SiteDeDocumentacaoTest` (achado #1 da reconciliação do `04`): `--min=78`
 * está escrito à mão nos dois lugares, e só a documentação carrega a JUSTIFICATIVA (RQ-06 pede
 * "escolhido e justificado"). Baixar o `--min` para 70 nos dois lugares não deixava nada vermelho
 * até este caso existir.
 *
 * Mora aqui, e não em `KitCoberturaTest`: o `beforeEach` daquele arquivo relocaliza a raiz da
 * aplicação para uma pasta temporária, e a sentinela `naArvoreDoKit()` responderia sobre ela.
 */
it('[CT-18] mantem o piso do composer e do ci igual a meta declarada na documentacao', function (): void {
    $raiz = base_path();

    $doc = (string) file_get_contents($raiz.'/docs/pt/referencia/qualidade-de-codigo.md');

    expect($doc)->toMatch('~### O piso é \d{1,3} %~');

    preg_match('~### O piso é (\d{1,3}) %~', $doc, $casado);

    $meta = $casado[1];

    foreach ([
        'composer.json'            => '~kit:cobertura[^"]*--min=(\d{1,3})~',
        '.github/workflows/ci.yml' => '~kit:cobertura[^
]*--min=(\d{1,3})~',
    ] as $arquivo => $padrao) {
        preg_match_all($padrao, (string) file_get_contents($raiz.'/'.$arquivo), $pisos);

        $this->assertNotEmpty(
            $pisos[1],
            "não achei nenhum `--min` de `kit:cobertura` em `{$arquivo}` — se o comando saiu de lá, este caso precisa mudar junto",
        );

        foreach ($pisos[1] as $piso) {
            $this->assertSame(
                $meta,
                $piso,
                "`{$arquivo}` aplica o piso {$piso} e a documentação declara a meta {$meta} — um dos dois mente",
            );
        }
    }
})->skip(fn (): bool => ! naArvoreDoKit(), '.github/ e o diretório do site são export-ignore.')->group('kit');
