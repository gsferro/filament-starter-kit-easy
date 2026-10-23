<?php

use App\Console\Commands\KitUpdate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * A guarda do roteiro de atualização por agente: o que o CI confere sobre
 * `wikis/atualizacoes/`, sobre o passo do workflow de release e sobre a redação do checklist.
 *
 * Casos em `wikis/specs/feat/roteiro-de-atualizacao/roteiro-de-atualizacao-por-agente/04-casos-de-teste.md`.
 * Os cenários de processo (CP-01 a CP-04) exigem um agente em sessão nova e ficam fora da suíte.
 *
 * Os detectores são funções puras sobre texto: o mesmo detector roda sobre a árvore real (CT-01) e
 * sobre fixtures corrompidas com um defeito por linha (os demais casos). Cada detector devolve a
 * lista de falhas, e lista vazia é "aprova".
 *
 * Todo caso leva `naArvoreDoKit()`: `CHANGELOG.md`, `.github/`, `docs/` e `wikis/specs/` são
 * `export-ignore`, e este arquivo viaja. Leitura de arquivo só acontece dentro do corpo do caso.
 */

/*
|--------------------------------------------------------------------------
| Constantes e fixtures
|--------------------------------------------------------------------------
*/

const RA_REPO = 'gsferro/filament-starter-kit-easy';

const RA_PADRAO = 'https://raw.githubusercontent.com/'.RA_REPO.'/v{versão}/wikis/atualizacoes/v{versão}.md';

const RA_INICIO = '<!-- roteiro-de-atualizacao:inicio -->';

const RA_FIM = '<!-- roteiro-de-atualizacao:fim -->';

const RA_FRASE_LER = 'Leia também o arquivo na main.';

const RA_FRASE_PREFERIR = 'Se o arquivo da main registrar a revisão do roteiro, prefira o arquivo da main.';

const RA_FRASE_PARAR = 'Na dúvida, pare e pergunte ao dono.';

/** As nove seções do contrato, na ordem em que o agente as lê. */
const RA_SECOES = [
    '## Identificação',
    '## Antes de começar',
    '## O que muda nesta versão',
    '## Cuidados',
    '## Decisões do dono do projeto',
    '## Passos',
    '## Como conferir',
    '## Quando parar',
    '## Relatório final',
];

const RA_PULO_FORA_DA_ARVORE = 'Lê arquivos que não viajam (CHANGELOG.md, .github/, docs/, wikis/specs/ são export-ignore): fora da árvore do kit não há o que conferir.';

function raNl(string $texto): string
{
    return str_replace("\r\n", "\n", $texto);
}

function raLer(string $relativo): string
{
    $caminho = base_path($relativo);

    return is_file($caminho) ? (string) file_get_contents($caminho) : '';
}

function raUrlRaw(string $ref, string $versaoDoArquivo, string $repo = RA_REPO): string
{
    return "https://raw.githubusercontent.com/{$repo}/{$ref}/wikis/atualizacoes/v{$versaoDoArquivo}.md";
}

/** @return array<string, string> */
function raBlocos(string $texto): array
{
    $partes = preg_split('/^(?=## (?!#))/mu', raNl($texto)) ?: [];
    $blocos = [];

    foreach ($partes as $i => $parte) {
        if ($i === 0 && ! str_starts_with($parte, '## ')) {
            $blocos['__preambulo'] = $parte;

            continue;
        }

        $blocos[rtrim((string) strtok($parte, "\n"))] = $parte;
    }

    return $blocos;
}

/** @param  array<string, string>  $blocos */
function raMontar(array $blocos): string
{
    return implode('', $blocos);
}

function raSemSecao(string $texto, string $titulo): string
{
    $blocos = raBlocos($texto);
    unset($blocos[$titulo]);

    return raMontar($blocos);
}

function raComCorpo(string $texto, string $titulo, string $corpo): string
{
    $blocos          = raBlocos($texto);
    $blocos[$titulo] = "{$titulo}\n\n".trim($corpo)."\n\n";

    return raMontar($blocos);
}

function raAcrescentar(string $texto, string $titulo, string $extra): string
{
    $blocos          = raBlocos($texto);
    $blocos[$titulo] = rtrim($blocos[$titulo])."\n\n".trim($extra)."\n\n";

    return raMontar($blocos);
}

function raMover(string $texto, string $titulo, string $depoisDe): string
{
    $blocos = raBlocos($texto);
    $movido = $blocos[$titulo];
    unset($blocos[$titulo]);

    $novo = [];

    foreach ($blocos as $chave => $bloco) {
        $novo[$chave] = $bloco;

        if ($chave === $depoisDe) {
            $novo[$titulo] = $movido;
        }
    }

    return raMontar($novo);
}

/** O corpo de uma seção `## ` (até o próximo `## `, com os seus `###`); `null` se o título não existe. */
function raSecao(string $texto, string $titulo): ?string
{
    $bloco = raBlocos($texto)[$titulo] ?? null;

    if ($bloco === null) {
        return null;
    }

    return (string) substr($bloco, strlen($titulo));
}

/** @return array<string, int> posição (índice no documento) do primeiro título de cada seção */
function raPosicoes(string $texto): array
{
    return array_flip(array_keys(array_filter(raBlocos($texto), static fn (string $chave): bool => $chave !== '__preambulo', ARRAY_FILTER_USE_KEY)));
}

function raDecisao(string $titulo, ?string $pergunta, ?string $recomendacao, ?string $semResposta): string
{
    $linhas = ["### {$titulo}", ''];

    if ($pergunta !== null) {
        $linhas[] = "Pergunta: {$pergunta}";
    }

    if ($recomendacao !== null) {
        $linhas[] = "Recomendação da release: {$recomendacao}";
    }

    if ($semResposta !== null) {
        $linhas[] = "Sem resposta: {$semResposta}";
    }

    return implode("\n", $linhas)."\n\n";
}

function raDecisaoDeConflito(string $semResposta = 'manter a versão do dono do arquivo e registrar o conflito no relatório final.'): string
{
    return raDecisao(
        'Arquivo do kit editado por você',
        'O kit:update encontrou conflito num arquivo do kit que você editou. Deseja sobrescrever o arquivo?',
        'manter a versão do dono.',
        $semResposta,
    );
}

function raDecisoesPadrao(): string
{
    return raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', 'desligado, como na v0.52.0.', 'manter desligado.')
        .raDecisao('Tutorial guiado', 'Deseja manter o tutorial guiado ligado?', 'ligado.', 'manter ligado.')
        .raDecisaoDeConflito();
}

/**
 * Um roteiro conforme da versão. Opções: `origem`, `link` (`''` tira a linha), `marca`, `padrao`
 * (`''` tira a linha), `semFrases` (`ler`, `preferir`, `parar`).
 *
 * @param  array<string, mixed>  $o
 */
function raRoteiro(string $versao = '0.53.0', array $o = []): string
{
    $origem = $o['origem'] ?? match ($versao) {
        '0.53.0', '0.52.1' => '0.52.0',
        '0.52.0'           => '0.51.2',
        '0.51.2'           => '0.51.1',
        default            => '0.0.1',
    };
    $link   = array_key_exists('link', $o) ? $o['link'] : raUrlRaw($versao === '0.52.0' ? 'main' : "v{$versao}", $versao);
    $padrao = array_key_exists('padrao', $o) ? $o['padrao'] : RA_PADRAO;
    $frases = array_diff_key(
        ['ler' => RA_FRASE_LER, 'preferir' => RA_FRASE_PREFERIR, 'parar' => RA_FRASE_PARAR],
        array_flip($o['semFrases'] ?? []),
    );

    $identificacao = "- Origem: v{$origem}\n- Destino: v{$versao}\n";

    if ($link !== '') {
        $identificacao .= "- Link: {$link}\n";
    }

    if (isset($o['marca'])) {
        $identificacao .= "- {$o['marca']}\n";
    }

    if ($padrao !== '') {
        $identificacao .= "- Padrão do link da próxima versão: `{$padrao}`\n";
    }

    $identificacao .= "\n".implode(' ', $frases);

    $corpos = [
        '## Identificação'               => $identificacao,
        '## Antes de começar'            => "Confira que a versão instalada do kit é a origem (v{$origem}) com `php artisan kit:info`. Se não for, pare: este roteiro não se aplica.\n\nFaça backup do banco antes de começar.",
        '## O que muda nesta versão'     => "A v{$versao} traz o tutorial guiado nos três painéis, na passagem de v{$origem} para v{$versao}.",
        '## Cuidados'                    => "Os cuidados da v{$versao}: faça backup antes de começar. Nunca rode `php artisan migrate:fresh` neste projeto.",
        '## Decisões do dono do projeto' => raDecisoesPadrao(),
        '## Passos'                      => "```bash\nphp artisan kit:update --from v{$origem}\nphp artisan migrate --force\n```",
        '## Como conferir'               => "```bash\nphp artisan kit:info\n```\n\nA versão do kit deve ser v{$versao}.",
        '## Quando parar'                => 'Pare e pergunte ao dono se algum passo falhar, ou se a versão instalada não for a origem.',
        '## Relatório final'             => 'Registre cada decisão do dono e o default aplicado quando ele não respondeu.',
    ];

    $texto = "# Roteiro de atualização — v{$versao}\n\n";

    foreach ($corpos as $titulo => $corpo) {
        $texto .= "{$titulo}\n\n".trim($corpo)."\n\n";
    }

    return $texto;
}

/**
 * @param  list<string>  $versoes  `'Unreleased'` vira `## [Unreleased]`
 * @param  list<string>  $revisadas  versões com o registro de revisão do roteiro
 * @param  list<string>|null  $defaults  itens de "### Defaults mudados"; `null` omite a subseção
 */
