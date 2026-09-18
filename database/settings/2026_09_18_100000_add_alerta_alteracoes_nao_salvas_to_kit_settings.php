<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * O interruptor do alerta de alterações não salvas.
 *
 * Migration NOVA, nunca a que já rodou: instalação de terceiro que só roda `migrate` ficaria sem a
 * linha, e `aplicarNaConfig()` estouraria `MissingSettings` no boot de todo request.
 *
 * O default vem de `config/kit.php` (`kit.alerta_alteracoes_nao_salvas`, que lê
 * `KIT_ALERTA_ALTERACOES_NAO_SALVAS`) — `true`, então quem atualiza passa a ser avisado antes de
 * perder o que digitou. Nasce LIGADO de propósito: o comportamento anterior era perder o
 * preenchimento em silêncio, e silêncio não é o default a preservar. Desligar é um clique na tela.
 *
 * Ver `wikis/specs/feat/estudo-de-pacotes-rodada-2/`, ADR-02.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('alerta_alteracoes_nao_salvas', (bool) config('kit.alerta_alteracoes_nao_salvas', true));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('alerta_alteracoes_nao_salvas');
        });
    }
};
