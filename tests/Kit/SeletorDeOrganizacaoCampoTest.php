<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * O interruptor "ocultar o seletor" na tela de configurações, SEM multi-organização.
 *
 * IDs de CT em `wikis/specs/main/ocultar-seletor-de-organizacao-unica/04-casos-de-teste.md`.
 *
 * Em `tests/Kit` e não em `tests/Tenancy`: é a suíte single-tenant, onde a flag
 * `kit.tenancy.enabled` é `false` — o único lugar em que o `->visible()` do campo
 * devolve falso.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Filament::setCurrentPanel('admin');
});

/**
 * CT-09 — sem seletor para ocultar, um interruptor que não muda nada é ruído:
 * o campo simplesmente não aparece na aba Kit.
 */
it('[CT-09] sem a multi-organização ligada o interruptor não aparece', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->assertSchemaComponentHidden('ocultar_seletor_unico', 'form');
});
