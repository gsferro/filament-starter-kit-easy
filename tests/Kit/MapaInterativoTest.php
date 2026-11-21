<?php

/**
 * As guardas da costura G2 do `04-casos-de-teste.md` da wiki `feat/mapa-interativo-do-kit`: o modelo do mapa
 * (`site/mapa/pecas.json`, `ligacoes.json` e `diagramas/`) contra a aplicação de pé, sem tenant.
 *
 * Lote deste arquivo: R8, R9 (CT-09, CT-52 e CT-53), R10, R11 (CT-11 e CT-13), R28 (CT-29 e CT-41), R35,
 * R36 (CT-40 e CT-54) e R37. O CT-52 é da costura G5: roda o gerador do mapa num `node`, na forma de leitura.
 * Os demais lotes de G2 (R6, R12, R15 a R20, R22, R29, R34) são de outros arquivos de lote.
 *
 * Escrito a partir do `04`: `app/`, `config/` e `site/mapa/` só foram lidos para achar NOMES (classe, caminho,
 * formato dos arquivos do modelo), nunca para decidir o resultado esperado.
 *
 * Toda guarda é uma função aplicada ao modelo publicado e a cópias adulteradas EM MEMÓRIA — nunca ao arquivo
 * real (`04`, "Guarda e controles"). As linhas "cópia com …" chamam a MESMA função da linha "publicado".
 * Cada guarda devolve `aceita`, o `motivo` (todas as violações, nomeando o item) e o que conferiu.
 *
 * Os helpers só deste arquivo moram aqui, com o prefixo `mapaModelo`. Os que as outras guardas do mapa também
 * usam (o modelo, os diagramas, o registro vivo, o catálogo, o índice do git, a F-nn da peça, o motivo de
 * enfeite e a entrada que o gerador dá ao archify) moram em `tests/Pest.php` (`.ai/rules/testes.md`).
 */

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Pages\BasePage;
use Filament\Panel;
use Filament\Widgets\Widget;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Contracts\HasMiddleware;
use Livewire\Component;
use Spatie\LaravelSettings\Settings;

define('MAPA_MODELO_SKIP', 'o modelo do mapa, o catálogo e a página moram em site/, docs/ e README, que não viajam no pacote (export-ignore)');

/*
|--------------------------------------------------------------------------
| Listas fechadas que as guardas guardam
|--------------------------------------------------------------------------
*/

/** Os 13 tipos de peça (RQ-24 e glossário), conforme o Setup Global do `04`. */
define('MAPA_MODELO_TIPOS', ['painel', 'recurso', 'pagina', 'widget', 'plugin', 'provider', 'middleware', 'servico', 'model', 'policy', 'setting', 'comando', 'livewire']);

/** Os métodos de contrato do alvo, das interfaces de `vendor/filament/filament/src/Models/Contracts` (R10, ADV2-08). */
define('MAPA_MODELO_METODOS_DE_CONTRATO', ['canAccessPanel', 'canAccessTenant', 'getTenants', 'getDefaultTenant', 'getCurrentTenantLabel', 'getFilamentName', 'getFilamentAvatarUrl']);

/**
 * A lista da guarda das peças e classes sem feature própria (P-12), com o motivo. Copiada de
 * `mapa-pesquisa/modelo/sem-feature-propria.json`.
 */
define('MAPA_MODELO_SEM_FEATURE_PROPRIA', [
    'App\\Http\\Controllers\\Controller' => 'classe base abstrata e vazia que o Laravel gera para os controllers; não tem método nem comportamento, e os controllers do kit que entregam feature (LoginSocialController, ContaIndisponivelController, EntrarNoPainelController) já têm a F-nn deles',
    'App\\Support\\BooleanoDoEnv'        => 'conversor do valor bruto de uma variável do .env em booleano com padrão (vazio ou irreconhecível cai no padrão), chamado pelo config/kit.php para várias chaves; é a infraestrutura de leitura da configuração, e cada feature que usa uma dessas chaves já tem a sua F-nn',
    'App\\Support\\NumeroDoEnv'          => 'conversor do valor bruto de uma variável do .env em número positivo com padrão, chamado pelo config/kit.php (paginação, teto de upload e outras); é a infraestrutura de leitura da configuração, e cada feature que lê um desses números já tem a sua F-nn',
]);

/*
|--------------------------------------------------------------------------
| Helpers: nomes, resultado e a prova que resolve (R9)
|--------------------------------------------------------------------------
| O modelo, os diagramas, o catálogo e o índice do git vêm de `tests/Pest.php`.
*/

function mapaModeloNorm(string $texto): string
{
    return str_replace("\r\n", "\n", $texto);
}

/**
 * @param  list<string>  $violacoes
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function mapaModeloResultado(array $violacoes, array $extra = []): array
{
    return [
        'aceita'    => $violacoes === [],
        'motivo'    => $violacoes === [] ? null : implode('; ', array_slice($violacoes, 0, 60)),
        'violacoes' => $violacoes,
    ] + $extra;
}

/**
 * O texto do arquivo com cada byte de comentário trocado por espaço e as quebras de linha mantidas: o mesmo
 * tamanho e as mesmas linhas, pela sintaxe do tipo do arquivo (`04`, Guarda e controles, ADV2-06 e Q20). É o que
 * diz se o trecho está fora de comentário NUMA linha (CT-52), onde apagar o comentário deslocaria as linhas.
 */
function mapaModeloMascararComentarios(string $arquivo, string $conteudo): string
{
    $mascarar = static fn (string $texto): string => (string) preg_replace('/[^\n]/', ' ', $texto);
    $porRegex = static fn (string $padrao): string => (string) preg_replace_callback($padrao, static fn (array $m): string => $mascarar($m[0]), $conteudo);

    if (str_ends_with($arquivo, '.blade.php')) {
        return $porRegex('/\{\{--.*?--\}\}|<!--.*?-->/s');
    }

    if (str_ends_with($arquivo, '.php')) {
        $saida = '';

        foreach (token_get_all($conteudo) as $token) {
            $saida .= ! is_array($token) ? $token : (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? $mascarar($token[1]) : $token[1]);
        }

        return $saida;
    }

    if (preg_match('/\.(m?js|ts|stub)$/', $arquivo) === 1) {
        return $porRegex('~/\*.*?\*/|(?<![:\w])//[^\n]*~s');
    }

    if (preg_match('/\.ya?ml$/', $arquivo) === 1 || str_starts_with(basename($arquivo), '.env')) {
        return $porRegex('/(?<=^|[ \t])#[^\n]*/m');
    }

    if (str_ends_with($arquivo, '.md')) {
        return $porRegex('/<!--.*?-->/s');
    }

    return $conteudo;
}

/**
 * O código do arquivo sem comentário, pela sintaxe do tipo dele (`04`, Guarda e controles, ADV2-06). Em YAML e
 * `.env`, o `#` até o fim da linha; em Markdown, o `<!-- -->` (Q20, step 9, achado A7); JSON não tem comentário.
 */
function mapaModeloSemComentario(string $arquivo, string $conteudo): string
{
    if (preg_match('/\.(ya?ml|md)$/', $arquivo) === 1 || str_starts_with(basename($arquivo), '.env')) {
        return mapaModeloMascararComentarios($arquivo, $conteudo);
    }

    if (str_ends_with($arquivo, '.blade.php')) {
        return preg_replace(['/\{\{--.*?--\}\}/s', '/<!--.*?-->/s'], '', $conteudo) ?? $conteudo;
    }

    if (str_ends_with($arquivo, '.php')) {
        return codigoSemComentario($conteudo);
    }

    if (preg_match('/\.(m?js|ts|stub)$/', $arquivo) === 1) {
        $sem = preg_replace('~/\*.*?\*/~s', '', $conteudo) ?? $conteudo;

        return preg_replace('~(?<![:\w])//[^\n]*~', '', $sem) ?? $sem;
    }

    return $conteudo;
}

/**
 * O código sem comentário de um arquivo do repositório, ou `null` se não é arquivo. A sobrescrita
 * troca o conteúdo em memória (o controle "cópia do arquivo sem a linha do trecho").
 *
 * @param  array<string, string>  $sobrescritas
 */
function mapaModeloCodigo(string $arquivo, array $sobrescritas = []): ?string
{
    static $cache = [];

    if (isset($sobrescritas[$arquivo])) {
        return mapaModeloSemComentario($arquivo, mapaModeloNorm($sobrescritas[$arquivo]));
    }

    if (array_key_exists($arquivo, $cache)) {
        return $cache[$arquivo];
    }

    $caminho = base_path($arquivo);

    return $cache[$arquivo] = is_file($caminho)
        ? mapaModeloSemComentario($arquivo, mapaModeloNorm((string) file_get_contents($caminho)))
        : null;
}

/**
 * R9: a prova resolve no commit que publica. O arquivo é versionado no kit, o trecho está nele fora de
 * comentário, e a prova não guarda número de linha (campo próprio, ou colado no caminho).
 *
 * A âncora tem chaves fechadas — `arquivo`, `trecho` e, opcional, `label` (step 9, achado A10): qualquer outra
 * (`linha`, `line`, `end_line`, `path`) reprova, porque o gerador a deixa vencer a que ele calcula.
 *
 * @param  array<string, mixed>  $campos  os campos além de `arquivo` e `trecho`
 * @param  array<string, string>  $sobrescritas
 * @return array{aceita: bool, motivo: ?string}
 */
function mapaModeloConferirProva(string $nome, mixed $arquivo, mixed $trecho, array $campos = [], array $sobrescritas = []): array
{
    $reprova = static fn (string $motivo): array => ['aceita' => false, 'motivo' => "{$nome}: {$motivo}"];

    foreach (array_keys($campos) as $campo) {
        if ($campo === 'label') {
            continue;
        }

        return $reprova(in_array($campo, ['linha', 'line', 'end_line'], true)
            ? "a prova guarda o número da linha no campo `{$campo}`"
            : "a prova traz a chave `{$campo}`, fora das chaves da âncora (arquivo, trecho e label)");
    }

    if (! is_string($arquivo) || trim($arquivo) === '') {
        return $reprova('a prova não tem arquivo');
    }

    if (preg_match('/(:\d+|#L\d+)$/', $arquivo) === 1) {
        return $reprova("a prova guarda o número da linha no caminho {$arquivo}");
    }

    if (! is_string($trecho) || trim($trecho) === '') {
        return $reprova("a prova de {$arquivo} não tem trecho (ausente, vazio ou só de espaços)");
    }

    $codigo = mapaModeloCodigo($arquivo, $sobrescritas);

    if ($codigo === null) {
        return $reprova("o arquivo {$arquivo} não existe");
    }

    if (! in_array($arquivo, arquivosVersionados(), true)) {
        return $reprova("o arquivo {$arquivo} está fora do commit (não é versionado no kit)");
    }

    if (! str_contains($codigo, mapaModeloNorm($trecho))) {
        return $reprova("o trecho não está em {$arquivo} fora de comentário");
    }

    return ['aceita' => true, 'motivo' => null];
}

/**
 * As peças (ou nós) de um arquivo de diagrama cuja prova não é a âncora fechada: guarda número de linha (num
 * campo ou colado no caminho), traz chave fora de `arquivo`, `trecho` e `label` (step 9, achado A10), ou tem no
 * `sources` uma fonte que não é âncora `{arquivo, trecho}`.
 *
 * @return list<string>
 */
function mapaModeloAncorasForaDoFormato(mixed $no, string $contexto = ''): array
{
    if (! is_array($no)) {
        return [];
    }

    if (isset($no['id']) && is_string($no['id'])) {
        $contexto = $no['id'];
    }

    $quem    = $contexto !== '' ? $contexto : '(sem id)';
    $achados = [];

    if (array_key_exists('arquivo', $no) || array_key_exists('trecho', $no)) {
        foreach (array_diff(array_keys($no), ['arquivo', 'trecho', 'label']) as $chave) {
            $achados[] = "{$quem} (chave `{$chave}`)";
        }

        if (is_string($no['arquivo'] ?? null) && preg_match('/(:\d+|#L\d+)$/', $no['arquivo']) === 1) {
            $achados[] = "{$quem} (linha no caminho {$no['arquivo']})";
        }
    }

    foreach (is_array($no['sources'] ?? null) ? $no['sources'] : [] as $fonte) {
        if (! is_array($fonte) || ! array_key_exists('arquivo', $fonte) || ! array_key_exists('trecho', $fonte)) {
            $achados[] = "{$quem} (fonte que não é âncora {arquivo, trecho})";
        }
    }

    foreach ($no as $filho) {
        $achados = array_merge($achados, mapaModeloAncorasForaDoFormato($filho, $contexto));
    }

    return $achados;
}

/**
 * R9 sobre o modelo inteiro: a prova de TODAS as ligações (e não só as de registro) e a ausência de linha
 * nas provas dos arquivos de diagrama.
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  array<string, array<string, mixed>>  $diagramas
 * @return array<string, mixed>
 */
function mapaModeloConferirProvas(array $modelo, array $diagramas): array
{
    $violacoes  = [];
    $conferidas = 0;
    $porPainel  = [];
    $evidencias = 0;

    foreach ($modelo['ligacoes'] as $ligacao) {
        $nome      = 'a ligação '.class_basename($ligacao['de']).' → '.class_basename($ligacao['para']);
        $evidencia = is_array($ligacao['evidencia'] ?? null) ? $ligacao['evidencia'] : [];
        $campos    = array_intersect_key($ligacao, ['linha' => 1, 'line' => 1]) + array_diff_key($evidencia, ['arquivo' => 1, 'trecho' => 1]);
        $resultado = mapaModeloConferirProva($nome, $evidencia['arquivo'] ?? null, $evidencia['trecho'] ?? null, $campos);

        $conferidas++;

        foreach ($ligacao['paineis'] ?? [] as $painel) {
            $porPainel[$painel] = ($porPainel[$painel] ?? 0) + 1;
        }

        if (! $resultado['aceita']) {
            $violacoes[] = $resultado['motivo'];
        }
    }

    // A10: a evidência de cada peça do modelo de peças também tem as chaves fechadas da âncora.
    foreach ($modelo['pecas'] as $peca) {
        foreach (is_array($peca['evidencia'] ?? null) ? $peca['evidencia'] : [] as $ancora) {
            $evidencias++;

            foreach (is_array($ancora) ? array_diff(array_keys($ancora), ['arquivo', 'trecho', 'label']) : ['(não é âncora)'] as $chave) {
                $violacoes[] = "a evidência da peça {$peca['id']} traz a chave `{$chave}`, fora das chaves da âncora (arquivo, trecho e label)";
            }
        }
    }

    foreach ($diagramas as $slug => $diagrama) {
        foreach (mapaModeloAncorasForaDoFormato($diagrama) as $peca) {
            $violacoes[] = "o diagrama {$slug} guarda, na prova da peça {$peca}, o que a âncora não tem (número de linha ou chave fora de arquivo, trecho e label)";
        }
    }

    return mapaModeloResultado($violacoes, ['conferidas' => $conferidas, 'porPainel' => $porPainel, 'evidencias' => $evidencias]);
}

/*
|--------------------------------------------------------------------------
| Helpers: a guarda do registro (R11)
|--------------------------------------------------------------------------
| O registro vivo do painel e o que o modelo lhe dá no modo vêm de `tests/Pest.php`.
*/

/**
 * R11: tudo que o painel registra tem ligação de registro no modelo (painel → modelo), e o modelo não dá ao
 * modo o que o painel não registra (modelo → painel).
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  list<string>|null  $paineis
 * @return array<string, mixed>
 */
function mapaModeloConferirRegistro(array $modelo, string $modo, ?array $paineis = null): array
{
    $violacoes  = [];
    $conferidas = 0;

    foreach ($paineis ?? array_keys(Filament::getPanels()) as $id) {
        $vivo     = array_merge(...array_values(registroVivoDoPainel(Filament::getPanel($id))));
        $noModelo = registradasNoModelo($modelo, $id, $modo);

        foreach (array_diff($vivo, $noModelo) as $peca) {
            $violacoes[] = "o painel {$id} registra ".class_basename($peca)." ({$peca}) e o modelo não tem a ligação de registro";
        }

        foreach (array_diff($noModelo, $vivo) as $peca) {
            $violacoes[] = 'o modelo dá '.class_basename($peca)." ({$peca}) ao painel {$id} no modo {$modo} e o painel não o registra";
        }

        $conferidas += count($vivo);
    }

    return mapaModeloResultado($violacoes, ['conferidas' => $conferidas]);
}

/*
|--------------------------------------------------------------------------
| Helpers: o tipo de peça pela classe (R8)
|--------------------------------------------------------------------------
*/

/**
 * O mapa nome-curto → classe dos `use` de um código PHP.
 *
 * @return array<string, string>
 */
function mapaModeloUsosDoArquivo(string $codigo): array
{
    preg_match_all('/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?;/m', $codigo, $m, PREG_SET_ORDER);

    $usos = [];

    foreach ($m as $uso) {
        $usos[$uso[2] ?? class_basename($uso[1])] = $uso[1];
    }

    return $usos;
}

/** A classe que um nome curto designa num código PHP: pelo `use` ou pelo namespace do arquivo; `null` se não existe. */
function mapaModeloResolverNomeCurto(string $codigo, string $curto): ?string
{
    $usos = mapaModeloUsosDoArquivo($codigo);

    if (isset($usos[$curto])) {
        return class_exists($usos[$curto]) ? $usos[$curto] : null;
    }

    if (preg_match('/^namespace\s+([\w\\\\]+);/m', $codigo, $m) === 1 && class_exists($m[1].'\\'.$curto)) {
        return $m[1].'\\'.$curto;
    }

    return null;
}

