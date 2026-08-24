<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\UsuarioAdminSeeder;

/**
 * O seeder do administrador inicial busca pelo PAPEL, não pelo e-mail.
 *
 * A versão anterior fazia `firstOrCreate(['email' => config('kit.admin.email')], …)`, e o defeito
 * aparecia no dia em que alguém trocava `KIT_ADMIN_EMAIL` e semeava de novo: nascia um SEGUNDO
 * `master_global`, com o primeiro vivo e a senha antiga. Dois administradores da instalação, sem
 * erro nenhum — e o esquecido é o perigoso, porque ninguém troca a senha de uma conta que não
 * sabe que existe.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('cria o administrador quando nao existe nenhum', function (): void {
    $this->seed(UsuarioAdminSeeder::class);

    $admin = User::where('email', config('kit.admin.email'))->firstOrFail();

    expect($admin->isMasterGlobal())->toBeTrue()
        ->and(User::count())->toBe(1);
});

/**
 * O caso que dava o defeito: e-mail configurado muda, seeder roda de novo.
 *
 * Um só administrador depois de duas execuções com e-mails diferentes. E o `toBe(1)` sobre a
 * contagem é a asserção que importa — asserir só "o novo e-mail não existe" deixaria passar uma
 * implementação que renomeasse a conta, o que é outra coisa e teria outros riscos.
 */
it('nao cria um segundo administrador quando o e-mail configurado muda', function (): void {
    $this->seed(UsuarioAdminSeeder::class);

    $original = User::where('email', config('kit.admin.email'))->firstOrFail();

    config(['kit.admin.email' => 'outro-admin@example.com']);

    $this->seed(UsuarioAdminSeeder::class);

    expect(User::count())->toBe(1)
        ->and(User::first()?->getKey())->toBe($original->getKey())
        // A conta segue com o e-mail e a senha originais: o seeder garante que EXISTA
        // administrador, não que ele espelhe o .env. Trocar credencial é na tela de perfil, e
        // sincronizar aqui reverteria essa troca em todo `db:seed`.
        ->and(User::first()?->email)->toBe($original->email);
});

/**
 * Reparo: a conta do e-mail configurado existe, mas alguém removeu o papel dela na tela.
 *
 * Aqui o certo é devolver o papel à conta que já está lá — não criar outra. É o que o
 * `firstOrCreate` no ramo de criação preserva, e o caso existe para que ninguém o troque por um
 * `create()` cru.
 */
it('devolve o papel a conta existente que perdeu o master_global', function (): void {
    $this->seed(UsuarioAdminSeeder::class);

    $admin = User::where('email', config('kit.admin.email'))->firstOrFail();
    $admin->removeRole(config('filament-shield.super_admin.name', 'master_global'));

    expect($admin->fresh()?->isMasterGlobal())->toBeFalse();

    $this->seed(UsuarioAdminSeeder::class);

    expect(User::count())->toBe(1)
        ->and($admin->fresh()?->isMasterGlobal())->toBeTrue();
});
