<?php

use App\Filament\Pages\PrimeirosPassos;
use App\Models\User;
use Database\Seeders\OnboardingSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wallacemartinss\FilamentOnboarding\Facades\Onboarding;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingStep;

/**
 * Tutorial guiado (wiki `onboarding-guiado`) — lote do consumo: G5 (o que cada request
 * renderiza e o que uma visita grava) e G6 (as ações do checklist já montado).
 *
 * O `Então` de cada caso vem do `04-casos-de-teste.md`. O estado do tutorial é fixado por
 * `ligarTutorialGuiado()`; o checklist do painel `/app` boota por GET real antes do
 * `Livewire::test()` (`checklistDoOnboarding()`).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** O endereço do dashboard de cada painel. */
function dashboardDoOnboarding(string $painel): string
{
    return "/{$painel}";
}

/** O link do item "Primeiros passos" está na navegação (href exato: `/admin/onboarding-flows` não conta). */
function itemDePrimeirosPassosNoHtml(string $html, string $painel): bool
{
    return preg_match('~href="[^"]*/'.preg_quote($painel, '~').'/onboarding"~', $html) === 1;
}

/* ---------------------------------------------------------------------------
| R6 — desligado, nada do consumo aparece
--------------------------------------------------------------------------- */

it('[CT-11] a tela do painel vem sem checklist, tour, boas-vindas e item "Primeiros passos" com o tutorial desligado', function (string $painel, bool $ligado): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado($ligado);

    $pessoa   = pessoaDoPainelDoOnboarding($painel);
    $resposta = abrirComoPessoaNoOnboarding($pessoa, dashboardDoOnboarding($painel))->assertOk();
    $html     = htmlDeInterfaceDoOnboarding($resposta);

    afirmarChecklistNaResposta($resposta, $ligado, "/{$painel}");

    expect(itemDePrimeirosPassosNoHtml($html, $painel))->toBe($ligado, 'item "Primeiros passos" na navegação');
    expect(str_contains($html, 'fio-welcome-layer'))->toBeFalse('a tela de boas-vindas do pacote está no corpo');

    if (! $ligado) {
        expect(str_contains($resposta->getContent(), 'x-data="onboardingTour"'))->toBeFalse('a marcação de tour está no corpo');
    }
})->with([
    'app desligado'   => ['app', false],
    'admin desligado' => ['admin', false],
    'infra desligado' => ['infra', false],
    'app ligado'      => ['app', true],
    'admin ligado'    => ['admin', true],
    'infra ligado'    => ['infra', true],
]);

it('[CT-45] a URL da página "Primeiros passos" responde 404 com o tutorial desligado, nos três painéis', function (string $papel, string $painel): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(false);

    $pessoa = usuarioDoKit($papel, "{$papel}@example.com");

    abrirComoPessoaNoOnboarding($pessoa, "/{$painel}/onboarding")
        ->assertNotFound()
        ->assertDontSeeLivewire(PrimeirosPassos::class);
})->with([
    'admin'  => ['admin', 'admin'],
    'app'    => ['panel_user', 'app'],
    'infra'  => ['infra', 'infra'],
    'master' => ['master_global', 'admin'],
]);

/* ---------------------------------------------------------------------------
| R7 — o widget de progresso
--------------------------------------------------------------------------- */

it('[CT-12] o widget de progresso some e volta, e o progresso gravado fica intacto', function (): void {
    jornadaDeTesteDoOnboarding('admin');
    ligarTutorialGuiado(true);

    $admin = usuarioDoKit('admin', 'admin@example.com');
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    foreach (['manual-pendente', 'concluido', 'com-imagem', 'com-video'] as $passo) {
        concluirPassoDoOnboarding($admin, 'admin', $passo);
    }

    concluirPassoDoOnboarding($bruno, 'admin', 'concluido');

    $texto = textoDoDashboardDoMaster();

    expect($texto)->toContain('Jornadas de onboarding concluídas')
        ->and($texto)->toMatch('~\b1\s*/\s*2\b~');

    $antes = fotografiaDoOnboarding();

    ligarTutorialGuiado(false);
    $desligado = textoDoDashboardDoMaster();

    expect($desligado)->not->toContain('Jornadas de onboarding concluídas')
        ->and(fotografiaDoOnboarding())->toBe($antes);

    ligarTutorialGuiado(true);
    $religado = textoDoDashboardDoMaster();

    expect($religado)->toContain('Jornadas de onboarding concluídas')
        ->and($religado)->toMatch('~\b1\s*/\s*2\b~');
});

/* ---------------------------------------------------------------------------
| R9 — ligado, os três painéis
--------------------------------------------------------------------------- */

