<?php

use App\Filament\Admin\Resources\Convites\Pages\CreateConvite;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ConviteDeAcesso;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;

/**
 * Convite de acesso — a única porta pela qual alguém de fora vira usuário.
 *
 * O que estes casos travam, em ordem de importância: a rota `/app/register` NUNCA responde
 * a quem não traz token válido (é a única coisa entre ela e um cadastro aberto), o token
 * não vaza para log nem para auditoria, e o papel nasce no contexto que
 * `User::canAccessPanel()` exige.
 *
 * Os casos de contexto de papel COM tenancy estão em `tests/Tenancy/ConviteTenancyTest.php`
 * — só lá a coluna `model_has_roles.team_id` existe.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function usuarioDoKit(string $papel, string $email = 'user@example.com'): User
{
    $user = User::create(['name' => 'Teste', 'email' => $email, 'password' => 'password']);

    $user->assignRole($papel);

    return $user;
}

/**
 * Cria um convite pendente e devolve o par [convite, token em claro].
 *
 * `enviar()` é o único ponto do sistema em que o token em claro é acessível fora do
 * e-mail, e os testes são o consumidor legítimo disso.
 *
 * @return array{0: Convite, 1: string}
 */
function conviteCom(string $papel, ?Tenant $tenant = null, ?string $email = null): array
{
    $convite = Convite::factory()->create([
        'email'     => $email ?? 'convidado@example.com',
        'role_id'   => Role::findByName($papel)->getKey(),
        'tenant_id' => $tenant?->getKey(),
    ]);

    return [$convite, $convite->enviar()];
}

/**
 * O aceite completo, pela tela.
 *
 * O token vai pela QUERY STRING (`withQueryParams`), e não pelo construtor do componente:
 * quem o lê é `mount()`, por `request()->query('token')` — é assim que o link do e-mail
 * chega.
 */
function aceitarConvite(string $token, string $nome = 'Fulano', ?string $email = null): Testable
{
    Filament::setCurrentPanel('app');

    return Livewire::withQueryParams(['token' => $token])
        ->test(RegistroPorConvite::class)
        ->fillForm(array_filter([
            'name'                 => $nome,
            'email'                => $email,
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ]))
        ->call('register');
}

/** Espia só o channel `autenticacao`; os outros continuam reais. */
function espiarAutenticacao(): LoggerInterface
{
    $canal = Mockery::spy(LoggerInterface::class);

    Log::partialMock()->shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    return $canal;
}

