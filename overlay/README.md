# Start-Kit — Laravel 13 + Filament v5

Arcabouço pronto para iniciar qualquer novo projeto: painéis, autenticação, permissões, observabilidade, manutenção, assistente de IA e stacks Docker já configurados. Clone/instale, customize a marca e comece a construir o negócio direto no painel `app`.

## Painéis

| Painel | URL | Propósito | Acesso |
|---|---|---|---|
| **Admin** | `/admin` | Administração da aplicação: usuários, papéis e permissões (Shield), assistente de IA | papéis `super_admin`, `admin` |
| **Infra** | `/infra` | Observabilidade e manutenção: health check, limpeza de caches, pacotes instalados, links para Pulse e Horizon | papéis `super_admin`, `infra` |
| **App** | `/app` | A operação de negócio. **Vem vazio de propósito** — é aqui que cada projeto constrói suas features | qualquer usuário autenticado |

As regras de acesso por painel ficam em `App\Models\User::canAccessPanel()` — ajuste conforme o projeto.

## Pacotes incluídos

- **filament/filament ^5** — framework de painéis (Livewire 4)
- **bezhansalleh/filament-shield + spatie/laravel-permission** — papéis e permissões com UI
- **spatie/laravel-health** — health checks (página no painel Infra + endpoint `health:check`)
- **spatie/laravel-activitylog** — trilha de auditoria
- **spatie/laravel-backup** — backups de banco e arquivos
- **laravel/horizon** — dashboard de filas (Redis) em `/horizon`
- **laravel/pulse** — observabilidade da aplicação em `/pulse`
- **prism-php/prism** — camada de IA multi-provedor (OpenAI, Anthropic, Gemini, Ollama...)
- **Dev:** laravel/pint, larastan
- **laravel-lang/common** — traduções pt-BR do Laravel, Filament e pacotes do ecossistema

## Requisitos

- PHP 8.3+ e Composer 2 (ou apenas Docker)
- Docker + Docker Compose (opcional, para as stacks)

## Instalação

### Opção A — via Composer (recomendada, após publicar no Packagist)

```bash
composer create-project fiotec/start-kit meu-projeto
```

### Opção B — via Git

```bash
git clone https://github.com/SUA-ORG/start-kit.git meu-projeto
cd meu-projeto
rm -rf .git && git init   # descarta o histórico do kit
composer install
```

### Passos comuns

```bash
cp .env.example .env
php artisan key:generate

# Se for usar as stacks Docker, copie as variáveis de .env.docker para o .env e:
docker compose up -d
# stacks opcionais:
docker compose --profile queue --profile storage up -d

php artisan migrate --seed
php artisan shield:setup
php artisan shield:generate --all --panel=admin
```

**Credenciais iniciais (seeder):** `admin@example.com` / `password` — papel `super_admin`. **Troque em produção.**

## Idioma (pt-BR)

O kit já vem inteiramente em português do Brasil:

- `APP_LOCALE=pt_BR`, `APP_FAKER_LOCALE=pt_BR` e `APP_TIMEZONE=America/Sao_Paulo` definidos no `.env.example`.
- Traduções do Laravel, do Filament e dos plugins instaladas via **laravel-lang** na pasta `lang/pt_BR` (o Filament usa o locale da aplicação automaticamente — login, tabelas, notificações, tudo em pt-BR).
- Labels das páginas e recursos do kit escritos em português.

Após instalar/atualizar pacotes, mantenha as traduções em dia com:

```bash
php artisan lang:update
```

Para adicionar outro idioma: `php artisan lang:add es` (por exemplo) e ajuste `APP_LOCALE`.

## Multi-tenancy (opcional, desligado por padrão)

A maioria dos projetos não precisa — por isso o kit vem com `KIT_TENANCY=false`. Mas o suporte está pronto: para ativar, basta:

```env
KIT_TENANCY=true
```

Com isso o painel **App** passa a operar por tenant (modelo `Team`, tabela `teams` + pivot `team_user` já migradas):

- URLs no formato `/app/{slug-da-equipe}/...` com seletor de troca de equipe no menu.
- Página de **registro de equipe** (`RegisterTeam`) e **perfil da equipe** (`EditTeamProfile`) em `app/Filament/App/Pages/Tenancy/`.
- Recursos do painel App são automaticamente escopados pelo relacionamento de posse `team` — seus modelos de negócio precisam ter `team_id` e o relacionamento `team()` (ou ajuste `ownership_relationship` em `config/kit.php`).
- O acesso do usuário às equipes é controlado por `User::getTenants()` / `canAccessTenant()`.