it('[CT-15] o dashboard de cada painel traz o checklist da jornada daquele painel', function (string $papel, string $painel): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(true);

    $pessoa = usuarioDoKit($papel, "{$papel}@example.com");

    $resposta = abrirComoPessoaNoOnboarding($pessoa, dashboardDoOnboarding($painel))->assertOk();
    $html     = htmlDeInterfaceDoOnboarding($resposta);

    afirmarChecklistNaResposta($resposta, true, "/{$painel}");

    expect(itemDePrimeirosPassosNoHtml($html, $painel))->toBeTrue('item "Primeiros passos" na navegação')
        ->and(str_contains($html, 'fio-welcome-layer'))->toBeFalse('a tela de boas-vindas do pacote está no corpo');

    $checklist = checklistDoOnboarding($painel, $pessoa);
    $atual     = $checklist->instance()->flowState();

    expect($atual)->not->toBeNull()
        ->and($atual->flow->panel_id)->toBe($painel)
        ->and(str_starts_with((string) $atual->title(), 'Primeiros passos'))->toBeTrue();

    $oferecidas = Onboarding::for($pessoa)->flows($painel);

    expect($oferecidas)->not->toBeEmpty()
        ->and($oferecidas->map(fn ($estado): ?string => $estado->flow->panel_id)->unique()->all())->toBe([$painel]);
})->with([
    'administradora, admin' => ['admin', 'admin'],
    'Ana, app'              => ['panel_user', 'app'],
    'Íris, infra'           => ['infra', 'infra'],
    'master, infra'         => ['master_global', 'infra'],
    'master, admin'         => ['master_global', 'admin'],
]);

it('[CT-16] a página "Primeiros passos" mostra a jornada do painel e nenhuma outra', function (string $painel, int $passos): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $pessoa = pessoaDoPainelDoOnboarding($painel);

    $resposta = abrirComoPessoaNoOnboarding($pessoa, "/{$painel}/onboarding")
        ->assertOk()
        ->assertSeeLivewire(PrimeirosPassos::class);

    $html = $resposta->getContent();

    // Controle positivo: a chave da jornada do painel está no corpo (o `wire:key` da seção).
    $this->assertStringContainsString("primeiros-passos-{$painel}", $html, "a chave da jornada de /{$painel} não está no corpo");

    foreach (array_diff(['admin', 'app', 'infra'], [$painel]) as $outro) {
        $this->assertStringNotContainsString("primeiros-passos-{$outro}", $html, "a jornada de /{$outro} está no corpo");
    }

    $flows = Onboarding::for($pessoa)->flows($painel);

    expect($flows)->toHaveCount(1)
        ->and($flows->first()->flow->panel_id)->toBe($painel)
        ->and($flows->first()->total())->toBe($passos);
})->with([
    'admin' => ['admin', 11],
    'app'   => ['app', 5],
    'infra' => ['infra', 15],
]);

it('[CT-47] a permissão da página decide quem vê o checklist, e todo papel do kit a recebe no seu painel', function (string $papel, string $painel, bool $retirada): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(true);

    if ($retirada) {
        semAPermissao($papel, 'View:PrimeirosPassos');
        noPainelDoShield($painel);
    }

    $pessoa = usuarioDoKit($papel, "{$papel}@example.com");

    $resposta = abrirComoPessoaNoOnboarding($pessoa, dashboardDoOnboarding($painel))->assertOk();
    $html     = htmlDeInterfaceDoOnboarding($resposta);

    afirmarChecklistNaResposta($resposta, ! $retirada, "/{$painel}");

    expect(itemDePrimeirosPassosNoHtml($html, $painel))->toBe(! $retirada, 'item "Primeiros passos"');
})->with([
    'admin como semeada'       => ['admin', 'admin', false],
    'panel_user como semeada'  => ['panel_user', 'app', false],
    'infra como semeada'       => ['infra', 'infra', false],
    'admin retirada'           => ['admin', 'admin', true],
    'infra retirada'           => ['infra', 'infra', true],
    'panel_user retirada'      => ['panel_user', 'app', true],
]);

it('[CT-62] com o tutorial ligado e sem a permissão da página, a URL de "Primeiros passos" responde 403', function (string $papel, string $painel): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(true);

    semAPermissao($papel, 'View:PrimeirosPassos');
    noPainelDoShield($painel);

    $pessoa = usuarioDoKit($papel, "{$papel}@example.com");

    abrirComoPessoaNoOnboarding($pessoa, "/{$painel}/onboarding")
        ->assertForbidden()
        ->assertDontSeeLivewire(PrimeirosPassos::class);
})->with([
    'admin' => ['admin', 'admin'],
    'app'   => ['panel_user', 'app'],
    'infra' => ['infra', 'infra'],
]);

/* ---------------------------------------------------------------------------
| R15 / R16 / R26 — o que uma visita grava e a borda de 100 %
--------------------------------------------------------------------------- */

it('[CT-26] o passo "Configurações da aplicação" conclui pela visita à tela só com o tutorial ligado', function (string $estadoDoTutorial, string $tela, bool $concluido): void {
    $this->seed(OnboardingSeeder::class);
    $admin = usuarioDoKit('admin', 'admin@example.com');

    ligarTutorialGuiado($estadoDoTutorial === 'ligado');

    abrirComoPessoaNoOnboarding($admin, $tela)->assertOk();

    // Lido com o tutorial ligado, sem nova visita (P-07).
    ligarTutorialGuiado(true);
    $passo = checklistDoOnboarding('admin', $admin)->instance()->flowState()->step('configuracoes-da-aplicacao');

    expect($passo)->not->toBeNull()
        ->and($passo->isCompleted())->toBe($concluido);

    $linha = progressoDoPassoDaPessoa($admin, 'configuracoes-da-aplicacao');

    if ($concluido) {
        expect($linha)->not->toBeNull();
    } else {
        expect($linha)->toBeNull();
    }
})->with([
    'ligado, configurações'    => ['ligado', '/admin/configuracoes-da-aplicacao', true],
    'ligado, usuários'         => ['ligado', '/admin/users', false],
    'desligado, configurações' => ['desligado', '/admin/configuracoes-da-aplicacao', false],
]);

