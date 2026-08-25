# Relatório de QA — W6: permissões das telas de pacote

**Data**: 2026-08-24 · **Base**: `21cbb80` (v0.19.4) · **Ciclos**: 1 · **Veredito**: **APROVADO COM DÉBITO**

Confronto `00-requisito.md` × `01-plano-acao.md` × código rodando, por sub-agente que **não**
implementou a feature. O agente recebeu o requisito como oráculo e a instrução de **provar** que a
entrega deixa passar defeito — não de aprovar.

## Matriz de Rastreabilidade

| RQ | Cláusula | Código | Teste | Situação |
|----|----------|--------|-------|----------|
| RQ-01 | a permissão decide | `app/Support/PermissaoDaTela.php` + 4 callbacks no `InfraPanelProvider` + 3 subclasses | CT-24, CT-25, dataset de CT-02/CT-03, `PermissoesDeWidgetsTest` | ✅ |
| RQ-02 | quem tem, entra | — (nenhum código novo é necessário) | `PaginasInfraTest`, 8 rotas com o papel `infra` real | ✅ oráculo de over-block, ver achado 7 |
| RQ-03 | quem não tem, 403 | `InfraPanelProvider` (o `&&` do LogsExplorer) | CT-24, linha `'a repro do requisito'` → `/infra/logs` 403 | ✅ |
| RQ-04 | permissão continua no banco e no checkbox | nenhum `pages.exclude` tocado | CT-24, 2ª asserção | ⚠️ parcial — ver "Débito declarado" |
| RQ-05 | o inviável fica declarado com `file:line` | comentário do `CommandCenterPlugin` | CT-27 | ✅ |
| RQ-06 | nenhuma permissão órfã | por construção: as 7 chaves são `class_basename`-idênticas | `PaginasInfraTest` + medição no banco | ✅ |
| RQ-07 | README no presente | `README.md` F-62 e F-67; `README.en.md` idem | — | ✅ |
| RQ-08 | CT-24 atualizado, não apagado | — | o mesmo `it()`, com o sinal trocado | ✅ |

## Achados e o que foi feito

| # | Sev. | Onde | Problema | Ação |
|---|---|---|---|---|
| 1 | **ALTO** | `app/Filament/Infra/Pages/HubDeInfraestrutura.php:15,143` | o mapa de descrições do hub era chaveado pelo FQCN do PACOTE, e `DescobreCardsDoPainel` casa por FQCN do que o painel registra → o cartão "Backups" ficava sem frase e `HubDeCardsTest` estourava chave indefinida | **corrigido** (commit `b1892c1`) |
| 2 | **ALTO** | `tests/Kit/PaineisTest.php:15,147` | `Paineis::permissoesDe('app', [classe do Breezy])` faz `->only($fqcns)` sobre FQCN **registrado** e voltava vazio → `toContain('View:MyProfilePage')` falhava | **corrigido** (mesmo commit) |
| 3 | MÉDIO | `InfraPanelProvider` (declaração de RQ-05) | citava `tests/Kit/PermissoesDeTelasDePacoteTest.php`, arquivo que a auditoria do step 6 fez não existir | **corrigido** |
| 4 | MÉDIO | `README.md` / `README.en.md` | a linha nova reusava o ID `F-65`, já ocupado por "Boas-vindas na raiz" | **corrigido** → `F-67` |
| 5 | MÉDIO | `InfraPanelProvider` (health e lixeira) | os dois `authorize()` eram só `PermissaoDaTela::permite(...)`, sem `auth()->check()`, assimétricos com o do grafo — e o helper falha ABERTO com usuário nulo. Não alcançável por HTTP (o `authMiddleware` cobre), mas Spotlight, cartão de hub e console consultam o mesmo `canAccess()` | **corrigido**: `auth()->check() &&` nos três, com a razão escrita uma vez |
| 6 | BAIXO | `vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:115,120` | o item "Meu perfil" do menu do usuário não checa visibilidade: quem perdeu `View:MyProfilePage` continua vendo o link e leva 403 no clique | **débito declarado**, ver abaixo |
| 7 | BAIXO | `tests/Kit/PaginasInfraTest.php` | as 8 linhas novas passariam com a feature revertida (antes as telas abriam para todos) — são guarda de **over-block**, sem poder discriminante próprio | **não é defeito**: é a metade "quem TEM entra" do par, e o docblock do dataset já diz isso. Quem discrimina é CT-24/CT-25 |

**Os dois ALTO têm a mesma causa, e é a lição desta entrega**: a subclasse homônima preserva a
**chave da permissão** (`class_basename`) e **não** preserva o **FQCN**. Todo lugar do kit que casa
por FQCN precisou acompanhar. A varredura completa foi
`grep -rn 'Brimham\\FilamentBackupMonitor\\Pages\|FilamentBreezy\\Pages\\MyProfilePage\|FilamentComposerReleaseNotifier\\Filament\\Widgets' app tests` — e ela é o que
`.ai/rules/specs.md` §"varra o padrão repetido" manda fazer antes de consertar um ponto só.