Customizações em `config/kit.php` (bloco `tenancy`): modelo do tenant (renomeie `Team` para o conceito do seu negócio — empresa, organização, filial...), atributo de slug e relacionamento de posse. Os painéis **Admin** e **Infra** permanecem globais (sem tenant) de propósito.

**Se não for usar tenancy:** não precisa fazer nada. Se quiser um projeto mais enxuto, pode remover a migration `*_create_teams_table.php`, o modelo `Team`, a pasta `app/Filament/App/Pages/Tenancy/` e o bloco correspondente no `AppPanelProvider` e no `User`.

## Stacks Docker

Definidas em `compose.yaml`. Usar ou não fica a cargo de cada projeto.

| Serviço | Porta padrão | Profile |
|---|---|---|
| app (PHP 8.4 + nginx) | 8080 | padrão |
| PostgreSQL 17 | 5432 | padrão |
| Redis 7 | 6379 | padrão |
| Mailpit (SMTP/UI) | 1025 / 8025 | padrão |
| Horizon (worker) | — | `queue` |
| MinIO (S3) | 9000 / 8900 | `storage` |

As variáveis de ambiente correspondentes estão em `.env.docker`.

## Assistente de IA

Página **Assistente IA** no painel Admin, usando o Prism. Configure no `.env`:

```env
PRISM_AI_PROVIDER=anthropic        # openai | anthropic | gemini | ollama ...
PRISM_AI_MODEL=claude-sonnet-4-6
ANTHROPIC_API_KEY=sk-...
```

O provedor/modelo é lido de `config/kit.php`. Para trocar o comportamento (system prompt, streaming, tools), edite `app/Filament/Admin/Pages/Assistant.php`.

## Customizações que cada projeto DEVE fazer

O kit é entregue **sem identidade visual** de propósito:

1. **Nome da aplicação** — `APP_NAME` no `.env`.
2. **Logo** — substitua `public/images/logo.svg` (hoje é a logo do Laravel, apenas placeholder). Se preferir, aponte `->brandLogo()` nos PanelProviders para outro asset ou remova para usar o nome textual.
3. **Cores** — o kit usa o tema padrão do Filament. Para customizar, adicione `->colors([...])` em cada `app/Providers/Filament/*PanelProvider.php`.
4. **Favicon** — `->favicon(asset('...'))` nos PanelProviders.
5. **Regras de acesso por painel** — `User::canAccessPanel()`.
6. **Health checks** — adicione/remova checks em `app/Providers/KitServiceProvider.php`.
7. **Provedor de IA** — variáveis `PRISM_*` e chave de API do provedor.
8. **Backups** — destino e agenda em `config/backup.php` + scheduler.
9. **Credenciais do seeder** — troque e-mail/senha em `database/seeders/KitSeeder.php`.
10. **Domínio/URL** — `APP_URL` e portas em `.env`.

## Como atualizar

- **Dependências (Filament, plugins, Laravel):** `composer update` normalmente — todo o "recheio" do kit são pacotes Composer, então evoluções deles chegam por aqui.
- **Evoluções do próprio kit (novas páginas, providers, docker):** projetos já criados divergem do template, então a atualização é por *diff*: compare com a tag mais recente do kit (`git remote add kit <url>; git fetch kit; git diff kit/main -- app/ compose.yaml`) e aplique o que fizer sentido.
- **Estratégia recomendada a médio prazo:** extrair a "cola" do kit (páginas Infra, KitServiceProvider, comandos) para um pacote próprio `fiotec/kit-core`. Aí os projetos recebem melhorias com um simples `composer update fiotec/kit-core`, e o skeleton fica mínimo.

## Estrutura relevante

```
app/
├── Filament/
│   ├── Admin/      # recursos e páginas do painel admin (Users, Assistente IA)
│   ├── Infra/      # páginas de infra (Health, Manutenção, Pacotes)
│   └── App/        # vazio — construa o negócio aqui
├── Providers/
│   ├── Filament/   # AdminPanelProvider, InfraPanelProvider, AppPanelProvider
│   └── KitServiceProvider.php   # health checks + gates do Pulse/Horizon
config/kit.php      # configurações do kit (IA)
compose.yaml        # stacks Docker
.env.docker         # variáveis para uso com Docker
```

## Solução de problemas

- **Conflito de versão de plugin:** o ecossistema migrou para Laravel 13 + Filament v5 recentemente; se algum `composer require` falhar, verifique a versão do plugin com suporte a `illuminate/*: ^13.0` e ajuste a constraint.
- **`/pulse` ou `/horizon` retornando 403:** os gates `viewPulse`/`viewHorizon` exigem papel `infra` ou `super_admin` (definidos no `KitServiceProvider`).
- **Assets do Filament ausentes:** rode `php artisan filament:assets`.
