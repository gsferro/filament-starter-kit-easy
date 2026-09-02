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

## Assimetria por painel mora em get*AuthorizationResponse() do resource — nunca em can*() nem na policy
Regra que vale num painel e não no outro (o `/app` não exclui usuário; o `/app` não edita quem governa a instalação) fica no resource do painel, sobrescrevendo a RESPOSTA: `getDeleteAuthorizationResponse()`, `getEditAuthorizationResponse()`, devolvendo `Response::deny($motivo)` com log `warning` no canal `autenticacao`.

Por quê nas duas outras opções erradas:
- `canDelete()`/`canEdit()` devolvendo `false` NÃO nega nada: no Filament 5 eles são invólucros que LÊEM a resposta (`Resource/Concerns/HasAuthorization.php:149-157`), e quem decide a ação chama a resposta direto — `Resources/Pages/Page.php:313-314` para `DeleteAction`/`EditAction` da tabela; `EditRecord.php:100` via `canEdit()`. Medido duas vezes (auditoria do Blueprint F-01; wiki `admin-app-nao-alcanca-master-global`).
- A policy é GLOBAL: negar em `UserPolicy::update()` proibiria também o `/admin`, onde a ação é legítima. Policy que pergunta `Filament::getCurrentPanel()` mente fora de request (job, comando).

Quando o alvo precisa também SUMIR da tela (não só ter a ação negada), a resposta é a segunda camada; a primeira é o recorte em `getEloquentQuery()`, que alimenta listagem, route binding (404), busca ⌘K e badge. As duas juntas, porque a query é falha de um só ponto — uma action nova que receba o model de fora da tabela passa por fora dela.

Molde: `App/Users/UserResource.php` (`getDeleteAuthorizationResponse()` e `getEditAuthorizationResponse()`). Teste: chame a resposta DIRETO com o alvo (`UserResource::getEditAuthorizationResponse($alvo)->denied()`), e monte o componente com o alvo em mãos conferindo que o dado não mudou.
