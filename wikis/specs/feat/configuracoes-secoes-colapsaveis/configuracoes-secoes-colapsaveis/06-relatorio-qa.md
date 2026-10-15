# Relatório de QA — feat/configuracoes-secoes-colapsaveis: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: carregamento adiado, colapso em Alpine, `localStorage`)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → `.ai/rules/pages.md`, `CapturaDeArteTest`, `art/` (e, no diff, `tests/Pest.php`) · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 2 · Cosmético: 1
- Não verificadas: H, K (K2) — as causas estão em *Não Verificado*
- `RQ` abertas: nenhuma no `00`. Q1, Q2, Q3, Q4 e Q6 seguem `aberta`, e os recortes de Q1 a Q4 estão implementados (QA-01)
- Ambiente: app em `http://localhost:8000` · Pest 5.0.5 (PHPUnit 13.3.0) · MCP: Boost indisponível (timeout de conexão), Playwright MCP ausente · PCOV e Xdebug carregados (`php -m`) · `pest-plugin-mutate` instalado (`ls vendor/pestphp/`)
- CT-19 vermelho: débito conhecido do step 12 (a rule entra depois deste veredito), não é defeito

## Achados

### QA-01 — Recortes de perguntas abertas implementados com a recomendação, e as RQ marcadas `fechada` · Major · destino 1

- **Dimensão**: A (passo 2, auditoria do requisito)
- **Relacionado a**: RQ-01, RQ-02, RQ-03 e RQ-05; Q1, Q2, Q3, Q4 e Q6; passos 2 e 3 do PRD; CT-05, CT-12, CT-B02 e CT-B04
- **Esperado**: na raia requisito, só o solicitante responde. O recorte que depende de uma pergunta fica bloqueado até a resposta entrar como Adendo com fonte. Ver `feature-wiki` SKILL.md:338-343 e `entrevista-tres-raias.md:41`.
- **Observado**:
  - No `00:48-52`, Q1, Q2, Q3, Q4 e Q6 estão `aberta`, mas cada recomendação está implementada: `persistCollapsed()` e o estado inicial expandido (Q1), o agrupamento em seções (Q2), a queda de qualquer `?tab=` antigo para a primeira aba, que o CHANGELOG já promete (Q3), e o aviso do dock dentro de uma seção (Q4). Os cenários CT-05, CT-12, CT-B02 e CT-B04 fixam essas respostas.
  - As RQ afetadas estão `fechada` no `00:32-36`. Por isso o `rastreabilidade.sh` não tem como acusar nada (exit 0).
  - A autorização citada no `03:93` e no `04` ("implemente tudo", 2026-10-15) não está no `00`: não aparece em `## Texto Original` nem como Adendo.
  - Q6 coincide com o comportamento do framework, sem código da feature: confirmei o 403 por `hydrateCanAuthorizeAccess` (ver C). Essa parte só pesa no teto.
- **Repro**:
  1. `sed -n 30,52p {wiki}00-requisito.md`: RQ `fechada` × Q `aberta`.
  2. `grep -n "recorte dependente" {wiki}01-plano-acao.md` → linhas 19-20: "implementada a recomendação".
  3. `grep -n "persistCollapsed" app/Filament/Admin/Pages/ConfiguracoesDoKit.php` → `secao()` e `secaoDoProvedor()`.
- **Evidência**: as saídas acima; `bash .claude/skills/feature-wiki/scripts/rastreabilidade.sh {wiki}` → exit 0, silencioso.
- **Destino**: 1, especificação.
- **Ação exigida**: o solicitante responde Q1, Q2, Q3, Q4 e Q6. A forma pode ser um Adendo com fonte ou o registro verbatim do "implemente tudo" como resposta a cada Qn. Depois, a coluna `Estado` e o `@pendente-Q1` dos cenários são reconciliados. Se uma resposta contrariar a recomendação, o recorte volta bloqueado e é re-derivado.

### QA-02 — PRD e `04` afirmam o que o código contradiz (chave da aba na URL; `tests/Pest.php`) · Major · destino 1

