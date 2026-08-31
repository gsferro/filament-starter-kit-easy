# Progresso — Travas de escalada na tela de papéis e no login social

> Wiki de **correção**, nascida da rodada de auditoria `filament-security-audit` do Filament Blueprint em 2026-08-30.
> Toca infra compartilhada (`AdministradorDaInstalacao`, `RolePolicy`) → regressão obrigatória.

## 1. `RolePolicy` guarda o registro do papel super-admin (RQ-02, RQ-03)

- [x] `AdministradorDaInstalacao::papelEditavelPor(Role, Authenticatable): bool` — operador **obrigatório** (ponytail-review)
- [x] `RolePolicy::update()` consulta a guarda
- [x] `RolePolicy::delete()` consulta a guarda
- [x] `RolePolicy::forceDelete()` consulta a guarda
- [x] `RolePolicy::restore()` consulta a guarda

## 2. Nome do papel super-admin é reservado (RQ-01)

- [x] `AdministradorDaInstalacao::regraDeNomeDePapel()`
- [x] `->rule()` no `TextInput::make('name')` do `RoleResource`
- [x] Log `warning` no channel `autenticacao` quando a regra reprova

## 3. Concessão limitada aos painéis do operador (RQ-04, RQ-05)

- [x] `AdministradorDaInstalacao::paineisAoAlcance()` — renomeado; inclui o painel default
- [x] `recortarConcessao()` filtra por painel (closure agrupada, sem `orWhere` de topo)
- [x] `regraDeConcessao()` filtra por painel
- [x] Mensagem do log de `gravarPapeis()` atualizada (o descarte agora tem duas causas)
- [x] Confirmado: nenhuma das três telas de concessão mudou

## 4. Convite não é aceito na volta do provedor por conta existente (RQ-06, RQ-07, RQ-08)

- [x] Chamada do ramo do vínculo removida
- [x] Chamada do ramo do e-mail removida
- [x] Método `aceitarConviteSeHouver()` removido
- [x] Log de convite pendente não consumido
- [x] `criarContaPorConvite()` confirmado intocado (RQ-07)
- [x] Testes da wiki `cadastro-social-por-convite-e-organizacao` com oráculo ajustado

## 5. Link de confirmação de vínculo é de uso único (RQ-09)

- [x] `Cache::add()` sobre o hash da assinatura em `confirmarVinculo()`
- [x] Recusa amigável na segunda tentativa
- [x] Log `warning` de link reutilizado

## Testes

- [x] `04-casos-de-teste.md` derivado do `00-requisito.md` pela skill `feature-test-design` (sub-agente)
- [x] Testes escritos e verdes
- [x] Sem `05-casos-de-teste-browser.md` — nenhum cenário exige navegador (ver `## Superfície de UI` do PRD)

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check`
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

- **RQ-04 mudou de critério antes de virar código.** A letra da decisão do solicitante ("só concede
  papel de painel que ele próprio acessa") contradizia a justificativa dela ("admin continua criando
  papel de /app"): `User::canAccessPanel()` exige `temPapelDoPainel()`, e o papel `admin` tem
  `painel = 'admin'`. Critério final em Q1 do `00-requisito.md` e na ADR-02. Descoberto pela
  `feature-test-design`, não por mim — é o retorno de derivar o teste a partir do requisito.
- **`paineisDoOperador()` virou `paineisAoAlcance()`**, e inclui o painel `->default()`.
- **`recortarConcessao()` ganhou o parâmetro `$alvo`** — não estava no plano. Ver Notas.
- **`DeleteBulkAction` precisou de `authorizeIndividualRecords('delete')`** — não estava no plano.
  Ver Notas.
- **CT-24 e CT-26 não viraram teste**, e CT-17/CT-19 não precisaram: motivo de cada um na seção
  `## Cenários não implementados, com o motivo` do `04`.

## Notas de Implementação

- **`->rule()` do Filament avalia a closure, não a executa como regra.** Passar
  `->rule(AdministradorDaInstalacao::regraDeNomeDePapel())` estoura
  `"[$atributo] was unresolvable"` (`vendor/filament/support/src/Concerns/EvaluatesClosures.php:102`):
  o Filament injeta parâmetro por **nome**, e os nomes da regra do Laravel (`$atributo`, `$valor`,
  `$falha`) não existem no vocabulário dele. O certo é `->rule(fn (): Closure => …)` — a closure
  externa devolve a regra. Quinze casos vermelhos de uma vez apontaram isso; o plano já dizia
  `fn (): Closure` e eu implementei sem o embrulho.
- **A policy do registro não fecha a exclusão em massa.** `DeleteBulkAction` pergunta só
  `deleteAny` e nunca consulta a policy por registro sem `authorizeIndividualRecords()`
  (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:252-266`). O `master_global` saía na
  seleção em massa com `RolePolicy::delete()` fechado. **Vale para toda BulkAction do kit** — esta
  wiki fechou só a de papéis, que é a que ela cobre. Candidato a rule de projeto.
- **Recorte de concessão não pode recortar o que a pessoa JÁ tem.** Sem o `$alvo`, a ficha de quem
  tem `infra` deixava de salvar: o papel não estava nas opções, e o `in` implícito do Select
  reprovava com "não contém um valor válido". Pior, se tivesse passado, o `syncRoles()` de
  `gravarPapeis()` **revogaria** o papel alheio a cada Salvar — que é a "escalada por subtração"
  que a revisão adversarial do `04` antecipou (CT-23). O recorte agora é sobre o que se
  **acrescenta**.
- **Duas falhas do baseline eram do trabalho anterior desta família**, não desta wiki:
  `tests/Kit/ImportExportTest.php` chamava `usuarioDoKit('admin')` sem semear os papéis, e o
  `ImportadorDoKit` passou a consultar a policy por linha. Corrigidas junto.

## Retrospectiva

- **Funcionou**: derivar o teste do requisito, por outro agente, sem acesso ao meu plano. Ele achou
  a contradição da RQ-04 (Q1) antes de qualquer código existir, e antecipou a escalada por subtração
  que só apareceria em produção, no dia em que um `admin` salvasse a ficha de alguém com `infra`.
- **Funcionou**: a ambiguidade RQ-04 da wiki `cadastro-social-por-convite-e-organizacao` já tinha o
  "Se negado" escrito. A auditoria negou a premissa meses depois, e não houve discussão sobre o que
  fazer — estava decidido.
- **Faltou**: eu implementei `->rule()` sem o embrulho que o **meu próprio plano** especificava.
  Quinze casos vermelhos para descobrir. Ler o passo antes de escrever a linha teria custado
  segundos.
- **Faltou no plano**: prever que uma trava de concessão precisa distinguir **acrescentar** de
  **manter**. O plano falava só em recortar, e recortar sozinho quebra a edição de quem já tem o
  papel.
