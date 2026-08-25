<?php

use App\Settings\ConfiguracoesDoKit;
use App\Support\ProvedorSocial;
use Illuminate\Contracts\Encryption\DecryptException;
use Spatie\LaravelSettings\Exceptions\SettingDoesNotExist;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Os três provedores de login social novos — e o conserto do segredo do Google.
 *
 * ## As nove propriedades novas
 *
 * Três por provedor (`habilitado`, `client_id`, `client_secret`), semeadas com o que a
 * CONFIGURAÇÃO tem, nunca com literais. É o mesmo motivo da migration original: o valor semeado
 * sai de `config(...)`, que sai do `.env`, que é onde o `kit:install` escreveu as respostas da
 * instalação. Semear com literais faria tudo funcionar e a resposta do usuário nunca aparecer na
 * tela.
 *
 * O default de cada interruptor é `false` — quatro portas fechadas até alguém abrir uma. Semear
 * do `.env` mantém quem já ligou por lá.
 *
 * ## O conserto: `login_google_client_secret` estava cifrado só na ida
 *
 * A migration original semeou aquele valor com `addEncrypted`, mas
 * `ConfiguracoesDoKit::encrypted()` não o listava — e é ESSA lista, via
 * `SettingsConfig::isEncrypted()` (`vendor/spatie/laravel-settings/src/SettingsConfig.php:84-87`),
 * que decide se o valor é decifrado na leitura (`SettingsMapper.php:92`) e cifrado na gravação
 * (`:67`). Com o nome fora da lista, os dois se omitiam, e o resultado tinha duas caras:
 *
 *  - instalação nova com o segredo no `.env`: gravado cifrado, lido como CIPHERTEXT, e o OAuth
 *    falhava no provedor com o botão no ar;
 *  - depois de um salvamento pela tela: regravado em TEXTO CLARO, contrariando o que a tela
 *    promete por escrito.
 *
 * `encrypted()` foi corrigida — e é isso que torna este `up()` obrigatório. Sem ele, o valor que
 * alguém salvou em claro passaria a ser decifrado, `Crypto::decrypt()` estouraria, e o
 * `catch (Throwable)` do `KitServiceProvider` engoliria a leitura do GRUPO INTEIRO: a instalação
 * voltaria ao `.env` em silêncio, perdendo TODAS as configurações da tela, não só o segredo.
 *
 * A decisão de cada valor é `decrypt` com `try/catch`, e não heurística sobre o formato da
 * string: só o `decrypt` sabe se o payload é ciphertext desta `APP_KEY`.
 *
 * `null` passa reto, e é o caso de quase toda instalação — `Crypto::encrypt(null)` devolve `null`
 * (`vendor/spatie/laravel-settings/src/Support/Crypto.php:8-12`), que é exatamente por que o
 * defeito viveu duas releases sem ninguém ver.
 *
 * Ver ADR-06 de wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/.
 *
 * ## Ao acrescentar propriedade
 *
 * Não edite esta migration depois de ela ter rodado em algum lugar — crie outra. As três coisas
 * que andam juntas são: a propriedade em `ConfiguracoesDoKit`, a linha em `mapaDeConfiguracao()`
 * e o par `add`/`deleteIfExists` numa migration. Para `client_secret`, há uma quarta: o nome em
 * `ConfiguracoesDoKit::encrypted()`.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->normalizarSegredoDoGoogle();

        $this->migrator->inGroup(ConfiguracoesDoKit::group(), function (SettingsBlueprint $kit): void {
            foreach ($this->provedoresNovos() as $provedor) {
                $kit->add(
                    $provedor->propriedadeDeSettings('habilitado'),
                    (bool) config("kit.login.{$provedor->value}.habilitado", false),
                );

                $kit->add(
                    $provedor->propriedadeDeSettings('client_id'),
                    $this->textoOuNulo("services.{$provedor->value}.client_id"),
                );

                // Cifrado na tabela, e o nome está em `ConfiguracoesDoKit::encrypted()` — o par
                // que a migration original não fechou. Ver o docblock desta classe.
                $kit->addEncrypted(
                    $provedor->propriedadeDeSettings('client_secret'),
                    $this->textoOuNulo("services.{$provedor->value}.client_secret"),
                );
            }
        });
    }

    public function down(): void
    {
        foreach ($this->provedoresNovos() as $provedor) {
            foreach (['habilitado', 'client_id', 'client_secret'] as $sufixo) {
                $this->migrator->deleteIfExists(
                    ConfiguracoesDoKit::group().'.'.$provedor->propriedadeDeSettings($sufixo),
                );
            }
        }

        /*
         * O segredo do Google NÃO é revertido para texto claro, de propósito: enquanto
         * `encrypted()` o listar, ciphertext é o estado CORRETO. Se a intenção for voltar à
         * v0.19.3 inteira, reverta o código primeiro e o dado depois — na ordem inversa, a
         * leitura do grupo estoura no meio.
         */
    }

    /**
     * Os provedores que ESTA migration acrescenta — todos menos o Google, que já existia.
     *
     * Deriva do enum em vez de listar à mão para que a lista não possa divergir dos casos
     * existentes. Migration já rodada em algum lugar não deve ser editada, então um provedor
     * futuro entra numa migration nova — e o `add()` deste `up()` estouraria
     * `SettingAlreadyExists` se rodasse duas vezes, que é o comportamento desejado.
     *
     * @return array<int, ProvedorSocial>
     */
    private function provedoresNovos(): array
    {
        return array_values(array_filter(
            ProvedorSocial::cases(),
            static fn (ProvedorSocial $provedor): bool => $provedor !== ProvedorSocial::Google,
        ));
    }

    /**
     * Põe `login_google_client_secret` no estado que `encrypted()` agora espera: cifrado.
     *
     * `update()` com `$encrypted = false` entrega o payload CRU à closure e grava o que ela
     * devolver, cru (`vendor/spatie/laravel-settings/src/Migrations/SettingsMigrator.php:81-95`).
     * É o que se quer aqui — a decisão de cifrar ou não é da closure, caso a caso.
     *
     * O `catch (SettingDoesNotExist)` cobre a instalação que nunca rodou a migration original —
     * `checkIfPropertyExists()` é `protected` no `SettingsMigrator` (`:139`), então a pergunta se
     * faz pela exceção que o próprio `update()` lança (`:83`). Propriedade ausente aqui não é
     * anomalia: é uma base que ainda vai receber o `create_kit_settings`, e nela não há nada a
     * normalizar.
     */
    private function normalizarSegredoDoGoogle(): void
    {
        $propriedade = ConfiguracoesDoKit::group().'.login_google_client_secret';

        try {
            $this->migrator->update($propriedade, function ($valor) {
                /*
                 * SÓ `null` passa reto, e a estreiteza é medida. A string vazia tem de ser
                 * CIFRADA como qualquer outro texto claro: `Crypto::decrypt()` só devolve sem
                 * mexer quando o payload é `null`
                 * (`vendor/spatie/laravel-settings/src/Support/Crypto.php:14-19`), então um `''`
                 * deixado em claro faz `decrypt('')` estourar na LEITURA — e o
                 * `catch (Throwable)` do `KitServiceProvider` engole a leitura do GRUPO INTEIRO,
                 * derrubando a instalação de volta ao `.env` em silêncio e perdendo TODAS as
                 * configurações da tela.
                 *
                 * Ou seja: um `blank()` ou um `=== ''` aqui reintroduz exatamente o modo de
                 * falha que esta normalização existe para evitar. Foi um caso de teste que pegou
                 * — a linha "string vazia, a fronteira".
                 */
                if ($valor === null) {
                    return $valor;
                }

                try {
                    decrypt($valor);

                    // Já era ciphertext desta APP_KEY: a semeadura original. Nada a fazer.
                    return $valor;
                } catch (DecryptException) {
                    // Texto claro, gravado por um salvamento pela tela enquanto `encrypted()`
                    // não listava a chave. É este o valor que o conserto precisa cifrar.
                    return encrypt($valor);
                }
            });
        } catch (SettingDoesNotExist) {
            // Nada gravado ainda — nada a normalizar.
        }
    }

    /**
     * O valor da config como string, ou `null` quando não há valor.
     *
     * String vazia vira `null` de propósito: as propriedades são `?string`, e `''` num campo de
     * credencial é um valor que parece configurado e não é — e `ConfiguracaoDoLogin::disponivel()`
     * usa `filled()` justamente para isso.
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
};
