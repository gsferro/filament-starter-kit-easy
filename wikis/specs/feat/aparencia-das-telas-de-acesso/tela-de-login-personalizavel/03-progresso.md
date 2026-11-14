# Progresso — feat: tela de login personalizável

**Estado**: em revisão

`{base}`: `main` · branch `feat/aparencia-das-telas-de-acesso`

## Auditoria Pré-Implementação

- Entendimento confirmado: 2026-11-13 — autorização existente no pedido (implementar, com branch própria e cenários ao final) — 1 rodada; 2 perguntas de requisito (Q1, Q2) seguem com a recomendação como premissa (P-01, P-02), sem bloquear passo
- Roteiro manual: não — 2026-11-13 — instrução explícita do desenvolvedor — o pedido exige cenários de atualização e documentação, não roteiro manual
- RQ-02 (página de referência avaliada): capturas desktop/celular, claro/escuro, foco e erro, com medidas de grid, tipografia, contraste e animação; lições genéricas levadas ao layout `painel` (painel de marca arredondado, frase menor que o título, faixa no celular em vez de sumir, foco visível) — sem copiar marca, texto ou ilustração; 2026-11-13
- RQ-03 (implementação de referência analisada): moldura em duas colunas plugada por troca de `getLayout()`/`getView()`, CSS dedicado, tokens claro/escuro; descartado para o kit trocar o layout do auth-designer (quebraria `body:has(> .fi-auth-layout)`, a detecção de tela de auth e os testes) — ADR-03; 2026-11-13
- RQ-04: ADR-01 (camada no kit) decidida antes de qualquer código
- RQ-16: a escolha final saiu de duas propostas anônimas (X = ideação do Codex, Y = sessão) julgadas por um Codex novo, sem autoria; vencedor Y com correções, todas aceitas: cobrir redefinição e verificação do vendor, mídia obrigatória no `painel`, `#[Locked]` não substitui validação, organização da aparência sem exigir cadastro aberto, guarda de tenancy no bloqueio, rascunho por instância (não só usuário+escopo), atualização por botão, `create` para organização nova, sem kicker/destaques/logo branca, cartão/cobertura fora
- Revisão profunda: `TelaLogin::orgDoPedido()` lê a query, que some no POST do Livewire — a aparência da organização usa propriedade `#[Locked]` preenchida no `mount()`; redefinição de senha e verificação de e-mail eram classes do vendor e ganham subclasses
- Classe irmã: `TelaBloqueio` (outra página que reconstrói `AuthDesignerConfig`) — ocorrências conferidas em `AppServiceProvider` (bind), `TelaDeAutenticacao` (detecção por trait) e `tests/Kit/BloqueioDeSessaoTest.php`; as classes novas `TelaRedefinirSenha`/`TelaVerificacaoDeEmail` precisam usar a trait do pacote (detecção) e redeclarar `$layout` (rule `auth.md`)
- Costuras confirmadas: 2026-11-13, sessão — convenção do kit (Kit/Tenancy + Browser)
- Ponytail: passe manual (plugin não invocado) — cortes: layouts cartão/cobertura, upload no editor, herança por campo, kicker/destaques, alternador de tema no preview, aparência por organização em recuperar senha/2FA
- `search-docs`: indisponível (MCP `laravel-boost` sem conexão) — APIs conferidas no vendor instalado

## 1. Decisor, config e settings

- [x] `AparenciaDasTelasDeAcesso` + config + propriedade + mapa + migration — CT-04, CT-05, CT-30, CT-31, CT-33 verdes; KitInfoTest 73 propriedades, 2026-11-13

## 2. Páginas de autenticação vestidas

- [x] Trait, cinco páginas existentes, duas classes novas, providers — CT-07, CT-08, CT-22, CT-25, CT-26, CT-33, CT-36, CT-37 verdes; TelasDeAutenticacaoTest e BloqueioDeSessaoTest verdes, 2026-11-13

## 3. Painel de marca

- [x] Partial, view, CSS, `filament:assets` — CT-B01, CT-B02, CT-B04, CT-B06, CT-40 verdes, 2026-11-13

## 4. Coluna da organização

- [x] Migration, model — CT-10..CT-13, CT-32, CT-34 (Tenancy) verdes, 2026-11-13

## 5. Aba Telas de acesso

- [x] Aba, seções, validação — CT-01..CT-03, CT-28..CT-30, CT-35, ConfiguracoesSecoesColapsaveisTest 32/32, 2026-11-13

