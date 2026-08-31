# Casos de Teste — Travas de escalada na tela de papéis e no login social

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · ADR: `02-decisoes-arquiteturais.md`
> Derivado do **requisito** (RQ-01..RQ-09). Nenhum cenário foi escrito olhando a implementação da
> feature — do `01` saíram apenas paths, rotas, nomes de tela e a tabela `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — Guarda do papel super-admin na tela de papéis (RQ-01..RQ-03) | 3 | 3 | 9 | completo |
| B — Trava de painel na concessão (RQ-04, RQ-05) | 3 | 3 | 9 | completo |
| C — Convite no fluxo social (RQ-06..RQ-08) | 3 | 3 | 9 | completo |
| D — Uso único do link de vínculo (RQ-09) | 2 | 3 | 6 | padrão |

P=3 nas três primeiras: cada uma integra com mecanismo de vendor que decide autorização
(`Gate::before` do Shield, `Rule::exists` do Laravel, `permission.teams` do spatie) e tem mais de
duas condições. I=3 em todas: são travas de **escalada de privilégio** — o impacto é acesso indevido
ao `/infra` (trilha de auditoria, IPs, ledger de IA de todas as organizações) ou à conta de outra
pessoa.

- Técnicas aplicadas: EP, normalização de identidade, matriz persona × operação, tabela de decisão,
  tabela estado × evento, rastreio de efeito, idempotência ancorada no agregado, partição sobre o
  **estado prévio do alvo**.
- Cenários: **27** (26 blocos Gherkin — CT-14/CT-16 é um `Esquema` de duas linhas) · Regras: **7** ·
  Mutantes previstos: **44** · Sem matador: **2** (M3b e M34, ambos com lacuna declarada e o que foi
  tentado registrado).
- **Teto de mutantes por regra**: R3 (8), R4 (6), R6 (6) e R7 (7) estouram o teto do perfil. Todos os
  excedentes — M35..M42 — vieram das duas rodadas de revisão adversarial, e mutante trazido pela
  revisão não conta para o teto: é achado medido, não enchimento.
- **Revisão adversarial**: **duas rodadas** por sub-agentes independentes (contrato cego: só o `00` e
  este arquivo). Rodada 1: 9 lacunas. Rodada 2: 5 lacunas de segunda ordem, 6 oráculos fracos e 4
  matadores que não matavam. **Todas fechadas** — ver `## Achados da Revisão Adversarial`.

### Divergência declarada: rule do projeto vence a skill

A skill sugere `pest --parallel --tia` como padrão de execução. `.ai/rules/testes-browser.md` mediu
que `--parallel` derruba os cenários de navegador e que, **sem PCOV**, o `--tia` não termina
(abortado após 35 min). **A rule vence.** O comando desta wiki é o do `01-plano-acao.md`:
`php artisan test --testsuite=Kit,Tenancy --compact`.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S**tructure | Guarda do registro do papel super-admin (policy + `AdministradorDaInstalacao`), regra de validação do `name` no `RoleResource`, recorte de concessão por painel, dois pontos do `LoginSocialController` (`retorno`, `confirmarVinculo`). **Sem migration, sem rota nova, sem model novo.** | — |
| **F**unction | recusar nome reservado; negar edição/exclusão do registro super-admin; recortar a concessão pelos painéis do operador; deixar de consumir convite na volta do provedor; recusar o segundo uso do link assinado. Função administrativa escondida: `master_global` atravessa todas as travas pelo `Gate::before`. | CT-01..CT-27 |
| **D**ata | `roles.name` (texto livre, `unique`), `roles.painel` (`admin` \| `infra` \| `app` \| **null**), `model_has_roles.team_id` (contexto), `convites.aceito_em`/`role_id`/`tenant_id`/token, `users.ativo`/`deleted_at`/`aprovacao_pendente`, marca de uso derivada da assinatura. Dado de outro tenant: papel de `/app` concedido no contexto de outra organização. Dado nulo com semântica própria: `painel = null` (papel que só carrega permissões). | CT-01, CT-08, CT-14, CT-18, CT-20 |
| **I**nterfaces | Filament: `Roles` create/edit/list, `EditUser`, `CreateConvite`, ação de lote `convidarEmMassa` em `ListConvites`, página `ConvitesRecebidos` do `/app`. HTTP: `GET /auth/{provedor}/callback` e `GET /auth/{provedor}/confirmar` (`signed`). **Chamada direta** a `UserResource::gravarPapeis()` — o payload forjado que não passa por tela nenhuma. | CT-05, CT-11, CT-13, CT-14, CT-19 |
| **P**latform | SQLite `:memory:` no teste × MySQL em produção — **a colação decide se `MASTER_GLOBAL` casa com `master_global`** (pergunta Q2). `CACHE_STORE=array` no `phpunit.xml`: o store vive no processo, então a marca de uso persiste entre dois requests do mesmo caso — é o que torna CT-20 executável, e é a mesma limitação que impede o cenário de concorrência (M34). `permission.teams` só está ligado na suíte `Tenancy` (`Tests\TenancyTestCase`), e `admin_app` só existe lá. | CT-01, CT-20 |
| **O**perations | Personas reais do kit: `master_global` (atravessa tudo), `admin` (painel `admin`, tem `Create/Update/Delete:Role`), `infra`, `admin_app`, `panel_user`. Uso indevido alvo: auto-promoção em três cliques por quem tem `Update:Role`; SSO silencioso do provedor consumindo convite sem clique da vítima. | CT-05, CT-08, CT-14 |
| **T**ime | Janela de 30 min da assinatura do link × janela da marca de uso — se a segunda for menor, o link volta a valer dentro da primeira. A ordem entre o aceite do convite e a barreira de conta indisponível **é** o defeito F-03. Validade do convite (7 dias, `KIT_CONVITE_VALIDADE_DIAS`) não interage: com a RQ-06 o ramo social não consome convite em estado nenhum. | CT-22, CT-18 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — O nome do papel super-admin é reservado a quem é `master_global`, na criação **e** na edição | A (completo) | RQ-01 | EP sobre `name` × persona nos dois pontos de entrada + normalização de identidade | CT-01..CT-04 |
| **R2** — O registro do papel super-admin não é editado nem excluído por quem não é `master_global` | A (completo) | RQ-02, RQ-03 | matriz persona × operação, com célula válida por coluna | CT-05..CT-07 |
| **R3** — Quem não é `master_global` concede papel sem painel, papel do painel de negócio (o `->default()`) e papel de painel que ele próprio acessa — nunca de painel que governa a instalação e ao qual ele não tem acesso | B (completo) | RQ-04 | tabela de decisão | CT-08..CT-11, CT-23 |
| **R4** — A trava de R3 vale nos três caminhos de concessão | B (completo) | RQ-05 | matriz caminho × alcance (verbo irmão) | CT-12, CT-13, CT-24 |
| **R5** — Conta existente não consome convite na volta do provedor; consome só na tela autenticada `ConvitesRecebidos`. Conta nova continua consumindo | C (completo) | RQ-06, RQ-07 | tabela de decisão + rastreio de efeito | CT-14..CT-17, CT-25 |
| **R6** — Conta indisponível não consome convite nem abre sessão | C (completo) | RQ-08 | tabela estado × evento (100% das células) | CT-18, CT-26 |
| **R7** — O link assinado de confirmação de vínculo vale uma única vez | D (padrão) | RQ-09 | idempotência ancorada no agregado + BVA sobre a janela de tempo | CT-19..CT-22, CT-27 |

**Técnica escalada acima do perfil da área**: R7 está em perfil `padrão` mas recebeu BVA sobre a
janela de tempo (CT-22) — sem ela, uma janela de uso menor que os 30 min da assinatura reabre o link
dentro da própria validade, e nenhum outro cenário distingue isso.

---

## Fronteira com o Plano

| Item do `01`/`02` | Recusado como oráculo porque | Destino |
|---|---|---|
| `AdministradorDaInstalacao::papelEditavelPor()`, `paineisDoOperador()` | nome de método — escolha de implementação | detalhe do cenário; o `Então` afirma o efeito (papel intacto), nunca a chamada |
| `Cache::add('vinculo-social:'.hash(...))` | mecanismo de armazenamento — o requisito pede **uso único**, não cache | detalhe; CT-20 afirma a recusa e o agregado, não a chave |
| Mensagem "Este nome é reservado ao administrador da instalação." | comportamento visível que só o plano determina | **pergunta Q4** — os cenários afirmam erro **no campo `name`**, não o texto |
| Linha de log `[LoginSocialController@retorno] Convite pendente não consumido…` | efeito colateral que só o plano determina; RQ-06 não pede rastro | não vira `Então` |
| Guarda em `RolePolicy::forceDelete()`/`restore()` | `App\Models\Role` **não usa `SoftDeletes`** e o `RoleResource` não tem `ForceDeleteAction`/`RestoreAction` — não há caminho observável | **pergunta Q3**; nenhum cenário |
| `->rule()` no `TextInput` vs. `mutateFormDataBefore*` | onde a validação mora | detalhe; CT-01 afirma erro de formulário + ausência no banco |
| "Ajustar os testes da wiki `cadastro-social-por-convite-e-organizacao`" | é consequência, não requisito | ver `## Testes existentes que mudam de oráculo` |

