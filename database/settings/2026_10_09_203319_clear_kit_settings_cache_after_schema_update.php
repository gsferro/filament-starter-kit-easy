<?php

use App\Settings\ConfiguracoesDoKit;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * Descarta settings serializados antes da inclusão de ocultar_seletor_unico.
 * Preserva valores do banco e respeita store/prefix do repositório de settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }

    public function down(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }
};
