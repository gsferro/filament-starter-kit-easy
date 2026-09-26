<?php

use Symfony\Component\Finder\Finder;

/**
 * A rede de asserções sobre a documentação continua vigiando o texto ONDE ELE PASSOU A MORAR.
 *
 * Separado de `SiteDeDocumentacaoTest` porque o alvo aqui não é o site: são as suítes que leem a
 * documentação.
 *
 * ## Os IDs deste arquivo vêm de DUAS wikis, e por isso colidem com os de outro arquivo
 *
 * | Caso | Wiki de origem |
 * |---|---|
 * | `[CT-07]` a `[CT-10]` | `wikis/specs/feat/site-de-documentacao/site-de-documentacao/04-casos-de-teste.md` (R4) |
 * | `[CT-11]`, `[CT-22]`, `[CT-23]` | `wikis/specs/fix/validacao-de-release/validacao-de-release/04-casos-de-teste.md` |
 *
 * `SiteDeDocumentacaoTest` tem um `[CT-11]`, um `[CT-22]` e um `[CT-23]` **seus**, com outro
 * significado. `grep -rn '\[CT-22\]' tests/` devolve os dois, e isso é esperado: o `CT-nn` é
 * único **dentro do arquivo**, e a wiki de origem é o que desambigua. Este bloco existe porque a
 * primeira versão do docblock declarava uma wiki só e ficou falsa quando os três casos da
 * segunda entraram (achado QA-13 do quality gate, ciclo 2).
 *
 * A assimetria que justifica o arquivo: asserção de PRESENÇA fica vermelha quando o texto
 * migra e se conserta sozinha; asserção de AUSÊNCIA fica verde e VAZIA — o README encolhido
 * não fala mais do assunto, então proibir um literal ali passa por construção, enquanto a
 * página migrada carrega a promessa proibida à vontade. É a perda silenciosa em forma pura.
 */
beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        $this->markTestSkipped('Fora da árvore do kit não há site a conferir: o diretório do site é export-ignore.');
    }
});

/**
 * As suítes que leem a documentação do kit: todo arquivo de `tests/Kit` que chama
 * `documentacaoDoKit()` ou nomeia um README ou uma página do site.
 *
 * @return array<string, string> nome do arquivo => código
 */
function suitesDeDocumentacao(): array
{
    $suites = [];

    foreach (Finder::create()->files()->in(__DIR__)->name('*Test.php') as $arquivo) {
        $codigo = codigoSemComentario($arquivo->getContents());

        if (preg_match('~documentacaoDoKit\(|README(\.en)?\.md|docs/(pt|en)/~', $codigo) === 1) {
            $suites[$arquivo->getFilename()] = $codigo;
        }
    }

    return $suites;
}

/**
 * CT-07 — o inventário do que é vigiado não encolhe nem troca de conteúdo.
 *
 * O piso é o NÚMERO LITERAL de sítios de `toContain` medidos nas suítes de documentação no
 * dia da migração (a wiki fala em 79 asserções contando as linhas de dataset; aqui contam-se
 * os sítios de chamada, que é o que `token_get_all()` enxerga). Quem apaga uma asserção para
 * destravar o commit (M15) precisa editar este número — visível em revisão de diff.
 *
 * Contar não é identidade (M41): as duas guardas de segurança — o disco de mídia não ser
 * público e o segredo do provedor viver só no `.env` — são conferidas NOMINALMENTE. E todo
 * literal de caminho de documento aponta para um arquivo que existe.
 */
it('[CT-07] o inventário do que é vigiado não encolhe nem troca de conteúdo', function (): void {
    $suites = suitesDeDocumentacao();
    $codigo = implode("\n", $suites);

    $sitios = 0;

    foreach ($suites as $conteudo) {
        foreach (token_get_all($conteudo) as $token) {
            if (is_array($token) && $token[0] === T_STRING && $token[1] === 'toContain') {
                $sitios++;
            }
        }
    }

    preg_match_all('~[\'"]((?:docs/(?:pt|en)/[\w/-]+\.md)|README(?:\.en)?\.md|\.env\.example|wikis/[\w/-]+\.md)[\'"]~', $codigo, $caminhos);
    $inexistentes = array_values(array_filter(
        array_unique($caminhos[1]),
        static fn (string $caminho): bool => ! is_file(base_path($caminho)),
    ));

    expect(count($suites))->toBeGreaterThanOrEqual(6)
        ->and($sitios)->toBeGreaterThanOrEqual(48)
        ->and($codigo)->toContain("MEDIA_DISK', 'public'")
        ->and($codigo)->toContain('CLIENT_SECRET')
        ->and($inexistentes)->toBe([]);
});

/**
 * CT-08 — nenhuma asserção de ausência fica sem assunto para vigiar: o documento proibido de
 * conter X precisa CONTER a âncora do mesmo assunto, senão a proibição é vácuo (M16). As linhas
 * `en` têm âncora em inglês onde o token difere, para pegar o dataset reapontado para o
 * arquivo do idioma errado (M17).
 */
