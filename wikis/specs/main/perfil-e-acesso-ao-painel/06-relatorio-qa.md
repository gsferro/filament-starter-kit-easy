# Relatório de QA — Perfil × permissão × acesso ao painel

> Requisito: **não existe `00-requisito.md`** · Plano: `01-plano-acao.md` · Casos: `04-casos-de-teste.md`
> Perfil de esforço: **completo** (domínio sensível — autorização de painel)
> Natureza da wiki: nova, sobre infra compartilhada · Regressão: sim (a wiki reescreve `canAccessPanel()`)
> Confrontado contra o código de `main` em `e196f20` (v0.18.2), não contra o que a wiki descreve.

> ⚠️ **ORÁCULO FRACO — sem `00-requisito.md`.** A wiki precede a `feature-wiki` 2.10.0 e
> **não** tem a seção "O que o usuário pediu, nas palavras dele" que as irmãs de `main/`
> têm (conferido: `grep -n -i 'palavras dele\|o usuário pediu\|pedido original'` no `01`
> volta vazio). O oráculo usado foi o `01-plano-acao.md`, que é **o que foi planejado, não
> o que foi pedido** — a dimensão A detecta cláusula do plano sem teste, mas **não** pode
> detectar pedido do usuário que nunca virou cláusula do plano. A única fala do usuário
> recuperável está indireta, em citação de terceira mão no `04` (CT-15: *"adicionar ao
> cadastro do usuário o tenant a que ele pertence, para evitar acesso indevido a outros
> dados"*).

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 1 · Minor: 3 · Cosmético: 1
- Ambiente: sem app servido (worktree isolado); Pest 5.0.5; sqlite `:memory:`; MCP não usado
- Suítes executadas: `--testsuite=Kit --filter=sonda`, `--testsuite=Tenancy --filter=sonda`
  (sondas efêmeras, apagadas), `--filter=RenomeacaoDePapel`, `--filter=AdminDaOrganizacao`

A fundação está entregue e é sólida: 15 dos 16 CT têm teste equivalente rodando verde. Os
achados são **de teste e de documento**, não de comportamento — as três sondas que
exercitaram os três CT sem teste mostraram o **código correto**.

## Achados

### QA-01 — CT-11 (a "falha silenciosa mais provável do plano") não tem teste nenhum · Major · destino 3

- **Dimensão**: A (omissão silenciosa) + K (adequação da suíte)
- **Relacionado a**: CT-11, passo 6/7d do PRD, `app/Filament/Admin/Resources/Roles/Pages/CreateRole.php`
- **Esperado**: o `04-casos-de-teste.md:333-364` define CT-11 e diz, literalmente, que a
  permission fantasma é *"a falha silenciosa mais provável de todo este plano"*.
- **Observado**: **nenhum teste da suíte instancia `CreateRole` ou `EditRole`.**
  `grep -rn "CreateRole\|EditRole" tests/` volta vazio. O passo 7d do PRD (acrescentar
  `'painel'` às duas listas nos dois arquivos) é o único ponto do plano cuja quebra não
  produz erro: sem ele o `afterCreate` do Shield cria uma permission chamada `app` e o
  `painel` nunca chega ao `save()` — papel novo nasce sem painel, e ninguém entra na tela.
- **Repro** (sonda efêmera, `tests/Kit/`, `Tests\TestCase`, seeders no `beforeEach`):
  1. `Filament::setCurrentPanel('admin')`, `actingAs(usuarioDoKit('master_global'))`
  2. `Livewire::test(CreateRole::class)->fillForm(['name'=>'suporte','guard_name'=>'web','painel'=>'app'])->call('create')`
  3. conferir `roles` e `permissions`
- **Evidência**: `papel gravado com painel app => true`, `permission fantasma "app" => false`.
  **O código está certo** (`CreateRole.php:28,34,37` e `EditRole.php:36,42,45` têm
  `'painel'` nas duas listas). O defeito é a **ausência do oráculo**: hoje nada impede um
  upgrade do Shield, ou um `shield:publish` refeito, de reverter os dois arquivos em
  silêncio.
- **Destino**: 3 (teste) — escrever CT-11 nos dois arquivos (`CreateRole` e `EditRole`),
  com a asserção negativa `Permission::where('name','app')->doesntExist()`, que é a única
  que acusa.
- **Ação exigida**: invocar a `feature-test-design` com o achado; a classe de lacuna é
  "efeito colateral de vendor não asserido" — mesma classe do CT-10, que existe.

### QA-02 — CT-14 e CT-15 sem teste; o `->required()` dos dois campos não tem oráculo · Minor · destino 3

- **Dimensão**: A + K
- **Relacionado a**: CT-14, CT-15, passo 7 do PRD
- **Esperado**: `04:422-477` — criar usuário sem papel é erro de formulário; com tenancy,
  sem organização também é.
- **Observado**: o código está correto nos dois casos, e nenhum teste o prova.
  `tests/Tenancy/TenancyTest.php:253` (`salva os papéis do usuário no painel admin`) usa
  `EditUser`, nunca `CreateUser`; nenhum teste da suíte instancia
  `App\Filament\Admin\Resources\Users\Pages\CreateUser`.
- **Repro**: duas sondas efêmeras com `Livewire::test(CreateUser::class)` (painel `admin`,
  `master_global`), uma sem `roles` e outra sem `tenants`.
- **Evidência**: sem `roles` → `erros: ['data.roles']`, usuário não criado. Sem `tenants`
  (suíte `Tenancy`) → `erros: ['data.tenants']`, usuário não criado. `UserResource.php:70`
  e `:130` têm o `->required()`.
- **Destino**: 3 — CT-14 e CT-15 valem sobretudo pela **cláusula do usuário** que o CT-15
  cita ("evitar acesso indevido a outros dados"): é a única cláusula de requisito
  rastreável desta wiki, e ela não tem teste.

### QA-03 — os dois arquivos de teste que a wiki nomeia não existem · Minor · destino 1

- **Dimensão**: A (rastreabilidade)
- **Relacionado a**: `01:1107-1111`, `04:498-516`
- **Esperado**: `tests/Kit/PerfilEAcessoTest.php` (13 CT) e
  `tests/Tenancy/PerfilEAcessoTenancyTest.php` (3 CT).
- **Observado**: **nenhum dos dois existe.** Os casos foram implementados dentro de
  `tests/Kit/PaineisTest.php` e `tests/Tenancy/TenancyTest.php` — decisão razoável (é onde
  os casos invertidos de sentido já viviam), mas a wiki nunca registrou a mudança, e o
  `03-progresso.md` também não. Consequência prática: quem for rodar a regressão desta
  wiki por ID de CT, como a própria `feature-quality-gate` manda, não acha os arquivos e
  conclui "feature sem teste".
- **Repro**: `ls tests/Kit/ | grep -i perfil` → vazio.
- **Destino**: 1 (especificação) — corrigir a coluna "Arquivo" do `04` e anotar no `03`.

### QA-04 — o plano diz 11 casos, o `04` tem 16 · Minor · destino 1

- **Dimensão**: A
- **Esperado / Observado**: `01:1107` — *"Onze casos"*; `04:498-516` — CT-01 a CT-16. A
  divergência é interna à wiki e não afeta código.
- **Destino**: 1.

### QA-05 — número medido em `.ai/rules/filament.md` está velho: 38 vs 59 permissions · Cosmético · destino 1

- **Dimensão**: D/K (documento que serve de oráculo a agentes)
- **Esperado / Observado**: a rule §"Resource, Page ou Widget de administração no painel
  `app`" afirma *"Medido no painel `app`: 38 permissions, 36 de Resource e 2 de Page"*.
  Medido agora: **59** (`Paineis::permissoes('app')->count()`), com 4 Resources no painel.
  A **instrução** da rule continua correta; só o número apodreceu.
- **Repro**: `php artisan tinker --execute 'echo App\Support\Paineis::permissoes("app")->count();'` → `59`
- **Destino**: 1 — número medido em prosa de rule ou vira asserção de teste, ou sai.

## Matriz de Rastreabilidade

Oráculo = `01-plano-acao.md` (fraco). Só as linhas com lacuna.

| CT | Cláusula | Passo PRD | Código | Teste que realmente existe | Veredito |
|----|----------|-----------|--------|----------------------------|----------|
| CT-01 | master_global nos três painéis | 3 | `User::canAccessPanel` | `PaineisTest` `deixa o master_global entrar…` | OK (arquivo ≠ o declarado) |
| CT-02 | papel abre só o painel que declara | 3 | idem | `PaineisTest` `recorta admin e infra por papel`, `dá 403 no painel errado` | OK |
| CT-03 | sem papel, 403 nos três | 3 | idem | `PaineisTest` `fecha os três painéis…` | OK |
| CT-04 | negativa vira log com motivo | 3 | `User.php` `Log::channel('autenticacao')` | `PaineisTest` `nega painel registrando o motivo no log` | OK |
| CT-05/06 | mapa e permissions por painel | 2, 5 | `App\Support\Paineis`, `ShieldPermissionsSeeder` | `PaineisTest` `gera permission para os três painéis…` | OK |
| CT-07 | matriz do papel = matriz do painel | 4 | `PapeisSeeder` | `PaineisTest` `recorta a matriz do papel pelo painel` | OK |
| CT-08 | painel nulo não é coringa | 3 | `User::canAccessPanel` | `PaineisTest` `não trata painel nulo como coringa` | OK |
| CT-09 | tela agrupa por painel | 6 | `Roles/RoleResource::getResourceEntitiesSchema` | `PaineisTest` `agrupa as permissões por painel…` | OK |
| CT-10 | Resource publicado é o registrado | 6a | idem | `PaineisTest` `registra o RoleResource publicado…` | OK |
| **CT-11** | **painel salva sem virar permission** | **7d** | `CreateRole.php:28,34,37` / `EditRole.php:36,42,45` | **— nenhum** | ❌ **QA-01** |
| CT-12/13 | papel `app` em qualquer org; papel de org não abre `/admin` | 3 | `User::canAccessPanel` | `TenancyTest` `exige papel no contexto global…` | OK |
| **CT-14** | **papel obrigatório ao criar usuário** | **7** | `Admin/…/UserResource.php:70` | **— nenhum** | ❌ **QA-02** |
| **CT-15** | **organização obrigatória ao criar usuário** | **7** | `Admin/…/UserResource.php:124-130` | **— nenhum** | ❌ **QA-02** |
| CT-16 | `app/Support` no `kit:update` | 10 | `KitUpdate.php:91` | `KitUpdateTest` (varredura da árvore) | OK |
| — | passo 11: rule de IA | 11 | `.ai/rules/filament.md` §31, §58, §64 | `QualidadeDeCodigoTest` (indireto) | OK |

Nenhum passo do PRD ficou sem código. Nenhum código novo ficou sem passo.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 3 achados; oráculo fraco declarado no cabeçalho |
| B | Fronteiras e dados | ✅ | painel nulo, contexto nulo e papel ausente têm caso |
| C | Matriz de permissão | ✅ | 5 papéis × 3 painéis cobertos por `PaineisTest` + `TenancyTest` |
| D | Observabilidade | ✅ | `autenticacao` com `[Classe@Método]`, `motivo`, `user_id`; sem PII em claro |
| E | Performance | ⏭️ | `temPapelOnde()` é um `exists()` por chamada; sem rota de listagem nova |
| F | UX de erro | ✅ | 403 nativo do Filament; `helperText` nos dois campos novos |
| G | Tema e cor | ⏭️ | a wiki não introduz view própria (o `RoleResource` publicado é cópia de vendor) |
| H | Acessibilidade | ⏭️ | sem componente novo; `tests/Browser/TemaEscuroTest.php` cobre os painéis |
| I | Segurança da superfície nova | ✅ | nenhuma rota nova; a fronteira nova é `canAccessPanel()`, com 4 casos |
| J | Regressão adjacente | ✅ | `PaineisTest` + `TenancyTest` + `AdminDaOrganizacaoTest` (19) verdes |
| K | Adequação da suíte | ⚠️ | ver QA-01/QA-02; `--mutate` **não** rodado (sem driver de cobertura no worktree) |

## Débitos Aceitos

- QA-03, QA-04, QA-05 (Minor/Cosmético): correção de documento, sem risco de produto.

## Suspeitas Não Confirmadas

- **`->unique()` de `users.email` como oráculo de existência entre organizações** — o
  achado foi confirmado, mas pertence à superfície de `admin-da-organizacao`; está
  registrado lá (QA-03 daquele relatório), não aqui.

## Não Verificado

- Dimensão K passo 2 (mutation score) — sem PCOV/Xdebug no worktree isolado.
- Dimensões G/H por screenshot — app não servido; Playwright MCP não usado.
- CT-09 no navegador — o `assertSee('Painel /admin')` do `PaineisTest` prova o texto no
  DOM, não que os três grupos estejam visíveis nos dois temas.
