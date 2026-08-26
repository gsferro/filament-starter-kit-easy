---
paths:
  - 'app/Filament/App/Resources/**'
---

# Resources

## Resource do /app fecha a query sem organização — a trait BelongsToTenant não basta
Todo resource do painel `/app` sobrescreve `getEloquentQuery()` e devolve `whereRaw('1 = 0')` quando `Filament::getTenant()` não é `Tenant`. Molde: `App/Users/UserResource.php`.

O escopo global de `BelongsToTenant` FALHA ABERTO por desenho (`app/Traits/BelongsToTenant.php:64-70`): sem tenant, o `if` não entra e nenhum `where` é aplicado. Em request de painel isso nunca acontece — o middleware identifica a organização antes. Fora dele (job, comando, busca sem contexto) o resource devolve TUDO. Medido na auditoria de aderência ao Blueprint: `ProjetoResource` devolvia 4 de 4 projetos de duas organizações enquanto `UserResource` e `ConviteResource` do mesmo painel devolviam 0.

Com tenant, delega ao pai — não duplique o `where` na query da tela (ver a rule "asserção de identidade vive no model").

Enforço: `tests/Tenancy/EscopoFailClosedTest.php` percorre `getResources()` do /app sem tenant e reprova quem devolver registro.
