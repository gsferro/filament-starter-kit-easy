# Decisões Arquiteturais — Estudo de viabilidade: Advanced Tables e alternativas

> Estudo, não implementação (RQ-05). As ADRs abaixo registram o que foi **concluído** e o que fica
> **proposto** para quando um nível do `01-plano-acao.md` for aprovado. A recomendação final é a ADR-06.

## ADR-01: O pacote pago não pode ser dependência do kit, independentemente do preço

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

O kit é um skeleton `composer create-project`. Toda dependência do `composer.json` é herdada por cada projeto
que nasce dele. O Advanced Tables é distribuído por repositório Composer privado com auth http-basic (e-mail +
chave de licença) e a licença Single cobre 1 domínio; nenhuma das três licenças permite redistribuir o código
(`docs.advancedtables.com/v5/license`).

### Decisão

O pago fica fora do `composer.json` do kit em qualquer cenário. Ele pode ser recomendado no README como
"o projeto que precisar disso compra e instala" — a documentação de instalação é pública.

### Alternativas Consideradas

1. Comprar licença Unlimited e embarcar — preço não é público e a licença continua proibindo redistribuição; quem clonar o kit não teria a chave, e o `composer install` falharia.
2. Deixar o `require` comentado no `composer.json` com instruções — ruído em arquivo de máquina; o README já faz esse papel.

### Consequências

- **Positivas**: nenhum custo de licença no kit; nenhum passo de autenticação no `kit:install`.
- **Negativas**: quem quiser a experiência completa do pago instala à parte (€79 por projeto).
- **Riscos**: nenhum.

### Referências

- `01-plano-acao.md` → "O que o Advanced Tables entrega"
- `CLAUDE.md` → "Do not change the application's dependencies without approval"

---

## ADR-02: "Botões de filtros específicos" é `getTabs()` nativo, não pacote

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

O solicitante destacou a criação de botões que aplicam um recorte pré-definido (RQ-04). Três mecanismos foram
confrontados no vendor: abas de listagem, filtros na query string e presets em pacote de terceiro.

### Decisão

Usar `getTabs()` do `HasTabs` (`vendor/filament/filament/src/Resources/Concerns/HasTabs.php:31-84`), com
`Tab::modifyQueryUsing()` (`vendor/filament/schemas/src/Components/Tabs/Tab.php:93`), `->badge()` e `->icon()`.
Quando o botão mora **em outra tela** (card do hub, notificação), o link leva `?tab=` ou `?filters[...]` —
`ListRecords` registra `#[Url(as: 'filters')] $tableFilters` e `#[Url(as: 'tab')] $activeTab`
(`vendor/filament/filament/src/Resources/Pages/ListRecords.php:39-55`).

Dois detalhes do vendor sustentam a decisão e precisam constar porque não são óbvios:

- **URL vence sessão.** `InteractsWithTable::bootedInteractsWithTable()` só lê a sessão quando `$tableFilters === null`
  (`vendor/filament/tables/src/Concerns/InteractsWithTable.php:64-73`). Com `persistir_filtros` ligado no kit, o botão
  continua aplicando o recorte dele.
- **URL funciona com `deferFilters()`.** O form é preenchido e, se os filtros são diferidos, `tableFilters` recebe
  `tableDeferredFilters` (`InteractsWithTable.php:81-85`). O kit usa `deferFilters()` globalmente; sem esse trecho o
  link preencheria o modal e não filtraria.

### Alternativas Consideradas

1. `kingmaker/filament-filter-sets` — faz o mesmo como dropdown, substitui os filtros atuais em vez de mesclar, 1 star, um release. Dependência para o que uma sobrescrita de método entrega.
2. `shkubu18/filament-widget-tabs` — abas como widgets com contador, mas exige tema Tailwind customizado; o kit não tem tema custom e não quer um por isso.
3. `Filter::toggle()` (`vendor/filament/tables/src/Filters/Filter.php:32`) — cosmético: o toggle continua dentro do modal, não vira botão na tela.
4. `Action` no `headerActions()` da tabela que seta `$this->tableFilters` — funciona, mas é reimplementar a aba com um botão que não mostra qual recorte está ativo. Fica para o nível (b), onde o "recorte" é uma view salva e a aba nativa não serve.

