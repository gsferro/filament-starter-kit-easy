<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as UsuarioDoGoogle;

/**
 * Login social com Google — as barreiras, a tela e o rastro.
 *
 * Os casos derivam do requisito (`wikis/specs/feat/login-social-google/login-social-google/`),
 * e o gate de mutantes de cada regra está no `04-casos-de-teste.md`. O que mais importa saber
 * para mexer aqui:
 *
 * **Nenhum caso sai para a rede.** `Socialite::fake('google', User::fake([...]))` é a API
 * oficial de teste do pacote, e o `User::fake()` faz `setRaw($atributos)`
 * (`vendor/laravel/socialite/src/Two/User.php:57`) — é por isso que passar `email_verified`
 * chega ao `getRaw()` e a barreira de e-mail verificado é testável sem Google nenhum.
 *
 * **CT-05 é a exceção, e é deliberada**: ele NÃO faz fake, porque o fake ignora o `state` de
 * CSRF (`vendor/laravel/socialite/src/Testing/FakeProvider.php:71-78`) e nenhum cenário
 * faked pode falsificar essa proteção. Ainda assim ele não toca a rede — o motivo está no
 * docblock do próprio caso.
 */
beforeEach(function (): void {
    /*
     * O interruptor nasce desligado e as credenciais vazias — é o estado de fábrica do kit, e
     * `KIT_SOCIALITE_GOOGLE` NÃO está entre as chaves que o `phpunit.xml` fixa com
     * `force="true"`. Fixar aqui seria medir o `phpunit.xml`; CT-01 tem uma linha que mede o
     * default de verdade e um comentário dizendo isso.
     */
    config()->set('kit.login.rodape', null);
});

/**
 * Liga o login com Google para o caso corrente.
 *
 * Fica neste arquivo, e não em `tests/Pest.php`, porque só este arquivo usa —
 * `.ai/rules/testes.md` manda mover só o helper usado por MAIS DE UM arquivo. O nome é longo
 * de propósito: função em PHP é global no processo, e um `ligarGoogle()` colidiria com
 * qualquer vizinho futuro.
 *
 * @param  array<string, mixed>  $credenciais  sobrescreve chaves de `services.google`
 */
function ligarLoginComGoogleDoKit(array $credenciais = []): void
{
    config()->set('kit.login.google.habilitado', true);

    config()->set('services.google', array_merge([
        'client_id'     => 'id-de-teste',
        'client_secret' => 'segredo-de-teste',
        'redirect'      => '/auth/google/callback',
    ], $credenciais));
}

/**
 * O usuário que o Google devolveria.
 *
 * @param  array<string, mixed>  $atributos
 */
function usuarioDoGoogleFalso(array $atributos = []): UsuarioDoGoogle
{
    return UsuarioDoGoogle::fake(array_merge([
        'id'             => 'google-sub-123',
        'name'           => 'Pessoa do Google',
        'email'          => 'ja.tem@example.com',
        'email_verified' => true,
    ], $atributos));
}

/*
|--------------------------------------------------------------------------
| R1 — o botão aparece se e somente se o interruptor está ligado E as três
|      credenciais estão preenchidas
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a tabela de decisão da exibição do botão.
 *
 * Quatro linhas, nenhuma colapsada: três recusam por motivos diferentes e só a quarta exibe.
 *
 * A primeira linha é a única desta feature que mede o DEFAULT de verdade — ela não chama
 * `ligarLoginComGoogleDoKit()` e depende de `KIT_SOCIALITE_GOOGLE` não estar fixada no
 * `phpunit.xml`. É ela que mata "o default é true".
 *
 * A terceira usa `client_secret` VAZIO, e não ausente: valor vazio é o caso real do kit
 * (`.ai/rules/config.md`) e `isset()` passaria por ele. É também `client_secret` e não
 * `client_id` de propósito — conferir duas chaves e esquecer a terceira é o mutante mais
 * provável, e a terceira é justamente a que ninguém lembra.
 */
