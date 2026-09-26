<?php

use App\Http\Middleware\RaizDeUrlSemPublic;
use App\Providers\KitServiceProvider;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\TrustProxies;

/**
 * O `RaizDeUrlSemPublic` chega a quem ATUALIZA, e não só a quem instala do zero.
 *
 * ## O defeito, e ele estava em produção
 *
 * O middleware é registrado em `bootstrap/app.php`, e **`bootstrap/` não viaja pelo
 * `kit:update`** — nem pode: é onde quem instala registra os próprios middlewares e providers.
 *
 * A classe entrou na **v0.36.1**. Quem instalou entre a v0.16.0 e a v0.36.0 e vem rodando
 * `kit:update` **tem a classe** e **não tem o registro** — a correção de URL não funciona, e o
 * sintoma é silencioso: nada quebra, o `/public` só continua aparecendo antes do painel.
 *
 * O `KitServiceProvider` viaja pelas duas rotas, e por isso a rede de segurança mora nele.
 *
 * ## O que cada caso aqui prova, e por que três e não um
 *
 * O caso fácil — *"o middleware está no stack"* — passaria hoje **sem** a rede de segurança,
 * porque o `bootstrap/app.php` desta árvore o registra. Ele não falsifica nada sozinho. O que
 * prova a correção é o `[CT-02]`, que remonta o kernel **sem** o registro do `bootstrap` e exige
 * que o provider o complete.
 */
it('[CT-01] o middleware esta no stack global, uma vez so, depois do TrustProxies', function (): void {
    $kernel = app(HttpKernel::class);

    $stack = (new ReflectionClass($kernel))->getProperty('middleware');
    $stack->setAccessible(true);

    /** @var list<string> $registrados */
    $registrados = $stack->getValue($kernel);

    $posicoes     = array_keys($registrados, RaizDeUrlSemPublic::class, true);
    $trustProxies = array_search(TrustProxies::class, $registrados, true);

    expect($posicoes)->toHaveCount(1, 'o middleware aparece mais de uma vez no stack — `pushMiddleware()` deveria ser idempotente')
        ->and($trustProxies)->not->toBeFalse('o `TrustProxies` saiu do stack global, e a ordem exigida por este middleware deixa de ser verificável');

    /*
     * A ordem é REQUISITO, não estilo: rodando antes do `TrustProxies`, ele leria host e porta sem
     * os cabeçalhos `X-Forwarded-*` e congelaria uma raiz que o navegador não alcança. Está
     * escrito no docblock da classe, e é o motivo de a rede de segurança **completar** o registro
     * em vez de movê-lo.
     */
    expect($posicoes[0])->toBeGreaterThan(
        (int) $trustProxies,
        'o `RaizDeUrlSemPublic` passou a rodar ANTES do `TrustProxies` — ele leria host e porta sem os cabeçalhos de proxy',
    );
})->group('kit');

/**
 * Sem o registro do `bootstrap/app.php`, o provider completa — que é o caso de quem atualizou.
 *
 * Este é o caso que **falsifica** a correção. Ele monta um kernel do zero, garante que o
 * middleware não está lá (o mundo de quem instalou antes da v0.36.1), roda o `boot()` do provider
 * e exige que ele apareça.
 *
 * Sem a rede de segurança, este caso fica **vermelho** — e foi assim que ele foi verificado.
 */
it('[CT-02] sem o registro do bootstrap, o provider completa o stack', function (): void {
    $kernel = app(HttpKernel::class);

    $stack = (new ReflectionClass($kernel))->getProperty('middleware');
    $stack->setAccessible(true);

    /** @var list<string> $original */
    $original = $stack->getValue($kernel);

    /*
     * O mundo de quem instalou entre a v0.16.0 e a v0.36.0 e atualizou: a classe existe, o
     * registro do `bootstrap/app.php` não.
     */
    $semRegistro = array_values(array_filter(
        $original,
        static fn (string $middleware): bool => $middleware !== RaizDeUrlSemPublic::class,
    ));

    $stack->setValue($kernel, $semRegistro);

    expect($stack->getValue($kernel))->not->toContain(RaizDeUrlSemPublic::class);

    try {
        /*
         * So o metodo da rede, e nao o `boot()` inteiro: `boot()` registra health checks, gates e
         * plugins do Filament, e rodar de novo estoura `DuplicateCheckNamesFound` do
         * `spatie/laravel-health` — falha do ARNES, que nao diz nada sobre o middleware.
         *
         * Exercitar um metodo privado por reflexao e o preco de falsificar exatamente esta
         * correcao: o caso precisa provar que o PROVIDER completa o registro, e nao que alguem
         * chamou `pushMiddleware()` no teste.
         */
        $rede = (new ReflectionClass(KitServiceProvider::class))->getMethod('garantirRaizDeUrlSemPublic');
        $rede->setAccessible(true);
        $rede->invoke(new KitServiceProvider(app()));

        expect($stack->getValue($kernel))->toContain(RaizDeUrlSemPublic::class);

        $posicoes     = array_keys((array) $stack->getValue($kernel), RaizDeUrlSemPublic::class, true);
        $trustProxies = array_search(TrustProxies::class, (array) $stack->getValue($kernel), true);

        expect($posicoes)->toHaveCount(1)
            ->and($posicoes[0])->toBeGreaterThan((int) $trustProxies);
    } finally {
        /*
         * O stack é estado do container compartilhado entre casos: restaurar no `finally` impede
         * que uma falha aqui derrube os vizinhos — vazamento que só apareceria em `--parallel` ou
         * ao rodar o arquivo isolado, que é o pior lugar para descobrir.
         */
        $stack->setValue($kernel, $original);
    }
})->group('kit');