it('[CT-08] nenhuma asserção de ausência fica sem assunto para vigiar', function (string $documento, string $proibido, string $ancora): void {
    // Presença sobre o texto CRU (a âncora pode viver numa nota `>`); ausência sobre o filtrado.
    expect((string) file_get_contents(base_path($documento)))->toContain($ancora)
        ->and(readmeSemCitacao($documento))->not->toContain($proibido);
})->with([
    'disco de mídia, pt'       => ['docs/pt/recursos/anexos-e-midia.md', '`public` por padrão', 'MEDIA_DISK'],
    'disco de mídia, en'       => ['docs/en/recursos/anexos-e-midia.md', '`public` by default', 'MEDIA_DISK'],
    'arte do login, pt'        => ['docs/pt/recursos/configuracoes-do-kit.md', 'public/images/auth/login.svg', 'arte do login'],
    'arte do login, en'        => ['docs/en/recursos/configuracoes-do-kit.md', 'public/images/auth/login.svg', 'login artwork'],
    'callback do LinkedIn, pt' => ['docs/pt/autenticacao/login-social.md', '/auth/linkedin/callback', 'socialiteproviders'],
    'callback do LinkedIn, en' => ['docs/en/autenticacao/login-social.md', '/auth/linkedin/callback', 'socialiteproviders'],
]);

/**
 * CT-09 — o nome e o motivo continuam na MESMA SEÇÃO, em toda página do site que nomeia o
 * Discord. Granularidade de seção, não de página: nome no topo e motivo trezentas linhas
 * abaixo passaria por página (M42). E o `Então` de existência impede a versão vácua em que
 * ninguém menciona Discord e a co-localização passa por vazio (M18).
 */
it('[CT-09] toda seção que nomeia o Discord traz o motivo da recusa na mesma seção', function (): void {
    $comDiscord = [];
    $semMotivo  = [];

    foreach (['pt', 'en'] as $idioma) {
        foreach (array_keys(paginasDoSite($idioma)) as $pagina) {
            foreach (secoesDoMarkdown("docs/{$idioma}/{$pagina}") as $secao) {
                if (! str_contains($secao, 'Discord')) {
                    continue;
                }

                if (str_contains($secao, 'socialiteproviders')) {
                    $comDiscord[] = "{$idioma}/{$pagina}";
                }

                // Ou o motivo está na seção, ou a seção aponta (por âncora) para a que o traz.
                if (! str_contains($secao, 'socialiteproviders') && preg_match('/\]\(#[^)]*discord[^)]*\)/i', $secao) !== 1) {
                    $semMotivo[] = "{$idioma}/{$pagina}: ".mb_substr(trim($secao), 0, 60);
                }
            }
        }
    }

    expect($comDiscord)->not->toBeEmpty()
        ->and($semMotivo)->toBe([]);
});

/**
 * CT-10 — nenhum cenário se guarda pela própria entrega, e nenhuma suíte de documentação nasce
 * sem sentinela. Inspeção ESTÁTICA de TODA suíte devolvida por `suitesDeDocumentacao()`: a
 * sentinela é `naArvoreDoKit()` (`.github`), e nenhum desvio de execução consulta `docs/` —
 * `is_dir('docs')` é auto-anulante: sem a migração, `docs/` não existe, tudo é ignorado e
 * `composer test:kit` fica verde com zero entrega (M40, M19).
 *
 * A forma anterior (um `Então` de auto-declaração dentro deste mesmo cenário) atestava a si
 * mesma; ler o código dos arquivos, como faz `HelpersDeTesteTest`, é o que funciona.
 *
 * ## Por que o universo é a varredura, e não uma lista escrita à mão
 *
 * A versão anterior cobrava a sentinela de DOIS arquivos nomeados, e foi isso que deixou dez
 * suítes de documentação nascerem sem ela — medido numa instalação real: o `kit:update` levou o
 * código de v0.30.1 a v0.32.0, não levou o README nem `docs/` (a lista
 * `KitUpdate::CAMINHOS_DO_KIT` só traz `wikis/README.md`), e a suíte que a atualização acabou de
 * instalar ficou vermelha em quatro casos, acusando o projeto por documentação que é do kit.
 *
 * ## O gatilho é grosso de propósito, e é justo
 *
 * `suitesDeDocumentacao()` casa `documentacaoDoKit(`, `README(.en).md` e `docs/(pt|en)/` no código
 * **sem comentário**, então um arquivo entra na lista mesmo que o caminho apareça só num literal
 * de dataset. Isso não é imprecisão a corrigir: nesta base a leitura é quase sempre INDIRETA — o
 * caminho vem do dataset e o `file_get_contents(base_path($arquivo))` está no corpo (CT-04 de
 * `DeployDockerLocalTest`, CT-05 de `MysqlNoDockerTest`, CT-33 de
 * `SituacaoDaContaDocumentacaoTest`) —, e nenhuma regra estática distingue esse literal de um
 * decorativo. Entre cobrar uma linha de sentinela a mais e deixar passar uma suíte que quebra em
 * toda instalação, o custo do falso alarme é uma linha; o do falso silêncio, uma suíte vermelha em
 * cada projeto que atualizar.
 *
 * A granularidade da sentinela é do AUTOR: `->skip()` por caso onde o arquivo mistura documentação
 * com código entregue, `beforeEach` onde o arquivo é todo de documentação, ou um
 * `markTestSkipped` no meio do corpo onde uma linha do dataset (o `docker-compose.yml` de CT-05)
 * ou uma das metades do caso (a `wikis/arquitetura.md` de CT-21) lê arquivo que É entregue e tem
 * de continuar conferida. Este caso só cobra que a sentinela exista.
 *
 * Uma nota de uso: a proibição de guard sobre `docs/` casa dentro da chamada inteira, então a
 * MENSAGEM do `skip()` não pode soletrar o nome do diretório — diga "o diretório do site". A
 * alternativa (regex que distingue expressão de literal de string) custa mais do que a convenção.
 */
