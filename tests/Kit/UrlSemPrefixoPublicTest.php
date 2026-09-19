<?php

declare(strict_types=1);

use App\Http\Middleware\RaizDeUrlSemPublic;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\TrustProxies;
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
 * ## A armadilha de arnês, medida — e as duas lições falsas que estavam escritas aqui
 *
 * **`SCRIPT_NAME` sozinho não basta.** O Symfony compara o basename dele com o de
 * `SCRIPT_FILENAME` para derivar a base; sem o segundo, ela sai **vazia** em todos os casos e o
 * mundo quebrado nunca é reproduzido — o cenário fica verde sem exercitar nada:
 *
 *     SCRIPT_NAME + SCRIPT_FILENAME    base = /public   <- o que basta
 *     só SCRIPT_NAME                   base = (vazio)   <- o furo
 *
 * **Este bloco já afirmou outras duas coisas, e as duas eram falsas.** Que `PHP_SELF` e
 * `REQUEST_URI` eram load-bearing — não são, a base continua `/public` sem eles — e que
 * `app()->instance('request', …)` não alcançava o `UrlGenerator`, exigindo `URL::setRequest()`
 * — alcança, pelo `rebinding` que o `RoutingServiceProvider` registra.
 *
 * O harness que falhou durante a investigação não tinha `SCRIPT_FILENAME`, e eu atribuí o
 * sintoma à causa errada. Fica registrado porque comentário de teste que ensina o errado é pior
 * que comentário nenhum.
 */

/**
 * Monta o request que o servidor entregaria e roda o middleware sobre ele.
 *
 * `$base` é o que o servidor faz o Symfony derivar. `$host` existe para CT-08, que separa "usa o
 * host do request" de "usa o `APP_URL`".
 */
function comABaseDeUrl(string $base, string $host = 'kit.test'): void
{
    $request = Request::create($base.'/qualquer', 'GET', [], [], [], [
        'SCRIPT_NAME'     => $base.'/index.php',
        'SCRIPT_FILENAME' => base_path(ltrim($base, '/').'/index.php'),
        'HTTP_HOST'       => $host,
        'HTTPS'           => 'on',
    ]);

    app()->instance('request', $request);

    (new RaizDeUrlSemPublic)->handle($request, fn (): Response => new Response);
}

/** O sinal do arranjo A: `.htaccess` na raiz reescrevendo para dentro de `public/`. */
function comReescritaNaRaiz(): void
{
    // Só a linha que a detecção lê. O `.htaccess` de verdade tem mais, e nada disso muda a resposta.
    file_put_contents(base_path('.htaccess'), 'RewriteRule ^(.*)$ public/$1 [L,NC]');
}

/*
 * O `.htaccess` da raiz é fixture — e a raiz é o working tree de quem roda a suíte.
 *
 * QA-15 do ciclo 1: a versão anterior apagava o arquivo direto, e quem tivesse um `.htaccess`
 * real ali — não versionado, não ignorado — o perderia ao rodar os testes. Agora o conteúdo
 * original é guardado e devolvido.
 */
beforeEach(function (): void {
    $this->htaccessOriginal = is_file(base_path('.htaccess'))
        ? (string) file_get_contents(base_path('.htaccess'))
        : null;

    // Estado de partida: sem reescrita e sem declaração. É o mundo do arranjo B, e o default de
    // quem instala o kit — ele não distribui `.htaccess` na raiz.
    @unlink(base_path('.htaccess'));
    config()->set('kit.url.remover_sufixo_public', null);
});

afterEach(function (): void {
    if ($this->htaccessOriginal === null) {
        @unlink(base_path('.htaccess'));
    } else {
        file_put_contents(base_path('.htaccess'), $this->htaccessOriginal);
    }

    URL::forceRootUrl(null);
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
    comABaseDeUrl('/public', host: 'outro.test');

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
 * `true` é a saída para nginx, onde não há `.htaccess` para inspecionar mas a reescrita está no
 * vhost.
 *
 * **A linha `false` que existia aqui foi cortada**: sem `.htaccess` a detecção já devolve falso,
 * então ela passava mesmo se a config fosse ignorada por completo — tautologia, e CT-09 já cobre
 * aquele caminho. Quem mata o `false` de verdade é CT-12, onde o sinal existe.
 */
it('[CT-11] true declarado encurta mesmo sem sinal, para nginx', function (): void {
    config()->set('kit.url.remover_sufixo_public', true);

    comABaseDeUrl('/public');

    expect(url('/app'))->toBe('https://kit.test/app');
});

/**
 * CT-13 — o middleware está registrado no stack global, depois do `TrustProxies`.
 *
 * **QA-01 do ciclo 1, e o achado mais grave.** Os outros casos chamam `handle()` direto: apagar
 * o `append` de `bootstrap/app.php` deixa a feature **inerte** e os 2.479 testes do kit seguem
 * verdes. Nada no repositório afirmava o registro — a mesma classe de "lista paralela" que já
 * cobrou nesta família.
 *
 * A ordem também é afirmada, e não é detalhe: antes do `TrustProxies` o middleware leria host e
 * porta sem os cabeçalhos `X-Forwarded-*`, e congelaria uma raiz que o navegador não alcança.
 * É a ADR-05, agora com guarda.
 */
it('[CT-13] o middleware esta no stack global, depois do TrustProxies', function (): void {
    /*
     * O CONTRATO, e não `Foundation\Http\Kernel` direto.
     *
     * **Correção de uma afirmação errada que esteve aqui** (QA-21 do ciclo 2): este comentário
     * dizia, como se fosse medido, que resolver a classe concreta devolveria o stack de fábrica.
     * Não devolve — o `withMiddleware()` registra um `afterResolving` pelo **contrato**, e o
     * container dispara por tipo, então a instância nova também recebe o `append`.
     *
     * O contrato continua sendo a escolha certa, por outro motivo: é o que o framework resolve
     * em produção, e é o único que continua verdadeiro se o kit um dia trocar a classe do kernel.
     */
    $global = app(Kernel::class)->getGlobalMiddleware();

    /*
     * `in_array` e não `toContain($classe, $mensagem)`: o `toContain` do Pest recebe VÁRIOS
     * needles, não uma mensagem — passar a explicação como segundo argumento a transforma numa
     * segunda busca, e o caso fica vermelho pelo motivo errado.
     */
    expect(in_array(RaizDeUrlSemPublic::class, $global, true))->toBeTrue(
        'o middleware saiu do stack global — a correção fica inerte e nada mais acusa',
    );

    /*
     * As duas posições afirmadas como INTEIRO antes de comparar.
     *
     * QA-22 do ciclo 2: `array_search` devolve `false` quando não acha, e em PHP `9 > false` é
     * verdadeiro — a asserção de ordem passaria em silêncio num dia em que o `TrustProxies`
     * saísse do stack, que é justamente quando ela mais importa.
     */
    $posicaoDaRaiz  = array_search(RaizDeUrlSemPublic::class, $global, true);
    $posicaoDoProxy = array_search(TrustProxies::class, $global, true);

    expect($posicaoDoProxy)->toBeInt('o TrustProxies saiu do stack global — a ordem deixou de ser afirmável')
        ->and($posicaoDaRaiz)->toBeInt()
        ->and($posicaoDaRaiz)->toBeGreaterThan(
            $posicaoDoProxy,
            'precisa rodar DEPOIS do TrustProxies, senão lê host e porta sem os X-Forwarded-*',
        );
});

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
