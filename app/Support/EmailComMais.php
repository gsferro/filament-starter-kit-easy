<?php

namespace App\Support;

use App\Models\User;
use Closure;

/**
 * O "+" na parte local do e-mail (`fulano+loja@gmail.com`): permitido ou bloqueado no cadastro.
 *
 * `fulano+qualquer-coisa@gmail.com` cai na caixa de `fulano@gmail.com`, então uma caixa só rende
 * cadastros sem fim. Com a opção desligada, toda via que DÁ um e-mail novo a uma conta — registro
 * aberto, convite para conta nova, CRUD de usuários, login social que cria conta, troca de e-mail
 * no perfil — recusa o endereço com "+". Wiki `wikis/specs/feat/email-com-mais/`.
 *
 * É a dona única de `kit.email.permitir_mais` (`.ai/rules/config.md`): ninguém lê a chave direto.
 *
 * O que NÃO é recusado (P-04 e P-06 da wiki): conta que já tem "+" (a regra ignora o e-mail
 * atual), convite para um e-mail que já é de uma conta (não cria conta, só vincula) e convite já
 * emitido (o aceite não passa por aqui).
 */
class EmailComMais
{
    /**
     * O "+" está permitido nesta instalação?
     *
     * Só um "desligado" explícito bloqueia (P-01). A chave ausente — projeto que manteve o próprio
     * `config/kit.php` no `kit:update` — devolve `null`, e `null` permite: um bloqueio que nasce
     * sozinho, sem o admin pedir, recusaria cadastros que ontem passavam.
     */
    public static function permitido(): bool
    {
        return config('kit.email.permitir_mais') !== false;
    }

    /** O "+" está na parte local, antes do ÚLTIMO "@"? O que vem depois dele é domínio. */
    public static function temMais(string $email): bool
    {
        $arroba = strrpos($email, '@');

        return str_contains($arroba === false ? $email : substr($email, 0, $arroba), '+');
    }

    /** O e-mail pode ser dado a uma conta agora? */
    public static function aceita(string $email): bool
    {
        return self::permitido() || ! self::temMais($email);
    }

    /**
     * O convite para este e-mail pode nascer agora?
     *
     * Além do `aceita()`, passa o e-mail que já é de uma conta: esse convite não cria conta, só
     * vincula (P-04) — e a conta com "+" de antes da opção continua convidável para outra
     * organização. A busca ignora caixa, como `Convite::usuarioExistente()`.
     */
    public static function aceitaNoConvite(string $email): bool
    {
        return self::aceita($email) || User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])
            ->exists();
    }

    public static function mensagem(): string
    {
        return 'Este sistema não aceita e-mail com "+" antes do "@". Use o endereço sem o "+".';
    }

    /**
     * Regra de validação do campo de e-mail.
     *
     * `$atual` é o e-mail que a conta já tem (edição no CRUD, perfil): repetido, ele passa, mesmo
     * com "+" — a regra olha só o e-mail que está sendo dado à conta (P-04). A comparação ignora
     * caixa, como o `unique` do e-mail.
     *
     * No Filament, ligue com `->rule(fn (): Closure => EmailComMais::regra(...))`: o Filament
     * avalia toda closure passada a `rule()`, e a regra crua seria chamada sem os argumentos.
     */
    public static function regra(?string $atual = null): Closure
    {
        return static function (string $atributo, mixed $valor, Closure $falha) use ($atual): void {
            $email = trim((string) $valor);

            if ($atual !== null && mb_strtolower($email) === mb_strtolower(trim($atual))) {
                return;
            }

            if (! self::aceita($email)) {
                $falha(self::mensagem());
            }
        };
    }

    /** A regra do campo de e-mail do convite: a de `aceitaNoConvite()`. */
    public static function regraDeConvite(): Closure
    {
        return static function (string $atributo, mixed $valor, Closure $falha): void {
            if (! self::aceitaNoConvite(trim((string) $valor))) {
                $falha(self::mensagem());
            }
        };
    }
}
