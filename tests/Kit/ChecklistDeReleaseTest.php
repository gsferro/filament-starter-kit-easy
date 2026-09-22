<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Os quatro cenários obrigatórios de `wikis/checklist-de-release.md`, e a fronteira entre o
 * que o roteiro entrega (texto) e o que ele só pode provar fora do arnês (execução real).
 *
 * IDs de CT em
 * `wikis/specs/fix/validacao-de-release/validacao-de-release/04-casos-de-teste.md` (versão pós
 * revisão adversarial, duas rodadas — RD-02).
 *
 * ## A regra do canário único (achado B2 da rodada 2)
 *
 * `wikis/checklist-de-release.md` viaja para todo projeto instalado. Onze casos afirmando sobre a
 * REDAÇÃO do roteiro sem guarda institucionalizariam o defeito desta wiki: reescrever uma frase do
 * roteiro deixaria vermelha a suíte de todo projeto de terceiro. A regra: **apenas CT-08 lê o
 * roteiro sem guarda**, e ele afirma só existência e destinatário — o resto carrega
 * `naArvoreDoKit()` na própria declaração do caso. CT-21 executa esta regra por inspeção de
 * tokens, com a lista de artefatos vinda da [tabela ambiente × artefato] do `04`.
 */

/*
|--------------------------------------------------------------------------
| Helpers deste arquivo (só ele usa — `.ai/rules/testes.md`)
|--------------------------------------------------------------------------
*/

/** O roteiro de release, cru. Ninguém aqui chama `file_get_contents` direto: só por aqui. */
function roteiroDeRelease(): string
{
    return (string) file_get_contents(base_path('wikis/checklist-de-release.md'));
}

/** O caminho relativo à raiz do kit de um "ponto de entrada" nomeado no `04`. */
function caminhoDoPontoDeEntrada(string $entrada): string
{
    return match ($entrada) {
        'CONTRIBUTING.md' => '.github/CONTRIBUTING.md',
        default           => $entrada,
    };
}

/** As linhas de DADO da tabela dos quatro cenários (entre `## A regra` e `## O roteiro`). */
function linhasDaTabelaDeCenarios(string $roteiro): array
{
    $inicio = mb_strpos($roteiro, '## A regra');
    $fim    = mb_strpos($roteiro, '## O roteiro');

    if ($inicio === false || $fim === false) {
        return [];
    }

    $bloco = mb_substr($roteiro, $inicio, $fim - $inicio);

    $linhas = [];

    foreach (explode("\n", $bloco) as $linha) {
        // `| 1 | **Instalação limpa, sem tenancy** | ... |` — descarta cabeçalho e separador.
        if (preg_match('/^\|\s*\d+\s*\|/', trim($linha)) === 1) {
            $linhas[] = $linha;
        }
    }

    return $linhas;
}

/** O bloco de "### Atualizar (cenários 3 e 4)" manda `kit:update` sobre o projeto RECÉM-CRIADO? */
function atualizaProjetoRecemCriado(string $roteiro): bool
{
    $inicio = mb_strpos($roteiro, '### Atualizar');
    $fim    = mb_strpos($roteiro, '### Conferir');

    if ($inicio === false || $fim === false) {
        return false;
    }

    $bloco = mb_substr($roteiro, $inicio, $fim - $inicio);

    return str_contains($bloco, 'novo-sem-tenant') || str_contains($bloco, 'novo-com-tenant');
}

/** O período tem alguma atenuação do tipo "quando possível"? (M7) */
function temAtenuacao(string $texto): bool
{
    return preg_match('/quando poss[íi]vel|idealmente|se poss[íi]vel|recomendad[oa]|sugerid[oa]/iu', $texto) === 1;
}

