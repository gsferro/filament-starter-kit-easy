# Relatório de QA — Roadmap item 9: excluir pela tela um papel que tem convite

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: autorização e e-mail de terceiro, P-06; UI sem JS)
> Natureza da wiki: correção · Toca infra compartilhada: não · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Ciclos anteriores (resumo)

- **Ciclo 1**: REPROVADO → especificação, com QA-01 a QA-08. Está no histórico do git, commit `7044378b`.
- **Ciclo 2**: REPROVADO → especificação, com QA-10 (Major) e QA-09, QA-11 e QA-12 (Minor). Este ciclo conferiu cada um:
  - **QA-09, fechado.** As citações de `docs/{pt,en}/autenticacao/convites.md` estão certas: `aceitar:619`, `aceitarComoUsuarioExistente:687` e `recusar:752` conferem com `grep -n "function aceitar\|function recusar" app/Models/Convite.php`. Também conferem `situacao:600`, `'Aceito':603` e `'Recusado':604`.
  - **QA-10, fechado.** A linha "Custo do caminho principal" do `01` agora traz 3 consultas antes de decidir e, por convite encerrado, 1 releitura, 1 `DELETE` e 1 auditoria, com a marca `*(alterado em …)*`. Isso confere com o `foreach` de `Role::delete()`. O `app/` não mudou desde o ciclo 2: `git show --stat 7e8d45e3` não lista nenhum arquivo de `app/`.
  - **QA-11, fechado.** A `## Verificação Final` do `03` tem o `[x]` do PHPStan. Este gate reproduziu o resultado: `{"result":"passed","errors":0}`.
  - **QA-12, fechado em parte.** No arquivo Kit, os nomes de teste e o comentário da R5 já dizem "encerrados". O nome do teste de Tenancy ainda diz "mortos" (ver QA-14).

## Veredito — Ciclo 3

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 3 · Cosmético: 0
- Não verificadas: J (em parte, porque a suíte Browser não foi rodada) e K (K2 sem prova confiável). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. Q3 e Q4 continuam abertas, mas com P-07 e P-08 vigentes, que é a forma prevista.
- Este é o ciclo 3, o último do teto. Os três Minors não reprovam. Eles viram débito, e cabe ao mantenedor aceitar o débito ou corrigi-lo antes do PR.
- Ambiente:
  - App em `http://127.0.0.1:8150`, com SQLite descartável e sem tenancy.
  - Pest 5 e PHPStan level 8.
  - Navegação com Playwright via Node. O MCP não foi usado.
  - Boost MCP indisponível (timeout de conexão).
- O que este gate alterou no banco:
  - O operador excluiu **"vendas"**, e com ela saíram eva e fabi. Ficaram 2 `deleted` de `Convite` em `audits`, com `user_id=3`, e-mail, `role_id` e `convidado_por_id`.
  - As tentativas de excluir "suporte" e a massa "suporte + analista" foram recusadas. Nada mudou: "suporte" e "analista" continuam, e carla, dani e ana.aceita também.

## Achados

### QA-13 — citações do `Convite.php` na wiki `diagramas-da-arquitetura` que este diff deslocou · Minor · destino 1

- **Dimensão**: L2 (a mesma classe do QA-09)
- **Relacionado a**: passo 1 do PRD (o `scopePendentes` acrescentou 13 linhas); `.ai/rules/specs.md`, que diz "Vale para citação em QUALQUER arquivo"; e a nota do `03` (`## Notas de Implementação`), que dá como corrigidas as citações envelhecidas, inclusive no `04` da `diagramas-da-arquitetura`.
- **Esperado**: toda citação `arquivo:símbolo:linha` que o diff deslocou aponta a linha nova.
- **Observado**: o diff editou `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/04-casos-de-teste.md`, mas só trocou `email_verified_at` (634 → 647), nas linhas 4100 e 4321. Ficaram com a linha da `main`, que estava certa antes:
  - `01-plano-acao.md:915`, `situacao:587-593` (hoje 600);
  - `04:1993`, `situacao:587`, `'Aceito':590`, `'Recusado':591` e `'recusado_em':479`. A linha 479 deixou de existir, porque virou `->pendentes()`;
  - `04:4320`, `aceitarComoUsuarioExistente:674` (hoje 687) e `Este convite já foi usado.:699` (hoje 712);
  - `04:4322`, `syncWithoutDetaching:637` (hoje 650) e `$this->atribuirPapel($user, $papel);:640` (hoje 653). Fica no mesmo parágrafo que o diff editou;
  - `04:4324`, `syncWithoutDetaching:709` (hoje 722) e `atribuirPapel:712` (hoje 725);
  - `04:5568`, `tenant_id:636` e `tenant_id:708`.