it('[CT-56] com todos os passos visíveis concluídos e um escondido pendente, a jornada está concluída', function (): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarDock(['app', 'infra']);

    $admin = usuarioDoKit('admin', 'admin@example.com');
    concluirPassosVisiveisDoAdminNoOnboarding($admin);

    $estado = checklistDoOnboarding('admin', $admin)->instance()->flowState();

    expect($estado->total())->toBe(10)
        ->and($estado->completedCount())->toBe(10)
        ->and($estado->isCompleted())->toBeTrue()
        ->and($estado->progress?->completed_at)->not->toBeNull();
});

it('[CT-60] o widget de progresso conta como concluída a jornada com um passo escondido pendente', function (): void {
    $this->seed(OnboardingSeeder::class);
    // "o tutorial ligado" (Dado do 04) — sem isto o widget some pelo `canView()` (P-02).
    ligarTutorialGuiado();
    ligarFeaturesDoOnboarding();
    ligarDock(['app', 'infra']);

    $admin = usuarioDoKit('admin', 'admin@example.com');
    concluirPassosVisiveisDoAdminNoOnboarding($admin);

    $texto = textoDoDashboardDoMaster();

    expect($texto)->toContain('Jornadas de onboarding concluídas')
        ->and($texto)->toMatch('~\b1\s*/\s*1\b~');
});

/* ---------------------------------------------------------------------------
| R26 (CT-54) — a persona padrão abre as telas dos seus passos
--------------------------------------------------------------------------- */

it('[CT-54] a persona padrão de cada painel abre a tela de todo passo visível do seu checklist, e passo de tela 403 não aparece', function (string $painel): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $pessoa    = pessoaDoPainelDoOnboarding($painel);
    $checklist = checklistDoOnboarding($painel, $pessoa);
    $visiveis  = $checklist->instance()->flowState()->steps->map(fn ($passo): string => $passo->key())->all();
    $jornada   = OnboardingFlow::query()->where('panel_id', $painel)->firstOrFail();
    $dashboard = parse_url(url("/{$painel}"), PHP_URL_PATH);

    $comTela = 0;

    foreach (OnboardingStep::query()->where('flow_id', $jornada->getKey())->orderBy('sort_order')->get() as $passo) {
        $primeira = $passo->resolveTourSteps()[0]['url'] ?? null;

        if ($primeira === null || rtrim((string) parse_url($primeira, PHP_URL_PATH), '/') === rtrim((string) $dashboard, '/')) {
            continue;
        }

        $status = abrirComoPessoaNoOnboarding($pessoa, $primeira)->getStatusCode();
        $comTela++;

        if (in_array($passo->key, $visiveis, true)) {
            expect($status)->not->toBeIn([403, 404], "o passo visível {$passo->key} leva a {$primeira} ({$status})");
            expect($status)->toBeLessThan(500, "o passo visível {$passo->key} leva a {$primeira} ({$status})");
        }

        if ($status === 403) {
            $this->assertNotContains($passo->key, $visiveis, "o passo {$passo->key} leva a 403 e está no checklist");
        }
    }

    expect($comTela)->toBeGreaterThan(0)->and($visiveis)->not->toBeEmpty();
})->with(['admin', 'app', 'infra']);

/* ---------------------------------------------------------------------------
| R8 — a ação do checklist montado, depois do desligar
--------------------------------------------------------------------------- */

/** Os verbos do checklist: nome, parâmetros e a situação de partida. */
function verbosDoChecklistDoOnboarding(): array
{
    return [
        'completeStep'        => ['completeStep', ['manual-pendente'], 'nenhuma'],
        'skipStep'            => ['skipStep', ['opcional'], 'nenhuma'],
        'undoStep'            => ['undoStep', ['concluido'], 'concluido'],
        'startTour'           => ['startTour', ['manual-pendente'], 'nenhuma'],
        'tourProgress'        => ['tourProgress', ['manual-pendente', 1, 3], 'nenhuma'],
        'finishTour'          => ['finishTour', ['manual-pendente'], 'nenhuma'],
        'openMedia'           => ['openMedia', ['com-imagem'], 'nenhuma'],
        'videoProgress'       => ['videoProgress', ['com-video', 30.0, 60.0], 'nenhuma'],
        'dismissFlow'         => ['dismissFlow', [], 'nenhuma'],
        'restoreFlow'         => ['restoreFlow', [], 'dispensada'],
        'restartFlow'         => ['restartFlow', [], 'concluido'],
        'selectFlow'          => ['selectFlow', ['jornada-b'], 'nenhuma'],
        'startOnboarding'     => ['startOnboarding', [], 'nenhuma'],
        'remindMeLater'       => ['remindMeLater', [], 'nenhuma'],
        'neverShowOnboarding' => ['neverShowOnboarding', [], 'nenhuma'],
        'showOnboardingAgain' => ['showOnboardingAgain', [], 'escondida'],
        'toggle'              => ['toggle', [], 'nenhuma'],
    ];
}

