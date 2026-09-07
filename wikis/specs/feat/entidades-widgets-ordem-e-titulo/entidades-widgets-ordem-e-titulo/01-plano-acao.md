# Plano de Ação — Listagem de entidades: visão geral no topo, tabela, demais widgets abaixo; título e breadcrumb como configurados

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: ajuste
- **Wiki ancestral**: `wikis/specs/main/insights-das-organizacoes/` — é ela que pôs os quatro widgets no cabeçalho de `ListTenants` (ADR-03 de lá explica por que vivem em `Resources/Tenants/Widgets/`). O rótulo minúsculo é anterior: nasceu com a tenancy (`wikis/specs/feature/multi-tenancy/organizacoes/`, commit `681be6d`, 2026-08-13).
- **Motivo**: reposicionar três dos quatro widgets para baixo da tabela; corrigir o título e o breadcrumb da listagem, que exibem o rótulo configurado em minúsculas desde que o Title Case do Filament foi desligado globalmente (commit `d5cf820`, 2026-08-15).
- **Toca infra compartilhada?**: não. Dois arquivos do próprio Resource, um teste da ancestral, um teste novo, docs.

> O tipo `ajuste` dispara **regressão** no quality gate contra os CT da ancestral: `tests/Tenancy/InsightsDasOrganizacoesTest.php` (CT-06 a CT-16) e `tests/Tenancy/FiltrosDeTabelaTenancyTest.php`, `tests/Tenancy/LightboxDaOrganizacaoTest.php` (usam `ListTenants`).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | visão geral no topo, acima da tabela | 1 | `OrganizacoesStats` fica sozinho em `getHeaderWidgets()` |
| RQ-02 | tabela imediatamente depois da visão geral | 1 | nenhum outro widget no cabeçalho; ordem header → tabela → footer é do vendor (`page/index.blade.php:headerWidgets:105, slot:109, footerWidgets:113`) |
| RQ-03 | os demais widgets abaixo da tabela | 1 | os três vão para `getFooterWidgets()` — atendida **sob premissa** (lista e ordem atuais, ver `00` → Ambiguidades) |
| RQ-04 | título da listagem como configurado | 2 | causa-raiz: `mb_strtolower()` em `getPluralModelLabel()` (ADR-01) |
| RQ-05 | breadcrumb como configurado | 2 | mesma causa: `HasBreadcrumbs::getBreadcrumb()` lê `getTitleCasePluralModelLabel()` |
| RQ-06 | menu continua como configurado | 2 (não regride), 4 (CT-02, segundo `Então`) | `getNavigationLabel()` já lê o `config()` cru e não é tocado |

## Objetivo

Na listagem do `TenantResource` (`/admin/{slug}`, "Entidades" na instalação do solicitante, "Organizações" por default), deixar só o widget de visão geral acima da tabela e mover os três widgets restantes (usuários únicos por organização, acessos por painel, atualizações recentes) para baixo dela. E fazer o título da página (o `<h1>` e o `<title>` da aba) e o primeiro item do breadcrumb exibirem o rótulo plural exatamente como foi configurado — hoje aparecem em minúsculas ("entidades", e "organizações" em qualquer instalação com o default) enquanto o menu está certo.

## Contexto

**Posição dos widgets.** `ListTenants::getHeaderWidgets()` (`app/Filament/Admin/Resources/Tenants/Pages/ListTenants.php:getHeaderWidgets():54-62`, linhas de ANTES desta entrega — hoje `:58-63`) declarava os quatro widgets como widgets de cabeçalho, e o Filament renderiza tudo o que está ali acima do conteúdo da página. O Filament já oferece o par: `Page::getFooterWidgets()` (`vendor/filament/filament/src/Pages/Page.php:getFooterWidgets():314-317`) renderiza abaixo do conteúdo. A ordem no template é fixa — `{{ $this->headerWidgets }}`, `{{ $slot }}` (a tabela), `{{ $this->footerWidgets }}` (`vendor/filament/filament/resources/views/components/page/index.blade.php:headerWidgets:105, slot:109, footerWidgets:113`). A doc do Filament 5 confirma: "`getHeaderWidgets()` returns an array of widgets to display above the page content, whereas `getFooterWidgets()` are displayed below" (search-docs, *Resources → Widgets → Displaying a widget on a resource page*). Colunas: as duas grades já são 2 por default (`vendor/filament/filament/src/Pages/Page.php:getHeaderWidgetsColumns():306-309` e `getFooterWidgetsColumns():356-359`), então o `getHeaderWidgetsColumns()` de `ListTenants` (`:64-67`) devolve o que já seria devolvido.

