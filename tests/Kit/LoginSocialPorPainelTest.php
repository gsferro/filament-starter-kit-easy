<?php

use App\Support\ConfiguracaoDoLogin;
use App\Support\ProvedorSocial;
use Illuminate\Support\Facades\Log;

/**
 * Em quais painéis cada provedor de login social vale.
 *
 * A condição é a TERCEIRA de `ConfiguracaoDoLogin::disponivel()`, e ela é conjuntiva com as duas
 * que já existiam: interruptor ligado, credenciais preenchidas, **e** painel autorizado. Os três
 * falham por motivos diferentes — escolha de quem instalou, descuido de quem configurou, escolha
 * de quem configurou.
 *
 * **Vazio significa TODOS**, não nenhum. É o que faz a feature nascer inerte: instalação que já
 * usa login social não perde nada num update, e a restrição só passa a valer quando alguém a
 * preenche. Ver ADR-04 de `wikis/specs/feat/login-social-por-painel/login-social-por-painel/`.
 *
 * O caso de uso do requisito é o `admin`: *"liberar o login do empresarial do google para acessar
 * o admin, mas para a empresa … ou usuário final, não"*.
 */

/*
|--------------------------------------------------------------------------
| A terceira condição, e a tradução de "vazio = todos"
|--------------------------------------------------------------------------
*/

/**
 * A matriz painel × lista configurada.
 *
 * A lista `['admin']` com a pergunta `app` é a célula que o requisito nomeia. A lista vazia é a
 * que garante a compatibilidade — e as duas linhas dela, com painéis diferentes, matam a
 * implementação que tratasse `[]` como "nenhum".
 */
it('decide o provedor pelo painel, e lista vazia vale para todos', function (array $paineis, string $painel, bool $esperado): void {
    ligarProvedor(ProvedorSocial::Google);
    config()->set('kit.login.google.paineis', $paineis);

    expect(ConfiguracaoDoLogin::disponivel(ProvedorSocial::Google, $painel))->toBe($esperado);
})->with([
    'vazio vale no admin'        => [[], 'admin', true],
    'vazio vale no app'          => [[], 'app', true],
    'vazio vale no infra'        => [[], 'infra', true],
    'só admin vale no admin'     => [['admin'], 'admin', true],
    'só admin NÃO vale no app'   => [['admin'], 'app', false],
    'só admin NÃO vale no infra' => [['admin'], 'infra', false],
    'dois painéis, um de fora'   => [['admin', 'infra'], 'app', false],
    'dois painéis, um de dentro' => [['admin', 'infra'], 'infra', true],
])->group('kit');

/**
 * Sem painel informado, a pergunta é "está configurado?" — não "vale onde?".
 *
 * É o que preserva TODO chamador anterior a esta feature, e é o que a tela de Settings quer: ela
 * pergunta se o provedor tem credencial, não se ele vale num painel.
 */
it('ignora a lista de painéis quando o painel não é informado', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    config()->set('kit.login.google.paineis', ['admin']);

    expect(ConfiguracaoDoLogin::disponivel(ProvedorSocial::Google))->toBeTrue()
        ->and(ConfiguracaoDoLogin::disponiveis())->toContain(ProvedorSocial::Google);
})->group('kit');

/**
 * A condição é CONJUNTIVA: painel autorizado não ressuscita provedor desligado.
 *
 * O mutante que este caso mata é o `||` no lugar do `&&` — ou uma implementação que checasse o
 * painel ANTES do interruptor e devolvesse `true` cedo. Sem ele, restringir por painel viraria uma
 * forma de LIGAR um provedor.
 */
it('não liga provedor desligado só porque o painel está na lista', function (): void {
    config()->set('kit.login.google.habilitado', false);
    config()->set('kit.login.google.paineis', ['admin']);

    expect(ConfiguracaoDoLogin::disponivel(ProvedorSocial::Google, 'admin'))->toBeFalse();
})->group('kit');

/**
 * `in_array` ESTRITO — a lista vem de config e de settings.
 *
 * Com comparação frouxa, `0 == 'admin'` é verdadeiro em PHP anterior ao 8, e uma lista que
 * chegasse com um inteiro (JSON malformado no settings, `.env` com `0`) autorizaria qualquer
 * painel. O caso fixa a comparação estrita.
 */
it('não autoriza painel por comparação frouxa', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    config()->set('kit.login.google.paineis', [0]);

    expect(ConfiguracaoDoLogin::painelAutorizado(ProvedorSocial::Google, 'admin'))->toBeFalse();
})->group('kit');

