# Progresso — feat: tela de login personalizável

**Estado**: em revisão — Adendos 2 e 3 (2026-11-14); gate dos Adendos 2 e 3 no ciclo 2 (REPROVADO → teste, QA-34 e QA-35 tratados); arte das telas de acesso gerada pelo `composer art`; merge, tag v0.53.0 e cenários sobre o commit final pendentes (RQ-17, QA-28)

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
- [x] Cenários 1 a 4 — ver Verificação Final, 2026-11-13

## 10. Adendo 1 — largura, destaque, ilustração, celular

- [x] Adendo 1 registrado no `00` (5 mensagens verbatim, URL mascarada) com RQ-17..RQ-24 e P-19..P-24 — 2026-11-14
- [x] Referência estudada para RQ-21/22 (ilustração de rede em movimento com botão de pausa e `prefers-reduced-motion`): trazida como sugestão própria, desenho e código do kit, sem copiar coordenadas, texto ou marca — 2026-11-14
- [x] Decisor: `tamanho_do_painel`→`mediaSize`, destaque, caminho restrito, ilustração, cor, `rede()`, `urlDaImagem()` — 2026-11-14
- [x] Trait: `destaqueDoPainelDeMarca()` (P-16); preview com `imagem_temporaria` — 2026-11-14
- [x] Editor em grupos; campos ocultos desidratados (o teste CT-02/CT-10 pegou `tamanho_do_destaque` e `movimento` caindo ao padrão quando ocultos) — 2026-11-14
- [x] View/CSS: topo/meio/base, rede SVG + Alpine, celular só com a marca — 2026-11-14
- [x] Seção da organização em largura total (a captura mostrou os campos espremidos na coluna da grade) — 2026-11-14
- [x] CT-35 `@obsoleto` (RQ-20) → CT-43; o CT-35 da suíte Kit era vácuo (`instanceof Filament\...` sem a barra inicial resolvia no namespace do teste) — 2026-11-14
- [x] Testes, medidos depois das correções do gate do adendo (`vendor/bin/pest <arquivo> --compact`): `AparenciaDasTelasAdendoTest` 58/58; `AparenciaDasTelasDeAcessoTest` 133/133, `…EntregaTest` 5/5, `…TenancyTest` 74/74; `tests/Browser/AparenciaDasTelasDeAcessoTest.php` 50/50 em três rodadas seguidas *(alterado em 2026-11-14: QA-20 — os 53/211/46 anteriores eram de antes do step 9)* — 2026-11-14
- [x] Falsificabilidade: CT-45 cai com o formato do caminho aberto (3 linhas); CT-B09 cai sem a regra do celular (2/2); CT-02/CT-10 caíram com `dehydratedWhenHidden()` ausente. CT-50 NÃO distingue o gate de `destaqueDoPainelDeMarca()` no clássico (o normalizador já zera o clássico): o gate fica como defesa em profundidade — 2026-11-14
- [x] Capturas novas: painel, celular, escuro, arte, editor, preview, organização (`art/telas-de-acesso-*.png`) — 2026-11-14
- [x] Revisão do diff do adendo (step 9) — ver "Revisão do Diff — Adendo 1" — 2026-11-14
- [ ] Quality gate do adendo, CI do PR, merge, tag e cenários 1–4 (RQ-17)

## Revisão do Diff — Adendo 1

Diff `cbb65fb6..81f62ce6` (sem `wikis/` e `art/`). Workflow de 4 revisores em paralelo, cada achado verificado por um cético que tenta refutá-lo; o Codex (`exec --yolo`, juiz cego) não terminou dentro do workflow e foi rodado de novo pela sessão, sem prazo.

| Achado | Origem | Verificação | Destino | O que foi feito |
|---|---|---|---|---|
| RD-A1 | `fw-revisor-diff` | real (leitura) | código | laço só com a rede à vista (≥ 1024 px), aba visível e sem pausa; CT-B10 (celular) falha sem a correção |
| RD-A2 / CX-A2 | `fw-revisor-diff`, Codex | real, reproduzido | código | meio com `overflow: hidden` e destaque com `max-height: min(teto, 100%)`; CT-B12 (1366×768) falha sem a correção |
| SEC-A1 | segurança | real (leitura) | código | aviso de imagem ausente uma vez por hora por caminho (`Cache::add`); CT-48 pede um aviso em duas visitas |
| CX-A1 | Codex | aceito (o Codex reproduziu no Chromium) | código | `.kit-painel-de-marca__base` com `flex-wrap`; CT-B12 (1024×768, 30%) falha sem a correção |
| CX-A3 | Codex | aceito | código | o componente ouve a mudança de `prefers-reduced-motion`, para o laço e esconde o botão |
| CX-A4 | Codex | aceito | código | `aparenciaDaTela()` (chamável por `$wire`) devolve sem a imagem fora do destaque "imagem" e sem a cor fora da rede; CT-50 falha sem a correção |
| ADV-A1 | `fw-adversario-ct` | lacuna real | CT | CT-B10 com `reducedMotion: reduce` no contexto do Playwright |
| ADV-A3, ADV-A11 | `fw-adversario-ct` | código certo, lacuna de teste | CT | CT-47 em recuperar senha e cadastro; CT-53 na organização |
| ADV-A4 | `fw-adversario-ct` | código certo, lacuna real | CT | CT-53: o destaque mostra a logo da organização |
| ADV-A6 | `fw-adversario-ct` | código certo | CT | CT-43 afirma `getMaxSize()` = teto do kit |
| ADV-A8 | `fw-adversario-ct` | código certo, oráculo fraco | CT + `00` | CT-44 com `''`; padrões de movimento e tamanho do destaque escritos em P-20/P-21 |
| ADV-A12, ADV-A17 | `fw-adversario-ct` | decisão faltando no `00` | `00` | P-20 (logo do destaque independe do topo) e P-24 (raiz do diretório) |
| ADV-A2, A5, A7, A9, A10, A13..A16, A18 | `fw-adversario-ct` | refutados pelo cético (código e cenário já cobrem, ou premissa falsa) | — | — |
| ADV-A15 (oráculos em comentário `# E …`) | `fw-adversario-ct` | forma, não defeito | débito | os ramos existem como testes; a promoção a cenários formais fica como débito da wiki |

