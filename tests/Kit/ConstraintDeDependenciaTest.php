<?php

use Illuminate\Support\Facades\File;

/**
 * Toda dependência declara um teto de major — e o `composer.json` é a fronteira.
 *
 * ## Por que este arquivo existe
 *
 * `spatie/laravel-backup` estava em produção com a constraint `"*"`. Constraint sem teto aceita
 * **qualquer** versão futura, inclusive uma major com mudança de API: um `composer update` de
 * rotina traz código novo de terceiro para dentro do kit **sem aviso e sem revisão**.
 *
 * O `composer validate` já avisava (*"unbound version constraints (*) should be avoided"*), e o
 * aviso passou despercebido por estar junto de outra saída. Aviso que ninguém lê não é guarda;
 * caso de teste é.
 *
 * ## Por que a lista de operadores é FECHADA
 *
 * A guarda nomeia o que **é aceito** (`^`, `~`, `>=…<`, versão exata), e não o que é recusado.
 * Operador novo, alias de branch ou grafia inesperada nasce **reprovando** — é a mesma regra que
 * `.ai/rules/config.md` aplica a interruptor de env, um nível acima: a fronteira falha fechada.
 */
it('nenhuma dependencia de producao tem constraint sem teto de major', function (): void {
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    /*
     * CONTROLE POSITIVO DA VARREDURA, e ele vem primeiro: um `composer.json` que deixasse de ter
     * a chave `require`, ou um filtro que não casasse mais, deixariam a lista de acusados vazia e
     * este caso VERDE sobre nada. O kit tem dezenas de dependências.
     */
    $pacotes = array_filter(
        $composer['require'] ?? [],
        static fn (string $nome): bool => str_contains($nome, '/'),
        ARRAY_FILTER_USE_KEY,
    );

    expect(count($pacotes))->toBeGreaterThan(20, 'a varredura não encontrou dependências — a lista de acusados abaixo mediria o vazio');

    $semTeto = [];

    foreach ($pacotes as $nome => $constraint) {
        if (constraintTemTeto((string) $constraint)) {
            continue;
        }

        $semTeto[] = $nome.' => '.$constraint;
    }

    expect($semTeto)->toBe([], implode("\n", [
        'Estas dependências de produção aceitam qualquer versão futura, inclusive uma major:',
        ...$semTeto,
        '',
        'Um `composer update` traz código novo de terceiro sem aviso. Declare o teto: `^10.3`',
        'para o major corrente, ou `>=1.2 <3` quando duas majors forem de fato suportadas.',
    ]));
})->skip(
    /*
     * Fora da arvore do kit, o `composer.json` e DO PROJETO -- e o kit nao tem o que reprovar ali.
     *
     * Achado pelos quatro cenarios de instalacao da v0.40.0: os cenarios 3 e 4 (`kit:update` a
     * partir da v0.39.1) ficavam VERMELHOS por `spatie/laravel-backup => *`, que foi o proprio kit
     * que embarcou na v0.39.1 e corrigiu na v0.40.0. O `composer.json` esta deliberadamente fora do
     * `kit:update` (`KitUpdate.php:366`), que avisa e manda revisar a mao -- entao quem atualiza
     * recebe o TESTE novo sem a CORRECAO, e a suite dele fica vermelha no dia 1 por algo que ele
     * nao causou e que so ele pode consertar.
     *
     * Instalacao limpa nao tinha o problema: ela ja nasce com o `composer.json` da versao nova.
     *
     * Guarda que dispara por decisao do kit, na arvore de outra pessoa, e alarme falso -- e alarme
     * falso e como guarda morre. Na arvore do kit ela continua valendo com todo o rigor, que e onde
     * a decisao de constraint e de fato tomada.
     */
    fn (): bool => ! naArvoreDoKit(),
    'O `composer.json` de um projeto instalado e dele: as dependencias que ele declara sao escolha de quem instalou.',
)->group('kit');

/**
 * A constraint limita o major que pode entrar?
 *
 * Lista FECHADA de formas aceitas — grafia não prevista reprova, e é isso que se quer.
 */
function constraintTemTeto(string $constraint): bool
{
    foreach (explode('||', $constraint) as $alternativa) {
        $alternativa = trim($alternativa);

        if ($alternativa === '') {
            return false;
        }

        // `*` sozinho, `>=1.0` sem par superior, e `dev-…` sem âncora: sem teto.
        if ($alternativa === '*' || str_starts_with($alternativa, 'dev-')) {
            return false;
        }

        /*
         * Delimitador `#`, e nao `~`: o til aparece DENTRO da classe de caracteres (`[\^~]`,
         * porque `~1.2` e uma das formas aceitas) e fecharia o padrao ali, produzindo
         * `preg_match(): Unknown modifier ']'`. Custou uma rodada vermelha ao escrever isto.
         */
        // `^1.2`, `~1.2`, `1.2.3` e `1.2.*` limitam o major. `>=1 <3` também, pelo par.
        $limitado = preg_match('#^[\^~]\s*\d#', $alternativa) === 1
            || preg_match('~^\d+(\.\d+)*(\.\*)?$~', $alternativa) === 1
            || (str_contains($alternativa, '<') && preg_match('~^>=?\s*\d~', $alternativa) === 1);

        if (! $limitado) {
            return false;
        }
    }

    return true;
}

/**
 * Controle positivo do reconhecedor — sem ele, a ausência acima ficaria verde sobre um
 * `constraintTemTeto()` que dissesse "sim" para tudo.
 *
 * As formas recusadas são as que já apareceram ou apareceriam de verdade; as aceitas são as que o
 * `composer.json` do kit usa hoje.
 */
it('o reconhecedor de teto distingue as duas familias', function (string $constraint, bool $temTeto): void {
    expect(constraintTemTeto($constraint))->toBe($temTeto);
})->with([
    'asterisco sozinho'         => ['*', false],
    'piso sem teto'             => ['>=10.3', false],
    'branch de desenvolvimento' => ['dev-main', false],
    'vazio'                     => ['', false],
    'uma alternativa sem teto'  => ['^10.3 || *', false],

    'circunflexo'              => ['^10.3', true],
    'til'                      => ['~10.3.0', true],
    'versao exata'             => ['10.3.3', true],
    'curinga no patch'         => ['10.3.*', true],
    'intervalo com teto'       => ['>=10.3 <12', true],
    'duas majors suportadas'   => ['^10.3 || ^11.0', true],
])->group('kit');
