# Casos de Teste — O administrador da organização não alcança quem governa a instalação

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. A implementação não existe; o que foi lido do código é
> convenção de teste (`tests/Tenancy/AdminDaOrganizacaoTest.php`, helpers de `tests/Pest.php`) e a
> API do Filament que o `01` nomeia como superfície.
>
> **Segunda versão.** A primeira passou por revisão adversarial (sub-agente com `00` + `04`, sem PRD)
> e levou 19 achados — 5 implementações erradas que passariam inteiras, 5 oráculos fracos, 3 defeitos
> de estrutura, 6 cláusulas sem falsificador. Todos fechados abaixo; a lista está em
> `## Revisão Adversarial`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A fronteira: quem governa a instalação some e não se edita | 3 — pivot de team do spatie, relação morph, dois contextos, duas camadas | 3 — autorização; escalada para a conta mais poderosa da instalação | 9 | **completo** |
| Regressão: `admin_app` continua vendo, criando e editando os seus | 1 | 2 — quebrar isto é bloquear a persona | 2 | mínimo |

- Técnicas aplicadas: **matriz papel × contexto** completa (4 células, mais papel misto e sem
  papel), **EP por atributo** (`roles.painel`, não por nome), **rastreio de efeito** por superfície,
  **autorização exercida fora do caminho feliz** com oráculo no **dado** (hash da senha),
  **propriedade** (predicado ≡ scope), **regressão fora do painel** (o scope não é global).
- Cenários: **11** · Regras: 5 · Mutantes previstos: 19 · Sem matador: 0

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | um predicado e um scope em `User`; a query e uma resposta de autorização no `UserResource` do `/app`. Sem migration, sem policy nova. **Não** é global scope | CT-05, CT-11 |
| **F** | esconder (4 superfícies); recusar (edição, por qualquer caminho); manter (ver, criar, editar usuário comum) | CT-01…CT-04, CT-06…CT-10 |
| **D** | o **alvo**: papel × contexto — 4 células (`≠app`×global, `≠app`×esta org, `≠app`×**outra** org, `app`×global) mais papel **misto**, **sem papel**, e um papel de instalação **novo** criado no teste; pessoa em duas organizações | CT-01 (a matriz), CT-07 |
| **I** | listagem Livewire, route binding (HTTP), busca global, badge, resposta de autorização chamada direto, componente `EditUser` montado com o alvo, componente `CreateUser` | CT-01…CT-04, CT-06, CT-09, CT-10 |
| **P** | `permission.teams` ligado (`tests/Tenancy`). Sem teams o resource nem existe — **declarado**: fora de escopo pelo `00` | — |
| **O** | executora `admin_app`; **e** a executora que também governa a instalação (ADR-01) | CT-08 |
| **T** | **não se aplica**: sem estado temporal, sem concorrência | — |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — quem governa a instalação não existe para o `/app`: listagem, URL direta, busca, badge | fronteira (completo) | RQ-02 | matriz papel × contexto + EP por atributo + rastreio por superfície | CT-01, CT-02, CT-03, CT-07, CT-08 |
| R2 — a edição de quem governa a instalação é recusada por qualquer caminho, sem mudar a conta, com registro | fronteira (completo) | RQ-03 | autorização fora do caminho feliz + oráculo no dado + rastreio de efeito (log) | CT-04, CT-10 |
| R3 — o predicado e o recorte dizem a mesma coisa sobre o mesmo conjunto | fronteira (completo) | RQ-02 + RQ-03 | propriedade | CT-05 |
| R4 — o `admin_app` continua vendo, criando e gravando os usuários comuns da sua organização | regressão (mínimo) | RQ-01, RQ-04 | gate de tela de escrita (criar **e** editar) + herdadas | CT-06, CT-09 |
| R5 — a regra é do `/app`: fora dele ninguém some | regressão (mínimo, **escalada** a completo pela revisão) | RQ-02 ("dentro do painel app") | regressão fora do painel | CT-11 |

**RQ-04** (só a sua organização) tem cobertura **herdada** e não duplicada: `lista apenas os usuarios
da organizacao corrente` e `nega o acesso direto ao registro de usuario de outra organizacao`
(`AdminDaOrganizacaoTest.php:113-147`). Bruno (Globex) entra na fixture como controle disso.

