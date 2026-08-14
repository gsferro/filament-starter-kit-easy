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

/**
 * Os tokens em claro dos lembretes já disparados, na ordem dos disparos.
 *
 * Pelo OBJETO da notificação, NUNCA pelo corpo renderizado: a URL em e-mail sofre quebra de
 * linha do quoted-printable, e um `preg_match` no HTML falharia por formatação em vez de por
 * comportamento.
 *
 * Todo destinatário on-demand cai no mesmo balde do fake (`AnonymousNotifiable::getKey()`
 * devolve null), então a ordem aqui é a ordem real dos envios.
 *
 * @return list<string>
 */
function tokensDosLembretes(): array
{
    $tokens = [];

    Notification::assertSentOnDemand(
        ConviteDeAcesso::class,
        function (ConviteDeAcesso $notificacao) use (&$tokens): bool {
            if ($notificacao->lembrete) {
                $tokens[] = $notificacao->token;
            }

            return true;
        },
    );

    return $tokens;
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

/*
|--------------------------------------------------------------------------
| Lembretes de convite
|--------------------------------------------------------------------------
| O convite cobra a si mesmo: `kit:convites-lembrar` manda um SEGUNDO link,
| paralelo ao do envio. O que estes casos travam, em ordem de importância: o
| `orWhere` de `Convite::valido()` NÃO escapa dos filtros de estado (CT-04), o
| link original continua valendo depois de qualquer lembrete (CT-02, CT-03), e
| sai UM lembrete por convite por execução (CT-01).
|
| Ver `wikis/specs/main/lembretes-de-convite/`.
*/

/**
 * CT-01 — cinco linhas, quatro propriedades travadas.
 *
 * As duas primeiras linhas são a diferença entre lembrete e spam: um erro de sinal na
 * comparação de datas mandaria lembrete no mesmo minuto do convite. `[[3, 5], 6, [1, 2]]` é
 * a propriedade central de ADR-03 e a razão de conferir o contador ENTRE execuções: os dois
 * prazos venceram e ainda assim sai um lembrete por execução. `[[3], 6, [1, 1]]` prova
 * ADR-05 — o teto é `count($dias)`, não uma segunda chave de config.
 *
 * Dois convites idênticos, para o caso também provar que eles andam juntos: nenhum recebe
 * dois lembretes enquanto o outro recebe zero.
 *
 * @param  list<int>  $dias
 * @param  list<int>  $esperados  o contador depois de CADA execução
 */
it('lembra conforme o cronograma, um lembrete por convite por execucao', function (array $dias, int $viagem, array $esperados): void {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => $dias]);

    [$a] = conviteCom('panel_user', email: 'a@example.com');
    [$b] = conviteCom('panel_user', email: 'b@example.com');

    $enviadoAntes = $a->fresh()?->enviado_em;

    $this->travel($viagem)->days();

    foreach ($esperados as $esperado) {
        $this->artisan('kit:convites-lembrar')->assertSuccessful();

        expect($a->fresh()?->lembretes_enviados)->toBe($esperado)
            ->and($b->fresh()?->lembretes_enviados)->toBe($esperado);
    }

    // Os dois envios originais mais um lembrete por convite por incremento.
    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2 + 2 * end($esperados));

    // Lembrete não é envio: o relógio de `enviado_em` não se move.
    expect($a->fresh()?->enviado_em?->equalTo($enviadoAntes))->toBeTrue();

    if (end($esperados) === 0) {
        expect($a->fresh()?->token_lembrete)->toBeNull();
    } else {
        expect($a->fresh()?->token_lembrete)->not->toBeNull();
    }
})->with([
    'nada no dia do envio'         => [[3, 5], 0, [0, 0]],
    'nada antes do primeiro prazo' => [[3, 5], 2, [0, 0]],
    'primeiro prazo vencido'       => [[3, 5], 4, [1, 1]],
    'dois prazos vencidos'         => [[3, 5], 6, [1, 2]],
    'teto = count(dias)'           => [[3], 6, [1, 1]],
]);

