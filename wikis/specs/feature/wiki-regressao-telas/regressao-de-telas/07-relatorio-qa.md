# Relatório de QA — Regressão de telas em browser real

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Dívida: `06-divida-tecnica.md`
> Perfil de esforço: **completo** (UI com JS nos três painéis)
> Natureza da wiki: **nova** · Regressão: **não** (dimensão J fora de escopo por natureza)
> Numerado `07` e não `06`: o `06-divida-tecnica.md` já ocupa aquele número nesta wiki.

## Limitação estrutural desta execução — declarada primeiro

O princípio 2 da skill exige separação de poderes: quem julga não constrói. Nesta rodada **o
mesmo agente capturou o requisito, escreveu o PRD, especificou os CT-B e rodou o gate** — a
cegueira correlacionada que a skill combate.

Mitigação aplicada: as dimensões dinâmicas (B, D, E, F, G, I) foram delegadas a um **agente
avaliador independente**, instruído a ser cético e proibido de alterar código. Os achados dele
foram então **reverificados por mim** antes de entrar aqui — três confirmados por reprodução
própria, um rebaixado, um rejeitado. As reproduções estão em cada achado.

Isso reduz o viés; não o elimina. Um revisor humano ainda é a única separação real de poderes.

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · **Major: 1** · Minor: 4 · Cosmético: 0
- Ambiente: app servido in-process pelo `pest-plugin-browser` · Pest 5.1.1 · PHPUnit 13.3.0 ·
  Playwright MCP: **indisponível** (não configurado) · Boost MCP: **indisponível**
- Suítes no momento do gate: `pest --group=kit` = **213/213**, 726 asserções (série) ·
  `pest --testsuite=Browser` = **11 cenários, 10 verdes + 1 `->todo()`**, 82 asserções,
  verde em **3 execuções consecutivas** (102 s, 121 s, 128 s)

O destino é **especificação**, não implementação: os achados que reprovam são de **documentação
de dívida errada** (`06-divida-tecnica.md` e a descrição do CT-B09), não de código quebrado.
Nenhum arquivo de `app/` foi alterado nesta entrega, e a suíte está verde e estável.

Pela prioridade da skill (especificação > teste > implementação), corrigir o `06` e o `05` vem
antes de tocar em qualquer teste.

---

## Achados

### QA-01 — Render hook de plugin vaza entre painéis no mesmo processo · **Major** · dimensão G

- **Relacionado a**: RQ-03, CT-B01–04, e é a **causa raiz** de QA-02
- **Esperado**: `FilamentClearCachePlugin` está registrado **só** no
  `app/Providers/Filament/InfraPanelProvider.php:198`. Logo `/admin` não deve ter botão de
  *Clear Cache*.
- **Observado**: `/admin` tem 0 ocorrências quando é a primeira tela do processo, e **9** quando
  `/infra` foi visitado antes no mesmo processo PHP. O painel `/admin` renderiza um botão que
  não é dele.
- **Repro** (verificada por mim, não só relatada):
  1. Teste efêmero em `tests/Browser/`, `actingAs(usuarioDoKit('master_global'))`
  2. Cenário A — só `$this->get('/admin')` → `substr_count($html, 'clear-cache-button')` = **0**
  3. Cenário B — `$this->get('/infra')` e **depois** `$this->get('/admin')` → **9**
  4. Cenário C — `/infra` três vezes seguidas → 9, 9, 9 (estável; o vazamento é cross-painel,
     não acúmulo por requisição)
- **Evidência**: os três dumps acima. Mecanismo: o `register()` do pacote chama
  `$panel->renderHook('panels::user-menu.before', …)`, e o hook sobrevive à troca de painel
  dentro do processo.
- **Por que é Major**: os CT-B01–04 visitam os três painéis no mesmo processo. **O DOM que eles
  validam não é o DOM que o usuário vê** — a premissa da suíte inteira. E foi exatamente esse
  vazamento que fez a sonda original desta wiki registrar o botão em `/admin`, produzindo
  QA-02.
- **Impacto em produção hoje: nulo.** Octane, FrankenPHP e RoadRunner não estão instalados
  (`composer show` não os lista), então cada request web é um processo novo. O risco é latente:
  ligar um worker persistente o torna real.
