# Progresso — Estudo e adoção de pacotes Filament, rodada 2

## 1. Avatar padrão local, com iniciais

- [x] `app/Support/AvatarDeIniciais.php` criado — implementa `Filament\AvatarProviders\Contracts\AvatarProvider`, 2026-09-18
- [x] `->defaultAvatarProvider(AvatarDeIniciais::class)` nos três `PanelProvider` — `Admin:95`, `App:106`, `Infra:117`, 2026-09-18
- [x] Escape do nome com `htmlspecialchars(..., ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE)`, 2026-09-18 *(alterado em 2026-09-18: achado 3 do `/code-review` — sem `ENT_SUBSTITUTE` a função devolve string vazia diante de byte UTF-8 inválido)*
- [x] Guarda do `?? Color::Gray[950]` para instalação sem a chave `gray` na paleta, 2026-09-18

## 2. Alerta de alterações não salvas

- [x] `config/kit.php` → `alerta_alteracoes_nao_salvas` com `BooleanoDoEnv::comPadrao(..., true)`, 2026-09-18
- [x] Propriedade em `ConfiguracoesDoKit` + linha em `mapaDeConfiguracao()`, 2026-09-18
- [x] `database/settings/2026_09_18_100000_add_alerta_alteracoes_nao_salvas_to_kit_settings.php`, 2026-09-18
- [x] `->unsavedChangesAlerts(fn (): bool => ...)` — **Closure**, nos três painéis, 2026-09-18
- [x] `Toggle` na aba Kit de `/admin/configuracoes-da-aplicacao`, 2026-09-18
- [x] `.env.example` → `KIT_ALERTA_ALTERACOES_NAO_SALVAS`, 2026-09-18
- [x] `phpunit.xml` → chave com `force="true"`, 2026-09-18

## 3. Versão no rodapé dos painéis

<!-- Reescrito pelo Adendo 2: era "versão do kit", passou a ser "versão do sistema". -->

- [x] `config/app.php` → `'version' => env('APP_VERSION')` *(alterado em 2026-09-18: adendo 2)*
- [x] `config/kit.php` → `exibir_versao` (interruptor da versão do kit), 2026-09-18
- [x] Propriedades `versao_do_sistema` e `exibir_versao_do_kit` + duas linhas no mapa, 2026-09-18
- [x] `database/settings/2026_09_18_110000_add_versao_do_sistema_to_kit_settings.php`, 2026-09-18
- [x] `resources/views/filament/versao-do-kit.blade.php` com guard `filament()->auth()->check()`, 2026-09-18
- [x] `ConfiguraFilamentGlobal::configuraVersaoNoRodape()` — render hook `FOOTER`, **sem `scopes:`**, 2026-09-18
- [x] `.kit-versao` em `resources/css/filament/kit.css`, sem declarar cor (herda o tema), 2026-09-18
- [x] `php artisan filament:assets` — `public/css/kit/kit-correcoes.css` republicado, 2026-09-18
- [x] `TextInput versao_do_sistema` (aba Identidade) e `Toggle exibir_versao_do_kit` (aba Kit), 2026-09-18
- [x] `.env.example` → `APP_VERSION=` e `KIT_EXIBIR_VERSAO=false`, 2026-09-18
- [x] `phpunit.xml` → as duas chaves com `force="true"`, 2026-09-18

## 4. `ativo` na trilha de auditoria

- [x] `AuditsFillables::auditaAlemDoFillable()` como ponto de extensão, 2026-09-18
- [x] `User::auditaAlemDoFillable()` → `['ativo', 'aprovacao_pendente']`, 2026-09-18
- [x] Confirmado que `Tenant`, `Projeto`, `Convite` e `AgenteIa` herdam o default vazio e não mudam de comportamento — `tests/Kit/FundacaoTest.php`, caso do par, 2026-09-18

## 5. Correção dos comentários sobre `USER_MENU_BEFORE`

- [x] `AdminPanelProvider`, `AppPanelProvider`, `InfraPanelProvider` — os dois blocos de cada um, 2026-09-18
- [x] `resources/views/filament/user-menu-header.blade.php` — o parágrafo do hook e o do fallback de avatar, 2026-09-18

