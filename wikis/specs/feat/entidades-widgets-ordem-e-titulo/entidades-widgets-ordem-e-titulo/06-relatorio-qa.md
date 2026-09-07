# Relatório de QA — Listagem de entidades: ordem dos widgets e título capitalizado

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **padrão** (natureza `ajuste`, UI presente sem JS, domínio comum)
> Natureza da wiki: ajuste · Regressão: **sim**

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 3 · Cosmético: 0
- Ambiente: app **não servido** (validação por Pest e leitura de vendor) · Pest 5 · Playwright MCP: não usado (sem CT-B; gate do `05` já recusou browser) · Boost MCP: não usado

## Achados

### QA-01 — o PRD afirma que nenhum código do app consome `getModelLabel()`, e um consome · Minor · destino 1

- **Dimensão**: L (L3 — PRD × código)
- **Relacionado a**: `01-plano-acao.md:40` (seção Contexto), ADR-01 → Consequências → Riscos
- **Esperado**: o PRD diz *"nenhum código do app consome `TenantResource::getModelLabel()`/`getPluralModelLabel()` diretamente (varredura de `app/`: zero)"*, e a ADR-01 repete a varredura como mitigação de risco.
- **Observado**: `app/Filament/Spotlight/AcoesDeCriacao.php:$rotulo:70` faz `ucfirst((string) $resource::getModelLabel())` para **todo** Resource do painel, `TenantResource` incluído — é o rótulo da ação "Criar …" do Spotlight (⌘K).
- **Repro**: `grep -rn "getModelLabel" app/ --include=*.php` → duas ocorrências fora do `TenantResource` (`Roles/RoleResource.php:655` e `Spotlight/AcoesDeCriacao.php:70`).
- **Evidência**: `app/Filament/Spotlight/AcoesDeCriacao.php:$rotulo:70`
- **Por que não é Major**: o comportamento não regride. Com o default, antes era `ucfirst('organização')` = "Criar Organização" e agora é `ucfirst('Organização')` = "Criar Organização". Para um rótulo composto configurado ("Unidade de Negócio") o Spotlight passa de "Criar Unidade de negócio" a "Criar Unidade de Negócio" — melhora, e na mesma direção do RQ-04.
- **Destino**: 1 — corrigir a afirmação no `01` e na ADR-01.
- **Ação exigida**: trocar "varredura de `app/`: zero" pela varredura real, dizendo que o único consumidor é o Spotlight e que ele fica igual ou melhor.
- **Status**: **corrigido na volta do ciclo 1** — `01-plano-acao.md:40` e `02-decisoes-arquiteturais.md` → ADR-01 → Consequências → Riscos, ambos com a marca `*(alterado em 2026-09-07 …)*`. Nenhuma linha de código ou de teste foi tocada pela correção.

### QA-03 — `KitUpdateTest` amarra a documentação do `kit:update` à seção do topo do CHANGELOG · Minor · destino 3

- **Dimensão**: J (regressão adjacente) / K (oráculo mal escopado)
- **Relacionado a**: passo 5 do PRD (`## [Unreleased]` no CHANGELOG)
- **Esperado**: o caso quer garantir que o CHANGELOG documenta o `kit:update` com a lista do destino.
- **Observado**: ele recortava **só a seção mais recente** (`tests/Kit/KitUpdateTest.php:459`) e afirmava que ela cita `kit:update` — verdadeiro só porque a v0.31.0, então no topo, era justamente essa correção. Qualquer entrada nova de CHANGELOG reprova as duas linhas de dataset, sem que nada tenha sido apagado.
- **Repro**: acrescentar `## [Unreleased]` ao `CHANGELOG.md` e rodar `php artisan test tests/Kit/KitUpdateTest.php` → 2 falhas.
- **Evidência**: saída da primeira `composer test:kit`, 2154/2156.
- **Destino**: 3 — o oráculo estava errado, não o produto.
- **Ação exigida**: afirmar sobre o CHANGELOG inteiro. **Feita**; 54/54 verdes.