function raChangelog(array $versoes, array $revisadas = [], ?array $defaults = ['chat de IA desligado', 'tutorial guiado ligado']): string
{
    $texto = "# Changelog\n\n";

    foreach ($versoes as $versao) {
        if ($versao === 'Unreleased') {
            $texto .= "## [Unreleased]\n\n";

            continue;
        }

        $texto .= "## [{$versao}] - 2026-10-01\n\n### Adicionado\n\n- novidade da {$versao}\n\n";

        if (in_array($versao, $revisadas, true)) {
            $texto .= "Roteiro de atualização revisado em 2026-11-02: passo de tenancy.\n\n";
        }

        if ($defaults !== null) {
            $texto .= "### Defaults mudados\n\n".implode("\n", array_map(static fn (string $d): string => "- {$d}", $defaults))."\n\n";
        }
    }

    return $texto;
}

function raAgentes(string $copia, bool $comInicio = true, bool $comFim = true): string
{
    return "# Agentes e skills\n\nTexto do documento.\n\n## Atualizar o projeto pelo agente\n\n"
        .($comInicio ? RA_INICIO."\n" : '')
        .$copia
        .($comFim ? "\n".RA_FIM : '')
        ."\n\nResto do documento.\n";
}

/*
|--------------------------------------------------------------------------
| Detectores: funções puras sobre texto. Lista vazia = aprova.
|--------------------------------------------------------------------------
*/

/** @return list<string> */
function raVersoesDoChangelog(string $changelog): array
{
    preg_match_all('/^## \[(\d+\.\d+\.\d+)\]/m', raNl($changelog), $m);

    return $m[1];
}

function raAnterior(string $changelog, string $marcador): ?string
{
    $versoes = raVersoesDoChangelog($changelog);
    $posicao = array_search($marcador, $versoes, true);

    return $posicao === false ? null : ($versoes[$posicao + 1] ?? null);
}

function raSecaoDoChangelog(string $changelog, string $versao): ?string
{
    $padrao = '/^## \['.preg_quote($versao, '/').'\][^\n]*\n(.*?)(?=^## \[|\z)/msu';

    return preg_match($padrao, raNl($changelog), $m) === 1 ? $m[1] : null;
}

function raRevisaoRegistrada(string $changelog, string $versao): bool
{
    $secao = raSecaoDoChangelog($changelog, $versao) ?? '';

    return preg_match('/^\s*(?:[-*]\s+)?Roteiro de atualização revisado/mu', $secao) === 1;
}

/** @return list<string>|null itens da lista; `null` se a subseção não existe */
function raDefaultsMudados(string $changelog, string $versao): ?array
{
    $secao = raSecaoDoChangelog($changelog, $versao) ?? '';

    if (preg_match('/^### Defaults mudados[^\n]*\n(.*?)(?=^#{2,3} |\z)/msu', $secao, $m) !== 1) {
        return null;
    }

    preg_match_all('/^\s*[-*]\s+(.+?)\s*$/mu', $m[1], $itens);

    return $itens[1];
}

/** @param  array<string, string>  $arvore  caminho relativo => conteúdo */
function raFalhasDeExistencia(string $marcador, array $arvore, string $changelog): array
{
    if ($marcador === '') {
        return ['o marcador de versão está vazio'];
    }

    $falhas = [];

    foreach (["wikis/atualizacoes/v{$marcador}.md", 'wikis/atualizacoes/README.md'] as $caminho) {
        if (trim($arvore[$caminho] ?? '') === '') {
            $falhas[] = "falta ou está vazio: {$caminho}";
        }
    }

    foreach (raVersoesDoChangelog($changelog) as $versao) {
        $publicadaComRoteiro = version_compare($versao, '0.52.0', '>=') && version_compare($versao, $marcador, '<');

        if ($publicadaComRoteiro && trim($arvore["wikis/atualizacoes/v{$versao}.md"] ?? '') === '') {
            $falhas[] = "roteiro publicado removido: wikis/atualizacoes/v{$versao}.md";
        }
    }

    return $falhas;
}

function raFalhasDeSecaoNoChangelog(string $marcador, string $changelog): array
{
    if ($marcador === '' || raSecaoDoChangelog($changelog, $marcador) !== null) {
        return [];
    }

    return ["o CHANGELOG não tem a seção \"## [{$marcador}]\""];
}

function raFalhasDeContrato(string $roteiro): array
{
    $falhas = [];

    foreach (RA_SECOES as $titulo) {
        $corpo = raSecao($roteiro, $titulo);

        if ($corpo === null) {
            $falhas[] = "falta a seção {$titulo}";
        } elseif (trim($corpo) === '') {
            $falhas[] = "a seção {$titulo} está sem corpo";
        }
    }

    $posicoes = raPosicoes($roteiro);

    foreach (['## Antes de começar', '## Decisões do dono do projeto'] as $antes) {
        if (isset($posicoes[$antes], $posicoes['## Passos']) && $posicoes[$antes] > $posicoes['## Passos']) {
            $falhas[] = "{$antes} vem depois de ## Passos";
        }
    }

    return $falhas;
}

function raFalhasDeCopia(string $roteiro, ?string $anterior): array
{
    if ($anterior === null) {
        return [];
    }

    $falhas = [];

    foreach (['## O que muda nesta versão', '## Cuidados'] as $titulo) {
        $atual = trim(raNl(raSecao($roteiro, $titulo) ?? ''));

        if ($atual !== '' && $atual === trim(raNl(raSecao($anterior, $titulo) ?? ''))) {
            $falhas[] = "a seção {$titulo} repete o corpo do roteiro anterior";
        }
    }

    return $falhas;
}

function raLinhaDaIdentificacao(string $roteiro, string $rotulo): ?string
{
    $corpo  = raSecao($roteiro, '## Identificação') ?? '';
    $padrao = '/^[\s>*\-]*'.preg_quote($rotulo, '/').'\**\s*:\**[ \t]*`?(v?\d+\.\d+\.\d+)/miu';

    return preg_match($padrao, $corpo, $m) === 1 ? $m[1] : null;
}

function raFalhasDeOrigemEDestino(string $roteiro, string $changelog, string $marcador): array
{
    $anterior = raAnterior($changelog, $marcador);

    if ($anterior === null) {
        return ['o CHANGELOG não tem versão publicada abaixo da seção do marcador'];
    }

    $falhas  = [];
    $origem  = raLinhaDaIdentificacao($roteiro, 'Origem');
    $destino = raLinhaDaIdentificacao($roteiro, 'Destino');

    if ($origem !== "v{$anterior}") {
        $falhas[] = 'a origem declarada ('.($origem ?? 'ausente').") não é a versão anterior publicada v{$anterior}";
    }

    if ($destino !== "v{$marcador}") {
        $falhas[] = "o destino esperado é v{$marcador}, e o declarado é ".($destino ?? 'ausente');
    }

    return $falhas;
}

function raLinkDaIdentificacao(string $roteiro): ?string
{
    $corpo = raSecao($roteiro, '## Identificação') ?? '';
    preg_match_all('~https?://[^\s)`>"\'\]]+~u', $corpo, $m);

    foreach ($m[0] as $url) {
        if (! str_contains($url, '{vers')) {
            return rtrim($url, '.,;');
        }
    }

    return null;
}

function raFalhasDoLink(string $roteiro, string $changelog, string $marcador): array
{
    $falhas        = [];
    $revisado      = raRevisaoRegistrada($changelog, $marcador);
    $link          = raLinkDaIdentificacao($roteiro);
    $identificacao = raSecao($roteiro, '## Identificação') ?? '';
    $temMarca      = preg_match('/^[\s>*\-]*Roteiro revisado em [^\s:]+:\s*\S/mu', $identificacao) === 1;

    if ($link === null) {
        $falhas[] = 'a identificação não traz o link do roteiro';
    } elseif (preg_match('~^https://raw\.githubusercontent\.com/([^/]+/[^/]+)/([^/]+)/(.+)$~', $link, $m) !== 1) {
        $falhas[] = "o link não é a URL raw do arquivo: {$link}";
    } else {
        [, $repo, $ref, $caminho] = $m;

        if ($repo !== RA_REPO) {
            $falhas[] = "o link aponta para outro repositório: {$repo}";
        }

        if ($caminho !== "wikis/atualizacoes/v{$marcador}.md") {
            $falhas[] = "o link aponta para outro caminho: {$caminho}";
        }

        $refPermitida = match (true) {
            $revisado              => $ref === 'main' || preg_match('/^[0-9a-f]{40}$/', $ref) === 1,
            $marcador === '0.52.0' => $ref === 'main',
            default                => $ref === "v{$marcador}",
        };

        if (! $refPermitida) {
            $falhas[] = "a ref \"{$ref}\" do link não é permitida para a v{$marcador}";
        }
    }

    if ($revisado && ! $temMarca) {
        $falhas[] = 'a revisão está registrada no CHANGELOG e o roteiro não traz a marca "Roteiro revisado em"';
    }

    if (! $revisado && $temMarca) {
        $falhas[] = 'o roteiro traz a marca de revisão e o CHANGELOG não a registra';
    }

    return $falhas;
}

function raFalhasDoPadrao(string $roteiro): array
{
    preg_match_all('~https?://[^\s)`>"\'\]]+~u', raNl($roteiro), $m);
    $padroes = array_values(array_filter($m[0], static fn (string $url): bool => str_contains($url, '{versão}')));

    if ($padroes === []) {
        return ['o roteiro não ensina o padrão do link da próxima versão'];
    }

    $falhas = [];

    foreach ($padroes as $padrao) {
        if (rtrim($padrao, '.,;') !== RA_PADRAO) {
            $falhas[] = "o padrão do link não é o esperado: {$padrao}";
        }
    }

    return $falhas;
}

