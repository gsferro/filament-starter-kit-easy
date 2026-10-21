<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as TelaDeConfiguracoes;
use App\Filament\Admin\Pages\Dashboard as DashboardDoAdmin;
use App\Filament\Admin\Pages\HubDeAdministracao;
use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use App\Filament\Admin\Resources\Convites\ConviteResource as ConviteDoAdmin;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Users\UserResource as UserDoAdmin;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\App\Pages\Dashboard as DashboardDoApp;
use App\Filament\App\Pages\HubDoNegocio;
use App\Filament\App\Resources\Convites\ConviteResource as ConviteDoApp;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\App\Resources\Users\UserResource as UserDoApp;
use App\Filament\Infra\Pages\BackupRunsPage;
use App\Filament\Infra\Pages\Dashboard as DashboardDoInfra;
use App\Filament\Infra\Pages\HubDeInfraestrutura;
use App\Filament\Infra\Pages\Pulse;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use App\Filament\Pages\MyProfilePage;
use App\Models\User;
use BezhanSalleh\FilamentExceptions\Resources\ExceptionResource;
use Bityukov\CommandCenter\Filament\Pages\Commands;
use Croustibat\FilamentJobsMonitor\Resources\QueueMonitorResource;
use Database\Seeders\Onboarding\BuscaGlobalSeeder;
use Database\Seeders\Onboarding\HubDeNavegacaoSeeder;
use Database\Seeders\Onboarding\PulseSeeder;
use Database\Seeders\Onboarding\UsuariosSeeder;
use Database\Seeders\OnboardingSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use LaBoiteACode\FilamentLogsExplorer\Pages\LogsExplorer;
use Livewire\Livewire;
use Promethys\Revive\Pages\RecycleBin;
use Tapp\FilamentAuditing\Filament\Resources\Audits\AuditResource;
use Wallacemartinss\FilamentOnboarding\Enums\CompletionMode;
use Wallacemartinss\FilamentOnboarding\Facades\Onboarding;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingCondition;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingStep;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingConditions\Pages\EditOnboardingCondition;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingFlows\Pages\EditOnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingFlows\RelationManagers\StepsRelationManager;

/**
 * Tutorial guiado (wiki `onboarding-guiado`) — lote da semeadura: G7 (o seeder pai, os filhos, as
 * condições, re-semear e os idiomas), G8 (o estado do checklist por condição e visibilidade) e G9
 * (a autoria grava os dois idiomas).
 *
 * O `Então` de cada caso vem do `04-casos-de-teste.md`; o estado do tutorial vem de
 * `ligarTutorialGuiado()` e as features dos passos de `ligarFeaturesDoOnboarding()`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Semeia as jornadas do kit pelo seeder pai. */
function semearOnboardingDoKit(): void
{
    test()->seed(OnboardingSeeder::class);
}

/** A jornada semeada do painel. */
function jornadaSemeadaDoPainel(string $painel): OnboardingFlow
{
    return OnboardingFlow::query()->where('panel_id', $painel)->firstOrFail();
}

/** Os passos da jornada do painel, na ordem. */
function passosDoPainelNoOnboarding(string $painel)
{
    return OnboardingStep::query()->where('flow_id', jornadaSemeadaDoPainel($painel)->getKey())->orderBy('sort_order')->get();
}

/** O passo da jornada do painel, pela chave. */
function passoDoPainelNoOnboarding(string $painel, string $chave): OnboardingStep
{
    return OnboardingStep::query()->where('flow_id', jornadaSemeadaDoPainel($painel)->getKey())->where('key', $chave)->firstOrFail();
}

/** As chaves dos passos visíveis do checklist da pessoa no painel. */
function visiveisNoChecklistDoOnboarding(string $painel, User $pessoa): array
{
    return checklistDoOnboarding($painel, $pessoa)->instance()->flowState()->steps->map(fn ($passo): string => $passo->key())->all();
}

/* ---------------------------------------------------------------------------
| R10 / R12 — as jornadas e os passos semeados
--------------------------------------------------------------------------- */

it('[CT-17] as paradas de tour de todos os passos semeados resolvem dentro do painel da jornada', function (): void {
    semearOnboardingDoKit();

    $passos = 0;

    foreach (OnboardingFlow::query()->get() as $jornada) {
        $caminho = trim((string) Filament::getPanel($jornada->panel_id)->getPath(), '/');

        foreach (OnboardingStep::query()->where('flow_id', $jornada->getKey())->get() as $passo) {
            $passos++;
            $paradas = (array) $passo->tour_steps;

            expect($paradas)->not->toBeEmpty("o passo {$passo->key} de /{$jornada->panel_id} não tem parada de tour");

            foreach ($paradas as $parada) {
                if (filled($parada['route'] ?? null)) {
                    expect(Route::has($parada['route']))->toBeTrue("rota {$parada['route']} não registrada")
                        ->and(str_starts_with($parada['route'], "filament.{$jornada->panel_id}."))->toBeTrue("a rota {$parada['route']} não é do painel {$jornada->panel_id}");
                }

                if (filled($parada['url'] ?? null)) {
                    expect(str_starts_with(ltrim((string) parse_url($parada['url'], PHP_URL_PATH), '/'), $caminho))->toBeTrue("a URL {$parada['url']} não é do painel {$jornada->panel_id}");
                }
            }
        }
    }

    expect($passos)->toBeGreaterThan(0);
});

