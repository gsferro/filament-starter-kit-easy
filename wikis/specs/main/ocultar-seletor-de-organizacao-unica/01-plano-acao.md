# Plano de Ação — ocultar-seletor-de-organizacao-unica

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: nenhuma — é a primeira feature a tocar a visibilidade do seletor de tenant. A vizinha mais próxima é `wikis/specs/feat/cabecalho-do-painel/` (as cinco opções do header que tornam o seletor redundante), referência de desenho, não ancestral
- **Motivo**: — (tipo nova)
- **Toca infra compartilhada?**: sim — `tests/BrowserTenancy/CapturaDeArteTest.php` e `App\Console\Commands\KitArte` (IMAGENS/CLIPES), que o `tests/Kit/KitArteTest.php` trava por varredura; e `config/kit.php` / `.env.example`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Opção nas configurações para ocultar o combo com um só tenant | 1, 2, 3, 4, 5, 6 | — |
| RQ-02 | Com a opção ativa e mais de um tenant, o combo segue exibido | 4, 5 | — |
| RQ-03 | Contagem é de tenants acessíveis, não de painéis | 4 | mesma fonte do seletor |
| RQ-04 | Um tenant + opção ativa = combo oculto (informação redundante) | 4, 5 | — |
| RQ-05 | Thumbs e gifs demonstrando o efeito do settings | 7, 8 | — |
| P-01 | Esconder o bloco inteiro do menu de tenant | 5 | `tenantMenu()`, não `tenantSwitcher()` |
| P-02 | Opção da instalação | 1–3, 6 | — |
| P-03 | Só UI; acesso por URL e permissões intactos | 5 | sem tocar `canAccessTenant()` |
| P-04 | Toggle só aparece com tenancy ligada | 6 | `->visible()` na aba Kit |

## Objetivo

Adicionar à tela `/admin/configuracoes-da-aplicacao` um interruptor que, quando ligado e o usuário tiver acesso a **uma única** organização ativa, esconde o seletor de organização do painel `/app` — informação redundante quando o cabeçalho já mostra marca, nome do projeto e nome do painel. Com duas ou mais organizações acessíveis, ou com o interruptor desligado, nada muda.

## Contexto

O kit liga multi-tenancy com `php artisan kit:tenancy` e o painel `/app` passa a `/app/{tenant}`, com o `x-filament-panels::tenant-menu` no topo da barra lateral. Desde `feat/cabecalho-do-painel` o topo já pode exibir logo, nome do projeto e nome do painel — quando o usuário só tem uma organização, o seletor repete o nome que o cabeçalho já mostra, sem oferecer escolha nenhuma (o Filament só lista *outros* tenants para troca).

O gancho oficial existe: `Panel::tenantMenu(bool|Closure)` (`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:tenantMenu():139-144`), avaliado **por request** no render da sidebar (`vendor/filament/filament/resources/views/livewire/sidebar.blade.php:$hasTenantMenu:11`) e da topbar (`vendor/filament/filament/resources/views/livewire/topbar.blade.php:hasTenantMenu():132`). `hasTenantMenu()` avalia a Closure em `$this->evaluate()` (`HasTenancy.php:hasTenantMenu():344-347`), então o settings gravado no banco governa na hora — sem decisor extra, satisfazendo `.ai/rules/settings.md`.

## Análise dos Arquivos Existentes

### `config/kit.php` (bloco `tenancy`, ~linha 398-411)
- `tenancy.enabled`, `tenancy.label`, `tenancy.label_plural`, `tenancy.slug`. A chave nova entra neste bloco, com env própria documentada.

### `app/Settings/ConfiguracoesDoKit.php`
- Propriedade bool nova no grupo `// Kit`, linha nova em `mapaDeConfiguracao()` apontando para `kit.tenancy.ocultar_seletor_unico`, e a migration correspondente — o contrato de três pontas da classe (propriedade, mapa, migration).