- **Repro**:
  1. `bash .claude/skills/feature-wiki/scripts/citacoes.sh wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura` sai com exit 1. Saem 11 linhas de `Convite.php`, como `04-casos-de-teste.md:4322: app/Models/Convite.php:syncWithoutDetaching:637 — símbolo "syncWithoutDetaching" não está na linha 637`.
  2. `git show main:app/Models/Convite.php | sed -n 637p` mostra `syncWithoutDetaching`. Na `main` a citação estava certa.
- **Evidência**: o script não acusa as linhas 4320 e 4322 na forma com espaço, que foram conferidas por `sed -n` na `main` e no `HEAD`. As duas citações de `RegistroPorConvite.php` (4316 e 4317) que o script também acusa já estavam erradas antes desta feature e ficam fora do achado.
- **Ação exigida**: atualizar as citações listadas no `01` e no `04` da `diagramas-da-arquitetura`, depois rodar o `citacoes.sh` nessa wiki. Também corrigir a nota do `03` que diz "corrigidas".

### QA-14 — o nome do teste do CT-12 de Tenancy ainda diz "mortos" · Minor · destino 1

- **Dimensão**: L7 (e o título no L1)
- **Relacionado a**: QA-12 do ciclo 2 (que nomeou só o arquivo Kit); termo "Encerrado" do glossário; R7 e CT-12.
- **Esperado**: o título do `it()` acompanha o Gherkin, que no `04`, linha 412, diz `[CT-12] os convites encerrados das duas organizações somem com o papel, auditados`.
- **Observado**: em `tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php:152`, o teste se chama `it('[CT-12] os convites mortos das duas organizações somem com o papel, auditados', …)`.
- **Repro**: `grep -rn -i "mort" tests/Tenancy/ExclusaoDePapelComConviteTenancyTest.php` acha a linha 152.
- **Ação exigida**: trocar "mortos" por "encerrados" no nome do teste e rodar o arquivo de novo.

### QA-15 — falta a linha do despacho do ciclo 2 do gate em `## Despachos` · Minor · destino 1

