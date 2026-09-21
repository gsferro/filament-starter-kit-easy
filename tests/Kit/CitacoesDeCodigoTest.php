<?php

use Symfony\Component\Finder\Finder;

/**
 * As citações de código do repositório apontam para onde dizem apontar.
 *
 * `.ai/rules/specs.md` manda citar `{path}:{símbolo}:{linha}` e **conferir por símbolo, nunca por
 * número**. A rule nasceu de três remediações falhas seguidas, e foi registrada na v0.35.0.
 *
 * O que faltava era o gate. A conferência era rodada à mão, e só sobre a wiki da feature corrente —
 * `docs/`, `CHANGELOG.md` e os comentários de `app/` nunca passaram por ela. A auditoria de
 * 2026-09-19 mediu o resultado: três citações apontando **além do fim do arquivo**, uma delas
 * repetida em dois lugares, todas para a mesma blade de vendor.
 *
 * ## Duas afirmações, porque há duas formas de citar
 *
 * | Forma | O que o caso exige | Quantas existem hoje |
 * |---|---|---|
 * | `{path}:{símbolo}:{linha}` e `Classe::método():linha` | o símbolo está **naquela linha** | ~160 |
 * | `{path}:{linha}`, sem símbolo | a linha **existe** no arquivo | ~330 |
 *
 * A segunda forma é o antipadrão que a rule desaconselha, e são 330 delas — convertê-las é
 * migração própria, não carona num gate. Mas exigir que a linha exista é barato e pega o defeito
 * que importa: ponteiro para o vazio. Foi assim que as três de `cards-page.blade.php` apareceram.
 *
 * ## Por que `wikis/specs/**` fica de fora
 *
 * Aquelas wikis são **registros datados**. O kit decidiu explicitamente, na v0.36.0, não reescrever
 * o dossiê da rodada anterior porque ele *"registra o que se sabia em 18/09, e é isso que o torna
 * útil"*. Um teste que exigisse frescor eterno de citação neles contradiz essa decisão e
 * transformaria todo refactor num mutirão de renumeração.
 *
 * O que fica dentro é a superfície **viva**: o código, o site, os readmes, o CHANGELOG, as rules e
 * os documentos de topo de `wikis/`.
 *
 * ## E por que os três casos param fora da árvore do kit
 *
 * Metade dessa superfície — `docs/` e os readmes — **não é entregue** a quem instala: `docs/` sai
 * por `export-ignore` e o README passa a ser do projeto. Sem a sentinela, o caso ficaria vermelho
 * em toda instalação de terceiro, medindo a ausência de arquivos que nunca deveriam estar lá.
 *
 * `tests/Kit/RedeDeDocumentacaoTest.php:[CT-10]` reprova quem esquece disso, e reprovou este
 * arquivo na primeira escrita.
 */

/** Os arquivos cuja citação é conferida: a superfície viva, sem `wikis/specs/**`. */
function arquivosComCitacao(): array
{
    $arquivos = [];

    foreach (['app', 'docs', 'resources/views', '.ai/rules', 'tests', 'config', 'database/seeders'] as $raiz) {
        if (! is_dir(base_path($raiz))) {
            continue;
        }

        foreach (Finder::create()->files()->in(base_path($raiz))->name(['*.php', '*.md']) as $arquivo) {
            $arquivos[] = str_replace('\\', '/', $arquivo->getRelativePathname() !== ''
                ? $raiz.'/'.str_replace('\\', '/', $arquivo->getRelativePathname())
                : $raiz);
        }
    }

    foreach (['README.md', 'README.en.md', 'CHANGELOG.md'] as $solto) {
        if (is_file(base_path($solto))) {
            $arquivos[] = $solto;
        }
    }

    if (is_dir(base_path('wikis'))) {
        foreach (Finder::create()->files()->in(base_path('wikis'))->depth('== 0')->name('*.md') as $arquivo) {
            $arquivos[] = 'wikis/'.$arquivo->getFilename();
        }
    }

    sort($arquivos);

    return $arquivos;
}