- **Destino**: **3 teste** (a suíte valida um DOM contaminado) + **2 implementação** (latente,
  se um dia houver worker persistente)
- **Ação exigida**: registrar como **DT-08** e decidir se os CT-B devem isolar processo por
  painel — hoje 2 dos 3 lotes rodam num DOM que ninguém vê.

### QA-02 — `06-divida-tecnica.md` atribui DT-01 ao painel errado · **Minor** · dimensão A

- **Relacionado a**: RQ-07, DT-01, CT-B09
- **Esperado**: `06-divida-tecnica.md` → DT-01 diz *"Como foi encontrada:
  `assertNoAccessibilityIssues()` em `/admin`"*, e `05-casos-de-teste-browser.md` → CT-B09
  repete a atribuição.
- **Observado**: o botão não existe em `/admin`. Ele é do `/infra`, e apareceu na sonda por
  contaminação de processo (QA-01). `grep -rn FilamentClearCachePlugin app/Providers/Filament/`
  retorna apenas `InfraPanelProvider.php:17,198`.
- **Repro**: o cenário A de QA-01 — `/admin` isolado, 0 botões.
- **Destino**: **1 especificação**
- **Ação exigida**: corrigir a proveniência de DT-01 e do CT-B09 de `/admin` para `/infra`.
  Sem isso, quem for pagar a dívida verifica em `/admin`, não encontra o botão e conclui que já
  está resolvida.

### QA-03 — CT-B09 nunca alcançaria a `critical` que a wiki diz que ele pega · **Minor** · dimensão A

- **Relacionado a**: CT-B09, DT-01
- **Esperado**: `05-casos-de-teste-browser.md` → CT-B09 afirma *"Rodando, ele falha — e a falha é
  real"*, e lista **dois** achados (critical + serious).
- **Observado**: `visit([...])` itera as páginas em ordem e **a primeira exceção aborta o
  laço**. `/app` já falha no contraste, então `/infra` — a única com a `critical` — nunca é
  avaliada. O CT-B09 reportaria **1** issue, não 2.
- **Repro**: `visit(['/app', '/infra'])->assertNoAccessibilityIssues()` →
  `"1 Accessibility issues found - [serious] Elements must meet minimum color contrast…"`.
  Nenhuma menção à `critical`.
- **Destino**: **3 teste**
- **Ação exigida**: quando DT-01/DT-02 forem pagas, separar o CT-B09 em um cenário por painel.
  Enquanto ele for um lote, cobre só o primeiro painel que falha.