### QA-02 — rótulo configurado vazio ou só com espaços produz título vazio · Minor · destino 5 (não-defeito, registrado)

- **Dimensão**: B (fronteiras)
- **Relacionado a**: RQ-04, `00` → Riscos ("rótulo configurado com espaço nas bordas ou vazio: fora de escopo")
- **Esperado**: nada — o `00` declara fora de escopo e o `01` também.
- **Observado**: `(string) config('kit.tenancy.label_plural', 'Organizações')` devolve `''` quando a chave existe com valor vazio: o default do `config()` só vale para chave **ausente**. O `<h1>` e o breadcrumb ficam vazios.
- **Repro**: `config()->set('kit.tenancy.label_plural', ''); TenantResource::getPluralModelLabel()` → `''`.
- **Por que é destino 5**: comportamento **idêntico ao anterior** — `mb_strtolower('')` também é `''`. Não é regressão desta entrega, e a barreira real está no Settings (`tests/Kit/ConfiguracoesDoKitTest.php`), como o `00` registra.
- **Ação exigida**: nenhuma nesta feature. Fica anotado para o dia em que alguém quiser fechar a fronteira no `config/kit.php`.

## Matriz de Rastreabilidade

<!-- Sem lacuna. Impressa mesmo assim porque é a prova de que a dimensão A rodou. -->

| RQ | Cláusula | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|----|----------|-----------|----|------|--------|-----------|----------|
| RQ-01 | visão geral no topo, acima da tabela | 1 | CT-01 | — | `ListTenants::getHeaderWidgets():58-63` | verde | OK |
| RQ-02 | tabela imediatamente depois da visão geral | 1 | CT-01 | — | template do vendor (`page/index.blade.php:headerWidgets:105, slot:109, footerWidgets:113`) | verde | OK |
| RQ-03 | demais widgets abaixo da tabela | 1 | CT-01 | — | `ListTenants::getFooterWidgets():68-74` | verde | OK (sob premissa do `00`) |
| RQ-04 | título com a caixa configurada | 2 | CT-02 | — | `TenantResource::getPluralModelLabel():82-85` | verde | OK |
| RQ-05 | breadcrumb com a caixa configurada | 2 | CT-03 | — | mesmo método, via `HasBreadcrumbs::getBreadcrumb():9-12` | verde | OK |
| RQ-06 | menu não regride | 2 | CT-02, 2º `Então` | — | `TenantResource::getNavigationLabel():87-90` (intacto) | verde | OK |

Nenhuma linha de "código sem `RQ`": o diff em `app/` tem exatamente duas mudanças de comportamento, ambas mapeadas acima. As demais linhas do diff são docblock, teste, docs e CHANGELOG.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | 6 `RQ`, 6 com passo, CT e código; nenhuma omissão silenciosa |
| B | Fronteiras e dados | ⚠️ | QA-02 (destino 5). Rótulo com acento e cedilha coberto por CT-02/CT-03 ("Negócio") |
| C | Matriz de permissão | ✅ | nenhuma permissão nova. `Page::footerWidgets():455-464` passa pelo mesmo `getWidgetsSchemaComponents():423` do cabeçalho, que filtra por `canView()` — o `[CT-16]` da ancestral (coluna `painel` ausente esconde só `AcessosPorPainel`) segue válido para o rodapé, e está verde |
| D | Observabilidade (log) | ⏭️ pulada | a feature declara "sem log" no `01` → Channel de Log, e o diff não tem nenhum `Log::`. Sem log, sem PII em log |
| E | Performance | ✅ | os mesmos quatro widgets, com as mesmas consultas, apenas em outra posição do template. Nenhuma query nova; nenhum widget deixou de ser lazy |
| F | UX de erro | ⏭️ pulada | o diff não tem caminho de erro: duas listas de classes e uma string de config |
| G | Tema e cor | ⏭️ pulada | o diff não toca Blade, CSS nem classe de cor — a grade do rodapé é a mesma do cabeçalho (2 colunas, default do vendor: `Page::getFooterWidgetsColumns():356-359`) |
| H | Acessibilidade | ⏭️ pulada | fora do perfil `padrão`; nenhum elemento novo, só reordenação de blocos existentes |
| I | Segurança da superfície nova | ✅ | nenhuma rota, ação ou campo novo. A barreira continua `TenantResource::canAccess()` (`ViewAny:Tenant` + `kit.tenancy.enabled`) |
| J | Regressão adjacente | ✅ | ver abaixo |
| K | Adequação da suíte | ✅ | passo estático + mutantes injetados; ver abaixo |
| L | Consistência documental | ⚠️ | QA-01. L1, L4 e L5 sem achado; L2 com 3 divergências intencionais e anotadas |

