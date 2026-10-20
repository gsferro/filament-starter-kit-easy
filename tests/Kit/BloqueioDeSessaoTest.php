<?php

use App\Filament\Pages\Auth\TelaBloqueio;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Pages\Auth\TelaLoginUnificada;
use App\Models\User;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as UsuarioDoProvedor;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Bloqueio de sessão (marjose123/filament-lockscreen) vestido com o layout de login do kit
 * (caresome/filament-auth-designer). O que estes casos fixam: a trava e o destravamento por
 * senha, o auto-lock por inatividade, e as duas armadilhas do acoplamento entre os dois
 * pacotes — o layout que não pode vazar para as outras páginas, e a tela aberta destravada
 * que não pode virar 500.
 *
 * `Filament::setCurrentPanel()` é obrigatório nos casos de componente: a página do pacote lê
 * `filament()->getCurrentPanel()->auth()` sem cair no painel default.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function usuarioMaster(): User
{
    $user = User::create([
        'name'     => 'Teste',
        'email'    => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    $user->assignRole('master_global');

    return $user;
}

/**
 * O item de menu nasce num `bootUsing()`, então o painel precisa estar BOOTADO — em request
 * real quem faz isso é o middleware do Filament. E `actingAs` antes não é cerimônia: o item
 * de perfil do Breezy resolve o nome do usuário nesse mesmo boot e estoura com sessão
 * anônima.
 */
it('registra o plugin e o item de menu nos três painéis', function (string $painel): void {
    $this->actingAs(usuarioMaster());

    $panel = Filament::getPanel($painel);
    Filament::setCurrentPanel($painel);
    Filament::bootCurrentPanel();

    expect($panel->hasPlugin('filament-lockscreen'))->toBeTrue();

    $item = collect($panel->getUserMenuItems())->first(
        fn ($acao): bool => $acao->getName() === 'lockSession',
    );

    // sort(-1) é o que põe o item no grupo do "Meu perfil", e não depois do
    // alternador de tema — ver TelaBloqueio::itemDeMenu().
    expect($item)->not->toBeNull()
        ->and($item->getSort())->toBe(-1);
})->with(['app', 'admin', 'infra']);

it('trava a sessão pela rota, sem deslogar', function (): void {
    $user = usuarioMaster();
    $this->actingAs($user);

    $this->post('/admin/lock-session')->assertRedirect();

    expect(session('lockscreen'))->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

it('redireciona o painel para a tela de bloqueio enquanto travado', function (string $painel): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $this->get("/{$painel}")->assertRedirect(route("lockscreen.{$painel}.page"));
})->with(['app', 'admin', 'infra']);

/**
 * O pacote entrega a tela como `SimplePage`; sem a trait do auth-designer na
 * TelaBloqueio ela sairia com `fi-simple-layout` e perderia a mídia da marca.
 */
it('renderiza a tela de bloqueio com o layout do login, não com o layout simples', function (string $painel): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $this->get(route("lockscreen.{$painel}.page"))
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        ->assertDontSee('fi-simple-layout', escape: false);
})->with(['app', 'admin', 'infra']);

/**
 * O contrapeso do caso acima: a trait atribui `static::$layout`, e sem a redeclaração da
 * propriedade na TelaBloqueio a atribuição cai no estático herdado de `Filament\Pages\Page`
 * e passa a valer para o processo inteiro.
 */
it('não vaza o layout de login para as outras páginas do painel', function (): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $this->get(route('lockscreen.admin.page'))->assertOk()->assertSee('fi-auth-layout', escape: false);

    session(['lockscreen' => false]);

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', escape: false);
});

it('mantém travado quando a senha está errada', function (): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);
    Filament::setCurrentPanel('admin');

    Livewire::test(TelaBloqueio::class)
        ->set('data.password', 'senha-errada')
        ->call('authenticate')
        ->assertHasErrors('data.password');

    expect(session('lockscreen'))->toBeTrue();
});

it('destrava com a senha correta e mantém a sessão', function (): void {
    $user = usuarioMaster();
    $this->actingAs($user);
    session(['lockscreen' => true]);
    Filament::setCurrentPanel('admin');

    Livewire::test(TelaBloqueio::class)
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertHasNoErrors();

    expect(session('lockscreen'))->toBeFalse()
        ->and(auth()->id())->toBe($user->id);
});

