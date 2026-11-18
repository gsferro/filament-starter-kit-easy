<?php

use App\Livewire\PreferenciasDeAparencia;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * A seção Aparência do Meu perfil: gravação pela pessoa (Kit, sem organizações).
 *
 * IDs de CT em `wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/04-casos-de-teste.md`
 * (R1, R2, R3 e CT-31 da R12).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    aparenciaDaInstalacao();
});

/** A Ana com a seção Aparência aberta: GET real do Meu perfil (boota o painel) e o componente. */
function abrirSecaoDeAparencia(string $painel = 'admin'): Testable
{
    test()->get("/{$painel}/meu-perfil")->assertOk();

    Filament::setCurrentPanel($painel);

    return Livewire::test(PreferenciasDeAparencia::class);
}

/** O que a instalação de referência mostra a quem não escolheu. */
function paginaDaInstalacaoDeReferencia(): array
{
    return ['densidade' => 'Compacto', 'fonte' => 'Grande', 'cor' => '#0ea5e9', 'menu' => 'Esconder tudo'];
}

/*
|--------------------------------------------------------------------------
| R1
|--------------------------------------------------------------------------
*/

it('[CT-01] a escolha gravada pela seção Aparência governa a página seguinte, e só ela fica gravada', function (string $preferencia, string $valor, string $mostrado): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    gravarAparenciaPelaSecao([$preferencia => $valor], 'admin');

    $efetiva = aparenciaEfetiva('/admin');

    expect($ana->fresh()->aparencia)->toBe([$preferencia => $valor])
        ->and($efetiva[$preferencia])->toBe($mostrado)
        ->and(Arr::except(Arr::only($efetiva, ['densidade', 'fonte', 'cor', 'menu']), [$preferencia]))
        ->toBe(Arr::except(paginaDaInstalacaoDeReferencia(), [$preferencia]));
})->with([
    'densidade Compacto'        => ['densidade', 'compacto', 'Compacto'],
    'densidade Denso'           => ['densidade', 'denso', 'Denso'],
    'densidade Confortável'     => ['densidade', 'confortavel', 'Confortável'],
    'fonte Pequena'             => ['fonte', 'pequena', 'Pequena'],
    'fonte Normal'              => ['fonte', 'normal', 'Normal'],
    'fonte Muito grande'        => ['fonte', 'muito-grande', 'Muito grande'],
    'cor Amber'                 => ['cor', 'Amber', 'Amber'],
    'cor Violet'                => ['cor', 'Violet', 'Violet'],
    'menu Manter os ícones'     => ['menu', 'icones', 'Manter os ícones'],
]);

it('[CT-02] o que ficou em "Padrão" acompanha a instalação quando ela muda depois', function (): void {
    aparenciaDaInstalacao(corHex: null);

    $ana = anaDoKit();
    $this->actingAs($ana);

    gravarAparenciaPelaSecao(['densidade' => 'denso', 'fonte' => 'muito-grande'], 'admin');
    gravarAparenciaPelaSecao(['densidade' => null], 'admin');

    aparenciaDaInstalacao(densidade: 'confortavel', fonte: null, menu: 'icones', cor: 'Amber', corHex: null);

    expect(aparenciaEfetiva('/admin'))
        ->densidade->toBe('Confortável')
        ->cor->toBe('Amber')
        ->menu->toBe('Manter os ícones')
        ->fonte->toBe('Muito grande');
});

it('[CT-03] a seção oferece exatamente o vocabulário de cada preferência, mais "Padrão"', function (): void {
    $this->actingAs(anaDoKit());

    $secao = abrirSecaoDeAparencia();

    $campos = collect($secao->instance()->form->getFlatComponents())->keyBy(fn ($c): string => $c->getName());

    $selects = $campos->filter(fn ($c): bool => $c instanceof Select);

    expect($campos->keys()->all())->toEqualCanonicalizing(['densidade', 'fonte', 'cor', 'menu'])
        ->and($selects)->toHaveCount(4);

    foreach ($selects as $nome => $select) {
        expect($select->isRequired())->toBeFalse("o campo {$nome} é obrigatório");
        expect((string) $select->getPlaceholder())->toStartWith('Padrão');
    }

    $rotulos = fn (string $campo): array => array_map(
        fn (string $rotulo): string => trim(explode(' —', preg_replace('/ \(.*/', '', $rotulo))[0]),
        $selects[$campo]->getOptions(),
    );

    expect($rotulos('densidade'))->toBe(['confortavel' => 'Confortável', 'compacto' => 'Compacto', 'denso' => 'Denso'])
        ->and($rotulos('fonte'))->toBe(['pequena' => 'Pequena', 'normal' => 'Normal', 'grande' => 'Grande', 'muito-grande' => 'Muito grande'])
        ->and(array_values($selects['cor']->getOptions()))->toBe([
            'Amber', 'Blue', 'Cyan', 'Emerald', 'Fuchsia', 'Indigo', 'Lime', 'Orange',
            'Pink', 'Purple', 'Red', 'Rose', 'Sky', 'Slate', 'Teal', 'Violet',
        ])
        ->and(array_values($selects['menu']->getOptions()))->toHaveCount(2);

    expect($selects['menu']->getOptions())->toHaveKeys(['icones', 'oculto']);
    expect(array_values($selects['menu']->getOptions())[0])->toStartWith('Manter os ícones')
        ->and(array_values($selects['menu']->getOptions())[1])->toStartWith('Esconder tudo');
});

