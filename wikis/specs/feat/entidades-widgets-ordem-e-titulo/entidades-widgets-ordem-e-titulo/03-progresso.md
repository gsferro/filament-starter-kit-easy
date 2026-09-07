# Progresso — Listagem de entidades: ordem dos widgets e título

> Branch: `feat/entidades-widgets-ordem-e-titulo` · Wiki criada em 2026-09-06 numa worktree isolada, sem código implementado. Implementada em 2026-09-07 na mesma worktree, depois de `composer install`.

## 1. `ListTenants`: visão geral no cabeçalho, os três restantes no rodapé

- [x] `getHeaderWidgets()` devolve só `OrganizacoesStats::class` — `ListTenants.php:getHeaderWidgets():58-63`, 2026-09-07
- [x] `getFooterWidgets()` devolve os três de detalhe, na ordem atual — `ListTenants.php:getFooterWidgets():68-74`, 2026-09-07
- [x] `getHeaderWidgetsColumns()` removido (é o default) — `grep getHeaderWidgetsColumns app/Filament/…/ListTenants.php`: zero ocorrências, 2026-09-07
- [x] Docblock de `getHeaderWidgets()` explica a divisão cabeçalho/rodapé — `ListTenants.php:getHeaderWidgets():40-57`, 2026-09-07
- [x] `vendor/bin/filacheck --fix` sem pendência — "All 17 rules passed!", 2026-09-07

## 2. `TenantResource`: rótulo como configurado

- [x] `mb_strtolower()` removido de `getModelLabel()` e `getPluralModelLabel()` — `TenantResource.php:getModelLabel():77-80` e `getPluralModelLabel():82-85`, 2026-09-07
- [x] Docblock da classe (seção "Rótulo e URL configuráveis") explica por que o rótulo sai como configurado — `TenantResource.php:## Rótulo e URL configuráveis:32-47`, 2026-09-07
- [x] `getNavigationLabel()` intacto — `TenantResource.php:getNavigationLabel():87-90`, idêntico ao original no `git diff`, 2026-09-07
- [x] `vendor/bin/filacheck --fix` sem pendência — "All 17 rules passed!", 2026-09-07

## 3. Ancestral: CT-12 passa a afirmar as duas listas

- [x] `tests/Tenancy/InsightsDasOrganizacoesTest.php` `[CT-12]` afirma cabeçalho `[OrganizacoesStats]` e rodapé `[UsuariosUnicosPorOrganizacao, AcessosPorPainel, AtualizacoesDasOrganizacoes]` — verde em `php artisan test tests/Tenancy/InsightsDasOrganizacoesTest.php`, 2026-09-07
- [x] `wikis/specs/main/insights-das-organizacoes/04-casos-de-teste.md` — cenário CT-12 e Índice com a marca `*(alterado em 2026-09-06: …)*` — `04-casos-de-teste.md:[CT-12]:463-468` e Índice `:601`, 2026-09-07

## 4. Teste novo: ordem no HTML, título, breadcrumb e menu

- [x] `tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php` criado com `[CT-01]`, `[CT-02]`, `[CT-03]` — 5 casos (CT-02 e CT-03 são `Esquema` com 2 linhas cada), 5/5 verdes, 9 asserções, 2026-09-07
- [x] Marcador de CT-01 confirmado no HTML inicial — o `wire:snapshot` traz o nome do componente, que no Livewire 4 é o FQCN, com a contrabarra dobrada pelo JSON; `fi-ta-ctn` aparece entre a visão geral e os três de detalhe. Medido por dump do HTML da página. **Desvio**: `app(Widget::class)->getName()` devolve `null` — ver Desvios do Plano. 2026-09-07

## 5. Docs e CHANGELOG

- [x] `docs/pt/recursos/multi-tenancy.md` — posição dos widgets — "a visão geral acima da tabela e os três de detalhe abaixo dela", 2026-09-07
- [x] `docs/en/recursos/multi-tenancy.md` — idem em inglês — "the overview above the table and the three detail widgets below it", 2026-09-07
- [x] `CHANGELOG.md` — `## [Unreleased]` com `### Alterado` e `### Corrigido` — acima de `## [0.31.0]`, 2026-09-07

## 6. Verificação

- [x] `vendor/bin/pint --dirty --format agent` — `{"tool":"pint","result":"passed"}`, 2026-09-07
- [x] `vendor/bin/filacheck --fix` — "All 17 rules passed!" (17 regras, zero correções aplicadas), 2026-09-07
- [x] `php artisan test tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php tests/Tenancy/InsightsDasOrganizacoesTest.php --compact` — 26 testes, 26 passaram, 46 asserções, 174 s, 2026-09-07
- [x] `composer test:kit` — **2156 testes, 2156 passaram, 7081 asserções, 1777 s**. Na primeira execução foram 2154/2156: as 2 falhas eram do `KitUpdateTest` (ver Desvios do Plano), corrigidas e a suíte reexecutada inteira, 2026-09-07

