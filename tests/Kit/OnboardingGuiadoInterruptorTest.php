<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as TelaDeConfiguracoes;
use App\Filament\Admin\Resources\Onboarding\CondicaoResource;
use App\Filament\Admin\Resources\Onboarding\JornadaResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Pages\PrimeirosPassos;
use App\Filament\Spotlight\PagesAutorizadasCategory;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Models\User;
use App\Settings\ConfiguracoesDoKit;
use Database\Seeders\OnboardingSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Spatie\Permission\Models\Permission;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingCondition;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingConditions\Pages\CreateOnboardingCondition;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingConditions\Pages\EditOnboardingCondition;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingConditions\Pages\ListOnboardingConditions;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingFlows\Pages\CreateOnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingFlows\Pages\EditOnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingFlows\Pages\ListOnboardingFlows;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingFlows\Pages\ViewOnboardingFlow;

/**
 * Tutorial guiado (wiki `onboarding-guiado`) — lote do interruptor: G1 (config, migration e
 * decisão por request), G2 (tela de configurações), G3 (autoria por URL) e G4 (navegação).
 *
 * O `Então` de cada caso vem do `04-casos-de-teste.md`. O estado do tutorial é fixado por
 * `ligarTutorialGuiado()` (ou pelo caminho banco -> config), nunca pelo `.env` do processo.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** O valor efetivo (dotenv) de uma chave no `.env.example`; linha comentada ou ausente = `null`. */
function valorNoEnvExampleDoOnboarding(string $chave): ?string
{
    return Dotenv\Dotenv::parse(File::get(base_path('.env.example')))[$chave] ?? null;
}

/** A administradora: papel `admin`, com as permissões de autoria como o `PapeisSeeder` as dá. */
function administradoraDoOnboarding(): User
{
    return usuarioDoKit('admin', 'admin@example.com');
}

/** Retira do papel `admin` toda permissão de autoria (jornada e condição) e fixa o painel do Shield. */
function retirarPermissoesDeAutoriaDoAdmin(): void
{
    $nomes = Permission::query()
        ->where('name', 'like', '%:OnboardingFlow')
        ->orWhere('name', 'like', '%:OnboardingCondition')
        ->pluck('name')
        ->all();

    expect($nomes)->not->toBeEmpty();

    semAPermissao('admin', ...$nomes);
    noPainelDoShield('admin');
}

/* ---------------------------------------------------------------------------
| R1 — a instalação nova nasce com o tutorial desligado
--------------------------------------------------------------------------- */

it('[CT-01] a config lida do ambiente de fábrica desliga o tutorial', function (bool $doEnvExample): void {
    $valor = $doEnvExample ? valorNoEnvExampleDoOnboarding('KIT_ONBOARDING') : null;

    expect(kitConfigCom('KIT_ONBOARDING', $valor)['onboarding']['habilitado'])->toBeFalse();
})->with([
    'variável ausente'            => [false],
    'valor do .env.example ativo' => [true],
]);

it('[CT-02] a migration de settings semeia o banco com o que o ambiente diz', function (?string $ambiente, bool $gravado): void {
    $habilitado = kitConfigCom('KIT_ONBOARDING', $ambiente)['onboarding']['habilitado'];

    SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->where('name', 'onboarding_habilitado')
        ->delete();

    expect(SettingsProperty::query()->where('name', 'onboarding_habilitado')->exists())->toBeFalse();

    config(['kit.onboarding.habilitado' => $habilitado]);

    $migrations = glob(database_path('settings/*_add_onboarding_to_kit_settings.php'));
    expect($migrations)->toHaveCount(1);

    $migration = require $migrations[0];
    $migration->up();

    expect(configuracaoGravada('onboarding_habilitado'))->toBe($gravado);
})->with([
    'fábrica (variável ausente)' => [null, false],
    '.env preenchido com true'   => ['true', true],
]);

/* ---------------------------------------------------------------------------
| R2 — o banco vence o .env e a decisão vale no request seguinte
--------------------------------------------------------------------------- */

