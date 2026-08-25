<?php

use App\Filament\App\Resources\Users\Pages\EditUser;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Livewire\Livewire;

/**
 * A regressão da correção de exclusão: negar `delete` não pode negar mais nada.
 *
 * ## Por que estes dois casos existem
 *
 * A correção do achado F-01 sobrescreve `getDeleteAuthorizationResponse()` e
 * `getDeleteAnyAuthorizationResponse()` no `UserResource` do `/app`. Escrever a negação no método
 * vizinho errado — `getEditAuthorizationResponse()`, `getViewAnyAuthorizationResponse()`, ou o
 * genérico `getAuthorizationResponse()` — deixaria toda a suíte de `tests/Kit` verde e quebraria a
 * administração da organização.
 *
 * A matriz do `04-casos-de-teste.md` (R4) tem uma coluna **válida** por isso: cobrir só as recusas
 * deixaria `editar` com negativas e nenhuma edição que funciona.
 *
 * ## Por que aqui e não em `tests/Kit`
 *
 * O `/app` é o painel escopado por organização: sem organização corrente a query FECHA de
 * propósito, e o helper `noPainelDa()` vive nesta suíte. Os casos de `tests/Kit` não precisam de
 * painel bootado porque a negação de R1/R2 é incondicional.
 *
 * Ver `wikis/specs/feat/auditoria-de-seguranca/travas-de-exclusao-e-upload-anonimo/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * O cenário mínimo: uma organização, quem a administra, e alguém para administrar.
 *
 * @return array{organizacao: Tenant, admin: User, alvo: User}
 */
function cenarioDaAuditoria(): array
{
    $organizacao = tenant('Acme', 'acme');

    $admin = usuarioComPapel('admin_app', $organizacao, 'admin.app@example.com');
    $alvo  = usuarioComPapel('panel_user', $organizacao, 'alvo@example.com');

    /*
     * O `attach` é indispensável, e a razão é a fronteira que a feature protege: o papel e o
     * VÍNCULO são coisas separadas. `usuarioComPapel()` atribui o papel no contexto da
     * organização, mas a posse mora na pivot `tenant_user` — é ela que o `whereHas('tenants')`
     * do `UserResource::getEloquentQuery()` consulta. Sem o attach, a query fecha (corretamente)
     * e o `EditUser` responde "No query results", o que pareceria defeito da correção.
     */
    $organizacao->users()->attach([$admin->getKey(), $alvo->getKey()]);

    $alvo->forceFill(['name' => 'Nome Antigo'])->save();

    return ['organizacao' => $organizacao, 'admin' => $admin, 'alvo' => $alvo];
}

/**
 * CT-06 — a edição GRAVA, e é gravação por componente, não visita.
 *
 * A regra do par: *uma tela aberta não é uma tela que grava*. Uma negação escrita em
 * `getEditAuthorizationResponse()` por confusão de nome deixa o `GET` verde e quebra o `save` — e é
 * exatamente o mutante que este caso mata.
 */
it('mantem a edicao de usuario funcionando no /app depois da negacao de exclusao', function (): void {
    ['organizacao' => $organizacao, 'admin' => $admin, 'alvo' => $alvo] = cenarioDaAuditoria();

    noPainelDa($organizacao);
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $alvo->uuid])
        ->fillForm(['name' => 'Nome Novo'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(User::query()->whereKey($alvo->getKey())->value('name'))->toBe('Nome Novo',
        'Negar a exclusão não pode negar a edição: o administrador da organização precisa continuar '
        .'administrando quem está nela.'
    );
})->group('tenancy');

/**
 * CT-07 — `view` e `viewAny` continuam permitidos, e a query continua escopada.
 *
 * ## Por que o oráculo NÃO é a tabela renderizada
 *
 * Renderizar a listagem exige o painel BOOTADO: a coluna de avatar chama o macro
 * `ImageColumn::simpleLightbox()`, registrado no `boot()` de um plugin, e sem o boot a tela morre
 * com `BadMethodCallException`. Só que bootar o painel dentro de um teste de componente estoura no
 * Breezy (`BreezyCore.php:112`, `parameter() on null`), que lê parâmetro de rota inexistente fora
 * de um request. Os dois arranjos são incompatíveis, e nenhum dos dois erros tem a ver com a
 * correção.
 *
 * O que este caso precisa provar são os mutantes que ele possui: a negação escrita por engano em
 * `getViewAnyAuthorizationResponse()` — a tela sairia do menu — ou no genérico
 * `getAuthorizationResponse()`, que atingiria todas as operações. As duas primeiras asserções
 * matam os dois sem depender de render.
 *
 * A terceira mantém o par negativo honesto: sem ela, uma negação "corrigida" abrindo a query
 * ficaria verde com o escopo de organização quebrado — defeito pior que o original. E a listagem
 * renderizada de verdade já está coberta em `tests/Tenancy/AdminDaOrganizacaoTest.php`, que é quem
 * tem esse arranjo montado.
 */
it('mantem view e viewAny permitidos no /app, e a query escopada', function (): void {
    ['organizacao' => $organizacao, 'alvo' => $alvo, 'admin' => $admin] = cenarioDaAuditoria();

    $deOutraOrganizacao = usuarioComPapel('panel_user', tenant('Globex', 'globex'), 'globex@example.com');

    noPainelDa($organizacao);
    $this->actingAs($admin);

    $visiveis = UserResource::getEloquentQuery()->pluck('id')->all();

    expect(UserResource::getViewAnyAuthorizationResponse()->allowed())->toBeTrue(
        'Negar a exclusão não pode negar a listagem: a tela sairia do menu.'
    )->and(UserResource::getViewAuthorizationResponse($alvo)->allowed())->toBeTrue(
        'Nem a visualização de um registro da própria organização.'
    )->and($visiveis)->toContain($alvo->getKey())
        ->and($visiveis)->not->toContain($deOutraOrganizacao->getKey());
})->group('tenancy');
