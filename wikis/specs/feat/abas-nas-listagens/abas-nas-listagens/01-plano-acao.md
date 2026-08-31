# Plano de Ação — Abas de recorte nas listagens de usuários e convites

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: `wikis/specs/feat/estudo-advanced-tables/estudo-advanced-tables/` — é o estudo que definiu este nível; ele declara que nenhum passo seria executado naquela branch e que a aprovação de um nível abre wiki nova. Esta é ela.
- **Motivo**: nível (a) aprovado.
- **Toca infra compartilhada?**: **sim, em grau baixo** — `AprovacaoDeCadastro` é trait usada pelos `UserResource` dos dois painéis, e `ConvitesTable` é a tabela de convites dos dois. A mudança nas duas é **extração** de closure existente, sem alterar comportamento. Regressão contra os testes de aprovação de cadastro e de convites.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Abas em `/admin/users` | 2 | |
| RQ-02 | Abas em `/app/users` | 2 | |
| RQ-03 | Badge com a contagem de pendentes | 2 | |
| RQ-04 | Badge e recorte respeitam o escopo do Resource | 2 | `static::getResource()::getEloquentQuery()` |
| RQ-05 | Recorte de pendentes num lugar só | 1 | `AprovacaoDeCadastro::recorteDePendentes()` |
| RQ-06 | O filtro do modal continua existindo | 1 | `filtroDePendentes()` passa a usar o recorte extraído |
| RQ-07 | Abas em `/admin/convites` e `/app/convites` | 4 | |
| RQ-08 | Recorte de convites num lugar só | 3 | `ConvitesTable::pendentes()`/`aceitos()` |
| RQ-09 | `/infra/ai-runs` sem abas | — | restrição: nada a fazer, e o passo 6 registra o porquê |
| RQ-10 | README com a convenção | 6 | |

## Objetivo

Dar às quatro listagens de pessoas e convites um recorte de **um clique** — a parte do estudo `advanced-tables` que o solicitante marcou como principal ("botões de filtros específicos"). O mecanismo é nativo do Filament (`ListRecords::getTabs()`), então não entra pacote, não entra tabela e não entra config: o que entra é a convenção de onde usar, e a extração das duas closures de recorte que hoje vivem dentro de um filtro cada.

## Contexto

Hoje, achar quem está esperando aprovação numa listagem de centenas exige abrir o modal de filtros e marcar "Somente pendentes de aprovação" (`AprovacaoDeCadastro::filtroDePendentes():75`). O mesmo vale para convites pendentes, atrás do `TernaryFilter` de `ConvitesTable.php:60`. O estudo mediu que abas nativas cobrem essa necessidade sem o pacote pago e sem alternativa gratuita — e que o resto do que o pacote oferece (visões salvas) é o nível (b), não aprovado.

## Análise dos Arquivos Existentes

### `app/Filament/Concerns/AprovacaoDeCadastro.php`

`filtroDePendentes()` (`:75`) devolve um `Filter` cuja `->query()` é `fn (Builder $query) => $query->where('aprovacao_pendente', true)`. A closure é o recorte; o `Filter` é a embalagem. A aba precisa do recorte sem a embalagem.

### `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php`

`TernaryFilter::make('pendente')` (`:60-66`) já tem as duas closures separadas em `queries(true:, false:, blank:)` — `whereNull('aceito_em')` e `whereNotNull('aceito_em')`. É a mesma extração.

### As quatro páginas de listagem

`Admin\...\ListUsers`, `App\...\ListUsers`, `Admin\...\ListConvites`, `App\...\ListConvites` — nenhuma sobrescreve `getTabs()`. Todas usam `HasResizableColumn` e definem `getHeaderActions()`.

## Autorização

Nenhuma mudança. A aba restringe a query; a policy do Resource continua decidindo o acesso, e o badge lê a mesma query do Resource (`getEloquentQuery()`), que já carrega o escopo de organização.

## Rotas

Nenhuma rota nova. O Filament aceita `?tab=` na URL da listagem (`ListRecords.php:39,54`), então `ListUsers::getUrl(['tab' => 'pendentes'])` já funciona sem passo nenhum.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `Admin\Users\ListUsers` | Filament | `/admin/users` | clica na aba "Pendentes de aprovação" | Não |
| `App\Users\ListUsers` | Filament | `/app/{tenant}/users` | idem | Não |
| `Admin\Convites\ListConvites` | Filament | `/admin/convites` | clica em "Pendentes" ou "Aceitos" | Não |
| `App\Convites\ListConvites` | Filament | `/app/{tenant}/convites` | idem | Não |

**Gate de CT-B**: a aba é `wire:click` que troca `activeTab` e recarrega a tabela pelo Livewire. O que se afirma é **qual registro a tabela mostra** e **qual número o badge traz** — teste de componente, com oráculo no banco. Nada de JS executado, console, tema, acessibilidade ou layout. **Sem CT-B.**

