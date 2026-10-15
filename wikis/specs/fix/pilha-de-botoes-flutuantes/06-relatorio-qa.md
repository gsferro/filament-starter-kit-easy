# Relatório de QA — fix/pilha-de-botoes-flutuantes: Pilha harmônica dos botões flutuantes (dock × "Voltar ao topo" × chat da IA)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS)
> Natureza da wiki: correção · Toca infra compartilhada: sim → `KitServiceProvider::configureCorrecoesDeCss()` e a blade do render hook global do "Voltar ao topo" · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa (ciclo 2; no ciclo 1 o despacho trouxe uma alegação de resultado da suíte, não usada como evidência)
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação.** Blocker 0 · Major 1 · Minor 5. QA-01 (Major: `01`/`02` defasados depois dos Adendos 4–8), QA-02 (contraste de "Cancelar" no tema escuro), QA-03 ("nada muda" contra a P-03 nas docs e no CHANGELOG), QA-04 (Fora de Escopo do `00`), QA-05 (estado e fatiamento do `03`), QA-06 (glossário e grafia "organization"). Todos corrigidos pela sessão em `4bc9508`.

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 5 · Cosmético: 2
- Não verificadas: G (nível 3), H, J (`--tia`), K (K2) — ver *Não Verificado*
- `RQ` abertas: nenhuma. RQ-05 (release v0.48.1) fora desta entrega por declaração do `01`: sai depois do merge, pelo passo 5
- Ambiente: app em `http://127.0.0.1:8000` (200) · Pest 5.0.5 · PCOV 1.0.12 e Xdebug carregados · MCP: Boost indisponível (CONNECT_TIMEOUT), Playwright MCP não exposto ao agente

### Achados do ciclo 1, conferidos no ciclo 2

| ID | Situação |
|---|---|
| QA-01 | Resolvido; sobrou defasagem de outra origem (QA-07, QA-09) |
| QA-02 | Resolvido: `dark:text-gray-400` no "Cancelar" e regra na folha |
| QA-03 | Resolvido: docs pt/en e CHANGELOG dizem "30 px da borda, no eixo do chat" |
| QA-04 | Resolvido |
| QA-05 | Resolvido |
| QA-06 | Resolvido: `wikis/glossario.md` e "organization" |

## Achados

### QA-07 — o `01` ainda cita a P-01, já substituída pela P-03 · Minor · destino 1
- **Dimensão**: L3 · **Relacionado a**: P-01, P-03, passos 1–3
- **Observado**: seis linhas do `01` (Impacto, Riscos, passo 1 e os `Atende` dos passos 1–3) usavam a P-01 como vigente; a cobertura ligava a P-03 só ao passo 2.
- **Repro**: `grep -rn "P-01" wikis/specs/fix/pilha-de-botoes-flutuantes/`
- **Resolução na sessão**: trocado por P-03, com a marca de data; cobertura da P-03 nos passos 1, 2 e 3.

### QA-08 — "os mesmos requests de hoje", mas a folha nova é um request a mais em todo painel · Minor · destino 1
- **Repro**: `curl -s http://127.0.0.1:8000/admin/login | grep -o "kit-assistente-chat[^\"]*"` → `kit-assistente-chat.css?v=5.8.2.0`
- **Resolução na sessão**: `## Modelo de Execução` do `01` diz "+1 folha CSS, cacheável, em todos os painéis".

### QA-09 — o passo 7 dizia que `montarAtalho` devolve "o id e o nome" da organização · Minor · destino 1
- **Observado**: devolve só o nome; o id sai da chave por `DockDeAtalhos::organizacaoDaChave()`.
- **Resolução na sessão**: passo 7 corrigido.

### QA-10 — nenhum teste exercita rota sem nome em `TelaDeAutenticacao::rota()` · Minor · destino 3
- **Evidência**: mutantes `FalseToTrue` e `RemoveEarlyReturn` do early return de rota em branco saíam UNCOVERED.
- **Resolução na sessão**: CT-12 no `04` (M96) e em `tests/Kit/TelasSemDockNemChatTest.php`; verde (4/4) e vermelho com o early return invertido (2 falhas).

