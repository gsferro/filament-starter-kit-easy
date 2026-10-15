# Plano de Ação — Tela de configurações: aba Aplicação, seções colapsáveis com ícone e carregamento adiado das abas

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/settings-do-kit/settings-do-kit/` (a tela nasceu lá); tocaram a mesma Page depois: `cabecalho-do-painel`, `layout-compact`, `login-social-por-painel`, `mais-provedores-sociais`, `recaptcha-nas-telas-publicas`, `logo-dark-mode`, `rodape-coerente`, `estudo-de-pacotes-rodada-2`, `estudo-de-pacotes-rodada-3`, `main/dock-de-acoes-rapidas`
- **Motivo**: a tela cresceu a seis abas e 50 campos, a maioria solta fora de seção; o solicitante pede seções colapsáveis com ícone em tudo, a aba "Kit" renomeada para "Aplicação" e o carregamento adiado das abas, porque a área de configuração só cresce
- **Toca infra compartilhada?**: sim → `.ai/rules/pages.md` (rule nova, RQ-04), `tests/BrowserTenancy/CapturaDeArteTest.php` (duas capturas clicam na aba "Kit") e `art/` (duas imagens recapturadas); nenhuma mudança em settings, seeders, middleware ou `tests/Pest.php`

> A tela é consumida por testes de 28 arquivos (`tests/Kit`, `tests/Tenancy`, `tests/Browser`, `tests/BrowserTenancy`) que preenchem e gravam campos por componente; a regressão contra eles é obrigatória (tipo `evolução` + infra compartilhada).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | aba "Kit" → "Aplicação" | 1, 4, 5 | o rótulo muda; posição e ícone ficam (P-02); docs/capturas acompanham (P-01) |
| RQ-02 | todo campo dentro de uma seção, agrupado com o que faz sentido junto | 2 | agrupamento proposto em `## Mapeamentos` › *Seções por aba* — **recorte dependente de Q2**: a sessão implementa a proposta; mudança de agrupamento pelo solicitante é Adendo |
| RQ-03 | toda seção colapsável e com ícone, inclusive as existentes | 2 | estado inicial e persistência do colapso: **recorte dependente de Q1** (implementada a recomendação — expandidas, com `persistCollapsed()`) |
| RQ-04 | a convenção vira rule do projeto | 6 | rule em `.ai/rules/pages.md` com o CT estrutural como enforcement; gravada no step 12 pela `requirement-to-rule`, com a aprovação do solicitante |
| RQ-05 | conteúdo das abas carregado de forma adiada | 3 | `Schema::deferLoading()` por aba, com `key()` em `Tabs` e em cada `Tab` (P-03, P-04) |
| RQ-06 | partir do código posterior à v0.49.0 | — | atendida: branch criada da `main` em `b0091516` (merge do PR #165), depois da tag `v0.49.0` |
| RQ-07 | branch nova | — | atendida: `feat/configuracoes-secoes-colapsaveis` |
| RQ-08 | Codex CLI como auxiliar | — | atendida no processo: levantamento do step 3 (despacho #1 do `03`) e lotes de implementação/teste |
| RQ-09 | processo pela `feature-wiki` | — | atendida: esta wiki |
| P-01 | docs, README e capturas falam "aba Aplicação" | 4, 5 | — |
| P-02 | só o rótulo da aba muda | 1 | — |
| P-03 | adiamento em todas as abas; aba com erro carrega sozinha | 3 | — |
| P-04 | salvar sem abrir uma aba adiada grava o que já estava nela | 3 | — |
| P-05 | seção colapsada envia e valida; erro expande a seção | 2 | — |

## Objetivo

Reorganizar a tela `/admin/configuracoes-da-aplicacao` sem mudar nenhuma configuração: todo campo passa a viver numa seção nomeada, com ícone e colapsável (estado lembrado por navegador); a aba "Kit" vira "Aplicação"; e cada aba só renderiza o seu conteúdo quando é aberta, para que a tela continue leve conforme as configurações crescem. A convenção "campo em seção com ícone e colapso" vira rule do projeto para as próximas abas e seções.

## Contexto

A tela é uma `SettingsPage` do `spatie-laravel-settings-plugin` com seis abas (`Identidade`, `E-mail`, `Tabelas`, `Registro`, `Login`, `Kit`). Levantamento do step 3 (despacho #1): dos 50 campos, 31 estão **soltos** fora de seção (Identidade 9, E-mail 8, Tabelas 7, Registro 3, Kit 4 + 3); as 10 seções existentes têm `description()`, mas só 3 têm ícone (`Dashboard dinâmico`, `Dock de atalhos`, as quatro de provedor social) e só 2 são colapsáveis (`Proteção anti-robô` por `collapsible()`, as de provedor por `collapsed()`). Nenhuma seção persiste o colapso. Todas as seis abas renderizam o HTML inteiro na primeira carga.

O Filament 5.10.2 instalado tem o mecanismo de adiamento: `Schema::deferLoading()` faz a aba renderizar um placeholder (`fi-sc-loading`, `aria-busy`) e um `IntersectionObserver` chama `$wire.loadDeferredSchema(chave)` quando o painel da aba entra na viewport; a aba cujo campo tem erro de validação é marcada como carregada antes do render, para o erro aparecer. Seção colapsada continua no DOM (classe `fi-collapsed` + CSS `hidden`), então envia e valida como antes, e o handler de erro do Filament dispara `expand` nos ancestrais do primeiro campo com erro — a seção abre sozinha.

## Análise dos Arquivos Existentes

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- `form()` monta `Tabs::make('configuracoes')->persistTabInQueryString()->columnSpanFull()->tabs([...])` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:form():117`, `:121-122`), com seis métodos privados `abaIdentidade()` (`:abaIdentidade():302`), `abaEmail()` (`:abaEmail():444`), `abaTabelas()` (`:abaTabelas():557`), `abaRegistro()` (`:abaRegistro():636`), `abaLogin()` (`:abaLogin():685`) e `abaKit()` (`:abaKit():939`), cada um devolvendo `Tab::make('…')->icon('…')->schema([...])`.
- Seções existentes: `Cabeçalho dos painéis` (`:abaIdentidade():402`, `columns(2)` em `:440`), `Página única de login` (`:abaLogin():698`), `Login social` (`:abaLogin():707`, que contém as quatro de `secaoDoProvedor()` — `:secaoDoProvedor():870`, `collapsed()` em `:874`, ícone de status em `:875`), `Rodapé da tela de login` (`:abaLogin():725`), `Proteção anti-robô` (`:secaoAntiRobo():761`, `:765`, `collapsible()` em `:767`), `Dashboard dinâmico` (`:abaKit():1000`, ícone em `:1002`) e `Dock de atalhos` (`:abaKit():1035`, ícone em `:1037`).
- Campos soltos por aba: ver `## Mapeamentos` › *Seções por aba* (nomes, rótulos e linhas vêm do inventário do despacho #1).
- Sem método público novo: `canEdit()` (`:canEdit():112`) e `form()` são os únicos; `mutateFormDataBeforeFill()`/`mutateFormDataBeforeSave()` são protegidos.

### Vendor — Filament 5.10.2
- `vendor/filament/schemas/src/Schema.php:deferLoading():152` guarda a condição; `:renderEmbeddedHtml():175`, `:177` devolve o placeholder enquanto `isDeferredSchemaLoaded()` é falso; `:renderDeferredLoadingHtml():261` monta o placeholder com `wire:partial="schema.{key}"` e `aria-busy`.
- `vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:loadDeferredSchema():138` é a ação Livewire que renderiza a parte; `:isDeferredSchemaLoaded():220` exige chave única (`:LogicException:225`, `:229` — sem chave ou com chave repetida) e marca como carregado o schema cujo campo tem erro (`:238-258`).
- `vendor/filament/schemas/resources/js/index.js:init:123`, `:loadDeferredSchema:128`, `:140`: `IntersectionObserver` → `$wire.loadDeferredSchema(schemaKey)`, desconecta ao carregar.
- `vendor/filament/schemas/src/Components/Concerns/CanBeCollapsed.php:collapsible():32`, `:collapsed():16` (também torna colapsável), `:persistCollapsed():44`; `vendor/filament/schemas/src/Components/Section.php:toEmbeddedHtml():263` renderiza o estado em Alpine (`$persist(...).as('filament:section:collapsed:' + id)` em `:364`, `x-on:expand` em `:368`, classe `fi-collapsed` em `:375`); `vendor/filament/schemas/src/Components/Concerns/HasId.php:id():11`.
- `vendor/filament/schemas/resources/js/index.js:handleFormValidationError:153`, `:159`: dispara `expand` nos ancestrais do primeiro `[data-validation-error]` — abre seção colapsada e aba.
- `vendor/filament/schemas/src/Components/Tabs.php:persistTabInQueryString():140`, `:150-166`: a chave na URL é o slug do rótulo (`:93`), e valor desconhecido cai na primeira aba.
- Doc: `vendor/filament/schemas/docs/04-tabs.md:Deferring the loading of tab content:199`, `:214-233` — `Tab::make()->key('…')->schema(Schema::make()->components([...])->deferLoading())`, `Tabs::make()->key('…')`; toda schema adiada exige chave única.

### Testes que exercitam a tela (regressão)
- Componente: `tests/Kit/ConfiguracoesDoKitTelaTest.php` (28 `it()`, `fillForm`/`assertFormFieldExists`), `tests/Kit/SegredosDoSettingsTest.php` (`:it:579` confere a estrutura das seções de provedor, `isCollapsed()` e `Rodapé da tela de login` em seção própria — `:598-615`), `tests/Kit/CabecalhoDoPainelTelaTest.php:it:204` (`assertSee('Cabeçalho dos painéis')`), mais 20 arquivos com `fillForm` na Page (lista completa no despacho #1).
- Navegador: `tests/Browser/ConfiguracoesDoKitTest.php` (`:it:76` troca de aba por clique em "Tabelas"; `:it:100` erro revelado na aba do campo), `tests/Browser/LoginSocialTest.php:it:152` (clica "Login" e "Entrar com GitHub"), `tests/Browser/AlertaDeAlteracoesNaoSalvasTest.php:it:38` (preenche `nome_da_aplicacao`).
- Capturas: `tests/BrowserTenancy/CapturaDeArteTest.php:it:937` e `:it:1085` clicam `'Kit'` (`:942`, `:1096`) — mudam para `'Aplicação'`; `:it:386` (aba Login), `:it:528` (anti-robô, registra que a seção nasce aberta), `:capturarAbaTabelas():1314` (aba Tabelas).

### Docs
- "aba Kit"/"**Kit** tab" em 27 linhas de `docs/pt` e `docs/en` (`configuracoes-do-kit.md`, `dock-de-atalhos.md`, `multi-tenancy.md`, `comecar/atualizando-o-projeto.md`); README sem ocorrência; CHANGELOG histórico com 6 (não se reescreve, P-01).
- Imagens: `art/admin-configuracoes-seletor.png` e `art/admin-configuracoes-dock.png` mostram a aba "Kit" ativa → recapturadas.

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Chaves estáveis: `Tabs::key('configuracoes')`, `Tab::key('{aba}')` (`identidade`, `email`, `tabelas`, `registro`, `login`, `aplicacao`) e `Section::id('configuracoes-{aba}-{secao}')` em kebab-case — a chave do colapso em `localStorage` e a do schema adiado não derivam do rótulo, então renomear um rótulo não perde o estado | autorização existente (convenção de nomes do kit) | difícil de reverter (é só string) | sessão, 2026-10-15 |
| D2 | Toda seção leva `->collapsible()->persistCollapsed()`; nasce expandida, salvo as quatro de provedor social que continuam `collapsed()` (decisão da wiki `mais-provedores-sociais`) | Q1 (recomendação implementada; resposta contrária é Adendo) | surpreendente | sessão, 2026-10-15 |
| D3 | A seção *Servidor SMTP* fica `->visible()` só com `mail_mailer === 'smtp'`, além do `visible()` que cada campo já tem — uma seção vazia com cabeçalho é pior que nenhuma | autorização existente | trade-off | sessão, 2026-10-15 |
| D4 | O enforcement da rule é um CT estrutural de componente que percorre todos os campos do formulário e exige: pai imediato `Section`, com ícone, `isCollapsible()` e `id()` preenchido — o teste é o guarda; a rule aponta para ele | autorização existente (escada de enforcement da `requirement-to-rule`) | surpreendente | sessão, 2026-10-15 |
| D5 | Adiamento em todas as seis abas (não só nas pesadas): o custo é um request por aba aberta, e a regra única é mais fácil de manter que uma lista | autorização existente | trade-off | sessão, 2026-10-15 |
| D6 | As duas capturas que mostram a aba são refeitas pelo `composer art` (não editadas); os nomes publicados não mudam | autorização existente | nenhum | sessão, 2026-10-15 |

## Autorização

- **Policies / Gates / Middleware / Guards**: nenhuma mudança. A tela continua atrás de `ExigePermissaoDaTela` (`page_ConfiguracoesDoKit`), e `loadDeferredSchema()` roda dentro do mesmo componente, sob a mesma permissão.

## Rotas

Nenhuma rota nova. `GET /admin/configuracoes-da-aplicacao` continua; a aba ativa continua em `?tab=` (a chave da aba renomeada passa de `kit::tabs` para `aplicacao::tabs` — Q3).

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `App\Filament\Admin\Pages\ConfiguracoesDoKit` — seis abas, agora com toda seção com ícone e cabeçalho clicável para colapsar/expandir, e com o conteúdo de cada aba carregado ao abri-la | Filament (`SettingsPage`, Livewire) | `/admin/configuracoes-da-aplicacao` | troca de aba (o conteúdo aparece depois de um request); clica no cabeçalho de uma seção para colapsar/expandir; recarrega e a seção fechada continua fechada; preenche e grava | **Sim** — carregamento adiado (`IntersectionObserver` + `$wire.loadDeferredSchema`), colapso (Alpine) e persistência (`localStorage`) só existem no navegador |

**Gate de CT-B**: três cenários só o navegador prova — (1) a aba inativa não tem os campos no HTML inicial e os ganha ao ser selecionada; (2) o colapso de uma seção sobrevive ao recarregamento; (3) erro de validação em campo de seção colapsada abre a seção (o CT-B02 da ancestral já prova a abertura da aba). O resto — estrutura das seções, rótulo da aba, gravação com aba adiada — é costura de componente e vai para o `04`.

**Gate de tela de escrita**: tela de escrita (`save()` do `SettingsPage`); o `04` precisa de cenário de gravação por componente **com a aba do campo adiada e nunca carregada** (P-04), além dos que a ancestral já tem.

## Variáveis de Ambiente

Nenhuma nova. Nenhuma chave de `.env` ou de settings muda.

## Eventos / Listeners / Observers

Nenhum novo. O listener `AuditarConfiguracoesDoKit` continua recebendo o mesmo `save()`.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | **1 + 1 por aba aberta**: o `GET` renderiza as seis abas como placeholder (a ativa carrega no primeiro `loadDeferredSchema` ao entrar na viewport, logo após o mount); cada aba selecionada custa um request Livewire que renderiza só a parte `schema.{chave}`. Hoje: 1 request com o HTML das seis abas |
| O que é adiado, e por qual gatilho? | o HTML de cada aba, por `IntersectionObserver` sobre o painel da aba (ativa: na montagem; inativa: ao ser selecionada e ficar visível). O **estado** do formulário não é adiado: `$data` é preenchido inteiro no `mount()` |
| O que é memoizado **por request**? | `loadedDeferredSchemas` (array público do componente) guarda, por request Livewire, quais abas já foram renderizadas; o Livewire o serializa no snapshot, então a aba carregada não é recarregada a cada interação |
| O que é cacheado **entre** requests? | nada no servidor. No navegador: o estado colapsado de cada seção em `localStorage` (`filament:section:collapsed:{id}`) e a aba ativa na query string (já existia) |
| Custo do caminho principal | abrir a tela: as mesmas consultas de hoje (settings vêm do cache do spatie) com **menos HTML** — só a aba ativa; trocar de aba: +1 request Livewire sem consulta nova ao banco (o estado já está no componente). Medido no step 10 |

## Impacto em Features Existentes

- **Todas as features com campo na tela** (identidade, e-mail, tabelas, registro, login unificado, social, anti-robô, rodapé, dashboard dinâmico, dock, densidade, versão, organizações): os campos mudam de contêiner, não de nome — os `fillForm()` por componente continuam valendo. Regressão: os 28 arquivos de teste listados no despacho #1.
- **`SegredosDoSettingsTest:it:579`**: confere que a seção de cada provedor é filha direta de *Login social* e nasce `collapsed()` — a hierarquia é preservada (D2).
- **`CapturaDeArteTest`**: dois cenários clicam `'Kit'` → `'Aplicação'`; as duas imagens são recapturadas (D6). O cenário do anti-robô registra que a seção nasce aberta — continua.
- **Browser `ConfiguracoesDoKitTest`**: a troca de aba passa a depender de um request Livewire; as asserções do plugin reexecutam até 45 s, então o oráculo continua, mas o tempo cresce. O CT-B02 ancestral (erro revelado na aba) ganha o mecanismo novo: a aba com erro é marcada carregada antes do render.
- **Docs pt/en**: 27 menções à aba Kit viram "Aplicação" (P-01); a tabela de abas de `configuracoes-do-kit.md` muda o nome e as seções novas entram na descrição.
- **Rules**: `.ai/rules/pages.md` ganha a seção da convenção (RQ-04); o glob `app/Filament/Admin/Pages/**` já aponta para ele.

## Rollback

- Sem migration. Reverter é restaurar `ConfiguracoesDoKit.php`, os testes e as docs do commit anterior; o estado colapsado gravado em `localStorage` do navegador fica inerte.

## Dependências

- **Composer**: nenhuma nova — `filament/filament` 5.10.2 já instalado tem `Schema::deferLoading()`, `CanBeCollapsed::persistCollapsed()` e `HasId::id()`.
- **NPM**: nenhuma.

## Riscos

- **Chave repetida ou ausente num schema adiado** → `LogicException` no render (`vendor/filament/schemas/src/Concerns/InteractsWithSchemas.php:LogicException:225`, `:229`): mitigação — o CT que renderiza a tela já estoura nesse caso *(alterado em 2026-10-15: Ponytail cortou o CT por reflexão)*.
- **Teste de navegador mais lento** (um request por aba): mitigação — o plugin já reexecuta asserções; o CT-B novo da aba adiada afirma sobre o placeholder e sobre o campo depois do clique, sem `wait()`.
- **Seção `visible()` com `Get`** (D3) avaliada no render: se a closure não resolver, a seção some com os campos — CT de componente com `mail_mailer = smtp` e `log`.
- **Captura de arte** depende de `npm run build` + navegador + ffmpeg no PATH: mitigação — mesmo roteiro da rodada 3 (`composer art`, PATH do ffmpeg exportado).

## Channel de Log da Feature

### Verificação de Channel Existente

`grep -n "Log::channel(" app/Filament/Admin/Pages/ConfiguracoesDoKit.php` → nenhuma ocorrência; a trilha da tela é o listener `AuditarConfiguracoesDoKit` (channel `configuracoes`, `config/logging.php`).

### Decisão

**Sem log novo.** A feature reorganiza a apresentação do formulário e não executa lógica nova no servidor além do `loadDeferredSchema()` do framework; o `save()` e a trilha de auditoria não mudam. Log de render de aba seria ruído sem leitor.

## Estrutura de Implementação

### 1. Rótulo da aba: "Kit" → "Aplicação"

> Skills: `livewire-development`, `pest-testing`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — `abaKit()` passa a `abaAplicacao()` e `Tab::make('Aplicação')`, mesmo ícone (`heroicon-o-squares-2x2`), mesma posição (última). Docblock do método explica o nome antigo e a chave `aplicacao` (D1).
- `tests/BrowserTenancy/CapturaDeArteTest.php`: os dois `->click('Kit')` (`:942`, `:1096`) viram `->click('Aplicação')`; comentários "aba Kit" idem.
- **Atende**: RQ-01, P-02
- **Logs**: nenhum.

### 2. Toda aba com campos soltos ganha seções; toda seção ganha ícone, colapso, persistência e id

> Skills: `livewire-development`, `tailwindcss-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- Um helper privado `secao(string $aba, string $chave, string $titulo, string $icone): Section` devolve `Section::make($titulo)->icon($icone)->collapsible()->persistCollapsed()->id("configuracoes-{$aba}-{$chave}")`; as seções existentes passam por ele (mantendo `description()`, `columns()`, `collapsed()` e ícone de status onde já há). As seções de `secaoDoProvedor()` ganham `persistCollapsed()` e `id()` (`configuracoes-login-provedor-{slug}`), mantendo `collapsed()` e o ícone de status.
- Agrupamento: `## Mapeamentos` › *Seções por aba* (Q2). Nenhum campo muda de nome, rótulo, `helperText`, `visible()`, regra ou `dehydrated()`.
- `Proteção anti-robô` troca `collapsible()` solto pelo helper (ganha ícone `heroicon-o-shield-check`, persistência e id).
- A seção *Servidor SMTP* recebe `->visible(fn (Get $get): bool => $get('mail_mailer') === 'smtp')` (D3); os `visible()` dos campos ficam — o `dehydrated()` da senha depende deles. *(alterado em 2026-10-15: Ponytail fundiu o passo 3 aqui)*
- **Atende**: RQ-02, RQ-03, P-05
- **Logs**: nenhum.

### 3. Carregamento adiado das seis abas

> Skills: `livewire-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — `Tabs::make('configuracoes')->key('configuracoes')`; cada `abaX()` devolve `Tab::make('…')->key('{aba}')->icon(…)->schema(Schema::make()->components([...])->deferLoading())`. Um helper privado `aba(string $chave, string $rotulo, string $icone, array $componentes): Tab` concentra isso.
- `use Filament\Schemas\Schema;` já importado (`form(Schema $schema)`).
- **Atende**: RQ-05, P-03, P-04
- **Logs**: nenhum.

### 4. Docs pt/en

> Skills: —

- **Path**: `docs/pt/recursos/configuracoes-do-kit.md`, `docs/en/recursos/configuracoes-do-kit.md` (tabela de abas: "Kit" → "Aplicação"; parágrafo novo sobre seções colapsáveis com estado lembrado e abas carregadas ao abrir; nome das seções novas onde o texto descreve os campos), `docs/pt|en/recursos/dock-de-atalhos.md`, `docs/pt|en/recursos/multi-tenancy.md`, `docs/pt|en/comecar/atualizando-o-projeto.md` (só "aba Kit" → "aba Aplicação").
- `CHANGELOG.md` › `[Unreleased]` › *Alterado*: a reorganização, o rótulo novo e o adiamento; nota de que o estado colapsado é por navegador.
- **Atende**: RQ-01 (P-01), RQ-02, RQ-03, RQ-05
- **Logs**: nenhum.

### 5. Capturas refeitas

> Skills: —

- `composer art` (com o ffmpeg no PATH) regrava `admin-configuracoes-seletor` e `admin-configuracoes-dock` com a aba "Aplicação"; nomes e `KitArte::IMAGENS` inalterados (D6).
- **Atende**: RQ-01 (P-01)
- **Logs**: nenhum.

### 6. Rule do projeto

> Skills: `requirement-to-rule`

- **Path**: `.ai/rules/pages.md` (glob `app/Filament/Admin/Pages/**` já no índice) — seção *Campo da tela de configurações vive numa Section com ícone, colapsável e com id estável*, apontando para o CT estrutural como enforcement (D4). Gravada no step 12 pela `requirement-to-rule`, com a aprovação do solicitante.
- **Atende**: RQ-04
- **Logs**: nenhum.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação: reutilizar as seções e o `Tab` de hoje, um helper por repetição (seção, aba), nada de abstração além dos dois helpers; sem CSS novo (o colapso e o ícone são do Filament). Atalhos deliberados levam `ponytail:`. Após implementar, `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** na comunicação; arquivos wiki, código, commits e PRs em prosa normal.
>
> **Baseline antes do primeiro commit**: suíte completa na `main` `b0091516` (árvore idêntica à desta branch no início): Kit 4 172/4 175 verdes (3 pulados) em `3bb21c8a` + CT-82 2/2 no commit seguinte, Tenancy 631/631 — registrada em 2026-10-15 na wiki `estudo-de-pacotes-rodada-3`; nenhuma falha pré-existente.

## Mapeamentos

### Seções por aba (proposta — Q2)

Nome, rótulo e linha de cada campo vêm do inventário do despacho #1 sobre `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`. `id` = `configuracoes-{aba}-{secao}`.

| Aba (chave) | Seção (id) | Ícone | Campos, na ordem | Estado inicial |
|---|---|---|---|---|
| Identidade (`identidade`) | Nome e versão (`nome-e-versao`) | `heroicon-o-identification` | `nome_da_aplicacao`, `versao_do_sistema` | expandida |
| | Cor primária (`cor-primaria`) | `heroicon-o-swatch` | `cor_primaria`, `cor_primaria_hex` | expandida |
| | Logo, favicon e arte (`marca`) | `heroicon-o-photo` | `unifica_logo_marca`, `logo`, `logo_dark`, `favicon`, `arte_do_login` | expandida |
| | Cabeçalho dos painéis (`cabecalho`) — existente | `heroicon-o-window` | os cinco `cabecalho_*` (mantém `columns(2)`) | expandida |
| E-mail (`email`) | Remetente (`remetente`) | `heroicon-o-paper-airplane` | `mail_mailer`, `mail_from_address`, `mail_from_name` | expandida |
| | Servidor SMTP (`smtp`) | `heroicon-o-server` | `mail_host`, `mail_port`, `mail_scheme`, `mail_username`, `mail_password` | expandida; seção visível só com SMTP (D3) |
| Tabelas (`tabelas`) | Paginação e aparência (`aparencia`) | `heroicon-o-table-cells` | `paginacao_padrao`, `tabela_listrada`, `persistir_filtros`, `colunas_redimensionaveis` | expandida |
| | Carregamento e visões (`carregamento-e-visoes`) | `heroicon-o-bookmark-square` | `tabela_carregamento`, `visoes_de_tabela`, `visoes_de_tabela_compartilhadas` | expandida |
| Registro (`registro`) | Cadastro sem convite (`cadastro`) | `heroicon-o-user-plus` | `registro_habilitado`, `registro_aprovacao_manual` | expandida |
| | E-mail verificado (`email-verificado`) | `heroicon-o-envelope-open` | `registro_verificar_email` | expandida |
| Login (`login`) | Página única de login (`pagina-unica`) — existente | `heroicon-o-arrow-right-on-rectangle` | `login_unificado` | expandida |
| | Login social (`social`) — existente | `heroicon-o-user-group` | `login_vinculo_confirmar` + as quatro seções de provedor (`provedor-{google,github,linkedin-openid,x}`, ícone de status, **colapsadas**) | expandida (filhas colapsadas) |
| | Proteção anti-robô (`anti-robo`) — existente | `heroicon-o-shield-check` | os seis `login_anti_robo_*` | expandida |
| | Rodapé da tela de login (`rodape`) — existente | `heroicon-o-document-text` | `login_rodape` | expandida |
| Aplicação (`aplicacao`) — ex-Kit | Comportamento dos painéis (`paineis`) | `heroicon-o-adjustments-horizontal` | `hub_de_navegacao`, `alerta_alteracoes_nao_salvas`, `densidade_do_layout`, `exibir_versao_do_kit` | expandida |
| | Dashboard dinâmico (`dashboard`) — existente | `heroicon-o-squares-plus` | `dashboard_dinamico_habilitado`, `dashboard_dinamico_paineis` | expandida |
| | Dock de atalhos (`dock`) — existente | `heroicon-o-bolt` | aviso de pacote ausente, `dock_paineis`, `dock_layout`, `dock_estilo` | expandida |
| | Organizações (`organizacoes`) | `heroicon-o-building-office-2` | `rotulo_da_organizacao`, `rotulo_das_organizacoes`, `ocultar_seletor_unico` | expandida |

### Chaves das abas (D1)

| Aba | `Tab::key()` | Chave na URL (`?tab=`) |
|---|---|---|
| Identidade | `identidade` | `identidade::tabs` |
| E-mail | `email` | `e-mail::tabs` (slug do rótulo, inalterado) |
| Tabelas | `tabelas` | `tabelas::tabs` |
| Registro | `registro` | `registro::tabs` |
| Login | `login` | `login::tabs` |
| Aplicação | `aplicacao` | `aplicacao::tabs` (antes `kit::tabs` — Q3) |

## Testes

> Ver `04-casos-de-teste.md` para os cenários de backend e `05-casos-de-teste-browser.md` para os de navegador.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest tests/Kit/ConfiguracoesSecoesColapsaveisTest.php --compact` + regressão dos 28 arquivos que preenchem a tela
- [ ] `vendor/bin/pest tests/Browser/ConfiguracoesSecoesColapsaveisTest.php` (CT-B)
- [ ] `composer art` com as duas capturas conferidas
