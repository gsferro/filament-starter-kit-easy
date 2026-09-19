<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impede que `/public` apareça na URL antes do painel — quando for seguro remover.
 *
 * ## O que produz o prefixo
 *
 * Em hospedagem cujo `DocumentRoot` aponta para a **raiz do projeto** em vez de `public/`, o
 * Symfony deriva a base do endereço como `/public` sempre que o endereço pedido também traz o
 * prefixo. A partir daí **todas** as URLs daquela página nascem prefixadas — rota, asset, link de
 * painel, redirect de login.
 *
 * Isso explica a intermitência do sintoma: basta entrar **uma vez** por um endereço com `/public`
 * — um favorito antigo, um link compartilhado — para a navegação inteira sair prefixada.
 *
 * ## A parte que não se resolve adivinhando, e quase virou um defeito pior
 *
 * Dois arranjos entregam ao PHP **a mesma assinatura** — `SCRIPT_NAME=/public/index.php`,
 * `REQUEST_URI=/public/app`, base `/public`:
 *
 *   A) `DocumentRoot` na raiz **com** um `.htaccess` reescrevendo tudo para dentro de `public/`.
 *      Aqui `/app` funciona, e remover o prefixo é a correção.
 *   B) `DocumentRoot` na raiz **sem** reescrita nenhuma, com o usuário acessando por
 *      `https://host/public/app`. Aqui `/app` **não existe**, e remover o prefixo transformaria
 *      todo link e todo asset em 404.
 *
 * O kit não distribui `.htaccess` na raiz, então o arranjo B não é hipotético — é o que acontece
 * com quem nunca acrescentou a gambiarra. A primeira versão desta correção encurtava nos dois, e
 * teria quebrado uma instalação que funcionava.
 *
 * Por isso a regra é **evidência positiva, com falha segura**: só encurta quando há sinal de que
 * `/` realmente roteia para dentro de `public/`. Sem sinal, não age — e o que funcionava continua
 * funcionando, que é o desfecho certo quando não dá para saber.
 *
 * ## Por que middleware, e não `boot()` de provider
 *
 * O `boot()` roda **antes** do `TrustProxies`. Ler `getSchemeAndHttpHost()` ali congela host e
 * porta sem os cabeçalhos `X-Forwarded-*`: atrás de um proxy que entrega na 8080, a raiz forçada
 * viraria `https://host:8080` — porta que o navegador não alcança. Middleware global roda
 * **depois** do `TrustProxies`, e o problema não existe.
 *
 * ## O que esta correção NÃO cobre
 *
 * Ela conserta o que a aplicação **gera**. O que vem do próprio request continua com o prefixo:
 * a URL pretendida que o `redirect()->guest()` guarda (`fullUrl()`) e o `url()->previous()`, que
 * sai do `Referer`. Quem cair no login vindo de `/public/app` volta para `/public/app` depois de
 * autenticar. **A correção de raiz é apontar o `DocumentRoot` para `public/`** — está na
 * documentação do kit, e esta classe existe porque o kit não controla a hospedagem de quem o
 * instala.
 *
 * Guarda: `tests/Kit/UrlSemPrefixoPublicTest.php`.
 */
final class RaizDeUrlSemPublic
{
    /** O segmento que o `DocumentRoot` mal configurado deixa na base da URL. */
    private const SUFIXO = '/public';

    /**
     * Memo do sinal, por processo.
     *
     * A detecção lê um arquivo, e a leitura só acontece quando a base já veio com o sufixo — ou
     * seja, nunca em instalação correta. Ainda assim o `.htaccess` não muda em runtime, e uma
     * leitura por request seria I/O à toa em toda página de uma instalação mal configurada.
     */
    private static ?bool $deveRemover = null;

    public function handle(Request $request, Closure $next): Response
    {
        $base = $request->getBaseUrl();

        // Sufixo, e não `str_contains`: `/publicacoes` e `/meupublic` são caminhos legítimos.
        if (str_ends_with($base, self::SUFIXO) && $this->deveRemover()) {
            URL::forceRootUrl(
                $request->getSchemeAndHttpHost().substr($base, 0, -strlen(self::SUFIXO)),
            );
        }

        return $next($request);
    }

    /** Esquece o sinal memoizado — para teste, e para quem trocar o `.htaccess` em runtime. */
    public static function esquecerODetectado(): void
    {
        self::$deveRemover = null;
    }

    private function deveRemover(): bool
    {
        $declarado = config('kit.url.remover_sufixo_public');

        // Declaração explícita vence a detecção, nos dois sentidos. É a saída para nginx, onde
        // não existe `.htaccess` para inspecionar, e para quem quiser desligar de vez.
        if ($declarado !== null) {
            return (bool) $declarado;
        }

        return self::$deveRemover ??= $this->raizReescreveParaPublic();
    }

    /**
     * Há sinal de que `/` roteia para dentro de `public/`?
     *
     * O sinal é o `.htaccess` na raiz do projeto com uma `RewriteRule` que aponta para `public/`.
     * É exatamente o arquivo que o arranjo A tem e o arranjo B não tem — e é o único sinal que o
     * PHP consegue observar sem sair pela rede.
     *
     * Em nginx não há `.htaccess`, e a resposta é `false`: nenhuma ação, comportamento de hoje
     * preservado. Quem servir nginx com reescrita equivalente declara por config.
     */
    private function raizReescreveParaPublic(): bool
    {
        $htaccess = base_path('.htaccess');

        if (! is_file($htaccess)) {
            return false;
        }

        return (bool) preg_match(
            '~^\s*RewriteRule\s+\S+\s+/?public/~mi',
            (string) file_get_contents($htaccess),
        );
    }
}
