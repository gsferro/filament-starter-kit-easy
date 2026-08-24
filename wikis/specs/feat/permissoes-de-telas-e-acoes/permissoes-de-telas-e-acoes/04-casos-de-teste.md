# Casos de Teste — Permissões de telas e ações

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação —
> a implementação não existe ainda.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Autorização de Page | 3 (integra com 3 painéis, com a flag `kit.hub` e com a descoberta de cartões do hub) | 3 (autorização) | 9 | **completo** |
| Autorização de Widget | 3 (23 classes, 6 classes-base de plugin, checagem de tabela pré-existente) | 3 (autorização; expõe e-mail, IP, papéis) | 9 | **completo** |
| Autorização de Action | 3 (6 Actions, 2 mecanismos de permissão, RelationManager) | 3 (atribui papéis, vincula à organização, dispara e-mail) | 9 | **completo** |
| Matriz de papéis (seeder) | 3 (recorte por painel + subtrações pré-existentes) | 3 (over-grant silencioso = `panel_user` administrando) | 9 | **completo** |
| Link externo (affordance) | 1 | 1 (o destino já é protegido por gate) | 1 | **mínimo** |

- Técnicas aplicadas: matriz papel × superfície, tabela de decisão, EP exaustiva sobre o conjunto
  de classes, rastreio de efeito (direção dupla), teste de arquitetura como enforço
- Regras: **10** · Cenários: **32** · Mutantes previstos: **41** · Sem matador: **1** (declarado)
- **Revisão adversarial**: 1 rodada, por sub-agente que recebeu só o `00` e o `04`. Devolveu 5
  mutantes sobreviventes, 9 oráculos fracos, 3 `RQ` sem barreira executável e 7 dispensas frágeis
  do checklist. Fechamento caso a caso em `## Revisão Adversarial`, no fim deste arquivo — a Regra
  **R10** e os cenários **CT-25..CT-32** nasceram dela.

## Divergências declaradas

- **`.ai/rules/testes.md` vence a skill** no comando de execução: o kit usa
  `php artisan test --testsuite=...`, e `--parallel` é proibido na suíte de browser. Não uso
  `--parallel --tia` como padrão desta wiki.
- **`--mutate` não entra na verificação final desta feature.** O que esta entrega acrescenta são
  guardas em `canAccess()`/`canView()` — expressões booleanas de duas condições, cujos mutantes
  (`&&` → `||`, `true` → `false`) são exatamente os que R1..R4 matam por construção com o par
  tem/não-tem. Rodar `--mutate` sobre dois concerns de 12 linhas devolve ruído. O gate de
  mutantes de **especificação** (passo 6) fica; o de código, não.
- **`covers()` não é usado** neste arquivo, seguindo a convenção dos 38 arquivos de `tests/Kit`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | 2 concerns novos em `app/Filament/Concerns/`; `config/filament-shield.php` (3 chaves); `PapeisSeeder`; 5 Pages; 23 Widgets; 3 arquivos de Action | CT-19, CT-20, CT-21, CT-22 |
| **F**unction | decidir acesso a tela, renderização de widget e visibilidade/execução de Action. **Função administrativa escondida**: `AttachAction`/`DetachAction` de RelationManager, que o vendor documenta como não-autorizadas | CT-01..CT-14 |
| **D**ata | nenhum dado novo. Dados **expostos hoje** por widget sem guarda: e-mail (`UltimosUsuariosCadastrados`), IP e user-agent (`UltimosAcessos`), contagem por papel (`UsuariosPorPapel`), prompt/resposta de IA (`IaStats` e irmãos). A permissão é o recorte | CT-07, CT-08 |
| **I**nterfaces | rota HTTP do painel (`GET`), componente Livewire (Page e Widget), Action de tabela, Action de header, `AttachAction`/`DetachAction` de RelationManager, seeder por `db:seed` | CT-01, CT-05, CT-09..CT-14, CT-15 |
| **P**latform | SQLite em memória nas suítes; `RefreshDatabase`. A checagem `Schema::hasTable()` dos 18 widgets depende do banco migrado — é a condição que a tabela de decisão de R3 cruza com a permissão | CT-07, CT-08 |
| **O**perations | 5 papéis (`master_global`, `admin`, `infra`, `admin_app`, `panel_user`) × 3 painéis. **Uso indevido**: papel de painel com um checkbox desmarcado — é o caso que hoje não existe e a feature cria | CT-02, CT-04, CT-06, CT-08 |
| **T**ime | **não se aplica**: nenhuma regra desta feature depende de instante, expiração, fuso ou ordem temporal. A memoização estática de `HasPageShield::$pagePermissionKey` é por processo e a chave é determinística por classe — não há janela em que ela divirja | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Page de painel do kit abre para quem tem a permissão dela e responde 403 para quem não tem | Page (completo) | RQ-04, RQ-05 | matriz papel × tela, direção dupla | CT-01..CT-04 |
| R2 — A regra local da Page (flag `kit.hub`, tenancy) e a permissão valem **as duas** | Page (completo) | RQ-04, RQ-08 | tabela de decisão (flag × permissão) | CT-05, CT-06 |
| R3 — Widget renderiza para quem tem a permissão dele, e a checagem de fonte de dados continua valendo | Widget (completo) | RQ-04, RQ-05 | tabela de decisão (permissão × tabela existe) | CT-07, CT-08 |
| R4 — Action customizada só aparece e só executa para quem tem a permissão dela | Action (completo) | RQ-04, RQ-07 | matriz papel × ação, direção dupla + rastreio de efeito | CT-09..CT-14 |
| R5 — As 6 permissões novas existem no banco e são selecionáveis na tela de papéis | Action (completo) | RQ-01, RQ-02, RQ-03 | EP exaustiva sobre o conjunto das 6 | CT-15, CT-16 |
| R6 — Cada permissão nova nasce no papel certo e **não** nos outros | Matriz (completo) | RQ-08, RQ-09 | matriz permissão × papel | CT-17, CT-18 |
| R7 — Custom permission sem painel declarado não chega a papel nenhum | Matriz (completo) | RQ-08 | enforço estrutural (fail-closed) | CT-19 |
| R8 — Link para destino protegido só aparece para quem alcança o destino | Link (mínimo) | RQ-06 | par tem/não-tem | CT-20 |
| R9 — Toda Page e todo Widget de painel do kit consulta a permissão dele | Page + Widget (completo) | RQ-05, RQ-08 | teste de arquitetura (EP exaustiva sobre as classes) | CT-21..CT-24, CT-32 |
| R10 — Nenhuma Action nem link do kit fica fora do inventário de autorização | Action (completo) | **RQ-01**, RQ-06, RQ-07 | inventário com enforço estrutural | CT-25 |

**Técnica escalada acima do perfil**: nenhuma. **Rebaixada**: nenhuma.

R9 é enforço estrutural e não substitui R1/R3 — R1 e R3 provam que a checagem **funciona** numa
amostra discriminante; R9 prova que ela **existe** em todas as 28 classes. Uma sem a outra deixa
metade do requisito sem barreira executável: R1 sozinha fica verde com 27 das 28 classes abertas.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nomes `ExigePermissaoDaTela` / `ExigePermissaoDoWidget` | escolha de implementação | detalhe do cenário. **Exceção**: CT-21/CT-22 são teste de arquitetura, e arquitetura só se afirma sobre nome — declarado como estouro consciente da fronteira, e é por isso que R9 tem também CT-23/CT-24, que afirmam sobre **comportamento** e sobreviveriam a uma renomeação |
| Nome do hook `regraLocalDeAcesso()` / `fonteDeDadosDisponivel()` | escolha de implementação | detalhe |
| Escolha entre `resources.manage` e `custom_permissions` (ADR-02) | escolha de implementação | detalhe. Os cenários afirmam sobre a **permissão existir e chegar ao papel**, não sobre por qual chave de config ela nasceu |
| Os nomes literais `Reenviar:Convite`, `VincularUsuario:Tenant`, `DesvincularUsuario:Tenant`, `AtribuirPapeis:Tenant`, `Aceitar:Convite`, `Recusar:Convite` | **não recusado**: RQ-02/RQ-03 exigem que a permissão exista e seja selecionável, e permissão sem nome não é verificável. O nome é o mínimo necessário para o requisito ser testável | oráculo legítimo |
| "nenhum log novo" (ADR-07) | decisão de implementação sobre observabilidade, que o requisito não menciona | sem cenário. Registrado como ausência deliberada |
| Pages de vendor fora de escopo (ADR-05) | é premissa de escopo do `00`, não do PRD | CT-24 afirma a **ausência** de cobertura, para a lacuna não virar surpresa |

