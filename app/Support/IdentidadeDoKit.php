<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Logo, favicon e arte de login da instalação, resolvidos para URL.
 *
 * Uma classe, e não a resolução repetida nos providers, pelo mesmo motivo de
 * `App\Support\CorPrimaria`: a **guarda**. São doze pontos de consumo — três
 * `brandLogo`, três `favicon` e nove `media()` do Auth Designer (login,
 * password-reset e email-verification em cada painel) —, e um caminho declarado
 * cujo arquivo não está no disco produz um `<link rel="icon">` apontando para
 * 404 no `<head>` de TODA página. Repetida em doze lugares, a guarda deixa de
 * existir num deles, e o modo de falhar é silencioso.
 *
 * O caso acontece de verdade: alguém apaga `storage/app/public/kit/`, ou clona o
 * repositório sem o `storage/` de quem enviou o arquivo.
 *
 * ## Duas origens, e é isso que a classe reconcilia
 *
 * - o que a tela envia vive no **disco** `public` (`storage/app/public/kit/...`),
 *   servido pelo link simbólico que o `kit:install` cria
 *   (`app/Console/Commands/KitInstall.php:353`);
 * - o padrão da arte é um arquivo do **repositório**, em `public/images/auth/`.
 *
 * `Storage::url()` para a primeira, `asset()` para a segunda. Uma chave de config
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
    /**
     * A arte que veste as telas de autenticação quando nenhuma foi enviada.
     *
     * Caminho relativo a `public/`, servido por `asset()`. É o mesmo arquivo que
     * os três painéis usavam literalmente antes desta feature existir, o que faz
     * uma instalação que nunca abriu a tela de configurações se comportar
     * exatamente como antes.
     */
    public const ARTE_PADRAO = 'images/auth/login.svg';

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
     */
    public static function arteDoLogin(): string
    {
        return self::doDisco('kit.identidade.arte_do_login') ?? asset(self::ARTE_PADRAO);
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
