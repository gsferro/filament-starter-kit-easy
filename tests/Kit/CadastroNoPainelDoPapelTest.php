<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\CadastroUnificado;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\PanelRegistry;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

/**
 * Cadastro no painel do papel — wiki `fix/convite-painel-do-papel`, 04-casos-de-teste.md.
 *
 * Os helpers abaixo tem prefixo proprio: o mesmo arquivo de tenancy declara os seus, e
 * funcao global colide entre arquivos.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    ligarLoginUnificado(false);
});

/** Papel que abre cada painel do kit. */
const PAPEL_DO_PAINEL_NO_CADASTRO = ['admin' => 'admin', 'app' => 'panel_user', 'infra' => 'infra'];

/**
 * Cadastro real pelo componente, a partir de um convite.
 */
function cadastroDoPapelPorConvite(string $componente, string $token, string $email = 'novo@example.com')
{
    Filament::auth()->logout();
    Filament::setCurrentPanel('app');

    return Livewire::withQueryParams(['token' => $token])
        ->test($componente)
        ->fillForm([
            'name'                 => 'Fulano',
            'email'                => $email,
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');
}

/**
 * GET na MESMA sessão do teste: sem nova autenticação e sem desligar middleware.
 */
function acessoDoPapelNaMesmaSessao(string $url): TestResponse
{
    session()->save();

    return test()
        ->withCookie(session()->getName(), session()->getId())
        ->get($url);
}

function esperarAcessoLivreNoCadastro(string $url): void
{
    $resposta = acessoDoPapelNaMesmaSessao($url);

    expect($resposta->getStatusCode())->toBe(200, "GET {$url} nao terminou em 200");
}

/** Conta ativa cujos painéis acessíveis são exatamente os pedidos. */
function contaComPaineisNoCadastro(array $paineis): User
{
    $user = User::factory()->create();

    foreach ($paineis as $painel) {
        $user->assignRole(PAPEL_DO_PAINEL_NO_CADASTRO[$painel]);
    }

    return $user;
}

/**
 * Fixa corrente, default e (opcional) ordem registrada.
 *
 * @param  list<string>|null  $ordem
 */
function paineisNoCadastro(string $corrente, string $default, ?array $ordem = null): void
{
    $registro = app(PanelRegistry::class);

    if (! array_key_exists('restrito', $registro->all()) && ($corrente === 'restrito' || in_array('restrito', $ordem ?? [], true))) {
        painelRegistradoEmTeste('restrito');
    }

    if ($ordem !== null) {
        $nova = [];

        foreach ($ordem as $id) {
            $nova[$id] = $registro->panels[$id];
        }

        $registro->panels = $nova + $registro->panels;
    }

    $registro->defaultPanel = Filament::getPanel($default);

    Filament::setCurrentPanel($corrente);
}

/** Resposta de cadastro pedida diretamente, com a conta autenticada no guard do painel corrente. */
function respostaDeCadastroNoCadastro(User $user, ?string $pretendida): string
{
    if ($pretendida !== null) {
        session()->put('url.intended', url($pretendida));
    }

    Filament::auth()->login($user);

    $requisicao = Request::capture();
    $requisicao->setLaravelSession(app('session.store'));

    return app(RegistrationResponse::class)->toResponse($requisicao)->getTargetUrl();
}

it('[CT-01] convite infra sem organizacao chega ao infra', function (): void {
    $convite = ofertaPara('novo@example.com', null, 'infra');

    cadastroDoPapelPorConvite(RegistroPorConvite::class, $convite->enviar())->assertRedirect('/infra');

    expect(Filament::auth()->check())->toBeTrue();
    esperarAcessoLivreNoCadastro('/infra');
});

it('[CT-02] convite admin sem organizacao chega ao admin', function (): void {
    $convite = ofertaPara('novo@example.com', null, 'admin');

    cadastroDoPapelPorConvite(RegistroPorConvite::class, $convite->enviar())->assertRedirect('/admin');

    expect(Filament::auth()->check())->toBeTrue();
    esperarAcessoLivreNoCadastro('/admin');
});

it('[CT-03] a resposta aplica o destino fora do componente', function (string $painel): void {
    $user = contaComPaineisNoCadastro([$painel]);

    paineisNoCadastro('app', 'app');

    $destino = respostaDeCadastroNoCadastro($user, null);

    expect($destino)->toBe(url("/{$painel}"))
        ->and(Filament::auth()->check())->toBeTrue();

    esperarAcessoLivreNoCadastro($destino);
})->with(['infra', 'admin']);

it('[CT-04] a resposta preserva a pretendida autorizada ou usa a corrente', function (
    array $papeis,
    string $corrente,
    string $default,
    ?string $pretendida,
    string $destino,
): void {
    $user = contaComPaineisNoCadastro($papeis);

    paineisNoCadastro($corrente, $default);

    $alvo = respostaDeCadastroNoCadastro($user, $pretendida);

    expect($alvo)->toBe(url($destino))
        ->and(Filament::auth()->check())->toBeTrue();

    esperarAcessoLivreNoCadastro($alvo);
})->with([
    'sem pretendida'             => [['app', 'infra'], 'app', 'infra', null, '/app'],
    'painel pretendido proibido' => [['app', 'infra'], 'app', 'infra', '/admin', '/app'],
    'mesmo painel'               => [['app', 'infra'], 'app', 'infra', '/app?origem=convite', '/app?origem=convite'],
    'outro painel'               => [['app', 'infra'], 'app', 'infra', '/infra?origem=convite', '/infra?origem=convite'],
    'corrente infra'             => [['admin', 'infra'], 'infra', 'admin', null, '/infra'],
    'corrente admin'             => [['admin', 'infra'], 'admin', 'infra', null, '/admin'],
    'subpagina acessivel'        => [['admin', 'infra'], 'infra', 'admin', '/admin/users?origem=convite', '/admin/users?origem=convite'],
]);

it('[CT-05] panel_user sem organizacao permanece no app sem tenancy', function (): void {
    $convite = ofertaPara('novo@example.com', null, 'panel_user');

    cadastroDoPapelPorConvite(RegistroPorConvite::class, $convite->enviar())->assertRedirect('/app');

    expect(Filament::auth()->check())->toBeTrue();
    esperarAcessoLivreNoCadastro('/app');
});

it('[CT-08] default acessivel prevalece sobre o primeiro acessivel', function (
    array $acessiveis,
    string $corrente,
    string $default,
    array $ordem,
    string $destino,
): void {
    $user = contaComPaineisNoCadastro($acessiveis);

    paineisNoCadastro($corrente, $default, $ordem);

    $alvo = respostaDeCadastroNoCadastro($user, null);

    expect($alvo)->toBe(url($destino))
        ->and(Filament::auth()->check())->toBeTrue();

    esperarAcessoLivreNoCadastro($alvo);
})->with([
    'admin e infra'   => [['admin', 'infra'], 'app', 'infra', ['admin', 'app', 'infra'], '/infra'],
    'admin e app'     => [['admin', 'app'], 'infra', 'app', ['admin', 'app', 'infra'], '/app'],
    'app e infra'     => [['app', 'infra'], 'admin', 'infra', ['app', 'admin', 'infra'], '/infra'],
    'tres acessiveis' => [['admin', 'app', 'infra'], 'restrito', 'infra', ['admin', 'app', 'infra', 'restrito'], '/infra'],
]);

it('[CT-09] default inacessivel usa o primeiro acessivel na ordem registrada', function (
    array $ordem,
    ?string $pretendida,
    string $destino,
): void {
    $user = contaComPaineisNoCadastro(['admin', 'infra']);

    paineisNoCadastro('app', 'app', $ordem);

    $alvo = respostaDeCadastroNoCadastro($user, $pretendida);

    expect($alvo)->toBe(url($destino))
        ->and(Filament::auth()->check())->toBeTrue()
        ->and(session()->has('url.intended'))->toBeFalse();

    esperarAcessoLivreNoCadastro($alvo);
})->with([
    'primeiro registrado'             => [['infra', 'app', 'admin'], null, '/infra'],
    'ordem inversa'                   => [['admin', 'app', 'infra'], null, '/admin'],
    'pretendida inacessivel'          => [['infra', 'app', 'admin'], '/app', '/infra'],
    'pretendida e ordem inversa'      => [['admin', 'app', 'infra'], '/app', '/admin'],
    'primeiro registrado proibido'    => [['app', 'infra', 'admin'], null, '/infra'],
    'primeiro e pretendida proibidos' => [['app', 'admin', 'infra'], '/app', '/admin'],
]);

it('[CT-10] zero paineis encerram a sessao e tornam o login alcancavel', function (string $corrente, ?string $pretendida): void {
    $user = User::factory()->create();

    paineisNoCadastro($corrente, 'admin');

    $alvo = respostaDeCadastroNoCadastro($user, $pretendida);

    expect($alvo)->toBe(Filament::getPanel($corrente)->getLoginUrl())
        ->and(Filament::auth()->check())->toBeFalse();

    esperarAcessoLivreNoCadastro($alvo);
})->with([
    'app sem pretendida'    => ['app', null],
    'app com pretendida'    => ['app', '/admin'],
    'infra sem pretendida'  => ['infra', null],
    'infra com pretendida'  => ['infra', '/admin'],
]);

it('[CT-11] a resposta descarta a pretendida e entrega um destino permitido', function (): void {
    $user = contaComPaineisNoCadastro(['infra']);

    paineisNoCadastro('app', 'app');

    $alvo = respostaDeCadastroNoCadastro($user, '/admin');

    expect($alvo)->toBe(url('/infra'))
        ->and(Filament::auth()->check())->toBeTrue()
        ->and(session()->has('url.intended'))->toBeFalse();

    esperarAcessoLivreNoCadastro($alvo);
});

it('[CT-12] pretendida acessivel de outro painel mantem o destino dentro do painel permitido', function (): void {
    $user = contaComPaineisNoCadastro(['infra']);

    paineisNoCadastro('app', 'app');

    $alvo = respostaDeCadastroNoCadastro($user, '/infra?origem=convite');

    expect(parse_url($alvo, PHP_URL_PATH))->toBe('/infra')
        ->and($alvo)->toBe(url('/infra?origem=convite'))
        ->and(Filament::auth()->check())->toBeTrue();

    esperarAcessoLivreNoCadastro($alvo);
});

it('[CT-13] a nota de deploy explica public app e a alternativa de configuracao', function (string $idioma, string $raiz): void {
    $texto = (string) file_get_contents(base_path("docs/{$idioma}/recursos/configuracao-global-filament.md"));

    $nota = collect(preg_split('/\R\s*\R/', $texto))
        ->first(fn (string $bloco): bool => str_contains($bloco, 'DirectorySlash'));

    expect($nota)->not->toBeNull();

    expect($nota)->toContain('/public/app')
        ->toContain('DocumentRoot')
        ->toContain('`public/`')
        ->toContain('DirectorySlash Off')
        ->toContain($raiz);
})->with([
    'pt' => ['pt', '`.htaccess` da raiz'],
    'en' => ['en', 'root `.htaccess`'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'docs/ nao viaja no projeto instalado');

it('[CT-14] modo ligado preserva o destino de um unico painel acessivel', function (): void {
    ligarLoginUnificado(true);

    $convite = ofertaPara('novo@example.com', null, 'infra');

    cadastroDoPapelPorConvite(CadastroUnificado::class, $convite->enviar())->assertRedirect('/infra');

    expect(Filament::auth()->check())->toBeTrue();
    esperarAcessoLivreNoCadastro('/infra');
});