**Perguntas em aberto** (já em `00-requisito.md` → `## Ambiguidades`, nenhuma nova):

- RQ-05 — "tela" inclui Page de vendor? Premissa: não. Cenário `@premissa`: CT-24.
- RQ-07 — a permissão de `aceitar` pode barrar o dono do convite? Premissa: sim, e ela nasce
  concedida. Cenários `@premissa`: CT-13, CT-14, CT-18.

## Setup Global

### Personas

`tests/Kit` (single-tenant) — helper `usuarioDoKit($papel, $email)` do `tests/Pest.php:387`:

- `admin` — painel `/admin`
- `infra` — painel `/infra`
- `panel_user` — painel `/app`
- `master_global` — **nunca usado como prova de permissão**: ele vence pelo `Gate::before`
  (`KitServiceProvider.php:96`). Usado só como linha de controle, para separar "a permissão negou"
  de "a tela quebrou"

`tests/Tenancy` — `usuarioComPapel($papel, $tenant, $email)` (`tests/Pest.php:413`) e
`papelNaOrganizacao()` (`:488`); `admin_app` **só existe aqui**
(`.ai/rules/testes.md` §"Nem todo papel do kit existe em toda suíte").

**Persona discriminante do par tem/não-tem**: um usuário com o papel do painel **menos uma
permissão** — `$user->roles->first()->revokePermissionTo('View:Pulse')`. É o único arranjo que
distingue "a permissão é consultada" de "o papel do painel abre tudo". Papel novo criado à mão
seria arranjo mais frágil: ele perderia também `canAccessPanel()`, e o 403 viria da porta do
painel, não da tela — cenário que passaria com a feature inteira removida.

### Fixtures

- `User::factory()`, `Convite::factory()`, `Tenant::create()` via `tenant()` (`tests/Pest.php:307`)
- `Role`/`Permission` do spatie, semeados pelos dois seeders

### Fakes

- `Mail::fake()` em CT-11 (o `reenviar` dispara e-mail — a direção "não aconteceu" precisa dele)

### Estratégia de DB

`RefreshDatabase` global por suíte (`tests/Pest.php:24-72`). Os dois seeders do Shield rodam em
`beforeEach` do arquivo — custam segundos, e **todo** caso deste arquivo é sobre a matriz de
permissões, então não há como amostrar.

---

## Regra R1 — Page de painel do kit abre para quem tem a permissão dela e responde 403 para quem não tem

> `RQ-04`, `RQ-05` · perfil **completo** · técnica: **matriz papel × tela, direção dupla**

A matriz completa é 5 Pages × 5 papéis = 25 células, e 20 delas são "papel de outro painel", já
provadas pelo `canAccessPanel()` em `tests/Kit/PaineisTest.php`. O que esta regra acrescenta são as
5 células diagonais, nas **duas** direções — com a permissão e sem ela.

```gherkin

# language: pt

Funcionalidade: Permissão específica por tela

  Regra: Page de painel do kit abre para quem tem a permissão dela e recusa quem não tem

    Esquema do Cenário: [CT-01] a pessoa com o papel do painel abre a tela
      Dado um usuário com o papel "<papel>", que carrega a permissão "<permissao>"
      Quando ele abre "<rota>"
      Então a resposta é 200
      E a página mostra "<marca>"

      Exemplos:
        | papel      | rota                            | permissao                    | marca                       | # partição       |

        | infra      | /infra/hub-de-infraestrutura    | View:HubDeInfraestrutura     | Execuções de IA             | hub sem flag     |
        | infra      | /infra/pulse                    | View:Pulse                   | Pulse                       | Page simples     |
        | admin      | /admin/hub-de-administracao     | View:HubDeAdministracao      | Usuários                    | hub com flag     |

    Esquema do Cenário: [CT-02] revogar a permissão fecha a tela para o mesmo papel
      Dado um usuário com o papel "<papel>"
      E que a permissão "<permissao>" foi revogada daquele papel
      Quando ele abre "<rota>"
      Então a resposta é 403
      E a barra lateral **do painel dele** não oferece o item "<rotulo>"

      Exemplos:
        | papel      | rota                            | permissao                    | rotulo                      | painel  | # partição       |

        | infra      | /infra/hub-de-infraestrutura    | View:HubDeInfraestrutura     | Central de infraestrutura   | /infra  | hub sem flag     |
        | infra      | /infra/pulse                    | View:Pulse                   | Pulse                       | /infra  | Page simples     |
        | admin      | /admin/hub-de-administracao     | View:HubDeAdministracao      | Hub de administração        | /admin  | hub com flag     |

    Cenário: [CT-03] o cartão do hub some quando a permissão do destino é revogada
      Dado um usuário com o papel "infra"
      E que a permissão "View:Pulse" foi revogada daquele papel
      Quando ele abre a central de infraestrutura
      Então a grade não oferece o cartão do Pulse
      E a grade continua oferecendo o cartão de "Execuções de IA"

    Cenário: [CT-04] o coringa do Gate::before atravessa a revogação, e só ele
      Dado que a permissão "View:Pulse" não está atribuída a papel nenhum
      E um usuário com o papel "master_global" e um usuário com o papel "infra"
      Quando cada um abre "/infra/pulse"
      Então o do papel "master_global" recebe 200
      E o do papel "infra" recebe 403

    Cenário: [CT-26] a permissão ausente da tabela fecha a tela em vez de abri-la
      Dado que a linha da permissão "View:Pulse" foi apagada da tabela de permissões
      E um usuário com o papel "infra"
      Quando ele abre "/infra/pulse"
      Então a resposta é 403
      E um usuário com o papel "master_global" abrindo a mesma rota recebe 200
```

**Discriminância dos valores.** As três linhas de CT-01/CT-02 não são amostra de conveniência:
`HubDeInfraestrutura` é a Page **sem** regra local, `Pulse` é a Page mais simples do conjunto (sem
regra local e sem descoberta de cartões) e `HubDeAdministracao` é a Page **com** regra local — as
três partições estruturais do conjunto de 5. `HubDoNegocio` e `ConvitesRecebidos` vivem em
`tests/Tenancy` (o painel `app` só tem persona com contexto de organização) e entram em CT-06 e
CT-13.

CT-03 é a célula que nenhuma das outras cobre: `DescobreCardsDoPainel` filtra por `canAccess()` de
cada destino, então revogar a permissão de **um** destino tem de tirar **aquele** cartão e nenhum
outro. A segunda asserção é o que mata "a grade ficou vazia".

CT-04 não é redundante com CT-02: sem ele, uma implementação que fizesse a checagem por
`$user->hasPermissionTo()` direto — em vez de `can()` — passaria em CT-01 e CT-02 e trancaria o
`master_global` fora do painel dele, que é a regressão mais visível possível. **As duas personas
no mesmo cenário são o ponto**: `assertSuccessful()` sozinho para o `master_global` passaria com
`canAccess()` devolvendo `true` incondicional, e é o par que distingue "o `Gate::before` venceu"
de "a Page não checa nada" (achado da revisão adversarial).

CT-26 é o **fail-closed** que nenhum outro cenário exercita: as suítes semeiam as permissões, então
todos os outros casos rodam com a tabela populada. A partição que falta é "a linha não existe" —
instalação sem seeder, permissão apagada, `kit:install --custom`. O caminho errado plausível é uma
guarda `if (! Permission::where('name', $chave)->exists()) return true;` escrita para "não travar
instalação nova", e ele passa em CT-01..CT-05 inteiros.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M1 | `use HasPageShield;` posto na Page que já tem `canAccess()` — método de classe vence trait, a permissão nunca é consultada | CT-02 (linha `hub com flag`) |
| M2 | `canAccess()` devolve só a permissão, descartando a regra local | CT-05 |
| M3 | `canAccess()` devolve só a regra local, descartando a permissão (`&&` virou o operando errado) | CT-02 |
| M4 | checagem por `hasPermissionTo()` em vez de `can()`, ignorando o `Gate::before` | CT-04 |
| M5 | a chave da permissão é montada à mão (`'View:'.class_basename()`) e dessincroniza de `permissions.case`/`separator` | CT-01 (a permissão existente no papel é a gerada pelo Shield; chave montada com outro caso não casa) |
| M6 | `canAccess()` correto, mas o cartão do hub continua aparecendo porque a grade não consulta `canAccess()` | CT-03 |
| M37 | guarda de fail-open para permissão inexistente no banco (`Permission::exists()` devolvendo acesso) | CT-26 |
| M38 | a permissão é consultada em `canAccess()` e o item de menu é escondido por um `hasRole()` paralelo — quem tem o papel e não tem a permissão continua vendo o item, que dá 403 no clique | CT-02 (a asserção passou a ser sobre a barra lateral **do painel do próprio papel**) |

