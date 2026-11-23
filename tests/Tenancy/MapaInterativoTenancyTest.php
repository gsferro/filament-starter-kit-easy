<?php

/**
 * As guardas do mapa interativo que só existem com o modo multi-tenant ligado: CT-12 e CT-55 (R11),
 * CT-30 (R28), CT-38 (R34), CT-44 (R6) e CT-56 (R30, com a população da R43), todos da costura G3 do
 * `04-casos-de-teste.md` da wiki `feat/mapa-interativo-do-kit`.
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
 *   desenha e o painel não registra (o "nada além"). Desde o Adendo 2, o conjunto é o registro vivo do painel
 *   com tenant MENOS a peça que fica indisponível nele mesmo com a chave ligada — a saída `lugar` do
 *   discriminante vivo, com o super_admin e a organização aberta no `/app` —, e a ponta desenhada segue a
 *   definição compartilhada do Setup Global: a peça só entra no diagrama que vale num modo se a condição que o
 *   modelo dá a ela a deixa disponível nesse modo. A linha admin nomeia o `TenantResource` (RQ-29).
 * - CT-56, a guarda da condição (R30, nas peças da R43): o publicado e três cópias — o `ProjetoResource` do
 *   `/app` só com o modo, o `AccountWidget` só sem tenant e o `HubDoNegocio` sem a condição. A cópia só
 *   muda a condição do modelo; a disponibilidade é a do discriminante vivo.
 * - CT-30, a guarda das F-nn, e CT-44, a guarda da rota: só o publicado (os três painéis; pt e en), sem
 *   controle em cópia (D38). O achado A15 do step 9 fica com o CT-55 e com a linha do Livewire do CT-38.
 *
 * O modelo, os diagramas, o registro vivo, o que o modelo dá ao painel no modo, o catálogo, a F-nn da peça,
 * o motivo de enfeite, o discriminante, a população dele e o arnês moram em `tests/Pest.php`, porque as
 * guardas sem tenant também os usam (`.ai/rules/testes.md`).
 */

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Tenants\Widgets\AcessosPorPainel;
use App\Filament\App\Pages\HubDoNegocio;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\App\Resources\Users\UserResource as UserResourceDoApp;
use App\Livewire\AssistenteChatWidget;
use App\Livewire\ChecklistDoOnboarding;
use App\Models\Tenant;
use App\Models\User;
use Bityukov\CommandCenter\Filament\Resources\CommandRecordResource;
use Filament\Facades\Filament;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Panel;
use Filament\Widgets\AccountWidget;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        test()->markTestSkipped('O mapa e a página dele só existem na árvore do kit: site/, o diretório das páginas do site e o CHANGELOG têm export-ignore.');
    }
});

/** Os cinco tipos de registro que o `registra` do modelo leva, na ordem do `04`. */
const MAPA_TENANCY_TIPOS_DE_REGISTRO = ['recurso', 'pagina', 'widget', 'plugin', 'middleware'];

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

/**
 * Os caminhos concretos que o curinga gera (um por painel quando há `{painel}`/`{panel}`) e, para
 * cada um, se o roteador da aplicação o resolve. `{org}` e `{/org}` levam a organização; a query sai.
 *
 * @return array<string, bool> caminho concreto => resolve
 */
