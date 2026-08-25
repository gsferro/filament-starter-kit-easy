<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * O que liga este repositório ao Snyk — e que não deve viajar para quem instala o kit.
 *
 * ## Por que remover
 *
 * O `.snyk` não é configuração: é o registro de uma decisão tomada NESTE repositório. Ele
 * carrega a URL da organização, o ID do projeto no painel, IDs de PR check e a apuração das
 * dependências do socialite. Nada disso descreve o projeto de quem instalou o kit. E o bloco
 * `ignore` que um dia entre ali silencia achado por ID: herdá-lo seria herdar uma exceção de
 * segurança que ninguém do outro lado concedeu — o pior tipo de default.
 *
 * O `seguranca.yml` vai junto por um motivo prático. Sem o secret `SNYK_TOKEN` ele avisaria
 * "Varredura nao executada" em todo push, para sempre, num projeto que nunca pediu Snyk. O job
 * é genérico e o README diz como recolocá-lo para quem quiser.
 *
 * ## Por que uma classe, e com o base explícito
 *
 * `base_path()` aqui dentro tornaria isto intestável: o caso teria de escrever um `.snyk` de
 * mentira na raiz do projeto e apagá-lo depois, e uma falha no meio deixaria o repositório sem
 * o arquivo. O base entra por parâmetro, os casos usam diretório temporário, e quem chama
 * resolve `base_path()` — a mesma disciplina que o cabeçalho de
 * `tests/Kit/CustomizadorDaInstalacaoTest.php` já impõe ao customizador.
 */
final class VinculoDoSnyk
{
    /**
     * Caminhos relativos que só servem ao repositório do kit.
     *
     * @var list<string>
     */
    public const ARQUIVOS = [
        '.snyk',
        '.github/workflows/seguranca.yml',
    ];

    /**
     * Apaga o que existir e devolve o que foi apagado.
     *
     * Devolver a lista, e não um `bool`, é o que deixa quem chama contar no resumo da
     * instalação sem consultar o disco outra vez. Lista vazia é caso normal: reexecutar o
     * instalador não é erro, e o comando fica calado quando não havia nada.
     *
     * @return list<string> caminhos ABSOLUTOS apagados
     */
    public static function remover(string $base): array
    {
        $existentes = [];

        foreach (self::ARQUIVOS as $relativo) {
            $caminho = rtrim($base, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativo);

            if (File::isFile($caminho)) {
                $existentes[] = $caminho;
            }
        }

        if ($existentes !== []) {
            File::delete($existentes);
        }

        return $existentes;
    }
}