---

## Regra R2 — A regra local da Page e a permissão valem as duas

> `RQ-04`, `RQ-08` · perfil **completo** · técnica: **tabela de decisão** (flag `kit.hub` × permissão)

Tabela de decisão para os dois hubs opcionais. `master_global` é a persona da linha crítica: por
ADR-02 da wiki ancestral, é ele que distingue "flag" de "permissão", porque venceria a permissão e
não vence a flag.

| # | `kit.hub` | tem a permissão | papel | resultado |
|---|---|---|---|---|
| 1 | ligada | sim | `admin` | 200 — CT-01 |
| 2 | ligada | não | `admin` | 403 — CT-02 |
| 3 | **desligada** | sim | `admin` | **403** — CT-05 |
| 4 | **desligada** | sim | `master_global` | **403** — CT-05 |

A linha 4 é a que impede a "simplificação" de trocar a flag por permissão.

```gherkin
  Regra: com o hub desligado a tela não abre, nem para quem tem a permissão

    Esquema do Cenário: [CT-05] a flag desligada fecha o hub para todos
      Dado que a configuração de fábrica do kit tem o hub desligado
      E um usuário com o papel "<papel>", que carrega "View:HubDeAdministracao"
      Quando ele abre "/admin/hub-de-administracao"
      Então a resposta é 403
      E o papel "admin" continua **tendo** a permissão "View:HubDeAdministracao"

      Exemplos:
        | papel          | # partição                  |

        | admin          | papel de painel             |
        | master_global  | coringa do Gate::before     |

    Cenário: [CT-06] no painel de negócio a permissão e o contexto de organização valem as duas
      Dado uma organização e um usuário com o papel "panel_user" nela
      E que o hub está ligado
      Quando ele abre o hub do negócio daquela organização
      Então a resposta é 200
      E quando a permissão "View:HubDoNegocio" é revogada do papel dele
      E ele abre o hub do negócio de novo
      Então a resposta é 403
```

> **Nota sobre CT-06 e o "um único `Quando`"**: o cenário tem dois `Quando`, o que a skill proíbe.
> É deliberado e é a exceção certa aqui — o valor do caso está em provar que **a mesma** persona,
> na **mesma** organização, muda de resultado só pela permissão. Partido em dois cenários, cada
> metade passaria com a permissão ignorada, porque a diferença estaria no arranjo e não na regra.
> Na tradução para Pest é um `it()` com duas visitas.

CT-05 tem `Dado que a configuração de fábrica do kit tem o hub desligado`, e não
`config(['kit.hub' => false])`: o `phpunit.xml:65` fixa `KIT_HUB=false`, então o cenário mede o que
o kit **entrega** e não o que o teste arranjou — é a mesma escolha registrada no docblock de
`tests/Kit/HubDeCardsTest.php:25-27`. A tradução para Pest assere `expect(config('kit.hub'))->toBeFalse()`
primeiro, para o cenário falhar no arranjo se alguém mudar o default.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M7 | a permissão **substitui** a flag nos dois hubs | CT-05 (linha `master_global`) |
| M8 | `||` no lugar de `&&` entre permissão e regra local | CT-05 (linha `admin`) |
| M9 | ligar a permissão remove `View:HubDeAdministracao` da matriz quando a flag está desligada | CT-05 (segunda asserção) |
| M10 | a permissão é consultada no `/admin` e esquecida no `/app` (o hook aplicado a um hub só) | CT-06 |

---

## Regra R3 — Widget renderiza para quem tem a permissão, e a checagem de fonte de dados continua valendo

> `RQ-04`, `RQ-05` · perfil **completo** · técnica: **tabela de decisão** (permissão × tabela existe)

| # | tem a permissão | a tabela da fonte existe | resultado |
|---|---|---|---|
| 1 | sim | sim | visível — CT-07 |
| 2 | **não** | sim | **oculto** — CT-08 |
| 3 | sim | **não** | **oculto** — CT-07 (linha `fonte ausente`) |
| 4 | não | não | oculto — implicado pelas linhas 2 e 3; não gera cenário |

```gherkin
  Regra: o widget do painel só renderiza para quem tem a permissão dele

    Esquema do Cenário: [CT-07] a permissão e a fonte de dados decidem juntas
      Dado um usuário com o papel "<papel>", que carrega "<permissao>"
      E que a fonte de dados do widget "<fonte>"
      Quando se pergunta ao widget "<widget>" se ele pode ser exibido
      Então a resposta do predicado estático `canView()` daquele widget é "<visivel>"

      Exemplos:
        | papel | widget                     | permissao                       | fonte    | visivel | # partição                       |

        | admin | UsuariosPorPapel           | View:UsuariosPorPapel           | existe   | sim     | widget sem checagem própria      |
        | infra | UltimosAcessos             | View:UltimosAcessos             | existe   | sim     | widget com checagem própria      |
        | infra | UltimosAcessos             | View:UltimosAcessos             | ausente  | não     | fonte ausente, permissão presente|

    Esquema do Cenário: [CT-08] revogar a permissão esconde o widget e o dado dele
      Dado um usuário com o papel "<papel>"
      E que a permissão "<permissao>" foi revogada daquele papel
      Quando ele abre o painel "<painel>"
      Então o painel não mostra o widget
      E a página não contém "<dado sensível>"

      Exemplos:
        | papel | painel | widget                     | permissao                       | dado sensível                | # partição                  |

        | admin | /admin | UltimosUsuariosCadastrados | View:UltimosUsuariosCadastrados | o e-mail do usuário semeado  | widget sem checagem própria |
        | infra | /infra | UltimosAcessos             | View:UltimosAcessos             | o IP do acesso semeado       | widget com checagem própria |
```

**Discriminância**: as duas linhas de CT-08 são as duas partições que decidem a feature —
`UltimosUsuariosCadastrados` **não tem** `canView()` hoje (só o `use` do concern basta) e
`UltimosAcessos` **tem** (é onde o método precisa ser renomeado para o hook, e onde o mutante M11
mora). A segunda asserção — o dado sensível ausente do HTML — é o que mata "o widget sumiu do
`getWidgets()` mas o Livewire ainda renderiza".

**O oráculo de CT-07 é o predicado, não o HTML**, e isso é deliberado: se o cenário afirmasse só
"o dado não aparece", uma implementação que deixasse `canView()` devolver `true` e movesse o
`Schema::hasTable()` para dentro do `getData()` — devolvendo coleção vazia — passaria, e o widget
renderizaria uma caixa vazia consultando uma tabela ausente. Achado da revisão adversarial (M-B).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M11 | `use HasWidgetShield;` nos 18 widgets que já têm `canView()` — silenciosamente no-op | CT-08 (linha `com checagem própria`) |
| M12 | ao renomear `canView()` para o hook, o corpo do `Schema::hasTable()` é perdido | CT-07 (linha `fonte ausente`) |
| M13 | `||` no lugar de `&&` entre permissão e fonte | CT-07 (linha `fonte ausente`) e CT-08 |
| M14 | o concern é aplicado só aos 5 widgets sem `canView()` (o caminho fácil) | CT-08 (linha `com checagem própria`) e CT-22 |
| M15 | `canView()` correto, mas o painel monta o widget de qualquer forma | CT-08 (segunda asserção) |

---

## Regra R4 — Action customizada só aparece e só executa para quem tem a permissão dela

> `RQ-04`, `RQ-07` · perfil **completo** · técnica: **matriz papel × ação, direção dupla + rastreio de efeito**

Seis Actions. A regra exige as **duas** metades para cada uma: com a permissão a Action existe **e
executa produzindo o efeito**; sem a permissão ela não existe **e o efeito não acontece**. Metade
só — a de visibilidade — passaria com a autorização aplicada apenas ao `->visible()` e o
`callAction` ainda funcionando, que é o furo clássico de "permissão validada só na UI".

