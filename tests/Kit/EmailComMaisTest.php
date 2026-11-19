<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Admin\Resources\Convites\Pages\CreateConvite;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Pages\Auth\CadastroUnificado;
use App\Filament\Pages\Auth\TelaLogin;
use App\Livewire\InformacoesPessoais;
use App\Models\Convite;
use App\Models\Role;
use App\Models\User;
use App\Models\VinculoSocial;
use App\Notifications\ConviteDeAcesso;
use App\Support\EmailComMais;
use App\Support\ProvedorSocial;
use App\Support\RegistroAberto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Laravel\Socialite\Socialite;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;

/**
 * Permitir ou bloquear o "+" no e-mail do cadastro — oito vias, três painéis, dois estados.
 *
 * IDs de CT: `wikis/specs/feat/email-com-mais/email-com-mais/04-casos-de-teste.md`. O oráculo é o
 * 04 (derivado do requisito), não a implementação. Os CT de organização estão em
 * `tests/Tenancy/RegistroAbertoTenancyTest.php` (CT-06) e `tests/Tenancy/AdminDaOrganizacaoTest.php`
 * (CT-12 e CT-20), porque `admin_app` e o `/app` com organizações só existem lá.
 *
 * Ordem importa em `opcaoDoMais()`: ela chama o alinhamento do settings, que regrava na config
 * TODAS as chaves da tela (registro aberto, login unificado, provedores). Quem precisa de uma
 * dessas chaves a liga DEPOIS dela.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** A mensagem única de recusa (Q?1 respondida pela recomendação): igual em todas as vias. */
function mensagemDoMaisEsperada(): string
{
    return 'Este sistema não aceita e-mail com "+" antes do "@". Use o endereço sem o "+".';
}

/**
 * As mensagens de erro do campo de e-mail de um componente (qualquer state path que termine em `email`).
 *
 * @return list<string>
 */
function errosDoCampoEmail(Testable $componente): array
{
    return collect($componente->errors()->toArray())
        ->filter(fn (mixed $mensagens, string $chave): bool => str_ends_with($chave, 'email'))
        ->flatten()
        ->values()
        ->all();
}

/** O campo de e-mail "não tem erro" ou "tem erro com mensagem que cita o +". */
function afirmarErroDoCampoEmail(Testable $componente, bool $comErro): void
{
    $erros = errosDoCampoEmail($componente);

    if (! $comErro) {
        expect($erros)->toBe([], 'o campo de e-mail não devia ter erro');

        return;
    }

    expect($erros)->not->toBe([], 'o campo de e-mail devia ter erro')
        ->and($erros[0])->toContain('"+"')
        ->and($erros[0])->not->toContain('por convite')
        ->and($erros[0])->not->toContain('válido')
        ->and($erros[0])->toBe(mensagemDoMaisEsperada());
}

/** Liga o registro aberto — DEPOIS de `opcaoDoMais()`. */
function ligarRegistroParaOMais(): void
{
    config(['kit.registro.habilitado' => true, 'kit.registro.aprovacao_manual' => false, 'kit.registro.verificar_email' => false]);
}

/** O visitante se cadastra na tela pedida (`/app/register` ou `/cadastro`). */
function cadastrarNaTela(string $tela, string $email): Testable
{
    if ($tela === '/app/register') {
        return registrarAberto($email);
    }

    ligarLoginUnificado();
    Filament::auth()->logout();
    Filament::setCurrentPanel('app');
    test()->get('/cadastro')->assertOk();

    return Livewire::test(CadastroUnificado::class)
        ->fillForm([
            'name'                 => 'Pessoa Nova',
            'email'                => $email,
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');
}

/**
 * O retorno do Google com o e-mail dado, com a conta e o provedor ligados.
 */
function retornoDoGoogleCom(string $email, string $sub = 'sub-mais'): TestResponse
{
    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], [
        'id'    => $sub,
        'email' => $email,
        'name'  => 'Pessoa do Google',
    ]));

    return test()->get('/auth/google/callback');
}

/*
|--------------------------------------------------------------------------
| R1 — toda ausência de valor permite; só "desligado" explícito bloqueia
|--------------------------------------------------------------------------
*/

