# Plano de Ação — O badge de papel reflete a organização ativa

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/main/perfil-e-acesso-ao-painel/` — é ela que criou `papelDoPainel()`, `papeisEmQualquerContexto()` e o badge
- **Motivo**: com multi-organização, um mesmo usuário tem papéis diferentes do painel `app` em organizações diferentes. O badge mostra o primeiro do banco, não o da organização aberta.
- **Toca infra compartilhada?**: **sim** — `User::papelDoPainel()` é consultado pela view do badge e tem suíte própria nas duas suítes (`tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` e `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`). Regressão obrigatória nas duas.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Badge mostra o papel da organização ativa | 1, 2 | |
| RQ-02 | Vale com mais de uma organização, papéis diferentes | 1 | é o caso que define o comportamento |
| RQ-03 | Trocar de organização troca o badge | 2 | consequência de ler `Filament::getTenant()` a cada render |
| RQ-04 | `/admin` e `/infra` sem mudança | 1 | sem organização corrente, o filtro não se aplica |
| RQ-05 | Acesso ao painel não muda | — | garantido por **não tocar** em `canAccessPanel()`, `temPapelDoPainel()` nem `isMasterGlobal()`; o passo 4 é a regressão que prova |

## Objetivo

Fazer o badge do cabeçalho do menu do usuário dizer a verdade quando a pessoa pertence a mais de uma organização: o papel exibido passa a ser o que ela tem **na organização aberta**, não o primeiro que o banco devolver.

A mudança é de **exibição**. As perguntas de acesso — quem entra em qual painel — continuam independentes da organização aberta, que é a decisão da wiki ancestral e permanece válida.

## Contexto

`User::papelDoPainel()` (`app/Models/User.php:398`) consulta `papeisEmQualquerContexto()`, a relação **sem** o `wherePivot(team_id)` que o spatie aplica com `permission.teams` ligado, e resolve com `->first()`. Sem ordenação declarada, o primeiro é o de menor `id`.

Com um usuário que é `panel_user` na Acme e `admin_app` na Globex — os dois com `roles.painel = 'app'` —, o badge mostra o mesmo papel nas duas organizações. Foi o que a instalação `v0223-tenancy` expôs.

O mecanismo de filtrar por organização **já existe no model**: `temPapelOnde()` (`:privado`) aplica `wherePivot($this->colunaDeTeam(), $contexto)` quando recebe contexto, e é o que `temPapelDoPainel()` usa. `papelDoPainel()` nunca recebeu esse parâmetro.

## Análise dos Arquivos Existentes

### `app/Models/User.php` — `papelDoPainel(string $painel): ?string`

Atalho do `master_global` no topo (continua). Depois, a consulta sem contexto e `->first()`. É o único ponto a mudar no model.

### `resources/views/filament/perfil-indicator.blade.php`

Chama `papelDoPainel($painelDoBadge)` com o id do painel corrente. Já trata ausência de papel sem renderizar nada — o caminho de "sem papel nesta organização" existe e não precisa de código novo.

### `app/Support/ContextoDePapeis.php`

**Não será usado aqui.** Ele fixa o `PermissionRegistrar` global para operações de **escrita** e tem `finally` + `unsetRelation`. Para uma leitura, `wherePivot` explícito é mais barato e não deixa efeito colateral no request — ver ADR-02.

## Autorização

Nenhuma mudança. O badge é exibição; quem decide acesso é `canAccessPanel()`, intocado (RQ-05).

## Rotas

Nenhuma.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Badge do cabeçalho do menu do usuário | Blade em render hook do Filament | qualquer tela dos três painéis | abre o menu do usuário e lê o papel | Não |

**Gate de CT-B**: o badge é HTML renderizado no servidor — nenhum cenário afirma sobre JavaScript, console, tema, acessibilidade ou layout. O que se afirma é **qual texto aparece**, com oráculo no banco. **Sem CT-B**; a regra vive em `User::papelDoPainel()` e é testável direto no model, como a suíte ancestral já faz.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é tocada.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`** — o caso `acha o papel mesmo fora do contexto de organização em que foi atribuído` **muda de oráculo**. Ele afirma hoje que o papel aparece mesmo com outra organização aberta; passa a afirmar o contrário para o badge. É mudança de decisão, não teste quebrado, e vai declarada nos Desvios.
- **`tests/Kit/CabecalhoDoMenuDoUsuarioTest.php`** — suíte single-tenant, sem organização. Não deve mudar: é a prova de que RQ-04 vale.
- **`canAccessPanel()` / `temPapelDoPainel()` / `isMasterGlobal()`** — intocados. A regressão do passo 4 é o que sustenta a afirmação.

