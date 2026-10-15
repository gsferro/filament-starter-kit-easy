# Progresso — Tela de configurações: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

**Estado**: em implementação

> Branch: `feat/configuracoes-secoes-colapsaveis` · Base do PR (`{base}`): `main` · Wiki: `wikis/specs/feat/configuracoes-secoes-colapsaveis/configuracoes-secoes-colapsaveis/`

## 1. Rótulo da aba: "Kit" → "Aplicação"
- [ ] `abaKit()` → `abaAplicacao()`, `Tab::make('Aplicação')`, mesmo ícone e posição
- [ ] `CapturaDeArteTest`: dois `click('Kit')` → `click('Aplicação')`

## 2. Seções em toda aba; ícone, colapso, persistência e id em toda seção
- [ ] Helper `secao()` e as 11 seções novas (*Seções por aba* do `01`)
- [ ] Seções existentes pelo helper (ícone onde faltava; `collapsible()` + `persistCollapsed()` + `id()`), provedores com `collapsed()` preservado
- [ ] Seção *Servidor SMTP* `->visible()` só com SMTP (D3)

## 3. Carregamento adiado das seis abas
- [ ] `Tabs::key()`, `Tab::key()` e `Schema::make()->components([...])->deferLoading()` por aba (helper `aba()`)

## 4. Docs pt/en e CHANGELOG
- [ ] "aba Kit" → "aba Aplicação" nas 27 linhas; parágrafo das seções e do adiamento; CHANGELOG `[Unreleased]`

## 5. Capturas refeitas
- [ ] `composer art`: `admin-configuracoes-seletor` e `admin-configuracoes-dock` com a aba "Aplicação"

## 6. Rule do projeto
- [ ] `.ai/rules/pages.md` — step 12 (`requirement-to-rule`), com a aprovação do solicitante

## Testes
- [ ] `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` (CT-01, CT-03…CT-18, CT-20)
- [ ] `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` (CT-02, CT-19)
- [ ] `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` (CT-B01…CT-B06)