it('tranca sozinha depois do tempo de inatividade', function (): void {
    $this->actingAs(usuarioMaster());

    session(['session_last_activity' => time() - (config('lockscreen.idle_timeout') + 60)]);

    $this->get('/admin')->assertRedirect(route('lockscreen.admin.page'));

    expect(session('lockscreen'))->toBeTrue();
});

it('manda visitante não autenticado para o login', function (): void {
    session(['lockscreen' => true]);

    $this->get(route('lockscreen.admin.page'))->assertRedirect();
});

/**
 * O `mount()` do pacote chama `redirect()` sem `return`: num processo onde o Livewire já
 * instalou o Redirector dele, o objeto chega onde o Laravel espera um código HTTP e o
 * request morre em `ErrorException ... could not be converted to int`. O primeiro GET existe
 * para instalar esse Redirector — sem ele o caso passa mesmo com o bug.
 */
it('redireciona em vez de estourar quando a tela é aberta sem a sessão travada', function (): void {
    $this->actingAs(usuarioMaster());

    $this->get('/admin')->assertOk();

    $this->get(route('lockscreen.admin.page'))->assertRedirect(url('admin'));
});

/**
 * A tela de bloqueio oferece os mesmos botões sociais do login — nos três painéis.
 *
 * Medido na validação real dos provedores (2026-08-26): quem entrou pelo Google e escolheu
 * viver sem senha local bloqueava a sessão e ficava preso — a tela só pedia a senha. Sem
 * provedor disponível a view renderiza vazia, então o caso liga o Google antes.
 */
it('oferece os botões de login social na tela de bloqueio', function (string $painel): void {
    ligarProvedor(ProvedorSocial::Google);
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $this->get(route("lockscreen.{$painel}.page"))
        ->assertOk()
        ->assertSee('Entrar com Google')
        ->assertSee(route('auth.social.redirect', ProvedorSocial::Google), escape: false);
})->with(['app', 'admin', 'infra']);

/**
 * E a volta do provedor destrava: sem isto o botão seria um laço — o callback loga e o
 * middleware do lockscreen devolve a pessoa à tela de bloqueio.
 */
it('destrava a sessão bloqueada na volta do provedor', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    $user = usuarioMaster();
    $this->actingAs($user);
    session(['lockscreen' => true]);

    Socialite::fake('google', UsuarioDoProvedor::fake([
        'id'             => 'google-sub-lock',
        'name'           => $user->name,
        'email'          => $user->email,
        'email_verified' => true,
    ]));

    $this->get('/auth/google/callback')->assertRedirect();

    expect(session('lockscreen'))->toBeFalse();

    $this->get('/admin')->assertOk();
});

/**
 * CT-10 (wiki `phpstan-nivel-8`) — o visitante da tela de bloqueio sempre sai para um destino
 * alcançável do MESMO painel, nunca para `''`, para `/` ou para a própria tela (laço).
 *
 * A linha `admin` fortalece "manda visitante não autenticado para o login" (`:152-156`), que só
 * afirmava `assertRedirect()` sem destino — aqui o destino é exato.
 *
 * A linha `financeiro` prova a premissa P-02: painel sem `->login()` (`painelRegistradoEmTeste()`)
 * cai na raiz do PRÓPRIO painel — nunca em `TelaBloqueio::mount()` deixando `getLoginUrl() ?? ''`
 * (laço) ou `?? url('/')` (destino inventado, fora do painel).
 *
 * `TelaBloqueio::mount()` sai por `HttpResponseException`, e não por `redirect()` solto —
 * `Illuminate\Routing\Route::run()` a captura e devolve `$e->getResponse()` direto
 * (`vendor/laravel/framework/src/Illuminate/Routing/Route.php:220-221`), ANTES de o harness de
 * teste do Livewire (que monta o componente atrás de uma rota real, ver
 * `vendor/livewire/livewire/src/Features/SupportTesting/InitialRender.php:29-34`) sequer montar o
 * `ComponentState` — por isso o resultado chega como uma resposta HTTP normal, sem precisar de
 * `try`/`catch`.
 */
it('[CT-10] o visitante da tela de bloqueio é levado ao login do painel, ou à raiz dele quando o painel não tem login', function (string $painel, string $destino): void {
    if ($painel === 'financeiro') {
        painelRegistradoEmTeste('financeiro');
    }

    Filament::setCurrentPanel($painel);
    session(['lockscreen' => true]);

    Livewire::test(TelaBloqueio::class)->assertRedirect($destino);
})->with([
    'admin, com login (fato)'               => ['admin', '/admin/login'],
    'financeiro, sem login (premissa P-02)' => ['financeiro', '/financeiro'],
]);

