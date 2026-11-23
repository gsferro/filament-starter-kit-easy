<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

/*
|--------------------------------------------------------------------------
| Mapa interativo do kit — as guardas da publicação (lote G1b)
|--------------------------------------------------------------------------
|
| [CT-07] (R7), [CT-23] (R21), [CT-25] e [CT-42] (R23), [CT-26] (R25), [CT-27] (R26), [CT-28] (R27)
| e [CT-34] (R33) do `04-casos-de-teste.md` de `wikis/specs/feat/mapa-interativo-do-kit/`.
|
| Toda guarda é uma função aplicada ao arquivo publicado e a cópias adulteradas em memória: a linha
| "cópia com …" chama a MESMA função que a linha "publicado", nunca uma função paralela.
|
| Tudo aqui lê `site/`, `docs/`, os READMEs ou `.ai/rules/`, que não viajam para o projeto
| instalado, ou invoca o `git`, que o `create-project` não entrega: a sentinela fica no
| `beforeEach` (molde de `tests/Kit/GuardasDosDiagramasTest.php`) e no `->skip()` de cada caso.
|
| O índice do git (`arquivosVersionados()`), os diagramas (`diagramasDoMapa()`), o registro vivo do
| painel (`registroVivoDoPainel()`), o motivo de enfeite (`motivoDeEnfeite()`) e o `node` do ambiente
| (`versaoDoNode()`) moram em `tests/Pest.php`, porque as outras guardas do mapa também os usam
| (`.ai/rules/testes.md`).
|
| [CT-48] e [CT-51] (R39), [CT-49] e [CT-50] (R40) são da costura G5, forma de efeito: o gerador e o
| posicionador rodam como processo filho do `node`, SEMPRE num clone descartável do kit no commit do
| teste (`git clone --shared` esparso, com a raiz e `site/mapa`), numa pasta temporária nova, sem
| `site/.archify` e com o download do archify bloqueado (`GIT_ALLOW_PROTOCOL=file`). Nunca na árvore
| do kit: ela tem o archify baixado, e o gerador, sob o defeito do achado A2, apagaria a pasta de
| `--saida`. `publicacaoForaDoKit()` trava todo caminho em que a G5 roda, grava ou apaga.
*/

beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        test()->markTestSkipped('As guardas da publicação do mapa só existem na árvore do kit — o projeto instalado não recebe site/, o diretório das páginas do site, os READMEs nem .ai/rules/.');
    }
});

/** O texto sem as linhas que casam o padrão: a cópia adulterada em memória. */
function publicacaoSemLinhas(string $texto, string $padrao): string
{
    return implode("\n", array_filter(preg_split('/\R/u', $texto), fn (string $linha): bool => preg_match($padrao, $linha) !== 1));
}

function publicacaoSemComentarioHtml(string $texto): string
{
    return (string) preg_replace('/<!--.*?-->/su', '', $texto);
}

/* ---------------------------------------------------------------- R7 */

/** O caminho casa o glob do índice das rules (`**` atravessa pastas; `*` e `?`, não). */
function publicacaoCasaGlob(string $glob, string $caminho): bool
{
    $regex = strtr(preg_quote($glob, '#'), ['\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]']);

    return preg_match('#^'.$regex.'$#u', $caminho) === 1;
}

/**
 * Os caminhos da linha do índice das rules que aponta para a rule; `null` quando a linha não existe.
 *
 * @return ?list<string>
 */
function publicacaoGlobsDaRule(string $indice, string $rule): ?array
{
    foreach (preg_split('/\R/u', $indice) as $linha) {
        $celulas = array_map('trim', explode('|', trim(trim($linha), '|')));

        if (count($celulas) < 2 || ! str_contains($celulas[count($celulas) - 1], $rule)) {
            continue;
        }

        return array_values(array_filter(
            array_map(fn (string $glob): string => trim($glob, " `'\""), explode(',', $celulas[0])),
            fn (string $glob): bool => $glob !== '',
        ));
    }

    return null;
}

/* ---------------------------------------------------------------- R21 */

/**
 * Os arquivos de uma pasta, recursivo, relativos a ela e sempre com `/`.
 *
 * @return list<string>
 */
function publicacaoArquivosDaPasta(string $pasta): array
{
    $arquivos = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS)) as $arquivo) {
        if ($arquivo->isFile()) {
            $arquivos[] = str_replace('\\', '/', substr($arquivo->getPathname(), strlen($pasta) + 1));
        }
    }

    sort($arquivos);

    return $arquivos;
}

/**
 * R21: os quatro campos de dependência do `site/package.json` e os arquivos de `site/mapa/`
 * contra a lista fechada. Devolve os motivos de reprovação, cada um nomeando o item; vazio é "aceita".
 *
 * @param  array<string, mixed>  $pacote
 * @param  list<string>  $arquivosDoMapa  relativos a `site/mapa/`
 * @return list<string>
 */
function publicacaoConferirDependenciasDoSite(array $pacote, array $arquivosDoMapa): array
{
    $base = [
        'dependencies'    => ['@astrojs/starlight', 'astro', 'astro-mermaid', 'mermaid', 'sharp'],
        'devDependencies' => ['axe-core', 'playwright', 'wait-on'],
    ];

    $reprovas = [];

    foreach ($base as $campo => $esperadas) {
        $declaradas = array_keys((array) ($pacote[$campo] ?? []));

        foreach (array_diff($declaradas, $esperadas) as $nome) {
            $reprovas[] = "{$campo}: dependência fora da base, {$nome}";
        }

        foreach (array_diff($esperadas, $declaradas) as $nome) {
            $reprovas[] = "{$campo}: dependência da base ausente, {$nome}";
        }
    }

    foreach (['optionalDependencies', 'peerDependencies'] as $campo) {
        foreach (array_keys((array) ($pacote[$campo] ?? [])) as $nome) {
            $reprovas[] = "{$campo}: dependência declarada, {$nome}";
        }
    }

    // O download, o gerador, a interface, o posicionador, os dois arquivos do modelo e a pasta de diagramas.
    $lista = ['archify.mjs', 'gerar.mjs', 'interface.json', 'posicionar.mjs', 'pecas.json', 'ligacoes.json'];

    foreach ($arquivosDoMapa as $arquivo) {
        if (! in_array($arquivo, $lista, true) && preg_match('#^diagramas/[^/]+\.json$#', $arquivo) !== 1) {
            $reprovas[] = "site/mapa/: arquivo fora da lista, {$arquivo}";
        }
    }

    return $reprovas;
}

/* ---------------------------------------------------------------- R23 */

/**
 * A posição do primeiro passo cujo `run` ou `uses` contém o trecho; `null` se nenhum.
 *
 * @param  list<array<string, mixed>>  $passos
 */
function publicacaoIndiceDoPasso(array $passos, string $trecho): ?int
{
    foreach (array_values($passos) as $indice => $passo) {
        if (str_contains((string) ($passo['run'] ?? ''), $trecho) || str_contains((string) ($passo['uses'] ?? ''), $trecho)) {
            return $indice;
        }
    }

    return null;
}

/** @param  array<string, mixed>  $job */
function publicacaoSoNaMain(array $job): bool
{
    return preg_match("~github\\.ref\\s*==\\s*'refs/heads/main'~", (string) ($job['if'] ?? '')) === 1;
}

/**
 * R23, CT-25: o passo de build roda o build do site, o build do site gera os diagramas do mapa, e o
 * conferidor do mapa roda depois do de acessibilidade, com a prévia que ele subiu, e (quando o
 * job envia o artefato) antes do envio, num job que só roda na `main`.
 *
 * @param  array<string, mixed>  $workflow
 * @param  array<string, mixed>  $pacoteDoSite
 * @return list<string>
 */
function publicacaoConferirBuildDoMapa(array $workflow, string $job, array $pacoteDoSite, bool $enviaArtefato): array
{
    $definicao = $workflow['jobs'][$job] ?? null;

    if (! is_array($definicao) || ! is_array($definicao['steps'] ?? null)) {
        return ["o job \"{$job}\" não existe ou não tem passos"];
    }

    $passos   = array_values($definicao['steps']);
    $reprovas = [];

    $build = publicacaoIndiceDoPasso($passos, 'npm run build');

    if ($build === null) {
        $reprovas[] = "o job \"{$job}\" não roda o build do site (npm run build)";
    }

    $script = (string) ($pacoteDoSite['scripts']['build'] ?? '');

    if (! str_contains($script, 'astro build') || ! str_contains($script, 'mapa/gerar.mjs')) {
        $reprovas[] = "o build do site não gera os diagramas do mapa: scripts.build é \"{$script}\"";
    }

    $acessibilidade = publicacaoIndiceDoPasso($passos, 'verifica-acessibilidade.mjs');
    $mapa           = publicacaoIndiceDoPasso($passos, 'verifica-mapa.mjs');

    if ($acessibilidade === null) {
        $reprovas[] = "o job \"{$job}\" não roda o conferidor de acessibilidade";
    }

    if ($mapa === null) {
        $reprovas[] = "o job \"{$job}\" não roda o conferidor do mapa (verifica-mapa.mjs)";

        return $reprovas;
    }

    $nome = (string) ($passos[$mapa]['name'] ?? 'verifica-mapa.mjs');

    if (($passos[$mapa]['working-directory'] ?? null) !== 'site') {
        $reprovas[] = "o passo \"{$nome}\" não roda o site/verifica-mapa.mjs: working-directory fora de site";
    }

    if ($build !== null && $mapa < $build) {
        $reprovas[] = "o passo \"{$nome}\" roda antes do build do site";
    }

    if ($acessibilidade !== null && $mapa <= $acessibilidade) {
        $reprovas[] = "o passo \"{$nome}\" não roda depois do conferidor de acessibilidade";
    }

    $previas = array_keys(array_filter($passos, fn (array $passo): bool => str_contains((string) ($passo['run'] ?? ''), 'astro preview')));

    if (count($previas) !== 1 || ($acessibilidade !== null && $previas[0] > $acessibilidade)) {
        $reprovas[] = "o job \"{$job}\" não sobe uma prévia só, até o conferidor de acessibilidade, para o conferidor do mapa usar";
    }

    preg_match('~http://localhost:\d+~', (string) ($passos[$mapa]['run'] ?? ''), $alvoDoMapa);
    preg_match('~http://localhost:\d+~', (string) ($passos[$acessibilidade ?? $mapa]['run'] ?? ''), $alvoDaAcessibilidade);

    if (($alvoDoMapa[0] ?? null) !== null && ($alvoDoMapa[0] ?? null) !== ($alvoDaAcessibilidade[0] ?? null)) {
        $reprovas[] = "o passo \"{$nome}\" espera outra prévia ({$alvoDoMapa[0]}), e não a do conferidor de acessibilidade";
    }

    if ($enviaArtefato) {
        $envio = publicacaoIndiceDoPasso($passos, 'upload-pages-artifact');

        if ($envio === null) {
            $reprovas[] = "o job \"{$job}\" não envia o artefato do Pages";
        } elseif ($mapa >= $envio) {
            $reprovas[] = "o passo \"{$nome}\" roda depois do envio do artefato";
        }

        if (! publicacaoSoNaMain($definicao)) {
            $reprovas[] = "o job \"{$job}\" roda fora da main: sem a condição github.ref == 'refs/heads/main'";
        }
    }

    return $reprovas;
}

