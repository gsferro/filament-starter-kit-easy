<?php

namespace App\Filament\Concerns;

use Gsferro\FilamentOdometerEasy\Navigation\OdometerNavigationBadge;

/**
 * Badge de contagem animado no item de menu do Resource.
 *
 * **Obrigatório em todo Resource escrito no app**, nos três painéis. Não é hábito nem preferência:
 * `tests/Kit/BadgeDeNavegacaoTest.php` varre os painéis registrados e fica VERMELHO com o nome da
 * classe que ficar de fora. Resource de pacote de terceiro está fora do escopo — o teste filtra
 * por namespace `App\Filament\`.
 *
 * A contagem sai de `getEloquentQuery()`, NUNCA de `getModel()::count()`: a query do resource já
 * carrega os escopos que valem para aquele painel (soft deletes, filtros de posse, escopo de
 * organização). Contar direto no model mostraria ao usuário um número que a listagem não confirma
 * — e, no painel `/app`, somaria as organizações umas nas outras.
 *
 * ## O zero aparece, e isso reverte a regra anterior
 *
 * Até a v0.28 o trait devolvia `null` quando a contagem era zero, com o argumento de que "um `0`
 * cinza em todo item só polui o menu". O argumento é legítimo e o efeito colateral não tinha sido
 * previsto: **badge ausente não distingue "está vazio" de "o badge quebrou"**. Alguém olhou o item
 * "Convites" sem badge, com a tabela em zero, e concluiu que a feature não existia.
 *
 * A concessão é a cor: zero fica em `gray`, contagem maior que zero mantém a cor default do
 * Filament (`getNavigationBadgeColor()` devolvendo `null`, que é o default de
 * `Resources\Resource\Concerns\HasNavigation:158`). Ver ADR-01 de
 * `wikis/specs/main/badge-de-contagem-em-todo-resource/`.
 *
 * ## Colisão de trait é FATAL, e é onde este trait dói
 *
 * Resource que já use um trait de vendor com métodos de navegação — `RoleResource` usa
 * `BezhanSalleh\PluginEssentials\Concerns\Resource\HasNavigation` — colide nos TRÊS métodos, e PHP
 * responde com erro de compilação, não com teste vermelho: a aplicação inteira para de bootar.
 * A resolução é `insteadof`, um por método. Ver ADR-02 da mesma wiki, com a mensagem transcrita.
 */
trait BadgeContagemNavegacao
{
    public static function getNavigationBadge(): ?string
    {
        return OdometerNavigationBadge::make(static::contagemDoBadge());
    }

    /**
     * Cinza no vazio; acima disso, a cor default do painel.
     *
     * `null` não é "sem cor" — é o que `HasNavigation::getNavigationBadgeColor()` devolve por
     * padrão, ou seja, exatamente a cor que estes badges já tinham antes de o zero passar a
     * aparecer.
     *
     * @return string|array<string>|null
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::contagemDoBadge() === 0 ? 'gray' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total de registros';
    }

    /**
     * O número e a cor precisam da MESMA contagem no mesmo request.
     *
     * `once()` e não propriedade estática: propriedade estática de trait sobrevive entre requests
     * no mesmo worker do Octane, e o badge congelaria no valor do primeiro request até o worker
     * reciclar — "o número não atualiza" só em produção. Ver ADR-03.
     */
    private static function contagemDoBadge(): int
    {
        return once(fn (): int => static::getEloquentQuery()->count());
    }
}