## 11. Adendos 2 e 3 — por tela, textos formatados, cor/gradiente, celular, social e anti-robô

- [x] Adendos 2 e 3 registrados verbatim no `00`; P-25..P-30 e ADR-05 — 2026-11-14
- [x] Codex como juiz cego do desenho (X corrigida no item 1, Y nos itens 2 e 3, X no 4) — 2026-11-14
- [x] Decisor, trait, páginas por família, editor em abas, view/CSS — 2026-11-14
- [x] Bug do celular com arte (RQ-30) corrigido e coberto pelo CT-B16 (cai sem a regra) — 2026-11-14
- [x] Login social e anti-robô no painel: sem mudança de código; CT-71/72 e CT-B17 provam — 2026-11-14
- [x] Dois defeitos pegos pelos testes durante a implementação: o `required()` do `layout` disparava em abas ocultas (CT-28) e, retirado da raiz, deixava um estado forjado zerar a aparência (CT-20) — `required` só na raiz — 2026-11-14
- [x] Falsificabilidade: `daFamilia()` ignorando as telas derruba CT-58/60/64; sem a regra do celular, o CT-B16 cai — 2026-11-14
- [x] Abas internas do editor trocadas por três seções colapsáveis com id e ícone: a tela de configurações proíbe abas aninhadas e campo fora de seção (`ConfiguracoesSecoesColapsaveisTest` CT-01/03/05/06) — 2026-11-14
- [x] Revisão do diff dos Adendos 2 e 3 — ver "Revisão do Diff — Adendos 2 e 3" — 2026-11-14
- [x] Revisão cega do Codex sobre `ca469486..694ec0d8` (correções da revisão, do gate e da revisão adversarial): 1 achado, CX-C1 (Minor) — na recuperação, o preview da organização trocava a aparência pela padrão, mas a imagem do destaque vinha do rascunho dela; corrigido em `PreviewDasTelasDeAcesso::urlDaImagemDoDestaque()`; CT-97 cai sem a correção (mostra `/livewire/preview-file/IMAGEM-DA-ACME`) — 2026-11-14
- [x] Testes, medidos em 2026-11-14 depois do ciclo 2 (`vendor/bin/pest <arquivo> --compact`): `AparenciaDasTelasPorTelaTest` + `AparenciaDasTelasAdendoTest` + `AparenciaDasTelasDeAcessoTest` 278/278; `AparenciaDasTelasDeAcessoTenancyTest` 83/83; CT-85, CT-88 e CT-92 reforçados pelas divergências que a `feature-test-design` apontou no QA-34 (redefinição pela URL assinada do e-mail; social e anti-robô desligados de forma explícita com controle positivo; linha do teto 80) — 2026-11-14
- [x] Arte do site gerada pelo `composer art` (pedido do solicitante: "gere gif com todas as possibilidades de customização" e "muitas arts"): cenários em `tests/BrowserTenancy/CapturaDeArteTest.php` (variações, celular, editor, tema escuro), `KitArte::CLIPES` `telas-de-acesso` (9 quadros) e `telas-de-acesso-editor` (6 quadros), 17 imagens em `KitArte::IMAGENS`; galeria e os dois GIFs nas docs pt/en; README pt/en com o resumo atual e o link do GIF; logo de arte em `tests/Browser/Fixtures/logo-de-arte*.png` — `KitArteTest`, `SeletorDeOrganizacaoArteTest`, `PacotesRodada3Test` 87/87; `SiteDeDocumentacaoTest`, `AparenciaDasTelasDeAcessoEntregaTest`, `KitInfoTest` 108/108 — 2026-11-14

## Revisão do Diff — Adendos 2 e 3

Diff `fe9ea589..0d9034fa` (sem `wikis/`, `art/`, `docs/`). Workflow: Codex (`exec --yolo`, juiz cego), `fw-revisor-diff` e auditoria `filament-security-audit`, um cético por achado. Os 14 achados foram confirmados; todos corrigidos com teste que cai sem a correção.

| Achado | Severidade | O que foi feito |
|---|---|---|
| CX-B1 | alta (reproduzido) | a cópia do login levava a imagem como string ao campo de upload, e salvar derrubava a validação com TypeError: a cópia usa o formato interno `[uuid => caminho]`; CT-74 cai sem a correção |
| CX-B5 / RD-B1 | média | a cópia leva o auxiliar `origem_da_cor`, e a cor própria continua à vista; CT-74 |
| CX-B2 / RD-B5 | média | texto antigo salvo pelo editor trocava `&copy;` por © e criava link de URL: `&` e `:` também escapados; CT-67 com entidade e URL |
| CX-B3 / RD-B3 | média | no preview, a tela que segue o login usa a imagem não salva do login, e o estado escondido não conta; CT-78 |
| CX-B4 | baixa (teste vácuo) | CT-B17 confere o anti-robô com `checkVisibility()` e dentro do formulário |
| RD-B2 | média | sobre o gradiente, o nome em destaque e o botão de pausar também ganham a superfície escura |
| SEC-B1 | baixa | a limpeza do HTML do Markdown passa a ser por DOM, não regex: `title` e `href` sem esquema seguro saem; CT-57 com título e `//` |
| SEC-B2 / RD-B4 | baixa | `aparenciaDaTela()` sem as cores do gradiente fora do gradiente; CT-76 |
| RD-B6 | baixa | com primária hex, o gradiente vazio usa os tons 600 e 900 da mesma paleta; CT-77 |
| RD-B7 | baixa | tela própria com layout ausente ou desconhecido cai no clássico (P-04), não no login; CT-75 |


## Testes

