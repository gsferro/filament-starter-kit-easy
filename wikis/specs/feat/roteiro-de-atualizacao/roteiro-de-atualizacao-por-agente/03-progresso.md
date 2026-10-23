# Progresso — Roteiro de atualização por prompt para agentes

**Estado**: em planejamento

**Base do PR**: `main`

## 1. Contrato e primeiro roteiro
- [ ] `wikis/atualizacoes/README.md` (contrato)
- [ ] `wikis/atualizacoes/v0.52.0.md` (primeiro roteiro, origem v0.51.2)

## 2. Cópia local em `wikis/agentes-e-skills.md`
- [ ] Seção "Atualizar o projeto pelo agente" com a cópia entre os marcadores

## 3. Guarda no CI
- [ ] `tests/Kit/RoteiroDeAtualizacaoTest.php`

## 4. Workflow de release
- [ ] Passo no `.github/workflows/release.yml`

## 5. Checklist de release
- [ ] Etapa pré-release, cenário 5, cadência de outros agentes, política de reprovação

## 6. Documentação de usuário e CHANGELOG
- [ ] `docs/{pt,en}/comecar/atualizando-o-projeto.md` e `CHANGELOG.md` `[Unreleased]`

## Testes
- `04-casos-de-teste.md`: 29 CT em 19 regras, 122 mutantes previstos, 13 sem matador automatizado (só os cenários de processo CP-01…CP-04, manuais, os matam); sem `05` (nenhuma superfície de browser)
- Revisão adversarial: 2 rodadas (teto), 37 achados (rodada 1: 18, 5 altos; rodada 2: 19, 7 altos), todos com destino em `## Fechamento da Revisão Adversarial` do `04`; o que o fechamento da rodada 2 criou (CT-25…CT-29, M95…M122) ficou sem revisão independente — débito declarado
- Respostas do solicitante: Adendo 3 (Q12–Q14, Q16–Q18) e Adendo 4 (Q19–Q21) no `00`; Q15 e as recomendações de desenho como D7–D21 no `01`

## Tickets
<!-- Step 8. -->

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] Testes da feature verdes
- [ ] Suíte `Kit` sem regressão
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)
- [ ] `rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh` silenciosos
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
| C8 | 5 | `codex exec --yolo` — revisão profunda do `01` contra o código (só leitura) | não exposto (Codex CLI 0.163.0) | conversa | `scratchpad/revisao-profunda.md` (8 premissas a corrigir, 14 listas paralelas) | — | árvore intacta; amostra: `ChecklistDeReleaseTest` (4 linhas), `PageHeaderTest` [CT-36] (paridade), 89 `00-requisito.md` conferidos |
| C7 | 3 | `codex exec --yolo` — parceiro de desenho (só leitura): fatos da atualização, o que viaja, cuidados, 3 desenhos, perguntas | não exposto (Codex CLI 0.163.0) | conversa | `scratchpad/desenho-roteiro.md` (3 desenhos, recomendação A, 16 perguntas) | — | árvore intacta; amostra: `.gitattributes:21,40`, `app/Console/Commands/KitUpdate.php:preVoo:409` (pré-voo `FAILURE`) e `app/Console/Commands/KitUpdate.php:marcarVersao:1140` conferidos |



## Blockers

## Desvios do Plano

## Notas de Implementação

- `rastreabilidade.sh` acusa `RQ-16 sem CT` de propósito: "nada muda na aplicação nem no `kit:update`" é afirmação sobre o diff desta entrega, não comportamento que dura (`04`, `## Cogitado e cortado`); quem confere é o quality gate, pelo `git diff --stat` contra a `main`. Achado esperado do script até o step 11, 2026-10-23.
- Do parecer C7: o pré-voo do `kit:update` atual sai com `FAILURE` (`app/Console/Commands/KitUpdate.php:preVoo:409`), mas a primeira rodada usa a classe antiga, que pode sair com 0; e a versão é marcada com qualquer arquivo aplicado (`app/Console/Commands/KitUpdate.php:marcarVersao:1140`). O roteiro confere versão **e** arquivos.

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-23
- `template-01-plano.md`, `template-03-progresso.md` (índices) — step 4 — 2026-10-23
- `entrevista-tres-raias.md`, `padrao-de-log.md`, `estrutura-criada.md`, `template-02-adr.md`, `pesquisa-step-3.md` — pulados: sem código de aplicação, sem log, sem Livewire; formato da entrevista já conhecido da feature anterior

## Retrospectiva
