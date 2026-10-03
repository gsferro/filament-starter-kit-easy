<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * A variante escura da logo da marca e o toggle que a separa.
 *
 * `logo_dark` nasce `null`, como `logo` e `favicon`: caminho no disk `public`,
 * vazio significa "sem variante" — nunca "logo apagada".
 *
 * `unifica_logo_marca` nasce `true` de propósito, e a direção do default é a do
 * `densidade_do_layout`: o comportamento anterior é o que o projeto já tem (uma
 * logo só), e nenhuma atualização do kit deve mudar a tela de quem instalou
 * sem um gesto na interface. Separar a marca em light/dark é escolha, e ela é
 * um clique na aba Identidade.
 *
 * Migration NOVA, nunca a que já rodou, pelo mesmo motivo registrado em
 * `add_densidade_do_layout`: instalação de terceiro que só roda `migrate` ficaria
 * sem as linhas, e `aplicarNaConfig()` estouraria `MissingSettings` no boot.
 *
 * O default vem de `config/kit.php` (`kit.identidade.unifica_logo_marca`, que lê
 * `KIT_UNIFICA_LOGO_MARCA`) — com a instalação nascendo unificada também por `.env`.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('logo_dark', config('kit.identidade.logo_dark'));
            $blueprint->add(
                'unifica_logo_marca',
                (bool) config('kit.identidade.unifica_logo_marca', true),
            );
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('logo_dark');
            $blueprint->delete('unifica_logo_marca');
        });
    }
};
