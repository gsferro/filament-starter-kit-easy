---
paths:
  - 'app/Models/**'
---

# Models

## Models com Resource no painel `/app` usam `App\Traits\ModeloCacheavel`

Toda model que tem um Filament Resource em `app/Filament/App/Resources/` deve usar a trait `App\Traits\ModeloCacheavel`. Isso ativa o `mike-bronner/laravel-model-caching` de forma controlada, respeitando `config('laravel-model-caching.enabled')`.

- Use `use App\Traits\ModeloCacheavel;` junto com as demais traits.
- Nunca use a trait diretamente do vendor (`GeneaLabs\LaravelModelCaching\Traits\Cachable`) — a trait intermediária é o ponto único de liga/desliga.
- O default da configuração é `false`; em produção ligue com `MODEL_CACHE_ENABLED=true` e `MODEL_CACHE_STORE=model-cache`.

## papelDoPainel() é exibição, nunca autorização — e consulta papeisEmQualquerContexto()
`User::papelDoPainel()` existe para o cabeçalho do menu do usuário responder "com que papel eu estou aqui". **Nunca use como guarda.** Quem decide entrada é `canAccessPanel()`, que loga a negativa; quem libera tudo é o `Gate::before` do `master_global`.

Duas coisas nele não podem ser "simplificadas":

1. O `master_global` é resolvido **antes** da consulta. O `roles.painel` dele é nulo — nulo não é coringa, ele entra pelo `Gate::before`. Uma consulta por painel devolveria `null` justamente para quem tem mais acesso.

2. A relação é `papeisEmQualquerContexto()`, a mesma de `canAccessPanel()` — **nunca** `roles()`. Com `permission.teams` ligado, a `roles()` do spatie acrescenta `wherePivot(team_id, getPermissionsTeamId())`, e o papel some no `/admin` e no `/infra`, que não têm tenant na rota. O caso que pega isso é `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` ("acha o papel mesmo fora do contexto") — e só ele.

Retorno por `getAttribute('name')`, não `->name`: o genérico da relação é `Model` porque `Config::roleModel()` é `class-string<Model>`, e o PHPStan reprova o acesso direto.
