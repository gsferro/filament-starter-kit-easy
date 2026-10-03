# Progresso — feat/logo-dark-mode

**Estado**: em implementação — código e testes verdes, faltam steps 9–12

## 1. Migration `logo_dark` em `tenants` + Settings migration
- [x] `database/migrations/2026_10_03_100000_add_logo_dark_to_tenants_table.php` — CT-04 verde (`fresh()->logo_dark` persiste)
- [x] `database/settings/2026_10_03_100000_add_logo_dark_e_unifica_marca_to_kit_settings.php` — CT-05/06 verdes

## 2. `Tenant` model + `TenantForm`
- [x] `logo_dark` fillable + `urlDaLogoEscura()` — CT-01..04 verdes
- [x] `FileUpload` `logo_dark` visível no modo separado — CT-09 verde

## 3. `config/kit.php` + `.env.example` + Settings class
- [x] `identidade.logo_dark` + `identidade.unifica_logo_marca` (env `KIT_UNIFICA_LOGO_MARCA`) — CT-05 verde
- [x] Propriedades + `mapaDeConfiguracao()` em `app/Settings/ConfiguracoesDoKit.php` — CT-06 verde (config alinha)

## 4. `IdentidadeDoKit`
- [x] `logoEscura()` + `unificaLogo()` — CT-07, CT-16..18 verdes
- [x] *(alterado em implementação)* `doDisco()` passou de `$disco->url()` para `asset()` — host do request, não `APP_URL` congelado; bug upstream do mesmo tipo que `urlDaLogo()` já corrigia (quebrava o browser test em `127.0.0.1:porta`). Mata a lacuna M4.

## 5. Aba Identidade da página de settings
- [x] `Toggle` `unifica_logo_marca` `->live()` — CT-08 verde
- [x] `logo_dark` visível/obrigatório-entre-si + helpers light/dark — CT-10..13 verdes

## 6. `darkModeBrandLogo` nos 3 PanelProviders
- [x] — CT-17/18 verdes (`fi-logo-dark` no `/admin/login`)

## 7. `TelaBloqueio` — resolução por variante
- [x] `media` = light (`tenant.logo ?? kit.logo`) — CT-14/16 verdes
- [x] `urlsDasLogos()` público para a view + `organizacaoResolvida()` memoizado com `once()`; motivos de log `painel_sem_tenancy`/`sem_tenant`/`sem_logo` preservados — `IdentidadeVisualTest` 74/74 na regressão

## 8. Override da partial de mídia + CSS do swap
- [x] `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php` — classes nativas `fi-logo`/`fi-logo-light`/`fi-logo-dark` + `object-fit:contain`
- [x] CSS do swap: **nenhum** — as regras nativas do Filament já cobrem (verificadas em `public/css/filament/filament/app.css`); `kit.css` não tocado

## 9. Screenshots light/dark
- [x] *(alterado em implementação)* `art/logo-tema-{claro,escuro}.png` — convenção real das docs é `art/` + URL raw.githubusercontent, não `docs/{idioma}/assets/` (capturados pelo CT-B01 em `screenshotElement`)

## 10. Documentação pt + en
- [x] Seção "Logo da marca: uma só, ou duas por tema" / "Brand logo: one, or two by theme" nos dois idiomas — CT-20 verde (pt + en)

## 11. `LogoDarkModeTest`
- [x] `tests/Kit/LogoDarkModeTest.php` — 24 casos, 114 asserções, 0 falhas
- [x] `tests/Browser/LogoDarkModeTest.php` — CT-B01 verde, 9 asserções (duas visitas, uma por tema)

## 12. Finalização
- [x] `filacheck --fix` — 17 regras ok
- [ ] `pint`, commits, PR

## Testes
- [x] CT-01 a CT-20 — `tests/Kit/LogoDarkModeTest.php` — **24/24 verdes**
- [x] CT-B01 — `tests/Browser/LogoDarkModeTest.php` — **verde** (9 asserções)
- [x] Regressão vizinha: `IdentidadeVisualTest` + `ConfiguracoesDoKitTest` + `ConfiguracoesDoKitTelaTest` — **74/74**
- [x] M4 resolvido upstream — `asset()` fix (não mais lacuna)

## Tickets
Não fatiado — a feature cabe numa sessão (12 passos pequenos, sem dependência externa); `/feature-tickets` não invocada

