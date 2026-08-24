<?php

use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Models\Role;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Livewire\Livewire;

/**
 * `uuid` na rota do papel — a última tabela do kit que expunha `id` na URL.
 *
 * Regra do starter-kit: NUNCA usar `id` na URL para acessar ou editar registro. A convenção é
 * a trait `App\Traits\TemUuid` (`app/Traits/TemUuid.php:14-18`), e `roles` é a tabela do
 * spatie — a coluna veio por migration própria, com backfill, porque toda instalação já tem
 * os cinco papéis do `PapeisSeeder`.
 *
 * Wiki: `wikis/specs/feat/perfis-e-permissoes/tela-de-perfis/`, ADR-03.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    noPainelBootado('admin');
});

/**
 * CT-11 — papel que já existia recebe uuid, e ele é único.
 *
 * O backfill é a metade da migration que ninguém lembra de escrever: aplicar a trait e
 * acrescentar a coluna sem preencher as linhas existentes deixa os cinco papéis semeados com
 * `uuid` nulo — e nenhuma tela de papel abre, porque a rota não resolve.
 *
 * A segunda asserção cobre o `unique` esquecido: dois papéis com o mesmo uuid fariam a rota
 * resolver o papel errado, e nada acusaria.
 */
it('preenche o uuid dos papeis que ja existiam', function (): void {
    $papeis = Role::query()->get();

    expect($papeis)->not->toBeEmpty()
        ->and($papeis->whereNull('uuid'))->toBeEmpty()
        ->and($papeis->pluck('uuid')->unique()->count())->toBe($papeis->count());
})->group('kit');

/**
 * CT-12 — a tela do papel abre pelo uuid e recusa o id.
 *
 * A linha do `id` é a cláusula do requisito, escrita como asserção: 404, não 200. Sem ela, a
 * migration e a trait podem estar no lugar e a rota continuar aceitando `id` — o que é
 * exatamente o mutante de aplicar a coluna e esquecer a trait no model.
 */
it('resolve a tela do papel por uuid e recusa o id', function (string $sufixo, string $chave, int $status): void {
    $papel = Role::findByName('panel_user');

    $parametro = $chave === 'uuid' ? $papel->getRouteKey() : (string) $papel->getKey();

    $this->actingAs(usuarioCom('master_global'))
        ->get("/admin/shield/roles/{$parametro}{$sufixo}")
        ->assertStatus($status);
})->with([
    'alteração por uuid'     => ['/edit', 'uuid', 200],
    'alteração por id'       => ['/edit', 'id', 404],
    'visualização por uuid'  => ['', 'uuid', 200],
    'visualização por id'    => ['', 'id', 404],
])->group('kit');

/**
 * CT-13 — parâmetro que não corresponde a papel algum devolve 404.
 *
 * Duas partições: uuid bem formado e inexistente, e texto que não é uuid. A segunda existe
 * porque `HasUniqueStringIds::resolveRouteBindingQuery()` lança quando o valor não é um uuid
 * válido, e o que se prova aqui é que isso chega ao usuário como 404 e não como erro 500.
 */
it('devolve 404 para identificador que nao e papel', function (string $parametro): void {
    $this->actingAs(usuarioCom('master_global'))
        ->get("/admin/shield/roles/{$parametro}/edit")
        ->assertStatus(404);
})->with([
    'uuid inexistente' => '018f2c4e-0000-7000-8000-000000000000',
    'nem uuid é'       => 'nao-e-uuid',
])->group('kit');

/**
 * CT-14 — papel criado pela tela nasce com uuid.
 *
 * A metade de CRIAÇÃO da regra. E por componente, não por HTTP: é o caminho em que o
 * `getRouteKeyName()` do model e a resolução do Livewire se encontram, e um dos dois pode
 * estar certo com o outro errado.
 */
it('grava uuid no papel criado pela tela', function (): void {
    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin'])
        ->call('create')
        ->assertHasNoFormErrors();

    $papel = Role::findByName('suporte');

    expect($papel->getAttribute('uuid'))->not->toBeNull()
        ->and($papel->getRouteKey())->toBe($papel->getAttribute('uuid'));
})->group('kit');

/**
 * CT-14b — editar o papel não troca o endereço dele.
 *
 * Sem este caso, um `uuid` regravado a cada `save()` passaria por todos os outros: a coluna
 * está preenchida, é única, e a rota resolve. O que quebra é o link que alguém salvou — e
 * ninguém descobre por teste que só cria.
 */
it('mantem o uuid do papel depois de editar', function (): void {
    $papel = Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin']);
    $antes = $papel->getAttribute('uuid');

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(EditRole::class, ['record' => $papel->getRouteKey()])
        ->fillForm(['name' => 'suporte_renomeado', 'guard_name' => 'web', 'painel' => 'admin'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($papel->fresh()->getAttribute('uuid'))->toBe($antes);
})->group('kit');

/**
 * A PK continua numérica — e isto não é detalhe de implementação.
 *
 * `TemUuid::uniqueIds()` devolve `['uuid']`, e o `HasUniqueStringIds` do Laravel só troca
 * `getKeyType()`/`getIncrementing()` quando a CHAVE PRIMÁRIA está nessa lista. Trocar a trait
 * por um `HasUuids` puro faria `uniqueIds()` devolver `['id']`, a PK viraria string e as
 * foreign keys de `model_has_roles` e `role_has_permissions` quebrariam — em silêncio no
 * SQLite, com erro em qualquer banco sério.
 */
it('mantem a chave primaria do papel numerica', function (): void {
    $papel = new Role;

    expect($papel->getKeyName())->toBe('id')
        ->and($papel->getKeyType())->toBe('int')
        ->and($papel->getIncrementing())->toBeTrue()
        ->and($papel->getRouteKeyName())->toBe('uuid');
})->group('kit');