it('[CT-03] depois do boot, o que está gravado decide, não o .env', function (bool $semente, bool $gravado, string $alvo, string $resultado): void {
    $this->seed(OnboardingSeeder::class);
    $admin = administradoraDoOnboarding();

    ligarTutorialGuiado($semente);
    gravarConfiguracao('onboarding_habilitado', $gravado);
    alinharConfiguracoesDoKit();

    if ($alvo === 'config') {
        expect(config('kit.onboarding.habilitado'))->toBe($gravado);

        return;
    }

    $resposta = abrirComoPessoaNoOnboarding($admin, $alvo);

    match ($resultado) {
        '200 lista' => $resposta->assertOk()->assertSeeLivewire(ListOnboardingFlows::class),
        '404'       => $resposta->assertNotFound(),
        'checklist' => (function () use ($resposta): void {
            $resposta->assertOk();
            afirmarChecklistNaResposta($resposta, true, '/admin');
        })(),
        'sem checklist' => (function () use ($resposta): void {
            $resposta->assertOk();
            afirmarChecklistNaResposta($resposta, false, '/admin');
        })(),
    };
})->with([
    '1 config efetiva'           => [false, true, 'config', ''],
    '2 autoria abre'             => [false, true, '/admin/onboarding-flows', '200 lista'],
    '3 checklist no /admin'      => [false, true, '/admin', 'checklist'],
    '4 autoria 404'              => [true, false, '/admin/onboarding-flows', '404'],
    '5 sem checklist no /admin'  => [true, false, '/admin', 'sem checklist'],
]);

it('[CT-04] o request seguinte obedece ao interruptor, nos dois sentidos, no mesmo processo', function (): void {
    $this->seed(OnboardingSeeder::class);
    $admin = administradoraDoOnboarding();

    ligarTutorialGuiado(true);
    afirmarChecklistNaResposta(abrirComoPessoaNoOnboarding($admin, '/admin')->assertOk(), true, 'primeiro par');
    abrirComoPessoaNoOnboarding($admin, '/admin/onboarding-flows')->assertOk();

    ligarTutorialGuiado(false);
    afirmarChecklistNaResposta(abrirComoPessoaNoOnboarding($admin, '/admin')->assertOk(), false, 'segundo par');
    abrirComoPessoaNoOnboarding($admin, '/admin/onboarding-flows')->assertNotFound();

    ligarTutorialGuiado(true);
    afirmarChecklistNaResposta(abrirComoPessoaNoOnboarding($admin, '/admin')->assertOk(), true, 'terceiro par');
    abrirComoPessoaNoOnboarding($admin, '/admin/onboarding-flows')->assertOk()->assertSeeLivewire(ListOnboardingFlows::class);
});

/* ---------------------------------------------------------------------------
| R3 — a tela de configurações grava o interruptor
--------------------------------------------------------------------------- */

it('[CT-05] a administradora liga e desliga o tutorial pela tela', function (bool $antes, bool $preenche, bool $gravado): void {
    gravarConfiguracao('onboarding_habilitado', $antes);

    $this->actingAs(administradoraDoOnboarding());

    $tela = Livewire::test(TelaDeConfiguracoes::class);
    carregarAba($tela, 'aplicacao');
    chaveDoCampo($tela, 'onboarding_habilitado');

    $tela->fillForm(['onboarding_habilitado' => $preenche])->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada('onboarding_habilitado'))->toBe($gravado);

    alinharConfiguracoesDoKit();

    expect(config('kit.onboarding.habilitado'))->toBe($gravado);
})->with([
    'liga'    => [false, true, true],
    'desliga' => [true, false, false],
]);

it('[CT-06] o settings do tutorial é um interruptor só, sem lista de painéis', function (): void {
    $propriedades = array_keys(array_filter(
        ConfiguracoesDoKit::mapaDeConfiguracao(),
        static fn (string $chave): bool => str_starts_with($chave, 'kit.onboarding.'),
    ));

    expect($propriedades)->toHaveCount(1)
        ->and((new ReflectionProperty(ConfiguracoesDoKit::class, $propriedades[0]))->getType()?->getName())->toBe('bool');

    $this->actingAs(administradoraDoOnboarding());

    $tela = Livewire::test(TelaDeConfiguracoes::class);
    carregarAba($tela, 'aplicacao');

    $secao = $tela->instance()->form->getComponent(
        fn ($componente): bool => $componente instanceof Section && $componente->getHeading() === 'Tutorial guiado',
        withHidden: true,
    );

    expect($secao)->not->toBeNull('a seção do tutorial não existe na aba Aplicação');

    $campos = [];

    foreach ($secao->getChildComponents() as $filho) {
        if ($filho instanceof Field) {
            $campos[] = $filho;
        }
    }

    expect($campos)->toHaveCount(1)
        ->and($campos[0])->toBeInstanceOf(Toggle::class)
        ->and($campos[0]->getName())->toBe($propriedades[0]);
});

