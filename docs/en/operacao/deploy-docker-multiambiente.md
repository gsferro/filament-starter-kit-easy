---
title: "Several environments on one server"
description: "Three non-production environments of the same project — dev, test, staging — on the same server, each with its own containers, database and volumes, and a…"
sidebar:
  order: 6
---
## What this page solves

Three non-production environments of the same project — dev, test, staging — on the **same
server**, each with its own containers, database and volumes, and a **Traefik** in front routing
by hostname with TLS. Everything here is **optional**: whoever keeps bringing up one stack with
`docker compose --profile app up -d --build` is not affected by anything on this page, and the
kit's `docker-compose.yml` does not change a single line.

## The central mechanism: one project name per environment

Compose isolates by **project name**. The kit was already designed for that: no service declares
a `container_name`, and the prefix of every container, network and volume comes from
`COMPOSE_PROJECT_NAME` in the `.env` — `kit:install` writes your project's name there ([the name
of the containers](../../comecar/instalacao-avancada/#container-names)). Give each
environment a distinct name and you get, with no further configuration:

- **containers** with unique names — `projeto-dev-app-1`, `projeto-homol-pgsql-1`;
- **one private network per environment** — the nginx `app:9000` resolves inside the right network;
- **prefixed volumes** — `projeto-dev_pgsql-data` and `projeto-homol_app-storage` never mix.

```bash
$ docker compose ps          # in /srv/projeto-dev
projeto-dev-app-1  projeto-dev-nginx-1  projeto-dev-pgsql-1  projeto-dev-redis-1 …
$ docker compose ps          # in /srv/projeto-homol
projeto-homol-app-1  projeto-homol-nginx-1 …
```

What is left to guarantee is **one `.env` per environment** (with its own `APP_KEY`, `APP_URL`
and `COMPOSE_PROJECT_NAME`) and **ports that do not collide** on the host — or no ports at all,
which is what Traefik allows.

## Traefik in front

The scenario confirmed with the server's DevOps: a Traefik is already running, with the docker
provider (it reads container **labels**), routing by `Host(...)` and terminating TLS on the
`websecure` entrypoint. Each environment must be **on the same Docker network** as Traefik — in
the example, `my-network`. The backend is the nginx internal port **80**; the kit's `nginx.conf`
does not change.

Only the **`nginx`** of each stack joins that network. The rest (`app`, `pgsql`, `redis`, `queue`,
`scheduler`) stays on the environment's private network, invisible from outside. Two details the
kit's example already carries and that are usually forgotten:

- **`traefik.docker.network`** is mandatory: nginx sits on **two** networks (the private one and
  Traefik's), and without that label Traefik may pick the IP on the wrong network — the symptom
  is a 504.
- **Router and service names are global** in Traefik: they must be unique per environment. The
  example uses the `COMPOSE_PROJECT_NAME` itself, so uniqueness comes for free with the project name.
- **Service names are global on the shared network too.** Docker's DNS resolves `app` for nginx
  across **every** network it is attached to; if another project on the server has a container
  named `app` or `reverb` on the same `my-network`, the kit's `fastcgi_pass app:9000` may land on
  the wrong project. Confirm with whoever runs Traefik that the network has no other service with
  those names — and only the kit's `nginx` joins it, never `app`.

This is what the example override declares (with `projeto-dev` and `dev.example.org` coming from the `.env`):

```yaml
nginx:
  networks:
    - default        # keeps talking to app:9000
    - traefik        # Traefik's network (real name in TRAEFIK_REDE)
  labels:
    - traefik.enable=true
    - traefik.docker.network=my-network
    - traefik.http.routers.projeto-dev.rule=Host(`dev.example.org`)
    - traefik.http.routers.projeto-dev.entrypoints=websecure
    - traefik.http.routers.projeto-dev.tls=true
    - traefik.http.services.projeto-dev.loadbalancer.server.port=80
networks:
  traefik:
    external: true
    name: my-network
```

## Enabling it on a server, step by step

The kit ships an example override at `docker/traefik/docker-compose.override.yml`. Compose loads a
`docker-compose.override.yml` sitting **at the checkout root** on its own, with no `-f` — so the
usual command, and `./deploy_docker_local.sh`, start applying the wiring without any change.

1. **Copy the example** to the root of that environment's checkout:

   ```bash
   cp docker/traefik/docker-compose.override.yml docker-compose.override.yml
   ```

   The copy is in the kit's `.gitignore`, like `.env`: it is server configuration. (Project born
   from an earlier kit version: add the line `/docker-compose.override.yml` to your `.gitignore` —
   `kit:update` does not deliver that file.)

2. **Fill in that environment's `.env`** — the block is ready at the end of `.env.docker`:

   ```ini
   COMPOSE_PROJECT_NAME=projeto-dev        # one per environment
   APP_URL=https://dev.example.org         # the public hostname
   TRUSTED_PROXIES=*                       # Laravel starts honouring Traefik's X-Forwarded-Proto
   TRAEFIK_HOST=dev.example.org            # mandatory: Compose refuses to start without it
   # TRAEFIK_REDE=my-network               # only if Traefik's network has another name
   ```

   `TRAEFIK_HOST` has no default on purpose: without it `docker compose` stops with *required
   variable TRAEFIK_HOST is missing* instead of bringing up a router that routes nothing.

3. **Bring it up and check**:

   ```bash
   docker compose --profile app up -d --build
   docker compose --profile app config | grep -A8 'labels:'   # the labels, with your project's name
   ```

   To turn it off, delete `docker-compose.override.yml` and run `up` again: nginx leaves Traefik's
   network and everything returns to what it was.

### Why `TRUSTED_PROXIES`

Traefik terminates TLS and delivers **HTTP** on the container's port 80. Without trusting the
`X-Forwarded-*` headers it sends, Laravel believes it is on `http://`: it generates wrong absolute
links, does not mark the session cookie as `secure` and redirects to addresses the browser cannot
reach. `TRUSTED_PROXIES` says whom to believe — a comma-separated list of IPs/CIDRs, or `*`.
**Empty or absent, nothing changes**: that is the behaviour the kit always had.

`*` is safe when the nginx port is reachable only from Traefik's network — with `FORWARD_APP_PORT`
on loopback (next section). If the port goes outward, replace `*` with the CIDR of Traefik's
network: whoever reaches the container without going through it could forge `X-Forwarded-*`.

The key is read at bootstrap, before `config/`. With the **configuration cached** (`config:cache`)
Laravel does not load the `.env`, so the key must be in the **process environment** — in the `app`
profile the Compose `env_file` already does that; outside Docker, set it on the service that starts
PHP (systemd, php-fpm pool) or do not use the configuration cache.

## Ports: distinct per environment, and on loopback unless they must go out

Behind Traefik the browser traffic needs no port on the host at all. But the kit's
`docker-compose.yml` **always** publishes the ports of `pgsql`, `redis`, `nginx` and `reverb`, and
Compose adds the override's ports to the base file's — the override cannot *remove* a publication.
Across three environments on the same server that collides: the second `up` stops with *port is
already allocated*. So the four `FORWARD_*` keys are **mandatory and distinct** per environment, and
what is "optional" is exposing them outward — loopback in the value itself keeps them for
administration only:

```ini
FORWARD_APP_PORT=127.0.0.1:8090   # publishes on loopback only: handy to debug on the machine, invisible from outside
```

It is also what makes `TRUSTED_PROXIES=*` safe: with the nginx port on `127.0.0.1`, only Traefik
reaches the container. A matrix that works:

| Variable | dev | test | staging |
|---|---|---|---|
| `FORWARD_APP_PORT` | `127.0.0.1:8090` | `127.0.0.1:9090` | `127.0.0.1:8080` |
| `FORWARD_DB_PORT` | `127.0.0.1:5433` | `127.0.0.1:5434` | `127.0.0.1:5435` |
| `FORWARD_REDIS_PORT` | `127.0.0.1:6380` | `127.0.0.1:6381` | `127.0.0.1:6382` |
| `FORWARD_REVERB_PORT` | 8190¹ | 8191¹ | 8192¹ |

¹ With Reverb through Traefik (next section) it can also go to `127.0.0.1:`; outside it, it is the
port the browser uses and stays open. Either way the default `FORWARD_REVERB_PORT=8090` collides with
the port 8090 the matrix reserved for dev's app — hence the offsets.

The original survey marked the first three as "optional"; in the kit they are not, because the base
file always publishes them. Leaving one undefined is the mistake that keeps the second environment
from starting.

## Reverb: two routes

The WebSocket can take two paths, and the choice belongs to the server, not to the kit:

- **Through Traefik, on the same hostname** (recommended): uncomment the block between
  `# >>> reverb-traefik` and `# <<< reverb-traefik` in the override. It creates a second router,
  `{project}-reverb`, matching `Host(...)` with `PathPrefix(/app/{REVERB_APP_KEY})` and
  `PathPrefix(/apps/{REVERB_APP_ID})` — the WebSocket and the API Reverb serves — and pointing at
  the container's port 8090. Narrowing by key and id is not decoration: `PathPrefix(/app)` alone
  would steal the kit's `/app` panel, because in Traefik the longest rule wins. In the `.env`, `REVERB_PORT=443` and `REVERB_SCHEME=https` (and the same
  `VITE_REVERB_*` values if your front end has Echo). No extra port exposed, TLS for free. The values
  the browser uses vary per environment and are **baked at build time** — change them and run `up`
  with `--build`:

  ```ini
  VITE_REVERB_HOST=dev.example.org
  VITE_REVERB_PORT=443
  VITE_REVERB_SCHEME=https
  ```

  What the block enables, already interpolated:

  ```yaml
  reverb:
    networks: [default, traefik]
    labels:
      - traefik.enable=true
      - traefik.docker.network=my-network
      - traefik.http.routers.projeto-dev-reverb.rule=Host(`dev.example.org`) && (PathPrefix(`/app/starter-kit-key`) || PathPrefix(`/apps/starter-kit`))
      - traefik.http.routers.projeto-dev-reverb.entrypoints=websecure
      - traefik.http.routers.projeto-dev-reverb.tls=true
      - traefik.http.services.projeto-dev-reverb.loadbalancer.server.port=8090
  ```
