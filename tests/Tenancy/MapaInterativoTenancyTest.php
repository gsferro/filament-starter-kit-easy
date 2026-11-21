<?php

/**
 * As guardas do mapa interativo que só existem com o modo multi-tenant ligado: CT-12 (R11), CT-30 (R28),
 * CT-38 (R34) e CT-44 (R6), todos da costura G3 do `04-casos-de-teste.md` da wiki
 * `feat/mapa-interativo-do-kit`.
 *
 * Escrito a partir do Gherkin do `04`; `app/` e `site/mapa/` foram lidos só para achar NOMES (classe,
 * arquivo, forma do modelo). O modo com tenant só existe com o `Tests\TenancyTestCase`, que fixa
 * `kit.tenancy.enabled` antes do boot.
 *
 * Cada guarda é uma função aplicada ao modelo publicado e a cópias adulteradas em memória: o controle
 * chama a mesma função da linha "publicado".
 */

use Filament\Facades\Filament;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Panel;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        test()->markTestSkipped('O mapa e a página dele só existem na árvore do kit: /site, /docs e CHANGELOG.md têm export-ignore.');
    }
});

/** Os cinco tipos de registro que o `registra` do modelo leva, na ordem do `04`. */
const MAPA_TENANCY_TIPOS_DE_REGISTRO = ['recurso', 'pagina', 'widget', 'plugin', 'middleware'];

if (! function_exists('mapaTenancyLerJson')) {
    /**
     * @return array<mixed>
     */
    function mapaTenancyLerJson(string $caminho): array
    {
        return json_decode((string) file_get_contents(base_path($caminho)), true, 512, JSON_THROW_ON_ERROR);
    }
}

if (! function_exists('mapaTenancyModelo')) {
    /**
     * O modelo publicado: peças indexadas pelo id, ligações e os arquivos de diagrama (a pasta é o manifesto).
     *
     * @return array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>, diagramas: list<array<string, mixed>>}
     */
    function mapaTenancyModelo(): array
    {
        $pecas = [];
        foreach (mapaTenancyLerJson('site/mapa/pecas.json') as $peca) {
            $pecas[$peca['id']] = $peca;
        }

        $diagramas = [];
        foreach (glob(base_path('site/mapa/diagramas/*.json')) ?: [] as $arquivo) {
            $diagramas[] = mapaTenancyLerJson('site/mapa/diagramas/'.basename($arquivo));
        }

        return ['pecas' => $pecas, 'ligacoes' => mapaTenancyLerJson('site/mapa/ligacoes.json'), 'diagramas' => $diagramas];
    }
}

if (! function_exists('mapaTenancyNormalizarMiddleware')) {
    /**
     * `Classe:parametro` vira `Classe`; o alias `panel:{id}` fica inteiro.
     */
    function mapaTenancyNormalizarMiddleware(string $middleware): string
    {
        if (str_starts_with($middleware, 'panel:')) {
            return $middleware;
        }

        return explode(':', $middleware)[0];
    }
}

if (! function_exists('mapaTenancyRegistroVivo')) {
    /**
     * O que o painel registra, lido dos getters do Filament (Setup Global do `04`), por tipo de registro.
     * Inclui as páginas que o painel liga pela configuração e as que o plugin registra.
     *
     * @return array<string, list<string>>
     */
    function mapaTenancyRegistroVivo(Panel $painel): array
    {
        $paginas = array_values($painel->getPages());
        foreach ([
            $painel->getLoginRouteAction(),
            $painel->getRegistrationRouteAction(),
            $painel->getRequestPasswordResetRouteAction(),
            $painel->getResetPasswordRouteAction(),
            $painel->getEmailVerificationPromptRouteAction(),
            $painel->getProfilePage(),
            $painel->getTenantRegistrationPage(),
            $painel->getTenantProfilePage(),
        ] as $pagina) {
            if (is_string($pagina) && class_exists($pagina)) {
                $paginas[] = $pagina;
            }
        }

        $middlewares = [...$painel->getMiddleware(), ...$painel->getAuthMiddleware()];
        if ($painel->hasTenancy()) {
            $middlewares = [...$middlewares, ...$painel->getTenantMiddleware()];
        }

        $registro = [
            'recurso'    => array_values($painel->getResources()),
            'pagina'     => $paginas,
            'widget'     => array_values(array_filter($painel->getWidgets(), 'is_string')),
            'plugin'     => array_values(array_map(fn (object $plugin): string => $plugin::class, $painel->getPlugins())),
            'middleware' => array_map(mapaTenancyNormalizarMiddleware(...), array_filter($middlewares, 'is_string')),
        ];

        return array_map(fn (array $ids): array => array_values(array_unique($ids)), $registro);
    }
}