/**
 * A lista é POR PROVEDOR: restringir o Google não restringe o GitHub.
 *
 * É RQ-02 — o caso de uso inteiro depende disto. Um mutante que lesse a lista de uma chave global
 * passaria em todos os casos acima e reprovaria só aqui.
 */
it('mantém a lista de painéis independente entre provedores', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    ligarProvedor(ProvedorSocial::Github);

    config()->set('kit.login.google.paineis', ['admin']);
    config()->set('kit.login.github.paineis', []);

    expect(ConfiguracaoDoLogin::disponiveis('app'))
        ->toBe([ProvedorSocial::Github])
        ->and(ConfiguracaoDoLogin::disponiveis('admin'))
        ->toBe([ProvedorSocial::Google, ProvedorSocial::Github]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| A barreira na rota, e o log da recusa
|--------------------------------------------------------------------------
*/

/**
 * A rota recusa o painel não autorizado — RQ-05.
 *
 * "Usar" é o fluxo, não o botão: esconder o botão e deixar a rota aberta entregaria a restrição
 * só na tela, e quem digitasse a URL passaria. Este é o par obrigatório do filtro do blade.
 *
 * O caso afirma as DUAS direções, e a primeira é a que impede o falso ✅: sem ela, um `abort(404)`
 * incondicional passaria.
 */
it('recusa a rota do provedor no painel não autorizado, e aceita no autorizado', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    config()->set('kit.login.google.paineis', ['admin']);

    $this->get(route('auth.social.redirect', ['provedor' => 'google', 'painel' => 'admin']))
        ->assertRedirect();

    $this->get(route('auth.social.redirect', ['provedor' => 'google', 'painel' => 'app']))
        ->assertNotFound();
})->group('kit');

/**
 * A recusa é AUDITADA, com o motivo.
 *
 * O 404 é o mesmo do provedor desligado; o log é o que distingue "não existe" de "alguém chegou
 * aqui e não devia". Sem o `motivo`, a linha não serve para investigar.
 */
it('registra a recusa por painel no canal de autenticação', function (): void {
    $canal = espiarAutenticacao();

    ligarProvedor(ProvedorSocial::Google);
    config()->set('kit.login.google.paineis', ['admin']);

    $this->get(route('auth.social.redirect', ['provedor' => 'google', 'painel' => 'app']))
        ->assertNotFound();

    $canal->shouldHaveReceived('warning')->withArgs(function (string $mensagem, array $contexto): bool {
        return str_contains($mensagem, '[LoginSocialController@redirecionar]')
            && ($contexto['motivo'] ?? null) === 'painel_nao_autorizado'
            && ($contexto['painel'] ?? null) === 'app'
            && ($contexto['provedor'] ?? null) === 'google';
    })->once();
})->group('kit');

/**
 * Painel FORJADO na query não vira barreira nem destino.
 *
 * `painelDaRequisicao()` só aceita id que existe em `Paineis::opcoes()`, que sai de
 * `Filament::getPanels()`. Um valor inventado cai no comportamento anterior à feature — segue no
 * painel default — em vez de estourar `getPanel()` com id inexistente, que seria 500 no fluxo de
 * autenticação.
 */
it('ignora painel inexistente na query em vez de estourar', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    config()->set('kit.login.google.paineis', ['admin']);

    $this->get(route('auth.social.redirect', ['provedor' => 'google', 'painel' => 'painel-que-nao-existe']))
        ->assertRedirect();
})->group('kit');

/**
 * O painel viaja na sessão, junto do `org`/`token`.
 *
 * É o mecanismo que decide o DESTINO na volta (a "leitura ampla" de A1). Sem ele, quem entra pelo
 * botão do `/admin` termina no `/app` — que é exatamente o que o requisito usa para justificar a
 * feature.
 */
it('carrega o painel na sessão para a volta do provedor', function (): void {
    ligarProvedor(ProvedorSocial::Google);

    $this->get(route('auth.social.redirect', ['provedor' => 'google', 'painel' => 'admin']))
        ->assertRedirect();

    expect(session('login_social.contexto.painel'))->toBe('admin');
})->group('kit');

/**
 * Sem painel na query, a sessão NÃO ganha a chave.
 *
 * `array_filter` descarta o nulo, e é isso que mantém o contexto igual ao de antes desta feature
 * — o destino cai no painel default, como sempre foi.
 */
it('não põe painel na sessão quando a query não informa', function (): void {
    ligarProvedor(ProvedorSocial::Google);

    $this->get(route('auth.social.redirect', ['provedor' => 'google']))
        ->assertRedirect();

    expect(session('login_social.contexto'))->not->toHaveKey('painel');
})->group('kit');
