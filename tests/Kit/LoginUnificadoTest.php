<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Pages\Auth\CadastroUnificado;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Pages\Auth\TelaLoginUnificada;
use App\Filament\Pages\Auth\TelaRecuperarSenhaUnificada;
use App\Models\Convite;
use App\Models\User;
use App\Support\DestinoAposLogin;
use App\Support\Paineis;
use App\Support\ProvedorSocial;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Página única de login (`/login`) para os três painéis — wiki `feat/login-unificado`.
 *
 * Os IDs são os do `04-casos-de-teste.md`. A chave é `kit.login.unificado` (`.env`
 * `KIT_LOGIN_UNIFICADO`, Settings `login_unificado`), desligada por default. Com ela ligada,
 * `/admin/login`, `/infra/login` e `/app/login` levam a `/login`; depois de entrar, um painel
 * acessível → direto nele, mais de um → `/login/painel` escolhe.
 *
 * A persona DISCRIMINANTE é `admin`: hoje ela NÃO entra pelo `/app/login` (sem papel no app), e a
 * página única roda no contexto do painel default. Se a decisão de acesso do Filament não fosse
 * sobrescrita, CT-08 ficaria vermelho na linha dela.
 *
 * `ligarLoginUnificado()`, `organizacaoComRegistro()` e `painelRegistradoEmTeste()` vivem em
 * `tests/Pest.php`: o arquivo de tenancy desta mesma feature os usa (`.ai/rules/testes.md`).
 */

/** Dois painéis, nenhum deles o default. */
function adminEInfra(string $email = 'dois@example.com'): User
{
    return usuarioDoKit('admin', $email)->assignRole('infra');
}

function personaDoKit(string $papel, string $email = 'pessoa@example.com'): User
{
    return match ($papel) {
        'admin+infra' => adminEInfra($email),
        // Acessa o /app por um papel cujo NOME não é o id do painel: separa "filtra por
        // canAccessPanel()" de "filtra por papel chamado como o painel".
        'admin+panel_user' => usuarioDoKit('admin', $email)->assignRole('panel_user'),
        'sem papel'        => usuario($email),
        default            => usuarioDoKit($papel, $email),
    };
}

/** Login pela página única, como o navegador faria: painel corrente é o default (`panel:app`). */
function entrarPelaPaginaUnica(string $email, string $senha = 'password'): Testable
{
    Filament::auth()->logout();
    Filament::setCurrentPanel('app');

    return Livewire::test(TelaLoginUnificada::class)
        ->fillForm(['email' => $email, 'password' => $senha])
        ->call('authenticate');
}

function urlDoPainel(string $id): string
{
    $painel = Filament::getPanel($id);

    return $painel->getUrl() ?? url($painel->getPath());
}

/*
|--------------------------------------------------------------------------
| R1 — a chave nasce desligada; só true/1 ligam; o toggle grava e governa
|--------------------------------------------------------------------------
*/

it('[CT-01] de fábrica a chave está desligada e o login continua por painel', function (): void {
    expect(kitConfigCom('KIT_LOGIN_UNIFICADO', null)['login']['unificado'])->toBeFalse();

    $this->get('/admin/login')->assertOk()->assertSeeLivewire(TelaLogin::class);
})->group('kit');

it('[CT-02] só true e 1 ligam a chave', function (string $valor, bool $ligado): void {
    expect(kitConfigCom('KIT_LOGIN_UNIFICADO', $valor)['login']['unificado'])->toBe($ligado);
})->with([
    'true — liga'           => ['true', true],
    '1 — liga'              => ['1', true],
    'false — não liga'      => ['false', false],
    '0 — não liga'          => ['0', false],
    'off — não liga'        => ['off', false],
    'vazio — falha fechado' => ['', false],
    'sim — irreconhecível'  => ['sim', false],
])->group('kit');