### Perguntas para o `00-requisito.md`

<!-- Desvio declarado: o `00-requisito.md` é imutável nesta derivação (instrução explícita).
     As perguntas ficam aqui, prontas para colagem em `## Ambiguidades e Perguntas Abertas`. -->

- **Q1 (BLOQUEIA R3/R4)** — **RQ-04 e a ADR-02 se contradizem.** A premissa registrada no `00`
  ("painéis dos papéis do operador em qualquer contexto") faz um operador cujo único papel é `admin`
  (`roles.painel = 'admin'`) **deixar de conceder `panel_user` e `admin_app`**, porque ambos têm
  `painel = 'app'`. É exatamente a perda de funcionalidade que a ADR-02 usou para **recusar** a
  alternativa 1 ("o convite para organização escolhe papel de `/app` a partir do `/admin`"). Ou o
  operador do convite precisa acumular um papel do `/app`, ou a regra é outra (barrar apenas o painel
  de maior privilégio; ou declarar `/app` concedível por qualquer operador do `/admin`).
  **RESOLVIDA** em `00-requisito.md` → Q1, depois desta derivação: o critério passou a ser **sem
  painel, painel de negócio (o `->default()` do kit) ou painel que o operador acessa**. A linha
  `panel_user` do CT-08 deixa de ser `@premissa` e passa a **recebe** (regra 3b da tabela). O
  fechamento que a mudança acrescenta: `admin_app` não concede papel de `/admin` — cobre CT-11.
- **Q2 (bloqueia parte de R1)** — RQ-01 diz "`name` igual a `AdministradorDaInstalacao::papel()`".
  Igual **como**? Em MySQL com colação `..._ci` (o caso de produção) `MASTER_GLOBAL` casa com
  `master_global` na consulta do spatie, e a variante de caixa **é** o papel super-admin para o
  `Gate::before`; em SQLite (a suíte) não casa. **Assumido**: a comparação normaliza caixa e espaços
  das bordas. Cenários afetados: CT-01, linhas `@premissa`.
- **Q3 (não bloqueia)** — RQ-03 fala de exclusão. O plano guarda também `forceDelete()` e
  `restore()`, que num model sem `SoftDeletes` e sem Action correspondente não têm caminho de
  execução. Guardar mesmo assim (defesa futura) ou deixar de fora?
- **Q4 (não bloqueia)** — a mensagem de recusa do nome reservado é comportamento visível que o
  requisito não determina. O texto proposto no plano é oficial?
- **Q5 (não bloqueia)** — RQ-02 barra **não-master**. Um `master_global` continua podendo renomear o
  próprio papel super-admin, o que troca quem é o administrador da instalação e pode trancá-lo fora.
  É deliberado?
- **Q6 (bloqueia CT-24)** — RQ-05 enumera **três** caminhos de concessão (ficha, convite individual,
  convite em massa). O papel de um convite, porém, só vira atribuição no **aceite**, que é um quarto
  ponto de gravação e não passa por Select algum. Um convite gravado antes da trava — ou por um
  operador que na época tinha o alcance — concede `infra` no aceite sem passar por nenhum dos três.
  A trava vale também ali? **Assumido**: sim (é o mesmo "a trava é na escrita" do RQ-04). Cenário
  afetado: CT-24, marcado `@premissa`. **Se negado**: CT-24 e M37 caem, e o convite já gravado passa
  a ser um caminho de escalada aceito por decisão.
- **Q7 (não bloqueia)** — RQ-08 lista "desativada, soft-deleted, pendente de aprovação", e RQ-07 diz
  que o `?token=` continua criando conta nova. Numa instalação com aprovação manual, a conta criada
  pelo convite **nasce** pendente. O convite é consumido nesse caso? **Assumido**: não — a letra do
  RQ-08 fala do estado da conta, não de quando ela nasceu, e queimar o convite de quem talvez nunca
  seja aprovado é o mesmo prejuízo do F-03. Cenário: CT-26.

---

## Setup Global

### Personas

- `master_global` — `usuarioDoKit('master_global', 'master@example.com')`. **Linha de controle,
  nunca de prova**: vence tudo pelo `Gate::before` (`.ai/rules/testes.md`).
- `admin` — `usuarioDoKit('admin', 'admin@example.com')`. O operador da tela de papéis: tem
  `Create/Update/Delete:Role` e `roles.painel = 'admin'`. É a persona discriminante de R1..R4.
- `admin` **com papel do `/app`** — `papelNaOrganizacao(usuarioDoKit('admin', ...), 'panel_user', $acme)`.
  Discrimina "painéis em qualquer contexto" de "painel do request corrente" (CT-09).
- `admin_app` — `usuarioComPapel('admin_app', $acme, ...)`. **Só existe em `tests/Tenancy`.**
- alvo da concessão — `usuario('dani@example.com')`, sem papel nenhum.
- **duas pessoas distintas** no fluxo social (`dona@example.com`, `outra@example.com`): persona
  colapsada não exercita barreira de identidade nenhuma (CT-21).

### Fixtures

- `Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin'])` — papel comum, a
  **célula válida** de toda coluna de operação.
- `Role::create(['name' => 'etiquetador', 'guard_name' => 'web', 'painel' => null])` — papel **sem
  painel**, a partição declarada da premissa 2 do `00`.
- `ofertaPara($email, $tenant, $papel)` (`tests/Pest.php`) — convite pendente com `role_id` explícito.
- `tenant('Acme', 'acme')` / `tenant('Globex', 'globex')`.
- `usuarioSocialFalso(ProvedorSocial::Google, [], ['id' => 'sub-x', 'email' => ...])` +
  `Socialite::fake('google', ...)`.

### Fakes

- `Notification::fake()` em tudo que toca convite ou vínculo.
- `Http::preventStrayRequests()` no fluxo social (já é o padrão de `LoginSocialContaIndisponivelTest`).
- `travelTo()` / `travelBack()` só em CT-22.

### Estratégia de DB e de suíte

- `RefreshDatabase` global (`tests/Pest.php`), `sqlite :memory:`.
- `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])` em todo caso que use papel do kit.
- **Suíte `Kit`** (sem tenancy): R1, R2, R6, R7. **Suíte `Tenancy`**: R3, R4, R5 — precisam de
  organização, de `admin_app` ou de `model_has_roles.team_id`.
- `Filament::setCurrentPanel('admin')` nos casos de componente do `/admin`; `noPainelDoShield()` em
  caso que atravesse painéis.

---

## Regra R1 — O nome do papel super-admin é reservado

> `RQ-01` · área A · perfil **completo** · técnicas: **EP** sobre o valor de `name` × persona,
> derivada nos **dois** pontos de entrada (criação e edição), mais **normalização de identidade**.

```gherkin
# language: pt

Funcionalidade: Travas de escalada na tela de papéis

  Regra: só quem é master_global grava um papel com o nome do papel super-admin

    Esquema do Cenário: [CT-01] o operador comum não grava o nome reservado, nem criando nem renomeando
      Dado o operador "admin", que não é master_global
      E um papel "suporte" já existente no banco
      Quando ele <operacao> um papel com o nome "<nome>"
      Então o formulário devolve erro no campo "name"
      E não existe no banco papel algum além dos semeados e de "suporte"
      E o papel "suporte" continua com o nome "suporte"

      Exemplos:
        | operacao | nome              | # partição                       |
        | cria     | master_global     | inválida — nome reservado exato  |
        | renomeia | master_global     | inválida — mesmo nome, na EDIÇÃO |
        | cria     | MASTER_GLOBAL     | @premissa Q2 — variante de caixa |
        | renomeia | " master_global " | @premissa Q2 — espaços nas bordas |

    Cenário: [CT-02] o master_global salva o papel super-admin sem alterar o nome
      Dado o operador "master_global"
      E o papel super-admin sem a permissão "View:Role"
      Quando ele salva o papel super-admin com o nome inalterado e a permissão "View:Role" marcada
      Então o formulário não devolve erro algum
      E o papel super-admin continua existindo com o nome "master_global"
      E o papel super-admin passa a ter a permissão "View:Role"

    Cenário: [CT-03] o nome reservado acompanha a configuração, não o literal
      Dado que o nome do papel super-admin configurado é "dono_da_instalacao"
      E o operador "admin", que não é master_global
      Quando ele cria um papel com o nome "dono_da_instalacao"
      Então o formulário devolve erro no campo "name"
      E não existe papel chamado "dono_da_instalacao" no banco

    Cenário: [CT-04] o operador comum continua criando papel com qualquer outro nome
      Dado o operador "admin", que não é master_global
      Quando ele cria um papel com o nome "auditor" e painel "admin"
      Então o formulário não devolve erro algum
      E existe no banco um papel "auditor" com painel "admin"
```