it('[CT-10] nenhum cenário se guarda pela própria entrega', function (): void {
    $suites = suitesDeDocumentacao();

    // Os dois arquivos da feature, nomeados: a varredura não pode encolher até deixá-los de fora.
    expect(array_keys($suites))->toContain('SiteDeDocumentacaoTest.php')
        ->toContain('RedeDeDocumentacaoTest.php');

    foreach ($suites as $arquivo => $codigo) {
        preg_match_all('/\b(is_dir|file_exists|is_file|markTestSkipped|skip)\s*\([^;]*?docs/', $codigo, $guardasSobreDocs);

        // `toContain()` recebe VÁRIOS needles — uma mensagem como 2º argumento viraria needle.
        expect(arquivoTemSentinela($codigo))
            ->toBeTrue("{$arquivo} lê a documentação do kit e não tem a sentinela naArvoreDoKit(): fora da árvore do kit ele fica vermelho em toda instalação.");

        expect($guardasSobreDocs[0])->toBe([], "{$arquivo} condiciona execução à existência de docs/");
    }
});

/**
 * O corpo de um caso **abre** um arquivo de caminho literal que casa com `$prefixos`?
 *
 * ## Por que por TOKENS, e não por regex
 *
 * A primeira versão deste detector usava regex, e ela **acusou um caso inocente**:
 * `ChecklistDeReleaseTest [CT-20]` afirma que o `[CT-12]` corrigido continua abrindo o documento,
 * e para isso carrega a chamada **dentro de uma string de asserção**:
 *
 * ```php
 * $this->assertStringContainsString("File::get(base_path('docs/pt/…'))", $corpo);
 * ```
 *
 * O caso **menciona** a leitura; quem lê é o `HostLocalTest`, que viaja. Regex não distingue as
 * duas coisas — para o tokenizador, a menção inteira é **um** `T_CONSTANT_ENCAPSED_STRING` e não
 * se decompõe em chamada. É o risco que a ADR-02 declarou ao aceitar este caso, e ele apareceu no
 * primeiro arquivo escrito depois dele.
 *
 * @param  list<string>  $funcoes  nomes de função/método que abrem arquivo
 */
function leCaminhoNaoEntregue(string $corpoDoCaso, array $funcoes, string $prefixos): bool
{
    $tokens = array_values(array_filter(
        token_get_all('<?php '.$corpoDoCaso),
        fn ($token): bool => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
    ));

    foreach ($tokens as $i => $token) {
        if (! is_array($token) || $token[0] !== T_STRING || ! in_array($token[1], $funcoes, true)) {
            continue;
        }

        /*
         * A partir do nome da função, o caminho literal aparece em poucos tokens — `('`,
         * `(base_path('`. Uma janela curta evita colher o literal de uma chamada seguinte.
         */
        foreach (array_slice($tokens, $i + 1, 4) as $seguinte) {
            if (is_array($seguinte) && $seguinte[0] === T_CONSTANT_ENCAPSED_STRING
                && preg_match($prefixos, trim($seguinte[1], "'\"")) === 1) {
                return true;
            }
        }
    }

    return false;
}

