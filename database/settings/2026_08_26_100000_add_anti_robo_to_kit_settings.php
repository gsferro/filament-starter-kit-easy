<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * A proteção anti-robô das telas públicas: interruptor, provedor e o par de chaves.
 *
 * Semeadas do `.env` (`KIT_ANTI_ROBO*`) como toda propriedade do kit — o banco vence depois. A
 * chave secreta vai com `addEncrypted`, E o nome dela está em `ConfiguracoesDoKit::encrypted()`:
 * são as duas metades do mesmo contrato, e faltar uma é o defeito que o segredo do Google teve
 * até a v0.19.3 (docblock de `encrypted()`).
 *
 * String vazia vira `null` nas duas chaves, de propósito: `''` num campo de credencial é um valor
 * que parece configurado e não é, e `ConfiguracaoDoLogin::antiRobo()` usa `filled()` justamente
 * por isso. Wiki `recaptcha-nas-telas-publicas`, ADR-03.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $kit): void {
            $kit->add('login_anti_robo_habilitado', (bool) config('kit.login.anti_robo.habilitado', false));
            $kit->add('login_anti_robo_provedor', (string) (config('kit.login.anti_robo.provedor') ?: 'recaptcha'));
            $kit->add('login_anti_robo_chave_do_site', $this->textoOuNulo('kit.login.anti_robo.chave_do_site'));
            $kit->addEncrypted('login_anti_robo_chave_secreta', $this->textoOuNulo('kit.login.anti_robo.chave_secreta'));
        });
    }

    public function down(): void
    {
        foreach (['habilitado', 'provedor', 'chave_do_site', 'chave_secreta'] as $sufixo) {
            $this->migrator->deleteIfExists("kit.login_anti_robo_{$sufixo}");
        }
    }

    private function textoOuNulo(string $chave): ?string
    {
        $valor = config($chave);

        if (! is_scalar($valor)) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
};
