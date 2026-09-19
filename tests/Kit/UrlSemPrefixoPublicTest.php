<?php

declare(strict_types=1);

use App\Http\Middleware\RaizDeUrlSemPublic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * `/public` nunca aparece na URL antes do painel — quando for seguro remover.
 *
 * Wiki `fix/url-sem-prefixo-public`. O defeito **não é do kit nem da aplicação**: é da hospedagem
 * cujo `DocumentRoot` aponta para a raiz do projeto em vez de `public/`. O Symfony então deriva a
 * base do request como `/public` sempre que o endereço pedido também traz o prefixo, e **todas**
 * as URLs daquela página nascem prefixadas.
 *
 * ## A distinção que este arquivo existe para proteger
 *
 * Dois arranjos entregam ao PHP **a mesma assinatura**. Com `.htaccess` reescrevendo na raiz,
 * `/app` funciona e encurtar é a correção. **Sem** reescrita, só `/public/app` existe — e
 * encurtar transformaria todo link e todo asset em 404, quebrando uma instalação que funcionava.
 *
 * A primeira versão desta correção encurtava nos dois. CT-09 é o cenário que a derrubou.
 *
 * ## Duas armadilhas de arnês, as duas medidas
 *
 * **1. O `UrlGenerator` guarda a própria referência de request.** Trocar
 * `app()->instance('request', …)` não o alcança. Sem `URL::setRequest()`, "antes" e "depois" saem
 * idênticos e o cenário passa **sem exercitar nada**.
 *
 * **2. O Symfony percorre `SCRIPT_FILENAME`, `PHP_SELF` e `ORIG_SCRIPT_NAME`** para derivar a
 * base, não só `SCRIPT_NAME`. Omitir qualquer uma devolve base **vazia** em todos os casos, e o
 * mundo quebrado nunca é reproduzido.
 *
 * As duas produzem o mesmo sintoma — teste verde que não testa — por causas diferentes, e as duas
 * custaram um harness errado durante a investigação.
 */

/**
 * Monta o request que o servidor entregaria e roda o middleware sobre ele.
 *
 * `$base` é o que o servidor faz o Symfony derivar. `$host` existe para CT-08, que separa "usa o
 * host do request" de "usa o `APP_URL`".
 */
function comABaseDeUrl(string $base, string $host = 'https://kit.test'): void
{
    $esquema        = str_starts_with($host, 'https://') ? 'on' : 'off';
    $hostSemEsquema = (string) preg_replace('~^https?://~', '', $host);

    $request = Request::create($base.'/qualquer', 'GET', [], [], [], [
        'SCRIPT_NAME'     => $base.'/index.php',
        'SCRIPT_FILENAME' => base_path(ltrim($base, '/').'/index.php'),
        'PHP_SELF'        => $base.'/index.php',
        'REQUEST_URI'     => $base.'/qualquer',
        'HTTP_HOST'       => $hostSemEsquema,
        'HTTPS'           => $esquema,
    ]);

    app()->instance('request', $request);
    URL::setRequest($request);
    URL::forceRootUrl(null);

    (new RaizDeUrlSemPublic)->handle($request, fn (): Response => new Response);
}

/** O sinal do arranjo A: `.htaccess` na raiz reescrevendo para dentro de `public/`. */
function comReescritaNaRaiz(): void
{
    file_put_contents(base_path('.htaccess'), implode("\n", [
        '<IfModule mod_rewrite.c>',
        'RewriteEngine On',
        'RewriteCond %{REQUEST_URI} !^/public/',
        'RewriteRule ^(.*)$ public/$1 [L,NC]',
        '</IfModule>',
    ]));
}

beforeEach(function (): void {
    /*
     * Estado de partida declarado: sem reescrita e sem declaração explícita. É o mundo do
     * arranjo B, e é o default de quem instala o kit — ele não distribui `.htaccess` na raiz.
     */
    @unlink(base_path('.htaccess'));
    config()->set('kit.url.remover_sufixo_public', null);
    RaizDeUrlSemPublic::esquecerODetectado();
});

afterEach(function (): void {
    @unlink(base_path('.htaccess'));
    URL::forceRootUrl(null);
    RaizDeUrlSemPublic::esquecerODetectado();
});

it('[CT-01] o request que chega com /public gera endereco limpo', function (): void {
    comReescritaNaRaiz();
    comABaseDeUrl('/public');

    expect(url('/app/acme'))->toBe('https://kit.test/app/acme')
        ->and(url('/app/acme'))->not->toContain('/public');
});

/**
 * CT-02 — instalação correta não é tocada.
 *
 * O caso comum, e o que não pode regredir. Base vazia é o "nulo" desta feature, e o desejado é
 * **não agir** — o oposto do que a maioria das guardas de escopo faz.
 */
it('[CT-02] instalacao correta nao e tocada', function (): void {
    comReescritaNaRaiz();
    comABaseDeUrl('');

    expect(url('/app/acme'))->toBe('https://kit.test/app/acme');
});

/**
 * CT-03 — a base mais funda perde apenas o último segmento.
 *
 * O valor limite que separa "remover o sufixo" de "zerar a raiz". Uma implementação que
 * devolvesse raiz vazia passaria em CT-01 e falharia aqui.
 */
it('[CT-03] a base mais funda perde apenas o ultimo segmento', function (): void {
    comReescritaNaRaiz();
    comABaseDeUrl('/sistema/public');

    expect(url('/admin'))->toBe('https://kit.test/sistema/admin');
});

/**
 * CT-04 — o endereço de asset também sai limpo.
 *
 * RQ-01 fala do endereço, não da rota. Rota e asset saem do mesmo gerador; se a correção tocasse
 * só o roteador, o CSS continuaria prefixado e a tela seguiria errada na aba de rede.
 */