/**
 * O que a tabela de tipos da R8 consulta além da herança: as policies que o `Gate` devolve para um model, e
 * os middlewares que um painel ou a aplicação registram (o alias `panel:{id}` é tratado à parte).
 *
 * @return array{politicas: array<string, true>, middlewares: array<string, true>}
 */
function mapaModeloContextoDeTipos(): array
{
    static $contexto = null;

    if ($contexto !== null) {
        return $contexto;
    }

    $politicas = [];

    foreach (Gate::policies() as $politica) {
        $politicas[$politica] = true;
    }

    foreach (modeloDoMapa()['pecas'] as $peca) {
        if ($peca['tipo'] === 'model' && class_exists($peca['id'])) {
            try {
                $politica = Gate::getPolicyFor($peca['id']);
            } catch (Throwable) {
                $politica = null;
            }

            if (is_object($politica)) {
                $politicas[$politica::class] = true;
            }
        }
    }

    $middlewares = [];
    $guardar     = static function (mixed $middleware) use (&$middlewares): void {
        if (is_string($middleware) && ! str_starts_with($middleware, 'panel:')) {
            $middlewares[explode(':', $middleware, 2)[0]] = true;
        }
    };

    foreach (Filament::getPanels() as $painel) {
        foreach ([$painel->getMiddleware(), $painel->getAuthMiddleware(), $painel->getTenantMiddleware()] as $lista) {
            array_map($guardar, $lista);
        }
    }

    $roteador = app('router');
    array_map($guardar, array_values($roteador->getMiddleware()));

    foreach ($roteador->getMiddlewareGroups() as $grupo) {
        array_map($guardar, $grupo);
    }

    array_map($guardar, app(Kernel::class)->getGlobalMiddleware());

    foreach ($roteador->getRoutes()->getRoutes() as $rota) {
        array_map($guardar, $rota->gatherMiddleware());
    }

    // Os que um provider de painel ou o bootstrap registram dentro de uma condição (o middleware de
    // organização só entra com a tenancy ligada, e esta suíte roda sem ela): a classe citada com `::class`
    // nesses arquivos e que é middleware (tem `handle`).
    $fontes = array_merge([base_path('bootstrap/app.php')], glob(app_path('Providers/Filament/*.php')) ?: []);

    foreach ($fontes as $fonte) {
        $codigo = codigoSemComentario((string) file_get_contents($fonte));
        $usos   = mapaModeloUsosDoArquivo($codigo);

        preg_match_all('/\b([A-Z]\w*)::class/', $codigo, $m);

        foreach (array_unique($m[1]) as $curto) {
            $classe = $usos[$curto] ?? null;

            if ($classe !== null && class_exists($classe) && method_exists($classe, 'handle')) {
                $middlewares[$classe] = true;
            }
        }
    }

    // A classe que a aplicação põe, pelo container, no lugar de um middleware registrado: o painel registra a
    // classe do pacote, e o `bind` do kit faz o container entregar a dele.
    $ligacoesDoContainer = app()->getBindings();

    foreach (array_keys($middlewares) as $registrado) {
        if (! isset($ligacoesDoContainer[$registrado]) || ! class_exists($registrado)) {
            continue;
        }

        try {
            $concreta = get_class(app($registrado));
        } catch (Throwable) {
            continue;
        }

        if ($concreta !== $registrado) {
            $middlewares[$concreta] = true;
        }
    }

    // Os que a aplicação registra como middleware no pipeline de um agente (`Laravel\Ai\Contracts\HasMiddleware`):
    // a classe citada no `middleware()` do agente, direto ou pelo registro que ele consulta, e que tem `handle`.
    foreach (mapaModeloClassesDeApp() as $agente) {
        if (! class_exists($agente) || ! is_subclass_of($agente, HasMiddleware::class)) {
            continue;
        }

        $codigo = codigoSemComentario((string) file_get_contents(base_path((string) arquivoDaClasse($agente))));
        $corpo  = mapaModeloCorpoDoMetodo($codigo, 'middleware');

        preg_match_all('/\bnew\s+([A-Z]\w*)|\b([A-Z]\w*)::(?:class\b|\w+\s*\()/', (string) $corpo, $citacoes, PREG_SET_ORDER);

        foreach ($citacoes as $citacao) {
            $classe = mapaModeloResolverNomeCurto($codigo, $citacao[1] !== '' ? $citacao[1] : $citacao[2]);

            if ($classe === null) {
                continue;
            }

            if (method_exists($classe, 'handle')) {
                $middlewares[$classe] = true;

                continue;
            }

            if (! str_starts_with($classe, 'App\\')) {
                continue;
            }

            $registro = codigoSemComentario((string) file_get_contents(base_path((string) arquivoDaClasse($classe))));
            preg_match_all('/\b([A-Z]\w*)::class\b/', $registro, $m);

            foreach (array_unique($m[1]) as $curto) {
                $registrada = mapaModeloResolverNomeCurto($registro, $curto);

                if ($registrada !== null && method_exists($registrada, 'handle')) {
                    $middlewares[$registrada] = true;
                }
            }
        }
    }

    return $contexto = ['politicas' => $politicas, 'middlewares' => $middlewares];
}

/**
 * O tipo que a classe tem, pela tabela da R8: a primeira linha que ela satisfaz, na ordem.
 *
 * @param  array{politicas: array<string, true>, middlewares: array<string, true>}  $contexto
 */
function mapaModeloTipoEsperado(string $id, array $contexto): string
{
    if (str_starts_with($id, 'painel:')) {
        return 'painel';
    }

    if (preg_match('/^panel:[\w-]+$/', $id) === 1) {
        return 'middleware';
    }

    $herancas = [
        'recurso'  => \Filament\Resources\Resource::class,
        'pagina'   => BasePage::class,
        'widget'   => Widget::class,
        'plugin'   => Plugin::class,
        'model'    => Model::class,
        'provider' => ServiceProvider::class,
        'comando'  => Command::class,
        'setting'  => Settings::class,
        'livewire' => Component::class,
    ];

    foreach ($herancas as $tipo => $base) {
        if (is_subclass_of($id, $base)) {
            return $tipo;
        }
    }

    if (isset($contexto['politicas'][$id])) {
        return 'policy';
    }

    if (isset($contexto['middlewares'][$id])) {
        return 'middleware';
    }

    return 'servico';
}

/**
 * Os pacotes instalados: o que o Composer instalou e o que os manifestos declaram.
 *
 * @return array<string, true>
 */
function mapaModeloPacotesInstalados(): array
{
    static $nomes = null;

    if ($nomes === null) {
        $nomes     = [];
        $instalado = json_decode((string) file_get_contents(base_path('vendor/composer/installed.json')), true);

        foreach ($instalado['packages'] ?? $instalado ?? [] as $pacote) {
            $nomes[$pacote['name']] = true;
        }

        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

        foreach (array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []) as $nome => $versao) {
            $nomes[$nome] = true;
        }

        $npm = json_decode((string) file_get_contents(base_path('package.json')), true);

        foreach (['dependencies', 'devDependencies', 'optionalDependencies'] as $campo) {
            foreach (array_keys($npm[$campo] ?? []) as $nome) {
                $nomes[$nome] = true;
            }
        }
    }

    return $nomes;
}

/**
 * R8: toda peça existe no código, é do tipo que a classe dela é, está no conjunto fechado e aparece uma vez.
 *
 * @param  list<array<string, mixed>>  $pecas
 * @return array<string, mixed>
 */
function mapaModeloConferirPecas(array $pecas): array
{
    $violacoes  = [];
    $vistos     = [];
    $paineis    = array_keys(Filament::getPanels());
    $instalados = mapaModeloPacotesInstalados();
    $contexto   = mapaModeloContextoDeTipos();

    foreach ($pecas as $peca) {
        $id          = $peca['id'];
        $tipo        = (string) ($peca['tipo'] ?? '');
        $vistos[$id] = ($vistos[$id] ?? 0) + 1;

        if (! in_array($tipo, MAPA_MODELO_TIPOS, true)) {
            $violacoes[] = "a peça {$id} tem o tipo \"{$tipo}\", que não é um dos 13 do conjunto fechado";

            continue;
        }

        if (isset($peca['pacote']) && ! isset($instalados[$peca['pacote']])) {
            $violacoes[] = "a peça {$id} é do pacote {$peca['pacote']}, que não está instalado";

            continue;
        }

        if ($tipo === 'painel') {
            $painel = str_starts_with($id, 'painel:') ? substr($id, 7) : $id;

            if (! in_array($painel, $paineis, true)) {
                $violacoes[] = "o painel {$painel} ({$id}) não está no registro de painéis";
            }

            continue;
        }

        if (! classeExiste($id) && preg_match('/^panel:[\w-]+$/', $id) !== 1) {
            $violacoes[] = "a classe {$id} não existe no código";

            continue;
        }

        $esperado = mapaModeloTipoEsperado($id, $contexto);

        if ($esperado !== $tipo) {
            $violacoes[] = "a classe {$id} é do tipo {$esperado}, e o modelo a declara do tipo {$tipo}";
        }
    }

    foreach ($vistos as $id => $vezes) {
        if ($vezes > 1) {
            $violacoes[] = "a classe {$id} aparece em {$vezes} peças";
        }
    }

    return mapaModeloResultado($violacoes, ['conferidas' => count($pecas)]);
}

/*
|--------------------------------------------------------------------------
| Helpers: a prova é a da ligação (R10)
|--------------------------------------------------------------------------
| O arquivo de uma classe vem de `arquivoDaClasse()`, em `tests/Pest.php`.
*/

/** O texto nomeia a peça pelo nome inteiro (palavra), e não por parte de outro nome. */
function mapaModeloNomeia(string $texto, string $nome): bool
{
    return preg_match('/(?<![\w])'.preg_quote($nome, '/').'(?!\w)/', $texto) === 1;
}

/**
 * O `register()` do plugin, num painel de rascunho, devolve a peça? (Setup Global: não confere pelo nome.) A
 * peça pode ser recurso, página, widget ou middleware (step 9, achado A9: o `Locker` pelo `Lockscreen`, o
 * `MustTwoFactor` pelo `BreezyCore`). O plugin é avaliado como o painel vivo o configura — uma cópia da
 * instância registrada nele —, e só sem ela pelo `make()`: o `BreezyCore` só põe o `MustTwoFactor` com o 2FA ligado.
 */
function mapaModeloPluginTraz(string $plugin, string $peca, string $painelVivo): bool
{
    try {
        $instancia = null;

        foreach ((Filament::getPanels()[$painelVivo] ?? null)?->getPlugins() ?? [] as $registrado) {
            if ($registrado::class === $plugin) {
                $instancia = clone $registrado;

                break;
            }
        }

        $painel = Panel::make()->id('mapa-modelo-teste')->path('mapa-modelo-teste');
        ($instancia ?? $plugin::make())->register($painel);

        $middlewares = array_map(
            static fn (string $middleware): string => explode(':', $middleware, 2)[0],
            array_filter(array_merge($painel->getMiddleware(), $painel->getAuthMiddleware()), static fn (mixed $middleware): bool => is_string($middleware) && ! str_starts_with($middleware, 'panel:')),
        );

        $trazidas = array_merge(array_filter(array_map('classeDoRegistro', array_merge($painel->getResources(), $painel->getPages(), $painel->getWidgets()))), $middlewares);

        return in_array($peca, $trazidas, true);
    } catch (Throwable) {
        return false;
    }
}

/**
 * A forma do invólucro do kit (step 9, achado A9): o nome curto chamado no provider resolve, pelos `use` dele,
 * para uma classe de `app/`, e o arquivo dessa classe nomeia a classe da peça.
 */
function mapaModeloInvolucroNomeia(string $codigoDoProvider, string $curto, string $peca): bool
{
    $classe  = mapaModeloResolverNomeCurto($codigoDoProvider, $curto);
    $arquivo = $classe !== null ? arquivoDaClasse($classe) : null;

    if ($arquivo === null || ! str_starts_with($arquivo, 'app/')) {
        return false;
    }

    $codigo = (string) mapaModeloCodigo($arquivo);
    $nome   = class_basename($peca);

    return str_contains($codigo, $peca)
        || (mapaModeloNomeia($codigo, $nome) && mapaModeloResolverNomeCurto($codigo, $nome) === $peca);
}

/** O pacote (`vendor/{fornecedor}/{pacote}`) de onde a classe vem, pelo arquivo dela; `null` fora de `vendor/`. */
function mapaModeloPacoteDaClasse(string $classe): ?string
{
    $arquivo = arquivoDaClasse($classe);

    return $arquivo !== null && preg_match('#^vendor/([^/]+/[^/]+)/#', $arquivo, $m) === 1 ? $m[1] : null;
}

/** O arquivo está em `app/Providers`, ou é um trait que uma classe de `app/Providers` usa (step 9, achado A9). */
function mapaModeloArquivoDeProvider(string $caminho): bool
{
    static $traits = null;

    if (str_starts_with($caminho, 'app/Providers/')) {
        return true;
    }

    if ($traits === null) {
        $traits = [];
        $pasta  = app_path('Providers');

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS)) as $arquivo) {
            $classe = 'App\\Providers\\'.str_replace(['/', '.php'], ['\\', ''], str_replace('\\', '/', substr($arquivo->getPathname(), strlen($pasta) + 1)));

            if ($arquivo->getExtension() === 'php' && class_exists($classe)) {
                foreach (class_uses_recursive($classe) as $trait) {
                    $traits[] = arquivoDaClasse($trait);
                }
            }
        }
    }

    return in_array($caminho, $traits, true);
}

/**
 * R10: a prova é a da ligação. Na ordem: a prova resolve (R9); as duas pontas são peças; e o arquivo é de
 * uma das formas válidas — o da origem, o trait que ela usa, o provider do painel (descoberta, plugin ou
 * configuração), o método de contrato do alvo, ou, para origem de pacote, o arquivo do kit que a liga.
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @return array{aceita: bool, motivo: ?string, regra: ?string, forma?: string}
 */
function mapaModeloConferirFormaDaProva(array $modelo, string $de, string $para, mixed $arquivo, mixed $trecho): array
{
    $nomeDe   = class_basename($de);
    $nomePara = class_basename($para);
    $ligacao  = "a ligação {$nomeDe} → {$nomePara}";
    $reprova  = static fn (string $regra, string $motivo): array => ['aceita' => false, 'motivo' => $motivo, 'regra' => $regra];

    $prova = mapaModeloConferirProva($ligacao, $arquivo, $trecho);

    if (! $prova['aceita']) {
        return $reprova('prova', $prova['motivo']);
    }

    $porId = pecasPorId($modelo['pecas']);

    foreach ([$de, $para] as $ponta) {
        if (! isset($porId[$ponta])) {
            return $reprova('ponta', "{$ligacao}: a ponta ".class_basename($ponta)." ({$ponta}) não é peça do modelo");
        }
    }

    $caminho = (string) $arquivo;
    $texto   = (string) $trecho;
    $aceita  = static fn (string $forma): array => ['aceita' => true, 'motivo' => null, 'regra' => null, 'forma' => $forma];

    // Arquivo da origem, com o nome da peça usada.
    if (arquivoDaClasse($de) === $caminho && mapaModeloNomeia($texto, $nomePara)) {
        return $aceita('arquivo da origem');
    }

    // Trait ou concern que a origem usa.
    if (class_exists($de)) {
        foreach (class_uses_recursive($de) as $trait) {
            if (arquivoDaClasse($trait) === $caminho && mapaModeloNomeia($texto, $nomePara)) {
                return $aceita('trait da origem');
            }
        }
    }

    // Provider do painel, nas ligações do painel.
    if (str_starts_with($de, 'painel:')) {
        $painel = substr($de, 7);

        if ($caminho === 'app/Providers/Filament/'.ucfirst($painel).'PanelProvider.php') {
            $destinoArquivo = arquivoDaClasse($para);

            if (preg_match_all('/->discover\w+\(\s*in:\s*app_path\(\'([^\']+)\'\)/', $texto, $m) > 0) {
                foreach ($m[1] as $pasta) {
                    if ($destinoArquivo !== null && str_starts_with($destinoArquivo, 'app/'.trim($pasta, '/').'/')) {
                        return $aceita('descoberta por pasta');
                    }
                }
            }

            if (mapaModeloNomeia($texto, $nomePara)) {
                return $aceita('registro pelo nome da peça');
            }

            if (preg_match('/(\w+)::make\(\)/', $texto, $m) === 1) {
                foreach ($modelo['pecas'] as $candidata) {
                    if ($candidata['tipo'] === 'plugin' && class_basename($candidata['id']) === $m[1] && mapaModeloPluginTraz($candidata['id'], $para, $painel)) {
                        return $aceita('registro do plugin que traz a peça');
                    }
                }
            }

            // Invólucro do kit que registra o plugin (step 9, A9): `X::make()` ou `X::plugin()`, com `X` de `app/`.
            if (preg_match_all('/(\w+)::(?:make|plugin)\(\)/', $texto, $chamadas) > 0) {
                foreach ($chamadas[1] as $curto) {
                    if (mapaModeloInvolucroNomeia((string) mapaModeloCodigo($caminho), $curto, $para)) {
                        return $aceita('invólucro do kit que registra o plugin');
                    }
                }
            }

            if ($para === "panel:{$painel}" && str_contains($texto, "->id('{$painel}')")) {
                return $aceita('configuração do painel (alias)');
            }

            if ($nomePara === 'IdentifyTenant' && str_contains($texto, '->tenant(')) {
                return $aceita('configuração do painel (organização)');
            }
        }
    }

    // Configuração global do pacote que registra a peça sozinho (step 9, A9): `Y::configureUsing(` num arquivo de
    // `app/Providers` ou num trait dele, com `Y` classe do mesmo pacote da peça.
    if (str_starts_with($de, 'painel:') && mapaModeloArquivoDeProvider($caminho) && preg_match_all('/(\w+)::configureUsing\(/', $texto, $chamadas) > 0) {
        $pacoteDaPeca = mapaModeloPacoteDaClasse($para);

        foreach ($chamadas[1] as $curto) {
            $classe = mapaModeloResolverNomeCurto((string) mapaModeloCodigo($caminho), $curto);

            if ($pacoteDaPeca !== null && $classe !== null && mapaModeloPacoteDaClasse($classe) === $pacoteDaPeca) {
                return $aceita('configuração global do pacote');
            }
        }
    }

    // Método de contrato do alvo.
    if (arquivoDaClasse($para) === $caminho && preg_match('/function\s+('.implode('|', MAPA_MODELO_METODOS_DE_CONTRATO).')\s*\(/', $texto) === 1) {
        return $aceita('método de contrato do alvo');
    }

    // Arquivo do kit que liga a peça de pacote (D26) e o par de mapeamento (D27).
    if (($porId[$de]['origem'] ?? null) === 'pacote' && ! str_starts_with($caminho, 'vendor/')) {
        if (preg_match('/^\s*\w+::class\s*=>\s*\w+::class,?\s*$/', $texto) === 1) {
            if (mapaModeloNomeia($texto, $nomeDe) && mapaModeloNomeia($texto, $nomePara)) {
                return $aceita('par de mapeamento');
            }
        } elseif (mapaModeloNomeia($texto, $nomePara) && mapaModeloNomeia((string) mapaModeloCodigo($caminho), $nomeDe)) {
            return $aceita('arquivo do kit que liga a peça de pacote');
        }
    }

    return $reprova('forma', "{$ligacao}: o arquivo {$caminho} não é um arquivo que esta ligação permita com o trecho dado");
}

