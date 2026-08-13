---
paths:
  - 'app/Filament/**'
---

# Filament

## Papel e permissão se gravam pela API do spatie, nunca por sync da relação
Em campo de formulário que grava `roles` ou `permissions`, o `->relationship()` do Filament NÃO serve sozinho: ele salva com `$relationship->sync()`, que escreve na pivot só as colunas da chave.

Com multi-tenancy ligada isso estoura 500 (`NOT NULL constraint failed: model_has_roles.team_id`) — o `wherePivot` que o spatie põe em `roles()` filtra LEITURA e não alimenta escrita; quem passa o `team_id` do contexto é o `assignRole()`/`syncRoles()`. Mesmo em single-tenant o `sync()` deixa o cache de papéis velho.

Use `->saveRelationshipsUsing()` chamando `syncRoles()`, e resolva os papéis em MODELOS antes (o state vem do Livewire como string, e o `collectRoles()` do spatie trata string como nome de papel — `"4"` viraria `RoleDoesNotExist`). Ver `app/Filament/Admin/Resources/Users/UserResource.php`.

Teste em par: um caso em `tests/Kit` (single-tenant) e um em `tests/Tenancy` conferindo o `team_id` da pivot. Abrir a tela não cobre — o `GET /admin/users` seguia verde com o salvamento quebrado.

## Resource ou RelationManager novo exige gerar as permissões

Resource novo nasce sem permission no banco: a tela responde 403 para todo mundo que não seja `master_global`. Depois de `make:filament-resource`, rode sempre os dois seeders, nesta ordem:

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

O primeiro roda `shield:generate --all` **em cada painel** (o comando só enxerga o painel corrente) e escreve as policies; o segundo recorta a matriz por painel via `App\Support\Paineis::permissoes()` e devolve as permissões aos papéis. Os dois são idempotentes.

**RelationManager o Shield não enxerga.** A descoberta cobre apenas Resources, Pages e Widgets (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php`), então nenhuma permission é gerada para ele e a autorização recai na policy do model relacionado. Se esse model já tem Resource em algum painel, não há nada a fazer. Se não tem, crie a policy à mão (`php artisan make:policy`) e declare as chaves em `config('filament-shield.custom_permissions')` **antes** de rodar os seeders — do contrário o RelationManager fica aberto a qualquer um que consiga abrir o Resource pai.

## Papel novo precisa declarar o painel

`roles.painel` é o que dá acesso ao painel — `User::canAccessPanel()` compara a coluna com o id do painel. **Nulo não é coringa**: papel sem painel não abre painel algum (o `master_global` entra pelo `Gate::before`, não pela coluna). Papel criado sem painel só carrega permissões, e quem o tiver sozinho autentica e leva 403 nos três painéis.

Papel semeado entra em `database/seeders/PapeisSeeder.php`, com o painel no terceiro argumento de `papel()`.
