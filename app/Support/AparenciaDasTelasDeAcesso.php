<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * A aparência das telas de autenticação: o que a instalação (e, com tenancy, a organização)
 * escolheu, lido por request e aplicado sobre a configuração que os providers dão ao
 * `caresome/filament-auth-designer`.
 *
 * ## Dois layouts, e o primeiro é "nada muda"
 *
 * - `classico` — a aparência de sempre. É o que vale com o valor gravado `null`, que é o default
 *   de toda instalação: `aplicar()` devolve o MESMO objeto que recebeu, então a tela sai com o
 *   HTML de antes (P-01);
 * - `painel` — formulário numa coluna e, na outra metade, um painel de marca. É o modo lateral do
 *   próprio pacote (`media-left`/`media-right`, `mediaSize 50%`) com o conteúdo da mídia trocado
 *   pelo painel no partial `media` que o kit já sobrescreve. O layout do pacote não é publicado
 *   (ADR-03 da wiki `feat/aparencia-das-telas-de-acesso`).
 *
 * ## Vocabulário fechado, lido de novo a cada request
 *
 * O valor vem de duas origens que o kit não controla por inteiro — a linha de settings e a coluna
 * JSON da organização —, e a tela que o consome é a de LOGIN. Então `normalizar()` nunca lança:
 * campo fora do vocabulário volta ao padrão do campo e layout desconhecido volta ao clássico, com
 * `warning` (P-04). Nenhum campo aceita HTML ou CSS: cor só `#RRGGBB`, textos com teto e escapados
 * na view (P-05).
 *
 * ## Organização: inteira ou nada
 *
 * Coluna nula = usa a da instalação. Não há herança por campo (ADR-02): a organização usa a padrão
 * inteira ou a sua inteira.
 */
final class AparenciaDasTelasDeAcesso
{
    public const LAYOUT_CLASSICO = 'classico';

    public const LAYOUT_PAINEL = 'painel';

    public const LAYOUTS = [self::LAYOUT_CLASSICO, self::LAYOUT_PAINEL];

    public const LADOS = ['esquerda', 'direita'];

    public const FUNDOS = ['cor', 'gradiente', 'arte'];

    /**
     * As famílias de tela com aparência própria (P-25, P-26). O login é a raiz do objeto; as outras
     * moram em `telas` e, vazias, seguem o login.
     */
    public const FAMILIA_LOGIN = 'login';

    public const FAMILIAS_COM_APARENCIA_PROPRIA = ['cadastro', 'recuperacao'];

    /** Direções do gradiente: um vocabulário fechado, nunca CSS livre (P-29). */
    public const DIRECOES_DO_GRADIENTE = ['vertical' => '180deg', 'horizontal' => '90deg', 'diagonal' => '135deg'];

    /** Formato da frase e do apoio: `texto` (gravado antes do Adendo 2) ou `markdown` (P-27). */
    public const FORMATOS_DOS_TEXTOS = ['texto', 'markdown'];

    /** Teto do texto BRUTO de cada campo formatado, antes da conversão (P-27). */
    public const LIMITE_BRUTO_DO_TEXTO = 4096;

    public const LIMITE_DA_FRASE = 80;

    public const LIMITE_DO_APOIO = 160;

    /**
     * Largura do painel de marca, em %, no modo lateral do auth designer (`mediaSize`): o
     * formulário fica com o resto. Passos de 5, de 30 a 70 — fora disso o formulário ou o painel
     * ficam estreitos demais para o conteúdo (P-19).
     */
    public const TAMANHOS_DO_PAINEL = [30, 35, 40, 45, 50, 55, 60, 65, 70];

    /** O que aparece no meio do painel (P-20). */
    public const DESTAQUES = ['nenhum', 'logo', 'imagem'];

    public const TAMANHOS_DO_DESTAQUE = ['pequeno', 'medio', 'grande'];

    /** A ilustração decorativa do painel (P-21). */
    public const ILUSTRACOES = ['nenhuma', 'rede'];

    /**
     * Onde a imagem do destaque mora no disco `public`. O caminho gravado só vale aqui dentro
     * (P-24): a aparência é JSON que o kit lê numa tela pública, e um caminho livre faria a tela
     * de login apontar para qualquer arquivo do disco.
     */
    public const DIRETORIO_DAS_IMAGENS = 'telas-de-acesso';

