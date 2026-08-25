# Progresso — Travas de exclusão e upload anônimo

**Concluída em 2026-08-25.**

## 1. `UserResource` do `/app`: negar pelo método que o Filament consulta

- [x] `getDeleteAuthorizationResponse()` e `getDeleteAnyAuthorizationResponse()` com `Response::deny()`
- [x] Constante `MOTIVO_DA_NEGACAO` (mensagem única, sem 403 mudo)
- [x] Import de `Illuminate\Auth\Access\Response`
- [x] Docblock dos `can*()` reescrito: o que eles fazem é navegação e busca global

## 2. `ConviteResource` do `/app`: o mesmo par

- [x] Mesmo par de métodos, mensagem própria
- [x] Docblock aponta para o raciocínio do `UserResource` e para a ADR-01

## 3. `EditUser`: o docblock passa a dizer a verdade

- [x] A frase "a trava de verdade é `UserResource::canDelete()`" saiu
- [x] Entrou a trava real, com o motivo de ela existir mesmo sem ação registrada

## 4. `BoasVindas`: fechar o RPC de upload na rota pública

- [x] `use RestrictsFileUploadsToSchemaComponents`
- [x] Docblock explica por que uma página **pública** precisa dele apesar de estender `Page`

## 5. `ConvitesRecebidos`: o mesmo trait

- [x] Trait aplicado, com nota de que é defesa em profundidade

## 6. Testes

- [x] `tests/Kit/TravaDeExclusaoTest.php` — CT-01, CT-02, CT-03, CT-03B, CT-04, CT-04B, CT-05, CT-11
- [x] `tests/Kit/UploadAnonimoTest.php` — CT-08, CT-09, CT-09B, CT-10
- [x] `tests/Tenancy/AuditoriaDeSegurancaTenancyTest.php` — CT-06, CT-07
- [x] **14 casos, 38 asserções, verdes**

## 7. Commits

- [x] Um por assunto, cada um com o seu teste

## 8. PR, merge e release

- [x] PR aberto, CI verde, merge na `main`, tag e release

## Verificação Final

- [x] `vendor/bin/pint` — passa
- [x] `php artisan test tests/Kit/TravaDeExclusaoTest.php tests/Tenancy/AuditoriaDeSegurancaTenancyTest.php` — 14/14
- [x] Mutação verificada nas seis correções (matriz abaixo)
- [x] `composer bp:off` antes do commit — o Blueprint não viaja no pacote
- [x] Suítes completas do CI

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada |
|---|---|---|
| `canDelete()` não é consultado pelo framework | confirmado: `grep` por chamadores em `vendor/filament/filament/src/` devolve **zero** linhas; `HasAuthorization.php:154` é invólucro de leitura | premissa mantida — é o achado |
| `Response::deny()` serve na assinatura | confirmado: `getDeleteAuthorizationResponse(): Response` exige `Illuminate\Auth\Access\Response`, que tem `deny()`, `allowed()` e `denied()` | plano mantido |
| a `DeleteAction` resolve por esse método | confirmado em `Page.php:309-325` — o `match` mapeia `DeleteAction` → `getDeleteAuthorizationResponse` e `DeleteBulkAction` → `getDeleteAnyAuthorizationResponse` | virou **caso de teste** (CT-03B), não só premissa |
| o trait `RestrictsFileUploadsToSchemaComponents` faz algo | o trait é **uma linha** devolvendo `true`. Quem aplica é `SchemasServiceProvider.php:63-77`, num hook `on('call')` do Livewire | ADR-03 escrita por causa disso |
| o trait basta | **quase**: a guarda exige DOIS métodos e, faltando um, ela **retorna sem abortar** — falha aberta. O segundo, `isFileUploadForSchemaComponent()`, existe em `InteractsWithSchemas:505`, que a `BasePage` compõe | verificado antes de implementar |

### Revisão adversarial dos casos de teste (perfil completo)

Delegada a um agente que **não** derivou os casos, com acesso apenas ao `00-requisito.md` e ao
`04-casos-de-teste.md` (proibido ler o PRD, as ADRs e o código). Ela devolveu **11 lacunas**.