- **Its own port on the host**: uncomment nothing and use `FORWARD_REVERB_PORT` with the matrix
  offset — it is the port the browser connects to, so it stays open outward:

  ```ini
  FORWARD_REVERB_PORT=8190
  ```

## One checkout per environment (Option A)

This is the recommended layout: each environment is a folder, on a branch, with its own `.env`
and its own copy of the override.

```text
/srv/projeto-dev     → branch develop,  .env with COMPOSE_PROJECT_NAME=projeto-dev
/srv/projeto-teste   → test branch,     .env with COMPOSE_PROJECT_NAME=projeto-teste
/srv/projeto-homol   → release branch,  .env with COMPOSE_PROJECT_NAME=projeto-homol
```

The three `.env` files, in what differs between them (the rest — an `APP_KEY` generated in each,
`TRUSTED_PROXIES=*`, database, cache — has the same shape):

```ini
# /srv/projeto-dev/.env
COMPOSE_PROJECT_NAME=projeto-dev
TRAEFIK_HOST=dev.example.org
APP_URL=https://dev.example.org
FORWARD_APP_PORT=127.0.0.1:8090
FORWARD_DB_PORT=127.0.0.1:5433
FORWARD_REDIS_PORT=127.0.0.1:6380
FORWARD_REVERB_PORT=8190
```

