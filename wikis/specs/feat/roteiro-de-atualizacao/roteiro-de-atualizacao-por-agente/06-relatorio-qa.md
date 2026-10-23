# Relatório de QA — roteiro-de-atualizacao-por-agente: Roteiro de atualização por prompt para agentes

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão
> Natureza da wiki: nova · Toca infra compartilhada: sim → processo de release (`wikis/checklist-de-release.md`, `.github/workflows/release.yml`) e `wikis/agentes-e-skills.md`, que é entregue a quem instala · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 11 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 4 · Cosmético: 1
- Não verificadas: K (só o passo estático; K2 fica fora do perfil padrão e não há classe de `app/` para mutar)
- `RQ` abertas: nenhuma
- Ambiente: sem app servido (a feature não tem UI nem runtime) · Pest 5.0.5 · MCP: não usado (não se aplica)

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 1 (QA-07, novo) · Cosmético: 0
- Não verificadas: K (só o passo estático; K2 fora do perfil padrão, sem classe de `app/`)
- Ciclo 1 fechado: QA-01 a QA-06 conferidos por comando; a suspeita do passo 8 fechada (o comando novo, executado em sqlite `:memory:`, gera 0 policies e cria `View:PrimeirosPassos`)
- Regressão: suíte `Kit` com 4 processos, 4883 testes, 4880 passaram, 3 pulados, 2 risky; `git status` vazio depois

### QA-07 — O passo 9 do roteiro roda o `ShieldPermissionsSeeder` que o passo 8 proíbe · Minor · destino 2

- **Dimensão**: A (RQ-12) e L (o roteiro se contradiz)
- **Observado**: a suíte do passo 9 semeia `ShieldPermissionsSeeder` (`tests/Pest.php` e 133 arquivos de `tests/Kit`/`tests/Tenancy`), que roda `shield:generate --all --ignore-existing-policies` e grava no disco do projeto a policy de todo Resource do dono sem policy; nenhum passo confere `app/Policies` depois da suíte.
- **Atenuante**: os três painéis usam `->strictAuthorization()` desde a v0.51.2; o caso afetado é Resource que sobrescreve os `can*` sem policy.
- **Ação exigida**: conferir `git status --porcelain app/Policies` depois da suíte, no roteiro e na cópia local.

## Achados

### QA-01 — O plano e a ADR descrevem a entrega de antes dos Adendos 3 e 4 e dos steps 9 e 10 · Major · destino 1

- **Dimensão**: L3
- **Relacionado a**: RQ-12, RQ-27, RQ-28, RQ-29, RQ-30, RQ-31, RQ-33, RQ-34, RQ-35; passos 3, 4 e 5 do PRD; ADR do `02`
- **Esperado**: o PRD e a ADR afirmam o que o código entregue faz, ou marcam a mudança com *(alterado em …)*.
- **Observado**: o passo 5 do `01` falava em dois canários (o checklist exige seis); o passo 4 só conferia a existência do roteiro (o `release.yml` confere também as nove seções, o corpo, a ordem e os títulos fora de cerca, RQ-27); o passo 3 listava quatro afirmações (a guarda tem 15 detectores); o cabeçalho do `01` citava "Adendos 1 e 2" (o `00` tem quatro); o `02` citava "D1 a D6" (o `01` tem D1 a D21).
- **Repro**: `grep -rn "D1 a D6\|Adendos 1 e 2"` na wiki; `sed -n 191,206p` do `01` contra o checklist e o `release.yml`.
- **Destino**: 1 (especificação)
- **Ação exigida**: alinhar os passos 3, 4 e 5 e o cabeçalho do `01`, e a frase do `02`, ao entregue, com a marca *(alterado em …)*.

### QA-02 — O passo 2 do roteiro remete a um `--quiet` que o pré-voo não usa · Minor · destino 2

- **Dimensão**: A (RQ-06)
- **Observado**: o passo 2 dizia "veja as mudanças com o `git diff` do passo 6 do pré-voo (sem o `--quiet`)"; nenhum comando do roteiro usa `--quiet`, o pré-voo usa `--name-only`.
- **Ação exigida**: trocar por "sem o `--name-only`" no roteiro e na cópia local.

### QA-03 — O roteiro v0.52.0, o único publicado, não tem o cenário pós-release agendado · Minor · destino 1