/**
 * CT-11 — leitura DIRETA e literal de arquivo não entregue exige a sentinela no PRÓPRIO caso.
 *
 * ## O que este caso acrescenta ao CT-10, e por que ele não é redundante
 *
 * O CT-10 cobra que a sentinela **exista no arquivo**, e o docblock dele declara que a
 * granularidade fica com o autor — com argumento: nesta base a leitura é quase sempre **indireta**
 * (o caminho vem de um dataset e o `file_get_contents()` está no corpo), e nenhuma regra estática
 * distingue esse literal de um decorativo.
 *
 * O argumento está certo para o caso indireto. Ele deixa aberta a fatia em que a regra **é**
 * decidível: quando o caminho aparece **literal, dentro da chamada de leitura, no corpo do caso**.
 * Aí não há ambiguidade — aquele caso lê aquele arquivo.
 *
 * ## E foi exatamente por essa fatia que o defeito passou
 *
 * `tests/Kit/HostLocalTest.php` `[CT-12]` fazia `File::get(base_path('docs/pt/…'))` sem sentinela
 * própria. O arquivo **tinha** `naArvoreDoKit()` — no `[CT-34]`, outro caso, 750 linhas abaixo —,
 * então o CT-10 ficava **verde** e o caso quebrava em toda instalação nova. Encontrado pela
 * validação de release da `v0.38.0`, não por gate nenhum.
 *
 * ## Por que não varrer tudo
 *
 * Porque a varredura ampla erra, e isso foi medido: três tentativas devolveram 8, 20 e 17
 * desprotegidos contra **1** real. Este caso não tenta decidir o indecidível — ele cobre só o que
 * o CT-10 declarou fora do próprio alcance, e deixa o resto onde estava.
 *
 * ## O oráculo é o CASO, não o arquivo
 *
 * Um caso pode ler `docs/` e ter a sentinela num vizinho; é isso que o CT-10 aceita e que este
 * recusa, para a fatia direta. A mensagem nomeia o caso, porque "o arquivo X não tem sentinela"
 * foi precisamente a informação que não bastou.
 */
/**
 * O oraculo do `[CT-10]`: a sentinela existe **em algum lugar do ARQUIVO**.
 *
 * Extraida para que `[CT-22]` possa exercita-la sobre o mesmo arranjo que alimenta o `[CT-11]`,
 * em vez de reafirmar uma string literal escrita tres linhas acima — a segunda assercao do
 * `[CT-22]` fazia isso e nao podia falhar (achado QA-17 do quality gate, ciclo 2).
 *
 * Agora as duas guardas rodam o MESMO codigo que rodam em producao, sobre o MESMO insumo, e o
 * `[CT-22]` afirma que elas **discordam** — que e a propriedade que justifica as duas existirem.
 */
function arquivoTemSentinela(string $codigo): bool
{
    return str_contains($codigo, 'naArvoreDoKit()');
}

/**
 * O corpo de um caso depende da ARVORE DO KIT para passar?
 *
 * Tres formas, todas decidiveis:
 *
 * 1. **le** um arquivo de caminho literal que nao viaja (`leCaminhoNaoEntregue()`);
 * 2. **afirma** que a sentinela e verdadeira — `expect(naArvoreDoKit())->toBeTrue()`;
 * 3. **invoca o git** — `create-project` nao entrega `.git`, entao `git check-attr` e vizinhos
 *    nao respondem em projeto instalado.
 *
 * A forma 1 era a unica coberta ate a validacao da v0.38.1, que encontrou um caso da forma 2
 * (`ChecklistDeReleaseTest [CT-19]`) vermelho em toda instalacao nova — e ele tambem era da
 * forma 3, por uma segunda razao independente.
 *
 * @param  list<string>  $funcoes
 */
function dependeDaArvoreDoKit(string $corpoDoCaso, array $funcoes, string $prefixos): bool
{
    if (leCaminhoNaoEntregue($corpoDoCaso, $funcoes, $prefixos)) {
        return true;
    }

    // Forma 2: a sentinela como SUJEITO de uma afirmacao de verdade.
    if (preg_match('~(?:expect|assertTrue)\s*\(\s*naArvoreDoKit\s*\(\s*\)~', $corpoDoCaso) === 1) {
        return true;
    }

    /*
     * Forma 3: um comando git EXECUTADO, que exige o `.git` que o create-project nao entrega.
     *
     * A primeira versao desta linha casava qualquer literal comecando por "git " e acusou dois
     * casos inocentes — `DeployDockerLocalTest [CT-01]` e um do `KitUpdateTest`, que procuram
     * 'git pull --ff-only' DENTRO de um script como agulha de busca. Mencionar o comando nao e
     * executa-lo, e contar mencao como uso e o mesmo erro que esta guarda inteira existe para
     * nao cometer. Agora o literal so conta quando e o ARGUMENTO de uma chamada que executa.
     */
    if (preg_match('~(?:->run|::run|\brun|\bexec|shell_exec|proc_open|fromShellCommandline)\s*\(\s*[\'"]git\s~', $corpoDoCaso) === 1) {
        return true;
    }

    /*
     * A VARIANTE EM ARRAY, que a primeira redacao desta funcao nao via: `new Process(['git', ...])`
     * seguido de `$git->run()` com parenteses VAZIOS. O comando nao esta no argumento do `run`, e
     * nenhum dos alternantes acima o alcanca.
     *
     * Nao e hipotese: `tests/Kit/SiteDeDocumentacaoTest.php:265` usa exatamente essa forma. Ali
     * ela nao morde porque o arquivo tem sentinela no `beforeEach` — mas o reconhecedor ficaria
     * cego para o proximo arquivo que a usasse sem essa protecao.
     */
    return preg_match('~(?:new\s+Process|::fromShellCommandline)\s*\(\s*\[\s*[\'"]git[\'"]~', $corpoDoCaso) === 1;
}

