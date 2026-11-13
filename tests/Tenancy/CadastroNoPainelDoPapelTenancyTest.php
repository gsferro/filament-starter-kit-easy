<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Facades\Filament;
use Filament\PanelRegistry;
use Illuminate\Http\Request;

/**
 * Cadastro no painel do papel com a tenancy LIGADA — wiki `fix/convite-painel-do-papel`.
 *
 * Helpers com sufixo proprio: o arquivo da suite Kit declara os seus e funcao global colide.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    ligarLoginUnificado(false);
});

/** panel_user somente da organização `acme`; `outra` existe sem vínculo. */
function contaSomenteAcmeTenancy(): User
{
    $acme = tenant('Acme', 'acme');
    tenant('Outra', 'outra');

    $user = User::factory()->create();
    papelNaOrganizacao($user, 'panel_user', $acme);
    $user->tenants()->attach($acme->id);

    return $user;
}

function respostaDeCadastroTenancy(User $user, string $corrente, string $default, ?string $pretendida): string
{
    $registro               = app(PanelRegistry::class);
    $registro->defaultPanel = Filament::getPanel($default);

    Filament::setCurrentPanel($corrente);

    if ($pretendida !== null) {
        session()->put('url.intended', url($pretendida));
    }

    Filament::auth()->login($user);

    $requisicao = Request::capture();
    $requisicao->setLaravelSession(app('session.store'));

    return app(RegistrationResponse::class)->toResponse($requisicao)->getTargetUrl();
}

it('[CT-01] convite infra sem organizacao chega ao infra com tenancy ligada', function (): void {
    $convite = ofertaPara('novo@example.com', null, 'infra');

    cadastrarPorConvite(RegistroPorConvite::class, $convite->enviar())->assertRedirect('/infra');

    expect(Filament::auth()->check())->toBeTrue();
    esperarAcessoLivre('/infra');
});

it('[CT-02] convite admin sem organizacao chega ao admin com tenancy ligada', function (): void {
    $convite = ofertaPara('novo@example.com', null, 'admin');

    cadastrarPorConvite(RegistroPorConvite::class, $convite->enviar())->assertRedirect('/admin');

    expect(Filament::auth()->check())->toBeTrue();
    esperarAcessoLivre('/admin');
});

it('[CT-04] organizacao sem vinculo na pretendida cai na organizacao da conta', function (): void {
    $user = contaSomenteAcmeTenancy();

    $alvo = respostaDeCadastroTenancy($user, 'app', 'admin', '/app/outra');

    expect($alvo)->toBe(url('/app/acme'))
        ->and(Filament::auth()->check())->toBeTrue();

    esperarAcessoLivre($alvo);
});

it('[CT-06] o unico painel app inclui a organizacao', function (): void {
    $user = contaSomenteAcmeTenancy();

    $alvo = respostaDeCadastroTenancy($user, 'infra', 'admin', null);

    expect($alvo)->toBe(url('/app/acme'))
        ->and(Filament::auth()->check())->toBeTrue();

    esperarAcessoLivre($alvo);
});

it('[CT-07] convite panel_user da organizacao chega ao app da organizacao', function (): void {
    $acme = tenant('Acme', 'acme');
    tenant('Outra', 'outra');

    $convite = ofertaPara('novo@example.com', $acme, 'panel_user');

    cadastrarPorConvite(RegistroPorConvite::class, $convite->enviar())->assertRedirect('/app/acme');

    expect(Filament::auth()->check())->toBeTrue();
    esperarAcessoLivre('/app/acme');
});

it('[CT-11] pretendida de organizacao sem vinculo e corrente inacessivel terminam na organizacao da conta', function (): void {
    $user = contaSomenteAcmeTenancy();

    $alvo = respostaDeCadastroTenancy($user, 'infra', 'infra', '/app/outra');

    expect($alvo)->toBe(url('/app/acme'))
        ->and(Filament::auth()->check())->toBeTrue()
        ->and(session()->has('url.intended'))->toBeFalse();

    esperarAcessoLivre($alvo);
});

it('[CT-15] pretendida em rota do painel sem organizacao ou da propria organizacao nao e descartada', function (string $pretendida): void {
    $user = contaSomenteAcmeTenancy();

    $alvo = respostaDeCadastroTenancy($user, 'app', 'app', $pretendida);

    expect($alvo)->toBe(url($pretendida));
})->with([
    'verificacao de e-mail (fora do grupo {tenant})' => ['/app/email-verification/prompt'],
    'pagina da propria organizacao'                  => ['/app/acme'],
]);

it('[CT-16] pretendida de organizacao sem vinculo em rota inexistente e descartada', function (): void {
    $user = contaSomenteAcmeTenancy();

    $alvo = respostaDeCadastroTenancy($user, 'app', 'app', '/app/outra/rota-que-nao-existe');

    expect($alvo)->toBe(url('/app/acme'))
        ->and(session()->has('url.intended'))->toBeFalse();
});