```ini
# /srv/projeto-teste/.env
COMPOSE_PROJECT_NAME=projeto-teste
TRAEFIK_HOST=test.example.org
APP_URL=https://test.example.org
APP_DEBUG=false
FORWARD_APP_PORT=127.0.0.1:9090
FORWARD_DB_PORT=127.0.0.1:5434
FORWARD_REDIS_PORT=127.0.0.1:6381
FORWARD_REVERB_PORT=8191
```

```ini
# /srv/projeto-homol/.env
COMPOSE_PROJECT_NAME=projeto-homol
TRAEFIK_HOST=staging.example.org
APP_URL=https://staging.example.org
APP_DEBUG=false
FORWARD_APP_PORT=127.0.0.1:8080
FORWARD_DB_PORT=127.0.0.1:5435
FORWARD_REDIS_PORT=127.0.0.1:6382
FORWARD_REVERB_PORT=8192
```

Updating an environment is the usual flow, in its folder: `git pull` then rebuild, in that order —
the image is self-contained, and rebuilding before the pull bakes the old code again.

```bash
cd /srv/projeto-dev
git pull
docker compose --profile app up -d --build
docker compose ps                   # only this environment's containers
docker compose logs -f app
```

Or all at once with `./deploy_docker_local.sh`, which does the pull, the rebuild, the `up`, the
migrations and the health check — and reads the port published by Docker itself, so it works with
the `127.0.0.1` bind.