## 6. Registro documental das dez decisões

- [x] `07-dossies-dos-pacotes.md` — dez pacotes, com nome Composer real, idade, stars, CI, mecanismo, risco e gatilho de reabertura, 2026-09-18
- [x] `wikis/pacotes-candidatos.md` — seção "Rodada 2 — 2026-09-18", 2026-09-18
- [x] `wikis/pacotes-ranking.md` — posição 70 corrigida (`zpmlabs/api-docs` não existe) + nota sobre os dez slugs errados, 2026-09-18

## 7. Documentação de usuário, CHANGELOG e Blueprint

- [x] `docs/pt/recursos/configuracoes-do-kit.md` — duas seções novas + tabela de abas, 2026-09-18
- [x] `docs/en/recursos/configuracoes-do-kit.md` — idem, 2026-09-18
- [x] `README.md` e `README.en.md` — contagem de features especificadas 57 → 58, 2026-09-18
- [x] `CHANGELOG.md` — entrada `[0.35.0]`, 2026-09-18
- [ ] Blueprint: `composer bp:on` → aderência → `composer bp:off`
- [ ] Bump de `config/kit.php` → `version` (é passo de **release**, não desta feature — ver Notas)

## 10. Atualizar o Filament para a versão corrente da série

<!-- Passo novo, do Adendo 1 (RQ-14/RQ-15/RQ-16), exigido pelo achado QA-01 do ciclo 1. -->

- [x] `composer update "filament/*" --with-all-dependencies` — **v5.7.6 → v5.8.2**, 2026-09-18
- [x] Constraint **mantida** em `^5.6` — RQ-14 proíbe travar, e subir para `^5.8` travaria quem está na 5.7, 2026-09-18
- [x] Assets republicados pelo `post-update-cmd` (`public/js/filament/**`, `public/fonts/filament/**`), 2026-09-18
- [x] ADR-08 registra o limite de RQ-16: o `kit:update` **notifica**, não propaga (`KitUpdate.php:299-306`, `:960-970`), 2026-09-18
- [x] `composer test` pós-bump — **2464/2464**, 9604 asserções, 2026-09-18
- [x] `composer test:browser` pós-bump — **61 verdes / 14 pulados / 0 falhas**, 2026-09-18

## 11. Correção do `(bool) env()` na exibição da versão do kit

<!-- Achado D2 da reconciliação (step 7), fechado no ciclo 2. -->

- [x] CT-09 escrito **antes** da correção e confirmado **vermelho** em `off`/`no`/`talvez`, 2026-09-18
- [x] `config/kit.php` → `BooleanoDoEnv::comPadrao(env('KIT_EXIBIR_VERSAO'), false)`, 2026-09-18
- [x] CT-09 verde depois da correção, 2026-09-18

## Testes

