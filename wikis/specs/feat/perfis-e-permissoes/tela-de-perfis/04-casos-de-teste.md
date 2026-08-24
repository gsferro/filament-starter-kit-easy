# Casos de Teste — Tela de perfis (papéis) do /admin

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação —
> ela não existe ainda. A implementação **existente** (`RoleResource` publicado) foi lida apenas
> para saber paths, nomes de página e convenção de teste, nunca como fonte de comportamento
> esperado.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — `uuid` na rota do papel (RQ-08, RQ-09) | 3 (migração de dado em tabela de vendor, infra compartilhada) | 3 (rota de autorização; enumeração por id) | **9** | completo |
| A2 — contagem de usuários e slide-over (RQ-04, RQ-05) | 2 (integra com a relação do spatie e com tenancy) | 3 (expõe e-mail de terceiro) | **6** | padrão |
| A3 — contador de permissões e tab vertical (RQ-07, RQ-10) | 2 (sobrescreve trait de vendor) | 2 (erro de leitura leva a conceder permissão errada) | **4** | padrão |
| A4 — guard como seleção (RQ-11) | 2 (o valor alimenta o `firstOrCreate` da permission) | 3 (guard errado cria papel que nunca casa com ninguém) | **6** | padrão |
| A5 — rótulo, label e breadcrumb (RQ-01, RQ-02, RQ-03, RQ-06) | 2 (cinco pontos existentes) | 1 (cosmético, reversível) | **2** | mínimo |

- Técnicas aplicadas: EP, BVA 2-valores (contagem), tabela de decisão (guard × gravação),
  matriz papel × ação (autorização do slide-over), rastreio de efeito (log), normalização (chave
  do papel → rótulo), partição exaustiva de enum (os painéis registrados).
- Cenários: **24** · Regras: **9** · Mutantes previstos: **31** · Sem matador: **2**
  (R7/M3 e CT-B02/M1, os dois sobre aparência).
- **Fechados depois de implementar** (ver `## Reconciliação pós-implementação`, no fim): três
  cenários novos e um mutante novo, todos trazidos por lacuna encontrada em execução, não por
  refinamento de gabarito.

## Divergências com a skill, por Project Rule do projeto

- A skill sugere `pest --parallel --tia` como padrão. **Vence `.ai/rules/testes-browser.md`**:
  neste projeto `--parallel` derruba 4 dos 11 cenários de navegador, e sem PCOV o `--tia` não
  termina (medido, abortado após 35 min). Os comandos desta wiki são
  `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` e `composer test:browser`.
- A skill pede `pest --mutate` no fechamento. Roda **escopado** ao `RoleResource` e ao `Role`, com
  `XDEBUG_MODE=coverage`, e o resultado é registrado no `03-progresso.md` — não é gate de merge
  enquanto o ambiente não tiver PCOV.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | migration nova em `roles`; `App\Models\Role` ganha `TemUuid`; `RoleResource` ganha coluna, action, `getRecordTitle()` e o `Tabs` vertical; `AdminPanelProvider` ganha três labels; 4 pontos de UI passam a usar `Papeis::rotulo()` | CT-01, CT-11, CT-14 |
| **F**unction | contar usuários por papel; listar usuários de um papel; contar permissões marcadas por grupo; resolver o papel por `uuid`; validar o guard; exibir rótulo | CT-04…CT-17 |
| **D**ata | 5 papéis semeados **já existentes** no momento da migration (backfill); papel com 0 usuários; papel com o mesmo usuário em 2 organizações; papel cujo nome não está em `Papeis::ROTULOS` (deriva por `Str::headline`); `painel` nulo; `guard_name` fora da lista | CT-04, CT-05, CT-06, CT-08, CT-12, CT-16 |
| **I**nterfaces | rota HTTP `/admin/shield/roles{,/create,/{uuid},/{uuid}/edit}`; componentes Livewire `ListRoles`/`CreateRole`/`EditRole`; dois widgets do dashboard `/admin`; a action "Papéis nesta organização" do `UsersRelationManager`; o `Select` de papéis do `/app` | CT-09, CT-10, CT-13, CT-14 |
| **P**latform | **SQLite** — é onde a migration em três tempos importa (coluna NOT NULL em tabela com linhas falha) e onde `count(distinct …)` precisa funcionar. `permission.teams` ligado só em `tests/Tenancy`/`tests/BrowserTenancy` | CT-11, CT-06 |
| **O**perations | quem opera é `master_global` e `admin`; o slide-over expõe e-mail, então há um perfil que **pode** listar papéis e **não pode** ver o detalhe | CT-07 |
| **T**ime | *não se aplica*: nenhuma cláusula do requisito depende de data, expiração, agendamento ou concorrência. O `uuid` é gerado no `creating` e nunca recalculado. Único efeito temporal: o cache do `PermissionRegistrar`, coberto por CT-11 (a migration o limpa) | CT-11 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O recurso se chama "Papel"/"Papéis" em navegação, título e plural | A5 (mínimo) | RQ-01, RQ-02 | EP | CT-01 |
| R2 — O título do registro é o rótulo legível, não a chave — e é ele que vai ao breadcrumb | A5 (mínimo) | RQ-03, RQ-06 | normalização | CT-02, CT-03 |
| R3 — Todo ponto de UI que exibe nome de papel exibe o rótulo | A5 (mínimo) | RQ-06 | EP exaustiva sobre os pontos | CT-09, CT-10 |
| R4 — A listagem mostra quantas **pessoas distintas** têm cada papel | A2 (padrão) | RQ-04 | BVA 2-valores + EP | CT-04, CT-05, CT-06 |
| R5 — O slide-over lista todos os usuários do papel, uma vez cada, só para quem pode ver o papel, e deixa rastro | A2 (padrão) | RQ-05 | matriz papel×ação + rastreio de efeito | CT-07, CT-08, CT-B02 |
| R6 — Cada grupo de permissões exibe `selecionadas/total`, e o número acompanha a marcação | A3 (padrão) | RQ-07 | BVA 2-valores | CT-15, CT-16 |
| R7 — No tab "Recursos" há um tab vertical por painel registrado | A3 (padrão) | RQ-10 | partição exaustiva do conjunto de painéis | CT-17, CT-B01 |
| R8 — A rota do papel resolve por `uuid`; o `id` não resolve | A1 (completo) | RQ-08, RQ-09 | EP + tabela de decisão | CT-11, CT-12, CT-13, CT-14 |
| R9 — `guard_name` é seleção fechada pelas chaves de `config('auth.guards')` | A4 (padrão) | RQ-11 | EP + tabela de decisão | CT-18, CT-19, CT-20 |

