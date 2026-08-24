# Relatório de QA — Permissões de telas e ações

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** (natureza `nova`, UI com JS, domínio **sensível** — autorização)
> Natureza da wiki: `nova` **com infra compartilhada tocada** · Regressão: **sim, obrigatória**

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: **0** · Major: **0** · Minor: **2** · Cosmético: 0
- Ambiente: suíte in-process (`pest-plugin-browser` sobe o próprio servidor) · Pest 5 · PHP 8.4
- MCP: **Playwright indisponível por instrução** (instância única compartilhada nesta rodada);
  **Boost indisponível de fato** — as tools nunca ficaram expostas, ver `03-progresso.md`

Os dois Minor são **débitos declarados no `00-requisito.md`**, não achados novos: nenhum dos dois é
divergência entre requisito e produto, os dois são metade de cláusula deliberadamente fora do
escopo, com o motivo escrito antes de implementar.

## Auditoria do requisito (passo 2, antes de validar comportamento)

As três ambiguidades do `00` foram reavaliadas contra o produto. Nenhuma **nova** encontrada.

| Ambiguidade | Premissa adotada | Continua válida? |
|---|---|---|
| RQ-05 — "tela" inclui Page de vendor? | não | ✅ e agora é **observável**: CT-24 abre `/infra/logs` com `View:LogsExplorer` revogada e recebe 200 |
| RQ-06 — "link" é item de menu ou Action de URL? | as duas | ✅ e a premissa ficou **mais simples** do que se supunha — ver QA-02 |
| RQ-07 — a permissão de aceite pode barrar o dono do convite? | sim, e nasce concedida | ✅ CT-13 (nasce concedida) e CT-14 (revogar barra) |

## Matriz de Rastreabilidade

| RQ | Cláusula | Passo PRD | CT | CT-B | Código | Resultado |
|----|----------|-----------|----|------|--------|-----------|
| RQ-01 | levantar o que não tem permissão | 0, R10 | CT-25 | — | `inventarioDeAutorizacao()`, `inventarioDeRelationManager()` | ✅ |
| RQ-02 | criar as que faltam | 3 | CT-15 | — | `config/filament-shield.php` | ✅ |
| RQ-03 | selecionáveis na tela de papéis | 3 | CT-16 | — | `tabs.custom_permissions`, `resources.manage` | ✅ |
| RQ-04 | aplicar na superfície | 1, 2, 5 | CT-01..CT-14, CT-26, CT-28..CT-31 | CT-B01, CT-B02 | os 2 concerns + 5 `->authorize()` | ✅ |
| RQ-05 | **toda** tela | 1, 2 | CT-21..CT-24, CT-32 | CT-B01, CT-B02 | 5 Pages + 23 Widgets | ⚠️ **parcial** — QA-01 |
| RQ-06 | **todo** link | 5, R10 | CT-20, CT-31, CT-25 | — | `ListAiRuns`, `InfraPanelProvider` | ✅ — com correção, QA-02 |
| RQ-07 | **toda** action | 5, R10 | CT-09..CT-14, CT-25, CT-28..CT-30 | — | 6 `->authorize()` | ✅ |
| RQ-08 | default do kit | 3, 4 | CT-17, CT-18, CT-19, CT-27 | — | `PapeisSeeder` | ✅ |
| RQ-09 | concedível/revogável por papel sem editar código | 3, 4 | CT-16 + CT-01/CT-02 | — | config + seeder | ⚠️ **parcial** — QA-03 |

Nenhuma linha de código, CT ou passo do PRD ficou **sem** `RQ`. Nenhuma `RQ` ficou sem passo.

## Achados

### QA-01 — Page e Widget de vendor: permissão gerada, selecionável e não consultada · Minor · destino **5**

- **Dimensão**: A
- **Relacionado a**: RQ-05, ADR-05, CT-24
- **Esperado**: "TODAS as telas [...] precisa ter sua permissão especifica"
- **Observado**: as 5 Pages e os 23 Widgets **escritos no kit** consultam. As **10 Pages** e **1
  Widget de vendor** do `/infra` (`HealthCheckResults`, `BackupRunsPage`, `LogsExplorer`,
  `DependencyGraphPage`, `Commands`, `History`, `RunView`, `RecycleBin`, `MyProfilePage`,
  `ComposerReleaseOverviewWidget`) têm a permissão no banco e no checkbox, e ela não decide nada.