/*
|--------------------------------------------------------------------------
| R2
|--------------------------------------------------------------------------
*/

it('[CT-04] valor fora do domínio escrito direto no estado do componente não vale', function (string $chave, string $valor): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    abrirSecaoDeAparencia()->set("data.{$chave}", $valor)->call('salvar');

    $efetiva = aparenciaEfetiva('/admin');

    expect(Arr::get((array) $ana->fresh()->aparencia, $chave))->not->toBe($valor)
        ->and($efetiva[$chave])->toBe(paginaDaInstalacaoDeReferencia()[$chave])
        ->and($efetiva['html'])->not->toContain('alert(1)');
})->with([
    'densidade ultra'    => ['densidade', 'ultra'],
    'fonte gigante'      => ['fonte', 'gigante'],
    'menu nenhum'        => ['menu', 'nenhum'],
    'cor Gray'           => ['cor', 'Gray'],
    'cor WCAG_AA_TEXT'   => ['cor', 'WCAG_AA_TEXT'],
    'cor hexadecimal'    => ['cor', '#ff0000'],
    'cor com injeção'    => ['cor', 'Red;}</style><script>alert(1)</script>'],
]);

it('[CT-05] valor de tipo errado escrito no estado do componente não vale e não quebra a tela', function (string $chave, mixed $valor): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    abrirSecaoDeAparencia()->set("data.{$chave}", $valor)->call('salvar');

    expect(Arr::get((array) $ana->fresh()->aparencia, $chave))->toBeNull();
    expect(Arr::only(aparenciaEfetiva('/admin'), ['densidade', 'fonte', 'cor', 'menu']))->toBe(paginaDaInstalacaoDeReferencia());
})->with([
    'densidade lista'    => ['densidade', ['denso']],
    'fonte inteiro'      => ['fonte', 3],
    'cor objeto'         => ['cor', ['a' => 'Red']],
    'menu booleano'      => ['menu', true],
]);

/*
|--------------------------------------------------------------------------
| R3
|--------------------------------------------------------------------------
*/

it('[CT-06] campo extra no envio não grava nada fora das preferências da própria Ana', function (string $extra): void {
    $bia = usuarioDoKit('panel_user', 'bia@example.com');
    aparenciaDe($bia, ['densidade' => 'compacto']);

    $ana = anaDoKit();
    $this->actingAs($ana);

    $valor = match ($extra) {
        'id', 'user_id' => $bia->getKey(),
        'email'         => 'outra@example.com',
        'tema'          => 'x',
    };

    abrirSecaoDeAparencia()
        ->fillForm(['densidade' => 'denso'])
        ->set("data.{$extra}", $valor)
        ->call('salvar');

    $guardado = $ana->fresh();

    expect($guardado->aparencia)->toBe(['densidade' => 'denso'])
        ->and($bia->fresh()->aparencia)->toBe(['densidade' => 'compacto'])
        ->and($guardado->email)->toBe('ana@example.com');
})->with(['id', 'user_id', 'email', 'tema']);

it('[CT-35] o formulário de dados pessoais do Meu perfil não grava a aparência', function (): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    $this->get('/admin/meu-perfil')->assertOk();
    Filament::setCurrentPanel('admin');

    Livewire::test(PersonalInfo::class)
        ->set('data.aparencia', ['densidade' => 'denso'])
        ->call('submit');

    expect($ana->fresh()->aparencia)->toBeNull()
        ->and(aparenciaEfetiva('/admin'))->densidade->toBe('Compacto');
});

it('[CT-07] upload para o componente da seção é recusado', function (): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    abrirSecaoDeAparencia()
        ->call('_startUpload', 'data.cor', [['name' => 'x.png', 'size' => 10, 'type' => 'image/png']], false)
        ->assertForbidden();

    expect($ana->fresh()->aparencia)->toBeNull();

    $this->get('/admin/meu-perfil')->assertOk();
});

