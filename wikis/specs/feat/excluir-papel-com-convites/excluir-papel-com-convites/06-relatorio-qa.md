# Relatório de QA — Roadmap item 9: excluir pela tela um papel que tem convite

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: autorização e e-mail de terceiro, P-06; UI sem JS)
> Natureza da wiki: correção · Toca infra compartilhada: não · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Ciclo 1 (resumo)

REPROVADO → especificação, com QA-01 a QA-08. Conferido neste ciclo:

- **Fechados no código e no app**: QA-01 (PHPStan com 0 erros), QA-03 (3 linhas `[RoleResource@recusarSeHaConvitePendente]` no log), QA-04 (o operador lê "Peça a quem administra os convites…"), QA-07 (o helper ficou fora do docblock de `ofertaPara()`).
- **Fechados na wiki**: QA-02 e QA-05 (os greps de `/admin/roles` e de "lacuna declarada" da concorrência não acham mais nada), QA-06 (o `03` tem `[x]` com evidência e os despachos do step 9).
- **Fechado em parte**: QA-08. O termo "Encerrado" entrou no glossário, mas os nomes de teste continuam com "mortos" (ver QA-12).
- **Fechado em parte**: o resíduo de QA-01. A `Verificação Final` do `03` ainda não tem o item do PHPStan (ver QA-11).

## Veredito — Ciclo 2

**REPROVADO → especificação** (QA-10, Major, destino 1)

- Blocker: 0 · Major: 1 · Minor: 3 · Cosmético: 0
- Não verificadas: J (em parte: a suíte Browser não foi rodada de novo) e K (K2 sem prova confiável). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. Q3 e Q4 continuam abertas com P-07 e P-08 vigentes, que é a forma prevista e não conta como achado.
- Ambiente:
  - App em `http://127.0.0.1:8150`, com SQLite descartável e sem tenancy.
  - Pest 5 e PHPStan level 8.
  - Playwright via Node. O MCP não foi usado.
  - Boost MCP indisponível (falha de conexão).
- Banco alterado por este gate:
  - O operador excluiu **"vendas"**, e com ela saíram eva e fabi.
  - O master revogou **carla** e **dani** e depois excluiu **"suporte"**, e com ela saiu ana.aceita.
  - Foram **inseridos** 7 convites pendentes em "analista" (`a1..a6@qa2.test` e `<img src=x onerror=alert(1)>@qa2.test`). Eles continuam no banco.
  - Uma chamada `Role::delete()` via tinker em "analista" foi recusada e não mudou nada.

## Achados

### QA-09: docs pt/en citam três linhas do `Convite.php` que esta feature deslocou · Minor · destino 1

- **Dimensão**: L2/L5
- **Relacionado a**: passo 1 do PRD (o `scopePendentes` acrescentou 13 linhas antes de `aceitar()`); `.ai/rules/specs.md` ("vale para citação em QUALQUER arquivo")
- **Esperado**: a citação do símbolo aponta a linha onde ele está. O `03` (`## Notas de Implementação`) diz que as citações que envelheceram com o scope novo foram corrigidas.
- **Observado**: em `docs/pt/autenticacao/convites.md` (seção "Sequência: do envio ao aceite") e na seção irmã de `docs/en/autenticacao/convites.md`, só `situacao` foi atualizado. Os outros três continuam com a linha antiga:

  | Citação | Linha citada | Linha real |
  |---|---|---|
  | `aceitar` | 606 | 619 |
  | `aceitarComoUsuarioExistente` | 674 | 687 |
  | `recusar` | 739 | 752 |

  Na `main`, as três estavam certas. O `CitacoesDeCodigoTest` não pega o caso porque a forma de continuação (`:símbolo:linha`, sem path) fica fora dele.
- **Repro**:
  1. `grep -n "function aceitar\|function recusar" app/Models/Convite.php` → 619, 687, 752
  2. `grep -rn ":aceitar:606\|:recusar:739" docs/` → pt e en
- **Evidência**: a saída acima. `git show main:app/Models/Convite.php | grep -n "function aceitar"` → 606, o que confirma que o defeito entrou com este diff.
- **Destino**: 1
- **Ação exigida**: corrigir as três citações em `docs/pt/autenticacao/convites.md` e em `docs/en/autenticacao/convites.md`, e corrigir a nota do `03` que diz "corrigidas".

### QA-10: o custo do `delete()` no PRD não é o do código · Major · destino 1

