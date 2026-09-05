# Casos de Teste — Badge de contagem em todo Resource do kit

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Do PRD saíram apenas paths, stack e a tabela
> `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Comportamento do badge (número, zero, cor) | 2 | 2 | 4 | padrão |
| Cobertura e enforço (todo Resource do app tem badge) | 2 | 2 | 4 | padrão |
| **Contagem sob organização** | 2 | 3 | 6 | padrão |

Nenhuma área chega a `completo` — **sem revisão adversarial** nesta rodada.

- Técnicas aplicadas: EP, varredura derivada dos painéis registrados, matriz painel × escopo
- Cenários: **8** · Regras: **5** · Mutantes previstos: **15** · Sem matador: **0**

> A terceira área não está escrita no requisito, e não foi inventada: RQ-03 manda dar badge a
> **todos** os Resources, e três deles vivem num painel escopado por organização. "O badge mostra
> a contagem daquele item de menu" (RQ-01) só é verdade, ali, se a contagem respeitar a organização
> corrente. É consequência direta de expandir RQ-03, e é onde o dano seria de dado de terceiro.

## Divergência declarada com a skill

Nenhuma rule de `.ai/rules/` colide com esta skill aqui. Divergência apenas de comando: a skill
sugere `pest --parallel --tia`; este conjunto usa `php artisan test --compact`, o comando do
`CLAUDE.md`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | um trait (`app/Filament/Concerns/`), dois Resources do app, um arquivo de teste novo. Nenhuma migration, model, policy, rota, comando ou config | CT-01, CT-06 |
| **F** | contar registros; decidir a cor pela contagem; garantir que todo Resource do app tenha badge | CT-01…CT-08 |
| **D** | contagem **0**; contagem N; **registro excluído por soft delete**; registros de **outra organização**; Resource de vendor (fora de escopo) | CT-02, CT-03, CT-04, CT-07, CT-08 |
| **I** | só o menu lateral dos três painéis. Nenhuma rota nova, nenhum comando, nenhum job | CT-01 |
| **P** | **a dimensão que mais dói aqui, e é de linguagem**: dois traits declarando o mesmo método é **erro fatal de compilação**, não teste vermelho. Nenhum cenário pode capturá-lo por assertion — o processo morre antes. O que o cobre é **carregar todas as classes**, que é o que CT-01 e CT-06 fazem por construção | CT-01, CT-06 (por efeito, não por assertion) |
| **O** | quem abre cada um dos três painéis; **instalação recém-criada, em que quase tudo é zero** — o cenário que originou o requisito | CT-04 |
| **T** | **não se aplica**: sem janela temporal, sem agendamento, sem expiração, sem concorrência. A contagem é do instante do render. Memoização por request é escolha de implementação, não comportamento requerido | — (declarado) |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — todo Resource do app registrado num painel expõe badge de navegação | cobertura (padrão) | RQ-01, RQ-02, RQ-03 | varredura derivada | CT-01 |
| R2 — o badge exibe o número de registros que **aquela listagem** mostraria | comportamento (padrão) | RQ-01, RQ-02 | EP | CT-02, CT-08 |
| R3 — em painel escopado por organização, a contagem é a da organização corrente | organização (padrão) | RQ-01, RQ-03 | matriz painel × escopo | CT-03 |
| R4 — contagem zero é exibida, em cor distinta da contagem maior que zero | comportamento (padrão) | RQ-01 (**premissa**) | EP + BVA de 2 pontos | CT-04 |
| R5 — o badge é renderizado pelo odômetro, e Resource do app sem badge reprova a suíte | cobertura (padrão) | RQ-04, RQ-05 | EP | CT-05, CT-06, CT-07 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome do trait `BadgeContagemNavegacao` | escolha de implementação | detalhe do cenário — CT-06 usa o nome porque é o mecanismo do enforço, não porque o requisito o nomeie |
| nome do método `contagemDoBadge()` e o uso de `once()` | escolha de implementação (ADR-03) | detalhe; nenhum `Então` afirma memoização |
| `insteadof` em `RoleResource` | escolha de implementação (ADR-02) | detalhe; o `Então` de CT-01 afirma que **`RoleResource` tem badge**, não como |
| `null` como "cor default" acima de zero | **só o PRD determina** | ⚠️ pergunta; CT-04 afirma que a cor do zero **difere** da cor acima de zero, e que a do zero é `gray` — o valor acima fica livre |
| tooltip `'Total de registros'` | só o PRD determina, e é texto visível | ⚠️ pergunta; sem cenário |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **Qual cor acima de zero?** Premissa: o default do Filament. Bloqueia R4 parcialmente — CT-04
  afirma só a **diferença** e o `gray` do zero, que é o que o usuário respondeu.
- **Soft delete conta?** Premissa: não, porque a contagem sai do que a listagem mostra. Bloqueia
  R2; **CT-08** marcado `@premissa`.
- **Nova nesta derivação**: o texto do tooltip não é determinado pelo requisito. Não bloqueia nada
  e não tem cenário. Ver `## Perguntas para o 00-requisito.md`.