/**
 * CT-02 — é o caso que a feature existe para não quebrar.
 *
 * O reflexo de quem for mexer aqui é chamar `enviar()`, que rotaciona o token e renova o
 * prazo — e o e-mail que a pessoa já tem passa a dar redirect para o login. A asserção 2
 * acusa isso. Ver ADR-01.
 */
it('lembra com um link novo sem invalidar o do envio', function (): void {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    [$convite, $token] = conviteCom('panel_user');

    $hashAntes   = $convite->fresh()?->token;
    $expiraAntes = $convite->fresh()?->expira_em;

    $this->travel(4)->days();

    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    $tokenDoLembrete = tokensDosLembretes()[0] ?? '';

    expect($tokenDoLembrete)->not->toBe('')
        // 1. O lembrete carrega um token DIFERENTE.
        ->and($tokenDoLembrete)->not->toBe($token)
        // 2. O hash do envio não mudou — ninguém chamou `enviar()`.
        ->and($convite->fresh()?->token)->toBe($hashAntes)
        // 3. O prazo não foi renovado.
        ->and($convite->fresh()?->expira_em?->equalTo($expiraAntes))->toBeTrue()
        // 4. Os DOIS links abrem o mesmo convite.
        ->and(Convite::valido($token)?->is($convite))->toBeTrue()
        ->and(Convite::valido($tokenDoLembrete)?->is($convite))->toBeTrue();

    // 5. E o link do lembrete cadastra de verdade.
    aceitarConvite($tokenDoLembrete)->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', ['email' => $convite->email]);
});

/**
 * CT-03 — no máximo DOIS links vivos por convite, e o caso diz quais.
 */
it('mantem vivos apenas o link do envio e o do ultimo lembrete', function (): void {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    [$convite, $token] = conviteCom('panel_user');

    $this->travel(4)->days();
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    $this->travel(2)->days();
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    [$lembrete1, $lembrete2] = tokensDosLembretes();

    expect($lembrete1)->not->toBe($lembrete2)
        // O link do envio continua valendo depois dos dois: é a propriedade que ADR-01 compra.
        ->and(Convite::valido($token)?->is($convite))->toBeTrue()
        // Cada lembrete SOBRESCREVE `token_lembrete`: o do primeiro morreu.
        ->and(Convite::valido($lembrete1))->toBeNull()
        ->and(Convite::valido($lembrete2)?->is($convite))->toBeTrue()
        // A coluna guarda o hash, nunca o claro.
        ->and($convite->fresh()?->token_lembrete)->toBe(hash('sha256', $lembrete2));
});

/**
 * CT-04 — o CT que existe para uma linha de SQL.
 *
 * `valido()` ganhou um `orWhere` (ADR-01), e `orWhere` **sem agrupamento explícito escapa
 * dos outros filtros**: o SQL sairia como
 * `WHERE token = ? AND aceito_em IS NULL AND ... OR token_lembrete = ?`, e o `OR` parte o
 * `WHERE` inteiro — o token de lembrete passaria a valer SOZINHO, sem prazo e sem estado.
 * O sintoma é o pior possível: um convite expirado volta a ser aceitável pelo link do
 * lembrete, sem erro, sem log, e a tela simplesmente aceita.
 *
 * Cobra os TRÊS filtros de estado, um por vez, porque é isso que diz QUAL deles escapou.
 */