it('cria convite pela tela e dispara a notificacao', function (): void {
    Notification::fake();

    $master = usuarioDoKit('master_global');
    Filament::setCurrentPanel('admin');
    $this->actingAs($master);

    Livewire::test(CreateConvite::class)
        ->fillForm([
            'email'   => 'novo@example.com',
            'role_id' => Role::findByName('panel_user')->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('convites', ['email' => 'novo@example.com', 'aceito_em' => null]);

    Notification::assertSentOnDemand(
        ConviteDeAcesso::class,
        fn ($notification, array $channels, $notifiable): bool => $notifiable->routes['mail'] === 'novo@example.com',
    );

    $convite = Convite::where('email', 'novo@example.com')->firstOrFail();

    // Token e prazo provam que o afterCreate() chamou enviar() — e não só que a linha
    // nasceu. Que a coluna guarda o HASH é CT-08, onde o token em claro está em mãos.
    expect($convite->token)->not->toBeEmpty()
        ->and($convite->expira_em?->isFuture())->toBeTrue()
        ->and($convite->convidado_por_id)->toBe($master->id);
});

/**
 * O caso central da feature: sem ele, a guarda do `mount()` pode desaparecer num refactor
 * e nada acusa. O caso "sem token nenhum" vem junto porque `blank($token)` é o primeiro
 * `return null` de `Convite::valido()` — um branch próprio.
 */
it('recusa registro com token inexistente', function (): void {
    $antes = User::count();
    $login = Filament::getPanel('app')->getLoginUrl();

    $this->get('/app/register?token='.Str::random(64))->assertRedirect($login);
    $this->get('/app/register')->assertRedirect($login);

    expect(User::count())->toBe($antes);
});

it('recusa registro com convite expirado', function (): void {
    [$convite, $token] = conviteCom('panel_user');

    $convite->forceFill(['expira_em' => now()->subMinute()])->save();

    // A MESMA resposta de CT-02: quem tem o link não descobre se o token não existe ou
    // se venceu. Ver ADR-02, decisão 4.
    $this->get("/app/register?token={$token}")
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    expect(User::where('email', $convite->email)->exists())->toBeFalse()
        ->and($convite->fresh()?->aceito_em)->toBeNull();
});

it('recusa reuso do convite e loga sem expor o token', function (): void {
    $canal = espiarAutenticacao();

    [$convite, $token] = conviteCom('panel_user');
    $convite->forceFill(['aceito_em' => now()])->save();

    $this->get("/app/register?token={$token}")
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    /*
     * O context NÃO pode conter o token em forma nenhuma — nem em claro, nem hasheado,
     * nem em prefixo. É a tradução literal da regra LGPD de config/logging.php:80-81, no
     * arquivo que o Logs Explorer do /infra exibe na tela. Sem este caso, um `$context`
     * "mais completo" acrescentado por boa intenção vazaria a credencial.
     */
    $canal->shouldHaveReceived('warning')
        ->withArgs(function (string $mensagem, array $contexto) use ($token): bool {
            $serializado = (string) json_encode($contexto);

            return str_starts_with($mensagem, '[RegistroPorConvite@mount]')
                && $contexto['motivo'] === 'convite_invalido'
                && filled($contexto['ip'])
                && ! str_contains($serializado, $token)
                && ! str_contains($serializado, hash('sha256', $token));
        })
        ->once();
});

it('aceita o convite e cria o usuario com o papel', function (): void {
    [$convite, $token] = conviteCom('panel_user', email: 'aceito@example.com');

    aceitarConvite($token)->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', ['email' => 'aceito@example.com', 'name' => 'Fulano']);

    $novo = User::where('email', 'aceito@example.com')->firstOrFail();

    // Sem `permission.teams` não há coluna de team: o contexto é travado de verdade em
    // CT-11 e CT-12, na suíte de tenancy.
    $this->assertDatabaseHas(config('permission.table_names.model_has_roles', 'model_has_roles'), [
        'model_id' => $novo->id,
    ]);

    expect($novo->hasRole('panel_user'))->toBeTrue()
        // O que prova que o convite entregou ACESSO, e não só um registro.
        ->and($novo->canAccessPanel(Filament::getPanel('app')))->toBeTrue()
        ->and($convite->fresh()?->aceito_em)->not->toBeNull()
        ->and(Hash::check('segredo-bem-longo-123', (string) $novo->password))->toBeTrue();
});

/**
 * A autoridade é `mutateFormDataBeforeRegister()`, NÃO o `->disabled()` do campo: campo
 * desabilitado é apresentação, e estado de Livewire chega do cliente. Sem este caso,
 * alguém "simplifica" removendo o mutate porque "o campo já está travado".
 */
it('ignora o email enviado pelo formulario e usa o do convite', function (): void {
    [, $token] = conviteCom('panel_user', email: 'verdadeiro@example.com');

    aceitarConvite($token, email: 'atacante@example.com');

    $this->assertDatabaseHas('users', ['email' => 'verdadeiro@example.com']);

    expect(User::where('email', 'atacante@example.com')->exists())->toBeFalse();
});

it('reenvia com token novo e mata o anterior', function (): void {
    Notification::fake();

    [$convite, $tokenAntigo] = conviteCom('panel_user');
    $expiraAntes             = $convite->expira_em;

    $this->travel(1)->minutes();

    $tokenNovo = $convite->enviar();

    expect($tokenNovo)->not->toBe($tokenAntigo)
        ->and(Convite::valido($tokenNovo)?->is($convite))->toBeTrue()
        // O link antigo morreu: é a propriedade que ADR-04 usa para justificar
        // "reenviar em vez de editar".
        ->and(Convite::valido($tokenAntigo))->toBeNull()
        // O token em claro não está no banco — ADR-02 na prática.
        ->and($convite->fresh()?->token)->toBe(hash('sha256', $tokenNovo))
        ->and($convite->fresh()?->expira_em?->greaterThan($expiraAntes))->toBeTrue();

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2);

    $this->travelBack();

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global'));

    Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('reenviar')->table($convite))
        ->assertNotified();

    expect(Convite::valido($tokenNovo))->toBeNull();
});

it('revoga o convite e o link deixa de valer', function (): void {
    Notification::fake();

    /*
     * `audit.console` é false no kit, e a suíte roda em console: sem esta linha o
     * `Auditable::isAuditingEnabled()` (vendor/owen-it/laravel-auditing/src/Auditable.php:552-559)
     * devolve false e a trilha nunca é escrita — o teste passaria por a tabela estar
     * vazia, em vez de por o hash estar fora dela.
     */
    config(['audit.console' => true]);

    [$convite, $token] = conviteCom('panel_user');

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global'));

    Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('delete')->table($convite));

    $this->assertDatabaseMissing('convites', ['id' => $convite->id]);

    expect(Convite::valido($token))->toBeNull();

    // A revogação fecha a porta de verdade, não só some da listagem.
    $this->get("/app/register?token={$token}")
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertDatabaseHas('audits', [
        'auditable_type' => Convite::class,
        'auditable_id'   => $convite->id,
        'event'          => 'deleted',
    ]);

    // A trilha guarda quem, quando e para qual e-mail — nunca o hash da credencial:
    // `token` está fora do $fillable e AuditsFillables devolve o $fillable.
    $trilha = DB::table('audits')
        ->where('auditable_type', Convite::class)
        ->where('auditable_id', $convite->id)
        ->where('event', 'deleted')
        ->value('old_values');

    expect((string) $trilha)->not->toContain('token');
});

