<?php

use App\Filament\Pages\Auth\CadastroUnificado;
use App\Filament\Pages\Auth\TelaLoginUnificada;
use App\Models\User;
use App\Support\ContextoDePapeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * As telas externas do login unificado com a tenancy LIGADA — wiki
 * `feat/login-unificado-telas-externas`, células marcadas [Tenancy] na tabela de decisão do `04`.
 *
 * Só existem aqui porque a tenancy é decidida no `createApplication()` (`tests/TestCase.php`), não
 * por `config()->set()`: é ela que torna a organização obrigatória no cadastro aberto
 * (`RegistroPorConvite::mount()`) e que faz `tenants.registro_habilitado` valer alguma coisa.
 *
 * É o cenário medido numa instalação real: organização sem "Aceita cadastro público" e link de
 * cadastro sem `?org=` levavam toda visita à recusa de convite inválido.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config([
        'kit.registro.habilitado'       => true,
        'kit.registro.aprovacao_manual' => false,
        'kit.registro.verificar_email'  => false,
    ]);
});

/*
|--------------------------------------------------------------------------
| R4 — /cadastro serve convite e aberto, e recusa sem destino
|--------------------------------------------------------------------------
*/

it('[CT-51] a rota de registro do painel redireciona para /cadastro preservando a query, com tenancy', function (callable $arranjo): void {
    ligarLoginUnificado();
    [$query, $esperado] = $arranjo();

    $resposta = $this->get("/app/register{$query}");

    expect($resposta->getStatusCode())->toBe(302);
    $resposta->assertRedirect(route('cadastro').$query);

    $this->followingRedirects()->get("/app/register{$query}")
        ->assertOk()
        ->assertSee($esperado, false);
})->with([
    'organização com cadastro público' => [function (): array {
        organizacaoComRegistro('acme');

        return ['?org=acme', 'wire:'];
    }],
    // O token dispensa o ?org=: quem tem convite não depende da porta aberta da organização.
    'convite sem organização' => [function (): array {
        $token = ofertaPara('novo@example.com')->enviar();

        return ["?token={$token}", 'novo@example.com'];
    }],
    'convite e organização com cadastro público' => [function (): array {
        organizacaoComRegistro('acme');
        $token = ofertaPara('novo@example.com')->enviar();

        return ["?token={$token}&org=acme", 'novo@example.com'];
    }],
    // A organização fechada NÃO recusa quem tem token: o token manda.
    'convite e organização sem cadastro público' => [function (): array {
        organizacaoComRegistro('acme', registro: false);
        $token = ofertaPara('novo@example.com')->enviar();

        return ["?token={$token}&org=acme", 'novo@example.com'];
    }],
])->group('tenancy');

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
    'E2 — organização sem cadastro público' => [function (): string {
        organizacaoComRegistro('acme', registro: false);

        return '?org=acme';
    }],
    'E3 — organização inativa' => [function (): string {
        organizacaoComRegistro('acme', ativo: false);

        return '?org=acme';
    }],
    'E4 — slug inexistente' => [fn (): string => '?org=nao-existe'],
    'E5 — sem ?org='        => [function (): string {
        organizacaoComRegistro('acme');

        return '';
    }],
    // O registro global desligado vence a organização que aceita cadastro.
    'registro global desligado' => [function (): string {
        organizacaoComRegistro('acme');
        config(['kit.registro.habilitado' => false]);

        return '?org=acme';
    }],
])->group('tenancy');

/*
|--------------------------------------------------------------------------
| R5 — a gravação vincula a conta à organização do pedido
|--------------------------------------------------------------------------
*/

it('[CT-55] o formulário de /cadastro cria a conta vinculada à organização do ?org=', function (): void {
    ligarLoginUnificado();
    $acme = organizacaoComRegistro('acme');

    Filament::auth()->logout();
    Filament::setCurrentPanel('app');
    $this->get('/cadastro?org=acme')->assertOk();

    Livewire::withQueryParams(['org' => 'acme'])
        ->test(CadastroUnificado::class)
        ->fillForm([
            'name'                 => 'Pessoa Nova',
            'email'                => 'novo@example.com',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $criada = User::query()->where('email', 'novo@example.com')->sole();

    expect($criada->tenants()->whereKey($acme->getKey())->exists())->toBeTrue()
        ->and(Filament::auth()->id())->toBe($criada->getKey());

    // O papel precisa estar NO CONTEXTO da organização: gravado no global ele fica invisível
    // dentro do /app, porque o `wherePivot` do spatie filtra pelo team do request.
    ContextoDePapeis::em(
        $acme->getKey(),
        $criada,
        fn () => expect($criada->fresh()->hasRole('panel_user'))->toBeTrue(),
    );
})->group('tenancy');

/*
|--------------------------------------------------------------------------
| R6 — o link de cadastro do login só existe com organização resolvível, e a carrega
|--------------------------------------------------------------------------
| A regra que fecha o defeito medido: com a organização fechada o link some, em vez de levar à
| recusa. A ausência é DUPLA — nem o texto do link, nem href algum para o cadastro.
*/

it('[CT-58] com tenancy o link de cadastro só existe com organização resolvível, e a carrega', function (callable $arranjo, bool $ligada, string $rota, ?string $href): void {
    ligarLoginUnificado($ligada);
    $arranjo();

    $resposta = $this->get($rota)->assertOk();
    $html     = (string) $resposta->getContent();

    if ($href === null) {
        expect($html)->not->toContain(route('cadastro'))
            ->and($html)->not->toContain((string) Filament::getPanel('app')->getRegistrationUrl());

        return;
    }

    expect($html)->toContain($href);
})->with([
    'E1 — resolvível, chave ligada' => [
        fn () => organizacaoComRegistro('acme'),
        true,
        '/login?org=acme',
        fn (): string => route('cadastro', ['org' => 'acme']),
    ],
    'E1 — resolvível, chave desligada' => [
        fn () => organizacaoComRegistro('acme'),
        false,
        '/app/login?org=acme',
        fn (): string => (string) Filament::getPanel('app')->getRegistrationUrl(['org' => 'acme']),
    ],
    'E2 — sem cadastro público' => [
        fn () => organizacaoComRegistro('acme', registro: false),
        true,
        '/login?org=acme',
        null,
    ],
    'E3 — inativa' => [
        fn () => organizacaoComRegistro('acme', ativo: false),
        true,
        '/login?org=acme',
        null,
    ],
    'E4 — slug inexistente' => [
        fn () => null,
        true,
        '/login?org=nao-existe',
        null,
    ],
    'E5 — sem ?org=' => [
        fn () => organizacaoComRegistro('acme'),
        true,
        '/login',
        null,
    ],
    // As duas linhas com a chave desligada: o dead end existia antes do login unificado.
    'E2 com a chave desligada' => [
        fn () => organizacaoComRegistro('acme', registro: false),
        false,
        '/app/login?org=acme',
        null,
    ],
    'E5 com a chave desligada' => [
        fn () => organizacaoComRegistro('acme'),
        false,
        '/app/login',
        null,
    ],
    'registro global desligado' => [
        function () {
            organizacaoComRegistro('acme');
            config(['kit.registro.habilitado' => false]);
        },
        true,
        '/login?org=acme',
        null,
    ],
])->group('tenancy');