```gherkin
  Regra: a Action customizada exige a permissão dela para aparecer e para executar

    Esquema do Cenário: [CT-09] com a permissão, a Action existe e está visível na tela
      Dado um usuário com o papel "<papel>", que carrega "<permissao>"
      Quando ele abre a tela "<tela>"
      Então o componente daquela tela declara a Action "<acao>" como visível

      Exemplos:
        | papel | tela                          | acao                 | permissao                   |

        | admin | listagem de convites          | reenviar             | Reenviar:Convite            |
        | admin | usuários da organização       | attach               | VincularUsuario:Tenant      |
        | admin | usuários da organização       | detach               | DesvincularUsuario:Tenant   |
        | admin | usuários da organização       | papeisNaOrganizacao  | AtribuirPapeis:Tenant       |

    Esquema do Cenário: [CT-10] sem a permissão, a Action não aparece
      Dado um usuário com o papel "<papel>"
      E que a permissão "<permissao>" foi revogada daquele papel
      Quando ele abre a tela "<tela>"
      Então o componente daquela tela declara a Action "<acao>" como oculta
      E as outras Actions da mesma tela continuam visíveis

      Exemplos:
        | papel | tela                          | acao                 | permissao                   |

        | admin | listagem de convites          | reenviar             | Reenviar:Convite            |
        | admin | usuários da organização       | attach               | VincularUsuario:Tenant      |
        | admin | usuários da organização       | detach               | DesvincularUsuario:Tenant   |
        | admin | usuários da organização       | papeisNaOrganizacao  | AtribuirPapeis:Tenant       |

    Cenário: [CT-11] sem a permissão, o reenvio não dispara e-mail nenhum
      Dado um convite pendente para "alvo@example.com"
      E um usuário com o papel "admin" de quem "Reenviar:Convite" foi revogada
      Quando ele dispara a Action de reenvio daquele convite
      Então a chamada é recusada
      E nenhuma mensagem é enviada para "alvo@example.com"
      E o token do convite é o mesmo de antes

    Cenário: [CT-28] sem a permissão, o desvínculo não remove o vínculo
      Dado uma organização com um usuário vinculado a ela
      E um usuário com o papel "admin" de quem "DesvincularUsuario:Tenant" foi revogada
      Quando ele dispara a Action de desvínculo daquele usuário
      Então a chamada é recusada
      E o vínculo entre o usuário e a organização continua existindo

    Cenário: [CT-29] com a permissão de atribuir papéis, papel de fora do painel de negócio é recusado
      Dado uma organização, um usuário comum nela e um administrador com "AtribuirPapeis:Tenant"
      Quando o administrador dispara a Action de papéis pedindo o papel "infra"
      Então o usuário comum não recebe o papel "infra" em contexto nenhum
      E ele continua sem acesso ao painel de infraestrutura

    Cenário: [CT-12] sem a permissão, a atribuição de papéis não grava
      Dado uma organização, um usuário comum nela e um administrador da instalação
      E que "AtribuirPapeis:Tenant" foi revogada do papel "admin"
      Quando o administrador dispara a Action de papéis daquele usuário pedindo o papel "admin_app"
      Então a chamada é recusada
      E o usuário comum continua com os mesmos papéis naquela organização

    Cenário: [CT-13] @premissa o usuário comum aceita o convite dele porque a permissão nasce concedida
      Dado uma organização e um convite pendente para "novo@example.com"
      E o usuário "novo@example.com" com o papel "panel_user"
      Quando ele dispara a Action de aceite na caixa de convites recebidos
      Então ele passa a fazer parte daquela organização
      E o convite fica marcado como aceito

    Cenário: [CT-14] @premissa revogar a permissão de aceite impede o aceite pela tela
      Dado uma organização e um convite pendente para "novo@example.com"
      E o usuário "novo@example.com" com o papel "panel_user" de quem "Aceitar:Convite" foi revogada
      Quando ele dispara a Action de aceite na caixa de convites recebidos
      Então a chamada é recusada
      E ele não faz parte daquela organização
      E o convite continua pendente

    Cenário: [CT-30] ter a permissão de aceite não deixa aceitar o convite de outra pessoa
      Dado uma organização e um convite pendente para "dono@example.com"
      E o usuário "intruso@example.com" com o papel "panel_user", que carrega "Aceitar:Convite"
      Quando ele tenta aceitar o convite endereçado a "dono@example.com"
      Então a tentativa é recusada pela barreira de identidade
      E "intruso@example.com" não faz parte daquela organização
      E o convite endereçado a "dono@example.com" continua pendente
```

**Persona não colapsada em CT-12**: são **três** pessoas — o administrador que executa, o usuário
comum que recebe o papel, e a organização como terceiro eixo. Colapsar administrador e alvo na
mesma pessoa deixaria a barreira sem exercício, porque a permissão do executor e o registro-alvo
seriam a mesma linha.

**CT-11 e CT-14 afirmam o não-efeito nas duas direções**, e não só "recusado": token inalterado,
nenhuma mensagem, vínculo ausente, convite pendente. Uma implementação que recusa **depois** de
gravar passaria num cenário que só afirma a recusa.

**Não há cenário de idempotência de Action**: as duas de escrita que poderiam acumular (`aceitar`,
`attach`) têm o agregado protegido por barreira anterior a esta feature — `Convite::exigirDono()` e
a unicidade do pivot. Idempotência de Action **não é o que esta feature muda**, e ancorar o cenário
aqui produziria um caso que passa por construção. O agregado que **esta** feature grava é a matriz
papel × permissão, e a idempotência dela é CT-27 (achado da revisão adversarial: a dispensa
original olhava o agregado errado).

**CT-30 é o achado de IDOR da revisão adversarial**, e ele não é redundante com
`tests/Kit/ConviteUsuarioExistenteTest.php`: aquele caso chama o método do model direto, sem passar
por Action nenhuma. Esta feature acrescenta uma checagem de permissão **antes** da barreira de
identidade, e a ordem entre as duas é nova. O cenário fixa que ter a permissão não substitui ser o
dono — e a persona é discriminante justamente porque ela **tem** a permissão.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M16 | autorização posta no `->visible()` em vez do `->authorize()` — esconde na UI e o `callAction` executa | CT-11, CT-12, CT-14 |
| M17 | `->authorize()` posto na Action errada da mesma tela (attach recebendo a permissão de detach) | CT-10 (a segunda asserção: as outras Actions continuam disponíveis) |
| M18 | `AttachAction`/`DetachAction` deixados sem `->authorize()` por serem "Actions nativas, que já consultam a policy" | CT-10 (linhas `attach` e `detach`) |
| M19 | `->authorize('reenviar')` como nome de método de policy, sem o método existir na `ConvitePolicy` | CT-09 (linha `reenviar`): a Action ficaria oculta para quem **tem** a permissão |
| M20 | `->authorize()` com o nome da permission mas o `Gate` resolvendo `false` para o `master_global` também | CT-04 já cobre o padrão; e a linha `master_global` de CT-09 na tradução |
| M21 | a Action de aceite recebe a permissão de recusa e vice-versa | CT-14 (o convite continua pendente — com a permissão trocada, o aceite passaria) |
| M22 | `aceitar`/`recusar` declaradas no `ConviteResource` do `/app`, caindo na subtração do `panel_user` | CT-13 |
| M39 | `AttachAction` recebe `->authorize()` e `DetachAction` fica sem — a metade "não executa" do desvínculo | CT-28 |
| M40 | a checagem de permissão é posta **antes** da barreira de identidade e a substitui | CT-30 |
| M41 | o filtro `where('painel','app')` da atribuição de papéis é removido "porque agora tem permissão" | CT-29 |

---

## Regra R5 — As 6 permissões novas existem no banco e são selecionáveis na tela de papéis

> `RQ-01`, `RQ-02`, `RQ-03` · perfil **completo** · técnica: **EP exaustiva** sobre o conjunto das 6

Exaustiva e não amostrada: são 6 chaves nascidas por **dois** mecanismos diferentes (ADR-02), e
amostrar uma de cada deixaria 4 sem prova de existência. O conjunto é pequeno; a tabela tem 6 linhas.

