<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as TelaDeConfiguracoes;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Http\Middleware\ExigirEmailVerificado;
use App\Models\User;
use App\Providers\Filament\AppPanelProvider;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\RegistroAberto;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/**
 * A exigência de e-mail validado no /app — a chave que era de BOOT e virou de REQUEST.
 *
 * ## O que estes casos travam, e por que eles existem separados
 *
 * `RegistroAbertoTest` cobre a porta de entrada: quem se cadastra, com que papel, pendente ou
 * não. Aqui o assunto é outro e mais amplo — a exigência alcança **todo** usuário do /app, venha
 * ele de cadastro aberto, de convite ou da tela de usuários. É por isso que ela tem arquivo
 * próprio em vez de mais uma seção lá.
 *
 * A dívida que estes casos fecham, em ordem de importância:
 *
 * 1. a decisão é tomada POR REQUEST — o valor gravado na tela vale no request seguinte, e não no
 *    próximo deploy (era exatamente isto que o quality gate da v0.19.1 reprovou);
 * 2. desligada é desligada: ninguém é barrado, nem em HTML nem em JSON;
 * 3. a rota de destino existe, senão ligar a opção troca a tela por um 500;
 * 4. o que NÃO regride: convite, `/admin` e `/infra`.
 *
 * Ver wikis/specs/feat/verificacao-de-email-editavel/.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| Personas
|--------------------------------------------------------------------------
| `usuarioDoKit()` usa `User::create()`, e `email_verified_at` está fora do
| `$fillable` — então o usuário do helper nasce SEM e-mail validado. Isso é
| conveniente aqui (é a persona central) e é uma armadilha em qualquer caso
| que precise do oposto, daí a segunda função existir.
*/

/** Usuário do /app sem `email_verified_at` — a persona que a exigência alcança. */
function usuarioSemEmailValidado(string $email = 'sem-validar@example.com'): User
{
    return usuarioDoKit('panel_user', $email);
}

/** O mesmo usuário, com o endereço já validado. */
function usuarioComEmailValidado(string $email = 'validado@example.com'): User
{
    $user = usuarioDoKit('panel_user', $email);

    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
}

/** Liga ou desliga a exigência pela CONFIG — o caminho curto, para os casos de middleware. */
function exigenciaDeEmail(bool $ligada): void
{
    config(['kit.registro.verificar_email' => $ligada]);
}

/**
 * Liga ou desliga a exigência pelo BANCO, e alinha a config como o próximo request faria.
 *
 * É o caminho REAL da tela, e a diferença importa: `exigenciaDeEmail()` mediria só o middleware,
 * deixando a linha do `mapaDeConfiguracao()` sem oráculo — que é o defeito silencioso que a rule
 * `.ai/rules/settings.md` nomeia. O `aplicarNaConfig()` à mão não contorna o mecanismo: é
 * literalmente o que o `KitServiceProvider::boot()` faz no request seguinte, e no
 * `RefreshDatabase` o boot já aconteceu antes de a tabela existir.
 */
function exigenciaDeEmailGravada(bool $ligada): void
{
    $settings                           = app(SettingsDoKit::class);
    $settings->registro_verificar_email = $ligada;
    $settings->save();

    alinharConfiguracoesDoKit();
}

/*
|--------------------------------------------------------------------------
| R1 — o barramento é decidido a cada request
|--------------------------------------------------------------------------
| A tabela de decisão é opção × `email_verified_at` × `expectsJson`. As duas
| linhas com a opção desligada não colapsam a coluna do JSON por asserção: a
| revisão adversarial mostrou uma implementação plausível que lê a opção só no
| ramo HTML e responde 403 em JSON com a exigência DESLIGADA — o que quebraria
| todo Livewire do /app no default do kit. Por isso as quatro combinações estão
| instanciadas.
*/

/** CT-01 / CT-02 / CT-03 — as quatro combinações da tabela, em HTML. */
it('barra no painel de negocio somente com a exigencia ligada e o email nao validado', function (bool $ligada, bool $validado, bool $entra): void {
    exigenciaDeEmail($ligada);

    $user = $validado ? usuarioComEmailValidado() : usuarioSemEmailValidado();

    $resposta = $this->actingAs($user)->get('/app');

    if ($entra) {
        $resposta->assertSuccessful()->assertSeeLivewire(Dashboard::class);

        return;
    }

    $resposta->assertRedirect(route('filament.app.auth.email-verification.prompt'));
})->with([
    'ligada + sem validar'      => [true, false, false],
    'ligada + validado'         => [true, true, true],
    'desligada + sem validar'   => [false, false, true],
    'desligada + validado'      => [false, true, true],
]);

