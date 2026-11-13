# Progresso — fix: cadastro por convite cai no painel do papel

**Estado**: concluída — 2026-11-13

`{base}`: `main` · branch `fix/convite-painel-do-papel`

## Auditoria Pré-Implementação

- Entendimento confirmado: 2026-11-13 — autorização existente no pedido ("veja a issue aberta e corrija") — 1 rodada, 0 perguntas ao solicitante; Q1 (devolvida pela derivação) respondida pela sessão como P-03 com base no CT-75 da wiki ancestral
- Roteiro manual: não — 2026-11-13 — instrução explícita do desenvolvedor — pedido de fix com tag; o roteiro que o pedido exige é o de atualização (`wikis/atualizacoes/v0.52.1.md`)
- Revisão profunda: a issue cita `Convite::url()` em `app/Notifications/ConviteDeAcesso.php`; a premissa usada aqui é só "o link é sempre o cadastro do `app`", fora de escopo de mudança
- Classe irmã: nenhuma classe nova (métodos novos em `DestinoAposLogin`). Irmã da resposta, `RespostaDeLogin`: assimetria pré-existente registrada como RD-03 (débito, ver Q2)
- Ponytail: passe manual (plugin não invocado) — 3 passos de código, sem abstração nova

## 1. `DestinoAposLogin::painelSemEscolha()`

- [x] Método público, reusa `paineisDe()` — commit 2dc2e79f, CT-08/CT-09 verdes, 2026-11-13

## 2. Ramo desligado de `RespostaDeCadastro`

- [x] Pretendida acessível ou corrente acessível → `parent::toResponse()` — CT-04/CT-05/CT-12 verdes, 2026-11-13
- [x] Fallback ao painel acessível com log `info` — CT-01/CT-02/CT-03 verdes, 2026-11-13
- [x] Zero painéis: log `warning`, logout, invalidate, regenerateToken, notificação, login do corrente — CT-10 verde (sessão, token e notificação afirmados), 2026-11-13

## 3. Organização da pretendida no cadastro

- [x] `painelDe(..., ?User)` + `organizacaoDaUrlAcessivel()`, só pelo cadastro — CT-04/CT-11 tenancy, CT-15, CT-16 verdes, 2026-11-13

## 4. Docs

- [x] `docs/{pt,en}/autenticacao/login-unificado.md` e `docs/{pt,en}/recursos/configuracao-global-filament.md` — CT-13 verde, 2026-11-13

## 5. Testes

- [x] `tests/Kit/CadastroNoPainelDoPapelTest.php` e `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php` — 51/51 com `DestinoAposCadastroTest` e `HelpersDeTesteTest`, 2026-11-13

## Release v0.52.1

- [x] Bumps do dependabot e phpstan — `phpstan analyse` 0 erros, 2026-11-13
- [x] CHANGELOG, `config/kit.php`, roteiro, cópia em `wikis/agentes-e-skills.md` — `RoteiroDeAtualizacaoTest` 212/212, 2026-11-13
- [x] Pré-validação cenário 1 (`git archive` de d90f380c) — 5583 testes, 0 falhas, 1156 pulados, 2026-11-13
- [x] Pré-validação cenário 2 (idem + `kit:tenancy --force`) — 5583 testes, 1156 pulados, 1 erro de timeout verde isolado, `kit.version` 0.52.1, tenancy SIM, 2026-11-13

## Testes

| Arquivo | CT |
|---|---|
| `tests/Kit/CadastroNoPainelDoPapelTest.php` | CT-01, CT-02, CT-03, CT-04, CT-05, CT-08, CT-09, CT-10, CT-11, CT-12, CT-13, CT-14 |
| `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php` | CT-01, CT-02, CT-04, CT-06, CT-07, CT-11, CT-15, CT-16 |

## Tickets

Não fatiado — 2026-11-13: 8 RQ, 16 CT, sessão sem compactação, 0 perguntas de requisito.

## Revisão do Diff (step 9)

Passe genérico: Codex (`codex exec -s read-only`, contexto novo, sem `wikis/`) no lugar do `/code-review`. Passe de eixos: `fw-revisor-diff`/opus.

| ID | Origem | Achado | Destino |
|---|---|---|---|
| EX-01 | executor-ct (vermelho b) | pretendida `/app/outra` (organização sem vínculo) aceita — CT-04/CT-11 tenancy | correção em `painelDe()` (P-04); CT já existia com Origem RQ-06 |
| CX-01 | Codex | primeiro segmento lido como organização recusava `/app/email-verification/...` e `/app/login` | P-04 → CT-15 → correção (casar a rota `{tenant}`); CT-15 falhou sem a correção |
| RD-01 | eixos | URL sem rota liberava, contra o docblock | P-04 → CT-16 → correção (falha fechado) |
| RD-02 | eixos | CHANGELOG omitia efeito no login | resolvido pelo estreitamento de P-04 ao cadastro (QA-01) |
| RD-03 | eixos | `RespostaDeLogin` desligada ainda manda conta `infra` ao `/app` (403) | **rejeitado nesta entrega** — pré-existente, login fora de escopo; vira Q2 / issue nova |

Re-revisão: as correções tocaram fronteira de dado; o quality gate (dimensão I) cobriu a segunda rodada.

## Conformidade com Rules

