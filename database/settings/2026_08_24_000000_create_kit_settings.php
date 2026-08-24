<?php

use App\Settings\ConfiguracoesDoKit;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * As 21 propriedades do grupo `kit`, semeadas com o que a CONFIGURAÇÃO tem.
 *
 * É esta migration que resolve o problema da fonte da verdade sem uma linha de
 * código de sincronização: o valor semeado sai de `config(...)`, que sai do
 * `.env`, que é onde o `kit:install` escreveu as respostas da instalação. Numa
 * instalação nova, a cor e o nome escolhidos no terminal chegam ao banco porque
 * o `migrate` roda depois do instalador ter escrito o arquivo.
 *
 * Semear com literais seria o defeito silencioso desta feature: tudo funcionaria,
 * e a resposta do usuário no `kit:install` nunca apareceria na tela.
 *
 * Num `kit:install --force` o SQLite é apagado, o `.env` é reescrito e o
 * `migrate` roda de novo — o banco é re-semeado do `.env` novo, e as duas fontes
 * nascem concordando outra vez.
 *
 * ## O `down()` é o desligamento de emergência
 *
 * Sem linha na tabela, `ConfiguracoesDoKit::aplicarNaConfig()` não é chamado (o
 * provider sai antes) e o `.env` volta a ser a única fonte. É por isso que não
 * existe flag de liga/desliga: ela seria uma TERCEIRA fonte da verdade, e o
 * `migrate:rollback` já faz o serviço. Ver ADR-01.
 *
 * `deleteIfExists` e não `delete`: `delete()` lança `SettingDoesNotExist` numa
 * propriedade que a instalação nunca teve, e um `down()` que estoura é um
 * desligamento que não funciona justamente quando alguém precisa dele.
 *
 * ## Ao acrescentar propriedade
 *
 * Não edite esta migration depois de ela ter rodado em algum lugar — crie outra.
 * As três coisas que andam juntas são: a propriedade em `ConfiguracoesDoKit`, a
 * linha em `mapaDeConfiguracao()` e o par `add`/`deleteIfExists` numa migration.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup(ConfiguracoesDoKit::group(), function (SettingsBlueprint $kit): void {
            $kit->add('nome_da_aplicacao', (string) config('app.name'));
            $kit->add('cor_primaria', $this->textoOuNulo('kit.cor_primaria'));
            $kit->add('cor_primaria_hex', $this->textoOuNulo('kit.cor_primaria_hex'));

            $kit->add('logo', $this->textoOuNulo('kit.identidade.logo'));
            $kit->add('favicon', $this->textoOuNulo('kit.identidade.favicon'));
            $kit->add('arte_do_login', $this->textoOuNulo('kit.identidade.arte_do_login'));

            $kit->add('mail_mailer', (string) config('mail.default', 'log'));
            $kit->add('mail_host', $this->textoOuNulo('mail.mailers.smtp.host'));
            $kit->add('mail_port', $this->inteiroOuNulo('mail.mailers.smtp.port'));
            $kit->add('mail_scheme', $this->textoOuNulo('mail.mailers.smtp.scheme'));
            $kit->add('mail_username', $this->textoOuNulo('mail.mailers.smtp.username'));

            // Cifrada na tabela: o `payload` é JSON em claro, e um dump de banco,
            // um backup e a tela de auditoria são três caminhos que a permissão
            // da tela não cobre. Ver ADR-05.
            $kit->addEncrypted('mail_password', $this->textoOuNulo('mail.mailers.smtp.password'));

            $kit->add('mail_from_address', $this->textoOuNulo('mail.from.address'));
            $kit->add('mail_from_name', $this->textoOuNulo('mail.from.name'));

            $kit->add('paginacao_padrao', (int) config('kit.tabelas.paginacao', 10));
            $kit->add('tabela_listrada', (bool) config('kit.tabelas.listrada', true));
            $kit->add('persistir_filtros', (bool) config('kit.tabelas.persistir_filtros', true));
            $kit->add('colunas_redimensionaveis', (bool) config('kit.tabelas.colunas_redimensionaveis', true));

            $kit->add('hub_de_navegacao', (bool) config('kit.hub', false));
            $kit->add('rotulo_da_organizacao', (string) config('kit.tenancy.label', 'Organização'));
            $kit->add('rotulo_das_organizacoes', (string) config('kit.tenancy.label_plural', 'Organizações'));

            // O default de todas as três é `false`, e o requisito é explícito quanto a isso:
            // porta fechada até alguém abrir. Semear do `.env` mantém quem já ligou por lá.
            $kit->add('registro_habilitado', (bool) config('kit.registro.habilitado', false));
            $kit->add('registro_aprovacao_manual', (bool) config('kit.registro.aprovacao_manual', false));

            $kit->add('login_google_habilitado', (bool) config('kit.login.google.habilitado', false));
            $kit->add('login_google_client_id', $this->textoOuNulo('services.google.client_id'));
            $kit->addEncrypted('login_google_client_secret', $this->textoOuNulo('services.google.client_secret'));
            $kit->add('login_rodape', $this->textoOuNulo('kit.login.rodape'));
        });
    }

    public function down(): void
    {
        foreach (array_keys(ConfiguracoesDoKit::mapaDeConfiguracao()) as $propriedade) {
            $this->migrator->deleteIfExists(ConfiguracoesDoKit::group().'.'.$propriedade);
        }
    }

    /**
     * O valor da config como string, ou `null` quando não há valor.
     *
     * String vazia vira `null` de propósito: as propriedades opcionais são
     * `?string`, e `''` num campo de host ou de usuário de SMTP é um valor que
     * parece configurado e não é. O `MAIL_USERNAME=null` do `.env.example` chega
     * aqui como `null` de verdade (o `env()` do Laravel converte), então este
     * método trata as duas formas de "vazio" do mesmo jeito.
     */
    private function textoOuNulo(string $chave): ?string
    {
        $valor = config($chave);

        if (! is_scalar($valor)) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function inteiroOuNulo(string $chave): ?int
    {
        $valor = config($chave);

        return is_numeric($valor) ? (int) $valor : null;
    }
};