## 6. Preview

- [x] Página, rascunho em cache, iframe — CT-14..CT-18, CT-38, CT-39, CT-B05, CT-B07, CT-B08 verdes, 2026-11-13

## 7. Formulário da organização

- [x] Seção, toggle, preview com escopo — CT-01, CT-10, CT-13, CT-14, CT-17, CT-41 (Tenancy) verdes, 2026-11-13

## 8. Documentação

- [x] Página nova pt/en, configurações, multi-tenancy, READMEs, CHANGELOG, capturas em `art/` — CT-23, CT-24 verdes; SiteDeDocumentacaoTest, 2026-11-13

## 9. Testes e cenários

- [x] CT e CT-B verdes — Kit 125/125, Tenancy 72/72, Entrega 5/5, Browser 40/40, 2026-11-13
- [ ] Cenários 1 a 4

## Testes

| Arquivo | CT |
|---|---|
| `tests/Kit/AparenciaDasTelasDeAcessoTest.php` | CT do 04 com destino K (Kit) |
| `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php` | CT do 04 com destino T (Tenancy) |
| `tests/Kit/AparenciaDasTelasDeAcessoEntregaTest.php` | CT-23, CT-24 |
| `tests/Browser/AparenciaDasTelasDeAcessoTest.php` | CT-B01..CT-B08 |
| cenários do checklist de release (sessão) | CT-21, CT-27 |

## Tickets

Sugerido `/feature-tickets` — 2026-11-13: sinais "sessão já compactada" e 43 CT (abaixo de 60); 16 RQ (abaixo de 18). A sessão não invoca a `feature-tickets` (só o usuário); sem pedido, segue não fatiado.

## Revisão do Diff (step 9)

Quatro passes independentes, em paralelo, sobre a árvore final contra `main` sem `wikis/`: genérico pelo Codex (`/code-review` substituído, pedido do usuário), eixos pelo `fw-revisor-diff`, e as duas skills de blueprint do Filament pedidas pelo usuário — `reviewing-filament-plans` (conformidade com o `01`) e `filament-security-audit`.

| ID | Origem | Achado | Destino |
|---|---|---|---|
| RD-01 / CX-02 | eixos, Codex | bloqueio com fundo "arte" usava a logo ampliada como arte | P-15 → CT-36 → correção (`aplicar()` usa sempre a arte da identidade); CT-36 falha sem a correção |
| SEC-01 / RD-02 | security-audit, eixos | `$wire.corDoPainelDeMarca()`/`logosDoPainelDeMarca()` revelavam cor/logo de organização no clássico | P-16 → CT-37 → correção; CT-37 falha sem a correção |
| SEC-02 / BP-35 | security-audit, blueprint | rota do preview sem o `Authenticate` do painel | P-17 → CT-38 → correção (middleware do Filament) |
| SEC-03 / CX-01 | security-audit, Codex | escopo autorizado só no `mount()` | P-17 → CT-39 → correção (reautoriza a cada request) |
| BP-23 | blueprint | texto branco ilegível sobre cor clara | P-18 → CT-40 → correção (`--claro`); CT-40 falha sem a correção |
| CX-03 | Codex | voltar à padrão e sair sem recarregar restaurava a própria | RQ/P-12 → CT-41 → correção (`afterStateUpdated` do toggle) |
| RD-05 / BP-37 | eixos, blueprint | "Atualizar preview" não recarregava iframe com rascunho vencido | correção (recarrega o iframe); coberto por CT-B05 |
| RD-03 | eixos | alias público `configBaseDoAuthDesigner` na `TelaBloqueio` (ação `$wire`) | correção (`as protected`) |
| RD-04 | eixos | `CabecalhoDoPainelTelaTest` testava a página de verificação do vendor | teste aponta `TelaVerificacaoDeEmail` |
| CX-04 | Codex | helper de teste engolia exceção e gravava direto | helper sem atalho |
| CX-05 | Codex | constantes "no teto" do CT-B acima do teto | corrigido pelo executor (a) |
| SEC-04 | security-audit | `debug` de tenancy a cada login com `?org=` | só a recusa é logada |
| BP-13, BP-33a, BP-42 | blueprint | CHANGELOG "três providers"; docblock deslocado; contexto do log do preview | corrigidos |
| BP-09, BP-34, BP-45 | blueprint | trait em `app/Filament/Concerns`; preview em `Pages/Auth` com rota no provider; cópia do clássico com padrão nula | desvio aceito, `01` reconciliado *(alterado)* |
| BP-20 | blueprint | painel usa só a logo clara | rejeitado: o selo é claro de propósito (doc e `helperText`) |
| BP-47 | blueprint | doc sem capturas | capturas em `art/telas-de-acesso-*.png` na doc pt/en |
| BP-51 | blueprint | sem prova positiva do painel em redefinir/verificar/2FA/bloqueio | coberto por CT-36 (bloqueio) e CT-33/CT-B06 (clássico); positivo em redefinir/verificação/2FA: débito |