| Rule | Veredito | Evidência |
|---|---|---|
| `.ai/rules/app.md` | aplicada | `RespostaDeCadastro` usa `Paineis::correnteOuPadrao()` e `Paineis::url()` |
| `.ai/rules/commands.md` | n.a. | `KitCobertura` só perdeu `is_string()`; nenhuma chave de `.env` nem caminho do `kit:update` |
| `.ai/rules/config.md` | n.a. | `config/kit.php` só muda `version` |
| `.ai/rules/kit.md` | aplicada | testes de componente montam com query só na montagem (`cadastrarPorConvite`) |
| `.ai/rules/onboarding.md` | n.a. | seeder de onboarding só trocou `->` por `?->`; nenhuma tela mudou |
| `.ai/rules/specs.md` | aplicada | citações de teste por ID de CT; citação de vendor em `LinkDoPainelDaOrganizacaoTest` ressincronizada (CitacoesDeCodigoTest verde) |
| `.ai/rules/support.md` | n.a. | nenhuma escrita de `.env` |
| `.ai/rules/testes.md` | aplicada | helpers compartilhados em `tests/Pest.php` (QA-02); painel de teste pelo `PanelRegistry`; CT-13 pula fora da árvore |

## Verificação Final

- [x] `rastreabilidade.sh` — exit 0, 2026-11-13
- [x] `checkbox-sem-evidencia.sh` — exit 0, 2026-11-13
- [x] `ids-ct.sh {wiki} 'tests/**/CadastroNoPainelDoPapel*.php'` — exit 0, 2026-11-13
- [x] `citacoes.sh` — só acusa o `06` do ciclo 1 (texto verbatim do gate, substituído no ciclo 2), 2026-11-13
- [x] `conformidade-rules.sh` — 8 rules com linha acima, 2026-11-13
- [x] Falsificabilidade: CT-04/CT-11 tenancy vermelhos antes da correção de organização (executor, ciclo 1); CT-15 falha com a leitura por segmento (cópia temporária do método); CT-10 sessão/token/notificação vieram dos mutantes sobreviventes do gate — 2026-11-13
- [x] Regressão Kit + Tenancy no código final — Kit 4911/4914 (3 pulados), Tenancy 669/669 na árvore do kit; cenário 2 (`git archive` + `kit:tenancy`) 5583 testes, 1 erro de timeout do `QualidadeDeCodigoTest` CT-01 sob carga, verde isolado (29/31, 2 pulados), 2026-11-13

## Notas de Implementação

- `getRoutes()->match()` faz `bind()` na instância de rota; não é a rota do request corrente (suspeita não confirmada do gate)
- O framework 13.42 passou a montar o path de view com `\` no Windows: `BotaoLimparCacheTest` compara por `realpath()`

## Retrospectiva

- Funcionou: CT derivado às cegas pelo Codex pegou a organização sem vínculo que o plano não previa
- Faltou: o `01` nasceu fora do formato do template e o estreitamento ao cadastro só veio no gate

## Quality Gate

| Ciclo | Veredito | Data |
|---|---|---|
| 1 | REPROVADO → especificação (QA-01, QA-02 Major) | 2026-11-13 |
| 2 | APROVADO COM DÉBITO (0 Blocker/Major; E e K não verificadas; QA-04, QA-10, QA-12 tratados depois do veredito: M-44 equivalente, `04` e docblocks, só teste/wiki) | 2026-11-13 |

## Candidatos a Rule

apresentados 0 · gravados 0 · recusados 0 · descartados no gate 0 · poda 0 — 2026-11-13, sessão principal; o gate não apontou candidato (o padrão `not->toBe($antes)` sobre valor anulável é linha de checklist da `feature-test-design`, não rule). `requirement-to-rule` não invocada: nenhum candidato.

## Despachos

| # | Step | Rota / modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|
| 1 | 7 | Codex `codex exec -s read-only` / não exposto | só o `00`, glossário e nomes | `04` com 14 CT, Q?1 | — | amostragem CT-10/11/12; árvore inalterada |
| 2 | impl. | `fw-executor-ct` / sonnet | `00`, `04`, nomes | 37 testes, 2 vermelhos (b) | 130 k tokens · 268 s | vermelhos reproduzidos pela sessão |
| 3 | release | Codex read-only / não exposto | contrato, roteiro anterior, CHANGELOG, diff | roteiro v0.52.1 | — | guarda 212/212; parágrafo do login acrescentado e depois estreitado |
| 4 | 9 | Codex read-only / não exposto | diff sem `wikis/` | CX-01 | — | reproduzido (CT-15 falhou sem a correção) |
| 5 | 9 | `fw-revisor-diff` / opus | diff sem `wikis/`, eixos | RD-01..03 | 84 k tokens · 453 s | RD-01 reproduzido pelo CT-16 |
| 6 | 11 | `fw-qa-gate` / opus | path da wiki, URL, diff --stat, base | ciclo 1 REPROVADO | 235 k tokens · 2164 s | `git status` vazio antes/depois |
| 7 | 11 | `fw-executor-ct` / sonnet (SendMessage) | achados QA-02/04, P-05 | 51/51 | 157 k tokens · 301 s | lista de `it()` conferida |
| 8 | 11 | `fw-qa-gate` / opus | path da wiki, URL, diff --stat, base | ciclo 2 APROVADO COM DÉBITO | 196 k tokens · 1618 s | `git status` vazio antes/depois |

Degradações: revisão adversarial do `04` feita pelo próprio Codex na derivação, não por `fw-adversario-ct`; `/code-review` substituído por Codex (pedido do usuário); Ponytail em passe manual.

## Referências Abertas

- `references/template-01-plano.md` (step 10, formato da Cobertura e da Estrutura)
- template do `00`/`03`: wiki irmã `fix/destino-apos-cadastro` usada como molde
