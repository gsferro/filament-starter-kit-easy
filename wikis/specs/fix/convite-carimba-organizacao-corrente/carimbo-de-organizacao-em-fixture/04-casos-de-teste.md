# Casos de Teste — O carimbo de organização do Filament sobre fixture de teste

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**. Nenhum cenário foi escrito a partir do comportamento desejado do
> plano — ver `## Fronteira com o Plano`.

> **Desvio de higiene de contexto, declarado**: no momento da derivação o passo 1 do plano já
> estava aplicado no working tree (`tests/Pest.php` modificado, não commitado), e o helper foi lido
> ao levantar a convenção do arnês (assinatura, helpers vizinhos, ligações do `tests/Pest.php`).
> Os oráculos abaixo vêm das **medições tabeladas no `00`** e das duas ADRs; nenhum `Então` foi
> extraído da implementação. Onde a implementação e o requisito coincidem, a fonte citada é o `00`.

## Natureza da entrega — e o que isso faz com o gate de mutação

**Nenhuma linha de `app/` muda.** O que muda é `tests/Pest.php`, um arquivo de
`tests/Tenancy/` e `.ai/rules/testes.md`. Consequência operacional, que vale registrar antes de
qualquer contagem:

- **`pest --mutate` é inaplicável a esta entrega.** Ele muta o código de produção sob `app/`, e
  aqui o código sob teste é o **arnês** mais um `creating` de **vendor**. Não existe alvo. O
  fechamento do ciclo (`## Fechamento do Ciclo`) descreve o que substitui a medição.
- Todos os mutantes deste arquivo são, portanto, **mutantes de especificação**: implementações
  erradas plausíveis do helper e do vendor, auditadas por revisão adversarial e não por ferramenta.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — a garantia da organização em `ofertaPara()` (RQ-02) | 3 | 3 | **9** | completo |
| A2 — a guarda do comportamento do vendor (RQ-04, risco 1 do plano) | 2 | 2 | 4 | padrão |
| A3 — a documentação: rule + wiki (RQ-01, RQ-04) | 1 | 1 | 1 | mínimo |
| A4 — a remoção do contorno e a regressão dos consumidores (RQ-03) | 2 | 2 | 4 | padrão |

**Justificativa de A1 = 9**, que é o número que decide o esforço do arquivo inteiro:

- **P = 3** — a regra depende de **duas condições de vendor** que ligam e desligam o carimbo
  (painel bootado; organização corrente), e a correção é **condicional** sobre o resultado delas.
  Três condições compondo, atravessando código de terceiro.
- **I = 3** — `ofertaPara()` é a fonte de fixture de **51 chamadas** em 11 arquivos (varredura em
  `tests/`), e várias delas são o **arranjo de casos de fronteira entre organizações**. Helper que
  entrega a organização errada não produz erro cosmético: produz **evidência falsa de isolamento
  de tenancy**. É a mesma classe de impacto de "autorização" da tabela do perfil.

- Técnicas aplicadas: **EP** (partição do "pedido" e das condições do carimbo), **tabela de
  decisão** (painel corrente × painel bootado × organização corrente), **rastreio de efeito**
  (a escrita de correção acontece / não acontece / acontece uma só vez).
- Cenários: **5** · Regras: **4** · Mutantes previstos: **19** · Sem matador: **1** (M15, declarado em R4).
- Revisão adversarial: **1 rodada**, 6 achados estruturais — todos fechados. Ver `## Revisão Adversarial`.
- **Sem `05-casos-de-teste-browser.md`** — ver `## Sem CT-B`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | `tests/Pest.php` (`ofertaPara()`), `tests/Tenancy/CarimboDeOrganizacaoTest.php` (novo), `tests/Tenancy/AbasDeListagemTenancyTest.php` (CT-12 perde o contorno), `.ai/rules/testes.md`. **Zero artefatos de `app/`** — sem model, migration, action, job, policy, resource ou command. O artefato **medido** é de vendor: `BelongsToTenant::observeTenancyModelCreation()` | CT-01…CT-04 |
| **F** | três funções: (1) garantir a organização pedida na fixture; (2) **medir** o carimbo do vendor sem passar pelo helper; (3) documentar a armadilha. A função escondida é a (2): ela é o que impede a (1) de virar no-op | CT-01, CT-02, CT-03 |
| **D** | `convites.tenant_id` — FK anulável. Partições do **pedido**: por parâmetro `$tenant`, por `atributos['tenant_id']`, **nenhum** (`null`). Partições do **gravado**: organização pedida, organização corrente, `null`. Dado pré-existente: duas organizações (Acme, Globex) e o papel `panel_user` | CT-01, CT-03 |
| **I** | **uma só**: chamada de função PHP dentro do arnês de teste. Sem rota, sem comando artisan, sem job, sem webhook, sem import. É por isso que a superfície de UI é vazia | — |
| **P** | Filament **v5.7.6**, Laravel 13.26.1, Livewire 4.4.0, Pest 5.1.1, SQLite `:memory:`. O listener é registrado em `Panel::boot()` e vive no dispatcher de eventos do model — que o `refreshApplication()` do Laravel recria **por caso**, e é por isso que o `00` mede **0 listeners** sem o boot. Consequência: o estado "bootado" não vaza entre casos, mas **vaza dentro de um mesmo caso** e é herdado de um request HTTP anterior no mesmo caso. `tests/Unit` **não** tem `pest()->extend(TestCase::class)` — não há container lá, o que fecha a camada `Unit` para todos estes cenários | CT-03 |
| **O** | usuário real = **quem escreve teste de fronteira** (agente ou mantenedor). Uso indevido esperado: passar `$tenant` e supor que veio, sem conferir — foi o que custou uma hora de investigação. Volume: 51 chamadas em 11 arquivos — 19 em `tests/Kit` (sem organização) e 32 em `tests/Tenancy` (com) | CT-01, CT-04 |
| **T** | **ordem, sim; relógio, não.** A ordem que decide tudo: os atributos são preenchidos → o `creating` do vendor sobrescreve → o insert grava → a correção do helper roda **depois**. Nenhum campo temporal, nenhuma expiração, nenhum fuso, nenhuma concorrência (arnês single-process). *Não se aplica: timezone, DST, agendamento, timeout* | CT-01 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — `ofertaPara()` entrega, **no banco e no modelo devolvido**, a organização que foi pedida — e não muda mais nada | A1 (completo) | RQ-02 | EP do pedido × tabela de decisão das condições do carimbo; cardinalidade do efeito | CT-01, CT-05 |
| **R2** — a garantia é **condicional**: quando o gravado já é o pedido, o helper não escreve nada | A1 (completo) | RQ-02 + ADR-02 | rastreio de efeito, 3 direções | CT-02 |
| **R3** — o carimbo do vendor é afirmado **diretamente**, e só ocorre com o painel do Resource bootado **e** organização corrente | A2 (padrão) | RQ-04 (núcleo factual) | tabela de decisão, 4 regras | CT-03 |
| **R4** — a fronteira entre organizações na listagem é provada **sem** corrigir a coluna no banco dentro do caso | A4 (padrão) | RQ-03 | regressão do consumidor | CT-04 |

