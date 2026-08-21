<?php

use App\Filament\App\Pages\HubDoNegocio;
use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\App\Resources\Users\UserResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * O hub do painel de negócio — e a única tela onde a filtragem por autorização é observável.
 *
 * Nos outros dois painéis, quem entra costuma poder tudo; aqui não: o `panel_user` divide a
 * tela com o `admin_app`, e é essa divergência que separa "o hub filtra por
 * `canAccess()`" de "o hub mostra tudo".
 *
 * O ponto vale ser repetido porque o pacote induz ao erro: `CardItem` NÃO verifica
 * autorização sozinho (`Concerns/CanBeHidden.php` avalia só `visible`/`hidden`). Quem filtra é
 * `App\Filament\Concerns\DescobreCardsDoPainel`.
 *
 * Ver `wikis/specs/main/hub-de-navegacao-em-cards/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->acme = tenant('Acme', 'acme');
});

/**
 * CT-01 da wiki `hub-de-cards-opcional` — a linha do painel /app.
 *
 * O par das linhas de /admin, que vivem em `tests/Kit/HubDeCardsTest.php`. Ele fica aqui porque a
 * persona do painel de negócio só existe com contexto de organização.
 *
 * A segunda parte cobre a barra lateral sem gastar um caso: `canAccess()` falso também faz
 * `Page::registerNavigationItems()` retornar cedo
 * (`vendor/filament/filament/src/Pages/Page.php:133-135`).
 */
it('recusa o hub do negócio enquanto a flag está desligada', function (): void {
    expect(config('kit.hub'))->toBeFalse();

    $comum = usuarioComPapel('panel_user', $this->acme, 'comum@example.com');
    $comum->tenants()->attach($this->acme->getKey());

    $this->actingAs($comum);

    $this->get("/app/{$this->acme->slug}/hub-do-negocio")->assertForbidden();

    $this->get("/app/{$this->acme->slug}")
        ->assertSuccessful()
        ->assertDontSee('Início');
});

/**
 * CT-02 da ancestral — o usuário comum da organização não recebe os caminhos de administração.
 *
 * Persona discriminante: rodar este caso com `master_global` deixaria a barreira inteira sem
 * cobertura, porque ele vence pelo `Gate::before` ANTES de qualquer `canAccess()` ser
 * consultado — o hub apareceria completo e o caso passaria com o filtro removido.
 *
 * As três asserções são um conjunto: a primeira prova que o hub não veio vazio (um filtro
 * quebrado que esconde tudo passaria só com as duas negativas).
 *
 * `kit.hub` LIGADO no arranjo: esta página nasce desligada no kit desde a wiki
 * `hub-de-cards-opcional`, e o caso mede o FILTRO por autorização, não a flag.
 */
it('esconde os destinos de administração do usuário comum da organização', function (): void {
    /*
     * A demo LIGADA é o que dá ao caso o seu controle positivo.
     *
     * `ProjetoResource` é o único resource de negócio do painel, e ele só existe
     * quando há demo (`config('kit.demo')`) — o /app de um projeto de verdade
     * nasce vazio. Sem esta linha o hub vem vazio, a primeira asserção cai, e as
     * duas negativas passariam por ausência de conteúdo em vez de por filtro.
     */
    config(['kit.demo' => true, 'kit.hub' => true]);

    $comum = usuarioComPapel('panel_user', $this->acme, 'comum@example.com');
    $comum->tenants()->attach($this->acme->getKey());

    noPainelDa($this->acme);
    Filament::setCurrentPanel('app');

    $this->actingAs($comum);

    Livewire::test(HubDoNegocio::class)
        ->assertSee(ProjetoResource::getNavigationLabel())
        ->assertDontSee(UserResource::getNavigationLabel())
        ->assertDontSee(ConviteResource::getNavigationLabel());
});

/**
 * CT-04 — o hub do negócio pertence ao usuário comum; a administração, não.
 *
 * `.ai/rules/filament.md` manda acrescentar toda Page de ADMINISTRAÇÃO do painel `app` à
 * lista de subtração do `panel_user` (`PapeisSeeder::permissoesDeAdministracaoDoApp()`). Esta
 * página deliberadamente NÃO entra: é navegação de negócio, e numa subtração o erro é
 * espelhado — acrescentá-la "por precaução" daria 403 na página inicial do cliente.
 *
 * As duas asserções fixam a decisão pelos dois lados: a primeira falharia se alguém
 * acrescentasse o hub à lista; a segunda, se alguém afrouxasse a subtração para o hub passar
 * e levasse o `UserResource` junto. Ver ADR-05 da wiki.
 */
it('mantém o hub do negócio com o usuário comum e a administração fora dele', function (): void {
    $permissoes = Role::findByName('panel_user')->permissions->pluck('name');

    expect($permissoes)
        ->toContain('View:HubDoNegocio')
        ->not->toContain('ViewAny:User');
});
