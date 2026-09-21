# Casos de Teste — Link de acesso ao painel da organização

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito** (RQ-01..RQ-05 e as decisões do `## Ambiguidades`), não do plano.
> Nenhum cenário foi escrito olhando implementação da feature — ela não existe. Os arquivos de
> `app/Filament/Admin/Resources/Tenants/**` foram lidos apenas para herdar convenção e para
> localizar o literal que o CT-02 tem de **excluir** (ver `## Fronteira com o Plano`).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| **A** — geração da URL e o slug que a compõe | 2 | 3 | 6 | padrão |
| **B** — presença/ausência do link nas superfícies | 1 | 2 | 2 | mínimo |
| **C** — os dois portões e a consequência do clique | 2 | 3 | 6 | padrão |
| **D** — nova aba | 1 | 1 | 1 | mínimo |
| **E** — gravação e estado do formulário de edição | 2 | 2 | 4 | padrão |
| **F** — custo da listagem | 2 | 2 | 4 | padrão |
| **G** — tenancy desligada | 1 | 2 | 2 | mínimo |

**Impacto 3 nas áreas A e C** → [revisão adversarial obrigatória](#revisão-adversarial), mesmo com
P×I ≤ 6. Justificativa do I=3:

- **A**: o `slug` é escrito por humano e **entra numa URL** renderizada no painel de
  administração. Sem a restrição de caractere, o `href` de uma tela de administração passa a ser
  escolhido por quem cadastra a organização. É superfície de segurança, não cosmética.
- **C**: o link atravessa dois portões de autorização (`canAccessPanel`, `canAccessTenant`). O
  requisito decidiu **não** guardá-lo — então o que resta a travar é que ele continue sem
  **conceder** nada.

- Técnicas aplicadas: EP, BVA 3-valores (comprimento do slug), tabela estado × operação,
  matriz persona × portão, rastreio de efeito (log, pivot), contagem de queries, varredura de
  código-fonte (ausência de concatenação).
- Cenários: **20** · Regras: **9** · Mutantes previstos: **28** · Sem matador: **0** ·
  Lacunas declaradas: **2**

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | Um gerador de URL (ponto único) + três schemas já existentes (`TenantForm`, `TenantInfolist`, `TenantsTable`). **Nenhuma** migration, coluna, rota, config, evento, job ou comando novo | CT-01, CT-02 |
| **F** | Gerar o endereço; renderizar o link (presente ou ausente por tela); abrir em nova aba; **não** conceder acesso; **não** interferir na gravação; **não** custar query | CT-04, CT-06, CT-07, CT-11, CT-12, CT-14 |
| **D** | `slug` (`alphaDash`, `unique`, `maxLength(120)`), escrito por humano e consumido como segmento de URL; `ativo` (a "exclusão lógica" desta entidade — não há `SoftDeletes` nem `DeleteAction`); registro **não gravado** (state do `CreateTenant`); slug gravado por fora do formulário | CT-05, CT-13, CT-16, CT-17, CT-18 |
| **I** | Três telas do painel `/admin`; o gerador alcançável por PHP puro; e o **destino**, alcançável por `GET /app/{slug}` — que é onde os portões decidem | CT-01, CT-08, CT-19 |
| **P** | Depende de o painel `app` estar registrado **com** `->tenant()`, o que só acontece com `config('kit.tenancy.enabled')`. Por isso CT-01..CT-18 e CT-20 vivem em `tests/Tenancy` (o `TenancyTestCase` fixa `permission.teams` antes das migrations) e **CT-19 vive em `tests/Kit`**, a única suíte onde a tenancy está desligada. O `assertSeeHtml` depende do formato de `Filament\Support\generate_href_html()` (`vendor/filament/support/src/helpers.php:generate_href_html:159-162`) | CT-19 |
| **O** | Quatro personas reais: `master_global` (passa nos dois portões), **administrador da instalação** (papel `admin`, sem papel do `app`, sem vínculo — falha no portão 1), usuário com papel do `app` sem linha no pivot (falha no portão 2), `admin_app` vinculado (passa). Uso previsto: "ir ver como está lá". Uso indevido: repassar o endereço a quem não tem acesso | CT-04, CT-08, CT-09 |
| **T** | **Não se aplica** a concorrência, agendamento, expiração, DST ou timezone: a feature não grava nem compara tempo. O tempo entra por **uma** via, e ela é cenário: o `slug` muda, e o link tem de acompanhar (`helperText` já avisa que "mudar invalida os links já compartilhados") | CT-03 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a URL do link é a que o painel `app` gera para a organização, derivada do slug **gravado** | A (padrão) | RQ-01, RQ-05 | EP + varredura de fonte + invariante temporal | CT-01, CT-02, CT-03 |
| **R2** — o link está presente, com o endereço certo, nas três superfícies da organização | B (mínimo) | RQ-02, RQ-03, RQ-04 | EP por superfície + partição de `ativo` | CT-04, CT-05 |
| **R3** — nenhum link é renderizado para organização não gravada | B (mínimo) | RQ-02 + invariante do `## Ambiguidades` | EP (estado "não gravada") | CT-06, CT-20 |
| **R4** — o link abre em nova aba nas três superfícies | D (mínimo) | RQ-01 (premissa adotada no `00`, ADR-04) | EP por superfície | CT-07 |
| **R5** — o link **navega**, não autoriza: quem não passa nos portões recebe a recusa do Filament, o motivo é registrado, e nada é concedido | C (padrão) | RQ-01 + decisão (a) do `## Ambiguidades` + `## Fora de Escopo` | matriz persona × portão + rastreio de efeito + saída do erro | CT-08, CT-09, CT-10, CT-11 |
| **R6** — o link não é campo: não entra no estado do formulário nem altera a gravação do `EditTenant` | E (padrão) | RQ-02 | gate de tela de escrita + idempotência no agregado persistido | CT-12, CT-13 |
| **R7** — a URL sai do slug do **próprio registro**: a listagem não paga query por linha | F (padrão) | RQ-05 | contagem de queries invariante à cardinalidade | CT-14, CT-15 |
| **R8** — o slug que compõe a URL continua restrito a `alphaDash` e a 120 caracteres, na criação **e** na edição | A (padrão) | RQ-05 + `## Superfície Livewire` do `02` | EP (inválidas isoladas) + BVA 3-valores | CT-16, CT-17, CT-18 |
| **R9** — com a tenancy desligada a superfície do link não é alcançável | G (mínimo) | RQ-01 (pressupõe a rota `/app/{slug}`, que só existe com tenancy) — mecanismo em ADR-03 | EP (config ligada/desligada) | CT-19 |

**Técnica escalada acima do perfil da área**: R8 está em área `padrão`, e a técnica é BVA
**3-valores** (119/120/121) em vez de 2-valores. Motivo: 2-valores não distingue `maxLength(120)`
de `maxLength(121)`, e 120 é o único número literal que o requisito herda do campo.

> **E o 120 não tem rede embaixo.** A coluna é `$table->string('slug')` — 255
> (`database/migrations/0001_01_01_000020_create_tenants_table.php:29`). O teto de 120 vive
> **apenas** no `TenantForm`: se ele sair, nada estoura, nada avisa, e um slug de 200 caracteres
> passa a compor a URL. É o caso da skill em que o valor do requisito foi parametrizado por um
> lugar só — CT-17 escreve o número **literal** e é a única coisa entre o kit e essa regressão.

**Estouro de teto declarado**:
- R2 (área mínimo, teto 1) usa **2** cenários — CT-05 existe porque `ativo` é uma partição de
  dado que a listagem **exibe** e o requisito não exclui.
- R3 (área mínimo, teto 1) usa **2** cenários — CT-20 fecha a célula `não gravada × gravar` da
  matriz e o gate de tela de escrita da rota `create`.
- R5 (área padrão, teto 3) usa **4** cenários — é regra de **rastreio de efeito** (log, pivot) e
  o teto não divide com a matriz de persona (passo 7 da skill). O gate do passo 6 vence o teto.

---

## Fronteira com o Plano

Itens que vieram do `01`/`02` e foram **recusados como oráculo**, para nenhum cenário virar teste
do PRD:

| Item do PRD / ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| "um ponto único que devolve a URL" (passo 1) | escolha de implementação — nome, classe e assinatura não são observáveis do requisito | detalhe do cenário. **Nenhum CT nomeia a função**; CT-01 e CT-02 afirmam sobre a **URL** e sobre a **fonte de onde ela sai** |
| `openUrlInNewTab()` (ADR-04) | é a API que produz o comportamento, não o comportamento | CT-07 afirma o HTML (`target="_blank"`), que é o que o usuário recebe |
| "coluna, não ação" (RQ-04, decidido) | **aceito** como oráculo: a decisão está no `00`, não só no PRD | CT-04 afirma a coluna da listagem |
| "zero query nova" (`## Modelo de Execução`) | o **número** é do PRD | o oráculo usado é RQ-05 ("a URL é derivada do slug"), traduzido em **invariância à cardinalidade** (CT-14). Nenhum CT afirma "N queries" |
| "403 do Filament" (ADR-02 e `## Ambiguidades`) | **factualmente errado para o portão 2** — ver a pergunta 1 abaixo. O `IdentifyTenant` do vendor faz `abort(404)` | CT-08 afirma **403 no portão 1** e **404 no portão 2**, o que o vendor faz. A wiki precisa ser corrigida |
| "a string `/app/` não aparece no código da feature" (ADR-01, `## Riscos`) | aceito, **com exclusão obrigatória** — ver abaixo | CT-02 |

### A exclusão que o CT-02 precisa declarar, senão ele fica vermelho contra a implementação correta

`TenantForm.php:configure:35` **já contém** o literal `/app/{slug}`, hoje, antes da feature:

```php
->description('O slug vira o endereço do painel de negócio: /app/{slug}.')
```

É **prosa exibida ao usuário**, não código de URL, e **não é comentário** — então o filtro de
comentário que a `.ai/rules/testes.md` exige para asserção de ausência **não a remove**. Um CT
escrito como "a string `/app/` não aparece em `TenantForm.php`" nasce vermelho com a
implementação correta.

CT-02 é escopado ao **arquivo do gerador de URL** (o ponto único do passo 1), onde o literal não
tem nenhum motivo legítimo para existir. As três superfícies são cobertas por CT-01 e CT-04, que
afirmam que a URL renderizada é **a mesma** que o gerador devolve — uma superfície que montasse a
URL por conta própria só passaria por coincidência hoje, e o CT-03 (troca de slug) a derruba.

### Perguntas em aberto

Replicadas em `00-requisito.md` → `## Ambiguidades`. Cada uma bloqueia o que está indicado.

1. **O código de status do portão 2 é 404, não 403.** `Filament\Http\Middleware\IdentifyTenant`
   faz `abort(404)` quando `canAccessTenant()` nega
   (`vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:handle:40-42`), e o portão 1
   (`canAccessPanel`) é que faz `abort_if(..., 403)`
   (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:authenticate:35-41`). O
   `Authenticate` embrulha as rotas de tenant (`vendor/filament/filament/routes/web.php:60`), então
   o portão 1 decide primeiro. Já existe teste do kit afirmando o 404 e **explicando por que é
   melhor que 403** (`tests/Tenancy/AdminDaOrganizacaoTest.php:98-105`: "um 403 confirmaria que a
   organização EXISTE, e bastaria varrer slugs para enumerar os clientes").
   **Premissa adotada**: o `04` afirma o que o vendor faz — 403 no portão 1, 404 no portão 2.
   **Bloqueia**: nada (é correção de fato, não de decisão). **Se negado**: o `00` e a ADR-02
   precisam dizer 403 **e** 404, e CT-08 continua como está — é a wiki que muda.

2. **O link de organização inativa.** `ativo` é a exclusão lógica desta entidade, e as duas
   metades do kit discordam: `User::getTenants()` filtra `->where('ativo', true)`
   (`app/Models/User.php:getTenants:775-782`), mas `canAccessTenant()` **não olha `ativo`**
   (`app/Models/User.php:canAccessTenant:789-809`). O `00` decidiu que o link **aparece sempre**
   — e não decide o que acontece ao **seguir** o link de uma organização inativa. Mudar os portões
   está em `## Fora de Escopo`.
   **Premissa adotada** (escopo): o cenário de renderização é obrigatório e está escrito (CT-05);
   o de **seguir** o link de organização inativa é **lacuna declarada** — ver
   `## Lacunas Declaradas`. **Bloqueia**: a célula `inativa × seguir` da matriz.
   **Se negado** (o usuário decidir que inativa não abre): nasce um CT afirmando a recusa, e a
   decisão sai de `Fora de Escopo`.

3. **O slug só é barrado no formulário.** `alphaDash` e `maxLength(120)` vivem em `TenantForm`; o
   model não tem barreira, e `Tenant::create(['slug' => '../outra'])` grava. A feature passa a
   **depender** dessa validação (é o achado do `## Superfície Livewire` do `02`).
   **Premissa adotada** (mecanismo): o formulário é o único portão, e o CT escrito é o
   invariante que vale nas duas leituras — **a feature não normaliza o slug** (CT-18).
   **Bloqueia**: o gate "toda regra de validação de domínio tem ≥1 cenário fora do componente de
   UI" para R8 — ver `## Lacunas Declaradas`.
   **Se negado** (decidir que o model barra): CT-18 inverte de "grava e o link segue o gravado"
   para "não grava", e nasce um CT chamando `Tenant::create()` direto.

### Divergência entre skill e Project Rule

A skill sugere `pest --parallel --tia` como padrão. Aqui **vence `.ai/rules/testes.md`** e o
`tests/Pest.php`: os CTs desta feature rodam na suíte `Tenancy` (grupo `kit`), e nenhum helper
novo cruza arquivos — por isso o comando desta feature é
`php artisan test --compact tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php`. Não há CT-B, então a
proibição de `--parallel` com browser não se aplica.

---

## Setup Global

### Suíte e arnês

| CTs | Arquivo | Suíte | Por quê |
|---|---|---|---|
| CT-01..CT-18, CT-20 | `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` | `Tenancy` (grupo `kit`) | `TenantResource::canAccess()` exige `kit.tenancy.enabled`, e o `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, antes das migrations. O papel `admin_app` **só existe** nesta suíte |
| CT-19 | `tests/Kit/LinkDoPainelSemTenancyTest.php` | `Kit` (grupo `kit`) | é a **única** suíte onde a tenancy está desligada. Um CT de "desligada" dentro de `tests/Tenancy` mediria o arnês, não o comportamento |

> Nenhum helper novo em `tests/Pest.php`: os dois arquivos não compartilham função. Se a
> implementação obrigar um helper comum, ele vai para `tests/Pest.php` — `.ai/rules/testes.md`.

### `beforeEach`

```php
$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
```

Sem os dois seeders, `View:Tenant` / `Update:Tenant` / `ViewAny:Tenant` não existem e todo caso
morre com 403 no arranjo — o mesmo padrão de `tests/Tenancy/IdentidadeVisualTenancyTest.php:20-22`.

### Personas

| Nome no cenário | Como criar | Passa no portão 1? | Passa no portão 2? |
|---|---|---|---|
| **o administrador da instalação** | `usuarioComPapel('admin')` — contexto **global** | **não** (papel do painel `admin`) | não (sem vínculo) |
| **o mestre da instalação** | `usuarioComPapel('master_global')` | sim (`Gate::before`) | sim (`isMasterGlobal()`) |
| **a operadora do negócio sem vínculo** | `usuarioComPapel('panel_user', $acme)` **sem** `->tenants()->attach()` | sim (papel do `app` em alguma organização) | **não** (`motivo: sem_vinculo`) |
| **a administradora da organização** | `usuarioComPapel('admin_app', $acme)` + `->tenants()->attach($acme)` | sim | sim |

**Por que o contexto do papel importa, e por que os dois helpers servem para o `/admin`.** Com
`permission.teams` ligado, papel gravado fora de `Tenant::CONTEXTO_GLOBAL` (que é `0`,
`app/Models/Tenant.php:CONTEXTO_GLOBAL:66`) fica invisível no `/admin` —
`User::canAccessPanel()` compara com `contextoGlobal()` para painel sem tenancy
(`app/Models/User.php:contextoGlobal:726-729`). O `KitServiceProvider` já fixa esse contexto no
boot (`app/Providers/KitServiceProvider.php:237`), então na suíte `Tenancy` tanto
`usuarioComPapel('admin')` (explícito) quanto `usuarioCom('admin')` / `usuarioDoKit('admin')`
(implícito, pelo contexto do boot) gravam `team_id = 0` e funcionam — é o que os vizinhos
`tests/Tenancy/IdentidadeVisualTenancyTest.php:43` e
`tests/Tenancy/PermissoesDeTenantResourceTest.php:22-25` fazem. Para papel do painel `app`, ao
contrário, o contexto **tem** de ser a organização: `usuarioComPapel($papel, $acme)`. Ver
`tests/Pest.php:papelNaOrganizacao:773` e `.ai/rules/testes.md`.

O `admin` recebe a matriz inteira do painel `admin` (`database/seeders/PapeisSeeder.php:58-59`),
então `ViewAny:Tenant`, `View:Tenant` e `Update:Tenant` existem para ele — é o que permite a
persona do CT-04 abrir as três telas **sem** passar em nenhum dos dois portões do painel de
negócio. Sem os dois seeders do `beforeEach`, as três telas dão 403 no arranjo.

### Fixtures

- `Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme'])` — a organização do caminho feliz
- `tenant('Globex', 'globex', ativo: false)` — a inativa (helper de `tests/Pest.php:tenant:384`)
- O **endereço esperado** é sempre calculado no caso, nunca escrito à mão:
  `$esperado = Filament::getPanel('app')->getUrl($organizacao)`
  (`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:getUrl:170-196`). A rota do tenant é
  registrada como `{tenant:slug}` (`vendor/filament/filament/routes/web.php:130-133` e `:220-223`),
  então os dois ramos de `getUrl()` devolvem o **slug**, e não o `getRouteKey()` — que neste model é
  o `uuid` (`app/Traits/TemUuid.php:getRouteKeyName:35-38`). **Esta diferença é o oráculo do CT-01.**

### Boot do painel

| Superfície | O que o caso precisa chamar antes | Por quê |
|---|---|---|
| `ListTenants` | `noPainelBootado('admin')` **e** `->loadTable()` | a coluna de logo usa o macro `simpleLightbox()`, registrado no `boot()` do plugin; e toda tabela do kit carrega adiada (`.ai/rules/testes.md`) |
| `EditTenant`, `ViewTenant`, `CreateTenant` | `Filament::setCurrentPanel('admin')` | o componente de resource resolve o schema pelo painel corrente — sem isso o caso morre em `getDefaultTestingSchemaName() on null` |

### Fakes

Nenhum `Queue::fake` / `Mail::fake` / `Http::fake`: a feature não despacha nada. O único efeito
observável é **log**, e ele é lido com `TestHandler` trocado no channel real
(`Log::channel('tenancy')->getLogger()->setHandlers([$registros])`), como
`tests/Tenancy/PaletaDaOrganizacaoTest.php:190`.

### Estratégia de DB

`RefreshDatabase`, ligado globalmente no `tests/Pest.php` para as duas suítes.

### Assertion de HTML

Os três caminhos de render passam por `Filament\Support\generate_href_html()`
(`vendor/filament/support/src/helpers.php:generate_href_html:153-174`), que emite exatamente
`href="{url}" target="_blank"`, nessa ordem, com a URL escapada por `e()`:

| Superfície | Onde o helper é chamado |
|---|---|
| listagem | `vendor/filament/tables/resources/views/index.blade.php:2364` |
| infolist | `vendor/filament/infolists/resources/views/components/entry-wrapper.blade.php:93` |
| formulário (Action / link / botão) | `vendor/filament/support/resources/views/components/link.blade.php:84` e `.../button/index.blade.php:108` |

Por isso a asserção é `assertSeeHtml('href="'.$esperado.'" target="_blank"')` — **uma só string,
adjacente**, e ela vale para qualquer das três escolhas de componente. `assertSee('target="_blank"')`
solto é **proibido** como oráculo: o topbar e o widget de informação do próprio Filament já emitem
`_blank` em toda página (`vendor/filament/filament/resources/views/livewire/topbar.blade.php` e
`.../widgets/filament-info-widget.blade.php`).

---

## Regra R1 — a URL do link é a que o painel `app` gera para a organização, derivada do slug gravado

> RQ-01, RQ-05 · área **A**, perfil **padrão** · técnicas: **EP** (o que a URL é e o que ela não é),
> **varredura de fonte** (de onde ela sai), **invariante temporal** (ela acompanha o slug)

```gherkin
# language: pt
Funcionalidade: Link de acesso ao painel da organização

  Regra: o endereço do link é o que o painel de negócio gera para aquela organização, a partir do slug gravado

    Cenário: [CT-01] o endereço é o do painel da organização, e não o do registro nem o da raiz
      Dado uma organização gravada com o slug "acme-do-brasil"
      E uma segunda organização gravada com o slug "globex"
      Quando o gerador de endereço da feature é consultado para a primeira
      Então o endereço termina com o segmento "/acme-do-brasil"
      E o endereço não contém o uuid da organização
      E o endereço não contém o segmento "organizacoes"
      E o endereço da segunda organização é diferente do da primeira

    Cenário: [CT-02] o caminho do painel não é escrito à mão em lugar nenhum do gerador
      Dado o código-fonte do gerador de endereço da feature, sem os comentários
      Quando o texto é inspecionado
      Então ele não contém o literal "/app"
      E ele não contém nenhuma concatenação do slug com um caminho

    Cenário: [CT-03] trocar o slug move o link
      Dado uma organização gravada com o slug "acme", aberta na tela de edição
      Quando a administradora salva a organização com o slug "acme-2"
      Então o link da tela recarregada termina com o segmento "/acme-2"
      E o endereço com o segmento "/acme" não aparece mais na tela
```

**Discriminância dos valores.** `acme-do-brasil` e não `acme`: um slug de um só termo não distingue
"o endereço termina com o slug" de "o endereço termina com o nome em minúsculas". A **segunda
organização** é o que mata o gerador que devolve sempre a raiz do painel — com uma organização só,
`getUrl()` sem tenant e `getUrl($tenant)` produzem strings diferentes, mas nenhuma comparação as
separa. O `uuid` é a chave de rota real do model (`TemUuid`), então "não contém o uuid" é a
asserção que separa **slug** de **chave de rota** — e é o mutante mais plausível de todos, porque
`route($nome, ['tenant' => $model])` com binding diferente daria o uuid.

**Camada.** CT-01 é `Feature` na suíte `Tenancy` — não é `Unit`: o gerador depende do painel `app`
resolvido e da rota registrada, e `tests/Pest.php` não liga o `TestCase` da aplicação a
`tests/Unit`. CT-02 é `Feature` (varredura de arquivo, sem banco). CT-03 é componente Livewire
(`EditTenant`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M01 | `url('/app/'.$organizacao->slug)` — a URL concatenada à mão | **CT-02** (CT-01 e CT-04 não matam: hoje a concatenação produz a mesma string) |
| M02 | o gerador devolve a raiz do painel (`getUrl()` sem passar a organização) | CT-01 (as duas organizações dariam o mesmo endereço) |
| M03 | o gerador usa a chave de rota do registro (`getRouteKey()`, que aqui é o `uuid`) em vez do slug | CT-01 (o endereço conteria o uuid) |
| M04 | o link aponta para a própria tela do resource (`/admin/organizacoes/{uuid}`) em vez do painel | CT-01 (o endereço conteria "organizacoes") |
| M05 | o endereço é resolvido uma vez e memoizado, e não acompanha a troca do slug | CT-03 |

---

## Regra R2 — o link está presente, com o endereço certo, nas três superfícies da organização

> RQ-02, RQ-03, RQ-04 · área **B**, perfil **mínimo** · técnica: **EP por superfície** + partição
> de `ativo`

```gherkin
  Regra: as três telas da organização exibem o link com o endereço do painel dela

    Esquema do Cenário: [CT-04] o link aparece na tela, com o endereço da organização
      Dado uma organização ativa gravada com o slug "acme"
      E que o administrador da instalação não tem papel do painel de negócio nem vínculo com ela
      Quando ele abre <tela>
      Então o HTML contém o endereço que o painel de negócio gera para a organização, como link

      Exemplos:
        | tela                     | # superfície            |
        | a listagem de organizações | RQ-04 — coluna         |
        | a ficha da organização     | RQ-03 — visualização   |
        | a edição da organização    | RQ-02 — formulário     |

    Esquema do Cenário: [CT-05] a organização inativa também exibe o link, e é o dela
      Dado uma organização inativa gravada com o slug "globex"
      E uma organização ativa gravada com o slug "acme"
      Quando o administrador da instalação abre <tela>
      Então o HTML contém o endereço da organização inativa, como link
      E o endereço exibido para a inativa não é o da organização ativa

      Exemplos:
        | tela                       | # partição de `ativo` |
        | a listagem de organizações | inativa na listagem   |
        | a ficha da inativa         | inativa no view       |
        | a edição da inativa        | inativa no form       |
```

**A persona do CT-04 é o oráculo, não um detalhe.** O requisito decidiu a opção **(a)**: o link
aparece **sempre**. Escrever o CT-04 com o `master_global` — que passa nos dois portões — deixaria
a decisão do usuário sem um único teste: a alternativa recusada (renderizar só para quem consegue
entrar) ficaria **verde no conjunto inteiro**. Só a persona que **falha** nos portões falsifica a
decisão (M14).

**Discriminância.** CT-05 exige o endereço **da inativa**, e afirma que ele difere do da ativa: uma
implementação que resolvesse o link a partir da organização errada (a primeira da página, o tenant
corrente) passaria num cenário de uma organização só.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M06 | o link entra só no formulário, e `view`/listagem ficam sem — a leitura mais literal de "adicione tanto no form e no view" | CT-04 (a linha da superfície faltante) |
| M07 | a listagem ganha a coluna com o endereço **como texto**, sem `href` | CT-04 (linha da listagem: a asserção é sobre `href=`, não sobre o texto) |
| M08 | o link é condicionado a `ativo`, e a organização desligada fica sem endereço visível | CT-05 |
| M14 | o link ganha a guarda dos dois portões — a alternativa 1 da ADR-02, recusada pelo usuário | CT-04 (a persona não passa em nenhum dos dois portões e o link tem de estar lá) |

---

## Regra R3 — nenhum link é renderizado para organização não gravada

> RQ-02 + o invariante afirmado no `## Ambiguidades` do `00` · área **B**, perfil **mínimo** ·
> técnica: **EP** (estado "não gravada")

```gherkin
  Regra: o link só existe onde existe um slug gravado

    Cenário: [CT-06] a tela de cadastro não oferece link, nem depois de o slug ser digitado
      Dado o administrador da instalação na tela de cadastro de organização
      Quando ele preenche o nome "Acme" e o slug "acme" sem salvar
      Então o HTML não contém nenhum link para o painel de negócio
      E nenhuma organização com o slug "acme" existe no banco

    Cenário: [CT-20] o cadastro continua gravando, e o link aparece na edição do que foi gravado
      Dado o mestre da instalação na tela de cadastro de organização
      Quando ele salva uma organização com o nome "Acme" e o slug "acme"
      Então a organização "acme" está gravada e ativa
      E a tela de edição dela contém o endereço que o painel de negócio gera para ela, como link
```

**Por que o "sem salvar" é o ponto do CT-06.** O `slug` é `live(onBlur: true)` por causa do
`afterStateUpdated` do campo `nome` (`TenantForm.php:configure:44-48`), então o estado do
formulário **tem** um slug antes de qualquer gravação. Uma implementação que montasse o link a
partir do state vivo passaria num cenário que só abrisse a tela vazia. O `fillForm` sem `create`
é o que discrimina (M10).

**CT-20 é o par do gate de tela de escrita** para a rota `create`: uma entrada nova condicionada a
"o registro existe" é exatamente o tipo de condição que estoura no `CreateTenant` (registro nulo) e
derruba a gravação com a tela abrindo verde.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M09 | a entrada é acrescentada ao schema do formulário sem condição de registro existente | CT-06 |
| M10 | o link do formulário é montado a partir do state vivo do slug, e aparece no cadastro | CT-06 (o `fillForm` sem salvar) |
| M11 | a condição "o registro existe" estoura com registro nulo e derruba a gravação do cadastro | CT-20 |

---

## Regra R4 — o link abre em nova aba nas três superfícies

> RQ-01 (premissa adotada no `00`; mecanismo em ADR-04) · área **D**, perfil **mínimo** ·
> técnica: **EP por superfície**

```gherkin
  Regra: seguir o link não faz a pessoa perder a tela de administração

    Esquema do Cenário: [CT-07] o link da tela abre em nova aba
      Dado uma organização ativa gravada com o slug "acme"
      Quando o administrador da instalação abre <tela>
      Então o HTML contém o endereço do painel de negócio com o atributo de nova aba imediatamente ao lado

      Exemplos:
        | tela                       | # superfície |
        | a listagem de organizações | RQ-04        |
        | a ficha da organização     | RQ-03        |
        | a edição da organização    | RQ-02        |
```

**Discriminância — e é onde este cenário quase não prova nada.** Afirmar `target="_blank"` solto é
decorativo: o topbar do Filament já o emite em toda página do painel. O oráculo é a **adjacência**,
`href="{endereço}" target="_blank"`, que é o que `generate_href_html()` produz e **só** produz
quando a nova aba está declarada (`helpers.php:159-162`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | a nova aba é declarada em duas superfícies e esquecida na terceira | CT-07 (a linha da superfície faltante) |
| M13 | o link abre na mesma aba, e quem clicava na listagem perde a lista | CT-07 |

---

## Regra R5 — o link navega, não autoriza

> RQ-01 + decisão (a) do `## Ambiguidades` + `## Fora de Escopo` do `00` · área **C**, perfil
> **padrão** · técnicas: **matriz persona × portão**, **rastreio de efeito** (log, pivot),
> **saída do estado de erro**

```gherkin
  Regra: seguir o link não concede nada — os dois portões continuam decidindo, e o motivo fica registrado

    Esquema do Cenário: [CT-08] seguir o link devolve o que os portões decidem
      Dado uma organização ativa gravada com o slug "acme"
      E <persona>
      Quando ela segue o endereço do link da organização
      Então a resposta é <resposta>

      Exemplos:
        | persona                                                          | resposta | # portão            |
        | o mestre da instalação                                            | 200      | passa nos dois      |
        | o administrador da instalação, sem papel do painel de negócio     | 403      | barra no portão 1   |
        | a operadora do negócio, com papel do painel e sem vínculo         | 404      | barra no portão 2   |
        | a administradora da organização, vinculada a ela                  | 200      | passa nos dois      |

    Cenário: [CT-09] a negação do vínculo fica registrada, e o acesso legítimo não registra nada
      Dado uma organização ativa gravada com o slug "acme"
      E a operadora do negócio, com papel do painel e sem vínculo com essa organização
      Quando ela segue o endereço do link da organização
      Então o canal de log da tenancy recebe um aviso de "[User@canAccessTenant]" com o motivo "sem_vinculo" e o id da organização
      E o mesmo canal, no acesso da administradora vinculada, não recebe nenhum aviso com esse motivo

    Cenário: [CT-10] a recusa não tranca o administrador fora da administração
      Dado o administrador da instalação, sem papel do painel de negócio, na listagem de organizações
      Quando ele segue o endereço do link e recebe a recusa
      Então ele continua autenticado
      E a listagem de organizações volta a abrir com sucesso

    Cenário: [CT-11] renderizar o link não cria vínculo nem papel
      Dado uma organização ativa gravada com o slug "acme"
      E o administrador da instalação, sem papel do painel de negócio e sem vínculo com ela
      Quando ele abre a listagem, a ficha e a edição da organização
      Então nenhuma linha nova existe na pivot de organizações do usuário
      E nenhum papel novo existe na pivot de papéis para ele
```

**O não-efeito do CT-09 tem destinatário.** O canal `tenancy` é o mesmo nos dois acessos, e o
caminho de negação **grava nele** — é o que a linha anterior do próprio cenário prova. A segunda
asserção não é feita num mundo sem canal: ela é feita no mundo onde o aviso acabou de ser visto.

**O não-efeito do CT-11 tem alvo.** A pivot `tenant_user` existe, a organização existe, o usuário
existe, e há um caminho no kit que **grava** ali (o `UsersRelationManager` da própria tela). A
contagem é tirada antes e depois, e não "nenhum registro" genérico.

**CT-10 é a saída do estado de erro.** O `AuthenticateSession` está na pilha do painel de negócio;
um clique que invalidasse a sessão deixaria o administrador fora do `/admin` também — o defeito que
nenhum cenário de 403/404 isolado enxerga, porque cada um, sozinho, está certo.

**Camada.** Os quatro são `Feature` com `GET` real — **por fora do componente de UI**, que é o gate
de camada da regra de autorização: uma barreira que existisse só no `Resource` ficaria verde em
qualquer teste de componente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | o link "ajuda": renderizá-lo (ou segui-lo) cria a linha no pivot para quem não tem | CT-11 |
| M16 | o portão 2 passa a devolver 403, confirmando que a organização existe e permitindo enumerar clientes por varredura de slug | CT-08 (a linha `sem_vinculo`) |
| M17 | o log de negação do vínculo deixa de ser registrado | CT-09 |
| M18 | seguir o link invalida a sessão e tranca o administrador fora do `/admin` | CT-10 |
| M14 | *(também de R2)* o link ganha a guarda dos dois portões e desaparece para quem não entra | CT-04 |

---

## Regra R6 — o link não é campo

> RQ-02 · área **E**, perfil **padrão** · técnicas: **gate de tela de escrita**, **idempotência
> ancorada no registro persistido**, armadilhas próprias da edição

```gherkin
  Regra: a entrada do link não participa do estado do formulário nem da gravação

    Cenário: [CT-12] a edição continua gravando os campos da organização
      Dado uma organização ativa gravada com o nome "Acme" e o slug "acme"
      Quando o mestre da instalação salva a edição com o nome "Acme Brasil" e o slug "acme-brasil"
      Então nenhum erro de formulário é apresentado
      E a organização gravada tem o nome "Acme Brasil" e o slug "acme-brasil"

    Esquema do Cenário: [CT-13] salvar sem alterar nada não muda nada
      Dado uma organização <estado> gravada com o slug "acme"
      Quando o mestre da instalação abre a edição e salva sem alterar nenhum campo
      Então nenhum erro de formulário é apresentado
      E todos os atributos gravados da organização continuam com os mesmos valores

      Exemplos:
        | estado  | # partição de `ativo` |
        | ativa   | estado normal         |
        | inativa | exclusão lógica       |
```

**CT-13 é três armadilhas num cenário, e todas são da edição, não da criação.** (i) a unicidade do
slug acusando colisão do registro **consigo mesmo**; (ii) a entrada nova entrando no `dehydrate` e
escrevendo alguma coluna no save; (iii) a linha `inativa`, que fecha a célula
`inativa × gravar` — o registro logicamente excluído **ainda tem de funcionar** na operação de
escrita. O oráculo é o **agregado persistido** (os atributos do registro relido), não o retorno da
chamada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M19 | a entrada é um `TextInput` desabilitado com a URL — que vem no `dehydrate` e vai para o save | CT-12, CT-13 |
| M20 | a entrada reaproveita o nome de uma coluna existente e sobrescreve o valor dela ao salvar | CT-13 |
| M21 | a unicidade do slug passa a acusar colisão do registro com ele mesmo, e a edição deixa de salvar | CT-13 |

---

## Regra R7 — a URL sai do slug do próprio registro

> RQ-05 · área **F**, perfil **padrão** · técnica: **contagem de queries invariante à
> cardinalidade**

```gherkin
  Regra: a coluna do link não introduz consulta por linha

    Cenário: [CT-14] a listagem custa o mesmo com uma e com cinco organizações
      Dado uma organização ativa gravada e a listagem já carregada uma vez
      E a contagem de consultas de um segundo carregamento, com essa única organização
      Quando outras quatro organizações são gravadas e a listagem é carregada de novo
      Então a contagem de consultas é a mesma das duas vezes

    Cenário: [CT-15] cada linha exibe o endereço da sua própria organização
      Dado cinco organizações ativas gravadas, com slugs distintos
      Quando o administrador da instalação abre a listagem
      Então o HTML contém os cinco endereços, um por slug
```

**O aquecimento do `Dado` não é cerimônia: sem ele o cenário é flaky e mede o arnês.** O primeiro
carregamento da listagem paga o cache de permissões do spatie e a resolução do painel; a contagem
do primeiro render é sempre maior que a do segundo, por motivo que **não** é a feature. Comparar
"1 registro (frio)" com "5 registros (quente)" produziria vermelho aleatório e, pior, poderia
esconder um N+1 de quatro queries atrás da diferença de aquecimento. As duas medições são feitas
**quentes**.

**Por que invariância e não um número.** O número de queries da listagem é do PRD, não do
requisito, e envelhece com qualquer mudança de painel. A **invariância à cardinalidade** é derivada
de RQ-05 ("a URL é derivada do slug", isto é, do próprio registro) e é o que distingue uma coluna
que lê o atributo de uma que consulta por linha.

**CT-15 mata o mutante oposto do CT-14** — a resolução feita uma vez e repetida em todas as linhas
custa o mesmo e está errada. Sem ele, "zero query nova" ficaria satisfeito por uma listagem em que
as cinco linhas apontam para a mesma organização.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | a coluna resolve o endereço por relação ou consulta, uma por linha renderizada | CT-14 |
| M23 | o endereço é resolvido uma vez e repetido em todas as linhas | CT-15 |

---

## Regra R8 — o slug que compõe a URL continua restrito

> RQ-05 + `## Superfície Livewire` do `02` · área **A**, perfil **padrão** · técnicas: **EP com
> inválidas isoladas**, **BVA 3-valores** (incremento: 1 caractere)

```gherkin
  Regra: o slug que vira endereço só aceita caractere de slug, e no máximo 120

    Esquema do Cenário: [CT-16] a edição recusa slug que não é slug, e não altera o gravado
      Dado uma organização ativa gravada com o slug "acme"
      Quando o mestre da instalação salva a edição com o slug <slug>
      Então o campo slug apresenta erro
      E a organização gravada continua com o slug "acme"

      Exemplos:
        | slug                  | # partição inválida        |
        | ../outra              | caminho relativo           |
        | acme/painel           | separador de caminho       |
        | acme painel           | espaço                     |
        | acme?x=1              | início de query string     |
        | organização           | acento                     |
        |                       | vazio                      |

    Esquema do Cenário: [CT-17] o comprimento do slug é inclusivo em 120
      Dado o mestre da instalação na tela de cadastro de organização
      Quando ele salva uma organização com um slug de <tamanho> caracteres
      Então o resultado é "<resultado>"

      Exemplos:
        | tamanho | resultado | # borda  |
        | 119     | gravado   | borda−1  |
        | 120     | gravado   | borda    |
        | 121     | recusado  | borda+1  |

    Cenário: [CT-18] o link segue o slug gravado, sem normalizar
      Dado uma organização gravada por fora do formulário com o slug "ACME-Brasil"
      Quando o administrador da instalação abre a ficha da organização
      Então o link exibido termina com o segmento "/ACME-Brasil"
```

**Uma inválida por cenário**: cada linha do CT-16 é uma partição isolada — combinar duas deixaria a
primeira validação a disparar mascarando a segunda.

**O não-efeito do CT-16 tem alvo**: a organização existe, gravada com `acme`, e o caminho feliz
(CT-12) altera esse mesmo campo. A asserção "continua com `acme`" é feita num mundo em que o
campo mudaria.

**CT-18 é o cenário por fora do componente de UI para esta regra, e é o que a premissa 3 permite
escrever.** A gravação é feita por factory (sem formulário), e o oráculo é o **invariante das duas
leituras**: seja o slug barrado no model ou apenas no formulário, a feature **não conserta** o que
está gravado. `ACME-Brasil` passa pelo `alphaDash` (maiúscula é permitida) mas não pelo `Str::slug`
— então um gerador que normalizasse produziria `/acme-brasil`, um endereço que **não existe**, e o
link nasceria quebrado sem ninguém notar. É um valor discriminante escolhido para isso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | o `->alphaDash()` do slug é removido (ou trocado por `->regex()` frouxo), e o `href` de uma tela de administração passa a ser escolhido por quem cadastra | CT-16 |
| M25 | o `->maxLength(120)` sai, ou vira 121, e o único teto do slug desaparece | CT-17 |
| M26 | o gerador aplica `Str::slug()` no slug gravado, e o link aponta para endereço inexistente | CT-18 |

---

## Regra R9 — com a tenancy desligada a superfície do link não é alcançável

> RQ-01 (o requisito pressupõe a rota `/app/{slug}`, que só existe com tenancy ligada); mecanismo
> em ADR-03 · área **G**, perfil **mínimo** · técnica: **EP** (config ligada/desligada)

```gherkin
  Regra: sem multi-tenancy não há painel de organização, e nenhuma tela oferece o link

    Cenário: [CT-19] com a tenancy desligada a listagem de organizações não abre
      Dado a instalação com a multi-tenancy desligada
      E uma organização gravada na tabela, com o slug "acme"
      Quando o administrador da instalação abre a listagem de organizações
      Então a resposta é 403
      E o cadastro de organizações não aparece na navegação
```

**O "não se aplica" aqui tem destinatário.** A organização **existe** na tabela (ela existe sem
tenancy, só não significa nada), então a listagem teria uma linha para renderizar um link. Sem essa
fixture o cenário passaria por não haver o que renderizar, e não por a tela estar fechada.

**Suíte.** `tests/Kit` — a única em que `kit.tenancy.enabled` é falso. Escrito em `tests/Tenancy`
com `config()->set()` num `beforeEach`, o caso mediria o arnês: o `TenancyTestCase` fixa a config
antes das migrations.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | a entrada é acrescentada fora do `TenantResource` (num hub, num widget, no menu) e fica alcançável sem tenancy, onde o gerador não tem rota | CT-19 |
| M28 | `canAccess()` ou `shouldRegisterNavigation()` do `TenantResource` perdem a condição de config no diff da feature | CT-19 |

---

## Matriz Estado × Operação

Uma só tabela, produto cartesiano fechado, montada a partir dos estados do registro e dos verbos
que a feature toca — não a partir do mapa de regras.

**Estados** (3): `não gravada` (state do `CreateTenant`), `ativa`, `inativa` — `ativo` é a exclusão
lógica desta entidade: não há `SoftDeletes` no `Tenant` e não há `DeleteAction` na tabela.
**Operações** (5): `listar`, `ver`, `editar`, `gravar`, `seguir` (o `GET` no endereço do link).

**3 × 5 = 15 células** · válidas exercitadas: **10** · não se aplica: **4** · lacuna declarada: **1**

| | listar | ver | editar | gravar | seguir |
|---|---|---|---|---|---|
| **não gravada** | n/a ¹ | n/a ¹ | **CT-06** ❌ | **CT-20** ✅ | n/a ² |
| **ativa** | **CT-04, CT-05, CT-15** ✅ | **CT-04** ✅ | **CT-04** ✅ | **CT-12, CT-13** ✅ | **CT-08, CT-09, CT-10** ✅❌ |
| **inativa** | **CT-05** ✅ | **CT-05** ✅ | **CT-05** ✅ | **CT-13** ✅ | ⚠️ lacuna declarada ³ |

¹ **não se aplica**: registro não gravado não tem linha na listagem nem rota `view`.
² **não se aplica**: sem endereço renderizado não há o que seguir — e é exatamente o que CT-06 afirma.
³ **lacuna declarada**: ver `## Lacunas Declaradas`, item 1.

**Legenda, que é asserção e foi conferida célula a célula:**

- ✅ = a operação foi **executada** naquele estado, com oráculo sobre o observável dela — não sobre
  uma operação vizinha.
- ❌ = a operação é recusada **e** o não-efeito é afirmado. Os efeitos que cada operação dispara no
  caminho feliz, e que por isso têm de ser negados:
  - `não gravada × editar` (CT-06): a operação dispara **renderização de link** no caminho feliz
    (CT-04) → CT-06 nega o link. E dispara **gravação** no caminho feliz do cadastro → CT-06 nega a
    existência da organização no banco. **Duas asserções de ausência, as duas com alvo.**
  - `ativa × seguir` (CT-08, linhas 403 e 404): a operação dispara **entrada no painel** no caminho
    feliz → a recusa é o status. E o caminho feliz de quem tem acesso **não** grava aviso de
    negação → CT-09 nega o aviso no acesso legítimo. E o caminho feliz de concessão **grava na
    pivot** → CT-11 nega a linha nova.

**As duas dimensões que a matriz não mostra, e que não ficaram fixas:**

| Dimensão | Onde varia |
|---|---|
| **persona** | CT-08 (quatro personas, uma por portão), CT-04 e CT-11 (a persona que **falha** nos dois portões), CT-12/CT-13/CT-16/CT-17 (o mestre da instalação) |
| **campo alterado** | CT-12 altera **nome e slug**; CT-16 altera **só o slug** (o campo que decide o endereço); CT-03 altera o slug **depois** de o registro estar gravado, que é o estado em que a recomputação do link importa |

---

## Lacunas Declaradas

| # | O que não é afirmado | O que foi tentado | Vinculada a |
|---|---|---|---|
| 1 | O que acontece ao **seguir** o link de uma organização **inativa** (`inativa × seguir`) | Lidos os dois lados: `IdentifyTenant` só consulta `canAccessTenant()` (`vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:handle:40-42`), que **não** olha `ativo` (`app/Models/User.php:canAccessTenant:789-809`); `User::getTenants()` **filtra** `ativo` (`app/Models/User.php:getTenants:775-782`). Os dois discordam e o `00` não decide. Escrever o cenário na direção "falha fechado" (a inativa não abre) o deixaria **vermelho contra a implementação correta**, porque mudar os portões está em `## Fora de Escopo` | pergunta **2** do `## Fronteira com o Plano`. O invariante que vale nas duas leituras **está** escrito: CT-05 |
| 2 | Uma barreira de domínio para o `slug` **fora do formulário** (o gate "≥1 cenário por fora do componente de UI" para R8, na direção de recusa) | Não existe barreira no model: `Tenant::create(['slug' => '../outra'])` grava. Escrever o cenário na direção de recusa seria vermelho contra a implementação atual, e o requisito não pede a barreira. O que foi escrito no lugar é o **invariante das duas leituras** (CT-18): a feature não normaliza o slug gravado, seja ele barrado onde for | pergunta **3** do `## Fronteira com o Plano` |

Nenhum mutante previsto fica sem matador por causa das duas: M24 é morto por CT-16 (a barreira que
**existe**, no ponto de entrada que existe) e M26 por CT-18.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **CT-08** — o `{record}` do `/admin/organizacoes` é deliberadamente **não** escopado (o `TenantResource` administra todas, e o docblock diz isso), então a fronteira horizontal desta feature não é o registro no `/admin`: é o **destino**, e ela é exercitada por persona em CT-08 |
| Autorização exercida na ação (não só `can()`) | **CT-08** — `GET` real, por fora do componente de UI |
| Idempotência (ancorada no agregado persistido) | **CT-13** — salvar duas vezes sem alterar; o oráculo são os atributos do registro relido, não o retorno |
| Concorrência | **não se aplica**: a feature não tem contador, saldo, estoque nem limite de uso |
| Fronteira no ponto de entrada (gravação) | **CT-16** (edição), **CT-17** (criação) |
| Domínio condicionado (um campo muda a fronteira do outro) | **não se aplica**: o `nome` **sugere** o slug (`afterStateUpdated`), mas não altera o domínio dele — `alphaDash` e 120 valem para qualquer nome |
| Estado × operação de escrita (o desligado ainda funciona?) | **CT-13**, linha `inativa` |
| Ausente ≠ `null` ≠ vazio | **CT-16**, linha `vazio`. O slug é `required` e `NOT NULL`: não há partição `ausente` distinta no formulário. O caso `null` é o do **registro não gravado** → CT-06 |
| Paginação | **CT-15** (cinco registros numa página). Item inserido entre a página 1 e a 2: **não se aplica** — a feature não muda a query nem a ordenação da listagem, e nenhum CT dela afirma sobre ordem |
| Ordenação por coluna | **não se aplica**: a coluna nova exibe endereço, e o requisito não pede ordenação por ela. Ordenar por `slug` já existe e já é coberto |
| Timezone / DST / virada de dia | **não se aplica**: a feature não grava, lê nem compara tempo |
| Unicode / limite de varchar | **CT-16** (acento recusado pelo `alphaDash`), **CT-17** (119/120/121). **Atenção — o 120 não vem do banco**: a coluna é `$table->string('slug')`, ou seja 255 (`database/migrations/0001_01_01_000020_create_tenants_table.php:29`). O 120 existe **só** no formulário, então CT-17 é a única guarda desse teto em todo o kit |
| Unicidade + soft delete | **não se aplica**: o `Tenant` não usa `SoftDeletes` (conferido em `app/Models/Tenant.php:5-13,68-73`) e a tabela não tem `DeleteAction` por decisão registrada no docblock de `TenantsTable`. A metade que **existe** — unicidade contra o próprio registro na edição — é **CT-13** |
| CRUD combinado (editar sem alterar; ID inexistente) | **CT-13** (editar sem alterar nada). ID inexistente: **não se aplica** — é o route binding do resource, pré-existente e não tocado pela feature |
| Mass assignment | **CT-13** — se a entrada do link virasse campo e trouxesse chave nova no `dehydrate`, algum atributo do registro mudaria num save sem alteração |
| Upload | **não se aplica**: a feature não acrescenta upload; o campo `logo` é pré-existente |
| Precisão monetária | **não se aplica**: sem valor monetário |
| Superfície Livewire (método público, propriedade pública, estado do framework) | **não se aplica como superfície nova**: o `02` registra que a feature não cria Page, Widget, método público nem propriedade pública, e não consome `$tableFilters`/`$tableSearch`/`$tableSortColumn`. O ponto de entrada que o inventário **achou** — o `slug`, escrito por humano e consumido como segmento de URL — é **CT-16** (valor fora do domínio) e **CT-18** (valor gravado por fora da tela) |
| Estado do framework usado sem validar (índice de array, `parse`, coluna) | **não se aplica**: nenhum valor de `$filters`/`$tableFilters` é consumido pela feature |
| IDOR por entidade (uma linha por tabela persistida) | **não se aplica**: a feature **não persiste** nenhuma tabela nova. `tenants` é pré-existente e sua fronteira é **CT-08** (destino) + `tests/Tenancy/PermissoesDeTenantResourceTest.php` (acesso à tela, pré-existente) |
| Escopo com discriminante nulo (fecha ou abre?) | **CT-06** — o discriminante desta feature é o slug **gravado**; nulo (registro não gravado) tem de **fechar**: nenhum link |
| Saída do estado de erro (4xx tem destino alcançável) | **CT-10** |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | o endereço é o do painel da organização, e não o do registro nem o da raiz | R1 | EP | Feature | `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` | M02, M03, M04 |
| CT-02 | o caminho do painel não é escrito à mão no gerador | R1 | varredura de fonte | Feature | idem | **M01** |
| CT-03 | trocar o slug move o link | R1 | invariante temporal | Livewire (`EditTenant`) | idem | M05 |
| CT-04 | o link aparece nas três telas, para quem **não** passa nos portões | R2 | EP por superfície | Livewire ×3 | idem | M06, M07, **M14** |
| CT-05 | a organização inativa também exibe o link, e é o dela | R2 | partição de `ativo` | Livewire ×3 | idem | M08 |
| CT-06 | a tela de cadastro não oferece link, nem com o slug digitado | R3 | EP (não gravada) | Livewire (`CreateTenant`) | idem | M09, M10 |
| CT-07 | o link da tela abre em nova aba | R4 | EP por superfície | Livewire ×3 | idem | M12, M13 |
| CT-08 | seguir o link devolve o que os portões decidem | R5 | matriz persona × portão | Feature (`GET`) | idem | M16 |
| CT-09 | a negação do vínculo fica registrada; o acesso legítimo não | R5 | rastreio de efeito | Feature (`GET`) | idem | M17 |
| CT-10 | a recusa não tranca o administrador fora da administração | R5 | saída do erro | Feature (`GET`) | idem | M18 |
| CT-11 | renderizar o link não cria vínculo nem papel | R5 | rastreio de efeito | Feature | idem | M15 |
| CT-12 | a edição continua gravando os campos | R6 | gate de tela de escrita | Livewire (`EditTenant`) | idem | M19 |
| CT-13 | salvar sem alterar nada não muda nada | R6 | idempotência no agregado | Livewire (`EditTenant`) | idem | M19, M20, M21 |
| CT-14 | a listagem custa o mesmo com uma e com cinco organizações | R7 | contagem de queries | Livewire (`ListTenants`) | idem | M22 |
| CT-15 | cada linha exibe o endereço da sua própria organização | R7 | cardinalidade | Livewire (`ListTenants`) | idem | M23 |
| CT-16 | a edição recusa slug que não é slug, e não altera o gravado | R8 | EP, inválidas isoladas | Livewire (`EditTenant`) | idem | M24 |
| CT-17 | o comprimento do slug é inclusivo em 120 | R8 | BVA 3-valores | Livewire (`CreateTenant`) | idem | M25 |
| CT-18 | o link segue o slug gravado, sem normalizar | R8 | invariante, escrita fora da UI | Livewire (`ViewTenant`) | idem | M26 |
| CT-19 | com a tenancy desligada a listagem não abre | R9 | EP (config) | Feature (`GET`) | `tests/Kit/LinkDoPainelSemTenancyTest.php` | M27, M28 |
| CT-20 | o cadastro continua gravando, e o link aparece na edição | R3 | gate de tela de escrita | Livewire (`CreateTenant`) | `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` | M11 |

**28 mutantes previstos, 28 com matador, 0 sem.**

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "o endereço gerado é absoluto e começa pela raiz da aplicação" | não mata nenhum mutante previsto: tanto o gerador do painel quanto a concatenação com `url()` produzem absoluto |
| "a coluna nova é `searchable`/`sortable`" | comportamento que só o PRD determinaria, e nem ele determina; sem origem no requisito |
| "a listagem exibe o rótulo do link traduzido" | rótulo é escolha de implementação; nenhum RQ o fixa |
| "seguir o link de organização que não existe devolve 404" | é o route binding do painel de negócio, pré-existente e fora do escopo — e já coberto por `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| "o `href` renderizado é igual em duas visitas seguidas" | mata o mesmo mutante que CT-01 (M02/M03), e mais fraco |
| "o admin sem `ViewAny:Tenant` não abre a listagem" | já é `tests/Tenancy/PermissoesDeTenantResourceTest.php:28-34`, pré-existente; duplicar não acrescenta |

---

## Sem CT-B

**Não há `05-casos-de-teste-browser.md`**, e o motivo é o gate, não o orçamento.

O que a feature entrega é um `<a href>` com o endereço certo, presente ou ausente conforme a tela,
com ou sem `target="_blank"`. Nenhuma das três superfícies afirma sobre **JavaScript executado,
console, acessibilidade, cor ou layout** — as quatro coisas que só o navegador prova. O único
aspecto aparentemente "de navegador" é a nova aba, e ela é o atributo `target="_blank"` no HTML,
emitido por `Filament\Support\generate_href_html()`
(`vendor/filament/support/src/helpers.php:generate_href_html:159-162`) e afirmável por componente
Livewire em milissegundos — é o CT-07.

Contra-argumento considerado e rejeitado: "um CT-B provaria que o clique abre a outra aba". Não
provaria nada que CT-07 não prove: o que abre a aba é o navegador, a partir do atributo; o
navegador honrando o `target` não é comportamento desta aplicação. E as três telas já entram no
smoke de navegador do kit (`/admin/organizacoes` e `/admin/organizacoes/create` estão em
`telasDoKit()`, `tests/Pest.php`), que continuaria verde de qualquer forma — é a assertion que o
`## Sem CT-B` existe para não fingir que cobre isto.

---

## Revisão Adversarial

Disparada por **Impacto 3** nas áreas A e C. Delegada a sub-agente que não derivou os cenários,
com entrada limitada ao `00-requisito.md` e a este arquivo.

| # | Achado | O que virou |
|---|---|---|
| — | *a preencher na execução da revisão* | — |