/** O prefixo de nome de rota de uma tela (Resource ou Page) no painel. */
function prefixoDeRotaDaTela(string $classe, string $painel): string
{
    $painelFilament = Filament::getPanel($painel);

    return is_subclass_of($classe, Resource::class)
        ? $classe::getRouteBaseName($painelFilament)
        : $classe::getRouteName($painelFilament);
}

it('[CT-20] sem multi-organização, cada painel recebe a sua jornada com os passos da tabela', function (string $painel, int $total, int $condicao, int $visita, int $manual, array $ancoras): void {
    expect(OnboardingFlow::query()->count())->toBe(0)->and(OnboardingStep::query()->count())->toBe(0);

    semearOnboardingDoKit();

    expect(OnboardingFlow::query()->count())->toBe(3)
        ->and(OnboardingFlow::query()->where('panel_id', $painel)->count())->toBe(1);

    $passos = passosDoPainelNoOnboarding($painel);
    $modo   = fn (CompletionMode $modo): int => $passos->filter(fn ($p): bool => $p->completion_mode === $modo)->count();

    expect($passos)->toHaveCount($total)
        ->and($modo(CompletionMode::Condition))->toBe($condicao)
        ->and($modo(CompletionMode::Visit))->toBe($visita)
        ->and($modo(CompletionMode::Manual))->toBe($manual);

    $rotas = $passos->flatMap(fn ($p): array => array_column((array) $p->tour_steps, 'route'))->filter()->unique()->values()->all();

    foreach ($ancoras as $ancora) {
        $prefixo = prefixoDeRotaDaTela($ancora, $painel);

        expect(collect($rotas)->contains(fn (string $rota): bool => str_starts_with($rota, $prefixo)))->toBeTrue("nenhuma parada alcança {$ancora}");
    }

    $soComOrganizacao = match ($painel) {
        'admin' => [TenantResource::class],
        'app'   => [UserDoApp::class, ConviteDoApp::class, ConvitesRecebidos::class, ProjetoResource::class],
        default => [],
    };

    foreach ($soComOrganizacao as $tela) {
        $prefixo = prefixoDeRotaDaTela($tela, $painel);

        expect(collect($rotas)->contains(fn (string $rota): bool => str_starts_with($rota, $prefixo)))->toBeFalse("um passo alcança {$tela}, que só existe com multi-organização");
    }
})->with([
    'admin' => ['admin', 11, 2, 1, 8, [UserDoAdmin::class, ConviteDoAdmin::class, RoleResource::class, TelaDeConfiguracoes::class, AgenteIaResource::class, HubDeAdministracao::class, MyProfilePage::class, DashboardDoAdmin::class]],
    'app'   => ['app', 6, 1, 0, 5, [HubDoNegocio::class, MyProfilePage::class, DashboardDoApp::class]],
    'infra' => ['infra', 15, 1, 0, 14, [HubDeInfraestrutura::class, Pulse::class, BackupRunsPage::class, LogsExplorer::class, ExceptionResource::class, QueueMonitorResource::class, AuditResource::class, AiRunResource::class, RecycleBin::class, Commands::class, MyProfilePage::class, DashboardDoInfra::class]],
]);

/** Os limites declarados nas migrations de onboarding (`string('x')` = 255; `string('x', 2048)` = 2048). */
function limitesDeColunaDoOnboarding(): array
{
    return [
        'onboarding_flows' => ['key' => 255, 'panel_id' => 255, 'icon' => 255, 'color' => 255, 'visibility_condition' => 255],
        'onboarding_steps' => [
            'key'           => 255, 'type' => 255, 'icon' => 255, 'cta_url' => 2048, 'cta_route' => 255, 'completion_mode' => 255,
            'condition_key' => 255, 'visit_url' => 2048, 'visibility_condition' => 255, 'media_type' => 255,
            'media_source'  => 255, 'media_disk' => 255, 'media_path' => 2048, 'media_url' => 2048, 'modal_position' => 255,
        ],
        'onboarding_conditions' => ['key' => 255, 'type' => 255, 'model' => 255, 'subject_column' => 255, 'scope_column' => 255],
    ];
}