**Técnica escalada acima do perfil da área**: R3 está em área `padrão` (que não prevê tabela de
decisão completa) e usa **tabela de decisão de 4 regras**. Motivo: as duas guardas do vendor
(`getCurrentPanel() !== $panel` e `if (! $tenant)`) são exatamente o que separa "trava fail-safe"
de "furo", e o `00` afirma as duas como fato — cobrir só a célula que carimba deixaria o `/admin`
sem nenhum cenário, que é a metade do RQ-04 que **é** falsificável.

### RQ sem regra — e por quê

| RQ | Virou regra? | Justificativa |
|---|---|---|
| RQ-01 — o comportamento fica documentado onde se tropeça | **não** | Ver `## RQ-01 e RQ-04: testável ou asserção de documentação?` |
| RQ-04 — fica registrado que é do vendor e fail-safe | **parcialmente → R3** | o **núcleo factual** ("é do vendor", "o `/admin` não é afetado", "é fail-safe") é falsificável e virou R3. O **registro** dele em prosa não é |

## RQ-01 e RQ-04: testável ou asserção de documentação?

Pedido explicitamente na derivação. Resposta honesta, separando as duas metades:

| Metade da cláusula | Veredito | Onde fica |
|---|---|---|
| RQ-04 — **o fato**: o carimbo é do vendor, só ocorre com painel bootado + organização corrente, e o `/admin` não é atingido | **testável** | **R3 / CT-03**. É o cenário que fica vermelho no dia em que o Filament mudar |
| RQ-04 — **o registro**: "para ninguém 'corrigir' a trava achando que é defeito" | **asserção de documentação** | item do `03-progresso.md` + `## Roteiro de Validação` deste arquivo |
| RQ-01 — **onde** documentar (`.ai/rules/testes.md` + wiki) | **asserção de documentação** | idem |

**Por que o registro não vira CT.** Um caso que afirmasse a presença de uma frase em
`.ai/rules/testes.md`:

1. **Fixa redação, não comportamento.** Qualquer reescrita da rule — inclusive uma melhor — fica
   vermelha sem defeito nenhum. É o anti-padrão "cenário que precisa mudar quando a implementação
   muda", aplicado a prosa.
2. **Não prova o que a cláusula quer.** RQ-01 quer que a armadilha seja encontrada por quem
   escreve teste de fronteira. Um `assertStringContainsString` prova que o byte está no arquivo,
   não que alguém o leu. O oráculo verdadeiro dessa cláusula é social, e não tem representação
   executável honesta.
3. **Colide com uma rule do próprio projeto.** `.ai/rules/testes.md` §"Asserção de ausência sobre
   arquivo documentado precisa filtrar comentário" registra três reprovações causadas por testes
   que afirmaram sobre o **texto** de arquivos documentados. Acrescentar um quarto para provar
   documentação seria repetir o padrão que a rule existe para conter.

**O precedente que foi considerado e recusado.** `tests/Kit/HelpersDeTesteTest.php` enforça uma
rule por varredura — mas ele varre **forma de código** (`token_get_all()` sobre chamadas de
função), não prosa. A distinção que sobrevive: *rule sobre código, enforço por varredura; rule
sobre julgamento humano, enforço por revisão*.

**Veredito**: RQ-01 e o registro do RQ-04 são **asserção de documentação**, verificados no
`## Roteiro de Validação` e no `03-progresso.md`. O **fato** do RQ-04 é CT-03, e é ele que dá
dentes à rule — que é exatamente o que o plano quis dizer com "a rule virar prosa que ninguém lê:
mitigado por ela ser curta e pelo caso do passo 4 ser o enforço".

## Fronteira com o Plano

| Item do `01` / `02` | Recusado como oráculo porque | Destino |
|---|---|---|
| "corrigir a coluna e `refresh()`" (passo 1) | **como** a garantia é obtida é escolha de implementação — `DB::table()->update()`, `forceFill()->save()` ou um `saveQuietly()` atendem igual | detalhe do cenário. O `Então` de CT-01 afirma **o valor gravado e o valor no modelo devolvido**, não o mecanismo |
| "`ofertaPara()` ganha o comportamento, sem helper novo" (RQ-02, assumido no `00`) | decisão de organização de código, não comportamento observável | detalhe. Nenhum `Então` cita o nome do helper como oráculo |
| "docblock curto com `vendor:linha`" (passo 1) | conteúdo de comentário | não testável, item do `03-progresso.md` |
| "`tests/Tenancy/` (arquivo a decidir)" (passo 4) | path | **usado como path**, que é para isso que o plano serve: `tests/Tenancy/CarimboDeOrganizacaoTest.php` |
| "os quatro consumidores continuam verdes" (impacto) | **número desatualizado** — a varredura mede **11 arquivos e 51 chamadas** | corrigido no perfil de risco (I=3 de A1); registrado como achado |
| "**a correção é condicional**" (passo 1 + ADR-02) | **NÃO recusado** — é comportamento, não implementação: o requisito quer que o helper *não mascare* a mudança de vendor | **virou R2**, e é a única regra cujo oráculo é um efeito ausente |

**A condicionalidade é o caso-limite desta seção**, e vale explicitar por que ela atravessa a
fronteira: "condicional" não descreve *onde* o código mora nem *como* ele grava — descreve um
**efeito observável a menos** (nenhuma escrita quando não era preciso), e o motivo dele está no
`00` ("para ninguém 'corrigir' a trava") e no risco 1 do plano. É comportamento.

### Perguntas em aberto

Ver `## Perguntas para o 00-requisito.md` no fim deste arquivo — o `00` **não foi editado**,
conforme a instrução da derivação.

## Setup Global

### Suíte

**`tests/Tenancy`**, obrigatoriamente. `Tests\TenancyTestCase` fixa `permission.teams` em
`createApplication()`, **antes** das migrations (`.ai/rules/testes.md`), e sem as colunas de team
nenhum arranjo de organização existe. `tests/Kit` está fechado para estes cenários pela mesma
rule. `tests/Unit` está fechado porque o `tests/Pest.php` **não** liga `TestCase::class` a
`Unit` — não há container lá.

### Personas

Nenhuma persona de autorização é exercitada: o assunto é a fixture, não quem a cria. O usuário
existe só como sujeito do painel corrente.

- `operador` — `papelNaOrganizacao(usuario('admin.acme@example.com'), 'admin_app', $acme)`.
  `admin_app` **só existe em `tests/Tenancy`** (`.ai/rules/testes.md`).

### Fixtures

- `tenant('Acme', 'acme')` e `tenant('Globex', 'globex')` — helpers existentes em `tests/Pest.php`.
  Duas organizações **distintas** e ambas usadas: é o que torna o exemplo discriminante. Um
  cenário com uma só organização não distingue "carimbou a corrente" de "respeitou a pedida".
- `Convite::factory()` **direto**, em CT-03: o cenário que mede o vendor **não pode** passar pelo
  helper, senão mede o helper.
- Papel `panel_user` (default de `ofertaPara()`), que existe nas duas suítes.

### Arranjo do painel — helpers que já existem (nenhum novo)