/**
 * O provider que carrega a rede de segurança viaja pelas duas rotas de entrega.
 *
 * É o que torna a correção efetiva: `bootstrap/` não viaja por nenhuma das duas, e foi essa a
 * causa raiz. Se um dia o `KitServiceProvider` sair de `CAMINHOS_DO_KIT`, a rede deixa de
 * alcançar quem atualiza e o defeito volta — em silêncio, como voltou da primeira vez.
 */
it('[CT-03] o KitServiceProvider e entregue pelas duas rotas', function (): void {
    $this->assertStringContainsString(
        "'app/Providers'",
        (string) file_get_contents(base_path('app/Console/Commands/KitUpdate.php')),
        'o `app/Providers` saiu de `CAMINHOS_DO_KIT` — a rede de segurança do middleware deixa de alcançar quem atualiza',
    );

    $this->assertStringNotContainsString(
        'app/Providers',
        (string) preg_replace('~^#.*$~m', '', (string) file_get_contents(base_path('.gitattributes'))),
        'o `app/Providers` passou a ser `export-ignore` — ele deixaria de existir em instalação nova',
    );
})->skip(fn (): bool => ! naArvoreDoKit(), 'O `.gitattributes` e o `KitUpdate` governam a entrega do kit, não a do projeto que nasce dele.')->group('kit');

/**
 * A rede de segurança está LIGADA no `boot()` — e este caso nasceu de um mutante sobrevivente.
 *
 * ## Por que ele existe
 *
 * O `[CT-02]` prova que `garantirRaizDeUrlSemPublic()` registra o middleware, e prova **só isso**:
 * ele invoca o método por reflexão. Rodado o mutante que apaga a chamada de dentro do `boot()`,
 * os três casos anteriores ficaram **verdes** — o método continuava correto e ninguém mais o
 * chamava.
 *
 * É a forma mais barata de a correção inteira sumir: um `boot()` reorganizado, uma linha perdida
 * num merge, e o defeito volta exatamente como estava — em silêncio, em produção, só para quem
 * atualiza.
 *
 * ## Por que a asserção é sobre o FONTE
 *
 * Provar dinamicamente exigiria uma segunda instância da aplicação com o kernel limpo, e o
 * `boot()` deste provider não é reentrante — registra health checks, gates e plugins do Filament,
 * e rodá-lo duas vezes estoura `DuplicateCheckNamesFound`. Foi tentado antes de escrever isto.
 *
 * A asserção estrutural é o que sobra, e ela mata o mutante que importa.
 */
it('[CT-04] o boot() chama a rede de seguranca', function (): void {
    $metodo = (new ReflectionMethod(KitServiceProvider::class, 'boot'));

    $fonte = implode('', array_slice(
        file((string) $metodo->getFileName()) ?: [],
        $metodo->getStartLine() - 1,
        $metodo->getEndLine() - $metodo->getStartLine() + 1,
    ));

    /*
     * Sem comentário: `boot()` poderia CITAR o método num docblock sem chamá-lo, e a asserção
     * passaria. É a mesma armadilha de `.ai/rules/testes.md` — citar não é executar.
     */
    $codigo = (string) preg_replace(['~/\*.*?\*/~s', '~//[^\n]*~'], '', $fonte);

    $this->assertStringContainsString(
        '$this->garantirRaizDeUrlSemPublic();',
        $codigo,
        'o `boot()` do `KitServiceProvider` deixou de chamar a rede de segurança — quem atualiza volta a ficar sem o registro do middleware, em silêncio',
    );
})->group('kit');