it('[CT-58] o que a tela grava vale no request seguinte, nos dois sentidos', function (bool $antes, bool $depois, string $alvo, string $resultado): void {
    $this->seed(OnboardingSeeder::class);
    $admin = administradoraDoOnboarding();

    gravarConfiguracao('onboarding_habilitado', $antes);
    alinharConfiguracoesDoKit();

    abrirComoPessoaNoOnboarding($admin, '/admin')->assertOk();

    $this->actingAs($admin);
    $tela = Livewire::test(TelaDeConfiguracoes::class);
    carregarAba($tela, 'aplicacao');
    $tela->fillForm(['onboarding_habilitado' => $depois])->call('save')->assertHasNoFormErrors();

    // O boot do request seguinte (o processo de teste é um só e não o refaz): lê o banco que a
    // tela gravou. Nenhum `gravarConfiguracao()` nem `config()->set()` no meio.
    alinharConfiguracoesDoKit();

    $resposta = abrirComoPessoaNoOnboarding($admin, $alvo);

    match ($resultado) {
        'sem checklist' => (function () use ($resposta): void {
            $resposta->assertOk();
            afirmarChecklistNaResposta($resposta, false, 'depois de salvar');
        })(),
        'checklist' => (function () use ($resposta): void {
            $resposta->assertOk();
            afirmarChecklistNaResposta($resposta, true, 'depois de salvar');
        })(),
        '404'       => $resposta->assertNotFound(),
        '200 lista' => $resposta->assertOk()->assertSeeLivewire(ListOnboardingFlows::class),
    };
})->with([
    'desliga, /admin'    => [true, false, '/admin', 'sem checklist'],
    'desliga, autoria'   => [true, false, '/admin/onboarding-flows', '404'],
    'liga, /admin'       => [false, true, '/admin', 'checklist'],
    'liga, autoria'      => [false, true, '/admin/onboarding-flows', '200 lista'],
]);

/* ---------------------------------------------------------------------------
| R5 — as rotas de autoria
--------------------------------------------------------------------------- */

/** @return array{0: string, 1: class-string} a URL e a Page de cada rota de autoria. */
function rotaDeAutoriaDoOnboarding(string $rota): array
{
    $jornada  = (string) OnboardingFlow::query()->where('panel_id', 'admin')->value('id');
    $condicao = (string) OnboardingCondition::query()->value('id');

    expect($jornada)->not->toBe('')->and($condicao)->not->toBe('');

    return match ($rota) {
        'jornadas'          => ['/admin/onboarding-flows', ListOnboardingFlows::class],
        'jornadas-criar'    => ['/admin/onboarding-flows/create', CreateOnboardingFlow::class],
        'jornadas-ver'      => ["/admin/onboarding-flows/{$jornada}", ViewOnboardingFlow::class],
        'jornadas-editar'   => ["/admin/onboarding-flows/{$jornada}/edit", EditOnboardingFlow::class],
        'condicoes'         => ['/admin/onboarding-conditions', ListOnboardingConditions::class],
        'condicoes-criar'   => ['/admin/onboarding-conditions/create', CreateOnboardingCondition::class],
        'condicoes-editar'  => ["/admin/onboarding-conditions/{$condicao}/edit", EditOnboardingCondition::class],
    };
}