### QA-11 — flake no CT-B02 ancestral do dock: cor lida no meio da transição de tema · Minor · destino 4
- **Repro**: `vendor/bin/pest tests/Browser/DockDeAtalhosTest.php --compact`, três vezes — 1 falha em 3: `Failed asserting that 0.34705267717012817 is equal to 0.2 or is less than 0.2`
- **Destino**: wiki ancestral `dock-de-acoes-rapidas`; não reprova esta feature. **Débito aceito.**

### QA-12 — comentário da blade do "Voltar ao topo" citava linhas do chat que mudaram · Cosmético · destino 2
- **Resolução na sessão**: citações por símbolo (`'Abrir assistente'`, `'Assistente'`).

### QA-13 — checkboxes da `## Verificação Final` abertos com o trabalho feito · Cosmético · destino 1
- **Resolução na sessão**: fechados com evidência.

## Dimensões (ciclo 2)

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; diff por passo cobre 1–4, 6 e 7 |
| B | Fronteiras e dados | ✅ | nome da organização e tooltip escapados pelo pacote; rota nula ou vazia tratada |
| C | Matriz de permissão | ✅ | nenhuma ação nova; favoritos com `->authorize(PERMISSAO)`; CT-04, CT-09, CT-10 |
| D | Observabilidade | ✅ | nenhum log novo; n.a. declarado com motivo no `01` |
| E | Performance | ✅ | CT-06 (zero consulta nova pelo dock) |
| F | UX de erro | ✅ | sem estado de erro novo |
| G | Tema e cor | ⏭️ parcial | nível 1 e 2 ok (CT-B09, CT-B10); nível 3 sem Playwright MCP |
| H | Acessibilidade | ⏭️ | sem `assertNoAccessibilityIssues()`; por leitura: `aria-label` e Tab (CT-B06) |
| I | Segurança da superfície nova | ✅ | step 9; `$routeName` `#[Locked]` recolocado no `hydrate()` |
| J | Regressão adjacente | ⏭️ parcial | ancestrais por arquivo verdes; `--tia` vetado por memória; QA-11 |
| K | Adequação da suíte | ⏭️ | K1 1 candidato rejeitado; K2 com arnês implausível no Windows; QA-10 |
| L | Consistência documental | ⚠️ → ✅ | QA-07, 08, 09, 12, 13 resolvidos na sessão; scripts L1, L2, L4, L6 exit 0 |

## Débitos Aceitos

- QA-11 — flake do CT-B02 ancestral (`dock-de-acoes-rapidas`), destino 4: esperar o fim da transição de tema antes de medir.
- Cobertura: G nível 3, H (axe), J `--tia` e K2 não verificados — limites do ambiente (sem Playwright MCP, memória, arnês do `--mutate` no Windows).

## Suspeitas Não Confirmadas

- O CT-04 escreve em `STDOUT` (`fwrite`) os destinos das linhas condicionais; ruído na saída da suíte. Hipótese de quebrar o subprocesso do `--mutate` não confirmada (o mesmo sintoma aparece sem `fwrite`).

## Não Verificado

- **K2** — 8 e 85 mutantes em 0,26 s e 2,73 s contra suítes de 42 s e 15 s: assinatura do arnês quebrado. Só a execução com `TelasSemDockNemChatTenancyTest` (62 s) é plausível, com 3 sobreviventes sem triagem (`RemoveNullSafeOperator` nas linhas de `getByName()?->` e `route()?->`, `BooleanAndToBooleanOr` no `is_string && class_exists`).
- **G nível 3** e **H** — sem Playwright MCP; sonda negada pelo hook.
- **J** — `pest --parallel --tia` vetado pela memória da máquina.
- **Boost MCP** — CONNECT_TIMEOUT.
