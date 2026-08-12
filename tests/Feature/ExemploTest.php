<?php

/*
|--------------------------------------------------------------------------
| Os testes do SEU projeto começam aqui
|--------------------------------------------------------------------------
| Esta pasta é sua: o kit não encosta nela num `kit:update`. Os testes da
| fundação (painéis, permissões, camada de IA) ficam em tests/Kit e rodam
| separados com `composer test:kit`.
|
| Este arquivo existe para dar um ponto de partida — e para a pasta existir
| no repositório, já que o git não versiona diretório vazio e o PHPUnit
| aborta quando a testsuite aponta para um caminho inexistente.
|
| Pode apagá-lo assim que escrever o primeiro teste de verdade.
*/

it('responde no endpoint de saúde da aplicação', function (): void {
    $this->get('/up')->assertSuccessful();
});

it('redireciona o visitante para o login do painel de negócio', function (): void {
    $this->get('/app')->assertRedirectContains('/app/login');
});
