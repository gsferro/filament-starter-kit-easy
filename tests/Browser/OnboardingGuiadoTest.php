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