/**
 * CT-04 — requisição que espera JSON recebe 403, não um HTML de redirecionamento.
 *
 * É a sutileza do `EnsureEmailIsVerified` do Laravel (`:36-38`) e a razão de herdar dele em vez
 * de reimplementar: as requisições AJAX do Livewire dentro do painel esperam JSON, e um
 * redirecionamento devolvido a elas quebra a tela sem erro no servidor.
 *
 * A segunda linha é o achado da revisão adversarial: com a exigência DESLIGADA — o default do
 * kit — o JSON tem de passar. A implementação que lê a opção só depois de checar
 * `expectsJson()` responde 403 aqui, e nenhum cenário de HTML a distingue.
 */
it('responde 403 a requisicao json somente com a exigencia ligada', function (bool $ligada, int $status): void {
    exigenciaDeEmail($ligada);

    $this->actingAs(usuarioSemEmailValidado())
        ->getJson('/app')
        ->assertStatus($status);
})->with([
    'ligada'    => [true, 403],
    'desligada' => [false, 200],
]);

/**
 * CT-01b — o middleware vale para TODA rota de página do painel, não só para a inicial.
 *
 * A revisão adversarial apontou que todos os cenários batiam na mesma URL, e que a justificativa
 * do corte ("o middleware vem de `getRouteMiddleware()`") era derivada do plano — a suíte
 * importava a hipótese que devia testar. Duas rotas de classes diferentes: uma `Page` do kit e a
 * `Dashboard` do vendor.
 */
it('barra em qualquer pagina do painel de negocio, nao so na inicial', function (string $rota): void {
    exigenciaDeEmail(true);

    $this->actingAs(usuarioSemEmailValidado())
        ->get($rota)
        ->assertRedirect(route('filament.app.auth.email-verification.prompt'));
})->with([
    'painel inicial'     => ['/app'],
    'convites recebidos' => ['/app/convites-recebidos'],
]);

/**
 * CT-01c — a exigência NÃO depende do registro aberto estar ligado.
 *
 * Achado da revisão adversarial: `habilitado() && exigirVerificacaoDeEmail()` é leitura plausível
 * de *"ao liberar o register, abra opções se deve usar a validação de email"*, e deixaria a
 * exigência inerte em toda instalação que só usa convite — que é o default do kit. Nenhum outro
 * caso distingue as duas implementações, porque nenhum outro declara o registro FECHADO.
 *
 * É também o motivo pelo qual o toggle da tela não se esconde com o registro desligado, ao
 * contrário do vizinho "cadastro nasce pendente".
 */
it('barra mesmo com o registro aberto desligado', function (): void {
    config(['kit.registro.habilitado' => false]);
    exigenciaDeEmail(true);

    expect(RegistroAberto::habilitado())->toBeFalse();

    $this->actingAs(usuarioSemEmailValidado())
        ->get('/app')
        ->assertRedirect(route('filament.app.auth.email-verification.prompt'));
});

/**
 * CT-14 — o barramento deixa trilha, e o e-mail vai mascarado.
 *
 * Sem isto, quem liga a opção sem ler o aviso vê "o painel parou de abrir" e o suporte não tem
 * onde olhar. O caminho LIBERADO não loga de propósito — ele é todo request de todo usuário do
 * /app, e log ali é ruído que esconde o sinal.
 */
it('registra o barramento no canal de autenticacao sem expor o email', function (): void {
    exigenciaDeEmail(true);

    $canal = espiarAutenticacao();

    $this->actingAs(usuarioSemEmailValidado('pessoa@example.com'))->get('/app');

    $canal->shouldHaveReceived('warning')
        ->withArgs(function (string $mensagem, array $contexto): bool {
            return str_contains($mensagem, '[ExigirEmailVerificado@handle]')
                && $contexto['email'] === 'pes***************'
                && $contexto['rota'] === 'filament.app.pages.dashboard';
        });
});

/**
 * CT-14b — quem passa não gera linha DESTE middleware.
 *
 * A asserção é filtrada pelo prefixo, e isto é achado de execução: o channel `autenticacao` tem
 * outros escritores (o log de autenticação e o bloqueio de sessão emitem `warning` no mesmo
 * canal em todo request bem-sucedido do /app). `shouldNotHaveReceived('warning')` cru reprovava
 * medindo os vizinhos — asserção de ausência precisa nomear o que deve estar ausente.
 */