it('exibe o botão do Google só com o interruptor ligado e as credenciais completas', function (
    string $arranjo,
    bool $deveAparecer,
): void {
    match ($arranjo) {
        'de fabrica'            => null,
        'interruptor desligado' => config()->set([
            'kit.login.google.habilitado' => false,
            'services.google'             => ['client_id' => 'x', 'client_secret' => 'y', 'redirect' => '/auth/google/callback'],
        ]),
        'secret vazio'          => ligarLoginComGoogleDoKit(['client_secret' => '']),
        'completo'              => ligarLoginComGoogleDoKit(),
    };

    $resposta = $this->get('/app/login')->assertOk();

    $deveAparecer
        ? $resposta->assertSee('Entrar com Google')
        : $resposta->assertDontSee('Entrar com Google');
})->with([
    'de fábrica (interruptor ausente, credenciais ausentes)' => ['de fabrica', false],
    'interruptor desligado com as três credenciais'          => ['interruptor desligado', false],
    'interruptor ligado com client_secret vazio'             => ['secret vazio', false],
    'interruptor ligado com as três credenciais'             => ['completo', true],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — indisponível, as duas rotas de OAuth respondem 404
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — as quatro células inválidas da matriz estado × operação.
 *
 * Esconder o botão não é barreira: a URL é fixa, pública e conhecida. As duas rotas × os dois
 * modos de indisponibilidade, porque o mutante clássico é pôr o guarda no `redirect` (onde
 * quem escreve está pensando no botão) e esquecer o `callback`.
 */
it('responde 404 nas rotas do Google quando o login social está indisponível', function (
    string $arranjo,
    string $rota,
): void {
    match ($arranjo) {
        'desligado'      => config()->set([
            'kit.login.google.habilitado' => false,
            'services.google'             => ['client_id' => 'x', 'client_secret' => 'y', 'redirect' => '/auth/google/callback'],
        ]),
        'sem client_id'  => ligarLoginComGoogleDoKit(['client_id' => '']),
    };

    $this->get($rota)->assertNotFound();

    $this->assertGuest();
})->with([
    'desligado, rota de ida'       => ['desligado', '/auth/google/redirect'],
    'desligado, rota de volta'     => ['desligado', '/auth/google/callback'],
    'sem client_id, rota de ida'   => ['sem client_id', '/auth/google/redirect'],
    'sem client_id, rota de volta' => ['sem client_id', '/auth/google/callback'],
])->group('kit');

/**
 * CT-03 — as duas células VÁLIDAS da mesma matriz.
 *
 * Sem elas, um guarda que derruba a rota SEMPRE (condição negada) ficaria verde em CT-02, e a
 * coluna do `callback` não teria nenhuma prova de que a rota está no ar.
 *
 * A linha do `callback` chega sem `code` e sem `state`, então o resultado esperado é a recusa
 * TRATADA — o que também prova que o `catch` existe em vez de deixar vazar um 500.
 */
it('mantém as rotas do Google no ar quando o login social está disponível', function (
    string $rota,
): void {
    ligarLoginComGoogleDoKit();
    Socialite::fake('google');

    $this->get($rota)
        ->assertStatus(302)
        ->assertRedirectContains($rota === '/auth/google/redirect' ? 'socialite.fake' : '/app/login');
})->with([
    'rota de ida'   => ['/auth/google/redirect'],
    'rota de volta' => ['/auth/google/callback'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — o botão fica DEPOIS do formulário e traz o ícone do Google
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — nos TRÊS painéis, exaustivo e não amostrado.
 *
 * A razão é a mesma que `tests/Kit/TelasDeAutenticacaoTest.php` dá: o defeito histórico do
 * kit nessa área é configurar um painel e esquecer os outros dois. Aqui a registração do
 * render hook é ÚNICA e sem escopo (ADR-05), então este caso é o que prova que a registração
 * única de fato alcança os três — e o que reprova alguém "consertando" para escopo de painel.
 *
 * `assertSeeInOrder` e não `assertSee`: o requisito diz ABAIXO do formulário, e `assertSee`
 * ficaria verde com o botão no topo da tela.
 *
 * As quatro cores da marca são o oráculo do ícone. `assertSee('svg')` ficaria verde com um
 * Heroicon genérico no lugar do logo, que é exatamente o mutante que Heroicons-não-tem-marca
 * torna tentador.
 */
it('põe o botão do Google abaixo do formulário, com o ícone da marca, nos três painéis', function (
    string $painel,
): void {
    ligarLoginComGoogleDoKit();

    $this->get("/{$painel}/login")
        ->assertOk()
        ->assertSeeInOrder(['form.password', 'Entrar com Google'], escape: false)
        ->assertSee('#EA4335', escape: false)
        ->assertSee('#4285F4', escape: false)
        ->assertSee('#FBBC05', escape: false)
        ->assertSee('#34A853', escape: false)
        ->assertSee('/auth/google/redirect', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — o `state` de CSRF permanece ligado
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — o callback chamado direto, sem `state` na sessão, não autentica ninguém.
 *
 * **Único caso de callback sem `Socialite::fake()`**, e não por descuido: o `FakeProvider`
 * devolve o usuário falso sem passar pela verificação de state
 * (`vendor/laravel/socialite/src/Testing/FakeProvider.php:71-78`), então um cenário faked é
 * incapaz de falsificar esta regra — ficaria verde com `->stateless()` no controller.
 *
 * Com o provedor REAL também não há rede, e a garantia é a ORDEM do vendor:
 * `AbstractProvider::user()` chama `hasInvalidState()` ANTES de `getAccessTokenResponse()`
 * (`vendor/laravel/socialite/src/Two/AbstractProvider.php:230-241`), e `hasInvalidState()` só
 * lê a sessão e o input (`:282-290`). A `InvalidStateException` é lançada sem um único byte de
 * rede.
 *
 * `Http::preventStrayRequests()` NÃO serve de rede de segurança aqui, e é bom deixar escrito:
 * o Socialite usa o Guzzle dele (`getHttpClient()`), não o cliente do Laravel, então a facade
 * `Http` não intercepta nada dele. Quem garante é a ordem citada acima, lida no vendor.
 *
 * As três asserções são a tríade do cenário de recusa: recusado, nada autenticado e nada
 * gravado — a última é o que separa "recusa" de "recusa DEPOIS de gravar".
 */
it('não autentica quando o retorno do Google chega sem o state da sessão', function (): void {
    ligarLoginComGoogleDoKit();
    usuario('ja.tem@example.com');

    $this->get('/auth/google/callback?code=codigo-inventado&state=state-inventado')
        ->assertRedirectContains('/app/login');

    $this->assertGuest();

    expect(User::query()->count())->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — autentica quem já tem conta, casando o e-mail de forma normalizada
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — o caminho feliz.
 *
 * "o total de contas continua sendo 1" é o oráculo que separa AUTENTICAR de CRIAR. Sem ele,
 * um `User::updateOrCreate()` — o exemplo da própria doc do Socialite — passaria aqui
 * criando uma conta a mais.
 */
it('autentica pelo Google quem já tem conta', function (): void {
    ligarLoginComGoogleDoKit();
    $user = usuario('ja.tem@example.com');

    Socialite::fake('google', usuarioDoGoogleFalso());

    $this->get('/auth/google/callback')->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    expect(User::query()->count())->toBe(1);
})->group('kit');

/**
 * CT-07 — o casamento por e-mail ignora caixa e espaços, nos DOIS lados.
 *
 * Os valores são discriminantes, não redondos: caixa alta distingue `where('email', $x)` cru
 * de comparação normalizada; espaços nas bordas distinguem `mb_strtolower()` sozinho de
 * `mb_strtolower(trim())`; e a última linha inverte os lados — a CONTA em caixa mista e o
 * provedor em minúsculas —, que é a única que mata "normaliza só o lado do provedor".
 */
it('casa a conta pelo e-mail ignorando caixa e espaços', function (
    string $emailDaConta,
    string $emailDoProvedor,
): void {
    ligarLoginComGoogleDoKit();
    $user = usuario($emailDaConta);

    Socialite::fake('google', usuarioDoGoogleFalso(['email' => $emailDoProvedor]));

    $this->get('/auth/google/callback')->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    expect(User::query()->count())->toBe(1);
})->with([
    'caixa alta no provedor'          => ['ja.tem@example.com', 'JA.TEM@EXAMPLE.COM'],
    'caixa mista no provedor'         => ['ja.tem@example.com', 'Ja.Tem@Example.com'],
    'espaços nas bordas'              => ['ja.tem@example.com', '  ja.tem@example.com '],
    'caixa na conta, não no provedor' => ['Ja.Tem@Example.com', 'ja.tem@example.com'],
])->group('kit');

/**
 * CT-23 — idempotência, ancorada no agregado persistido.
 *
 * A mesma volta do Google duas vezes não pode produzir uma segunda conta nem uma segunda
 * identidade. A âncora é o TOTAL DE CONTAS e a conta autenticada — não o retorno da chamada,
 * que passaria por construção.
 */
it('não cria segunda conta quando o mesmo e-mail volta do Google duas vezes', function (): void {
    ligarLoginComGoogleDoKit();
    $user = usuario('ja.tem@example.com');

    Socialite::fake('google', usuarioDoGoogleFalso());

    $this->get('/auth/google/callback')->assertStatus(302);
    $this->get('/auth/google/callback')->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    expect(User::query()->count())->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — sem conta e com o registro fechado, o callback recusa
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — a barreira do convite, e o caso mais caro desta feature.
 *
 * `@premissa`: a premissa é que o login social AUTENTICA e não CADASTRA enquanto o registro
 * aberto estiver desligado — registrada em `## Ambiguidades` do `00-requisito.md` com o par
 * "Assumido / Se negado". O kit é por convite obrigatório, e um callback que cria conta
 * contorna o convite: é furo de autorização, não conveniência.
 *
 * Os três `Então` são a tríade da recusa. O do meio — nenhuma conta criada — é o que reprova
 * `updateOrCreate`.
 */
it('recusa o Google de quem não tem conta enquanto o registro está fechado', function (): void {
    ligarLoginComGoogleDoKit();

    Socialite::fake('google', usuarioDoGoogleFalso(['email' => 'de.fora@example.com']));

    $this->get('/auth/google/callback')->assertRedirectContains('/app/login');

    $this->assertGuest();

    expect(User::query()->where('email', 'de.fora@example.com')->exists())->toBeFalse()
        ->and(User::query()->count())->toBe(0);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — quem se registra vai para o perfil; quem só autentica vai para o painel
|--------------------------------------------------------------------------
*/

/**
 * CT-09 — com o registro aberto ligado, a conta nova nasce e o destino é o perfil.
 *
 * `@premissa` em duas frentes: o interruptor de registro aberto é da branch
 * `feat/registro-e-aprovacao` e a chave que ele grava ainda não existe — aqui ela é forçada; e
 * a conta nova NÃO recebe papel, porque decidir qual papel o registro aberto concede é daquela
 * feature. Por isso o oráculo é o DESTINO do redirecionamento, e não "consegue abrir a tela":
 * conta sem papel recebe 403 no painel, e isso é o comportamento correto do kit.
 *
 * "o nome Pessoa do Google" é `Então` de valor concreto: sem ele, uma implementação que grava
 * o e-mail no campo do nome passa.
 */
it('cria a conta e manda para o perfil quando o registro aberto está ligado', function (): void {
    ligarLoginComGoogleDoKit();
    config()->set('kit.registro.aberto', true);

    Socialite::fake('google', usuarioDoGoogleFalso([
        'email' => 'novo@example.com',
        'name'  => 'Pessoa do Google',
    ]));

    $this->get('/auth/google/callback')->assertRedirectContains('meu-perfil');

    $novo = User::query()->where('email', 'novo@example.com')->first();

    expect($novo)->not->toBeNull()
        ->and($novo->name)->toBe('Pessoa do Google');

    $this->assertAuthenticatedAs($novo);
})->group('kit');

/**
 * CT-10 — o contrapeso obrigatório de CT-09.
 *
 * Sem ele, uma implementação que manda TODO MUNDO para o perfil fica verde em CT-09 — e o
 * requisito diz "se a pessoa se registrar", não "sempre".
 */
it('manda quem já tinha conta para o painel, não para o perfil', function (): void {
    ligarLoginComGoogleDoKit();
    usuario('ja.tem@example.com');

    Socialite::fake('google', usuarioDoGoogleFalso());

    $resposta = $this->get('/auth/google/callback');

    $resposta->assertStatus(302);

    expect($resposta->headers->get('Location'))->not->toContain('meu-perfil')
        ->and($resposta->headers->get('Location'))->toContain('/app');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — e-mail não verificado no provedor é recusado
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — a partição EXAUSTIVA do valor de verificação. Não se amostra.
 *
 * A linha `false` é a tomada de conta: casar por e-mail que o provedor não verificou permite
 * criar uma conta Google com o e-mail de outra pessoa e entrar na conta dela.
 *
 * As linhas textuais são as discriminantes: `(bool) "false"` é `true`, então uma implementação
 * com um cast de bool fica verde nas quatro primeiras e falha nelas. É o mesmo defeito de
 * fronteira do interruptor em `config/kit.php`, do outro lado.
 *
 * A linha do alias existe porque o `GoogleProvider` popula as DUAS chaves —`email_verified`
 * do userinfo v3 e `verified_email` por compatibilidade
 * (`vendor/laravel/socialite/src/Two/GoogleProvider.php:90-92`) — e ler só uma delas é
 * mutante plausível nas duas direções.
 */
it('decide pelo valor de verificação de e-mail que o Google devolve', function (
    array $bruto,
    bool $deveAutenticar,
): void {
    ligarLoginComGoogleDoKit();
    $user = usuario('vitima@example.com');

    Socialite::fake('google', UsuarioDoGoogle::fake(array_merge([
        'id'    => 'google-sub-123',
        'name'  => 'Vítima',
        'email' => 'vitima@example.com',
    ], $bruto)));

    $this->get('/auth/google/callback')->assertStatus(302);

    $deveAutenticar
        ? $this->assertAuthenticatedAs($user)
        : $this->assertGuest();
})->with([
    'email_verified verdadeiro'      => [['email_verified' => true], true],
    'email_verified falso'           => [['email_verified' => false], false],
    'campo ausente'                  => [[], false],
    'a string "false"'               => [['email_verified' => 'false'], false],
    'a string "0"'                   => [['email_verified' => '0'], false],
    'só o alias verified_email'      => [['verified_email' => true], true],
])->group('kit');

/**
 * CT-22 — provedor que não devolve e-mail é partição inválida PRÓPRIA.
 *
 * Isolada de CT-11 de propósito: combinar duas partições inválidas no mesmo caso faz a
 * primeira validação mascarar a segunda. E uma implementação que confira só a verificação
 * estouraria aqui com `null` chegando ao `where`.
 */
it('recusa quando o Google não devolve e-mail', function (): void {
    ligarLoginComGoogleDoKit();
    usuario('ja.tem@example.com');

    Socialite::fake('google', UsuarioDoGoogle::fake([
        'id'             => 'google-sub-123',
        'name'           => 'Sem E-mail',
        'email'          => null,
        'email_verified' => true,
    ]));

    $this->get('/auth/google/callback')->assertRedirectContains('/app/login');

    $this->assertGuest();

    expect(User::query()->count())->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — o `client_secret` não aparece em nenhuma saída
|--------------------------------------------------------------------------
*/

/**
 * CT-12 — o segredo não aparece no HTML de nenhuma das três telas de login.
 *
 * O valor é escolhido para ser discriminante: uma string que não aparece por acidente em
 * lugar nenhum. Usar `secret` ou `password` produziria falso vermelho pelo próprio formulário.
 */
it('não deixa o client_secret aparecer no HTML da tela de login', function (string $painel): void {
    ligarLoginComGoogleDoKit(['client_secret' => 'segredo-irreconhecivel-42']);

    $this->get("/{$painel}/login")
        ->assertOk()
        ->assertSee('Entrar com Google')
        ->assertDontSee('segredo-irreconhecivel-42', escape: false);
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * CT-13 — nem o segredo nem o e-mail em claro chegam ao log; o mascarado chega.
 *
 * A terceira asserção é o que separa "mascarou" de "não logou nada": sem ela, remover todos
 * os logs deixaria o caso verde. `espiarAutenticacao()` espia só o channel `autenticacao` e
 * deixa os outros reais (`tests/Pest.php`).
 */
it('não deixa o segredo nem o e-mail em claro chegarem ao log do callback', function (): void {
    ligarLoginComGoogleDoKit(['client_secret' => 'segredo-irreconhecivel-42']);
    usuario('ja.tem@example.com');

    $canal = espiarAutenticacao();

    Socialite::fake('google', usuarioDoGoogleFalso());

    $this->get('/auth/google/callback')->assertStatus(302);

    $canal->shouldNotHaveReceived('info', function (string $mensagem): bool {
        return str_contains($mensagem, 'segredo-irreconhecivel-42')
            || str_contains($mensagem, 'ja.tem@example.com');
    });

    $canal->shouldHaveReceived('info', fn (string $mensagem): bool => str_contains(
        $mensagem,
        Str::mask('ja.tem@example.com', '*', 3),
    ));
})->group('kit');

/*
|--------------------------------------------------------------------------
| R10 e R11 — o rodapé da tela de login
|--------------------------------------------------------------------------
*/

/**
 * CT-14 — a exibição do rodapé por estado do texto.
 *
 * A linha "só espaços" é a discriminante: ela distingue `filled()` de uma comparação com
 * `null`, e é o que impede uma faixa vazia na tela.
 */
it('exibe o rodapé da tela de login só quando há texto configurado', function (
    ?string $texto,
    bool $deveAparecer,
): void {
    config()->set('kit.login.rodape', $texto);

    $resposta = $this->get('/app/login')->assertOk();

    $deveAparecer
        ? $resposta->assertSee('fi-login-rodape', escape: false)
        : $resposta->assertDontSee('fi-login-rodape', escape: false);
})->with([
    'com texto'      => ['Kit — todos os direitos reservados', true],
    'vazio'          => ['', false],
    'só espaços'     => ['   ', false],
])->group('kit');

/**
 * CT-15 — HTML no rodapé sai ESCAPADO.
 *
 * Escalonamento declarado acima do perfil da área: a implementação defeituosa plausível é a
 * saída crua do Blade "para permitir link no rodapé", e ela é XSS armazenado numa página
 * PÚBLICA e NÃO AUTENTICADA — a tela por onde todo mundo entra. Nenhum exemplo de CT-14 a
 * distingue. Ver ADR-09.
 *
 * O par de asserções é o oráculo: o escapado presente E o executável ausente. Só a segunda
 * ficaria verde com o rodapé não renderizado.
 */
it('escapa o HTML do rodapé da tela de login', function (): void {
    config()->set('kit.login.rodape', '<script>alert(1)</script>Fiotec');

    $this->get('/app/login')
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;Fiotec', escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R12 — o login por Google não contorna o 2FA
|--------------------------------------------------------------------------
*/

/**
 * CT-16 — quem tem 2FA confirmado entra pelo Google e cai no desafio.
 *
 * A barreira não é do kit: é o middleware `MustTwoFactor` do Breezy
 * (`vendor/jeffgreco13/filament-breezy/src/Middleware/MustTwoFactor.php:42-43`), que entra no
 * stack por default porque o kit chama `enableTwoFactorAuthentication()` sem tocar no 4º
 * parâmetro (`.../Concerns/Plugin/HasTwoFactorAuthentication.php:29`). O caso existe porque
 * barreira de terceiro NÃO é barreira garantida: um mutante que autentique por outro guard, ou
 * que marque a sessão de 2FA como válida "porque o Google já autenticou", a desliga sem erro.
 *
 * Painel `/admin` e não `/app`: o `MustTwoFactor` tem um `return` antecipado quando há tenancy
 * e a rota não traz o parâmetro `tenant` (`:31-33`). Em `tests/Kit` a tenancy está desligada,
 * mas escolher `/admin` mantém o caso correto nos dois modos.
 *
 * `confirm()` direto e não `confirmTwoFactorAuthentication()`: o segundo também chama
 * `setTwoFactorSession()`, o que abriria a sessão de 2FA e faria o caso passar por acidente.
 */
it('não deixa o login pelo Google contornar o segundo fator', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    noPainelBootado('admin');

    ligarLoginComGoogleDoKit();

    $user = usuarioDoKit('admin', 'ja.tem@example.com');
    $user->enableTwoFactorAuthentication();
    $user->breezySession->confirm();

    Socialite::fake('google', usuarioDoGoogleFalso());

    $this->get('/auth/google/callback')->assertStatus(302);

    $this->get('/admin')
        ->assertRedirectContains('two-factor');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R13 e R14 — as fronteiras: documentação e coerção de env
|--------------------------------------------------------------------------
*/

/**
 * CT-17 — as chaves novas estão declaradas onde quem instala o kit as encontra.
 *
 * Asserção de PRESENÇA sobre o texto cru, então sem o filtro de comentário que
 * `.ai/rules/testes.md` exige — o filtro é obrigatório só na asserção de ausência, que é
 * CT-18.
 *
 * `/auth/google/callback` nos READMEs cobre o lado da documentação do caminho literal do
 * requisito: quem cadastra a URI no console do Google precisa achá-la escrita.
 */
it('declara as chaves do login social no .env.example e nos dois READMEs', function (
    string $arquivo,
    string $termo,
): void {
    expect(file_get_contents(base_path($arquivo)))->toContain($termo);
})->with([
    ['.env.example', 'KIT_SOCIALITE_GOOGLE'],
    ['.env.example', 'GOOGLE_CLIENT_ID'],
    ['.env.example', 'GOOGLE_CLIENT_SECRET'],
    ['.env.example', 'KIT_LOGIN_RODAPE'],
    ['README.md', 'KIT_SOCIALITE_GOOGLE'],
    ['README.md', '/auth/google/callback'],
    ['README.en.md', 'KIT_SOCIALITE_GOOGLE'],
    ['README.en.md', '/auth/google/callback'],
])->group('kit');

/**
 * CT-18 — a coercao do interruptor falha FECHADO.
 *
 * Este caso nasceu errado e a correcao vale mais que ele: a versao original tambem varria o
 * arquivo inteiro afirmando que `(bool) env(` era antipadrao, e REPROVOU — porque tres chaves
 * irmas do `config/kit.php` (`tenancy.enabled`, `demo`, `hub`) usam exatamente isso.
 *
 * Medido no vendor antes de reescrever, que e o que `.ai/rules/specs.md` cobra: o
 * `Env::getOption()` do Laravel JA converte "true"/"false"/"(false)"/"null"/"empty" em valor
 * PHP (`vendor/laravel/framework/src/Illuminate/Support/Env.php:252-262`), entao para todo
 * valor documentado no `.env.example` o cast de bool acerta e as tres irmas nao estao erradas.
 *
 * A diferenca real e de DIRECAO, e aparece so no que o Laravel nao reconhece:
 *
 *   valor    | (bool) | filter_var
 *   "off"    | true   | false
 *   "no"     | true   | false
 *   "lixo"   | true   | false
 *
 * O cast falha ABERTO; o `filter_var` falha FECHADO. Para as irmas isso e gosto; aqui nao e,
 * porque este interruptor abre uma superficie PUBLICA de OAuth e "off" e um valor que gente
 * escreve.
 *
 * Por isso a assercao e de PRESENCA da coercao que falha fechado, e nao mais uma varredura de
 * ausencia — que era a assercao errada, sobre um padrao que nao e defeito.
 */
it('coage o interruptor do login social por uma regra que falha fechado', function (): void {
    expect(file_get_contents(config_path('kit.php')))
        ->toContain("filter_var(env('KIT_SOCIALITE_GOOGLE', false), FILTER_VALIDATE_BOOLEAN)");
})->group('kit');

/**
 * CT-18b — e o interruptor fica DESLIGADO diante de valor irreconhecivel.
 *
 * O contrapeso comportamental de CT-18: sem ele, a asercao de presenca acima e sobre TEXTO do
 * arquivo, e uma implementacao que escrevesse a linha certa e lesse outra chave passaria.
 *
 * Nao ha `putenv()` aqui de proposito — `.ai/rules` e a propria skill de teste registram que
 * teste que mexe em env passa local e falha no CI. O que se exercita e a coercao com o valor
 * que discrimina, na mesma expressao que o `config/kit.php` usa.
 */
it('mantém o interruptor desligado diante de valor irreconhecível', function (string $valor): void {
    expect(filter_var($valor, FILTER_VALIDATE_BOOLEAN))->toBeFalse()
        ->and((bool) $valor)->toBeTrue();
})->with(['off', 'no', 'lixo'])->group('kit');

/*
|--------------------------------------------------------------------------
| R15 — o rastro: channel `autenticacao` e trilha de acesso
|--------------------------------------------------------------------------
*/

/**
 * CT-19 — o sucesso grava no channel de autenticação, no formato do kit.
 */
it('grava log informativo no channel de autenticação ao entrar pelo Google', function (): void {
    ligarLoginComGoogleDoKit();
    $user = usuario('ja.tem@example.com');

    $canal = espiarAutenticacao();

    Socialite::fake('google', usuarioDoGoogleFalso());

    $this->get('/auth/google/callback')->assertStatus(302);

    $canal->shouldHaveReceived('info', fn (string $mensagem): bool => str_contains(
        $mensagem,
        "[LoginComGoogleController@retorno] Autenticado pelo Google | user: {$user->getKey()}",
    ));
})->group('kit');

/**
 * CT-20 — a recusa grava ALERTA com o motivo, e NÃO grava sucesso.
 *
 * A terceira asserção é a direção "não aconteceu quando não devia": ela mata o `return`
 * esquecido, em que a recusa loga o alerta e segue para o log de sucesso.
 */
it('grava alerta com o motivo na recusa, sem log de autenticação', function (): void {
    ligarLoginComGoogleDoKit();

    $canal = espiarAutenticacao();

    Socialite::fake('google', usuarioDoGoogleFalso(['email' => 'de.fora@example.com']));

    $this->get('/auth/google/callback')->assertStatus(302);

    $canal->shouldHaveReceived('warning', fn (string $mensagem, array $contexto): bool => str_contains($mensagem, 'Recusado')
        && ($contexto['motivo'] ?? null) === 'conta_inexistente_registro_fechado');

    $canal->shouldNotHaveReceived('info', fn (string $mensagem): bool => str_contains($mensagem, 'Autenticado pelo Google'));
})->group('kit');

/**
 * CT-21 — o login pelo Google entra na trilha de acesso da instalação.
 *
 * Prova algo que NENHUMA linha desta feature escreve: `Auth::login()` dispara
 * `Illuminate\Auth\Events\Login`, que o `rappasoft/laravel-authentication-log` escuta
 * (`.../LaravelAuthenticationLogServiceProvider.php:35`) e grava em `authentication_log`
 * (`.../Models/AuthenticationLog.php:33`).
 *
 * É o único caso que mata "abrir a sessão por fora do `Auth::login()`" — escrevendo na sessão
 * à mão, ou por um guard próprio. Essa implementação passa em TODOS os outros casos e
 * desaparece da trilha de acesso do painel /infra, sem erro nenhum.
 */
it('registra o login pelo Google na trilha de acesso', function (): void {
    ligarLoginComGoogleDoKit();
    $user = usuario('ja.tem@example.com');

    Socialite::fake('google', usuarioDoGoogleFalso());

    $this->get('/auth/google/callback')->assertStatus(302);

    $this->assertDatabaseHas('authentication_log', [
        'authenticatable_id'   => $user->getKey(),
        'authenticatable_type' => $user->getMorphClass(),
    ]);
})->group('kit');
