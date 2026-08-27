<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * O interruptor do modo estrito do vínculo de provedor social.
 *
 * `false` (padrão): a primeira entrada de um provedor numa conta que já existe ENTRA e avisa por
 * e-mail. `true`: não entra; envia o link de confirmação e só entra depois dele. Semeado do
 * `.env` (`KIT_SOCIALITE_VINCULO_CONFIRMAR`), como toda propriedade do kit — o banco vence depois.
 * ADR-03 e ADR-04 da wiki `vinculo-de-provedor-social`.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $kit): void {
            $kit->add('login_vinculo_confirmar', (bool) config('kit.login.vinculo_confirmar', false));
        });
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('kit.login_vinculo_confirmar');
    }
};