it('[CT-59] todo valor semeado em coluna de texto com limite cabe no limite da coluna', function (): void {
    semearOnboardingDoKit();

    $lidos = 0;

    foreach (limitesDeColunaDoOnboarding() as $tabela => $colunas) {
        foreach (DB::table($tabela)->get() as $linha) {
            foreach ($colunas as $coluna => $limite) {
                $valor = $linha->{$coluna} ?? null;

                if ($valor === null) {
                    continue;
                }

                $lidos++;

                expect(mb_strlen((string) $valor))->toBeLessThanOrEqual($limite, "{$tabela}.{$coluna} passa de {$limite}")
                    // Um valor exatamente no limite é indistinguível de um valor cortado nele.
                    ->and(mb_strlen((string) $valor))->not->toBe($limite, "{$tabela}.{$coluna} no limite: pode ter sido truncado");
            }
        }
    }

    expect($lidos)->toBeGreaterThan(100);
});

/* ---------------------------------------------------------------------------
| R13 / R14 — filhos e interruptor
--------------------------------------------------------------------------- */

it('[CT-21] o seeder filho rodado sozinho cria só o passo da sua feature', function (string $filho, int $total, array $paineis): void {
    expect(OnboardingStep::query()->count())->toBe(0);

    $this->seed($filho);

    expect(OnboardingStep::query()->count())->toBe($total)
        ->and(OnboardingStep::query()->pluck('key')->unique())->toHaveCount(1);

    $dosPaineis = OnboardingStep::query()->get()->map(fn ($p): string => (string) $p->flow->panel_id)->sort()->values()->all();

    expect($dosPaineis)->toBe($paineis);
})->with([
    'busca global' => [BuscaGlobalSeeder::class, 3, ['admin', 'app', 'infra']],
    'usuários'     => [UsuariosSeeder::class, 1, ['admin']],
    'hub'          => [HubDeNavegacaoSeeder::class, 2, ['admin', 'app']],
    'pulse'        => [PulseSeeder::class, 1, ['infra']],
]);

it('[CT-22] o interruptor gravado continua o mesmo depois de semear', function (bool $antes): void {
    gravarConfiguracao('onboarding_habilitado', $antes);
    alinharConfiguracoesDoKit();

    semearOnboardingDoKit();

    expect(configuracaoGravada('onboarding_habilitado'))->toBe($antes)
        ->and(config('kit.onboarding.habilitado'))->toBe($antes);
})->with([false, true]);

/* ---------------------------------------------------------------------------
| R15 — as condições
--------------------------------------------------------------------------- */

it('[CT-23] toda chave de condição citada pelos passos semeados resolve no painel do passo', function (): void {
    semearOnboardingDoKit();

    // Os três painéis bootados: as condições em código são registradas no boot do plugin.
    noPainelBootado('admin');
    noPainelBootado('infra');
    $this->actingAs(pessoaDoPainelDoOnboarding('app'))->get('/app');

    $resolve = fn (string $chave): bool => Onboarding::conditions()->has($chave);
    $citadas = 0;

    foreach (OnboardingFlow::query()->get() as $jornada) {
        foreach (array_filter([$jornada->visibility_condition]) as $chave) {
            $citadas++;
            expect($resolve($chave))->toBeTrue("a jornada {$jornada->key} cita {$chave}, que não resolve");
        }

        foreach (OnboardingStep::query()->where('flow_id', $jornada->getKey())->get() as $passo) {
            foreach (array_filter([$passo->condition_key, $passo->visibility_condition]) as $chave) {
                $citadas++;
                expect($resolve($chave))->toBeTrue("o passo {$passo->key} de /{$jornada->panel_id} cita {$chave}, que não resolve");
            }
        }
    }

    expect($citadas)->toBeGreaterThan(10);
});

it('[CT-24] o passo "Perfil e 2FA" conclui só com o segundo fator confirmado, em cada painel', function (string $painel, string $doisFatores, bool $concluido): void {
    semearOnboardingDoKit();
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $pessoa = pessoaDoPainelDoOnboarding($painel);

    match ($doisFatores) {
        'configurado' => ativarDoisFatoresDaPessoa($pessoa, confirmar: false),
        'confirmado'  => ativarDoisFatoresDaPessoa($pessoa),
        default       => null,
    };

    $passo = checklistDoOnboarding($painel, $pessoa)->instance()->flowState()->step('perfil-e-dois-fatores');

    expect($passo)->not->toBeNull()->and($passo->isCompleted())->toBe($concluido);
})->with([
    'admin, nenhum'                    => ['admin', 'nenhum', false],
    'admin, configurado sem confirmar' => ['admin', 'configurado', false],
    'admin, confirmado'                => ['admin', 'confirmado', true],
    'app, confirmado'                  => ['app', 'confirmado', true],
    'infra, confirmado'                => ['infra', 'confirmado', true],
]);