- **Nota**: a consequência genérica já estava documentada em ADR-02 (*"um lote para na primeira
  tela que falha"*). O que é novo é o efeito específico no CT-B09, que anula o propósito dele.

### QA-04 — DT-02 vale só no tema claro, e RQ-06 pedia esse eixo · **Minor** · dimensão G

- **Relacionado a**: RQ-06, DT-02
- **Esperado**: DT-02 registra *"Contraste 4.25:1 no indicador de ambiente"* sem qualificar tema.
- **Observado**: o elemento é `fi-text-color-600 dark:fi-text-color-400` — no tema escuro usa a
  cor 400 e **atravessa** o limiar. `visit('/admin')->inDarkMode()->assertNoAccessibilityIssues()`
  **passa**; sem `inDarkMode()` **falha** com o contraste. A sonda que originou DT-02 só mediu no
  claro.
- **Destino**: **1 especificação**
- **Ação exigida**: qualificar DT-02 como exclusiva do tema claro. É o único achado de tema da
  entrega, e estava sem justamente o eixo que RQ-06 nomeia.

### QA-05 — Nenhum CT-B assere valor de indicador · **Minor** · dimensão G

- **Relacionado a**: RQ-03 (*"teste se elas estão funcionando"*)
- **Esperado**: uma tela "funcionando" mostra o dado que tem no banco.
- **Observado**: os stats usam
  `FilamentOdometerEasyPlugin::make()->delay(1000)->duration(1500)`
  (`AdminPanelProvider.php:146-148`), que anima de 0 até o valor — o texto renderizado é `0`
  durante ~2,5 s. **Isso é comportamento desenhado**, não defeito: o `delay`/`duration` é
  escolha explícita do mantenedor. O achado real é outro: **nenhum CT-B assere o valor de um
  indicador**. Se o custom element `<number-flow>` falhar, o `0` fica permanente e
  `assertSee` + `assertNoJavaScriptErrors` passam em silêncio.
- **Repro parcial**: o avaliador independente observou `data-value="7"` com texto `0` via
  browser. **Eu não reproduzi** pelo caminho HTTP (`$this->get()` devolve markup pré-hidratação
  e meu regex não casou). Registro a limitação em vez de herdar a medição.
- **Destino**: **3 teste**
- **Ação exigida**: um CT-B que crie N registros e asserte o número na tela. Sem isso, "a tela
  abre" é tudo que a suíte prova sobre os dashboards.
- **Rebaixado** de Major (como o avaliador propôs) para Minor: o comportamento observado é o do
  plugin de odômetro funcionando como configurado.

---

## Achados rejeitados — destino 5, registrados para não voltarem

| # | Suspeita | Por que não é defeito |
|---|---|---|
| R-1 | `admin_organizacao` sem nenhum CT-B, apesar de RQ-05 pedir "valide os perfis" | O papel só é semeado com `config('kit.tenancy.enabled')` (`PapeisSeeder.php:70`), e a suíte roda single-tenant. Multi-tenancy em browser está em **Fora de Escopo** no `00`. Cobertura em nível HTTP existe em `tests/Tenancy/`. |
| R-2 | Senha preservada no formulário após login inválido | Comportamento default do Filament, e defensável. Não há requisito contra. Se o mantenedor discordar, vira destino 2 — mas é decisão dele, não achado. |
| R-3 | Avatar de `/app/meu-perfil` aparentemente quebrado no screenshot | Artefato de captura pré-hidratação. O DOM tem `.filepond--root` com rótulo pt-BR correto. |
| R-4 | N+1 em `/admin/users` | Medido: **13 queries constantes** com 1, 10 e 30 usuários. A aparência de N+1 vinha de deriva de medição no mesmo processo. |

## Débitos Aceitos

| # | Débito | Severidade | Registrado em |
|---|---|---|---|
| QA-06 | Telas de `/infra` misturam inglês e português (`"Logs explorer"`, `"1 file"`, `"Composer releases"`, `"Modified há 1 hora"`) e a tela de 403 traz `"Message no."`. Nenhuma das 7 dívidas cobre i18n | Minor | virar **DT-09** |
| QA-07 | A suíte escreve no `storage/logs` real: `autenticacao-2026-08-14.log` com 4.463 linhas / 1,1 MB só das rodadas de hoje. `phpunit.xml` não redireciona o channel | Minor · destino **4 infra** | virar **DT-10** |

## Matriz de Rastreabilidade

Lacunas apenas — as 8 cláusulas sem lacuna estão omitidas.

| RQ | Cláusula | Passo PRD | CT-B | Código | Resultado | Veredito |
|----|----------|-----------|------|--------|-----------|----------|
| RQ-05 | validar os perfis | 6 | CT-B05 (3 papéis) | — | ⚠️ | 4 dos 5 papéis exercitados; `admin_organizacao` fora por escopo (R-1). **Sem CT-B para "usuário sem papel nenhum"**, que `tests/Kit/PaineisTest.php:68-70` cobre em unidade — lacuna só de browser. Destino 3, Minor |
| RQ-06 | validar dark mode | 7 | CT-B07, CT-B08 | — | ⚠️ | 4 telas de 52 em tema escuro. `assertSee` **passa** com texto branco sobre branco, então o CT-B07 prova que a tela abre sob `prefers-color-scheme: dark`, não que está legível. Mitigado nesta rodada por inspeção visual de 9 telas + 403 + login nos dois temas: **nenhum texto ilegível, ícone sumido ou logo com fundo cravado**. O eixo tema está saudável; o teste é que é fraco |

As outras 8 cláusulas (RQ-01, 02, 03, 04, 07, 08, 09, 10) fecham sem lacuna.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 3 achados (QA-02, QA-03, e as 2 lacunas da matriz). Nenhuma cláusula sem entrega |
| B | Fronteiras e dados | ✅ | Escopo quase vazio — a entrega não introduz input. As 52 rotas dos CT-B **existem todas** e **nenhuma duplicada**, conferido contra `route:list --json` |
| C | Matriz de permissão | ⚠️ | 4 de 5 papéis exercitados. `admin_organizacao` fora por escopo (R-1); "sem papel" sem CT-B (matriz) |
| D | Observabilidade e PII | ✅ | `git diff -- config/logging.php` vazio, o que sustenta ADR-03. O log `[User@canAccessPanel]` **é** emitido no channel `autenticacao`. **Sem PII em claro**: e-mails aparecem mascarados (`nov*************`); regex de e-mail em `storage/logs/*.log` → 0 ocorrências. Só QA-07 (higiene) |
| E | Performance | ✅ | Nenhum N+1. `/admin/users` em 13 queries constantes com 1, 10 e 30 usuários |
| F | UX de erro | ✅ | A tela de 403 é boa: pt-BR, explica o que houve, mostra conta e papéis, tem "Voltar" e Request ID, e **zero vazamento** (busca por `App\`, `Illuminate\`, `vendor/`, `Stack trace` no innerHTML → vazio). Login inválido: *"Essas credenciais não correspondem aos nossos registros."*, e-mail preservado |
| G | Tema e cor | ⚠️ | 3 achados (QA-01, QA-04, QA-05). Mecanismo detectado: sem `tailwind.config.js` (Tailwind 4), tema vem do Filament com `--default-theme-mode: system`. **O eixo em si está saudável** — o axe em modo escuro passa onde o claro falha |
| H | Acessibilidade | ⚠️ | Coberta por CT-B09 `->todo()`, com as duas dívidas conhecidas. QA-03 mostra que o cenário, como está, alcançaria só uma delas |
| I | Segurança da superfície nova | ✅ | Nenhum `secrets.*` no `ci.yml`; nenhum comando perigoso. `--exclude-group=browser` exclui **exatamente** os 11 de browser: 227 total → 216 sem browser → 11 na testsuite. `pest()->browser()->timeout()` é escopado e não afeta Kit/Tenancy |
| J | Regressão adjacente | ⏭️ pulada | Natureza da wiki é `nova` — sem ancestral contra a qual regredir. A regressão **é** o produto desta wiki |

## Suspeitas Não Confirmadas

- **`visit([])` com array vazio passaria vacuamente** — real no mecanismo, inalcançável hoje: os
  quatro arrays dos CT-B são literais. Vira risco de verdade se DT-07 (derivar rotas de
  `getPages()`/`getResources()`) for pago. Anotado no próprio DT-07.
- **Processos órfãos** (35 chrome, 46 node, 20 php após as rodadas) — sondas foram interrompidas
  várias vezes durante a sessão; não atribuível à suíte sem uma medição limpa.

## Não Verificado

- **Tema escuro das 43 telas restantes** — 9 telas + 403 + login foram inspecionadas
  visualmente nos dois temas; as demais não. Automatizar isso é o próximo passo natural, e
  precisa de baseline de screenshot (hoje ausente, e a skill proíbe sem baseline revisado).
- **QA-01 sob worker persistente** — Octane/FrankenPHP/RoadRunner não instalados. O impacto em
  produção é inferido do mecanismo, não observado.
- **`pest --parallel --tia`** — bloqueado por DT-03, pré-existente. O contorno (série) é verde.
- **Playwright MCP** — indisponível nesta sessão; os confrontos de inventário de elementos e de
  rede/console que ele habilita não foram feitos. Fallback nativo (`screenshot`, `content`) foi
  usado, conforme ADR-05.
- **Telas com `{record}` e `/*/screen/lock`** — fora de escopo declarado no `00`.

## Ciclo 2 — necessário?

**Não.** Os cinco achados são de documentação (QA-02, QA-04), de lacuna de teste conhecida
(QA-03, QA-05) e um de mecanismo de framework sem impacto em produção hoje (QA-01). Nenhum
exige reimplementação, e todos têm destino claro. Um segundo ciclo reencontraria os mesmos.

O que **fecha** este gate é corrigir o `06-divida-tecnica.md` e a descrição do CT-B09 no `05` —
destino 1, prioridade máxima.
