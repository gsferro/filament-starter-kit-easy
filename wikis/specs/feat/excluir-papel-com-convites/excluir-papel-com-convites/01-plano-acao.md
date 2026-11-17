# Plano de Ação — Roadmap item 9: excluir um papel que tem convite

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/phpstan-nivel-8/phpstan-nivel-8/` (premissa P-07 e `[CT-12]`, que mediram o 500 e travaram o invariante do banco)
- **Motivo**: a recusa do banco chega ao administrador como 500; falta a resposta da tela, e o histórico de convites bloqueia a exclusão sem motivo de produto
- **Toca infra compartilhada?**: não — o model de convite ganha uma consulta nomeada que a consulta existente passa a reutilizar, sem mudar o resultado dela

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Excluir papel com convite pendente não responde 500 | 2, 3 | — |
| RQ-02 | A exclusão é recusada; o papel continua | 2, 3 | — |
| RQ-03 | O aviso lista os e-mails dos convites pendentes | 3 | — |
| RQ-04 | Recusada, nada é apagado | 2, 3 | — |
| RQ-05 | O convite pendente continua aceitável com o mesmo papel | 2 | o `[CT-12]` da ancestral continua no `tests/` |
| RQ-06 | Revogados os pendentes, a exclusão passa | 2 | — |
| RQ-07 | Aceitos, recusados e expirados somem junto com o papel | 2 | — |
| RQ-08 | A remoção deles fica na auditoria | 2 | — |
| RQ-09 | Pendente e histórico juntos: recusa, nada apagado | 2 | — |
| P-01 | "Pendente" = Situação Pendente da tela de Convites | 1 | — |
| P-02 | Recusa nos três caminhos de exclusão | 3 | — |
| P-03 | Exclusão em massa: nada da seleção sai se um papel está bloqueado | 3 | — |
| P-04 | No máximo 5 e-mails por papel, "e mais N" | 3 | — |
| P-05 | Convites de todas as organizações contam | 1, 2 | — |
| P-06 | E-mails só para quem vê convites | 3 | — |
| P-07 | Papel pelo rótulo no aviso | 3 | — |
| P-08 | Encerrados saem sem exigir permissão de convite | 2 | — |
| P-09 | Exclusão atômica; convite pendente na limpeza não sai | 2 | — |

## Objetivo

Trocar o 500 que o administrador vê ao excluir um papel com convite por uma resposta de produto:
com convite **pendente**, a exclusão é recusada com um aviso que lista os e-mails; com convites só
**encerrados** (aceitos, recusados ou expirados), eles são apagados junto com o papel, um a um, para
a auditoria registrar cada remoção.

## Contexto

A chave estrangeira `convites.role_id` não tem cascade de propósito. Hoje qualquer convite do papel,
pendente ou não, faz o `DELETE` do papel estourar a violação de chave estrangeira. A ação da linha e a
da página de edição deixam a exceção subir (500). A exclusão em massa do Filament captura a exceção e
mostra só "N não puderam ser excluídos", sem dizer por quê.

## Análise dos Arquivos Existentes

### `app/Models/Convite.php`
- `app/Models/Convite.php:pendentesPara():542-553` já monta a condição de pendente (sem aceite, sem recusa, `expira_em > now()`) e tira o escopo de organização do painel `/app` com `withoutGlobalScope(Filament::getPanel('app')->getTenancyScopeName())`. É a mesma regra da `app/Models/Convite.php:situacao():600-608` (validade nula conta como Expirado, P-01).
- O model é auditável (`AuditsFillables`, eventos `created`/`updated`/`deleted` em `config/audit.php:events:65-70`): só `->delete()` por instância grava a trilha; `DELETE` em massa pela query não grava.

### `app/Models/Role.php`
- `app/Models/Role.php:Role:39-45` estende o `Role` do spatie e só usa `TemUuid`. Não tem relação com convite.

### `app/Filament/Admin/Resources/Roles/RoleResource.php` e `Pages/EditRole.php`
- Três caminhos de exclusão: `DeleteAction::make()` na linha da tabela (`app/Filament/Admin/Resources/Roles/RoleResource.php:DeleteAction:297`), `DeleteBulkAction::make()->authorizeIndividualRecords('delete')` (`app/Filament/Admin/Resources/Roles/RoleResource.php:DeleteBulkAction:310`) e `DeleteAction::make()` no cabeçalho da edição (`app/Filament/Admin/Resources/Roles/Pages/EditRole.php:DeleteAction:31`).
- `vendor/filament/actions/src/DeleteAction.php:action:51-60`: `$record->delete()` devolvendo `false` chama `failure()`, sem exceção.
- `vendor/filament/actions/src/DeleteBulkAction.php:action:81-111`: cada registro é apagado num `try`; exceção vira "falha de processamento" genérica.

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | A regra mora no model `Role` (`delete()` sobrescrito *(alterado em 2026-11-16: o `deleting` do spatie roda antes e já desliga permissões e usuários; recusar num listener deixaria o papel vazio)*), e as três ações só montam o aviso antes: assim tinker, seeder e qualquer tela futura recebem a mesma recusa | autorização existente ("prossiga até o fim") | surpreendente | sessão, 2026-11-16 |
| D2 | A exclusão do papel roda numa transação, com o papel e os convites travados, e cada convite é relido antes de sair *(alterado em 2026-11-17: reinstalada pela P-09 — os três revisores do step 9 mostraram a corrida e o papel vazio quando o `DELETE` falha depois do `deleting` do spatie)* | P-09 | — | sessão, 2026-11-17 |
| ~~D2 (versão do step 6)~~ | ~~A exclusão do papel roda numa transação~~ — cortada no step 6: a recusa acontece antes de apagar qualquer convite, então RQ-04 não depende dela; só um erro inesperado do banco depois do `deleting` deixaria convite encerrado apagado com o papel ainda presente, e esse convite já não servia para entrar | autorização existente | — | sessão, 2026-11-16 *(alterado em 2026-11-16: Ponytail)* |
| D4 | O `[CT-12]` da `phpstan-nivel-8` passa a provar a chave estrangeira por um `DELETE` pela query, que pula o model: o `delete()` do model agora recusa antes do banco e não lança `QueryException` | Q5 (desenho) | trade-off | sessão, 2026-11-16 |
| D3 | Canal de log `autenticacao`, o mesmo dos convites | convenção (`ConvitesTable` revogar) | trade-off | sessão, 2026-11-16 |

## Autorização

- **Policies**: nenhuma mudança. `RolePolicy::delete()`/`deleteAny()` continuam decidindo quem exclui; a recusa por convite vem depois da autorização
- **Gates / Middleware / Guards**: nenhum

## Rotas

Nenhuma rota nova.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Lista de papéis — ação Excluir da linha | Filament | `/admin/roles` | exclui um papel e vê o aviso de recusa | Não |
| Lista de papéis — exclusão em massa | Filament | `/admin/roles` | seleciona papéis, exclui e vê o aviso de recusa | Não |
| Edição do papel — ação Excluir do cabeçalho | Filament | `/admin/roles/{record}/edit` | exclui e vê o aviso de recusa | Não |

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

- **Observers**: nenhum — a regra é o `delete()` do `Role` *(alterado em 2026-11-16: ver D1)*

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | 1 por ação, como hoje |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado por request? | nada |
| O que é cacheado entre requests? | nada |
| Custo do caminho principal | +1 consulta de pendentes por papel no aviso, +1 no `deleting`; com histórico, +1 consulta e 1 `DELETE` + 1 auditoria por convite encerrado |

## Impacto em Features Existentes

- Convites (`ConviteTest`, `ConviteEmMassaTest`, `ConviteRecebidoNoMenuTest`): `pendentesPara()` passa a reutilizar a consulta nomeada — o resultado não muda
- Tela de papéis (`TelaDePapeisTest`, `PapeisPorOrganizacaoTest`): exclusão sem convite segue igual
- `[CT-12]` da `phpstan-nivel-8`: afirma que o **banco** recusa; continua valendo para um `DELETE` que pula o model

## Rollback

- Sem migration. Reverter o commit devolve o comportamento anterior

## Dependências

Nenhuma.

## Riscos

- Escopo de organização esconder convites e o `DELETE` estourar a chave estrangeira mesmo assim: a relação tira o escopo do painel `/app`, como a `pendentesPara()` (P-05), e o teste de tenancy cobre
- Aviso longo com muitos convites: teto de 5 e-mails por papel (P-04)

## Channel de Log da Feature

`autenticacao` (já existe em `config/logging.php:autenticacao:132`, usado pela revogação de convite). Sem channel novo.

## Estrutura de Implementação

### 1. Consulta nomeada de convite pendente

> Skills: `laravel-best-practices`

- **Path**: `app/Models/Convite.php`
- Novo `scopePendentes(Builder $query): void` com `whereNull('aceito_em')`, `whereNull('recusado_em')`, `where('expira_em', '>', now())`
- `pendentesPara()` passa a usar `->pendentes()` no lugar das três linhas, sem mudar o resultado
- **Atende**: P-01, P-05
- **Logs**: nenhum (consulta)

### 2. A regra no model `Role`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Models/Role.php`
- `convites(): HasMany` para `Convite`, com `withoutGlobalScope(Filament::getPanel('app')->getTenancyScopeName())` (P-05)
- `delete(): ?bool` sobrescrito *(alterado em 2026-11-16: era um listener `deleting`; o `deleting` do spatie em `HasPermissions::bootHasPermissions()` roda antes e desliga permissões e usuários, então a recusa tem de vir antes de qualquer evento)*:
  - se há convite pendente: log e `return false`, sem chamar o `parent::delete()` (RQ-02, RQ-04, RQ-09)
  - senão: `convites()->get()->each->delete()` — instância por instância, para a auditoria (RQ-07, RQ-08) — e `parent::delete()`
  - tudo dentro de `DB::beginTransaction()`, com `lockForUpdate()` no papel e nos convites; cada convite é relido pelo scope `pendentes()` antes de sair, e um que voltou a Pendente desfaz tudo e recusa; qualquer exceção faz `rollBack()` e sobe *(alterado em 2026-11-17: P-09)*
