<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Substituição pontual numa linha de arquivo de texto — `.env` e configs.
 *
 * Pontual é o ponto: mexe só na linha alvo e preserva comentários, ordem e
 * qualquer chave que o usuário tenha acrescentado. Reescrever o arquivo a
 * partir de um template seria mais simples e apagaria o que não é nosso.
 *
 * É o mesmo desenho do `replaceInFile()` do instalador do Laravel, com um
 * acréscimo: quando o padrão não casa, dá para anexar a chave no fim em vez de
 * perdê-la em silêncio (`$fallback`) — o caso de um `.env` antigo que não tem a
 * chave nova.
 *
 * Nasceu privada dentro do `KitTenancy`; virou classe quando o
 * `CustomizadorDaInstalacao` passou a precisar da mesma coisa. Dois chamadores
 * reais, não uma camada especulativa.
 */
final class SubstituicaoEmArquivo
{
    /**
     * @param  string  $padrao  regex com delimitadores; use `/m` para casar linha a linha
     * @param  string  $novo  a linha inteira, já pronta
     * @param  string|null  $fallback  anexado ao fim quando o padrão não casa; `null` não anexa nada
     * @return bool se o arquivo foi alterado
     */
    public static function aplicar(string $caminho, string $padrao, string $novo, ?string $fallback = null): bool
    {
        if (! File::exists($caminho)) {
            return false;
        }

        $conteudo = File::get($caminho);

        /*
         * Limite de 1: sem ele, um padrão que também casa dentro de um comentário
         * ("# APP_NAME=…, mude aqui") reescreveria a documentação junto com o valor.
         */
        if (preg_match($padrao, $conteudo) === 1) {
            File::put($caminho, (string) preg_replace($padrao, $novo, $conteudo, 1));

            return true;
        }

        if ($fallback === null) {
            return false;
        }

        File::append($caminho, $fallback);

        return true;
    }

    /**
     * Grava `CHAVE=valor` no .env, case a linha esteja preenchida, comentada ou ausente.
     *
     * Os três estados existem no mesmo arquivo recém-copiado do `.env.example`:
     * `APP_NAME` vem preenchida, `DB_HOST` vem comentada e uma chave nova pode
     * não existir. Um padrão que só trate o primeiro caso perde os outros dois
     * sem erro nenhum.
     *
     * O valor é sempre citado e escapado: ele vem de texto digitado por uma
     * pessoa e vai para dentro de um arquivo de configuração. Aspas, barras e
     * quebras de linha aqui não são detalhe de formatação — quebra de linha
     * INJETA uma chave nova no .env.
     */
    public static function definirNoEnv(string $caminho, string $chave, string $valor): bool
    {
        $linha = $chave.'="'.self::escaparValorDeEnv($valor).'"';

        return self::aplicar(
            $caminho,
            '/^#?\s*'.preg_quote($chave, '/').'=.*$/m',
            $linha,
            PHP_EOL.$linha.PHP_EOL,
        );
    }

    /**
     * Neutraliza o que quebraria o parse do .env ou permitiria injetar uma linha.
     *
     * Quebra de linha vira espaço em vez de `\n` escapado de propósito: o dotenv
     * do PHP expande `\n` dentro de aspas duplas, e o valor voltaria a conter uma
     * quebra — só que agora dentro do próprio valor, o que quebra
     * `${APP_NAME}` em MAIL_FROM_NAME e VITE_APP_NAME.
     */
    private static function escaparValorDeEnv(string $valor): string
    {
        return str_replace(
            ['\\', '"', '$', "\r\n", "\n", "\r"],
            ['\\\\', '\\"', '\\$', ' ', ' ', ' '],
            $valor,
        );
    }
}
