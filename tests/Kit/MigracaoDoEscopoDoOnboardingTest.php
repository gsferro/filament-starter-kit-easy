<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Regra R8 da wiki `phpstan-nivel-8` — CARACTERIZAÇÃO: tratar nulidade na migration
 * `2026_08_12_164953_harden_onboarding_progress_scope.php` não muda o resultado da fusão de
 * progresso duplicado. O `Então` é o comportamento da `main`, não uma regra nova.
 *
 * **Medido antes do diff** (`git diff main -- database/migrations/2026_08_12_164953_...php`): a
 * ÚNICA diferença entre a `main` e esta branch é uma guarda `if ($survivor === null) { continue; }`
 * depois de `$survivor = $rows->first();`, onde `$rows` vem de um `having count(*) > 1` — ou
 * seja, `$rows` NUNCA é vazio, e a guarda é inalcançável em qualquer entrada real. É
 * comportamento idêntico por construção, não uma medição de campo que pudesse divergir; por isso
 * este arquivo não teve de rodar duas vezes (uma vez contra a `main` via `git stash`, outra na
 * branch) para cada linha — a MESMA leitura da migration explica as duas.
 *
 * Não havia teste algum desta migration antes desta wiki (`grep` por
 * `flow_progress|step_progress|scope_type` em `tests/` voltava vazio).
 *
 * Arnês: `(require $migration)->down()` volta as colunas de escopo a nulas, insere as linhas
 * direto na tabela (fora do Eloquent do pacote — as colunas o exigem cruas), e `->up()` refaz a
 * fusão. As FKs de `flow_id`/`step_id` para `onboarding_flows`/`onboarding_steps` ficam fora do
 * caminho: a migration sob teste nunca lê essas tabelas, só grava linhas de progresso.
 *
 * `PRAGMA defer_foreign_keys = ON` — e não `Schema::disableForeignKeyConstraints()` — pela mesma
 * razão medida em `tests/Kit/ConviteTest.php` (CT-13): o `RefreshDatabase` do Kit roda dentro de
 * uma transação, e o `PRAGMA foreign_keys` do SQLite é no-op enquanto ela estiver aberta. Como o
 * `RefreshDatabase` só dá `ROLLBACK` (nunca `COMMIT`), a checagem adiada nunca dispara.
 */
beforeEach(function (): void {
    DB::statement('PRAGMA defer_foreign_keys = ON');
});

/** A migration sob teste, como objeto — para chamar `->down()` e `->up()` diretamente. */
function migracaoDoOnboarding(): object
{
    return require database_path('migrations/2026_08_12_164953_harden_onboarding_progress_scope.php');
}

/** O nome real da tabela, pela MESMA config que a migration lê. */
function tabelaDeProgresso(string $chave): string
{
    return config("filament-onboarding.tables.{$chave}", "onboarding_{$chave}");
}

/**
 * Um id que ORDENA na ordem de inserção — o merge da migration decide "a mais antiga" por
 * `orderBy('id')`, e um UUID v4 aleatório (`Str::uuid()`) não tem essa garantia. Sequencial e
 * zero-padded, então a comparação lexicográfica de string casa com a ordem de chamada.
 */
function idOrdenavel(): string
{
    static $contador = 0;

    return sprintf('00000000-0000-0000-0000-%012d', ++$contador);
}

/**
 * Insere uma linha de progresso direto na tabela (fora do Eloquent do pacote).
 *
 * `flow_id` é NOT NULL nas duas tabelas — em `step_progress` ele convive com `step_id`
 * (`create_onboarding_tables.php.stub:84-85`), mas o merge daquela tabela agrupa por `step_id`
 * (`up()`: `mergeDuplicates(..., 'step_id', ...)`), nunca por `flow_id` — por isso um `flow_id`
 * FIXO, igual em toda linha do caso, é inócuo para o agrupamento e só existe para satisfazer a
 * coluna. A FK dele fica fora do caminho graças ao `PRAGMA defer_foreign_keys` do `beforeEach`.
 *
 * @param  array{scope: ?string, ts: ?string, completed: ?string, meta: ?array<string, mixed>}  $linha
 */
function inserirProgresso(string $tabela, string $ownerColumn, string $donoId, string $sujeito, string $colunaDoOutroTs, array $linha): string
{
    $id = idOrdenavel();

    DB::table($tabela)->insert(array_merge([
        'id'             => $id,
        'flow_id'        => 'flow-dummy-fixo',
        'subject_type'   => 'App\\Models\\User',
        'subject_id'     => $sujeito,
        'scope_type'     => $linha['scope'],
        'scope_id'       => $linha['scope'],
        $colunaDoOutroTs => $linha['ts'],
        'completed_at'   => $linha['completed'],
        'meta'           => $linha['meta'] === null ? null : json_encode($linha['meta']),
        'created_at'     => now(),
        'updated_at'     => now(),
    ], [$ownerColumn => $donoId]));

    return $id;
}

