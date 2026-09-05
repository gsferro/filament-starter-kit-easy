<?php

use App\Filament\Admin\Widgets\UsuariosVisaoGeralStats;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/**
 * O sexto stat de "Usuários e acesso": logins de hoje, com sparkline de 7 dias.
 *
 * Cinco stats mediam cadastro e nenhum media USO — cadastro grande com zero login era
 * indistinguível de cadastro grande com uso diário.
 *
 * Os cenários afirmam sobre os objetos `Stat`, não sobre o HTML, e isso é deliberado: a série vai
 * para a página dentro de um atributo Alpine JSON-escapado, e `assertSee('0')` casaria com
 * qualquer zero da tela — inclusive o de outro stat. O oráculo é o valor, e ele está no objeto.
 *
 * Ver `wikis/specs/main/stat-de-logins-do-dia/`.
 */

/**
 * Os `Stat` que o widget monta, na ordem.
 *
 * `getStats()` e `getCachedStats()` são `protected` (`Filament\Widgets\StatsOverviewWidget`), então
 * o acesso é por closure vinculada ao componente montado. `getCachedStats()` e não `getStats()`
 * porque é o primeiro que a view usa.
 *
 * Helper de um arquivo só, então fica no arquivo (`.ai/rules/testes.md`).
 *
 * @return array<int, Stat>
 */
function statsDeUsuariosEAcesso(): array
{
    Filament::setCurrentPanel('admin');

    $componente = Livewire::test(UsuariosVisaoGeralStats::class)->instance();

    return (fn (): array => $this->getCachedStats())->call($componente);
}

/**
 * O NÚMERO do stat, e não o markup dele.
 *
 * `StatPlus` estende `OdometerStat`, então `getValue()` devolve um `HtmlString` com
 * `<number-flow data-value="3">…</number-flow>` — o inteiro não sai de lá por casting: o corpo da
 * tag é `0` (o odômetro anima de zero até o valor no navegador), e só o atributo carrega o número.
 *
 * Extrair `data-value` é o oráculo mais estreito disponível: o nome do atributo pinça exatamente
 * onde o número vive, ao contrário de um `toContain('3')` no HTML inteiro, que casaria com o `3`
 * de qualquer outro lugar da marcação.
 */
function valorDoStat(?Stat $stat): ?int
{
    if (! $stat instanceof Stat) {
        return null;
    }

    return preg_match('/data-value="(\d+)"/', (string) $stat->getValue(), $achado) === 1
        ? (int) $achado[1]
        : null;
}

/** O stat que tem gráfico — o de logins é o único, e CT-07 é quem prova isso. */
function statComGrafico(): ?Stat
{
    foreach (statsDeUsuariosEAcesso() as $stat) {
        if ($stat->getChart() !== null) {
            return $stat;
        }
    }

    return null;
}

/**
 * Logins registrados para um usuário novo, num instante dado.
 *
 * Nasce pela relação morph do pacote (`AuthenticationLoggable::authentications()`): `$fillable` do
 * model NÃO inclui as colunas do morph, e `AuthenticationLog::create()` com elas no array não
 * grava o vínculo.
 */