Técnica escalada acima do perfil da área: **R2 usa normalização** (caixa/underscore/acento) apesar
de A5 ser `mínimo`, porque a regra é sobre transformação de string e EP sozinho não distingue
`Str::headline()` de `ucfirst()`.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome do método `acaoDeUsuarios()`, `usuariosDoPapel()`, `contagemDoPainel()` | escolha de implementação | detalhe do cenário; nenhum `Então` cita nome de método privado |
| `Tabs::make('paineis')` como chave do componente | escolha de implementação | detalhe; o `Então` afirma que **os dois painéis aparecem e um por vez**, não a chave |
| `->counts([… DB::raw('count(distinct …)')])` | escolha de implementação | o `Então` afirma o **número**, não o SQL |
| channel `autenticacao` para o log do slide-over | escolha de implementação (**mas** o requisito não pede log nenhum) | o CT-08 existe porque expor e-mail sem rastro é achado, não porque o PRD pediu — registrado como cenário **além** do requisito, marcado `@além-do-requisito` |
| rótulo "Usuários" da coluna nova | comportamento visível que o requisito não determina ("adicione a coluna de quantos usuários") | ⚠️ pergunta; premissa adotada: "Usuários" |
| texto "Papel"/"Papéis" (singular/plural exatos) | o requisito determina o **termo**, não a flexão | o `Então` afirma o termo escolhido; a flexão é detalhe |
| forma da contagem exibida (`3/12`) | comportamento visível que o requisito não determina ("exibir a quantidade") | ⚠️ pergunta; premissa adotada: `selecionadas/total` |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- O formato da contagem do RQ-07 é `selecionadas/total` ou só `selecionadas`? — bloqueia o `Então`
  literal de R6; premissa adotada: `selecionadas/total` (cenários CT-15 e CT-16 marcados
  `@premissa`). Se a resposta for "só selecionadas", os dois `Então` mudam de `1/8` para `1`.
- O rótulo da coluna nova é "Usuários"? — bloqueia o `assertSee` de CT-04; premissa adotada
  "Usuários". Cenário marcado `@premissa`.
- O slide-over deve mostrar mais que nome e e-mail (organização, data do vínculo)? — premissa
  adotada: nome e e-mail, porque é o que a listagem de usuários do `/admin` já mostra. CT-B02
  marcado `@premissa`.

## Setup Global

### Personas

- `master_global` — `usuarioCom('master_global')` (helper de `tests/Pest.php:…`). Vence pelo
  `Gate::before`, então abre tudo sem depender da matriz de permissões.
- `admin` — `usuarioCom('admin')`. Papel do painel `/admin`, sujeito à matriz do `PapeisSeeder`.
- **`operador_sem_view`** — persona nova, criada no próprio cenário: papel com `painel = 'admin'` e
  **apenas** `ViewAny:Role` atribuída. É a persona que separa "listar papéis" de "ver quem tem o
  papel" (CT-07). Sem ela, a matriz papel × ação de R5 fica com a barreira sem nenhum cenário —
  toda a matriz percorrida por `master_global` passa com o `->authorize()` removido.
- Em `tests/Tenancy`: `usuarioComPapel('panel_user', $org)` e `papelNaOrganizacao()`, para o caso
  da mesma pessoa em duas organizações.

### Fixtures

- **Não há factory para `Role`** (`ls database/factories/` não tem `RoleFactory`). Papel se cria
  com `Role::create(['name' => …, 'guard_name' => 'web', 'painel' => …])`, como
  `tests/Kit/PaineisTest.php:236` já faz.
- Os cinco papéis do kit vêm de `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`
  — par obrigatório, e `admin_app` **só existe em `tests/Tenancy`** (`.ai/rules/testes.md`).
- Usuários: `usuario('a@example.com')`, `usuarioCom($papel)`.

### Fakes

- Nenhum `Queue::fake()`/`Mail::fake()`: a feature não enfileira nem envia nada.
- Log: `espiarAutenticacao()` (helper existente em `tests/Pest.php`) para CT-08.

### Estratégia de DB

- `RefreshDatabase` global, aplicado por `tests/Pest.php` a `Kit`, `Tenancy`, `Feature` e `Unit`.
- Suíte: cenário single-tenant → `tests/Kit`; cenário que precisa de duas organizações →
  `tests/Tenancy` (o `TenancyTestCase` fixa `permission.teams` em `createApplication()`, antes das
  migrations — ligar em `beforeEach` é tarde demais).
- **Teste de componente exige `noPainelBootado('admin')`**: `Filament::setCurrentPanel()` não boota
  o painel, e sem o boot a página do Shield morre em *"Plugin [filament-shield] is not registered
  for panel [infra]"*. E **toda tabela do kit carrega adiada** — sem `->loadTable()` o HTML testado
  é o do esqueleto (`.ai/rules/testes.md`).

---

## Regra R1 — O recurso se chama "Papel"/"Papéis"

> `RQ-01`, `RQ-02` · perfil **mínimo** · técnica: **EP** (uma partição: o termo escolhido)

