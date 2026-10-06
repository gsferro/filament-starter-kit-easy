# Progresso — Issue #148: overrides autorais de `resources/views/vendor` no `kit:update`

**Estado**: em implementação

> Branch: `fix/kit-update-views-vendor` · Base do PR: `main` (`dcb3083`, v0.45.0)

## 1. As cinco pastas autorais em `CAMINHOS_DO_KIT`
- [x] Comentário de bloco + 5 entradas depois de `'resources/views/svg',`, na forma que `caminhosDeclaradosEm()` lê — `grep -c "^        '" app/Console/Commands/KitUpdate.php` = 84 (79 + 5); comentário de 7 linhas (teto 6 do ponytail estourado em 1: a última linha nomeia o teste que decide); `[CT-10]` verde, 2026-10-06

## 2. A varredura decide autoria em `resources/views/vendor`
- [x] Caso novo em `KitUpdateTest` (autoral → coberta; crua → fora; skip fora da árvore) pelo `fw-executor-ct`, a partir do `04` — CT-02…CT-08, CT-11…CT-14 + CT-01 no dataset e CT-10 renomeado; `pest tests/Kit/KitUpdateTest.php --compact` → 83/83, 133 asserções (despacho 3), 2026-10-06
- [x] Comentário do `continue` de `resources/views/vendor/` reescrito — aponta CT-06/CT-07 deste arquivo (`git diff -- tests/Kit/KitUpdateTest.php`), 2026-10-06
- [x] `media.blade.php` no dataset da fundação — chave `[CT-01] override da lock-screen` no `->with([...])` do caso `cobre os arquivos da fundação`, 2026-10-06

## 3. Recalcular as citações de `KitUpdate.php`
- [x] 20 citações em docs pt/en e 3 testes reancoradas; `CitacoesDeCodigoTest` e CT-66 verdes — 5 pelo `citacoes.py` (símbolo), 7 à mão (chave entre aspas: `'tests/Kit'`, `'tests/Pest.php'`, `'wikis/README.md'` ×2 idiomas, `CAMINHOS_SO_RELATORIO`) e 3 curtas (`483,1158`, `487-489`), todas +12; `pest CitacoesDeCodigoTest ChecklistDeReleaseTest ConstraintDeDependenciaTest DeployDockerLocalTest` → 47/47 (3 pulados); as citações de `wikis/specs/**` antigas **não** foram tocadas (registros datados, fora do `CitacoesDeCodigoTest` por decisão da v0.36.0), 2026-10-06

