<?php

declare(strict_types=1);

namespace App\Settings;

use Illuminate\Support\Facades\Log;
use Spatie\LaravelSettings\Settings;

/**
 * As configurações da INSTALAÇÃO, editáveis em /admin/configuracoes-do-kit.
 *
 * ## Quem é a fonte da verdade
 *
 * Até esta classe existir, o `.env` era a única fonte: o `kit:install` escrevia
 * nele, `config/kit.php` e `config/app.php` liam dele, e todo consumidor lia
 * `config()`. Um settings no banco cria uma SEGUNDA fonte, e duas fontes sem
 * regra de precedência é a definição de configuração inconsistente — alguém
 * edita o .env, nada muda, e ninguém consegue explicar por quê.
 *
 * A regra, e ela é dura:
 *
 *   **o banco vence em tempo de execução; o .env semeia e é o plano B.**
 *
 * Como isso funciona sem tocar em nenhum consumidor:
 *
 * 1. `database/settings/*_create_kit_settings.php` semeia cada propriedade com o
 *    valor de `config(...)`, que vem do .env. Instalação nova carrega as
 *    respostas do `kit:install` para o banco sem uma linha de sincronização.
 * 2. `KitServiceProvider::configureSettingsDoKit()` chama `aplicarNaConfig()` no
 *    boot, sobrepondo a config do processo com o que o banco tem.
 * 3. `CorPrimaria::paleta()`, os três painéis, `ConfiguraFilamentGlobal` e o
 *    MailManager do Laravel continuam lendo `config()`. Nenhum sabe que isto
 *    existe.
 *
 * Sem linha no banco, o alinhamento é no-op e o .env volta a ser a única fonte —
 * é por isso que `migrate:rollback` na migration de settings é um desligamento
 * completo, e por isso a suíte de testes não precisou de arranjo nenhum (no
 * `RefreshDatabase` o boot acontece antes das migrations).
 *
 * Ver ADR-01 em wikis/specs/feat/settings-do-kit/settings-do-kit/.
 *
 * ## Isto NÃO é o settings de uma organização
 *
 * A identidade visual de um tenant (multi-tenancy) é CRUD comum em
 * /admin/organizacoes, nas colunas `cor_primaria` e `logo` do model `Tenant`, e
 * ela VENCE esta dentro de /app/{slug} — o `bootUsing()` do AppPanelProvider a
 * registra mais tarde no ciclo, e quem registra por último vence. Nada aqui
 * pertence a uma organização.
 *
 * ## Auditoria
 *
 * Esta classe não é um model Eloquent, então `App\Traits\AuditsFillables` não se
 * aplica — e apontar o repositório do spatie para uma model com a trait audita
 * só a CRIAÇÃO, porque `updatePropertiesPayload()` grava com `upsert()`
 * (vendor/spatie/laravel-settings/src/SettingsRepositories/DatabaseSettingsRepository.php:74-77),
 * que não dispara evento de Eloquent. A trilha sai de
 * `App\Listeners\AuditarConfiguracoesDoKit`, ouvindo `SavingSettings`. Ver ADR-07.
 *
 * ## Ao acrescentar uma propriedade
 *
 * Três lugares, sempre: a propriedade aqui, a linha em `mapaDeConfiguracao()` e
 * o `add()`/`deleteIfExists()` numa migration de settings. O mapa mora nesta classe
 * de propósito — esquecer a linha fica visível no mesmo arquivo.
 *
 * A migration é NOVA, nunca a que já rodou: instalação de terceiro que só roda
 * `migrate` ficaria sem a linha, e `aplicarNaConfig()` estoura `MissingSettings`
 * no boot de todo request. Ver ADR-05 da wiki `verificacao-de-email-editavel`.
 */
final class ConfiguracoesDoKit extends Settings
{
    // Identidade -------------------------------------------------------------

    public string $nome_da_aplicacao;