it('[CT-01] o valor da variável de ambiente decide, e só o falso explícito bloqueia', function (?string $env, string $resposta): void {
    $configuracao = kitConfigCom('KIT_EMAIL_PERMITIR_MAIS', $env);

    config()->set('kit.email.permitir_mais', $configuracao['email']['permitir_mais']);

    expect(EmailComMais::permitido() ? 'permitido' : 'bloqueado')->toBe($resposta);
})->with([
    'ausente'                  => [null, 'permitido'],
    'vazia'                    => ['', 'permitido'],
    'irreconhecível'           => ['talvez', 'permitido'],
    'true'                     => ['true', 'permitido'],
    'false'                    => ['false', 'bloqueado'],
    '0'                        => ['0', 'bloqueado'],
    'off (Q?2 respondida)'     => ['off', 'bloqueado'],
    'no (Q?2 respondida)'      => ['no', 'bloqueado'],
])->group('kit');

it('[CT-02] o projeto que manteve o próprio config/kit.php sem a chave permite o "+"', function (): void {
    $kit = config('kit');
    unset($kit['email']);
    config()->set('kit', $kit);

    expect(config('kit.email'))->toBeNull();

    ligarRegistroParaOMais();

    $registro = registrarAberto('gsferroti+starter@gmail.com');

    expect(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeTrue();
    afirmarErroDoCampoEmail($registro, comErro: false);
})->group('kit');

it('[CT-03] o settings ainda não migrado permite, e o gravado decide', function (?string $env, ?bool $settings, bool $existe): void {
    config()->set('kit.email.permitir_mais', kitConfigCom('KIT_EMAIL_PERMITIR_MAIS', $env)['email']['permitir_mais']);

    if ($settings === null) {
        SettingsProperty::query()->where('group', 'kit')->where('name', 'email_permitir_mais')->delete();
        app()->forgetInstance(App\Settings\ConfiguracoesDoKit::class);
    } else {
        gravarConfiguracao('email_permitir_mais', $settings);
    }

    alinharConfiguracoesDoKit();
    ligarRegistroParaOMais();

    registrarAberto('gsferroti+starter@gmail.com');

    expect(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBe($existe);
})->with([
    'settings não migrado e env ausente: permite'  => [null, null, true],
    'settings não migrado: o env explícito decide' => ['false', null, false],
    'gravado permitindo vence o env'               => ['false', true, true],
    'gravado desligado bloqueia'                   => [null, false, false],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — a opção é da instalação, governada pela tela, lida por um só leitor
|--------------------------------------------------------------------------
*/

it('[CT-04] o admin desliga o "+" na aba Registro e o registro aberto passa a recusar', function (): void {
    opcaoDoMais(true);
    gravarConfiguracao('registro_habilitado', true);

    $this->actingAs(usuarioDoKit('admin'));
    Filament::setCurrentPanel('admin');

    $tela = Livewire::test(ConfiguracoesDoKit::class);
    carregarAba($tela, 'registro')->refresh();

    $tela->fillForm(['email_permitir_mais' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('email_permitir_mais'))->toBeFalse();

    alinharConfiguracoesDoKit();

    $registro = registrarAberto('gsferroti+starter@gmail.com');

    expect(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeFalse();
    afirmarErroDoCampoEmail($registro, comErro: true);
})->group('kit');

it('[CT-05] só o leitor único lê a chave da opção', function (): void {
    $donos = [
        realpath(app_path('Support/EmailComMais.php')),
        realpath(app_path('Settings/ConfiguracoesDoKit.php')),
    ];

    $leitores = collect(File::allFiles(app_path()))
        ->filter(fn (SplFileInfo $arquivo): bool => $arquivo->getExtension() === 'php')
        ->filter(fn (SplFileInfo $arquivo): bool => preg_match(
            '/kit\.email[\'".]/',
            codigoSemComentario((string) file_get_contents((string) $arquivo->getRealPath())),
        ) === 1)
        ->map(fn (SplFileInfo $arquivo): string => (string) $arquivo->getRealPath())
        ->values();

    $foraDoLeitor = $leitores->reject(fn (string $caminho): bool => in_array($caminho, $donos, true))
        ->map(fn (string $caminho): string => basename($caminho));

    expect($foraDoLeitor->all())->toBe([], 'leitura da chave fora do leitor: '.$foraDoLeitor->implode(', '))
        // Controle positivo: sem ele, a varredura "não achou nada" também com o regex quebrado.
        ->and($leitores->contains(realpath(app_path('Support/EmailComMais.php'))))->toBeTrue();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — só conta o "+" da parte local
|--------------------------------------------------------------------------
*/

it('[CT-07] a regra decide pela parte local e pelo estado da opção', function (bool $permitido, string $email, bool $passa): void {
    opcaoDoMais($permitido);

    $validador = Validator::make(['email' => $email], ['email' => [EmailComMais::regra()]]);

    expect($validador->passes())->toBe($passa);
})->with([
    'bloqueado, sem "+"'                       => [false, 'gsferroti@gmail.com', true],
    'bloqueado, "+" no meio'                   => [false, 'gsferroti+starter@gmail.com', false],
    'bloqueado, "+" no meio com hífen'         => [false, 'gsferroti+starter-kit@gmail.com', false],
    'bloqueado, "+" primeiro caractere'        => [false, '+gsferroti@gmail.com', false],
    'bloqueado, "+" último antes do "@"'       => [false, 'gsferroti+@gmail.com', false],
    'bloqueado, "+" repetido'                  => [false, 'gsferroti+a+b@gmail.com', false],
    'bloqueado, "+" depois do "@" não conta'   => [false, 'gsferroti@gma+il.com', true],
    'permitido, com "+"'                       => [true, 'gsferroti+starter-kit@gmail.com', true],
    'permitido, sem "+"'                       => [true, 'gsferroti@gmail.com', true],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — registro aberto
|--------------------------------------------------------------------------
*/

it('[CT-08] as duas telas de registro aberto seguem a opção', function (string $tela, bool $permitido, string $email, bool $existe): void {
    Notification::fake();
    opcaoDoMais($permitido);
    ligarRegistroParaOMais();

    expect(User::where('email', $email)->exists())->toBeFalse();

    $registro = cadastrarNaTela($tela, $email);

    expect(User::where('email', $email)->exists())->toBe($existe);
    afirmarErroDoCampoEmail($registro, comErro: ! $existe);
})->with([
    '/app/register permitido com "+"'  => ['/app/register', true, 'gsferroti+starter@gmail.com', true],
    '/app/register bloqueado com "+"'  => ['/app/register', false, 'gsferroti+starter@gmail.com', false],
    '/app/register bloqueado sem "+"'  => ['/app/register', false, 'gsferroti@gmail.com', true],
    '/cadastro permitido com "+"'      => ['/cadastro', true, 'gsferroti+starter@gmail.com', true],
    '/cadastro bloqueado com "+"'      => ['/cadastro', false, 'gsferroti+starter@gmail.com', false],
    '/cadastro bloqueado sem "+"'      => ['/cadastro', false, 'gsferroti@gmail.com', true],
])->group('kit');

it('[CT-09] o registro chamado por fora da tela também recusa o "+" bloqueado', function (): void {
    opcaoDoMais(false);
    ligarRegistroParaOMais();

    $antes = User::count();

    expect(fn (): User => RegistroAberto::registrar([
        'name'     => 'Direto',
        'email'    => 'gsferroti+starter@gmail.com',
        'password' => 'segredo-bem-longo-123',
    ]))->toThrow(Exception::class);

    expect(User::count())->toBe($antes)
        ->and(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeFalse();
})->group('kit');

it('[CT-10] a recusa no registro diz o motivo, e a pessoa pode corrigir na mesma tela', function (): void {
    opcaoDoMais(false);
    ligarRegistroParaOMais();

    $registro = registrarAberto('gsferroti+starter@gmail.com');

    afirmarErroDoCampoEmail($registro, comErro: true);

    $registro->fillForm([
        'name'                 => 'Fulano',
        'email'                => 'gsferroti@gmail.com',
        'password'             => 'segredo-bem-longo-123',
        'passwordConfirmation' => 'segredo-bem-longo-123',
    ])->call('register');

    $conta = User::where('email', 'gsferroti@gmail.com')->first();

    expect($conta)->not->toBeNull()
        ->and(Filament::auth()->id())->toBe($conta?->getKey())
        ->and(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeFalse();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — convite um a um
|--------------------------------------------------------------------------
*/

it('[CT-11] a criação de convite no /admin segue a opção e a conta existente', function (bool $permitido, bool $contaExistente, string $email, bool $convite): void {
    Notification::fake();
    opcaoDoMais($permitido);

    if ($contaExistente) {
        usuario($email);
    }

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    Filament::setCurrentPanel('admin');

    $tela = Livewire::test(CreateConvite::class)
        ->fillForm(['email' => $email, 'role_id' => Role::findByName('panel_user')->getKey()])
        ->call('create');

    expect(Convite::where('email', $email)->exists())->toBe($convite);
    afirmarErroDoCampoEmail($tela, comErro: ! $convite);

    if ($convite) {
        Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1);
        Notification::assertSentOnDemand(
            ConviteDeAcesso::class,
            fn ($notificacao, array $canais, $notificavel): bool => $notificavel->routes['mail'] === $email,
        );
    } else {
        Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 0);
    }
})->with([
    'permitido, sem conta, com "+"'    => [true, false, 'gsferroti+starter@gmail.com', true],
    'bloqueado, sem conta, com "+"'    => [false, false, 'gsferroti+starter@gmail.com', false],
    'bloqueado, sem conta, sem "+"'    => [false, false, 'gsferroti@gmail.com', true],
    'bloqueado, conta existente, "+"'  => [false, true, 'gsferroti+starter@gmail.com', true],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — convite em massa
|--------------------------------------------------------------------------
*/

it('[CT-13] lote misto com a opção desligada: três enviados, uma falha com motivo próprio', function (): void {
    Notification::fake();
    opcaoDoMais(false);
    usuario('c+1@example.com');

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("a@example.com\nb+1@example.com\nc+1@example.com\nd@example.com"),
        (int) Role::findByName('panel_user')->getKey(),
        null,
        null,
    );

    $falhas = collect($resultado->falhas)->map->toArray()->all();

    expect($resultado->enviados)->toBe(['a@example.com', 'c+1@example.com', 'd@example.com'])
        ->and($falhas)->toHaveCount(1)
        ->and($falhas[0]['email'])->toBe('b+1@example.com')
        ->and($falhas[0]['motivo'])->not->toBeIn(['formato_invalido', 'convite_pendente', 'recusou_antes', 'erro_no_envio'])
        ->and($falhas[0]['motivo'])->not->toBeEmpty()
        ->and(Convite::where('email', 'b+1@example.com')->exists())->toBeFalse();

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 3);
})->group('kit');

it('[CT-14] o mesmo lote com a opção permitindo envia todos', function (): void {
    Notification::fake();
    opcaoDoMais(true);
    usuario('c+1@example.com');

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));
    Filament::setCurrentPanel('admin');

    Livewire::test(ListConvites::class)->callAction(TestAction::make('convidarEmMassa'), [
        'emails'  => "a@example.com\nb+1@example.com\nc+1@example.com\nd@example.com",
        'role_id' => Role::findByName('panel_user')->getKey(),
    ]);

    expect(Convite::count())->toBe(4);

    foreach (['a@example.com', 'b+1@example.com', 'c+1@example.com', 'd@example.com'] as $endereco) {
        expect(Convite::where('email', $endereco)->exists())->toBeTrue("o endereço {$endereco} ficou sem convite");
    }

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 4);
})->group('kit');

it('[CT-15] o "+" depois do "@" cai no formato, não no motivo do "+"', function (): void {
    Notification::fake();
    opcaoDoMais(false);

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("gsferroti@gma+il.com\ngsferroti@gmail.com"),
        (int) Role::findByName('panel_user')->getKey(),
        null,
        null,
    );

    $falhas = collect($resultado->falhas)->map->toArray()->all();

    expect($falhas)->toBe([['email' => 'gsferroti@gma+il.com', 'motivo' => 'formato_invalido']])
        ->and($resultado->enviados)->toBe(['gsferroti@gmail.com']);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — o convite já emitido vale
|--------------------------------------------------------------------------
*/

it('[CT-16] o aceite pela tela do convite com "+" emitido antes de desligar', function (string $tela, bool $unificado): void {
    Notification::fake();
    opcaoDoMais(true);

    [$convite, $token] = conviteCom('panel_user', null, 'gsferroti+starter@gmail.com');

    opcaoDoMais(false);

    if ($unificado) {
        ligarLoginUnificado();
        Filament::auth()->logout();
        Filament::setCurrentPanel('app');

        $formulario = Livewire::withQueryParams(['token' => $token])
            ->test(CadastroUnificado::class)
            ->fillForm([
                'name'                 => 'Pessoa Convidada',
                'password'             => 'segredo-bem-longo-123',
                'passwordConfirmation' => 'segredo-bem-longo-123',
            ])
            ->call('register');
    } else {
        $formulario = aceitarConvite($token, 'Pessoa Convidada');
    }

    $conta = User::where('email', 'gsferroti+starter@gmail.com')->first();

    expect($conta)->not->toBeNull()
        ->and($conta?->hasRole('panel_user'))->toBeTrue()
        ->and($convite->fresh()?->aceito_em)->not->toBeNull();
    afirmarErroDoCampoEmail($formulario, comErro: false);
})->with([
    '/app/register?token' => ['/app/register', false],
    '/cadastro?token'     => ['/cadastro', true],
])->group('kit');

it('[CT-17] o aceite pelo login social do convite com "+" emitido antes de desligar', function (): void {
    Notification::fake();
    opcaoDoMais(true);

    [$convite, $token] = conviteSocialPara('gsferroti+starter@gmail.com');

    opcaoDoMais(false);
    config(['kit.registro.habilitado' => false]);
    ligarProvedor(ProvedorSocial::Google);

    Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], [
        'id'    => 'sub-convite-mais',
        'email' => 'gsferroti+starter@gmail.com',
        'name'  => 'Pessoa Convidada',
    ]));

    $this->withSession(['login_social.contexto' => ['token' => $token]])
        ->get('/auth/google/callback')
        ->assertRedirect();

    $conta = User::where('email', 'gsferroti+starter@gmail.com')->first();

    expect($conta)->not->toBeNull()
        ->and($conta?->hasRole('panel_user'))->toBeTrue()
        ->and($convite->fresh()?->aceito_em)->not->toBeNull();

    $this->assertAuthenticatedAs($conta);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — CRUD de usuários no /admin
|--------------------------------------------------------------------------
*/

it('[CT-18] criar usuário no /admin segue a opção', function (bool $permitido, string $email, bool $existe): void {
    opcaoDoMais($permitido);
    noPainelBootado('admin');
    $this->actingAs(usuarioCom('master_global'));

    $tela = Livewire::test(CreateUser::class)
        ->fillForm([
            'name'     => 'Fulano',
            'email'    => $email,
            'password' => 'secret1234',
            'roles'    => [Role::findByName('panel_user')->getKey()],
        ])
        ->call('create');

    expect(User::where('email', $email)->exists())->toBe($existe);
    afirmarErroDoCampoEmail($tela, comErro: ! $existe);
})->with([
    'permitido, com "+"' => [true, 'gsferroti+starter@gmail.com', true],
    'bloqueado, com "+"' => [false, 'gsferroti+starter@gmail.com', false],
    'bloqueado, sem "+"' => [false, 'gsferroti@gmail.com', true],
])->group('kit');

it('[CT-19] editar usuário no /admin com a opção desligada só olha o e-mail novo', function (bool $permitido, string $atual, string $novo, string $gravado, string $nome, bool $erro): void {
    opcaoDoMais($permitido);

    $usuario = usuarioDoKit('panel_user', $atual);
    $usuario->update(['name' => 'Antigo']);

    noPainelBootado('admin');
    $this->actingAs(usuarioCom('master_global'));

    $tela = Livewire::test(EditUser::class, ['record' => $usuario->getRouteKey()])
        ->fillForm(['email' => $novo, 'name' => 'Novo'])
        ->call('save');

    $depois = $usuario->fresh();

    expect($depois?->email)->toBe($gravado)
        ->and($depois?->name)->toBe($nome);
    afirmarErroDoCampoEmail($tela, comErro: $erro);
})->with([
    'sem trocar (P-04)'                 => [false, 'gsferroti+starter@gmail.com', 'gsferroti+starter@gmail.com', 'gsferroti+starter@gmail.com', 'Novo', false],
    'de "+" para outro "+"'             => [false, 'gsferroti+starter@gmail.com', 'gsferroti+starter-kit@gmail.com', 'gsferroti+starter@gmail.com', 'Antigo', true],
    'de "+" para sem "+"'               => [false, 'gsferroti+starter@gmail.com', 'gsferroti@gmail.com', 'gsferroti@gmail.com', 'Novo', false],
    'de sem "+" para "+"'               => [false, 'gsferroti@gmail.com', 'gsferroti+starter@gmail.com', 'gsferroti@gmail.com', 'Antigo', true],
    'permitido: de sem "+" para "+"'    => [true, 'gsferroti@gmail.com', 'gsferroti+starter@gmail.com', 'gsferroti+starter@gmail.com', 'Novo', false],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — login social que cria conta
|--------------------------------------------------------------------------
*/

it('[CT-21] o Google devolve um e-mail com "+" e a opção desligada: nada nasce, e o aviso diz o motivo', function (): void {
    opcaoDoMais(false);
    ligarRegistroParaOMais();
    ligarProvedor(ProvedorSocial::Google);
    Http::preventStrayRequests();

    expect(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeFalse()
        ->and(Convite::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeFalse();

    retornoDoGoogleCom('gsferroti+starter@gmail.com', 'sub-mais-bloqueado');

    $this->assertGuest();

    $titulos = collect(avisosDoFilament())->pluck('title')->filter()->values()->all();

    expect(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeFalse()
        ->and(VinculoSocial::where('sub', 'sub-mais-bloqueado')->exists())->toBeFalse()
        ->and($titulos)->not->toBe([], 'nenhum aviso foi exibido')
        ->and($titulos[0])->toContain('"+"')
        ->and($titulos[0])->not->toBe('O acesso a este sistema é por convite.')
        ->and($titulos[0])->toBe(mensagemDoMaisEsperada());
})->group('kit');

it('[CT-22] a mesma volta do Google com a opção permitindo cria a conta', function (): void {
    opcaoDoMais(true);
    ligarRegistroParaOMais();
    ligarProvedor(ProvedorSocial::Google);

    retornoDoGoogleCom('gsferroti+starter@gmail.com', 'sub-mais-permitido');

    $conta = User::where('email', 'gsferroti+starter@gmail.com')->first();

    expect($conta)->not->toBeNull();
    $this->assertAuthenticatedAs($conta);
})->group('kit');

it('[CT-23] a recusa social leva ao login, de onde a pessoa ainda entra com outro e-mail', function (): void {
    opcaoDoMais(false);
    ligarRegistroParaOMais();
    ligarProvedor(ProvedorSocial::Google);

    retornoDoGoogleCom('gsferroti+starter@gmail.com', 'sub-mesma-pessoa');
    $this->assertGuest();

    retornoDoGoogleCom('gsferroti@gmail.com', 'sub-mesma-pessoa');

    $conta = User::where('email', 'gsferroti@gmail.com')->first();

    expect($conta)->not->toBeNull()
        ->and(User::where('email', 'gsferroti+starter@gmail.com')->exists())->toBeFalse();
    $this->assertAuthenticatedAs($conta);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R10 — perfil
|--------------------------------------------------------------------------
*/

it('[CT-24] a troca de e-mail no perfil segue a opção e só olha o e-mail novo', function (bool $permitido, string $atual, string $novo, string $gravado, string $nome, bool $erro): void {
    opcaoDoMais($permitido);

    $pessoa = usuarioDoKit('admin', $atual);
    $pessoa->update(['name' => 'Antigo']);

    $this->actingAs($pessoa);
    noPainelBootado('admin');

    $tela = Livewire::test(InformacoesPessoais::class)
        ->fillForm(['name' => 'Novo', 'email' => $novo, 'current_password' => 'password'])
        ->call('submit');

    $depois = $pessoa->fresh();

    expect($depois?->email)->toBe($gravado)
        ->and($depois?->name)->toBe($nome);
    afirmarErroDoCampoEmail($tela, comErro: $erro);
})->with([
    'bloqueado: sem "+" para "+"'        => [false, 'gsferroti@gmail.com', 'gsferroti+1@gmail.com', 'gsferroti@gmail.com', 'Antigo', true],
    'bloqueado: sem trocar'              => [false, 'gsferroti+starter@gmail.com', 'gsferroti+starter@gmail.com', 'gsferroti+starter@gmail.com', 'Novo', false],
    'bloqueado: de "+" para outro "+"'   => [false, 'gsferroti+starter@gmail.com', 'gsferroti+starter-kit@gmail.com', 'gsferroti+starter@gmail.com', 'Antigo', true],
    'bloqueado: para sem "+"'            => [false, 'gsferroti@gmail.com', 'gsferroti2@gmail.com', 'gsferroti2@gmail.com', 'Novo', false],
    'permitido: sem "+" para "+"'        => [true, 'gsferroti@gmail.com', 'gsferroti+1@gmail.com', 'gsferroti+1@gmail.com', 'Novo', false],
])->group('kit');

it('[CT-25] a troca para "+" no perfil de cada painel segue a opção', function (string $painel, string $papel, bool $permitido): void {
    opcaoDoMais($permitido);

    $pessoa = usuarioDoKit($papel, 'gsferroti@gmail.com');

    $this->actingAs($pessoa)->get("/{$painel}/meu-perfil")->assertOk();

    // O componente que o plugin Breezy DAQUELE painel registra como `personal_info`, depois do GET.
    $componente = Filament::getPanel($painel)->getPlugin('filament-breezy')->getRegisteredMyProfileComponents()['personal_info'];

    $tela = Livewire::test($componente)
        ->fillForm(['name' => 'Fulano', 'email' => 'gsferroti+1@gmail.com', 'current_password' => 'password'])
        ->call('submit');

    expect($pessoa->fresh()?->email)->toBe($permitido ? 'gsferroti+1@gmail.com' : 'gsferroti@gmail.com');
    afirmarErroDoCampoEmail($tela, comErro: ! $permitido);
})->with([
    'admin bloqueado' => ['admin', 'admin', false],
    'app bloqueado'   => ['app', 'panel_user', false],
    'infra bloqueado' => ['infra', 'infra', false],
    'admin permitido' => ['admin', 'admin', true],
    'app permitido'   => ['app', 'panel_user', true],
    'infra permitido' => ['infra', 'infra', true],
])->group('kit');

it('[CT-26] cada painel serve, em meu-perfil, as informações pessoais com a regra', function (string $painel, string $papel): void {
    $this->actingAs(usuarioDoKit($papel));

    $this->get("/{$painel}/meu-perfil")
        ->assertOk()
        ->assertSeeLivewire(InformacoesPessoais::class)
        ->assertDontSeeLivewire(PersonalInfo::class);
})->with([
    'admin' => ['admin', 'admin'],
    'app'   => ['app', 'panel_user'],
    'infra' => ['infra', 'infra'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R11 — a conta existente com "+" continua entrando
|--------------------------------------------------------------------------
*/

it('[CT-27] a conta existente com "+" entra com a opção desligada', function (string $via): void {
    opcaoDoMais(false);

    $conta = usuarioDoKit('panel_user', 'gsferroti+starter@gmail.com');

    if ($via === 'senha') {
        ligarLoginUnificado(false);
        Filament::setCurrentPanel('app');
        $this->get('/app/login')->assertOk();

        Livewire::test(TelaLogin::class)
            ->fillForm(['email' => 'gsferroti+starter@gmail.com', 'password' => 'password'])
            ->call('authenticate');
    } else {
        ligarProvedor(ProvedorSocial::Google);
        retornoDoGoogleCom('gsferroti+starter@gmail.com', 'sub-conta-existente');
    }

    $this->assertAuthenticatedAs($conta);

    expect(User::where('email', 'gsferroti+starter@gmail.com')->count())->toBe(1);
})->with([
    'e-mail e senha no login do /app'      => ['senha'],
    'retorno do Google com o mesmo e-mail' => ['google'],
])->group('kit');