/**
 * R23, CT-42: a falha do conferidor do mapa reprova o job — o passo não tem `continue-on-error`, o
 * comando não engole a falha (`||` depois do conferidor) — e, no Pages, o job só roda na `main`.
 *
 * @param  array<string, mixed>  $workflow
 * @return list<string>
 */
function publicacaoConferirFalhaDoMapa(array $workflow, string $job, bool $exigeMain): array
{
    $definicao = $workflow['jobs'][$job] ?? null;

    if (! is_array($definicao)) {
        return ["o job \"{$job}\" não existe"];
    }

    $reprovas = [];

    if ($exigeMain && ! publicacaoSoNaMain($definicao)) {
        $reprovas[] = "o job \"{$job}\" roda fora da main: sem a condição github.ref == 'refs/heads/main'";
    }

    $achou = false;

    foreach ((array) ($definicao['steps'] ?? []) as $passo) {
        $run      = (string) ($passo['run'] ?? '');
        $posicao  = strpos($run, 'verifica-mapa.mjs');

        if ($posicao === false) {
            continue;
        }

        $achou    = true;
        $nome     = (string) ($passo['name'] ?? $run);
        $continua = $passo['continue-on-error'] ?? false;

        if (! in_array($continua, [false, 'false'], true)) {
            $reprovas[] = "o passo \"{$nome}\" tem continue-on-error: ".var_export($continua, true);
        }

        if (str_contains(substr($run, $posicao), '||')) {
            $reprovas[] = "o passo \"{$nome}\" engole a falha do conferidor do mapa com ||";
        }
    }

    if (! $achou) {
        $reprovas[] = "o job \"{$job}\" não roda o conferidor do mapa (verifica-mapa.mjs)";
    }

    return $reprovas;
}

/**
 * Os nomes dos diagramas do mapa: a pasta de diagramas (`diagramasDoMapa()`, de `tests/Pest.php`) e os
 * links da página do mapa.
 *
 * @return list<string>
 */
function publicacaoSlugsDoMapa(): array
{
    $slugs = array_keys(diagramasDoMapa());

    foreach (['pt', 'en'] as $idioma) {
        preg_match_all("~mapa/{$idioma}/([A-Za-z0-9_-]+)\\.html~", (string) file_get_contents(base_path("docs/{$idioma}/referencia/mapa-interativo.md")), $links);
        $slugs = [...$slugs, ...$links[1]];
    }

    return array_values(array_unique($slugs));
}

/**
 * Os HTML de diagrama do mapa no índice do git: os de uma pasta `mapa/`, e os que têm o nome de
 * um diagrama sem ser o stub de redirecionamento de uma página do site.
 *
 * @param  list<string>  $versionados
 * @param  list<string>  $slugs
 * @return list<string>
 */
function publicacaoHtmlsDoMapaVersionados(array $versionados, array $slugs): array
{
    return array_values(array_filter($versionados, function (string $arquivo) use ($slugs): bool {
        if (! str_ends_with($arquivo, '.html')) {
            return false;
        }

        if (preg_match('#(^|/)mapa/#', $arquivo) === 1) {
            return true;
        }

        return in_array(basename($arquivo, '.html'), $slugs, true)
            && (! is_file(base_path($arquivo)) || stripos((string) file_get_contents(base_path($arquivo)), 'http-equiv="refresh"') === false);
    }));
}

/* ---------------------------------------------------------------- R25 */

/** O endereço do site do kit, do repositório do `support.source` do `composer.json`. */
function publicacaoSiteDoKit(): string
{
    $composer = composerDoKit();

    preg_match('~github\.com/([^/]+)/([^/#?]+)~', (string) ($composer['support']['source'] ?? ''), $repositorio);

    return "https://{$repositorio[1]}.github.io/{$repositorio[2]}/";
}

/**
 * Os links (Markdown, de referência ou `href`) para a página do mapa, por idioma.
 *
 * @return array{pt: list<string>, en: list<string>}
 */
function publicacaoLinksDoMapa(string $markdown): array
{
    preg_match_all('~\]\(\s*<?([^)\s>]+)~u', $markdown, $inline);
    preg_match_all('~href\s*=\s*["\']([^"\']+)["\']~iu', $markdown, $html);
    preg_match_all('~^\s*\[[^\]]+\]:\s*<?(\S+?)>?\s*$~mu', $markdown, $referencias);

    $porIdioma = ['pt' => [], 'en' => []];

    foreach ([...$inline[1], ...$html[1], ...$referencias[1]] as $endereco) {
        if (preg_match('~(?:^|/)(pt|en)/referencia/mapa-interativo(?:/|\.html|\.md)?(?:[?#].*)?$~u', $endereco, $idioma) === 1) {
            $porIdioma[$idioma[1]][] = $endereco;
        }
    }

    return $porIdioma;
}

/* ---------------------------------------------------------------- R26 */

/**
 * O inventário Mermaid da base (`d9608c5a`), num idioma, com o arquivo de cada bloco CONGELADO
 * (Setup Global): DG-01 a DG-20 em `docs/{idioma}/`, e o DG-01 também no README. Medido nesta
 * escrita com `blocosMermaidDe()` sobre os arquivos daquele commit; o mapeamento é o mesmo nos
 * dois idiomas.
 *
 * @return list<array{id: string, arquivo: string}>
 */
function publicacaoInventarioMermaidCongelado(string $idioma): array
{
    $paginas = [
        'DG-01' => 'referencia/arquitetura-em-diagramas.md',
        'DG-02' => 'referencia/arquitetura-em-diagramas.md',
        'DG-03' => 'referencia/arquitetura-em-diagramas.md',
        'DG-04' => 'autenticacao/index.md',
        'DG-05' => 'autenticacao/login-unificado.md',
        'DG-06' => 'autenticacao/login-social.md',
        'DG-07' => 'autenticacao/convites.md',
        'DG-08' => 'autenticacao/estados-de-usuario.md',
        'DG-09' => 'autenticacao/convites.md',
        'DG-10' => 'autenticacao/index.md',
        'DG-11' => 'operacao/roteiro-de-features.md',
        'DG-12' => 'recursos/trilhas-de-infraestrutura.md',
        'DG-13' => 'referencia/arquitetura-em-diagramas.md',
        'DG-14' => 'recursos/configuracoes-do-kit.md',
        'DG-15' => 'comecar/instalacao-avancada.md',
        'DG-16' => 'comecar/atualizando-o-projeto.md',
        'DG-17' => 'comecar/atualizando-o-projeto.md',
        'DG-18' => 'comecar/instalacao-avancada.md',
        'DG-19' => 'operacao/desenvolvendo-o-kit.md',
        'DG-20' => 'recursos/multi-tenancy.md',
    ];

    $inventario = [['id' => 'DG-01', 'arquivo' => $idioma === 'en' ? 'README.en.md' : 'README.md']];

    foreach ($paginas as $id => $pagina) {
        $inventario[] = ['id' => $id, 'arquivo' => "docs/{$idioma}/{$pagina}"];
    }

    return $inventario;
}

/** O commit da base existe aqui? O checkout raso do CI (`actions/checkout` sem `fetch-depth`) não o traz. */
function publicacaoTemOCommitDaBase(): bool
{
    return trim(Process::path(base_path())->run(['git', 'cat-file', '-t', 'd9608c5a'])->output()) === 'commit';
}

/**
 * O inventário Mermaid da base congelada (`d9608c5a`), num idioma: `blocosMermaidDe()` rodado no
 * README e nas páginas de `docs/{idioma}/` DAQUELE commit, como `blocosMermaidDaArvore()` faz na
 * árvore — cada DG fora de comentário HTML, com o arquivo em que estava.
 *
 * @return list<array{id: string, arquivo: string}>
 */
function publicacaoInventarioMermaidDaBase(string $idioma): array
{
    $base   = 'd9608c5a';
    $readme = $idioma === 'en' ? 'README.en.md' : 'README.md';
    $saida  = Process::path(base_path())->run(['git', 'grep', '-l', 'mermaid', $base, '--', $readme, "docs/{$idioma}"])->throw()->output();

    $inventario = [];

    foreach (array_filter(array_map('trim', explode("\n", $saida))) as $linha) {
        $arquivo  = substr($linha, strlen($base) + 1);
        $conteudo = Process::path(base_path())->run(['git', 'show', "{$base}:{$arquivo}"])->throw()->output();

        foreach (blocosMermaidDe($conteudo) as $bloco) {
            if ($bloco['idCatalogo'] !== null && ! $bloco['dentroDeComentarioHtml']) {
                $inventario[] = ['id' => $bloco['idCatalogo'], 'arquivo' => $arquivo];
            }
        }
    }

    return $inventario;
}

/* ---------------------------------------------------------------- R27 */

/**
 * Os pacotes que os manifestos declaram (Q16): o `composer.json` em `require` e `require-dev`, e o
 * `package.json` da aplicação em `dependencies`, `devDependencies` e `optionalDependencies`. O
 * `php` (e qualquer `ext-*`) é plataforma e fica fora da conta.
 *
 * @return list<string>
 */
function publicacaoPacotesInstalados(): array
{
    $composer = composerDoKit();
    $npm      = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    $nomes = [];

    foreach (['require', 'require-dev'] as $campo) {
        foreach (array_keys($composer[$campo] ?? []) as $nome) {
            if ($nome !== 'php' && ! str_starts_with($nome, 'ext-')) {
                $nomes[] = strtolower($nome);
            }
        }
    }

    foreach (['dependencies', 'devDependencies', 'optionalDependencies'] as $campo) {
        foreach (array_keys($npm[$campo] ?? []) as $nome) {
            $nomes[] = strtolower($nome);
        }
    }

    return array_values(array_unique($nomes));
}

/**
 * Os pacotes que a referência lista fora da seção dos avaliados e não adotados. O nome sai do
 * endereço do link da primeira célula: o texto `pest-plugin-laravel` leva a
 * `pestphp/pest-plugin-laravel`.
 *
 * @return list<string>
 */
function publicacaoPacotesDaReferencia(string $referencia): array
{
    $pacotes   = [];
    $avaliados = false;

    foreach (preg_split('/\R/u', $referencia) as $linha) {
        if (str_starts_with($linha, '## ')) {
            $avaliados = preg_match('/Avaliados e não adotados|Evaluated and not adopted/iu', $linha) === 1;

            continue;
        }

        if ($avaliados || preg_match('/^\|([^|]*)\|/u', $linha, $celula) !== 1) {
            continue;
        }

        preg_match_all('~\]\((https?://[^)\s]+)\)~u', $celula[1], $links);

        foreach ($links[1] as $endereco) {
            if (preg_match('~packagist\.org/packages/([^/?#]+/[^/?#]+)~', $endereco, $pacote) === 1
                || preg_match('~npmjs\.com/package/((?:@[^/?#]+/)?[^/?#]+)~', $endereco, $pacote) === 1
                || preg_match('~github\.com/([^/?#]+/[^/?#]+)~', $endereco, $pacote) === 1) {
                $pacotes[] = strtolower($pacote[1]);
            }
        }
    }

    return array_values(array_unique($pacotes));
}

