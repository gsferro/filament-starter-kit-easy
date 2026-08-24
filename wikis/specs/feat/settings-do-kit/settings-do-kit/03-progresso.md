# Progresso — Settings do kit em `/admin`

## 1. Channel de log

- [ ] `config/logging.php` — channel `configuracoes` com a forma dos três vizinhos (driver por `LOG_KIT_DRIVER` + `handler`)

## 2. A classe de settings

- [ ] `app/Settings/ConfiguracoesDoKit.php` — 21 propriedades tipadas, `group()` = `kit`, `encrypted()` = `['mail_password']`
- [ ] `aplicarNaConfig()` — o mapa propriedade → chave de config, num só lugar
- [ ] PHPDoc de classe: fonte da verdade, e o aviso de RQ-18 (não é settings de organização)

## 3. As chaves novas de `config/kit.php`

- [ ] `cor_primaria_hex`
- [ ] `identidade` (logo, favicon, arte_do_login)
- [ ] `tabelas` (paginação, listrada, persistir_filtros, colunas_redimensionaveis) — com a coerção de booleano de `.ai/rules/config.md`
- [ ] `.env.example` — as chaves novas, comentadas, no bloco do kit

## 4. A página de settings

- [ ] `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` com `HasPageShield`
- [ ] `canEdit()` de **instância** devolvendo `static::canAccess()`
- [ ] Quatro abas: Identidade, E-mail, Tabelas, Kit
- [ ] Log em `afterSave()`

## 5. Migration de settings, alinhamento no boot e auditoria

- [ ] `database/settings/*_create_kit_settings.php` — `up()` semeando de `config()`, `down()` com `deleteIfExists`
- [ ] `config/settings.php` — registro explícito da classe
- [ ] `KitServiceProvider::configureSettingsDoKit()` — antes de `configuraFilamentGlobal()`, com `try/catch (Throwable)` envolvendo o `Schema::hasTable()`
- [ ] `app/Listeners/AuditarConfiguracoesDoKit.php` — diff, uma linha por propriedade alterada, `event = 'settings-updated'`, segredo mascarado
- [ ] Registro do listener

## 6. Os três painéis passam a ler o settings

- [ ] `app/Support/IdentidadeDoKit.php` — logo, favicon, arte, com a guarda de arquivo ausente
- [ ] `AdminPanelProvider` — `brandName` em Closure, `favicon`, `brandLogo`, as três `media()`
- [ ] `AppPanelProvider` — idem
- [ ] `InfraPanelProvider` — idem

## 7. Cor, tabelas e `kit:install --custom`

- [ ] `app/Support/CorPrimaria.php` — precedência hex → nome → padrão, com validação de formato
- [ ] `app/Providers/Concerns/ConfiguraFilamentGlobal.php` — `configuraTable()` lendo `kit.tabelas.*`; TODO de `:35-38` substituído
- [ ] `app/Support/CustomizadorDaInstalacao.php` — `aplicarSemBanco()` propagando para o settings; `itensManuais()` sem a arte do login

## 8. Permissões

- [ ] `db:seed --class=ShieldPermissionsSeeder`
- [ ] `db:seed --class=PapeisSeeder`
- [ ] Conferir: `admin` **tem** `View:ConfiguracoesDoKit`; `infra` e `panel_user` **não**
- [ ] Confirmar que `database/seeders/PapeisSeeder.php` **não** precisou de edição

## 9. Documentação e inventário

- [ ] `tests/Pest.php` — `/admin/configuracoes-do-kit` em `telasDoKit()['admin']`
- [ ] `README.md` — seção nova; TODO de `:1257` substituído
- [ ] `README.en.md` — idem; TODO de `:1221` substituído
- [ ] `CHANGELOG.md` + `config/kit.php` (`version`)

## Testes