it('nao registra barramento quando ninguem e barrado', function (): void {
    exigenciaDeEmail(false);

    $canal = espiarAutenticacao();

    $this->actingAs(usuarioSemEmailValidado())->get('/app')->assertSuccessful();

    $canal->shouldNotHaveReceived('warning', [
        Mockery::on(fn (mixed $mensagem): bool => is_string($mensagem)
            && str_contains($mensagem, '[ExigirEmailVerificado@handle]')),
        Mockery::any(),
    ]);
});

/*
|--------------------------------------------------------------------------
| R2 — a mudança na tela vale no request seguinte
|--------------------------------------------------------------------------
*/

/**
 * CT-05 / CT-06 — ligar, desligar e ligar de novo, tudo no MESMO processo.
 *
 * Três voltas, e não duas, por causa do achado da revisão adversarial: a implementação que
 * memoiza a leitura numa propriedade estática sobrevive a "liga e mede" e a "desliga e mede"
 * quando os dois moram em casos separados — o kill dependia da ordem de execução da suíte, que
 * `--filter` e `--parallel` desfazem. Num caso só, a memoização morre.
 *
 * E o valor entra pelo BANCO, não por `config()`: é isso que dá oráculo à linha do
 * `mapaDeConfiguracao()`. Sem ela o toggle grava e não governa nada, que é o defeito de 2026-08
 * desta chave exata.
 */
it('leva o valor gravado no settings ao request seguinte, nas duas direcoes', function (): void {
    $this->actingAs(usuarioSemEmailValidado());

    // O `.env` da suíte tem a exigência DESLIGADA — é o default do kit.
    expect(RegistroAberto::exigirVerificacaoDeEmail())->toBeFalse();
    $this->get('/app')->assertSuccessful();

    exigenciaDeEmailGravada(true);
    $this->get('/app')->assertRedirect(route('filament.app.auth.email-verification.prompt'));

    exigenciaDeEmailGravada(false);
    $this->get('/app')->assertSuccessful()->assertSeeLivewire(Dashboard::class);

    exigenciaDeEmailGravada(true);
    $this->get('/app')->assertRedirect(route('filament.app.auth.email-verification.prompt'));
});

/*
|--------------------------------------------------------------------------
| R1/R3 estruturais — quem está no array da rota
|--------------------------------------------------------------------------
*/

/**
 * CT-03b — RQ-03: o decisor é um middleware DO KIT, e ele está no array da rota.
 *
 * Este é o único oráculo da cláusula que exige *"um middleware proprio do kit que decida por
 * request"*. Sem ele, um decisor implementado como Closure no provider — ou o alias `verified` do
 * Filament de volta — passaria em todos os casos de comportamento. A revisão adversarial pegou a
 * ausência.
 *
 * A string tem o parâmetro porque `Panel::getEmailVerifiedMiddleware()` concatena nome e rota de
 * destino (`HasAuth.php:367-370`).
 */
it('poe o middleware do kit no array de middleware das rotas do painel de negocio', function (): void {
    $esperado = ExigirEmailVerificado::class.':filament.app.auth.email-verification.prompt';

    $rotas = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($rota): bool => in_array($esperado, $rota->gatherMiddleware(), true));

    expect($rotas)->not->toBeEmpty('Nenhuma rota carrega o middleware do kit: a decisão voltou para o boot.')
        ->and($rotas->map(fn ($rota): string => (string) $rota->getName())->all())
        ->toContain('filament.app.pages.dashboard');
});

/**
 * CT-10 — RQ-08: `/admin` e `/infra` NÃO ganham a exigência.
 *
 * `App\Models\User implements MustVerifyEmail` é contrato GLOBAL — o que protege os dois painéis
 * é só o fato de eles não pedirem verificação. Duas metades, e as duas importam: a estrutural
 * (nenhuma rota daqueles painéis carrega o middleware) e a comportamental (com a exigência
 * LIGADA, quem não validou entra). Ligada nos dois exemplos de propósito: desligada, o caso
 * passaria até com o middleware aplicado globalmente por engano.
 */
it('nao leva o middleware do kit para fora do painel de negocio', function (): void {
    $fora = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($rota): bool => collect($rota->gatherMiddleware())
            ->contains(fn (mixed $m): bool => is_string($m) && str_contains($m, 'ExigirEmailVerificado')))
        ->reject(fn ($rota): bool => str_starts_with($rota->uri(), 'app'))
        ->map(fn ($rota): string => $rota->uri())
        ->all();

    expect($fora)->toBe([], 'Rota fora do /app carregando a exigência de e-mail: '.implode(', ', $fora));
});

