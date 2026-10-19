# Relatório de QA — agentes-ia-no-settings: Agentes de IA ligados pelo settings, por painel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: chat flutuante e pilha de botões; domínio sensível: autorização do catálogo e dado do usuário indo ao provedor de IA)
> Natureza da wiki: nova · Toca infra compartilhada: sim → settings do kit, `config/kit.php`, tela de configurações, render hook global do chat, `voltar-ao-topo`, `tests/Pest.php` · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa (artefatos do scratchpad da sessão, como `falsificabilidade.py`/`.out` e `suite-final2.log`, foram lidos só como evidência da L6)
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 4 · Cosmético: 1
- Não verificadas: G, H, K (as causas estão em *Não Verificado*)
- `RQ` abertas: nenhuma. Ficam abertas as perguntas Q1, Q3, Q4, Q6, Q8, Q9 e Q10. Todas estão cobertas por premissa (P-01, P-03, P-06, P-07, P-08) ou determinadas por cláusula fechada. A P-01 (a lista de painéis governa o chat, não o catálogo) depende da Q1: se o solicitante responder diferente, a feature muda de forma.
- Ambiente: app em `http://127.0.0.1:8010` (sqlite local), Pest 5.0.5, PCOV e Xdebug presentes, `pest-plugin-mutate` instalado. Playwright MCP indisponível (não exposto) e Boost MCP indisponível (falha de conexão).

O único Major é documental e barato de corrigir. O comportamento entregue confere com o `00` nos testes e no app servido.

## Achados

### QA-01: o resultado do mutante M-h da Verificação Final não tem evidência · Major · destino 1

- **Dimensão**: L6
- **Relacionado a**: `03-progresso.md` `## Verificação Final`, linha do `pest --mutate`, e a dimensão K
- **Esperado**: todo número de um `[x]` sai de um comando que o reproduz (SKILL, L6; Proibição 11).
- **Observado**: o `03` diz *"8/8 mortos … M-h `Resource::canAccess()` sem o interruptor → CT-09/CT-11 2 vermelhos"*. O script citado roda o M-h só com `--filter=CT-09`, e não com CT-11. A execução registrada morreu com `UnicodeEncodeError: 'charmap' codec can't encode character '⌘'` no `print` do M-h, que é o caractere ⌘ do nome do mutante. Por isso o resultado do M-h nunca foi impresso nem gravado: o `falsificabilidade.log` não existe e a linha "RESTAURADO" não saiu. A evidência existente cobre 7 de 8 mutantes, não 8 de 8.
- **Repro**:
  1. `cat {scratchpad da sessão}/falsificabilidade.out` mostra as linhas de M-a a M-g, depois o Traceback e `EXIT=1`. Não há linha do M-h.
  2. `sed -n 44,47p falsificabilidade.py` mostra o M-h com `['tests/Kit/AgentesIaNoSettingsTest.php', '--filter=CT-09']`.
- **Evidência**: `grep -rn "8/8\|M-h" wikis/specs/feat/agentes-ia-no-settings/` aparece só em `03-progresso.md:68`.
- **Destino**: 1, especificação (texto da wiki).
- **Ação exigida**: rodar de novo só o M-h (com `PYTHONIOENCODING=utf-8`) e colar a saída real em `03-progresso.md:68`, ou reescrever como "7/8 com evidência, M-h sem saída". Corrigir também "CT-09/CT-11" para o filtro que de fato rodou.

### QA-02: a ADR e a Superfície Livewire do `02` não acompanharam o código final · Minor · destino 1

- **Dimensão**: L3
- **Esperado**: o `02` descreve o código entregue, e cada desvio fica marcado `*(alterado em …)*`.
- **Observado**:
  - `02:6` diz *"As nove decisões de desenho (D1 a D9)"*, mas o `01` tem de D1 a D11 (D10 e D11 nasceram no step 7).
  - A `## Superfície Livewire` foi "re-varrida pré-step 9". Ela não lista o `public function hydrate()` novo de `AssistenteChatWidget` (RD-01). Também não lista o `authorizeAccess()` que o trait `ExigeAgentesDeIaLigados` passou a sobrescrever. A frase *"nenhum public function de instância novo"* ficou falsa.
- **Repro**: `grep -n "public function hydrate\|function authorizeAccess" app/Livewire/AssistenteChatWidget.php app/Filament/Concerns/ExigeAgentesDeIaLigados.php` × `02-decisoes-arquiteturais.md:6,14,31`.
- **Ação exigida**: corrigir "D1 a D9" (o `grep -rn "D1 a D9" wikis/specs/feat/agentes-ia-no-settings/` dá só `02:6`) e acrescentar as duas linhas à tabela, com a data.