it('[CT-25] o passo "Convites" do /admin conclui quando a própria pessoa já convidou alguém', function (string $convites, bool $concluido): void {
    semearOnboardingDoKit();
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $admin = pessoaDoPainelDoOnboarding('admin');
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    // O estado do convite entra por atributo (aceito_em, recusado_em, expira_em): aceitar e recusar
    // exigem a pessoa convidada e o token, e o passo só pergunta quem convidou (ADV-15).
    $estado = match ($convites) {
        'aceito'   => ['aceito_em' => now()],
        'recusado' => ['recusado_em' => now()],
        'expirado' => ['expira_em' => now()->subDay()],
        default    => [],
    };

    match ($convites) {
        'nenhum'   => null,
        'do bruno' => ofertaPara('a@example.com', null, 'panel_user', ['convidado_por_id' => $bruno->getKey()]),
        default    => ofertaPara('a@example.com', null, 'panel_user', ['convidado_por_id' => $admin->getKey(), ...$estado]),
    };

    $passo = checklistDoOnboarding('admin', $admin)->instance()->flowState()->step('convites');

    expect($passo)->not->toBeNull()->and($passo->isCompleted())->toBe($concluido);
})->with([
    'nenhum'         => ['nenhum', false],
    'um dela'        => ['pendente', true],
    'um do Bruno'    => ['do bruno', false],
    'dela, aceito'   => ['aceito', true],
    'dela, recusado' => ['recusado', true],
    'dela, expirado' => ['expirado', true],
]);

/* ---------------------------------------------------------------------------
| R16 — features desligadas
--------------------------------------------------------------------------- */

/** Aplica o "desligada" da linha do CT-27 sobre as features todas ligadas ("só no X" = desligada só no X). */
function desligarFeatureDoOnboarding(string $desligada): void
{
    match ($desligada) {
        'nenhuma'                => null,
        'dock fora do admin'     => ligarDock(['app', 'infra']),
        'dock fora do infra'     => ligarDock(['admin', 'app']),
        'dock fora do app'       => ligarDock(['admin', 'infra']),
        'bloqueio de tela'       => config(['lockscreen.enabled' => false]),
        'dinamico fora do admin' => ligarDashboardDinamico(true, ['app', 'infra']),
        'dinamico fora do infra' => ligarDashboardDinamico(true, ['admin', 'app']),
        'dinamico fora do app'   => ligarDashboardDinamico(true, ['admin', 'infra']),
        'hub'                    => config(['kit.hub' => false]),
        'agentes de ia'          => ligarAgentesDeIa(false, []),
        'central de comandos'    => config(['command-center.enabled' => false]),
    };
}

it('[CT-27] desligar a feature esconde exatamente o passo dela no painel', function (string $desligada, string $painel, ?int $visiveis, string $passoDaFeature, bool $presente): void {
    semearOnboardingDoKit();
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $pessoa = pessoaDoPainelDoOnboarding($painel);

    // Contagem relativa ao controle do próprio painel quando o 04 não fixa o número (`@premissa P-11`).
    $controle = count(visiveisNoChecklistDoOnboarding($painel, $pessoa));

    desligarFeatureDoOnboarding($desligada);

    $visiveisAgora = visiveisNoChecklistDoOnboarding($painel, $pessoa);

    if ($visiveis !== null) {
        expect(in_array($passoDaFeature, $visiveisAgora, true))->toBe($presente, "passo {$passoDaFeature} em /{$painel}")
            ->and(count($visiveisAgora))->toBe($visiveis);

        return;
    }

    // `/app`: N₀ no controle (o hub aparece para quem abre a tela, invariante da P-11) e N₀ − 1 sem a feature.
    if ($desligada === 'nenhuma') {
        expect(in_array($passoDaFeature, $visiveisAgora, true))->toBe(HubDoNegocio::canAccess());

        return;
    }

    expect(in_array($passoDaFeature, $visiveisAgora, true))->toBe($presente, "passo {$passoDaFeature} em /{$painel}")
        ->and(count($visiveisAgora))->toBe($controle - 1, 'o passo escondido sai da contagem, e só ele');
})->with([
    'admin, nenhuma'                => ['nenhuma', 'admin', 11, 'dock-de-atalhos', true],
    'admin, dock fora do admin'     => ['dock fora do admin', 'admin', 10, 'dock-de-atalhos', false],
    'admin, dock fora do infra'     => ['dock fora do infra', 'admin', 11, 'dock-de-atalhos', true],
    'admin, bloqueio de tela'       => ['bloqueio de tela', 'admin', 10, 'bloqueio-de-tela', false],
    'admin, dinâmico fora do admin' => ['dinamico fora do admin', 'admin', 10, 'dashboard-dinamico', false],
    'admin, hub'                    => ['hub', 'admin', 10, 'hub-de-navegacao', false],
    'admin, agentes de IA'          => ['agentes de ia', 'admin', 10, 'agentes-de-ia', false],
    'infra, nenhuma'                => ['nenhuma', 'infra', 15, 'central-de-comandos', true],
    'infra, central de comandos'    => ['central de comandos', 'infra', 14, 'central-de-comandos', false],
    'infra, dock fora do infra'     => ['dock fora do infra', 'infra', 14, 'dock-de-atalhos', false],
    'infra, dinâmico fora do infra' => ['dinamico fora do infra', 'infra', 14, 'dashboard-dinamico', false],
    'admin, dinâmico fora do infra' => ['dinamico fora do infra', 'admin', 11, 'dashboard-dinamico', true],
    'infra, agentes de IA'          => ['agentes de ia', 'infra', 15, 'execucoes-de-ia', true],
    'app, nenhuma'                  => ['nenhuma', 'app', null, 'hub-de-navegacao', true],
    'app, hub'                      => ['hub', 'app', null, 'hub-de-navegacao', false],
    'app, dock fora do app'         => ['dock fora do app', 'app', null, 'dock-de-atalhos', false],
    'app, dinâmico fora do app'     => ['dinamico fora do app', 'app', null, 'dashboard-dinamico', false],
    'admin, dinâmico fora do app'   => ['dinamico fora do app', 'admin', 11, 'dashboard-dinamico', true],
]);