it('[CT-08] as sete rotas de autoria respondem 404 com o tutorial desligado, para qualquer persona', function (string $persona, string $rota): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(false);

    $pessoa = match ($persona) {
        'master' => usuarioDoKit('master_global', 'master@example.com'),
        default  => administradoraDoOnboarding(),
    };

    if ($persona === 'sem') {
        retirarPermissoesDeAutoriaDoAdmin();
    }

    [$url, $pagina] = rotaDeAutoriaDoOnboarding($rota);

    abrirComoPessoaNoOnboarding($pessoa, $url)
        ->assertNotFound()
        ->assertDontSeeLivewire($pagina);
})->with([
    'com, jornadas'           => ['com', 'jornadas'],
    'com, jornadas criar'     => ['com', 'jornadas-criar'],
    'com, jornadas ver'       => ['com', 'jornadas-ver'],
    'com, jornadas editar'    => ['com', 'jornadas-editar'],
    'com, condições'          => ['com', 'condicoes'],
    'com, condições criar'    => ['com', 'condicoes-criar'],
    'com, condições editar'   => ['com', 'condicoes-editar'],
    'sem, jornadas'           => ['sem', 'jornadas'],
    'sem, condições editar'   => ['sem', 'condicoes-editar'],
    'master, jornadas editar' => ['master', 'jornadas-editar'],
    'master, condições'       => ['master', 'condicoes'],
]);

it('[CT-09] com o tutorial ligado, a permissão decide como nas outras telas do kit', function (bool $comPermissao, string $rota, int $status): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(true);

    $admin = administradoraDoOnboarding();

    if (! $comPermissao) {
        retirarPermissoesDeAutoriaDoAdmin();
    }

    [$url, $pagina] = rotaDeAutoriaDoOnboarding($rota);

    $resposta = abrirComoPessoaNoOnboarding($admin, $url)->assertStatus($status);

    if ($status === 200) {
        $resposta->assertSeeLivewire($pagina);
    } else {
        $resposta->assertDontSeeLivewire($pagina);
    }
})->with([
    'com, jornadas'         => [true, 'jornadas', 200],
    'com, jornadas editar'  => [true, 'jornadas-editar', 200],
    'com, condições'        => [true, 'condicoes', 200],
    'sem, jornadas'         => [false, 'jornadas', 403],
    'sem, condições'        => [false, 'condicoes', 403],
]);

it('[CT-10] depois do 404 da autoria, o painel de administração continua abrindo', function (): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(false);

    $admin = administradoraDoOnboarding();

    abrirComoPessoaNoOnboarding($admin, '/admin/onboarding-flows')->assertNotFound();

    $resposta = abrirComoPessoaNoOnboarding($admin, '/admin')->assertOk();
    $html     = corpoDepoisDoTitulo($resposta->getContent());

    $this->assertStringContainsString(
        parse_url(UserResource::getUrl(panel: 'admin'), PHP_URL_PATH),
        $html,
    );
    $this->assertStringNotContainsString('/admin/onboarding-flows', $html);
    $this->assertStringNotContainsString('/admin/onboarding-conditions', $html);
});

/* ---------------------------------------------------------------------------
| R5 (CT-46) — a tela de autoria aberta recusa a ação depois do desligar
--------------------------------------------------------------------------- */

/**
 * O snapshot de um componente Livewire dentro do HTML de uma tela real, escolhido pelo nome.
 *
 * @return array<string, mixed>
 */
function snapshotDoComponenteNaTela(string $html, string $trechoDoNome): array
{
    preg_match_all('~wire:snapshot="([^"]*)"~', $html, $achados);

    $nomes = [];

    foreach ($achados[1] as $bruto) {
        $snapshot = json_decode(html_entity_decode($bruto, ENT_QUOTES), true);
        $nome     = strtolower((string) ($snapshot['memo']['name'] ?? ''));
        $nomes[]  = $nome;

        if (is_array($snapshot) && str_contains($nome, $trechoDoNome)) {
            return $snapshot;
        }
    }

    throw new RuntimeException("Nenhum componente com '{$trechoDoNome}' no HTML da tela (achados: ".implode(', ', $nomes).', '.substr_count($html, 'wire:snapshot').' marcas).');
}

/**
 * Envia uma ação pela rota HTTP de update do Livewire, com o snapshot de uma tela aberta de
 * verdade: é assim que o middleware persistente do painel é reaplicado (`Livewire::test` não o
 * aplica, `PersistentMiddleware.php:isLivewireRoute`).
 *
 * @param  array<string, mixed>  $snapshot
 * @param  array<string, mixed>  $atualizacoes
 * @param  list<array{method: string, params: array<int, mixed>}>  $chamadas
 */
