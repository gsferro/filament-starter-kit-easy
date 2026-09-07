---
paths:
  - 'app/Filament/Pages/Auth/**'
---

# Auth

## Página que usa o layout do Auth Designer precisa redeclarar $layout
A trait `HasAuthDesignerLayout` faz `static::$layout = ...` no `boot()`. Se a subclasse não declarar a própria `protected static string $layout`, a atribuição cai no estático herdado de `Filament\Pages\Page` e o layout de login passa a vestir TODA página Filament do processo — a página de 2FA do Breezy morre em `getAuthDesignerConfig does not exist`.

Ver `TelaBloqueio` (lock screen do marjose123, que é `SimplePage` e ignora o layout). Quem troca a classe do pacote pela nossa é o bind em `AppServiceProvider::register()`, porque a rota do pacote resolve `LockerScreen::class` pelo container.

Cubra sempre em par: um caso assertando `fi-auth-layout` na tela nova, e outro assertando que uma página comum do painel NÃO tem `fi-auth-layout` depois dela — ver `tests/Kit/BloqueioDeSessaoTest.php`.

## Guarda de laço em mount() por método sobrescrevível, não request()->routeIs()
Página de auth servida em duas rotas (a do painel e `/login`) decide "redireciono ou sirvo" em `mount()`. Não use `request()->routeIs('login')` nem `request()->route()`: em `Livewire::test()` e no `/livewire/update` a rota corrente não é a da página, a guarda devolve null e a página redireciona para si mesma (laço). O padrão é um método sobrescrevível — `TelaLogin::ehAPaginaUnica(): false`, `TelaLoginUnificada::ehAPaginaUnica(): true` — e o redirect dentro do `mount()` via `HttpResponseException(new RedirectResponse(...))`, que é o único que interrompe o Livewire. Ver `app/Filament/Pages/Auth/TelaLogin.php` e `TelaLoginUnificada.php` (v0.31.0).

## Página de auth fora do painel resolve o painel da CONTA antes de operação que consulta canAccessPanel()
Servida sob `panel:app`, a página empresta o painel default só para tema e layout — e operação do vendor que pergunta `canAccessPanel(Filament::getCurrentOrDefaultPanel())` decide sobre o painel ERRADO, em silêncio.

Medido na v0.32.0: `RequestPasswordReset::request()` só envia o e-mail quando `$user->canAccessPanel()` do painel corrente (`vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:request()`), então em `/esqueci-minha-senha` uma conta só de `/admin` recebia a notificação de tela "enviamos, se a conta existir" e **nenhum e-mail**. Falha silenciosa dos dois lados: a tela não distingue e nada vai para o log.

O padrão é `TelaRecuperarSenhaUnificada::request()`: resolve a conta pelo MESMO caminho do vendor (`Password::broker()->getUser()`), põe o painel corrente no primeiro painel que ela acessa (`DestinoAposLogin::paineisDe()`) e delega ao `parent::`, sem duplicar uma linha do método original. É a mesma decisão de `TelaLoginUnificada::isUserAllowedToAccessPanel()` (ADR-07 de `login-unificado`): fora dos painéis, a pergunta é "acessa **algum** painel", nunca "acessa o corrente".

Ao acrescentar página de auth fora do painel, varra o vendor pelo que ela chama à procura de `getCurrentOrDefaultPanel()` — o `Login::registerAction()` e o hint de senha do `Login` tinham o mesmo problema, e foram resolvidos apontando para a URL do kit.
