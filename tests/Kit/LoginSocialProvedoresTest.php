<?php

use App\Models\User;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\ConfiguracaoDoLogin;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\User as UsuarioDoProvedor;
use Psr\Log\LoggerInterface;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Tests\TestCase;

/**
 * Login social com os QUATRO provedores — as barreiras, a tela, as rotas e o rastro.
 *
 * Derivado de `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/04-casos-de-teste.md`
 * (R1–R7, R13–R16, R18, R19). O gate de mutantes de cada regra está lá; aqui cada caso diz, no
 * docblock, qual implementação errada ele reprova.
 *
 * **Nenhum caso sai para a rede.** Duas defesas, e elas cobrem coisas diferentes:
 *
 * - `Socialite::fake($driver, $usuario)` para o provedor. É a API oficial do pacote.
 * - `Http::fake()` + `Http::preventStrayRequests()` para a chamada do KIT a
 *   `api.github.com/user/emails`. `Http::fake()` sem stub devolve 200 vazio, então o par é
 *   obrigatório: sem o `preventStrayRequests()` um stub com a URL errada passa em silêncio.
 *
 * **Registrado porque não é intuitivo**: `Http::preventStrayRequests()` NÃO protege o Socialite
 * — ele usa o Guzzle dele (`getHttpClient()`), não a facade `Http`. Nos casos que rodam sem
 * `Socialite::fake()` (CT-04, CT-04b e a linha do Google em CT-37) a garantia é a ORDEM do
 * vendor, e está escrita no docblock de cada um.
 *
 * O que este arquivo NÃO cobre, de propósito: a partição de verificação do Google (a wiki
 * ancestral já a tem exaustiva, `tests/Kit/LoginSocialGoogleTest.php` CT-11), os segredos no
 * Settings e a tela de configurações (`tests/Kit/SegredosDoSettingsTest.php`) e a multi-tenancy
 * (`tests/Tenancy/LoginSocialProvedoresTenancyTest.php`).
 */
