<?php

use Symfony\Component\Finder\Finder;

/**
 * Toda action de terceiro dos workflows é pinada por SHA — e a próxima que não for, reprova.
 *
 * ## Por que SHA e não tag
 *
 * Uma tag é **móvel**. Quem controla o repositório da action pode reescrever `v4` para outro
 * commit a qualquer momento, e todo pipeline que a usa passa a executar código novo sem que
 * nada no repositório consumidor mude. Foi assim que o `tj-actions/changed-files` vazou segredo
 * de milhares de pipelines em março de 2025: a tag foi reescrita, e quem pinava por ela
 * executou o código do atacante no minuto seguinte.
 *
 * Um SHA de 40 caracteres não se reescreve.
 *
 * ## O que este caso protege que a correção sozinha não protegia
 *
 * O Plumb acusou **10 de 23** referências não pinadas — seis `actions/cache@v4` no `ci.yml` e as
 * quatro ações do `pages.yml`. Corrigir as dez fecha o caso e **deixa a classe aberta**: o
 * próximo workflow, ou o próximo passo acrescentado a um workflow existente, nasce com `@v4`
 * porque é assim que toda documentação de action escreve o exemplo.
 *
 * Esta guarda é o que transforma uma correção pontual em invariante.
 *
 * ## O que ela deliberadamente NÃO exige
 *
 * Ação do próprio repositório (`uses: ./.github/actions/...`) fica de fora: ela é versionada
 * junto, e exigir SHA dela seria pedir que o repositório se pinasse a si mesmo.
 */
it('[CT-01] toda action de terceiro dos workflows esta pinada por SHA', function (): void {
    $naoPinadas = [];
    $pinadas    = 0;

    foreach (Finder::create()->files()->in(base_path('.github/workflows'))->name('*.yml') as $workflow) {
        $relativo = '.github/workflows/'.$workflow->getFilename();

        preg_match_all('~^\s*-?\s*uses:\s*(\S+)~m', (string) $workflow->getContents(), $usos);

        foreach ($usos[1] as $referencia) {
            /*
             * Ação local não tem o que pinar: ela viaja no mesmo commit que a usa.
             */
            if (str_starts_with($referencia, './') || str_starts_with($referencia, 'docker://')) {
                continue;
            }

            if (preg_match('~@[0-9a-f]{40}$~', $referencia) === 1) {
                $pinadas++;

                continue;
            }

            $naoPinadas[] = $relativo.' → '.$referencia;
        }
    }

    /*
     * Sentinela contra a falha silenciosa desta própria varredura: se o regex parar de casar
     * (indentação diferente, `uses` numa chave aninhada), o laço roda zero vezes e o caso ficaria
     * **verde sem ter conferido nada** — que é o modo mais barato de uma guarda morrer.
     */
    expect($pinadas)->toBeGreaterThan(10, 'a varredura de `uses:` não achou referências pinadas — o regex parou de casar');

    $this->assertSame(
        [],
        $naoPinadas,
        'estas actions de terceiro não estão pinadas por SHA — uma tag é móvel, e quem a controla '
        ."pode reescrevê-la para outro commit a qualquer momento:\n  ".implode("\n  ", $naoPinadas),
    );
})->skip(fn (): bool => ! naArvoreDoKit(), 'Os workflows são `export-ignore`: o CI é do kit, não do projeto que nasce dele.')->group('kit');

/**
 * Todo SHA pinado carrega, ao lado, o rótulo da versão que ele representa.
 *
 * Sem o comentário, `@3d3c42e5aac5ba805825da76410c181273ba90b1` é ilegível: ninguém sabe se está
 * numa versão antiga sem ir ao GitHub conferir, e por isso ninguém confere. O rótulo é o que
 * permite ler o diff de um bump do Dependabot e entender o que mudou.
 *
 * É a mesma razão de o badge de cobertura guardar o percentual em vez de um SVG opaco: número que
 * ninguém consegue ler é número que ninguém audita.
 */
it('[CT-02] todo SHA pinado tem o rotulo da versao ao lado', function (): void {
    $semRotulo = [];

    foreach (Finder::create()->files()->in(base_path('.github/workflows'))->name('*.yml') as $workflow) {
        $relativo = '.github/workflows/'.$workflow->getFilename();

        foreach (preg_split('~\R~', (string) $workflow->getContents()) ?: [] as $numero => $linha) {
            if (preg_match('~uses:\s*(\S+@[0-9a-f]{40})~', $linha, $casado) !== 1) {
                continue;
            }

            if (preg_match('~#\s*v?\d~', $linha) === 1) {
                continue;
            }

            $semRotulo[] = $relativo.':'.($numero + 1).' → '.$casado[1];
        }
    }

    $this->assertSame(
        [],
        $semRotulo,
        "estes SHAs não dizem que versão representam — acrescente `# vX.Y.Z` no fim da linha:\n  "
        .implode("\n  ", $semRotulo),
    );
})->skip(fn (): bool => ! naArvoreDoKit(), 'Os workflows são `export-ignore`: o CI é do kit, não do projeto que nasce dele.')->group('kit');