/** Quantas cláusulas de exceção/condição o texto carrega? Oráculo estrutural de CT-14 (achado A2). */
function contarClausulasDeExcecao(string $texto): int
{
    return preg_match_all(
        '/\bexceto\b|\bsalvo\b|\bdispensad?[ao]\b|n[ãa]o se aplica a|n[ãa]o vale para|com exce[çc][ãa]o de/iu',
        $texto,
    );
}

/** Um documento markdown, em memória, sem as linhas de citação (`>`) — para asserção de AUSÊNCIA. */
function semCitacao(string $texto): string
{
    return implode("\n", array_filter(
        explode("\n", $texto),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '>'),
    ));
}

/** Colapsa quebra de linha (inclusive continuação de blockquote `\n> `) num espaço só. */
function normalizarEspacos(string $texto): string
{
    $semQuebraDeCitacao = str_replace("\n>", ' ', $texto);

    return trim((string) preg_replace('/\s+/u', ' ', $semQuebraDeCitacao));
}

/** `[texto](alvo)` de todo link markdown do arquivo indicado (path relativo à raiz do kit). */
function linksMarkdown(string $caminhoRelativo): array
{
    $conteudo = (string) file_get_contents(base_path($caminhoRelativo));

    preg_match_all('/\[([^\]]+)\]\(([^)\s]+)(?:\s+"[^"]*")?\)/', $conteudo, $bruto, PREG_SET_ORDER);

    return array_map(static fn (array $m): array => ['texto' => $m[1], 'alvo' => $m[2]], $bruto);
}

/**
 * O alvo de um link resolvido em disco, a partir do DIRETÓRIO DO ARQUIVO QUE O DECLARA — é assim
 * que o GitHub resolve, e é a partição que a rodada 2 (achado A3) acrescentou a CT-16: resolver a
 * partir de `base_path()` dá 404 para um link escrito dentro de `wikis/README.md`.
 */
function alvoResolvidoEmDisco(string $arquivoDeOrigem, string $alvo): ?string
{
    if (preg_match('~^https?://~', $alvo) === 1 || str_starts_with($alvo, '#')) {
        return null;
    }

    $semAncora = explode('#', $alvo)[0];

    if ($semAncora === '') {
        return null;
    }

    $relativo = str_starts_with($semAncora, '/')
        ? ltrim($semAncora, '/')
        : trim(dirname($arquivoDeOrigem).'/'.$semAncora, '/');

    $partes = [];

    foreach (explode('/', $relativo) as $parte) {
        if ($parte === '.' || $parte === '') {
            continue;
        }

        if ($parte === '..') {
            array_pop($partes);

            continue;
        }

        $partes[] = $parte;
    }

    return implode('/', $partes);
}

/** O código PHP de um arquivo, SEM comentário nem docblock — só o que executa. */
function codigoPhpSemComentario(string $codigo): string
{
    $saida = '';

    foreach (token_get_all($codigo) as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $saida .= $token[1];
        } else {
            $saida .= $token;
        }
    }

    return $saida;
}

/**
 * Os valores de toda string literal AUTÔNOMA (não substring de uma string maior) num trecho PHP.
 *
 * Distingue "este caso LÊ o artefato X" (X é o próprio token de string, argumento de uma chamada)
 * de "este caso MENCIONA X" (X vive dentro de uma string MAIOR, como o texto que CT-20 compara) —
 * é o achado D1 da rodada 2: menção não é leitura.
 */
function literaisDeStringNoTrecho(string $trechoPhp): array
{
    $literais = [];

    foreach (token_get_all('<?php '.$trechoPhp) as $token) {
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $literais[] = trim($token[1], "'\"");
        }
    }

    return $literais;
}

/**
 * Os artefatos que a [tabela ambiente × artefato] do `04` marca AUSENTE ou NÃO RESOLVIDA — linhas
 * 3 (`CONTRIBUTING.md`), 6 (`docs/`, representado pelo arquivo que `HostLocalTest` lê), 7
 * (`CHANGELOG.md`) e 12 (`.github/workflows/release.yml`). Achado D2 da rodada 2: a lista vem da
 * tabela, não de uma enumeração ad hoc dentro do cenário — a versão anterior omitia `docs/`.
 */