if (! function_exists('mapaTenancyRegistroDoModelo')) {
    /**
     * O que o modelo dá ao painel no modo, pelas ligações `registra` que saem de `painel:{id}`, por tipo.
     *
     * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
     * @return array<string, list<string>>
     */
    function mapaTenancyRegistroDoModelo(array $modelo, string $painel, string $modo): array
    {
        $registro = array_fill_keys(MAPA_TENANCY_TIPOS_DE_REGISTRO, []);

        foreach ($modelo['ligacoes'] as $ligacao) {
            if ($ligacao['de'] !== "painel:{$painel}" || $ligacao['verbo'] !== 'registra' || ! in_array($modo, $ligacao['modos'], true)) {
                continue;
            }
            $tipo = $modelo['pecas'][$ligacao['para']]['tipo'] ?? null;
            if (in_array($tipo, MAPA_TENANCY_TIPOS_DE_REGISTRO, true)) {
                $registro[$tipo][] = $ligacao['para'];
            }
        }

        return array_map(fn (array $ids): array => array_values(array_unique($ids)), $registro);
    }
}

if (! function_exists('mapaTenancyConferirRegistro')) {
    /**
     * A guarda do registro (R11): o painel está inteiro no modelo e o modelo não dá ao modo nada além.
     * Devolve os motivos da reprovação; vazio é "aceita".
     *
     * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
     * @return list<string>
     */
    function mapaTenancyConferirRegistro(array $modelo, Panel $painel, string $modo): array
    {
        $vivo    = mapaTenancyRegistroVivo($painel);
        $dado    = mapaTenancyRegistroDoModelo($modelo, $painel->getId(), $modo);
        $motivos = [];

        foreach (MAPA_TENANCY_TIPOS_DE_REGISTRO as $tipo) {
            foreach (array_diff($vivo[$tipo], $dado[$tipo]) as $id) {
                $motivos[] = "o painel {$painel->getId()} registra {$id} ({$tipo}) e o modelo não o liga a ele";
            }
            foreach (array_diff($dado[$tipo], $vivo[$tipo]) as $id) {
                $motivos[] = "o modelo dá {$id} ({$tipo}) ao painel {$painel->getId()} no modo {$modo} e ele não o registra";
            }
        }

        return $motivos;
    }
}

if (! function_exists('mapaTenancyCatalogo')) {
    /**
     * As linhas do catálogo de um idioma: F-nn, "onde fica" (coluna achada pelo cabeçalho) e se está removida.
     *
     * @return list<array{fnn: string, onde: string, removida: bool}>
     */
    function mapaTenancyCatalogo(string $idioma): array
    {
        $linhas     = [];
        $colunaOnde = null;

        foreach (file(base_path("docs/{$idioma}/operacao/roteiro-de-features.md"), FILE_IGNORE_NEW_LINES) ?: [] as $linha) {
            if (! str_starts_with($linha, '|')) {
                continue;
            }
            $celulas = array_map(trim(...), preg_split('/(?<!\\\\)\|/', trim($linha, '| ')) ?: []);

            if (($celulas[0] ?? '') === '#') {
                $colunaOnde = array_search($idioma === 'pt' ? 'Onde' : 'Where', $celulas, true);

                continue;
            }
            if ($colunaOnde === null || ! preg_match('/^F-\d+[a-z]?$/', $celulas[0] ?? '')) {
                continue;
            }
            $linhas[] = [
                'fnn'      => $celulas[0],
                'onde'     => $celulas[$colunaOnde] ?? '',
                'removida' => (bool) preg_match('/remov(ida|ed)/i', $linha),
            ];
        }

        return $linhas;
    }
}

