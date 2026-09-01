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
use App\Support\GerenciadorAntiRobo;
use App\Support\ProvedorAntiRobo;
use App\Support\VerificacaoAntiRobo;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Ddr\FilamentCaptcha\CaptchaManager;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * A proteção anti-robô das três telas públicas, agora sobre o `ddr/filament-captcha` — desligada,
 * ligada, os quatro provedores, o limiar do v3, o ambiente local, o segredo e a tela.
 *
 * Derivado de `wikis/specs/feat/adotar-ddr-filament-captcha/adotar-ddr-filament-captcha/04-casos-de-teste.md`
 * (R1–R10) sobre a base da wiki ancestral `recaptcha-nas-telas-publicas`. O gate de mutantes de
 * cada regra está lá; aqui cada caso diz, no docblock, qual implementação errada ele reprova.
 *
 * **Nenhum caso sai para a rede.** `Http::preventStrayRequests()` no `beforeEach` e `Http::fake()`
 * por URL do provedor em cada caso: URL errada estoura, em vez de passar em silêncio. A URL de
 * verificação vem do `config/captcha.php` do pacote — é o que o driver dele usa
 * (`vendor/ddr/filament-captcha/src/Drivers/RecaptchaV2Driver.php:28`).
 *
 * As três telas são testadas como componente Livewire — é a camada mais barata que prova
 * validação e efeito (`.ai/rules/testes.md`, "uma tela aberta não é uma tela que grava"). O que
 * só o navegador prova (o widget renderizar) fica em `tests/Browser/ProtecaoAntiRoboTest.php`.
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
function ligarAntiRobo(ProvedorAntiRobo $provedor = ProvedorAntiRobo::RecaptchaV2, array $sobrescrever = []): void
{
    config()->set('kit.login.anti_robo', array_merge([
        'habilitado'       => true,
        'local'            => false,
        'provedor'         => $provedor->value,
        'chave_do_site'    => 'SITE-42',
        'chave_secreta'    => 'SEGREDO-DE-TESTE-42',
        'pontuacao_minima' => 0.5,
    ], $sobrescrever));
}

/** O `siteverify` que o driver do pacote chama — lido da config dele, não copiado. */
function urlDeVerificacao(ProvedorAntiRobo $provedor): string
{
    return (string) config("captcha.{$provedor->value}.verify_url");
}

/** O host do script do widget, sem query string — a asserção de presença/ausência no HTML. */
function hostDoScript(ProvedorAntiRobo $provedor): string
{
    return match ($provedor) {
        ProvedorAntiRobo::RecaptchaV2,
        ProvedorAntiRobo::RecaptchaV3 => 'www.google.com/recaptcha/api.js',
        ProvedorAntiRobo::Turnstile   => 'challenges.cloudflare.com/turnstile/v0/api.js',
        ProvedorAntiRobo::Hcaptcha    => 'js.hcaptcha.com/1/api.js',
    };
}