| Arquivo | CT |
|---|---|
| `tests/Kit/AparenciaDasTelasDeAcessoTest.php` | CT do 04 com destino K (Kit) |
| `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php` | CT do 04 com destino T (Tenancy) |
| `tests/Kit/AparenciaDasTelasDeAcessoEntregaTest.php` | CT-23, CT-24 |
| `tests/Browser/AparenciaDasTelasDeAcessoTest.php` | CT-B01..CT-B18 |
| `tests/Kit/AparenciaDasTelasAdendoTest.php` | CT do Adendo 1 (CT-43..CT-56) com destino K |
| `tests/Kit/AparenciaDasTelasPorTelaTest.php` | CT-57..CT-92 com destino K |
| `tests/BrowserTenancy/CapturaDeArteTest.php` | arte das telas de acesso (só com `KIT_ART=1`) |
| cenários do checklist de release (sessão) | CT-21, CT-27 |

## Tickets

Sugerido `/feature-tickets` — 2026-11-13: sinais "sessão já compactada" e 43 CT (abaixo de 60); 16 RQ (abaixo de 18). A sessão não invoca a `feature-tickets` (só o usuário); sem pedido, segue não fatiado.

## Revisão do Diff (step 9)

Quatro passes independentes, em paralelo, sobre a árvore final contra `main` sem `wikis/`: genérico pelo Codex (`/code-review` substituído, pedido do usuário), eixos pelo `fw-revisor-diff`, e as duas skills de blueprint do Filament pedidas pelo usuário — `reviewing-filament-plans` (conformidade com o `01`) e `filament-security-audit`.

| ID | Origem | Achado | Destino |
|---|---|---|---|
| RD-01 / CX-02 | eixos, Codex | bloqueio com fundo "arte" usava a logo ampliada como arte | P-15 → CT-36 → correção (`aplicar()` usa sempre a arte da identidade); CT-36 falha sem a correção |
| SEC-01 / RD-02 | security-audit, eixos | `$wire.corDoPainelDeMarca()`/`logosDoPainelDeMarca()` revelavam cor/logo de organização no clássico | P-16 → CT-37 → correção; CT-37 falha sem a correção |
| SEC-02 / BP-35 | security-audit, blueprint | rota do preview sem o `Authenticate` do painel | P-17 → CT-38 → correção: primeiro o middleware do Filament, *substituído* no ciclo 1 do gate (QA-01) por `canAccessPanel(admin)` na página |
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
| `.ai/rules/commands.md` | não se aplica | `KitArte` só ganhou entradas nas constantes `CLIPES` e `IMAGENS` (arte das telas de acesso); não grava `.env` nem muda o `kit:update` |
| `.ai/rules/app.md` | aplicada | `Paineis::correnteOuPadrao()` em `VesteAparenciaDasTelas`; URLs de logo via `IdentidadeDoKit` (`asset()`); nenhum `Storage::url()` |
| `.ai/rules/auth.md` | aplicada | toda página nova/alterada redeclara `$layout`; par de testes CT-33/CT-B06 + `TelasDeAutenticacaoTest`; operação fora do painel que consulta `canAccessPanel()`: o preview pede `canAccessPanel(Filament::getPanel('admin'))` explicitamente, sem depender do painel corrente (QA-01 do ciclo 1, CT-38) |
| `.ai/rules/config.md` | aplicada | `config/kit.php` ganha `identidade.aparencia_das_telas = null`, sem `env()` (estrutura governada pelo banco) |
| `.ai/rules/css-filament.md` | aplicada | blades do kit só usam classes `kit-*` declaradas em `kit.css`; nenhum `display` nas imagens com `fi-logo-light/dark`; `filament:assets` rodado |
| `.ai/rules/filament-resources.md` | n.a. | `TenantForm`/Edit/Create só ganham seção e mutação; nenhum Resource novo |
| `.ai/rules/filament.md` | aplicada | alias de trait de vendor `as protected` (`TelaDoisFatores`, `TelaBloqueio` — RD-03); nenhuma Action nova (preview sem Action; `PermissoesDeAcoesTest` verde); `AderenciaAoBlueprintTest` verde |
| `.ai/rules/kit.md` | aplicada | CT-08/CT-25 medem a organização pela propriedade montada, não pela query do update |
| `.ai/rules/models.md` | n.a. | `Tenant` só ganha fillable e cast; sem trait nova |
| `.ai/rules/onboarding.md` | n.a. | nenhuma tela de onboarding tocada |
| `.ai/rules/pages.md` | aplicada | campos da aba nova em `Section` pelo helper `secao()`, aba pelo helper `aba()` com `deferLoading`; `ConfiguracoesSecoesColapsaveisTest` 32/32; nenhum segredo |
| `.ai/rules/providers-filament.md` | n.a. | nenhum plugin novo; só `usingResetPage`/`emailVerification` com classes do kit |
| `.ai/rules/providers.md` | aplicada | rota do preview no `KitServiceProvider` com `web` explícito + `panel:admin` + `auth`; a autorização é da página *(alterado em 2026-11-14: QA-12)* |
| `.ai/rules/settings.md` | aplicada | propriedade + linha no mapa + migration nova com limpeza de cache; lida por request (`AparenciaDasTelasDeAcesso::global()` no render); `KitInfoTest` 73 |
| `.ai/rules/specs.md` | aplicada | citações `arquivo:símbolo:linha` (`citacoes.sh` exit 0; `CitacoesDeCodigoTest` verde) |
| `.ai/rules/support.md` | n.a. | nenhuma escrita de `.env` |
| `.ai/rules/testes-browser.md` | aplicada | `tests/Browser/AparenciaDasTelasDeAcessoTest.php` 40/40; suíte `Browser` (`tests/Browser` + `tests/BrowserTenancy`) 220: 183 passaram, 37 pulados, 0 falhas sobre o commit do ciclo 3 *(alterado em 2026-11-14: QA-16)* |
| `.ai/rules/testes.md` | aplicada | helpers compartilhados em `tests/Pest.php`; classe de teste com namespace (`tests/Kit/fixtures`); `toContain` sem mensagem; skip por `naArvoreDoKit()` |
| `.ai/rules/vendor.md` | aplicada | só o partial `media` (já autoral e em `CAMINHOS_DO_KIT`) foi alterado; `KitUpdateTest` verde |
| `.ai/rules/views.md` | aplicada | nenhuma diretiva em comentário Blade (`painel-de-marca`, preview, botão) |

