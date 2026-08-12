# starter-kit-easy

![Starter Kit Easy](art/banner.png)

[![Packagist](https://img.shields.io/packagist/v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Downloads](https://img.shields.io/packagist/dt/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Testes](https://img.shields.io/github/actions/workflow/status/gsferro/filament-starter-kit-easy/ci.yml?branch=main&style=flat-square&label=testes)](https://github.com/gsferro/filament-starter-kit-easy/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/packagist/php-v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![License](https://img.shields.io/packagist/l/gsferro/starter-kit-easy.svg?style=flat-square)](LICENSE)

Starter kit **Laravel 13 + Filament 5** pronto para uso. Um comando cria o projeto, instala tudo, migra, popula o banco e entrega três painéis funcionando: **negócio**, **administração** e **infraestrutura**.

```bash
composer create-project gsferro/starter-kit-easy meu-projeto
cd meu-projeto
composer dev
```

Não há passo manual: o `create-project` já cria o `.env`, gera a `APP_KEY`, cria o banco SQLite, roda as migrations, semeia papéis/permissões/usuário, publica os assets do Filament e faz o build do front-end. Ao final ele imprime as URLs e o login inicial.

![Instalação do starter-kit-easy em um comando](art/install.gif)

Prefere clonar? O mesmo instalador roda sozinho:

```bash
git clone https://github.com/gsferro/filament-starter-kit-easy.git meu-projeto
cd meu-projeto && rm -rf .git && git init   # descarta o histórico do kit
composer setup
```

## Acesso de demonstração

O seeder cria um usuário master que já entra nos três painéis:

| | |
|---|---|
| **Usuário** | `admin@example.com` |
| **Senha** | `password` |
| **Papel** | `master_global` (vence qualquer permissão via `Gate::before`) |

Entre por `/app`, `/admin` ou `/infra` — a mesma sessão vale para os três, e o menu do usuário troca de painel.

> ⚠️ **Troque a senha antes de expor o ambiente.** Para nascer com outra credencial, defina `KIT_ADMIN_EMAIL`, `KIT_ADMIN_PASSWORD` e `KIT_ADMIN_NAME` no `.env` **antes** de rodar a instalação (os valores ficam em `config/kit.php`). Num projeto já instalado, troque pelo próprio painel em `/admin/users` ou em **Meu perfil**.

Para testar o recorte de acesso, crie um usuário só com o papel `admin` ou `infra`: ele entra no painel correspondente e toma 403 no outro.

## Os três painéis

| Painel | URL | Para quê | Quem entra |
|---|---|---|---|
| **App** | `/app` | A operação do negócio. **Vem vazio de propósito** — é aqui que seu projeto nasce | qualquer usuário autenticado |
| **Admin** | `/admin` | Usuários, papéis e permissões (Shield), catálogo de agentes de IA, autoria de onboarding | `master_global`, `admin` |
| **Infra** | `/infra` | Health checks, backups, filas, logs, auditoria, caches, comandos, Pulse, custos de IA | `master_global`, `infra` |

A regra de acesso fica em `App\Models\User::canAccessPanel()`. O papel `master_global` vence qualquer gate via `Gate::before` (`App\Providers\KitServiceProvider`) — não precisa de permissions no banco.

Separar admin de infra é o ponto do kit: quem administra usuários não precisa (nem deve) enxergar logs, filas e comandos operacionais, e vice-versa.

### Como cada um se parece

| Login | Administração |
|---|---|
| [![Tela de login](art/thumbs/login.png)](art/login.png) | [![Painel admin](art/thumbs/panel-admin.png)](art/panel-admin.png) |
| Auth Designer em duas colunas — troque a arte em `public/images/auth/login.svg` | Usuários, papéis, agentes de IA e indicadores de administração |

| Infraestrutura | Negócio |
|---|---|
| [![Painel infra](art/thumbs/panel-infra.png)](art/panel-infra.png) | [![Painel app](art/thumbs/panel-app.png)](art/panel-app.png) |
| Saúde, filas, trilhas, comandos e custos de IA — agrupados por tema | Vazio de propósito: é onde o seu projeto nasce |

Mais telas: [saúde da aplicação](art/infra-health.png) · [permissões (Shield)](art/admin-roles.png) · [catálogo de agentes de IA](art/admin-agentes-ia.png) · [central de comandos](art/infra-comandos.png) · [busca ⌘K](art/spotlight.png) · [acesso negado](art/erro-403.png)

## O que já vem pronto

**Administração e segurança**
- Shield (papéis e permissões com UI) sobre spatie/laravel-permission
- Breezy: perfil do usuário, avatar, 2FA e passkeys
- Auth Designer: tela de login em duas colunas (troque a arte em `public/images/auth/login.svg`)
- Lockscreen: bloqueio de sessão por inatividade (30 min), registrado nos 3 painéis
- Impersonate, log de autenticação, auditoria de alterações (owen-it)
- Panel Switch: troca de painel pelo menu do usuário

**Observabilidade e manutenção (painel infra)**
- Spatie Health com checks de banco, cache, filas, agendador, disco, debug mode e IA local
- Backup Monitor (spatie/laravel-backup), Jobs Monitor, Logs Explorer (sem botão de apagar — trilha é evidência)
- Command Center: comandos Artisan pré-aprovados pela UI, com histórico
- Laravel Pulse embutido como página do painel
- Dependency Graph: mapa de models, relações, resources e painéis
- Release Notifier: avisa quando há versão nova dos pacotes Composer

**IA (opcional, local por padrão)**
- `laravel/ai` com catálogo de agentes no banco: system prompt, provider, modelo, tools e guardrails são **dados**, editáveis no `/admin` sem deploy
- Guardrails encadeados: budget, prompt injection, classificador local, redação de PII e filtro de saída sensível
- Ledger de execuções (`ai_runs`) com custo e tokens no painel infra
- Widget de chat com streaming
- Inferência 100% local via llama.cpp (`docker compose --profile ai up -d`) ou qualquer provider SaaS trocando `AI_PROVIDER`

**Produtividade**
- Busca ⌘K (Spotlight) com gatilho na topbar — as categorias do kit checam `canAccess()`, então ninguém encontra na busca uma tela que tomaria 403
- Badges de contagem animados no menu, centro de notificações com abas, indicador de ambiente
- Dashboards já preenchidos: stat cards com contador animado, funis, metas, breakdowns e timelines sobre os dados que os painéis já têm
- Páginas de erro brandadas (Sentinel) em pt-BR — a de 403 só mostra o diagnóstico de permissão fora de produção
- UI 100% em pt-BR e tabelas com defaults sensatos

## Requisitos

- PHP 8.3+ e Composer 2
- Node 20+ (opcional — sem ele a instalação segue e avisa como fazer o build depois)
- Docker (opcional — só para Postgres, Redis, IA local e e-mail)

## Banco de dados

O kit instala com **SQLite** para não depender de nada. Para Postgres, suba os containers e copie as variáveis:

```bash
docker compose up -d              # pgsql (com pgvector) + redis
# copie o bloco de banco de .env.docker para o seu .env
php artisan migrate --seed
```

## Docker

Tudo é opt-in por profile. Um container por feature:

```bash
docker compose up -d                            # pgsql + redis
docker compose --profile ai up -d               # + llama.cpp (chat e embeddings)
docker compose --profile mail up -d             # + mailpit (1025 / 8025)
docker compose --profile full up -d             # infra completa
docker compose --profile app up -d --build      # a aplicação containerizada
docker compose --profile realtime up -d reverb pulse
```

| Serviço | Porta | Profile |
|---|---|---|
| PostgreSQL 17 + pgvector | 5432 | base |
| Redis 7 (só cache) | 6379 | base |
| llama.cpp (chat) | 8080 | `ai` |
| llama.cpp (embeddings) | 8081 | `ai` |
| Mailpit | 1025 / 8025 | `mail` |
| App (nginx + php-fpm) | 8000 | `app` |
| Reverb (WebSocket) | 8090 | `app`, `realtime` |

O Reverb usa 8090 e não o default 8080 para não colidir com o llama.cpp.

## Comandos

```bash
composer dev          # servidor + fila + vite juntos
composer test         # pint + phpstan + testes
composer lint         # formata o código
php artisan kit:install --force   # reinstala do zero (apaga o SQLite)
```

## Personalize seu projeto

1. **Nome** — `APP_NAME` no `.env`
2. **Arte do login** — `public/images/auth/login.svg`
3. **Cores** — `->colors([...])` em cada `app/Providers/Filament/*PanelProvider.php`
4. **Acesso aos painéis** — `App\Models\User::canAccessPanel()`
5. **Matriz de permissões** — `database/seeders/PapeisSeeder.php`
6. **Health checks** — `KitServiceProvider::configureHealthChecks()`
7. **Comandos da UI** — `config/command-center.php`
8. **Credenciais do seeder** — `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` no `.env`
9. **Backups** — destino e agenda em `config/backup.php`
10. **Agente de IA** — `/admin` → Agentes de IA (ou `database/seeders/AssistenteSeeder.php`)

## Configuração global do Filament

Um único arquivo define como **toda** tabela, toggle, modal e coluna do projeto se comporta: `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (aplicado pelo `KitServiceProvider`). Mudou ali, mudou em todo lugar — inclusive nas telas dos plugins de terceiros, que você não conseguiria editar de outro jeito.

**Toda tabela nasce com:**

| Comportamento | Por quê |
|---|---|
| `deferLoading()` | a tela aparece antes da query terminar |
| `striped()` + `stackedOnMobile()` | leitura em lista no desktop, cartão no celular |
| `persistFilters/Search/Sort/ColumnSearchesInSession()` | o recorte do usuário sobrevive à navegação |
| `reorderableColumns()` + `dragReorderableColumns()` + `stickableColumns()` | colunas reordenáveis, arrastáveis e fixáveis |
| **colunas redimensionáveis** (`asmit/resized-column`) | largura ajustável pelo usuário, preservada na sessão |
| `filtersLayout(Modal)` + `filtersFormColumns(2)` + `deferFilters()` | com 3+ filtros o dropdown vira rolagem; o modal não |
| `defaultPaginationPageOption(10)` + `extremePaginationLinks()` | paginação previsível, com atalhos de primeira/última |
| `deselectAllRecordsWhenFiltered(false)` | filtrar não descarta a seleção |

Também são globais: modal que **não** fecha no Esc (um toque acidental descartaria o formulário), toggles com cor e ícone de estado, coluna de ícone booleana com check/x colorido, `CreateAction` com ícone padrão e o alternador de painéis.

> **Colunas redimensionáveis em telas novas:** o comportamento padrão já vale para qualquer tabela; para que a largura escolhida seja **lembrada**, a página de listagem precisa do trait:
>
> ```php
> use Asmit\ResizedColumn\HasResizableColumn;
>
> class ListProdutos extends ListRecords
> {
>     use HasResizableColumn;
> }
> ```

> 📌 **TODO:** transformar esses defaults num **Settings em `/admin`**, para que paginação, densidade, persistência de filtros e colunas redimensionáveis virem preferência do projeto pela interface, sem editar código. O `filament/spatie-laravel-settings-plugin` já está instalado para isso.

## Convenções do kit

- **UUID nas rotas, `id` int como PK.** Toda tabela nova ganha `$table->uuid('uuid')->unique()` e o model usa `App\Traits\TemUuid`. URL com id numérico devolve 404 e ninguém enumera registros por sequência. UUID não é autorização — policies continuam obrigatórias.
- **Auditoria no que é editável.** `App\Traits\AuditsFillables` audita exatamente o `$fillable`, sem vazar colunas técnicas para a trilha.
- **Seeder nunca usa factory nem faker.** `fakerphp/faker` é `require-dev` e a imagem Docker roda `--no-dev`.
- **Permissões vêm de seeder, não de `shield:generate` interativo** — é o que permite instalar sem intervenção. Depois de criar Resources novos, rode `php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder`.

## Depois de criar seus Resources

```bash
php artisan make:filament-resource Produto --panel=app
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

Na página de listagem gerada, adicione os dois traits do kit:

```php
use App\Filament\Concerns\BadgeContagemNavegacao;   // no Resource: badge de contagem no menu
use Asmit\ResizedColumn\HasResizableColumn;         // na List page: lembra a largura das colunas
```

## Atualizando um projeto que já nasceu do kit

**O kit é um ponto de partida, não uma dependência.** Depois do `create-project` o projeto é seu: você renomeia painéis, muda `canAccessPanel()`, edita seeders. Por isso **não existe** um `kit:update` que sobrescreve arquivos — ele reescreveria justamente o que você personalizou, e um starter kit que estraga o projeto do usuário não serve para nada.

O que muda separa-se em três camadas, e cada uma tem um caminho próprio:

| Camada | O que é | Como atualizar |
|---|---|---|
| **Dependências** | Filament, plugins, Laravel | `composer update` — é a maior parte das melhorias e chega sozinha |
| **Cola do kit** | providers, traits, widgets, views de erro | diff manual contra a tag nova (abaixo) |
| **Seu negócio** | tudo que você escreveu | nunca é tocado |

### Diff contra a versão nova

Adicione o kit como um **segundo remote**, uma única vez. Seu `origin` continua sendo o seu projeto; o `kit` é só uma fonte de leitura:

```bash
git remote add kit https://github.com/gsferro/filament-starter-kit-easy.git

# o remote do kit é somente-leitura: evita um `git push kit main` acidental
# mandar o SEU projeto para dentro do repositório do kit
git remote set-url --push kit no_push
```

As tags do kit vão para um namespace próprio (`kit-v*`). Isso importa: um `git fetch kit --tags` traria `v0.1.0`, `v0.2.0`… para o seu projeto e colidiria com as **suas** versões depois.

```bash
git fetch --no-tags kit 'refs/tags/*:refs/tags/kit-*'
git tag -l 'kit-*'      # kit-v0.1.0, kit-v0.2.0, ...
```

Depois, a cada versão, veja o que mudou e traga só o que interessa:

```bash
# 1. panorama entre a sua versão e a nova
git diff kit-v0.1.0..kit-v0.2.0 --stat

# 2. o diff da "cola" do kit (ignore o que você já reescreveu)
git diff kit-v0.1.0..kit-v0.2.0 -- app/Providers app/Filament/Concerns \
        app/Filament/Spotlight app/Traits resources/views/errors config/kit.php

# 3. traga arquivo a arquivo, revisando
git checkout kit-v0.2.0 -- resources/views/errors
git checkout kit-v0.2.0 -- app/Filament/Concerns/BadgeContagemNavegacao.php
```

Faça isso num branch (`git switch -c atualiza-kit`) e rode `composer test` antes do merge. Arquivos que você reescreveu: leia o diff e aplique à mão — é o único caminho seguro.

> 💡 **TODO / rumo do projeto:** extrair a "cola" para um pacote Composer próprio (`gsferro/kit-core`) com os providers, traits, widgets e páginas de infra. Aí a camada do meio vira `composer update gsferro/kit-core` e o skeleton fica mínimo — só o que é mesmo ponto de partida. É a evolução natural deste kit.

## Solução de problemas

- **`/infra` ou `/admin` dando 403** — seu usuário precisa do papel `master_global`, `admin` ou `infra`. A tela de 403 mostra qual permissão faltou, mas **só fora de produção**: em produção ela não revela papéis nem permissões.
- **Assets do Filament sumidos** — `php artisan filament:assets`.
- **Pulse sem dados** — falta o daemon: `php artisan pulse:check` (ou o serviço `pulse` do compose).
- **Sininho não atualiza em tempo real** — `BROADCAST_CONNECTION=reverb` exige o processo Reverb no ar; sem ele o kit cai para polling de 30s.
- **Assistente de IA indisponível** — suba `docker compose --profile ai up -d` (o primeiro boot baixa ~4,5 GB de modelo) ou troque `AI_PROVIDER` para um provider SaaS com API key.

## Licença

MIT.
