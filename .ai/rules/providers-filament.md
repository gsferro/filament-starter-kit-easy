---
paths:
  - 'app/Providers/Filament/**'
---

# Providers Filament

## Plugin que resolve o painel corrente precisa ser registrado nos TRÊS painéis
Alguns pacotes resolvem o próprio plugin pelo painel CORRENTE, com o helper `filament()` / `Plugin::get()`. Registrá-los em um painel só derruba a aplicação inteira — não a tela, a aplicação.

Casos já medidos no kit:

- `marjose123/filament-lockscreen` — o `routes/web.php` do pacote resolve no boot; painel sem o plugin estoura em todo request, e até `package:discover` morre.
- `bezhansalleh/filament-exceptions` — o `ExceptionResource` chama `FilamentExceptionsPlugin::get()` nos métodos estáticos de navegação, e o `filament-shield` percorre `Filament::getPanels()` no boot **sem fixar o painel corrente**. A resolução cai no painel default (`app`) e estoura `LogicException: Plugin [filament-exceptions] is not registered for panel [app]` em TODO comando artisan — `migrate` e `inspire` inclusive.

Saída: registrar nos três painéis, e desligar a navegação onde a tela não deve aparecer (`->registerNavigation(false)`).

**Consequência obrigatória, não opcional**: o resource passa a existir na matriz de permissões daqueles painéis. Se um deles for o `/app`, a entidade PRECISA entrar na lista de subtração do `panel_user` em `PapeisSeeder::permissoesDeAdministracaoDoApp()`. Sem isso, todo usuário comum herda as permissions — no caso das exceções, leitura de stack trace da instalação inteira. Ver `.ai/rules/filament.md` §4.

Sintoma de que você caiu nisto: um `LogicException` de plugin não registrado num painel onde você nunca quis a tela.