- [x] `tests/Kit/AvatarDeIniciaisTest.php` — 18 casos: domínio externo nos 3 painéis, iniciais (7 datasets), nome vazio, escape por parser XML, byte UTF-8 inválido, contraste, 2026-09-18
- [x] `tests/Kit/VersaoNoRodapeTest.php` — 12 casos: visitante nas 3 telas de login, tabela de decisão 2×2, elemento ausente, 3 painéis, guarda de "não lê `.git`", 2026-09-18
- [x] `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php` — 13 casos: liga nos 3 painéis, muda de resposta no mesmo processo, tela→painel, contrato de 3 lugares, `encrypted()`, precedência banco×`.env`, 2026-09-18
- [x] `tests/Kit/TrilhaDeEstadoDaContaTest.php` — 4 casos: `desativar`/`reativar`/`aprovacao_pendente` + contraprova, 2026-09-18
- [x] `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` — guarda da posição real dos dois hooks no vendor instalado, 2026-09-18
- [ ] Reconciliação de IDs `[CT-nn]` com o `04` — os testes foram escritos antes do `04` chegar
- [ ] `tests/Browser/*` — os 2 CT-B do `05`
- [x] `tests/Kit/FundacaoTest.php` — guarda de auditoria reescrita para o contrato novo, **em par** (um caso prova a extensão, outro prova que ela não vaza), 2026-09-18
- [x] `tests/Kit/KitInfoTest.php` — âncora de propriedades do settings 51 → 54, com o motivo no comentário, 2026-09-18

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent` — passed, 2026-09-18
- [x] `composer types:check` (phpstan level 7) — 0 erros, 2026-09-18
- [x] `composer filament:check` (filacheck) — 17/17 regras, 2026-09-18
- [x] `composer test:kit` — **2332/2332 verdes, 7821 asserções**, 2026-09-18
- [x] Testes desta feature — 51/51 verdes nos arquivos novos, 2026-09-18
- [x] **Falsificabilidade provada por `git stash`** — 22 de 49 casos vermelhos sem a implementação, 2026-09-18
- [x] `composer test` (pint + phpstan + filacheck + Unit,Feature,Kit,Tenancy) — **2464/2464, 9604 asserções**, já no Filament v5.8.2, 2026-09-18 *(alterado em 2026-09-18: a medição anterior, 2379/2379, era pré-bump)*
- [x] `composer test:browser` — **61 verdes / 14 pulados / 0 falhas**, no Filament v5.8.2, 2026-09-18
- [ ] `composer test:browser` — CT-B
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] **Custo medido** — queries do caminho principal contra o `## Modelo de Execução` (esperado: 0 query nova)
- [ ] `composer bp:on` → aderência → `composer bp:off`, com `composer.json` commitado desligado
- [x] **`/code-review` no diff (step 7.5)** — 3 achados, 3 fechados, 2026-09-18
- [ ] Citações `arquivo:símbolo:linha` reverificadas
- [ ] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa
- [ ] `git commit`

<!-- Cada [x] acima leva " — {evidência}, {data}". -->

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | aplicada | Nenhum `assignRole()`/`syncRoles()` novo; nenhum DTO novo |
| `filament.md` | `app/Filament/**` | aplicada | Nenhum Resource, Page ou Widget novo → nenhuma permission nova; `filacheck` 17/17 |
| `pages.md` | `app/Filament/Admin/Pages/**` | aplicada | Campos novos entram no `form()` existente; nenhum segredo acrescentado, logo `mutateFormDataBeforeFill()` intocado |
| `settings.md` | `app/Settings/**` | aplicada | As três chaves são lidas **por request** (Closure no painel, `config()` na blade), nunca no boot. Contrato de três lugares cumprido: propriedade + `mapaDeConfiguracao()` + migration **nova** |
| `config.md` | `config/**` | aplicada | `BooleanoDoEnv::comPadrao()` nas **duas** chaves novas. A redação anterior desta linha dizia *"`(bool) env()` só na de default `false`, com o motivo escrito"* e **virou falsa** no ciclo 2: o motivo escrito cobria a partição vazia × ausente e não a do vocabulário (`off`, `no`), e `off` ligava a exibição. Ver o passo 11 |
| `providers-filament.md` | `app/Providers/Filament/**` | aplicada | Nenhum plugin novo; nenhuma resolução de `Plugin::get()` no boot |
| `providers.md` | `app/Providers/**` | aplicada | Render hook global sem `scopes:`, no padrão já documentado de `configuraBotaoVoltarAoTopo()` |
| `models.md` | `app/Models/**` | aplicada | `auditaAlemDoFillable()` não altera `$fillable` nem `$casts` |
| `css-filament.md` | `resources/css/filament/**` | aplicada | `.kit-versao` é classe do kit, não utilitária de vendor; `filament:assets` rodado |
| `views.md` | `resources/views/**` | aplicada | Nenhuma utilitária Tailwind na blade nova |
| `specs.md` | `wikis/specs/**` | aplicada | Toda afirmação sobre vendor tem `arquivo:linha`, e as que sustentam decisão foram reverificadas no `vendor/` local |
| `testes.md` | `tests/**` | aplicada | Helpers cruzados declarados em `tests/Pest.php`, não em arquivo de teste; `group('kit')` em todo caso; nenhum `const` global |
| `general.md` | `composer.json` | **n.a.** | `composer.json` **intocado** — é o resultado central da rodada |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: 1 · **Veredito**: **REPROVADO → especificação** · **Data**: 2026-09-18
- **Relatório**: `06-relatorio-qa.md`
- **Contagem**: Blocker 1 · Major 3 · Minor 4 · Cosmético 1
- **Medido pelo gate** (não pelo implementador): `Kit,Tenancy --parallel` 2446/2446 · `Browser` 75 casos, 61 verdes / 14 pulados / 0 falhas · os 8 arquivos da feature 181/181

