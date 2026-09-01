---
title: The / route is public
parent: Authentication
grand_parent: English
nav_order: 6
---

# The `/` route is public and shows no secrets

[![Welcome page on the / route: three cards for the /app, /admin and /infra panels, plus two sections with what kit:install customised](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/boas-vindas.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/boas-vindas.png)

Instead of Laravel's `welcome.blade.php`, the root serves `App\Filament\Pages\BoasVindas`: one
card per panel (`/app`, `/admin`, `/infra`) and an infolist with what the installation
customised — name, colour, tenancy, retention windows, kit version.

It is **anonymous**, like the page it replaces, which is why the list of what it does **not**
show matters: the admin's e-mail, name and password, the database host and user, the repository
URL, `app.env`, `app.debug`, `app.url` and the mail configuration. A test plants a sentinel in
each of those values and asserts it is absent from the HTML — alongside an `assertOk()`, because
otherwise a 500 would pass every line by accident.

The "show everything outside production" alternative was deliberately rejected: security that
depends on `APP_ENV` being right is not security.

The route carries the `panel:app` middleware, and that is not decoration — it is the alias for
`SetUpPanel`, which boots the panel and therefore brings in Filament's stylesheet, the project
palette and the theme switcher. Measured: `@filamentStyles` alone does not bring the stylesheet
and the page renders amber even with `KIT_COR_PRIMARIA=Violet`. The middleware authenticates
nobody.

```php
// routes/web.php
Route::get('/', BoasVindas::class)->middleware('panel:app')->name('boas-vindas');
```

