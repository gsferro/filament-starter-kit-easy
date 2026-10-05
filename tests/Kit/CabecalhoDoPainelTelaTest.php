<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use Caresome\FilamentAuthDesigner\Pages\Auth\EmailVerification;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * A seção "Cabeçalho dos painéis" da tela /admin/configuracoes-da-aplicacao — gravação,
 * validação, visibilidade e autorização.
 *
 * IDs de CT em `wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/04-casos-de-teste.md`
 * (grupo G2, costura "componente Livewire/Filament").
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Filament::setCurrentPanel('admin');
});

// --- R13 — o detalhe só tem duas opções ----------------------------------------

it('[CT-17] o seletor do detalhe oferece exatamente duas opcoes: perfil e email', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    $opcoes = null;

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['cabecalho_usuario' => true])
        ->assertSchemaComponentExists('cabecalho_detalhe_do_usuario', checkComponentUsing: function ($componente) use (&$opcoes): bool {
            $opcoes = $componente->getOptions();

            return true;
        });

    expect($opcoes)->toBeArray()
        ->and($opcoes)->toHaveCount(2)
        ->and(array_keys($opcoes))->toEqualCanonicalizing(['perfil', 'email']);
})->group('kit');

// --- R16 — a tela grava as cinco opções e a config as reflete -------------------

it('[CT-21] grava as opcoes do cabecalho pela tela e a config as reflete depois do alinhamento', function (array $escolhas, ?string $detalhe, bool $partidaLigada): void {
    $this->actingAs(usuarioDoKit('admin'));

    $chaves = [
        'cabecalho_nome_do_projeto' => 'nome_do_projeto',
        'cabecalho_nome_do_painel'  => 'nome_do_painel',
        'cabecalho_logo_da_marca'   => 'logo_da_marca',
        'cabecalho_usuario'         => 'usuario',
    ];

    if ($partidaLigada) {
        Livewire::test(ConfiguracoesDoKit::class)
            ->fillForm(array_fill_keys(array_keys($chaves), true))
            ->call('save')
            ->assertHasNoFormErrors();

        app()->forgetInstance(SettingsDoKit::class);
    }

    $formulario = $escolhas + ($detalhe !== null ? ['cabecalho_detalhe_do_usuario' => $detalhe] : []);

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm($formulario)
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    foreach ($chaves as $propriedade => $chave) {
        expect(configuracaoGravada($propriedade))->toBe($escolhas[$propriedade], "gravado: {$propriedade}")
            ->and(config("kit.cabecalho.{$chave}"))->toBe($escolhas[$propriedade], "config: {$chave}");
    }

    if ($detalhe !== null) {
        expect(configuracaoGravada('cabecalho_detalhe_do_usuario'))->toBe($detalhe)
            ->and(config('kit.cabecalho.detalhe_do_usuario'))->toBe($detalhe);
    }
})->with([
    'linha 1 — projeto e usuário, e-mail' => [
        ['cabecalho_nome_do_projeto' => true, 'cabecalho_nome_do_painel' => false, 'cabecalho_logo_da_marca' => false, 'cabecalho_usuario' => true],
        'email',
        false,
    ],
    'linha 2 — painel e usuário, perfil' => [
        ['cabecalho_nome_do_projeto' => false, 'cabecalho_nome_do_painel' => true, 'cabecalho_logo_da_marca' => false, 'cabecalho_usuario' => true],
        'perfil',
        false,
    ],
    'linha 3 — logo e usuário, e-mail' => [
        ['cabecalho_nome_do_projeto' => false, 'cabecalho_nome_do_painel' => false, 'cabecalho_logo_da_marca' => true, 'cabecalho_usuario' => true],
        'email',
        false,
    ],
    'linha 4 — ligado para desligado' => [
        ['cabecalho_nome_do_projeto' => false, 'cabecalho_nome_do_painel' => false, 'cabecalho_logo_da_marca' => false, 'cabecalho_usuario' => false],
        null,
        true,
    ],
])->group('kit');

// --- R17 — o detalhe enviado pelo cliente fora do domínio é recusado ------------