/*
|--------------------------------------------------------------------------
| Desbloqueio volta para a página em que a pessoa estava
|--------------------------------------------------------------------------
| IDs de CT em `wikis/specs/feat/desbloqueio-volta-a-pagina/…/04-casos-de-teste.md`. A situação de
| partida é construída por transições reais (`travadaPorInatividadeAoPedir()` de `tests/Pest.php`,
| GET de verdade, bloqueio manual por POST); a marca de trava só é forçada onde o cenário É a
| tela aberta direto. CT-02, CT-21 e CT-30 ficam em `tests/Tenancy/BloqueioComRetornoTenancyTest.php`.
| CT-26 é lacuna declarada (nenhuma rota POST do painel admin atravessa a barreira).
*/

/** O endereço do `Location` de uma resposta HTTP, decodificado. */
function destinoDaResposta(TestResponse $resposta): string
{
    return urldecode((string) $resposta->headers->get('Location'));
}

/** A conta B: outra pessoa, com acesso ao painel admin e a senha `password`. */
function contaComAcessoAoAdmin(): User
{
    return usuarioMaster();
}

/** O provedor devolve a conta ao callback (Socialite fake, e-mail verificado como o driver real). */
function provedorDevolve(User $conta, ProvedorSocial $provedor = ProvedorSocial::Google): void
{
    ligarProvedor($provedor);

    Socialite::fake($provedor->value, usuarioSocialFalso($provedor, [], [
        'id'    => "{$provedor->value}-sub-{$conta->id}",
        'name'  => $conta->name,
        'email' => $conta->email,
    ]));
}

/**
 * A tela de bloqueio abre com 200, sem redirecionar.
 *
 * @param  array<string, string>  $cabecalhos
 */
function telaDeBloqueioAbre(string $painel, array $cabecalhos = []): void
{
    test()->withHeaders($cabecalhos)->get(route("lockscreen.{$painel}.page"))->assertOk();
    test()->flushHeaders();
}

it('[CT-01] a sessão travada por inatividade volta à página pedida, com a query, e não à última visitada', function (string $painel, string $pedido, string $anterior): void {
    expect(config('lockscreen.idle_timeout'))->toBeNumeric()->toBeGreaterThan(0);

    $user = usuarioMaster();
    $this->actingAs($user);
    $this->get($anterior)->assertOk();

    travadaPorInatividadeAoPedir($pedido, ['Referer' => url($anterior)]);

    $componente = desbloquearPorSenha($painel);

    expect(destinoDoComponente($componente))->toBe(enderecoDe($pedido))
        ->and(session('lockscreen'))->toBeFalse()
        ->and(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
})->with([
    'painel admin'                           => ['admin', '/admin/users?q=ana', '/admin/shield/roles'],
    'painel infra'                           => ['infra', '/infra/execucoes-ia?q=ana', '/infra'],
    'painel app (sem tenancy)'               => ['app', '/app/users?q=ana', '/app'],
    'query de array dos filtros do Filament' => ['admin', '/admin/users?tableFilters[status][value]=ativo&tableSearch=ana', '/admin/shield/roles'],
]);

it('[CT-03] um Referer de fora do sistema nunca vira destino', function (): void {
    $this->actingAs(usuarioMaster());
    $this->get('/admin/shield/roles')->assertOk();

    travadaPorInatividadeAoPedir('/admin/users', ['Referer' => 'https://externo.example/phishing']);

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toContain('externo.example');
});

it('[CT-04] a pessoa já travada que abre outra página volta a essa última página', function (string $partida): void {
    $user = usuarioMaster();
    $this->actingAs($user);

    if ($partida === 'S2') {
        session(['lockscreen' => true]);
    } else {
        travadaPorInatividadeAoPedir('/admin/users');
    }

    $this->get('/admin/shield/roles')->assertRedirect(route('lockscreen.admin.page'));

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin/shield/roles'))
        ->and($destino)->not->toContain('screen/lock')
        ->and($destino)->not->toBe(enderecoDe('/admin/users'));
})->with([
    'S2: sem página guardada (tela aberta direto)'      => ['S2'],
    'S1: com /admin/users pedida antes por inatividade' => ['S1'],
]);

it('[CT-05] abrir a tela de bloqueio depois do redirecionamento não troca o destino', function (string $tela): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    $this->get($tela)->assertOk();

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toContain('screen/lock');
})->with([
    'painel da trava' => ['/admin/screen/lock'],
    'outro painel'    => ['/infra/screen/lock'],
]);