- **Dimensão**: L6
- **Relacionado a**: `feature-wiki`, cuja regra é "uma linha por disparo… com a coluna `Custo`" (checklist: "Todo disparo em `## Despachos`").
- **Observado**: a tabela termina na linha 9, que é o ciclo 1 do `fw-qa-gate`. O ciclo 2 foi disparado (o `06` foi gravado e há a linha `Ciclo: 2` em `## Quality Gate`), mas não tem linha própria com modelo, `Custo` e a auditoria do retorno: o `git status` e o banco alterado.
- **Repro**: `grep -n "fw-qa-gate" …/03-progresso.md` acha só a linha `| 9 | 11 | fw-qa-gate — ciclo 1 |`.
- **Ação exigida**: acrescentar as linhas 10 (ciclo 2) e 11 (este ciclo 3), com o `Custo` que o host reportou, ou `—` se ele não reportar.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0. Sem `07-tickets/`, porque a feature não foi fatiada (justificado no `03`). `git diff` por passo: os três passos conferem com `Convite.php`, `Role.php`, `RoleResource.php` e `EditRole.php`. No app, os três caminhos foram reproduzidos de novo: linha, massa e edição. RQ-01 a RQ-04, RQ-07 e RQ-08 foram reproduzidos no app. P-03 também: na massa, "Analista" ficou. P-06, P-08 e P-10 também |
| B | Fronteiras e dados | ✅ | O código do aviso não mudou desde o ciclo 2. Lá, o escape de `<img …>` e o teto de 5 com "e mais 2" foram provados no app. O CT-05 cobre 5 e 6 e passou (41/41) |
| C | Matriz de permissão | ✅ | O master vê os e-mails na linha, na massa e na edição. O operador (`View/Delete/DeleteAny:Role`) vê "2 convites pendentes" com "Peça a quem administra…", e exclui "vendas" com os encerrados (P-08). O operador não tem a edição (sem `Update:Role`), como esperado. A policy não mudou |
| D | Observabilidade | ✅ | No canal `autenticacao`, as recusas pela tela saem como `[RoleResource@recusarSeHaConvitePendente]`, com `pendentes_por_papel` e `operador_id`. O sucesso sai como `[Role@delete] Convites encerrados apagados…`, com `{"role_id":6,"convites":2}`. Nenhum `manual.test` no log, ou seja, sem PII |
| E | Performance | ✅ | O aviso faz 1 consulta por papel. O `delete()` tem custo linear, agora igual ao do PRD (QA-10 fechado) |
| F | UX de erro | ✅ | O aviso diz o que bloqueou e qual saída a pessoa alcança. Nenhuma resposta 5xx e nenhum erro de console nas 5 navegações |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 sobre o diff: exit 0. Nível 3, no escuro (`html.dark` = true): título `rgb(255,255,255)`, corpo `oklch(0.705…)`, fundo `oklch(0.21…)`, legível |
| H | Acessibilidade | ✅ | A view não mudou desde o ciclo 2, cujo axe escopado ao aviso só achou `heading-order` (moderate) no markup do Filament, fora do diff. O aviso tem `role="alert"` |
| I | Segurança da superfície nova | ✅ | Coberta pelo step 9 (`## Revisão do Diff` do `03`: 10 achados, 3 rejeitados). Além dele: nenhuma rota nova, a `ViewRole` não tem exclusão (só `EditAction`), não existe outro resource de papel nos painéis e não há mass assignment novo |
| J | Regressão adjacente | ⚠️ ⏭️ | `pest --parallel --processes=3 --tia --exclude-testsuite=Browser`: 6058 testes, 6055 passaram, 3 pulados, 0 falhas, 33087 asserções. Os testes de docs, convites e papéis passaram (390/390), assim como Kit 41/41 e Tenancy 7/7. A suíte Browser não foi rodada |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, com a mesma linha de `ConviteTest:289`, já rejeitada no ciclo 1. K2 não foi verificado. A revisão adversarial do `04` foi feita em 2 rodadas |
| L | Consistência documental | ⚠️ | L1 `ids-ct.sh` exit 0. O cabeçalho 21/12/70/0 confere com `grep -c`. L2 `citacoes.sh` exit 0 nesta wiki, mas houve achado na irmã (QA-13). L3 ✅. L4 `conformidade-rules.sh` exit 0. L5: as docs pt e en batem entre si e com o app, e a rota `/infra/audits` existe. L6 `checkbox-sem-evidencia.sh` exit 0. Os números da `Verificação Final` foram reproduzidos: 41/41 com 432 asserções, 7/7, PHPStan 0, pint passed. Achado L6: QA-15. L7: QA-14. L8 `roteiro-manual.sh` exit 0, e a L8 não se aplica: não há `08` e o `03` decide `Roteiro manual: não` |

## Débitos Aceitos

- QA-13, QA-14 e QA-15 (Minor, destino 1). São débito proposto e ainda precisam ser aceitos pelo mantenedor e replicados no `03`.

## Suspeitas Não Confirmadas

- `Role::delete()` comita a limpeza dos encerrados se `parent::delete()` devolver `false`. Continua sem repro, como no ciclo 2.

## Não Verificado

- **K2 (mutação)**: falta a prova indispensável. No Windows, o score só é aceito com amostragem manual de mutante, o que exige editar `app/`, e este gate não altera a árvore.
- **J, suíte Browser**: não foi rodada. A feature não tem CT-B, e a alegação do `03` (254 testes, 202 passaram, 52 pulados) não foi reproduzida.
- **L6, falsificabilidade** ("Kit 36 de 37 e Tenancy 7 de 7 falharam sem a correção"): reproduzir exige `git apply -R` em `app/`, que este gate não faz.