## Verificação Final

- [x] `rastreabilidade.sh` — exit 0, 2026-11-13
- [x] `checkbox-sem-evidencia.sh` — exit 0, 2026-11-13
- [x] `citacoes.sh` — exit 0, 2026-11-13
- [x] `ids-ct.sh {wiki} 'tests/**/AparenciaDasTelasDeAcesso*.php'` — exit 0 (CT-21/CT-27 `@obsoleto`: execução pela sessão nos cenários), 2026-11-13
- [x] `conformidade-rules.sh {wiki} main` — exit 0, 19 rules com linha, 2026-11-13
- [x] Falsificabilidade: CT-36 (arte no painel), CT-37 (gate do `$wire`) e CT-40 (texto escuro) falham com a correção revertida em cópia de segurança e voltam verdes restaurados; CT-32 e CT-05 nasceram vermelhos (b) pelo executor e ficaram verdes com a correção — 2026-11-13
- [x] RQ-12: grep pelos identificadores do projeto e da URL de referência (não registrados aqui) em todo arquivo alterado contra `main`, incluindo a wiki — `{ git diff main --name-only; git ls-files --others --exclude-standard; } | sort -u` = 79 arquivos, 0 ocorrências *(alterado em 2026-11-13: QA-06 — o "80" anterior não tinha comando)*, 2026-11-13
- [x] Regressão Kit 5045 testes, 0 falhas fora das contagens do README (corrigidas: `SiteDeDocumentacaoTest` 68/68); Tenancy 741/741 — 2026-11-13
- [x] Suíte `Browser` (`vendor/bin/pest --testsuite=Browser --compact`, inclui `tests/BrowserTenancy`) sobre o commit do ciclo 3: 220 testes, 183 passaram, 37 pulados, 0 falhas. Rodada anterior com a mesma árvore: 1 falha no `DockDeAtalhosTest` CT-B02 (cor lida no meio da transição de tema, `oklab` interpolado), verde isolado (4/4) e na repetição — flake, fora do diff *(alterado em 2026-11-14: QA-16 — o "171/171" de 2026-11-13 era anterior aos ciclos 1 e 2)* — 2026-11-14
- [x] QA-15 falsificável: com a view do preview de `d343fc56`, `tests/BrowserTenancy/IdentidadeVisualTest.php` falha com `ResizeObserver loop completed with undelivered notifications`; com a correção, 9/9 — 2026-11-14
- [x] `phpstan analyse` 0 erros; `filacheck` 17/17; `pint --dirty` — 2026-11-13
- [x] Cenários 1 a 4 sobre `b28debff` (tag local `pre-aparencia-das-telas`): 1 e 2 por `git archive` + `kit:install --create-project` (2 com `kit:tenancy --force`), 3 e 4 por `create-project` da v0.52.1 + `kit:update --repo={kit} --tag=pre-aparencia-das-telas --all` (4 com `kit:tenancy` antes) — os 4 com todos os arquivos novos, `kit.identidade.aparencia_das_telas` = null e 5786 testes, 0 falhas; erros só em 1 (5) e 2 (4), todos `phpstan dump-parameters` com timeout de 60 s sob carga no `QualidadeDeCodigoTest`, verde isolado (31: 29 passaram, 2 pulados); pulados 1161 nos quatro = teto 1156 + 5 do `AparenciaDasTelasDeAcessoEntregaTest` (CT-23 × 4 e CT-24, guardados por `naArvoreDoKit()`: docs e README não viajam) — 2026-11-13
- [x] Cenários 1 a 4 sobre o commit final pós-gate (`d343fc56`, tag local `pre-aparencia-das-telas-3`; a rodada sobre `1b6d3a09` foi interrompida e descartada porque o ciclo 2 gerou commit novo), mesmos procedimentos (diretórios `c1c`..`c4c`): os 4 com todos os arquivos novos, `kit.identidade.aparencia_das_telas` = null e 5795 testes (+9 do ciclo 1 e 2), 0 falhas; erros só `phpstan dump-parameters` com timeout de 60 s sob carga no `QualidadeDeCodigoTest` (1: 5; 3: 2; 4: 2), verde isolado em 1, 3 e 4 (31: 29 passaram, 2 pulados); 2 limpo na repetição (a primeira caiu por `WorkerCrashedException` — timeout de 5 s do `git rev-parse` chamado pelo Pest, ambiente); pulados 1161 nos quatro, igual à rodada anterior; `kit:update` aplicou 46 arquivos e as 2 migrations novas em 3 e 4 — 2026-11-14

- [x] Adendo 1, sobre `a1a50f6f`: `pest --testsuite=Kit --parallel` 5110 (5107 passaram, 3 pulados, 0 falhas); `--testsuite=Tenancy --parallel` 743/743; `--testsuite=Browser` 230 com 193 passaram, 37 pulados, 0 falhas numa rodada da sessão — o gate, em duas rodadas, viu 2 e 1 falhas: `DockDeAtalhosTest` CT-B02 (flake registrado no ciclo 3, fora do diff) e o CT-B09 da feature uma vez (logo compartilhada com o CT-B04; corrigido, QA-21) *(alterado em 2026-11-14: QA-20)*; `phpstan` 0 erros nos arquivos do adendo; `filacheck` 17/17; scripts da wiki exit 0 — 2026-11-14