/**
 * O caso esta guardado pela sentinela — de verdade, nao por mencao.
 *
 * ## Por que nao basta `str_contains($corpo, 'naArvoreDoKit')`
 *
 * Porque `ChecklistDeReleaseTest [CT-19]` menciona `naArvoreDoKit` TRES vezes e nao e guardado
 * por nenhuma delas: ele a usa como sujeito de assercao e como fonte de um regex. Contar a
 * mencao como protecao e o MESMO erro do `[CT-10]` contando o arquivo em vez do caso — um nivel
 * acima, e cometido por mim ao escrever a correcao daquele.
 *
 * Guardar e uma dessas duas coisas, e nenhuma outra:
 * `->skip(... naArvoreDoKit ...)` no proprio caso, ou `markTestSkipped()` sob a sentinela.
 */
function temSentinelaPropria(string $corpoDoCaso): bool
{
    /*
     * `[^;}]`... nao: `[^}]{0,400}`, pelo mesmo motivo do ramo abaixo. Um `->skip()` com closure
     * de mais de uma instrucao tem `;` antes da sentinela, e a janela antiga (`[^;]*`) cortava
     * ali — acusando um caso GUARDADO. A chave que fecha a closure e a fronteira certa; o teto
     * de 400 impede que um `->skip()` sem sentinela se ligue a uma mencao distante no bloco.
     */
    if (preg_match('~->skip\s*\([^}]{0,400}naArvoreDoKit~s', $corpoDoCaso) === 1) {
        return true;
    }

    /*
     * `[^}]` e nao `[^;]`: entre a sentinela e o `markTestSkipped` cabe mais de uma instrucao
     * (`if (! naArvoreDoKit()) { $motivo = '...'; $this->markTestSkipped($motivo); }`), e o
     * primeiro `;` cortava o casamento no meio. A fronteira certa e a chave que fecha o bloco.
     */
    return preg_match('~naArvoreDoKit[^}]{0,400}markTestSkipped~s', $corpoDoCaso) === 1;
}

/**
 * Os casos que **abrem** um arquivo de caminho literal nao entregue e nao tem a sentinela
 * `naArvoreDoKit()` **no proprio corpo**.
 *
 * Extraida de dentro do `[CT-11]` para que `[CT-22]` e `[CT-23]` possam alimenta-la com um
 * arranjo conhecido. Enquanto a varredura morava dentro do caso, a unica prova de que ela
 * funcionava era **manual** — restaurar o defeito na arvore e olhar. Prova que nao esta
 * versionada nao protege release nenhuma: foi o achado QA-03 do quality gate.
 *
 * @param  array<string, string>  $suites  arquivo => codigo PHP sem comentario
 * @return list<string>
 */
function casosSemSentinelaPropria(array $suites): array
{
    /*
     * Os prefixos que NAO viajam, do `.gitattributes`. `wikis/specs` fica de fora de proposito:
     * o `KitUpdateTest` cita caminhos de la como string, sem ler, e o CT-10 ja trata esse caso.
     */
    $naoEntregues = '~^(?:docs|site|site-vitepress|\.github)/~';

    $funcoesDeLeitura = ['file_get_contents', 'get', 'exists', 'isDirectory'];

    $desprotegidos = [];

    foreach ($suites as $arquivo => $codigo) {
        $blocos = preg_split('~\nit\(~', $codigo) ?: [];

        /*
         * O PREAMBULO — tudo antes do primeiro `it(` — e onde mora o `beforeEach` do arquivo.
         * Sentinela ali protege TODOS os casos, e e um dos tres mecanismos que o kit usa
         * (`tests/Kit/SiteDeDocumentacaoTest.php:naArvoreDoKit:59`).
         *
         * Esta verificacao nao estava na primeira versao do `[CT-11]`, e ele acusou os 15
         * cenarios daquele arquivo — repetindo exatamente o erro que a terceira varredura
         * estatica da investigacao cometeu. `[CT-23]` fixa isso num caso, para que a proxima
         * pessoa nao precise lembrar.
         */
        if (str_contains($blocos[0] ?? '', 'naArvoreDoKit')) {
            continue;
        }

        // Um bloco por `it(` — o corpo do caso, nao o arquivo.
        foreach (array_slice($blocos, 1) as $corpo) {
            if (! dependeDaArvoreDoKit($corpo, $funcoesDeLeitura, $naoEntregues)) {
                continue;
            }

            if (temSentinelaPropria($corpo)) {
                continue;
            }

            preg_match("~^'([^']+)'~", $corpo, $nome);

            $desprotegidos[] = $arquivo.' -> '.($nome[1] ?? '?');
        }
    }

    return $desprotegidos;
}