### Consequências

- **Positivas**: zero dependência; API existe desde o Filament 3 e é coberta pela doc oficial (`03-resources/02-listing-records.md`, "Using tabs to filter the records"); `?tab=` é linkável.
- **Negativas**: a aba ativa **não persiste na sessão** — `HasTabs::loadDefaultActiveTab()` cai no `getDefaultActiveTab()` (`HasTabs.php:19-25, 46-49`); ao voltar à tela o usuário vê a primeira aba. Documentar, não contornar.
- **Riscos**: badge com `count()` por aba é uma query por render; usar `->deferBadge()` (`Tab.php:141`) onde o volume incomoda.

### Referências

- `01-plano-acao.md` → nível (a), passos 1–2
- `app/Filament/Concerns/AprovacaoDeCadastro.php:90-95` — a query que vira a primeira aba
- `app/Filament/Admin/Resources/Roles/RoleResource.php:290` — o kit já importa `Filament\Schemas\Components\Tabs\Tab`

---

## ADR-03: Nenhum pacote gratuito compatível merece adoção hoje

**Status**: Aceita
**Data**: 2026-08-26

### Contexto

A varredura (RQ-02) encontrou 14 candidatos. Filtrados por `filament/* ^5` **e** `laravel ^13` no `composer.json`,
sobram cinco; filtrados por "faz view salva por usuário", sobram dois: `ymsoft/filament-table-presets` (17 stars,
1.0.1 em mar/2026, nada desde então) e `wotz/filament-table-filter-presets` (0 stars, v0.5.0, PHP `^8.3`, dependência
extra da própria Wotz). `kisame76/filament-db-table-state` (2 stars, jun/2026) faz persistência cross-device do
estado bruto, não views.

### Decisão

Não adotar nenhum agora. O critério do kit para pacote (`wikis/pacotes-ranking.md`, "Critério do ranking") pesa
confiança no mantenedor e risco; todos os compatíveis têm mantenedor único, menos de 20 stars e menos de seis meses
de vida. Se um projeto pedir views salvas antes de o kit ter o nível (b), `ymsoft/filament-table-presets` é o
primeiro a testar — está registrado em `wikis/pacotes-candidatos.md` (linha "Table Presets / DB Table State") e
esta ADR é o detalhamento daquela linha.

`ableaura/filament-advanced-tables` fica **descartado por licença, não só por versão**: 4 commits no mesmo dia,
descrição copiada do marketing do pago, sem disclaimer de origem. Adotar código com essa procedência expõe o kit
e todo projeto derivado.

### Alternativas Consideradas

1. Adotar `ymsoft/filament-table-presets` já — cobre o nível (b) inteiro sem escrever código, mas a aposta é num repositório de uma pessoa parado há cinco meses; se ele não acompanhar o próximo minor do Filament, o kit herda o fork. Adiado, não recusado.
2. Adotar `kisame76/filament-db-table-state` para persistência entre dispositivos — o kit não tem essa demanda; a sessão já resolve o caso "voltei para a tela".
3. Portar o `kozsuper/filament-table-views` (Filament 3) — o upstream real é o AureusERP (MIT, ativo); se o nível (c) for aprovado, é referência de leitura, não de código.

### Consequências

- **Positivas**: `composer.json` intocado; nenhuma migration de vendor entra no kit.
- **Negativas**: quem precisar de views salvas hoje instala por conta própria.
- **Riscos**: a varredura é por termos; um plugin com nome fora de table/filter/view/preset/saved/column pode ter escapado. Repetir a busca antes de aprovar o nível (b).