### Achados abertos

| # | Achado | Sev. | Destino |
|---|---|---|---|
| QA-01 | RQ-15 não entregue: Filament 5.8.2 publicado, kit em 5.7.6, `composer.json`/`.lock` intocados | **Blocker** | 2 |
| QA-02 | `01-plano-acao.md` nunca reconciliado com o Adendo 2 (sem linha para RQ-13…RQ-18; passo 3 e env/UI defasados) | Major | 1 |
| QA-03 | Docblock de `configuraVersaoNoRodape()` e cabeçalho da CSS ainda dizem `config("kit.version")` | Major | 1 |
| QA-04 | RQ-16: `kit:update` nunca propaga `composer.json` (`KitUpdate.php:299-306`, `:960-970`) | Major | 1 |
| QA-05 | `User.php:841-845` citada 4× para `getFilamentAvatarUrl()`, que está em `:857-862`; `01` passo 4 cita `:291` em vez de `:307` | Minor | 1 |
| QA-06 | Referências cruzadas de ADR trocadas (ADR-03 ↔ ADR-02; ADR-05 ↔ ADR-03) | Minor | 1 |
| QA-07 | As 10 perguntas do `04` nunca viraram Adendo 3 no `00` | Minor | 1 |
| QA-08 | Verificação Final aberta: ciclo do Blueprint (RQ-11) e `ponytail-review` | Minor | 2 |
| QA-09 | `versao-do-kit.blade.php` renderiza primariamente a versão do sistema | Cosmético | 1 |

### Débitos aceitos

- Dimensão H: o rodapé novo não é coberto por `assertNoAccessibilityIssues()` — some-se à DT-12.
- Pergunta nº 10 do `04`: barreira separada de leitura e de escrita na tela de configurações.

> **L1 medida em estado transitório**, com o `04` sendo reescrito por outro agente no mesmo
> minuto. 19 IDs só no teste, nenhum só no `04`/`05`. Reconferir no ciclo 2.

> **Os 3 achados do `/code-review` foram conferidos e procedem** — não recontados como novos.

### Ciclo 2 — 2026-09-18

- **Ciclo**: 2 · **Veredito**: **REPROVADO → especificação** · **Data**: 2026-09-18
- **Relatório**: `06-relatorio-qa.md` › `# Ciclo 2` (o Ciclo 1 foi preservado)
- **Contagem**: Blocker 0 · Major 5 · Minor 4 · Cosmético 0
- **Medido pelo gate, já com o Filament v5.8.2**: `Kit,Tenancy --parallel` **2461/2461**
  (9600 asserções, 0 falhas) · os 8 arquivos da feature **196/196** (1977 asserções)

**Fechados no ciclo 1**: QA-01 (Blocker), QA-03, QA-04, QA-06, QA-07.
**Parciais**: QA-02 → QA-12 · QA-05 → QA-18. **Aberto**: QA-08 → QA-15.