## 4. CHANGELOG
- [x] `[Unreleased]` → `### Corrigido` (#148) — entrada com as 5 pastas, as 7 de fora, o critério e o efeito para quem já editou uma delas; `[CT-11]` verde, 2026-10-06

## Testes
- [ ] `tests/Kit/KitUpdateTest.php` (CT-01, CT-02 ×14, CT-03, CT-04 ×3, CT-05, CT-06, CT-07, CT-08, CT-11, CT-12, CT-13, CT-14; CT-10 = caso existente `extrai do fonte desta versão…`, renomeado com o ID) *(alterado em 2026-10-06: revisão adversarial +3 CT, +7 Exemplos)*
- [x] CT-09 — procedimento com `--log-junit` sobre CT-06…CT-08 na extração do `git archive` (evidência aqui, não `it()`) — na extração `validacao-v0.45.0/novo-sem-tenant` com uma pasta `resources/views/vendor/projeto-x` publicada e editada pelo projeto, fora da lista: `php artisan test tests/Kit/KitUpdateTest.php --filter='CT-0[678]' --log-junit` → `{"result":"passed","tests":6,"passed":3,"skipped":3}`, os três `skipped` (o junit do Pest 5 não carrega o motivo; o motivo não vazio é provado pelo CT-13); **M17**: com o `->skip(…)` removido por `sed` na cópia da extração → `"result":"failed"`, mensagem citando `projeto-x/painel.blade.php` e "override AUTORAL … em KitUpdate::CAMINHOS_DO_KIT"; na árvore do kit os três passam (83/83). Cópia restaurada, pasta sonda removida, 2026-10-06

## Tickets
Não fatiado — 2026-10-06: 5 RQ vigentes, 11 CT, compactação: sim (da feature anterior desta sessão, antes do step 0 desta), 3 perguntas de requisito — nenhum sinal de tamanho (18 RQ / 60 CT); a compactação não é desta feature, que cabe numa sessão: sugestão não feita

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest tests/Kit/KitUpdateTest.php --compact`
- [ ] Regressão: `DuasRotasDeEntregaTest`, `LogoDarkModeTest`, `BotaoLimparCacheTest`, `CitacoesDeCodigoTest`, `ChecklistDeReleaseTest`, `ConstraintDeDependenciaTest`, `DeployDockerLocalTest`, `DiagramasDaArquiteturaTest`
- [ ] Mutantes manuais do caso novo: entrada removida da lista; pasta crua acrescentada à lista
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados ou rejeitados com motivo
- [ ] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa: `ids-ct.sh {wiki} 'tests/Kit/KitUpdateTest.php'` silencioso
- [ ] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade dos CTs novos: quantos falham sem o fix
- [ ] CHANGELOG reconciliado com o comportamento final; docs pt/en sem frase a mudar além das citações
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-06 — sessão autônoma, pelas recomendações (o solicitante confirma ao ler o PR; ele pediu "analise com cuidado o que foi reportado e corrija") — 2 rodadas (step 4; step 7 devolveu Q7); perguntas: 0 fato (resolvidas por leitura e pelo script de auditoria), 4 desenho (Q4–Q7 → D1–D3), 3 requisito (Q1–Q3 — nenhuma bloqueia passo: Q1 e Q2 implementadas pela direção que falha fechado como P-01 e P-02; Q3 é a tag, fora desta entrega)

### Perguntas da entrevista (step 4)

| Qn | Raia | Afeta | Pergunta | Recomendação adotada |
|---|---|---|---|---|
| Q1 | requisito | RQ-02, RQ-04 | as 7 pastas de publish cru saem do repositório? | não nesta entrega; fora de escopo declarado; nenhuma entra na lista (P-01) |
| Q2 | requisito | RQ-02, RQ-04 | `command-center` com 1 view idêntica: pasta inteira? | pasta inteira (P-02) |
| Q3 | requisito | RQ-01, RQ-05 | patch `v0.45.1` depois do merge? | recomendado sim; a sessão não cria a tag sem a palavra do solicitante |
| Q4 | desenho | RQ-03 | varredura em `KitUpdateTest` ou `DuasRotasDeEntregaTest`? | `KitUpdateTest`, onde está a exclusão (D1) |
| Q5 | desenho | RQ-02 | entrada por pasta, arquivo ou raiz `resources/views/vendor`? | pasta (D2) |
| Q6 | desenho | RQ-03, RQ-04 | oráculo de autoria: conteúdo × vendor, git, ou lista à mão? | conteúdo × vendor instalado (D3, P-03, P-05) |
| Q7 | desenho (devolvida pela derivação do `04`) | P-03 | mesmo caminho relativo em dois pacotes / view sem par: compara com o quê? | idêntica a **algum** candidato = cru; sem par = autoral (já era a D3); as duas linhas de CT-02 fechadas |

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| caso *cobre todo o código do kit* na linha 158, guarda `.github` na 166 | `tests/Kit/KitUpdateTest.php:'cobre todo o código do kit':160` e `tests/Kit/KitUpdateTest.php:'.github':167` (`citacoes.sh` acusou a segunda) | `01`, Análise — citações reancoradas, 2026-10-06 |
| docs `atualizando-o-projeto` não enumeram `CAMINHOS_DO_KIT` | confirmado: `grep -n "views/\|CAMINHOS_DO_KIT"` só acha a citação `app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT:93` (que não desloca: as entradas entram depois da 93), um diagrama e um exemplo histórico com `resources/views/errors` | nenhuma — passo 4 confirmado |
| nenhum channel de log do `kit:update` | confirmado: `config/logging.php` tem só `ai`, `tenancy`, `autenticacao` e `configuracoes` | nenhuma — D4 confirmada |
| `caminhosDeclaradosEm()` tem caso que compara o fonte desta versão com a constante | confirmado: `tests/Kit/KitUpdateTest.php:caminhosDeclaradosEm:409` (`toBe(caminhosDoKit())`) — a forma `        'caminho',` é obrigatória | nenhuma — passo 1 já exige a forma |

### Varredura da classe irmã (step 5)
Nenhuma classe nova nesta entrega. A "irmã" relevante é a **entrada** `'resources/views/svg'` de `CAMINHOS_DO_KIT`: ela aparece em `app/Console/Commands/KitUpdate.php` (constante e dois comentários), em `tests/Kit/KitUpdateTest.php` (dataset da fundação não a cita; a varredura de `DIRETORIOS_DE_CODIGO` a cobre) e em `tests/Kit/DuasRotasDeEntregaTest.php` (comentário da ocorrência 1). Conferido em 2026-10-06 com `grep -rn "resources/views/svg" app tests config docs README*`: 8 ocorrências, todas em **comentário ou prosa** (`KitUpdate.php` 208/229/248/689, `KitUpdateTest.php` 86/125, `DuasRotasDeEntregaTest.php` 23, docs pt/en 80/81). Nenhum inventário, seeder ou config lista entradas da constante: a entrada nova só precisa existir na constante e no comentário dela.

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `shrink:` comentário do passo 1 "no tom dos vizinhos" (15 linhas) → teto de 6 | sim | `01`, passo 1 |
| 2 | `shrink:` D3 tratava "view sem original" como caso à parte → cai na mesma expressão, zero ramo | sim | `01`, D3 |
| — | `02`: Lean already (nenhuma ADR, Superfície não exigida) | — | — |

`/ponytail:ponytail-review` rodado em linha pela skill do projeto (`.claude/skills/ponytail-review`), 2026-10-06: `net: -10 lines possible.`

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| — | 0 | Sem despacho — captura verbatim do requisito (exceção declarada) | sessão | — | `00` gerado do corpo bruto do issue com marcadores nos caracteres de controle | — | — |
| — | 3 | Sem despacho — auditoria de `resources/views/vendor` por script (tarefa de 1–2 passos) | sessão | — | tabela de 12 pastas: 5 autorais, 7 cruas | — | 3 pastas conferidas por `git log` do arquivo |
| 1 | 7 | `general-purpose` — seguir `feature-test-design`, derivar o `04` | opus (explícito) | `01` inteiro (só paths, stack e "Sem superfície de UI" colados no prompt), `02`, `03`, conversa, código da correção | `04` gravado: 11 CT, 7 regras, 23 mutantes, 4 costuras (unit de regra), 1 pergunta de desenho (Q7), `## Sem CT-B` | 139,3 k tokens · 281 s | `git status --porcelain`: só `?? …/04-casos-de-teste.md`; `grep -o "\[CT-[0-9][0-9]\]" \| sort -u \| wc -l` = 11 = cabeçalho; `rastreabilidade.sh` e `citacoes.sh` exit 0; amostrados CT-02 (7 partições isoladas), CT-09 (procedural com junit — aceito) e CT-11 (arquivo inteiro, regra do CHANGELOG) |
| 2 | 7 | `fw-adversario-ct` — provar que o `04` deixa passar defeito | opus | `01`, `02`, `03`, conversa, código | 21 achados: 6 bloqueantes (pasta amarrada ao pacote pelo nome; iteração a partir do pacote; listagem não recursiva; M7/M5/M6 não morriam), 15 cosméticos; 19 aplicados no `04` (14 CT, 33 mutantes, 1 sem matador), 2 rejeitados com motivo (ADV-09 caixa no Windows, ADV-10 leitor antigo) — tabela em `04` → `## Revisão adversarial` | 55,6 k tokens · 210 s | `git status --porcelain` igual antes/depois (só leu); 3 bloqueantes reproduzidos de cabeça contra o script do step 3 (ele procurava o par por caminho relativo em todos os pacotes — ADV-01 não o atingia, mas atingiria uma implementação ingênua); `git ls-files resources/views/vendor` sem colisão de caixa (ADV-09) |

## Blockers
- nenhum

## Desvios do Plano
- nenhum ainda

## Notas de Implementação
- nenhuma ainda

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-06
- `template-01-plano.md` — step 4 — 2026-10-06
- `template-02-adr.md` — step 4 — 2026-10-06
- `template-03-progresso.md` — step 4 — 2026-10-06
- `entrevista-tres-raias.md`, `pesquisa-step-3.md`, `citacoes-de-codigo.md`, `roteamento-e-despacho.md`, `delegacao-casos-de-teste.md`, `padrao-de-log.md`, `estrutura-criada.md` — lidas nesta mesma sessão para a feature anterior (`deploy-multiambiente-docker`, 2026-10-05); não reabertas: o conteúdo está em contexto

## Retrospectiva
- *(ao final)*