/**
 * Os pacotes que uma F-nn de origem pacote, ou pacote com customização própria, nomeia na coluna
 * Origem, pelo nome do Composer ou do npm entre crases (`@premissa` Q17).
 *
 * @return array<string, list<string>> pacote => F-nn
 */
function publicacaoPacotesNomeadosNoCatalogo(string $catalogo): array
{
    $nomeados = [];

    foreach (preg_split('/\R/u', $catalogo) as $linha) {
        if (preg_match('/^\|\s*(F-\d+[a-z]?)\s*\|[^|]*\|([^|]*)\|/u', $linha, $feature) !== 1
            || preg_match('/^\s*(pacote|package)\b/iu', $feature[2]) !== 1) {
            continue;
        }

        preg_match_all('/`([^`]+)`/u', $feature[2], $nomes);

        foreach ($nomes[1] as $nome) {
            $nomeados[strtolower(trim($nome))][] = $feature[1];
        }
    }

    return $nomeados;
}

/**
 * A lista da guarda dos pacotes instalados sem feature própria, com o motivo de cada um
 * (`@premissa` Q17: mora na guarda, como a lista das páginas, Q14b). Pacote cuja classe um painel
 * registra nunca entra aqui (ADV-04).
 *
 * @return array<string, string>
 */
function publicacaoPacotesSemFeaturePropria(): array
{
    return [
        'laravel/framework'                 => 'é o framework sobre o qual o kit inteiro roda: a base de todas as features, e não uma feature que alguém usa por ele',
        'laravel/tinker'                    => 'REPL de terminal (php artisan tinker) para quem desenvolve: não tem tela, rota nem peça nos painéis',
        'laravel/reverb'                    => 'servidor WebSocket que o Docker sobe nos profiles app e realtime; o Echo do Filament não vem configurado, e nenhuma tela do kit o usa hoje',
        'predis/predis'                     => 'cliente Redis em PHP puro: o driver de conexão do cache, da fila e da sessão quando o Redis está ligado, sem tela nem peça própria',
        'livewire/blaze'                    => 'instalado sem uso no código do kit hoje: nenhum componente Blade do kit passa pelo otimizador',
        'awcodes/filament-badgeable-column' => 'instalado sem uso no código do kit hoje: nenhuma tabela dos painéis usa a coluna com badges',
        'lara-zeus/progress'                => 'instalado sem uso no código do kit hoje: nenhuma coluna nem entry dos painéis usa a barra de progresso',
        'syriable/filament-activitylog'     => 'instalado sem uso no código do kit hoje: nenhum painel registra o plugin do log de atividades',
        'filament/upgrade'                  => 'ferramenta de terminal para atualizar o Filament (composer upgrade:filament): roda só na máquina de quem mantém o kit',
        'laravel/pail'                      => 'ferramenta de terminal que mostra os logs em tempo real para quem desenvolve: não entrega nada nos painéis',
        'nunomaduro/collision'              => 'formata os erros no terminal e na saída dos testes para quem desenvolve: não entrega nada nos painéis',
        '@tailwindcss/vite'                 => 'plugin de build que liga o Tailwind ao Vite na compilação dos temas: não tem tela nem peça',
        'concurrently'                      => 'ferramenta de terminal que sobe servidor, fila, Vite e Reverb juntos no composer dev: só para quem desenvolve',
        'laravel-vite-plugin'               => 'integração de build entre o Vite e o Laravel, que publica os assets compilados: não tem tela nem peça',
        'tailwindcss'                       => 'framework de CSS dos temas dos painéis, usado só na compilação dos assets: não entrega feature por si',
        'vite'                              => 'empacotador dos assets do front-end, usado só na compilação: não tem tela nem peça',
        '@laravel/multiplex'                => 'dependência opcional que agrupa as requisições do Livewire: otimização de transporte, sem tela nem peça própria',
    ];
}

/**
 * Os pacotes de `vendor/` cuja classe um painel registra — plugin, recurso, página ou widget —,
 * lidos do registro vivo (`registroVivoDoPainel()`, de `tests/Pest.php`).
 *
 * @return array<string, list<string>> pacote => "painel: classe"
 */
function publicacaoPacotesComPecaRegistrada(): array
{
    $vendor    = strtolower(str_replace('\\', '/', base_path('vendor'))).'/';
    $porPacote = [];

    foreach (Filament::getPanels() as $painel) {
        $registro = registroVivoDoPainel($painel);
        $classes  = [...$registro['plugin'], ...$registro['recurso'], ...$registro['pagina'], ...$registro['widget']];

        foreach (array_unique($classes) as $classe) {
            $arquivo = strtolower(str_replace('\\', '/', (string) (new ReflectionClass($classe))->getFileName()));

            if (! str_starts_with($arquivo, $vendor)) {
                continue;
            }

            [$fornecedor, $nome] = explode('/', substr($arquivo, strlen($vendor)), 3);

            $porPacote["{$fornecedor}/{$nome}"][] = $painel->getId().': '.$classe;
        }
    }

    return $porPacote;
}

/**
 * R27: instalado → referência e (F-nn de origem pacote ou lista dos sem feature própria);
 * referência → instalado; e a lista sem motivo de enfeite e sem pacote com peça registrada.
 *
 * @param  list<string>  $instalados
 * @param  array<string, string>  $semFeature
 * @param  array<string, list<string>>  $comPecaRegistrada
 * @return array{reprovas: list<string>, conferidos: list<string>}
 */
function publicacaoConferirPacotes(array $instalados, string $referencia, string $catalogo, array $semFeature, array $comPecaRegistrada): array
{
    $listados = publicacaoPacotesDaReferencia($referencia);
    $nomeados = publicacaoPacotesNomeadosNoCatalogo($catalogo);
    $reprovas = [];

    foreach ($semFeature as $pacote => $motivo) {
        if (motivoDeEnfeite($motivo)) {
            $reprovas[] = "{$pacote}: na lista dos sem feature própria sem motivo, ou com motivo de enfeite";
        }

        if (isset($comPecaRegistrada[$pacote])) {
            $reprovas[] = "{$pacote}: na lista dos sem feature própria, e um painel registra peça dele (".implode('; ', $comPecaRegistrada[$pacote]).')';
        }
    }

    foreach ($instalados as $pacote) {
        if (! in_array($pacote, $listados, true)) {
            $reprovas[] = "{$pacote}: instalado e fora da referência de pacotes";
        }

        if (! isset($nomeados[$pacote]) && ! array_key_exists($pacote, $semFeature)) {
            $reprovas[] = "{$pacote}: sem F-nn de origem pacote e fora da lista dos sem feature própria";
        }
    }

    foreach ($listados as $pacote) {
        if (! in_array($pacote, $instalados, true)) {
            $reprovas[] = "{$pacote}: na referência de pacotes e não instalado";
        }
    }

    return ['reprovas' => $reprovas, 'conferidos' => $instalados];
}

/* ---------------------------------------------------------------- R33 */

/**
 * O que a página do mapa tem de dizer, por idioma (R33), com o `Então` que cada padrão confere.
 *
 * @return array<string, string>
 */
function publicacaoDeclaracoesDaPaginaDoMapa(string $idioma): array
{
    if ($idioma === 'en') {
        return [
            'a casca do comparativo fica em inglês'                                                            => '/comparison[^.]*shell[^.]*English/iu',
            'o piso da varredura: as peças que os painéis registram e as classes de app/ fora de app/Filament' => '/floor[^.]*piece[^.]*panel[^.]*regist[^.]*class[^.]*app\/[^.]*outside[^.]*app\/Filament/iu',
            'além do piso, a completude não tem garantia'                                                      => '/Completeness[^.]*floor[^.]*(?:does not|doesn\'t) guarantee/iu',
            'o comparativo não leva cards nem passaporte'                                                      => '/comparison[^.]*no cards (?:or|nor) passport/iu',
            'a base e a cabeça do comparativo são diagramas do mapa com a prova de cada ligação'               => '/comparison[^.]*proof of every link[^.]*diagrams it compares/iu',
        ];
    }

    return [
        'a casca do comparativo fica em inglês'                                                            => '/comparativo[^.]*casca[^.]*inglês/iu',
        'o piso da varredura: as peças que os painéis registram e as classes de app/ fora de app/Filament' => '/piso[^.]*peça[^.]*painel[^.]*regist[^.]*classe[^.]*app\/[^.]*fora de[^.]*app\/Filament/iu',
        'além do piso, a completude não tem garantia'                                                      => '/Completude[^.]*piso[^.]*não garante/iu',
        'o comparativo não leva cards nem passaporte'                                                      => '/comparativo[^.]*não leva cards nem passaporte/iu',
        'a base e a cabeça do comparativo são diagramas do mapa com a prova de cada ligação'               => '/comparativo[^.]*prova de cada ligação[^.]*diagramas que ele compara/iu',
    ];
}

/* ================================================================ casos */

it('[CT-07] a rule do catálogo está no índice das rules, alcança o catálogo e os lugares onde a feature nasce, e manda o número só crescer', function (): void {
    $globs = publicacaoGlobsDaRule((string) file_get_contents(base_path('.ai/rules/index.md')), '.ai/rules/catalogo-de-features.md');

    // Então a linha existe, e cada caminho dela casa ao menos um arquivo existente
    expect($globs)->not->toBeNull('o índice das rules não tem a linha da rule do catálogo')
        ->and($globs)->not->toBeEmpty();

    $arquivos = arquivosVersionados();

    expect($arquivos)->not->toBeEmpty();

    foreach ($globs as $glob) {
        $casados = array_filter($arquivos, fn (string $arquivo): bool => publicacaoCasaGlob($glob, $arquivo));

        expect($casados)->not->toBeEmpty("o caminho {$glob} da linha do índice não casa arquivo nenhum");
    }

    // ... e todo arquivo versionado de app/ e de routes/, onde a feature nasce, cai em algum caminho da linha
    $foraDaLinha = array_values(array_filter(
        $arquivos,
        fn (string $arquivo): bool => (str_starts_with($arquivo, 'app/') || str_starts_with($arquivo, 'routes/'))
            && array_filter($globs, fn (string $glob): bool => publicacaoCasaGlob($glob, $arquivo)) === [],
    ));

    expect($foraDaLinha)->toBe([], 'arquivos de app/ e de routes/ que a linha do índice não alcança');

    // E os caminhos casam o catálogo em pt e em en, e também app/**, composer.json, package.json e routes/**
    foreach ([
        'docs/pt/operacao/roteiro-de-features.md',
        'docs/en/operacao/roteiro-de-features.md',
        'app/Filament/Admin/Resources/Exemplos/ExemploResource.php',
        'app/Console/Commands/ExemploCommand.php',
        'composer.json',
        'package.json',
        'routes/web.php',
    ] as $alvo) {
        $alcancado = array_filter($globs, fn (string $glob): bool => publicacaoCasaGlob($glob, $alvo)) !== [];

        expect($alcancado)->toBeTrue("a linha do índice não alcança {$alvo}");
    }

    $rule = (string) preg_replace('/\s+/u', ' ', (string) file_get_contents(base_path('.ai/rules/catalogo-de-features.md')));

    // E a rule nomeia a página do catálogo como o lugar único das features
    expect($rule)->toContain('docs/pt/operacao/roteiro-de-features.md')
        ->toContain('docs/en/operacao/roteiro-de-features.md')
        ->toMatch('/lugar único das features|catálogo único/u');

    // E a rule manda o próximo número livre e proíbe trocar, reaproveitar ou remover número sem registro
    expect($rule)->toMatch('/próximo número livre/u')
        ->toMatch('/proibido[^.]*trocar[^.]*reaproveitar[^.]*remover[^.]*sem registro/iu');
})->skip(fn (): bool => ! naArvoreDoKit(), 'Lê .ai/rules/ e invoca o git, que não viajam no pacote.');