| # | Achado | Sev. | Destino |
|---|---|---|---|
| QA-10 | O bump para v5.8.2 moveu 9 âncoras de `vendor/` sob as citações — 5 arquivos de produção e 6 de wiki | **Major** | 1 |
| QA-11 | O `04` ainda diz CT-09 "sem teste" e M11 vivo; CT-46/CT-47 fora do Índice; `[CT-38]` em 2 arquivos | **Major** | 1 |
| QA-12 | QA-02 fechado pela metade: passo 3, `## Superfície de UI` e `## Variáveis de Ambiente` seguem pré-Adendo 2; RQ-20 aponta um passo que não fala de rótulo | **Major** | 1 |
| QA-15 | O `03` não registra o passo 10 nem a correção de `config/kit.php`; a linha `config.md` da tabela de rules virou falsa; `composer test` registrado é pré-bump | **Major** | 1 |
| QA-16 | A premissa nº 2 do `00` (rodapé não renderiza nada) é negada pelo código, por CT-01/CT-47 e pelas docs pt/en | **Major** | 1 |
| QA-13 | A v5.8.2 mudou as iniciais do `UiAvatarsProvider`; ADR-03 e o docblock ainda afirmam paridade | Minor | 1 (e 3) |
| QA-14 | CT-47 mantém o oráculo fraco que CT-46 perdeu; e CT-46/CT-01/CT-08 fixam forma e string que o Adendo 3 não fixa | Minor | 3 |
| QA-17 | O `composer update` moveu 18 pacotes, 5 fora da família Filament; CHANGELOG, `01` e ADR-08 dizem "nove irmãs" | Minor | 1 |
| QA-18 | `02:329` (ADR-05 › Referências) ainda cita `User.php:291` em vez de `:307` | Minor | 1 |

> **L1 reconferida** (o ciclo 1 a deixou em estado transitório): o `04` agora tem o problema
> inverso — IDs no teste que o Índice não registra (CT-46, CT-47) e um Índice que descreve o
> estado anterior à correção. Ver QA-11.

> **Os três achados encontrados e corrigidos pelo solicitante foram reavaliados**: os três
> procedem; o de CT-46 fechou só metade da classe. Ver `06 › Ciclo 2 › Os três achados`.

### Ciclo 3 — 2026-09-18 · **TETO DA SKILL**

- **Ciclo**: 3 (último) · **Veredito**: **REPROVADO → especificação · ESCALAR AO USUÁRIO** · **Data**: 2026-09-18
- **Relatório**: `06-relatorio-qa.md` › `# Ciclo 3` (os Ciclos 1 e 2 foram preservados)
- **Contagem**: Blocker 0 · Major 6 novos + 1 carry-over (QA-12) · Minor 2 novos + 3 carry-over · Cosmético 1
- **Medido pelo gate**: `Kit,Tenancy --parallel` **2461/2461** (9600 asserções, 0 falhas) ·
  `VersaoNoRodapeTest` **46/46** (1485 asserções) · **5 mutantes manuais** no blade do rodapé,
  aplicados e revertidos (sem PCOV/Xdebug, `--mutate` indisponível nos três ciclos)

**Fechado no ciclo 2**: QA-15. **Parciais**: QA-10 → QA-19 · QA-11 → QA-20/QA-21 ·
QA-14 → QA-22 · QA-16 → QA-23. **Abertos sem avanço**: QA-08, QA-12, QA-13, QA-17, QA-18.

| # | Achado | Sev. | Destino |
|---|---|---|---|
| QA-19 | A remediação de QA-10 corrompeu 2 citações (`UiAvatarsProvider.php:27`, em `01:228` e `02:182`) e deixou 6 intactas. As 12 âncoras do vendor conferem | **Major** | 1 |
| QA-20 | `04:1829-1831` e `VersaoNoRodapeTest.php:457-480` ainda dizem que CT-09 não tem caso e que M11 está vivo — 250 linhas acima dos dois casos verdes | **Major** | 1 |
| QA-21 | Cabeçalho do `04` (45 CT, 13 regras, 62 mutantes) e Mapa de Regras (sem R14) pré-Adendo 3; CT-46/CT-47 ainda fora do Índice | **Major** | 1 |
| QA-22 | A fraqueza do oráculo de RQ-20 migrou do texto para a adjacência: CT-47 deixa M65 sobreviver (46/46 verde com a versão do kit sem rótulo); CT-46 e CT-08 reprovam rótulo sufixado que RQ-20 permite | **Major** | 3 |
| QA-23 | O `00:105-118` fechou a premissa nº 2 e passou a citar as docs pt/en como prova — elas dizem o oposto (`docs/{pt,en}/.../configuracoes-do-kit.md:41`) | **Major** | 1 |
| QA-24 | `## Impacto em Features Existentes`, `## Rollback` e `## Dependências` do `01` não cobrem o passo 10 — a maior superfície do branch | **Major** | 1 |
| QA-25 | `04:103` (SFDIPOT › Platform) ainda deriva os cenários do Filament 5.7.6 | Minor | 1 |
| QA-26 | `pacotes-candidatos.md:590` e `02:463` ainda dão "kit em 5.7.6" como gate decisivo do `page-header`; o `07-dossies:71` já riscou o motivo | Minor | 1 |
| QA-27 | `composer test:browser` duas vezes na Verificação Final, `[x]` e `[ ]` | Cosmético | 1 |