it('[CT-08] com a chave desligada depois de a seção abrir, a gravação é recusada com aviso e nada muda no perfil', function (string $estado, string $enviado): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    if ($estado === 'referencia') {
        gravarAparenciaPelaSecao(null, 'admin');
    }

    $secao = abrirSecaoDeAparencia();

    gravarConfiguracao('aparencia_por_usuario', false);
    alinharConfiguracoesDoKit();

    limparAvisosDoFilament();

    $secao->fillForm(['densidade' => $enviado])->call('salvar');

    $titulos = collect(avisosDoFilament())->map(fn (array $aviso): string => ($aviso['title'] ?? '').' '.($aviso['body'] ?? ''))->implode(' | ');

    expect($ana->fresh()->aparencia)->toBe($estado === 'referencia' ? escolhaDeReferencia() : null)
        ->and($titulos)->toMatch('/desligad/iu')
        ->and($titulos)->not->toContain('Aparência salva');
})->with([
    'vazio'      => ['vazio', 'denso'],
    'referência' => ['referencia', 'confortavel'],
]);

/*
|--------------------------------------------------------------------------
| R12 — CT-31
|--------------------------------------------------------------------------
*/

it('[CT-31] sem organizações, a seção não mostra o aviso em nenhum painel', function (string $painel): void {
    $this->actingAs(anaDoKit());

    $html  = $this->get("/{$painel}/meu-perfil")->assertOk()->getContent();
    $texto = preg_replace('/\s+/', ' ', strip_tags($html));

    expect($html)->toContain('Salvar aparência')
        ->toContain('Densidade do layout')
        ->toContain('Tamanho da fonte')
        ->toContain('Cor primária')
        ->toContain('Menu lateral ao recolher');

    expect(preg_match('/organiza[çc][ãa]o\s+liber/iu', $texto))->toBe(0);

    // Nenhuma menção a organização em lugar nenhum da seção — nem no "Padrão" dos campos (QA-04 do gate).
    $secao  = abrirSecaoDeAparencia($painel);
    $textos = collect(['densidade', 'fonte', 'cor', 'menu'])
        ->map(fn (string $campo): string => (string) $secao->instance()->form->getComponent(
            fn ($c): bool => $c instanceof Select && $c->getName() === $campo,
        )?->getPlaceholder())
        ->push(preg_replace('/\s+/', ' ', strip_tags($secao->html())))
        ->implode(' | ');

    expect(preg_match('/organiza/iu', $textos))->toBe(0, $textos);
})->with(['admin', 'app', 'infra']);

it('[CT-43] a gravação e a recusa ficam no log só com os valores do vocabulário', function (): void {
    $ana = anaDoKit();
    $this->actingAs($ana);

    $registros = [];
    Event::listen(
        MessageLogged::class,
        function (MessageLogged $evento) use (&$registros): void {
            if (str_starts_with($evento->message, '[PreferenciasDeAparencia@salvar]')) {
                $registros[] = [$evento->level, $evento->message, $evento->context];
            }
        },
    );

    abrirSecaoDeAparencia()->fillForm(escolhaDeReferencia() + ['extra' => "x\nforjado"])->call('salvar');

    $secao = abrirSecaoDeAparencia();
    gravarConfiguracao('aparencia_por_usuario', false);
    alinharConfiguracoesDoKit();
    $secao->fillForm(['densidade' => 'denso'])->call('salvar');

    expect($registros)->toHaveCount(2)
        ->and($registros[0][0])->toBe('info')
        ->and($registros[0][1])->toStartWith('[PreferenciasDeAparencia@salvar] Aparência pessoal salva')
        ->and($registros[0][2])->toBe(['user_id' => $ana->getKey(), 'aparencia' => escolhaDeReferencia()])
        ->and($registros[1][0])->toBe('warning')
        ->and($registros[1][1])->toStartWith('[PreferenciasDeAparencia@salvar] Gravação recusada')
        ->and($registros[1][2])->toBe(['user_id' => $ana->getKey()]);
});

it('[CT-46] o "Padrão" de cada campo diz qual é o padrão vigente da aplicação', function (): void {
    $this->actingAs(anaDoKit());

    $secao = abrirSecaoDeAparencia();

    $placeholder = fn (string $campo): string => (string) $secao->instance()->form->getComponent(
        fn ($c): bool => $c instanceof Select && $c->getName() === $campo,
    )?->getPlaceholder();

    expect($placeholder('densidade'))->toContain('Compacto')
        ->and($placeholder('fonte'))->toContain('Grande')
        ->and($placeholder('cor'))->toContain('#0ea5e9')
        ->and($placeholder('menu'))->toContain('Esconder tudo');
});