| # | Lacuna apontada | Desfecho |
|---|---|---|
| 1 | nenhum caso exercita ação real; o risco `P=3` declarado não tem matador | **fechada** — CT-03B chama `getDefaultActionAuthorizationResponse()` da página real com `DeleteAction` real |
| 2 | CT-05 usa `master_global`, que vence por `Gate::before` e mascara o mutante | **fechada, e ela estava certa** — `app/Models/Role.php:13` confirma o atalho. Persona trocada para o papel `admin`, que decide pela matriz |
| 3 | pré-condição da matriz nunca afirmada; mutantes condicionais | **fechada** — todo caso de negação começa por controle positivo, e o ator é **autenticado** com a permissão |
| 4 | oráculo de R5 é `class_uses_recursive`, e o mutante "aplicar o trait e sobrescrever depois" sobrevive | **fechada** — o oráculo passou a ser a resposta do método |
| 5 | R5 sem coluna válida (partição no eixo errado) | **fechada** — CT-09B afirma que a tela COM upload legítimo não é restringida |
| 6 | `forceDelete` e `restore` não enumerados | **dissolvida na medição** — `User` e `Convite` **não** usam `SoftDeletes`, então não há caminho de force delete nem de restore |
| 7 | relation manager, `/infra` e contexto sem painel não enumerados | **parcialmente dissolvida** — `find app/Filament/App -iname "*RelationManager*"` devolve zero. Fica declarada como lacuna: se um relation manager de usuário ou convite entrar no `/app`, ele tem os métodos dele e precisa da mesma negação |
| 8 | CT-10 sem âncora de população | **fechada** — o caso afirma `toHaveCount(1)` e `toContain(BoasVindas::class)` |
| 9 | R6 declarada repo-wide, verificada em um arquivo, com `contains` sem polaridade | **fechada** — varredura em todo o `app/`, com asserção positiva sobre a frase inteira |
| 10 | mensagem de negação não afirmada no caminho `*Any`; "não vazia" aceita espaço | **fechada** — `trim()` nas quatro, e o `*Any` afirma mensagem |
| 11 | CT-04 fundia duas operações; contagem 17 vs 19; `Verify` do F-02 apontava CT-05..CT-07 | **fechadas** — caso desmembrado, contagem corrigida, referência cruzada corrigida |

**Três achados eram meus defeitos reais** (2, 3, 4), e o de nº 3 foi confirmado por medição, não por
argumento: a primeira mutação deixou **três dos quatro** casos de negação verdes.

## Matriz de Mutação

Cada correção foi revertida e a suíte rodada. O que ficou vermelho:

| Mutação aplicada | Casos vermelhos | Esperado? |
|---|---|---|
| `getDeleteAuthorizationResponse()` removido do `UserResource` | CT-01, CT-03, CT-03B | sim |
| `getDeleteAnyAuthorizationResponse()` removido do `UserResource` | CT-02, CT-03B | sim |
| o par removido do `ConviteResource` | CT-04 | sim |
| `getDeleteAnyAuthorizationResponse()` removido do `ConviteResource` | CT-04B | sim |
| trait removido da `BoasVindas` | CT-08 (dataset), CT-10 | sim |
| frase falsa devolvida ao docblock do `EditUser` | CT-11 | sim |
| **nenhuma** (estado final) | nenhum — 14/14 verdes | sim |

## Desvios do Plano

- **CT-07 mudou de oráculo.** O plano pedia `assertCanSeeTableRecords` na listagem. Renderizar a
  tabela exige o painel bootado (a coluna de avatar chama o macro `ImageColumn::simpleLightbox()`),
  e bootar o painel dentro de um teste de componente estoura no Breezy
  (`BreezyCore.php:112`, `parameter() on null`, que lê parâmetro de rota inexistente). Os dois
  arranjos são incompatíveis. O caso passou a afirmar sobre `view`/`viewAny` e sobre a query
  escopada — que são os mutantes que ele possui —, e a listagem renderizada continua coberta em
  `tests/Tenancy/AdminDaOrganizacaoTest.php`, que tem o arranjo montado.