## Verificação Final
- [x] Revisão do diff (step 9) — linha de leitura no lugar de `/code-review` sub-agente (host sem sub-agente); achados na tabela abaixo
- [x] `vendor/bin/pint --dirty` — 5 arquivos formatados, 14 conferidos
- [x] `vendor/bin/pest --filter=LogoDarkMode --compact` — 24/24 (114 asserções)
- [x] `vendor/bin/pest tests/Browser --filter=LogoDarkMode` — CT-B01 verde (9 asserções)
- [x] Suíte `tests/Kit` completa — **fechada** (serial, 1544 s + paralela, 292 s): 3348 passaram / 9 falharam / 3 pulados. Classificação das falhas: **4 nossas, corrigidas** — badge `casos de teste` 1.837→1.855 (2 readmes), `IdentidadeDoKitTest` ×3 (oráculo `Storage::url()` → `asset()`), `KitInfoTest` CT-06 55→57 propriedades, contagem de arquivos 171/198→172/200 (2 readmes); **4 pré-existentes, mantidas** — CT-25 pt/en (rótulo `kit 0.42.1` velho nos docs), números objetivos dos readmes, e CT-26 residual com 16 citações de *chamadas* (`->enableRateLimit(`, `BODY_END`, `env('KIT_TENANCY')`) que o detector de declarações nunca achou — vermelho em `main`, baseline estava incompleta
- [x] **Custo medido**: stats de disco no render — 1 `exists()` por variante resolvida + 1 `Tenant::find` memoizado por `once()`; nenhuma query em loop (dimensão E do `06`)
- [x] Revisão do diff + eixos (step 9) — ver `## Revisão do Diff`
- [x] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em implementação)*`
- [x] `rastreabilidade.sh {wiki}` — 3 linhas: RQ-11/RQ-12 rejeitadas (processo, QA-02), P-04 corrigida (QA-01)
- [x] `checkbox-sem-evidencia.sh {wiki}` — exit 0 após evidências coladas
- [x] Docs pt/en reconciliados; CHANGELOG e README: não tocados (sem release ainda)
- [x] `git commit` — `✨ feat(identidade)`; PR aguardando `git push` do solicitante

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 | implementação | `doDisco()` devolvia `$disco->url()` — URL congelada em `APP_URL` quebra a `<img>` em host divergente (o browser test `127.0.0.1:porta` a mostrava quebrada) | 2 — corrigido upstream no mesmo diff: `asset('storage/'.$caminho)` | CT-B01 (evidência visual) · mata a lacuna M4 do `04` | — |
| RD-02 | implementação | `it()` sem `[CT-nn]` no nome (convenção da casa) | 1 — prefixos aplicados | QA-03 | — |
| RD-03 | suspeita | `mediaAlt` = nome da organização quando a imagem exibida é a logo do KIT (org sem logo, kit com logo) | — | — | rejeitado: alt nomeia a marca que a pessoa desbloqueia (P-05), não o dono do arquivo — comportamento correto |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| *(conformidade-rules.sh exit 0 — nenhuma rule de `.ai/rules` casou os paths do diff com pendência declarada)* | — | n.a. | `bash .claude/skills/feature-wiki/scripts/conformidade-rules.sh {wiki} main` → silêncio |

## Quality Gate

- **Ciclo**: 1 · **Veredito**: `APROVADO COM DÉBITO` · **Data**: 2026-10-03
- **Relatório**: `06-relatorio-qa.md`
- **Débito**: E sem contagem de queries medida · H sem axe · revisão adversarial do `04` não feita (host sem sub-agente) · independência degradada (mesma sessão)

## Candidatos a Rule

- [x] **`asset()` em vez de `Storage::url()` para URL de arquivo público consumida por `<img>`** — **gravada** em `.ai/rules/app.md` ("URL pública de arquivo do disco"): terceira recorrência do bug de host divergente no repo (`urlDaLogo`, `urlDaLogoEscura`, `doDisco`) — durável + não-inferível + recorrente
- [x] **`inDarkMode()`/`inLightMode()` só valem no load da página** (pest-plugin-browser) — **gravada** em `.ai/rules/testes-browser.md` ("uma visita por tema"): trap não-óbvia que custou um ciclo de debug do CT-B01

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-03 — solicitante (resposta "siga as recomendações" a Q1–Q6) — 1 rodada; perguntas: 0 fato (pesquisa fechou tudo), 2 desenho (Q5, Q6), 4 requisito (Q1–Q4)

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| Helper condicional via `->helperText(fn (Get) => …)` encadeado no `arquivo()` | Docblock do `arquivo()` (app/Filament/Admin/Pages/ConfiguracoesDoKit.php:arquivo():998 — docblock nas linhas acima): helperText vem **por argumento** porque encadeado sobrescreve o sufixo teto/SVG | D7 novo; passo 5 e análise do arquivo corrigidos — ajuda estática por modo |
| `darkModeBrandLogo` nos painéis como única superfície de login | O oráculo de login existe: `page.simple`/`header/simple` emite `x-filament-panels::logo` → `fi-logo-dark` no HTML (verificado em vendor) | Passo 11/análise: CT do login asserta `fi-logo-dark`; RQ-11 viável sem tocar o login |
| `instanceof TelaBloqueio` na partial bastaria | A mídia da lock-screen pode ser **arte** (sem logo) — `instanceof` trataria a arte como logo com `contain` | Passo 7/8: `urlsDasLogos()` público memoizado + ramo por `filled($logos['clara'])`; arte sempre cai no markup do vendor |
| Classes `.kit-logo-*` novas em `kit.css` | `fi-logo`/`fi-logo-light`/`fi-logo-dark` já têm regra de swap no `app.css` publicado (verificado) | D4 revisado; passo 8 com zero CSS novo, `margin:0` inline nas imgs |
| Baseline de `main` não registrada | `composer test:kit` em `main`: **3799 passaram, 3 falharam, 3 pulados** — `SiteDeDocumentacaoTest`: números dos readmes, CT-25 rótulo de versão (pt e en) | `## Verificação Final` do 01 com a baseline nominal |

