# A Norma Extraída do Blueprint

> Do que o `filament/blueprint` v2.2.0 orienta, o que é **verificável em código existente**.
>
> **Sem texto do pacote.** O `filament/blueprint` é pago e a licença não permite redistribuir o
> conteúdo dele. Este arquivo cita apenas **onde** cada orientação está (arquivo e seção) e diz, em
> palavras próprias, o que ela orienta — nenhuma frase do vendor é reproduzida. Quem tiver licença
> confere na fonte; quem não tiver lê a norma traduzida sem receber o material.
>
> As 23 referências de `resources/markdown/planning/` (3.232 linhas) são escritas para um agente que
> vai *planejar* uma feature nova. Elas dizem "o plano deve conter X". Para auditar um kit que já
> existe, cada orientação precisa ser traduzida numa pergunta com resposta sim/não sobre o código —
> ou declarada não-aplicável, com o motivo. Este arquivo é essa tradução. É contra ele que o
> `05-comparativo.md` mede o kit.

## Como ler

| Coluna | Significado |
|---|---|
| **N-##** | ID da norma. É o que o comparativo cita |
| **Origem** | referência e trecho do Blueprint de onde a norma sai |
| **Verificação** | o que se mede no código, e como |
| **Peso** | **S** segurança · **A** arquitetura · **Q** qualidade · **D** documentação |

Regras que só falam da **forma do plano** — incluir URL de docs, listar o comando de scaffold, ter
uma linha `Component:` — estão na seção **Não se aplica a código**, ao final, para ficar
registrado que foram lidas e por que não geram medição.

## A. Autorização (`authorization.md`, `checklist.md`, skill de auditoria)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-01 | Toda ação customizada tem autorização **explícita**, não herdada por acidente | `authorization.md` → seção *Action Authorization*; `checklist.md` → item sobre ação customizada sem autorização declarada | `grep "Action::make('"` em `app/Filament`, e para cada uma: existe `->authorize()`, `->visible()` com permissão, ou é ação sobre a própria sessão (não há o que autorizar)? | S |
| N-02 | Ação destrutiva é autorizada pelo método que o Filament **consulta**, não por `can*()` | skill de auditoria, check A3 — no v4+ o `can*()` continua governando o acesso à PÁGINA, enquanto ação de registro e de lote autorizam pelo `get*AuthorizationResponse()` | `grep "function can(Delete\|Edit\|Create)"` em resources: cada override com intenção de negar tem o par `get*AuthorizationResponse()`? | S |
| N-03 | Policy usa **permissão nomeada**, não papel hardcoded | `authorization.md` → a forma prescrita é a permissão nomeada; papel hardcoded aparece na lista do que **não** escrever | `grep "hasRole\|->role\b"` em `app/Policies`: zero ocorrências | S |
| N-04 | Quem não pode agir não vê a superfície **e** não consegue agir | `authorization.md` → seção *Field Visibility*; skill de auditoria, check A4 — coluna editável inline sem `->disabled()` grava | Para cada Page/Widget: `canAccess()`/`canView()` consulta permissão? Para cada resource: `getEloquentQuery()` fecha sem contexto? | S |
| N-05 | Bulk action com guarda `*Any()` quando o par por registro existe | skill A1 | `grep "DeleteBulkAction"` → a policy do modelo tem `deleteAny()`? | S |

## B. Multi-tenancy (`multi-tenancy.md`)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-06 | `canAccessTenant()` gateia por **pertencimento real** | `multi-tenancy.md` — o método é apontado como a **única** barreira contra manipulação de tenant pela URL | ler `User::canAccessTenant()`: consulta a pivot? Devolve `true` incondicional só para quem tem atalho declarado? | S |
| N-07 | `unique`/`exists` em campo de resource **escopado** usa `scopedUnique`/`scopedExists` | `multi-tenancy.md` — as regras `unique` e `exists` do Laravel não respeitam global scope | `grep "->unique(\|->exists("` em `app/Filament/App`: cada um está numa tabela **escopada por tenant**? (Tabela global — `users` — é exceção legítima e precisa estar documentada) | S |
| N-08 | Nenhum `withoutGlobalScopes()` sem argumento | `multi-tenancy.md` — a chamada sem argumento remove também o escopo de tenant | `grep "withoutGlobalScopes()"` em `app/`: zero | S |
| N-09 | Modelo com `tenant_id` sem resource no painel tenant é escopado **explicitamente** ou vive só em painel global | `multi-tenancy.md` — modelo sem resource no painel não é escopado automaticamente | listar modelos com FK de tenant; para cada um sem resource no `/app`: onde é consultado? | S |
| N-10 | Query em provider/middleware roda **depois** da identificação do tenant | `multi-tenancy.md` — consulta em middleware antes da identificação do tenant vaza dado | `grep "Tenant::\|->tenants()"` em `app/Providers` fora de closures adiadas | S |