## Testes

- [x] `tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php` — CT-01, CT-02 (2 linhas), CT-03 (2 linhas): 5/5 verdes, 2026-09-07
- [x] `tests/Tenancy/InsightsDasOrganizacoesTest.php` — CT-12 (ancestral, atualizado): 21/21 verdes no arquivo, 2026-09-07

### Gate de falsificabilidade (mutantes injetados e revertidos)

| Mutante | Injetado | Quem ficou vermelho |
|---|---|---|
| M1 — os quatro no cabeçalho, sem `getFooterWidgets()` | sim | CT-01, e só ele (4/5 verdes) |
| M7/M8/M9 — `mb_strtolower()` de volta em `getPluralModelLabel()` | sim | CT-02 (as 2 linhas) e CT-03 (as 2 linhas) — 1/5 verde |

Os dois mutantes foram revertidos por cópia de backup e o arquivo voltou a 5/5 verdes.

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — modo `full` ativo durante toda a implementação; o único candidato a corte era o helper `marcadorDoWidget()`, que virou closure local (menos superfície global, ver Desvios), 2026-09-07
- [x] `vendor/bin/pint --dirty --format agent` — passou, 2026-09-07
- [x] `vendor/bin/filacheck --fix` — 17/17, 2026-09-07
- [x] `php artisan test tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php tests/Tenancy/InsightsDasOrganizacoesTest.php --compact` — 26/26, 2026-09-07
- [x] `composer test:kit` — nada mais no kit quebrou — **2156/2156 verdes, 7081 asserções** na reexecução. As 2 únicas falhas da primeira rodada foram do `tests/Kit/KitUpdateTest.php`, causadas pela seção `[Unreleased]` nova (o caso recortava só o topo do CHANGELOG), 2026-09-07
- [x] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*` — 3 marcas (`01` passo 4, `02` ADR-03, `04` Setup), 2026-09-07
- [x] Citações `arquivo:símbolo:linha` reverificadas — 35/38 ok; as 3 divergências são intencionais e estão anotadas no próprio texto (duas são a citação ERRADA que a tabela de auditoria abaixo corrige; uma é o `getHeaderWidgets():54-62` de ANTES desta entrega, citado como histórico com a linha nova ao lado), 2026-09-07
- [x] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa; `[CT-12]` sincronizado com o `04` da ancestral — `04` declara CT-01, CT-02, CT-03; o teste declara exatamente esses três; CT-12 tem o mesmo texto nos dois lados, 2026-09-07
- [x] Docs pt/en e CHANGELOG reconciliados com o comportamento final — inclusive "Criar Organização" na tela de criação, consequência registrada no `00`, 2026-09-07
- [x] `git commit` — 5 commits, 2026-09-07

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — papel via `ContextoDePapeis` | `app/**` | n.a. (nenhuma atribuição de papel) | diff em `app/` toca só rótulo e lista de widgets |
| `filament.md` — Page/Widget/Action novos nascem com permissão consultada | `app/Filament/**` | n.a. (nenhum widget, page ou action novo) | os quatro seguem sob `TenantResource::canAccess()`; `Page::getWidgetsSchemaComponents()` filtra `canView()` nas duas listas |
| `filament.md` — construções que o Blueprint reprova | `app/Filament/**` | aplicada | `AderenciaAoBlueprintTest` verde em `composer test:kit`; `filacheck --fix` 17/17 |
| `filament-resources.md` — badge de contagem em todo Resource | `app/Filament/**/Resources/**` | aplicada | `BadgeContagemNavegacao` continua no `TenantResource` (`:54`); `BadgeDeNavegacaoTest` verde |
| `testes.md` — helper cruzado vive em `tests/Pest.php` | `tests/**` | aplicada | nenhuma função global nova: o marcador de CT-01 é closure dentro do próprio caso; `HelpersDeTesteTest` verde |
| `testes.md` — teste de componente de painel precisa de `noPainelBootado()` | `tests/**` | aplicada | CT-01 e CT-03 chamam `noPainelBootado('admin')`; CT-02 é HTTP e boota pelo middleware |
| `testes.md` — `admin_app` só em `tests/Tenancy` / tela exige tenancy | `tests/**` | aplicada | arquivo novo em `tests/Tenancy`; persona é `admin`, não `admin_app` |
| `testes-browser.md` — browser só para o que só ele prova | `tests/Browser*/**` | n.a. (sem CT-B) | gate registrado em `04` → `## Sem CT-B` |
| `specs.md` — justificativa de vendor com `file:line` | `wikis/specs/**` | aplicada | conferência mecânica 35/38, divergências anotadas acima |