**Técnica escalada**: R5 nasceu da revisão adversarial (achado 5). O `00` diz "dentro do painel app",
e nenhum cenário da primeira versão saía do painel — um global scope no `User` passaria em tudo e
apagaria o `master_global` do `/admin`.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nomes `governaAInstalacao()`, `queNaoGovernamAInstalacao()`, `getEditAuthorizationResponse()` | escolha de implementação | detalhe dos cenários (CT-04, CT-05, CT-10 os chamam pelo nome; o oráculo é comportamento) |
| Texto da mensagem de negação | comportamento visível que o requisito não determina | **não** é oráculo; CT-04 assere `denied()` |
| Channel `autenticacao`, nível `warning`, chaves `alvo_id`/`executor_id` | o requisito não fala em log | oráculo **auxiliar** em CT-04, herdado da convenção das barreiras vizinhas — declarado |
| "Papel de instalação = `roles.painel` ≠ `app` (ou nulo), no contexto global" | resposta do solicitante + definição já existente em `canAccessPanel()` | **oráculo legítimo**: é o `00` |
| Páginas do resource: `index`, `create`, `edit` — sem `view` | fato do código, lido para saber que superfície existe | CT-02 cobre a única rota com `{record}`; se uma página `view` nascer, ela precisa de linha aqui (registrado em `## Fora do alcance`) |

**Perguntas em aberto** (já em `00-requisito.md` → `## Ambiguidades`): o `admin` **dentro** de uma
organização (esta ou outra) continua visível — premissa adotada; CT-01 tem as linhas marcadas
`@premissa`.

## Setup Global

### Camada

`tests/Tenancy`, sempre: `admin_app` só existe com tenancy (`.ai/rules/testes.md`), e
`TenancyTestCase` fixa `permission.teams` antes das migrations.

### Personas e fixture — "a organização com gente de fora dentro"

Uma organização (Acme), uma vizinha (Globex), onze pessoas. Cada uma existe para uma célula:

| Pessoa | Papel | Contexto | Vinculada à Acme? | Aparece para a `admin_app` da Acme? | Célula |
|---|---|---|---|---|---|
| Ana | `admin_app` | Acme | sim | **sim** | executora; controle |
| Beto | `panel_user` | Acme | sim | **sim** | `app` × esta org — controle |
| Gil | `master_global` **e** `panel_user` | global **e** Acme | sim | **não** | o nome do requisito, com papel **misto** (achado 4) |
| Ada | `admin` | global | sim | **não** | `≠app` × global, não master |
| Ivo | `infra` | global | sim | **não** | idem, segundo painel |
| Aldo | `auditor` — papel **criado no teste**, `painel = 'infra'` | global | sim | **não** | EP por **atributo**, não por nome (achado 2) |
| Leo | `admin` | **Acme** | sim | **sim** `@premissa` | `≠app` × esta org — não governa |
| Rui | `admin` | **Globex** | sim (e à Globex) | **sim** `@premissa` | `≠app` × **outra** org — não governa (achado 3) |
| Nina | `panel_user` | **global** | sim | **sim** | `app` × global — a célula que faltava (achado 1) |
| Nino | nenhum | — | sim | **sim** | "vazio": sem papel algum (achado 19) |
| Bruno | `admin_app` | Globex | **não** | não (regra herdada RQ-04) | controle de organização |

**Visíveis na Acme: Ana, Beto, Leo, Rui, Nina, Nino = 6.** O número sai desta tabela e é usado
literalmente em CT-07. **Não governam a instalação (predicado): os 6 acima mais Bruno = 7** — CT-05.

Construção com `tenant()`, `usuario()`, `papelNaOrganizacao($u, $papel, $tenant|null)` (null =
contexto global), `Role::create(['name' => 'auditor', 'guard_name' => 'web', 'painel' => 'infra'])`,
`->tenants()->attach()`. Semear `ShieldPermissionsSeeder` + `PapeisSeeder` no `beforeEach`. **Guardar o
`password` (hash) de Gil** no arranjo — CT-04 e CT-10 comparam depois.

### Fakes

`Log::spy()` em CT-04. Nada mais.

### Contexto de painel

`noPainelDa($acme)` antes de qualquer chamada estática ao resource do `/app`; para CT-11,
`noPainelBootado('admin')` (ou nenhum painel) — é o ponto do cenário.

---

## Regra R1 — quem governa a instalação não existe para o `/app`: listagem, URL direta, busca, badge

