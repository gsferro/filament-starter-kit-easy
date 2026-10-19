<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as TelaDeConfiguracoes;
use App\Filament\Admin\Pages\HubDeAdministracao;
use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use App\Filament\Admin\Resources\AgentesIa\Pages\CreateAgenteIa;
use App\Filament\Admin\Resources\AgentesIa\Pages\EditAgenteIa;
use App\Filament\Admin\Resources\AgentesIa\Pages\ListAgentesIa;
use App\Filament\Admin\Resources\Users\UserResource as UserResourceDoAdmin;
use App\Filament\Spotlight\AcoesDeCriacao;
use App\Filament\Spotlight\ResourcesAutorizadasCategory;
use App\Livewire\AssistenteChatWidget;
use App\Models\AgenteIa;
use App\Models\User;
use App\Settings\ConfiguracoesDoKit;
use Database\Seeders\AssistenteSeeder;
use Database\Seeders\GuardaPromptSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Models\Conversation;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Wezlo\FilamentSearchSpotlight\Actions\SpotlightActionRegistry;
use Wezlo\FilamentSearchSpotlight\Categories\RecordsCategory;

/**
 * Agentes de IA ligados pelo settings, por painel — lote A (configuração, tela de
 * configurações e catálogo: R1 a R7 e R6b) da wiki `agentes-ia-no-settings`.
 *
 * O `Então` de cada caso vem do `04-casos-de-teste.md`. Fixture por `ligarAgentesDeIa()` e pelo
 * caminho banco -> config (`gravarConfiguracao()` + `alinharConfiguracoesDoKit()`), nunca pelo
 * `.env` do processo.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, AssistenteSeeder::class, GuardaPromptSeeder::class]);
    config(['ai.providers.llamacpp.models.text.default' => 'modelo-de-teste']);
});

/**
 * O `config/kit.php` relido com várias chaves de ambiente ao mesmo tempo (`null` = ausente).
 * `kitConfigCom()` de `tests/Pest.php` troca uma chave só.
 *
 * @param  array<string, string|null>  $ambiente
 * @return array<string, mixed>
 */
function configDoKitComAmbiente(array $ambiente): array
{
    $anteriores = [];

    foreach ($ambiente as $chave => $valor) {
        $anteriores[$chave] = $_ENV[$chave] ?? null;

        if ($valor === null) {
            unset($_ENV[$chave], $_SERVER[$chave]);
            putenv($chave);
        } else {
            $_ENV[$chave]    = $valor;
            $_SERVER[$chave] = $valor;
            putenv("{$chave}={$valor}");
        }
    }

    try {
        return require base_path('config/kit.php');
    } finally {
        foreach ($anteriores as $chave => $valor) {
            if ($valor === null) {
                unset($_ENV[$chave], $_SERVER[$chave]);
                putenv($chave);
            } else {
                $_ENV[$chave]    = $valor;
                $_SERVER[$chave] = $valor;
                putenv("{$chave}={$valor}");
            }
        }
    }
}

/** Os valores efetivos do `.env.example` (linha comentada = ausente = `null`). */
function valorNoEnvExample(string $chave): ?string
{
    return Dotenv\Dotenv::parse(File::get(base_path('.env.example')))[$chave] ?? null;
}

/** O Bruno: entra nos três painéis e tem a permissão do catálogo (a do `admin`). */
function brunoDoCatalogo(): User
{
    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    $bruno->assignRole(['panel_user', 'infra']);

    return $bruno;
}

/** O chat do Assistente está (ou não) no corpo da resposta: Livewire e marcador no HTML sem `<script>`. */
function afirmarChatNaResposta(TestResponse $resposta, bool $presente, string $contexto = ''): void
{
    $html = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $resposta->getContent());

    if ($presente) {
        $resposta->assertSeeLivewire(AssistenteChatWidget::class);
        expect($html)->toContain('data-assistente-chat');

        return;
    }

    $resposta->assertDontSeeLivewire(AssistenteChatWidget::class);
    expect(str_contains($html, 'data-assistente-chat'))->toBeFalse("o chat apareceu: {$contexto}");
}

/** Abre a URL de um painel como a pessoa, depois de o request anterior ter terminado. */
function abrirNoPainel(User $pessoa, string $url): TestResponse
{
    fronteiraDeRequest();

    return test()->actingAs($pessoa)->get($url);
}

