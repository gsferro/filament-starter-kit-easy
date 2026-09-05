# Casos de Teste — Fix: conta indisponível não pode ser personificada

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação
> proposta — o vendor foi lido apenas para saber **onde** a régua é consultada (superfície), nunca
> para inferir o comportamento esperado.

> **Os IDs `CT-nn` são desta wiki e começam em `CT-01`.** Eles **não** continuam a numeração de
> `tests/Kit/SituacaoDaContaTest.php`, cujos IDs pertencem à wiki
> `status-e-exclusao-logica-de-usuario`. Ao escrever os testes neste arquivo vizinho, cite o ID
> **com a wiki**: `[impersonate CT-01]`, para os dois conjuntos não colidirem no docblock.

---

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Régua de personificação no model (`User::canBeImpersonated()`) | 3 | 3 | 9 | **completo** |
| Superfície da ação na lista do `/admin` (o `visible()` e a execução) | 2 | 3 | 6 | padrão |

- **P=3 na régua**: regra com múltiplas condições, consumida por um pacote de terceiro em **dois**
  pontos distintos, sobre um model compartilhado pelos três painéis.
- **I=3 nas duas áreas**: é fronteira de **autorização** — o efeito do defeito é um administrador
  operando o sistema **como** uma pessoa que o kit acabou de barrar no login. E **não existe hoje
  nenhum teste de impersonate no kit** (`grep -rln -i impersonat tests/` devolve vazio): este
  conjunto é a primeira cobertura da funcionalidade, então não há rede embaixo.
- **Herança de perfil**: R1, R2, R3 e R4 atravessam as duas áreas e herdam o **maior** perfil —
  **completo**. Consequência prática: BVA não se aplica (não há faixa ordenável), mas a **partição
  do estado é exaustiva**, ela é **cruzada com o papel do alvo**, e **13 das 15 células** da matriz
  estado × ponto-de-consulta são cobertas na **configuração de fábrica** — inclusive a célula
  **válida de cada coluna**. As duas restantes (conta excluída nos dois pontos de tela) só
  discriminam com a premissa **P2** aceita.

- Técnicas aplicadas: **EP exaustiva** (partição do estado da conta **× papel do alvo**), **tabela
  estado × ponto de consulta** (que aqui substitui "estado × operação"), **tabela estado × evento**
  para as três arestas de volta, **sequência de dois eventos (2-switch)** para RQ-03, e **matriz de
  persona** (operador, alvo comum, alvo `master_global` — nunca colapsados).