it('deixa os paineis de administracao entrarem sem email validado', function (string $painel, string $papel): void {
    exigenciaDeEmail(true);

    $this->actingAs(usuarioDoKit($papel))
        ->get($painel)
        ->assertSuccessful();
})->with([
    'administração da instalação' => ['/admin', 'admin'],
    'infraestrutura'              => ['/infra', 'infra'],
]);

/*
|--------------------------------------------------------------------------
| R4 — a rota de destino existe nos dois estados
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — a INVERSÃO declarada, e o motivo dela.
 *
 * O caso equivalente da wiki `registro-e-aprovacao` afirmava o oposto: com a opção desligada, o
 * painel NÃO tinha verificação e a rota não nascia. Aquela asserção era o guardião da dívida —
 * ela travava o mecanismo que tornava a chave ineditável.
 *
 * Agora as duas coisas valem nos dois estados, e é isso que impede o pior defeito possível desta
 * feature: middleware que decide por request pode redirecionar em qualquer request, e destino
 * inexistente é `RouteNotFoundException` — um 500 em vez de tela.
 */
it('mantem a tela de confirmacao no ar nos dois estados da exigencia', function (bool $ligada): void {
    exigenciaDeEmail($ligada);

    $painel = (new AppPanelProvider($this->app))->panel(Panel::make());

    expect($painel->hasEmailVerification())->toBeTrue()
        ->and($painel->isEmailVerificationRequired())->toBeTrue()
        ->and($painel->getEmailVerifiedMiddlewareName())->toBe(ExigirEmailVerificado::class)
        ->and(Route::has('filament.app.auth.email-verification.prompt'))->toBeTrue()
        ->and(Route::has('filament.app.auth.email-verification.verify'))->toBeTrue();
})->with([
    'ligada'    => [true],
    'desligada' => [false],
]);

/**
 * CT-08b — o redirecionamento chega numa tela, e não em outro redirecionamento.
 *
 * O laço era o risco levantado pela revisão adversarial, e ele não acontece por um motivo
 * verificável: a rota do prompt nasce de um `Route::get()` direto no `routes/web.php` do Filament,
 * não de `Page::registerRoutes()` — então ela não recebe `getRouteMiddleware()`, e o destino do
 * redirecionamento não é guardado pelo middleware que redireciona. `Route::has()` prova nome;
 * este caso prova ALCANCE.
 */
it('leva a uma tela que responde, e nao a outro redirecionamento', function (): void {
    exigenciaDeEmail(true);

    $this->actingAs(usuarioSemEmailValidado())
        ->followingRedirects()
        ->get('/app')
        ->assertSuccessful()
        ->assertSee(__('filament-panels::auth/pages/email-verification/email-verification-prompt.heading'));
});

/*
|--------------------------------------------------------------------------
| R3 — desligada é desligada, inclusive no envio
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — as duas direções do efeito, num par.
 *
 * A revisão adversarial apontou que a direção "aconteceu quando devia" estava delegada a um caso
 * da wiki ancestral sem ID nem arquivo — promessa, não cobertura. As duas ficam aqui, no mesmo
 * `Esquema do Cenário`, porque uma sem a outra não distingue
 * `email_verified_at = now()` incondicional de condicional.
 */
