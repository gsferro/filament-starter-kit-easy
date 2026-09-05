<?php

/**
 * O `deploy_docker_local.sh`: atualizar a stack Docker na máquina que a hospeda.
 *
 * Três coisas quebram em SILÊNCIO neste script, e é por isso que ele tem teste:
 *
 * 1. A ORDEM `pull` antes de `build`. A imagem do profile `app` é self-contained — o código é
 *    assado nela. Rebuild antes do pull reassa o código velho, sobe verde, e o deploy "funciona"
 *    sem entregar a evolução. Nada no runtime acusa.
 * 2. O `--help`, que imprime o cabeçalho por FAIXA DE LINHA (`sed -n 'A,Bp'`). Acrescentar um
 *    parágrafo ao cabeçalho desloca a faixa: o help passa a cortar a explicação no meio, ou a
 *    vazar código. Nenhum lint vê isso.
 * 3. O BIT DE EXECUÇÃO no índice do git. Sem ele, quem clona não roda `./deploy_docker_local.sh`
 *    — e o modo é dado do índice, que nenhuma revisão de diff mostra.
 *
 * O quarto caso é a paridade dos dois READMEs, a assimetria de sempre: o `README.en.md` é o que
 * fica para trás.
 */

use Illuminate\Support\Facades\Process;

/** O script inteiro, como texto. */
function scriptDeDeploy(): string
{
    return (string) file_get_contents(base_path('deploy_docker_local.sh'));
}

it('[CT-01] assa a imagem DEPOIS de trazer o código novo', function (): void {
    $script = scriptDeDeploy();

    // Só as linhas de comando: o cabeçalho explica a ordem, e casar com ele passaria de graça.
    $comandos = implode("\n", array_filter(
        explode("\n", $script),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '#'),
    ));

    $pull  = strpos($comandos, 'git pull --ff-only');
    $build = strpos($comandos, 'up -d --build');

    expect($pull)->not->toBeFalse('O script não faz `git pull --ff-only`.')
        ->and($build)->not->toBeFalse('O script não faz `up -d --build`.');

    // Asserção à parte, e não `->and(...)`: o encadeamento perde o estreitamento de tipo do PHPStan.
    expect($pull)->toBeLessThan($build, 'O rebuild vem ANTES do pull: a imagem é self-contained e reassaria o código velho.');
})->group('kit');

it('[CT-02] o --help imprime o cabeçalho inteiro, e só o cabeçalho', function (): void {
    $linhas = explode("\n", scriptDeDeploy());

    expect(preg_match("/-h\|--help\) sed -n '(\d+),(\d+)p'/", scriptDeDeploy(), $faixa))
        ->toBe(1, 'O --help deixou de imprimir o cabeçalho por faixa de linha: os casos abaixo não valem mais.');

    [$primeira, $ultima] = [(int) $faixa[1], (int) $faixa[2]];

    expect($primeira)->toBeGreaterThan(1, 'A faixa começa no shebang.')
        ->and($ultima)->toBeGreaterThan($primeira);

    // 1-based no sed, 0-based no array.
    for ($n = $primeira; $n <= $ultima; $n++) {
        expect($linhas[$n - 1])->toStartWith('#', "A linha {$n} está na faixa do --help e não é comentário: o help vaza código.");
    }

    expect($linhas[$ultima])->not->toStartWith('#', 'A faixa do --help para no meio do cabeçalho: o texto seguinte não sai na tela.');

    $ajuda = implode("\n", array_slice($linhas, $primeira - 1, $ultima - $primeira + 1));

    // `toContain()` recebe VÁRIOS needles: uma mensagem como 2º argumento vira needle e reprova sozinha.
    expect(str_contains($ajuda, '--recreate'))->toBeTrue('O --help não explica a única opção do script.');
})->group('kit');

it('[CT-03] está executável no índice do git', function (): void {
    $modo = Process::path(base_path())->run('git ls-files -s deploy_docker_local.sh');

    expect($modo->successful())->toBeTrue('Não consegui ler o índice do git.')
        ->and($modo->output())->toStartWith('100755', 'O script não está executável no índice: quem clona não consegue rodá-lo.');
})->group('kit');

it('[CT-04] os dois READMEs documentam o script e a opção --recreate', function (string $readme): void {
    $texto = (string) file_get_contents(base_path($readme));

    expect(str_contains($texto, './deploy_docker_local.sh'))->toBeTrue("{$readme} não cita o script.")
        ->and(str_contains($texto, '--recreate'))->toBeTrue("{$readme} cita o script e omite a opção que existe.");
})->with(['README.md', 'README.en.md'])->group('kit');