> `RQ-02` · perfil **completo** · técnica: **matriz papel × contexto** (4 células + misto + vazio +
> papel novo) e **rastreio de efeito por superfície**

```gherkin
# language: pt

Funcionalidade: O administrador da organização não alcança quem governa a instalação

  Regra: quem governa a instalação não aparece em nenhuma superfície do painel da organização

    Cenário: [CT-01] a listagem mostra exatamente quem não governa a instalação
      Dado a Acme com as onze pessoas da fixture
      Quando Ana abre a listagem de usuários da Acme
      Então a listagem contém Ana, Beto, Leo, Rui, Nina e Nino — os seis, pelo registro
      E a listagem não contém Gil, Ada, Ivo, Aldo nem Bruno
```

> Presença **e** ausência na **mesma carga** (`loadTable()` uma vez): `assertCanNotSeeTableRecords`
> sozinho passa numa tabela vazia por erro de query engolido (achado 10). Não é `Esquema`: a matriz
> é um estado só, e as duas listas nomeiam pessoas — identidade, não contagem.

```gherkin
    Esquema do Cenário: [CT-02] a URL direta decide pelo alvo: 404 para quem governa, a ficha certa para quem não governa
      Dado a Acme com a fixture
      Quando Ana pede a tela de edição de "<pessoa>" pela URL
      Então a resposta é "<status>"
      E "<conteudo>"

      Exemplos:
        | pessoa | status | conteudo                                      | # partição                  |
        | Gil    | 404    | nada da ficha de Gil aparece                  | governa (misto)             |
        | Aldo   | 404    | nada da ficha de Aldo aparece                 | governa, papel novo         |
        | Beto   | 200    | o e-mail de Beto está no formulário exibido   | não governa — e é a ficha DELE |

    Esquema do Cenário: [CT-03] a busca global do resource não encontra quem governa a instalação
      Dado a Acme com a fixture
      Quando o resource resolve a busca global por "<termo>" no painel da Acme
      Então os resultados "<resultado>"

      Exemplos:
        | termo | resultado                 | # partição      |
        | Gil   | são vazios                | governa         |
        | Ada   | são vazios                | governa         |
        | Beto  | contêm Beto, e só ele     | não governa     |
        | Rui   | contêm Rui                | @premissa outra org |

    Cenário: [CT-07] o badge do menu conta só quem não governa a instalação
      Dado a Acme com a fixture
      Quando o resource resolve o badge de contagem no painel da Acme
      Então o badge é "6"

    Cenário: [CT-08] a administradora que também governa a instalação some da própria listagem
      Dado a Acme com a fixture
      E Ana recebe também o papel admin no contexto global
      Quando Ana abre a listagem de usuários da Acme
      Então a listagem não contém Ana
      E contém Beto, Leo, Rui, Nina e Nino
```

> **CT-02: 404 e não 403** é o requisito ("não pode NUNCA ver"): 403 diz "existe e você não pode". O
> terceiro `Exemplo` prova que o 200 é **a ficha de Beto** (achado 7), não qualquer 200.
>
> **CT-07 conta 6 e o número vem da tabela da fixture** (achado 6) — a primeira versão dizia 4 por
> misturar o conjunto de CT-05 com o da listagem; uma implementação correta reprovaria e o
> executor "corrigiria" o literal. Leo, Rui e Nina estão na conta pela premissa; se ela for negada,
> a tabela muda e o 6 muda com ela.
>
> **CT-08** é a consequência declarada em ADR-01 (achado 18): a regra não tem exceção para quem
> executa. Uma implementação com `orWhere('id', auth()->id())` passaria em todo o resto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | Filtra só pelo **nome** `master_global` — `admin` e `infra` continuam visíveis | CT-01 (Ada, Ivo ausentes) |
| M2 | Filtra qualquer papel de painel ≠ `app` **sem** olhar o contexto — Leo e Rui somem | CT-01 (Leo, Rui presentes), CT-03 (Rui) |
| M3 | Consulta a relação `roles()` do spatie, filtrada pelo team **corrente** — o `master_global` de Gil está no global e não é visto | CT-01 (Gil ausente), CT-03 (Gil) |
| M4 | O recorte é aplicado na `table()` e não em `getEloquentQuery()` — a listagem esconde, a URL direta abre | CT-02 (Gil, Aldo); CT-03; CT-07 |
| M5 | Coluna de team sem qualificar no `whereDoesntHave` — filtro inerte ou SQL inválido | CT-01, CT-05 |
| M13 | "Governa = **qualquer** papel no contexto global", sem olhar `painel` — Nina some | CT-01 (Nina presente), CT-07 — **revisão adversarial, achado 1** |
| M14 | Nomes **hardcoded** `['master_global', 'admin', 'infra']` — o papel `auditor` (`painel = infra`) vaza | CT-01 (Aldo ausente), CT-02 (Aldo) — **achado 2** |
| M15 | Contexto = "≠ organização corrente" em vez de "= global" — Rui (admin na Globex) some da Acme | CT-01 (Rui presente), CT-03 (Rui) — **achado 3** |
| M16 | Exceção para papel misto ("só governa se não tiver papel `app` na org") ou para a própria executora | CT-01 (Gil ausente: misto), CT-08 (Ana ausente) — **achado 4** |
| M17 | `painel != 'app'` sem `whereNull` — `NULL != 'app'` não é verdadeiro em SQL, e o `master_global` (painel nulo) passa | CT-01 (Gil ausente) — achado 19 (nulo) |
| M2′ | Condição invertida (`whereHas` no lugar de `whereDoesntHave`) — esconde os comuns | CT-01 (os seis presentes), CT-05 |