function urlDoCatalogo(): string
{
    return AgenteIaResource::getUrl('index', panel: 'admin');
}

/** O id do agente `assistente` do catálogo semeado. */
function idDoAssistente(): string
{
    return (string) AgenteIa::query()->where('slug', 'assistente')->value('uuid');
}

/* ---------------------------------------------------------------------------
| R1 — instalação nova e `.env` de fábrica nascem desligados e sem painel
--------------------------------------------------------------------------- */

it('[CT-01] a config lida do ambiente de fábrica desliga o uso e não escolhe painel', function (?string $uso, ?string $paineis, bool $doEnvExample): void {
    if ($doEnvExample) {
        $uso     = valorNoEnvExample('KIT_AGENTES_IA');
        $paineis = valorNoEnvExample('KIT_AGENTES_IA_PAINEIS');
    }

    $config = configDoKitComAmbiente(['KIT_AGENTES_IA' => $uso, 'KIT_AGENTES_IA_PAINEIS' => $paineis]);

    expect($config['agentes_ia']['habilitado'])->toBeFalse()
        ->and($config['agentes_ia']['paineis'])->toBe([]);
})->with([
    'instalação sem as chaves'       => [null, null, false],
    '.env de fábrica (.env.example)' => [null, null, true],
]);

it('[CT-02] a migration de settings semeia o banco com o que o ambiente diz', function (?string $uso, ?string $paineis, bool $usoGravado, array $listaGravada): void {
    $config = configDoKitComAmbiente(['KIT_AGENTES_IA' => $uso, 'KIT_AGENTES_IA_PAINEIS' => $paineis]);

    SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->whereIn('name', ['agentes_ia_habilitado', 'agentes_ia_paineis'])
        ->delete();

    expect(SettingsProperty::query()->where('name', 'agentes_ia_habilitado')->exists())->toBeFalse();

    config([
        'kit.agentes_ia.habilitado' => $config['agentes_ia']['habilitado'],
        'kit.agentes_ia.paineis'    => $config['agentes_ia']['paineis'],
    ]);

    $migrations = glob(database_path('settings/*_add_agentes_ia_to_kit_settings.php'));
    expect($migrations)->toHaveCount(1);

    $migration = require $migrations[0];
    $migration->up();

    expect(configuracaoGravada('agentes_ia_habilitado'))->toBe($usoGravado)
        ->and(configuracaoGravada('agentes_ia_paineis'))->toBe($listaGravada);
})->with([
    'fábrica'         => [null, null, false, []],
    '.env preenchido' => ['true', 'admin,infra', true, ['admin', 'infra']],
]);

/* ---------------------------------------------------------------------------
| R2 — sem a chave na config, o uso fica desligado
--------------------------------------------------------------------------- */

it('[CT-03] config sem o bloco ou com a lista nula falha fechado', function (string $estado, string $url, int $status, ?bool $chat, ?string $painel): void {
    $bruno = brunoDoCatalogo();

    if ($estado === 'sem o bloco') {
        $kit = config('kit');
        unset($kit['agentes_ia']);
        config(['kit' => $kit]);
    } else {
        config(['kit.agentes_ia.habilitado' => true, 'kit.agentes_ia.paineis' => null]);
    }

    $resposta = $this->actingAs($bruno)->get($url);

    if ($status === 404) {
        $resposta->assertNotFound();
    } else {
        $resposta->assertOk();
    }

    if ($url === '/admin/agentes-ia' && $status === 200) {
        $resposta->assertSeeLivewire(ListAgentesIa::class);
    }

    if ($chat !== null) {
        afirmarChatNaResposta($resposta, $chat, "{$estado} em {$url}");
    }
})->with([
    'bloco ausente, catálogo'       => ['sem o bloco', '/admin/agentes-ia', 404, null, null],
    'bloco ausente, /admin'         => ['sem o bloco', '/admin', 200, false, 'admin'],
    'lista nula, /admin'            => ['lista nula', '/admin', 200, false, 'admin'],
    'lista nula, /app'              => ['lista nula', '/app', 200, false, 'app'],
    'lista nula, catálogo (ligado)' => ['lista nula', '/admin/agentes-ia', 200, null, null],
]);