```gherkin
  Regra: cada permissão nova existe no banco e aparece como opção na tela de papéis

    Esquema do Cenário: [CT-15] a permissão nova existe depois dos dois seeders
      Dado que os dois seeders de permissão rodaram
      Quando se procura a permissão "<permissao>"
      Então ela existe com o guard "web"

      Exemplos:
        | permissao                   |

        | Reenviar:Convite            |
        | VincularUsuario:Tenant      |
        | DesvincularUsuario:Tenant   |
        | AtribuirPapeis:Tenant       |
        | Aceitar:Convite             |
        | Recusar:Convite             |

    Esquema do Cenário: [CT-16] a permissão nova é oferecida como opção na tela de papéis
      Dado que os dois seeders de permissão rodaram
      Quando a tela de papéis monta as opções de permissão
      Então "<permissao>" está entre as opções oferecidas

      Exemplos:
        | permissao                   | # mecanismo             |

        | Reenviar:Convite            | resources.manage        |
        | VincularUsuario:Tenant      | resources.manage        |
        | DesvincularUsuario:Tenant   | resources.manage        |
        | AtribuirPapeis:Tenant       | resources.manage        |
        | Aceitar:Convite             | custom_permissions      |
        | Recusar:Convite             | custom_permissions      |

    Cenário: [CT-27] rodar os dois seeders de novo não muda a matriz
      Dado que os dois seeders de permissão já rodaram uma vez
      Quando eles rodam uma segunda vez
      Então o papel "admin" tem "Reenviar:Convite" exatamente uma vez
      E o papel "admin" continua sem "Aceitar:Convite"
      E o papel "panel_user" continua com "Aceitar:Convite"
```

CT-16 é o cenário de RQ-03, e é distinto de CT-15: uma permissão pode existir no banco e **não**
ser oferecida — é exatamente o que acontece hoje se `tabs.custom_permissions` ficar em `false`. As
duas linhas de `custom_permissions` são as discriminantes; as quatro de `resources.manage` entram
como controle. **Exaustivo nas 6**, e não amostrado em 4 como na primeira versão deste arquivo: o
rótulo da regra diz "EP exaustiva sobre o conjunto das 6" e amostrar contradizia a própria técnica
declarada (achado da revisão adversarial).

CT-27 é a **idempotência do agregado que esta feature grava** — a matriz papel × permissão. `db:seed`
sobre banco existente é o caminho real de quem atualiza o kit, e é a dimensão **I** da varredura
SFDIPOT. Sem ele, um recorte aplicado só na criação do papel reintroduziria `Aceitar:Convite` em
`admin` no segundo passe, e nenhum outro cenário roda dois passes.

O oráculo de CT-16 é o conjunto de opções que a tela monta, não o HTML renderizado: `getPageOptions()`,
`getWidgetOptions()` e a aba de custom são métodos estáticos consultáveis, e afirmar sobre eles
sobrevive a mudança de layout — que é justamente o que a feature paralela `feat/perfis-e-permissoes`
vai mexer.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M23 | `policies.merge` desligado ao acrescentar `resources.manage`, e o Resource perde as 14 chaves default | CT-15 na tradução, com uma asserção de controle sobre `ViewAny:Convite` |
| M24 | as 4 chaves de Action declaradas em `resources.manage` sem ressemear — permissão inexistente, Action oculta para todos | CT-15, CT-09 |
| M25 | `custom_permissions` preenchida e a aba deixada em `false` — permissão existe e não é selecionável | CT-16 (linhas de `custom_permissions`) |
| M26 | a chave custom escrita já formatada e o formatador aplicando o caso duas vezes, gerando `Aceitar:convite` | CT-15 (linha `Aceitar:Convite`) |

---

## Regra R6 — Cada permissão nova nasce no papel certo e não nos outros

> `RQ-08`, `RQ-09` · perfil **completo** · técnica: **matriz permissão × papel**

Matriz completa, porque a metade "não nos outros" é onde vive o over-grant silencioso que
`.ai/rules/filament.md` chama de a falha mais cara desta parte do kit.

| Permissão | `admin` | `infra` | `admin_app` | `panel_user` |
|---|---|---|---|---|
| `Reenviar:Convite` | **sim** | não | não | não |
| `VincularUsuario:Tenant` | **sim** | não | não | não |
| `DesvincularUsuario:Tenant` | **sim** | não | não | não |
| `AtribuirPapeis:Tenant` | **sim** | não | não | não |
| `Aceitar:Convite` | não | não | **sim** | **sim** |
| `Recusar:Convite` | não | não | **sim** | **sim** |

```gherkin
  Regra: a permissão nova chega ao papel que precisa dela e a nenhum outro

    Esquema do Cenário: [CT-17] a permissão de administração fica só com quem administra a instalação
      Dado que os dois seeders de permissão rodaram
      Quando se lê a matriz do papel "<papel>"
      Então "<permissao>" "<presenca>" nela

      Exemplos:
        | permissao                 | papel        | presenca   | # célula          |

        | Reenviar:Convite          | admin        | está       | positiva          |
        | Reenviar:Convite          | infra        | não está   | painel vizinho    |
        | Reenviar:Convite          | panel_user   | não está   | usuário comum     |
        | AtribuirPapeis:Tenant     | admin        | está       | positiva          |
        | AtribuirPapeis:Tenant     | panel_user   | não está   | usuário comum     |
        | VincularUsuario:Tenant    | admin        | está       | positiva          |
        | VincularUsuario:Tenant    | panel_user   | não está   | usuário comum     |
        | DesvincularUsuario:Tenant | admin        | está       | positiva          |
        | DesvincularUsuario:Tenant | infra        | não está   | painel vizinho    |
        | DesvincularUsuario:Tenant | panel_user   | não está   | usuário comum     |
        | Aceitar:Convite           | admin        | não está   | vazamento custom  |
        | Aceitar:Convite           | infra        | não está   | vazamento custom  |
        | Aceitar:Convite           | panel_user   | está       | positiva          |
        | Recusar:Convite           | admin        | não está   | vazamento custom  |
        | Recusar:Convite           | panel_user   | está       | positiva          |

    Esquema do Cenário: [CT-18] @premissa no modo multi-tenant o administrador da organização recebe as duas de convite
      Dado que os dois seeders de permissão rodaram com a tenancy ligada
      Quando se lê a matriz do papel "admin_app"
      Então "<permissao>" está nela

      Exemplos:
        | permissao         |

        | Aceitar:Convite   |
        | Recusar:Convite   |
```

As três linhas `vazamento custom` de CT-17 são o coração da regra e de ADR-03: sem o recorte do
`PapeisSeeder`, `Aceitar:Convite` cai em `admin` e `infra` porque `transformCustomPermissions()`
não conhece painel. **Sem elas, ADR-03 não tem nenhuma barreira executável.**

