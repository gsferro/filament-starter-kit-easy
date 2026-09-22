# Plano de Ação — Link de acesso ao painel da organização

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: entrada nova em três schemas existentes do `TenantResource`
- **Toca infra compartilhada?**: **não** — só os três schemas do `TenantResource`. Nenhum seeder,
  middleware, `tests/Pest.php` ou config.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | Link clicável para `/app/{slug}` | 1, 2, 3, 4 | ADR-01 (gerador de URL), ADR-02 (sempre visível), ADR-04 (nova aba) |
| RQ-02 | No formulário | 2 | Só no `EditTenant` — decidido com o usuário |
| RQ-03 | Na visualização | 3 | `TenantInfolist` |
| RQ-04 | Na listagem, acesso direto | 4 | **Coluna** com URL clicável — decidido com o usuário |
| RQ-05 | URL derivada do slug | 1 | ADR-01 — pelo gerador, sem concatenar `/app/` |

## Objetivo

Hoje o administrador vê o slug da organização em três telas e **não tem como chegar ao painel dela**
sem montar a URL na barra de endereço. O slug já está ali, a rota já existe, e a informação de que
"o slug vira o endereço do painel" já está escrita na descrição da seção do formulário
(`TenantForm.php`) — falta só o link.

## Contexto

A tenancy é opt-in. Ligada, o painel `app` passa a responder em `/app/{slug}`
(`AppPanelProvider` → `->tenant(Tenant::class, slugAttribute: 'slug')`), e o `TenantResource` em
`/admin/organizacoes` é o CRUD dessas organizações.

## Análise dos Arquivos Existentes

### `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
`Section::make('Identificação')` com `description('O slug vira o endereço do painel de negócio:
/app/{slug}.')`, e dentro dela `TextInput::make('nome')` (com `afterStateUpdated` que gera o slug) e
`TextInput::make('slug')` (`->alphaDash()->unique()->maxLength(120)`).

**É exatamente "a parte que cadastra o nome e a slug"** do requisito.

### `app/Filament/Admin/Resources/Tenants/Schemas/TenantInfolist.php`
`Section::make('Identificação')` com `TextEntry::make('slug')->label('Identificador')->copyable()`.

### `app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php`
Colunas: logo, `nome` (searchable em nome+slug), `slug` (badge cinza), `ativo`, `users_count`,
`created_at`. Em `recordActions()` há um comentário que **importa para esta feature**: *"Sem
`->url()` nos dois: com as páginas `view` e `edit` registradas, o `Page::getDefaultActionUrl()`
resolve a URL sozinho"*. O nosso caso é o oposto — a URL é **externa ao resource**, então ela é
explícita.

### `app/Models/Tenant.php`
Não declara `getRouteKeyName()`; a chave de rota do tenant vem do `slugAttribute: 'slug'` do painel.

## Autorização

**Nenhuma mudança.** Ver ADR-02: o link não autoriza, ele navega. Os dois portões existentes
(`canAccessPanel`, `canAccessTenant`) continuam decidindo, e o 403 é comportamento declarado.

## Rotas

**Nenhuma rota nova.** A feature consome a rota que o `->tenant()` já registra.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `TenantForm` (via `EditTenant`) | Filament | `/admin/organizacoes/{record}/edit` | vê e clica no link | **Não** |
| `TenantInfolist` (via `ViewTenant`) | Filament | `/admin/organizacoes/{record}` | vê e clica no link | **Não** |
| `TenantsTable` (via `ListTenants`) | Filament | `/admin/organizacoes` | vê a coluna e clica | **Não** |

**Gate de CT-B**: nenhuma das três afirma sobre algo que **só o navegador prova**. O que a feature
entrega é um `<a href>` com o endereço certo, presente ou ausente conforme a tela — isso é
**asserção sobre HTML renderizado por componente Livewire**, e roda em milissegundos no `04`.

**Consequência: não haverá `05-casos-de-teste-browser.md`.** O único aspecto de navegador seria
"abre em nova aba", que é o atributo `target="_blank"` no HTML — afirmável por componente.

**Gate de tela de escrita**: `EditTenant` é rota de escrita. O `04` **já precisa** de cenário de
gravação por componente — e ele existe hoje; esta feature não pode quebrá-lo (o link é uma entrada
de schema, não um campo, então não entra no `dehydrate`). Há CT para isso.

## Variáveis de Ambiente

**Nenhuma nova.** `config('kit.tenancy.enabled')` já existe e já governa o resource (ADR-03).

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | **o mesmo de hoje** — nenhuma requisição nova |
| O que é adiado? | nada |
| O que é memoizado por request? | nada novo |
| Custo | **zero query nova**. O `slug` já vem no registro que o resource carrega; na tabela ele já é coluna selecionada |

> Este bloco é curto porque a feature é declarativa. O ponto que ele **prova** é que a coluna nova
> da tabela **não** introduz N+1: `slug` está no próprio registro, não numa relação.

## Impacto em Features Existentes

- **`TenantsTable`**: uma coluna nova. Largura da tabela cresce
- **`TenantForm`**: uma entrada nova na seção `Identificação` — **não** é campo, não entra no
  `dehydrate`, não afeta a gravação
- **Testes existentes do tenant**: `tests/Tenancy/**` e os CTs do `TenantResource`. Conferido na
  revisão profunda: **nenhum** assere contagem de colunas