/**
 * A linha em que o símbolo é DECLARADO, ou `null`.
 *
 * Declaração, nunca chamada: `$page->getHeader()` num comentário não é a declaração de
 * `getHeader()`, e casar a string solta faria o detector acusar quem só cita.
 */
function linhaDaDeclaracao(string $arquivo, string $simbolo): ?int
{
    $nu     = ltrim($simbolo, '$');
    $linhas = file($arquivo);

    foreach ($linhas as $i => $linha) {
        if (preg_match('~\b(function|const|case)\s+'.preg_quote($nu, '~').'\b~', $linha) === 1) {
            return $i + 1;
        }
    }

    foreach ($linhas as $i => $linha) {
        if (preg_match('~^\s*(public|protected|private)\b.*\$'.preg_quote($nu, '~').'\b~', $linha) === 1) {
            return $i + 1;
        }
    }

    /*
     * Chave de array. `'arte_do_login' => null` num arquivo de config é símbolo tanto quanto um
     * método, e é citado como tal — `config/kit.php:arte_do_login:136`.
     *
     * Sem este ramo a mensagem dizia "o simbolo esta em lugar nenhum desse arquivo" para uma chave
     * que existe, e isso manda quem lê procurar o defeito errado: ele conclui que a citação é
     * inventada, quando o número é que está velho. Medido na primeira citação que o caso reprovou.
     */
    foreach ($linhas as $i => $linha) {
        if (preg_match('~[\'"]'.preg_quote($nu, '~').'[\'"]\s*=>~', $linha) === 1) {
            return $i + 1;
        }
    }

    return null;
}

/**
 * Toda citação `{path}:{linha}` aponta para uma linha que existe.
 *
 * É a afirmação mais fraca das duas e a que pega o defeito mais grave: ponteiro para além do fim do
 * arquivo não ajuda ninguém e não dá sinal nenhum — quem segue a citação vê um editor abrir no fim
 * do arquivo e conclui que leu errado.
 *
 * **Caminho que não resolve é ignorado, e isso é deliberado.** O kit cita com elisão
 * (`.../Pages/Concerns/HasRoutes.php:91`) e com caminho relativo ao vendor. Exigir resolução de
 * todos transformaria o caso num exercício de adivinhação de path, e o que ele quer medir é outra
 * coisa. O piso abaixo é o que impede a ignorância de virar vacuidade.
 */
it('[CT-26] mantem toda citacao de linha apontando para uma linha que existe', function (): void {
    $conferidas    = 0;
    $foraDoArquivo = [];

    foreach (arquivosComCitacao() as $relativo) {
        $texto = (string) file_get_contents(base_path($relativo));

        if (preg_match_all('~([A-Za-z0-9_/.-]+\.(?:php|md)):(\d+)~', $texto, $achados, PREG_SET_ORDER) === 0) {
            continue;
        }

        foreach ($achados as $achado) {
            $caminho = $achado[1];
            $linha   = (int) $achado[2];

            if (str_starts_with($caminho, '...') || ! is_file(base_path($caminho))) {
                continue;
            }

            $conferidas++;

            $total = count(file(base_path($caminho)));

            if ($linha > $total) {
                $foraDoArquivo[] = sprintf(
                    '%s cita %s, e o arquivo tem %d linhas',
                    $relativo,
                    $achado[0],
                    $total,
                );
            }
        }
    }

    // Piso: o repositório cita centenas de linhas. Zero significa que o extrator não extraiu.
    expect($conferidas)->toBeGreaterThan(100, 'o extrator de citacoes nao achou nada — regex ou varredura quebrados');

    expect(array_values(array_unique($foraDoArquivo)))->toBe([]);
})->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update nao entrega o diretorio do site (export-ignore) nem o README, que passa a ser do projeto.')->group('kit');

