<?php

use App\Filament\Admin\Resources\Users\UserResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

it('[CT-B01] bruno abre o dock e chega à tela do favorito', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $bruno = usuarioDoKit('admin', 'bruno@example.com');
    concederDock('admin');
    ligarDock(['admin']);
    gravarAtalhos($bruno, [chaveDeAtalho('admin', UserResource::class)]);

    $this->actingAs($bruno);
    $this->get('/admin')->assertOk();

    // vendor/hoceineel/filament-quick-action-dock/resources/views/dock.blade.php:x-ref="trigger":171; onTriggerClick:182.
    visit('/admin')
        ->click('[data-layout] button[x-ref="trigger"]')
        ->assertVisible('[data-layout] a[href$="/admin/users"]')
        ->click('[data-layout] a[href$="/admin/users"]')
        ->assertPathIs('/admin/users')
        ->assertNoJavaScriptErrors();
})->group('browser');
