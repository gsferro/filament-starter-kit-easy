# Casos de Teste — Hub de navegação em cards

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — quais cartões cada pessoa vê | 2 — integra com `canAccess()` de Resource e Page, e com a matriz do Shield | 3 — **autorização**: cartão indevido vaza a existência de tela de administração | 6 | **padrão** |
| A2 — permissão da Page nova e a subtração do `panel_user` | 2 | 3 — autorização; o erro espelhado tira acesso de quem deveria ter | 6 | **padrão** |
| A3 — aparência do cartão (o CSS do ADR-02) | 3 — depende de classe existir na CSS pré-compilada de terceiro; falha **silenciosa** | 1 — cosmético, reversível | 3 | **mínimo** |
| A4 — busca client-side | 1 | 1 | 1 | **mínimo** |

- Técnicas aplicadas: **matriz papel × destino**, **EP por painel**, **matriz papel × permission**, **rastreio de efeito** (grupo e destino do cartão)
- Cenários: 6 (5 no `04`, 1 no `05`) · Regras: 4 · Mutantes previstos: 12 · Sem matador: 1 (declarado)
- **Cortes da auditoria Ponytail**: R5/CT-06 (guarda de painel nulo — estado inalcançável) e CT-B01 (busca client-side — testa o Alpine do vendor)

> **Divergência declarada — Project Rule vence a skill.** `--parallel` fica fora dos CT-B e o
> `--tia` é inviável sem PCOV (`.ai/rules/testes-browser.md`). Dois comandos:
> `vendor/bin/pest --parallel --group=kit` e `vendor/bin/pest --testsuite=Browser`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | 3 Pages novas (uma por painel), 1 concern, 1 arquivo CSS, 1 registro de asset. **3 permissions novas** derivadas pelo Shield | CT-03, CT-04 |
| **F** | duas funções: **descobrir** os destinos do painel e **filtrar** por autorização. A segunda é a que carrega risco | CT-01, CT-02, CT-05 |
| **D** | o dado é a **lista de componentes do painel** — não vem do banco. Cardinalidade relevante: painel com muitos destinos (`/infra`), painel com poucos (`/app`), e **nenhum destino autorizado** (usuário sem permissão alguma) | CT-01, CT-02 |
| **I** | três rotas GET novas, uma por painel. Nenhum comando, job ou webhook | CT-03 |
| **P** | **Tailwind**: a blade do pacote depende de classes que podem não existir na CSS pré-compilada (ADR-02) — falha silenciosa, com HTML correto | CT-B01 |
| **O** | três perfis: `master_global` (vence pelo `Gate::before`), papel de painel (`admin`, `infra`) e `panel_user` no `/app` | CT-01, CT-02, CT-03 |
| **T** | **não se aplica**: nada expira, nada é agendado. O hub é recalculado a cada request; não há cache a invalidar | não se aplica |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — o hub mostra apenas os destinos que o visitante pode acessar | A1 (padrão) | RQ-02, RQ-06 | matriz papel × destino | CT-01, CT-02 |
| **R2** — cada painel tem um hub alcançável por quem entra nele | A2 (padrão) | RQ-06 | EP por painel | CT-03 |
| **R3** — o hub do `/app` pertence ao usuário comum da organização | A2 (padrão) | RQ-06 + ADR-05 | matriz papel × permission | CT-04 |
| **R4** — o cartão leva ao destino que ele nomeia | A1 (padrão) | RQ-02 | rastreio de efeito (URL e agrupamento) | CT-05 |

**Regras que o requisito gera e que não viram cenário:**

