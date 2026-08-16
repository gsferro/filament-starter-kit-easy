<?php

use App\Support\CorPrimaria;
use App\Support\CustomizadorDaInstalacao;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

/**
 * A cor primária escolhida na instalação (`KIT_COR_PRIMARIA`).
 *
 * O que se afirma aqui é a PALETA registrada, não o pixel: a cor chega à tela
 * pelo `->colors()` dos três painéis, e o que decide o resultado é o que
 * `CorPrimaria::paleta()` devolve. Pixel exigiria navegador e não provaria mais
 * nada sobre esta regra.
 *
 * A precedência contra a cor de uma ORGANIZAÇÃO é testada em
 * tests/Tenancy/IdentidadeVisualTest.php — lá existe tenant.
 */
it('devolve a paleta da cor configurada', function (): void {
    config(['kit.cor_primaria' => 'Blue']);

    expect(CorPrimaria::paleta())->toBe(['primary' => Color::Blue]);
})->group('kit');

it('mantém o padrão do Filament quando não há cor configurada', function (mixed $valor): void {
    config(['kit.cor_primaria' => $valor]);

    expect(CorPrimaria::paleta())->toBe([]);
})->with([
    'não definida' => null,
    'vazia'        => '',
])->group('kit');

/**
 * `constant()` num nome inexistente lança `Error: Undefined constant`. Como isto
 * roda no boot de TODO painel, o erro derrubaria toda página do projeto — não
 * uma tela. Um .env editado à mão com um nome inválido volta ao padrão.
 */
it('ignora nome de cor que não existe na paleta em vez de derrubar o painel', function (): void {
    config(['kit.cor_primaria' => 'Roxo']);

    expect(CorPrimaria::paleta())->toBe([]);
})->group('kit');

it('só oferece cores que existem de verdade na paleta do Filament', function (): void {
    foreach (CustomizadorDaInstalacao::CORES as $cor) {
        expect(defined(Color::class.'::'.$cor))->toBeTrue("A cor `{$cor}` é oferecida na instalação e não existe em Filament\\Support\\Colors\\Color.");
    }
})->group('kit');

it('registra a cor configurada nos três painéis', function (string $painel): void {
    config(['kit.cor_primaria' => 'Emerald']);

    fronteiraDeRequest();
    noPainelBootado($painel);

    expect(FilamentColor::getColors()['primary'])
        ->toBe(Color::all()['emerald']);
})->with(['app', 'admin', 'infra'])->group('kit');