## Rollback

`git revert`. Sem migration, sem dado, sem config.

## Dependências

Nenhuma.

## Riscos

- **Sumir badge de quem tinha** — se a pessoa tem papel do painel em outra organização e nenhum na ativa, o badge some (premissa declarada no `00`). É o comportamento pedido, mas é visível: o passo 3 documenta no README.
- **`getTenant()` nulo no `/app`** — na tela de escolha de organização não há tenant. Tratado como ausência de papel, mesma regra do fail-closed que o kit já usa.
- **Painel sem tenancy que ganhe tenancy no futuro** — o filtro passa a valer sozinho, porque a condição é "há organização corrente?", não uma lista de painéis.

## Channel de Log da Feature

**Nenhum log e nenhum channel novo.** O badge é leitura de exibição, executada a cada render de página: logar aqui produziria uma linha por request, sem decisão de fluxo para registrar. É a mesma conclusão da wiki `abas-nas-listagens`, pelo mesmo motivo.

## Estrutura de Implementação

### 1. `papelDoPainel()` aceita o contexto da organização (RQ-01, RQ-02, RQ-04)

> Skills: `laravel-best-practices`, `eloquent-best-practices`, `ponytail`

- **Path**: `app/Models/User.php`
- Assinatura passa a `papelDoPainel(string $painel, ?int $contexto = null): ?string`.
- Quando `$contexto !== null`, aplicar `wherePivot($this->colunaDeTeam(), $contexto)` na consulta — o mesmo que `temPapelOnde()` já faz.
- O atalho do `master_global` continua **antes** de tudo: ele não depende de organização (RQ-04 e a premissa do `00`).
- Default `null` preserva todo chamador existente — é o que mantém `/admin` e `/infra` sem mudança.
- **Logs**: nenhum.

### 2. A view passa a organização corrente (RQ-01, RQ-03)

> Skills: `laravel-best-practices`

- **Path**: `resources/views/filament/perfil-indicator.blade.php`
- `$contextoDoBadge = filament()->getTenant()?->getKey();` e repassar para `papelDoPainel($painelDoBadge, $contextoDoBadge)`.
- Sem organização corrente (`/admin`, `/infra`, ou `/app` na escolha de organização), o valor é `null` e a consulta volta a ser a de hoje — que é o comportamento correto ali.
- A troca de organização recarrega a página, então o badge acompanha sem código extra (RQ-03).
- **Logs**: nenhum.

### 3. README (RQ-01)

> Skills: nenhuma

- **Path**: `README.md` e `README.en.md`, na seção que descreve papéis e painéis.
- Uma frase: o badge do menu mostra o papel **da organização aberta**; sem papel nela, não há badge. É comportamento observável que muda, e quem administra precisa saber antes de estranhar.

### 4. Verificação e regressão

- `vendor/bin/pint --dirty --format agent`
- `composer types:check`
- `php artisan test tests/Kit/CabecalhoDoMenuDoUsuarioTest.php tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php --compact` — as duas suítes do badge
- `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — a regressão que prova RQ-05

## Filosofia de Implementação

> **Ponytail em `full`.** O que a escada decidiu:
> 1. **Reutilizar o mecanismo que já existe** — `wherePivot` + `colunaDeTeam()`, o mesmo de `temPapelOnde()`. Nada de `ContextoDePapeis` (escrita) nem de query nova.
> 2. **Parâmetro opcional em vez de método novo** — `papelDoPainelNaOrganizacao()` seria um segundo método com 90% do corpo igual, e todo chamador teria de escolher entre os dois.
> 3. **Nenhum log, nenhum channel** — não há decisão de fluxo a registrar.
> 4. **Sem CT-B** — o badge é HTML de servidor; o oráculo é o texto e o banco.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05-*-browser.md` — ver o gate na `## Superfície de UI`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
- [ ] Conferência na instalação `TESTES KIT/v0223-tenancy`, com o usuário do relato: badge diferente em cada organização

## Commits

- `🐛 fix(perfil): o badge de papel reflete a organização aberta`
- `📝 docs(readme): o badge mostra o papel da organização ativa`
