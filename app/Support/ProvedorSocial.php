<?php

declare(strict_types=1);

namespace App\Support;

use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as UsuarioDoProvedor;

/**
 * Os provedores de login social do kit — e a única coisa que varia de verdade entre eles.
 *
 * ## Por que agora é um enum, quando antes era uma constante
 *
 * O ADR-10 da wiki `login-social-google` recusou toda abstração e escreveu o critério de
 * reabertura: "enum de um caso é abstração sem segundo caso. Quando o GitHub (ou outro)
 * entrar, a decisão de extrair se toma com DOIS casos na mão — feita com um, ela adivinha a
 * forma."
 *
 * A forma que os quatro casos revelaram NÃO é a que se adivinharia com um. O redirect, o
 * botão e o predicado de disponibilidade são idênticos em todos — não precisavam de
 * abstração nenhuma. O que varia, e varia radicalmente, é **como cada provedor prova que o
 * e-mail está verificado**. Uma interface desenhada em cima do Google teria abstraído os três
 * primeiros e deixado de fora o único que precisava.
 *
 * É por isso que este enum tem `emailVerificado()` e não tem `redirect()`.
 *
 * ## A regra que elimina toda tabela de mapeamento
 *
 * O `value` de cada caso é, ao mesmo tempo:
 *
 *   - o nome do driver do Socialite (`Socialite::driver($provedor->value)`);
 *   - o segmento da URL (`/auth/{value}/callback`);
 *   - a chave em `config/services.php`;
 *   - a chave em `config/kit.php` → `login.{value}.habilitado`.
 *
 * Daí `linkedin-openid` como valor do caso `LinkedIn`, e não `linkedin`: é o nome que o
 * `SocialiteManager` exige (`vendor/laravel/socialite/src/SocialiteManager.php:108-113`, que
 * lê `services.linkedin-openid` em `:110`). A URL fica `/auth/linkedin-openid/callback`, que
 * é menos bonito e mais informativo — diz ao operador qual das duas APIs de LinkedIn está no
 * ar. A alternativa (valor curto + um método `driver()`) introduz duas strings por provedor e
 * um lugar para elas divergirem, e a divergência é silenciosa: credencial gravada numa chave
 * e lida na outra põe um botão no ar apontando para um OAuth que não existe. Ver ADR-01.
 *
 * `propriedadeDeSettings()` é a ÚNICA transformação de nome no desenho, e existe por uma
 * limitação da linguagem: nome de propriedade PHP não aceita hífen.
 *
 * ## Quem NÃO está aqui, e por quê
 *
 * **Facebook** — não expõe nenhum sinal de e-mail verificado. O `verified` que o provider
 * pede (`vendor/laravel/socialite/src/Two/FacebookProvider.php:34`) é de nível de CONTA,
 * legado, e ausente na Graph v23.0 que ele usa (`:27`); o caminho OIDC/Limited Login
 * (`:134-167`) devolve claims sem `email_verified`. Aceitá-lo faria o nível de garantia do
 * login depender de qual botão a pessoa clicou. Ver ADR-05.
 *
 * **Discord** — não é driver do Socialite. `vendor/laravel/socialite/src/Two/` não tem
 * `DiscordProvider.php`, e a documentação oficial lista "Facebook, X, LinkedIn, Google,
 * GitHub, GitLab, Bitbucket, and Slack", remetendo o resto ao catálogo comunitário
 * `socialiteproviders.com`. Entraria com uma dependência nova mais um listener de evento —
 * um segundo mecanismo de extensão num desenho cuja premissa é que provedor é caso de enum.
 * Ver ADR-04 e a seção de login social do README.
 *
 * **Twitter OAuth 1.0** — o `One\TwitterProvider` não põe o e-mail nem no payload bruto
 * (`vendor/laravel/socialite/src/One/TwitterProvider.php:23`), então a barreira de
 * verificação não tem onde encostar. O kit usa o driver `x`.
 *
 * ## Acrescentar o próximo provedor
 *
 * Um caso aqui (com o ramo dele em `emailVerificado()`, que o `match` exaustivo COBRA), um
 * bloco em `config/services.php`, um em `config/kit.php`, três propriedades no
 * `ConfiguracoesDoKit` com a migration, e uma partial de SVG. Nenhum arquivo de lógica muda:
 * as rotas, o controller, o blade e a tela de Settings percorrem `cases()`.
 *
 * Wiki: `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/`.
 */
enum ProvedorSocial: string
{
    case Google = 'google';

    case Github = 'github';

    case LinkedIn = 'linkedin-openid';

    case X = 'x';

