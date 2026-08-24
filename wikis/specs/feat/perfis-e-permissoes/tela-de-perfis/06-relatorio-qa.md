# Relatório de QA — Tela de papéis do /admin

> Confronto `00-requisito.md` × `01-plano-acao.md` × código rodando.
> Executado por agente independente, que não escreveu o código nem a wiki (a skill proíbe
> autorrevisão). Um ciclo, sem reciclagem: nenhum achado foi roteado para "reimplementar o passo".

## Veredito

**APROVADO COM DÉBITO.**

Nenhum achado bloqueia o merge. Dois foram corrigidos em código, três viraram correção de
especificação nesta wiki, dois saíram para a feature paralela `feat/permissoes-de-telas-e-acoes`
e dois foram confirmados como lacuna já declarada.

## Matriz de Rastreabilidade

Toda linha de "virou código" foi conferida por leitura do arquivo, não pela palavra da wiki.

| RQ | virou passo? | virou caso? | virou código? | veredito |
|---|---|---|---|---|
| RQ-01 termo "Papéis" | 1 | CT-01 | `AdminPanelProvider.php:146-149` | ✅ |
| RQ-02 label do recurso | 1 | CT-01 (3 getters) | `AdminPanelProvider.php:147-149` | ✅ |
| RQ-03 breadcrumb + segmento do registro | 1, 5 | CT-02 (método + tela) | `RoleResource.php` `getRecordTitle()` | ✅ |
| RQ-04 coluna de usuários | 2 | CT-04, CT-05, CT-06 | `RoleResource.php` (`count(distinct users.id)`) | ✅ |
| RQ-05 slide-over | 3 | CT-07, CT-07b, CT-08, CT-21, CT-22, CT-B02 | `RoleResource::acaoDeUsuarios()` | ✅ |
| RQ-06 rótulo em **toda** exibição | 5 | CT-09, CT-10, **+1 novo** | 25 call sites de `Papeis::rotulo` | ⚠️ → ✅ **após o achado 1** |
| RQ-07 contador por grupo | 4 | CT-15, CT-16 | `RoleResource` (badge do Tab e `afterHeader`) | ✅ |
| RQ-08 uuid na URL | 6 | CT-11..CT-14b | migration + `Role.php` | ✅ |
| RQ-09 nenhum `id` em URL no kit | 6 | CT-12 (só `roles`) | só `roles` | ⚠️ **dívida declarada — achado 3** |
| RQ-10 tab vertical | 4 | CT-17 + CT-B01 | `Tabs::make('paineis')->vertical()` | ✅ (orientação sem oráculo, declarado) |
| RQ-11 guard como Select | 7 | CT-18, CT-19, CT-20 | `RoleResource` (`Select` + validação nativa) | ✅ |
| RQ-12 Playwright MCP + design | — | — | — | ❌ **fora da entrega, honesta** |
| RQ-13 componentes nativos | 1..7 | CT-B01, CT-B02 (indireto) | zero Blade novo no diff | ✅ |

Nenhuma cláusula ficou órfã — nenhuma virou passo sem virar código, nem virou código sem virar
caso. RQ-12 está marcada **não atendida** em `01-plano-acao.md` e em `03-progresso.md`, e não
atendida por substituto: o gate conferiu isso explicitamente.

## Achados

### 1 — média — corrigido em código

`app/Filament/App/Pages/ConvitesRecebidos.php` — a confirmação do aceite de convite imprimia a
**chave** do papel (`panel_user`) na mesma tela cuja coluna, três linhas acima, já mostrava
"Painel App".

Por que escapou: o acesso é `$record->papel?->getAttribute('name')`, e a varredura de RQ-06 do PRD
usou `grep "roles\.name\|papel\.name"` — que não casa com essa forma. A "lista fechada de cinco
pontos crus" do `01-plano-acao.md` estava, portanto, incompleta, e nenhum CT alcançava o ponto.

Destino: **implementação** + reabertura da lista na especificação. Corrigido, com caso próprio em
`tests/Tenancy/TelaDePapeisTenancyTest.php` — é uma **terceira família** de renderização (texto de
modal), depois de coluna de tabela e opção de Select, e o oráculo é `getModalDescription()` do
action resolvido, porque o Filament não imprime conteúdo de modal no HTML do componente pai.

### 2 — baixa — **não-defeito**, faltava declarar

`resources/views/errors/403.blade.php` exibe `Papel ausente` e `Seus papéis` com as chaves cruas.

Confirmado no arquivo: o bloco inteiro está sob `$mostrarDiagnostico = ! app()->isProduction();`
(`:15`), e o comentário do próprio arquivo (`:9-13`) diz que ele existe **para o desenvolvedor** e
que por isso não pode chegar ao usuário final. A chave é o que se põe em `assignRole()` — é o valor
útil ali, e a classe CSS `mono` já sinaliza "isto é identificador".

Destino: **não-defeito**. O que faltava era a declaração de escopo, agora em `00-requisito.md`.

### 3 — média — dívida declarada, era afirmação falsa

`03-progresso.md` afirmava *"Auditoria: nenhum outro `id` em URL de registro"*. **Falso.**
`php artisan route:list` traz `infra/exceptions/{record}`, `infra/audits/{record}` e
`infra/command-center/definitions/{record}/edit`, todos resolvendo por PK inteira — o model de
exceções do vendor não tem `uuid` nem `getRouteKeyName()`
(`vendor/bezhansalleh/filament-exceptions/src/Models/Exception.php:33`).

RQ-09 é cláusula geral ("NUNCA ... qualquer registro"), então isto é escopo não entregue, não
engano de leitura. Não bloqueia RQ-08, que é sobre `roles` e está fechado.