| `RQ` | Por que não há cenário |
|---|---|
| RQ-03, RQ-05 (documentação e exemplos) | prosa em markdown; teste de `str_contains` seria tautológico |
| RQ-04 (sugestão de uso para o agente) | o mecanismo é a Project Rule em `.ai/rules/`, cuja eficácia não é observável em teste automatizado. **Lacuna declarada por natureza do requisito**, não por omissão |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes das classes (`HubDeInfraestrutura`, `DescobreCardsDoPainel`) | escolha de implementação | detalhe do cenário |
| slugs das rotas (`/infra/hub-de-infraestrutura`) | derivados do nome da classe, que é escolha do PRD | os cenários navegam pelo **componente**, não pela URL literal, exceto CT-03 — que precisa da rota e a obtém de `route()`/`getUrl()`, não de string fixa |
| `$columns = 3`, `$searchable`, `$navigationSort` | apresentação | detalhe |
| **títulos das telas** ("Central de infraestrutura") | comportamento visível que **só o PRD determina** | ⚠️ **pergunta ao usuário** — o requisito não nomeia as telas |
| `canAccess()` como filtro | **aceito como oráculo**: o requisito pede um hub de navegação do painel, e navegação que oferece destino negado não é navegação — é 403 adiado. Reforçado pela convenção do projeto (`.ai/rules/filament.md`) | R1 |

**Perguntas para o `00-requisito.md`** (replicar em `## Ambiguidades`):

- Os títulos das três telas vêm do PRD, não do requisito. Confirmar — ou aceitar que nenhum
  cenário afirma sobre eles (é o que está feito: os `Então` falam dos **destinos**, não do título).
- Usuário autenticado num painel **sem nenhum destino autorizado** deve ver o hub vazio ou receber
  403? O requisito não diz. **Assumido**: hub vazio, porque o 403 esconderia a única página de
  aterrissagem. **Sem cenário**: quem não tem papel não entra em painel algum
  (`User::canAccessPanel()`), então o estado só é alcançável por um papel de painel sem nenhuma
  permission — configuração que o `PapeisSeeder` não produz.

## Setup Global

### Personas

| Persona | Como criar | Por que existe |
|---|---|---|
| `master_global` | `usuarioDoKit('master_global')` | vence pelo `Gate::before`; vê **tudo**. É a persona de controle: se o hub some para ele, o defeito é de renderização, não de autorização |
| `infra` | `usuarioDoKit('infra')` | papel de painel real, dependente da matriz do Shield |
| `panel_user` numa organização | `usuarioComPapel('panel_user', $tenant)` + `noPainelDa($tenant)` | **a persona discriminante**: é ela que separa "filtra por `canAccess()`" de "mostra tudo" |

> **Persona colapsada é o anti-padrão a evitar aqui.** Percorrer os três hubs só com
> `master_global` produz uma matriz "100% coberta" com a barreira de autorização inteira sem um
> único cenário — ele passa pelo `Gate::before` **antes** de qualquer `canAccess()` ser consultado.

### Fixtures

- Organização: `tenant('Acme', 'acme')`, com o usuário vinculado por `->tenants()->attach()`
- Nenhuma fixture de negócio: os "dados" do hub são os componentes registrados no painel

### Fakes

Nenhum. Não há e-mail, job, evento nem HTTP nesta feature.

### Estratégia de DB

`RefreshDatabase` global. Seeders no `beforeEach`: `ShieldPermissionsSeeder` + `PapeisSeeder`, nessa ordem — **sem eles a Page nova responde 403 para todo mundo** e todos os cenários falham por um motivo que não é o deles.

### Onde os arquivos vivem

| Suíte | Arquivo | Cenários |
|---|---|---|
| `tests/Kit` | `HubDeCardsTest.php` | CT-01, CT-03, CT-05 |
| `tests/Tenancy` | `HubDeCardsTest.php` | CT-02, CT-04 |
| `tests/Browser` | `HubDeCardsTest.php` | CT-B01 |

---

## Regra R1 — o hub mostra apenas os destinos que o visitante pode acessar

> `RQ-02`, `RQ-06` · perfil **padrão** · técnica: **matriz papel × destino**

Esta é a regra que carrega o risco da entrega. O `CardItem` do pacote **não** verifica
autorização por conta própria — o filtro tem de ser construído, e a falha é silenciosa: o hub
fica bonito, completo, e oferece a todo mundo o caminho para telas que devolvem 403.

