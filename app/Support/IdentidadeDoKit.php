<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
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

    /**
     * URL da variante escura da logo, ou `null` quando ela não vale.
     *
     * Dois `null` de propósito, com significados diferentes: marca unificada
     * (`unifica_logo_marca` ligado — a logo_dark gravada é inerte por desenho,
     * não por acidente) e marca separada sem arquivo utilizável. O consumidor
     * (a `<img>` dark nas telas, o `darkModeBrandLogo` nos painéis) recebe o
     * mesmo `null` nos dois casos e renderiza a clara sozinha.
     */
    public static function logoEscura(): ?string
    {
        if (self::unificaLogo()) {
            return null;
        }

        return self::doDisco('kit.identidade.logo_dark');
    }

    /**
     * A instalação usa uma logo só nos dois temas?
     *
     * Lê a config, e não o `.env`: o valor gravado nas settings vence o
     * `KIT_UNIFICA_LOGO_MARCA` — como toda chave do `mapaDeConfiguracao()`.
     */
    public static function unificaLogo(): bool
    {
        return (bool) config('kit.identidade.unifica_logo_marca', true);
    }

    /**
     * O par de logos (clara e escura) de uma organização, com queda para a instalação.
     *
     * Regra única para duas superfícies — a tela de bloqueio e o topo do `/app` —, para que
     * não divirjam. Por variante e independente: a escura nunca cai para a clara da
     * organização, e é `null` com a marca unificada — da instalação
     * (`unifica_logo_marca`) OU da organização (`unifica_logo`, o mesmo toggle um nível
     * abaixo: a clara dela serve os dois temas). O flag da organização decide sobre as
     * logos DELA: sem clara própria resolvível, não há o que unificar — o par segue a
     * instalação inteiro, escura inclusa.
     *
     * @return array{clara: ?string, escura: ?string}
     */
    public static function logosPara(?Tenant $organizacao): array
    {
        $logoDaOrganizacao = $organizacao?->urlDaLogo();

        if ($organizacao?->unifica_logo && $logoDaOrganizacao === null) {
            return ['clara' => self::logo(), 'escura' => self::logoEscura()];
        }

        return [
            'clara'  => $logoDaOrganizacao ?? self::logo(),
            'escura' => self::unificaLogo()
                || ($organizacao !== null && $organizacao->unifica_logo)
                ? null
                : ($organizacao?->urlDaLogoEscura() ?? self::logoEscura()),
        ];
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

        /*
         * `asset()` e não `$disco->url()`: a URL do disk é a string congelada
         * `APP_URL . '/storage'` (config/filesystems.php), e `asset()` segue o host
         * do request corrente. Os dois divergem sempre que o host efetivo não é o
         * APP_URL — proxy, staging, o servidor de teste do navegador — e aí a logo
         * quebra enquanto o resto da página carrega. Mesma correção e mesma razão
         * do comentário em `Tenant::urlDaLogo()`.
         */
        return asset('storage/'.$caminho);
    }
}