**Título e breadcrumb minúsculos — a cadeia completa, medida no vendor:**

1. `TenantResource::getModelLabel():68-71` e `getPluralModelLabel():73-76` aplicam `mb_strtolower()` ao rótulo configurado. Escrito em 2026-08-13 (`681be6d`), quando o Title Case do Filament ainda estava **ligado**: `HasLabels::getTitleCasePluralModelLabel()` (`vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php:getTitleCasePluralModelLabel():78-85`) aplicava `Str::ucwords()` e devolvia o título capitalizado de qualquer jeito; o minúsculo servia às frases em que o rótulo fica no meio ("Criar organização", "Nenhuma organização").
2. Em 2026-08-15 (`d5cf820`) o kit desligou o Title Case para **todos** os Resources — `Resource::titleCaseModelLabel(false)` em `app/Providers/Concerns/ConfiguraFilamentGlobal.php:titleCaseModelLabel():79` — porque `ucwords` capitaliza preposição ("Agentes **De** IA"). A partir daí `getTitleCasePluralModelLabel()` devolve o rótulo **como está** (`HasLabels.php:hasTitleCaseModelLabel():80-82`), isto é, minúsculo.
3. Quem consome `getTitleCasePluralModelLabel()`: o título da listagem (`vendor/filament/filament/src/Resources/Pages/ListRecords.php:getTitle():76-79`), que alimenta o `<h1>` e o `<title>` da aba (`vendor/filament/filament/resources/views/components/layout/base.blade.php:getTitle():30`); o primeiro item do breadcrumb de **todas** as páginas do Resource (`vendor/filament/filament/src/Resources/Resource/Concerns/HasBreadcrumbs.php:getBreadcrumb():9-12`, chamado em `vendor/filament/filament/src/Resources/Pages/Page.php:getBreadcrumb():186`); e o rótulo de navegação por default (`vendor/filament/filament/src/Resources/Resource/Concerns/HasNavigation.php:getNavigationLabel():140-143`).
4. O menu escapou porque `TenantResource::getNavigationLabel():78-81` sobrescreve o default e devolve o `config()` cru. É exatamente o sintoma do requisito: menu certo, título e breadcrumb errados.

Nenhum teste assere o título ou o breadcrumb da listagem (varredura de `tests/` por `getTitle|getBreadcrumbs|strtolower` sobre o `TenantResource`: zero ocorrências), e o único código do app que consome `TenantResource::getModelLabel()` é genérico: `app/Filament/Spotlight/AcoesDeCriacao.php:$rotulo:70` faz `ucfirst($resource::getModelLabel())` para todo Resource do painel, e o resultado fica igual ("Criar Organização" antes e depois) ou melhor (um rótulo composto deixa de virar "Criar Unidade de negócio"). `getPluralModelLabel()` não é consumido por nenhum código do app. *(alterado em 2026-09-07: o texto original dizia "varredura de `app/`: zero" — QA-01 do `06`.)* Quem lê o singular são só o vendor: `CreateRecord::getTitle():302-311` ("Criar :label" com `getTitleCaseModelLabel()`), `Page::getDefaultActionModelLabel():344-347` (rótulo das actions da tabela — no kit, os textos da tabela e da `CreateAction` são todos declarados à mão em `app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php:emptyStateHeading():66-67` e `ListTenants.php:label():32`), `vendor/filament/filament/src/GlobalSearch/Providers/DefaultGlobalSearchProvider.php:category():32` (categoria da busca global nativa, que o kit não usa — o Spotlight é do `wezlo`).

## Análise dos Arquivos Existentes

### `app/Filament/Admin/Resources/Tenants/Pages/ListTenants.php`

- `getHeaderWidgets():54-62` devolve os quatro widgets; `getHeaderWidgetsColumns():64-67` devolve 2. Docblock (`:40-53`) explica o `canView()` e o diretório dos widgets e aponta a ADR-03 da ancestral — continua válido, ganha uma frase sobre a divisão cabeçalho/rodapé.
- **Muda**: `getHeaderWidgets()` devolve só `OrganizacoesStats::class`; nasce `getFooterWidgets()` com os três restantes, na ordem atual; `getHeaderWidgetsColumns()` **sai** (é o default).