```gherkin
# language: pt

Funcionalidade: Nomenclatura da tela de papéis

  Regra: O recurso de papéis se apresenta como "Papel"/"Papéis", nunca como "Funções"

    Cenário: [CT-01] a navegação e os rótulos do recurso usam o termo escolhido
      Dado o painel /admin com o plugin de papéis registrado
      Quando o administrador abre a listagem de papéis
      Então o rótulo de navegação do recurso é "Papéis"
      E o rótulo singular do recurso é "Papel"
      E a palavra "Funções" não aparece na página
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `navigationLabel()` configurado e `modelLabel()`/`pluralModelLabel()` esquecidos — a navegação diz "Papéis" e o título da página diz "Funções" | CT-01 (as três asserções) |
| M2 | traduzir a chave do Shield em `lang/vendor/filament-shield/pt_BR/` em vez de configurar o plugin — funciona, mas o `RoleResource::getNavigationLabel()` continua lendo a tradução e um `vendor:publish` sobrescreve | CT-01 (passa nos dois; **é o mutante que não se mata por teste** — vale ADR, não cenário. Registrado como escolha, não como defeito) |
| M3 | rótulo escrito direto no Resource (`protected static ?string $navigationLabel`) em vez do plugin, deixando o plugin devolver "Funções" para quem consultar por ele | CT-01 (a asserção lê `RoleResource::getNavigationLabel()`, que delega ao plugin) |

**Camada**: `Kit`, HTTP + leitura estática da classe. Não é `Unit` porque a resolução do rótulo passa
pelo plugin registrado no painel, e o `tests/Pest.php` só liga o `TestCase` da aplicação fora de
`Unit`.

---

## Regra R2 — O título do registro é o rótulo, não a chave

> `RQ-03`, `RQ-06` · perfil **mínimo**, técnica escalada para **normalização** · o breadcrumb da
> tela de edição é o consumidor visível

```gherkin
# language: pt

Funcionalidade: Nomenclatura da tela de papéis

  Regra: O título de um papel é o rótulo legível, e é ele que aparece no breadcrumb

    Esquema do Cenário: [CT-02] o título do papel é o rótulo, nunca a chave
      Dado um papel de nome "<chave>"
      Quando o administrador abre a tela de alteração desse papel
      Então o breadcrumb mostra "<rotulo>"
      E o breadcrumb não mostra "<chave>"

      Exemplos:
        | chave             | rotulo               | # partição                                |
        | panel_user        | Painel App           | chave com rótulo próprio                  |
        | master_global     | Administrador Geral  | chave com rótulo próprio                  |
        | gerente_de_contas | Gerente De Contas    | chave sem rótulo próprio, derivada        |
        | admin             | Admin                | chave de uma palavra (headline é idempotente) |

    Cenário: [CT-03] sem registro, o título cai no rótulo singular do recurso
      Dado que nenhum papel foi informado
      Quando o título do registro é pedido
      Então o resultado é "Papel"
```

`admin` está no `Exemplos` de propósito: é o caso em que `Str::headline()` devolve algo igual ao
início da chave, e um mutante que "passe a chave adiante" acerta essa linha. Ela existe para que
nenhuma **outra** linha da tabela seja tomada como suficiente — as três primeiras são as que
discriminam.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `getRecordTitle()` não sobrescrito — o default devolve `$record->name` cru | CT-02, linhas `panel_user` e `master_global` |
| M2 | `ucfirst($record->name)` em vez de `Papeis::rotulo()` — resolve `admin` e não resolve `panel_user` | CT-02, linha `panel_user` |
| M3 | `Str::headline()` direto, sem passar pelo mapa `ROTULOS` — devolve "Panel User" | CT-02, linha `panel_user` (rótulo esperado "Painel App") |
| M4 | `getRecordTitle()` estoura com `$record` nulo (busca global chama com nulo) | CT-03 |

**Camada**: CT-02 em `Kit` por HTTP (`GET /admin/shield/roles/{uuid}/edit` + `assertSee`/`assertDontSee`),
porque o breadcrumb é o que o requisito nomeia. CT-03 em `Kit`, chamada estática direta.

---

## Regra R3 — Todo ponto de UI que exibe papel exibe o rótulo

> `RQ-06` · perfil **mínimo** · técnica: **EP exaustiva sobre os pontos** — o eixo de cobertura são
> os pontos de UI, não os valores

O requisito diz "sempre". A varredura encontrou quatro pontos que exibem a chave crua além do
título do registro (que é R2). Cada um é uma partição, porque cada um é um caminho de código
diferente — coluna de tabela, `mapWithKeys` de `Select`, `implode` de badge de widget e rótulo de
item de breakdown.

```gherkin
# language: pt

Funcionalidade: Exibição de papel em toda a interface

  Regra: Nenhum ponto da interface exibe a chave do papel

    Cenário: [CT-09] os widgets do dashboard /admin exibem o rótulo do papel
      Dado um usuário com o papel "panel_user"
      Quando o administrador abre o painel de controle do /admin
      Então o widget de últimos usuários mostra "Painel App"
      E o widget de usuários por papel mostra "Painel App"
      E nenhum dos dois mostra "panel_user"

    Cenário: [CT-10] os seletores de papel exibem o rótulo do papel
      Dado uma organização com um usuário vinculado
      Quando o administrador abre o seletor de papéis daquela organização
      Então as opções mostram "Painel App"
      E as opções não mostram "panel_user"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | corrigir só a coluna de tabela e esquecer os widgets — é o padrão de erro: os widgets não parecem "tela de papel" | CT-09 |
| M2 | corrigir os widgets e esquecer o `pluck('name','id')` do seletor da organização, que é o único que passa por `mapWithKeys` | CT-10 |
| M3 | usar `Str::headline()` nos widgets em vez de `Papeis::rotulo()` — "Panel User" em vez de "Painel App" | CT-09 e CT-10 afirmam o rótulo **esperado**, não só "diferente da chave" |

**Camada**: `Kit`. CT-09 por HTTP em `GET /admin` (os dois widgets renderizam no dashboard).
CT-10 por componente Livewire no `UsersRelationManager` — precisa de organização, então vai para
`tests/Tenancy`.

⚠️ O quinto ponto (`Select` de papéis do `/app`, `App/Resources/Users/UserResource.php`) tem
cobertura por **inspeção estática**, não por cenário: o `getOptionLabelFromRecordUsing()` só é
avaliado quando o Select renderiza opções, e o Resource se esconde sem tenancy. Fica como
**lacuna declarada** — tentado: componente `Livewire::test(CreateUser::class)` no `/app` dentro de
`tests/Tenancy`; o rótulo da opção não aparece no HTML inicial porque o Select é `->searchable()`
e carrega opções por requisição separada.

---

## Regra R4 — A listagem conta pessoas distintas

> `RQ-04` · perfil **padrão** · técnica: **BVA 2-valores** na contagem (0 e 1) + **EP** na
> multiplicidade de organização

