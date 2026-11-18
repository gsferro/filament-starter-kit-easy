<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * Os padrões e a chave da aparência, na tela de Configurações da aplicação (Kit).
 *
 * IDs de CT em `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/04-casos-de-teste.md`
 * (R8 — CT-20, CT-21; R10 — CT-25; R11 — CT-27).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Filament::setCurrentPanel('admin');
});

it('[CT-20] o que a administradora grava nas Configurações governa a página de quem não escolheu', function (): void {
    aparenciaDaInstalacao(true, 'compacto', 'normal', 'icones');

    $bia = usuarioDoKit('panel_user', 'bia@example.com');

    $this->actingAs(usuarioDoKit('admin', 'adm@example.com'));

    $tela = Livewire::test(ConfiguracoesDoKit::class);
    carregarAba($tela, 'aplicacao')->refresh()
        ->fillForm(['tamanho_da_fonte' => 'grande', 'modo_do_menu' => 'oculto', 'aparencia_por_usuario' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('tamanho_da_fonte'))->toBe('grande')
        ->and(configuracaoGravada('modo_do_menu'))->toBe('oculto')
        ->and(configuracaoGravada('aparencia_por_usuario'))->toBeFalse();

    alinharConfiguracoesDoKit();

    $this->flushSession();
    $this->actingAs($bia);

    expect(aparenciaEfetiva('/app'))->fonte->toBe('Grande')->menu->toBe('Esconder tudo');
});

it('[CT-21] os valores de fábrica são a chave ligada, a fonte Normal e o menu com ícones', function (string $variavel, string $chaveDoArquivo, string $propriedade, mixed $fabrica): void {
    expect(kitConfigCom($variavel, null)[$chaveDoArquivo])->toBe($fabrica)
        ->and(configuracaoGravada($propriedade))->toBe($fabrica);
})->with([
    'chave da instalação' => ['KIT_APARENCIA_POR_USUARIO', 'aparencia_por_usuario', 'aparencia_por_usuario', true],
    'tamanho da fonte'    => ['KIT_TAMANHO_DA_FONTE', 'tamanho_da_fonte', 'tamanho_da_fonte', 'normal'],
    'modo do menu'        => ['KIT_MODO_DO_MENU', 'modo_do_menu', 'modo_do_menu', 'icones'],
]);

it('[CT-25] fonte e menu fora do vocabulário no estado da tela de Configurações não são gravados como vieram', function (string $campo, mixed $valor, array $vocabulario): void {
    aparenciaDaInstalacao(true, 'compacto', 'grande', 'oculto');

    $this->actingAs(usuarioDoKit('admin', 'adm@example.com'));

    $tela = Livewire::test(ConfiguracoesDoKit::class);
    carregarAba($tela, 'aplicacao')->refresh()
        ->fillForm([$campo => $valor])
        ->call('save');

    expect($vocabulario)->toContain(configuracaoGravada($campo))
        ->and(configuracaoGravada($campo))->not->toBe($valor);
})->with([
    'fonte gigante'  => ['tamanho_da_fonte', 'gigante', ['pequena', 'normal', 'grande', 'muito-grande']],
    'menu nenhum'    => ['modo_do_menu', 'nenhum', ['icones', 'oculto']],
    'fonte em lista' => ['tamanho_da_fonte', ['grande'], ['pequena', 'normal', 'grande', 'muito-grande']],
    'menu inteiro'   => ['modo_do_menu', 1, ['icones', 'oculto']],
]);

it('[CT-27] quem não administra a instalação não chega às Configurações', function (string $papel, string $email, string $painel): void {
    aparenciaDaInstalacao();

    $pessoa = usuarioDoKit($papel, $email);
    $this->actingAs($pessoa);

    $this->get('/admin/configuracoes-da-aplicacao')->assertForbidden();

    expect(ConfiguracoesDoKit::canAccess())->toBeFalse()
        ->and(configuracaoGravada('aparencia_por_usuario'))->toBeTrue();

    $this->get($painel)->assertOk();
})->with([
    'Ivo (infra)'      => ['infra', 'ivo@example.com', '/infra'],
    'Bia (panel_user)' => ['panel_user', 'bia@example.com', '/app'],
]);

it('[CT-44] os quatro campos de aparência moram na seção "Aparência dos painéis", sem "Padrão" selecionável', function (): void {
    $this->actingAs(usuarioDoKit('admin', 'adm-secao@example.com'));

    $tela = carregarAba(Livewire::test(ConfiguracoesDoKit::class), 'aplicacao')->refresh();

    foreach (['aparencia_por_usuario', 'densidade_do_layout', 'tamanho_da_fonte', 'modo_do_menu'] as $campo) {
        $componente = $tela->instance()->form->getComponent(
            fn ($c): bool => method_exists($c, 'getName') && $c->getName() === $campo,
            withHidden: true,
        );

        expect($componente)->not->toBeNull("o campo {$campo} não existe")
            ->and($componente->getContainer()->getParentComponent()?->getId())->toBe('configuracoes-aplicacao-aparencia');

        if ($campo !== 'aparencia_por_usuario') {
            expect($componente->canSelectPlaceholder())->toBeFalse("{$campo} deixa escolher o vazio");
        }
    }
});