**Pros**: independent branches, `.env` per folder, zero changes to the compose file, `restart:
unless-stopped` survives a reboot. **Cons**: three images on disk and three Postgres/Redis pairs in
memory — acceptable for database and cache, which are cheap; for what is expensive, see Option D.

## The other options (B, C and D), and why not

### Option B — a single checkout with `-p` and `--env-file`

`docker compose -p projeto-dev --env-file .env.dev up`. Two conflicts with the kit's compose file:
`env_file: .env` and the bind `./.env:/var/www/.env` are fixed, and `--env-file` only changes
interpolation, not those two. Besides, one checkout is one branch: the three environments would run
the **same code**. Discarded.

### Option C — one override file per environment

`docker-compose.dev.yml`… combined with `-f`. Since everything is already an `.env` variable, you
gain one more file per environment and nothing over the layout above. Discarded.

### Option D — shared infrastructure

One environment hosts what is expensive and the others point at it. Worth it for **llama.cpp**
(~8 GB of RAM per instance) and for **Mailpit**, which stay shared; not for `pgsql` and `redis`
(Postgres and Redis), which are cheap, stay per environment, and whose full isolation is what makes a
`migrate:fresh` in dev harmless for staging. The
recipe goes in the override of the environment that does **not** host, overriding the keys the base
compose file fixes:

```yaml
services:
  app:
    environment:
      LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1'
  queue:
    environment:
      LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1'
```

with both environments on a common network (Traefik's will do) and `--profile ai` enabled only on
the one that hosts.

## Pitfalls

- **Cookies**: solved by the hostname — each environment has its own, and cookies are per host. No
  custom `SESSION_COOKIE`.
- **A different `APP_KEY` per environment**: encrypted sessions and cookies become incompatible
  with each other, which reinforces the isolation. `php artisan key:generate` in each `.env`.
- **`APP_URL` per environment**, with `https://`; **`APP_DEBUG=false`** outside dev.
- **`VITE_*` is baked at build time**: `npm run build` runs inside the image, without the `.env`.
  The kit's Dockerfile accepts `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` and
  `VITE_REVERB_APP_KEY` as build arguments, and the example override passes them from the `.env`.
  This matters for a project with Echo on the front end; the kit's JavaScript does not consume
  those variables by default — empty arguments produce the same image as today.
- **Changing `COMPOSE_PROJECT_NAME` after the first `up`** creates new volumes; the old data stays
  in the volume under the previous name.

## What does not change

The `docker-compose.yml`, the `Dockerfile.laravel` (apart from the build arguments, empty by
default), the `nginx.conf`, the `deploy_docker_local.sh` and the commands documented in
[Advanced installation](../../comecar/instalacao-avancada/#containers-by-profile). Without the override
at the root and without the keys in the `.env`, `docker compose config` is identical to what it
was before this page.
