# Progresso — O administrador da organização não alcança quem governa a instalação

> Tipo **evolução** das wikis `admin-da-organizacao` e `travas-de-escalada-de-papeis`; toca
> `App\Models\User` (infra compartilhada) → regressão obrigatória sobre `AdminDaOrganizacaoTest`,
> `EscopoFailClosedTest` e a suíte `Tenancy`.

## 1. `User::governaAInstalacao()` e o scope `queNaoGovernamAInstalacao()`

- [x] Predicado e scope sobre `papeisEmQualquerContexto()`, com o helper privado
      `restringirAosPapeisDeInstalacao()` compartilhado (`painel` nulo ou ≠ `app`, `guard_name`;
      `team_id = CONTEXTO_GLOBAL` quando há teams)
- [x] Coluna de team **qualificada** (ADR-03); `painel` e `guard_name` também, via `qualifyColumn()`
- [x] Docblock com a definição única e a referência a `canAccessPanel()`

## 2. Recorte da query do `/app`

- [x] `UserResource::getEloquentQuery()` (app) → `->queNaoGovernamAInstalacao()` no ramo com tenant
- [x] Docblock: os quatro consumidores e por que a query não basta (passo 3)

## 3. Recusa de edição independente da query

- [x] `UserResource::getEditAuthorizationResponse()` (app) nega quando o alvo governa a instalação
- [x] `warning` no channel `autenticacao` com `alvo_id`, `executor_id`, `tenant_id`, `painel`, `motivo`
- [x] Confirmado no vendor: `EditRecord::mount()` (`:100`) e `Page.php:314` lêem a resposta

## 4. Testes

- [x] `tests/Tenancy/FronteiraDoAdminAppTest.php` — CT-01 a CT-11: **22 casos (com datasets), 55
      asserções, verdes**
- [x] **Gate de falsificação** (2026-09-02): com `User.php` e `UserResource.php` em `git stash`, o
      arquivo novo fica **12 de 22 vermelho** — os 9 verdes são os controles (Beto, Rui, Leo, Nina
      permitidos) e a regressão (CT-06, CT-09, CT-11). Restaurado com `stash pop`.
- [x] Regressão: `AdminDaOrganizacaoTest` + `EscopoFailClosedTest` + o arquivo novo = **52 testes,
      228 asserções, verdes**
- [x] Revisão adversarial (sub-agente, `00` + `04`, sem PRD): **19 achados, todos fechados** na v2 do
      `04` — 5 novos cenários (CT-07…CT-11), regra R5 nova (o recorte não é global scope), 9 mutantes
      novos (M13–M21), fixture de 7 → 11 pessoas

## 5. CHANGELOG e documentação

- [x] `CHANGELOG.md` → `[Unreleased]` → `### Segurança`
- [x] `docs/pt/recursos/multi-tenancy.md:41` e `docs/en/…:41` — uma frase na descrição do `admin_app`

## Verificação Final

- [x] Revisão Ponytail no diff: nada a cortar. Um predicado, um scope, um helper privado de
      condições (é o que impede duas definições — ADR-03), uma linha na query, uma resposta de
      autorização. `net: 0`
- [x] `vendor/bin/pint --dirty --format agent` — passed
- [x] `vendor/bin/pest --no-tia tests/Tenancy/FronteiraDoAdminAppTest.php --compact` — 22 verdes
- [x] `vendor/bin/pest --no-tia tests/Tenancy/AdminDaOrganizacaoTest.php tests/Tenancy/EscopoFailClosedTest.php --compact` — verdes (52 com o arquivo novo)
- [x] `vendor/bin/pest --no-tia --parallel --testsuite=Tenancy --compact` — **275 testes, 1.079 asserções, verdes** (4,9 min)
- [ ] **Não feito**: instalação nova com tenancy. `kit:install` sem terminal interativo instala com
      os padrões (tenancy desligada) e `kit:tenancy` é interativo; montar isso custa mais do que
      prova, porque os 22 casos de `tests/Tenancy` já atravessam as rotas reais do painel
      (`GET /app/acme/users/{uuid}/edit` → 404) e a tabela real (`ListUsers` + `loadTable()`).
      Fica para a validação da release, junto com as outras features da versão.
- [x] `git commit` — 3 commits na `feat/admin-app-nao-alcanca-master-global`

## Auditoria Pré-Implementação

### Captura do requisito

