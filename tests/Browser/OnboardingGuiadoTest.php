<?php

use Database\Seeders\OnboardingSeeder;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Feature `onboarding-guiado`: o tour do pacote abre no navegador a partir do checklist flutuante,
 * no /admin e no /infra (RQ-28, RQ-18). O backend prova o despacho do evento; só o JS prova o popover.
 */

/**
 * Título (pt_BR) da jornada e da primeira parada do passo, lidos do banco semeado — nunca à mão.
 *
 * @return array{jornada: string, parada: string}
 */
function titulosDoTourSemeado(string $painel, string $passo): array
{
    $jornada = DB::table('onboarding_flows')->where('key', "primeiros-passos-{$painel}")->first();
    $step    = DB::table('onboarding_steps')->where('flow_id', $jornada->id)->where('key', $passo)->first();

    $traduzido = static fn (mixed $valor): string => is_string($valor) && str_starts_with($valor, '{')
        ? json_decode($valor, true, flags: JSON_THROW_ON_ERROR)['pt_BR']
        : (string) $valor;

    $paradas = json_decode($step->tour_steps, true, flags: JSON_THROW_ON_ERROR);
    $titulo  = $paradas[0]['title'];

    return [
        'jornada' => $traduzido($jornada->title),
        'parada'  => is_array($titulo) ? $titulo['pt_BR'] : $traduzido($titulo),
    ];
}

it('[CT-B01] o tour do passo de Usuários abre no navegador a partir do checklist do /admin', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, OnboardingSeeder::class]);
    ligarTutorialGuiado();
    $administradora = pessoaDoPainelDoOnboarding('admin');
    $titulos        = titulosDoTourSemeado('admin', 'usuarios');

    $this->actingAs($administradora);
    $this->get('/admin');

    $pagina = visit('/admin')->on()->desktop()->inLightMode();
    $pagina->assertPathIs('/admin')
        ->click('button[aria-label="'.$titulos['jornada'].'"]')
        ->assertVisible('[role="dialog"][aria-label="'.$titulos['jornada'].'"]')
        ->click('[wire\:click*="startTour"][wire\:click*="usuarios"]')
        ->assertPathIs(parse_url(route('filament.admin.resources.users.index'), PHP_URL_PATH))
        ->assertVisible('[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]')
        ->assertSeeIn('[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]', $titulos['parada'])
        ->assertNoJavaScriptErrors();
});

it('[CT-B02] o tour do passo de Pulse abre no navegador a partir do checklist do /infra', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, OnboardingSeeder::class]);
    ligarTutorialGuiado();
    $iris    = pessoaDoPainelDoOnboarding('infra');
    $titulos = titulosDoTourSemeado('infra', 'pulse');

    $this->actingAs($iris);
    $this->get('/infra');

    $pagina = visit('/infra')->on()->desktop()->inLightMode();
    $pagina->assertPathIs('/infra')
        ->click('button[aria-label="'.$titulos['jornada'].'"]')
        ->assertVisible('[role="dialog"][aria-label="'.$titulos['jornada'].'"]')
        ->click('[wire\:click*="startTour"][wire\:click*="pulse"]')
        ->assertPathIs(parse_url(route('filament.infra.pages.pulse'), PHP_URL_PATH))
        ->assertVisible('[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]')
        ->assertSeeIn('[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]', $titulos['parada'])
        ->assertNoJavaScriptErrors();
});

/**
 * Linhas do tutorial da pessoa (progresso de passos, de jornada e preferências), na íntegra.
 *
 * @return array<string, array<int, array<string, mixed>>>
 */
function linhasDoTutorialDa(object $pessoa): array
{
    $linhas = [];

    foreach (['onboarding_step_progress', 'onboarding_flow_progress', 'onboarding_preferences'] as $tabela) {
        $linhas[$tabela] = DB::table($tabela)
            ->where('subject_id', (string) $pessoa->getKey())
            ->orderBy('id')
            ->get()
            ->map(static fn (object $linha): array => (array) $linha)
            ->all();
    }

    return $linhas;
}

dataset('onboarding-contraste', [
    '/admin claro, sem passo concluído'                         => ['admin', 'admin', false, null],
    '/admin escuro, sem passo concluído'                        => ['admin', 'admin', true, null],
    '/infra claro, sem passo concluído'                         => ['infra', 'infra', false, null],
    '/infra escuro, sem passo concluído'                        => ['infra', 'infra', true, null],
    '/admin claro, com "Configurações da aplicação" concluído'  => ['admin', 'admin', false, 'configuracoes-da-aplicacao'],
    '/admin escuro, com "Configurações da aplicação" concluído' => ['admin', 'admin', true, 'configuracoes-da-aplicacao'],
    '/infra claro, com "Perfil e 2FA" concluído'                => ['infra', 'infra', false, 'perfil-e-dois-fatores'],
    '/infra escuro, com "Perfil e 2FA" concluído'               => ['infra', 'infra', true, 'perfil-e-dois-fatores'],
]);

