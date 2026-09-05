---
paths:
  - 'app/Filament/**/Resources/**'
---

# Filament Resources

## Resource do app nasce com badge de contagem — e a colisão de trait é fatal
Todo Resource escrito em `app/Filament/**/Resources/**`, nos três painéis, usa `App\Filament\Concerns\BadgeContagemNavegacao`. Resource de pacote de terceiro está FORA do escopo: não dá para lhe aplicar o trait sem editar `vendor/`.

O trait conta por `getEloquentQuery()`, nunca por `getModel()::count()` — a query do resource já carrega os escopos daquele painel, e no `/app` é isso que impede o badge de somar uma organização na outra. E o zero APARECE, em `gray`: badge ausente não distingue "está vazio" de "o badge quebrou", e foi assim que alguém olhou "Convites" com a tabela em zero e concluiu que a feature não existia.

Enforço: `tests/Kit/BadgeDeNavegacaoTest.php` varre `getResources()` dos três painéis, filtra o namespace `App\Filament`, e reprova com o FQCN de quem devolver badge nulo. Medido: comentar o trait em um resource deixa o caso vermelho nomeando a classe.

A ARMADILHA NÃO TEM TESTE, E É FATAL. Resource que já use um trait de vendor com métodos de navegação colide, e PHP responde com erro de COMPILAÇÃO — a aplicação inteira para de bootar, `php artisan about` incluído. Nenhuma assertion alcança isso: o processo morre antes de o Pest carregar. O caso concreto é `RoleResource`, que usa `BezhanSalleh\PluginEssentials\Concerns\Resource\HasNavigation`:

    PHP Fatal error: Trait method ...HasNavigation::getNavigationBadge has not been applied as
    RoleResource::getNavigationBadge, because of collision with ...BadgeContagemNavegacao::getNavigationBadge

A resolução é `insteadof`, e são TRÊS — aquele `HasNavigation` declara `getNavigationBadge` (`:41`), `getNavigationBadgeColor` (`:46`) e `getNavigationBadgeTooltip` (`:51`), e cada um colide separadamente:

    use BadgeContagemNavegacao, Essentials\HasNavigation {
        BadgeContagemNavegacao::getNavigationBadge insteadof Essentials\HasNavigation;
        BadgeContagemNavegacao::getNavigationBadgeColor insteadof Essentials\HasNavigation;
        BadgeContagemNavegacao::getNavigationBadgeTooltip insteadof Essentials\HasNavigation;
    }

Declarar os três métodos direto na classe também compila e foi recusado: duplica o corpo do trait, e o dia em que o trait mudar a classe fica para trás em silêncio. Depois de mexer nesse bloco, rode `php artisan about` — é a única prova de que ele compila.

Ver `wikis/specs/main/badge-de-contagem-em-todo-resource/` (ADR-01 e ADR-02).