/* ---------------------------------------------------------------------------
| R3 — o banco vence o .env; a decisão vale por request, sem consulta ao settings
--------------------------------------------------------------------------- */

it('[CT-04] depois do boot, o que está gravado decide, não o .env', function (bool $usoSemente, array $listaSemente, bool $usoGravado, array $listaGravada, string $alvo, string $resultado): void {
    $bruno = brunoDoCatalogo();

    ligarAgentesDeIa($usoSemente, $listaSemente);
    gravarConfiguracao('agentes_ia_habilitado', $usoGravado);
    gravarConfiguracao('agentes_ia_paineis', $listaGravada);
    alinharConfiguracoesDoKit();

    if ($alvo === 'config') {
        expect(config('kit.agentes_ia.habilitado'))->toBe($usoGravado)
            ->and(config('kit.agentes_ia.paineis'))->toBe($listaGravada);

        return;
    }

    $resposta = $this->actingAs($bruno)->get($alvo);

    match ($resultado) {
        '200 lista' => $resposta->assertOk()->assertSeeLivewire(ListAgentesIa::class),
        '404'       => $resposta->assertNotFound(),
        'chat'      => (function () use ($resposta): void {
            $resposta->assertOk();
            afirmarChatNaResposta($resposta, true);
        })(),
        'sem chat' => (function () use ($resposta, $alvo): void {
            $resposta->assertOk();
            afirmarChatNaResposta($resposta, false, $alvo);
        })(),
    };
})->with([
    '1 config efetiva'        => [false, [], true, ['admin'], 'config', ''],
    '2 catálogo abre'         => [false, [], true, ['admin'], '/admin/agentes-ia', '200 lista'],
    '3 chat no /admin'        => [false, [], true, ['admin'], '/admin', 'chat'],
    '4 catálogo 404'          => [true, ['admin', 'app', 'infra'], false, ['admin', 'app', 'infra'], '/admin/agentes-ia', '404'],
    '5 sem chat no /admin'    => [true, ['admin', 'app', 'infra'], false, ['admin', 'app', 'infra'], '/admin', 'sem chat'],
    '6 chat no /infra'        => [true, ['admin'], true, ['infra'], '/infra', 'chat'],
    '7 sem chat no /app'      => [true, ['admin', 'app'], true, ['infra'], '/app', 'sem chat'],
    '8 lista vazia no /admin' => [true, ['admin'], true, [], '/admin', 'sem chat'],
]);

it('[CT-05] o request seguinte já obedece à mudança, no mesmo processo', function (bool $uso, array $lista, string $alvo, string $resultado): void {
    $bruno = brunoDoCatalogo();

    ligarAgentesDeIa(true, ['admin']);

    $this->actingAs($bruno)->get('/admin/agentes-ia')->assertOk()->assertSeeLivewire(ListAgentesIa::class);
    afirmarChatNaResposta(abrirNoPainel($bruno, '/admin')->assertOk(), true, 'primeiro request');

    ligarAgentesDeIa($uso, $lista);

    $resposta = abrirNoPainel($bruno, $alvo);

    if ($resultado === '404') {
        $resposta->assertNotFound();
    } elseif ($resultado === '200 lista') {
        $resposta->assertOk()->assertSeeLivewire(ListAgentesIa::class);
    } else {
        $resposta->assertOk();
        afirmarChatNaResposta($resposta, false, 'request seguinte');
    }
})->with([
    'desligado, catálogo'    => [false, ['admin'], '/admin/agentes-ia', '404'],
    'desligado, /admin'      => [false, ['admin'], '/admin', 'sem chat'],
    'lista vazia, catálogo'  => [true, [], '/admin/agentes-ia', '200 lista'],
    'lista vazia, /admin'    => [true, [], '/admin', 'sem chat'],
]);

/** A consulta lê ou grava a tabela `settings`. */
function consultaAoSettingsDaFeature(string $sql): bool
{
    return preg_match('/\b(from|into|update|join)\s+[`"\[]?settings[`"\]]?(\s|$)/i', $sql) === 1;
}

/**
 * As consultas de um GET do `/admin` com o uso ligado ou desligado, em request novo para o container.
 *
 * @return list<array{sql: string, arquivos: list<string>}>
 */
