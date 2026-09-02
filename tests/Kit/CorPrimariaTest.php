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

/**
 * CT-05 — a cor livre em hexadecimal vence a seleção pelo enum.
 *
 * Tabela de decisão completa: duas condições, seis linhas sobreviventes. As duas
 * que discriminam são a 1 (hex e nome preenchidos — separa "hex vence" de "nome
 * vence") e a 3 (hex inválido com nome válido — separa "cai para o nome" de "zera
 * tudo"). As outras quatro são âncoras.
 *
 * O hexadecimal é devolvido como STRING, não como paleta: o `ColorManager` chama
 * `Color::generatePalette()` sozinho quando recebe string (`ColorManager.php:84-85`),
 * que é o mesmo caminho da cor de uma organização.
 */
it('resolve a paleta pela fonte de maior precedencia disponivel', function (?string $hex, ?string $nome, mixed $esperado): void {
    config(['kit.cor_primaria_hex' => $hex, 'kit.cor_primaria' => $nome]);

    expect(CorPrimaria::paleta())->toBe($esperado);
})->with([
    'hex e nome preenchidos'     => ['#7c3aed', 'Blue', ['primary' => '#7c3aed']],
    'só o hex'                   => ['#7c3aed', '', ['primary' => '#7c3aed']],
    'hex inválido, nome válido'  => ['azul', 'Blue', ['primary' => Color::Blue]],
    'só o nome'                  => ['', 'Blue', ['primary' => Color::Blue]],
    'os dois inválidos'          => ['azul', 'Roxo', []],
    'os dois vazios'             => ['', '', []],
])->group('kit');

/**
 * CT-06 — hexadecimal fora do formato é recusado, sem lançar.
 *
 * `#abcd` é a linha que existe por um motivo só: ela separa `{3,6}` (frouxo,
 * aceita 4 e 5 dígitos) de `{3}|{6}` (correto). Sem ela, as duas expressões
 * passam — e `Color::generatePalette()` receberia algo que não é cor.
 *
 * `#7C3AED` cobre o alfabeto maiúsculo, que é o que manual de marca costuma
 * entregar: uma classe de caracteres `[0-9a-f]` sem `A-F` recusaria a cor da
 * empresa sem dizer por quê.
 */
it('recusa hexadecimal fora do formato em vez de repassar lixo para a paleta', function (string $hex, mixed $esperado): void {
    config(['kit.cor_primaria_hex' => $hex, 'kit.cor_primaria' => '']);

    expect(CorPrimaria::paleta())->toBe($esperado);
})->with([
    'três dígitos'          => ['#abc', ['primary' => '#abc']],
    'seis dígitos'          => ['#aabbcc', ['primary' => '#aabbcc']],
    'maiúsculas'            => ['#7C3AED', ['primary' => '#7C3AED']],
    'dois dígitos'          => ['#ab', []],
    'quatro dígitos'        => ['#abcd', []],
    'sete dígitos'          => ['#aabbccd', []],
    'sem o #'               => ['aabbcc', []],
    'fora do alfabeto hexa' => ['#gggggg', []],
])->group('kit');

/**
 * CT-07 — o painel sobe com as duas cores inválidas, em vez de morrer em toda página.
 *
 * A tolerância é deliberada e está documentada no docblock de `CorPrimaria` e no
 * `config/kit.php`: `constant()` num nome inexistente lança `Error: Undefined
 * constant`, e `Color::generatePalette()` não valida nada antes de passar o valor
 * para `convertToOklch()`. Como isto roda no boot de TODO painel, qualquer um dos
 * dois derrubaria toda página do projeto — não uma tela.
 */
it('sobe o painel com cor invalida nas duas fontes', function (): void {
    config(['kit.cor_primaria' => 'Roxo', 'kit.cor_primaria_hex' => 'vermelho']);

    fronteiraDeRequest();
    noPainelBootado('admin');

    expect(CorPrimaria::paleta())->toBe([]);

    $this->get('/admin/login')->assertOk();
})->group('kit');

/**
 * CT-05 (wiki `paleta-do-filament-na-organizacao`) — a regra é UMA: o que `paleta()` resolve para
 * a config, `resolver()` resolve para dois argumentos. A organização chama `resolver()` com as
 * colunas dela, então esta identidade é o que garante que kit e organização nunca divergem. A
 * tabela é a de "resolve a paleta pela fonte de maior precedencia disponivel", reaproveitada.
 */
it('resolve para dois argumentos o que a regra do kit resolve para a config', function (?string $hex, ?string $nome, mixed $esperado): void {
    config(['kit.cor_primaria_hex' => $hex, 'kit.cor_primaria' => $nome]);

    expect(CorPrimaria::resolver($hex, $nome))->toBe(CorPrimaria::paleta())
        ->and(CorPrimaria::resolver($hex, $nome))->toBe($esperado);
})->with([
    'hex e nome preenchidos'    => ['#7c3aed', 'Blue', ['primary' => '#7c3aed']],
    'só o hex'                  => ['#7c3aed', '', ['primary' => '#7c3aed']],
    'hex inválido, nome válido' => ['azul', 'Blue', ['primary' => Color::Blue]],
    'só o nome'                 => ['', 'Blue', ['primary' => Color::Blue]],
    'os dois inválidos'         => ['azul', 'Roxo', []],
    'os dois vazios'            => ['', '', []],
    'os dois nulos'             => [null, null, []],
])->group('kit');