## Tickets
Não fatiado — 2026-10-15: 9 RQ vigentes, 15 CT (+4 CT-B), compactação: não (nesta feature), 5 perguntas de requisito — nenhum sinal

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest tests/Kit/ConfiguracoesSecoesColapsaveisTest.php --compact`
- [ ] `vendor/bin/pest tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` (CT-B)
- [ ] `php artisan test --compact --parallel --testsuite=Kit` e `--testsuite=Tenancy`: nada além da baseline (Kit 4 172/4 175 + CT-82, Tenancy 631/631)
- [ ] `pest --mutate --path=app/Filament/Admin/Pages/ConfiguracoesDoKit.php`: no Windows, só por amostragem manual (lição da rodada 3)
- [ ] **Custo medido**: requests e HTML da tela antes × depois, contra o `## Modelo de Execução`
- [ ] **Step 9**: revisão do diff por quem não implementou (passe genérico cego + `fw-revisor-diff`), antes da reconciliação
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] `citacoes.sh {wiki}` silencioso
- [ ] `ids-ct.sh {wiki} tests/…` silencioso
- [ ] `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade dos CTs de achado provada
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 11. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: pendente — rodada 1 da entrevista em 2026-10-15: 0 perguntas de fato ao usuário (raia fato resolvida pelo levantamento do despacho #1 e pelo vendor), 6 decisões de desenho tomadas pela sessão sob autorização existente (D1–D6 do `01`), 5 perguntas de requisito ao solicitante (Q1–Q3 do `00` e Q4/Q6 da derivação, implementadas pela recomendação; resposta contrária entra como Adendo) e 1 de desenho da derivação (Q5 → D7). O solicitante autorizou a implementação ("inicia a /feature-wiki da tela de configurações" e "implemente tudo", 2026-10-15), então a sessão segue sem nova aprovação do plano.

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|
| — | nenhuma divergência de comportamento: a feature não muda o que o código faz, só onde cada campo mora e quando a aba renderiza | — | — | — |

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `Tab::schema()` aceita um `Schema` (para `deferLoading()`) | `vendor/filament/schemas/src/Components/Concerns/HasChildComponents.php:schema():72` — `array \| Schema \| Closure` | nenhuma |
| `Schema::deferLoading()` existe no 5.10.2 instalado | `vendor/filament/schemas/src/Schema.php:deferLoading():152`; `composer show filament/filament` = v5.10.2, última no Packagist | nenhuma (o `01` não precisa de bump) |
| Aba com erro carrega sozinha | `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:isDeferredSchemaLoaded():220`, `:238-258` percorre o error bag pelos state paths | nenhuma |
| Seção colapsada abre no erro | `vendor/filament/schemas/resources/js/index.js:handleFormValidationError:153`, `:159` dispara `expand` nos ancestrais; `vendor/filament/schemas/src/Components/Section.php:toEmbeddedHtml():263`, `:368` escuta | nenhuma |
| `persistCollapsed()` grava por `id()` | `vendor/filament/schemas/src/Components/Section.php:$persist:364` — chave `filament:section:collapsed:{id}` em `localStorage` (Alpine `$persist`) | nenhuma |
| `Section::visible()` aceita closure com `Get` | `vendor/filament/schemas/src/Components/Concerns/CanBeHidden.php:visible():114` (`bool \| Closure`), e a Page já usa `fn (Get $get)` em `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaEmail():444`, `:446` | nenhuma |
| `secaoAntiRobo()` em `:765` | o método começa em `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:secaoAntiRobo():761`; o `Section::make` está em `:765` | `01` corrigido (citação) |
| A chave da aba na URL deriva do rótulo | `vendor/filament/schemas/src/Components/Tabs.php:persistTabInQueryString():140`, `:93` (`Str::slug(label) . '::tabs'`) — "Kit" → `kit::tabs`, "Aplicação" → `aplicacao::tabs` | nenhuma (Q3 confirmada como pergunta real) |
| Nenhum teste afirma o rótulo "Kit" por componente | levantamento do despacho #1: só os dois `click('Kit')` de `tests/BrowserTenancy/CapturaDeArteTest.php:it:937`, `:942` e `:it:1085`, `:1096`; `tests/Kit/SegredosDoSettingsTest.php:it:579` afirma a hierarquia das seções de provedor (preservada) | nenhuma |

**Varredura da classe irmã**: não há classe nova nesta feature (a Page, os helpers privados e os testes são edições/arquivos de teste) — n.a., registrado em 2026-10-15.

### Auditoria Ponytail (step 6)
`/ponytail-review` (skill do projeto, em linha) sobre `01` e `02`, 2026-10-15 — `net: -3 itens`.
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | yagni: passo 3 (uma linha de `->visible()` da seção SMTP) como passo próprio | sim — fundido no passo 2; passos renumerados | `01` passos 2–6, `## Cobertura`; `03` seções |
| 2 | delete: CT de chaves únicas por reflexão (o render já estoura `LogicException`) | sim | `01` Riscos |
| 3 | delete: D6 "o `columns(2)` fica" — estado atual, não decisão | sim — D7 virou D6 | `01` Decisões de Desenho |
| — | `02`: lean already (sem ADR; só a Superfície Livewire) | — | — |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 3 | **Codex CLI 0.162.1** (`codex exec --yolo < /dev/null`), contrato só-leitura escrito pela sessão — inventário da Page (campos por aba, seções, ícone/colapso), fatos do vendor (Tabs/Tab, `Schema::deferLoading`, Section/colapso/persistência), testes e docs que citam a aba Kit ou seções, rules | modelo padrão do Codex (não exposto) | conversa, wiki; proibido escrever fora de `scratchpad/codex2/levantamento.md` | 420 linhas: 50 campos (31 soltos), 10 seções existentes, API do adiamento com citações, 28 arquivos de teste com `fillForm` na Page, 27 linhas de docs "aba Kit", 2 capturas, rules sem seção sobre Section/Tab | — (host não reporta); ~10 min | presença ok (A–E no formato pedido); integridade: `git status --porcelain` só com a wiki nova; amostragem pela sessão: `vendor/filament/schemas/src/Components/Section.php:$persist:364`, `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:isDeferredSchemaLoaded():220`, `:238-258`, `tests/Kit/SegredosDoSettingsTest.php:assertSchemaComponentExists:591`, `:598-615` e `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaKit():939`, `:944-999` lidos e conferem |
| 2 | 7 | `general-purpose` como analista — derivação do `04`/`05` pela `feature-test-design` (sem `analista` em `.claude/agents/`) | opus | conversa, `01` inteiro (só o extrato: paths, rotas, Superfície de UI, composição), `03`, código da feature | `04` com 15 CT, 12 regras, 40 mutantes, 0 sem matador; `05` com CT-B01…CT-B04 (MB1–MB9); 3 perguntas (Q?1–Q?3 → Q4, Q5, Q6); costuras propostas (5 grupos); conflito declarado: CT-05/CT-B02 escritos sobre a recomendação de Q1, marcados `@pendente-Q1` | 254.453 tokens · 1.109 s · 31 tool uses | presença ok (delimitadores `<<<04`/`>>>04`, `<<<05`/`>>>05`, `## Para o orquestrador`); integridade: rascunhos só em `scratchpad/derivacao3/`, `git status` sem arquivo novo fora da wiki; amostragem pela sessão: `grep -cE` = 15 cenários no `04` e 4 no `05`, CT-10 grava com a aba adiada (P-04), CT-03 percorre `getFlatComponents(withHidden: true)`; **ressalva**: o agente relatou a seção *Composição da tela* vazia no extrato e leu a tabela direto do `01` › `## Mapeamentos` (mesmo conteúdo, só para seletores) — degradação de cegueira registrada |
| 3 | 7 | `fw-adversario-ct` — revisão adversarial do `04`/`05` (rodada 1) | opus (agente) | `01`, `02`, `03`, código, conversa | 25 achados (10 implementações que passam, 8 oráculos fracos, 3 malformados, 4 RQ/P sem cenário discriminante), sondas 6–8 n.a., 7 tentativas rejeitadas com motivo, 1 pergunta (locale — rejeitada pela sessão: rótulo literal); leituras negadas: nenhuma | 64.574 tokens · 191 s · 5 tool uses | presença ok (formato fixo); amostragem pela sessão: ADV-17 confere (`vendor/filament/schemas/src/Components/Concerns/HasId.php:getId():18` cai em `getKey()`), ADV-08 confere (CT-02 com `≥ 8`), ADV-01 confere (CT-10 sem segredo fora do default); decisões em `scratchpad/derivacao3/adv-rodada-1.md` (ADV-10 e ADV-24 rejeitados com prova/motivo; Q1 mantida aberta com a recomendação autorizada) e o derivador (#2) retomado por `SendMessage` para o fechamento |
| 5 | 7 | derivador (#2) retomado por `SendMessage` — fechar ADV-01…ADV-25 com as decisões em `scratchpad/derivacao3/adv-rodada-1.md` | opus | idem #2 | `04` com **20 CT, 13 regras, 54 mutantes, 0 sem matador**; `05` com 5 CT-B (MB1–MB13); CT-16…CT-20, R13, M41–M54 novos; seção `## Revisão adversarial — rodada 1` (23 fechados, 2 rejeitados); rodada 2 devida | 350.409 tokens · 608 s · 11 tool uses | greps conferidos pela sessão (20/13/54; 5/13); amostragem: CT-10 grava segredos fora do default e compara decifrado (ADV-01), CT-05 usa `getCustomId()` (ADV-17), CT-02 conta exatamente 8 abaixo de `## [0.49.0]` (ADV-08); `citacoes.sh` e `rastreabilidade.sh` exit 0 |
| 4 | impl. | **Codex CLI 0.162.1** como construtor — lote C1 (passos 1–3 do `01`: rótulo, seções, SMTP) | modelo padrão do Codex (não exposto) | `03`, `04`, `05`; proibido tocar settings/config/database/testes (salvo os dois `click`) | `ConfiguracoesDoKit.php` +383/−336 e os dois `click('Aplicação')`; 18 seções pelo helper + 4 de provedor com id/persistência; pint ok, FilaCheck 17/17, PHPStan 0; fumaça 414/414 + 181/181; sem desvio — commit `0db621d0` | — (host não reporta) | presença ok (formato fixo com tabela de seções); integridade: `git status` só com os 2 arquivos do lote (hashes da wiki e de `tests/Pest.php` intactos); amostragem: `grep -c '\$this->secao('` = 18, `Tab::make('Kit')` = 0, ids `configuracoes-login-provedor-*` nas 4 seções |
| 6 | 7 | `fw-adversario-ct` retomado — rodada 2 (última): conferir os 25 fechamentos e os cenários novos | opus (agente) | idem #3 | 20 fechados, 5 parciais (ADV-07, 09, 16/MB10, 20, 22), 8 achados novos (ADV-26…ADV-33: controle positivo com a palavra solta, CT-19 só pelo título e M53 impossível, `live()` do remetente sem CT, MB10 sem divergência, literal `tabelas::tabs`, três Quando duplos, Q1 sem marca em CT-17/CT-B05), 6 tentativas rejeitadas com motivo; nenhuma pergunta nova; leituras negadas: nenhuma | 122.525 tokens · 234 s · 4 tool uses | presença ok; amostragem pela sessão: ADV-27 confere (`.ai/rules/index.md` já tem a linha `app/Filament/Admin/Pages/**`), ADV-28 confere (o hex preenchido antes do colapso segue no primeiro Salvar), ADV-29 confere (D1 põe `key()` nas abas — o valor da URL é implementação); decisões em `scratchpad/derivacao3/adv-rodada-2.md` (ADV-30 rejeitado com motivo; CT-B06 novo; CT-B03 encolhido); derivador (#2) retomado para o fechamento final |
| 7 | 7 | derivador (#2) retomado — fechar a rodada 2 (decisões em `scratchpad/derivacao3/adv-rodada-2.md`) | opus | idem #2 | `04` final: **20 CT, 13 regras, 54 mutantes, 0 sem matador**; `05` com **6 CT-B** (MB1–MB9, MB11–MB14); CT-B06 novo (`live()` do remetente), MB10 retirado, M53 trocado, CT-B03 encolhido, CT-B04 sem literal, CT-16/CT-17/CT-B05 ajustados; seção `## Revisão adversarial — rodada 2`; débito declarado: CT-B06 sem revisão independente (teto de 2 rodadas) | 461.752 tokens · 461 s · 14 tool uses | greps conferidos pela sessão (20/13/54; 6/13); amostragem: CT-02 com agulha da aba e tabela por arquivo (a sessão corrigiu pt 8 → 9 conforme `git show v0.49.0`), CT-19 exige conteúdo da rule, CT-B04 lê a URL depois do clique |
| 8 | impl. | **Codex CLI 0.162.1** como construtor — lote C2 (passo 3 do `01`: carregamento adiado) | modelo padrão do Codex (não exposto) | `03`, `04`, `05` | `Tabs::key('configuracoes')`, helper `aba()` com `Tab::key()` + `Schema::make()->components()->deferLoading()` nas seis abas; pint/FilaCheck/PHPStan ok; prova do adiamento (6 `fi-sc-loading`, `nome_da_aplicacao` fora do HTML inicial, dentro depois de `loadDeferredSchema('form.configuracoes.identidade')`); **fumaça 482/518: 34 falhas + 2 erros em testes existentes**, nenhum ajustado (contrato) | — (host não reporta) | presença ok; integridade: só a Page alterada; sessão investigou com sonda: a falha é de BUSCA, não de comportamento — chaves herdáveis das abas aninham a chave dos campos (`form.configuracoes.email.mail_password`) e `getComponent('mail_password')` do Filament prefixa só a raiz (`vendor/filament/schemas/src/Concerns/HasComponents.php:getComponent():166`, `:168-170`); `assertSchemaComponentExists` lê `getFlatComponents(withHidden: true)[$key]` (`vendor/filament/schemas/src/Testing/TestsSchemas.php:assertSchemaComponentExists():25`, `:38`); com a chave aninhada e com finder por closure os campos são achados (sonda da sessão); abas adiadas também tiram os campos do HTML inicial. Decisão: D8 (helpers `chaveDoCampo()`/`carregarAba()` em `tests/Pest.php` e migração dos testes existentes — lote T0, #9) |
| 9 | impl. | **Codex CLI 0.162.1** como executor (contrato `fw-executor-ct`, lote T0: migração dos testes existentes para as chaves aninhadas / carga da aba) | modelo padrão do Codex (não exposto) | `01`–`05`, wiki; proibido tocar `app/`, oráculos | helpers `chaveDoCampo()` (`tests/Pest.php:chaveDoCampo():396`) e `carregarAba()` (`:carregarAba():407`); **22 `it()` migrados em 10 arquivos** (Kit 9 + Tenancy 1), todos verdes na reexecução filtrada; achado (a) do próprio lote: depois de `carregarAba()` o `Testable` conserva o HTML anterior — `refresh()` em seguida; nenhum (b)/(c); 17 dos 27 arquivos pedidos passaram sem toque; pint só nos 11 arquivos — commit `2fb1b082` | — (host não reporta); ~55 min | presença ok (tabela por arquivo com as linhas JSON em logs de `%TEMP%`); integridade: só testes e `tests/Pest.php`; amostragem pela sessão: `SegredosDoSettingsTest.php:521-522` usa `chaveDoCampo()` com o mesmo oráculo (`assertSchemaComponentVisible/Hidden`), helpers conferidos em `tests/Pest.php` |
| 10 | impl. | **Codex CLI 0.162.1** como executor (contrato `fw-executor-ct`, lote L1: CT-01, CT-03…CT-18, CT-20 em `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php`) | modelo padrão do Codex (não exposto) | `00`–`03`, `05`; proibido `app/`, `tests/Pest.php`, oráculos | 18 cenários / 31 execuções: 26 verdes, **3 vermelhos (b)** relatados sem ajuste — CT-09 (com erro, o Filament carrega TODAS as abas adiadas: `InteractsWithSchemas.php:isDeferredSchemaLoaded():245`), CT-14 (`$loadedDeferredSchemas` é `#[Locked]`: escrita recusada), CT-17 (`client_id` do provedor não tem regra de validação); correções (a) relatadas: `id="form.` também nos contêineres (CT-07 filtra campos), cargas parciais em `effects['partials']`, campos condicionais ligados no arranjo (CT-12/CT-15), CT-10 com logo real no disco | — (host não reporta) | presença ok (tabela por CT + saídas literais); integridade: só o arquivo novo criado; amostragem pela sessão: os três (b) conferidos no vendor/código (`#[Locked]` em `:53`, contêineres com caminho `data` em `:245`, `secaoDoProvedor()` sem `required()` no `client_id`) — **todos erro de especificação**, roteados como causa (a): P-03 reescrita e P-06 nova no `00`, linha do `02` corrigida, CT-09/CT-14/CT-17 e CT-B05 reescritos no `04`/`05`; redespacho L1b (#11) |
| 11 | impl. | **Codex CLI** executor — lote L1b: realinhar CT-09, CT-14, CT-17 ao `04` corrigido + análise do CT-10 na tag | modelo padrão do Codex | idem #10 | CT-09, CT-14 (3 partições) e CT-17 verdes; **31/31** `{"tests":31,"passed":31,"assertions":366}`; pint ok; CT-10 na tag: o snapshot/segredos já valeriam na v0.49.0, as asserções de marcador/aba nunca carregada dependem do adiamento (análise aceita, sem execução na tag) | — | presença ok; integridade: só o arquivo do lote; amostragem pela sessão a seguir (grep dos três CT) |
| 12 | impl. | **Codex CLI** como executor de CT-B (contrato `fw-executor-ctb`, lote B1: CT-B01…CT-B06 em `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php`) | modelo padrão do Codex | `00`–`04`; proibido `app/`, `tests/Pest.php`, wiki | 6 `it` (8 execuções: CT-B04 com 3 linhas); 7/8 verdes no executor; CT-B03 vermelho **(a)** no teto de 3 execuções: o `05` esperava a mensagem inline do Filament, mas o campo `required()` é barrado pela validação nativa do navegador (o Filament expande a seção pelo evento `invalid` e o navegador mostra a mensagem no campo focado). Sessão reescreveu o oráculo do passo 5 e o `05` (Seletores com os ids reais `form.configuracoes.{aba}.{campo}`, mecanismo do CT-B03, isolamento para o CT-B05) — **8/8 verdes, 32 asserções** (`pest tests/Browser/ConfiguracoesSecoesColapsaveisTest.php --compact`) | exit 0 · ~58 s de Pest no executor | `git status`: só o arquivo novo; amostragem CT-B02 (`closest('.fi-section')` + refresh), CT-B04 (URL lida da troca de aba, sem literal) e CT-B06 (`select` sem salvar) conferidas por leitura; `id()` da seção não vira atributo HTML — fato registrado no `05` |
| 13 | impl. | **Codex CLI** como executor de CT (contrato `fw-executor-ct`, lote L2: CT-02 e CT-19 acrescentados a `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php`) | modelo padrão do Codex | `00`, `01`, `02`, `03`, `05`; proibido `app/`, `docs/`, `CHANGELOG.md`, `.ai/`, `tests/Pest.php`, wiki | 4 `it` novos (CT-02 ×3: ausência do nome antigo em docs/READMEs incluindo a célula `\| **Kit** \|`, mínimo por arquivo da agulha da aba Aplicação, exatamente 8 menções históricas abaixo de `## [0.49.0]`; CT-19 ×1). CT-02 verde; CT-19 **vermelho (b) esperado** até o step 12 (rule ausente). Correção (a) do `04`: o mínimo de configurações pt/en é 8/8, não 9/9 (a sessão somara a célula da tabela de abas) — `04` corrigido pela sessão | exit 0 · 2 execuções do Pest | `git diff --stat`: só o arquivo do lote (+74); `it` existentes intactos; amostragem: regex do CT-19 exige título `## ` com seção/ícone/colaps e corpo com `tod[ao]`, `colaps`, `ícone` e `ConfiguracoesSecoesColapsaveisTest` — casa com o CT-19 do `04`; contagens da tag reproduzidas pela sessão (8/8) |
| — | 0, 2, 4 | Sem despacho — captura verbatim do requisito, `RQ`, nome da feature, `00`/`01`/`02`/`03` escritos pela sessão (edição com forte interdependência; os fatos vieram do despacho #1 e do vendor lido pela sessão) | — | — | — | — | — |

## Blockers

## Desvios do Plano
- **P-03 (lote L1, causa a)**: a premissa dizia que a aba com erro de validação carrega sozinha; o Filament 5.10.2 carrega **todas** as abas adiadas na resposta com erro (`vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:isDeferredSchemaLoaded():220`, `:238-258`: todo componente cujo caminho de estado é prefixo da chave do erro marca a schema, e os contêineres têm o caminho raiz `data`). `00` P-03 reescrita; CT-09 e M26 reescritos no `04`.
- **P-06 nova (lote L1)**: `$loadedDeferredSchemas` é `#[Locked]` (`InteractsWithSchemas.php:$loadedDeferredSchemas:55`, `:53`); a linha do `02` dizia "sem `#[Locked]`" — corrigida; CT-14 passou a afirmar a recusa (M38 reescrito).
- **CT-17/CT-B05 (lote L1, causa a)**: os campos das seções de provedor não têm regra de validação (`secaoDoProvedor()` sem `required()`), então o cenário "erro em seção que nasce colapsada" não tinha erro para disparar; trocados para a seção colapsável *Proteção anti-robô* (pontuação mínima fora de 0–1, `required`/`numeric`/`minValue`/`maxValue`), sem a marca `@pendente-Q1`.
- **D8 (lote C2)**: ver `01` › Decisões de Desenho — chaves aninhadas e helpers `chaveDoCampo()`/`carregarAba()` em `tests/Pest.php`; migração dos testes existentes (lote T0).

## Notas de Implementação

## Referências Abertas
- `hosts-e-subagentes.md` — step 3 (antes do primeiro despacho) — 2026-10-15
- `roteamento-e-despacho.md` — step 3 — 2026-10-15
- `pesquisa-step-3.md` — step 3 — 2026-10-15
- `template-00-requisito.md` — step 4 — 2026-10-15
- `entrevista-tres-raias.md` — step 4 — 2026-10-15
- `template-01-plano.md` — step 4 — 2026-10-15
- `template-02-adr.md` — step 4 — 2026-10-15
- `template-03-progresso.md` — step 4 — 2026-10-15
- `padrao-de-log.md` — step 4 — 2026-10-15
- `estrutura-criada.md` — step 4 — 2026-10-15
- `glossario.md` — step 4 — 2026-10-15
- `citacoes-de-codigo.md` — step 4 — 2026-10-15
- `ponytail-caveman.md` — início — 2026-10-15

## Retrospectiva
- **Funcionou bem**: —
- **Faltou no plano**: —