    private const FORMATO_DO_CAMINHO = '#^telas-de-acesso/[A-Za-z0-9][A-Za-z0-9._-]*\.(png|jpe?g|webp)\z#i';

    /**
     * Só `#RRGGBB`: o valor vai para um atributo `style`, e nada além de hexadecimal entra lá.
     *
     * `\z`, e não `$`: o `$` do PCRE aceita uma quebra de linha no fim, e `#123456\n` passaria.
     */
    public const FORMATO_DA_COR = '/^#[0-9a-fA-F]{6}\z/';

    /**
     * O objeto com que o layout `painel` nasce — no editor da instalação e na organização que
     * deixa de usar a padrão quando a padrão ainda é o clássico.
     *
     * Os campos do Adendo 1 (tamanho, destaque, ilustração) nascem nos valores que reproduzem o
     * painel de antes: uma aparência gravada sem eles continua igual (P-23).
     *
     * @return array<string, mixed>
     */
    public static function padraoDoPainel(): array
    {
        return [
            'layout'                => self::LAYOUT_PAINEL,
            'lado_do_formulario'    => 'direita',
            'tamanho_do_painel'     => 50,
            'fundo'                 => 'cor',
            'cor'                   => null,
            'gradiente_de'          => null,
            'gradiente_ate'         => null,
            'gradiente_direcao'     => 'diagonal',
            'formato_dos_textos'    => 'markdown',
            'frase'                 => null,
            'apoio'                 => null,
            'logo_no_painel'        => true,
            'destaque'              => 'nenhum',
            'imagem_do_destaque'    => null,
            'tamanho_do_destaque'   => 'medio',
            'ilustracao'            => 'nenhuma',
            'movimento'             => true,
            'cor_da_ilustracao'     => null,
            'alternador_de_tema'    => true,
            'transicao_entre_telas' => true,
        ];
    }

    /**
     * O objeto inteiro gravado, reduzido ao vocabulário: a aparência do login na raiz e, em `telas`,
     * a do cadastro e a do "esqueci a senha" (ADR-05). `null` = clássico em todas as telas.
     *
     * Sem `telas`, a saída é a mesma de antes do Adendo 2 — o login, e as outras telas seguindo-o.
     * Com o login no clássico e outra tela própria, a raiz vira `['layout' => 'classico']`, e as
     * telas próprias continuam (o juiz cego apontou que reduzir a raiz a `null` as apagaria).
     *
     * @param  string  $origem  `global` ou `tenant:{id}` — só para o log
     * @return array<string, mixed>|null
     */
    public static function normalizar(mixed $bruto, string $origem): ?array
    {
        $login = self::normalizarTela($bruto, $origem);
        $telas = [];

        foreach (self::FAMILIAS_COM_APARENCIA_PROPRIA as $familia) {
            $propria = is_array($bruto) && is_array($bruto['telas'] ?? null) ? ($bruto['telas'][$familia] ?? null) : null;

            // Ausente, vazia ou "igual ao login": segue o login (P-25; ADV-C8 da revisão adversarial).
            if (! is_array($propria) || $propria === [] || ($propria['igual_ao_login'] ?? false) === true) {
                continue;
            }

            // Tela própria com layout ausente ou desconhecido cai no clássico, como qualquer layout
            // inválido (P-04) — e não no login (RD-B7 da revisão dos Adendos 2 e 3).
            $telas[$familia] = self::normalizarTela($propria, $origem.':'.$familia) ?? ['layout' => self::LAYOUT_CLASSICO];
        }

        if ($telas === []) {
            return $login;
        }

        /*
         * A transição entre as telas é UMA opção, a do login (P-32): o efeito pertence ao par de
         * páginas, então uma tela própria nunca a liga ou desliga sozinha. Com o login no clássico,
         * vale o que estiver gravado na raiz, e o padrão (ligada) sem nada gravado.
         */
        $transicao = $login['transicao_entre_telas']
            ?? self::booleano(is_array($bruto) ? ($bruto['transicao_entre_telas'] ?? null) : null, true);

        foreach ($telas as $familia => $tela) {
            if (($tela['layout'] ?? null) === self::LAYOUT_PAINEL) {
                $telas[$familia]['transicao_entre_telas'] = $transicao;
            }
        }

        // Com o login no clássico, a raiz guarda a opção: sem ela, a escolha se perderia na próxima
        // leitura (RD-D1 da revisão do Adendo 4).
        return [...($login ?? ['layout' => self::LAYOUT_CLASSICO, 'transicao_entre_telas' => $transicao]), 'telas' => $telas];
    }