- `noPainelDa($tenant)` — painel corrente `app` + organização corrente + team de permissões.
  **Não boota.**
- `noPainelBootado('app')` — `setCurrentPanel()` + `bootCurrentPanel()`. É o boot que **registra o
  `creating`** do vendor. Serve aqui porque nenhum destes cenários renderiza página (o
  `AbasDeListagemTenancyTest` precisa de um `GET` real por causa do `BreezyCore`, que exige rota
  corrente — restrição de renderização, não de boot).

Nenhum helper novo é necessário. Se algum vier a ser, `.ai/rules/testes.md` obriga `tests/Pest.php`
a partir do segundo arquivo consumidor, e `tests/Kit/HelpersDeTesteTest.php` enforça.

### Fakes

Nenhum. Sem e-mail, sem fila, sem HTTP, sem evento fingido. **`Event::fake()` é proibido nestes
cenários**: o comportamento sob teste **é** um listener de `eloquent.creating`, e falsificar o
dispatcher apagaria o objeto do teste.

### Estratégia de DB

`RefreshDatabase` global via `tests/Pest.php` (`->in('Tenancy')`), SQLite `:memory:` do
`phpunit.xml`.

### Oráculo de persistência

**Banco cru** (`DB::table('convites')->where('id', …)->value('tenant_id')`) sempre que o cenário
afirmar o que foi gravado — é o oráculo que o `00` usou nas três medições, e o único que não pode
ser satisfeito por um atributo corrigido em memória.

---

## Regra R1 — `ofertaPara()` entrega a organização que foi pedida

> `RQ-02` · área A1 · perfil **completo** · técnicas: **EP** do pedido (parâmetro / `atributos` /
> nenhum) × **tabela de decisão** das condições do carimbo (bootado × organização corrente)

```gherkin
# language: pt

Funcionalidade: Fixture de convite na organização certa

  Regra: ofertaPara() entrega, no banco e no modelo devolvido, a organização que foi pedida

    Esquema do Cenário: [CT-01] o convite nasce na organização pedida, independentemente do carimbo
      Dado que existem as organizações Acme e Globex
      E que o contexto do painel é "<contexto>"
      Quando o autor do teste pede uma oferta <pedido>
      Então o tenant_id gravado na tabela de convites é o da <gravada>
      E o tenant_id do convite devolvido é o da <gravada>
      E a organização corrente e o team de permissões continuam sendo os que eram antes da chamada

      Exemplos:
        | contexto                              | pedido                       | gravada | # partição                              |
        | painel app não bootado                | para a Globex, por parâmetro | Globex  | vendor inativo — o pedido já vale       |
        | painel app bootado, Acme corrente     | para a Globex, por parâmetro | Globex  | vendor ativo e divergente               |
        | painel app bootado, Acme corrente     | para a Acme, por parâmetro   | Acme    | vendor ativo e convergente              |
        | painel app bootado, Acme corrente     | para a Globex, por atributo  | Globex  | pedido pela outra porta                 |
        | painel app bootado, sem organização   | para a Globex, por parâmetro | Globex  | 3º estado do discriminador              |
        | painel app bootado, Acme corrente     | sem organização nenhuma      | Acme    | @premissa — nada pedido, nada garantido |

    Cenário: [CT-05] duas ofertas para o mesmo e-mail guardam cada uma a sua organização, e os demais argumentos sobrevivem
      Dado que existem as organizações Acme e Globex, e o painel app está bootado com a Acme corrente
      Quando o autor do teste pede duas ofertas para o mesmo e-mail — uma para a Acme, com o papel admin_app e aceito_em preenchido, e outra para a Globex
      Então existem exatamente dois convites para aquele e-mail
      E a oferta da Acme tem o tenant_id da Acme, o papel admin_app e o aceito_em preenchido
      E a oferta da Globex tem o tenant_id da Globex
```

**Por que estes valores discriminam** (o gate do exemplo discriminante):

- **Duas organizações distintas, e a pedida nunca é a corrente** nas linhas 2 e 4. Com uma só
  organização, ou com `pedida == corrente`, toda implementação passa — inclusive a que ignora o
  argumento e grava sempre a corrente.
- **A linha 3 (`pedida == corrente`) é obrigatória** e não é redundante: ela é a única que fica
  vermelha se a garantia for implementada como "grava sempre a **outra** organização" ou como uma
  inversão da condição de divergência (M7).
- **Os dois `Então`** — banco e modelo — separam duas implementações que o `Então` único não
  separa: corrigir só em memória (M2) e corrigir só no banco, devolvendo modelo velho (M3).
- **A linha 5 marca `@premissa`**: o `00` não determina o que acontece quando nenhuma organização é
  pedida. Premissa adotada, derivada de RQ-02 ("criar fixture **de outra organização**"): *a
  garantia é sobre a organização pedida; sem pedido não há garantia, e o carimbo do vendor fica de
  pé*. Ver `## Perguntas para o 00-requisito.md`.
- **A linha 6 é também o item "ausente ≠ null ≠ vazio"** do checklist: `$tenant = null` e
  `atributos['tenant_id'] => null` são, por derivação, o **mesmo** pedido — "nenhum".
- **A linha 5 fecha o terceiro estado do discriminador** — "bootado, mas sem organização corrente".
  Ela existia na tabela de decisão do checklist e **não existia nos `Exemplos`**: lacuna trazida
  pela revisão adversarial (achado A6).

**Quais linhas carregam a regra, e quais são grade de proteção.** Registrado por honestidade, e
porque a revisão adversarial mediu isto: as linhas **2 e 4** são as que ficam vermelhas com o
estado de hoje (helper sem correção). As linhas 1, 3, 5 e 6 passam hoje **de propósito** — elas
não existem para pegar M1, e sim para impedir que a correção **quebre** as partições em que o
vendor já se comportava (1, 5), inverta a condição (3) ou desfaça o carimbo (6). Grade de proteção
é cobertura legítima; o que seria ilegítimo é contá-las como prova da regra, e elas não são.

**O `Então` da invariância** (terceira linha) não estava na primeira derivação e entrou pela
revisão adversarial: sem ele, a implementação mais natural de todas — trocar a organização
corrente por `Filament::setTenant()` em volta do `create()` — passa nas seis linhas mutando estado
global do Filament e o team de permissões do Spatie no meio do caso (M17).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o helper só passa `tenant_id` para a factory e confia no resultado (o estado de hoje, antes da correção) | **CT-01**, linha 2 — grava Acme com Globex pedida |
| M2 | corrige o atributo **em memória** (`$convite->tenant_id = $pedido`) sem persistir | **CT-01**, `Então` do banco |
| M3 | corrige no banco por fora do model e **devolve o modelo sem recarregar** | **CT-01**, `Então` do modelo devolvido |
| M4 | a garantia olha só o parâmetro `$tenant` e ignora `atributos['tenant_id']` | **CT-01**, linha 4 |
| M5 | a correção grava `$pedido` **incondicionalmente**, inclusive `null` — desfazendo o carimbo quando nada foi pedido | **CT-01**, linha 6 |
| M17 | *(revisão adversarial)* em vez de corrigir a coluna, o helper **troca a organização corrente** (`Filament::setTenant()`) em volta do `create()` e restaura depois, sem `finally` | **CT-01**, `Então` da invariância |
| M18 | *(revisão adversarial)* a correção alcança a linha por `where('email', …)` em vez da linha criada | **CT-05** — reescreveria o `tenant_id` da primeira oferta |
| M19 | *(revisão adversarial)* quando `$tenant` é dado, o helper **substitui** `$atributos` em vez de mesclar, perdendo papel e demais campos | **CT-05** — o papel `admin_app` e o `aceito_em` não sobrevivem |

