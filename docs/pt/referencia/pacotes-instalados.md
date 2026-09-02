---
title: "Pacotes instalados"
parent: "Referência"
grand_parent: "Português"
nav_order: 3
---

# Pacotes instalados

Tudo abaixo já vem instalado, publicado e registrado nos painéis — não existe passo de "agora instale o plugin X". A fonte da verdade das versões é o `composer.json`; a tabela diz **para que serve cada um dentro do kit**.

## Base

| Pacote | Para quê |
|---|---|
| [laravel/framework](https://packagist.org/packages/laravel/framework) | o framework |
| [filament/filament](https://packagist.org/packages/filament/filament) | os painéis, tabelas, formulários e widgets |
| [laravel/tinker](https://packagist.org/packages/laravel/tinker) | REPL do Laravel |
| [livewire/blaze](https://packagist.org/packages/livewire/blaze) | otimiza componentes Blade dobrando-os no template pai |

## Administração e segurança

| Pacote | Para quê |
|---|---|
| [bezhansalleh/filament-shield](https://packagist.org/packages/bezhansalleh/filament-shield) | papéis e permissões com UI, sobre spatie/laravel-permission |
| [jeffgreco13/filament-breezy](https://packagist.org/packages/jeffgreco13/filament-breezy) | perfil do usuário, avatar, 2FA e passkeys |
| [caresome/filament-auth-designer](https://packagist.org/packages/caresome/filament-auth-designer) | tela de login em duas colunas |
| [marjose123/filament-lockscreen](https://packagist.org/packages/marjose123/filament-lockscreen) | bloqueio de sessão por inatividade, sem deslogar |
| [stechstudio/filament-impersonate](https://packagist.org/packages/stechstudio/filament-impersonate) | entrar como outro usuário |
| [tapp/filament-authentication-log](https://packagist.org/packages/tapp/filament-authentication-log) | histórico de logins, IP e dispositivo |
| [owen-it/laravel-auditing](https://packagist.org/packages/owen-it/laravel-auditing) | trilha de alterações dos models |
| [tapp/filament-auditing](https://packagist.org/packages/tapp/filament-auditing) | a tela dessa trilha no painel |
| [syriable/filament-activitylog](https://packagist.org/packages/syriable/filament-activitylog) | log de atividades (spatie/laravel-activitylog) no Filament |
| [bezhansalleh/filament-panel-switch](https://packagist.org/packages/bezhansalleh/filament-panel-switch) | troca de painel pelo menu do usuário |
| [laravel/socialite](https://packagist.org/packages/laravel/socialite) | login social (Google, GitHub, LinkedIn, X), opt-in por provedor |
| [ddr/filament-captcha](https://packagist.org/packages/ddr/filament-captcha) | desafio anti-robô nas telas públicas (reCAPTCHA v2/v3, Turnstile, hCaptcha), opt-in; o kit embrulha com falha fechada e log ([detalhes](../autenticacao/protecao-anti-robo.md)) |

## Observabilidade e manutenção

| Pacote | Para quê |
|---|---|
| [shuvroroy/filament-spatie-laravel-health](https://packagist.org/packages/shuvroroy/filament-spatie-laravel-health) | health checks (banco, cache, filas, agendador, disco, IA) |
| [spatie/laravel-backup](https://packagist.org/packages/spatie/laravel-backup) | backup da aplicação e do banco |
| [brimham/filament-backup-monitor](https://packagist.org/packages/brimham/filament-backup-monitor) | histórico e saúde dos backups por destino |
| [croustibat/filament-jobs-monitor](https://packagist.org/packages/croustibat/filament-jobs-monitor) | monitor de filas para qualquer driver |
| [laboiteacode/filament-logs-explorer](https://packagist.org/packages/laboiteacode/filament-logs-explorer) | leitura e busca nos logs sem sair do painel |
| [ssbityukov/filament-command-center](https://packagist.org/packages/ssbityukov/filament-command-center) | comandos Artisan pré-aprovados pela UI, com histórico |
| [laravel/pulse](https://packagist.org/packages/laravel/pulse) | performance e uso da aplicação em tempo real |
| [dotswan/filament-laravel-pulse](https://packagist.org/packages/dotswan/filament-laravel-pulse) | o Pulse embutido como página do painel |
| [laboiteacode/filament-dependency-graph](https://packagist.org/packages/laboiteacode/filament-dependency-graph) | mapa visual de models, relações, resources e painéis |
| [mominalzaraa/filament-composer-release-notifier](https://packagist.org/packages/mominalzaraa/filament-composer-release-notifier) | avisa quando há versão nova dos pacotes Composer |
| [cms-multi/filament-clear-cache](https://packagist.org/packages/cms-multi/filament-clear-cache) | limpar caches pelo painel |
| [bezhansalleh/filament-exceptions](https://packagist.org/packages/bezhansalleh/filament-exceptions) | exceções agrupadas por tipo e frequência, com retenção |
| [tapp/filament-maillog](https://packagist.org/packages/tapp/filament-maillog) | trilha de todo e-mail enviado |
| [promethys/revive](https://packagist.org/packages/promethys/revive) | a Lixeira: restaura registro apagado com `SoftDeletes` |

## IA

| Pacote | Para quê |
|---|---|
| [laravel/ai](https://packagist.org/packages/laravel/ai) | o SDK oficial de IA do Laravel (agentes, tools, streaming) |
| [fomvasss/laravel-ai-tasks](https://packagist.org/packages/fomvasss/laravel-ai-tasks) | orquestração das tarefas de IA: roteamento, fila, auditoria e budget |

## UI e produtividade

| Pacote | Para quê |
|---|---|
| [wezlo/filament-search-spotlight](https://packagist.org/packages/wezlo/filament-search-spotlight) | o overlay da busca ⌘K |
| [prodstarter/filament-notification-center](https://packagist.org/packages/prodstarter/filament-notification-center) | centro de notificações com abas e categorias |
| [pxlrbt/filament-environment-indicator](https://packagist.org/packages/pxlrbt/filament-environment-indicator) | indicador de ambiente (local, homologação, produção) |
| [gsferro/filament-odometer-easy](https://packagist.org/packages/gsferro/filament-odometer-easy) | contadores animados em tabelas, infolists, stats e badges |
| [gsferro/odometer-easy](https://packagist.org/packages/gsferro/odometer-easy) | a base do odometer fora do Filament |
| [gsferro/filament-stat-plus-easy](https://packagist.org/packages/gsferro/filament-stat-plus-easy) | stat cards com ícone de canto, borda colorida e skeleton |
| [awcodes/filament-badgeable-column](https://packagist.org/packages/awcodes/filament-badgeable-column) | badges dentro de colunas de tabela |
| [asmit/resized-column](https://packagist.org/packages/asmit/resized-column) | colunas redimensionáveis pelo usuário |
| [laboiteacode/filament-dashboard-widgets](https://packagist.org/packages/laboiteacode/filament-dashboard-widgets) | widgets prontos de métrica, meta, breakdown e tendência |
| [mddev31/filament-dynamic-dashboard](https://packagist.org/packages/mddev31/filament-dynamic-dashboard) | dashboard configurável pelo usuário: arrastar e redimensionar widgets |
| [lara-zeus/progress](https://packagist.org/packages/lara-zeus/progress) | barras de progresso em colunas e entries |
| [wallacemartinss/filament-onboarding](https://packagist.org/packages/wallacemartinss/filament-onboarding) | checklists e tours guiados, com autoria no `/admin` |
| [anselmokossa/filament-sentinel](https://packagist.org/packages/anselmokossa/filament-sentinel) | páginas de erro (403, 404, 419, 500, 503) com a cara do painel |
| [flowframe/laravel-trend](https://packagist.org/packages/flowframe/laravel-trend) | agregação por período para os gráficos dos widgets |
| [bezhansalleh/filament-language-switch](https://packagist.org/packages/bezhansalleh/filament-language-switch) | seletor de idioma nos três painéis e nas telas de login |
| [harvirsidhu/filament-cards](https://packagist.org/packages/harvirsidhu/filament-cards) | a grade de cartões dos [hubs de navegação](../recursos/hub-de-navegacao.md) |
| [leandrocfe/filament-apex-charts](https://packagist.org/packages/leandrocfe/filament-apex-charts) | gráficos ApexCharts nos widgets dos dashboards |
| [solution-forest/filament-simplelightbox](https://packagist.org/packages/solution-forest/filament-simplelightbox) | lightbox para ampliar imagem em tabela e infolist |

## Dados e serviços

| Pacote | Para quê |
|---|---|
| [filament/spatie-laravel-settings-plugin](https://packagist.org/packages/filament/spatie-laravel-settings-plugin) | páginas de configuração no painel |
| [spatie/laravel-settings](https://packagist.org/packages/spatie/laravel-settings) | as configurações persistidas por trás delas |
| [filament/spatie-laravel-media-library-plugin](https://packagist.org/packages/filament/spatie-laravel-media-library-plugin) | a camada de mídia (upload, coleções, conversões) nos componentes de form, tabela e infolist |
| [mike-bronner/laravel-model-caching](https://packagist.org/packages/mike-bronner/laravel-model-caching) | cache automático de queries do Eloquent |
| [predis/predis](https://packagist.org/packages/predis/predis) | cliente Redis em PHP puro (sem extensão) |
| [laravel/reverb](https://packagist.org/packages/laravel/reverb) | WebSocket para as notificações em tempo real |

> **Motores por baixo dos plugins**, instalados como dependência (você não os declara, mas eles são o que de fato roda): `spatie/laravel-permission` (Shield), `spatie/laravel-health` (os checks), `spatie/laravel-activitylog` (o log de atividades), `spatie/laravel-medialibrary` (os anexos) e `livewire/livewire` (o Filament inteiro).

## Model Caching

O kit aplica a trait `App\Traits\ModeloCacheavel` nas models que têm Resource no painel `/app` — hoje `User`, `Convite` e `Projeto`. O pacote `mike-bronner/laravel-model-caching` cacheia as queries Eloquent quando `MODEL_CACHE_ENABLED=true`.

- O default é `false` (`MODEL_CACHE_ENABLED=false` no `.env.example`).
- Para ligar, defina `MODEL_CACHE_ENABLED=true` e use `MODEL_CACHE_STORE=model-cache` (store Redis configurado em `config/cache.php`).
- A invalidação é automática: `save`, `update` e `delete` limpam o cache da model.
- Painéis `/admin` e `/infra` continuam **sem** model caching por padrão, reduzindo o risco de stale data em telas administrativas.

```bash
php artisan modelCache:clear      # limpa o cache das models
```


## Desenvolvimento (`require-dev`)

| Pacote | Para quê |
|---|---|
| [pestphp/pest](https://packagist.org/packages/pestphp/pest) + [pest-plugin-laravel](https://packagist.org/packages/pestphp/pest-plugin-laravel) | a suíte de testes |
| [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) | o motor por baixo do Pest |
| [larastan/larastan](https://packagist.org/packages/larastan/larastan) | análise estática (`composer types:check`) |
| [laravel/pint](https://packagist.org/packages/laravel/pint) | formatação (`composer lint`) |
| [laraveldaily/filacheck](https://packagist.org/packages/laraveldaily/filacheck) | lint específico de Filament (`composer filament:check`) |
| [laravel-lang/common](https://packagist.org/packages/laravel-lang/common) | traduções pt-BR do Laravel |
| [laravel/pail](https://packagist.org/packages/laravel/pail) | logs em tempo real no terminal |
| [laravel/pao](https://packagist.org/packages/laravel/pao) | ferramentas de desenvolvimento do Laravel |
| [nunomaduro/collision](https://packagist.org/packages/nunomaduro/collision) | erros legíveis no terminal |
| [mockery/mockery](https://packagist.org/packages/mockery/mockery) | mocks nos testes |
| [fakerphp/faker](https://packagist.org/packages/fakerphp/faker) | dados falsos **só em teste** — seeder do kit nunca usa |
| [pestphp/pest-plugin-browser](https://packagist.org/packages/pestphp/pest-plugin-browser) | os testes de navegador (`tests/Browser`, `tests/BrowserTenancy`) |
| [pestphp/pest-plugin-mutate](https://packagist.org/packages/pestphp/pest-plugin-mutate) | mutation testing (`pest --mutate`) |
| [pestphp/pest-plugin-phpstan](https://packagist.org/packages/pestphp/pest-plugin-phpstan) | o PHPStan dentro do `pest` |
| [rector/rector](https://packagist.org/packages/rector/rector) + [driftingly/rector-laravel](https://packagist.org/packages/driftingly/rector-laravel) | reescrita automática (`composer refactor:preview` / `refactor:apply`) |
| [filament/upgrade](https://packagist.org/packages/filament/upgrade) | a ferramenta de upgrade do Filament (`composer upgrade:filament`) |
| [laravel/boost](https://packagist.org/packages/laravel/boost) | MCP server e guidelines para os agentes de IA (`.ai/rules`) |

## Front-end (`package.json`)

| Pacote | Para quê |
|---|---|
| [vite](https://www.npmjs.com/package/vite) + [laravel-vite-plugin](https://www.npmjs.com/package/laravel-vite-plugin) | o build dos assets |
| [tailwindcss](https://www.npmjs.com/package/tailwindcss) + [@tailwindcss/vite](https://www.npmjs.com/package/@tailwindcss/vite) | o CSS (v4, sem arquivo de config) |
| [concurrently](https://www.npmjs.com/package/concurrently) | roda servidor, fila e vite juntos no `composer dev` |
| [playwright](https://www.npmjs.com/package/playwright) | o navegador dos testes do `pest-plugin-browser` |
| [@laravel/multiplex](https://www.npmjs.com/package/@laravel/multiplex) | agrupa requests do Livewire (opcional) |