    /**
     * A aparência de UMA família de tela, tirada do objeto inteiro: a própria, se a tela tem uma;
     * senão a do login. `null` = clássico nessa tela (P-25).
     *
     * @param  array<string, mixed>|null  $aparencia  saída de `normalizar()`
     * @return array<string, mixed>|null
     */
    public static function daFamilia(?array $aparencia, string $familia): ?array
    {
        if ($aparencia === null) {
            return null;
        }

        $tela = $familia !== self::FAMILIA_LOGIN ? ($aparencia['telas'][$familia] ?? null) : null;
        $tela ??= $aparencia;

        unset($tela['telas']);

        return ($tela['layout'] ?? null) === self::LAYOUT_PAINEL ? $tela : null;
    }

    /**
     * Uma tela: o valor gravado reduzido ao vocabulário. `null` = clássico.
     *
     * @return array<string, mixed>|null
     */
    private static function normalizarTela(mixed $bruto, string $origem): ?array
    {
        if ($bruto === null || $bruto === []) {
            return null;
        }

        if (! is_array($bruto)) {
            self::avisarDescarte($origem, 'formato');

            return null;
        }

        $layout = $bruto['layout'] ?? null;

        if ($layout === self::LAYOUT_CLASSICO) {
            return null;
        }

        if ($layout !== self::LAYOUT_PAINEL) {
            self::avisarDescarte($origem, 'layout');

            return null;
        }

        $padrao = self::padraoDoPainel();
        // Sem a chave, é uma aparência gravada antes do Adendo 2: texto puro, exibido como antes (P-27).
        $formato = self::umDe($bruto['formato_dos_textos'] ?? 'texto', self::FORMATOS_DOS_TEXTOS, 'texto');

        return [
            'layout'                => self::LAYOUT_PAINEL,
            'lado_do_formulario'    => self::umDe($bruto['lado_do_formulario'] ?? null, self::LADOS, $padrao['lado_do_formulario']),
            'tamanho_do_painel'     => self::tamanho($bruto['tamanho_do_painel'] ?? null),
            'fundo'                 => self::umDe($bruto['fundo'] ?? null, self::FUNDOS, $padrao['fundo']),
            'cor'                   => self::cor($bruto['cor'] ?? null),
            'gradiente_de'          => self::cor($bruto['gradiente_de'] ?? null),
            'gradiente_ate'         => self::cor($bruto['gradiente_ate'] ?? null),
            'gradiente_direcao'     => self::umDe($bruto['gradiente_direcao'] ?? null, array_keys(self::DIRECOES_DO_GRADIENTE), $padrao['gradiente_direcao']),
            'formato_dos_textos'    => $formato,
            'frase'                 => self::textoNoFormato($bruto['frase'] ?? null, $formato, self::LIMITE_DA_FRASE),
            'apoio'                 => self::textoNoFormato($bruto['apoio'] ?? null, $formato, self::LIMITE_DO_APOIO),
            'logo_no_painel'        => self::booleano($bruto['logo_no_painel'] ?? null, $padrao['logo_no_painel']),
            'destaque'              => self::umDe($bruto['destaque'] ?? null, self::DESTAQUES, $padrao['destaque']),
            'imagem_do_destaque'    => self::caminhoDaImagem($bruto['imagem_do_destaque'] ?? null),
            'tamanho_do_destaque'   => self::umDe($bruto['tamanho_do_destaque'] ?? null, self::TAMANHOS_DO_DESTAQUE, $padrao['tamanho_do_destaque']),
            'ilustracao'            => self::umDe($bruto['ilustracao'] ?? null, self::ILUSTRACOES, $padrao['ilustracao']),
            'movimento'             => self::booleano($bruto['movimento'] ?? null, $padrao['movimento']),
            'cor_da_ilustracao'     => self::cor($bruto['cor_da_ilustracao'] ?? null),
            'alternador_de_tema'    => self::booleano($bruto['alternador_de_tema'] ?? null, $padrao['alternador_de_tema']),
            'transicao_entre_telas' => self::booleano($bruto['transicao_entre_telas'] ?? null, $padrao['transicao_entre_telas']),
        ];
    }