- **Repro**: revogar `View:LogsExplorer` do papel `infra` e abrir `/infra/logs` → 200.
- **Evidência**: CT-24 de `tests/Kit/PermissoesDeTelasTest.php` — o caso **assere** a lacuna, para
  ela não ser surpresa nem ser "corrigida" pelo caminho errado.
- **Destino 5 (não-defeito)**: é premissa de escopo declarada em `00-requisito.md` →
  `## Fora desta entrega` **antes** de implementar, com o custo da alternativa escrito em ADR-05
  (subclassear classe de plugin, com o `LogicException` que `.ai/rules/providers-filament.md`
  documenta). Quatro das dez já têm barreira própria (`ver-logs`, `command-center:access`,
  `viewPulse`, e o `canAccess()` do próprio pacote em três delas).
- **Ação exigida**: nenhuma nesta entrega. Fica como débito no `03-progresso.md`.

### QA-02 — O "furo de affordance" do link do dashboard de IA não existia · Minor · destino **5**

- **Dimensão**: A / I
- **Relacionado a**: RQ-06, passo 5 do PRD
- **Esperado**: o PRD mandava `->visible(fn () => Gate::allows('ver-ai-tasks'))` no
  `dashboardAiTasks`, porque "o botão aparecia para qualquer um que abrisse a listagem".
- **Observado**: `AiRunResource::canAccess()` é `Auth::user()?->can('ver-ai-tasks')`
  (`app/Filament/Infra/Resources/AiRuns/AiRunResource.php:81-84`) — a **mesma** expressão que
  protege a rota de destino. Não existe persona que abra a listagem e falhe no gate.
- **Repro**: papel `admin` (papel de painel legítimo, sem o gate de infra) →
  `GET /infra/execucoes-ia` responde **403**, antes de chegar a qualquer header action.
- **Evidência**: CT-20 de `tests/Kit/PermissoesDeAcoesTest.php`, dataset de duas linhas.
- **Destino 5**: o `->visible()` foi implementado, o teste **não conseguiu falsificá-lo** e a linha
  foi revertida — mutante sem matador é o que o `04` proíbe. O passo do PRD estava errado, e a
  premissa dele foi escrita sem `file:line` do ponto que a sustentaria, que é o sinal de alerta que
  `.ai/rules/specs.md` nomeia.
- **Ação exigida**: nenhuma. O docblock do arquivo registra a ausência com `file:line`, e o desvio
  está no `03-progresso.md`.

### QA-03 — RQ-09 não tem cenário do caminho pela tela de papéis · Minor · destino **3** (débito)

- **Dimensão**: A / K
- **Relacionado a**: RQ-09, CT-16
- **Esperado**: "total flexibilidade" = conceder/revogar por papel **sem editar código**.
- **Observado**: provado em **três** peças, nunca em um cenário só — CT-16 (a permissão É oferecida
  como opção pela tela), CT-01/CT-02 (ter e não ter muda o resultado) e
  `tests/Kit/PaineisTest.php:198,228` (a tela de papéis grava de fato, por `fillForm` + `call`).
  Falta o cenário que marca o checkbox em `/admin/shield/roles` e vê a tela passar de 403 para 200.
- **Repro**: n/a — é lacuna de cobertura, não defeito observável.
- **Destino 3**, aceito como **débito** e não como reprovação: o cenário exigiria
  `Livewire::test(EditRole::class)` sobre `app/Filament/Admin/Resources/Roles/**`, que a feature
  paralela `feat/perfis-e-permissoes` está reescrevendo agora — um caso ali é conflito de merge
  garantido, e a restrição de não tocar naquele diretório é dura nesta rodada.
- **Ação exigida**: escrever o cenário **depois** do merge das duas features. Registrado no
  `03-progresso.md`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 3 achados, todos Minor: 2 destino 5 (premissa declarada), 1 destino 3 (débito) |
