# Decisões Arquiteturais — O administrador da organização não alcança quem governa a instalação

## ADR-01: "Governa a instalação" é ter papel de painel sem tenancy no contexto global — não é uma lista de nomes

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O requisito nomeia `master_global`; o solicitante ampliou para "qualquer papel de instalação". O kit
já tem a definição operacional disso em dois lugares: `User::canAccessPanel()` (papel de painel sem
tenancy só vale no contexto global) e `PapeisSeeder` (`admin` → `/admin`, `infra` → `/infra`,
`master_global` sem painel). Escrever `['master_global', 'admin', 'infra']` numa constante criaria uma
terceira definição, que envelhece sozinha no dia em que alguém cria um quarto painel de instalação.

### Decisão

`User::governaAInstalacao()` e o scope `queNaoGovernamAInstalacao()` perguntam a mesma coisa que
`canAccessPanel()` responde para `/admin` e `/infra`: existe papel deste usuário com `roles.painel`
nulo ou ≠ `app`, atribuído em `model_has_roles.team_id = Tenant::CONTEXTO_GLOBAL`? Sobre a relação
`papeisEmQualquerContexto()` — a mesma de `isMasterGlobal()` —, porque a `roles()` do spatie filtra
pelo team do request e dentro do `/app` esse team é a organização.

**Consequência aceita**: quem é `admin_app` numa organização **e** `admin` da instalação some da
lista de usuários daquela organização no `/app` — inclusive para si mesmo. Quem governa a instalação
se administra pelo `/admin`, onde o `master_global` e os demais aparecem.

### Alternativas Consideradas

1. **Só `master_global`** (`isMasterGlobal()` direto) — atende o texto literal e não a decisão do
   solicitante; e deixa o `admin_app` com poder de trocar a senha de um `admin` da instalação, que
   entra no `/admin` e edita todo mundo. A escalada seria de dois passos em vez de um.
2. **Lista de nomes de papel** — terceira definição do que já está em `canAccessPanel()` e no
   seeder; passa a exigir manutenção em três lugares.
3. **Qualquer atribuição de papel de instalação, em qualquer contexto** — um papel `admin` atribuído
   dentro de uma organização (o helper `duasOrganizacoes()` faz isso) não governa nada, porque
   `canAccessPanel()` exige contexto global. Esconder essa pessoa seria esconder alguém que o
   `admin_app` legitimamente administra.

### Consequências

- **Positivas**: uma definição, a mesma do acesso a painel; painel de instalação novo entra na regra
  sem ninguém lembrar.
- **Negativas**: o predicado depende de `roles.painel` estar preenchido corretamente — que já é a
  regra do kit (`.ai/rules/filament.md`, "Papel novo precisa declarar o painel").

### Referências

- `app/Models/User.php` — `canAccessPanel()`, `isMasterGlobal()`, `temPapelOnde()`, `papeisEmQualquerContexto()`, `contextoGlobal()`
- `database/seeders/PapeisSeeder.php:24-80`

---

## ADR-02: Duas camadas — a query esconde, a resposta de autorização recusa

**Status**: Aceita
**Data**: 2026-09-02
**Refina**: ADR-08 de `wikis/specs/main/admin-da-organizacao/` (exclusão negada por resposta de autorização, por painel)

### Contexto

"Não vê" e "não altera" são cláusulas distintas (RQ-02 e RQ-03), e um mecanismo só não prova as
duas. A query em `getEloquentQuery()` alimenta os quatro consumidores (listagem, route binding, busca,
badge) e resolve "não vê" — e, por tabela, "não altera pela tela", porque o route binding devolve
404. Mas a query é falha de um só ponto: um `resolveRecord()` sobrescrito, uma action que receba um
`User` de outra origem, um `EditUser::mount()` chamado com model já carregado passam por fora dela.

O kit já decidiu isso para a exclusão: `getDeleteAuthorizationResponse()` nega **por painel**, e a
wiki das travas mediu que `canDelete()` não era consultado pelo framework — quem decide lê a
**resposta**.

### Decisão