- [x] Cenários 1 a 4 sobre `e5b0d157` (Adendo 1; tags locais `pre-adendo-final` e `pre-adendo-base`), diretórios `d1`..`d4`: os quatro com 5857 testes e 1161 pulados (o mesmo teto da rodada anterior); `d3` e `d4` (`kit:update` de um projeto com painel gravado antes do adendo) `passed`, 4696 passaram, 0 falhas; `d1` e `d2` com 2 erros cada, ambos "exceeded the timeout" do `phpstan dump-parameters` (flake de carga conhecido). Prova de P-23 em `d3.login.depois.html`: `--media-size: 50%`, `media-right` e a frase `ANTIGA` presentes, sem `data-kit-destaque` — 2026-11-14
- [x] Rodada `e1`..`e4` sobre `7ca671df` interrompida e descartada: o ciclo 1 do gate gerou código novo depois dela (QA-35) — 2026-11-14
- [x] Adendos 2 e 3, sobre `694ec0d8`: `pest --testsuite=Kit --parallel --processes=4` 5198 (5195 passaram, 3 pulados, 2 risky, 0 falhas); CI do PR #193 todo verde (instalacao, qualidade, telas, site) — 2026-11-14
- [ ] Cenários 1 a 4 sobre o commit final (tag local `pre-final`), diretórios `f1`..`f4`, com texto gravado antes do Adendo 2, um de cada vez
- [x] Adendos 2 e 3, sobre `ca469486`: `pest --testsuite=Kit --parallel` 5166 (5163 passaram, 3 pulados, 0 falhas); `--testsuite=Tenancy --parallel` 744/744; `--testsuite=Browser` 235 (197 passaram, 37 pulados, 1 falha: `DockDeAtalhosTest` CT-B02, flake de transição de tema registrado no ciclo 3, verde isolado 4/4); `phpstan` 0 erros nos arquivos tocados; `filacheck` 17/17; scripts da wiki exit 0 — 2026-11-14

## Blockers

- **Gate no teto de ciclos** (2026-11-14): o ciclo 3 reprovou por QA-15 (laço do `ResizeObserver` no preview, que derrubava um teste de navegador existente) e QA-16/QA-17 (texto do `03`). Os três foram corrigidos depois do veredito, com QA-15 provado falsificável e a suíte `Browser` verde; não há ciclo 4. O aceite final é do solicitante, no PR

## Débitos

- **Q1 aberta** (instalação nova nasce no clássico ou no painel?): implementada a premissa estreita P-01 — nada muda sem decisão do admin, nem em instalação nova. Confirmar com o solicitante
- **Q2 respondida pela leitura do texto original** (QA-03/QA-13): confirmação explícita do solicitante pendente; se a resposta for "só o login", vira Adendo
- **Alternador de tema fora do teclado** (vendor, `tabindex="-1"` nos botões do `theme-switcher` do Filament), no clássico e no painel — CT-B03 prova o percurso sem armadilha, sem o alternador
- **Organização no navegador**: as linhas de organização dos CT-B05/CT-B07 (editor da organização) não foram automatizadas em `tests/BrowserTenancy`; o comportamento está coberto por componente (CT-01, CT-10..CT-14, CT-41 em Tenancy)
- **Positivo do painel em redefinir senha, confirmação de e-mail e 2FA** só por leitura de código e pelo CT-B06 (clássico); BP-51
- **Preview sem a pilha do painel** (`MustTwoFactor`, `Locker`): sessão bloqueada ainda abriria o próprio rascunho (suspeita do gate, ciclo 2, não reproduzida); o rascunho só existe para quem acabou de editar no `/admin`

## Notas de Implementação

- O spatie settings lê o `@var` da propriedade para decidir cast e não aceita `mixed`: a propriedade usa `@phpstan-var`
- O Filament AVALIA todo closure passado a `rule()`: a regra do Laravel vai embrulhada em `fn (): Closure => …`
- Campo oculto não é desidratado: a cor com fundo de arte precisa de `dehydratedWhenHidden()` para a aparência ser inteira
- O alternador de tema do Filament nasce com `tabindex="-1"` nos botões: fora do percurso de teclado, no clássico e no painel (débito, vendor)
- O iframe do preview na largura da coluna (~900 px) caía abaixo de 1024 px: o quadro é 1440×900 escalado
- `npx playwright install chromium` foi preciso para o executor de CT-B (o Playwright 1.65 pede o build 1254)

## Retrospectiva

- Funcionou: ideação e julgamento cego pelo Codex trocaram a proposta de layout por uma mais enxuta antes de uma linha de código; a revisão adversarial dos CT (23 achados) e os quatro revisores do step 9 pegaram defeitos que a suíte verde não via (arte do bloqueio, `$wire` revelando a organização, contraste, preview caindo no layout de celular)
- Faltou: medir o preview no navegador antes do step 9 — o defeito do iframe abaixo de 1024 px só apareceu na captura; e os helpers de teste do executor mascararam por um ciclo o bug do `rule()` (fallback para o banco)

## Quality Gate

| Ciclo | Veredito | Data |
|---|---|---|
| 1 | REPROVADO → especificação (QA-01, QA-02 Blocker; QA-03..06 Major; E, G, H, K não verificadas) | 2026-11-13 |
| 2 | REPROVADO → especificação (QA-02 aberto até os cenários do commit final; QA-10, QA-12 Major; QA-11, QA-13 Minor; QA-14 Cosmético) | 2026-11-14 |
| 3 | REPROVADO → especificação (QA-16) com implementação (QA-15); QA-17 Cosmético — teto do loop, escalado ao solicitante | 2026-11-14 |
| Adendo 1 · 1 | REPROVADO → especificação (QA-19, QA-20) com implementação (QA-18); Major 3, Minor 6, Cosmético 1; teto APROVADO COM DÉBITO (G, H, K) | 2026-11-14 |
| Adendos 2 e 3 · 1 | REPROVADO → especificação (QA-27, QA-31) com teste (QA-30) e implementação (QA-28); Major 4, Minor 3; teto APROVADO COM DÉBITO (G, H, J, K) | 2026-11-14 |
| Adendos 2 e 3 · 2 | REPROVADO → teste (QA-34); Major 1 novo + QA-28 como pendência de release, Minor 1 (QA-35); teto APROVADO COM DÉBITO (G, H, J, K) | 2026-11-14 |

