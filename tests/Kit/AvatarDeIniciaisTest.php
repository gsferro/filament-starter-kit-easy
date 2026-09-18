<?php

use App\Models\User;
use App\Support\AvatarDeIniciais;
use Filament\Facades\Filament;

/**
 * O avatar de quem não enviou foto.
 *
 * Este arquivo guarda uma correção de PRIVACIDADE, não uma preferência visual. Até a v0.35.0 os
 * três painéis não sobrescreviam `defaultAvatarProvider`, então valia o `UiAvatarsProvider` do
 * Filament (`vendor/filament/filament/src/Panel/Concerns/HasAvatars.php:10`), que devolve
 * `https://ui-avatars.com/api/?name={iniciais}&…`
 * (`vendor/filament/filament/src/AvatarProviders/UiAvatarsProvider.php:23`). Como
 * `User::getFilamentAvatarUrl()` devolve `null` sem foto, o navegador de cada pessoa requisitava
 * um domínio de terceiro em toda tela, levando as iniciais e o `Referer` do painel junto.
 *
 * O caso que vale mais aqui é o do domínio: ele fica vermelho se alguém remover a linha dos
 * providers, e não depende de nenhum detalhe do desenho do SVG.
 *
 * Ver ADR-03 de `wikis/specs/feat/estudo-de-pacotes-rodada-2/`.
 */
function svgDoAvatar(string $nome): string
{
    $uri = (new AvatarDeIniciais)->get(new User(['name' => $nome]));

    expect($uri)->toStartWith('data:image/svg+xml;base64,');

    return (string) base64_decode(str_replace('data:image/svg+xml;base64,', '', $uri), true);
}

/*
|--------------------------------------------------------------------------
| A fronteira: nada sai para fora
|--------------------------------------------------------------------------
*/

it('nao pede o avatar padrao a nenhum dominio externo', function (string $painel): void {
    $provider = Filament::getPanel($painel)->getDefaultAvatarProvider();

    expect($provider)->toBe(AvatarDeIniciais::class);

    $uri = app($provider)->get(new User(['name' => 'Ana Souza']));

    expect($uri)
        ->toStartWith('data:')
        ->not->toContain('ui-avatars.com')
        ->not->toContain('http://')
        ->not->toContain('https://gravatar')
        // `http` aparece no `xmlns` do SVG (`http://www.w3.org/2000/svg`), que é um
        // identificador de namespace e não uma URL buscada pelo navegador. A asserção
        // negativa acima já cobre `http://` no valor bruto — este é o data URI, não o SVG.
        ->and(base64_decode(str_replace('data:image/svg+xml;base64,', '', $uri), true))
        ->not->toContain('ui-avatars.com');
})->with(['app', 'admin', 'infra'])->group('kit');

/*
|--------------------------------------------------------------------------
| As iniciais
|--------------------------------------------------------------------------
*/

it('desenha as iniciais do nome', function (string $nome, string $esperado): void {
    $svg = svgDoAvatar($nome);

    expect($svg)->toContain(">{$esperado}</text>");
})->with([
    'dois termos'                       => ['Ana Souza', 'AS'],
    'um termo só'                       => ['Ana', 'A'],
    'três termos usa os dois primeiros' => ['Ana Beatriz Souza', 'AB'],
    'minúsculas viram maiúsculas'       => ['ana souza', 'AS'],
    'espaços extras são ignorados'      => ['  Ana   Souza  ', 'AS'],
    'acento preserva o caractere'       => ['Ângela Éder', 'ÂÉ'],
    'nome não latino'                   => ['Дмитрий Иванов', 'ДИ'],
])->group('kit');

/**
 * Nome vazio devolve SVG sem texto — não lança, não inventa "?".
 *
 * `users.name` é `NOT NULL`, mas o provider recebe qualquer `Model`, e o Filament o chama no
 * render de TODA tela: uma exceção aqui derrubaria o painel inteiro, não um avatar.
 */