if (! function_exists('mapaTenancyCaminhosComOrganizacao')) {
    /**
     * Os caminhos entre crases que começam por `/` e trazem `{org}` ou `{/org}`, por F-nn.
     *
     * @param  list<array{fnn: string, onde: string, removida: bool}>  $catalogo
     * @return list<array{fnn: string, caminho: string}>
     */
    function mapaTenancyCaminhosComOrganizacao(array $catalogo): array
    {
        $caminhos = [];
        foreach ($catalogo as $linha) {
            preg_match_all('/`(\/[^`]*)`/', $linha['onde'], $achados);
            foreach ($achados[1] as $caminho) {
                if (str_contains($caminho, '{org}') || str_contains($caminho, '{/org}')) {
                    $caminhos[] = ['fnn' => $linha['fnn'], 'caminho' => $caminho];
                }
            }
        }

        return $caminhos;
    }
}

if (! function_exists('mapaTenancyResolveNoRoteador')) {
    /**
     * Os caminhos concretos que o curinga gera (um por painel quando há `{painel}`/`{panel}`) e, para
     * cada um, se o roteador da aplicação o resolve. `{org}` e `{/org}` levam a organização; a query sai.
     *
     * @return array<string, bool> caminho concreto => resolve
     */
    function mapaTenancyResolveNoRoteador(string $caminho, string $organizacao = 'acme'): array
    {
        $caminho   = explode('?', $caminho)[0];
        $caminho   = str_replace(['{/org}', '{org}'], ["/{$organizacao}", $organizacao], $caminho);
        $variantes = preg_match('/\{(painel|panel)\}/', $caminho)
            ? array_map(fn (string $id): string => preg_replace('/\{(painel|panel)\}/', $id, $caminho), array_keys(Filament::getPanels()))
            : [$caminho];

        $resultado = [];
        foreach ($variantes as $variante) {
            $concreto = preg_replace('/\{[^}]+\}/', '1', $variante);
            try {
                $rota = app('router')->getRoutes()->match(Request::create($concreto, 'GET'));
                // o Filament marca como fallback a rota de slug vazio (o dashboard); o catch-all do Laravel é que não conta
                $resultado[$concreto] = $rota->uri() !== '{fallbackPlaceholder}';
            } catch (HttpExceptionInterface|BackedEnumCaseNotFoundException) {
                $resultado[$concreto] = false;
            }
        }

        return $resultado;
    }
}

if (! function_exists('mapaTenancyConferirCaminhos')) {
    /**
     * A guarda da rota (R6): todo caminho com organização do catálogo resolve. Devolve os motivos e os
     * caminhos conferidos por F-nn.
     *
     * @param  list<array{fnn: string, onde: string, removida: bool}>  $catalogo
     * @return array{motivos: list<string>, conferidos: array<string, list<string>>}
     */
    function mapaTenancyConferirCaminhos(array $catalogo): array
    {
        $motivos    = [];
        $conferidos = [];

        foreach (mapaTenancyCaminhosComOrganizacao($catalogo) as ['fnn' => $fnn, 'caminho' => $caminho]) {
            foreach (mapaTenancyResolveNoRoteador($caminho) as $concreto => $resolve) {
                $conferidos[$fnn][] = $concreto;
                if (! $resolve) {
                    $motivos[] = "{$fnn}: o caminho {$caminho} ({$concreto}) não resolve no roteador";
                }
            }
        }

        return ['motivos' => $motivos, 'conferidos' => $conferidos];
    }
}

