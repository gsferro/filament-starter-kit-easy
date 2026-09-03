<img alt="Starter Kit Easy" class="filament-hidden" src="https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbnail.png"/>

[![Packagist](https://img.shields.io/packagist/v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Downloads](https://img.shields.io/packagist/dt/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Plumb](https://plumbphp.dev/badges/gsferro/starter-kit-easy/composite.svg)](https://plumbphp.dev/gsferro/starter-kit-easy)
[![Testes](https://img.shields.io/github/actions/workflow/status/gsferro/filament-starter-kit-easy/ci.yml?branch=main&style=flat-square&label=testes)](https://github.com/gsferro/filament-starter-kit-easy/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/packagist/php-v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Filament](https://img.shields.io/badge/Filament-5.x-FFAA00?style=flat-square)](https://filamentphp.com)
[![License](https://img.shields.io/packagist/l/gsferro/starter-kit-easy.svg?style=flat-square)](LICENSE)

> 🇧🇷 Português · 🇺🇸 [English](https://github.com/gsferro/filament-starter-kit-easy/blob/main/README.en.md)

Starter kit **Laravel 13 + Filament 5** pronto para uso. Um comando cria o projeto, instala tudo, migra, popula o banco e entrega três painéis funcionando: **negócio**, **administração** e **infraestrutura**.

```bash
composer create-project gsferro/starter-kit-easy meu-projeto
cd meu-projeto
composer dev
```

Não há passo manual: o `create-project` já cria o `.env`, gera a `APP_KEY`, cria o banco, roda as migrations, semeia papéis/permissões/usuário, publica os assets do Filament e faz o build do front-end. Ao final ele imprime as URLs e o login inicial.

Antes de tocar no banco, ele **pergunta cinco coisas** — como o `laravel new` faz:

| | Pergunta | Padrão |
|---|---|---|
| 1 | Nome do projeto | o nome da pasta |
| 2 | Banco de dados | SQLite · **PostgreSQL** (recomendado: é o único com `pgvector`, exigido pelas funções de IA local) · MySQL |
| 3 | E-mail e senha do administrador | `admin@example.com` / `password` |
| 4 | Cor primária dos painéis | o padrão do Filament |
| 5 | Multi-organização (multi-tenancy) | desligada |

**Enter em tudo instala exatamente como antes** — nenhuma pergunta é obrigatória, e a primeira delas é "personalizar agora?", que pula todas de uma vez. Sem terminal (CI, Docker, `--no-interaction`) nada é perguntado. Ao final o instalador mostra o resumo do que mudou, o que continua sendo editado à mão, e oferece rodar os testes do kit.

> **No Windows as perguntas não aparecem, e isso não é bug do kit.** Medido nos dois shells,
> PowerShell e Git Bash: o Composer nunca liga TTY em Windows — `ProcessExecutor::runProcess()`
> descarta o modo TTY quando `Platform::isWindows()`, porque o `symfony/process` lançaria
> `TTY mode is not supported on Windows platform`. O `artisan` recebe pipes, e o instalador se
> pula pelo próprio guarda de terminal, avisando na tela.
>
> **O que fazer**, e a ordem importa:
>
> ```bash
> php artisan kit:install --force    # as cinco perguntas — RECRIA o banco
> ```
>
> Rode **logo depois de instalar**, com o banco ainda só com os dados de seed: aí o `--force` é
> inócuo. Mais tarde ele é destrutivo, porque apaga o SQLite antes de perguntar.
>
> Se já tem dado no banco e quer só o nome e a cor:
>
> ```bash
> php artisan kit:install --custom   # nome e cor, sem tocar em nada
> ```
>
> As outras três perguntas não têm versão não destrutiva, e o comando explica por quê: banco e
> multi-organização exigem recriar (as tabelas de permissão só nascem com a coluna de contexto
> antes do `migrate`), e as credenciais do administrador **não são sincronizadas pelo seeder** —
> ele garante que exista um administrador, e não que ele espelhe o `.env`, porque roda em todo
> `db:seed` e sobrescrever ali reverteria a senha trocada à mão.
>
> Para trocar e-mail ou senha do administrador, o caminho é deliberado:
>
> ```bash
> php artisan kit:admin
> php artisan kit:admin --email=novo@example.com --senha=segredo --force   # sem perguntas — evite: a senha fica no histórico do shell
> ```
>
> Ele pede confirmação, nunca ecoa a senha, recusa e-mail que já pertence a outra conta e **para**
> se houver mais de um `master_global` — em vez de escolher um por ordenação. A tela de perfil do
> painel também serve.
>
> Em Linux, macOS e WSL as perguntas aparecem no `create-project` e nada disso é necessário.

> A multi-organização é o item que mais compensa decidir agora: ligada na instalação, ela custa zero; ligada depois, o `kit:tenancy` **recria o banco** (as tabelas de permissão só nascem com a coluna de contexto se a flag estiver ativa antes do migrate).

![Instalação do starter-kit-easy em um comando](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/install.gif)

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
| **App** | `/app` | A operação do negócio. **Vem vazio de propósito** — é aqui que seu projeto nasce | `master_global`, `panel_user`, `admin_app` (com tenancy) |
| **Admin** | `/admin` | Usuários, papéis e permissões (Shield), catálogo de agentes de IA, autoria de onboarding | `master_global`, `admin` |
| **Infra** | `/infra` | Health checks, backups, filas, logs, exceções, trilha de e-mails, lixeira, auditoria, caches, comandos, Pulse, custos de IA | `master_global`, `infra` |

**Quem entra vem do papel, não de uma lista no código.** Cada papel declara em qual painel vale, na coluna `roles.painel` — é o campo **Painel** na tela `/admin` → Papéis. `App\Models\User::canAccessPanel()` compara essa coluna com o painel que está sendo aberto. Criar um papel e escolher o painel dele **é** o ato de dar acesso.

Nulo **não** é coringa: papel sem painel só carrega permissões e não abre painel algum. O papel `master_global` entra nos três de outro jeito — ele vence qualquer gate via `Gate::before` (`App\Providers\KitServiceProvider`), sem precisar de permissions no banco, e o `canAccessPanel()` o libera antes de olhar a coluna.

Nos painéis **sem** tenancy (`/admin`, `/infra`) o papel precisa estar atribuído no contexto global: ser `admin` dentro de uma organização não é credencial para administrar a instalação. No `/app` vale o papel em qualquer organização — qual delas você abre é decidido depois, por `canAccessTenant()`.

**O badge do menu do usuário mostra o papel da organização ABERTA.** Quem pertence a mais de uma pode ter papéis diferentes em cada — `panel_user` numa, `admin_app` noutra —, e o badge acompanha a troca de organização. Sem papel na organização aberta, não há badge: entrar no painel não depende da organização (é o parágrafo acima), mas a exibição sim. Nos painéis sem tenancy nada muda, porque lá não há organização corrente.

> Com o [modo multi-tenant](#multi-tenancy-opt-in) ligado, o **App** vira `/app/{tenant}` e passa a mostrar só os dados do tenant selecionado. Admin e Infra seguem globais.

Separar admin de infra é o ponto do kit: quem administra usuários não precisa (nem deve) enxergar logs, filas e comandos operacionais, e vice-versa.

### Como cada um se parece

| Login | Administração |
|---|---|
| [![Tela de login](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/login.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login.png) | [![Painel admin](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-admin.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-admin.png) |
| Auth Designer em duas colunas — a arte mostra o nome da aplicação | Usuários, papéis, agentes de IA e indicadores de administração |

| Infraestrutura | Negócio |
|---|---|
| [![Painel infra](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-infra.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-infra.png) | [![Painel app](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-app.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-app.png) |
| Saúde, filas, trilhas, comandos e custos de IA — agrupados em Observabilidade, IA, Trilhas e Sistema | Vazio de propósito: é onde o seu projeto nasce |

Mais telas: [saúde da aplicação](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/infra-health.png) · [usuários](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-users.png) · [permissões (Shield)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-roles.png) · [catálogo de agentes de IA](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-agentes-ia.png) · [central de comandos](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/infra-comandos.png) · [busca ⌘K](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/spotlight.png) · [acesso negado](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/erro-403.png)

## Nossos números

Não é vitrine: é o inventário de tudo que já existe, e o que você não vai precisar escrever.

| | `/app` | `/admin` | `/infra` | **Total** |
|---|---:|---:|---:|---:|
| **Telas navegáveis** | 12 | 28 | 27 | **67** |
| Resources | 4 | 8 | 8 | **20** |
| Páginas próprias | 4 | 4 | 12 | **20** |
| Widgets | 1 | 9 | 19 | **29** |
| Rotas `GET` | 21 | 35 | 33 | **89** |

O `/app` é o menor de propósito — ele nasce **vazio**, porque é onde o seu negócio entra. Os outros
dois já vêm completos.

| Fundação | |
|---|---:|
| Pacotes de produção | **55** |
| Pacotes de desenvolvimento | **19** |
| Migrations | **54** |
| Policies | **14** |
| Comandos `kit:*` | **7** |

| Qualidade | |
|---|---:|
| Casos de teste (suíte `Kit`, medida em 2026-08-26) | **mais de 1.200**, com mais de 3.500 asserções |
| Telas varridas em navegador real | **55** |
| Arquivos de teste | **84** em `Kit` + `Tenancy` (105 no total) |
| PHPStan | **level 7**, zero erros |
| FilaCheck | **17** regras, todas passando |

| Documentação | |
|---|---:|
| Documentos de referência (`wikis/`) | **9** |
| Features especificadas (`wikis/specs/`) | **28** |
| Project rules para agentes de IA (`.ai/rules/`) | **14** |

> O detalhamento saiu daqui e está no site: **[Referência](https://gsferro.github.io/filament-starter-kit-easy/pt/referencia/)** e **[Começar](https://gsferro.github.io/filament-starter-kit-easy/pt/comecar/)**.

## O que já vem pronto

**Porta de entrada**
- **Página de boas-vindas na rota `/`**, no lugar da welcome padrão do Laravel: um cartão por
  painel e as informações do que o `kit:install` personalizou ([detalhes](#a-rota--é-pública-e-não-mostra-segredo))

**Administração e segurança**
- Shield (papéis e permissões com UI) sobre spatie/laravel-permission
- Breezy: perfil do usuário, avatar, 2FA e passkeys
- Auth Designer: tela de login em duas colunas — a arte **mostra o nome da aplicação**, lido de `APP_NAME` a cada carregamento; para usar a sua imagem, envie em `/admin/configuracoes-do-kit`
- **Registro aberto opcional** (desligado por default): cadastro sem convite no `/app`, com papel único, aprovação manual e validação de e-mail — cada um em sua chave ([detalhes](#registro-aberto-e-aprovação))
- Lockscreen: bloqueio de sessão por inatividade (30 min), registrado nos 3 painéis — a tela de bloqueio usa o mesmo layout do login (Auth Designer), não o layout simples do Filament
- Impersonate, log de autenticação, auditoria de alterações (owen-it)
- Panel Switch: troca de painel pelo menu do usuário
- **Proteção anti-robô opcional** (desligada por default): reCAPTCHA v2/v3, Turnstile ou hCaptcha nas telas de login, recuperação de senha e registro, via `ddr/filament-captcha` ([detalhes](#proteção-anti-robô))

**Observabilidade e manutenção (painel infra)**
- Spatie Health com checks de banco, cache, filas, agendador, disco (exceto no Windows), debug mode, ambiente, app otimizado e IA local
- Backup Monitor (spatie/laravel-backup), Jobs Monitor, Logs Explorer (sem botão de apagar — trilha é evidência)
- **Exceções agrupadas** por tipo e frequência — o que Health, Pulse e arquivo de log não respondem
- **Trilha de e-mails enviados**: separa "não foi enviado" de "foi enviado e caiu no spam"
- **Lixeira**: restaura o que foi apagado com `SoftDeletes` ([detalhes](#trilhas-do-infra-exceções-e-mails-e-lixeira))
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
- **Busca ⌘K** no lugar do campo nativo da topbar: encontra registros, telas, páginas e ações de criação — tudo recortado por permissão (detalhes abaixo)
- Badges de contagem animados no menu, centro de notificações com abas, indicador de ambiente
- **Dashboards já preenchidos** nos painéis admin e infra: 24 widgets (stat cards com contador animado, funis, metas, breakdowns, timelines) sobre os dados que os painéis já têm — nada de tela vazia esperando você
- Páginas de erro brandadas (Sentinel) em pt-BR — a de 403 só mostra o diagnóstico de permissão fora de produção
- UI 100% em pt-BR, inclusive nos plugins que só trazem inglês (traduções em `lang/vendor/`)
- **Seletor de idioma** nos três painéis e nas telas de login — dirigido por dado, não por flag (detalhes abaixo)
- **Camada de mídia** (spatie/laravel-medialibrary) nos componentes do Filament: upload, coleções e conversões em formulário, tabela e infolist ([detalhes](#anexos-e-mídia))

## Documentação completa

O que antes vivia aqui — e eram mais de duas mil linhas — agora tem site próprio, com busca e
navegação: **[https://gsferro.github.io/filament-starter-kit-easy/pt/](https://gsferro.github.io/filament-starter-kit-easy/pt/)**

| Grupo | O que tem lá |
|---|---|
| [Começar](https://gsferro.github.io/filament-starter-kit-easy/pt/comecar/) | instalação avançada, banco, comandos, personalização, e como atualizar um projeto que já nasceu do kit |
| [Autenticação](https://gsferro.github.io/filament-starter-kit-easy/pt/autenticacao/) | convites, registro aberto, login social, proteção anti-robô, estados do usuário |
| [Recursos](https://gsferro.github.io/filament-starter-kit-easy/pt/recursos/) | multi-tenancy, anexos e mídia, import/export CSV, trilhas do `/infra`, configurações do kit, hub de cartões |
| [Operação](https://gsferro.github.io/filament-starter-kit-easy/pt/operacao/) | agentes de IA, roteiro das 68 features, convenções, o que fazer depois de criar um Resource |
| [Referência](https://gsferro.github.io/filament-starter-kit-easy/pt/referencia/) | qualidade de código, busca e idioma, os ~70 pacotes instalados |

A versão em inglês fica em **[https://gsferro.github.io/filament-starter-kit-easy/en/](https://gsferro.github.io/filament-starter-kit-easy/en/)**.

## Requisitos

- PHP 8.3+ e Composer 2
- Node 20+ (opcional — sem ele a instalação segue e avisa como fazer o build depois)
- Docker (opcional — só para Postgres/MySQL, Redis, IA local e e-mail)

## Banco de dados

**A instalação pergunta** — SQLite, PostgreSQL ou MySQL. O padrão é **SQLite**, para não depender de nada.

**PostgreSQL é o recomendado**, e por um motivo funcional: ele é o único que traz `pgvector`, de que dependem as funções de IA local que usam busca semântica (embeddings). Com SQLite ou MySQL o resto do kit roda igual — só essas funções ficam indisponíveis.

**Postgres e MySQL têm container no kit** — o MySQL em profile próprio, porque a instalação escolhe um banco só. Os comandos estão na seção [Docker](#docker).

Escolhendo Postgres na instalação, o `.env` já sai com o bloco que o `docker-compose.yml` lê, e falta só subir o container. Se ele não estiver de pé na hora da instalação, o kit avisa, **pula as migrations** e diz o comando para refazer:

```bash
docker compose up -d
php artisan migrate --seed
```

Para trocar depois da instalação, suba os containers e copie as variáveis:

```bash
docker compose up -d              # pgsql (com pgvector) + redis
# copie o bloco de banco de .env.docker para o seu .env
php artisan migrate --seed
```

## Docker

Tudo é opt-in por profile. Um container por feature:

```bash
docker compose up -d                            # pgsql + redis
docker compose up -d mysql redis                # MySQL em vez do Postgres
docker compose --profile ai up -d               # + llama.cpp (chat e embeddings)
docker compose --profile mail up -d             # + mailpit (1025 / 8025)
docker compose --profile full up -d             # infra completa
docker compose --profile app up -d --build      # a aplicação containerizada
docker compose --profile realtime up -d reverb pulse
```

| Serviço | Porta | Profile |
|---|---|---|
| PostgreSQL 17 + pgvector | 5432 | base |
| MySQL 8 | 3306 | `mysql` |
| Redis 7 (só cache) | 6379 | base |
| llama.cpp (chat) | 8080 | `ai` |
| llama.cpp (embeddings) | 8081 | `ai` |
| Mailpit | 1025 / 8025 | `mail` |
| App (nginx + php-fpm) | 8000 | `app` |
| Reverb (WebSocket) | 8090 | `app`, `realtime` |

O Reverb usa 8090 e não o default 8080 para não colidir com o llama.cpp.

Nenhum serviço tem `container_name` fixo: o prefixo vem de `COMPOSE_PROJECT_NAME`, que o `kit:install` grava com o nome do seu projeto. [Detalhes no site](https://gsferro.github.io/filament-starter-kit-easy/pt/comecar/instalacao-avancada.html).

### Atualizando a stack na máquina que a hospeda

`./deploy_docker_local.sh` roda **no host dos containers** (não na máquina de desenvolvimento) e faz a sequência inteira: `git pull`, rebuild da imagem, `--profile app up -d`, migrations, `optimize:clear`, health check em `/up` e sonda TCP do Reverb. A saída fica em `storage/logs/deploy_docker_local.log`.

```bash
./deploy_docker_local.sh
./deploy_docker_local.sh --recreate   # quando o .env mudou
```

O rebuild vem **depois** do pull porque a imagem é self-contained (o código é assado nela) — rebuild antes reassa o código velho. E como ele recria `reverb` e `pulse`, que estão no mesmo profile `app`, não há comando de restart à parte: processo long-running não vê código novo sem reiniciar.

`--recreate` acrescenta `--force-recreate`, e é necessário quando o `.env` mudou: o Compose lê o `env_file` na **criação** do container, então um container já existente mantém os valores antigos. Se o `.env.example` mudou no pull, o script avisa.

## Comandos

```bash
composer dev          # servidor + fila + vite juntos
composer test         # pint + phpstan + filacheck + a suíte inteira
composer test:kit     # só os testes do kit (a fundação), em paralelo
composer lint         # formata o código
composer lint:check   # só verifica a formatação, sem alterar nada (o que a CI roda)
composer filament:check   # só o lint específico de Filament (FilaCheck)
composer refactor:preview # o que o Rector reescreveria (dry-run) — FORA do composer test
composer refactor:apply   # aplica a reescrita do Rector — FORA do composer test
composer upgrade:filament # roda o vendor/bin/filament-v5 (filament/upgrade já está no require-dev)
php artisan kit:install --force   # reinstala do zero (APAGA o SQLite) e refaz as perguntas
php artisan kit:install --custom   # refaz só nome e cor, sem tocar no banco
php artisan kit:install --no-custom   # instala sem perguntar nada
php artisan kit:install --no-npm      # pula a instalação e o build dos assets front-end
php artisan kit:install --no-seed     # não popula o banco (papéis, usuário inicial, agentes de IA)
php artisan kit:install --no-support  # pula o convite para dar uma estrela ao kit no GitHub
#   --create-project é uso interno do post-create-project-cmd: apaga o que só serve ao repositório do kit
php artisan kit:admin             # troca e-mail e senha do administrador (pede confirmação)
php artisan kit:admin --email=x --senha=y --force   # sem perguntas — evite: a senha fica no histórico do shell
php artisan kit:info              # mostra como o projeto está customizado e de onde cada valor vem
php artisan kit:update            # traz melhorias de uma versão nova do kit
php artisan kit:tenancy           # liga o modo multi-tenant (opt-in)
```

## Personalize seu projeto

**Os cinco primeiros o instalador já pergunta** (ver [a instalação](#starter-kit-easy)) — a lista abaixo é para mudar depois, ou para quem pulou as perguntas.

| # | O quê | Onde | Perguntado na instalação? |
|---|---|---|---|
| 1 | **Nome** | `APP_NAME` no `.env` | ✅ |
| 2 | **Banco de dados** | bloco `DB_*` no `.env` | ✅ |
| 3 | **Credenciais do seeder** | `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` no `.env` | ✅ |
| 4 | **Cor primária** | `KIT_COR_PRIMARIA` no `.env` (nome de uma cor da paleta do Filament), ou `KIT_COR_PRIMARIA_HEX` com um hexadecimal livre — o hex vence o nome quando os dois estão preenchidos | ✅ |
| 5 | **[Multi-tenancy](#multi-tenancy-opt-in)** | `php artisan kit:tenancy`, e o termo exibido em `config/kit.php` → `tenancy.label` | ✅ |
| 6 | **Arte do login** | nenhuma: ela **mostra o nome da aplicação** (`APP_NAME`) sozinha. Para trocar por uma imagem sua, envie em `/admin/configuracoes-do-kit` | ✅ (pelo nome) |
| 7 | **Acesso aos painéis** | o papel de cada usuário (`/admin` → Papéis, campo *Painel*); a regra que o lê é `App\Models\User::canAccessPanel()` | — |
| 8 | **Matriz de permissões** | `database/seeders/PapeisSeeder.php` | — |
| 9 | **Health checks** | `KitServiceProvider::configureHealthChecks()` | — |
| 10 | **Comandos da UI** | `config/command-center.php` | — |
| 11 | **Backups** | destino e agenda em `config/backup.php` | — |
| 12 | **Agente de IA** | `/admin` → Agentes de IA (ou `database/seeders/AssistenteSeeder.php`) | — |
| 13 | **[Idiomas do painel](#o-seletor-de-idioma)** | `config/kit.php` → `idiomas` (lista de locales; com um só, o seletor não aparece) | — |
| 14 | **[Retenção das trilhas](#retenção-o-número-é-a-intenção-o-agendador-é-a-execução)** | `KIT_RETENCAO_EXCECOES_DIAS` / `KIT_RETENCAO_EMAILS_DIAS` no `.env` | — |
| 15 | **[Disco da mídia](#anexos-e-mídia)** | `MEDIA_DISK` no `.env` (`local` por padrão — privado, servido por URL assinada) | `php artisan kit:midia-privada` migra a mídia já gravada em disco público |
| 16 | **[Import e export CSV](#import-e-export-csv)** | a Action em cada `app/Filament/**/Pages/List*.php` (ligada ou comentada); a permissão em `config/filament-shield.php` → `policies.methods`; a retenção do histórico em `KIT_RETENCAO_IMPORTACOES_DIAS` / `KIT_RETENCAO_EXPORTACOES_DIAS` no `.env` | ressemeie `ShieldPermissionsSeeder` + `PapeisSeeder` depois de mexer no config |

Os onze últimos não entram nas perguntas porque são **código ou dado de tela**, não um valor que caiba num prompt de terminal. O instalador os lista no resumo final, com o arquivo de cada um.

> ⚠️ O item 5 é o único que **não** é "edite um arquivo" depois de instalado: o `kit:tenancy` roda `migrate:fresh --seed` e **apaga os dados**. Ele exige árvore git limpa e confirmação explícita. **Respondido na instalação, ele não apaga nada** — o banco ainda nem existe, e é essa a hora certa de decidir.

> A cor primária vale para os três painéis. Com o [modo multi-tenant](#multi-tenancy-opt-in) ligado, a cor de cada organização **vence** esta dentro de `/app/{slug}` — o `/admin` e o `/infra` continuam com a do projeto. Para uma paleta completa, e não só a `primary`, o caminho continua sendo `->colors([...])` em cada `app/Providers/Filament/*PanelProvider.php`.

## Atualizando um projeto que já nasceu do kit

**O kit é um ponto de partida, não uma dependência.** Depois do `create-project` o projeto é seu: você renomeia painéis, muda `canAccessPanel()`, edita seeders. Por isso **não existe** um `kit:update` que sobrescreve arquivos — ele reescreveria justamente o que você personalizou, e um starter kit que estraga o projeto do usuário não serve para nada.

O que muda separa-se em três camadas, e cada uma tem um caminho próprio:

| Camada | O que é | Como atualizar |
|---|---|---|
| **Dependências** | Filament, plugins, Laravel | `composer update` — é a maior parte das melhorias e chega sozinha |
| **Cola do kit** | providers, traits, widgets, views de erro | diff manual contra a tag nova (abaixo) |
| **Seu negócio** | tudo que você escreveu | nunca é tocado |

## Solução de problemas

- **403 em todos os painéis, logo depois de autenticar** — o usuário não tem papel nenhum, ou o papel dele está sem painel declarado (`roles.painel` vazio não é coringa: não abre nada). Dê o papel em `/admin` → Usuários, ou preencha o campo *Painel* em `/admin` → Papéis.
- **`/infra` ou `/admin` dando 403** — seu usuário precisa de um papel cujo painel seja esse (`master_global`, `admin` ou `infra`), e com a tenancy ligada o papel tem de estar atribuído no contexto global. A tela de 403 mostra qual permissão faltou, mas **só fora de produção**: em produção ela não revela papéis nem permissões.
- **Assets do Filament sumidos** — `php artisan filament:assets`.
- **Pulse sem dados** — falta o daemon: `php artisan pulse:check` (ou o serviço `pulse` do compose).
- **Sininho não atualiza em tempo real** — `BROADCAST_CONNECTION=reverb` exige o processo Reverb no ar; sem ele o kit cai para polling de 30s.
- **Assistente de IA indisponível** — suba `docker compose --profile ai up -d` (o primeiro boot baixa ~4,5 GB de modelo) ou troque `AI_PROVIDER` para um provider SaaS com API key.

## Licença

MIT.
