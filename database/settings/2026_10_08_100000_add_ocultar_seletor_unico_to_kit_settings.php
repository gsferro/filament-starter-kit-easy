<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * O interruptor "ocultar o seletor de organização quando houver uma só"
 * (main/ocultar-seletor-de-organizacao-unica).
 *
 * Migration NOVA, e não edição da que criou o grupo: a de criação já rodou em
 * toda instalação e nunca mais roda. O default vem de `config/kit.php`, que por
 * sua vez lê o `.env` (`KIT_TENANCY_OCULTAR_SELETOR_UNICO`) — é assim que o
 * `.env` "semeia a primeira gravação". O default `false` preserva o
 * comportamento anterior: o seletor aparece sempre.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('ocultar_seletor_unico', (bool) config('kit.tenancy.ocultar_seletor_unico', false));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('ocultar_seletor_unico');
        });
    }
};
