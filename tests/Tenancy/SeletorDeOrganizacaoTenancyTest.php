<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Support\SeletorDeOrganizacao;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Livewire\Sidebar;
use Livewire\Livewire;

/**
 * O seletor de organização do /app, com a opção "ocultar quando houver uma só".
 *
 * IDs de CT em `wikis/specs/main/ocultar-seletor-de-organizacao-unica/04-casos-de-teste.md`.
 *
 * Aqui e não em `tests/Kit` porque organização na rota e `admin_app` só existem com
 * `permission.teams` ligado, que `Tests\TenancyTestCase` fixa antes das migrations.
 *
 * Toda asserção de presença/ausência é feita sobre `fi-tenant-menu`, o marcador que o
 * componente `x-filament-panels::tenant-menu` desenha
 * (`vendor/filament/filament/resources/views/components/tenant-menu.blade.php:45`) — a
 * região inteira, não o texto do nome, que apareceria também no cabeçalho.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * A opção gravada E alinhada à config, pela cadeia real settings → config que o
 * `KitServiceProvider` roda no boot — o que a Closure de `tenantMenu()` lê por request.
 */
function ligarOcultarSeletor(bool $ligado = true): void
{
    gravarConfiguracao('ocultar_seletor_unico', $ligado);
    alinharConfiguracoesDoKit();
}

/*
|--------------------------------------------------------------------------
| R1 — a opção existe, grava e governa; default desligado
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a gravação por componente: o GET da tela pode ficar verde com o save()
 * quebrado, então o caminho feliz é preencher, salvar e conferir o efeito.
 */
it('[CT-01] ligar o interruptor na tela de configurações esconde o seletor', function (): void {
    $acme    = tenant('Acme', 'acme');
    $usuario = usuarioComPapel('admin_app', $acme);
    $usuario->tenants()->attach($acme);

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioComPapel('admin', null, 'admin@example.com'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['ocultar_seletor_unico' => true])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(configuracaoGravada('ocultar_seletor_unico'))->toBeTrue();

    // A cadeia real que a Closure de `tenantMenu()` lê por request.
    alinharConfiguracoesDoKit();

    noPainelDa($acme);
    $this->actingAs($usuario);

    expect(SeletorDeOrganizacao::visivel())->toBeFalse();
});

/** CT-02 — a migration semeia `false`; quem não escreveu nada vê o seletor de sempre. */
it('[CT-02] a opção nasce desligada e o seletor continua aparecendo', function (): void {
    $acme    = tenant('Acme', 'acme');
    $usuario = usuarioComPapel('admin_app', $acme);
    $usuario->tenants()->attach($acme);

    noPainelDa($acme);
    $this->actingAs($usuario);

    expect(configuracaoGravada('ocultar_seletor_unico'))->toBeFalse()
        ->and(SeletorDeOrganizacao::visivel())->toBeTrue();
});

