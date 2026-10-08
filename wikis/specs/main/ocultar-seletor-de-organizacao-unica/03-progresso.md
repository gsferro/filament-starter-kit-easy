# Progresso — ocultar-seletor-de-organizacao-unica

**Estado**: em verificação

## 1. Chave de config + `.env.example`
- [x] `kit.tenancy.ocultar_seletor_unico` em `config/kit.php` com env `KIT_TENANCY_OCULTAR_SELETOR_UNICO` — `config/kit.php:'ocultar_seletor_unico':416`
- [x] Linha comentada em `.env.example` junto das `KIT_TENANCY_*` — `.env.example:192`

## 2. Propriedade no Settings + mapa
- [x] `public bool $ocultar_seletor_unico` em `App\Settings\ConfiguracoesDoKit` — `app/Settings/ConfiguracoesDoKit.php:$ocultar_seletor_unico:223`
- [x] Linha `ocultar_seletor_unico => kit.tenancy.ocultar_seletor_unico` em `mapaDeConfiguracao()` — `app/Settings/ConfiguracoesDoKit.php:'ocultar_seletor_unico':462`

## 3. Migration de settings
- [x] `database/settings/2026_10_08_100000_add_ocultar_seletor_unico_to_kit_settings.php` com `add`/`delete` — arquivo criado; CT-02 lê `false` gravado pela migration

## 4. `App\Support\SeletorDeOrganizacao`
- [x] `SeletorDeOrganizacao::visivel()` — curto-circuito desligado, defesa sem usuário, contagem por `Filament::getUserTenants()` — `app/Support/SeletorDeOrganizacao.php:visivel():46`
- [x] Log `debug` no channel `tenancy` só no ramo que esconde — `app/Support/SeletorDeOrganizacao.php:'tenancy':67`

## 5. `tenantMenu()` no AppPanelProvider
- [x] `->tenantMenu(fn (): bool => SeletorDeOrganizacao::visivel())` no bloco `kit.tenancy.enabled` — `app/Providers/Filament/AppPanelProvider.php:tenantMenu():622`

## 6. Toggle na aba Kit
- [x] `Toggle::make('ocultar_seletor_unico')->visible(config('kit.tenancy.enabled'))` após `rotulo_das_organizacoes` — `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:'ocultar_seletor_unico':986`

## 7. Capturas de arte
- [x] Cenários `captura o seletor de organização visível e oculto` e `captura o interruptor de ocultar o seletor na tela de configurações` em `tests/BrowserTenancy/CapturaDeArteTest.php` produzindo `seletor-organizacao-1-visivel`, `seletor-organizacao-2-oculto` e `admin-configuracoes-seletor` — `tests/BrowserTenancy/CapturaDeArteTest.php:'seletor-organizacao-1-visivel':911` e `CapturaDeArteTest.php:'admin-configuracoes-seletor':937`; 2 passaram com KIT_ART=1, PNGs em `tests/Browser/Screenshots/`

## 8. `KitArte` + docs
- [x] `CLIPES['seletor-organizacao']` e `IMAGENS[] = 'admin-configuracoes-seletor'` em `app/Console/Commands/KitArte.php` — `app/Console/Commands/KitArte.php:'seletor-organizacao':91` e `KitArte.php:'admin-configuracoes-seletor':143`
- [x] `docs/pt|en/recursos/configuracoes-do-kit.md` e `multi-tenancy.md` atualizados — seção "Um seletor para quem não tem o que trocar" / "A selector for people with nothing to switch to" nos 4 arquivos
- [x] Entrada no `CHANGELOG.md` — `CHANGELOG.md:10-17`

## Testes
- [x] `SeletorDeOrganizacaoTenancyTest` (CT-01 a CT-08, CT-10) — 16/16 verdes no `--filter=SeletorDeOrganizacao`
- [x] `SeletorDeOrganizacaoCampoTest` (CT-09 — suíte Kit, sem tenancy) — `assertFormFieldHidden` verde
- [x] `SeletorDeOrganizacaoArteTest` (CT-11 — varredura CLIPES × capturas) — verde

