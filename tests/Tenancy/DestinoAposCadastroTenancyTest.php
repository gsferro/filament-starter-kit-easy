<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\CadastroUnificado;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** CT-76 — com tenancy e chave ligada, conta só de /admin cai no /admin. */
it('com tenancy a conta admin cai no admin sem procurar organizacao', function (): void {
    ligarLoginUnificado();

    $convite = ofertaPara('novo.admin@example.com', null, 'admin');
    $token   = $convite->enviar();

    Filament::auth()->logout();
    Filament::setCurrentPanel('app');

    Livewire::withQueryParams(['token' => $token])
        ->test(CadastroUnificado::class)
        ->fillForm([
            'name'                 => 'Admin Novo',
            'email'                => 'novo.admin@example.com',
            'password'             => 'segredo-bem-longo-123',
            'passwordConfirmation' => 'segredo-bem-longo-123',
        ])
        ->call('register')
        ->assertRedirect('/admin');

    $this->get('/admin')->assertOk();

    $novo = User::where('email', 'novo.admin@example.com')->firstOrFail();

    expect($novo->tenants)->toHaveCount(0);
});