it('nao aceita token de lembrete de convite aceito, recusado nem expirado', function (): void {
    [$convite, $token] = conviteCom('panel_user');

    $tokenLembrete = 'x'.Str::random(63);
    $convite->forceFill(['token_lembrete' => hash('sha256', $tokenLembrete)])->save();

    // Sanidade: antes de qualquer coisa, os dois valem.
    expect(Convite::valido($token)?->is($convite))->toBeTrue()
        ->and(Convite::valido($tokenLembrete)?->is($convite))->toBeTrue();

    $login = Filament::getPanel('app')->getLoginUrl();

    $estados = [
        'aceito'   => ['aceito_em' => now()],
        'recusado' => ['aceito_em' => null, 'recusado_em' => now()],
        'expirado' => ['recusado_em' => null, 'expira_em' => now()->subMinute()],
    ];

    foreach ($estados as $estado => $colunas) {
        $convite->forceFill($colunas)->save();

        expect(Convite::valido($token))->toBeNull($estado)
            ->and(Convite::valido($tokenLembrete))->toBeNull($estado);

        // Fecha pela porta HTTP também, que é onde a falha apareceria de verdade.
        $this->get("/app/register?token={$token}")->assertRedirect($login);
        $this->get("/app/register?token={$tokenLembrete}")->assertRedirect($login);
    }
});

/**
 * CT-05 — o aceite fecha as DUAS portas.
 */
it('nao lembra convite ja aceito', function (): void {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    [$convite, $token] = conviteCom('panel_user', email: 'aceitou@example.com');

    // Um lembrete já enviado, para haver o que apagar.
    $this->travel(4)->days();
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    aceitarConvite($token)->assertHasNoFormErrors();

    $this->travel(2)->days();
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    // O envio e o lembrete de ANTES do aceite, e nada depois dele.
    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2);

    expect($convite->fresh()?->lembretes_enviados)->toBe(1)
        /*
         * Sem esta asserção, um link de lembrete continuaria pendurado num convite
         * consumido — barrado pelo `whereNull('aceito_em')` de `valido()`, mas vivo no
         * banco sem razão.
         */
        ->and($convite->fresh()?->token_lembrete)->toBeNull();
});

/**
 * CT-06 — "ela disse não" é diferente de "ela não viu".
 *
 * Sem a linha `recusado`, o kit insistiria com quem recusou: o pior comportamento possível
 * desta feature. E nenhuma coluna de status é escrita — expirado é DERIVADO de `expira_em`,
 * que é o que nos poupou do `--mark-expired` do laravel-invite-only (ADR-03).
 */
it('nao lembra convite fora de jogo', function (string $estado): void {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    [$convite] = conviteCom('panel_user');

    if ($estado === 'expirado') {
        $convite->forceFill(['expira_em' => now()->subMinute()])->save();
    } else {
        // O caminho real da wiki irmã: `recusar()` exige o dono do endereço.
        $convite->recusar(usuario($convite->email));
    }

    $this->travel(4)->days();

    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    // Só o envio original.
    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1);

    expect($convite->fresh()?->lembretes_enviados)->toBe(0)
        ->and($convite->fresh()?->token_lembrete)->toBeNull()
        ->and($convite->fresh()?->aceito_em)->toBeNull();
})->with(['expirado', 'recusado']);

/**
 * CT-07 — agora há DOIS segredos por convite, e o `autenticacao.log` é aberto na tela pelo
 * Logs Explorer do /infra. A asserção é a tradução literal da regra de
 * `config/logging.php:80-81`.
 */