function consultasDoAdminComUso(bool $ligado): array
{
    ligarAgentesDeIa($ligado, ['admin']);
    fronteiraDeRequest();
    app()->forgetInstance(ConfiguracoesDoKit::class);

    test()->actingAs(User::query()->findOrFail(auth()->id()));

    return consultasDoGet('/admin');
}

it('[CT-06] ligar ou desligar o uso não muda as consultas à tabela de settings, e a feature não a consulta', function (): void {
    $bruno = brunoDoCatalogo();
    $this->actingAs($bruno);

    $contar = static fn (array $consultas): int => count(array_filter($consultas, static fn (array $c): bool => consultaAoSettingsDaFeature($c['sql'])));

    $arquivosDaFeature = [
        'app/Support/AgentesDeIa.php',
        'app/Filament/Admin/Resources/AgentesIa/',
        'app/Filament/Concerns/ExigeAgentesDeIaLigados.php',
        'app/Filament/Admin/Widgets/AgentesIa',
        'app/Livewire/AssistenteChatWidget.php',
        'app/Providers/Concerns/ConfiguraFilamentGlobal.php',
    ];

    $daFeature = static fn (array $consulta): bool => consultaAoSettingsDaFeature($consulta['sql'])
        && count(array_filter(
            $consulta['arquivos'],
            static function (string $arquivo) use ($arquivosDaFeature): bool {
                $normalizado = str_replace('\\', '/', $arquivo);

                foreach ($arquivosDaFeature as $trecho) {
                    if (str_contains($normalizado, $trecho)) {
                        return true;
                    }
                }

                return false;
            },
        )) > 0;

    // Aquecimento.
    consultasDoAdminComUso(true);

    // Controle do contador: resolver o settings do banco é visto como consulta à tabela.
    $controle = [];
    DB::listen(function ($consulta) use (&$controle): void {
        $controle[] = $consulta->sql;
    });
    app()->forgetInstance(ConfiguracoesDoKit::class);
    app(ConfiguracoesDoKit::class)->agentes_ia_habilitado;
    expect(count(array_filter($controle, consultaAoSettingsDaFeature(...))))->toBeGreaterThanOrEqual(1);

    $quatro = [
        consultasDoAdminComUso(false),
        consultasDoAdminComUso(true),
        consultasDoAdminComUso(true),
        consultasDoAdminComUso(false),
    ];

    expect($contar($quatro[1]) + $contar($quatro[2]))->toBe($contar($quatro[0]) + $contar($quatro[3]));

    foreach ($quatro as $indice => $consultas) {
        expect($consultas)->not->toBeEmpty();
        expect(array_column(array_filter($consultas, $daFeature), 'sql'))->toBe([], "o request {$indice} consultou o settings por código da feature");
    }
});

/* ---------------------------------------------------------------------------
| R4 — a tela de configurações grava o interruptor e a lista
--------------------------------------------------------------------------- */

it('[CT-07] o administrador grava o uso e os painéis pela tela', function (bool $usoAntes, array $listaAntes, array $preenche, bool $usoGravado, array $listaGravada): void {
    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    conversaPara($ana, 'Plano de férias');

    gravarConfiguracao('agentes_ia_habilitado', $usoAntes);
    gravarConfiguracao('agentes_ia_paineis', $listaAntes);
    app()->forgetInstance(ConfiguracoesDoKit::class);

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $tela = Livewire::test(TelaDeConfiguracoes::class);
    carregarAba($tela, 'aplicacao');
    chaveDoCampo($tela, 'agentes_ia_habilitado');
    chaveDoCampo($tela, 'agentes_ia_paineis');

    $tela->fillForm($preenche)->call('save')->assertHasNoFormErrors();

    expect(configuracaoGravada('agentes_ia_habilitado'))->toBe($usoGravado)
        ->and(configuracaoGravada('agentes_ia_paineis'))->toBe($listaGravada)
        ->and(AgenteIa::query()->where('ativo', true)->count())->toBe(2)
        ->and(Conversation::query()->where('title', 'Plano de férias')->where('participant_id', $ana->getKey())->exists())->toBeTrue();
})->with([
    'ligar com dois painéis' => [false, [], ['agentes_ia_habilitado' => true, 'agentes_ia_paineis' => ['admin', 'infra']], true, ['admin', 'infra']],
    'lista vazia = nenhum'   => [false, [], ['agentes_ia_habilitado' => true, 'agentes_ia_paineis' => []], true, []],
    'desligar não apaga'     => [true, ['app'], ['agentes_ia_habilitado' => false], false, ['app']],
]);

