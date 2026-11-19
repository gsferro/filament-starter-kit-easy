<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * A opção do "+" no e-mail do cadastro (wiki `wikis/specs/feat/email-com-mais/`).
 *
 * Semeia do `config('kit.email.permitir_mais')`, que sai de `KIT_EMAIL_PERMITIR_MAIS` — permitido
 * quando a chave falta ou está vazia (P-01). Quem já desligou pelo `.env` continua bloqueando
 * depois do `migrate`.
 *
 * `deleteIfExists` no `down()`, como as irmãs: `delete()` lança numa propriedade que a instalação
 * nunca teve.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $kit): void {
            $kit->add('email_permitir_mais', config('kit.email.permitir_mais') !== false);
        });
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('kit.email_permitir_mais');
    }
};
