<?php

use Pest\Mixins\Expectation;

/**
 * As expectativas VARIÁDICAS do Pest — as únicas que não recebem mensagem de falha.
 *
 * ## O que esta suíte protege
 *
 * `toContain(mixed ...$needles)` não tem parâmetro `$message`. Quem passa a explicação da falha
 * no segundo argumento não documenta nada: transforma a mensagem numa string **a procurar**. Na
 * forma negativa — `->not->toContain($x, $msg)` — o defeito é **silencioso**, porque exigir que a
 * mensagem esteja ausente de um HTML é sempre verdade.
 *
 * A regra completa, com o custo medido, está em `.ai/rules/testes.md`.
 *
 * ## Por que por REFLEXÃO e não por varredura dos testes
 *
 * Varrer `tests/` atrás de `->toContain(` com dois argumentos exigiria regex adivinhado sobre
 * código — chamada quebrada em várias linhas, parêntese aninhado, o literal dentro de comentário.
 * É exatamente o que `.ai/rules/testes.md` desaconselha, e o resultado seria um teste que erra
 * nos dois sentidos.
 *
 * A reflexão mede o **motivo** da regra em vez do sintoma dela: enquanto a assinatura do vendor
 * for variádica, a armadilha existe. No dia em que o Pest aceitar mensagem, este caso fica
 * vermelho, e é o sinal de que a regra pode SAIR do `.ai/rules/testes.md` em vez de envelhecer
 * lá dentro como conselho obsoleto. É o mesmo papel de `tests/Kit/OrdemDasCascadeLayersTest.php`
 * para a correção da `v0.37.1`.
 */
it('[CT-01] toContain e toContainEqual continuam sem parametro de mensagem', function (string $metodo): void {
    $parametros = (new ReflectionMethod(Expectation::class, $metodo))->getParameters();

    expect($parametros)->toHaveCount(1, "`{$metodo}()` mudou de assinatura no Pest");

    expect($parametros[0]->isVariadic())->toBeTrue(
        "`{$metodo}()` deixou de ser variádica: reveja a regra do `.ai/rules/testes.md`",
    );

    $temMensagem = collect($parametros)->contains(
        fn (ReflectionParameter $p): bool => $p->getName() === 'message',
    );

    expect($temMensagem)->toBeFalse(
        "`{$metodo}()` passou a aceitar mensagem — a armadilha acabou e a regra do "
        .'`.ai/rules/testes.md` sobre o 2º argumento ser outra agulha pode ser REMOVIDA',
    );
})->with(['toContain', 'toContainEqual'])->group('kit');

/**
 * CT-02 — controle positivo da regra: a esmagadora maioria das expectativas RECEBE mensagem.
 *
 * Sem ele, CT-01 sozinho não distingue "estas duas são a exceção" de "nenhuma expectativa do Pest
 * recebe mensagem" — e a segunda leitura mandaria trocar a suíte inteira por asserção do PHPUnit,
 * que é o oposto do que a regra diz.
 *
 * O número não é afirmado exato de propósito: o Pest acrescenta expectativa a cada versão menor, e
 * travar `74` deixaria o caso vermelho num `composer update` sem nada de errado. O que a regra
 * precisa é da PROPORÇÃO — a mensagem é o padrão, a variádica é a exceção.
 */
it('[CT-02] receber mensagem e o padrao no Pest, e a variadica e a excecao', function (): void {
    $expectativas = collect((new ReflectionClass(Expectation::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject(fn (ReflectionMethod $m): bool => $m->isConstructor());

    $comMensagem = $expectativas->filter(
        fn (ReflectionMethod $m): bool => collect($m->getParameters())
            ->contains(fn (ReflectionParameter $p): bool => $p->getName() === 'message'),
    );

    expect($expectativas->count())->toBeGreaterThan(50, 'a API de expectativas do Pest encolheu demais para esta leitura');

    expect($comMensagem->count() / $expectativas->count())->toBeGreaterThan(
        0.9,
        'receber mensagem deixou de ser o padrão no Pest: a regra do `.ai/rules/testes.md` está '
        .'escrita sobre uma proporção que não vale mais',
    );

    $semMensagem = $expectativas->diff($comMensagem)->map(fn (ReflectionMethod $m): string => $m->getName())->values();

    expect($semMensagem->all())->toEqualCanonicalizing(
        ['toContain', 'toContainEqual'],
        'a lista de expectativas SEM mensagem mudou — a regra do `.ai/rules/testes.md` nomeia '
        .'`toContain` e `toContainEqual`, e precisa ser atualizada',
    );
})->group('kit');