---

## Regra R2 — a edição de quem governa a instalação é recusada por qualquer caminho, sem mudar a conta, com registro

> `RQ-03` · perfil **completo** · técnica: **autorização exercida fora do caminho feliz** + **oráculo
> no dado** (o hash da senha) + **rastreio de efeito** (o `warning`)

A tela é protegida por R1 (404 antes de qualquer edição). Esta regra é sobre os caminhos que **não
passam pela listagem**: a resposta de autorização chamada direto (o que `EditRecord::mount()` e a
`EditAction` lêem), e o componente de edição **montado com o alvo** — que é o bypass mais fácil de
escrever (uma action nova que recebe um `User` de fora da tabela).

```gherkin
# language: pt

  Regra: a edição de quem governa a instalação é recusada em qualquer caminho, sem mudar a conta, e fica registrada

    Esquema do Cenário: [CT-04] a resposta de autorização de edição decide pelo alvo, e só o alvo que governa gera registro
      Dado Ana autenticada no painel da Acme
      E o alvo "<pessoa>" da fixture
      Quando o resource é perguntado se Ana pode editar o alvo, sem passar pela listagem
      Então a resposta é "<resposta>"
      E "<registro>"

      Exemplos:
        | pessoa | resposta  | registro                                                                              | # partição             |
        | Gil    | negada    | um warning no canal autenticacao, com alvo_id = id de Gil e executor_id = id de Ana   | misto                  |
        | Ada    | negada    | um warning no canal autenticacao, com alvo_id = id de Ada e executor_id = id de Ana   | não master             |
        | Aldo   | negada    | um warning no canal autenticacao, com alvo_id = id de Aldo                            | papel novo             |
        | Leo    | permitida | nenhum warning no canal autenticacao                                                  | @premissa esta org     |
        | Nina   | permitida | nenhum warning no canal autenticacao                                                  | app × global           |
        | Beto   | permitida | nenhum warning no canal autenticacao                                                  | controle               |

    Esquema do Cenário: [CT-10] montar a tela de edição com o alvo em mãos não altera a conta de quem governa
      Dado Ana autenticada no painel da Acme, e o hash da senha de "<pessoa>" anotado
      Quando o componente de edição é montado diretamente com "<pessoa>" como registro e recebe um pedido de gravação com senha nova
      Então o resultado é "<resultado>"
      E o hash da senha de "<pessoa>" no banco é o anotado, inalterado
      E o e-mail de "<pessoa>" no banco é o original

      Exemplos:
        | pessoa | resultado                          | # partição                    |
        | Gil    | 404 ou 403 — a gravação não ocorre | governa: o bypass é barrado   |
        | Ada    | 404 ou 403 — a gravação não ocorre | governa, não master           |
```

