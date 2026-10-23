# Progresso — Roteiro de atualização por prompt para agentes

**Estado**: implementada — reconciliada no step 10; quality gate (step 11) pendente

**Base do PR**: `main`

## 1. Contrato e primeiro roteiro
- [x] `wikis/atualizacoes/README.md` (contrato) — `wikis/atualizacoes/README.md:# Roteiros de atualização — o contrato:1`; `'[CT-04]'`, 2026-10-23
- [x] `wikis/atualizacoes/v0.52.0.md` (primeiro roteiro, origem v0.51.2) — `wikis/atualizacoes/v0.52.0.md:## Identificação:7`; `'[CT-01]'` com todos os detectores sobre a árvore real; auditado contra o `kit:update` da v0.51.2 e da v0.52.0 (C16, C19), 2026-10-23

## 2. Cópia local em `wikis/agentes-e-skills.md`
- [x] Seção "Atualizar o projeto pelo agente" com a cópia entre os marcadores — `wikis/agentes-e-skills.md:## Atualizar o projeto pelo agente:155`; cópia idêntica conferida por `'[CT-13]'`, 2026-10-23

## 3. Guarda no CI
- [x] `tests/Kit/RoteiroDeAtualizacaoTest.php` — 29 CT, 212 casos: `{"result":"passed","tests":212,"passed":212,"assertions":339}`; `ids-ct.sh` silencioso, 2026-10-23

## 4. Workflow de release
- [x] Passo no `.github/workflows/release.yml` — `.github/workflows/release.yml:roteiro:34`; `'[CT-15]'` e `'[CT-29]'` executam o `run` com bash (ausente, fora de ordem, vazia, título em cerca, marcador ≠ tag), 2026-10-23

## 5. Checklist de release
- [x] Etapa pré-release, cenário com agente, cadência de outros agentes, política de reprovação — `wikis/checklist-de-release.md:## Etapa pré-release obrigatória: o roteiro de atualização:327`, `:## Cenário pós-release com agente:355`; `'[CT-16]'`, `'[CT-17]'`, `'[CT-18]'`, `'[CT-24]'`; `ChecklistDeReleaseTest` verde (quatro cenários intactos), 2026-10-23

## 6. Documentação de usuário e CHANGELOG
- [x] `docs/{pt,en}/comecar/atualizando-o-projeto.md` e `CHANGELOG.md` `[Unreleased]` — `docs/pt/comecar/atualizando-o-projeto.md:Atualizar pedindo a um agente:223`, `docs/en/comecar/atualizando-o-projeto.md:Updating by asking an agent:224`, `CHANGELOG.md:## [Unreleased]:6`, `CHANGELOG.md:### Defaults mudados:62`; `'[CT-19]'`, `'[CT-25]'`, 2026-10-23

## Testes
- `04-casos-de-teste.md`: 29 CT em 19 regras, 122 mutantes previstos, 13 sem matador automatizado (só os cenários de processo CP-01…CP-04, manuais, os matam); sem `05` (nenhuma superfície de browser)
- Revisão adversarial: 2 rodadas (teto), 37 achados (rodada 1: 18, 5 altos; rodada 2: 19, 7 altos), todos com destino em `## Fechamento da Revisão Adversarial` do `04`; o que o fechamento da rodada 2 criou (CT-25…CT-29, M95…M122) ficou sem revisão independente — débito declarado
- Respostas do solicitante: Adendo 3 (Q12–Q14, Q16–Q18) e Adendo 4 (Q19–Q21) no `00`; Q15 e as recomendações de desenho como D7–D21 no `01`