- Texto escrito (fidelidade alta). Duas ampliações decididas pelo solicitante antes da escrita:
  **qualquer** papel de instalação (não só `master_global`) e **sumir inteiramente** (não "aparecer
  bloqueado"). Registradas verbatim no `00`.
- Boost `search-docs` consultado (Filament 5: `getEloquentQuery()` alimenta todo o resource;
  autorização de edição via policy/resposta; Laravel 13: `wherePivot` só na relação). Vendor
  confirmado: `HasAuthorization.php:89,149,209`.

### Medições que antecederam a wiki (2026-09-02)

| O que | Resultado |
|---|---|
| Recorte por organização no `/app` | **já existe** (`getEloquentQuery()` + `whereHas('tenants')`, fecha sem tenant), com 2 testes |
| Barreira sobre **quem** é o alvo | **não existe** — `EditAction` aberta para qualquer usuário listado |
| `master_global` vinculado a organizações | **em toda instalação**: `TenantsSeeder.php:32` |
| Definição operacional de "papel de instalação" | já existe em `canAccessPanel()`: painel ≠ `app`, contexto global |
| Quem lê `getEditAuthorizationResponse()` no vendor | `EditRecord::mount()` via `canEdit()`; `Page.php:314` direto para a `EditAction` da tabela |

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `EditAction` da tabela lê a resposta de autorização, não `canEdit()` | `vendor/filament/filament/src/Resources/Pages/Page.php:314`: `$action instanceof EditAction => static::getResource()::getEditAuthorizationResponse(...)` — e `EditRecord.php:100` usa `canEdit()`, que **também** lê a resposta (`HasAuthorization.php:149`) | premissa confirmada; passo 3 marcado como verificado |
| Existe `tests/Kit/PerfisTest.php` para regressão | **não existe** com esse nome | removido da Verificação Final; a regressão de papéis é `AdminDaOrganizacaoTest` + suíte `Tenancy` |
| A descrição do `admin_app` nos docs fica em `multi-tenancy.md` | `docs/pt/recursos/multi-tenancy.md:38-41` e `en:38-41` — tabela de personas e o parágrafo "administra **uma** organização sem administrar o sistema" | passo 5 aponta a linha exata |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `delete:` `tests/Kit/PerfisTest.php` na Verificação Final — arquivo não existe | sim | `01`, Natureza e Verificação Final |
| 2 | `yagni:` `getViewAuthorizationResponse()` cogitado no passo 3 — não há página `view` no resource | já estava fora; a Filosofia do `01` registra o motivo | `01`, passo 3 |
| 3 | `shrink:` cogitado colapsar CT-04 e CT-05 (os dois percorrem a matriz) — recusado: medem coisas diferentes (resposta de autorização × igualdade predicado/scope) e matam mutantes distintos (M6–M9 × M10–M11) | recusada | `04` |

`net: -1 linha`. Plano já mínimo: um predicado, um scope, uma linha na query, uma resposta de
autorização, um arquivo de teste.

## Blockers

- Nenhum.

## Desvios do Plano

| Onde | O plano dizia | O que foi feito | Por quê |
|---|---|---|---|
| Passo 1, helper de condições | `painel` sem qualificar | `qualifyColumn('painel')` e `qualifyColumn('guard_name')` | dentro do `whereDoesntHave` a query tem `roles` **e** `model_has_roles` no `FROM`; qualificar tudo custa nada e tira a ambiguidade do caminho |
| Passo 1, helper | só `painel` + contexto | acrescentou `guard_name = web` | é o que `temPapelOnde()` já faz; papel de outro guard não governa este painel |
| `04`, CT-10 | `expect(...)->toThrow(closure)` | `try/catch` com as quatro exceções aceitas | o `toThrow(callable)` do Pest tratou a assinatura como mensagem esperada e reprovou com "contains Throwable"; `try/catch` é explícito e legível |
| `04`, CT-01/CT-08 | `noPainelDa()` bastava | um `GET /app/acme/users` real **antes** do `Livewire::test()` | a tabela usa o macro `simpleLightbox()`, registrado no boot do plugin; `noPainelBootado('app')` sem rota derruba o Breezy (`route()->parameter()` em null). Um request real boota pelo middleware, com rota |

## Notas de Implementação

- **`noPainelBootado()` não serve para o painel `/app`**: o `BreezyCore::boot()` lê
  `route()->parameter()` e, sem request, `route()` é null. `AdminDaOrganizacaoTest` nunca tropeçou
  nisso porque os casos HTTP anteriores bootam o painel antes dos casos Livewire — dependência de
  ordem invisível. O arquivo novo é o primeiro em que um caso Livewire do `/app` roda **isolado**.
  Candidato a nota na rule `testes.md` (step 9).
- **O gate de falsificação com `git stash` é barato e decisivo**: 100 s para provar que 12 dos 22
  casos medem a feature e não o ambiente. Vale como padrão para feature de autorização.
- **`getGlobalSearchResults()` devolve o `title` como `name` do usuário** — todos "Usuário" na
  fixture (`usuario()` cria com nome fixo). Por isso CT-03 busca pelo **e-mail** e assere a
  contagem de resultados via o título repetido; se a fixture ganhar nomes, o dataset melhora.

## Retrospectiva

- **Funcionou**: a revisão adversarial por sub-agente isolado. 19 achados, 5 dos quais eram
  implementações plausíveis que passariam inteiras — inclusive a mais "elegante" (global scope no
  `User`), que teria apagado o `master_global` do `/admin`. Sem PRD na entrada, ele não herdou os
  meus pontos cegos.
- **Funcionou**: definir "governa a instalação" pela **mesma** pergunta de `canAccessPanel()`, e não
  por nomes. O papel `auditor` criado no teste (Aldo) prova que a regra alcança o que ainda não existe.
- **Faltou no plano**: prever o boot do painel para o primeiro caso Livewire do arquivo. A rule
  `testes.md` fala de `noPainelBootado()` como solução, e para o `/app` ela **não** é.
- **Faltou no plano**: uma fixture com nomes distintos. `usuario()` fixa `name = 'Usuário'`, e a
  busca global só se deixou asserir pelo e-mail.