Re-revisão: as correções tocaram fronteira (preview, `$wire`); não houve segundo despacho — cobertura pelos CT-36..CT-41 e pelo quality gate (dimensão I).

## Conformidade com Rules

| Rule | Veredito | Evidência |
|---|---|---|
| `.ai/rules/app.md` | aplicada | `Paineis::correnteOuPadrao()` em `VesteAparenciaDasTelas`; URLs de logo via `IdentidadeDoKit` (`asset()`); nenhum `Storage::url()` |
| `.ai/rules/auth.md` | aplicada | toda página nova/alterada redeclara `$layout` (`TelaRedefinirSenha`, `TelaVerificacaoDeEmail`, `PreviewDasTelasDeAcesso`); par de testes: CT-33/CT-B06 + `TelasDeAutenticacaoTest` "página comum sem layout" verdes; guarda de `mount()` sem `routeIs()` |
| `.ai/rules/config.md` | aplicada | `config/kit.php` ganha `identidade.aparencia_das_telas = null`, sem `env()` (estrutura governada pelo banco) |
| `.ai/rules/css-filament.md` | aplicada | blades do kit só usam classes `kit-*` declaradas em `kit.css`; nenhum `display` nas imagens com `fi-logo-light/dark`; `filament:assets` rodado |
| `.ai/rules/filament-resources.md` | n.a. | `TenantForm`/Edit/Create só ganham seção e mutação; nenhum Resource novo |
| `.ai/rules/filament.md` | aplicada | alias de trait de vendor `as protected` (`TelaDoisFatores`, `TelaBloqueio` — RD-03); nenhuma Action nova (preview sem Action; `PermissoesDeAcoesTest` verde); `AderenciaAoBlueprintTest` verde |
| `.ai/rules/kit.md` | aplicada | CT-08/CT-25 medem a organização pela propriedade montada, não pela query do update |
| `.ai/rules/models.md` | n.a. | `Tenant` só ganha fillable e cast; sem trait nova |
| `.ai/rules/onboarding.md` | n.a. | nenhuma tela de onboarding tocada |
| `.ai/rules/pages.md` | aplicada | campos da aba nova em `Section` pelo helper `secao()`, aba pelo helper `aba()` com `deferLoading`; `ConfiguracoesSecoesColapsaveisTest` 32/32; nenhum segredo |
| `.ai/rules/providers-filament.md` | n.a. | nenhum plugin novo; só `usingResetPage`/`emailVerification` com classes do kit |
| `.ai/rules/providers.md` | aplicada | rota do preview no `KitServiceProvider` com `web` explícito + `panel:admin` + `Authenticate` do Filament |
| `.ai/rules/settings.md` | aplicada | propriedade + linha no mapa + migration nova com limpeza de cache; lida por request (`AparenciaDasTelasDeAcesso::global()` no render); `KitInfoTest` 73 |
| `.ai/rules/specs.md` | aplicada | citações `arquivo:símbolo:linha` (`citacoes.sh` exit 0; `CitacoesDeCodigoTest` verde) |
| `.ai/rules/support.md` | n.a. | nenhuma escrita de `.env` |
| `.ai/rules/testes-browser.md` | aplicada | `tests/Browser/AparenciaDasTelasDeAcessoTest.php` 40/40; suíte Browser 171/171 |
| `.ai/rules/testes.md` | aplicada | helpers compartilhados em `tests/Pest.php`; classe de teste com namespace (`tests/Kit/fixtures`); `toContain` sem mensagem; skip por `naArvoreDoKit()` |
| `.ai/rules/vendor.md` | aplicada | só o partial `media` (já autoral e em `CAMINHOS_DO_KIT`) foi alterado; `KitUpdateTest` verde |
| `.ai/rules/views.md` | aplicada | nenhuma diretiva em comentário Blade (`painel-de-marca`, preview, botão) |

