# Progresso — Travas de escalada na tela de papéis e no login social

> Wiki de **correção**, nascida da rodada de auditoria `filament-security-audit` do Filament Blueprint em 2026-08-30.
> Toca infra compartilhada (`AdministradorDaInstalacao`, `RolePolicy`) → regressão obrigatória.

## 1. `RolePolicy` guarda o registro do papel super-admin (RQ-02, RQ-03)

- [ ] `AdministradorDaInstalacao::papelEditavelPor(Role, ?Authenticatable): bool`
- [ ] `RolePolicy::update()` consulta a guarda
- [ ] `RolePolicy::delete()` consulta a guarda
- [ ] `RolePolicy::forceDelete()` consulta a guarda
- [ ] `RolePolicy::restore()` consulta a guarda

## 2. Nome do papel super-admin é reservado (RQ-01)

- [ ] `AdministradorDaInstalacao::regraDeNomeDePapel()`
- [ ] `->rule()` no `TextInput::make('name')` do `RoleResource`
- [ ] Log `warning` no channel `autenticacao` quando a regra reprova

## 3. Concessão limitada aos painéis do operador (RQ-04, RQ-05)

- [ ] `AdministradorDaInstalacao::paineisDoOperador()`
- [ ] `recortarConcessao()` filtra por painel (closure agrupada, sem `orWhere` de topo)
- [ ] `regraDeConcessao()` filtra por painel
- [ ] Mensagem do log de `gravarPapeis()` atualizada (o descarte agora tem duas causas)
- [ ] Confirmado: nenhuma das três telas de concessão mudou

## 4. Convite não é aceito na volta do provedor por conta existente (RQ-06, RQ-07, RQ-08)

- [ ] Chamada do ramo do vínculo removida
- [ ] Chamada do ramo do e-mail removida
- [ ] Método `aceitarConviteSeHouver()` removido
- [ ] Log de convite pendente não consumido
- [ ] `criarContaPorConvite()` confirmado intocado (RQ-07)
- [ ] Testes da wiki `cadastro-social-por-convite-e-organizacao` com oráculo ajustado

## 5. Link de confirmação de vínculo é de uso único (RQ-09)

- [ ] `Cache::add()` sobre o hash da assinatura em `confirmarVinculo()`
- [ ] Recusa amigável na segunda tentativa
- [ ] Log `warning` de link reutilizado

## Testes

- [ ] `04-casos-de-teste.md` derivado do `00-requisito.md` pela skill `feature-test-design` (sub-agente)
- [ ] Testes escritos e verdes
- [ ] Sem `05-casos-de-teste-browser.md` — nenhum cenário exige navegador (ver `## Superfície de UI` do PRD)

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --compact`
- [ ] Mutação manual: remover a guarda da policy → CT de RQ-02/RQ-03 reprovam
- [ ] `git commit` por bloco

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `admin` tem `Update:Role`/`Delete:Role` | `PapeisSeeder:58-59` dá a matriz inteira do painel `/admin`, e `RoleResource` é tela do `/admin` | nenhuma; confirma o achado |
| `RolePolicy` já recebe o registro | `RolePolicy.php:30,37` — `update(AuthUser, Role $role)` e `delete(AuthUser, Role $role)` ignoram `$role` | nenhuma; é o que torna a ADR-01 barata |
| `name` é texto livre | `RoleResource.php:109-116` — `unique()` + `required()` + `maxLength(255)`, nenhuma restrição de valor | nenhuma |
| os três caminhos de concessão consomem `AdministradorDaInstalacao` | `UserResource` (`gravarPapeis`), `ConviteForm` (`->rule()`), `Admin\ListConvites` (`->rule()`) | nenhuma; é o que faz o passo 3 não tocar tela |
| `aceitarConviteSeHouver()` só é chamada em ramo de conta existente | `LoginSocialController.php:189` (vínculo) e `:203` (e-mail); conta nova usa `criarContaPorConvite()` | nenhuma; confirma que remover não afeta RQ-07 |
| `papeisEmQualquerContexto()` existe | `app/Models/User.php` — `MorphToMany` sem filtro de team | nenhuma; é a fonte de `paineisDoOperador()` |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | Guarda do papel super-admin na policy em vez de `getUpdate*AuthorizationResponse()` no Resource | sim | ADR-01, passo 1 |
| 2 | Trava de painel nos dois métodos que as telas já chamam, sem tocar em tela nenhuma | sim | ADR-02, passo 3 |
| 3 | Uso único por `Cache::add()` em vez de migration + coluna | sim | ADR-04, passo 5 |
| 4 | Remover `aceitarConviteSeHouver()` inteiro em vez de reordenar + guardar — fecha F-03 e F-04 com uma deleção | sim | ADR-03, passo 4 |
| 5 | Guarda no model `Role` recusada como YAGNI (não há caminho de edição fora do Filament hoje) | sim | ADR-01, alternativa 3 |
| 6 | Channel de log novo recusado — `autenticacao` já é o channel das três telas e do login social | sim | `01` → Channel de Log |

## Blockers

- Nenhum.

## Desvios do Plano

<!-- preencher na implementação -->

## Notas de Implementação

<!-- preencher na implementação -->

## Retrospectiva

<!-- preencher no fim -->
