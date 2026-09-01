<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Logo, favicon e arte de login da instalação, resolvidos para URL.
 *
 * Uma classe, e não a resolução repetida nos providers, pelo mesmo motivo de
 * `App\Support\CorPrimaria`: a **guarda**. São dezesseis pontos de consumo — três
 * `brandLogo`, três `favicon` e dez `media()` do Auth Designer (`/admin` 3,
 * `/app` 4, `/infra` 3) —, e um caminho declarado cujo arquivo não está no disco
 * produz um `<link rel="icon">` apontando para 404 no `<head>` de TODA página.
 * Repetida em dezesseis lugares, a guarda deixa de existir num deles, e o modo de
 * falhar é silencioso.
 *
 * Duas telas a mais herdam a arte sem `media()` próprio — o bloqueio de sessão
 * (herda a chave `login`) e o desafio de 2FA (herda `password-reset`) —, o que faz
 * doze superfícies vestidas por dez chamadas.
 *
 * O caso acontece de verdade: alguém apaga `storage/app/public/kit/`, ou clona o
 * repositório sem o `storage/` de quem enviou o arquivo.
 *
 * ## Duas origens, e é isso que a classe reconcilia
 *
 * - o que a tela envia vive no **disco** `public` (`storage/app/public/kit/...`),
 *   servido pelo link simbólico que o `kit:install` cria
 *   (`app/Console/Commands/KitInstall.php:353`);
 * - o padrão da arte **não é arquivo**: é a view `svg.arte-do-login`, renderizada
 *   a cada chamada porque precisa carregar o nome da aplicação.
 *
 * `Storage::url()` para a primeira, data URI para a segunda. Uma chave de config
 * que às vezes fosse uma e às vezes outra seria a fonte de um bug por ano.
 *
 * ## Sem cache de propósito
 *
 * `Storage::disk('public')->exists()` é um `stat` de arquivo local, chamado no
 * render do cabeçalho.
 *
 * ponytail: em disco remoto (S3) isto vira uma chamada de rede por render — se o
 * kit passar a nascer com disco remoto, a guarda precisa de cache por request.
 */
final class IdentidadeDoKit
{
    /** URL da logo da marca, ou `null` para o Filament usar o brand em texto. */
    public static function logo(): ?string
    {
        return self::doDisco('kit.identidade.logo');
    }

    /** URL do favicon, ou `null` para o Filament usar o ícone dele. */
    public static function favicon(): ?string
    {
        return self::doDisco('kit.identidade.favicon');
    }

    /**
     * URL da arte das telas de autenticação. Nunca `null`.
     *
     * O Auth Designer recebe este valor em `->media()`, e `null` ali deixaria a
     * tela sem imagem — que é uma regressão visível, não um default.
     *
     * Sem arte enviada, o padrão é gerado: a view `svg.arte-do-login` carrega o
     * nome da aplicação e volta como data URI, para que toda instalação nasça com
     * a tela de login mostrando o **seu** nome, e não o do kit.
     *
     * O data URI cai no ramo `<img>` do Auth Designer porque ele escolhe entre
     * imagem e vídeo por extensão (`MediaDetector::isVideo()`), e base64 não tem
     * ponto no alfabeto — logo, nunca produz extensão.
     */
    public static function arteDoLogin(): string
    {
        return self::doDisco('kit.identidade.arte_do_login')
            ?? 'data:image/svg+xml;base64,'.base64_encode(self::artePadrao());
    }

    /** O SVG da arte padrão, com o nome da aplicação dentro. */
    private static function artePadrao(): string
    {
        return view('svg.arte-do-login', ['nome' => config('app.name')])->render();
    }

    /**
     * O caminho gravado, resolvido para URL — ou `null` quando não há arquivo
     * utilizável.
     *
     * Duas condições devolvem `null`, e as duas importam: chave vazia (ninguém
     * enviou nada) e arquivo declarado que **não existe** no disco. A segunda é a
     * que justifica a classe.
     */
    private static function doDisco(string $chave): ?string
    {
        $caminho = config($chave);

        if (! is_string($caminho) || $caminho === '') {
            return null;
        }

        $disco = Storage::disk('public');

        if (! $disco->exists($caminho)) {
            Log::channel('configuracoes')->warning(
                '[IdentidadeDoKit@doDisco] Arquivo declarado e ausente no disco, usando o padrão | caminho: '.$caminho,
                ['chave' => $chave, 'caminho' => $caminho, 'disco' => 'public'],
            );

            return null;
        }

        return $disco->url($caminho);
    }
}