it('[CT-03] ligar e desligar pela tela de configurações governa o login do painel no request seguinte', function (): void {
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_unificado' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('login_unificado'))->toBeTrue();
    alinharConfiguracoesDoKit();
    Filament::auth()->logout();

    $this->get('/admin/login')->assertRedirect(route('login'));

    $this->actingAs(usuarioDoKit('admin', 'admin2@example.com'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_unificado' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('login_unificado'))->toBeFalse();
    alinharConfiguracoesDoKit();
    Filament::auth()->logout();

    $this->get('/admin/login')->assertOk()->assertSeeLivewire(TelaLogin::class);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — ligada, toda tela de login de painel leva a /login; desligada, responde; sem laço
|--------------------------------------------------------------------------
*/

it('[CT-04] cada tela de login de painel obedece à chave', function (bool $ligada, string $painel): void {
    ligarLoginUnificado($ligada);

    $resposta = $this->get("/{$painel}/login");

    $ligada
        ? $resposta->assertRedirect(route('login'))
        : $resposta->assertOk()->assertSeeLivewire(TelaLogin::class);
})->with([
    'ligada, admin'    => [true, 'admin'],
    'ligada, infra'    => [true, 'infra'],
    'ligada, app'      => [true, 'app'],
    'desligada, admin' => [false, 'admin'],
    'desligada, infra' => [false, 'infra'],
    'desligada, app'   => [false, 'app'],
])->group('kit');

it('[CT-05] os fluxos que terminam na tela de login de um painel terminam em /login', function (string $rota, string $primeiroDestino): void {
    ligarLoginUnificado();

    $this->get($rota)->assertRedirect($primeiroDestino);

    $this->followingRedirects()->get($rota)
        ->assertOk()
        ->assertSeeLivewire(TelaLoginUnificada::class);
})->with([
    'anônimo numa tela do /admin'   => ['/admin/users', fn (): string => Filament::getPanel('admin')->getLoginUrl()],
    'anônimo na raiz do /infra'     => ['/infra', fn (): string => Filament::getPanel('infra')->getLoginUrl()],
    // O registro passou a viver em /cadastro (CT-51); sem token e sem cadastro aberto ele recusa,
    // e a recusa é que termina na página única — que é o que este caso mede.
    'registro sem token de convite' => ['/app/register', fn (): string => route('cadastro')],
])->group('kit');

it('[CT-05] o logout de um painel encerra a sessão e termina em /login', function (): void {
    ligarLoginUnificado();
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $this->post('/admin/logout')->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    $this->assertGuest();

    $this->followingRedirects()->get('/admin/login')->assertSeeLivewire(TelaLoginUnificada::class);
})->group('kit');

it('[CT-06] @premissa /login com a chave desligada leva ao login do painel default, e não volta', function (): void {
    ligarLoginUnificado(false);

    $this->get('/login')->assertRedirect(Filament::getDefaultPanel()->getLoginUrl());
    $this->get(Filament::getDefaultPanel()->getLoginUrl())->assertOk()->assertSeeLivewire(TelaLogin::class);
})->group('kit');

it('[CT-07] /login com a chave ligada responde a própria tela, com o layout de autenticação', function (): void {
    ligarLoginUnificado();

    $this->get('/login')
        ->assertOk()
        ->assertSeeLivewire(TelaLoginUnificada::class)
        ->assertSee('fi-auth-layout', false)
        ->assertSee('data.email', false)
        ->assertSee('data.password', false);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — entra quem acessa ALGUM painel; os demais são recusados sem sessão
|--------------------------------------------------------------------------
*/

it('[CT-08] quem acessa ao menos um painel entra pela página única', function (string $papel): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel);

    entrarPelaPaginaUnica($pessoa->email)
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertAuthenticatedAs($pessoa);
})->with(['admin', 'infra', 'panel_user', 'admin+infra', 'master_global'])->group('kit');

it('[CT-09] quem não acessa painel nenhum é recusado com o erro genérico, sem sessão, e a recusa é logada', function (): void {
    ligarLoginUnificado();
    $canal  = espiarAutenticacao();
    $pessoa = personaDoKit('sem papel');

    entrarPelaPaginaUnica($pessoa->email)->assertHasErrors(['data.email']);

    $this->assertGuest();
    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_contains($mensagem, 'nenhum painel acessível') && $contexto['user_id'] === $pessoa->getKey())
        ->once();
})->group('kit');

it('[CT-10] senha errada é recusada com o erro genérico, mesmo para quem acessa três painéis', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('master_global');

    entrarPelaPaginaUnica($pessoa->email, 'senha-errada')->assertHasErrors(['data.email']);

    $this->assertGuest();
})->group('kit');

it('[CT-11] conta inativa com senha certa recebe a mesma explicação de hoje, sem sessão', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('admin');
    $pessoa->forceFill(['ativo' => false])->save();

    entrarPelaPaginaUnica($pessoa->email)->assertRedirectContains('conta-indisponivel');

    $this->assertGuest();
})->group('kit');

it('[CT-23] a página única mantém o bloqueio por tentativas do Filament', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('admin');
    Filament::auth()->logout();
    Filament::setCurrentPanel('app');

    $tela = Livewire::test(TelaLoginUnificada::class);

    foreach (range(1, 5) as $tentativa) {
        $tela->fillForm(['email' => $pessoa->email, 'password' => 'errada'])->call('authenticate')->assertHasErrors(['data.email']);
    }

    $tela->fillForm(['email' => $pessoa->email, 'password' => 'password'])->call('authenticate')->assertNotified();

    $this->assertGuest();
})->group('kit');

it('[CT-24] e-mail que não existe é recusado igual a senha errada', function (string $email): void {
    ligarLoginUnificado();

    entrarPelaPaginaUnica($email, 'qualquer')->assertHasErrors(['data.email']);

    $this->assertGuest();
})->with(['ninguem@example.com', 'admin@example.com.br'])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — destino: pretendida acessível → ela; um painel → ele; senão → escolha
|--------------------------------------------------------------------------
*/

it('[CT-12] quem tem um só painel vai direto para ele', function (string $papel, string $painel): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel);

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(urlDoPainel($painel));
})->with([
    'admin → /admin'      => ['admin', 'admin'],
    'infra → /infra'      => ['infra', 'infra'],
    'panel_user → /app'   => ['panel_user', 'app'],
])->group('kit');

it('[CT-13] quem tem mais de um painel vai para a escolha', function (string $papel): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel);

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(route('login.painel'));
})->with(['admin+infra', 'master_global'])->group('kit');