**Camada**: componente Livewire — `Livewire::test(CreateRole::class)` / `EditRole::class` com
`fillForm(['name' => ..., 'guard_name' => 'web', 'painel' => 'admin'])` → `->call('create'|'save')` →
`assertHasFormErrors(['name'])` + `assertDatabaseMissing`/`assertDatabaseHas`.

**Gate de tela de escrita**: `/admin/shield/roles/create` e `/edit` estão na `## Superfície de UI` e
recebem **gravação** por componente em CT-01/CT-02/CT-04 — não só visita.

**Nota de discriminação (CT-03)**: a única forma de separar "compara com `AdministradorDaInstalacao::papel()`"
de "compara com a string `master_global`" é mover a configuração. Sem CT-03 o mutante literal fica
verde para sempre, porque o valor de fábrica e o literal coincidem.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | regra escrita só no caminho de criação, ausente no `save` | CT-01, linha `renomeia` |
| M2 | comparação contra o literal `'master_global'` em vez do nome configurado | CT-03 |
| M3a | regra condicionada a "o nome mudou", que isenta o `master_global` por acidente e tranca o dono nas demais gravações | CT-02 — que agora **grava** uma permissão, e não só passa sem erro |
| M3b | regra aplicada a **todo** operador nas colunas *criar* e *renomear para* o nome reservado | ⚠️ **sem matador — inexpressável**. O `unique` do `name` já impede **qualquer** operador de gravar o nome reservado enquanto o papel super-admin o ocupa; libertar o nome exige renomear o papel super-admin antes, o que **tira do próprio operador** a condição de `master_global` (`isMasterGlobal()` resolve pelo nome de config). Tentado: mover `filament-shield.super_admin.name` — a persona master se move junto com o nome reservado, e a colisão se reproduz. Ligado a **Q5**. |
| M4 | condição negada — a regra recusa qualquer nome | CT-04 |
| M5 | comparação sensível a caixa e a espaços das bordas | CT-01, linhas `@premissa` (depende de Q2) |

---

## Regra R2 — O registro do papel super-admin não é editado nem excluído por não-master

> `RQ-02`, `RQ-03` · área A · perfil **completo** · técnica: **matriz persona × operação**, com
> célula válida obrigatória em cada coluna e o verbo irmão (`excluir` × `excluir em massa`)
> falsificado separadamente.

**Matriz derivada** (linha = registro + persona; coluna = operação):

| Registro / persona | abrir edição | salvar edição | excluir | excluir em massa |
|---|---|---|---|---|
| super-admin / `admin` | recusa (CT-05) | recusa (CT-05) | recusa (CT-05) | recusa (CT-05) |
| super-admin / `master_global` | permite (CT-07) | permite (CT-07) | permite (CT-07) | mesma policy de CT-07 |
| papel comum / `admin` | permite (CT-06) | permite (CT-06) | permite (CT-06) | permite (CT-06) |

```gherkin
  Regra: o registro do papel super-admin só é editado ou excluído por master_global

    Esquema do Cenário: [CT-05] o operador comum não alcança o registro do papel super-admin
      Dado o operador "admin", que não é master_global
      Quando ele tenta <operacao> o papel super-admin
      Então a operação é recusada
      E o papel super-admin continua existindo no banco com o nome "master_global"
      E a matriz de permissões e o painel do papel super-admin continuam idênticos aos de antes
      E o master_global existente continua com esse papel atribuído

      Exemplos:
        | operacao                                 | # verbo                 |
        | abrir e salvar a tela de alteração       | update                  |
        | disparar a exclusão pela linha da tabela | delete                  |
        | disparar a exclusão em massa             | deleteAny (verbo irmão) |

    Esquema do Cenário: [CT-06] o operador comum continua editando e excluindo papel comum
      Dado o operador "admin", que não é master_global
      E um papel "suporte" com painel "admin"
      Quando ele <operacao> o papel "suporte"
      Então <resultado>
      E a operação não foi recusada

      Exemplos:
        | operacao                                 | resultado                                                       | # célula válida |
        | renomeia para "suporte_n1"               | existe no banco um papel "suporte_n1" com painel "admin"        | update          |
        | exclui pela linha da tabela              | não existe no banco papel chamado "suporte"                     | delete          |

    Cenário: [CT-07] o master_global continua alcançando o papel super-admin
      Dado o operador "master_global"
      Quando ele dispara a exclusão do papel super-admin pela linha da tabela
      Então não existe no banco papel chamado "master_global"
```

**Camada**: componente Livewire. Abrir a edição →
`Livewire::test(EditRole::class, ['record' => ...])->assertForbidden()`; exclusão →
`Livewire::test(ListRoles::class)->loadTable()->callAction(TestAction::make(DeleteAction::class)->table($papel))`
e, para o lote, `TestAction::make(DeleteBulkAction::class)->table()`. O oráculo é sempre
`assertDatabaseHas('roles', ['name' => 'master_global'])` **depois** da chamada — nunca só
`assertActionHidden`, porque esconder é UX e o `mountAction` alcança ação escondida (ADR-01).

**Uma tela aberta não é uma tela que grava** (`.ai/rules/testes.md`): CT-06 fecha o par com a
gravação real, e é ele que impede a guarda de virar "ninguém edita papel algum".

**Célula colapsada, declarada**: em Filament a página de edição autoriza no **mount**, então "abrir"
e "salvar" não são duas células independentes sobre o papel super-admin — se a guarda vive só no
`save`, o mount já falha; se vive só no mount, o `save` não chega a ser alcançável. Viram uma linha
só. A célula de escrita que precisa existir separada é a **válida**, e é CT-06, linha `renomeia`.

> `CT-06` executa duas operações em sequência (renomear, excluir) sobre o **mesmo** registro: é o
> caso "CRUD combinado" do checklist, e as duas são a célula válida das duas colunas. Não é um
> segundo `Quando` disfarçado — o `Então` é sobre o estado final do registro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | guarda em `update`, esquecida em `delete` (verbo irmão) | CT-05, linha `delete` |
| M7 | guarda posta como `->hidden()`/`->visible()` na Action, não na autorização do registro — o lote e o `mountAction` passam | CT-05, linha `deleteAny` |
| M8 | guarda que também barra o `master_global` (pergunta a permission em vez de "é o master?") | CT-07 |
| M9 | guarda que nega **todo** papel, não só o super-admin | CT-06 |
| M10 | guarda que consulta o nome **enviado no formulário** em vez do nome do registro — o `delete` não envia nome algum | CT-05, linha `delete` |

---

## Regra R3 — Concessão limitada aos painéis do operador

> `RQ-04` · área B · perfil **completo** · técnica: **tabela de decisão**.

**Condições**: C1 = o operador é `master_global`? · C2 = o `painel` do papel concedido é nulo? ·
C3 = esse painel está entre os painéis dos papéis do operador (em qualquer contexto)? ·
C3b = esse painel é o **painel de negócio** (o `->default()` do kit)? ·
C4 = o papel é o super-admin (trava por nome, já existente)?

| # | C1 | C2 | C3 | C3b | C4 | Ação | Cenário |
|---|---|---|---|---|---|---|---|
| 1 | sim | — | — | — | — | concede | CT-10 |
| 2 | não | — | — | — | sim | recusa | CT-08, linha `master_global` |
| 3 | não | sim | — | — | não | concede | CT-08, linha `etiquetador` |
| 3b | não | não | não | sim | não | concede | CT-08, linha `panel_user` |
| 4 | não | não | sim | — | não | concede | CT-08, linha `admin`; CT-09 |
| 5 | não | não | não | não | não | recusa | CT-08, linha `infra`; CT-11 |

