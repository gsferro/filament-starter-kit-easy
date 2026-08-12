# starter-kit-easy

Starter kit **Laravel 13 + Filament 5** pronto para uso. Um comando cria o projeto, instala tudo, migra, popula o banco e entrega três painéis funcionando: **negócio**, **administração** e **infraestrutura**.

```bash
composer create-project gsferro/starter-kit-easy meu-projeto
cd meu-projeto
composer dev
```

Não há passo manual: o `create-project` já cria o `.env`, gera a `APP_KEY`, cria o banco SQLite, roda as migrations, semeia papéis/permissões/usuário, publica os assets do Filament e faz o build do front-end. Ao final ele imprime as URLs e o login inicial.

**Login inicial:** `admin@example.com` / `password` — papel `master_global`. **Troque antes de expor o ambiente.**

## Os três painéis

| Painel | URL | Para quê | Quem entra |
|---|---|---|---|
| **App** | `/app` | A operação do negócio. **Vem vazio de propósito** — é aqui que seu projeto nasce | qualquer usuário autenticado |
| **Admin** | `/admin` | Usuários, papéis e permissões (Shield), catálogo de agentes de IA, autoria de onboarding | `master_global`, `admin` |
| **Infra** | `/infra` | Health checks, backups, filas, logs, auditoria, caches, comandos, Pulse, custos de IA | `master_global`, `infra` |

A regra de acesso fica em `App\Models\User::canAccessPanel()`. O papel `master_global` vence qualquer gate via `Gate::before` (`App\Providers\KitServiceProvider`) — não precisa de permissions no banco.

Separar admin de infra é o ponto do kit: quem administra usuários não precisa (nem deve) enxergar logs, filas e comandos operacionais, e vice-versa.

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
- Busca global ⌘K (Spotlight), centro de notificações com abas, indicador de ambiente
- Widgets prontos: odômetro animado, stat cards, funis, metas, timelines
- Páginas de erro brandadas (Sentinel), tabelas com defaults sensatos e UI 100% em pt-BR

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

## Solução de problemas

- **`/infra` ou `/admin` dando 403** — seu usuário precisa do papel `master_global`, `admin` ou `infra`.
- **Assets do Filament sumidos** — `php artisan filament:assets`.
- **Pulse sem dados** — falta o daemon: `php artisan pulse:check` (ou o serviço `pulse` do compose).
- **Sininho não atualiza em tempo real** — `BROADCAST_CONNECTION=reverb` exige o processo Reverb no ar; sem ele o kit cai para polling de 30s.
- **Assistente de IA indisponível** — suba `docker compose --profile ai up -d` (o primeiro boot baixa ~4,5 GB de modelo) ou troque `AI_PROVIDER` para um provider SaaS com API key.

## Licença

MIT.
