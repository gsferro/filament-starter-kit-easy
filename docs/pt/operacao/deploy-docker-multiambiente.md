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
- **Nome de serviço também é global na rede compartilhada.** O DNS do Docker resolve nome em
  **todas** as redes do container que pergunta: o `nginx` do kit, na `my-network`, resolve `app`
  também por lá, e se outro projeto do servidor tiver um container chamado `app` nessa rede, o
  `fastcgi_pass app:9000` pode cair no projeto errado; com o bloco do Reverb ligado, o `reverb`
  entra na rede e passa a resolver `pgsql` e `redis` do mesmo jeito. Confirme com quem administra o
  Traefik (DevOps) que a `my-network` não tem outro serviço com esses nomes — e só o `nginx` (e o
  `reverb`, se ligado) do kit entra nela, nunca o `app`.

É isto que o override de exemplo declara (com `projeto-dev` e `dev.exemplo.br` vindos do `.env`):

```yaml
nginx:
  networks:
    - default        # continua falando com app:9000
    - traefik        # a rede do Traefik (nome real em TRAEFIK_REDE)
  labels:
    - traefik.enable=true
    - traefik.docker.network=my-network
    - traefik.http.routers.projeto-dev.rule=Host(`dev.exemplo.br`)
    - traefik.http.routers.projeto-dev.entrypoints=websecure
    - traefik.http.routers.projeto-dev.tls=true
    - traefik.http.services.projeto-dev.loadbalancer.server.port=80
networks:
  traefik:
    external: true
    name: my-network
```

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

`*` confia em **quem quer que alcance a porta 80 do nginx**: com `FORWARD_APP_PORT` no loopback
(próxima seção) isso exclui a internet e o host, mas não os outros containers da `my-network` — a
rede é compartilhada, e um container vizinho que alcance `nginx:80` direto forja `X-Forwarded-For` e
`X-Forwarded-Proto` (o IP que o rate limit de login e a trilha de autenticação registram). O que
fecha isso é o **IP fixo do Traefik** (`ipv4_address` na rede, peça ao DevOps) em `TRUSTED_PROXIES`
no lugar de `*`; o CIDR da rede inteira não resolve, porque os vizinhos estão dentro dele. Se o `*`
ficar, fica como risco aceito entre projetos do mesmo servidor.

A chave é lida como qualquer outra do `.env`, pelo `config/kit.php`, e aplicada no boot do kit. Item
que não é IP nem CIDR, e coringa dentro de lista (`**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS`), são
descartados com um aviso no log — um erro de digitação em `TRUSTED_PROXIES` nunca derruba a aplicação
nem abre confiança a mais. Com a **configuração em cache** (`config:cache`) vale o valor que
`TRUSTED_PROXIES` tinha no `.env` na hora do cache: ao mudá-la, refaça o cache.

## Portas: distintas por ambiente, e no loopback se não forem para fora

Atrás do Traefik o tráfego do navegador não precisa de porta nenhuma no host. Mas o
`docker-compose.yml` do kit publica **sempre** as portas de `pgsql`, `redis`, `nginx` e `reverb`, e o
Compose soma as portas do override às do arquivo base — o override não consegue *remover* uma
publicação. Em três ambientes no mesmo servidor isso colide: o segundo `up` para com *port is
already allocated*. Então as quatro `FORWARD_*` são **obrigatórias e distintas** por ambiente, e o
que é "opcional" é expô-las para fora — o loopback no próprio valor as deixa só para administração:

```ini
FORWARD_APP_PORT=127.0.0.1:8090   # publica só no loopback: útil para depurar na máquina, invisível de fora
```

É também o que torna `TRUSTED_PROXIES=*` seguro: com a porta do nginx em `127.0.0.1`, só o Traefik
alcança o container. Uma matriz que funciona:

| Variável | dev | teste | homol |
|---|---|---|---|
| `FORWARD_APP_PORT` | 127.0.0.1:8090 | 127.0.0.1:9090 | 127.0.0.1:8080 |
| `FORWARD_DB_PORT` | 127.0.0.1:5433 | 127.0.0.1:5434 | 127.0.0.1:5435 |
| `FORWARD_REDIS_PORT` | 127.0.0.1:6380 | 127.0.0.1:6381 | 127.0.0.1:6382 |
| `FORWARD_REVERB_PORT` | 8190¹ | 8191¹ | 8192¹ |