> **O oráculo de CT-10 é o dado** (achado 16): o defeito que motivou o `00` é a senha editável, e
> "recusado" sem comparar o hash passaria numa implementação que recusa **depois** de gravar. O
> `resultado` aceita 404 (a query recortou no route binding) **ou** 403 (a resposta de autorização
> negou) — as duas camadas são legítimas; o que não pode é 200 com gravação.
>
> **Chaves nomeadas no `Log::spy()`** (achado 8): `Mockery::on(fn ($ctx) => $ctx['alvo_id'] === $gil->id && $ctx['executor_id'] === $ana->id)`, e `Log::shouldHaveReceived('channel')->with('autenticacao')` — ids trocados ou canal default reprovam. Nas linhas permitidas, `shouldNotHaveReceived('warning')` sobre o canal.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | Nenhuma segunda camada: só a query esconde. A resposta para Gil é `allowed()` | CT-04 (Gil, Ada, Aldo) |
| M7 | A barreira é escrita em `canEdit()` e não em `getEditAuthorizationResponse()` — quem lê a resposta continua permitido | CT-04 (a resposta é chamada direto); CT-10 se a `EditRecord` ler a resposta |
| M8 | A resposta nega usando só `isMasterGlobal()` | CT-04 (Ada, Aldo) |
| M9 | O `warning` não é escrito, é escrito para todo alvo, ou vai para o canal default | CT-04 (coluna `registro`, nas duas direções) |
| M18 | A resposta nega, mas depois de o formulário ter gravado (ordem errada numa action custom) | CT-10 (hash inalterado) — **achado 16** |

---

## Regra R3 — o predicado e o recorte dizem a mesma coisa sobre o mesmo conjunto

> `RQ-02` + `RQ-03` · perfil **completo** · técnica: **propriedade** (duas formas, um resultado)

```gherkin
# language: pt

  Regra: a pergunta "governa a instalação?" tem uma resposta só, em query e em predicado

    Cenário: [CT-05] o recorte da query e o predicado por pessoa concordam em toda a fixture
      Dado as onze pessoas da fixture
      Quando o mantenedor avalia o predicado em cada uma das onze, e o recorte sobre todos os usuários
      Então o conjunto dos que o predicado nega é igual ao conjunto que o recorte devolve
      E esse conjunto é exatamente Ana, Beto, Leo, Rui, Nina, Nino e Bruno
```

> O predicado é avaliado **sobre as onze pessoas** (`User::all()`), não sobre a saída do scope —
> senão o primeiro `Então` é circular (achado 9). O segundo `Então` impede as duas formas de estarem
> erradas juntas. Bruno entra porque o predicado não sabe de organização; quem o tira da listagem é
> o `whereHas('tenants')` de R4.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | O predicado e o scope têm condições **diferentes** (um esquece o contexto) — divergem em Leo/Rui | CT-05 (primeiro `Então`) |
| M11 | As duas formas concordam e estão erradas juntas (ambas sem contexto, ou ambas por nome) | CT-05 (segundo `Então`: Leo, Rui, Nina e Aldo decidem) |

---

## Regra R4 — o `admin_app` continua vendo, criando e gravando os usuários comuns da sua organização

> `RQ-01`, `RQ-04` · perfil **mínimo** · técnica: **gate de tela de escrita**, nos dois verbos
> ("criar **e** editar" — verbo irmão não herda evidência, achado 14)

```gherkin
# language: pt

  Regra: o recorte novo não tira do admin_app quem ele administra

    Cenário: [CT-06] a admin_app edita um usuário comum da sua organização e a gravação acontece
      Dado Ana, admin_app da Acme, e Beto, panel_user da Acme
      Quando Ana salva a ficha de Beto com o nome "Beto Silva"
      Então o nome de Beto no banco é "Beto Silva"
      E Beto continua vinculado à Acme com o papel panel_user

    Cenário: [CT-09] a admin_app cria um usuário comum e ele aparece na listagem
      Dado Ana, admin_app da Acme
      Quando Ana cria "Dora" com papel panel_user pela tela da Acme
      Então Dora existe no banco, vinculada à Acme, com o papel panel_user no contexto da Acme
      E Dora aparece na listagem de usuários da Acme
```

> Regressão herdada, **rodada e não reescrita**: `lista apenas os usuarios da organizacao corrente`,
> `nega o acesso direto ao registro de usuario de outra organizacao`, `grava o papel no contexto da
> organizacao`, `vincula o usuario criado a organizacao corrente` (`AdminDaOrganizacaoTest.php`) e
> `EscopoFailClosedTest.php`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | A resposta de autorização nega **todo mundo** (condição faltando ou invertida) | CT-06 (a gravação acontece), CT-04 (Leo, Nina, Beto permitidos) |
| M19 | O recorte quebra a criação — o usuário novo não aparece (recorte errado esconde quem acabou de nascer sem papel, ou o `afterCreate` deixa de vincular) | CT-09 — **achado 14** |