### QA-03: o `04` tem afirmações defasadas sobre a Q8 e sobre o arquivo de CT-24 e CT-26 · Minor · destino 1

- **Dimensão**: L3/L5
- **Observado**:
  - `04:107` diz *"Q8 respondida: texto do plano aceito"*, mas o `00` mantém a Q8 `aberta`.
  - `04:822` diz que *"CT-24 e CT-26 vivem em `tests/Kit/AgentesIaNoSettingsTest.php`"*, mas eles estão em `AgentesIaNoSettingsChatTest.php`. O próprio Índice de Cenários está certo.
- **Repro**: `grep -o "\[CT-[0-9]*\]" tests/Kit/AgentesIaNoSettingsChatTest.php | sort -u` lista CT-24 e CT-26.
- **Ação exigida**: alinhar `04:107` ao estado da Q8 no `00` e corrigir o path em `04:822`.

### QA-04: o prefixo de log descrito no `01` e no `04` difere do código · Minor · destino 1

- **Dimensão**: L3 (e D)
- **Observado**:
  - O passo 5 do `01` dá `[ExigeAgentesDeIaLigados@authorizeResourceAccess]` e o passo 7 dá `[AssistenteChatWidget@assertContexto]`, assim como `04:110`.
  - O log real usa `[ExigeAgentesDeIaLigados@exigirUsoDeAgentesLigado]` e `[AssistenteChatWidget@exigirUsoNoPainel]`, conferido em `storage/logs/ai-2026-10-19.log`, entradas das 20:18:28 e 20:21:31.
- **Ação exigida**: atualizar o `01` (passos 5 e 7) e o `04:110`, com a marca `*(alterado em …)*`.

### QA-05: "catálogo" tem dois sentidos contra o glossário · Minor · destino 1

- **Dimensão**: L7
- **Observado**:
  - `wikis/glossario.md` define **Catálogo (do dock)**.
  - A feature usa "catálogo" também para o Resource "Agentes de IA": no `01`, no `04` (CT-09 tem as duas coisas na mesma tabela, "catálogo do dock" e "o catálogo Agentes de IA"), no helperText da tela e no docblock.
  - O termo não entrou no glossário, e o `03` registra "nenhum termo novo decidido".
- **Ação exigida**: incluir no glossário "Catálogo de agentes", com *Não confundir com* "Catálogo (do dock)", ou trocar o termo nos textos.

### QA-06: checkboxes abertos defasados no `03` · Cosmético · destino 1

- **Dimensão**: L6
- **Observado**: três itens seguem `[ ]` embora feitos:
  - `## Testes` "`tests/Pest.php` — `ligarAgentesDeIa()`", que está no diff e é usado por 14 arquivos.
  - `## Verificação Final` "`git commit`", embora 6 commits existam.
  - `## Processo do pedido` "`codex --yolo` usado", embora o despacho #1 o registre.
