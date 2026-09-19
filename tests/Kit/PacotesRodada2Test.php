<?php

use Composer\Semver\Semver;

/**
 * O que a rodada 2 de estudo de pacotes deixou verificável.
 *
 * Duas coisas muito diferentes moram aqui, e só uma delas é código:
 *
 *   1. A **constraint do Filament** (R11). É uma linha do `composer.json`, e ela decide se um
 *      `composer update` de rotina traz a correção de segurança da série ou um major novo.
 *   2. O **registro documental das dez decisões** (R12). Não é comportamento — é o artefato que
 *      impede a próxima varredura de reavaliar do zero dez pacotes já avaliados, e de adotar por
 *      engano um que foi recusado por motivo de segurança.
 *
 * IDs de CT em
 * `wikis/specs/feat/estudo-de-pacotes-rodada-2/estudo-de-pacotes-rodada-2/04-casos-de-teste.md`.
 */

/** @return array<string, mixed> */
function composerDoKit(): array
{
    return json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Os dez pacotes avaliados na rodada, com o veredito VIGENTE de cada um.
 *
 * A lista é declarada aqui, e não derivada da própria página: derivá-la faria o caso afirmar que a
 * página concorda consigo mesma, e uma página vazia passaria.
 *
 * `mortalkiller/filament-page-header` saiu de ADIAR para ADOTAR em 2026-09-19, quando o motivo de
 * peso caiu — o kit subiu para o Filament 5.8.2, que a constraint dele exige. O veredito muda
 * AQUI, e o pacote não sai do registro: os outros nove continuam precisando da proteção, e um
 * deles (`matondojk/filament-avatar-picker`) foi recusado por vazar foto de perfil entre
 * organizações. Ver ADR-08 de `wikis/specs/feat/page-header-nas-telas-de-registro/`.
 *
 * @return array<string, string>
 */
function pacotesDaRodada2(): array
{
    return [
        'mortalkiller/filament-page-header'          => 'ADOTAR',
        'jeffersongoncalves/filament-page-visits'    => 'ADIAR',
        'jeffersongoncalves/filament-ban'            => 'RECUSAR',
        'packstub/filament-flow'                     => 'ADIAR',
        'syofyanzuhad/filament-connection-indicator' => 'RECUSAR',
        'matondojk/filament-avatar-picker'           => 'RECUSAR',
        'vaslv/filament-app-version'                 => 'ADIAR',
        'ronssij/filament-simple-draft'              => 'RECUSAR',
        'yousefaman/filament-autosave'               => 'ADIAR',
        'alexkramse/filament-openapi-docs'           => 'RECUSAR',
    ];
}

/*
|--------------------------------------------------------------------------
| R11 — a constraint do Filament
|--------------------------------------------------------------------------
*/

/**
 * CT-32 — o kit declara o Filament em caret na série 5, e só nela.
 *
 * As quatro asserções cobrem as partições que o Adendo 1 do requisito trata explicitamente, e
 * nenhuma delas é sobre a SINTAXE da constraint: o caso pergunta **quais versões ela aceita**, que
 * é o que RQ-14 compra. `>=5.7 <6.0` satisfaz o requisito e reprovaria num oráculo de sintaxe.
 *
 *   M46 — `*`: "travar ao contrário", e um major novo entra sozinho num `composer update`.
 *   M47 — versão exata ("para estabilizar"), que é literalmente o que RQ-14 proíbe.
 *   M61 — a constraint COMPOSTA (`^5.6 || ^6.0`), que começa por `^5.`, não é `*`, não é exata, e
 *         passava no cenário inteiro enquanto deixa o próximo major entrar sem revisão.
 */
it('[CT-32] declara o filament em caret na serie 5 e so nela', function (): void {
    $constraint = composerDoKit()['require']['filament/filament'] ?? null;

    expect($constraint)->not->toBeNull('o kit deixou de declarar filament/filament');

    $instalada = collect(json_decode((string) file_get_contents(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR)['packages'])
        ->firstWhere('name', 'filament/filament')['version'] ?? null;

    expect($instalada)->not->toBeNull('filament/filament não está no composer.lock');

    $versao = ltrim((string) $instalada, 'v');

    expect(Semver::satisfies($versao, $constraint))
        ->toBeTrue("a constraint {$constraint} não aceita a versão instalada {$versao}")
        // Toda versão seguinte da série 5: é o que faz a correção de segurança chegar sozinha.
        ->and(Semver::satisfies('5.99.99', $constraint))->toBeTrue()
        // E nenhum major novo, que é a premissa (a) do Adendo 1.
        ->and(Semver::satisfies('6.0.0', $constraint))->toBeFalse()
        // Nem só a versão instalada: uma constraint exata trava a série inteira.
        ->and(Semver::satisfies('5.7.7', $constraint) || Semver::satisfies('5.99.99', $constraint))->toBeTrue();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R12 — as dez decisões registradas, e nenhuma adotada
|--------------------------------------------------------------------------
*/

/**
 * CT-33 — os dez pacotes têm veredito registrado, pelo NOME COMPOSER.
 *
 * Duas metades, com fontes diferentes, e as duas precisam estar:
 *
 *   O **nome Composer** (M48) — o registro que usasse o slug do diretório (`filament-ban` em vez
 *   de `jeffersongoncalves/filament-ban`) não é encontrável pela próxima varredura, que procura
 *   pelo nome que o Packagist devolve. Os dez divergem nesta rodada.
 *
 *   O **veredito** (M49, e o achado da rodada 2) — sem a coluna, um pacote marcado com o veredito
 *   errado passa: `mortalkiller/filament-page-header` como RECUSAR passaria, apesar de o `00`
 *   (Adendo 1) registrar ADIAR, e essa linha específica **é** requisito.
 *
 * O veredito é casado na MESMA LINHA do nome, e não em qualquer lugar da página: as palavras
 * ADIAR e RECUSAR aparecem dezenas de vezes num documento que compara dez pacotes.
 */
it('[CT-33] registra o veredito de cada um dos dez pacotes, pelo nome composer', function (string $pacote, string $veredito): void {
    $pagina = (string) file_get_contents(base_path('wikis/pacotes-candidatos.md'));

    $linhas = array_values(array_filter(
        explode("\n", $pagina),
        fn (string $linha): bool => str_contains($linha, "`{$pacote}`"),
    ));

    expect($linhas)->not->toBeEmpty("o pacote {$pacote} não aparece pelo nome Composer no registro da rodada");

    /*
     * `str_contains` embrulhado, e não `toContain($agulha, $mensagem)`: `toContain()` é VARIÁDICO
     * — o segundo argumento entra como outra agulha e o caso passa a exigir a própria mensagem de
     * erro dentro do arquivo. `.ai/rules/testes.md` registra essa armadilha, e ela custou uma volta
     * aqui também.
     */
    expect(str_contains(implode("\n", $linhas), "**{$veredito}**"))->toBeTrue(
        "o veredito registrado para {$pacote} não é {$veredito}",
    );
})->with(collect(pacotesDaRodada2())
    // Dataset associativo entrega só o VALOR ao caso; o nome do pacote precisa chegar como
    // argumento, então cada linha vira o par `[pacote, veredito]` com a chave só como rótulo.
    ->map(fn (string $veredito, string $pacote): array => [$pacote, $veredito])
    ->all())->group('kit');

/**
 * CT-34 — os NOVE não adotados continuam fora das dependências; o adotado está dentro.
 *
 * A asserção de ausência tem alvo dos dois lados: a lista é declarada no próprio caso, e o
 * `composer.json` do kit tem dezenas de dependências diretas — o caso compara dois conjuntos não
 * vazios. "Nenhuma dependência nova" genérico seria vácuo, e é exatamente o formato que deixaria
 * M50 (um dos avaliados entrando junto com a entrega) passar.
 *
 * A varredura cobre `require` e `require-dev`: recusado por vazamento de foto entre organizações
 * não vira aceitável por estar no bloco de desenvolvimento.
 *
 * ## A armadilha que ESTE caso cometeu, e que ele mesmo documentava três funções acima
 *
 * Até 2026-09-19 a asserção era `expect($declaradas)->not->toContain($pacote, "mensagem")`. O
 * `toContain()` do Pest é VARIÁDICO: a mensagem entrava como SEGUNDA AGULHA, e `not` sobre duas
 * agulhas passa quando ao menos uma está ausente — a mensagem nunca está no `composer.json`, logo
 * o caso passava SEMPRE. Medido: com `mortalkiller/filament-page-header` já no `require`, os doze
 * casos do arquivo ficaram verdes.
 *
 * O caso existia para impedir que um pacote avaliado entrasse nas dependências, e **nunca foi
 * capaz de detectar isso**. O que o expôs foi a adoção deste pacote: o caso deveria ter ficado
 * vermelho por desenho, e não ficou.
 *
 * A forma correta é `toBeIn()` invertido — um valor, uma agulha — ou `in_array()` embrulhado. Aqui
 * é `in_array()`, pelo mesmo motivo que o CT-33 usa `str_contains()`: a mensagem fica no
 * `expect()`, longe da lista de agulhas.
 */
it('[CT-34] mantem fora das dependencias os nove pacotes nao adotados', function (): void {
    $composer = composerDoKit();

    $declaradas = array_keys([
        ...($composer['require'] ?? []),
        ...($composer['require-dev'] ?? []),
    ]);

    $naoAdotados = array_keys(array_filter(
        pacotesDaRodada2(),
        static fn (string $veredito): bool => $veredito !== 'ADOTAR',
    ));

    expect($declaradas)->not->toBeEmpty()
        ->and($naoAdotados)->toHaveCount(9);

    foreach ($naoAdotados as $pacote) {
        expect(in_array($pacote, $declaradas, true))->toBeFalse(
            "o pacote {$pacote} nao foi adotado na rodada 2 e entrou nas dependencias",
        );
    }
})->group('kit');

/**
 * CT-35 — o pacote adotado ESTÁ declarado, e no piso seguro.
 *
 * O par positivo do CT-34, e ele não é decoração: sem esta metade, a mudança de veredito de um
 * pacote o tiraria da varredura de ausência e **nada** passaria a afirmar coisa nenhuma sobre ele.
 * O registro continuaria completo na página e vazio no oráculo.
 *
 * O piso `2.1.5` é requisito, não preferência: a v2.1.4 quebra no Filament 5.8.2 — o
 * `.fi-header-actions-ctn` do 5.8.2 passou a trazer `sm:self-end`, que conflita com o layout do
 * pacote. Quem resolvesse 2.1.4 teria as ações do cabeçalho deslocadas, sem erro nenhum.
 */
it('[CT-35] declara o pacote adotado da rodada 2, no piso seguro', function (): void {
    $composer = composerDoKit();

    $adotados = array_keys(array_filter(
        pacotesDaRodada2(),
        static fn (string $veredito): bool => $veredito === 'ADOTAR',
    ));

    expect($adotados)->toBe(['mortalkiller/filament-page-header']);

    $constraint = $composer['require']['mortalkiller/filament-page-header'] ?? null;

    expect($constraint)->not->toBeNull('o pacote adotado na rodada 2 nao esta no require')
        // O piso: a 2.1.4 fica de fora.
        ->and(Semver::satisfies('2.1.4', $constraint))->toBeFalse()
        ->and(Semver::satisfies('2.1.5', $constraint))->toBeTrue()
        // Caret, nao pino: correcao da serie chega sozinha.
        ->and(Semver::satisfies('2.99.99', $constraint))->toBeTrue()
        // E nenhum major novo, num repositorio que ja trocou a API em 24 h.
        ->and(Semver::satisfies('3.0.0', $constraint))->toBeFalse();
})->group('kit');
