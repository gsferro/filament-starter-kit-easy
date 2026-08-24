<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * `kit:admin` — o único caminho do kit que reescreve credencial de acesso total pela linha de
 * comando.
 *
 * Existe porque o `UsuarioAdminSeeder` **não** sincroniza: ele roda em todo `db:seed`, e
 * sincronizar reverteria em silêncio a senha trocada pela tela de perfil. A troca deliberada
 * ganhou comando deliberado — e comando que muda acesso total precisa de guardas testadas, não
 * de boas intenções.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);
});

it('troca e-mail e senha com force', function (): void {
    $antes = User::where('email', config('kit.admin.email'))->firstOrFail();

    $this->artisan('kit:admin', [
        '--email' => 'novo-admin@example.com',
        '--senha' => 'senha-bem-longa-123',
        '--force' => true,
    ])->assertSuccessful();

    $depois = $antes->fresh();

    expect($depois?->email)->toBe('novo-admin@example.com')
        ->and(Hash::check('senha-bem-longa-123', (string) $depois?->password))->toBeTrue()
        // E-mail novo entra verificado: deixar nulo trancaria o próprio administrador fora das
        // telas que exigem verificação.
        ->and($depois?->email_verified_at)->not->toBeNull()
        // Um administrador, não dois — a diferença entre este comando e o defeito que o seeder
        // tinha até a 0.18.8.
        ->and(User::count())->toBe(1);
})->group('kit');

/**
 * A guarda que evita erro de banco cru: o e-mail novo já é de outra conta.
 *
 * Sem ela o `save()` estoura na constraint de unicidade e a pessoa lê um `SQLSTATE` no terminal
 * em vez de uma frase que diz o que fazer. O cenário é realista — convidar alguém e depois
 * querer aquele endereço para o administrador.
 */
it('recusa e-mail que ja pertence a outra conta', function (): void {
    $admin = User::where('email', config('kit.admin.email'))->firstOrFail();

    User::create([
        'name'     => 'Outra',
        'email'    => 'ocupado@example.com',
        'password' => 'password',
    ]);

    $this->artisan('kit:admin', [
        '--email' => 'ocupado@example.com',
        '--force' => true,
    ])->assertFailed();

    expect($admin->fresh()?->email)->toBe(config('kit.admin.email'));
})->group('kit');

/**
 * Dois `master_global` param o comando em vez de escolher um.
 *
 * O papel pode ser concedido na tela de papéis, então mais de um administrador é estado possível.
 * Escolher o primeiro seria trocar a credencial de alguém por sorteio de ordenação — e a pessoa
 * que perdeu o acesso não teria como saber por quê.
 */
it('para quando ha mais de um administrador', function (): void {
    $segundo = User::create([
        'name'     => 'Segundo',
        'email'    => 'segundo@example.com',
        'password' => 'password',
    ]);

    $segundo->assignRole(config('filament-shield.super_admin.name', 'master_global'));

    $this->artisan('kit:admin', ['--senha' => 'outra-senha-longa', '--force' => true])
        ->assertFailed();

    expect(Hash::check('outra-senha-longa', (string) User::where('email', config('kit.admin.email'))->firstOrFail()->password))
        ->toBeFalse();
})->group('kit');

/**
 * Sem terminal e sem flag: não pergunta, não inventa, não altera.
 *
 * Diferente do `kit:install`, onde pular as perguntas em CI é o comportamento certo, aqui pular
 * significa não fazer nada — um comando que reescreve credencial não pode ter default.
 */
it('nao altera nada sem terminal e sem flag', function (): void {
    $admin      = User::where('email', config('kit.admin.email'))->firstOrFail();
    $senhaAntes = (string) $admin->password;

    $this->artisan('kit:admin')->assertSuccessful();

    expect($admin->fresh()?->password)->toBe($senhaAntes)
        ->and($admin->fresh()?->email)->toBe(config('kit.admin.email'));
})->group('kit');

/**
 * O log registra a troca e **não** vaza credencial.
 *
 * O channel `autenticacao` é lido pelo Logs Explorer do `/infra`: o que entra ali sai numa tela.
 * A asserção é de AUSÊNCIA — senha em lugar nenhum, e-mail só mascarado —, que é o oráculo que
 * importa aqui.
 */
it('loga a troca sem expor senha nem e-mail em claro', function (): void {
    $canal = espiarAutenticacao();

    $this->artisan('kit:admin', [
        '--email' => 'mascarado@example.com',
        '--senha' => 'senha-secreta-longa',
        '--force' => true,
    ])->assertSuccessful();

    $canal->shouldHaveReceived('warning')
        ->withArgs(function (string $mensagem, array $contexto): bool {
            $serializado = $mensagem.json_encode($contexto);

            return str_starts_with($mensagem, '[KitAdmin@aplicar]')
                && $contexto['trocou_email'] === true
                && $contexto['trocou_senha'] === true
                && ! str_contains($serializado, 'senha-secreta-longa')
                && ! str_contains($serializado, 'mascarado@example.com');
        })
        ->once();

    Log::shouldHaveReceived('channel')->with('autenticacao');
})->group('kit');
