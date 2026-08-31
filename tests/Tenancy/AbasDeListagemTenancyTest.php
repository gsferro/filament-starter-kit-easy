<?php

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\App\Resources\Convites\Pages\ListConvites;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * A fronteira que a aba e o badge não podem atravessar.
 *
 * O recorte da aba é por `aprovacao_pendente`, que não sabe nada de organização — quem sabe é o
 * `getEloquentQuery()` do Resource. Por isso a aba e, principalmente, o **badge** têm de sair
 * dele: um badge escrito com `User::query()` recortaria certo na tabela e contaria a instalação
 * inteira no rótulo, informando quantas pessoas existem fora da organização de quem olha.
 *
 * ADR-02 da wiki abas-nas-listagens.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->acme   = tenant('Acme', 'acme');
    $this->globex = tenant('Globex', 'globex');

    $this->operador = papelNaOrganizacao(usuario('admin.acme@example.com'), 'admin_app', $this->acme);
    $this->operador->tenants()->attach($this->acme->id);

    $this->actingAs($this->operador);

    /*
     * Um request REAL ao painel antes dos casos, e a resposta não interessa.
     *
     * A tabela de usuários do /app usa o macro `ImageColumn::simpleLightbox()`, registrado no
     * `boot()` do plugin — e quem chama o boot é o middleware `SetUpPanel`, que teste de
     * componente Livewire não atravessa. Macro é estático, então um request basta para o
     * processo inteiro. `noPainelBootado('app')` NÃO serve aqui: sem rota corrente o
     * `BreezyCore:112` morre em `parameter() on null`.
     *
     * Os arquivos vizinhos de `tests/Tenancy` funcionam por herdarem esse registro de um caso
     * HTTP anterior no mesmo processo — dependência de ordem que este arquivo não quer.
     */
    $this->get("/app/{$this->acme->slug}");

    noPainelDa($this->acme);
});

/** Pendente dentro de uma organização: o vínculo é a pivot, e `aprovacao_pendente` é forçado. */
function pendenteNa(Tenant $tenant, string $email): User
{
    $user = usuario($email);
    $user->forceFill(['aprovacao_pendente' => true])->save();
    $user->tenants()->attach($tenant->id);

    return $user;
}

it('[CT-08] a aba "Pendentes" da Acme não mostra o pendente da Globex', function (): void {
    $daAcme   = pendenteNa($this->acme, 'pendente.acme@example.com');
    $daGlobex = pendenteNa($this->globex, 'pendente.globex@example.com');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->set('activeTab', 'pendentes')
        ->assertCanSeeTableRecords([$daAcme])
        ->assertCanNotSeeTableRecords([$daGlobex]);
});

/**
 * O caso que separa "recorta certo" de "conta certo". Os dois pendentes existem na instalação;
 * só um é da Acme. Um badge que contasse `User::query()` diria 2 ao lado de uma tabela com 1.
 */
it('[CT-09] o badge da Acme conta um, e não os dois da instalação', function (): void {
    pendenteNa($this->acme, 'pendente.acme@example.com');
    pendenteNa($this->globex, 'pendente.globex@example.com');

    $abas = Livewire::test(ListUsers::class)->instance()->getTabs();

    expect($abas['pendentes']->getBadge())->toBe('1');
});

/**
 * Fail-closed: sem organização corrente o `getEloquentQuery()` do /app fecha com
 * `whereRaw('1 = 0')`, e a aba e o badge herdam isso.
 *
 * Sem renderizar a tela: a rota do /app exige `{tenant}` e o Blade estoura em "Missing required
 * parameter" antes de qualquer asserção — o que mediria o roteador, não o recorte. O badge é
 * justamente o lugar por onde um número escaparia sem organização para escopar.
 */
it('[CT-10] sem organização corrente a query fecha e o badge é zero', function (): void {
    pendenteNa($this->acme, 'pendente.acme@example.com');

    Filament::setTenant(null);

    // A fonte do badge, sem montar a pagina: `Livewire::test()` renderiza o Blade da
    // ListRecords, que resolve `route('filament.app.resources.users.index')` e estoura em
    // "Missing parameter: tenant" antes de qualquer assercao. A expressao abaixo e
    // exatamente a do `->badge()` em `ListUsers::getTabs()`; quem prova que a aba usa essa
    // expressao com a tela de pe e o CT-09.
    expect(UserResource::getEloquentQuery()->count())->toBe(0)
        ->and(UserResource::recorteDePendentes(UserResource::getEloquentQuery())->count())->toBe(0);
});

it('[CT-12] a aba "Pendentes" de convites da Acme não mostra convite da Globex', function (): void {
    $daAcme   = ofertaPara('convidado.acme@example.com', $this->acme);
    $daGlobex = ofertaPara('convidado.globex@example.com', $this->globex);

    /*
     * O `tenant_id` do convite é carimbado com a organização CORRENTE na criação, e o valor
     * passado à factory é descartado — comportamento pré-existente do kit, não desta feature
     * (medido: `Convite::factory()->create(['tenant_id' => globex])` grava o id da Acme). Para
     * o convite da Globex existir de verdade, o arranjo corrige a coluna no banco.
     *
     * Achado adjacente, registrado em `03-progresso.md` → Notas de Implementação.
     */
    DB::table('convites')->where('id', $daGlobex->getKey())->update(['tenant_id' => $this->globex->getKey()]);
    $daGlobex->refresh();

    // A fonte antes da tela: se o recorte do Resource já estiver errado, o defeito não é da aba.
    expect(ConviteResource::getEloquentQuery()->pluck('email')->all())
        ->toContain('convidado.acme@example.com')
        ->not->toContain('convidado.globex@example.com');

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->set('activeTab', 'pendentes')
        ->assertCanSeeTableRecords([$daAcme])
        ->assertCanNotSeeTableRecords([$daGlobex]);
});
