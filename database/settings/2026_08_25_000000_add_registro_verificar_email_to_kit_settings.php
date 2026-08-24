<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * A 25ª propriedade do grupo `kit`: a exigência de e-mail validado no /app.
 *
 * ## Por que uma migration nova, e não uma linha na anterior
 *
 * `2026_08_24_000000_create_kit_settings.php` já rodou — no repositório, no CI e em toda
 * instalação de terceiro que fez `composer create-project` da v0.19.x. Migration que já rodou
 * não roda de novo, então acrescentar o `add()` lá dentro deixaria essas instalações **sem a
 * linha**: `ConfiguracoesDoKit::aplicarNaConfig()` lê `$this->registro_verificar_email`, o
 * spatie não acha a propriedade e estoura `MissingSettings` — no boot, em todo request.
 *
 * É a regra que o docblock daquela migration já dizia, aplicada pela primeira vez.
 *
 * ## O que ela semeia, e por quê do `.env`
 *
 * `config('kit.registro.verificar_email')`, que sai de `KIT_REGISTRO_VERIFICAR_EMAIL`. Quem já
 * tinha a chave ligada por lá continua com a exigência ligada depois do `migrate` — a alternativa
 * (semear `false` literal) desligaria silenciosamente uma barreira de acesso em produção durante
 * uma atualização, que é o pior comportamento possível para esta chave em particular.
 *
 * A regra dura do settings do kit continua valendo: **o banco vence em tempo de execução; o .env
 * semeia e é o plano B.**
 *
 * ## O `down()`
 *
 * `deleteIfExists` e não `delete`: `delete()` lança `SettingDoesNotExist` numa propriedade que a
 * instalação nunca teve, e um `down()` que estoura é um desligamento que não funciona justamente
 * quando alguém precisa dele.
 *
 * Sem a linha no banco, `aplicarNaConfig()` volta a não sobrepor esta chave e o `.env` manda de
 * novo. Vale lembrar que o `down()` da migration anterior também apaga esta propriedade — ele
 * itera `mapaDeConfiguracao()` —, então há sobreposição inofensiva entre os dois rollbacks.
 *
 * Ver ADR-05 em wikis/specs/feat/verificacao-de-email-editavel/.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $kit): void {
            $kit->add('registro_verificar_email', (bool) config('kit.registro.verificar_email', false));
        });
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('kit.registro_verificar_email');
    }
};