/** Leva a pessoa à situação de partida da linha, com o tutorial ligado. */
function prepararSituacaoDoChecklistDoOnboarding(User $pessoa, string $painel, string $situacao): void
{
    ligarTutorialGuiado(true);

    match ($situacao) {
        'concluido'  => concluirPassoDoOnboarding($pessoa, $painel, 'concluido'),
        'dispensada' => checklistDoOnboarding($painel, $pessoa)->call('dismissFlow'),
        'escondida'  => checklistDoOnboarding($painel, $pessoa)->call('neverShowOnboarding'),
        default      => null,
    };

    ligarTutorialGuiado(true);
}

it('[CT-13] a ação do checklist montado com o tutorial ligado é recusada depois do desligar', function (string $acao, array $parametros, string $situacao): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    jornadaDeTesteDoOnboarding('admin');
    segundaJornadaDeTesteDoOnboarding('admin');

    prepararSituacaoDoChecklistDoOnboarding($admin, 'admin', $situacao);

    $checklist = checklistDoOnboarding('admin', $admin);

    if ($situacao === 'dispensada') {
        $checklist->set('flowKey', 'jornada-de-teste');
    }

    $antes = fotografiaDoOnboarding();

    ligarTutorialGuiado(false);

    $checklist->call($acao, ...$parametros)->assertNotFound();

    // "O checklist continua na jornada em que estava": depois do 404 o componente não é
    // re-hidratado (o `Testable` perde o estado), então o observável é o 404 e as linhas iguais
    // — `selectFlow` e `restoreFlow` só mudam `flowKey` e a dispensa, e as duas estão na fotografia
    // ou recusadas antes de qualquer escrita.
    expect(fotografiaDoOnboarding())->toBe($antes);

    $checklist->assertNotDispatched('onboarding-tour-start')->assertNotDispatched('onboarding-media-open');
})->with(fn (): array => verbosDoChecklistDoOnboarding());

it('[CT-14] com o tutorial ligado, a mesma ação na mesma fixture produz o efeito', function (string $acao, array $parametros, string $situacao): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    jornadaDeTesteDoOnboarding('admin');
    segundaJornadaDeTesteDoOnboarding('admin');

    prepararSituacaoDoChecklistDoOnboarding($admin, 'admin', $situacao);

    $checklist = checklistDoOnboarding('admin', $admin);

    // Com duas jornadas, `restoreFlow()` sem chave age na corrente (a não dispensada): a dispensada é fixada.
    if ($situacao === 'dispensada') {
        $checklist->set('flowKey', 'jornada-de-teste');
    }

    $checklist->call($acao, ...$parametros);

    $jornada = OnboardingFlow::query()->where('key', 'jornada-de-teste')->firstOrFail();
    $meta    = static fn (string $passo): array => (array) json_decode((string) (progressoDoPassoDaPessoa($admin, $passo)?->meta ?? '[]'), true);
    $pref    = static fn (): ?object => DB::table('onboarding_preferences')->where('subject_id', (string) $admin->getKey())->first();
    $fluxo   = static fn (): ?object => DB::table('onboarding_flow_progress')->where('flow_id', $jornada->getKey())->where('subject_id', (string) $admin->getKey())->first();

    match ($acao) {
        'completeStep', 'finishTour' => expect(progressoDoPassoDaPessoa($admin, 'manual-pendente')?->completed_at)->not->toBeNull(),
        'skipStep'                   => expect(progressoDoPassoDaPessoa($admin, 'opcional')?->skipped_at)->not->toBeNull(),
        'undoStep'                   => expect(progressoDoPassoDaPessoa($admin, 'concluido')?->completed_at)->toBeNull(),
        'startTour'                  => $checklist->assertDispatched(
            'onboarding-tour-start',
            fn (string $evento, array $dados): bool => ($dados['key'] ?? null) === 'manual-pendente' && count($dados['steps'] ?? []) === 3,
        ),
        'tourProgress' => expect($meta('manual-pendente'))->toMatchArray(['tour_index' => 1, 'tour_total' => 3]),
        'openMedia'    => $checklist->assertDispatched(
            'onboarding-media-open',
            fn (string $evento, array $dados): bool => ($dados['media']['url'] ?? null) === 'https://exemplo.test/imagem.png',
        ),
        'videoProgress'       => expect($meta('com-video'))->toMatchArray(['video_seconds' => 30.0, 'video_duration' => 60.0]),
        'dismissFlow'         => expect($fluxo()?->dismissed_at)->not->toBeNull(),
        'restoreFlow'         => expect($fluxo()?->dismissed_at)->toBeNull(),
        'restartFlow'         => expect(progressoDoPassoDaPessoa($admin, 'concluido'))->toBeNull(),
        'selectFlow'          => expect($checklist->instance()->flowKey)->toBe('jornada-b')
            ->and($checklist->instance()->flowState()?->key())->toBe('jornada-b'),
        'startOnboarding'     => expect($pref()?->welcomed_at)->not->toBeNull(),
        'remindMeLater'       => expect(session()->get('filament-onboarding.welcome.later'))->toBeTrue(),
        'neverShowOnboarding' => expect($pref()?->hidden_at)->not->toBeNull(),
        'showOnboardingAgain' => expect($pref()?->hidden_at)->toBeNull(),
        'toggle'              => expect($checklist->get('isOpen'))->toBeTrue(),
    };
})->with(fn (): array => verbosDoChecklistDoOnboarding());