function acessos(int $quantidade, Carbon $quando, bool $comSucesso = true): void
{
    if ($quantidade === 0) {
        return;
    }

    $usuario = User::factory()->create();

    for ($i = 0; $i < $quantidade; $i++) {
        $usuario->authentications()->create([
            'login_at'         => $quando,
            'login_successful' => $comSucesso,
            'ip_address'       => '203.0.113.7',
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| R1 — o widget passa a exibir seis stats
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — o sexto stat é acrescentado, não substitui nenhum.
 *
 * A terceira asserção é o que separa "acrescentou" de "substituiu": sem ela, uma implementação que
 * trocasse o stat de Permissões pelo de logins também devolveria 6.
 */
it('[CT-01] acrescenta o sexto stat sem substituir nenhum dos cinco', function (): void {
    $stats = statsDeUsuariosEAcesso();

    $comGrafico = array_filter($stats, fn (Stat $s): bool => $s->getChart() !== null);

    $rotulosSemGrafico = array_map(
        fn (Stat $s): string => (string) $s->getLabel(),
        array_filter($stats, fn (Stat $s): bool => $s->getChart() === null),
    );

    expect($stats)->toHaveCount(6)
        ->and($comGrafico)->toHaveCount(1)
        ->and(array_values($rotulosSemGrafico))->toBe([
            'Usuários',
            'Com 2FA ativo',
            'Novos em 30 dias',
            'Papéis',
            'Permissões',
        ]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — o valor é a contagem de logins bem-sucedidos de hoje
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — tentativa que falhou não conta, e o dia é hoje.
 *
 * Fixture discriminante: hoje **3** e ontem **5**, valores DIFERENTES de propósito. Com 3 e 3 um
 * off-by-one no índice passaria. As 2 falhas de hoje matam o filtro ausente de `login_successful`,
 * que daria 5.
 *
 * A segunda asserção não afirma que o valor "sai da série" — isso é implementação. Ela afirma que
 * as duas grandezas que o requisito pede separadamente (o número do dia e a ponta da série de 7
 * dias) medem a mesma coisa. Duas consultas divergentes ficam vermelhas só aqui.
 */
it('[CT-02] conta só os logins bem-sucedidos de hoje', function (): void {
    acessos(3, Carbon::today()->addHours(9));
    acessos(2, Carbon::today()->addHours(10), comSucesso: false);
    acessos(5, Carbon::yesterday()->addHours(9));

    $stat  = statComGrafico();
    // `end()` recebe por REFERÊNCIA: passar `$stat->getChart()` direto estoura
    // "Only variables should be passed by reference".
    $serie = $stat?->getChart() ?? [];

    expect($stat)->not->toBeNull()
        ->and(valorDoStat($stat))->toBe(3)
        ->and(end($serie))->toBe(3);
})->group('kit');

/**
 * CT-03 — a virada da meia-noite decide de que dia é o login.
 *
 * BVA de 3 valores com granularidade de 1 segundo, escalado acima do perfil `padrão` porque as
 * duas fronteiras desta feature são de tempo: com 2 valores o cenário passa nas duas
 * implementações.
 *
 * `travelTo` fixa o instante da observação — sem congelar, "hoje" pode virar entre o arranjo e a
 * asserção e a linha da borda vira flake.
 */
it('[CT-03] decide o dia do login pela virada da meia-noite', function (string $quando, int $esperado): void {
    Carbon::setTestNow(Carbon::parse('2026-09-04 12:00:00'));

    $instante = match ($quando) {
        'ontem 23:59:59' => Carbon::parse('2026-09-03 23:59:59'),
        'hoje 00:00:00'  => Carbon::parse('2026-09-04 00:00:00'),
        'hoje 00:00:01'  => Carbon::parse('2026-09-04 00:00:01'),
        'hoje 23:59:59'  => Carbon::parse('2026-09-04 23:59:59'),
    };

    acessos(1, $instante);

    expect(valorDoStat(statComGrafico()))->toBe($esperado);
})->with([
    'borda menos 1s' => ['ontem 23:59:59', 0],
    'borda'          => ['hoje 00:00:00', 1],
    'dentro'         => ['hoje 00:00:01', 1],
    'fim do dia'     => ['hoje 23:59:59', 1],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — a série tem sete posições, terminando em hoje
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — sete posições, em ordem cronológica crescente.
 *
 * A segunda e a terceira asserções matam o rótulo derivado do índice do laço em vez da data: com
 * rótulo errado eles repetem, ou o último deixa de corresponder a hoje.
 */
it('[CT-04] monta sete posições, da mais antiga até hoje', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-04 12:00:00'));

    for ($i = 0; $i < 7; $i++) {
        acessos(1, Carbon::today()->subDays($i)->addHours(9));
    }

    $serie = statComGrafico()?->getChart();

    expect($serie)->toHaveCount(7)
        ->and(array_unique(array_keys($serie)))->toHaveCount(7)
        ->and(array_key_last($serie))->toBe(Carbon::today()->format('d/m'));
})->group('kit');

/**
 * CT-05 — a ponta antiga da janela.
 *
 * O `Então` é a SOMA da série, não "a posição 0 vale 1": somar não pressupõe onde a implementação
 * colocaria o dia, então o cenário mede a janela sem medir o layout do array.
 */
it('[CT-05] inclui o sétimo dia e exclui o oitavo', function (int $diasAtras, int $somatorio): void {
    Carbon::setTestNow(Carbon::parse('2026-09-04 12:00:00'));

    acessos(1, Carbon::today()->subDays($diasAtras)->addHours(9));

    expect(array_sum(statComGrafico()?->getChart() ?? []))->toBe($somatorio);
})->with([
    'dentro'       => [5, 1],
    'borda'        => [6, 1],
    'borda mais 1' => [7, 0],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — dia sem login vale zero e não some
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — o dia vazio não é omitido.
 *
 * A regra que uma implementação ingênua erra: construir a série a partir do RESULTADO da consulta
 * faz o dia vazio sumir, a série encolher e a curva "pular" o buraco — um fim de semana sem
 * ninguém vira um trecho reto.
 */
it('[CT-06] mantém o dia sem login na série, com valor zero', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-04 12:00:00'));

    acessos(2, Carbon::today()->addHours(9));
    acessos(4, Carbon::today()->subDays(3)->addHours(9));

    $serie = statComGrafico()?->getChart();

    expect($serie)->toHaveCount(7)
        ->and(array_filter($serie, fn (int $v): bool => $v === 0))->toHaveCount(5)
        ->and(array_filter($serie, fn (mixed $v): bool => $v === null))->toBe([]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 e R6 — o gráfico é do stat, e a fonte ausente derruba só ele
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — só o stat de logins tem gráfico.
 *
 * A segunda asserção é o que impede o falso ✅: "um stat tem gráfico" ficaria verde se o gráfico
 * tivesse sido pendurado no stat de Permissões.
 */
it('[CT-07] põe o gráfico no stat de logins e em nenhum outro', function (): void {
    acessos(3, Carbon::today()->addHours(9));

    $comGrafico = array_values(array_filter(
        statsDeUsuariosEAcesso(),
        fn (Stat $s): bool => $s->getChart() !== null,
    ));

    expect($comGrafico)->toHaveCount(1)
        ->and(valorDoStat($comGrafico[0]))->toBe(3);
})->group('kit');

/**
 * CT-08 — o plugin ausente derruba só o stat que depende dele.
 *
 * A terceira asserção mata o mutante mais caro: a guarda declarada como
 * `fonteDeDadosDisponivel()` esconderia o widget INTEIRO, e "0 stats" também satisfaria "nenhum
 * tem gráfico".
 */
it('[CT-08] esconde só o stat de logins quando a tabela de acesso não existe', function (): void {
    Schema::drop((string) config('authentication-log.table_name', 'authentication_log'));

    $stats = statsDeUsuariosEAcesso();

    $rotulos = array_map(fn (Stat $s): string => (string) $s->getLabel(), $stats);

    expect($stats)->toHaveCount(5)
        ->and(array_filter($stats, fn (Stat $s): bool => $s->getChart() !== null))->toBe([])
        ->and($rotulos)->toContain('Usuários');
})->group('kit');