### `app/Filament/Admin/Resources/Tenants/TenantResource.php`

- `getModelLabel():68-71` e `getPluralModelLabel():73-76` com `mb_strtolower()`. Docblock da classe (`:32-38`, "Rótulo e URL configuráveis") diz que "tudo que o usuário lê (menu, títulos, mensagens)" sai do `config('kit.tenancy')` — correto, e é justamente isso que a correção restaura.
- **Muda**: os dois métodos devolvem `(string) config(...)` sem `mb_strtolower()`. O docblock ganha uma frase: o rótulo sai como configurado porque o Title Case do kit está desligado e título/breadcrumb leem este método sem capitalizar nada.
- `getNavigationLabel():78-81` **não muda** (RQ-06). Passa a ser redundante com o default do Filament (que agora devolveria o mesmo), mas é a única linha que protege o menu se alguém religar o Title Case; ponytail: manter, custo zero.

### `app/Filament/Admin/Resources/Tenants/Widgets/*.php`

- `OrganizacoesStats` (`$sort = 1`, `$columnSpan = 'full'`, heading "Visão geral"), `UsuariosUnicosPorOrganizacao` (`$sort = 2`, span 1), `AcessosPorPainel` (`$sort = 3`, span 1), `AtualizacoesDasOrganizacoes` (`$sort = 4`, span `'full'`). **Nenhum muda.** A grade do rodapé é 2 colunas por default, então o par lado a lado e a timeline em largura total ficam como hoje.
- Todos herdam `CanBeLazy` com `$isLazy = true` (`vendor/filament/support/src/Concerns/CanBeLazy.php:isLazy:9`): no HTML inicial da página cada widget é um placeholder Livewire, sem o heading. Isso importa para o teste de ordem (ver `04`, CT-01): o marcador de cada widget é o **nome do componente Livewire** dentro do `wire:snapshot` que o Livewire injeta na raiz de todo componente montado (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:snapshot:76`), presente também no placeholder.

### `tests/Tenancy/InsightsDasOrganizacoesTest.php` (ancestral)

- `[CT-12]` (`:296-310`) afirma que `getHeaderWidgets()` devolve os quatro. **Fica vermelho** com o passo 1 — é a regressão esperada, e o caso é atualizado (passo 3), não apagado: a regra dele ("a página declara os widgets; um widget escrito e nunca ligado passaria nos casos que montam o componente direto") continua valendo, só que agora em duas listas.
- Padrão de arranjo herdado: `usuarioComPapel('admin', null, …)` + `$this->actingAs()` + `noPainelBootado('admin')` + `Livewire::test(ListTenants::class)`; seeders `ShieldPermissionsSeeder` e `PapeisSeeder` no `beforeEach`; leitura de método protegido por closure vinculada (`(fn (): array => $this->getHeaderWidgets())->call($pagina)`).

### `wikis/specs/main/insights-das-organizacoes/04-casos-de-teste.md` (ancestral)

- Cenário `[CT-12]` (`:463-466`) e Índice (`:599`). Ganham a marca `*(alterado em 2026-09-06: wiki entidades-widgets-ordem-e-titulo — a visão geral fica no cabeçalho e os outros três no rodapé)*`. É o único ponto da ancestral que afirma algo que o código deixará de fazer.

### `docs/pt/recursos/multi-tenancy.md:Insights:45-52` e `docs/en/recursos/multi-tenancy.md:insights:45-52`

- "A listagem de organizações traz quatro widgets para operação global" + lista. Ganham a posição: a visão geral acima da tabela e os outros três abaixo dela. Uma frase em cada idioma.

### `CHANGELOG.md`

- Convenção do repositório: seção `## [Unreleased]` no topo até o release (medido em `2b59380`); o release a renomeia. Entrada em `### Alterado` (posição dos widgets) e `### Corrigido` (título/breadcrumb minúsculos desde a v0.16 — "organizações" em toda instalação com o default).

## Autorização

- **Policies / Gates / Middleware / Guards**: nenhuma mudança. A barreira da tela e dos widgets continua `TenantResource::canAccess()` (`ViewAny:Tenant` + `config('kit.tenancy.enabled')`), e `Page::getWidgetsSchemaComponents()` filtra por `canView()` tanto no cabeçalho quanto no rodapé (`vendor/filament/filament/src/Pages/Page.php:getWidgetsSchemaComponents():423`).

## Rotas

Nenhuma rota nova. A listagem continua em `/admin/{config('kit.tenancy.slug')}` (`TenantResource::getSlug():83-86`; `organizacoes` nos testes, forçado em `phpunit.xml:KIT_TENANCY_SLUG:68`).

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ListTenants` (listagem) | Filament `ListRecords` | `/admin/organizacoes` | leitura: vê a visão geral, a tabela e, abaixo, os três widgets; lê título e breadcrumb | Não |
| `CreateTenant`, `EditTenant`, `ViewTenant` (breadcrumb) | Filament | `/admin/organizacoes/create`, `/{record}/edit`, `/{record}` | leitura do primeiro item do breadcrumb (mesma fonte) | Não |

**Gate de CT-B**: nenhuma linha afirma algo que só o navegador prova. Ordem de blocos é posição no **HTML** (o template do Filament é linear: cabeçalho → conteúdo → rodapé), provável por `assertSeeHtmlInOrder` no teste de componente; título e breadcrumb são strings de método. Não há JS, tema, layout calculado nem acessibilidade em jogo. **Sem `05`**, motivo registrado no `04` → `## Sem CT-B`.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é alterada nesta wiki (só o breadcrumb, que é leitura). O gate não se aplica.

## Variáveis de Ambiente

Nenhuma nova. As existentes que entram nos testes: `KIT_TENANCY_LABEL_PLURAL` (`phpunit.xml:KIT_TENANCY_LABEL_PLURAL:67`, forçado em "Organizações") — os cenários que precisam de outro rótulo usam `config()->set('kit.tenancy.label_plural', …)`.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`InsightsDasOrganizacoesTest` CT-12** fica vermelho até o passo 3 — previsto e corrigido na mesma entrega.
- **Título "Criar Entidade"** na tela de criação (antes "Criar entidade"): consequência da causa-raiz, alinhada aos irmãos. Registrada em `00` → Ambiguidades.
- **Breadcrumb das quatro páginas** do Resource passa a "Entidades › …" (antes "entidades › …") — é o RQ-05 valendo também para criar/editar/ver, de graça.
- **Rótulo das actions da tabela** (`Page::getDefaultActionModelLabel()`): as `EditAction`/`ViewAction` da `TenantsTable` não têm texto com rótulo; `CreateAction` tem `->label('Novo registro')`; estado vazio é declarado à mão. Nada visível muda.
- **Busca global nativa do Filament** (`vendor/filament/filament/src/GlobalSearch/Providers/DefaultGlobalSearchProvider.php:category():32`): categoria passa de "entidades" a "Entidades" — o kit usa Spotlight, então a nativa não é exibida.
- **Arte** (`art/`): não há captura da listagem de organizações em `tests/BrowserTenancy/CapturaDeArteTest.php` (varredura por `organizac` em `app/Console/Commands/KitArte.php`: zero). Nada a recapturar.
- **`.ai/rules/filament.md` — "Page, Widget e Action novos nascem com a permissão consultada"**: nenhum widget novo; os quatro continuam sob `TenantResource::canAccess()`.

## Rollback

- Sem migration, sem dado. `git revert` do commit devolve os quatro widgets ao cabeçalho e o rótulo minúsculo.

## Dependências

- **Composer / NPM**: nenhuma. Filament `^5.6`, Livewire 4, Pest `^5.1` já instalados (`composer.json:34,91`).
- **Ambiente**: a worktree onde esta wiki foi escrita **não tem `vendor/`** — `composer install` (ou `--prefer-dist` a partir do `composer.lock`) antes de rodar qualquer teste.

## Riscos

- **Marcador do teste de ordem (CT-01)**: os widgets são lazy; o marcador é o nome Livewire do componente no `wire:snapshot`. Se o Livewire 4 escapar o nome de forma que a substring não apareça literal, a alternativa é `Livewire::test(ListTenants::class)->instance()` + as duas listas (já cobertas por CT-12′) — a ordem HTML fica então provada só pela citação do template do vendor. Decidir na implementação e registrar em `03` → Notas.
- **Rótulo configurado com espaço nas bordas ou vazio**: fora de escopo (Settings valida; default do `config()` cobre o vazio).

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php` tem `tenancy` (`:123`), `autenticacao` (`:132`), `configuracoes` (`:153`), `ai` (`:114`).

### Decisão

- **Sem log nesta feature**: os dois passos de código são declarativos (uma lista de classes; a remoção de uma função sobre uma string) — não há decisão de fluxo, falha nem efeito colateral a rastrear. Padrão `[Classe@Método]` não se aplica.

## Estrutura de Implementação

### 1. `ListTenants`: visão geral no cabeçalho, os três restantes no rodapé

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Admin/Resources/Tenants/Pages/ListTenants.php`
- `getHeaderWidgets()` passa a devolver `[OrganizacoesStats::class]`.
- Novo método, logo abaixo, com a mesma assinatura e o mesmo PHPDoc `@return array<int, class-string>`:

  ```php
  protected function getFooterWidgets(): array
  {
      return [
          UsuariosUnicosPorOrganizacao::class,
          AcessosPorPainel::class,
          AtualizacoesDasOrganizacoes::class,
      ];
  }
  ```

- **Remover** `getHeaderWidgetsColumns()`: devolve 2, que é o default de `Page::getHeaderWidgetsColumns()` (`vendor/filament/filament/src/Pages/Page.php:getHeaderWidgetsColumns():306-309`). Não declarar `getFooterWidgetsColumns()` pelo mesmo motivo (`:356-359`).
- Docblock de `getHeaderWidgets()` (`:40-53`): trocar "Os quatro widgets agregados desta tela" por uma frase que diga que a visão geral fica acima da tabela e os três de detalhe abaixo, e por quê (o pedido: a tabela é o que se opera; os detalhes se leem depois). Manter os parágrafos sobre `canView()` e sobre o diretório dos widgets — continuam verdadeiros para as duas listas.
- Atende RQ-01, RQ-02, RQ-03. Sem log (ver Channel de Log).

### 2. `TenantResource`: rótulo como configurado

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Admin/Resources/Tenants/TenantResource.php`
- `getModelLabel()` → `return (string) config('kit.tenancy.label', 'Organização');`
- `getPluralModelLabel()` → `return (string) config('kit.tenancy.label_plural', 'Organizações');`
- Docblock da classe, seção "Rótulo e URL configuráveis" (`:32-38`): acrescentar que o rótulo sai **como configurado**, sem `mb_strtolower()`, porque o kit desliga o Title Case do Filament (`ConfiguraFilamentGlobal::configuraFilamentGlobal()`), e sem ele `ListRecords::getTitle()` e `Resource::getBreadcrumb()` exibem o rótulo tal qual este método devolve. Uma linha de histórico: o minúsculo era de quando o Title Case recapitalizava por cima.
- `getNavigationLabel()` não muda.
- Atende RQ-04, RQ-05, RQ-06. Sem log.

### 3. Ancestral: CT-12 passa a afirmar as duas listas

> Skills: `pest-testing`

- **Path**: `tests/Tenancy/InsightsDasOrganizacoesTest.php:it():296-310`
- O caso `[CT-12]` continua com o mesmo ID e título ajustado ("declara a visão geral no cabeçalho e os três widgets de detalhe no rodapé"); asserção: `getHeaderWidgets()` é `[OrganizacoesStats::class]` **e** `getFooterWidgets()` é `[UsuariosUnicosPorOrganizacao::class, AcessosPorPainel::class, AtualizacoesDasOrganizacoes::class]`, ambos por closure vinculada como hoje. Igualdade estrita de array (`toBe`), que também fixa a ordem.
- **Path**: `wikis/specs/main/insights-das-organizacoes/04-casos-de-teste.md:declara:463-466` e `:599` — reescrever o `Então` do cenário CT-12 e a descrição no Índice com a marca `*(alterado em 2026-09-06: wiki entidades-widgets-ordem-e-titulo)*`.
- Atende a regressão exigida pelo tipo `ajuste`.

### 4. Teste novo: ordem no HTML, título, breadcrumb e menu

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php` (suíte `Tenancy`, grupo `kit` herdado do `tests/Pest.php:TenancyTestCase:78-81`; a tela exige `kit.tenancy.enabled`, que só é `true` em `Tests\TenancyTestCase`)
- Cenários: `[CT-01]` a `[CT-03]` do `04-casos-de-teste.md`, com o arranjo herdado da ancestral. Docblock do arquivo cita esta wiki.
- Helper de teste, se houver, fica **dentro do arquivo** enquanto só ele usar (regra `.ai/rules/testes.md` — helper cruzado vai para `tests/Pest.php`). Nenhum helper novo é previsto. *(alterado em 2026-09-07 na implementação: o marcador virou uma closure local sobre o FQCN — ver ADR-03 e `03` → Desvios do Plano.)*

### 5. Docs e CHANGELOG

> Skills: nenhuma

- `docs/pt/recursos/multi-tenancy.md:listagem:47` → "A listagem de organizações traz quatro widgets para operação global — a visão geral acima da tabela e os três de detalhe abaixo dela:"; `docs/en/recursos/multi-tenancy.md:list:47` equivalente em inglês.
- `CHANGELOG.md`: criar `## [Unreleased]` acima de `## [0.31.0]` com `### Alterado` (posição dos widgets na listagem de organizações) e `### Corrigido` (título da página e breadcrumb da listagem em minúsculas — "organizações" desde a v0.16, em toda instalação; o menu já estava certo).

### 6. Verificação

- `vendor/bin/pint --dirty --format agent`
- `vendor/bin/filacheck --fix`
- `php artisan test tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php tests/Tenancy/InsightsDasOrganizacoesTest.php --compact`
- `composer test:kit` (Kit + Tenancy em paralelo — é o comando de regressão do kit; `--tia` fica fora porque a rule `.ai/rules/testes-browser.md` mediu que sem PCOV ele não termina — a rule vence a skill)
- Pedir ao usuário a suíte completa (`composer test`) antes do PR.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo — `getFooterWidgets()` é API nativa; nenhum render hook, nenhuma view custom
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível — a correção do rótulo é **remover** uma função em duas linhas
> 5. Mínimo código que funciona — `getHeaderWidgetsColumns()` sai porque é o default
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.

## Mapeamentos

| Widget | Hoje | Depois | `$sort` | `$columnSpan` |
|---|---|---|---|---|
| `OrganizacoesStats` ("Visão geral") | cabeçalho | **cabeçalho** | 1 | `'full'` |
| `UsuariosUnicosPorOrganizacao` | cabeçalho | **rodapé** | 2 | 1 |
| `AcessosPorPainel` | cabeçalho | **rodapé** | 3 | 1 |
| `AtualizacoesDasOrganizacoes` | cabeçalho | **rodapé** | 4 | `'full'` |

| Superfície | Fonte no vendor | Hoje (rótulo "Entidades") | Depois |
|---|---|---|---|
| `<h1>` e `<title>` da listagem | `ListRecords::getTitle():78` → `getTitleCasePluralModelLabel()` | "entidades" | "Entidades" |
| Breadcrumb, 1º item, nas 4 páginas | `HasBreadcrumbs::getBreadcrumb():11` | "entidades" | "Entidades" |
| Menu | `TenantResource::getNavigationLabel():80` (config cru) | "Entidades" | "Entidades" |
| Título de `CreateTenant` | `CreateRecord::getTitle():309` → `getTitleCaseModelLabel()` | "Criar entidade" | "Criar Entidade" |

## Testes

> Ver `04-casos-de-teste.md` para a especificação completa dos cenários (derivados do `00`, não deste plano).
> Não há `05-casos-de-teste-browser.md` — motivo em `04` → `## Sem CT-B`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `php artisan test tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php tests/Tenancy/InsightsDasOrganizacoesTest.php --compact`
- [ ] `composer test:kit`
- [ ] Citações `arquivo:símbolo:linha` reverificadas (grep da skill)
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa; `[CT-12]` da ancestral atualizado nos dois lados

## Commits

- `♻️ refactor(organizacoes): visão geral no cabeçalho da listagem, demais widgets no rodapé`
- `🐛 fix(organizacoes): título e breadcrumb exibem o rótulo como configurado`
- `✅ test(organizacoes): ordem dos widgets, título, breadcrumb e menu da listagem`
- `📝 docs: posição dos widgets da listagem de organizações; CHANGELOG`
- `📝 docs(wiki): feat/entidades-widgets-ordem-e-titulo — requisito, plano, ADRs, casos e progresso`
