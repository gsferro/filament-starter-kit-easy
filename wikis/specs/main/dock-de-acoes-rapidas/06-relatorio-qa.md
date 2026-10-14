# Relatório de QA — dock-de-acoes-rapidas: Dock de ações rápidas (favoritos do usuário)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: o dock usa Alpine e assets do pacote; domínio sensível: autorização entre painéis e organizações)
> Natureza da wiki: nova · Toca infra compartilhada: sim → matriz de papéis, settings da instalação, tabela `users`, os três `PanelProvider`, inventário de permissões · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 3 · Cosmético: 1
- Não verificadas: D (parcial), G, H. As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. A Q28 segue aberta sobre a P-02 (premissa, não `RQ`), como no ciclo 1.
- Ambiente: app em `http://localhost:8000` (`/admin` 302, `/admin/login` 200) · Pest 5.1.1 · Playwright MCP indisponível · Boost MCP usado (`database-query`, `last-error`)

Entre os dois ciclos só mudou texto: `git diff 1424e41 HEAD -- app tests config database resources routes` sai vazio. Os dois Major do ciclo 1 (QA-01 e QA-02) estão fechados. O QA-04 e o QA-05 também. O QA-03 foi fechado só em parte e volta como QA-03 (reaberto). Os achados novos (QA-06 a QA-08) vêm da medição K2 refeita por este gate e da L6.

## Achados

### QA-03 (reaberto) — O termo "organização corrente" ainda aparece no `02`, no `04` e na doc en · Minor · destino 1

- **Dimensão**: L (L7)
- **Relacionado a**: RQ-25, RQ-35; glossário "Organização aberta (en: open organization)"
- **Esperado**: a correção do ciclo 1 ("organização aberta" no `01`, no `04` e nas docs) vale para toda a wiki e para os dois idiomas.
- **Observado**: o `01` e a doc pt foram corrigidos, e os três termos novos entraram no glossário. O termo antigo continua em quatro lugares. No `02`: ADR-01, complementação (2) e *Consequências → Negativas*. No `04`: a definição do estado "de outra organização" e as linhas "organização vinculada, mas não a corrente" (dataset do CT-43 e mapa de IDOR). Em `docs/en/recursos/hub-de-navegacao.md`, seção Dock: "the current organisation".
- **Repro**: `grep -n -i "organização.\{0,30\}corrente\|não a corrente\|da corrente" wikis/specs/main/dock-de-acoes-rapidas/0[1-5]*.md` e `grep -n "current" docs/en/recursos/hub-de-navegacao.md`
- **Evidência**: 5 linhas no `02`/`04` (`02` linhas 23 e 34; `04` linhas 100, 1099 e 1556) e as linhas 57–58 da doc en.
- **Destino**: 1
- **Ação exigida**: trocar por "organização aberta" / "open organization" no `02`, no `04` e na doc en. O `00` não é editado. "Catálogo corrente" e "contexto corrente" não são o termo do glossário e podem ficar.

### QA-06 — 31 mutantes de `DockDeAtalhos` não são cobertos por nenhum teste da feature, entre eles a recusa do servidor em `gravar()` · Minor · destino 3

- **Dimensão**: K (K2)
- **Relacionado a**: RQ-29 (recusa em `DockDeAtalhos::gravar()`), RQ-14 (`habilitadoNoPainel(null)`), RQ-11/ADR-02 (chave adulterada: painel inexistente, organização não numérica), RQ-23 (rota ou página nula, Resource sem `index`), RQ-31 (segmento do rótulo)
- **Esperado**: todo ramo de regra do `DockDeAtalhos` é exercido por algum CT.
- **Observado**: a mutação com as duas suítes da feature dá 396 mutantes. São 364 testados e 1 timeout, todos mortos, e 31 não cobertos. Os não cobertos se dividem assim:
  - **Recusa de RQ-29 dentro de `gravar()`** (linhas 271–277: o `throw` e o log). Nunca é executada: o `Select` do slideover recusa a chave fora das opções antes de a action chamar `gravar()`. Os CT-17, CT-52 e CT-54 provam a RQ-29 de ponta a ponta, mas a barreira própria do servidor nunca foi exercida.
  - **Ramos que falham fechado**: linhas 93, 343, 671, 708, 749, 759, 781, 814 e 859.
  - **Logs**: linhas 217–219 e 361–363. Pertencem à dimensão D e não pedem CT.
  - **Fora do filtro ou artefato da ferramenta**: `opcoesDeEstilo()` (linhas 140–142) é exercida pela `ConfiguracoesDoKitTelaTest`, que não entrou no filtro. A constante `MAXIMO_DE_ATALHOS` (linha 81) é linha que a cobertura não rastreia, e o teto é provado pelos CT-33/34.
