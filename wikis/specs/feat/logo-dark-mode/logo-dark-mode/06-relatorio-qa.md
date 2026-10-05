# Relatório de QA — feat/logo-dark-mode: logo da marca por tema (claro/escuro)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS — toggle `->live()` + swap por tema no cliente)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `IdentidadeDoKit::doDisco()`, `TelaBloqueio`, aba Identidade das settings · Regressão: sim
> Independência: em linha — mesma sessão que escreveu a wiki (modo degradado, declarado)
> Cobertura: 12 de 12 dimensões executadas · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 6 · Cosmético: 0
- Não verificadas: nenhuma dimensão inteira — parciais declaradas em *Não Verificado* (medição dinâmica de E/H, contagem de queries)
- `RQ` abertas: nenhuma (RQ-11/RQ-12 são cláusulas de processo — declaradas sem cenário no `04`)
- Ambiente: `vendor/bin/pest` local · Pest 4 · suite `tests/Kit` executada ao fim do ciclo · MCP/Playwright: não usado (evidência via CT-B01 + pest-plugin-browser)

## Achados

### QA-01 — `P-04` vigente sem regra citando-o na Origem · Minor · destino 1 — corrigido no ciclo

- **Dimensão**: A
- **Relacionado a**: P-04, CT-16, `04` Mapa de Regras R6
- **Esperado**: toda `P-nn` vigente com cenário que a exerça citado na Origem de uma regra
- **Observado**: `rastreabilidade.sh` acusou `P-04 sem CT` — a resolução `session('tenant_corrente')` + `Tenant::find()` é exercida pelo CT-16 (R6), mas nenhuma Origem citava P-04
- **Repro**: `bash .claude/skills/feature-wiki/scripts/rastreabilidade.sh {wiki}` → linha `00-requisito.md:97: P-04 sem CT`
- **Destino**: 1 — célula de Origem da R6 passou a citar `P-04` (CT-16 já a exercitava; era omissão de citação, não de teste)
- **Estado**: resolvido — re-run do script: só restam RQ-11/RQ-12 (ver QA-02)

### QA-02 — RQ-11 e RQ-12 sem CT · Minor · destino 5 — não-defeito, rejeitado com motivo

- **Dimensão**: A
- **Esperado**: todo `RQ` com CT
- **Observado**: `rastreabilidade.sh` acusa `RQ-11 sem CT` e `RQ-12 sem CT`
- **Repro**: idem — saída `00-requisito.md:73-74`
- **Destino**: 5 — são cláusulas de **processo**, não de comportamento: RQ-11 = "escrever o `LogoDarkModeTest`" (satisfeita pela existência do arquivo, 24 casos verdes) e RQ-12 = "filacheck, pint, commit, PR" (gate de finalização). O `04` as declara explicitamente no comentário `RQ fechadas sem cenário` — forma prevista da feature-test-design
- **Ação exigida**: nenhuma — registro para não reaparecer no próximo ciclo

### QA-03 — `it()` sem prefixo `[CT-nn]` no nome (21 cenários) · Minor · destino 1 — corrigido no ciclo

- **Dimensão**: L1
- **Relacionado a**: todos os CT-01…CT-20 + CT-B01
- **Esperado**: `it('[CT-nn] …')` — convenção da casa (`AbasDeListagemTest`, `RodapeNaDobraTest`, forma multi-CT `[CT-B04][CT-B10]`)
- **Observado**: `ids-ct.sh` acusou os 21 IDs presentes no `04`/`05` e ausentes nos nomes dos testes (os IDs estavam só no docblock)
- **Repro**: `bash .claude/skills/feature-wiki/scripts/ids-ct.sh {wiki} 'tests/**/LogoDarkMode*.php'` → 21 linhas `CT-nn sem teste`
- **Destino**: 1 — nomes dos `it()` prefixados (`[CT-01][CT-02][CT-03]` agrupados quando um `it` cobre vários)
- **Estado**: resolvido — re-run: exit 0

### QA-04 — Citações `arquivo:símbolo:linha` inválidas (2) · Minor · destino 1 — corrigido no ciclo

- **Dimensão**: L2
- **Observado**: (a) `03:106` `ConfiguracoesDoKit.php:965-971` sem símbolo e apontando a classe errada — basename ambíguo: `arquivo()` mora na **página** (`app/Filament/Admin/Pages/`, linha 998), o short-path resolvia para a Settings (581 linhas); (b) `04:57` símbolo `x-filament-panels::logo` fora do formato (`::` lê como `Classe::método`)
- **Repro**: `bash .claude/skills/feature-wiki/scripts/citacoes.sh {wiki}` → 2 linhas
- **Destino**: 1 — (a) path completo + `:arquivo():998`; (b) prop `$logo` na linha 8 (onde o `@if` vive)
- **Estado**: resolvido — re-run: exit 0

### QA-05 — Checkboxes `[x]` sem ` — {evidência}` (13) · Minor · destino 1 — corrigido no ciclo

