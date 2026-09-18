<?php

use App\Models\User;
use App\Support\AvatarDeIniciais;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;

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
    return svgDoAvatarDe(new User(['name' => $nome]));
}

/** O mesmo, a partir de um registro já montado — é o que os casos de persona precisam. */
function svgDoAvatarDe(User $user): string
{
    $uri = (new AvatarDeIniciais)->get($user);

    expect($uri)->toStartWith('data:image/svg+xml;base64,');

    return (string) base64_decode(str_replace('data:image/svg+xml;base64,', '', $uri), true);
}

/**
 * Os `<img src>` de um documento HTML.
 *
 * @return list<string>
 */
function enderecosDeImagem(string $html): array
{
    preg_match_all('/<img[^>]+src="([^"]*)"/i', $html, $achados);

    return $achados[1];
}

/*
|--------------------------------------------------------------------------
| A fronteira: nada sai para fora
|--------------------------------------------------------------------------
*/

it('[CT-20] nao pede o avatar padrao a nenhum dominio externo', function (string $painel): void {
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

/**
 * CT-21 — a varredura da página inteira, e não de um domínio nomeado.
 *
 * O mutante que ela mata é M29/M58: o provider local passa a valer no menu do usuário e **algum
 * outro ponto da página** (widget, tabela, o avatar da organização) continua saindo para um
 * terceiro — ou o terceiro é trocado por outro terceiro. Uma lista negra de um item
 * (`ui-avatars.com`) não pega nenhum dos dois; a asserção genérica — todo `<img src>` ou é
 * embutido, ou é relativo, ou tem o host da própria aplicação — fecha a classe inteira.
 *
 * O `Dado` tem destinatário: a usuária existe, está autenticada e NÃO tem foto, que é exatamente
 * a condição em que o provider padrão é chamado. Sem isso a asserção passaria numa página que
 * não renderizasse avatar nenhum.
 */
it('[CT-21] nao aponta nenhuma imagem da pagina para fora da aplicacao', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $ana = usuarioDoKit('admin', 'ana@example.com');
    $ana->forceFill(['name' => 'Ana Souza'])->save();

    $html = $this->actingAs($ana)->get('/admin')->assertOk()->getContent();

    $enderecos = enderecosDeImagem((string) $html);

    expect($enderecos)->not->toBeEmpty('a página não renderizou nenhuma imagem: a asserção seria vácua')
        ->and((string) $html)->not->toContain('ui-avatars.com');

    $proprio = parse_url((string) config('app.url'), PHP_URL_HOST);

    foreach ($enderecos as $endereco) {
        $host = parse_url($endereco, PHP_URL_HOST);

        expect($host === null || $host === $proprio)->toBeTrue(
            "a imagem `{$endereco}` sai para o host `{$host}`, fora da aplicação",
        );
    }
})->group('kit');

/**
 * CT-22 — o par de falsificabilidade: quem tem foto continua com a foto.
 *
 * Um provider que ignorasse `getFilamentAvatarUrl()` e desenhasse iniciais para todo mundo
 * passaria em CT-20 e CT-21 — as duas afirmam privacidade, e iniciais para todo mundo é
 * privadíssimo. É este caso que separa "não sai para a rede" de "sequestrou o avatar de quem
 * enviou um" (M30).
 */
it('[CT-22] mantem a foto de quem enviou uma, sem trocar por iniciais', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $ana = usuarioDoKit('admin', 'ana@example.com');
    $ana->forceFill(['name' => 'Ana Souza', 'avatar_url' => 'perfis/ana.png'])->save();

    $daFoto = Storage::disk('public')->url('perfis/ana.png');

    $this->actingAs($ana)->get('/admin')
        ->assertOk()
        ->assertSee($daFoto, escape: false);

    expect(Filament::getUserAvatarUrl($ana->fresh()))
        ->toBe($daFoto)
        ->not->toStartWith('data:');
})->group('kit');

/**
 * CT-45 — o avatar é o da pessoa que o provider recebe, não o de quem está olhando.
 *
 * O buraco que este caso fecha (M62): CT-20…CT-25 medem o gerador isolado e a ausência de host
 * externo, e nenhum deles liga uma coisa à outra. Um provider que ignorasse o registro recebido e
 * usasse sempre `Auth::user()` — ou um avatar fixo — passaria em todos, com **toda listagem
 * mostrando as iniciais de quem está olhando**.
 *
 * ## Desvio declarado do cenário do `04`
 *
 * O `04` arranja o par numa LISTAGEM. Nenhuma listagem do kit renderiza avatar padrão: as duas
 * (`/admin` e `/app`) usam `ImageColumn::make('avatar_url')` **sem** `defaultImageUrl()`, de
 * propósito, para que a célula de quem não enviou foto fique vazia
 * (`app/Filament/Admin/Resources/Users/UserResource.php:179`). Não existe, hoje, superfície
 * renderizada em que "a pessoa da linha" seja diferente de quem está autenticado.
 *
 * O par discriminante é montado então onde o mutante vive: duas pessoas sem foto, iniciais
 * diferentes, uma terceira autenticada, e o provider chamado com cada registro. É a mesma
 * proposição do cenário, sem a listagem que o kit não tem.
 */