## Quality Gate

- **Ciclo**: 1 · **Veredito**: **APROVADO COM DÉBITO** (0 Blocker, 0 Major, 3 Minor — os três quitados ou classificados como não-defeito no mesmo ciclo) · **Data**: 2026-09-07
- **Relatório**: `06-relatorio-qa.md`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| Citação `ConfiguraFilamentGlobal.php:configuraFilamentGlobal():79` | a linha 79 contém `Resource::titleCaseModelLabel(false)`; o símbolo do método externo não está na linha | símbolo trocado para `titleCaseModelLabel()` em `00`, `01`, `02` |
| Citação `Resources/Pages/Page.php:getResourceBreadcrumbs():186` | a linha 186 contém `$resource::getBreadcrumb()` | símbolo trocado para `getBreadcrumb()` em `01`, `02` |
| Paths curtos `DefaultGlobalSearchProvider.php:32`, `TenantsTable.php:66-67`, `Page.php:306-309`, `Page.php:423` | o path completo nunca tinha aparecido no documento (e há dois `Page.php` no vendor — ambíguo) | path completo + símbolo em `01`, `02` |
| 17 citações só com linha (`phpunit.xml:67`, `tests/Pest.php:78-81`, `base.blade.php:30`, `page/index.blade.php:105,109,113`, `CanBeLazy.php:9`, `HandleComponents.php:76`, docs, ancestral…) | a skill exige `arquivo:símbolo:linha`; sem símbolo não sobrevive ao deslocamento | símbolo acrescentado em todas (`KIT_TENANCY_LABEL_PLURAL`, `TenancyTestCase`, `getTitle()`, `headerWidgets`/`slot`/`footerWidgets`, `isLazy`, `snapshot`, `Insights`/`listagem`, `it()`, `declara`, `mb_strtolower()`) |
| Widgets renderizam o heading no HTML inicial (premissa implícita do teste de ordem) | `CanBeLazy::$isLazy = true` (`vendor/filament/support/src/Concerns/CanBeLazy.php:isLazy:9`): o HTML inicial é placeholder, sem heading | ADR-03 e Setup do `04` trocam o marcador para o nome Livewire do componente no `wire:snapshot` + `fi-ta-ctn`; fallback declarado |
| Filament tem `getFooterWidgets()` em `ListRecords` | vive em `Pages/Page.php:getFooterWidgets():314-317`, herdado; a doc do Filament 5 confirma para páginas de Resource | citação apontada para `Pages/Page.php`, não para `ListRecords.php` |
| `getHeaderWidgetsColumns()` de `ListTenants` é configuração necessária | devolve 2, que já é o default (`Pages/Page.php:getHeaderWidgetsColumns():306-309`); rodapé idem (`:356-359`) | passo 1 remove o método em vez de duplicá-lo para o rodapé (ADR-02) |
| Conferência mecânica final | 32/32 citações com símbolo conferidas contra a árvore principal (`vendor/` idêntico pelo `composer.lock`); 3 paths curtos (`HasLabels.php`, `ListTenants.php`, `page/index.blade.php`) aparecem após o path completo no mesmo documento, como a skill permite | — |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | CT-02 com dois oráculos para a mesma fonte (`getTitle()` no componente + `<title>` via HTTP) | sim — só o HTTP; `<h1>` e aba saem do mesmo `getTitle()` | `04`, R3; Índice; Cogitado e cortado |
| 2 | CT-04 (menu) repete o `Dado` e as partições de CT-02 | sim — fundido como segundo `Então` do `Esquema` de CT-02; M14/M15 seguem com matador | `04`, R3+R5; Índice; `01` Cobertura e passo 4; `02` ADR-01; `03` |
| 3 | Segundo `Então` de CT-01 é consequência lógica da asserção de sequência | sim — removido | `04`, CT-01 |
| 4 | `vendor/bin/filacheck --fix` repetido nos passos 1, 2, 6 e na Verificação Final | sim — só no passo 6 e na Verificação Final | `01`, passos 1 e 2 |
| 5 | "Channel de Log" com três parágrafos para dizer "sem log" | sim — uma linha | `01`, Channel de Log |

Resultado: 3 cenários em vez de 4, 2 asserções a menos, plano 4 linhas mais curto. Nenhuma sugestão recusada. Re-execução não necessária (< 3 arquivos com mudança significativa; as edições foram cortes, não acréscimos).

## Blockers