    /** Nome de uma constante de `Filament\Support\Colors\Color`. */
    public ?string $cor_primaria;

    /** Hexadecimal livre. VENCE a `cor_primaria` — ver `App\Support\CorPrimaria`. */
    public ?string $cor_primaria_hex;

    /** Caminho no disco `public`, resolvido por `App\Support\IdentidadeDoKit`. */
    public ?string $logo;

    public ?string $favicon;

    public ?string $arte_do_login;

    // E-mail -----------------------------------------------------------------

    public string $mail_mailer;

    public ?string $mail_host;

    public ?int $mail_port;

    public ?string $mail_scheme;

    public ?string $mail_username;

    public ?string $mail_password;

    public ?string $mail_from_address;

    public ?string $mail_from_name;

    // Tabelas ----------------------------------------------------------------

    public int $paginacao_padrao;

    public bool $tabela_listrada;

    public bool $persistir_filtros;

    public bool $colunas_redimensionaveis;

    // Kit --------------------------------------------------------------------

    public bool $hub_de_navegacao;

    public string $rotulo_da_organizacao;

    public string $rotulo_das_organizacoes;

    // Registro aberto --------------------------------------------------------

    public bool $registro_habilitado;

    public bool $registro_aprovacao_manual;

    /**
     * Exige e-mail validado no /app.
     *
     * Editável desde que a decisão saiu do array da rota — ver
     * `App\Http\Middleware\ExigirEmailVerificado` e a nota no `mapaDeConfiguracao()`.
     */
    public bool $registro_verificar_email;

    // Login social e rodapé --------------------------------------------------

    public bool $login_google_habilitado;

    public ?string $login_google_client_id;

    public ?string $login_google_client_secret;

    public ?string $login_rodape;

    public static function group(): string
    {
        return 'kit';
    }