### Referências

- `01-plano-acao.md` → "Alternativas gratuitas encontradas"
- `wikis/pacotes-candidatos.md:454`

---

## ADR-04: Views salvas (nível b) nascem em tabela própria, não na `table_settings` do asmit

**Status**: Proposta (vale se o nível b for aprovado)
**Data**: 2026-08-26

### Contexto

`asmit/resized-column` já traz um model `Asmit\ResizedColumn\Models\TableSetting` com `user_id`, `resource` e
`styles` (`vendor/asmit/resized-column/src/Models/TableSetting.php`) e a opção `preserveOnDB()` — ou seja, o kit já
carrega um "estado de tabela por usuário em banco", hoje restrito à largura das colunas e desligado (os três painéis
usam `->preserveOnSession()`, `AdminPanelProvider.php:238-239`).

### Decisão

Tabela própria `visoes_de_tabela` (`user_id`, `tenant_id` nullable, `tabela`, `nome`, `estado` json, `padrao`), com
`BelongsToTenant` e `Auditable`. Ícone, cor e favorita ficam para o nível (c) — são a Favorites Bar do pago. A identificação da tela é o FQCN da `ListRecords`, o
mesmo que o Filament usa para compor as chaves de sessão.

### Alternativas Consideradas

1. Estender `table_settings` do asmit com colunas novas — a migration é do vendor e muda sem aviso; um `composer update` pode recriá-la. Recusado.
2. Guardar o JSON no `spatie/laravel-settings` — settings são globais por definição; "por usuário" não cabe no modelo.
3. Guardar na sessão apenas, sem banco — é o que já existe (`persist*InSession`), e o problema que a view resolve é justamente sobreviver ao logout e ter mais de uma.

### Consequências

- **Positivas**: modelo do kit, auditado, escopado por tenant, testável em par (`tests/Kit` + `tests/Tenancy`) como manda `.ai/rules/filament.md`.
- **Negativas**: mais uma migration e um model no kit; permissões `Create/Update/Delete:VisaoDeTabela` via `custom_permissions` do Shield e recorte manual em `PapeisSeeder::paineisDasPermissoesCustomizadas()`.
- **Riscos**: o JSON cristaliza o formato interno do estado de filtro (`['isActive' => bool]` em `Filter.php:18,74`; `['value' => …]`/`['values' => […]]` em `SelectFilter.php:109-155`). Mitigação: aplicar sempre por `getTableFiltersForm()->fill()`, o mesmo caminho da URL (`InteractsWithTable.php:81`) — se o link continuar funcionando, a view continua.

### Referências

- `01-plano-acao.md` → passos 5–7
- `.ai/rules/filament.md` → "Resource de model sem relação de posse com o tenant"; "Page, Widget e Action novos nascem com a permissão consultada"

---

## ADR-05: A barra de views entra por trait na `ListRecords`, nunca por `Table::configureUsing()`

**Status**: Proposta (vale para os níveis b e c)
**Data**: 2026-08-26

### Contexto

O kit configura toda tabela em `ConfiguraFilamentGlobal::configuraTable()` via `Table::configureUsing()`. O
comentário daquele método registra que `filtersTriggerAction()` e irmãs, quando globais, atingem tabelas **sem**
filtro (as dos plugins de terceiros) e derrubam a página com `LogicException: Action ... must have a unique name`
— oito telas do `/infra` caíram por isso.

### Decisão

A UI de views (Actions "Salvar visão", lista de visões, futura barra de favoritos) é registrada por trait
(`App\Filament\Concerns\TemVisoesSalvas`) em cada `ListRecords` que a quiser, no `getHeaderActions()` — o mesmo
modelo do `Asmit\ResizedColumn\HasResizableColumn`, que todas as dez `List*.php` do kit já usam. Componentes
Filament (`Action`, `ActionGroup`) em vez de Blade solto, para não exigir tema customizado como o pago exige.