> **Teto atingido.** A skill não abre ciclo 4. O que escalar está em
> `06 › Ciclo 3 › O que ESCALAR ao solicitante`: a frase das docs pt/en, se a posição do rótulo é
> requisito, RQ-11 (Blueprint) e o alcance do bump.

> **Padrão de processo, nos três ciclos**: a remediação fecha o ponto citado e não a classe —
> QA-05 → QA-18 → QA-19 (citações), QA-02 → QA-12 → QA-12 (passo 3), QA-14 → QA-22 (oráculo de RQ-20).

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa | O código real diz | Correção aplicada |
|---|---|---|
| "o provider padrão do Filament é `UiAvatarsProvider` e o kit não sobrescreve" | ✔ confirmado: `HasAvatars.php:10`; `grep -rn "defaultAvatarProvider" app/` vazio | nenhuma — premissa correta |
| "`unsavedChangesAlerts` nasce `false` e o kit não liga" | ✔ confirmado: `HasUnsavedChangesAlerts.php:9`; `grep` em `app/` vazio | nenhuma |
| "`ativo` não é auditado" | ✔ confirmado: `AuditsFillables.php:21-23` + `User.php:89-94` (`ativo` em `$attributes`, `:106-108`) | nenhuma |
| "`USER_MENU_BEFORE` renderiza fora do dropdown" | ✔ confirmado: `user-menu.blade.php:43` vs. `<x-filament::dropdown>` em `:40`; `USER_MENU_PROFILE_BEFORE` em `:97`, `:110`, `:133`, `:148` | nenhuma |
| "o kit está em Filament 5.7.6 e o page-header exige ^5.8.1" | ✔ confirmado no `composer.lock` | nenhuma |
| "`config('kit.version')` é a tag do release, logo serve ao RQ-02" | **errado** — é a tag **do kit**, não a do produto | RQ-02 substituída por RQ-17 no **Adendo 2**; passo 3 reescrito; ADR-04 reescrita |
| "`Panel::configureUsing()` resolveria os dois registros num lugar só" | `Component` usa `Configurable`, mas `PanelProvider::register()` monta o painel na fase de **register** e `configuraFilamentGlobal()` roda no **boot** — a ordem não foi provada | recusada: os dois ficam explícitos nos três providers, que é o padrão já usado por `->colors()` e `->favicon()` |

### Varredura da classe irmã (obrigatória para toda classe nova)

Classe nova: `App\Support\AvatarDeIniciais`. Irmã escolhida: **`App\Support\CorPrimaria`** — mesmo
papel (classe de suporte consumida pelos três `PanelProvider` numa cadeia fluente, sem estado, sem
registro em container).

```bash
grep -rn "App\\\\Support\\\\CorPrimaria\|CorPrimaria::" --include=*.php app config database tests
```

Ocorrências da irmã, e a decisão para a nova em cada uma:

| Onde `CorPrimaria` aparece | A nova entra? |
|---|---|
| `app/Providers/Filament/*PanelProvider.php` (3×, no `use` e na cadeia) | **sim** — feito |
| `tests/Kit/CorPrimariaTest.php` (teste unitário próprio) | **sim** — pendente, sai do `04` |
| `config/kit.php` (comentário citando a classe) | não — o avatar não tem chave de config |
| `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` | não — não há campo a configurar |
| `app/Console/Commands/KitInfo.php` | não — não é valor de instalação |

