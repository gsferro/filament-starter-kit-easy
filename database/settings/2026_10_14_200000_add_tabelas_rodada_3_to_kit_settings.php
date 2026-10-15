<?php

use App\Settings\ConfiguracoesDoKit;
use App\Support\CarregamentoDaTabela;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * Os três interruptores de tabela da rodada 3 de pacotes (wiki `estudo-de-pacotes-rodada-3`):
 * carregamento da tabela, visões salvas e o compartilhamento das visões.
 *
 * Migration NOVA, e não edição da que criou o grupo: a de criação já rodou em toda instalação.
 * Os defaults vêm de `config()`, que lê o `.env` — e os três nascem DESLIGADOS (`paginacao`,
 * `false`, `false`), como o requisito pede (RQ-05). O terceiro é semeado de
 * `remember-table.allow_public_views`, a chave do pacote para a qual o `mapaDeConfiguracao()`
 * aponta, publicada em `config/remember-table.php` com o default do kit.
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
            $blueprint->add('tabela_carregamento', CarregamentoDaTabela::coagir(config('kit.tabelas.carregamento'))->value);
            $blueprint->add('visoes_de_tabela', (bool) config('kit.tabelas.visoes_salvas', false));
            $blueprint->add('visoes_de_tabela_compartilhadas', (bool) config('remember-table.allow_public_views', false));
        });

        $this->limparCacheDoRepositorio();
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('tabela_carregamento');
            $blueprint->delete('visoes_de_tabela');
            $blueprint->delete('visoes_de_tabela_compartilhadas');
        });

        $this->limparCacheDoRepositorio();
    }

    private function limparCacheDoRepositorio(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }
};
