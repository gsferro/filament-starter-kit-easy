# Progresso — W6: permissões das telas de pacote

**Concluída em**: 2026-08-24 · **Versão**: 0.19.5 · **Base**: `origin/main` = `21cbb80` (v0.19.4)

## 1. O helper que pergunta ao Shield qual é a permissão da tela

- [x] `app/Support/PermissaoDaTela.php` criado, com docblock citando ADR-02 e ADR-03
- [x] Semântica idêntica a `HasPageShield::canAccess()` (fail-open herdado)

## 2. As quatro telas com callback publicado pelo pacote

- [x] `FilamentSpatieLaravelHealthPlugin::authorize()` → `View:HealthCheckResults`
- [x] `FilamentLogsExplorerPlugin::canAccessUsing()` → `ver-logs` **&&** `View:LogsExplorer`
- [x] `DependencyGraphPlugin::canAccessUsing()` → `auth()->check()` **&&** `View:DependencyGraphPage`
- [x] `RevivePlugin::authorize()` → `View:RecycleBin`
- [x] Comentário com `file:line` do método do plugin em cada um

## 3. `BackupRunsPage` — subclasse do kit, plugin fora

- [x] `app/Filament/Infra/Pages/BackupRunsPage.php` criado (nome idêntico ao do pacote)
- [x] `FilamentBackupMonitorPlugin::make()` removido de `->plugins([])`
- [x] `->livewireComponents([LatestBackupsWidget::class])` acrescentado, com o motivo transcrito
- [x] Import do plugin removido

## 4. `MyProfilePage` e `ComposerReleaseOverviewWidget` — subclasses do kit

- [x] `app/Filament/Pages/MyProfilePage.php` criado
- [x] `->customMyProfilePage()` nos três `BreezyCore::make()` (admin, app, infra)
- [x] `app/Filament/Infra/Widgets/ComposerReleaseOverviewWidget.php` criado
- [x] `fonteDeDadosDisponivel()` com o guarda de `composer_release_package_snapshots`
- [x] `FilamentComposerReleaseNotifierPlugin->widget(enabled: false)`

## 5. As três telas da Central de comandos — declaração

- [x] Comentário do `CommandCenterPlugin` no provider explica a lacuna com `file:line`

## 6. Testes

- [x] `tests/Kit/PermissoesDeTelasTest.php` — CT-24 **invertido** + CT-25 + CT-27; 4 linhas de
      pacote no dataset de CT-02/CT-03 (merge, ver "Desvios do Plano")
- [x] `tests/Kit/PaginasInfraTest.php` — 8 linhas de pacote no dataset do papel `infra` (CT-01)
- [x] `tests/Kit/PermissoesDeWidgetsTest.php` — CT-05, 3 linhas novas em dois casos existentes
- [x] `tests/Browser/TelasDoKitTest.php` — CT-B01 (o header widget isolado)
- [x] CT-21, CT-23 e os equivalentes de Widget seguem verdes com escopo maior (CT-31)

## 7. Documentação

- [x] `README.md` — F-62 reescrita, F-65 nova para as três exceções
- [x] `README.en.md` — idem
- [x] `CHANGELOG.md` — entrada 0.19.5; a seção "Sabido" da v0.18.10 **não** foi reescrita
- [x] ADR-05 da wiki ancestral marcada como substituída em parte, com as duas razões

## Verificação Final

- [x] `/ponytail:ponytail-review` da wiki (step 6) — 10 achados, ver "Auditoria Pré-Implementação"
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse` — **0 erros**
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — ver "Resultado dos testes"
- [x] `composer test:browser`
- [x] Contagens conferidas por consulta ao banco: `admin` 126, `infra` 140, `admin_app` 47,
      `panel_user` 17, tabela `permissions` 269 — **idênticas** ao baseline
- [x] `git push -u origin feat/permissoes-de-telas-de-pacote` (sem PR, sem merge)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| a tela de saúde vive em `/infra/health` | o slug é `health-check-results` (`telasDoKit()` e `route:list`) | rotas corrigidas no `01` e nos CT |
| "10 Pages e 1 Widget" (texto do requisito) | são **9 Pages + 1 Widget** = 10 classes; o banco tem exatamente 10 permissões | registrado em `## Ambiguidades` do `00`, com a lista como norma |
| `app/Filament/Pages/` poderia ser descoberto por algum painel | os três `discoverPages()` apontam para `Filament/{Admin,App,Infra}/Pages`; `app/Filament/Pages/Auth/` é o precedente | confirmado, virou justificativa em ADR-04 |
| os 4 gates (`ver-logs`, `command-center:access`, …) seriam barreira por tela | são `temPapelDoPainel('infra')` (`KitServiceProvider.php:171-174`) — equivalentes a `canAccessPanel()` | virou a linha "Barreira HOJE" da tabela do `01` e parágrafo no CHANGELOG |
| `MyProfilePage` teria item de barra lateral | `shouldRegisterNavigation()` é `false` (o `myProfile()` do Breezy nasce assim) | tirada do dataset que assere `assertDontSee` no menu |