it('[CT-14] @premissa a URL pretendida de um painel acessível vence, com um ou mais painéis', function (string $papel, string $pretendida): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel);
    session()->put('url.intended', url($pretendida));

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(url($pretendida));
})->with([
    'admin, /admin/users'                          => ['admin', '/admin/users'],
    'admin+infra, /infra/health'                   => ['admin+infra', '/infra/health'],
    'admin+infra, a raiz /admin'                   => ['admin+infra', '/admin'],
    'master_global, /app/users (acessa sem papel)' => ['master_global', '/app/users'],
])->group('kit');

it('[CT-15] @premissa a URL pretendida de painel inacessível, prefixo enganoso ou host externo é descartada', function (string $papel, string $pretendida, string $destino): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel);
    session()->put('url.intended', $pretendida);

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect($destino);
})->with([
    'admin, /infra/health → /admin'              => ['admin', fn (): string => url('/infra/health'), fn (): string => urlDoPainel('admin')],
    'admin+infra, /app/users → escolha'          => ['admin+infra', fn (): string => url('/app/users'), fn (): string => route('login.painel')],
    'admin, /administracao/x → /admin'           => ['admin', fn (): string => url('/administracao/x'), fn (): string => urlDoPainel('admin')],
    'admin, host externo → /admin'               => ['admin', 'https://evil.test/admin', fn (): string => urlDoPainel('admin')],
    'admin, //evil.test/admin → /admin'          => ['admin', '//evil.test/admin', fn (): string => urlDoPainel('admin')],
    'admin, barra invertida → /admin'            => ['admin', '/\evil.test/admin', fn (): string => urlDoPainel('admin')],
    'admin, host com @ → /admin'                 => ['admin', 'https://evil.test\@localhost/admin', fn (): string => urlDoPainel('admin')],
    'admin, javascript: → /admin'                => ['admin', 'javascript://localhost/admin', fn (): string => urlDoPainel('admin')],
])->group('kit');

it('[CT-16] a URL pretendida é consumida no login e não vaza para o seguinte', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('admin');
    session()->put('url.intended', url('/admin/users'));

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(url('/admin/users'));
    expect(session()->has('url.intended'))->toBeFalse();

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(urlDoPainel('admin'));
})->group('kit');