- **Dimensão**: L (L3), com reflexo na J
- **Relacionado a**: RQ-01, Q3, D1, D8, CT-B04
- **Esperado**: o PRD diz o que o código faz, ou leva a marca `*(alterado em …)*`.
- **Observado**:
  - O `01:88` diz que a chave na URL "passa de `kit::tabs` para `aplicacao::tabs`". A tabela *Chaves das abas* (`01:251-258`) lista `identidade::tabs` … `aplicacao::tabs (antes kit::tabs)`. O `04:77` repete "a chave `aplicacao::tabs` na URL".
  - No código, `Tab::key('aplicacao')` e `Tabs::getActiveTab()` comparam `getKey(isAbsolute: false)` (`vendor/filament/schemas/src/Components/Tabs.php:156`), então a URL é `?tab=aplicacao`. É o que o CHANGELOG diz (`?tab=identidade`, `?tab=tabelas`) e o que a própria Q3 corrigida diz.
  - O `01:10` (Natureza) afirma "nenhuma mudança em … `tests/Pest.php`". O diff acrescenta `chaveDoCampo()` e `carregarAba()` (+20 linhas), que é a infra de teste compartilhada da D8.
  - O `03:47` declara "Desvios propagados: `01` (Mapeamentos …)", mas essas linhas ficaram sem marca.
- **Repro**:
  1. `grep -rn "::tabs" {wiki}` → `01:88`, `01:253-258`, `04:77` (e `04:627`, linha de cortado).
  2. `git diff --stat main...HEAD -- tests/Pest.php` → `20 +`.
  3. `sed -n 147,170p vendor/filament/schemas/src/Components/Tabs.php`.
- **Evidência**: as saídas acima. CT-B04 está verde lendo a URL que a tela escreve, sem literal.
- **Destino**: 1.
- **Ação exigida**: corrigir `01:10`, `01:88`, `01:251-258` e `04:77` com a marca de data (o `04:627` só se ainda for citado como fato), e conferir que nenhuma outra cópia de `::tabs` sobra na wiki.

### QA-03 — Resumos de P-03 desatualizados ("a aba com erro carrega sozinha") e Cobertura sem P-06/P-07 · Minor · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: P-03, P-06, P-07, R7
- **Esperado**: P-03 reescrita no lote L1 diz que, com erro de validação, **todas** as abas adiadas carregam.
- **Observado**: o texto antigo, sem marca, continua em vários lugares:
  - `01:29` (Cobertura: "aba com erro carrega sozinha"), `01:41` e `01:127`;
  - o docblock de `aba()` em `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:310-311`;
  - a linha R7 do Mapa de Regras (`04:46`), cujo título no `04:327` já foi corrigido.
  - Além disso, `## Cobertura do Requisito` não tem linha para P-06 e P-07. O script não acusa, porque o CT com `Origem` existe.
- **Repro**: `grep -rn "carrega sozinha" {wiki} app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- **Evidência**: as linhas acima. CT-09 está verde afirmando as demais abas carregadas.
- **Destino**: 1.
- **Ação exigida**: alinhar os quatro trechos e o docblock a P-03, e acrescentar P-06 e P-07 à Cobertura.

### QA-04 — Roteiros do `05` e evidência da `## Conformidade` descrevem seletores e campos que o CT-B não usa · Minor · destino 1

- **Dimensão**: L (L3/L4, rule `testes-browser.md`)
- **Relacionado a**: CT-B03, CT-B04, CT-B05, CT-B06, RD-05, CR-05
- **Esperado**: o roteiro executável e a evidência de rule "aplicada" descrevem o teste versionado.
- **Observado**: `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` usa `closest('section')`, `[aria-expanded]` e os ids `#form\.configuracoes\.{aba}\.{campo}`. Mesmo assim:
  - `05:277` e os passos 3 e 5 do CT-B03 citam `.fi-section-header` e `fi-collapsed`;
  - os roteiros do CT-B04, CT-B05 e CT-B06 usam `#form\.paginacao_padrao`, `#form\.nome_da_aplicacao`, `#form\.mail_mailer` e `#form\.mail_host` (ids antigos);
  - a linha de Seletores (`05:37`) e o roteiro (`05:280`) dizem que o CT-B05 usa o `client_id` do Google com `requiredIf`, quando o teste usa a pontuação do anti-robô;
  - o Gherkin do CT-B05 diz "seção colapsável aninhada", mas a seção anti-robô é filha direta da aba;
  - o `03:79` (Conformidade, `testes-browser.md`) cita `closest('.fi-section')` como evidência.
- **Repro**: `grep -n "fi-collapsed\|fi-section-header\|#form\\\\\.\(paginacao\|nome_da\|mail_\|login_anti\)" {wiki}05-casos-de-teste-browser.md` × `grep -n "aria-expanded\|closest" tests/Browser/ConfiguracoesSecoesColapsaveisTest.php`
- **Evidência**: as linhas acima; o CT-B está 9/9 verde.
- **Destino**: 1.
- **Ação exigida**: reescrever os roteiros, a linha de Seletores, o título e Gherkin do CT-B05 e a evidência do `03:79` conforme o teste.