/**
 * TODA suite de `tests/Kit`, sem comentario — e nao so as de documentacao.
 *
 * ## Por que o escopo teve de crescer junto com o reconhecedor
 *
 * `suitesDeDocumentacao()` filtra por `documentacaoDoKit(`, `README.md` e `docs/{pt,en}/`. Era o
 * escopo CERTO enquanto a unica forma de depender da arvore do kit era **ler um arquivo de
 * documentacao que nao viaja**.
 *
 * `dependeDaArvoreDoKit()` passou a reconhecer mais duas formas — **afirmar a sentinela** e
 * **invocar o git** — e nenhuma delas tem relacao com documentacao. O reconhecedor cresceu, o
 * escopo nao, e a guarda ficou detectando um risco em arquivos onde ele nao estava e ignorando
 * o arquivo onde estava.
 *
 * **Medido**: `tests/Kit/BlueprintForaDoPacoteTest.php` invoca `git ls-files`, nao tem sentinela
 * nenhuma e **nunca era varrido**, porque nao cita documentacao. Com o escopo alargado, ele e o
 * unico acusado em 112 arquivos — nenhum falso positivo.
 *
 * Alargar e seguro porque o reconhecedor ja se auto-limita: a forma 1 exige caminho literal com
 * prefixo `docs|site|site-vitepress|.github`, e os prefixos nao mudam com o escopo.
 *
 * @return array<string, string>
 */
function suitesComRiscoDeArvore(): array
{
    $suites = [];

    foreach (Finder::create()->files()->in(__DIR__)->name('*Test.php') as $arquivo) {
        $suites[$arquivo->getFilename()] = codigoSemComentario($arquivo->getContents());
    }

    return $suites;
}

it('[CT-11] leitura direta de arquivo nao entregue tem sentinela no proprio caso', function (): void {
    /*
     * CONTROLE POSITIVO DA VARREDURA, e ele vem primeiro: um `Finder` que nao achasse arquivo
     * deixaria a lista de acusados vazia e este caso VERDE sobre nada. A base tem mais de cem
     * suites em `tests/Kit`.
     */
    expect(count($suites = suitesComRiscoDeArvore()))
        ->toBeGreaterThan(50, 'a varredura nao encontrou suites — a lista de acusados abaixo mediria o vazio');

    expect(casosSemSentinelaPropria($suites))->toBe(
        [],
        'estes casos leem arquivo que NAO viaja no composer create-project e nao tem a sentinela '
        .'`naArvoreDoKit()` no proprio corpo: eles ficam vermelhos em toda instalacao nova. '
        .'Sentinela num caso vizinho do mesmo arquivo satisfaz o [CT-10] e nao protege este.',
    );
})->group('kit');

/**
 * CT-22 — a guarda reprova o arranjo EXATO que a enganou na `v0.38.0`.
 *
 * ## Por que este caso existe
 *
 * A ADR-02 aceitou o `[CT-11]` dizendo que ele "foi verificado por mutacao antes de entrar". Era
 * verdade, e era **manual**: restaurei o defeito na arvore, rodei, restaurei de volta. Nada no
 * repositorio guardava essa prova. O quality gate mediu a consequencia (achado QA-03): trocar o
 * corpo de `leCaminhoNaoEntregue()` por `return false` deixa `$desprotegidos === []` e o
 * `[CT-11]` **verde**.
 *
 * Guarda sem controle positivo e a mesma forma de defeito que esta wiki inteira persegue: ela
 * afirma ausencia, e ausencia nunca falha sozinha.
 *
 * ## O arranjo reproduzido aqui
 *
 * E o da `v0.38.0`, em miniatura: um caso que abre `docs/` com caminho **literal** e sem
 * sentinela, e um caso **vizinho, no mesmo arquivo**, que tem a sentinela. No original os dois
 * estavam a 750 linhas de distancia. O `[CT-10]`, que olha o arquivo inteiro com `str_contains`,
 * fica verde nesse arranjo — e e por isso que as duas asserções abaixo andam juntas: elas
 * documentam que as duas guardas **discordam de proposito**.
 */
it('[CT-22] a guarda reprova o arranjo que a enganou, e o CT-10 nao', function (): void {
    $arranjoDoDefeito = <<<'PHP'
    <?php
    it('[FIXTURE-A] le a pagina do site como oraculo documental', function (): void {
        $pagina = File::get(base_path('docs/pt/comecar/dominio-local.md'));
        expect($pagina)->toContain('sudo');
    });

    it('[FIXTURE-B] o caso vizinho, 750 linhas abaixo, tem a sentinela', function (): void {
        expect(true)->toBeTrue();
    })->skip(fn (): bool => ! naArvoreDoKit(), 'fora da arvore do kit');
    PHP;

    $suite = ['ArranjoDaV0380Test.php' => $arranjoDoDefeito];

    // A guarda nova acusa — e acusa o caso CERTO, nao o arquivo.
    expect(casosSemSentinelaPropria($suite))
        ->toBe(['ArranjoDaV0380Test.php -> [FIXTURE-A] le a pagina do site como oraculo documental']);

    /*
     * E o oraculo do [CT-10] sobre o MESMO arranjo, INVOCADO e nao reproduzido: `[CT-10]` chama
     * esta mesma funcao. Ele passa, porque o FIXTURE-B tem a sentinela e ele olha o arquivo.
     *
     * As duas linhas juntas sao o achado da v0.38.0 transformado em teste: sobre um so insumo, a
     * guarda por arquivo aprova e a guarda por caso reprova. Se alguem "unificar" as duas, este
     * caso fica vermelho — que e exatamente o alarme que se quer.
     */
    expect(arquivoTemSentinela($arranjoDoDefeito))->toBeTrue();
})->group('kit');