if (! function_exists('mapaTenancyMotivoDeEnfeite')) {
    /**
     * O motivo vazio, só de travessão ou pontuação, ou "não se aplica" sem dizer por quê.
     */
    function mapaTenancyMotivoDeEnfeite(?string $motivo): bool
    {
        $limpo = trim((string) $motivo);

        return $limpo === ''
            || ! preg_match('/[\p{L}\p{N}]/u', $limpo)
            || preg_match('/^n(ão|ao) se aplica\.?$/iu', $limpo) === 1;
    }
}

if (! function_exists('mapaTenancyConferirFeatures')) {
    /**
     * A guarda das F-nn (R28): toda peça registrada (recurso, página, widget, plugin) entrega ao menos uma
     * F-nn que o catálogo tem, ou está na lista com o motivo. Devolve os motivos e o que conferiu.
     *
     * @param  array{pecas: array<string, array<string, mixed>>}  $modelo
     * @param  list<string>  $catalogo  as F-nn que o catálogo tem (não removidas)
     * @param  array<string, string>  $lista  id da peça => motivo
     * @return array{motivos: list<string>, conferidas: list<string>}
     */
    function mapaTenancyConferirFeatures(array $modelo, array $catalogo, Panel $painel, array $lista): array
    {
        $vivo       = mapaTenancyRegistroVivo($painel);
        $motivos    = [];
        $conferidas = [];

        foreach (['recurso', 'pagina', 'widget', 'plugin'] as $tipo) {
            foreach ($vivo[$tipo] as $id) {
                $conferidas[] = $id;
                $features     = array_intersect($modelo['pecas'][$id]['features'] ?? [], $catalogo);

                if ($features !== []) {
                    continue;
                }
                if (array_key_exists($id, $lista)) {
                    if (mapaTenancyMotivoDeEnfeite($lista[$id])) {
                        $motivos[] = "{$id} está na lista sem motivo";
                    }

                    continue;
                }
                $motivos[] = "{$id} ({$tipo}) do painel {$painel->getId()} não entrega F-nn do catálogo e está fora da lista";
            }
        }

        return ['motivos' => $motivos, 'conferidas' => $conferidas];
    }
}

if (! function_exists('mapaTenancyLigacoesDesenhadas')) {
    /**
     * As ligações de registro (`painel:{id}` registra peça) que um diagrama `architecture` ou `tree` desenha,
     * no modo: a árvore, todas as do painel nesse modo; o mapa de arquitetura, as que têm as duas pontas
     * desenhadas, como peça ou como membro de agrupamento (mesma regra do gerador).
     *
     * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
     * @param  array<string, mixed>  $diagrama
     * @return list<string> ids das peças ligadas ao painel
     */
    function mapaTenancyLigacoesDesenhadas(array $modelo, array $diagrama, string $painel, string $modo): array
    {
        $raiz        = "painel:{$painel}";
        $registradas = array_filter(
            $modelo['ligacoes'],
            fn (array $l): bool => $l['de'] === $raiz && $l['verbo'] === 'registra' && in_array($modo, $l['modos'], true),
        );

        if ($diagrama['tipo'] === 'tree') {
            return array_values(array_map(fn (array $l): string => $l['para'], $registradas));
        }

        $existe     = fn (string $id): bool => count($diagrama['modos']) !== 1 || in_array($diagrama['modos'][0], $modelo['pecas'][$id]['registrada'] ?? [], true);
        $desenhadas = array_filter(array_map(fn (array $p): string => $p['id'], $diagrama['pecas'] ?? []), $existe);
        $membros    = [];
        foreach ($diagrama['agrupamentos'] ?? [] as $grupo) {
            foreach (array_filter($grupo['membros'], $existe) as $membro) {
                $membros[$membro] = $grupo['id'];
            }
        }
        $ponta = fn (string $id): ?string => in_array($id, $desenhadas, true) ? $id : ($membros[$id] ?? null);

        $ids = [];
        foreach ($registradas as $l) {
            $de   = $ponta($l['de']);
            $para = $ponta($l['para']);
            if ($de !== null && $para !== null && $de !== $para) {
                $ids[] = $l['para'];
            }
        }

        return array_values(array_unique($ids));
    }
}