- **Atende**: RQ-01, RQ-02, RQ-04, RQ-05, RQ-06, RQ-07, RQ-08, RQ-09, P-05, P-08, P-09
- `tests/Kit/ConviteTest.php` `[CT-12]`: o `DELETE` vai pela query (`Role::query()->whereKey(...)->delete()`) para continuar provando a recusa do banco (D4)
- **Logs**:
  - `Log::channel('autenticacao')->warning("[Role@delete] Exclusão recusada: papel com convite pendente | papel: {$papel->id}", ['role_id' => $papel->id, 'pendentes' => $quantidade])`
  - `Log::channel('autenticacao')->info("[Role@delete] Convites encerrados apagados com o papel | papel: {$papel->id}", ['role_id' => $papel->id, 'convites' => $quantidade])` — só quando houver algum
  - Allowlist: `role_id` e contagens; sem e-mail

### 3. O aviso nas três ações de exclusão

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Resources/Roles/RoleResource.php`, `app/Filament/Admin/Resources/Roles/Pages/EditRole.php`
- `RoleResource::recusarSeHaConvitePendente(Action $action, iterable $papeis): void` — para cada papel com pendentes, uma linha "**{nome}**: e-mail, e-mail… e mais N" (até 5 e-mails, P-04); sem `ViewAny` de convite, "**{nome}**: N convites pendentes" (P-06); havendo alguma, `Notification::make()->danger()->title('Papel com convite pendente')->body(...)->persistent()->send()` e `$action->cancel()` (P-03: a seleção inteira para)
- `DeleteAction` da linha e da edição: `->before(fn (DeleteAction $action, Role $record) => self::recusarSeHaConvitePendente($action, [$record]))`
- `DeleteBulkAction`: `->before(fn (DeleteBulkAction $action, Collection $records) => self::recusarSeHaConvitePendente($action, $records))`
- O corpo diz o que fazer: "Revogue os convites pendentes na tela de Convites e tente de novo."
- **Atende**: RQ-01, RQ-02, RQ-03, RQ-04, P-02, P-03, P-04, P-06, P-07
- **Logs**: o do passo 2 cobre a recusa; o aviso não loga de novo

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação. Atalhos deliberados marcados com `ponytail:`.
> **Caveman** só na conversa; wiki, código, commits e PR em prosa normal.
> **Baseline antes do primeiro commit**: suíte completa em `main` (`d1b69284`) — 6264 testes, 6209 passaram, 55 pulados, 0 falhas (2026-11-16, `vendor/bin/pest --tia --parallel --processes=3 --compact`).

## Testes

> Ver `04-casos-de-teste.md`.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] testes da feature (Kit e Tenancy)
- [ ] `vendor/bin/pest --parallel --tia` contra a baseline
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)

## Commits
- `:bug: fix(papeis): excluir papel com convite pendente recusa com aviso; convites encerrados saem junto`
- `:memo: docs(wiki): wiki da correção excluir-papel-com-convites`