## Setup Global

### Suítes

| Regra | Suíte | Por quê |
|---|---|---|
| R1, R4, R5 e o CT-02 de R2 | `tests/Kit` | o badge não depende de organização nos painéis `admin` e `infra` |
| R3 e o CT-08 de R2 | `tests/Tenancy` | `noPainelDa()` e o escopo de organização só existem com `permission.teams` ligado e o painel `app` com o segmento de tenant. Em `tests/Kit` o cenário mediria o ramo fail-closed, não o escopo |

> Escolher a suíte errada aqui não dá erro: CT-03 em `tests/Kit` ficaria **verde** contando zero
> pelos dois motivos ao mesmo tempo, e não distinguiria nada. Mesma armadilha que
> `.ai/rules/testes.md` documenta para o papel `admin_app`.

### Personas

- `admin` — `usuarioDoKit('admin', 'admin@example.com')` (Kit)
- `duasOrganizacoes()` — o helper global devolve `acme`, `globex` e uma pessoa vinculada às duas.
  É ele que dá a **persona não colapsada** de CT-03: sem duas organizações com contagens
  diferentes, o escopo não é exercitado.

### Fixtures

- `Convite` — `ofertaPara('alguem@example.com')`, helper global (o `role_id` explícito importa).
  **`Convite` não usa `SoftDeletes`** (medido), por isso CT-08 usa `Projeto`
- `Projeto` — para CT-08, três na Acme com um `delete()`. O model usa `SoftDeletes`
- `Role` — já semeados por `PapeisSeeder`; a contagem vem do seeder, não de fixture escrita à mão
- `User` — `usuario()` / `User::factory()`

### Fakes

Nenhum. A feature não emite e-mail, fila, HTTP, notificação nem log.

### Estratégia de DB

`RefreshDatabase`, global em `tests/Kit` e `tests/Tenancy` por `tests/Pest.php`.

### Como ler o badge

`getNavigationBadge()` devolve **HTML renderizado pelo odômetro**, não um número. Afirmar
`toContain('5')` seria fraco — `5` aparece em qualquer atributo. O oráculo é a **igualdade contra
a mesma renderização do valor esperado**:

```php
expect($resource::getNavigationBadge())
    ->toBe(OdometerNavigationBadge::make($esperado));
```

O valor esperado é calculado da **fixture**, nunca do código sob teste. Isso mata "contou da fonte
errada" sem depender do formato do HTML — e o formato é do pacote, que pode mudar num upgrade sem
que a regra mude.

---

## Regra R1 — todo Resource do app registrado num painel expõe badge de navegação

> `RQ-01`, `RQ-02`, `RQ-03` · perfil **padrão** · técnica: **varredura derivada dos painéis registrados**

```gherkin
# language: pt

Funcionalidade: Badge de contagem no menu

  Regra: todo Resource escrito no app e registrado em um painel expõe badge de navegação

    Cenário: [CT-01] nenhum Resource do app fica sem badge
      Dado os Resources registrados nos painéis "admin", "app" e "infra"
      Quando o administrador considera apenas os escritos no app
      Então todos eles devolvem um badge de navegação
      E "Convites" do painel "admin" está entre eles
      E "Papéis" do painel "admin" está entre eles
```