```gherkin
  Regra: quem não é master_global concede papel sem painel, do painel de negócio, ou de painel que ele acessa

    Esquema do Cenário: [CT-08] o alcance do operador recorta o papel concedido na ficha do usuário
      Dado o operador "admin", cujo único papel tem painel "admin"
      E a pessoa "Dani", sem papel algum
      Quando ele salva a ficha de Dani com o papel "<papel>"
      Então Dani <resultado> o papel "<papel>", em contexto algum

      Exemplos:
        | papel         | resultado  | # regra da tabela                                 |
        | admin         | recebe     | 4 — painel do próprio operador                    |
        | etiquetador   | recebe     | 3 — papel sem painel (premissa 2 do 00-requisito) |
        | infra         | não recebe | 5 — painel que governa a instalação, fora do alcance |
        | panel_user    | recebe     | 3b — painel de negócio (o default do kit)         |
        | master_global | não recebe | 2 — trava por nome, que continua valendo          |

    Cenário: [CT-09] o alcance soma os painéis dos papéis do operador em qualquer organização
      Dado o operador "admin", que também tem o papel "panel_user" na organização Acme
      E a pessoa "Dani", sem papel algum
      Quando ele salva a ficha de Dani, no /admin, com o papel "panel_user" e a organização Acme
      Então Dani tem o papel "panel_user" no contexto da organização Acme
      E o formulário não devolve erro algum

    Cenário: [CT-10] o master_global concede papel de qualquer painel
      Dado o operador "master_global"
      E a pessoa "Dani", sem papel algum
      Quando ele salva a ficha de Dani com o papel "infra"
      Então Dani tem o papel "infra" no contexto global

    Esquema do Cenário: [CT-23] salvar a ficha não revoga o que o operador não poderia conceder
      Dado o operador "admin", cujo único papel tem painel "admin"
      E a pessoa "Dani", que já tem o papel "infra" no contexto global e "panel_user" na organização Acme
      Quando ele salva a ficha de Dani <acao>
      Então o formulário não devolve erro algum
      E Dani continua com o papel "infra" no contexto global
      E Dani continua com o papel "panel_user" no contexto da organização Acme
      E <efeito_da_gravacao>

      Exemplos:
        | acao                                    | efeito_da_gravacao                      | # célula        |
        | sem mexer no campo de papéis            | nada mais mudou na ficha                | no-op           |
        | acrescentando o papel "admin"           | Dani passa a ter também o papel "admin" | escrita efetiva |

    Cenário: [CT-11] o payload forjado não contorna o recorte
      Dado o operador "admin", cujo único papel tem painel "admin"
      E a pessoa "Dani", sem papel algum
      Quando a gravação de papéis é chamada direto com os identificadores dos papéis "infra" e "admin"
      Então Dani não tem o papel "infra", em contexto algum
      E Dani tem o papel "admin" — o payload não é descartado inteiro por causa do item recusado
```

**Camada**: componente Livewire (`EditUser` com `fillForm(['roles' => [...], 'tenants' => [...]])->call('save')`)
mais a chamada direta a `UserResource::gravarPapeis()` em CT-11 — o precedente do kit está em
`tests/Tenancy/PapeisPorOrganizacaoTest.php`. O oráculo é sempre `model_has_roles` (`pivotDePapeis()`),
nunca as opções renderizadas: **opção não é fronteira**.
**Suíte `Tenancy`**: CT-09 precisa de organização e de `model_has_roles.team_id`.

**Nota de discriminação (CT-09)**: o `/admin` não tem tenant na rota, então o contexto do request é o
global. Um operador cujo papel de `/app` vive no contexto da Acme só é reconhecido por uma leitura
"em qualquer contexto" — é a única linha que separa as duas leituras da premissa do RQ-04.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | recorte só na lista da tela, sem a trava na escrita | CT-11 |
| M12 | alternativa de topo (`orWhere` solto, sem agrupar) — a condição do nome vira alternativa e o super-admin volta a passar | CT-08, linha `master_global` |
| M13 | painéis lidos do papel do **painel corrente**, ou da relação filtrada pelo team do request, em vez de qualquer contexto | CT-09 |
| M14 | papel com `painel = null` tratado como "painel desconhecido" e bloqueado | CT-08, linha `etiquetador` |
| M15 | trava aplicada também ao `master_global` | CT-10 |
| M16 | comparação invertida — concede justamente o que está **fora** do alcance | CT-08, linhas `admin` e `infra` juntas |
| M35 | o recorte é aplicado ao conjunto enviado e o resultado vai para um `sync` — o que o operador não podia conceder é **revogado** de quem já tinha (escalada por subtração: um `admin` derruba o `infra` alheio, ou o próprio papel super-admin, com um clique em Salvar) | CT-23, linha `escrita efetiva` — a linha `no-op` sozinha não mata, porque uma gravação que só roda quando o campo mudou nunca executa ali |
| M36 | o recorte aborta a gravação inteira quando encontra um item fora do alcance, descartando também os papéis legítimos do mesmo payload | CT-11, segundo `Então` |

---

## Regra R4 — A trava vale nos três caminhos de concessão

> `RQ-05` · área B · perfil **completo** · técnica: **matriz caminho × alcance** (verbo irmão: a
> mesma regra em três superfícies distintas).

| Caminho | papel fora do alcance (`infra`) | papel dentro (`admin`) |
|---|---|---|
| ficha de usuário (`EditUser`) | CT-08 | CT-08 |
| convite individual (`CreateConvite`) | CT-12 | CT-12 |
| convite em massa (`convidarEmMassa`) | CT-13 | CT-13 |

```gherkin
  Regra: a trava de painel vale na ficha do usuário, no convite individual e no convite em massa

    Esquema do Cenário: [CT-12] o convite individual só oferta papel dentro do alcance do operador
      Dado o operador "admin", cujo único papel tem painel "admin"
      Quando ele cria um convite para "nova@example.com" com o papel "<papel>"
      Então o formulário <resultado_form>
      E <resultado_banco>

      Exemplos:
        | papel | resultado_form                | resultado_banco                                            |
        | infra | devolve erro no campo role_id | não existe convite para "nova@example.com"                 |
        | admin | não devolve erro algum        | existe convite para "nova@example.com" com o papel "admin" |

    Esquema do Cenário: [CT-13] o convite em massa herda a mesma trava
      Dado o operador "admin", cujo único papel tem painel "admin"
      Quando ele dispara o convite em massa para "um@example.com" e "dois@example.com" com o papel "<papel>"
      Então <resultado>

      Exemplos:
        | papel | resultado                                                     |
        | infra | não existe convite para nenhum dos dois endereços             |
        | admin | existem exatamente dois convites, ambos com o papel "admin" e "aceito_em" vazio |
```

**Camada**: componente Livewire. CT-12 =
`Livewire::test(CreateConvite::class)->fillForm([...])->call('create')->assertHasFormErrors(['role_id'])`
+ `assertDatabaseMissing('convites', ...)`. CT-13 =
`Livewire::test(ListConvites::class)->loadTable()->callAction(TestAction::make('convidarEmMassa'), ['emails' => ..., 'role_id' => ...])`
— o padrão exato de `tests/Kit/ConviteEmMassaTest.php`.

    Cenário: [CT-24] @premissa Q6 — o convite já gravado não concede papel fora do alcance no aceite
      Dado um convite pendente para "ja.tem@example.com", para a organização Acme, com o papel "infra"
      E que quem gravou esse convite é um operador "admin", sem alcance sobre o painel "infra"
      E a pessoa "ja.tem@example.com" autenticada
      Quando ela aceita o convite na tela "Convites recebidos"
      Então ela não recebe o papel "infra", em contexto algum
      E ela não é filiada à organização Acme
      E o convite continua com "aceito_em" vazio — não é queimado sem entregar nada

**Nota de camada e de discriminação (CT-12/CT-13/CT-24)**: `fillForm()` escreve o estado do
componente **direto**, sem passar pela lista renderizada — então a linha `infra` de CT-12/CT-13 já é
o payload forjado. O que ela **não** distingue é um recorte posto na consulta de opções de um Select
de relacionamento, porque o Laravel valida `exists` com a mesma consulta: pela tela, "opção" e
"escrita" colapsam num ponto só. O único ponto de concessão que não passa por Select algum é o
**aceite do convite** — daí CT-24, que é onde a exigência do RQ-04 ("a trava é na escrita") tem
consequência observável distinta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | trava só na ficha de usuário; os convites continuam com o recorte antigo (só por nome) | CT-12, CT-13 |
| M37 | trava posta só onde há Select — o papel de um convite já gravado é atribuído sem conferência no aceite | CT-24 (depende de Q6) |
| M41 | o aceite recusa o papel fora do alcance mas **consome** o convite assim mesmo: a pessoa fica sem papel, sem filiação e sem convite | CT-24, dois últimos `Então` |
| M18 | trava no convite individual, esquecida no lote (verbo irmão) | CT-13 |
| M19 | trava nos convites recusa **todo** papel, inclusive o do alcance | CT-12/CT-13, linhas `admin` |
| M20 | o lote grava e **depois** valida — recusa parcial, alguns endereços entram | CT-13, linha `infra`, que afirma sobre **os dois** endereços |

---

## Regra R5 — Conta existente não consome convite na volta do provedor

> `RQ-06`, `RQ-07` · área C · perfil **completo** · técnicas: **tabela de decisão** (existe conta
> local? × por qual ramo o provedor reconheceu a pessoa?) + **rastreio de efeito** sobre o aceite do
> convite (não aconteceu no fluxo social / aconteceu na tela / aconteceu na criação de conta).

| # | conta local | ramo do reconhecimento | convite consumido? | vira membro? | Cenário |
|---|---|---|---|---|---|
| 1 | existe | e-mail | **não** | não | CT-14 |
| 2 | existe | vínculo social já registrado | **não** | não | CT-16 |
| 3 | existe, e a pessoa aceita depois na tela | — | sim | sim | CT-15 |
| 4 | não existe | criação por convite | sim | sim | CT-17 |

