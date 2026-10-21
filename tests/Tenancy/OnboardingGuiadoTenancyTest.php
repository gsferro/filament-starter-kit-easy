<?php

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\App\Resources\Convites\ConviteResource as ConviteDoApp;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\App\Resources\Users\UserResource as MembrosDoApp;
use App\Filament\Pages\PrimeirosPassos;
use App\Livewire\ChecklistDoOnboarding;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\OnboardingSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Symfony\Component\Console\Output\BufferedOutput;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingStep;

/**
 * Tutorial guiado com multi-organização (grupo G10 do 04 da wiki `onboarding-guiado`): R19, R20
 * (CT-55), R22 (CT-41), R24 e R25 (CT-51). Só a suíte Tenancy liga a multi-organização no bootstrap.
 *
 * Os helpers `onbTenancy*` são usados só por este arquivo e por isso vivem aqui.
 */

/** Semeia o que todo caso precisa: permissões e papéis (o `PapeisSeeder` cria `admin_app` só com tenancy). */
function onbTenancyBase(): void
{
    test()->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
}

/** As features dos passos ligadas, para a visibilidade não esconder passo por acaso (Setup Global). */
function onbTenancyLigarFeatures(bool $demo = true): void
{
    ligarDock(['admin', 'app', 'infra']);
    ligarDashboardDinamico(true, []);
    ligarAgentesDeIa(true, []);
    config([
        'kit.hub'                => true,
        'lockscreen.enabled'     => true,
        'command-center.enabled' => true,
        'kit.demo'               => $demo,
    ]);
}

/**
 * A Acme e a Globex, e a Carla (`panel_user`) nas duas.
 *
 * @return array{acme: Tenant, globex: Tenant, carla: User}
 */
function onbTenancyCarlaNasDuas(): array
{
    $acme   = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);
    $globex = Tenant::factory()->create(['nome' => 'Globex', 'slug' => 'globex']);

    $carla = usuarioComPapel('panel_user', $acme, 'carla@example.com');
    papelNaOrganizacao($carla, 'panel_user', $globex);
    $carla->tenants()->attach([$acme->id, $globex->id]);

    return compact('acme', 'globex', 'carla');
}

/**
 * O checklist montado como a pessoa o vê: no `/app/{organização}` por request real (boota o painel),
 * nos demais pelo painel bootado.
 */
function onbTenancyChecklist(string $painel, ?Tenant $organizacao = null): Testable
{
    if ($painel === 'app') {
        test()->followingRedirects()->get('/app/'.$organizacao->slug)->assertOk();
        fronteiraDeRequest();
        noPainelDa($organizacao);
    } else {
        noPainelBootado($painel);
    }

    return Livewire::test(ChecklistDoOnboarding::class);
}

/** O estado do passo no checklist: null quando não está visível, true quando concluído. */
function onbTenancyPasso(Testable $checklist, string $chave): ?bool
{
    return $checklist->instance()->flowState()?->step($chave)?->isCompleted();
}

/** @return array{condicao: int, visita: int, manual: int, total: int} */
function onbTenancyPassosDaJornada(string $painel): array
{
    $jornada = OnboardingFlow::query()->where('key', "primeiros-passos-{$painel}")->firstOrFail();
    $passos  = OnboardingStep::query()->where('flow_id', $jornada->getKey())->get();
    $modo    = fn (string $nome): int => $passos->filter(fn ($p): bool => $p->completion_mode->value === $nome)->count();

    return [
        'condicao' => $modo('condition'),
        'visita'   => $modo('visit'),
        'manual'   => $modo('manual'),
        'total'    => $passos->count(),
    ];
}

/**
 * Pages e Resources que o painel registra, sem seeder nem isenção declarada.
 *
 * @return array{descobertas: list<class-string>, registradas: list<class-string>}
 */
function onbTenancyInventario(string $painel): array
{
    $filament    = Filament::getPanel($painel);
    $registradas = array_values(array_unique([...$filament->getPages(), ...$filament->getResources()]));

    $descobertas = array_values(array_filter(
        $registradas,
        fn (string $tela): bool => ! array_key_exists($tela, OnboardingSeeder::TELAS[$painel] ?? [])
            && ! array_key_exists($tela, OnboardingSeeder::ISENTAS)
            && ! array_key_exists($tela, OnboardingSeeder::ISENTAS_NO_PAINEL[$painel] ?? []),
    ));

    return compact('descobertas', 'registradas');
}

