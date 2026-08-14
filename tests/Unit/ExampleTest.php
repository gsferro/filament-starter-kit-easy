<?php

/*
|--------------------------------------------------------------------------
| Os testes de unidade do SEU projeto começam aqui
|--------------------------------------------------------------------------
| Como o tests/Feature/ExemploTest.php, este arquivo existe para dar um ponto
| de partida e para a pasta existir no repositório — o git não versiona
| diretório vazio, e o PHPUnit aborta quando a testsuite aponta para um
| caminho inexistente.
|
| Escrito em Pest, e não como classe PHPUnit, por uma razão concreta: o `--tia`
| do Pest 5 ABORTA a execução inteira ao encontrar uma classe PHPUnit
| ("Encountered PHPUnit class … Convert it to a Pest test, or run without
| Tia"). Um único arquivo class-based esquecido no repositório desliga o Test
| Impact Analysis para todo mundo.
|
| Pode apagá-lo assim que escrever o primeiro teste de verdade.
*/

it('roda a suíte de unidade', function (): void {
    expect(true)->toBeTrue();
});