it('[CT-06] o bloqueio manual a partir de uma página, com query, volta a essa página inteira', function (): void {
    $this->actingAs(usuarioMaster());
    $this->get('/admin/users?q=ana')->assertOk();

    $this->from(url('/admin/users?q=ana'))->post('/admin/lock-session')->assertRedirect();

    expect(session('lockscreen'))->toBeTrue();

    $componente = desbloquearPorSenha('admin');

    expect(destinoDoComponente($componente))->toBe(enderecoDe('/admin/users?q=ana'))
        ->and(session('lockscreen'))->toBeFalse();
});

/**
 * Envia a ação `authenticate` pela rota HTTP de update do Livewire, com os cabeçalhos e a query
 * pedidos. O `Livewire::test` monta o componente com `withHeaders`/`withQueryParams`, mas as
 * chamadas seguintes (`SubsequentRender`) usam um `RequestBroker` novo, só com `X-Livewire`: o
 * Referer e a query somem. Aqui eles chegam, e um listener de `RouteMatched` registra o que o
 * servidor viu (o controle de que não é vácuo).
 *
 * @param  array<string, string>  $cabecalhos
 * @param  array<string, string>  $consulta
 * @return array{destino: string, vistos: list<array{referer: ?string, query: array<string, mixed>}>}
 */
function authenticatePelaRotaDeUpdate(Testable $componente, array $cabecalhos = [], array $consulta = [], string $senha = 'password'): array
{
    $vistos = [];

    Event::listen(RouteMatched::class, function (RouteMatched $evento) use (&$vistos): void {
        $vistos[] = [
            'referer' => $evento->request->headers->get('referer'),
            'query'   => $evento->request->query(),
        ];
    });

    $uri = app('livewire')->getUpdateUri().($consulta === [] ? '' : '?'.http_build_query($consulta));

    $resposta = test()->withHeaders($cabecalhos + ['X-Livewire' => 'true'])->postJson($uri, [
        'components' => [[
            'snapshot' => json_encode($componente->snapshot),
            'calls'    => [['path' => '', 'method' => 'authenticate', 'params' => []]],
            'updates'  => ['data.password' => $senha],
        ]],
    ]);
    test()->flushHeaders();

    $resposta->assertOk();

    return [
        'destino' => urldecode((string) $resposta->json('components.0.effects.redirect')),
        'vistos'  => $vistos,
    ];
}

it('[CT-25] query na própria tela de bloqueio não escolhe o destino', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    $consulta = ['redirect' => 'https://externo.example/x', 'intended' => '/infra'];

    $this->get('/admin/screen/lock?'.http_build_query($consulta))->assertOk();

    Filament::setCurrentPanel('admin');

    $componente = Livewire::withQueryParams($consulta)->test(TelaBloqueio::class);

    ['destino' => $destino, 'vistos' => $vistos] = authenticatePelaRotaDeUpdate($componente, [], $consulta);

    // Controle: a query chegou ao servidor na requisição da AÇÃO (o `Livewire::test` a descarta).
    expect($vistos)->not->toBeEmpty()
        ->and($vistos[0]['query'])->toMatchArray($consulta);

    expect($destino)->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toContain('externo.example');
});

it('[CT-07] requisição que não é GET navegável não vira destino', function (string $requisicao): void {
    $this->actingAs(usuarioMaster());
    $this->get('/admin/shield/roles')->assertOk();

    $cabecalhos = ['Referer' => url('/admin/shield/roles')];

    if ($requisicao === 'json') {
        session(['session_last_activity' => time() - (config('lockscreen.idle_timeout') + 60)]);

        $this->withHeaders($cabecalhos + ['Accept' => 'application/json'])->get('/admin/users');
        $this->flushHeaders();
    } else {
        // O pacote deixa toda chamada Livewire passar sem travar: a linha parte da sessão JÁ
        // travada, sem pedido de página, e a chamada X-Livewire chega depois, com o mesmo Referer.
        session(['lockscreen' => true]);

        $this->withHeaders($cabecalhos + ['X-Livewire' => 'true'])->get('/admin/users');
        $this->flushHeaders();
    }

    expect(session('lockscreen'))->toBeTrue();

    telaDeBloqueioAbre('admin');

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toBe(enderecoDe('/admin/shield/roles'));
})->with([
    'GET que espera JSON'            => ['json'],
    'chamada de componente Livewire' => ['livewire'],
]);

