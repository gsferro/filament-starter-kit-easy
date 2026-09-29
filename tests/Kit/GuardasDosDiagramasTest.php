<?php

/**
 * O lote R48–R53 da wiki `feat/diagramas-da-arquitetura` — CT-106..CT-111, CT-115 e CT-116.
 *
 * Fonte: `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/04-casos-de-teste.md`.
 * Escrito a partir do Gherkin do `04` (e do `00`, indiretamente, via o `04`) — NUNCA do `01` nem do
 * `02` da wiki, e NUNCA da implementação: `app/` só foi lido para achar NOMES (classe do plugin,
 * método, arquivo de config), nunca para decidir o comportamento esperado.
 *
 * Arquivo NOVO, separado de `tests/Kit/DiagramasDaArquiteturaTest.php` (que já tem CT-01..CT-105) —
 * este cobre só o lote das seis regras novas da 2ª passada do step 10 que a wiki descreve nos
 * grupos "Extras do catálogo — suíte Kit": R48 (DG-10), R49 e R50 (DG-19), R52 (controles contra a
 * tabela literal de CT-114, que mora em `tests/Tenancy`) e R53 (a promessa da página, os 20 DGs).
 *
 * CT-112, CT-113 e CT-114 (R51 e a metade de R52 que precisa do GET real) são de
 * `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` — fora deste arquivo.
 *
 * Helpers usados por mais de um arquivo (`blocoDoCatalogoNaArvore`, `transicoesDeEstado`,
 * `arestasDeFluxo`, `rotulosDeNoDeFluxo`, `mensagensDeSequencia`, `blocoDoServico`,
 * `naArvoreDoKit`, …) vêm de `tests/Pest.php`, nunca clonados aqui. Todo helper NOVO deste lote é
 * usado só por este arquivo, e por isso mora nele (`.ai/rules/testes.md`).
 */

use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;

beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        test()->markTestSkipped('As guardas dos diagramas só existem na árvore do kit — o projeto instalado não recebe a documentação do site nem o README pelo kit:update.');
    }
});

/*
|--------------------------------------------------------------------------
| Helpers gerais deste arquivo
|--------------------------------------------------------------------------
*/

/**
 * O valor literal do 2º argumento de `env('CHAVE', valor)` dentro de um texto-fonte (o conteúdo de
 * um arquivo de `config/`, real ou uma cópia mutada em memória) — nunca `config()`, que mede o
 * ambiente de quem roda o teste, não o DEFAULT do kit (a mesma técnica de R4 sobre
 * `config/kit.php`). `null` quando a chave não aparece no fonte.
 */
function defaultDoEnvNoFonte(string $fonte, string $chave): ?string
{
    if (preg_match('/env\(\s*[\'"]'.preg_quote($chave, '/').'[\'"]\s*,\s*([^)]+?)\s*\)/', $fonte, $m) !== 1) {
        return null;
    }

    $valor = trim($m[1]);

    // O 2º argumento de env() pode vir citado ('default') ou não (1800, true) — só o LITERAL
    // interessa aqui, nunca as aspas que o envolvem.
    if (preg_match('/^([\'"])(.*)\1$/', $valor, $mCitado) === 1) {
        return $mCitado[2];
    }

    return $valor;
}

/**
 * A primeira transição $de -> $para (`transicoesDeEstado()`, de `tests/Pest.php`) cujo evento tem
 * um número — o número e o texto do evento. `null` quando a transição não existe ou o evento dela
 * não carrega dígito nenhum (é o que prova que o destino, não só o número, está sendo lido: uma
 * cópia que desvia o destino faz este helper devolver `null`, nunca um número errado).
 *
 * @param  list<array{de: string, para: string, evento: ?string}>  $transicoes
 * @return ?array{numero: int, evento: string}
 */