**Nenhuma lista paralela encontrada**: `config/filament-shield.php` não a menciona (não é Page nem
Resource), e não há seeder, inventário de teste nem `->pages()`/`->widgets()` que precise dela.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | Pendente: `/ponytail:ponytail-review` ainda não rodou sobre o diff | — | — |

## Revisão de Código do Diff (step 7.5)

`/code-review` no nível `high`, por quem não implementou. **Três achados, os três confirmados e
fechados.** Nenhum deles virou Adendo no `00`: nenhum muda o que a feature promete ao requisito —
o primeiro corrige uma promessa que **eu** escrevi na documentação, e os outros dois são defeito de
implementação e de cobertura.

| # | Achado | Severidade | Destino | Como fechou |
|---|---|---|---|---|
| 1 | `versao_do_sistema → app.version`: `aplicarNaConfig()` sobrescreve `config('app.version')` com o valor do banco **sem guarda de nulo**, então `APP_VERSION` editada depois da instalação é ignorada em silêncio | média | **especificação** (documentação) | docs pt/en e `helperText` reescritos; 2 CTs novos fixando o par |
| 2 | O caso "mostra a versão nos três painéis" tinha só `admin` e `infra` — justamente o `/app`, o painel com tenancy, ficava fora do mutante de escopo que ele diz matar | baixa | **teste** | dataset completado com `app` |
| 3 | `htmlspecialchars` com flags explícitas e **sem** `ENT_SUBSTITUTE` devolve string vazia diante de byte UTF-8 inválido em `users.name` | baixa | **implementação** | flag acrescentada; 1 CT novo |

### O achado 1, por extenso

`aplicarNaConfig()` faz `$novo[$chave] = $this->{$propriedade}`
(`app/Settings/ConfiguracoesDoKit.php:483-484`), sem tratar nulo — e isso **é contrato**, fixado por
`ConfiguracoesDoKitTest`, caso *"zera a chave de configuracao quando a propriedade e limpada"*.

Consequência para esta feature: numa instalação já feita com o campo em branco, escrever
`APP_VERSION=2.4.1` no `.env` não muda o rodapé, porque o `null` do banco vence. **E a
documentação que esta entrega escreveu mandava o operador fazer exatamente isso no script de
deploy.**

**A correção foi na documentação, não no comportamento**, e a escolha tem motivo: o comportamento é
a regra universal do kit (*"o banco vence em tempo de execução; o `.env` semeia e é o plano B"*) e
é a opção que o solicitante escolheu no Adendo 2. Abrir exceção para uma chave seria a
inconsistência — e seria invisível, porque todas as outras continuariam com a regra antiga.

**Débito aberto, para decisão do mantenedor**: deploy automatizado não tem caminho de escrita da
versão — só a tela. Um `php artisan kit:versao 2.4.1` fecharia, e é superfície nova que não cabia
nesta entrega.

### Sobre a suíte verde não ter pego o achado 1

O revisor registrou isto, e vale repetir aqui: `tests/Kit` fechou **2022/2022** durante a revisão, e
isso **não** era evidência contra o achado — nenhum caso cobria o cenário *"o `.env` mudou depois
que a linha de settings foi semeada"*. Era precisamente a lacuna, e é o que os dois CTs novos
fecham.

### Limitação do método, registrada

`git diff main...HEAD` veio **vazio**: a revisão rodou com a árvore de trabalho suja, sobre
`git diff HEAD` mais os arquivos não rastreados. Funcionou, mas o certo é commitar antes de
disparar o gate — um arquivo novo esquecido no `.gitignore` teria passado despercebido.

## Blockers

- Nenhum.

## Desvios do Plano

1. **A implementação precedeu o design dos casos de teste.** O fluxo da skill manda invocar
   `feature-test-design` no step 4, antes de escrever código; aqui os cinco itens foram
   implementados e só então o `04`/`05` foram derivados. **Degradação declarada**, com a mitigação:
   a derivação recebeu o `00-requisito.md` como oráculo e instrução explícita de **não** usar o
   código como fonte de comportamento. O risco residual — cenário que confirma o que o código faz
   em vez do que o requisito pede — é o que o step 7.5 e o quality gate existem para pegar.