- **Dimensão**: L3 (e E)
- **Relacionado a**: `01-plano-acao.md`, `## Modelo de Execução`, linha "Custo do caminho principal"; P-09 e D2
- **Esperado**: o PRD diz "+1 no `delete()` do model; com histórico, +1 consulta e 1 `DELETE` + 1 auditoria por convite encerrado".
- **Observado**: o `Role::delete()` atual, reinstalado pela P-09, faz 3 consultas antes de decidir:
  - a trava do papel;
  - a leitura travada de todos os convites;
  - a contagem de pendentes.

  Depois, para cada convite encerrado, faz uma releitura `exists` além do `DELETE` e da auditoria. A linha só ganhou a troca de palavra do QA-02 (`deleting` → `delete()`). O número ficou o de antes da P-09.
- **Repro**:
  1. `DB_CONNECTION=sqlite DB_DATABASE={banco descartável} php artisan tinker --execute '$r=App\Models\Role::findByName("analista"); DB::enableQueryLog(); $r->delete(); foreach(DB::getQueryLog() as $q){echo $q["query"],PHP_EOL;}'` sobre um papel com convite pendente
- **Evidência**: três consultas, `select * from "roles" … limit 1`, `select * from "convites" where "convites"."role_id" = ?` e `select count(*) … "expira_em" > ?`, com resultado `false`. O laço de releitura está no `foreach` de `Role::delete()`. `grep -rn "+1 no" wikis/specs/feat/excluir-papel-com-convites/excluir-papel-com-convites/` só acha o `01`, sem cópia em outro arquivo.
- **Destino**: 1
- **Ação exigida**: reescrever a célula no `01` com o custo real (3 consultas por exclusão; por convite encerrado, 1 releitura + 1 `DELETE` + 1 auditoria) e marcar `*(alterado em …)*`.

### QA-11: a `Verificação Final` do `03` não registra o PHPStan · Minor · destino 1

- **Dimensão**: L6
- **Relacionado a**: ação exigida do QA-01 do ciclo 1 ("Incluir `phpstan analyse` na `## Verificação Final` do `01` e do `03`")
- **Observado**: o `01` ganhou o item. No `03`, o PHPStan só aparece na prosa de `## Quality Gate`, sem checkbox nem evidência na `## Verificação Final`.
- **Repro**: `grep -n -i phpstan …/03-progresso.md` → só a linha de `## Quality Gate`
- **Evidência**: `vendor/bin/phpstan analyse --no-progress --memory-limit=4G` → `{"result":"passed","errors":0}` (reproduzido por este gate).
- **Ação exigida**: acrescentar `- [x] vendor/bin/phpstan analyse — 0 erros, {data}` à `## Verificação Final` do `03`.

### QA-12: os nomes de teste de CT-08 e CT-14 ainda dizem "mortos" · Minor · destino 1

