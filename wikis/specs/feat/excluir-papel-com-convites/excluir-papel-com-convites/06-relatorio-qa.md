# Relatório de QA — Roadmap item 9: excluir pela tela um papel que tem convite

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: autorização e e-mail de terceiro, P-06; UI sem JS)
> Natureza da wiki: correção · Toca infra compartilhada: não · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação** (destino de maior prioridade entre os Majors; QA-01 também reprova, destino implementação)

- Blocker: 0 · Major: 2 · Minor: 6 · Cosmético: 0
- Não verificadas: J (parcial, suíte Browser não reexecutada) e K (K2 sem prova confiável). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. As perguntas Q3 e Q4 seguem abertas, com P-07 e P-08 vigentes (forma prevista; não é achado)
- Ambiente: app em `http://127.0.0.1:8150` (SQLite descartável) · Pest 5.0.5 · PCOV e Xdebug carregados · Playwright via Node (o MCP não foi usado) · Boost MCP indisponível (falha de conexão)
- Banco alterado por este gate: o papel **"vendas"** foi excluído pelo `operador@manual.test`, e com ele os convites expirado (eva) e recusado (fabi). As duas recusas de "suporte" (linha e massa com "analista") e a recusa na edição pelo master não mudaram nada.

## Achados

### QA-01: PHPStan level 8 fica vermelho com o diff (gate do CI) · Major · destino 2

- **Dimensão**: J (regressão de um gate que a `main` mantinha verde)
- **Relacionado a**: passo 3 do PRD; `.ai/rules/app.md:33` ("PHPStan no level 8 (gate do `composer test`)"); `.github/workflows/ci.yml:45`
- **Esperado**: level 8 com zero erros, como afirmam o README e a rule. `composer.json` tem `types:check` dentro de `test`
- **Observado**: 2 erros `argument.type` em código novo
- **Repro**:
  1. `vendor/bin/phpstan analyse --no-progress --memory-limit=4G`
- **Evidência**: `"errors":2`. `app/Filament/Admin/Resources/Roles/Pages/EditRole.php:32` e `app/Filament/Admin/Resources/Roles/RoleResource.php:298`: *"Parameter #2 $papeis of static method RoleResource::recusarSeHaConvitePendente() expects iterable<App\Models\Role>, array<int, Illuminate\Database\Eloquent\Model> given."*
- **Destino**: 2 implementação
- **Ação exigida**: corrigir o tipo na origem (o `before` recebe `Model $record`), sem baseline nem ignore. Incluir `phpstan analyse` na `## Verificação Final` do `01` e do `03`, porque nenhum dos dois o lista.

### QA-02: PRD e ADR descrevem rota e mecanismo que o código não tem · Major · destino 1

- **Dimensão**: L3
- **Relacionado a**: `## Superfície de UI` e `## Modelo de Execução` do `01`; `## Superfície Livewire` do `02`; D1
- **Esperado**: o texto bate com o código ou leva a marca `*(alterado em …)*`
- **Observado**:
  - (a) A rota é `/admin/shield/roles` (`php artisan route:list --path=admin --name=roles`). O `01` diz `/admin/roles` e `/admin/roles/{record}/edit`; `GET /admin/roles` responde 404. As docs pt/en dizem `/admin/shield/roles`, que está certo.
  - (b) O `02` diz três vezes "recusa por convite pendente no `before` e no `deleting`". Não existe recusa em `deleting`: ela está no `delete()` sobrescrito (`app/Models/Role.php:68`), e o D1 do `01` já foi marcado como alterado. O `01:110` ainda diz "+1 no `deleting`".
- **Repro**:
  1. `grep -rn "/admin/roles" wikis/specs/feat/excluir-papel-com-convites/excluir-papel-com-convites/` → `01-plano-acao.md:86,87,88`, `04-casos-de-teste.md:29,74`
  2. `grep -rn "deleting" …/excluir-papel-com-convites/` → `02-decisoes-arquiteturais.md:12,13,14`, `01-plano-acao.md:110`, `04-casos-de-teste.md:72` (este cita o `02`)