it('[CT-45] desenha as iniciais do registro recebido, e nao as de quem esta autenticado', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $ana   = usuario('ana@example.com');
    $bruno = usuario('bruno@example.com');

    $ana->forceFill(['name' => 'Ana Souza'])->save();
    $bruno->forceFill(['name' => 'Bruno Teixeira'])->save();

    // A terceira pessoa: se o provider usar quem está autenticado, as duas voltas trazem "CM".
    $carla = usuarioDoKit('admin', 'carla@example.com');
    $carla->forceFill(['name' => 'Carla Mendes'])->save();

    $this->actingAs($carla);

    expect(svgDoAvatarDe($ana->fresh()))->toContain('>AS</text>')
        ->and(svgDoAvatarDe($bruno->fresh()))->toContain('>BT</text>');
})->group('kit');

/*
|--------------------------------------------------------------------------
| As iniciais
|--------------------------------------------------------------------------
*/

it('[CT-23] desenha as iniciais do nome', function (string $nome, string $esperado): void {
    $svg = svgDoAvatar($nome);

    expect($svg)->toContain(">{$esperado}</text>");
})->with([
    'dois termos'                       => ['Ana Souza', 'AS'],
    'um termo só'                       => ['Ana', 'A'],
    'três termos usa os dois primeiros' => ['Ana Beatriz Souza', 'AB'],
    /*
     * A linha de QUATRO termos é a que mata M37 — "pega o primeiro e o ÚLTIMO". Com três termos
     * as duas leituras produzem iniciais diferentes por acaso do dataset; com quatro, "AM" contra
     * "AL" é a diferença que não depende de sorte.
     */
    'quatro termos usa os dois primeiros' => ['Ana Maria Souza Lima', 'AM'],
    'minúsculas viram maiúsculas'         => ['ana souza', 'AS'],
    'espaços extras são ignorados'        => ['  Ana   Souza  ', 'AS'],
    'acento preserva o caractere'         => ['Ângela Éder', 'ÂÉ'],
    'nome não latino'                     => ['Дмитрий Иванов', 'ДИ'],
    // Fora do alfabeto latino E sem caixa: `mb_strtoupper` é identidade aqui, e é `mb_substr`
    // quem fica vermelho se alguém voltar para `substr()` — meio caractere quebra o XML.
    'nome sem caixa (CJK)'                => ['李 明', '李明'],
])->group('kit');

/**
 * Nome vazio devolve SVG sem texto — não lança, não inventa "?".
 *
 * `users.name` é `NOT NULL`, mas o provider recebe qualquer `Model`, e o Filament o chama no
 * render de TODA tela: uma exceção aqui derrubaria o painel inteiro, não um avatar.
 */
it('[CT-23] nao quebra com nome vazio', function (string $nome): void {
    $svg = svgDoAvatar($nome);

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('></text>');
})->with(['vazio' => [''], 'só espaços' => ['   ']])->group('kit');

/*
 * CT-25 ("registro sem nome nenhum não derruba a página") **não tem caso**, e o motivo é de tipo,
 * não de preguiça: o provider não lê `name` do registro — ele recebe o nome de
 * `Filament::getNameForDefaultAvatar()` (`FilamentManager.php:323`), declarado `: string`, que
 * para usuário passa por `User::getFilamentName(): string`. Um nome nulo estoura `TypeError`
 * **antes** de chegar ao kit, e nenhuma implementação do provider muda isso. A partição
 * "sem nome" que É alcançável é a string vazia, e ela está em CT-23 acima.
 *
 * Fundido em CT-23, com o registro em `04-casos-de-teste.md` › `## Reconciliação`.
 */

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
it('[CT-24] escapa caractere que quebraria o svg', function (string $nome, string $iniciais, string $escapado): void {
    $svg = svgDoAvatar($nome);

    expect($svg)->toContain($escapado);

    $documento = simplexml_load_string($svg);

    expect($documento)->not->toBeFalse('o SVG gerado não é XML válido')
        ->and((string) $documento->text)->toBe($iniciais)
        /*
         * A terceira linha do `Então` de CT-24: "nenhum elemento além dos que o desenho usa". Um
         * nome com `<b>…</b>` que chegasse cru abriria elemento novo dentro do SVG, e o documento
         * continuaria bem formado — o parser sozinho não pega isso.
         */
        ->and(array_values(array_diff(array_keys((array) $documento), ['@attributes'])))->toBe(['rect', 'text']);
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
