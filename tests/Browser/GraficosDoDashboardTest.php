<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Facades\DB;

/**
 * CT-B01 — os gráficos do dashboard são desenhados de fato.
 *
 * Por que navegador: um gráfico ApexCharts **não existe no HTML** que o servidor devolve. O
 * componente Livewire entrega um `<div>` vazio e um array de opções; quem desenha o `<svg>` é o
 * JavaScript. Um teste de componente prova que os DADOS chegaram — e continua verde com o plugin
 * não registrado no painel, que é o modo de falha mais provável da entrega.
 *
 * Os três num cenário só, de propósito: um boot de navegador serve aos três, e assim o caso
 * ainda prova que eles **convivem** na mesma página — erro de JS de um widget derruba o Alpine
 * dos demais, e nenhum teste de componente enxerga isso.
 *
 * Ver `wikis/specs/main/graficos-com-apexcharts/05-casos-de-teste-browser.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    // Dado semeado nas duas fontes: dashboard com tudo zerado desenha gráficos vazios, e
    // gráfico vazio não distingue "desenhou" de "não desenhou".
    AiRun::create([
        'tenant_id' => '1', 'task' => 'teste', 'driver' => 'openai',
        'modality'  => 'text', 'status' => 'success',
    ]);

    DB::table('queue_monitors')->insert([
        'job_id'      => '1',
        'name'        => 'App\\Jobs\\Qualquer',
        'queue'       => 'default',
        'started_at'  => now(),
        'finished_at' => now(),
        'failed'      => false,
        'attempt'     => 1,
        'progress'    => 100,
    ]);
});

it('desenha os gráficos do painel de infraestrutura', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    visit('/infra')
        /*
         * Janela alta de propósito. Os widgets do Filament carregam ADIADO, e o gatilho é a
         * entrada em viewport: com a janela padrão os gráficos ficam abaixo da dobra, nunca são
         * carregados, e o cenário falharia dizendo que o elemento não existe — quando o que
         * aconteceu é que ele nem chegou a ser pedido. Medido: `/infra` renderiza só 3 widgets
         * na resposta inicial.
         */
        ->resize(1440, 4000)
        // O `id` sai do `$chartId` que o kit declara — a coisa mais próxima de um `data-testid`
        // que a entrega tem. O `<svg>` dentro dele é o que só o navegador prova: afirmar apenas
        // o título seria falso ✅, porque o título é HTML do servidor e aparece com o ApexCharts
        // completamente quebrado.
        ->assertPresent('#iaExecucoesPorDia svg.apexcharts-svg')
        ->assertPresent('#iaExecucoesPorStatus svg.apexcharts-svg')
        ->assertPresent('#filasTaxaDeSucesso svg.apexcharts-svg')
        ->assertNoJavaScriptErrors();
});
