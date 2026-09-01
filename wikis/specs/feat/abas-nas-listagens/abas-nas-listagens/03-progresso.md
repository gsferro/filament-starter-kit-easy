# Progresso — Abas de recorte nas listagens

> Nível (a) do estudo `estudo-advanced-tables`, aprovado em 2026-08-30.

## 1. Extrair o recorte de pendentes

- [x] `AprovacaoDeCadastro::recorteDePendentes(Builder): Builder`
- [x] `filtroDePendentes()` passa a usá-lo
- [x] Testes de aprovação de cadastro verdes **antes** de acrescentar a aba

## 2. Abas nas listagens de usuários

- [x] `Admin\Users\Pages\ListUsers::getTabs()`
- [x] `App\Users\Pages\ListUsers::getTabs()`
- [x] Badge pela `getResource()::getEloquentQuery()`

## 3. Extrair o recorte de convites

- [x] `ConvitesTable::pendentes(Builder): Builder`
- [x] `ConvitesTable::aceitos(Builder): Builder`
- [x] `TernaryFilter` passa a usá-los

## 4. Abas nas listagens de convites

- [x] `Admin\Convites\Pages\ListConvites::getTabs()`
- [x] `App\Convites\Pages\ListConvites::getTabs()`

## 5. Testes

- [x] `04-casos-de-teste.md` derivado do `00-requisito.md` pela `feature-test-design`
- [x] Casos escritos e verdes
- [x] Sem `05-*-browser.md` — a aba é troca de `activeTab` pelo Livewire, com oráculo no banco

## 6. README