- **Dimensão**: A
- **Relacionado a**: RQ-11, RQ-12, P-01, CP-01…CP-04
- **Observado**: nenhum passo do `01` nem linha do `03` diz quando o CP-01 roda sobre o roteiro v0.52.0. A simulação do step 9 (C19) foi feita por um revisor com o contexto do diff, não por um agente em sessão nova só com o link.
- **Ação exigida**: registrar que o CP-01 a CP-03 da v0.52.0 rodam depois do merge, antes de divulgar o link, ou declarar a v0.52.0 fora da RQ-11 com uma `P-nn`.

### QA-04 — O CT-15 roda o passo do workflow com `bash` sem `-e`, e o GitHub roda com `bash -e` · Minor · destino 3

- **Dimensão**: K
- **Observado**: o arnês do CT-15/CT-29 chamava `bash passo.sh`; o runner Ubuntu, sem `shell:`, usa `bash -e {0}`. Sem divergência observada hoje, mas um comando novo sem guarda daria verde no teste e vermelho na tag.
- **Ação exigida**: rodar o arnês com `bash -e`.

### QA-05 — "Roteiro de atualização" não está no glossário, que usa "roteiro" em outro sentido · Minor · destino 1

- **Dimensão**: L7
- **Observado**: em `wikis/glossario.md`, na entrada Jornada, "roteiro" significa o tour.
- **Ação exigida**: entrada "Roteiro de atualização", com "Não confundir com" o tour e o roteiro manual (`08`).

### QA-06 — Cerca solta no `04` tira o CT-02 do bloco Gherkin · Cosmético · destino 1

- **Dimensão**: L1
- **Observado**: 41 cercas no `04`; a que fechava o bloco antes do CT-02 estava solta.
- **Ação exigida**: remover a cerca.

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-16 | nada muda na aplicação nem no `kit:update` | 1–6 | — (por desenho, `04` `## Cogitado e cortado`) | — | diff sem `app/`, `config/` nem `KitUpdate.php` (`git diff --name-only main...HEAD`) | ✅ | OK. A linha do `rastreabilidade.sh` foi rejeitada, destino 5: o gate conferiu pelo diff |
| RQ-11 | cenário pós-release em toda publicação | 5 | CT-17, CP-01…CP-04 | — | `wikis/checklist-de-release.md` | processo escrito; nunca executado para a v0.52.0 | ⚠️ QA-03 |

As demais RQ e a P-01 estão sem lacuna: `rastreabilidade.sh` só acusou a RQ-16.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-02 e QA-03 |
| B | Fronteiras e dados | ✅ | estática; datasets cobrem vazio, ausente, CRLF, dígito duplo, hotfix, cerca e título repetido; NFD é lacuna declarada |
| C | Matriz de permissão | n/a | nenhuma rota, policy ou gate |
| D | Observabilidade | n/a | sem código de runtime |
| E | Performance | ✅ | guarda: 212 casos em 34,3 s |
| F | UX de erro | ✅ | `::error::` e falhas da guarda nomeiam a seção ou o arquivo |
| G | Tema e cor | n/a | sem UI |
| H | Acessibilidade | n/a | sem UI |
| I | Segurança da superfície nova | ✅ | step 9 cobriu os eixos; a tag vem de `GITHUB_REF_NAME` por env; a impressão do pré-voo grava md5, sem PII |
| J | Regressão adjacente | ✅ | suíte `Kit`: 4883 testes, 4880 passaram, 3 pulados, 2 risky |
| K | Adequação da suíte | ⏭️ | K1 exit 0; K2 fora do perfil; QA-04; CT-25…CT-29 sem revisão adversarial independente |
| L | Consistência documental | ❌ | QA-01, QA-05, QA-06; `ids-ct.sh`, `citacoes.sh`, `conformidade-rules.sh`, `checkbox-sem-evidencia.sh` exit 0; números do `03` reproduzidos |

## Débitos Aceitos

- Nenhum: QA-01 a QA-06 corrigidos depois do ciclo 1 e QA-07 depois do ciclo 2 (ver `03-progresso.md`, `## Quality Gate`).

## Suspeitas Não Confirmadas

- O passo 8 do roteiro roda o `ShieldPermissionsSeeder` (`shield:generate --all --ignore-existing-policies`) sempre que o tutorial é ligado, sem perguntar. Num Resource do dono sem policy, isso pode gerar uma policy nova e restringir o acesso dos papéis dele. Não reproduzido.

## Não Verificado

- K2 (mutação): fora do perfil padrão, e não há classe de `app/` no diff.
- CT-25…CT-29 e M95…M122: sem revisão adversarial independente.
- CP-01…CP-04: exigem o roteiro na `main`, um agente em sessão nova e `create-project` da tag v0.51.2 (QA-03).
- Leitura do link raw: o roteiro ainda não está na `main`.
