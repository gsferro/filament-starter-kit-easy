# Plano de Ação — feat/logo-dark-mode: Suporte a logo dark / light com unificação de campo

> Requisito: `00-requisito.md` (Adendo 1 incorporado: Q1–Q4 respondidas pela recomendação)

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feature/identidade-visual-da-organizacao/identidade-visual-da-organizacao/` — feature que introduziu `Tenant::urlDaLogo()`, a logo no `TenantForm` e a aplicação da logo na `TelaBloqueio`
- **Motivo**: a feature nova estende a mesma superfície (campo de logo da organização, resolução de URL, lock-screen) com a variante dark e o toggle de unificação
- **Toca infra compartilhada?**: não — migration nova em `tenants`, propriedades novas nas settings (mesmo grupo `kit`, sem mexer nas existentes), CSS novo arquivo à parte

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | `logo_dark` nullable no tenant | 1, 2 | Tabela real: `tenants` (P-01) |
| RQ-02 | Toggle `unifica_logo_marca` default `true` | 3, 4, 5 | Chave `kit.identidade.*` (P-02) |
| RQ-03 | Toggle `true` → só `logo` | 4, 5, 8 | `logo_dark` escondido e inerte |
| RQ-04 | Toggle `false` → dois campos obrigatórios entre si | 4 | `required_with` — ambos vazios válido (Adendo 1, Q2) |
| RQ-05 | Remover `fi-auth-media` + wrapper flex + `contain` na logo | 7, 8 | **Só lock-screen** (Adendo 1, Q1); arte do login fica `cover` |
| RQ-06 | Escolha unificada / light / dark / tenant override | 6, 7, 8 | Troca por CSS no cliente (ADR-01); topbar nos 3 painéis via `darkModeBrandLogo` (Adendo 1, Q3) |
| RQ-07 | `<img>` no wrapper com alt | 8 | Alt = nome da organização / "Logo da marca" (P-05) |
| RQ-08 | Branch `feat/logo-dark-mode` | — | ✅ já criada (step 2) |
| RQ-09 | Docs pt + en, seção nova | 10 | `docs/pt/recursos/configuracoes-do-kit.md` + EN |
| RQ-10 | Screenshots light/dark | 9 | `art/logo-tema-{claro,escuro}.png` *(alterado em implementação — a convenção real das docs é `art/` + URL raw.githubusercontent; CT-20 ajustado)* |
| RQ-11 | `LogoDarkModeTest` (3 casos) | 11 | Ver `04-casos-de-teste.md` |
| RQ-12 | filacheck + pint + commit + PR | 12 | Gate do step 11 da wiki |

## Objetivo

Permitir que a marca tenha uma variante de logo para modo escuro — tanto a logo da instalação (Settings do kit) quanto a da organização (tenant) — mantendo o comportamento atual quando o toggle `unifica_logo_marca` está ligado (default). Corrige de quebra a distorção da logo na lock-screen: `fi-auth-media` força `width/height:100%` com `object-fit:cover`, que corta e estica a imagem; a logo passa a `object-fit:contain` centralizada.

## Contexto

Hoje existe um único campo `logo` (light) na identidade do kit e no `Tenant`. A `TelaBloqueio` injeta a logo da organização como `media` do `AuthDesignerConfig`, e a partial `filament-auth-designer::components.partials.media` aplica a classe `fi-auth-media` (`100% × 100%`, `object-fit:cover`) — a mesma classe correta para a `arte_do_login`, mas errada para uma logo com proporção própria.

O Filament resolve o tema **no cliente** (a classe `dark` no `<html>`, cobrindo escolha manual e "Sistema"). O servidor não sabe se a tela está escura — `prefersDarkMode()` só existe com Client Hint que os navegadores não mandam por padrão. O padrão nativo do Filament (`darkModeBrandLogo` + `logo.blade.php`) renderiza os dois `<img>` e o CSS troca pela classe `.dark`; a feature adota o mesmo mecanismo (ADR-01).

## Análise dos Arquivos Existentes

<!-- Raia fato da entrevista: o que o agente descobriu no código, sem perguntar ao usuário. -->

### `app/Settings/ConfiguracoesDoKit.php`
Três pontos por propriedade (docblock da classe): `public ?string $logo`, linha `'logo' => 'kit.identidade.logo'` em `mapaDeConfiguracao()`, e entrada na settings migration. Recebe `?string $logo_dark` e `bool $unifica_logo_marca`.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
Aba Identidade usa o helper `arquivo(nome, rotulo, ajuda): FileUpload` (disk `public`, dir `kit`, `->image()`, sem SVG, teto `TetoDeUpload`). **O docblock do helper registra que `->helperText()` encadeado sobrescreve** o texto com teto/SVG — a ajuda condicional não pode ser closure encadeada; resolve por argumento estático (D7). O toggle e o campo `logo_dark` entram aqui com `->live()`/`->visible()`/`->required()` (closures `Get` já são o padrão do arquivo, ex.: `abaEmail()`).

### `config/kit.php`
`identidade` tem `logo`, `favicon`, `arte_do_login` (todos `null`). Booleanos usam `BooleanoDoEnv::comPadrao(env('KIT_...'), $default)` — padrão que a chave nova segue. `.env.example` ganha `# KIT_UNIFICA_LOGO_MARCA=true` comentada.