- **Repro**: `XDEBUG_MODE=coverage cmd //c "$(cygpath -w .claude/skills/feature-wiki/scripts/pestw.cmd)" --testsuite=Kit,Tenancy --filter=DockDeAtalhos --mutate --path=app/Support/DockDeAtalhos.php --parallel --no-cache`
- **Evidência**: `Mutations: 31 uncovered, 1 timeout, 364 tested` · `Score: 92.17%` · `Duration: 658.29s` · `Parallel: 20 processes`. Não há sobrevivente coberto. O md5 do `.env` foi igual antes e depois.
- **Destino**: 3
- **Ação exigida**: levar os não cobertos à `feature-test-design`. Prioridade: (1) a recusa de `gravar()` chamada sem passar pelo formulário, ou a decisão registrada de que a barreira do `Select` é a única; (2) as chaves adulteradas (painel inexistente, organização não numérica); (3) `habilitadoNoPainel(null)`. Logs ficam fora.

### QA-07 — A `## Verificação Final` declara "score 100,00 %" sem dizer que 80 mutantes foram excluídos · Minor · destino 1

- **Dimensão**: L (L6)
- **Relacionado a**: `03-progresso.md`, `## Verificação Final`, linha do `pest --mutate`; o `## Quality Gate` repete "316 mutantes, 0 sobreviventes"
- **Esperado**: o número publicado é a saída do comando, e o leitor fica sabendo o que ficou de fora.
- **Observado**: o comando declarado traz `--covered-only` e só `tests/Kit/DockDeAtalhosTest.php`. Reproduzi a parte coberta: 316 mutantes (310 testados + 6 timeout), 0 sobreviventes, 605,75 s. O número confere e a duração é plausível. Mas o mesmo comando sem `--covered-only` mostra mais 80 mutantes não cobertos, e o score real sobre a classe é 79,80 %. Com a suíte Tenancy incluída, sobe para 92,17 % (QA-06). O "100,00 %" dá a entender que a classe inteira está protegida.
- **Repro**: o comando do `03` sem `--covered-only` (`... tests/Kit/DockDeAtalhosTest.php --mutate --path=app/Support/DockDeAtalhos.php --parallel --no-cache`)
- **Evidência**: `Mutations: 80 uncovered, 6 timeout, 310 tested` · `Score: 79.80%` · `Duration: 605.75s`. `grep -rn "100,00\|316 mutantes" wikis/specs/main/dock-de-acoes-rapidas/` acha o número no `03` (Verificação Final e Quality Gate).
- **Destino**: 1
- **Ação exigida**: substituir, nos dois lugares do `03`, pela saída das duas suítes (396 mutantes, 31 não cobertos, 92,17 %, 658 s) e citar o QA-06.

### QA-08 — O docblock de `avaliar()` ainda cita a P-01 e promete "zero consulta" · Cosmético · destino 2