## Tickets
Não fatiado — 2026-10-08: 5 RQ vigentes, 11 CT, compactação: não, 0 perguntas de requisito abertas — nenhum sinal (uma suíte de predicado + um toggle; cabe numa sessão)

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff — Auditoria Ponytail (step 6) abaixo: net -0, lean already
- [x] `vendor/bin/pint --dirty` — clean
- [x] `vendor/bin/pest --filter=SeletorDeOrganizacao --compact` — 16/16
- [x] `KIT_ART=1 vendor/bin/pest tests/BrowserTenancy --filter=seletor` — 2 cenários passaram; `seletor-organizacao.gif` (44 KB) e `admin-configuracoes-seletor.png` + thumb publicados por `kit:arte`
- [x] Baseline `main` (comando ID 104): 4288 passaram, 3 falharam (`TemaEscuroTest`, acessibilidade — preexistente, não tocada pela feature), 25 pulados; suite final pós-diff: `vendor/bin/pest --parallel` (comando ID 316) — 6 falharam: 3 preexistentes + CT-56 e 2 contagens de README que o próprio diff invalidou, corrigidos e reverificados verdes
- [x] `pest --mutate --path=app/Support/SeletorDeOrganizacao.php` — 19 mutantes, 100% mortos, 0 sobreviventes (comando ID 291, `pestw.cmd`; Duration compatível com 49s de base × N)
- [x] **`/code-review high main...HEAD` + passe de eixos (step 9)** — sem sub-agente no host; passe de eixos em linha registrado abaixo
- [x] `php artisan kit:arte` — `seletor-organizacao.gif` montado dos dois quadros e `admin-configuracoes-seletor.png` + thumb publicados (art/)
- [x] `rastreabilidade.sh {wiki}` silencioso — após corrigir fence do passo 6 no `01` (abria em bullet, fechava em coluna 0, engolia os passos 7/8)
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [x] `citacoes.sh {wiki}` silencioso — símbolo exigido em toda citação e path curto só com o completo antes no doc
- [x] `ids-ct.sh {wiki} 'tests/**/SeletorDeOrganizacao*.php'` silencioso
- [x] `conformidade-rules.sh {wiki} main` silencioso
- [x] Docs pt/en, CHANGELOG reconciliados com o comportamento final — mesmas frases dos testes de doc
- [ ] `git commit`

## Revisão do Diff (step 9)

Sem sub-agente no host — passe de eixos em linha sobre `main...HEAD`:

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| R1 | Segurança | `visivel()` só governa UI: `canAccessTenant()`, `/app/{tenant}` e link direto não mudam; log traz `user_id` + contagem, nada sensível | — | P-03 | — |
| R2 | Consistência | Mesma forma das classes-dona (`CabecalhoDoPainel`, `RegistroAberto`): `final` estática, contrato config→settings→migration, `->visible()` igual `login_anti_robo_local` | — | P-01, P-04 | — |
| R3 | Simplicidade | Curto-circuito desligado sai sem query; contagem feita uma vez e reutilizada no log | — | P-03 | — |
| R4 | Desempenho | Ligado, `hasTenantMenu()` avalia no render da sidebar **e** da topbar — até 2× `getUserTenants()`/request | não-defeito | — | consulta barata e indexada; cache por request ficou fora (YAGNI, D4 do `01`) |
| R5 | Efeito colateral | CT-56 (extrator `KIT_*`) e contagens do `SiteDeDocumentacaoTest` medem a árvore — o diff os invalidou | implementação | — | corrigido: READMEs, badge e `mapaOptInDaGuarda()` |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

