---
title: Instalação avançada
parent: Começar
grand_parent: Português
nav_order: 1
---

# Instalação avançada

## Banco de dados

**A instalação pergunta** — SQLite, PostgreSQL ou MySQL. O padrão é **SQLite**, para não depender de nada.

**PostgreSQL é o recomendado**, e por um motivo funcional: ele é o único que traz `pgvector`, de que dependem as funções de IA local que usam busca semântica (embeddings). Com SQLite ou MySQL o resto do kit roda igual — só essas funções ficam indisponíveis.

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
php artisan kit:update            # traz melhorias de uma versão nova do kit
php artisan kit:tenancy           # liga o modo multi-tenant (opt-in)
```
Os aprofundamentos de qualidade que ficavam sob esta seção — FilaCheck, Rector, a suíte de testes,
as imagens do README e a varredura SFDIPOT — estão em
[Qualidade de código](../referencia/qualidade-de-codigo.md).

## Personalize seu projeto

**Os cinco primeiros o instalador já pergunta** (ver [a instalação](https://github.com/gsferro/filament-starter-kit-easy#starter-kit-easy)) — a lista abaixo é para mudar depois, ou para quem pulou as perguntas.

| # | O quê | Onde | Perguntado na instalação? |
|---|---|---|---|
| 1 | **Nome** | `APP_NAME` no `.env` | ✅ |
| 2 | **Banco de dados** | bloco `DB_*` no `.env` | ✅ |
| 3 | **Credenciais do seeder** | `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` no `.env` | ✅ |
| 4 | **Cor primária** | `KIT_COR_PRIMARIA` no `.env` (nome de uma cor da paleta do Filament), ou `KIT_COR_PRIMARIA_HEX` com um hexadecimal livre — o hex vence o nome quando os dois estão preenchidos | ✅ |
| 5 | **[Multi-tenancy](../recursos/multi-tenancy.md)** | `php artisan kit:tenancy`, e o termo exibido em `config/kit.php` → `tenancy.label` | ✅ |
| 6 | **Arte do login** | nenhuma: ela **mostra o nome da aplicação** (`APP_NAME`) sozinha. Para trocar por uma imagem sua, envie em `/admin/configuracoes-do-kit` | ✅ (pelo nome) |
| 7 | **Acesso aos painéis** | o papel de cada usuário (`/admin` → Papéis, campo *Painel*); a regra que o lê é `App\Models\User::canAccessPanel()` | — |
| 8 | **Matriz de permissões** | `database/seeders/PapeisSeeder.php` | — |
| 9 | **Health checks** | `KitServiceProvider::configureHealthChecks()` | — |
| 10 | **Comandos da UI** | `config/command-center.php` | — |
| 11 | **Backups** | destino e agenda em `config/backup.php` | — |
| 12 | **Agente de IA** | `/admin` → Agentes de IA (ou `database/seeders/AssistenteSeeder.php`) | — |
| 13 | **[Idiomas do painel](../referencia/busca-e-idioma.md#o-seletor-de-idioma)** | `config/kit.php` → `idiomas` (lista de locales; com um só, o seletor não aparece) | — |
| 14 | **[Retenção das trilhas](../recursos/trilhas-de-infraestrutura.md#retenção-o-número-é-a-intenção-o-agendador-é-a-execução)** | `KIT_RETENCAO_EXCECOES_DIAS` / `KIT_RETENCAO_EMAILS_DIAS` no `.env` | — |
| 15 | **[Disco da mídia](../recursos/anexos-e-midia.md)** | `MEDIA_DISK` no `.env` (`local` por padrão — privado, servido por URL assinada) | `php artisan kit:midia-privada` migra a mídia já gravada em disco público |
| 16 | **[Import e export CSV](../recursos/import-export-csv.md)** | a Action em cada `app/Filament/**/Pages/List*.php` (ligada ou comentada); a permissão em `config/filament-shield.php` → `policies.methods`; a retenção do histórico em `KIT_RETENCAO_IMPORTACOES_DIAS` / `KIT_RETENCAO_EXPORTACOES_DIAS` no `.env` | ressemeie `ShieldPermissionsSeeder` + `PapeisSeeder` depois de mexer no config |

Os onze últimos não entram nas perguntas porque são **código ou dado de tela**, não um valor que caiba num prompt de terminal. O instalador os lista no resumo final, com o arquivo de cada um.

> ⚠️ O item 5 é o único que **não** é "edite um arquivo" depois de instalado: o `kit:tenancy` roda `migrate:fresh --seed` e **apaga os dados**. Ele exige árvore git limpa e confirmação explícita. **Respondido na instalação, ele não apaga nada** — o banco ainda nem existe, e é essa a hora certa de decidir.

> A cor primária vale para os três painéis. Com o [modo multi-tenant](../recursos/multi-tenancy.md) ligado, a cor de cada organização **vence** esta dentro de `/app/{slug}` — o `/admin` e o `/infra` continuam com a do projeto. Para uma paleta completa, e não só a `primary`, o caminho continua sendo `->colors([...])` em cada `app/Providers/Filament/*PanelProvider.php`.