it('[CT-23] o site não ganha dependência de visualização além do archify, nem pelo manifesto nem por arquivo em site/mapa/', function (string $mundo, ?string $nomeado): void {
    $pacote   = json_decode((string) file_get_contents(base_path('site/package.json')), true, flags: JSON_THROW_ON_ERROR);
    $arquivos = publicacaoArquivosDaPasta(base_path('site/mapa'));

    // Piso de não vácuo: a guarda leu o manifesto e a pasta
    expect($pacote['dependencies'] ?? [])->not->toBeEmpty()
        ->and($arquivos)->not->toBeEmpty();

    match ($mundo) {
        'publicados'                       => null,
        'd3 nas de execução'               => $pacote['dependencies']['d3']          = '^7.9.0',
        '@antv/g6 nas de desenvolvimento'  => $pacote['devDependencies']['@antv/g6'] = '^5.0.0',
        'chart.js em peerDependencies'     => $pacote['peerDependencies']            = ['chart.js' => '^4.4.0'],
        'chart.min.js em site/mapa/'       => $arquivos[]                            = 'chart.min.js',
    };

    $reprovas = publicacaoConferirDependenciasDoSite($pacote, $arquivos);

    if ($nomeado === null) {
        expect($reprovas)->toBe([]);

        return;
    }

    expect($reprovas)->not->toBe([])
        ->and(implode("\n", $reprovas))->toContain(", {$nomeado}");
})->with([
    'publicados'                                => ['publicados', null],
    'cópia com d3 nas de execução'              => ['d3 nas de execução', 'd3'],
    'cópia com @antv/g6 nas de desenvolvimento' => ['@antv/g6 nas de desenvolvimento', '@antv/g6'],
    'cópia com chart.js em peerDependencies'    => ['chart.js em peerDependencies', 'chart.js'],
    'cópia de site/mapa/ com chart.min.js'      => ['chart.min.js em site/mapa/', 'chart.min.js'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Lê site/, que não viaja no pacote.');

it('[CT-25] o mapa nasce no build do workflow, e o conferidor do mapa roda depois do de acessibilidade, com a mesma prévia, antes de o artefato sair', function (string $arquivo, string $job, bool $enviaArtefato): void {
    $pacoteDoSite = json_decode((string) file_get_contents(base_path('site/package.json')), true, flags: JSON_THROW_ON_ERROR);

    // Então o build gera os diagramas; o conferidor do mapa roda depois do de acessibilidade, com a mesma prévia, e antes do envio
    expect(publicacaoConferirBuildDoMapa(Yaml::parseFile(base_path($arquivo)), $job, $pacoteDoSite, $enviaArtefato))->toBe([])
        ->and(base_path('site/verifica-mapa.mjs'))->toBeFile();

    // E nenhum HTML de diagrama do mapa está versionado no repositório
    $slugs = publicacaoSlugsDoMapa();

    expect($slugs)->not->toBeEmpty()
        ->and(publicacaoHtmlsDoMapaVersionados(arquivosVersionados(), $slugs))->toBe([]);
})->with([
    '.github/workflows/pages.yml'            => ['.github/workflows/pages.yml', 'build', true],
    'o job site de .github/workflows/ci.yml' => ['.github/workflows/ci.yml', 'site', false],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Lê .github/, site/ e o diretório das páginas do site e invoca o git, que não viajam no pacote.');

it('[CT-42] a falha do conferidor do mapa reprova o job, e o build do Pages só roda na main', function (string $arquivo, string $job, string $mundo): void {
    $workflow  = Yaml::parseFile(base_path($arquivo));
    $exigeMain = $arquivo === '.github/workflows/pages.yml';
    $publicado = publicacaoConferirFalhaDoMapa($workflow, $job, $exigeMain);
    $indice    = publicacaoIndiceDoPasso((array) ($workflow['jobs'][$job]['steps'] ?? []), 'verifica-mapa.mjs');

    expect($indice)->not->toBeNull("o job {$job} de {$arquivo} não roda o conferidor do mapa");

    if ($mundo === 'publicado') {
        expect($publicado)->toBe([]);

        return;
    }

    $copia = $workflow;
    $passo = (string) ($workflow['jobs'][$job]['steps'][$indice]['name'] ?? '');

    if ($mundo === 'continue-on-error') {
        $copia['jobs'][$job]['steps'][$indice]['continue-on-error'] = true;
        [$nomeado, $traco]                                          = ["o passo \"{$passo}\"", 'continue-on-error'];
    } elseif ($mundo === '|| true') {
        $run = (string) $workflow['jobs'][$job]['steps'][$indice]['run'];

        $copia['jobs'][$job]['steps'][$indice]['run'] = str_replace('node verifica-mapa.mjs', 'node verifica-mapa.mjs || true', $run);

        expect($copia['jobs'][$job]['steps'][$indice]['run'])->not->toBe($run);
        [$nomeado, $traco] = ["o passo \"{$passo}\"", '||'];
    } else {
        unset($copia['jobs'][$job]['if']);
        [$nomeado, $traco] = ["o job \"{$job}\"", 'refs/heads/main'];
    }

    $novas = array_values(array_diff(publicacaoConferirFalhaDoMapa($copia, $job, $exigeMain), $publicado));

    expect($novas)->not->toBe([])
        ->and(implode("\n", $novas))->toContain($nomeado)->toContain($traco);
})->with([
    'pages.yml publicado'                                                  => ['.github/workflows/pages.yml', 'build', 'publicado'],
    'o job site de ci.yml publicado'                                       => ['.github/workflows/ci.yml', 'site', 'publicado'],
    'pages.yml, cópia com continue-on-error: true no passo do conferidor'  => ['.github/workflows/pages.yml', 'build', 'continue-on-error'],
    'o job site de ci.yml, cópia que roda node verifica-mapa.mjs || true'  => ['.github/workflows/ci.yml', 'site', '|| true'],
    'pages.yml, cópia em que o job build não tem a condição da main'       => ['.github/workflows/pages.yml', 'build', 'sem main'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Lê .github/, que não viaja no pacote.');

it('[CT-26] o README de cada idioma leva ao mapa do mesmo idioma', function (string $readme, string $idioma): void {
    $outro  = $idioma === 'pt' ? 'en' : 'pt';
    $links  = publicacaoLinksDoMapa(publicacaoSemComentarioHtml((string) file_get_contents(base_path($readme))));
    $pagina = publicacaoSiteDoKit()."{$idioma}/referencia/mapa-interativo";
    $noSite = array_filter($links[$idioma], fn (string $endereco): bool => preg_match('~^'.preg_quote($pagina, '~').'(?:/|\.html)(?:[?#].*)?$~u', $endereco) === 1);

    // Então há um link para a página do mapa em <idioma> no site do kit
    expect($noSite)->not->toBeEmpty("{$readme} não leva a {$pagina}/ (nem ao stub .html)");

    // E nenhum link para a página do mapa do outro idioma
    expect($links[$outro])->toBe([]);
})->with([
    'README.md'    => ['README.md', 'pt'],
    'README.en.md' => ['README.en.md', 'en'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Lê os READMEs, que não viajam no pacote.');

it('[CT-27] todo bloco Mermaid da base continua no arquivo em que estava, nos dois idiomas', function (): void {
    $esperados = array_map(fn (int $numero): string => sprintf('DG-%02d', $numero), range(1, 20));

    foreach (['pt', 'en'] as $idioma) {
        $readme = $idioma === 'en' ? 'README.en.md' : 'README.md';
        $base   = publicacaoInventarioMermaidCongelado($idioma);

        // O literal congelado é o que blocosMermaidDe() acha no commit da base — prova, nunca fonte, e só onde o commit existe
        if (publicacaoTemOCommitDaBase()) {
            $ordenar = fn (array $itens): array => collect($itens)->sortBy(fn (array $item): string => "{$item['id']} {$item['arquivo']}")->values()->all();

            expect($ordenar(publicacaoInventarioMermaidDaBase($idioma)))->toBe($ordenar($base));
        }

        // Dado o inventário da base: DG-01 a DG-20 nos arquivos de docs/, e o DG-01 também no README
        $idsEmDocs = array_values(array_unique(array_column(array_filter($base, fn (array $item): bool => str_starts_with($item['arquivo'], 'docs/')), 'id')));
        sort($idsEmDocs);

        expect($idsEmDocs)->toBe($esperados)
            ->and(in_array(['id' => 'DG-01', 'arquivo' => $readme], $base, true))->toBeTrue("[{$idioma}] o DG-01 não estava no {$readme} da base");

        // E entre eles estão o DG-11, no catálogo de features, e o DG-19, em "Desenvolvendo o kit"
        expect(in_array(['id' => 'DG-11', 'arquivo' => "docs/{$idioma}/operacao/roteiro-de-features.md"], $base, true))->toBeTrue("[{$idioma}] DG-11 fora do catálogo na base")
            ->and(in_array(['id' => 'DG-19', 'arquivo' => "docs/{$idioma}/operacao/desenvolvendo-o-kit.md"], $base, true))->toBeTrue("[{$idioma}] DG-19 fora de Desenvolvendo o kit na base");

        // Então cada DG da base está no mesmo arquivo da base, fora de comentário HTML
        $atuais = array_values(array_map(
            fn (array $bloco): array => ['id' => $bloco['idCatalogo'], 'arquivo' => $bloco['arquivo']],
            array_filter(blocosMermaidDaArvore($idioma), fn (array $bloco): bool => $bloco['idCatalogo'] !== null && ! $bloco['dentroDeComentarioHtml']),
        ));

        foreach ($base as $item) {
            expect(in_array($item, $atuais, true))->toBeTrue("[{$idioma}] o {$item['id']} saiu de {$item['arquivo']}, ou foi para dentro de comentário HTML");
        }
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'Lê o diretório das páginas do site e os READMEs e invoca o git, que não viajam no pacote.');

it('[CT-28] todo pacote instalado está na referência e chega ao catálogo, ou à lista dos sem feature própria; e a referência só lista pacote instalado', function (string $idioma, string $mundo, ?string $nomeado): void {
    $referencia = (string) file_get_contents(base_path("docs/{$idioma}/referencia/pacotes-instalados.md"));
    $catalogo   = (string) file_get_contents(base_path("docs/{$idioma}/operacao/roteiro-de-features.md"));
    $semFeature = publicacaoPacotesSemFeaturePropria();
    $comPeca    = publicacaoPacotesComPecaRegistrada();
    $instalados = publicacaoPacotesInstalados();
    $publicada  = publicacaoConferirPacotes($instalados, $referencia, $catalogo, $semFeature, $comPeca);

    if ($mundo === 'publicada') {
        expect($publicada['reprovas'])->toBe([])
            ->and(count($publicada['conferidos']))->toBeGreaterThanOrEqual(91)
            ->and($publicada['conferidos'])->toContain('@laravel/multiplex');

        return;
    }

    $original = [$referencia, $catalogo];

    match ($mundo) {
        'sem a linha do dock'           => $referencia = publicacaoSemLinhas($referencia, '~^\|.*hoceineel/filament-quick-action-dock~u'),
        'com vendor-inexistente/plugin' => $referencia .= "\n| [vendor-inexistente/plugin](https://packagist.org/packages/vendor-inexistente/plugin) | plugin que os manifestos não declaram |\n",
        'sem a F-nn do Health'          => $catalogo                                               = publicacaoSemLinhas($catalogo, '~^\|\s*F-\d+[a-z]?\s*\|[^|]*\|[^|]*`shuvroroy/filament-spatie-laravel-health`~u'),
        'tinker sem motivo'             => $semFeature['laravel/tinker']                           = '',
        'tinker com travessão'          => $semFeature['laravel/tinker']                           = '—',
        'tinker com não se aplica'      => $semFeature['laravel/tinker']                           = 'não se aplica',
        'Health na lista'               => $semFeature['shuvroroy/filament-spatie-laravel-health'] = 'o plugin só mostra o resultado dos checks que o spatie/laravel-health roda',
    };

    // As premissas da cópia: o dock está instalado; o Health tem o plugin no /infra; a cópia mudou o que diz mudar
    if ($mundo === 'sem a linha do dock') {
        expect($instalados)->toContain('hoceineel/filament-quick-action-dock')
            ->and($referencia)->not->toBe($original[0]);
    }

    if ($mundo === 'sem a F-nn do Health') {
        expect($catalogo)->not->toBe($original[1]);
    }

    if ($mundo === 'Health na lista') {
        expect($comPeca)->toHaveKey('shuvroroy/filament-spatie-laravel-health')
            ->and(array_filter($comPeca['shuvroroy/filament-spatie-laravel-health'] ?? [], fn (string $peca): bool => str_starts_with($peca, 'infra: ')))->not->toBeEmpty();
    }

    $novas = array_values(array_diff(publicacaoConferirPacotes($instalados, $referencia, $catalogo, $semFeature, $comPeca)['reprovas'], $publicada['reprovas']));

    expect($novas)->not->toBe([])
        ->and(implode("\n", $novas))->toContain("{$nomeado}:");
})->with([
    'pt publicada'                                                   => ['pt', 'publicada', null],
    'en publicada'                                                   => ['en', 'publicada', null],
    'pt, cópia da referência sem a linha do dock'                    => ['pt', 'sem a linha do dock', 'hoceineel/filament-quick-action-dock'],
    'pt, cópia que lista vendor-inexistente/plugin'                  => ['pt', 'com vendor-inexistente/plugin', 'vendor-inexistente/plugin'],
    'pt, cópia do catálogo sem a F-nn do Health'                     => ['pt', 'sem a F-nn do Health', 'shuvroroy/filament-spatie-laravel-health'],
    'pt, cópia da lista com laravel/tinker sem o motivo'             => ['pt', 'tinker sem motivo', 'laravel/tinker'],
    'pt, cópia da lista com laravel/tinker e o motivo —'             => ['pt', 'tinker com travessão', 'laravel/tinker'],
    'pt, cópia da lista com laravel/tinker e "não se aplica"'        => ['pt', 'tinker com não se aplica', 'laravel/tinker'],
    'pt, cópia da lista com o Health, cujo plugin o /infra registra' => ['pt', 'Health na lista', 'shuvroroy/filament-spatie-laravel-health'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Lê o diretório das páginas do site, que não viaja no pacote.');

it('[CT-34] a página do mapa de cada idioma declara a casca do comparativo em inglês, os dois defeitos tolerados do archify e o limite da varredura', function (string $idioma): void {
    $pagina = (string) preg_replace('/\s+/u', ' ', publicacaoSemComentarioHtml((string) file_get_contents(base_path("docs/{$idioma}/referencia/mapa-interativo.md"))));

    // Então a casca em inglês; E o piso da varredura e a completude sem garantia além dele; E o comparativo sem cards nem passaporte
    foreach (publicacaoDeclaracoesDaPaginaDoMapa($idioma) as $entao => $padrao) {
        expect(preg_match($padrao, $pagina))->toBe(1, "[{$idioma}] a página não diz que {$entao}");
    }

    // E a página nomeia os dois defeitos tolerados pela regra e pelo elemento: `nested-interactive` no
    // `<svg>` raiz, e `color-contrast` na contagem de grupo do índice de nós (`.node-outline-heading > small`)
    $ausentes = array_values(array_filter(
        ['nested-interactive', '<svg>', 'color-contrast', '.node-outline-heading > small'],
        fn (string $nome): bool => ! str_contains($pagina, $nome),
    ));

    expect($ausentes)->toBe([], "[{$idioma}] a página não nomeia: ".implode(', ', $ausentes));
})->with(['pt', 'en'])->skip(fn (): bool => ! naArvoreDoKit(), 'Lê o diretório das páginas do site, que não viaja no pacote.');

/* ---------------------------------------------------------------- R39 e R40 (costura G5, forma de efeito) */

/**
 * A trava da G5: o caminho (absoluto, existente ou não) fica fora da árvore do kit. A árvore tem o
 * archify baixado em `site/.archify`, e o gerador, sob o defeito do achado A2, apagaria a pasta de
 * `--saida`; por isso nenhuma ferramenta roda, grava ou apaga ali.
 */
function publicacaoForaDoKit(string $caminho): void
{
    $normalizar = fn (string $texto): string => rtrim(strtolower(str_replace('\\', '/', $texto)), '/').'/';

    if (str_starts_with($normalizar($caminho), $normalizar((string) realpath(base_path())))) {
        throw new RuntimeException("A G5 não toca a árvore do kit, e o caminho {$caminho} está nela.");
    }
}

/** Uma pasta temporária nova, no diretório temporário do sistema: a raiz do clone, da saída e do link. */
function publicacaoPastaTemporaria(): string
{
    $raiz = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR.'mapa-g5-'.bin2hex(random_bytes(6));
    publicacaoForaDoKit($raiz);
    mkdir($raiz);

    return $raiz;
}

/** Apaga o caminho fora do kit; o link (a junção, no Windows) some sozinho, sem que a remoção entre no alvo. */
function publicacaoApagarPasta(string $caminho): void
{
    publicacaoForaDoKit($caminho);

    if (is_link($caminho)) {
        PHP_OS_FAMILY === 'Windows' ? @rmdir($caminho) : @unlink($caminho);

        return;
    }

    if (is_dir($caminho)) {
        foreach (array_diff(scandir($caminho) ?: [], ['.', '..']) as $nome) {
            publicacaoApagarPasta($caminho.DIRECTORY_SEPARATOR.$nome);
        }

        @rmdir($caminho);

        return;
    }

    if (file_exists($caminho)) {
        @chmod($caminho, 0666);
        @unlink($caminho);
    }
}

/** O link para a pasta: uma junção no Windows (`mklink /J`, sem privilégio), ou um link simbólico no Linux. */
function publicacaoLigarPasta(string $alvo, string $elo): void
{
    publicacaoForaDoKit($alvo);
    publicacaoForaDoKit($elo);

    if (PHP_OS_FAMILY === 'Windows') {
        Process::timeout(30)->run(['cmd', '/c', 'mklink', '/J', $elo, $alvo])->throw();

        return;
    }

    symlink($alvo, $elo);
}

/** Tira o link, e só ele: o `rmdir` de uma junção não entra no alvo. */
function publicacaoDesligarPasta(string $elo): void
{
    publicacaoForaDoKit($elo);

    if (file_exists($elo) || is_link($elo)) {
        PHP_OS_FAMILY === 'Windows' ? @rmdir($elo) : @unlink($elo);
    }
}

/**
 * O clone descartável da G5, dentro da pasta temporária: `git clone --shared` esparso do kit, com a
 * raiz e `site/mapa`, no commit do teste. O `Dado` vira asserção aqui: o commit é o do kit; o clone
 * não tem `site/.archify`; tem o `site/.gitignore` (sem ele, o `.archify` criado sujaria o
 * `git status` pelo motivo errado); e o download está bloqueado — com `GIT_ALLOW_PROTOCOL=file`, o
 * https é recusado na hora, sem rede.
 *
 * @return array{kit: string, site: string}
 */
function publicacaoCloneDescartavel(string $raiz): array
{
    publicacaoForaDoKit($raiz);

    $kit    = $raiz.DIRECTORY_SEPARATOR.'kit';
    $site   = $kit.DIRECTORY_SEPARATOR.'site';
    $commit = trim(Process::path(base_path())->run(['git', 'rev-parse', 'HEAD'])->throw()->output());

    Process::path($raiz)->timeout(120)->run(['git', 'clone', '--quiet', '--shared', '--sparse', base_path(), 'kit'])->throw();
    Process::path($kit)->timeout(120)->run(['git', 'sparse-checkout', 'add', 'site/mapa'])->throw();

    $bloqueio = Process::path($kit)->env(['GIT_ALLOW_PROTOCOL' => 'file'])->timeout(30)
        ->run(['git', 'ls-remote', 'https://github.com/tt-a1i/archify.git']);

    expect(trim(Process::path($kit)->run(['git', 'rev-parse', 'HEAD'])->throw()->output()))->toBe($commit, 'o clone não está no commit do kit')
        ->and(file_exists($site.DIRECTORY_SEPARATOR.'.archify'))->toBeFalse('o clone nasceu com site/.archify')
        ->and(is_file($site.DIRECTORY_SEPARATOR.'.gitignore'))->toBeTrue('o clone não tem o site/.gitignore')
        ->and(array_values(array_filter(
            ['gerar.mjs', 'posicionar.mjs', 'archify.mjs', 'pecas.json', 'ligacoes.json', 'diagramas/app-resumo-sem.json'],
            fn (string $arquivo): bool => ! is_file($site.DIRECTORY_SEPARATOR.'mapa'.DIRECTORY_SEPARATOR.$arquivo),
        )))->toBe([], 'o clone não tem as ferramentas e o modelo do mapa')
        ->and($bloqueio->successful())->toBeFalse('o download por https não está bloqueado no clone')
        ->and(str_contains($bloqueio->errorOutput(), "transport 'https' not allowed"))->toBeTrue('o bloqueio não é o do GIT_ALLOW_PROTOCOL: '.$bloqueio->errorOutput());

    return ['kit' => $kit, 'site' => $site];
}

/**
 * Roda uma ferramenta do mapa com o download do archify bloqueado, sem `throw()`: o código e a saída
 * são o que o caso afirma. O diretório e todo argumento que é caminho passam pela trava da G5.
 *
 * @param  list<string>  $comando
 * @return array{codigo: ?int, stdout: string, stderr: string}
 */
function publicacaoRodarNoClone(string $cwd, array $comando): array
{
    $caminhos = array_filter($comando, fn (string $argumento): bool => $argumento === '.' || preg_match('~[\\\\/]~', $argumento) === 1);

    foreach ([$cwd, ...$caminhos] as $caminho) {
        $absoluto = preg_match('~^([a-zA-Z]:)?[\\\\/]~', $caminho) === 1 ? $caminho : $cwd.DIRECTORY_SEPARATOR.$caminho;
        publicacaoForaDoKit(realpath($absoluto) ?: $absoluto);
    }

    $rodada = Process::path($cwd)->env(['GIT_ALLOW_PROTOCOL' => 'file'])->timeout(120)->run($comando);

    return ['codigo' => $rodada->exitCode(), 'stdout' => $rodada->output(), 'stderr' => $rodada->errorOutput()];
}

/** A impressão dos bytes do arquivo; `null` quando ele sumiu. */
function publicacaoBytes(string $arquivo): ?string
{
    return is_file($arquivo) ? hash_file('sha256', $arquivo) : null;
}

/**
 * O HTML do mapa escrito nas pastas de saída: os `.html` de `pt/` e de `en/` de cada uma.
 *
 * @param  list<string>  $pastas
 * @return list<string>
 */
function publicacaoHtmlsDoMapa(array $pastas): array
{
    $htmls = [];

    foreach ($pastas as $pasta) {
        foreach (['pt', 'en'] as $idioma) {
            $htmls = [...$htmls, ...(glob($pasta.DIRECTORY_SEPARATOR.$idioma.DIRECTORY_SEPARATOR.'*.html') ?: [])];
        }
    }

    return $htmls;
}

/**
 * A decisão das recusas da G5 (CT-48, CT-49 e CT-50): a ferramenta termina com código diferente de
 * zero, com uma linha `[mapa]` que nomeia o culpado — comparado sem caixa e com `/` — e diz o porquê
 * na mesma linha, e nenhum dos efeitos aconteceu. Devolve as reprovas, com a saída literal, para que
 * todo `Então` apareça numa rodada só.
 *
 * @param  array{codigo: ?int, stdout: string, stderr: string}  $rodada
 * @param  list<string>  $culpado  os nomes do culpado, todos na mesma linha; o que começa por `~` é um padrão, aplicado à linha crua
 * @param  ?string  $porQue  o padrão do porquê, na mesma linha do culpado
 * @param  array<string, bool>  $efeitos  o efeito → aconteceu
 * @return list<string>
 */
function publicacaoConferirRecusa(array $rodada, array $culpado, ?string $porQue, array $efeitos): array
{
    $normalizar = fn (string $texto): string => rtrim(strtolower(str_replace('\\', '/', $texto)), '/');
    $saida      = $rodada['stdout']."\n".$rodada['stderr'];
    $literal    = 'saída literal, código '.var_export($rodada['codigo'], true).': '.mb_substr(trim($saida), 0, 900);
    $nomeiam    = array_filter(
        preg_split('/\R/u', $saida),
        fn (string $linha): bool => str_contains($linha, '[mapa]')
            && array_filter($culpado, fn (string $nome): bool => str_starts_with($nome, '~') ? preg_match($nome, $linha) !== 1 : ! str_contains($normalizar($linha), $normalizar($nome))) === [],
    );

    $reprovas = [];

    if ($rodada['codigo'] === 0) {
        $reprovas[] = "terminou com código 0 — {$literal}";
    }

    if ($nomeiam === []) {
        $reprovas[] = 'nenhuma linha [mapa] nomeia '.implode(' e ', $culpado)." — {$literal}";
    } elseif ($porQue !== null && array_filter($nomeiam, fn (string $linha): bool => preg_match($porQue, $linha) === 1) === []) {
        $reprovas[] = "a linha [mapa] que nomeia o culpado não diz o porquê ({$porQue}) — {$literal}";
    }

    foreach ($efeitos as $efeito => $aconteceu) {
        if ($aconteceu) {
            $reprovas[] = $efeito;
        }
    }

    return $reprovas;
}

/**
 * A decisão do CT-51: as chamadas pelo caminho real e pelo link terminam com o mesmo código e a mesma
 * saída, fora o caminho (nas formas com `\`, com `/` e de `file:///`), e o código de cada uma é
 * diferente de zero.
 *
 * @param  array<string, array{codigo: ?int, stdout: string, stderr: string}>  $rodadas  a via → a rodada
 * @param  list<string>  $caminhos  os caminhos que a comparação ignora: o real e o do link
 * @return list<string>
 */
function publicacaoConferirMesmaChamada(array $rodadas, array $caminhos): array
{
    $barras     = fn (string $texto): string => str_replace('\\', '/', $texto);
    $semCaminho = fn (string $texto): string => trim(str_ireplace(array_map($barras, $caminhos), '<site>', $barras($texto)));
    $vistas     = array_map(
        fn (array $rodada): array => ['codigo' => $rodada['codigo'], 'stdout' => $semCaminho($rodada['stdout']), 'stderr' => $semCaminho($rodada['stderr'])],
        $rodadas,
    );

    $reprovas = [];

    if (count(array_unique(array_map('serialize', $vistas))) !== 1) {
        $reprovas[] = 'as chamadas não terminam com o mesmo código e a mesma saída: '.json_encode($vistas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    foreach ($vistas as $via => $vista) {
        if ($vista['codigo'] === 0) {
            $reprovas[] = "pelo {$via}, o gerador terminou com código 0";
        }
    }

    return $reprovas;
}

it('[CT-48] com --saida e sem slug, o gerador recusa, antes de qualquer efeito, a pasta que tem o que ele não gerou, e não apaga nada', function (string $pasta): void {
    $raiz = publicacaoPastaTemporaria();

    try {
        // Dado o node do ambiente, lido pela versão, e um clone descartável no commit, sem site/.archify e com o download bloqueado
        expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+$/');
        $clone  = publicacaoCloneDescartavel($raiz);
        $status = fn (): string => trim(Process::path($clone['kit'])->run(['git', 'status', '--porcelain', '--untracked-files=all', '--', 'site'])->throw()->output());

        // E a pasta de saída: a do autor, com o leia-me.txt e nenhum HTML do mapa; ou `.`, a própria site/ do clone
        $saida  = $pasta === 'autor' ? $raiz.DIRECTORY_SEPARATOR.'saida' : $clone['site'];
        $leiaMe = $saida.DIRECTORY_SEPARATOR.'leia-me.txt';

        if ($pasta === 'autor') {
            mkdir($saida);
            file_put_contents($leiaMe, "Rascunho do autor, que o gerador não gerou.\n");
        }

        $bytesDoLeiaMe = publicacaoBytes($leiaMe);

        expect(publicacaoHtmlsDoMapa([$saida]))->toBe([], 'a pasta de saída já tinha HTML do mapa')
            ->and($pasta === 'autor' ? $bytesDoLeiaMe !== null : $status() === '')->toBeTrue('a pasta de saída não nasceu como o Exemplo diz');

        // Quando o autor roda, de site/ do clone, node mapa/gerar.mjs --saida com essa pasta e sem slug
        $rodada = publicacaoRodarNoClone($clone['site'], ['node', 'mapa/gerar.mjs', '--saida', $pasta === 'autor' ? $saida : '.']);

        $statusDepois = $status();
        $htmls        = publicacaoHtmlsDoMapa([$saida, $clone['site'].DIRECTORY_SEPARATOR.'dist'.DIRECTORY_SEPARATOR.'mapa']);

        // Então código diferente de zero, com a mensagem [mapa] que nomeia a pasta e diz por que a recusa;
        // E <intacto> continua, com os mesmos bytes; E nada foi baixado nem gerado
        expect(publicacaoConferirRecusa($rodada, [$saida], '/arquivo|n[ãa]o\s+(o\s+)?ger|leia-me\.txt|\bfiles?\b|not\s+generated/iu', [
            'o leia-me.txt do autor não continua com os mesmos bytes'                          => $pasta === 'autor' && publicacaoBytes($leiaMe) !== $bytesDoLeiaMe,
            "o git status de site/ do clone não está limpo: {$statusDepois}"                   => $pasta === 'site' && $statusDepois !== '',
            'o clone ganhou site/.archify: o download do archify foi tentado antes da recusa'  => file_exists($clone['site'].DIRECTORY_SEPARATOR.'.archify'),
            'o gerador escreveu HTML do mapa: '.implode(', ', $htmls)                          => $htmls !== [],
        ]))->toBe([]);
    } finally {
        publicacaoApagarPasta($raiz);
    }
})->with([
    'uma pasta temporária com o leia-me.txt do autor e nenhum HTML do mapa'  => ['autor'],
    '`.`, a própria site/ do clone'                                          => ['site'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Clona o kit e roda site/mapa/, que não viajam no pacote.');

/**
 * Os nomes que a linha `[mapa]` do CT-49 tem de trazer para nomear o argumento: a opção desconhecida com borda, para
 * que `--tentativas` não passe por `--tentativa`, e junto a opção que existe; o slug ausente pela palavra `slug`; o slug
 * a mais com borda.
 *
 * @return list<string>
 */
function publicacaoCulpadoNoCt49(string $argumento): array
{
    return match ($argumento) {
        '--tentativa'    => ['~(?<![\w-])--tentativa(?![\w-])~u', '--tentativas'],
        'o slug'         => ['slug'],
        'app-resumo-com' => ['~(?<![\w-])app-resumo-com(?![\w-])~u'],
        default          => [$argumento],
    };
}

/** O padrão do "o que falta" do CT-49, pelas palavras da coluna do `04`, na mesma linha `[mapa]` que nomeia o argumento. */
function publicacaoOQueFaltaNoCt49(string $oQueFalta, string $argumento): string
{
    $positivo = '(?=.*(inteiro|integer|n[úu]mero|number))(?=.*(positiv|maior\s+que\s+(0|zero)|>\s*0|≥\s*1|>=\s*1|pelo\s+menos\s+1|at\s+least\s+1|greater\s+than\s+(0|zero)))';

    return match ($oQueFalta) {
        'o valor que ele espera'                                                                => $argumento === '--saida' ? '/pasta|diret[óo]rio|folder|directory/iu' : '/^'.$positivo.'/iu',
        'que a opção não existe, e a que existe (`--tentativas`)'                               => '/desconhecid|n[ãa]o\s+existe|inexistente|n[ãa]o\s+reconhec|unknown|unrecognized|not\s+exist/iu',
        'que ele falta, com o uso da ferramenta'                                                => '/falt|ausente|missing|obrigat|required|sem\s+(o\s+)?slug|n[ãa]o\s+(foi\s+)?(informad|passad|indicad)|precisa|espera|expects?|needs?/iu',
        'que o posicionador recebe um slug só'                                                  => '/um\s+slug\s+s[óo]|um\s+s[óo]\s+slug|s[óo]\s+um\s+slug|apenas\s+um|somente\s+um|um\s+(slug|diagrama)\s+(por|de\s+cada)\s+vez|one\s+slug|only\s+one|a\s+single|a\s+mais|extra|demais|too\s+many/iu',
        'que ele aparece mais de uma vez, ou que o segundo valor, `0`, não é inteiro positivo'  => '/mais\s+de\s+uma\s+vez|repetid|duplicad|duas\s+vezes|more\s+than\s+once|repeated|duplicate|^'.$positivo.'(?=.*(?<![\d.])0(?![\d.]))/iu',
    };
}

it('[CT-49] o argumento sem valor válido, desconhecido, a mais ou ausente é recusado antes de qualquer efeito, com a mensagem [mapa] que nomeia o argumento', function (array $comando, string $argumento, string $oQueFalta): void {
    $raiz = publicacaoPastaTemporaria();

    try {
        // Dado o node do ambiente, lido pela versão, e um clone descartável no commit, sem site/.archify e com o download bloqueado
        expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+$/');
        $clone     = publicacaoCloneDescartavel($raiz);
        $diagramas = $clone['site'].DIRECTORY_SEPARATOR.'mapa'.DIRECTORY_SEPARATOR.'diagramas';
        $antes     = publicacaoFotoDaPasta($diagramas);

        expect($antes)->toHaveKey('app-resumo-sem.json');

        // Quando o autor roda, de site/ do clone, o comando
        $rodada = publicacaoRodarNoClone($clone['site'], $comando);
        $saida  = strtolower(str_replace('\\', '/', $rodada['stdout']."\n".$rodada['stderr']));
        $mudou  = publicacaoDiferencaDaFoto($antes, publicacaoFotoDaPasta($diagramas));

        // Então código diferente de zero, com a mensagem [mapa] que nomeia o argumento e diz o que falta, na mesma linha
        // (e, sem slug, o uso da ferramenta na saída); E nada foi baixado nem gravado: o clone continua sem site/.archify,
        // e os arquivos de diagrama do clone têm os mesmos bytes
        expect(publicacaoConferirRecusa($rodada, publicacaoCulpadoNoCt49($argumento), publicacaoOQueFaltaNoCt49($oQueFalta, $argumento), [
            'a saída não traz o uso da ferramenta (mapa/posicionar.mjs <slug>)'               => $oQueFalta === 'que ele falta, com o uso da ferramenta' && ! str_contains($saida, 'posicionar.mjs <slug>'),
            'o clone ganhou site/.archify: o download do archify foi tentado antes da recusa' => file_exists($clone['site'].DIRECTORY_SEPARATOR.'.archify'),
            "os arquivos de diagrama do clone não têm os mesmos bytes: {$mudou}"              => $mudou !== '',
        ]))->toBe([]);
    } finally {
        publicacaoApagarPasta($raiz);
    }
})->with([
    'gerador: pasta ausente'                                                      => [['node', 'mapa/gerar.mjs', '--saida'], '--saida', 'o valor que ele espera'],
    'posicionador: número ausente'                                                => [['node', 'mapa/posicionar.mjs', 'app-resumo-sem', '--tentativas'], '--tentativas', 'o valor que ele espera'],
    'posicionador: valor não numérico'                                            => [['node', 'mapa/posicionar.mjs', 'app-resumo-sem', '--tentativas', 'muitas'], '--tentativas', 'o valor que ele espera'],
    'posicionador: zero, abaixo do inteiro positivo (Q24)'                        => [['node', 'mapa/posicionar.mjs', 'app-resumo-sem', '--tentativas', '0'], '--tentativas', 'o valor que ele espera'],
    'posicionador: opção desconhecida, com um slug real (step 11, QA-04)'         => [['node', 'mapa/posicionar.mjs', 'app-resumo-sem', '--tentativa', '0'], '--tentativa', 'que a opção não existe, e a que existe (`--tentativas`)'],
    'posicionador: sem slug (step 11, QA-04)'                                     => [['node', 'mapa/posicionar.mjs'], 'o slug', 'que ele falta, com o uso da ferramenta'],
    'posicionador: um slug a mais (step 11, QA-04)'                               => [['node', 'mapa/posicionar.mjs', 'app-resumo-sem', 'app-resumo-com'], 'app-resumo-com', 'que o posicionador recebe um slug só'],
    'posicionador: opção repetida, com o segundo valor inválido (step 11, QA-04)' => [['node', 'mapa/posicionar.mjs', 'app-resumo-sem', '--tentativas', '5', '--tentativas', '0'], '--tentativas', 'que ele aparece mais de uma vez, ou que o segundo valor, `0`, não é inteiro positivo'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Clona o kit e roda site/mapa/, que não viajam no pacote.');

/**
 * Os bytes de uma pasta: cada caminho relativo (com `/`) → a impressão do arquivo, ou `pasta`. Vazio
 * quando a pasta não existe.
 *
 * @return array<string, string>
 */
function publicacaoFotoDaPasta(string $pasta): array
{
    if (! is_dir($pasta)) {
        return [];
    }

    $foto = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        $foto[str_replace('\\', '/', substr($item->getPathname(), strlen($pasta) + 1))] = $item->isDir() ? 'pasta' : (string) hash_file('sha256', $item->getPathname());
    }

    ksort($foto);

    return $foto;
}

/**
 * Os caminhos que mudaram entre duas fotos da mesma pasta: o que sumiu, o que nasceu e o que mudou de bytes.
 *
 * @param  array<string, string>  $antes
 * @param  array<string, string>  $depois
 */
function publicacaoDiferencaDaFoto(array $antes, array $depois): string
{
    return implode(', ', array_keys(array_diff_assoc($antes, $depois) + array_diff_assoc($depois, $antes)));
}

it('[CT-50] o arquivo de entrada que a ferramenta não pode usar — JSON quebrado, peça que não está no modelo, ou prova que não resolve — é recusado antes de qualquer efeito, com a mensagem [mapa] que nomeia o culpado', function (string $arquivo, string $defeito, array $comando, array $culpado): void {
    $raiz    = publicacaoPastaTemporaria();
    $intrusa = 'App\\Support\\PecaQueNaoExiste';

    try {
        // Dado o node do ambiente, lido pela versão, e um clone descartável no commit, sem site/.archify e com o download bloqueado
        expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+$/');
        $clone     = publicacaoCloneDescartavel($raiz);
        $mapa      = $clone['site'].DIRECTORY_SEPARATOR.'mapa';
        $saida     = $raiz.DIRECTORY_SEPARATOR.'saida';
        $dist      = $clone['site'].DIRECTORY_SEPARATOR.'dist'.DIRECTORY_SEPARATOR.'mapa';
        $naRevisao = fn (string $citado): string => Process::path($clone['kit'])->run(['git', 'show', 'HEAD:'.$citado])->throw()->output();
        $umSo      = function (array $itens, Closure $casa, string $oQue): object {
            $achados = array_values(array_filter($itens, $casa));

            expect($achados)->toHaveCount(1, "o clone não tem exatamente um item: {$oQue}");

            return $achados[0];
        };

        // Os arquivos de entrada do clone: os de diagrama e os do modelo
        $entradas = fn (): array => [
            ...publicacaoFotoDaPasta($mapa.DIRECTORY_SEPARATOR.'diagramas'),
            'pecas.json'    => (string) publicacaoBytes($mapa.DIRECTORY_SEPARATOR.'pecas.json'),
            'ligacoes.json' => (string) publicacaoBytes($mapa.DIRECTORY_SEPARATOR.'ligacoes.json'),
        ];
        mkdir($saida);

        // A pasta de saída do comando: vazia, ou com o pt/ e o en/ de uma geração anterior — só HTML do mapa, a pasta que a P-14 aceita
        if (in_array('{saida-anterior}', $comando, true)) {
            foreach (['pt', 'en'] as $idioma) {
                mkdir($saida.DIRECTORY_SEPARATOR.$idioma);
                file_put_contents($saida.DIRECTORY_SEPARATOR.$idioma.DIRECTORY_SEPARATOR.'app-resumo-sem.html', "<!doctype html>\n<html lang=\"{$idioma}\"><head><title>app-resumo-sem</title></head><body>geração anterior</body></html>\n");
            }
        }

        // E, no clone, o arquivo com o defeito
        $caminho = $clone['kit'].DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $arquivo);
        publicacaoForaDoKit($caminho);

        if ($defeito === 'com JSON quebrado') {
            // O existente sem o último fecho; o diagrama a mais, cortado. O texto quebrado não traz o nome do arquivo
            // nem o [mapa], para que o eco do JSON.parse não passe por nomeação.
            $quebrado = is_file($caminho) ? substr(rtrim((string) file_get_contents($caminho)), 0, -1) : '{"tipo": "architecture"';

            expect(json_decode($quebrado))->toBeNull('o JSON da cópia não está quebrado')
                ->and(str_contains($quebrado, basename($arquivo)) || str_contains($quebrado, '[mapa]'))->toBeFalse('o JSON quebrado ecoaria o nome do arquivo ou o [mapa]');

            file_put_contents($caminho, $quebrado);
        } else {
            $json = json_decode((string) file_get_contents($caminho), flags: JSON_THROW_ON_ERROR);

            if (str_starts_with($defeito, 'com a peça fora do modelo')) {
                // A peça que não é peça do modelo, no diagrama construído: entre as peças desenhadas, ou como membro de um agrupamento
                $modelo = json_decode((string) file_get_contents($mapa.DIRECTORY_SEPARATOR.'pecas.json'), flags: JSON_THROW_ON_ERROR);

                expect(array_values(array_filter($modelo, fn (object $peca): bool => $peca->id === $intrusa)))->toBe([], 'a peça da linha é peça do modelo')
                    ->and($json->construtor ?? null)->toBe('mapa', 'o diagrama da linha não é construído do modelo');

                if (str_ends_with($defeito, 'entre as peças desenhadas')) {
                    $json->pecas[] = (object) ['id' => $intrusa, 'row' => max(array_map(fn (object $peca): int => (int) ($peca->row ?? 0), $json->pecas)) + 1, 'col' => 0];
                } else {
                    $json->agrupamentos[0]->membros[] = $intrusa;
                }
            } elseif ($defeito === 'com a primeira mensagem sem prova') {
                expect($json->messages[0]->prova ?? null)->not->toBeNull('a primeira mensagem do diagrama escrito à mão não tem prova');

                unset($json->messages[0]->prova);
            } else {
                // A âncora trocada: onde ela mora, o arquivo que ela cita, o trecho novo, e se ele só aparece em comentário
                [$ancora, $citado, $trecho, $soEmComentario] = match ($defeito) {
                    "com a prova da primeira mensagem trocada por ->id('financeiro'), que não está no arquivo" => [
                        $json->messages[0]->prova, 'app/Providers/Filament/AdminPanelProvider.php', "->id('financeiro')", false,
                    ],
                    'com a evidência de AppServiceProvider registra BloquearSessaoComRetorno trocada por um trecho só de comentário' => [
                        $umSo($json, fn (object $ligacao): bool => $ligacao->de === 'App\\Providers\\AppServiceProvider' && $ligacao->para === 'App\\Http\\Middleware\\BloquearSessaoComRetorno' && $ligacao->verbo === 'registra', 'a ligação AppServiceProvider registra BloquearSessaoComRetorno')->evidencia,
                        'app/Providers/AppServiceProvider.php', 'pelo mesmo caminho: o plugin registra', true,
                    ],
                    'com a evidência do UserResource do /admin trocada por class UserResource extends Model, que não está no arquivo' => [
                        $umSo($json, fn (object $peca): bool => $peca->id === 'App\\Filament\\Admin\\Resources\\Users\\UserResource', 'a peça UserResource do /admin')->evidencia[0],
                        'app/Filament/Admin/Resources/Users/UserResource.php', 'class UserResource extends Model', false,
                    ],
                    'com a evidência de painel:app registra ConvitesRecebidos trocada por ConvitesRecebidos::make(), que não está no arquivo' => [
                        $umSo($json, fn (object $ligacao): bool => $ligacao->de === 'painel:app' && $ligacao->para === 'App\\Filament\\App\\Pages\\ConvitesRecebidos' && $ligacao->verbo === 'registra', 'a ligação painel:app registra ConvitesRecebidos')->evidencia,
                        'app/Providers/Filament/AppPanelProvider.php', 'ConvitesRecebidos::make(),', false,
                    ],
                };
                $texto = $naRevisao($citado);

                expect($ancora->arquivo)->toBe($citado, 'a âncora da linha não cita o arquivo que o 04 nomeia')
                    ->and(str_contains($texto, $trecho))->toBe($soEmComentario, "o trecho novo não está no arquivo citado como o 04 diz: {$trecho}")
                    ->and(str_contains(codigoSemComentario($texto), $trecho))->toBeFalse("o trecho novo está no código, fora de comentário: {$trecho}");

                $ancora->trecho = $trecho;
            }

            file_put_contents($caminho, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        }

        // O diagrama que desenha a ligação: qualquer um dos que têm as duas pontas dela, entre eles o kit-acesso-e-autenticacao, que o 04 nomeia;
        // e o app-resumo-sem, que o posicionador projeta, tem o ConvitesRecebidos
        if (str_contains($defeito, 'BloquearSessaoComRetorno')) {
            $desenham = array_values(array_map(
                fn (string $diagrama): string => basename($diagrama, '.json'),
                array_filter(glob($mapa.DIRECTORY_SEPARATOR.'diagramas'.DIRECTORY_SEPARATOR.'*.json') ?: [], fn (string $diagrama): bool => str_contains($texto = (string) file_get_contents($diagrama), 'BloquearSessaoComRetorno') && str_contains($texto, 'Providers\\\\AppServiceProvider')),
            ));

            expect($desenham)->toContain('kit-acesso-e-autenticacao');

            $culpado = array_map(fn (string $nome): string => $nome === '{o diagrama que desenha a ligação}' ? '~(?:'.implode('|', array_map(fn (string $slug): string => preg_quote($slug, '~'), $desenham)).')(?![a-z0-9-])~' : $nome, $culpado);
        }

        if (str_contains($defeito, 'ConvitesRecebidos')) {
            expect((string) file_get_contents($mapa.DIRECTORY_SEPARATOR.'diagramas'.DIRECTORY_SEPARATOR.'app-resumo-sem.json'))->toContain('ConvitesRecebidos');
        }

        $htmlsAntes    = publicacaoHtmlsDoMapa([$saida, $dist]);
        $saidaAntes    = publicacaoFotoDaPasta($saida);
        $entradasAntes = $entradas();

        expect(count($entradasAntes))->toBeGreaterThan(2, 'a foto dos arquivos de entrada do clone não tem os diagramas');

        // Quando o autor roda, de site/ do clone, o comando
        $rodada = publicacaoRodarNoClone($clone['site'], array_map(fn (string $parte): string => in_array($parte, ['{saida}', '{saida-anterior}'], true) ? $saida : $parte, $comando));

        $htmlsNovos      = array_values(array_diff(publicacaoHtmlsDoMapa([$saida, $dist]), $htmlsAntes));
        $saidaMudou      = publicacaoDiferencaDaFoto($saidaAntes, publicacaoFotoDaPasta($saida));
        $entradasMudaram = publicacaoDiferencaDaFoto($entradasAntes, $entradas());

        // Então código diferente de zero, com a mensagem [mapa] que nomeia o culpado — todos os nomes dele, na mesma linha;
        // E nada foi baixado, gerado nem gravado: sem site/.archify, nenhum HTML do mapa escrito, e a pasta de saída e os
        // arquivos de entrada do clone — os de diagrama e os do modelo — com os mesmos bytes de antes do comando
        expect(publicacaoConferirRecusa($rodada, $culpado, null, [
            'o clone ganhou site/.archify: o download do archify foi tentado antes da recusa'                             => file_exists($clone['site'].DIRECTORY_SEPARATOR.'.archify'),
            'a ferramenta escreveu HTML do mapa: '.implode(', ', $htmlsNovos)                                             => $htmlsNovos !== [],
            "a pasta de saída não tem os mesmos bytes: {$saidaMudou}"                                                     => $saidaMudou !== '',
            "os arquivos de entrada do clone (os de diagrama e os do modelo) não têm os mesmos bytes: {$entradasMudaram}" => $entradasMudaram !== '',
        ]))->toBe([]);
    } finally {
        publicacaoApagarPasta($raiz);
    }
})->with([
    'gerador: diagrama quebrado, mesmo fora do pedido'                => ['site/mapa/diagramas/zz-quebrado.json', 'com JSON quebrado', ['node', 'mapa/gerar.mjs', '--saida', '{saida}', 'app-resumo-sem'], ['zz-quebrado.json']],
    'gerador: modelo de peças quebrado'                               => ['site/mapa/pecas.json', 'com JSON quebrado', ['node', 'mapa/gerar.mjs', '--saida', '{saida}', 'app-resumo-sem'], ['pecas.json']],
    'gerador: modelo de ligações quebrado'                            => ['site/mapa/ligacoes.json', 'com JSON quebrado', ['node', 'mapa/gerar.mjs', '--saida', '{saida}', 'app-resumo-sem'], ['ligacoes.json']],
    'posicionador: diagrama quebrado'                                 => ['site/mapa/diagramas/app-resumo-sem.json', 'com JSON quebrado', ['node', 'mapa/posicionar.mjs', 'app-resumo-sem'], ['app-resumo-sem.json']],
    'gerador: peça fora do modelo (re-revisão)'                       => ['site/mapa/diagramas/admin-acesso-e-autenticacao.json', 'com a peça fora do modelo entre as peças desenhadas', ['node', 'mapa/gerar.mjs', '--saida', '{saida-anterior}'], ['PecaQueNaoExiste', 'admin-acesso-e-autenticacao']],
    'gerador: membro de agrupamento fora do modelo (re-revisão)'      => ['site/mapa/diagramas/admin-resumo.json', 'com a peça fora do modelo como membro de um agrupamento', ['node', 'mapa/gerar.mjs', '--saida', '{saida-anterior}'], ['PecaQueNaoExiste', 'admin-resumo']],
    'posicionador: diagrama irmão quebrado (re-revisão)'              => ['site/mapa/diagramas/zz-quebrado.json', 'com JSON quebrado', ['node', 'mapa/posicionar.mjs', 'app-resumo-sem'], ['zz-quebrado.json']],
    'gerador: prova de ligação que não está no arquivo (step 10)'     => ['site/mapa/diagramas/requisicao-autenticada.json', "com a prova da primeira mensagem trocada por ->id('financeiro'), que não está no arquivo", ['node', 'mapa/gerar.mjs', '--saida', '{saida-anterior}'], ["->id('financeiro')", 'AdminPanelProvider.php', '~requisicao-autenticada(?![a-z0-9-])~']],
    'gerador: evidência de ligação só em comentário (step 10)'        => ['site/mapa/ligacoes.json', 'com a evidência de AppServiceProvider registra BloquearSessaoComRetorno trocada por um trecho só de comentário', ['node', 'mapa/gerar.mjs', '--saida', '{saida-anterior}'], ['AppServiceProvider.php', '{o diagrama que desenha a ligação}']],
    'gerador: ligação sem prova (step 10)'                            => ['site/mapa/diagramas/requisicao-autenticada.json', 'com a primeira mensagem sem prova', ['node', 'mapa/gerar.mjs', '--saida', '{saida-anterior}'], ['navegador', 'setuppanel', '~requisicao-autenticada(?![a-z0-9-])~']],
    'gerador: evidência de peça que não está no arquivo (step 10)'    => ['site/mapa/pecas.json', 'com a evidência do UserResource do /admin trocada por class UserResource extends Model, que não está no arquivo', ['node', 'mapa/gerar.mjs', '--saida', '{saida-anterior}'], ['~App(?:\\\\{1,2}|-)Filament(?:\\\\{1,2}|-)Admin(?:\\\\{1,2}|-)Resources(?:\\\\{1,2}|-)Users(?:\\\\{1,2}|-)UserResource~', 'UserResource.php']],
    'posicionador: peça fora do modelo (step 10)'                     => ['site/mapa/diagramas/app-resumo-sem.json', 'com a peça fora do modelo entre as peças desenhadas', ['node', 'mapa/posicionar.mjs', 'app-resumo-sem'], ['PecaQueNaoExiste', '~app-resumo-sem(?![a-z0-9-])~']],
    'posicionador: modelo quebrado (step 10)'                         => ['site/mapa/pecas.json', 'com JSON quebrado', ['node', 'mapa/posicionar.mjs', 'app-resumo-sem'], ['pecas.json']],
    'posicionador: prova que não resolve (step 10)'                   => ['site/mapa/ligacoes.json', 'com a evidência de painel:app registra ConvitesRecebidos trocada por ConvitesRecebidos::make(), que não está no arquivo', ['node', 'mapa/posicionar.mjs', 'app-resumo-sem'], ['AppPanelProvider.php', '~app-resumo-sem(?![a-z0-9-])~']],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Clona o kit e roda site/mapa/, que não viajam no pacote.');

it('[CT-51] o gerador chamado por um caminho que passa por um link faz o mesmo que pelo caminho real, e não termina com 0 calado', function (): void {
    $raiz = publicacaoPastaTemporaria();
    $elo  = $raiz.DIRECTORY_SEPARATOR.'elo';

    try {
        // Dado o node do ambiente, lido pela versão, e um clone descartável no commit, sem site/.archify e com o download bloqueado
        expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+$/');
        $clone = publicacaoCloneDescartavel($raiz);

        // E um link para site/ do clone: uma junção no Windows (mklink /J, sem privilégio), ou um link simbólico no Linux
        publicacaoLigarPasta($clone['site'], $elo);

        expect(is_file($elo.DIRECTORY_SEPARATOR.'mapa'.DIRECTORY_SEPARATOR.'gerar.mjs'))->toBeTrue('o link não leva a site/ do clone');

        // Quando o autor pede mapa/gerar.mjs --saida com uma pasta temporária vazia e o slug diagrama-que-nao-existe,
        // uma vez pelo caminho real de site/ do clone e outra pelo link, com o site/.archify apagado entre as duas
        $saida   = $raiz.DIRECTORY_SEPARATOR.'saida';
        $rodadas = [];

        foreach (['caminho real' => $clone['site'], 'link' => $elo] as $via => $site) {
            publicacaoApagarPasta($clone['site'].DIRECTORY_SEPARATOR.'.archify');
            publicacaoApagarPasta($saida);
            mkdir($saida);

            $rodadas[$via] = publicacaoRodarNoClone($raiz, ['node', $site.DIRECTORY_SEPARATOR.'mapa'.DIRECTORY_SEPARATOR.'gerar.mjs', '--saida', $saida, 'diagrama-que-nao-existe']);
        }

        // Então as duas terminam com o mesmo código e a mesma saída, fora o caminho; E o código das duas é diferente de zero
        expect(publicacaoConferirMesmaChamada($rodadas, [$clone['site'], $elo]))->toBe([]);
    } finally {
        publicacaoDesligarPasta($elo);
        publicacaoApagarPasta($raiz);
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'Clona o kit e roda site/mapa/, que não viajam no pacote.');
