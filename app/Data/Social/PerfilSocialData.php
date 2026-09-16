<?php

declare(strict_types=1);

namespace App\Data\Social;

use App\Support\ProvedorSocial;
use Laravel\Socialite\Contracts\User as UsuarioDoProvedor;
use Spatie\LaravelData\Data;

/**
 * O perfil que volta do provedor social, com a fronteira do kit desenhada.
 *
 * ## Por que existe
 *
 * O `LoginSocialController` lia `getEmail()`, `getId()` e `getName()` direto do `AbstractUser` do
 * Socialite, e a informação de "e-mail verificado" era garimpada do `getRaw()` com três nomes
 * possíveis. O contrato do que o kit consome do provedor não existia em lugar nenhum.
 *
 * ## O que NÃO entra aqui
 *
 * Credencial. O `AbstractUser` carrega `token` e `refreshToken`, e o `getRaw()` de alguns
 * provedores repete `access_token`/`secret`. Este Data é serializável (`toArray()`, `toJson()`,
 * payload de fila), então credencial dentro dele é superfície de vazamento nova — ADR-04 e P6 da
 * wiki `laravel-data-como-padrao-de-dto`. O `bruto` sai filtrado.
 *
 * ## `emailVerificado` aqui é INFORMAÇÃO, não decisão
 *
 * A marca deste Data é a leitura genérica dos três nomes que os provedores usam, e ela distingue
 * três estados: `true` (o provedor afirmou), `false` (o provedor desmentiu) e `null` (o provedor
 * não disse). Quem **decide** se o login segue continua sendo
 * `ProvedorSocial::emailVerificado()`, que tem regra própria por provedor — misturar as duas
 * inverteria uma decisão de segurança para caber num tipo.
 */
final class PerfilSocialData extends Data
{
    /**
     * Nomes que os provedores usam para a mesma informação. O Google popula os dois primeiros; o
     * X usa o terceiro, mas com o ENDEREÇO dentro, e por isso ele é lido como presença, nunca
     * como booleano (ver `ProvedorSocial::naoDesmentidoNoBruto()`).
     */
    private const CHAVES_DE_VERIFICACAO = ['email_verified', 'verified_email', 'confirmed_email'];

    /** Chaves do payload bruto que nunca viajam no Data. */
    private const CREDENCIAIS = ['token', 'refresh_token', 'refreshToken', 'access_token', 'accessToken', 'secret'];

    /**
     * @param  array<string, mixed>  $bruto  payload do provedor, sem credenciais
     */
    public function __construct(
        public readonly string $provedor,
        public readonly string $id,
        public readonly ?string $email = null,
        public readonly ?string $nome = null,
        public readonly ?string $avatar = null,
        public readonly ?bool $emailVerificado = null,
        public readonly array $bruto = [],
    ) {}

    /**
     * A fábrica da fronteira com o Socialite.
     *
     * O e-mail chega normalizado (minúsculo, sem bordas) e **vazio vira `null`**: o kit trata
     * "não devolveu e-mail" e "devolveu string vazia" como o mesmo caso, e deixar `''` circular
     * faria uma string vazia passar por endereço nas comparações seguintes.
     */
    public static function doSocialite(ProvedorSocial $provedor, UsuarioDoProvedor $usuario): self
    {
        $email = mb_strtolower(trim((string) $usuario->getEmail()));
        $nome  = trim((string) $usuario->getName());

        /*
         * `getRaw()` nao esta no contrato `Socialite\Contracts\User` — ele vive no
         * `AbstractUser`, que e o que todo driver devolve na pratica. Tipar o parametro pela
         * classe concreta acoplaria esta fronteira ao Two/One do Socialite; a checagem mantem o
         * contrato e devolve lista vazia se algum driver nao expuser o bruto.
         */
        $bruto = method_exists($usuario, 'getRaw') ? (array) $usuario->getRaw() : [];

        return self::from([
            'provedor'        => $provedor->value,
            'id'              => trim((string) $usuario->getId()),
            'email'           => $email === '' ? null : $email,
            'nome'            => $nome === '' ? null : $nome,
            'avatar'          => $usuario->getAvatar(),
            'emailVerificado' => self::verificacaoNoBruto($bruto),
            'bruto'           => self::semCredenciais($bruto),
        ]);
    }

    /**
     * `true` quando o provedor afirmou, `false` quando desmentiu, `null` quando não disse.
     *
     * A distinção importa: `false` é informação (o provedor sabe que não verificou) e `null` é
     * ausência dela. Colapsar os dois em `false` apagaria a diferença que o vínculo social usa.
     *
     * @param  array<string, mixed>  $bruto
     */
    private static function verificacaoNoBruto(array $bruto): ?bool
    {
        foreach (self::CHAVES_DE_VERIFICACAO as $chave) {
            if (! array_key_exists($chave, $bruto)) {
                continue;
            }

            $valor = $bruto[$chave];

            // `confirmed_email` do X guarda o ENDEREÇO, não um booleano: presença de texto é
            // afirmação, e `filter_var` devolveria `false` para "a@b.com".
            if (is_string($valor) && ! in_array(mb_strtolower($valor), ['0', '1', 'true', 'false', ''], true)) {
                return true;
            }

            return blank($valor) ? null : filter_var($valor, FILTER_VALIDATE_BOOLEAN);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $bruto
     * @return array<string, mixed>
     */
    private static function semCredenciais(array $bruto): array
    {
        return array_diff_key($bruto, array_flip(self::CREDENCIAIS));
    }
}
