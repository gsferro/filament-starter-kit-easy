<?php

/**
 * O documento de candidatas a pacote (`wikis/candidatas-a-pacote.md`).
 *
 * IDs de CT em `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/04-casos-de-teste.md`
 * (R14, R15, R16). Tudo aqui lê arquivo da árvore: `wikis/specs/` e `.gitattributes` não viajam
 * para um projeto instalado, então cada caso pula fora da árvore do kit.
 */
const DOCUMENTO_DE_CANDIDATAS = 'wikis/candidatas-a-pacote.md';

/**
 * Os caminhos de `app/` ou `resources/` citados entre crases num trecho de texto, e quais existem.
 *
 * @return array{citados: list<string>, existentes: list<string>}
 */
function caminhosDeCodigoCitados(string $trecho): array
{
    preg_match_all('~`((?:app|resources)/[A-Za-z0-9_/.\-]+)`~', $trecho, $achados);

    $citados = array_values(array_unique($achados[1]));

    return [
        'citados'    => $citados,
        'existentes' => array_values(array_filter($citados, fn (string $caminho): bool => file_exists(base_path($caminho)))),
    ];
}

it('[CT-32] o documento está ao lado do roadmap, declara os dois propósitos numa seção própria e viaja nas duas rotas de entrega', function (): void {
    expect(file_exists(base_path(DOCUMENTO_DE_CANDIDATAS)))->toBeTrue('o documento não existe')
        ->and(DOCUMENTO_DE_CANDIDATAS)->not->toStartWith('wikis/specs')
        ->and(file_exists(base_path('wikis/roadmap.md')))->toBeTrue();

    expect(caminhosDoKit())->toContain(DOCUMENTO_DE_CANDIDATAS);

    $semComentario = array_filter(
        explode("\n", (string) file_get_contents(base_path('.gitattributes'))),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '#'),
    );

    foreach ($semComentario as $linha) {
        if (! str_contains($linha, 'export-ignore')) {
            continue;
        }

        $alvo = trim(explode(' ', trim($linha))[0], '/');

        expect(DOCUMENTO_DE_CANDIDATAS)->not->toStartWith($alvo.'/', "o .gitattributes marca {$alvo} como export-ignore")
            ->and(DOCUMENTO_DE_CANDIDATAS)->not->toBe($alvo);
    }

    $comOsDoisPropositos = array_filter(
        secoesDoMarkdown(DOCUMENTO_DE_CANDIDATAS),
        fn (string $secao): bool => preg_match('/outros? projetos?/iu', $secao) === 1 && preg_match('/contribu/iu', $secao) === 1,
    );

    expect($comOsDoisPropositos)->not->toBeEmpty('nenhuma seção declara o uso por outros projetos e a contribuição na mesma passagem');
})->skip(fn (): bool => ! naArvoreDoKit(), 'wikis/specs e .gitattributes não viajam: fora da árvore do kit não há o que conferir');

it('[CT-33] o documento tem a entrada das preferências de aparência, apontando código que existe', function (): void {
    $secoes = array_filter(
        secoesDoMarkdown(DOCUMENTO_DE_CANDIDATAS),
        fn (string $secao): bool => preg_match('/^[^\n]*prefer[êe]ncias de apar[êe]ncia/iu', $secao) === 1,
    );

    expect($secoes)->not->toBeEmpty('nenhuma seção trata das preferências de aparência por usuário');

    $existentes = array_merge(...array_map(fn (string $secao): array => caminhosDeCodigoCitados($secao)['existentes'], $secoes));

    expect($existentes)->not->toBeEmpty('a seção não cita nenhum caminho de app/ que exista');
})->skip(fn (): bool => ! naArvoreDoKit(), 'wikis/specs não viaja: fora da árvore do kit não há o que conferir');

it('[CT-34] além da aparência, o documento lista outras features do kit, cada uma apontando seu código', function (): void {
    $secao = collect(secoesDoMarkdown(DOCUMENTO_DE_CANDIDATAS))
        ->first(fn (string $s): bool => str_starts_with($s, 'As demais'));

    expect($secao)->not->toBeNull('não há seção das demais candidatas');

    $candidatas = array_values(array_filter(
        explode("\n", (string) $secao),
        fn (string $linha): bool => preg_match('/^\|\s*\d+\s*\|/', $linha) === 1,
    ));

    expect(count($candidatas))->toBeGreaterThanOrEqual(2);

    foreach ($candidatas as $linha) {
        expect(caminhosDeCodigoCitados($linha)['existentes'])->not->toBeEmpty("a candidata sem caminho existente: {$linha}");
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'wikis/specs não viaja: fora da árvore do kit não há o que conferir');