- **Dimensão**: L (L3)
- **Relacionado a**: P-04 (substitui a P-01), CT-71/72
- **Esperado**: o comentário do código segue a premissa vigente. A P-04 admite uma consulta por favorito (`Gate::before` → `isMasterGlobal()`).
- **Observado**: o docblock de `DockDeAtalhos::avaliar()` diz "(P-01, `organizacaoDoRequest()`): ele custa zero consulta a mais que a página já fez". O docblock de `organizacaoDoRequest()` também cita a P-01.
- **Repro**: `grep -n "P-01" app/Support/DockDeAtalhos.php`
- **Destino**: 2
- **Ação exigida**: citar a P-04 e a consulta admitida. Só o comentário muda.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; `indice.sh --check` exit 0; o código não mudou desde o ciclo 1, que conferiu o diff passo a passo. Os passos 6 e 7 e `## Autorização` do `01` agora descrevem o código (QA-01/02 fechados) |
| B | Fronteiras e dados | ✅ | Herdada do ciclo 1 (código igual): CT-09, 12, 14, 17, 28, 33, 34, 40, 54. A mutação do QA-06 mostra que as chaves adulteradas por JSON não têm CT |
| C | Matriz de permissão | ✅ | Herdada do ciclo 1: nenhuma célula destrutiva sem CT; `PermissoesDeAcoesTest` verde de novo neste ciclo |
| D | Observabilidade | ⏭️ parcial | Estático limpo (canal, prefixo, context sem PII nem chave crua). Log real não lido: `LOG_KIT_DRIVER=monolog` forçado no `phpunit.xml` e dock desligado no banco local (`dock_paineis = []`) |
| E | Performance | ✅ | CT-35, 36, 56, 65, 71 e 72 verdes neste ciclo (Kit 124/124, Tenancy 44/44) |
| F | UX de erro | ✅ | Herdada do ciclo 1 (mensagens pt-BR, `halt()` mantém o slideover aberto) |
| G | Tema e cor | ⏭️ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 `dark-mode.sh` exit 0 nos 10 arquivos AM de `app/`; níveis 2 e 3 não rodados |
| H | Acessibilidade | ⏭️ | CT-B01 sem `assertNoAccessibilityIssues()`; Playwright MCP indisponível |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (14 achados, 2 rejeitados). Além dele, no ciclo 1: nenhuma rota nova, `$fillable` intacto, IDOR por usuário e por organização (CT-13, 28, 61). Código igual |
| J | Regressão adjacente | ✅ | Suíte completa do ciclo 1 (4443 testes, só a falha de baseline) vale porque o código não mudou. Neste ciclo, com docs e glossário alterados: `SiteDeDocumentacao`, `RedeDeDocumentacao`, `ConfiguracoesDoKitTela`, `PermissoesDeAcoes` e `CitacoesDeCodigo` 167/167 |
| K | Adequação da suíte | ⚠️ | K1 `k1-oraculo-fraco.sh` exit 1, 1 candidato rejeitado (CT-10 afirma no helper `afirmarLayoutEEstiloDoDock()`). K2 refeita com `--no-cache`, 20 processos: só Kit, 316 cobertos com 0 sobreviventes e 80 não cobertos (605 s); Kit + Tenancy, 365 cobertos com 0 sobreviventes e 31 não cobertos, triados no QA-06 (658 s). Duração compatível com 124 + 44 testes por mutante em paralelo. Revisão adversarial do `04` feita |
| L | Consistência documental | ⚠️ | QA-03 (reaberto), QA-07, QA-08. L1 `ids-ct.sh` exit 0; cabeçalho do `04` confere (75 = `grep -c`). L2 `citacoes.sh` exit 1: as 4 linhas são do `06` do ciclo 1, reescrito aqui sem citação `arquivo:linha`. L4 `conformidade-rules.sh main` exit 0. L5: doc pt alinhada; doc en no QA-03. L6 `checkbox-sem-evidencia.sh` exit 0; Kit 124/124 e Tenancy 44/44 reproduzidos; Ponytail agora declarado como dívida (QA-04 fechado). L7: glossário com os 3 termos novos |

## Débitos Aceitos

- QA-03 (Minor): termo "organização corrente" no `02`, no `04` e na doc en. Replicar no `03`.
- QA-06 (Minor): 31 mutantes não cobertos em `DockDeAtalhos`, entre eles a recusa do servidor em `gravar()`. Replicar no `03`.
- QA-07 (Minor): score "100,00 %" sem os mutantes excluídos. Replicar no `03`.
- QA-08 (Cosmético): docblocks com P-01. Replicar no `03`.

## Suspeitas Não Confirmadas

- O erro `Class "App\Filament\Dock\PluginDoDock" not found` (`last-error`, 03:12:20) é o mesmo do ciclo 1, anterior ao commit 1424e41, e não se repetiu.

## Não Verificado

- D: log real não lido. O canal de teste é `NullHandler` forçado, e o dock está desligado no banco local. Ligá-lo e gravar favorito alteraria o banco de desenvolvimento.
- G: nível 2 (sem CT-B em tema escuro) e nível 3 (Playwright MCP indisponível).
- H: sem `assertNoAccessibilityIssues()` no CT-B01 e sem MCP.
- K2 em `App\Filament\Dock\PluginDoDock`: não mutado (o `01` só pede `DockDeAtalhos`). Fica fora a guarda do hook `call` e o aviso do teto.
- L6: "6 de 9 falham sem a correção" (step 9). Reproduzir exige restaurar arquivos de `app/`, fora do contrato do gate.

## Ciclo 1 — histórico

**REPROVADO → especificação** (2026-10-14; 8 de 12 dimensões; não verificadas D parcial, G, H, K2 com o `--mutate` ainda rodando)

| ID | Achado | Sev. | Destino | Estado no ciclo 2 |
|---|---|---|---|---|
| QA-01 | O `01` afirmava cast de enum em `dock_layout` que o `User` não tem | Major | 1 | fechado: passos 6 e 7 do `01` com marca de 2026-10-14 |
| QA-02 | O `01` dizia que todo favorito passa por painel, organização e troca de contexto (sem P-04, RD-01, RD-05) | Major | 1 | fechado: `## Autorização` e passo 7 com marca |
| QA-03 | "Organização corrente" × glossário; termos decididos fora do glossário | Minor | 1 | parcial: reaberto acima |
| QA-04 | Passe Ponytail "manual" com a skill instalada | Minor | 1 | fechado: linha reescrita como dívida declarada |
| QA-05 | Checklists de passos do `03` abertos | Cosmético | 1 | fechado: itens com evidência por ticket |

Nota: as citações `arquivo:linha` do texto do ciclo 1 não seguiam o formato `arquivo:símbolo:linha` (`citacoes.sh` exit 1). Por isso o histórico foi resumido sem elas.