/** As três instruções de RQ-32, em português ou inglês. */
function raFalhasDasInstrucoesDoPadrao(string $texto): array
{
    $texto  = raColapsar($texto);
    $falhas = [];

    if (preg_match('/\b(?:ler|leia|lê|confira|conferir|consulte|read|check|open|fetch|compare)\b[^.\n]{0,80}\bmain\b/iu', $texto) !== 1) {
        $falhas[] = 'falta ler também o arquivo na main';
    }

    if (preg_match('/(?:prefir\w*|prefer\w*)[^.\n]{0,100}\bmain\b|\bmain\b[^.\n]{0,100}(?:prefir\w*|prefer\w*)/iu', $texto) !== 1) {
        $falhas[] = 'falta preferir o arquivo da main quando ele registrar a revisão';
    }

    if (preg_match('/d[úu]vida[^.\n]{0,60}\bpar\w+|\bpar\w+[^.\n]{0,60}d[úu]vida|(?:doubt|unsure|unclear)[^.\n]{0,80}\bstop\w*|\bstop\w*[^.\n]{0,80}(?:doubt|unsure|unclear)/iu', $texto) !== 1) {
        $falhas[] = 'falta parar na dúvida';
    }

    return $falhas;
}

/** @return array<string, string> título => bloco da decisão, na ordem do documento */
function raDecisoes(string $roteiro): array
{
    $corpo    = raSecao($roteiro, '## Decisões do dono do projeto') ?? '';
    $decisoes = [];

    foreach (preg_split('/^(?=### )/mu', $corpo) ?: [] as $parte) {
        if (str_starts_with($parte, '### ')) {
            $decisoes[trim((string) strtok($parte, "\n"))] = $parte;
        }
    }

    return $decisoes;
}

function raRotulo(string $bloco, string $rotulo): ?string
{
    $padrao = '/^[\s>*\-]*'.preg_quote($rotulo, '/').'\**[ \t]*:\**[ \t]*(.*)$/miu';

    return preg_match($padrao, $bloco, $m) === 1 ? trim($m[1]) : null;
}

function raFalhasDeDecisoes(string $roteiro): array
{
    $falhas = [];

    foreach (raDecisoes($roteiro) as $titulo => $bloco) {
        $pergunta = raRotulo($bloco, 'Pergunta');

        if ($pergunta === null || $pergunta === '') {
            $falhas[] = "{$titulo}: falta a Pergunta";
        } elseif (preg_match('/\?[`*_\s]*$/u', $pergunta) !== 1) {
            $falhas[] = "{$titulo}: a Pergunta não termina em \"?\"";
        }

        foreach (['Recomendação da release', 'Sem resposta'] as $rotulo) {
            if ((raRotulo($bloco, $rotulo) ?? '') === '') {
                $falhas[] = "{$titulo}: falta ou está vazio \"{$rotulo}:\"";
            }
        }
    }

    return $falhas;
}

function raFalhasDoRelatorio(string $roteiro): array
{
    $corpo = raSecao($roteiro, '## Relatório final') ?? '';

    return preg_match('/decis[ãõa]/iu', $corpo) === 1 && preg_match('/default/iu', $corpo) === 1
        ? []
        : ['## Relatório final não pede o registro de cada decisão e do default aplicado'];
}

function raFalhasDosDefaults(string $roteiro, string $changelog, string $marcador): array
{
    $itens = raDefaultsMudados($changelog, $marcador);

    if ($itens === null) {
        return ["a seção \"## [{$marcador}]\" do CHANGELOG não tem \"### Defaults mudados\""];
    }

    $perguntas = array_map(
        static fn (string $bloco): string => (string) raRotulo($bloco, 'Pergunta'),
        raDecisoes($roteiro),
    );
    $falhas = [];

    foreach ($itens as $item) {
        if (mb_strtolower(trim($item, ' .')) === 'nenhum') {
            continue;
        }

        $perguntado = array_filter($perguntas, static fn (string $p): bool => mb_stripos($p, $item) !== false);

        if ($perguntado === []) {
            $falhas[] = "o default mudado \"{$item}\" não aparece em nenhuma Pergunta";
        }
    }

    return $falhas;
}

function raFalhasDoConflito(string $roteiro): array
{
    $conflito = array_filter(
        raDecisoes($roteiro),
        static fn (string $bloco, string $titulo): bool => preg_match('/conflito/iu', $titulo.' '.$bloco) === 1,
        ARRAY_FILTER_USE_BOTH,
    );

    if ($conflito === []) {
        return ['falta a decisão de conflito em arquivo do kit editado pelo dono'];
    }

    $semResposta = raRotulo((string) reset($conflito), 'Sem resposta') ?? '';
    $mantem      = preg_match('/vers[ãa]o do dono/iu', $semResposta) === 1 && preg_match('/mant|fica|preserv/iu', $semResposta) === 1;
    $sobrescreve = preg_match('/sobrescrev\w*\s+(?:com|pel[ao]|por)\s+(?:a\s+)?vers[ãa]o\s+do\s+kit/iu', $semResposta) === 1;

    return $mantem && ! $sobrescreve
        ? []
        : ['o "Sem resposta:" da decisão de conflito não mantém a versão do dono'];
}

function raFalhasDeAutossuficiencia(string $roteiro, string $marcador): array
{
    $roteiro       = raNl($roteiro);
    $semComentario = (string) preg_replace('/<!--.*?-->/su', '', $roteiro);
    $falhas        = [];

    $remissoes = [
        'remete ao CHANGELOG'                => '/\b(?:veja|ver|consulte|consultar|leia|ler|confira|conferir|abra|abrir|segundo|conforme)\b[^.\n]{0,60}\bCHANGELOG\b|CHANGELOG\.md/iu',
        'remete a docs/'                     => '~(?<![\w.-])docs/~u',
        'remete a wikis/specs/'              => '~wikis/specs/~u',
        'remete a wikis/agentes-e-skills.md' => '~agentes-e-skills\.md~u',
        'remete ao README'                   => '~(?<![\w/.-])README\.md~u',
        'remete a PR ou issue'               => '~github\.com/[^\s/]+/[^\s/]+/(?:pull|issues)/\d+~u',
        'promete funcionar em todo agente'   => '/(?:funciona|serve|roda|vale|atende)[^.\n]{0,30}(?:qualquer|todo[s]?(?: os)?)\s+agentes?/iu',
    ];

    foreach ($remissoes as $rotulo => $padrao) {
        if (preg_match($padrao, $semComentario) === 1) {
            $falhas[] = $rotulo;
        }
    }

    preg_match_all('~wikis/atualizacoes/v(\d+\.\d+\.\d+)\.md~u', $semComentario, $roteiros);

    foreach (array_unique($roteiros[1]) as $versao) {
        if ($versao !== $marcador) {
            $falhas[] = "remete ao roteiro da v{$versao}";
        }
    }

    $proprio = raLinkDaIdentificacao($roteiro);
    preg_match_all('~https://raw\.githubusercontent\.com/'.preg_quote(RA_REPO, '~').'/[^\s)`>"\'\]]+~u', $semComentario, $urls);

    foreach ($urls[0] as $url) {
        $url       = rtrim($url, '.,;');
        $permitida = str_contains($url, '{vers') || $url === $proprio || str_ends_with($url, "/wikis/atualizacoes/v{$marcador}.md");

        if (! $permitida) {
            $falhas[] = "remete a outro arquivo do repositório: {$url}";
        }
    }

    $invoca = false;
    preg_match_all('/^```[^\n]*\n(.*?)^```/msu', raSecao($roteiro, '## Passos') ?? '', $blocos);

    foreach ($blocos[1] as $bloco) {
        foreach (explode("\n", $bloco) as $linha) {
            if (str_starts_with(ltrim($linha, "\$ \t"), 'php artisan kit:update')) {
                $invoca = true;
            }
        }
    }

    if (! $invoca) {
        $falhas[] = '## Passos não invoca "php artisan kit:update" em bloco de código';
    }

    $preVoo = raSecao($roteiro, '## Antes de começar') ?? '';

    if (preg_match('/vers[ãa]o\s+instalada/iu', $preVoo) !== 1 || preg_match('/\borigem\b/iu', $preVoo) !== 1) {
        $falhas[] = '## Antes de começar não confere a versão instalada contra a origem';
    }

    return $falhas;
}

/** Texto bruto, com comentários (RQ-34): só `## Cuidados` fica de fora. */
function raFalhasDeComandoDestrutivo(string $roteiro): array
{
    $falhas = [];
    $padrao = '/artisan(?:\s+--\S+)*\s+(?:migrate:fresh|db:wipe|migrate:refresh|migrate:reset|migrate:rollback)\b/iu';

    foreach (raBlocos($roteiro) as $titulo => $bloco) {
        if ($titulo !== '## Cuidados' && preg_match($padrao, $bloco, $m) === 1) {
            $falhas[] = "{$titulo} traz comando que destrói dados do banco: {$m[0]}";
        }
    }

    return $falhas;
}

function raFalhasDeCopiaLocal(string $roteiro, string $agentes): array
{
    $agentes = raNl($agentes);
    $inicio  = strpos($agentes, RA_INICIO);
    $fim     = strpos($agentes, RA_FIM);
    $falhas  = [];

    if ($inicio === false) {
        $falhas[] = 'falta o marcador de início da cópia local';
    }

    if ($fim === false) {
        $falhas[] = 'falta o marcador de fim da cópia local';
    }

    if ($falhas !== []) {
        return $falhas;
    }

    $copia = substr($agentes, $inicio + strlen(RA_INICIO), $fim - $inicio - strlen(RA_INICIO));

    return trim($copia) === trim(raNl($roteiro))
        ? []
        : ['a cópia local difere do roteiro da versão marcada'];
}

/**
 * A seção de um documento markdown cujo título casa com o padrão, até o próximo título do mesmo nível
 * ou superior (código cercado não conta como título).
 */
function raSecaoPorTitulo(string $markdown, string $padraoDoTitulo): ?string
{
    $nivel    = null;
    $corpo    = [];
    $emCodigo = false;

    foreach (explode("\n", raNl($markdown)) as $linha) {
        if (str_starts_with(ltrim($linha), '```')) {
            $emCodigo = ! $emCodigo;
        }

        if (! $emCodigo && preg_match('/^(#{1,6}) (.*)$/u', $linha, $m) === 1) {
            if ($nivel !== null && strlen($m[1]) <= $nivel) {
                break;
            }

            if ($nivel === null && preg_match($padraoDoTitulo, $m[2]) === 1) {
                $nivel = strlen($m[1]);
            }
        }

        if ($nivel !== null) {
            $corpo[] = $linha;
        }
    }

    return $nivel === null ? null : implode("\n", $corpo);
}