/** HTML sem os blocos `<script>`: marcador dentro de script não é o checklist na tela. */
function onbTenancyHtmlSemScript(string $html): string
{
    return (string) preg_replace('~<script\b[^>]*>.*?</script>~is', '', $html);
}

/** Diretório de instalação novo, isolado, com o `.env.example` — nunca o `.env` do projeto. */
function onbTenancyDiretorioDeInstalacao(): string
{
    $base = sys_get_temp_dir().'/kit_install_onb_'.getmypid().'_'.Str::random(10);

    File::ensureDirectoryExists($base);
    File::put("{$base}/.env", File::get(base_path('.env.example')));

    app()->setBasePath($base);

    return $base;
}

afterEach(function (): void {
    foreach (File::glob(sys_get_temp_dir().'/kit_install_onb_'.getmypid().'_*') as $temporario) {
        File::deleteDirectory($temporario);
    }
});

/*
|--------------------------------------------------------------------------
| R19 — passos só de organização e consumo no /app/{organização}
|--------------------------------------------------------------------------
*/

it('[CT-34] com multi-organização, o /admin e o /app ganham os passos só de organização', function (): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true, 'kit.demo' => false]);

    $this->seed(OnboardingSeeder::class);

    expect(onbTenancyPassosDaJornada('admin'))->toBe(['condicao' => 2, 'visita' => 1, 'manual' => 9, 'total' => 12]);

    $admin        = OnboardingFlow::query()->where('key', 'primeiros-passos-admin')->firstOrFail();
    $organi       = OnboardingStep::query()->where('flow_id', $admin->getKey())->where('key', 'organizacoes')->firstOrFail();
    $rotasDoPasso = collect($organi->tour_steps)->pluck('route')->all();
    expect($rotasDoPasso)->not->toBeEmpty()
        ->and(collect($rotasDoPasso)->contains(fn (string $r): bool => str_contains($r, 'organizacoes')))->toBeTrue('o passo de Organizações não tem parada na tela de Organizações');

    expect(onbTenancyPassosDaJornada('app'))->toBe(['condicao' => 2, 'visita' => 0, 'manual' => 9, 'total' => 11]);

    $app    = OnboardingFlow::query()->where('key', 'primeiros-passos-app')->firstOrFail();
    $chaves = OnboardingStep::query()->where('flow_id', $app->getKey())->pluck('key')->all();
    expect($chaves)->toContain('membros-da-organizacao', 'convites-da-organizacao', 'convites-recebidos', 'projetos', 'troca-de-organizacao');

    $paradasDoApp = collect(OnboardingStep::query()->where('flow_id', $app->getKey())->get())
        ->mapWithKeys(fn ($p): array => [$p->key => collect($p->tour_steps)->pluck('route')->filter()->all()]);
    expect($paradasDoApp['membros-da-organizacao'])->toContain('filament.app.resources.users.index')
        ->and($paradasDoApp['convites-da-organizacao'])->toContain('filament.app.resources.convites.index')
        ->and($paradasDoApp['convites-recebidos'])->not->toBeEmpty()
        ->and($paradasDoApp['projetos'])->not->toBeEmpty();

    expect(onbTenancyPassosDaJornada('infra')['total'])->toBe(15);

    // Todo passo de toda jornada tem ao menos uma parada de tour que resolve no painel da jornada.
    foreach (['admin', 'app', 'infra'] as $painel) {
        $jornada = OnboardingFlow::query()->where('key', "primeiros-passos-{$painel}")->firstOrFail();

        foreach (OnboardingStep::query()->where('flow_id', $jornada->getKey())->get() as $passo) {
            $resolvem = collect($passo->tour_steps)
                ->pluck('route')
                ->filter(fn ($rota): bool => is_string($rota) && str_starts_with($rota, "filament.{$painel}.") && Route::has($rota));

            expect($resolvem->isNotEmpty())->toBeTrue("o passo {$passo->key} da jornada /{$painel} não tem parada que resolva no painel");
        }
    }

    // Com a Acme aberta, toda parada da jornada do /app gera endereço dentro de /app/acme.
    $acme = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    foreach (OnboardingStep::query()->where('flow_id', $app->getKey())->get() as $passo) {
        foreach (collect($passo->tour_steps)->pluck('route')->filter() as $rota) {
            $endereco = route($rota, ['tenant' => $acme->slug]);

            expect($endereco)->toStartWith(url('/app/acme'), "a parada {$rota} do passo {$passo->key} gera {$endereco}");
        }
    }
});