### `app/Support/IdentidadeDoKit.php`
`logo()` → `doDisco('kit.identidade.logo')` — resolve o caminho gravado para URL pública com guarda de arquivo ausente (loga no channel `configuracoes`). `logoEscura()` espelha em `kit.identidade.logo_dark`, e `unificaLogo()` lê o toggle. *(alterado em implementação — `doDisco()` passou de `$disco->url()` para `asset('storage/'.$caminho)`: a URL do disk é `APP_URL` congelado e quebrava a `<img>` quando o host do request difere — proxy, staging, servidor do browser test. Mesma correção que `Tenant::urlDaLogo()` já tinha; mata a lacuna M4 do `04`.)*

### `app/Models/Tenant.php`
`logo` no `$fillable`; `urlDaLogo()` devolve `asset('storage/'.$this->logo)` com guarda `Storage::disk('public')->exists()`. `logo_dark` entra no fillable/docblock e `urlDaLogoEscura()` espelha o método.

### `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
`FileUpload::make('logo')` em `organizacoes/logos`, opcional. `logo_dark` entra como segundo upload opcional (P-03), visível só quando a marca está separada por modo (D2).

### `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`
Os três fazem `->brandLogo(fn (): ?string => IdentidadeDoKit::logo())` — closures avaliadas por request, então `->darkModeBrandLogo(fn () => IdentidadeDoKit::logoEscura())` resolve a cada render (resposta Q3).

### `app/Filament/Pages/Auth/TelaBloqueio.php`
`getAuthDesignerConfig()` resolve tenant por `session('tenant_corrente')` + guarda de painel `app`, aplica `urlDaLogo()` como `media` e loga o motivo no channel `tenancy`. Estende com a variante escura por variante (tenant.logo_dark ?? kit.logo_dark) e expõe `urlDaLogoEscura()` para a view.

### `vendor/caresome/filament-auth-designer/resources/views/components/partials/media.blade.php`
Partial `@props(['config','imageClass','videoClass'])` — `<img>` com `class="{{ $imageClass }}"` (`fi-auth-media`) ou `<video>`. Sobreposta em `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php` (Laravel resolve `resources/views/vendor/{namespace}` antes do pacote). O override bifurca: `$livewire instanceof TelaBloqueio` → bloco de logo (wrapper + até 2 imgs); senão → markup original do vendor. `$livewire` está no escopo porque o `@include` herda as variáveis da `layouts/auth.blade.php`, que já chama `$livewire->getAuthDesignerConfig()`.

### `vendor/.../layouts/auth.blade.php` e `auth-designer.css`
`.fi-auth-media` = `height/width:100%; object-fit:cover`. Intocado: o override é só na partial, e o ramo não-logo mantém `fi-auth-media` (Adendo 1, Q1).

### `database/settings/*_kit_settings.php`
Padrão: `SettingsMigration` + `$this->migrator->inGroup('kit', ...)` com `$blueprint->add()` puxando o default do `config()`.

## Decisões de Desenho

