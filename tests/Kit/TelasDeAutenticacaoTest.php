<?php

use Illuminate\Support\Facades\URL;

/**
 * O layout das telas públicas de autenticação.
 *
 * O que se afirma aqui é o EIXO do split, que é o que o usuário enxerga: a
 * classe `media-left` põe a arte à esquerda e o formulário à direita
 * (`flex-direction: row`); `media-right` inverte os dois (`row-reverse`). São as
 * duas classes que a CSS do pacote consome, então elas são o oráculo honesto —
 * afirmar "a tela abriu" não distingue layout nenhum.
 *
 * A recuperação de senha é espelhada de propósito: mesma arte, mesmo tema, lados
 * trocados. É o sinal de que se saiu do login, sem trocar cor nem marca.
 */
it('abre o login com a arte à esquerda e o formulário à direita', function (string $painel): void {
    $this->get("/{$painel}/login")
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertSee('media-left', escape: false)
        ->assertDontSee('media-right', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

it('espelha a recuperação de senha: arte à direita, formulário à esquerda', function (string $painel): void {
    $this->get("/{$painel}/password-reset/request")
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertSee('media-right', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * A tela de redefinição (a que o link do e-mail abre) usa a MESMA configuração do
 * pedido: o plugin registra as duas páginas com uma chave só (`password-reset`).
 * Se alguém configurar só uma das duas, esta é a que denuncia.
 *
 * A URL precisa ser ASSINADA: a rota de redefinição do Filament roda atrás do
 * `signed`, e montá-la à mão devolve 403 — o que reprovaria o caso por um motivo
 * que não é o dele.
 */
it('mantém o espelho também na tela de redefinição', function (string $painel): void {
    $url = URL::signedRoute("filament.{$painel}.auth.password-reset.reset", [
        'email' => 'alguem@example.com',
        'token' => 'token-de-teste',
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('media-right', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');
