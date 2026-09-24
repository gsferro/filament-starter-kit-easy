<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Lê o Clover, aplica o piso e mantém o badge honesto
|--------------------------------------------------------------------------
|
| Vive em `.github/` porque é infraestrutura DO KIT: o diretório inteiro é
| `export-ignore` no `.gitattributes`, então não viaja para quem instala. O
| projeto que nasce do kit mede a própria cobertura, não a nossa.
|
| ## Uso
|
|   php .github/cobertura.php cobertura.xml --min=78            # verifica (CI)
|   php .github/cobertura.php cobertura.xml --min=78 --write    # grava o badge
|
| ## Por que o badge guarda o percentual INTEIRO
|
| O número real move a cada rodada — uma linha a mais em `app/` muda a terceira
| casa. Se o JSON guardasse `79,79 %`, o job reprovaria por ruído e a guarda
| viraria alarme falso, que é como guarda morre.
|
| Guardando o inteiro truncado, o JSON só muda quando a cobertura cruza um ponto
| percentual — ~98 statements nesta árvore. Quem gatilha o gate de verdade é o
| `--min`, não o badge.
|
| ## O que este arquivo NÃO faz
|
| Não commita nada. O `.github/badges/cobertura.json` é versionado à mão, e o CI
| só reprova quando ele mente — ver ADR-04 em
| `wikis/specs/feat/cobertura-de-testes/cobertura-de-testes/`.
*/

$argumentos = $argv;
array_shift($argumentos);

$clover = null;
$piso = null;
$gravar = false;

foreach ($argumentos as $argumento) {
    if (str_starts_with($argumento, '--min=')) {
        $piso = (float) substr($argumento, 6);

        continue;
    }

    if ($argumento === '--write') {
        $gravar = true;

        continue;
    }

    $clover = $argumento;
}

if ($clover === null || ! is_file($clover)) {
    fwrite(STDERR, "uso: php .github/cobertura.php <clover.xml> --min=NN [--write]\n");
    fwrite(STDERR, "relatório Clover não encontrado: ".var_export($clover, true)."\n");
    fwrite(STDERR, "gere-o com: composer test:coverage\n");

    exit(2);
}

$xml = simplexml_load_file($clover);

if ($xml === false) {
    fwrite(STDERR, "Clover ilegível: {$clover}\n");

    exit(2);
}

$metricas = $xml->project->metrics ?? null;

if ($metricas === null) {
    fwrite(STDERR, "Clover sem <project><metrics>: {$clover}\n");

    exit(2);
}

$total = (int) $metricas['statements'];
$cobertas = (int) $metricas['coveredstatements'];

if ($total === 0) {
    fwrite(STDERR, "Clover com zero statements — o denominador `app/` não foi medido\n");

    exit(2);
}

$percentual = 100 * $cobertas / $total;
$inteiro = (int) floor($percentual);

printf("cobertura: %.2f%%  (%d / %d statements)\n", $percentual, $cobertas, $total);

$badge = [
    'schemaVersion' => 1,
    'label' => 'cobertura',
    'message' => $inteiro.'%',
    'color' => match (true) {
        $inteiro >= 80 => 'brightgreen',
        $inteiro >= 70 => 'green',
        $inteiro >= 60 => 'yellow',
        default => 'red',
    },
];

$caminhoDoBadge = __DIR__.'/badges/cobertura.json';
$conteudo = json_encode($badge, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

if ($gravar) {
    file_put_contents($caminhoDoBadge, $conteudo);
    echo "badge gravado: .github/badges/cobertura.json → {$inteiro}%\n";
}

$falhas = [];

if ($piso !== null && $percentual < $piso) {
    $falhas[] = sprintf(
        'cobertura %.2f%% abaixo do piso de %.0f%% — ver a meta no ADR-06',
        $percentual,
        $piso,
    );
}

if (! $gravar) {
    $commitado = is_file($caminhoDoBadge) ? file_get_contents($caminhoDoBadge) : '';

    if ($commitado !== $conteudo) {
        $mensagemCommitada = json_decode($commitado, true)['message'] ?? '(ausente)';

        $falhas[] = sprintf(
            'o badge commitado diz %s e o medido é %d%% — rode `composer test:coverage` e commite '
            .'`.github/badges/cobertura.json`',
            $mensagemCommitada,
            $inteiro,
        );
    }
}

if ($falhas !== []) {
    fwrite(STDERR, "\n".implode("\n", array_map(static fn (string $f): string => "  ✗ {$f}", $falhas))."\n");

    exit(1);
}

echo "ok: badge confere e o piso foi respeitado\n";