- **Ciclo**: 1 · **Veredito**: APROVADO COM DÉBITO (perfil Padrão — teto por construção; H não verificada, axe não rodado nos CT-B novos) · **Data**: 2026-10-08
- **Relatório**: `06-relatorio-qa.md`
- **Débito**: —

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-08 — solicitante — 1 rodada; perguntas: 0 fato, 0 desenho, 3 requisito (Q1–Q3 respondidas no chat com as recomendações aceitas; decisões de desenho D1–D5 registradas no `01`)

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `hasTenantMenu()` avaliado por request no render da sidebar e topbar | Confirmado: `vendor/filament/filament/resources/views/livewire/sidebar.blade.php:$hasTenantMenu:11` (`hasTenancy() && hasTenantMenu()`) e `vendor/filament/filament/resources/views/livewire/topbar.blade.php:hasTenantMenu():132` | nenhuma — plano correto |
| `tenantMenu(bool\|Closure)` existe e aceita Closure | Confirmado: `vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:tenantMenu():139-144`; `hasTenantMenu()` avalia em `HasTenancy.php:hasTenantMenu():344-347` | nenhuma |
| `.env.example` tem `KIT_TENANCY_*` comentadas | Confirmado: `KIT_TENANCY=false` ativa (linha 181) e `LABEL`/`LABEL_PLURAL`/`SLUG` comentadas (185-187) — a chave nova entra comentada junto delas | nenhuma |
| `gravarConfiguracao()`/`alinharConfiguracoesDoKit()` existem como helpers | Confirmado: `tests/Pest.php:gravarConfiguracao():349` e `Pest.php:alinharConfiguracoesDoKit():370` | nenhuma |
| `getUserTenants()` delega a `User::getTenants()` e devolve array | Confirmado: `vendor/filament/filament/src/FilamentManager.php:getUserTenants():626-635` | nenhuma |
| `SeletorDeOrganizacao` não existe | `Test-Path` → False | nenhuma |

#### Varredura da classe irmã (step 5)

- **Irmã**: `App\Support\CabecalhoDoPainel` (mesmo papel: classe-dona de decisão visual de painel).
- **Ocorrências**: `config/kit.php` (a chave), `app/Settings/ConfiguracoesDoKit.php` (propriedades/mapa), os três `*PanelProvider` (consome), `ConfiguracoesDoKit.php` da página (campos), `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` e `tests/Pest.php` (helpers), `tests/Kit/DiagramasDaArquiteturaTest.php` (citação em doc).
- **Pergunta aplicada à classe nova**: `SeletorDeOrganizacao` vale só para o painel `/app` (único com `->tenant()`), então o consumo em um provider só está correto — os três providers citam `CabecalhoDoPainel` porque o cabeçalho é dos três. Demais pontos são os mesmos do plano (config, settings/mapa/migration, página, teste). Nenhuma ocorrência além das previstas.

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | Nenhum corte: `SeletorDeOrganizacao` é o padrão do kit para decisão de config (irmãs `DensidadeDoLayout`/`CabecalhoDoPainel`/`RegistroAberto`) e o `--mutate` precisa do path; `admin-configuracoes-seletor` serve RQ-05; guarda `! $user` é 1 linha defensiva; demais passos são o contrato mecânico do settings | n.a. | net -0 — lean already |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| — | — | Sem despacho — host sem sub-agente (Windsurf); tudo roda em linha | — | — | — | — | — |

## Blockers
- [ ] nenhum

## Desvios do Plano

## Notas de Implementação

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-08
- `template-01-plano.md` — step 4 — 2026-10-08
- `template-02-adr.md` — step 4 — 2026-10-08
- `template-03-progresso.md` — step 4 — 2026-10-08
- `entrevista-tres-raias.md` — step 4 — 2026-10-08
- `padrao-de-log.md` — step 4 — 2026-10-08
- `pesquisa-step-3.md` — step 3 — 2026-10-07/08 (formato da Superfície Livewire e stack de testes; SKILL.md lido por inteiro)

## Retrospectiva