```gherkin
  Regra: o convite de conta existente não é consumido na volta do provedor, e continua aceitável na tela

    Esquema do Cenário: [CT-14/CT-16] a volta do provedor não consome o convite de quem já tem conta
      Dado a pessoa "ja.tem@example.com", com conta ativa <estado_do_vinculo>
      E um convite pendente para "ja.tem@example.com", para a organização Acme, com o papel "panel_user"
      Quando ela volta do provedor Google com o token do convite no contexto da sessão
      Então ela fica autenticada como "ja.tem@example.com"
      E o convite continua com "aceito_em" vazio, dentro da validade e sem marca de recusa
      E ela não pertence à organização Acme
      E ela não tem o papel "panel_user" no contexto da organização Acme

      Exemplos:
        | estado_do_vinculo                    | # ramo  | # id  |
        | e sem vínculo com o Google           | e-mail  | CT-14 |
        | e já vinculada ao Google pelo sub-1  | vínculo | CT-16 |

    Cenário: [CT-15] o convite que sobrou é aceito na tela autenticada
      Dado a pessoa "ja.tem@example.com" autenticada, com o convite que sobrou da volta do provedor de CT-14
      Quando ela aceita o convite na tela "Convites recebidos"
      Então o convite fica com "aceito_em" preenchido
      E ela pertence à organização Acme
      E ela tem o papel "panel_user" no contexto da organização Acme

    Cenário: [CT-25] o link de confirmação de vínculo também não consome o convite
      Dado a pessoa "ja.tem@example.com", com conta ativa e o modo estrito de vínculo ligado
      E um convite pendente para ela, para a organização Acme, com o papel "panel_user"
      E o token do convite no contexto da sessão
      Quando ela abre o link de confirmação de vínculo recebido por e-mail
      Então ela fica autenticada como "ja.tem@example.com"
      E o vínculo dela com o Google passa a existir
      E o convite continua com "aceito_em" vazio
      E ela não pertence à organização Acme
      E ela não tem o papel "panel_user" no contexto da organização Acme

    Cenário: [CT-17] o token continua criando conta nova pelo provedor
      Dado que não existe conta local para "nova@example.com"
      E um convite pendente para "nova@example.com", para a organização Globex, com o papel "panel_user"
      Quando ela volta do provedor Google com o token do convite no contexto da sessão
      Então existe a conta "nova@example.com", autenticada, com origem "google"
      E o convite fica com "aceito_em" preenchido
      E ela pertence à organização Globex com o papel "panel_user"
```

**Camada**: `Feature` HTTP para CT-14/CT-16/CT-17
(`withSession(['login_social.contexto' => ['token' => $token]])->get('/auth/google/callback')`),
componente Livewire para CT-15 (`ConvitesRecebidos`, ação `aceitar`). **Suíte `Tenancy`** — a
asserção mais forte é a não-filiação à organização, que exige tenancy.

**CT-17 já existe** como CT-C04 em `tests/Kit/CadastroSocialPorConviteTest.php` e em
`tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php`: entra aqui como **linha de controle**,
sem teste novo. É ele que impede a correção de virar "o social parou de aceitar convite".

**Nota de discriminação**: o `Então` afirma o **agregado** (filiação à organização e papel no
`team_id` certo), não só `aceito_em`. Uma implementação que deixe de gravar a data mas continue
filiando a pessoa à organização de terceiro é o mesmo furo com outra cara — e passaria num cenário
que só olhasse `aceito_em`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M21 | remove o aceite só do ramo do e-mail e mantém o do vínculo (verbo irmão) | CT-16 |
| M22 | remove o aceite dos dois ramos **e também** da criação de conta nova | CT-17 |
| M23 | em vez de deixar pendente, marca o convite como recusado ou expirado | CT-14, que afirma o convite **dentro da validade e sem marca de recusa** — e CT-15, que agora encadeia a partir do convite deixado por CT-14, em vez de usar fixture própria |
| M24 | deixa de gravar `aceito_em`, mas continua filiando a pessoa e atribuindo o papel | CT-14, últimos dois `Então` |
| M25 | apenas **reordena** (aceita depois da barreira de indisponibilidade), mantendo o aceite automático para conta ativa | CT-14 |
| M38 | o aceite sai do retorno do provedor mas sobrevive no **terceiro** ramo de autenticação de conta existente — a confirmação do vínculo pelo link assinado, que enxerga o mesmo contexto de sessão | CT-25 |

---

## Regra R6 — Conta indisponível não consome convite nem abre sessão

> `RQ-08` · área C · perfil **completo** · técnica: **tabela estado × evento**, com 100% das células
> de indisponibilidade e uma célula válida de controle.

| Estado da conta local | volta do provedor com token de convite no contexto |
|---|---|
| ativa | sessão aberta, convite **pendente** (CT-18, controle) |
| `ativo = false` | sem sessão, convite **pendente** (CT-18) |
| excluída (soft delete) | sem sessão, convite **pendente** (CT-18) |
| `aprovacao_pendente = true` | sem sessão, convite **pendente** (CT-18) |

```gherkin
  Regra: convite não é consumido por conta indisponível

    Esquema do Cenário: [CT-18] a conta indisponível não queima o convite
      Dado a pessoa "ja.tem@example.com" com a conta <estado>
      E um convite pendente para "ja.tem@example.com" com o papel "panel_user"
      Quando ela volta do provedor Google com o token do convite no contexto da sessão
      Então <sessao>
      E o convite continua com "aceito_em" vazio
      E o convite continua com o token de lembrete intacto

      Exemplos:
        | estado                | sessao                                         | # célula |
        | ativa                 | ela fica autenticada como "ja.tem@example.com" | controle |
        | desativada            | nenhuma sessão é aberta                        | inválida |
        | excluída logicamente  | nenhuma sessão é aberta                        | inválida |
        | pendente de aprovação | nenhuma sessão é aberta                        | inválida |

    Cenário: [CT-26] a conta que nasce pendente de aprovação também não queima o convite
      Dado que a instalação exige aprovação manual de cadastro
      E que não existe conta local para "nova@example.com"
      E um convite pendente para "nova@example.com" com o papel "panel_user"
      Quando ela volta do provedor Google com o token do convite no contexto da sessão
      Então existe a conta "nova@example.com", com origem "google" e aprovação pendente
      E nenhuma sessão é aberta
      E o convite continua com "aceito_em" vazio e com o token de lembrete intacto

**Camada**: `Feature` HTTP, suíte `Kit`, em `tests/Kit/LoginSocialContaIndisponivelTest.php` — o
arquivo já tem o helper local `callbackDoGoogle()` e as três personas de indisponibilidade; o que
falta lá é a asserção **sobre o convite**, que é o oráculo do RQ-08. Manter no mesmo arquivo evita
mover helper para `tests/Pest.php` sem necessidade (`.ai/rules/testes.md`).

**Nota de discriminação**: a linha `ativa` é a que impede o cenário de virar tautologia — sem ela,
uma implementação que nunca consumisse convite **em caso algum** passaria em todas as linhas, e o
oráculo mediria a ausência de feature em vez da ordem das barreiras. Ela também é a linha que separa
R6 de R5: nas duas o convite fica pendente, mas aqui a sessão **não** abre.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | aceite do convite reintroduzido **antes** da barreira de indisponibilidade (o defeito F-03 original) | CT-18, linhas inválidas |
| M27 | barreira só para `ativo = false`, sem cobrir soft delete e aprovação pendente | CT-18, linhas `excluída` e `pendente de aprovação` |
| M28 | conta indisponível passa a abrir sessão (barreira removida) | CT-18, coluna `sessao` das linhas inválidas |
| M29 | conta indisponível deixa de entrar **e** a conta ativa também para de entrar | CT-18, linha `ativa` |
| M39 | a barreira percorre só contas **preexistentes**: a conta criada pelo convite nasce pendente de aprovação e o convite é consumido do mesmo jeito — queimado por quem talvez nunca seja aprovado | CT-26 |
| M40 | com aprovação manual ligada, o ramo de criação por convite é abortado antes de criar a conta — o convite fica intacto, mas a feature do RQ-07 some nessa partição | CT-26, primeiro `Então` |

---

## Regra R7 — O link assinado de confirmação de vínculo vale uma única vez

> `RQ-09` · área D · perfil **padrão** · técnicas: **idempotência ancorada no agregado** (o vínculo
> persistido e a sessão, não o retorno da chamada) + **BVA** sobre a janela de tempo.

```gherkin
  Regra: o link de confirmação de vínculo social é de uso único

    Cenário: [CT-19] o primeiro uso vincula e entra
      Dado o modo estrito de vínculo ligado e a pessoa "dona@example.com" com o link recebido por e-mail
      Quando ela abre o link
      Então ela fica autenticada como "dona@example.com"
      E existe um vínculo do Google "sub-1" apontando para "dona@example.com"

    Cenário: [CT-20] o segundo uso do mesmo link é recusado
      Dado o link de confirmação de "dona@example.com" já usado uma vez
      Quando o mesmo link é aberto de novo, sem sessão
      Então nenhuma sessão é aberta
      E a resposta é o redirect da recusa do login social, e não o 403 de assinatura inválida
      E existe exatamente um vínculo no banco, ainda apontando para "dona@example.com"

    Cenário: [CT-21] o link de uma pessoa não invalida o da outra
      Dado o link de confirmação de "dona@example.com" já usado uma vez
      E o link de confirmação de "outra@example.com", ainda não usado
      Quando "outra@example.com" abre o link dela
      Então ela fica autenticada como "outra@example.com"
      E existem dois vínculos no banco, um para cada pessoa

    Esquema do Cenário: [CT-22] o link continua queimado em toda a janela de validade da assinatura
      Dado o link de confirmação de "dona@example.com" já usado uma vez
      E que se passaram <minutos> minutos, com a assinatura ainda válida
      Quando o mesmo link é aberto de novo, sem sessão
      Então nenhuma sessão é aberta
      E a resposta é o mesmo redirect de recusa de CT-20, e não o 403 de assinatura vencida
      E existe exatamente um vínculo no banco

      Exemplos:
        | minutos | # ponto da janela                                  |
        | 20      | interior                                           |
        | 29      | borda−1 da assinatura — a marca ainda tem de valer  |

    Esquema do Cenário: [CT-27] o segundo link legítimo da mesma pessoa continua valendo
      Dado o link de confirmação do Google de "dona@example.com", já usado uma vez
      E um segundo link legítimo para ela, <origem_do_segundo>
      Quando ela abre o segundo link
      Então ela fica autenticada como "dona@example.com"
      E o primeiro link, aberto de novo, continua recusado

      Exemplos:
        | origem_do_segundo                      | # partição                              |
        | reenviado para o mesmo provedor Google | mesma pessoa, MESMO provedor            |
        | emitido para ela no GitHub             | mesma pessoa, outro provedor            |