/**
 * CT-26 — a guarda acusa a invocacao do git nas DUAS formas que esta base usa.
 *
 * A primeira redacao de `dependeDaArvoreDoKit()` so via o comando como **argumento literal** de
 * quem executa (`->run('git ...')`). A base tambem usa a forma em **array**
 * (`new Process(['git', 'show', ...])` + `$git->run()`, em `SiteDeDocumentacaoTest.php:265`), em
 * que `run()` tem parenteses vazios e nenhum alternante alcancava o comando.
 *
 * Ali nao mordia, porque aquele arquivo tem sentinela no `beforeEach` — e e por isso que o
 * buraco sobreviveu: ele estava coberto por acidente, e nao por construcao. Achado da revisao
 * deste PR.
 */
it('[CT-26] a guarda acusa a invocacao do git nas duas formas', function (string $rotulo, string $codigo): void {
    expect(casosSemSentinelaPropria([$rotulo => $codigo]))
        ->toHaveCount(1, "a guarda nao viu a invocacao do git em {$rotulo}");
})->with([
    'literal no argumento de quem executa' => ['GitLiteralTest.php', <<<'PHP'
    <?php
    it('[FIXTURE-G] consulta o git pelo argumento literal', function (): void {
        $saida = Process::path(base_path())->run('git check-attr export-ignore -- wikis/')->output();
        expect($saida)->toContain('export-ignore');
    });
    PHP],

    'array, com run() de parenteses vazios' => ['GitArrayTest.php', <<<'PHP'
    <?php
    it('[FIXTURE-H] consulta o git montando o comando em array', function (): void {
        $git = new Process(['git', 'show', 'HEAD:README.md']);
        $git->run();

        expect($git->isSuccessful())->toBeTrue();
    });
    PHP],
])->group('kit');

/**
 * CT-27 — controle NEGATIVO de CT-26: mencionar o git nao e invoca-lo.
 *
 * A primeira versao do reconhecedor de forma 3 casava qualquer literal comecando por `git ` e
 * acusou dois casos inocentes que procuram `git pull --ff-only` DENTRO de um script, como agulha
 * de busca. Contar mencao como uso e o mesmo erro que esta guarda inteira existe para nao
 * cometer, entao ele fica fixado num caso.
 */
/**
 * CT-28 — o ESCOPO da varredura cobre todo arquivo que o reconhecedor considera em risco.
 *
 * ## O mutante que nenhum outro caso mata
 *
 * `[CT-11]` prova que os arquivos varridos estao limpos. Ele **nao** prova que a varredura olha
 * para os arquivos certos: estreitar o escopo de volta para `suitesDeDocumentacao()` deixa
 * `[CT-11]` verde, porque o unico acusado ganhou `->skip()` junto. Reconhecedor e escopo sao
 * duas pecas, e ate aqui so uma delas tinha guarda.
 *
 * Foi exatamente assim que o buraco nasceu: `dependeDaArvoreDoKit()` cresceu para reconhecer
 * **afirmar a sentinela** e **invocar o git**, e o escopo ficou filtrando por documentacao. O
 * reconhecedor passou a enxergar um risco que o escopo nao o deixava alcancar.
 *
 * Este caso liga as duas: **todo** arquivo de `tests/Kit` em que o reconhecedor acha risco tem de
 * estar no conjunto varrido. Nao afirma qual e o escopo — afirma a RELACAO entre os dois, que e
 * a propriedade, e nao a implementacao.
 */
it('[CT-28] a varredura alcanca todo arquivo em que o reconhecedor acha risco', function (): void {
    $varridos = array_keys(suitesComRiscoDeArvore());

    $emRisco = [];

    foreach (Finder::create()->files()->in(__DIR__)->name('*Test.php') as $arquivo) {
        $codigo = codigoSemComentario($arquivo->getContents());
        $blocos = preg_split('~\nit\(~', $codigo) ?: [];

        foreach (array_slice($blocos, 1) as $corpo) {
            if (dependeDaArvoreDoKit($corpo, ['file_get_contents', 'get', 'exists', 'isDirectory'], '~^(?:docs|site|site-vitepress|\.github)/~')) {
                $emRisco[] = $arquivo->getFilename();

                break;
            }
        }
    }

    /*
     * CONTROLE POSITIVO, e ele vem primeiro: se o reconhecedor nao achasse risco em lugar nenhum,
     * a diferenca abaixo seria vazia e o caso ficaria verde sobre nada. Esta base tem varios.
     */
    expect(count($emRisco))->toBeGreaterThan(3, 'o reconhecedor nao achou risco em arquivo nenhum — a diferenca abaixo mediria o vazio');

    expect(array_values(array_diff($emRisco, $varridos)))->toBe([], implode("\n", [
        'Estes arquivos tem caso que depende da arvore do kit e a varredura NAO os alcanca:',
        ...array_diff($emRisco, $varridos),
        '',
        'O reconhecedor e o escopo tem de crescer juntos. Ver `suitesComRiscoDeArvore()`.',
    ]));
})->group('kit');