- **Evidência**: as saídas acima, mais o 404 em `/admin/roles` no Playwright
- **Destino**: 1 especificação
- **Ação exigida**: corrigir e marcar a data em `01:86-88`, `01:110`, `02:12-14`, `04:29`, `04:72` e `04:74`.

### QA-03: a recusa pela tela não gera log, ao contrário do que o PRD promete · Minor · destino 2

- **Dimensão**: D
- **Relacionado a**: passo 3 do PRD ("Logs: o do passo 2 cobre a recusa; o aviso não loga de novo"); RQ-02
- **Esperado**: toda recusa grava `[Role@delete] Exclusão recusada… | papel: {id}` no canal `autenticacao`
- **Observado**: o `before()` chama `$action->cancel()` antes de `Role::delete()`. Por isso, nos três caminhos da tela, a recusa não deixa nenhuma linha no log. Só a exclusão por fora da tela registra a recusa.
- **Repro**:
  1. Entrar como `operador@manual.test` e excluir "Suporte" pela linha; depois, em massa, "Suporte" + "Analista"
  2. `grep "Role@delete\] Exclus" storage/logs/autenticacao-*.log | wc -l` → `0`
- **Evidência**: 0 linhas depois das duas recusas (01:31–01:32 UTC). O teste manual anterior da sessão (00:44–00:55) também não deixou nenhuma. As linhas de sucesso existem e estão no padrão, sem PII: `[Role@delete] Convites encerrados apagados com o papel | papel: 6 {"role_id":6,"convites":2}`
- **Destino**: 2 implementação
- **Ação exigida**: registrar a recusa no `recusarSeHaConvitePendente()` (com `role_id` e contagem, sem e-mail), ou decidir no `01` que a recusa da tela não loga e corrigir o passo 3.

### QA-04: o aviso manda revogar numa tela que o operador não alcança · Minor · destino 1

- **Dimensão**: F (e o eixo "estado de erro sem saída" da I, para essa persona)
- **Relacionado a**: P-06, RQ-06, CT-13/CT-18 (o `04:500` permite o texto)
- **Esperado**: a mensagem diz como resolver
- **Observado**: o operador sem `ViewAny:Convite` lê "Revogue os convites pendentes na tela de Convites e tente de novo.", mas não tem Convites no menu e não pode revogar
- **Repro**:
  1. Entrar como `operador@manual.test` e excluir "Suporte" pela linha
  2. O aviso aparece com essa frase, e a barra lateral mostra só "Papéis"
- **Evidência**: captura `op-recusa-linha.png` (scratchpad do gate); `.fi-no-notification` contém `<strong>Suporte</strong>: 2 convites pendentes<br>Revogue…`
- **Destino**: 1 especificação
- **Ação exigida**: decidir o texto para quem não vê convites, como uma `P-nn` nova (por exemplo, "peça a quem administra os convites"). Depois, CT no `04` com `Origem` nessa `P-nn`, e só então a correção.

### QA-05: o `04` ainda trata a concorrência como lacuna e lista P-01…P-08 · Minor · destino 1

- **Dimensão**: L5 (consequência invalidada ainda descrita)
- **Observado**: `04:32` ("corrida entre checagem e exclusão (lacuna declarada)"), `04:668` ("Concorrência: lacuna declarada… SQLite não sustenta") e `04:54` ("toda P-nn vigente (P-01…P-08)"). Depois disso a P-09 entrou, e a R11/CT-20 cobre a atomicidade.
- **Repro**: `sed -n '32p;54p;668p' …/04-casos-de-teste.md`
- **Ação exigida**: alinhar as três linhas à R11/CT-20 e à P-09.

### QA-06: o `03` não registra o estado real da entrega · Minor · destino 1

