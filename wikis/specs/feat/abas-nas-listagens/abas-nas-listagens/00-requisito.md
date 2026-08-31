# Requisito — Abas de recorte nas listagens de usuários e convites

## Fonte

- **Origem**: aprovação do **nível (a)** do estudo de viabilidade `wikis/specs/feat/estudo-advanced-tables/estudo-advanced-tables/`, pedida no chat pelo mantenedor do kit durante a varredura de wikis não implementadas.
- **Data**: 2026-08-30
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta — o nível (a) está escrito no `01-plano-acao.md` do estudo, com passos, paths e namespaces confirmados contra o vendor; a decisão do solicitante foi a escolha explícita "Nível (a) do advanced-tables" entre opções apresentadas.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

Do requisito ancestral (`estudo-advanced-tables/00-requisito.md`), a cláusula que este nível atende:

> | RQ-04 | A parte de "botões de filtros específicos" (filtros pré-definidos acionados por botão/aba) recebe atenção principal | "principalmente a parte onde se cria botões de filtros especificos" | funcional |

Do plano do estudo, o nível aprovado, verbatim:

> ### Nível (a) — Abas e botões de filtro nativos (RQ-04)
>
> **Passo 1 — Abas em `ListUsers` (admin e app)**
>
> - **Path**: `app/Filament/Admin/Resources/Users/Pages/ListUsers.php`, `app/Filament/App/Resources/Users/Pages/ListUsers.php`
> - Sobrescrever `getTabs(): array` devolvendo `['todos' => Tab::make('Todos'), 'pendentes' => Tab::make('Pendentes de aprovação')->icon(Heroicon::OutlinedClock)->badge(fn (): int => static::getResource()::getEloquentQuery()->where('aprovacao_pendente', true)->count())->modifyQueryUsing(fn (Builder $q): Builder => $q->where('aprovacao_pendente', true))]`. Namespace do `Tab`: `Filament\Schemas\Components\Tabs\Tab` (o mesmo que `RoleResource.php:290` já importa).
> - A query da aba **repete** a de `AprovacaoDeCadastro::filtroDePendentes()`; para não duplicar, extrair a closure para `AprovacaoDeCadastro::recorteDePendentes(Builder): Builder` e usá-la nos dois lugares. O filtro do modal continua existindo (o usuário pode combinar).
> - Query da badge sempre via `static::getResource()::getEloquentQuery()`, nunca `User::query()` — `.ai/rules/filament.md`, "Resource de model sem relação de posse com o tenant".
> - Botão **em outra tela** (card do hub, notificação) que abre esta listagem já recortada não precisa de passo: é `ListUsers::getUrl(['tab' => 'pendentes'])` ou `ListUsers::getUrl(['filters' => ['aprovacao_pendente' => ['isActive' => true]]])` — ambos confirmados em `ListRecords.php:39,54`. Se algum card vier a precisar, é uma linha no `->url()` dele.
> - **Logs**: nenhum.
>
> **Passo 2 — Abas em `ListConvites` (admin e app)**
>
> - **Path**: `app/Filament/Admin/Resources/Convites/Pages/ListConvites.php`, `app/Filament/App/Resources/Convites/Pages/ListConvites.php`
> - `getTabs()`: `todos`, `pendentes` (`whereNull('aceito_em')`), `aceitos` (`whereNotNull('aceito_em')`). Reaproveitar as closures do `TernaryFilter` de `ConvitesTable.php:60-66` movendo-as para métodos estáticos de `ConvitesTable` (`pendentes(Builder)`, `aceitos(Builder)`).
> - `/infra/ai-runs` fica **de fora de propósito**: o `SelectFilter('status')` já está na tela e uma aba por status o duplicaria para um usuário de infra. Quem quiser, é o mesmo padrão dos passos 1 e 2, com `->deferBadge()` por causa do volume.
> - **Logs**: nenhum.
>
> **Passo 3 — Testes**
>
> - Um caso por listagem em `tests/Kit/`: `livewire(ListUsers::class)->set('activeTab', 'pendentes')->assertCanSeeTableRecords($pendentes)->assertCanNotSeeTableRecords($aprovados)`; e um caso em `tests/Tenancy/` para o `ListUsers` do painel `app` conferindo que a aba não vaza usuário de outra organização.
>
> **Passo 4 — README**
>
> - Seção curta em "Convenções do kit": "Listagem com estados distintos ganha `getTabs()`; o filtro do modal é para combinação, a aba é para o recorte de um clique". Registrar que a aba ativa não persiste na sessão (nativo).

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A listagem de usuários do `/admin` tem aba "Todos" e aba "Pendentes de aprovação" | "Sobrescrever `getTabs(): array` devolvendo `['todos' => …, 'pendentes' => …]`" | funcional |
| RQ-02 | A mesma dupla de abas existe na listagem de usuários do `/app` | "`app/Filament/Admin/…/ListUsers.php`, `app/Filament/App/…/ListUsers.php`" | funcional |
| RQ-03 | A aba "Pendentes" mostra a contagem de pendentes num badge | "`->badge(fn (): int => …->where('aprovacao_pendente', true)->count())`" | funcional |
| RQ-04 | A contagem do badge e o recorte da aba respeitam o escopo do Resource (organização corrente no `/app`) | "Query da badge sempre via `static::getResource()::getEloquentQuery()`, nunca `User::query()`" | não-funcional |
| RQ-05 | O recorte de pendentes é definido em **um** lugar, compartilhado entre a aba e o filtro existente | "extrair a closure para `AprovacaoDeCadastro::recorteDePendentes(Builder): Builder` e usá-la nos dois lugares" | restrição |
| RQ-06 | O filtro "Somente pendentes de aprovação" continua existindo | "O filtro do modal continua existindo (o usuário pode combinar)" | restrição |
| RQ-07 | As listagens de convites (`/admin` e `/app`) têm abas "Todos", "Pendentes" e "Aceitos" | "`getTabs()`: `todos`, `pendentes` (`whereNull('aceito_em')`), `aceitos` (`whereNotNull('aceito_em')`)" | funcional |
| RQ-08 | O recorte de convites pendentes/aceitos é definido em **um** lugar, compartilhado entre as abas e o `TernaryFilter` existente | "Reaproveitar as closures do `TernaryFilter` … movendo-as para métodos estáticos de `ConvitesTable`" | restrição |
| RQ-09 | `/infra/ai-runs` **não** recebe abas | "`/infra/ai-runs` fica **de fora de propósito**" | restrição |
| RQ-10 | O README registra a convenção "estados distintos ganham `getTabs()`" e que a aba ativa não persiste na sessão | "Seção curta em 'Convenções do kit' … Registrar que a aba ativa não persiste na sessão (nativo)" | funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-01/RQ-07 — a aba "Todos" é a primeira e é a default?** **Assumido**: sim; `getTabs()` do Filament usa a primeira chave como aba ativa quando não há `?tab=` na URL, então "Todos" primeiro preserva o comportamento atual da tela para quem já usa. **Se negado**: abrir em "Pendentes" muda a tela de todo mundo que só queria a listagem.
- **RQ-03 — o badge conta pendentes mesmo quando a aba não está ativa?** **Assumido**: sim, é o ponto do badge (avisar sem clicar). O custo é uma `count()` por render da página. **Se negado**: `->deferBadge()`, que o próprio estudo já indica para volume alto.
- **RQ-02 — o `/app` sem tenancy (instalação single-tenant) tem a mesma aba?** **Assumido**: sim; o recorte é por `aprovacao_pendente`, não por organização, e o escopo do Resource já resolve o resto. **Se negado**: a aba fica condicionada a `config('kit.tenancy.enabled')`.

## Fora de Escopo (declarado)

- Níveis (b) e (c) do estudo — visões salvas por usuário e pacote publicável. Continuam não aprovados.
- Abas em qualquer outra listagem do kit, incluindo `/infra/ai-runs` (RQ-09).
- Persistência da aba ativa na sessão — o Filament não faz, e o kit não vai fazer.
