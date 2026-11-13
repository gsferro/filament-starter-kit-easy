# Progresso — fix: cadastro por convite cai no painel do papel

**Estado**: em implementação

`{base}`: `main` · branch `fix/convite-painel-do-papel`

## Auditoria Pré-Implementação

- Entendimento confirmado: 2026-11-13 — autorização existente no pedido ("veja a issue aberta e corrija") — 1 rodada, 0 perguntas ao solicitante; Q1 (devolvida pela derivação) respondida pela sessão como P-03 com base no CT-75 da wiki ancestral
- Roteiro manual: não — 2026-11-13 — instrução explícita do desenvolvedor — pedido de fix com tag; o roteiro que o pedido exige é o de atualização (`wikis/atualizacoes/v0.52.1.md`)
- Revisão profunda: a issue cita `Convite::url()` em `app/Notifications/ConviteDeAcesso.php`; a premissa usada aqui é só "o link é sempre o cadastro do `app`", conferida no `00` e fora de escopo de mudança
- Classe irmã: nenhuma classe nova (método novo em `DestinoAposLogin`)
- Ponytail: passe manual — 2 passos de código, sem abstração nova; o desempate ficou em 1 método de 12 linhas reusando `paineisDe()`

## 1. `DestinoAposLogin::painelSemEscolha()`

- [x] Método público, reusa `paineisDe()` — `app/Support/DestinoAposLogin.php:painelSemEscolha()`, commit 2dc2e79f, 2026-11-13

## 2. Ramo desligado de `RespostaDeCadastro`

- [x] Pretendida acessível ou corrente acessível → `parent::toResponse()` — commit 2dc2e79f, 2026-11-13
- [x] Fallback ao painel acessível com log `info` — commit 2dc2e79f, 2026-11-13
- [x] Zero painéis: log `warning`, logout, invalidate, login do corrente — commit 2dc2e79f, 2026-11-13

## 3. Docs

- [x] `docs/{pt,en}/autenticacao/login-unificado.md` e `docs/{pt,en}/recursos/configuracao-global-filament.md` — commit 2dc2e79f, 2026-11-13

## 4. Testes

- [ ] `tests/Kit/CadastroNoPainelDoPapelTest.php` e `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php`

## 5. Release v0.52.1

- [x] Bumps do dependabot e phpstan — `phpstan analyse` 0 erros, commit 62df722b, 2026-11-13
- [ ] CHANGELOG, `config/kit.php`, roteiro, cópia em `wikis/agentes-e-skills.md`

## Testes

| Arquivo | CT |
|---|---|
| `tests/Kit/CadastroNoPainelDoPapelTest.php` | (a preencher) |
| `tests/Tenancy/CadastroNoPainelDoPapelTenancyTest.php` | (a preencher) |

## Tickets

Não fatiado — 2026-11-13: 8 RQ, 14 CT, sessão sem compactação, 0 perguntas de requisito.

## Revisão do Diff (step 9)

(pendente)

## Conformidade com Rules

(pendente)

## Verificação Final

(pendente)

## Quality Gate

(pendente)

## Candidatos a Rule

(pendente)

## Despachos

| # | Step | Rota / modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|
| 1 | 7 | Codex `codex exec -s read-only` / modelo não exposto | só o `00`, glossário e nomes de testes | `04` com 14 CT, Q?1 | — | amostragem: CT-10, CT-11, CT-12 lidos; nenhum arquivo alterado (`git status` limpo após) |
| 2 | impl. | `fw-executor-ct` / sonnet | `00`, `04`, nomes | (em curso) | — | — |
| 3 | release | Codex `codex exec -s read-only` / modelo não exposto | contrato, roteiro anterior, CHANGELOG, diff | (em curso) | — | — |

Degradação declarada: revisão adversarial do `04` feita pelo próprio Codex na derivação (duas rodadas internas), não por `fw-adversario-ct` separado.

## Referências Abertas

- `references/hosts-e-subagentes.md` não aberta — contrato aplicado pelo corpo do SKILL.md
- template do `00`/`03`: wiki irmã `fix/destino-apos-cadastro` usada como molde
