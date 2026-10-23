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
<!-- Preenchida no step 7. -->

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

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| C7 | 3 | `codex exec --yolo` — parceiro de desenho (só leitura): fatos da atualização, o que viaja, cuidados, 3 desenhos, perguntas | não exposto (Codex CLI 0.163.0) | conversa | `scratchpad/desenho-roteiro.md` (3 desenhos, recomendação A, 16 perguntas) | — | árvore intacta; amostra: `.gitattributes:21,40`, `app/Console/Commands/KitUpdate.php:preVoo:409` (pré-voo `FAILURE`) e `app/Console/Commands/KitUpdate.php:marcarVersao:1140` conferidos |

## Blockers

## Desvios do Plano

## Notas de Implementação

- Do parecer C7: o pré-voo do `kit:update` atual sai com `FAILURE` (`app/Console/Commands/KitUpdate.php:preVoo:409`), mas a primeira rodada usa a classe antiga, que pode sair com 0; e a versão é marcada com qualquer arquivo aplicado (`app/Console/Commands/KitUpdate.php:marcarVersao:1140`). O roteiro confere versão **e** arquivos.

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-23
- `template-01-plano.md`, `template-03-progresso.md` (índices) — step 4 — 2026-10-23
- `entrevista-tres-raias.md`, `padrao-de-log.md`, `estrutura-criada.md`, `template-02-adr.md`, `pesquisa-step-3.md` — pulados: sem código de aplicação, sem log, sem Livewire; formato da entrevista já conhecido da feature anterior

## Retrospectiva