**Camada**: `Feature` HTTP, suíte `Kit`, em `tests/Kit/VinculoDeProvedorSocialTest.php` — o link sai
de `Notification::assertSentTo(..., ConfirmarVinculoSocial::class)`, exatamente como CT-V04 já faz.
**CT-19 já existe** como CT-V04: entra como linha de controle, sem teste novo.

**Plataforma**: `CACHE_STORE=array` guarda a marca de uso no processo do teste, e os dois requests do
cenário são o mesmo processo — é o que torna CT-20 executável. (A ADR-04 avisa que o driver `array`
não persiste **entre processos**; aqui não precisa.)

**Tempo (CT-22)**: `travelTo(now()->addMinutes(20))` + `travelBack()` — 20 min está **dentro** dos 30
min da assinatura, que é a janela em que o defeito é observável. Fora dela o middleware `signed` já
recusa, e o cenário mediria o middleware em vez da marca de uso.

**Estouro de teto declarado**: o perfil `padrão` prevê 3 cenários por regra e há 5. CT-19 é controle
já existente, então os cenários **novos** são 4. CT-22 e CT-27 sobrevivem porque são os únicos
matadores de M32 e de M31b, e o gate de falsificabilidade vence o teto — mutante vivo é pior que
cenário a mais.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | a marca de uso é gravada mas o resultado nunca é consultado | CT-20 |
| M31a | a marca é fixa por rota — o link de qualquer pessoa fica queimado depois do primeiro uso de qualquer outra | CT-21 |
| M31b | a marca é por **usuário**, ou por `(usuário, provedor)`, em vez de por assinatura — o segundo link legítimo do mesmo dono nasce morto | CT-27, cujas duas linhas são "mesmo provedor" e "outro provedor": só a primeira mata a variante `(usuário, provedor)` |
| M32 | janela da marca menor que os 30 min da assinatura | CT-22, linha `29` — a linha `20` sozinha deixa passar qualquer janela entre 21 e 30 min |
| M42 | a marca é sobrescrita a cada confirmação (uma por usuário, com reset) — usar o segundo link revive o primeiro | CT-27, `E o primeiro link, aberto de novo, continua recusado` |
| M33 | a marca é gravada **depois** de vincular e logar, e a recusa nunca acontece porque o vínculo é idempotente (`firstOrCreate` não deixa rastro do segundo uso) | CT-20 — a asserção é sobre a **sessão**, que o `firstOrCreate` não esconde |
| M34 | leitura seguida de escrita não atômica em vez de uma operação atômica | ⚠️ **sem matador** — lacuna declarada. Tentado: dois `get()` no mesmo caso são sequenciais no mesmo processo, e o store `array` não tem como intercalar; espionar a chamada provaria a **implementação**, não o comportamento. Fica como item de revisão de código no `06-relatorio-qa.md`. |

---

## Checklist de Taxonomia

<!-- Resposta válida: um ID de cenário, "não se aplica: {motivo}", ou "lacuna declarada: {tentado}". -->

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | CT-21 e CT-27 (o link de uma pessoa não age sobre a conta da outra, nem sobre o segundo link dela própria). O aceite de convite alheio já é coberto por `exigirDono()` em `tests/Tenancy/ConviteUsuarioExistenteTest.php` |
| Autorização exercida na ação (não só `can()`) | CT-05 (as Actions são disparadas e o oráculo é o registro no banco), CT-11 (chamada direta à gravação) |
| Idempotência (ancorada no agregado) | CT-20, com CT-27 fechando a variante "marca por usuário" — agregado = sessão + vínculos persistidos, nunca o retorno da chamada |
| Concorrência | lacuna declarada: M34 — dois requests no mesmo processo de teste são sequenciais; sem forma de intercalar com o store `array` do `phpunit.xml` |
| Fronteira no ponto de entrada (gravação) | CT-01 (criação **e** edição), CT-12, CT-13 |
| Domínio condicionado (painel do papel × painéis do operador) | CT-08 |
| Estado × operação de escrita | CT-05 (papel super-admin × editar/excluir/excluir em massa), CT-18 (conta indisponível × volta do provedor) |
| Ausente ≠ null ≠ vazio | CT-08, linha `etiquetador`: `painel = null` tem semântica declarada — "não abre painel algum, logo é concedível" (premissa 2 do `00`) |
| Paginação / ordenação | não se aplica: nenhuma cláusula toca listagem paginada ou ordenação |
| Timezone / DST | não se aplica: a janela de R7 é relativa (`now()->addMinutes(30)`), sem data absoluta nem comparação `date` × `datetime`. A passagem de tempo é exercitada em CT-22 |
| Unicode / limite de varchar | CT-01, linhas `@premissa` (caixa e espaços nas bordas do nome) — bloqueado por Q2 |
| Unicidade + soft delete | não se aplica: `App\Models\Role` não usa `SoftDeletes`. A metade viva da armadilha — unicidade contra o próprio registro — é CT-02 |
| CRUD combinado | CT-02 (salvar o registro sem alterar o campo protegido, gravando outro), CT-06 (as duas células válidas separadas), CT-23 (salvar sem mexer no campo e não perder o que já estava lá) |
| Mass assignment | CT-11 (identificador de papel fora do alcance injetado direto na gravação) e CT-24 (o mesmo valor chegando pelo convite já gravado, sem Select algum no caminho) |
| Upload | não se aplica: nenhuma cláusula toca arquivo |
| Precisão monetária | não se aplica: nenhum valor monetário |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | nome reservado recusado na criação e na renomeação | R1 | EP × ponto de entrada | Livewire | `tests/Kit/TelaDePapeisTest.php` | M1, M5 |
| CT-02 | master salva o papel super-admin sem alterar o nome, gravando outro campo | R1 | unicidade contra si mesmo + célula válida | Livewire | `tests/Kit/TelaDePapeisTest.php` | M3a |
| CT-03 | o nome reservado acompanha a configuração | R1 | valor discriminante | Livewire | `tests/Kit/TelaDePapeisTest.php` | M2 |
| CT-04 | operador comum cria papel com outro nome | R1 | partição válida | Livewire | `tests/Kit/TelaDePapeisTest.php` | M4 |
| CT-05 | operador comum não edita nem exclui o papel super-admin | R2 | matriz persona × operação (célula abrir/salvar colapsada) | Livewire | `tests/Kit/TelaDePapeisTest.php` | M6, M7, M10 |
| CT-06 | operador comum edita e exclui papel comum (uma operação por linha) | R2 | célula válida por coluna | Livewire | `tests/Kit/TelaDePapeisTest.php` | M9 |
| CT-07 | master exclui o papel super-admin | R2 | linha de controle | Livewire | `tests/Kit/TelaDePapeisTest.php` | M8 |
| CT-08 | alcance do operador recorta o papel concedido | R3 | tabela de decisão | Livewire | `tests/Tenancy/PapeisPorOrganizacaoTest.php` | M12, M14, M16 |
| CT-09 | alcance soma os painéis em qualquer organização | R3 | valor discriminante | Livewire | `tests/Tenancy/PapeisPorOrganizacaoTest.php` | M13 |
| CT-10 | master concede papel de qualquer painel | R3 | linha de controle | Livewire | `tests/Tenancy/PapeisPorOrganizacaoTest.php` | M15 |
| CT-11 | payload forjado não contorna o recorte, e o legítimo do mesmo payload é gravado | R3 | mass assignment | Feature (chamada direta) | `tests/Tenancy/PapeisPorOrganizacaoTest.php` | M11, M36 |
| CT-12 | convite individual herda a trava | R4 | matriz caminho × alcance | Livewire | `tests/Tenancy/PapeisPorOrganizacaoTest.php` | M17, M19 |
| CT-13 | convite em massa herda a trava | R4 | verbo irmão | Livewire | `tests/Tenancy/PapeisPorOrganizacaoTest.php` | M18, M20 |
| CT-14 | volta do provedor não consome convite de conta existente (ramo e-mail) | R5 | tabela de decisão | Feature | `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` | M23, M24, M25 |
| CT-15 | o convite pendente é aceito na tela autenticada | R5 | célula válida | Livewire | `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` | M23 |
| CT-16 | idem CT-14, pelo ramo do vínculo | R5 | verbo irmão | Feature | `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` | M21 |
| CT-17 | conta nova continua sendo criada e consumindo o convite | R5 | linha de controle (**já existe**: CT-C04) | Feature | `tests/Kit/CadastroSocialPorConviteTest.php`, `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` | M22 |
| CT-18 | conta indisponível não queima o convite | R6 | estado × evento | Feature | `tests/Kit/LoginSocialContaIndisponivelTest.php` | M26, M27, M28, M29 |
| CT-19 | primeiro uso do link vincula e entra | R7 | linha de controle (**já existe**: CT-V04) | Feature | `tests/Kit/VinculoDeProvedorSocialTest.php` | — |
| CT-20 | segundo uso do mesmo link é recusado | R7 | idempotência no agregado | Feature | `tests/Kit/VinculoDeProvedorSocialTest.php` | M30, M33 |
| CT-21 | o link de uma pessoa não invalida o da outra | R7 | valor discriminante (duas personas) | Feature | `tests/Kit/VinculoDeProvedorSocialTest.php` | M31a |
| CT-22 | o link segue queimado em toda a janela da assinatura (20 e 29 min) | R7 | BVA de tempo, 2 pontos | Feature | `tests/Kit/VinculoDeProvedorSocialTest.php` | M32 |
| CT-23 | salvar a ficha não revoga o que o operador não poderia conceder (no-op e escrita efetiva) | R3 | partição sobre o estado prévio do alvo | Livewire | `tests/Tenancy/PapeisPorOrganizacaoTest.php` | M35 |
| CT-24 | o convite já gravado não concede papel fora do alcance no aceite | R4 | rastreio até o ponto real de gravação (`@premissa` Q6) | Livewire | `tests/Tenancy/ConviteUsuarioExistenteTest.php` | M37, M41 |
| CT-25 | o link de confirmação de vínculo também não consome o convite | R5 | evento faltante na tabela estado × evento | Feature | `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` | M38 |
| CT-26 | a conta nasce pendente de aprovação, entra na fila e não queima o convite | R6 | cruzamento estado × partição "conta nova" | Feature | `tests/Kit/CadastroSocialPorConviteTest.php` | M39, M40 |
| CT-27 | o segundo link legítimo da mesma pessoa continua valendo (mesmo provedor e outro) | R7 | valor discriminante (duas assinaturas, um dono) | Feature | `tests/Kit/VinculoDeProvedorSocialTest.php` | M31b, M42 |

