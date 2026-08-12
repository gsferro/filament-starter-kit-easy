<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Health\Facades\Health;

/**
 * Invariantes da fundação: convenções que, se quebrarem, quebram em silêncio.
 */
it('gera uuid e usa ele como route key', function (): void {
    $user = User::create([
        'name'     => 'Teste',
        'email'    => 'uuid@example.com',
        'password' => 'password',
    ]);

    expect($user->uuid)->not->toBeEmpty()
        ->and($user->getRouteKeyName())->toBe('uuid')
        ->and($user->getRouteKey())->toBe($user->uuid);
});

it('deixa o master_global vencer qualquer gate pelo Gate::before', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', 'admin@example.com')->firstOrFail();

    expect(Gate::forUser($master)->allows('ver-logs'))->toBeTrue()
        ->and(Gate::forUser($master)->allows('uma-ability-que-nao-existe'))->toBeTrue();
});

it('nega abilities de infra para quem não tem papel', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $user = User::create([
        'name'     => 'Comum',
        'email'    => 'comum@example.com',
        'password' => 'password',
    ]);

    expect(Gate::forUser($user)->allows('ver-logs'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('command-center:access'))->toBeFalse();
});

it('audita exatamente os campos fillable', function (): void {
    $user = new User;

    expect($user->getAuditInclude())->toBe($user->getFillable());
});

it('registra os health checks do kit', function (): void {
    $checks = Health::registeredChecks();

    expect($checks)->not->toBeEmpty();
});
