---
title: "Local domain"
description: "Reaching the project at http://my-project.test instead of http://127.0.0.1:8000 costs one line in your machine's hosts file and two keys in your .env. No…"
sidebar:
  order: 3
---
## Why it pays off

Reaching the project at `http://my-project.test` instead of `http://127.0.0.1:8000` costs one line
in your machine's `hosts` file and two keys in your `.env`. No versioned file changes, and anyone
who does nothing stays on `http://localhost:8000` without noticing a difference. Adoption is
individual: each teammate decides, and the published server is never touched.

The gain is more than cosmetic. Session cookies, absolute links stored in the database and social
login callbacks start using a stable name rather than an IP with a port — the same shape as the
real environment.

## Recipe, in three steps

### 1. The hosts file

Once per machine, and it **requires a terminal running as administrator**. On Windows the path is
**not** `/etc/hosts`:

```text
C:\Windows\System32\drivers\etc\hosts      # Windows
/etc/hosts                                 # Linux and macOS

127.0.0.1    my-project.test
```

The `hosts` file lives on disk: the line lasts forever, not only for the open session. From an
**elevated** PowerShell:

```powershell
Add-Content "$env:windir\System32\drivers\etc\hosts" "`n127.0.0.1`tmy-project.test" -Encoding ascii
ipconfig /flushdns
```

The `-Encoding ascii` flag is a precaution: PowerShell 7 writes UTF-8 by default, and the Windows
resolver expects plain ASCII in that file.

If the machine runs Laravel Herd or Valet, run `ping my-project.test` **before** editing `hosts`:
both already resolve `*.test` to `127.0.0.1` on their own, and the manual line may be unnecessary.

### 2. Your .env

The `.env` file is never versioned, so these keys stay on your machine alone:

```dotenv
APP_URL=http://my-project.test
FORWARD_APP_PORT=80          # `app` profile only; without it the port stays 8000
COMPOSE_PROJECT_NAME=my-project
```

`FORWARD_APP_PORT` is what drops the `:8000` from the address: `docker-compose.yml` already
publishes the port as `${FORWARD_APP_PORT:-8000}:80`, so setting the key is enough. The default
stays 8000 for everyone who leaves it alone.

### 3. Bring it up and clear the config cache

```bash
docker compose --profile app up -d --build
docker compose --profile app exec app php artisan config:clear
```

Running PHP on the host instead of the container? Use `php artisan serve --host=0.0.0.0 --port=80`
— or skip `FORWARD_APP_PORT` and browse `http://my-project.test:8000`.

Checking, with no elevation needed:

```powershell
Get-Content "$env:windir\System32\drivers\etc\hosts" | Select-String my-project
ping my-project.test         # should answer 127.0.0.1
```

## Why it works without touching anything versioned

| Piece | Reason |
|---|---|
| `docker/nginx/nginx.conf` | `server_name _` is a catch-all: nginx already serves any `Host` |
| `bootstrap/app.php` | with no `trustHosts()`, Laravel never rejects an unknown host |
| `config/session.php` | `SESSION_DOMAIN=null` binds the cookie to the current host by itself |
| multi-tenancy | path-based (`/app/{tenant}`), never subdomain-based — no wildcard DNS |
| `docker-compose.yml` | the published port already reads `${FORWARD_APP_PORT:-8000}:80` |

The `name:` key in `docker-compose.yml` is the kit's **floor** and should never be edited: a test
case guards it. The per-project name travels in the `.env`, through `COMPOSE_PROJECT_NAME`, and
that is the key `kit:install` writes.

## Traps

### Elevation: flushdns misleads you

If `Add-Content` answers `Access to the path ... is denied`, the terminal is not elevated — the
file's ACL grants write access only to the local Administrators group, with the SYSTEM account as
owner. Here is the trap: `ipconfig /flushdns` runs **without** elevation and reports success
either way, so its success proves nothing about your session being elevated.

Open Windows Terminal with *Run as administrator*, or elevate the single command:

```powershell
Start-Process pwsh -Verb RunAs -ArgumentList '-NoProfile','-Command',
  'Add-Content "$env:windir\System32\drivers\etc\hosts" "`n127.0.0.1`tmy-project.test" -Encoding ascii'
```

Should the error survive elevation, the next suspect is Controlled Folder Access in Defender, or a
corporate antivirus guarding the file.

### Suffix .test, never .local

Use `.test`, reserved by RFC 6761 for exactly this purpose. The `.local` suffix is reserved by RFC
6762 for mDNS (Bonjour/Avahi): it does work on Windows, because `hosts` is consulted first, yet it
shares space with printer discovery and AirPlay, and is a known source of resolution latency.

### Social login and Vite

Two points deserve attention once `APP_URL` changes:

- **Social login**: providers register `APP_URL + /auth/{provider}/callback`. The new URI has to be
  registered in the provider's console, or the callback fails.
- **`npm run dev`**: modules are served from `localhost:5173` while the page sits on the local
  domain, and Vite restricts CORS by default. With `npm run build` — what the `app` profile uses —
  the issue never shows up.

## Undoing it

Delete the line from `hosts` and the keys from `.env`. Nothing else needs cleaning: no file in the
repository was ever touched, and the address goes back to `http://localhost:8000`.