it('[CT-25] com a chave desligada o destino continua sendo o painel do login usado', function (string $papel, string $painel): void {
    ligarLoginUnificado(false);
    $pessoa = personaDoKit($papel);
    Filament::auth()->logout();
    Filament::setCurrentPanel($painel);

    Livewire::test(TelaLogin::class)
        ->fillForm(['email' => $pessoa->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect(urlDoPainel($painel));
})->with([
    'admin+infra pelo /admin'   => ['admin+infra', 'admin'],
    'master_global pelo /infra' => ['master_global', 'infra'],
])->group('kit');

it('[CT-32] quem já está autenticado e abre /login vai para o destino dela', function (): void {
    ligarLoginUnificado();
    $this->actingAs(personaDoKit('admin'));

    $this->get('/login')->assertRedirect(urlDoPainel('admin'));
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — a escolha: só para quem entrou e tem mais de um painel; só os acessíveis
|--------------------------------------------------------------------------
*/

it('[CT-43] a tela mostra um cartão por painel acessível, com o rótulo do Panel Switch, e nenhum a mais', function (string $papel, array $presentes, array $ausentes): void {
    ligarLoginUnificado();
    config(['app.name' => 'Projeto Três']);
    $this->actingAs(personaDoKit($papel));

    $resposta = $this->get('/login/painel')
        ->assertOk()
        ->assertSee('kit-cards-page', false)
        ->assertSee('fi-simple-main-ctn', false)
        ->assertDontSee('id="fi-main-sidebar"', false)
        ->assertDontSee('Painel do negócio');

    $html = (string) $resposta->getContent();

    /*
     * A contagem é sobre o HREF, não sobre o rótulo: `config('app.name')` também vai no <title>
     * e contar o texto mediria o título junto. O rótulo é conferido no corpo, depois do </title>,
     * pelo mesmo motivo.
     */
    foreach ($presentes as $rotulo => $painel) {
        $href = route('login.painel.entrar', ['painel' => $painel]);
        expect(substr_count($html, $href))->toBe(1, "cartão {$painel}");
        expect(corpoDepoisDoTitulo($html))->toContain($rotulo);
    }

    foreach ($ausentes as $painel) {
        $resposta->assertDontSee(route('login.painel.entrar', ['painel' => $painel]), false);
    }
})->with([
    'admin+infra'      => ['admin+infra', ['Administração' => 'admin', 'Infraestrutura' => 'infra'], ['app']],
    'admin+panel_user' => ['admin+panel_user', ['Administração' => 'admin', 'Projeto Três' => 'app'], ['infra']],
    'master_global'    => ['master_global', ['Projeto Três' => 'app', 'Administração' => 'admin', 'Infraestrutura' => 'infra'], []],
])->group('kit');

it('[CT-45] um painel registrado depois da instalação aparece só para quem o acessa', function (string $papel, bool $presente): void {
    ligarLoginUnificado();
    painelRegistradoEmTeste('financeiro');
    $this->actingAs(personaDoKit($papel));

    $resposta = $this->get('/login/painel')->assertOk();
    $href     = route('login.painel.entrar', ['painel' => 'financeiro']);

    $presente
        ? $resposta->assertSee($href, false)->assertSee('Financeiro')
        : $resposta->assertDontSee($href, false)->assertDontSee('Financeiro');
})->with([
    'master_global acessa qualquer painel registrado' => ['master_global', true],
    'admin+infra não tem papel do financeiro'         => ['admin+infra', false],
])->group('kit');

it('[CT-65] o login de quem acessa um painel registrado depois da instalação leva à escolha, com os dois cartões', function (): void {
    ligarLoginUnificado();
    painelRegistradoEmTeste('financeiro');

    $pessoa = usuarioDoKit('admin', 'dois@example.com');
    $pessoa->assignRole(Role::create(['name' => 'financeiro', 'guard_name' => 'web', 'painel' => 'financeiro']));

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(route('login.painel'));

    $this->actingAs($pessoa->fresh())
        ->get('/login/painel')
        ->assertOk()
        ->assertSee(route('login.painel.entrar', ['painel' => 'admin']), false)
        ->assertSee(route('login.painel.entrar', ['painel' => 'financeiro']), false)
        ->assertDontSee(route('login.painel.entrar', ['painel' => 'app']), false);
})->group('kit');

it('[CT-44] o cartão de cada painel registrado tem o rótulo e o ícone do Panel Switch configurado', function (string $painel, ?string $rotulo, ?string $icone): void {
    config(['app.name' => 'Projeto Três']);
    painelRegistradoEmTeste('financeiro');

    $switch  = PanelSwitch::make();
    $cartao  = Paineis::cartoes()[$painel];
    $esperaR = $rotulo ?? $switch->getLabels()[$painel];
    $esperaI = $icone ?? $switch->getIcons()[$painel];

    expect($cartao->getLabel())->toBe($esperaR)
        ->and($cartao->getIcon())->toBe($esperaI);
})->with([
    'app mapeado'             => ['app', null, null],
    'admin mapeado'           => ['admin', null, null],
    'infra mapeado'           => ['infra', null, null],
    'financeiro fora do mapa' => ['financeiro', 'Financeiro', 'heroicon-o-square-2-stack'],
])->group('kit');

it('[CT-47] dentro do painel, o Panel Switch mostra os mesmos rótulos que a escolha mostrou', function (): void {
    ligarLoginUnificado();
    config(['app.name' => 'Projeto Três']);
    painelRegistradoEmTeste('financeiro');
    $this->actingAs(personaDoKit('master_global'));

    $corpo = corpoDepoisDoTitulo((string) $this->get('/admin')->assertOk()->getContent());

    expect($corpo)->toContain('Projeto Três')
        ->and($corpo)->toContain('Infraestrutura')
        ->and($corpo)->toContain('Financeiro')
        ->and($corpo)->not->toContain('Painel do negócio');
})->group('kit');

it('[CT-18] quem tem um só painel nunca vê a tela; quem não entrou também não', function (?string $papel, string $destino): void {
    ligarLoginUnificado();

    if ($papel !== null) {
        $this->actingAs(personaDoKit($papel));
    }

    $this->get('/login/painel')->assertRedirect($destino);
})->with([
    'um painel → o painel' => ['admin', fn (): string => urlDoPainel('admin')],
    'anônimo → /login'     => [null, fn (): string => route('login')],
])->group('kit');

it('[CT-26] com a chave desligada a escolha e o cartão não existem: vão para o painel default', function (): void {
    ligarLoginUnificado(false);
    $this->actingAs(personaDoKit('admin+infra'));

    $this->get('/login/painel')->assertRedirect(urlDoPainel('app'));
    $this->get(route('login.painel.entrar', ['painel' => 'infra']))->assertRedirect(urlDoPainel('app'));
})->group('kit');

it('[CT-19] quem entrou e não tem painel nenhum tem a sessão encerrada e volta ao login', function (): void {
    ligarLoginUnificado();
    $canal  = espiarAutenticacao();
    $pessoa = personaDoKit('sem papel');
    $this->actingAs($pessoa);

    $this->get('/login/painel')
        ->assertRedirect(route('login'))
        ->assertSessionHas('filament.notifications'); // o aviso sobrevive ao encerramento da sessão

    $this->assertGuest();
    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_contains($mensagem, 'sessão encerrada') && $contexto['user_id'] === $pessoa->getKey())
        ->once();
})->group('kit');

it('[CT-29] depois de escolher, os painéis abrem e não devolvem à escolha', function (): void {
    ligarLoginUnificado();
    $this->actingAs(personaDoKit('admin+infra'));

    $this->get('/admin')->assertOk();
    $this->get('/infra')->assertOk();
})->group('kit');

it('[CT-27] o segundo fator continua sendo exigido ao entrar no painel', function (): void {
    ligarLoginUnificado();

    // O 2FA do Breezy é por painel (`scopeToPanel`): a sessão nasce no painel corrente. Como em
    // `LoginSocialGoogleTest`, ela é criada no /admin, que é onde a barreira será exercida.
    noPainelBootado('admin');
    $pessoa = personaDoKit('admin+infra');
    $pessoa->enableTwoFactorAuthentication();
    $pessoa->breezySession->confirm();

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(route('login.painel'));

    $this->get('/admin')->assertRedirectContains('two-factor');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — login social no modo unificado
|--------------------------------------------------------------------------
*/

function voltaDoGoogle(string $email): TestResponse
{
    ligarProvedor(ProvedorSocial::Google);
    Socialite::fake(ProvedorSocial::Google->value, usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => $email]));

    return test()->get('/auth/google/callback');
}

it('[CT-20] a volta do provedor segue a regra de destino', function (string $papel, string $destino): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel, 'social@example.com');

    voltaDoGoogle($pessoa->email)->assertRedirect($destino);

    $this->assertAuthenticatedAs($pessoa);
})->with([
    'admin → /admin'          => ['admin', fn (): string => urlDoPainel('admin')],
    'admin+infra → escolha'   => ['admin+infra', fn (): string => route('login.painel')],
])->group('kit');

it('[CT-30] conta social sem painel nenhum termina deslogada, de volta ao login', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('sem papel', 'social@example.com');
    ligarProvedor(ProvedorSocial::Google);
    Socialite::fake(ProvedorSocial::Google->value, usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => $pessoa->email]));

    $this->followingRedirects()->get('/auth/google/callback')
        ->assertOk()
        ->assertSeeLivewire(TelaLoginUnificada::class);

    $this->assertGuest();
})->group('kit');