it('[CT-27] mencionar o git como agulha de busca nao conta como invocacao', function (): void {
    $mencao = <<<'PHP'
    <?php
    it('[FIXTURE-I] o script de deploy faz pull antes de subir', function (): void {
        $script = File::get(base_path('deploy_docker_local.sh'));
        expect($script)->toContain('git pull --ff-only');
    });
    PHP;

    expect(casosSemSentinelaPropria(['MencaoDeGitTest.php' => $mencao]))->toBe([]);
})->group('kit');

/**
 * CT-23 — a guarda declara a fatia que NAO decide, em vez de reprovar por suspeita.
 *
 * O docblock do `[CT-10]` argumenta, com razao, que nesta base a leitura e quase sempre
 * **indireta** — o caminho vem de um dataset e a chamada esta no corpo — e que regra estatica nao
 * distingue esse literal de um decorativo. O `[CT-11]` nao tenta decidir isso. Este caso fixa os
 * tres nao-alvos, para que ninguem "melhore" a guarda ate ela virar ruido:
 *
 * 1. **leitura indireta** — o caminho chega por variavel;
 * 2. **mencao dentro de string** — foi o falso positivo real do `[CT-20]` do
 *    `ChecklistDeReleaseTest`, que afirma sobre a chamada sem executa-la;
 * 3. **sentinela no `beforeEach` do arquivo** — o mecanismo que a terceira varredura estatica da
 *    investigacao nao enxergou, e que a primeira versao do `[CT-11]` tambem nao enxergava.
 */
it('[CT-23] a guarda nao acusa o que ela declarou nao decidir', function (string $rotulo, string $codigo): void {
    expect(casosSemSentinelaPropria([$rotulo => $codigo]))
        ->toBe([], "a guarda acusou um caso da fatia que ela declara NAO decidir: {$rotulo}");
})->with([
    'leitura indireta, caminho por variavel' => ['IndiretaTest.php', <<<'PHP'
    <?php
    it('[FIXTURE-C] le o caminho que o dataset trouxe', function (string $caminho): void {
        expect(File::get(base_path($caminho)))->not->toBe('');
    })->with(['docs/pt/comecar/dominio-local.md']);
    PHP],

    'mencao dentro de string, sem leitura' => ['MencaoTest.php', <<<'PHP'
    <?php
    it('[FIXTURE-D] afirma que o outro caso continua abrindo o documento', function (): void {
        $fonte = (string) file_get_contents(base_path('tests/Kit/HostLocalTest.php'));
        $this->assertStringContainsString("File::get(base_path('docs/pt/comecar/dominio-local.md'))", $fonte);
    });
    PHP],

    /*
     * OS DOIS RAMOS DE `temSentinelaPropria()`, um caso cada — e a primeira redacao deste bloco
     * tinha so um, que exercitava o ramo `->skip()` quando a correcao era no ramo
     * `markTestSkipped`. O mutante sobreviveu a bateria e so apareceu porque ela rodou.
     *
     * Nos dois, a sentinela e o skip estao separados por OUTRA instrucao. A janela antiga
     * (`[^;]`) cortava no primeiro `;` e acusava um caso GUARDADO.
     */
    'markTestSkipped com instrucao no meio' => ['JanelaSkipTest.php', <<<'PHP'
    <?php
    it('[FIXTURE-F] guardado por markTestSkipped, com uma instrucao no meio', function (): void {
        if (! naArvoreDoKit()) {
            $motivo = 'fora da arvore do kit';
            $this->markTestSkipped($motivo);
        }

        expect(File::get(base_path('docs/pt/comecar/dominio-local.md')))->not->toBe('');
    });
    PHP],

    /*
     * O rotulo NAO escreve `->skip(` literal: o regex do `[CT-10]` casa `skip(` seguido de `docs`
     * sem `;` no meio, e o rotulo em prosa ficava colado no heredoc que le `docs/`. Falso
     * positivo do CT-10 produzido pelo texto do caso, e nao pelo codigo.
     */
    'closure de mais de uma instrucao no gatilho de pulo' => ['JanelaClosureTest.php', <<<'PHP'
    <?php
    it('[FIXTURE-J] guardado por gatilho de pulo com closure multilinha', function (): void {
        expect(File::get(base_path('docs/pt/comecar/dominio-local.md')))->not->toBe('');
    })->skip(function (): bool {
        $rotulo = 'sentinela';

        return ! naArvoreDoKit();
    });
    PHP],

    'sentinela no beforeEach do arquivo' => ['BeforeEachTest.php', <<<'PHP'
    <?php
    beforeEach(function (): void {
        if (! naArvoreDoKit()) {
            $this->markTestSkipped('fora da arvore do kit');
        }
    });

    it('[FIXTURE-E] le o site, protegido pelo beforeEach acima', function (): void {
        expect(File::get(base_path('docs/pt/comecar/dominio-local.md')))->not->toBe('');
    });
    PHP],
])->group('kit');