**Matriz papel × destino** (painel `/app`, onde a diferença é observável):

| Destino | `master_global` | `panel_user` |
|---|---|---|
| Projetos | visível | **visível** |
| Usuários da organização | visível | **oculto** |
| Convites | visível | **oculto** |
| Convites recebidos | visível | visível |

```gherkin

# language: pt

Funcionalidade: Hub de navegação em cards

  Regra: o hub oferece apenas os destinos que o visitante pode abrir

    Cenário: [CT-01] o administrador da instalação vê os destinos de administração
      Dado um administrador da instalação no painel de administração
      Quando ele abre o hub de navegação
      Então o hub oferece o destino de usuários
      E o hub oferece o destino de papéis

    Cenário: [CT-02] o usuário comum da organização não vê os destinos de administração
      Dado uma organização com um usuário comum vinculado
      Quando esse usuário abre o hub de navegação do painel de negócio
      Então o hub oferece o destino de projetos
      E o hub não oferece o destino de usuários
      E o hub não oferece o destino de convites
```

**Camada**: componente Livewire (`Livewire::test(HubDoNegocio::class)`), precedido de
`Filament::setCurrentPanel(...)` — e, no cenário do `/app`, de `noPainelDa($tenant)`, sem o qual
o `getEloquentQuery()` dos Resources cairia no ramo fail-closed e o cenário mediria outra coisa.

**Assertions**: `assertSee` / `assertDontSee` sobre o **rótulo de navegação** de cada destino.