/*
|--------------------------------------------------------------------------
| Helpers: a F-nn de cada peça e de cada classe (R28)
|--------------------------------------------------------------------------
| A F-nn da peça (`fnnDaPeca()`) e o motivo de enfeite (`motivoDeEnfeite()`) vêm de `tests/Pest.php`.
*/

/**
 * Os comandos `kit:*`, por classe, que nunca entram na lista das sem feature própria (ADV2-18).
 *
 * @return array<string, string>
 */
function mapaModeloComandosKit(): array
{
    static $comandos = null;

    if ($comandos === null) {
        $comandos = [];

        foreach (Artisan::all() as $nome => $comando) {
            if (str_starts_with($nome, 'kit:')) {
                $comandos[$comando::class] = $nome;
            }
        }
    }

    return $comandos;
}

/**
 * Os arquivos PHP de `app/` fora de `app/Filament`, como classe (PSR-4), lidos da árvore.
 *
 * @return list<string>
 */
function mapaModeloClassesDeApp(): array
{
    static $classes = null;

    if ($classes === null) {
        $classes  = [];
        $raiz     = str_replace('\\', '/', app_path()).'/';
        $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS));

        foreach ($iterador as $arquivo) {
            $relativo = substr(str_replace('\\', '/', $arquivo->getPathname()), strlen($raiz));

            if (! str_ends_with($relativo, '.php') || str_starts_with($relativo, 'Filament/')) {
                continue;
            }

            $classes[] = 'App\\'.str_replace('/', '\\', substr($relativo, 0, -4));
        }

        sort($classes);
    }

    return $classes;
}

/**
 * R28: cada peça (ou classe) entrega ao menos uma F-nn que o catálogo tem, ou está na lista com o motivo; e
 * comando `kit:*` nunca está na lista.
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  list<string>  $ids
 * @param  list<string>  $catalogo
 * @param  array<string, mixed>  $lista
 * @return array<string, mixed>
 */
function mapaModeloConferirFeatures(array $modelo, array $ids, array $catalogo, array $lista): array
{
    $porId     = pecasPorId($modelo['pecas']);
    $kit       = mapaModeloComandosKit();
    $violacoes = [];

    foreach ($lista as $id => $motivo) {
        if (motivoDeEnfeite($motivo)) {
            $violacoes[] = class_basename($id)." ({$id}) está na lista das sem feature própria sem motivo, ou com motivo de enfeite";
        }

        if (isset($kit[$id])) {
            $violacoes[] = 'o comando '.class_basename($id)." ({$kit[$id]}) é kit:* e nunca entra na lista das sem feature própria";
        }
    }

    foreach ($ids as $id) {
        if (! isset($porId[$id])) {
            $violacoes[] = class_basename($id)." ({$id}) não é peça do modelo, nem está na lista";

            continue;
        }

        if (array_key_exists($id, $lista)) {
            continue;
        }

        if (fnnDaPeca($porId, $id, $catalogo) === []) {
            $violacoes[] = 'a peça '.class_basename($id)." ({$id}) não entrega F-nn que o catálogo tenha (features: [".implode(', ', $porId[$id]['features'] ?? []).']) e está fora da lista';
        }
    }

    return mapaModeloResultado($violacoes, ['conferidas' => count($ids)]);
}

/**
 * Uma cópia do modelo com a peça trocada.
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @return array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}
 */
function mapaModeloComPeca(array $modelo, string $id, callable $troca): array
{
    foreach ($modelo['pecas'] as $i => $peca) {
        if ($peca['id'] === $id) {
            $modelo['pecas'][$i] = $troca($peca);
        }
    }

    return $modelo;
}

/*
|--------------------------------------------------------------------------
| Helpers: âncora dos fluxos (R35)
|--------------------------------------------------------------------------
*/

/**
 * Onde o trecho está no código (fora de comentário) e em que método.
 *
 * @param  array<string, string>  $sobrescritas
 * @return array{pos: int, metodo: ?string}|null
 */
function mapaModeloPosicaoDoTrecho(string $arquivo, string $trecho, array $sobrescritas = []): ?array
{
    $codigo = mapaModeloCodigo($arquivo, $sobrescritas);
    $pos    = $codigo === null ? false : strpos($codigo, mapaModeloNorm($trecho));

    if ($pos === false) {
        return null;
    }

    preg_match_all('/function\s+&?\s*(\w+)\s*\(/', substr($codigo, 0, $pos), $m);

    return ['pos' => $pos, 'metodo' => $m[1] === [] ? null : end($m[1])];
}

/**
 * As transições seguidas de um fluxo (R35), em pares de índices [primeira, segunda]. No `sequence`, a mensagem e
 * a que vem logo depois dela no tempo. No `workflow` e no `dataflow`, a seta e uma seta adiante na lista que sai
 * do passo onde ela chega: duas setas que saem do mesmo passo são ramos, e não seguidas.
 *
 * @param  array<int, array<string, mixed>>  $transicoes
 * @return list<array{0: int, 1: int}>
 */
function mapaModeloParesSeguidos(string $tipo, array $transicoes): array
{
    $indices = array_keys($transicoes);
    $pares   = [];

    foreach ($indices as $posicao => $i) {
        if ($tipo === 'sequence') {
            if (isset($indices[$posicao + 1])) {
                $pares[] = [$i, $indices[$posicao + 1]];
            }

            continue;
        }

        foreach (array_slice($indices, $posicao + 1) as $j) {
            if (isset($transicoes[$j]['from'], $transicoes[$i]['to']) && $transicoes[$j]['from'] === $transicoes[$i]['to']) {
                $pares[] = [$i, $j];
            }
        }
    }

    return $pares;
}

/**
 * R35: toda transição de fluxo tem âncora que resolve (R9); no `lifecycle`, todo estado também; e duas
 * transições seguidas, ancoradas no mesmo método, estão na ordem do código.
 *
 * @param  array<string, array<string, mixed>>  $diagramas
 * @param  array<string, string>  $sobrescritas
 * @return array<string, mixed>
 */
function mapaModeloConferirFluxos(array $diagramas, array $sobrescritas = []): array
{
    $colecoes   = ['workflow' => 'edges', 'sequence' => 'messages', 'dataflow' => 'flows', 'lifecycle' => 'transitions'];
    $violacoes  = [];
    $conferidas = 0;

    foreach ($diagramas as $slug => $diagrama) {
        $tipo = $diagrama['tipo'] ?? null;

        if (! isset($colecoes[$tipo])) {
            continue;
        }

        $posicoes = [];
        $nomes    = [];

        foreach ($diagrama[$colecoes[$tipo]] ?? [] as $i => $transicao) {
            $nomes[$i]    = "o diagrama {$slug}, transição ".($transicao['from'] ?? '?').' → '.($transicao['to'] ?? '?').(isset($transicao['id']) ? " ({$transicao['id']})" : '');
            $posicoes[$i] = null;
            $conferidas++;

            $ancora = $transicao['prova'] ?? null;

            if (! is_array($ancora)) {
                $violacoes[] = "{$nomes[$i]} não tem âncora";

                continue;
            }

            $resultado = mapaModeloConferirProva($nomes[$i], $ancora['arquivo'] ?? null, $ancora['trecho'] ?? null, array_intersect_key($ancora, ['linha' => 1, 'line' => 1]), $sobrescritas);

            if (! $resultado['aceita']) {
                $violacoes[] = $resultado['motivo'];

                continue;
            }

            $posicoes[$i] = ['arquivo' => $ancora['arquivo']] + (mapaModeloPosicaoDoTrecho($ancora['arquivo'], $ancora['trecho'], $sobrescritas) ?? ['pos' => -1, 'metodo' => null]);
        }

        if ($tipo === 'lifecycle') {
            foreach ($diagrama['states'] ?? [] as $estado) {
                $conferidas++;
                $nome = "o diagrama {$slug}, estado ".($estado['id'] ?? '?');

                if (($estado['sources'] ?? []) === []) {
                    $violacoes[] = "{$nome} não tem âncora";

                    continue;
                }

                foreach ($estado['sources'] as $fonte) {
                    $resultado = mapaModeloConferirProva($nome, $fonte['arquivo'] ?? null, $fonte['trecho'] ?? null, array_intersect_key($fonte, ['linha' => 1, 'line' => 1]), $sobrescritas);

                    if (! $resultado['aceita']) {
                        $violacoes[] = $resultado['motivo'];
                    }
                }
            }

            continue;
        }

        foreach (mapaModeloParesSeguidos($tipo, $diagrama[$colecoes[$tipo]] ?? []) as [$i, $j]) {
            $a = $posicoes[$i] ?? null;
            $b = $posicoes[$j] ?? null;

            if ($a === null || $b === null || $a['arquivo'] !== $b['arquivo'] || $a['metodo'] === null || $a['metodo'] !== $b['metodo']) {
                continue;
            }

            if ($a['pos'] > $b['pos']) {
                $violacoes[] = "{$nomes[$i]} vem antes de {$nomes[$j]}, mas o trecho dela está depois no método {$a['metodo']} de {$a['arquivo']}";
            }
        }
    }

    return mapaModeloResultado($violacoes, ['conferidas' => $conferidas]);
}

/*
|--------------------------------------------------------------------------
| Helpers: o desenho está no modelo (R36)
|--------------------------------------------------------------------------
*/

/**
 * As arestas de um diagrama de mapa ou de árvore de registro: as que o gerador deriva do modelo (o recorte
 * do painel e do modo, com as pontas nos agrupamentos) e as que a cópia traz em `arestas`. Cada uma diz o
 * que representa: as ligações do modelo.
 *
 * @param  array<string, mixed>  $diagrama
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @return list<array{de: string, para: string, representa: list<array{de: string, para: string}>}>
 */
function mapaModeloArestasDoDiagrama(array $diagrama, array $modelo): array
{
    $porId      = pecasPorId($modelo['pecas']);
    $arestas    = [];
    $construtor = $diagrama['construtor'] ?? null;

    if ($construtor === 'mapa') {
        $modo       = count($diagrama['modos'] ?? []) === 1 ? $diagrama['modos'][0] : null;
        $existe     = static fn (string $id): bool => $modo === null || in_array($modo, $porId[$id]['registrada'] ?? [], true);
        $pecas      = array_values(array_filter($diagrama['pecas'] ?? [], static fn (array $p): bool => $existe($p['id'])));
        $desenhadas = array_flip(array_column($pecas, 'id'));
        $grupoDe    = [];

        foreach ($diagrama['agrupamentos'] ?? [] as $grupo) {
            foreach ($grupo['membros'] as $membro) {
                if ($existe($membro)) {
                    $grupoDe[$membro] = $grupo['id'];
                }
            }
        }

        $ponta = static fn (string $id): ?string => isset($desenhadas[$id]) ? $id : ($grupoDe[$id] ?? null);

        foreach ($modelo['ligacoes'] as $ligacao) {
            if ($modo !== null && ! in_array($modo, $ligacao['modos'] ?? [], true)) {
                continue;
            }

            $doEscopo = ($diagrama['paineis'] ?? []) !== []
                ? array_intersect($ligacao['paineis'] ?? [], $diagrama['paineis']) !== []
                : ($ligacao['paineis'] ?? []) === [];

            if (! $doEscopo) {
                continue;
            }

            $de   = $ponta($ligacao['de']);
            $para = $ponta($ligacao['para']);

            if ($de === null || $para === null || $de === $para) {
                continue;
            }

            $arestas["{$de}--{$para}"] ??= ['de' => $de, 'para' => $para, 'representa' => []];
            $arestas["{$de}--{$para}"]['representa'][] = ['de' => $ligacao['de'], 'para' => $ligacao['para']];
        }
    } elseif ($construtor === 'registro') {
        $raiz = 'painel:'.$diagrama['paineis'][0];
        $modo = $diagrama['modos'][0];

        foreach ($modelo['ligacoes'] as $ligacao) {
            if ($ligacao['de'] === $raiz && $ligacao['verbo'] === 'registra' && in_array($modo, $ligacao['modos'] ?? [], true)) {
                $arestas[] = ['de' => $raiz, 'para' => $ligacao['para'], 'representa' => [['de' => $ligacao['de'], 'para' => $ligacao['para']]]];
            }
        }
    }

    foreach ($diagrama['arestas'] ?? [] as $aresta) {
        $arestas[] = $aresta + ['representa' => [['de' => $aresta['de'], 'para' => $aresta['para']]]];
    }

    return array_values($arestas);
}

/**
 * As peças que o diagrama desenha como peça do modelo: o id (mapa e árvore) ou o rótulo que tem o nome exato
 * de uma peça (fluxo e demais). Cada item traz as peças candidatas.
 *
 * @param  array<string, mixed>  $diagrama
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @return list<array{rotulo: string, candidatas: list<string>}>
 */
function mapaModeloPecasDesenhadas(array $diagrama, array $modelo): array
{
    $construtor = $diagrama['construtor'] ?? null;

    if ($construtor === 'mapa') {
        return array_map(static fn (array $p): array => ['rotulo' => class_basename($p['id']), 'candidatas' => [$p['id']]], $diagrama['pecas'] ?? []);
    }

    if ($construtor === 'registro') {
        $modo = $diagrama['modos'][0];
        $raiz = 'painel:'.$diagrama['paineis'][0];
        $ids  = [$raiz];

        foreach (mapaModeloArestasDoDiagrama($diagrama, $modelo) as $aresta) {
            $ids[] = $aresta['para'];
        }

        return array_map(static fn (string $id): array => ['rotulo' => class_basename($id), 'candidatas' => [$id]], array_values(array_unique($ids)));
    }

    $nomes = [];

    foreach ($modelo['pecas'] as $peca) {
        $nomes[$peca['nome']][] = $peca['id'];
    }

    $achadas = [];

    foreach (['nodes', 'participants', 'states', 'entities', 'types', 'events', 'spans', 'components'] as $colecao) {
        foreach ($diagrama[$colecao] ?? [] as $no) {
            $rotulo = $no['label'] ?? null;
            $rotulo = is_array($rotulo) ? ($rotulo['pt'] ?? null) : $rotulo;

            if (is_string($rotulo) && isset($nomes[$rotulo])) {
                $achadas[] = ['rotulo' => $rotulo, 'candidatas' => $nomes[$rotulo]];
            }
        }
    }

    return $achadas;
}

/**
 * As peças que o diagrama de um painel pode desenhar: as do painel, as do kit inteiro e as pontas das
 * ligações que partem do painel ou das peças dele (`04`, R36).
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @return array<string, true>
 */
function mapaModeloPecasDoPainel(array $modelo, string $painel): array
{
    $permitidas = [];

    foreach ($modelo['pecas'] as $peca) {
        if (($peca['paineis'] ?? []) === [] || in_array($painel, $peca['paineis'], true)) {
            $permitidas[$peca['id']] = true;
        }
    }

    $do = $permitidas;

    foreach ($modelo['ligacoes'] as $ligacao) {
        if (isset($do[$ligacao['de']])) {
            $permitidas[$ligacao['para']] = true;
        }
    }

    return $permitidas;
}

