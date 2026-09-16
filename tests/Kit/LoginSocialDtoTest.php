<?php

use App\Data\Social\PerfilSocialData;
use App\Models\User;
use App\Models\VinculoSocial;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Laravel\Socialite\Facades\Socialite;

/**
 * O perfil do provedor social como Data — a fronteira do kit com o Socialite.
 *
 * Os IDs de CT são os de
 * `wikis/specs/feat/laravel-data-como-padrao-de-dto/laravel-data-como-padrao-de-dto/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
 * CT-17 — provedor sem e-mail não quebra a fronteira.
 *
 * O oráculo afirma VALOR (o identificador e o nome que vieram), não "não é nulo": um Data com
 * `nome = 'n/d'` passaria num `not-null`.
 */
it('[CT-17] aceita provedor sem e-mail, preservando id e nome', function (): void {
    $perfil = PerfilSocialData::doSocialite(
        ProvedorSocial::Google,
        usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => '1234', 'name' => 'Ana', 'email' => null]),
    );

    expect($perfil->email)->toBeNull()
        ->and($perfil->id)->toBe('1234')
        ->and($perfil->nome)->toBe('Ana');
});

/*
 * CT-18 — e-mail vazio é tratado como ausente.
 *
 * Deixar `''` circular faria uma string vazia passar por endereço nas comparações seguintes.
 */
it('[CT-18] trata e-mail vazio como ausente', function (): void {
    $perfil = PerfilSocialData::doSocialite(
        ProvedorSocial::Google,
        usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => '1234', 'email' => '   ']),
    );

    expect($perfil->email)->toBeNull();
});

/*
 * CT-19 — "email verificado" é lido dos três nomes possíveis.
 *
 * A linha "o provedor não disse" é discriminante: `null` e `false` significam coisas diferentes —
 * `false` é o provedor negando, `null` é ele não ter dito. Colapsar os dois apagaria a distinção
 * que o vínculo social usa.
 */
it('[CT-19] le a marca de verificacao dos tres nomes', function (array $bruto, ?bool $esperado): void {
    $usuario = usuarioSocialFalso(ProvedorSocial::Github, [], ['id' => '1234']);
    $usuario->setRaw(array_merge(['id' => '1234'], $bruto));

    $perfil = PerfilSocialData::doSocialite(ProvedorSocial::Github, $usuario);

    expect($perfil->emailVerificado)->toBe($esperado);
})->with([
    'nome 1'               => [['email_verified' => true], true],
    'nome 2'               => [['verified_email' => true], true],
    'nome 3'               => [['confirmed_email' => 'ana@example.com'], true],
    'desmentido explicito' => [['email_verified' => false], false],
    'o provedor nao disse' => [['locale' => 'pt_BR'], null],
]);

/*
 * CT-27 — o perfil não leva NENHUMA das quatro credenciais.
 *
 * A segunda asserção é o destinatário: sem ela, um `toArray()` vazio passaria por vácuo e a
 * ausência não provaria nada.
 */
it('[CT-27] nao leva credencial do provedor', function (): void {
    $usuario = usuarioSocialFalso(ProvedorSocial::Google, [
        'token'         => 'tok-123',
        'refresh_token' => 'ref-456',
        'access_token'  => 'acc-789',
        'secret'        => 'sec-000',
    ], ['id' => '1234']);

    $perfil = PerfilSocialData::doSocialite(ProvedorSocial::Google, $usuario);

    $comoArray = $perfil->toArray();

    expect($comoArray['bruto'])->not->toHaveKeys(['token', 'refresh_token', 'access_token', 'secret'])
        ->and($comoArray['bruto'])->toHaveKey('id', '1234')
        ->and($perfil->id)->toBe('1234');
});

/*
 * CT-23 — o callback social grava o vínculo A PARTIR do Data.
 *
 * Discriminante: o provedor devolve a chave extra `is_admin`. Pelo caminho do array cru ela
 * chegaria ao insert; pelo Data, não existe. É o cenário que prova que o Data não é decoração —
 * sem ele, as classes existiriam e nenhum ponto de produção as consumiria.
 */
it('[CT-23] grava o vinculo a partir do Data do perfil', function (): void {
    /*
     * As duas linhas de configuração ficam AQUI, e não num helper compartilhado: o helper
     * equivalente vive dentro de `LoginSocialGoogleTest.php`, e usá-lo daqui violaria a rule
     * `.ai/rules/testes.md` — helper usado por dois arquivos tem de migrar para `tests/Pest.php`.
     * Duas linhas não pagam a migração.
     */
    config()->set('kit.login.google.habilitado', true);
    config()->set('services.google', [
        'client_id'     => 'id-de-teste',
        'client_secret' => 'segredo-de-teste',
        'redirect'      => '/auth/google/callback',
    ]);

    $usuario = User::factory()->create(['email' => 'ana@example.com']);
    $usuario->assignRole('panel_user');

    Socialite::fake(ProvedorSocial::Google->value, usuarioSocialFalso(
        ProvedorSocial::Google,
        ['is_admin' => true],
        ['id'       => '1234', 'email' => 'ana@example.com', 'name' => 'Ana'],
    ));

    $this->get(route('auth.social.callback', ['provedor' => ProvedorSocial::Google->value]));

    $vinculo = VinculoSocial::query()
        ->where('provedor', ProvedorSocial::Google->value)
        ->where('sub', '1234')
        ->first();

    expect($vinculo)->not->toBeNull()
        ->and($vinculo->user_id)->toBe($usuario->id)
        // A chave extra do provedor não vira coluna nem atributo do vínculo.
        ->and(array_key_exists('is_admin', $vinculo->getAttributes()))->toBeFalse();
});