it('[CT-08] valor fora do domínio é recusado no campo e nada é gravado', function (string $campo, mixed $valor, string $erroEm): void {
    gravarConfiguracao('agentes_ia_habilitado', false);
    gravarConfiguracao('agentes_ia_paineis', ['app']);
    app()->forgetInstance(ConfiguracoesDoKit::class);

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $tela = Livewire::test(TelaDeConfiguracoes::class);
    carregarAba($tela, 'aplicacao');
    chaveDoCampo($tela, 'agentes_ia_habilitado');
    chaveDoCampo($tela, 'agentes_ia_paineis');

    if ($campo === 'agentes_ia_paineis') {
        $tela->set('data.agentes_ia_habilitado', true);
    }

    $tela->set('data.'.$campo, $valor)->call('save')->assertHasFormErrors([$erroEm]);

    expect(configuracaoGravada('agentes_ia_habilitado'))->toBeFalse()
        ->and(configuracaoGravada('agentes_ia_paineis'))->toBe(['app']);
})->with([
    'lista como texto'                     => ['agentes_ia_paineis', 'app', 'agentes_ia_paineis'],
    'painel fora das opções (prefixo)'     => ['agentes_ia_paineis', ['apps'], 'agentes_ia_paineis.0'],
    'um válido ao lado de um fora (caixa)' => ['agentes_ia_paineis', ['admin', 'App'], 'agentes_ia_paineis.1'],
    'toggle como lista'                    => ['agentes_ia_habilitado', ['1'], 'agentes_ia_habilitado'],
    'toggle como texto não booleano'       => ['agentes_ia_habilitado', 'talvez', 'agentes_ia_habilitado'],
]);