- **Dimensão**: L6
- **Observado**:
  - Os 5 itens dos passos e os 15 da `## Verificação Final` estão todos `[ ]`, com os dois commits feitos e "Estado: em revisão". Não há nenhuma alegação que se possa conferir. O `checkbox-sem-evidencia.sh` dá exit 0 só porque não existe `[x]`.
  - `## Testes` (`03:19`) não menciona o CT-20.
  - `## Despachos` não tem os disparos do step 9 (`/code-review`, `fw-revisor-diff`, `codex`) que a `## Revisão do Diff` cita na linha CR-01/RD-01/CX-01.
- **Repro**: `grep -c '^- \[ \]' …/03-progresso.md` → 20; `grep -c '\[x\]' …/03-progresso.md` → 0
- **Ação exigida**: fechar cada item com ` — {evidência}`, ou deixá-lo aberto e dizer por quê; acrescentar o CT-20 e as três linhas de despacho do step 9 com `Custo` (o valor reportado ou `—`).

### QA-07: o novo helper descolou o docblock de `ofertaPara()` · Minor · destino 2

- **Dimensão**: L (documentação de código no diff)
- **Observado**: `apagarPapelPelaQuery()` foi inserido entre o docblock de `ofertaPara()` (a explicação da correção CONDICIONAL e o `@param array<string, mixed> $atributos`) e a função. O helper fica com dois docblocks seguidos, e `ofertaPara()` fica sem nenhum.
- **Repro**: `sed -n 1175,1196p tests/Pest.php`
- **Ação exigida**: mover o helper para fora do par docblock/função.

### QA-08: o termo "encerrado" foi decidido na feature e não está no glossário · Minor · destino 1

- **Dimensão**: L7
- **Observado**: "encerrados" (aceito, recusado ou expirado) é termo de P-08, P-09 e do `01:39`, e entra na UI do domínio via docs e CHANGELOG ("Aceitos, Recusados ou Expirados"). Não há entrada em `wikis/glossario.md`. O `04` usa ainda "mortos" como sinônimo.
- **Repro**: `grep -n -i "encerrad" wikis/glossario.md` → nada
- **Ação exigida**: registrar o termo no glossário, com "Não confundir com: Expirado".

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; sem `07-tickets/` (não fatiado, com justificativa no `03`). O `git diff` de cada passo confere: passo 1 (`Convite::scopePendentes`), 2 (`Role::delete`/`convites`), 3 (`RoleResource`/`EditRole`). No app: RQ-01..RQ-04, RQ-07 e P-02/P-03/P-06/P-07/P-08 reproduzidos |
| B | Fronteiras e dados | ✅ | validade × agora em 3 valores (CT-01) e teto de 5 (CT-05) verdes; e-mail e rótulo passam por `e()` no corpo HTML (`RoleResource.php`); validade nula = Expirado (CT-01) |
| C | Matriz de permissão | ✅ | master: e-mails (edição, no app); operador com `Delete:Role` e sem `ViewAny:Convite`: só a contagem (linha e massa, no app) e exclusão dos encerrados (P-08, "vendas" no app); edição sem `Update:Role` fica inacessível ao operador, e a célula está coberta por CT-13 com a persona `admin`. A policy não mudou |
| D | Observabilidade | ⚠️ | QA-03. O log de sucesso está no canal certo, com prefixo e context, e sem PII |
| E | Performance | ✅ | uma consulta `pluck` por papel selecionado no aviso, linear na seleção e não nas linhas da tabela (CR-04 rejeitado com motivo); `delete()` com uma releitura por convite encerrado, aceitável para histórico |
| F | UX de erro | ⚠️ | QA-04. Há título e corpo em pt, o que bloqueou e o que fazer, sem detalhe interno; nenhum 4xx/5xx nem erro de console nas ações |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0 (diff só PHP); nível 3: capturas claro e escuro legíveis (título branco e corpo `oklch(0.705…)` sobre `oklch(0.21…)`) |
| H | Acessibilidade | ✅ | axe (vendor do `pest-plugin-browser`) nos dois temas sem violação no aviso, que tem `role="alert"`; o foco volta ao botão Excluir. Violações pré-existentes da tabela (`empty-table-header`, `link-name`, `heading-order`) estão fora do diff |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9, `## Revisão do Diff` do `03` (10 achados, 3 rejeitados). Além dele: sem rota nova nem `can:` faltando; sem mass assignment; e-mail de terceiro escondido sem `ViewAny:Convite` (no app); escape `e()`; registro resolvido pela query da tabela, não pelo id do cliente |
| J | Regressão adjacente | ⚠️ ⏭️ | `pest --parallel --processes=3 --tia --exclude-testsuite=Browser`: 6056 testes, 6053 passaram, 3 pulados, 0 falhas (TIA caiu para execução direta). Convites e papéis da ancestral: 246/246; CT-12 da `phpstan-nivel-8` verde por D4. QA-01 (PHPStan). Browser não reexecutado |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1: a única linha (`ConviteTest.php:289`, `assertDatabaseMissing` por PK na revogação) é rejeitada, porque é exclusão física e a PK é o oráculo certo. K2 sem prova confiável (ver *Não Verificado*). Revisão adversarial do `04` feita em 2 rodadas |
| L | Consistência documental | ⚠️ | L1 `ids-ct.sh` exit 0 (46 testes = 46 linhas de Exemplos; cabeçalho 20/11/65/0 conferido por `grep -c`); L2 `citacoes.sh` exit 0; L3 QA-02; L4 `conformidade-rules.sh` exit 0, filacheck 17/17 e pint ok; L5 QA-05 (docs pt × en × CHANGELOG coerentes e números do README conferidos: 217/255 arquivos, `SiteDeDocumentacaoTest` verde); L6 `checkbox-sem-evidencia.sh` exit 0 e QA-06; L7 QA-08; L8 n/a (sem `08`, `Roteiro manual: não` no `03`) |