```gherkin
# language: pt

Funcionalidade: Quantidade de usuários por papel

  Regra: A coluna de usuários conta pessoas distintas, não vínculos

    Esquema do Cenário: [CT-04] a listagem mostra a quantidade de usuários do papel
      Dado um papel "suporte" com <usuarios> usuários vinculados
      Quando o administrador abre a listagem de papéis
      Então a linha de "Suporte" mostra a quantidade <esperado>

      Exemplos:
        | usuarios | esperado | # borda                     |
        | 0        | 0        | borda inferior              |
        | 1        | 1        | borda+1                     |
        | 3        | 3        | dentro                      |

    Cenário: [CT-05] a coluna é ordenável pela quantidade
      Dado um papel "suporte" com 3 usuários e um papel "auditor" com 1 usuário
      Quando o administrador ordena a listagem pela coluna de usuários, decrescente
      Então "Suporte" aparece antes de "Auditor"

    Cenário: [CT-06] a mesma pessoa em duas organizações conta uma vez
      Dado uma pessoa com o papel "panel_user" na organização Alfa
      E a mesma pessoa com o papel "panel_user" na organização Beta
      Quando o administrador abre a listagem de papéis
      Então a linha de "Painel App" mostra a quantidade 1
```

CT-05 existe porque a coluna nova é `->sortable()` e ordenar por coluna agregada é o caminho em que
um `withCount` mal formado falha com erro de SQL em vez de número errado — e o `Então` usa dois
papéis com contagens **diferentes e não empatadas**, para que a ordenação seja observável.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `->counts('users')` puro — `count(*)` sobre a pivot | CT-06 (mostraria 2) |
| M2 | contar `permissions` no lugar de `users` (copiar a coluna vizinha e esquecer de trocar a relação) | CT-04, linha `0` (papel sem usuário mas com permissões mostraria número > 0) |
| M3 | `->state(fn ($r) => $r->users()->count())` — número certo, N+1 na listagem | ⚠️ nenhum cenário funcional mata; é decisão de desenho (ADR-04), auditada pelo quality gate, não por CT |
| M4 | coluna sem `->sortable()`, ou `sortable()` sobre um alias que o SQL não conhece | CT-05 |
| M5 | papel sem usuário renderiza vazio em vez de `0` | CT-04, linha `0` (o `Então` afirma o valor `0`, não "não mostra número") |

**Camada**: `Kit` por componente (`Livewire::test(ListRoles::class)->loadTable()`), exceto CT-06 que
precisa de duas organizações e vai para `tests/Tenancy`.

---

## Regra R5 — O slide-over lista os usuários, autorizado e auditado

> `RQ-05` · perfil **padrão** · técnicas: **matriz papel × ação** (autorização) + **rastreio de
> efeito** (log)

Matriz papel × ação, com a **persona separada** do dono da tela — percorrer tudo com
`master_global` deixaria a barreira sem cenário:

| Persona | listar papéis | abrir o slide-over |
|---|---|---|
| `master_global` | permitido — CT-B02 | permitido — CT-B02 |
| papel com só `ViewAny:Role` | permitido | **recusado — CT-07** |

```gherkin
# language: pt

Funcionalidade: Usuários de um papel

  Regra: Só quem pode ver o papel vê quem o tem, e a consulta deixa rastro

    Cenário: [CT-07] quem só pode listar papéis não abre a lista de usuários
      Dado um operador cujo papel tem apenas a permissão de listar papéis
      Quando ele abre a listagem de papéis
      Então a ação de ver os usuários do papel não está disponível

    Cenário: [CT-08] @além-do-requisito a consulta da lista de usuários é registrada
      Dado um papel "suporte" com 1 usuário vinculado
      Quando o administrador abre a lista de usuários desse papel
      Então o canal de autenticação registra uma entrada de nível info
      E a entrada identifica o papel consultado e quem consultou
```

CT-08 está marcado `@além-do-requisito`: nenhuma cláusula pede log. Ele existe porque a ação nova
expõe e-mail de terceiro, e a rule do projeto trata ausência de rastro nesse caso como achado.
Se o usuário recusar o log, CT-08 sai e ADR-07 é revisada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | action sem `->authorize()` — o default do Filament é liberado | CT-07 |
| M2 | `->visible()` em vez de `->authorize()` — esconde o botão e a chamada Livewire continua atendendo | CT-07 (o `Então` é "a ação não está disponível", verificado por `assertActionHidden`/`assertActionDoesNotExist`, que reprova a chamada e não só o botão) |
| M3 | `->authorize('viewAny')` em vez de `'view'` — não separa nada, porque quem chegou à listagem já tem `viewAny` | CT-07 |
| M4 | lista vinda de `$papel->users` sem dedupe — a mesma pessoa duas vezes sob tenancy | CT-B02, e o CT-06 de R4 no mesmo mecanismo |
| M5 | log removido, ou emitido no channel default em vez de `autenticacao` | CT-08 (o `Então` nomeia o canal) |
| M5b | log escrito em `->action()`. **Mutante real, e a primeira implementação caiu nele**: com `->modalSubmitAction(false)` nada dispara `callMountedAction`, então `->action()` é código morto e a trilha de auditoria nunca acontece na tela | CT-08 **só depois** de trocar `callAction` por `mountAction`. Com `callAction` ele era verde falso — está na Reconciliação abaixo |
| M6 | modal com botão de gravar — o slide-over é leitura, e um `submit` ali salvaria o formulário vazio sobre o papel | CT-B02 (afirma que não há botão de gravação) |

**Camada**: CT-07 e CT-08 em `Kit` por componente (`Livewire::test(ListRoles::class)`), CT-B02 em
`Browser` porque abrir o slide-over é JavaScript.

---

## Regra R6 — Cada grupo mostra `selecionadas/total`

> `RQ-07` · perfil **padrão** · técnica: **BVA 2-valores** (0 marcada, 1 marcada) · `@premissa` no
> formato do texto