### Alternativas Consideradas

1. `Table::configureUsing()` com `headerActions()` — atinge as tabelas de vendor, onde não há `ListRecords` do kit para autorizar nem policy para consultar; e reproduz o defeito das oito telas.
2. Render hook global `RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE` (`PanelsRenderHook.php:103`) com Blade — funciona sem trait, mas o Blade precisa de CSS próprio (o pago publica `plugin.css` e exige `@source` no tema) e fica fora do sistema de autorização de Actions que `tests/Kit/PermissoesDeAcoesTest.php` inventaria.

### Consequências

- **Positivas**: opt-in por tela; autorização por `->authorize()`; entra no inventário de Actions automaticamente.
- **Negativas**: cada listagem nova precisa lembrar do `use`; o README registra a convenção como já faz para o `HasResizableColumn`.
- **Riscos**: nenhum novo.

### Referências

- `app/Providers/Concerns/ConfiguraFilamentGlobal.php` → comentário sobre `filtersTriggerAction()`
- `app/Filament/App/Resources/Users/Pages/ListUsers.php`

---

## ADR-06: Recomendação — executar o nível (a) agora; (b) sob demanda; (c) não

**Status**: Aceita (recomendação do estudo; a execução depende do mantenedor)
**Data**: 2026-08-26

### Contexto

O requisito pede viabilidade e custo (RQ-06). As três perguntas do estudo têm resposta: o pago entrega quinze
funcionalidades, das quais o nativo + kit já cobrem persistência, reordenação de colunas e query builder; nenhum
gratuito compatível é adotável hoje (ADR-03); e "botões de filtro" é sobrescrita de método (ADR-02).

### Decisão

| Nível | Recomendação | Custo | Motivo |
|---|---|---|---|
| (a) abas e links com filtro na URL | **fazer**, próxima release | 1 a 2 dias | é o que o solicitante destacou; API nativa; zero dependência; cinco telas de listagem (três Resources) já têm o recorte escrito como filtro |
| (b) views salvas por usuário | **adiar** até um projeto real pedir | 4 a 6 dias | o kit não tem hoje uma tela onde o mesmo operador repete o mesmo recorte todo dia; construir antes é a abstração sem cliente |
| (c) pacote gratuito publicável | **não fazer** como projeto do kit | 15 a 25 dias + 2 a 4 dias/mês | o pago custa €79 por projeto (menos de meio dia de dev) e lança semanalmente; paridade é custo recorrente que o kit não tem por que carregar. Se o autor quiser publicar pela marca, é decisão de portfólio, separada do kit, e o nível (b) é o ponto de partida |

Enquanto (b) não existe, o README aponta: "views salvas por usuário → comprar Advanced Tables (€79) ou testar
`ymsoft/filament-table-presets`".

### Alternativas Consideradas

1. Comprar o pago para o kit — inviável por licença (ADR-01).
2. Fazer (b) já, porque é barato — 4 a 6 dias sem cliente é exatamente o que o Ponytail manda não fazer; e a tabela `visoes_de_tabela` viraria migration obrigatória de todo projeto derivado.
3. Fazer (c) direto, pulando (b) — inverte a ordem: pacote sem uso interno nasce sem os defeitos que o uso encontra.

### Consequências

- **Positivas**: a demanda concreta (RQ-04) é atendida com o menor diff; o custo dos outros níveis fica medido e datado para a decisão futura.
- **Negativas**: quem precisar de views salvas em 2026 paga €79 ou testa um pacote de 17 stars.
- **Riscos**: o mercado muda — se `ymsoft/filament-table-presets` ganhar tração ou o Filament trouxer views ao core (o autor do pago já portou a reordenação de colunas), esta ADR fica obsoleta. Reavaliar na próxima varredura de pacotes.

### Referências

- `01-plano-acao.md` → "Estimativa de custo"
- ADR-01, ADR-02, ADR-03
