<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as UsuarioDoProvedor;
use Laravel\Socialite\Two\User as UsuarioDeOAuth2;
use Throwable;

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
     *   - **GitHub** — não dá prova nenhuma no bruto, e é o ramo que precisa de trabalho. Ver
     *     `emailVerificadoNoGithub()`.
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
             * O X só devolve `confirmed_email`, e o provider mapeia esse campo — e nenhum
             * outro — para `email` (`TwitterProvider.php:61,74`). Então ter e-mail já É a
             * prova, e o ramo diz isso por escrito em vez de devolver um `true` que pareceria
             * descuido na revisão.
             *
             * A segunda metade é a guarda contra o próprio argumento envelhecer: se o X
             * passar a mandar um campo de verificação, ele VENCE a presença. Sem ela, este
             * ramo autenticaria alguém com `email_verified => false` explícito no payload —
             * exatamente o que o ADR-05 recusou o Facebook por permitir, e o argumento não
             * pode valer num provedor e não valer no outro. Achado da revisão adversarial
             * dos casos de teste.
             */
            self::X => filled($doProvedor->getEmail())
                && $this->naoDesmentidoNoBruto($doProvedor, ['email_verified']),

            self::Github => $this->emailVerificadoNoGithub($doProvedor),
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
            if (array_key_exists($chave, $bruto) && ! filter_var($bruto[$chave], FILTER_VALIDATE_BOOLEAN)) {
                return false;
            }
        }

        return true;
    }

    /**
     * O GitHub verifica o e-mail e DESCARTA a evidência — então o kit refaz a conferência.
     *
     * O `GithubProvider` chama `/user/emails` (`GithubProvider.php:62`), escolhe a entrada com
     * `$email['primary'] && $email['verified']` (`:73`) e guarda apenas a **string** do
     * endereço (`:48`). O payload bruto que chega aqui não tem `verified` nenhum.
     *
     * Pior: a chamada acontece só quando `'user:email'` está nos escopos (`:47`), e qualquer
     * falha cai num `catch` que faz `return` (`:68-70`) — deixando em `email` o que `/user`
     * devolveu, que é o e-mail do PERFIL PÚBLICO. Então "e-mail não vazio" não é prova de
     * verificação: é prova de que ou a verificação passou, ou a chamada falhou. De fora, os
     * dois casos são indistinguíveis.
     *
     * Confiar em `getApprovedScopes()` não resolve — ele diria que o escopo foi concedido, não
     * que a chamada funcionou, e é justamente a chamada que o `catch` engole.
     *
     * Então: uma requisição a mais, com o token que o provedor acabou de nos dar, exigindo uma
     * entrada `primary` E `verified` cujo endereço case com o que veio. É prova positiva.
     *
     * Falha fechada em tudo: token vazio, resposta não-2xx, rede fora, corpo inesperado. O
     * preço é recusar um login legítimo quando a API do GitHub estiver fora — que é a direção
     * certa do erro, e o motivo vai para o log.
     *
     * ponytail: uma chamada HTTP dentro de um enum é estranho de ver. A alternativa era um
     * serviço só para hospedar este método, e aí o nome do provedor deixaria de ficar ao lado
     * da regra dele — que é a única coisa que este enum existe para juntar. Teto conhecido: se
     * um segundo provedor precisar de chamada própria, os dois saem para um serviço junto.
     *
     * O tipo é `Two\User` e não `AbstractUser`, e a estreiteza é do próprio Socialite: o
     * `$token` é declarado em `vendor/laravel/socialite/src/Two/User.php:14`, não na classe
     * abstrata — usuário de OAuth 1.0 tem `token` e `tokenSecret`, que é outra coisa. Sem
     * token não há como refazer a consulta, então a resposta é NÃO. Foi o PHPStan que tornou
     * isso explícito, e a estreiteza aqui É a decisão de segurança.
     */
    private function emailVerificadoNoGithub(AbstractUser $doProvedor): bool
    {
        if (! $doProvedor instanceof UsuarioDeOAuth2) {
            return false;
        }

        $email = mb_strtolower(trim((string) $doProvedor->getEmail()));
        $token = (string) $doProvedor->token;

        if ($email === '' || $token === '') {
            return false;
        }

        $mascarado = Str::mask($email, '*', 3);

        try {
            $resposta = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
                ->timeout(10)
                ->get('https://api.github.com/user/emails');

            if ($resposta->failed()) {
                Log::channel('autenticacao')->warning(
                    '[ProvedorSocial@emailVerificadoNoGithub] Não foi possível confirmar o e-mail verificado no GitHub | email: '.$mascarado,
                    [
                        'motivo'   => 'github_emails_indisponivel',
                        'status'   => $resposta->status(),
                        'email'    => $mascarado,
                        'provedor' => $this->value,
                    ],
                );

                return false;
            }

            $enderecos = $resposta->json();
        } catch (Throwable $e) {
            Log::channel('autenticacao')->warning(
                '[ProvedorSocial@emailVerificadoNoGithub] Falha ao consultar os e-mails do GitHub | email: '.$mascarado,
                [
                    'exception' => $e,
                    'motivo'    => 'github_emails_falhou',
                    'email'     => $mascarado,
                    'provedor'  => $this->value,
                ],
            );

            return false;
        }

        if (! is_array($enderecos)) {
            return false;
        }

        foreach ($enderecos as $endereco) {
            if (! is_array($endereco)) {
                continue;
            }

            $mesmo = mb_strtolower(trim((string) ($endereco['email'] ?? ''))) === $email;

            if ($mesmo
                && filter_var($endereco['primary'] ?? false, FILTER_VALIDATE_BOOLEAN)
                && filter_var($endereco['verified'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ) {
                return true;
            }
        }

        Log::channel('autenticacao')->warning(
            '[ProvedorSocial@emailVerificadoNoGithub] Nenhum e-mail primário e verificado casou com o do provedor | email: '.$mascarado,
            [
                'motivo'   => 'github_email_nao_verificado',
                'email'    => $mascarado,
                'provedor' => $this->value,
            ],
        );

        return false;
    }
}