it('[CT-35] ligar a multi-organização depois e re-semear acrescenta só os passos de organização', function (): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => false]);
    $this->seed(OnboardingSeeder::class);

    $admin = OnboardingFlow::query()->where('key', 'primeiros-passos-admin')->firstOrFail();
    $admin->update(['title' => [...$admin->title, 'pt_BR' => 'Comece por aqui']]);

    config(['kit.tenancy.enabled' => true]);
    $this->seed(OnboardingSeeder::class);

    expect(onbTenancyPassosDaJornada('admin')['total'])->toBe(12)
        ->and(onbTenancyPassosDaJornada('app')['total'])->toBe(11)
        ->and(OnboardingFlow::query()->count())->toBe(3)
        ->and(OnboardingFlow::query()->where('key', 'primeiros-passos-admin')->firstOrFail()->title['pt_BR'])->toBe('Comece por aqui');
});

it('[CT-36] o passo de Projetos aparece no /app da organização só com a demo ligada', function (bool $demo): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true, 'kit.demo' => false]);
    $this->seed(OnboardingSeeder::class);

    ['acme' => $acme, 'carla' => $carla] = onbTenancyCarlaNasDuas();
    ligarTutorialGuiado();
    $this->actingAs($carla);

    // Controle da Carla na Acme (N₁): demo ligada.
    onbTenancyLigarFeatures(true);
    $controle = onbTenancyChecklist('app', $acme)->instance()->flowState();
    $n1       = $controle->total();

    expect($controle->key())->toBe('primeiros-passos-app')
        ->and($controle->step('projetos'))->not->toBeNull('o controle (demo ligada) devia trazer o passo de Projetos');

    onbTenancyLigarFeatures($demo);
    $fluxo = onbTenancyChecklist('app', $acme)->instance()->flowState();

    expect($fluxo->total())->toBe($demo ? $n1 : $n1 - 1)
        ->and($fluxo->step('projetos') !== null)->toBe($demo);
})->with([
    'demo ligada'    => [true],
    'demo desligada' => [false],
]);

it('[CT-37] dentro da organização, o checklist e a página seguem o interruptor', function (string $persona, bool $ligado, string $tela, string $resultado): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true]);
    $this->seed(OnboardingSeeder::class);

    ['acme' => $acme, 'globex' => $globex, 'carla' => $carla] = onbTenancyCarlaNasDuas();
    $davi                                                     = usuarioComPapel('admin_app', $acme, 'davi@example.com');
    $davi->tenants()->attach($acme->id);
    onbTenancyLigarFeatures();
    ligarTutorialGuiado($ligado);

    $this->actingAs($persona === 'davi' ? $davi : $carla);

    $itemDoMenu = PrimeirosPassos::getUrl(panel: 'app', tenant: $acme);

    if ($tela === 'dashboard') {
        $resposta = $this->followingRedirects()->get('/app/acme')->assertOk();
        $html     = onbTenancyHtmlSemScript($resposta->getContent());

        if ($ligado) {
            $resposta->assertSeeLivewire('filament-onboarding-launcher');
            expect($html)->toContain('fio-ring');

            fronteiraDeRequest();
            noPainelDa($acme);
            expect(Livewire::test(ChecklistDoOnboarding::class)->instance()->flowState()?->key())->toBe('primeiros-passos-app');
        } else {
            $resposta->assertDontSeeLivewire('filament-onboarding-launcher');
            expect($html)->not->toContain('fio-ring')
                ->and($html)->not->toContain($itemDoMenu);
        }

        return;
    }

    if ($ligado) {
        $resposta = $this->get('/app/acme/onboarding')->assertOk();
        $resposta->assertSeeLivewire(PrimeirosPassos::class);

        fronteiraDeRequest();
        noPainelDa($acme);
        $fluxo   = Livewire::test(ChecklistDoOnboarding::class)->instance()->flowState();
        $noBanco = onbTenancyPassosDaJornada('app')['total'];
        // P-11: só passos cuja tela ela abre — sem número absoluto para o panel_user.
        expect($fluxo->key())->toBe('primeiros-passos-app')
            ->and($fluxo->total())->toBeGreaterThan(0)
            ->and($fluxo->total())->toBeLessThanOrEqual($noBanco);
    } else {
        $this->get('/app/acme/onboarding')->assertNotFound();
    }
})->with([
    'Carla, ligado, dashboard'     => ['carla', true, 'dashboard', 'checklist no corpo'],
    'Carla, ligado, página'        => ['carla', true, 'pagina', '200, 11 passos'],
    'Davi, ligado, dashboard'      => ['davi', true, 'dashboard', 'checklist no corpo'],
    'Carla, desligado, dashboard'  => ['carla', false, 'dashboard', 'sem checklist nem item'],
    'Carla, desligado, página'     => ['carla', false, 'pagina', '404'],
]);