it('[CT-21] o botão social carrega o painel só no modo por painel', function (bool $ligada, string $tela, bool $comPainel): void {
    ligarLoginUnificado($ligada);
    ligarProvedor(ProvedorSocial::Google);

    $resposta = $this->get($tela)->assertOk()->assertSee('auth/google/redirect', false);

    $comPainel
        ? $resposta->assertSee('painel=admin', false)
        : $resposta->assertDontSee('painel=', false);
})->with([
    'ligada, /login'          => [true, '/login', false],
    'desligada, /admin/login' => [false, '/admin/login', true],
])->group('kit');

/*
|--------------------------------------------------------------------------
| RQ-07 — o convite válido continua abrindo o registro
|--------------------------------------------------------------------------
*/

it('[CT-51] a rota de registro do painel redireciona para /cadastro preservando a query', function (callable $arranjo, callable $conferir): void {
    ligarLoginUnificado();
    $query = $arranjo();

    $resposta = $this->get("/app/register{$query}");

    // 302, nunca 301: o redirect depende da chave, e um 301 cacheado pelo navegador viraria laço
    // no cliente no dia em que ela for desligada.
    expect($resposta->getStatusCode())->toBe(302);
    $resposta->assertRedirect(route('cadastro').$query);

    $conferir($this->followingRedirects()->get("/app/register{$query}"));
})->with([
    'sem convite, cadastro aberto desligado' => [
        fn (): string => '',
        fn (TestResponse $r) => $r->assertOk()->assertSeeLivewire(TelaLoginUnificada::class),
    ],
    'convite válido' => [
        fn (): string => '?token='.ofertaPara('novo@example.com')->enviar(),
        fn (TestResponse $r) => $r->assertOk()->assertSee('novo@example.com', false),
    ],
])->group('kit');

it('[CT-52] com a chave desligada /cadastro devolve à rota do painel, com a query, e não volta', function (bool $comConvite): void {
    ligarLoginUnificado(false);
    $query = $comConvite ? '?token='.ofertaPara('novo@example.com')->enviar() : '';

    $this->get("/cadastro{$query}")->assertRedirect(Filament::getPanel('app')->getRegistrationUrl().$query);
    $this->get("/app/register{$query}")->assertOk();
})->with([
    'sem query'     => [false],
    'com o convite' => [true],
])->group('kit');

it('[CT-53] /cadastro sem destino recusa como hoje: termina em /login e nenhuma conta nasce', function (callable $arranjo): void {
    ligarLoginUnificado();
    $query = $arranjo();
    $antes = User::query()->count();

    $this->get("/cadastro{$query}")->assertRedirect();

    $this->followingRedirects()->get("/cadastro{$query}")
        ->assertOk()
        ->assertSeeLivewire(TelaLoginUnificada::class);

    expect(User::query()->count())->toBe($antes);
    $this->assertGuest();
})->with([
    'sem token e sem cadastro aberto' => [fn (): string => ''],
    'token inexistente'               => [function (): string {
        config(['kit.registro.habilitado' => true]);

        return '?token=nao-existe';
    }],
    'token expirado' => [function (): string {
        config(['kit.registro.habilitado' => true]);
        $convite = ofertaPara('x@example.com');
        $token   = $convite->enviar();
        $convite->forceFill(['expira_em' => now()->subDay()])->save();

        return "?token={$token}";
    }],
    'token já aceito' => [function (): string {
        config(['kit.registro.habilitado' => true]);
        $convite = ofertaPara('x@example.com');
        $token   = $convite->enviar();
        $convite->forceFill(['aceito_em' => now()])->save();

        return "?token={$token}";
    }],
])->group('kit');

it('[CT-56] /cadastro com a chave ligada serve a própria tela, sem redirecionar', function (bool $comConvite): void {
    ligarLoginUnificado();
    config(['kit.registro.habilitado' => true]);
    $query = $comConvite ? '?token='.ofertaPara('novo@example.com')->enviar() : '';

    $resposta = $this->get("/cadastro{$query}")
        ->assertOk()
        ->assertSeeLivewire(CadastroUnificado::class)
        ->assertSee('fi-auth-layout', false);

    // O convite prevalece sobre o cadastro aberto: o e-mail vem travado no formulário.
    $comConvite
        ? $resposta->assertSee('novo@example.com', false)
        : $resposta->assertDontSee('novo@example.com', false);
})->with([
    'convite prevalece sobre o cadastro aberto' => [true],
    'cadastro aberto, sem convite'              => [false],
])->group('kit');