Destino: **especificação**. A afirmação foi substituída por dívida declarada, com os três paths
nomeados e a razão de ficarem fora (são models de vendor; trocar a route key deles é entrega
própria, com risco próprio).

### 4 e 5 — roteados para fora desta wiki

- **alta** — `UsersRelationManager::acaoDePapeis()` não tem `->authorize()`, e o `->action()` dela
  **concede papel**. `RelationManager::isReadOnly()` só neutraliza as actions padrão
  (`vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:220-237`), então em
  `ViewTenant` quem tem apenas `View:Tenant` escreve papel.
- **baixa** — `ConvitesTable::reenviar` não tem `->authorize()`; rotaciona token e dispara e-mail
  de terceiro.

As duas são **pré-existentes** e são exatamente o item que o coordenador recortou desta feature
("TODAS as telas, links e actions precisam ter permissão específica"). Vão para
`feat/permissoes-de-telas-e-acoes`. Não são omissão desta entrega, e o achado 4 é o mais grave do
relatório — vale sinalizar para a fila.

### 6, 7, 8 — baixa — corrigidos: três afirmações falsas

- docblock do CT-19 dizia que o `Select` não valida `in:` sozinho. Diz o oposto do código, e o
  código está certo (`Select.php:1742-1774` + `CanBeValidated.php:808-815`). Deixado como estava,
  induziria o próximo agente a reintroduzir o `->in()` que a auditoria do diff acabou de remover.
- `Papeis::rotulo()` documentava `master_global → "Master Global"`; o mapa devolve "Administrador
  Geral".
- o docblock de `Papeis` dizia "dezessete" cópias; eram dezenove quando foi escrito e são
  vinte e cinco agora.

Os três são da classe que `.ai/rules/specs.md` persegue: a **conclusão** estava certa, a
justificativa não — e é isso que torna o erro invisível.

### 9 — baixa — oráculo fraco, corrigido

CT-12 provava só `assertStatus(200)` na linha do `uuid`: um `resolveRouteBinding` que devolvesse
**qualquer** registro passaria. Numa tela de permissão, "qualquer registro" é o defeito. Agora
confere que a tela abriu o papel pedido.

### 10 e 11 — não-defeito, lacuna já declarada

- CT-17 (`tests/Kit/PaineisTest.php`) passa com `Section`, tab horizontal ou vertical — é a lacuna
  R7/M3, declarada em `04-casos-de-teste.md` desde a derivação. A **troca** de painel está provada
  por CT-B01 (com `assertSee`, que confere visibilidade); a **orientação** não tem oráculo barato,
  e seletor por classe de CSS é proibido por `.ai/rules/testes-browser.md`.
- A metade HTTP do CT-01 é `assertSuccessful` + texto de navegação; o oráculo discriminante são os
  três getters no mesmo caso.

## Hipóteses rejeitadas

Relatório sem rejeição parece que só procurou onde achou. Cada uma custou o mesmo que um achado.

| Hipótese | Por que caiu |
|---|---|
| Sobrou algum write de papel pela classe do vendor (`Spatie\Permission\Models\Role`) | As 5 ocorrências restantes em `app/` e as 7 em `tests/` são **todas leitura** (`findByName`/`query`/`where`/type-hint/`instanceof`). `database/` tem um único write, `PapeisSeeder.php:261`, já por `App\Models\Role` |
| Algum model com Resource sem `TemUuid` | Os 6 de `app/Models/` têm a trait. O `AiRunResource` usa model de vendor com `HasUuids` e `uuid` **como PK** (`vendor/fomvasss/laravel-ai-tasks/src/Models/AiRun.php:13-22`) — a URL já é uuid |
| `->action()` morto sob `->modalSubmitAction(false)` em outro lugar | A única ocorrência de `modalSubmitAction(false)` no repo é a desta feature, e ela não tem `->action()`: o log foi para `afterFormFilled` e CT-08 usa `mountAction` |
| `once()` em `usuariosDoPapel()` devolvendo a lista do papel errado | O hash inclui as used-variables da closure (`Illuminate/Support/Onceable.php:64-88`) e `$record` entra por `spl_object_hash` — a chave é por registro |
| CT-02 passando pelo rótulo do tab em vez do breadcrumb | `Paineis::opcoes()` devolve `/app`, então o tab é "Painel **/app**" e não colide com `assertSee('Painel App')` |
| RQ-12 atendida por substituto | Marcada ⚠️ fora no PRD e "não atendida, não como atendida por substituto" no progresso |
| `assertDatabaseHas` só com PK, ou `assertNoJavaScriptErrors` como oráculo único | Nenhum caso nos cinco arquivos. CT-18/19/20 asseram o valor **e** a ausência da linha; os dois CT-B têm oráculo de conteúdo antes do console |

## O que o gate NÃO cobriu

- **Aparência.** As duas lacunas de oráculo (orientação do tab, slide-over vs modal central) são
  sobre o que a tela parece, e nenhuma asserção barata as alcança. São exatamente o que a inspeção
  visual de RQ-12 fecharia — e RQ-12 está fora desta entrega.
- **Mutation testing.** `pest --mutate` não foi executado: sem PCOV no ambiente, `--tia` e
  `--mutate` são inviáveis aqui (`.ai/rules/testes-browser.md` registra um run abortado após
  35 min com Xdebug). O gate de mutantes desta feature é o **previsto** no `04`, não o medido.
- **Regressão da feature paralela.** `feat/permissoes-de-telas-e-acoes` mergeia antes desta e toca
  `PapeisSeeder`, os dois widgets do /admin e o `UsersRelationManager` — os mesmos arquivos. A
  regressão real só é observável depois daquele merge, num segundo rebase.