## Tickets
<!-- Step 8. -->

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff — C22, cortes de baixo risco aplicados e recusas justificadas na linha C22 de `## Despachos`, 2026-10-23
- [x] `vendor/bin/pint --dirty --format agent` — `vendor/bin/pint --test tests/Kit/RoteiroDeAtualizacaoTest.php`: `{"result":"passed"}` (único PHP do diff), 2026-10-23
- [x] Testes da feature verdes — `tests/Kit/RoteiroDeAtualizacaoTest.php`: 212/212, 2026-10-23
- [x] Suíte `Kit` sem regressão — `php artisan test --testsuite=Kit --parallel --processes=4`: `{"result":"passed","tests":4883,"passed":4880,"skipped":3}`, 2026-10-23
- [x] `/code-review high main...HEAD` + passe de eixos (step 9) — C18 e C19, 21 achados, tabela em `## Revisão do Diff (step 9)`; correção no commit 2f6cbd6a, 2026-10-23
- [ ] `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh` silenciosos
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| E-01 | eixos | detector de conflito aprova "Sem resposta" que sobrescreve o arquivo do dono | guarda corrigida (negação, aplica/sobrescreve) | `'[CT-26]'` | — |
| E-02 | eixos | comando destrutivo com opção curta, `kit:tenancy` e `kit:install` passa | guarda + contrato (lista com `kit:tenancy`, `kit:install`) | `'[CT-23]'` | — |
| E-03 | eixos | `## ` dentro de bloco cercado vira seção e isenta o comando | guarda (seções fora de cerca) | `'[CT-03]'`, `'[CT-23]'` | — |
| E-04 | eixos | instruções da RQ-32 negadas aprovam | guarda (negação antes do verbo) | `'[CT-22]'` | — |
| E-05 | eixos | caso duplicado no CT-01 | o caso passa a testar a marca de revisão (D19) | `'[CT-01]'` | — |
| E-06 | eixos | PowerShell 5.1 apaga `$env:DB_URL=''` | roteiro: `DB_URL=sqlite:///:memory:` e `Remove-Item` dos três | CP-01 | — |
| E-07 | eixos | bloco executável em `## Cuidados` isento | guarda: em Cuidados só frase de proibição; bloco cercado reprova | `'[CT-23]'` | — |
| CR-01 | /code-review | segundo `## Cuidados` sobrescreve a chave e libera comando | guarda: título repetido reprova; varredura por posição | `'[CT-03]'`, `'[CT-23]'` | — |
| CR-02 | /code-review | regex destrutiva contornável (`-n`, `sail art`) | = E-02 | `'[CT-23]'` | — |
| CR-03 | /code-review | Cuidados aceita comando como instrução | = E-07 | `'[CT-23]'` | — |
| CR-04 | /code-review | conflito aprova default negado | = E-01 | `'[CT-26]'` | — |
| CR-05 | /code-review | conflito lê a decisão errada quando a ordem muda | guarda: decisão de conflito pelo título `###` | `'[CT-26]'` | — |
| CR-06 | /code-review | `--from`/`--tag` do `kit:update` não conferidos | guarda + fixture com `--tag` | `'[CT-12]'` | — |
| CR-07 | /code-review | ordem das nove seções não conferida | guarda: ordem comparada com o contrato | `'[CT-03]'` | — |
| CR-08 | /code-review | tutorial depende também de `phpunit.xml` e `ConfiguracoesDoKit.php` | roteiro: sete arquivos, merge mínimo | CP-01 | — |
| CR-09 | /code-review | `migrate --force` aplica migration Pending do dono | roteiro: pré-voo 3 para e pergunta | CP-03 | — |
| CR-10 | /code-review | a v0.52.0 publicada não tem a cópia local | checklist (pedido sem link a partir da v0.54.0), docs pt/en, contrato | P-01, CP-04 | — |
| CR-11 | /code-review | a saída do passo 1 não termina na linha citada | roteiro: "contém a linha" | CP-01 | — |
| CR-12 | /code-review | `DB_URL` fica vazio na sessão PowerShell | = E-06 | CP-01 | — |
| CR-13 | /code-review | workflow aceita título em cerca, fora de ordem ou sem corpo | workflow (awk) + linhas novas no CT-15 | `'[CT-15]'` | — |
| CR-14 | /code-review | pontuação final no item de Defaults reprova o certo | guarda: `rtrim` | `'[CT-25]'` | — |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `.ai/rules/kit.md` | `tests/Kit/**` | n.a. | a guarda não usa `Livewire::test` |
| `.ai/rules/testes.md` | `tests/**` | aplicada, com uma exceção justificada | helpers locais a um arquivo só (ficam no próprio teste); a seção da versão do CHANGELOG é lida pelo marcador, não pelo topo; **exceção**: "asserção de ausência sobre arquivo documentado precisa filtrar comentário" cede no detector de comando destrutivo (RQ-34, D18: o agente lê o raw, comentário incluso), registrada em `## Setup Global` §Precedência do `04` |
| `.ai/rules/specs.md` | `wikis/specs/**` | aplicada | citações por símbolo (`citacoes.sh` silencioso) e de teste pelo ID do CT entre aspas |

