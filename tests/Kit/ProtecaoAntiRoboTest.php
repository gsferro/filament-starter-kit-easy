<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\Forms\Components\CampoAntiRobo;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Filament\Pages\Auth\TelaLogin;
use App\Filament\Pages\Auth\TelaRecuperarSenha;
use App\Models\Convite;
use App\Models\Role;
use App\Models\User;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\ConfiguracaoDoLogin;
use App\Support\ProvedorAntiRobo;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * A proteção anti-robô das três telas públicas — desligada, ligada, o segredo e a tela.
 *
 * Derivado de `wikis/specs/feat/recaptcha-nas-telas-publicas/recaptcha-nas-telas-publicas/04-casos-de-teste.md`
 * (R1–R10). O gate de mutantes de cada regra está lá; aqui cada caso diz, no docblock, qual
 * implementação errada ele reprova.
 *
 * **Nenhum caso sai para a rede.** `Http::preventStrayRequests()` no `beforeEach` e `Http::fake()`
 * por URL do provedor em cada caso: URL errada estoura, em vez de passar em silêncio.
 *
 * As três telas são testadas como componente Livewire — é a camada mais barata que prova
 * validação e efeito (`.ai/rules/testes.md`, "uma tela aberta não é uma tela que grava"). O que
 * só o navegador prova (o widget renderizar) fica fora, e o motivo está no `04`.
 */