### QA-05 — Contagem de mutantes do cabeçalho do `04` defasada · Cosmético · destino 1

- **Dimensão**: L (L1)
- **Observado**: `04:20` diz "Mutantes previstos: 54", mas `grep -cE '^\| M-?[0-9]+' 04-casos-de-teste.md` → **55**. O M55 entrou no step 9. As cópias do 54 no `03:131` e `03:134` são registro histórico de despacho.
- **Repro e evidência**: o `grep -c` do `template-04.md` §*Contagem do cabeçalho*. Cenários 20, Regras 13 e Sem matador 0 conferem.
- **Ação exigida**: recalcular a linha pelo `grep -c`.

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-01 | aba "Kit" → "Aplicação" (recorte Q3) | 1, 4, 5 | CT-01, CT-02 | CT-B04 | `abaAplicacao()` | ✅; URL e links antigos pela recomendação de Q3 | ⚠️ QA-01, QA-02 |
| RQ-02 | todo item em seção (recortes Q2, Q4) | 2 | CT-03, CT-11, CT-18 | CT-B06 | `secao()` | ✅; agrupamento e aviso do dock pela recomendação | ⚠️ QA-01 |
| RQ-03 | toda seção colapsável com ícone (recorte Q1) | 2 | CT-04, CT-05, CT-12 | CT-B02 | `secao()`, `secaoDoProvedor()` | ✅; persistência e estado inicial pela recomendação | ⚠️ QA-01 |
| RQ-05 | abas adiadas (recorte Q6) | 3 | CT-06…CT-10, CT-13…CT-17, CT-20 | CT-B01, CT-B05 | `aba()` | ✅; Q6 = comportamento do framework, sem código | ⏳ teto |
| P-06/P-07 | lista travada; persistência nos dois sentidos | — (Cobertura sem linha) | CT-14 | CT-B02 | vendor | ✅ | ⚠️ QA-03 |

RQ-04 está a cargo do step 12: CT-19 vermelho, débito conhecido. RQ-06 a RQ-09 são de processo, conferidas no `01` e no git.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-01 — `rastreabilidade.sh` exit 0; `indice.sh --check`: sem `07-tickets/` (não fatiado, `03:34`); `git diff` por passo: os passos 1–3 tocam `ConfiguracoesDoKit.php` como descrito |
| B | Fronteiras e dados | ✅ | CT-13 e CT-B04 verdes. Sondagem de `loadDeferredSchema` com `""`, `form`, `form.configuracoes`, chave inexistente, 600 chars, `<script>`, caminho de campo e chave sem `form.`: todas com `carregadas=[]` e `loading=6` (falha fechada) |
| C | Matriz de permissão | ✅ | papel único com acesso (admin). Com a tela aberta, ao trocar para um usuário sem `View:ConfiguracoesDoKit` (id 3), `loadDeferredSchema` dá 403 (`assertForbidden` passou; `CanAuthorizeAccess::hydrateCanAuthorizeAccess()`). A gravação já tem o CT-33 da ancestral |
| D | Observabilidade | ✅ | sem log novo (decisão do `01`). `afterSave` no channel `configuracoes` com `[Classe@Método]` e context `user_id` + nomes de campo: sem valor, sem PII. As chaves de `$data` continuam planas |
| E | Performance | ✅ | HTML inicial 37 340 B (6 `fi-sc-loading`); com as 6 abas, 390 954 B. 3 queries no mount e 3 por carga de aba, constantes (sem N+1) |
| F | UX de erro | ✅ | o diff não traz mensagem nova. Erro em aba não carregada fica visível (CT-09, CT-B05). Link antigo cai na primeira aba, com conteúdo (CT-B04) e aviso no CHANGELOG |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 `dark-mode.sh {AM}` exit 0; sem cor própria no diff. Nível 3 olhando `art/admin-configuracoes-tabelas-escuro.png`, regravada nesta branch: cabeçalho, ícone e chevron legíveis. Não foi o MCP ao vivo |
| H | Acessibilidade | ⏭️ | sem `assertNoAccessibilityIssues()` e sem MCP. Só estático: `aria-expanded` no botão de colapso e `aria-busy` no placeholder (usados pelos CT-B) |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9 (`03:57-69`: 8 linhas de achado, 1 rejeitada — RD-03). Coberto além do step 9: reautorização por hydrate (ver C), sondagem de chaves (ver B), nenhuma rota nova, nenhum `public` novo na Page, segredos fora do HTML (CT-10, CT-15) |
| J | Regressão adjacente | ✅ | `--testsuite=Kit --parallel`: 4 219 testes, 4 215 verdes, 3 pulados, 1 vermelho (CT-19, esperado), 2 *risky*. `Tenancy --parallel`: 631/631. Navegador ancestral (`ConfiguracoesDoKitTest`, `AlertaDeAlteracoesNaoSalvasTest`, `LoginSocialTest`): 5/5. `--tia` não rodado (a suíte completa o substitui; ver *Não Verificado*). Previsto × medido: o `01` não previa `tests/Pest.php` (QA-02) |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, 14 candidatas, todas rejeitadas: estão em `it()` que o diff não alterou (CapturaDeArte `:1071…:1348`, `DashboardDinamicoTest:38`, `SeletorDeOrganizacaoTenancyTest:136`). Os 3 arquivos da feature saíram silenciosos. K2 não verificada (ver *Não Verificado*). Revisão adversarial do `04`: feita, 2 rodadas; o débito declarado é o CT-B06 sem revisão |
| L | Consistência documental | ⚠️ | 4 achados (QA-02 a QA-05). L1 `ids-ct.sh` exit 1, 1 linha (`[CT-50]` de `site-de-documentacao`, rejeitada: arquivo compartilhado). L2 `citacoes.sh` exit 0. L4 `conformidade-rules.sh` exit 0, porém com a evidência do `03:79` defasada (QA-04). L5: docs pt × en coerentes e com rastro (RQ-03, RQ-05, P-07). L6 `checkbox-sem-evidencia.sh` exit 0, números reproduzidos (ver abaixo). L7: nenhum termo de domínio novo; sem conflito com o glossário |