## Quality Gate

<!-- Preenchido no step 11. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-23 — gsferro (solicitante e mantenedor), respostas no chat — 3 rodadas; perguntas: 0 fato, 2 desenho (Q9, Q10), 8 requisito (Q1–Q8), 1 roteiro manual (Q11)

Roteiro manual: não — 2026-10-23 — resposta a Q11 — o cenário 5 pós-release com agente (RQ-11) já é o teste manual de valor

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| um quinto cenário entra na matriz do checklist | `tests/Kit/ChecklistDeReleaseTest.php` exige exatamente quatro linhas e quatro `create-project` | cenário com agente em seção própria (`01`, passo 5) |
| o cenário reusa `velho-sem-tenant`/`velho-com-tenant` | os cenários 3/4 já os atualizaram para a versão nova | dois projetos novos da versão anterior (`agente-*`) |
| "mesmo estado dos cenários 3/4" mede canais e customizações | o checklist não define canário | dois canários preparados antes: canal próprio no `logging.php` e linha própria no `.env` |
| o teste novo não mexe no teto de pulados | `tests/Kit` viaja e o teste pula fora da árvore | teto 942 + N, medido e justificado na próxima validação |
| o workflow de release garante a obrigação | roda depois do push da tag | redação: detecta depois; quem age antes é a guarda do CI |
| a cópia local é texto livre | `tests/Kit/CitacoesDeCodigoTest.php` confere `wikis/*.md` de topo | roteiro sem citação `arquivo:símbolo:linha` |
| a seção nova nas docs é livre | paridade de seções pt/en (`tests/Kit/PageHeaderTest.php`) | parágrafo dentro de seção existente, nas duas línguas |
| contagens do README só mudam pelo teste | specs 88 no README, 89 na árvore com esta wiki | reconciliar 88 → 89 |

### Varredura da classe irmã (step 5)

`grep -rnF` pela irmã, conferido no parecer C8.

| Classe/arquivo novo | Irmã escolhida | Ocorrências da irmã | A nova entra? |
|---|---|---|---|
| `tests/Kit/RoteiroDeAtualizacaoTest.php` | `tests/Kit/ChecklistDeReleaseTest.php` | só o próprio arquivo, `tests/Pest.php` e `tests/Kit/RedeDeDocumentacaoTest.php` (suíte de documentação por tokens) — sem lista de registro | descoberta por diretório; a `RedeDeDocumentacaoTest` seleciona a suíte nova se ela citar README/docs: conferir no step 10 |
| `wikis/atualizacoes/` | `wikis/agentes-e-skills.md` | `KitUpdate::CAMINHOS_DO_KIT`, `KitUpdateTest` (glob `wikis/*.md` de topo), `SiteDeDocumentacaoTest` [CT-48] (contagem de topo), `CitacoesDeCodigoTest` (topo) | não: pasta aninhada não entra nos inventários de topo nem no `kit:update` (D5, RQ-16) |

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `shrink`: seção das docs repetia cópia local e checklist → um parágrafo | sim | `01`, passo 6 |
| 2 | `shrink`: detector do link raw à parte → linha obrigatória do `## Identificação` | sim | `01`, passo 3 |