it('[CT-25] salvar outro campo da tela não apaga o uso nem a lista gravados', function (): void {
    gravarConfiguracao('agentes_ia_habilitado', true);
    gravarConfiguracao('agentes_ia_paineis', ['admin']);
    app()->forgetInstance(ConfiguracoesDoKit::class);

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    Livewire::test(TelaDeConfiguracoes::class)
        ->fillForm(['nome_da_aplicacao' => 'Outro nome'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Outro nome')
        ->and(configuracaoGravada('agentes_ia_habilitado'))->toBeTrue()
        ->and(configuracaoGravada('agentes_ia_paineis'))->toBe(['admin']);
});

/* ---------------------------------------------------------------------------
| R5 — com o uso desligado, o catálogo some de toda superfície de navegação
--------------------------------------------------------------------------- */

/** Bruno com o dock e o hub ligados, o `/admin` bootado e o uso no estado pedido. */
function brunoDasSuperficies(bool $uso, array $lista): User
{
    config(['kit.hub' => true]);
    $bruno = brunoDoCatalogo();
    concederDock('admin', 'panel_user', 'infra');
    ligarDock(['admin']);
    ligarAgentesDeIa($uso, $lista);

    fronteiraDeRequest();
    noPainelBootado('admin');
    test()->actingAs($bruno);

    return $bruno;
}

/** As URLs de todos os itens da barra lateral do painel corrente. */
function urlsDaBarraLateral(): array
{
    $urls = [];

    foreach (Filament::getNavigation() as $grupo) {
        foreach ($grupo->getItems() as $item) {
            $urls[] = $item->getUrl();
        }
    }

    return $urls;
}

/** As URLs de todos os cartões do hub de administração. */
function urlsDoHub(): array
{
    $metodo = new ReflectionMethod(HubDeAdministracao::class, 'getCards');
    $metodo->setAccessible(true);

    $urls = [];

    /** @var list<CardGroup> $grupos */
    $grupos = $metodo->invoke(null);

    foreach ($grupos as $grupo) {
        /** @var CardItem $item */
        foreach ($grupo->getItems() as $item) {
            $urls[] = $item->getUrl();
        }
    }

    return $urls;
}

it('[CT-09] cada superfície de navegação lista o catálogo só com o uso ligado', function (string $superficie, bool $uso, array $lista, bool $presente): void {
    brunoDasSuperficies($uso, $lista);

    $url   = urlDoCatalogo();
    $chave = chaveDeAtalho('admin', AgenteIaResource::class);

    $achou = match ($superficie) {
        'barra lateral' => in_array($url, urlsDaBarraLateral(), true),
        'hub'           => in_array($url, urlsDoHub(), true),
        'telas'         => collect((new ResourcesAutorizadasCategory)->search('Agentes', 10))
            ->contains(fn ($resultado): bool => ($resultado->payload['resource'] ?? null) === AgenteIaResource::class || $resultado->url === $url),
        'registros' => collect((new RecordsCategory)->search('Assistente', 10))
            ->contains(fn ($resultado): bool => ($resultado->payload['resource_class'] ?? null) === AgenteIaResource::class),
        'ação criar' => (function (): bool {
            AcoesDeCriacao::registrar();

            return array_key_exists('criar-agentes-ia', app(SpotlightActionRegistry::class)->all());
        })(),
        'dock' => in_array($chave, array_keys(opcoesDoSlideover(dockNaPagina('admin'))), true),
    };

    expect($achou)->toBe($presente, "{$superficie}: catálogo ".($presente ? 'deveria estar' : 'não deveria estar').' na superfície');
})->with([
    'barra, desligado'      => ['barra lateral', false, ['admin'], false],
    'barra, ligado []'      => ['barra lateral', true, [], true],
    'barra, ligado admin'   => ['barra lateral', true, ['admin'], true],
    'hub, desligado'        => ['hub', false, ['admin'], false],
    'hub, ligado []'        => ['hub', true, [], true],
    'telas, desligado'      => ['telas', false, ['admin'], false],
    'telas, ligado []'      => ['telas', true, [], true],
    'registros, desligado'  => ['registros', false, ['admin'], false],
    'registros, ligado []'  => ['registros', true, [], true],
    'ação, desligado'       => ['ação criar', false, ['admin'], false],
    'ação, ligado []'       => ['ação criar', true, [], true],
    'dock, desligado'       => ['dock', false, ['admin'], false],
    'dock, ligado []'       => ['dock', true, [], true],
]);

/** Os escolhidos hoje no slideover do dock (o estado do campo `atalhos`). */
function escolhidosDoSlideover(Testable $dock): array
{
    $escolhidos = [];

    $dock->mountAction('gerenciarAtalhos')->assertSchemaComponentExists(
        'atalhos',
        'mountedActionSchema0',
        function (Select $campo) use (&$escolhidos): bool {
            $escolhidos = (array) $campo->getState();

            return true;
        },
    );

    return $escolhidos;
}

it('[CT-10] o favorito do dock para o catálogo fica oculto com o uso desligado, sobrevive ao salvar o slideover e volta ao religar', function (string $historico, string $acao): void {
    $bruno = brunoDasSuperficies(false, ['admin']);
    $chave = chaveDeAtalho('admin', AgenteIaResource::class);
    $url   = urlDoCatalogo();
    $users = chaveDeAtalho('admin', UserResourceDoAdmin::class);

    gravarAtalhos($bruno, [$chave]);

    $salvarSlideover = function () use ($users): void {
        expect(array_keys(opcoesDoSlideover(dockNaPagina('admin'))))->toContain($users);

        dockNaPagina('admin')->callAction('gerenciarAtalhos', ['atalhos' => [$users]])->assertHasNoErrors();
    };

    switch ($acao) {
        case 'desenha':
            dockNaPagina('admin')->assertDontSee($url, false);
            break;

        case 'abre':
            expect(array_keys(opcoesDoSlideover(dockNaPagina('admin'))))->not->toContain($chave)
                ->and(escolhidosDoSlideover(dockNaPagina('admin')))->not->toContain($chave);
            break;

        case 'salva':
            $salvarSlideover();
            expect(atalhosGravados($bruno))->toContain($users);
            break;

        case 'religa':
            $salvarSlideover();
            ligarAgentesDeIa(true, ['admin']);
            fronteiraDeRequest();
            noPainelBootado('admin');
            $this->actingAs($bruno);
            dockNaPagina('admin')->assertSee($url, false);
            break;
    }

    expect(atalhosGravados($bruno))->toContain($chave);
})->with([
    'desenha o dock desligado'           => ['o uso desligado', 'desenha'],
    'abre o slideover desligado'         => ['o uso desligado', 'abre'],
    'salva o slideover desligado'        => ['o uso desligado', 'salva'],
    'salva desligado e religa'           => ['o slideover salvo desligado, e religado', 'religa'],
]);

/* ---------------------------------------------------------------------------
| R6 — com o uso desligado, as três URLs do catálogo respondem 404
--------------------------------------------------------------------------- */

it('[CT-11] lista, criar e editar respondem 404 com o uso desligado', function (string $rota, string $pagina, string $permissao, bool $comPermissao): void {
    ligarAgentesDeIa(false, ['admin', 'app', 'infra']);

    $admin = usuarioDoKit('admin', 'admin@example.com');

    if (! $comPermissao) {
        semAPermissao('admin', $permissao);
        noPainelDoShield('admin');
    }

    $url = str_replace('{assistente}', idDoAssistente(), $rota);

    $this->actingAs($admin)->get($url)
        ->assertNotFound()
        ->assertDontSeeLivewire($pagina)
        ->assertDontSee('Assistente');
})->with([
    'lista com'   => ['/admin/agentes-ia', ListAgentesIa::class, 'ViewAny:AgenteIa', true],
    'lista sem'   => ['/admin/agentes-ia', ListAgentesIa::class, 'ViewAny:AgenteIa', false],
    'criar com'   => ['/admin/agentes-ia/create', CreateAgenteIa::class, 'Create:AgenteIa', true],
    'criar sem'   => ['/admin/agentes-ia/create', CreateAgenteIa::class, 'Create:AgenteIa', false],
    'editar com'  => ['/admin/agentes-ia/{assistente}/edit', EditAgenteIa::class, 'Update:AgenteIa', true],
    'editar sem'  => ['/admin/agentes-ia/{assistente}/edit', EditAgenteIa::class, 'Update:AgenteIa', false],
]);

it('[CT-12] depois do 404, o painel de administração continua abrindo, e só o link do catálogo some', function (bool $ligado): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $url   = urlDoCatalogo();

    if (! $ligado) {
        ligarAgentesDeIa(false, ['admin']);
        $this->actingAs($admin)->get('/admin/agentes-ia')->assertNotFound();
    } else {
        ligarAgentesDeIa(true, []);
    }

    $resposta = abrirNoPainel($admin, '/admin')->assertOk();

    if ($ligado) {
        $this->assertStringContainsString($url, $resposta->getContent());
    } else {
        $this->assertStringNotContainsString($url, $resposta->getContent());
    }
})->with([
    'desligado depois do 404'    => [false],
    'ligado com [] (controle)'   => [true],
]);

/* ---------------------------------------------------------------------------
| R6b — a tela do catálogo já aberta recusa a ação depois de desligar
--------------------------------------------------------------------------- */

it('[CT-22] a tela do catálogo aberta com o uso ligado recusa a ação depois que o uso é desligado', function (string $tela): void {
    ligarAgentesDeIa(true, ['admin']);
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));
    Filament::setCurrentPanel('admin');

    match ($tela) {
        'criar' => (function (): void {
            $componente = Livewire::test(CreateAgenteIa::class)->fillForm([
                'slug'       => 'relator',
                'nome'       => 'Relator',
                'ativo'      => true,
                'instrucoes' => 'Instruções de teste.',
                'versao'     => 1,
            ]);

            ligarAgentesDeIa(false);

            $componente->call('create')->assertNotFound();

            expect(AgenteIa::query()->where('slug', 'relator')->exists())->toBeFalse();
        })(),
        'editar' => (function (): void {
            $componente = Livewire::test(EditAgenteIa::class, ['record' => idDoAssistente()])
                ->fillForm(['nome' => 'Assistente geral']);

            ligarAgentesDeIa(false);

            $componente->call('save')->assertNotFound();

            expect(AgenteIa::query()->where('slug', 'assistente')->value('nome'))->toBe('Assistente');
        })(),
        'lista' => (function (): void {
            $componente = Livewire::test(ListAgentesIa::class)->loadTable();

            ligarAgentesDeIa(false);

            $componente->sortTable('nome')->assertNotFound()->assertDontSee('Assistente');
        })(),
    };

    expect(AgenteIa::query()->count())->toBe(2);
})->with(['criar', 'editar', 'lista']);