function acaoPelaRotaDeUpdateDoOnboarding(array $snapshot, array $atualizacoes, array $chamadas): TestResponse
{
    fronteiraDeRequest();

    return test()->withHeaders(['X-Livewire' => 'true'])->postJson(app('livewire')->getUpdateUri(), [
        'components' => [[
            'snapshot' => json_encode($snapshot),
            'updates'  => $atualizacoes,
            'calls'    => array_map(static fn (array $chamada): array => ['path' => '', ...$chamada], $chamadas),
        ]],
    ]);
}

it('[CT-46] a tela de autoria aberta com o tutorial ligado recusa a ação depois do desligar', function (string $tela): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(true);

    $admin    = administradoraDoOnboarding();
    $jornada  = OnboardingFlow::query()->where('panel_id', 'admin')->firstOrFail();
    $condicao = OnboardingCondition::query()->firstOrFail();

    $antes = [
        'jornadas'  => OnboardingFlow::query()->count(),
        'passos'    => DB::table('onboarding_steps')->count(),
        'condicoes' => OnboardingCondition::query()->count(),
        'titulo'    => $jornada->getRawOriginal('title'),
        'rotulo'    => $condicao->getRawOriginal('label'),
        'lista'     => DB::table('onboarding_steps')->where('flow_id', $jornada->getKey())->pluck('key')->sort()->values()->all(),
    ];

    [$url, $trecho, $atualizacoes, $chamadas] = match ($tela) {
        'edição da jornada'   => ["/admin/onboarding-flows/{$jornada->getKey()}/edit", 'editonboardingflow', ['data.title.pt_BR' => 'Texto novo'], [['method' => 'save', 'params' => []]]],
        'edição da condição'  => ["/admin/onboarding-conditions/{$condicao->getKey()}/edit", 'editonboardingcondition', ['data.label.pt_BR' => 'Texto novo'], [['method' => 'save', 'params' => []]]],
        'lista de jornadas'   => ['/admin/onboarding-flows', 'listonboardingflows', ['tableSort' => 'title:asc'], []],
        'passos da jornada'   => ["/admin/onboarding-flows/{$jornada->getKey()}/edit", 'stepsrelationmanager', [], [['method' => 'loadTable', 'params' => []], ['method' => 'mountAction', 'params' => ['create', [], ['table' => true]]]]],
    };

    $html     = abrirComoPessoaNoOnboarding($admin, $url)->assertOk()->getContent();
    $snapshot = snapshotDoComponenteNaTela($html, $trecho);

    ligarTutorialGuiado(false);

    acaoPelaRotaDeUpdateDoOnboarding($snapshot, $atualizacoes, $chamadas)->assertNotFound();

    expect(OnboardingFlow::query()->count())->toBe($antes['jornadas'])
        ->and(DB::table('onboarding_steps')->count())->toBe($antes['passos'])
        ->and(OnboardingCondition::query()->count())->toBe($antes['condicoes'])
        ->and(OnboardingFlow::query()->findOrFail($jornada->getKey())->getRawOriginal('title'))->toBe($antes['titulo'])
        ->and(OnboardingCondition::query()->findOrFail($condicao->getKey())->getRawOriginal('label'))->toBe($antes['rotulo'])
        ->and(DB::table('onboarding_steps')->where('flow_id', $jornada->getKey())->pluck('key')->sort()->values()->all())->toBe($antes['lista']);

    // Controle: com o tutorial ligado, a mesma ação passa pela rota (não é um 404 do arranjo). O
    // gerenciador de passos fica sem controle: um update cru ao componente dele estoura em
    // `RelationManager::$table` (tabela não inicializada) antes de ser uma ação.
    if ($tela !== 'passos da jornada') {
        ligarTutorialGuiado(true);

        acaoPelaRotaDeUpdateDoOnboarding($snapshot, $atualizacoes, $chamadas)->assertOk();
    }
})->with(['edição da jornada', 'edição da condição', 'passos da jornada', 'lista de jornadas']);

/* ---------------------------------------------------------------------------
| R4 / R6 — a navegação
--------------------------------------------------------------------------- */