### `database/settings/`
- Padrão `add_{coisa}_to_kit_settings.php`: `$blueprint->add('prop', (bool) config('kit....', false))` com `down()` removendo. Referência: `2026_10_05_100000_add_cabecalho_to_kit_settings.php`.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` (`abaKit()`, ~linha 885-977)
- A aba Kit já abriga `rotulo_da_organizacao`/`rotulo_das_organizacoes` (voca bulário da instalação). O toggle novo vai junto deles, com `->visible()` condicionado a `config('kit.tenancy.enabled')` (P-04, mesmo critério de `login_anti_robo_local`).

### `app/Providers/Filament/AppPanelProvider.php` (linha 610-614)
- `if (config('kit.tenancy.enabled')) { $panel->tenant(Tenant::class, ...)->tenantMiddleware(...); }` — aqui entra `->tenantMenu(Closure)`.

### `app/Models/User.php` (`getTenants()`, linha 805-812)
- A fonte da contagem: `master_global` recebe todos os tenants `ativo`; os demais, os vínculos `ativo`. É exatamente a lista que alimenta o seletor (`FilamentManager::getUserTenants()` → `HasTenantMenu::getSwitchableTenants()`).

### `app/Support/`
- Padrão das classes-dona de decisão (`ConfiguracaoDoLogin`, `DensidadeDoLayout`, `CabecalhoDoPainel`, `RegistroAberto`): classe `final`, métodos estáticos, docblock explicando quem lê e quando. A nova `SeletorDeOrganizacao` segue o mesmo.

### `tests/BrowserTenancy/CapturaDeArteTest.php`
- O `beforeEach` já cria o cenário perfeito para a captura: um `master_global` vinculado a **uma** organização (Acme). `arranjarPainelApp()` aquece o painel; `gravarConfiguracao()` + `alinharConfiguracoesDoKit()` em `tests/Pest.php:gravarConfiguracao():349-378` gravam e aplicam o settings.

### `app/Console/Commands/KitArte.php` (CLIPES linha 70-91, IMAGENS linha 112-142)
- `CLIPES` define os GIFs (nome do clipe → quadros na ordem); `IMAGENS` as capturas publicadas com thumb. O `CT-50` de `tests/Kit/KitArteTest.php` reprova quadro declarado sem captura correspondente em `CapturaDeArteTest`.

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---------|----------|------------------|--------------------|
| D1 | `tenantMenu()` (esconde o bloco inteiro), não `tenantSwitcher()` (só a lista) | — | difícil de reverter | solicitante + sessão, 2026-10-08 (P-01) |
| D2 | Contagem via `User::getTenants()` (fonte do seletor), não `tenants()->count()` na pivot | — | difícil de reverter | solicitante + sessão, 2026-10-08 (Q1) |
| D3 | Decisão encapsulada em `App\Support\SeletorDeOrganizacao::visivel()` em vez de Closure inline no provider | — | surpreendente | sessão, 2026-10-08 |
| D4 | Toggle com `->visible(config('kit.tenancy.enabled'))` — sem tenancy, o campo não aparece | — | difícil de reverter | sessão, 2026-10-08 (P-04) |
| D5 | Log no channel `tenancy` existente, não channel novo | — | difícil de reverter | sessão, 2026-10-08 |

## Autorização

- **Policies**: nenhuma. A tela de settings já é governada por `View:ConfiguracoesDoKit` (CT-50 do `ConfiguracoesDoKitDocumentacaoTest` a cita).
- **Gates**: nenhum. Ocultar é cosmético (P-03): `canAccessTenant()` segue intacto e a URL continua servindo.

## Rotas

Nenhuma rota nova. O único caminho afetado é o render do `/app/{tenant}`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `x-filament-panels::tenant-menu` na sidebar do /app | Filament (blade do vendor) | `/app/{tenant}/*` | bloco aparece ou não, conforme o settings e a contagem | Não — presença/ausência no HTML |
| Toggle `ocultar_seletor_unico` na aba Kit da tela de configurações | Filament (campo de formulário) | `/admin/configuracoes-da-aplicacao` | ligar/desligar e salvar | Não |

**Gate de tela de escrita**: a tela de settings é `edit`-like — o `04` precisa de cenário de gravação por componente Livewire (ligar o toggle, salvar, conferir o banco).

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_TENANCY_OCULTAR_SELETOR_UNICO` | `false` | Semeia a primeira gravação: esconder o seletor quando o usuário tem uma única organização acessível |

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | 1 — a Closure do `tenantMenu()` é avaliada no mesmo render que já calcula o menu |
| O que é adiado, e por qual gatilho? | Nenhum |
| O que é memoizado **por request**? | O `HasTenantMenu` memoiza `getSwitchableTenants()` na instância do componente — a nossa Closure roda antes dele, numa query própria |
| O que é cacheado **entre** requests? | Nada novo; `SETTINGS_CACHE_ENABLED` já cobre a leitura do settings no boot |
| Custo do caminho principal | Desligado: zero queries (curto-circuito). Ligado: **uma** query de `getTenants()` a mais no render do /app — aceitável; a alternativa `count()` divergiria do que o seletor lista |

## Impacto em Features Existentes

- **Seletor de tenant do /app**: default `false` preserva o comportamento atual em instalação nova e em update (migration semeia o default do `config/kit.php`).
- **`CapturaDeArteTest`/`KitArte`**: quadro declarado em `CLIPES` sem captura quebra o `CT-50` do `KitArteTest` — o cenário de captura entra no mesmo PR.
- **Docs**: `docs/pt|en/recursos/configuracoes-do-kit.md` (aba Kit) e `multi-tenancy.md` ganham o parágrafo do novo interruptor; READMEs só se mencionarem o seletor.
- **`kit:update`**: a migration nova entrega o campo; a regra de `.ai/rules/commands.md` (caminho novo só entrega arquivo que mudou entre as tags) se aplica aos arquivos tocados.

## Rollback

- **Migration down**: remove a propriedade do grupo `kit` na `settings`.
- **Feature flag**: desligar o toggle na tela (ou `KIT_TENANCY_OCULTAR_SELETOR_UNICO=false` numa instalação sem banco gravado) restaura o seletor imediatamente — leitura por request, sem cache.

## Dependências

Nenhuma. `Panel::tenantMenu()` é API do Filament já instalado; `pest-plugin-browser` e Playwright já servem a suíte `BrowserTenancy`.

## Riscos

- **Query extra por render**: uma `getTenants()` a mais só quando o interruptor está ligado — mitigado pelo curto-circuito desligado e pelo fato de a lista ser pequena.
- **`master_global` com zero/uma organização ativa**: coberto por Q3 — segue a mesma regra.
- **Usuário com zero tenant acessível**: `count() > 1` já é falso — seletor oculto; o Filament redireciona para o primeiro tenant e o `403/404` continua sendo do `canAccessTenant()`.

## Channel de Log da Feature

- **Channel**: `tenancy` (já existe — `User::canAccessTenant()` loga nele). A decisão de esconder/mostrar o seletor é o mesmo domínio; um channel `ocultar-seletor-de-organizacao-unica` separaria duas linhas de log da mesma fronteira.
- **Ponto único**: `SeletorDeOrganizacao::visivel()` loga em `debug` **somente quando esconde** — o caminho "mostra" é o default e não merece ruído em todo request.

## Estrutura de Implementação

### 1. Chave de config + `.env.example`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`, `.env.example`
- Em `tenancy`, depois de `'slug'`: `'ocultar_seletor_unico' => (bool) env('KIT_TENANCY_OCULTAR_SELETOR_UNICO', false)`, com comentário explicando que semeia a primeira gravação e que o toggle vive na aba Kit da tela de configurações.
- `.env.example`: linha `# KIT_TENANCY_OCULTAR_SELETOR_UNICO=false` comentada junto das outras `KIT_TENANCY_*` (são comentadas — `.env.example` linhas 178-194).
- **Atende**: RQ-01, P-02

### 2. Propriedade no Settings + mapa

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php`
- `public bool $ocultar_seletor_unico;` no grupo `// Kit`, com docblock: lida por request via Closure de `tenantMenu()`, então a tela governa de verdade (`.ai/rules/settings.md`).
- `mapaDeConfiguracao()`: `'ocultar_seletor_unico' => 'kit.tenancy.ocultar_seletor_unico'`, junto das duas linhas `rotulo_da_*` que já mapeiam `kit.tenancy.*`.
- **Atende**: RQ-01, P-02

### 3. Migration de settings

> Skills: `laravel-best-practices`

- **Path**: `database/settings/2026_10_08_100000_add_ocultar_seletor_unico_to_kit_settings.php`
- Padrão das irmãs: `$blueprint->add('ocultar_seletor_unico', (bool) config('kit.tenancy.ocultar_seletor_unico', false))` no grupo `kit`; `down()` com `$blueprint->delete('ocultar_seletor_unico')`.
- **Atende**: RQ-01

### 4. `App\Support\SeletorDeOrganizacao`

> Skills: `laravel-best-practices`

- **Path**: `app/Support/SeletorDeOrganizacao.php`
- Classe `final`, método `public static function visivel(): bool`:
  - `false` (seletor mostra, comportamento de sempre) quando `config('kit.tenancy.ocultar_seletor_unico')` é falso — curto-circuito sem query;
  - `false` também sem usuário autenticado (defesa; o menu só renderiza logado);
  - caso contrário `Filament::getUserTenants(Filament::auth()->user())` — a MESMA fonte do seletor (`FilamentManager::getUserTenants()`, que delega a `User::getTenants()`) — e esconde quando `count() <= 1` (RQ-02, RQ-03, RQ-04).
- Docblock citando: quem lê (`tenantMenu()` do `AppPanelProvider`), quando (render, por request), e por que a fonte é `getUserTenants` e não a pivot (Q1).
- **Logs**: `Log::channel('tenancy')->debug('[SeletorDeOrganizacao@visivel] Seletor de organização oculto | user: {id} - tenants: {n}')`, só no ramo que esconde.
- **Atende**: RQ-02, RQ-03, RQ-04, P-03

### 5. `tenantMenu()` no AppPanelProvider

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AppPanelProvider.php` (bloco `kit.tenancy.enabled`, linha ~610)
- `->tenantMenu(fn (): bool => SeletorDeOrganizacao::visivel())` encadeado ao `->tenant(...)`. Comentário curto: lido por request (o Filament avalia a Closure no render, `HasTenancy.php:hasTenantMenu():344`), não no boot — é o que torna o settings editável.
- **Atende**: RQ-01, RQ-02, RQ-04, P-01, P-03

### 6. Toggle na aba Kit

> Skills: `filament-development`, `laravel-best-practices`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` (`abaKit()`, após `rotulo_das_organizacoes`)

```php
Toggle::make('ocultar_seletor_unico')
    ->label('Ocultar o seletor quando houver uma organização só')
    ->helperText('Quem tem acesso a uma única organização não tem o que trocar: ligado, o seletor do topo da barra lateral some; com duas ou mais, ele continua lá. Só aparência — o acesso por link direto não muda.')
    ->visible(fn (): bool => (bool) config('kit.tenancy.enabled'))
    ->columnSpanFull(),
```
- **Atende**: RQ-01, P-04

### 7. Capturas de arte (thumbs + quadros do GIF)

> Skills: `pest-testing`

- **Path**: `tests/BrowserTenancy/CapturaDeArteTest.php`
- Cenário novo `captura o seletor de organização visível e oculto`: arranja o painel /app (helper existente `arranjarPainelApp`), e para cada estado (`false` → `seletor-organizacao-1-visivel`, `true` → `seletor-organizacao-2-oculto`) grava `gravarConfiguracao('ocultar_seletor_unico', …)` + `alinharConfiguracoesDoKit()`, visita `/app/{acme}` e `screenshot(fullPage: false, filename: …)`. Um terceiro quadro `admin-configuracoes-seletor` fotografa o toggle na aba Kit.
- O `beforeEach` já garante o cenário de **uma** organização — nenhum arranjo extra.
- **Atende**: RQ-05

### 8. `KitArte` + docs

> Skills: `laravel-best-practices`

- **Path**: `app/Console/Commands/KitArte.php`, `docs/pt/recursos/configuracoes-do-kit.md`, `docs/pt/recursos/multi-tenancy.md`, `docs/en/...` (mesmos arquivos), `CHANGELOG.md`
- `CLIPES`: `'seletor-organizacao' => ['seletor-organizacao-1-visivel', 'seletor-organizacao-2-oculto']`.
- `IMAGENS`: `admin-configuracoes-seletor` (a thumb da tela de settings).
- Docs pt/en: parágrafo na seção da aba Kit (configuracoes-do-kit) e no seletor (multi-tenancy), citando `KIT_TENANCY_OCULTAR_SELETOR_UNICO` e o GIF.
- CHANGELOG: entrada na seção da versão corrente.
- **Atende**: RQ-05

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação: reutilizar `gravarConfiguracao`/`alinharConfiguracoesDoKit`/`arranjarPainelApp` existentes; uma Closure de uma linha no provider; sem cache, sem canal novo, sem decisor extra.
>
> **Caveman ativo em modo `ultra`** na comunicação. Arquivos wiki, código, commits e PRs são boundary do Caveman — prosa normal.
>
> **Baseline antes do primeiro commit**: `vendor/bin/pest --parallel --compact` em `main` (rodando, comando ID 104) — a `## Verificação Final` compara contra ela.

## Mapeamentos

Nenhum — booleano direto.

## Testes

> Ver `04-casos-de-teste.md` (a derivar no step 7) e `05-casos-de-teste-browser.md` se houver costura `browser`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest --filter=SeletorDeOrganizacao --compact` (CTs de backend)
- [ ] `KIT_ART=1 vendor/bin/pest tests/BrowserTenancy --filter=seletor` (capturas — arte é o deliverable do RQ-05)
- [ ] `vendor/bin/pest --parallel --tia` — comparado à baseline de `main`
- [ ] `pest --mutate --path=app/Support/SeletorDeOrganizacao.php` via `pestw.cmd` — score, duração e sobreviventes
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**
- [ ] `php artisan kit:arte --sem-gif` produz thumbs + monta o GIF `seletor-organizacao.gif`

## Commits

- `:sparkles: feat: ocultar o seletor de organização quando houver uma só`
- `:memo: docs: wiki da feature ocultar-seletor-de-organizacao-unica`