beforeEach(function (): void {
    /*
     * O kit não chama API de provedor nenhuma. Isto vale para o arquivo inteiro: qualquer
     * chamada pela facade `Http` em qualquer caso daqui estoura. NÃO cobre o Socialite, que usa
     * o Guzzle dele — quem o impede de sair para a rede é o `Socialite::fake()` de cada caso.
     */
    Http::preventStrayRequests();

    /*
     * O rodapé fica fora do caminho: ele é `null` no kit de fábrica, e as asserções de ausência
     * de botão desta suíte não podem depender de texto configurado por outra feature.
     *
     * Os quatro interruptores NÃO são fixados aqui, e nem o do registro aberto. `KIT_SOCIALITE_*`
     * não está no `phpunit.xml` (conferido), e é isso que faz as linhas "de fábrica" de CT-01 e as
     * linhas "DEFAULT de fábrica" de CT-45 medirem o default de verdade em vez do `phpunit.xml`.
     */
    config()->set('kit.login.rodape', null);

    /*
     * Registro aberto atribui o papel `panel_user` ao criar a conta. O papel só existe depois
     * do seeder; sem ele, `assignRole()` estoura "There is no role named `panel_user`".
     */
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| Os dois veredictos, definidos UMA vez
|--------------------------------------------------------------------------
| CT-10, CT-11, CT-12, CT-15 e CT-45 falam de "autentica" e "recusa". Nenhum
| dos dois é oráculo — são apelidos para os dois conjuntos de asserções abaixo,
| e é aqui que eles ficam escritos.
|
| As duas linhas que separam recusa de "recusa DEPOIS de gravar" e de "recusa
| que segue para o sucesso" (o `return` esquecido) são o total de contas e a
| ausência do `info` de sucesso. Sem elas o apelido não valeria nada.
|
| Recebe o `TestCase` por parâmetro porque função em PHP não tem `$this` — e ela
| é função de arquivo, não de `tests/Pest.php`, porque só este arquivo a usa
| (`.ai/rules/testes.md`).
*/

function conferirVeredicto(
    TestCase $caso,
    TestResponse $resposta,
    string $veredicto,
    ?User $user,
    int $contas,
    LoggerInterface $canal,
): void {
    $sucesso = static fn (string $mensagem): bool => str_contains($mensagem, 'Autenticado pelo provedor');

    $resposta->assertStatus(302);

    if ($veredicto === 'autentica') {
        $caso->assertAuthenticatedAs($user);

        expect($resposta->headers->get('Location'))->toContain('/app')
            ->and($resposta->headers->get('Location'))->not->toContain('/app/login');

        $canal->shouldHaveReceived('info', $sucesso);
    } else {
        $caso->assertGuest();

        $resposta->assertRedirectContains('/app/login');

        $canal->shouldHaveReceived('warning', static fn (string $mensagem, array $contexto): bool => filled($contexto['motivo'] ?? null));

        $canal->shouldNotHaveReceived('info', $sucesso);
    }

    expect(User::query()->count())->toBe($contas);
}

/**
 * O usuário de um provedor com o campo de verificação em UM LADO SÓ do objeto.
 *
 * Existe para as duas linhas de CT-10 que matam M34 — e ele é o oposto de
 * `usuarioSocialFalso()` de `tests/Pest.php`, que põe a verificação só no BRUTO (o lado em que o
 * driver real a entrega). Aqui é possível pedir o lado errado de propósito:
 *
 * - `'bruto'` → só `getRaw()` tem o campo; é o que o `LinkedInOpenIdProvider` real faz;
 * - `'atributo'` → só o atributo mapeado tem o campo, o bruto não; a falha fechada correta é
 *   RECUSAR, e é isso que reprova quem lê `$doProvedor->email_verified`;
 * - `'nenhum'` → o campo não existe em lado nenhum; é a partição "campo ausente".
 *
 * Fica neste arquivo porque só CT-10 usa — `.ai/rules/testes.md` manda mover para `tests/Pest.php`
 * só o helper usado por MAIS DE UM arquivo.
 */
function usuarioDoLinkedInComVerificacaoSoEm(string $lado, string $email): UsuarioDoProvedor
{
    $mapeados = ['id' => 'linkedin-openid-123', 'name' => 'Vítima', 'email' => $email];

    $usuario = UsuarioDoProvedor::fake($lado === 'atributo'
        ? $mapeados + ['email_verified' => true]
        : $mapeados);

    return $usuario->setRaw($lado === 'bruto'
        ? $mapeados + ['email_verified' => true]
        : $mapeados);
}

/**
 * As seções de um arquivo Markdown, quebradas nos títulos — para CT-42b.
 *
 * "Na mesma seção" é o que separa a recusa EXPLICADA de uma menção decorativa numa linha de
 * roadmap, que é o que a revisão adversarial achou em CT-42.
 *
 * @return array<int, string>
 */
function secoesDoMarkdown(string $caminho): array
{
    return preg_split('~^#{1,6} ~m', (string) file_get_contents(base_path($caminho))) ?: [];
}

/*
|--------------------------------------------------------------------------
| R1 — o botão aparece se e somente se o interruptor está ligado E as três
|      credenciais estão preenchidas
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a tabela de decisão da exibição do botão, provedor por provedor.
 *
 * Dezesseis linhas e nenhuma colapsada. As quatro linhas "de fábrica" são as únicas que medem o
 * DEFAULT de verdade: elas não chamam `ligarProvedor()`, e `KIT_SOCIALITE_*` não está no
 * `phpunit.xml` — fixá-las aqui mediria o `phpunit.xml` (M5).
 *
 * "client_secret vazio" é string VAZIA e não ausente: é o que sobra de um `.env` preenchido pela
 * metade, e `isset()` passa por ele (M2, M3). A linha "só com espaços" é a fronteira de string
 * que separa `filled()` de uma comparação com `''`. A de "interruptor desligado, três chaves"
 * isola a condição 1 (M4), e as de `client_id` e `redirect` vazios cobram as outras duas
 * sub-chaves (M1).
 */
it('exibe o botão de cada provedor só com o interruptor ligado e as três credenciais preenchidas', function (
    ProvedorSocial $provedor,
    string $arranjo,
    bool $deveAparecer,
): void {
    match ($arranjo) {
        'de fabrica'            => null,
        'interruptor desligado' => config()->set([
            "kit.login.{$provedor->value}.habilitado" => false,
            'services.'.$provedor->value              => [
                'client_id'     => 'id-de-teste',
                'client_secret' => 'segredo-de-teste',
                'redirect'      => "/auth/{$provedor->value}/callback",
            ],
        ]),
        'secret vazio'       => ligarProvedor($provedor, ['client_secret' => '']),
        'secret com espacos' => ligarProvedor($provedor, ['client_secret' => '   ']),
        'id vazio'           => ligarProvedor($provedor, ['client_id' => '']),
        'redirect vazio'     => ligarProvedor($provedor, ['redirect' => '']),
        'completo'           => ligarProvedor($provedor),
    };

    $resposta = $this->get('/app/login')->assertOk();

    $deveAparecer
        ? $resposta->assertSee('Entrar com '.$provedor->rotulo())
        : $resposta->assertDontSee('Entrar com '.$provedor->rotulo());
})->with([
    'google de fábrica'                       => [ProvedorSocial::Google, 'de fabrica', false],
    'github de fábrica'                       => [ProvedorSocial::Github, 'de fabrica', false],
    'linkedin de fábrica'                     => [ProvedorSocial::LinkedIn, 'de fabrica', false],
    'x de fábrica'                            => [ProvedorSocial::X, 'de fabrica', false],
    'google com client_secret vazio'          => [ProvedorSocial::Google, 'secret vazio', false],
    'github com client_secret vazio'          => [ProvedorSocial::Github, 'secret vazio', false],
    'linkedin com client_secret vazio'        => [ProvedorSocial::LinkedIn, 'secret vazio', false],
    'x com client_secret vazio'               => [ProvedorSocial::X, 'secret vazio', false],
    'google completo'                         => [ProvedorSocial::Google, 'completo', true],
    'github completo'                         => [ProvedorSocial::Github, 'completo', true],
    'linkedin completo'                       => [ProvedorSocial::LinkedIn, 'completo', true],
    'x completo'                              => [ProvedorSocial::X, 'completo', true],
    'github desligado com as três chaves'     => [ProvedorSocial::Github, 'interruptor desligado', false],
    'linkedin com client_id vazio'            => [ProvedorSocial::LinkedIn, 'id vazio', false],
    'x com redirect vazio'                    => [ProvedorSocial::X, 'redirect vazio', false],
    'github com client_secret só de espaços'  => [ProvedorSocial::Github, 'secret com espacos', false],
])->group('kit');

/**
 * CT-02 — a lista de disponíveis traz exatamente os completos, na ordem do enum.
 *
 * O único caso deste arquivo preso a nome de método, e a ORDEM é o oráculo que justifica isso:
 * sem ela, um `array_filter()` que devolve o array com as chaves originais e um que devolve
 * `array_values()` são indistinguíveis — e o primeiro quebra o laço do blade (M7). A comparação
 * é com a lista INTEIRA, e não `toContain`, que ficaria verde com `cases()` devolvido cru (M6).
 */
it('lista como disponíveis exatamente os provedores completos, na ordem do enum', function (): void {
    ligarProvedor(ProvedorSocial::Github);
    ligarProvedor(ProvedorSocial::X);
    ligarProvedor(ProvedorSocial::Google, ['client_secret' => '']);
    ligarProvedor(ProvedorSocial::LinkedIn);
    config()->set('kit.login.linkedin-openid.habilitado', false);

    expect(ConfiguracaoDoLogin::disponiveis())->toBe([ProvedorSocial::Github, ProvedorSocial::X]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — indisponível, as duas rotas respondem 404; disponível, as duas ficam no ar
|--------------------------------------------------------------------------
*/

/**
 * CT-03 — as dezesseis células INVÁLIDAS da matriz estado × operação × provedor.
 *
 * Esconder o botão não é barreira: a URL é fixa, pública e conhecida. Nenhuma célula colapsada,
 * porque o mutante clássico é pôr o guarda no `redirect` — onde quem escreve está pensando no
 * botão — e esquecer o `callback` (M8, M9). As oito linhas de `client_secret vazio` são as que
 * reprovam o guarda que confere só o interruptor (M11).
 *
 * **O que este caso NÃO mata, e está registrado na revisão adversarial**: rota registrada dentro
 * de um `if` do interruptor (M14) devolve 404 igual ao guarda, e um guarda com provedor FIXO
 * (M12) também devolve 404 nas dezesseis linhas, porque no arranjo tudo está indisponível. Quem
 * mata M14 é CT-41 (última asserção) e quem mata M12 é CT-04.
 */
it('responde 404 nas duas rotas de OAuth enquanto o provedor está indisponível', function (
    ProvedorSocial $provedor,
    string $motivo,
    string $sufixo,
): void {
    match ($motivo) {
        'interruptor desligado' => config()->set([
            "kit.login.{$provedor->value}.habilitado" => false,
            'services.'.$provedor->value              => [
                'client_id'     => 'id-de-teste',
                'client_secret' => 'segredo-de-teste',
                'redirect'      => "/auth/{$provedor->value}/callback",
            ],
        ]),
        'client_secret vazio' => ligarProvedor($provedor, ['client_secret' => '']),
    };

    $this->get("/auth/{$provedor->value}/{$sufixo}")->assertNotFound();

    $this->assertGuest();
})->with(function (): iterable {
    foreach (ProvedorSocial::cases() as $provedor) {
        foreach (['interruptor desligado', 'client_secret vazio'] as $motivo) {
            foreach (['redirect', 'callback'] as $sufixo) {
                yield "{$provedor->value}, {$motivo}, rota de {$sufixo}" => [$provedor, $motivo, $sufixo];
            }
        }
    }
})->group('kit');

/**
 * CT-04 — a rota de ida manda a pessoa para O PROVEDOR DELA, com a credencial DELA.
 *
 * **Roda sem `Socialite::fake()`, e é deliberado.** Com o fake, o destino é o mesmo para a
 * implementação certa e para um driver FIXO com o parâmetro usado só no guarda e no log — o
 * `FakeProvider` não depende do driver pedido. Os quatro botões mandariam todo mundo para o
 * Google e o caso ficaria verde (M12b). Nenhum cenário da rodada 1 matava esse mutante.
 *
 * **E também não toca a rede**: `AbstractProvider::redirect()` só monta a URL de autorização e
 * devolve um `RedirectResponse` (`vendor/laravel/socialite/src/Two/AbstractProvider.php:83`).
 * Quem sai para a rede é `user()`, que este caso não chama.
 *
 * As quatro URLs de autorização foram LIDAS no vendor, não presumidas. A do X é a discriminante
 * do conjunto: o `TwitterProvider` pai usa `twitter.com` (`TwitterProvider.php:43`) e o
 * `XProvider` sobrescreve para `x.com` (`XProvider.php:15`) — esta linha é a que reprova o kit
 * oferecendo o driver `twitter` no lugar do `x`.
 *
 * O `client_id` no `Então` mata a variante mais sutil: driver certo, credencial de OUTRO
 * provedor (M12). E as quatro linhas juntas são células VÁLIDAS que reprovam um guarda com a
 * condição negada, que derrubaria a rota sempre (M10).
 */
it('manda a pessoa para o provedor dela, com a credencial dela, na rota de ida', function (
    ProvedorSocial $provedor,
    string $hostDeAutorizacao,
): void {
    ligarProvedor($provedor, ['client_id' => "id-de-{$provedor->value}"]);

    $destino = $this->get("/auth/{$provedor->value}/redirect")
        ->assertStatus(302)
        ->headers->get('Location');

    expect($destino)->toStartWith($hostDeAutorizacao);

    parse_str((string) parse_url((string) $destino, PHP_URL_QUERY), $parametros);

    expect($parametros['client_id'] ?? null)->toBe("id-de-{$provedor->value}")
        ->and($parametros['redirect_uri'] ?? '')->toEndWith("/auth/{$provedor->value}/callback");
})->with([
    'google'   => [ProvedorSocial::Google, 'https://accounts.google.com/o/oauth2/auth'],
    'github'   => [ProvedorSocial::Github, 'https://github.com/login/oauth/authorize'],
    'linkedin' => [ProvedorSocial::LinkedIn, 'https://www.linkedin.com/oauth/v2/authorization'],
    'x'        => [ProvedorSocial::X, 'https://x.com/i/oauth2/authorize'],
])->group('kit');

/**
 * CT-04b — a rota de volta trata a recusa em vez de estourar.
 *
 * A célula válida da coluna do `callback`: chega sem `code` e sem `state`, então o resultado
 * esperado é a recusa TRATADA — 302 para a tela de login, e não 500 (M13).
 *
 * Também sem `Socialite::fake()`, e sem rede: `AbstractProvider::user()` chama
 * `hasInvalidState()` ANTES de `getAccessTokenResponse()`
 * (`vendor/laravel/socialite/src/Two/AbstractProvider.php:230-241`), e `hasInvalidState()` só lê
 * a sessão e o input (`:282-290`). A `InvalidStateException` é lançada sem um único byte de rede.
 */
it('trata a recusa na rota de volta em vez de estourar, em cada provedor', function (
    ProvedorSocial $provedor,
): void {
    ligarProvedor($provedor);

    $this->get("/auth/{$provedor->value}/callback")
        ->assertStatus(302)
        ->assertRedirectContains('/app/login');

    $this->assertGuest();
})->with([
    'google'   => [ProvedorSocial::Google],
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — o estado de um provedor não altera o de nenhum outro
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — ligar só o GitHub não põe nenhum outro provedor no ar.
 *
 * Os outros três ficam DESLIGADOS mas com as três chaves preenchidas: é o arranjo favorável ao
 * mutante, porque um predicado que confira só a credencial os poria no ar (M17). E é ele que
 * reprova o interruptor único para o login social inteiro (M16, M100) e a chave lida com provedor
 * fixo (M15).
 *
 * A linha do LinkedIn é a que mata M19 — credencial gravada em `services.linkedin` e lida em
 * `services.linkedin-openid`, a alternativa 3 recusada no ADR-01: com o nome divergindo, o
 * LinkedIn desligado com credenciais responderia 302.
 */
it('põe no ar só o provedor ligado, sem vazar para os outros três', function (): void {
    ligarProvedor(ProvedorSocial::Github);

    foreach ([ProvedorSocial::Google, ProvedorSocial::LinkedIn, ProvedorSocial::X] as $outro) {
        ligarProvedor($outro);
        config()->set("kit.login.{$outro->value}.habilitado", false);
    }

    $this->get('/app/login')
        ->assertOk()
        ->assertSee('Entrar com GitHub')
        ->assertDontSee('Entrar com Google')
        ->assertDontSee('Entrar com LinkedIn')
        ->assertDontSee('Entrar com X');

    $this->get('/auth/github/redirect')->assertStatus(302);

    $this->get('/auth/google/redirect')->assertNotFound();
    $this->get('/auth/linkedin-openid/redirect')->assertNotFound();
    $this->get('/auth/x/redirect')->assertNotFound();
})->group('kit');

/**
 * CT-06 — desligar o Google não derruba o GitHub.
 *
 * A direção simétrica de CT-05: aquele é "ligar não vaza", este é "desligar não arrasta".
 *
 * A tela é renderizada ANTES do desligamento, de propósito: é o que dá ao mutante que cacheia a
 * lista de disponíveis entre requests a chance de cachear (M18). Sem o primeiro render, um cache
 * nasceria vazio depois do desligamento e o caso ficaria verde.
 */
it('não derruba o GitHub quando o Google é desligado', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    ligarProvedor(ProvedorSocial::Github);

    $this->get('/app/login')
        ->assertOk()
        ->assertSee('Entrar com Google')
        ->assertSee('Entrar com GitHub');

    config()->set('kit.login.google.habilitado', false);

    $this->get('/app/login')
        ->assertOk()
        ->assertSee('Entrar com GitHub')
        ->assertDontSee('Entrar com Google');

    $this->get('/auth/github/redirect')->assertStatus(302);
    $this->get('/auth/google/redirect')->assertNotFound();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — provedor fora do enum responde 404, mesmo com credenciais em config
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — as partições inválidas do parâmetro de provedor.
 *
 * Este é o caso que PROVA que Facebook e Discord estão fora (ADR-04, ADR-05), e o arranjo é o
 * mais favorável possível ao mutante: interruptor ligado E três credenciais preenchidas em
 * `config`. `config()->set()` aceita qualquer chave, então o arranjo é realizável — o enum é o
 * que não aceita.
 *
 * O 404 vem do implicit enum binding do roteador, antes de qualquer código do kit rodar, e é
 * justamente isso que o caso prova: um parâmetro `string` com `Socialite::driver($provedor)`
 * responderia 302 aqui (M20), e um `tryFrom()` no controller responderia 302 para a tela de
 * login em vez de 404 (M22).
 *
 * A linha `slack` é a discriminante do conjunto. `facebook`, `discord` e `twitter` também
 * morreriam num `match` com lista branca escrita à mão; `slack` é um driver EXISTENTE e válido do
 * Socialite que o kit não oferece, e ele só é recusado se a lista for o ENUM.
 */
it('responde 404 para segmento de provedor que não é caso do enum', function (
    string $segmento,
    string $sufixo,
): void {
    config()->set("kit.login.{$segmento}.habilitado", true);
    config()->set("services.{$segmento}", [
        'client_id'     => 'id-de-teste',
        'client_secret' => 'segredo-de-teste',
        'redirect'      => "/auth/{$segmento}/callback",
    ]);

    $this->get("/auth/{$segmento}/{$sufixo}")->assertNotFound();

    $this->assertGuest();
})->with([
    'facebook, ida (ADR-05: sem prova de e-mail verificado)' => ['facebook', 'redirect'],
    'facebook, volta'                                        => ['facebook', 'callback'],
    'discord, ida (ADR-04: não é driver do Socialite)'       => ['discord', 'redirect'],
    'discord, volta'                                         => ['discord', 'callback'],
    'twitter, ida (OAuth 1.0 não põe e-mail no bruto)'       => ['twitter', 'redirect'],
    'linkedin legado, ida (sem campo de verificação)'        => ['linkedin', 'redirect'],
    'slack, ida (driver que existe e o kit não oferece)'     => ['slack', 'redirect'],
])->group('kit');

/**
 * CT-07b — o segmento NÃO é normalizado antes de resolver o enum.
 *
 * O arranjo é o OPOSTO de CT-07 e é isso que torna estas linhas discriminantes: com o `google`
 * LIGADO, a implementação que normaliza o segmento responde **302** e a que não normaliza
 * responde **404** (M23). Com o Google desligado — como estava na primeira rodada da derivação —
 * as duas respondem 404 e o mutante sobrevive com a linha marcada de verde.
 */
it('não normaliza o segmento de provedor antes de resolver o enum', function (
    string $segmento,
): void {
    ligarProvedor(ProvedorSocial::Google);

    $this->get("/auth/{$segmento}/redirect")->assertNotFound();

    $this->assertGuest();
})->with([
    'caixa alta'        => ['GOOGLE'],
    'caixa mista'       => ['Google'],
    'espaço à direita'  => ['google%20'],
    'espaço à esquerda' => ['%20google'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — cada botão traz o ícone da marca daquele provedor e o href da rota dele
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — o botão, o ícone e o href por provedor × os TRÊS painéis.
 *
 * O marcador de ícone é o `data-provedor="{value}"` do `<svg>` de cada partial — a pergunta A-01
 * da derivação, respondida IMPLEMENTANDO o atributo. Sem ele, três dos quatro ícones são
 * monocromáticos e o oráculo teria de cair para um fragmento do `d` do path de cada marca.
 * `assertSee('svg')` ficaria verde com um Heroicon genérico no lugar do logo, que é exatamente o
 * mutante que Heroicons-não-tem-marca torna tentador.
 *
 * O valor do atributo é o `value` do enum e não o `icone()`: no LinkedIn os dois divergem
 * (`linkedin-openid` × `linkedin`), e é o `value` que identifica o provedor em toda superfície.
 *
 * O arranjo de "só um provedor ligado" é o que torna a CRUZ-NEGATIVA expressável: com os quatro
 * na tela, "não traz o marcador do outro" é falso por construção. É ela que mata a partial fixa
 * (M25) — em 9 das 12 linhas.
 *
 * `assertSeeInOrder` e não `assertSee`: o requisito diz DEPOIS do formulário, e `assertSee`
 * ficaria verde com o botão no topo (M27). O `href` por linha mata o link montado com provedor
 * fixo ou com o `rotulo()` no lugar do `value` (M26), e o texto `Entrar com LinkedIn` mata o
 * `rotulo()` que devolve o `value` cru (M30).
 *
 * Os três painéis, exaustivos e não amostrados: o defeito histórico do kit nessa área é
 * configurar um painel e esquecer os outros dois, e a registração do render hook é única e sem
 * escopo (M28).
 */
it('põe o botão de cada provedor depois do formulário, com o ícone da marca dele, nos três painéis', function (
    ProvedorSocial $provedor,
    string $painel,
): void {
    ligarProvedor($provedor);

    $resposta = $this->get("/{$painel}/login")
        ->assertOk()
        ->assertSeeInOrder(['form.password', 'Entrar com '.$provedor->rotulo()], escape: false)
        ->assertSee("/auth/{$provedor->value}/redirect", escape: false)
        ->assertSee('data-provedor="'.$provedor->value.'"', escape: false);

    foreach (ProvedorSocial::cases() as $outro) {
        if ($outro !== $provedor) {
            $resposta->assertDontSee('data-provedor="'.$outro->value.'"', escape: false);
        }
    }
})->with(function (): iterable {
    foreach (ProvedorSocial::cases() as $provedor) {
        foreach (['app', 'admin', 'infra'] as $painel) {
            yield "{$provedor->value} no painel /{$painel}" => [$provedor, $painel];
        }
    }
})->group('kit');

/**
 * CT-09 — com nenhum provedor disponível, o divisor "ou" também não aparece.
 *
 * O contrapeso do laço: um `@if` errado deixa um divisor "ou" solto numa tela sem botão nenhum
 * (M29) — cosmético, mas é a tela por onde todo mundo entra.
 *
 * O oráculo do divisor é o estilo inline dele, e não a palavra "ou" solta: a palavra aparece em
 * texto corrido de qualquer tela em português e daria falso vermelho.
 */
it('não renderiza o divisor "ou" quando nenhum provedor está disponível', function (): void {
    $this->get('/app/login')
        ->assertOk()
        ->assertDontSee('Entrar com')
        ->assertDontSee('fi-login-social', escape: false)
        ->assertDontSee('opacity:.6">ou<', escape: false);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — o login só autentica quando AQUELE provedor prova que o e-mail está
|      verificado
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — a partição EXAUSTIVA do valor de verificação do LinkedIn. Não se amostra.
 *
 * A linha `false` é a tomada de conta: casar por e-mail que o provedor não verificou permite
 * criar uma conta no provedor com o e-mail de outra pessoa e entrar na conta dela.
 *
 * As linhas textuais são as discriminantes de duas direções opostas. `"false"` e `"0"` matam o
 * cast de bool, que as leria como verdadeiras (M32). `1` e `"true"` matam o `=== true` estrito,
 * que recusaria TODO login de LinkedIn — o bruto vem de JSON, e o sintoma seria "este provedor
 * nunca deixa ninguém entrar" (M33).
 *
 * As duas últimas linhas são as únicas que matam M34, e é por elas que
 * `usuarioDoLinkedInComVerificacaoSoEm()` existe: `Two\User::fake()` popula o bruto E o atributo
 * mapeado, então todo cenário faked com ele fica verde enquanto a produção recusaria todo login
 * de Google. A revisão adversarial declarou M34 morto por CT-12 e ele não estava.
 */
it('decide o login do LinkedIn pelo valor de verificação no bruto', function (
    string $arranjo,
    string $veredicto,
): void {
    $email = 'vitima@example.com';

    ligarProvedor(ProvedorSocial::LinkedIn);
    $user  = usuario($email);
    $canal = espiarAutenticacao();

    Http::fake();
    Http::preventStrayRequests();

    $doProvedor = match ($arranjo) {
        'so no bruto'    => usuarioDoLinkedInComVerificacaoSoEm('bruto', $email),
        'so no atributo' => usuarioDoLinkedInComVerificacaoSoEm('atributo', $email),
        'ausente'        => usuarioDoLinkedInComVerificacaoSoEm('nenhum', $email),
        default          => usuarioSocialFalso(
            ProvedorSocial::LinkedIn,
            ['email_verified' => match ($arranjo) {
                'true'         => true,
                'false'        => false,
                'string false' => 'false',
                'string zero'  => '0',
                'inteiro um'   => 1,
                'string true'  => 'true',
            }],
            ['email' => $email],
        ),
    };

    Socialite::fake(ProvedorSocial::LinkedIn->value, $doProvedor);

    $resposta = $this->get('/auth/linkedin-openid/callback');

    conferirVeredicto($this, $resposta, $veredicto, $user, 1, $canal);
})->with([
    'email_verified verdadeiro'              => ['true', 'autentica'],
    'email_verified falso (tomada de conta)' => ['false', 'recusa'],
    'campo ausente (falha fechada)'          => ['ausente', 'recusa'],
    'a string "false"'                       => ['string false', 'recusa'],
    'a string "0"'                           => ['string zero', 'recusa'],
    'o inteiro 1 do JSON'                    => ['inteiro um', 'autentica'],
    'a string "true" do JSON'                => ['string true', 'autentica'],
    'verdadeiro SÓ no bruto (mata M34)'      => ['so no bruto', 'autentica'],
    'verdadeiro SÓ no atributo (mata M34)'   => ['so no atributo', 'recusa'],
])->group('kit');

/**
 * CT-11 — o X decide pela PRESENÇA do e-mail, e um desmentido explícito vence a presença.
 *
 * O X só devolve `confirmed_email` e o provider mapeia esse campo — e nenhum outro — para
 * `email` (`vendor/laravel/socialite/src/Two/TwitterProvider.php:61,74`). Então ter e-mail já É a
 * prova (ADR-03).
 *
 * **A última linha INVERTEU em relação ao `04-casos-de-teste.md`, e é a resposta à pergunta
 * A-03.** A tabela derivada esperava `autentica` com `email_verified => false` no bruto, sob a
 * premissa de que o ramo do X não lê esse campo. A premissa foi NEGADA pelo dono do kit: o ramo
 * agora é `filled($email) && naoDesmentidoNoBruto(...)`, porque o argumento "a presença basta"
 * não pode valer para o X e não valer para o Facebook. A linha ficou aqui, invertida, em vez de
 * ser apagada — é ela que prova que o desmentido explícito vence a presença.
 *
 * A primeira linha continua matando o `match` de um ramo só com a régua do Google: o X não manda
 * campo de verificação nenhum e seria recusado sempre (M35). As duas do meio matam o `true` fixo
 * no ramo do X (M37) e o `isset()` no lugar de `filled()` (M38) — e-mail só com espaços passa
 * pelo `isset()`.
 */
it('decide o login do X pela presença do e-mail, salvo desmentido no bruto', function (
    ?string $email,
    bool $desmentido,
    string $veredicto,
): void {
    ligarProvedor(ProvedorSocial::X);
    $user  = usuario('vitima@example.com');
    $canal = espiarAutenticacao();

    Http::fake();
    Http::preventStrayRequests();

    Socialite::fake(ProvedorSocial::X->value, usuarioSocialFalso(
        ProvedorSocial::X,
        $desmentido ? ['email_verified' => false] : [],
        ['email' => $email],
    ));

    $resposta = $this->get('/auth/x/callback');

    conferirVeredicto($this, $resposta, $veredicto, $user, 1, $canal);
})->with([
    'e-mail preenchido, sem campo de verificação'         => ['vitima@example.com', false, 'autentica'],
    'e-mail nulo'                                         => [null, false, 'recusa'],
    'e-mail só com espaços'                               => ['   ', false, 'recusa'],
    'e-mail preenchido e email_verified falso (A-03)'     => ['vitima@example.com', true, 'recusa'],
])->group('kit');

/**
 * CT-12 — o MESMO payload recebe veredictos diferentes em provedores diferentes.
 *
 * É o caso que prova que o `match` do ADR-03 tem RAMOS DIFERENTES. Sem ele, um `match` de um ramo
 * único — a régua do Google para todos (M35), ou `true` para todos (M36) — fica verde em CT-10 ou
 * em CT-11, nunca reprovado pelos dois.
 *
 * **O eixo discriminante mudou de payload, e é consequência de A-03.** Na tabela derivada o
 * payload era `email_verified => false` e o X autenticava; com o ramo do X passando a respeitar o
 * desmentido, aquele payload recusa nos três e deixaria de distinguir ramo nenhum. O payload que
 * distingue agora é a **ausência** do campo: falha fechada no Google e no LinkedIn, prova
 * suficiente no X. As três linhas do payload antigo continuam aqui, todas `recusa` — elas são o
 * contrapeso que prova que o novo guarda do X não abriu nada.
 */
it('dá veredictos diferentes ao mesmo payload em provedores diferentes', function (
    ProvedorSocial $provedor,
    string $payload,
    string $veredicto,
): void {
    ligarProvedor($provedor);
    $user  = usuario('vitima@example.com');
    $canal = espiarAutenticacao();

    Http::fake();
    Http::preventStrayRequests();

    Socialite::fake($provedor->value, usuarioSocialFalso(
        $provedor,
        $payload === 'desmentido' ? ['email_verified' => false] : ['email_verified' => null],
        ['email' => 'vitima@example.com'],
    ));

    $resposta = $this->get("/auth/{$provedor->value}/callback");

    conferirVeredicto($this, $resposta, $veredicto, $user, 1, $canal);
})->with([
    'google sem campo de verificação'   => [ProvedorSocial::Google, 'sem campo', 'recusa'],
    'linkedin sem campo de verificação' => [ProvedorSocial::LinkedIn, 'sem campo', 'recusa'],
    'x sem campo de verificação'        => [ProvedorSocial::X, 'sem campo', 'autentica'],
    'google com desmentido no bruto'    => [ProvedorSocial::Google, 'desmentido', 'recusa'],
    'linkedin com desmentido no bruto'  => [ProvedorSocial::LinkedIn, 'desmentido', 'recusa'],
    'x com desmentido no bruto (A-03)'  => [ProvedorSocial::X, 'desmentido', 'recusa'],
])->group('kit');

/**
 * CT-13 — o e-mail ausente é partição inválida PRÓPRIA, nos quatro provedores.
 *
 * Isolada de CT-10 e CT-11 de propósito: combinar duas partições inválidas no mesmo caso faz a
 * primeira validação mascarar a segunda (M39). E uma implementação que confira só a verificação
 * estouraria aqui com `null` chegando ao `where` — 500 no callback, e o oráculo é 302 (M40).
 *
 * A última asserção é a do GitHub: com o e-mail ausente, a barreira anterior já recusou, então
 * nenhuma requisição pode ter saído para a API.
 */
it('recusa quando o provedor não devolve e-mail, em cada provedor', function (
    ProvedorSocial $provedor,
): void {
    ligarProvedor($provedor);
    usuario('ja.tem@example.com');

    Http::fake();
    Http::preventStrayRequests();

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor, [], ['email' => null]));

    $this->get("/auth/{$provedor->value}/callback")
        ->assertStatus(302)
        ->assertRedirectContains('/app/login');

    $this->assertGuest();

    expect(User::query()->count())->toBe(1);

    Http::assertNothingSent();
})->with([
    'google'   => [ProvedorSocial::Google],
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/**
 * CT-14 — idempotência do callback, ancorada no AGREGADO persistido.
 *
 * A âncora é o total de contas e o total de linhas de trilha de acesso, não o retorno da chamada
 * — esse passaria por construção. O total de contas mata `User::updateOrCreate()`, o exemplo da
 * própria doc do Socialite (M41).
 *
 * As DUAS linhas de trilha são o contrapeso: sem elas, uma implementação que aborte o segundo
 * callback por "já autenticado" passaria, e o kit perderia o registro do segundo acesso (M42).
 *
 * O Google fica de fora porque a wiki ancestral já tem o caso (CT-23 de lá). O GitHub entra
 * porque lá há uma chamada HTTP a mais, e "duas voltas, duas chamadas, uma conta" é informação
 * nova.
 */
it('não cria segunda conta quando a mesma volta chega duas vezes', function (
    ProvedorSocial $provedor,
): void {
    $email = 'ja.tem@example.com';

    ligarProvedor($provedor);
    $user = usuario($email);

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor, [], ['email' => $email]));

    $this->get("/auth/{$provedor->value}/callback")->assertStatus(302);
    $this->get("/auth/{$provedor->value}/callback")->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    expect(User::query()->count())->toBe(1);

    /*
     * UMA linha, e não duas — o pacote de trilha de acesso DEDUPLICA. Com
     * `prevent_session_restoration_logging` ligado (default) e `session_restoration_window_minutes`
     * em 5, a segunda entrada do mesmo dispositivo dentro da janela é tratada como restauração de
     * sessão e só bumpa `last_activity_at`
     * (`vendor/rappasoft/laravel-authentication-log/src/Listeners/LoginListener.php:59-80`).
     *
     * A primeira versão deste caso esperava 2 e reprovava. Vale registrar: a asserção estava
     * errada sobre um PACOTE, exatamente o que `.ai/rules/specs.md` manda conferir no `vendor/`
     * antes de escrever — a regra vale para caso de teste tanto quanto para wiki.
     *
     * O oráculo da idempotência é o `User::count()` acima; esta linha é o contrapeso que
     * documenta a janela, e é ela que fica vermelha se alguém desligar a deduplicação.
     */
    $this->assertDatabaseCount('authentication_log', 1);
})->with([
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — no GitHub e no X, a PRESENÇA do e-mail é a prova — e a invariante
|      de escopo em que isso se apoia é enforçada aqui
|--------------------------------------------------------------------------
| Esta regra foi REESCRITA depois de uma leitura errada do `vendor/`. A versão
| anterior afirmava que o `GithubProvider` deixa o e-mail do PERFIL PÚBLICO no
| lugar quando a consulta a `/user/emails` falha — e que, por isso, "e-mail não
| vazio" não provava verificação e o kit tinha de refazer a chamada.
|
| A linha 48 diz outra coisa: `$user['email'] = $this->getEmailByToken($token)`
| é atribuição INCONDICIONAL. E `getEmailByToken()` devolve o primeiro
| `primary && verified` (`:73`) ou **`null`** — tanto no `catch` (`:70`) quanto
| ao sair pelo fim do método sem casar nada (`:76`).
|
| Ou seja: para o GitHub, e-mail preenchido JÁ significa `primary && verified`,
| e e-mail nulo cai na barreira de `email_ausente`. A chamada extra era
| redundante, custava uma requisição por login e criava um modo de falha que a
| ausência dela não tem.
|
| A invariante em que o argumento se apoia é uma só: `user:email` está SEMPRE
| nos escopos. É o default do provider (`GithubProvider.php:16`), e a chave
| `scopes` de `config/services.php` passa por `scopes()`, que SOMA
| (`AbstractProvider.php:398`) — só `setScopes()` substitui (`:410`), e o kit
| não a chama.
|
| Essa invariante é enforçada por CT-17, em análise estática, e não por código
| em execução: um `in_array('user:email', getApprovedScopes())` no runtime
| dependeria de o GitHub sempre devolver `scope` na resposta do token, e se essa
| suposição estivesse errada o login do GitHub morreria inteiro. Invariante de
| configuração se guarda com teste, não com indisponibilidade.
*/

/**
 * CT-15 — presença e ausência de e-mail decidem, nos dois provedores de presença.
 *
 * A linha do e-mail nulo é a discriminante: ela é o que separa "a presença é a prova" de "o
 * provedor sempre autentica". Sem ela, um `emailVerificado()` que devolvesse `true` fixo para
 * GitHub e X ficaria verde.
 *
 * Para o GitHub, o e-mail nulo é exatamente o que o Socialite entrega quando a consulta dele a
 * `/user/emails` falha ou não acha nenhum `primary && verified` — então esta linha cobre, sem
 * nenhum stub de HTTP, os três casos que a versão anterior desta regra tentava cobrir com uma
 * tabela de decisão de resposta HTTP.
 */
it('decide pela presença do e-mail no GitHub e no X', function (
    ProvedorSocial $provedor,
    ?string $email,
    string $veredicto,
): void {
    ligarProvedor($provedor);

    $user   = usuario('ja.tem@example.com');
    $contas = User::query()->count();
    $canal  = espiarAutenticacao();

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor, ['email' => $email], ['email' => $email]));

    conferirVeredicto(
        $this,
        $this->get("/auth/{$provedor->value}/callback"),
        $veredicto,
        $user,
        $contas,
        $canal,
    );
})->with([
    'github com e-mail'  => [ProvedorSocial::Github, 'ja.tem@example.com', 'autentica'],
    'github sem e-mail'  => [ProvedorSocial::Github, null, 'recusa'],
    'x com e-mail'       => [ProvedorSocial::X, 'ja.tem@example.com', 'autentica'],
    'x sem e-mail'       => [ProvedorSocial::X, null, 'recusa'],
])->group('kit');

/**
 * CT-16 — um desmentido explícito no bruto VENCE a presença.
 *
 * O contrapeso do argumento de presença, e o que impede que ele envelheça mal: se um dia o
 * provedor passar a mandar `email_verified`, ele decide. Sem este caso, o ramo autenticaria
 * alguém com `email_verified => false` no payload — exatamente o que o ADR-05 recusou o Facebook
 * por permitir, e o argumento não pode valer num provedor e não valer no outro.
 *
 * Achado da revisão adversarial dos casos: a primeira versão do ramo era só `filled()`.
 */
it('recusa quando o bruto desmente a verificação, mesmo com e-mail presente', function (
    ProvedorSocial $provedor,
): void {
    ligarProvedor($provedor);

    $user   = usuario('ja.tem@example.com');
    $contas = User::query()->count();
    $canal  = espiarAutenticacao();

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor, ['email_verified' => false]));

    conferirVeredicto(
        $this,
        $this->get("/auth/{$provedor->value}/callback"),
        'recusa',
        $user,
        $contas,
        $canal,
    );

    $canal->shouldHaveReceived('warning', static fn (string $mensagem, array $contexto): bool => ($contexto['motivo'] ?? null) === 'email_nao_verificado');
})->with([
    'github' => [ProvedorSocial::Github],
    'x'      => [ProvedorSocial::X],
])->group('kit');

/**
 * CT-17 — a invariante de escopo do GitHub, enforçada estaticamente.
 *
 * Este é o caso que substituiu a chamada HTTP do kit, e ele guarda a única coisa que o argumento
 * de presença precisa: que `user:email` continue nos escopos efetivos do driver.
 *
 * Se alguém puser `'scopes' => ['read:user']` em `config/services.php`, o `scopes()` do Socialite
 * SOMA e `user:email` sobrevive — o caso segue verde, corretamente. O que ele reprova é um
 * `setScopes()` no kit, ou a remoção do default numa atualização do pacote: nos dois casos o
 * `GithubProvider` deixaria de sobrescrever o e-mail (`GithubProvider.php:47`) e passaria a
 * entregar o do perfil público, NÃO verificado — em silêncio, e sem nenhum outro caso vermelho.
 *
 * Lê os escopos pelo driver de verdade, e não pelo texto do arquivo: é o valor efetivo depois de
 * o `SocialiteManager` aplicar a config que importa.
 */
it('mantém user:email nos escopos efetivos do driver do GitHub', function (): void {
    ligarProvedor(ProvedorSocial::Github);

    $driver = Socialite::driver(ProvedorSocial::Github->value);

    expect($driver)->toBeInstanceOf(GithubProvider::class)
        ->and($driver->getScopes())->toContain('user:email');
})->group('kit');

/**
 * CT-19 — o kit não chama API de provedor nenhuma na volta.
 *
 * Antes desta correção o kit fazia UMA chamada, e este caso existia para provar que ele não a
 * fazia para os outros três provedores. Agora ele não faz nenhuma, para nenhum, e o caso ficou
 * mais forte e mais curto.
 *
 * `preventStrayRequests()` está no `beforeEach` do arquivo, então qualquer chamada pela facade
 * `Http` em QUALQUER caso deste arquivo estoura. Este caso acrescenta a asserção positiva de que
 * nada foi enviado, junto da autenticação — sem ela, `assertNothingSent()` ficaria verde com o
 * callback abortando antes da barreira.
 *
 * Registrado, porque não é intuitivo: isto NÃO cobre as chamadas do Socialite. Ele usa o Guzzle
 * dele (`getHttpClient()`), não a facade. Quem impede o Socialite de sair para a rede é o
 * `Socialite::fake()`.
 */
it('não faz chamada HTTP nenhuma na volta de nenhum provedor', function (
    ProvedorSocial $provedor,
): void {
    ligarProvedor($provedor);
    $user = usuario('ja.tem@example.com');

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor));

    $this->get("/auth/{$provedor->value}/callback")->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    Http::assertNothingSent();
})->with([
    'google'   => [ProvedorSocial::Google],
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R18 — sem conta, o callback cria conta se e somente se o registro aberto
|       está ligado
|--------------------------------------------------------------------------
| Regra inteira trazida pela revisão adversarial. Das quatro células da tabela
| `conta existe × registro aberto`, a rodada 1 tinha UMA — e a que faltava era
| a válida. Uma implementação que recuse `conta_inexistente_registro_fechado`
| SEMPRE, sem nunca consultar o interruptor, passava nos 44 cenários: GitHub,
| LinkedIn e X nunca criariam conta em produção e nada ficaria vermelho.
*/

/**
 * CT-45 — as quatro células de conta × registro aberto.
 *
 * As três linhas `aberto ligado` sem conta matam a recusa incondicional (M112) e a criação
 * presa ao ramo do Google (M114). As três linhas `DEFAULT de fábrica` matam o inverso — criar
 * conta sempre que não existe (M113) — e são a **única medida direta de RQ-07** desta wiki: elas
 * NÃO arranjam o interruptor de registro, exatamente como a linha "de fábrica" de CT-01.
 * Arranjar seria medir o `phpunit.xml`.
 *
 * A chave é `kit.registro.habilitado`, da feature de registro e aprovação. **Não** inventar
 * `kit.registro.aberto`: `config()->set()` aceita qualquer chave, e essa foi a chave imaginária
 * que deixou dois casos da wiki ancestral verdes enquanto a produção recusava o cadastro.
 *
 * O destino `meu-perfil` versus `/app` é o contrapeso das duas metades: sem ele, uma
 * implementação que mande TODO MUNDO para o perfil passa nas três primeiras linhas (M115). E o
 * total 1 nas duas últimas mata `updateOrCreate` criando uma segunda conta para quem já tem
 * (M118).
 */
it('cria conta na volta do provedor se e somente se o registro aberto está ligado', function (
    ProvedorSocial $provedor,
    string $email,
    bool $contaExiste,
    bool $registroAberto,
    string $veredicto,
    int $total,
    string $destino,
): void {
    // O papel único do registro aberto precisa existir: a conta nova passa pela mesma porta do formulário.
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    ligarProvedor($provedor);

    if ($contaExiste) {
        usuario($email);
    }

    if ($registroAberto) {
        config()->set('kit.registro.habilitado', true);
    }

    $canal = espiarAutenticacao();

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor, [], ['email' => $email]));

    $resposta = $this->get("/auth/{$provedor->value}/callback");

    conferirVeredicto($this, $resposta, $veredicto, User::query()->where('email', $email)->first(), $total, $canal);

    expect($resposta->headers->get('Location'))->toContain($destino);
})->with([
    'github sem conta, registro aberto ligado'   => [ProvedorSocial::Github, 'novo@example.com', false, true, 'autentica', 1, 'meu-perfil'],
    'linkedin sem conta, registro aberto ligado' => [ProvedorSocial::LinkedIn, 'novo@example.com', false, true, 'autentica', 1, 'meu-perfil'],
    'x sem conta, registro aberto ligado'        => [ProvedorSocial::X, 'novo@example.com', false, true, 'autentica', 1, 'meu-perfil'],
    'github sem conta, registro no DEFAULT'      => [ProvedorSocial::Github, 'novo@example.com', false, false, 'recusa', 0, '/app/login'],
    'linkedin sem conta, registro no DEFAULT'    => [ProvedorSocial::LinkedIn, 'novo@example.com', false, false, 'recusa', 0, '/app/login'],
    'x sem conta, registro no DEFAULT'           => [ProvedorSocial::X, 'novo@example.com', false, false, 'recusa', 0, '/app/login'],
    'github com conta, registro aberto ligado'   => [ProvedorSocial::Github, 'ja.tem@example.com', true, true, 'autentica', 1, '/app'],
    'github com conta, registro no DEFAULT'      => [ProvedorSocial::Github, 'ja.tem@example.com', true, false, 'autentica', 1, '/app'],
])->group('kit');

/**
 * CT-45b — a conta criada por login social nasce com o e-mail já verificado e o nome do provedor.
 *
 * Os valores concretos que separam "criou uma conta" de "criou a conta CERTA". Uma implementação
 * que grave o e-mail no campo do nome (M117), ou que deixe `email_verified_at` nulo (M116) — e
 * prenda a pessoa numa tela de "verifique seu e-mail" depois de o provedor JÁ ter verificado —
 * passa em CT-45 e morre aqui.
 *
 * A asserção de `email_verified_at` é a que exerce o par com a barreira de verificação: o
 * callback só chega aqui depois de o provedor PROVAR que o endereço está verificado, então
 * deixar a conta nova sem a marca é pedir a mesma prova duas vezes, e a segunda por e-mail.
 */
it('cria a conta do login social com o e-mail verificado e o nome do provedor', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    ligarProvedor(ProvedorSocial::Github);
    config()->set('kit.registro.habilitado', true);

    Socialite::fake(ProvedorSocial::Github->value, usuarioSocialFalso(
        ProvedorSocial::Github,
        [],
        ['email' => 'novo@example.com', 'name' => 'Pessoa do GitHub'],
    ));

    $this->get('/auth/github/callback')->assertStatus(302);

    $contas = User::query()->where('email', 'novo@example.com')->get();

    expect($contas)->toHaveCount(1)
        ->and($contas->first()->name)->toBe('Pessoa do GitHub')
        ->and($contas->first()->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($contas->first());
})->group('kit');

/*
|--------------------------------------------------------------------------
| R19 — o login autentica na conta certa
|--------------------------------------------------------------------------
*/

/**
 * CT-47 — duas contas parecidas, e só a certa é autenticada.
 *
 * Nenhum cenário da rodada 1 tinha mais de uma conta no banco, e com uma conta só
 * `where('email','like',$email)` é indistinguível de `where('email',$email)` — o `like` é
 * case-insensitive E trata `_` como curinga. Quem controla `ja_tem@example.com` num provedor
 * entra na conta `ja.tem@example.com` (M119, M120). **O e-mail é o identificador de recurso
 * desta feature**, e o checklist da rodada 1 dispensou IDOR dizendo que não havia id de recurso.
 *
 * As duas últimas linhas são o cruzamento com R7: elas provam que a normalização de caixa e de
 * espaços que CT-16 exige não foi comprada ao preço de trocar de conta — inclusive um
 * `str_replace(' ', '', ...)`, que juntaria e-mails distintos (M121).
 */
it('autentica exatamente a conta do e-mail do provedor, nunca a vizinha parecida', function (
    string $emailDoProvedor,
    string $emailEsperado,
): void {
    ligarProvedor(ProvedorSocial::Github);

    $comPonto      = usuario('ja.tem@example.com');
    $comSublinhado = usuario('ja_tem@example.com');

    $esperado = $emailEsperado === 'ja.tem@example.com' ? $comPonto : $comSublinhado;
    $outra    = $emailEsperado === 'ja.tem@example.com' ? $comSublinhado : $comPonto;

    Socialite::fake(ProvedorSocial::Github->value, usuarioSocialFalso(
        ProvedorSocial::Github,
        [],
        ['email' => $emailDoProvedor],
    ));

    $this->get('/auth/github/callback')->assertStatus(302);

    $this->assertAuthenticatedAs($esperado);

    expect(User::query()->count())->toBe(2)
        ->and($outra->fresh()->email)->toBe($outra->email)
        ->and($outra->fresh()->updated_at->equalTo($outra->updated_at))->toBeTrue();
})->with([
    'o sublinhado, que o curinga do like capturaria' => ['ja_tem@example.com', 'ja_tem@example.com'],
    'o ponto, o lado espelho do mesmo curinga'       => ['ja.tem@example.com', 'ja.tem@example.com'],
    'caixa alta sem trocar de conta'                 => ['JA_TEM@EXAMPLE.COM', 'ja_tem@example.com'],
    'espaços nas bordas sem trocar de conta'         => [' ja.tem@example.com', 'ja.tem@example.com'],
])->group('kit');

/**
 * CT-48 — o botão funciona a partir dos três painéis, e o destino é sempre o painel `/app`.
 *
 * **Este caso NÃO afirma o que o `04-casos-de-teste.md` derivou, e a divergência é uma decisão
 * registrada, não um descuido.** A tabela de R19 pedia "quem entra por um painel volta para
 * aquele painel" (M122, M123). A pergunta A-06/A-05 foi respondida pelo dono do kit com
 * **limitação aceita**: rastrear o painel de origem através da ida e volta do OAuth é feature
 * nova, não correção desta — o comportamento vem inalterado da feature do Google, em que
 * `recusar()` e o caminho de sucesso apontam os dois para o painel `/app`.
 *
 * Então o oráculo aqui é o comportamento REAL, e o caso serve a dois propósitos que continuam
 * valendo: o login pelo botão de `/admin/login` e de `/infra/login` **completa** (não é 500, não
 * é 403), e a limitação fica com um teste que a documenta — se alguém implementar o painel de
 * origem, é este caso que reprova e cobra a atualização da tabela de R19.
 *
 * O papel por painel não é enfeite: sem papel do painel, `canAccessPanel()` recusa e o caso
 * mediria o barramento em vez do destino.
 */
it('completa o login a partir dos três painéis, com destino no painel app', function (
    string $painel,
    string $papel,
): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    ligarProvedor(ProvedorSocial::Github);

    $user = usuarioDoKit($papel, 'ja.tem@example.com');

    Socialite::fake(ProvedorSocial::Github->value, usuarioSocialFalso(ProvedorSocial::Github));

    $this->get("/{$painel}/login")
        ->assertOk()
        ->assertSee('/auth/github/redirect', escape: false);

    $resposta = $this->from("/{$painel}/login")->get('/auth/github/callback');

    $resposta->assertStatus(302);

    $this->assertAuthenticatedAs($user);

    expect(parse_url((string) $resposta->headers->get('Location'), PHP_URL_PATH))->toBe('/app');
})->with([
    'app'   => ['app', 'panel_user'],
    'admin' => ['admin', 'admin'],
    'infra' => ['infra', 'infra'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R13 — o rastro no channel `autenticacao`
|--------------------------------------------------------------------------
*/

/**
 * CT-36 — o log de sucesso, provedor por provedor.
 *
 * As direções no mesmo caso porque todas falam do mesmo registro: que ele existe, o que ele diz e
 * o que ele NÃO diz. A do mascarado PRESENTE é o que separa "mascarou" de "não logou nada": sem
 * ela, remover todos os logs deixaria o caso verde.
 *
 * A ausência é asserida sobre a **mensagem E o contexto serializados**, e não só sobre a
 * mensagem: um segredo no array de contexto chega ao arquivo de log exatamente igual (M89). O
 * marcador de payload bruto — uma chave que só existe no `getRaw()` do duplo — é o que prova que
 * o kit não despeja o payload do provedor no log.
 *
 * O nome do provedor na mensagem mata a mensagem herdada do Google (M87), e o espião é do channel
 * `autenticacao` — o que mata o log que vai para o `stack` default (M95).
 *
 * Os valores são strings que não aparecem por acidente em lugar nenhum: usar `secret` ou
 * `password` produziria falso vermelho pelo próprio formulário de login.
 */
it('grava o log de sucesso nomeando o provedor, com o e-mail mascarado e nada de segredo', function (
    ProvedorSocial $provedor,
): void {
    ligarProvedor($provedor, ['client_secret' => 'SEGREDO-IRRECONHECIVEL-42']);
    usuario('ja.tem@example.com');

    $canal = espiarAutenticacao();

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor, ['bruto_do_provedor' => 'PAYLOAD-BRUTO-99']));

    $this->get("/auth/{$provedor->value}/callback")->assertStatus(302);

    $canal->shouldHaveReceived('info', static fn (string $mensagem): bool => str_contains($mensagem, 'Autenticado pelo provedor')
        && str_contains($mensagem, "provedor: {$provedor->value}")
        && str_contains($mensagem, Str::mask('ja.tem@example.com', '*', 3)));

    $vazou = static function (string $mensagem, array $contexto = []): bool {
        $registro = $mensagem.' '.json_encode($contexto, JSON_PARTIAL_OUTPUT_ON_ERROR);

        return str_contains($registro, 'ja.tem@example.com')
            || str_contains($registro, 'SEGREDO-IRRECONHECIVEL-42')
            || str_contains($registro, 'PAYLOAD-BRUTO-99');
    };

    $canal->shouldNotHaveReceived('info', $vazou);
    $canal->shouldNotHaveReceived('warning', $vazou);
})->with([
    'google'   => [ProvedorSocial::Google],
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/**
 * CT-37 — o motivo de CADA barreira que recusa.
 *
 * Uma barreira por linha e PROVEDORES DIFERENTES por linha, de propósito: percorrer as quatro
 * barreiras sempre com o mesmo provedor deixaria três provedores sem nenhuma prova de que a
 * recusa deles é logada, e um `if ($provedor === Google)` no bloco de log passaria.
 *
 * Os quatro motivos DIFERENTES matam o `motivo` genérico compartilhado (M91); o `warning` mata a
 * recusa logada em `info` (M92); e a ausência do `info` de sucesso mata o `return` esquecido
 * (M90).
 *
 * A linha do Google roda SEM `Socialite::fake()` — é o retorno sem `code` e sem `state`, e a
 * `InvalidStateException` é lançada antes de qualquer byte de rede (ver CT-04b).
 */
it('grava o motivo de cada barreira que recusa, sem log de sucesso', function (
    ProvedorSocial $provedor,
    string $barreira,
    string $motivo,
): void {
    ligarProvedor($provedor);

    $canal = espiarAutenticacao();

    Http::fake();
    Http::preventStrayRequests();

    match ($barreira) {
        'sem e-mail'         => Socialite::fake($provedor->value, usuarioSocialFalso($provedor, [], ['email' => null])),
        'nao verificado'     => Socialite::fake($provedor->value, usuarioSocialFalso($provedor, ['email_verified' => false])),
        'sem conta'          => Socialite::fake($provedor->value, usuarioSocialFalso($provedor, [], ['email' => 'de.fora@example.com'])),
        'sem code sem state' => null,
    };

    $this->get("/auth/{$provedor->value}/callback")->assertStatus(302);

    $canal->shouldHaveReceived('warning', static fn (string $mensagem, array $contexto): bool => ($contexto['motivo'] ?? null) === $motivo);

    $canal->shouldNotHaveReceived('info', static fn (string $mensagem): bool => str_contains($mensagem, 'Autenticado pelo provedor'));

    $this->assertGuest();
})->with([
    'github sem e-mail'              => [ProvedorSocial::Github, 'sem e-mail', 'email_ausente'],
    'linkedin sem e-mail verificado' => [ProvedorSocial::LinkedIn, 'nao verificado', 'email_nao_verificado'],
    'x sem conta e registro fechado' => [ProvedorSocial::X, 'sem conta', 'conta_inexistente_registro_fechado'],
    'google sem code e sem state'    => [ProvedorSocial::Google, 'sem code sem state', 'falha_no_provedor'],
])->group('kit');

/**
 * CT-38 — o log da ida nomeia o provedor.
 *
 * Sem ele, o log da rota de ida é a primeira linha que alguém apaga com "não interessa quem só
 * clicou" (M93) — e é justamente ela que diz, no incidente, quantas idas não voltaram.
 */
it('registra o redirecionamento da ida nomeando o provedor', function (
    ProvedorSocial $provedor,
): void {
    ligarProvedor($provedor);

    $canal = espiarAutenticacao();

    $this->get("/auth/{$provedor->value}/redirect")->assertStatus(302);

    $canal->shouldHaveReceived('info', static fn (string $mensagem): bool => str_contains($mensagem, 'Redirecionando para o provedor')
        && str_contains($mensagem, "provedor: {$provedor->value}"));
})->with([
    'google'   => [ProvedorSocial::Google],
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/**
 * CT-39 — abrir a tela de login não escreve NADA no channel de autenticação.
 *
 * A direção "não aconteceu onde não devia", e ela vem do `config/logging.php`: um `info` por
 * request de abertura de tela custou 1,1 MB/dia medido nesta instalação (M94).
 *
 * Os quatro botões são asseridos junto de propósito: a ausência total no channel sozinha ficaria
 * verde com a tela renderizando sem o render hook.
 */
it('não escreve nada no channel de autenticação ao abrir a tela de login', function (
    string $painel,
): void {
    foreach (ProvedorSocial::cases() as $provedor) {
        ligarProvedor($provedor);
    }

    $canal = espiarAutenticacao();

    $resposta = $this->get("/{$painel}/login")->assertOk();

    foreach (ProvedorSocial::cases() as $provedor) {
        $resposta->assertSee('Entrar com '.$provedor->rotulo());
    }

    $canal->shouldNotHaveReceived('info');
    $canal->shouldNotHaveReceived('warning');
    $canal->shouldNotHaveReceived('error');
})->with(['app', 'admin', 'infra'])->group('kit');

/*
|--------------------------------------------------------------------------
| R14 — o interruptor de cada provedor nasce false e falha fechado
|--------------------------------------------------------------------------
*/

/**
 * CT-40 — a coerção de cada interruptor, medida no PRÓPRIO config.
 *
 * Comportamental e não textual: `kitConfigCom()` (`tests/Pest.php`) relê o `config/kit.php` com a
 * variável forçada e restaura o ambiente no `finally`. A versão ancestral deste cenário nasceu
 * errada duas vezes — uma afirmando sobre o TEXTO do arquivo, outra testando a stdlib do PHP.
 *
 * As linhas `ausente` matam o default `true` (M97). As linhas `off`, `no` e `lixo` são as
 * discriminantes: elas distinguem `filter_var` de `(bool) env()`, que é falha ABERTA (M96). E o
 * caminho de config do LinkedIn ser `login.linkedin-openid.habilitado` com a chave de env
 * `KIT_SOCIALITE_LINKEDIN` é o que mata a divergência de nome entre os dois (M98, M99).
 *
 * O GitHub leva a partição completa e os outros três levam três linhas cada. A partição completa
 * por quatro chaves seriam 36 linhas provando quatro vezes a mesma coisa sobre `filter_var`; o
 * que precisa ser provado por chave é que AQUELA chave usa o mecanismo certo.
 *
 * **Lacuna declarada**: não há linha para valor com espaços nas bordas (`" true "`). Tentada —
 * `filter_var` com `FILTER_VALIDATE_BOOLEAN` apara espaços, então a implementação certa e a
 * errada concordam e o cenário não discriminaria.
 */
it('mantém o interruptor de cada provedor desligado, exceto em valor claramente verdadeiro', function (
    string $chave,
    string $caminho,
    ?string $valor,
    bool $esperado,
): void {
    expect(data_get(kitConfigCom($chave, $valor), $caminho))->toBe($esperado);
})->with([
    'google ausente'   => ['KIT_SOCIALITE_GOOGLE', 'login.google.habilitado', null, false],
    'google off'       => ['KIT_SOCIALITE_GOOGLE', 'login.google.habilitado', 'off', false],
    'google true'      => ['KIT_SOCIALITE_GOOGLE', 'login.google.habilitado', 'true', true],
    'github ausente'   => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', null, false],
    'github vazio'     => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', '', false],
    'github false'     => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', 'false', false],
    'github zero'      => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', '0', false],
    'github off'       => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', 'off', false],
    'github no'        => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', 'no', false],
    'github lixo'      => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', 'lixo', false],
    'github true'      => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', 'true', true],
    'github um'        => ['KIT_SOCIALITE_GITHUB', 'login.github.habilitado', '1', true],
    'linkedin ausente' => ['KIT_SOCIALITE_LINKEDIN', 'login.linkedin-openid.habilitado', null, false],
    'linkedin no'      => ['KIT_SOCIALITE_LINKEDIN', 'login.linkedin-openid.habilitado', 'no', false],
    'linkedin true'    => ['KIT_SOCIALITE_LINKEDIN', 'login.linkedin-openid.habilitado', 'true', true],
    'x ausente'        => ['KIT_SOCIALITE_X', 'login.x.habilitado', null, false],
    'x lixo'           => ['KIT_SOCIALITE_X', 'login.x.habilitado', 'lixo', false],
    'x um'             => ['KIT_SOCIALITE_X', 'login.x.habilitado', '1', true],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R15 — o enum é a única lista, em TODAS as sete superfícies derivadas
|--------------------------------------------------------------------------
| Escalada pela revisão adversarial: a rodada 1 conferia DUAS superfícies de
| pelo menos sete. Um `disponiveis()` com array escrito à mão, um `encrypted()`
| com quatro nomes escritos à mão, um mapa de Settings escrito à mão e um
| `rotulo()` sem caso novo ficariam todos verdes e derrubariam o quinto provedor
| em silêncio — que é literalmente o que RQ-02 proíbe.
*/

/**
 * CT-41 — as sete superfícies, caso por caso do enum.
 *
 * A última asserção — **rotas REGISTRADAS com o interruptor desligado** — separa duas causas que
 * produzem o mesmo 404: "404 porque o guarda recusou" e "404 porque a rota não existe". CT-03 não
 * distingue as duas, então M14 estava declarado morto por ele e não estava. `RouteCollection`
 * casa a URI sem passar pelo binding do enum nem pelo controller, e é isso que faz a distinção.
 *
 * O `rotulo()` diferente do valor cru é o que mata "provedor novo entra no enum sem caso no
 * `match` do rótulo" (M126) — no LinkedIn isso apareceria na tela como `Entrar com
 * linkedin-openid`. A lista de cifradas mata `encrypted()` escrito à mão (M125), e o `redirect`
 * de cada bloco mata o bloco copiado do vizinho (M127).
 *
 * `Feature` e não `Unit`: precisa do resolvedor de views, do roteador, do `config` e da tabela de
 * settings, e um `Unit` sem o `TestCase` da aplicação não os tem.
 */
it('deriva as sete superfícies de cada caso do enum, sem exceção', function (
    ProvedorSocial $provedor,
): void {
    expect(view()->exists('filament.auth.icones.'.$provedor->icone()))->toBeTrue();

    expect($provedor->rotulo())->not->toBe('')
        ->and($provedor->rotulo())->not->toBe($provedor->value);

    $credenciais = config('services.'.$provedor->value);

    expect($credenciais)->toBeArray()
        ->and($credenciais)->toHaveKeys(['client_id', 'client_secret', 'redirect'])
        ->and($credenciais['redirect'])->toBe("/auth/{$provedor->value}/callback");

    expect(config()->has("kit.login.{$provedor->value}.habilitado"))->toBeTrue();

    foreach (['habilitado', 'client_id', 'client_secret'] as $sufixo) {
        expect(SettingsProperty::query()
            ->where('group', SettingsDoKit::group())
            ->where('name', $provedor->propriedadeDeSettings($sufixo))
            ->exists())->toBeTrue("falta a propriedade de settings {$provedor->propriedadeDeSettings($sufixo)}");
    }

    expect(SettingsDoKit::encrypted())->toContain($provedor->propriedadeDeSettings('client_secret'));

    /*
     * As rotas REGISTRADAS, com o interruptor no default (desligado): `RouteCollection::match()`
     * casa a URI e para aí — sem binding de enum, sem middleware e sem controller —, então esta é
     * a única asserção do conjunto que distingue rota inexistente de guarda que recusou.
     */
    foreach (['redirect', 'callback'] as $sufixo) {
        expect(Route::getRoutes()->match(Request::create("/auth/{$provedor->value}/{$sufixo}"))->getName())
            ->toBe("auth.social.{$sufixo}");
    }
})->with([
    'google'   => [ProvedorSocial::Google],
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/**
 * CT-41b — nada sobrando: nenhuma superfície de provedor fora do enum.
 *
 * O oráculo de "nada sobrando" é o que dá valor ao de cima. Um ícone ÓRFÃO é o rastro de um
 * provedor removido pela metade, e a segunda asserção é onde M103 morre de verdade: o
 * `botao-google.blade.php` que o ADR-08 removeu **não fica** no diretório de ícones, então a
 * asserção da rodada 1 nunca o alcançava.
 *
 * `login_rodape` é a única propriedade `login_*` que não pertence a provedor: ela é o texto do
 * rodapé da tela de login, e entra na lista de exceções por nome para que qualquer OUTRA
 * propriedade nova com esse prefixo reprove aqui.
 */
it('não deixa nenhuma superfície de provedor fora do enum', function (): void {
    $doEnum = array_map(static fn (ProvedorSocial $provedor): string => $provedor->icone(), ProvedorSocial::cases());

    $noDisco = array_map(
        static fn (string $caminho): string => basename($caminho, '.blade.php'),
        glob(resource_path('views/filament/auth/icones/*.blade.php')) ?: [],
    );

    sort($doEnum);
    sort($noDisco);

    expect($noDisco)->toBe($doEnum);

    expect(glob(resource_path('views/filament/auth/botao-*.blade.php')) ?: [])->toBe([]);

    $propriedadesDoEnum = ['login_rodape'];

    foreach (ProvedorSocial::cases() as $provedor) {
        foreach (['habilitado', 'client_id', 'client_secret'] as $sufixo) {
            $propriedadesDoEnum[] = $provedor->propriedadeDeSettings($sufixo);
        }
    }

    $gravadas = SettingsProperty::query()
        ->where('group', SettingsDoKit::group())
        ->where('name', 'like', 'login\_%')
        ->pluck('name')
        ->all();

    // O anti-robô não é provedor social, mas compartilha o prefixo `login_*`.
    $excecoesDasPropriedades = [
        'login_anti_robo_habilitado',
        'login_anti_robo_provedor',
        'login_anti_robo_chave_do_site',
        'login_anti_robo_chave_secreta',
    ];

    expect(array_values(array_diff($gravadas, $propriedadesDoEnum, $excecoesDasPropriedades)))->toBe([]);

    $doConfig = array_keys(array_filter(
        (array) config('kit.login'),
        static fn (mixed $bloco): bool => is_array($bloco) && array_key_exists('habilitado', $bloco),
    ));

    // O bloco de configuração do anti-robô também tem o interruptor `habilitado`, mas não é provedor.
    expect(array_values(array_diff($doConfig, array_map(
        static fn (ProvedorSocial $provedor): string => $provedor->value,
        ProvedorSocial::cases(),
    ), ['anti_robo'])))->toBe([]);
})->group('kit');

/**
 * CT-46 — o default do `config/services.php`, sem NENHUM arranjo.
 *
 * Nenhum cenário da rodada 1 lia o `config/services.php` de fábrica: `ligarProvedor()` escreve as
 * três chaves em memória, e a linha "de fábrica" de CT-01 media só o interruptor. Um bloco
 * copiado do Google — os quatro `redirect` apontando para `/auth/google/callback` — passava nos 44
 * cenários e, em produção, mandaria o GitHub devolver a pessoa no callback do Google (M127).
 *
 * É o mesmo método que CT-40 aplica ao `config/kit.php`: **medir o default do arquivo**, não o
 * valor que o teste escreveu. A rodada 1 aplicou a um dos dois arquivos de config da feature.
 */
it('traz os quatro blocos de credencial de fábrica, com o redirect de cada provedor', function (
    ProvedorSocial $provedor,
): void {
    $bloco = require base_path('config/services.php');

    expect($bloco)->toHaveKey($provedor->value)
        ->and($bloco[$provedor->value])->toHaveKeys(['client_id', 'client_secret', 'redirect'])
        ->and($bloco[$provedor->value]['redirect'])->toBe("/auth/{$provedor->value}/callback");
})->with([
    'google'   => [ProvedorSocial::Google],
    'github'   => [ProvedorSocial::Github],
    'linkedin' => [ProvedorSocial::LinkedIn],
    'x'        => [ProvedorSocial::X],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R16 — cada provedor está declarado onde quem instala procura
|--------------------------------------------------------------------------
*/

/**
 * CT-42 — o termo declarado no arquivo.
 *
 * Asserção de PRESENÇA sobre o texto cru, então SEM o filtro de comentário: `.ai/rules/testes.md`
 * exige o filtro só na asserção de ausência (que é a última linha de CT-42b).
 *
 * As linhas do `README.en.md` matam "só o README pt foi atualizado" (M106), e a URI do LinkedIn
 * escrita inteira mata `/auth/linkedin/callback` na documentação (M105).
 */
it('declara as chaves e as URIs de callback nos arquivos que quem instala lê', function (
    string $arquivo,
    string $termo,
): void {
    expect(file_get_contents(base_path($arquivo)))->toContain($termo);
})->with([
    ['.env.example', 'KIT_SOCIALITE_GITHUB'],
    ['.env.example', 'KIT_SOCIALITE_LINKEDIN'],
    ['.env.example', 'KIT_SOCIALITE_X'],
    ['.env.example', 'GITHUB_CLIENT_ID'],
    ['.env.example', 'GITHUB_CLIENT_SECRET'],
    ['.env.example', 'LINKEDIN_CLIENT_ID'],
    ['.env.example', 'LINKEDIN_CLIENT_SECRET'],
    ['.env.example', 'X_CLIENT_ID'],
    ['.env.example', 'X_CLIENT_SECRET'],
    ['README.md', '/auth/github/callback'],
    ['README.md', '/auth/linkedin-openid/callback'],
    ['README.md', '/auth/x/callback'],
    ['README.md', 'KIT_SOCIALITE_GITHUB'],
    ['README.en.md', '/auth/github/callback'],
    ['README.en.md', '/auth/linkedin-openid/callback'],
    ['README.en.md', '/auth/x/callback'],
    ['README.en.md', 'KIT_SOCIALITE_GITHUB'],
])->group('kit');

/**
 * CT-42b — a recusa de cada provedor vem com o motivo, na MESMA seção.
 *
 * As linhas de `Discord` e `Facebook` saíram de CT-42 justamente porque, como simples presença da
 * palavra, eram decorativas: as duas são satisfeitas por qualquer menção — uma linha de roadmap,
 * ou o próprio texto do requisito colado no README. Certa e errada produziam o mesmo resultado
 * (M107). Exigir provedor + motivo na MESMA seção é o que RQ-10 realmente pede.
 *
 * **Divergência declarada quanto ao termo do motivo.** O `04-casos-de-teste.md` escreve
 * `e-mail verificado` (pt) e `verified email` (en), e nenhuma das duas expressões está literal na
 * seção; o termo que os dois READMEs usam para a mesma afirmação é `email_verified` — o nome do
 * claim que o Facebook não devolve. Ele é usado aqui porque é estritamente MAIS discriminante que
 * a expressão em prosa: uma linha de roadmap não carrega o nome do claim.
 *
 * A última asserção é de AUSÊNCIA, então ela — e só ela — vai sobre o texto filtrado, sem linhas
 * de comentário e de citação (`.ai/rules/testes.md`): os READMEs deste kit citam o que proíbem, e
 * o padrão já reprovou três vezes nesta base.
 */
it('explica a recusa de Facebook e Discord na mesma seção em que os nomeia', function (
    string $arquivo,
    string $provedor,
    string $motivo,
): void {
    $comOsDois = array_filter(
        secoesDoMarkdown($arquivo),
        static fn (string $secao): bool => str_contains($secao, $provedor) && str_contains($secao, $motivo),
    );

    expect($comOsDois)->not->toBeEmpty("nenhuma seção de {$arquivo} traz '{$provedor}' junto de '{$motivo}'");

    $semCitacao = implode("\n", array_filter(
        explode("\n", (string) file_get_contents(base_path($arquivo))),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '>')
            && ! str_starts_with(ltrim($linha), '<!--'),
    ));

    expect($semCitacao)->not->toContain('/auth/linkedin/callback');
})->with([
    'discord no README pt'  => ['README.md', 'Discord', 'socialiteproviders'],
    'facebook no README pt' => ['README.md', 'Facebook', 'email_verified'],
    'discord no README en'  => ['README.en.md', 'Discord', 'socialiteproviders'],
    'facebook no README en' => ['README.en.md', 'Facebook', 'email_verified'],
])->group('kit');

/**
 * A partial do ícone não vaza o próprio comentário para a tela.
 *
 * Medido no navegador do solicitante, numa instalação real (2026-08-26): o botão "Entrar com
 * Google" exibia o texto do comentário de cabeçalho da partial. As quatro partials abriam com
 * `{--` e fechavam com `--}` — uma chave só, que o Blade não reconhece como comentário e
 * imprime. Nenhum teste via, porque `assertSee('Entrar com Google')` continua verdadeiro com
 * o comentário ao lado. Este caso renderiza cada partial e exige o SVG sem o rastro.
 */
it('renderiza o icone do provedor sem vazar o comentario da partial', function (ProvedorSocial $provedor): void {
    $html = view('filament.auth.icones.'.$provedor->icone())->render();

    expect($html)
        ->toContain('data-provedor="'.$provedor->value.'"')
        ->not->toContain('{--')
        ->not->toContain('--}')
        ->not->toContain('Icone de marca');
})->with(function (): iterable {
    foreach (ProvedorSocial::cases() as $provedor) {
        yield $provedor->value => [$provedor];
    }
})->group('kit');
