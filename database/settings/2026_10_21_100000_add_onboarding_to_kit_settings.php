<?php

use App\Settings\ConfiguracoesDoKit;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * O tutorial guiado (wiki `onboarding-guiado`): o interruptor do uso.
 *
 * Migration NOVA, e não edição da que criou o grupo: a de criação já rodou em toda instalação.
 * O default vem de `config/kit.php`, que lê `KIT_ONBOARDING` — e nasce DESLIGADO (RQ-11).
 * Instalação que atualiza e quer o tutorial liga pela tela de configurações (ou põe
 * `KIT_ONBOARDING=true` no `.env` antes de rodar esta migration) e semeia as jornadas com
 * `php artisan db:seed --class=OnboardingSeeder`.
 *
 * O cache serializado do repositório é limpo na ida e na volta, como nas migrations do dock e
 * dos agentes de IA: com `SETTINGS_CACHE_ENABLED`, o objeto em cache não tem a propriedade nova.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('onboarding_habilitado', (bool) config('kit.onboarding.habilitado', false));
        });

        $this->limparCacheDoRepositorio();
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('onboarding_habilitado');
        });

        $this->limparCacheDoRepositorio();
    }

    private function limparCacheDoRepositorio(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }
};
