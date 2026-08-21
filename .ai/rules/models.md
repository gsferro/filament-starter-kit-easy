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

## Mídia e soft delete: o que cada trait obriga em outro lugar
**`SoftDeletes`** — model que ganha a trait PRECISA entrar na lista `models()` do `RevivePlugin`, no `InfraPanelProvider`. Sem isso o registro é apagado e não existe tela para restaurar. A lista é explícita, e não `modelsNamespace()`, porque a varredura automática alcançaria `User`, `Role` e `Tenant`, cuja restauração tem consequência de autorização — usuário volta com papel numa organização que pode não existir mais.

**`InteractsWithMedia`** (spatie/laravel-medialibrary):

- O isolamento por organização é **herdado, não configurado**: a tabela `media` é polimórfica, o arquivo pertence ao registro, e o registro já é escopado por `BelongsToTenant`. Não há coluna de tenant em `media`.
- Herdado quer dizer que três coisas o desfazem, sem gerar erro: query direta em `Media` (a tabela não tem escopo nenhum), dono que não é escopado (o `User` do kit pertence a várias organizações) e model nova sem `BelongsToTenant`.
- **Coleção de mídia declara o disco**: `$this->addMediaCollection('x')->useDisk('local')`. Quem decide se o arquivo sai sem sessão é o **disco**, não o `->visibility('private')` do campo de upload — e a declaração vence o default, então trocar `MEDIA_DISK` não reabre o vazamento. O default já é `local` (privado, servido por URL assinada); a redundância é de propósito.
- A URL é **assinada, não autorizada**: `Media::getUrl()` de mídia privada responde 403 (falha fechada) e o link publicável vem de `getTemporaryUrl()`, mas quem tem o link entra sem sessão durante a validade. Avatar e logo ficam em `->disk('public')` explícito, porque aparecem antes de haver sessão.
- Em `registerMediaConversions()`, `nonQueued()` vem **antes** de `width()`/`height()`: os dois últimos são encaminhados ao `ImageDriver` e devolvem o driver, não a `Conversion`. Encadeado depois, é `BadMethodCallException` na primeira conversão.
- Enquanto o kit nascer com `QUEUE_CONNECTION=sync`, conversão enfileirada nunca é gerada e a coluna fica vazia sem erro. `nonQueued()` é o default certo aqui.

Referência viva das duas: `App\Models\Projeto` e `app/Filament/App/Resources/Projetos/ProjetoResource.php`.