As duas últimas linhas são RQ-01 e RQ-02 **nomeadas**, e não são redundantes com a primeira: uma
varredura que devolvesse lista vazia satisfaria "todos eles" por vacuidade. Ancorar os dois itens
que o requisito cita impede o falso ✅.

> **A lista é derivada de `Filament::getPanel($id)->getResources()`, nunca escrita à mão.** Escrita
> à mão, ela não pega a classe nova — que é a única razão de este caso existir. Molde:
> `tests/Kit/PermissoesDeWidgetsTest.php:234`.
>
> **Efeito colateral deliberado**: percorrer os três painéis **carrega todas as classes de
> Resource**. Colisão de trait é erro fatal de compilação e mataria este caso na hora — é a única
> forma de a suíte "ver" um defeito que nenhuma assertion alcança.

**Camada**: `Feature` (suíte `Kit`) — o `Então` afirma sobre classes registradas, não sobre tela.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o trait é aplicado só nos Resources do painel `admin`, esquecendo `infra` | CT-01 |
| M2 | a varredura devolve lista vazia (filtro de namespace errado) e o caso passa por vacuidade | CT-01 (as duas âncoras) |
| M3 | `RoleResource` fica de fora porque a colisão de trait foi "resolvida" removendo o trait do kit | CT-01 (âncora de Papéis) |

---

## Regra R2 — o badge exibe o número de registros que aquela listagem mostraria

> `RQ-01`, `RQ-02` · perfil **padrão** · técnica: **EP** (vazio / um / muitos)

```gherkin
# language: pt

  Regra: o número do badge é a contagem dos registros que a listagem daquele item mostraria

    Esquema do Cenário: [CT-02] a contagem acompanha os registros existentes @premissa
      Dado <quantidade> convites pendentes cadastrados
      Quando o administrador consulta o badge do item "Convites"
      Então o badge é a renderização do número <quantidade>

      Exemplos:
        | quantidade | # partição       |
        | 1          | um               |
        | 3          | muitos           |
```

> A partição **vazio** saiu daqui na auditoria Ponytail: CT-04 já a afirma, e com mais força —
> lá o `Então` cobre a existência do badge **e** a cor, não só o número. Nenhum mutante ficou sem
> matador.

> `@premissa`: registro excluído por soft delete não conta, porque a contagem sai do que a
> listagem mostra. Registrado em `00-requisito.md`.
>
> Três é o "muitos" mínimo que discrimina: com 2, um mutante que contasse `1` no lugar de `n`
> ainda erraria, mas um que devolvesse a contagem de **outra** tabela pequena poderia acertar por
> acidente. Três, com a tabela vizinha em outro valor, separa.

```gherkin
# language: pt

  Regra: o número do badge é a contagem dos registros que a listagem daquele item mostraria

    Cenário: [CT-08] registro excluído por soft delete não conta @premissa
      Dado 3 projetos na Acme, um deles excluído
      Quando um operador da Acme consulta o badge do item "Projetos" do painel do negócio
      Então o badge é a renderização do número 2
```

> Três registros com **um** excluído, e não dois com um: o número esperado (2) precisa diferir
> tanto do total bruto (3) quanto de qualquer constante plausível. Com 2 e 1, o esperado seria 1 —
> indistinguível do mutante "devolve 1 quando há registro" (M6).
>
> Este cenário nasceu de uma **lacuna declarada errada**: a primeira derivação afirmou que o único
> model do kit com soft delete e Resource era `User`, cujo Resource cai no ramo fail-closed sem
> tenant. A revisão profunda mediu: `Projeto` também usa `SoftDeletes`, e `ProjetoResource` **está**
> registrado no painel `app` da suíte `Tenancy`. O arnês sempre permitiu — a impossibilidade era
> hipótese, não conclusão.