2. **`aprovacao_pendente` entrou junto de `ativo` no passo 4.** O escopo aprovado dizia "auditoria
   de `ativo`". `aprovacao_pendente` é **o mesmo defeito na mesma linha**: estado de fronteira de
   acesso, fora do `$fillable` pelo mesmo motivo, e igualmente invisível na trilha. Entregar só
   metade seria deixar um defeito conhecido no lugar. Registrado aqui por ser ampliação de escopo,
   ainda que mínima; ADR-05 explica.
3. **Duas migrations de settings em vez de uma.** O plano previa uma; o Adendo 2 acrescentou duas
   propriedades depois que a primeira já existia. Separar é mais seguro que reeditar — e a regra
   de `ConfiguracoesDoKit` (*"a migration é NOVA, nunca a que já rodou"*) vale mesmo quando a
   anterior ainda não rodou em lugar nenhum.
4. **O README precisou de edição não prevista.** `tests/Kit/SiteDeDocumentacaoTest.php` conta os
   `00-requisito.md` da árvore e compara com um número nos dois READMEs: criar a wiki subiu de 57
   para 58. Funcionou como projetado — a guarda ficou vermelha e obrigou a decisão.

## Notas de Implementação

- **`Panel::configureUsing()` não foi usado, e vale registrar por quê.**
  `Filament\Support\Components\Component` usa o concern `Configurable`, e `Panel extends Component`
  — então `Panel::configureUsing()` existe. Mas `PanelProvider::register()` monta o painel na fase
  de **register** do Laravel, e `configuraFilamentGlobal()` roda no **boot**: provar que a ordem
  funciona exigiria medir. Os dois registros ficaram explícitos nos três providers, que é o padrão
  que `->colors()`, `->favicon()` e `->brandName()` já usam. Menos esperto e verificável por leitura.
- **O render hook `FOOTER` é emitido por dois layouts**, e isso não estava no plano inicial:
  `layout/index.blade.php:126` (telas do painel) e `layout/simple.blade.php:61` (login, registro,
  recuperação de senha). É a razão de o guard de visitante existir, e ele é o ponto mais frágil do
  passo 3 — daí `filament()->auth()->check()` em vez de `@auth`, que consultaria o guard default.
- **A CSS da versão não declara cor de propósito.** Herda a do tema e aplica só `opacity`, o que a
  faz funcionar no claro e no escuro com **uma** regra — e evita a armadilha que o CHANGELOG da
  0.34.2 documenta, em que uma sobrescrita com especificidade suficiente para vencer no tema claro
  vence também no escuro, porque a regra escura do Filament usa `:where()`.
- **`config/kit.php` → `version` NÃO foi bumpado.** Ele é marca de nascença escrita pelo
  `KitUpdate::marcarVersao()` no projeto consumidor, e no repositório do kit é passo de release
  (`:bookmark: chore(release)`), não de feature. O `CHANGELOG.md` já traz a seção `[0.35.0]`.

## Retrospectiva

- **Funcionou bem**: exigir o nome Composer real e a constraint real do `composer.json` de cada
  pacote logo no prompt dos sub-agentes. Os dez slugs estavam errados, e duas das dez recusas
  (page-header por `^5.8.1`, app-version por `php ^8.4`) só existem porque a constraint foi lida em
  vez de presumida.
- **Funcionou bem**: mandar os sub-agentes lerem o vendor em vez da descrição do card. Os três
  defeitos do próprio kit corrigidos nesta entrega vieram disso, não da lista de pacotes.
- **Faltou no plano**: o plano tratou "a versão" como uma coisa só. Eram duas — a do kit e a do
  produto — e a distinção só apareceu quando o solicitante a apontou. Uma pergunta no step 3
  (*"versão de quê, exatamente?"*) teria evitado reescrever o passo 3, a ADR-04 e a blade.
- **Faltou no plano**: prever que criar a wiki quebraria a contagem do README. É guarda conhecida
  do repositório e devia estar na checagem de impacto.
