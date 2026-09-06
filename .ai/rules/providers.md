---
paths:
  - 'app/Providers/**'
---

# Providers

## Rota do kit nasce no KitServiceProvider com `web` explícito, não em routes/web.php
`routes/web.php` pertence ao projeto e NÃO está em `KitUpdate::CAMINHOS_DO_KIT`: rota escrita lá nunca chega a quem roda `kit:update`. Rota que o kit entrega (ex.: `/login`, `/login/painel`, `/login/painel/{painel}`) é registrada em `KitServiceProvider::boot()` via `Route::middleware(['web', 'panel:app'])->group(...)`. O `web` precisa ser explícito porque rota registrada em provider não herda o grupo do `routes/web.php` — sem ele não há sessão nem CSRF, e o `auth` falha em silêncio. Sempre registrar (mesmo com a feature desligada) e gatear pelo config dentro da página/controller, para a URL não virar 404 quando alguém liga o toggle no Settings. ADR-05 em `wikis/specs/feat/login-unificado/`.