it('[CT-28] o passo escondido sai do percentual e volta concluído ao religar a feature', function (): void {
    semearOnboardingDoKit();
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $admin = pessoaDoPainelDoOnboarding('admin');

    concluirPassoDoOnboarding($admin, 'admin', 'dock-de-atalhos');
    concluirPassoDoOnboarding($admin, 'admin', 'usuarios');

    ligarDock(['app', 'infra']);
    $fora = checklistDoOnboarding('admin', $admin)->instance()->flowState();

    expect($fora->completedCount())->toBe(1)->and($fora->total())->toBe(10);

    ligarDock(['admin', 'app', 'infra']);
    $deVolta = checklistDoOnboarding('admin', $admin)->instance()->flowState();

    expect($deVolta->completedCount())->toBe(2)
        ->and($deVolta->total())->toBe(11)
        ->and($deVolta->step('dock-de-atalhos')->isCompleted())->toBeTrue();
});

it('[CT-61] a jornada concluída continua concluída quando o passo escondido volta a aparecer', function (): void {
    semearOnboardingDoKit();
    ligarFeaturesDoOnboarding();
    ligarDock(['app', 'infra']);

    $admin = pessoaDoPainelDoOnboarding('admin');
    concluirPassosVisiveisDoAdminNoOnboarding($admin);

    expect(checklistDoOnboarding('admin', $admin)->instance()->flowState()->isCompleted())->toBeTrue();

    ligarDock(['admin', 'app', 'infra']);
    $estado = checklistDoOnboarding('admin', $admin)->instance()->flowState();

    expect($estado->progress?->completed_at)->not->toBeNull('a jornada deixou de estar concluída')
        ->and($estado->completedCount())->toBe(10)
        ->and($estado->total())->toBe(11)
        ->and($estado->step('dock-de-atalhos')->isPending())->toBeTrue();

    expect(textoDoDashboardDoMaster())->toMatch('~\b1\s*/\s*1\b~');
});

/* ---------------------------------------------------------------------------
| R26 — o acesso à tela decide o passo
--------------------------------------------------------------------------- */

it('[CT-53] tirar a permissão da tela de um passo tira o passo do checklist', function (string $painel, ?string $permissao, string $passoDaTela, ?int $visiveis): void {
    semearOnboardingDoKit();
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $pessoa   = pessoaDoPainelDoOnboarding($painel);
    $controle = visiveisNoChecklistDoOnboarding($painel, $pessoa);

    if ($permissao === null) {
        expect($controle)->toContain($passoDaTela);

        if ($visiveis !== null) {
            expect(count($controle))->toBe($visiveis);
        }

        return;
    }

    $papel = ['admin' => 'admin', 'app' => 'panel_user', 'infra' => 'infra'][$painel];

    semAPermissao($papel, $permissao);
    noPainelDoShield($painel);

    $depois = visiveisNoChecklistDoOnboarding($painel, $pessoa);

    // Contagem fixa nas linhas do admin; `N − 1` relativo ao controle nas outras (`@premissa P-11`).
    expect($depois)->not->toContain($passoDaTela)
        ->and(count($depois))->toBe($visiveis ?? count($controle) - 1);
})->with([
    'admin, como semeadas'       => ['admin', null, 'usuarios', 11],
    'admin, sem ver Usuários'    => ['admin', 'ViewAny:User', 'usuarios', 10],
    'admin, sem ver Papéis'      => ['admin', 'ViewAny:Role', 'papeis-e-permissoes', 10],
    'infra, como semeadas'       => ['infra', null, 'pulse', null],
    'infra, sem a tela do Pulse' => ['infra', 'View:Pulse', 'pulse', null],
    'app, como semeadas'         => ['app', null, 'hub-de-navegacao', null],
    'app, sem a tela do Hub'     => ['app', 'View:HubDoNegocio', 'hub-de-navegacao', null],
]);