it('nao quebra com nome vazio', function (string $nome): void {
    $svg = svgDoAvatar($nome);

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('></text>');
})->with(['vazio' => [''], 'só espaços' => ['   ']])->group('kit');

/*
|--------------------------------------------------------------------------
| O escape
|--------------------------------------------------------------------------
*/

/**
 * `users.name` é preenchido pela própria pessoa, no cadastro e no "Meu perfil".
 *
 * Sem escape, um nome com `<`, `>`, `&` ou aspas produziria um SVG malformado — e como o
 * resultado vira `data:` URI dentro de um `src`, o estrago seria uma imagem quebrada em toda
 * tela, para todo mundo que a visse.
 *
 * ## O oráculo é o PARSER, não a busca por substring
 *
 * A primeira redação deste caso procurava o caractere cru no texto e falhou por um motivo que
 * vale registrar: `&amp;` contém `&`, e o `</text>` do próprio SVG contém `<`. Procurar
 * substring mede a string, não a integridade do documento — e integridade é o que está em jogo.
 *
 * Então: o SVG é **parseado**. Documento malformado devolve `false` e o caso fica vermelho pelo
 * motivo certo. E o texto lido de volta pelo parser tem de ser a inicial CRUA — o que prova que
 * o caractere foi escapado, e não descartado (descartar também deixaria o documento válido, e é
 * errado por outro motivo).
 */
it('escapa caractere que quebraria o svg', function (string $nome, string $iniciais, string $escapado): void {
    $svg = svgDoAvatar($nome);

    expect($svg)->toContain($escapado);

    $documento = simplexml_load_string($svg);

    expect($documento)->not->toBeFalse('o SVG gerado não é XML válido')
        ->and((string) $documento->text)->toBe($iniciais);
})->with([
    'menor que'    => ['<script> Souza', '<S', '&lt;'],
    'e comercial'  => ['&mpresa Souza', '&S', '&amp;'],
    'aspas duplas' => ['"Zé" Souza', '"S', '&quot;'],
])->group('kit');

/**
 * Byte UTF-8 inválido degrada para U+FFFD, e as iniciais continuam lá.
 *
 * Achado pelo `/code-review` do diff: com flags explícitas, `htmlspecialchars()` DESLIGA a
 * substituição padrão do PHP e devolve string VAZIA diante de byte inválido. `users.name` chega
 * por caminhos que não passam pelo formulário — nome de provedor social e importação são dois —,
 * e o resultado seria o avatar daquela pessoa virando um quadrado escuro sem letra nenhuma, em
 * toda tela, sem erro nenhum.
 *
 * O caso assere a PRESENÇA da inicial válida, não a ausência do byte: é a primeira que some
 * quando a flag é removida.
 */
it('mantem as iniciais com byte utf-8 invalido no nome', function (): void {
    $svg = svgDoAvatar('Ana ±Souza');

    $documento = simplexml_load_string($svg);

    expect($documento)->not->toBeFalse('o SVG gerado não é XML válido')
        ->and((string) $documento->text)->not->toBe('')
        ->and((string) $documento->text)->toStartWith('A');
})->group('kit');

/*
|--------------------------------------------------------------------------
| O contraste
|--------------------------------------------------------------------------
*/

/**
 * Fundo escuro fixo, texto branco — o mesmo par que o provider do vendor produzia.
 *
 * NÃO é a cor primária de propósito: o avatar aparece dentro de `/app/{slug}`, onde a paleta é a
 * da organização, e um fundo que mudasse de cor por organização tornaria a legibilidade do texto
 * branco uma aposta. Este caso fica vermelho se alguém trocar por `primary`.
 */
it('desenha texto branco sobre fundo escuro fixo', function (): void {
    $svg = svgDoAvatar('Ana Souza');

    expect($svg)
        ->toContain('fill="#FFFFFF"')
        ->toMatch('/<rect[^>]+fill="#0[0-9a-f]{5}"/');
})->group('kit');