    /**
     * A opção "Transição entre as telas" da instalação (P-32, P-35): a do login da padrão, ligada
     * quando não há nada gravado.
     */
    public static function transicaoDaInstalacao(): bool
    {
        return (bool) (self::global()['transicao_entre_telas'] ?? true);
    }

    /**
     * Os caminhos das telas que podem participar da transição: as de autenticação de cada painel e
     * as páginas únicas do login unificado. A página que sai pula a transição para qualquer outro
     * destino — o painel depois de entrar, por exemplo —, que não a declara: sem isso o navegador
     * aborta a transição e registra um erro de JavaScript a cada login (teste manual da v0.53.1).
     *
     * @return list<string>
     */
    public static function caminhosDaTransicao(): array
    {
        $nomes = ['login', 'cadastro', 'esqueci-minha-senha'];

        foreach (Filament::getPanels() as $painel) {
            foreach (['login', 'register', 'password-reset.request', 'password-reset.reset', 'two-factor', 'email-verification.prompt'] as $tela) {
                $nomes[] = "filament.{$painel->getId()}.auth.{$tela}";
            }
        }

        $caminhos = [];

        foreach ($nomes as $nome) {
            $rota = Route::getRoutes()->getByName($nome);

            // Rota com parâmetro (o dois fatores do `/app` com organizações: `app/{tenant}/...`)
            // fica de fora: sem o valor não há caminho, e ir para ela só perde a animação.
            if ($rota !== null && $rota->parameterNames() === []) {
                $caminhos[] = '/'.ltrim((string) parse_url(url($rota->uri()), PHP_URL_PATH), '/');
            }
        }

        return array_values(array_unique($caminhos));
    }

    /**
     * A da instalação — `config('kit.identidade.aparencia_das_telas')`, já sobreposta pelo banco
     * no boot (`ConfiguracoesDoKit::aplicarNaConfig()`).
     *
     * @return array<string, mixed>|null
     */
    public static function global(): ?array
    {
        return self::normalizar(config('kit.identidade.aparencia_das_telas'), 'global');
    }

    /**
     * A que vale para uma organização: a dela, se ela tem uma; senão a da instalação.
     *
     * Quem decide SE há organização (tenancy, painel, organização ativa) é quem chama — esta
     * função só escolhe entre as duas aparências.
     *
     * @return array<string, mixed>|null
     */
    public static function daOrganizacao(?Tenant $organizacao): ?array
    {
        $propria = $organizacao?->aparencia_das_telas;

        if ($organizacao === null || $propria === null) {
            return self::global();
        }

        return self::normalizar($propria, 'tenant:'.$organizacao->getKey());
    }

    /**
     * A configuração do pacote com a aparência aplicada.
     *
     * Clássico (`null`) devolve `$base` — o mesmo objeto, então nada do que os providers ou a
     * página montaram muda. O `painel` reconstrói o objeto (ele é `final readonly`, sem `with()`):
     * lado oposto ao do formulário, metade da tela, sem blur, e SEMPRE com mídia (a arte), porque o pacote
     * só inclui o partial `media` — onde o painel nasce — quando há mídia
     * (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:30`).
     *
     * @param  array<string, mixed>|null  $aparencia
     */
    public static function aplicar(AuthDesignerConfig $base, ?array $aparencia): AuthDesignerConfig
    {
        if ($aparencia === null || ($aparencia['layout'] ?? null) !== self::LAYOUT_PAINEL) {
            return $base;
        }

        // A mídia do painel é SEMPRE a arte da identidade, lida agora: é o fundo "arte" que a tela
        // promete (P-15), em toda tela — inclusive no bloqueio, cuja mídia base pode ser a logo.
        $media = IdentidadeDoKit::arteDoLogin();

        return new AuthDesignerConfig(
            position: ($aparencia['lado_do_formulario'] ?? 'direita') === 'esquerda' ? MediaPosition::Right : MediaPosition::Left,
            media: $media,
            mediaSize: self::tamanho($aparencia['tamanho_do_painel'] ?? null).'%',
            blur: 0,
            mediaAlt: $base->mediaAlt ?? config('app.name'),
            showThemeSwitcher: $base->showThemeSwitcher && (bool) ($aparencia['alternador_de_tema'] ?? true),
            themePosition: $base->themePosition,
            isVideo: false,
            mediaMimeType: null,
            renderHooks: $base->renderHooks,
        );
    }