/**
 * R36: no mapa de arquitetura e na árvore de registro, toda aresta é ligação do modelo no mesmo sentido
 * (direta, de agrupamento ou de porta); e todo diagrama de painel só desenha, como peça, peça do painel ou
 * do kit inteiro.
 *
 * @param  array<string, array<string, mixed>>  $diagramas
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  array<string, array<string, mixed>>  $mundo  os diagramas onde procurar o outro lado de uma porta
 * @return array<string, mixed>
 */
function mapaModeloConferirArestasEPecas(array $diagramas, array $modelo, array $mundo): array
{
    $violacoes         = [];
    $arestasConferidas = 0;
    $nosConferidos     = 0;
    $existeLigacao     = static fn (string $de, string $para): bool => array_any($modelo['ligacoes'], static fn (array $l): bool => $l['de'] === $de && $l['para'] === $para);

    foreach ($diagramas as $slug => $diagrama) {
        if (in_array($diagrama['construtor'] ?? null, ['mapa', 'registro'], true)) {
            $grupos = [];

            foreach ($diagrama['agrupamentos'] ?? [] as $grupo) {
                $grupos[$grupo['id']] = $grupo['membros'];
            }

            $portas = [];

            foreach ($diagrama['portas'] ?? [] as $porta) {
                $portas['porta-'.$porta['para']] = $porta['para'];
            }

            foreach (mapaModeloArestasDoDiagrama($diagrama, $modelo) as $aresta) {
                $arestasConferidas++;
                $nome = "no diagrama {$slug}, a aresta {$aresta['de']} → {$aresta['para']}";

                foreach ($aresta['representa'] as $representada) {
                    $ligacao = class_basename($representada['de']).' → '.class_basename($representada['para']);

                    if (! $existeLigacao($representada['de'], $representada['para'])) {
                        $invertida   = $existeLigacao($representada['para'], $representada['de']) ? ' (a aresta está invertida: a ligação do modelo vai no outro sentido)' : '';
                        $violacoes[] = "{$nome} representa a ligação {$ligacao}, que não existe no modelo{$invertida}";

                        continue;
                    }

                    foreach (['de', 'para'] as $campo) {
                        $ponta = $aresta[$campo];
                        $peca  = $representada[$campo];

                        if (isset($grupos[$ponta])) {
                            if (! in_array($peca, $grupos[$ponta], true)) {
                                $violacoes[] = "{$nome}: o agrupamento {$ponta} não nomeia a peça ".class_basename($peca);
                            }
                        } elseif (isset($portas[$ponta])) {
                            $desenhadas = array_column($mundo[$portas[$ponta]]['pecas'] ?? [], 'id');

                            if (! in_array($peca, $desenhadas, true)) {
                                $violacoes[] = "{$nome}: a porta {$ponta} leva ao mapa {$portas[$ponta]}, que não desenha a peça ".class_basename($peca);
                            }
                        } elseif ($ponta !== $peca) {
                            $violacoes[] = "{$nome} não confere com a ligação {$ligacao}";
                        }
                    }
                }
            }
        }

        if (($diagrama['paineis'] ?? []) === []) {
            continue;
        }

        $permitidas = [];

        foreach ($diagrama['paineis'] as $painel) {
            $permitidas += mapaModeloPecasDoPainel($modelo, $painel);
        }

        foreach (mapaModeloPecasDesenhadas($diagrama, $modelo) as $desenhada) {
            $nosConferidos++;

            if (array_intersect($desenhada['candidatas'], array_keys($permitidas)) === []) {
                $violacoes[] = "o diagrama {$slug} (painel ".implode(', ', $diagrama['paineis']).") desenha a peça {$desenhada['rotulo']}, que não é do painel nem do kit inteiro";
            }
        }
    }

    return mapaModeloResultado($violacoes, ['arestas' => $arestasConferidas, 'nos' => $nosConferidos]);
}

/*
|--------------------------------------------------------------------------
| Helpers: a marca de disponibilidade é a do código (R37)
|--------------------------------------------------------------------------
*/

/** O corpo de um método num código PHP, ou `null`. */
function mapaModeloCorpoDoMetodo(string $codigo, string $metodo): ?string
{
    if (preg_match('/function\s+'.preg_quote($metodo, '/').'\s*\([^)]*\)\s*(?::\s*[\w?\\\\|]+)?\s*\{/', $codigo, $m, PREG_OFFSET_CAPTURE) !== 1) {
        return null;
    }

    $inicio = $m[0][1] + strlen($m[0][0]);
    $nivel  = 1;

    for ($i = $inicio, $fim = strlen($codigo); $i < $fim; $i++) {
        if ($codigo[$i] === '{') {
            $nivel++;
        } elseif ($codigo[$i] === '}' && --$nivel === 0) {
            return substr($codigo, $inicio, $i - $inicio);
        }
    }

    return null;
}

/**
 * As chaves de config que o `canAccess()` da peça lê, direto ou pelo apoio estático que ele chama.
 *
 * @return list<string>
 */
function mapaModeloChavesQueDecidemOAcesso(string $classe): array
{
    $arquivo = arquivoDaClasse($classe);
    $codigo  = $arquivo === null ? null : mapaModeloCodigo($arquivo);
    $corpo   = $codigo === null ? null : mapaModeloCorpoDoMetodo($codigo, 'canAccess');

    if ($corpo === null) {
        return [];
    }

    preg_match_all('/config\(\s*\'([^\']+)\'/', $corpo, $diretas);
    $chaves = $diretas[1];

    preg_match_all('/\b([A-Z]\w*)::(\w+)\(/', $corpo, $apoios, PREG_SET_ORDER);
    $usos = mapaModeloUsosDoArquivo((string) $codigo);

    foreach ($apoios as [, $curto, $metodo]) {
        $apoio          = $usos[$curto] ?? null;
        $arquivoDoApoio = $apoio === null ? null : arquivoDaClasse($apoio);
        $codigoDoApoio  = $arquivoDoApoio === null ? null : mapaModeloCodigo($arquivoDoApoio);
        $corpoDoApoio   = $codigoDoApoio === null ? null : mapaModeloCorpoDoMetodo($codigoDoApoio, $metodo);

        if ($corpoDoApoio !== null) {
            preg_match_all('/config\(\s*\'([^\']+)\'/', $corpoDoApoio, $doApoio);
            $chaves = array_merge($chaves, $doApoio[1]);
        }
    }

    return array_values(array_unique($chaves));
}

/**
 * A variável do `.env` e o valor de fábrica de uma chave de `config/kit.php` (`kit.a.b`), pelo texto do
 * arquivo — nunca pelo `config()`, que mede o ambiente de quem roda.
 *
 * @return array{variavel: string, padrao: mixed}|null
 */
function mapaModeloVariavelEPadraoDaChave(string $chave): ?array
{
    $caminho = explode('.', $chave);

    if (array_shift($caminho) !== 'kit' || $caminho === []) {
        return null;
    }

    $fonte        = (string) mapaModeloCodigo('config/kit.php');
    $deslocamento = 0;

    foreach ($caminho as $i => $segmento) {
        $ultimo = $i === array_key_last($caminho);
        $padrao = $ultimo ? '/\''.preg_quote($segmento, '/').'\'\s*=>\s*([^\n]*)/' : '/\''.preg_quote($segmento, '/').'\'\s*=>\s*\[/';

        if (preg_match($padrao, $fonte, $m, PREG_OFFSET_CAPTURE, $deslocamento) !== 1) {
            return null;
        }

        if ($ultimo) {
            if (preg_match('/env\(\s*\'([A-Z0-9_]+)\'\s*(?:,\s*([^)]*?))?\s*\)/', $m[1][0], $env) !== 1) {
                return null;
            }

            $bruto = trim($env[2] ?? 'null');

            return ['variavel' => $env[1], 'padrao' => match (true) {
                $bruto === 'true'  => true,
                $bruto === 'false' => false,
                is_numeric($bruto) => $bruto + 0,
                default            => trim($bruto, '\'"'),
            }];
        }

        $deslocamento = $m[0][1] + strlen($m[0][0]);
    }

    return null;
}

/**
 * As marcas de disponibilidade publicadas: o campo `disponibilidade` de uma peça do modelo ou de um nó de
 * diagrama.
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  array<string, array<string, mixed>>  $diagramas
 * @return list<array{peca: string, onde: string, marca: array<string, mixed>}>
 */
function mapaModeloMarcasDeDisponibilidade(array $modelo, array $diagramas): array
{
    $marcas = [];

    foreach ($modelo['pecas'] as $peca) {
        if (isset($peca['disponibilidade'])) {
            $marcas[] = ['peca' => $peca['id'], 'onde' => 'o modelo', 'marca' => $peca['disponibilidade']];
        }
    }

    $varrer = static function (mixed $no, string $slug) use (&$varrer, &$marcas): void {
        if (! is_array($no)) {
            return;
        }

        if (isset($no['disponibilidade'], $no['id'])) {
            $marcas[] = ['peca' => (string) $no['id'], 'onde' => "o diagrama {$slug}", 'marca' => $no['disponibilidade']];
        }

        foreach ($no as $filho) {
            $varrer($filho, $slug);
        }
    };

    foreach ($diagramas as $slug => $diagrama) {
        $varrer($diagrama, $slug);
    }

    return $marcas;
}

/**
 * R37: toda marca de disponibilidade publicada é a do código — a chave que o `canAccess()` lê, com o valor de
 * fábrica dela, ou o modo. Sem marca nenhuma, aceita por vácuo.
 *
 * @param  array{pecas: list<array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  array<string, array<string, mixed>>  $diagramas
 * @return array<string, mixed>
 */
function mapaModeloConferirDisponibilidade(array $modelo, array $diagramas): array
{
    $violacoes = [];
    $marcas    = mapaModeloMarcasDeDisponibilidade($modelo, $diagramas);

    foreach ($marcas as ['peca' => $peca, 'onde' => $onde, 'marca' => $marca]) {
        $nome   = class_basename($peca);
        $chaves = mapaModeloChavesQueDecidemOAcesso($peca);

        if (isset($marca['modo'])) {
            $decideOModo = in_array('kit.tenancy.enabled', $chaves, true);

            if (! $decideOModo || $marca['modo'] !== 'com') {
                $violacoes[] = "{$onde} marca a peça {$nome} como disponível no modo \"{$marca['modo']}\", e o canAccess() dela lê [".implode(', ', $chaves).']';
            }

            continue;
        }

        $decisoras = [];

        foreach ($chaves as $chave) {
            $dados = mapaModeloVariavelEPadraoDaChave($chave);

            if ($dados !== null) {
                $decisoras[$dados['variavel']] = $dados['padrao'];
                $decisoras[$chave]             = $dados['padrao'];
                $decisoras[substr($chave, 4)]  = $dados['padrao'];
            }
        }

        $chaveDaMarca = (string) ($marca['chave'] ?? '');

        if (! array_key_exists($chaveDaMarca, $decisoras)) {
            $violacoes[] = "{$onde} marca a peça {$nome} com a chave {$chaveDaMarca}, que não decide o acesso dela (decidem: ".implode(', ', array_keys($decisoras)).')';

            continue;
        }

        if (array_key_exists('padrao', $marca) && $marca['padrao'] !== $decisoras[$chaveDaMarca]) {
            $violacoes[] = "{$onde} marca a peça {$nome} com {$chaveDaMarca} de fábrica ".var_export($marca['padrao'], true).', e o config/kit.php diz '.var_export($decisoras[$chaveDaMarca], true);
        }
    }

    return mapaModeloResultado($violacoes, ['marcas' => count($marcas)]);
}

/*
|--------------------------------------------------------------------------
| [CT-08] R8 — toda peça do modelo existe, tem o tipo da sua classe e aparece uma vez
|--------------------------------------------------------------------------
*/

