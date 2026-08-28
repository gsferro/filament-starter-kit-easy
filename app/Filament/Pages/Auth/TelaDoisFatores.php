<?php

namespace App\Filament\Pages\Auth;

use Caresome\FilamentAuthDesigner\Concerns\HasAuthDesignerLayout;
use Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage;

/**
 * O desafio de 2FA — a tela onde se digita o código — com o MESMO layout do login.
 *
 * O Breezy entrega a tela como `SimplePage` do Filament
 * (`vendor/jeffgreco13/filament-breezy/src/Pages/TwoFactorPage.php:23`), e `SimplePage` fixa
 * `$layout` em `filament-panels::components.layout.simple`
 * (`vendor/filament/filament/src/Pages/SimplePage.php:12`). Resultado: quem acabava de entrar
 * pelo login split caía numa segunda etapa sem mídia, sem marca e sem alternador de tema.
 *
 * A view do Breezy é `<x-filament-panels::page.simple>` — o MESMO componente que a página de
 * login do Filament usa —, então trocar o layout basta: o conteúdo já é um cartão de auth.
 * Nenhum método de comportamento é sobrescrito aqui; o `authenticate()`, o `rateLimit(5)` e o
 * alternador de código de recuperação continuam sendo os do pacote.
 *
 * Quem põe esta classe na rota é o parâmetro `action:` de `enableTwoFactorAuthentication()` nos
 * três painéis — a rota do pacote pergunta ao plugin qual classe usar
 * (`vendor/jeffgreco13/filament-breezy/routes/web.php:24`). Diferente de `TelaBloqueio`, aqui
 * NÃO cabe bind no container: a rota recebe um class-string do plugin e nunca consulta o
 * container para escolher a classe. Ver ADR-01 da wiki `auth-designer-telas`.
 */
class TelaDoisFatores extends TwoFactorPage
{
    use HasAuthDesignerLayout;

    /**
     * **Não remover por parecer redundante com a trait.** `$layout` é estático e a trait faz
     * `static::$layout = ...` no `boot()`
     * (`vendor/caresome/filament-auth-designer/src/Concerns/HasAuthDesignerLayout.php:14`). Sem
     * esta redeclaração a subclasse não tem storage próprio, a atribuição cai no estático de
     * `Filament\Pages\SimplePage` e a primeira renderização desta tela passa a vestir o layout
     * de login em **toda** página simples do processo.
     *
     * A ironia registrada em `.ai/rules/auth.md`: a página que morria com esse vazamento era
     * justamente esta. Regra do kit — página de auth redeclara o `$layout`.
     */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    /**
     * A chave `login` de propósito: o desafio de 2FA é a SEGUNDA ETAPA do mesmo login — mesma
     * barreira, mesma arte, mesmo lado, mesmo alternador de tema. É a mesma escolha de
     * `TelaBloqueio::getAuthDesignerPageKey()`, pelo mesmo motivo.
     *
     * Chave não configurada não estoura: o repositório devolve um `AuthPageConfig` vazio e a
     * tela sai vestida e VAZIA, sem mídia e sem alternador — sem erro nenhum. Ver ADR-02.
     */
    /**
     * `password-reset`, e não `login`: a chave escolhe o LAYOUT, não a semântica. O desafio de
     * 2FA é um passo do login, mas é um formulário curto de um campo — como "esqueci a senha" e
     * o registro, que nos três painéis põem a arte à direita e o formulário à esquerda
     * (`MediaPosition::Right`). O login é o único com a arte à esquerda. Pedido do solicitante
     * na validação real de 2026-08-26, depois de passar pelo desafio numa instalação.
     */
    protected function getAuthDesignerPageKey(): string
    {
        return 'password-reset';
    }
}