Tratamento do ciclo 1:

| QA | Destino | O que foi feito |
|---|---|---|
| QA-01 | 3 → 2 | CT-38 limpa o painel corrente antes do request e falha sem a correção; a página exige `canAccessPanel(admin)` em `podeVer()` (a rota volta a `auth`); `03`/docblocks corrigidos |
| QA-02 | 2 | cenários 1 a 4 em execução sobre o commit; evidência em Verificação Final |
| QA-03 | 1 | Q2 respondida pelo próprio texto original ("renderiza as telas de auth"), com P-02 (recorte sem a escolha de painel); confirmação explícita pedida ao solicitante no fechamento |
| QA-04 | 3 → 2 | CT-40 ganhou fronteira (`#00aaff`, `#d97706`, `#1d4ed8`, primária default) e falha com o corte antigo; `fundoClaro()` escolhe o texto de maior contraste, entende `oklch`, e a cor do painel nunca é vazia (âmbar 600 default) |
| QA-05 | 1 | `01`, `02` (ADR-03, ADR-04, Superfície Livewire) e docs pt/en reconciliados *(alterado)* |
| QA-06 | 1 | contagem refeita com o comando, na Verificação Final |
| QA-07 | 1 | "telas vestidas pelo Auth Designer, sem a escolha de painel" na doc e no CHANGELOG; três termos no glossário |
| QA-08 | 3 | CT-42 (padrão em painel × organização no clássico próprio), com controle positivo |
| QA-09 | 1 | cabeçalho do `04` com contagem e comando; linha quebrada do `03` corrigida |

Tratamento do ciclo 2 (commit `d343fc56`):

| QA | Destino | O que foi feito |
|---|---|---|
| QA-02 | 2 | cenários 1 a 4 sobre `d343fc56`, verdes — Verificação Final |
| QA-10 | 3 → 2 | `#rgb` expandido antes do contraste; linha nova no CT-40 |
| QA-11 | 3 → 2 | luminância do `oklch` pela conversão OKLab → sRGB linear; Fuchsia/Pink 600 com texto branco, Sky 600 com escuro, no CT-40 |
| QA-12 | 1 | PRD e `03` (rules) dizem que a página exige `canAccessPanel(admin)` |
| QA-13 | 1 | P-02 sem a condição "enquanto Q2 não é respondida" |
| QA-14 | 1 | cabeçalho do `04` no formato do template |

Tratamento do ciclo 2 dos Adendos 2 e 3:

| QA | Destino | O que foi feito |
|---|---|---|
| QA-34 | 3 | `feature-test-design` escreveu os cenários de CT-80..CT-96 com mutantes próprios (M109..M132); CT-97 (CX-C1) e M133 pela sessão; cabeçalho recalculado pelos greps (97 cenários, 111 mutantes, 0 sem matador) |
| QA-35 | 1 | Verificação Final, Testes, seção 11, despachos 25..30 em ordem e Estado; a rodada dos cenários passa a apontar para o commit final de verdade (`f1`..`f4`) |
| QA-28 | 2 | segue como pendência de release: cenários 1–4 sobre o commit final |

Tratamento do ciclo 1 dos Adendos 2 e 3:

| QA | Destino | O que foi feito |
|---|---|---|
| QA-27 | 1 → 2 | P-31: a organização não tem "esqueci a senha"; o editor dela não oferece a seção e o preview da organização mostra a padrão nessa tela; CT-79 cai sem a correção; CT-43 da organização conta dois uploads |
| QA-28 | 2 | rodada `d1`..`d4` registrada; rodada `e1`..`e4` sobre `7ca671df` em execução; linha do QA-18 corrigida |
| QA-29 | 1 | o trecho da RQ-27 inclui "possibilidade de usar html"; Q12 registrada com a recomendação e o porquê; P-05 marcada |
| QA-30 | 3 | `feature-test-design` reescreveu R26–R30 (Gherkin completo, M73..M108); `fw-adversario-ct` rodou (ADV-C1..C32): CT-80..CT-96 e CT-B18, e o defeito ADV-C8 corrigido |
| QA-31 | 1 | "abas" → "seções" no PRD, na ADR-05, nas docs pt/en e no CHANGELOG; Superfície Livewire com `destaqueDoPainelDeMarca()` e `gradienteDoPainelDeMarca()`; docblock da view |
| QA-32 | 1 | despachos 19..25, degradação, Estado |
| QA-33 | 1 | glossário: "Painel de marca", "Tela própria", "Família de tela" |

Tratamento do ciclo 1 do Adendo 1 (o adendo reabre o loop: pedido novo do solicitante):

| QA | Destino | O que foi feito |
|---|---|---|
| QA-18 | 2 | cenários 1–4 sobre `e5b0d157` (Adendo 1), diretórios `d1`..`d4` — registrados na Verificação Final só depois do gate dos Adendos 2 e 3 *(alterado em 2026-11-14: QA-28 — a linha apontava para um registro que ainda não existia)* |
| QA-19 | 1 | passo 1, passo 3 e Decisões de Desenho do `01` e ADR-03 do `02` reescritos com a marca *(alterado em 2026-11-14: Adendo 1)* |
| QA-20 | 1 | números do item "Testes" e da linha da suíte `Browser` trocados pela saída medida |
| QA-21 | 3 → 2 | CT-B05 espera o iframe e repete o "Atualizar preview"; CT-B09 usa uma logo só dele; três rodadas seguidas 50/50 |
| QA-22 | 1 | despachos 16..18 e a degradação da derivação do adendo |
| QA-23 | 1 | IDs repetidos renumerados (CT-54..CT-56, CT-B13..CT-B15) no `04`/`05` e nos `it()`; cabeçalho pelos `grep -cE` (56 · 17 · 57 · 0); oráculo dos cabeçalhos, índices do `05`, linha Upload e M66 |
| QA-24 | 1 | P-23 e as docs pt/en ressalvam o celular; P-09 marcada como substituída em parte; glossário |
| QA-25 | 3 → 2 | o botão de pausar fica só com o rótulo variável, sem `aria-pressed`; CT-B10 ajustado antes |
| QA-26 | 2 | a ajuda da largura diz "por exemplo" |

