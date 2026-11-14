<?php

use App\Settings\ConfiguracoesDoKit;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * A aparência das telas de autenticação (wiki `feat/aparencia-das-telas-de-acesso`).
 *
 * Migration NOVA, e não edição da que criou o grupo: a de criação já rodou em toda instalação.
 * Nasce `null` — o layout clássico, o de sempre (P-01): quem atualiza o kit não vê tela nenhuma
 * mudar até alguém escolher outro layout na aba "Telas de acesso" das configurações.
 *
 * O cache serializado do repositório é limpo na ida e na volta, como nas migrations do dock, dos
 * agentes de IA e do onboarding: com `SETTINGS_CACHE_ENABLED`, o objeto em cache não tem a
 * propriedade nova.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('aparencia_das_telas', null);
        });

        $this->limparCacheDoRepositorio();
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('aparencia_das_telas');
        });

        $this->limparCacheDoRepositorio();
    }

    private function limparCacheDoRepositorio(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }
};
