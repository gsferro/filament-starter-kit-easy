---
title: "Convenções do kit"
parent: "Operação"
grand_parent: "Português"
nav_order: 3
---

# Convenções do kit

- **UUID nas rotas, `id` int como PK.** Toda tabela nova ganha `$table->uuid('uuid')->unique()` e o model usa `App\Traits\TemUuid`. URL com id numérico devolve 404 e ninguém enumera registros por sequência. UUID não é autorização — policies continuam obrigatórias.
- **Auditoria no que é editável.** `App\Traits\AuditsFillables` audita exatamente o `$fillable`, sem vazar colunas técnicas para a trilha.
- **Seeder nunca usa factory nem faker.** `fakerphp/faker` é `require-dev` e a imagem Docker roda `--no-dev`.
- **Permissões vêm de seeder, não de `shield:generate` interativo** — é o que permite instalar sem intervenção. O `ShieldPermissionsSeeder` gera para os **três** painéis (o comando do Shield só enxerga o painel corrente); o `PapeisSeeder` recorta a matriz por painel e entrega aos papéis. Depois de criar Resources novos, rode os dois (veja [abaixo](depois-de-criar-resources.md)).
- **Acesso a painel é dado do papel**, na coluna `roles.painel` — não uma lista de nomes no código. Papel sem painel não abre painel nenhum: o default fecha.
- **Nada de affordance sem permissão.** Menu, busca e ações consultam `canAccess()`/`canCreate()` antes de aparecer. Encontrar algo que resulta em 403 é considerado bug.
- **Tradução de plugin vai em `lang/vendor/`.** Vários pacotes só trazem inglês; o kit traduz sem tocar no vendor.
- **Listagem com estados distintos ganha `getTabs()`.** A aba é o recorte de **um clique**; o filtro do modal é para **combinar** (com a busca, com a lixeira, com outro filtro). Os dois existem, e não competem. A regra do recorte fica em **um lugar só**, chamado pelos dois — `AprovacaoDeCadastro::recorteDePendentes()` para usuários, `Convite::recorteDePendentes()`/`recorteDeAceitos()` para convites. Escrever a query de novo dentro do `getTabs()` é como o filtro e a aba passam a discordar sem ninguém notar. A contagem do badge sai sempre do `getEloquentQuery()` do Resource, nunca do model: no `/app` ele carrega o recorte de organização, e um badge contando a instalação inteira informaria quantos registros existem fora dela. **A aba ativa não persiste na sessão** (é assim no Filament): para linkar uma listagem já recortada — de um card do hub, de uma notificação —, use `?tab=` na URL, com `ListUsers::getUrl(['tab' => 'pendentes'])`. O ledger de IA (`/infra/ai-runs`) fica de fora de propósito: ele já tem `SelectFilter('status')` na tela, e uma aba por status o duplicaria.

## Armadilhas já resolvidas

Coisas que custaram tempo para descobrir e que o kit já entrega prontas — se você mexer nelas, saiba o porquê:

| Onde | O quê |
|---|---|
| Lockscreen | precisa estar registrado nos **três** painéis: o `routes/web.php` do pacote resolve o plugin pelo painel corrente e estoura `LogicException` em todo request — até `artisan package:discover` morre |
| Tela de bloqueio | é uma `SimplePage` e ignora o layout do Auth Designer. `App\Filament\Pages\Auth\TelaBloqueio` a veste com o layout do login (bind em `AppServiceProvider`), **redeclarando `$layout`** — a trait do pacote atribui a propriedade estática, e sem a redeclaração o layout de login vaza para toda página Filament do processo |
| "Bloquear sessão" no menu | o item que o pacote registra nasce sem `sort` e cai depois do alternador de tema; o kit o substitui num `bootUsing()` com `sort(-1)` (no corpo de `panel()` não funciona: plugin boota antes, e quem registra por último vence) |
| Command Center | **sem** `->cluster()`: com cluster a página raiz devolve 500 |
| `databaseNotifications()` | declarado **depois** de `plugins()`, senão o Notification Center apaga o recorte, sem erro nenhum |
| Dependency Graph | `canAccessUsing()` substitui a regra local-only do pacote (sem ele, 404 em homologação) |
| Logs Explorer | `deletable(false)`: o delete do pacote faz `@unlink()` sem gravar rastro |
| Ações de filtro | **fora** do `configureUsing()` global: em tabela sem filtro a ação nasce sem nome e derruba a página |
| Pulse + resized-column | os dois bundles declaram constantes no escopo global; carregados como ES module para o segundo não morrer calado |
| Busca ⌘K | gatilho no hook `GLOBAL_SEARCH_BEFORE` (o `USER_MENU_BEFORE` renderiza dentro do dropdown) e overlay aberto em `setTimeout`, senão o próprio clique fecha o painel |

