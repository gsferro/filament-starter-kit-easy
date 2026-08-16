<?php

use App\Filament\Admin\Widgets\ConvitesPorSituacao;
use App\Filament\Admin\Widgets\UsuariosVisaoGeralStats;
use App\Filament\Infra\Widgets\FilasStats;
use App\Filament\Infra\Widgets\FilasTaxaDeSucesso;
use App\Filament\Infra\Widgets\IaExecucoesPorDia;
use App\Filament\Infra\Widgets\IaExecucoesPorStatus;
use App\Models\Convite;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Os gráficos do dashboard (leandrocfe/filament-apex-charts).
 *
 * O que estes casos medem é o DADO, na camada barata: o widget é um componente Livewire cuja
 * propriedade pública `$options` carrega a série que o ApexCharts vai desenhar. Que o desenho
 * apareça na tela é assunto do CT-B, e só o navegador prova.
 *
 * Ver `wikis/specs/main/graficos-com-apexcharts/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    // Um usuário autenticado, porque widget é componente de painel; e UM papel, porque a
    // `ConviteFactory` resolve `role_id` a partir do primeiro papel existente. Papel criado à
    // mão em vez dos dois seeders do Shield: eles custam segundos por caso, e nenhum caso deste
    // arquivo é sobre autorização — o único que é (CT-10) semeia por conta própria.
    Role::create(['name' => 'papel-de-teste', 'guard_name' => 'web', 'painel' => 'app']);

    $this->actingAs(User::factory()->create());
});

/** As opções que o widget entrega ao ApexCharts. */
function opcoesDoGrafico(string $widget): array
{
    return Livewire::test($widget)->get('options') ?? [];
}

/*
|--------------------------------------------------------------------------
| R1 — a rosca de convites reflete a situação derivada pelo model
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — cada situação é contada na fatia dela.
 *
 * Partição EXAUSTIVA do enum de situação, e não amostra: cobrir "Aceito" e "Pendente" e deixar
 * "Expirado" de fora permite exatamente o defeito que importa — a rosca dizer que a maioria
 * dos convites foi aceita quando metade expirou.
 */
it('conta cada convite na fatia da situação dele', function (string $situacao, array $atributos): void {
    Convite::factory()->create([...$atributos, 'email' => 'alvo@example.com']);

    $opcoes = opcoesDoGrafico(ConvitesPorSituacao::class);

    $indice = array_search($situacao, $opcoes['labels'], true);

    expect($opcoes['series'][$indice])->toBe(1);
})->with([
    'aceito'   => ['Aceito', ['aceito_em' => '2026-08-01 10:00:00']],
    'recusado' => ['Recusado', ['recusado_em' => '2026-08-01 10:00:00']],
    'expirado' => ['Expirado', ['expira_em' => '2026-08-01 10:00:00']],
    'pendente' => ['Pendente', ['expira_em' => '2099-01-01 10:00:00']],
]);

/**
 * CT-03 — o cenário discriminante da regra: ACEITO VENCE EXPIRADO.
 *
 * Uma implementação que reescrevesse a derivação em SQL — `whereNotNull('aceito_em')` para
 * aceito e `where('expira_em','<',now())` para expirado — passa em todo o CT-01 e erra
 * exatamente aqui, contando o mesmo convite duas vezes ou classificando como expirado um
 * convite que já virou conta. A precedência está escrita em `Convite::situacao()` e em nenhum
 * outro lugar.
 */
it('mantém como aceito o convite cujo prazo venceu depois do aceite', function (): void {
    Convite::factory()->create([
        'email'     => 'alvo@example.com',
        'aceito_em' => '2026-08-01 10:00:00',
        'expira_em' => '2026-08-02 10:00:00',
    ]);

    $opcoes = opcoesDoGrafico(ConvitesPorSituacao::class);

    expect($opcoes['series'][array_search('Aceito', $opcoes['labels'], true)])->toBe(1)
        ->and($opcoes['series'][array_search('Expirado', $opcoes['labels'], true)])->toBe(0);
});

/**
 * CT-02 — base vazia devolve as quatro fatias zeradas, não uma série vazia.
 *
 * `series: []` faz o ApexCharts desenhar um canvas em branco, sem legenda e sem explicação — e
 * esse é o estado de TODA instalação nova, não um caso de borda exótico.
 */
it('desenha as quatro fatias zeradas quando não há convite nenhum', function (): void {
    $opcoes = opcoesDoGrafico(ConvitesPorSituacao::class);

    expect($opcoes['labels'])->toHaveCount(4)
        ->and($opcoes['series'])->toBe([0, 0, 0, 0]);
});

/*
|--------------------------------------------------------------------------
| R2 — a taxa de sucesso das filas conta só o que terminou
|--------------------------------------------------------------------------
*/

/** Uma linha em `queue_monitors` no estado pedido. */
function job(bool $falhou, bool $terminou): void
{
    DB::table('queue_monitors')->insert([
        'job_id'      => (string) fake()->unique()->randomNumber(6),
        'name'        => 'App\\Jobs\\Qualquer',
        'queue'       => 'default',
        'started_at'  => now(),
        'finished_at' => $terminou ? now() : null,
        'failed'      => $falhou,
        'attempt'     => 1,
        'progress'    => 100,
    ]);
}

/**
 * CT-04 — job em andamento não pesa na taxa.
 *
 * Os números são discriminantes de propósito: com 3 concluídos, 1 falhado e 6 em andamento, a
 * taxa é 75% se o denominador for só o que terminou e 30% se os em andamento entrarem. Um
 * cenário sem jobs em andamento daria 75% nas duas implementações — seria decorativo.
 */
it('calcula a taxa de sucesso ignorando os jobs em andamento', function (): void {
    job(falhou: false, terminou: true);
    job(falhou: false, terminou: true);
    job(falhou: false, terminou: true);
    job(falhou: true, terminou: true);

    for ($i = 0; $i < 6; $i++) {
        job(falhou: false, terminou: false);
    }

    expect(opcoesDoGrafico(FilasTaxaDeSucesso::class)['series'])->toBe([75]);
});

/**
 * CT-06 — falha é a coluna `failed`, não a ausência de término.
 *
 * O job falhado deste caso TEM `finished_at`: é isso que distingue as duas implementações. Com
 * falhados sempre sem `finished_at` — o caso mais comum — as duas dariam o mesmo número.
 */
it('conta a falha pela coluna failed, e não pela ausência de término', function (): void {
    job(falhou: false, terminou: true);
    job(falhou: true, terminou: true);

    expect(opcoesDoGrafico(FilasTaxaDeSucesso::class)['series'])->toBe([50]);
});

/**
 * CT-07 — o arredondamento é para o inteiro mais próximo.
 *
 * 1 de 3 é 33,33…: sem esta linha, truncar e arredondar passam igual.
 */
it('arredonda a taxa de sucesso para o inteiro mais próximo', function (): void {
    job(falhou: false, terminou: true);
    job(falhou: true, terminou: true);
    job(falhou: true, terminou: true);

    expect(opcoesDoGrafico(FilasTaxaDeSucesso::class)['series'])->toBe([33]);
});

/**
 * CT-05 — base zero devolve 0%, e não estoura nem mente.
 *
 * Duas coisas ao mesmo tempo: sem a guarda a divisão por zero derrubaria o DASHBOARD INTEIRO
 * (widget que estoura leva a página junto), e devolver 100% — "nada falhou" — seria otimismo
 * falso, o pior resultado possível para um indicador de saúde.
 */
it('devolve zero por cento quando nenhum job terminou', function (): void {
    job(falhou: false, terminou: false);
    job(falhou: false, terminou: false);

    expect(opcoesDoGrafico(FilasTaxaDeSucesso::class)['series'])->toBe([0]);
});

/*
|--------------------------------------------------------------------------
| R4 — a rosca de status de IA inclui os status desconhecidos
|--------------------------------------------------------------------------
*/

/**
 * CT-09 — status que o kit não conhece aparece na rosca, em vez de sumir ou estourar.
 *
 * O mapa de cor por status é uma lista fechada escrita à mão, e o pacote de IA pode ganhar
 * estados novos a qualquer upgrade. A implementação natural — `$mapa[$status]` direto ou
 * `match` sem `default` — estoura com `Undefined array key` e derruba o dashboard.
 */
it('inclui na rosca o status de IA que o kit não conhece', function (): void {
    AiRun::create(['tenant_id' => '1', 'task' => 't', 'driver' => 'openai', 'modality' => 'text', 'status' => 'success']);
    AiRun::create(['tenant_id' => '1', 'task' => 't', 'driver' => 'openai', 'modality' => 'text', 'status' => 'success']);
    AiRun::create(['tenant_id' => '1', 'task' => 't', 'driver' => 'openai', 'modality' => 'text', 'status' => 'cancelado_pelo_usuario']);

    $opcoes = opcoesDoGrafico(IaExecucoesPorStatus::class);

    expect($opcoes['labels'])->toHaveCount(2)
        ->and($opcoes['series'][array_search('cancelado_pelo_usuario', $opcoes['labels'], true)])->toBe(1);
});

/*
|--------------------------------------------------------------------------
| R5 — o gráfico migrado preserva a série diária
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — dia sem execução aparece como zero, não como buraco.
 *
 * É a decisão preservada da versão anterior do widget: uma migração que passasse o resultado
 * da consulta direto ao ApexCharts produziria UM ponto, desenharia sem erro, e mentiria sobre
 * a operação — dois dias parados virariam um trecho reto.
 */
it('mantém um ponto por dia do calendário no gráfico de execuções de IA', function (): void {
    AiRun::create([
        'tenant_id' => '1', 'task' => 't', 'driver' => 'openai', 'modality' => 'text',
        'status'    => 'success', 'created_at' => Carbon::today(),
    ]);

    $opcoes = opcoesDoGrafico(IaExecucoesPorDia::class);
    $serie  = $opcoes['series'][0]['data'];

    expect($serie)->toHaveCount(14)
        ->and(end($serie))->toBe(1)
        ->and(array_sum($serie))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| R6 — proteção de tabela ausente e polling declarado
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — o dashboard abre mesmo sem as tabelas opcionais.
 *
 * Sem `canView()`, uma instalação sem o pacote de filas não perde um card: perde a PÁGINA
 * inteira, porque a exceção do widget sobe pelo dashboard.
 */
it('abre o dashboard de infra sem a tabela de monitoramento de filas', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    DB::statement('DROP TABLE queue_monitors');

    expect(FilasTaxaDeSucesso::canView())->toBeFalse();

    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'))
        ->get('/infra')
        ->assertSuccessful();
});

/**
 * CT-11 — nenhum gráfico do kit herda o intervalo de atualização padrão do pacote.
 *
 * O default é 5 SEGUNDOS, por widget e por aba aberta: uma aba esquecida gera dezenas de
 * consultas agregadas por minuto, indefinidamente, sem ninguém olhando.
 *
 * A lista é DERIVADA dos widgets registrados, e não escrita à mão: assim o caso cobre também o
 * gráfico que alguém criar amanhã — que é exatamente quem vai esquecer a declaração.
 */
it('declara o intervalo de atualização em todo gráfico do kit', function (string $painel): void {
    $graficos = collect(Filament::getPanel($painel)->getWidgets())
        ->filter(fn (string $widget): bool => is_a($widget, ApexChartWidget::class, true));

    expect($graficos)->not->toBeEmpty();

    $graficos->each(function (string $widget): void {
        /*
         * A propriedade é `protected` em `Filament\Widgets\Concerns\CanPoll` (e o getter
         * também), então a leitura é por reflexão. Ler o VALOR e não chamar o getter é
         * deliberado: o que se quer provar é que a classe DECLAROU o intervalo, não que algum
         * override devolveu outra coisa em runtime.
         */
        $propriedade = new ReflectionProperty($widget, 'pollingInterval');

        expect($propriedade->getDefaultValue())->not->toBe('5s');
    });
})->with(['admin' => 'admin', 'infra' => 'infra']);

/**
 * Guarda de fronteira: os OUTROS dois pacotes de widget continuam donos do que é deles.
 *
 * A regra do kit é por tipo de desenho — gráfico é ApexCharts, stat card é StatPlus, o resto é
 * dashboard-widgets. Este caso fixa a metade que um refactor entusiasmado desfaria primeiro:
 * transformar stat card em gráfico.
 */
it('mantém os stats no StatPlus e não no ApexCharts', function (): void {
    expect(is_a(FilasStats::class, ApexChartWidget::class, true))->toBeFalse()
        ->and(is_a(UsuariosVisaoGeralStats::class, ApexChartWidget::class, true))->toBeFalse();
});
