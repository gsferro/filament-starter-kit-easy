<?php

use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Infra\Resources\AiRuns\Pages\ListAiRuns;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Livewire\Livewire;

/**
 * Abas de recorte nas listagens do /admin — nível (a) do estudo `advanced-tables`.
 *
 * A aba é o recorte de UM clique; o filtro do modal continua para COMBINAR. O que estes casos
 * guardam é que os dois dizem a mesma coisa **porque leem a mesma definição**: derivar o recorte
 * em dois lugares é como a listagem de convites do /admin e a do /app já divergiram uma vez.
 *
 * A fronteira de organização (aba e badge parando no tenant corrente) é assunto de
 * `tests/Tenancy/AbasDeListagemTenancyTest.php` — aqui a tenancy está desligada.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->admin = usuarioDoKit('admin', 'admin@example.com');
    noPainelDoShield('admin');
    noPainelBootado('admin');
    $this->actingAs($this->admin);
});

/**
 * `aprovacao_pendente` fica FORA do `$fillable` de propósito (`RegistroAberto.php`): um
 * `User::create([... 'aprovacao_pendente' => true])` gravaria `false` em silêncio e o caso
 * viraria falso ✅. Daí o `forceFill`.
 */
function pendente(string $email): User
{
    $user = usuario($email);
    $user->forceFill(['aprovacao_pendente' => true])->save();

    return $user;
}

/*
|--------------------------------------------------------------------------
| R1 — as abas declaradas, na ordem, com "Todos" ativa por padrão
|--------------------------------------------------------------------------
*/

it('[CT-01] declara as abas da listagem de usuários com "Todos" ativa por padrão', function (): void {
    $componente = Livewire::test(ListUsers::class);

    expect(array_keys($componente->instance()->getTabs()))->toBe(['todos', 'pendentes']);

    // A aba ativa é derivada da PRIMEIRA chave pelo Filament quando não há `?tab=` — é isto
    // que mantém a tela de quem não clica em nada igual à de antes desta feature.
    $componente->assertSet('activeTab', 'todos');
});

it('[CT-01] declara as três abas da listagem de convites com "Todos" ativa por padrão', function (): void {
    $componente = Livewire::test(ListConvites::class);

    expect(array_keys($componente->instance()->getTabs()))->toBe(['todos', 'pendentes', 'aceitos']);

    $componente->assertSet('activeTab', 'todos');
});

/*
|--------------------------------------------------------------------------
| R2 e R3 — o recorte da aba e o badge que a rotula
|--------------------------------------------------------------------------
*/

it('[CT-03] a aba "Pendentes de aprovação" mostra só os pendentes, e "Todos" mostra os dois', function (): void {
    $pendente = pendente('pendente@example.com');
    $ativo    = usuario('ativo@example.com');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->set('activeTab', 'pendentes')
        ->assertCanSeeTableRecords([$pendente])
        ->assertCanNotSeeTableRecords([$ativo])
        ->set('activeTab', 'todos')
        ->assertCanSeeTableRecords([$pendente, $ativo]);
});

/**
 * O badge é a contagem que ROTULA a aba: se ele discordar do que a aba mostra, ele informa um
 * número que a tela não sustenta. Três valores porque zero é o caso que some da tela.
 */
it('[CT-06] o badge conta os pendentes', function (int $quantos): void {
    for ($i = 0; $i < $quantos; $i++) {
        pendente("pendente{$i}@example.com");
    }

    usuario('ativo@example.com');

    $abas = Livewire::test(ListUsers::class)->instance()->getTabs();

    // `getBadge()` do Filament devolve string — o badge é rótulo, não número.
    expect($abas['pendentes']->getBadge())->toBe((string) $quantos);
})->with([
    'nenhum pendente' => 0,
    'um pendente'     => 1,
    'dois pendentes'  => 2,
]);

/**
 * Excluído logicamente sai dos dois — da aba e do badge — porque os dois leem a MESMA query do
 * Resource, que carrega o `SoftDeletes`. Um badge escrito com `User::query()` contaria o
 * excluído e mostraria um número ao lado de uma tabela que não o tem.
 */
it('[CT-04] o pendente excluído logicamente sai da aba e do badge', function (): void {
    $vivo = pendente('vivo@example.com');
    pendente('excluido@example.com')->delete();

    $componente = Livewire::test(ListUsers::class)->loadTable()->set('activeTab', 'pendentes');

    $componente->assertCanSeeTableRecords([$vivo]);

    expect($componente->instance()->getTabs()['pendentes']->getBadge())->toBe('1');
});

/*
|--------------------------------------------------------------------------
| R5 — a extração não mexeu no filtro, e os dois recortam igual
|--------------------------------------------------------------------------
*/

it('[CT-13] o filtro "Somente pendentes de aprovação" continua filtrando depois da extração', function (): void {
    $pendente = pendente('pendente@example.com');
    $ativo    = usuario('ativo@example.com');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->filterTable('aprovacao_pendente')
        ->assertCanSeeTableRecords([$pendente])
        ->assertCanNotSeeTableRecords([$ativo]);
});

