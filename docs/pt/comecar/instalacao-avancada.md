---
title: "Instalação avançada"
description: "A instalação pergunta — SQLite, PostgreSQL ou MySQL. O padrão é SQLite, para não depender de nada."
sidebar:
  order: 1
---
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

### MySQL também tem container

Escolhendo MySQL, o `.env` sai apontando para `127.0.0.1:3306` com usuário `root`, e o kit sobe o
servidor para você. O comando é diferente do de Postgres, e o motivo importa:

```bash
docker compose up -d mysql redis
php artisan migrate --seed
```

O MySQL é o **único banco em profile próprio**, porque a instalação escolhe um banco só — deixá-lo
sem profile faria toda instalação subir Postgres e MySQL juntos. Nomear os serviços na linha de
comando liga o profile do MySQL **e** restringe a subida ao que foi nomeado, então o Postgres do
profile padrão não sobe. Por isso o `redis` precisa estar escrito ali: nomear serviços desliga o
resto do padrão junto.

Dois detalhes da imagem, que explicam o que o instalador grava:

- **O usuário é `root`.** A imagem oficial recusa criar `root` por `MYSQL_USER` — mantido esse
  usuário, o único caminho é a senha de root.
- **A senha não é vazia.** `mysql:8.0` recusa inicializar sem senha de root, e o instalador grava
  `secret`, a mesma que o container lê. Trazendo o seu próprio servidor, ajuste `DB_PASSWORD` no
  `.env` — como já se faz com um Postgres externo.

- **A porta do host é `FORWARD_MYSQL_PORT`**, com default 3306, e não o `FORWARD_DB_PORT` do
  Postgres. São chaves separadas porque no profile `app` os dois bancos sobem juntos, e uma variável
  só faria os dois disputarem a mesma porta. Se já houver um MySQL na sua máquina, o Docker recusa
  com `Bind for 0.0.0.0:3306 failed: port is already allocated` — troque a chave:

  ```bash
  FORWARD_MYSQL_PORT=3399 docker compose up -d mysql redis
  ```

  e ajuste `DB_PORT` no `.env` para a mesma porta.

A opção da IA local não muda: busca semântica e embeddings dependem de `pgvector`, que só existe no
Postgres.

## Domínio local no fim da instalação

Terminado o trabalho pesado — migrations, seeders e assets —, o `kit:install` faz a última
pergunta: **cadastrar um domínio local** para abrir o projeto em `http://meu-projeto.test` no lugar
de `http://localhost:8000`.

```text
Cadastrar um domínio local (ex.: http://meu-projeto.test)? [y/N]
Qual domínio? › meu-projeto.test
```

Três coisas valem saber antes de responder:

- **É opt-in.** A resposta padrão é *não*: Enter segue com `http://localhost:8000`, exatamente como
  antes. A pergunta só aparece quando há terminal — em CI, build Docker ou `--no-interaction` a
  etapa nem acontece. Não existe flag para ligá-la ou desligá-la, e ela também não depende do
  `--force`.
- **A sugestão vem do nome que você escolheu** na primeira pergunta: `Loja do Ferro` vira
  `loja-do-ferro.test`. Você pode digitar outro — sem `http://`, sem barra e sem espaço, e dentro
  dos limites do DNS (63 caracteres por parte do nome, 253 no total). O sufixo é `.test`, reservado
  pela RFC 6761; `.local` é recusado, porque a RFC 6762 o reserva ao mDNS.
- **Domínio público pede um sim a mais.** Se o que você digitar não terminar em `.test`,
  `.localhost`, `.example` ou `.invalid` — os quatro sufixos que a RFC 6761 reserva ao uso local —,
  o comando pergunta de novo, dizendo o que vai acontecer:

  ```text
  minha-empresa.com.br é um domínio público. Apontar mesmo assim para 127.0.0.1? [y/N]
  ```

  A resposta padrão é *não*, e por um motivo concreto: apontar um domínio real para `127.0.0.1`
  **impede o acesso ao site de verdade nesta máquina**, e a linha fica no `hosts` até alguém
  removê-la à mão — remover não faz parte do que o `kit:install` faz. É a proteção contra o erro de
  digitação que custa mais caro.