it('registra a execucao no channel autenticacao sem vazar token', function (): void {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    $canal = espiarAutenticacao();

    [, $token] = conviteCom('panel_user', email: 'fulano@example.com');
    conviteCom('panel_user', email: 'outro@example.com');

    $this->travel(4)->days();

    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    $tokens = tokensDosLembretes();

    expect($tokens)->toHaveCount(2);

    $semSegredo = function (array $contexto) use ($token, $tokens): bool {
        $serializado = (string) json_encode($contexto);

        foreach ([$token, ...$tokens] as $segredo) {
            if (str_contains($serializado, $segredo) || str_contains($serializado, hash('sha256', $segredo))) {
                return false;
            }
        }

        // E-mail sempre mascarado (`Str::mask($email, '*', 3)`).
        return ! str_contains($serializado, 'fulano@example.com');
    };

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[Convite@lembrar]')
            && filled($contexto['convite_id'])
            && filled($contexto['enviado_em'])
            && filled($contexto['expira_em'])
            && $contexto['lembretes_enviados'] === 1
            && $semSegredo($contexto))
        ->twice();

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[KitConvitesLembrar@handle]')
            && $contexto['total'] === 2
            && $contexto['dias'] === [3, 5]
            && $semSegredo($contexto))
        ->once();

    // Convite não devido não gera linha: repetir a execução produz só o resumo, com total 0.
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[KitConvitesLembrar@handle]')
            && $contexto['total'] === 0)
        ->once();

    Log::shouldHaveReceived('channel')->with('autenticacao');
});

/**
 * CT-08 — o único caso que exercita o CORPO do e-mail de lembrete.
 *
 * **Sem `Notification::fake()` de propósito**: é o único em que `toMail()` renderiza. O
 * mailer é `array` (`phpunit.xml:41`), então nada sai da máquina. Um erro no ternário do
 * assunto ou no `url()` só apareceria como job falhado em produção.
 */
it('manda o e-mail de lembrete com assunto proprio', function (): void {
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    conviteCom('panel_user');

    $this->travel(4)->days();

    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    $mensagens = Mail::mailer()->getSymfonyTransport()->messages();

    expect($mensagens)->toHaveCount(2);

    $envio    = $mensagens->first()?->getOriginalMessage();
    $lembrete = $mensagens->last()?->getOriginalMessage();

    expect($envio?->getSubject())->toStartWith('Você foi convidado')
        ->and($lembrete?->getSubject())->toStartWith('Lembrete:');

    // Quoted-printable quebra a cada 76 colunas, inclusive no meio da URL.
    $corpo = quoted_printable_decode((string) $lembrete?->toString());

    expect($corpo)->toContain('Aceitar convite')
        ->and($corpo)->toContain('Este é um lembrete')
        ->and($corpo)->toContain('/app/register?token=');
});

/**
 * CT-09 — o caso de ADR-02.
 *
 * Se o intervalo contasse de `created_at`, a última execução mandaria um "lembrete" no mesmo
 * dia do reenvio, e em duas execuções o teto se esgotaria — os lembretes do envio que
 * importa nunca sairiam, sem erro nenhum no caminho.
 */
it('reinicia o relogio de lembretes quando o convite e reenviado', function (): void {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    [$convite, $token] = conviteCom('panel_user');

    $this->travel(6)->days();
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    $tokenDoLembrete = tokensDosLembretes()[0] ?? '';

    $tokenNovo = $convite->enviar();

    expect($convite->fresh()?->lembretes_enviados)->toBe(0)
        ->and($convite->fresh()?->token_lembrete)->toBeNull()
        // Ao segundo: a coluna é `timestamp` e não guarda os microssegundos que `now()` tem.
        ->and($convite->fresh()?->enviado_em?->format('Y-m-d H:i:s'))->toBe(now()->format('Y-m-d H:i:s'))
        /*
         * Os DOIS links anteriores morreram — é o que mantém verdadeira a promessa da modal
         * de Reenviar ("o link anterior deixa de funcionar"): os dois, não só o do envio.
         */
        ->and(Convite::valido($token))->toBeNull()
        ->and(Convite::valido($tokenDoLembrete))->toBeNull()
        ->and(Convite::valido($tokenNovo)?->is($convite))->toBeTrue();

    // O relógio recomeçou: no mesmo instante do reenvio não há lembrete devido.
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    expect($convite->fresh()?->lembretes_enviados)->toBe(0);

    // 1 envio + 1 lembrete + 1 reenvio.
    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 3);
});