/*
|--------------------------------------------------------------------------
| R22 — o inventário com multi-organização
|--------------------------------------------------------------------------
*/

it('[CT-41] com multi-organização, toda tela de organização tem seeder ou isenção', function (): void {
    expect(config('kit.tenancy.enabled'))->toBeTrue();

    $registradas = [];

    foreach (['admin', 'app', 'infra'] as $painel) {
        ['descobertas' => $descobertas, 'registradas' => $registradas[$painel]] = onbTenancyInventario($painel);

        expect($descobertas)->toBe([], "telas do /{$painel} sem seeder nem isenção: ".implode(', ', $descobertas));
    }

    $filhosDoPai = [...OnboardingSeeder::FILHOS, ...OnboardingSeeder::FILHOS_DE_TENANCY];

    $deOrganizacao = [
        'admin' => [TenantResource::class],
        'app'   => [MembrosDoApp::class, ConviteDoApp::class, ConvitesRecebidos::class, ProjetoResource::class],
    ];

    foreach ($deOrganizacao as $painel => $telas) {
        foreach ($telas as $tela) {
            expect($registradas[$painel])->toContain($tela);

            $filho = OnboardingSeeder::TELAS[$painel][$tela] ?? null;
            expect($filho)->not->toBeNull("a tela {$tela} do /{$painel} não está coberta por seeder filho")
                ->and($filhosDoPai)->toContain($filho);
        }
    }
});

/*
|--------------------------------------------------------------------------
| R24 — progresso e condição de convite por organização
|--------------------------------------------------------------------------
*/

it('[CT-49] o passo "Convites" do /app conclui só na organização em que a pessoa convidou', function (string $convites, string $organizacao, string $estado): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true]);
    $this->seed(OnboardingSeeder::class);

    $acme   = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);
    $globex = Tenant::factory()->create(['nome' => 'Globex', 'slug' => 'globex']);

    $davi = usuarioComPapel('admin_app', $acme, 'davi@example.com');
    papelNaOrganizacao($davi, 'admin_app', $globex);
    $davi->tenants()->attach([$acme->id, $globex->id]);

    ligarTutorialGuiado();
    onbTenancyLigarFeatures();
    $this->actingAs($davi);

    if ($convites === 'davi') {
        ofertaPara('convidado@example.com', $acme, 'panel_user', ['convidado_por_id' => $davi->getKey()]);
    }

    if ($convites === 'elisa') {
        $elisa = usuarioComPapel('admin_app', $acme, 'elisa@example.com');
        ofertaPara('convidado@example.com', $acme, 'panel_user', ['convidado_por_id' => $elisa->getKey()]);
    }

    $checklist = onbTenancyChecklist('app', $organizacao === 'acme' ? $acme : $globex);
    $passo     = onbTenancyPasso($checklist, 'convites-da-organizacao');

    expect($passo)->not->toBeNull('o passo Convites não está visível no checklist')
        ->and($passo)->toBe($estado === 'concluído');
})->with([
    'convite do Davi, vendo a Acme'          => ['davi', 'acme', 'concluído'],
    'convite do Davi, vendo a Globex'        => ['davi', 'globex', 'pendente'],
    'nenhum convite, vendo a Acme'           => ['nenhum', 'acme', 'pendente'],
    'convite da Elisa, Davi vendo a Acme'    => ['elisa', 'acme', 'pendente'],
]);