function raColapsar(string $texto): string
{
    return trim((string) preg_replace('/\s+/u', ' ', str_replace("\n>", ' ', raNl($texto))));
}

/** @param  array<string, string>  $elementos  rótulo => padrão que a seção tem de casar */
function raElementosAusentes(?string $secao, array $elementos): array
{
    if ($secao === null) {
        return ['a seção não existe no checklist'];
    }

    $texto = raColapsar($secao);

    return array_keys(array_filter($elementos, static fn (string $padrao): bool => preg_match($padrao, $texto) !== 1));
}

/** @return array<string, string> */
function raArvoreReal(): array
{
    $arvore = [];

    foreach (glob(base_path('wikis/atualizacoes/*')) ?: [] as $caminho) {
        $arvore['wikis/atualizacoes/'.basename($caminho)] = (string) file_get_contents($caminho);
    }

    return $arvore;
}

/** O bloco `run` do passo de `release.yml` que confere o roteiro; `null` se o passo não existe. */
function raPassoDoWorkflow(): ?string
{
    $yaml = Yaml::parse(raLer('.github/workflows/release.yml'));

    foreach ($yaml['jobs'] ?? [] as $job) {
        foreach ($job['steps'] ?? [] as $passo) {
            $run = $passo['run'] ?? null;

            if (is_string($run) && str_contains($run, 'wikis/atualizacoes')) {
                return $run;
            }
        }
    }

    return null;
}

/**
 * Executa com `bash` o bloco `run` numa árvore temporária; sem `bash` o caso falha.
 *
 * @param  array<string, string>  $pasta  conteúdo de `wikis/atualizacoes/`
 * @return array{0: int, 1: string} código de saída e saída
 */
function raRodarPasso(string $run, string $tag, string $marcador, string $changelog, array $pasta): array
{
    $raiz = sys_get_temp_dir().'/ra-workflow-'.bin2hex(random_bytes(4));

    try {
        File::ensureDirectoryExists("{$raiz}/config");
        File::ensureDirectoryExists("{$raiz}/wikis/atualizacoes");
        file_put_contents("{$raiz}/config/kit.php", "<?php\n\nreturn [\n    'version' => '{$marcador}',\n];\n");
        file_put_contents("{$raiz}/CHANGELOG.md", $changelog);

        foreach ($pasta as $nome => $conteudo) {
            file_put_contents("{$raiz}/wikis/atualizacoes/{$nome}", $conteudo);
        }

        file_put_contents("{$raiz}/passo.sh", str_replace("\r\n", "\n", $run)."\n");

        $resultado = Process::path($raiz)->env(['GITHUB_REF_NAME' => $tag])->run(['bash', 'passo.sh']);

        return [$resultado->exitCode() ?? 1, $resultado->output().$resultado->errorOutput()];
    } finally {
        File::deleteDirectory($raiz);
    }
}

/** @return list<string> as linhas `::error::` da saída que contêm a agulha */
function raErrosQueNomeiam(string $saida, string $agulha): array
{
    return array_values(array_filter(
        explode("\n", str_replace("\r", '', $saida)),
        static fn (string $linha): bool => str_contains($linha, '::error::') && str_contains($linha, $agulha),
    ));
}

/*
|--------------------------------------------------------------------------
| R1 — todo marcador tem roteiro e seção no CHANGELOG
|--------------------------------------------------------------------------
*/