it('envia o pedido de validacao somente quando a exigencia esta ligada', function (bool $ligada, bool $enviou): void {
    Notification::fake();

    config(['kit.registro.habilitado' => true]);
    exigenciaDeEmail($ligada);

    Filament::setCurrentPanel('app');

    Livewire::test(RegistroPorConvite::class)
        ->fillForm([
            'name'                 => 'Fulano',
            'email'                => 'novo@example.com',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $novo = User::where('email', 'novo@example.com')->firstOrFail();

    expect($novo->hasVerifiedEmail())->toBe(! $ligada);

    $enviou
        ? Notification::assertSentTo($novo, VerifyEmail::class)
        : Notification::assertNotSentTo($novo, VerifyEmail::class);
})->with([
    'ligada'    => [true, true],
    'desligada' => [false, false],
]);

/*
|--------------------------------------------------------------------------
| R5 — o convite não regride
|--------------------------------------------------------------------------
*/

/**
 * CT-09 — o convidado entra com a exigência LIGADA, e não recebe e-mail.
 *
 * O aceite é precondição COM asserção própria, e não parte do `Quando`: era o achado da revisão
 * adversarial — uma falha no aceite tornaria "nenhuma notificação enviada" verdadeiro por
 * acidente. Aqui o `expect($convidado->hasVerifiedEmail())` prova que o aceite fez o seu
 * trabalho antes de o painel ser medido.
 *
 * E o aceite tem de ser o fluxo REAL: é `Convite::aceitar()` que grava `email_verified_at`
 * (`Convite.php:591`), porque o token já provou posse do endereço. Fabricar o usuário por factory
 * provaria menos — a factory grava a coluna por outro caminho.
 */
it('deixa quem vem de convite entrar sem barreira e sem email de validacao', function (): void {
    Notification::fake();

    exigenciaDeEmail(true);

    // `enviar()` devolve o token em claro — a coluna guarda o hash. Os testes são o único
    // consumidor legítimo desse retorno (ver o docblock de `ConviteFactory`).
    $token = ofertaPara('convidado@example.com')->enviar();

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

    $convidado = User::where('email', 'convidado@example.com')->firstOrFail();

    expect($convidado->hasVerifiedEmail())->toBeTrue();

    Notification::assertNotSentTo($convidado, VerifyEmail::class);

    $this->actingAs($convidado)
        ->get('/app')
        ->assertSuccessful()
        ->assertSeeLivewire(Dashboard::class);
});

/*
|--------------------------------------------------------------------------
| R7 — a opção é editável, e o valor gravado governa
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — o gate de tela de escrita: o administrador grava pela TELA.
 *
 * *Uma tela aberta não é uma tela que grava* (`.ai/rules/testes.md`). Sem este caso, o toggle
 * poderia estar ausente do schema, ou `->dehydrated(false)`, e nada acusaria.
 */
it('grava a exigencia de email pela tela de configuracoes do kit', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    noPainelBootado('admin');

    Livewire::test(TelaDeConfiguracoes::class)
        ->fillForm(['registro_verificar_email' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(SettingsDoKit::class)->registro_verificar_email)->toBeTrue();
});

/**
 * CT-11b — o campo aparece mesmo com o registro aberto DESLIGADO.
 *
 * Ao contrário de "cadastro nasce pendente", a exigência alcança todo usuário do /app. Esconder o
 * campo com o registro fechado criaria o defeito espelhado: exigência ligada e invisível, sem
 * como desligá-la pela tela. Achado da revisão adversarial.
 */
it('mostra o campo de exigencia de email mesmo com o registro aberto desligado', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    noPainelBootado('admin');

    Livewire::test(TelaDeConfiguracoes::class)
        ->fillForm(['registro_habilitado' => false])
        ->assertFormFieldVisible('registro_verificar_email')
        ->assertFormFieldHidden('registro_aprovacao_manual');
});

/**
 * CT-12 — a linha do mapa governa a config, nas DUAS partições.
 *
 * Só a partição verdadeira deixaria passar um mapa que devolve constante, ou um cast que torna
 * `"false"` truthy. Achado da revisão adversarial.
 */
it('leva a exigencia gravada para a chave de configuracao, nas duas particoes', function (bool $gravado): void {
    exigenciaDeEmailGravada($gravado);

    expect(config('kit.registro.verificar_email'))->toBe($gravado)
        ->and(RegistroAberto::exigirVerificacaoDeEmail())->toBe($gravado)
        ->and(SettingsDoKit::mapaDeConfiguracao())
        ->toHaveKey('registro_verificar_email', 'kit.registro.verificar_email');
})->with([
    'ligada'    => [true],
    'desligada' => [false],
]);

/**
 * CT-13 — a migration nova semeia do AMBIENTE, nas duas partições.
 *
 * O caso invariante de `ConfiguracoesDoKitTest` prova que existe linha; ele não prova o VALOR
 * semeado, e semear `false` literal desligaria em silêncio a barreira de quem já tinha
 * `KIT_REGISTRO_VERIFICAR_EMAIL=true` durante uma atualização. Achado da revisão adversarial.
 */
it('semeia a exigencia com o valor que o ambiente definiu', function (bool $doAmbiente): void {
    $migration = require base_path('database/settings/2026_08_25_000000_add_registro_verificar_email_to_kit_settings.php');

    $migration->down();

    config(['kit.registro.verificar_email' => $doAmbiente]);

    $migration->up();

    app()->forgetInstance(SettingsDoKit::class);

    expect(app(SettingsDoKit::class)->registro_verificar_email)->toBe($doAmbiente);
})->with([
    'ambiente ligado'    => [true],
    'ambiente desligado' => [false],
]);