it('[CT-08] toda peça do modelo existe no código, é de um tipo do conjunto fechado e aparece uma vez', function (string $mundo, string $resultado, string $nomeando, string $nomeandoTambem): void {
    $pecas  = modeloDoMapa()['pecas'];
    $indice = static fn (string $id): int => (int) array_search($id, array_column($pecas, 'id'), true);

    switch ($mundo) {
        case 'classe inexistente':
            $pecas[] = ['id' => 'App\\Filament\\Admin\\Resources\\Inexistente\\InexistenteResource', 'nome' => 'InexistenteResource', 'tipo' => 'recurso', 'paineis' => ['admin'], 'registrada' => ['sem', 'com'], 'origem' => 'proprio', 'features' => [], 'evidencia' => []];
            break;
        case 'tipo rota':
            $pecas[$indice('App\\Models\\Convite')]['tipo'] = 'rota';
            break;
        case 'user como recurso':
            $pecas[$indice('App\\Models\\User')]['tipo'] = 'recurso';
            break;
        case 'user em duas peças':
            $pecas[] = $pecas[$indice('App\\Models\\User')];
            break;
        case 'pacote não instalado':
            $pecas[$indice('BezhanSalleh\\FilamentShield\\FilamentShieldPlugin')]['pacote'] = 'vendor-inexistente/plugin';
            break;
        case 'painel financeiro':
            $pecas[] = ['id' => 'painel:financeiro', 'nome' => '/financeiro', 'tipo' => 'painel', 'paineis' => ['financeiro'], 'registrada' => ['sem', 'com'], 'origem' => 'proprio', 'features' => [], 'evidencia' => []];
            break;
    }

    $guarda = mapaModeloConferirPecas($pecas);

    if ($resultado === 'aceita') {
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);
        expect($guarda['conferidas'])->toBeGreaterThan(0);

        foreach (array_keys(Filament::getPanels()) as $painel) {
            $peca = pecasPorId($pecas)["painel:{$painel}"] ?? null;

            expect($peca)->not->toBeNull("o painel {$painel} do registro não é peça do modelo")
                ->and($peca['tipo'])->toBe('painel');
        }

        return;
    }

    expect($guarda['aceita'])->toBeFalse('a guarda aceitou a cópia adulterada: '.$mundo);
    expect($guarda['motivo'])->toContain($nomeando);

    if ($nomeandoTambem !== '') {
        expect($guarda['motivo'])->toContain($nomeandoTambem);
    }
})->with([
    'publicado'                              => ['publicado', 'aceita', '', ''],
    'cópia com a classe inexistente'         => ['classe inexistente', 'reprova', 'App\\Filament\\Admin\\Resources\\Inexistente\\InexistenteResource', ''],
    'cópia com uma peça do tipo rota'        => ['tipo rota', 'reprova', 'rota', ''],
    'cópia com User do tipo recurso'         => ['user como recurso', 'reprova', 'App\\Models\\User', 'recurso'],
    'cópia com User em duas peças'           => ['user em duas peças', 'reprova', 'App\\Models\\User', ''],
    'cópia com peça de pacote não instalado' => ['pacote não instalado', 'reprova', 'vendor-inexistente/plugin', ''],
    'cópia com a peça painel financeiro'     => ['painel financeiro', 'reprova', 'financeiro', ''],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-09] R9 — toda prova resolve no commit que publica
|--------------------------------------------------------------------------
*/

it('[CT-09] a prova de toda ligação resolve no commit que publica: o arquivo é versionado, o trecho está nele, fora de comentário, e a prova não guarda número de linha', function (string $mundo, string $nome, string $arquivo, string $trecho, string $resultado, string $nomeando): void {
    $modelo    = modeloDoMapa();
    $diagramas = diagramasDoMapa();

    if ($mundo === 'publicado') {
        $guarda = mapaModeloConferirProvas($modelo, $diagramas);

        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);
        // a guarda conferiu a prova de tantas ligações quantas o modelo tem, e de ao menos uma de cada painel
        expect($guarda['conferidas'])->toBe(count($modelo['ligacoes']))
            ->and(count($modelo['ligacoes']))->toBeGreaterThan(0);

        // o piso vem de fora do modelo: o registro vivo dos painéis
        $registradas = 0;

        foreach (array_keys(Filament::getPanels()) as $painel) {
            $registradas += count(array_merge(...array_values(registroVivoDoPainel(Filament::getPanel($painel)))));

            expect($guarda['porPainel'][$painel] ?? 0)->toBeGreaterThanOrEqual(1, "nenhuma ligação do painel {$painel} foi conferida");
        }

        expect($guarda['conferidas'])->toBeGreaterThanOrEqual($registradas);

        // a guarda das chaves fechadas (A10) olhou a evidência de toda peça do modelo de peças
        expect($guarda['evidencias'])->toBe(array_sum(array_map(static fn (array $peca): int => count($peca['evidencia'] ?? []), $modelo['pecas'])))
            ->and($guarda['evidencias'])->toBeGreaterThanOrEqual(count($modelo['pecas']));

        return;
    }

    if (str_ends_with($mundo, 'na evidência de peça')) {
        // step 9, achado A10: a chave a mais na evidência do `UserResource` do `/admin`
        $chave  = explode(' ', $mundo)[0];
        $peca   = 'App\\Filament\\Admin\\Resources\\Users\\UserResource';
        $indice = array_search($peca, array_column($modelo['pecas'], 'id'), true);

        expect($indice)->not->toBeFalse("{$peca} deveria ser peça do modelo")
            ->and($modelo['pecas'][$indice]['evidencia'][0]['arquivo'] ?? null)->toBe('app/Filament/Admin/Resources/Users/UserResource.php');

        $modelo['pecas'][$indice]['evidencia'][0][$chave] = ['line' => 240, 'end_line' => 241, 'path' => 'app/Models/User.php'][$chave];
        $guarda                                           = mapaModeloConferirProvas($modelo, []);

        expect($guarda['aceita'])->toBeFalse("a guarda aceitou a chave {$chave} na evidência da peça")
            ->and($guarda['motivo'])->toContain($peca)
            ->and($guarda['motivo'])->toContain("`{$chave}`");

        return;
    }

    if ($mundo === 'campo linha no modelo') {
        $modelo['ligacoes'][0]['evidencia']['linha'] = 240;
        $guarda                                      = mapaModeloConferirProvas($modelo, []);

        expect($guarda['aceita'])->toBeFalse('a guarda aceitou o campo linha no modelo')
            ->and($guarda['motivo'])->toContain(class_basename($modelo['ligacoes'][0]['de']));

        return;
    }

    if ($mundo === 'linha nas provas do diagrama') {
        $copia                                          = $diagramas['login'];
        $copia['participants'][1]['sources'][0]['line'] = 240;
        $guarda                                         = mapaModeloConferirProvas(['pecas' => [], 'ligacoes' => []], ['login' => $copia]);

        expect($guarda['aceita'])->toBeFalse('a guarda aceitou a linha nas provas do diagrama')
            ->and($guarda['motivo'])->toContain('login')
            ->and($guarda['motivo'])->toContain($copia['participants'][1]['id']);

        return;
    }

    $sobrescritas = [];

    if ($mundo === 'sem a linha do trecho') {
        $sobrescritas[$arquivo] = str_replace($trecho, '', (string) file_get_contents(base_path($arquivo)));
    }

    if ($mundo === 'arquivo fora do git') {
        expect(is_file(base_path($arquivo)))->toBeTrue("{$arquivo} deveria existir no disco (o Composer o gera)");
        preg_match("/^\\s*'[^']+' =>/m", (string) file_get_contents(base_path($arquivo)), $m);
        $trecho = trim($m[0]);
    }

    $guarda = mapaModeloConferirProva("a ligação \"{$nome}\"", $arquivo, $mundo === 'sem trecho' ? null : $trecho, [], $sobrescritas);

    if ($resultado === 'aceita') {
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

        return;
    }

    expect($guarda['aceita'])->toBeFalse("a guarda aceitou a prova ({$mundo})")
        ->and($guarda['motivo'])->toContain($nomeando);
})->with([
    'publicado'                             => ['publicado', '', '', '', 'aceita', ''],
    'prova válida: arquivo versionado'      => ['valida', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPanelProvider.php', 'FilamentShieldPlugin::make()', 'aceita', ''],
    'sem trecho'                            => ['sem trecho', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPanelProvider.php', '', 'reprova', 'o painel admin registra o plugin do Shield'],
    'trecho vazio'                          => ['trecho vazio', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPanelProvider.php', '', 'reprova', 'o painel admin registra o plugin do Shield'],
    'trecho só de espaços'                  => ['trecho só de espaços', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPanelProvider.php', '   ', 'reprova', 'o painel admin registra o plugin do Shield'],
    'trecho que saiu do código'             => ['sem a linha do trecho', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPanelProvider.php', 'FilamentShieldPlugin::make()', 'reprova', 'o painel admin registra o plugin do Shield'],
    'trecho só em comentário'               => ['comentario', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPanelProvider.php', 'ver a nota longa no AppPanelProvider', 'reprova', 'o painel admin registra o plugin do Shield'],
    'arquivo inexistente'                   => ['inexistente', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPainelProvider.php', 'FilamentShieldPlugin::make()', 'reprova', 'app/Providers/Filament/AdminPainelProvider.php'],
    'arquivo fora do git'                   => ['arquivo fora do git', 'o painel admin registra o plugin do Shield', 'bootstrap/cache/packages.php', '', 'reprova', 'bootstrap/cache/packages.php'],
    'arquivo de pacote, fora do commit'     => ['vendor', 'o painel infra registra a página `HealthCheckResults`', 'vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php', '$panel->pages([$this->getPage()]);', 'reprova', 'vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php'],
    'prova com número de linha'             => ['linha no caminho', 'o painel admin registra o plugin do Shield', 'app/Providers/Filament/AdminPanelProvider.php:240', 'FilamentShieldPlugin::make()', 'reprova', 'o painel admin registra o plugin do Shield'],
    'trecho só em comentário Blade'         => ['blade', 'a página ConvitesRecebidos', 'resources/views/filament/pages/convites-recebidos.blade.php', 'ConvitesRecebidos::table()', 'reprova', 'a página ConvitesRecebidos'],
    'linha num campo do modelo'             => ['campo linha no modelo', '', '', '', 'reprova', ''],
    'linha nas provas do diagrama'          => ['linha nas provas do diagrama', '', '', '', 'reprova', ''],
    'linha na evidência de peça'            => ['line na evidência de peça', '', '', '', 'reprova', ''],
    'fim de intervalo na evidência de peça' => ['end_line na evidência de peça', '', '', '', 'reprova', ''],
    'outro caminho na evidência de peça'    => ['path na evidência de peça', '', '', '', 'reprova', ''],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-10] R10 — a prova é a da ligação
|--------------------------------------------------------------------------
*/

it('[CT-10] a prova é a da ligação: está num arquivo que a ligação permite, e o trecho é o dela', function (string $de, string $para, string $arquivo, string $trecho, string $resultado, string $regra, string $nomeando, bool $semUser): void {
    $modelo = modeloDoMapa();

    if ($de === 'publicado') {
        // step 9, achado A9: cada ligação publicada pela MESMA função das linhas de controle
        $reprovadas = [];
        $conferidas = 0;

        foreach ($modelo['ligacoes'] as $ligacao) {
            $resultado = mapaModeloConferirFormaDaProva($modelo, $ligacao['de'], $ligacao['para'], $ligacao['evidencia']['arquivo'] ?? null, $ligacao['evidencia']['trecho'] ?? null);
            $conferidas++;

            if (! $resultado['aceita']) {
                $reprovadas[] = $resultado['motivo'];
            }
        }

        expect($conferidas)->toBe(count($modelo['ligacoes']))
            ->and($conferidas)->toBeGreaterThan(0)
            ->and($reprovadas)->toBe([], count($reprovadas).' de '.$conferidas.' ligações publicadas reprovam: '.implode(' | ', $reprovadas));

        return;
    }

    if ($semUser) {
        $modelo['pecas'] = array_values(array_filter($modelo['pecas'], static fn (array $p): bool => $p['id'] !== 'App\\Models\\User'));
    }

    $guarda = mapaModeloConferirFormaDaProva($modelo, $de, $para, $arquivo, $trecho);

    if ($resultado === 'aceita') {
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

        return;
    }

    expect($guarda['aceita'])->toBeFalse('a guarda aceitou a prova de outra ligação')
        ->and($guarda['regra'])->toBe($regra, (string) $guarda['motivo'])
        ->and($guarda['motivo'])->toContain($nomeando);
})->with(function (): array {
    $admin            = 'App\\Filament\\Admin\\Resources\\Users\\UserResource';
    $user             = 'App\\Models\\User';
    $tenant           = 'App\\Models\\Tenant';
    $health           = 'ShuvroRoy\\FilamentSpatieLaravelHealth\\Pages\\HealthCheckResults';
    $healthPlugin     = 'ShuvroRoy\\FilamentSpatieLaravelHealth\\FilamentSpatieLaravelHealthPlugin';
    $providerAdmin    = 'app/Providers/Filament/AdminPanelProvider.php';
    $providerApp      = 'app/Providers/Filament/AppPanelProvider.php';
    $providerInfra    = 'app/Providers/Filament/InfraPanelProvider.php';
    $trait            = 'app/Traits/BelongsToTenant.php';
    $belongsTo        = 'return $this->belongsTo(Tenant::class);';
    $modelUser        = 'protected static ?string $model = User::class;';
    $locker           = 'lockscreen\\FilamentLockscreen\\Http\\Livewire\\LockerScreen';
    $bloqueio         = 'App\\Filament\\Pages\\Auth\\TelaBloqueio';
    $bind             = '$this->app->bind(LockerScreen::class, TelaBloqueio::class);';
    $politica         = 'App\\Policies\\ExceptionPolicy';
    $par              = 'Exception::class                      => ExceptionPolicy::class,';
    $mapeamento       = 'app/Support/PoliciesDeVendor.php';
    $tenantMiddleware = 'Filament\\Http\\Middleware\\IdentifyTenant';
    $lockerMiddleware = 'lockscreen\\FilamentLockscreen\\Http\\Middleware\\Locker';
    $mustTwoFactor    = 'Jeffgreco13\\FilamentBreezy\\Middleware\\MustTwoFactor';
    $dock             = 'HoceineEl\\QuickActionDock\\QuickActionDockPlugin';
    $onboarding       = 'Wallacemartinss\\FilamentOnboarding\\FilamentOnboardingPlugin';
    $idioma           = 'BezhanSalleh\\LanguageSwitch\\Http\\Middleware\\SwitchLanguageLocale';
    $global           = 'app/Providers/Concerns/ConfiguraFilamentGlobal.php';

    return [
        'aceita: plugin que traz o middleware (step 9)'                     => ['painel:admin', $lockerMiddleware, $providerAdmin, 'Lockscreen::make()', 'aceita', '', '', false],
        'aceita: invólucro do kit, o dock (step 9)'                         => ['painel:admin', $dock, $providerAdmin, 'PluginDoDock::make(),', 'aceita', '', '', false],
        'aceita: invólucro do kit, o tutorial (step 9)'                     => ['painel:admin', $onboarding, $providerAdmin, 'TutorialGuiado::plugin(),', 'aceita', '', '', false],
        'aceita: configuração global do pacote (step 9)'                    => ['painel:admin', $idioma, $global, 'LanguageSwitch::configureUsing(', 'aceita', '', '', false],
        'reprova: plugin que não traz o middleware (step 9)'                => ['painel:admin', $mustTwoFactor, $providerAdmin, 'Lockscreen::make()', 'reprova', 'forma', 'painel:admin → MustTwoFactor', false],
        'reprova: invólucro cujo arquivo não nomeia a peça (step 9)'        => ['painel:admin', $dock, $providerAdmin, 'TutorialGuiado::plugin(),', 'reprova', 'forma', 'painel:admin → QuickActionDockPlugin', false],
        'reprova: configuração global de classe de outro pacote (step 9)'   => ['painel:admin', $idioma, $global, 'Toggle::configureUsing(fn (Toggle $toggle): Toggle => $this->configuraToggle($toggle));', 'reprova', 'forma', 'painel:admin → SwitchLanguageLocale', false],
        'publicado: cada ligação do modelo publicado, uma por uma (step 9)' => ['publicado', '', '', '', 'aceita', '', '', false],

        'aceita: arquivo da origem'                           => [$admin, $user, 'app/Filament/Admin/Resources/Users/UserResource.php', $modelUser, 'aceita', '', '', false],
        'aceita: trait que a origem usa'                      => ['App\\Models\\Projeto', $tenant, $trait, $belongsTo, 'aceita', '', '', false],
        'aceita: descoberta que alcança a pasta'              => ['painel:admin', $admin, $providerAdmin, "->discoverResources(in: app_path('Filament/Admin/Resources')", 'aceita', '', '', false],
        'aceita: registro pelo plugin que traz a peça'        => ['painel:infra', $health, $providerInfra, 'FilamentSpatieLaravelHealthPlugin::make()', 'aceita', '', '', false],
        'aceita: configuração do painel (alias)'              => ['painel:admin', 'panel:admin', $providerAdmin, "->id('admin')", 'aceita', '', '', false],
        'aceita: configuração do painel (organização)'        => ['painel:app', $tenantMiddleware, $providerApp, "->tenant(Tenant::class, slugAttribute: 'slug')", 'aceita', '', '', false],
        'aceita: método de contrato do alvo'                  => ['painel:admin', $user, 'app/Models/User.php', 'public function canAccessPanel(Panel $panel): bool', 'aceita', '', '', false],
        'aceita: arquivo do kit que liga a peça de pacote'    => [$locker, $bloqueio, 'app/Providers/AppServiceProvider.php', $bind, 'aceita', '', '', false],
        'aceita: par de mapeamento, a linha inteira'          => ['BezhanSalleh\\FilamentExceptions\\Models\\Exception', $politica, $mapeamento, $par, 'aceita', '', '', false],
        'reprova: outro arquivo, que nomeia a peça'           => [$admin, $user, 'app/Filament/Exports/UserExporter.php', $modelUser, 'reprova', 'forma', 'UserResource → User', false],
        'reprova: descoberta que não alcança'                 => ['painel:infra', $admin, $providerInfra, "->discoverResources(in: app_path('Filament/Infra/Resources')", 'reprova', 'forma', 'painel:infra → UserResource', false],
        'reprova: registro de plugin que não traz a peça'     => ['painel:infra', $health, $providerInfra, 'FilamentJobsMonitorPlugin::make()', 'reprova', 'forma', 'painel:infra → HealthCheckResults', false],
        'reprova: configuração de outro painel (alias)'       => ['painel:admin', 'panel:admin', $providerApp, "->id('app')", 'reprova', 'forma', 'painel:admin → panel:admin', false],
        'reprova: configuração de outro painel (organização)' => ['painel:admin', $tenantMiddleware, $providerApp, "->tenant(Tenant::class, slugAttribute: 'slug')", 'reprova', 'forma', 'painel:admin → IdentifyTenant', false],
        'reprova: trait que a origem não usa'                 => [$admin, $tenant, $trait, $belongsTo, 'reprova', 'forma', 'UserResource → Tenant', false],
        'reprova: método fora da lista de contrato'           => ['painel:admin', 'App\\Models\\Convite', 'app/Models/Convite.php', 'public function situacao(): string', 'reprova', 'forma', 'painel:admin → Convite', false],
        'reprova: par que não nomeia a origem'                => ['Tapp\\FilamentMailLog\\Models\\MailLog', $politica, $mapeamento, $par, 'reprova', 'forma', 'MailLog → ExceptionPolicy', false],
        'reprova: prova de outra ligação'                     => ['painel:admin', 'BezhanSalleh\\FilamentShield\\FilamentShieldPlugin', $providerAdmin, 'AuthDesignerPlugin::make()', 'reprova', 'forma', 'painel:admin → FilamentShieldPlugin', false],
        'reprova: arquivo que não nomeia a origem'            => [$health, $bloqueio, 'app/Providers/AppServiceProvider.php', $bind, 'reprova', 'forma', 'HealthCheckResults → TelaBloqueio', false],
        'reprova: arquivo de vendor'                          => [$healthPlugin, $health, 'vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php', 'protected string $page = HealthCheckResults::class;', 'reprova', 'prova', 'vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php', false],
        'reprova: saída de comando'                           => [$locker, $bloqueio, 'a saída de `php artisan route:list`', 'a linha da rota da tela de bloqueio', 'reprova', 'prova', 'LockerScreen → TelaBloqueio', false],
        'reprova: nome só dentro de outro nome'               => [$admin, $user, 'app/Filament/Admin/Resources/Users/UserResource.php', 'class UserResource', 'reprova', 'forma', 'UserResource → User', false],
        'reprova: ponta que não é peça'                       => [$admin, $user, 'app/Filament/Admin/Resources/Users/UserResource.php', $modelUser, 'reprova', 'ponta', 'User', true],
    ];
})->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-11] R11 — sem tenant, tudo que o painel registra está no modelo
|--------------------------------------------------------------------------
*/

it('[CT-11] sem tenant, tudo que o painel registra está no modelo, e o modelo não dá a esse modo nada além disso', function (string $painel, array $registrado, array $fora): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse('esta suíte roda sem tenant: kit.tenancy.enabled lido como falso')
        ->and(Filament::getPanel($painel)->hasTenancy())->toBeFalse("o painel {$painel} não tem tenancy neste modo");

    $modelo = modeloDoMapa();
    $guarda = mapaModeloConferirRegistro($modelo, 'sem', [$painel]);

    expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

    $vivo     = array_merge(...array_values(registroVivoDoPainel(Filament::getPanel($painel))));
    $noModelo = registradasNoModelo($modelo, $painel, 'sem');
    $comNome  = static fn (array $ids, string $nome): array => array_values(array_filter($ids, static fn (string $id): bool => class_basename($id) === $nome));

    foreach ($registrado as $nome) {
        expect($comNome($vivo, $nome))->not->toBeEmpty("{$nome} não está no registro do painel {$painel}")
            ->and($comNome($noModelo, $nome))->not->toBeEmpty("{$nome} não está no modelo do painel {$painel}");
    }

    foreach ($fora as $nome) {
        expect($comNome($vivo, $nome))->toBeEmpty("{$nome} está no registro do painel {$painel}, e não devia")
            ->and($comNome($noModelo, $nome))->toBeEmpty("o modelo dá {$nome} ao painel {$painel} no modo sem tenant");
    }
})->with([
    'admin' => ['admin', ['FilamentShieldPlugin', 'panel:admin', 'TenantResource'], ['IdentifyTenant']],
    'app'   => ['app', ['FilamentSearchSpotlightPlugin', 'TelaLogin'], ['DefinirTenantDePermissoes', 'IdentifyTenant']],
    'infra' => ['infra', ['FilamentSpatieLaravelHealthPlugin', 'HealthCheckResults'], ['IdentifyTenant']],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-13] R11 — a guarda do registro reprova o modelo que esquece peça, que sobra peça ou que troca o modo
|--------------------------------------------------------------------------
*/

it('[CT-13] a guarda do registro reprova o modelo que esquece peça, que sobra peça ou que troca o modo', function (string $mundo, string $resultado, array $nomeando): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse('esta suíte roda sem tenant: kit.tenancy.enabled lido como falso');

    $modelo     = modeloDoMapa();
    $semLigacao = static fn (array $ligacoes, string $de, string $para): array => array_values(array_filter($ligacoes, static fn (array $l): bool => ! ($l['de'] === $de && $l['para'] === $para)));

    switch ($mundo) {
        case 'sem o Shield':
            $modelo['ligacoes'] = $semLigacao($modelo['ligacoes'], 'painel:admin', 'BezhanSalleh\\FilamentShield\\FilamentShieldPlugin');
            break;
        case 'Health no admin':
            $modelo['ligacoes'][] = ['de' => 'painel:admin', 'para' => 'ShuvroRoy\\FilamentSpatieLaravelHealth\\FilamentSpatieLaravelHealthPlugin', 'verbo' => 'registra', 'paineis' => ['admin'], 'modos' => ['sem', 'com'], 'evidencia' => ['arquivo' => 'app/Providers/Filament/AdminPanelProvider.php', 'trecho' => 'FilamentShieldPlugin::make()']];
            break;
        case 'organização no modo sem tenant':
            foreach ($modelo['ligacoes'] as $i => $ligacao) {
                if ($ligacao['de'] === 'painel:app' && $ligacao['para'] === 'App\\Http\\Middleware\\DefinirTenantDePermissoes') {
                    $modelo['ligacoes'][$i]['modos'] = ['sem', 'com'];
                }
            }
            break;
        case 'sem a TelaLogin':
            $modelo['ligacoes'] = $semLigacao($modelo['ligacoes'], 'painel:app', 'App\\Filament\\Pages\\Auth\\TelaLogin');
            break;
    }

    $guarda = mapaModeloConferirRegistro($modelo, 'sem');

    if ($resultado === 'aceita') {
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

        return;
    }

    expect($guarda['aceita'])->toBeFalse('a guarda aceitou o modelo adulterado: '.$mundo);

    foreach ($nomeando as $nome) {
        expect($guarda['motivo'])->toContain($nome);
    }
})->with([
    'publicado'                                    => ['publicado', 'aceita', []],
    'cópia sem a ligação do Shield'                => ['sem o Shield', 'reprova', ['FilamentShieldPlugin', 'painel admin']],
    'cópia que dá o Health ao admin'               => ['Health no admin', 'reprova', ['FilamentSpatieLaravelHealthPlugin', 'painel admin']],
    'cópia que dá a organização ao app sem tenant' => ['organização no modo sem tenant', 'reprova', ['DefinirTenantDePermissoes']],
    'cópia sem a ligação da TelaLogin'             => ['sem a TelaLogin', 'reprova', ['TelaLogin', 'painel app']],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-29] R28 — sem tenant, toda peça registrada entrega F-nn ou está na lista com o motivo
|--------------------------------------------------------------------------
*/

it('[CT-29] sem tenant, toda peça que o painel registra entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo', function (string $painel, string $mundo, string $resultado, string $nomeando): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse('esta suíte roda sem tenant: kit.tenancy.enabled lido como falso')
        ->and(Filament::getPanel($painel)->hasTenancy())->toBeFalse("o painel {$painel} não tem tenancy neste modo");

    $modelo = modeloDoMapa();
    $lista  = MAPA_MODELO_SEM_FEATURE_PROPRIA;

    // `F-999`: o número que o catálogo não tem (o `04` diz F-99, que o catálogo ganhou depois da derivação).
    expect(array_column(linhasDoCatalogo('pt'), 0))->not->toContain('F-999');

    switch ($mundo) {
        case 'TenantResource sem F-nn':
            $modelo = mapaModeloComPeca($modelo, 'App\\Filament\\Admin\\Resources\\Tenants\\TenantResource', static fn (array $p): array => ['features' => []] + $p);
            break;
        case 'HealthCheckResults sem F-nn':
            $modelo = mapaModeloComPeca($modelo, 'ShuvroRoy\\FilamentSpatieLaravelHealth\\Pages\\HealthCheckResults', static fn (array $p): array => ['features' => []] + $p);
            break;
        case 'AccountWidget na F-nn inexistente':
            $modelo = mapaModeloComPeca($modelo, 'Filament\\Widgets\\AccountWidget', static fn (array $p): array => ['features' => ['F-999']] + $p);
            break;
        case 'AccountWidget na lista sem motivo':
            $modelo                                    = mapaModeloComPeca($modelo, 'Filament\\Widgets\\AccountWidget', static fn (array $p): array => ['features' => []] + $p);
            $lista['Filament\\Widgets\\AccountWidget'] = '';
            break;
    }

    $registradas = registroVivoDoPainel(Filament::getPanel($painel));
    $ids         = array_merge($registradas['recurso'], $registradas['pagina'], $registradas['widget'], $registradas['plugin']);
    $guarda      = mapaModeloConferirFeatures($modelo, $ids, array_column(linhasDoCatalogo('pt'), 0), $lista);

    if ($resultado === 'aceita') {
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo'])
            ->and($guarda['conferidas'])->toBeGreaterThan(0);

        if ($painel === 'infra') {
            expect($ids)->toContain('ShuvroRoy\\FilamentSpatieLaravelHealth\\Pages\\HealthCheckResults');
        }

        return;
    }

    expect($guarda['aceita'])->toBeFalse('a guarda aceitou a cópia adulterada: '.$mundo)
        ->and($guarda['motivo'])->toContain($nomeando);
})->with([
    'admin, publicado'                                          => ['admin', 'publicado', 'aceita', ''],
    'app, publicado'                                            => ['app', 'publicado', 'aceita', ''],
    'infra, publicado'                                          => ['infra', 'publicado', 'aceita', ''],
    'admin, TenantResource sem F-nn nem lista'                  => ['admin', 'TenantResource sem F-nn', 'reprova', 'TenantResource'],
    'infra, HealthCheckResults sem F-nn nem lista'              => ['infra', 'HealthCheckResults sem F-nn', 'reprova', 'HealthCheckResults'],
    'admin, AccountWidget aponta a F-nn que o catálogo não tem' => ['admin', 'AccountWidget na F-nn inexistente', 'reprova', 'AccountWidget'],
    'admin, AccountWidget na lista sem motivo'                  => ['admin', 'AccountWidget na lista sem motivo', 'reprova', 'AccountWidget'],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-41] R28 — toda classe de app/ fora de app/Filament entrega F-nn ou está na lista com o motivo
|--------------------------------------------------------------------------
*/

it('[CT-41] toda classe de app/ fora de app/Filament entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo', function (string $mundo, string $resultado, string $nomeando): void {
    $modelo  = modeloDoMapa();
    $lista   = MAPA_MODELO_SEM_FEATURE_PROPRIA;
    $classes = mapaModeloClassesDeApp();

    switch ($mundo) {
        case 'KitUpdate sem F-nn e fora da lista':
            $modelo = mapaModeloComPeca($modelo, 'App\\Console\\Commands\\KitUpdate', static fn (array $p): array => ['features' => []] + $p);
            break;
        case 'classe nova esquecida':
            $classes[] = 'App\\Support\\RelatorioNovo';
            break;
        case 'motivo ausente':
            $lista['App\\Support\\TelaDeAutenticacao'] = '';
            break;
        case 'motivo de enfeite':
            $lista['App\\Traits\\TemUuid'] = '—';
            break;
        case 'comando kit na lista':
            $lista['App\\Console\\Commands\\KitUpdate'] = 'classe de apoio';
            break;
    }

    $guarda = mapaModeloConferirFeatures($modelo, $classes, array_column(linhasDoCatalogo('pt'), 0), $lista);

    if ($resultado === 'aceita') {
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);
        // a guarda conferiu ao menos as 135 classes da base
        expect($guarda['conferidas'])->toBeGreaterThanOrEqual(135);

        return;
    }

    expect($guarda['aceita'])->toBeFalse('a guarda aceitou a cópia adulterada: '.$mundo)
        ->and($guarda['motivo'])->toContain($nomeando);
})->with([
    'publicado'                                                    => ['publicado', 'aceita', ''],
    'cópia em que KitUpdate não entrega F-nn e está fora da lista' => ['KitUpdate sem F-nn e fora da lista', 'reprova', 'KitUpdate'],
    'lista de classes com uma a mais'                              => ['classe nova esquecida', 'reprova', 'RelatorioNovo'],
    'TelaDeAutenticacao na lista sem o motivo'                     => ['motivo ausente', 'reprova', 'TelaDeAutenticacao'],
    'TemUuid na lista com motivo de enfeite'                       => ['motivo de enfeite', 'reprova', 'TemUuid'],
    'comando kit:* na lista'                                       => ['comando kit na lista', 'reprova', 'KitUpdate'],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-39] R35 — toda transição de fluxo tem âncora que resolve
|--------------------------------------------------------------------------
*/

it('[CT-39] toda transição de fluxo publicado tem âncora que resolve, todo estado de lifecycle também, e as transições seguidas no mesmo método estão na ordem do código', function (string $mundo, string $resultado, string $nomeando): void {
    $diagramas = diagramasDoMapa();

    if ($mundo === 'publicados') {
        $guarda   = mapaModeloConferirFluxos($diagramas);
        $esperado = 0;
        $colecoes = ['workflow' => 'edges', 'sequence' => 'messages', 'dataflow' => 'flows', 'lifecycle' => 'transitions'];

        foreach ($diagramas as $diagrama) {
            if (isset($colecoes[$diagrama['tipo']])) {
                $esperado += count($diagrama[$colecoes[$diagrama['tipo']]] ?? []) + ($diagrama['tipo'] === 'lifecycle' ? count($diagrama['states'] ?? []) : 0);
            }
        }

        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);
        // a guarda conferiu a âncora de todas as transições de todos os diagramas de fluxo
        expect($esperado)->toBeGreaterThan(0)
            ->and($guarda['conferidas'])->toBe($esperado);

        return;
    }

    $copia        = $diagramas['convite'];
    $sobrescritas = [];
    $slug         = 'convite';

    switch ($mundo) {
        case 'transição sem âncora':
            $copia['transitions'][] = ['id' => 'recusado-pendente', 'from' => 'recusado', 'to' => 'pendente', 'label' => ['pt' => 'Reabrir', 'en' => 'Reopen']];
            break;
        case 'âncora que não resolve':
            $copia['transitions'][] = ['id' => 'recusado-pendente', 'from' => 'recusado', 'to' => 'pendente', 'label' => ['pt' => 'Reabrir', 'en' => 'Reopen'], 'prova' => ['arquivo' => 'app/Models/Convite.php', 'trecho' => '$this->forceFill([\'recusado_em\' => null])->save();']];
            break;
        case 'estado sem âncora':
            foreach ($copia['states'] as $i => $estado) {
                if ($estado['id'] === 'expirado') {
                    $copia['states'][$i]['sources'] = [];
                }
            }
            break;
        case 'chamada que saiu do código':
            $slug                             = 'login';
            $copia                            = $diagramas['login'];
            $ancora                           = $copia['messages'][0]['prova'];
            $sobrescritas[$ancora['arquivo']] = str_replace($ancora['trecho'], '', (string) file_get_contents(base_path($ancora['arquivo'])));
            break;
        case 'ordem trocada no mesmo método':
            $achado = null;

            foreach ($diagramas as $outroSlug => $diagrama) {
                $colecao = ['workflow' => 'edges', 'sequence' => 'messages', 'dataflow' => 'flows'][$diagrama['tipo']] ?? null;

                foreach ($colecao === null ? [] : array_keys($diagrama[$colecao]) as $i) {
                    $a = $diagrama[$colecao][$i]['prova'] ?? null;
                    $b = $diagrama[$colecao][$i + 1]['prova'] ?? null;

                    if ($a === null || $b === null || $a['arquivo'] !== $b['arquivo']) {
                        continue;
                    }

                    $pa = mapaModeloPosicaoDoTrecho($a['arquivo'], $a['trecho']);
                    $pb = mapaModeloPosicaoDoTrecho($b['arquivo'], $b['trecho']);

                    if ($pa !== null && $pb !== null && $pa['metodo'] !== null && $pa['metodo'] === $pb['metodo'] && $pa['pos'] < $pb['pos']) {
                        $achado = [$outroSlug, $colecao, $i];
                        break 2;
                    }
                }
            }

            expect($achado)->not->toBeNull('nenhum fluxo publicado tem duas transições seguidas ancoradas no mesmo método, em ordem estrita: não há de onde copiar o controle');

            [$slug, $colecao, $i]                                              = $achado;
            $copia                                                             = $diagramas[$slug];
            [$copia[$colecao][$i]['prova'], $copia[$colecao][$i + 1]['prova']] = [$copia[$colecao][$i + 1]['prova'], $copia[$colecao][$i]['prova']];
            $nomeando                                                          = ($copia[$colecao][$i]['from'] ?? '').' → '.($copia[$colecao][$i]['to'] ?? '');
            break;
    }

    $guarda = mapaModeloConferirFluxos([$slug => $copia], $sobrescritas);

    expect($guarda['aceita'])->toBeFalse('a guarda aceitou a cópia adulterada: '.$mundo)
        ->and($guarda['motivo'])->toContain($nomeando);

    if ($mundo === 'ordem trocada no mesmo método') {
        expect($guarda['motivo'])->toContain($copia[$colecao][$i + 1]['from'].' → '.$copia[$colecao][$i + 1]['to']);
    }
})->with([
    'publicados'                                                       => ['publicados', 'aceita', ''],
    'transição Recusado → Pendente sem âncora'                         => ['transição sem âncora', 'reprova', 'recusado → pendente'],
    'transição Recusado → Pendente ancorada num trecho que não existe' => ['âncora que não resolve', 'reprova', 'recusado → pendente'],
    'estado do lifecycle sem âncora'                                   => ['estado sem âncora', 'reprova', 'expirado'],
    'chamada que saiu do código'                                       => ['chamada que saiu do código', 'reprova', 'navegador → telalogin'],
    'duas transições seguidas, no mesmo método, trocadas de ordem'     => ['ordem trocada no mesmo método', 'reprova', ''],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-40] R36 — toda aresta de mapa é ligação do modelo no mesmo sentido
|--------------------------------------------------------------------------
*/

it('[CT-40] toda aresta de mapa é ligação do modelo no mesmo sentido, e todo diagrama de painel só desenha, como peça, o que é dele, do kit inteiro ou porta', function (string $mundo, string $resultado, string $nomeando): void {
    $modelo    = modeloDoMapa();
    $diagramas = diagramasDoMapa();

    if ($mundo === 'publicados') {
        $guarda = mapaModeloConferirArestasEPecas($diagramas, $modelo, $diagramas);
        $mapas  = array_filter($diagramas, static fn (array $d): bool => in_array($d['construtor'] ?? null, ['mapa', 'registro'], true));

        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);
        // a guarda conferiu todas as arestas dos mapas e das árvores, e todo nó que é peça do modelo nos diagramas de painel
        expect($mapas)->not->toBeEmpty()
            ->and($guarda['arestas'])->toBeGreaterThanOrEqual(count($mapas))
            ->and($guarda['nos'])->toBeGreaterThan(0);

        return;
    }

    $existeLigacao = static fn (string $de, string $para): bool => array_any($modelo['ligacoes'], static fn (array $l): bool => $l['de'] === $de && $l['para'] === $para);

    $slug         = 'admin-acesso-e-autenticacao';
    $copia        = $diagramas[$slug];
    $userResource = 'App\\Filament\\Admin\\Resources\\Users\\UserResource';
    $user         = 'App\\Models\\User';

    switch ($mundo) {
        case 'aresta invertida':
            expect($existeLigacao($userResource, $user))->toBeTrue('o modelo deveria ligar UserResource → User')
                ->and($existeLigacao($user, $userResource))->toBeFalse();
            $copia['arestas'] = [['de' => $user, 'para' => $userResource]];
            $nomeando         = 'User → UserResource';
            break;
        case 'aresta inventada':
            $ids = array_column($copia['pecas'], 'id');
            $par = null;

            foreach ($ids as $a) {
                foreach ($ids as $b) {
                    if ($a !== $b && ! $existeLigacao($a, $b) && ! $existeLigacao($b, $a)) {
                        $par = [$a, $b];
                        break 2;
                    }
                }
            }

            expect($par)->not->toBeNull('o diagrama deveria ter duas peças que o modelo não liga');
            $copia['arestas'] = [['de' => $par[0], 'para' => $par[1]]];
            $nomeando         = $par[0].' → '.$par[1];
            break;
        case 'membro não nomeado':
            $slug   = 'admin-resumo';
            $copia  = $diagramas[$slug];
            $grupo  = $copia['agrupamentos'][0];
            $achada = null;

            foreach ($modelo['ligacoes'] as $ligacao) {
                if ($ligacao['de'] === 'painel:admin' && $ligacao['verbo'] === 'registra' && ! in_array($ligacao['para'], $grupo['membros'], true)) {
                    $achada = $ligacao;
                    break;
                }
            }

            expect($achada)->not->toBeNull('o modelo deveria ter ligação do painel para peça fora do primeiro agrupamento');
            $copia['arestas'] = [['de' => 'painel:admin', 'para' => $grupo['id'], 'representa' => [['de' => $achada['de'], 'para' => $achada['para']]]]];
            $nomeando         = $grupo['id'];
            break;
        case 'porta para o mapa errado':
            $slug              = 'admin-resumo';
            $copia             = $diagramas[$slug];
            $desenhadasNoInfra = array_column($diagramas['infra-resumo']['pecas'], 'id');
            $achada            = null;

            foreach ($modelo['ligacoes'] as $ligacao) {
                if ($ligacao['de'] === 'painel:admin' && ! in_array($ligacao['para'], $desenhadasNoInfra, true)) {
                    $achada = $ligacao;
                    break;
                }
            }

            expect($achada)->not->toBeNull('o modelo deveria ter ligação do painel admin para peça que o mapa do infra não desenha');
            $copia['portas']  = [['para' => 'infra-resumo', 'rotulo' => ['pt' => '/infra', 'en' => '/infra']]];
            $copia['arestas'] = [['de' => 'painel:admin', 'para' => 'porta-infra-resumo', 'representa' => [['de' => $achada['de'], 'para' => $achada['para']]]]];
            $nomeando         = 'porta-infra-resumo';
            break;
        case 'peça de outro painel':
            $slug             = 'infra-copiado-do-admin';
            $copia['paineis'] = ['infra'];
            expect(array_column($copia['pecas'], 'id'))->toContain($userResource);
            $nomeando = 'UserResource';
            break;
    }

    $guarda = mapaModeloConferirArestasEPecas([$slug => $copia], $modelo, $diagramas);

    expect($guarda['aceita'])->toBeFalse('a guarda aceitou a cópia adulterada: '.$mundo)
        ->and($guarda['motivo'])->toContain($nomeando)
        ->and($guarda['motivo'])->toContain($slug);
})->with([
    'publicados'                                                      => ['publicados', 'aceita', ''],
    'cópia com a aresta User → UserResource invertida'                => ['aresta invertida', 'reprova', ''],
    'cópia com aresta entre duas peças que o modelo não liga'         => ['aresta inventada', 'reprova', ''],
    'cópia com aresta de agrupamento que representa peça não nomeada' => ['membro não nomeado', 'reprova', ''],
    'cópia com aresta de porta para o mapa errado'                    => ['porta para o mapa errado', 'reprova', ''],
    'cópia de diagrama do infra copiado do admin'                     => ['peça de outro painel', 'reprova', ''],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-45] R37 — toda marca de disponibilidade publicada é a do código
|--------------------------------------------------------------------------
*/

it('[CT-45] toda marca de disponibilidade publicada é a do código, com a chave que decide e o valor de fábrica, ou o modo', function (string $mundo, string $resultado, string $nomeando): void {
    $modelo       = modeloDoMapa();
    $diagramas    = diagramasDoMapa();
    $agente       = 'App\\Filament\\Admin\\Resources\\AgentesIa\\AgenteIaResource';
    $organizacoes = 'App\\Filament\\Admin\\Resources\\Tenants\\TenantResource';

    switch ($mundo) {
        case 'chave que não decide':
            $modelo = mapaModeloComPeca($modelo, $agente, static fn (array $p): array => $p + ['disponibilidade' => ['chave' => 'KIT_REGISTRO', 'padrao' => false]]);
            break;
        case 'valor de fábrica trocado':
            $modelo = mapaModeloComPeca($modelo, $agente, static fn (array $p): array => $p + ['disponibilidade' => ['chave' => 'KIT_AGENTES_IA', 'padrao' => true]]);
            break;
        case 'modo invertido':
            $modelo = mapaModeloComPeca($modelo, $organizacoes, static fn (array $p): array => $p + ['disponibilidade' => ['modo' => 'sem']]);
            break;
    }

    $guarda = mapaModeloConferirDisponibilidade($modelo, $diagramas);

    if ($resultado === 'aceita') {
        // sem marca nenhuma a regra é vácua; com marca, cada uma tem de ser a do código
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

        return;
    }

    expect($guarda['marcas'])->toBeGreaterThan(0)
        ->and($guarda['aceita'])->toBeFalse('a guarda aceitou a marca adulterada: '.$mundo)
        ->and($guarda['motivo'])->toContain($nomeando);
})->with([
    'publicados'                                            => ['publicados', 'aceita', ''],
    'AgenteIaResource com a chave KIT_REGISTRO'             => ['chave que não decide', 'reprova', 'AgenteIaResource'],
    'AgenteIaResource com KIT_AGENTES_IA ligada de fábrica' => ['valor de fábrica trocado', 'reprova', 'AgenteIaResource'],
    'TenantResource disponível só sem tenant'               => ['modo invertido', 'reprova', 'TenantResource'],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| Helpers do step 9: a linha que o build dá (CT-52), as âncoras sem guarda (CT-53) e os ids dos construídos (CT-54)
|--------------------------------------------------------------------------
| A entrada que o gerador dá ao archify (costura G5, forma de leitura) vem de `entradasDoGeradorDoMapa()`, de
| `tests/Pest.php`, que as guardas da R38 também usam.
*/

/**
 * O texto de cada arquivo no `HEAD`, a revisão que o gerador lê (`git show`) para dar a linha à âncora; `null`
 * para o que não está nela. Um `git cat-file --batch` só, para todos os arquivos.
 *
 * @param  list<string>  $arquivos
 * @return array<string, ?string>
 */
function mapaModeloTextosNaRevisao(array $arquivos): array
{
    if ($arquivos === []) {
        return [];
    }

    $saida = Process::path(base_path())
        ->input(implode('', array_map(static fn (string $arquivo): string => "HEAD:{$arquivo}\n", $arquivos)))
        ->timeout(120)
        ->run(['git', 'cat-file', '--batch'])
        ->throw()
        ->output();

    $textos  = [];
    $posicao = 0;

    foreach ($arquivos as $arquivo) {
        $fim = strpos($saida, "\n", $posicao);

        if ($fim === false) {
            $textos[$arquivo] = null;

            continue;
        }

        $cabecalho = substr($saida, $posicao, $fim - $posicao);
        $posicao   = $fim + 1;

        if (preg_match('/^\S+ blob (\d+)$/', $cabecalho, $m) !== 1) {
            $textos[$arquivo] = null;

            continue;
        }

        $textos[$arquivo] = str_replace("\r\n", "\n", substr($saida, $posicao, (int) $m[1]));
        $posicao += (int) $m[1] + 1;
    }

    return $textos;
}

/**
 * R9, CT-52: a linha que o build dá a cada prova — o item de prova de cada ligação desenhada e cada fonte de
 * peça — tem o trecho da âncora fora de comentário, pela sintaxe do tipo do arquivo, no texto da revisão que o
 * gerador lê. Diagrama que o gerador recusou reprova, com a mensagem dele. O texto trocado em memória (o
 * controle do nowdoc, re-revisão RR-03) vale no lugar do da revisão, como valeu para o gerador.
 *
 * @param  array<string, array<string, mixed>>  $entradas  os `diagramas` de `entradasDoGeradorDoMapa()`, montados em `pt` e com as âncoras marcadas
 * @param  array<string, string>  $textosNaRevisao  caminho → o texto que o gerador leu no lugar do `git show`
 * @return array<string, mixed>
 */
function mapaModeloConferirLinhasDoBuild(array $entradas, array $textosNaRevisao = []): array
{
    $violacoes = [];
    $ancoras   = [];

    foreach ($entradas as $slug => $saida) {
        if (isset($saida['pt']['erro'])) {
            $violacoes[] = "o gerador recusou o diagrama {$slug}: {$saida['pt']['erro']}";

            continue;
        }

        $coletar = static function (mixed $no, string $quem) use (&$coletar, &$ancoras, &$violacoes, $slug): void {
            if (! is_array($no)) {
                return;
            }

            if (isset($no['id']) && is_string($no['id'])) {
                $quem = $no['id'];
            }

            if (array_key_exists('path', $no) && array_key_exists('line', $no)) {
                if (! is_string($no['label'] ?? null) || $no['label'] === '') {
                    $violacoes[] = "o diagrama {$slug}: a fonte da peça {$quem} saiu do gerador sem a âncora que a projetou";

                    return;
                }

                $ancoras[] = [$slug, "a fonte da peça {$quem}", (string) $no['path'], (int) $no['line'], $no['label']];

                return;
            }

            foreach ($no as $filho) {
                $coletar($filho, $quem);
            }
        };

        $coletar(array_diff_key($saida['pt'], ['cards' => 1]), $slug);

        foreach ($saida['pt']['cards'] ?? [] as $card) {
            if (! str_starts_with((string) ($card['title'] ?? ''), 'Prova das ligações')) {
                continue;
            }

            foreach ($card['items'] ?? [] as $item) {
                if (! is_string($item) || preg_match('/^(.*?): (\S+):(\d+)(?:-\d+)? — (.*)$/su', $item, $m) !== 1) {
                    $violacoes[] = "o diagrama {$slug}: o item de prova não traz arquivo e linha: ".json_encode($item, JSON_UNESCAPED_UNICODE);

                    continue;
                }

                $ancoras[] = [$slug, "a ligação {$m[1]}", $m[2], (int) $m[3], $m[4]];
            }
        }
    }

    $trocados   = array_map(static fn (string $texto): string => str_replace("\r\n", "\n", $texto), $textosNaRevisao);
    $textos     = $trocados + mapaModeloTextosNaRevisao(array_values(array_diff(array_unique(array_column($ancoras, 2)), array_keys($trocados))));
    $mascaras   = [];
    $inicios    = [];
    $repetidas  = [];
    $conferidas = 0;

    foreach ($ancoras as [$slug, $quem, $caminho, $linha, $trecho]) {
        $conferidas++;
        $texto = $textos[$caminho] ?? null;

        if ($texto === null) {
            $violacoes[] = "o diagrama {$slug}: {$quem}: {$caminho} não está na revisão que o gerador lê";

            continue;
        }

        $mascaras[$caminho] ??= mapaModeloMascararComentarios($caminho, $texto);
        $inicios[$caminho] ??= [0, ...array_map(static fn (array $quebra): int => $quebra[1] + 1, preg_match_all('/\n/', $texto, $q, PREG_OFFSET_CAPTURE) > 0 ? $q[0] : [])];

        if (substr_count($texto, $trecho) > 1) {
            $repetidas["{$caminho}\0{$trecho}"] = true;
        }

        $inicio = $inicios[$caminho][$linha - 1] ?? null;
        $fim    = isset($inicios[$caminho][$linha]) ? $inicios[$caminho][$linha] - 1 : strlen($texto);
        $achou  = false;

        for ($busca = $inicio; $busca !== null && ($achado = strpos($texto, $trecho, $busca)) !== false && $achado <= $fim; $busca = $achado + 1) {
            if (substr($mascaras[$caminho], $achado, strlen($trecho)) === $trecho) {
                $achou = true;

                break;
            }
        }

        if (! $achou) {
            $violacoes[] = "o diagrama {$slug}: {$quem}: a linha {$linha} de {$caminho}, que o build dá à prova, não tem o trecho "
                .json_encode($trecho, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).' fora de comentário';
        }
    }

    return mapaModeloResultado($violacoes, ['conferidas' => $conferidas, 'repetidas' => count($repetidas)]);
}

/**
 * O diagrama escrito à mão, em memória, dos controles do CT-52: dois nós e uma ligação provada pela âncora dada.
 *
 * @return array<string, mixed>
 */
function mapaModeloDiagramaDeControle(string $slug, string $arquivo, string $trecho): array
{
    return [
        'slug'           => $slug,
        'tipo'           => 'workflow',
        'titulo'         => ['pt' => 'Controle do CT-52', 'en' => 'CT-52 control'],
        'paineis'        => [],
        'modos'          => ['sem', 'com'],
        'schema_version' => 1,
        'diagram_type'   => 'workflow',
        'nodes'          => [['id' => 'app-service-provider', 'label' => 'AppServiceProvider'], ['id' => 'locker', 'label' => 'Locker']],
        'edges'          => [[
            'id'    => 'liga-locker',
            'from'  => 'app-service-provider',
            'to'    => 'locker',
            'label' => ['pt' => 'liga', 'en' => 'binds'],
            'prova' => ['arquivo' => $arquivo, 'trecho' => $trecho],
        ]],
    ];
}

/** A linha (a partir de 1) da primeira ocorrência do trecho no texto; `null` se não há. */
function mapaModeloLinhaDoTrecho(string $texto, string $trecho): ?int
{
    $posicao = strpos($texto, $trecho);

    return $posicao === false ? null : substr_count(substr($texto, 0, $posicao), "\n") + 1;
}

/**
 * Toda âncora de um arquivo de diagrama escrito à mão, com a coleção e o item onde ela mora (a fonte de
 * participante, nó, entidade, tipo, evento e span; a prova de relação, de mensagem, de transição).
 *
 * @param  array<string, mixed>  $diagrama
 * @return list<array{colecao: string, item: string, ancora: array<string, mixed>}>
 */
function mapaModeloAncorasDoDiagrama(array $diagrama): array
{
    $achadas = [];

    $coletar = static function (mixed $no, string $colecao, string $item) use (&$coletar, &$achadas): void {
        if (! is_array($no)) {
            return;
        }

        if (array_key_exists('arquivo', $no) || array_key_exists('trecho', $no)) {
            $achadas[] = ['colecao' => $colecao, 'item' => $item, 'ancora' => $no];

            return;
        }

        foreach ($no as $filho) {
            $coletar($filho, $colecao, $item);
        }
    };

    foreach ($diagrama as $colecao => $itens) {
        if (! is_array($itens) || ! array_is_list($itens)) {
            $coletar($itens, (string) $colecao, (string) $colecao);

            continue;
        }

        foreach ($itens as $indice => $item) {
            $id = is_array($item) && isset($item['id']) && is_scalar($item['id']) ? (string) $item['id'] : "#{$indice}";
            $coletar($item, (string) $colecao, $id);
        }
    }

    return $achadas;
}

/**
 * R9, CT-53: a guarda da R9 — arquivo versionado, trecho fora de comentário pela sintaxe do tipo, sem número de
 * linha e com as chaves fechadas — sobre a evidência de cada peça do modelo de peças e sobre toda âncora dos
 * diagramas escritos à mão (os sem `construtor`).
 *
 * @param  list<array<string, mixed>>  $pecas
 * @param  array<string, array<string, mixed>>  $diagramas
 * @param  array<string, string>  $sobrescritas
 * @return array<string, mixed>
 */
function mapaModeloConferirAncoras(array $pecas, array $diagramas, array $sobrescritas = []): array
{
    $violacoes  = [];
    $conferidas = 0;
    $porColecao = [];

    $conferir = static function (string $nome, mixed $ancora, string $colecao) use (&$violacoes, &$conferidas, &$porColecao, $sobrescritas): void {
        $ancora    = is_array($ancora) ? $ancora : [];
        $resultado = mapaModeloConferirProva($nome, $ancora['arquivo'] ?? null, $ancora['trecho'] ?? null, array_diff_key($ancora, ['arquivo' => 1, 'trecho' => 1]), $sobrescritas);
        $conferidas++;
        $porColecao[$colecao] = ($porColecao[$colecao] ?? 0) + 1;

        if (! $resultado['aceita']) {
            $violacoes[] = $resultado['motivo'];
        }
    };

    foreach ($pecas as $peca) {
        foreach (is_array($peca['evidencia'] ?? null) ? $peca['evidencia'] : [] as $ancora) {
            $conferir("a evidência da peça {$peca['id']}", $ancora, 'pecas');
        }
    }

    foreach ($diagramas as $slug => $diagrama) {
        if (array_key_exists('construtor', $diagrama)) {
            continue;
        }

        foreach (mapaModeloAncorasDoDiagrama($diagrama) as ['colecao' => $colecao, 'item' => $item, 'ancora' => $ancora]) {
            $conferir("o diagrama {$slug}, em {$colecao}, {$item}", $ancora, $colecao);
        }
    }

    return mapaModeloResultado($violacoes, ['conferidas' => $conferidas, 'porColecao' => $porColecao]);
}

/**
 * R36, CT-54: todo id que um arquivo de diagrama construído (com `construtor`) nomeia — a peça desenhada e o
 * membro de agrupamento — é peça do modelo de peças. Lê o arquivo, e não o desenho: o gerador some com o id
 * desconhecido no diagrama de um modo e o põe cru no card no de dois modos (step 9, achado A8).
 *
 * @param  array<string, array<string, mixed>>  $diagramas
 * @param  list<array<string, mixed>>  $pecas
 * @return array<string, mixed>
 */
function mapaModeloConferirIdsConstruidos(array $diagramas, array $pecas): array
{
    $doModelo   = array_flip(array_column($pecas, 'id'));
    $violacoes  = [];
    $arquivos   = [];
    $conferidos = 0;

    foreach ($diagramas as $slug => $diagrama) {
        if (! array_key_exists('construtor', $diagrama)) {
            continue;
        }

        $arquivos[$slug] = ['paineis' => count($diagrama['paineis'] ?? []), 'modos' => count($diagrama['modos'] ?? [])];

        foreach ($diagrama['pecas'] ?? [] as $desenhada) {
            $conferidos++;
            $id = (string) ($desenhada['id'] ?? '');

            if (! isset($doModelo[$id])) {
                $violacoes[] = "o diagrama {$slug} desenha a peça {$id}, que não é peça do modelo";
            }
        }

        foreach ($diagrama['agrupamentos'] ?? [] as $grupo) {
            foreach ($grupo['membros'] ?? [] as $membro) {
                $conferidos++;

                if (! isset($doModelo[$membro])) {
                    $violacoes[] = "o diagrama {$slug} põe {$membro} no agrupamento {$grupo['id']}, e {$membro} não é peça do modelo";
                }
            }
        }
    }

    return mapaModeloResultado($violacoes, ['arquivos' => $arquivos, 'conferidos' => $conferidos]);
}

/*
|--------------------------------------------------------------------------
| [CT-52] R9 — a linha que o build dá a cada prova é a do código (costura G5, forma de leitura)
|--------------------------------------------------------------------------
*/

it('[CT-52] a linha que o build dá a cada prova é a do código: o trecho está fora de comentário nela', function (string $mundo): void {
    // Dado o node do ambiente, lido pela versão: sem ele o caso reprova, e não pula (Q21)
    expect(versaoDoNode())->toMatch('/^v\d+\.\d+\.\d+$/');

    if ($mundo === 'publicados') {
        $diagramas = array_filter(diagramasDoMapa(), static fn (array $diagrama): bool => ($diagrama['tipo'] ?? null) !== 'compare');
        $entradas  = entradasDoGeradorDoMapa(['idiomas' => ['pt'], 'marcarAncoras' => true])['diagramas'];

        // o gerador montou a entrada de cada diagrama publicado (o comparativo é a soma de dois deles)
        expect(array_keys($entradas))->toEqualCanonicalizing(array_keys($diagramas));

        $guarda = mapaModeloConferirLinhasDoBuild($entradas);

        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

        // conferiu tantas provas quantas o gerador projeta: cada fonte de peça e cada item de prova de ligação
        $projetadas = 0;

        foreach ($entradas as $saida) {
            $projetadas += substr_count(json_encode(array_diff_key($saida['pt'] ?? [], ['cards' => 1]), JSON_THROW_ON_ERROR), '"line":');

            foreach ($saida['pt']['cards'] ?? [] as $card) {
                if (str_starts_with((string) ($card['title'] ?? ''), 'Prova das ligações')) {
                    $projetadas += count($card['items'] ?? []);
                }
            }
        }

        expect($guarda['conferidas'])->toBe($projetadas)
            ->and($projetadas)->toBeGreaterThan(count(modeloDoMapa()['ligacoes']))
            ->and($guarda['repetidas'])->toBeGreaterThanOrEqual(1, 'nenhuma âncora conferida tem o trecho mais de uma vez no arquivo');

        return;
    }

    // o controle: em app/Providers/AppServiceProvider.php, `Locker::class` aparece primeiro num comentário `//`
    // e depois no código (premissa conferida aqui, no texto da revisão que o gerador lê)
    $arquivo  = 'app/Providers/AppServiceProvider.php';
    $trecho   = 'Locker::class';
    $texto    = (string) (mapaModeloTextosNaRevisao([$arquivo])[$arquivo] ?? '');
    $mascara  = mapaModeloMascararComentarios($arquivo, $texto);
    $primeira = strpos($texto, $trecho);

    expect($primeira)->not->toBeFalse("{$trecho} deveria estar em {$arquivo}")
        ->and(substr($mascara, (int) $primeira, strlen($trecho)))->not->toBe($trecho, 'a primeira ocorrência deveria estar num comentário')
        ->and(str_contains($mascara, $trecho))->toBeTrue("{$trecho} deveria estar também fora de comentário");

    if ($mundo === 'nowdoc') {
        // re-revisão RR-03: o mesmo diagrama, sobre o texto do arquivo no commit do teste com um nowdoc de corpo com
        // apóstrofo inserido em memória depois da linha 19 (o `{` do `register()`), antes do comentário que cita
        // `Locker::class`. O texto chega ao gerador no lugar do `git show` dele; nada é gravado no repositório.
        $linhas = explode("\n", $texto);

        expect($linhas[17] ?? '')->toContain('function register(')
            ->and(trim($linhas[18] ?? ''))->toBe('{', 'a linha 19 deveria ser o { do register()');

        $comNowdoc    = implode("\n", [...array_slice($linhas, 0, 19), "\$aviso = <<<'TXT'", "it's a nowdoc", 'TXT;', ...array_slice($linhas, 19)]);
        $mascarado    = mapaModeloMascararComentarios($arquivo, $comNowdoc);
        $noComentario = mapaModeloLinhaDoTrecho($comNowdoc, $trecho);

        // a premissa no texto adulterado: o comentário que cita o trecho vem depois do nowdoc, e o código, depois dele
        expect($noComentario)->toBeGreaterThan(22)
            ->and(substr($mascarado, (int) strpos($comNowdoc, $trecho), strlen($trecho)))->not->toBe($trecho, 'a primeira ocorrência deveria estar num comentário')
            ->and(str_contains($mascarado, $trecho))->toBeTrue("{$trecho} deveria estar também fora de comentário");

        // a sonda: um trecho único, de código, abaixo do nowdoc, cuja linha só bate se o texto adulterado chegou ao gerador
        $sonda        = '$this->app->bind(LockerScreen::class, TelaBloqueio::class);';
        $linhaDaSonda = mapaModeloLinhaDoTrecho($comNowdoc, $sonda);

        expect(substr_count($comNowdoc, $sonda))->toBe(1)
            ->and($linhaDaSonda)->not->toBe(mapaModeloLinhaDoTrecho($texto, $sonda));

        $montado = entradasDoGeradorDoMapa([
            'extras'          => [mapaModeloDiagramaDeControle('controle-ct52-nowdoc', $arquivo, $trecho), mapaModeloDiagramaDeControle('sonda-ct52-nowdoc', $arquivo, $sonda)],
            'soExtras'        => true,
            'idiomas'         => ['pt'],
            'marcarAncoras'   => true,
            'textosNaRevisao' => [$arquivo => $comNowdoc],
        ])['diagramas'];

        $itensDaSonda = array_merge(...array_map(static fn (array $card): array => $card['items'] ?? [], $montado['sonda-ct52-nowdoc']['pt']['cards'] ?? []));
        $saiuNaLinha  = preg_match('/AppServiceProvider\.php:(\d+) — /u', implode("\n", array_filter($itensDaSonda, 'is_string')), $m) === 1 ? (int) $m[1] : null;

        expect($saiuNaLinha)->toBe($linhaDaSonda, "o texto com o nowdoc não chegou ao gerador: a sonda saiu na linha {$saiuNaLinha}, e no texto com o nowdoc está na {$linhaDaSonda} (o pedido de entradasDoGeradorDoMapa() precisa trocar o git show pela opção textosNaRevisao)");

        $guarda = mapaModeloConferirLinhasDoBuild(['controle-ct52-nowdoc' => $montado['controle-ct52-nowdoc'] ?? []], [$arquivo => $comNowdoc]);

        expect($guarda['conferidas'])->toBeGreaterThanOrEqual(1, 'o item de prova da ligação de controle não saiu do gerador')
            ->and($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

        return;
    }

    $controle = mapaModeloDiagramaDeControle('controle-ct52-locker', $arquivo, $trecho);

    $saida = entradasDoGeradorDoMapa(['extras' => [$controle], 'soExtras' => true, 'idiomas' => ['pt'], 'marcarAncoras' => true])['diagramas']['controle-ct52-locker'] ?? null;

    expect($saida)->not->toBeNull('o gerador não devolveu o diagrama de controle');

    if (isset($saida['pt']['erro'])) {
        // o gerador recusou a âncora ambígua: aceita, se a mensagem nomeia a ligação
        expect(str_contains($saida['pt']['erro'], 'app-service-provider → locker') || str_contains($saida['pt']['erro'], 'AppServiceProvider → Locker'))
            ->toBeTrue('o gerador recusou a prova sem nomear a ligação: '.$saida['pt']['erro']);

        return;
    }

    $guarda = mapaModeloConferirLinhasDoBuild(['controle-ct52-locker' => $saida]);

    expect($guarda['conferidas'])->toBeGreaterThanOrEqual(1, 'o item de prova da ligação de controle não saiu do gerador')
        ->and($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);
})->with([
    'publicados'                                                                    => ['publicados'],
    'diagrama em memória com o trecho antes num comentário'                         => ['controle'],
    'o mesmo diagrama, com um nowdoc de apóstrofo antes do comentário (re-revisão)' => ['nowdoc'],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-53] R9 — toda âncora do mapa resolve na suíte do kit
|--------------------------------------------------------------------------
*/

it('[CT-53] toda âncora do mapa resolve na suíte do kit: a evidência de cada peça do modelo e toda âncora dos diagramas escritos à mão', function (string $mundo): void {
    $pecas        = modeloDoMapa()['pecas'];
    $diagramas    = diagramasDoMapa();
    $sobrescritas = [];
    $nomeando     = [];

    switch ($mundo) {
        case 'evidência que saiu do código':
            $peca   = 'App\\Filament\\Admin\\Resources\\Users\\UserResource';
            $indice = array_search($peca, array_column($pecas, 'id'), true);

            expect($indice)->not->toBeFalse()
                ->and($pecas[$indice]['evidencia'][0]['arquivo'] ?? null)->toBe('app/Filament/Admin/Resources/Users/UserResource.php');

            $pecas[$indice]['evidencia'][0]['trecho'] = 'trecho que não está no UserResource do admin';
            $nomeando                                 = [$peca];
            break;

        case 'migração que não existe':
            $migracao = 'database/migrations/2026_01_01_000000_create_tabela_que_nao_existe.php';

            expect(is_file(base_path($migracao)))->toBeFalse()
                ->and($diagramas['erd-identidade-e-permissoes']['entities'][0]['sources'][0]['arquivo'] ?? '')->toStartWith('database/migrations/');

            $diagramas['erd-identidade-e-permissoes']['entities'][0]['sources'][0]['arquivo']  = $migracao;
            $nomeando                                                                          = ['erd-identidade-e-permissoes', $migracao];
            break;

        case 'trecho fora do CHANGELOG':
            $trecho = '## [9.99.9] - 2099-12-31';

            expect($diagramas['marcos-do-changelog']['events'][0]['sources'][0]['arquivo'] ?? null)->toBe('CHANGELOG.md')
                ->and(str_contains((string) file_get_contents(base_path('CHANGELOG.md')), $trecho))->toBeFalse();

            $diagramas['marcos-do-changelog']['events'][0]['sources'][0]['trecho'] = $trecho;
            $nomeando                                                              = ['marcos-do-changelog'];
            break;

        case 'relação só em comentário':
            $relacao = $diagramas['classes-heranca-e-contratos']['relationships'][0];
            $arquivo = $relacao['prova']['arquivo'];
            $trecho  = 'class User implements ProvaQueSoExisteNumComentario';

            // cópia em memória do arquivo da prova com o trecho só num comentário
            $sobrescritas[$arquivo] = file_get_contents(base_path($arquivo))."\n// {$trecho}\n";

            expect(str_contains((string) mapaModeloCodigo($arquivo, $sobrescritas), $trecho))->toBeFalse()
                ->and(str_contains($sobrescritas[$arquivo], $trecho))->toBeTrue();

            $diagramas['classes-heranca-e-contratos']['relationships'][0]['prova']['trecho'] = $trecho;
            $nomeando                                                                        = ['classes-heranca-e-contratos', (string) $relacao['id']];
            break;

        case 'span só em comentário de YAML':
            $arquivo = '.github/workflows/ci.yml';
            $trecho  = 'job-que-so-existe-num-comentario:';

            // cópia em memória do workflow com o trecho só numa linha de comentário `#` (Q20)
            $sobrescritas[$arquivo] = file_get_contents(base_path($arquivo))."\n# {$trecho}\n";

            expect(in_array($arquivo, arquivosVersionados(), true))->toBeTrue()
                ->and(str_contains((string) mapaModeloCodigo($arquivo, $sobrescritas), $trecho))->toBeFalse();

            $diagramas['tempos-ci']['spans'][0]['sources'][0] = ['arquivo' => $arquivo, 'trecho' => $trecho, 'label' => 'controle do CT-53'];
            $nomeando                                         = ['tempos-ci'];
            break;
    }

    $guarda = mapaModeloConferirAncoras($pecas, $diagramas, $sobrescritas);

    if ($mundo === 'publicados') {
        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo']);

        // conferiu tantas âncoras quantas o modelo de peças e os diagramas escritos à mão têm
        $esperadas = array_sum(array_map(static fn (array $peca): int => count($peca['evidencia'] ?? []), $pecas));

        foreach ($diagramas as $diagrama) {
            if (! array_key_exists('construtor', $diagrama)) {
                $esperadas += substr_count(json_encode($diagrama, JSON_THROW_ON_ERROR), '"arquivo":');
            }
        }

        expect($guarda['conferidas'])->toBe($esperadas)
            ->and($guarda['conferidas'])->toBeGreaterThan(count($pecas));

        foreach (['participants', 'nodes', 'entities', 'types', 'events', 'spans', 'relationships'] as $colecao) {
            expect($guarda['porColecao'][$colecao] ?? 0)->toBeGreaterThanOrEqual(1, "nenhuma âncora de {$colecao} foi conferida");
        }

        return;
    }

    expect($guarda['aceita'])->toBeFalse("a guarda aceitou a cópia: {$mundo}");

    foreach ($nomeando as $nome) {
        expect($guarda['motivo'])->toContain($nome);
    }
})->with([
    'publicados'                                                           => ['publicados'],
    'cópia com a evidência do UserResource do /admin fora do código'       => ['evidência que saiu do código'],
    'cópia do erd-identidade-e-permissoes com uma migração que não existe' => ['migração que não existe'],
    'cópia do marcos-do-changelog com um trecho fora do CHANGELOG'         => ['trecho fora do CHANGELOG'],
    'cópia do classes-heranca-e-contratos com a prova só em comentário'    => ['relação só em comentário'],
    'cópia do tempos-ci com o trecho só num comentário de YAML'            => ['span só em comentário de YAML'],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);

/*
|--------------------------------------------------------------------------
| [CT-54] R36 — todo id de um diagrama construído é peça do modelo
|--------------------------------------------------------------------------
*/

it('[CT-54] todo id que um arquivo de diagrama construído nomeia — peça desenhada ou membro de agrupamento — é peça do modelo, em diagrama de painel e do kit inteiro, de um modo ou dos dois', function (string $mundo, string $slug, string $onde, int $paineis, int $modos): void {
    $pecas     = modeloDoMapa()['pecas'];
    $diagramas = diagramasDoMapa();
    $intruso   = 'App\\Support\\PecaQueNaoExiste';

    if ($mundo === 'publicados') {
        $guarda      = mapaModeloConferirIdsConstruidos($diagramas, $pecas);
        $construidos = array_keys(array_filter($diagramas, static fn (array $diagrama): bool => array_key_exists('construtor', $diagrama)));

        expect($guarda['aceita'])->toBeTrue((string) $guarda['motivo'])
            ->and(array_keys($guarda['arquivos']))->toEqualCanonicalizing($construidos)
            ->and($construidos)->not->toBeEmpty()
            ->and($guarda['conferidos'])->toBeGreaterThan(0);

        // os de painel e os do kit inteiro, de um modo e dos dois
        $comoSao = array_values($guarda['arquivos']);

        expect(array_filter($comoSao, static fn (array $arquivo): bool => $arquivo['paineis'] > 0))->not->toBeEmpty()
            ->and(array_filter($comoSao, static fn (array $arquivo): bool => $arquivo['paineis'] === 0))->not->toBeEmpty()
            ->and(array_filter($comoSao, static fn (array $arquivo): bool => $arquivo['modos'] === 1))->not->toBeEmpty()
            ->and(array_filter($comoSao, static fn (array $arquivo): bool => $arquivo['modos'] === 2))->not->toBeEmpty();

        return;
    }

    // Dado: o arquivo construído é do kit inteiro ou de painel, de um modo ou dos dois, e o intruso não é peça
    $copia = $diagramas[$slug];

    expect($copia['construtor'] ?? null)->toBe('mapa')
        ->and(count($copia['paineis'] ?? []))->toBe($paineis)
        ->and(count($copia['modos'] ?? []))->toBe($modos)
        ->and(classeExiste($intruso))->toBeFalse()
        ->and(pecasPorId($pecas))->not->toHaveKey($intruso);

    if ($onde === 'membro') {
        expect($copia['agrupamentos'] ?? [])->not->toBeEmpty();
        $copia['agrupamentos'][0]['membros'][] = $intruso;
    } else {
        $copia['pecas'][] = ['id' => $intruso, 'row' => 9, 'col' => 9];
    }

    $diagramas[$slug] = $copia;
    $guarda           = mapaModeloConferirIdsConstruidos($diagramas, $pecas);

    expect($guarda['aceita'])->toBeFalse("a guarda aceitou o id desconhecido em {$slug}")
        ->and($guarda['motivo'])->toContain($slug)
        ->and($guarda['motivo'])->toContain($intruso);
})->with([
    'publicados'                                                        => ['publicados', '', '', 0, 0],
    'cópia do kit-resumo-sem com um membro desconhecido no agrupamento' => ['copia', 'kit-resumo-sem', 'membro', 0, 1],
    'cópia do admin-resumo com um membro desconhecido no agrupamento'   => ['copia', 'admin-resumo', 'membro', 1, 2],
    'cópia do app-resumo-sem com uma peça desconhecida desenhada'       => ['copia', 'app-resumo-sem', 'peca', 1, 1],
])->skip(fn (): bool => ! naArvoreDoKit(), MAPA_MODELO_SKIP);