**Arquivos novos: nenhum.** Os 25 cenários novos entram em sete arquivos que já existem, ao lado dos
casos da mesma superfície. Nenhum helper novo em `tests/Pest.php` — todos os arranjos usam
`usuarioDoKit`, `usuarioComPapel`, `papelNaOrganizacao`, `pivotDePapeis`, `ofertaPara`,
`ligarProvedor`, `usuarioSocialFalso` e `tenant`, que já vivem lá.

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Visita `GET /admin/shield/roles` com o operador `admin` | já provado por `tests/Kit/PermissoesDeResourcesTest.php`; visita não é gravação e não mata mutante algum desta wiki |
| `assertActionHidden(DeleteAction)` sobre o papel super-admin | esconder é UX, não fronteira (ADR-01): o cenário passaria com a autorização intacta, e o `mountAction` ainda alcançaria a ação |
| Papel de `/infra` cunhado por um `admin` continuar existindo (o "papel órfão" da ADR-02) | consequência declarada, não requisito — nenhuma RQ afirma sobre ele |
| Log `[LoginSocialController@retorno] Convite pendente não consumido` | efeito colateral só do plano; ver `## Fronteira com o Plano` |
| Convite **expirado** na volta do provedor | com R5 o ramo social não consome convite em estado algum — a partição não discrimina implementação alguma |
| Cenário de concorrência sobre a marca de uso do link | inexpressável no arnês (M34); vira lacuna declarada |
| `restore` / `forceDelete` do papel super-admin | `Role` não usa `SoftDeletes` e não há Action correspondente: o cenário não tem como ser executado (Q3) |
| Cenário `Kit` (sem tenancy) espelhando CT-14 | o oráculo forte é a não-filiação à organização, que só existe com tenancy; a versão sem organização mataria menos mutantes pelo mesmo custo |

---

## Testes existentes que mudam de oráculo

| Teste | Oráculo hoje | Oráculo depois | Cláusula |
|---|---|---|---|
| `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` — CT-C08, *"faz a conta existente aceitar o convite na volta do provedor"* | conta existente volta do provedor → vira membro da Acme, `aceito_em` preenchido | vira **CT-14**: continua autenticando, mas `aceito_em` fica vazio e ela **não** é membro. O aceite migra para CT-15, na tela `ConvitesRecebidos` | RQ-06 |
| `tests/Kit/LoginSocialContaIndisponivelTest.php` — CT-13, CT-14, CT-15 | recusam a sessão; **não afirmam nada sobre convite** | ganham a asserção do convite intacto (CT-18). Não é mudança de oráculo: é o oráculo que faltava — sem ele o defeito F-03 atravessa os três casos verdes | RQ-08 |
| `tests/Kit/VinculoDeProvedorSocialTest.php` — CT-V04 | abre o link uma vez e entra | **inalterado** (um uso só). Vira a linha de controle CT-19 | RQ-09 |
| `tests/Kit/TelaDePapeisTest.php` | opera inteiro como `master_global` | **inalterado** — o `Gate::before` atravessa as travas novas. É a evidência de que R1/R2 não trancam o dono fora | RQ-01..RQ-03 |
| `tests/Kit/ConviteEmMassaTest.php`, `tests/Kit/ConviteTest.php` | concedem com `master_global` | **inalterados**, pela mesma razão | RQ-04, RQ-05 |
| `tests/Tenancy/ConviteEmMassaTenancyTest.php` | `admin_app` (painel `app`) convida com `panel_user` (painel `app`) | **inalterado**: mesmo painel, dentro do alcance | RQ-04 |
| `tests/Tenancy/PapeisPorOrganizacaoTest.php` (não commitado) | `master_global` concede; `admin` não concede `master_global` | **inalterado**, e recebe CT-08..CT-13 e CT-23 | RQ-04, RQ-05 |
| `tests/Tenancy/ConviteUsuarioExistenteTest.php` | aceite do convite pelo dono, na tela | **inalterado**; recebe CT-24, que depende de **Q6** | RQ-05 |

> **Atenção**: se a resposta a **Q1** for "o `admin` precisa continuar concedendo papel de `/app`",
> CT-08 (linha `panel_user`) e CT-12/CT-13 mudam de oráculo antes de serem escritos, e R3 precisa ser
> reformulada. **Q1 bloqueia o passo 3 do plano.**

---

## Sem CT-B

Concordo com o gate declarado no `01-plano-acao.md`. Nenhuma das nove cláusulas afirma sobre
JavaScript executado, console, acessibilidade, cor, tema ou layout:

- RQ-01..RQ-05 são **validação de formulário, autorização de ação e gravação** — três coisas que
  `Livewire::test(...)->fillForm()->call()` e `callAction(TestAction::…)` provam em milissegundos,
  com oráculo no banco. Empurrar para o navegador trocaria uma asserção sobre
  `roles`/`model_has_roles` por uma asserção sobre pixel.
- RQ-06..RQ-09 são **redirect HTTP e efeito no banco**, num controller sem tela própria.
- As telas envolvidas (`Roles`, `EditUser`, `Convites`) já têm cobertura de navegador no inventário
  de telas do kit, e nada nesta wiki muda o HTML ou o JS delas.

O arquivo `05-casos-de-teste-browser.md` **não foi criado**.

---

## Achados da Revisão Adversarial

Rodada 1 executada por sub-agente independente, que recebeu **apenas** o `00-requisito.md` e este
arquivo — sem o plano, sem as ADR, sem código e sem o raciocínio da derivação. Nove lacunas, todas
fechadas. Nenhuma foi cosmética: quatro eram cláusula sem falsificação, três eram oráculo que passava
com a implementação defeituosa, duas eram matador declarado que não matava.

