# Progresso — Hub de navegação em cards

> Plano: `01-plano-acao.md` · Requisito: `00-requisito.md`

## 1. Instalar o pacote

- [x] `composer require harvirsidhu/filament-cards:"^1.0"`

## 2. Registrar o plugin nos três painéis

- [x] `AdminPanelProvider` — `FilamentCardsPlugin::make()`
- [x] `AppPanelProvider` — `FilamentCardsPlugin::make()`
- [x] `InfraPanelProvider` — `FilamentCardsPlugin::make()`

## 3. CSS dos cards

- [x] `resources/css/filament/cards.css` criado com cabeçalho explicativo
- [x] `KitServiceProvider::configureCorrecoesDeCss()` registra `Css::make('kit-cards', …)`
- [x] Conteúdo determinado **olhando a tela**, não por antecipação
- [x] `php artisan filament:assets`

## 4. Concern de descoberta

- [x] `app/Filament/Concerns/DescobreCardsDoPainel.php`
- [x] Filtra por `canAccess()` de cada destino
- [x] **Sem** guarda de painel nulo (cortado na auditoria Ponytail — estado inalcançável)
- [x] Agrupa pelo grupo de navegação, respeitando a ordem do painel

## 5. Hub do `/infra`

- [x] `app/Filament/Infra/Pages/HubDeInfraestrutura.php`

## 6. Hub do `/admin`

- [x] `app/Filament/Admin/Pages/HubDeAdministracao.php`

## 7. Hub do `/app`

- [x] `app/Filament/App/Pages/HubDoNegocio.php`
- [x] Confirmado que **não** foi acrescentado a `permissoesDeAdministracaoDoApp()`

## 8. Regenerar permissões

- [x] `ShieldPermissionsSeeder`
- [x] `PapeisSeeder`
- [x] `php artisan route:list --path=hub` confere as três rotas

## 9. Documentação do kit

- [x] `wikis/pacotes.md`
- [x] `wikis/receitas.md` — receita "Página hub de cards" com os 4 casos de uso e o "o que NÃO fazer"
- [x] `wikis/receitas.md` — linha nos checklists "Resource novo" e "Página de painel" (RQ-04)
- [x] `wikis/convencoes.md` — armadilhas do `canAccess()` e das classes interpoladas

## 10. README — dependência

- [x] Linha na tabela `### UI e produtividade`
- [x] Roteiro de features atualizado, se listar telas

## 11. Candidato a rule de projeto

- [ ] Avaliado nos 4 gates
- [ ] Apresentado ao usuário
- [ ] Gravado via `requirement-to-rule` **somente se aprovado**

## Testes