## C. Formulários e validação (`forms.md`, `reactive-fields.md`)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-11 | `unique()` **sem** `ignoreRecord` — o v4+ ignora o registro corrente por default | `forms.md:185` — o v4+ ignora o registro corrente por conta própria, então `ignoreRecord` é redundante — confirmado em `CanBeValidated.php:34`, `$shouldUniqueValidationIgnoreRecordByDefault = true` | `grep "ignoreRecord: true"` em `app/`: cada ocorrência é redundante | Q |
| N-12 | Reatividade com `->live()`, nunca `->reactive()` | `checklist.md` → seção de métodos trocados, entre eles `->reactive()` no lugar de `->live()` | `grep "->reactive("`: zero | Q |
| N-13 | `Get`/`Set` do namespace `Filament\Schemas\Components\Utilities`, nunca `Filament\Forms\Get` | `checklist.md` → seção de namespaces trocados | `grep "use Filament\\\\Forms\\\\(Get\|Set);"`: zero | Q |
| N-14 | Inteiro com `->integer()`, não `->numeric()` | `forms.md` → campo inteiro usa `->integer()`, e não `->numeric()` | `grep "->numeric()"` em `TextInput` de campo inteiro | Q |
| N-15 | Componentes que não existem no v5 não aparecem | `checklist.md` → `Card`, `BelongsToSelect`, `MultiSelect`, `BadgeColumn`, `BooleanColumn`, `DateColumn` | `grep` de cada nome em `app/`: zero | Q |
| N-16 | Enum em `Select`/`Radio`/`ToggleButtons` implementa `HasLabel` (e `HasColor`/`HasIcon` quando exibido como badge) | `forms.md` → seção sobre os contratos de enum; `tables.md` → seção sobre coluna de enum | listar enums em `app/Enums` e `app/Support`: os usados em campo/coluna implementam os contratos? | Q |
| N-17 | Toggle para preferência ("Enable X"), Checkbox para consentimento | `forms.md` → seção que separa Toggle de Checkbox | `grep "Checkbox::make"`: cada um é consentimento? | Q |
| N-18 | Colunas aninhadas não multiplicam para 25% (form 2 col + section 2 col) | `checklist.md` → item sobre coluna aninhada estreita demais | para cada `Section::make(...)->columns(2)`: o schema pai é 1 coluna ou a seção tem `columnSpanFull()`? | Q |

## D. Tabelas (`tables.md`, `bulk-actions.md`)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-19 | Coluna de data/datetime é `->sortable()` | `tables.md` — coluna de data é sempre ordenável | `grep "->date()\|->dateTime()"` em colunas: cada uma tem `->sortable()`? | Q |
| N-20 | Boolean é `IconColumn->boolean()` (ou `ToggleColumn` se editável **com** autorização) | `tables.md`; skill A4 | `grep "ToggleColumn\|SelectColumn\|TextInputColumn\|CheckboxColumn"`: cada uma tem `->disabled()` com auth? | S |
| N-21 | Status/enum como `->badge()` | `tables.md` — status e enum se exibem como badge | colunas de status sem badge | Q |

## E. Models (`models.md`, `relationships.md`)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-22 | Atributo sensível em `$hidden` | skill D2 | `password`, `remember_token`, tokens, secrets: em `$hidden`? | S |
| N-23 | Mass assignment fechado: `$fillable` explícito, sem `$guarded = []` | prática Laravel que o Blueprint pressupõe em `models.md` → seções sobre tipos de atributo e restrições | `grep "guarded = \[\]"`: zero | S |
| N-24 | Enum backed com `HasLabel` quando exibido | `models.md` → seção sobre enums | ver N-16 | Q |

## F. Páginas e widgets (`custom-pages.md`, `widgets.md`)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-25 | Page customizada tem `canAccess()` que consulta permissão | `custom-pages.md` → autorização na lista do que a página precisa ter; skill de auditoria, check A6 | cada Page em `app/Filament/**/Pages` (fora `Auth/`): usa `ExigePermissaoDaTela` ou `canAccess()` próprio? | S |
| N-26 | Widget tem `canView()` que consulta permissão | `widgets.md` → autorização na configuração do widget | cada Widget: usa `ExigePermissaoDoWidget` ou `canView()` próprio? | S |
| N-27 | Page fora de painel (rota própria) restringe upload ao schema | skill A5 | ver v0.20.0 — coberto por `UploadAnonimoTest` | S |
| N-28 | Settings page segue o padrão que o app já tem | `custom-pages.md` → orienta seguir o padrão de settings que a aplicação já tiver | `ConfiguracoesDoKit` usa `spatie/laravel-settings` como o resto do kit? | A |