it('[CT-55] o formulário de /cadastro cria a conta com o papel e o vínculo da partição', function (bool $comConvite): void {
    ligarLoginUnificado();
    config(['kit.registro.habilitado' => true]);

    $email   = $comConvite ? 'convidado@example.com' : 'novo@example.com';
    $convite = $comConvite ? ofertaPara($email) : null;
    $token   = $convite instanceof Convite ? $convite->enviar() : null;
    $query   = $token === null ? [] : ['token' => $token];

    Filament::auth()->logout();
    Filament::setCurrentPanel('app');
    $this->get('/cadastro'.($query === [] ? '' : '?token='.$token))->assertOk();

    // O token chega por query string, nunca pelo construtor: é `mount()` que o lê, e é assim que
    // o link do e-mail de convite entra (molde de `registrarNaOrganizacao()`).
    Livewire::withQueryParams($query)
        ->test(CadastroUnificado::class)
        ->fillForm([
            'name'                 => 'Pessoa Nova',
            'email'                => $email,
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $criada = User::query()->where('email', $email)->sole();

    // Gravada E funcional: o registro autentica e devolve ao painel, não deixa a conta órfã.
    expect($criada->hasRole('panel_user'))->toBeTrue()
        ->and(Filament::auth()->id())->toBe($criada->getKey());

    if ($convite instanceof Convite) {
        expect($convite->fresh()->aceito_em)->not->toBeNull();
    }
})->with([
    'cadastro aberto' => [false],
    'convite'         => [true],
])->group('kit');

it('[CT-54] autenticado, /cadastro e /esqueci-minha-senha levam ao destino da pessoa, sem criar conta', function (callable $arranjo): void {
    ligarLoginUnificado();
    config(['kit.registro.habilitado' => true]);

    // `admin` é discriminante: não acessa o /app, que é para onde o `mount()` do vendor mandaria.
    $this->actingAs(personaDoKit('admin'));
    $antes            = User::query()->count();
    [$rota, $convite] = $arranjo();

    $this->get($rota)->assertRedirect(urlDoPainel('admin'));

    $this->assertAuthenticated();
    expect(User::query()->count())->toBe($antes);

    if ($convite instanceof Convite) {
        expect($convite->fresh()->aceito_em)->toBeNull();
    }
})->with([
    '/cadastro'             => [fn (): array => ['/cadastro', null]],
    '/esqueci-minha-senha'  => [fn (): array => ['/esqueci-minha-senha', null]],
    '/cadastro com convite' => [function (): array {
        $convite = ofertaPara('x@example.com');

        return ['/cadastro?token='.$convite->enviar(), $convite];
    }],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — o log de acesso recebe o painel em que a pessoa DE FATO entrou
|--------------------------------------------------------------------------
| O `authentication_log` nasce carimbado com o painel corrente (KitServiceProvider). Na página
| única o corrente é o default emprestado pelo middleware — não é o painel de entrada. Os
| widgets de "acessos por painel" dependem disto.
*/

function painelDoUltimoAcesso(User $pessoa): ?string
{
    return AuthenticationLog::query()
        ->where('authenticatable_type', $pessoa->getMorphClass())
        ->where('authenticatable_id', $pessoa->getKey())
        ->latest('login_at')
        ->first()
        ?->getAttribute('painel');
}

it('[CT-33] o login pela página única com um só painel carimba esse painel no log de acesso', function (string $papel, string $painel): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel);
    session()->put(DestinoAposLogin::SESSAO_EM_CURSO, true); // o que o mount() de /login grava

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(urlDoPainel($painel));

    expect(painelDoUltimoAcesso($pessoa))->toBe($painel)
        ->and(session()->has(DestinoAposLogin::SESSAO_EM_CURSO))->toBeFalse();
})->with([
    'admin → admin' => ['admin', 'admin'],
    'infra → infra' => ['infra', 'infra'],
])->group('kit');

it('[CT-34] com dois painéis o acesso fica sem painel até o clique no cartão, que carimba o escolhido', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('admin+infra');
    session()->put(DestinoAposLogin::SESSAO_EM_CURSO, true);

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(route('login.painel'));
    expect(painelDoUltimoAcesso($pessoa))->toBeNull();

    $this->get('/login/painel')->assertOk()->assertSee(route('login.painel.entrar', ['painel' => 'infra']), false);

    $this->get(route('login.painel.entrar', ['painel' => 'app']))->assertRedirect(route('login.painel'));
    expect(painelDoUltimoAcesso($pessoa))->toBeNull();

    $this->get(route('login.painel.entrar', ['painel' => 'infra']))->assertRedirect(urlDoPainel('infra'));
    expect(painelDoUltimoAcesso($pessoa))->toBe('infra');
})->group('kit');

it('[CT-35] a URL pretendida decide o painel carimbado', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('admin+infra');
    session()->put(DestinoAposLogin::SESSAO_EM_CURSO, true);
    session()->put('url.intended', url('/infra/health'));

    entrarPelaPaginaUnica($pessoa->email)->assertRedirect(url('/infra/health'));

    expect(painelDoUltimoAcesso($pessoa))->toBe('infra');
})->group('kit');