- [x] `04-casos-de-teste.md` gerado pela skill `feature-test-design`
- [x] `05-casos-de-teste-browser.md` gerado
- [x] Testes de componente verdes
- [ ] CT-B verdes

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check`
- [x] `vendor/bin/pest --group=kit --compact`
- [ ] `composer test:browser`
- [x] Os três hubs abertos com `master_global`, papel de painel e `panel_user`
- [ ] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido
- [ ] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "se algum teste assere **número** de Pages ou de permissions, ele quebra com as 3 Pages novas" | varredura feita: o único `toHaveCount` sobre permissões é `PaineisTest.php:123`, e ele assere que `master_global` tem **zero** permissions (ele vence pelo `Gate::before`). Nenhum teste conta Pages nem permissions de painel | nenhuma correção; o risco não se materializa. Registrado para o step 6 não reabrir a dúvida |
| o pacote não registra CSS | confirmado: `FilamentCardsServiceProvider` faz só `->name()->hasViews()`. Nenhum `FilamentAsset::register()`, nenhum arquivo em `resources/dist` | nenhuma; é a base do ADR-02 |
| `CardItem` não verifica `canAccess()` | confirmado: `Concerns/CanBeHidden.php` avalia só `visible`/`hidden`, e `CardsPage::getProcessedGroups()` filtra só por `isVisible()`. O `canAccess()` só aparece dentro de `discoverClusterCards()`/`discoverResourceCards()` | nenhuma; é a base do ADR-04 |
| a suíte `Browser` cobre só `tests/Browser` | `phpunit.xml:32-39`: a testsuite `Browser` inclui **`tests/Browser` e `tests/BrowserTenancy`** | nenhuma mudança de plano — mas explica por que `--testsuite=Browser` é o comando único dos CT-B |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `yagni:` guarda de painel nulo + `warning` + CT-06 — `cardsDoPainel()` só é chamado de uma `CardsPage`, que nunca renderiza fora de request de painel | **sim** | `01` (§ Channel de Log e passo 4), `03`, `04` (R5 removida) |
| 2 | `delete:` CT-B01 (busca client-side em navegador) — testa o Alpine do vendor; o único mutante é uma propriedade booleana | **sim** | `05` |
| 3 | `yagni:` hub em 3 painéis | **recusada** — pedido explícito do usuário; Ponytail não simplifica o que foi pedido |
| 4 | concern `DescobreCardsDoPainel` como abstração prematura | **recusada** — 3 chamadores e fecha o furo de `canAccess()` |

## Blockers

Nenhum.

## Desvios do Plano

| Passo | Desvio | Motivo |
|---|---|---|
| 3 — CSS | o `cards.css` saiu com ~45 regras, não com "as poucas que faltarem" | ver a nota abaixo: o custo estimado no ADR-02 estava **uma ordem de grandeza** abaixo do real |
| 3 — CSS | acrescentado `getPageClasses()` nas três Pages | era preciso um seletor para escopar o CSS. Sem escopo, definir `.p-4`, `.text-sm` e `.bg-white` globalmente atropelaria a marcação de qualquer plugin que use os mesmos nomes |
| Testes | os casos de componente fixam `Filament::setCurrentPanel(...)` | sem isso a descoberta lê o painel que estiver ambiente no processo — o primeiro teste do hub de admin passou a renderizar os **18 cartões do /infra** |

## Notas de Implementação

### A CSS pré-compilada do Filament 5 quase não tem utilitária genérica

Medido com uma varredura sobre `public/css/filament/filament/app.css`: das 53 classes que a blade do pacote emite, **51 não existem lá**. O arquivo carrega quase só as semânticas `fi-*` (`.fi-btn`, `.fi-ta-cell`, …) — não há `.grid-cols-1`, `.rounded-xl`, `.p-4`, `.text-sm`, `.bg-white`.

Consequência prática: **o `cards.css` não é um remendo, é a folha de estilo da página**. O ADR-02 previa "preencher o que faltou, provavelmente o `colorMap`"; o real foi escrever grid, espaçamento, tipografia, sombra, anel e tema escuro. O ADR foi anotado com a medição.

Isso muda a leitura do custo, não a decisão: a alternativa era o tema Filament customizado, recusada pelo usuário porque tornaria `npm run build` pré-requisito duro para os painéis abrirem.

**O que fica de manutenção**: se o pacote mudar a markup num upgrade, a grade perde estilo **em silêncio** — HTML correto, zero erro. O alarme é o CT-B (screenshot), e o aviso está no cabeçalho do `cards.css`.

### As cores vêm de variável emitida em runtime

`--primary-*`, `--gray-*` e irmãs **não** estão no CSS compilado: o Filament as emite por request, num `<style>` do `assets.blade.php`, a partir da paleta do painel. É por isso que `var(--primary-500)` no `cards.css` funciona e acompanha a identidade visual da organização — o mesmo mecanismo em que o `kit.css` já se apoia.

### O rótulo do `RoleResource` é "Funções", não "Papéis"

Vem da tradução do Shield. O caso de teste passou a usar `RoleResource::getNavigationLabel()` em vez da string: cravar o texto tornaria o caso um teste da tradução do vendor.

## Degradações declaradas

- **Boost MCP indisponível nesta sessão**: `search-docs` não estava conectado. A API do pacote foi confirmada lendo o código-fonte dele (`CardItem.php`, `CardsPage.php`, `Concerns/CanBeHidden.php`, `cards-page.blade.php`) e a documentação oficial. As duas descobertas que mudaram o plano — `CardItem` não checa `canAccess()` e o pacote não registra CSS — vieram do código, não da doc.

## Retrospectiva