    /**
     * A mesma configuração sem o alternador de tema — para o preview: o alternador grava a
     * preferência no `localStorage` do navegador, o mesmo do admin que está editando, e clicar nele
     * dentro do iframe trocaria o tema da própria tela de configurações.
     */
    public static function semAlternadorDeTema(AuthDesignerConfig $config): AuthDesignerConfig
    {
        return new AuthDesignerConfig(
            position: $config->position,
            media: $config->media,
            mediaSize: $config->mediaSize,
            blur: $config->blur,
            mediaAlt: $config->mediaAlt,
            showThemeSwitcher: false,
            themePosition: $config->themePosition,
            isVideo: $config->isVideo,
            mediaMimeType: $config->mediaMimeType,
            renderHooks: $config->renderHooks,
        );
    }

    /**
     * A cor de fundo do painel, como valor CSS seguro: o hexadecimal escolhido na aparência, senão
     * a primária da organização, senão a da instalação, senão o âmbar 600 que o Filament usa sem
     * primária configurada — sempre uma cor concreta, para o contraste do texto ser decidido (QA-04).
     *
     * O valor só pode vir de duas fontes, e as duas são fechadas: `#RRGGBB` validado, ou uma
     * constante de `Filament\Support\Colors\Color` (resolvida por `CorPrimaria::resolver()`).
     *
     * @param  array<string, mixed>|null  $aparencia
     */
    public static function corDoPainel(?array $aparencia, ?Tenant $organizacao): string
    {
        $propria = self::cor($aparencia['cor'] ?? null);

        if ($propria !== null) {
            return $propria;
        }

        return self::primariaEm(600, $organizacao);
    }

    /**
     * O gradiente do painel, como valor CSS montado só de peças fechadas: duas cores (`#RRGGBB`
     * validado ou um tom da primária) e um ângulo do vocabulário (P-29). `null` fora do gradiente.
     *
     * @param  array<string, mixed>|null  $aparencia
     */
    public static function gradienteDoPainel(?array $aparencia, ?Tenant $organizacao): ?string
    {
        if (($aparencia['fundo'] ?? null) !== 'gradiente') {
            return null;
        }

        // Tom 600 DA PALETA também com primária hex (RD-B6): o fundo sólido usa o hex cru (QA-10),
        // mas o gradiente anuncia "tom 600 e tom 900" e os dois saem da mesma paleta.
        $de     = self::cor($aparencia['gradiente_de'] ?? null) ?? self::primariaEm(600, $organizacao, daPaleta: true);
        $ate    = self::cor($aparencia['gradiente_ate'] ?? null) ?? self::primariaEm(900, $organizacao);
        $angulo = self::DIRECOES_DO_GRADIENTE[$aparencia['gradiente_direcao'] ?? 'diagonal'] ?? self::DIRECOES_DO_GRADIENTE['diagonal'];

        return "linear-gradient({$angulo}, {$de}, {$ate})";
    }

    /**
     * Um tom da cor primária — a da organização, senão a da instalação, senão o âmbar que o
     * Filament usa sem primária configurada (P-28: "a cor padrão do Filament").
     */
    public static function primariaEm(int $tom, ?Tenant $organizacao, bool $daPaleta = false): string
    {
        $resolvida = ($organizacao !== null ? CorPrimaria::resolver($organizacao->cor_primaria, $organizacao->cor_primaria_nome) : [])
            ?: CorPrimaria::paleta();

        $primaria = $resolvida['primary'] ?? null;

        if (is_string($primaria) && ($tom !== 600 || $daPaleta)) {
            $primaria = Color::hex(self::hexCompleto($primaria));
        }

        if (is_string($primaria)) {
            // A primária hexadecimal aceita `#rgb` (`CorPrimaria`): expandida, para o contraste do
            // texto ser decidido sobre ela (QA-10 do gate, ciclo 2).
            return self::hexCompleto($primaria);
        }

        if (is_array($primaria) && is_string($primaria[$tom] ?? null)) {
            return $primaria[$tom];
        }

        // Sem primária configurada, o Filament usa o âmbar: a cor é dita aqui, e não deixada à
        // variável `--primary-600`, para o contraste do texto poder ser decidido (QA-04).
        return Color::Amber[$tom] ?? Color::Amber[600];
    }

    private static function hexCompleto(string $hex): string
    {
        return preg_match('/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/', $hex, $p) === 1
            ? strtolower('#'.$p[1].$p[1].$p[2].$p[2].$p[3].$p[3])
            : $hex;
    }