| # | Lacuna apontada | Regra | O que virou |
|---|---|---|---|
| 1 | a trava do convite podia morar só nas opções do Select — e nenhum cenário do convite forjava o payload | R4 | **CT-24** + a nota que explica por que, pela tela, "opção" e "escrita" colapsam num ponto só, e por que o aceite é o único ponto que as separa. Pergunta **Q6** |
| 2 | todo alvo de R3 era "sem papel algum": um filtro aplicado ao array de entrada seguido de `sync` revoga o que o operador não podia conceder — escalada por **subtração** | R3 | **CT-23** e o mutante **M35** |
| 3 | CT-02 exercitava só a célula "nome inalterado", então **M3 não morria**: uma guarda condicionada a "o nome mudou" isenta o master por acidente | R1 | CT-02 passou a **gravar uma permissão** (célula válida de verdade); M3 foi dividido em **M3a** (morto) e **M3b** (**sem matador**, inexpressável — o `unique` já barra todo mundo) |
| 4 | o aceite do convite podia sobreviver no **terceiro** ramo de autenticação de conta existente: o link assinado de confirmação de vínculo | R5 | **CT-25** e o mutante **M38** |
| 5 | a tabela estado × evento de R6 só percorria conta **preexistente**; a conta que nasce pendente de aprovação queimava o convite | R6 | **CT-26**, o mutante **M39** e a pergunta **Q7** |
| 6 | RQ-05 enumera três caminhos, mas o papel do convite só vira atribuição no **aceite** — um quarto ponto de gravação | R4 | **CT-24** (`@premissa`) e a pergunta **Q6** |
| 7 | **M31 não morria inteiro**: CT-21 usa duas pessoas, e a variante "marca por usuário" passava ilesa | R7 | **CT-27** (dois links, um dono) e M31 dividido em **M31a**/**M31b** |
| 8 | CT-05 afirmava "a operação é recusada" sem não-efeito por atributo: um `->disabled()` no campo `name` que continua salvando permissões e `painel` passava | R2 | CT-05 ganhou a asserção sobre a matriz de permissões e o `painel` do registro |
| 9 | CT-06 tinha **dois `Quando`** e o rename ficava sem oráculo (asserção só de ausência) | R2 | CT-06 virou `Esquema do Cenário` com uma operação por linha, e a linha de rename afirma o registro **presente** com o nome novo |

Fora da lista, dois oráculos fracos apontados na mesma rodada e também corrigidos: CT-11 passou a
afirmar que os papéis legítimos do mesmo payload **são** gravados (mutante **M36**), e CT-20/CT-22
passaram a distinguir o redirect de "link já usado" do 403 de assinatura inválida ou vencida — sem
isso, CT-22 era CT-20 com `travelTo` e as mesmas asserções.

### Rodada 2 — resultado

O fechamento da rodada 1 criou cenário novo (CT-23..CT-27), então a skill obriga uma segunda passada,
com o mesmo contrato e outro sub-agente. Ela achou **5 lacunas de segunda ordem** — todas na
superfície que os cenários novos introduziram, o que é exatamente o risco que a segunda rodada existe
para cobrir — mais 6 oráculos fracos e 4 matadores declarados que não matavam. Todas fechadas, sem
cenário novo: o conserto foi **linha de `Exemplos` faltando** e **`Então` faltando**, não caso a mais.

| # | Lacuna de segunda ordem | Regra | O que virou |
|---|---|---|---|
| 1 | CT-23 só percorria a célula **no-op** (`salva sem mexer no campo`): uma implementação que filtra e dá `sync` **quando o campo muda** nunca executava ali, e M35 seguia vivo | R3 | CT-23 virou `Esquema` com a linha `escrita efetiva` — o operador acrescenta `admin` a quem já tem `infra`, e o `infra` sobrevive |
| 2 | CT-27 variou o **provedor**, não a assinatura: uma marca por `(usuário, provedor)` passava e matava o **reenvio legítimo do mesmo provedor** | R7 | CT-27 virou `Esquema` com as duas partições, e a linha `mesmo provedor` é a que mata M31b |
| 3 | CT-22 fixava **um ponto interior** (20 min): qualquer janela entre 21 e 30 min passava e reabria o link dentro da assinatura | R7 | CT-22 virou `Esquema` com `20` e `29` — o segundo é a borda−1 da assinatura |
| 4 | CT-26 afirmava só o negativo: uma implementação que **aborta a criação da conta** quando há aprovação manual passava, e RQ-07 ficava sem falsificação nessa partição | R6 × RQ-07 | CT-26 passou a afirmar que a conta **existe**, com origem `google` e aprovação pendente (mutante **M40**) |
| 5 | CT-24 afirmava só "não recebe o papel": consumir o convite sem entregar nada passava, e o `Dado` não fixava o autor do convite | R4 | CT-24 fixa quem gravou o convite e afirma o convite **ainda pendente** e a não-filiação (mutante **M41**) |

Oráculos fracos corrigidos na mesma passada: **CT-11** ganhou `em contexto algum` (sem ele, gravar o
papel noutro `team_id` passava); **CT-25** ganhou a asserção do papel e a de que o vínculo **é**
criado; **CT-27** ganhou `o primeiro link continua recusado` (mutante **M42**, marca com reset);
**CT-26** herdou a asserção do token de lembrete intacto.

Dois matadores declarados que não matavam, além dos já citados: **M23** apontava um par CT-14+CT-15
que não era par — CT-14 não distinguia "pendente" de "recusado/expirado" e CT-15 usava fixture
própria. CT-14 passou a afirmar o convite **dentro da validade e sem marca de recusa**, e CT-15
**encadeia** a partir do convite deixado por CT-14.

Achado de estrutura aceito e declarado, não corrigido: **CT-05** tinha duas linhas (`abrir` e
`salvar`) sem poder discriminante independente, porque a página de edição do Filament autoriza no
`mount`. As duas viraram **uma célula só**, declarada como colapsada na nota da R2.

**Teto de rodadas atingido (2).** A rodada 2 não produziu achado estrutural que exigisse desdobrar
regra: as 5 lacunas eram de **cobertura de partição dentro da regra existente**, e todas couberam em
linha de `Exemplos`. Terceira rodada não se justifica.

---

## Comandos

```bash
# verificação da feature
php artisan test --testsuite=Kit,Tenancy --compact

# um arquivo por vez, durante o desenvolvimento
php artisan test --compact tests/Tenancy/PapeisPorOrganizacaoTest.php

# fechamento do ciclo (exige PCOV, ou XDEBUG_MODE=coverage)
vendor/bin/pest tests/Kit tests/Tenancy --mutate --path=app/Support/AdministradorDaInstalacao.php
vendor/bin/pest tests/Kit tests/Tenancy --mutate --path=app/Policies/RolePolicy.php
vendor/bin/pest tests/Kit tests/Tenancy --mutate --path=app/Http/Controllers/Auth/LoginSocialController.php
```

`pestphp/pest-plugin-mutate` está declarado em `require-dev` do `composer.json` — não é dependência
transitiva, o comando não some num `composer update`.

Mutante sobrevivente é traduzido de volta para lacuna de derivação e vira cenário novo aqui, não
"asserção a mais" no teste que sobrou.


---

## Cenários não implementados, com o motivo

Escritos na derivação e **não** transformados em teste, porque as premissas que os sustentavam
foram resolvidas em sentido contrário depois — em `00-requisito.md` → `## Ambiguidades`. Ficam
aqui, e não apagados, porque cada um vira teste no dia em que a premissa mudar.

| CT | Por que não foi escrito | O que o faria voltar |
|---|---|---|
| **CT-24** (aceite do convite não concede papel fora do alcance) | Q6 pôs a trava na **criação** do convite, não no aceite. Revalidar no aceite exigiria consultar o alcance de **quem gravou** o convite num momento em que esse operador não está autenticado — e, depois desta wiki, é impossível gravar convite com papel fora do alcance. Sobra a **janela histórica**: convites gravados antes, ainda pendentes, concedem no aceite o papel que têm. | Decidir que o aceite revalida; ou uma migration que expire convites pendentes com papel fora do alcance de quem os gravou |
| **CT-26** (conta que nasce pendente não queima o convite) | Medido no código: `Convite::aceitar()` cria a conta sem `aprovacao_pendente` (`app/Models/Convite.php`, `User::create` + `forceFill`). Cadastro por convite **é** a aprovação, então a conta nunca nasce pendente e o cenário não tem caso observável. | Passar a marcar conta de convite como pendente em alguma configuração |

**CT-17 e CT-19 não precisaram ser escritos**: são controles positivos que a suíte já tem — a conta
nova nascendo pelo convite no fluxo social (`tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php`)
e o link de confirmação funcionando no primeiro uso (`tests/Kit/VinculoDeProvedorSocialTest.php`,
CT-V04). Escrever cópia deles seria cobertura duplicada.