### J — Regressão

- `composer test:kit` (Kit + Tenancy em paralelo): **2156/2156 verdes, 7081 asserções** na reexecução. Na primeira execução, 2 falhas — as duas linhas de dataset de `tests/Kit/KitUpdateTest.php:459`, que recortava só a seção do topo do `CHANGELOG.md` e exigia que ela citasse `kit:update`. A seção `[Unreleased]` desta entrega empurrou a v0.31.0 (onde essa entrada mora) para baixo. **É defeito do caso, não da entrega**: o recorte reprovaria a próxima feature qualquer, e a asserção não protege o que diz proteger. Corrigido para olhar o CHANGELOG inteiro; `KitUpdateTest` 54/54 verde e a suíte reexecutada. Registrado em `03` → Desvios do Plano.
- CT da ancestral rodados por ID: `tests/Tenancy/InsightsDasOrganizacoesTest.php` inteiro (CT-06 a CT-20), 21/21 verdes — inclusive `[CT-15]` (permissão revogada) e `[CT-16]` (fonte ausente esconde só o widget dela), que são as duas asserções que o `01` marcou como as em risco na mudança de cabeçalho para rodapé.
- `--tia` **não** foi usado: a rule `.ai/rules/testes-browser.md` mediu que sem PCOV ele não termina. Divergência já declarada no `04`.
- RCRCRC nos arquivos tocados: **Recent** (`ListTenants`, tocado pela ancestral há dias) e **Repaired** (`TenantResource`, cujo rótulo minúsculo é o defeito antigo) — os dois cobertos pela suíte da ancestral e pelos CT novos. **Core / Risk / Configuration / Chronic**: o rótulo vem de `config('kit.tenancy.*')`, alimentado por Settings; `tests/Kit/ConfiguracoesDoKitTest.php` cobre esse caminho e está na `composer test:kit`.
- Comparação com `## Impacto em Features Existentes` do `01`: **bate**. As cinco consequências previstas (CT-12 vermelho até o passo 3; "Criar Organização"; breadcrumb das quatro páginas; rótulo das actions sem mudança; categoria da busca global nativa) se confirmaram, e nenhuma sexta apareceu — exceto a nuance do Spotlight, que é o QA-01 e não muda comportamento.

### K — Adequação da suíte

**Passo 1 (estático), sobre os 3 CT novos:** nenhum caso com `assertOk()` como oráculo único, nenhum `assertSee` de texto de layout, nenhuma tautologia. CT-01 usa `assertSeeHtmlInOrder` (oráculo de sequência); CT-02 combina `assertSeeInOrder` no `<title>` com `toBe` no rótulo de navegação; CT-03 usa `toBe` no primeiro item do breadcrumb. O `assertOk()` de CT-02 é acompanhante, não oráculo.

**Passo 2 (medido) — mutantes injetados manualmente, não `--mutate`:**

| Mutante | Resultado |
|---|---|
| M1 — os quatro widgets de volta no cabeçalho, sem `getFooterWidgets()` | CT-01 vermelho; os outros 4 casos verdes (discriminante) |
| M7/M8/M9 — `mb_strtolower()` de volta em `getPluralModelLabel()` | CT-02 (2 linhas) e CT-03 (2 linhas) vermelhos; só CT-01 verde |