## Débitos Aceitos

- Nenhum neste ciclo. Os Minors QA-03..QA-08 voltam com a correção dos Majors.

## Suspeitas Não Confirmadas

- `Role::delete()` comita a limpeza dos encerrados se `parent::delete()` devolver `false` (um listener `deleting` que cancele). Hoje não há listener assim em `app/` (grep sem resultado), por isso não há repro.
- `## Notas de Implementação` do `03` alega "18/18 nos dois modos; 5 `deleted` em cada". Não há comando que reproduza o número; está fora da `Verificação Final`, então não é achado L6.

## Não Verificado

- K2 (mutação) — prova indispensável pendente. Duas execuções por `pestw.cmd` com `--no-cache` e `--path=app/Models/Role.php`: (1) com `--filter` de 4 CT, 22 mutantes, 100 %, Duration 0,83 s; (2) sem filtro, arquivo Kit (39 testes levam 4 min 25 s), 27 mutantes, 100 %, Duration 0,86 s. Isso dá cerca de 32 ms por mutante, contra segundos por teste. Foram dados como mortos mutantes sem efeito observável no SQLite: `Line 75: RemoveMethodCall`, a leitura com `lockForUpdate()` cujo resultado é descartado, e `Line 109/120: RemoveArrayItem` (context do log, que nenhum CT afirma). Como o arnês está quebrado, o score não vale. A amostragem manual de mutante exige editar `app/`, o que este gate não faz. `RoleResource::recusarSeHaConvitePendente` não foi mutado.
- J — suíte Browser não reexecutada, por tempo e porque a feature não tem CT-B; a alegação do orquestrador (254/202/52/0) não foi reproduzida.
- Auditoria no app servido — o banco do servidor não pôde ser lido (o `.env` aponta outro SQLite); os `deleted` do "vendas" não foram conferidos no app, só pelos CT-08/CT-15 verdes.
- RQ-06 no app (revogar e excluir de novo) não foi reproduzido no app servido; está coberto por CT-10/CT-19 verdes.