    /** Como o provedor se chama na tela e no log — "Entrar com {rotulo}". */
    public function rotulo(): string
    {
        return match ($this) {
            self::Google   => 'Google',
            self::Github   => 'GitHub',
            self::LinkedIn => 'LinkedIn',
            self::X        => 'X',
        };
    }

    /**
     * Nome da partial do SVG, em `resources/views/filament/auth/icones/`.
     *
     * Sem hífen de propósito: é nome de arquivo, e `linkedin` basta para identificá-lo. O
     * `value` continua sendo `linkedin-openid` onde ele importa — driver, URL e config.
     */
    public function icone(): string
    {
        return match ($this) {
            self::Google   => 'google',
            self::Github   => 'github',
            self::LinkedIn => 'linkedin',
            self::X        => 'x',
        };
    }

    /**
     * O nome da propriedade correspondente em `App\Settings\ConfiguracoesDoKit`.
     *
     * `login_linkedin_openid_habilitado`, `login_x_client_secret`, e assim por diante. O
     * hífen do `value` vira sublinhado porque nome de propriedade PHP não aceita hífen — é a
     * única tradução de nome do desenho, e ela é mecânica.
     */
    public function propriedadeDeSettings(string $sufixo): string
    {
        return 'login_'.str_replace('-', '_', $this->value).'_'.$sufixo;
    }

    /**
     * O e-mail está PROVADAMENTE verificado no provedor?
     *
     * Falha FECHADO em todos os ramos, e o motivo é o mesmo do Google: casar conta por e-mail
     * que o provedor não verificou é a tomada de conta clássica do login social — bastaria
     * criar uma conta no provedor com o e-mail de outra pessoa. Com o registro do kit fechado
     * (o default), o caminho principal é justamente o casamento com conta existente, que é o
     * lado mais perigoso.
     *
     * Cada provedor prova de um jeito diferente, e a investigação está no ADR-03 com
     * `file:line`. Resumo, sobre `vendor/laravel/socialite/src/Two/`:
     *
     *   - **Google** — `email_verified`, mais o alias `verified_email` que o provider mantém
     *     por compatibilidade (`GoogleProvider.php:90-92`), no bruto.
     *   - **LinkedIn OpenID** — `email_verified` no bruto (`LinkedInOpenIdProvider.php:61,73`)
     *     e também mapeado no objeto (`:80`).
     *   - **X** — a PRESENÇA do e-mail é a prova: o provider pede `confirmed_email`
     *     (`TwitterProvider.php:61`) e mapeia esse campo — e só esse — para `email` (`:74`).
     *     O X não devolve endereço que ele não tenha confirmado.
     *   - **GitHub** — também a PRESENÇA, e por um caminho menos óbvio: o provider SEMPRE
     *     sobrescreve o e-mail quando `user:email` está nos escopos
     *     (`GithubProvider.php:47-49`), com o primeiro `primary && verified` (`:73`) ou com
     *     **`null`** — na exceção (`:70`) e também quando nenhum casa (`:76`). Não há sinal de
     *     verificação no bruto, e não precisa: e-mail preenchido já significa verificado, e
     *     e-mail nulo cai na barreira de `email_ausente` do controller.
     *
     * `getRaw()` NÃO está no contrato `Socialite\Contracts\User` — ele é de `AbstractUser`.
     * Provedor que não exponha o payload bruto não permite conferir a verificação, e aí a
     * resposta é NÃO, nunca "assume que sim". A estreiteza do tipo aqui é a decisão de
     * segurança, não um contorno dela.
     *
     * `filter_var` e não um cast de bool: o valor chega do JSON do provedor, e a string
     * `"false"` num cast de bool é `true`.
     *
     * **Cuidado ao ler o valor.** `AbstractUser::map()` só atribui a propriedade real quando
     * `property_exists` (`vendor/laravel/socialite/src/AbstractUser.php:138-149`), então
     * `$doProvedor->email_verified` funciona SÓ no LinkedIn OpenID e é `null` no Google. E
     * `$doProvedor['email_verified']` (`ArrayAccess`, `:170-173`) lê o BRUTO, não os
     * atributos — os dois acessores apontam para arrays diferentes. Ler pelo lugar errado
     * devolveria `null` e, com falha fechada, recusaria TODO login: um defeito que se
     * manifesta como "este provedor nunca deixa ninguém entrar". Daí `getRaw()` explícito.
     */
    public function emailVerificado(UsuarioDoProvedor $doProvedor): bool
    {
        if (! $doProvedor instanceof AbstractUser) {
            return false;
        }

        return match ($this) {
            self::Google   => $this->booleanoDoBruto($doProvedor, ['email_verified', 'verified_email']),
            self::LinkedIn => $this->booleanoDoBruto($doProvedor, ['email_verified']),

            /*
             * Os dois em que a PRESENÇA do e-mail é a prova — por motivos diferentes, os dois
             * lidos no `vendor/` e citados abaixo.
             *
             * **X**: o provider pede `confirmed_email` e mapeia esse campo, e nenhum outro,
             * para `email` (`TwitterProvider.php:61,74`). O X não devolve endereço que ele não
             * tenha confirmado.
             *
             * **GitHub**: o provider SEMPRE sobrescreve o e-mail quando `user:email` está nos
             * escopos (`GithubProvider.php:47-49`), e o valor que ele escreve é o primeiro
             * `primary && verified` (`:73`) ou **`null`** — tanto na exceção (`:70`) quanto
             * quando nenhum e-mail casa (`:76`, saindo pelo fim do método). Então, para o
             * GitHub, e-mail preenchido significa `primary && verified`; e-mail nulo cai na
             * barreira de `email_ausente` do controller.
             *
             * O `user:email` está SEMPRE nos escopos: é o default do provider (`:16`), e a
             * chave `scopes` de `config/services.php` passa por `scopes()`, que **soma**
             * (`AbstractProvider.php:398`) — só `setScopes()` substitui (`:410`), e o kit não
             * a chama. Essa é a invariante em que este ramo se apoia, e ela é enforçada por
             * caso de teste, não por código em execução.
             *
             * A segunda condição é a guarda contra o argumento envelhecer: se o provedor
             * passar a mandar um campo de verificação, ele VENCE a presença. Sem ela, este
             * ramo autenticaria alguém com `email_verified => false` explícito no payload —
             * exatamente o que o ADR-05 recusou o Facebook por permitir, e o argumento não
             * pode valer num provedor e não valer no outro.
             */
            self::X, self::Github => filled($doProvedor->getEmail())
                && $this->naoDesmentidoNoBruto($doProvedor, ['email_verified']),
        };
    }