if (! function_exists('mapaTenancyConferirDesenhoDoRegistro')) {
    /**
     * A guarda do desenho (R34): os diagramas `architecture` e a árvore de registro do painel que valem no
     * modo desenham, juntos, o registro vivo dele nesse modo, nem mais nem menos.
     *
     * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
     * @param  list<array<string, mixed>>  $diagramas
     * @return list<string>
     */
    function mapaTenancyConferirDesenhoDoRegistro(array $modelo, array $diagramas, Panel $painel, string $modo): array
    {
        $vivo      = array_merge(...array_values(mapaTenancyRegistroVivo($painel)));
        $desenhado = [];

        foreach ($diagramas as $diagrama) {
            $vale = in_array($diagrama['tipo'], ['architecture', 'tree'], true)
                && in_array($painel->getId(), $diagrama['paineis'] ?? [], true)
                && in_array($modo, $diagrama['modos'] ?? [], true);
            if ($vale) {
                $desenhado = [...$desenhado, ...mapaTenancyLigacoesDesenhadas($modelo, $diagrama, $painel->getId(), $modo)];
            }
        }
        $desenhado = array_values(array_unique($desenhado));

        $motivos = [];
        foreach (array_diff($vivo, $desenhado) as $id) {
            $motivos[] = "o painel {$painel->getId()} registra {$id} no modo {$modo} e nenhum diagrama dele a desenha";
        }
        foreach (array_diff($desenhado, $vivo) as $id) {
            $motivos[] = "os diagramas do painel {$painel->getId()} desenham {$id} no modo {$modo} e ele não a registra";
        }

        return $motivos;
    }
}

/**
 * Os `Dado` de cada caso afirmam o valor lido, e não presumem a suíte.
 */
function mapaTenancyDadoModoComTenant(): void
{
    expect(config('kit.tenancy.enabled'))->toBeTrue();
}