**Gate de tela de escrita**: nenhuma rota `create`/`edit` é tocada por esta wiki.

## Variáveis de Ambiente

Nenhuma. O estudo já havia recusado o `KIT_TABELA_VISOES_SALVAS` na auditoria Ponytail dele.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`registro-e-aprovacao`**: o filtro de pendentes passa a chamar a closure extraída. Comportamento idêntico; os testes daquela feature são a regressão que prova.
- **`convite-de-usuario` / `convite-em-massa`**: o `TernaryFilter` passa a chamar os dois métodos estáticos. Mesma coisa.
- **Contagem de navegação**: `BadgeContagemNavegacao` já existe e conta pendentes no menu. O badge da aba usa a mesma query, e não substitui aquele — um é o menu, outro é a aba.

## Rollback

Remover os `getTabs()` e inlinar de volta as duas closures. Sem migration, sem dado, sem config.

## Dependências

Nenhuma. `Filament\Schemas\Components\Tabs\Tab` já é usado em `RoleResource.php:290`.

## Riscos

- **Aba default muda a tela de quem já usa** — mitigado pela ordem: "Todos" é a primeira chave, e o Filament ativa a primeira quando não há `?tab=`.
- **`count()` por render** para o badge — aceito no volume das listagens de pessoas e convites do kit; o estudo já indicou `->deferBadge()` como saída se o volume crescer.
- **Extração que muda comportamento sem querer** — mitigado por rodar os testes de aprovação e de convites **antes** de acrescentar as abas, com a extração isolada no passo 1 e 3.

## Channel de Log da Feature

**Nenhum log e nenhum channel novo.** A aba não decide nada: ela recorta uma query a pedido explícito de um clique. Logar troca de aba produz ruído por interação e nenhum rastro útil — é a mesma conclusão que o estudo registrou ("Nível (a) não loga").

## Estrutura de Implementação

### 1. Extrair o recorte de pendentes (RQ-05, RQ-06)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Concerns/AprovacaoDeCadastro.php`
- Novo método `protected static function recorteDePendentes(Builder $query): Builder` com o corpo da closure de hoje.
- `filtroDePendentes()` passa a `->query(self::recorteDePendentes(...))`.
- **Verificação isolada**: rodar os testes de aprovação de cadastro **antes** do passo 2. Se a extração mudou algo, é aqui que aparece, e não misturado com a aba.

### 2. Abas nas duas listagens de usuários (RQ-01..RQ-04)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Users/Pages/ListUsers.php`, `app/Filament/App/Resources/Users/Pages/ListUsers.php`
- `getTabs(): array` com `todos` (sem modificador) e `pendentes` (`->modifyQueryUsing(AprovacaoDeCadastro::recorteDePendentes(...))`, `->icon(Heroicon::OutlinedClock)`, `->badge(...)`).
- A contagem do badge sai de `static::getResource()::getEloquentQuery()`, nunca de `User::query()` — `.ai/rules/filament.md` e RQ-04.
- `Tab` de `Filament\Schemas\Components\Tabs\Tab`.

### 3. Extrair o recorte de convites (RQ-08)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php`
- `public static function pendentes(Builder $query): Builder` e `aceitos(Builder $query): Builder`.
- O `TernaryFilter` passa a referenciá-los.

### 4. Abas nas duas listagens de convites (RQ-07)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Convites/Pages/ListConvites.php`, `app/Filament/App/Resources/Convites/Pages/ListConvites.php`
- `getTabs()` com `todos`, `pendentes` e `aceitos`.

### 5. Testes

- Ver `04-casos-de-teste.md`.

### 6. README (RQ-09, RQ-10)

- **Path**: `README.md`, seção de convenções do kit.
- Registrar: listagem com estados distintos ganha `getTabs()`; o filtro do modal é para **combinar**, a aba é para o recorte de **um clique**; a aba ativa **não** persiste na sessão (é nativo, e `?tab=` na URL é o jeito de linkar uma listagem já recortada); e por que `/infra/ai-runs` fica de fora (o `SelectFilter('status')` já está na tela).

## Filosofia de Implementação

> **Ponytail em `full`.** O que a escada já decidiu aqui:
> 1. Mecanismo **nativo** (`getTabs()`), não pacote — é o resultado do estudo ancestral.
> 2. **Extrair** as duas closures que já existem em vez de escrever query nova nas abas — a regra de recorte fica num lugar só, e a aba e o filtro não derivam.
> 3. Sem trait, sem helper compartilhado, sem config: quatro `getTabs()` diretos nas quatro páginas.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05-casos-de-teste-browser.md` — ver o gate na `## Superfície de UI`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`

## Commits

- `♻️ refactor(filament): recorte de pendentes e de convites extraído para método único`
- `✨ feat(filament): abas de recorte nas listagens de usuários e convites`
- `📝 docs(readme): convenção de abas em listagens com estados distintos`