it('[CT-43] no /app e no /infra, a ação do checklist montado também é recusada depois do desligar', function (string $painel, string $acao): void {
    $pessoa = pessoaDoPainelDoOnboarding($painel);
    jornadaDeTesteDoOnboarding($painel);

    ligarTutorialGuiado(true);

    $checklist = checklistDoOnboarding($painel, $pessoa);
    $antes     = fotografiaDoOnboarding();

    ligarTutorialGuiado(false);

    $checklist->call($acao, 'manual-pendente')->assertNotFound();

    expect(fotografiaDoOnboarding())->toBe($antes);

    $checklist->assertNotDispatched('onboarding-tour-start');
})->with([
    'app, completeStep'   => ['app', 'completeStep'],
    'app, startTour'      => ['app', 'startTour'],
    'infra, completeStep' => ['infra', 'completeStep'],
    'infra, startTour'    => ['infra', 'startTour'],
]);

/* ---------------------------------------------------------------------------
| R11 — o que as ações do checklist alcançam
--------------------------------------------------------------------------- */

it('[CT-18] entrada fora do domínio no checklist do /admin não grava fora da jornada do /admin', function (string $entrada): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $admin = usuarioDoKit('admin', 'admin@example.com');
    $admin->assignRole('infra');

    $jornadaInfra = OnboardingFlow::query()->where('panel_id', 'infra')->firstOrFail();

    $pagina = $entrada === 'acao-da-pagina';

    if ($pagina) {
        // Destinatário: o progresso dela na jornada do /infra existe e precisa continuar.
        concluirPassoDoOnboarding($admin, 'infra', 'pulse');
    }

    $checklist = $pagina ? null : checklistDoOnboarding('admin', $admin);
    $antes     = fotografiaDoOnboarding();

    $chamar = function () use ($entrada, $checklist, $admin): void {
        match ($entrada) {
            'outro-painel'    => $checklist->call('completeStep', 'pulse', 'primeiros-passos-infra'),
            'passo-ausente'   => $checklist->call('completeStep', 'passo-que-nao-existe'),
            'selectFlow'      => $checklist->call('selectFlow', 'primeiros-passos-infra'),
            'indice-negativo' => $checklist->call('tourProgress', 'usuarios', -1, 0),
            'tipo-errado'     => $checklist->call('tourProgress', 'usuarios', 'um', 'três'),
            'acao-da-pagina'  => (function () use ($admin): void {
                fronteiraDeRequest();
                noPainelBootado('admin');
                test()->actingAs($admin);
                Livewire::test(PrimeirosPassos::class)
                    ->callAction('restartFlow', arguments: ['flow' => 'primeiros-passos-infra']);
            })(),
        };
    };

    try {
        $chamar();
    } catch (Throwable $erro) {
        // Tipo errado: recusado com erro de tipo, e o que importa é que nada foi gravado.
        expect($entrada)->toBe('tipo-errado');
    }

    expect(fotografiaDoOnboarding())->toBe($antes);

    if ($entrada === 'selectFlow') {
        expect($checklist->instance()->flowState()?->flow->panel_id)->toBe('admin');
    }

    if (! $pagina) {
        expect(DB::table('onboarding_step_progress')->where('flow_id', $jornadaInfra->getKey())->count())->toBe(0);
    }
})->with([
    'flowKey de outro painel'                 => ['outro-painel'],
    'stepKey fora da lista'                   => ['passo-ausente'],
    'selectFlow de outro painel'              => ['selectFlow'],
    'índice fora da faixa'                    => ['indice-negativo'],
    'tipo errado'                             => ['tipo-errado'],
    'ação da página com flow de outro painel' => ['acao-da-pagina'],
]);

it('[CT-19] o passo concluído por uma pessoa não conclui para a outra', function (): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $admin = usuarioDoKit('admin', 'admin@example.com');
    $bruno = usuarioDoKit('admin', 'bruno@example.com');

    checklistDoOnboarding('admin', $admin)->call('completeStep', 'usuarios');

    expect(checklistDoOnboarding('admin', $admin)->instance()->flowState()->step('usuarios')->isCompleted())->toBeTrue();

    $doBruno = checklistDoOnboarding('admin', $bruno)->instance()->flowState()->step('usuarios');

    expect($doBruno->isCompleted())->toBeFalse()
        ->and(progressoDoPassoDaPessoa($bruno, 'usuarios'))->toBeNull();
});

