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
| **G** — tenancy desligada | 1 | 3 | 3 | mínimo |

**Impacto 3 nas áreas A e C** → [revisão adversarial obrigatória](#revisão-adversarial), mesmo com
P×I ≤ 6. Justificativa do I=3:

- **A**: o `slug` é escrito por humano e **entra numa URL** renderizada no painel de
  administração. Sem a restrição de caractere, o `href` de uma tela de administração passa a ser
  escolhido por quem cadastra a organização. É superfície de segurança, não cosmética.
- **C**: o link atravessa dois portões de autorização (`canAccessPanel`, `canAccessTenant`). O
  requisito decidiu **não** guardá-lo — então o que resta a travar é que ele continue sem
  **conceder** nada.

- Técnicas aplicadas: EP, BVA 3-valores (comprimento do slug), **partição de unicidade**, tabela
  estado × operação, matriz persona × portão, rastreio de efeito (log, pivot), contagem de queries,
  varredura de código-fonte (ausência do literal do caminho), inventário de telas.
- Cenários: **24** · Regras: **9** · Mutantes previstos: **38** · Sem matador: **0** ·
  Lacunas declaradas: **3** (uma delas reduzida a **meia** célula — ver `## Lacunas Declaradas`)

> **Este arquivo foi reconciliado DEPOIS da implementação, e a ordem invertida é deliberada.** A
> revisão adversarial disparada pelo Impacto 3 chegou depois de a feature fechar verde, e quatro
> dos seus achados já tinham sido endereçados nos **testes**, não aqui — por um período os testes
> foram mais fortes que a especificação deles. Os treze achados estão em `## Revisão Adversarial`,
> cada um com o que virou. Onde o `04` e o teste divergiam, quem manda é o `03-progresso.md` →
> `## Desvios do Plano` (D-01..D-04): o cenário foi reescrito para o que o teste **prova**, e nunca
> o contrário.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | Um gerador de URL (ponto único) + três schemas já existentes (`TenantForm`, `TenantInfolist`, `TenantsTable`). **Nenhuma** migration, coluna, rota, config, evento, job ou comando novo | CT-01, CT-02 |
| **F** | Gerar o endereço; renderizar o link (presente ou ausente por tela); abrir em nova aba; **não** conceder acesso; **não** interferir na gravação; **não** custar query | CT-04, CT-06, CT-07, CT-11, CT-12, CT-14 |
| **D** | `slug` (`alphaDash`, **`unique`**, `maxLength(120)`) — as **três** restrições, e a unicidade é o que faz o endereço IDENTIFICAR a organização —, escrito por humano e consumido como segmento de URL; `ativo` (a "exclusão lógica" desta entidade — não há `SoftDeletes` nem `DeleteAction`); registro **não gravado** (state do `CreateTenant`); slug gravado por fora do formulário | CT-05, CT-13, CT-16, CT-17, CT-18 |
| **I** | Três telas do painel `/admin`; o gerador alcançável por PHP puro; e o **destino**, alcançável por `GET /app/{slug}` — que é onde os portões decidem | CT-01, CT-08, CT-19 |
| **P** | Depende de o painel `app` estar registrado **com** `->tenant()`, o que só acontece com `config('kit.tenancy.enabled')`. Por isso CT-01..CT-18, CT-20 e CT-22 vivem em `tests/Tenancy` (o `TenancyTestCase` fixa `permission.teams` antes das migrations) e **CT-19 vive em `tests/Kit`**, **CT-19 e CT-21 vivem em `tests/Kit`**, a única suíte onde a tenancy está desligada. O `assertSeeHtml` depende do formato de `Filament\Support\generate_href_html()` (`vendor/filament/support/src/helpers.php:generate_href_html:153`) | CT-19, CT-21 |
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
| **R5** — o link **navega**, não autoriza: quem não passa nos portões recebe a recusa do Filament, o motivo é registrado, e nada é concedido | C (padrão) | RQ-01 + decisão (a) do `## Ambiguidades` + `## Fora de Escopo` | matriz persona × portão + rastreio de efeito + saída do erro + invariante das duas leituras | CT-08, CT-09, CT-10, CT-11, CT-22 |
| **R6** — o link não é campo: não entra no estado do formulário nem altera a gravação do `EditTenant` | E (padrão) | RQ-02 | gate de tela de escrita + idempotência no agregado persistido | CT-12, CT-13 |
| **R7** — a URL sai do slug do **próprio registro**: a listagem não paga query por linha | F (padrão) | RQ-05 | contagem de queries invariante à cardinalidade | CT-14, CT-15 |
| **R8** — o slug que compõe a URL continua restrito a `alphaDash`, a 120 caracteres e a **um por organização**, na criação **e** na edição | A (padrão) | RQ-05 + `## Superfície Livewire` do `02` | EP (inválidas isoladas) + BVA 3-valores + **partição de unicidade** | CT-16, CT-17, CT-18 |
| **R9** — com a tenancy desligada a superfície do link não é alcançável **e o gerador devolve `null`** | G (mínimo) | RQ-01 (pressupõe a rota `/app/{slug}`, que só existe com tenancy) — mecanismo em ADR-03, revisto em 2026-09-21 | EP (config ligada/desligada) × inventário de telas × retorno do gerador | CT-19, CT-21, CT-23 |

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
- R5 (área padrão, teto 3) usa **5** cenários — é regra de **rastreio de efeito** (log, pivot) e
  o teto não divide com a matriz de persona (passo 7 da skill). O gate do passo 6 vence o teto.
  O quinto (CT-22) entrou na revisão adversarial, para fechar a metade não contestada de
  `inativa × seguir`.
- R9 (área mínimo, teto 1) usa **2** cenários — CT-21 entrou na revisão adversarial (achado A-7):
  CT-19 mata M27 só no `TenantResource`, e o modo de falha fora dele é **500** na tela inteira,
  não link quebrado. Cenário que o gate de falsificabilidade obriga, e o teto não vence gate.

---

## Fronteira com o Plano

Itens que vieram do `01`/`02` e foram **recusados como oráculo**, para nenhum cenário virar teste
do PRD:

| Item do PRD / ADR | Recusado como oráculo porque | Destino |
|---|---|---|
| "um ponto único que devolve a URL" (passo 1) | escolha de implementação — nome, classe e assinatura não são observáveis do requisito | detalhe do cenário. **Nenhum CT nomeia a função**; CT-01 e CT-02 afirmam sobre a **URL** e sobre a **fonte de onde ela sai** |
| `openUrlInNewTab()` (ADR-04) | é a API que produz o comportamento, não o comportamento | CT-07 afirma o HTML (`target="_blank"`), que é o que o usuário recebe |
| "coluna, não ação" (RQ-04, decidido) | **aceito** como oráculo: a decisão está no `00`, não só no PRD | CT-04 afirma o **estado da coluna**, e não o `href` — ver `### Coluna não é ação, e lugar não é presença` |
| "a entrada fica na seção que cadastra nome e slug" (RQ-02) | **aceito**: RQ-02 é cláusula de **lugar** ("na onde tem a parte que cadastra o nome e a slug"), e lugar é observável | CT-04 sobe a hierarquia do schema e afirma a `Section` que contém a entrada |
| "zero query nova" (`## Modelo de Execução`) | o **número** é do PRD | o oráculo usado é RQ-05 ("a URL é derivada do slug"), traduzido em **invariância à cardinalidade** (CT-14). Nenhum CT afirma "N queries" |
| "403 do Filament" (ADR-02 e `## Ambiguidades`) | **factualmente errado para o portão 2** — ver a pergunta 1 abaixo. O `IdentifyTenant` do vendor faz `abort(404)` | CT-08 afirma **403 no portão 1** e **404 no portão 2**, o que o vendor faz. A wiki precisa ser corrigida |
| "a string `/app/` não aparece no código da feature" (ADR-01, `## Riscos`) | aceito, **com exclusão obrigatória** — ver abaixo | CT-02 |

### A exclusão que o CT-02 precisa declarar, senão ele fica vermelho contra a implementação correta

`app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:description:36` **já contém** o literal `/app/{slug}`, hoje, antes da feature:

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

**E o gerador ficou em arquivo próprio, então CT-02 tem sujeito.** O ponto único é
`Tenant::urlDoPainel()` (`app/Models/Tenant.php:urlDoPainel:164`), ao lado de `urlDaLogo()` — a
irmã exata, "o endereço de algo desta organização". É a decisão D-01 do `03-progresso.md`: método
de model, nenhuma classe nova. O sujeito da varredura de CT-02 é **esse arquivo**, e não um schema
— `app/Models/Tenant.php`, com os comentários filtrados, porque o docblock do próprio método cita
`/app/{slug}` para explicar o que não faz (`.ai/rules/testes.md` → "Asserção de ausência sobre
arquivo documentado precisa filtrar comentário").

### Coluna não é ação, e lugar não é presença

Os dois oráculos abaixo não existem no HTML, e sem eles **duas decisões do `00` ficam sem
falsificador**. Foram escritos no teste antes de estarem escritos aqui — é o achado A-1/A-2 da
revisão adversarial.

| Decisão | Por que o HTML não a falsifica | O oráculo que a falsifica |
|---|---|---|
| **coluna, não ação** (RQ-04) | `Action::make()->url(…)->openUrlInNewTab()` emite **exatamente** o mesmo `href="…" target="_blank"`, pelo mesmo `generate_href_html()` do vendor. Todo cenário de HTML da listagem passaria com a feature implementada como ação por linha, e o motivo da decisão ("a coluna mostra o endereço, então o destino é visível antes do clique") ficaria sem teste | o **estado da coluna**: `assertTableColumnStateSet('url_do_painel', $endereco, $organizacao)`. Ele exige que o endereço seja o **conteúdo da célula**, e nem sequer compila contra uma ação — não há coluna para consultar |
| **lugar** (RQ-02: "na onde tem a parte que cadastra o nome e a slug") | um link como **header action** do `EditTenant`, ou numa `Section` própria no rodapé, passaria em todo cenário de HTML e não atenderia a cláusula | subir a hierarquia do schema a partir da entrada (`getContainer()->getParentComponent()`), afirmar que o pai é uma `Section` e que o `getHeading()` dela é `Identificação` |

### Perguntas em aberto

Replicadas em `00-requisito.md` → `## Ambiguidades`. Cada uma bloqueia o que está indicado.

1. **O código de status do portão 2 é 404, não 403.** `Filament\Http\Middleware\IdentifyTenant`
   faz `abort(404)` quando `canAccessTenant()` nega
   (`vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:handle:13`, o `abort(404)` na
   linha 41), e o portão 1
   (`canAccessPanel`) é que faz `abort_if(..., 403)`
   (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:authenticate:15`, o `abort_if` na
   linha 35). O
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
| CT-01..CT-18, CT-20, CT-22 | `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` | `Tenancy` (grupo `kit`) | `TenantResource::canAccess()` exige `kit.tenancy.enabled`, e o `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, antes das migrations. O papel `admin_app` **só existe** nesta suíte |
| CT-19, CT-21, CT-23 | `tests/Kit/LinkDoPainelSemTenancyTest.php` | `Kit` (grupo `kit`) | é a **única** suíte onde a tenancy está desligada. Um CT de "desligada" dentro de `tests/Tenancy` mediria o arnês, não o comportamento |

> Nenhum helper novo em `tests/Pest.php`: os dois arquivos não compartilham função. Se a
> implementação obrigar um helper comum, ele vai para `tests/Pest.php` — `.ai/rules/testes.md`.

### `beforeEach`

```php
$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
```

Sem os dois seeders, `View:Tenant` / `Update:Tenant` / `ViewAny:Tenant` não existem e todo caso
morre com 403 no arranjo — o mesmo padrão de `tests/Tenancy/IdentidadeVisualTenancyTest.php:20-22`.

### `fronteiraDeRequest()` entre visitas que trocam de painel

**Obrigatório em CT-09 e CT-10**, entre um `GET` e o seguinte
(`tests/Pest.php:fronteiraDeRequest:775`). Os dois cenários atravessam `/admin` e `/app` no mesmo
caso, e o teste não ganha de graça a fronteira que o request de verdade tem: em produção cada
request nasce com container próprio, no teste o mesmo container atravessa todas as visitas.

O motivo específico desta feature é o **`SpotlightActionRegistry`**: ele é singleton
(`FilamentSearchSpotlightServiceProvider.php:25`) e **acumula** as ações "Criar X" de todo painel
visitado. No painel seguinte, o ⌘K resolve `getUrl('create')` de um resource que não existe ali e o
request morre em **500** (`Route [filament.app.resources.agentes-ia.create] not defined`) — um 500
que se lê como "o link do painel quebrou a tela" e não tem nada a ver com a feature. Junto vão o
`ColorManager` e o `AssetManager`, pelo mesmo motivo de cache de painel.

CT-10 é o caso em que a falta disto seria mais cara: ele afirma que a listagem **volta** a abrir
depois da recusa, e um 500 do registry faria o cenário acusar exatamente o defeito que ele existe
para pegar, pelo motivo errado.

### Personas

| Nome no cenário | Como criar | Passa no portão 1? | Passa no portão 2? |
|---|---|---|---|
| **o administrador da instalação** | `usuarioComPapel('admin')` — contexto **global** | **não** (papel do painel `admin`) | não (sem vínculo) |
| **o administrador da instalação, vinculado** | o mesmo, mais `->tenants()->attach($acme)` | **não** — e é o ponto: o portão 1 decide primeiro, e vínculo não dá papel do painel `app` | (não chega a ser consultado) |
| **o mestre da instalação** | `usuarioComPapel('master_global')` | sim (`Gate::before`) | sim (`isMasterGlobal()`) |
| **a operadora do negócio sem vínculo** | `usuarioComPapel('panel_user', $acme)` **sem** `->tenants()->attach()` | sim (papel do `app` em alguma organização) | **não** (`motivo: sem_vinculo`) |
| **a administradora da organização** | `usuarioComPapel('admin_app', $acme)` + `->tenants()->attach($acme)` | sim | sim |

**Por que o contexto do papel importa, e por que os dois helpers servem para o `/admin`.** Com
`permission.teams` ligado, papel gravado fora de `Tenant::CONTEXTO_GLOBAL` (que é `0`,
`app/Models/Tenant.php:CONTEXTO_GLOBAL:67`) fica invisível no `/admin` —
`User::canAccessPanel()` compara com `contextoGlobal()` para painel sem tenancy
(`app/Models/User.php:contextoGlobal:726-729`). O `KitServiceProvider` já fixa esse contexto no
boot (`app/Providers/KitServiceProvider.php:237`), então na suíte `Tenancy` tanto
`usuarioComPapel('admin')` (explícito) quanto `usuarioCom('admin')` / `usuarioDoKit('admin')`
(implícito, pelo contexto do boot) gravam `team_id = 0` e funcionam — é o que os vizinhos
`tests/Tenancy/IdentidadeVisualTenancyTest.php:43` e
`tests/Tenancy/PermissoesDeTenantResourceTest.php:22-25` fazem. Para papel do painel `app`, ao
contrário, o contexto **tem** de ser a organização: `usuarioComPapel($papel, $acme)`. Ver
`tests/Pest.php:papelNaOrganizacao:799` e `.ai/rules/testes.md`.

O `admin` recebe a matriz inteira do painel `admin` (`database/seeders/PapeisSeeder.php:58-59`),
então `ViewAny:Tenant`, `View:Tenant` e `Update:Tenant` existem para ele — é o que permite a
persona do CT-04 abrir as três telas **sem** passar em nenhum dos dois portões do painel de
negócio. Sem os dois seeders do `beforeEach`, as três telas dão 403 no arranjo.

### Fixtures

- `Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme'])` — a organização do caminho feliz
- `tenant('Globex', 'globex', ativo: false)` — a inativa (helper de `tests/Pest.php:tenant:385`)
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

Por isso a asserção de **R4** é `assertSeeHtml('href="'.$esperado.'" target="_blank"')` — **uma só
string, adjacente**, e ela vale para qualquer das três escolhas de componente.
`assertSee('target="_blank"')` solto é **proibido** como oráculo: o topbar e o widget de informação
do próprio Filament já emitem `_blank` em toda página
(`vendor/filament/filament/resources/views/livewire/topbar.blade.php` e
`.../widgets/filament-info-widget.blade.php`).

**Mas a string adjacente tem UM dono, e ele é o CT-07.** Se CT-04, CT-05 e CT-15 também a usarem,
eles **viram** CT-07: o mutante "o link abre na mesma aba" (M13) derruba os quatro de uma vez, e o
diagnóstico aponta a regra errada — o leitor vê R2 vermelha e vai procurar link ausente, quando o
link está lá e só perdeu o `target`. Um mutante tem de derrubar o cenário da **sua** regra.

| Cenário | Regra | String afirmada |
|---|---|---|
| CT-04, CT-05, CT-15 | R2, R7 — **presença e identidade** do endereço | `href="{endereço}"`, e só |
| CT-07 | R4 — **nova aba** | `href="{endereço}" target="_blank"`, adjacente |

CT-04 acrescenta o **estado da coluna** e a **seção** (ver `### Coluna não é ação, e lugar não é
presença`); nenhum dos dois é HTML, e é por isso que eles existem.

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
      Dado o código-fonte do arquivo do gerador de endereço da feature, sem os comentários
      Quando o texto é inspecionado
      Então ele não contém o literal "/app"

    Cenário: [CT-03] trocar o slug move o link
      Dado uma organização gravada com o slug "acme", aberta na tela de edição
      Quando a administradora salva a organização com o slug "acme-2"
      Então o link da tela recarregada termina com o segmento "/acme-2"
      E o endereço com o segmento "/acme" não aparece mais na tela
```

**O segundo `Então` do CT-02 foi CORTADO** ("ele não contém nenhuma concatenação do slug com um
caminho"). Não é oráculo executável: não existe forma de afirmar "nenhuma concatenação" sem um
regex adivinhado sobre fonte, e `.ai/rules/testes.md` é explícita contra isso ("não invente um
regex, ele conta comentário como chamada"). O que a cláusula queria dizer — que o endereço não é
montado à mão — já é o **primeiro** `Então`, e o que ela realmente protegeria (o endereço apontar
para o lugar errado) é CT-01 e CT-03. Registrado em `### Cogitado e cortado`.

> ✅ **Fechada** no commit `3c326bf`, **antes** do ciclo 1 do quality gate. O `preg_match` saiu do
> teste e o docblock de CT-02 registra o corte. Este aviso continuou aqui dizendo "divergência
> viva" e apontando uma linha que hoje está em branco — achado QA-16 do ciclo 2, mesma família do
> QA-02: o texto sobreviveu à correção que ele descrevia.

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
      E na listagem, o endereço é o CONTEÚDO da célula da coluna do painel, e não só o alvo do link
      E na edição, a entrada do link está dentro da seção intitulada "Identificação"

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
      E nas telas de um registro só, o endereço da organização ativa não aparece

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

**Os dois `Então` novos do CT-04 são os oráculos que o HTML não dá**, e sem eles duas decisões do
`00` ficam sem falsificador — ver `### Coluna não é ação, e lugar não é presença`. A linha da
listagem afirma o **estado da coluna** (M29: a feature implementada como ação por linha emite HTML
idêntico); a linha da edição afirma a **seção** que contém a entrada (M30: um header action passa
em todo cenário de HTML e não atende RQ-02, que é cláusula de **lugar**).

**Discriminância do CT-05, e o `Então` que foi CORTADO.** O segundo `Então` do `04` original dizia
"o endereço exibido para a inativa não é o da organização ativa". Ele **não pode falhar quando o
primeiro passa**: os slugs são distintos por construção (`globex` e `acme`), logo os endereços que
`getUrl()` devolve são distintos — a asserção é uma tautologia sobre as fixtures, não sobre a
implementação. Foi cortado.

O que ficou no lugar é a versão **falsificável** da mesma preocupação, e é a que o teste já fazia:
nas telas de **um registro só** (ficha e edição da inativa), o endereço da **ativa** não pode
aparecer no HTML. Aí sim existe implementação que falha — a que resolve o link a partir da
organização errada (a primeira da página, o tenant corrente) —, e a asserção de ausência tem alvo,
porque a organização ativa existe no banco e o endereço dela é renderizável. Na **listagem** a
cláusula não se aplica: as duas organizações estão na página, e os dois endereços têm de aparecer.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M06 | o link entra só no formulário, e `view`/listagem ficam sem — a leitura mais literal de "adicione tanto no form e no view" | CT-04 (a linha da superfície faltante) |
| M07 | a listagem ganha a coluna com o endereço **como texto**, sem `href` | CT-04 (linha da listagem: a asserção é sobre `href=`, não sobre o texto) |
| M08 | o link é condicionado a `ativo`, e a organização desligada fica sem endereço visível | CT-05 |
| M14 | o link ganha a guarda dos dois portões — a alternativa 1 da ADR-02, recusada pelo usuário | CT-04 (a persona não passa em nenhum dos dois portões e o link tem de estar lá) |
| M29 | a listagem entrega o link como **ação por linha** (`Action::make()->url()->openUrlInNewTab()`) em vez da coluna decidida em RQ-04 — o HTML é **idêntico**, e todo `assertSeeHtml` continua verde | CT-04 (linha da listagem: o **estado da coluna**, que uma ação não tem) |
| M30 | o link da edição vira **header action** do `EditTenant`, ou ganha uma `Section` própria no rodapé — presente, clicável, e fora do lugar que RQ-02 pede | CT-04 (linha da edição: a `Section` que contém a entrada, e o título dela) |
| M31 | o link da inativa é resolvido a partir da organização errada (a primeira da página, o tenant corrente) nas telas de um registro só | CT-05 (a ausência do endereço da ativa na ficha e na edição da inativa) |

---

## Regra R3 — nenhum link é renderizado para organização não gravada

> RQ-02 + o invariante afirmado no `## Ambiguidades` do `00` · área **B**, perfil **mínimo** ·
> técnica: **EP** (estado "não gravada")

```gherkin
  Regra: o link só existe onde existe um slug gravado

    Cenário: [CT-06] a tela de cadastro não oferece link, nem depois de o slug ser digitado
      Dado o administrador da instalação na tela de cadastro de organização
      Quando ele preenche o nome "Acme" e o slug "acme" sem salvar
      Então o HTML não contém nenhum `href` para o endereço que o painel de negócio daria a "acme"
      E a entrada do link do painel está oculta no schema do formulário
      E a prosa "O slug vira o endereço do painel de negócio: /app/{slug}." continua na tela
      E nenhuma organização com o slug "acme" existe no banco

    Cenário: [CT-20] o cadastro continua gravando, e o link aparece na edição do que foi gravado
      Dado o mestre da instalação na tela de cadastro de organização
      Quando ele salva uma organização com o nome "Acme" e o slug "acme"
      Então a organização "acme" está gravada e ativa
      E a tela de edição dela contém o endereço que o painel de negócio gera para ela, como link
```

**O oráculo é o `href`, e NUNCA o caminho `/app/` — senão CT-06 nasce vermelho.** O `TenantForm`
é o schema do `CreateTenant` **também** (o mesmo `TenantForm::configure()` serve as duas páginas), e
a `description` da seção já contém o literal
(`app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:description:36`):

```php
->description('O slug vira o endereço do painel de negócio: /app/{slug}.')
```

É **prosa renderizada ao usuário**, não comentário — é onde está escrito o que o slug significa, e
o filtro de comentário da `.ai/rules/testes.md` não a remove. Um CT-06 escrito como
`assertDontSee('/app/')` nasce **vermelho contra a implementação correta**, e a "correção" óbvia
seria apagar a explicação do slug da tela de cadastro.

Daí as três formas do `Então`, e a terceira é o ponto:

1. **ausência do `href`** — o endereço que o painel daria a `acme`, montado no caso, não aparece
   como alvo de link nenhum. É a ausência que tem alvo: a operação renderiza link no caminho feliz
   (CT-04);
2. **a entrada oculta no schema** (`assertSchemaComponentHidden`) — afirma a condição de registro
   existente no ponto em que ela é decidida, e não no HTML resultante;
3. **PRESENÇA da prosa** — `O slug vira o endereço do painel de negócio: /app/{slug}.` continua na
   tela. Esta linha existe **exatamente** para travar o conserto errado: sem ela, um agente futuro
   faz a ausência passar apagando a `description` da seção.

**Por que o "sem salvar" é o ponto do CT-06.** O `slug` é `live(onBlur: true)` por causa do
`afterStateUpdated` do campo `nome` (`app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:afterStateUpdated:46`), então o estado do
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
quando a nova aba está declarada (`vendor/filament/support/src/helpers.php:generate_href_html:153`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | a nova aba é declarada em duas superfícies e esquecida na terceira | CT-07 (a linha da superfície faltante) |
| M13 | o link abre na mesma aba, e quem clicava na listagem perde a lista | CT-07 |

---

## Regra R5 — o link navega, não autoriza

> RQ-01 + decisão (a) do `## Ambiguidades` + `## Fora de Escopo` do `00` · área **C**, perfil
> **padrão** · técnicas: **matriz persona × portão**, **rastreio de efeito** (log, pivot),
> **saída do estado de erro**, **invariante das duas leituras**

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
        | o administrador da instalação, VINCULADO à organização            | 403      | barra no portão 1   |
        | a operadora do negócio, com papel do painel e sem vínculo         | 404      | barra no portão 2   |
        | a administradora da organização, vinculada a ela                  | 200      | passa nos dois      |

    Cenário: [CT-09] a negação do vínculo fica registrada, e o acesso legítimo não registra nada
      Dado uma organização ativa gravada com o slug "acme"
      E a operadora do negócio, com papel do painel e sem vínculo com essa organização
      Quando ela segue o endereço do link da organização
      Então o canal de log da tenancy recebe um aviso de "[User@canAccessTenant]" com o motivo "sem_vinculo" e o id da organização
      E o mesmo canal, no acesso da administradora vinculada, não recebe nenhum aviso com esse motivo

    Cenário: [CT-10] a recusa não tranca o administrador fora da administração
      Dado uma organização ativa gravada com o slug "acme"
      E o administrador da instalação, sem papel do painel de negócio
      E que a listagem de organizações abre com sucesso para ele
      Quando ele segue o endereço do link e recebe a recusa
      Então ele continua autenticado
      E a listagem de organizações volta a abrir com sucesso

    Cenário: [CT-11] renderizar o link não cria vínculo nem papel
      Dado uma organização ativa gravada com o slug "acme"
      E o administrador da instalação, sem papel do painel de negócio e sem vínculo com ela
      Quando ele abre a listagem, a ficha e a edição da organização
      Então nenhuma linha nova existe na pivot de organizações do usuário
      E nenhum papel novo existe na pivot de papéis para ele

    Cenário: [CT-22] seguir o link de organização inativa, sem vínculo, é recusado — e as duas leituras concordam
      Dado uma organização INATIVA gravada com o slug "globex"
      E a operadora do negócio, com papel do painel e sem vínculo com essa organização
      Quando ela segue o endereço do link da organização inativa
      Então a resposta é 404
      E o portão 2 nega o acesso a essa organização
      E a organização inativa não aparece entre as organizações que ela pode escolher
```

**O não-efeito do CT-09 tem destinatário.** O canal `tenancy` é o mesmo nos dois acessos, e o
caminho de negação **grava nele** — é o que a linha anterior do próprio cenário prova. A segunda
asserção não é feita num mundo sem canal: ela é feita no mundo onde o aviso acabou de ser visto.

**O não-efeito do CT-11 tem alvo.** A pivot `tenant_user` existe, a organização existe, o usuário
existe, e há um caminho no kit que **grava** ali (o `UsersRelationManager` da própria tela). A
contagem é tirada antes e depois, e não "nenhum registro" genérico.

**CT-10 é a saída do estado de erro, e ele precisa das DUAS medições.** O oráculo é "a listagem
**volta** a abrir" — e "volta" é uma afirmação sobre *transição*, não sobre estado. Sem afirmar a
abertura **antes** da recusa, a segunda metade não distingue "voltou a abrir" de "sempre abriu, e a
recusa não tinha como afetar nada": o cenário passaria verde numa instalação em que o `/admin`
jamais fecharia, medindo o arnês. A situação de partida também estava faltando — sem organização
gravada não há listagem com linha, não há link e não há endereço a seguir. As duas correções vieram
do achado A-9; **o teste já as fazia** (`tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:453-468`),
era a especificação que estava atrás.

O defeito que o cenário existe para pegar: o `AuthenticateSession` está na pilha do painel de
negócio, e um clique que invalidasse a sessão deixaria o administrador fora do `/admin` também — o
defeito que nenhum cenário de 403/404 isolado enxerga, porque cada um, sozinho, está certo.

**CT-22 é a metade NÃO contestada da lacuna 1.** A célula `inativa × seguir` tem duas metades, e a
pergunta 2 do `## Fronteira com o Plano` só suspende **uma**: a de quem **passa** nos portões (o
`master_global`, a vinculada), onde `canAccessTenant()` não olha `ativo` e o `00` não decide. Para
quem **não tem vínculo** não há contestação nenhuma: `canAccessTenant()` nega por falta de vínculo,
independente de `ativo`, e `User::getTenants()` também não a devolve porque filtra
`->where('ativo', true)` — as **duas** leituras que discordam no caso contestado **concordam** aqui.

É a mesma técnica que o `04` já aplicou na lacuna 2 (o invariante que vale nas duas leituras, CT-18)
e não tinha aplicado na 1. Com CT-22 escrito, a lacuna 1 deixa de ser uma célula inteira e passa a
ser **meia** — ver `## Lacunas Declaradas`.

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
| M32 | o portão 1 passa a aceitar **vínculo** como credencial ("ele está ligado à organização, então deixa entrar"), e quem se vincula pelo `UsersRelationManager` da própria tela ganha o painel de negócio sem papel nenhum | CT-08 (a linha do administrador **vinculado**, que continua em 403) |
| M33 | `canAccessTenant()` passa a devolver verdadeiro sem vínculo (ou `getTenants()` e `canAccessTenant()` divergem na direção de **abrir**), e a organização inativa de terceiro abre para quem tem papel do painel | CT-22 |

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

    Cenário: [CT-14] resolver o endereço das organizações da página não custa consulta nenhuma
      Dado uma organização ativa gravada e os registros da página já hidratados
      E a resolução do endereço já exercitada uma vez, para aquecer painel e rota
      Quando o endereço é resolvido para essa única organização, com o log de consultas ligado
      Então a contagem de consultas é zero
      Quando outras quatro organizações são gravadas e o endereço das cinco é resolvido
      Então a contagem de consultas continua a mesma

    Cenário: [CT-15] cada linha exibe o endereço da sua própria organização
      Dado cinco organizações ativas gravadas, com slugs distintos
      Quando o administrador da instalação abre a listagem
      Então o HTML contém os cinco endereços, um por slug
```

**Este cenário foi REESCRITO depois da implementação, e o motivo está medido.** A redação original
era "a listagem custa o mesmo com uma e com cinco organizações", e ela afirmava uma propriedade
**falsa** da tela. Medido nas duas pontas do diff, com o mesmo arnês (D-02 do `03-progresso.md`):

| | 1 organização | 5 organizações |
|---|---|---|
| **antes** da feature (`git stash push -- app/`) | **33** | **53** |
| **depois** | **33** | **53** |

A coluna nova custa **zero** — é o que o `## Modelo de Execução` do `01` afirma, e está confirmado.
Mas a listagem **já** crescia 5 consultas por linha **antes** da feature (autorização de
`ViewAction`/`EditAction` por registro, entre outras). O oráculo original nasceria **vermelho contra
a implementação correta**, medindo um N+1 de terceiro que a feature não introduziu e não pode
consertar — mudar a listagem está em `## Fora de Escopo`. Um cenário assim não é rigor: é um
cenário que obriga quem vier depois a consertar algo fora do escopo dele, ou a apagá-lo.

O oráculo **não mudou** — continua sendo **invariância à cardinalidade**, derivada de RQ-05. O que
mudou é o **sujeito**: ele passou a ser aplicado ao que a feature de fato possui, a **resolução do
endereço**, e não à tela inteira, que é de terceiro. Zero consultas para uma organização, zero para
cinco. É o que mata M22: um resolvedor por relação ou consulta marcaria N aqui, com os registros já
hidratados.

O N+1 pré-existente da listagem **não some por isso**: ele foi medido, é real, e ficou registrado
como **lacuna 3** de `## Lacunas Declaradas` — dívida de terceiro, não defeito desta feature.

**O aquecimento do `Dado` não é cerimônia: sem ele o cenário é flaky e mede o arnês.** O primeiro
`getUrl()` do processo resolve o painel e a rota; a contagem da primeira resolução é sempre maior
que a da segunda, por motivo que **não** é a feature. A hidratação dos registros antes de ligar o
log é da mesma natureza — sem ela o cenário mediria a query da própria listagem em vez da resolução
do endereço.

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
> inválidas isoladas**, **BVA 3-valores** (incremento: 1 caractere), **partição de unicidade**

```gherkin
  Regra: o slug que vira endereço só aceita caractere de slug, no máximo 120, e um por organização

    Esquema do Cenário: [CT-16] a edição recusa slug que não serve de endereço, e não altera o gravado
      Dado uma organização ativa gravada com o slug "acme"
      E uma segunda organização já gravada com o slug "globex"
      Quando o mestre da instalação salva a edição da primeira com o slug <slug>
      Então o campo slug apresenta erro
      E a organização gravada continua com o slug "acme"

      Exemplos:
        | slug                  | # partição recusada                |
        | ../outra              | caminho relativo                   |
        | acme/painel           | separador de caminho               |
        | acme painel           | espaço                             |
        | acme?x=1              | início de query string             |
        | acme.painel           | ponto                              |
        | acme%2fpainel         | percent-encoding                   |
        |                       | vazio                              |
        | globex                | slug de OUTRA organização gravada  |

    Esquema do Cenário: [CT-17] o comprimento do slug é inclusivo em 120
      Dado o mestre da instalação na tela de cadastro de organização
      Quando ele salva uma organização com um slug de <tamanho> caracteres
      Então o resultado é "<resultado>"
      E quando o resultado é "recusado", nenhuma organização com esse slug existe no banco

      Exemplos:
        | tamanho | resultado | # borda  |
        | 119     | gravado   | borda−1  |
        | 120     | gravado   | borda    |
        | 121     | recusado  | borda+1  |

    Esquema do Cenário: [CT-18] o link segue o slug gravado, sem normalizar e sem encodar
      Dado uma organização gravada por fora do formulário com o slug <slug>
      Quando o administrador da instalação abre a ficha da organização
      Então o endereço gerado termina com o segmento "/<slug>", byte a byte
      E o link exibido na ficha aponta para esse endereço

      Exemplos:
        | slug         | # o que o valor discrimina                        |
        | ACME-Brasil  | caixa preservada — o `Str::slug` a destruiria      |
        | organização  | acento gravado, endereço CRU (sem percent-encoding) |
```

**Uma recusa por linha**: cada linha do CT-16 é uma partição isolada — combinar duas deixaria a
primeira validação a disparar mascarando a segunda. **Sete** linhas são de **formato** (o valor não
serve de segmento de URL) e a **oitava** é de **unicidade** (o valor serve, mas já é de outra
organização): é a mesma regra R8 e o mesmo oráculo — o campo acusa erro, e o gravado não muda.

**O não-efeito do CT-16 tem alvo**: a organização existe, gravada com `acme`, e o caminho feliz
(CT-12) altera esse mesmo campo. A asserção "continua com `acme`" é feita num mundo em que o
campo mudaria.

### A linha `acento` saiu, e as duas que entraram no lugar

Registrado como **D-03** no `03-progresso.md`. O `04` original listava `organização` como partição
**inválida**, e a linha nasceria **vermelha contra a implementação correta**: `alpha_dash` do
Laravel é **unicode-aware**, e sem o argumento `ascii` a regra é `/\A[\pL\pM\pN_-]+\z/u`
(`vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:validateAlphaDash:403`)
— `ç` e `ã` são `\pL`, e o slug acentuado **grava**. Trocar `->alphaDash()` por
`->alphaDash(ascii: true)` seria mexer numa validação que **não é desta feature** para fazer um
cenário passar: recusado.

As duas linhas que entraram cobrem o que a do acento **pretendia** cobrir e o `\pL` de fato recusa,
e as duas são metacaracteres de **segmento de URL**, que é o risco real da feature: o **ponto**
(`acme.painel`, que abre caminho para `..` e para extensão de arquivo) e o **percent-encoding**
(`acme%2fpainel`, uma barra disfarçada). O acento migrou para **CT-18**, do lado **válido**: ele
grava, e o link segue o gravado.

### A unicidade — a terceira restrição do campo, que o mapa de regras tinha perdido

A varredura SFDIPOT inventariou `slug (alphaDash, unique, maxLength(120))`. R8 recolheu duas das
três: o `unique` (`app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:unique:54`) não tinha mutante nem cenário. Achado A-5.

**Por que ele importa NESTA feature, e não só no cadastro.** É a unicidade que faz o endereço
**identificar** a organização. Sem ela, duas linhas da listagem exibem o **mesmo** `href`, e uma
organização ganha um link que abre a **outra** — que é exatamente o defeito que CT-05 e CT-15 foram
desenhados para pegar, chegando por um caminho que nenhum dos dois cobre: os dois partem de slugs
distintos por construção, e nenhum deles exercita a gravação.

A partição é **irmã** das outras do CT-16 — "valor que o campo tem de recusar, e o gravado não
muda" — e por isso cabe como linha dos `Exemplos`, com o `Dado` acrescentando a segunda organização.
O `Dado` novo vale para as **oito** linhas: uma organização a mais no banco não muda o veredito das
**sete** primeiras, e evita um segundo esquema para uma linha só.

> **Medido, não suposto.** O cenário **PASSA** hoje. `->unique()` do Filament ignora o próprio
> registro **por padrão** nesta versão — `$ignoreRecord ??= $component->shouldUniqueValidationIgnoreRecordByDefault()`
> (`vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:unique:563`) e a propriedade
> nasce `true` (`vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:shouldUniqueValidationIgnoreRecordByDefault:34`). É por isso
> que `->unique()` sem argumento atende ao mesmo tempo CT-13 (salvar sem alterar, o registro não
> colide consigo mesmo) e esta linha (colidir com **outra** organização é erro). **Não é achado
> para o `03`**; é cenário que faltava à especificação.

**CT-18 é o cenário por fora do componente de UI para esta regra, e é o que a premissa 3 permite
escrever.** A gravação é feita por factory (sem formulário), e o oráculo é o **invariante das duas
leituras**: seja o slug barrado no model ou apenas no formulário, a feature **não conserta** o que
está gravado. `ACME-Brasil` passa pelo `alphaDash` (maiúscula é permitida) mas não pelo `Str::slug`
— então um gerador que normalizasse produziria `/acme-brasil`, um endereço que **não existe**, e o
link nasceria quebrado sem ninguém notar. É um valor discriminante escolhido para isso.

**A forma canônica do endereço no kit é a CRUA, e agora está afirmada.** Medido durante a
implementação: `Panel::getUrl()` **não** percent-encoda o segmento — o endereço de `organização`
sai `http://…/app/organização`, com o UTF-8 cru, e o `href` sai assim, porque o `e()` de
`generate_href_html()` escapa **HTML**, não **URL**. O link funciona (navegador e servidor encodam
o caminho na hora de pedir), mas até aqui **ninguém havia afirmado isso**.

*Decisão: vira asserção em CT-18, e não lacuna declarada.* Três motivos, nesta ordem:

1. **Ela é falsificável, e barata.** O `Então` "termina com `/organização`, byte a byte" tem um
   mutante plausível e imediato — um `rawurlencode()` no gerador (M35) — e cai sozinho contra ele.
   Lacuna declarada é o destino de afirmação que **não se pode escrever**; esta se escreve em uma
   linha, e declará-la lacuna seria declarar como intransponível o que custa um `toEndWith`.
2. **Ela já é consumida como verdade em outro lugar.** Todo cenário que compara endereço no kit —
   CT-01, CT-03, CT-05, CT-15 — calcula o esperado com `getUrl()` e compara **string com string**.
   Se a forma canônica mudar, os quatro passam a comparar formas diferentes da mesma URL, e o
   vermelho aparece longe da causa. A afirmação que sustenta os quatro merece um dono.
3. **A escolha não é arbitrária, e já está registrada.** A feature decidiu **não normalizar** o
   slug gravado (o próprio CT-18, linha `ACME-Brasil`). Não encodar é a mesma decisão vista do
   outro lado: o endereço é o que o painel gera a partir do que está no banco, sem que a feature
   se meta no meio. Afirmar uma metade e declarar a outra lacuna seria incoerente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | o `->alphaDash()` do slug é removido (ou trocado por `->regex()` frouxo), e o `href` de uma tela de administração passa a ser escolhido por quem cadastra | CT-16 |
| M25 | o `->maxLength(120)` sai, ou vira 121, e o único teto do slug desaparece | CT-17 |
| M26 | o gerador aplica `Str::slug()` no slug gravado, e o link aponta para endereço inexistente | CT-18 (linha `ACME-Brasil`) |
| M34 | o `->unique()` do slug é perdido no diff da feature (ou trocado por `->unique(ignoreRecord: false)`, que quebra a edição em vez de proteger a unicidade), e duas organizações passam a compartilhar o endereço — o `href` de uma abre a outra | CT-16 (linha `slug de OUTRA organização gravada`) |
| M35 | o gerador passa a percent-encodar o segmento (`rawurlencode($slug)`), e a forma canônica do endereço muda sem nada avisar: CT-01, CT-03, CT-05 e CT-15 passam a comparar duas formas da mesma URL | CT-18 (linha `organização`) |

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

    Cenário: [CT-21] com a tenancy desligada nenhuma tela do /admin estoura nem oferece o link
      Dado a instalação com a multi-tenancy desligada
      E uma organização gravada na tabela, com o slug "acme"
      Quando o administrador da instalação abre, uma a uma, as telas do painel de administração
      Então nenhuma delas responde 500
      E nenhum endereço do painel de negócio aparece no HTML de nenhuma delas
```

**O "não se aplica" aqui tem destinatário.** A organização **existe** na tabela (ela existe sem
tenancy, só não significa nada), então a listagem teria uma linha para renderizar um link. Sem essa
fixture o cenário passaria por não haver o que renderizar, e não por a tela estar fechada.

**CT-21 existe porque CT-19 mata M27 só pela metade.** M27 fala em "hub, widget, menu" — três
lugares —, e CT-19 afirma duas coisas sobre **um** lugar: a listagem do `TenantResource` em 403 e a
ausência dele na navegação. Nenhuma das duas alcança um **widget do dashboard** do `/admin`, um item
do menu do usuário ou qualquer outra entrada que renderizasse o link fora do resource. E com a
tenancy desligada o painel `app` pode nem estar registrado: o gerador não estoura em link quebrado,
estoura em **500** na tela inteira. Um widget assim deixaria o `/admin` inteiro fora do ar numa
instalação single-tenant, e CT-19 ficaria verde.

CT-21 fecha isso varrendo as telas que `tests/Pest.php:telasDoKit:225` lista para o painel `admin` —
a lista que o `InventarioDeTelasTest` já obriga a manter completa, então o cenário herda cobertura
de toda tela nova sem precisar de edição. As duas asserções são as duas metades do defeito: **nenhum
500** (o gerador chamado onde não há rota) e **nenhum `href` do painel de negócio** (a entrada
renderizada onde ela não deveria existir).

**Suíte.** `tests/Kit` — a única em que `kit.tenancy.enabled` é falso. Escrito em `tests/Tenancy`
com `config()->set()` num `beforeEach`, o caso mediria o arnês: o `TenancyTestCase` fixa a config
antes das migrations. Vale para CT-19 **e** para CT-21.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | a entrada é acrescentada fora do `TenantResource` (num hub, num widget, no menu) e fica alcançável sem tenancy, onde o gerador não tem rota | CT-19 **em parte** (só a tela do resource) + **CT-21** (as demais telas do `/admin`) |
| M28 | `canAccess()` ou `shouldRegisterNavigation()` do `TenantResource` perdem a condição de config no diff da feature | CT-19 |
| M36 | **a guarda `hasTenancy()` sai de `Tenant::urlDoPainel()`** — sem tenancy o gerador devolve `/app/{uuid}`, um link morto que responde 404, em vez de `null` | **CT-23** |
| M37 | a entrada nasce num **widget do dashboard** ou no **menu do usuário** do `/admin`, e sem tenancy o gerador chama um painel `app` que não está registrado — a tela inteira responde **500**, não link quebrado | CT-21 |

*(renumerado em 2026-09-22, achado QA-15 do ciclo 2: o ID **M36 tinha duas definições e dois
matadores** neste mesmo arquivo — esta tabela dizia "widget/menu → CT-21" e o adendo do
`/code-review` dizia "guarda `hasTenancy()` → CT-23", **medindo** que CT-19 e CT-21 seguem verdes
sem a guarda. O contador "36 com matador" contava um ID que significava duas coisas. Separados:
a guarda é M36, matada por CT-23; a entrada fora do resource é M37, matada por CT-21.)*

---

## Matriz Estado × Operação

Uma só tabela, produto cartesiano fechado, montada a partir dos estados do registro e dos verbos
que a feature toca — não a partir do mapa de regras.

**Estados** (3): `não gravada` (state do `CreateTenant`), `ativa`, `inativa` — `ativo` é a exclusão
lógica desta entidade: não há `SoftDeletes` no `Tenant` e não há `DeleteAction` na tabela.
**Operações** (5): `listar`, `ver`, `editar`, `gravar`, `seguir` (o `GET` no endereço do link).

**3 × 5 = 15 células** · com CT: **11** · com CT em **metade** da célula: **1** (`inativa × seguir`)
· não se aplica: **3** · lacuna declarada: **meia** célula. Soma: 11 + 1 + 3 = **15**.

> **Os dois números do cabeçalho estavam errados, e a soma escondia o erro** (achado A-12). A
> redação anterior declarava **10** exercitadas e **4** `n/a` — que também somam 15, e por isso a
> conferência **pelo total** passava. Conferido célula a célula: as com CT são **11** (2 na linha
> `não gravada`, 5 na `ativa`, 4 na `inativa`) e as `n/a` são **3**, todas na linha `não gravada`.
> A legenda abaixo diz "conferida célula a célula", e **legenda é asserção**: se ela afirma a
> conferência, ela tem de estar certa, senão é a única linha do arquivo que mente sobre si mesma.

| | listar | ver | editar | gravar | seguir |
|---|---|---|---|---|---|
| **não gravada** | n/a ¹ | n/a ¹ | **CT-06** ❌ | **CT-20** ✅ | n/a ² |
| **ativa** | **CT-04, CT-05, CT-15** ✅ | **CT-04** ✅ | **CT-04** ✅ | **CT-12, CT-13** ✅ | **CT-08, CT-09, CT-10** ✅❌ |
| **inativa** | **CT-05** ✅ | **CT-05** ✅ | **CT-05** ✅ | **CT-13** ✅ | **CT-22** ❌ — **meia** célula ³ |

¹ **não se aplica**: registro não gravado não tem linha na listagem nem rota `view`.
² **não se aplica**: sem endereço renderizado não há o que seguir — e é exatamente o que CT-06 afirma.
³ **meia célula**: quem **não tem vínculo** está afirmado (CT-22 — 404, e as duas leituras
concordam). Quem **passa** nos portões (`master_global`, vinculada) continua lacuna declarada — ver
`## Lacunas Declaradas`, item 1.

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
  - `inativa × seguir` (CT-22): a operação dispara **entrada no painel** no caminho feliz (CT-08,
    linha `vinculada`) → a recusa é o 404. E o não-efeito é afirmado nas **duas leituras** do kit,
    que no caso sem vínculo concordam: `canAccessTenant()` nega, e a organização não aparece entre
    as escolhíveis. É meia célula: a outra metade, de quem passa nos portões, é lacuna.

**As duas dimensões que a matriz não mostra, e que não ficaram fixas:**

| Dimensão | Onde varia |
|---|---|
| **persona** | CT-08 (**cinco** personas, incluindo o administrador **vinculado** que ainda toma 403 — vínculo não é papel), CT-22 (a operadora sem vínculo, na inativa), CT-04 e CT-11 (a persona que **falha** nos dois portões), CT-12/CT-13/CT-16/CT-17 (o mestre da instalação) |
| **campo alterado** | CT-12 altera **nome e slug**; CT-16 altera **só o slug** (o campo que decide o endereço); CT-03 altera o slug **depois** de o registro estar gravado, que é o estado em que a recomputação do link importa |

---

## Lacunas Declaradas

| # | O que não é afirmado | O que foi tentado | Vinculada a |
|---|---|---|---|
| 1 | **Meia célula** de `inativa × seguir`: o que acontece quando segue o link de organização **inativa** quem **passa** nos dois portões (o `master_global`, a administradora vinculada) | Lidos os dois lados: `IdentifyTenant` só consulta `canAccessTenant()` (`vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:handle:13`), que **não** olha `ativo` (`app/Models/User.php:canAccessTenant:789-809`); `User::getTenants()` **filtra** `ativo` (`app/Models/User.php:getTenants:775-782`). Os dois discordam **nesta metade**, e o `00` não decide. Escrever o cenário na direção "falha fechado" (a inativa não abre) o deixaria **vermelho contra a implementação correta**, porque mudar os portões está em `## Fora de Escopo`. **A outra metade não é contestada e foi escrita**: sem vínculo, as duas leituras concordam em negar, independente de `ativo` → **CT-22** | pergunta **2** do `## Fronteira com o Plano`. O invariante de renderização **está** escrito: CT-05 |
| 2 | Uma barreira de domínio para o `slug` **fora do formulário** (o gate "≥1 cenário por fora do componente de UI" para R8, na direção de recusa) | Não existe barreira no model: `Tenant::create(['slug' => '../outra'])` grava. Escrever o cenário na direção de recusa seria vermelho contra a implementação atual, e o requisito não pede a barreira. O que foi escrito no lugar é o **invariante das duas leituras** (CT-18): a feature não normaliza o slug gravado, seja ele barrado onde for | pergunta **3** do `## Fronteira com o Plano` |
| 3 | O **N+1 pré-existente da listagem de organizações**: ela cresce ~5 consultas por linha (33 com uma organização, 53 com cinco) | **Medido nas duas pontas do diff** (D-02 do `03-progresso.md`): 33/53 **antes** da feature e 33/53 **depois** — a coluna nova custa **zero**. O N+1 vem da autorização de `ViewAction`/`EditAction` por registro, é **anterior** à feature e mudar a listagem está em `## Fora de Escopo`. É dívida registrada, **não defeito desta feature**, e o CT-14 original (que a mediria) foi reescrito para o sujeito que a feature possui | `## Fora de Escopo` do `00` + D-02 do `03`. O oráculo de invariância **está** escrito, sobre a resolução do endereço: CT-14 |

Nenhum mutante previsto fica sem matador por causa das três: M24 é morto por CT-16 (a barreira que
**existe**, no ponto de entrada que existe), M26 por CT-18 e M22 por CT-14 (a resolução do endereço,
que é o que a feature possui — o custo da tela é da lacuna 3).

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
| Unicidade + soft delete | o **soft delete** não se aplica: o `Tenant` não usa `SoftDeletes` (conferido em `app/Models/Tenant.php:5-13,68-73`) e a tabela não tem `DeleteAction` por decisão registrada no docblock de `TenantsTable`. A **unicidade** tem as duas metades escritas: contra o **próprio** registro na edição (tem de passar) é **CT-13**; contra **outra** organização gravada (tem de recusar) é **CT-16**, linha `slug de OUTRA organização gravada`. É a unicidade que faz o endereço identificar a organização — sem ela, duas linhas da listagem exibem o mesmo `href` |
| Autorização: credencial certa, e vínculo não é credencial | **CT-08**, linha do administrador **vinculado** — ele vê o link, toma 403, vai ao `UsersRelationManager` da própria tela, se vincula, clica de novo e **continua tomando 403**. Vínculo não dá papel do painel `app`, e o portão 1 decide primeiro |
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
| CT-04 | o link aparece nas três telas, para quem **não** passa nos portões; é **coluna** na listagem e mora na seção `Identificação` na edição | R2 | EP por superfície + estado de coluna + hierarquia de schema | Livewire ×3 | idem | M06, M07, **M14**, **M29**, **M30** |
| CT-05 | a organização inativa também exibe o link, e é o dela | R2 | partição de `ativo` | Livewire ×3 | idem | M08, M31 |
| CT-06 | a tela de cadastro não oferece link, nem com o slug digitado | R3 | EP (não gravada) | Livewire (`CreateTenant`) | idem | M09, M10 |
| CT-07 | o link da tela abre em nova aba | R4 | EP por superfície | Livewire ×3 | idem | M12, M13 |
| CT-08 | seguir o link devolve o que os portões decidem | R5 | matriz persona × portão | Feature (`GET`) | idem | M16, M32 |
| CT-09 | a negação do vínculo fica registrada; o acesso legítimo não | R5 | rastreio de efeito | Feature (`GET`) | idem | M17 |
| CT-10 | a recusa não tranca o administrador fora da administração | R5 | saída do erro | Feature (`GET`) | idem | M18 |
| CT-11 | renderizar o link não cria vínculo nem papel | R5 | rastreio de efeito | Feature | idem | M15 |
| CT-12 | a edição continua gravando os campos | R6 | gate de tela de escrita | Livewire (`EditTenant`) | idem | M19 |
| CT-13 | salvar sem alterar nada não muda nada | R6 | idempotência no agregado | Livewire (`EditTenant`) | idem | M19, M20, M21 |
| CT-14 | resolver o endereço das organizações da página não custa consulta nenhuma | R7 | contagem de queries, invariante à cardinalidade | Feature | idem | M22 |
| CT-15 | cada linha exibe o endereço da sua própria organização | R7 | cardinalidade | Livewire (`ListTenants`) | idem | M23 |
| CT-16 | a edição recusa slug que não serve de endereço, e não altera o gravado | R8 | EP, inválidas isoladas + partição de unicidade | Livewire (`EditTenant`) | idem | M24, **M34** |
| CT-17 | o comprimento do slug é inclusivo em 120 | R8 | BVA 3-valores | Livewire (`CreateTenant`) | idem | M25 |
| CT-18 | o link segue o slug gravado, sem normalizar e sem encodar | R8 | invariante das duas leituras, escrita fora da UI | Livewire (`ViewTenant`) | idem | M26, **M35** |
| CT-19 | com a tenancy desligada a listagem não abre | R9 | EP (config) | Feature (`GET`) | `tests/Kit/LinkDoPainelSemTenancyTest.php` | M27 *(em parte)*, M28 |
| CT-20 | o cadastro continua gravando, e o link aparece na edição | R3 | gate de tela de escrita | Livewire (`CreateTenant`) | `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` | M11 |
| CT-21 | com a tenancy desligada nenhuma tela do `/admin` estoura nem oferece o link | R9 | EP (config) × inventário de telas | Feature (`GET`) | `tests/Kit/LinkDoPainelSemTenancyTest.php` | M27 |
| CT-22 | seguir o link de organização inativa, sem vínculo, é recusado nas duas leituras | R5 | invariante das duas leituras | Feature (`GET`) | `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` | **M33** |
| CT-23 | sem tenancy o gerador devolve `null`, e não o `/app/{uuid}` morto | R9 | EP (config), chamada direta do gerador | Feature (PHP puro) | `tests/Kit/LinkDoPainelSemTenancyTest.php` | **M36** |
| CT-24 | as três superfícies **avisam** que o link troca de aba | R1 | inventário das três superfícies × forma do aviso | Feature (schema resolvido) | M38 |

**36 mutantes previstos, 36 com matador, 0 sem** — `M01`..`M36`, nenhum ID pulado, conferido por
`grep -o "M[0-9][0-9]" 04-casos-de-teste.md | sort -u | wc -l`. (M36 entrou pelo `/code-review` —
ver o adendo no fim deste arquivo. O `37` escrito aqui antes vinha de um erro anterior ao M36: o
incremento 36→37 preservou a diferença em vez de corrigi-la.)

### Cenários especificados sem teste escrito — **nenhum** (fechado em 2026-09-21)

**Dois cenários e duas linhas de `Examples`** nasceram da revisão adversarial sem caso escrito, e
os quatro foram **sondados** contra o código real antes de entrarem aqui. Os quatro existem hoje, e
os quatro **confirmaram a sonda** — a direção registrada não precisou ser invertida em nenhum:

| Onde | Direção sondada | Onde o caso está |
|---|---|---|
| **CT-21** | as **18** telas de `telasDoKit()['admin']` respondem **abaixo de 500** com a tenancy desligada, e nenhuma exibe `href` do painel de negócio | `tests/Kit/LinkDoPainelSemTenancyTest.php:[CT-21]` — 56 asserções, com controle positivo |
| **CT-22** | a operadora sem vínculo recebe **404** na organização inativa; `canAccessTenant()` devolve falso e `getTenants()` não a contém | `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:[CT-22]` — as três asserções |
| **CT-16**, linha `globex` | a edição **recusa** o slug de outra organização gravada, e o gravado continua `acme`. **PASSA hoje** — `->unique()` ignora o próprio registro por padrão nesta versão do Filament | linha `slug de OUTRA organização gravada` no dataset de `[CT-16]` |
| **CT-08**, linha do administrador **vinculado** | **403** — o portão 1 decide primeiro, e vínculo não dá papel do painel `app` | persona `admin_vinculado` no dataset de `[CT-08]` |

Depois deles ainda entrou **CT-23**, pelo achado 2 do `/code-review` (ver o adendo no fim deste
arquivo). Ele é o único caso que fica vermelho se a guarda `hasTenancy()` sair do gerador.

> O gate "IDs `[CT-nn]` do teste ⊆ `04` **e vice-versa**" do step 7 **fecha** nos dois sentidos:
> 23 IDs de um lado, 23 do outro, saída vazia. Registrado na Verificação Final do
> `03-progresso.md`, como **D-05 — FECHADO**.
>
> A única exceção é declarada e não é cenário desta feature:
> `tests/Kit/ExpectativaVariadicaDoPestTest.php` tem `[CT-01]` e `[CT-02]` **próprios**, locais ao
> arquivo — ver `## Sem CT-B` → *"O teste que não é CT desta feature"*.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "o endereço gerado é absoluto e começa pela raiz da aplicação" | não mata nenhum mutante previsto: tanto o gerador do painel quanto a concatenação com `url()` produzem absoluto |
| "a coluna nova é `searchable`/`sortable`" | comportamento que só o PRD determinaria, e nem ele determina; sem origem no requisito |
| "a listagem exibe o rótulo do link traduzido" | rótulo é escolha de implementação; nenhum RQ o fixa |
| "seguir o link de organização que não existe devolve 404" | é o route binding do painel de negócio, pré-existente e fora do escopo — e já coberto por `tests/Tenancy/AdminDaOrganizacaoTest.php` |
| "o `href` renderizado é igual em duas visitas seguidas" | mata o mesmo mutante que CT-01 (M02/M03), e mais fraco |
| "o admin sem `ViewAny:Tenant` não abre a listagem" | já é `tests/Tenancy/PermissoesDeTenantResourceTest.php:28-34`, pré-existente; duplicar não acrescenta |
| CT-02, 2º `Então`: "o gerador não contém nenhuma concatenação do slug com um caminho" | **cortado na revisão** (corte **C-1**): não é oráculo executável. Afirmar "nenhuma concatenação" exige um regex adivinhado sobre fonte, e `.ai/rules/testes.md` é explícita contra isso ("não invente um regex, ele conta comentário como chamada"). O que a cláusula queria já é o 1º `Então` (o literal), e o defeito que ela temia é CT-01 e CT-03 |
| CT-05, 2º `Então`: "o endereço da inativa não é o da ativa" | **cortado na revisão** (corte **C-2**): **não pode falhar quando o primeiro passa**. Os slugs das duas fixtures são distintos por construção, logo os endereços que `getUrl()` devolve são distintos — a asserção fala das fixtures, não da implementação. A justificação virou prosa em R2, e a versão falsificável da mesma preocupação ficou no lugar: nas telas de um registro só, o endereço da **ativa** não aparece |

---

## Sem CT-B

**Não há `05-casos-de-teste-browser.md`**, e o motivo é o gate, não o orçamento.

O que a feature entrega é um `<a href>` com o endereço certo, presente ou ausente conforme a tela,
com ou sem `target="_blank"`. Nenhuma das três superfícies afirma sobre **JavaScript executado,
console, acessibilidade, cor ou layout** — as quatro coisas que só o navegador prova. O único
aspecto aparentemente "de navegador" é a nova aba, e ela é o atributo `target="_blank"` no HTML,
emitido por `Filament\Support\generate_href_html()`
(`vendor/filament/support/src/helpers.php:generate_href_html:153`) e afirmável por componente
Livewire em milissegundos — é o CT-07.

Contra-argumento considerado e rejeitado: "um CT-B provaria que o clique abre a outra aba". Não
provaria nada que CT-07 não prove: o que abre a aba é o navegador, a partir do atributo; o
navegador honrando o `target` não é comportamento desta aplicação. E as três telas já entram no
smoke de navegador do kit (`/admin/organizacoes` e `/admin/organizacoes/create` estão em
`telasDoKit()`, `tests/Pest.php`), que continuaria verde de qualquer forma — é a assertion que o
`## Sem CT-B` existe para não fingir que cobre isto.

### O teste que não é CT desta feature

`tests/Kit/ExpectativaVariadicaDoPestTest.php` entra no diff desta branch e **não tem cenário
aqui**, de propósito. Ele não afirma nada sobre o link do painel: afirma, por reflexão, que
`toContain()` e `toContainEqual()` do Pest continuam **variádicas** e sem parâmetro `$message`.

É infraestrutura da rule `.ai/rules/testes.md` → *"`toContain()` do Pest não recebe mensagem"*, que
nasceu do erro descrito no adendo abaixo. Os `[CT-01]` e `[CT-02]` dele são numeração **local ao
arquivo** e não colidem com os CT desta wiki — o gate bidirecional de IDs do step 7 compara o `04`
com `LinkDoPainelDaOrganizacaoTest` e `LinkDoPainelSemTenancyTest`, e este arquivo está fora do
conjunto por não ser da feature.

O que o torna sentinela e não teste decorativo: no dia em que o Pest aceitar mensagem nos dois, ele
fica **vermelho**, e esse é o sinal de que a rule pode **sair** do `.ai/rules/` — mesmo mecanismo
de `tests/Kit/OrdemDasCascadeLayersTest.php`. Achado **QA-09** do quality gate; registrado aqui
porque "arquivo de teste sem rastro na wiki" é achado por si, mesmo quando a resposta é *"não
pertence a esta wiki"*.

---

## Revisão Adversarial

Disparada por **Impacto 3** nas áreas A e C. Delegada a sub-agente que não derivou os cenários,
com entrada limitada ao `00-requisito.md` e a este arquivo.

**A revisão chegou DEPOIS da implementação, e isso muda como ela se lê.** Na esteira normal, o `04`
precede o código. Aqui a feature já tinha fechado verde, e o implementador já havia endereçado
**quatro** dos achados — nos **testes**, não aqui. O resultado é que por um período os testes foram
mais fortes que a especificação deles, que é a pior configuração possível: quem lê o `04` acredita
que aquilo é o contrato, e quem apaga uma linha do teste não encontra nada que reclame. Esta seção
reconcilia os dois, e a coluna **"estado"** diz de qual lado cada achado estava.

| # | Achado | Estado antes desta passagem | O que virou |
|---|---|---|---|
| **A-1** | "coluna, não ação" (RQ-04) não tinha falsificador: `Action::make()->url()->openUrlInNewTab()` emite HTML **idêntico** ao da coluna, e todo cenário de `assertSeeHtml` passaria com a feature implementada como ação por linha | **já fechado no teste** — CT-04 usa `assertTableColumnStateSet('url_do_painel', $endereco, $organizacao)` | `Então` novo em CT-04 (o endereço é o **conteúdo da célula**) + mutante **M29** + a tabela `### Coluna não é ação, e lugar não é presença` |
| **A-2** | RQ-02 é cláusula de **lugar** ("na onde tem a parte que cadastra o nome e a slug"), e nenhum cenário afirmava lugar — um header action passaria em todo cenário de HTML | **já fechado no teste** — CT-04 sobe `getContainer()->getParentComponent()` e afirma `Section` + `getHeading() === 'Identificação'` | `Então` novo em CT-04 (a seção que contém a entrada) + mutante **M30** + linha nova em `## Fronteira com o Plano` |
| **A-3** | M01 (`url('/app/'.$slug)`) só tem matador se CT-02 tiver **sujeito**, e o `04` não dizia qual arquivo | **já fechado no teste** — o gerador ficou em arquivo próprio, `Tenant::urlDoPainel()` | o sujeito do CT-02 está nomeado e citado: `app/Models/Tenant.php:urlDoPainel:164` (D-01 do `03`) |
| **A-4** | *(ver A-2 — mesma cláusula, o lado da hierarquia do schema)* | **já fechado no teste** | idem A-2 |
| **A-5** | 🔴 **bloqueava**: a varredura SFDIPOT inventariou `slug (alphaDash, unique, maxLength(120))` e R8 recolheu **duas** das três. O `unique` não tinha mutante nem cenário — e é ele que faz o endereço **identificar** a organização | **aberto nos dois lados** | 8ª linha dos `Exemplos` de CT-16 (`globex`) — *era "9ª" até 2026-09-22; o QA-12 removeu uma linha em outra seção e este número ficou para trás*, `Dado` com a segunda organização, mutante **M34**, `### A unicidade` em R8 e linha nova no `## Checklist de Taxonomia`. **Sondado: PASSA hoje** |
| **A-6** | `fronteiraDeRequest()` não estava no `## Setup Global`, e sem ela um cenário que atravessa dois painéis morre em **500** — o `SpotlightActionRegistry` é singleton e acumula as ações de todo painel visitado | **já fechado no teste** — presente entre as visitas em CT-09 e CT-10 | subseção `### fronteiraDeRequest() entre visitas que trocam de painel` no `## Setup Global`, com o motivo |
| **A-7** | M27 fala em "hub, widget, menu" e CT-19 alcança **um** lugar só. Um widget do `/admin` ou item do menu do usuário que renderizasse o link derrubaria a tela inteira em **500** sem tenancy, e CT-19 ficaria verde | **aberto nos dois lados** | **CT-21** (novo) + mutante **M36**. Varre `tests/Pest.php:telasDoKit:225`, que o `InventarioDeTelasTest` já obriga a manter completa |
| **A-8** | a lacuna 1 (`inativa × seguir`) foi declarada **inteira**, mas a célula tem **duas** metades e só uma é contestada: sem vínculo, `canAccessTenant()` nega independente de `ativo` e `getTenants()` também — as duas leituras **concordam** | **aberto nos dois lados** | **CT-22** (novo) + mutante **M33**; a lacuna 1 reduzida a **meia** célula. É a técnica do invariante das duas leituras, que o `04` já aplicava na lacuna 2 |
| **A-9** | CT-10 é cenário positivo **sem situação de partida** (nenhuma organização gravada) e o oráculo "a listagem **volta** a abrir" não afirmava a abertura **antes** — sem as duas medições, "voltou" não se distingue de "sempre abriu" | **já correto no teste**, errado no `04` | `Dado` com a organização + `E` com a abertura prévia; prosa explicando por que "volta" exige as duas pontas |
| **A-10** | CT-08 cobria **três** das quatro células do 2×2 persona × portão. Faltava o administrador **vinculado** que ainda toma 403 — a célula que documenta "**vínculo não basta**" | **aberto nos dois lados** | 3ª linha dos `Exemplos` de CT-08 + mutante **M32** + linha nova no `## Checklist de Taxonomia`. **Sondado: 403, como esperado** |
| **A-11** | o `## Assertion de HTML` prescrevia **uma** string adjacente (`href="…" target="_blank"`) para todos. Se CT-04 a usasse, CT-04 **seria** CT-07: o mutante "mesma aba" derrubaria os dois e o diagnóstico apontaria a regra errada | **já separado no teste**, junto no `04` | tabela de dois usos no `## Assertion de HTML`: CT-04/CT-05/CT-15 afirmam `href="$esperado"`; **só** CT-07 afirma a adjacência |
| **A-12** | o cabeçalho da matriz estado × operação declarava **10** exercitadas e **4** `n/a`. Conferido célula a célula: **11** e **3**. A soma dá 15 nos dois casos, e era isso que escondia o erro — e a legenda **afirma** ter sido conferida célula a célula | **aberto** (o `04` não tem contraparte no teste) | os dois números corrigidos, a soma explicitada, e o motivo do erro registrado. **Legenda é asserção** |
| **A-13** | CT-17 dizia `Então o resultado é "recusado"` sem afirmar o **não-efeito**. CT-16 faz a versão correta ("a organização gravada continua com o slug acme"); CT-17 roda no `CreateTenant`, onde o não-efeito é "nenhuma organização com esse slug existe" | **já correto no teste** (`expect(Tenant::where('slug', $slug)->exists())->toBe($gravado)`), faltava no `04` | um `E` a mais no CT-17 |

### Os dois `Então` cortados

Cortar é resultado de revisão tanto quanto acrescentar, e os dois estão registrados em
`### Cogitado e cortado` com o motivo:

- **C-1** — CT-02, 2º `Então` ("não contém nenhuma concatenação do slug com um caminho"): não é
  oráculo executável, e exigiria regex adivinhado sobre fonte, que `.ai/rules/testes.md` proíbe.
  ✅ **Fechado**: a linha saiu do teste, e o docblock de CT-02 registra o corte
  (`tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php`, seção *"A segunda asserção saiu"*). O que
  carrega o cenário é a asserção do literal, que continua lá.
- **C-2** — CT-05, 2º `Então` ("o endereço da inativa não é o da ativa"): **não pode falhar quando
  o primeiro passa**. Ficou no lugar a versão falsificável, que o teste já fazia.

### As divergências do implementador que este arquivo absorveu

| Desvio do `03` | O que o `04` dizia | O que o `04` diz agora |
|---|---|---|
| **D-02** | CT-14: "a listagem custa o mesmo com uma e com cinco organizações" — propriedade **falsa**: a listagem já crescia ~5 consultas por linha **antes** da feature (33 → 53, medido nas duas pontas do diff) | mesmo oráculo (invariância à cardinalidade), **sujeito** trocado para o que a feature possui: a resolução do endereço, **zero** consultas para uma e para cinco. O N+1 de terceiro virou **lacuna 3** |
| **D-03** | a linha `acento` de CT-16 estava do lado **errado**: `alpha_dash` é unicode-aware (`/\A[\pL\pM\pN_-]+\z/u`), e `organização` **grava** | o acento migrou para CT-18, lado **válido**; entraram `acme.painel` e `acme%2fpainel`, que o `\pL` recusa de fato e que cobrem o risco de segmento de URL |
| **novo** | ninguém havia afirmado que `Panel::getUrl()` **não** percent-encoda o segmento (o `e()` do helper escapa HTML, não URL) | vira **asserção** em CT-18 (linha `organização`), não lacuna: é falsificável em uma linha, tem mutante (**M35**), e é a premissa que CT-01/CT-03/CT-05/CT-15 já consomem ao comparar endereço com string |


---

## Adendo 2 — o CT-24, do ciclo 2 do quality gate (2026-09-22)

O QA-13 do ciclo 1 apontou que a **ficha** não sinalizava a nova aba — o formulário avisava por
`helperText`, a listagem por ícone, e ela não avisava nada. A correção entrou, e o **ciclo 2
apontou que ela entrou sem caso** (QA-19): apagar o `->icon()` deixava os 44 casos verdes.

```gherkin
    Cenário: [CT-24] As três superfícies avisam que o link troca de aba
      Dado uma organização cadastrada
      Então a ficha declara um ícone na entrada do painel
      E a listagem declara um ícone na coluna do painel
      E o formulário exibe "Abre em nova aba"
```

**O oráculo é o SINAL, não o destino.** Afirmar `target="_blank"` aqui mediria o que CT-04 e CT-15
já medem. Trocar de aba sem avisar é defeito de usabilidade **mesmo com o `href` perfeito**.

**E o oráculo muda de natureza conforme a superfície**, o que é a parte interessante:

| Superfície | Aviso | Oráculo | Por quê |
|---|---|---|---|
| ficha, listagem | ícone | **estrutural** — `getIcon()` da entrada e da coluna | marcação de ícone é detalhe de render do vendor; afirmar sobre o HTML seria frágil |
| formulário | prosa | **texto renderizado** — `assertSee()` | `helperText()` é açúcar sobre `belowContent()` (`vendor/filament/forms/src/Components/Concerns/HasHelperText.php:12`) e não tem getter. E o que importa ao usuário é a frase aparecer. A string é do kit, não do vendor |

**M38** — o aviso some de qualquer uma das três. Verificado por mutação em 2026-09-22: ficha sem
ícone, listagem sem ícone e formulário sem a frase deixam o caso vermelho, cada um por conta.

---

## Adendo — os quatro achados do `/code-review` (2026-09-21)

O step 7.5 rodou sobre o diff, por quem não implementou. Os quatro achados são registrados aqui com
o que os **fechou**, porque três deles eram defeitos de **afirmação** — texto que dizia proteger
algo que não protegia — e esse é o tipo que some sem registro.

| # | Achado | Verificado como | Destino |
|---|---|---|---|
| 1 | CT-21 guardava a string `?tenant={uuid}`, que **nenhuma implementação produz** | medido: `Panel::getUrl()` devolve `/app/{uuid}`, segmento de caminho | especificação + teste |
| 2 | `urlDoPainel()` devolvia um `/app/{uuid}` **morto** sem tenancy, sem guarda | mesma medição | ADR-03 + implementação + **CT-23** |
| 3 | comentário de `TenantsTable` dizia "21 queries", CHANGELOG e CT-14 dizem 33/53 | leitura cruzada | implementação (comentário) |
| 4 | CT-13 alegava fechar a armadilha do `dehydrate`; não fecha | `url_do_painel` não é coluna nem está no `$fillable` | especificação (docblock) |

### Por que o achado 1 é o mais grave dos quatro

Não pelo efeito — a tela está correta — e sim pelo **modo**. A asserção era de **ausência**, e
asserção de ausência não distingue *"o kit não renderiza isto"* de *"esta string não existe no
universo"*. Ela teria ficado verde para sempre, inclusive contra o mutante que o docblock dizia
matar, e o docblock **explicava em detalhe** por que estava certa — com citação de `arquivo:linha`
do vendor. Raciocínio plausível, citação real, conclusão errada.

**A correção estrutural** é o controle positivo, não a troca da string: CT-21 agora mede o gerador
e **exige** que ele produza o endereço morto antes de afirmar a ausência dele. Se o Filament mudar
a forma da URL, o caso fica vermelho no controle em vez de emudecer. Mesmo mecanismo de
`tests/Kit/OrdemDasCascadeLayersTest.php`, da `v0.37.1`.

### M36 — o mutante novo

| Mutante | Matador | Verificado |
|---|---|---|
| **M36** — a guarda `hasTenancy()` sai de `Tenant::urlDoPainel()` | **CT-23** | sim, por mutação em 2026-09-21: removida a guarda, CT-23 fica vermelho e CT-19/CT-21 seguem **verdes** |

A segunda metade dessa linha é o motivo de CT-23 existir separado de CT-21: **CT-21 não protege a
guarda.** Ele é verde com ou sem ela, porque sem tenancy o `TenantResource` está fechado e não há
tabela para renderizar. Sem a verificação por mutação, CT-23 pareceria redundante com CT-21 e teria
sido cortado.

### O achado que o próprio arquivo não previa: `toContain()` não recebe mensagem

Ao escrever o controle positivo, o segundo argumento de `expect()->toContain()` revelou-se **outra
agulha**, não a mensagem de falha. O controle nasceu vermelho e expôs que as asserções de ausência
do laço de CT-21 **já tinham o mesmo defeito**: `->not->toContain($endereco, $mensagem)` exigia que
a *mensagem* também estivesse ausente do HTML — o que é sempre verdade, e portanto inócuo.

As duas passaram para `assertStringContainsString` / `assertStringNotContainsString`, do PHPUnit,
que recebem mensagem de fato. Efeito medido no arquivo: **174 → 227 asserções** nas duas suítes,
sem cenário novo além de CT-23 — a diferença são asserções que antes eram engolidas. (O total das
duas suítes é hoje **229**, medido em 2026-09-21 depois de CT-02 ganhar controle positivo e CT-05
perder a linha cortada em C-2; o `227` era contagem parcial.)

**Rule gravada, e o step 9 desta candidata está fechado**: `.ai/rules/testes.md` →
*"`toContain()` do Pest não recebe mensagem — o 2º argumento é outra AGULHA"* (commit `9c6c494`,
neste branch), com `tests/Kit/ExpectativaVariadicaDoPestTest.php` de sentinela.