```gherkin
# language: pt

Funcionalidade: Vínculo de permissões de um papel

  Regra: Cada grupo de permissões exibe quantas daquele grupo o papel já tem selecionadas

    Cenário: [CT-15] @premissa papel novo mostra zero selecionadas em todos os grupos
      Dado a tela de criação de papel
      Quando o administrador a abre
      Então o grupo de cada painel mostra "0/" seguido do total daquele painel

    Cenário: [CT-16] @premissa marcar uma permissão aumenta a contagem daquele grupo
      Dado a tela de criação de papel com nenhuma permissão marcada
      Quando o administrador marca uma permissão do grupo de um Resource
      Então aquele grupo passa a mostrar 1 selecionada
      E o grupo de outro Resource continua mostrando 0 selecionadas
```

A segunda asserção de CT-16 é o que separa "conta o grupo" de "conta o formulário": um contador
que some tudo mostraria 1 nos dois grupos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | contar do banco (`$record->permissions`) — o número não se move ao marcar | CT-16 |
| M2 | somar o formulário inteiro em vez do grupo | CT-16, segunda asserção |
| M3 | ler o state por outra chave que não o FQCN do Resource — o contador fica sempre em `0` | CT-16 |
| M4 | mostrar só o total, sem as selecionadas (o badge que o vendor já tem) | CT-15 (afirma `0/`, não apenas o total) |
| M5 | `count()` sobre `null` quando o grupo nunca foi tocado → erro de tipo na renderização | CT-15 (a tela de criação nasce com todos os grupos nulos) |

**Camada**: `Kit`, componente (`Livewire::test(CreateRole::class)` + `fillForm` + asserção sobre o
HTML renderizado). **Não é browser**: o `CheckboxList` do Shield já é `->live()`, então a
re-renderização acontece no servidor e o texto do badge está no HTML que o teste de componente vê.

---

## Regra R7 — Um tab vertical por painel

> `RQ-10` · perfil **padrão** · técnica: **partição exaustiva** do conjunto de painéis registrados

```gherkin
# language: pt

Funcionalidade: Vínculo de permissões de um papel

  Regra: No tab de recursos, cada painel registrado tem um tab próprio

    Cenário: [CT-17] os três painéis do kit aparecem como grupo
      Dado a tela de criação de papel
      Quando o administrador a abre
      Então há um grupo para o painel /admin
      E há um grupo para o painel /app
      E há um grupo para o painel /infra
```

