<?php

use App\Support\DensidadeDoLayout;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * A densidade do layout — o "compacto" dos três painéis.
 *
 * Migration NOVA, nunca a que já rodou: instalação de terceiro que só roda `migrate` ficaria sem
 * a linha, e `aplicarNaConfig()` estouraria `MissingSettings` no boot de todo request.
 *
 * O default vem de `config/kit.php` (`kit.densidade_do_layout`, que lê `KIT_DENSIDADE_DO_LAYOUT`)
 * — `confortavel`, que é o padrão do Filament e não emite `<style>` nenhum. Nasce assim de
 * propósito, e a direção é o oposto da do `alerta_alteracoes_nao_salvas`: lá o comportamento
 * anterior perdia digitação e o silêncio não era default a preservar; aqui o comportamento
 * anterior é **a aparência que o projeto já tem**, e nenhuma atualização do kit deve mudá-la
 * sozinha. Apertar é uma escolha de gosto, e ela é um clique na tela.
 *
 * O `coagir()` na semente e não `config(...)` direto: a chave tem vocabulário fechado, e um
 * `.env` com `compact` (em inglês) semearia a tabela com um valor que nenhum nível conhece.
 *
 * Ver `wikis/specs/feat/layout-compact/layout-compact/`, ADR-03 e ADR-04.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add(
                'densidade_do_layout',
                DensidadeDoLayout::coagir(config('kit.densidade_do_layout'))->value,
            );
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('densidade_do_layout');
        });
    }
};
