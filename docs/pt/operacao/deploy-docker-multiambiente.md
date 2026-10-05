---
title: "Vários ambientes no mesmo servidor"
description: "Três ambientes não produtivos do mesmo projeto — dev, teste, homologação — no mesmo servidor, cada um com os próprios containers, banco e volumes, e um…"
sidebar:
  order: 6
---
## O que esta página resolve

Três ambientes não produtivos do mesmo projeto — dev, teste, homologação — no **mesmo servidor**,
cada um com os próprios containers, banco e volumes, e um **Traefik** na frente roteando por
hostname com TLS. Tudo o que está aqui é **opcional**: quem continua subindo uma stack com
`docker compose --profile app up -d --build` não é afetado por nada desta página, e o
`docker-compose.yml` do kit não muda uma linha.

## O mecanismo central: um nome de projeto por ambiente

O Compose isola por **nome de projeto**. O kit já foi desenhado para isso: nenhum serviço declara
`container_name`, e o prefixo de todo container, rede e volume vem de `COMPOSE_PROJECT_NAME` no
`.env` — o `kit:install` grava ali o nome do seu projeto ([o nome dos
containers](../../comecar/instalacao-avancada/#o-nome-dos-containers)). Dê a cada ambiente um nome
distinto e você ganha, sem configurar mais nada:

- **containers** com nomes únicos — `projeto-dev-app-1`, `projeto-homol-pgsql-1`;
- **uma rede privada por ambiente** — o `app:9000` do nginx resolve dentro da rede certa;
- **volumes prefixados** — `projeto-dev_pgsql-data` e `projeto-homol_app-storage` não se misturam.

```bash
$ docker compose ps          # em /srv/projeto-dev
projeto-dev-app-1  projeto-dev-nginx-1  projeto-dev-pgsql-1  projeto-dev-redis-1 …
$ docker compose ps          # em /srv/projeto-homol
projeto-homol-app-1  projeto-homol-nginx-1 …
```

O que falta garantir é **um `.env` por ambiente** (com `APP_KEY`, `APP_URL` e
`COMPOSE_PROJECT_NAME` próprios) e **portas que não colidam** no host — ou nenhuma porta, que é o
que o Traefik permite.

## Traefik na frente

O cenário confirmado com o DevOps do servidor: existe um Traefik rodando, com docker provider
(ele lê **labels** dos containers), roteando por `Host(...)` e terminando o TLS no entrypoint
`websecure`. Cada ambiente precisa estar **na mesma rede Docker** que o Traefik — no exemplo,
`my-network`. O backend é a porta interna **80** do nginx; o `nginx.conf` do kit não muda.

Só o **`nginx`** de cada stack entra nessa rede. O resto (`app`, `pgsql`, `redis`, `queue`,
`scheduler`) continua na rede privada do ambiente, invisível de fora. Dois detalhes que o exemplo
do kit já traz e que costumam ser esquecidos:

- **`traefik.docker.network`** é obrigatória: o nginx fica em **duas** redes (a privada e a do
  Traefik), e sem esse label o Traefik pode escolher o IP da rede errada — o sintoma é um 504.
- **Router e service têm nome global** no Traefik: precisam ser únicos por ambiente. O exemplo
  usa o próprio `COMPOSE_PROJECT_NAME`, então a unicidade vem de graça com o nome do projeto.

## Ligar num servidor, passo a passo

O kit versiona um override de exemplo em `docker/traefik/docker-compose.override.yml`. O Compose
carrega um `docker-compose.override.yml` que esteja **na raiz do checkout** sozinho, sem `-f` —
então o comando de sempre, e o `./deploy_docker_local.sh`, passam a aplicar o encaixe sem mudar.

1. **Copie o exemplo** para a raiz do checkout daquele ambiente:

   ```bash
   cp docker/traefik/docker-compose.override.yml docker-compose.override.yml
   ```

   A cópia está no `.gitignore` do kit, como o `.env`: é configuração do servidor. (Projeto que
   nasceu de uma versão anterior do kit: acrescente a linha `/docker-compose.override.yml` ao
   seu `.gitignore` — o `kit:update` não entrega esse arquivo.)

2. **Preencha o `.env`** daquele ambiente — o bloco está pronto no fim do `.env.docker`:

   ```ini
   COMPOSE_PROJECT_NAME=projeto-dev        # um por ambiente
   APP_URL=https://dev.exemplo.br          # o hostname público
   TRUSTED_PROXIES=*                       # o Laravel passa a honrar o X-Forwarded-Proto do Traefik
   TRAEFIK_HOST=dev.exemplo.br             # obrigatória: o Compose recusa subir sem ela
   # TRAEFIK_REDE=my-network               # só se a rede do Traefik tiver outro nome
   ```

   `TRAEFIK_HOST` não tem default de propósito: sem ela o `docker compose` para com a mensagem
   *required variable TRAEFIK_HOST is missing* em vez de subir um router que não roteia nada.

3. **Suba e confira**:

   ```bash
   docker compose --profile app up -d --build
   docker compose --profile app config | grep -A8 'labels:'   # os labels com o nome do seu projeto
   ```

   Para desligar, apague o `docker-compose.override.yml` e rode o `up` de novo: o nginx sai da
   rede do Traefik e tudo volta ao que era.

### Por que `TRUSTED_PROXIES`

O Traefik termina o TLS e entrega **HTTP** na porta 80 do container. Sem confiar nos cabeçalhos
`X-Forwarded-*` que ele envia, o Laravel acha que está em `http://`: gera links absolutos errados,
não marca o cookie de sessão como `secure` e redireciona para endereços que o navegador não
alcança. `TRUSTED_PROXIES` diz em quem acreditar — uma lista de IPs/CIDRs separada por vírgula, ou
`*`. **Vazia ou ausente, nada muda**: é o comportamento que o kit sempre teve.

`*` é seguro quando a porta 80 do container só é alcançável pela rede do Traefik — que é o caso
atrás dele, sem porta publicada no host. Se você publicar a porta para fora (próxima seção),
prefira o CIDR da rede do Traefik.

## Portas: opcionais, ou só para você

Atrás do Traefik **nenhuma porta precisa ser publicada** no host. Mas o Compose soma as portas
do override às do arquivo base — o override não consegue *remover* a publicação da 8000. Então
"opcional" se resolve pela própria chave:

```ini
FORWARD_APP_PORT=127.0.0.1:8090   # publica só no loopback: útil para depurar na máquina, invisível de fora
```

Sem a chave, a porta continua a 8000 de sempre — e em três ambientes isso colide: o segundo `up`
para com *port is already allocated*. Uma matriz que funciona:

| Variável | dev | teste | homol |
|---|---|---|---|
| `FORWARD_APP_PORT` *(opcional¹)* | `127.0.0.1:8090` | `127.0.0.1:9090` | `127.0.0.1:8080` |
| `FORWARD_DB_PORT` *(opcional¹)* | `127.0.0.1:5433` | `127.0.0.1:5434` | `127.0.0.1:5435` |
| `FORWARD_REDIS_PORT` *(opcional¹)* | `127.0.0.1:6380` | `127.0.0.1:6381` | `127.0.0.1:6382` |
| `FORWARD_REVERB_PORT` *(só se fora do Traefik²)* | 8190 | 8191 | 8192 |

¹ Com o Traefik roteando por hostname, nenhuma destas precisa existir — mantenha só para
depuração ou administração, e no loopback.

² Se o Reverb **não** passar pelo Traefik, o default `FORWARD_REVERB_PORT=8090` colide com a
porta que a matriz reservou ao dev — daí os offsets.

## Reverb: duas rotas

O WebSocket pode seguir dois caminhos, e a escolha é do servidor, não do kit:

- **Pelo Traefik, no mesmo hostname** (recomendado): descomente o bloco do serviço `reverb` no
  override. Ele cria um segundo router, `{projeto}-reverb`, que casa `Host(...)` com
  `PathPrefix(/app)` e `PathPrefix(/apps)` — os dois caminhos que o Reverb serve — e aponta para a
  porta 8090 do container. No `.env`, `REVERB_PORT=443` e `REVERB_SCHEME=https` (e os `VITE_REVERB_*`
  iguais, se o seu front tiver Echo). Nenhuma porta extra exposta, TLS de graça.
- **Porta própria no host**: não descomente nada e use `FORWARD_REVERB_PORT` com o offset da matriz.

## Um checkout por ambiente

É o arranjo recomendado: cada ambiente é uma pasta, numa branch, com o próprio `.env` e a própria
cópia do override.

```text
/srv/projeto-dev     → branch develop,   .env com COMPOSE_PROJECT_NAME=projeto-dev
/srv/projeto-teste   → branch de testes, .env com COMPOSE_PROJECT_NAME=projeto-teste
/srv/projeto-homol   → branch release,   .env com COMPOSE_PROJECT_NAME=projeto-homol
```

Atualizar um ambiente é o fluxo de sempre, na pasta dele — o
`./deploy_docker_local.sh` faz `git pull`, rebuild, `up`,
migrations e health check, e lê a porta publicada pelo próprio Docker, então funciona com o bind
em `127.0.0.1`:

```bash
cd /srv/projeto-dev
./deploy_docker_local.sh            # ou: git pull && docker compose --profile app up -d --build
docker compose ps                   # só os containers deste ambiente
docker compose logs -f app
```

**Prós**: branches independentes, `.env` por pasta, zero alteração no compose, `restart:
unless-stopped` sobrevive ao reboot. **Contras**: três imagens em disco e três pares
Postgres/Redis em memória — aceitável para banco e cache, que são baratos; para o que é caro, veja
a Opção D.

## As outras opções, e por que não

- **Um checkout só, com `-p` e `--env-file`** — `docker compose -p projeto-dev --env-file .env.dev
  up`. Dois conflitos com o compose do kit: `env_file: .env` e o bind `./.env:/var/www/.env` são
  fixos, e `--env-file` muda só a interpolação, não os dois. Além disso um checkout é uma branch:
  os três ambientes rodariam o **mesmo código**. Descartada.
- **Um arquivo de override por ambiente** (`docker-compose.dev.yml`… com `-f`) — como tudo já é
  variável de `.env`, ganha-se um arquivo a mais por ambiente e nada sobre o arranjo acima. Descartada.
- **Infra compartilhada** (Opção D) — um ambiente hospeda o que é caro e os outros apontam para
  ele. Vale para o **llama.cpp** (~8 GB de RAM por instância) e para o **Mailpit**; não vale para
  Postgres e Redis, que são baratos e cujo isolamento total é o que torna um `migrate:fresh` do dev
  inofensivo para a homologação. A receita é no próprio override do ambiente que **não** hospeda,
  sobrepondo as chaves que o compose base fixa:

  ```yaml
  services:
    app:   { environment: { LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1' } }
    queue: { environment: { LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1' } }
  ```

  com os dois ambientes numa rede comum (a do Traefik serve) e `--profile ai` ligado só no que
  hospeda.

## Armadilhas

- **Cookies**: resolvidos pelo hostname — cada ambiente tem o seu, e cookie é por host. Nenhum
  `SESSION_COOKIE` customizado.
- **`APP_KEY` diferente por ambiente**: sessões e cookies criptografados ficam incompatíveis entre
  si, o que reforça o isolamento. `php artisan key:generate` em cada `.env`.
- **`APP_URL` por ambiente**, com `https://`; **`APP_DEBUG=false`** fora do dev.
- **`VITE_*` é assado no build**: o `npm run build` roda dentro da imagem, sem o `.env`. O
  Dockerfile do kit aceita `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` e
  `VITE_REVERB_APP_KEY` como argumentos de build, e o override de exemplo os repassa do `.env`.
  Vale para o projeto que tiver Echo no front; o JavaScript do kit, por padrão, não consome essas
  variáveis — os argumentos vazios produzem a mesma imagem de hoje.
- **Trocar `COMPOSE_PROJECT_NAME` depois de subir** cria volumes novos; os dados antigos ficam no
  volume do nome anterior.

## O que não muda

O `docker-compose.yml`, o `Dockerfile.laravel` (fora dos argumentos de build, vazios por padrão), o
`nginx.conf`, o `deploy_docker_local.sh` e os comandos documentados em
[Instalação avançada](../../comecar/instalacao-avancada/#os-containers-por-profile). Sem o override na
raiz e sem as chaves no `.env`, `docker compose config` é idêntico ao de antes desta página.