/**
 * Sem `Notification::fake()` de propósito: é o único caso em que `ConviteDeAcesso::toMail()`
 * realmente RENDERIZA. O mailer é `array` no phpunit.xml, então nada sai da máquina — e um
 * erro no corpo do e-mail (ou na URL montada por `Panel::route()`) aparece aqui, em vez de
 * num job falhado em produção.
 */
it('registra envio e aceite no channel autenticacao sem vazar segredo', function (): void {
    $canal = espiarAutenticacao();

    [, $token] = conviteCom('panel_user', email: 'fulano@example.com');

    $corpo = (string) Mail::mailer()->getSymfonyTransport()->messages()->first()?->getOriginalMessage()->toString();

    expect($corpo)->toContain('Aceitar convite');

    aceitarConvite($token)->assertHasNoFormErrors();

    $semSegredo = function (array $contexto) use ($token): bool {
        $serializado = (string) json_encode($contexto);

        return ! str_contains($serializado, $token)
            && ! str_contains($serializado, hash('sha256', $token))
            // E-mail sempre mascarado (`Str::mask($email, '*', 3)`).
            && ! str_contains($serializado, 'fulano@example.com')
            && $contexto['email'] === Str::mask('fulano@example.com', '*', 3);
    };

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[Convite@enviar]')
            && $contexto['role_id'] !== null
            && $contexto['papel'] === 'panel_user'
            && $contexto['painel'] === 'app'
            && filled($contexto['expira_em'])
            && $semSegredo($contexto))
        ->once();

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[Convite@aceitar]')
            && filled($contexto['user_id'])
            && array_key_exists('contexto_papel', $contexto)
            && $semSegredo($contexto))
        ->once();
});

/**
 * O par é obrigatório e a ordem importa (`.ai/rules/auth.md:13`): um caso só passaria
 * mesmo com o layout vazando para TODA página Filament do processo — o bug que a
 * redeclaração de `$layout` previne e que já matou a página de 2FA do Breezy.
 */
it('veste o layout do auth designer sem vazar para as outras paginas', function (): void {
    [, $token] = conviteCom('panel_user');

    $this->get("/app/register?token={$token}")
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false)
        // A mídia acusa ADR-06: registro ligado fora do AuthDesignerPlugin faz a config
        // cair em `new AuthPageConfig` e a imagem sumir, sem erro nenhum.
        ->assertSee('images/auth/login.svg', escape: false);

    $this->actingAs(usuarioDoKit('panel_user', 'outro@example.com'))
        ->get('/app')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', escape: false);
});

/**
 * As duas asserções juntas são o ponto: sem a segunda, o teste passaria por o registro
 * estar desligado. Cobre o efeito colateral de `Login::getSubheading()`
 * (`vendor/filament/filament/src/Auth/Pages/Login.php:445-455`).
 */
it('nao oferece cadastro na tela de login', function (): void {
    $painel = Filament::getPanel('app');

    $this->get('/app/login')
        ->assertOk()
        ->assertDontSee($painel->getRegistrationUrl(), escape: false)
        ->assertDontSee('Cadastre-se');

    expect($painel->hasRegistration())->toBeTrue();
});

/**
 * O INVERSO do que este caso provava até a v0.11.0.
 *
 * Antes, e-mail já cadastrado era recusado em duas pontas — no formulário de convite e no
 * `aceitar()`. Isso fazia do convite uma parede no caso mais comum de SaaS multi-tenant: a
 * consultora que atende dois clientes não podia ser convidada pelo segundo.
 *
 * Agora o endereço com conta vira OFERTA DE ACESSO: nenhuma conta nova, a pessoa confirma
 * autenticada, e é vinculada com o papel do convite. Ver
 * `wikis/specs/main/convite-para-usuario-existente/`.
 */
it('convida quem ja tem conta em vez de recusar', function (): void {
    Notification::fake();

    $existente = User::factory()->create(['email' => 'existente@example.com']);

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global'));

    // Ponta 1 — o formulário ACEITA o endereço que já tem conta.
    Livewire::test(CreateConvite::class)
        ->fillForm([
            'email'   => 'existente@example.com',
            'role_id' => Role::findByName('panel_user')->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Convite::where('email', 'existente@example.com')->exists())->toBeTrue();

    // Ponta 2 — o aceite VINCULA em vez de lançar, e não cria segunda conta.
    [$convite, $token] = conviteCom('panel_user', email: 'corrida@example.com');
    $corrida           = User::factory()->create(['email' => 'corrida@example.com']);

    $aceito = $convite->aceitar(['name' => 'Ignorado', 'password' => 'ignorada']);

    expect($aceito->getKey())->toBe($corrida->getKey())
        ->and(User::where('email', 'corrida@example.com')->count())->toBe(1)
        ->and($convite->fresh()?->aceito_em)->not->toBeNull();

    // Ponta 3 — a barreira que substituiu a recusa: o convite é de OUTRO endereço.
    [$deOutro] = conviteCom('panel_user', email: 'dona@example.com');

    expect(fn () => $deOutro->aceitarComoUsuarioExistente($existente))
        ->toThrow(RuntimeException::class);

    expect($deOutro->fresh()?->aceito_em)->toBeNull();
});
