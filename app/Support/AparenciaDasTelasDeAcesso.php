<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Illuminate\Support\Facades\Log;

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

    public const FUNDOS = ['cor', 'arte'];

    public const LIMITE_DA_FRASE = 80;

    public const LIMITE_DO_APOIO = 160;

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
     * @return array{layout: string, lado_do_formulario: string, fundo: string, cor: ?string, frase: ?string, apoio: ?string, logo_no_painel: bool, alternador_de_tema: bool}
     */
    public static function padraoDoPainel(): array
    {
        return [
            'layout'             => self::LAYOUT_PAINEL,
            'lado_do_formulario' => 'direita',
            'fundo'              => 'cor',
            'cor'                => null,
            'frase'              => null,
            'apoio'              => null,
            'logo_no_painel'     => true,
            'alternador_de_tema' => true,
        ];
    }

    /**
     * O valor gravado, reduzido ao vocabulário. `null` = clássico.
     *
     * @param  string  $origem  `global` ou `tenant:{id}` — só para o log
     * @return array{layout: string, lado_do_formulario: string, fundo: string, cor: ?string, frase: ?string, apoio: ?string, logo_no_painel: bool, alternador_de_tema: bool}|null
     */
    public static function normalizar(mixed $bruto, string $origem): ?array
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

        return [
            'layout'             => self::LAYOUT_PAINEL,
            'lado_do_formulario' => self::umDe($bruto['lado_do_formulario'] ?? null, self::LADOS, $padrao['lado_do_formulario']),
            'fundo'              => self::umDe($bruto['fundo'] ?? null, self::FUNDOS, $padrao['fundo']),
            'cor'                => self::cor($bruto['cor'] ?? null),
            'frase'              => self::texto($bruto['frase'] ?? null, self::LIMITE_DA_FRASE),
            'apoio'              => self::texto($bruto['apoio'] ?? null, self::LIMITE_DO_APOIO),
            'logo_no_painel'     => self::booleano($bruto['logo_no_painel'] ?? null, $padrao['logo_no_painel']),
            'alternador_de_tema' => self::booleano($bruto['alternador_de_tema'] ?? null, $padrao['alternador_de_tema']),
        ];
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
            mediaSize: '50%',
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
     * a primária da organização, senão a da instalação; `null` deixa a CSS cair na variável
     * `--primary-600` do painel.
     *
     * O valor só pode vir de duas fontes, e as duas são fechadas: `#RRGGBB` validado, ou uma
     * constante de `Filament\Support\Colors\Color` (resolvida por `CorPrimaria::resolver()`).
     *
     * @param  array<string, mixed>|null  $aparencia
     */
    public static function corDoPainel(?array $aparencia, ?Tenant $organizacao): ?string
    {
        $propria = self::cor($aparencia['cor'] ?? null);

        if ($propria !== null) {
            return $propria;
        }

        $resolvida = ($organizacao !== null ? CorPrimaria::resolver($organizacao->cor_primaria, $organizacao->cor_primaria_nome) : [])
            ?: CorPrimaria::paleta();

        $primaria = $resolvida['primary'] ?? null;

        if (is_string($primaria)) {
            return $primaria;
        }

        if (is_array($primaria) && is_string($primaria[600] ?? null)) {
            return $primaria[600];
        }

        return null;
    }

    /**
     * A cor de fundo é clara demais para texto branco? Só para `#RRGGBB` (a cor escolhida na
     * aparência ou a hexadecimal da organização): luminância relativa (WCAG) acima de 0,4 pede texto
     * escuro (P-18). As cores da paleta do Filament (`oklch`, tom 600) são escuras e ficam no branco.
     */
    public static function fundoClaro(?string $cor): bool
    {
        if ($cor === null || preg_match(self::FORMATO_DA_COR, $cor) !== 1) {
            return false;
        }

        $canal = static function (string $hex): float {
            $c = hexdec($hex) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        $luminancia = 0.2126 * $canal(substr($cor, 1, 2)) + 0.7152 * $canal(substr($cor, 3, 2)) + 0.0722 * $canal(substr($cor, 5, 2));

        return $luminancia > 0.4;
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