it('[CT-48] concluir um passo num painel não conclui o passo de mesmo nome em outro painel', function (string $painelDaAcao, string $outro): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $iris = usuarioDoKit('infra', 'iris@example.com');
    $iris->assignRole('panel_user');

    expect(checklistDoOnboarding($painelDaAcao, $iris)->instance()->flowState()->step('busca-global')->isCompleted())->toBeFalse();
    expect(checklistDoOnboarding($outro, $iris)->instance()->flowState()->step('busca-global')->isCompleted())->toBeFalse();

    checklistDoOnboarding($painelDaAcao, $iris)->call('completeStep', 'busca-global');

    expect(checklistDoOnboarding($painelDaAcao, $iris)->instance()->flowState()->step('busca-global')->isCompleted())->toBeTrue();

    $noOutro = checklistDoOnboarding($outro, $iris)->instance()->flowState()->step('busca-global');
    $jornada = OnboardingFlow::query()->where('panel_id', $outro)->firstOrFail();

    expect($noOutro->isCompleted())->toBeFalse()
        ->and(DB::table('onboarding_step_progress')->where('flow_id', $jornada->getKey())->where('subject_id', (string) $iris->getKey())->count())->toBe(0);
})->with([
    'infra para app' => ['infra', 'app'],
    'app para infra' => ['app', 'infra'],
]);

/* ---------------------------------------------------------------------------
| R28 — escrever a chave de outra jornada direto no checklist
--------------------------------------------------------------------------- */

it('[CT-66] a chave de uma jornada invisível escrita direto na propriedade do checklist não muda o checklist', function (string $chave, string $outra): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $admin = usuarioDoKit('admin', 'admin@example.com');
    $admin->assignRole('infra');

    $checklist = checklistDoOnboarding('admin', $admin);

    expect($checklist->instance()->flowState()->flow->panel_id)->toBe('admin');

    $checklist->set('flowKey', $chave);

    expect($checklist->instance()->flowState()?->flow->panel_id)->toBe('admin', 'o checklist deixou de mostrar a jornada do /admin');

    $checklist->call('completeStep', 'usuarios');

    $jornadaAdmin = OnboardingFlow::query()->where('panel_id', 'admin')->firstOrFail();
    $linha        = progressoDoPassoDaPessoa($admin, 'usuarios');

    expect($linha)->not->toBeNull()
        ->and($linha->flow_id)->toBe($jornadaAdmin->getKey())
        ->and(DB::table('onboarding_step_progress')->where('flow_id', '!=', $jornadaAdmin->getKey())->count())->toBe(0, "há progresso na jornada {$outra}");
})->with([
    'jornada do /infra'      => ['primeiros-passos-infra', 'do /infra'],
    'jornada do /app'        => ['primeiros-passos-app', 'do /app'],
    'chave que não existe'   => ['jornada-que-nao-existe', 'do /infra'],
]);

/* ---------------------------------------------------------------------------
| R30 — o passo do dashboard dinâmico segue a permissão da página
--------------------------------------------------------------------------- */

it('[CT-68] o passo do dashboard dinâmico segue a permissão da página do dashboard dinâmico do painel', function (string $painel, string $permissao): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $pessoa = pessoaDoPainelDoOnboarding($painel);
    $papel  = ['admin' => 'admin', 'infra' => 'infra'][$painel];

    // A permissão `Manage:Dashboard` é a da página do dinâmico: o controle a concede ao papel.
    Permission::findOrCreate('Manage:Dashboard', 'web');
    papelDoKit($papel)->givePermissionTo('Manage:Dashboard');

    if ($permissao === 'com a do infra, admin sem a do admin') {
        $pessoa->assignRole('admin');
        semAPermissao('admin', 'Manage:Dashboard');
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    noPainelDoShield($painel);

    $controle = visiveisNoChecklistDoOnboarding($painel, $pessoa);

    expect($controle)->toContain('dashboard-dinamico');

    if ($permissao === 'com a do infra, admin sem a do admin') {
        return;
    }

    if ($permissao === 'sem') {
        semAPermissao($papel, 'Manage:Dashboard');
        noPainelDoShield($painel);

        $depois = visiveisNoChecklistDoOnboarding($painel, $pessoa);

        expect($depois)->not->toContain('dashboard-dinamico')
            ->and(count($depois))->toBe(count($controle) - 1);
    }
})->with([
    'admin, com'                       => ['admin', 'com'],
    'admin, sem'                       => ['admin', 'sem'],
    'infra, com'                       => ['infra', 'com'],
    'infra, sem'                       => ['infra', 'sem'],
    'infra com, admin sem a do admin'  => ['infra', 'com a do infra, admin sem a do admin'],
]);

/* ---------------------------------------------------------------------------
| R33 — a página "Primeiros passos" com passo concluído
--------------------------------------------------------------------------- */

/** A abertura do `<article>` do passo na página (`wire:key` da jornada + passo), ou `null`. */
function azulejoDoPassoNaPagina(string $html, string $jornada, string $passo): ?string
{
    $chave = preg_quote("fio-tile-{$jornada}-{$passo}", '~');

    return preg_match('~<article\b[^>]*"'.$chave.'"[^>]*>~s', $html, $achado) === 1 ? $achado[0] : null;
}

