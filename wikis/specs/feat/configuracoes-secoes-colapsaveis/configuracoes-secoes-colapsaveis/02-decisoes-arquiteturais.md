# Decisões Arquiteturais — Tela de configurações: aba Aplicação, seções colapsáveis e carregamento adiado

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01. -->

Nenhuma decisão passou nos três portões. As escolhas desta feature — a chave estável de cada seção para o estado colapsado persistir, o carregamento adiado por aba com `key()` próprio, o rótulo "Aplicação" e o agrupamento dos campos — são reversíveis numa edição da própria Page, não surpreendem quem lê o código ao lado (a Page já tem seções com ícone e colapso, e o Filament documenta o adiamento) e não envolvem trade-off que outra alternativa real disputasse. Estão em `## Decisões de Desenho` do `01`.

## Superfície Livewire

Tudo o que o navegador alcança entre requests nesta feature. A tela é uma `SettingsPage` existente (`App\Filament\Admin\Pages\ConfiguracoesDoKit`), e a feature **não cria** método público nem propriedade pública nova no código do kit — a superfície nova vem do mecanismo de carregamento adiado do Filament, que a tela passa a usar.

| Ponto de entrada | Origem | Alcançável por | Fronteira aplicada | Evidência |
|---|---|---|---|---|
| `loadDeferredSchema(string $schemaKey)` | framework (trait `InteractsWithSchemas`) | `$wire.loadDeferredSchema('form.configuracoes.identidade')` com qualquer string *(alterado em 2026-10-15, step 9 — `fw-revisor-diff`: a chave efetiva leva o prefixo `form.`, como o CT-13 e `tests/Pest.php:carregarAba():407` usam)* | só renderiza o HTML de um schema **adiado e visível** da própria Page (`isHidden()` recusa; chave desconhecida cai no `skipPartialRender`); não grava nada; o conteúdo é o formulário que o usuário já vê — mesma permissão da tela (`ExigePermissaoDaTela`) | `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:loadDeferredSchema():138`, `:155-160` |
| `public array $loadedDeferredSchemas` | framework (trait `InteractsWithSchemas`) | tentativa de `$wire.set('loadedDeferredSchemas', {...})` entre requests | **`#[Locked]`** — o Livewire recusa a escrita (`CannotUpdateLockedPropertyException`); o cliente só alcança a lista pelo `loadDeferredSchema()` *(alterado em 2026-10-15, lote L1: a linha dizia "sem `#[Locked]`")* | `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:$loadedDeferredSchemas:55`, `:53` |
| `public array $data` | framework (`SettingsPage`/`InteractsWithSchemas`) | `$wire.set('data.x', …)` | já inventariada na wiki ancestral `settings-do-kit` (gravação passa por `mutateFormDataBeforeSave()` e pelas regras do formulário); esta feature não muda o que entra nem o que sai | `vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:save():62` |
| Estado colapsado das seções | framework (Alpine `$persist` em `localStorage`, chave `filament:section:collapsed:{id}`) | só no navegador do usuário; não chega ao servidor | nenhuma — é preferência de exibição; o formulário envia os campos colapsados do mesmo jeito (`x-bind:class` só esconde, o DOM continua) | `vendor/filament/schemas/src/Components/Section.php:$persist:364`, `:x-bind:class:375` |
| Aba ativa na query string (`?tab=`) | framework (`persistTabInQueryString`) | URL digitada | valor desconhecido cai na primeira aba (`getKey(isAbsolute: false) !== $queryStringTab` ignora) | `vendor/filament/schemas/src/Components/Tabs.php:persistTabInQueryString():140`, `:150-166` |

Greps feitos no step 3 (sessão) sobre `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`: `grep -n "public function " ` devolve só `canEdit()` e `form()`, já inventariados na ancestral (os `mutateFormDataBefore*` são protegidos); `grep -nE 'public[[:space:]]+([^($;]*[[:space:]])?\$[[:alpha:]_]'` não devolve propriedade pública nova. **Re-varredura sobre o código final (pré-9, 2026-10-15)**: `grep -n "public function "` devolve só `canEdit():113` e `form():118`; o grep de propriedades públicas devolve vazio; nenhuma linha nova na tabela — a superfície nova continua sendo só a do vendor (`loadDeferredSchema()` e a propriedade travada).