    /**
     * O primeiro dos valores presentes no payload bruto, avaliado como booleano.
     *
     * Duas chaves no caso do Google porque o provider popula as duas. Ausência de todas
     * devolve `false` — a falha fechada.
     *
     * @param  array<int, string>  $chaves
     */
    private function booleanoDoBruto(AbstractUser $doProvedor, array $chaves): bool
    {
        $bruto = $doProvedor->getRaw();

        foreach ($chaves as $chave) {
            if (array_key_exists($chave, $bruto)) {
                return filter_var($bruto[$chave], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return false;
    }

    /**
     * O bruto NÃO desmente a verificação? — a guarda do ramo do X.
     *
     * Diferente de `booleanoDoBruto()` na direção do default: lá a ausência é `false` (exige
     * prova), aqui a ausência é `true` (não há desmentido). Os dois são falha fechada em
     * contextos opostos, e é por isso que são dois métodos e não um com bandeira: um parâmetro
     * `$padrao` faria a chamada de cada ramo depender de um booleano na assinatura, que é o
     * jeito mais fácil de inverter uma decisão de segurança sem ninguém notar na revisão.
     *
     * **Só chave BOOLEANA entra aqui.** `confirmed_email` do X parece candidata e não é: ela
     * guarda o ENDEREÇO, e `filter_var('a@b.com', FILTER_VALIDATE_BOOLEAN)` é `false` — passá-la
     * faria este método recusar todo login do X. Custou uma correção antes do commit.
     *
     * @param  array<int, string>  $chaves  nomes de chaves cujo valor é booleano
     */
    private function naoDesmentidoNoBruto(AbstractUser $doProvedor, array $chaves): bool
    {
        $bruto = $doProvedor->getRaw();

        foreach ($chaves as $chave) {
            if (! array_key_exists($chave, $bruto)) {
                continue;
            }

            /*
             * `null` e string vazia NÃO são desmentido — são ausência de informação, e o ramo de
             * presença já tem a prova dele. `blank()` e não `filled()` invertido porque
             * `blank(false)` é FALSE no Laravel: o booleano `false` é informação, e é exatamente
             * o desmentido que este método existe para pegar.
             *
             * A assimetria com `booleanoDoBruto()` é deliberada: lá a pergunta é "há prova?" e
             * `null` responde não; aqui é "há desmentido?" e `null` responde não também. Os dois
             * são falha fechada em contextos opostos.
             */
            if (blank($bruto[$chave])) {
                continue;
            }

            if (! filter_var($bruto[$chave], FILTER_VALIDATE_BOOLEAN)) {
                return false;
            }
        }

        return true;
    }
}