it('[CT-08] uma chamada de componente com a sessão já travada não troca a página guardada', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    $this->withHeaders(['X-Livewire' => 'true', 'Referer' => url('/admin/shield/roles')])->get('/admin/shield/roles');
    $this->flushHeaders();

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toBe(enderecoDe('/admin/shield/roles'));
});

it('[CT-27] um GET que espera JSON com a sessão já travada não troca a página guardada, com ou sem Referer', function (?string $referer): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    $cabecalhos = $referer === null ? [] : ['Referer' => str_starts_with($referer, '/') ? url($referer) : $referer];

    $this->withHeaders($cabecalhos)->getJson('/admin?formato=json');
    $this->flushHeaders();

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toBe(enderecoDe('/admin/shield/roles'))
        ->and($destino)->not->toBe(enderecoDe('/admin?formato=json'));

    if ($referer !== null) {
        expect($destino)->not->toBe(enderecoDe($referer));
    }
})->with([
    'sem Referer' => [null],
    'interno'     => ['/admin/shield/roles'],
    'externo'     => ['https://externo.example/x'],
]);

it('[CT-09] a tela de bloqueio aberta direto leva à raiz do painel, nunca à página pública', function (string $painel, string $raiz): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $destino = destinoDoComponente(desbloquearPorSenha($painel));

    expect($destino)->toBe(enderecoDe($raiz))
        ->and($destino)->not->toBe(enderecoDe('/'));
})->with([
    'admin'                  => ['admin', '/admin'],
    'infra'                  => ['infra', '/infra'],
    'app (Kit, sem tenancy)' => ['app', '/app'],
]);

it('[CT-10] o segundo ciclo de trava não herda a página do primeiro', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    expect(destinoDoComponente(desbloquearPorSenha('admin')))->toBe(enderecoDe('/admin/users'));

    session(['lockscreen' => true]);

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'));
});

it('[CT-11] o duplo envio da senha correta leva, nos dois envios, à página guardada', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users?q=ana');

    $componente = desbloquearPorSenha('admin');
    $primeiro   = destinoDoComponente($componente);

    $componente->call('authenticate');
    $segundo = destinoDoComponente($componente);

    expect($primeiro)->toBe(enderecoDe('/admin/users?q=ana'))
        ->and($segundo)->toBe(enderecoDe('/admin/users?q=ana'))
        ->and($primeiro)->not->toBe(enderecoDe('/'))
        ->and($segundo)->not->toBe(enderecoDe('/'));
});

it('[CT-23] depois de uma visita real, a trava sem pedido de página vai à raiz, não à última visitada', function (string $trava): void {
    $this->actingAs(usuarioMaster());
    $this->get('/admin/shield/roles')->assertOk();

    if ($trava === 'tela') {
        session(['lockscreen' => true]);
    } else {
        session(['session_last_activity' => time() - (config('lockscreen.idle_timeout') + 60)]);

        $this->getJson('/admin/users');
    }

    expect(session('lockscreen'))->toBeTrue();

    telaDeBloqueioAbre('admin');

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin'))
        ->and($destino)->not->toBe(enderecoDe('/admin/shield/roles'));
})->with([
    'marca forçada e tela aberta direto'      => ['tela'],
    'GET que espera JSON com a sessão ociosa' => ['json'],
]);

it('[CT-24] a tela aberta direto ignora o Referer, de dentro ou de fora do sistema', function (string $referer): void {
    $this->actingAs(usuarioMaster());
    session(['lockscreen' => true]);

    $referer = str_starts_with($referer, '/') ? url($referer) : $referer;

    telaDeBloqueioAbre('admin', ['Referer' => $referer]);

    Filament::setCurrentPanel('admin');

    $componente = Livewire::withHeaders(['Referer' => $referer])->test(TelaBloqueio::class);

    ['destino' => $destino, 'vistos' => $vistos] = authenticatePelaRotaDeUpdate($componente, ['Referer' => $referer]);

    // Controle: o Referer chegou ao servidor na requisição da AÇÃO (o `Livewire::test` o descarta).
    expect($vistos)->not->toBeEmpty()
        ->and($vistos[0]['referer'])->toBe($referer);

    expect($destino)->toBe(enderecoDe('/admin'))
        ->and($destino)->not->toBe(urldecode($referer));
})->with([
    'interno' => ['/admin/shield/roles'],
    'externo' => ['https://externo.example/phishing'],
]);