it('[CT-50] concluir um passo numa organização não conclui na outra', function (string $organizacao, string $estado): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true]);
    $this->seed(OnboardingSeeder::class);

    ['acme' => $acme, 'globex' => $globex, 'carla' => $carla] = onbTenancyCarlaNasDuas();
    ligarTutorialGuiado();
    onbTenancyLigarFeatures();
    $this->actingAs($carla);

    $naAcme = onbTenancyChecklist('app', $acme);
    expect(onbTenancyPasso($naAcme, 'busca-global'))->toBeFalse('"Busca ⌘K" devia começar pendente na Acme');
    $naGlobex = onbTenancyChecklist('app', $globex);
    expect(onbTenancyPasso($naGlobex, 'busca-global'))->toBeFalse('"Busca ⌘K" devia começar pendente na Globex');

    onbTenancyChecklist('app', $acme)->call('completeStep', 'busca-global');

    $checklist = onbTenancyChecklist('app', $organizacao === 'acme' ? $acme : $globex);

    expect(onbTenancyPasso($checklist, 'busca-global'))->toBe($estado === 'concluído');
})->with([
    'Acme'   => ['acme', 'concluído'],
    'Globex' => ['globex', 'pendente'],
]);

/*
|--------------------------------------------------------------------------
| R25 — desligar a multi-organização esconde sem apagar
|--------------------------------------------------------------------------
*/

it('[CT-51] desligar a multi-organização esconde os passos de organização sem apagar, e religar os traz de volta', function (): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true]);
    $this->seed(OnboardingSeeder::class);

    ligarTutorialGuiado();
    onbTenancyLigarFeatures();
    $administradora = usuarioComPapel('admin', null, 'admin@example.com');
    $this->actingAs($administradora);

    onbTenancyChecklist('admin')->call('completeStep', 'organizacoes');
    expect(onbTenancyPasso(onbTenancyChecklist('admin'), 'organizacoes'))->toBeTrue('o passo de Organizações devia estar concluído antes do 2-switch');

    config(['kit.tenancy.enabled' => false]);
    $desligada = onbTenancyChecklist('admin')->instance()->flowState();
    $noBanco   = onbTenancyPassosDaJornada('admin')['total'];

    config(['kit.tenancy.enabled' => true]);
    $religada = onbTenancyChecklist('admin')->instance()->flowState();

    expect($desligada->total())->toBe(11)
        ->and($desligada->completedCount())->toBe(0)
        ->and($desligada->step('organizacoes'))->toBeNull()
        ->and($noBanco)->toBe(12)
        ->and($religada->total())->toBe(12)
        ->and($religada->completedCount())->toBe(1)
        ->and($religada->step('organizacoes')?->isCompleted())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| R20 — kit:install --onboarding com multi-organização
|--------------------------------------------------------------------------
*/