| B | Fronteiras e dados | ✅ | a feature não introduz campo, domínio ordenável nem payload. A **única** partição de valor ausente que ela cria é "a linha de permissão não existe", e ela tem cenário: CT-26 |
| C | Matriz de permissão | ✅ | 6 permissões × 5 papéis conferida **por query** depois de ressemear, não por leitura do seeder. As 4 de administração só em `admin`; as 2 de convite em `admin_app` e `panel_user`; `master_global` com **zero** permissions. CT-17 (15 células) e CT-18 |
| D | Observabilidade real | ✅ | **nenhum log novo, por ADR-07**, e a ausência é decisão escrita: `canView()` roda 16× por carregamento do `/infra` e `canAccess()` é consultado por cartão de hub, item de menu e categoria do Spotlight — logar negativa ali esconde o evento. Os logs de sucesso que já existiam (`autenticacao`: attach, detach, atribuição de papéis, reenvio, revogação) **não foram alterados**. Sem log novo, **não há PII nova em context** |
| E | Performance | ✅ | medido: 16 widgets do `/infra` custam **16 queries** com o cache de permissão do spatie aquecido — e essas 16 são os `Schema::hasTable()` que **já existiam** no `canView()`. O `&&` do concern coloca a permissão **antes** do hook, então papel sem a permissão **economiza** a query. A checagem de permissão em si não gera query: `PermissionRegistrar` carrega tudo em memória na primeira. Ver "Não Verificado" para o que faltou medir |
| F | UX de erro | ✅ | a feature não cria mensagem nem formulário. A negativa de Page é o `abort(403)` com a tela branda do `filament-sentinel`, inalterada; a de Action é **ocultação**, que é o default do vendor e não gera mensagem |
| G | Tema e cor | ⏭️ pulada | o diff não toca `resources/css/`, `resources/views/` nem classe de cor nenhuma. `git diff --stat` confirma: só PHP de `app/`, `config/`, `database/seeders/`, `tests/` e markdown |
| H | Acessibilidade | ⏭️ pulada | nenhuma marcação nova. O efeito da feature é widget/Page **ausente**, não elemento novo |
| I | Segurança da superfície nova | ✅ | a feature **é** a superfície de segurança. IDOR: CT-30 (quem tem `Aceitar:Convite` não assume convite de outro — a barreira é `Convite::exigirDono()`, e a ordem entre ela e a permissão é nova). Mass assignment: CT-29 (com `AtribuirPapeis:Tenant` concedida, pedir papel `infra` não grava). Escalonamento de privilégio: as duas Actions nativas do RelationManager, que o vendor documenta como não-autorizadas, passaram a exigir permissão (ADR-04) |
| J | Regressão adjacente | ✅ | a wiki é `nova` mas **toca infra compartilhada**, então a regressão foi obrigatória. Suíte `Unit,Feature,Kit,Tenancy` completa + `Browser` completa, sem `--tia`: rodar por diff mediria o impacto e não a ausência de regressão, que é o que interessa quando o diff mexe na matriz de papéis. Ver "Resultado da regressão" |
| K | Adequação da suíte | ✅ | passo estático: os 54 casos novos foram varridos contra a tabela de oráculo fraco. **Nenhum** tem `assertOk()`/`assertSuccessful()` como assertion única, nenhum tem `assertNoJavaScriptErrors()` sozinho (os dois CT-B afirmam `<svg>` presente e dado sensível ausente), nenhum tem `assertDatabaseHas` só com a chave. **Dois oráculos foram reescritos durante a implementação** por serem fracos — CT-07 passou a afirmar o predicado `canView()` em vez do HTML, e CT-08 passou a afirmar `getVisibleWidgets()` do Dashboard em vez de `assertDontSee` (widget do Filament carrega adiado: o dado sensível não está na resposta inicial nem **com** a permissão, e o `assertDontSee` seria falso ✅). Passo medido (`--mutate`): ver "Não Verificado" |

## Resultado da regressão