const ARTEFATOS_AUSENTES_OU_NAO_RESOLVIDOS = [
    'CONTRIBUTING.md',
    'docs/pt/comecar/dominio-local.md',
    'CHANGELOG.md',
    '.github/workflows/release.yml',
];

/*
|--------------------------------------------------------------------------
| R1 — quatro cenários, cruzando origem com tenancy, versão-alvo por marcador
|--------------------------------------------------------------------------
| Toda asserção de REDAÇÃO do roteiro leva naArvoreDoKit() — regra do canário único.
*/

/**
 * O oráculo é ESTRUTURAL (linha da tabela), não busca de token. Controle positivo embutido: o
 * contador reprova diante de uma quinta linha.
 */
it('[CT-01] cada combinação tem seção própria e distinta', function (string $origem, string $tenancy): void {
    $roteiro = roteiroDeRelease();
    $linhas  = linhasDaTabelaDeCenarios($roteiro);

    $comLinhaExtra = str_replace(
        '## O roteiro',
        "| 5 | **Cenário fantasma** | x | y |\n\n## O roteiro",
        $roteiro,
    );
    expect(linhasDaTabelaDeCenarios($comLinhaExtra))->toHaveCount(5);

    $chave = match ($origem) {
        'composer create-project' => 'Instalação limpa',
        'php artisan kit:update'  => 'kit:update',
    };

    $casadas = array_values(array_filter(
        $linhas,
        static fn (string $linha): bool => str_contains($linha, $chave) && str_contains($linha, $tenancy),
    ));

    expect($casadas)->toHaveCount(1)
        ->and($linhas)->toHaveCount(4);
})->with([
    'composer create-project, sem tenancy' => ['composer create-project', 'sem tenancy'],
    'composer create-project, com tenancy' => ['composer create-project', 'com tenancy'],
    'php artisan kit:update, sem tenancy'  => ['php artisan kit:update', 'sem tenancy'],
    'php artisan kit:update, com tenancy'  => ['php artisan kit:update', 'com tenancy'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/** Controle positivo: o detector de "atualiza o projeto novo" acende contra um texto corrompido. */
it('[CT-02] cada cenário de atualização parte, ele próprio, de uma versão anterior publicada', function (): void {
    $roteiro = roteiroDeRelease();

    $this->assertStringContainsString('velho-sem-tenant "vX.Y.(Z-1)"', $roteiro);
    $this->assertStringContainsString('velho-com-tenant "vX.Y.(Z-1)"', $roteiro);

    expect(atualizaProjetoRecemCriado($roteiro))->toBeFalse();

    $corrompido = str_replace('cd velho-sem-tenant', 'cd novo-sem-tenant', $roteiro);
    expect(atualizaProjetoRecemCriado($corrompido))->toBeTrue();
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/**
 * Achado A1 (rodada 2): a versão anterior aceitava número literal, que envelhece — a release
 * seguinte validaria a anterior. O oráculo agora exige o MARCADOR, nunca um literal.
 */
it('[CT-12] os comandos fixam a versão-alvo por marcador, e não por número literal', function (): void {
    $roteiro = roteiroDeRelease();

    preg_match_all('/composer create-project gsferro\/starter-kit-easy \S+ "([^"]+)"/', $roteiro, $versoes);

    expect($versoes[1])->toHaveCount(4);

    foreach ($versoes[1] as $versao) {
        expect($versao)->not->toBe('latest')
            ->not->toContain('main')
            ->not->toContain('dev-')
            ->not->toMatch('/^v?\d+\.\d+\.\d+$/'); // não é número de release LITERAL

        expect($versao)->toMatch('/^vX\.Y\.(?:Z|\(Z-1\))$/'); // é o MARCADOR
    }

    // o marcador vX.Y.Z é definido, no próprio roteiro, como a tag NOVA sendo validada.
    $this->assertStringContainsString('na tag nova', normalizarEspacos($roteiro));
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/*
|--------------------------------------------------------------------------
| R2a — obrigação incondicional, sem cláusula de exceção
|--------------------------------------------------------------------------
*/

it('[CT-03] a obrigação é incondicional e nomeia o evento que a dispara', function (): void {
    $roteiro = roteiroDeRelease();

    $inicio    = (int) mb_strpos($roteiro, '## A regra');
    $fim       = (int) mb_strpos($roteiro, '| # | Cenário');
    $paragrafo = trim(mb_substr($roteiro, $inicio, $fim - $inicio));

    $this->assertStringContainsString('A cada nova tag, rodar os quatro cenários.', $paragrafo);

    expect(temAtenuacao($paragrafo))->toBeFalse();

    // Controle positivo: o detector de atenuação acende quando ela está de fato presente.
    expect(temAtenuacao($paragrafo.' Quando possível, rode os quatro cenários.'))->toBeTrue();
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/**
 * Achado A2 (rodada 2): a versão anterior enumerava duas dispensas nomeadas, e "tag de
 * republicação" ou "tag que só mexe na wiki" passariam intactas. O conjunto de dispensas é
 * aberto; a CONTAGEM de cláusulas de exceção é fechada — e é isso que o oráculo mede.
 */
it('[CT-14] a obrigação não abre exceção para nenhuma classe de tag', function (): void {
    $roteiro = roteiroDeRelease();

    $inicio = (int) mb_strpos($roteiro, '## A regra');
    $fim    = (int) mb_strpos($roteiro, '| # | Cenário');
    $secao  = semCitacao(mb_substr($roteiro, $inicio, $fim - $inicio));

    expect(contarClausulasDeExcecao($secao))->toBe(0);

    // Controle positivo: o contador sobe para 1 diante de uma cláusula de exceção real.
    expect(contarClausulasDeExcecao($secao.' Exceto tag que só mexe na wiki.'))->toBe(1);
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/*
|--------------------------------------------------------------------------
| R2b — critério de aprovação fechado
|--------------------------------------------------------------------------
*/

it('[CT-04] o critério é zero erro e zero falha, e pulado declarado não conta contra', function (): void {
    $roteiro = roteiroDeRelease();

    $normalizado = normalizarEspacos($roteiro);

    $this->assertStringContainsString('Zero erro e zero falha', $normalizado);
    $this->assertStringContainsString('pulado declarado não conta contra', $normalizado);
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/**
 * `@premissa` — pergunta 5 do `04`: é acréscimo de processo, precisa de aprovação explícita do
 * usuário. Escrito fielmente ao Gherkin revisado (teto por cenário, justificativa nomeando o
 * caso, execução na árvore do kit não fecha) — se o roteiro publicado não satisfizer, o vermelho
 * é o resultado certo.
 */
it('[CT-13] o critério registra um número esperado de pulados por cenário de validação', function (): void {
    // Normalizado pelo mesmo motivo do CT-15: estas agulhas passaram por sorte de quebra de linha.
    $roteiro = normalizarEspacos(roteiroDeRelease());

    $this->assertMatchesRegularExpression('/\bteto\b/iu', $roteiro);
    $this->assertMatchesRegularExpression('/justificad[ao]? por escrito|justificar por escrito/iu', $roteiro);
    $this->assertMatchesRegularExpression('/nomeando o caso/iu', $roteiro);
    $this->assertStringContainsString('não fecha o caso', $roteiro);
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

it('[CT-15] o roteiro exige o registro da evidência antes da tag de correção', function (): void {
    // Normalizado: o markdown quebra linha onde couber, e agulha de varias palavras cai na
    // emenda. Foi o que aconteceu aqui na primeira execucao: a frase exigida estava escrita, mas
    // o par "tag de" e "correcao" ficou separado por uma quebra de linha e o regex nao casou.
    // Oraculo que reprova por quebra de linha mede a largura da coluna, nao o conteudo.
    $roteiro = normalizarEspacos(roteiroDeRelease());

    $this->assertMatchesRegularExpression('/vers[ãa]o validada/iu', $roteiro);
    $this->assertMatchesRegularExpression('/quatro diret[óo]rios/iu', $roteiro);
    $this->assertMatchesRegularExpression('/sa[íi]da colada/iu', $roteiro);
    $this->assertMatchesRegularExpression('/antes d[ae] (?:a )?tag de corre[çc][ãa]o/iu', $roteiro);
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/*
|--------------------------------------------------------------------------
| R3 — dois pontos de entrada, alvo do link resolve a partir de quem declara
|--------------------------------------------------------------------------
| `.github/CONTRIBUTING.md` tem destino NÃO RESOLVIDO no projeto instalado (pergunta 1) — os dois
| Esquema levam guarda, e ela vale para as DUAS linhas (`wikis/README.md` incluído): é o mesmo
| caso Pest, e a tabela do 04 marca as duas "com guarda" por isso — perda declarada no R3.
*/

it('[CT-05] o ponto de entrada tem seção que remete ao roteiro', function (string $entrada): void {
    $caminho = caminhoDoPontoDeEntrada($entrada);

    $secaoComRoteiro = array_filter(
        secoesDoMarkdown($caminho),
        static fn (string $secao): bool => str_contains($secao, 'checklist-de-release.md'),
    );

    expect($secaoComRoteiro)->not->toBeEmpty();
})->with(['CONTRIBUTING.md', 'wikis/README.md'])
    ->skip(fn (): bool => ! naArvoreDoKit(), 'CONTRIBUTING.md tem destino não resolvido no projeto instalado (pergunta 1 do 04) — guarda por falha fechado; vale para as duas linhas do Esquema.');

it('[CT-16] o alvo do link resolve a partir do arquivo que o declara', function (string $entrada): void {
    $caminho = caminhoDoPontoDeEntrada($entrada);
    $links   = linksMarkdown($caminho);

    $alvosResolvidos = array_values(array_filter(array_map(
        static fn (array $link): ?string => alvoResolvidoEmDisco($caminho, $link['alvo']),
        $links,
    )));

    expect($alvosResolvidos)->toContain('wikis/checklist-de-release.md');

    $declaradosParaORoteiro = array_values(array_filter(
        $links,
        static fn (array $link): bool => str_contains($link['alvo'], 'checklist-de-release'),
    ));

    $quebrados = [];

    foreach ($declaradosParaORoteiro as $link) {
        $alvo = alvoResolvidoEmDisco($caminho, $link['alvo']);

        if ($alvo === null || ! file_exists(base_path($alvo))) {
            $quebrados[] = "{$caminho} → [{$link['texto']}]({$link['alvo']})";
        }
    }

    expect($quebrados)->toBe([]);
})->with(['CONTRIBUTING.md', 'wikis/README.md'])
    ->skip(fn (): bool => ! naArvoreDoKit(), 'CONTRIBUTING.md tem destino não resolvido no projeto instalado (pergunta 1 do 04) — guarda por falha fechado; vale para as duas linhas do Esquema.');

/*
|--------------------------------------------------------------------------
| R4 — medição empírica delimitada (CT-17 INVERTIDO pela RD-02)
|--------------------------------------------------------------------------
*/

it('[CT-06] a justificativa empírica está escrita, delimitada ao que lhe cabe', function (): void {
    $roteiro = roteiroDeRelease();

    $semQuebras = normalizarEspacos($roteiro);

    $this->assertStringContainsString('instalar e rodar é a única medição confiável', $semQuebras);
    $this->assertStringContainsString('nenhuma varredura estática a substitui', $semQuebras);
    $this->assertStringContainsString('fora da árvore do kit', $semQuebras);
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/**
 * Invertido pela RD-02 (achado A4/E6): a versão anterior exigia que o roteiro dissesse que as
 * varreduras erraram por PROPRIEDADE do problema. O `00` retratou isso — elas erraram por
 * limitação delas, e existe hoje guarda estática (`[CT-11]`) para a fatia decidível. Manter o
 * texto anterior levaria a próxima pessoa a remover essa guarda.
 */
it('[CT-17] o roteiro não apresenta a falha das varreduras como propriedade do problema', function (): void {
    $roteiro    = roteiroDeRelease();
    $semQuebras = normalizarEspacos($roteiro);

    $this->assertStringContainsString('teste que viaja lendo arquivo que não viaja', $roteiro);
    $this->assertStringContainsString('[CT-11]', $roteiro);
    $this->assertStringContainsString('caminho', $semQuebras);
    $this->assertStringContainsString('decidível', $semQuebras);

    // Não afirma que uma varredura não PODE acertar os três mecanismos (a cláusula retratada).
    $this->assertDoesNotMatchRegularExpression(
        '/varredura[^.]{0,80}precisa acertar os tr[êe]s mecanismos|n[ãa]o (?:pode|consegue) acertar os tr[êe]s mecanismos/iu',
        $semQuebras,
    );
})->skip(fn (): bool => ! naArvoreDoKit(), 'Asserção sobre a REDAÇÃO do roteiro — regra do canário único (04).');

/*
|--------------------------------------------------------------------------
| R5 — dois canais de entrega, até o disco
|--------------------------------------------------------------------------
*/

/**
 * `git check-attr` numa análise ISOLADA de um caminho ANINHADO sob um padrão de DIRETÓRIO do
 * `.gitattributes` (`/wikis/specs`, sem `/**`) devolve "unspecified" — é assim que o próprio
 * `gitattributes(5)` documenta a diferença para `.gitignore`: padrão de diretório não recorre para
 * ATRIBUTO sem o `/**`. Medido neste repositório: só o caminho EXATO do padrão declarado
 * (`wikis/specs`) devolve "set", e é esse o único caminho que prova que a consulta discrimina.
 */
it('[CT-07] nenhuma regra de export-ignore alcança o roteiro', function (): void {
    $doRoteiro = trim(Process::path(base_path())->run('git check-attr export-ignore -- wikis/checklist-de-release.md')->output());
    $controle  = trim(Process::path(base_path())->run('git check-attr export-ignore -- wikis/specs')->output());

    $this->assertStringContainsString('wikis/checklist-de-release.md: export-ignore: unspecified', $doRoteiro);
    $this->assertStringContainsString('export-ignore: set', $controle);
})->skip(fn (): bool => ! naArvoreDoKit(), 'git check-attr não responde fora de um repositório git — o projeto instalado não tem .git.');

/**
 * Canário único (regra do 04): o ÚNICO caso desta suíte que lê o roteiro SEM guarda. Afirma só
 * existência e destinatário — nada sobre a redação do processo, que é do kit e não do instalador.
 */
it('[CT-08] o roteiro está presente onde quem instalou o lê, e diz de quem é o processo', function (): void {
    expect(file_exists(base_path('wikis/checklist-de-release.md')))->toBeTrue();

    $roteiro = roteiroDeRelease();

    $this->assertStringContainsString(
        'Este documento é do processo de quem MANTÉM o kit, não de quem o instala.',
        $roteiro,
    );
});

/**
 * `@premissa`. `KitUpdate::handle()` opera sobre `base_path()` fixo, exige repositório git real
 * (`is_dir(base_path('.git'))`, `app/Console/Commands/KitUpdate.php:414`) e cria remote/tag contra
 * o repositório publicado (`:1089`, `new Process([$this->git, ...], base_path(), ...)`). Rodar
 * isto de verdade destruiria/poluiria o checkout onde a suíte roda, e exige rede e uma tag
 * publicada — o mesmo ambiente externo que L1 do `04` já declara fora do arnês. NÃO EXECUTADO.
 * Ver o retorno desta execução, seção "O que você não conseguiu testar e por quê".
 */
it('[CT-18] o kit:update deixa o roteiro em disco, qualquer que seja o destino', function (string $estado): void {
    // Intencionalmente vazio — ver o docblock acima e o retorno da execução.
})->with([
    'ausente'               => ['ausente'],
    'presente e idêntico'   => ['presente e idêntico'],
    'presente e modificado' => ['presente e modificado'],
])->skip(
    'Fora do arnês: `KitUpdate::handle()` exige git real e opera sobre base_path() fixo — '
    .'executar contra este checkout seria destrutivo e exige rede + tag publicada (mesma classe '
    .'de L1 do 04). Ver app/Console/Commands/KitUpdate.php:414,1089.',
);

/*
|--------------------------------------------------------------------------
| R6 — a guarda é do caso, a sentinela discrimina nos dois sentidos, a leitura não sumiu
|--------------------------------------------------------------------------
| CT-10, CT-19 e CT-20 são desta wiki e deste arquivo. CT-22 e CT-23 (endurecer a guarda de
| RedeDeDocumentacaoTest.php) NÃO são deste arquivo — outro lote os escreve lá.
*/

it('[CT-10] a guarda é do caso, e não do arquivo inteiro', function (): void {
    $fonte = (string) file_get_contents(base_path('tests/Kit/HostLocalTest.php'));

    preg_match(
        "~it\('\[CT-12\] emite o mesmo comando de elevacao.*?\}\)(?:->skip\([^;]*?naArvoreDoKit[^;]*?\))~s",
        $fonte,
        $caso,
    );

    expect($caso[0] ?? '')->not->toBe('');

    $preambulo = mb_substr($fonte, 0, (int) mb_strpos($fonte, "\nit("));

    $this->assertStringNotContainsString('markTestSkipped', $preambulo);
});

/**
 * Achado C1 (rodada 2): a versão anterior só afirmava o ramo verdadeiro, na árvore do kit — onde
 * a sentinela correta e `fn () => true` respondem igual. Bilateral de verdade exige o ramo FALSO,
 * e sem tocar na árvore real do kit (perigoso e desnecessário): simula-se a âncora ausente/
 * presente num diretório descartável, e confirma-se separadamente que a chamada AO VIVO (nesta
 * árvore) responde verdadeiro.
 */
it('[CT-19] a sentinela discrimina nos dois sentidos', function (): void {
    // Ramo verdadeiro, ao vivo: estamos de fato na árvore do kit.
    expect(naArvoreDoKit())->toBeTrue();

    $fontePest = (string) file_get_contents(base_path('tests/Pest.php'));
    $inicio    = (int) mb_strpos($fontePest, 'function naArvoreDoKit');
    $fimChave  = (int) mb_strpos($fontePest, "\n}", $inicio);
    $corpo     = mb_substr($fontePest, $inicio, $fimChave - $inicio);

    preg_match("~is_dir\\(base_path\\('([^']+)'\\)\\)~", $corpo, $ancoraExtraida);
    $ancora = $ancoraExtraida[1] ?? null;

    expect($ancora)->not->toBeNull('não consegui extrair a âncora de naArvoreDoKit() — a implementação mudou de forma');
    $this->assertStringNotContainsString('docs', $corpo);

    // Bilateral, num diretório de mentira — NUNCA na árvore real do kit.
    $tmp = sys_get_temp_dir().'/naarvoredokit-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($tmp);

    expect(is_dir($tmp.'/'.$ancora))->toBeFalse(); // ramo FALSO: âncora inacessível
    File::ensureDirectoryExists($tmp.'/'.$ancora);
    expect(is_dir($tmp.'/'.$ancora))->toBeTrue();  // ramo VERDADEIRO: âncora acessível

    File::deleteDirectory($tmp);

    // A âncora é um artefato marcado AUSENTE na tabela — mesmo oráculo (git check-attr) de CT-07.
    $controle = trim(Process::path(base_path())->run("git check-attr export-ignore -- {$ancora}")->output());
    $this->assertStringContainsString('export-ignore: set', $controle);

    // E não é o diretório que os casos guardados leem (docs/).
    expect($ancora)->not->toBe('docs');
});

it('[CT-20] a correção guardou a leitura, não a removeu', function (): void {
    $fonte = (string) file_get_contents(base_path('tests/Kit/HostLocalTest.php'));

    preg_match("~it\('\[CT-12\] emite o mesmo comando de elevacao.*?\}\)->skip~s", $fonte, $caso);

    expect($caso[0] ?? '')->not->toBe('');

    $corpo = $caso[0];

    $this->assertStringContainsString("File::get(base_path('docs/pt/comecar/dominio-local.md'))", $corpo);
    $this->assertStringContainsString('$achado[1]', $corpo);
});

/*
|--------------------------------------------------------------------------
| R8 — este arquivo não repete o defeito que ele mesmo audita
|--------------------------------------------------------------------------
*/

/**
 * Inspeção fechada de UM arquivo: este mesmo — por TOKENS, não por menção em texto (achado D1 da
 * rodada 2): este próprio caso NOMEIA os quatro artefatos da tabela para procurá-los nos OUTROS
 * casos, e essa menção seria uma falsa leitura de si mesmo se o oráculo fosse textual. Por isso
 * ele se exclui da própria varredura — é "membro declarado do conjunto que inspeciona", como o
 * `04` argumenta.
 *
 * A lista de agulhas vem de `ARTEFATOS_AUSENTES_OU_NAO_RESOLVIDOS`, que cita a tabela do `04`
 * (achado D2): a versão anterior omitia `docs/`, o próprio caminho que originou esta wiki.
 */
it('[CT-21] os casos novos respeitam a fronteira entre o que viaja e o que não viaja', function (): void {
    $fonte = codigoPhpSemComentario((string) file_get_contents(base_path('tests/Kit/ChecklistDeReleaseTest.php')));

    $blocos = preg_split('~\nit\(~', $fonte) ?: [];

    $semGuardaParaArtefato = [];
    $leRoteiroSemGuarda    = [];

    foreach (array_slice($blocos, 1) as $corpo) {
        preg_match("~^'([^']+)'~", $corpo, $nome);
        $idDoCaso = $nome[1] ?? '?';

        // Este PRÓPRIO caso é membro declarado do conjunto — ele nomeia os artefatos para
        // procurá-los alhures, e essa menção não é uma leitura sua (achado D1).
        if (str_starts_with($idDoCaso, '[CT-21]')) {
            continue;
        }

        $temGuarda = str_contains($corpo, 'naArvoreDoKit()');
        $literais  = literaisDeStringNoTrecho($corpo);

        foreach (ARTEFATOS_AUSENTES_OU_NAO_RESOLVIDOS as $artefato) {
            if (in_array($artefato, $literais, true) && ! $temGuarda) {
                $semGuardaParaArtefato[] = "{$idDoCaso} → {$artefato}";
            }
        }

        // "Afirma sobre a redação do roteiro" = chama o helper que LÊ o conteúdo do roteiro.
        if (str_contains($corpo, 'roteiroDeRelease(') && ! $temGuarda) {
            $leRoteiroSemGuarda[] = $idDoCaso;
        }
    }

    expect($semGuardaParaArtefato)->toBe([]);

    // Exatamente um caso lê o roteiro sem guarda, e é o canário de existência/destinatário.
    expect($leRoteiroSemGuarda)->toBe([
        '[CT-08] o roteiro está presente onde quem instalou o lê, e diz de quem é o processo',
    ]);
});
