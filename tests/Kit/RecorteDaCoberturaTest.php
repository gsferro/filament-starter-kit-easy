<?php

use Illuminate\Support\Facades\File;

/**
 * O denominador da cobertura é `app/` inteiro — e este arquivo é o que impede que ele encolha.
 *
 * ## O buraco que ele fecha
 *
 * `phpunit.xml` era a **única** coisa que definia o que entra no cálculo da cobertura, e nada a
 * afirmava. Trocar `<directory>app</directory>` por `<directory>app/Models</directory>`, ou
 * acrescentar um `<exclude>` sobre o diretório de pior cobertura, **sobe o percentual** e passa
 * por todo o CI — inclusive pelo job `cobertura`, que confere o badge contra a medição e ficaria
 * feliz com as duas mentindo juntas.
 *
 * É a forma mais barata de fazer a meta "passar" sem cobrir uma linha, e é irmã da que o
 * `[CT-51]` fechou: lá o número do piso, aqui o conjunto sobre o qual ele é calculado.
 *
 * Achado pela derivação cega do `04-casos-de-teste.md`, como o buraco mais barato de fechar entre
 * os que sobraram do quality gate.
 *
 * ## Por que a asserção é de IGUALDADE de conjuntos
 *
 * A redação óbvia — procurar por `<exclude>` — deixaria passar o estreitamento feito por
 * **inclusão**, que é o mesmo defeito com outra sintaxe. Comparar o recorte declarado com a
 * árvore de verdade fecha as duas portas de uma vez, e uma terceira: o diretório novo que nasça
 * em `app/` e não seja medido.
 */
it('[CT-52] o recorte da cobertura e declarado, e nada o estreita', function (): void {
    $xml = simplexml_load_string((string) file_get_contents(base_path('phpunit.xml')));

    expect($xml)->not->toBeFalse();

    $fonte = $xml->source ?? null;

    /*
     * A existência do `<source>` é afirmada primeiro, e não é formalidade: **sem ele, o PHPUnit
     * mede o projeto inteiro** — `vendor/` junto —, o que é o pior caso possível e o único que
     * uma asserção só sobre `<exclude>` deixaria passar em silêncio.
     */
    $this->assertNotNull($fonte, '`phpunit.xml` não declara `<source>` — sem ele a cobertura mede o projeto inteiro, `vendor/` incluído');

    $incluidos = [];

    foreach ($fonte->include->directory ?? [] as $diretorio) {
        $incluidos[] = trim((string) $diretorio);
    }

    $this->assertSame(
        ['app'],
        $incluidos,
        'o recorte da cobertura deixou de ser `app` inteiro — estreitá-lo sobe o percentual sem cobrir uma linha',
    );

    $excluidos = [];

    foreach ($fonte->exclude->directory ?? [] as $diretorio) {
        $excluidos[] = trim((string) $diretorio);
    }

    foreach ($fonte->exclude->file ?? [] as $arquivo) {
        $excluidos[] = trim((string) $arquivo);
    }

    $this->assertSame(
        [],
        $excluidos,
        'há exclusão declarada no recorte da cobertura — se ela é legítima, declare o motivo aqui e ajuste este caso',
    );
})->skip(fn (): bool => ! naArvoreDoKit(), 'O `phpunit.xml` do projeto instalado é dele, e o recorte que ele mede é escolha de quem instalou.')->group('kit');

/**
 * Todo subdiretório de `app/` presente na árvore entra no cálculo — o caso anterior pelo avesso.
 *
 * O `[CT-52]` prova que a **declaração** não encolheu. Este prova que a declaração **alcança** o
 * que existe: um `app/Dominio/` novo, nascido depois, passa a contar no denominador sem ninguém
 * precisar lembrar de nada — e, se algum dia alguém trocar a declaração por uma lista de
 * diretórios, este caso é o que acusa o primeiro que ficar de fora.
 *
 * Os dois juntos são a igualdade de conjuntos que a derivação pediu: nem menos que `app/`, nem
 * um recorte que finja cobri-lo.
 */
it('[CT-53] todo subdiretorio de app esta dentro do recorte medido', function (): void {
    $xml = simplexml_load_string((string) file_get_contents(base_path('phpunit.xml')));

    $incluidos = [];

    foreach ($xml->source->include->directory ?? [] as $diretorio) {
        $incluidos[] = trim((string) $diretorio);
    }

    $naArvore = collect(File::directories(base_path('app')))
        ->map(fn (string $caminho): string => 'app/'.basename($caminho))
        ->sort()
        ->values();

    expect($naArvore)->not->toBeEmpty();

    $foraDoRecorte = $naArvore->reject(
        fn (string $diretorio): bool => collect($incluidos)->contains(
            fn (string $incluido): bool => $diretorio === $incluido || str_starts_with($diretorio.'/', rtrim($incluido, '/').'/'),
        ),
    )->values()->all();

    $this->assertSame(
        [],
        $foraDoRecorte,
        'estes subdiretórios de `app/` existem na árvore e ficam fora do cálculo da cobertura',
    );
})->skip(fn (): bool => ! naArvoreDoKit(), 'O `phpunit.xml` do projeto instalado é dele, e o recorte que ele mede é escolha de quem instalou.')->group('kit');