<!-- Raia desenho. Q5 e Q6 respondidas por "siga as recomendações" (mensagem do dev, 2026-10-03). -->

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | Troca light/dark por **CSS** (dois `<img>` + classe `.dark`), não por leitura server-side — o servidor não conhece o tema "Sistema" e o Filament não tem `isDarkMode()` | Q5 | difícil de reverter | dev, 2026-10-03 → vira **ADR-01** junto com o override da partial |
| D2 | `logo_dark` no `TenantForm` fica `->visible()` só quando `kit.identidade.unifica_logo_marca === false` — campo inerte escondido, como na página de settings | — | difícil de reverter, surpreendente | sessão, 2026-10-03 |
| D3 | Variante dark da logo resolve **por variante**: `tenant.logo_dark ?? kit.logo_dark`, sem cair para `tenant.logo` — espelha o pseudocódigo do requisito; quando a dark resolve `null`, renderiza-se só a light (que serve aos dois modos) | — | difícil de reverter | sessão, 2026-10-03 |
| D4 | **Reutilizar `fi-logo` + `fi-logo-light`/`fi-logo-dark` nativas** — verificadas no CSS compilado (`public/css/filament/filament/app.css`: `.fi-logo.fi-logo-light:where(.dark){display:none}` e `.fi-logo.fi-logo-dark` inverso). A base `fi-logo` traz margens de header, neutralizadas por `margin:0` inline na `<img>` — zero CSS novo e mesmo comportamento da topbar *(alterado no step 5: confirmada a regra no CSS publicado)* | — | difícil de reverter | sessão, 2026-10-03 |
| D5 | Screenshots gerados por CT-B (pest-plugin-browser `inDarkMode()`/light) e copiados a `art/` *(alterado em implementação — `screenshotElement` da mídia da lock-screen, convenção `art/` + raw.githubusercontent; `inDarkMode()` só vale no load da página, então o CT-B01 faz uma visita por tema)* | — | difícil de reverter | sessão, 2026-10-03 |
| D7 | Helper dos campos de logo é **estático por argumento** de `arquivo()`, não closure encadeada — o docblock do helper registra que `->helperText()` encadeado sobrescreveria o sufixo com teto/SVG. O texto do `logo` cobre os dois modos ("no modo com logos separadas, esta é a do tema claro — fundo claro recomendado") | — | surpreendente | sessão, 2026-10-03 |
| D6 | Chave do toggle em `kit.identidade.unifica_logo_marca` (não `kit.unifica_logo_marca`) — identidade já é dona de `kit.identidade.*` | Q6 | surpreendente | dev, 2026-10-03 |

## Autorização

