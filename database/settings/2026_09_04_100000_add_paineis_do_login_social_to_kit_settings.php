<?php

use App\Support\ProvedorSocial;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Em quais painéis cada provedor de login social vale.
 *
 * Uma propriedade por provedor, semeada com o que a CONFIGURAÇÃO tem — nunca com literais. É o
 * mesmo motivo das migrations de settings anteriores: o valor semeado sai de `config(...)`, que sai
 * do `.env`, que é onde o `kit:install` escreveu as respostas da instalação.
 *
 * **O default é a lista VAZIA, e vazio significa TODOS os painéis.** A tradução é de
 * `App\Support\ConfiguracaoDoLogin`, não daqui. É isso que faz a feature nascer inerte: quem já
 * usa login social não perde nada num update, e a escolha por painel só passa a valer quando
 * alguém a preenche. Ver A2 e ADR-04 de
 * `wikis/specs/feat/login-social-por-painel/login-social-por-painel/`.
 *
 * ## Migration NOVA, e nunca a que já rodou
 *
 * `2026_08_25_000000_add_provedores_sociais_to_kit_settings.php` já rodou em instalação de
 * terceiro. Editá-la deixaria essas instalações sem as linhas novas, e
 * `ConfiguracoesDoKit::aplicarNaConfig()` estouraria `MissingSettings` no boot de todo request.
 * Ver ADR-05 da wiki `verificacao-de-email-editavel`.
 *
 * Fora de `encrypted()`: lista de painéis não é segredo.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            foreach (ProvedorSocial::cases() as $provedor) {
                $blueprint->add(
                    $provedor->propriedadeDeSettings('paineis'),
                    (array) config("kit.login.{$provedor->value}.paineis", []),
                );
            }
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
            foreach (ProvedorSocial::cases() as $provedor) {
                /*
                 * `delete()`, e não `deleteIfExists()` — este último NÃO existe na API do pacote
                 * (`SettingsBlueprint` tem `add`, `delete`, `rename`, `update`, `addEncrypted`,
                 * `updateEncrypted`, `encrypt`, `decrypt`). O plano da wiki pedia o inexistente, e
                 * o caso `it desfaz e refaz as migrations de settings sem quebrar` foi quem pegou.
                 */
                $blueprint->delete($provedor->propriedadeDeSettings('paineis'));
            }
        });
    }
};
