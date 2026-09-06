<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Página única de login (`/login`) para os três painéis. O default vem do `config/kit.php`
 * (`kit.login.unificado`, que lê `KIT_LOGIN_UNIFICADO`), então uma instalação que já ligou pelo
 * `.env` nasce ligada no Settings. Ver `wikis/specs/feat/login-unificado/`.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('login_unificado', (bool) config('kit.login.unificado', false));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('login_unificado');
        });
    }
};