- [ ] `tests/Kit/ConfiguracoesDoKitTest.php` — CT-01…CT-04, CT-11, CT-12, CT-21…CT-25, CT-28, CT-30
- [ ] `tests/Kit/CorPrimariaTest.php` (acrescentar) — CT-05, CT-06, CT-07
- [ ] `tests/Kit/ConfiguracoesDoKitTelaTest.php` — CT-08…CT-10, CT-13…CT-16
- [ ] `tests/Kit/DefaultsDeTabelaTest.php` — CT-17…CT-20
- [ ] `tests/Kit/IdentidadeDoKitTest.php` — CT-26
- [ ] `tests/Kit/CustomizadorDaInstalacaoTest.php` (acrescentar) — CT-27
- [ ] `tests/Tenancy/IdentidadeVisualTest.php` (acrescentar) — CT-29
- [ ] `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` — CT-31, CT-32
- [ ] `tests/Browser/ConfiguracoesDoKitTest.php` — CT-B01, CT-B02

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` — 0 erros
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — 662 na base, não deixar cair
- [ ] `composer test:browser`
- [ ] `migrate:rollback` da migration de settings, e `migrate` de volta, sem quebrar o boot
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] `git push -u origin feat/settings-do-kit`

---

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa inicial | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "o plugin de settings não está registrado em nenhum PanelProvider — registrar" | **não existe classe `Plugin`** no pacote. `SpatieLaravelSettingsPluginServiceProvider:9-18` só registra o comando `make:filament-settings-page` e as traduções | passo do PRD **removido**: nada a registrar. Registrado na análise de `vendor/filament/spatie-laravel-settings-plugin` |
| `canEdit()` é estático (o exemplo do README do vendor sugere isso) | `public function canEdit(): bool` — **instância** (`src/Pages/SettingsPage.php:248`) | PRD passo 4 e ADR-04 corrigidos |
| a trait do Shield está em `Concerns/` | está em `Traits/HasPageShield.php` — `Concerns/` só tem `HasAboutCommand`, `HasEntityDiscovery`, `HasEntityTransformers`, `HasLabelResolver`, `HasResourceHelpers` | PRD passo 4 e ADR-04 corrigidos com o caminho certo |
| `PapeisSeeder.php` precisa de alteração mínima para a permission chegar ao papel `admin` | **não precisa de nenhuma**: `permissoesDoPainel('admin', $guard)` (`:57-58`) colhe a matriz inteira do painel, e as duas listas de subtração recortam o painel `app` | ADR-04 reescrito; o risco de conflito de rebase com `feat/permissoes-de-telas-e-acoes` **desapareceu** |
| `config(['app.name' => ...])` no boot muda o `brandName` dos painéis | **não muda.** Probe: `php artisan tinker --execute 'config(["app.name" => "PROBE"]); echo Filament::getPanel("admin")->getBrandName();'` → `Starter Kit • Admin` | ADR-02 criado; PRD passo 6 passa a exigir `Closure` nos três `brandName`, no `favicon` e no `brandLogo` |
| "densidade da tabela" é um default do Filament que vira Settings (o TODO promete) | **não existe no Filament 5.** Nenhuma ocorrência de `density` em `vendor/filament/tables/src`; `Enums/` tem 7 enums e nenhum de densidade | ADR-09 criado; RQ-11 fica 3/4 e o TODO dos READMEs é **reescrito**, não apagado |
| auditar o settings é aplicar `App\Traits\AuditsFillables` a uma model sobre a tabela `settings` | audita a **criação** e perde **toda alteração**: `createProperty()` usa `->create()` (`DatabaseSettingsRepository.php:56`) mas `updatePropertiesPayload()` usa `->upsert()` (`:74-77`), que não dispara evento de Eloquent | ADR-07 criado: listener de `SavingSettings` |
| `auditable_type` pode ser a classe de settings | a listagem faz `->with(['user','auditable'])` (`AuditsTable.php:38`); tipo que não é model Eloquent quebra o eager load do morph e derruba a tela | ADR-07: `auditable_type` = `SettingsProperty::class` (model real na tabela `settings`) |
| `event = 'updated'` na trilha, como o resto do kit | `RestoreAuditAction` fica visível com `event === 'updated'` (`RestoreAuditAction.php:46`) e o restore faz `fill($old_values)->save()` (`CanRestoreAudit.php:53-54`) numa linha de colunas `group/name/payload` — SQL inválido | ADR-07: `event = 'settings-updated'`, e CT-24 afirma que o botão não aparece |
| `search-docs` cobre o plugin de settings 5.x | devolve a **2.x** (`make:filament-settings-page`, `Filament\Pages\SettingsPage`, `$settings`) — coincide em parte, e é isso que torna o erro invisível. O contrato foi lido no vendor com `file:line` | declarado no `01` (análise do vendor) e no `04` |
| a suíte de 662 casos vai precisar de arranjo novo por causa do alinhamento no boot | **não**: no `RefreshDatabase` o `boot()` do provider roda **antes** das migrations, então o alinhamento não acha nada e os valores forçados no `phpunit.xml` seguem valendo | registrado no `01` (análise do `phpunit.xml`) como consequência de desenho, e é o que torna a regressão barata |
| `storage:link` pode não estar rodando na instalação | roda — `app/Console/Commands/KitInstall.php:353` (`callSilently('storage:link')`) | ADR-03 cita a linha |
| `(bool) env('CHAVE', true)` serve para os interruptores novos | mesmo defeito que `.ai/rules/config.md` documenta para inteiros: com `CHAVE=` o default `true` nunca entra | PRD passo 3 passa a usar `filter_var(..., FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $padrao`; virou candidato a rule |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar model `App\Models\ConfiguracaoDoKit` só para dar nome bonito ao `auditable_type` — `SettingsProperty` do vendor já é a model daquela tabela e resolve o morph | **sim** | ADR-07, alternativa 2 |
| 2 | não editar `PapeisSeeder.php` — a matriz do painel já entrega a permission ao `admin` | **sim** | ADR-04 |
| 3 | não registrar nada em `PanelProvider` para o plugin de settings — não há plugin a registrar | **sim** | `01`, análise do vendor |
| 4 | não criar flag `KIT_SETTINGS_ENABLED` — seria uma terceira fonte da verdade, e o `migrate:rollback` já é o desligamento | **sim** | ADR-01, alternativa 3 |
| 5 | não usar `spatie/laravel-medialibrary` para três arquivos — ela exige model dona, e o settings não é model | **sim** | ADR-03, alternativa 1 |
| 6 | não criar duas permissões (ver/editar) — o `canEdit()` do vendor não esconde valor, e a tela guarda senha de SMTP | **sim** | ADR-04, alternativa 1 |
| 7 | uma classe de settings e uma página, não uma por assunto — quatro abas em vez de quatro telas, quatro permissões e quatro migrations | **sim** | `01`, passo 4 |
| 8 | não trocar os consumidores de `config()` por leitura direta do settings — dez arquivos contra um mapa | **sim** | ADR-01, alternativa 4 |
| 9 | cortar `darkModeBrandLogo`, idiomas, retenção e slug de organização do escopo | **sim** | `00`, "Fora desta entrega" |
| 10 | não escrever CT-B para favicon, upload, tema e cor — o `04` prova mais barato | **sim** | `05`, "Cogitado e cortado" (7 cenários) |
| 11 | reusar os helpers de `tests/Pest.php` em vez de criar novos (`.ai/rules/testes.md`: helper cruzado estoura em `--tia` e em arquivo isolado) | **sim** | `04`, Setup Global |
| 12 | recusado: "juntar as duas listas de subtração do `PapeisSeeder`" não foi cogitado — reintroduziria o defeito da 0.18.2 | **n/a** | — |

### Revisão adversarial (step 6 do `feature-test-design`)

Delegada a sub-agente independente, sem acesso ao `01`, ao `02` nem a código.

**Rodada 1 — 28 achados**: 5 implementações erradas que passavam em todos os 34 cenários, 10 oráculos fracos, 3 cenários com `Quando` múltiplo indevido e 10 cláusulas `RQ` com cobertura insuficiente. **Zero cenários sem `Então`.**

O achado estrutural foi um só, e explica quatro dos cinco: **CT-01 particionava por tipo de PHP (string, aninhada, inteiro, booleano) quando a unidade de falha é a chave de config**. Cada chave é uma linha de código independente — não há classe de equivalência entre `mail.from.address` e `mail.mailers.smtp.host`. Com 6 das 21 propriedades exercitadas, um mapa que cobrisse só aquelas seis passava no conjunto inteiro, e a tela prometia 21 configurações entregando 6.

O segundo achado mais caro foi de camada, não de partição: **a classe de resolução de identidade tinha oráculo no próprio retorno**, e uma implementação em que ela está perfeita e **nenhum painel a consome** passava — `brandLogo` ausente, arte do Auth Designer literal. Nenhum cenário renderizava uma página e olhava o HTML.

**Fechamento** (detalhe caso a caso em `04-casos-de-teste.md` → `## Revisão Adversarial`):

| Destino | Quantidade |
|---|---|
| cenário novo | 5 (CT-33, CT-34, CT-35, CT-35b, CT-36) |
| cenário reescrito | 8 (CT-01 de 6 para 21 linhas; CT-11 por reflexão em vez de contagem; CT-12 com remigração; CT-15 sem a fusão de verbos; CT-16 pelo `DatabaseSeeder`; CT-20, CT-24 e CT-28 com oráculo forte) |
| justificativa escrita acrescentada | 2 (CT-08 e CT-B02, `Quando` múltiplo declarado) |
| rebaixado a asserção de apoio | 1 (CT-14 — quase-tautologia de framework, mantido porque é o único matador de M29) |
| recusado com motivo | 3 (oráculo de função pura em CT-05/CT-06 é o observável certo; CT-27 cobre o que o `--custom` de fato oferece) |
| mutantes acrescentados | 9 (M71…M79, marcados "origem: revisão adversarial") |

Totais depois do fechamento: **39 cenários, 17 regras, 79 mutantes previstos, 2 sem matador** (M49 e M70, declarados com o que foi tentado).

**Rodada 2 — 17 achados** (teto de 2 rodadas atingido; o que sobrou virou lacuna declarada, não uma rodada 3): 3 implementações erradas que ainda passavam, 5 oráculos novos fracos ou vacuosos, 6 redundâncias/contradições introduzidas pelo próprio fechamento da rodada 1, e 3 cláusulas ainda sem falsificador.

Duas coisas que essa rodada provou, e nenhuma delas era esperada:

1. **Os três "ainda passava" eram lacunas de TESTE, não de código.** O código já estava escrito quando ela rodou, e nos três casos ele faz o certo: o alinhamento está no `boot()` do `KitServiceProvider` (alcança comando artisan e fila), o mapa grava a chave **sem** condicionar a valor não-nulo, e a trilha sai do listener de `SavingSettings` (alcança gravação fora da tela). Faltava o cenário capaz de reprovar a alternativa. É o valor de uma revisão cega: ela não sabia o que o código faz e apontou onde o conjunto não olhava.
2. **Um achado da rodada 1 era erro meu, e a rodada 2 o desfez.** CT-33 nasceu da regra do verbo irmão ("evidência de `abrir` não cobre `gravar`"). Sob a decisão de RQ-14 — uma permissão governa as duas —, `mount()` aborta em `canAccess()` antes de `save()` existir, e `canEdit()` fixo em `true` é **inobservável por qualquer cenário possível**. A regra é válida em geral e foi aplicada a um par de verbos que a própria decisão de escopo fundiu. CT-33 foi removido e M27/M73 passaram a lacuna declarada.

Fechamento: **3 cenários novos** (CT-37 varre onde o alinhamento está ligado; CT-38 cobre a propriedade limpada, que era o caminho de volta ao default inexistente; CT-39 grava fora da tela), **1 removido** (CT-33), **7 oráculos reescritos** (CT-11 passa a arranjar valor discriminante antes de semear, CT-20 volta a ser só fumaça de tela de vendor, CT-32 troca asserção de ausência por presença, CT-34/CT-35/CT-35b declaram o arranjo, CT-36 troca contagem de opções por comportamento), **2 linhas escritas** que a rodada 1 prometeu e não escreveu (a `logo` em CT-26), **6 mutantes acrescentados** (M80…M85).

Totais finais: **41 cenários, 17 regras, 85 mutantes, 5 sem matador — todos declarados**: M27 e M73 (`canEdit()` inobservável com uma permissão só), M49 (heurística de máscara de segredo, com um campo de segredo só), M70 (alinhamento duplicado — performance, sem efeito funcional) e **RQ-16**, que não é falsificável por teste porque julga o julgamento de quem analisou o kit. Marcá-la como fechada, como a rodada 1 fez, era relabelagem de cobertura.

---

## Blockers

<!-- nada até agora -->

---

## Desvios do Plano

<!-- preenchido durante a implementação -->

---

## Notas de Implementação

<!-- preenchido durante a implementação -->

---

## Candidatos a Rule de Projeto (PROPOSTA — decisão do usuário)

Apenas propostos. A gravação via `record-rule` é decisão do usuário e o agente principal executa.

### 1. Coerção de booleano do `.env` — o irmão do defeito já documentado

- **Glob**: `config/**`
- **Evidência**: `.ai/rules/config.md` documenta `(int) env('CHAVE', 100)` como padrão defeituoso e manda varrer o repo ao encontrar um. O caso do booleano é o **mesmo mecanismo** e não está escrito: com `CHAVE=` (presente, vazia), `env()` devolve `''`, `(bool) ''` é `false`, e um default `true` nunca entra. Três chaves novas desta feature caem nele (`KIT_TABELA_LISTRADA`, `KIT_TABELA_PERSISTIR_FILTROS`, `KIT_TABELA_COLUNAS_REDIMENSIONAVEIS`), e o `KIT_HUB` existente só escapa porque o default dele é `false`.
- **Nota proposta**: default `true` em chave booleana do `.env` exige `filter_var($bruto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $padrao`. `(bool) env('CHAVE', true)` é defeituoso — chave presente e vazia devolve `''`, que é `false`, e o default nunca entra. Default `false` é inócuo. Mesmo mecanismo do `(int) env()` já documentado nesta rule.
- **Gates**: durável ✅ (vale para toda chave futura) | escopável ✅ (`config/**`) | não-inferível ✅ (o código ao redor usa `(bool) env()` e parece certo) | não-redundante ✅ (a rule existente cobre só inteiro — é **atualização** da rule, não rule nova, que é o preferível)

### 2. Configuração que precisa valer em tempo de execução vai em `Closure`, não em escalar

- **Glob**: `app/Providers/Filament/**`
- **Evidência**: medido nesta wiki com probe de tinker — `config(['app.name' => 'PROBE'])` depois do boot não muda `getBrandName()`. `.ai/rules/providers-filament.md` não tem essa regra; o único registro do mecanismo é um comentário sobre **cores** dentro do `AppPanelProvider.php:104-126`, que ninguém encontra procurando por "brand".
- **Nota proposta**: valor de painel que pode mudar em tempo de execução (`brandName`, `favicon`, `brandLogo`, mídia do Auth Designer, `colors`) recebe `Closure`, nunca escalar. O escalar é resolvido na construção do `Panel` e congela — medido: `config()` alterado no `boot()` de um provider não muda `getBrandName()`. As assinaturas aceitam `Closure` (`Panel/Concerns/HasBrandName.php:12`, `HasFavicon.php:11`, `HasBrandLogo.php:16`).
- **Gates**: durável ✅ | escopável ✅ | não-inferível ✅ (escalar funciona e parece certo; a falha é "o valor novo não aparece", sem erro) | não-redundante ✅

### 3. Settings do spatie não se audita por trait

- **Glob**: `app/Settings/**`
- **Evidência**: `DatabaseSettingsRepository.php:56` (`create()`) contra `:74-77` (`upsert()`). A trilha por trait audita a criação e perde toda alteração, em silêncio.
- **Nota proposta**: classe de `app/Settings/` não é model Eloquent e não recebe `App\Traits\AuditsFillables`. Apontar `settings.repositories.database.model` para uma model com a trait audita só a criação: `updatePropertiesPayload()` usa `upsert()` (`vendor/spatie/laravel-settings/src/SettingsRepositories/DatabaseSettingsRepository.php:74-77`), que não dispara evento. A trilha sai de um listener de `SavingSettings`, com `auditable_type = SettingsProperty::class` e um `event` diferente de `updated` (senão a `RestoreAuditAction` corrompe a linha).
- **Gates**: durável ✅ (vale para as wikis de register e de login social, que vão criar mais settings) | escopável ✅ | não-inferível ✅ (o padrão do kit é a trait, e aplicá-la parece o certo) | não-redundante ✅

> Teto de 3 respeitado. Um quarto candidato foi **recusado**: "toda Page nova nasce com `HasPageShield`" — a branch `feat/permissoes-de-telas-e-acoes` está estabelecendo essa convenção e é dela que a rule deve sair, não desta.

---

## Retrospectiva

<!-- preenchido no fim -->