| Suíte | Resultado |
|---|---|
| `tests/Kit/PermissoesDeTelasTest.php` | 16/16 ✅ |
| `tests/Kit/PermissoesDeWidgetsTest.php` | 9/9 ✅ |
| `tests/Kit/PermissoesDeAcoesTest.php` | 33/33 ✅ |
| `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | 15/15 ✅ |
| `tests/Browser/PermissoesDoDashboardTest.php` | 2/2 ✅ |
| `Unit,Feature,Kit,Tenancy` completa | ver `03-progresso.md` → Verificação Final |
| `composer test:browser` | ver `03-progresso.md` → Verificação Final |
| `vendor/bin/phpstan analyse` | 0 erros ✅ |

Os alvos de regressão de maior risco, por RCRCRC, e por que seguem verdes:

| Alvo | Letra | Por que era risco | Resultado |
|---|---|---|---|
| `tests/Kit/HubDeCardsTest.php` | **R**epaired · **C**hronic | o docblock de `HubDeInfraestrutura` proíbe `canAccess()` **com a flag**, e há um cenário guarda de ADR-03 | verde: a persona dele é papel `infra`, que **tem** `View:HubDeInfraestrutura`. ADR-06 registra por que flag e permissão são ortogonais |
| `tests/Kit/GraficosDoDashboardTest.php` | **C**ore | os 6 widgets que ele testa passaram a exigir permissão, e o arranjo dele cria papel à mão sem permissão nenhuma | verde: ele usa `Livewire::test($widget)->get('options')`, e `Widget` só checa `canView()` no **hydrate** (`vendor/filament/widgets/src/Concerns/CanAuthorizeAccess.php`), não no mount |
| `tests/Kit/PaginasInfraTest.php` + as 52 telas de `tests/Browser` | **C**ore · **R**isk | visitam com papel de painel; qualquer vermelho aqui significaria permissão que não chegou ao papel | verde |
| `tests/Tenancy/AdminDaOrganizacaoTest.php` | **R**isk | usa `papeisNaOrganizacao`, que ganhou `->authorize()` | verde: a persona é `master_global`, e a matriz do `admin` recebeu `AtribuirPapeis:Tenant` |
| `config/filament-shield.php` | **C**onfiguration | três chaves alteradas, e a matriz dos três painéis deriva dela | conferido por query: 61/127/142 permissões (app/admin/infra), contra 59/125/140 antes |

**Divergência entre previsto e medido**: nenhuma. O PRD previu que o efeito no default do kit seria
**zero** para os cinco papéis, porque todo papel de painel recebe a matriz inteira do painel dele —
confirmado por query.

## Débitos Aceitos

- **QA-01** (Minor): Page e Widget de vendor com permissão inerte. Premissa declarada em ADR-05;
  CT-24 assere a lacuna e fica vermelho no dia em que alguém a fechar.
- **QA-03** (Minor): RQ-09 sem cenário do caminho pela tela de papéis. Escrever **depois** do merge
  de `feat/perfis-e-permissoes`.

Os dois replicados em `03-progresso.md`.

## Suspeitas Não Confirmadas

- Nenhuma. Os dois pontos que poderiam ter virado suspeita — o `->visible()` do link e o mutante
  `->visible()` vs `->authorize()` — foram **reproduzidos e resolvidos** durante a implementação, e
  estão como QA-02 e como desvio nº 2 do `03-progresso.md`.

## Não Verificado

- **`pest --mutate` sobre os dois concerns.** Declarado fora do escopo no `04` **antes** de
  implementar, com motivo: o que a entrega acrescenta são expressões booleanas de duas condições,
  cujos mutantes (`&&` → `||`, `true` → `false`) R1..R4 matam por construção com o par tem/não-tem.
  Rodar mutação sobre 12 linhas de trait devolve ruído. O passo **estático** da dimensão K rodou.
- **Segunda sonda de performance** (queries com **todas** as permissões negadas, para medir o ganho
  do curto-circuito). A sonda estourou o timeout disputando CPU com a suíte completa. A primeira
  sonda mediu o caso caro (16/16 visíveis, 16 queries) e o curto-circuito é consequência da ordem
  do `&&` — mas o **número** do caso barato não foi medido.
- **Playwright MCP** — proibido nesta rodada (instância única compartilhada com três agentes). Os
  três confrontos que só ele faz (inventário de elementos × cobertura do CT-B, UI renderizada ×
  `## Superfície de UI`, screenshot nos dois temas) **não** foram executados. Mitigação parcial: a
  dimensão G foi pulada por ausência de mudança de marcação/cor no diff, o que é verificável
  estaticamente por `git diff --stat`.
- **Boost MCP** — `search-docs`, `database-query`, `database-schema` e `record-rule` nunca ficaram
  expostos. Substitutos e o que cada um cobriu estão em `03-progresso.md` →
  `## Degradação de ferramenta declarada`.