- **Aceitando, duas coisas acontecem**: uma linha `127.0.0.1` é acrescentada ao arquivo `hosts` da
  máquina, e a chave `APP_URL` do seu `.env` passa a ser `http://meu-projeto.test` — que é o
  endereço que o próprio comando imprime no fim, já com o nome novo.

No **Windows** a etapa executa o cadastro, pedindo elevação pelo UAC: uma janela se abre, e o que
decide se deu certo é o kit **reler o arquivo** depois — código de saída de processo elevado não
prova nada. No **Linux** e no **macOS** ela imprime a linha pronta para você colar com `sudo`, e
ajusta a `APP_URL` do mesmo jeito.

Nada disso aborta a instalação — nem uma pergunta interrompida no meio. Elevação negada, antivírus
bloqueando o arquivo ou `pwsh` ausente viram **aviso** no fim, com o comando pronto para colar (o do
seu sistema: `Start-Process … -Verb RunAs` no Windows, `sudo tee -a` no Linux e no macOS). O aviso
também diz **em que estado a `APP_URL` ficou**: o normal é ela não ser tocada, para a tela final não
mostrar um endereço que não responde.

Se o domínio já resolve **para esta máquina** — instalação anterior, ou Laravel Herd e Valet, que
respondem por `*.test` sozinhos —, o arquivo `hosts` não é tocado e só a `APP_URL` é ajustada. Note
o "para esta máquina": só `127.0.0.0/8` e `::1` contam. Um DNS corporativo com curinga responde por
qualquer nome, inclusive um que você acabou de inventar, e tratar isso como "já está pronto" deixaria
a tela final apontando para um servidor de terceiro. Nesse caso o comando avisa qual endereço
respondeu e cadastra a linha local por cima.

A receita manual completa, com as armadilhas de elevação, o `FORWARD_APP_PORT` e o efeito sobre
login social e Vite, está em [Domínio local](../dominio-local/).

## O nome dos containers

Nenhum serviço do `docker-compose.yml` declara `container_name`. O prefixo de todo container e de
toda rede vem de `COMPOSE_PROJECT_NAME`, no `.env`, e o `kit:install` grava ali o nome que você
escolheu — em minúsculas e com hífen, que é o formato que o Compose aceita:

```bash
$ docker compose ps
minha-app-pgsql-1
minha-app-redis-1
```

Sem essa chave vale `starter-kit`, que é o piso escrito no próprio `docker-compose.yml`.

Dois pontos práticos:

- **Projeto que já nasceu de uma versão anterior do kit** não recebe a chave pelo `kit:update`, que
  não mexe em `.env`. Rode `php artisan kit:install --custom`, que refaz nome e cor, ou acrescente a
  linha à mão.
- **Trocar o nome depois de já ter subido containers cria volumes novos.** Os dados antigos
  continuam no volume do nome anterior; migre-os antes, ou troque o nome antes do primeiro `up`.

## A aplicação containerizada e o banco

O profile `app` sobe a aplicação inteira em container. Ele fala com o Postgres por padrão; para
apontá-lo ao MySQL, defina no `.env`:

```
DOCKER_DB_SERVICE=mysql
```

e ligue os dois profiles juntos, senão o container do banco não sobe e o host não existe:

```bash
docker compose --profile app --profile mysql up -d --build
```

Um aviso honesto: nessa combinação um container de Postgres sobe ocioso, porque os serviços do
profile `app` dependem dele para ordenar o boot. A alternativa foi medida e é pior — sem essa
dependência, `docker compose --profile app up -d` sobe a aplicação **sem banco nenhum** e sem erro.

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
Os aprofundamentos de qualidade que ficavam sob esta seção — FilaCheck, Rector, a suíte de testes,
as imagens do README e a varredura SFDIPOT — estão em
[Qualidade de código](../../referencia/qualidade-de-codigo/).

## Personalize seu projeto

