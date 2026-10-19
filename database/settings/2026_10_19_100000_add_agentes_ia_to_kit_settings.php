<?php

use App\Settings\ConfiguracoesDoKit;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * Os agentes de IA (wiki `agentes-ia-no-settings`): o interruptor do uso e os painéis do chat.
 *
 * Migration NOVA, e não edição da que criou o grupo: a de criação já rodou em toda instalação.
 * Os defaults vêm de `config/kit.php`, que lê `KIT_AGENTES_IA` e `KIT_AGENTES_IA_PAINEIS` — e
 * os dois nascem DESLIGADOS (`false` e lista vazia = nenhum painel), como o requisito pede
 * (RQ-07). Instalação que atualiza e quer o chat de volta no /app liga pela tela de
 * configurações ou pelo `.env` antes de rodar esta migration.
 *
 * O cache serializado do repositório é limpo na ida e na volta, como em
 * `2026_10_14_030956_add_dock_to_kit_settings.php`: com `SETTINGS_CACHE_ENABLED`, o objeto em
 * cache não tem as propriedades novas e a classe estouraria ao ser hidratada.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('agentes_ia_habilitado', (bool) config('kit.agentes_ia.habilitado', false));
            $blueprint->add('agentes_ia_paineis', array_values((array) config('kit.agentes_ia.paineis', [])));
        });

        $this->limparCacheDoRepositorio();
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('agentes_ia_habilitado');
            $blueprint->delete('agentes_ia_paineis');
        });

        $this->limparCacheDoRepositorio();
    }

    private function limparCacheDoRepositorio(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }
};
