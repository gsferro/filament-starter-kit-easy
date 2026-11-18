<?php

use App\Settings\ConfiguracoesDoKit;
use App\Support\ModoDoMenu;
use App\Support\TamanhoDaFonte;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * Os padrões de fonte e de menu e a chave da aparência pessoal (wiki `feat/preferencias-de-aparencia`).
 *
 * Semeados do `config/kit.php` (que lê o `.env`), coagidos como a densidade: o padrão de fábrica é
 * a fonte do Filament e o menu de sempre, então quem atualiza o kit não vê tela nenhuma mudar.
 * A chave nasce ligada (P-03) — sem escolha de ninguém, ligada não muda nada na tela além da seção
 * nova do Meu perfil.
 *
 * O cache serializado do repositório é limpo na ida e na volta, como nas migrations irmãs.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('tamanho_da_fonte', TamanhoDaFonte::coagir(config('kit.tamanho_da_fonte'))->value);
            $blueprint->add('modo_do_menu', ModoDoMenu::coagir(config('kit.modo_do_menu'))->value);
            $blueprint->add('aparencia_por_usuario', (bool) config('kit.aparencia_por_usuario', true));
        });

        $this->limparCacheDoRepositorio();
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('tamanho_da_fonte');
            $blueprint->delete('modo_do_menu');
            $blueprint->delete('aparencia_por_usuario');
        });

        $this->limparCacheDoRepositorio();
    }

    private function limparCacheDoRepositorio(): void
    {
        app(SettingsCacheFactory::class)->build(ConfiguracoesDoKit::repository())->clear();
    }
};
