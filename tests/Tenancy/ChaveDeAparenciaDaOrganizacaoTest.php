<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * A chave da aparência pessoal no cadastro da organização (suíte Tenancy).
 *
 * IDs de CT em `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/04-casos-de-teste.md`
 * (R7 — CT-19; R9 — CT-22, CT-23, CT-24; R10 — CT-26; R11 — CT-28).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    aparenciaDaInstalacao(corHex: null);
});

/** Quem administra a instalação, no `/admin`: master_global (padrão de `PaletaDaOrganizacaoTest`). */
function administradoraDaChave(): void
{
    test()->flushSession();
    Filament::setCurrentPanel('admin');
    test()->actingAs(usuarioComPapel('master_global', null, 'adm-'.uniqid().'@example.com'));
}

/** A Ana (membro de tudo) com a escolha de referência gravada pela seção; devolve ela e as organizações. */
function anaComAEscolhaDeReferencia(): array
{
    $dados = anaComTresOrganizacoes();

    test()->actingAs($dados['ana']);
    gravarAparenciaPelaSecao(null, 'admin');

    return $dados;
}

/** O que a Ana vê numa página, depois de a administradora agir. */
function densidadeVistaPelaAna(User $ana, string $pagina): string
{
    test()->flushSession();
    test()->actingAs($ana);

    return aparenciaEfetiva($pagina)['densidade'];
}

it('[CT-19] com a chave da instalação desligada, editar a organização não mexe na chave dela', function (string $alteracao): void {
    ['acme' => $acme] = anaComTresOrganizacoes();
    $acme->update(['aparencia_por_usuario' => true]);

    gravarConfiguracao('aparencia_por_usuario', false);
    alinharConfiguracoesDoKit();

    administradoraDaChave();

    $edicao = Livewire::test(EditTenant::class, ['record' => $acme->getRouteKey()]);

    if ($alteracao === 'nome') {
        $edicao->fillForm(['nome' => 'Acme S.A.']);
    } else {
        $edicao->set('data.aparencia_por_usuario', false);
    }

    $edicao->call('save');

    expect($acme->fresh()->aparencia_por_usuario)->toBeTrue();
})->with(['só o nome' => ['nome'], 'interruptor forjado' => ['forjado']]);