/**
 * O caso que a extração existe para guardar: aba e filtro leem a mesma definição, então
 * combiná-los não pode mudar o conjunto. Se alguém reescrever a query dentro do `getTabs()` e
 * as duas divergirem, é aqui que aparece — e não em produção, meses depois.
 */
it('[CT-14] a aba e o filtro recortam o mesmo conjunto, e combiná-los não muda nada', function (): void {
    $pendente = pendente('pendente@example.com');
    $ativo    = usuario('ativo@example.com');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->set('activeTab', 'pendentes')
        ->filterTable('aprovacao_pendente')
        ->assertCanSeeTableRecords([$pendente])
        ->assertCanNotSeeTableRecords([$ativo]);
});

/*
|--------------------------------------------------------------------------
| R6 e R7 — as abas de convites, e o ternário que continua com três ramos
|--------------------------------------------------------------------------
*/

it('[CT-16] as abas de convites separam pendente de aceito', function (): void {
    $pendente = ofertaPara('pendente@example.com');
    $aceito   = ofertaPara('aceito@example.com', atributos: ['aceito_em' => now()]);

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->set('activeTab', 'pendentes')
        ->assertCanSeeTableRecords([$pendente])
        ->assertCanNotSeeTableRecords([$aceito])
        ->set('activeTab', 'aceitos')
        ->assertCanSeeTableRecords([$aceito])
        ->assertCanNotSeeTableRecords([$pendente])
        ->set('activeTab', 'todos')
        ->assertCanSeeTableRecords([$pendente, $aceito]);
});

/**
 * Recusado e expirado entram em "Pendentes" — "pendente" aqui é o oposto de "aceito", como o
 * `TernaryFilter` sempre foi. Quem separa os três estados é a coluna "Situação". Sem este caso,
 * um recorte "esperto" que excluísse recusado da aba passaria despercebido.
 */
it('[CT-16] recusado e expirado ficam em "Pendentes", porque pendente é o oposto de aceito', function (array $atributos): void {
    $convite = ofertaPara('alguem@example.com', atributos: $atributos);

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->set('activeTab', 'pendentes')
        ->assertCanSeeTableRecords([$convite])
        ->set('activeTab', 'aceitos')
        ->assertCanNotSeeTableRecords([$convite]);
})->with([
    'recusado' => fn (): array => ['recusado_em' => now()],
    'expirado' => fn (): array => ['expira_em' => now()->subDay()],
]);

it('[CT-18] o ramo em branco do filtro "Pendente" devolve a listagem inteira', function (): void {
    $pendente = ofertaPara('pendente@example.com');
    $aceito   = ofertaPara('aceito@example.com', atributos: ['aceito_em' => now()]);

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->filterTable('pendente', null)
        ->assertCanSeeTableRecords([$pendente, $aceito]);
});

it('[CT-19] o filtro e a aba de convites recortam o mesmo conjunto', function (): void {
    $pendente = ofertaPara('pendente@example.com');
    $aceito   = ofertaPara('aceito@example.com', atributos: ['aceito_em' => now()]);

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->filterTable('pendente', true)
        ->assertCanSeeTableRecords([$pendente])
        ->assertCanNotSeeTableRecords([$aceito]);

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->set('activeTab', 'pendentes')
        ->assertCanSeeTableRecords([$pendente])
        ->assertCanNotSeeTableRecords([$aceito]);
});

/*
|--------------------------------------------------------------------------
| R5 e R7 — o recorte tem UMA fonte
|--------------------------------------------------------------------------
*/

/**
 * Asserção de fonte, não de comportamento: nenhuma página de listagem pode escrever a condição
 * de recorte por conta própria. É o mutante que os casos acima **não** matam — uma cópia da
 * query dentro do `getTabs()` passa em todos eles no dia em que é escrita, e só diverge quando
 * a definição muda.
 */
it('[CT-15/CT-20] nenhuma página de listagem escreve o recorte por conta própria', function (string $arquivo): void {
    $fonte = file_get_contents(base_path($arquivo));

    expect($fonte)
        ->not->toContain("'aprovacao_pendente', true")
        ->not->toContain("whereNull('aceito_em')")
        ->not->toContain("whereNotNull('aceito_em')");
})->with([
    'admin/ListUsers'    => 'app/Filament/Admin/Resources/Users/Pages/ListUsers.php',
    'app/ListUsers'      => 'app/Filament/App/Resources/Users/Pages/ListUsers.php',
    'admin/ListConvites' => 'app/Filament/Admin/Resources/Convites/Pages/ListConvites.php',
    'app/ListConvites'   => 'app/Filament/App/Resources/Convites/Pages/ListConvites.php',
]);

/**
 * Controle da restrição negativa da RQ-09: o ledger de IA fica de fora de propósito — ele já
 * tem `SelectFilter('status')` na tela, e uma aba por status o duplicaria. O caso também é o
 * controle positivo de que a tela continua de pé: sem ele, "não tem aba" passaria com a
 * listagem quebrada.
 */
it('[CT-21] o ledger de execuções de IA continua sem abas, e de pé', function (): void {
    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));
    noPainelDoShield('infra');
    noPainelBootado('infra');

    $componente = Livewire::test(ListAiRuns::class);

    $componente->assertSuccessful();

    expect($componente->instance()->getTabs())->toBe([]);
});