## Verificação Final

## Notas de Implementação

- O spatie settings lê o `@var` da propriedade para decidir cast e não aceita `mixed`: a propriedade usa `@phpstan-var`
- O Filament AVALIA todo closure passado a `rule()`: a regra do Laravel vai embrulhada em `fn (): Closure => …`
- Campo oculto não é desidratado: a cor com fundo de arte precisa de `dehydratedWhenHidden()` para a aparência ser inteira
- O alternador de tema do Filament nasce com `tabindex="-1"` nos botões: fora do percurso de teclado, no clássico e no painel (débito, vendor)
- O iframe do preview na largura da coluna (~900 px) caía abaixo de 1024 px: o quadro é 1440×900 escalado
- `npx playwright install chromium` foi preciso para o executor de CT-B (o Playwright 1.65 pede o build 1254)

## Retrospectiva

## Quality Gate

## Candidatos a Rule

## Despachos

| # | Step | Rota / modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|
| 1 | 3 | Codex `exec --yolo` / não exposto | pedido resumido, repositório | ideação: camada no kit, `legado` default, JSON por organização, iframe exclusivo, 3 layouts | — | `git status` só com a wiki; afirmações amostradas (`AuthDesignerConfig` readonly, `KitUpdate` só relata composer) |
| 2 | 3 | workflow, 3 leitores + crítico / herdado da sessão | projeto de referência; página de referência; mapa do kit | 3 relatórios + crítica de lacunas | 426 k tokens · 334 s | capturas abertas pela sessão; trechos do partial, layout e `kit.css` relidos |
| 3 | 3 | Codex `exec --yolo` (juiz cego, sessão nova) / não exposto | propostas X/Y anônimas + repositório | Y com correções; layout `painel` 50/50 | — | `git status` só com a wiki; correções conferidas no código |
| 4 | 7 | Codex `exec --yolo` (derivação cega) / não exposto | `00`, recortes do `01`/`02`, glossário, `feature-test-design` | `04` 28 CT + `05` 6 CT-B; Q?1..Q?9 | — | `git status` sem mudança fora da sessão; Q?n viraram Q3..Q11/P-10..P-14 |
| 5 | 7 | `fw-adversario-ct` / herdado | `00`, `04`, `05`, glossário | ADV-01..23 | 81 k tokens · 199 s | ADV-03 reproduzido no código (`$` aceita `
`) e corrigido |
| 6 | 7 | Codex `exec --yolo` (re-derivação cega) / não exposto | `00` atual, `04`/`05`, achados | `04` 35 CT + `05` 8 CT-B, 23 ADV fechados | — | índice conferido; nenhum arquivo do repositório alterado |
| 7 | impl. | `fw-executor-ct` / sonnet | `00`, `04`, nomes | 191 casos; 3 divergências (b): regra do teto, teto cortado, README | 309 k tokens · 1386 s | divergências reproduzidas e corrigidas; CT-03 era teste errado (união de arrays) |
| 8 | 10 | `fw-executor-ctb` / sonnet | `00`, `05`, nomes | 40 casos; B05 (b) 1440×900; B03/B08 (a) teclado; B04 arte | 231 k tokens · 3876 s | B05 corrigido no código; B03/B04/B08 corrigidos pela sessão |
| 9 | 9 | Codex `exec --yolo` / não exposto | diff sem `wikis/` | CX-01..05 | — | reproduzidos |
| 10 | 9 | `fw-revisor-diff` / herdado | diff sem `wikis/`, eixos, Superfície | RD-01..05 | 136 k tokens · 520 s | RD-01 reproduzido (CT-36) |
| 11 | 9 | `general-purpose`/opus com `reviewing-filament-plans` | `01`, diff, testes | BP-01..51 (34 ok) | 166 k tokens · 293 s | amostrados BP-23, BP-35, BP-37 |
| 12 | 9 | `general-purpose`/opus com `filament-security-audit` | diff | SEC-01..04 (0 alta/média) | 127 k tokens · 150 s | SEC-01 reproduzido (CT-37) |
| 13 | 7 | executor-ct (SendMessage) | CT-36..41 | 197 casos verdes | 356 k tokens · 716 s | falsificabilidade CT-36/37/40 provada |

Degradações: `search-docs` indisponível; Ponytail em passe manual.

## Referências Abertas

- `references/template-00-requisito.md` (step 4)