`/ponytail-review` (o comando sem o namespace é o que a instalação expõe) sobre `01` e `02`, 2026-10-23: `net: -10 lines`. Mudança em 1 arquivo: sem re-execução.

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| C13 | 7 | derivação (retomada) — fechamento da rodada 2: 19 achados, Adendo 4, D15–D19 | herdado (opus) | conversa, código | `04` com 29 CT, 122 M, 13 sem matador; 2 perguntas de desenho (D20, D21) | 346.348 tokens · 525 s | contagens por grep conferidas (29 CT); árvore: só o `04` |
| C12 | 7 | `fw-adversario-ct` — rodada 2 (última) | herdado | `01`, `02`, `03`, código, raciocínio do derivador | 19 achados (7 altos); 11 da rodada 1 mal fechados; Q20, Q21 ao solicitante | 94.970 tokens · 226 s | leu só `00`, `04` e glossário (declarado); nada gravado |
| C11 | 7 | derivação (retomada) — Adendo 3, D7–D14, 18 achados | herdado (opus) | conversa, código | `04` com 24 CT, 94 M; Q19 ao solicitante, 3 de desenho (D15–D17) | 264.514 tokens · 457 s | 24 CT por grep; Q?8 retirada pelo fato `app/Console/Commands/KitUpdate.php:'wikis/agentes-e-skills.md':330` |
| C10 | 7 | `fw-adversario-ct` — rodada 1 | herdado | `01`, `02`, `03`, código, raciocínio do derivador | 18 achados (5 altos); veredito "não mata" Impacto 3; Q16–Q18 ao solicitante | 103.858 tokens · 218 s | leu só `00`, `04`, glossário e o SKILL da `feature-test-design` (declarado); nada gravado |
| C9 | 7 | `general-purpose` seguindo `feature-test-design` — derivação do `04` | opus | conversa, código, plano além de paths/contrato/UI | `04` com 19 CT, 58 M, 5 sem matador; 10 perguntas (Q12–Q15 ao solicitante, 5 de desenho D7–D11, 1 resolvida por fato) | 199.874 tokens · 667 s | 19 CT e 14 regras por grep; árvore: só o `04`; amostra CT-15, CP-01, M48 |
| C22 | 10 | `general-purpose` seguindo `ponytail-review` — excesso de complexidade do diff | sonnet | conversa, wiki | 26 cortes propostos (net -200); aplicados os de baixo risco: guarda 1841 → 1795 linhas (212 casos e 339 asserções iguais), roteiro 518 → 475, workflow 66 → 63; recusados os que reabririam E-01/E-02, o CT-27, o awk, a frase do RQ-13 e o enxugamento de Como conferir/Quando parar | 120.654 tokens · 121 s | testes dos docs e guarda: 373 verdes, 3 pulados |
| C21 | 9 | construtor (retomado) — achados do step 9 no roteiro, contrato, workflow, checklist e docs | opus | conversa | 8 pontos; workflow testado em 6 situações | 358.780 tokens · 532 s | testes dos docs verdes; guarda 212/212 contra os arquivos dele |
| C20 | 9 | `fw-executor-ct` (retomado) — 12 achados do step 9 na guarda | herdado | conversa, plano | 212 casos verdes; cada achado com linha de dataset | 300.022 tokens · 420 s | não provou por execução que as linhas novas falhariam nos detectores antigos — limite declarado |
| C19 | 9 | `general-purpose` com `/code-review` high | opus | conversa, wiki | 14 achados (10 confirmados), simulação do roteiro num projeto v0.51.2 descartável | 222.740 tokens · 838 s | árvore intacta (`git status` limpo, sem tags `kit-*`) |
| C18 | 9 | `fw-revisor-diff` — passe de eixos | herdado | PRD, conversa | 7 achados (2 maiores) | 118.660 tokens · 345 s | `git status` antes = depois (vazio) |
| C17 | impl. | construtor (retomado) — 12 achados da auditoria do Codex no roteiro | opus | conversa | roteiro 408 → ~508 linhas; contrato com 4 regras gerais | 332.394 tokens · 416 s | 344 verdes nos testes dos docs |
| C16 | impl. | `codex exec --yolo` — auditoria só leitura do roteiro contra o `kit:update` (v0.51.2 e v0.52.0) e o diff das tags | não exposto (Codex CLI) | conversa | 13 achados (R-01..R-13); R-01 recusado (a barra simples funciona nos dois shells) | — | árvore intacta (status e md5 antes = depois); R-02, R-03, R-04, R-05 conferidos no código |
| C15 | impl. | `fw-executor-ct` — guarda do passo 3 | herdado | conversa, plano além de paths e D | 29 CT, 183 casos verdes | 239.304 tokens · 804 s | hook negou o `01`; usou as `@premissa` do `04` |
| C14 | impl. | construtor (`general-purpose`) — passos 1, 2, 4, 5, 6 | opus | conversa | roteiro, contrato, cópia, workflow, checklist, docs, CHANGELOG | 273.775 tokens · 840 s | `git status` só nos arquivos do escopo; opções do `kit:update` conferidas no código |
| C8 | 5 | `codex exec --yolo` — revisão profunda do `01` contra o código (só leitura) | não exposto (Codex CLI 0.163.0) | conversa | `scratchpad/revisao-profunda.md` (8 premissas a corrigir, 14 listas paralelas) | — | árvore intacta; amostra: `ChecklistDeReleaseTest` (4 linhas), `PageHeaderTest` [CT-36] (paridade), 89 `00-requisito.md` conferidos |
| C7 | 3 | `codex exec --yolo` — parceiro de desenho (só leitura): fatos da atualização, o que viaja, cuidados, 3 desenhos, perguntas | não exposto (Codex CLI 0.163.0) | conversa | `scratchpad/desenho-roteiro.md` (3 desenhos, recomendação A, 16 perguntas) | — | árvore intacta; amostra: `.gitattributes:21,40`, `app/Console/Commands/KitUpdate.php:preVoo:409` (pré-voo `FAILURE`) e `app/Console/Commands/KitUpdate.php:marcarVersao:1140` conferidos |