1. `UserResource::getEloquentQuery()` (app) aplica o recorte por subquery:
   `->whereIn(users.id, User::queNaoGovernamAInstalacao()->select('id'))`. **Não encadeado**: o pai
   devolve `Builder<Model>` e o scope só existe em `User` — o PHPStan (level 7) reprovava com "Call to
   an undefined method", e trocar o tipo no retorno não é opção porque o template do Builder não é
   covariante. A definição continua única, no model. (Ajuste feito no merge do PR #54, que chegou com
   o gate `qualidade` vermelho por isso; `whereKey(Builder)` foi tentado e vira `=`, não `IN`.)
2. `UserResource::getEditAuthorizationResponse(Model $record)` (app) devolve `Response::deny()` com
   motivo quando `$record->governaAInstalacao()`, com `warning` no channel `autenticacao` — e só aí,
   porque só se chega a esse ponto se a primeira camada foi contornada.

Na **policy**, nada: `UserPolicy::update()` é global, e no `/admin` editar o `master_global` é
legítimo. A assimetria é por painel, e o lugar dela é o resource do painel.

### Alternativas Consideradas

1. **Só a query** — cobre a tela de hoje. Não cobre o caminho que ainda não existe, e barreira de
   segurança que depende de ninguém escrever código novo não é barreira.
2. **Só a resposta de autorização** — o `master_global` continuaria **aparecendo** na lista, na
   busca e no badge, com a ação de editar negada. Contradiz "não pode NUNCA ver" e a resposta do
   solicitante ("some inteiramente").
3. **Na `UserPolicy`, com `Filament::getCurrentPanel()`** — policy que pergunta em que painel está é
   policy que mente fora de request (job, comando), onde `getCurrentPanel()` é null. E quebra a
   regra do kit de que a assimetria por painel mora no resource.
4. **`canEdit()` em vez de `getEditAuthorizationResponse()`** — foi exatamente o erro medido com
   `canDelete()`: o framework lê a resposta, não o booleano (F-01 da auditoria do Blueprint).

### Consequências

- **Positivas**: as duas cláusulas têm cada uma o seu mecanismo, e o segundo é testável **direto**
  (`UserResource::getEditAuthorizationResponse($masterGlobal)->denied()`), sem depender da tela.
- **Negativas**: dois pontos para manter em vez de um. É o custo declarado de defesa em profundidade
  para a conta mais poderosa da instalação.

### Referências

- `app/Filament/App/Resources/Users/UserResource.php` — `getDeleteAuthorizationResponse()` e o
  docblock "Por que aqui, e não em `canDelete()`"
- `vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php:89,149,209`

---

## ADR-03: A coluna de team é qualificada no `whereDoesntHave`, não `wherePivot()`

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

`temPapelOnde()` documenta a armadilha: `wherePivot()` existe na **relação**, não no `Eloquent\Builder`
que chega ao closure de `whereDoesntHave()`/`whereHas()`. Um `->when()` encaminhado ao builder já fez
`isMasterGlobal()` responder `false` com a pivot correta no banco.

### Decisão

O helper de condições que o predicado e o scope compartilham aplica o contexto como
`where(Config::modelHasRolesTable().'.'.Config::teamForeignKey(), Tenant::CONTEXTO_GLOBAL)` — coluna
qualificada, válida tanto na relação quanto no builder do closure. Sem teams (`contextoGlobal()` nulo)
a condição não entra, como em `temPapelOnde()`.

### Alternativas Consideradas

1. **Dois textos de condição, um com `wherePivot()` para o predicado e outro qualificado para o
   scope** — é a divergência que o risco 1 do plano existe para evitar.
2. **Subquery por `whereIn('id', User::whereHas(...))`** — funciona e custa uma query a mais para
   dizer o mesmo.

### Consequências

- **Positivas**: um helper, duas chamadas, um teste que compara as duas formas sobre a mesma fixture.
- **Negativas**: depende do nome da tabela e da coluna via `Config` do spatie — que é exatamente de
  onde `papeisEmQualquerContexto()` já os tira.

### Referências

- `app/Models/User.php` — `temPapelOnde()` (o comentário "Nada de `->when()` aqui") e `colunaDeTeam()`
