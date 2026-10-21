<?php

use App\Filament\Pages\PrimeirosPassos;
use App\Models\User;
use Database\Seeders\OnboardingSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
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

    foreach (OnboardingFlow::query()->where('panel_id', '!=', $painel)->get() as $outra) {
        $this->assertStringNotContainsString($outra->key, $html, "a jornada de /{$outra->panel_id} está no corpo");
    }

    $flows = Onboarding::for($pessoa)->flows($painel);

    expect($flows)->toHaveCount(1)
        ->and($flows->first()->flow->panel_id)->toBe($painel);

    // `@premissa P-11`: a contagem do `/app` depende de quais telas o `panel_user` abre; o 04
    // afirma 6 e a P-11 manda sumir o passo de tela que o papel não abre. Invariante: no máximo
    // os 6 do 04, e ao menos um.
    if ($painel === 'app') {
        expect($flows->first()->total())->toBeLessThanOrEqual($passos)->toBeGreaterThan(0);

        return;
    }

    expect($flows->first()->total())->toBe($passos);
})->with([
    'admin' => ['admin', 11],
    'app'   => ['app', 6],
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
            expect($visiveis)->not->toContain($passo->key, "o passo {$passo->key} leva a 403 e está no checklist");
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