> **Estouro do teto de mutantes por regra, declarado**: R1 tem 8 mutantes contra o teto de 6 do
> perfil completo. Os três excedentes (M17, M18, M19) vieram da **revisão adversarial** e, pela
> regra da skill, achado medido não conta para o teto. Desdobrar R1 em duas regras só para caber
> renumeraria toda a rastreabilidade por motivo cosmético.

---

## Regra R2 — a garantia é condicional: sem divergência, nenhuma escrita

> `RQ-02` + **ADR-02** ("correção incondicional mascararia o comportamento; por isso ela é
> **condicional** — só age quando o gravado divergiu do pedido") · área A1 · perfil **completo** ·
> técnica: **rastreio de efeito**, nas três direções (aconteceu / não aconteceu / uma só vez)

```gherkin
# language: pt

Funcionalidade: Fixture de convite na organização certa

  Regra: a correção do helper só age quando o gravado divergiu do pedido

    Esquema do Cenário: [CT-02] a escrita de correção acontece exatamente quando o carimbo divergiu
      Dado que existem as organizações Acme e Globex
      E que o contexto do painel é "<contexto>"
      Quando o autor do teste pede uma oferta <pedido>
      Então <quantidade> escrita de correção é feita sobre a linha do convite depois do insert
      E o tenant_id gravado é o da <gravada>

      Exemplos:
        | contexto                          | pedido         | quantidade   | gravada | # direção do rastreio                     |
        | painel app não bootado            | para a Globex  | nenhuma      | Globex  | não aconteceu — o vendor nem estava ativo |
        | painel app bootado, Acme corrente | para a Acme    | nenhuma      | Acme    | não aconteceu — vendor ativo, convergente |
        | painel app bootado, Acme corrente | para a Globex  | exatamente 1 | Globex  | aconteceu, e uma só vez                   |
```

**Por que este oráculo, e por que ele é legítimo.** Condicional e incondicional produzem **o mesmo
`tenant_id`** em todas as cinco linhas do CT-01 — o valor não separa as duas. Foram considerados e
descartados três oráculos de valor: `updated_at` (a escrita direta na tabela não toca timestamp),
trilha de auditoria (a escrita por fora do model não dispara evento) e violação de FK com
organização inexistente (contrived, e o SQLite do arnês não a garante). **O único observável que
separa as duas implementações é a escrita em si** — daí o rastreio de efeito, com o log de
consultas como instrumento. Trata-se de um efeito colateral do arnês, e efeito colateral é oráculo
de primeira classe nesta skill.

**As três linhas são as três direções do rastreio**, e a do meio é a única que falsifica a
condicionalidade — foi a correção mais importante trazida pela revisão adversarial (achado A2):

- **Linha 2 (não aconteceu, com o vendor ATIVO e convergente)** é a que mata M6. Ela é a única
  partição em que condicional e incondicional **divergem de fato**: o vendor carimbou a Acme, a
  Acme foi pedida, não há o que corrigir — e a implementação incondicional escreve mesmo assim.
- **Linha 1 (não aconteceu, vendor inativo)** não distingue nada por si só: sem listener, nenhuma
  implementação plausível escreve. Ela permanece como **grade de proteção** da partição que o
  risco 1 do plano antecipa — no dia em que o Filament passar a respeitar a coluna, todas as 51
  chamadas caem aqui, e é bom haver um cenário afirmando o que acontece.
- **Linha 3 (aconteceu, e uma só vez)** impede o falso ✅ clássico desta técnica: sem ela, as duas
  primeiras são satisfeitas por *"nunca escreve nada"*. E o `exatamente 1` fecha a terceira
  direção — uma correção aplicada duas vezes (uma no helper, outra num `saved`) passaria por um
  oráculo de "pelo menos uma". É também a linha que mata M16.
- **O segundo `Então` (o valor gravado)** amarra as três linhas ao resultado: contar escrita sem
  afirmar valor é contabilidade, não comportamento.

> **Correção de um erro da primeira derivação**, registrada porque ela ilustra o modo de falha que
> esta skill combate. A versão inicial afirmava que o segundo `Então` da linha 1 impedia M1 ("o
> helper não corrige nada") de passar. **Era falso**: sem o painel bootado, quem grava a Globex é a
> própria factory, e M1 passa a linha inteira. Quem mata M1 é **CT-01, linha 2** — e agora também
> **CT-02, linha 3**, pela escrita ausente. Um oráculo que a partição já satisfaz por construção é
> tautológico, e foi preciso um revisor que não derivou o conjunto para enxergar isso.

**Ressalva de forma, deliberada** (achado A3 da revisão adversarial, aceito como **não-defeito**):
o oráculo é *escrita de correção sobre a linha do convite*, e **não** "uma instrução SQL `UPDATE`".
A distinção importa porque uma implementação escrita sem `if`
(`$convite->forceFill(['tenant_id' => $pedido])->saveQuietly();`) não emite escrita nenhuma quando
o valor já está correto — a checagem de campos sujos do Eloquent a torna **condicional no efeito**,
que é o que a regra exige.

A revisão adversarial classificou isso como falha do oráculo ("o veredito depende do mecanismo de
escrita"). **Discordância registrada, com o motivo**: R2 é sobre o efeito observável, não sobre a
forma do código-fonte, e a própria `## Fronteira com o Plano` recusa o mecanismo como oráculo. Uma
implementação assim **satisfaz** a regra e deve passar. Foi por isso que o oráculo alternativo
sugerido pela revisão — `wasChanged('tenant_id')` falso e `updated_at == created_at` — **não** foi
adotado: ele mediria a forma, reintroduzindo como oráculo exatamente o que a fronteira recusou.
O que a revisão acertou, e foi corrigido, é a **partição**: linha 2.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | a correção roda sempre que uma organização foi pedida, sem comparar com o gravado | **CT-02**, linha 2 — uma escrita onde deveriam ser zero |
| M7 | a condição de divergência está invertida (corrige quando **já** está igual) | **CT-01**, linha 2 — a Globex pedida continua gravada como Acme |
| M8 | a divergência é medida contra a **organização corrente** (`Filament::getTenant()`) em vez do pedido | **CT-01**, linha 2 — não corrige e a Globex pedida continua Acme |
| M9 | a correção é aplicada **duas vezes** (no helper e num `saved`/observer do arranjo) | **CT-02**, linha 3 — `exatamente 1` |
| M16 | *(revisão adversarial)* em vez de corrigir, o helper **silencia o listener** (`Convite::withoutEvents()`), mascarando o vendor de forma incondicional para as 51 chamadas | **CT-02**, linha 3 — sem carimbo não há divergência, e a escrita esperada não acontece |

---

## Regra R3 — o carimbo do vendor, medido diretamente

> `RQ-04` (núcleo factual) · risco 1 do `01` ("um caso que afirma o carimbo do vendor diretamente,
> e que fica vermelho se o Filament mudar") · área A2 · perfil **padrão**, técnica **escalada** para
> tabela de decisão de 4 regras (justificada no Mapa de Regras)

**Este é o cenário que impede a correção do R1 de virar no-op silenciosa.** Restrição de arranjo,
que é parte da regra e não detalhe: o convite de CT-03 é criado **direto pela factory**, nunca por
`ofertaPara()` — passar pelo helper mediria o helper e devolveria verde sobre o vendor mudado.

Condições (as duas guardas do vendor, verbatim do `00`): `Filament::getCurrentPanel() !== $panel`
→ retorna; `if (! $tenant)` → retorna; senão `$relationship->associate($tenant)`.

| # | painel do Resource bootado | painel corrente | organização corrente | ação esperada |
|---|---|---|---|---|
| D1 | não | `app` | Acme | nenhuma — não há listener |
| D2 | sim | `app` | **nenhuma** | nenhuma — guarda `if (! $tenant)` |
| D3 | sim | **`admin`** | Acme | nenhuma — guarda `getCurrentPanel() !== $panel` |
| D4 | sim | `app` | Acme | **carimba a Acme**, descartando o valor passado |

```gherkin
# language: pt

Funcionalidade: O carimbo de organização do Filament sobre a criação de Convite

  Regra: o Filament carimba o tenant_id com a organização corrente apenas com o painel do Resource bootado e uma organização corrente

    Esquema do Cenário: [CT-03] o carimbo do vendor liga e desliga pelas duas guardas
      Dado que existem as organizações Acme e Globex
      E que o painel app <bootado> bootado, o painel corrente é "<corrente>" e a organização corrente é <organizacao>
      E que há <listeners> listener de criação registrado para o Convite
      Quando o autor do teste grava um Convite pela factory pedindo a Globex
      Então o tenant_id gravado na tabela de convites é o da <gravada>

      Exemplos:
        | bootado | corrente | organizacao | listeners | gravada | # guarda exercitada                    |
        | não foi | app      | a Acme      | 0         | Globex  | D1 — sem boot não há listener          |
        | foi     | app      | nenhuma     | 1         | Globex  | D2 — guarda "sem organização corrente" |
        | foi     | admin    | a Acme      | 1         | Globex  | D3 — guarda "outro painel"; é o /admin |
        | foi     | app      | a Acme      | 1         | Acme    | D4 — o carimbo, descartando o pedido   |
```

**As quatro linhas são a regra inteira, e cada uma tem serventia própria:**

- **D4 é a linha que vira vermelha no dia em que o Filament mudar.** É o enforço do risco 1.
- **D1, D2 e D3 são as linhas que provam que a trava é *fail-safe* e não um furo** — a metade
  falsificável do RQ-04. **D3 é o `/admin`**: é ela que sustenta a frase do `00` "o `/admin` não é
  afetado, que é como o convite para qualquer organização continua funcionando a partir da tela de
  administração". Sem D3, a rule diria "é fail-safe" sem nenhum cenário por trás.
- **A tabela é bidirecional de propósito**: três linhas em que o carimbo **não** ocorre e uma em
  que ele ocorre.

**A contagem de listeners é precondição verificada, não enfeite** — e ela entrou pela revisão
adversarial (achado A4), que derrubou uma afirmação da primeira derivação. O texto original dizia
que "o cenário valida o próprio arranjo". **Isso valia só para D4.** Em D1, D2 e D3 o valor
esperado é `Globex`, que é exatamente o que sai de *nenhum listener existir*: um
`noPainelBootado()` que silenciosamente não bootasse — por exemplo, se alguém o trocasse por
`Filament::setCurrentPanel()`, que **não** boota (`.ai/rules/testes.md`) — deixaria D2 e D3 verdes
**pelo motivo errado**, medindo o ambiente em vez da guarda. Com o `E` da contagem, D2 e D3 passam
a afirmar que *havia uma guarda para exercitar*.

O instrumento não é invenção: é a coluna "Listeners em `eloquent.creating: App\Models\Convite`" da
tabela de medições do `00` (0 sem boot, 1 com boot), que a primeira derivação leu e não usou.

**Oráculo no banco cru.** O `associate()` do vendor também acerta o atributo em memória, então o
modelo e o banco concordam nas quatro linhas — mas ler o banco é o que impede um `refresh()` ou
uma correção de helper introduzida no futuro de mudar a resposta por baixo do cenário.

#### Mutantes previstos

Aqui o "código sob teste" é vendor de terceiro. Os mutantes plausíveis são, portanto, as mudanças
que um upgrade do Filament pode trazer — que é exatamente o que o risco 1 do plano pede para vigiar.

| # | Implementação errada plausível (ou mudança de vendor) | Cenário que mata |
|---|---|---|
| M10 | o vendor passa a **respeitar coluna já preenchida** (`if ($record->tenant_id !== null) return;`) | **CT-03, D4** — passa a gravar Globex, e a linha fica vermelha avisando que a correção do R1 virou no-op |
| M11 | o vendor deixa de comparar o painel corrente e carimba também a partir do `/admin` | **CT-03, D3** |
| M12 | o vendor deixa de checar a organização corrente e associa `null` | **CT-03, D2** |

---

## Regra R4 — a fronteira da listagem, provada sem contorno no banco

> `RQ-03` · área A4 · perfil **padrão** · técnica: **regressão do consumidor** (o caso já existe;
> o que muda é o arranjo)

```gherkin
# language: pt

Funcionalidade: Abas de listagem dentro de uma organização

  Regra: a aba "Pendentes" de convites recorta pela organização corrente, com a fixture entregue pronta

    Cenário: [CT-04] a aba de convites da Acme não mostra o convite da Globex
      Dado que o operador da Acme está no painel da Acme, com o painel bootado por um request real
      E que existem uma oferta para a Acme e uma oferta para a Globex, criadas pelo helper de fixture
      Quando o operador abre a aba "Pendentes" da listagem de convites
      Então o tenant_id gravado da oferta da Globex é o da Globex
      E a fonte da listagem contém o e-mail da oferta da Acme e não contém o da Globex
      E a tabela exibe a oferta da Acme e não exibe a da Globex
```

**O que muda em relação ao caso de hoje**: some do arranjo a correção manual da coluna no banco, o
`refresh()` e o bloco de comentário; e **entra um `Então` novo**, o do `tenant_id` gravado da
oferta da Globex — trazido pela revisão adversarial (achado A5), que mostrou que os dois oráculos
originais ficam verdes tanto com o contorno manual no lugar quanto com o helper sem correção
nenhuma. Sem esse primeiro `Então`, CT-04 não prova nada sobre R1: a fixture podia ter chegado à
Globex por qualquer caminho. Os dois oráculos antigos ficam, e a ordem
entre eles é a que já está lá: a **fonte** (`ConviteResource::getEloquentQuery()`) antes da tela,
porque é ela que separa "a aba recorta" de "o Resource recorta" — se só a asserção de tela
sobrevivesse, uma aba que filtrasse por organização por conta própria ficaria verde enquanto o
Resource vazasse.

**Este cenário não é redundante com CT-01, linha 2**, embora as duas afirmem a mesma organização
gravada: CT-01 mede a fixture isolada, com o painel bootado por `noPainelBootado()`; CT-04 mede a
mesma fixture no **contexto real que originou a wiki** — painel bootado por um `GET` de verdade,
dentro do arquivo cujo `beforeEach` tem esse request. É a única prova de que a garantia do helper
sobrevive ao arranjo de produção da suíte.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | a garantia do helper depende de o painel ter sido bootado por `noPainelBootado()` e não sobrevive ao boot vindo de um request HTTP real | **CT-04** |
| M14 | o contorno é removido e a asserção de **fonte** vai junto, deixando só a tela | **CT-04**, `Então` da fonte da listagem — uma aba que recortasse por organização por conta própria passaria sem ele |
| M15 | o contorno manual **permanece** no caso (ou reaparece em outro caso de fronteira) | ⚠️ **sem matador — lacuna declarada.** Ver abaixo |

**Lacuna declarada M15 — o que foi tentado.** Um caso de varredura sobre `tests/` proibindo a
correção manual de `convites.tenant_id` fora de `tests/Pest.php`, no molde do
`tests/Kit/HelpersDeTesteTest.php`. Descartado por dois motivos, nesta ordem:

1. **Colide com uma rule do projeto.** `.ai/rules/testes.md` §"Asserção de ausência sobre arquivo
   documentado precisa filtrar comentário" registra três reprovações exatamente deste tipo — e o
   docblock do helper **cita** o padrão que a varredura proibiria. A varredura teria de filtrar
   comentário por `token_get_all()` para não reprovar pela própria documentação.
2. **É enforço especulativo.** O contorno existia porque não havia alternativa; com o helper
   entregando, ele deixa de ser a saída óbvia. Ponytail: escrever a trava antes da segunda
   ocorrência é construir para um problema que ainda não existe.

**Verificação substituta**: inspeção do diff, item marcado no `03-progresso.md` §2, e o
`## Roteiro de Validação` deste arquivo. **Se o contorno reaparecer uma segunda vez, a varredura
deixa de ser especulativa** e vira linha nova do checklist de taxonomia em `.ai/rules/testes.md`.

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi tentado}`.

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **CT-01, CT-03, CT-04** — a fronteira entre duas organizações é o assunto da feature; a versão dela aqui é "a fixture da Globex existe mesmo dentro do contexto da Acme". *Sem rota e sem `{id}`: não há a variante HTTP do item* |
| Autorização exercida na ação (não só `can()`) | não se aplica: nenhuma ação autorizável é criada ou alterada. O que o `00` chama de trava é uma associação de FK no `creating` do vendor, não uma policy |
| Idempotência (ancorada no agregado) | não se aplica: `ofertaPara()` é **fábrica**, não operação idempotente — duas chamadas com o mesmo e-mail devem produzir dois convites, e `tests/Tenancy/ConviteUsuarioExistenteTest.php:269-272` depende disso |
| **Cardinalidade do efeito** (0 / 1 / N linhas atingidas) | **CT-05** — linha própria, trazida pela revisão adversarial: a dispensa da idempotência acima levantou o fato de duas ofertas partilharem e-mail e **não** o transformou em cenário, deixando passar uma correção que alcança a linha por `where('email', …)` (M18) |
| **Preservação dos demais argumentos** | **CT-05** — papel e `atributos` sobrevivem à garantia da organização (M19) |
| **Invariância de estado global** (o que a chamada não pode mudar) | **CT-01**, `Então` da organização corrente e do team de permissões (M17) |
| Concorrência | não se aplica: arnês single-process, sem contador, saldo ou limite |
| **Fronteira no ponto de entrada** (gravação) | não se aplica: `tenant_id` é FK, não faixa ordenável — não há `borda−1`/`borda`/`borda+1` a derivar |
| **Domínio condicionado** (discriminador × dependente) | **CT-01** — o valor efetivo de `tenant_id` depende do **contexto do painel** (discriminador de 3 estados: não bootado / bootado sem organização / bootado com organização) cruzado com a **organização pedida**. É a razão de o CT-01 ser um esquema e não um cenário simples |
| **Estado × operação de escrita** | não se aplica: o escopo é o instante da **criação**. O ciclo de vida do convite (aceitar, recusar, expirar) é coberto por `ConviteUsuarioExistenteTest` e não é tocado |
| Ausente ≠ null ≠ vazio | **CT-01, linha 5** (`@premissa`) — "nenhuma organização pedida". `$tenant = null` e `atributos['tenant_id'] => null` são derivados como o **mesmo** pedido |
| Paginação / ordenação | não se aplica: nenhuma listagem nova; a listagem de CT-04 é pré-existente e não muda |
| Timezone / DST | não se aplica: nenhum campo temporal participa da regra. O único eixo de tempo é **ordem de execução** (atributos → `creating` → insert → correção), coberto pela existência dos dois `Então` de CT-01 |
| Unicode / limite de varchar | não se aplica: o campo sob regra é FK inteira |
| Unicidade + soft delete | não se aplica: `convites.tenant_id` não é único e `Convite` não participa da regra por soft delete |
| CRUD combinado | não se aplica: o helper só cria |
| Mass assignment | não se aplica ao requisito, **com achado**: `ofertaPara()` espalha `$atributos` sobre o payload da factory sem filtro. É arnês de teste, onde isso é o comportamento desejado (é como os consumidores injetam `aceito_em`, `recusado_em`). Registrado para não voltar como "descoberta" |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Suíte errada / papel inexistente** *(linha própria do projeto — `.ai/rules/testes.md`)* | coberto pelo `## Setup Global` §Suíte: os quatro CT vivem em `tests/Tenancy`, e `admin_app` não existe em `tests/Kit` |
| **Helper cruzado entre arquivos** *(linha própria do projeto)* | coberto por `tests/Kit/HelpersDeTesteTest.php`, que já enforça; nenhum helper novo é introduzido |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | o convite nasce na organização pedida (6 partições) | R1 | EP × tabela de decisão | `Feature` (Tenancy) | `tests/Tenancy/CarimboDeOrganizacaoTest.php` (novo) | M1, M2, M3, M4, M5, M7, M8, M17 |
| CT-02 | a escrita de correção acontece exatamente quando o carimbo divergiu (3 direções) | R2 | rastreio de efeito | `Feature` (Tenancy) | `tests/Tenancy/CarimboDeOrganizacaoTest.php` (novo) | M6, M9, M16 |
| CT-03 | o carimbo do vendor liga e desliga pelas duas guardas (4 células) | R3 | tabela de decisão | `Feature` (Tenancy) | `tests/Tenancy/CarimboDeOrganizacaoTest.php` (novo) | M10, M11, M12 |
| CT-04 | a aba da Acme não mostra o convite da Globex, sem contorno | R4 | regressão do consumidor | componente Livewire | `tests/Tenancy/AbasDeListagemTenancyTest.php` (**existente**, `[CT-12]` — arranjo alterado) | M13, M14 |
| CT-05 | duas ofertas com o mesmo e-mail, e os demais argumentos sobrevivem | R1 | cardinalidade do efeito + combinação de parâmetros | `Feature` (Tenancy) | `tests/Tenancy/CarimboDeOrganizacaoTest.php` (novo) | M18, M19 |

**Camada — por que nenhum destes é `Unit`.** Os `Então` afirmam **linha persistida** e **listener
registrado no boot de um painel**: container, banco e Filament, os três. E o `tests/Pest.php` deste
projeto **não** liga `pest()->extend(TestCase::class)` a `tests/Unit` — um caso lá rodaria sem
container e sem banco. A camada mais barata que o arnês sustenta é `Feature` em `tests/Tenancy`.
CT-04 sobe um degrau (componente Livewire) porque já vive lá e porque o segundo `Então` dele é
sobre a tabela renderizada.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| o mesmo carimbo sobre `Projeto` (o outro Resource com `$isScopedToTenant`) | declarado **fora de escopo** no `00`; e mataria os mesmos M10–M12, porque a mecânica é a mesma classe de vendor, registrada uma vez por Resource |
| asserção sobre o texto de `.ai/rules/testes.md` | asserção sobre prosa — ver `## RQ-01 e RQ-04` |
| varredura proibindo a correção manual de `convites.tenant_id` em `tests/` | lacuna declarada M15, com os dois motivos escritos |
| `ofertaPara($email, $acme, atributos: ['tenant_id' => $globex])` — **os dois** pedidos ao mesmo tempo | o `00` não determina a precedência entre parâmetro e atributo. Vira **pergunta**, não cenário com valor chutado. Nenhum consumidor exercita a combinação |
| duas chamadas de `ofertaPara()` com o mesmo e-mail (idempotência) | o agregado cresce por desenho; ver checklist |
| criar convite para outra organização pela **tela do `/admin`** | é comportamento de aplicação, e `app/` está fora de escopo. A célula "o `/admin` não carimba" já está em **CT-03, D3**, na camada mais barata |
| um cenário por linha do CT-01 e do CT-03 | `Esquema do Cenário` conta como 1 cenário; separar inflaria a contagem sem matar mutante novo |

## Teto do perfil

| Regra | Perfil | Teto | Cenários | Situação |
|---|---|---|---|---|
| R1 | completo | 5 | 2 (esquema de 6 linhas + CT-05) | dentro |
| R2 | completo | 5 | 1 (esquema de 3 linhas) | dentro |
| R3 | padrão | 3 | 1 (esquema de 4 linhas) | dentro |
| R4 | padrão | 3 | 1 | dentro |

Nenhum estouro de cenários. O conjunto é pequeno porque a superfície é pequena: **uma** interface
(chamada de função), **um** campo (`tenant_id`), **zero** artefatos de `app/`.

**Um estouro declarado, no teto de MUTANTES por regra**: R1 tem 8 contra o teto de 6 do perfil
completo. Os três excedentes vieram da revisão adversarial, que pela regra da skill não conta para
o teto. Registrado também na seção de R1.

## Sem CT-B

**O gate do `05` não passa, e o `01` está correto ao declarar "Sem superfície de UI".** Confirmado
por três verificações independentes:

1. **A varredura SFDIPOT §I encontrou uma única interface**: chamada de função PHP dentro do
   arnês. Sem rota nova, sem comando, sem job, sem tela.
2. **Nenhum artefato de `app/`, `routes/` ou `resources/views/` é tocado** — o `01` e o `02`
   declaram isso como decisão (ADR-01), e a `## Cobertura do Requisito` do plano mapeia os quatro
   RQ para `tests/Pest.php`, um arquivo de teste e um `.md`.
3. **A única tela que aparece em qualquer cenário é a listagem de convites do CT-04, que já
   existe e não muda**: o que muda ali é o **arranjo**. E o oráculo dela é `assertCanSeeTableRecords`
   / `assertCanNotSeeTableRecords`, provável por **componente Livewire** — nada de JavaScript
   executado, pixel, tema ou acessibilidade. Empurrar isso para o browser seria exatamente a
   decisão que a skill proíbe.

Sem linha em `## Superfície de UI`, o gate não tem nem o primeiro requisito. **Nenhum
`05-casos-de-teste-browser.md` é criado.**

## Fechamento do Ciclo — o que substitui `pest --mutate`

`pestphp/pest-plugin-mutate` está declarado em `composer.json` (v5.0.2, direto e não transitivo), e
mesmo assim **não há o que rodar**: o plugin muta código sob `app/`, e esta entrega não altera
`app/`. Rodar `--mutate --path=app/…` mediria a suíte contra código que ninguém tocou.

O que fica no lugar, em ordem de força:

| Instrumento | O que ele mede |
|---|---|
| **CT-03** | a **mudança de vendor** — é o único mutante que nenhuma ferramenta local pode gerar, porque ele chega por `composer update` |
| **Regressão dos consumidores** — `php artisan test --testsuite=Kit,Tenancy --parallel --compact` | que a mudança no helper compartilhado não quebrou nenhuma das 51 chamadas em 11 arquivos |
| **Revisão adversarial** | os mutantes de especificação de R1, R2 e R4, que a ferramenta não geraria de qualquer forma |

**Divergência skill × projeto, declarada**: a skill sugere `pest --parallel --tia` como padrão. O
`tests/Pest.php` liga `pest()->tia()->locally()`, e o `--tia` roda apenas o subconjunto afetado
pelo diff; o comando de verificação do plano usa `--parallel` sem `--tia`, que é o que roda a
suíte inteira dos consumidores — e regressão de helper compartilhado **precisa** da suíte inteira.
**O comando do plano vence.**

## Revisão Adversarial

**1 rodada**, executada por sub-agente independente que recebeu **apenas** o `00-requisito.md` e
este `04` — sem o plano, sem as ADRs, sem o código, sem o `tests/Pest.php`, sem o `vendor/` e sem o
raciocínio da derivação. Contrato: *provar que o conjunto deixa passar um defeito*; proibido
elogiar ou reescrever.

Produziu **5 implementações erradas que passavam por todos os cenários** e 15 lacunas. Fechamento:

| # | Achado | Severidade | O que virou |
|---|---|---|---|
| **A1** | a partição de CT-02 era a **errada**: com o painel não bootado, nenhuma implementação plausível escreve, então condicional e incondicional não divergem ali. **M6 não morria** | **estrutural** | CT-02 ganhou a linha 2 — **vendor ativo e convergente** —, que é a única partição em que a distinção existe. M6 passou a apontar para ela |
| **A2** | CT-02 tinha só uma direção do rastreio; sozinha, é satisfeita por "nunca escreve nada" | **estrutural** | CT-02 virou esquema de 3 linhas (não aconteceu × 2 partições, aconteceu uma só vez). *Parcialmente fechado antes da revisão, por autoauditoria da técnica* |
| **A3** | o veredito de CT-02 depende do mecanismo de escrita: `forceFill()->saveQuietly()` sem `if` não emite escrita quando o valor já está certo | **discordância registrada** | **Não fechado como defeito, e por quê**: essa implementação é *condicional no efeito*, que é o que R2 exige. O oráculo alternativo proposto (`wasChanged`, `updated_at == created_at`) mediria a **forma**, que a `## Fronteira com o Plano` recusa. Escrito na ressalva de R2 |
| **A4** | CT-03 D1, D2 e D3 esperam `Globex`, que é **exatamente o resultado de nenhum listener existir** — um arranjo que não bootasse de fato deixaria as três verdes pelo motivo errado | **estrutural** | CT-03 ganhou a precondição verificada `E há <listeners> listener de criação registrado`, com a coluna 0/1/1/1 — o instrumento que o próprio `00` mediu e a primeira derivação não usou |
| **A5** | CT-04 fica verde com o contorno manual no lugar **e** com o helper sem correção: os dois `Então` originais não afirmam a organização gravada | **estrutural** | CT-04 ganhou o `Então` do `tenant_id` gravado da oferta da Globex, antes dos dois antigos |
| **A6** | o Checklist de Taxonomia afirmava que CT-01 cobria 3 estados do discriminador, e os `Exemplos` só tinham 2 — **"bootado sem organização corrente" não tinha linha** | **estrutural** | linha 5 de CT-01 |
| **A7** | MX-1: silenciar o listener (`withoutEvents()`) em vez de corrigir passava tudo | derivado | morto pela **CT-02 linha 3** (sem carimbo não há divergência, e a escrita esperada some) → **M16** |
| **A8** | MX-3: trocar a organização corrente em volta do `create()` passava tudo, mutando estado global e o team de permissões | derivado | `Então` de invariância em CT-01 → **M17** |
| **A9** | MX-4: corrigir por `where('email', …)` passava tudo e reescreveria a oferta anterior | derivado | **CT-05** → **M18**; linha nova no checklist (cardinalidade do efeito) |
| **A10** | MX-5: substituir `$atributos` em vez de mesclar passava tudo — nenhum cenário variava mais de um argumento | derivado | **CT-05** → **M19**; linha nova no checklist (preservação dos argumentos) |
| **A11** | afirmação falsa na justificativa de CT-02: o segundo `Então` **não** matava M1 | **correção factual** | corrigido no texto de R2, com a explicação de por que era tautológico |

### Achados recusados, com motivo

| Achado | Por que não virou cenário |
|---|---|
| oráculo mecanismo-independente para R2 (`wasChanged`, `updated_at`) | mede a forma; ver A3 |
| caminho de exceção sobre a invariância ("se a criação lançar, a organização corrente continua a da Acme") | especulativo: nenhuma implementação plausível do helper lança entre a troca e a restauração, e o cenário exigiria injetar falha no `create()`. **Lacuna declarada**, reavaliar se M17 chegar a ser implementado |
| `role_id` de papel do team da organização pedida | os papéis do kit **não** são por organização — a coluna de team vive na pivot do Spatie, não em `roles`. O achado supõe um modelo de dados que este kit não tem |
| `ofertaPara()` chamado com o painel `admin` corrente | nenhum consumidor faz isso, e o comportamento é o mesmo da partição "vendor inativo" já coberta por CT-01 linha 1 |
| "existe exatamente uma linha para aquele e-mail" como `Então` de CT-01 | coberto por CT-05, que é onde a cardinalidade importa; em CT-01 seria detalhe incidental |

### Segunda rodada

**Não executada.** A regra da skill manda re-revisar uma única vez **se o fechamento criou cenário
novo** — e criou (CT-05). A rodada foi **dispensada por decisão registrada**: a superfície nova de
CT-05 é uma segunda chamada do mesmo helper com argumentos que os outros cenários já exercitam
isoladamente, e não introduz condição, estado nem interface nova. Se a implementação de CT-05
revelar arranjo mais complexo que o previsto, a segunda rodada volta a ser devida.

## Roteiro de Validação: Desenhado × Implementado

Preenchido na implementação. As duas últimas linhas são a verificação das cláusulas que **não**
viraram CT.

| # | O que o requisito pediu | Onde se verifica | Confere? | Evidência |
|---|---|---|---|---|
| 1 | RQ-02 — caminho explícito para fixture de outra organização | CT-01 | | |
| 2 | RQ-02/ADR-02 — a correção é condicional | CT-02 | | |
| 3 | RQ-04 (fato) — é do vendor, com duas guardas, e o `/admin` não é atingido | CT-03 | | |
| 4 | RQ-03 — o contorno do CT-12 saiu | CT-04 + **inspeção do diff** (M15 é lacuna declarada) | | |
| 5 | RQ-01 — a armadilha está em `.ai/rules/testes.md`, glob `tests/**` | **inspeção** — asserção de documentação | | |
| 6 | RQ-04 (registro) — a rule diz que é do vendor, que é fail-safe e que **não se conserta** | **inspeção** — asserção de documentação | | |

---

## Perguntas para o 00-requisito.md

> **Desvio declarado**: a instrução da derivação foi explícita — *"Perguntas novas vão no relatório
> final marcadas 'para ## Ambiguidades do 00-requisito.md' — não edite o `00`"*. O bloco abaixo está
> pronto para colagem em `00-requisito.md` → `## Ambiguidades e Perguntas Abertas`, na mesma forma
> dos itens que já existem lá. As perguntas continuam **bloqueando** o que delas depende.

- **RQ-02 — o que acontece quando NENHUMA organização é pedida, com o painel bootado?**
  **Assumido**: a garantia é sobre a organização **pedida**; sem pedido não há garantia, e o
  carimbo do vendor fica de pé (o convite nasce na organização corrente). É o que sustenta as 19
  chamadas de `tests/Kit` que não passam organização. **Se negado** (o helper deveria forçar
  `null`): a linha 5 do CT-01 inverte, e todo consumidor que hoje cria fixture dentro de uma
  organização sem nomeá-la passa a receber convite órfão. *Bloqueia*: CT-01 linha 5 (`@premissa`).

- **RQ-02 — quando a organização é pedida pelas DUAS portas ao mesmo tempo (`$tenant` e
  `atributos['tenant_id']`) e elas divergem, qual vence?** **Não assumido, e nenhum cenário foi
  escrito com valor chutado**: nenhum dos 11 arquivos consumidores exercita a combinação. **Se a
  resposta for "o atributo vence"** (a leitura natural de um override explícito), entra uma sexta
  linha no CT-01; **se for "erro de arranjo"**, entra um cenário de recusa. *Bloqueia*: nada hoje;
  vira dívida no dia do primeiro consumidor.

- **RQ-03 — a remoção do contorno deve ser enforçada por varredura, ou basta a inspeção do
  diff?** **Assumido**: basta a inspeção — a varredura foi cortada como enforço especulativo e por
  colidir com a rule de filtrar comentário (ver lacuna declarada **M15**). **Se negado**: entra um
  caso no molde do `tests/Kit/HelpersDeTesteTest.php`, com `token_get_all()` para ignorar a
  menção ao padrão no próprio docblock do helper. *Bloqueia*: o matador de M15.