beforeEach(function (): void {
    Http::preventStrayRequests();

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Liga a proteção pela CONFIG (o que o `aplicarNaConfig()` faria com o banco), com chaves de teste.
 *
 * Fica neste arquivo porque só ele usa (`.ai/rules/testes.md`).
 *
 * @param  array<string, mixed>  $sobrescrever
 */
function ligarAntiRobo(ProvedorAntiRobo $provedor = ProvedorAntiRobo::Recaptcha, array $sobrescrever = []): void
{
    config()->set('kit.login.anti_robo', array_merge([
        'habilitado'    => true,
        'provedor'      => $provedor->value,
        'chave_do_site' => 'SITE-42',
        'chave_secreta' => 'SEGREDO-DE-TESTE-42',
    ], $sobrescrever));
}

/** O provedor respondendo o que se pedir — `success` verdadeiro ou falso, ou um status de erro. */
function provedorResponde(ProvedorAntiRobo $provedor, bool $sucesso, int $status = 200): void
{
    Http::fake([
        $provedor->urlDeVerificacao() => Http::response(
            ['success' => $sucesso, 'error-codes' => $sucesso ? [] : ['invalid-input-response']],
            $status,
        ),
    ]);
}

/** Um convite pendente para o `panel_user`, e o token em claro dele. */
function convitePendente(): string
{
    return Convite::factory()->create([
        'email'   => 'convidado@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ])->enviar();
}

/** O login do /app pela tela, com ou sem token. */
function enviarLoginComAntiRobo(User $user, ?string $token): Testable
{
    Filament::setCurrentPanel('app');

    return Livewire::test(TelaLogin::class)
        ->fillForm(array_filter([
            'email'     => $user->email,
            'password'  => 'password',
            'anti_robo' => $token,
        ]))
        ->call('authenticate');
}

/** O pedido de redefinição do /app pela tela, com ou sem token. */
function pedirRedefinicao(User $user, ?string $token): Testable
{
    Filament::setCurrentPanel('app');

    return Livewire::test(TelaRecuperarSenha::class)
        ->fillForm(array_filter(['email' => $user->email, 'anti_robo' => $token]))
        ->call('request');
}

/** O aceite de convite pela tela, com ou sem token. */
function aceitarConviteComAntiRobo(string $tokenDoConvite, ?string $token): Testable
{
    Filament::setCurrentPanel('app');

    return Livewire::withQueryParams(['token' => $tokenDoConvite])
        ->test(RegistroPorConvite::class)
        ->fillForm(array_filter([
            'name'                 => 'Convidado',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
            'anti_robo'            => $token,
        ]))
        ->call('register');
}

/*
|--------------------------------------------------------------------------
| R1 — de fábrica, desligada e sem script
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — o default de verdade, sem ajuste do caso.
 *
 * `KIT_ANTI_ROBO` não está no `phpunit.xml` (conferido), então esta linha mede o
 * `config/kit.php`, e não o arnês. Mata M1.
 */
it('nasce com a proteção anti-robô desligada e o recaptcha como provedor padrão', function (): void {
    expect(ConfiguracaoDoLogin::antiRobo())->toBeNull()
        ->and(config('kit.login.anti_robo.habilitado'))->toBeFalse()
        ->and(config('kit.login.anti_robo.provedor'))->toBe('recaptcha');
})->group('kit');

/**
 * CT-03 — desligada, nenhuma das sete telas carrega script de provedor nem o campo.
 *
 * As três URLs de script asseridas em cada tela, e não só a do recaptcha: um render hook que
 * carregasse o script no `<head>` independentemente do estado (M3) seria pego aqui, e um campo
 * sempre visível com só a regra condicionada (M2) deixaria o nome `anti_robo` no HTML.
 */
it('não carrega script de provedor nenhum nas telas públicas com a proteção desligada', function (string $tela): void {
    if ($tela === 'register') {
        $tela = '/app/register?token='.convitePendente();
    }

    $html = $this->get($tela)->assertOk()->getContent();

    foreach (ProvedorAntiRobo::cases() as $provedor) {
        expect($html)->not->toContain($provedor->urlDoScript());
    }

    expect($html)->not->toContain('anti_robo');
})->with([
    '/app/login',
    '/admin/login',
    '/infra/login',
    '/app/password-reset/request',
    '/admin/password-reset/request',
    '/infra/password-reset/request',
    'register',
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — duas condições (e um provedor válido)
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — a tabela de decisão da ativação.
 *
 * As linhas 2 e 3 matam "só o interruptor decide" (M4) e "só a chave do site importa" (M7); a
 * de espaços separa `filled()` de `isset()` (M5); a do provedor inválido mata "cai no recaptcha"
 * (M6) — que com chaves do Turnstile renderizaria um widget que não abre.
 */
it('liga a proteção só com interruptor, as duas chaves e um provedor conhecido', function (bool $ligado, ?string $site, ?string $secreta, string $provedor, ?ProvedorAntiRobo $esperado): void {
    config()->set('kit.login.anti_robo', [
        'habilitado'    => $ligado,
        'provedor'      => $provedor,
        'chave_do_site' => $site,
        'chave_secreta' => $secreta,
    ]);

    expect(ConfiguracaoDoLogin::antiRobo())->toBe($esperado);
})->with([
    'interruptor desligado com tudo preenchido' => [false, 'SITE', 'SEGREDO', 'recaptcha', null],
    'chave do site vazia'                       => [true, '', 'SEGREDO', 'recaptcha', null],
    'chave secreta vazia'                       => [true, 'SITE', '', 'recaptcha', null],
    'chave secreta só de espaços'               => [true, 'SITE', '   ', 'recaptcha', null],
    'chave secreta ausente (null)'              => [true, 'SITE', null, 'recaptcha', null],
    'provedor fora da lista'                    => [true, 'SITE', 'SEGREDO', 'banana', null],
    'recaptcha completo'                        => [true, 'SITE', 'SEGREDO', 'recaptcha', ProvedorAntiRobo::Recaptcha],
    'turnstile completo'                        => [true, 'SITE', 'SEGREDO', 'turnstile', ProvedorAntiRobo::Turnstile],
    'hcaptcha completo'                         => [true, 'SITE', 'SEGREDO', 'hcaptcha', ProvedorAntiRobo::Hcaptcha],
])->group('kit');

/** CT-02 (complemento) — provedor desconhecido é o único ramo que registra, e registra o valor. */
it('registra no canal de autenticação o provedor anti-robô desconhecido', function (): void {
    $canal = espiarAutenticacao();

    ligarAntiRobo(sobrescrever: ['provedor' => 'banana']);

    expect(ConfiguracaoDoLogin::antiRobo())->toBeNull();

    $canal->shouldHaveReceived('warning', fn (string $mensagem, array $contexto): bool => str_contains($mensagem, 'desconhecido') && $contexto['provedor'] === 'banana');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — ligada, o script certo em cada tela; a secreta nunca
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — o script do provedor e a chave do site presentes, a chave secreta ausente.
 *
 * As linhas de `/admin` e `/infra` matam "esqueceu o `usingPage(TelaLogin)` nos dois painéis"
 * (M8); as três de `password-reset` matam o `usingPage(TelaRecuperarSenha)` esquecido (M9); as
 * de turnstile e hcaptcha matam o `match` trocado em `urlDoScript()` (M11). A asserção de
 * ausência é sobre o conteúdo cru e mata a view que serializa o settings inteiro (M10).
 */
it('carrega o script do provedor escolhido e nunca a chave secreta nas telas públicas', function (string $tela, ProvedorAntiRobo $provedor): void {
    ligarAntiRobo($provedor);

    if ($tela === 'register') {
        $tela = '/app/register?token='.convitePendente();
    }

    $html = $this->get($tela)->assertOk()->getContent();

    expect($html)->toContain($provedor->urlDoScript())
        ->and($html)->toContain('SITE-42')
        ->and($html)->toContain('data-anti-robo="'.$provedor->value.'"')
        ->and($html)->not->toContain('SEGREDO-DE-TESTE-42');
})->with([
    'login do app com recaptcha'          => ['/app/login', ProvedorAntiRobo::Recaptcha],
    'login do admin com recaptcha'        => ['/admin/login', ProvedorAntiRobo::Recaptcha],
    'login do infra com recaptcha'        => ['/infra/login', ProvedorAntiRobo::Recaptcha],
    'recuperação do app'                  => ['/app/password-reset/request', ProvedorAntiRobo::Recaptcha],
    'recuperação do admin'                => ['/admin/password-reset/request', ProvedorAntiRobo::Recaptcha],
    'recuperação do infra'                => ['/infra/password-reset/request', ProvedorAntiRobo::Recaptcha],
    'registro por convite'                => ['register', ProvedorAntiRobo::Recaptcha],
    'login do app com turnstile'          => ['/app/login', ProvedorAntiRobo::Turnstile],
    'login do app com hcaptcha'           => ['/app/login', ProvedorAntiRobo::Hcaptcha],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — desligada, os três formulários funcionam sem token
|--------------------------------------------------------------------------
| O `Http::preventStrayRequests()` do beforeEach é a segunda asserção destes
| três: uma regra que rodasse com o campo oculto chamaria o provedor (M13) e
| estouraria aqui.
*/

/** CT-05a — mata `required()` incondicional no login (M12). */
it('autentica sem token com a proteção desligada', function (): void {
    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, null)->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
})->group('kit');

/** CT-05b — o e-mail de redefinição sai sem token. */
it('envia o e-mail de redefinição sem token com a proteção desligada', function (): void {
    Notification::fake();

    $user = usuarioDoKit('panel_user');

    pedirRedefinicao($user, null)->assertHasNoFormErrors();

    Notification::assertSentTo($user, ResetPassword::class);
})->group('kit');

/** CT-05c — o convite é aceito sem token. */
it('aceita o convite sem token com a proteção desligada', function (): void {
    $token = convitePendente();

    aceitarConviteComAntiRobo($token, null)->assertHasNoFormErrors();

    expect(User::query()->where('email', 'convidado@example.com')->exists())->toBeTrue();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — ligada, sem token ou com token recusado, nada acontece
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — sem token o campo obrigatório reprova ANTES de qualquer chamada.
 *
 * Mata M14, o `required()` ausente: o Laravel não executa regra de closure em campo ausente, e
 * um envio sem token pularia a verificação inteira. `Http::assertNothingSent()` é a segunda
 * metade — a regra não pode nem tentar verificar um token que não existe.
 */
it('reprova o login sem token com a proteção ligada, sem chamar o provedor', function (): void {
    Http::fake();
    ligarAntiRobo();

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, null)->assertHasFormErrors(['anti_robo' => 'required']);

    $this->assertGuest();
    Http::assertNothingSent();
})->group('kit');

/**
 * CT-07 — token recusado pelo provedor reprova, por provedor, e registra sem o token.
 *
 * O provedor responde **200** com `success: false` — é assim que o Google recusa —, então
 * `successful()` sozinho deixaria passar (M16). O `$fail()` esquecido é M15. E o contexto do
 * warning é asserido SEM o token (M18): token é credencial de uso único, e o log é a trilha que
 * o `/infra` abre.
 */
it('reprova o login com token recusado pelo provedor e registra o motivo', function (ProvedorAntiRobo $provedor): void {
    $canal = espiarAutenticacao();

    ligarAntiRobo($provedor);
    provedorResponde($provedor, sucesso: false);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-ruim')->assertHasFormErrors(['anti_robo']);

    $this->assertGuest();

    $canal->shouldHaveReceived('warning', function (string $mensagem, array $contexto) use ($provedor): bool {
        return ($contexto['motivo'] ?? null) === 'token_invalido'
            && $contexto['provedor'] === $provedor->value
            && ! str_contains(json_encode($contexto), 'token-ruim')
            && ! str_contains($mensagem, 'token-ruim');
    });
})->with([
    'recaptcha' => [ProvedorAntiRobo::Recaptcha],
    'turnstile' => [ProvedorAntiRobo::Turnstile],
    'hcaptcha'  => [ProvedorAntiRobo::Hcaptcha],
])->group('kit');

/** CT-09a — a regra vale na recuperação de senha: recusado, nenhum e-mail sai (M17). */
it('não envia o e-mail de redefinição com token recusado', function (): void {
    Notification::fake();
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::Recaptcha, sucesso: false);

    $user = usuarioDoKit('panel_user');

    pedirRedefinicao($user, 'token-ruim')->assertHasFormErrors(['anti_robo']);

    Notification::assertNothingSent();
})->group('kit');

/** CT-10a — a regra vale no registro: recusado, a conta não nasce e o convite segue pendente (M17). */
it('não cria a conta do convidado com token recusado', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::Recaptcha, sucesso: false);

    $token = convitePendente();

    aceitarConviteComAntiRobo($token, 'token-ruim')->assertHasFormErrors(['anti_robo']);

    expect(User::query()->where('email', 'convidado@example.com')->exists())->toBeFalse()
        ->and(Convite::query()->sole()->aceito_em)->toBeNull();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — ligada, token aceito segue; o request ao provedor está certo
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — token aceito autentica, e a verificação foi UMA, ao endpoint do provedor, como
 * formulário, com secret, response e remoteip.
 *
 * O fake é por URL: `urlDeVerificacao()` trocada (M19) estoura o `preventStrayRequests()`.
 * `assertSent` confere o corpo (M20, secret vindo da chave do site) e o `Content-Type` (M21, o
 * Google exige form). `assertSentCount(1)` mata a verificação dupla (M22) — em produção a
 * segunda falharia, porque o token é de uso único.
 */
it('autentica com token aceito e verifica uma vez no endpoint do provedor', function (ProvedorAntiRobo $provedor): void {
    ligarAntiRobo($provedor);
    provedorResponde($provedor, sucesso: true);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-bom')->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) use ($provedor): bool {
        return $request->url() === $provedor->urlDeVerificacao()
            && $request->isForm()
            && $request['secret'] === 'SEGREDO-DE-TESTE-42'
            && $request['response'] === 'token-bom'
            && filled($request['remoteip']);
    });
})->with([
    'recaptcha' => [ProvedorAntiRobo::Recaptcha],
    'turnstile' => [ProvedorAntiRobo::Turnstile],
    'hcaptcha'  => [ProvedorAntiRobo::Hcaptcha],
])->group('kit');

/** CT-09b — token aceito, o e-mail de redefinição sai. */
it('envia o e-mail de redefinição com token aceito', function (): void {
    Notification::fake();
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::Recaptcha, sucesso: true);

    $user = usuarioDoKit('panel_user');

    pedirRedefinicao($user, 'token-bom')->assertHasNoFormErrors();

    Notification::assertSentTo($user, ResetPassword::class);
})->group('kit');

/**
 * CT-10b — token aceito, a conta nasce.
 *
 * Também é o caso de mass assignment da taxonomia: o token NÃO chega a `User::create()`
 * (`->dehydrated(false)`), e a prova é a conta criada sem erro com o campo preenchido.
 */
it('cria a conta do convidado com token aceito', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::Recaptcha, sucesso: true);

    $token = convitePendente();

    aceitarConviteComAntiRobo($token, 'token-bom')->assertHasNoFormErrors();

    expect(User::query()->where('email', 'convidado@example.com')->exists())->toBeTrue();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — provedor indisponível recusa e registra
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — conexão recusada: erro de validação (não 500), anônimo, warning com a exceção.
 *
 * Exceção não capturada viraria 500 no `call()` (M23); `catch` devolvendo `true` — "o provedor
 * caiu, deixa passar" — autenticaria (M24).
 */
it('reprova o login quando o provedor não responde, e registra a indisponibilidade', function (): void {
    $canal = espiarAutenticacao();

    ligarAntiRobo();
    Http::fake([
        ProvedorAntiRobo::Recaptcha->urlDeVerificacao() => fn () => throw new ConnectionException('Connection refused'),
    ]);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-qualquer')->assertHasFormErrors(['anti_robo']);

    $this->assertGuest();

    $canal->shouldHaveReceived('warning', fn (string $mensagem, array $contexto): bool => ($contexto['motivo'] ?? null) === 'verificacao_indisponivel'
        && ($contexto['exception'] ?? null) instanceof ConnectionException);
})->group('kit');

/** CT-12 — resposta 503 também recusa (M24). */
it('reprova o login quando o provedor responde erro de servidor', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::Recaptcha, sucesso: true, status: 503);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-qualquer')->assertHasFormErrors(['anti_robo']);

    $this->assertGuest();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R10 — o widget é redefinido depois de cada verificação
|--------------------------------------------------------------------------
*/

/** CT-17 — verificação feita (aqui, recusada) dispara o evento de redefinição. Mata M32. */
it('manda o widget se redefinir depois de verificar o token', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::Recaptcha, sucesso: false);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-ruim')->assertDispatched(CampoAntiRobo::EVENTO_REDEFINIR);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — a chave secreta é segredo, e as quatro propriedades alcançam a config
|--------------------------------------------------------------------------
*/

/**
 * CT-13 — o oráculo de três pontas: payload criptograma, leitura legível, config alcançada.
 *
 * Sem cifra a primeira falha (M26); `addEncrypted` sem o nome em `encrypted()` faz a leitura
 * devolver ciphertext (M25 — o defeito do Google até a v0.19.3); decifra que não chega ao
 * consumidor, a terceira.
 */
it('grava a chave secreta cifrada, devolve legivel e alcanca a config', function (): void {
    $settings                                = app(SettingsDoKit::class);
    $settings->login_anti_robo_chave_secreta = 'SEGREDO-ANTI-ROBO-42';
    $settings->save();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    expect((string) configuracaoGravada('login_anti_robo_chave_secreta'))->not->toContain('SEGREDO-ANTI-ROBO-42')
        ->and(app(SettingsDoKit::class)->login_anti_robo_chave_secreta)->toBe('SEGREDO-ANTI-ROBO-42')
        ->and(config('kit.login.anti_robo.chave_secreta'))->toBe('SEGREDO-ANTI-ROBO-42');
})->group('kit');

/**
 * CT-14a — a chave secreta fora do HTML da tela de configurações (M27).
 *
 * `assertOk()` junto da ausência: a página que estoura em 500 também não contém o segredo.
 */
it('nao serializa a chave secreta anti-robo no html da tela de configuracoes', function (): void {
    $settings                                = app(SettingsDoKit::class);
    $settings->login_anti_robo_habilitado    = true;
    $settings->login_anti_robo_chave_do_site = 'SITE-42';
    $settings->login_anti_robo_chave_secreta = 'SEGREDO-ANTI-ROBO-42';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    $resposta = $this->get('/admin/configuracoes-do-kit');

    $resposta->assertOk();

    expect($resposta->getContent())->not->toContain('SEGREDO-ANTI-ROBO-42');
})->group('kit');

/** CT-14b — o save que não tocou na chave a mantém (M29, `->dehydrated()` sem condição). */
it('mantem a chave secreta anti-robo quando o campo fica em branco', function (): void {
    $settings                                = app(SettingsDoKit::class);
    $settings->login_anti_robo_habilitado    = true;
    $settings->login_anti_robo_chave_secreta = 'SEGREDO-ANTI-ROBO-42';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Outro Nome'])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);

    expect(app(SettingsDoKit::class)->login_anti_robo_chave_secreta)->toBe('SEGREDO-ANTI-ROBO-42')
        ->and(configuracaoGravada('nome_da_aplicacao'))->toBe('Outro Nome');
})->group('kit');

/** CT-14c — preencher substitui a guardada (M28, `->dehydrated(false)` fixo). */
it('substitui a chave secreta anti-robo quando o campo e preenchido', function (): void {
    $settings                                = app(SettingsDoKit::class);
    $settings->login_anti_robo_habilitado    = true;
    $settings->login_anti_robo_chave_secreta = 'ANTIGA';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_anti_robo_chave_secreta' => 'NOVA'])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);

    expect(app(SettingsDoKit::class)->login_anti_robo_chave_secreta)->toBe('NOVA');
})->group('kit');

/** CT-15 — cada propriedade alcança a chave de config dela (M30, a linha do mapa esquecida). */
it('alcanca a chave de config de cada propriedade anti-robo', function (string $propriedade, mixed $valor, string $chave): void {
    $settings                 = app(SettingsDoKit::class);
    $settings->{$propriedade} = $valor;
    $settings->save();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    expect(config($chave))->toBe($valor);
})->with([
    'habilitado'    => ['login_anti_robo_habilitado', true, 'kit.login.anti_robo.habilitado'],
    'provedor'      => ['login_anti_robo_provedor', 'turnstile', 'kit.login.anti_robo.provedor'],
    'chave do site' => ['login_anti_robo_chave_do_site', 'site-x', 'kit.login.anti_robo.chave_do_site'],
    'chave secreta' => ['login_anti_robo_chave_secreta', 'segredo-x', 'kit.login.anti_robo.chave_secreta'],
])->group('kit');

/**
 * CT-16 — a tela grava as quatro pela seção, e a proteção entra no ar no request seguinte.
 *
 * O `Então` não olha o banco: olha o ponto único depois do realinhamento (o boot do próximo
 * request) e a tela de login do visitante. Mata M31 (`Select` sem as opções do enum — o
 * `Rule::in()` recusaria) e o toggle que grava e não governa.
 */
it('liga a proteção pela tela de configurações e ela chega à tela de login', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            'login_anti_robo_habilitado'    => true,
            'login_anti_robo_provedor'      => 'turnstile',
            'login_anti_robo_chave_do_site' => 'SITE-DA-TELA',
            'login_anti_robo_chave_secreta' => 'SEGREDO-DA-TELA',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    expect(ConfiguracaoDoLogin::antiRobo())->toBe(ProvedorAntiRobo::Turnstile);

    // A tela de login é do VISITANTE: autenticado, `/app/login` redireciona para o painel.
    auth()->logout();

    $html = $this->get('/app/login')->assertOk()->getContent();

    expect($html)->toContain(ProvedorAntiRobo::Turnstile->urlDoScript())
        ->and($html)->toContain('SITE-DA-TELA')
        ->and($html)->not->toContain('SEGREDO-DA-TELA');
})->group('kit');

/** CT-16 (visibilidade) — os três campos seguem o interruptor. */
it('abre os campos anti-robo conforme o interruptor', function (bool $ligado): void {
    $this->actingAs(usuarioDoKit('admin'));

    $componente = Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_anti_robo_habilitado' => $ligado]);

    foreach (['login_anti_robo_provedor', 'login_anti_robo_chave_do_site', 'login_anti_robo_chave_secreta'] as $campo) {
        $ligado
            ? $componente->assertSchemaComponentVisible($campo)
            : $componente->assertSchemaComponentHidden($campo);
    }
})->with(['desligado' => [false], 'ligado' => [true]])->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — a página nova veste o layout sem vazá-lo
|--------------------------------------------------------------------------
*/

/** CT-18 — o par da rule `.ai/rules/auth.md`: o layout está na recuperação e não vaza para o painel. */
it('veste a recuperação de senha com o layout de autenticação sem vazá-lo para o painel', function (): void {
    $this->get('/admin/password-reset/request')
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false);

    $this->actingAs(usuarioDoKit('admin'));

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', escape: false);
})->group('kit');