    /**
     * A frase ou o apoio como HTML seguro para a tela pública (P-27): `markdown` passa pelo mesmo
     * `Str::markdown` do rodapé da tela de login (HTML cru descartado, link só com esquema seguro) e
     * depois só fica a formatação acordada — imagem, título e lista digitados ou colados viram texto
     * ou somem; `texto` (gravado antes do Adendo 2) sai escapado, como sempre saiu.
     */
    public static function textoComoHtml(?string $texto, string $formato): ?HtmlString
    {
        if ($texto === null || $texto === '') {
            return null;
        }

        if ($formato !== 'markdown') {
            return new HtmlString(e($texto));
        }

        $html = Str::markdown($texto, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return new HtmlString(self::soAFormatacaoAcordada($html));
    }

    /**
     * O HTML do Markdown reduzido, por um parser de verdade (e não regex — SEC-B1 da revisão dos
     * Adendos 2 e 3), à formatação acordada: `p`, `br`, `strong`, `em`, `del` e `a`. Todo atributo
     * sai; o `<a>` fica só com o `href` que o CommonMark já filtrou (`allow_unsafe_links`), e só com
     * esquema seguro, mais `rel`. Qualquer outro elemento vira o seu texto.
     */
    private static function soAFormatacaoAcordada(string $html): string
    {
        $documento = new \DOMDocument;
        $anterior  = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?><div id="raiz">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $raiz = $documento->getElementById('raiz');

        if ($raiz === null) {
            return '';
        }

        self::limparNo($raiz, $documento);

        $saida = '';

        foreach ($raiz->childNodes as $filho) {
            $saida .= $documento->saveHTML($filho);
        }

        return trim($saida);
    }

    private static function limparNo(\DOMNode $no, \DOMDocument $documento): void
    {
        $permitidos = ['p', 'br', 'strong', 'em', 'del', 'a'];

        foreach (iterator_to_array($no->childNodes) as $filho) {
            if (! $filho instanceof \DOMElement) {
                if (! $filho instanceof \DOMText) {
                    $no->removeChild($filho);
                }

                continue;
            }

            self::limparNo($filho, $documento);

            $tag = strtolower($filho->tagName);

            if (! in_array($tag, $permitidos, true)) {
                $no->replaceChild($documento->createTextNode($filho->textContent), $filho);

                continue;
            }

            $href = $tag === 'a' ? $filho->getAttribute('href') : '';

            foreach (iterator_to_array($filho->attributes) as $atributo) {
                $filho->removeAttribute($atributo->nodeName);
            }

            if ($tag === 'a' && preg_match('#^(https?://|mailto:|/(?!/))#i', $href) === 1) {
                $filho->setAttribute('href', $href);
                $filho->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }

    /** O texto que a tela mostra, para medir o teto (P-27): sem tags, entidades decodificadas, espaços normalizados. */
    public static function textoVisivel(?string $texto, string $formato): ?string
    {
        $html = self::textoComoHtml($texto, $formato);

        return $html === null ? null : self::textoLimpo(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * O texto do painel deve ser ESCURO sobre esta cor? Escolhe a cor de texto de maior contraste
     * (WCAG) entre o branco e o escuro do kit (`#111827`) — P-18, com o limiar do QA-04 do gate:
     * um corte fixo deixava branco em cores médias (`#00aaff`, o âmbar 600 da primária default) com
     * contraste abaixo de 3:1.
     *
     * Entende `#RRGGBB` e o `oklch(...)` das constantes de `Filament\Support\Colors\Color`
     * (convertido a sRGB linear de verdade). Valor que não entende: `false` (texto branco).
     */
    public static function fundoClaro(?string $cor): bool
    {
        $luminancia = self::luminancia($cor);

        if ($luminancia === null) {
            return false;
        }

        $contrasteBranco = 1.05 / ($luminancia + 0.05);
        $contrasteEscuro = ($luminancia + 0.05) / (self::LUMINANCIA_DO_TEXTO_ESCURO + 0.05);

        return $contrasteEscuro > $contrasteBranco;
    }

    /** Luminância relativa de `#111827`, o texto escuro do painel (`kit.css`). */
    private const LUMINANCIA_DO_TEXTO_ESCURO = 0.0118;

    private static function luminancia(?string $cor): ?float
    {
        if ($cor === null) {
            return null;
        }

        if (preg_match(self::FORMATO_DA_COR, $cor) === 1) {
            $canal = static function (string $hex): float {
                $c = hexdec($hex) / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            };

            return 0.2126 * $canal(substr($cor, 1, 2)) + 0.7152 * $canal(substr($cor, 3, 2)) + 0.0722 * $canal(substr($cor, 5, 2));
        }

        if (preg_match('/^oklch\(\s*([0-9.]+)(%?)\s+([0-9.]+)\s+([0-9.]+)/i', $cor, $partes) === 1) {
            return self::luminanciaDoOklch(
                (float) $partes[1] / ($partes[2] === '%' ? 100 : 1),
                (float) $partes[3],
                (float) $partes[4],
            );
        }

        return null;
    }

    /**
     * Luminância relativa de uma cor OKLCH, pela conversão de verdade OKLab → sRGB linear (Björn
     * Ottosson). A aproximação `L³` errava a escolha em Fuchsia e Pink 600 (QA-11 do gate, ciclo 2).
     */
    private static function luminanciaDoOklch(float $l, float $c, float $h): float
    {
        $rad = deg2rad($h);
        $a   = $c * cos($rad);
        $b   = $c * sin($rad);

        $l_ = ($l + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m_ = ($l - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s_ = ($l - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        $r = max(0.0, min(1.0, 4.0767416621 * $l_ - 3.3077115913 * $m_ + 0.2309699292 * $s_));
        $g = max(0.0, min(1.0, -1.2684380046 * $l_ + 2.6097574011 * $m_ - 0.3413193965 * $s_));
        $z = max(0.0, min(1.0, -0.0041960863 * $l_ - 0.7034186147 * $m_ + 1.7076147010 * $s_));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $z;
    }

    /**
     * O desenho da ilustração "rede" (P-21): pontos num quadro de 600×400 e as ligações entre eles.
     *
     * Fixo, e não sorteado por request: a tela de login é a mesma a cada visita, e o teste pode
     * contar pontos e ligações. Cada ponto liga aos dois vizinhos mais próximos ainda não ligados a
     * ele — o resultado é uma malha sem cruzamentos longos. O movimento (quando ligado) é só um
     * deslocamento pequeno de cada ponto em torno desta posição, feito no navegador.
     *
     * @return array{pontos: list<array{x: int, y: int, r: int}>, ligacoes: list<array{0: int, 1: int}>}
     */
    public static function rede(): array
    {
        $pontos = [
            ['x' => 70, 'y' => 60, 'r' => 5], ['x'    => 190, 'y' => 30, 'r' => 7], ['x' => 320, 'y' => 85, 'r' => 9],
            ['x' => 455, 'y' => 40, 'r' => 6], ['x'   => 560, 'y' => 120, 'r' => 8], ['x' => 120, 'y' => 170, 'r' => 8],
            ['x' => 250, 'y' => 200, 'r' => 11], ['x' => 395, 'y' => 180, 'r' => 7], ['x' => 520, 'y' => 245, 'r' => 10],
            ['x' => 60, 'y' => 290, 'r' => 6], ['x'   => 185, 'y' => 320, 'r' => 7], ['x' => 330, 'y' => 300, 'r' => 9],
            ['x' => 450, 'y' => 345, 'r' => 6], ['x'  => 575, 'y' => 370, 'r' => 5],
        ];

        $ligacoes = [];

        foreach ($pontos as $i => $ponto) {
            $distancias = [];

            foreach ($pontos as $j => $outro) {
                if ($i !== $j) {
                    $distancias[$j] = ($ponto['x'] - $outro['x']) ** 2 + ($ponto['y'] - $outro['y']) ** 2;
                }
            }

            asort($distancias);

            foreach (array_slice(array_keys($distancias), 0, 2) as $j) {
                $par                          = [min($i, $j), max($i, $j)];
                $ligacoes[implode('-', $par)] = $par;
            }
        }

        return ['pontos' => $pontos, 'ligacoes' => array_values($ligacoes)];
    }

    /**
     * A URL da imagem do destaque, ou `null` quando não há arquivo utilizável: caminho fora do
     * diretório das telas de acesso (P-24) ou arquivo que sumiu do disco — a tela segue sem o
     * destaque, nunca quebra (P-20).
     */
    public static function urlDaImagem(mixed $caminho): ?string
    {
        $caminho = self::caminhoDaImagem($caminho);

        if ($caminho === null) {
            return null;
        }

        if (! Storage::disk('public')->exists($caminho)) {
            // Uma vez por hora por caminho: a tela é pública, e um aviso por request seria volume
            // que o visitante controla (SEC-A1 da revisão do adendo).
            if (! Cache::add('aparencia-imagem-ausente:'.hash('xxh128', $caminho), true, now()->addHour())) {
                return null;
            }

            Log::channel('autenticacao')->warning(
                '[AparenciaDasTelasDeAcesso@urlDaImagem] Imagem do destaque ausente no disco | caminho: '.$caminho,
                ['caminho' => $caminho, 'disco' => 'public'],
            );

            return null;
        }

        // `asset()`, e não `url()` do disco: segue o host do request (mesma razão de `IdentidadeDoKit::doDisco()`).
        return asset('storage/'.$caminho);
    }

    /**
     * O caminho gravado, se for um caminho do diretório das telas de acesso (P-24). Aceita o
     * estado do `FileUpload` do Filament — `['uuid' => 'caminho']` — além da string.
     */
    public static function caminhoDaImagem(mixed $valor): ?string
    {
        if (is_array($valor)) {
            $valor = count($valor) === 1 ? reset($valor) : null;
        }

        return is_string($valor) && preg_match(self::FORMATO_DO_CAMINHO, $valor) === 1 ? $valor : null;
    }

    private static function tamanho(mixed $valor): int
    {
        $numero = is_int($valor) || (is_string($valor) && ctype_digit($valor)) ? (int) $valor : null;

        return in_array($numero, self::TAMANHOS_DO_PAINEL, true) ? $numero : 50;
    }

    /**
     * @param  list<string>  $permitidos
     */
    private static function umDe(mixed $valor, array $permitidos, string $padrao): string
    {
        return is_string($valor) && in_array($valor, $permitidos, true) ? $valor : $padrao;
    }

    private static function cor(mixed $valor): ?string
    {
        return is_string($valor) && preg_match(self::FORMATO_DA_COR, $valor) === 1 ? strtolower($valor) : null;
    }

    /**
     * O texto como ele é gravado e medido (P-11): espaços das bordas e repetidos removidos; vazio
     * ou só espaços é ausente. Público porque a validação da tela mede o teto sobre ESTE valor.
     */
    public static function textoLimpo(mixed $valor): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $limpo = trim(preg_replace('/\s+/u', ' ', $valor) ?? '');

        return $limpo === '' ? null : $limpo;
    }

    private static function texto(mixed $valor, int $limite): ?string
    {
        $limpo = self::textoLimpo($valor);

        // Acima do teto é inválido, e campo inválido volta ao padrão dele — ausente (P-04). Cortar
        // mostraria na tela de login um texto que ninguém escreveu daquele jeito.
        return $limpo === null || mb_strlen($limpo) > $limite ? null : $limpo;
    }

    /**
     * O texto gravado no seu formato. `markdown`: mantido como foi escrito (as quebras importam),
     * aparado nas bordas, com o teto bruto de 4 KiB e o teto do texto VISÍVEL (P-27); acima de
     * qualquer um, volta ao padrão do campo — ausente. `texto`: o tratamento de antes (P-11).
     */
    private static function textoNoFormato(mixed $valor, string $formato, int $limite): ?string
    {
        if ($formato !== 'markdown') {
            return self::texto($valor, $limite);
        }

        if (! is_string($valor)) {
            return null;
        }

        $bruto = trim($valor);

        if ($bruto === '' || strlen($bruto) > self::LIMITE_BRUTO_DO_TEXTO) {
            return null;
        }

        $visivel = self::textoVisivel($bruto, 'markdown');

        return $visivel === null || mb_strlen($visivel) > $limite ? null : $bruto;
    }

    private static function booleano(mixed $valor, bool $padrao): bool
    {
        return is_bool($valor) ? $valor : (in_array($valor, [0, 1, '0', '1'], true) ? (bool) $valor : $padrao);
    }

    private static function avisarDescarte(string $origem, string $motivo): void
    {
        Log::channel('autenticacao')->warning(
            "[AparenciaDasTelasDeAcesso@normalizar] Aparencia invalida descartada | origem: {$origem} - motivo: {$motivo}",
            ['origem' => $origem, 'motivo' => $motivo],
        );
    }
}