it('[CT-22] ligar o interruptor no cadastro libera a escolha pessoal dentro da organização', function (string $acao, string $slug): void {
    ['ana' => $ana, 'acme' => $acme] = anaComAEscolhaDeReferencia();

    if ($acao === 'cria') {
        administradoraDaChave();

        Livewire::test(CreateTenant::class)
            ->fillForm(['nome' => 'Umbrella', 'slug' => $slug, 'ativo' => true, 'aparencia_por_usuario' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $organizacao = Tenant::where('slug', $slug)->firstOrFail();
        papelNaOrganizacao($ana, 'panel_user', $organizacao);
        $ana->tenants()->attach($organizacao->getKey());
    } else {
        administradoraDaChave();

        Livewire::test(EditTenant::class, ['record' => $acme->getRouteKey()])
            ->fillForm(['aparencia_por_usuario' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $organizacao = $acme;
    }

    expect($organizacao->fresh()->aparencia_por_usuario)->toBeTrue()
        ->and(densidadeVistaPelaAna($ana, "/app/{$slug}"))->toBe('Denso');
})->with([
    'cria'  => ['cria', 'umbrella'],
    'edita' => ['edita', 'acme'],
]);

it('[CT-23] a chave da organização nasce desligada', function (string $origem): void {
    ['ana' => $ana] = anaComAEscolhaDeReferencia();

    if ($origem === 'cria') {
        administradoraDaChave();

        Livewire::test(CreateTenant::class)
            ->fillForm(['nome' => 'Umbrella', 'slug' => 'umbrella', 'ativo' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $organizacao = Tenant::where('slug', 'umbrella')->firstOrFail();
    } else {
        // Linha de antes da feature: inserida sem a coluna nova.
        $id          = DB::table('tenants')->insertGetId(['uuid' => (string) Str::uuid(), 'nome' => 'Umbrella', 'slug' => 'umbrella', 'ativo' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $organizacao = Tenant::findOrFail($id);
    }

    papelNaOrganizacao($ana, 'panel_user', $organizacao);
    $ana->tenants()->attach($organizacao->getKey());

    expect($organizacao->fresh()->aparencia_por_usuario)->toBeFalse()
        ->and(densidadeVistaPelaAna($ana, '/app/umbrella'))->toBe('Compacto');
})->with([
    'cria sem tocar'       => ['cria'],
    'existente anterior'   => ['existente'],
]);

it('[CT-24] o interruptor "Liberar a aparência pessoal" só aparece com a chave da instalação ligada', function (bool $instalacao, string $tela): void {
    ['acme' => $acme] = anaComTresOrganizacoes();

    aparenciaDaInstalacao($instalacao, corHex: null);

    administradoraDaChave();

    $componente = $tela === 'criacao'
        ? Livewire::test(CreateTenant::class)
        : Livewire::test(EditTenant::class, ['record' => $acme->getRouteKey()]);

    // controle positivo: a tela abriu e um campo vizinho está nela
    $componente->assertSchemaComponentVisible('nome');

    $instalacao
        ? $componente->assertSchemaComponentVisible('aparencia_por_usuario')
        : $componente->assertSchemaComponentHidden('aparencia_por_usuario');
})->with([
    'ligada criação'    => [true, 'criacao'],
    'ligada edição'     => [true, 'edicao'],
    'desligada criação' => [false, 'criacao'],
    'desligada edição'  => [false, 'edicao'],
]);

it('[CT-26] valor não booleano numa chave é recusado e a chave fica como estava', function (bool $instalacaoAntes, string $tela, mixed $valor): void {
    ['ana' => $ana, 'acme' => $acme] = anaComAEscolhaDeReferencia();

    if (! $instalacaoAntes) {
        gravarConfiguracao('aparencia_por_usuario', false);
        alinharConfiguracoesDoKit();
    }

    administradoraDaChave();

    if ($tela === 'configuracoes') {
        $componente = Livewire::test(ConfiguracoesDoKit::class);
        carregarAba($componente, 'aplicacao')->refresh()
            ->fillForm(['aparencia_por_usuario' => $valor])
            ->call('save')
            ->assertHasFormErrors(['aparencia_por_usuario']);

        expect(configuracaoGravada('aparencia_por_usuario'))->toBeFalse();
        alinharConfiguracoesDoKit();
        $pagina = '/admin';
    } else {
        Livewire::test(EditTenant::class, ['record' => $acme->getRouteKey()])
            ->fillForm(['aparencia_por_usuario' => $valor])
            ->call('save')
            ->assertHasFormErrors(['aparencia_por_usuario']);

        expect($acme->fresh()->aparencia_por_usuario)->toBeFalse();
        $pagina = '/app/acme';
    }

    expect(densidadeVistaPelaAna($ana, $pagina))->toBe('Compacto');
})->with([
    'instalação "talvez"'   => [false, 'configuracoes', 'talvez'],
    'Acme "talvez"'         => [true, 'acme', 'talvez'],
    'Acme ["1"]'            => [true, 'acme', ['1']],
]);

it('[CT-28] quem administra uma organização não libera a aparência dela', function (): void {
    ['acme' => $acme] = anaComTresOrganizacoes();

    $carla = usuarioComPapel('admin_app', $acme, 'carla@example.com');
    $carla->tenants()->attach($acme->getKey());

    $this->actingAs($carla);

    $this->get("/admin/organizacoes/{$acme->getRouteKey()}/edit")->assertForbidden();

    expect($carla->can('update', $acme))->toBeFalse()
        ->and($acme->fresh()->aparencia_por_usuario)->toBeFalse();

    $this->get('/app/acme')->assertOk();
});