/** O provedor respondendo o que se pedir — `success`, um status de erro, e a pontuação do v3. */
function provedorResponde(ProvedorAntiRobo $provedor, bool $sucesso, int $status = 200, ?float $pontuacao = null): void
{
    $corpo = ['success' => $sucesso, 'error-codes' => $sucesso ? [] : ['invalid-input-response']];

    if ($pontuacao !== null) {
        $corpo['score'] = $pontuacao;
    }

    Http::fake([urlDeVerificacao($provedor) => Http::response($corpo, $status)]);
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
| R4 — de fábrica, desligada, `recaptcha_v2` e sem script
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — o default de verdade, sem ajuste do caso.
 *
 * `KIT_ANTI_ROBO*` está fixado no `phpunit.xml` com o DEFAULT do `config/kit.php`, para um
 * `.env` local com a proteção ligada não vazar para a suíte — então o que esta linha mede é o
 * default declarado, nos dois lugares. Mata M10 (default ainda `recaptcha`) e M11 (nasce ligado).
 */
it('nasce com a proteção anti-robô desligada, o recaptcha v2 como provedor e 0,5 de limiar', function (): void {
    expect(ConfiguracaoDoLogin::antiRobo())->toBeNull()
        ->and(config('kit.login.anti_robo.habilitado'))->toBeFalse()
        ->and(config('kit.login.anti_robo.local'))->toBeFalse()
        ->and(config('kit.login.anti_robo.provedor'))->toBe('recaptcha_v2')
        ->and(ConfiguracaoDoLogin::pontuacaoMinimaAntiRobo())->toBe(0.5);
})->group('kit');

/**
 * CT-16 — desligada, nenhuma das sete telas carrega script de provedor nem o campo.
 *
 * As quatro URLs de script asseridas em cada tela, e não só a do recaptcha: um render hook que
 * carregasse o script no `<head>` independentemente do estado (M21) seria pego aqui, e um campo
 * sempre visível com só a regra condicionada (M20) deixaria o nome `anti_robo` no HTML.
 */
it('não carrega script de provedor nenhum nas telas públicas com a proteção desligada', function (string $tela): void {
    if ($tela === 'register') {
        $tela = '/app/register?token='.convitePendente();
    }

    $html = $this->get($tela)->assertOk()->getContent();

    foreach (ProvedorAntiRobo::cases() as $provedor) {
        expect($html)->not->toContain(hostDoScript($provedor));
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
| R3 — quatro condições, um provedor válido, e o ambiente local
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — a tabela de decisão da ativação.
 *
 * As linhas de chave vazia matam "só o interruptor decide" (M6); a de espaços separa `filled()`
 * de `isset()` (M7); a do provedor inválido mata "cai no recaptcha" (M8); a do valor legado
 * `recaptcha` prova que ele NÃO é aceito em runtime — quem tem esse valor precisa da migration
 * (ADR-04); a do v3 mata "v3 não está na lista" (M9).
 */
it('liga a proteção só com interruptor, as duas chaves e um provedor conhecido', function (bool $ligado, ?string $site, ?string $secreta, string $provedor, ?ProvedorAntiRobo $esperado): void {
    config()->set('kit.login.anti_robo', [
        'habilitado'    => $ligado,
        'local'         => false,
        'provedor'      => $provedor,
        'chave_do_site' => $site,
        'chave_secreta' => $secreta,
    ]);

    expect(ConfiguracaoDoLogin::antiRobo())->toBe($esperado);
})->with([
    'interruptor desligado com tudo preenchido' => [false, 'SITE', 'SEGREDO', 'recaptcha_v2', null],
    'chave do site vazia'                       => [true, '', 'SEGREDO', 'recaptcha_v2', null],
    'chave do site só de espaços'               => [true, '   ', 'SEGREDO', 'recaptcha_v2', null],
    'chave secreta vazia'                       => [true, 'SITE', '', 'recaptcha_v2', null],
    'chave secreta ausente (null)'              => [true, 'SITE', null, 'recaptcha_v2', null],
    'provedor fora da lista'                    => [true, 'SITE', 'SEGREDO', 'banana', null],
    'valor legado recaptcha (sem a migration)'  => [true, 'SITE', 'SEGREDO', 'recaptcha', null],
    'recaptcha v2 completo'                     => [true, 'SITE', 'SEGREDO', 'recaptcha_v2', ProvedorAntiRobo::RecaptchaV2],
    'recaptcha v3 completo'                     => [true, 'SITE', 'SEGREDO', 'recaptcha_v3', ProvedorAntiRobo::RecaptchaV3],
    'turnstile completo'                        => [true, 'SITE', 'SEGREDO', 'turnstile', ProvedorAntiRobo::Turnstile],
    'hcaptcha completo'                         => [true, 'SITE', 'SEGREDO', 'hcaptcha', ProvedorAntiRobo::Hcaptcha],
])->group('kit');

/**
 * CT-07b — em ambiente local a proteção só entra com o opt-in `local`.
 *
 * `app()['env']` é o que `app()->isLocal()` consulta; o arnês roda em `testing`, então a linha
 * "testing" prova que o interruptor local NÃO interfere fora do local (M-local-2), e as duas
 * linhas "local" matam "ignora o ambiente" (M-local-1) e "local desliga sempre" (M-local-3).
 */
it('em ambiente local só aplica a proteção com o interruptor local ligado', function (string $ambiente, bool $local, ?ProvedorAntiRobo $esperado): void {
    app()['env'] = $ambiente;

    ligarAntiRobo(sobrescrever: ['local' => $local]);

    expect(ConfiguracaoDoLogin::antiRobo())->toBe($esperado);
})->with([
    'local sem opt-in'      => ['local', false, null],
    'local com opt-in'      => ['local', true, ProvedorAntiRobo::RecaptchaV2],
    'testing sem opt-in'    => ['testing', false, ProvedorAntiRobo::RecaptchaV2],
    'production sem opt-in' => ['production', false, ProvedorAntiRobo::RecaptchaV2],
])->group('kit');

/** CT-07 (complemento) — provedor desconhecido é o único ramo que registra, e registra o valor. */
it('registra no canal de autenticação o provedor anti-robô desconhecido', function (): void {
    $canal = espiarAutenticacao();

    ligarAntiRobo(sobrescrever: ['provedor' => 'banana']);

    expect(ConfiguracaoDoLogin::antiRobo())->toBeNull();

    $canal->shouldHaveReceived('warning', fn (string $mensagem, array $contexto): bool => str_contains($mensagem, 'desconhecido') && $contexto['provedor'] === 'banana');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R1/R2 — o manager do kit alimenta o pacote com a config do kit, por request
|--------------------------------------------------------------------------
*/

/** CT-01 (binding) — o container entrega o manager do kit, e o driver sai embrulhado. Mata "bind esquecido". */
it('substitui o manager do pacote pelo do kit e embrulha o driver com a verificação do kit', function (): void {
    ligarAntiRobo(ProvedorAntiRobo::Turnstile);

    $manager = app(CaptchaManager::class);

    expect($manager)->toBeInstanceOf(GerenciadorAntiRobo::class)
        ->and($manager->driver())->toBeInstanceOf(VerificacaoAntiRobo::class)
        ->and($manager->driver()->getView())->toBe('filament-captcha::drivers.turnstile')
        ->and($manager->driver()->getSiteKey())->toBe('SITE-42');
})->group('kit');

/**
 * CT-01 — as chaves do kit chegam ao driver certo, e só a ele.
 *
 * Mata M1 (projeta sempre para o hcaptcha) e M2 (projeta para todos): o driver de OUTRO nome
 * recebe chave nula, e por isso a regra do pacote nem verifica.
 */
it('projeta as chaves do kit só para o driver ativo', function (ProvedorAntiRobo $provedor): void {
    ligarAntiRobo($provedor);

    $manager = app(CaptchaManager::class);

    expect($manager->driver()->getSiteKey())->toBe('SITE-42')
        ->and($manager->driver($provedor->value)->getSiteKey())->toBe('SITE-42')
        ->and(config("captcha.{$provedor->value}.secret"))->toBe('SEGREDO-DE-TESTE-42');

    foreach (ProvedorAntiRobo::cases() as $outro) {
        if ($outro !== $provedor) {
            expect($manager->driver($outro->value)->getSiteKey())->toBeNull();
        }
    }
})->with(ProvedorAntiRobo::cases())->group('kit');

/**
 * CT-03 — desligada, o pacote recebe chave nula MESMO com as env vars dele preenchidas.
 *
 * É a regra "uma pergunta, uma dona" (`.ai/rules/config.md`): `RECAPTCHA_V2_SITEKEY` no `.env`
 * não liga nada. Mata M3 (projeta sem `habilitado`) e o fallback para a config do pacote.
 */
it('ignora as env vars do pacote: desligada no kit, o driver não tem chave', function (): void {
    config()->set('captcha.driver', 'recaptcha_v2');
    config()->set('captcha.recaptcha_v2.sitekey', 'SITE-DO-PACOTE');
    config()->set('captcha.recaptcha_v2.secret', 'SEGREDO-DO-PACOTE');

    $driver = app(CaptchaManager::class)->driver();

    expect(ConfiguracaoDoLogin::antiRobo())->toBeNull()
        ->and($driver->getSiteKey())->toBeNull()
        ->and(config('captcha.recaptcha_v2.secret'))->toBeNull();
})->group('kit');

/** CT-05/CT-06 — o limiar do kit chega ao driver do v3; vazio no `.env` cai em 0,5, não em 0. */
it('projeta o limiar do recaptcha v3 a partir da config do kit', function (mixed $configurado, float $esperado): void {
    ligarAntiRobo(ProvedorAntiRobo::RecaptchaV3, ['pontuacao_minima' => $configurado]);

    app(CaptchaManager::class)->driver();

    expect(ConfiguracaoDoLogin::pontuacaoMinimaAntiRobo())->toBe($esperado)
        ->and(config('captcha.recaptcha_v3.score'))->toBe($esperado);
})->with([
    '0,7 configurado'         => [0.7, 0.7],
    'string numérica'         => ['0.3', 0.3],
    'vazio (chave sem valor)' => ['', 0.5],
    'ausente'                 => [null, 0.5],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — ligada, o script certo em cada tela; a secreta nunca
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — o script do provedor e a chave do site presentes, a chave secreta ausente.
 *
 * As linhas de `/admin` e `/infra` matam "esqueceu o `usingPage(TelaLogin)` nos dois painéis";
 * as três de `password-reset` matam o `usingPage(TelaRecuperarSenha)` esquecido; as de turnstile,
 * hcaptcha e v3 matam a view publicada errada. `data-anti-robo` é o seletor dos CT-B. A asserção
 * de ausência é sobre o conteúdo cru e mata a view que serializa o settings inteiro.
 */
it('carrega o script do provedor escolhido e nunca a chave secreta nas telas públicas', function (string $tela, ProvedorAntiRobo $provedor): void {
    ligarAntiRobo($provedor);

    if ($tela === 'register') {
        $tela = '/app/register?token='.convitePendente();
    }

    $html = $this->get($tela)->assertOk()->getContent();

    expect($html)->toContain(hostDoScript($provedor))
        ->and($html)->toContain('SITE-42')
        ->and($html)->toContain('data-anti-robo="'.$provedor->value.'"')
        ->and($html)->toContain('fi-fo-anti-robo')
        ->and($html)->not->toContain('SEGREDO-DE-TESTE-42');
})->with([
    'login do app com recaptcha v2'   => ['/app/login', ProvedorAntiRobo::RecaptchaV2],
    'login do admin com recaptcha v2' => ['/admin/login', ProvedorAntiRobo::RecaptchaV2],
    'login do infra com recaptcha v2' => ['/infra/login', ProvedorAntiRobo::RecaptchaV2],
    'recuperação do app'              => ['/app/password-reset/request', ProvedorAntiRobo::RecaptchaV2],
    'recuperação do admin'            => ['/admin/password-reset/request', ProvedorAntiRobo::RecaptchaV2],
    'recuperação do infra'            => ['/infra/password-reset/request', ProvedorAntiRobo::RecaptchaV2],
    'registro por convite'            => ['register', ProvedorAntiRobo::RecaptchaV2],
    'login do app com recaptcha v3'   => ['/app/login', ProvedorAntiRobo::RecaptchaV3],
    'login do app com turnstile'      => ['/app/login', ProvedorAntiRobo::Turnstile],
    'login do app com hcaptcha'       => ['/app/login', ProvedorAntiRobo::Hcaptcha],
])->group('kit');

/** CT-04 (v3) — o v3 carrega o script com `render={chave}` e sem caixa: nada de `render=explicit`. */
it('carrega o recaptcha v3 no modo invisível, com a chave do site na URL do script', function (): void {
    ligarAntiRobo(ProvedorAntiRobo::RecaptchaV3);

    $html = $this->get('/app/login')->assertOk()->getContent();

    expect($html)->toContain('recaptcha/api.js?render=SITE-42')
        ->and($html)->not->toContain('render=explicit');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 (desligada) — os três formulários funcionam sem token
|--------------------------------------------------------------------------
| O `Http::preventStrayRequests()` do beforeEach é a segunda asserção destes
| três: uma regra que rodasse com o campo oculto chamaria o provedor e
| estouraria aqui.
*/

/** CT-05a — mata `required()` incondicional no login. */
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
 * CT-12 — sem token o campo obrigatório reprova ANTES de qualquer chamada.
 *
 * Mata M14, o `required()` ausente: o Laravel não executa regra de objeto em campo ausente, e
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
 * CT-10/CT-14 — token recusado pelo provedor reprova, por provedor, e registra sem o token.
 *
 * O provedor responde **200** com `success: false` — é assim que o Google recusa —, então
 * `successful()` sozinho deixaria passar (M12). O contexto do warning é asserido SEM o token
 * (M18): token é credencial de uso único, e o log é a trilha que o `/infra` abre.
 */
it('reprova o login com token recusado pelo provedor e registra o motivo', function (ProvedorAntiRobo $provedor): void {
    $canal = espiarAutenticacao();

    ligarAntiRobo($provedor);
    provedorResponde($provedor, sucesso: false, pontuacao: $provedor->usaPontuacao() ? 0.9 : null);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-ruim')->assertHasFormErrors(['anti_robo']);

    $this->assertGuest();

    $canal->shouldHaveReceived('warning', function (string $mensagem, array $contexto) use ($provedor): bool {
        return ($contexto['motivo'] ?? null) === 'token_invalido'
            && $contexto['provedor'] === $provedor->value
            && ! str_contains(json_encode($contexto), 'token-ruim')
            && ! str_contains($mensagem, 'token-ruim');
    })->once();
})->with(ProvedorAntiRobo::cases())->group('kit');

/**
 * CT-13 — reCAPTCHA v3: `success: true` com pontuação abaixo do limiar recusa.
 *
 * Mata M15 (só olha `success`). O limiar vem da config do kit — 0,7 aqui, e 0,3 não passa.
 */
it('recusa o recaptcha v3 com pontuação abaixo do limiar do kit', function (): void {
    ligarAntiRobo(ProvedorAntiRobo::RecaptchaV3, ['pontuacao_minima' => 0.7]);
    provedorResponde(ProvedorAntiRobo::RecaptchaV3, sucesso: true, pontuacao: 0.3);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-de-robo')->assertHasFormErrors(['anti_robo']);

    $this->assertGuest();
})->group('kit');

/** CT-13 (complemento) — a pontuação NO limiar passa (`>=`, não `>`). Valor limite. */
it('aceita o recaptcha v3 com pontuação igual ao limiar', function (): void {
    ligarAntiRobo(ProvedorAntiRobo::RecaptchaV3, ['pontuacao_minima' => 0.7]);
    provedorResponde(ProvedorAntiRobo::RecaptchaV3, sucesso: true, pontuacao: 0.7);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-de-pessoa')->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
})->group('kit');

/** CT-09a — a regra vale na recuperação de senha: recusado, nenhum e-mail sai. */
it('não envia o e-mail de redefinição com token recusado', function (): void {
    Notification::fake();
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::RecaptchaV2, sucesso: false);

    $user = usuarioDoKit('panel_user');

    pedirRedefinicao($user, 'token-ruim')->assertHasFormErrors(['anti_robo']);

    Notification::assertNothingSent();
})->group('kit');

/** CT-10a — a regra vale no registro: recusado, a conta não nasce e o convite segue pendente. */
it('não cria a conta do convidado com token recusado', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::RecaptchaV2, sucesso: false);

    $token = convitePendente();

    aceitarConviteComAntiRobo($token, 'token-ruim')->assertHasFormErrors(['anti_robo']);

    expect(User::query()->where('email', 'convidado@example.com')->exists())->toBeFalse()
        ->and(Convite::query()->sole()->aceito_em)->toBeNull();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5/R8 — ligada, token aceito segue; o request ao provedor está certo
|--------------------------------------------------------------------------
*/

/**
 * CT-09 — token aceito autentica, e a verificação foi UMA, ao endpoint do provedor, como
 * formulário, com secret, response e remoteip.
 *
 * O fake é por URL: driver errado para o provedor configurado (M16) estoura o
 * `preventStrayRequests()`. `assertSent` confere o corpo (secret vindo da chave do kit) e o
 * `Content-Type` (o Google exige form). `assertSentCount(1)` mata a verificação dupla — em
 * produção a segunda falharia, porque o token é de uso único.
 */
it('autentica com token aceito e verifica uma vez no endpoint do provedor', function (ProvedorAntiRobo $provedor): void {
    ligarAntiRobo($provedor);
    provedorResponde($provedor, sucesso: true, pontuacao: $provedor->usaPontuacao() ? 0.9 : null);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-bom')->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) use ($provedor): bool {
        return $request->url() === urlDeVerificacao($provedor)
            && $request->isForm()
            && $request['secret'] === 'SEGREDO-DE-TESTE-42'
            && $request['response'] === 'token-bom'
            && filled($request['remoteip']);
    });
})->with(ProvedorAntiRobo::cases())->group('kit');

/** CT-17 (recuperação) — token aceito, o e-mail de redefinição sai. */
it('envia o e-mail de redefinição com token aceito', function (): void {
    Notification::fake();
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::RecaptchaV2, sucesso: true);

    $user = usuarioDoKit('panel_user');

    pedirRedefinicao($user, 'token-bom')->assertHasNoFormErrors();

    Notification::assertSentTo($user, ResetPassword::class);
})->group('kit');

/**
 * CT-17 (registro) — token aceito, a conta nasce.
 *
 * Também é o caso de mass assignment da taxonomia: o token NÃO chega a `User::create()`
 * (o pacote faz `->dehydrated(false)`), e a prova é a conta criada sem erro com o campo preenchido.
 */
it('cria a conta do convidado com token aceito', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::RecaptchaV2, sucesso: true);

    $token = convitePendente();

    aceitarConviteComAntiRobo($token, 'token-bom')->assertHasNoFormErrors();

    expect(User::query()->where('email', 'convidado@example.com')->exists())->toBeTrue();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5/R6 — provedor indisponível recusa e registra (falha FECHADA)
|--------------------------------------------------------------------------
*/

/**
 * CT-11/CT-15 — conexão recusada: erro de validação (não 500), anônimo, warning com a exceção.
 *
 * O driver do pacote não captura (`RecaptchaV2Driver.php:30-36`); sem o decorator a exceção
 * viraria 500 no `call()` (M13); `catch` devolvendo `true` — "o provedor caiu, deixa passar" —
 * autenticaria.
 */
it('reprova o login quando o provedor não responde, e registra a indisponibilidade', function (): void {
    $canal = espiarAutenticacao();

    ligarAntiRobo();
    Http::fake([
        urlDeVerificacao(ProvedorAntiRobo::RecaptchaV2) => fn () => throw new ConnectionException('Connection refused'),
    ]);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-qualquer')->assertHasFormErrors(['anti_robo']);

    $this->assertGuest();

    $canal->shouldHaveReceived('warning', fn (string $mensagem, array $contexto): bool => ($contexto['motivo'] ?? null) === 'verificacao_indisponivel'
        && str_contains($mensagem, 'indisponível')
        && ($contexto['exception'] ?? null) instanceof ConnectionException)->once();
})->group('kit');

/** CT-12 (5xx) — resposta 503 também recusa. */
it('reprova o login quando o provedor responde erro de servidor', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::RecaptchaV2, sucesso: true, status: 503);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-qualquer')->assertHasFormErrors(['anti_robo']);

    $this->assertGuest();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R10 (ancestral) — o widget é redefinido depois de cada verificação
|--------------------------------------------------------------------------
*/

/** Verificação feita (aqui, recusada) dispara o evento de redefinição que as views publicadas escutam. */
it('manda o widget se redefinir depois de verificar o token', function (): void {
    ligarAntiRobo();
    provedorResponde(ProvedorAntiRobo::RecaptchaV2, sucesso: false);

    $user = usuarioDoKit('panel_user');

    enviarLoginComAntiRobo($user, 'token-ruim')->assertDispatched(CampoAntiRobo::EVENTO_REDEFINIR);
})->group('kit');

/** As quatro views publicadas escutam o evento — mata a view publicada sem o `x-on` do reset. */
it('escuta o evento de redefinição em cada view publicada do pacote', function (ProvedorAntiRobo $provedor): void {
    ligarAntiRobo($provedor);

    $html = $this->get('/app/login')->assertOk()->getContent();

    expect($html)->toContain('x-on:'.CampoAntiRobo::EVENTO_REDEFINIR.'.window');
})->with(ProvedorAntiRobo::cases())->group('kit');

/*
|--------------------------------------------------------------------------
| R10 — a migration converte `recaptcha` → `recaptcha_v2` e volta
|--------------------------------------------------------------------------
*/

/**
 * CT-20/CT-21 — `down()` devolve o valor legado e apaga as propriedades novas; `up()` converte
 * e recria. Mata M26 (no-op) e M27 (`down` não reverte). `RefreshDatabase` já rodou o `up()`,
 * por isso o caso começa pelo `down()`.
 */
it('converte o provedor legado recaptcha em recaptcha_v2 na migration, e reverte no down', function (): void {
    $migration = require base_path('database/settings/2026_08_31_100000_adotar_filament_captcha_nas_kit_settings.php');

    $migration->down();

    expect(configuracaoGravada('login_anti_robo_provedor'))->toBe('recaptcha')
        ->and(configuracaoGravada('login_anti_robo_pontuacao_minima'))->toBeNull()
        ->and(configuracaoGravada('login_anti_robo_local'))->toBeNull();

    $migration->up();

    expect(configuracaoGravada('login_anti_robo_provedor'))->toBe('recaptcha_v2')
        ->and(configuracaoGravada('login_anti_robo_pontuacao_minima'))->toBe(0.5)
        ->and(configuracaoGravada('login_anti_robo_local'))->toBeFalse();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — a chave secreta é segredo, e as seis propriedades alcançam a config
|--------------------------------------------------------------------------
*/

/**
 * O oráculo de três pontas: payload criptograma, leitura legível, config alcançada.
 *
 * Sem cifra a primeira falha; `addEncrypted` sem o nome em `encrypted()` faz a leitura devolver
 * ciphertext (o defeito do Google até a v0.19.3); decifra que não chega ao consumidor, a terceira.
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
 * A chave secreta fora do HTML da tela de configurações.
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

/** O save que não tocou na chave a mantém (`->dehydrated()` sem condição). */
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

/** Preencher substitui a guardada (`->dehydrated(false)` fixo). */
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

/** Cada propriedade alcança a chave de config dela (a linha do mapa esquecida — `.ai/rules/settings.md`). */
it('alcanca a chave de config de cada propriedade anti-robo', function (string $propriedade, mixed $valor, string $chave): void {
    $settings                 = app(SettingsDoKit::class);
    $settings->{$propriedade} = $valor;
    $settings->save();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    expect(config($chave))->toBe($valor);
})->with([
    'habilitado'       => ['login_anti_robo_habilitado', true, 'kit.login.anti_robo.habilitado'],
    'local'            => ['login_anti_robo_local', true, 'kit.login.anti_robo.local'],
    'provedor'         => ['login_anti_robo_provedor', 'turnstile', 'kit.login.anti_robo.provedor'],
    'chave do site'    => ['login_anti_robo_chave_do_site', 'site-x', 'kit.login.anti_robo.chave_do_site'],
    'chave secreta'    => ['login_anti_robo_chave_secreta', 'segredo-x', 'kit.login.anti_robo.chave_secreta'],
    'pontuação mínima' => ['login_anti_robo_pontuacao_minima', 0.7, 'kit.login.anti_robo.pontuacao_minima'],
])->group('kit');

/**
 * A tela grava pela seção, e a proteção entra no ar no request seguinte — com o pacote.
 *
 * O `Então` não olha o banco: olha o ponto único depois do realinhamento (o boot do próximo
 * request) e a tela de login do visitante. Mata o `Select` sem as opções do enum (o `Rule::in()`
 * recusaria) e o toggle que grava e não governa.
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

    expect($html)->toContain(hostDoScript(ProvedorAntiRobo::Turnstile))
        ->and($html)->toContain('SITE-DA-TELA')
        ->and($html)->not->toContain('SEGREDO-DA-TELA');
})->group('kit');

/** CT-19 (gravação) — o limiar digitado na tela chega ao driver do v3 como float. */
it('grava o limiar do recaptcha v3 pela tela e ele chega ao driver do pacote', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            'login_anti_robo_habilitado'       => true,
            'login_anti_robo_provedor'         => 'recaptcha_v3',
            'login_anti_robo_pontuacao_minima' => '0.8',
            'login_anti_robo_chave_do_site'    => 'SITE-DA-TELA',
            'login_anti_robo_chave_secreta'    => 'SEGREDO-DA-TELA',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    app(CaptchaManager::class)->driver();

    expect(app(SettingsDoKit::class)->login_anti_robo_pontuacao_minima)->toBe(0.8)
        ->and(config('captcha.recaptcha_v3.score'))->toBe(0.8);
})->group('kit');

/** CT-19 (validação) — o limiar fora de 0..1 é recusado pela tela. */
it('recusa limiar do recaptcha v3 fora do intervalo de 0 a 1', function (string $valor): void {
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            'login_anti_robo_habilitado'       => true,
            'login_anti_robo_provedor'         => 'recaptcha_v3',
            'login_anti_robo_pontuacao_minima' => $valor,
        ])
        ->call('save')
        ->assertHasFormErrors(['login_anti_robo_pontuacao_minima']);
})->with(['negativo' => ['-0.1'], 'acima de um' => ['1.1'], 'texto' => ['alto']])->group('kit');

/** CT-18 — o `Select` oferece os quatro provedores do pacote — e nada mais. */
it('oferece os quatro provedores do pacote na tela de configuracoes', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_anti_robo_habilitado' => true])
        ->assertSchemaComponentExists(
            'login_anti_robo_provedor',
            checkComponentUsing: fn (Select $select): bool => array_keys($select->getOptions()) === ['recaptcha_v2', 'recaptcha_v3', 'turnstile', 'hcaptcha'],
        );

    expect(array_column(ProvedorAntiRobo::cases(), 'value'))->toBe(['recaptcha_v2', 'recaptcha_v3', 'turnstile', 'hcaptcha']);
})->group('kit');

/** Os campos seguem o interruptor. O `local` tem regra própria — ver os dois casos abaixo. */
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

/**
 * O toggle "aplicar também em ambiente local" só existe com `APP_ENV=local`.
 *
 * Fora dali ele é um interruptor sem efeito: quem decide é `ConfiguracaoDoLogin::antiRobo()`,
 * que só consulta `kit.login.anti_robo.local` quando `app()->isLocal()` (`:185`). Interruptor
 * inerte na tela é pior que ausente — quem o vê supõe que mexer nele muda alguma coisa.
 *
 * O oráculo é o HTML da tela, e não `assertSchemaComponentVisible` sobre um `fillForm()`: com o
 * ambiente trocado dentro do caso, o estado reativo do componente Livewire não reflete o
 * interruptor, e a asserção mediria o arranjo em vez da regra. Pelo GET, o que se afirma é o que
 * a pessoa vê.
 */
it('mostra o toggle de ambiente local somente com APP_ENV=local', function (string $ambiente, bool $visivel): void {
    $settings                             = app(SettingsDoKit::class);
    $settings->login_anti_robo_habilitado = true;
    $settings->save();

    config(['app.env' => $ambiente]);
    App::detectEnvironment(fn (): string => $ambiente);

    $resposta = $this->actingAs(usuarioDoKit('admin'))->get('/admin/configuracoes-do-kit')->assertOk();

    $visivel
        ? $resposta->assertSee('Aplicar também em ambiente local')
        : $resposta->assertDontSee('Aplicar também em ambiente local');
})->with([
    'local'       => ['local', true],
    'producao'    => ['production', false],
    'homologacao' => ['staging', false],
])->group('kit');

/** Com a proteção desligada o toggle some mesmo em local — ele segue o interruptor também. */
it('esconde o toggle de ambiente local quando a protecao esta desligada', function (): void {
    $settings                             = app(SettingsDoKit::class);
    $settings->login_anti_robo_habilitado = false;
    $settings->save();

    config(['app.env' => 'local']);
    App::detectEnvironment(fn (): string => 'local');

    $this->actingAs(usuarioDoKit('admin'))
        ->get('/admin/configuracoes-do-kit')
        ->assertOk()
        ->assertDontSee('Aplicar também em ambiente local');
})->group('kit');

/** CT-19 — o campo de limiar só aparece com o reCAPTCHA v3 (M25: sempre visível). */
it('mostra o limiar só quando o provedor e o recaptcha v3', function (ProvedorAntiRobo $provedor): void {
    $this->actingAs(usuarioDoKit('admin'));

    $componente = Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_anti_robo_habilitado' => true, 'login_anti_robo_provedor' => $provedor->value]);

    $provedor->usaPontuacao()
        ? $componente->assertSchemaComponentVisible('login_anti_robo_pontuacao_minima')
        : $componente->assertSchemaComponentHidden('login_anti_robo_pontuacao_minima');
})->with(ProvedorAntiRobo::cases())->group('kit');

/*
|--------------------------------------------------------------------------
| Ancestral R9 — a página de recuperação veste o layout sem vazá-lo
|--------------------------------------------------------------------------
*/

/** O par da rule `.ai/rules/auth.md`: o layout está na recuperação e não vaza para o painel. */
it('veste a recuperação de senha com o layout de autenticação sem vazá-lo para o painel', function (): void {
    $this->get('/admin/password-reset/request')
        ->assertOk()
        ->assertSee('fi-auth-layout', escape: false);

    $this->actingAs(usuarioDoKit('admin'));

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('fi-auth-layout', escape: false);
})->group('kit');
