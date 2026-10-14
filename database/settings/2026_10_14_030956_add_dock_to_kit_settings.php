<?php

use App\Settings\ConfiguracoesDoKit;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * O dock de atalhos (main/dock-de-acoes-rapidas): painéis, layout e estilo padrão.
 *
 * Migration NOVA, e não edição da que criou o grupo: a de criação já rodou em toda instalação.
 * Os defaults vêm de `config/kit.php` — `paineis` lê `KIT_DOCK_PAINEIS`, e o default vazio faz o
 * dock nascer desligado em todo painel (RQ-14).
 *
 * O cache serializado do repositório é limpo na ida e na volta, como em
 * `2026_10_09_203319_clear_kit_settings_cache_after_schema_update.php`: com
 * `SETTINGS_CACHE_ENABLED`, o objeto em cache não tem as propriedades novas e a classe
 * estouraria ao ser hidratada.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('dock_paineis', array_values((array) config('kit.dock.paineis', [])));
            $blueprint->add('dock_layout', (string) config('kit.dock.layout', 'speed-dial'));
            $blueprint->add('dock_estilo', (string) config('kit.dock.estilo', 'native'));
        });

        $this->limparCacheDoRepositorio();
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('dock_paineis');
            $blueprint->delete('dock_layout');
            $blueprint->delete('dock_estilo');
        });

        $this->limparCacheDoRepositorio();
    }

    private function limparCacheDoRepositorio(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }
};
