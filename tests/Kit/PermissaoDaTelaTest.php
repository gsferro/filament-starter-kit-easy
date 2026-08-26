<?php

use App\Filament\Infra\Pages\Pulse;
use App\Support\PermissaoDaTela;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * `PermissaoDaTela::permite()` falha FECHADO quando a chave da página não resolve.
 *
 * ## O que era, e por que mudou
 *
 * Até a v0.20.0 o predicado devolvia `true` para página fora do mapa do Shield — "falha aberta
 * declarada". A chave não resolve quando `FilamentShield::getPages()` não tem a página, e esse
 * mapa é `once()`: memoizado por processo no PRIMEIRO acesso. Qualquer código que o toque antes do
 * `SetUpPanel` congela o mapa no painel errado, e aí TODA página com o trait abria sem consultar
 * permissão. Medido numa sonda de processo único durante a auditoria de aderência ao Blueprint: as
 * 12 páginas do /infra abrindo com `View:Pulse` revogada.
 *
 * Em request real isso não acontece (medido via Playwright: 403). Mas a condição que torna a falha
 * alcançável não está sob controle do kit — é qualquer provider ou plugin novo. Fechar custa zero
 * em request e elimina a classe. Ver ADR-02 da wiki `aderencia-ao-blueprint`.
 *
 * ## O oráculo é uma classe FORA do mapa
 *
 * Não uma página real com a permissão revogada — isso `PermissoesDeTelasTest` já cobre, e passaria
 * nas duas versões do predicado. O que distingue as duas versões é uma chave que NÃO resolve, e a
 * forma honesta de arranjar isso é um FQCN que o Shield nunca mapeou.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('nega quando a pagina nao esta no mapa do Shield e ha usuario', function (): void {
    noPainelDoShield('infra');
    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));

    expect(PermissaoDaTela::permite('App\\Filament\\Infra\\Pages\\PaginaQueNaoExiste'))->toBeFalse(
        'Chave que não resolve tem de FECHAR. A versão anterior devolvia true — falha aberta.'
    );
})->group('kit');

/**
 * A metade válida, para a mutação "fechar sempre" não passar: página mapeada, papel com a
 * permissão, `permite()` = true.
 */
it('permite quando a pagina esta no mapa e o papel tem a permissao', function (): void {
    noPainelDoShield('infra');
    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));

    expect(PermissaoDaTela::permite(Pulse::class))->toBeTrue();
})->group('kit');

/**
 * Sem usuário o predicado NÃO decide: a página de painel já exige `auth`, e é o middleware quem
 * responde por anônimo. Devolver `false` aqui faria `canAccess()` dizer "proibido" onde a resposta
 * certa é "faça login" — e mudaria o 302 do painel para 403.
 */
it('delega ao painel quando nao ha usuario autenticado', function (): void {
    noPainelDoShield('infra');

    expect(PermissaoDaTela::permite(Pulse::class))->toBeTrue();
})->group('kit');