¹ Com o Reverb pelo Traefik (próxima seção) ela também pode ir para `127.0.0.1:`; fora dele, é a
porta que o navegador usa e fica aberta. Em qualquer caso o default `FORWARD_REVERB_PORT=8090` colide
com a porta 8090 que a matriz reservou ao app do dev — daí os offsets.

O levantamento original marcava as três primeiras como "opcionais"; no kit elas não são, porque o
arquivo base as publica sempre. Deixar de definir uma delas é o erro que faz o segundo ambiente não
subir. O mesmo vale para os profiles que você ligar: `ai` publica `FORWARD_LLAMA_PORT` (default 8080
— colide com o app da homol na matriz) e `FORWARD_EMBED_PORT` (8081), `mail` publica
`FORWARD_MAILPIT_PORT` (1025) e `FORWARD_MAILPIT_DASHBOARD_PORT` (8025); no ambiente que os hospeda,
leve-as para o loopback com valores próprios (`FORWARD_LLAMA_PORT=127.0.0.1:8180`).

## Reverb: duas rotas

O WebSocket pode seguir dois caminhos, e a escolha é do servidor, não do kit:

- **Pelo Traefik, no mesmo hostname** (recomendado): descomente o bloco entre `# >>> reverb-traefik`
  e `# <<< reverb-traefik` no override. Ele cria um segundo router, `{projeto}-reverb`, que casa
  `Host(...)` com `PathPrefix(/app/{REVERB_APP_KEY})` e `PathPrefix(/apps/{REVERB_APP_ID})` — o
  WebSocket e a API que o Reverb serve — e aponta para a porta 8090 do container. O recorte pela
  chave e pelo id não é enfeite: `PathPrefix(/app)` sozinho roubaria o painel `/app` do kit, porque
  no Traefik a regra mais longa ganha prioridade. Nenhuma porta extra exposta, TLS de graça. No
  `.env` mudam **só** os `VITE_REVERB_*` — o que o navegador usa, se o seu front tiver Echo;
  `REVERB_HOST`, `REVERB_PORT` e `REVERB_SCHEME` **não** mudam: são internas (do PHP para o
  container do WebSocket), e o compose já aponta o `app` para a porta 8090 desse container em HTTP
  puro (um `REVERB_SCHEME=https` faria o broadcast falhar num TLS que o container não serve). Os
  valores do navegador variam por ambiente e são **assados no build** — mude-os e rode o `up` com
  `--build`:

  ```ini
  VITE_REVERB_HOST=dev.exemplo.br
  VITE_REVERB_PORT=443
  VITE_REVERB_SCHEME=https
  ```

  O que o bloco liga, já interpolado:

  ```yaml
  reverb:
    networks: [default, traefik]
    labels:
      - traefik.enable=true
      - traefik.docker.network=my-network
      - traefik.http.routers.projeto-dev-reverb.rule=Host(`dev.exemplo.br`) && (PathPrefix(`/app/starter-kit-key`) || PathPrefix(`/apps/starter-kit`))
      - traefik.http.routers.projeto-dev-reverb.entrypoints=websecure
      - traefik.http.routers.projeto-dev-reverb.tls=true
  ```

  mais o service do mesmo nome apontando a porta 8090 do container (a linha `loadbalancer` do
  exemplo).
- **Porta própria no host**: não descomente nada e use `FORWARD_REVERB_PORT` com o offset da matriz —
  é a porta a que o navegador se conecta, então fica aberta para fora:

  ```ini
  FORWARD_REVERB_PORT=8190
  ```

## Um checkout por ambiente (Opção A)

É o arranjo recomendado: cada ambiente é uma pasta, numa branch, com o próprio `.env` e a própria
cópia do override.

```text
/srv/projeto-dev     → branch develop,   .env com COMPOSE_PROJECT_NAME=projeto-dev
/srv/projeto-teste   → branch de testes, .env com COMPOSE_PROJECT_NAME=projeto-teste
/srv/projeto-homol   → branch release,   .env com COMPOSE_PROJECT_NAME=projeto-homol
```

Os três `.env`, no que difere entre eles (o resto — `APP_KEY` gerada em cada um, `TRUSTED_PROXIES=*`,
banco, cache — é igual em forma):

```ini
# /srv/projeto-dev/.env
COMPOSE_PROJECT_NAME=projeto-dev
TRAEFIK_HOST=dev.exemplo.br
APP_URL=https://dev.exemplo.br
FORWARD_APP_PORT=127.0.0.1:8090
FORWARD_DB_PORT=127.0.0.1:5433
FORWARD_REDIS_PORT=127.0.0.1:6380
FORWARD_REVERB_PORT=8190
```

