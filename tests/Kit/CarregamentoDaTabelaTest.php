<?php

use App\Support\CarregamentoDaTabela;
use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;

it('[CT-09] aceita so os tres valores e coage o resto para paginacao', function (mixed $bruto, string $resultado): void {
    expect(CarregamentoDaTabela::coagir($bruto)->value)->toBe($resultado);
})->with([
    '"paginacao" | paginacao | válido'                   => ['paginacao', 'paginacao'],
    '"rolagem" | rolagem | válido'                       => ['rolagem', 'rolagem'],
    '"botao" | botao | válido'                           => ['botao', 'botao'],
    '"scroll" | paginacao | valor do pacote, não do kit' => ['scroll', 'paginacao'],
    '"button" | paginacao | valor do pacote, não do kit' => ['button', 'paginacao'],
    '"ROLAGEM" | paginacao | caixa'                      => ['ROLAGEM', 'paginacao'],
    '" rolagem" | paginacao | espaço na borda'           => [' rolagem', 'paginacao'],
    '"botão" | paginacao | acento'                       => ['botão', 'paginacao'],
    '"" | paginacao | vazio'                             => ['', 'paginacao'],
    'null | paginacao | ausente'                         => [null, 'paginacao'],
    '1 | paginacao | tipo errado (inteiro)'              => [1, 'paginacao'],
    '["rolagem"] | paginacao | tipo errado (lista)'      => [['rolagem'], 'paginacao'],
])->group('kit');

it('[CT-10] resolve o modo de cada valor e oferece exatamente as tres opcoes', function (string $valor, ?InfiniteScrollMode $modo): void {
    expect(CarregamentoDaTabela::from($valor)->modoDoPacote())->toBe($modo);

    expect(array_keys(CarregamentoDaTabela::opcoes()))->toBe(['paginacao', 'rolagem', 'botao']);
})->with([
    'paginacao | nenhum (não é contínuo)' => ['paginacao', null],
    'rolagem | Scroll'                    => ['rolagem', InfiniteScrollMode::Scroll],
    'botao | Button'                      => ['botao', InfiniteScrollMode::Button],
])->group('kit');