/**
 * CT-10 — a ordem é o ponto.
 *
 * O convite estragado tem `id` menor, então é o primeiro do chunk (`chunkById` ordena por
 * id). Sem o `try/catch`, ele derrubaria a execução e o convite bom ficaria sem lembrete em
 * TODA execução, para sempre — starvation silenciosa.
 *
 * **Sem `Notification::fake()`**: com o fake nada monta o destinatário no Symfony Mailer, e
 * o endereço inválido não estouraria — o caso não testaria nada. O mailer é `array`.
 */
it('nao deixa um convite estragado derrubar o lote', function (): void {
    config(['kit.convites.lembretes_dias' => [3, 5]]);

    $canal = espiarAutenticacao();

    // 1º — endereço inválido: o Symfony Mailer lança ao montar o destinatário. Não pode
    //      nascer por `conviteCom()`, porque `enviar()` estouraria na hora.
    $estragado = Convite::factory()->create([
        'email'   => 'sem-arroba',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ]);
    $estragado->forceFill(['enviado_em' => now()->subDays(4)])->save();

    // 2º — anterior à migration: `enviado_em` nulo, e nunca foi enviado.
    $antigo = Convite::factory()->create([
        'email'   => 'antigo@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ]);

    // 3º — o bom.
    [$bom] = conviteCom('panel_user', email: 'novo@example.com');

    $this->travel(4)->days();

    // Sucesso, e não FAILURE: um cron que sai com erro por causa de um endereço inválido
    // gera alarme falso todo dia.
    $this->artisan('kit:convites-lembrar')->assertSuccessful();

    expect($bom->fresh()?->lembretes_enviados)->toBe(1)
        // Sem `enviado_em` o kit não sabe de quando contar, e a linha fica fora do lote.
        ->and($antigo->fresh()?->lembretes_enviados)->toBe(0)
        // A escrita acontece ANTES do `notify()`, de propósito: endereço permanentemente
        // quebrado sai do lote em vez de ser tentado todo dia (ADR-03).
        ->and($estragado->fresh()?->lembretes_enviados)->toBe(1);

    // O lembrete do convite bom saiu de verdade.
    $destinos = Mail::mailer()->getSymfonyTransport()->messages()
        ->flatMap(fn ($mensagem) => array_map(
            fn ($endereco) => $endereco->getAddress(),
            $mensagem->getEnvelope()->getRecipients(),
        ));

    expect($destinos)->toContain('novo@example.com');

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[KitConvitesLembrar@handle]')
            && $contexto['convite_id'] === $estragado->id
            && $contexto['exception'] instanceof Throwable
            && $contexto['email'] === Str::mask('sem-arroba', '*', 3))
        ->once();
});

/**
 * CT-11 — o agendamento nasce ligado (ADR-04), então o comando precisa ser inerte numa
 * instalação nova. E a chave vazia desliga a FEATURE, não só o cronograma: o convite da
 * segunda metade está devido, e ainda assim nada sai.
 */
it('termina com sucesso sem convite pendente e com os lembretes desligados', function (): void {
    Notification::fake();

    $canal = espiarAutenticacao();

    // 1ª metade — banco vazio, com o default de config (sem override).
    $this->artisan('kit:convites-lembrar')
        ->expectsOutputToContain('Nenhum convite pendente')
        ->assertSuccessful();

    Notification::assertNothingSent();

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[KitConvitesLembrar@handle]')
            && $contexto['total'] === 0)
        ->once();

    // 2ª metade — convite DEVIDO, mas a lista vazia desliga a feature.
    config(['kit.convites.lembretes_dias' => []]);

    [$convite] = conviteCom('panel_user');

    $this->travel(4)->days();

    $this->artisan('kit:convites-lembrar')
        ->expectsOutputToContain('desligados')
        ->assertSuccessful();

    // Só o envio original.
    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1);

    expect($convite->fresh()?->lembretes_enviados)->toBe(0)
        ->and($convite->fresh()?->token_lembrete)->toBeNull();
});
