<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * O interruptor do dashboard dinâmico. O default vem do `config/kit.php`
 * (`kit.dashboard_dinamico.*`, que lê `KIT_DASHBOARD_DINAMICO*`) — `false` para
 * quem atualiza, então o `kit:update` é inerte (RQ-08). Ver
 * `wikis/specs/main/dashboard-dinamico-nos-paineis/`.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('dashboard_dinamico_habilitado', (bool) config('kit.dashboard_dinamico.habilitado', false));
            $blueprint->add('dashboard_dinamico_paineis', (array) config('kit.dashboard_dinamico.paineis', []));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('dashboard_dinamico_habilitado');
            $blueprint->delete('dashboard_dinamico_paineis');
        });
    }
};