#### Varredura de classes irmãs (step 5)
- `urlDaLogo()` também é consumido por `TenantHeader`/`TenantInfolist` (exibição) — inalterados; `logo_dark` não aparece em nenhum dos dois.
- `partials.media` é incluída só por `layouts/auth.blade.php` (uma chamada); todas as páginas com `HasAuthDesignerLayout` (`TelaDoisFatores`, `RegistroPorConvite`, `CadastroUnificado`, `TelaLogin`, `TelaLoginUnificada`, `TelaRecuperarSenha`, `TelaRecuperarSenhaUnificada`) caem no ramo do vendor porque não implementam `urlsDasLogos()`.

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `instanceof TelaBloqueio` → `method_exists($livewire, 'urlsDasLogos')` — desacopla a view do FQCN e abre o ramo para qualquer página que implemente o par | Sim | Passo 8 do 01, ADR-01 |
| 2 | Arquivo de CSS novo/`kit.css` para classes próprias | Sim — eliminado pelo D4 revisado (classes nativas + `margin:0` inline) | D4, passo 8 |
| 3 | Segundo screenshot EN separado — a mesma PNG duplicada nos dois idiomas basta (artefatos) | Sim | D5, passo 9 |
| 4 | Sem cache extra além de `once()` por request | Sim — mantém o padrão do `IdentidadeDoKit` (ponytail já declarado na classe) | Passo 7 |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|

## Blockers

- [ ] Nenhum até aqui

## Desvios do Plano

- **`doDisco()` corrigido upstream**: `$disco->url()` → `asset()` — bug pré-existente que o browser test evidenciou (host divergente); resolve a lacuna M4 declarada no `04`
- **Screenshots em `art/`**, não `docs/{idioma}/assets/` — convenção real das docs (raw.githubusercontent + `art/`); CT-20 reescrito para ela, 01/04/05 marcados `*(alterado em implementação)*`
- **Zero CSS novo** — `kit.css` não tocado; classes nativas `fi-logo*` já tinham as regras (previsto no D4 revisado, confirmado em `app.css` publicado)
- **CT-B01 com duas visitas** — `inDarkMode()` mid-session não reavalia; emulação só vale no load

## Notas de Implementação

- `FileUpload` guarda estado como `array uuid => caminho` — os CTs de form passam `[]`/`['path']`, não string/null
- Lock-screen é `SimplePage` — não emite `x-filament-panels::logo`; a marca do topo é superfície só de login/topbar (CT-17/18)
- `match` de `motivo` em `TelaBloqueio` lê `session('tenant_corrente')` bruto de novo porque o `motivo` precisa do valor cru, não do `null` resolvido — CT-07 de `IdentidadeVisualTest` assere os três motivos
- Cobertura de mutation: 92.45% no trio `IdentidadeDoKit`/`Tenant`/`TelaBloqueio`; os 4 uncovered são `match` do motivo (artefato de atribuição) e guardas do `mount()` pré-existentes fora do diff

## Referências Abertas

- `entrevista-tres-raias.md` — step 4 — 2026-10-03
- `template-00-requisito.md` — step 4 — 2026-10-03
- `template-01-plano.md` — step 4 — 2026-10-03
- `template-02-adr.md` — step 4 — 2026-10-03
- `template-03-progresso.md` — step 4 — 2026-10-03

## Retrospectiva

- **Funcionou bem**: a pesquisa achou o `darkModeBrandLogo` nativo e a impossibilidade do `isDarkMode()` server-side antes da implementação — o desenho nasceu sobre o mecanismo real do Filament
- **Faltou no plano**: —