```ini
# /srv/projeto-teste/.env
COMPOSE_PROJECT_NAME=projeto-teste
TRAEFIK_HOST=teste.exemplo.br
APP_URL=https://teste.exemplo.br
APP_DEBUG=false
FORWARD_APP_PORT=127.0.0.1:9090
FORWARD_DB_PORT=127.0.0.1:5434
FORWARD_REDIS_PORT=127.0.0.1:6381
FORWARD_REVERB_PORT=8191
```

```ini
# /srv/projeto-homol/.env
COMPOSE_PROJECT_NAME=projeto-homol
TRAEFIK_HOST=homol.exemplo.br
APP_URL=https://homol.exemplo.br
APP_DEBUG=false
FORWARD_APP_PORT=127.0.0.1:8080
FORWARD_DB_PORT=127.0.0.1:5435
FORWARD_REDIS_PORT=127.0.0.1:6382
FORWARD_REVERB_PORT=8192
```

Atualizar um ambiente é o fluxo de sempre, na pasta dele: `git pull` e rebuild, nesta ordem —
a imagem é self-contained, e rebuild antes do pull reassa o código velho.

```bash
cd /srv/projeto-dev
git pull
docker compose --profile app up -d --build
docker compose ps                   # só os containers deste ambiente
docker compose logs -f app
```

Ou tudo de uma vez com o `./deploy_docker_local.sh`, que faz o pull, o rebuild, o `up`, as migrations
e o health check — e lê a porta publicada pelo próprio Docker, então funciona com o bind em `127.0.0.1`.

**Prós**: branches independentes, `.env` por pasta, zero alteração no compose, `restart:
unless-stopped` sobrevive ao reboot. **Contras**: três imagens em disco e três pares
Postgres/Redis em memória — aceitável para banco e cache, que são baratos; para o que é caro, veja
a Opção D.

## As outras opções (B, C e D), e por que não

### Opção B — um checkout só, com `-p` e `--env-file`

`docker compose -p projeto-dev --env-file .env.dev up`. Dois conflitos com o compose do kit:
`env_file: .env` e o bind `./.env:/var/www/.env` são fixos, e `--env-file` muda só a interpolação,
não os dois. Além disso um checkout é uma branch: os três ambientes rodariam o **mesmo código**.
Descartada.

### Opção C — um arquivo de override por ambiente

`docker-compose.dev.yml`… combinados com `-f`. Como tudo já é variável de `.env`, ganha-se um
arquivo a mais por ambiente e nada sobre o arranjo acima. Descartada.

### Opção D — infra compartilhada

Um ambiente hospeda o que é caro e os outros apontam para ele. Vale para o **llama.cpp** (~8 GB de
RAM por instância) e para o **Mailpit**, que ficam compartilhados; não vale para `pgsql` e `redis`
(Postgres e Redis), que são baratos, ficam por ambiente, e cujo isolamento total é o que torna um
`migrate:fresh` do dev inofensivo para a homologação. A receita precisa de **uma rede própria** entre os ambientes (não a do Traefik: lá só
entra o `nginx`), que o `app` e o `queue` de quem consome e o `llamacpp` de quem hospeda passam a
compartilhar — tudo no override de cada lado, sobrepondo as chaves que o compose base fixa:

```yaml
# override de quem CONSOME (teste, homol): docker network create ia-compartilhada, uma vez
services:
  app:
    networks: [default, ia]
    environment:
      LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1'
      LLAMACPP_EMBED_URL: 'http://projeto-dev-llamacpp-embeddings-1:8080/v1'
  queue:
    networks: [default, ia]
    environment:
      LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1'
      LLAMACPP_EMBED_URL: 'http://projeto-dev-llamacpp-embeddings-1:8080/v1'
networks:
  ia:
    external: true
    name: ia-compartilhada
```

```yaml
# override de quem HOSPEDA (dev): sobe com --profile ai, e publica o llama só no loopback
# (FORWARD_LLAMA_PORT=127.0.0.1:8180: o default 8080 colide com o app da homol na matriz)
services:
  llamacpp:
    networks: [default, ia]
  llamacpp-embeddings:
    networks: [default, ia]
networks:
  ia:
    external: true
    name: ia-compartilhada
```

Vale o mesmo aviso de nome global: na `ia-compartilhada` só entram esses containers, com os
nomes completos (`projeto-dev-llamacpp-1`), nunca `app`.

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