/** Hub, dock e `/admin` bootado, com o tutorial no estado pedido. */
function prepararSuperficiesDoOnboarding(string $painel, User $pessoa, bool $ligado): void
{
    config(['kit.hub' => true]);
    concederDock('admin', 'panel_user', 'infra');
    ligarDock(['admin', 'app', 'infra']);
    ligarTutorialGuiado($ligado);

    fronteiraDeRequest();
    test()->actingAs($pessoa);

    if ($painel === 'app') {
        test()->get('/app')->assertOk();
        Filament::setCurrentPanel('app');
    } else {
        noPainelBootado($painel);
    }
}

it('[CT-07] cada superfície lista a autoria só com o tutorial ligado', function (string $superficie, string $tela, bool $ligado): void {
    $this->seed(OnboardingSeeder::class);
    prepararSuperficiesDoOnboarding('admin', administradoraDoOnboarding(), $ligado);

    $recurso = $tela === 'jornadas' ? JornadaResource::class : CondicaoResource::class;
    $url     = $recurso::getUrl('index', panel: 'admin');
    $chave   = chaveDeAtalho('admin', $recurso);

    $achou = match ($superficie) {
        'barra' => in_array($url, urlsDaBarraLateralDoOnboarding(), true),
        'hub'   => in_array($url, urlsDoHubDeAdministracaoDoOnboarding(), true),
        'telas' => collect((new ResourcesAutorizadasCategory)->search($recurso::getPluralModelLabel(), 20))
            ->contains(fn ($resultado): bool => ($resultado->payload['resource'] ?? null) === $recurso || $resultado->url === $url),
        'dock' => in_array($chave, array_keys(opcoesDoSlideover(dockNaPagina('admin'))), true),
    };

    expect($achou)->toBe($ligado, "{$superficie}: {$tela} ".($ligado ? 'deveria estar' : 'não deveria estar'));
})->with([
    'barra jornadas desligado'  => ['barra', 'jornadas', false],
    'barra condições desligado' => ['barra', 'condicoes', false],
    'hub jornadas desligado'    => ['hub', 'jornadas', false],
    'telas condições desligado' => ['telas', 'condicoes', false],
    'dock jornadas desligado'   => ['dock', 'jornadas', false],
    'barra jornadas ligado'     => ['barra', 'jornadas', true],
    'barra condições ligado'    => ['barra', 'condicoes', true],
    'hub jornadas ligado'       => ['hub', 'jornadas', true],
    'telas condições ligado'    => ['telas', 'condicoes', true],
    'dock jornadas ligado'      => ['dock', 'jornadas', true],
]);

it('[CT-44] "Primeiros passos" some de toda superfície de navegação com o tutorial desligado', function (string $papel, string $painel, string $superficie, bool $ligado): void {
    $this->seed(OnboardingSeeder::class);

    $pessoa = usuarioDoKit($papel, "{$papel}@example.com");
    prepararSuperficiesDoOnboarding($painel, $pessoa, $ligado);

    $url   = PrimeirosPassos::getUrl(panel: $painel);
    $chave = chaveDeAtalho($painel, PrimeirosPassos::class);

    $achou = match ($superficie) {
        'hub'   => in_array($url, urlsDoHubDeAdministracaoDoOnboarding(), true),
        'telas' => collect((new PagesAutorizadasCategory)->search('Primeiros', 20))
            ->contains(fn ($resultado): bool => ($resultado->payload['page'] ?? null) === PrimeirosPassos::class),
        'dock' => in_array($chave, array_keys(opcoesDoSlideover(dockNaPagina($painel))), true),
    };

    expect($achou)->toBe($ligado, "{$superficie} de /{$painel}: a página ".($ligado ? 'deveria estar' : 'não deveria estar'));
})->with([
    'admin hub desligado'    => ['admin', 'admin', 'hub', false],
    'admin telas desligado'  => ['admin', 'admin', 'telas', false],
    'admin dock desligado'   => ['admin', 'admin', 'dock', false],
    'app dock desligado'     => ['panel_user', 'app', 'dock', false],
    'infra telas desligado'  => ['infra', 'infra', 'telas', false],
    'admin hub ligado'       => ['admin', 'admin', 'hub', true],
    'admin telas ligado'     => ['admin', 'admin', 'telas', true],
    'admin dock ligado'      => ['admin', 'admin', 'dock', true],
    'infra telas ligado'     => ['infra', 'infra', 'telas', true],
]);