- [x] Convenção "estados distintos ganham `getTabs()`"
- [x] Aba ativa não persiste na sessão; `?tab=` é o jeito de linkar
- [x] Por que `/infra/ai-runs` fica de fora

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check`
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
- [x] `git commit` por bloco

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| nenhuma das quatro páginas sobrescreve `getTabs()` | confirmado nas quatro | nenhuma |
| o recorte de pendentes está numa closure de `Filter` | `AprovacaoDeCadastro.php:75-80` | nenhuma; é a base da ADR-01 |
| o recorte de convites já está em closures separadas | `ConvitesTable.php:60-66`, `queries(true:, false:, blank:)` | nenhuma |
| `Tab` vem de `Filament\Schemas\Components\Tabs\Tab` | usado em `RoleResource.php` | nenhuma |
| `?tab=` funciona sem passo extra | `ListRecords.php:39,54` | nenhuma; o passo do estudo já dizia isso |

### Auditoria Ponytail (step 6)

Herdada do estudo ancestral, que já cortou este nível de 4 passos para o mínimo: sem trait
compartilhada, sem helper, sem env var, sem log, e `/infra/ai-runs` fora. Nada a cortar além.

## Blockers

- Nenhum.

## Desvios do Plano

- **Os recortes de convite foram para o MODEL, não para `ConvitesTable`.** O plano previa métodos
  estáticos na tabela do /admin; ela não serve ao /app, que tem tabela própria dentro de
  `App\Resources\Convites\ConviteResource`. Pôr o recorte lá obrigaria o /app a importar uma classe
  do /admin — acoplamento entre painéis para não duplicar duas linhas. `Convite::recorteDePendentes()`
  e `recorteDeAceitos()` ficam ao lado de `situacao()`, que mora no model pelo mesmo motivo e cujo
  docblock já registra a divergência que isso evitou.
- **As abas de convites não têm badge.** O plano não pedia, e o estudo ancestral também não: convite
  pendente é a maioria da listagem, então o número ao lado de "Pendentes" custaria uma `count()` por
  render para dizer quase o total. O badge de usuários existe porque lá a informação é a exceção.
- **`recorteDePendentes()` é `public`, não `protected`.** A página de listagem não herda da trait —
  ela é do Resource —, então `protected` não alcança.
- **Os três recortes são genéricos (`@template TModel`)**, e não `Builder<User>`/`Builder<Convite>`.
  O chamador é o `getEloquentQuery()` do Resource, que o Filament declara como `Builder<Model>`;
  estreitar o tipo obrigaria a chamar `User::query()` na aba, que é o que `.ai/rules/filament.md`
  proíbe — é justamente o que carrega o recorte de organização.

## Notas de Implementação

- **`getBadge()` do Filament devolve `string`**, não `int` — o badge é rótulo. Três casos vermelhos
  com "Failed asserting that '1' is identical to 1" antes de a asserção ser corrigida.
- **`->loadTable()` é obrigatório** antes de qualquer asserção de registro: a tabela do kit carrega
  adiada (`deferLoading`), e sem ele o HTML testado é o do esqueleto. Já era convenção do projeto;
  o custo de esquecer é uma falha que despeja a página inteira no relatório.
- **O macro `ImageColumn::simpleLightbox()` só existe com o painel BOOTADO**, e teste de componente
  Livewire não passa pelo middleware `SetUpPanel`, que é o único chamador do boot.
  `noPainelBootado('app')` não resolve: sem rota corrente o `BreezyCore:112` morre em
  `parameter() on null`. A saída é um request HTTP real ao painel no `beforeEach` — macro é
  estático, então um basta para o processo. Os arquivos vizinhos de `tests/Tenancy` funcionam por
  herdarem esse registro de um caso HTTP anterior, dependência de ordem que este arquivo não quis.
- **A tela do `/app` não renderiza sem organização corrente**: a rota é `app/{tenant}/users` e o
  Blade estoura em "Missing required parameter" antes de qualquer asserção. O CT-10 (fail-closed)
  afirma sobre a query e sobre a expressão do badge; quem prova que a aba usa essa expressão com a
  tela de pé é o CT-09.
- **Achado adjacente, fora do escopo desta wiki**: `Convite::factory()->create(['tenant_id' => X])`
  grava o id da organização **corrente**, descartando o valor passado — medido com `new Convite` +
  `save()` e conferido no banco cru (`DB::table('convites')`). O único listener de
  `eloquent.creating: App\Models\Convite` chega embrulhado pelo `Dispatcher::makeListener()` e a
  origem não foi identificada dentro do orçamento desta feature. Não é defeito introduzido aqui — o
  CT-12 contorna corrigindo a coluna no banco —, **mas merece wiki própria**: um convite criado no
  contexto errado nasce na organização errada.

## Validação em instalação real (2026-09-01, v0.22.3)

As abas foram percorridas num kit instalado do Packagist (`TESTES KIT/v0223-padrao`), servido em
`127.0.0.1:8123`, com navegador real (Playwright MCP, `--isolated --headless`) — **observação, não
cobertura**: quem prova comportamento continua sendo o `04-casos-de-teste.md`.

| O que | Resultado |
|---|---|
| `/admin/users` | "Todos" ativa por default e "Pendentes de aprovação" com badge `0` — RQ-01, RQ-03 e a ADR-03 (a primeira chave é a ativa) |
| `/admin/users?tab=pendentes` | a aba troca **pela URL** e a tabela recorta ("Sem registros", com zero pendentes) — é o mecanismo que o README indica no lugar de persistir a aba na sessão |
| `/admin/convites` | as três abas: "Todos", "Pendentes", "Aceitos", sem badge — RQ-07 e o desvio registrado acima |
| console | zero erros nas telas visitadas |

O `/app` não pôde ser percorrido nesta instalação: sem tenancy os Resources de usuários e convites
do painel de negócio se escondem, então as abas de lá não têm tela. A instalação com tenancy
(`v0223-tenancy`, `kit:tenancy --demo`) rodou a suíte `Kit,Tenancy` completa — 1753 casos, que
incluem os CT-08 a CT-12 da fronteira de organização.

## Retrospectiva

- **Funcionou**: extrair o recorte ANTES de escrever a aba, e rodar os testes de aprovação no meio.
  Quando as abas chegaram, já era certo que o filtro não tinha mudado de comportamento.
- **Funcionou**: o estudo ancestral ter cortado o nível (a) ao mínimo. Não houve nenhuma decisão de
  desenho a tomar durante a implementação — só as divergências de vendor acima.
- **Faltou no plano**: perceber que o /app não usa a `ConvitesTable` do /admin. Uma olhada em
  `App\Resources\Convites\ConviteResource::table()` na revisão profunda teria evitado o desvio.