it('[CT-65] o passo concluído cuja tela a pessoa deixa de abrir sai também do numerador', function (): void {
    semearOnboardingDoKit();
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $admin = pessoaDoPainelDoOnboarding('admin');

    concluirPassoDoOnboarding($admin, 'admin', 'usuarios');

    $antes = checklistDoOnboarding('admin', $admin)->instance()->flowState();
    expect($antes->completedCount())->toBe(1)->and($antes->total())->toBe(11);

    semAPermissao('admin', 'ViewAny:User');
    noPainelDoShield('admin');

    $depois = checklistDoOnboarding('admin', $admin)->instance()->flowState();

    expect($depois->completedCount())->toBe(0)
        ->and($depois->total())->toBe(10)
        ->and(progressoDoPassoDaPessoa($admin, 'usuarios'))->not->toBeNull();
});

/* ---------------------------------------------------------------------------
| R17 — re-semear
--------------------------------------------------------------------------- */

/** Jornadas, passos e condições (contagem e conteúdo), ordenados. */
function fotografiaDaSemeadura(): array
{
    return collect(['onboarding_flows', 'onboarding_steps', 'onboarding_conditions'])
        ->mapWithKeys(fn (string $tabela): array => [$tabela => DB::table($tabela)->orderBy('id')->get()->map(fn ($l): array => (array) $l)->all()])
        ->all();
}

it('[CT-29] o seeder pai rodado duas vezes deixa o banco igual ao da primeira vez', function (): void {
    semearOnboardingDoKit();
    $antes = fotografiaDaSemeadura();

    semearOnboardingDoKit();

    expect(fotografiaDaSemeadura())->toBe($antes)
        ->and(OnboardingFlow::query()->count())->toBe(3)
        ->and(OnboardingStep::query()->count())->toBe(32);

    $repetidas = OnboardingStep::query()->select('flow_id', 'key')->groupBy('flow_id', 'key')->havingRaw('count(*) > 1')->get()->count();

    expect($repetidas)->toBe(0);
});

it('[CT-30] o que o administrador editou sobrevive ao re-semear', function (string $edicao): void {
    semearOnboardingDoKit();

    $admin = jornadaSemeadaDoPainel('admin');

    switch ($edicao) {
        case 'título da jornada':
            $admin->update(['title' => [...$admin->title, 'pt_BR' => 'Comece por aqui']]);
            semearOnboardingDoKit();
            expect($admin->fresh()->title['pt_BR'])->toBe('Comece por aqui')->and(OnboardingFlow::query()->where('panel_id', 'admin')->count())->toBe(1);
            break;

        case 'título en do passo':
            $passo = passoDoPainelNoOnboarding('admin', 'usuarios');
            $passo->update(['title' => [...$passo->title, 'en' => 'Your team']]);
            semearOnboardingDoKit();
            expect($passo->fresh()->title['en'])->toBe('Your team');
            break;

        case 'paradas do Pulse':
            $passo = passoDoPainelNoOnboarding('infra', 'pulse');
            $passo->update(['tour_steps' => [($passo->tour_steps)[0]]]);
            semearOnboardingDoKit();
            expect($passo->fresh()->tour_steps)->toHaveCount(1);
            break;

        case 'passo desativado':
            $passo = passoDoPainelNoOnboarding('app', 'busca-global');
            $passo->update(['is_active' => false]);
            semearOnboardingDoKit();
            expect($passo->fresh()->is_active)->toBeFalse();
            break;

        case 'passo movido':
            $passo  = passoDoPainelNoOnboarding('admin', 'usuarios');
            $ultima = (int) passosDoPainelNoOnboarding('admin')->max('sort_order') + 10;
            $passo->update(['sort_order' => $ultima]);
            semearOnboardingDoKit();
            expect($passo->fresh()->sort_order)->toBe($ultima);
            break;

        case 'rótulo da condição':
            $condicao = OnboardingCondition::query()->firstOrFail();
            $condicao->update(['label' => [...$condicao->label, 'pt_BR' => 'Convidou']]);
            semearOnboardingDoKit();
            expect($condicao->fresh()->label['pt_BR'])->toBe('Convidou');
            break;

        case 'jornada desativada':
            $infra = jornadaSemeadaDoPainel('infra');
            $infra->update(['is_active' => false]);
            semearOnboardingDoKit();
            expect($infra->fresh()->is_active)->toBeFalse()->and(OnboardingFlow::query()->where('panel_id', 'infra')->count())->toBe(1);
            break;

        case 'condição desativada':
            $condicao = OnboardingCondition::query()->firstOrFail();
            $condicao->update(['is_active' => false]);
            semearOnboardingDoKit();
            expect($condicao->fresh()->is_active)->toBeFalse()->and(OnboardingCondition::query()->where('key', $condicao->key)->count())->toBe(1);
            break;

        case 'modo trocado':
            $passo = passoDoPainelNoOnboarding('admin', 'usuarios');
            $passo->update(['completion_mode' => 'visit', 'visit_url' => '/admin/users']);
            semearOnboardingDoKit();
            expect($passo->fresh()->completion_mode)->toBe(CompletionMode::Visit)
                ->and($passo->fresh()->visit_url)->toBe('/admin/users')
                ->and(OnboardingStep::query()->where('flow_id', $admin->getKey())->where('key', 'usuarios')->count())->toBe(1);
            break;

        case 'passo da autoria':
            OnboardingStep::query()->create([
                'flow_id' => $admin->getKey(), 'key' => 'relatorios', 'sort_order' => 999,
                'title'   => ['pt_BR' => 'Relatórios', 'en' => 'Reports'],
            ]);
            semearOnboardingDoKit();
            expect(passoDoPainelNoOnboarding('admin', 'relatorios')->title['pt_BR'])->toBe('Relatórios')
                ->and(passosDoPainelNoOnboarding('admin'))->toHaveCount(12);
            break;
    }
})->with([
    'título da jornada', 'título en do passo', 'paradas do Pulse', 'passo desativado', 'passo movido',
    'rótulo da condição', 'jornada desativada', 'condição desativada', 'modo trocado', 'passo da autoria',
]);