it('[CT-12] senha errada não leva a lugar nenhum e mantém a sessão travada', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    desbloquearPorSenha('admin', 'senha-errada')
        ->assertHasErrors('data.password')
        ->assertNoRedirect();

    expect(session('lockscreen'))->toBeTrue();
});

it('[CT-13] a senha errada não gasta a página guardada: a senha certa em seguida ainda volta a ela', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    $componente = desbloquearPorSenha('admin', 'senha-errada')->assertHasErrors('data.password');

    $componente->set('data.password', 'password')->call('authenticate');

    expect(destinoDoComponente($componente))->toBe(enderecoDe('/admin/users'))
        ->and(session('lockscreen'))->toBeFalse();
});

it('[CT-31] o provedor negado não destrava nem gasta a página: a senha certa em seguida ainda volta a ela', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    // Sem Socialite::fake: o driver real, sem o `state` que a ida ao provedor gravaria, recusa.
    $this->get('/auth/google/callback?error=access_denied')->assertRedirect();

    expect(session('lockscreen'))->toBeTrue();

    $componente = desbloquearPorSenha('admin');

    expect(destinoDoComponente($componente))->toBe(enderecoDe('/admin/users'))
        ->and(session('lockscreen'))->toBeFalse();
});

it('[CT-14] a volta do provedor destrava e leva à página pedida, com o login unificado ligado ou desligado', function (bool $unificado): void {
    ligarLoginUnificado($unificado);

    $conta = usuarioMaster();
    provedorDevolve($conta);
    $this->actingAs($conta);
    travadaPorInatividadeAoPedir('/admin/users?q=ana');

    $resposta = $this->get('/auth/google/callback');

    expect(destinoDaResposta($resposta))->toBe(enderecoDe('/admin/users?q=ana'))
        ->and(session('lockscreen'))->toBeFalse()
        ->and(auth()->id())->toBe($conta->id);
})->with([
    'unificado desligado' => [false],
    'unificado ligado'    => [true],
]);

it('[CT-15] com a tela de bloqueio aberta direto, o provedor leva à raiz do painel dela', function (string $painel, string $raiz): void {
    $conta = usuarioMaster();
    provedorDevolve($conta);
    $this->actingAs($conta);
    session(['lockscreen' => true]);

    telaDeBloqueioAbre($painel);

    $resposta = $this->withHeaders(['Referer' => 'https://accounts.google.com'])->get('/auth/google/callback');
    $this->flushHeaders();

    expect(destinoDaResposta($resposta))->toBe(enderecoDe($raiz))
        ->and(destinoDaResposta($resposta))->not->toBe(enderecoDe('/'))
        ->and(session('lockscreen'))->toBeFalse();
})->with([
    'admin'                             => ['admin', '/admin'],
    'infra, diferente do painel padrão' => ['infra', '/infra'],
]);

it('[CT-16] parâmetro de destino no callback é ignorado', function (): void {
    $conta = usuarioMaster();
    provedorDevolve($conta);
    $this->actingAs($conta);
    travadaPorInatividadeAoPedir('/admin/users');

    $resposta = $this->get('/auth/google/callback?redirect=https://externo.example/x&intended=/infra');

    expect(destinoDaResposta($resposta))->toBe(enderecoDe('/admin/users'))
        ->and(destinoDaResposta($resposta))->not->toContain('externo.example');
});