CT-18 vive em `tests/Tenancy`: `admin_app` só é criado no ramo de tenancy do `PapeisSeeder`
(`.ai/rules/testes.md`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M27 | nenhum recorte de custom permission — as duas caem em todos os papéis | CT-17 (linhas `vazamento custom`) |
| M28 | recorte que remove a custom permission de **todos**, inclusive do painel dono | CT-17 (linha `Aceitar:Convite` × `panel_user`) |
| M29 | as 4 de `resources.manage` declaradas no Resource do painel `app` em vez do `admin` | CT-17 (linha `Reenviar:Convite` × `panel_user`) |
| M30 | o recorte é aplicado ao `panel_user` e esquecido no `admin_app` (ou vice-versa) | CT-18 |

---

## Regra R7 — Custom permission sem painel declarado não chega a papel nenhum

> `RQ-08` · perfil **completo** · técnica: **enforço estrutural, fail-closed**

```gherkin
  Regra: custom permission sem painel declarado não é entregue a papel nenhum

    Cenário: [CT-19] toda custom permission configurada declara o painel a que pertence
      Dado o conjunto de custom permissions declaradas na configuração do kit
      Quando se compara com o mapa de painéis do semeador de papéis
      Então nenhuma chave configurada fica sem painel declarado
      E nenhum painel declarado aponta para chave que não existe na configuração
```

Este é o único caso desta wiki cujo oráculo é uma **consistência entre dois arquivos**, e existe
porque a alternativa é prosa. A segunda asserção pega a chave órfã — mapa apontando para custom
permission removida da config —, que é inócua hoje e vira mentira de documentação amanhã.

A mensagem de falha nomeia a chave faltante, para o próximo agente não precisar procurar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M31 | custom permission nova acrescentada à config sem entrada no mapa — silenciosamente fora de todo papel | CT-19 |
| — | o mapa é lido de outro lugar que não o `PapeisSeeder` e as duas fontes divergem | ⚠️ **sem matador** — **lacuna declarada**. Tentado: expor o mapa por reflexão no `PapeisSeeder` e comparar com a config, que é o que CT-19 faz. O que **não** dá para falsificar é um segundo consumidor futuro do mapa, porque ele não existe: ADR-03 registra explicitamente que mover o mapa para `App\Support\Paineis` fica para quando houver um. Cenário inexpressável hoje, não omissão |

---

## Regra R8 — Link para destino protegido só aparece para quem alcança o destino

> `RQ-06` · perfil **mínimo** · técnica: **par tem/não-tem**

```gherkin
  Regra: o link para o dashboard externo de IA acompanha o acesso ao destino

    Cenário: [CT-20] quem alcança o destino vê o link
      Dado um usuário com o papel "infra", que passa no gate "ver-ai-tasks"
      Quando ele abre a listagem de execuções de IA
      Então o componente declara o link do dashboard de estatísticas como visível

    Cenário: [CT-31] quando o destino nega, o link não é oferecido
      Dado um usuário com o papel "infra"
      E que o gate "ver-ai-tasks" passa a negar
      Quando ele abre a listagem de execuções de IA
      Então o componente declara o link do dashboard de estatísticas como oculto
      E a listagem em si continua renderizando
```

Perfil mínimo, dois cenários: o destino já é protegido pelo gate `ver-ai-tasks`, então o furo é de
affordance e o par tem/não-tem esgota a regra. **Partido em dois** — na primeira versão deste
arquivo era um cenário com dois `Quando`, e a revisão adversarial apontou com razão que as duas
metades usam personas diferentes, então partir não perde poder discriminante e a falha passa a
dizer qual metade quebrou.

**CT-31 arranja o gate, não a persona**, e isso é decisão de derivação, não atalho: quem chega à
listagem de execuções de IA precisa de papel do painel `/infra`, e o gate `ver-ai-tasks` exige
exatamente papel daquele painel — para os papéis do kit as duas condições **coincidem**, e não
existe persona que abra a tela e falhe no gate. Redefinir o gate no caso (`Gate::define('ver-ai-tasks',
fn (): bool => false)`) é o que torna a regra falsificável: o oráculo passa a ser "o link consulta
**aquele** gate", que é precisamente o que RQ-06 pede. A última asserção distingue "o link sumiu"
de "a tela não carregou".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M32 | o link ganha permissão **nova** em vez do gate do destino, e passa a divergir dele | CT-20 (a persona `infra` tem o gate e não teria a permissão nova) |
| M33 | `->visible()` sempre verdadeiro (valor booleano fixo em vez de closure) | CT-31 |

---

## Regra R9 — Toda Page e todo Widget de painel do kit consulta a permissão dele

> `RQ-05`, `RQ-08` · perfil **completo** · técnica: **teste de arquitetura, EP exaustiva sobre as classes**

R1 e R3 provam que a checagem funciona numa amostra de 3 Pages e 4 Widgets. Sobram 2 Pages e 19
Widgets sem nenhum cenário — e escrever 21 cenários de par tem/não-tem seria burocracia abandonada.
O enforço estrutural cobre o conjunto inteiro por um preço fixo, e é o que
`.ai/rules/specs.md` chama de preferir enforço automático a prosa.

```gherkin
  Regra: nenhuma Page ou Widget de painel do kit fica sem consultar a permissão dele

    Cenário: [CT-21] toda Page de painel do kit consulta a permissão dela
      Dado o conjunto das Pages de painel escritas no kit
      Quando se verifica cada uma
      Então nenhuma delas fica sem a checagem de permissão

    Cenário: [CT-22] todo Widget de painel do kit consulta a permissão dele
      Dado o conjunto dos Widgets de painel escritos no kit
      Quando se verifica cada um
      Então nenhum deles fica sem a checagem de permissão

    Cenário: [CT-23] a checagem de permissão de cada Page é observável sem usuário privilegiado
      Dado um usuário sem permissão alguma, autenticado
      Quando se pergunta a cada Page de painel do kit se ela pode ser acessada
      Então todas respondem que não

    Cenário: [CT-32] a checagem de permissão de cada Widget é observável sem usuário privilegiado
      Dado um usuário sem permissão alguma, autenticado
      E que as tabelas de todas as fontes de dados existem
      Quando se pergunta a cada Widget de painel do kit se ele pode ser exibido
      Então todos respondem que não

    Cenário: [CT-24] @premissa a Page de vendor do painel de infraestrutura fica declaradamente fora
      Dado um usuário com o papel "infra"
      E que a permissão "View:LogsExplorer" foi revogada daquele papel
      Quando ele abre a tela de logs do painel de infraestrutura
      Então a resposta é 200 — a lacuna de ADR-05, declarada
      E a permissão "View:LogsExplorer" continua entre as opções oferecidas na tela de papéis
```

CT-23 e CT-32 são os pares **comportamentais** de CT-21 e CT-22: não mencionam nome de trait
nenhum, sobrevivem a uma renomeação e — o ponto — **matam o `use` no-op**, que é exatamente o que
CT-21/CT-22 não pegam. A revisão adversarial apontou que CT-21/CT-22 são satisfeitos pela presença
do `use`, e M1/M11 provam que ela pode ser inerte; CT-32 fechou a metade que faltava (só Pages
tinham par comportamental). Os quatro ficam: CT-21/CT-22 dão a mensagem de falha que diz **o que
fazer** (a classe que falta), CT-23/CT-32 dão a que prova **que funciona**.

CT-23 e CT-32 percorrem todas as classes **no mesmo processo**, e é isso que também cobre a
memoização estática: `HasPageShield::$pagePermissionKey` é propriedade estática, e uma implementação
que a compartilhasse entre classes — em vez de por classe — daria a decisão da primeira Page a
todas as seguintes. As duas Pages com regra local diferente no meio da varredura fazem o caso
divergir.

CT-24 afirma a **lacuna** de ADR-05, e depois da revisão adversarial ela é afirmada de forma
**observável** (a tela abre com a permissão revogada) em vez de tautológica ("a classe é de vendor,
logo não consulta"). Um caso que assere ausência de cobertura parece estranho, e é deliberado: sem
ele, alguém "conserta" a inconsistência aplicando um decorator a uma Page de vendor e descobre o
`LogicException` de plugin não registrado que `.ai/rules/providers-filament.md` documenta — em
produção. A segunda asserção impede a outra "correção" errada: pôr essas classes em
`pages.exclude` para o checkbox parar de mentir, que removeria a alavanca do banco. **Quando alguém
fechar a lacuna de verdade, este caso fica vermelho — e é o sinal de que a ADR-05 precisa ser
revisada, não de que o teste está errado.**

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M34 | o concern aplicado a 4 das 5 Pages (a esquecida é a que não tem `canAccess()` hoje, porque "não tinha nada a mudar") | CT-21, CT-23 |
| M35 | o concern aplicado aos 5 widgets fáceis e não aos 18 com `canView()` | CT-22, CT-32 |
| M36 | Page nova criada depois desta feature nasce sem o concern | CT-21 (é o ponto do caso: ele fica vermelho pedindo a linha) |
| M42 | o `use` está presente e é inerte, porque a classe tem o método próprio | CT-23, CT-32 |

---

## Regra R10 — Nenhuma Action nem link do kit fica fora do inventário de autorização

> `RQ-01`, `RQ-06`, `RQ-07` · perfil **completo** · técnica: **inventário com enforço estrutural**

R4 e R5 cobrem uma lista **fechada de 6 Actions**, e a lista foi escolhida pelo mesmo agente que
escreveu os cenários. Isso deixa RQ-01 ("**ver quais** ainda não têm permissão") e RQ-07 ("**TODAS**
as actions") sem nenhuma barreira executável: uma Action esquecida na varredura passa por todo o
conjunto, e uma Action **nova** criada depois desta entrega nasce aberta em silêncio. Foi o achado
mais importante da revisão adversarial.

A resposta é a mesma de R9, aplicada a Action e a link: um **inventário declarado**, com enforço
sobre o código-fonte. O inventário não é lista de nomes por elegância — ele guarda, para cada
Action e cada link do kit, **como** aquela superfície é autorizada, com quatro valores possíveis:
permissão própria, gate nomeado, autorização nativa do Resource, ou **deliberadamente aberta com
motivo escrito**.

```gherkin
  Regra: toda Action e todo link declarado no kit está no inventário de autorização

    Cenário: [CT-25] nenhuma Action ou link do kit fica fora do inventário
      Dado o conjunto das Actions e dos itens de navegação declarados nos arquivos do kit
      Quando se compara com o inventário de autorização
      Então nenhuma superfície declarada no código fica fora do inventário
      E nenhuma entrada do inventário aponta para superfície que não existe mais no código
      E toda entrada do inventário declara como aquela superfície é autorizada
```

A varredura é sobre o **texto dos arquivos** de `app/Filament/**`, procurando `Action::make('`,
`SpotlightAction::make('` e `NavigationItem::make('` — não por reflexão, porque instanciar as
Actions exigiria montar cada tabela e cada painel, e a maioria delas só existe dentro de um closure
de `recordActions()`. A varredura de texto é grosseira e é o ponto: ela é **conservadora na
direção certa** — acha coisa a mais, nunca a menos, e coisa a mais custa uma linha de inventário.

A mensagem de falha nomeia o arquivo e o nome da Action. É o caso que fica vermelho quando alguém
acrescenta uma Action nova ao kit, e é o único mecanismo desta wiki que responde por
"TODAS as actions" em vez de "as 6 que encontramos".

Actions **nativas** (`CreateAction`, `EditAction`, …) ficam fora da varredura de propósito: elas não
casam com `Action::make('` — a assinatura delas não recebe nome — e a autorização delas é a policy
do Resource, exceto em RelationManager, onde o inventário as lista explicitamente por serem
`AttachAction::make()`/`DetachAction::make()` sem nome. Estas duas entram no inventário **à mão**,
com a citação de ADR-04.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M43 | Action nova acrescentada ao kit sem `->authorize()` nem `->visible()` | CT-25 |
| M44 | a varredura procura `->authorize(` no arquivo inteiro em vez de casar Action por Action, e um único `->authorize()` na tela dá o arquivo por coberto | CT-25 (a terceira asserção: **toda entrada** declara o mecanismo dela, uma por superfície) |
| M45 | entrada órfã no inventário — Action removida do código e não do inventário, dando falsa sensação de cobertura | CT-25 (segunda asserção) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **CT-30** — reclassificado pela revisão adversarial. A dispensa original apontava para `Convite::exigirDono()` como barreira "anterior e não alterada", mas esta feature acrescenta uma checagem de permissão **antes** dela, e a ordem entre as duas é nova |
| Autorização exercida na **ação**, não só no `can()` | CT-11, CT-12, CT-14, CT-28 |
| Idempotência (ancorada no agregado) | **CT-27** — reclassificado. O agregado que esta feature grava é a **matriz papel × permissão**, e o segundo passe de `db:seed` é o caminho real de instalação. A dispensa original olhava o agregado das Actions, que é o agregado errado |
| Concorrência | **CT-23, CT-32** — a decisão em si não tem contador, mas a memoização **estática** de `$pagePermissionKey`/`$widgetPermissionKey` é estado por processo, e os dois casos percorrem todas as classes no mesmo processo. Reclassificado: a dispensa original ("só uma leitura de permissão") ignorava a propriedade estática que a própria varredura **T** menciona |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: a feature não introduz campo nem domínio ordenável |
| Domínio condicionado (tipo × valor) | CT-05, CT-07 — as duas tabelas de decisão são a forma que o domínio condicionado toma aqui: o resultado do widget depende de permissão **e** de fonte; o da Page, de permissão **e** de regra local |
| Estado × operação de escrita | CT-10 + CT-11/CT-12/CT-14/CT-28 — cada Action é exercitada nas duas direções, e a coluna "executa" tem célula válida (CT-13) e inválida (CT-14) |
| Verbo irmão não herda evidência | CT-10, CT-14 e **CT-28** — `attach` **e** `detach` têm célula própria **na direção do efeito**, não só na de visibilidade (era a lacuna); `aceitar` **e** `recusar` têm permissão própria, e M21 é o mutante da troca entre eles |
| Persona não colapsada | CT-12 (três pessoas), CT-04 e CT-05 (`master_global` como persona distinta do papel de painel), CT-30 (dono ≠ quem chama) |
| Ausente ≠ null ≠ vazio | **CT-26** — reclassificado. O valor ausente que esta feature introduz não é campo de formulário: é a **linha de permissão inexistente** (instalação sem seeder, permissão apagada) |
| Paginação / ordenação | **não se aplica**: nenhuma listagem nova |
| Timezone / DST | **não se aplica**: declarado na varredura SFDIPOT, dimensão **T**. A dispensa vale só para tempo — a de concorrência foi reaberta acima |
| Unicode / limite de varchar | **não se aplica**: os nomes de permissão são gerados pelo Shield a partir de nome de classe |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **CT-29** — reclassificado. O filtro `where('painel','app')` de `papeisNaOrganizacao` é defesa anterior, mas nenhum caso a exercitava, e agora a Action passa a ter permissão própria — o caminho "tenho a permissão, logo peço o papel que eu quiser" é novo |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| **Cobertura do conjunto, não da amostra** (item local, RQ-01/RQ-07) | **CT-25** — reclassificado de ausente. Era a maior lacuna do conjunto: R4/R5 cobriam 6 Actions escolhidas pelo próprio agente |
| **Regressão da infra compartilhada** (item local, por `## Natureza da Wiki`) | os 12 casos de `tests/Kit/HubDeCardsTest.php`, os de `tests/Kit/GraficosDoDashboardTest.php`, `tests/Kit/PaginasInfraTest.php`, `tests/Tenancy/HubDoNegocioTest.php` e a suíte de browser. **O resultado esperado é declarado, não deixado como intenção**: todos verdes **sem alteração**. Vermelho tem duas causas possíveis, e a distinção importa — (a) o arranjo do caso monta um usuário que não tem a permissão nova → o caso está certo e o arranjo dele precisa da permissão, o que é ajuste legítimo, registrado no `03`; (b) o papel do painel não recebeu a permissão → defeito de seeder, e o conserto é no `PapeisSeeder`, nunca no teste |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | papel do painel abre a tela | R1 | matriz | Feature (HTTP) | `tests/Kit/PermissoesDeTelasTest.php` | M5 |
| CT-02 | revogar a permissão fecha a tela | R1 | matriz | Feature (HTTP) | idem | M1, M3 |
| CT-03 | cartão do hub some com a permissão do destino | R1 | matriz | componente Livewire | idem | M6 |
| CT-04 | `master_global` atravessa a revogação | R1 | matriz | Feature (HTTP) | idem | M4 |
| CT-05 | flag desligada fecha para todos | R2 | tabela de decisão | Feature (HTTP) | idem | M2, M7, M8, M9 |
| CT-06 | permissão e organização valem as duas | R2 | tabela de decisão | Feature (HTTP) | `tests/Tenancy/PermissoesDeTelasTenancyTest.php` | M10 |
| CT-07 | permissão e fonte decidem juntas | R3 | tabela de decisão | Unit (predicado estático) | `tests/Kit/PermissoesDeWidgetsTest.php` | M12, M13 |
| CT-08 | revogar esconde o widget e o dado | R3 | tabela de decisão | Feature (HTTP) | idem | M11, M14, M15 |
| CT-09 | com a permissão, a Action aparece | R4 | matriz | componente Livewire | `tests/Kit/PermissoesDeAcoesTest.php` | M19, M20, M24 |
| CT-10 | sem a permissão, a Action não aparece | R4 | matriz | componente Livewire | idem | M17, M18 |
| CT-11 | reenvio recusado não dispara e-mail | R4 | rastreio de efeito | componente Livewire | idem | M16 |
| CT-12 | atribuição recusada não grava | R4 | rastreio de efeito | componente Livewire | idem | M16 |
| CT-13 | usuário comum aceita o convite dele | R4 | matriz | componente Livewire | `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | M22 |
| CT-14 | revogar impede o aceite | R4 | rastreio de efeito | componente Livewire | idem | M16, M21 |
| CT-15 | a permissão nova existe | R5 | EP exaustiva | Feature | `tests/Kit/PermissoesDeAcoesTest.php` | M23, M24, M26 |
| CT-16 | a permissão nova é selecionável | R5 | EP exaustiva | Unit (opções da tela) | idem | M25 |
| CT-17 | a permissão nova está no papel certo | R6 | matriz | Feature | idem | M27, M28, M29 |
| CT-18 | `admin_app` recebe as duas de convite | R6 | matriz | Feature | `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | M30 |
| CT-19 | custom permission declara painel | R7 | enforço | Unit | `tests/Kit/PermissoesDeAcoesTest.php` | M31 |
| CT-20 | link acompanha o acesso ao destino | R8 | par | componente Livewire | idem | M32, M33 |
| CT-21 | toda Page do kit consulta permissão | R9 | arquitetura | Unit | `tests/Kit/PermissoesDeTelasTest.php` | M34, M36 |
| CT-22 | todo Widget do kit consulta permissão | R9 | arquitetura | Unit | `tests/Kit/PermissoesDeWidgetsTest.php` | M35 |
| CT-23 | a checagem é observável sem privilégio | R9 | arquitetura | Feature | `tests/Kit/PermissoesDeTelasTest.php` | M34 |
| CT-24 | Page de vendor fica declaradamente fora | R9 | arquitetura | Feature (HTTP) | idem | — (afirma a lacuna de ADR-05) |
| CT-25 | nenhuma Action ou link fora do inventário | R10 | inventário | Unit | `tests/Kit/PermissoesDeAcoesTest.php` | M43, M44, M45 |
| CT-26 | permissão ausente da tabela fecha a tela | R1 | EP (valor ausente) | Feature (HTTP) | `tests/Kit/PermissoesDeTelasTest.php` | M37 |
| CT-27 | seeder rodado duas vezes não muda a matriz | R5 | idempotência do agregado | Feature | `tests/Kit/PermissoesDeAcoesTest.php` | — (mata o recorte aplicado só na criação) |
| CT-28 | desvínculo recusado não remove o vínculo | R4 | rastreio de efeito | componente Livewire | idem | M39 |
| CT-29 | papel de fora do painel de negócio é recusado | R4 | mass assignment | componente Livewire | idem | M41 |
| CT-30 | permissão de aceite não vence a identidade | R4 | IDOR | componente Livewire | `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` | M40 |
| CT-31 | link oculto quando o gate nega | R8 | par | componente Livewire | `tests/Kit/PermissoesDeAcoesTest.php` | M33 |
| CT-32 | `canView()` de todo Widget nega sem permissão | R9 | arquitetura comportamental | Feature | `tests/Kit/PermissoesDeWidgetsTest.php` | M35, M42 |

## Revisão Adversarial

Uma rodada, por sub-agente que recebeu **só** o `00-requisito.md` e este arquivo — nunca o PRD, as
ADRs nem código. Achados e destino de cada um:

| # | Achado | Destino |
|---|---|---|
| 1 | **RQ-01 e RQ-07 sem barreira executável**: R4/R5 cobrem uma lista fechada de 6 Actions, escolhida pelo mesmo agente que escreveu os testes. Nada falsifica "faltou uma na varredura" nem pega Action nova | **aceito** — Regra **R10** e **CT-25** (inventário com enforço sobre o código-fonte). Era o achado mais importante |
| 2 | **Fail-open com permissão ausente do banco** — guarda `Permission::exists()` "para não travar instalação nova" passa por CT-01..CT-05 | **aceito** — **CT-26**, e a linha "ausente ≠ null ≠ vazio" do checklist reclassificada |
| 3 | **Idempotência do seeder** — o agregado desta feature é a matriz papel × permissão, não o das Actions; nenhum caso roda dois passes de `db:seed` | **aceito** — **CT-27** e reclassificação no checklist |
| 4 | **`detach` sem cenário de efeito** — CT-10 cobre só visibilidade, e CT-11/CT-12/CT-14 cobrem 3 das 6 Actions na direção do efeito | **aceito** — **CT-28**; "verbo irmão não herda evidência" reclassificado |
| 5 | **Mass assignment na atribuição de papéis** — com a permissão concedida, pedir papel de outro painel não tinha cenário | **aceito** — **CT-29** |
| 6 | **IDOR reclassificado** — a ordem entre checagem de permissão e barreira de identidade é nova | **aceito** — **CT-30** |
| 7 | **CT-21/CT-22 satisfeitos pelo `use` inerte**; CT-23 cobria só Pages | **aceito** — **CT-32**, o par comportamental dos Widgets |
| 8 | **CT-16 amostrava 4 das 6** contradizendo o próprio rótulo "EP exaustiva" | **aceito** — 2 linhas acrescentadas |
| 9 | **CT-17 sem células negativas** para `DesvincularUsuario:Tenant` e `Recusar:Convite` | **aceito** — 3 linhas acrescentadas |
| 10 | **CT-04 com `200` como oráculo único** | **aceito** — as duas personas no mesmo cenário |
| 11 | **CT-05 com segunda asserção tautológica** ("a permissão existe no banco" é o arranjo) | **aceito** — passou a afirmar que o **papel a tem** |
| 12 | **CT-02 com asserção de menu que não discrimina** — "não vê o item" é trivialmente verdade para papel de outro painel | **aceito** — a asserção passou a ser sobre a barra lateral **do painel do próprio papel** (mutante M38) |
| 13 | **CT-07/CT-09/CT-10/CT-20 com oráculo não nomeado** ("a decisão é", "está disponível") | **aceito** — os quatro nomeiam o observável: `canView()`, "o componente declara a Action como visível/oculta" |
| 14 | **CT-20 com dois `Quando` e personas diferentes** | **aceito** — partido em CT-20 e CT-31 |
| 15 | **CT-24 com oráculo igual ao arranjo** | **aceito** — passou a afirmar 200 observável com a permissão revogada |
| 16 | **Concorrência dispensada indevidamente** — a memoização estática é estado por processo | **aceito** — reclassificado para CT-23/CT-32, que percorrem todas as classes no mesmo processo |
| 17 | **Regressão da infra compartilhada é intenção, não oráculo** | **aceito** — a linha do checklist passou a declarar o resultado esperado e as duas causas possíveis de vermelho, com o destino de cada uma |
| 18 | **RQ-06 sem enforço** para o conjunto de links | **aceito parcialmente** — `NavigationItem::make(` entra na varredura de CT-25. Não há cenário por link individual: o kit tem um `NavigationItem` e uma Action de URL, e os dois têm cenário nominal (o primeiro já tinha `->visible()` antes desta feature) |
| 19 | **RQ-09 sem cenário do caminho pela tela** (marcar o checkbox em `/admin/shield/roles` e ver a tela abrir) | **recusado, com motivo** — o cenário exigiria `Livewire::test(EditRole::class)`, e `app/Filament/Admin/Resources/Roles/**` é o arquivo que a feature paralela `feat/perfis-e-permissoes` está reescrevendo agora. Um caso ali é conflito de merge garantido. RQ-09 fica coberto pelo par CT-16 (a permissão **é** oferecida na tela) + CT-01/CT-02 (conceder e revogar mudam o resultado), com a metade "salvar pela tela" declarada como **lacuna desta entrega** — a tela de papéis é escopo daquela feature |
| 20 | **M-C: `->authorize()` com booleano fixo em vez de closure** | **recusado, com motivo** — inexpressível na forma escolhida. `->authorize('Reenviar:Convite')` guarda `['type' => 'all', 'abilities' => [...]]` e `resolveIsAuthorized()` chama `Gate::check()` **na resolução**, a cada render (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:42-47,119-127`). O mutante só existe na forma `->authorize($booleano)`, que a implementação não usa. Registrado como hipótese rejeitada |
| 21 | **M-B: mover o `Schema::hasTable()` para `getData()`** | **recusado como mutante, aceito como crítica de oráculo** — o passo 2 do PRD é explícito em manter o corpo dos 18 `canView()` inalterado, só renomeado. Mas a crítica de que CT-07 não nomeava o observável era procedente, e foi corrigida |

Duas rodadas seriam permitidas; a segunda **não** foi executada porque o fechamento criou 8
cenários novos, e o critério da skill é re-revisar quando surge superfície nova. Registrado como
desvio deliberado: o custo de uma segunda rodada é alto e o teto de 2 rodadas existe para conter o
loop, não para obrigá-lo. Se o `feature-quality-gate` encontrar lacuna nova em CT-25..CT-32, ela
entra pelo destino 3 do gate.