it('[CT-55] com multi-organização, o kit:install --onboarding semeia também os passos de organização', function (): void {
    $base = onbTenancyDiretorioDeInstalacao();
    File::put("{$base}/.env", (string) preg_replace('~^KIT_TENANCY=.*$~m', 'KIT_TENANCY=true', File::get("{$base}/.env")));
    config(['app.key' => '', 'kit.tenancy.enabled' => true]);

    $buffer = new BufferedOutput;
    Artisan::call('kit:install', [
        '--onboarding'     => true,
        '--no-npm'         => true,
        '--no-support'     => true,
        '--no-interaction' => true,
    ], $buffer);
    $saida = $buffer->fetch();

    expect(OnboardingFlow::query()->count())->toBe(3, $saida)
        ->and(OnboardingStep::query()->count())->toBe(38)
        ->and(onbTenancyPassosDaJornada('admin')['total'])->toBe(12)
        ->and(onbTenancyPassosDaJornada('app')['total'])->toBe(11)
        ->and(onbTenancyPassosDaJornada('infra')['total'])->toBe(15)
        ->and(configuracaoGravada('onboarding_habilitado'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| R8 — a ação do checklist montado em /app/{organização} é recusada depois do desligar
|--------------------------------------------------------------------------
*/

it('[CT-63] dentro da organização, a ação do checklist montado também é recusada depois do desligar', function (string $acao): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true]);

    ['acme' => $acme, 'carla' => $carla] = onbTenancyCarlaNasDuas();

    $jornada = OnboardingFlow::query()->create([
        'key'            => 'jornada-de-teste-app',
        'panel_id'       => 'app',
        'title'          => ['pt_BR' => 'Jornada de teste', 'en' => 'Test journey'],
        'is_active'      => true,
        'is_dismissible' => true,
        'sort_order'     => 1,
    ]);
    OnboardingStep::query()->create([
        'flow_id'         => $jornada->getKey(),
        'key'             => 'manual-pendente',
        'type'            => 'tour',
        'title'           => ['pt_BR' => 'Passo manual', 'en' => 'Manual step'],
        'completion_mode' => 'manual',
        'is_active'       => true,
        'is_required'     => true,
        'sort_order'      => 1,
        'tour_steps'      => [
            ['route'        => 'filament.app.pages.onboarding', 'url' => null, 'target' => null, 'selector' => null, 'placement' => 'auto',
                'title'     => ['pt_BR' => 'Parada', 'en' => 'Stop'], 'body' => ['pt_BR' => 'Texto', 'en' => 'Text'],
                'condition' => null, 'advance' => null, 'optional' => false],
        ],
    ]);

    ligarTutorialGuiado();
    $this->actingAs($carla);

    $checklist = onbTenancyChecklist('app', $acme);
    expect($checklist->instance()->flowState()?->step('manual-pendente'))->not->toBeNull('o passo de teste devia estar visível com o tutorial ligado');

    $fotografia = fn (): array => [
        DB::table('onboarding_step_progress')->orderBy('id')->get()->toArray(),
        DB::table('onboarding_flow_progress')->orderBy('id')->get()->toArray(),
        DB::table('onboarding_preferences')->orderBy('id')->get()->toArray(),
    ];
    $antes = $fotografia();

    ligarTutorialGuiado(false);

    $checklist->call($acao, 'manual-pendente')->assertNotFound()->assertNotDispatched('onboarding-tour-start');

    expect($fotografia())->toEqual($antes);
})->with([
    'completeStep' => ['completeStep'],
    'startTour'    => ['startTour'],
]);

/*
|--------------------------------------------------------------------------
| R25 — o /app sem multi-organização
|--------------------------------------------------------------------------
*/

it('[CT-64] desligar a multi-organização esconde os passos de organização do /app sem apagar', function (): void {
    onbTenancyBase();
    config(['kit.tenancy.enabled' => true]);
    $this->seed(OnboardingSeeder::class);

    ligarTutorialGuiado();
    onbTenancyLigarFeatures();

    $acme = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);
    $ana  = usuarioComPapel('panel_user', $acme, 'ana@example.com');
    $ana->tenants()->attach($acme->id);
    $this->actingAs($ana);

    $app        = OnboardingFlow::query()->where('key', 'primeiros-passos-app')->firstOrFail();
    $fotografia = fn (): array => OnboardingStep::query()->where('flow_id', $app->getKey())->orderBy('id')->get()->toArray();
    $antes      = $fotografia();

    // Controle positivo: com a multi-organização ligada ela vê os passos de organização que o papel abre.
    $controle = onbTenancyChecklist('app', $acme)->instance()->flowState();
    expect($controle->step('projetos'))->not->toBeNull('o controle devia ver Projetos com a multi-organização ligada');

    config(['kit.tenancy.enabled' => false]);
    fronteiraDeRequest();
    Filament::setCurrentPanel('app');
    $fluxo = Livewire::test(ChecklistDoOnboarding::class)->instance()->flowState();

    $visiveis = $fluxo?->steps->map->key()->all() ?? [];
    foreach (['membros-da-organizacao', 'convites-da-organizacao', 'convites-recebidos', 'troca-de-organizacao', 'projetos'] as $chave) {
        expect($visiveis)->not->toContain($chave);
    }

    expect(onbTenancyPassosDaJornada('app')['total'])->toBe(11)
        ->and($fotografia())->toEqual($antes);
});
