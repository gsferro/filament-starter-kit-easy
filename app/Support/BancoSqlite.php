<?php

declare(strict_types=1);

namespace App\Support;

use ErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Recria o arquivo SQLite do zero — e falha alto quando não consegue.
 *
 * ## O defeito que isto conserta (matriz de instalações da wiki `aderencia-ao-blueprint`, P1)
 *
 * `kit:install --force` fazia `File::delete($caminho)` e seguia sem olhar o retorno. No Windows,
 * arquivo aberto não se apaga — `unlink()` devolve `false` sem erro —, e o boot do kit JÁ abre o
 * SQLite: `ConfiguracoesDoKit::aplicarNaConfig()` lê a tabela de settings no `KitServiceProvider`.
 * Medido numa instalação real: o `--force` rodava inteiro, "Rodando migrations" passava no banco
 * VELHO, o admin antigo sobrevivia, e o resumo imprimia "Login inicial: novo@email" — uma
 * credencial que não existia. No Linux o unlink funciona com handle aberto, e por isso o CI nunca
 * viu.
 *
 * Duas coisas, na ordem: desconectar (`DB::disconnect()` + `purge()`, senão o handle do próprio
 * processo segura o arquivo) e **verificar** que o arquivo sumiu. Se outro processo o segura — um
 * `php artisan serve`, um tinker, o editor —, a única resposta honesta é parar com a causa, porque
 * seguir adiante produz exatamente a mentira acima.
 *
 * Classe própria, com o caminho por parâmetro, para ser testável em diretório temporário: o
 * comando inteiro não pode rodar em teste (o `key:generate --force` dele reescreveria o `.env`
 * do projeto).
 */
final class BancoSqlite
{
    /**
     * @throws RuntimeException quando o arquivo continua existindo depois do delete
     */
    public static function recriar(string $caminho, string $conexao = 'sqlite'): void
    {
        if (File::exists($caminho)) {
            DB::disconnect($conexao);
            DB::purge($conexao);

            $apagou = File::delete($caminho);
            clearstatcache(true, $caminho);

            if (! $apagou || is_file($caminho)) {
                throw new RuntimeException(self::mensagemDeArquivoPreso($caminho, 'apagar'));
            }
        }

        try {
            self::criarSeFaltar($caminho);
        } catch (ErrorException) {
            /*
             * Windows, segunda forma do mesmo problema: o `unlink` "passa" (o arquivo fica marcado
             * para apagar) e é o `file_put_contents` seguinte que falha com "Permission denied",
             * porque outro processo ainda o segura. Medido com um handle aberto de fora. A causa é a
             * mesma, a mensagem tem de ser a mesma.
             */
            throw new RuntimeException(self::mensagemDeArquivoPreso($caminho, 'recriar'));
        }
    }

    /** @return bool true quando o arquivo foi criado agora; false quando já existia */
    public static function criarSeFaltar(string $caminho): bool
    {
        if (File::exists($caminho)) {
            return false;
        }

        File::ensureDirectoryExists(dirname($caminho));
        File::put($caminho, '');

        return true;
    }

    private static function mensagemDeArquivoPreso(string $caminho, string $verbo): string
    {
        return "--force não conseguiu {$verbo} {$caminho}. Algum processo ainda o mantém aberto "
            .'(um `php artisan serve`, um tinker, o editor). Feche-o e rode de novo — '
            .'seguir adiante migraria o banco velho e o resumo mentiria sobre o login inicial.';
    }
}