**Camada**: CT-02 em `Feature` (suíte `Kit`); **CT-08 em `Feature` (suíte `Tenancy`)**, com
`noPainelDa($acme)` — sem o tenant fixado, `ProjetoResource::getEloquentQuery()` cai no ramo
fail-closed e o cenário mediria a fronteira em vez do soft delete.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | contagem por `getModel()::count()` em vez de `getEloquentQuery()` — ignora os escopos do resource | CT-02 (linha "vazio" não; **CT-03** é quem o mata de verdade) |
| M5 | o badge devolve a contagem de outro Resource (copy-paste do vizinho) | CT-02 (linha "muitos") |
| M6 | a contagem é fixada em 1 quando há qualquer registro | CT-02 (linha "muitos") |
| M7 | o badge devolve o número cru, sem o odômetro | CT-05 |
| M15 | a contagem ignora o soft delete (`withTrashed()`, ou `getModel()::count()` sem os escopos) | CT-08 |

---

## Regra R3 — em painel escopado por organização, a contagem é a da organização corrente

> `RQ-01`, `RQ-03` · perfil **padrão** · técnica: **matriz painel × escopo**

A matriz, e cada célula é uma linha do `Esquema`:

| Organização corrente | Convites da Acme | Convites da Globex | Badge esperado |
|---|---|---|---|
| Acme | 3 | 1 | **3** |
| Globex | 3 | 1 | **1** |

Contagens **diferentes** de propósito: com 1 e 1, uma implementação que ignorasse a organização
devolveria 2 e ainda assim erraria — mas uma que devolvesse "a primeira organização" acertaria por
acidente. Com 3 e 1, cada defeito produz um número distinto.

```gherkin
# language: pt

  Regra: no painel do negócio, o badge conta apenas os registros da organização corrente

    Esquema do Cenário: [CT-03] o badge não soma organizações
      Dado 3 convites na Acme e 1 convite na Globex
      Quando um operador da "<organizacao>" consulta o badge do item "Convites" do painel do negócio
      Então o badge é a renderização do número <esperado>

      Exemplos:
        | organizacao | esperado | # célula          |
        | Acme        | 3        | organização maior |
        | Globex      | 1        | organização menor |
```

**Camada**: `Feature` (suíte `Tenancy`), com `noPainelDa($organizacao)` fixando o tenant e o
contexto de papéis — é o que um request real faz e um teste não tem de graça.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | `getModel()::count()` em vez de `getEloquentQuery()` — soma as duas organizações e devolve 4 | CT-03 (as duas linhas) |
| M8 | a contagem usa a primeira organização em vez da corrente | CT-03 (linha "organização menor") |
| M9 | o badge é calculado antes de o painel fixar o tenant, caindo no ramo fail-closed e devolvendo 0 | CT-03 (as duas linhas) |

---

## Regra R4 — contagem zero é exibida, em cor distinta da contagem maior que zero

> `RQ-01` (**premissa negociada com o usuário**) · perfil **padrão** · técnica: **EP + BVA de 2 pontos** (fronteira: `0` × `1`)

Esta regra **reverte** o comportamento anterior do kit, em que zero não produzia badge nenhum.
O cenário precisa afirmar as duas metades: o badge **existe** no zero, e a cor **difere**.

```gherkin
# language: pt

  Regra: o badge aparece mesmo com zero registros, e a cor distingue o vazio do preenchido

    Cenário: [CT-04] zero aparece, em cor discreta
      Dado nenhum convite cadastrado
      Quando o administrador consulta o item "Convites"
      Então o badge existe e é a renderização do número 0
      E a cor do badge é "gray"
      E com 1 convite cadastrado a cor do badge deixa de ser "gray"
```

> A terceira linha é o par obrigatório da segunda: afirmar só `gray` no zero passaria numa
> implementação que devolvesse `gray` **sempre** — e aí a distinção que o usuário pediu não
> existiria, com o checklist marcado ✅.
>
> O valor da cor acima de zero **não** é afirmado, porque o requisito não o determina. Ver
> `## Fronteira com o Plano`.