`pest --mutate` não foi executado: o kit não tem PCOV configurado, e a mesma rule que barra o `--tia` vale aqui. Substituído pela injeção manual dos dois mutantes que o `04` declara como os principais — está em "Não Verificado" o que a medição automática cobriria além disso.

**Lacuna encontrada no desenho (não é achado, é confirmação da ADR-03):** CT-01 sozinho **não** mata o mutante "os três widgets nas duas listas ao mesmo tempo" — a sequência `visão geral → tabela → três de detalhe` continua satisfeita pelas cópias do rodapé (medido: com o mutante duplicado, 5/5 verdes). Quem o mata é o `[CT-12]` da ancestral, com `toBe` estrito nas duas listas. A ADR-03 já dizia que os dois são complementares; agora há a medição.

### L — Consistência documental, por checagem

| # | Resultado |
|---|---|
| L1 — IDs de CT | `04` declara CT-01, CT-02, CT-03; o teste declara exatamente esses três. Datasets: CT-02 tem 2 linhas e 2 `Exemplos`; CT-03 tem 2 linhas e 2 `Exemplos`. `[CT-12]` sincronizado entre `tests/Tenancy/InsightsDasOrganizacoesTest.php` e o `04` da ancestral, os dois com a marca de alteração. **Sem achado** |
| L2 — citações `arquivo:símbolo:linha` | conferência mecânica: **35/38**. As 3 divergências são intencionais e anotadas no próprio texto — duas na tabela de auditoria do `03`, que existe justamente para citar a citação errada que corrigiu; uma no Contexto do `01`, que descreve o estado de ANTES da entrega e agora traz a linha nova ao lado. **Sem achado** |
| L3 — PRD/ADR × código | **QA-01**. Os desvios reais da implementação (marcador de CT-01) foram propagados ao `01`, `02` e `04` com `*(alterado em 2026-09-07 …)*`, não só ao `03` |
| L4 — rules × diff | 9 rules com glob casando o diff, todas na tabela do `03` com evidência. Conferidas no código: `BadgeContagemNavegacao` continua no `TenantResource:54`; nenhuma função global nova em `tests/` (o marcador de CT-01 é closure local); `noPainelBootado('admin')` presente em CT-01 e CT-03; arquivo novo em `tests/Tenancy`, com `->group('kit')`; nenhuma construção da lista do Blueprint. **Sem achado** |
| L5 — docs × comportamento × rastro | `docs/pt` e `docs/en` ganharam a mesma frase, com o mesmo conteúdo; o CHANGELOG descreve a mudança de posição e a correção da caixa, e declara a consequência "Criar Organização" — que tem rastro no `00` → Ambiguidades. Nenhuma frase nova sem `RQ` ou ADR de origem. **Sem achado** |

## Débitos Aceitos

- QA-01 (Minor, destino 1): **quitado no mesmo ciclo** — a varredura declarada no `01` e na ADR-01 estava errada; o comportamento, não. Corrigido o texto nos dois arquivos.
- QA-02 (Minor, destino 5): rótulo vazio produz título vazio, igual a antes. Fora de escopo declarado no `00`.
- QA-03 (Minor, destino 3): **quitado no mesmo ciclo** — oráculo do `KitUpdateTest` reescopado; nenhuma asserção relaxada, a garantia continua a mesma sobre um recorte maior.

## Suspeitas Não Confirmadas

- Nenhuma.

## Não Verificado

- **Mutation score automático** (`pest --mutate`) — motivo: sem driver de cobertura (PCOV/Xdebug) configurado no kit, e a rule `.ai/rules/testes-browser.md` mediu que a instrumentação de cobertura não termina neste ambiente. Substituído por injeção manual de dois mutantes; o que fica de fora é a varredura exaustiva de operadores.
- **App servido** — motivo: a validação foi por Pest (que sobe a aplicação em processo) e leitura de vendor. As dimensões que exigiriam navegador (G, H) foram puladas por escopo do diff, não por falta do app.
- **Playwright MCP** — motivo: a feature não tem CT-B, e o gate do `05` recusou browser com justificativa registrada. Não há inventário de elementos a confrontar.