L6 — reprodução das alegações do `03` › `## Verificação Final`:

- **18 `it` / 31 execuções**: `{"tests":31,"passed":31,"assertions":366}`, e `grep -c` deu 18. Confere.
- **CT-B**: `{"tests":9,"passed":9,"assertions":41}`. Confere.
- **Kit 4 219 e Tenancy 631**: conferem. Os 2 vermelhos daquela execução viraram 1, porque o CT-26 foi corrigido no `4a130c05`.
- **HTML 37 350 / 390 954 B**: medi 37 340 / 390 954. A diferença de 10 B no inicial é variação do render: usuário, token CSRF.
- **Scripts declarados com exit 0**: conferem.
- **`ids-ct.sh`**: confere.
- **Degradação "`pest --mutate` não mede no Windows"**: a ferramenta existe (plugin, PCOV e Xdebug), mas a alegação é de não confiabilidade, não de ausência, e tem medição documentada. Não é achado.
- **Amostragem 4/4 e pint/filacheck**: não reproduzidas (ver *Não Verificado*).

## Débitos Aceitos

- Nenhum ainda. QA-03, QA-04 e QA-05 (Minor/Cosmético) vão para o `03` se o loop os aceitar.

## Suspeitas Não Confirmadas

- **P-05 pelo caminho do servidor**: nenhum CT-B cobre erro de validação **do servidor** em campo de seção **colapsada pelo usuário**. O CT-B03 cobre a validação nativa; o CT-B05 usa uma seção que nasce expandida. O mecanismo (`handleFormValidationError` → `expand`) não foi exercitado. Não reproduzi defeito: é candidato a destino 3 se a sessão quiser fechar a classe.
- **2 testes *risky* na suíte Kit**: não identifiquei se são anteriores à branch.

## Não Verificado

- **H (acessibilidade)**: sem axe (`assertNoAccessibilityIssues`) nos CT-B e sem Playwright MCP; teclado e foco não exercitados.
- **K2 (mutação medida)**: o `pest --mutate` grava cache (`.pest/`) na árvore, o que este gate não pode fazer. E a medição no Windows está documentada como não confiável mesmo com `pestw.cmd`. A amostragem manual do `03` (4/4) exige mutar arquivo e `git checkout`, proibido ao gate. É prova indispensável pendente da K.
- **J, passo 1 (`--tia`)**: não rodado, porque grava o grafo na árvore. Substituído pela suíte completa Kit + Tenancy.
- **Playwright MCP e Boost MCP**: indisponíveis. As dimensões dinâmicas foram feitas com Pest, `php artisan tinker --execute` somente leitura e as capturas de `art/`.
- **`pint --dirty` e `filacheck` da Verificação Final**: não reexecutados.