- **Cenários: 11** · **Regras: 4** · **Mutantes previstos: 19** · **Sem matador: 1**
- **Revisão adversarial**: executada por sub-agente independente — **2 rodadas, 6 achados, todos
  fechados**. Ver [`## Revisão Adversarial`](#revisão-adversarial). Os contadores acima são os do
  conjunto **depois** do fechamento.

### Divergência declarada: rule do projeto × skill

`.ai/rules/testes-browser.md:115` mediu que **sem PCOV o `--tia` não termina** neste ambiente
(abortado após 35 min). O `--mutate` da skill exige **o mesmo driver de cobertura**. Então o
[fechamento do ciclo por mutation score](#fechamento-do-ciclo) fica **bloqueado até haver PCOV** —
a rule vence a skill, e o gate que responde por este conjunto é o **passo 6 (mutantes previstos)**,
não o MSI medido.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | Um método de model (`User::canBeImpersonated()`). Nenhuma migration, nenhum arquivo novo, nenhuma config publicada. A ação `Impersonate::make()` está registrada em `app/Filament/Admin/Resources/Users/UserResource.php:222`, sem `->visible()` próprio; o `/app` não a tem (`:279` do UserResource do App). | CT-01, CT-02 |
| **F**unction | Função administrativa **escondida**: personificar. O que muda é a autorização do **alvo**; a do **operador** (`canImpersonate()` = `isMasterGlobal()`) é declarada fora de escopo no `00`. | CT-01…CT-11 |
| **D**ata | O estado da conta, em **cinco** partições: ativa, inativa (`ativo = false`), pendente (`aprovacao_pendente = true`), excluída (`deleted_at`), e `master_global`. Dado colateral que importa: **o registro excluído continua com `ativo = true`** na coluna (medido pelo CT-03 da wiki ancestral) — é ele que discrimina a implementação que só relê `ativo`. `aprovacao_pendente` é `boolean NOT NULL DEFAULT false` (`database/migrations/2026_08_24_000001_...:46`), então não há terceiro valor. | CT-01, CT-02 |
| **I**nterfaces | Três portas, e as três importam: **(a)** o método do model, chamado direto (é o que qualquer job/comando/API futuro veria); **(b)** o `visible()` da ação, por **linha** da tabela (`vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:37`); **(c)** a **execução** da ação (`:112` → `:167`), que reconsulta antes de entrar. Nenhuma rota, nenhum comando artisan, nenhum job. | (a) CT-01, CT-02, CT-08, CT-09, CT-11 · (b) CT-03, CT-06, CT-08 · (c) CT-04, CT-05, CT-07, CT-10 |
| **P**latform | `config('filament-impersonate.allow_soft_deleted')`, default `false` no vendor e **não publicada** no kit — ela decide **qual guarda** mata a conta excluída, e por isso é parte do arnês de CT-06/CT-07. `ImpersonateManager::enter()` exige guard de **sessão** (`resolveSessionGuard()` lança `RuntimeException` fora de `SessionGuard`) — o `web` do kit é, e por isso CT-05 é expressável. | CT-05, CT-06, CT-07 |
| **O**perations | O operador é **sempre** `master_global` (é o que `canImpersonate()` exige). O método é chamado **uma vez por linha renderizada** — relevante para o log que o plano prevê, irrelevante para o comportamento. Uso indevido que motiva o fix: o administrador entrando numa conta barrada no login. | CT-03…CT-07, CT-10 |
| **T**ime | A régua é **lida por request, não gravada** — é isso que faz RQ-03 sair sem migração de dado, e é o que CT-08 prova em **dois eventos**. As **três** arestas de volta (reativar, aprovar, restaurar) estão em CT-08 e CT-11 — cobrir só a primeira deixava a régua-gravada viva nas outras duas (achado 5). A dimensão que sobra: **sessão de personificação já em curso** quando a conta-alvo é desativada — o `00` declara isso **fora de escopo** ("o requisito pede o *não liberar*, não o *interromper*"). Vira o mutante M10, **sem matador**, lacuna declarada. | CT-08, CT-11 · lacuna M10 |

Nenhuma dimensão ficou em silêncio.

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — Só conta **disponível** é alvo de personificação: inativa, pendente de aprovação e excluída não são — **qualquer que seja o papel do alvo** | régua no model (completo) | RQ-01, RQ-02, premissa **A1** | EP **exaustiva** do estado **× papel do alvo** | CT-01, CT-02 |
| **R2** — A recusa vale nos **dois** pontos que o pacote consulta: a ação **não aparece** na linha **e** a execução **não personifica** | régua + superfície (completo) | RQ-02 | tabela **estado × ponto de consulta** | CT-03…CT-07 |
| **R3** — Conta que **volta a estar disponível** volta a ser alvo — pelas **três** arestas de retorno | régua + superfície (completo) | RQ-03, premissa **A1** | **2-switch** + **tabela estado × evento** | CT-08, CT-11 |
| **R4** — `master_global` **nunca** é alvo, mesmo estando disponível | régua + superfície (completo) | RQ-04 | EP — célula **preservada** | CT-09, CT-10 |

Toda `RQ` do `00` gerou regra. A premissa **A1** (o fix alcança os três estados, não só `ativo`)
alarga R1 — os cenários que dependem dela estão marcados `@premissa A1`; **se A1 for negada, as
linhas `pendente` de CT-01/CT-03/CT-04 e os cenários CT-02, CT-06 e CT-07 saem do conjunto**, e a
conta excluída volta a depender do default de uma config do vendor. A premissa **A2** (a opção
simplesmente não aparece, sem mensagem nova) determina o **não-efeito** de CT-04/CT-07/CT-10, e
está marcada lá.

### A matriz que organiza o conjunto — estado × ponto de consulta

Esta é a matriz do perfil completo, e **as duas metades são cobradas**: toda célula de recusa e,
em cada coluna, **ao menos uma célula válida exercitada**. Cobrir só a coluna (b) deixaria metade
de RQ-02 sem oráculo — é exatamente o falso ✅ que `.ai/rules/filament.md` combate ao dizer que
`->visible()` e a execução **bloqueiam igual, mas não são a mesma barreira**.

| Estado do alvo | (a) `canBeImpersonated()` no model | (b) `visible()` da ação na linha | (c) execução da ação |
|---|---|---|---|
| **ativa** (célula **VÁLIDA** obrigatória) | `true` — **CT-01** | **visível** — **CT-03** | **personifica** — **CT-05** |
| inativa (`ativo = false`) | `false` — CT-01 | oculta — CT-03 | recusa — CT-04 |
| pendente (`aprovacao_pendente = true`) | `false` — CT-01 | oculta — CT-03 | recusa — CT-04 |
| excluída (`deleted_at`) | `false` — CT-02 | oculta — CT-06 ⚠️ **condicional a P2** | recusa — CT-07 ⚠️ **condicional a P2** |
| `master_global` (ativa, não pendente) | `false` — CT-09 | oculta — CT-03, **com o controle "ativo e não pendente" na própria linha** | recusa — CT-10 |

**Contagem honesta: 13 das 15 células na configuração de fábrica do kit** — 15/15 apenas com a
premissa **P2** aceita. As duas células ⚠️ dependem de inverter
`filament-impersonate.allow_soft_deleted` no teste, porque a guarda do vendor mata antes de `:167`;
na config entregue elas **não** discriminam, e a única prova da conta excluída é CT-02, na camada de
model. Declarar 15/15 era um falso ✅ que a própria lacuna L1 já contradizia — **achado 6 da
revisão adversarial**.

**Sem a célula "ativa" o conjunto provaria só recusas, e uma implementação que devolvesse `false`
para todo mundo passaria inteira.** Ela existe nas três colunas: CT-01 (linhas `ativa`), CT-03
(linha `ativa`) e CT-05. **M6 morre em CT-01 e CT-05**; CT-03 cobre a coluna (b) e mata **M8**, não
M6 — creditar M6 a CT-03 era cobertura declarativa sem tabela que a sustentasse (achado 6).

**O papel do alvo é a quarta dimensão desta matriz**, não detalhe do exemplo: CT-01 e CT-03
percorrem o estado com alvo `panel_user` **e** com alvo `admin`. Percorrer só `panel_user` deixava
viva uma régua com allowlist de papel (**M17**) e uma delegada a `canAccessPanel()` (**M18**) — as
duas passariam a matriz inteira (achado 1).

---

## Fronteira com o Plano

O que veio do `01-plano-acao.md` e foi **recusado como oráculo**:

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `motivoDeIndisponibilidade()` como fonte da régua | escolha de implementação (reuso). O requisito determina o **resultado** por estado, não qual método é consultado | detalhe do cenário; nenhum `Então` nomeia o método |
| a ordem das condições (`isMasterGlobal()` primeiro) | escolha de implementação — **nenhuma saída observável depende da ordem** (um master indisponível é recusado nas duas ordens) | descartado; não gera mutante contável |
| `=== null` em vez de `blank()` | escolha de implementação; os três valores possíveis (`'conta_inativa'`, `'conta_excluida'`, `null`) produzem a mesma decisão nas duas formas | descartado |
| o `warning` no channel `autenticacao` | **efeito colateral que só o plano determina** — o `00` não pede rastro nenhum | **pergunta P1**; nenhum CT o assere (lacuna declarada L2) |
| "a ação **não** ganha `->visible()` próprio no Resource" | escolha de implementação. O oráculo é o **comportamento nos dois pontos**, não onde a condição foi escrita | virou CT-03 (ponto b) **e** CT-04/CT-07 (ponto c) — as duas metades, justamente para que a escolha errada apareça |
| A3 — não publicar `config/filament-impersonate.php` | escolha de implementação | virou **arnês**: CT-06/CT-07 invertem a config **no teste**, não no ambiente |
| **"a régua passa a ser a mesma do `canAccessPanel()`"** | é **framing do plano**, não cláusula do `00`: o requisito determina o **veredito por estado**, nunca que os dois métodos são equivalentes. E como oráculo ela é **tautológica** contra a implementação que delega a `canAccessPanel()` — a candidata mais óbvia | **removida** do `Então` de CT-01 na revisão adversarial (achado 2). O oráculo de CT-01 passou a ser o booleano literal por linha, e a delegação virou o mutante **M18** |
| `Log`/`Auth`/`Str` já importados no `User` | detalhe de código | irrelevante para oráculo |

**Perguntas em aberto** — bloco pronto para colagem em
[`## Perguntas para o 00-requisito.md`](#perguntas-para-o-00-requisitomd) no fim deste arquivo.
O `00` está **editável**, mas está sendo consumido por esta derivação como linha de base: as
perguntas ficam aqui, em bloco, e **continuam bloqueando o que dependem delas** (desvio declarado,
conforme a skill).

---

## Setup Global

### Personas — **quatro papéis distintos, nunca colapsados**

Este é o risco nº 1 do conjunto. `canImpersonate()` (operador) é `isMasterGlobal()`; o vendor
recusa `$current->is($target)` em `Impersonate.php:151`. **Um cenário em que operador e alvo são a
mesma pessoa não exercita barreira nenhuma — ele mede a guarda do vendor** e ficaria verde com o
fix inteiro removido.

| Persona | Quem é | Como criar | Por que ela existe |
|---|---|---|---|
| **operador** | quem personifica | `usuarioNoEstado('master_global', 'master@example.com')`, autenticado | `canImpersonate()` exige `master_global`; com `admin` a ação nem apareceria e o cenário mediria o operador, não o alvo |
| **alvo comum** | a conta em cada estado | `usuarioNoEstado('panel_user', 'alvo@example.com', ativo: …)` + o estado aplicado inline | é o eixo da partição de R1 e R2 |
| **alvo de outro papel** | a mesma conta, com papel `admin` | `usuarioNoEstado('admin', 'alvo-admin@example.com', ativo: …)` | **achado 1 da revisão adversarial**. Com o alvo sempre `panel_user`, uma régua com allowlist de papel (M17) ou delegada a `canAccessPanel('app')` (M18) passava o conjunto inteiro. `admin` é o papel discriminante porque ele **não** acessa o painel `app` |
| **alvo master** | `master_global` **como alvo** | `usuarioNoEstado('master_global', 'master-alvo@example.com')` — **ativo e não pendente** | RQ-04. Se o alvo master estivesse inativo, o cenário passaria pela condição **nova** e não provaria a **preservada** |
| **controle na linha** | um segundo alvo **ativo** no mesmo render | `usuario('ativo@example.com')` | a linha visível ao lado da oculta, no mesmo HTML: mata "a tela toda perdeu as ações" |

### Fixtures — os cinco estados

| Estado | Arranjo | Nota |
|---|---|---|
| ativa | `usuarioNoEstado($papel, $email)` | `ativo` default `true`, `aprovacao_pendente` default `false` |
| inativa | `usuarioNoEstado($papel, $email, ativo: false)` | helper local já existente (`SituacaoDaContaTest.php:32-41`) |
| pendente | `$alvo->forceFill(['aprovacao_pendente' => true])->save()` | exatamente como o CT-30 da wiki ancestral faz |
| excluída | `$alvo->delete()`; no model, reler com `User::withTrashed()->findOrFail($id)` | como o CT-03 da wiki ancestral. **Assere `ativo === true` como controle** |
| `master_global` alvo | `usuarioNoEstado('master_global', …)` | ativo e não pendente, sempre |

### Arranjo da tela

Reutilizar o helper local **já existente** `administradorNaListaDeUsuarios()`
(`SituacaoDaContaTest.php`), **parametrizando o papel** —
`administradorNaListaDeUsuarios(string $papel = 'admin'): User` — e chamando-o com
`'master_global'` nos cenários de impersonate.

**Não criar um clone** (`masterNaListaDeUsuarios()`): `.ai/rules/testes.md` é explícita — clone com
outro nome "troca um erro que estoura por duas funções idênticas que ninguém percebe". E o helper
**continua local**, porque só este arquivo o usa; no dia em que um segundo arquivo usar, ele vai
para `tests/Pest.php` (mesma rule).

Ele já faz o que um teste de componente não faz por si: `actingAs`, `noPainelDoShield('admin')` e
`noPainelBootado('admin')`. Toda tabela do kit carrega adiada, então **`->loadTable()` é
obrigatório** antes de qualquer asserção de linha ou de ação.

### Fakes

Nenhum. Não há e-mail, job, evento nem notificação no requisito. `espiarAutenticacao()` **existe**
no `tests/Pest.php` e **não é usado por nenhum CT** — porque o log é do plano, não do requisito
(lacuna L2 / pergunta P1).

### Estratégia de DB

`RefreshDatabase` global, aplicado a `tests/Kit` por `tests/Pest.php:57`. O `beforeEach` do arquivo
já semeia `ShieldPermissionsSeeder` + `PapeisSeeder` — nada a acrescentar.

### Camada — e por que **nenhum** cenário é `Unit`

`tests/Pest.php` liga `TestCase::class` a `Feature`, `Kit`, `Tenancy`, `Browser` e
`BrowserTenancy` — **`tests/Unit` não tem `pest()->extend()` nenhum**. Um caso "unitário" ali roda
sem container, e todos estes precisam de banco (papéis do spatie, soft delete, sessão). A camada
mais barata **que o arnês do projeto sustenta** é `tests/Kit`.

**Arquivo único: `tests/Kit/SituacaoDaContaTest.php`** (o vizinho que o `01` indica), numa seção
nova ao fim.

---

## Regra R1 — Só conta disponível é alvo de personificação

> `RQ-01`, `RQ-02`, premissa **A1** · perfil **completo** · técnica: **EP exaustiva do estado × papel do alvo**
> Ponto de consulta exercitado: **(a) o método do model, chamado direto** — a única camada em que a
> régua do kit é observável **sem nenhuma guarda do vendor na frente**.

```gherkin
# language: pt

Funcionalidade: Personificação respeita o estado da conta-alvo

  Regra: Só conta disponível é alvo de personificação

    @premissa A1
    Esquema do Cenário: [CT-01] a régua do alvo recusa toda conta indisponível, em qualquer papel
      Dado um alvo com o papel "<papel>" no estado <estado>
      Quando se pergunta ao model se ele pode ser alvo de personificação
      Então a resposta é <personificavel>

      Exemplos:
        | papel      | estado   | personificavel | # partição                      |
        | panel_user | ativa    | verdadeiro     | VÁLIDA — obrigatória            |
        | panel_user | inativa  | falso          | ativo = false                   |
        | panel_user | pendente | falso          | aprovacao_pendente              |
        | admin      | ativa    | verdadeiro     | VÁLIDA — outro papel de alvo    |
        | admin      | inativa  | falso          | papel × estado: allowlist morre |

    @premissa A1
    Cenário: [CT-02] a conta excluída não é alvo, e a coluna "ativo" dela ainda diz "true"
      Dado um alvo que foi excluído logicamente e recarregado com os excluídos incluídos
      E que a coluna "ativo" desse alvo continua verdadeira
      Quando se pergunta ao model se ele pode ser alvo de personificação
      Então a resposta é falso
```

**Camada**: `tests/Kit` · **API**: `expect($alvo->canBeImpersonated())->toBeFalse()`;
`User::withTrashed()->findOrFail($id)` em CT-02.

**Discriminância de CT-01 — e o que a revisão adversarial mudou aqui.** A versão anterior tinha um
quarto passo, *"E ela é igual à resposta de 'pode entrar no painel do próprio papel'"*, que foi
**removido**: ele compara a régua com a expressão que a implementação candidata mais óbvia
(`! isMasterGlobal() && canAccessPanel(...)`) **chama por dentro**, e por isso não pode falhar
contra ela. Era oráculo tautológico, e vinha do plano, não do `00` (achados 1 e 2).

O que discrimina agora é a **coluna do papel**, e as duas últimas linhas do `Exemplos` são o ponto:
- `admin` **ativa → verdadeiro** mata a allowlist de papel (**M17**) e a delegação a
  `canAccessPanel('app')` (**M18**): um alvo `admin` não acessa o painel `app`, então as duas
  implementações devolvem `false` onde o requisito exige `true`.
- `admin` **inativa → falso** é o par dela: sem essa linha, uma régua que simplesmente liberasse
  todo `admin` passaria a anterior.

O papel do alvo é dimensão porque `RQ-01` fala de "o usuário", sem qualificar papel. `admin` existe
nas duas suítes (`.ai/rules/testes.md`), então as linhas rodam em `tests/Kit`.

**Discriminância de CT-02** — a segunda linha do `Dado` é o coração do cenário. O registro excluído
**mantém `ativo = true`** (medido pelo CT-03 da wiki ancestral). Logo, uma implementação que releia
`ativo` em vez de perguntar pela indisponibilidade **libera a conta excluída**, e só este cenário
enxerga isso. É também a única camada em que a conta excluída é observável com a config do vendor no
**default** — ver [a nota de discriminância da conta excluída](#discriminância-da-conta-excluída).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | nada mudou: `return ! $this->isMasterGlobal();` (o defeito atual) | CT-01 (linhas `inativa` e `pendente`), CT-02 |
| M2 | leitura literal de "status desativado": só `ativo` é consultado | CT-01 (linha `pendente`) **e CT-02** (a excluída tem `ativo = true`) |
| M3 | `&&` → `\|\|` entre indisponibilidade e pendência | CT-01 (linhas `inativa` **e** `pendente` — uma só não basta: cada uma satisfaz um lado do OR) |
| M4 | as condições novas **substituem** a guarda do `master_global` | CT-09 |
| M5 | a condição foi escrita em `canImpersonate()` (o **operador**) em vez de `canBeImpersonated()` (o **alvo**) | CT-01 (linha `inativa`), CT-04 |
| M6 | negação invertida: recusa quando há disponibilidade | CT-01 (**linhas `ativa`** — as células válidas), CT-05 |
| M17 | **allowlist de papel**: `hasRole('panel_user') && …` — o estado só é consultado para usuário de painel, e nenhum `admin` é personificável | CT-01 (linha `admin`/`ativa`), CT-03 (linha `admin`) — *achado 1* |
| M18 | **delegação ao painel**: `! isMasterGlobal() && canAccessPanel(getPanel('app'))`, leitura literal de "a mesma régua do login" | CT-01 (linha `admin`/`ativa`) — *achado 1; o oráculo que a legitimava caiu no achado 2* |

---

## Regra R2 — A recusa vale nos dois pontos que o pacote consulta

> `RQ-02` · perfil **completo** · técnica: **tabela estado × ponto de consulta**
> Pontos exercitados: **(b)** o `visible()` da ação na linha (`Impersonate.php:37`) e **(c)** a
> execução (`:112` → `:167`).

```gherkin
# language: pt

Funcionalidade: Personificação respeita o estado da conta-alvo

  Regra: A recusa vale tanto na linha da tabela quanto na execução da ação

    @premissa A1
    Esquema do Cenário: [CT-03] a ação Personificar aparece na linha do alvo disponível e não na do indisponível
      Dado um master global autenticado na lista de usuários do /admin
      E um alvo com o papel "<papel>" no estado <estado>, listado na tabela
      E que esse alvo está disponível em todos os atributos que a linha não altera
      Quando a lista de usuários é renderizada
      Então a linha do alvo está na tabela
      E a ação "impersonate" existe na definição dessa linha
      E a ação "impersonate" está <visibilidade> nessa linha
      E a ação "edit" continua visível nessa mesma linha

      Exemplos:
        | papel         | estado   | visibilidade | # célula                                  |
        | panel_user    | ativa    | visível      | VÁLIDA — obrigatória                      |
        | panel_user    | inativa  | oculta       | ativo = false                             |
        | panel_user    | pendente | oculta       | aprovacao_pendente                        |
        | admin         | inativa  | oculta       | papel × estado no ponto (b)                |
        | master_global | ativa    | oculta       | RQ-04 no ponto (b), com o alvo DISPONÍVEL |

    @premissa A1 @premissa A2
    Esquema do Cenário: [CT-04] disparar a ação sobre conta indisponível não personifica ninguém
      Dado um master global autenticado na lista de usuários do /admin
      E um alvo no estado <estado> listado na tabela
      E que a ação "impersonate" existe na definição da linha desse alvo
      Quando o master global dispara a ação "impersonate" na linha desse alvo
      Então o usuário autenticado continua sendo o master global
      E não há personificação em curso na sessão
      E nenhuma notificação foi exibida

      Exemplos:
        | estado    | # célula            |
        | inativa   | ativo = false       |
        | pendente  | aprovacao_pendente  |

    Cenário: [CT-05] o master global personifica de fato uma conta ativa
      Dado um master global autenticado na lista de usuários do /admin
      E um alvo ativo, com o papel "panel_user", listado na tabela
      Quando o master global dispara a ação "impersonate" na linha desse alvo
      Então o usuário autenticado passa a ser o alvo
      E a sessão registra o master global como personificador

    @premissa A1 @premissa P2
    Cenário: [CT-06] a conta excluída não oferece a ação nem com a permissão de soft-deleted ligada
      Dado que o valor efetivo lido de "filament-impersonate.allow_soft_deleted" é verdadeiro
      E um master global autenticado na lista de usuários do /admin com a lixeira incluída
      E um alvo excluído e um alvo ativo, ambos listados
      Quando a lista de usuários é renderizada
      Então a ação "impersonate" está oculta na linha do alvo excluído
      E a ação "impersonate" está visível na linha do alvo ativo

    @premissa A1 @premissa A2 @premissa P2
    Cenário: [CT-07] disparar a ação sobre a conta excluída não personifica, nem com a permissão de soft-deleted ligada
      Dado que o valor efetivo lido de "filament-impersonate.allow_soft_deleted" é verdadeiro
      E um master global autenticado na lista de usuários do /admin com a lixeira incluída
      E um alvo excluído listado na tabela
      E que a ação "impersonate" existe na definição da linha desse alvo
      Quando o master global dispara a ação "impersonate" na linha desse alvo
      Então o usuário autenticado continua sendo o master global
      E não há personificação em curso na sessão
      E nenhuma notificação foi exibida
```

**Camada**: `tests/Kit`, componente Livewire.
**API confirmada no vendor** (`vendor/filament/actions/.stubs.php:30-34` e
`vendor/filament/actions/src/Testing/TestsActions.php:138,217,231`):
`assertActionExists`, `assertActionVisible`, `assertActionHidden`, `callAction` — todas com
`TestAction::make('impersonate')->table($alvo)`. **Nunca** `assertTableActionExists` nem
`callTableAction`: são `@deprecated` e `tests/Kit/AderenciaAoBlueprintTest.php` reprova.
Estado da sessão: `session(\STS\FilamentImpersonate\ImpersonateManager::SESSION_KEY)` — a constante,
não a string `'impersonated_by'` literal.

### Os dois controles que a revisão adversarial acrescentou aqui

**1. Controle positivo antes do `Quando` (achado 3).** CT-04, CT-07 e CT-10 provam **recusa**, e
recusa se demonstra por ausência: *"continua autenticado como o operador"*, *"não há
personificação em curso"*. Essas duas asserções passam **idênticas** se o `callAction` nunca
alcançou a ação — `TestAction` que não casa com a linha, registro que a tabela não resolve
(risco real, ver Armadilha 4). Nesse caso o cenário fica verde medindo **nada**, que é a forma mais
barata de um conjunto de recusas parecer cobertura.

Por isso os três ganharam `E que a ação "impersonate" existe na definição da linha desse alvo`
(`assertActionExists`) **antes** do `Quando`: ela falha se a linha não resolveu, separando "a
barreira funcionou" de "o teste não chegou lá". É a mesma dívida que o CT-19 da wiki ancestral
paga com `assertActionExists` + `assertActionHidden` na sequência.

**2. A config tem de ser lida, não suposta (achado 4).** A discriminância **inteira** de CT-06 e
CT-07 depende de a inversão de `allow_soft_deleted` ter surtido efeito — se ela não pegar (config
resolvida no boot, cache, ação já instanciada), a guarda do vendor volta a matar antes de `:167` e
os dois cenários **passam com o defeito intacto, em silêncio**. O `Dado` deixou de dizer "está
ligada" e passou a afirmar o **valor efetivo lido**, exatamente como a skill exige de qualquer
cenário cuja premissa é ambiental:
`expect(config('filament-impersonate.allow_soft_deleted'))->toBeTrue()` no arranjo.
Lixeira: `->filterTable('trashed', true)` (`TrashedFilter` é `TernaryFilter`, `true` → `withTrashed()`).

### Por que CT-04/CT-07 exercitam o ponto (c) e não o (b) — verificado no vendor

**`callAction` não respeita `visible()`.** `mountAction()`
(`vendor/filament/actions/src/Concerns/InteractsWithActions.php:138-162`) checa `isDisabled()` e a
notificação de autorização, e `callMountedAction()` (`:232-253`) checa `isDisabled()` e
`isAuthorized()` — **nenhum dos dois consulta `isVisible()`**, e `resolveActions()` (`:537`) também
não. A ação oculta é resolvida e o `->action()` dela **roda**, caindo no `impersonate()` do vendor,
que reconsulta em `:112` → `:167`.

Isso é o que torna CT-04 e CT-07 discriminantes: com a guarda escrita apenas num `->visible()` no
Resource (mutante M7), CT-03 ficaria **verde** e CT-04/CT-07 ficariam **vermelhos** — que é
exatamente a metade de RQ-02 que um conjunto só de `assertActionHidden` deixa sem oráculo.

**Se numa versão futura o mount passar a barrar a ação oculta**, CT-04/CT-07 deixam de discriminar
e migram para a chamada direta do mesmo ponto: `Impersonate::make()->impersonate($alvo)`, cujo
retorno é `false` na recusa. Registrar o desvio se acontecer.

### Discriminância da conta excluída

Um cenário de conta excluída **pode passar pela guarda do vendor, não pela correção do kit** —
e o conjunto tem de dizer como distingue as duas.

`Impersonate::canImpersonate()` recusa soft-deleted em
`vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:159-161`, **antes** de
consultar `canBeImpersonated()` em `:167` (o `00` cita `:157-159`; a numeração verificada agora é
`:159-161` — mesma guarda). Essa recusa depende de
`config('filament-impersonate.allow_soft_deleted')`, default `false` no vendor
(`config/filament-impersonate.php:12`) e **config não publicada** no kit.

Consequência, e ela é dura:

| Cenário | Com a config no **default** (`false`) | Como este conjunto resolve |
|---|---|---|
| ação oculta na linha do excluído (ponto b) | passa **mesmo com o defeito** — a guarda do vendor mata antes | CT-06 roda com `config(['filament-impersonate.allow_soft_deleted' => true])`, **desligando a guarda do vendor**; o único barrador que resta é o do kit |
| execução recusada no excluído (ponto c) | idem | CT-07, mesma inversão de config |
| a régua do model (ponto a) | discrimina sempre — **o model não lê essa config** | CT-02, sem inversão nenhuma |

O arnês **foi mudado** antes de declarar impossibilidade, como a skill exige: a inversão da config
é uma linha, e é ela que faz CT-06/CT-07 matarem M9. E a inversão não é artificial — é literalmente
o risco que o `00` descreve: *"basta alguém escrever `FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED=true`
no `.env`"*. **Está marcada `@premissa P2`** porque o `00` a narra como risco, sem transformá-la em
cláusula `RQ`.

**Lacuna declarada L1**: com a config no **default**, nenhum cenário de UI distingue kit de vendor
na conta excluída. Não é mutante sem matador — M9 morre em CT-06/CT-07 — mas é o motivo pelo qual a
prova na configuração de fábrica vive **só** em CT-02, na camada de model. Se P2 for negada
("basta o default do vendor"), CT-06 e CT-07 saem e M9 fica **sem matador**.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | a guarda vive **só** num `->visible()` da ação no `UserResource`: esconde o botão e não barra a execução | CT-04, CT-07 (o ponto **c**) |
| M8 | a conta indisponível é **recortada da listagem** (`getEloquentQuery()`/filtro): a ação "desaparece" porque a **linha** desaparece, e a execução segue funcionando | CT-03 (que afirma que **a linha está na tabela** e que a ação **existe** e está **oculta**) + CT-04 |
| M9 | a conta excluída fica protegida apenas pelo default de `allow_soft_deleted`, sem régua própria | CT-06, CT-07 (com a config invertida) + CT-02 |
| M10 | a régua é lida **só na entrada**: a sessão de personificação já em curso sobrevive à desativação da conta-alvo | ⚠️ **sem matador** — **fora de escopo declarado** no `00` (*"o requisito pede o não liberar, não o interromper"*). Lacuna declarada; ver pergunta **P3** |

---

## Regra R3 — Conta reativada volta a ser alvo

> `RQ-03` · perfil **completo** · técnica: **2-switch — sequência de dois eventos**, com o oráculo
> sobre o resultado do **segundo**.

Um cenário só com o estado final não prova a volta: ele é indistinguível de CT-01 linha `ativa`.
A ida **e** a volta precisam estar no mesmo cenário, sobre o **mesmo registro persistido** — e o
primeiro evento entra como `Dado` com asserção de **controle**, para o `Quando` continuar único
(é o padrão que o CT-20 da wiki ancestral já usa).

```gherkin
# language: pt

Funcionalidade: Personificação respeita o estado da conta-alvo

  Regra: Conta reativada volta a ser alvo de personificação

    Cenário: [CT-08] a conta desativada e depois reativada volta a oferecer a ação
      Dado um master global autenticado na lista de usuários do /admin
      E um alvo que estava ativo, foi desativado, e cuja personificação passou a ser recusada
      E que a ação "impersonate" está oculta na linha dele nesse momento
      Quando o master global reativa esse alvo pela ação "reativar" da mesma lista
      Então a régua do model, lida num alvo RECARREGADO do banco, volta a responder que ele pode ser alvo
      E a ação "impersonate" volta a estar visível na linha dele, no mesmo render

    @premissa A1
    Esquema do Cenário: [CT-11] as outras duas arestas de volta também devolvem o alvo
      Dado um alvo indisponível por <estado>, cuja personificação é recusada
      Quando o administrador aplica o evento <evento> sobre ele
      Então a régua, lida num alvo RECARREGADO do banco, volta a responder verdadeiro

      Exemplos:
        | estado    | evento     | # aresta de retorno              |
        | pendente  | aprovar    | aprovacao_pendente → aprovado |
        | excluída  | restaurar  | deleted_at → restaurado       |
```

**Camada**: `tests/Kit`, componente Livewire — um único `Livewire::test(ListUsers::class)` que
assere o estado oculto, chama `callAction(TestAction::make('reativar')->table($alvo))` e reassere a
visibilidade, como o CT-20 da wiki ancestral faz.

**Nota de arranjo**: o operador é `master_global`, e ele precisa da permissão `Reativar:User` para
disparar `reativar`. O `PapeisSeeder` a entrega ao `admin` (CT-24 da wiki ancestral); o
`master_global` passa pelo `Gate::before`. Se na execução o `reativar` não aparecer para o master,
o desvio a registrar é usar **dois** operadores no cenário (um `admin` que reativa, um
`master_global` que lê a linha) — o que **não** colapsa persona: são papéis distintos por desenho.

**CT-11 roda na camada de model** (`tests/Kit`), não por componente: `aprovar` e `restaurar`
mudam o estado e a pergunta é sobre a régua, não sobre a linha. Isso também evita o problema de
resolver um registro excluído dentro da tabela (Armadilha 4). A transição usa os métodos que o kit
já tem — a ação `aprovar` da lista do `/admin` e o `restore()` do `SoftDeletes`, cujas Actions
vivem no mesmo `recordActions()` do Resource.

**Discriminância — e as duas correções da revisão adversarial (achado 5).**

As duas linhas do `Então` de CT-08 cobrem os dois pontos: a primeira mata a régua que virou
**estado gravado**; a segunda mata a régua **memoizada**, porque o `visible()` é reavaliado no
mesmo ciclo de vida do componente, depois da transição. Faltavam duas coisas:

1. **A instância tem de ser recarregada.** "A régua volta a responder que ele pode ser alvo" não
   dizia **em qual instância**. Contra uma implementação memoizada (`$this->personificavel ??= …`),
   o cenário passava ou falhava **por sorte** do jeito que o teste fosse escrito — oráculo
   indefinido é mutante vivo por acidente. O `Então` agora exige `$alvo->fresh()` (ou
   `User::withTrashed()->findOrFail($id)` em CT-11), e é isso que fecha **M12**.
2. **A máquina de estado tem três arestas de volta, não uma.** `RQ-03` nomeia a reativação, mas a
   premissa **A1** trouxe pendente e excluída para R1 — e com elas vieram `aprovar` e `restaurar`.
   Cobrir só `reativar` deixava viva a régua gravada que **só o `reativar()` limpa**: o alvo
   aprovado e o alvo restaurado ficariam permanentemente fora de alcance, sem nada vermelho. CT-11
   fecha as duas células e mata **M19**.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | a recusa é **gravada** (coluna, flag ou cache) no `desativar()`, e o `reativar()` não a limpa | CT-08 (primeira linha do `Então`, com o alvo recarregado) |
| M12 | a régua é **memoizada** na instância/request, e a reativação não é refletida no mesmo ciclo | CT-08 (segunda linha do `Então`) + CT-08/CT-11 exigindo instância **recarregada** — *achado 5* |
| M13 | a régua passa a exigir algo que a reativação **não restaura** (ex.: `aprovacao_pendente` ligado por engano no `desativar()`) — o alvo reativado nunca volta | CT-08 |
| M19 | a recusa é gravada e **só o `reativar()` a limpa**: o alvo aprovado e o alvo restaurado nunca voltam a ser personificáveis | CT-11 (as duas linhas) — *achado 5* |

---

## Regra R4 — `master_global` nunca é alvo, mesmo estando disponível

> `RQ-04` · perfil **completo** · técnica: **EP — a célula preservada**
> Regra existente que **não pode regredir**. O alvo master é **ativo e não pendente** de propósito:
> se ele estivesse indisponível, o cenário passaria pela condição **nova** e a **preservada** ficaria
> sem oráculo — é a mesma armadilha do valor redondo, na versão de estado.

```gherkin
# language: pt

Funcionalidade: Personificação respeita o estado da conta-alvo

  Regra: O master global nunca é alvo de personificação

    Cenário: [CT-09] o master global disponível continua fora de alcance no model
      Dado um alvo com o papel "master_global", ativo, sem aprovação pendente e não excluído
      E que ele satisfaz o MESMO predicado de disponibilidade dos alvos que CT-01 aceita
      Quando se pergunta ao model se ele pode ser alvo de personificação
      Então a resposta é falso

    @premissa A2
    Cenário: [CT-10] disparar a ação sobre outro master global não personifica ninguém
      Dado um master global autenticado na lista de usuários do /admin
      E um segundo master global, ativo e sem aprovação pendente, listado na tabela
      E que a ação "impersonate" existe na definição da linha do segundo
      Quando o primeiro dispara a ação "impersonate" na linha do segundo
      Então o usuário autenticado continua sendo o primeiro master global
      E não há personificação em curso na sessão
      E nenhuma notificação foi exibida
```

**Camada**: CT-09 em `tests/Kit` (model); CT-10 em `tests/Kit`, componente Livewire.

**Discriminância de CT-09** — a segunda linha do `Dado` é a que separa "recusado por ser master" de
"recusado por estar indisponível". Ela mudou na revisão adversarial: antes afirmava
`canAccessPanel('admin')`, que é um predicado **de outro painel** e não é o que as linhas de CT-01
satisfazem — contra o mutante que troca a guarda do master pelo predicado de disponibilidade, o
controle no painel errado não separava nada. Agora ela afirma **o mesmo predicado** das linhas
aceitas de CT-01 (`ativo`, sem pendência, não excluído), que é o observável do requisito, e o
alvo master carrega também o papel do alvo comum para que nenhuma variação por papel o salve.

**Persona**: dois `master_global` **distintos**. O vendor recusa `$current->is($target)` em `:151`,
então usar o **mesmo** master como operador e alvo mediria a guarda do vendor e ficaria verde com
RQ-04 removida por inteiro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | as condições de disponibilidade **substituem** a guarda do master ("consolidei tudo numa expressão") | CT-09, CT-10 |
| M15 | `&&` → `\|\|` na condição do master: `! isMasterGlobal() \|\| disponível` — o master ativo volta a ser alvo | CT-09 |
| M16 | a guarda do master é movida para o **operador** (`canImpersonate()`), sob a leitura "master não personifica" | CT-09, CT-10 |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou
> `lacuna declarada: {o que foi tentado}`. **Nunca "sim".**

| Item | Cenário que mata |
|---|---|
| **IDOR / autorização horizontal** (recurso de outro por `{id}`) | não se aplica: **o operador está fora de escopo** (`00` § Fora de Escopo). A barreira do operador é `canImpersonate()` = `isMasterGlobal()`, inalterada — cobri-la aqui provaria código que esta entrega não toca |
| **Autorização exercida na ação, não só no `can()`** | CT-04, CT-07, CT-10 — o disparo real, atravessando `mountAction`, que **não** consulta `isVisible()` |
| **Autorização declarada em policy/permission** | não se aplica: nenhuma policy nem permission nova. `Impersonate::make()` não declara `->authorize()` hoje, e mudar isso é decisão do operador (fora de escopo) |
| **Idempotência (ancorada no agregado)** | não se aplica: a operação **não grava em agregado nenhum** — o efeito é sessão, e a segunda entrada já é recusada pelo vendor em `:155-157`. Ancorar em sessão produziria caso tautológico (ver [cogitado e cortado](#cogitado-e-cortado)) |
| **Concorrência** | não se aplica: sem contador, saldo, estoque ou limite de uso |
| **Campo cujo domínio depende de outro** | CT-02 — `ativo` × `deleted_at`: o excluído **mantém `ativo = true``**, e é essa combinação que mata M2 |
| **Fronteira no ponto de entrada (gravação)** | não se aplica: o fix não acrescenta campo nem valida entrada. A gravação do estado é da wiki ancestral (CT-35 de lá prova que `ativo` não é atribuível em massa) |
| **Estado × operação de escrita ("o excluído ainda funciona?")** | CT-07 — a operação de escrita sobre a conta excluída é recusada, e não apenas escondida |
| **Criação ≠ edição ≠ uso** | criação e edição do estado: wiki ancestral (não se aplica aqui). **Uso**: CT-01…CT-07. **Edição → uso** (o ponto que costuma faltar): **CT-08**, que altera o estado e reavalia o uso no mesmo ciclo |
| **Ausente ≠ `null` ≠ `""`** | não se aplica: `aprovacao_pendente` é `boolean NOT NULL DEFAULT false`; não existe terceiro valor a distinguir |
| **Paginação / ordenação** | não se aplica: nenhuma listagem nova, nenhuma ordenação tocada |
| **Timezone / DST** | não se aplica: nenhuma comparação temporal. `deleted_at` é testado por **presença**, nunca por valor — logo não há janela de divergência de fuso em que duas implementações difiram |
| **Unicode / limite de varchar / texto livre** | não se aplica: nenhum campo de texto entra na decisão |
| **Unicidade + soft delete** | não se aplica: sem chave única nova. A interação com soft delete que **existe** está em CT-02, CT-06, CT-07 |
| **CRUD combinado (ID inexistente, excluir duas vezes, editar sem alterar)** | não se aplica: nenhum CRUD novo. O caso "alvo inexistente" é recusado pelo vendor em `:151` (`blank($target)`) |
| **Mass assignment** | não se aplica: nenhum campo novo no formulário; `ativo` já não é `fillable` (CT-35 da wiki ancestral) |
| **Upload** | não se aplica: sem upload |
| **Precisão monetária** | não se aplica: sem valor numérico |
| **Efeito colateral (log da recusa)** | **lacuna declarada L2**: o `01` prevê um `warning` no channel `autenticacao`; o `00` **não pede rastro nenhum**. Tentado: `espiarAutenticacao()` já existe e serviria de arnês — o que falta é **oráculo no requisito**, não arnês. Ver pergunta **P1** |
| **Persona colapsada** | CT-04, CT-05, CT-07, CT-08, CT-10 usam **operador ≠ alvo**; CT-10 usa **dois `master_global` distintos**. Nenhum cenário do conjunto tem operador igual ao alvo |
| **Papel do alvo fixado** (a dimensão irmã da persona) | CT-01 (linhas `admin`), CT-03 (linha `admin`), CT-09 (o master também carrega o papel do alvo comum). Era **lacuna cega** até a revisão adversarial: o alvo era `panel_user` em 100% dos cenários |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| um `admin` (não-master) não vê a ação Personificar | prova `canImpersonate()`, o **operador** — fora de escopo declarado no `00`. Nenhum estado da conta-alvo muda essa resposta |
| o operador tenta personificar **a si mesmo** | recusado pelo vendor em `Impersonate.php:151` (`$current->is($target)`) — provaria o vendor, e ficaria verde com o fix inteiro removido |
| personificar duas vezes seguidas (duplo clique) | recusado pelo vendor em `:155-157` (`isImpersonating()`). Idempotência aqui é **inexpressável**: não há agregado persistido a ancorar, só sessão, e o `Então` sobre sessão repetiria a asserção de CT-05 |
| a ação Personificar **não existe** no `/app` | decisão anterior já registrada (`app/Filament/App/Resources/Users/UserResource.php:279`) e **independente do estado da conta** — nenhum mutante desta entrega a alcança |
| conta excluída pela **tela**, com a config no **default** | **não discrimina**: a guarda do vendor (`:159-161`) mata antes de `:167`, e o cenário passaria com o defeito intacto. Substituído por CT-02 (model, sempre discriminante) + CT-06/CT-07 (config invertida) |
| o `warning` de recusa no channel `autenticacao` | sem cláusula no `00` — seria teste do PRD. Vira pergunta **P1** e lacuna **L2** |
| um caso de browser abrindo `/admin/users` e olhando a linha | o gate do `05` não passa (ver [`## Sem CT-B`](#sem-ct-b)) |

Teto do perfil **completo** (5 por regra): R1 = 2, **R2 = 5**, R3 = 2, R4 = 2. Nenhum estouro.
As linhas acrescentadas pela revisão adversarial (papel do alvo em CT-01/CT-03) entraram em
`Exemplos` de cenários existentes, e **um `Esquema do Cenário` conta como 1** — só CT-11 é cenário
novo, e ele existe porque o gate de mutantes vence o teto.
R2 usa o teto inteiro porque a técnica dela é a matriz de **dois pontos × quatro estados** — é o
custo declarado da técnica, não inflação.

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | régua do model por estado × papel do alvo (5 linhas) | R1 | EP exaustiva estado × papel | `tests/Kit` (model) | `tests/Kit/SituacaoDaContaTest.php` | M1, M2, M3, M5, M6, M17, M18 |
| CT-02 | conta excluída não é alvo, com `ativo = true` de controle | R1 | EP + domínio condicionado | `tests/Kit` (model) | idem | M1, M2, M9 |
| CT-03 | a ação na linha, por estado × papel (ponto **b**) | R2 | estado × ponto de consulta | `tests/Kit` (Livewire) | idem | M8, M17 |
| CT-04 | disparo recusado em conta inativa e pendente (ponto **c**) | R2 | estado × ponto de consulta | `tests/Kit` (Livewire) | idem | M5, M7, M8 |
| CT-05 | o master personifica de fato a conta ativa (**célula válida**) | R2 | célula válida obrigatória | `tests/Kit` (Livewire) | idem | M6 |
| CT-06 | excluída: ação oculta com `allow_soft_deleted = true` | R2 | estado × ponto de consulta + arnês de config | `tests/Kit` (Livewire) | idem | M9 |
| CT-07 | excluída: disparo recusado com `allow_soft_deleted = true` | R2 | estado × ponto de consulta + arnês de config | `tests/Kit` (Livewire) | idem | M7, M9 |
| CT-08 | desativar → recusa → reativar → volta a ser alvo | R3 | 2-switch | `tests/Kit` (Livewire) | idem | M11, M12, M13 |
| CT-09 | master ativo continua fora de alcance no model | R4 | EP — célula preservada | `tests/Kit` (model) | idem | M4, M14, M15, M16 |
| CT-10 | disparo de master sobre master não personifica | R4 | EP — célula preservada | `tests/Kit` (Livewire) | idem | M14, M16 |
| CT-11 | aprovar e restaurar também devolvem o alvo | R3 | tabela estado × evento | `tests/Kit` (model) | idem | M12, M19 |

**Cobertura de mutantes**: 19 previstos, **18 com matador**, **1 sem** (M10 — fora de escopo
declarado). Nenhum cenário do conjunto deixa de matar ao menos um mutante.

**Rastreabilidade `RQ` → cenário**:

| RQ | Cenários |
|---|---|
| RQ-01 (inativa não é personificável, ação não aparece) | CT-01, CT-03 |
| RQ-02 (a recusa vale fora da tela também) | CT-04, CT-05, CT-07 — **e é só aqui que o ponto (c) tem oráculo** |
| RQ-03 (reativada volta) | CT-08 — e CT-11 para as outras duas arestas trazidas por A1 |
| RQ-04 (`master_global` nunca é alvo) | CT-09, CT-10 |
| A1 (pendente e excluída também) | CT-01 (linha `pendente`), CT-02, CT-06, CT-07 |
| A2 (sem mensagem nova) | CT-04, CT-07, CT-10 (o `Então` de não-notificação) |

---

## Armadilhas de Arnês Verificadas

Cada uma foi confirmada no vendor agora, e cada uma invalidaria um cenário se ignorada.

1. **`callAction` não checa `isVisible()`** — `InteractsWithActions.php:138-162` e `:232-253`.
   É o que faz CT-04/CT-07/CT-10 exercitarem o ponto (c). Se mudar, migrar para
   `Impersonate::make()->impersonate($alvo)`.
2. **`->loadTable()` é obrigatório** — `deferLoading` é global no kit (`.ai/rules/testes.md`); sem
   ele o HTML testado é o do esqueleto e toda asserção de linha mente.
3. **`noPainelBootado('admin')` é obrigatório** — `Filament::setCurrentPanel()` **não** boota o
   painel; quem chama `Panel::boot()` é o middleware `SetUpPanel`, que teste de componente não
   atravessa.
4. **Registro excluído resolvido pela tabela** — `TrashedFilter` declara
   `excludeWhenResolvingRecord()`, então `TestAction::make('impersonate')->table($excluido)` deve
   resolver com `filterTable('trashed', true)`. **Se não resolver**, o fallback é chamar
   `Impersonate::make()->impersonate($excluido)` direto — o **mesmo** ponto `:112` — e registrar o
   desvio no `03`.
5. **`ImpersonateManager::enter()` exige `SessionGuard`** (`resolveSessionGuard()` lança
   `RuntimeException` fora dele) e chama `auth()->forgetGuards()`. O oráculo de CT-05 lê a sessão
   **depois** disso: `session(ImpersonateManager::SESSION_KEY)` e `auth()->id()`.
6. **Constante, não string** — `ImpersonateManager::SESSION_KEY`, nunca `'impersonated_by'` no CT.
7. **Helper de escopo** — `administradorNaListaDeUsuarios()` fica **local** enquanto só este arquivo
   o usar; um segundo consumidor obriga a mover para `tests/Pest.php` (`.ai/rules/testes.md`), ou
   `--parallel`/`--tia`/arquivo isolado quebram com `Call to undefined function`.
8. **Papel disponível na suíte** — `master_global`, `admin` e `panel_user` existem em ambas as
   suítes (`.ai/rules/testes.md`); `admin_app` **não** existe em `tests/Kit` e não é usado aqui.

---

## Comando de Verificação

```bash
php artisan test --compact tests/Kit/SituacaoDaContaTest.php
php artisan test --compact tests/Kit/PermissoesDeAcoesTest.php tests/Kit/LoginSocialContaIndisponivelTest.php
```

### Fechamento do Ciclo

`vendor/bin/pest tests/Kit/SituacaoDaContaTest.php --mutate --path=app/Models/User.php` é o
fechamento que a skill pede — **e ele está bloqueado**: exige driver de cobertura, e
`.ai/rules/testes-browser.md:115` mediu que sem PCOV isso é inviável neste ambiente. A rule vence a
skill (divergência declarada no cabeçalho). Até haver PCOV, o gate deste conjunto é a tabela de
mutantes previstos, auditável linha a linha acima.

Quando houver PCOV: escopar com `--path=app/Models/User.php` (a classe é grande; `--class` não casa
de forma confiável) e traduzir cada sobrevivente de volta em cenário, não em asserção a mais.

---

## Sem CT-B

Nenhum caso de browser. Motivo, em três partes:

1. O `01-plano-acao.md` § *Superfície de UI* declara o **gate reprovado**, e a declaração se
   sustenta: as duas afirmações do requisito são "a ação não está disponível naquela linha" e "a
   execução é recusada". A primeira é `assertActionHidden`, a segunda é `callAction` — as duas por
   componente Livewire, em milissegundos.
2. Nada no requisito depende de **JavaScript executado, console, cor, tema, layout ou
   acessibilidade** — as únicas coisas que só o navegador prova.
3. A suíte Browser do kit **não roda com `--parallel`** (`.ai/rules/testes-browser.md:104`: 4 de 11
   cenários caem), então um CT-B aqui custaria um run em série inteiro para provar o que
   `assertActionHidden` prova de graça.

A verificação manual do `01` (desativar no `/admin`, ver a ação desaparecer, reativar, ver voltar)
**continua valendo** — ela é a checagem de olho humano, não substituta de CT-B.

---

## Revisão Adversarial

Executada por **sub-agente independente**, que recebeu o `00-requisito.md` e este `04` — e **não**
recebeu o `01-plano-acao.md`, o código, nem o raciocínio de quem derivou. Contrato: *provar que este
conjunto deixa passar um defeito*. **2 rodadas, 6 achados, todos fechados.**

> **Ressalva de execução, registrada por honestidade**: nas duas rodadas o revisor reportou que o
> `00-requisito.md` "não existe". Isso é **falso** — ele está commitado — e a causa é conhecida: a
> árvore de trabalho trocou de branch no meio da revisão, e o revisor leu um caminho que naquele
> instante já tinha migrado. Consequência prática: as duas rodadas foram feitas **cegas ao
> requisito**, reconstruindo as regras a partir das citações internas do `04`. Isso **reforça** os
> achados de oráculo fraco (que se auditam só com o `04`) e **enfraquece** os que dependiam de
> conhecer o escopo — dois deles foram por isso recusados, abaixo.

### O que cada achado mudou

| # | Achado | O que mudou no conjunto |
|---|---|---|
| **1** | **O papel do alvo nunca variava** — alvo `panel_user` em 100% dos cenários. Uma régua com allowlist de papel, ou delegada a `canAccessPanel('app')`, passava os 10 cenários | O papel do alvo virou **dimensão da matriz**: CT-01 ganhou as linhas `admin`/`ativa` e `admin`/`inativa`; CT-03 ganhou a linha `admin`. Nasceram os mutantes **M17** e **M18**, e a persona "alvo de outro papel" entrou no Setup Global |
| **2** | **CT-01 tinha oráculo tautológico** — *"é igual à resposta de 'pode entrar no painel'"* compara a régua com a expressão que a implementação candidata chama por dentro. E a equivalência vinha do **plano**, não do `00` | O passo foi **removido**. O oráculo de CT-01 passou a ser o booleano literal por linha, e a equivalência com `canAccessPanel()` entrou na **Fronteira com o Plano** como recusada |
| **3** | **CT-04/CT-07/CT-10 recusavam sem controle positivo** — "continua autenticado", "sem personificação em curso" passam idênticas se o `callAction` nunca alcançou a ação | Os três ganharam `E que a ação "impersonate" existe na definição da linha` (`assertActionExists`) **antes** do `Quando`. CT-07 e CT-10 ganharam também a linha de **não-notificação** que A2 exige e que faltava |
| **4** | **CT-06/CT-07 não asseriam que a inversão de `allow_soft_deleted` surtiu efeito**, e toda a discriminância da conta excluída depende dela | O `Dado` deixou de dizer "está ligada" e passou a afirmar o **valor efetivo lido** da config, no momento do ato |
| **5** | **CT-08 não exigia instância recarregada** (mutante de memoização vivo por sorte), e das **três** arestas de volta só `reativar` tinha cenário | O `Então` de CT-08 passou a exigir alvo **recarregado do banco**. Nasceu **CT-11** (`Esquema`, camada de model) cobrindo `aprovar` e `restaurar`, e o mutante **M19** — "a recusa é gravada e só o `reativar()` a limpa" |
| **6** | **A matriz declarava 15/15** enquanto a própria lacuna L1 dizia que duas células dependem da premissa P2; e a célula `master_global × (b)` não tinha o controle "alvo disponível" que CT-09 tinha | A matriz passou a **13/15 na configuração de fábrica** (15/15 só com P2 aceita), as duas células estão marcadas ⚠️, a atribuição de M6 foi corrigida (CT-01 e CT-05, não CT-03), e CT-03 ganhou o controle de disponibilidade na linha do master. O `Dado` de CT-09 trocou `canAccessPanel('admin')` pelo **mesmo predicado** das linhas aceitas de CT-01 |

### Achados recusados, com o motivo

Dois vieram da rodada cega ao requisito e **não procedem**:

- **"a régua deveria particionar as causas de indisponibilidade do login inteiro"** (organização
  inativa, `email_verified_at`, papel sem painel). O `00` **enumera** os estados: inativa, pendente
  e excluída — e a premissa **A1** é exatamente a decisão de alcance. Alargar para tenant e
  verificação de e-mail seria derivar de um requisito que ninguém escreveu. Registrado como o
  território do "se A1 for negada/ampliada", não como lacuna.
- **"falta cenário de estado desconhecido / default deny"**. `aprovacao_pendente` é
  `boolean NOT NULL DEFAULT false` e `motivoDeIndisponibilidade()` tem união literal declarada
  (`'conta_excluida'|'conta_inativa'|null`): não existe quarto valor a exercitar hoje. Um estado
  novo é mudança de requisito, e vem com os próprios cenários.

Um terceiro achado foi **absorvido como nota, não como cenário**: *"a régua do alvo passa a depender
do operador autenticado"* (`auth()->user()?->isMasterGlobal()` dentro de `canBeImpersonated()`).
Ele é o espelho de **M5**, já previsto, e a interface (a) é exercitada em CT-01, CT-02, CT-09 e
CT-11 **sem operador autenticado** — esses quatro cenários chamam o método direto, então uma
régua que exigisse sessão os derrubaria. A cobertura já existia; o que faltava era dizer isso.

### Segunda rodada

Houve, e trouxe apenas reforço dos mesmos eixos (oráculo de CT-01, controle positivo, contagem da
matriz). O fechamento criou **um** cenário novo (CT-11); pela regra da skill isso autorizaria mais
uma rodada, e ela **não foi feita**: CT-11 vive na camada de model, sobre métodos que a wiki
ancestral já cobre, e não introduz superfície nova onde uma lacuna de segunda ordem pudesse morar.
Desvio declarado, com o motivo.

---

## Perguntas para o `00-requisito.md`

> **Desvio declarado**: a skill manda devolver as perguntas para `## Ambiguidades` do `00`. O `00`
> **está editável**, mas está sendo consumido nesta derivação como linha de base — então elas ficam
> aqui, **em bloco pronto para colagem** na seção `## Ambiguidades e Perguntas Abertas` do `00`. Elas
> **continuam bloqueando** o que dependem delas: P2 bloqueia CT-06 e CT-07; P1 bloqueia a lacuna L2.

```markdown
- **P1 — a recusa de personificação deve deixar rastro?**
  O `01-plano-acao.md` prevê um `warning` no channel `autenticacao` com `motivo`,
  `personificacao_recusada` e a razão. O texto original não pede efeito colateral nenhum, e
  `canBeImpersonated()` é chamado **uma vez por linha renderizada** da lista de usuários.
  - **Assumido**: **o requisito não determina o log.** Nenhum caso de teste o assere — ele é
    escolha do plano, e um `Então` sobre ele seria teste do PRD.
  - **Se confirmado que o log é requisito**: entra um cenário de rastreio de efeito
    (aconteceu / não aconteceu quando não devia / aconteceu uma só vez), com `espiarAutenticacao()`,
    e a lacuna declarada L2 do `04` fecha.
  - **Se confirmado que não é**: o log fica como conveniência de operação, sem oráculo — e é o
    primeiro candidato a cair se ruidoso, como o próprio plano já registra.

- **P2 — a conta excluída deve continuar recusada mesmo com
  `FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED=true`?**
  O `00` descreve isso como o risco que a correção fecha ("basta alguém escrever … no `.env`"), mas
  não há cláusula `RQ` que o afirme.
  - **Assumido**: **sim** — a régua do kit é independente da config do vendor. É o que dá sentido a
    A1 e a A3 juntas.
  - **Por que importa para o teste, e não só para a prosa**: com a config no default, a guarda do
    vendor (`Impersonate.php:159-161`) roda **antes** de `canBeImpersonated()` (`:167`), então
    **nenhum cenário de UI sobre a conta excluída distingue a correção do kit da guarda do
    vendor** — todos passariam com o defeito intacto. CT-06 e CT-07 invertem a config **no teste**
    justamente para discriminar.
  - **Se negado** (basta o default do vendor): CT-06 e CT-07 saem do `04`, o mutante M9 ("a conta
    excluída fica protegida apenas pelo default do vendor") fica **sem matador** na camada de UI, e
    a única prova passa a ser CT-02, no model.

- **P3 — aceitar como dívida a sessão de personificação já em curso?**
  O `00` já declara fora de escopo o encerramento automático de uma sessão de personificação quando
  a conta-alvo é desativada **durante** ela, e registra o caso como achado para decisão. O `04`
  transformou isso no mutante M10 — *"a régua é lida só na entrada"* — **sem cenário matador**.
  - **Assumido**: **lacuna declarada e aceita**, vinculada ao escopo.
  - **Se negado** (o escopo passa a incluir o interromper): entra uma regra nova (a régua é
    reavaliada por request também **dentro** da personificação), com cenário de dois eventos —
    personificar, desativar o alvo, e afirmar sobre o request seguinte. É o espelho exato do CT-34
    da wiki `status-e-exclusao-logica-de-usuario`, que já prova esse padrão para o login.
```