/** CT-08 — tipo errado no campo: a regra `boolean` do Toggle recusa e nada se corrompe. */
it('[CT-08] um valor que não é booleano enviado ao campo não corrompe a configuração', function (): void {
    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioComPapel('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['ocultar_seletor_unico' => 'talvez'])
        ->call('save')
        ->assertHasFormErrors(['ocultar_seletor_unico']);

    expect(configuracaoGravada('ocultar_seletor_unico'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| R2 — exibição = opção × contagem de organizações acessíveis
|--------------------------------------------------------------------------
*/

/**
 * CT-03 — a tabela de decisão inteira, com a borda 1↔2 organizações.
 *
 * Vínculo ≠ acesso: o papel vai num contexto de organização, mas quem conta para o
 * seletor é o `attach` na relação `tenants()` — separar os dois é o que permite o
 * caso de zero acessível (a defesa do predicado: sem nenhuma, o /app nem resolve).
 */
it('[CT-03] o seletor só aparece quando há troca possível', function (bool $opcao, int $quantidade, bool $visivel): void {
    $organizacoes = [tenant('Acme', 'acme'), tenant('Globex', 'globex'), tenant('Initech', 'initech')];

    $usuario = usuarioComPapel('admin_app', $organizacoes[0]);
    $usuario->tenants()->attach(array_slice(array_map(
        static fn ($tenant): int => $tenant->id,
        $organizacoes,
    ), 0, $quantidade));

    ligarOcultarSeletor($opcao);

    noPainelDa($organizacoes[0]);
    $this->actingAs($usuario);

    expect(SeletorDeOrganizacao::visivel())->toBe($visivel);
})->with([
    'desligada e uma organização' => [false, 1, true],
    'ligada e nenhuma acessível'  => [true, 0, false],
    'ligada e uma organização'    => [true, 1, false],
    'ligada e duas organizações'  => [true, 2, true],
    'ligada e três organizações'  => [true, 3, true],
]);

/** CT-04 — o bloco INTEIRO some do HTML: avatar, rótulo e nome, não só a lista. */
it('[CT-04] ligada e uma organização: o bloco inteiro some da barra lateral', function (): void {
    $acme    = tenant('Acme', 'acme');
    $usuario = usuarioComPapel('admin_app', $acme);
    $usuario->tenants()->attach($acme);

    ligarOcultarSeletor();

    noPainelDa($acme);
    $this->actingAs($usuario);

    expect(Livewire::test(Sidebar::class)->html())->not->toContain('fi-tenant-menu');
});

/** CT-05 — com duas, o bloco continua no HTML. */
it('[CT-05] ligada e duas organizações: o bloco aparece', function (): void {
    $acme    = tenant('Acme', 'acme');
    $globex  = tenant('Globex', 'globex');
    $usuario = usuarioComPapel('admin_app', $acme);
    $usuario->tenants()->attach([$acme->id, $globex->id]);

    ligarOcultarSeletor();

    noPainelDa($acme);
    $this->actingAs($usuario);

    expect(Livewire::test(Sidebar::class)->html())->toContain('fi-tenant-menu');
});

/*
|--------------------------------------------------------------------------
| R3 — "acessível" é a mesma lista que o seletor usa
|--------------------------------------------------------------------------
*/

/** CT-06 — vínculo com inativa não entra na contagem: uma ativa + uma inativa = oculto. */
it('[CT-06] a organização inativa não entra na contagem', function (): void {
    $acme    = tenant('Acme', 'acme');
    $inativa = tenant('Inativa', 'inativa', ativo: false);
    $usuario = usuarioComPapel('admin_app', $acme);
    $usuario->tenants()->attach([$acme->id, $inativa->id]);

    ligarOcultarSeletor();

    noPainelDa($acme);
    $this->actingAs($usuario);

    expect(SeletorDeOrganizacao::visivel())->toBeFalse();
});

/** CT-07 — o master_global conta por TODAS as ativas, sem vínculo explícito. */
it('[CT-07] o master_global conta por todas as organizações ativas', function (int $quantidade, bool $visivel): void {
    $acme = tenant('Acme', 'acme');

    if ($quantidade === 2) {
        tenant('Globex', 'globex');
    }

    ligarOcultarSeletor();

    noPainelDa($acme);
    $this->actingAs(usuarioComPapel('master_global'));

    expect(SeletorDeOrganizacao::visivel())->toBe($visivel);
})->with([
    'uma organização ativa'    => [1, false],
    'duas organizações ativas' => [2, true],
]);

/*
|--------------------------------------------------------------------------
| R4 — o interruptor só aparece na tela com a multi-organização ligada
|--------------------------------------------------------------------------
*/

/** CT-10 — o par de CT-09 (que mora na suíte sem tenancy): ligada, o campo aparece. */
it('[CT-10] com a multi-organização ligada, o interruptor aparece', function (): void {
    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioComPapel('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->assertSchemaComponentVisible('ocultar_seletor_unico', 'form');
});
