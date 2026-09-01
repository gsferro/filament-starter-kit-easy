<?php

/**
 * A tela de configurações documentada nos DOIS READMEs.
 *
 * IDs de CT em `wikis/specs/feat/settings-do-kit/settings-do-kit/04-casos-de-teste.md`.
 *
 * O kit já tem este padrão (`AnexosPrivadosDocumentacaoTest`), e ele existe porque
 * documentação prometida e não escrita é indistinguível de documentação escrita —
 * para todo mundo menos o leitor. RQ-19 pede os dois idiomas, no plural, e o
 * `README.en.md` é o que costuma ficar para trás.
 */

/** O README sem as linhas de citação (`>`), para asserção de AUSÊNCIA. */
function readmeSemCitacao(string $arquivo): string
{
    return implode("\n", array_filter(
        explode("\n", (string) file_get_contents(base_path($arquivo))),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '>'),
    ));
}

/**
 * CT-31 — cada README cita a tela e a regra de precedência.
 *
 * A asserção de PRESENÇA roda sobre o texto cru: citar é o que se quer aqui.
 *
 * As quatro referências não são decoração. A URL é como alguém acha a tela; a
 * regra do banco vencendo o `.env` é a decisão que a wiki chama de mais
 * importante da feature, e quem lê o README sem ela vai editar o `.env` e não
 * entender por que nada muda; a permissão é o que explica um 403; e o aviso de
 * que isto não é o settings de uma organização é literalmente uma cláusula do
 * requisito (RQ-18).
 */
it('documenta a tela de configuracoes nos dois readmes', function (string $arquivo, string $urlDaTela, string $precedencia, string $permissao, string $naoEhTenant): void {
    $texto = (string) file_get_contents(base_path($arquivo));

    expect($texto)->toContain($urlDaTela)
        ->and($texto)->toContain($precedencia)
        ->and($texto)->toContain($permissao)
        ->and($texto)->toContain($naoEhTenant);
})->with([
    'português' => [
        'README.md',
        '/admin/configuracoes-do-kit',
        'O banco vence em tempo de execução',
        'View:ConfiguracoesDoKit',
        '/admin/organizacoes',
    ],
    'inglês' => [
        'README.en.md',
        '/admin/configuracoes-do-kit',
        'The database wins at runtime',
        'View:ConfiguracoesDoKit',
        '/admin/organizacoes',
    ],
])->group('kit');

/**
 * CT-32 — o TODO de virada para settings não sobrevive, e a densidade é explicada.
 *
 * Duas metades, e a segunda é a que a rodada 2 da revisão adversarial cobrou: uma
 * asserção só de AUSÊNCIA passa num README que **apaga** a linha da densidade em
 * silêncio, como se ela tivesse sido entregue. Ausência não distingue apagar de
 * explicar; presença distingue.
 *
 * A asserção de ausência precisa filtrar as linhas de citação, porque o texto novo
 * **cita** o TODO antigo para explicar o que mudou — é a armadilha que
 * `.ai/rules/testes.md` documenta em três casos anteriores deste repositório.
 */
it('substitui o TODO de settings nos dois readmes, explicando a densidade', function (string $arquivo, string $promessaAntiga, string $explicacaoDaDensidade): void {
    expect(readmeSemCitacao($arquivo))->not->toContain($promessaAntiga);

    expect((string) file_get_contents(base_path($arquivo)))->toContain($explicacaoDaDensidade);
})->with([
    'português' => ['README.md', 'TODO:** transformar esses defaults', 'Densidade de tabela não existe no Filament 5'],
    'inglês'    => ['README.en.md', 'TODO:** turn these defaults', 'Table density does not exist in Filament 5'],
])->group('kit');

/**
 * O TODO do código também foi fechado.
 *
 * `ConfiguraFilamentGlobal` era o dono da promessa, e o requisito cita justamente
 * esses TODOs ("agora é o momento da implementação").
 *
 * ⚠️ A asserção é sobre o TEXTO DA PROMESSA, não sobre a palavra `TODO`, e a
 * primeira versão deste caso reprovou por isso: em português, `TODOS` e `TODA`
 * contêm `TODO` como substring, e o trait usa as duas em três lugares ("vale para
 * TODOS", "em TODA tela"). É a mesma família de armadilha que
 * `.ai/rules/testes.md` documenta para asserção de ausência — só que aqui o falso
 * positivo vem do idioma, não do comentário.
 *
 * As duas asserções de presença são o que distingue "fechou explicando" de
 * "apagou": sem elas, remover o docblock inteiro passaria.
 */
it('fecha o TODO de settings no trait de configuracao global do filament', function (): void {
    $trait = (string) file_get_contents(base_path('app/Providers/Concerns/ConfiguraFilamentGlobal.php'));

    expect($trait)->not->toContain('transformar estes defaults')
        ->and($trait)->toContain('/admin/configuracoes-do-kit')
        ->and($trait)->toContain('densidade de tabela');
})->group('kit');

/**
 * CT-11 — cada README diz que a arte usa o nome, e como trocá-la.
 *
 * Wiki `feat/arte-do-login-com-nome-da-aplicacao`, RQ-04. Mora aqui, e não em
 * arquivo próprio, porque `readmeSemCitacao()` já vive neste arquivo: um clone
 * com outro nome é o que `.ai/rules/testes.md` proíbe, e mover o helper para
 * `tests/Pest.php` só para ganhar um arquivo novo não paga.
 *
 * A ausência precisa do FILTRO de citação: o README pode perfeitamente citar o
 * caminho antigo num bloco `>` explicando o que mudou, e citar não é instruir.
 * Antes desta feature o caminho aparecia três vezes por README, sempre como
 * instrução ao leitor — "troque a arte em `public/images/auth/login.svg`". Esse
 * arquivo não existe mais, e é essa mentira que o caso falsifica.
 *
 * A linha do inglês é a que importa: o `README.en.md` é o que historicamente
 * fica para trás no kit.
 */
it('documenta nos dois readmes que a arte do login usa o nome da aplicacao', function (string $arquivo, string $frase): void {
    $texto = (string) file_get_contents(base_path($arquivo));

    expect($texto)->toContain('APP_NAME')
        ->and($texto)->toContain($frase)
        ->and($texto)->toContain('/admin/configuracoes-do-kit')
        ->and(readmeSemCitacao($arquivo))->not->toContain('public/images/auth/login.svg');
})->with([
    'README.md'    => ['README.md', 'mostra o nome da aplicação'],
    'README.en.md' => ['README.en.md', 'shows the application name'],
])->group('kit');