it('[CT-22] outra conta no provedor não herda a página guardada da primeira pessoa', function (ProvedorSocial $provedor): void {
    $contaA = usuarioMaster();
    $contaB = contaComAcessoAoAdmin();
    provedorDevolve($contaB, $provedor);
    $this->actingAs($contaA);
    travadaPorInatividadeAoPedir('/admin/users?q=ana');

    $resposta = $this->get("/auth/{$provedor->value}/callback");
    $destino  = destinoDaResposta($resposta);

    $seguido = $this->get($destino);

    expect($destino)->not->toBe(enderecoDe('/admin/users?q=ana'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toContain('screen/lock')
        ->and(session('lockscreen'))->toBeFalse()
        ->and(auth()->id())->toBe($contaB->id);

    $seguido->assertOk();
})->with([
    'mesmo provedor' => [ProvedorSocial::Google],
    'outro provedor' => [ProvedorSocial::Github],
]);

it('[CT-28] a página da conta A não vaza para a conta B num bloqueio posterior sem pedido', function (): void {
    $contaA = usuarioMaster();
    $contaB = contaComAcessoAoAdmin();
    provedorDevolve($contaB);
    $this->actingAs($contaA);
    travadaPorInatividadeAoPedir('/admin/users?q=ana');

    $this->get('/auth/google/callback')->assertRedirect();

    expect(auth()->id())->toBe($contaB->id);

    session(['lockscreen' => true]);
    telaDeBloqueioAbre('admin');

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users?q=ana'));
});

it('[CT-29] a conta que entra depois de "Trocar de conta" não recebe a página da conta A', function (string $mecanismo): void {
    $contaA = usuarioMaster();
    $contaB = contaComAcessoAoAdmin();
    $this->actingAs($contaA);
    travadaPorInatividadeAoPedir('/admin/users?q=ana');

    $this->post('/admin/logout');

    expect(auth()->check())->toBeFalse();

    if ($mecanismo === 'senha') {
        Filament::setCurrentPanel('admin');

        $componente = Livewire::test(TelaLogin::class)
            ->fillForm(['email' => $contaB->email, 'password' => 'password'])
            ->call('authenticate');

        $destino = destinoDoComponente($componente);
    } else {
        provedorDevolve($contaB);

        $destino = destinoDaResposta($this->get('/auth/google/callback'));
    }

    expect($destino)->not->toBe(enderecoDe('/admin/users?q=ana'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'))
        ->and(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($contaB->id);
})->with([
    'senha, na tela de login comum' => ['senha'],
    'provedor, no callback'         => ['provedor'],
]);

it('[CT-17] seguir o retorno depois da trava por inatividade abre a página e não trava de novo', function (string $mecanismo): void {
    $conta = usuarioMaster();
    $this->actingAs($conta);
    travadaPorInatividadeAoPedir('/admin/users');

    if ($mecanismo === 'senha') {
        $destino = destinoDoComponente(desbloquearPorSenha('admin'));
    } else {
        provedorDevolve($conta);

        $destino = destinoDaResposta($this->get('/auth/google/callback'));
    }

    expect($destino)->toBe(enderecoDe('/admin/users'));

    $this->get($destino)->assertOk();

    expect(session('lockscreen'))->toBeFalse();
})->with([
    'senha (componente)'  => ['senha'],
    'provedor (callback)' => ['provedor'],
]);

it('[CT-18] com a sessão já destravada, abrir a tela de bloqueio volta ao painel', function (string $painel, string $raiz): void {
    $this->actingAs(usuarioMaster());

    $this->post("/{$painel}/lock-session")->assertRedirect();

    desbloquearPorSenha($painel);

    $resposta = $this->get(route("lockscreen.{$painel}.page"));

    $resposta->assertRedirect(url($raiz));

    $this->get(destinoDaResposta($resposta))->assertOk();
})->with([
    'admin'     => ['admin', '/admin'],
    'infra'     => ['infra', '/infra'],
    'app (Kit)' => ['app', '/app'],
]);

it('[CT-19] chave extra no estado do componente não escolhe o destino', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    Filament::setCurrentPanel('admin');

    $componente = Livewire::test(TelaBloqueio::class)
        ->set('data.redirect', 'https://externo.example/x')
        ->set('data.password', 'password')
        ->call('authenticate');

    $destino = destinoDoComponente($componente);

    expect($destino)->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toContain('externo.example');
});

it('[CT-20] senha com tipo errado é recusada sem exceção e mantém a trava', function (): void {
    $this->actingAs(usuarioMaster());
    travadaPorInatividadeAoPedir('/admin/users');

    Filament::setCurrentPanel('admin');

    Livewire::test(TelaBloqueio::class)
        ->set('data.password', [])
        ->call('authenticate')
        ->assertHasErrors('data.password')
        ->assertNoRedirect();

    expect(session('lockscreen'))->toBeTrue();
});

/** O link assinado de confirmação de vínculo (a mesma rota e parâmetros que o e-mail carrega). */
function linkDeConfirmacaoDoVinculo(User $conta, ProvedorSocial $provedor, string $sub): string
{
    return URL::temporarySignedRoute('auth.social.confirmar', now()->addMinutes(30), [
        'provedor' => $provedor->value,
        'user'     => $conta->getKey(),
        'sub'      => $sub,
    ]);
}

it('[CT-32] a outra conta que confirma o vínculo pelo link não recebe a página guardada de A', function (bool $unificado): void {
    config()->set('kit.login.vinculo_confirmar', true);
    ligarLoginUnificado($unificado);
    ligarProvedor(ProvedorSocial::Google);

    $contaA = usuarioMaster();
    $contaB = contaComAcessoAoAdmin();
    $this->actingAs($contaA);
    travadaPorInatividadeAoPedir('/admin/users?q=ana');

    $resposta = $this->get(linkDeConfirmacaoDoVinculo($contaB, ProvedorSocial::Google, 'google-sub-b'));
    $destino  = destinoDaResposta($resposta);

    $this->get($destino);

    expect($destino)->not->toBe(enderecoDe('/admin/users?q=ana'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'))
        ->and(auth()->id())->toBe($contaB->id);
})->with([
    'unificado ligado'    => [true],
    'unificado desligado' => [false],
]);

it('[CT-33] a mesma conta que confirma o vínculo não deixa a página antiga para o ciclo seguinte', function (): void {
    config()->set('kit.login.vinculo_confirmar', true);
    ligarProvedor(ProvedorSocial::Google);

    $contaA = usuarioMaster();
    $this->actingAs($contaA);
    travadaPorInatividadeAoPedir('/admin/users?q=ana');

    $this->get(linkDeConfirmacaoDoVinculo($contaA, ProvedorSocial::Google, 'google-sub-a'))->assertRedirect();

    session(['lockscreen' => true]);
    telaDeBloqueioAbre('admin');

    $destino = destinoDoComponente(desbloquearPorSenha('admin'));

    expect($destino)->toBe(enderecoDe('/admin'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users?q=ana'));
});

it('[CT-35] esgotar as tentativas desloga e o próximo login segue o destino de um login sem página guardada', function (string $partida): void {
    ligarLoginUnificado();

    $conta = usuarioMaster();

    $entrar = function () use ($conta): string {
        Filament::auth()->logout();
        Filament::setCurrentPanel('app');

        $login = Livewire::test(TelaLoginUnificada::class)
            ->fillForm(['email' => $conta->email, 'password' => 'password'])
            ->call('authenticate');

        return destinoDoComponente($login);
    };

    // D0: o login de referência, sem nenhuma trava, para a mesma conta e configuração.
    $d0 = $entrar();

    expect($d0)->not->toBe('')
        ->and($d0)->not->toBe(enderecoDe('/admin'))
        ->and($d0)->not->toBe(enderecoDe('/admin/users?q=ana'));

    if ($partida === 'pagina') {
        travadaPorInatividadeAoPedir('/admin/users?q=ana');
    } else {
        session(['lockscreen' => true]);
    }

    Filament::setCurrentPanel('admin');
    $tela = Livewire::test(TelaBloqueio::class);

    for ($tentativa = 0; $tentativa < 8 && auth()->check(); $tentativa++) {
        $tela->set('data.password', 'senha-errada')->call('authenticate');
    }

    expect(auth()->check())->toBeFalse();

    $destino = $entrar();

    expect($destino)->toBe($d0)
        ->and($destino)->not->toBe(enderecoDe('/admin'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users?q=ana'))
        ->and(session()->has('url.intended'))->toBeFalse();
})->with([
    'com página (travada ao pedir /admin/users?q=ana)' => ['pagina'],
    'fallback (tela aberta direto, raiz /admin)'       => ['fallback'],
]);

it('[CT-36] com o componente da tela já montado, uma página pedida em outra aba, ainda travada, vira o destino', function (string $mecanismo): void {
    $conta = usuarioMaster();
    $this->actingAs($conta);
    travadaPorInatividadeAoPedir('/admin/users');

    // Aba 1: o componente é montado ANTES do pedido da aba 2.
    Filament::setCurrentPanel('admin');
    $componente = Livewire::test(TelaBloqueio::class);

    // Aba 2: pede outra página com a sessão já travada.
    $this->get('/admin/shield/roles')->assertRedirect(route('lockscreen.admin.page'));

    if ($mecanismo === 'senha') {
        $destino = destinoDoComponente($componente->set('data.password', 'password')->call('authenticate'));
    } else {
        provedorDevolve($conta);

        $destino = destinoDaResposta($this->get('/auth/google/callback'));
    }

    expect($destino)->toBe(enderecoDe('/admin/shield/roles'))
        ->and($destino)->not->toBe(enderecoDe('/admin/users'));
})->with([
    'senha correta, enviada no componente já montado' => ['senha'],
    'volta do provedor google com a mesma conta'      => ['provedor'],
]);