/**
 * CT-17 — as linhas de progresso duplicadas (mesmo dono, mesmo sujeito, escopo nulo ×
 * nulo/vazio) viram uma só, com o valor mais avançado de cada timestamp e a união dos metas; a
 * linha de controle (outro sujeito, sem duplicata) não perde os próprios valores — só o escopo
 * nulo dela também vira `""`.
 *
 * Os seis casos são discriminantes (não redundantes):
 * - #1/#2: a mesma forma (nulo × nulo) nas duas tabelas — prova que o merge não é hardcoded para
 *   `flow_progress`;
 * - #3: nulo × `""` é a colisão REAL que a migration existe para prevenir (índice único não
 *   distinguia as duas antes desta entrega);
 * - #4: o timestamp mais avançado está na linha MAIS ANTIGA — "ficar com os valores da mais
 *   nova" erraria aqui;
 * - #5: colisão de CHAVE do meta — "o primeiro vence" e "união" divergem, e o esperado é a
 *   união com o mais novo vencendo a chave repetida;
 * - #6: um TRIO, com a mais antiga já em `""` — separa "funde o grupo inteiro" de "funde só um
 *   par", e testa o `coalesce` do agrupamento com os dois valores (nulo e vazio) presentes.
 *
 * @param  array<int, array{scope: ?string, ts: ?string, completed: ?string, meta: ?array<string, mixed>}>  $duplicatas
 * @param  array{ts: ?string, completed: ?string, meta: ?array<string, mixed>}  $esperado
 */
it('[CT-17] as linhas de progresso duplicadas viram uma, com o que a main produzir, e a linha única não muda', function (
    string $tabelaChave,
    string $ownerColumn,
    string $colunaDoOutroTs,
    array $duplicatas,
    array $esperado,
): void {
    $migracao = migracaoDoOnboarding();
    $migracao->down();

    $tabela  = tabelaDeProgresso($tabelaChave);
    $dono    = (string) Str::uuid();
    $sujeito = 'sujeito-'.Str::random(8);

    $idsDoPar = [];

    foreach ($duplicatas as $linha) {
        $idsDoPar[] = inserirProgresso($tabela, $ownerColumn, $dono, $sujeito, $colunaDoOutroTs, $linha);
    }

    // A linha de controle: outro sujeito, sem duplicata, escopo nulo — prova que o merge não
    // toca em quem não tem par, além de virar "" no escopo.
    $idControle = inserirProgresso($tabela, $ownerColumn, $dono, 'controle-'.Str::random(8), $colunaDoOutroTs, [
        'scope'     => null,
        'ts'        => '2026-01-01 00:00:00',
        'completed' => '2026-01-02 00:00:00',
        'meta'      => ['controle' => true],
    ]);

    $migracao->up();

    $restantes = DB::table($tabela)->whereIn('id', $idsDoPar)->get();

    expect($restantes)->toHaveCount(1, 'esperava que o par tivesse virado uma linha só');

    $sobrevivente = $restantes->first();

    expect($sobrevivente->id)->toBe($idsDoPar[0], 'a sobrevivente deveria ser a mais antiga (menor id)')
        ->and($sobrevivente->{$colunaDoOutroTs})->toBe($esperado['ts'])
        ->and($sobrevivente->completed_at)->toBe($esperado['completed'])
        ->and(json_decode((string) $sobrevivente->meta, true))->toBe($esperado['meta'])
        ->and($sobrevivente->scope_type)->toBe('')
        ->and($sobrevivente->scope_id)->toBe('');

    $controle = DB::table($tabela)->where('id', $idControle)->first();

    expect($controle->scope_type)->toBe('')
        ->and($controle->scope_id)->toBe('')
        ->and($controle->{$colunaDoOutroTs})->toBe('2026-01-01 00:00:00')
        ->and($controle->completed_at)->toBe('2026-01-02 00:00:00')
        ->and(json_decode((string) $controle->meta, true))->toBe(['controle' => true]);
})->with([
    'nulo × nulo (flow_progress)' => [
        'flow_progress', 'flow_id', 'started_at',
        [
            ['scope' => null, 'ts' => '2026-08-01 10:00:00', 'completed' => null, 'meta' => ['a' => 1]],
            ['scope' => null, 'ts' => '2026-08-02 09:00:00', 'completed' => '2026-08-03 08:00:00', 'meta' => ['b' => 2]],
        ],
        ['ts' => '2026-08-02 09:00:00', 'completed' => '2026-08-03 08:00:00', 'meta' => ['a' => 1, 'b' => 2]],
    ],
    'nulo × nulo, outra tabela (step_progress)' => [
        'step_progress', 'step_id', 'seen_at',
        [
            ['scope' => null, 'ts' => '2026-08-01 10:00:00', 'completed' => null, 'meta' => ['a' => 1]],
            ['scope' => null, 'ts' => '2026-08-02 09:00:00', 'completed' => '2026-08-03 08:00:00', 'meta' => ['b' => 2]],
        ],
        ['ts' => '2026-08-02 09:00:00', 'completed' => '2026-08-03 08:00:00', 'meta' => ['a' => 1, 'b' => 2]],
    ],
    'nulo × vazio: a colisão real (flow_progress)' => [
        'flow_progress', 'flow_id', 'started_at',
        [
            ['scope' => null, 'ts' => '2026-08-01 10:00:00', 'completed' => null, 'meta' => ['a' => 1]],
            ['scope' => '', 'ts' => '2026-08-02 09:00:00', 'completed' => '2026-08-03 08:00:00', 'meta' => ['b' => 2]],
        ],
        ['ts' => '2026-08-02 09:00:00', 'completed' => '2026-08-03 08:00:00', 'meta' => ['a' => 1, 'b' => 2]],
    ],
    'máximo na linha ANTIGA (flow_progress)' => [
        'flow_progress', 'flow_id', 'started_at',
        [
            ['scope' => null, 'ts' => '2026-08-05 10:00:00', 'completed' => '2026-08-06 08:00:00', 'meta' => ['a' => 1]],
            ['scope' => null, 'ts' => '2026-08-02 09:00:00', 'completed' => null, 'meta' => ['b' => 2]],
        ],
        ['ts' => '2026-08-05 10:00:00', 'completed' => '2026-08-06 08:00:00', 'meta' => ['a' => 1, 'b' => 2]],
    ],
    'chave de meta em colisão (flow_progress)' => [
        'flow_progress', 'flow_id', 'started_at',
        [
            ['scope' => null, 'ts' => '2026-08-01 10:00:00', 'completed' => null, 'meta' => ['a' => 1]],
            ['scope' => null, 'ts' => '2026-08-02 09:00:00', 'completed' => null, 'meta' => ['a' => 2]],
        ],
        ['ts' => '2026-08-02 09:00:00', 'completed' => null, 'meta' => ['a' => 2]],
    ],
])->group('kit');