- **Citação de terceiro que o diff desloca** *(achado R2 da revisão profunda)*:
  `tests/Tenancy/FiltrosDeTabelaTenancyTest.php:9` cita `TenantsTable.php:55`, que hoje é o
  `->filters([`. A coluna nova entra no `->columns([…])`, que fecha na linha 53 — **inserir a coluna
  desloca a 55 e invalida a citação de um teste que não é desta feature**. A citação precisa ser
  atualizada **no mesmo commit** da coluna, senão a rule `specs.md` fica violada por um arquivo que
  o diff nem abriria

## Rollback

Sem migration. `git revert`. A feature é aditiva: remover volta ao estado anterior.

## Dependências

**Nenhuma nova.**

## Riscos

| Risco | Mitigação |
|---|---|
| Concatenar `/app/` e quebrar sob subdiretório | ADR-01 — gerador de URL; CT afirma que a string `/app/` não aparece no código da feature |
| Slug com `../` ou `?` corromper a URL | `->alphaDash()` já existe; CT afirma a **dependência** dela (ver `## Superfície Livewire` do `02`) |
| Link no `CreateTenant` → 404 | Só no `EditTenant`; CT de ausência no create |
| ~~Teste existente contar colunas da tabela~~ | **Descartado**: conferido na revisão profunda, nenhum teste conta colunas |
| **Citação de terceiro deslocada pela coluna nova** | Achado R2 — atualizar `FiltrosDeTabelaTenancyTest.php:9` no mesmo commit; conferência de citações na Verificação Final |
| Gerador de URL falhar com tenancy desligada | ADR-03; CT |

## Channel de Log da Feature

**Nenhum log novo, nenhum channel novo.**

A feature **não executa lógica**: ela renderiza um `<a href>`. Logar renderização de link seria
ruído — e o caminho que **importa** já loga: quem clica sem acesso cai em
`canAccessTenant()`, que registra `[User@canAccessTenant] Acesso a tenant negado … motivo:
sem_vinculo` no channel `tenancy`. Esse log é o rastro do 403 da ADR-02, e **já existe**.

> Registrado explicitamente porque o template da skill pede channel por feature. Aqui a resposta é
> "não se aplica, e o rastro que interessa já está coberto" — não "esqueci".

## Estrutura de Implementação

### 1. O gerador da URL — um lugar só

> Skills: `ponytail`, `laravel-best-practices`

- Um único ponto que devolve a URL do painel da organização, consumido pelas três superfícies.
- **Cria, não reaproveita** — e isso foi verificado, não suposto: `grep -rn "getTenantUrl|tenantUrl"
  app/` devolve **zero**, e os únicos hits de `filament.app` são nomes de rota em contexto não
  relacionado. *(alterado em 2026-09-21: o texto dizia "reaproveitar antes de criar"; a revisão
  profunda R1 mostrou que não há o que reaproveitar.)*
- **A string `/app/` não aparece** (ADR-01)

### 2. `TenantForm` — no `EditTenant`, dentro da seção `Identificação`

> Skills: `laravel-best-practices`

- Entrada **não-campo** na `Section::make('Identificação')`, junto de nome e slug (RQ-02)
- Visível só quando o registro existe (decidido com o usuário)
- Nova aba (ADR-04)
- **Não** pode entrar no estado do formulário — nada de `TextInput` com `->disabled()`, que viria
  no `dehydrate`

### 3. `TenantInfolist` — na seção `Identificação`

> Skills: `laravel-best-practices`

- Junto do `TextEntry::make('slug')` que já é `->copyable()`
- Nova aba (ADR-04)

### 4. `TenantsTable` — coluna com a URL clicável

> Skills: `laravel-best-practices`

- **Coluna**, não ação (decidido com o usuário)
- A URL visível, clicável, nova aba
- **Sem query nova** — o `slug` já vem no registro

### 5. Documentação

> Skills: —

- `docs/{pt,en}/recursos/multi-tenancy.md` — a frase que descreve a tela de organizações
- **Links internos relativos** (`[CT-45]` de `SiteDeDocumentacaoTest` reprova caminho absoluto)
- `CHANGELOG.md` → `## [Unreleased]` → `### Adicionado`

## Filosofia de Implementação

> **Ponytail em modo `full`.** A feature é três entradas declarativas e um gerador de URL. Nada de
> classe nova, nada de trait, nada de componente Blade. Se a primeira versão tiver mais de ~30
> linhas somadas, algo foi inventado.

## Testes

Ver `04-casos-de-teste.md`. **Sem `05-casos-de-teste-browser.md`** — ver o gate na
`## Superfície de UI`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix` — **obrigatório**, toca `app/Filament`
- [ ] `php artisan test --compact` dos CTs da feature
- [ ] Testes existentes do tenant (`tests/Tenancy/**` + `TenantResource`)
- [ ] `composer test:kit`
- [ ] **Custo medido** — contagem de queries da listagem antes e depois, contra o `## Modelo de Execução`
- [ ] `/code-review` no diff (step 7.5)
- [ ] `feature-quality-gate` (step 8)

## Commits

- `:memo: docs(wiki): requisito do link de acesso ao painel da organizacao` *(feito)*
- `:memo: docs(wiki): ADRs e plano do link do painel`
- `:white_check_mark: docs(wiki): casos de teste do link do painel`
- `:sparkles: feat(tenancy): link de acesso ao painel da organizacao`
- `:memo: docs(tenancy): o link de acesso na documentacao`