**Os cinco primeiros o instalador já pergunta** (ver [a instalação](https://github.com/gsferro/filament-starter-kit-easy#starter-kit-easy)) — a lista abaixo é para mudar depois, ou para quem pulou as perguntas.

`php artisan kit:info` mostra o valor atual de cada item abaixo, e se ele está vindo do banco ou do `.env`.

| # | O quê | Onde | Perguntado na instalação? |
|---|---|---|---|
| 1 | **Nome** | `APP_NAME` no `.env` | ✅ |
| 2 | **Banco de dados** | bloco `DB_*` no `.env` | ✅ |
| 3 | **Credenciais do seeder** | `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` no `.env` | ✅ |
| 4 | **Cor primária** | `KIT_COR_PRIMARIA` no `.env` (nome de uma cor da paleta do Filament), ou `KIT_COR_PRIMARIA_HEX` com um hexadecimal livre — o hex vence o nome quando os dois estão preenchidos | ✅ |
| 5 | **[Multi-tenancy](../../recursos/multi-tenancy/)** | `php artisan kit:tenancy`, e o termo exibido em `config/kit.php` → `tenancy.label` | ✅ |
| 6 | **Arte do login** | nenhuma: ela **mostra o nome da aplicação** (`APP_NAME`) sozinha. Para trocar por uma imagem sua, envie em `/admin/configuracoes-da-aplicacao` | ✅ (pelo nome) |
| 7 | **Acesso aos painéis** | o papel de cada usuário (`/admin` → Papéis, campo *Painel*); a regra que o lê é `App\Models\User::canAccessPanel()` | — |
| 8 | **Matriz de permissões** | `database/seeders/PapeisSeeder.php` | — |
| 9 | **Health checks** | `KitServiceProvider::configureHealthChecks()` | — |
| 10 | **Comandos da UI** | `config/command-center.php` | — |
| 11 | **Backups** | destino e agenda em `config/backup.php` | — |
| 12 | **Agente de IA** | `/admin` → Agentes de IA (ou `database/seeders/AssistenteSeeder.php`) | — |
| 13 | **[Idiomas do painel](../../referencia/busca-e-idioma/#o-seletor-de-idioma)** | `config/kit.php` → `idiomas` (lista de locales; com um só, o seletor não aparece) | — |
| 14 | **[Retenção das trilhas](../../recursos/trilhas-de-infraestrutura/#retenção-o-número-é-a-intenção-o-agendador-é-a-execução)** | `KIT_RETENCAO_EXCECOES_DIAS` / `KIT_RETENCAO_EMAILS_DIAS` no `.env` | — |
| 15 | **[Disco da mídia](../../recursos/anexos-e-midia/)** | `MEDIA_DISK` no `.env` (`local` por padrão — privado, servido por URL assinada) | `php artisan kit:midia-privada` migra a mídia já gravada em disco público |
| 16 | **[Import e export CSV](../../recursos/import-export-csv/)** | a Action em cada `app/Filament/**/Pages/List*.php` (ligada ou comentada); a permissão em `config/filament-shield.php` → `policies.methods`; a retenção do histórico em `KIT_RETENCAO_IMPORTACOES_DIAS` / `KIT_RETENCAO_EXPORTACOES_DIAS` no `.env` | ressemeie `ShieldPermissionsSeeder` + `PapeisSeeder` depois de mexer no config |

Os onze últimos não entram nas perguntas porque são **código ou dado de tela**, não um valor que caiba num prompt de terminal. O instalador os lista no resumo final, com o arquivo de cada um.

> ⚠️ O item 5 é o único que **não** é "edite um arquivo" depois de instalado: o `kit:tenancy` roda `migrate:fresh --seed` e **apaga os dados**. Ele exige árvore git limpa e confirmação explícita. **Respondido na instalação, ele não apaga nada** — o banco ainda nem existe, e é essa a hora certa de decidir.

> A cor primária vale para os três painéis. Com o [modo multi-tenant](../../recursos/multi-tenancy/) ligado, a cor de cada organização **vence** esta dentro de `/app/{slug}` — o `/admin` e o `/infra` continuam com a do projeto. Para uma paleta completa, e não só a `primary`, o caminho continua sendo `->colors([...])` em cada `app/Providers/Filament/*PanelProvider.php`.

