<?php

/**
 * As guardas do mapa interativo que só existem com o modo multi-tenant ligado: CT-12 e CT-55 (R11),
 * CT-30 (R28), CT-38 (R34) e CT-44 (R6), todos da costura G3 do `04-casos-de-teste.md` da wiki
 * `feat/mapa-interativo-do-kit`.
 *
 * Escrito a partir do Gherkin do `04`; `app/` e `site/mapa/` foram lidos só para achar NOMES (classe,
 * arquivo, forma do modelo). O modo com tenant só existe com o `Tests\TenancyTestCase`, que fixa
 * `kit.tenancy.enabled` antes do boot.
 *
 * Cada guarda é uma função aplicada ao modelo publicado. Onde há controle, a cópia é adulterada em
 * memória e passa pela mesma função da linha "publicado". O que cada dataset tem (Q26 do `04`):
 * - CT-12, a guarda do registro: só o publicado, uma linha por painel (app, admin e infra).
 * - CT-55, os controles da mesma guarda, sobre os três painéis: o publicado e três cópias do modelo — a
 *   sobra de uma peça fora dos cinco tipos de registro (o Livewire `InformacoesPessoais` dado ao `/app`
 *   só com tenant), a falta do `IdentifyTenant` no `/app` e o `DefinirTenantDePermissoes` dado ao `/admin`.
 * - CT-38, a guarda do desenho: o publicado de cada painel e três cópias — o `/app` só com os diagramas
 *   compartilhados, o `IdentifyTenant` só no `sequence`, e o Livewire que a árvore do `/app` com tenant
 *   desenha e o painel não registra (o "nada além").
 * - CT-30, a guarda das F-nn, e CT-44, a guarda da rota: só o publicado (os três painéis; pt e en), sem
 *   controle em cópia (D38). O achado A15 do step 9 fica com o CT-55 e com a linha do Livewire do CT-38.
 *
 * O modelo, os diagramas, o registro vivo, o que o modelo dá ao painel no modo, o catálogo, a F-nn da peça e
 * o motivo de enfeite moram em `tests/Pest.php`, porque as guardas sem tenant também os usam
 * (`.ai/rules/testes.md`).
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

if (! function_exists('mapaTenancyConferirRegistro')) {
    /**
     * A guarda do registro (R11): o painel está inteiro no modelo e o modelo não dá ao modo nada além,
     * tipo a tipo (o tipo do lado do modelo é o da peça). Devolve os motivos da reprovação; vazio é "aceita".
     *
     * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
     * @return list<string>
     */
    function mapaTenancyConferirRegistro(array $modelo, Panel $painel, string $modo): array
    {
        $vivo    = registroVivoDoPainel($painel);
        $dado    = array_fill_keys(MAPA_TENANCY_TIPOS_DE_REGISTRO, []);
        $motivos = [];

        foreach (registradasNoModelo($modelo, $painel->getId(), $modo) as $id) {
            $tipo = $modelo['pecas'][$id]['tipo'] ?? null;
            if (in_array($tipo, MAPA_TENANCY_TIPOS_DE_REGISTRO, true)) {
                $dado[$tipo][] = $id;

                continue;
            }
            // O painel só registra os cinco tipos: a peça de outro tipo que o modelo dá a ele no modo sobra (R11-M7).
            $motivos[] = "o modelo dá {$id} (".($tipo ?? 'sem tipo').") ao painel {$painel->getId()} no modo {$modo} e ele não o registra";
        }

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

if (! function_exists('mapaTenancyCaminhosComOrganizacao')) {
    /**
     * Os caminhos entre crases que começam por `/` e trazem `{org}` ou `{/org}`, por F-nn, lidos da célula
     * "onde fica" (a quarta) de cada linha do catálogo.
     *
     * @param  list<list<string>>  $catalogo  as linhas de `linhasDoCatalogo()`
     * @return list<array{fnn: string, caminho: string}>
     */
    function mapaTenancyCaminhosComOrganizacao(array $catalogo): array
    {
        $caminhos = [];
        foreach ($catalogo as $celulas) {
            preg_match_all('/`(\/[^`]*)`/', $celulas[3], $achados);
            foreach ($achados[1] as $caminho) {
                if (str_contains($caminho, '{org}') || str_contains($caminho, '{/org}')) {
                    $caminhos[] = ['fnn' => $celulas[0], 'caminho' => $caminho];
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
     * @param  list<list<string>>  $catalogo  as linhas de `linhasDoCatalogo()`
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
        $vivo       = registroVivoDoPainel($painel);
        $motivos    = [];
        $conferidas = [];

        foreach (['recurso', 'pagina', 'widget', 'plugin'] as $tipo) {
            foreach ($vivo[$tipo] as $id) {
                $conferidas[] = $id;

                if (fnnDaPeca($modelo['pecas'], $id, $catalogo) !== []) {
                    continue;
                }
                if (array_key_exists($id, $lista)) {
                    if (motivoDeEnfeite($lista[$id])) {
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
        $vivo      = array_merge(...array_values(registroVivoDoPainel($painel)));
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

    $modelo = modeloDoMapaPorId();

    // Então 1 e 2: todo registro do painel é destino de ligação dele no modelo, e o modelo não dá nada além
    expect(mapaTenancyConferirRegistro($modelo, $painel, 'com'))->toBe([]);

    $vivo = array_merge(...array_values(registroVivoDoPainel($painel)));
    $dado = registradasNoModelo($modelo, $id, 'com');
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

it('[CT-55] com tenant, a guarda do registro reprova o modelo que sobra peça de qualquer tipo, que esquece peça ou que troca o painel', function (string $mundo, string $resultado, array $nomeando): void {
    // Dado o modo com tenant, lido, e os três painéis do registro, cada um com o hasTenancy() lido
    mapaTenancyDadoModoComTenant();
    $paineis = Filament::getPanels();
    expect(array_keys($paineis))->toEqualCanonicalizing(['admin', 'app', 'infra'])
        ->and($paineis['app']->hasTenancy())->toBeTrue()
        ->and($paineis['admin']->hasTenancy())->toBeFalse()
        ->and($paineis['infra']->hasTenancy())->toBeFalse();

    $modelo      = modeloDoMapaPorId();
    $livewire    = 'App\Livewire\InformacoesPessoais';
    $organizacao = 'App\Http\Middleware\DefinirTenantDePermissoes';

    switch ($mundo) {
        case 'Livewire no app':
            // uma peça do tipo Livewire, que o painel app não registra e que o modelo publicado não dá a ele
            expect($modelo['pecas'][$livewire]['tipo'] ?? null)->toBe('livewire')
                ->and(array_merge(...array_values(registroVivoDoPainel($paineis['app']))))->not->toContain($livewire)
                ->and(registradasNoModelo($modelo, 'app', 'com'))->not->toContain($livewire);
            $modelo['ligacoes'][] = ['de' => 'painel:app', 'para' => $livewire, 'verbo' => 'registra', 'paineis' => ['app'], 'modos' => ['com'], 'evidencia' => $modelo['pecas'][$livewire]['evidencia'][0]];
            break;
        case 'sem o IdentifyTenant':
            // o painel app registra o IdentifyTenant com tenant, e a cópia tira a ligação dele
            expect(registroVivoDoPainel($paineis['app'])['middleware'])->toContain(IdentifyTenant::class);
            $antes              = count($modelo['ligacoes']);
            $modelo['ligacoes'] = array_values(array_filter($modelo['ligacoes'], fn (array $l): bool => ! ($l['de'] === 'painel:app' && $l['para'] === IdentifyTenant::class && $l['verbo'] === 'registra')));
            expect(count($modelo['ligacoes']))->toBe($antes - 1)
                ->and(registradasNoModelo($modelo, 'app', 'com'))->not->toContain(IdentifyTenant::class);
            break;
        case 'organização no admin':
            // a ligação do app fica; a cópia dá a mesma peça também ao admin, que não a registra
            $doApp = array_values(array_filter($modelo['ligacoes'], fn (array $l): bool => $l['de'] === 'painel:app' && $l['para'] === $organizacao && $l['verbo'] === 'registra' && in_array('com', $l['modos'] ?? [], true)));
            expect($doApp)->toHaveCount(1)
                ->and(array_merge(...array_values(registroVivoDoPainel($paineis['admin']))))->not->toContain($organizacao);
            $modelo['ligacoes'][] = ['de' => 'painel:admin', 'paineis' => ['admin'], 'modos' => ['com']] + $doApp[0];
            break;
    }

    // Quando a guarda do mapa compara o registro dos três painéis com o modelo, no modo com tenant
    $motivos = [];
    foreach ($paineis as $painel) {
        $motivos = [...$motivos, ...mapaTenancyConferirRegistro($modelo, $painel, 'com')];
    }

    if ($resultado === 'aceita') {
        expect($motivos)->toBe([]);

        return;
    }

    // Então reprova, com um motivo que nomeia a peça e o painel
    expect($motivos === [])->toBeFalse('a guarda aceitou o modelo adulterado: '.$mundo);
    $nomeiam = array_filter($motivos, function (string $motivo) use ($nomeando): bool {
        foreach ($nomeando as $nome) {
            if (! str_contains($motivo, $nome)) {
                return false;
            }
        }

        return true;
    });
    expect($nomeiam)->not->toBeEmpty('nenhum motivo nomeia '.implode(' e ', $nomeando).': '.implode(' | ', $motivos));
})->with([
    'publicado'                                                          => ['publicado', 'aceita', []],
    'cópia que dá ao app, só com tenant, o Livewire InformacoesPessoais' => ['Livewire no app', 'reprova', ['InformacoesPessoais', 'painel app']],
    'cópia sem a ligação do IdentifyTenant no app com tenant'            => ['sem o IdentifyTenant', 'reprova', ['IdentifyTenant', 'painel app']],
    'cópia que dá o DefinirTenantDePermissoes ao admin com tenant'       => ['organização no admin', 'reprova', ['DefinirTenantDePermissoes', 'painel admin']],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O modelo do mapa não viaja no pacote.');

it('[CT-30] com tenant, toda peça que o painel registra entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo', function (string $id, string $ancora): void {
    mapaTenancyDadoModoComTenant();
    $painel   = Filament::getPanel($id);
    $catalogo = array_column(array_filter(linhasDoCatalogo('pt'), fn (array $celulas): bool => preg_match('/remov(ida|ed)/i', implode(' | ', $celulas)) !== 1), 0);
    expect($catalogo)->not->toBeEmpty();

    $resultado = mapaTenancyConferirFeatures(modeloDoMapaPorId(), $catalogo, $painel, []);

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
    $modelo    = modeloDoMapaPorId();
    $diagramas = array_values(diagramasDoMapa());

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

    if ($mundo === 'Livewire na árvore') {
        // a cópia do modelo dá ao /app, só com tenant, o registro de uma peça Livewire que ele não registra; a árvore
        // `app-registro-com`, que o construtor gera do modelo, passa a desenhá-la
        $livewire = 'App\Livewire\InformacoesPessoais';
        expect($modelo['pecas'][$livewire]['tipo'] ?? null)->toBe('livewire')
            ->and(array_merge(...array_values(registroVivoDoPainel($painel))))->not->toContain($livewire)
            ->and(registradasNoModelo($modelo, $id, 'com'))->not->toContain($livewire);
        $modelo['ligacoes'][] = ['de' => "painel:{$id}", 'para' => $livewire, 'verbo' => 'registra', 'paineis' => [$id], 'modos' => ['com'], 'evidencia' => $modelo['pecas'][$livewire]['evidencia'][0]];
        expect(mapaTenancyLigacoesDesenhadas($modelo, diagramasDoMapa()['app-registro-com'], $id, 'com'))->toContain($livewire);
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
    'app, publicados'                                                    => ['app', 'publicados', []],
    'admin, publicados'                                                  => ['admin', 'publicados', []],
    'infra, publicados'                                                  => ['infra', 'publicados', []],
    'app, só diagramas compartilhados'                                   => ['app', 'só compartilhados', [IdentifyTenant::class, 'App\Http\Middleware\DefinirTenantDePermissoes']],
    'app, só no requisicao-autenticada-com'                              => ['app', 'só no sequence', [IdentifyTenant::class]],
    'app, cópia com o Livewire InformacoesPessoais na árvore com tenant' => ['app', 'Livewire na árvore', ['InformacoesPessoais']],
])->skip(fn (): bool => ! naArvoreDoKit(), 'Os diagramas do mapa não viajam no pacote.');

it('[CT-44] com tenant, todo caminho do "onde fica" que leva a organização resolve no roteador', function (string $idioma): void {
    mapaTenancyDadoModoComTenant();
    $catalogo = linhasDoCatalogo($idioma);
    expect($catalogo)->not->toBeEmpty();

    $resultado = mapaTenancyConferirCaminhos($catalogo);

    // Então todo caminho resolve
    expect($resultado['motivos'])->toBe([]);

    // E a guarda conferiu ao menos os caminhos da F-15, da F-22 e da F-25
    expect($resultado['conferidos'])->toHaveKeys(['F-15', 'F-22', 'F-25']);
})->with(['pt', 'en'])->skip(fn (): bool => ! naArvoreDoKit(), 'O catálogo não viaja no pacote.');
