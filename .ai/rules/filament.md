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
