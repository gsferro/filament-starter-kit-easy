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

`*` is safe when the container's port 80 is reachable only from Traefik's network — which is the
case behind it, with no port published on the host. If you publish the port outward (next
section), prefer the CIDR of Traefik's network.

## Ports: optional, or just for you

Behind Traefik **no port needs to be published** on the host. But Compose adds the override's
ports to the base file's — the override cannot *remove* the publication of 8000. So "optional" is
solved by the key itself:

```ini
FORWARD_APP_PORT=127.0.0.1:8090   # publishes on loopback only: handy to debug on the machine, invisible from outside
```

Without the key the port stays the usual 8000 — and across three environments that collides: the
second `up` stops with *port is already allocated*. A matrix that works:

| Variable | dev | test | staging |
|---|---|---|---|
| `FORWARD_APP_PORT` *(optional¹)* | `127.0.0.1:8090` | `127.0.0.1:9090` | `127.0.0.1:8080` |
| `FORWARD_DB_PORT` *(optional¹)* | `127.0.0.1:5433` | `127.0.0.1:5434` | `127.0.0.1:5435` |
| `FORWARD_REDIS_PORT` *(optional¹)* | `127.0.0.1:6380` | `127.0.0.1:6381` | `127.0.0.1:6382` |
| `FORWARD_REVERB_PORT` *(only outside Traefik²)* | 8190 | 8191 | 8192 |

¹ With Traefik routing by hostname none of these needs to exist — keep them only for debugging or
administration, and on loopback.

² If Reverb does **not** go through Traefik, the default `FORWARD_REVERB_PORT=8090` collides with
the port the matrix reserved for dev — hence the offsets.

## Reverb: two routes

The WebSocket can take two paths, and the choice belongs to the server, not to the kit:

- **Through Traefik, on the same hostname** (recommended): uncomment the `reverb` service block in
  the override. It creates a second router, `{project}-reverb`, matching `Host(...)` with
  `PathPrefix(/app)` and `PathPrefix(/apps)` — the two paths Reverb serves — and pointing at the
  container's port 8090. In the `.env`, `REVERB_PORT=443` and `REVERB_SCHEME=https` (and the same
  `VITE_REVERB_*` values if your front end has Echo). No extra port exposed, TLS for free.
- **Its own port on the host**: uncomment nothing and use `FORWARD_REVERB_PORT` with the matrix offset.

## One checkout per environment

This is the recommended layout: each environment is a folder, on a branch, with its own `.env`
and its own copy of the override.

```text
/srv/projeto-dev     → branch develop,  .env with COMPOSE_PROJECT_NAME=projeto-dev
/srv/projeto-teste   → test branch,     .env with COMPOSE_PROJECT_NAME=projeto-teste
/srv/projeto-homol   → release branch,  .env with COMPOSE_PROJECT_NAME=projeto-homol
```

Updating an environment is the usual flow, in its folder — `./deploy_docker_local.sh`
does `git pull`, rebuild, `up`, migrations and health check, and reads the port published by Docker
itself, so it works with the `127.0.0.1` bind:

```bash
cd /srv/projeto-dev
./deploy_docker_local.sh            # or: git pull && docker compose --profile app up -d --build
docker compose ps                   # only this environment's containers
docker compose logs -f app
```

**Pros**: independent branches, `.env` per folder, zero changes to the compose file, `restart:
unless-stopped` survives a reboot. **Cons**: three images on disk and three Postgres/Redis pairs in
memory — acceptable for database and cache, which are cheap; for what is expensive, see Option D.

## The other options, and why not

- **A single checkout with `-p` and `--env-file`** — `docker compose -p projeto-dev --env-file
  .env.dev up`. Two conflicts with the kit's compose file: `env_file: .env` and the bind
  `./.env:/var/www/.env` are fixed, and `--env-file` only changes interpolation, not those two.
  Besides, one checkout is one branch: the three environments would run the **same code**. Discarded.
- **One override file per environment** (`docker-compose.dev.yml`… with `-f`) — since everything is
  already an `.env` variable, you gain one more file per environment and nothing over the layout
  above. Discarded.
- **Shared infrastructure** (Option D) — one environment hosts what is expensive and the others
  point at it. Worth it for **llama.cpp** (~8 GB of RAM per instance) and for **Mailpit**; not for
  Postgres and Redis, which are cheap and whose full isolation is what makes a `migrate:fresh` in
  dev harmless for staging. The recipe goes in the override of the environment that does **not**
  host, overriding the keys the base compose file fixes:

  ```yaml
  services:
    app:   { environment: { LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1' } }
    queue: { environment: { LLAMACPP_URL: 'http://projeto-dev-llamacpp-1:8080/v1' } }
  ```

  with both environments on a common network (Traefik's will do) and `--profile ai` enabled only
  on the one that hosts.

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