- **Policies**: nenhuma nova — `/admin/organizacoes` e `/admin/configuracoes-da-aplicacao` já exigem `admin`
- **Gates**: n.a.
- **Middleware**: os das rotas existentes (lock-screen exige `auth` + `lockscreen` na sessão)
- **Guards**: n.a.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/admin/organizacoes/{record}/edit` | `filament.admin.resources.organizacoes.edit` | `auth`, `can:update` |
| GET | `/admin/configuracoes-da-aplicacao` | `filament.admin.pages.configuracoes-do-kit` | `auth` |
| GET | `/app/{tenant}/lockscreen` | `lockscreen.app.page` | `auth` |
| GET | `/admin/login` | `filament.admin.auth.login` | — |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Aba Identidade — toggle `unifica_logo_marca` + campo `logo_dark` | Filament | `/admin/configuracoes-da-aplicacao` | liga/desliga mostra o campo dark; upload dos dois arquivos | Sim (`->live()` re-render) |
| `TenantForm` — campo `logo_dark` | Filament | `/admin/organizacoes/*/edit` | upload opcional, visível só no modo separado | Sim (visibilidade por config) |
| Lock-screen — logo light/dark | Livewire + Blade | `lockscreen.*.page` | nenhuma — renderiza o par de imgs conforme o tema | Sim (classe `.dark` no `<html>`) |
| Topbar dos 3 painéis | Filament (nativo) | qualquer página de painel | nenhuma — `fi-logo-light`/`fi-logo-dark` nativos | Sim |

**Gate de CT-B**: a troca de logo por tema **só o navegador prova** (classe `.dark` aplicada ao `<html>` pelo JS do Filament) — é costura `browser` e justifica o `05`.

**Gate de tela de escrita**: as duas telas de edição (`configuracoes-do-kit`, `organizacoes/edit`) recebem cenário de gravação por componente no `04`.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_UNIFICA_LOGO_MARCA` | `true` | `false` separa a marca em `logo` (light) + `logo_dark` (dark) |

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum
- **Listeners**: nenhum
- **Observers**: nenhum

## Jobs / Queues

- n.a. — um request, sem trabalho adiado

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | 1 por render — o mesmo de hoje |
| O que é adiado, e por qual gatilho? | nenhum |
| O que é memoizado **por request**? | `AuthDesignerConfig` resolvido pelo repositório do pacote (cache interno por request) |
| O que é cacheado **entre** requests? | nada novo — `Storage::exists()` é stat local no `public`, sem cache de propósito (ponytail já declarado na classe) |
| Custo do caminho principal | lock-screen: 1 `Tenant::find` (existente) + até 2 stats de disco; topbar: +1 stat quando `logo_dark` configurada |

## Impacto em Features Existentes

- **Identidade visual da organização** (wiki ancestral): `Tenant::urlDaLogo()` continua intacta; `logo_dark` é coluna nova, não altera a leitura atual.
- **Login / arte de autenticação**: o ramo não-logo do override preserva byte a byte o markup do vendor — arte continua `fi-auth-media` `cover`.
- **Settings do kit**: duas propriedades novas no grupo `kit`; o alinhamento config↔settings passa a incluí-las via `mapaDeConfiguracao()`.
- **Upgrade do `caresome/filament-auth-designer`**: o override da partial pode divergir do original — mitigado por CT que assere o contrato (`@props` + markup não-logo preservado).

## Rollback

- **Migration down**: `down()` remove `tenants.logo_dark` e as duas settings (`logo_dark`, `unifica_logo_marca`) do grupo `kit`
- **Feature flag**: `unifica_logo_marca=true` (default) torna `logo_dark` inerte em toda superfície
- **Reversão de dados**: campos `nullable`, sem backfill — nada a reverter

## Dependências

- **Composer**: nenhuma nova
- **NPM**: nenhuma nova

## Riscos

- **Override da partial diverge do vendor no upgrade**: mitigado por CT de contrato no `04` + ADR-01 documentando a decisão
- **Toggle desligado com logo já gravado**: `required_with` passa a exigir o par — é a semântica aceita no Adendo 1 (Q2); helper text orienta
- **CSS do swap não carrega** (ex.: cache de assets): os dois `<img>` apareceriam juntos — o seletor `.kit-logo-dark { display: none }` vem primeiro e é o fallback seguro; guarda CSS segue o padrão de `css-filament.md` se a feature test-design julgar necessária *(não foi — as regras nativas `.fi-logo-light`/`.fi-logo-dark` já estão em `app.css`; `kit.css` não foi tocado)*

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php` tem `tenancy` (usado por `TelaBloqueio`) e `configuracoes` (usado por `IdentidadeDoKit` e pela página de settings) — ambos já cobrem os pontos desta feature

### Decisão

- **Sem channel novo**: a feature é identidade visual — logs de resolução de logo ficam em `tenancy` (lock-screen) e `configuracoes` (settings/disco), mantendo a convenção existente

## Estrutura de Implementação

### 1. Migration `logo_dark` em `tenants` + Settings migration

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_10_03_100000_add_logo_dark_to_tenants_table.php`, `database/settings/2026_10_03_100000_add_logo_dark_e_unifica_marca_to_kit_settings.php`
- Coluna `logo_dark` string nullable depois de `logo`; blueprint `inGroup('kit')` com `add('logo_dark', config('kit.identidade.logo_dark'))` e `add('unifica_logo_marca', (bool) config('kit.identidade.unifica_logo_marca', true))`; `down()` faz `delete` das duas
- **Atende**: RQ-01, RQ-02

### 2. `Tenant` model + `TenantForm`

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/Tenant.php`, `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- `logo_dark` no `$fillable` e no `@property`; `urlDaLogoEscura(): ?string` espelhando `urlDaLogo()` (guarda `exists()` + `asset('storage/')`)
- `FileUpload::make('logo_dark')` mesmas regras do `logo` (`->image()`, `organizacoes/logos`, `TetoDeUpload`), `->visible(fn () => ! config('kit.identidade.unifica_logo_marca'))`, helper "fundo transparente recomendado" (D2)
- **Atende**: RQ-01
- **Logs**: nenhum (campo de form)

### 3. `config/kit.php` + `.env.example` + Settings class

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`, `.env.example`, `app/Settings/ConfiguracoesDoKit.php`
- `identidade.logo_dark => null` e `identidade.unifica_logo_marca => BooleanoDoEnv::comPadrao(env('KIT_UNIFICA_LOGO_MARCA'), true)`; `.env.example` com linha comentada
- `public ?string $logo_dark;` e `public bool $unifica_logo_marca;` + duas linhas em `mapaDeConfiguracao()` → `kit.identidade.logo_dark`, `kit.identidade.unifica_logo_marca`
- **Atende**: RQ-02, RQ-06

### 4. `IdentidadeDoKit`

> Skills: `laravel-best-practices`

- **Path**: `app/Support/IdentidadeDoKit.php`
- `logoEscura(): ?string` → `self::unificaLogo() ? null : self::doDisco('kit.identidade.logo_dark')` (toggle ligado torna a dark inerte — RQ-03); `unificaLogo(): bool` → `(bool) config('kit.identidade.unifica_logo_marca', true)`
- **Atende**: RQ-02, RQ-03, RQ-06
- **Logs**: `doDisco()` já loga arquivo ausente no channel `configuracoes` — herdado de graça

### 5. Aba Identidade da página de settings

> Skills: `laravel-best-practices`, `filament-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` (`abaIdentidade()`)
- `Toggle::make('unifica_logo_marca')->live()->default(true)` + `helperText` explicando os dois modos
- `arquivo('logo', ...)` ganha `->helperText(fn (Get $get) => $get('unifica_logo_marca') ? $ajudaOriginal : 'Fundo claro recomendado. ...')` e `->required(fn (Get $get) => ! $get('unifica_logo_marca') && filled($get('logo_dark')))`
- `arquivo('logo_dark', 'Logo para modo escuro', ...)` com `->visible(fn (Get $get) => ! $get('unifica_logo_marca'))` e `->required(fn (Get $get) => ! $get('unifica_logo_marca') && filled($get('logo')))` — obrigatórios entre si (Q2)
- **Atende**: RQ-02, RQ-03, RQ-04
- **Logs**: a página já loga gravações no channel `configuracoes` (padrão da classe)

### 6. `darkModeBrandLogo` nos 3 PanelProviders

> Skills: `laravel-best-practices`, `filament-development`

- **Path**: `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`
- `->darkModeBrandLogo(fn (): ?string => IdentidadeDoKit::logoEscura())` ao lado do `->brandLogo()` existente — closure por request, devolve `null` no modo unificado e o Filament renderiza um `<img>` só
- **Atende**: RQ-06 (Q3)

### 7. `TelaBloqueio` — resolução por variante

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Pages/Auth/TelaBloqueio.php`
- `urlsDasLogos(): array{clara: ?string, escura: ?string}` público, memoizado por `once()` — resolução única compartilhada com a view (step 5: sem ele a partial não saberia distinguir a logo da arte de fallback e aplicaria `contain` à arte):
  - `clara` = `$organizacao?->urlDaLogo() ?? IdentidadeDoKit::logo()`
  - `escura` = `IdentidadeDoKit::unificaLogo() ? null : ($organizacao?->urlDaLogoEscura() ?? IdentidadeDoKit::logoEscura())` — resolução por variante (D3)
- `getAuthDesignerConfig()` usa `urlsDasLogos()['clara']`: preenchida → `media` = clara (logo); `null` → comportamento atual (mídia base = arte, sem mexer em nada)
- A organização resolvida (`Tenant::find` + guards atuais) é extraída para um método privado memoizado — os dois públicos compartilham o mesmo `find` por request
- **Atende**: RQ-06
- **Logs**: `Log::channel('tenancy')->debug('[TelaBloqueio@getAuthDesignerConfig] …')` existente + linha no registro de sucesso com `logo_dark` aplicada ou não

### 8. Override da partial de mídia + CSS do swap

> Skills: `tailwindcss-development`, `laravel-best-practices`

- **Path**: `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php` (novo — override), ~~`resources/css/filament/kit.css`~~ *(alterado em implementação — classes nativas, CSS não necessário)*
- Partial: `$logos = method_exists($livewire, 'urlsDasLogos') ? $livewire->urlsDasLogos() : null`; `filled($logos['clara'])` — `method_exists` e não `instanceof` (step 6 — ponytail: o mesmo ramo fica aberto para qualquer página que implemente o par de URLs, sem acoplar a view ao FQCN da lock-screen) → `<div style="display:flex;justify-content:center;align-items:center;width:100%;height:100%">` com `<img class="fi-logo fi-logo-light" style="margin:0;max-width:100%;max-height:100%;object-fit:contain" alt="{{ $config->mediaAlt }}">` e, quando `escura` preenchida, `<img class="fi-logo fi-logo-dark">` igual; **em todo o resto** (arte, outras páginas) → markup idêntico ao do vendor (`fi-auth-media` preservado — Q1)
- Zero CSS novo: as classes `fi-logo-light`/`fi-logo-dark` já têm as regras do swap no `app.css` publicado do Filament (D4 revisado)
- **Atende**: RQ-05, RQ-06, RQ-07

### 9. Screenshots light/dark (geração)

> Skills: `pest-testing`

- **Path**: `tests/Browser/LogoDarkModeTest.php` (captura) → `art/logo-tema-{claro,escuro}.png` *(alterado em implementação)*
- Cenário visita `/app/screen/lock` com a marca separada configurada, uma visita por tema (`inDarkMode()` só vale no load — emulação mid-session não reavalia) e `->screenshotElement('.fi-auth-media-wrapper', filename:)` + cópia para `art/` *(alterado em implementação)*
- **Atende**: RQ-10
- **Bloqueado por**: passo 8 (precisa do swap funcionando)

### 10. Documentação pt + en

> Skills: nenhuma

- **Path**: `docs/pt/recursos/configuracoes-do-kit.md`, `docs/en/recursos/configuracoes-do-kit.md`
- Seção "Logo da marca: uma só, ou duas por tema": o toggle, os dois campos com as recomendações de fundo (claro para light, transparente para dark), fallback do tenant, screenshots embutidos por URL raw.githubusercontent para `art/logo-tema-{claro,escuro}.png` *(alterado em implementação)*
- **Atende**: RQ-09

### 11. `LogoDarkModeTest`

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Kit/LogoDarkModeTest.php` (+ CT-B em `tests/Browser/` do passo 9)
- Casos derivados pela feature-test-design no `04`: toggle ligado → login com um logo só; separado → `fi-logo-dark` com `logo_dark`; lock-screen usa a logo do tenant (e a dark quando separado); `urlDaLogoEscura()`/`logoEscura()` resolution; settings: campo visível/obrigatório-entre-si
- **Atende**: RQ-11

### 12. Finalização

> Skills: nenhuma

- `vendor/bin/filacheck --fix`, `vendor/bin/pint --format agent`, `composer test:kit` (baseline comparada), commit(s) semânticos, PR
- **Atende**: RQ-12

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.
>
> **Model novo declara `$table`** sempre que o nome da tabela não for o plural inglês que o
> Eloquent infere — com nome em pt-BR é sempre: `centros_custo`, não `centro_custos`.
>
> **Baseline antes do primeiro commit**: rodar a suíte completa em `{base}` e listar por nome as
> falhas pré-existentes. A `## Verificação Final` compara contra a baseline, não contra zero.

## Mapeamentos

### Resolução da logo por modo (RQ-06, D3)

| Toggle | Variante | Ordem de resolução |
|--------|----------|--------------------|
| `unifica_logo_marca=true` | única | `tenant.logo` → `kit.identidade.logo` |
| `unifica_logo_marca=false` | light | `tenant.logo` → `kit.identidade.logo` |
| `unifica_logo_marca=false` | dark | `tenant.logo_dark` → `kit.identidade.logo_dark` → *(null → só a light renderiza)* |

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários de UI (swap por tema — costura `browser`).

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest --filter=LogoDarkMode --compact` (CTs de backend)
- [ ] `vendor/bin/pest tests/Browser --filter=LogoDarkMode` (CT-B — swap por tema)
- [ ] `composer test:kit` — comparado à **baseline** de `main`: 3799 passaram, 3 falharam (`SiteDeDocumentacaoTest`: números dos readmes × 1, CT-25 rótulo de versão × 2 — pt/en), 3 pulados
- [ ] **Custo medido** — queries/stats do render da lock-screen contra o `## Modelo de Execução`
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)** — antes da reconciliação
- [ ] `vendor/bin/filacheck --fix` (RQ-12)

## Commits
- `:sparkles: logo: variante dark da marca com toggle de unificação`
- `:memo: docs: seção logo light/dark em configuracoes-do-kit (pt/en)`
- `:memo: wiki da feature logo-dark-mode`