it('[CT-01] a árvore do kit passa em cada detector da guarda', function (string $detector): void {
    $marcador       = (string) config('kit.version');
    $roteiro        = raLer("wikis/atualizacoes/v{$marcador}.md");
    $changelog      = raLer('CHANGELOG.md');
    $anteriorVersao = raAnterior($changelog, $marcador);
    $anterior       = $anteriorVersao === null ? '' : raLer("wikis/atualizacoes/v{$anteriorVersao}.md");

    $falhas = match ($detector) {
        'existência e conteúdo'                      => raFalhasDeExistencia($marcador, raArvoreReal(), $changelog),
        'seção do marcador no CHANGELOG'             => raFalhasDeSecaoNoChangelog($marcador, $changelog),
        'as nove seções, com corpo e ordem'          => raFalhasDeContrato($roteiro),
        'corpo diferente do roteiro anterior'        => raFalhasDeCopia($roteiro, $anterior === '' ? null : $anterior),
        'origem e destino'                           => raFalhasDeOrigemEDestino($roteiro, $changelog, $marcador),
        'link da identificação'                      => raFalhasDoLink($roteiro, $changelog, $marcador),
        'padrão do link e suas instruções'           => [...raFalhasDoPadrao($roteiro), ...raFalhasDasInstrucoesDoPadrao($roteiro)],
        'link e marca depois de revisão'             => raFalhasDoLink($roteiro, $changelog, $marcador),
        'decisões do dono'                           => raFalhasDeDecisoes($roteiro),
        'defaults mudados viraram pergunta'          => raFalhasDosDefaults($roteiro, $changelog, $marcador),
        'relatório final'                            => raFalhasDoRelatorio($roteiro),
        'decisão de conflito em arquivo do dono'     => raFalhasDoConflito($roteiro),
        'autossuficiência e invocação do kit:update' => raFalhasDeAutossuficiencia($roteiro, $marcador),
        'comando destrutivo do banco'                => raFalhasDeComandoDestrutivo($roteiro),
        'cópia local idêntica'                       => raFalhasDeCopiaLocal($roteiro, raLer('wikis/agentes-e-skills.md')),
    };

    expect($roteiro)->not->toBe('', "wikis/atualizacoes/v{$marcador}.md não existe ou está vazio");
    expect($falhas)->toBe([]);
})->with([
    'existência e conteúdo',
    'seção do marcador no CHANGELOG',
    'as nove seções, com corpo e ordem',
    'corpo diferente do roteiro anterior',
    'origem e destino',
    'link da identificação',
    'padrão do link e suas instruções',
    'link e marca depois de revisão',
    'decisões do dono',
    'defaults mudados viraram pergunta',
    'relatório final',
    'decisão de conflito em arquivo do dono',
    'autossuficiência e invocação do kit:update',
    'comando destrutivo do banco',
    'cópia local idêntica',
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/**
 * Inspeção por tokens deste próprio arquivo (Q12 do 04): a condição de pulo de cada caso é só
 * estar fora da árvore do kit, e na árvore do kit a sentinela vale verdadeiro.
 */
it('[CT-27] na árvore do kit, nenhum caso da guarda pula', function (): void {
    expect(naArvoreDoKit())->toBeTrue();
    expect(is_dir(base_path('.github')))->toBeTrue();

    $tokens = array_values(array_filter(
        token_get_all((string) file_get_contents(__FILE__)),
        static fn (mixed $token): bool => ! (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)),
    ));

    $casos = 0;
    $pulos = [];

    foreach ($tokens as $i => $token) {
        $anterior = $tokens[$i - 1] ?? null;
        $proximo  = $tokens[$i + 1] ?? null;

        if (! is_array($token) || $token[0] !== T_STRING || $proximo !== '(') {
            continue;
        }

        $anteriorEhAcesso = is_array($anterior) && in_array($anterior[0], [T_OBJECT_OPERATOR, T_FUNCTION, T_DOUBLE_COLON], true);

        if ($token[1] === 'it' && ! $anteriorEhAcesso) {
            $casos++;
        }

        if ($token[1] === 'skip' && is_array($anterior) && $anterior[0] === T_OBJECT_OPERATOR) {
            $profundidade = 0;
            $argumentos   = '';

            for ($j = $i + 1; $j < count($tokens); $j++) {
                $texto = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];

                if ($texto === '(' && ++$profundidade === 1) {
                    continue;
                }

                if ($texto === ')' && --$profundidade === 0) {
                    break;
                }

                $argumentos .= $texto;
            }

            $pulos[] = $argumentos;
        }
    }

    $condicao = 'fn():bool=>!naArvoreDoKit()';

    expect($casos)->toBeGreaterThan(20);
    expect($pulos)->toHaveCount($casos);

    foreach ($pulos as $argumentos) {
        $soForaDaArvore = $argumentos === $condicao || str_starts_with($argumentos, $condicao.',');
        expect($soForaDaArvore)->toBeTrue("a condição de pulo não é só estar fora da árvore do kit: {$argumentos}");
    }
})->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-28] o roteiro de toda versão publicada desde a v0.52.0 continua na pasta', function (string $pasta, string $resultado): void {
    $changelog = raChangelog(['0.53.0', '0.52.0', '0.51.2']);
    $arvore    = [
        'wikis/atualizacoes/README.md'  => 'contrato',
        'wikis/atualizacoes/v0.53.0.md' => raRoteiro('0.53.0'),
    ];

    if ($pasta === 'v0.52.0 e v0.53.0') {
        $arvore['wikis/atualizacoes/v0.52.0.md'] = raRoteiro('0.52.0');
    }

    $falhas = raFalhasDeExistencia('0.53.0', $arvore, $changelog);

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);

        return;
    }

    expect(implode("\n", $falhas))->toContain('v0.52.0.md');
})->with([
    'conforme'                   => ['v0.52.0 e v0.53.0', 'aprova'],
    'roteiro publicado removido' => ['só v0.53.0', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-02] o commit de versão sem roteiro ou sem seção no CHANGELOG reprova, e diz o que falta', function (string $marcador, string $pasta, array $versoes, array $faltas): void {
    $arvore = match ($pasta) {
        'só a anterior'     => ['wikis/atualizacoes/v0.52.0.md' => raRoteiro('0.52.0')],
        'roteiro vazio'     => ['wikis/atualizacoes/v0.52.0.md' => raRoteiro('0.52.0'), "wikis/atualizacoes/v{$marcador}.md" => ''],
        'pasta inexistente' => [],
        'roteiro conforme'  => ['wikis/atualizacoes/README.md' => 'contrato', 'wikis/atualizacoes/v0.52.0.md' => raRoteiro('0.52.0'), "wikis/atualizacoes/v{$marcador}.md" => raRoteiro('0.53.0')],
    };

    $falhas = [
        ...raFalhasDeExistencia($marcador, $arvore, raChangelog($versoes)),
        ...raFalhasDeSecaoNoChangelog($marcador, raChangelog($versoes)),
    ];

    expect($falhas)->not->toBe([]);

    foreach ($faltas as $falta) {
        expect(implode("\n", $falhas))->toContain($falta);
    }
})->with([
    'release menor, só a anterior'   => ['0.53.0', 'só a anterior', ['0.53.0', '0.52.0'], ['wikis/atualizacoes/v0.53.0.md', 'wikis/atualizacoes/README.md']],
    'hotfix, só a anterior'          => ['0.52.1', 'só a anterior', ['0.52.1', '0.52.0'], ['wikis/atualizacoes/v0.52.1.md', 'wikis/atualizacoes/README.md']],
    'roteiro vazio'                  => ['0.53.0', 'roteiro vazio', ['0.53.0', '0.52.0'], ['wikis/atualizacoes/v0.53.0.md', 'wikis/atualizacoes/README.md']],
    'entrada ausente: pasta'         => ['0.53.0', 'pasta inexistente', ['0.53.0', '0.52.0'], ['wikis/atualizacoes/v0.53.0.md', 'wikis/atualizacoes/README.md']],
    'entrada ausente: marcador'      => ['', 'só a anterior', ['0.52.0', '0.51.2'], ['marcador de versão', 'vazio']],
    'entrada ausente: seção (RQ-29)' => ['0.53.0', 'roteiro conforme', ['0.52.0', '0.51.2'], ['## [0.53.0]']],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R2 — o contrato
|--------------------------------------------------------------------------
*/

it('[CT-03] o roteiro fora do contrato reprova, nomeando a seção', function (string $defeito, string $secao): void {
    $base = raRoteiro('0.53.0');

    $roteiro = match ($defeito) {
        'falta'              => raSemSecao($base, $secao),
        'presença falsa'     => raAcrescentar(raSemSecao($base, '## Cuidados'), '## O que muda nesta versão', 'Os Cuidados estão registrados em outro lugar.'),
        'corpo vazio'        => raComCorpo($base, '## Cuidados', ''),
        'ordem das decisões' => raMover($base, '## Decisões do dono do projeto', '## Passos'),
        'ordem do pré-voo'   => raMover($base, '## Antes de começar', '## Passos'),
    };

    expect(raFalhasDeContrato($base))->toBe([]);
    expect(implode("\n", raFalhasDeContrato($roteiro)))->toContain($secao);
})->with([
    'falta o título Identificação'               => ['falta', '## Identificação'],
    'falta o título Antes de começar'            => ['falta', '## Antes de começar'],
    'falta o título O que muda nesta versão'     => ['falta', '## O que muda nesta versão'],
    'falta o título Cuidados'                    => ['falta', '## Cuidados'],
    'falta o título Decisões do dono do projeto' => ['falta', '## Decisões do dono do projeto'],
    'falta o título Passos'                      => ['falta', '## Passos'],
    'falta o título Como conferir'               => ['falta', '## Como conferir'],
    'falta o título Quando parar'                => ['falta', '## Quando parar'],
    'falta o título Relatório final'             => ['falta', '## Relatório final'],
    'presença falsa'                             => ['presença falsa', '## Cuidados'],
    'corpo vazio'                                => ['corpo vazio', '## Cuidados'],
    'ordem das decisões'                         => ['ordem das decisões', '## Decisões do dono do projeto'],
    'ordem do pré-voo'                           => ['ordem do pré-voo', '## Antes de começar'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-04] o contrato publicado e a guarda exigem as mesmas seções', function (): void {
    $readme = raLer('wikis/atualizacoes/README.md');

    expect($readme)->not->toBe('', 'wikis/atualizacoes/README.md não existe ou está vazio');

    preg_match_all('/`(## [^`]+)`/u', $readme, $m);
    $declaradas = array_values(array_unique(array_map('trim', $m[1])));

    expect($declaradas)->toHaveCount(9)
        ->and($declaradas)->toEqualCanonicalizing(RA_SECOES);
})->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-20] o roteiro que repete o conteúdo da versão anterior reprova', function (string $corpo, string $resultado, ?string $secao): void {
    $anterior = raRoteiro('0.52.0');
    $roteiro  = match ($corpo) {
        'próprio', 'primeiro roteiro'  => raRoteiro('0.53.0'),
        'o que muda copiado, com CRLF' => str_replace("\n", "\r\n", raComCorpo(raRoteiro('0.53.0'), '## O que muda nesta versão', (string) raSecao($anterior, '## O que muda nesta versão'))),
        'cuidados copiado'             => raComCorpo(raRoteiro('0.53.0'), '## Cuidados', (string) raSecao($anterior, '## Cuidados')),
    };

    $falhas = raFalhasDeCopia($roteiro, $corpo === 'primeiro roteiro' ? null : $anterior);

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);

        return;
    }

    expect(implode("\n", $falhas))->toContain((string) $secao);
})->with([
    'corpo próprio'                     => ['próprio', 'aprova', null],
    'O que muda copiado, com CRLF'      => ['o que muda copiado, com CRLF', 'reprova', '## O que muda nesta versão'],
    'Cuidados copiado'                  => ['cuidados copiado', 'reprova', '## Cuidados'],
    'primeiro roteiro: nada a comparar' => ['primeiro roteiro', 'aprova', null],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R3 — origem e destino
|--------------------------------------------------------------------------
*/

it('[CT-05] a origem é exatamente a versão anterior publicada', function (string $origem, string $resultado): void {
    $changelog = raChangelog(['Unreleased', '0.52.0', '0.51.2', '0.51.1']);
    $roteiro   = raRoteiro('0.52.0', ['origem' => ltrim($origem, 'v')]);

    $falhas = raFalhasDeOrigemEDestino($roteiro, $changelog, '0.52.0');

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'a anterior imediata'      => ['v0.51.2', 'aprova'],
    'salto de duas versões'    => ['v0.51.1', 'reprova'],
    'a própria versão destino' => ['v0.52.0', 'reprova'],
    'versão não publicada'     => ['v0.51.3', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-06] a versão anterior sai da sequência publicada, qualquer que seja a forma do histórico', function (string $marcador, array $secoes, string $origem): void {
    $roteiro = raRoteiro($marcador, ['origem' => $origem]);

    expect(raFalhasDeOrigemEDestino($roteiro, raChangelog($secoes), $marcador))->toBe([]);
})->with([
    'com [Unreleased] no topo'       => ['0.52.0', ['Unreleased', '0.52.0', '0.51.2'], '0.51.2'],
    'sem [Unreleased]'               => ['0.52.0', ['0.52.0', '0.51.2', '0.51.1'], '0.51.2'],
    'hotfix'                         => ['0.52.1', ['Unreleased', '0.52.1', '0.52.0'], '0.52.0'],
    'dígito duplo: 0.52.9 < 0.52.10' => ['0.52.10', ['0.52.10', '0.52.9', '0.52.8'], '0.52.9'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-07] o destino declarado diferente do marcador reprova', function (): void {
    $roteiro = str_replace('- Destino: v0.52.0', '- Destino: v0.51.3', raRoteiro('0.52.0'));

    $falhas = raFalhasDeOrigemEDestino($roteiro, raChangelog(['0.52.0', '0.51.2']), '0.52.0');

    expect($falhas)->not->toBe([]);
    expect(implode("\n", $falhas))->toContain('v0.52.0');
})->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R4 e R15 e R16 — links
|--------------------------------------------------------------------------
*/

it('[CT-08] o link da identificação aponta para o arquivo bruto da versão, na ref permitida', function (string $versao, string $url, string $onde, string $resultado): void {
    $roteiro = match ($onde) {
        'identificação' => raRoteiro($versao, ['link' => $url]),
        'passos'        => raAcrescentar(raRoteiro($versao, ['link' => '']), '## Passos', $url),
        'nenhum'        => raRoteiro($versao, ['link' => '']),
    };

    $falhas = raFalhasDoLink($roteiro, raChangelog([$versao, '0.52.0']), $versao);

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'tag destino'                       => ['0.53.0', raUrlRaw('v0.53.0', '0.53.0'), 'identificação', 'aprova'],
    'v0.52.0 na main (P-01)'            => ['0.52.0', raUrlRaw('main', '0.52.0'), 'identificação', 'aprova'],
    '0.52.1 na main'                    => ['0.52.1', raUrlRaw('main', '0.52.1'), 'identificação', 'reprova'],
    'v0.52.0 na tag'                    => ['0.52.0', raUrlRaw('v0.52.0', '0.52.0'), 'identificação', 'reprova'],
    'main fora da v0.52.0, sem revisão' => ['0.53.0', raUrlRaw('main', '0.53.0'), 'identificação', 'reprova'],
    'ref sem o v'                       => ['0.53.0', raUrlRaw('0.53.0', '0.53.0'), 'identificação', 'reprova'],
    'tag de outra versão'               => ['0.53.0', raUrlRaw('v0.52.0', '0.53.0'), 'identificação', 'reprova'],
    'caminho de outra versão'           => ['0.53.0', raUrlRaw('v0.53.0', '0.52.0'), 'identificação', 'reprova'],
    'outro repositório'                 => ['0.53.0', raUrlRaw('v0.53.0', '0.53.0', 'outro/filament-starter-kit-easy'), 'identificação', 'reprova'],
    'página HTML, não o arquivo bruto'  => ['0.53.0', 'https://github.com/'.RA_REPO.'/blob/v0.53.0/wikis/atualizacoes/v0.53.0.md', 'identificação', 'reprova'],
    'fora da identificação'             => ['0.53.0', raUrlRaw('v0.53.0', '0.53.0'), 'passos', 'reprova'],
    'sem link'                          => ['0.53.0', '', 'nenhum', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-09] o padrão do link da próxima versão é conferido como o link', function (string $padrao, string $resultado): void {
    $roteiro = raRoteiro('0.53.0', ['padrao' => $padrao]);

    $falhas = raFalhasDoPadrao($roteiro);

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'conforme'                                         => [RA_PADRAO, 'aprova'],
    'sem padrão: só o link concreto da própria versão' => ['', 'reprova'],
    'ref fixa numa versão'                             => ['https://raw.githubusercontent.com/'.RA_REPO.'/v0.52.0/wikis/atualizacoes/v{versão}.md', 'reprova'],
    'ref fixa na main'                                 => ['https://raw.githubusercontent.com/'.RA_REPO.'/main/wikis/atualizacoes/v{versão}.md', 'reprova'],
    'ref sem o v'                                      => ['https://raw.githubusercontent.com/'.RA_REPO.'/{versão}/wikis/atualizacoes/v{versão}.md', 'reprova'],
    'página HTML'                                      => ['https://github.com/'.RA_REPO.'/blob/v{versão}/wikis/atualizacoes/v{versão}.md', 'reprova'],
    'outro repositório'                                => ['https://raw.githubusercontent.com/outro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md', 'reprova'],
    'caminho fixo numa versão'                         => ['https://raw.githubusercontent.com/'.RA_REPO.'/v{versão}/wikis/atualizacoes/v0.52.0.md', 'reprova'],
    'caminho fora da pasta dos roteiros'               => ['https://raw.githubusercontent.com/'.RA_REPO.'/v{versão}/wikis/v{versão}.md', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-22] o padrão manda conferir a main e parar na dúvida', function (string $falta, string $resultado): void {
    $roteiro = raRoteiro('0.53.0', ['semFrases' => $falta === 'nenhuma' ? [] : [$falta]]);

    $falhas = raFalhasDasInstrucoesDoPadrao($roteiro);

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'nenhum defeito'           => ['nenhuma', 'aprova'],
    'sem conferir a main'      => ['ler', 'reprova'],
    'sem regra de preferência' => ['preferir', 'reprova'],
    'sem parada'               => ['parar', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-21] o link e a marca da revisão andam com o registro no CHANGELOG', function (?string $registro, ?string $marca, string $ref, string $repo, string $resultado): void {
    $marcaTexto = $marca === null ? null : 'Roteiro revisado em 2026-11-02: passo de tenancy';
    $roteiro    = raRoteiro('0.53.0', [
        'marca' => $marcaTexto,
        'link'  => raUrlRaw($ref, '0.53.0', $repo),
    ]);
    $changelog = raChangelog(['0.53.0', '0.52.0'], $registro === null ? [] : [$registro]);

    $falhas = raFalhasDoLink($roteiro, $changelog, '0.53.0');

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'revisão registrada, main'     => ['0.53.0', 'com marca', 'main', RA_REPO, 'aprova'],
    'revisão registrada, commit'   => ['0.53.0', 'com marca', '3f1c9e2a7b4d6e8f0a1b2c3d4e5f60718293a4b5', RA_REPO, 'aprova'],
    'link na tag reprovada'        => ['0.53.0', 'com marca', 'v0.53.0', RA_REPO, 'reprova'],
    'registro de outra versão'     => ['0.52.0', 'com marca', 'main', RA_REPO, 'reprova'],
    'SHA curto'                    => ['0.53.0', 'com marca', '3f1c9e2', RA_REPO, 'reprova'],
    'branch que não é a main'      => ['0.53.0', 'com marca', 'develop', RA_REPO, 'reprova'],
    'commit em outro repositório'  => ['0.53.0', 'com marca', '3f1c9e2a7b4d6e8f0a1b2c3d4e5f60718293a4b5', 'outro/filament-starter-kit-easy', 'reprova'],
    'revisão registrada sem marca' => ['0.53.0', null, 'main', RA_REPO, 'reprova'],
    'marca sem registro'           => [null, 'com marca', 'v0.53.0', RA_REPO, 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R5 e R19 — decisões do dono
|--------------------------------------------------------------------------
*/

it('[CT-10] cada decisão do dono traz os três elementos, com valor', function (string $decisoes, string $resultado): void {
    $corpo = match ($decisoes) {
        'completa'                  => raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', 'desligado.', 'manter desligado.').raDecisaoDeConflito(),
        'só conflito'               => raDecisaoDeConflito(),
        'sem recomendação'          => raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', null, 'manter desligado.'),
        'sem default'               => raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', 'desligado.', null),
        'sem pergunta'              => raDecisao('Chat de IA', null, 'desligado.', 'manter desligado.')."Ligue o interruptor.\n",
        'rótulo vazio'              => raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', '', 'manter desligado.'),
        'pergunta sem interrogação' => raDecisao('Chat de IA', 'ligar o interruptor.', 'desligado.', 'manter desligado.'),
        'duas, defeito na segunda'  => raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', 'desligado.', 'manter desligado.')
            .raDecisao('Tutorial guiado', 'Deseja manter o tutorial guiado ligado?', 'ligado.', null),
    };

    $falhas = raFalhasDeDecisoes(raComCorpo(raRoteiro('0.53.0'), '## Decisões do dono do projeto', $corpo));

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    '1, completa'                 => ['completa', 'aprova'],
    '0 decisões da versão'        => ['só conflito', 'aprova'],
    '1, falta recomendação'       => ['sem recomendação', 'reprova'],
    '1, falta default seguro'     => ['sem default', 'reprova'],
    '1, falta pergunta explícita' => ['sem pergunta', 'reprova'],
    '1, rótulo vazio'             => ['rótulo vazio', 'reprova'],
    '1, pergunta sem ?'           => ['pergunta sem interrogação', 'reprova'],
    'N, defeito só na segunda'    => ['duas, defeito na segunda', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-11] o relatório final pede o registro de cada decisão e do default aplicado', function (): void {
    $roteiro = raRoteiro('0.53.0');

    expect(raFalhasDoRelatorio($roteiro))->toBe([]);

    $semRegistro = raComCorpo($roteiro, '## Relatório final', 'Liste as versões de origem e destino.');
    $falhas      = raFalhasDoRelatorio($semRegistro);

    expect($falhas)->not->toBe([]);
    expect(implode("\n", $falhas))->toContain('## Relatório final');
})->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-25] cada default que a versão mudou vira uma pergunta do roteiro', function (?array $lista, string $perguntas, string $resultado, ?string $item): void {
    $roteiro = raRoteiro('0.53.0');

    $roteiro = match ($perguntas) {
        'cada item numa Pergunta'  => $roteiro,
        'só a decisão de conflito' => raComCorpo($roteiro, '## Decisões do dono do projeto', raDecisaoDeConflito()),
        'só chat de IA desligado'  => raComCorpo($roteiro, '## Decisões do dono do projeto', raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', 'desligado.', 'manter desligado.').raDecisaoDeConflito()),
        'item só em Cuidados'      => raAcrescentar(raComCorpo($roteiro, '## Decisões do dono do projeto', raDecisaoDeConflito()), '## Cuidados', 'Atenção: tutorial guiado ligado por padrão nesta versão.'),
    };

    $falhas = raFalhasDosDefaults($roteiro, raChangelog(['0.53.0', '0.52.0'], [], $lista), '0.53.0');

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);

        return;
    }

    expect($falhas)->not->toBe([]);

    if ($item !== null) {
        expect(implode("\n", $falhas))->toContain($item);
    }
})->with([
    'N itens, todos perguntados'       => [['chat de IA desligado', 'tutorial guiado ligado'], 'cada item numa Pergunta', 'aprova', null],
    '0 itens, explícito'               => [['nenhum'], 'só a decisão de conflito', 'aprova', null],
    'N, um item calado'                => [['chat de IA desligado', 'tutorial guiado ligado'], 'só chat de IA desligado', 'reprova', 'tutorial guiado ligado'],
    'item citado fora de uma pergunta' => [['tutorial guiado ligado'], 'item só em Cuidados', 'reprova', 'tutorial guiado ligado'],
    'lista ausente'                    => [null, 'cada item numa Pergunta', 'reprova', null],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-26] o roteiro traz a decisão de conflito em arquivo do kit editado pelo dono', function (string $decisao, string $resultado): void {
    $corpo = match ($decisao) {
        'conforme'       => raDecisoesPadrao(),
        'ausente'        => raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', 'desligado.', 'manter desligado.'),
        'default errado' => raDecisao('Chat de IA', 'Deseja manter o chat de IA desligado?', 'desligado.', 'manter desligado.')
            .raDecisaoDeConflito('sobrescrever com a versão do kit.'),
    };

    $falhas = raFalhasDoConflito(raComCorpo(raRoteiro('0.53.0'), '## Decisões do dono do projeto', $corpo));

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'conforme'        => ['conforme', 'aprova'],
    'decisão ausente' => ['ausente', 'reprova'],
    'default errado'  => ['default errado', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R6 e R17 — autossuficiência e comandos destrutivos
|--------------------------------------------------------------------------
*/

it('[CT-12] o roteiro que depende de outro documento, ou que não invoca o kit:update, reprova', function (string $defeito, string $resultado): void {
    $base     = raRoteiro('0.53.0');
    $oQueMuda = '## O que muda nesta versão';

    $roteiro = match ($defeito) {
        'nenhum'                    => $base,
        'CHANGELOG sem link'        => raAcrescentar($base, $oQueMuda, 'Veja o CHANGELOG para os detalhes.'),
        'docs/'                     => raAcrescentar($base, $oQueMuda, '[guia](docs/pt/comecar/atualizando-o-projeto.md)'),
        'wiki de spec'              => raAcrescentar($base, $oQueMuda, '[wiki](wikis/specs/feat/onboarding-guiado/)'),
        'wiki que viaja'            => raAcrescentar($base, $oQueMuda, '[agentes](wikis/agentes-e-skills.md)'),
        'README'                    => raAcrescentar($base, $oQueMuda, '[README](README.md)'),
        'URL raw do repositório'    => raAcrescentar($base, $oQueMuda, '[changelog](https://raw.githubusercontent.com/'.RA_REPO.'/v0.52.0/CHANGELOG.md)'),
        'roteiro anterior'          => raAcrescentar($base, $oQueMuda, 'Os cuidados de wikis/atualizacoes/v0.51.2.md continuam valendo.'),
        'PR'                        => raAcrescentar($base, $oQueMuda, '[PR](https://github.com/'.RA_REPO.'/pull/174)'),
        'kit:update só em Cuidados' => raAcrescentar(raComCorpo($base, '## Passos', 'Atualize o kit pelo comando do projeto.'), '## Cuidados', 'Rode `php artisan kit:update` com cuidado.'),
        'kit:update só proibição'   => raComCorpo($base, '## Passos', "Não rode php artisan kit:update antes do backup.\n\n```bash\nphp artisan migrate --force\n```"),
        'todo agente'               => raAcrescentar($base, $oQueMuda, 'Este roteiro funciona com qualquer agente.'),
        'sem conferir a origem'     => raComCorpo($base, '## Antes de começar', 'Faça backup do banco antes de começar.'),
    };

    $falhas = raFalhasDeAutossuficiencia($roteiro, '0.53.0');

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'nenhum'                                          => ['nenhum', 'aprova'],
    'remete ao CHANGELOG'                             => ['CHANGELOG sem link', 'reprova'],
    'remete a docs/'                                  => ['docs/', 'reprova'],
    'remete à wiki de spec'                           => ['wiki de spec', 'reprova'],
    'remete a documento que viaja (agentes-e-skills)' => ['wiki que viaja', 'reprova'],
    'remete a documento que viaja (README)'           => ['README', 'reprova'],
    'URL raw do próprio repositório como remissão'    => ['URL raw do repositório', 'reprova'],
    'remete ao roteiro anterior'                      => ['roteiro anterior', 'reprova'],
    'remete a PR ou issue'                            => ['PR', 'reprova'],
    'não invoca nos passos'                           => ['kit:update só em Cuidados', 'reprova'],
    'só proibição'                                    => ['kit:update só proibição', 'reprova'],
    'promete todo agente'                             => ['todo agente', 'reprova'],
    'aplicável fora da origem'                        => ['sem conferir a origem', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-23] comando destrutivo do banco fora de "## Cuidados" reprova', function (string $secao, string $conteudo, string $resultado): void {
    $roteiro = raAcrescentar(raRoteiro('0.53.0'), $secao, $conteudo);

    $falhas = raFalhasDeComandoDestrutivo($roteiro);

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'migrate --force'                  => ['## Passos', "```bash\nphp artisan migrate --force\n```", 'aprova'],
    'migrate:fresh'                    => ['## Passos', "```bash\nphp artisan migrate:fresh --force\n```", 'reprova'],
    'db:wipe'                          => ['## Passos', "```bash\nphp artisan db:wipe\n```", 'reprova'],
    'migrate:refresh'                  => ['## Passos', "```bash\nphp artisan migrate:refresh\n```", 'reprova'],
    'migrate:reset'                    => ['## Passos', "```bash\nphp artisan migrate:reset\n```", 'reprova'],
    'migrate:rollback'                 => ['## Passos', "```bash\nphp artisan migrate:rollback\n```", 'reprova'],
    'migrate:fresh --seed'             => ['## Passos', "```bash\nphp artisan migrate:fresh --seed\n```", 'reprova'],
    'prefixo sail'                     => ['## Passos', "```bash\n./vendor/bin/sail artisan migrate:fresh\n```", 'reprova'],
    'prefixo docker'                   => ['## Passos', "```bash\ndocker compose exec app php artisan db:wipe\n```", 'reprova'],
    'opção antes do comando'           => ['## Passos', "```bash\nphp artisan --env=production migrate:fresh\n```", 'reprova'],
    'inline no meio da linha'          => ['## Passos', 'Se travar, rode `php artisan migrate:reset`.', 'reprova'],
    'dentro de comentário'             => ['## Passos', '<!-- php artisan db:wipe -->', 'reprova'],
    'subseção dentro dos passos'       => ['## Passos', "### Se falhar\n\n```bash\nphp artisan migrate:fresh\n```", 'reprova'],
    'outra seção que o agente executa' => ['## Quando parar', "```bash\nphp artisan migrate:rollback\n```", 'reprova'],
    'proibição em Cuidados'            => ['## Cuidados', 'Nunca rode php artisan migrate:fresh no projeto.', 'aprova'],
    'subseção de Cuidados'             => ['## Cuidados', "### Banco\n\nNunca rode php artisan db:wipe.", 'aprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R7 e R8 — cópia local
|--------------------------------------------------------------------------
*/

it('[CT-13] a cópia entre os marcadores é conferida contra o roteiro da versão marcada', function (string $copia, string $resultado): void {
    $roteiro = raRoteiro('0.52.0');

    $agentes = match ($copia) {
        'idêntica'     => raAgentes($roteiro),
        'CRLF'         => str_replace("\n", "\r\n", raAgentes($roteiro)),
        'um caractere' => raAgentes((string) preg_replace('/v0\.51\.2/', 'v0.51.1', $roteiro, 1)),
        'outra versão' => raAgentes(raRoteiro('0.51.2')),
        'sem início'   => raAgentes($roteiro, comInicio: false),
        'sem fim'      => raAgentes($roteiro, comFim: false),
    };

    $falhas = raFalhasDeCopiaLocal($roteiro, $agentes);

    if ($resultado === 'aprova') {
        expect($falhas)->toBe([]);
    } else {
        expect($falhas)->not->toBe([]);
    }
})->with([
    'idêntica'                    => ['idêntica', 'aprova'],
    'só o fim de linha difere'    => ['CRLF', 'aprova'],
    '1 caractere, mesma extensão' => ['um caractere', 'reprova'],
    'cópia de outra versão'       => ['outra versão', 'reprova'],
    'marcador de início ausente'  => ['sem início', 'reprova'],
    'marcador de fim ausente'     => ['sem fim', 'reprova'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-14] "wikis/agentes-e-skills.md" viaja pela rota de entrega', function (string $rota): void {
    $caminho = 'wikis/agentes-e-skills.md';

    if ($rota === 'composer create-project') {
        $doArquivo = trim(Process::path(base_path())->run("git check-attr export-ignore -- {$caminho}")->output());
        $controle  = trim(Process::path(base_path())->run('git check-attr export-ignore -- wikis/specs')->output());

        expect($controle)->toContain('export-ignore: set');
        expect($doArquivo)->toContain("{$caminho}: export-ignore: unspecified");

        return;
    }

    $lista  = (new ReflectionClassConstant(KitUpdate::class, 'CAMINHOS_DO_KIT'))->getValue();
    $contem = array_filter($lista, static fn (string $entrada): bool => $entrada === $caminho || str_starts_with($caminho, rtrim($entrada, '/').'/'));

    expect($contem)->not->toBeEmpty();
})->with([
    'composer create-project',
    'php artisan kit:update',
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R9 — o passo do workflow de release (executado com bash)
|--------------------------------------------------------------------------
*/

it('[CT-15] o passo do workflow confere o roteiro da tag', function (string $versao, string $roteiro, string $saida, ?string $agulha): void {
    $run = raPassoDoWorkflow();

    expect($run)->not->toBeNull('nenhum passo de .github/workflows/release.yml confere wikis/atualizacoes');

    $conforme = raRoteiro($versao);
    $pasta    = ['README.md' => 'contrato'];

    match (true) {
        $roteiro === 'existe'     => $pasta["v{$versao}.md"] = $conforme,
        $roteiro === 'ausente'    => null,
        $roteiro === 'sem âncora' => $pasta["v{$versao}.md"] = raAcrescentar(raSemSecao($conforme, '## Cuidados'), '## O que muda nesta versão', 'Veja ## Cuidados abaixo.'),
        default                   => $pasta["v{$versao}.md"] = raSemSecao($conforme, $roteiro),
    };

    [$codigo, $texto] = raRodarPasso($run, "v{$versao}", $versao, raChangelog([$versao, '0.52.0']), $pasta);

    if ($saida === 'zero') {
        expect($codigo)->toBe(0, $texto);
        expect($texto)->not->toContain('::error::');

        return;
    }

    expect($codigo)->not->toBe(0, $texto);
    expect(raErrosQueNomeiam($texto, (string) $agulha))->not->toBeEmpty("nenhuma linha ::error:: nomeia \"{$agulha}\" na saída: {$texto}");
})->with([
    'presente'                        => ['0.53.0', 'existe', 'zero', null],
    'ausente, menor'                  => ['0.53.0', 'ausente', 'diferente', 'wikis/atualizacoes/v0.53.0.md'],
    'ausente, hotfix'                 => ['0.52.1', 'ausente', 'diferente', 'wikis/atualizacoes/v0.52.1.md'],
    'sem Identificação'               => ['0.53.0', '## Identificação', 'diferente', '## Identificação'],
    'sem Antes de começar'            => ['0.53.0', '## Antes de começar', 'diferente', '## Antes de começar'],
    'sem O que muda nesta versão'     => ['0.53.0', '## O que muda nesta versão', 'diferente', '## O que muda nesta versão'],
    'sem Cuidados'                    => ['0.53.0', '## Cuidados', 'diferente', '## Cuidados'],
    'sem Decisões do dono do projeto' => ['0.53.0', '## Decisões do dono do projeto', 'diferente', '## Decisões do dono do projeto'],
    'sem Passos'                      => ['0.53.0', '## Passos', 'diferente', '## Passos'],
    'sem Como conferir'               => ['0.53.0', '## Como conferir', 'diferente', '## Como conferir'],
    'sem Quando parar'                => ['0.53.0', '## Quando parar', 'diferente', '## Quando parar'],
    'sem Relatório final'             => ['0.53.0', '## Relatório final', 'diferente', '## Relatório final'],
    'presença sem âncora'             => ['0.53.0', 'sem âncora', 'diferente', '## Cuidados'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-29] a conferência do roteiro não encerra o passo antes das outras conferências da tag', function (): void {
    $run = raPassoDoWorkflow();

    expect($run)->not->toBeNull('nenhum passo de .github/workflows/release.yml confere wikis/atualizacoes');

    [$codigo, $texto] = raRodarPasso(
        $run,
        'v0.53.0',
        '0.52.0',
        raChangelog(['0.53.0', '0.52.0']),
        ['README.md' => 'contrato', 'v0.53.0.md' => raRoteiro('0.53.0')],
    );

    expect($codigo)->not->toBe(0, $texto);
    expect(raErrosQueNomeiam($texto, '0.53.0 saiu sem o bump'))->not->toBeEmpty("nenhuma linha ::error:: diz que a tag saiu sem o bump: {$texto}");
})->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R10, R11, R12, R18 — redação do checklist
|--------------------------------------------------------------------------
| As seções são achadas pelo título (qualquer nível); critério e reprovação, quando não têm
| subtítulo próprio, são lidos na seção do cenário pós-release que os contém.
*/

it('[CT-16] a etapa do roteiro no checklist declara cada obrigação', function (string $elemento, string $padrao): void {
    $secao = raSecaoPorTitulo(raLer('wikis/checklist-de-release.md'), '/roteiro de atualiza[çc][ãa]o/iu');

    expect(raElementosAusentes($secao, [$elemento => $padrao]))->toBe([]);

    if ($elemento === 'obrigatória, sem atenuação') {
        expect(preg_match('/quando poss[íi]vel|idealmente|se poss[íi]vel|recomendad[oa]|sugerid[oa]/iu', raColapsar((string) $secao)))->toBe(0);
    }
})->with([
    'obrigatória, sem atenuação'    => ['obrigatória, sem atenuação', '/obrigat[óo]ri/iu'],
    'vale para hotfix'              => ['vale para hotfix', '/hotfix/iu'],
    'vale para release nova'        => ['vale para release nova', '/release (?:nova|menor)|nova release|toda release|todas as releases|release e hotfix|hotfix e release/iu'],
    'acontece antes da tag'         => ['acontece antes da tag', '/antes d[ae] (?:a )?tag/iu'],
    'o CHANGELOG como fonte'        => ['o CHANGELOG como fonte', '/CHANGELOG/u'],
    'a wiki da feature como fonte'  => ['a wiki da feature como fonte', '/\bwiki\b/iu'],
    'reconciliação das duas fontes' => ['reconciliação das duas fontes', '/reconcili/iu'],
    'lista de defaults mudados'     => ['lista de defaults mudados', '/defaults? mudad|default[^.]{0,60}(?:mud|alter)\w*|lista de (?:todo )?defaults?/iu'],
    'cada item vira pergunta'       => ['cada item vira pergunta', '/cada item[^.]{0,80}pergunta|pergunta[^.]{0,80}cada item|vir(?:a|am) pergunta/iu'],
    'atualização da cópia local'    => ['atualização da cópia local', '/c[óo]pia local/iu'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-17] a seção do cenário pós-release declara cada obrigação', function (string $elemento, array $padroes): void {
    $secao = raSecaoPorTitulo(raLer('wikis/checklist-de-release.md'), '/p[óo]s-release/iu');

    expect(raElementosAusentes($secao, array_combine(
        array_map(static fn (int $i): string => "{$elemento} #{$i}", array_keys($padroes)),
        $padroes,
    )))->toBe([]);
})->with([
    'obrigatório em toda publicação'             => ['obrigatório em toda publicação', ['/obrigat[óo]ri/iu', '/toda publica[çc][ãa]o|toda release|todas as (?:publica|release)|a cada (?:nova )?(?:tag|release|publica)/iu']],
    'sessão nova, sem o contexto'                => ['sessão nova, sem o contexto', ['/sess[ãa]o nova/iu', '/sem (?:o )?contexto/iu']],
    'ambiente do agente declarado'               => ['ambiente do agente declarado', ['/mem[óo]ria de usu[áa]rio/iu', '/skills globais/iu']],
    'só o pedido com o link'                     => ['só o pedido com o link', ['/(?:s[óo]|apenas) o pedido/iu', '/link/iu']],
    'dois projetos, sem e com multi-organização' => ['dois projetos', ['/dois projetos/iu', '/multi-?organiza[çc][ãa]o/iu']],
    'cadência declarada'                         => ['cadência declarada', ['/cad[êe]ncia/iu']],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-24] o critério do cenário pós-release declara cada condição', function (string $elemento, array $padroes): void {
    $checklist  = raLer('wikis/checklist-de-release.md');
    $posRelease = raSecaoPorTitulo($checklist, '/p[óo]s-release/iu');
    $criterio   = $posRelease === null ? null : (raSecaoPorTitulo($posRelease, '/crit[ée]rio/iu') ?? $posRelease);

    expect(raElementosAusentes($criterio, array_combine(
        array_map(static fn (int $i): string => "{$elemento} #{$i}", array_keys($padroes)),
        $padroes,
    )))->toBe([]);
})->with([
    'o mesmo estado dos cenários 3 e 4'         => ['estado dos cenários 3 e 4', ['/cen[áa]rios? 3 e 4/iu', '/estado/iu']],
    'canais e customizações preservados'        => ['canais e customizações', ['/canais/iu', '/customiza[çc]/iu']],
    'dados do dono no banco preservados'        => ['dados do dono no banco', ['/dados do dono|dados[^.]{0,40}banco|banco[^.]{0,40}dados/iu']],
    'cada item da lista virou pergunta'         => ['cada item virou pergunta', ['/cada item[^.]{0,80}pergunta|pergunta[^.]{0,80}cada item|vir(?:ou|a|am) pergunta/iu']],
    'sem a lista de defaults o cenário reprova' => ['sem a lista reprova', ['/sem a lista[^.]{0,120}reprov|reprov[^.]{0,120}sem a lista|lista ausente/iu']],
    'confronto com o diff entre as tags'        => ['confronto com o diff', ['/diff/iu', '/config\//u', '/migrations? de settings/iu', '/seeders/iu', '/tag de origem/iu']],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

it('[CT-18] a reprovação do cenário pós-release tem destino escrito', function (string $elemento, string $padrao): void {
    $checklist  = raLer('wikis/checklist-de-release.md');
    $posRelease = raSecaoPorTitulo($checklist, '/p[óo]s-release/iu');
    $reprovacao = $posRelease === null ? null : (raSecaoPorTitulo($posRelease, '/reprov|falh/iu') ?? $posRelease);

    expect(raElementosAusentes($reprovacao, [$elemento => $padrao]))->toBe([]);
})->with([
    'a tag não é reescrita'                             => ['a tag não é reescrita', '/(?:n[ãa]o|nunca)[^.]{0,60}(?:reescrev|recri|apag|mov)\w*[^.]{0,30}tag|tag[^.]{0,60}n[ãa]o (?:[ée] )?(?:reescrita|recriada|movida|apagada)/iu'],
    'o roteiro é corrigido na main, com evidência nova' => ['corrigido na main com evidência nova', '/\bmain\b[^.]{0,200}evid[êe]ncia nova|evid[êe]ncia nova[^.]{0,200}\bmain\b|corrig\w+[^.]{0,120}\bmain\b/iu'],
    'o link passa a apontar para a correção'            => ['o link aponta para a correção', '/link[^.]{0,100}(?:aponta|apontar)[^.]{0,60}corre[çc][ãa]o|(?:aponta|apontar)[^.]{0,60}corre[çc][ãa]o/iu'],
    'o CHANGELOG registra que o roteiro foi revisado'   => ['o CHANGELOG registra a revisão', '/CHANGELOG[\s\S]{0,200}?revis|revis[\s\S]{0,200}?CHANGELOG/iu'],
    'falha do código vira hotfix'                       => ['falha do código vira hotfix', '/hotfix/iu'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);

/*
|--------------------------------------------------------------------------
| R13 — pontos de entrada
|--------------------------------------------------------------------------
*/

it('[CT-19] o ponto de entrada traz o padrão do link e o pedido', function (string $arquivo, string $trecho): void {
    $conteudo = raLer($arquivo);

    expect($conteudo)->not->toBe('', "{$arquivo} não existe ou está vazio");

    if ($trecho === 'seção fora dos marcadores') {
        $semCopia = (string) preg_replace('/'.preg_quote(RA_INICIO, '/').'.*?'.preg_quote(RA_FIM, '/').'/su', '', raNl($conteudo));
        $texto    = raSecaoPorTitulo($semCopia, '/Atualizar o projeto pelo agente/u');

        expect($texto)->not->toBeNull('a seção "Atualizar o projeto pelo agente" não existe');
    } else {
        $texto = $conteudo;
    }

    $padrao = str_contains($texto, RA_PADRAO) || str_contains($texto, str_replace('{versão}', '{version}', RA_PADRAO));

    expect($padrao)->toBeTrue("{$arquivo} não traz o padrão do link raw do roteiro");
    expect(preg_match('/pedido|pe[çc]a ao agente|atualize o projeto|request|ask (?:your |the )?agent|prompt|update (?:my|the|this|your) project/iu', $texto))->toBe(1, "{$arquivo} não traz o pedido ao agente");
    expect(raFalhasDasInstrucoesDoPadrao($texto))->toBe([]);
})->with([
    'wikis/agentes-e-skills.md'                => ['wikis/agentes-e-skills.md', 'seção fora dos marcadores'],
    'docs/pt/comecar/atualizando-o-projeto.md' => ['docs/pt/comecar/atualizando-o-projeto.md', 'página inteira'],
    'docs/en/comecar/atualizando-o-projeto.md' => ['docs/en/comecar/atualizando-o-projeto.md', 'página inteira'],
])->skip(fn (): bool => ! naArvoreDoKit(), RA_PULO_FORA_DA_ARVORE);