function mapaTenancyResolveNoRoteador(string $caminho): array
{
    $caminho   = explode('?', $caminho)[0];
    $caminho   = str_replace(['{/org}', '{org}'], ['/acme', 'acme'], $caminho);
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

/**
 * As ligações de registro (`painel:{id}` registra peça) que um diagrama `architecture` ou `tree` desenha,
 * no modo: a árvore, as do painel nesse modo; o mapa de arquitetura, as que têm as duas pontas
 * desenhadas, como peça ou como membro de agrupamento (mesma regra do gerador). Nos dois, só a ponta que
 * entra no diagrama pela definição compartilhada (`pecaAbreNosModosDoDiagrama()`): a guarda que conta a
 * ponta só pelo registro fica vermelha contra a implementação certa.
 *
 * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  array<string, mixed>  $diagrama
 * @return list<string> ids das peças ligadas ao painel
 */
function mapaTenancyLigacoesDesenhadas(array $modelo, array $diagrama, string $painel, string $modo): array
{
    $raiz        = "painel:{$painel}";
    $modos       = $diagrama['modos'] ?? [];
    $entra       = fn (string $id): bool => pecaAbreNosModosDoDiagrama($modelo, $id, $modos);
    $registradas = array_filter(
        $modelo['ligacoes'],
        fn (array $l): bool => $l['de'] === $raiz && $l['verbo'] === 'registra' && in_array($modo, $l['modos'], true),
    );

    if ($diagrama['tipo'] === 'tree') {
        return array_values(array_filter(array_map(fn (array $l): string => $l['para'], $registradas), $entra));
    }

    $existe     = fn (string $id): bool => $entra($id) && (count($modos) !== 1 || in_array($modos[0], $modelo['pecas'][$id]['registrada'] ?? [], true));
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

/**
 * A subtração da R34 com tenant (Adendo 2, colisão a): a peça que o painel registra — recurso, página e
 * widget, a população registrada do discriminante — e que fica indisponível com tenant mesmo com a chave
 * ligada. Sai da saída `lugar` do discriminante vivo, com a condição do modelo só como proposta: a chave
 * errada no modelo não tira do conjunto a peça que a chave certa abre.
 *
 * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @return list<string>
 */
function mapaTenancySubtraidas(array $modelo, string $painel): array
{
    $subtraidas = [];

    foreach (populacaoDoDiscriminante($painel, $modelo)['registrada'] as $peca) {
        if (! discriminanteDaPeca($painel, 'com', $peca, $modelo['pecas'][$peca]['disponibilidade'] ?? null)['lugar']) {
            $subtraidas[] = $peca;
        }
    }

    return $subtraidas;
}

/**
 * A guarda do desenho (R34): os diagramas `architecture` e a árvore de registro do painel que valem no
 * modo desenham, juntos, o registro vivo dele nesse modo menos a peça que fica indisponível nele mesmo com
 * a chave ligada (`mapaTenancySubtraidas()`), nem mais nem menos.
 *
 * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @param  list<array<string, mixed>>  $diagramas
 * @param  list<string>  $subtraidas
 * @return array{motivos: list<string>, esperado: list<string>, desenhado: list<string>}
 */
function mapaTenancyConferirDesenhoDoRegistro(array $modelo, array $diagramas, Panel $painel, string $modo, array $subtraidas): array
{
    $vivo      = array_values(array_unique(array_merge(...array_values(registroVivoDoPainel($painel)))));
    $esperado  = array_values(array_diff($vivo, $subtraidas));
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
    foreach (array_diff($esperado, $desenhado) as $id) {
        $motivos[] = "o painel {$painel->getId()} registra {$id} no modo {$modo}, ela fica disponível nele, e nenhum diagrama dele a desenha";
    }
    foreach (array_diff($desenhado, $esperado) as $id) {
        $motivos[] = in_array($id, $subtraidas, true)
            ? "os diagramas do painel {$painel->getId()} desenham {$id} no modo {$modo}, e ela fica indisponível nesse modo mesmo com a chave ligada"
            : "os diagramas do painel {$painel->getId()} desenham {$id} no modo {$modo} e ele não a registra";
    }

    return ['motivos' => array_values($motivos), 'esperado' => $esperado, 'desenhado' => $desenhado];
}

/**
 * Os `Dado` de cada caso afirmam o valor lido, e não presumem a suíte.
 */
function mapaTenancyDadoModoComTenant(): void
{
    expect(config('kit.tenancy.enabled'))->toBeTrue();
}

/**
 * O `Dado` do CT-56 e do CT-38: o modo com tenant lido, o `/app` com organização, toda outra chave booleana
 * de `kit.*` lida com o valor de fábrica, e o arnês do discriminante — o super_admin do kit autenticado e a
 * organização que ele abre no `/app`.
 *
 * @return array{superAdmin: User, organizacao: Tenant}
 */
function mapaTenancyDadoDoDiscriminante(): array
{
    mapaTenancyDadoModoComTenant();
    expect(Filament::getPanel('app')->hasTenancy())->toBeTrue()
        ->and(booleanasDoKitForaDaFabrica())->toBe([]);

    $arnes = arnesDoDiscriminante();

    expect($arnes['organizacao'])->toBeInstanceOf(Tenant::class)
        ->and(auth()->id())->toBe($arnes['superAdmin']->getKey())
        ->and($arnes['superAdmin']->isMasterGlobal())->toBeTrue()
        ->and($arnes['superAdmin']->canAccessTenant($arnes['organizacao']))->toBeTrue();

    return $arnes;
}

/**
 * A guarda da R30 na suíte com tenant, sobre a população da R43 em cada painel do registro
 * (`populacaoDoDiscriminante()`): o registro vivo, a peça fora do registro com método próprio e o componente
 * flutuante. Cada uma é medida pela saída `guarda` do discriminante vivo, com a condição que o modelo dá a ela
 * (`disponibilidade`), e conferida por `conferirCondicaoNoModo()`. A condição é da peça: a registrada em
 * mais de um painel é conferida em cada um; a que painel nenhum registra tem de dar o mesmo em todos, ou a
 * guarda reprova nomeando a peça e os painéis (falha fechado). Plugin e middleware nunca guardam condição
 * (RQ-32). A exceção do discriminante (chave que a configuração não tem, componente flutuante fora do mapa de
 * predicados) vira motivo, nomeando a peça.
 *
 * @param  array{pecas: array<string, array<string, mixed>>, ligacoes: list<array<string, mixed>>}  $modelo
 * @return array{motivos: list<string>, avaliadas: array<string, array<string, array{deFabrica: bool, comAsChavesDaCondicao: bool}>>}
 */
function mapaTenancyConferirCondicoes(array $modelo): array
{
    $motivos   = [];
    $avaliadas = [];
    $fora      = [];

    foreach ($modelo['pecas'] as $id => $peca) {
        if (in_array($peca['tipo'] ?? null, ['plugin', 'middleware'], true) && array_key_exists('disponibilidade', $peca)) {
            $motivos[] = "{$id} ({$peca['tipo']}) guarda condição no modelo, e plugin e middleware nunca guardam condição (RQ-32)";
        }
    }

    foreach (array_keys(Filament::getPanels()) as $painel) {
        $populacao = populacaoDoDiscriminante($painel, $modelo);
        $fora      = [...$populacao['foraDoRegistro'], ...$populacao['flutuante']];

        foreach ([...$populacao['registrada'], ...$fora] as $peca) {
            try {
                $avaliadas[$peca][$painel] = discriminanteDaPeca($painel, 'com', $peca, $modelo['pecas'][$peca]['disponibilidade'] ?? null)['guarda'];
            } catch (UnexpectedValueException $erro) {
                $motivos[] = "{$peca} no painel {$painel}: {$erro->getMessage()}";
            }
        }
    }

    foreach ($avaliadas as $peca => $porPainel) {
        $condicao = $modelo['pecas'][$peca]['disponibilidade'] ?? null;

        if (! in_array($peca, $fora, true)) {
            foreach ($porPainel as $painel => $medido) {
                $motivos = [...$motivos, ...conferirCondicaoNoModo("{$peca} no painel {$painel}", 'com', $condicao, $medido)];
            }

            continue;
        }

        if (count(array_unique(array_map('serialize', $porPainel))) > 1) {
            $motivos[] = "{$peca}, que painel nenhum registra, dá disponibilidades diferentes nos painéis ".implode(', ', array_keys($porPainel)).': o modelo não tem como dar a ela a condição certa';

            continue;
        }

        $motivos = [...$motivos, ...conferirCondicaoNoModo($peca.' nos painéis '.implode(', ', array_keys($porPainel)), 'com', $condicao, reset($porPainel))];
    }

    return ['motivos' => array_values(array_unique($motivos)), 'avaliadas' => $avaliadas];
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
    // Dado o modo com tenant lido, toda outra chave booleana de kit.* lida de fábrica, o super_admin do kit
    // autenticado e uma organização aberta no /app
    mapaTenancyDadoDoDiscriminante();
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

    // Quando a guarda do mapa junta as ligações de registro que os diagramas do painel que valem com tenant
    // desenham, contra o registro vivo com tenant menos a subtração do discriminante vivo
    $resultado = mapaTenancyConferirDesenhoDoRegistro($modelo, $diagramas, $painel, 'com', mapaTenancySubtraidas($modelo, $id));

    if ($mundo === 'publicados') {
        // Então aceita: o conjunto é o registro do painel com tenant, inclusive <nomeados>
        expect($resultado['esperado'])->not->toBeEmpty();
        foreach ($nomeados as $peca) {
            expect($resultado['esperado'])->toContain($peca)
                ->and($resultado['desenhado'])->toContain($peca);
        }
        expect($resultado['motivos'])->toBe([]);

        return;
    }

    // Então reprova, nomeando o painel e as peças
    expect($resultado['motivos'])->not->toBeEmpty();
    $texto = implode("\n", $resultado['motivos']);
    expect($texto)->toContain("painel {$id}");
    foreach ($nomeados as $peca) {
        expect($texto)->toContain($peca);
    }
})->with([
    'app, publicados'                                                    => ['app', 'publicados', [IdentifyTenant::class, 'App\Http\Middleware\DefinirTenantDePermissoes']],
    'admin, publicados'                                                  => ['admin', 'publicados', [TenantResource::class]],
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

it('[CT-56] com tenant, toda peça registrada que fica indisponível guarda no modelo a condição que a explica, e a peça que a condição dá ao modo com tenant está disponível nele', function (string $mundo, string $resultado, string $nomeando, string $porque): void {
    // Dado o modo multi-tenant ligado, com kit.tenancy.enabled lido como verdadeiro, e toda outra chave booleana
    // de kit.* lida com o valor de fábrica; e o super_admin do kit autenticado e uma organização aberta no /app
    mapaTenancyDadoDoDiscriminante();

    // E o modelo "<mundo>": a cópia muda só a condição do modelo, nunca a disponibilidade
    $modelo = modeloDoMapaPorId();

    switch ($mundo) {
        case 'ProjetoResource só com o modo':
            expect($modelo['pecas'])->toHaveKey(ProjetoResource::class);
            $modelo['pecas'][ProjetoResource::class]['disponibilidade'] = [
                'modo'      => 'com',
                'evidencia' => ['arquivo' => 'app/Filament/App/Resources/Projetos/ProjetoResource.php', 'trecho' => 'public static function canAccess(): bool'],
            ];
            break;
        case 'AccountWidget só sem tenant':
            expect(registroVivoDoPainel(Filament::getPanel('admin'))['widget'])->toContain(AccountWidget::class)
                ->and($modelo['pecas'])->toHaveKey(AccountWidget::class);
            $modelo['pecas'][AccountWidget::class]['disponibilidade'] = [
                'modo'      => 'sem',
                'evidencia' => ['arquivo' => 'app/Providers/Filament/AdminPanelProvider.php', 'trecho' => 'AccountWidget::class,'],
            ];
            break;
        case 'sem a condição do HubDoNegocio':
            expect($modelo['pecas'])->toHaveKey(HubDoNegocio::class);
            unset($modelo['pecas'][HubDoNegocio::class]['disponibilidade']);
            expect($modelo['pecas'][HubDoNegocio::class])->not->toHaveKey('disponibilidade');
            break;
    }

    // Quando a guarda do mapa avalia o canAccess() ou o canView() de cada recurso, página e widget que cada painel
    // registra, e de cada peça do modelo fora do registro que tem um dos dois ou que é componente flutuante, de
    // fábrica e, na peça cuja condição traz chaves, de novo com elas ligadas juntas
    $guarda = mapaTenancyConferirCondicoes($modelo);

    // a população não é vazia, e tem as três frentes da R43: o registro, a peça fora dele e o componente flutuante
    expect($guarda['avaliadas'])->toHaveKeys([
        TenantResource::class, UserResourceDoApp::class, ProjetoResource::class, HubDoNegocio::class, AccountWidget::class,
        CommandRecordResource::class, AcessosPorPainel::class, AssistenteChatWidget::class, ChecklistDoOnboarding::class,
    ]);

    if ($resultado === 'aceita') {
        // E, no publicado, a guarda achou disponíveis o TenantResource, o UserResource do /app e o AcessosPorPainel,
        // e o ProjetoResource do /app indisponível até a KIT_DEMO ligar
        expect($guarda['avaliadas'][TenantResource::class]['admin']['deFabrica'])->toBeTrue('o TenantResource ficou indisponível com tenant')
            ->and($guarda['avaliadas'][UserResourceDoApp::class]['app']['deFabrica'])->toBeTrue('o UserResource do /app ficou indisponível com tenant')
            ->and($guarda['avaliadas'][ProjetoResource::class]['app']['deFabrica'])->toBeFalse('o ProjetoResource do /app abriu com tenant de fábrica');
        foreach ($guarda['avaliadas'][AcessosPorPainel::class] as $painel => $medido) {
            expect($medido['deFabrica'])->toBeTrue("o AcessosPorPainel ficou indisponível com tenant no painel {$painel}");
        }
        $comKitDemo = discriminanteDaPeca('app', 'com', ProjetoResource::class, [
            'modo'      => 'com',
            'chaves'    => [['chave' => 'KIT_DEMO', 'padrao' => false]],
            'evidencia' => ['arquivo' => 'app/Filament/App/Resources/Projetos/ProjetoResource.php', 'trecho' => 'public static function canAccess(): bool'],
        ]);
        expect($comKitDemo['guarda']['deFabrica'])->toBeFalse()
            ->and($comKitDemo['guarda']['comAsChavesDaCondicao'])->toBeTrue('com a KIT_DEMO ligada, o ProjetoResource do /app continua indisponível com tenant');

        // Então o resultado é "aceita"
        expect($guarda['motivos'])->toBe([]);

        return;
    }

    // Então reprova, nomeando a peça, pelo motivo da linha
    expect($guarda['motivos'])->not->toBeEmpty('a guarda aceitou o modelo adulterado: '.$mundo);
    $nomeiam = array_filter($guarda['motivos'], fn (string $motivo): bool => str_contains($motivo, $nomeando) && str_contains($motivo, $porque));
    expect($nomeiam)->not->toBeEmpty("nenhum motivo nomeia {$nomeando} com \"{$porque}\": ".implode(' | ', $guarda['motivos']));
})->with([
    'publicado'                                                                 => ['publicado', 'aceita', '', ''],
    'cópia que dá ao ProjetoResource do /app só o modo com tenant, sem a chave' => ['ProjetoResource só com o modo', 'reprova', ProjetoResource::class.' no painel app', 'a dá como disponível com tenant e de fábrica, e ela está indisponível'],
    'cópia que dá ao AccountWidget do /admin a condição só sem tenant'          => ['AccountWidget só sem tenant', 'reprova', AccountWidget::class.' no painel admin', 'está disponível com tenant e de fábrica'],
    'cópia sem a condição do HubDoNegocio'                                      => ['sem a condição do HubDoNegocio', 'reprova', HubDoNegocio::class.' no painel app', 'o modelo não guarda a condição que a explica'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O modelo do mapa não viaja no pacote.');