/* ---------------------------------------------------------------------------
| R7 — com o uso ligado, o catálogo abre e grava como hoje
--------------------------------------------------------------------------- */

it('[CT-13] com o uso ligado, a permissão da tela decide como hoje', function (array $lista, string $rota, string $pagina, string $permissao, bool $comPermissao): void {
    ligarAgentesDeIa(true, $lista);

    $admin = usuarioDoKit('admin', 'admin@example.com');

    if (! $comPermissao) {
        semAPermissao('admin', $permissao);
        noPainelDoShield('admin');
    }

    $resposta = $this->actingAs($admin)->get(str_replace('{assistente}', idDoAssistente(), $rota));

    if ($comPermissao) {
        $resposta->assertOk()->assertSeeLivewire($pagina);
    } else {
        $resposta->assertForbidden()->assertDontSeeLivewire($pagina);
    }
})->with([
    '[] lista com'          => [[], '/admin/agentes-ia', ListAgentesIa::class, 'ViewAny:AgenteIa', true],
    '[] criar com'          => [[], '/admin/agentes-ia/create', CreateAgenteIa::class, 'Create:AgenteIa', true],
    '[] editar com'         => [[], '/admin/agentes-ia/{assistente}/edit', EditAgenteIa::class, 'Update:AgenteIa', true],
    '[admin] lista com'     => [['admin'], '/admin/agentes-ia', ListAgentesIa::class, 'ViewAny:AgenteIa', true],
    '[admin] criar com'     => [['admin'], '/admin/agentes-ia/create', CreateAgenteIa::class, 'Create:AgenteIa', true],
    '[admin] editar com'    => [['admin'], '/admin/agentes-ia/{assistente}/edit', EditAgenteIa::class, 'Update:AgenteIa', true],
    '[] lista sem'          => [[], '/admin/agentes-ia', ListAgentesIa::class, 'ViewAny:AgenteIa', false],
    '[] criar sem'          => [[], '/admin/agentes-ia/create', CreateAgenteIa::class, 'Create:AgenteIa', false],
    '[] editar sem'         => [[], '/admin/agentes-ia/{assistente}/edit', EditAgenteIa::class, 'Update:AgenteIa', false],
]);

