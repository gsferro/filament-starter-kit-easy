<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * A versão do SISTEMA e o interruptor da versão do kit no rodapé.
 *
 * Migration NOVA, nunca a que já rodou: instalação de terceiro que só roda `migrate` ficaria sem
 * as linhas, e `aplicarNaConfig()` estouraria `MissingSettings` no boot de todo request.
 *
 * `versao_do_sistema` é semeada por `config('app.version')`, que lê `APP_VERSION` — `null` em toda
 * instalação que não declarou, e aí o rodapé simplesmente não mostra versão. `exibir_versao_do_kit`
 * nasce `false`: a versão do kit é métrica interna do starter, não do produto entregue.
 *
 * Ver `wikis/specs/feat/estudo-de-pacotes-rodada-2/`, Adendo 2 e ADR-04.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('versao_do_sistema', config('app.version'));
            $blueprint->add('exibir_versao_do_kit', (bool) config('kit.exibir_versao', false));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('versao_do_sistema');
            $blueprint->delete('exibir_versao_do_kit');
        });
    }
};