/**
 * CT-17, linha 6 — o TRIO, isolado do `->with()` acima porque o oráculo dele é outro (a
 * SOBREVIVENTE já nasce com escopo `""`, e não nulo) e porque ele afirma a AUSÊNCIA de uma
 * terceira linha, que o corpo genérico acima não mede.
 */
it('[CT-17] o trio de duplicatas vira uma linha só, e nenhuma sobra do par', function (): void {
    $migracao = migracaoDoOnboarding();
    $migracao->down();

    $tabela  = tabelaDeProgresso('flow_progress');
    $dono    = (string) Str::uuid();
    $sujeito = 'sujeito-'.Str::random(8);

    $mais_antiga = inserirProgresso($tabela, 'flow_id', $dono, $sujeito, 'started_at', [
        'scope' => '', 'ts' => '2026-08-01 10:00:00', 'completed' => null, 'meta' => null,
    ]);
    inserirProgresso($tabela, 'flow_id', $dono, $sujeito, 'started_at', [
        'scope' => null, 'ts' => '2026-08-03 10:00:00', 'completed' => null, 'meta' => null,
    ]);
    inserirProgresso($tabela, 'flow_id', $dono, $sujeito, 'started_at', [
        'scope' => null, 'ts' => '2026-08-02 10:00:00', 'completed' => '2026-08-04 08:00:00', 'meta' => null,
    ]);

    $migracao->up();

    $restantes = DB::table($tabela)->where('subject_id', $sujeito)->get();

    expect($restantes)->toHaveCount(1, 'nenhuma outra linha do par deveria sobrar');

    $sobrevivente = $restantes->first();

    expect($sobrevivente->id)->toBe($mais_antiga)
        ->and($sobrevivente->started_at)->toBe('2026-08-03 10:00:00')
        ->and($sobrevivente->completed_at)->toBe('2026-08-04 08:00:00')
        ->and($sobrevivente->meta)->toBeNull()
        ->and($sobrevivente->scope_type)->toBe('')
        ->and($sobrevivente->scope_id)->toBe('');
})->group('kit');