it('[CT-B03] a página "Primeiros passos" com o checklist aberto não tem problema sério de acessibilidade', function (string $painel, string $persona, bool $escuro, ?string $passoConcluido): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, OnboardingSeeder::class]);
    ligarTutorialGuiado();
    $pessoa  = pessoaDoPainelDoOnboarding($persona);
    $titulos = titulosDoTourSemeado($painel, $painel === 'admin' ? 'usuarios' : 'pulse');
    $rotulo  = '.fi-main .fio-tile--done .fio-tile-status';

    $this->actingAs($pessoa);
    $this->get("/{$painel}/onboarding");

    if ($passoConcluido === 'configuracoes-da-aplicacao') {
        $this->get('/admin/configuracoes-da-aplicacao')->assertOk();
    }

    if ($passoConcluido === 'perfil-e-dois-fatores') {
        ativarDoisFatoresDaPessoa($pessoa);
        $this->withSession(['breezy_session_id' => md5((string) $pessoa->breezySession->id)]);
    }

    if ($passoConcluido !== null) {
        fronteiraDeRequest();
    }

    $pagina = visit("/{$painel}/onboarding")->on()->desktop();
    $pagina = $escuro ? $pagina->inDarkMode() : $pagina->inLightMode();

    $pagina->assertPathIs("/{$painel}/onboarding")
        ->assertScript("document.documentElement.classList.contains('dark')", $escuro)
        ->assertSeeIn('.fi-main .fi-header-heading', $titulos['jornada']);

    if ($passoConcluido === null) {
        $pagina->assertNotPresent('.fi-main .fio-tile--done');
    }

    $pagina->click('button[aria-label="'.$titulos['jornada'].'"]')
        ->assertVisible('[role="dialog"][aria-label="'.$titulos['jornada'].'"]');

    if ($passoConcluido !== null) {
        $pagina->assertVisible($rotulo)
            ->assertScript("(() => { const r = document.querySelector('{$rotulo}'); r.scrollIntoView({block: 'center'}); const c = r.getBoundingClientRect(); return r.contains(document.elementFromPoint(c.left + c.width / 2, c.top + c.height / 2)); })()", true);
    }

    $pagina->assertNoAccessibilityIssues();
})->with('onboarding-contraste');

it('[CT-B04] a tecla Esc fecha o checklist aberto, sem concluir nem dispensar nada', function (string $foco, int $teclas): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, OnboardingSeeder::class]);
    ligarTutorialGuiado();
    $administradora = pessoaDoPainelDoOnboarding('admin');
    $titulo         = titulosDoTourSemeado('admin', 'usuarios')['jornada'];
    $botao          = 'button[aria-label="'.$titulo.'"]';
    $painel         = '[role="dialog"][aria-label="'.$titulo.'"]';
    $iniciarTour    = '[wire\:click*="startTour"][wire\:click*="usuarios"]';
    $seletorDoFoco  = $foco === 'botao' ? $botao : $painel.' '.$iniciarTour;

    $this->actingAs($administradora);
    $this->get('/admin');

    $pagina = visit('/admin')->on()->desktop()->inLightMode();
    $pagina->assertPathIs('/admin')
        ->keys($botao, 'Enter')
        ->assertVisible($painel);

    $antes = linhasDoTutorialDa($administradora);

    if ($teclas === 1) {
        $pagina->keys($seletorDoFoco, 'Escape');
    } else {
        // O segundo Esc 5 ms depois do primeiro, com a chamada Livewire ainda em voo (QA-20: reabre com a resposta ainda
        // por voltar). `keys()` espera a resposta entre as teclas, as duas no mesmo tick o Livewire coalesce e o
        // `networkidle` não espera nada numa página já carregada: o script só devolve depois de 1,5 s sem
        // chamada nova a `/livewire` (espera por estado, não por tempo).
        $pagina->script('(async () => {
            const esc = () => window.dispatchEvent(new KeyboardEvent("keydown", {key: "Escape", bubbles: true}));
            const chamadas = () => performance.getEntriesByType("resource").filter((e) => e.name.includes("/livewire")).length;
            document.querySelector('.json_encode($seletorDoFoco).').focus();
            esc();
            await new Promise((r) => setTimeout(r, 5));
            esc();
            let ultima = chamadas(), desde = Date.now();
            while (Date.now() - desde < 1500) {
                await new Promise((r) => setTimeout(r, 50));
                if (chamadas() !== ultima) { ultima = chamadas(); desde = Date.now(); }
            }
            return true;
        })()');
    }

    $pagina->waitForEvent('networkidle')
        ->assertMissing($painel)
        ->assertVisible($botao)
        ->assertMissing('[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]')
        ->click($botao)
        ->assertVisible($painel.' '.$iniciarTour);

    expect(linhasDoTutorialDa($administradora))->toBe($antes);

    $pagina->refresh()
        ->assertPathIs('/admin')
        ->assertVisible($botao);
})->with([
    'foco no botão do checklist'              => ['botao', 1],
    'foco dentro do painel, no botão do tour' => ['painel', 1],
    'foco no botão, Esc duas vezes seguidas'  => ['botao', 2],
]);
