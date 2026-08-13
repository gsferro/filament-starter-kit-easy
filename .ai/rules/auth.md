---
paths:
  - 'app/Filament/Pages/Auth/**'
---

# Auth

## Página que usa o layout do Auth Designer precisa redeclarar $layout
A trait `HasAuthDesignerLayout` faz `static::$layout = ...` no `boot()`. Se a subclasse não declarar a própria `protected static string $layout`, a atribuição cai no estático herdado de `Filament\Pages\Page` e o layout de login passa a vestir TODA página Filament do processo — a página de 2FA do Breezy morre em `getAuthDesignerConfig does not exist`.

Ver `TelaBloqueio` (lock screen do marjose123, que é `SimplePage` e ignora o layout). Quem troca a classe do pacote pela nossa é o bind em `AppServiceProvider::register()`, porque a rota do pacote resolve `LockerScreen::class` pelo container.

Cubra sempre em par: um caso assertando `fi-auth-layout` na tela nova, e outro assertando que uma página comum do painel NÃO tem `fi-auth-layout` depois dela — ver `tests/Kit/BloqueioDeSessaoTest.php`.
