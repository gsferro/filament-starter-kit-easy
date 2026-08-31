<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * A proteção anti-robô passa a ser o pacote `ddr/filament-captcha`: três ajustes nas settings.
 *
 * 1. O valor do provedor `recaptcha` vira `recaptcha_v2` — é o nome do driver no pacote, que
 *    precisa distinguir do `recaptcha_v3`. Sem esta conversão, quem já tinha o reCAPTCHA ligado
 *    acordaria com a proteção DESLIGADA em silêncio: provedor desconhecido é tratado como
 *    desligado (`ConfiguracaoDoLogin::antiRobo()`). Fallback em runtime foi recusado — esconderia
 *    o valor sujo (ADR-04 da wiki `adotar-ddr-filament-captcha`).
 * 2. `login_anti_robo_pontuacao_minima`: o limiar do reCAPTCHA v3 (0 a 1). Semeado do `.env`
 *    (`KIT_ANTI_ROBO_PONTUACAO_MINIMA`), como toda propriedade do kit; 0,5 é o sugerido pelo Google.
 * 3. `login_anti_robo_local`: aplicar o desafio também com `APP_ENV=local`. Nasce desligado —
 *    chave de produção não aceita localhost (ADR-07).
 *
 * A propriedade nova segue a regra dos três lugares (`.ai/rules/settings.md`): está na classe,
 * no `mapaDeConfiguracao()` e aqui.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $kit): void {
            $kit->update('login_anti_robo_provedor', static fn (mixed $valor): mixed => $valor === 'recaptcha' ? 'recaptcha_v2' : $valor);
            $kit->add('login_anti_robo_pontuacao_minima', (float) config('kit.login.anti_robo.pontuacao_minima', 0.5));
            $kit->add('login_anti_robo_local', (bool) config('kit.login.anti_robo.local', false));
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $kit): void {
            $kit->update('login_anti_robo_provedor', static fn (mixed $valor): mixed => $valor === 'recaptcha_v2' ? 'recaptcha' : $valor);
        });

        $this->migrator->deleteIfExists('kit.login_anti_robo_pontuacao_minima');
        $this->migrator->deleteIfExists('kit.login_anti_robo_local');
    }
};