**Camada**: `Feature` (suíte `Kit`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | o zero continua devolvendo `null` — comportamento anterior do kit, que é o mais provável de sobreviver | CT-04 (primeira asserção) |
| M11 | a cor é `gray` sempre | CT-04 (terceira asserção) |
| M12 | a cor nunca é `gray` — o método não foi declarado e herda o `null` do Filament | CT-04 (segunda asserção) |

---

## Regra R5 — o badge é renderizado pelo odômetro, e Resource do app sem badge reprova a suíte

> `RQ-04`, `RQ-05` · perfil **padrão** · técnica: **EP**

```gherkin
# language: pt

  Regra: o badge sai do pacote de odômetro, e a ausência dele em Resource do app reprova

    Cenário: [CT-05] o badge é a renderização do odômetro, não o número cru
      Dado 3 convites pendentes cadastrados
      Quando o administrador consulta o badge do item "Convites"
      Então o badge difere da representação textual do número 3
      E o badge é idêntico ao que o odômetro produz para o número 3

    Cenário: [CT-06] Resource do app sem badge reprova a varredura
      Dado um Resource escrito no app que não expõe badge de navegação
      Quando a varredura dos painéis é executada
      Então ela reprova nomeando a classe

    Cenário: [CT-07] Resource de pacote de terceiro não é cobrado
      Dado os Resources de pacotes de terceiros registrados nos três painéis
      Quando a varredura dos painéis é executada
      Então nenhum deles é cobrado pela regra
```

> **CT-06 é o único cenário que testa o próprio teste.** Ele exercita a varredura contra uma classe
> anônima de Resource sem badge, e afirma que ela reprova **com o nome**. Sem ele, um filtro
> excessivamente estreito (ou uma lista vazia) deixaria a suíte verde para sempre, e RQ-05 estaria
> ✅ com o enforço desligado.
>
> **CT-07 protege a fronteira de escopo** que o usuário decidiu. Sem ele, alguém "conserta" a
> varredura incluindo vendor, o teste fica vermelho em 9 classes que ninguém pode editar, e a
> reação provável é desligar o enforço inteiro.

**Camada**: `Feature` (suíte `Kit`) nos três.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | o badge devolve `(string) $contagem`, sem passar pelo odômetro | CT-05 (primeira asserção) |
| M13 | a varredura só avisa, sem reprovar (assertion ausente ou `expect()` sem `->toBe()`) | CT-06 |
| M14 | a varredura inclui Resource de vendor e fica vermelha de forma incontornável | CT-07 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | CT-03 — é a forma que o item assume aqui: o "recurso de outra pessoa" é o registro de outra organização, e o badge não pode contá-lo |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: o badge não é ação. Ele só é renderizado para quem já vê o item de menu, e o item é gated pelo `canAccess()` do Resource, já coberto por `tests/Kit/PermissoesDeResourcesTest.php` |
| Idempotência (ancorada no agregado) | **não se aplica**: a feature só lê. Consultar o badge duas vezes não muda nada — não há agregado a ancorar |
| Concorrência | **não se aplica**: sem contador, saldo ou limite. A contagem é uma leitura do instante |
| Fronteira no ponto de entrada | CT-04 — a única fronteira desta feature é `0` × `1` |
| Domínio condicionado | **não se aplica**: a contagem não depende de nenhum outro campo |
| Estado × operação de escrita | **não se aplica**: a feature não acrescenta operação de escrita |
| Ausente ≠ null ≠ vazio | CT-04 — é exatamente a distinção da regra: badge **ausente** (`null`) deixa de ser o mesmo que contagem **zero** |
| Paginação / ordenação | **não se aplica**: o badge é um número agregado, sem página nem ordem |
| Timezone / DST | **não se aplica**: nenhuma coluna de data participa da contagem |
| Unicode / limite de varchar | **não se aplica**: o badge só carrega inteiro |
| Unicidade + soft delete | **CT-08**. A primeira derivação declarou lacuna aqui, alegando que só `User` tinha soft delete — **medição na revisão profunda mostrou que `Projeto` também tem**, e que `ProjetoResource` está registrado na suíte `Tenancy`. Lacuna falsa, convertida em cenário |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: nenhum payload chega a esta feature |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: contagem inteira |
| **Colisão de trait (fatal de compilação)** | CT-01 e CT-06 **por efeito**, não por assertion — os dois carregam todas as classes de Resource, e um fatal mata o run. Nenhuma assertion pode capturá-lo, e o `php artisan about` da Verificação Final é a segunda rede |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | nenhum Resource do app fica sem badge | R1 | varredura derivada | Feature | `tests/Kit/BadgeDeNavegacaoTest.php` | M1, M2, M3 |
| CT-02 | a contagem acompanha os registros | R2 | EP | Feature | `tests/Kit/BadgeDeNavegacaoTest.php` | M5, M6 |
| CT-03 | o badge não soma organizações | R3 | matriz painel × escopo | Feature | `tests/Tenancy/BadgeDeNavegacaoTenancyTest.php` | M4, M8, M9 |
| CT-04 | zero aparece, em cor discreta | R4 | EP + BVA | Feature | `tests/Kit/BadgeDeNavegacaoTest.php` | M10, M11, M12 |
| CT-05 | o badge é o odômetro, não o número cru | R5 | EP | Feature | `tests/Kit/BadgeDeNavegacaoTest.php` | M7 |
| CT-06 | a varredura reprova nomeando a classe | R5 | EP | Feature | `tests/Kit/BadgeDeNavegacaoTest.php` | M13 |
| CT-07 | Resource de vendor não é cobrado | R5 | EP | Feature | `tests/Kit/BadgeDeNavegacaoTest.php` | M14 |
| CT-08 | soft delete não conta | R2 | EP | Feature | `tests/Tenancy/BadgeDeNavegacaoTenancyTest.php` | M15 |

## Sem CT-B

**O `05` não é criado.**

A tabela `## Superfície de UI` do PRD tem uma linha e ela marca "depende de JS: sim" — o odômetro
anima o número. Mas essa animação **já existe hoje**, nos oito badges que o kit renderiza; esta
feature não a introduz, e nenhuma cláusula do requisito afirma sobre ela.

Tudo o que a feature muda é falsificável sem navegador: qual classe expõe badge, qual número o
badge carrega, qual cor ele tem, e se a varredura reprova. O smoke de JavaScript das telas dos três
painéis já é rodado por `tests/Browser/TelasDoKitTest.php` sobre `telasDoKit()`, que inclui as
rotas raiz dos três painéis — é lá que um badge quebrado apareceria como erro de console.

Cenários cogitados e cortados:

| Cenário cogitado | Por que foi cortado |
|---|---|
| o menu dos três painéis abre sem erro de JS com os badges novos | já coberto por `TelasDoKitTest` |
| o odômetro anima de 0 até N | é comportamento do pacote, não desta feature |
| o badge continua visível com a sidebar recolhida | é `badgeOnCollapsedSidebar()`, configuração já existente do plugin nos três painéis |
| a cor `gray` renderiza mais discreta que a default | é pixel; o oráculo útil é o valor da cor, e está em CT-04 |

## Perguntas para o `00-requisito.md`

Bloco pronto para colagem em `## Ambiguidades e Perguntas Abertas`:

```markdown
- **Levantada na derivação dos casos de teste** — o texto do tooltip do badge não é determinado
  pelo requisito.
  - **Assumido**: `Total de registros`, que é o texto já vigente no trait.
  - **Se negado**: troca de string, sem efeito em nenhum caso de teste — nenhum `Então` afirma o
    tooltip, de propósito.
```

## Fechamento do ciclo — mutation testing

Depois de implementar:

```bash
php artisan test --compact tests/Kit/BadgeDeNavegacaoTest.php tests/Tenancy/BadgeDeNavegacaoTenancyTest.php
vendor/bin/pest tests/Kit/BadgeDeNavegacaoTest.php --mutate --path=app/Filament/Concerns/BadgeContagemNavegacao.php
```

`pestphp/pest-plugin-mutate ^5.0` está declarado direto no `composer.json`.

> **Aviso sobre o que o mutation score não vê aqui**: ele muta o **trait**, que é onde a lógica
> vive. Ele **não** enxerga um Resource que nunca recebeu o trait — não há linha para mutar. Quem
> responde por essa omissão é CT-01, e é por isso que a lista dele é derivada dos painéis.

Mutante sobrevivente vira lacuna de derivação e cenário novo **aqui** — nunca ajuste no teste para
ficar verde.
