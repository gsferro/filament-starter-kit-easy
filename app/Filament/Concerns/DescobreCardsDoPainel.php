<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Transforma os destinos do painel corrente em cartões, agrupados como na barra lateral.
 *
 * ## Por que este concern existe, e não `CardItem::make(SeuResource::class)` na página
 *
 * `CardItem` NÃO verifica autorização. O `Concerns/CanBeHidden` do pacote avalia só
 * `visible`/`hidden`, e o `CardsPage::getProcessedGroups()` filtra apenas por `isVisible()`. O
 * `canAccess()` aparece exclusivamente dentro de `discoverClusterCards()` e
 * `discoverResourceCards()` — e nenhum dos dois serve aqui: um exige que a página esteja num
 * Cluster, o outro que ela seja página de um Resource. As nossas são páginas de painel.
 *
 * Consequência de escrever os cartões à mão: um `CardItem::make(UserResource::class)` aparece
 * para todo mundo e só devolve 403 no clique. Não é escalada de privilégio — a autorização do
 * destino continua íntegra —, mas vaza a existência de telas de administração e oferece um
 * caminho que só falha depois do clique.
 *
 * O segundo motivo é manutenção: lista escrita à mão não acompanha Resource novo, e o hub fica
 * incompleto sem nada acusar. Ver ADR-04 da wiki `hub-de-navegacao-em-cards`.
 *
 * ponytail: se o pacote ganhar um `discoverPanelCards()` oficial, este concern é substituído por
 * uma chamada e some inteiro.
 */
trait DescobreCardsDoPainel
{
    /**
     * Os destinos do painel corrente que o visitante PODE acessar, agrupados.
     *
     * @param  list<class-string>  $excluir  classes que não viram cartão (a própria página, o Dashboard)
     * @param  array<class-string, string>  $descricoes  a frase de cada destino, por FQCN. Chave
     *                                                   ausente sai sem frase; chave órfã (destino
     *                                                   que não está neste painel) nunca é lida.
     * @return list<CardGroup>
     */
    protected static function cardsDoPainel(array $excluir = [], array $descricoes = []): array
    {
        $painel = Filament::getCurrentPanel();

        $componentes = [...$painel->getResources(), ...$painel->getPages()];

        return collect($componentes)
            ->reject(fn (string $componente): bool => in_array($componente, $excluir, true))
            // `canAccess()` é o ponto do concern — ver o cabeçalho da classe.
            // `shouldRegisterNavigation()` respeita quem foi deliberadamente tirado da barra
            // lateral: `ConvitesRecebidos` só aparece no menu do usuário, e um índice de
            // navegação não deve desfazer essa decisão por conta própria.
            ->filter(fn (string $componente): bool => $componente::canAccess()
                && $componente::shouldRegisterNavigation())
            ->map(fn (string $componente): CardItem => static::cardDe(
                $componente,
                $descricoes[$componente] ?? null,
            ))
            ->pipe(static::agrupar(...));
    }

    /**
     * Um cartão com os metadados que a própria classe já declara.
     *
     * Nada é redigitado aqui: resource renomeado, com ícone novo ou com badge de contagem se
     * atualiza sozinho no hub. É o que mantém a grade fiel à barra lateral sem duas fontes.
     *
     * A descrição é a única exceção, e é exceção por necessidade: o Filament não tem
     * `getNavigationDescription()`, e a maioria dos destinos de um painel é vendor — não há onde
     * declarar a frase na classe. Ela vem de fora, do mapa que a Page passa. Ver ADR-04 da wiki
     * `hub-de-cards-opcional`.
     *
     * `null` é seguro: `HasDescription::description()` aceita nulo e a blade do pacote só emite o
     * `<p>` sob `@if (filled($itemDescription))`
     * (`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:373`). Cartão
     * sem frase sai byte a byte como antes — é o que mantém os hubs de /admin e /app intactos.
     */
    protected static function cardDe(string $componente, ?string $descricao = null): CardItem
    {
        $icone = $componente::getNavigationIcon();

        return CardItem::make($componente)
            ->label($componente::getNavigationLabel())
            ->description($descricao)
            ->icon($icone instanceof BackedEnum || is_string($icone) ? $icone : null)
            ->badge($componente::getNavigationBadge())
            ->badgeColor($componente::getNavigationBadgeColor())
            ->sort($componente::getNavigationSort() ?? 0)
            // `getUrl()` e não caminho montado à mão: é ele que resolve o segmento do tenant no
            // painel /app (`/app/{slug}/…`). Concatenar quebraria só ali, e só em produção.
            ->url(is_a($componente, Resource::class, true) ? $componente::getUrl() : $componente::getUrl());
    }

    /**
     * Agrupa pelo grupo de navegação, na ordem declarada em `->navigationGroups([...])`.
     *
     * Grupo que o painel não declarou vai para o fim, em ordem alfabética; destino sem grupo
     * nenhum fica num bloco sem rótulo, no final — o mesmo arranjo da barra lateral.
     *
     * @param  Collection<int, CardItem>  $itens
     * @return list<CardGroup>
     */
    protected static function agrupar(Collection $itens): array
    {
        $ordem = collect(Filament::getCurrentPanel()->getNavigationGroups())
            ->map(fn (mixed $grupo): string => is_string($grupo) ? $grupo : (string) $grupo->getLabel())
            ->values()
            ->all();

        // `array_values()` por cima do `->values()`: o `all()` da Collection devolve
        // `array<int, CardGroup>` para o analisador, e o `filament-cards` consome a lista por
        // posição.
        return array_values($itens
            ->groupBy(fn (CardItem $item): string => static::grupoDe($item->getPage()))
            ->sortBy(fn (Collection $itens, string $grupo): array => [
                // Sem rótulo vai por último; depois os grupos declarados, na ordem do painel;
                // e o que sobra, alfabético.
                $grupo === '' ? 2 : (in_array($grupo, $ordem, true) ? 0 : 1),
                $grupo === '' ? 0 : (in_array($grupo, $ordem, true) ? array_search($grupo, $ordem, true) : 0),
                $grupo,
            ])
            ->map(fn (Collection $itens, string $grupo): CardGroup => CardGroup::make($grupo === '' ? null : $grupo)
                ->schema($itens->sortBy(fn (CardItem $item): int => $item->getSort())->values()->all()))
            ->all());
    }

    /** O grupo de navegação da classe, normalizado para string (o Filament aceita enum). */
    protected static function grupoDe(?string $componente): string
    {
        if ($componente === null) {
            return '';
        }

        $grupo = is_a($componente, Page::class, true) || is_a($componente, Resource::class, true)
            ? $componente::getNavigationGroup()
            : null;

        return match (true) {
            is_string($grupo)          => $grupo,
            $grupo instanceof UnitEnum => (string) ($grupo->value ?? $grupo->name),
            default                    => '',
        };
    }
}