## Débito declarado

**O link "Meu perfil" do menu do usuário continua visível para quem perdeu a permissão.** O item é
registrado pelo `BreezyCore` (`BreezyCore.php:110-120`) e não tem `->visible()`. Sobrescrevê-lo
exigiria replicar rótulo, URL e avatar em `userMenuItems()` nos **três** providers — mais ou menos
18 linhas para esconder um link que já responde 403 no clique.

Isso **não** é a mesma classe de defeito que a feature fechou: a barreira existe e funciona; o que
sobra é vazamento de affordance num item de menu que a tela é do próprio usuário. Registrado aqui,
no `CHANGELOG` e no `03-progresso.md` em vez de corrigido — e a redação de F-62 nos READMEs fala do
item da **barra lateral**, que é onde a promessa se cumpre (`MyProfilePage::shouldRegisterNavigation()`
é `false`, então ela nunca teve item de barra lateral).

## RQ-04: o que ficou parcial, e por que não é regressão

A cláusula fala em "a permissão existe no banco **e no checkbox** da tela de papéis". A metade do
banco está testada (2ª asserção de CT-24). A metade do checkbox **não** — e para Page de `/infra` o
checkbox não aparece em `/admin/shield/roles`, porque as abas de Páginas e Widgets do Shield são
scoped ao painel corrente (`EditRole.php:126-131`).

Isso é **anterior** a esta entrega e é exatamente a lacuna que o hotfix da v0.19.4 nomeia na própria
seção "Sabido" dele. Fechar exigiria alargar o alcance do formulário — e a v0.19.4 registra que, se
alguém o fizer, a conta de `oferecidas` em `EditRole::permissoesQueOFormularioOferece()` tem de
crescer junto. Fora do escopo desta wiki; não foi tocado.

## Hipóteses rejeitadas

Registradas porque custaram o mesmo que os achados, e relatório sem rejeição parece que só se
procurou onde se achou.

- **Chave de permissão órfã** — não. `FilamentShield::resolveSubject()` usa `class_basename` da
  instância (`FilamentShield.php:147-163`), então subclasse homônima produz a mesma `View:*`.
  Medido no banco antes e depois: `admin` 126, `infra` 140, `admin_app` 47, `panel_user` 17, tabela
  `permissions` 269 — idênticos.
- **Interação com o hotfix da v0.19.4** — não. `getPageOptions()`/`getWidgetOptions()`
  (`HasShieldFormComponents.php:92-104`) derivam de `FilamentShield::getPages()/getWidgets()`, cujo
  `permissions` é a mesma chave; a troca de FQCN **não** altera o que o formulário oferece nem o que
  `EditRole::permissoesQueOFormularioOferece()` (`:134-162`) preserva. `View:BackupRunsPage` e
  `View:ComposerReleaseOverviewWidget` nunca foram oferecidas no `/admin` (só existem no mapa do
  `/infra`) e seguem protegidas pelo `atuais − oferecidas` de `permissoesFinais()` (`:181-190`).
  **Nenhuma linha nova em `it('resolve o salvamento pela regra de conjunto')`.**
- **Trancar o papel `infra` fora de alguma tela** — não. Os 4 FQCN passados ao helper batem com os
  registrados, então a chave resolve e não cai no fail-open; `master_global` atravessa pelo
  `Gate::before` porque o helper usa `can()` e não `hasPermissionTo()`.
- **`panel_user` trancado fora do próprio perfil** — não. `View:MyProfilePage` não está em
  `permissoesDeAdministracaoDoApp()` nem em `permissoesForaDoApp()` (`PapeisSeeder.php:159-208`), e
  está nos quatro papéis.
- **Tirar o `FilamentBackupMonitorPlugin` do painel** — seguro. Grep no pacote inteiro não acha
  nenhum `filament('filament-backup-monitor')` nem `::get()`; é o oposto do caso de
  `.ai/rules/providers-filament.md`. E `->livewireComponents([LatestBackupsWidget::class])` reproduz
  o único efeito colateral do `register()` dele.
- **O `canView()` do pacote vencendo o trait no widget** — não. Trait na classe vence método
  herdado, e o guarda de tabela ficou em `fonteDeDadosDisponivel()`. As chaves de tradução do CT-B
  existem (`lang/pt_BR/backups.php:6,32`).
- **Colisão de slug ou de basename** — não. Uma classe por basename registrada em cada painel, e
  nada em `config/filament-shield.php` foi tocado.
- **Contradição com `.ai/rules/filament.md` / `providers-filament.md`** — nada. Nenhuma Page ganhou
  `shouldRegisterNavigation()` à mão, nenhum `CardItem::make()` à mão, e o guarda de tabela está
  separado da autorização.