- **Ação exigida**: fechar cada um com a evidência.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; sem `07-tickets/`. O `git diff` por passo (1 a 9) confere com os arquivos citados. No app servido: RQ-04 (sem link e sem item no `/admin` desligado), RQ-05 (404 nas 3 URLs, inclusive editar com chave real), RQ-06 (200 nas 3 ligado), RQ-09 (widgets ausentes ou presentes), P-01 (chat só no painel da lista), P-03 e P-06 por HTTP real (abaixo) |
| B | Fronteiras e dados | ✅ | Partições cobertas por CT verde: CT-08 (texto, `apps`, `App`, lista no toggle, "talvez"), CT-03 (bloco ausente, lista nula), CT-16 (ids fora das opções). Sem sonda `--agent` (o Pest 5.0.5 não tem a opção); fallback por leitura e CT |
| C | Matriz de permissão | ✅ | CT-11 e CT-13 cobrem lista, criar e editar × com e sem permissão × ligado e desligado; CT-15 cobre `/app` e `/infra` 404; chat por posse no CT-22 do `AssistenteChatWidgetTest` e no CT-23/CT-24 |
| D | Observabilidade | ✅ | Log real no canal `ai`, nível `warning`, prefixo `[Classe@Método]`, context `user_id`/`pagina|painel`/`motivo`, sem PII. Um `warning` por request recusado (`novaConversa` e `$refresh`). Única divergência é o texto do prefixo contra o plano (QA-04) |
| E | Performance | ✅ | CT-06 verde (soma das consultas ao `settings` ligado = desligado, nenhuma com pilha da feature); decisor só lê `config()` |
| F | UX de erro | ✅ | 404 em pt ("Página não encontrada"), com saída "Início" e "Voltar", sem interno exposto; helperText do toggle avisa o 404 |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (classe `dark` do Filament nos 3 painéis); nível 1 `dark-mode.sh` exit 1 com 3 linhas `text-white` sobre `bg-primary-600`, rejeitadas como falso positivo e pré-existentes. Nível 2: CT-B01 só `inLightMode()`, e o `AssistenteChatEstiloTest` só mede o `/app`. Nível 3: sem MCP. Atenuante: os 3 painéis usam `CorPrimaria::paleta()` |
| H | Acessibilidade | ⏭️ não verificada | CT-B01 sem `assertNoAccessibilityIssues()` no `/admin` e no `/infra`; teclado e foco do chat nesses painéis não exercitados; sem MCP |
| I | Segurança da superfície nova | ✅ | As 4 linhas *eixo 9* foram cobertas pelo step 9 (12 achados, 3 rejeitados). Coberto além disso: nenhuma rota nova e nenhum mass assignment; IDOR de conversa pelos CT-22, CT-23 e CT-24. Por HTTP real (`/livewire-…/update`), com o snapshot do chat do `/admin` e a lista trocada para `["app"]`: `novaConversa` → 404 e `$refresh` → 404, com painel `admin` resolvido pelo middleware persistente. `$refresh` da `ListAgentesIa` após desligar → 404, sem "Assistente" no corpo. Discriminante nulo (painel nulo) fecha; o 404 tem saída |
| J | Regressão adjacente | ✅ | `suite-final2.log` (artefato, não reexecutado): 4.977 testes, 4.974 verdes, 3 pulados. Reexecutados por mim: ancestrais Kit/Tenancy 140/140 (`TelasSemDockNemChat*`, `AssistenteChatWidgetTest`, `VoltarAoTopoTest`, `AssistenteChatCssTest`, `ConfiguracoesSecoesColapsaveisTest`, `KitInfoTest`, `AgenteIaResourceTest`, `HelpersDeTesteTest`) e browser 29/29 (`AgentesIaNoSettings` + `PilhaDeBotoesFlutuantes` + `AssistenteChatEstilo`) |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1, 8 linhas, todas rejeitadas: os testes são pré-existentes e o diff só acrescentou `ligarAgentesDeIa()`. Os 4 arquivos novos não geraram linha. K2: amostragem manual da sessão com 7/8 evidenciados (QA-01). Minha execução `pestw.cmd --mutate --path=app/Support/AgentesDeIa.php --no-cache` (946 s) parou na rodada inicial: 1 falha `rename … Acesso negado` no cache de views, por colisão com testes que rodei em paralelo. Nenhum mutante medido. Revisão adversarial do `04`: feita |
| L | Consistência documental | ⚠️ | 6 achados (QA-01 a QA-06). L1 `ids-ct.sh` exit 0; L2 `citacoes.sh` exit 0; L4 `conformidade-rules.sh` exit 0, 14 rules (404 × rule `filament.md`: exceção declarada, CR-06 → step 12); L6 `checkbox-sem-evidencia.sh` exit 0. Números reproduzidos: 127/127 com 548 asserções (= 73+51+3 e 278+259+11), browser 2/2, cabeçalho do `04` 28/15/74/0, 14 `alterado em` no `01`. Docs pt × en × CHANGELOG coerentes |

## Débitos Aceitos

- QA-02, QA-03, QA-04, QA-05 (Minor) e QA-06 (Cosmético): texto da wiki. Replicar no `03-progresso.md`.

## Suspeitas Não Confirmadas

- Contraste do botão e do slide-over do chat no tema escuro do `/admin` e do `/infra`: nunca medido. Mesma paleta do `/app`, então o risco é baixo.

## Não Verificado

- **G**: níveis 2 e 3 no `/admin` e no `/infra`. Motivo: o CT-B01 só roda no tema claro, e o Playwright MCP está indisponível.
- **H**: axe, teclado e foco do chat nos painéis novos. Motivo: o CT-B01 não chama `assertNoAccessibilityIssues()`, e não há MCP.
- **K2**: o score de mutação não foi medido. Motivos:
  - a minha execução abortou na rodada inicial por colisão de cache de views;
  - `.ai/rules/testes.md` declara o `--mutate` não confiável no Windows;
  - a amostragem manual tem 7/8 com evidência (QA-01).
  - Prova pendente indispensável: o resultado do M-h.
- **J**: a suíte completa foi aceita pelo log `suite-final2.log` e não reexecutada, porque rodava em paralelo noutra sessão.