it('[CT-04] o endereco de asset tambem sai limpo', function (): void {
    comReescritaNaRaiz();
    comABaseDeUrl('/public');

    expect(asset('/css/kit/kit-correcoes.css'))->toBe('https://kit.test/css/kit/kit-correcoes.css');
});

/**
 * CT-05 — base que apenas se parece com `/public` é preservada.
 *
 * As bordas do sufixo. `/meupublic` termina igual mas é outro segmento; `/publicacoes` começa
 * igual e não é sufixo. A linha `/PUBLIC` é `@premissa`: a comparação é sensível a caixa, porque
 * o diretório do Laravel é literalmente `public`. Se o solicitante decidir o contrário, é esta
 * linha que inverte — ver `## Ambiguidades` do `00`.
 */
it('[CT-05] base que apenas se parece com /public e preservada', function (string $base): void {
    comReescritaNaRaiz();
    comABaseDeUrl($base);

    expect(url('/app'))->toBe("https://kit.test{$base}/app");
})->with(['/sistema', '/meupublic', '/publicacoes', '/PUBLIC']);

/**
 * CT-06 — qualquer painel sai sem `/public`, inclusive um que não existe no kit.
 *
 * É a cláusula RQ-03 — *"ou outro que possa vir a ser criada pelo usuario do kit"* — e o único
 * cenário que distingue "corrigiu a raiz" de "corrigiu uma lista de painéis". `/financeiro/lotes`
 * não existe em lugar nenhum do kit, de propósito.
 */
it('[CT-06] qualquer painel sai sem /public', function (string $caminho): void {
    comReescritaNaRaiz();
    comABaseDeUrl('/public');

    expect(url($caminho))->toBe("https://kit.test{$caminho}");
})->with(['/app/acme', '/admin', '/infra', '/financeiro/lotes']);

/**
 * CT-08 — o esquema e o host vêm do request, não de `APP_URL`.
 *
 * Protege a decisão da ADR-02, e é o único cenário que separa esta implementação da alternativa
 * mais curta — `forceRootUrl(config('app.url'))` sempre —, que quebraria instalação
 * multi-domínio jogando o usuário para o domínio do `APP_URL`.
 */
it('[CT-08] o esquema e o host vem do request, nao de APP_URL', function (): void {
    config()->set('app.url', 'https://kit.test');

    comReescritaNaRaiz();
    comABaseDeUrl('/public', host: 'https://outro.test');

    expect(url('/app'))->toBe('https://outro.test/app');
});

/**
 * CT-09 — SEM sinal de reescrita, a raiz é preservada. O cenário que derrubou o desenho anterior.
 *
 * `DocumentRoot` na raiz **sem** `.htaccess`, usuário acessando `https://host/public/app`. Aqui
 * `/app` **não existe**: encurtar transformaria todo link e todo asset em 404, quebrando uma
 * instalação que funcionava.
 *
 * O kit não distribui `.htaccess` na raiz, então este é o mundo de quem nunca acrescentou a
 * gambiarra — não é hipótese. A primeira versão da correção encurtava aqui, e foi o
 * `/code-review` do diff que pegou, antes de ir para a `main`.
 *
 * É também a regra de **falha segura** desta feature: sem evidência, não age.
 */
it('[CT-09] sem sinal de reescrita a raiz e preservada', function (): void {
    expect(is_file(base_path('.htaccess')))->toBeFalse('o mundo deste caso é o SEM reescrita');

    comABaseDeUrl('/public');

    expect(url('/app/acme'))->toBe('https://kit.test/public/app/acme');
});

/**
 * CT-10 — o sinal é a `RewriteRule` para `public/`, não a mera existência do arquivo.
 *
 * Um `.htaccess` na raiz pode existir por outro motivo — cabeçalho, bloqueio de IP, compressão —
 * sem rotear nada para dentro de `public/`. Nesse caso o arranjo continua sendo o B, e encurtar
 * continua quebrando.
 */
it('[CT-10] htaccess sem reescrita para public nao conta como sinal', function (): void {
    file_put_contents(base_path('.htaccess'), "Header set X-Frame-Options SAMEORIGIN\n");

    comABaseDeUrl('/public');

    expect(url('/app'))->toBe('https://kit.test/public/app');
});

/**
 * CT-11 — a declaração explícita vence a detecção, nos dois sentidos.
 *
 * `true` é a saída para nginx, onde não existe `.htaccess` para inspecionar mas a reescrita está
 * no vhost. `false` desliga de vez, para quem não quiser a correção.
 */
it('[CT-11] a declaracao explicita vence a deteccao', function (bool $declarado, string $esperado): void {
    config()->set('kit.url.remover_sufixo_public', $declarado);

    comABaseDeUrl('/public');

    expect(url('/app'))->toBe($esperado);
})->with([
    'true sem htaccess (nginx)' => [true, 'https://kit.test/app'],
    'false desliga de vez'      => [false, 'https://kit.test/public/app'],
]);

/**
 * CT-12 — `false` explícito vence até quando o sinal existe.
 *
 * O par do anterior: sem este caso, uma implementação que lesse a config apenas quando a detecção
 * falha passaria em CT-11 e ignoraria o desligamento de quem tem `.htaccess` na raiz.
 */
it('[CT-12] false explicito vence o sinal presente', function (): void {
    comReescritaNaRaiz();
    config()->set('kit.url.remover_sufixo_public', false);

    comABaseDeUrl('/public');

    expect(url('/app'))->toBe('https://kit.test/public/app');
});