it('[CT-72] a página "Primeiros passos" abre com um passo já concluído e o mostra concluído', function (string $painel, string $passo, string $modo, string $pendente, int $total): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $pessoa = pessoaDoPainelDoOnboarding($painel);

    match ($modo) {
        'visita'      => abrirComoPessoaNoOnboarding($pessoa, '/admin/configuracoes-da-aplicacao')->assertOk(),
        'tour'        => checklistDoOnboarding($painel, $pessoa)->call('finishTour', $passo),
        'convite'     => ofertaPara('convidado@example.com', null, 'panel_user', ['convidado_por_id' => $pessoa->getKey()]),
        'doisFatores' => doisFatoresPassadosNoOnboarding($pessoa, $painel),
    };

    $resposta = abrirComoPessoaNoOnboarding($pessoa, "/{$painel}/onboarding")
        ->assertOk()
        ->assertSeeLivewire(PrimeirosPassos::class);

    $html      = $resposta->getContent();
    $jornada   = "primeiros-passos-{$painel}";
    $concluido = azulejoDoPassoNaPagina($html, $jornada, $passo);
    $aberto    = azulejoDoPassoNaPagina($html, $jornada, $pendente);

    expect($concluido)->not->toBeNull("o passo {$passo} não está na página")
        ->and($aberto)->not->toBeNull("o passo {$pendente} não está na página");

    $this->assertStringContainsString('fio-tile--done', (string) $concluido, "o passo {$passo} não aparece concluído");
    $this->assertStringNotContainsString('fio-tile--done', (string) $aberto, "o passo {$pendente} aparece concluído");

    $contagem = e(__('filament-onboarding::onboarding.page.meta.completed', ['count' => 1, 'total' => $total]));

    $this->assertStringContainsString($contagem, $html, "a contagem não é \"{$contagem}\"");
})->with([
    'admin, visita'   => ['admin', 'configuracoes-da-aplicacao', 'visita', 'usuarios', 11],
    'admin, tour'     => ['admin', 'usuarios', 'tour', 'configuracoes-da-aplicacao', 11],
    'admin, condição' => ['admin', 'convites', 'convite', 'usuarios', 11],
    'app, tour'       => ['app', 'busca-global', 'tour', 'perfil-e-dois-fatores', 5],
    'app, condição'   => ['app', 'perfil-e-dois-fatores', 'doisFatores', 'busca-global', 5],
    'infra, tour'     => ['infra', 'pulse', 'tour', 'backups', 15],
    'infra, condição' => ['infra', 'perfil-e-dois-fatores', 'doisFatores', 'pulse', 15],
]);

/* ---------------------------------------------------------------------------
| R34 — o custo de abrir uma tela não cresce com os passos da mesma tela
--------------------------------------------------------------------------- */

/**
 * As consultas ao banco de um GET, e o HTML da resposta.
 *
 * @return array{0: int, 1: string}
 */
function consultasEHtmlDoGetNoOnboarding(User $pessoa, string $url): array
{
    $consultas = 0;
    $ativo     = true;

    DB::listen(function () use (&$consultas, &$ativo): void {
        if ($ativo) {
            $consultas++;
        }
    });

    try {
        $resposta = abrirComoPessoaNoOnboarding($pessoa, $url)->assertOk();
    } finally {
        $ativo = false;
    }

    return [$consultas, $resposta->getContent()];
}

it('[CT-73] dez passos a mais que ensinam a tela de Usuários não acrescentam consultas ao abrir uma tela do /admin', function (): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $admin = pessoaDoPainelDoOnboarding('admin');
    $tela  = '/admin/configuracoes-da-aplicacao';

    // O aquecimento: a primeira abertura carrega permissões e settings antes das duas medidas.
    abrirComoPessoaNoOnboarding($admin, $tela)->assertOk();

    $jornada = OnboardingFlow::query()->where('key', 'primeiros-passos-admin')->firstOrFail();

    foreach (range(1, 10) as $n) {
        OnboardingStep::query()->create([
            'flow_id'              => $jornada->getKey(),
            'key'                  => "extra-{$n}",
            'title'                => ['pt_BR' => "Passo extra {$n}", 'en' => "Extra step {$n}"],
            'sort_order'           => 500 + $n,
            'is_active'            => true,
            'is_required'          => true,
            'visibility_condition' => null,
        ]);
    }

    // Controle: os dez extras sem condição (um GET de aquecimento depois da mudança, para o cache de
    // definições do pacote, limpo pelo `saved`, não entrar na conta).
    abrirComoPessoaNoOnboarding($admin, $tela)->assertOk();
    [$controle] = consultasEHtmlDoGetNoOnboarding($admin, $tela);

    $extras = fn (?string $condicao) => OnboardingStep::query()->where('key', 'like', 'extra-%')->get()->each->update(['visibility_condition' => $condicao]);

    $extras('tela:usuarios');
    abrirComoPessoaNoOnboarding($admin, $tela)->assertOk();
    [$medida] = consultasEHtmlDoGetNoOnboarding($admin, $tela);

    /*
     * Controle de novo, na mesma distância: no processo do teste os render hooks de painel se
     * acumulam a cada request (o `PanelSwitch` pergunta `canAccessPanel()` mais vezes a cada GET),
     * e a contagem sobe uns poucos por request mesmo sem mudança nenhuma. A medida fica entre dois
     * controles equidistantes, e a deriva linear se cancela.
     */
    $extras(null);
    abrirComoPessoaNoOnboarding($admin, $tela)->assertOk();
    [$controleDepois] = consultasEHtmlDoGetNoOnboarding($admin, $tela);

    $esperado = intdiv($controle + $controleDepois, 2);

    expect($medida)->toBeLessThanOrEqual($esperado, "consultas: {$medida} com a condição, {$controle}/{$controleDepois} nos controles");

    $extras('tela:usuarios');

    // Anti-vácuo: a condição foi avaliada e deu "pode abrir" — os dez extras estão visíveis.
    $visiveis = visiveisNoChecklistDoOnboarding('admin', $admin);

    foreach (range(1, 10) as $n) {
        expect($visiveis)->toContain("extra-{$n}");
    }

});

