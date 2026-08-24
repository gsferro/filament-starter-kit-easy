<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Filament\Pages\Auth\TelaLogin;
use App\Models\Convite;
use App\Models\Role;
use App\Models\User;
use App\Providers\Filament\AppPanelProvider;
use App\Support\RegistroAberto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Registro aberto no /app — a segunda porta pela qual alguém de fora vira usuário.
 *
 * A primeira é o convite, coberta por `ConviteTest`. O que estes casos travam, em ordem de
 * importância:
 *
 * 1. com a opção DESLIGADA (o default), `/app/register` sem token continua recusando — ou seja,
 *    a feature é inerte até alguém ligá-la de propósito;
 * 2. token inválido **nunca** cai no modo aberto, mesmo com a opção ligada (é a única coisa
 *    entre `?token=lixo` e uma porta pública que não passa pelo throttle da recusa);
 * 3. quem entra por aqui recebe UM papel e leva 403 em `/admin` e `/infra`;
 * 4. enquanto pendente de aprovação, não entra em painel NENHUM;
 * 5. o caminho do convite não muda.
 *
 * Os casos com organização (`?org=`) e o de "usuário comum não aprova" estão em
 * `tests/Tenancy/RegistroAbertoTenancyTest.php` — o primeiro porque exige tenancy, o segundo
 * porque o papel `admin_app` só existe naquela suíte (`.ai/rules/testes.md`).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Liga o registro aberto, e opcionalmente as duas opções que dependem dele. */
function ligarRegistroAberto(bool $aprovacaoManual = false, bool $verificarEmail = false): void
{
    config([
        'kit.registro.habilitado'       => true,
        'kit.registro.aprovacao_manual' => $aprovacaoManual,
        'kit.registro.verificar_email'  => $verificarEmail,
    ]);
}

/**
 * O cadastro completo pelo formulário, no modo ABERTO — sem token na query string.
 *
 * Espelha o `aceitarConvite()` de `ConviteTest`, e a diferença é justamente o que se testa: lá
 * o token vai por `withQueryParams`, aqui não vai nada.
 */