it('[CT-31] o que falta é recriado, e o resto fica como está', function (string $ausencia): void {
    semearOnboardingDoKit();

    $admin = jornadaSemeadaDoPainel('admin');
    $admin->update(['title' => [...$admin->title, 'pt_BR' => 'Comece por aqui']]);

    match ($ausencia) {
        'passo'   => passoDoPainelNoOnboarding('admin', 'usuarios')->delete(),
        'jornada' => (function (): void {
            $infra = jornadaSemeadaDoPainel('infra');
            OnboardingStep::query()->where('flow_id', $infra->getKey())->get()->each->delete();
            $infra->delete();
        })(),
        'condição' => OnboardingCondition::query()->firstOrFail()->delete(),
    };

    $condicoesAntes = OnboardingCondition::query()->count();

    semearOnboardingDoKit();

    match ($ausencia) {
        'passo'    => expect(passosDoPainelNoOnboarding('admin'))->toHaveCount(11)->and(passoDoPainelNoOnboarding('admin', 'usuarios'))->not->toBeNull(),
        'jornada'  => expect(passosDoPainelNoOnboarding('infra'))->toHaveCount(15),
        'condição' => expect(OnboardingCondition::query()->count())->toBe($condicoesAntes + 1)
            ->and(OnboardingCondition::query()->where('is_active', false)->count())->toBe(0),
    };

    expect(OnboardingFlow::query()->where('panel_id', 'admin')->count())->toBe(1)
        ->and(jornadaSemeadaDoPainel('admin')->title['pt_BR'])->toBe('Comece por aqui');
})->with(['passo', 'jornada', 'condição']);

/* ---------------------------------------------------------------------------
| R18 — idiomas
--------------------------------------------------------------------------- */

it('[CT-32] jornadas, passos, paradas de tour e condições semeadas têm texto nos dois idiomas', function (): void {
    semearOnboardingDoKit();

    $nos = 0;

    $cheio = function (mixed $texto, string $onde) use (&$nos): void {
        $nos++;
        expect($texto)->toBeArray()
            ->and(trim((string) ($texto['pt_BR'] ?? '')))->not->toBe('', "{$onde}: pt_BR vazio")
            ->and(trim((string) ($texto['en'] ?? '')))->not->toBe('', "{$onde}: en vazio");
    };

    foreach (OnboardingFlow::query()->get() as $jornada) {
        $cheio($jornada->title, "título da jornada {$jornada->key}");
        $cheio($jornada->description, "descrição da jornada {$jornada->key}");
        expect($jornada->title['pt_BR'])->not->toBe($jornada->title['en'], "título da jornada {$jornada->key} igual nos dois idiomas");
    }

    foreach (OnboardingStep::query()->get() as $passo) {
        $cheio($passo->title, "título do passo {$passo->key}");
        $cheio($passo->description, "descrição do passo {$passo->key}");
        expect($passo->description['pt_BR'])->not->toBe($passo->description['en'], "descrição do passo {$passo->key} igual nos dois idiomas");

        foreach ((array) $passo->tour_steps as $i => $parada) {
            $cheio($parada['title'] ?? null, "título da parada {$i} de {$passo->key}");
            $cheio($parada['body'] ?? null, "corpo da parada {$i} de {$passo->key}");
            expect($parada['body']['pt_BR'])->not->toBe($parada['body']['en'], "corpo da parada {$i} de {$passo->key} igual nos dois idiomas");
        }
    }

    foreach (OnboardingCondition::query()->get() as $condicao) {
        $cheio($condicao->label, "rótulo da condição {$condicao->key}");
        $cheio($condicao->description, "descrição da condição {$condicao->key}");
    }

    expect($nos)->toBeGreaterThan(100);
});

