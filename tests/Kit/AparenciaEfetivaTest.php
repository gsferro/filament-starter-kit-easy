<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Livewire\PreferenciasDeAparencia;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * A aparência efetiva na página servida (Kit, sem organizações).
 *
 * IDs de CT em `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/04-casos-de-teste.md`
 * (R4, R6, R7, R8 e R12 — CT-29). O observável é a página servida: `aparenciaEfetiva()` de
 * `tests/Pest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    aparenciaDaInstalacao();
});

/** A administradora salva a tela de Configurações com os campos dados (transição real). */
function administradoraSalvaAsConfiguracoes(array $campos): void
{
    test()->flushSession();
    test()->actingAs(usuarioDoKit('admin', 'adm-'.uniqid().'@example.com'));

    Filament::setCurrentPanel('admin');

    $tela = Livewire::test(ConfiguracoesDoKit::class);
    carregarAba($tela, 'aplicacao')->refresh()
        ->fillForm($campos)
        ->call('save')
        ->assertHasNoFormErrors();

    alinharConfiguracoesDoKit();
}

$referencia = ['densidade' => 'Denso', 'fonte' => 'Muito grande', 'cor' => 'Red', 'menu' => 'Manter os ícones'];
$instalacao = ['densidade' => 'Compacto', 'fonte' => 'Grande', 'cor' => '#0ea5e9', 'menu' => 'Esconder tudo'];

/*
|--------------------------------------------------------------------------
| R4
|--------------------------------------------------------------------------
*/

it('[CT-09] a escolha gravada no /app vale nos três painéis com a chave ligada, e some com ela desligada', function (string $chave, string $pagina, bool $vale) use ($referencia, $instalacao): void {
    $this->actingAs(anaDoKit());

    gravarAparenciaPelaSecao(null, 'app');

    if ($chave === 'desligada') {
        gravarConfiguracao('aparencia_por_usuario', false);
        alinharConfiguracoesDoKit();
    }

    $efetiva = aparenciaEfetiva($pagina);

    expect(Arr::only($efetiva, ['densidade', 'fonte', 'cor', 'menu']))->toBe($vale ? $referencia : $instalacao);
})->with([
    'ligada /admin'    => ['ligada', '/admin', true],
    'ligada /app'      => ['ligada', '/app', true],
    'ligada /infra'    => ['ligada', '/infra', true],
    'desligada /admin' => ['desligada', '/admin', false],
    'desligada /app'   => ['desligada', '/app', false],
    'desligada /infra' => ['desligada', '/infra', false],
]);

it('[CT-10] quem não escolheu vê o padrão da instalação, mesmo logo depois de outra pessoa com escolha', function () use ($instalacao): void {
    // A Bia abre o /admin: o `panel_user` não entra nele, então ela é `admin` neste caso.
    $bia = usuarioDoKit('admin', 'bia@example.com');
    $ana = anaDoKit();

    $this->actingAs($bia);
    gravarAparenciaPelaSecao(null, 'admin');
    aparenciaEfetiva('/admin');

    $this->flushSession();
    $this->actingAs($ana);

    $efetiva = aparenciaEfetiva('/admin', fronteira: false);

    expect(Arr::only($efetiva, ['densidade', 'fonte', 'cor', 'menu']))->toBe($instalacao);
});

it('[CT-11] tela sem pessoa logada mostra o padrão da instalação', function (string $tela): void {
    config(['kit.registro.habilitado' => true]);

    $this->actingAs(anaDoKit());
    gravarAparenciaPelaSecao(null, 'app');

    Auth::forgetGuards();
    $this->flushSession();

    $efetiva = aparenciaEfetiva($tela);

    expect($efetiva)
        ->densidade->toBe('Compacto')
        ->fonte->toBe('Grande')
        ->cor->toBe('#0ea5e9');
})->with([
    'login admin'       => '/admin/login',
    'login app'         => '/app/login',
    'cadastro do app'   => '/app/register',
    'recuperar /admin'  => '/admin/password-reset/request',
]);

it('[CT-41] a pré-visualização das telas de acesso mostra o padrão, mesmo aberta por quem tem escolha', function () use ($instalacao): void {
    $this->actingAs(usuarioDoKit('admin', 'adm-preview@example.com'));
    gravarAparenciaPelaSecao(null, 'admin');

    expect(aparenciaEfetiva('/admin'))->densidade->toBe('Denso');

    $efetiva = aparenciaEfetiva(urlDoPreviewDe(rascunhoDe('global', aparenciaDeTeste('P'))));

    expect($efetiva)
        ->densidade->toBe($instalacao['densidade'])
        ->fonte->toBe($instalacao['fonte'])
        ->cor->toBe($instalacao['cor']);
});

it('[CT-37] a página reflete a escolha nova logo depois de a pessoa gravá-la', function (): void {
    $this->actingAs(anaDoKit());
    gravarAparenciaPelaSecao(null, 'admin');

    $antes = aparenciaEfetiva('/admin');
    expect($antes)->densidade->toBe('Denso')->cor->toBe('Red');

    gravarAparenciaPelaSecao(['densidade' => 'confortavel', 'cor' => 'Amber'], 'admin');

    $depois = aparenciaEfetiva('/admin', fronteira: false);
    expect($depois)->densidade->toBe('Confortável')->cor->toBe('Amber');
});

/*
|--------------------------------------------------------------------------
| R6
|--------------------------------------------------------------------------
*/

it('[CT-15] a escolha gravada fora do vocabulário mostra o padrão da instalação, sem erro', function (string $gravado) use ($instalacao): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    aparenciaDe($ana, json_decode($gravado));

    $efetiva = aparenciaEfetiva('/admin');

    expect(Arr::only($efetiva, ['densidade', 'fonte', 'cor', 'menu']))->toBe($instalacao)
        ->and($efetiva['html'])->not->toContain('alert(1)');
})->with([
    'densidade ultra'     => ['{"densidade": "ultra"}'],
    'densidade vazia'     => ['{"densidade": ""}'],
    'densidade nula'      => ['{"densidade": null}'],
    'fonte gigante'       => ['{"fonte": "gigante"}'],
    'menu nenhum'         => ['{"menu": "nenhum"}'],
    'cor Gray'            => ['{"cor": "Gray"}'],
    'cor WCAG_AA_TEXT'    => ['{"cor": "WCAG_AA_TEXT"}'],
    'cor hexadecimal'     => ['{"cor": "#ff0000"}'],
    'cor com injeção'     => ['{"cor": "Red</style><script>alert(1)</script>"}'],
    'densidade em lista'  => ['{"densidade": ["denso"]}'],
    'JSON escalar'        => ['"denso"'],
    'objeto vazio'        => ['{}'],
]);

it('[CT-16] o padrão da instalação gravado fora do vocabulário cai no de fábrica, sem erro', function (string $propriedade, string $gravado, string $preferencia, string $fabrica): void {
    gravarConfiguracao($propriedade, $gravado);
    alinharConfiguracoesDoKit();

    $this->actingAs(anaDoKit());

    expect(aparenciaEfetiva('/admin'))->{$preferencia}->toBe($fabrica);
})->with([
    'fonte' => ['tamanho_da_fonte', 'gigante', 'fonte', 'Normal'],
    'menu'  => ['modo_do_menu', 'nenhum', 'menu', 'Manter os ícones'],
]);

it('[CT-38] uma chave inválida no perfil não derruba a preferência válida gravada ao lado', function (string $gravado, array $esperado): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    aparenciaDe($ana, json_decode($gravado));

    expect(Arr::only(aparenciaEfetiva('/admin'), array_keys($esperado)))->toEqualCanonicalizing($esperado);
})->with([
    'densidade inválida, cor válida' => ['{"densidade": "ultra", "cor": "Red"}', ['densidade' => 'Compacto', 'cor' => 'Red']],
    'cor inválida, fonte válida'     => ['{"cor": "Gray", "fonte": "muito-grande"}', ['cor' => '#0ea5e9', 'fonte' => 'Muito grande']],
]);

/*
|--------------------------------------------------------------------------
| R7
|--------------------------------------------------------------------------
*/

it('[CT-17] a chave da instalação desligada e religada pela tela devolve a escolha da Ana', function (): void {
    $ana = anaDoKit();
    $this->actingAs($ana);
    gravarAparenciaPelaSecao(null, 'admin');

    administradoraSalvaAsConfiguracoes(['aparencia_por_usuario' => false]);
    administradoraSalvaAsConfiguracoes(['aparencia_por_usuario' => true]);

    $this->flushSession();
    $this->actingAs($ana);

    expect(aparenciaEfetiva('/admin'))->densidade->toBe('Denso')->cor->toBe('Red')
        ->and($ana->fresh()->aparencia)->toBe(escolhaDeReferencia());
});

it('[CT-39] religar a chave depois de uma gravação recusada não traz o que foi enviado com ela desligada', function (): void {
    $ana = anaDoKit();
    $this->actingAs($ana);
    gravarAparenciaPelaSecao(null, 'admin');

    $secao = Livewire::test(PreferenciasDeAparencia::class);

    gravarConfiguracao('aparencia_por_usuario', false);
    alinharConfiguracoesDoKit();

    $secao->fillForm(['densidade' => 'confortavel'])->call('salvar');

    administradoraSalvaAsConfiguracoes(['aparencia_por_usuario' => true]);

    $this->flushSession();
    $this->actingAs($ana);

    expect(aparenciaEfetiva('/admin'))->densidade->toBe('Denso')
        ->and($ana->fresh()->aparencia)->toBe(escolhaDeReferencia());
});

/*
|--------------------------------------------------------------------------
| R8
|--------------------------------------------------------------------------
*/

it('[CT-40] a mudança de padrão feita pela administradora chega a quem já estava navegando', function (): void {
    gravarConfiguracao('tamanho_da_fonte', 'normal');
    gravarConfiguracao('modo_do_menu', 'icones');
    alinharConfiguracoesDoKit();

    $bia = usuarioDoKit('panel_user', 'bia@example.com');
    $this->actingAs($bia);

    expect(aparenciaEfetiva('/app'))->fonte->toBe('Normal');

    administradoraSalvaAsConfiguracoes(['tamanho_da_fonte' => 'muito-grande']);

    $this->flushSession();
    $this->actingAs($bia);

    expect(aparenciaEfetiva('/app', fronteira: false))->fonte->toBe('Muito grande');
});

/*
|--------------------------------------------------------------------------
| R12 — CT-29
|--------------------------------------------------------------------------
*/

it('[CT-29] a seção Aparência, com os quatro campos, acompanha a chave da instalação nos três painéis', function (string $chave, string $painel): void {
    if ($chave === 'desligada') {
        gravarConfiguracao('aparencia_por_usuario', false);
        alinharConfiguracoesDoKit();
    }

    $this->actingAs(anaDoKit());

    $html = $this->get("/{$painel}/meu-perfil")->assertOk()->getContent();

    $secao = preg_match('~fi-section-header-heading[^>]*>\s*Aparência\s*<~u', $html) === 1;

    // controle positivo: o Meu perfil abriu e a seção de senha por e-mail (do kit) está nele
    expect($html)->toContain('Definir senha por e-mail');

    if ($chave === 'ligada') {
        expect($secao)->toBeTrue('a seção "Aparência" não apareceu')
            ->and($html)->toContain('Salvar aparência')
            ->toContain('Densidade do layout')
            ->toContain('Tamanho da fonte')
            ->toContain('Cor primária')
            ->toContain('Menu lateral ao recolher');

        return;
    }

    expect($secao)->toBeFalse('a seção "Aparência" apareceu com a chave desligada')
        ->and($html)->not->toContain('Salvar aparência');
})->with([
    'ligada /admin'    => ['ligada', 'admin'],
    'ligada /app'      => ['ligada', 'app'],
    'ligada /infra'    => ['ligada', 'infra'],
    'desligada /admin' => ['desligada', 'admin'],
    'desligada /app'   => ['desligada', 'app'],
    'desligada /infra' => ['desligada', 'infra'],
]);