function transicaoComNumero(array $transicoes, string $de, string $para): ?array
{
    foreach ($transicoes as $t) {
        if ($t['de'] === $de && $t['para'] === $para && $t['evento'] !== null && preg_match('/(\d+)/', $t['evento'], $m) === 1) {
            return ['numero' => (int) $m[1], 'evento' => $t['evento']];
        }
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| R48 — DG-10: ociosidade, tentativas e force logout do plugin de bloqueio
|--------------------------------------------------------------------------
*/

/**
 * Muda uma propriedade PROTEGIDA do plugin de bloqueio já registrado no painel — o "mundo
 * alterado" desta regra é o plugin JÁ REGISTRADO, nunca `config()` depois do boot (o painel leu a
 * config ao montar).
 *
 * Via `Closure::bind`, e não os métodos públicos do próprio pacote
 * (`enableIdleTimeout()`/`enableRateLimit()`): `disableIdleTimeout()` e `disableRateLimit()` do
 * `marjose123/filament-lockscreen` (v1.x) não têm `return $this` apesar do `: self` declarado —
 * chamá-los estoura `TypeError` (medido). Setar a propriedade direto contorna o defeito do vendor
 * sem tocar nele, e dá controle independente sobre CADA campo (liga/desliga separado do número).
 */
function forcarPropriedadeDoPlugin(object $plugin, string $propriedade, mixed $valor): void
{
    Closure::bind(function () use ($propriedade, $valor): void {
        $this->{$propriedade} = $valor;
    }, $plugin, $plugin::class)();
}

/**
 * A guarda do R48: o DG-10 (pt e en) contra o fonte de `config/lockscreen.php` (ou uma cópia
 * mutada) e o plugin JÁ REGISTRADO nos três painéis. Nunca compara com literais (1800, 5) escritos
 * aqui — só com o fonte e com `isEnableIdleTimeout()`/`getIdleTimeout()`/`isRateLimitEnabled()`/
 * `getRateLimitLimit()`/`isForceLogout()` do plugin de CADA painel (M4: um painel só não basta).
 *
 * @return array{aceita: bool, motivo: ?string}
 */
function conferirDg10(string $blocoPt, string $blocoEn, string $fonteLockscreen): array
{
    $idleDoFonte    = (int) (defaultDoEnvNoFonte($fonteLockscreen, 'LOCKSCREEN_IDLE_TIMEOUT') ?? '0');
    $enabledDoFonte = filter_var(defaultDoEnvNoFonte($fonteLockscreen, 'LOCKSCREEN_ENABLED') ?? 'true', FILTER_VALIDATE_BOOLEAN);

    foreach (['pt' => $blocoPt, 'en' => $blocoEn] as $idioma => $bloco) {
        $transicoes = transicoesDeEstado($bloco);

        $paraBloqueada = transicaoComNumero($transicoes, 'autenticada', 'bloqueada');

        if ($paraBloqueada === null) {
            return ['aceita' => false, 'motivo' => "o DG-10 em {$idioma} não desenha a transição de autenticada para bloqueada com um número"];
        }

        if ($paraBloqueada['numero'] !== $idleDoFonte) {
            return ['aceita' => false, 'motivo' => "o DG-10 em {$idioma} desenha {$paraBloqueada['numero']}s de ociosidade, e o fonte de config/lockscreen.php diz {$idleDoFonte}s"];
        }

        if (! $enabledDoFonte && ! str_contains($bloco, 'LOCKSCREEN_ENABLED')) {
            return ['aceita' => false, 'motivo' => "LOCKSCREEN_ENABLED está falso no fonte (o bloqueio virou opt-in), e o DG-10 em {$idioma} não marca isso — R39 só extrai config/kit.php"];
        }

        $paraEncerrada = transicaoComNumero($transicoes, 'bloqueada', 'encerrada');

        if ($paraEncerrada === null) {
            return ['aceita' => false, 'motivo' => "o DG-10 em {$idioma} não desenha a transição de bloqueada para encerrada por tentativas"];
        }

        foreach (['admin', 'app', 'infra'] as $painel) {
            $plugin = Filament::getPanel($painel)->getPlugin('filament-lockscreen');

            if (! $plugin->isEnableIdleTimeout() || $plugin->getIdleTimeout() !== $paraBloqueada['numero']) {
                return ['aceita' => false, 'motivo' => "o painel {$painel} não bate com a ociosidade ({$paraBloqueada['numero']}s) desenhada em {$idioma}: isEnableIdleTimeout()=".($plugin->isEnableIdleTimeout() ? 'true' : 'false').", getIdleTimeout()={$plugin->getIdleTimeout()}"];
            }

            if (! $plugin->isRateLimitEnabled() || $plugin->getRateLimitLimit() !== $paraEncerrada['numero']) {
                return ['aceita' => false, 'motivo' => "o painel {$painel} não bate com as tentativas ({$paraEncerrada['numero']}) desenhadas em {$idioma}: getRateLimitLimit()={$plugin->getRateLimitLimit()}"];
            }

            if (! $plugin->isForceLogout()) {
                return ['aceita' => false, 'motivo' => "o painel {$painel} tem isForceLogout() falso, e o DG-10 em {$idioma} desenha o desfecho encerrada (force logout)"];
            }
        }
    }

    return ['aceita' => true, 'motivo' => null];
}

it('[CT-106] a ociosidade, as tentativas e o desfecho desenhados no DG-10 são os do plugin de bloqueio do painel', function (string $painel): void {
    expect(config('lockscreen.enabled'))->toBeTrue('lockscreen.enabled deveria estar ligado no processo de teste — afirmado, não presumido');

    $fonte       = (string) file_get_contents(config_path('lockscreen.php'));
    $idleDoFonte = (int) defaultDoEnvNoFonte($fonte, 'LOCKSCREEN_IDLE_TIMEOUT');

    $plugin = Filament::getPanel($painel)->getPlugin('filament-lockscreen');

    expect($plugin->isEnableIdleTimeout())->toBeTrue("{$painel}: isEnableIdleTimeout() deveria ser verdadeiro")
        ->and($plugin->getIdleTimeout())->toBe((int) config('lockscreen.idle_timeout'))
        ->and($plugin->getIdleTimeout())->toBe($idleDoFonte)
        ->and($plugin->isRateLimitEnabled())->toBeTrue("{$painel}: isRateLimitEnabled() deveria ser verdadeiro")
        ->and($plugin->isForceLogout())->toBeTrue("{$painel}: isForceLogout() deveria ser verdadeiro");

    foreach (['pt', 'en'] as $idioma) {
        $blocoDoCatalogo = blocoDoCatalogoNaArvore('DG-10', $idioma);
        expect($blocoDoCatalogo)->not->toBeNull("DG-10 em {$idioma} deveria existir no catálogo publicado");

        $transicoes = transicoesDeEstado($blocoDoCatalogo['bloco']);

        $paraBloqueada = transicaoComNumero($transicoes, 'autenticada', 'bloqueada');
        expect($paraBloqueada)->not->toBeNull("DG-10 em {$idioma} deveria desenhar a transição de autenticada para bloqueada com um número")
            ->and($paraBloqueada['numero'])->toBe($idleDoFonte, "DG-10 em {$idioma}: a ociosidade desenhada deveria ser o default do fonte de config/lockscreen.php");

        $paraEncerrada = transicaoComNumero($transicoes, 'bloqueada', 'encerrada');
        expect($paraEncerrada)->not->toBeNull("DG-10 em {$idioma} deveria desenhar a transição de bloqueada para encerrada por tentativas")
            ->and($paraEncerrada['numero'])->toBe($plugin->getRateLimitLimit(), "DG-10 em {$idioma}: as tentativas desenhadas deveriam ser getRateLimitLimit() do painel {$painel}");
    }

    expect(Route::has("lockscreen.{$painel}.lock-session"))->toBeTrue("a rota lockscreen.{$painel}.lock-session (o bloqueio manual do rótulo) deveria existir");
})->with(['admin', 'app', 'infra']);

it('[CT-107] o DG-10 fica vermelho quando o número, o desfecho ou a marca de opcional do bloqueio deixam de bater com o código', function (string $mundo, string $bloco, bool $aceitaEsperado, string $nomeando): void {
    $fonte   = (string) file_get_contents(config_path('lockscreen.php'));
    $blocoPt = blocoDoCatalogoNaArvore('DG-10', 'pt')['bloco'];
    $blocoEn = blocoDoCatalogoNaArvore('DG-10', 'en')['bloco'];

    switch ($mundo) {
        case 'nada alterado':
            break;

        case 'plugin do admin com limit 3 e forceLogout true':
            $adminPlugin = Filament::getPanel('admin')->getPlugin('filament-lockscreen');
            forcarPropriedadeDoPlugin($adminPlugin, 'rateLimit', 3);
            forcarPropriedadeDoPlugin($adminPlugin, 'forceLogout', true);
            break;

        case 'plugin do app com forceLogout false (default do plugin)':
            forcarPropriedadeDoPlugin(Filament::getPanel('app')->getPlugin('filament-lockscreen'), 'forceLogout', false);
            break;

        case 'plugin do infra sem a ociosidade ligada':
            forcarPropriedadeDoPlugin(Filament::getPanel('infra')->getPlugin('filament-lockscreen'), 'enableActivityTimeout', false);
            break;

        case 'fonte com LOCKSCREEN_IDLE_TIMEOUT em 900':
            $fonte = str_replace("env('LOCKSCREEN_IDLE_TIMEOUT', 1800)", "env('LOCKSCREEN_IDLE_TIMEOUT', 900)", $fonte);
            break;

        case 'fonte com LOCKSCREEN_ENABLED em false':
            $fonte = str_replace("env('LOCKSCREEN_ENABLED', true)", "env('LOCKSCREEN_ENABLED', false)", $fonte);
            break;

        default:
            throw new RuntimeException("mundo desconhecido: {$mundo}");
    }

    switch ($bloco) {
        case 'real':
            break;

        case 'en com idle for 1800s trocado por idle for 900s':
            $blocoEn = str_replace('idle for 1800s', 'idle for 900s', $blocoEn);
            break;

        case 'pt com a transição de tentativas levada a bloqueada':
            $blocoPt = str_replace(
                'bloqueada --> encerrada : 5 tentativas erradas (force logout)',
                'bloqueada --> bloqueada : 5 tentativas erradas (force logout)',
                $blocoPt,
            );
            break;

        case 'real com a nota LOCKSCREEN_ENABLED no estado bloqueada':
            $blocoPt = str_replace('state "Bloqueada" as bloqueada', 'state "Bloqueada (LOCKSCREEN_ENABLED)" as bloqueada', $blocoPt);
            $blocoEn = str_replace('state "Locked" as bloqueada', 'state "Locked (LOCKSCREEN_ENABLED)" as bloqueada', $blocoEn);
            break;

        default:
            throw new RuntimeException("bloco desconhecido: {$bloco}");
    }

    $resultado = conferirDg10($blocoPt, $blocoEn, $fonte);

    expect($resultado['aceita'])->toBe($aceitaEsperado, (string) ($resultado['motivo'] ?? ''));

    if (! $aceitaEsperado && $nomeando !== '') {
        expect((string) $resultado['motivo'])->toContain($nomeando);
    }
})->with([
    ['nada alterado', 'real', true, ''],
    ['plugin do admin com limit 3 e forceLogout true', 'real', false, 'admin'],
    ['plugin do app com forceLogout false (default do plugin)', 'real', false, 'app'],
    ['plugin do infra sem a ociosidade ligada', 'real', false, 'infra'],
    ['fonte com LOCKSCREEN_IDLE_TIMEOUT em 900', 'real', false, '900'],
    ['nada alterado', 'en com idle for 1800s trocado por idle for 900s', false, 'en'],
    ['nada alterado', 'pt com a transição de tentativas levada a bloqueada', false, 'pt'],
    ['fonte com LOCKSCREEN_ENABLED em false', 'real', false, 'LOCKSCREEN_ENABLED'],
    ['fonte com LOCKSCREEN_ENABLED em false', 'real com a nota LOCKSCREEN_ENABLED no estado bloqueada', true, ''],
]);

/*
|--------------------------------------------------------------------------
| R49 — DG-19: o agendador (Schedule::events())
|--------------------------------------------------------------------------
*/

/**
 * O evento agendado cujo `command` (Schedule::command()) contém $trecho — o comando de verdade
 * lido do `Event`, nunca uma lista escrita aqui. `$excetoExpressao` desambigua quando dois eventos
 * têm o MESMO comando (achado: `bezhansalleh/filament-exceptions` agenda o seu PRÓPRIO
 * `model:prune --model=Exception` às 00:00 — `daily()` sem `->at()` —, por fora de
 * `routes/console.php`, que agenda o mesmo comando às 02:00; ver o relatório desta entrega).
 */
function eventoPeloComando(string $trecho, ?string $excetoExpressao = null): ?Event
{
    return collect(Schedule::events())->first(
        fn (Event $e): bool => str_contains((string) ($e->command ?? ''), $trecho)
            && ($excetoExpressao === null || $e->expression !== $excetoExpressao),
    );
}

/** O evento agendado por `Schedule::call(...)->name($descricao)` — as três podas por closure. */
function eventoPelaDescricao(string $descricao): ?Event
{
    return collect(Schedule::events())->first(fn (Event $e): bool => (string) $e->description === $descricao);
}

/**
 * O nó do agendador do DG-19 em que este evento cai, pela FORMA do nome (`@premissa` Q?1): comando
 * que termina em `:purge`, é `model:prune`, ou description que começa por `kit:limpar-` caem em
 * "purge"; `health:check` cai em "health_check"; `kit:convites-lembrar` cai em "convites_lembrar".
 * `null` quando a forma não é reconhecida — é o soundness que reprova `backup:run`.
 */
function noDoAgendadorDg19(Event $evento): ?string
{
    $comando   = (string) ($evento->command ?? '');
    $descricao = (string) ($evento->description ?? '');

    return match (true) {
        str_contains($comando, 'health:check')          => 'health_check',
        str_contains($comando, 'kit:convites-lembrar')  => 'convites_lembrar',
        str_contains($comando, ':purge'),
        str_contains($comando, 'model:prune'),
        str_starts_with($descricao, 'kit:limpar-')       => 'purge',
        default                                          => null,
    };
}

/**
 * A frequência de `$cron` bate com o texto do rótulo, nas três formas que a guarda reconhece
 * (a premissa Q1 do 04): intervalo (passo de N minutos, cron "estrela-barra-N estrela estrela
 * estrela estrela" -> "a cada N min"/"every N min"), horário fixo ("M H estrela estrela estrela"
 * com H >= 6 -> "HH:MM") e madrugada (H em [0, 6) -> "madrugada"/"overnight"). A comparação lê
 * SEMPRE `$cron` (o `Event->expression` real) — nunca um literal escrito no teste.
 */
function frequenciaBateComORotulo(string $cron, string $rotulo, string $idioma): bool
{
    if (preg_match('/^\*\/(\d+) \* \* \* \*$/', $cron, $m) === 1) {
        return str_contains($rotulo, $idioma === 'en' ? "every {$m[1]} min" : "a cada {$m[1]} min");
    }

    if (preg_match('/^(\d{1,2}) (\d{1,2}) \* \* \*$/', $cron, $m) === 1) {
        $minuto = (int) $m[1];
        $hora   = (int) $m[2];

        if ($hora >= 0 && $hora < 6) {
            return str_contains($rotulo, $idioma === 'en' ? 'overnight' : 'madrugada');
        }

        return str_contains($rotulo, sprintf('%02d:%02d', $hora, $minuto));
    }

    return false;
}

/** Insere um nó novo, ligado a `scheduler_container`, antes da 1ª aresta do agendador do DG-19. */
function adicionarNoNoAgendador(string $bloco, string $id, string $rotulo): string
{
    return str_replace(
        '  scheduler_container --> health_check',
        "    {$id}[\"{$rotulo}\"]\n  scheduler_container --> {$id}\n  scheduler_container --> health_check",
        $bloco,
    );
}

/**
 * A guarda do R49: todo evento REAL de `Schedule::events()` cai em um dos três nós do agendador do
 * DG-19 (soundness — `backup:run` não cairia em nenhum), todo nó do agendador é alcançado só por
 * `scheduler_container` (nunca por outro contêiner, e nunca por um nó que o código não agenda), e a
 * frequência de cada evento bate com o rótulo do seu nó, em pt e em en.
 *
 * @return array{aceita: bool, motivo: ?string}
 */
function conferirAgendadorDg19(string $blocoPt, string $blocoEn): array
{
    $nomeDoNo = [
        'health_check'     => 'health:check',
        'convites_lembrar' => 'kit:convites-lembrar',
        'purge'            => 'podas de retenção',
    ];

    $eventos = collect(Schedule::events());

    if ($eventos->count() < 7) {
        return ['aceita' => false, 'motivo' => "Schedule::events() tem só {$eventos->count()} eventos — o piso é 7"];
    }

    foreach ($eventos as $evento) {
        if (noDoAgendadorDg19($evento) === null) {
            $identidade = $evento->command ?? $evento->description ?? '(sem identidade)';

            return ['aceita' => false, 'motivo' => "o evento {$identidade} não é reconhecido por nenhum nó do agendador do DG-19"];
        }
    }

    foreach (['pt' => $blocoPt, 'en' => $blocoEn] as $idioma => $bloco) {
        $arestas = arestasDeFluxo($bloco);
        $rotulos = rotulosDeNoDeFluxo($bloco);

        foreach (['health_check', 'convites_lembrar', 'purge'] as $no) {
            $origens = array_values(array_unique(array_column(
                array_filter($arestas, static fn (array $a): bool => $a['para'] === $no),
                'de',
            )));

            if ($origens === []) {
                return ['aceita' => false, 'motivo' => "o DG-19 em {$idioma} não liga scheduler_container a {$nomeDoNo[$no]}"];
            }

            if ($origens !== ['scheduler_container']) {
                return ['aceita' => false, 'motivo' => "o DG-19 em {$idioma} liga {$no} a algo além de scheduler_container: ".implode(', ', $origens)];
            }
        }

        $alvosDoAgendador = array_unique(array_column(
            array_filter($arestas, static fn (array $a): bool => $a['de'] === 'scheduler_container'),
            'para',
        ));

        foreach ($alvosDoAgendador as $alvo) {
            if (! in_array($alvo, ['health_check', 'convites_lembrar', 'purge'], true)) {
                $rotuloDoAlvo = $rotulos[$alvo] ?? $alvo;

                return ['aceita' => false, 'motivo' => "o DG-19 em {$idioma} desenha \"{$rotuloDoAlvo}\" agendado, e o código não agenda isso"];
            }
        }

        foreach ($eventos as $evento) {
            $no = noDoAgendadorDg19($evento);

            if (! frequenciaBateComORotulo($evento->expression, $rotulos[$no] ?? '', $idioma)) {
                $identidade = $evento->command ?? $evento->description ?? $no;

                return ['aceita' => false, 'motivo' => "{$idioma}: {$identidade} tem cron \"{$evento->expression}\", e o rótulo de {$no} (\"".($rotulos[$no] ?? '').'") não bate'];
            }
        }
    }

    return ['aceita' => true, 'motivo' => null];
}

it('[CT-108] cada evento agendado está no agendador do DG-19 com a frequência que declara', function (string $evento, string $no, string $cronHoje, ?string $excetoExpressao): void {
    $eventoReal = str_starts_with($evento, 'kit:limpar-')
        ? eventoPelaDescricao($evento)
        : eventoPeloComando($evento, $excetoExpressao);

    expect($eventoReal)->not->toBeNull("o evento {$evento} deveria estar agendado em routes/console.php")
        ->and($eventoReal->expression)->toBe($cronHoje, "a expressão cron de {$evento} deveria ser {$cronHoje}")
        ->and(noDoAgendadorDg19($eventoReal))->toBe($no, "{$evento} deveria cair no nó {$no}, pela forma do nome");

    foreach (['pt', 'en'] as $idioma) {
        $bloco   = blocoDoCatalogoNaArvore('DG-19', $idioma)['bloco'];
        $rotulos = rotulosDeNoDeFluxo($bloco);

        test()->assertArrayHasKey($no, $rotulos, "o DG-19 em {$idioma} deveria desenhar o nó {$no}");

        expect(frequenciaBateComORotulo($cronHoje, $rotulos[$no], $idioma))->toBeTrue(
            "o rótulo do nó {$no} em {$idioma} ('{$rotulos[$no]}') deveria dizer a frequência de {$evento} ({$cronHoje})",
        );
    }

    // Piso: todo evento real cai numa das 7 linhas (M1) — CT-108 exige as 7, sem exigir SÓ 7: o
    // achado do model:prune duplicado (nota do docblock de eventoPeloComando()) cai na MESMA
    // linha "purge" e não quebra o piso.
    expect(collect(Schedule::events())->every(fn (Event $e): bool => noDoAgendadorDg19($e) !== null))->toBeTrue(
        'todo evento de Schedule::events() deveria cair numa das linhas deste esquema',
    );
})->with([
    ['health:check', 'health_check', '*/15 * * * *', null],
    ['kit:convites-lembrar', 'convites_lembrar', '0 8 * * *', null],
    ['authentication-log:purge', 'purge', '0 0 * * *', null],
    ['model:prune', 'purge', '0 2 * * *', '0 0 * * *'],
    ['kit:limpar-trilha-de-emails', 'purge', '10 2 * * *', null],
    ['kit:limpar-historico-de-importacoes', 'purge', '20 2 * * *', null],
    ['kit:limpar-historico-de-exportacoes', 'purge', '30 2 * * *', null],
]);

it('[CT-109] o agendador do DG-19 fica vermelho quando o código agenda o que o bloco não desenha, ou o bloco desenha o que o código não agenda', function (string $mundo, string $bloco, bool $aceitaEsperado, string $nomeando): void {
    $blocoPt = blocoDoCatalogoNaArvore('DG-19', 'pt')['bloco'];
    $blocoEn = blocoDoCatalogoNaArvore('DG-19', 'en')['bloco'];

    switch ($mundo) {
        case 'nada alterado':
            break;

        case 'backup:run registrado às 01:30':
            Schedule::command('backup:run')->daily()->at('01:30');
            break;

        case 'kit:convites-lembrar às 09:00':
            eventoPeloComando('kit:convites-lembrar')->expression = '0 9 * * *';
            break;

        case 'health:check a cada hora':
            eventoPeloComando('health:check')->expression = '0 * * * *';
            break;

        case 'kit:limpar-trilha-de-emails às 06:00':
            eventoPelaDescricao('kit:limpar-trilha-de-emails')->expression = '0 6 * * *';
            break;

        case 'kit:limpar-trilha-de-emails às 05:59':
            eventoPelaDescricao('kit:limpar-trilha-de-emails')->expression = '59 5 * * *';
            break;

        default:
            throw new RuntimeException("mundo desconhecido: {$mundo}");
    }

    switch ($bloco) {
        case 'real':
            break;

        case 'com o nó backup:run - 01:30 ligado a scheduler_container':
            $blocoPt = adicionarNoNoAgendador($blocoPt, 'backup_run', 'backup:run - 01:30');
            $blocoEn = adicionarNoNoAgendador($blocoEn, 'backup_run', 'backup:run - 01:30');
            break;

        case 'com a aresta queue_worker --> health_check':
            $blocoPt .= "\n  queue_worker --> health_check";
            $blocoEn .= "\n  queue_worker --> health_check";
            break;

        case 'sem a aresta scheduler_container --> convites_lembrar':
            $blocoPt = str_replace("  scheduler_container --> convites_lembrar\n", '', $blocoPt);
            $blocoEn = str_replace("  scheduler_container --> convites_lembrar\n", '', $blocoEn);
            break;

        default:
            throw new RuntimeException("bloco desconhecido: {$bloco}");
    }

    $resultado = conferirAgendadorDg19($blocoPt, $blocoEn);

    expect($resultado['aceita'])->toBe($aceitaEsperado, (string) ($resultado['motivo'] ?? ''));

    if (! $aceitaEsperado && $nomeando !== '') {
        expect((string) $resultado['motivo'])->toContain($nomeando);
    }
})->with([
    ['nada alterado', 'real', true, ''],
    ['backup:run registrado às 01:30', 'real', false, 'backup:run'],
    ['nada alterado', 'com o nó backup:run - 01:30 ligado a scheduler_container', false, 'backup:run'],
    ['kit:convites-lembrar às 09:00', 'real', false, 'kit:convites-lembrar'],
    ['health:check a cada hora', 'real', false, 'health:check'],
    ['kit:limpar-trilha-de-emails às 06:00', 'real', false, ''],
    ['kit:limpar-trilha-de-emails às 05:59', 'real', true, ''],
    ['nada alterado', 'com a aresta queue_worker --> health_check', false, ''],
    ['nada alterado', 'sem a aresta scheduler_container --> convites_lembrar', false, 'kit:convites-lembrar'],
]);

/*
|--------------------------------------------------------------------------
| R50 — DG-19: processos do composer dev e do Docker Compose
|--------------------------------------------------------------------------
*/

/** As filas do `--queue=` do command de um serviço do compose, na ordem em que o código escreve. */
function filaDoServico(string $compose, string $servico): string
{
    preg_match('/--queue=([a-z0-9,\-]+)/i', blocoDoServico($compose, $servico), $m);

    return $m[1] ?? '';
}

/** O subcomando artisan (`schedule:work`, `reverb:start`, …) do command de um serviço do compose. */
function comandoDoServico(string $compose, string $servico): ?string
{
    preg_match('/\'([a-z][a-z0-9]*:[a-z0-9:_-]+)\'/i', blocoDoServico($compose, $servico), $m);

    return $m[1] ?? null;
}

/**
 * A fila padrão do `queue:listen` (sem `--queue`) — lida do FONTE de `config/queue.php`
 * (`DB_QUEUE`, a conexão `database` é o default do kit), nunca de `config()` no processo de teste:
 * `phpunit.xml` força `QUEUE_CONNECTION=sync`, que não tem fila (M3 do R50).
 */
function filaPadraoNoFonteDoQueue(string $fonteQueuePhp): string
{
    return defaultDoEnvNoFonte($fonteQueuePhp, 'DB_QUEUE') ?? 'default';
}

/**
 * A guarda do R50: cada processo do DG-19 (composer dev + Docker Compose) afirma o comando, as
 * filas (na ORDEM do código, nunca como conjunto) e a condição de plataforma que o fonte (do
 * `docker-compose.yml`, real ou uma cópia mutada) realmente tem.
 *
 * @return array{aceita: bool, motivo: ?string}
 */
function conferirProcessosDg19(string $blocoPt, string $blocoEn, string $composeFonte): array
{
    $filaQueueWorker  = filaDoServico($composeFonte, 'queue');
    $comandoScheduler = comandoDoServico($composeFonte, 'scheduler');
    $temHorizon       = blocoDoServico($composeFonte, 'horizon') !== '';
    $filaPadrao       = filaPadraoNoFonteDoQueue((string) file_get_contents(config_path('queue.php')));

    foreach (['pt' => $blocoPt, 'en' => $blocoEn] as $idioma => $bloco) {
        $rotulos = rotulosDeNoDeFluxo($bloco);

        if ($filaQueueWorker === '' || ! str_contains($rotulos['queue_worker'] ?? '', $filaQueueWorker)) {
            return ['aceita' => false, 'motivo' => "{$idioma}: queue_worker não afirma a ordem das filas ({$filaQueueWorker}) que o docker-compose.yml registra"];
        }

        if ($filaQueueWorker !== $filaPadrao && str_contains($rotulos['queue_listen'] ?? '', $filaQueueWorker)) {
            return ['aceita' => false, 'motivo' => "{$idioma}: queue_listen afirma as filas do Docker Compose ({$filaQueueWorker}), e deveria afirmar só a fila padrão ({$filaPadrao})"];
        }

        if (! str_contains($rotulos['queue_listen'] ?? '', $filaPadrao)) {
            return ['aceita' => false, 'motivo' => "{$idioma}: queue_listen deveria afirmar a fila padrão ({$filaPadrao}), lida do fonte de config/queue.php"];
        }

        if (! str_contains($rotulos['pail'] ?? '', $idioma === 'en' ? 'off Windows' : 'fora do Windows')) {
            return ['aceita' => false, 'motivo' => "{$idioma}: pail deveria ter a condição de plataforma (pcntl_fork)"];
        }

        if (isset($rotulos['horizon']) && ! $temHorizon) {
            return ['aceita' => false, 'motivo' => "{$idioma}: horizon não é serviço do docker-compose.yml"];
        }

        if ($comandoScheduler !== 'schedule:work' && isset($rotulos['scheduler_container'])) {
            return ['aceita' => false, 'motivo' => "{$idioma}: scheduler_container roda \"{$comandoScheduler}\", não schedule:work"];
        }
    }

    return ['aceita' => true, 'motivo' => null];
}

it('[CT-110] o que o rótulo de cada processo do DG-19 afirma é o que o código registra para ele', function (string $no): void {
    $compose = (string) file_get_contents(base_path('docker-compose.yml'));

    foreach (['pt', 'en'] as $idioma) {
        $rotulo = rotulosDeNoDeFluxo(blocoDoCatalogoNaArvore('DG-19', $idioma)['bloco'])[$no] ?? null;

        expect($rotulo)->not->toBeNull("o DG-19 em {$idioma} deveria desenhar o nó {$no}");

        match ($no) {
            'queue_worker' => test()->assertStringContainsString(
                filaDoServico($compose, 'queue'),
                $rotulo,
                "queue_worker em {$idioma} deveria afirmar as filas do serviço queue do compose, na ordem do command",
            ),
            'scheduler_container' => (function () use ($compose, $rotulo, $idioma): void {
                expect(comandoDoServico($compose, 'scheduler'))->toBe('schedule:work', 'o serviço scheduler do compose deveria rodar schedule:work');
                expect($rotulo)->toContain($idioma === 'en' ? 'scheduler' : 'agendador');
            })(),
            'reverb_compose' => expect(comandoDoServico($compose, 'reverb'))->toBe('reverb:start', 'o serviço reverb do compose deveria rodar reverb:start'),
            'pulse_compose'  => expect($rotulo)->toContain('pulse:check')
                ->and(comandoDoServico($compose, 'pulse'))->toBe('pulse:check'),
            'queue_listen' => test()->assertStringContainsString(
                filaPadraoNoFonteDoQueue((string) file_get_contents(config_path('queue.php'))),
                $rotulo,
                'queue_listen deveria afirmar a fila padrão, lida do fonte de config/queue.php — nunca de config() (sync não tem fila no phpunit.xml)',
            ),
            'pail'  => expect($rotulo)->toContain($idioma === 'en' ? 'off Windows' : 'fora do Windows'),
            default => throw new RuntimeException("nó desconhecido: {$no}"),
        };
    }
})->with(['queue_worker', 'scheduler_container', 'reverb_compose', 'pulse_compose', 'queue_listen', 'pail']);

it('[CT-111] os processos do DG-19 ficam vermelhos quando o comando muda no código ou o rótulo muda no bloco', function (string $mundo, string $bloco, bool $aceitaEsperado, string $nomeando): void {
    $composeFonte = (string) file_get_contents(base_path('docker-compose.yml'));
    $blocoPt      = blocoDoCatalogoNaArvore('DG-19', 'pt')['bloco'];
    $blocoEn      = blocoDoCatalogoNaArvore('DG-19', 'en')['bloco'];

    switch ($mundo) {
        case 'nada alterado':
            break;

        case 'compose com --queue=default,ai,ai-post no serviço queue':
            $composeFonte = str_replace('--queue=ai,ai-post,default', '--queue=default,ai,ai-post', $composeFonte);
            break;

        case 'compose com --queue=ai,ai-post,ai-embed,default no serviço queue':
            $composeFonte = str_replace('--queue=ai,ai-post,default', '--queue=ai,ai-post,ai-embed,default', $composeFonte);
            break;

        case 'compose com o command do scheduler em schedule:run':
            $composeFonte = str_replace("'schedule:work'", "'schedule:run'", $composeFonte);
            break;

        default:
            throw new RuntimeException("mundo desconhecido: {$mundo}");
    }

    switch ($bloco) {
        case 'real':
            break;

        case 'com o queue_listen com ai,ai-post,default':
            $blocoPt = str_replace('queue:listen (--queue=default)', 'queue:listen (--queue=ai,ai-post,default)', $blocoPt);
            $blocoEn = str_replace('queue:listen (--queue=default)', 'queue:listen (--queue=ai,ai-post,default)', $blocoEn);
            break;

        case 'com o nó pail sem a condição de plataforma':
            $blocoPt = str_replace('pail["pail (fora do Windows)"]', 'pail["pail"]', $blocoPt);
            $blocoEn = str_replace('pail["pail (off Windows)"]', 'pail["pail"]', $blocoEn);
            break;

        case 'com o nó horizon no subgraph Docker Compose':
            $blocoPt = str_replace('pulse_compose["pulse (pulse:check)"]', "pulse_compose[\"pulse (pulse:check)\"]\n    horizon[\"horizon\"]", $blocoPt);
            $blocoEn = str_replace('pulse_compose["pulse (pulse:check)"]', "pulse_compose[\"pulse (pulse:check)\"]\n    horizon[\"horizon\"]", $blocoEn);
            break;

        default:
            throw new RuntimeException("bloco desconhecido: {$bloco}");
    }

    $resultado = conferirProcessosDg19($blocoPt, $blocoEn, $composeFonte);

    expect($resultado['aceita'])->toBe($aceitaEsperado, (string) ($resultado['motivo'] ?? ''));

    if (! $aceitaEsperado && $nomeando !== '') {
        expect((string) $resultado['motivo'])->toContain($nomeando);
    }
})->with([
    ['nada alterado', 'real', true, ''],
    ['compose com --queue=default,ai,ai-post no serviço queue', 'real', false, 'default,ai,ai-post'],
    ['compose com --queue=ai,ai-post,ai-embed,default no serviço queue', 'real', false, 'ai-embed'],
    ['nada alterado', 'com o queue_listen com ai,ai-post,default', false, ''],
    ['nada alterado', 'com o nó pail sem a condição de plataforma', false, 'pail'],
    ['nada alterado', 'com o nó horizon no subgraph Docker Compose', false, 'horizon'],
    ['compose com o command do scheduler em schedule:run', 'real', false, 'scheduler'],
]);

/*
|--------------------------------------------------------------------------
| R52 — DG-20: controles contra a tabela literal de CT-114
|--------------------------------------------------------------------------
| CT-114 (o GET real, as 5 linhas) mora em tests/Tenancy — este arquivo só usa a mesma tabela como
| DADO LITERAL (a wiki manda: "as cinco linhas de CT-114 como literal"), sem precisar da tenancy.
*/

/**
 * As cinco linhas de CT-114, como literal (situação da organização, persona, vínculo, status
 * esperado) — replicadas aqui como DADO, não como oráculo derivado: o oráculo de CT-114 é o GET
 * real, em tests/Tenancy; aqui elas só alimentam os CONTROLES sobre a condição do alt.
 *
 * @return list<array{situacaoOrg: string, persona: string, vinculo: string, statusEsperado: int}>
 */
function linhasDeCt114(): array
{
    return [
        ['situacaoOrg' => 'ativa', 'persona' => 'operador', 'vinculo' => 'com', 'statusEsperado' => 200],
        ['situacaoOrg' => 'ativa', 'persona' => 'operador', 'vinculo' => 'sem', 'statusEsperado' => 404],
        ['situacaoOrg' => 'inativa', 'persona' => 'operador', 'vinculo' => 'com', 'statusEsperado' => 404],
        ['situacaoOrg' => 'inativa', 'persona' => 'master_global', 'vinculo' => 'sem', 'statusEsperado' => 404],
        ['situacaoOrg' => 'ativa', 'persona' => 'master_global', 'vinculo' => 'sem', 'statusEsperado' => 200],
    ];
}

/**
 * A condição do `alt` do DG-20 cobre esta linha? `$notaMasterGlobalSempreEntra` representa a
 * adulteração "nota master_global sempre entra antes do alt" — o master_global nunca cai no ramo
 * que nega, mesmo quando a organização está inativa (o que o código NÃO permite: a inativa vem
 * antes do master_global).
 */
function condicaoDoAltCobre(string $condicao, bool $notaMasterGlobalSempreEntra, array $linha): bool
{
    $inativa    = $linha['situacaoOrg'] === 'inativa';
    $master     = $linha['persona'] === 'master_global';
    $semVinculo = $linha['vinculo'] === 'sem';

    if ($notaMasterGlobalSempreEntra && $master) {
        return false;
    }

    return match ($condicao) {
        'organização inativa, ou sem vínculo e não é master_global'  => $inativa || ($semVinculo && ! $master),
        'organização inativa'                                        => $inativa,
        'sem vínculo'                                                => $semVinculo,
        default                                                      => throw new RuntimeException("condição desconhecida: {$condicao}"),
    };
}

/**
 * A guarda dos controles: para as 5 linhas de CT-114, a condição do alt (+ o status do ramo que
 * nega) precisa cobrir EXATAMENTE as linhas cujo status esperado é 404 — nem mais (M3), nem menos
 * (M5) —, e o ramo que nega precisa responder 404, nunca 403 (M1: 403 confirmaria que a organização
 * existe).
 *
 * @return array{aceita: bool, motivo: ?string}
 */
function conferirControlesDg20(string $condicao, int $statusDoRamoQueNega, bool $notaMasterGlobalSempreEntra): array
{
    if ($statusDoRamoQueNega !== 404) {
        return ['aceita' => false, 'motivo' => "o ramo que nega responde {$statusDoRamoQueNega}, e deveria responder 404"];
    }

    foreach (linhasDeCt114() as $linha) {
        $cobreEsperado = $linha['statusEsperado'] === 404;
        $cobre         = condicaoDoAltCobre($condicao, $notaMasterGlobalSempreEntra, $linha);

        if ($cobre !== $cobreEsperado) {
            $descricao = "{$linha['situacaoOrg']} × {$linha['persona']} × {$linha['vinculo']} vínculo";

            return ['aceita' => false, 'motivo' => "a linha \"{$descricao}\" (status esperado {$linha['statusEsperado']}) não bate: a condição do alt ".($cobre ? 'cobre' : 'não cobre').' esta linha'];
        }
    }

    return ['aceita' => true, 'motivo' => null];
}

it('[CT-115] controles do DG-20 contra a tabela literal de CT-114', function (string $condicao, int $status, bool $notaMasterGlobalSempreEntra, bool $aceitaEsperado, string $nomeando): void {
    $resultado = conferirControlesDg20($condicao, $status, $notaMasterGlobalSempreEntra);

    expect($resultado['aceita'])->toBe($aceitaEsperado, (string) ($resultado['motivo'] ?? ''));

    if (! $aceitaEsperado && $nomeando !== '') {
        expect((string) $resultado['motivo'])->toContain($nomeando);
    }
})->with([
    ['organização inativa, ou sem vínculo e não é master_global', 404, false, true, ''],
    ['organização inativa', 404, false, false, 'sem'],
    ['sem vínculo', 404, false, false, 'inativa × operador × com vínculo'],
    ['organização inativa, ou sem vínculo e não é master_global', 403, false, false, '403'],
    ['organização inativa, ou sem vínculo e não é master_global', 404, true, false, 'inativa × master_global'],
]);

/*
|--------------------------------------------------------------------------
| R53 — CT-116: a promessa da página vale para os 20 DGs, em pt e em en
|--------------------------------------------------------------------------
*/

/**
 * O par (termo real, termo adulterado) por DG, para os 17 DGs cujo "fato declarado" é uma simples
 * presença de um termo do CÓDIGO (nome de classe, tabela, comando ou chave de config) que o bloco
 * publicado cita — idêntico em pt e em en porque identificadores de código não se traduzem. Os 3
 * extras (DG-10, DG-19, DG-20) usam um fato de R48/R50/R51 (mais forte que presença de termo — não
 * é o TIPO do bloco, R3.M6) e ficam fora deste mapa; ver `fatoEAdulteracaoDoDg()`.
 *
 * @return array<string, array{a: string, b: string}>
 */
function paresDeAdulteracaoPorDg(): array
{
    return [
        'DG-01' => ['a' => 'ai_runs', 'b' => 'historico_ia'],
        'DG-02' => ['a' => 'KIT_TENANCY', 'b' => 'KIT_MULTIORG'],
        'DG-03' => ['a' => 'master_global', 'b' => 'super_admin'],
        'DG-04' => ['a' => 'canAccessPanel()', 'b' => 'autorizarPainel()'],
        'DG-05' => ['a' => 'DestinoAposLogin', 'b' => 'RoteadorDeLogin'],
        'DG-06' => ['a' => 'KIT_SOCIALITE_VINCULO_CONFIRMAR', 'b' => 'KIT_SOCIALITE_LINK_EXTRA'],
        'DG-07' => ['a' => 'kit:convites-lembrar', 'b' => 'kit:convites-avisar'],
        'DG-08' => ['a' => 'master_global', 'b' => 'super_admin'],
        'DG-09' => ['a' => 'KIT_TENANCY', 'b' => 'KIT_MULTIORG'],
        'DG-11' => ['a' => 'PromptInjectionGuardMiddleware', 'b' => 'FiltroDeInjecaoMiddleware'],
        'DG-12' => ['a' => 'health_check_result_history_items', 'b' => 'health_check_snapshots'],
        'DG-13' => ['a' => 'vinculos_sociais', 'b' => 'contas_sociais'],
        'DG-14' => ['a' => 'KIT_TENANCY', 'b' => 'KIT_MULTIORG'],
        'DG-15' => ['a' => 'kit:install', 'b' => 'kit:configurar'],
        'DG-16' => ['a' => 'CAMINHOS_DO_KIT', 'b' => 'ROTAS_DO_KIT'],
        'DG-17' => ['a' => 'CAMINHOS_DO_KIT', 'b' => 'ROTAS_DO_KIT'],
        'DG-18' => ['a' => '.env.docker', 'b' => '.env.container'],
    ];
}

/**
 * A ordem do DG-20 é a que o código executa: `identify_tenant` fala com `can_access_tenant` ANTES
 * de falar com `definir_tenant` (o fato de R51 escolhido para este cenário — ver a nota de R53 no
 * `04`: o de R52 reprovaria hoje o bloco publicado pela linha 5 de CT-114, sem dizer nada sobre
 * fato vácuo).
 */
function ordemDg20EhCorreta(string $bloco): bool
{
    $indiceConsulta = null;
    $indiceDefinir  = null;

    foreach (mensagensDeSequencia($bloco) as $i => $m) {
        if ($m['de'] === 'identify_tenant' && $m['para'] === 'can_access_tenant') {
            $indiceConsulta ??= $i;
        }

        if ($m['de'] === 'identify_tenant' && $m['para'] === 'definir_tenant') {
            $indiceDefinir ??= $i;
        }
    }

    return $indiceConsulta !== null && $indiceDefinir !== null && $indiceConsulta < $indiceDefinir;
}

/**
 * Troca os DESTINOS de duas mensagens de sequência (`->>destinoA:` <-> `->>destinoB:`) — uma
 * involução: aplicar duas vezes devolve o bloco original. É a adulteração de R53 para o DG-20 ("a
 * mensagem a definir_tenant antes da consulta a can_access_tenant"): sem mover linha nenhuma, só
 * troca QUEM cada mensagem já existente alcança, então a cópia difere do bloco real só por isso.
 */
function trocarDestinosDeMensagem(string $bloco, string $destinoA, string $destinoB): string
{
    $marcador = "\0TROCA\0";
    $bloco    = str_replace("->>{$destinoA}:", $marcador, $bloco);
    $bloco    = str_replace("->>{$destinoB}:", "->>{$destinoA}:", $bloco);

    return str_replace($marcador, "->>{$destinoB}:", $bloco);
}

/**
 * O fato declarado do DG, a cópia adulterada dele e como desfazer a adulteração (para provar que a
 * cópia difere do bloco real só por ela — o `Então` "cada cópia difere... só pelo trecho
 * adulterado").
 *
 * @return array{fato: callable(string): bool, copia: string, desfazer: callable(string): string}
 */
function fatoEAdulteracaoDoDg(string $dg, string $idioma, string $bloco): array
{
    if ($dg === 'DG-10') {
        $par = $idioma === 'en'
            ? ['a' => '5 wrong attempts', 'b' => '3 wrong attempts']
            : ['a' => '5 tentativas erradas', 'b' => '3 tentativas erradas'];

        return [
            'fato' => static function (string $b): bool {
                $transicao = transicaoComNumero(transicoesDeEstado($b), 'bloqueada', 'encerrada');

                return $transicao !== null
                    && $transicao['numero'] === Filament::getPanel('admin')->getPlugin('filament-lockscreen')->getRateLimitLimit();
            },
            'copia'    => str_replace($par['a'], $par['b'], $bloco),
            'desfazer' => static fn (string $c): string => str_replace($par['b'], $par['a'], $c),
        ];
    }

    if ($dg === 'DG-19') {
        $par = ['a' => 'ai,ai-post,default', 'b' => 'default,ai,ai-post'];

        return [
            'fato' => static function (string $b): bool {
                $filaReal = filaDoServico((string) file_get_contents(base_path('docker-compose.yml')), 'queue');

                return $filaReal !== '' && str_contains($b, "--queue={$filaReal}");
            },
            'copia'    => str_replace($par['a'], $par['b'], $bloco),
            'desfazer' => static fn (string $c): string => str_replace($par['b'], $par['a'], $c),
        ];
    }

    if ($dg === 'DG-20') {
        return [
            'fato'     => static fn (string $b): bool => ordemDg20EhCorreta($b),
            'copia'    => trocarDestinosDeMensagem($bloco, 'can_access_tenant', 'definir_tenant'),
            'desfazer' => static fn (string $c): string => trocarDestinosDeMensagem($c, 'can_access_tenant', 'definir_tenant'),
        ];
    }

    $par = paresDeAdulteracaoPorDg()[$dg] ?? null;

    if ($par === null) {
        throw new RuntimeException("sem par de adulteração para {$dg}");
    }

    return [
        'fato'     => static fn (string $b): bool => str_contains($b, $par['a']),
        'copia'    => str_replace($par['a'], $par['b'], $bloco),
        'desfazer' => static fn (string $c): string => str_replace($par['b'], $par['a'], $c),
    ];
}

it('[CT-116] o fato declarado de cada DG do catálogo aceita os blocos publicados e reprova a cópia adulterada deles, nos dois idiomas', function (): void {
    $dgs = array_map(static fn (int $n): string => sprintf('DG-%02d', $n), range(1, 20));

    $conferidos = 0;

    foreach ($dgs as $dg) {
        foreach (['pt', 'en'] as $idioma) {
            $blocoDoCatalogo = blocoDoCatalogoNaArvore($dg, $idioma);
            expect($blocoDoCatalogo)->not->toBeNull("{$dg} em {$idioma} deveria existir no catálogo publicado");

            $bloco = $blocoDoCatalogo['bloco'];

            ['fato' => $fato, 'copia' => $copia, 'desfazer' => $desfazer] = fatoEAdulteracaoDoDg($dg, $idioma, $bloco);

            expect($fato($bloco))->toBeTrue("o fato declarado de {$dg} ({$idioma}) deveria aceitar o bloco publicado");
            expect($fato($copia))->toBeFalse("o fato declarado de {$dg} ({$idioma}) deveria reprovar a cópia adulterada");
            expect($copia)->not->toBe($bloco, "a adulteração de {$dg} ({$idioma}) deveria mudar o bloco publicado");
            expect($desfazer($copia))->toBe($bloco, "a cópia adulterada de {$dg} ({$idioma}) deveria diferir do bloco real só pelo trecho adulterado");

            $conferidos++;
        }
    }

    expect($conferidos)->toBe(40, 'são 20 DGs × 2 idiomas — o piso de "presença não vácua" desta regra');
});