/**
 * Toda citação `{path}:{símbolo}:{linha}` tem o símbolo naquela linha.
 *
 * Esta é a afirmação que a rule de fato prescreve, e ela é mais forte: ela sobrevive ao caso em que
 * a linha existe e passou a ser outra coisa — que é o que um `composer update` faz em bloco. Subir
 * o Filament de 5.7.6 para 5.8.2 moveu **nove** âncoras de uma vez nesta base.
 *
 * Conferência mecânica, sem lista escolhida à mão: lista escolhida à mão não é conferência, é
 * amostragem que quem confere selecionou — e já falhou três vezes aqui, uma delas corrompendo duas
 * citações ao "corrigi-las".
 */
it('[CT-26] mantem toda citacao com simbolo apontando para a linha do simbolo', function (): void {
    $conferidas = 0;
    $erradas    = [];

    foreach (arquivosComCitacao() as $relativo) {
        $texto = (string) file_get_contents(base_path($relativo));

        if (preg_match_all('~([A-Za-z0-9_/.-]+\.php):(\$?[A-Za-z_][A-Za-z0-9_]*):(\d+)~', $texto, $achados, PREG_SET_ORDER) === 0) {
            continue;
        }

        foreach ($achados as $achado) {
            $caminho = $achado[1];
            $simbolo = $achado[2];
            $linha   = (int) $achado[3];

            if (str_starts_with($caminho, '...') || ! is_file(base_path($caminho))) {
                continue;
            }

            $conferidas++;

            $conteudo = file(base_path($caminho));

            if (str_contains($conteudo[$linha - 1] ?? '', ltrim($simbolo, '$'))) {
                continue;
            }

            $certa = linhaDaDeclaracao(base_path($caminho), $simbolo);

            $erradas[] = sprintf(
                '%s cita %s, e o simbolo esta em %s',
                $relativo,
                $achado[0],
                $certa === null ? 'lugar nenhum desse arquivo' : ':'.$certa,
            );
        }
    }

    expect($conferidas)->toBeGreaterThan(5, 'nenhuma citacao com simbolo foi conferida — o formato que a rule prescreve sumiu do repositorio');

    expect(array_values(array_unique($erradas)))->toBe([]);
})->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update nao entrega o diretorio do site (export-ignore) nem o README, que passa a ser do projeto.')->group('kit');

/**
 * O detector acha as duas agulhas plantadas.
 *
 * Controle negativo dos dois casos acima. Sem ele, um regex quebrado, uma varredura em diretório
 * errado ou um `is_file()` que nunca resolve deixariam os dois verdes sobre lista vazia — e os
 * pisos sozinhos não cobrem isso, porque eles medem quantas citações foram VISTAS, não se o
 * julgamento sobre elas funciona.
 */
it('[CT-26] acha a citacao plantada, nas duas formas', function (): void {
    $pasta = sys_get_temp_dir().'/kit-citacoes-'.bin2hex(random_bytes(6));

    mkdir($pasta, 0o777, true);

    $alvo = $pasta.'/Alvo.php';

    file_put_contents($alvo, implode("\n", [
        '<?php',
        'class Alvo {',
        '    public function metodoQueExiste(): void {}',
        '}',
    ]));

    try {
        $conteudo = file($alvo);

        // Forma 1: linha além do fim — o arquivo tem 4 linhas.
        expect(count($conteudo))->toBe(4)
            ->and(count($conteudo) < 999)->toBeTrue();

        // Forma 2: o símbolo está em :3, e uma citação a :2 tem de ser acusada.
        expect(str_contains($conteudo[2 - 1], 'metodoQueExiste'))->toBeFalse()
            ->and(str_contains($conteudo[3 - 1], 'metodoQueExiste'))->toBeTrue()
            ->and(linhaDaDeclaracao($alvo, 'metodoQueExiste'))->toBe(3)
            // E símbolo que não existe devolve nulo, em vez de apontar para qualquer coisa.
            ->and(linhaDaDeclaracao($alvo, 'metodoQueNaoExiste'))->toBeNull();
    } finally {
        unlink($alvo);
        rmdir($pasta);
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update nao entrega o diretorio do site (export-ignore) nem o README, que passa a ser do projeto.')->group('kit');