it('[CT-12] com tenant, tudo que o painel registra está no modelo, e o modelo não dá a esse modo nada além disso', function (string $id, bool $tenancy, array $registrado, array $fora): void {
    mapaTenancyDadoModoComTenant();
    $painel = Filament::getPanel($id);
    expect($painel->hasTenancy())->toBe($tenancy);

    $modelo = mapaTenancyModelo();

    // Então 1 e 2: todo registro do painel é destino de ligação dele no modelo, e o modelo não dá nada além
    expect(mapaTenancyConferirRegistro($modelo, $painel, 'com'))->toBe([]);

    $vivo = array_merge(...array_values(mapaTenancyRegistroVivo($painel)));
    $dado = array_merge(...array_values(mapaTenancyRegistroDoModelo($modelo, $id, 'com')));
    expect($vivo)->not->toBeEmpty();

    // E <registrado> está nos dois lados
    foreach ($registrado as $peca) {
        expect($vivo)->toContain($peca)
            ->and($dado)->toContain($peca);
    }

    // E <fora> não está no registro dele, e o modelo não o dá a ele no modo com tenant
    foreach ($fora as $peca) {
        expect($vivo)->not->toContain($peca)
            ->and($dado)->not->toContain($peca);
    }
})->with([
    'app, com tenancy' => ['app', true, [
        IdentifyTenant::class,
        'App\Http\Middleware\DefinirTenantDePermissoes',
    ], ['App\Filament\Admin\Resources\Users\UserResource']],
    'admin, sem tenancy' => ['admin', false, [
        'BezhanSalleh\FilamentShield\FilamentShieldPlugin',
        'App\Filament\Admin\Resources\Tenants\TenantResource',
    ], [IdentifyTenant::class]],
    'infra, sem tenancy' => ['infra', false, [
        'ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin',
        'ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults',
    ], [IdentifyTenant::class]],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O modelo do mapa não viaja no pacote.');

it('[CT-30] com tenant, toda peça que o painel registra entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo', function (string $id, string $ancora): void {
    mapaTenancyDadoModoComTenant();
    $painel   = Filament::getPanel($id);
    $catalogo = array_column(array_filter(mapaTenancyCatalogo('pt'), fn (array $l): bool => ! $l['removida']), 'fnn');
    expect($catalogo)->not->toBeEmpty();

    $resultado = mapaTenancyConferirFeatures(mapaTenancyModelo(), $catalogo, $painel, []);

    expect($resultado['motivos'])->toBe([])
        ->and($resultado['conferidas'])->toContain($ancora);
})->with([
    'app'   => ['app', 'Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin'],
    'admin' => ['admin', 'App\Filament\Admin\Resources\Tenants\TenantResource'],
    'infra' => ['infra', 'ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O modelo e o catálogo do mapa não viajam no pacote.');

it('[CT-38] com tenant, os diagramas de arquitetura e a árvore de registro de cada painel que valem nesse modo desenham o registro dele nesse modo, e nada além', function (string $id, string $mundo, array $nomeados): void {
    mapaTenancyDadoModoComTenant();
    $painel    = Filament::getPanel($id);
    $modelo    = mapaTenancyModelo();
    $diagramas = $modelo['diagramas'];

    if ($mundo === 'só compartilhados') {
        // o `/app` só com os diagramas que valem nos dois modos
        $diagramas = array_values(array_filter($diagramas, fn (array $d): bool => ! in_array($id, $d['paineis'] ?? [], true) || count($d['modos'] ?? []) === 2));
    }

    if ($mundo === 'só no sequence') {
        // o IdentifyTenant só aparece no `sequence` `requisicao-autenticada-com`: sai de todo diagrama de registro ou arquitetura do /app que o desenha
        $diagramas = array_values(array_filter($diagramas, fn (array $d): bool => ! (
            in_array($d['tipo'], ['architecture', 'tree'], true)
            && in_array($id, $d['paineis'] ?? [], true)
            && in_array('com', $d['modos'] ?? [], true)
            && in_array(IdentifyTenant::class, mapaTenancyLigacoesDesenhadas($modelo, $d, $id, 'com'), true)
        )));
        expect(array_column($diagramas, 'slug'))->toContain('requisicao-autenticada-com');
    }

    $motivos = mapaTenancyConferirDesenhoDoRegistro($modelo, $diagramas, $painel, 'com');

    if ($mundo === 'publicados') {
        expect($motivos)->toBe([]);

        return;
    }

    expect($motivos)->not->toBeEmpty();
    $texto = implode("\n", $motivos);
    expect($texto)->toContain("painel {$id}");
    foreach ($nomeados as $peca) {
        expect($texto)->toContain($peca);
    }
})->with([
    'app, publicados'                       => ['app', 'publicados', []],
    'admin, publicados'                     => ['admin', 'publicados', []],
    'infra, publicados'                     => ['infra', 'publicados', []],
    'app, só diagramas compartilhados'      => ['app', 'só compartilhados', [IdentifyTenant::class, 'App\Http\Middleware\DefinirTenantDePermissoes']],
    'app, só no requisicao-autenticada-com' => ['app', 'só no sequence', [IdentifyTenant::class]],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Os diagramas do mapa não viajam no pacote.');

it('[CT-44] com tenant, todo caminho do "onde fica" que leva a organização resolve no roteador', function (string $idioma): void {
    mapaTenancyDadoModoComTenant();
    $catalogo = mapaTenancyCatalogo($idioma);
    expect($catalogo)->not->toBeEmpty();

    $resultado = mapaTenancyConferirCaminhos($catalogo);

    // Então todo caminho resolve
    expect($resultado['motivos'])->toBe([]);

    // E a guarda conferiu ao menos os caminhos da F-15, da F-22 e da F-25
    expect($resultado['conferidos'])->toHaveKeys(['F-15', 'F-22', 'F-25']);
})->with(['pt', 'en'])->skip(fn (): bool => ! naArvoreDoKit(), 'O catálogo não viaja no pacote.');