- [x] **Worktree sem `vendor/`** — resolvido em 2026-09-07 com `composer install` na própria worktree (exit 0, 206 pacotes). **Faltava também o `.env`**: sem ele o Laravel morre com "No application encryption key has been specified" em qualquer teste que renderize view. Resolvido com `cp .env.example .env && php artisan key:generate` (o `.env` é gitignorado e não entra no commit).

## Desvios do Plano

- **Marcador de CT-01 não sai de `app(Widget::class)->getName()`** (passo 4 do `01`, ADR-03, Setup do `04`). Medido: `Component::getName()` (`vendor/livewire/livewire/src/Component.php:getName():70-73`) devolve `$this->__name`, que só é preenchido no **mount** — numa instância criada por `app()` ele é `null`, e o caso morria com "Return value must be of type string, null returned". O que o HTML de fato traz é o nome do componente dentro do `wire:snapshot`, e no Livewire 4 esse nome é o **FQCN**; como o snapshot é JSON, a contrabarra chega dobrada. O marcador virou `str_replace('\\', '\\\\', Widget::class)` numa closure local — continua derivado de `::class`, sem string mágica, e sem a alternativa 2 da ADR-03 (que teria deixado a ordem provada só pela citação do template). `01`, `02` e `04` marcados com `*(alterado em 2026-09-07 …)*`.
- **Helper local em vez de função global**: o `01` previa "helper dentro do arquivo enquanto só ele usar". Uma função global de teste é justamente o que a rule `.ai/rules/testes.md` trata como acoplamento invisível; como o marcador é uma expressão de uma linha usada num único caso, virou closure dentro do `it()`. Menos superfície, mesma legibilidade.
- **`tests/Kit/KitUpdateTest.php` corrigido** (o `01` não previa tocar nesse arquivo). O caso "documenta a lista do destino … e no CHANGELOG" recortava **só a seção do topo** do `CHANGELOG.md` e exigia que ela citasse `kit:update` — o que era verdade porque a v0.31.0, no topo, trouxe justamente a correção do `kit:update`. A seção `[Unreleased]` desta entrega empurrou aquela para baixo e as duas linhas de dataset ficaram vermelhas. O recorte é o defeito: qualquer feature seguinte reprovaria, e a asserção não protege o que diz proteger. A asserção passou a olhar o CHANGELOG inteiro. Nenhuma outra asserção do arquivo mudou; 54/54 verdes.
- **Ordem dos commits**: o `01` lista o commit de docs/CHANGELOG depois do de teste; a ordem executada foi a mesma. Sem desvio.

## Notas de Implementação

- **A worktree precisou de `.env`, não só de `vendor/`.** O `01` → Dependências previu só o `composer install`. Sem `APP_KEY` os cinco casos morrem no `EncryptionServiceProvider`, com mensagem que aponta para a view do Filament e não para a causa. Vale para qualquer worktree nova deste repo.
- **CT-01 não pega o mutante "os quatro no cabeçalho E os três no rodapé"** (widget duplicado nas duas listas): a sequência `visão geral → tabela → três de detalhe` continua satisfeita pelas cópias do rodapé. Esse mutante é morto pelo `[CT-12]` da ancestral, que afirma as listas com `toBe` (igualdade estrita). Os dois casos são complementares por desenho — está na ADR-03 — e este é o exemplo concreto de por que.
- **O `<title>` renderizado é `"{rótulo}\n - \n{brand}"`**: `assertSeeInOrder(['<title>', $rotulo, '</title>'], escape: false)` funciona porque afirma ordem, não igualdade. Um `assertSee("<title>{$rotulo}</title>")` teria falhado por causa das quebras de linha do Blade.
- **`getBreadcrumbs()` devolve `[url => rótulo, …]`** (`vendor/filament/filament/src/Resources/Pages/Page.php:getResourceBreadcrumbs():178-186`), então o primeiro item se lê com `reset()`, não com `[0]`.

## Retrospectiva

- **Funcionou bem**: a auditoria pré-implementação (step 5) já tinha derrubado a premissa do "heading no HTML" e apontado o `wire:snapshot` — sem ela o CT-01 teria sido escrito com `assertSee('Visão geral')`, que passa verde e não prova nada, porque o texto não existe no HTML inicial. O gate de falsificabilidade por mutante injetado custou dois minutos e provou que os três cenários matam o que dizem matar.
- **Faltou no plano**: o `.env` na lista de pré-requisitos da worktree, e a verificação de que `Component::getName()` funciona fora do mount — a ADR-03 declarou explicitamente "verificado por leitura do vendor, não por execução", e foi exatamente ali que a leitura errou. A linha 71 do `Component.php` **é** `getName()`; o que a leitura não fez foi seguir quem escreve `$this->__name`.