it('[CT-14] com o uso ligado, o formulário do catálogo grava', function (array $lista, string $acao): void {
    ligarAgentesDeIa(true, $lista);
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));
    Filament::setCurrentPanel('admin');

    if ($acao === 'criar') {
        Livewire::test(CreateAgenteIa::class)
            ->fillForm([
                'slug'       => 'relator',
                'nome'       => 'Relator',
                'ativo'      => true,
                'instrucoes' => 'Instruções de teste.',
                'versao'     => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $agente = AgenteIa::query()->where('slug', 'relator')->first();

        expect($agente)->not->toBeNull()
            ->and($agente->nome)->toBe('Relator')
            ->and($agente->ativo)->toBeTrue();

        return;
    }

    Livewire::test(EditAgenteIa::class, ['record' => idDoAssistente()])
        ->fillForm(['nome' => 'Assistente geral'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(AgenteIa::query()->where('slug', 'assistente')->value('nome'))->toBe('Assistente geral');
})->with([
    'cria com []'      => [[], 'criar'],
    'edita com admin'  => [['admin'], 'editar'],
]);

it('[CT-15] o catálogo continua só no /admin, mesmo com o chat nos três painéis', function (string $url, int $status): void {
    ligarAgentesDeIa(true, ['admin', 'app', 'infra']);

    $resposta = $this->actingAs(brunoDoCatalogo())->get($url);

    if ($status === 404) {
        $resposta->assertNotFound();
    } else {
        $resposta->assertOk()->assertSeeLivewire(ListAgentesIa::class);
    }
})->with([
    '/app'              => ['/app/agentes-ia', 404],
    '/infra'            => ['/infra/agentes-ia', 404],
    '/admin (controle)' => ['/admin/agentes-ia', 200],
]);