it('[CT-33] a administradora edita os dois idiomas do registro semeado pela autoria', function (string $registro): void {
    semearOnboardingDoKit();
    ligarTutorialGuiado(true);

    $admin = pessoaDoPainelDoOnboarding('admin');
    $this->actingAs($admin);
    noPainelBootado('admin');

    $jornada = jornadaSemeadaDoPainel('admin');
    $campos  = ['pt_BR' => 'Comece por aqui', 'en' => 'Start here'];

    // Colunas json comparadas pelo conteúdo (a ordem das chaves e o espaçamento do JSON não são dado).
    // E o formulário do vendor descarta chave nula (`selector`) e grava `{pt_BR: null, en: null}` no lugar de `null`:
    // sem texto é sem texto, e isso não é "campo que mudou".
    $semNulos = static function (mixed $valor) use (&$semNulos): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $limpo = array_filter(array_map($semNulos, $valor), static fn (mixed $item): bool => $item !== null && $item !== []);

        return $limpo === [] ? null : $limpo;
    };
    $normaliza  = static fn (mixed $valor): mixed => $semNulos(is_string($valor) && in_array($valor[0] ?? '', ['[', '{'], true) ? json_decode($valor, true) : $valor);
    $semOCampo  = static fn (array $atributos, string $campo): array => array_map($normaliza, array_diff_key($atributos, [$campo => 1, 'updated_at' => 1]));
    $diferentes = static fn (array $depois, array $antes): array => array_keys(array_filter(
        $depois,
        static fn (mixed $valor, string $chave): bool => $valor != ($antes[$chave] ?? null),
        ARRAY_FILTER_USE_BOTH,
    ));

    if ($registro === 'jornada') {
        $antes = $semOCampo($jornada->getAttributes(), 'title');

        Livewire::test(EditOnboardingFlow::class, ['record' => $jornada->getKey()])
            ->fillForm(['title' => $campos])
            ->call('save')
            ->assertHasNoFormErrors();

        $depois = $jornada->fresh();

        expect($depois->title)->toMatchArray($campos)
            ->and($diferentes($semOCampo($depois->getAttributes(), 'title'), $antes))->toBe([]);
    }

    if ($registro === 'passo') {
        $passo = passoDoPainelNoOnboarding('admin', 'usuarios');
        $antes = $semOCampo($passo->getAttributes(), 'title');

        Livewire::test(StepsRelationManager::class, ['ownerRecord' => $jornada, 'pageClass' => EditOnboardingFlow::class])
            ->loadTable()
            ->callAction(TestAction::make('edit')->table($passo), ['title' => $campos])
            ->assertHasNoFormErrors();

        $depois = $passo->fresh();

        $mudaram = $diferentes($semOCampo($depois->getAttributes(), 'title'), $antes);

        expect($depois->title)->toMatchArray($campos)
            ->and($mudaram)->toBe([], 'campos do passo que a edição do título mudou: '.json_encode(array_map(
                fn (string $campo): array => ['antes' => $antes[$campo] ?? null, 'depois' => $semOCampo($depois->getAttributes(), 'title')[$campo] ?? null],
                $mudaram,
            ), JSON_UNESCAPED_UNICODE));
    }

    if ($registro === 'condição') {
        $condicao = OnboardingCondition::query()->firstOrFail();
        $antes    = $semOCampo($condicao->getAttributes(), 'label');

        Livewire::test(EditOnboardingCondition::class, ['record' => $condicao->getKey()])
            ->fillForm(['label' => $campos])
            ->call('save')
            ->assertHasNoFormErrors();

        $depois = $condicao->fresh();

        expect($depois->label)->toMatchArray($campos)
            ->and($diferentes($semOCampo($depois->getAttributes(), 'label'), $antes))->toBe([]);
    }

    expect(passosDoPainelNoOnboarding('admin'))->toHaveCount(11);
})->with(['jornada', 'passo', 'condição']);

/* ---------------------------------------------------------------------------
| R25 (CT-52) — filho de organização sozinho, sem multi-organização
--------------------------------------------------------------------------- */

it('[CT-52] o seeder filho de organização rodado sozinho com a multi-organização desligada não cria nada', function (string $filho): void {
    expect(config('kit.tenancy.enabled'))->toBeFalsy();

    $this->artisan('db:seed', ['--class' => $filho, '--force' => true])->assertSuccessful();

    expect(OnboardingFlow::query()->count())->toBe(0)->and(OnboardingStep::query()->count())->toBe(0);
})->with([
    'organizações'            => ['Database\\Seeders\\Onboarding\\OrganizacoesSeeder'],
    'convites da organização' => ['Database\\Seeders\\Onboarding\\ConvitesDaOrganizacaoSeeder'],
    'projetos'                => ['Database\\Seeders\\Onboarding\\ProjetosSeeder'],
]);
