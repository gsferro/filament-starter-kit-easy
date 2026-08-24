<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Facades\DB;

/**
 * O dashboard continua sendo DESENHADO quando parte dos widgets é ocultada pela permissão.
 *
 * ## Por que navegador, quando tudo o mais desta feature ficou no `04`
 *
 * 403, item de menu ausente, cartão de hub que desaparece, Action oculta e Action recusada são todos
 * falsificáveis por componente Livewire, e é lá que estão. Sobra **uma** afirmação que só o navegador
 * prova: a grade do Filament monta `columnSpan` no cliente e os gráficos ApexCharts são `<svg>`
 * produzido por JavaScript — nada disso existe no HTML que o servidor devolve.
 *
 * Isto não é hipótese genérica. `tests/Browser/GraficosDoDashboardTest.php:16-18` registra que erro
 * de JS de UM widget derruba o Alpine dos demais, e que nenhum teste de componente enxerga isso. Esta
 * feature é a primeira do kit capaz de produzir um dashboard com SUBCONJUNTO de widgets — antes,
 * todo papel de painel via todos.
 *
 * Ver `.../05-casos-de-teste-browser.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    // Dado semeado nas duas fontes: dashboard zerado desenha gráfico vazio, e gráfico vazio não
    // distingue "desenhou" de "não desenhou".
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

    $alvo = User::factory()->create(['email' => 'alvo-do-widget@example.com']);

    DB::table('authentication_log')->insert([
        'authenticatable_type' => $alvo->getMorphClass(),
        'authenticatable_id'   => $alvo->getKey(),
        'ip_address'           => '203.0.113.7',
        'user_agent'           => 'Teste',
        'login_at'             => now(),
        'login_successful'     => true,
    ]);
});

/**
 * CT-B01 — a grade do /infra sobrevive à ocultação de um widget por permissão.
 *
 * Os três `assertPresent` são o oráculo do cenário: o `<svg>` do ApexCharts é o que só o navegador
 * prova, e afirmar apenas o título do gráfico seria falso ✅ — o título é HTML do servidor e aparece
 * com o ApexCharts completamente quebrado.
 *
 * `assertNoJavaScriptErrors()` e não `assertNoSmoke()`: a página tem widget de plugin de terceiro, e
 * o `assertNoSmoke()` reprova por `console.log` alheio.
 */
it('desenha a grade do painel de infraestrutura com um widget ocultado pela permissão', function (): void {
    semAPermissao('infra', 'View:UltimosAcessos');

    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));

    visit('/infra')
        /*
         * Janela alta de propósito. Os widgets do Filament carregam ADIADO e o gatilho é a entrada em
         * viewport: com a janela padrão os gráficos ficam abaixo da dobra, nunca são pedidos, e o
         * cenário falharia dizendo que o elemento não existe — quando o que aconteceu é que ele nem
         * chegou a ser pedido. Medido em `GraficosDoDashboardTest.php:48-55`.
         */
        ->resize(1440, 4000)
        ->assertPresent('#iaExecucoesPorDia svg.apexcharts-svg')
        ->assertPresent('#iaExecucoesPorStatus svg.apexcharts-svg')
        ->assertPresent('#filasTaxaDeSucesso svg.apexcharts-svg')
        // O widget revogado não vazou o dado dele — nem no HTML inicial, nem depois do carregamento
        // adiado, que é a diferença entre este cenário e um teste de componente.
        ->assertDontSee('203.0.113.7')
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B02 — o painel de administração continua utilizável com a MAIORIA dos widgets ocultada.
 *
 * A partição oposta de CT-B01: lá um widget de 16 é ocultado, aqui sobra um de sete. É o **erro
 * visível** desta feature — grade vazia ou colapsada —, e ele só existe depois do JavaScript.
 *
 * A asserção do valor, e não só do rótulo: `assertSee('Usuários')` sozinho é texto que aparece com a
 * grade inteira vazia, e é o falso ✅ que o `04` proíbe. "Contas cadastradas no sistema" é a descrição
 * do StatPlus que só existe se o card foi renderizado.
 */
it('desenha a grade do painel de administração com a maioria dos widgets ocultada', function (): void {
    semAPermissao(
        'admin',
        'View:UsuariosPorPapel',
        'View:UltimosUsuariosCadastrados',
        'View:ConvitesPorSituacao',
        'View:AgentesIaStats',
        'View:AgentesIaPorProvider',
        'View:ProgressoOnboarding',
    );

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    visit('/admin')
        ->resize(1440, 4000)
        // O único widget que sobrou renderizou de fato.
        ->assertSee('Contas cadastradas no sistema')
        // E o revogado não vazou o e-mail que ele lista.
        ->assertDontSee('alvo-do-widget@example.com')
        ->assertNoJavaScriptErrors();
});