- **Dimensão**: L6
- **Observado**: `checkbox-sem-evidencia.sh` acusou 13 caixas fechadas sem evidência nos passos 1–11 do `03`
- **Repro**: `bash .claude/skills/feature-wiki/scripts/checkbox-sem-evidencia.sh {wiki}` → 13 linhas
- **Destino**: 1 — evidência por CT colada em cada checkbox
- **Estado**: resolvido — re-run: exit 0

### QA-06 — Vocabulário decidido na feature fora do glossário · Minor · destino 1 — corrigido no ciclo

- **Dimensão**: L7
- **Relacionado a**: `wikis/glossario.md`
- **Esperado**: termo decidido na feature registrado no glossário do projeto
- **Observado**: "marca unificada"/"marca separada" (RQ-02/03) e "variante escura" (`logo_dark`) são vocabulário decidido nesta feature e aparecem em código, docs pt/en e UI — sem entrada no glossário
- **Destino**: 1 — duas entradas adicionadas a `wikis/glossario.md`
- **Estado**: resolvido neste ciclo

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 1 → 3 linhas, todas resolvidas/rejeitadas (QA-01/02); sem `07-tickets/` (coluna Ticket n/a); `git diff` por passo confere: 13 arquivos, nenhum fora do escopo |
| B | Fronteiras e dados | ✅ | estático: path órfão (CT-02), par vazio/um/par (CT-10..13), `tenant_corrente` adulterada guardada por `is_int/is_string` (coleção → null); sem `--agent` — dinâmico em *Não Verificado* |
| C | Matriz de permissão | ✅ | sem ação/rota nova: a página de settings segue `View:ConfiguracoesDoKit` e o CRUD do tenant segue as policies existentes — cobertura herdada, testes vizinhos verdes |
| D | Observabilidade real | ✅ | `tenancy` (motivo/tenant_id/slug/logo_escura) e `configuracoes` (doDisco herda log de arquivo ausente); context estruturado, sem PII (só id/slug/bool) |
| E | Performance | ✅ estática | `once()` memoiza o `find` do tenant; 1 `exists()` por variante por render; sem loop nem query nova — contagem medida em *Não Verificado* |
| F | UX de erro | ✅ | validação `required` nativa pt_BR + helpers pt; erro não expõe interno |
| G | Tema e cor | ✅ | `--mecanismo` exit 1 (Filament dark class detectado) → G roda; nível 1 `dark-mode.sh` no diff: exit 0, limpo; nível 2: CT-B01 prova `display` computado nos dois temas + screenshots em `art/` |
| H | Acessibilidade | ✅ parcial | alt = nome da organização (CT-19), `<img>` com alt obrigatório; axe/`assertNoAccessibilityIssues` não rodado — em *Não Verificado* |
| I | Segurança da superfície nova | ✅ | step 9 cobriu os eixos do diff (1 achado upstream corrigido: `doDisco()` → `asset()`); além: mass-assignment deliberado (`logo_dark` espelha `logo`), upload herda mimes/disk/teto do campo irmão, guarda de sessão adulterada, sem rota nova |
| J | Regressão adjacente | ✅ | toca infra compartilhada → rodou; vizinhos `IdentidadeVisualTest` + `ConfiguracoesDoKit{,Tela}Test` 74/74; suite `tests/Kit` completa executada — resultado no `03` |
| K | Adequação da suíte | ✅ | K1 `k1-oraculo-fraco.sh` exit 0; K2 `pest --mutate` **92.45%** (49 testados, 4 uncovered — `TelaBloqueio:134` match do `motivo` e `:269/:273` guardas do `mount()` pré-existentes, fora do diff; duração 10.96s plausível); revisão adversarial do `04`: NÃO FEITA — host sem sub-agente (declarado) |
| L | Consistência documental | ⚠️→✅ | 5 achados Minor (QA-03/04/05/06) — todos corrigidos; L1 exit 0, L2 exit 0, L4 exit 0, L6 exit 0 após correção; L5 docs pt × en espelhadas, CHANGELOG não tocado (sem release ainda) |

## Débitos Aceitos

- E (contagem de queries não medida) e H (axe não rodado) — medidas estáticas; o swap e o alt têm oráculo versionado em CT-B01/CT-19
- Independência degradada: gate rodado na mesma sessão que escreveu a wiki — achados garantidos por script mecânico (saída reproduzível acima), não por julgamento
- K2 com 4 mutantes uncovered nomeados — nenhum vivo; score acima do piso (70%)

## Suspeitas Não Confirmadas

- `TelaBloqueio.php:134` (`match` do `motivo`) marcado `UNCOVERED` apesar de CT-15 executar o ramo `blank($logo)` — provável artefato de atribuição de linha do coverage; sem repro que o torne achado

## Não Verificado

- E — contagem de queries por request não medida (`--agent`/profile não usados); justificativa estática acima
- H — `assertNoAccessibilityIssues()` não executado (axe); mitigação: CT-19 + `assertVisible`/computed display do CT-B01
- Revisão adversarial do `04` — `Revisão adversarial: NÃO FEITA` no cabeçalho (host sem sub-agente)