- **Os casos foram divididos em DOIS arquivos** (`TravaDeExclusaoTest` e `UploadAnonimoTest`), um
  por achado, e não no arquivo único que o plano previa. O motivo é o pedido de commits
  individualizados: com um arquivo só, o commit do F-02 teria de reabrir o arquivo do F-01 — um
  commit consertando o anterior, que e exatamente o que "sem seguinte" exclui.
- **CT-03 sobreviveu à poda.** O plano previa três casos em R1; ficaram quatro, porque CT-03B
  (mapeamento do framework) nasceu da revisão adversarial e não estava no plano.

## Notas de Implementação

- **`Filament::getCurrentPanel()` VAZA entre arquivos de teste.** Descoberto na mutação: um caso
  deste arquivo rodou com o painel `infra` corrente, herdado de outra suíte, e por isso ficou verde
  com a correção removida. O `beforeEach` agora fixa o painel. Sem isso, a **sensibilidade** dos
  casos depende da ordem de execução — que é o pior tipo de teste verde, porque ele é honesto
  isoladamente e mentiroso em conjunto.
- **O cache de permissão do Spatie também sobrevive entre casos.** Os dois casos `*Any` não caíam
  na mutação mesmo com o painel fixado. `app(PermissionRegistrar::class)->forgetCachedPermissions()`
  depois de cada `givePermissionTo()` resolveu. Sem isso, `$ator->fresh()->can(...)` dizia `true`
  enquanto a policy consultada pelo Gate ainda via o estado antigo.
- **`usuarioComPapel()` não anexa a pivot `tenant_user`.** Ela atribui o papel no contexto da
  organização, e a **posse** é outra coisa: o `whereHas('tenants')` do
  `UserResource::getEloquentQuery()` consulta a pivot. Sem o `attach`, o `EditUser` responde
  "No query results" e parece defeito da correção.
- **`toContain()` do Pest é variádico** (`toContain(mixed ...$needles)`) e **não aceita mensagem**.
  Passar a explicação como segundo argumento a transforma em segundo needle, o `not` passa sempre e
  o caso mede nada. Aconteceu duas vezes nesta wiki. `toBe`, `toBeTrue` e `toBeFalse` **aceitam**
  mensagem (`toBe(mixed $expected, string $message = '')`).
- **Varredura por regex de documentação flagra a própria correção.** O primeiro oráculo do CT-11
  casava "trava" e "canDelete" na mesma frase em qualquer ordem, e reprovava o texto novo — que
  **explica** o erro antigo. Explicar não é cometer, e um oráculo que não distingue as duas coisas
  proíbe documentar. A forma afirmativa no presente (`trava (de verdade )?é ... canDelete`) separa.

## Lacunas Declaradas

- **Relation manager e `/infra`.** Medido: zero relation managers em `app/Filament/App`, e
  `ai_runs`/`recycle_bin_items` vivem só no `/infra`, por desenho. Se um relation manager de usuário
  ou convite entrar no `/app`, ele tem os **seus** `get*AuthorizationResponse()` e precisa da mesma
  negação — nenhum caso desta wiki pegaria isso.
- **Contexto sem painel corrente** (console, job, comando). A negação desta entrega é do resource,
  então ela vale em qualquer contexto; mas nenhum caso exercita a ausência de painel.

## Retrospectiva

- **Funcionou bem**: ler o `vendor/` antes de escrever o plano. Duas das quatro premissas do plano
  só se sustentaram porque foram medidas — e a do trait revelou que a guarda do Filament **falha
  aberta** quando falta um dos dois métodos, o que virou a ADR-03.
- **Funcionou bem**: a revisão adversarial. Ela custou um agente e devolveu três defeitos reais nos
  meus oráculos, incluindo o caso que eu havia chamado de "o mais importante do conjunto" e que não
  provava nada por causa do `Gate::before`.
- **A mutação é o que separa opinião de medida.** A revisão *previu* que os mutantes eram
  condicionais; a mutação *provou*, mostrando três casos verdes com a correção fora. Sem rodar, eu
  teria fechado a entrega com dois terços do conjunto decorativo.
- **Faltou no plano**: o plano tratou "escrever o teste" como um passo. Na prática foram três
  iterações de arranjo — painel vazado, cache de permissão, pivot de organização — e nenhuma delas
  era sobre a feature. Wiki futura que toque autorização em painel escopado deve prever isso como
  passo próprio.