    /**
     * A senha do SMTP é cifrada no `payload`.
     *
     * A tabela `settings` guarda JSON em claro, e um dump de banco, um backup e a
     * tela de auditoria são três caminhos que a permissão da tela não cobre.
     * Rotacionar a `APP_KEY` torna o valor ilegível — comportamento normal de
     * valor cifrado no Laravel, e o `catch (Throwable)` do provider garante que
     * isso derrube a leitura, não a aplicação.
     *
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['mail_password'];
    }

    /**
     * Propriedade → chave de `config()`. A ÚNICA cópia deste mapa.
     *
     * Ele existe para que nenhum consumidor precise saber que este settings
     * existe: quem lê `config('app.name')` ou `config('kit.tabelas.paginacao')`
     * recebe o valor do banco sem nenhuma alteração de código.
     *
     * @return array<string, string>
     */
    public static function mapaDeConfiguracao(): array
    {
        return [
            'nome_da_aplicacao'        => 'app.name',
            'cor_primaria'             => 'kit.cor_primaria',
            'cor_primaria_hex'         => 'kit.cor_primaria_hex',
            'logo'                     => 'kit.identidade.logo',
            'favicon'                  => 'kit.identidade.favicon',
            'arte_do_login'            => 'kit.identidade.arte_do_login',
            'mail_mailer'              => 'mail.default',
            'mail_host'                => 'mail.mailers.smtp.host',
            'mail_port'                => 'mail.mailers.smtp.port',
            'mail_scheme'              => 'mail.mailers.smtp.scheme',
            'mail_username'            => 'mail.mailers.smtp.username',
            'mail_password'            => 'mail.mailers.smtp.password',
            'mail_from_address'        => 'mail.from.address',
            'mail_from_name'           => 'mail.from.name',
            'paginacao_padrao'         => 'kit.tabelas.paginacao',
            'tabela_listrada'          => 'kit.tabelas.listrada',
            'persistir_filtros'        => 'kit.tabelas.persistir_filtros',
            'colunas_redimensionaveis' => 'kit.tabelas.colunas_redimensionaveis',
            'hub_de_navegacao'         => 'kit.hub',
            'rotulo_da_organizacao'    => 'kit.tenancy.label',
            'rotulo_das_organizacoes'  => 'kit.tenancy.label_plural',
            /*
             * O registro aberto entra pelo MAPA, e por isso `App\Support\RegistroAberto` não
             * muda uma linha: os três métodos dele leem `config('kit.registro.*')`, e
             * `aplicarNaConfig()` sobrepõe essa config com o banco no boot do
             * `KitServiceProvider`. O "ponto único de ligação" que aquela classe documenta
             * acabou não precisando ser reescrito — o mapa É a ligação.
             *
             * E isto resolve a armadilha que o docblock dela previa: a leitura NÃO passa a
             * tocar o banco em todo request. Quem toca é o `aplicarNaConfig()`, uma vez por
             * boot, já com o `Schema::hasTable()` e o try/catch do provider — então
             * `migrate` em base nova, clone e CI seguem lendo o `.env`.
             *
             * **`verificar_email` ESTA aqui desde a v0.20, e entrar custou uma inversao.**
             * Ela nao podia estar: o `AppPanelProvider` a lia no BOOT, e o middleware de
             * e-mail verificado e fixado no array da rota no momento do registro
             * (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`), nao por
             * request — nem Closure em `isRequired` resolveria, porque quem avalia e o
             * REGISTRO da rota. O campo na tela era um toggle que gravava e nao fazia nada.
             *
             * O que mudou nao foi o mapa, foi o que esta fixado no array da rota: o painel
             * aplica a exigencia SEMPRE e declara a classe do kit em
             * `emailVerifiedMiddlewareName()`, entao a rota guarda um DECISOR em vez de uma
             * decisao. `App\Http\Middleware\ExigirEmailVerificado` pergunta a cada request,
             * e a linha abaixo e o que faz a resposta vir do banco. Ver a wiki
             * `verificacao-de-email-editavel`.
             */
            'registro_habilitado'       => 'kit.registro.habilitado',
            'registro_aprovacao_manual' => 'kit.registro.aprovacao_manual',
            'registro_verificar_email'  => 'kit.registro.verificar_email',
            /*
             * Login social e rodapé. Estas quatro nunca tiveram o problema de
             * `registro_verificar_email`: as duas que decidem algo são lidas por request — o
             * `abort_unless()` do `LoginComGoogleController` e a closure do render hook do
             * botão. Nada aqui é decidido no boot do painel, e as rotas de `/auth/google/*`
             * nascem sempre de propósito (registrá-las dentro de um `if` quebraria
             * `route('auth.google.*')`); quem recusa é o controller, com 404.
             */
            'login_google_habilitado'    => 'kit.login.google.habilitado',
            'login_google_client_id'     => 'services.google.client_id',
            'login_google_client_secret' => 'services.google.client_secret',
            'login_rodape'               => 'kit.login.rodape',
        ];
    }

    /**
     * Sobrepõe a configuração do processo com o que está gravado.
     *
     * Chamado uma vez por request e por comando artisan, do
     * `KitServiceProvider::boot()`, ANTES de `configuraFilamentGlobal()` — que lê
     * `kit.tabelas.*`. A ordem é requisito, não estilo.
     *
     * A leitura de `$this->{$propriedade}` dispara o carregamento do grupo
     * inteiro numa única query (`getPropertiesInGroup`), então o custo é uma
     * query por boot, e `SETTINGS_CACHE_ENABLED` existe para zerar isso.
     */
    public function aplicarNaConfig(): void
    {
        $mapa = self::mapaDeConfiguracao();
        $novo = [];

        foreach ($mapa as $propriedade => $chave) {
            $novo[$chave] = $this->{$propriedade};
        }

        config($novo);

        Log::channel('configuracoes')->debug(
            '[ConfiguracoesDoKit@aplicarNaConfig] Configuração do processo alinhada com o banco | grupo: '.self::group(),
            ['chaves' => array_values($mapa)],
        );
    }
}