- **Dimensão**: L7 (e o título no L1)
- **Relacionado a**: QA-08 do ciclo 1; glossário "Encerrado"; R5
- **Observado**: o `04` passou a "encerrados" nos títulos de CT-08 e CT-14, mas os `it()` correspondentes em `tests/Kit/ExclusaoDePapelComConviteTest.php` e o comentário da R5 no arquivo continuam com "mortos". O título do teste diverge do título do Gherkin.
- **Repro**: `grep -n "mortos" tests/Kit/ExclusaoDePapelComConviteTest.php` → `[CT-08] … convites mortos dele …` e `[CT-14] … audita os mortos dos dois`
- **Ação exigida**: alinhar os nomes dos `it()` e o comentário da R5 ao termo do glossário, como no `04`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0. Sem `07-tickets/` (não fatiado, com justificativa no `03`). `git diff` por passo confere. No app: RQ-01..RQ-04, RQ-06 (revogar carla e dani na tela de Convites e excluir "suporte"), RQ-07, RQ-08 (5 `deleted` de `Convite` em `audits`, com e-mail, `role_id` e `convidado_por_id`), P-02, P-03, P-04 ("e mais 2" com 7 pendentes), P-06, P-07, P-08 e P-10 |
| B | Fronteiras e dados | ✅ | Um e-mail `<img src=x onerror=alert(1)>@qa2.test` gravado direto no banco aparece escapado no aviso (0 `<img>` no nó, nenhum dialog). Com 7 pendentes, o aviso mostra 5 e "e mais 2" |
| C | Matriz de permissão | ✅ | Master: e-mails na edição. Operador sem `ViewAny:Convite`: contagem na linha e na massa, e exclusão de "vendas" com os encerrados (P-08). A policy não mudou |
| D | Observabilidade | ✅ | 3 recusas pela tela → 3 linhas `[RoleResource@recusarSeHaConvitePendente]` no canal `autenticacao`, com `pendentes_por_papel` e `operador_id` e sem e-mail. As linhas de sucesso `[Role@delete]` têm `role_id` e contagem. Sem PII |
| E | Performance | ✅ | O aviso faz uma consulta por papel selecionado. O `delete()` faz 3 consultas + 3 por convite encerrado, linear e aceitável. A divergência com o PRD está em QA-10 |
| F | UX de erro | ✅ | O aviso diz o que bloqueou e a saída alcançável para cada persona (P-10). Sem 4xx/5xx nem erro de console |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1: `dark-mode.sh` sobre o diff, exit 0. Nível 3: capturas no escuro (master e operador) e no claro, legíveis: título `rgb(255,255,255)`, corpo `oklch(0.705…)` sobre `oklch(0.21…)` |
| H | Acessibilidade | ✅ | axe (vendor do `pest-plugin-browser`) escopado ao aviso nos dois temas: só `heading-order` (moderate) no markup da notificação do Filament, fora do diff. `role="alert"`. O foco volta ao botão Excluir |
| I | Segurança da superfície nova | ✅ | Coberta pelo step 9, `## Revisão do Diff` do `03` (10 achados, 3 rejeitados). Além dele: escape do e-mail provado no app, sem rota nova, sem mass assignment, registro resolvido pela query da tabela |
| J | Regressão adjacente | ⚠️ ⏭️ | `pest --parallel --processes=3 --tia --exclude-testsuite=Browser`: 6058 testes, 6055 passaram, 3 pulados, 0 falhas (TIA caiu para execução direta). `SiteDeDocumentacao` + `CitacoesDeCodigo` + `RoteiroDeAtualizacao`: 283/283. CT-12 da ancestral verde por D4. Browser não rodado de novo |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1: a única linha (`assertDatabaseMissing` por PK na revogação, em `ConviteTest`) foi rejeitada no ciclo 1 e não reaparece como achado. K2 não verificado. Revisão adversarial do `04` feita em 2 rodadas |
| L | Consistência documental | ⚠️ | L1 `ids-ct.sh` exit 0: 48 linhas de Exemplos = 41 Kit + 7 Tenancy; cabeçalho 21/12/70/0 conferido por `grep -c`. L2 `citacoes.sh` exit 1, só com linhas do `06` do ciclo 1 (substituído por este); docs em QA-09. L3: QA-10. L4 `conformidade-rules.sh` exit 0 e filacheck "All 17 rules passed!". L5: QA-09. L6 `checkbox-sem-evidencia.sh` exit 0; números reproduzidos (41/41 com 432 asserções, 7/7, 283/283, 6058 testes, filacheck 17); QA-11. L7: QA-12. L8 `roteiro-manual.sh` exit 0, n/a: sem `08`, com `Roteiro manual: não` no `03` |

## Débitos Aceitos

- Nenhum neste ciclo. Os Minors QA-09, QA-11 e QA-12 voltam junto com a correção do QA-10.

## Suspeitas Não Confirmadas

- `Role::delete()` comita a limpeza dos encerrados se `parent::delete()` devolver `false`. Continua sem repro: o spatie não sobrescreve `delete()` e nenhum listener `deleting` do kit cancela.
- O caminho de recusa do `delete()` lê e trava todos os convites antes de contar os pendentes, uma leitura que não serve para nada quando a exclusão é recusada. É custo, não defeito. Fica só como registro.

## Não Verificado

- **K2 (mutação)**: prova indispensável pendente. `.ai/rules/testes.md` só aceita score no Windows com amostragem manual de mutante (editar `app/` e rodar o CT), e este gate não altera a árvore. Não foi repetida a execução por `pestw.cmd`, cujo score o ciclo 1 já mostrou não ser plausível (cerca de 32 ms por mutante).
- **J**: a suíte Browser não foi rodada de novo. A feature não tem CT-B. A alegação do `03` (254 testes, 202 passaram, 52 pulados, 0 falhas) não foi reproduzida.
- **L6, falsificabilidade** (`03`, `Verificação Final`: "Kit 36 de 37 e Tenancy 7 de 7 falharam sem a correção"): reproduzir exige `git apply -R` em `app/`, que este gate não faz.