it('[CT-74] a decisão de acesso à tela vale só para o request em que foi tomada', function (): void {
    $this->seed(OnboardingSeeder::class);
    ligarTutorialGuiado(true);

    $admin = pessoaDoPainelDoOnboarding('admin');

    abrirComoPessoaNoOnboarding($admin, '/admin')->assertOk();

    $antes = visiveisNoChecklistDoOnboarding('admin', $admin);

    expect($antes)->toContain('usuarios');

    semAPermissao('admin', 'ViewAny:User');
    noPainelDoShield('admin');

    abrirComoPessoaNoOnboarding($admin, '/admin')->assertOk();

    $depois = visiveisNoChecklistDoOnboarding('admin', $admin);

    expect($depois)->not->toContain('usuarios')
        ->and(count($depois))->toBe(count($antes) - 1);
});

/* ---------------------------------------------------------------------------
| R35 — valor recusado não forja linha no log do tutorial
--------------------------------------------------------------------------- */

it('[CT-75] uma chave de jornada com quebra de linha não cria linha nova no canal de log do tutorial', function (string $entrada, string $quebra): void {
    $this->seed(OnboardingSeeder::class);
    ligarFeaturesDoOnboarding();
    ligarTutorialGuiado(true);

    $arquivo = tempnam(sys_get_temp_dir(), 'onb-log-');

    try {
        // O canal real, com o formatter de linha do Laravel, gravando num arquivo do cenário.
        config(['logging.channels.onboarding' => [
            'driver'               => 'single',
            'path'                 => $arquivo,
            'level'                => 'debug',
            'replace_placeholders' => true,
        ]]);
        Log::forgetChannel('onboarding');
        Log::channel('onboarding')->info('SENTINELA do cenario');

        $admin     = pessoaDoPainelDoOnboarding('admin');
        $checklist = checklistDoOnboarding('admin', $admin);
        $valor     = 'qa-gate'.$quebra.'[2026-10-21 23:59:59] local.CRITICAL: linha forjada';

        if ($entrada === 'propriedade') {
            $checklist->set('flowKey', $valor);
        } else {
            $checklist->call('selectFlow', $valor);
        }

        expect($checklist->instance()->flowState()?->flow->panel_id)->toBe('admin', 'o checklist deixou de mostrar a jornada do /admin');

        $linhas = preg_split('/\R/', (string) file_get_contents($arquivo));
        $indice = null;

        foreach ($linhas as $posicao => $linha) {
            if (str_contains($linha, 'SENTINELA do cenario')) {
                $indice = $posicao;
            }
        }

        expect($indice)->not->toBeNull('a sentinela não está no arquivo: o teste não lê o canal');

        $novas = array_filter(
            array_slice($linhas, $indice + 1),
            fn (string $linha): bool => preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $linha) === 1,
        );

        expect(count($novas))->toBeLessThanOrEqual(1, 'mais de uma entrada nova: '.implode(' || ', $novas));

        foreach ($linhas as $linha) {
            $this->assertStringStartsNotWith('[2026-10-21 23:59:59] local.CRITICAL', $linha, 'linha forjada no canal');
        }
    } finally {
        Log::forgetChannel('onboarding');
        @unlink($arquivo);
    }
})->with([
    'propriedade, LF'   => ['propriedade', "\n"],
    'propriedade, CRLF' => ['propriedade', "\r\n"],
    'troca de aba, LF'  => ['selectFlow', "\n"],
]);

/** O segundo fator confirmado no painel da pessoa e já vencido na sessão (sem o desafio de 302 no GET). */
function doisFatoresPassadosNoOnboarding(User $pessoa, string $painel): void
{
    Filament::setCurrentPanel($painel);
    $pessoa->enableTwoFactorAuthentication();
    $pessoa->breezySession->confirm();

    test()->withSession(['breezy_session_id' => md5((string) $pessoa->breezySession->id)]);
}