it('[CT-36] o login social pela página única carimba o painel de entrada', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('admin', 'social@example.com');
    session()->put(DestinoAposLogin::SESSAO_EM_CURSO, true);

    voltaDoGoogle($pessoa->email)->assertRedirect(urlDoPainel('admin'));

    expect(painelDoUltimoAcesso($pessoa))->toBe('admin');
})->group('kit');

it('[CT-37] a marca da página única não anula o carimbo de um login feito na tela do painel', function (): void {
    ligarLoginUnificado(false);
    $pessoa = personaDoKit('admin+infra');
    session()->put(DestinoAposLogin::SESSAO_EM_CURSO, true); // sobra de uma visita anterior a /login
    Filament::auth()->logout();
    Filament::setCurrentPanel('admin');

    Livewire::test(TelaLogin::class)
        ->fillForm(['email' => $pessoa->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertRedirect(urlDoPainel('admin'));

    expect(painelDoUltimoAcesso($pessoa))->toBe('admin');
})->group('kit');

/*
|--------------------------------------------------------------------------
| RQ-07 — lock screen e reset de senha continuam funcionando; e o layout de auth não vaza
|--------------------------------------------------------------------------
*/

it('[CT-38] o layout de autenticação da página única não veste as páginas comuns do painel', function (): void {
    ligarLoginUnificado();

    $this->get('/login')->assertOk()->assertSee('fi-auth-layout', false);

    $this->actingAs(personaDoKit('admin'));
    $this->get('/admin')->assertOk()->assertDontSee('fi-auth-layout', false);
})->group('kit');

it('[CT-39] a tela de bloqueio continua dentro do painel, e sair dela termina em /login', function (): void {
    ligarLoginUnificado();
    $this->actingAs(personaDoKit('admin'));

    $this->post('/admin/lock-session')->assertRedirect();
    $this->get('/admin')->assertRedirect(route('lockscreen.admin.page'));
    $this->get(route('lockscreen.admin.page'))->assertOk()->assertSee('fi-auth-layout', false);

    $this->post('/admin/logout')->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    $this->assertGuest();
    $this->followingRedirects()->get('/admin/login')->assertSeeLivewire(TelaLoginUnificada::class);
})->group('kit');

it('[CT-57] sem tenancy, o link de cadastro segue a chave e só existe com o registro ligado', function (bool $registro, bool $chave, string $tela, ?string $href): void {
    ligarLoginUnificado($chave);
    config(['kit.registro.habilitado' => $registro]);

    $html = (string) $this->get($tela)->assertOk()->getContent();

    if ($href === null) {
        // Ausência DUPLA: nem o texto do link, nem href algum para qualquer das duas URLs de
        // cadastro. Só "não contém /cadastro?org=" deixaria passar o link sem a organização.
        expect($html)->not->toContain(route('cadastro'))
            ->and($html)->not->toContain((string) Filament::getPanel('app')->getRegistrationUrl());

        return;
    }

    expect($html)->toContain($href);
})->with([
    'ligado e chave ligada'    => [true, true, '/login', fn (): string => route('cadastro')],
    'ligado e chave desligada' => [true, false, '/app/login', fn (): string => (string) Filament::getPanel('app')->getRegistrationUrl()],
    'desligado'                => [false, true, '/login', null],
])->group('kit');

it('[CT-59] a página única aponta para /esqueci-minha-senha, que serve a tela; as rotas de painel levam até ela', function (): void {
    ligarLoginUnificado();
    $unica = route('esqueci-minha-senha');

    $this->get('/login')
        ->assertOk()
        ->assertSee($unica, false)
        ->assertDontSee((string) Filament::getPanel('app')->getRequestPasswordResetUrl(), false);

    $this->get($unica)
        ->assertOk()
        ->assertSeeLivewire(TelaRecuperarSenhaUnificada::class)
        ->assertSee('fi-auth-layout', false);

    foreach (['app', 'admin', 'infra'] as $painel) {
        $resposta = $this->get((string) Filament::getPanel($painel)->getRequestPasswordResetUrl());

        // 302 pelo mesmo motivo de CT-51: o redirect depende da chave.
        expect($resposta->getStatusCode())->toBe(302, "painel {$painel}");
        $resposta->assertRedirect($unica);
    }
})->group('kit');

it('[CT-60] com a chave desligada /esqueci-minha-senha devolve à rota do painel default, e nada mais muda', function (): void {
    ligarLoginUnificado(false);
    $doPainel = (string) Filament::getPanel('app')->getRequestPasswordResetUrl();

    $this->get('/esqueci-minha-senha')->assertRedirect($doPainel);
    $this->get($doPainel)->assertOk();
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee((string) Filament::getPanel('admin')->getRequestPasswordResetUrl(), false);
})->group('kit');

it('[CT-61] o pedido em /esqueci-minha-senha envia o e-mail só à conta pedida, e o link do e-mail abre', function (string $pedido, bool $enviou): void {
    ligarLoginUnificado();
    NotificationFacade::fake();

    $admin = usuarioDoKit('admin', 'admin@example.com');
    $outra = usuario('outra@example.com');

    Filament::setCurrentPanel('app');
    Livewire::test(TelaRecuperarSenhaUnificada::class)
        ->fillForm(['email' => $pedido])
        ->call('request')
        ->assertHasNoFormErrors();

    if (! $enviou) {
        // Quem pede não descobre se o e-mail existe: mesma tela, e nada sai.
        NotificationFacade::assertNothingSent();

        return;
    }

    NotificationFacade::assertSentTo($admin, ResetPassword::class, function (ResetPassword $notificacao): bool {
        expect($notificacao->url)->toStartWith(url('/app/password-reset/reset'));

        // O link do e-mail continua por painel (premissa RQ-06) e PRECISA abrir com a chave ligada.
        $this->get($notificacao->url)->assertOk()->assertSee('fi-auth-layout', false);

        return true;
    });
    NotificationFacade::assertNotSentTo($outra, ResetPassword::class);
})->with([
    'conta existente'   => ['admin@example.com', true],
    'conta inexistente' => ['ninguem@example.com', false],
])->group('kit');

it('[CT-62] cada página única nova veste fi-auth-layout, e a página comum do painel aberta em seguida não', function (string $pagina): void {
    ligarLoginUnificado();
    config(['kit.registro.habilitado' => true]);

    $this->get($pagina)->assertOk()->assertSee('fi-auth-layout', false);

    $this->actingAs(personaDoKit('admin'))
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', false);
})->with([
    '/cadastro'            => ['/cadastro'],
    '/esqueci-minha-senha' => ['/esqueci-minha-senha'],
])->group('kit');

it('[CT-63] toda volta ao login a partir das telas externas termina em /login, em no máximo um salto', function (callable $arranjo): void {
    ligarLoginUnificado();
    $volta = $arranjo($this);

    if ($volta !== route('login')) {
        $this->get($volta)->assertRedirect(route('login'));
    }

    $this->followingRedirects()->get($volta)
        ->assertOk()
        ->assertSeeLivewire(TelaLoginUnificada::class);
})->with([
    'tela de cadastro por convite' => [function ($teste): string {
        $token = ofertaPara('novo@example.com')->enviar();
        $teste->get("/cadastro?token={$token}")->assertOk();

        return (string) Filament::getPanel('app')->getLoginUrl();
    }],
    'tela de recuperação de senha' => [function ($teste): string {
        $teste->get('/esqueci-minha-senha')->assertOk();

        return (string) Filament::getPanel('app')->getLoginUrl();
    }],
    'tela de conta indisponível' => [function ($teste): string {
        $inativa = personaDoKit('admin', 'inativa@example.com');
        $inativa->forceFill(['ativo' => false])->save();

        entrarPelaPaginaUnica($inativa->email);

        $html = (string) $teste->followingRedirects()->get(route('auth.conta-indisponivel'))->getContent();
        expect($html)->toContain('Voltar ao login');

        return route('login');
    }],
])->group('kit');

it('[CT-64] a verificação de e-mail continua dentro do painel com a chave ligada', function (): void {
    ligarLoginUnificado();
    config(['kit.registro.verificar_email' => true]);

    $pessoa = usuarioDoKit('panel_user', 'sem-verificar@example.com');
    $pessoa->forceFill(['email_verified_at' => null])->save();

    $this->actingAs($pessoa)->get('/app')->assertRedirect();

    $this->actingAs($pessoa)
        ->get('/app/email-verification/prompt')
        ->assertOk()
        ->assertSee('fi-auth-layout', false);
})->group('kit');

/*
|--------------------------------------------------------------------------
| Auditoria Blueprint — provedor restrito por painel, constraint da rota
|--------------------------------------------------------------------------
*/

it('[CT-41] o login social pela página única respeita os painéis autorizados do provedor', function (string $papel, array $paineisDoGoogle, string $destino): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit($papel, 'social@example.com');
    config()->set('kit.login.google.paineis', $paineisDoGoogle);

    voltaDoGoogle($pessoa->email)->assertRedirect($destino);
})->with([
    'admin+infra, Google só no infra → /infra'          => ['admin+infra', ['infra'], fn (): string => urlDoPainel('infra')],
    'admin+infra, Google em qualquer painel → escolha'  => ['admin+infra', [], fn (): string => route('login.painel')],
    'admin, Google só no infra → escolha (que encerra)' => ['admin', ['infra'], fn (): string => route('login.painel')],
])->group('kit');

it('[CT-41] quem entra por provedor não autorizado em nenhum painel acessível termina deslogado', function (): void {
    ligarLoginUnificado();
    $pessoa = personaDoKit('admin', 'social@example.com');
    config()->set('kit.login.google.paineis', ['infra']);
    ligarProvedor(ProvedorSocial::Google);
    Socialite::fake(ProvedorSocial::Google->value, usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-1', 'email' => $pessoa->email]));

    $this->followingRedirects()->get('/auth/google/callback')->assertOk()->assertSeeLivewire(TelaLoginUnificada::class);

    $this->assertGuest();
})->group('kit');

it('[CT-42] o cartão só aceita id de painel bem formado', function (string $painel): void {
    ligarLoginUnificado();
    $this->actingAs(personaDoKit('admin+infra'));

    $this->get('/login/painel/'.$painel)->assertNotFound();
})->with(['ADMIN%0Ainjetado', 'a%20b', str_repeat('x', 40)])->group('kit');