> **Por que o rótulo e não a URL**: o rótulo é o que o requisito descreve ("grade organizada de
> links"). A URL é verificada em CT-05, que é a regra do destino. Separar evita que um cenário
> falhe por dois motivos.
>
> **Cuidado de discriminação**: `assertDontSee('Usuários')` é frágil se a palavra aparecer no
> layout (menu lateral, breadcrumb). O componente Livewire renderiza **só o componente**, não o
> layout do painel — é por isso que o cenário roda aqui e não em `$this->get()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `CardItem::make(X::class)` sem filtro de autorização — o caminho que o pacote induz | CT-02 |
| M2 | filtro escrito com `auth()->user()->can('ViewAny:X')` em vez de `X::canAccess()`: perde a política do Resource e diverge do que o painel realmente autoriza | CT-02 |
| M3 | filtro aplicado ao **grupo** e não ao **cartão**: some o grupo inteiro quando um destino é negado | CT-02 (o destino "Convites recebidos" continua visível) |
| M4 | filtro invertido (`! canAccess()`) | CT-01 |
| M5 | a própria Page do hub não é excluída e aparece como cartão dentro de si mesma | ⚠️ **sem matador direto** — lacuna declarada. Tentado derivar por `assertDontSee` do título do hub, mas o título aparece no cabeçalho da própria página, então a assertion não discrimina. Coberto indiretamente por CT-B02 (a contagem de cartões na tela) |

---

## Regra R2 — cada painel tem um hub alcançável por quem entra nele

> `RQ-06` · perfil **padrão** · técnica: **EP por painel**

```gherkin
  Regra: quem tem acesso ao painel alcança o hub dele

    Esquema do Cenário: [CT-03] o hub do painel responde a quem tem o papel dele
      Dado uma pessoa com o papel "<papel>"
      Quando ela abre o hub de navegação do painel "<painel>"
      Então a página responde com sucesso
      E a página oferece ao menos um destino

      Exemplos:
        | painel | papel        | # motivo                                       |
        | admin  | admin        | papel de painel, dependente da matriz do Shield |
        | infra  | infra        | idem — é o painel com mais destinos             |
        | admin  | master_global | controle: vence pelo Gate::before               |
```

**Camada**: `Feature` (HTTP) — a rota precisa existir, a permission precisa ter sido gerada, e o
`discoverPages()` precisa ter encontrado a classe. Nada disso é observável por componente.

**Assertions**: `assertSuccessful()` **e** `assertSee` de ao menos um rótulo de destino.

> `assertSuccessful()` sozinho é proibido como oráculo: responde 200 com a grade vazia. A segunda
> assertion é a que prova que a descoberta funcionou.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | Page criada na pasta errada — o `discoverPages()` não a encontra e a rota não existe | CT-03 |
| M7 | seeders não rodados: permission inexistente e 403 para todo mundo que não seja `master_global` | CT-03 (linhas `admin`/`infra`, que **não** passam pelo `Gate::before`) |
| M8 | a descoberta devolve lista vazia (grupo montado com a chave errada) — a tela abre em branco | CT-03 |

---

## Regra R3 — o hub do `/app` pertence ao usuário comum da organização

> `RQ-06` + ADR-05 · perfil **padrão** · técnica: **matriz papel × permission**

A convenção do projeto (`.ai/rules/filament.md`) manda acrescentar toda **Page de administração**
do painel `app` à lista de subtração do `panel_user`. O hub **não** é de administração, e o ADR-05
registra a decisão. Numa subtração o erro é **espelhado**: aplicar a regra onde ela não cabe tira
acesso de quem deveria ter.

```gherkin
  Regra: o hub do painel de negócio faz parte do que o usuário comum recebe

    Cenário: [CT-04] o usuário comum da organização recebe a permissão do hub
      Dado a matriz de papéis semeada
      Quando o papel do usuário comum da organização é consultado
      Então ele tem a permissão de ver o hub do painel de negócio
      E ele não tem a permissão de listar os usuários da organização
```

**Camada**: `Feature`, suíte `tests/Tenancy`.

**Assertions**: as **duas** juntas. A primeira sozinha passaria numa implementação que não subtrai
nada; a segunda sozinha passaria numa que subtrai demais. É o par que fixa a decisão do ADR-05.

> Este cenário é o irmão de `it('mantem o usuario comum fora da administracao da organizacao')`,
> que já existe no kit. Ele documenta em código executável uma decisão que, lida às pressas,
> parece esquecimento da rule.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | o hub é acrescentado a `permissoesDeAdministracaoDoApp()` "por precaução" — o usuário comum leva 403 na página inicial dele | CT-04 (primeira assertion) |
| M10 | a subtração é afrouxada para o hub passar, e leva junto `UserResource` | CT-04 (segunda assertion) |

---

## Regra R4 — o cartão leva ao destino que ele nomeia

> `RQ-02` · perfil **padrão** · técnica: **rastreio de efeito** (URL e agrupamento)

```gherkin
  Regra: o cartão aponta para o destino que ele nomeia, no grupo a que o destino pertence

    Cenário: [CT-05] o cartão de um recurso aponta para a listagem dele
      Dado um administrador de infraestrutura no hub do painel de infraestrutura
      Quando ele lê o cartão de execuções de IA
      Então o cartão aponta para o endereço da listagem de execuções de IA
      E o cartão está sob o grupo de navegação "IA"
```

**Camada**: componente Livewire.

**Assertions**: a URL — obtida de `AiRunResource::getUrl()`, **nunca** escrita como string fixa
(string fixa transforma o cenário em teste do PRD) — e o rótulo do grupo.

> O agrupamento entra aqui porque é o que distingue "grade organizada" de "lista de links soltos",
> que é literalmente o que o requisito pede.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | todos os cartões caem num grupo único, sem rótulo | CT-05 |
| M12 | a URL é montada por concatenação em vez de `getUrl()`, e quebra no painel com tenant (`/app/{tenant}/…`) | CT-05 (e CT-02, que roda no painel com tenant) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| **IDOR / autorização horizontal** | CT-02 — a versão desta feature: cartão de outra alçada oferecido a quem não tem |
| **Autorização exercida na ação (não só `can()`)** | CT-02 — o filtro é exercido na **montagem** da grade, que é a ação desta feature |
| Idempotência | **não se aplica**: nenhuma operação de escrita |
| Concorrência | **não se aplica** |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: a feature não cria nem altera formulário. **Gate de tela de escrita**: a tabela `## Superfície de UI` do PRD não tem rota `create`/`edit` |
| Domínio condicionado | **não se aplica** |
| Estado × operação de escrita | **não se aplica**: o hub não tem ciclo de vida |
| Ausente ≠ null ≠ vazio | **não se aplica**: nenhum campo opcional. Destino sem grupo de navegação: **lacuna declarada** — o kit não tem hoje Resource sem grupo, e criar um só para o teste inventaria superfície |
| Paginação / ordenação | **não se aplica**: a grade não pagina. A **ordem** dos cartões vem de `getNavigationSort()`; sem cenário, porque o requisito não determina ordem — se determinasse, seria BVA de empate |
| Timezone / DST | **não se aplica** |
| Unicode / limite de varchar | **não se aplica**: rótulos são strings do próprio código |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica** |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| **Aparência / CSS (risco do ADR-02)** e **console limpo** | CT-B01 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | administrador vê os destinos de administração | R1 | matriz papel × destino | componente | `tests/Kit/HubDeCardsTest.php` | M4 |
| CT-02 | usuário comum não vê os destinos de administração | R1 | matriz papel × destino | componente | `tests/Tenancy/HubDeCardsTest.php` | M1, M2, M3, M12 |
| CT-03 | o hub responde em cada painel | R2 | EP por painel | Feature | `tests/Kit/HubDeCardsTest.php` | M6, M7, M8 |
| CT-04 | o `panel_user` recebe a permissão do hub e não a de administração | R3 | matriz papel × permission | Feature | `tests/Tenancy/HubDeCardsTest.php` | M9, M10 |
| CT-05 | o cartão aponta para o destino, no grupo certo | R4 | rastreio de efeito | componente | `tests/Kit/HubDeCardsTest.php` | M11, M12 |
| CT-B01 | o cartão tem aparência de cartão | — | pixel | Browser | `tests/Browser/HubDeCardsTest.php` | M-CSS |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| **CT-06 — descoberta sem painel corrente** | **cortado na auditoria Ponytail**: `cardsDoPainel()` só é chamado de uma `CardsPage`, que nunca renderiza fora de request de painel. Guarda para estado inalcançável |
| **CT-B01 antigo — a busca filtra os cartões** | **cortado na auditoria Ponytail**: testa o Alpine do vendor; o único mutante é `$searchable` esquecido em `false`, conferido a olho no roteiro *Desenhado × Implementado* |
| um cenário por painel para a regra R1 | os três exercitam o mesmo filtro; o do `/app` é o único onde as personas divergem de verdade |
| conferir os títulos das telas | vêm do PRD, não do requisito (ver `## Fronteira com o Plano`) |
| conferir `$columns = 3` | apresentação |
| hub para usuário sem papel nenhum | ele não entra em painel algum (`User::canAccessPanel()`), então nunca chega ao hub — o cenário mediria o barramento do painel, já coberto por `tests/Kit/PaineisTest.php` |
| ordem dos cartões dentro do grupo | o requisito não determina ordem |

## Fechamento com Mutation Testing

Escopo útil: **o concern**, que é o único arquivo com lógica de verdade.

```bash
XDEBUG_MODE=coverage vendor/bin/pest tests/Kit/HubDeCardsTest.php --mutate --path=app/Filament/Concerns
```

Mutante esperado como sobrevivente aceitável: nenhum. O filtro por `canAccess()` é um `&&`/`filter`
— exatamente o tipo de código que operador de mutação cobre bem. **Se `filter` invertido sobreviver,
a lacuna é de persona**: significa que algum cenário está rodando com `master_global` onde deveria
usar `panel_user`.

> Antes de rodar: confirmar que `pestphp/pest-plugin-mutate` está declarado no `composer.json`.
> Hoje **não está** — se aparecer em `vendor/`, é dependência transitiva do Pest 5 e some num
> `composer update`. Nesse caso, `composer require pestphp/pest-plugin-mutate --dev` vira passo do PRD.
