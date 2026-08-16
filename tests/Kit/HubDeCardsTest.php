<?php

use App\Filament\Admin\Pages\HubDeAdministracao;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Infra\Pages\HubDeInfraestrutura;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * As páginas hub em cartões (harvirsidhu/filament-cards).
 *
 * Ver `wikis/specs/main/hub-de-navegacao-em-cards/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-03 — o hub responde em cada painel, para quem tem o papel dele.
 *
 * Duas asserções por linha, e a segunda é a que importa: `assertSuccessful()` sozinho passa
 * com a grade VAZIA — que é justamente o que acontece se a descoberta devolver nada (grupo
 * montado com a chave errada, Page na pasta errada, seeders não rodados).
 *
 * As linhas com papel de painel (`admin`, `infra`) são as que exercitam a matriz do Shield:
 * o `master_global` vence pelo `Gate::before` sem permission nenhuma no banco.
 */
it('abre o hub do painel para quem tem o papel dele', function (string $rota, string $papel, string $destino): void {
    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"));

    $this->get($rota)
        ->assertSuccessful()
        ->assertSee($destino);
})->with([
    'admin com papel admin'         => ['/admin/hub-de-administracao', 'admin', 'Usuários'],
    'infra com papel infra'         => ['/infra/hub-de-infraestrutura', 'infra', 'Execuções de IA'],
    'admin com papel master_global' => ['/admin/hub-de-administracao', 'master_global', 'Usuários'],
]);

/**
 * CT-05 — o cartão aponta para o destino que ele nomeia, no grupo a que o destino pertence.
 *
 * A URL vem de `AiRunResource::getUrl()`, nunca escrita como string fixa: string fixa
 * transformaria o caso num teste do PRD, e quebraria no dia em que o slug mudasse por um
 * motivo legítimo.
 *
 * O grupo entra porque é ele que separa "grade organizada" — o que o requisito pede — de
 * "lista de links soltos".
 */
it('aponta o cartão para o destino e o coloca no grupo dele', function (): void {
    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));

    // O painel corrente é arranjo, não detalhe: quem o define num request é o middleware
    // `SetUpPanel`, que teste de componente não atravessa. Sem esta linha a descoberta lê o
    // painel que estiver ambiente no processo — e o caso passaria medindo outro painel.
    Filament::setCurrentPanel('infra');

    Livewire::test(HubDeInfraestrutura::class)
        ->assertSee(AiRunResource::getUrl(), escape: false)
        ->assertSee('IA');
});

/**
 * CT-01 — o administrador da instalação vê os destinos de administração.
 *
 * É a persona de controle da regra R1: se o hub some para ele, o defeito é de renderização,
 * não de autorização. O recorte por papel é assunto do par em `tests/Tenancy`, onde as
 * personas divergem de verdade.
 */
it('mostra os destinos de administração para quem administra a instalação', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Filament::setCurrentPanel('admin');

    // Os rótulos vêm das próprias classes, nunca escritos como string: o do `RoleResource` é
    // "Funções" (rótulo do Shield), não "Papéis", e cravar o texto aqui tornaria o caso um
    // teste da tradução do vendor em vez do hub.
    Livewire::test(HubDeAdministracao::class)
        ->assertSee(UserResource::getNavigationLabel())
        ->assertSee(RoleResource::getNavigationLabel());
});
