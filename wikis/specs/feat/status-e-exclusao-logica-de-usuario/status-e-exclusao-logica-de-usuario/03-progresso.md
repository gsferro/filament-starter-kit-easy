# Progresso — Status de ativo/inativo e exclusão lógica de usuário

Branch `feat/status-e-exclusao-logica-de-usuario` (worktree isolado, criado a partir da `main`
`b6af684` e com `origin/fix/kit-install-force-semeia-do-env` (PR #45) mesclada antes de qualquer
escrita — é a branch que trouxe `users.origem`, `VinculoSocial` e o `retorno()` novo do login
social, sobre os quais esta feature constrói).

## 1. Migration: `users.ativo` e `users.deleted_at`

- [ ] `database/migrations/2026_08_26_200001_add_ativo_and_soft_deletes_to_users_table.php`

## 2. `App\Models\User`

- [ ] `SoftDeletes` + `Recyclable`
- [ ] cast `ativo`, fora do `$fillable`
- [ ] `scopeComEmail()`
- [ ] `motivoDeIndisponibilidade()`
- [ ] guarda em `canAccessPanel()` (primeira instrução) com log
- [ ] `desativar()` / `reativar()` / `motivoParaNaoDesativar()` / `ehOUltimoMasterGlobalAtivo()`

## 3. Tela de aviso

- [ ] `resources/views/auth/conta-indisponivel.blade.php`
- [ ] `app/Http/Controllers/Auth/ContaIndisponivelController.php`
- [ ] rota `auth.conta-indisponivel` em `routes/web.php`

## 4. `TelaLogin`

- [ ] `authenticate()` com `try/catch` + interceptor com `Timebox` + `Hash::check`
- [ ] `Failed` à mão só para excluído
- [ ] `->usingPage(TelaLogin::class)` no `/admin` e no `/infra`

## 5. Login social

- [ ] `contaCom()` com `withTrashed()` + `comEmail()`
- [ ] `recusarSeIndisponivel()` nos dois ramos de `retorno()`
- [ ] `confirmarVinculo()` com `withTrashed()` + a checagem

## 6. Trait `SituacaoDaConta`

- [ ] coluna (3 estados), filtro de inativos, `acaoDeDesativar()`, `acaoDeReativar()`
- [ ] `AprovacaoDeCadastro` perde `colunaDeSituacao()`

## 7. Os dois `UserResource`, Shield e policy

- [ ] `/admin`: trait, filtros (`inativos`, `TrashedFilter`), ações (`desativar`, `reativar`, `RestoreAction`)
- [ ] `/app`: trait, filtro de inativos (sem ações)
- [ ] `config/filament-shield.php` → `resources.manage`
- [ ] `UserPolicy::desativar()` / `reativar()`
- [ ] `PermissoesDeAcoesTest::inventarioDeAutorizacao()` com as duas entradas

## 8. Lixeira do `/infra`

- [ ] `User::class` em `->models()`, comentário reescrito
- [ ] `Projeto` com `Recyclable` (dívida paga)

## 9. Guarda executável da Lixeira

- [ ] `tests/Kit/LixeiraTest.php` — CT-25, CT-26, CT-27, CT-28, CT-29, CT-32

## 10. Testes da feature

- [ ] `tests/Kit/SituacaoDaContaTest.php` — CT-01…CT-12, CT-18…CT-24, CT-30, CT-31, CT-34, CT-35
- [ ] `tests/Kit/LoginSocialContaIndisponivelTest.php` — CT-13…CT-17
- [ ] `tests/Kit/SituacaoDaContaDocumentacaoTest.php` — CT-33
- [ ] `tests/Kit/VinculoDeProvedorSocialTest.php` CT-V07 → `forceDelete()`

## 11. README

- [ ] `README.md` — seção nova, Lixeira, roteiro (F-69, F-70)
- [ ] `README.en.md` — espelho

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` nos arquivos tocados
- [ ] testes novos verdes
- [ ] regressão (14 arquivos listados no PRD) verde
- [ ] `--testsuite=Kit` inteira
- [ ] duas mutações registradas abaixo
- [ ] `git push -u origin feat/status-e-exclusao-logica-de-usuario` (sem PR)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "a Lixeira lista qualquer model com `SoftDeletes` declarada em `models()`" (premissa inicial do coordenador e do README) | `Tables/RecycleBin.php:120-124` lê `recycle_bin_items`; quem grava é `Recyclable::booted()` (`Recyclable.php:29-45`). `Projeto` não usa a trait | ADR-06; passo 8 ganhou `Recyclable` nos dois models; CT-29 |
| "sobrescrever `throwFailureValidationException()` para redirecionar" (sugestão do coordenador) | é `never` e roda dentro do `Timebox` (`Login.php:89-113,231-236`) | ADR-02: `try/catch ValidationException` em `authenticate()` |
| "o Filament faz logout e mostra erro genérico quando `canAccessPanel()` nega" | no v5 não há login+logout: `retrieveByCredentials` → `validateCredentials` → `isUserAllowedToAccessPanel` → `fireFailedEvent($user)` + exceção, **antes** de `attemptWhen` (`Login.php:98-110`) | R3/CT-09: a trilha recebe `Failed` com o usuário sem código novo; `Failed` à mão só para o excluído (M12) |
| "`Recyclable::booted()` pode colidir com `booted()` de outra trait" | nenhuma trait de `User`/`Projeto` declara `booted()` (grep em Breezy, auth-log, HasRoles, Auditable, `app/Traits`) | risco registrado, sem passo |
| "`$vinculo->user` acha o excluído" | `belongsTo` respeita o escopo → `null`; o fluxo cai no ramo do e-mail | passo 5 documenta; CT-15 usa o ramo do e-mail para excluído |
| "`->unique()` do Filament ignora soft-deleted" | doc do Laravel 13: inclui por default; `withoutTrashed()` é opt-in | ADR-05 sem código; CT-32 |
| "`getEloquentQuery()` do resource precisa remover o `SoftDeletingScope` para o `TrashedFilter`" (padrão do gerador do Filament) | `BadgeContagemNavegacao` conta por `getEloquentQuery()`; o `TrashedFilter` chama `withTrashed()`, que remove o escopo sozinho | passo 7: query intocada |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `debug` "Aviso exibido" no controller da tela de aviso — segundo log da mesma recusa | sim | `01`, passo 3 |
| 2 | `podeSerDesativado()` + guardas repetidas em `desativar()` — mesma regra em dois lugares | sim → `motivoParaNaoDesativar(): ?string` único | `01`, passos 2 e 6 |
| 3 | `try/catch RuntimeException` + `Notification` + `halt()` na Action para corrida entre abas | sim (ação já oculta; exceção legível basta) | `01`, passo 6 |
| 4 | dois mecanismos de restauração (`TrashedFilter`/`RestoreAction` no `/admin` e Revive no `/infra`) | **recusada**: o papel `admin` não alcança o `/infra`, e o requisito diz "/admin ou /infra" | `00` ambiguidade RQ-11 |
| 5 | seis commits → quatro | sim | `01`, Commits |

## Blockers

- nenhum até aqui

## Desvios do Plano

- _(pós-implementação)_

## Notas de Implementação

- _(pós-implementação)_

## Mutações executadas

- _(pós-implementação)_

## Retrospectiva

- _(pós-implementação)_
