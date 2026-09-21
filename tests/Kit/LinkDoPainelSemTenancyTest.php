<?php

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * CT-19 da wiki `link-painel-do-tenant` — com a multi-tenancy desligada a superfície do link não é
 * alcançável.
 *
 * ## Por que o caso vive aqui e não em `tests/Tenancy`
 *
 * `tests/Kit` é a ÚNICA suíte em que `kit.tenancy.enabled` é falso. Escrito em `tests/Tenancy` com
 * um `config()->set()` num `beforeEach`, este caso mediria o arnês: o `Tests\TenancyTestCase` fixa
 * a config em `createApplication()`, antes das migrations (`.ai/rules/testes.md`).
 *
 * ## O "não se aplica" aqui tem destinatário
 *
 * A organização EXISTE na tabela — ela existe sem tenancy, só não significa nada —, então a
 * listagem teria uma linha para renderizar um link. Sem essa fixture o caso passaria por não haver
 * o que renderizar, e não por a tela estar fechada.
 *
 * ## O que ele protege
 *
 * A ADR-03 decidiu NÃO acrescentar guarda de config nenhuma: a feature vive dentro do
 * `TenantResource`, que já se fecha nos dois métodos (`canAccess()` e `shouldRegisterNavigation()`).
 * Este caso é o invariante que sustenta essa decisão — ele fica vermelho se a entrada do link
 * nascer fora do resource (num hub, num widget, no menu), onde o gerador não tem rota de tenant
 * para resolver, e fica vermelho se o par de métodos do resource perder a condição de config.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('[CT-19] com a tenancy desligada a listagem de organizacoes nao abre', function (): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse('a suíte Kit tem de rodar com a tenancy desligada');

    Tenant::create(['nome' => 'Acme', 'slug' => 'acme', 'ativo' => true]);

    $administrador = usuarioDoKit('admin', 'admin-instalacao@example.com');

    $this->actingAs($administrador)->get('/admin/organizacoes')->assertForbidden();

    expect(TenantResource::canAccess())->toBeFalse()
        ->and(TenantResource::shouldRegisterNavigation())->toBeFalse();

    // E o endereço da tela nem aparece na navegação do painel.
    $this->actingAs($administrador)->get('/admin')->assertSuccessful()->assertDontSee('/admin/organizacoes');
})->group('kit');

/**
 * CT-21 — com a tenancy desligada nenhuma tela do `/admin` estoura nem oferece o link.
 *
 * ## Por que ele existe, se CT-19 já está aqui
 *
 * CT-19 mata M27 só pela metade. M27 fala em "hub, widget, menu" — três lugares —, e CT-19 afirma
 * duas coisas sobre UM: a listagem do `TenantResource` em 403 e a ausência dele na navegação.
 * Nenhuma das duas alcança um widget do dashboard, um item do menu do usuário ou qualquer outra
 * entrada que renderizasse o link fora do resource. Um widget assim deixaria o `/admin` INTEIRO
 * fora do ar numa instalação single-tenant (M36) — e CT-19 ficaria verde.
 *
 * ## O inventário é emprestado, de propósito
 *
 * A varredura usa `telasDoKit()['admin']` (`tests/Pest.php:telasDoKit:224`), a mesma lista que o
 * `InventarioDeTelasTest` obriga a manter completa nos dois sentidos. Assim o caso herda cobertura
 * de toda tela nova do painel sem precisar de edição: quem acrescentar uma tela é obrigado a
 * listá-la lá, e ela entra aqui de graça.
 *
 * ## As duas asserções são as duas metades do defeito
 *
 * **Nenhum 500** — o gerador chamado onde não há rota de tenant. `toBeLessThan(500)` e não
 * `assertSuccessful()`: várias telas respondem 403 por design sem tenancy (a de organizações é uma
 * delas, CT-19), e exigir 200 mediria a matriz de permissões, não o estouro.
 *
 * **Nenhum endereço do painel de negócio** — a entrada renderizada onde ela não deveria existir.
 * Duas formas, porque o gerador produz uma diferente conforme a tenancy: com ela ligada o endereço
 * é `/app/{slug}`; com ela desligada `Panel::getUrl($tenant)`
 * (`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:getUrl:170`) cai no ramo de
 * `Route::has()` — a rota não tem parâmetro `{tenant}` sem tenancy, então o modelo vira QUERY
 * STRING (`?tenant={uuid}`). Afirmar só a primeira deixaria a segunda passar — e é justamente ela
 * que uma implementação ingênua produziria aqui.
 *
 * Nenhuma das duas é `/app` solto: a raiz do painel de negócio aparece legitimamente no `/admin`
 * (o seletor de painéis do kit), e a asserção nasceria vermelha contra a instalação correta. O que
 * não pode aparecer é o endereço DESTA organização.
 */
it('[CT-21] com a tenancy desligada nenhuma tela do /admin estoura nem oferece o link', function (): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse('a suíte Kit tem de rodar com a tenancy desligada');

    // A organização EXISTE — sem ela o caso passaria por não haver o que renderizar.
    $organizacao = Tenant::create(['nome' => 'Acme', 'slug' => 'acme', 'ativo' => true]);

    $administrador = usuarioDoKit('admin', 'admin-instalacao@example.com');

    $enderecosDaOrganizacao = [
        '/app/'.$organizacao->slug,
        'tenant='.$organizacao->getRouteKey(),
    ];

    foreach (telasDoKit()['admin'] as $tela) {
        $resposta = $this->actingAs($administrador)->get($tela);

        expect($resposta->status())->toBeLessThan(500, "a tela {$tela} estourou com a tenancy desligada");

        foreach ($enderecosDaOrganizacao as $endereco) {
            expect($resposta->getContent())->not->toContain(
                $endereco,
                "a tela {$tela} oferece o endereço `{$endereco}` do painel de negócio sem tenancy",
            );
        }
    }
})->group('kit');
