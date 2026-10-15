# Relatório de QA — feat/configuracoes-secoes-colapsaveis: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: carregamento adiado, colapso em Alpine, `localStorage`)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → `.ai/rules/pages.md`, `CapturaDeArteTest`, `art/`, `tests/Pest.php` · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 1 · Cosmético: 1, todos achados novos deste ciclo (QA-06, QA-07). QA-01 a QA-05 do ciclo 1 estão fechados.
- Não verificadas: H e K (K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. Q1, Q2, Q3, Q4 e Q6 seguem `aberta`, com a recomendação implementada sob a RQ-10 (Adendo 1); a confirmação do solicitante fica pedida no PR e conta como débito.
- Ambiente: app em `http://localhost:8000` · Pest 5.0.5 · Boost MCP indisponível · Playwright MCP ausente · PCOV e Xdebug carregados.
- CT-19 vermelho: débito conhecido do step 12, não é defeito.

## Ciclo 2 — fechamento dos achados do ciclo 1

| Achado (ciclo 1) | Estado | Conferência |
|---|---|---|
| QA-01 · Major · recortes de Q1, Q2, Q3, Q4 e Q6 implementados sem resposta | **fechado** | O `00` ganhou o `## Adendo 1`, com texto verbatim e fonte. Dele sai a RQ-10, que manda implementar a recomendação de cada pergunta aberta e levar a confirmação ao PR. Com isso, implementar os recortes passou a cumprir uma cláusula do oráculo, e não a interpretação do dev. A RQ-10 está na Cobertura do `01` e no `04`; `rastreabilidade.sh` exit 0. O resíduo fica no teto: as perguntas continuam `aberta` e a resposta é pedida no PR. |
| QA-02 · Major · `::tabs` e `tests/Pest.php` no PRD | **fechado** | `01:10`, `01:88`, a tabela *Chaves das abas*, `04:77` e `04:627` foram corrigidos com marca de data. `grep -rn "::tabs"` nos `00` a `05` só acha registro histórico (Q3, a nota da tabela, `03` RD-04/step 5/Despachos/Retrospectiva, ADV-03) e a partição `nada::tabs` do CT-B04. |
| QA-03 · Minor · P-03 desatualizada, Cobertura sem P-06/P-07 | **fechado** | `grep -rn "carrega sozinha"` só acha registro histórico (`03:107`, `03:151`) e o `05:62` ("a aba ativa carrega sozinha"), que é correto: a aba ativa carrega por viewport. O docblock de `aba()` foi reescrito, e só ele mudou em `app/`. P-06 e P-07 estão na Cobertura. |
| QA-04 · Minor · roteiros e seletores do `05`, evidência do `03:79` | **fechado** | Os ids aninhados, `<section>`, `[aria-expanded]` e o CT-B05 pela pontuação do anti-robô conferem com `tests/Browser/ConfiguracoesSecoesColapsaveisTest.php`. O `03:79` foi reescrito. Ficou só o resíduo cosmético do QA-07. |
| QA-05 · Cosmético · cabeçalho com 54 mutantes | **fechado** | O cabeçalho diz 55, e `grep -cE '^\| M-?[0-9]+' 04-casos-de-teste.md` → 55. |
| Suspeita P-05 (erro do servidor em seção colapsada pelo usuário) | débito aceito pela sessão | registrado no `03` › Quality Gate |

## Achados

### QA-06 — A evidência do `citacoes.sh` na Verificação Final diz que só o `06` tem citação sem símbolo, mas o próprio `03:50` também tem · Minor · destino 1

- **Dimensão**: L (L2/L6)
- **Relacionado a**: `03` › `## Verificação Final`, linha do `citacoes.sh`
- **Esperado**: o `[x]` traz a saída real do script.
- **Observado**: o `03:50` afirma que "as únicas linhas são duas citações sem símbolo dentro do `06-relatorio-qa.md`". Ao repetir as duas citações do `06` (`Tabs.php:156` e `ConfiguracoesDoKit.php:310-311`) sem símbolo, a própria linha do `03` também é acusada. O script devolve 4 linhas, 2 delas no `03`.
- **Repro**: `bash .claude/skills/feature-wiki/scripts/citacoes.sh {wiki}`
- **Evidência** (exit 1):
  ```
  03-progresso.md:50: Tabs.php:156 — citação sem símbolo: use {arquivo}:{símbolo}:{linha}
  03-progresso.md:50: ConfiguracoesDoKit.php:310-311 — citação sem símbolo: use {arquivo}:{símbolo}:{linha}
  06-relatorio-qa.md:46: vendor/filament/schemas/src/Components/Tabs.php:156 — citação sem símbolo …
  06-relatorio-qa.md:64: app/Filament/Admin/Pages/ConfiguracoesDoKit.php:310-311 — citação sem símbolo …
  ```
- **Destino**: 1.
- **Ação exigida**: no `03:50`, citar com símbolo (`Tabs.php:getActiveTab():156`, `ConfiguracoesDoKit.php:aba():310-311`) ou descrever sem a forma de citação, e colar a saída real do script. As linhas do `06` seguem como limitação declarada.

### QA-07 — Resíduo "aninhada" no MB12 do CT-B05 · Cosmético · destino 1

- **Dimensão**: L (L3)
- **Observado**: `05:223`: "MB12 | a seção aninhada fica colapsada…". A premissa do CT-B05 (`05:206`) já diz "filha direta da aba".
- **Repro**: `grep -n "aninhada" {wiki}05-casos-de-teste-browser.md`
- **Ação exigida**: trocar por "a seção do campo".

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0 no ciclo 2; `indice.sh --check`: sem `07-tickets/` (não fatiado). QA-01 fechado pela RQ-10; Q1–Q4/Q6 aguardam a resposta no PR (teto) |
| B | Fronteiras e dados | ✅ | ciclo 1: CT-13 e CT-B04 verdes; sondagem de 8 chaves em `loadDeferredSchema`, todas com falha fechada. O ciclo 2 não muda código executável |
| C | Matriz de permissão | ✅ | ciclo 1: 403 em `loadDeferredSchema` depois da troca para um usuário sem permissão (`hydrateCanAuthorizeAccess`) |
| D | Observabilidade | ✅ | ciclo 1: sem log novo; `afterSave` sem valores nem PII |
| E | Performance | ✅ | ciclo 1: 37 340 B no HTML inicial × 390 954 B com as seis abas; 3 queries constantes por carga de aba |
| F | UX de erro | ✅ | ciclo 1: o diff não traz mensagem nova; erro em aba não carregada fica visível (CT-09, CT-B05) |
| G | Tema e cor | ✅ | ciclo 1: `dark-mode.sh --mecanismo` exit 1 (Filament); nível 1 exit 0; nível 3 pela captura escura de `art/` |
| H | Acessibilidade | ⏭️ | sem axe e sem MCP |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9 (8 linhas, 1 rejeitada — RD-03). Coberto além do step 9 no ciclo 1: reautorização por hydrate, sondagem de chaves, nenhum `public` novo, segredos fora do HTML (CT-10, CT-15) |
| J | Regressão adjacente | ✅ | ciclo 1: Kit 4 219 testes (4 215 verdes, 3 pulados, CT-19), Tenancy 631/631, navegador ancestral 5/5. Ciclo 2: a única mudança em `app/` é um docblock (+1 linha); arquivos da feature 48/49 (só CT-19 vermelho); `CitacoesDeCodigoTest` 3/3, que confere as citações deslocadas pela linha nova |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1 no ciclo 1, 14 candidatas rejeitadas (fora dos hunks do diff); arquivos da feature silenciosos. K2 não verificada. Revisão adversarial do `04`: feita, 2 rodadas |
| L | Consistência documental | ⚠️ | 2 achados novos (QA-06, QA-07). L1 `ids-ct.sh` exit 1, 1 linha (`[CT-50]` de outra wiki, rejeitada). L2 `citacoes.sh` exit 1, 4 linhas: 2 no `06` (limitação declarada) e 2 no `03` (QA-06). L4 `conformidade-rules.sh` exit 0, evidência do `03:79` conforme o teste. L6 `checkbox-sem-evidencia.sh` exit 0; a linha do `citacoes.sh` não reproduz (QA-06). L7 sem conflito |

## Débitos Aceitos

- **Q1, Q2, Q3, Q4 e Q6**: recomendação implementada sob a RQ-10 (Adendo 1), com confirmação do solicitante pedida no PR. Resposta contrária vira Adendo 2 e re-derivação.
- **Suspeita P-05 pelo caminho do servidor**: aceita como débito no `03` › Quality Gate.
- QA-06 (Minor) e QA-07 (Cosmético), se a sessão não os corrigir antes do PR.

## Suspeitas Não Confirmadas

- O texto do Adendo 1 que autoriza "implemente tudo … abrindo o PR" vem da feature anterior da sessão. Nesta feature há só os "continue". O próprio Adendo declara essa origem, então não é achado, mas o solicitante deve vê-lo no PR junto das Qn.
- Os 2 testes *risky* da suíte Kit (ciclo 1) não foram atribuídos a esta branch.

## Não Verificado

- **H (acessibilidade)**: sem axe (`assertNoAccessibilityIssues`) e sem Playwright MCP.
- **K2 (mutação medida)**: o `--mutate` grava `.pest/` na árvore e mede errado no Windows. A amostragem manual do `03` (4/4) exige mutar arquivo, proibido ao gate.
- **J, passo 1 (`--tia`)**: não rodado, porque grava cache. A suíte completa do ciclo 1 o substitui.
- **`pint --dirty` e `filacheck`**: não reexecutados.

## Ciclo 1 — resumo (histórico)

Veredito **REPROVADO → especificação**: 0 Blocker, 2 Major, 2 Minor, 1 Cosmético.

- QA-01 — perguntas abertas implementadas sem resposta (Major, destino 1)
- QA-02 — `::tabs` e `tests/Pest.php` contradizendo o código (Major, destino 1)
- QA-03 — P-03 desatualizada e Cobertura sem P-06/P-07 (Minor, destino 1)
- QA-04 — roteiros e seletores do `05` e evidência do `03:79` (Minor, destino 1)
- QA-05 — 54 mutantes no cabeçalho, 55 reais (Cosmético, destino 1)

Todos foram corrigidos no `df9c5cfd` e conferidos acima. O texto integral do ciclo 1 está no histórico git deste arquivo (`df9c5cfd`).