it('[CT-22] com o bloco ligado, detalhe invalido enviado pelo cliente e recusado e nada e gravado', function (mixed $valor): void {
    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('cabecalho_usuario', true);
    gravarConfiguracao('cabecalho_detalhe_do_usuario', 'email');
    gravarConfiguracao('nome_da_aplicacao', 'Antes');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Não Gravou'])
        ->set('data.cabecalho_detalhe_do_usuario', $valor)
        ->call('save')
        ->assertHasFormErrors(['cabecalho_detalhe_do_usuario']);

    expect(configuracaoGravada('cabecalho_detalhe_do_usuario'))->toBe('email')
        ->and(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
})->with([
    'fora da lista'       => ['telefone'],
    'vazio'               => [''],
    'tipo errado: lista'  => [['email']],
    'tipo errado: numero' => [7],
])->group('kit');

it('[CT-23] com o bloco desligado, detalhe invalido enviado pelo cliente nao e gravado', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('cabecalho_usuario', false);
    gravarConfiguracao('cabecalho_detalhe_do_usuario', 'email');

    Livewire::test(ConfiguracoesDoKit::class)
        ->set('data.cabecalho_detalhe_do_usuario', 'telefone')
        ->call('save')
        ->assertOk();

    expect(configuracaoGravada('cabecalho_detalhe_do_usuario'))->toBe('email');
})->group('kit');

// --- R18 — a tela continua gravando --------------------------------------------

it('[CT-24] com o bloco desligado, salvar outro campo grava e preserva o detalhe', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('cabecalho_usuario', false);
    gravarConfiguracao('cabecalho_detalhe_do_usuario', 'email');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Gravou Mesmo Assim'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Gravou Mesmo Assim')
        ->and(configuracaoGravada('cabecalho_detalhe_do_usuario'))->toBe('email')
        ->and(configuracaoGravada('cabecalho_nome_do_projeto'))->toBeFalse()
        ->and(configuracaoGravada('cabecalho_nome_do_painel'))->toBeFalse()
        ->and(configuracaoGravada('cabecalho_logo_da_marca'))->toBeFalse()
        ->and(configuracaoGravada('cabecalho_usuario'))->toBeFalse();
})->group('kit');

it('[CT-25] com detalhe fora da lista gravado direto, salvar outro campo grava e normaliza o detalhe para perfil', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('cabecalho_usuario', true);
    gravarConfiguracao('cabecalho_detalhe_do_usuario', 'telefone');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Gravou Mesmo Assim'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Gravou Mesmo Assim')
        ->and(configuracaoGravada('cabecalho_detalhe_do_usuario'))->toBe('perfil');
})->group('kit');

// --- R19 — só quem tem a permissão da tela --------------------------------------

it('[CT-26] o administrador sem a permissao da tela e recusado na montagem', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    semAPermissao('admin', 'View:ConfiguracoesDoKit');

    Livewire::test(ConfiguracoesDoKit::class)->assertForbidden();
})->group('kit');

// --- R24 — a seção expõe as cinco opções ---------------------------------------

it('[CT-32] o seletor do detalhe acompanha o interruptor do bloco do usuario', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('cabecalho_usuario', false);

    Livewire::test(ConfiguracoesDoKit::class)
        ->assertSchemaComponentHidden('cabecalho_detalhe_do_usuario')
        ->fillForm(['cabecalho_usuario' => true])
        ->assertSchemaComponentVisible('cabecalho_detalhe_do_usuario');
})->group('kit');

it('[CT-33] a secao "Cabecalho dos paineis" traz as cinco opcoes', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    $tela = Livewire::test(ConfiguracoesDoKit::class)
        ->assertSee('Cabeçalho dos painéis');

    foreach ([
        'cabecalho_nome_do_projeto',
        'cabecalho_nome_do_painel',
        'cabecalho_logo_da_marca',
        'cabecalho_usuario',
        'cabecalho_detalhe_do_usuario',
    ] as $campo) {
        $tela->assertSchemaComponentExists($campo);
    }
})->group('kit');

// --- R22 — tela de autenticação autenticada, depois de um update Livewire --------

it('[CT-35] a tela de verificacao de e-mail continua sem composicao depois de um update Livewire', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Projeto Ômega');
    gravarConfiguracao('cabecalho_nome_do_projeto', true);
    alinharConfiguracoesDoKit();

    $usuario = usuarioDoKit('panel_user');
    $usuario->forceFill(['email_verified_at' => null])->save();

    Filament::setCurrentPanel('app');

    $tela = Livewire::actingAs($usuario)
        ->test(EmailVerification::class);

    expect($tela->html())->not->toContain('kit-cabecalho');

    $tela->call('$refresh');

    expect($tela->html())->not->toContain('kit-cabecalho');
})->group('kit');
