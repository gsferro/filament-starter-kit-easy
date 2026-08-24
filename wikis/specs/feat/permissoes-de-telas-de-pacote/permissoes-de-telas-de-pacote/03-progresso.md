# Progresso — W6: permissões das telas de pacote

## 1. O helper que pergunta ao Shield qual é a permissão da tela

- [ ] `app/Support/PermissaoDaTela.php` criado, com docblock citando ADR-02 e ADR-03
- [ ] Semântica idêntica a `HasPageShield::canAccess()` (fail-open herdado)

## 2. As quatro telas com callback publicado pelo pacote

- [ ] `FilamentSpatieLaravelHealthPlugin::authorize()` → `View:HealthCheckResults`
- [ ] `FilamentLogsExplorerPlugin::canAccessUsing()` → `ver-logs` **&&** `View:LogsExplorer`
- [ ] `DependencyGraphPlugin::canAccessUsing()` → `auth()->check()` **&&** `View:DependencyGraphPage`
- [ ] `RevivePlugin::authorize()` → `View:RecycleBin`
- [ ] Comentário com `file:line` do método do plugin em cada um

## 3. `BackupRunsPage` — subclasse do kit, plugin fora

- [ ] `app/Filament/Infra/Pages/BackupRunsPage.php` criado (nome idêntico ao do pacote)
- [ ] `FilamentBackupMonitorPlugin::make()` removido de `->plugins([])`
- [ ] `->livewireComponents([LatestBackupsWidget::class])` acrescentado, com o motivo transcrito
- [ ] Import do plugin removido

## 4. `MyProfilePage` e `ComposerReleaseOverviewWidget` — subclasses do kit

- [ ] `app/Filament/Pages/MyProfilePage.php` criado
- [ ] `->customMyProfilePage()` nos três `BreezyCore::make()` (admin, app, infra)
- [ ] `app/Filament/Infra/Widgets/ComposerReleaseOverviewWidget.php` criado
- [ ] `fonteDeDadosDisponivel()` com o guarda de `composer_release_package_snapshots`
- [ ] `FilamentComposerReleaseNotifierPlugin->widget(enabled: false)`

## 5. As três telas da Central de comandos — declaração

- [ ] Comentário do `CommandCenterPlugin` no provider explica a lacuna com `file:line`

## 6. Testes

- [ ] `tests/Kit/PermissoesDeTelasDePacoteTest.php` — CT-01, CT-02, CT-03, CT-04, CT-06, CT-07, CT-08, CT-30
- [ ] `tests/Kit/PermissoesDeWidgetsTest.php` — CT-05
- [ ] `tests/Kit/PermissoesDeTelasTest.php` — CT-24 removido de lá e reescrito como CT-02 (RQ-08)
- [ ] `tests/Browser/PermissoesDeTelasDePacoteTest.php` — CT-B01
- [ ] CT-21, CT-23 e os equivalentes de Widget seguem verdes com escopo maior (CT-31)

## 7. Documentação

- [ ] `README.md` — a frase de v0.18.10 reescrita para o presente
- [ ] `README.en.md` — idem
- [ ] `CHANGELOG.md` — entrada nova; a seção "Sabido" da v0.18.10 **não** é reescrita
- [ ] ADR-05 da wiki ancestral marcada como substituída (uma linha de `Status`)

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse` — 0 erros
- [ ] `php artisan test --testsuite=Kit --compact`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — base 1016
- [ ] `composer test:browser`
- [ ] contagens do baseline conferidas por consulta (126 / 140 / 47 / 17 / 269)
- [ ] `git push -u origin feat/permissoes-de-telas-de-pacote` (sem PR, sem merge)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| (a preencher) | | |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| (a preencher) | | | |

## Blockers

<!-- a preencher -->

## Desvios do Plano

<!-- a preencher -->

## Notas de Implementação

### A inversão do CT-24 (RQ-08)

<!-- a preencher com o resultado real -->

## Retrospectiva

<!-- a preencher -->