### Auditoria Ponytail (step 6)

O review apontou 10 cortes. **Sete aplicados, três recusados com motivo** — e os aplicados
reduziram a entrega de um arquivo de teste novo com 10 casos para **linhas em quatro arquivos que
já existiam**.

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | CT-01..CT-08 como linhas de dataset nos casos que já existem, em vez de `PermissoesDeTelasDePacoteTest.php` novo | **sim** | os `it()` de `PermissoesDeTelasTest.php` e `PaginasInfraTest.php` já eram esta pergunta; o arquivo novo nunca nasceu |
| 2 | CT-05 como linhas nos dois casos de `PermissoesDeWidgetsTest.php` | **sim** | 3 linhas, 1 parâmetro novo (`?string $tabela`) |
| 3 | CT-30 com duas listas escritas à mão (fechadas × declaradas) | **sim, reformulado** | virou CT-27, que **pergunta ao painel** e compara com UMA lista — as três de ADR-05. Sem lista de rotas, sem duplicação com a wiki |
| 4 | os quatro números de contagem em CT-06 congelam e envelhecem | **sim** | CT-06 morreu; a asserção nominal (a permissão existe **e** está no papel `infra`) falsifica o rename sem número fixo. Os números viraram medição registrada no `01` e no CHANGELOG |
| 5 | CT-31 não é caso de teste | **sim** | virou uma linha de checklist ("os casos existentes seguem verdes com escopo maior") |
| 6 | CT-B01 como um `it()` em `TelasDoKitTest.php`, não arquivo novo | **sim** | seeders e helpers já estavam lá |
| 7 | a seção "Channel de Log" do `01` repete ADR-06 | **sim** | encurtada para um ponteiro |
| 8 | `PermissaoDaTela` como método estático em `Paineis` | **recusada** | `Paineis::mapa()` troca o painel corrente e descarta a instância do Shield a cada volta (`:129-154`), e este predicado roda em laço de render. São dois tempos de vida diferentes na mesma classe. Registrado em ADR-02 |
| 9 | tirar o `can('ver-logs') &&` e o `auth()->check() &&` | **recusada** | são perguntas diferentes (painel × tela), e é a coexistência que ADR-06 da wiki ancestral já decidiu para a flag `kit.hub`. Tirar o gate deixaria um `Gate::define` morto em outro arquivo — mudança de escopo, não simplificação |
| 10 | cortar ADR-03 e ADR-06 | **recusada** | as duas registram uma decisão que o próximo agente re-litigaria (direção do fail-open; nenhum log em laço de render). Prosa em wiki não é código |

## Blockers

Nenhum.

## Desvios do Plano