Tratamento do ciclo 3 (último do loop; achados corrigidos depois do veredito, sem novo ciclo de gate):

| QA | Destino | O que foi feito |
|---|---|---|
| QA-15 | 2 | o `ResizeObserver` do preview reagia à altura da moldura, que muda com a escala (laço de layout); agora só a largura decide e a medição vai para `requestAnimationFrame`. `IdentidadeVisualTest` vermelho com a view antiga, verde com a nova |
| QA-16 | 1 | as duas linhas "Browser 171/171" trocadas pela saída real da suíte `Browser` sobre o commit final |
| QA-17 | 1 | linha do ciclo 2 com o destino do veredito |

## Candidatos a Rule

Step 12 (2026-11-14): nenhum candidato passa em *Vale virar rule*. As decisões duráveis (ADR-01..04) são desta feature e já estão nos docblocks; as Notas de Implementação são armadilhas de framework, não decisão do projeto. Observação do gate, não promovida por falta de recorrência: "a suíte `Browser` inclui `tests/BrowserTenancy`; regressão de UI do admin roda as duas pastas".

## Despachos

| # | Step | Rota / modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|
| 1 | 3 | Codex `exec --yolo` / não exposto | pedido resumido, repositório | ideação: camada no kit, `legado` default, JSON por organização, iframe exclusivo, 3 layouts | — | `git status` só com a wiki; afirmações amostradas (`AuthDesignerConfig` readonly, `KitUpdate` só relata composer) |
| 2 | 3 | workflow, 3 leitores + crítico / herdado da sessão | projeto de referência; página de referência; mapa do kit | 3 relatórios + crítica de lacunas | 426 k tokens · 334 s | capturas abertas pela sessão; trechos do partial, layout e `kit.css` relidos |
| 3 | 3 | Codex `exec --yolo` (juiz cego, sessão nova) / não exposto | propostas X/Y anônimas + repositório | Y com correções; layout `painel` 50/50 | — | `git status` só com a wiki; correções conferidas no código |
| 4 | 7 | Codex `exec --yolo` (derivação cega) / não exposto | `00`, recortes do `01`/`02`, glossário, `feature-test-design` | `04` 28 CT + `05` 6 CT-B; Q?1..Q?9 | — | `git status` sem mudança fora da sessão; Q?n viraram Q3..Q11/P-10..P-14 |
| 5 | 7 | `fw-adversario-ct` / herdado | `00`, `04`, `05`, glossário | ADV-01..23 | 81 k tokens · 199 s | ADV-03 reproduzido no código (o `$` aceita quebra de linha no fim) e corrigido |
| 6 | 7 | Codex `exec --yolo` (re-derivação cega) / não exposto | `00` atual, `04`/`05`, achados | `04` 35 CT + `05` 8 CT-B, 23 ADV fechados | — | índice conferido; nenhum arquivo do repositório alterado |
| 7 | impl. | `fw-executor-ct` / sonnet | `00`, `04`, nomes | 191 casos; 3 divergências (b): regra do teto, teto cortado, README | 309 k tokens · 1386 s | divergências reproduzidas e corrigidas; CT-03 era teste errado (união de arrays) |
| 8 | 10 | `fw-executor-ctb` / sonnet | `00`, `05`, nomes | 40 casos; B05 (b) 1440×900; B03/B08 (a) teclado; B04 arte | 231 k tokens · 3876 s | B05 corrigido no código; B03/B04/B08 corrigidos pela sessão |
| 9 | 9 | Codex `exec --yolo` / não exposto | diff sem `wikis/` | CX-01..05 | — | reproduzidos |
| 10 | 9 | `fw-revisor-diff` / herdado | diff sem `wikis/`, eixos, Superfície | RD-01..05 | 136 k tokens · 520 s | RD-01 reproduzido (CT-36) |
| 11 | 9 | `general-purpose`/opus com `reviewing-filament-plans` | `01`, diff, testes | BP-01..51 (34 ok) | 166 k tokens · 293 s | amostrados BP-23, BP-35, BP-37 |
| 12 | 9 | `general-purpose`/opus com `filament-security-audit` | diff | SEC-01..04 (0 alta/média) | 127 k tokens · 150 s | SEC-01 reproduzido (CT-37) |
| 13 | 7 | executor-ct (SendMessage) | CT-36..41 | 197 casos verdes | 356 k tokens · 716 s | falsificabilidade CT-36/37/40 provada |
| 14 | 11 | `general-purpose` / herdado (cenários) | tags locais `pre-aparencia-das-telas{,-3}`, procedimento dos 4 cenários | 4 cenários × 2 rodadas, 0 falhas | 150 k tokens · 9918 s (última rodada) | logs `c?c.test.log` conferidos pelo gate do ciclo 3 |
| 15 | 11 | `fw-qa-gate` / herdado (ciclos 1, 2 e 3) | path da wiki, URL servida, `diff --stat`, base | ciclo 3: REPROVADO, QA-15..17 | 196 k tokens · 1357 s (ciclo 3) | QA-15 reproduzido pela sessão com a view antiga |
| 16 | 9 (Adendo 1) | workflow: Codex `exec --yolo`, `fw-revisor-diff`, auditoria `filament-security-audit` (general-purpose), `fw-adversario-ct`, um cético por achado / herdado | diff `cbb65fb6..81f62ce6` sem `wikis/` e `art/`; o adversário só com `00`, `04`/`05` › Adendo 1 e glossário | RD-A1..A2, SEC-A1, ADV-A1..A18; o Codex não terminou dentro do workflow | 1362 k tokens · 400 s | vereditos dos céticos lidos no `journal.jsonl`; RD-A2 reproduzido pelo cético |
| 17 | 9 (Adendo 1) | Codex `exec --yolo` (juiz cego, sessão nova) / não exposto | o mesmo diff, pedido de revisão rígida | CX-A1..A4 | — | CX-A1 e CX-A2 conferidos com CT-B12/CT-B15 que falham sem a correção |
| 18 | 11 (Adendo 1) | `fw-qa-gate` / herdado | path da wiki, URL servida, `diff --stat`, base | ciclo 1 do adendo: REPROVADO → especificação, QA-18..QA-26 | 258 k tokens · 2002 s | QA-20 reproduzido (53→57 na hora do gate; 58 depois do CT-55); QA-23 pelo `uniq -d` |
| 19 | 11 (release) | `general-purpose` / herdado | contrato de `wikis/atualizacoes/README.md`, roteiro v0.52.1, CHANGELOG, diff `v0.52.1..HEAD` | `wikis/atualizacoes/v0.53.0.md` e a cópia em `agentes-e-skills.md`; guarda 215/215 | 139 k tokens · 337 s | guarda rodada de novo pela sessão (315/315 com SiteDeDocumentacao e KitInfo) |
| 20 | 11 (Adendo 1) | `general-purpose` / herdado (cenários) | tags `pre-adendo-final`/`pre-adendo-base`, procedimento dos 4 cenários | `d1`..`d4` | — | resultados lidos dos logs pela sessão (Verificação Final) |
| 21 | 3 (Adendo 2) | Codex `exec --yolo` (juiz cego) / não exposto | proposta anônima X + repositório | X corrigida (item 1), Y (itens 2 e 3), X (item 4) | — | veredito adotado na íntegra; P-25..P-30 e ADR-05 |
| 22 | 9 (Adendos 2 e 3) | workflow: Codex `exec --yolo`, `fw-revisor-diff`, `filament-security-audit` (general-purpose), um cético por achado / herdado | diff `fe9ea589..0d9034fa` sem `wikis/`, `art/`, `docs/` | CX-B1..B5, RD-B1..B7, SEC-B1..B2; 14 confirmados | 1023 k tokens · 405 s | CX-B1 reproduzido pela sessão (CT-74 cai sem a correção) |
| 23 | 11 (Adendos 2 e 3) | `fw-qa-gate` / herdado | path da wiki, URL servida, base; sem suítes inteiras (memória) | ciclo 1: REPROVADO → especificação, QA-27..QA-33 | 255 k tokens · 857 s | QA-27 reproduzido (CT-79 cai sem a correção) |
| 24 | 7 (Adendos 2 e 3) | `general-purpose` com `feature-test-design` / herdado | `00`, seções dos Adendos 2 e 3 do `04`/`05` | Gherkin completo de R26–R30, M73..M108, CT-79; 3 divergências apontadas | 160 k tokens · 363 s | 2 lacunas fechadas pela sessão (CT-66 sem gravação; M108 no CT-B16) |
| 25 | 11 (Adendos 2 e 3) | `general-purpose` / herdado (cenários) | tags `pre-adendo2-final`/`pre-adendo-base` | `e1`..`e4` | — | interrompida e descartada: `7ca671df` deixou de ser o commit final (QA-35) |
| 26 | 7 (Adendos 2 e 3) | `fw-adversario-ct` / herdado | `00`, seções dos Adendos 2 e 3 do `04`/`05`, glossário | ADV-C1..C32 (9 altos); 1 defeito real (C8) | 73 k tokens · 207 s | CT-80..CT-96 e CT-B18 escritos e verdes; C8 corrigido |
| 27 | 11 (CI) | Codex `exec --yolo` / não exposto | duas falhas do job `telas` no CI de `7ca671df`; sem rodar testes | ambas (b) prováveis, sem defeito da branch | — | CI de `694ec0d8` todo verde: as duas não se repetiram |
| 28 | 11 (Adendos 2 e 3) | `fw-qa-gate` / herdado | path da wiki, URL servida, `diff --stat`, base; sem suítes inteiras | ciclo 2: REPROVADO → teste, QA-34 (Major) e QA-35 (Minor) | 208 k tokens · 586 s | QA-34 reproduzido pelos greps do relatório; o `git status` que o gate estranhou era a arte desta sessão |
| 29 | 9 (Adendos 2 e 3) | Codex `exec --yolo` (revisor cego) / não exposto | diff `ca469486..HEAD` de `app`/`resources`/`tests`/`public/css` | CX-C1 (Minor), reproduzido fora do repositório | — | CT-97 cai sem a correção |
| 30 | 7 (QA-34) | `general-purpose` com `feature-test-design` / herdado | `00`, `04`, glossário, nomes dos testes | Gherkin de CT-80..CT-96, M109..M132; 6 divergências teste × cenário | 162 k tokens · 354 s | 3 divergências viraram teste mais forte (CT-85, 88, 92); CT-96 respondida no `04` (regra da cor sólida do QA-10); CT-81 e CT-84 só de forma |

Degradações: `search-docs` indisponível; Ponytail em passe manual. Adendos 2 e 3: os cenários R26–R30 foram derivados pela sessão; o gate (QA-30) mandou reescrevê-los pela `feature-test-design` e passar pelo `fw-adversario-ct` — feito depois do gate (ver Despachos). Adendo 1: os cenários CT-43..CT-56 e CT-B09..CT-B15 foram derivados pela sessão, não às cegas pela `feature-test-design`; a revisão adversarial independente (`fw-adversario-ct`) rodou sobre o conjunto antes do step 9, e os acrescentados depois dela (CT-53..CT-56, CT-B12..CT-B15) não tiveram re-revisão *(alterado em 2026-11-14: QA-22)*.

## Referências Abertas

- `references/template-00-requisito.md` (step 4)
