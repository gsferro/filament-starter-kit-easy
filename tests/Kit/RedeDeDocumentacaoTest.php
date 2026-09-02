<?php

use Symfony\Component\Finder\Finder;

/**
 * A rede de asserções sobre a documentação continua vigiando o texto ONDE ELE PASSOU A MORAR.
 *
 * IDs de CT em `wikis/specs/feat/site-de-documentacao/site-de-documentacao/04-casos-de-teste.md`
 * (R4). Separado de `SiteDeDocumentacaoTest` porque o alvo aqui não é o site: são as suítes
 * que leem a documentação.
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
 * O código PHP sem comentários e docblocks — `token_get_all()` e não regex, porque a menção a
 * um literal dentro de um docblock é o caso mais comum nesta suíte (`HelpersDeTesteTest`).
 */
function codigoSemComentario(string $codigo): string
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
 * CT-10 — nenhum cenário se guarda pela própria entrega. Inspeção ESTÁTICA dos dois arquivos
 * de teste da feature: a sentinela é `naArvoreDoKit()` (`.github`), e nenhum desvio de
 * execução consulta `docs/` — `is_dir('docs')` é auto-anulante: sem a migração, `docs/` não
 * existe, tudo é ignorado e `composer test:kit` fica verde com zero entrega (M40, M19).
 *
 * A forma anterior (um `Então` de auto-declaração dentro deste mesmo cenário) atestava a si
 * mesma; ler o código dos arquivos, como faz `HelpersDeTesteTest`, é o que funciona.
 */
it('[CT-10] nenhum cenário se guarda pela própria entrega', function (): void {
    foreach (['SiteDeDocumentacaoTest.php', 'RedeDeDocumentacaoTest.php'] as $arquivo) {
        $codigo = codigoSemComentario((string) file_get_contents(__DIR__.'/'.$arquivo));

        preg_match_all('/\b(is_dir|file_exists|is_file|markTestSkipped|skip)\s*\([^;]*?docs/', $codigo, $guardasSobreDocs);

        expect($codigo)->toContain('naArvoreDoKit()')
            ->and($guardasSobreDocs[0])->toBe([], "{$arquivo} condiciona execução à existência de docs/");
    }
});