function registrarAberto(string $email = 'novo@example.com', string $nome = 'Fulano'): Testable
{
    Filament::setCurrentPanel('app');

    /*
     * Desloga antes, e isto não é higiene: `Register::mount()` do Filament começa com
     * `if (Filament::auth()->check()) { redirect()->intended(...) }` (`Register.php:57-63`), e
     * um cadastro bem-sucedido termina autenticado (`:108`). Sem o logout, a SEGUNDA chamada
     * desta função no mesmo caso monta um componente que já redirecionou — `$this->form` nunca
     * é preenchido e o `fillForm()` morre em `getDefaultTestingSchemaName() on null`, uma
     * mensagem que não tem nada a ver com a causa. Cada chamada é um visitante novo, que é
     * exatamente o que o caso do throttle (CT-13) quer dizer.
     */
    Filament::auth()->logout();

    return Livewire::test(RegistroPorConvite::class)
        ->fillForm([
            'name'                 => $nome,
            'email'                => $email,
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');
}

/*
|--------------------------------------------------------------------------
| R9 — a configuração é lida por um ponto único, e o default é false
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — o guardião de ADR-02: a troca para o Settings tem de continuar sendo UM arquivo.
 *
 * Sem este caso, um `config('kit.registro.*')` acrescentado por conveniência em qualquer
 * consumidor passa despercebido, e no dia do rebase a feature fica metade no Settings e metade
 * no `.env` — sem erro nenhum para acusar.
 *
 * O filtro de comentário é obrigatório em asserção de AUSÊNCIA (`.ai/rules/testes.md`): os
 * arquivos do kit citam o que proíbem, e é lá que está escrito o porquê. Sem o filtro, o
 * comentário do `config/kit.php` e o docblock do `RegistroAberto` reprovam o caso.
 */
it('le as opcoes de registro por um ponto unico', function (): void {
    $pontoUnico = realpath(app_path('Support/RegistroAberto.php'));

    $arquivos = collect(File::allFiles(app_path()))
        ->filter(fn (SplFileInfo $arquivo): bool => $arquivo->getExtension() === 'php')
        ->reject(fn (SplFileInfo $arquivo): bool => $arquivo->getRealPath() === $pontoUnico);

    $infratores = $arquivos->filter(function (SplFileInfo $arquivo): bool {
        // Comentário fora antes de afirmar ausência — /* */, // e docblock.
        $codigo = (string) preg_replace(
            ['~/\*.*?\*/~s', '~//[^\n]*~'],
            '',
            (string) file_get_contents((string) $arquivo->getRealPath()),
        );

        return str_contains($codigo, "config('kit.registro")
            || str_contains($codigo, 'config("kit.registro');
    })->map(fn (SplFileInfo $arquivo): string => $arquivo->getFilename())->values();

    expect($infratores)->toBeEmpty(
        'Leitura de kit.registro fora de RegistroAberto: '.$infratores->implode(', ')
    );
});

/**
 * CT-02 — a pendência não é gravável por formulário.
 *
 * Mesmo padrão de `ConviteUsuarioExistenteTest` para `email_verified_at`. É o par ESTRUTURAL;
 * o comportamental, pela tela, é CT-16.
 */
it('mantem a pendencia de aprovacao fora do mass assignment', function (): void {
    expect(in_array('aprovacao_pendente', (new User)->getFillable(), true))->toBeFalse();
});

/** CT-26 — as três opções nascem desligadas. O `phpunit.xml` não fixa nenhuma delas. */
it('nasce com as tres opcoes de registro desligadas', function (string $metodo): void {
    expect(RegistroAberto::{$metodo}())->toBeFalse();
})->with([
    'registro aberto'     => ['habilitado'],
    'aprovação manual'    => ['exigirAprovacao'],
    'validação de e-mail' => ['exigirVerificacaoDeEmail'],
]);

/*
|--------------------------------------------------------------------------
| R1 — sem token, o registro só existe quando a opção está ligada
|--------------------------------------------------------------------------
*/

/** CT-03 — o default: a visita sem token termina no login, como sempre terminou. */
it('recusa registro sem token quando a opcao esta desligada', function (): void {
    $this->get('/app/register')
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    expect(User::count())->toBe(0);
});

/**
 * CT-04 — com a opção ligada, o formulário aparece e o e-mail é EDITÁVEL.
 *
 * "Habilitado e vazio" é a diferença observável entre os dois modos: no convite o campo vem
 * preenchido e desabilitado. É esta asserção que pega o ramo do convite vazando para o aberto.
 */
it('oferece o formulario de cadastro quando a opcao esta ligada', function (): void {
    ligarRegistroAberto();

    Filament::setCurrentPanel('app');

    Livewire::test(RegistroPorConvite::class)
        ->assertFormFieldEnabled('email')
        ->assertSchemaStateSet(['email' => null]);
});

/**
 * CT-05 — a espinha de R1: token inválido continua recusando MESMO com a opção ligada.
 *
 * Se o garfo fosse "não achei convite válido, então cai no modo aberto", `?token=lixo` seria
 * uma segunda porta para o cadastro aberto — e justamente a que não passa pelo throttle de log
 * de `recusar()`. Este caso é o único que distingue as duas implementações.
 */
it('recusa token invalido mesmo com o registro aberto ligado', function (): void {
    ligarRegistroAberto();

    $this->get('/app/register?token=nao-existe-este-token')
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    expect(User::count())->toBe(0);
});

/** CT-20b — a recusa continua deixando rastro depois do garfo novo. */
it('registra a recusa de cadastro sem convite no canal de autenticacao', function (): void {
    $canal = espiarAutenticacao();

    $this->get('/app/register')
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[RegistroPorConvite@mount]')
            && $contexto['motivo'] === 'convite_invalido'
            && filled($contexto['ip']))
        ->once();
});

/*
|--------------------------------------------------------------------------
| R2 — o caminho do convite não muda
|--------------------------------------------------------------------------
| A partição "registro desligado" NÃO está aqui de propósito: ela é exatamente
| `ConviteTest::'aceita o convite e cria o usuario com o papel'`, que já roda sob
| o default. O que esta feature introduz é a COEXISTÊNCIA.
*/

/** CT-06 — o convite é aceito com o registro aberto LIGADO. */
it('aceita convite com o registro aberto ligado', function (): void {
    ligarRegistroAberto();

    $convite = Convite::factory()->create([
        'email'   => 'convidado@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ]);
    $token = $convite->enviar();

    Filament::setCurrentPanel('app');

    Livewire::withQueryParams(['token' => $token])
        ->test(RegistroPorConvite::class)
        ->fillForm([
            'name'                 => 'Convidada',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $novo = User::where('email', 'convidado@example.com')->firstOrFail();

    expect($novo->hasRole('panel_user'))->toBeTrue()
        ->and($convite->fresh()?->aceito_em)->not->toBeNull();
});

/**
 * CT-07 — o convite vence o formulário na escolha do e-mail, com a opção ligada.
 *
 * Regressão dirigida ao mutante que a coexistência introduz: tornar
 * `mutateFormDataBeforeRegister()` condicional é exatamente onde se pode inverter a condição e
 * deixar o formulário escolher o e-mail de um convite.
 */
it('mantem o email do convite mesmo com o registro aberto ligado', function (): void {
    ligarRegistroAberto();

    $convite = Convite::factory()->create([
        'email'   => 'convidado@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ]);
    $token = $convite->enviar();

    Filament::setCurrentPanel('app');

    Livewire::withQueryParams(['token' => $token])
        ->test(RegistroPorConvite::class)
        ->fillForm([
            'name'                 => 'Convidada',
            'email'                => 'outro@example.com',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');

    expect(User::where('email', 'convidado@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'outro@example.com')->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| R3 — um papel, e nada além dele
|--------------------------------------------------------------------------
*/

/** CT-08 — aprovação automática: entra no /app, com exatamente um papel. */
it('cria o cadastro com um unico papel e acesso ao painel de negocio', function (): void {
    ligarRegistroAberto();

    registrarAberto('aberto@example.com')->assertHasNoFormErrors();

    $novo = User::where('email', 'aberto@example.com')->firstOrFail();

    expect($novo->roles)->toHaveCount(1)
        ->and($novo->hasRole(RegistroAberto::papel()))->toBeTrue()
        ->and($novo->aprovacao_pendente)->toBeFalse();

    $this->actingAs($novo)->get('/app')->assertSuccessful();
});

/**
 * CT-09 — RQ-05 na forma mais forte: os painéis de administração respondem 403.
 *
 * `Esquema do Cenário` e não amostragem: é a técnica escalada declarada no perfil de derivação.
 * Amostrar painel deixaria justamente o não amostrado sem barreira.
 */
it('nega ao cadastro aberto os paineis de administracao', function (string $painel): void {
    ligarRegistroAberto();

    registrarAberto('aberto@example.com');

    $novo = User::where('email', 'aberto@example.com')->firstOrFail();

    $this->actingAs($novo)->get($painel)->assertForbidden();
})->with([
    'administração da instalação' => ['/admin'],
    'infraestrutura'              => ['/infra'],
]);

/**
 * CT-10 — a barreira vale para o chamador DIRETO, não só para a tela.
 *
 * É a reprodução fiel de um job, comando ou seeder chamando `registrar()`. Cenário que passa
 * pela tela mede o contexto, não a fronteira: ele ficaria verde com a atribuição de papel
 * movida para dentro da página. Ver `.ai/rules/filament.md`.
 */
it('da um papel so quando o registro e chamado fora da tela', function (): void {
    ligarRegistroAberto();

    $novo = RegistroAberto::registrar([
        'name'     => 'Direto',
        'email'    => 'direto@example.com',
        'password' => 'segredo-bem-longo-123',
    ]);

    expect($novo->roles)->toHaveCount(1)
        ->and($novo->hasRole(RegistroAberto::papel()))->toBeTrue();
});

/** CT-10b — e com a porta fechada, o chamador direto é recusado. */
it('recusa o registro chamado fora da tela com a opcao desligada', function (): void {
    expect(fn (): User => RegistroAberto::registrar([
        'name'     => 'Direto',
        'email'    => 'direto@example.com',
        'password' => 'segredo-bem-longo-123',
    ]))->toThrow(RuntimeException::class);

    expect(User::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| R4 — pendente não entra em painel nenhum
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — os três painéis, todos 403.
 *
 * Este é o caso que reprova se a guarda de pendência for posta DEPOIS do atalho do
 * `master_global` em `canAccessPanel()`, ou se for esquecida em um painel.
 */
it('nega todos os paineis ao cadastro pendente de aprovacao', function (string $painel): void {
    ligarRegistroAberto(aprovacaoManual: true);

    registrarAberto('pendente@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();

    expect($pendente->aprovacao_pendente)->toBeTrue()
        ->and($pendente->roles)->toHaveCount(0);

    $this->actingAs($pendente)->get($painel)->assertForbidden();
})->with([
    'painel de negócio'           => ['/app'],
    'administração da instalação' => ['/admin'],
    'infraestrutura'              => ['/infra'],
]);

/**
 * CT-12 — @premissa a pessoa não fica autenticada, e é avisada.
 *
 * A página do Filament termina em `Filament::auth()->login($user)` (`Register.php:108`). Sem
 * desfazer isso, a sessão fica autenticada para alguém que não pode nada, e o próximo request
 * vira 403 sem explicação.
 *
 * A asserção da mensagem casa o SENTIDO, não a redação: o texto não está no requisito.
 */
it('nao autentica o cadastro pendente e avisa que ele aguarda aprovacao', function (): void {
    ligarRegistroAberto(aprovacaoManual: true);

    registrarAberto('pendente@example.com')->assertNotified();

    $this->assertGuest();

    expect(User::where('email', 'pendente@example.com')->exists())->toBeTrue();
});

/** CT-15 — o pendente aparece no filtro, e o aprovado não. */
it('lista somente os pendentes no filtro de aprovacao', function (): void {
    ligarRegistroAberto(aprovacaoManual: true);
    registrarAberto('pendente@example.com');

    ligarRegistroAberto();
    registrarAberto('ativo@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();
    $ativo    = User::where('email', 'ativo@example.com')->firstOrFail();

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->filterTable('aprovacao_pendente')
        ->assertCanSeeTableRecords([$pendente])
        ->assertCanNotSeeTableRecords([$ativo]);
});

/**
 * CT-16 — o par COMPORTAMENTAL de CT-02: salvar pela tela não aprova em silêncio.
 *
 * CT-02 assere que a coluna está fora do `$fillable`; este passa pelo formulário de verdade. Um
 * é estrutural, o outro é a consequência — e é o segundo que reprova se alguém acrescentar um
 * campo de pendência ao form.
 */
it('nao aprova o cadastro pendente ao salvar a edicao', function (): void {
    ligarRegistroAberto(aprovacaoManual: true);
    registrarAberto('pendente@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    // `getRouteKey()` e não `getKey()`: `App\Traits\TemUuid` faz o uuid ser a chave de rota, e
    // o id numérico devolve `No query results for model [App\Models\User]` no route binding.
    Livewire::test(EditUser::class, ['record' => $pendente->getRouteKey()])
        ->fillForm(['name' => 'Nome Novo'])
        ->call('save')
        ->assertHasNoFormErrors();

    $depois = $pendente->fresh();

    expect($depois?->name)->toBe('Nome Novo')
        ->and($depois?->aprovacao_pendente)->toBeTrue()
        ->and($depois?->roles)->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| R5 — a aprovação libera, é idempotente e exige quem pode
|--------------------------------------------------------------------------
*/

/** CT-19 — @premissa a aprovação libera o painel de negócio. */
it('libera o painel de negocio ao aprovar o cadastro', function (): void {
    ligarRegistroAberto(aprovacaoManual: true);
    registrarAberto('pendente@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('aprovar')->table($pendente))
        ->assertNotified();

    $depois = $pendente->fresh();

    expect($depois?->aprovacao_pendente)->toBeFalse()
        ->and($depois?->hasRole(RegistroAberto::papel()))->toBeTrue();

    $this->actingAs($depois)->get('/app')->assertSuccessful();
});

/** CT-20 — a aprovação deixa rastro, sem senha e com o e-mail mascarado. */
it('registra a aprovacao no canal de autenticacao sem expor o email', function (): void {
    ligarRegistroAberto(aprovacaoManual: true);
    registrarAberto('pendente@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();
    $executor = usuarioDoKit('master_global', 'master@example.com');

    $canal = espiarAutenticacao();
    $this->actingAs($executor);

    $pendente->aprovar();

    $canal->shouldHaveReceived('info')
        ->withArgs(function (string $mensagem, array $contexto) use ($pendente, $executor): bool {
            $serializado = (string) json_encode($contexto);

            return str_starts_with($mensagem, '[User@aprovar]')
                && $contexto['alvo_id'] === $pendente->id
                && $contexto['executor_id'] === $executor->id
                && ! str_contains($serializado, 'pendente@example.com')
                && ! str_contains($serializado, 'segredo-bem-longo-123');
        })
        ->once();
});

/**
 * CT-21 — idempotência ancorada no AGREGADO PERSISTIDO.
 *
 * "Tem exatamente um papel depois de duas execuções sobre o mesmo registro" é o que falsifica o
 * mutante "aprovar atribui o papel sem verificar se já tem". Ancorar no retorno da chamada
 * passaria por construção.
 */
it('aprova duas vezes sem duplicar papel', function (): void {
    ligarRegistroAberto(aprovacaoManual: true);
    registrarAberto('pendente@example.com');

    $pendente = User::where('email', 'pendente@example.com')->firstOrFail();

    $pendente->aprovar();
    $pendente->aprovar();

    $depois = $pendente->fresh();

    expect($depois?->roles)->toHaveCount(1)
        ->and($depois?->aprovacao_pendente)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| R6 — a verificação de e-mail é opcional, e o convite nunca a dispara
|--------------------------------------------------------------------------
*/

/**
 * CT-17 — com a opção ligada, o cadastro aberto nasce SEM validar.
 *
 * É este estado que faz o vendor enviar o pedido de validação:
 * `Register::sendEmailVerificationNotification()` só desiste quando o modelo não implementa o
 * contrato ou quando `hasVerifiedEmail()` já é verdade (`Register.php:163-169`). As duas
 * asserções cobrem exatamente essas duas condições.
 *
 * **Lacuna declarada — o ENVIO em si não é asserido aqui, e não por escolha de escopo.**
 * `sendEmailVerificationNotification()` monta a URL com `Filament::getVerifyEmailUrl($user)`,
 * que resolve a rota `filament.app.auth.email-verification.verify`. Essa rota nasce no BOOT do
 * painel (`vendor/filament/filament/routes/web.php:75-84`), e `config()` ajustado dentro do
 * caso chega tarde — o cenário morre em `Route [...] not defined`, que é defeito de arnês e não
 * de código. Tentado e recusado: (a) declarar a rota à mão no teste, que é fabricar encanamento
 * de framework para "provar" um efeito do framework; (b) `refreshApplication()` com a env var
 * setada, que derruba o SQLite `:memory:` no meio do caso.
 *
 * O que fica coberto sem ela: CT-22b prova que o painel liga a exigência (e portanto as rotas)
 * quando a opção está ligada, e CT-18/CT-22 provam a direção que importa para a segurança — que
 * NADA é enviado quando não deve.
 */
it('cria o cadastro sem email validado quando a opcao esta ligada', function (): void {
    ligarRegistroAberto(verificarEmail: true);

    RegistroAberto::registrar([
        'name'     => 'Verificar',
        'email'    => 'verificar@example.com',
        'password' => 'segredo-bem-longo-123',
    ]);

    $novo = User::where('email', 'verificar@example.com')->firstOrFail();

    expect($novo->email_verified_at)->toBeNull()
        ->and($novo->hasVerifiedEmail())->toBeFalse()
        // Mata o mutante "o contrato foi removido de User": sem ele o vendor pula o envio
        // sempre, e a opção inteira vira decoração.
        ->and($novo)->toBeInstanceOf(MustVerifyEmail::class);
});

/** CT-18 — com a opção desligada, ninguém recebe pedido de validação. */
it('nao pede validacao de email quando a opcao esta desligada', function (): void {
    Notification::fake();

    ligarRegistroAberto();

    registrarAberto('sem-verificar@example.com');

    $novo = User::where('email', 'sem-verificar@example.com')->firstOrFail();

    expect($novo->email_verified_at)->not->toBeNull()
        ->and($novo->hasVerifiedEmail())->toBeTrue();

    Notification::assertNothingSent();
});

/**
 * CT-22 — o aceite de convite NUNCA dispara validação, mesmo com a opção ligada.
 *
 * É o caso que sustenta ADR-05: o convidado nasce validado porque `Convite::aceitar()` grava a
 * coluna (o token já provou posse do endereço), e o vendor pula o envio para quem já validou
 * (`Register.php:167-169`). Sem esta asserção, ligar a verificação passaria a mandar e-mail em
 * todo aceite de convite.
 */
it('nao pede validacao de email a quem aceita convite', function (): void {
    Notification::fake();

    ligarRegistroAberto(verificarEmail: true);

    $convite = Convite::factory()->create([
        'email'   => 'convidado@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ]);
    $token = $convite->enviar();

    Filament::setCurrentPanel('app');

    Livewire::withQueryParams(['token' => $token])
        ->test(RegistroPorConvite::class)
        ->fillForm([
            'name'                 => 'Convidada',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register');

    $novo = User::where('email', 'convidado@example.com')->firstOrFail();

    expect($novo->hasVerifiedEmail())->toBeTrue();

    Notification::assertNotSentTo($novo, VerifyEmail::class);
});

/**
 * CT-22b — o painel só EXIGE validação quando a opção está ligada.
 *
 * Medido onde a decisão é tomada, e não pela rota: o registro das rotas de confirmação acontece
 * no BOOT do painel (`vendor/filament/filament/routes/web.php:75-84`, sob
 * `if ($panel->hasEmailVerification())`), e `config()` ajustado no teste chega tarde. Montar o
 * painel pelo próprio provider mede a mesma condição que a rota consome, sem exigir um
 * `TestCase` e uma suíte novos só para um cenário.
 *
 * Closure no primeiro parâmetro não resolveria: `filled(Closure)` é sempre `true`, e a rota
 * nasceria sempre.
 */
it('exige validacao de email no painel de negocio somente com a opcao ligada', function (bool $ligada, bool $esperado): void {
    config(['kit.registro.verificar_email' => $ligada]);

    $painel = (new AppPanelProvider($this->app))->panel(Panel::make());

    expect($painel->hasEmailVerification())->toBe($esperado)
        ->and($painel->isEmailVerificationRequired())->toBe($esperado);
})->with([
    'ligada'    => [true, true],
    'desligada' => [false, false],
]);

/*
|--------------------------------------------------------------------------
| R8 — a porta pública é limitada
|--------------------------------------------------------------------------
*/

/**
 * CT-13 — o limite de tentativas do formulário, na borda.
 *
 * A borda é 2, e é o valor EFETIVO lido do vendor: `rateLimit(2)` por IP
 * (`Register.php:72-78`) mais 2 por e-mail (`:129-148`). O throttle é o do Filament de
 * propósito (ADR-09) — o mesmo que o aceite de convite já herda; escrever um próprio
 * duplicaria o mecanismo com outra chave e outro número.
 *
 * O limite por e-mail usa o `RateLimiter` no store de cache, que no `phpunit.xml` é `array` —
 * por processo. É isso que torna este caso escrevível.
 */
it('barra a terceira tentativa de cadastro na mesma janela', function (): void {
    ligarRegistroAberto();

    registrarAberto('um@example.com')->assertHasNoFormErrors();
    registrarAberto('dois@example.com')->assertHasNoFormErrors();

    registrarAberto('tres@example.com');

    // A terceira não cria conta: o vendor devolve `null` de `register()` antes da transação.
    expect(User::where('email', 'tres@example.com')->exists())->toBeFalse()
        ->and(User::count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| R1/R12 — o link "Cadastre-se" reflete a opção
|--------------------------------------------------------------------------
*/

/**
 * CT-04b — o login oferece o link só quando existe caminho aberto.
 *
 * Os dois lados importam: com a opção desligada o link levaria toda visita a uma tela que
 * recusa (affordance para lugar nenhum); com ela ligada, esconder o link seria o defeito
 * espelhado — a porta aberta que ninguém acha.
 */
it('oferece o link de cadastro no login somente com o registro ligado', function (bool $ligado, bool $temLink): void {
    config(['kit.registro.habilitado' => $ligado]);

    Filament::setCurrentPanel('app');

    $subtitulo = Livewire::test(TelaLogin::class)->instance()->getSubheading();

    expect($subtitulo !== null)->toBe($temLink);
})->with([
    'ligado'    => [true, true],
    'desligado' => [false, false],
]);