Os três painéis, e não uma amostra: o conjunto de painéis é o eixo de partição e ele é pequeno e
fechado. Este cenário já existe hoje em `tests/Kit/PaineisTest.php` (*"agrupa as permissões por
painel na tela de papéis"*) — é **reaproveitado**, não duplicado; o que muda é a forma do
agrupamento, que CT-B01 prova.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `FilamentShield::getResources()` como fonte — devolve só o painel corrente, sairia um grupo só | CT-17 |
| M2 | `Tabs` interno com a mesma chave do `Tabs` do vendor — o estado de aba ativa colide e o tab de painel não troca | CT-B01 |
| M3 | `->vertical()` esquecido — os painéis viram fita horizontal | ⚠️ **sem matador por CT**: `assertSee` não distingue orientação e nenhum `assertNoJavaScriptErrors` muda. Lacuna declarada; tentado `assertAttributeContains` na classe `fi-vertical` do wrapper, recusado por acoplar o teste à classe de CSS do vendor (`.ai/rules/testes-browser.md` proíbe seletor por classe). Verificação fica no roteiro *Desenhado × Implementado* do `05`, por inspeção |

**Camada**: CT-17 em `Kit`, HTTP. CT-B01 em `Browser`.

---

## Regra R8 — A rota resolve por `uuid`, e o `id` não resolve

> `RQ-08`, `RQ-09` · perfil **completo** · técnicas: **EP** (partições do parâmetro de rota) +
> **tabela de decisão** (existe × formato)

Tabela de decisão do parâmetro `{record}`:

| # | valor | é uuid de papel existente? | resposta esperada | cenário |
|---|---|---|---|---|
| D1 | `uuid` do papel | sim | 200, tela do papel | CT-11 |
| D2 | `id` numérico do papel | não | **404** | CT-12 |
| D3 | `uuid` válido, inexistente | não | 404 | CT-13 |
| D4 | texto que não é uuid | não | 404 | CT-13 |

```gherkin
# language: pt

Funcionalidade: Endereço do papel

  Regra: O papel é endereçado pelo uuid, e o id não abre a tela

    Cenário: [CT-11] o papel semeado antes da migration recebe uuid
      Dado uma instalação com os papéis do kit já semeados
      Quando as migrações são aplicadas
      Então todo papel tem um uuid preenchido
      E dois papéis nunca têm o mesmo uuid

    Esquema do Cenário: [CT-12] a tela do papel abre pelo uuid e recusa o id
      Dado um papel "suporte"
      Quando o administrador acessa a tela de <tela> desse papel por <parametro>
      Então a resposta é <status>

      Exemplos:
        | tela        | parametro | status | # partição            |
        | alteração   | uuid      | 200    | D1                    |
        | alteração   | id        | 404    | D2 — a cláusula do RQ |
        | visualização| uuid      | 200    | D1                    |
        | visualização| id        | 404    | D2                    |

    Esquema do Cenário: [CT-13] parâmetro que não corresponde a papel algum devolve 404
      Dado nenhum papel com o identificador <parametro>
      Quando o administrador acessa a tela de alteração com <parametro>
      Então a resposta é 404

      Exemplos:
        | parametro                              | # partição |
        | 018f2c4e-0000-7000-8000-000000000000   | D3         |
        | nao-e-uuid                             | D4         |

    Cenário: [CT-14] o papel criado depois da migration nasce com uuid
      Dado a tela de criação de papel
      Quando o administrador grava um papel novo
      Então o papel gravado tem uuid preenchido
```

CT-14 é a metade de **criação** da regra (a skill: *criação ≠ edição ≠ uso*). CT-11 é a de **dado
que já existia**; CT-12 e CT-13 são as de **uso**. A metade de **edição** — salvar o papel sem
mexer no uuid não o troca — está em CT-14 por extensão? Não: é cenário próprio, e é o que M5 mata.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `uuid` acrescentado ao schema e a trait `TemUuid` esquecida no model — a rota continua resolvendo por `id` | CT-12, linha `id` (esperaria 404 e receberia 200) |
| M2 | trait aplicada e migration sem backfill — os cinco papéis semeados ficam com `uuid` nulo e nenhuma tela de papel existente abre | CT-11 |
| M3 | `HasUuids` puro em vez de `TemUuid` — `uniqueIds()` devolve `['id']`, a PK vira string e as FKs de `model_has_roles` quebram | CT-11 (uuid preenchido na coluna `uuid`) + toda a suíte de papéis |
| M4 | `uuid` regravado a cada `save()` — a URL de um papel muda quando alguém o edita | ⚠️ **sem matador** entre CT-11..CT-14. Cenário a escrever: **CT-14b** — *editar o papel não troca o uuid*. Escrito abaixo |
| M5 | `unique` esquecido — dois papéis com o mesmo uuid e a rota resolvendo o errado | CT-11, segunda asserção |
| M6 | `getRouteKeyName()` sobrescrito na `Role` e não na resolução do Filament (route binding do Livewire) — HTTP funciona e o componente `EditRole` não | CT-12 é HTTP; **CT-14** usa componente, cobrindo o outro caminho |

```gherkin
    Cenário: [CT-14b] editar o papel não troca o endereço dele
      Dado um papel "suporte" com um uuid conhecido
      Quando o administrador altera o nome desse papel
      Então o uuid do papel continua o mesmo
```

**Camada**: CT-11 em `Kit` (consulta ao banco depois das migrações da suíte), CT-12/CT-13 em `Kit`
por HTTP, CT-14 e CT-14b em `Kit` por componente.

---

## Regra R9 — Guard é seleção fechada

> `RQ-11` · perfil **padrão** · técnicas: **EP** (dentro/fora da lista) + **tabela de decisão**
> (guard × gravação de permissão)

```gherkin
# language: pt

Funcionalidade: Guard do papel

  Regra: O guard do papel só pode ser um dos guards configurados na aplicação

    Cenário: [CT-18] as opções de guard vêm da configuração da aplicação
      Dado que a aplicação tem os guards configurados em auth.guards
      Quando o administrador abre a tela de criação de papel
      Então as opções do campo de guard são exatamente as chaves de auth.guards

    Cenário: [CT-19] guard fora da lista é recusado
      Dado a tela de criação de papel
      Quando o administrador tenta gravar um papel com o guard "api-inventado"
      Então a gravação é recusada com erro no campo de guard
      E nenhum papel com esse nome é criado

    Cenário: [CT-20] guard vazio é recusado
      Dado a tela de criação de papel
      Quando o administrador tenta gravar um papel sem informar o guard
      Então a gravação é recusada com erro no campo de guard
      E nenhum papel com esse nome é criado
```

CT-19 e CT-20 afirmam o **não-efeito** (nenhum papel criado), porque uma implementação que valide
depois de gravar passaria só com "recusado".

CT-18 lê a lista **da configuração efetiva**, não de um literal `['web']`: o `Então` compara com
`array_keys(config('auth.guards'))`. Um cenário com `'web'` escrito à mão passaria numa
implementação que devolvesse `['web']` fixo — que é exatamente o mutante M2.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | continuar `TextInput` — aceita qualquer texto | CT-19 |
| M2 | `->options(['web' => 'web'])` escrito à mão | CT-18 (compara com a config, não com o literal) |
| M3 | ler os **valores** de `config('auth.guards')` em vez das **chaves** — as opções virariam arrays de driver/provider | CT-18 |
| M4 | `Select` sem `->required()` — guard nulo chega ao `firstOrCreate` da permission e cria permission com guard nulo, que `checkPermissionTo()` nunca acha | CT-20 |
| M5 | `->options()` certo e **validação só no cliente**. ⚠️ **Este mutante não existe**: verificado em `Select::getInValidationRuleValues()` (`vendor/filament/forms/src/Components/Select.php:1787-1811`), que devolve `[]` quando o state não casa com nenhuma opção, e em `CanBeValidated::getInValidationRule()` (`:808-815`), que o transforma em `Rule::in([])`. O Select valida no servidor **sozinho**. Registrado porque a primeira versão da implementação acrescentou um `->in()` redundante em cima disso, com um comentário afirmando o contrário — e o comentário errado é o defeito, não a linha | CT-19 (grava pelo componente, que é o caminho real, e passa com **e** sem o `->in()`) |
| M6 | fixar as opções num literal (`['web' => 'web']`) em vez de ler a config — passa hoje e recusa o guard que um projeto acrescentar | CT-18 (a opção `api` é injetada na config em tempo de teste e tem de ser **aceita** na gravação) |

**Camada**: `Kit`, componente (`Livewire::test(CreateRole::class)`).

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: papel não pertence a um usuário; não há recurso "de outra pessoa" a pedir. A autorização em jogo é vertical (permissão), coberta por CT-07 |
| Autorização exercida na ação (não só `can()`) | CT-07 — a asserção é sobre a **ação indisponível** no componente, não sobre `Gate::allows()` |
| Idempotência (ancorada no agregado) | **não se aplica**: nenhuma operação nova de escrita. As duas escritas existentes (criar/editar papel) já têm CT em `tests/Kit/PaineisTest.php`, e a única escrita que esta entrega toca é o guard (CT-19/CT-20). O uuid é escrito uma vez, no `creating`, e CT-14b prova que a edição não o reescreve |
| Concorrência | **não se aplica**: nenhum contador, saldo ou limite de uso |
| **Fronteira no ponto de entrada** (gravação) | CT-19, CT-20 (guard); CT-14 (uuid na criação) |
| **Domínio condicionado** (um campo depende de outro) | **não se aplica**: `guard_name`, `painel` e `name` são independentes entre si. O `team_id` depende de tenancy, e essa dependência é anterior a esta entrega |
| **Estado × operação de escrita** | **não se aplica**: papel não tem coluna de estado nem `SoftDeletes` (`RevivePlugin` não lista `Role` — a exclusão dele tem consequência de autorização e é definitiva por decisão anterior) |
| Ausente ≠ null ≠ vazio | CT-20 (guard vazio). Para `painel`, nulo é valor legítimo e já coberto por `tests/Kit/PaineisTest.php` |
| Paginação / ordenação | CT-05 (ordenação pela coluna nova). Paginação: **não se aplica** — a listagem de papéis não ganhou nada que mude a paginação, e o slide-over lista tudo por decisão declarada (`ponytail:` no passo 3 do PRD) |
| Timezone / DST | **não se aplica**: nenhuma cláusula do requisito depende de tempo |
| Unicode / limite de varchar | **não se aplica** a esta entrega: `name` e `guard_name` não mudaram de tipo nem de validação de tamanho. O rótulo derivado (`Str::headline`) não é gravado |
| Unicidade + soft delete | **não se aplica**: `Role` não usa `SoftDeletes`. A unicidade do `uuid` é coberta por CT-11 |
| CRUD combinado (ler/editar/excluir inexistente) | CT-13 (alteração de inexistente → 404) |
| Mass assignment | **lacuna declarada**: `Role` do spatie usa `$guarded = []`, então `uuid` **é** mass-assignable, contra o item 3 do checklist de `TemUuid`. Tentado: declarar `$fillable` na `App\Models\Role` — recusado porque quebraria `Role::create()` do spatie e dos dois seeders, que passam chaves variáveis. O risco real é baixo (nenhum formulário do kit tem campo `uuid`, e `CreateRole::mutateFormDataBeforeCreate()` faz `Arr::only()` — `CreateRole.php:34-37`), e está registrado em ADR-03 |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: nenhum valor monetário |
| Efeito colateral (log) — aconteceu | CT-08 |
| Efeito colateral (log) — canal correto | CT-08 (o `Então` nomeia o canal `autenticacao`) |
| Efeito colateral (log) — **não** aconteceu quando não devia | **lacuna declarada**: exigiria a persona sem `View:Role` chamando a action, e nesse caminho a action nem existe (CT-07). Tentado: `callAction()` na persona recusada — o Filament lança antes de chegar ao `->action()`, então o cenário provaria a autorização de novo e não o não-efeito |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | rótulos do recurso usam o termo escolhido | R1 | EP | Kit (HTTP + estático) | `tests/Kit/TelaDePapeisTest.php` | M1, M3 |
| CT-02 | o título do papel é o rótulo, nunca a chave | R2 | normalização | Kit (HTTP) | `tests/Kit/TelaDePapeisTest.php` | R2/M1, M2, M3 |
| CT-03 | sem registro, cai no rótulo singular | R2 | EP | Kit | `tests/Kit/TelaDePapeisTest.php` | R2/M4 |
| CT-04 | quantidade de usuários do papel | R4 | BVA 2-valores | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R4/M2, M5 |
| CT-05 | a coluna é ordenável | R4 | — | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R4/M4 |
| CT-06 | a mesma pessoa em duas organizações conta uma vez | R4 | EP | Tenancy (componente) | `tests/Tenancy/TelaDePapeisTenancyTest.php` | R4/M1, R5/M4 |
| CT-07 | quem só lista papéis não abre a lista de usuários | R5 | matriz papel×ação | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R5/M1, M2, M3 |
| CT-08 | a consulta é registrada no canal de autenticação | R5 | rastreio de efeito | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R5/M5 |
| CT-09 | os widgets do /admin exibem o rótulo | R3 | EP por ponto | Kit (HTTP) | `tests/Kit/ExibicaoDePapeisTest.php` | R3/M1, M3 |
| CT-10 | os seletores de papel exibem o rótulo | R3 | EP por ponto | Tenancy (componente) | `tests/Tenancy/TelaDePapeisTenancyTest.php` | R3/M2, M3 |
| CT-11 | papel semeado recebe uuid, e ele é único | R8 | EP | Kit | `tests/Kit/UuidDoPapelTest.php` | R8/M2, M3, M5 |
| CT-12 | a tela abre pelo uuid e recusa o id | R8 | tabela de decisão | Kit (HTTP) | `tests/Kit/UuidDoPapelTest.php` | R8/M1, M6 |
| CT-13 | parâmetro inexistente devolve 404 | R8 | tabela de decisão | Kit (HTTP) | `tests/Kit/UuidDoPapelTest.php` | — (partições D3/D4) |
| CT-14 | papel novo nasce com uuid | R8 | EP (criação) | Kit (componente) | `tests/Kit/UuidDoPapelTest.php` | R8/M6 |
| CT-14b | editar o papel não troca o uuid | R8 | EP (edição) | Kit (componente) | `tests/Kit/UuidDoPapelTest.php` | R8/M4 |
| CT-15 | papel novo mostra zero selecionadas | R6 | BVA | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R6/M4, M5 |
| CT-16 | marcar permissão aumenta a contagem do grupo | R6 | BVA | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R6/M1, M2, M3 |
| CT-17 | os três painéis aparecem como grupo | R7 | partição exaustiva | Kit (HTTP) | `tests/Kit/PaineisTest.php` (existente) | R7/M1 |
| CT-18 | as opções de guard vêm da configuração | R9 | EP | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R9/M2, M3 |
| CT-19 | guard fora da lista é recusado | R9 | EP inválida | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R9/M1, M5 |
| CT-20 | guard vazio é recusado | R9 | EP inválida | Kit (componente) | `tests/Kit/TelaDePapeisTest.php` | R9/M4 |

CT-B01 e CT-B02 em `05-casos-de-teste-browser.md`.

---

## Reconciliação pós-implementação

> Escrita **depois** de implementar e rodar. O que está acima foi derivado do requisito, sem olhar
> implementação; esta seção registra o que a execução acrescentou, e por quê. Nada aqui é
> refinamento de redação — são lacunas que só apareceram quando o código existiu.

### Cenários novos

| ID | Cenário | Por que não existia | Regra | Arquivo |
|---|---|---|---|---|
| CT-07b | com `View:Role`, a ação de ver usuários **aparece** | o `04` só tinha a metade negativa. "Escondida" e "inexistente" são indistinguíveis por uma asserção de ausência sozinha: CT-07 passaria com a action removida da tela | R5 | `tests/Kit/TelaDePapeisTest.php` |
| CT-21 | papel sem usuário abre o slide-over no estado vazio | achado no roteiro *Desenhado × Implementado* do `05`, linha 3. O `EmptyState` e o `RepeatableEntry` têm `->visible()` **complementares**, e um erro de sinal deixa os dois visíveis ou nenhum | R5 | `tests/Kit/TelaDePapeisTest.php` |
| CT-22 | papel com usuário abre o slide-over com a tabela | a contraprova de CT-21. Sem ela, o par de `->visible()` passa com os dois sinais invertidos | R5 | `tests/Kit/TelaDePapeisTest.php` |

Os três estouram o teto do perfil `padrão` (3 por regra) em R5, que fica com 5. Justificativa: o
[gate do passo 6 vence o teto](../../../../.claude/skills/feature-test-design/SKILL.md) — os três
matam mutante que nenhum outro cenário mata, e dois deles são a metade positiva de uma asserção de
ausência, que é a forma mais comum de cobertura ilusória.

### Oráculos que mudaram de camada ou de forma

| CT | Oráculo do `04` | Oráculo real | Motivo |
|---|---|---|---|
| CT-10 | `assertSee` do rótulo no HTML da action | `assertSchemaComponentExists('roles', 'mountedActionSchema0', …)` sobre `getOptions()` | o `Select` é `->searchable()` e o Filament não imprime as opções no HTML inicial. Um `assertSee` ficaria **vermelho com a tela certa**, e um `assertDontSee` da chave ficaria **verde com a tela errada** |
| CT-21, CT-22 | `assertSee` do texto do estado vazio | visibilidade dos dois componentes, por `assertSchemaComponentExists` | o Filament não renderiza o conteúdo do modal no HTML do componente pai; ele vai por partial separado |
| CT-02 | `Role::create(...)` no arranjo | `Role::query()->firstOrCreate(...)` | três dos quatro papéis do dataset já vêm do `PapeisSeeder`, e o `create` do spatie lança `RoleAlreadyExists` |
| CT-B01 | `assertSee('Exception')` para o `/infra` | `assertSee('Audit')->assertDontSee('Tenant')` | `Exception` tem Resource nos **três** painéis — não discrimina. Conferido em `Paineis::resources()` |
| CT-09 | `GET /admin` + `assertSee`/`assertDontSee` | `Livewire::test()` nos dois widgets | widget de painel é **lazy** por default (`vendor/filament/support/src/Concerns/CanBeLazy.php:9`): `GET /admin` devolve placeholder, não o conteúdo. O caso falhou na asserção POSITIVA — com só o `assertDontSee` da chave ele passaria verde medindo uma página sem widget nenhum |
| CT-08 | `callAction(...)` | `mountAction(...)` | `callAction` faz montar **e** executar. O slide-over não tem submit, então a tela só monta — e um log escrito em `->action()` ficava verde com `callAction` e nunca acontecia de verdade. `mountAction` reproduz o clique |

### Um caso a mais em R8, e ele não veio do requisito

`it('mantem a chave primaria do papel numerica')` em `tests/Kit/UuidDoPapelTest.php`. Nenhuma
cláusula pede que a PK continue `int`; o caso existe porque trocar `TemUuid` por um `HasUuids` puro
faz `uniqueIds()` devolver `['id']`, a PK virar string e as foreign keys de `model_has_roles` e
`role_has_permissions` quebrarem. Sem ele, essa troca falharia longe da causa, com mensagem de
banco.

### O defeito que o conjunto pegou, e o cenário que o pegou

`database/seeders/PapeisSeeder.php` escrevia papel por `Spatie\Permission\Models\Role` — a classe
sem `TemUuid`. Todo papel semeado nasceria com `uuid` nulo, a rota não resolveria, a tela de
alteração responderia 404 e o `EditAction` da listagem geraria URL sem parâmetro. **Sem erro no
seeder.**

Quem pegou foi um caso de **regressão** (`tests/Kit/PaineisTest.php`, "salva o painel do papel na
edicao…") e, logo depois, **CT-11**, que é o guarda permanente: ele reprova qualquer caminho futuro
que crie papel sem `uuid`, seja qual for a classe. CT-11 nasceu de uma frase sobre o requisito
("nunca id na url" ⇒ "papel que já existia recebe uuid"), não sobre o plano — um caso derivado do
PRD teria testado a migration, que estava certa.

### Índice de cenários — arquivos reais

Todos os CT do índice acima estão implementados nos arquivos que ele nomeia. Contagem medida:

| Arquivo | Casos | Asserções |
|---|---|---|
| `tests/Kit/TelaDePapeisTest.php` | 21 (o dataset de CT-02 conta 4, o de CT-04 conta 3) | 64 |
| `tests/Kit/UuidDoPapelTest.php` | 10 (datasets de CT-12 e CT-13 contam 4 e 2) | 22 |
| `tests/Tenancy/TelaDePapeisTenancyTest.php` | 2 | 7 |
| `tests/Browser/PapeisTest.php` | 2 | 10 |
| `tests/Kit/ExibicaoDePapeisTest.php` | +1 (CT-09, no arquivo existente) | — |

### Dois fatos de vendor que a derivação afirmou errado

A `.ai/rules/specs.md` do projeto é dura nisto: afirmação sobre comportamento de pacote exige
`file:line`, e escrever a explicação a partir do que se espera encontrar já produziu três erros numa
única feature. Aconteceu duas vezes aqui, e as duas foram pegas pela auditoria do diff, não pela
derivação:

1. **"`Select` do Filament não valida `in:` sozinho"** — falso.
   `Select::getInValidationRuleValues()` (`vendor/filament/forms/src/Components/Select.php:1787-1811`)
   devolve `[]` quando o state não casa com nenhuma opção, e `CanBeValidated::getInValidationRule()`
   (`:808-815`) o transforma em `Rule::in([])`. A afirmação sustentava uma linha de código
   (`->in()`) que **sobrescrevia a validação nativa por uma pior**. A conclusão ("o guard precisa de
   trava de servidor") estava certa por outro motivo — que é o que torna esse erro invisível.

2. **"o log em `->action()` registra quem consultou a lista"** — falso na prática.
   `->modalSubmitAction(false)` remove o único gatilho de `callMountedAction`, então `->action()`
   nunca roda pela tela. CT-08 passava porque usava `callAction()`, que força o caminho que a
   interface não tem. É o arquétipo do teste que mede o arnês em vez do sistema.