## G. Testes (`testing.md`)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-29 | Todo resource tem teste de **autorização** (quem não pode, não acessa) | `testing.md` — autorização encabeça a ordem de prioridade | para cada resource: existe caso com 403 por permissão revogada? | S |
| N-30 | Todo formulário de create/edit tem teste de **validação** com dataset | `testing.md` — validação, com dataset | para cada resource com form: existe `assertHasFormErrors` em dataset? | Q |
| N-31 | Toda ação customizada tem teste | `testing.md` — ações customizadas, prioridade média | para cada `Action::make('x')` de N-01: existe `callAction('x')` em teste? | Q |
| N-32 | Filtro customizado tem teste | `testing.md` — filtros, prioridade média | para cada `Filter::make`/`SelectFilter::make`: existe `filterTable()`? | Q |
| N-33 | Não se testa o que o Filament garante (visibilidade de coluna, layout, ações built-in sem customização) | `testing.md` → seção sobre o que não testar | há teste que só afirma `assertTableColumnVisible` sem regra? | Q |
| N-34 | Helpers depreciados não aparecem | `.stubs.php` do Filament marcam `assertFormSet`, `callTableAction`, `assertTableActionExists` como `@deprecated` | `grep` dos três em `tests/`: zero | Q |

## H. Segurança geral (skill `filament-security-audit`, linha de base v0.20.0)

| ID | Norma | Origem | Verificação | Peso |
|---|---|---|---|---|
| N-35 | Os 21 checks do catálogo continuam `Pass`/`N/A` — nenhuma regressão desde a v0.20.0 | skill inteira | re-rodar as buscas dos checks com achado provável (A3, A5, B2, C1, D3) sobre o código atual | S |
| N-36 | Default global de `preventFilePathTampering` (dica §5 da v0.20.0) | skill de auditoria, check B1 — a dica do §5 | `grep "preventFilePathTampering"` em `app/Providers`: existe? Condição: há `FileUpload` não-Spatie e `FILESYSTEM_DISK=local` | S |

## I. Instalação e opt-in (fora do Blueprint — é a natureza de starter-kit)

O Blueprint não fala de instalador porque não é o seu assunto. Mas o requisito pede (RQ-05, RQ-06),
e a norma é a do próprio kit: **cada caminho documentado funciona como documentado**.

| ID | Norma | Verificação | Peso |
|---|---|---|---|
| N-37 | `create-project` sem interação instala e os três painéis respondem 200 | instalar; `curl` em `/up`, `/app/login`, `/admin/login`, `/infra/login` | S |
| N-38 | Cada chave `KIT_*` do `.env.example` tem efeito observável e está no README | para cada chave: onde é lida (`grep config('kit.`), e o README a descreve? | D |
| N-39 | Cada flag do `kit:install` faz o que a descrição diz | instalar com cada flag; comparar resultado com a ajuda do comando | Q |
| N-40 | `kit:tenancy` liga a multi-organização e o `/app` passa a exigir tenant na URL | instalar; ligar; `curl` em `/app` redireciona para escolha/registro de tenant | S |
| N-41 | Papéis semeados têm as permissões das telas que o README diz que eles abrem | por papel: listar permissões; cruzar com a tabela de acesso do README | S |
| N-42 | Revogar uma permissão **fecha a porta**, não só o menu | para amostra de telas por painel: revogar `View:X` do papel e `GET` → 403 | S |
| N-43 | `kit:admin`, `kit:update`, `kit:midia-privada`, `kit:convites-lembrar`, `kit:arte` fazem o que o README descreve | rodar cada um na instalação; comparar com o README | D |

## Não se aplica a código (lido, e descartado com motivo)

| Orientação do Blueprint | Por que não gera medição |
|---|---|
| exigir que todo elemento do plano declare componente, URL de docs, validação e config | é forma de **plano**. Em código, o "Component" é o `use`, a "Config" é a chamada. A URL de docs em comentário é opcional |
| incluir o comando de scaffold com `--no-interaction` | o kit já existe; não há scaffold a rodar |
| perguntar ao usuário antes de planejar | a rodada é de auditoria; as perguntas viraram `## Ambiguidades` do `00` |
| a ordem models, resources, autorização e testes | ordem de escrita de plano; não descreve estrutura de código |
| especificar colunas e span no layout | o **efeito** (N-18) é verificável; a forma de especificar, não |
| `imports.md` / `exports.md` — formato de plano de importador | o kit tem importador/exportador (`ImportExport/`); a norma de **autorização** deles está em N-01 e na skill A2, já `N/A` na v0.20.0 |
| `pivot-tables.md`, `wizards.md`, `infolists.md`, `styling.md` | o kit não tem wizard; pivot é `tenant_user` (coberto por N-06/N-09); infolist é `BoasVindas` (coberto por N-27); styling é sobre cor de badge (N-21) |
