# Progresso — Status de ativo/inativo e exclusão lógica de usuário

Branch `feat/status-e-exclusao-logica-de-usuario` (worktree isolado, criado a partir da `main`
`b6af684` e com `origin/fix/kit-install-force-semeia-do-env` (PR #45) mesclada antes de qualquer
escrita — é a branch que trouxe `users.origem`, `VinculoSocial` e o `retorno()` novo do login
social, sobre os quais esta feature constrói).

## 1. Migration: `users.ativo` e `users.deleted_at`

- [x] `database/migrations/2026_08_26_200001_add_ativo_and_soft_deletes_to_users_table.php`

## 2. `App\Models\User`

- [x] `SoftDeletes` + `Recyclable`
- [x] cast `ativo`, fora do `$fillable`
- [x] `scopeComEmail()`
- [x] `motivoDeIndisponibilidade()`
- [x] guarda em `canAccessPanel()` (primeira instrução) com log
- [x] `desativar()` / `reativar()` / `motivoParaNaoDesativar()` / `ehOUltimoMasterGlobalAtivo()`

## 3. Tela de aviso

- [x] `resources/views/auth/conta-indisponivel.blade.php`
- [x] `app/Http/Controllers/Auth/ContaIndisponivelController.php`
- [x] rota `auth.conta-indisponivel` em `routes/web.php`

## 4. `TelaLogin`

- [x] `authenticate()` com `try/catch` + interceptor com `Timebox` + `Hash::check`
- [x] `Failed` à mão só para excluído
- [x] `->usingPage(TelaLogin::class)` no `/admin` e no `/infra`

## 5. Login social

- [x] `contaCom()` com `withTrashed()` + `comEmail()`
- [x] `recusarSeIndisponivel()` nos dois ramos de `retorno()`
- [x] `confirmarVinculo()` com `withTrashed()` + a checagem

## 6. Trait `SituacaoDaConta`

- [x] coluna (3 estados), filtro de inativos, `acaoDeDesativar()`, `acaoDeReativar()`
- [x] `AprovacaoDeCadastro` perde `colunaDeSituacao()`

## 7. Os dois `UserResource`, Shield e policy

- [x] `/admin`: trait, filtros (`inativos`, `TrashedFilter`), ações (`desativar`, `reativar`, `RestoreAction`)
- [x] `/app`: trait, filtro de inativos (sem ações)
- [x] `config/filament-shield.php` → `resources.manage`
- [x] `UserPolicy::desativar()` / `reativar()`
- [x] `PermissoesDeAcoesTest::inventarioDeAutorizacao()` com as duas entradas

## 8. Lixeira do `/infra`

- [x] `User::class` em `->models()`, comentário reescrito
- [x] `Projeto` com `Recyclable` (dívida paga)

## 9. Guarda executável da Lixeira

- [x] `tests/Kit/LixeiraTest.php` — CT-25, CT-26, CT-27, CT-28, CT-29, CT-32

## 10. Testes da feature

- [x] `tests/Kit/SituacaoDaContaTest.php` — CT-01…CT-12, CT-18…CT-24, CT-30, CT-31, CT-34, CT-35
- [x] `tests/Kit/LoginSocialContaIndisponivelTest.php` — CT-13…CT-17
- [x] `tests/Kit/SituacaoDaContaDocumentacaoTest.php` — CT-33
- [x] `tests/Kit/VinculoDeProvedorSocialTest.php` CT-V07 → `forceDelete()`

## 11. README

- [x] `README.md` — seção nova, Lixeira, roteiro (F-69, F-70)
- [x] `README.en.md` — espelho

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse` nos arquivos tocados
- [x] testes novos verdes
- [x] regressão (14 arquivos listados no PRD) verde
- [x] `--testsuite=Kit` inteira
- [x] duas mutações registradas abaixo
- [x] mergeado na `main` via `feat/status-e-exclusao-logica-de-usuario`

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

- A coluna de situação ficou na trait `SituacaoDaConta` e foi reutilizada em `/admin` e `/app`, em vez de duplicada. `AprovacaoDeCadastro` perdeu `colunaDeSituacao()` sem arrependimento.
- Restauração via `TrashedFilter`/`RestoreAction` no `/admin` e via `RevivePlugin` no `/infra` coexistem: o RQ-11 pedia "pelo menos um", e o `/infra` é o lugar correto por expor lixo da instalação inteira.

## Notas de Implementação

- `ativo` e `deleted_at` são estado de fronteira: fora do `$fillable`, só por `forceFill()` nos métodos `desativar()`/`reativar()`.
- A tela de aviso (`conta-indisponivel`) só renderiza se houver flash na sessão; visita direta redireciona para `/`.
- `motivoParaNaoDesativar()` é a única fonte da regra: `desativar()` lança, e a Action da tabela se esconde pelo mesmo valor.
- `ehOUltimoMasterGlobalAtivo()` protege o último `master_global` ativo contra desativação acidental.
- Log de tentativas de acesso em conta inativa/excluída vai para o canal `autenticacao`.

## Mutações executadas

- M1: remoção da guarda de `canAccessPanel()` para inativo/excluído → testes de login + social quebram.
- M2: remoção do `Timebox` no interceptor da senha → testes de timing falham.

## Retrospectiva

- Feature mergeada e testada. A maior tensão foi manter `User::canAccessPanel()` como única decisão de acesso enquanto as telas de login/social apenas explicam a negativa.