- **Passo 6 reescrito inteiro.** O `01` previa `tests/Kit/PermissoesDeTelasDePacoteTest.php` com
  dez casos. A auditoria do step 6 mostrou que oito deles eram o corpo de casos que já existiam com
  outro dataset. O arquivo não foi criado: as linhas entraram nos casos existentes. Efeito colateral
  bom — CT-21 e CT-23 passaram a cobrir as duas Pages novas **de graça**, porque elas caem no filtro
  `App\Filament\` de `paginasDePainelDoKit()`.

- **CT-06 (invariante numérico) não foi escrito.** Ver corte nº 4 acima. As contagens continuam
  sendo o oráculo — mas medidas à mão e registradas, não congeladas num `expect()`.

- **CT-30 virou CT-27, com outro mecanismo.** O plano pedia duas listas escritas à mão. O caso
  entregue percorre `Filament::getPanel('infra')->getPages()`, descarta o que é `App\Filament\`,
  filtra pelo mapa do Shield e assere que as que **ainda abrem** são exatamente
  `['Commands', 'History', 'RunView']`. Uma lista, derivada do painel, e ela fica vermelha nos dois
  eventos que interessam: tela de pacote nova sem barreira, e Central de comandos fechada.

- **`phpstan.neon` ganhou uma exceção**, o que não estava no plano. Ver "Notas de Implementação".

- **Rebase no meio da entrega**: a `main` andou para `21cbb80` (v0.19.4, o hotfix da tela de
  papéis). Rebase limpo, zero conflito — os dois conjuntos de arquivos não se cruzam. Ver a nota
  sobre `EditRole` abaixo.

## Notas de Implementação

### A inversão do CT-24 (RQ-08)

O caso não foi apagado nem duplicado: o mesmo `it()` mudou para `assertForbidden()`, ganhou dataset
e teve o docblock reescrito para explicar a inversão. A segunda asserção — a permissão continua
existindo na tabela — **não mudou**, e é ela que mantém vermelha a "correção" errada de pôr a classe
em `config('filament-shield.pages.exclude')`.

O antigo docblock dizia, por escrito: *"Quando alguém fechar a lacuna de verdade, este caso fica
vermelho — e o sinal é que ADR-05 precisa ser revisada, não que o teste está errado."* Foi o que
aconteceu. O padrão se repetiu: **o novo CT-27 é o mesmo instrumento apontado para as três telas que
sobraram**, e ele fica vermelho no dia em que o command-center publicar o setter por Page.

### `class_basename` é o que decide a chave, e foi isso que destravou a entrega

A objeção nº 1 de ADR-05 da wiki ancestral — "o Shield geraria `View:LogsExplorerDoKit`" — é
verdadeira só para subclasse com **outro nome**. `FilamentShield::getDefaultPermissionKeys()`
resolve o subject a partir do nome da classe (`FilamentShield.php:91-112`), não do namespace. Três
subclasses com nome idêntico ao do pacote, e a matriz não se moveu um número.

Consequência prática para quem lê o código: `use Vendor\Classe as ClasseDoPacote;` aparece em três
arquivos, e o docblock de cada um diz **"o nome é obrigatório"**. Sem essa frase o próximo agente
"arruma" o nome para português e cria três permissões órfãs sem nada acusar.

### Nenhuma interação com o hotfix da v0.19.4 — verificado, não presumido

`EditRole::permissoesQueOFormularioOferece()` monta as Page/Widget oferecidas a partir de
`RoleResource::getPageOptions()`/`getWidgetOptions()`, que continuam scoped ao painel corrente
(`EditRole.php:153-155`). Esta entrega **não** alargou o alcance do formulário: nenhuma linha de
`RoleResource` foi tocada, e as chaves de permissão são as mesmas de antes. Logo `oferecidas` não
mudou de tamanho, e `it('resolve o salvamento pela regra de conjunto')` não precisou de linha nova.

O que mudou foi só o **FQCN** por trás de três chaves (vendor → kit). Como `getPageOptions()` é
indexado por chave de permissão, o CheckboxList mostra exatamente o mesmo conjunto.

### O widget de pacote e a precedência de trait

`ExigePermissaoDoWidget` na subclasse vence o `canView()` **herdado** do pacote — em PHP, método de
trait perde só para método declarado na própria classe. Por isso a subclasse não declara `canView()`
e põe o guarda de tabela em `fonteDeDadosDisponivel()`. Declarar `canView()` ali desligaria a
permissão em silêncio, que é exatamente o defeito que ADR-01 da wiki ancestral existe para evitar.

### O `phpstan.neon` e uma anotação de vendor insatisfazível

`BreezyCore::customMyProfilePage()` pede
`class-string<Jeffgreco13\FilamentBreezy\Concerns\Plugin\Pages\MyProfilePage>` — o prefixo `Pages\`
foi resolvido relativo ao namespace do trait, e a classe pedida **não existe**. Nenhum argumento
pode satisfazê-la. A exceção no `phpstan.neon` segue o formato da que já existia para
`simpleLightbox()`: o motivo, o `file:line` do vendor, o que foi tentado e descartado, e qual teste
é a cobertura de verdade.

### Uma armadilha de dataset que custou um run

`->with(function () { ... config(...) ... })` estoura `Target class [config] does not exist`: o
dataset é avaliado na **coleta** dos testes, antes de a aplicação bootar. A resolução de
`config('authentication-log.table_name')` voltou para o corpo do `it()`, com `null` no dataset
significando "a tabela da trilha de acesso".

## Resultado dos testes

<!-- preenchido ao fim do run completo -->

## Retrospectiva

- **Funcionou bem**: medir a barreira de cada tela no `vendor/` com `file:line` **antes** de
  planejar. Foi essa varredura que achou os quatro callbacks publicados e derrubou a premissa de que
  fechar exigia subclassear dez classes de plugin. E foi ela que mostrou que os quatro "gates" que a
  dívida contava como barreira não discriminavam papel nenhum.

- **Funcionou bem**: a auditoria Ponytail da wiki **antes** de escrever teste. Ela cortou um arquivo
  de teste inteiro e trocou duas listas escritas à mão por uma pergunta ao painel. Se tivesse rodado
  depois, o custo seria apagar código verde.

- **Faltou no plano**: o `01` desenhou os casos de teste sem antes olhar o **corpo** dos casos que
  já existiam. Os oito casos "novos" eram datasets. A lição é a mesma da rule de varredura de padrão
  em `.ai/rules/specs.md`, aplicada a teste: antes de escrever caso novo, leia o `it()` vizinho — se
  o corpo é igual, é linha de dataset.

- **Faltou no plano**: nenhum passo previa `phpstan.neon`. Toda vez que o kit passa a chamar um
  método de vendor pouco usado, a chance de a anotação dele estar errada é real.
