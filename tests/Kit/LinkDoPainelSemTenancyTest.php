<?php

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;

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
 * A varredura usa `telasDoKit()['admin']` (`tests/Pest.php:telasDoKit:225`), a mesma lista que o
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
 * Duas formas, uma por implementação ingênua possível:
 *
 * 1. `/app/{slug}`, o que a CONCATENAÇÃO à mão produziria (`url('/app/'.$tenant->slug)`) — a
 *    alternativa que a ADR-01 descartou
 * 2. `/app/{uuid}`, o que o GERADOR produz de fato sem tenancy
 *
 * A segunda forma esteve escrita errada aqui, e o erro é instrutivo. O docblock afirmava
 * `?tenant={uuid}`, raciocinando que sem tenancy a rota não teria o parâmetro `{tenant}` e que o
 * modelo viraria query string. **Medido em 2026-09-21: não vira**, e o raciocínio errou o RAMO.
 *
 * `HasRoutes::getUrl()` (`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:getUrl:170`)
 * só passaria por `route()` se `Route::has('filament.app.home')` fosse verdade — e sem tenancy
 * essa rota não é registrada. Ele cai no ramo seguinte, `:193`, que monta o endereço por
 * **concatenação de string**, usando `$tenant->getRouteKey()` porque não há `slugAttribute`
 * declarado. Devolve `http://host/app/{uuid}`: segmento de caminho, chave de rota no lugar do
 * slug, e **nenhuma** query string.
 *
 * Enquanto a string afirmada foi `tenant=`, esta metade do caso era VAZIA: nenhuma implementação,
 * certa ou errada, poderia produzi-la, e a asserção teria ficado verde para sempre. Note que o
 * docblock antigo citava `arquivo:linha` REAL e raciocinava de forma plausível sobre ele — o que
 * faltou foi executar.
 *
 * ## O controle positivo existe por causa disso
 *
 * Asserção de AUSÊNCIA não distingue "o kit não renderiza" de "esta string não existe no
 * universo". O controle abaixo mede o gerador e exige que ele produza o endereço morto — se o
 * Filament mudar a forma da URL, o caso fica VERMELHO no controle em vez de emudecer. É o mesmo
 * mecanismo de `tests/Kit/OrdemDasCascadeLayersTest.php`, criado na `v0.37.1` pelo mesmo motivo.
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

    $enderecoGerado = Filament::getPanel('app')->getUrl($organizacao);

    /*
     * CONTROLE POSITIVO — sem ele as asserções de ausência abaixo podem ficar vazias em silêncio.
     *
     * `assertStringContainsString` e não `expect()->toContain()`: o segundo argumento de
     * `toContain()` é outra AGULHA, não a mensagem. Passar a explicação ali a transformaria numa
     * string a procurar — que nunca existe —, e o controle positivo nasceria quebrado. A mesma
     * armadilha valia para as asserções de ausência do laço abaixo, onde ela era silenciosa:
     * `->not->toContain($endereco, $mensagem)` só exigia que a MENSAGEM também estivesse ausente,
     * e mensagem nenhuma aparece num HTML. Por isso as duas usam a asserção do PHPUnit.
     */
    $this->assertStringContainsString(
        '/app/'.$organizacao->getRouteKey(),
        $enderecoGerado,
        'o gerador do Filament deixou de produzir `/app/{chave}` sem tenancy: as asserções de '
        .'ausência deste caso não guardam mais nada e precisam ser reescritas contra a forma nova',
    );

    $enderecosDaOrganizacao = [
        '/app/'.$organizacao->slug,
        '/app/'.$organizacao->getRouteKey(),
    ];

    foreach (telasDoKit()['admin'] as $tela) {
        $resposta = $this->actingAs($administrador)->get($tela);

        expect($resposta->status())->toBeLessThan(500, "a tela {$tela} estourou com a tenancy desligada");

        foreach ($enderecosDaOrganizacao as $endereco) {
            $this->assertStringNotContainsString(
                $endereco,
                $resposta->getContent(),
                "a tela {$tela} oferece o endereço `{$endereco}` do painel de negócio sem tenancy",
            );
        }
    }
})->group('kit');

/**
 * CT-23 — sem multi-tenancy o gerador de endereço da organização devolve `null`, não um link morto.
 *
 * ## O caso nasceu de uma alegação errada da ADR-03
 *
 * A ADR-03 decidiu não acrescentar guarda de config, e registrou como risco que *"se alguém abrir
 * o `TenantResource` sem tenancy, o gerador de URL da ADR-01 **falha**"*. Falhar seria o bom
 * desfecho: exceção aparece. **Medido em 2026-09-21: ele não falha.**
 * `Panel::getUrl($tenant)` devolve `http://host/app/{uuid}` — sintaticamente uma URL, e um 404
 * para quem clica. O modo de falha real era SILENCIOSO, e nenhuma asserção de status, de exceção
 * ou de console o enxergaria.
 *
 * ## Por que este caso e o CT-21 não são o mesmo caso
 *
 * CT-21 afirma que nenhuma TELA do `/admin` oferece o endereço. Ele é verde hoje por um motivo que
 * não é a guarda: o `TenantResource` inteiro está fechado sem tenancy (CT-19), então não há tabela
 * para renderizar coluna nenhuma. **CT-21 continuaria verde com a guarda removida** — ele protege
 * contra a entrada do link NASCER fora do resource, não contra o gerador.
 *
 * Este caso chama o gerador DIRETO, sem passar por tela. É o único que fica vermelho se a guarda
 * de `Tenant::urlDoPainel()` sumir do diff, e é por isso que ele existe separado.
 *
 * ## O oráculo é `null` e não "uma string qualquer"
 *
 * As três superfícies tipam `?string` e tratam `null` como "sem link": a coluna fica vazia e a
 * `TextEntry` não vira âncora. Qualquer string — inclusive a morta — vira link clicável.
 */
it('[CT-23] sem tenancy o gerador de endereco da organizacao devolve null', function (): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse('a suíte Kit tem de rodar com a tenancy desligada');

    $organizacao = Tenant::create(['nome' => 'Acme', 'slug' => 'acme', 'ativo' => true]);

    expect(Filament::getPanel('app')->hasTenancy())->toBeFalse(
        'o painel de negócio não deveria ter tenancy com `kit.tenancy.enabled` falso',
    );

    expect($organizacao->urlDoPainel())->toBeNull(
        'sem tenancy o endereço da organização tem de ser `null`, e não o `/app/{uuid}` morto que '
        .'`Panel::getUrl()` devolve',
    );
})->group('kit');