## Blockers

## Desvios do Plano

- O roteiro v0.52.0 tem cinco decisões do dono, não só as dos defaults mudados: o `PapeisSeeder` entrou como pergunta porque redefine as permissões dos papéis (`syncPermissions`) e apagaria as do dono; sem resposta, não roda.
- O passo 1 usa `--keep-remote` (as tags `kit-*` servem ao merge mínimo do passo 2) e um passo final remove o remote e as tags.
- Ligar o tutorial depende de sete arquivos (`config/kit.php`, `phpunit.xml`, `app/Settings/ConfiguracoesDoKit.php` e os quatro providers), não só do `.env`: a migration de settings lê a config.
- A suíte do projeto roda com o banco forçado no próprio comando (`DB_URL=sqlite:///:memory:`), não pela inspeção do `phpunit.xml`, que não usa `force`.
- O contrato ganhou regras gerais vindas da auditoria: decisões dependentes, revisar/desfazer pelo índice, banco forçado, nomes reservados do comando, `kit:tenancy`/`kit:install` como destrutivos.
- A cópia local só existe a partir de projetos na v0.53.0: a v0.52.0 publicada não a tem (P-01).

## Notas de Implementação

- `rastreabilidade.sh` acusa `RQ-16 sem CT` de propósito: "nada muda na aplicação nem no `kit:update`" é afirmação sobre o diff desta entrega, não comportamento que dura (`04`, `## Cogitado e cortado`); quem confere é o quality gate, pelo `git diff --stat` contra a `main`. Achado esperado do script até o step 11, 2026-10-23.
- Do parecer C7: o pré-voo do `kit:update` atual sai com `FAILURE` (`app/Console/Commands/KitUpdate.php:preVoo:409`), mas a primeira rodada usa a classe antiga, que pode sair com 0; e a versão é marcada com qualquer arquivo aplicado (`app/Console/Commands/KitUpdate.php:marcarVersao:1140`). O roteiro confere versão **e** arquivos.

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-23
- `template-01-plano.md`, `template-03-progresso.md` (índices) — step 4 — 2026-10-23
- `entrevista-tres-raias.md`, `padrao-de-log.md`, `estrutura-criada.md`, `template-02-adr.md`, `pesquisa-step-3.md` — pulados: sem código de aplicação, sem log, sem Livewire; formato da entrevista já conhecido da feature anterior

## Retrospectiva