---

## Regra R5 — a regra é do `/app`: fora dele ninguém some

> `RQ-02` ("dentro do painel app") · **completo** por escalada · técnica: **regressão fora do painel**

Origem: revisão adversarial, achado 5. Um `addGlobalScope` no `User` — a forma mais "elegante" de
"sumir inteiramente" — passaria em todos os cenários acima e apagaria o `master_global` do `/admin`,
do `UsersRelationManager` e do guard de autenticação.

```gherkin
# language: pt

  Regra: o recorte só existe onde o painel da organização o invoca

    Cenário: [CT-11] fora do painel da organização, quem governa a instalação continua existindo
      Dado as onze pessoas da fixture
      Quando o mantenedor conta os usuários sem painel corrente, e lista os usuários pelo resource do /admin
      Então a contagem é 11
      E a listagem do /admin contém Gil, Ada, Ivo e Aldo
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | O recorte vira global scope do `User` | CT-11 (os dois `Então`) |
| M21 | O recorte é posto no `Admin/UserResource` também, por simetria | CT-11 (segundo `Então`) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **CT-02** (URL direta de Gil e Aldo → 404) e a herdada de outra organização (`AdminDaOrganizacaoTest:113`) |
| Autorização exercida na ação (não só `can()`) | **CT-04** (resposta chamada direto) e **CT-10** (componente montado com o alvo) |
| Idempotência | não se aplica: sem operação de escrita nova |
| Concorrência | não se aplica |
| Fronteira no ponto de entrada (gravação) | **CT-09** (criar) e **CT-06** (editar) para o caminho permitido; a trava de papel na gravação já existe (`gravarPapeis()`) |
| **Domínio condicionado** (tipo × valor) | **CT-01** — o mesmo papel `admin` em três contextos (global / Acme / Globex) com destinos distintos: Ada some, Leo e Rui ficam |
| Estado × operação de escrita | não se aplica |
| **Ausente ≠ null ≠ vazio** | **CT-01**: `painel` **nulo** é Gil (`NULL != 'app'` não é verdadeiro em SQL — M17); **sem papel algum** é Nino, que aparece; **ausente** da organização é Bruno |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | não se aplica |
| Unicidade + soft delete | não se aplica: tratado por `status-e-exclusao-logica-de-usuario` |
| CRUD combinado | **CT-02** (editar id de quem não se pode ver → 404); **CT-09** (criar); **CT-06** (editar) |
| Mass assignment | não se aplica |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Persona colapsada** | executora e alvos são pessoas distintas em todo cenário; **CT-08** é o único em que a executora é alvo — de propósito |
| **Papel misto** (linha nova, achado 4) | **CT-01/CT-04** (Gil é `master_global` **e** `panel_user`); **CT-08** (Ana é `admin_app` **e** `admin`) |
| **Célula `app` × global** (linha nova, achado 1) | **CT-01/CT-04** (Nina) |
| **Regressão fora do painel** | **CT-11** |

## Fora do alcance

| Afirmação | Por que | Quem verifica |
|---|---|---|
| Uma página `view` do resource, se um dia existir, herda o 404 | hoje não há `view` (`getPages()`: index, create, edit); CT-02 cobre a única rota com `{record}` | quem criar a página: acrescentar linha em CT-02 |
| O overlay ⌘K não **exibe** quem governa | CT-03 prova que o **dado** da busca não os devolve; o overlay é JS, coberto pelo F-45 | — |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | listagem: os seis presentes, os cinco ausentes, na mesma carga | R1 | matriz papel × contexto | Livewire (`ListUsers` + `loadTable()`) | `tests/Tenancy/FronteiraDoAdminAppTest.php` | M1, M2, M3, M5, M13–M17, M2′ |
| CT-02 | URL direta: 404 (Gil, Aldo) / ficha de Beto | R1 | IDOR | HTTP | idem | M4, M14 |
| CT-03 | busca global por termo | R1 | rastreio por superfície | Feature (estático sob `noPainelDa`) | idem | M3, M4, M15 |
| CT-07 | badge = 6 | R1 | rastreio por superfície | Feature | idem | M4, M13 |
| CT-08 | a executora que governa some de si mesma | R1 | papel misto na executora | Livewire | idem | M16 |
| CT-04 | resposta de edição por alvo, com/sem warning nomeado | R2 | autorização fora do caminho feliz + log | Feature | idem | M6, M7, M8, M9, M12 |
| CT-10 | componente montado com o alvo: hash e e-mail inalterados | R2 | oráculo no dado | Livewire (`EditUser`) | idem | M7, M18 |
| CT-05 | predicado ≡ recorte, conjunto exato de 7 | R3 | propriedade | Feature | idem | M5, M10, M11, M2′ |
| CT-06 | admin_app edita usuário comum | R4 | gate de escrita (editar) | Livewire (`EditUser` → `save`) | idem | M12 |
| CT-09 | admin_app cria usuário comum e ele aparece | R4 | gate de escrita (criar) | Livewire (`CreateUser` → `create`) | idem | M19 |
| CT-11 | fora do painel ninguém some; `/admin` lista os quatro | R5 | regressão fora do painel | Feature | idem | M20, M21 |

## Sem CT-B

- **Motivo**: toda afirmação é conteúdo de query, resposta HTTP, resposta de autorização ou registro
  no banco. A busca ⌘K é JavaScript, mas o **dado** dela sai de `getEloquentQuery()` (CT-03); o
  overlay é o F-45.

## Divergência entre skill e rule do projeto

- A skill sugere `pest --parallel --tia`; `.ai/rules/testes-browser.md` mede que sem PCOV o `--tia`
  não termina. **A rule vence**: `vendor/bin/pest --no-tia tests/Tenancy/FronteiraDoAdminAppTest.php`.

## Revisão Adversarial

Sub-agente independente, entrada `00` + `04` (primeira versão), sem PRD nem código. **19 achados**,
todos fechados nesta versão:

| # | Achado | O que virou |
|---|---|---|
| 1 | "governa = qualquer papel no global" passava — faltava a célula `app` × global | Nina; **M13** |
| 2 | nomes hardcoded passavam — a fixture era exatamente o trio | Aldo, papel `auditor` criado no teste; **M14** |
| 3 | contexto "≠ corrente" passava — faltava `≠app` × **outra** org | Rui; **M15** |
| 4 | exceção para papel misto ou para si mesma passava — todo alvo tinha um papel só | Gil misto; **CT-08**; **M16** |
| 5 | global scope no `User` passava — nenhum cenário saía do `/app` | **R5 / CT-11**; **M20, M21** |
| 6 | badge "4" era aritmética errada (era o conjunto de CT-05) | tabela da fixture com o número derivado; CT-07 = **6** |
| 7 | CT-02 "200" só | terceiro `Exemplo` exige o e-mail de Beto no formulário |
| 8 | spy de log aceitava ids trocados e canal default | chaves nomeadas + `channel('autenticacao')` explícito |
| 9 | CT-05 circular se o predicado rodasse sobre a saída do scope | "avalia o predicado em cada uma das onze" |
| 10 | CT-01 só ausência | presença dos seis na **mesma carga** |
| 11 | CT-02 com dois `Quando` | `Esquema` por pessoa |
| 12 | CT-03 com três `Quando` | CT-03 (busca, `Esquema`) e CT-07 (badge) separados |
| 13 | CT-05 com o teste como ator | reescrito; aceito que "propriedade" tem o mantenedor como ator |
| 14 | RQ-01 "cria" sem cenário | **CT-09** |
| 15 | RQ-03 "por caminho que contorne" só por método | **CT-10** monta o componente com o alvo |
| 16 | senha sem oráculo | CT-10 compara o **hash** |
| 17 | rota `view` | `## Fora do alcance`: não existe; linha obrigatória se nascer |
| 18 | auto-ocultação (ADR-01) sem cenário | **CT-08** |
| 19 | "vazio" (sem papel) não coberto | Nino |

**Sem segunda rodada**: os cenários novos (CT-07…CT-11) reforçam regras existentes ou criam R5, cuja
superfície é uma contagem e uma listagem — não introduzem partição nova a revisar. Registrado como
decisão; se a implementação revelar lacuna, a segunda rodada roda antes do merge.
