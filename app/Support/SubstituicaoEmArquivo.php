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
 *
 * Para CHAVE do `.env`, a porta é `definirNoEnv()` (valor citado e escapado) ou
 * `definirLinhaNoEnv()` (linha pronta): as duas trocam TODA linha ativa da chave
 * e nenhuma comentada (RQ-50 da wiki `diagramas-da-arquitetura`). `aplicar()`
 * fica para padrão arbitrário — config PHP, uma ocorrência só.
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
         * Limite de 1: este é o substituto genérico (`'teams' => false` num config PHP), e
         * uma ocorrência só é o contrato dele. Chave do `.env` NÃO passa por aqui — ver
         * `definirLinhaNoEnv()`, que troca toda linha ativa e nenhuma comentada (RQ-50).
         *
         * `preg_replace_callback()`, e NUNCA `preg_replace($padrao, $novo, ...)` (RD2-08): o
         * `$novo` pode ser uma linha ESCAPADA para o `.env` (`definirNoEnv()` grava `\\`, `\"` e
         * `\$` dentro de aspas). Passar essa string pronta como argumento de SUBSTITUIÇÃO de
         * `preg_replace()` é o defeito — o próprio PCRE interpreta `\\` e `\$` na substituição à
         * sua maneira (`\\` colapsa para uma barra só, `\$` come a barra e deixa o cifrão nu),
         * consumindo uma camada do escape ANTES de `$novo` chegar ao arquivo. O `.env` grava uma
         * barra mal formada, e `Dotenv\Dotenv::parse()` lança `InvalidFileException: unexpected
         * escape sequence` ao reler. O callback devolve `$novo` verbatim, sem nenhum
         * processamento de backreference/escape sobre o texto de saída.
         */
        if (preg_match($padrao, $conteudo) === 1) {
            File::put($caminho, (string) preg_replace_callback($padrao, static fn (): string => $novo, $conteudo, 1));

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
        return self::definirLinhaNoEnv($caminho, $chave, $chave.'="'.self::escaparValorDeEnv($valor).'"');
    }

    /**
     * Deixa a CHAVE do .env com a `$linha` dada em toda linha ATIVA, e nenhuma comentada.
     *
     * "Ativa" é o que o Dotenv lê como a chave — com `export` na frente, espaço em
     * volta do `=` ou indentação (`vendor/vlucas/phpdotenv/src/Parser/EntryParser.php`,
     * `parseName()` e o `trim()` de `parse()`); "comentada" é o que ele pula: primeiro
     * caractere não branco `#` (`vendor/vlucas/phpdotenv/src/Parser/Lines.php`,
     * `isCommentOrWhitespace()`). Toda ativa é trocada porque o Dotenv e a carga do
     * Laravel ficam com a ÚLTIMA definição do arquivo: trocar só a primeira deixava
     * os dois leitores com o valor velho (RQ-50, Adendo 7 da wiki
     * `diagramas-da-arquitetura`). O espaço antes do nome é `[ \t]*`, nunca `\s*`:
     * com `/m`, `\s*` começa na linha em branco anterior e consome a quebra dela.
     *
     * Sem nenhuma linha ativa, a primeira comentada é descomentada NO LUGAR (é o
     * `# DB_HOST=` que o `.env.example` deixa para preencher — P-45 da mesma wiki,
     * decidida pelo Adendo 8, RQ-53); sem nem essa, a linha é anexada ao fim — para toda
     * chave, `DB_CONNECTION` incluído: um `.env` de versão anterior à chave não pode
     * deixar o instalador seguir como se tivesse gravado (RQ-50, "qualquer leitor
     * fica com o valor gravado").
     *
     * @return bool se a gravação aconteceu — o arquivo existe e, depois, tem a linha pedida (Q?15 da wiki); `false` só sem o arquivo
     */
    public static function definirLinhaNoEnv(string $caminho, string $chave, string $linha): bool
    {
        if (! File::exists($caminho)) {
            return false;
        }

        /*
         * (DV-12) O valor depois do `=` não pode ser `.*$`: numa chave com valor entre
         * aspas ESPALHADO em várias linhas (`APP_NAME="linha1\nlinha2"`), o `.*` para na
         * primeira quebra e a substituição deixa a continuação órfã no arquivo — e o
         * `Dotenv::parse()` lança `InvalidFileException` na linha seguinte, derrubando o
         * boot INTEIRO do app. O alternado reconhece os três formatos do Dotenv —
         * aspas duplas, aspas simples e valor nu — e a classe negada dentro das aspas
         * cruza `\n` de propósito (classe negada ignora o `/s`; é o que `parse()` do
         * Dotenv aceita como valor multilinha). Um valor com aspa NÃO fechada cai no
         * terceiro ramo (linha única): a substituição troca só a primeira linha, e o
         * arquivo já era inválido antes mesmo de tocar nele.
         *
         * O terceiro ramo é `[^\n]*`, nunca `[^\r\n]*`: em arquivo CRLF o `.*` antigo
         * consumia o `\r` junto com o valor (`.` só exclui `\n`), e `[^\r\n]*` parava
         * ANTES dele — a linha deixava de casar inteira e a chave era anexada outra vez
         * no fim do arquivo (o duplicado que o CT-17 mede).
         */
        $valorDeEnv = "(?:\"(?:[^\"\\\\]|\\\\.)*\"|'(?:[^'\\\\]|\\\\.)*'|[^\\n]*)";

        $conteudo = File::get($caminho);
        $nome     = preg_quote($chave, '/');
        $ativa    = '/^[ \t]*(?:export[ \t]+)?'.$nome.'[ \t]*='.$valorDeEnv.'$/m';

        if (preg_match($ativa, $conteudo) === 1) {
            File::put($caminho, (string) preg_replace_callback($ativa, static fn (): string => $linha, $conteudo));

            return true;
        }

        $comentada = '/^[ \t]*#[ \t]*(?:export[ \t]+)?'.$nome.'[ \t]*='.$valorDeEnv.'$/m';

        if (preg_match($comentada, $conteudo) === 1) {
            File::put($caminho, (string) preg_replace_callback($comentada, static fn (): string => $linha, $conteudo, 1));

            return true;
        }

        File::append($caminho, PHP_EOL.$linha.PHP_EOL);

        return true;
    }

    /**
     * Neutraliza o que quebraria o parse do .env ou permitiria injetar uma linha.
     *
     * Quebra de linha vira espaço em vez de `\n` escapado de propósito: o dotenv
     * do PHP expande `\n` dentro de aspas duplas, e o valor voltaria a conter uma
     * quebra — só que agora dentro do próprio valor, o que quebra
     * `${APP_NAME}` em MAIL_FROM_NAME e VITE_APP_NAME. CRLF vira UM espaço (RQ-51):
     * a ordem do `str_replace` troca o par antes de `\n` e `\r` sozinhos.
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
