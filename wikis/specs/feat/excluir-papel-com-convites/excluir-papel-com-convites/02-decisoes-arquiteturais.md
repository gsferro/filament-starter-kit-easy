# Decisões Arquiteturais — Roadmap item 9: excluir um papel que tem convite

Nenhuma decisão passou nos três portões. As escolhas de desenho estão em `## Decisões de Desenho` do `01`.

## Superfície Livewire

A feature não cria página, widget nem componente: muda três ações existentes do Filament. Nenhum
`public function` nem `public $` novo no código da feature.

| Ponto | Origem | O que o cliente alcança | Fronteira |
|---|---|---|---|
| `DeleteAction` da linha (`app/Filament/Admin/Resources/Roles/RoleResource.php:DeleteAction:297`) | framework | monta a ação com a chave de um registro da tabela | registro resolvido pela query da tabela; `RolePolicy::delete()`; recusa por convite pendente no `before` e no `deleting` |
| `DeleteBulkAction` (`app/Filament/Admin/Resources/Roles/RoleResource.php:DeleteBulkAction:310`) | framework | envia as chaves selecionadas | `authorizeIndividualRecords('delete')`; recusa por convite pendente no `before` e no `deleting` |
| `DeleteAction` da edição (`app/Filament/Admin/Resources/Roles/Pages/EditRole.php:DeleteAction:31`) | framework | o registro da página | `RolePolicy::delete()`; recusa no `before` e no `deleting` |

Greps sobre o código que a feature escreve (2026-11-16, antes da implementação):

```
grep -n "public function\|public \$" app/Filament/Admin/Resources/Roles/Pages/EditRole.php
→ getActions/afterSave etc. já existentes; nenhum novo previsto
```
