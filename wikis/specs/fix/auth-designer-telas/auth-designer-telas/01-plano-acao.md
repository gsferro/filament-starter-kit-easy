# Plano de Ação — Auth Designer nas telas que ficaram de fora

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/main/identidade-visual-da-organizacao/` (é ela que introduziu
  `TelaBloqueio` e a manobra de vestir tela de terceiro com o layout do Auth Designer) e
  `wikis/specs/main/convite-para-usuario-existente/` (que criou `RegistroPorConvite`).
- **Motivo**: três telas de autenticação ficaram fora do guarda-roupa do Auth Designer — o
  desafio de 2FA do Breezy nunca foi vestido, o `register` foi vestido com a mídia no MESMO
  lado do login (em vez do inverso), e a confirmação de e-mail não tem configuração nenhuma.
- **Toca infra compartilhada?**: **sim** — `$layout` é propriedade **estática** e o mecanismo do
  Auth Designer atribui `static::$layout` no `boot()`. Errar isso não quebra a tela nova: quebra
  **toda** página Filament do processo. Regressão obrigatória contra
  `tests/Kit/BloqueioDeSessaoTest.php` e `tests/Kit/TelasDeAutenticacaoTest.php`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | 2FA com Auth Designer | 1, 2 | classe nova + ligação nos três painéis |
| RQ-02 | `register` com a mídia no lado inverso ao do login | 3 | uma linha |
| RQ-03 | "inverso" = o mesmo eixo do esqueci-a-senha (`MediaPosition::Right`) | 3 | confirmado em `AppPanelProvider.php:236` |
| RQ-04 | confirmação de e-mail vestida | 4 | nos três painéis — a **configuração** do Auth Designer, não a rota (ADR-03) |
| RQ-05 | e sem passar a ser usada como default | 4 | ação da rota apagada com `null` **e** `isRequired: false` |

## Objetivo

Vestir com o layout do `caresome/filament-auth-designer` as três telas de autenticação que
ficaram cruas, e corrigir o eixo da mídia no `register`. Nenhuma regra de negócio muda: a
entrega é toda de apresentação e de configuração de painel.

O risco real não está no que se acrescenta, está no **como**. A trait
`HasAuthDesignerLayout::boot()` faz `static::$layout = '…'`
(`vendor/caresome/filament-auth-designer/src/Concerns/HasAuthDesignerLayout.php:14`), e
propriedade estática sem redeclaração na subclasse escreve no estático do ancestral. A tela
que morre nesse cenário é justamente a de 2FA — que é a tela que este requisito manda vestir.
`.ai/rules/auth.md` já registra o defeito; este plano o trata como o item nº 1.

## Contexto

Estado medido antes da mudança:

| Tela | Auth Designer | Painéis | Eixo da mídia |
|---|---|---|---|
| Login | sim | app, admin, infra | `MediaPosition::Left` (`AppPanelProvider.php:192`, `AdminPanelProvider.php:115`, `InfraPanelProvider.php:149`) |
| Register | sim | só `app` | `MediaPosition::Left` (`AppPanelProvider.php:215`) — **igual** ao login |
| PasswordReset (pedido + redefinição) | sim | app, admin, infra | `MediaPosition::Right` (`AppPanelProvider.php:236`) |
| EmailVerification | **não** | nenhum | — (nem a rota existe) |
| 2FA (desafio pós-login) | **não** | — | vem do Breezy como `SimplePage` |
| Profile | não (é o `myProfile` do Breezy) | — | fora de escopo |

## Análise dos Arquivos Existentes

### `vendor/jeffgreco13/filament-breezy/src/Pages/TwoFactorPage.php`

`class TwoFactorPage extends SimplePage implements HasForms` (`:23`), com
`protected string $view = 'filament-breezy::filament.pages.two-factor'` (`:29`). A view é
`<x-filament-panels::page.simple>` — o **mesmo** componente que a página de login do Filament
usa —, então trocar só o layout basta: o conteúdo já é um "cartão de auth".

O layout vem de `SimplePage`, que declara
`protected static string $layout = 'filament-panels::components.layout.simple'`
(`vendor/filament/filament/src/Pages/SimplePage.php:12`). É por isso que a tela sai com
`fi-simple-layout` hoje.

Quem escolhe a classe da rota é o plugin, não o container:
`Route::get('/two-factor-authentication', $plugin->getTwoFactorRouteAction())`
(`vendor/jeffgreco13/filament-breezy/routes/web.php:24`), e a classe vem de
`enableTwoFactorAuthentication(… string|Closure|array|null $action = TwoFactorPage::class …)`
(`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:29,33`).
Ou seja: **não é preciso bind no container** como em `TelaBloqueio` — o parâmetro `action:` é o
ponto de extensão oficial do pacote. Menos manobra que o precedente.

### `app/Filament/Pages/Auth/TelaBloqueio.php`

O precedente da manobra: `use HasAuthDesignerLayout`, `$layout` redeclarado (`:50`),
`getAuthDesignerPageKey(): 'login'` (`:52-55`). A tela nova de 2FA é o mesmo caso, sem a parte
de logo de organização — o desafio de 2FA acontece **antes** de haver organização corrente.

### `app/Providers/Filament/AppPanelProvider.php`

`AuthDesignerPlugin` em `:185-239`, `BreezyCore` em `:241-243`. O bloco `->registration(...)`
(`:212-218`) tem `MediaPosition::Left` — é a linha do RQ-02.

### `app/Providers/Filament/AdminPanelProvider.php` e `InfraPanelProvider.php`

`AuthDesignerPlugin` com `login` + `passwordReset` (`AdminPanelProvider.php:111-123`,
`InfraPanelProvider.php:145-158`); `BreezyCore` com `->enableTwoFactorAuthentication()`
(`AdminPanelProvider.php:132`, `InfraPanelProvider.php:161`). Sem `registration` — os dois
painéis não têm cadastro, e isso não muda aqui.

### `Panel::emailVerification()` — a assinatura que decide o RQ-05

```php
public function emailVerification(
    string | Closure | array | null $promptAction = EmailVerificationPrompt::class,
    bool | Closure $isRequired = true,
): static
```
(`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:110`)

O `AuthDesignerPlugin::register()` chama
`$panel->emailVerification($this->getEmailVerificationPageClass())`
(`vendor/caresome/filament-auth-designer/src/AuthDesignerPlugin.php:45-47`) — **um argumento
só**, então `isRequired` fica `true`. Consequências de `isRequired = true`, medidas no vendor:

- a rota das páginas de painel ganha o middleware de e-mail verificado
  (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`);
- esse middleware é o `EnsureEmailIsVerified` do Laravel, que só barra usuário
  `instanceof MustVerifyEmail`
  (`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40`);
- `App\Models\User` **não** implementa `MustVerifyEmail` (`app/Models/User.php:29`), então
  hoje ninguém seria barrado — mas a armadilha ficaria **armada**: bastaria alguém acrescentar
  a interface ao model para que todo usuário semeado sem `email_verified_at` perdesse o painel.

E há um segundo fato, **descoberto por medição durante a implementação**, que decidiu o desenho
do passo 4: a tela do Filament **não renderiza** neste kit.
`EmailVerificationPrompt::getVerifiable()` declara retorno `MustVerifyEmail`
(`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`) e
é chamada no `mount()` (`:31`); como `App\Models\User` não implementa a interface, o request morre
em `TypeError`. Ou seja, deixar a rota no ar publicaria uma rota que **sempre** responde 500.

Daí a saída do passo 4: separar os dois efeitos, que a API do Filament permite separar.

- A **configuração** do Auth Designer para a chave `email-verification` vem do flag do PLUGIN,
  gravado em `AuthDesignerPlugin::configureRepository()` (`AuthDesignerPlugin.php:99-101`) durante
  o `boot()`. Não depende da rota. É isto que "veste" a tela, e é isto que o requisito pede.
- A **rota** depende da AÇÃO no painel: `hasEmailVerification()` é
  `filled($this->getEmailVerificationPromptRouteAction())` (`HasAuth.php:620-623`). Passar `null`
  apaga a ação e nenhuma rota é registrada (`vendor/filament/filament/routes/web.php:75-84`).

E `Panel::plugin()` roda `$plugin->register($this)` **na hora**
(`vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:15-21`), então uma chamada a
`->emailVerification(null, isRequired: false)` **depois** do `->plugins([...])` vence a do plugin.
A ordem no `panel()` é o que faz isso funcionar.

## Autorização

Nada a criar. Nenhuma das três telas é de Resource, nenhuma entra na matriz do Shield: 2FA e
confirmação de e-mail são rotas do grupo de auth, não Pages descobertas por `discoverPages()`.
Consequência prática: **nada a ressemear** — não vale aqui a regra "Resource novo exige gerar
as permissões" de `.ai/rules/filament.md`.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/{painel}/two-factor-authentication` | `filament.{painel}.auth.two-factor` | já existia — muda só a **classe** que responde |
| — | — | — | **nenhuma rota nova.** A confirmação de e-mail nasce vestida e com a ação da rota apagada (`->emailVerification(null, ...)`) — ver ADR-03 |

`/app/register` não muda de rota nem de classe: muda só o eixo da mídia.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `App\Filament\Pages\Auth\TelaDoisFatores` | Filament (Page) | `/{painel}/two-factor-authentication` | digita o código TOTP ou o de recuperação e envia | Sim (Livewire + alternador de tema do Auth Designer) |
| `Caresome\…\Pages\Auth\Register` (via `RegistroPorConvite`) | Filament (Page) | `/app/register?token=…` | preenche nome e senha | Sim |
| `Caresome\…\Pages\Auth\EmailVerification` | **não entra no ar** — só a configuração do Auth Designer é registrada | — | — | — |

**Gate de CT-B**: as três afirmações centrais desta entrega — "tem `fi-auth-layout`", "tem
`media-right`", "não vazou `fi-auth-layout` para a página comum" — são **classes no HTML**, e
`$this->get()` as prova por um custo ordens de magnitude menor. O que só o navegador prova é
que a tela de 2FA, agora dentro de um layout diferente do que o Breezy desenhou, **continua
carregando sem erro de JavaScript** (o layout do Auth Designer injeta o alternador de tema e
o Alpine dele). Isso vira **um** CT-B.

**Gate de tela de escrita**: nenhuma rota `create`/`edit` nova. O equivalente aqui é a
**submissão do código** na tela de 2FA — cenário de componente Livewire no `04`, obrigatório:
uma tela de 2FA que abre e não autentica é o mesmo defeito de "tela aberta não é tela que
grava".

## Variáveis de Ambiente

Nenhuma nova.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Todas as páginas Filament dos três painéis** — pelo `static::$layout`. É o risco nº 1, e o
  que o par de casos CT-03/CT-04 mede.
- **Bloqueio de sessão** (`TelaBloqueio`) — usa a mesma trait e a mesma chave `login`. Se a
  chave `login` do Auth Designer mudasse, as duas telas mudariam junto. Não muda aqui.
- **Aceite de convite** (`RegistroPorConvite`) — muda de lado. `tests/Browser/TelasDoKitTest.php:67`
  visita `/app/register` sem token, que **redireciona para o login**; esse cenário continua
  medindo o login, não o register. Não é regressão, é limite pré-existente do CT-B.
- **Middleware `MustTwoFactor` do Breezy** — continua redirecionando para a mesma rota nomeada
  (`vendor/jeffgreco13/filament-breezy/src/Middleware/MustTwoFactor.php:35,37`), que agora
  responde com a nossa subclasse.
- **Nenhuma rota nova em nenhum painel.** Verificado com `php artisan route:list`: as três rotas
  de `two-factor-authentication` trocaram de classe (`App\Filament\Pages\Auth\TelaDoisFatores`) e
  `--path=email-verification` não devolve nada. A superfície de URL do kit é a mesma de antes.

## Rollback

Sem migration, sem dado migrado. Reverter é `git revert` do commit: as três mudanças são
declarativas (dois blocos de provider e uma classe nova).

## Dependências

Nenhuma nova. `caresome/filament-auth-designer` 3.1.0 e `jeffgreco13/filament-breezy` já estão
instalados e registrados nos três painéis.

## Riscos

1. **Vazamento de `$layout`** (crítico) — mitigação: `protected static string $layout` redeclarado
   na classe nova + o par de casos CT-03/CT-04, que é exatamente o par que
   `.ai/rules/auth.md` cobra.
2. **Nome do componente Livewire** — o Breezy registra o alias `two-factor-page` para a classe
   dele (`vendor/jeffgreco13/filament-breezy/src/FilamentBreezyServiceProvider.php:40`); a nossa
   subclasse não tem alias registrado. Mitigação: CT-07 chama a submissão do código por
   `Livewire::test()`, que resolve a classe pelo registry — se o nome não resolvesse, o caso
   ficaria vermelho.
3. **`isRequired` do e-mail** — mitigação: CT-08 assere, nos três painéis,
   `isEmailVerificationRequired() === false`, `hasEmailVerification() === false` e a ausência da
   rota nomeada. É o trio que denuncia se alguém reordenar o `panel()` e a chamada de
   desligamento passar a rodar antes do `->plugins([...])`.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` já tem o channel `autenticacao` (usado por
`app/Filament/Pages/Auth/RegistroPorConvite.php:233` e por `app/Console/Commands/KitAdmin.php`).

### Decisão

**Nenhum log novo, e a ausência é decisão, não esquecimento.** As três mudanças são de
apresentação e de configuração de painel: não há ramo de fluxo, não há falha a registrar, não há
parâmetro a rastrear. Log em `TelaDoisFatores` seria uma linha por render de tela de 2FA no
mesmo arquivo `daily` que o Logs Explorer do `/infra` abre — ruído com custo de disco, sem sinal.

O que **já** é registrado e continua: o Breezy erra o código pelo `addError()`
(`TwoFactorPage.php:154`) e o kit registra tentativa de autenticação pelo
`rappasoft/laravel-authentication-log` do model `User`. Nada disso passa por esta entrega.

## Estrutura de Implementação

### 1. A tela de 2FA vestida — `app/Filament/Pages/Auth/TelaDoisFatores.php`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Filament/Pages/Auth/TelaDoisFatores.php` (novo)
- `final class TelaDoisFatores extends TwoFactorPage`
- `use HasAuthDesignerLayout;` — sem alias: a classe **não** sobrescreve
  `getAuthDesignerConfig()` (diferente de `TelaBloqueio`, que sobrescreve e por isso precisou
  de `{ … as … }`).
- `protected static string $layout = 'filament-auth-designer::components.layouts.auth';`
  — a linha que impede o vazamento. Docblock explicando, apontando para `.ai/rules/auth.md`.
- `protected function getAuthDesignerPageKey(): string { return 'login'; }` — mesma chave do
  login e de `TelaBloqueio`: é a segunda etapa do mesmo login.
- **Nada mais.** Nenhum método de comportamento sobrescrito: o `authenticate()`, o
  `rateLimit(5)` e o alternador de código de recuperação do Breezy continuam intactos.
- **Logs**: nenhum (ver a seção acima).

### 2. Ligar a tela nos três painéis

> Skills: `ponytail`

- **Paths**: `app/Providers/Filament/AppPanelProvider.php`,
  `AdminPanelProvider.php`, `InfraPanelProvider.php`
- Trocar `->enableTwoFactorAuthentication()` por
  `->enableTwoFactorAuthentication(action: TelaDoisFatores::class)`.
- Argumento **nomeado** porque `action` é o 3º parâmetro
  (`HasTwoFactorAuthentication.php:29`) e passar posicional exigiria repetir os defaults de
  `$condition` e `$force`.
- Comentário curto de uma linha em cada painel dizendo por que a subclasse existe, com
  ponteiro para a classe (o porquê longo mora no docblock dela).
- **Logs**: nenhum.

### 3. Espelhar o `register` — uma linha

> Skills: `ponytail`

- **Path**: `app/Providers/Filament/AppPanelProvider.php:215`
- `->mediaPosition(MediaPosition::Left)` → `->mediaPosition(MediaPosition::Right)` dentro do
  bloco `->registration(...)`.
- Ajustar o comentário do bloco: hoje ele fala só do porquê de passar pelo plugin; acrescentar
  a frase do espelho, com a mesma razão já escrita no bloco de `passwordReset` — sair do login
  se anuncia pelo eixo, sem trocar cor nem marca.
- **Logs**: nenhum.

### 4. A confirmação de e-mail vestida, e desligada

> Skills: `ponytail`

- **Paths**: os três providers
- No `AuthDesignerPlugin`, acrescentar depois do `->passwordReset(...)`:
  ```php
  ->emailVerification(fn (AuthPageConfig $config): AuthPageConfig => $config
      ->media(asset('images/auth/login.svg'), alt: config('app.name'))
      ->mediaPosition(MediaPosition::Right)
      ->mediaSize('70%')
      ->themeToggle()
  )
  ```
  É este bloco que grava a chave `email-verification` no `AuthDesignerConfigRepository`
  (`AuthDesignerPlugin.php:99-101`) — é ele, e só ele, que "veste" a tela.
- E **depois** do `->plugins([...])`, na cadeia do `panel()`:
  ```php
  ->emailVerification(null, isRequired: false)
  ```
  `null` no primeiro parâmetro apaga a **ação da rota** que o plugin registrou, então nenhuma
  rota nasce (`hasEmailVerification()` avalia a ação — `HasAuth.php:620-623`). A configuração
  do Auth Designer sobrevive, porque ela é gravada no `boot()` do plugin e não depende da rota.
- **Por que a rota fica fora** (revisado durante a implementação, por medição): a tela do
  Filament responde **500** enquanto `App\Models\User` não implementar `MustVerifyEmail` —
  `EmailVerificationPrompt::getVerifiable()` declara esse retorno
  (`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`)
  e é chamada no `mount()` (`:31`). Ver ADR-03.
- **Ordem é load-bearing**: `Panel::plugin()` registra na hora (`HasPlugins.php:15-21`), então a
  chamada tem de vir **depois** de `->plugins([...])`. Cobrir por CT-08.
- Comentário no `AppPanelProvider` com os TRÊS passos para ligar de verdade (interface no model,
  classe no lugar do `null`, `isRequired: true`); os outros dois providers apontam para ele.
- **Logs**: nenhum.

### 5. Testes

> Skills: `pest-testing`

Ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.

### 6. Verificação e commits

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- `vendor/bin/phpstan analyse --no-progress`
- `composer test:browser`

## Filosofia de Implementação

> **Ponytail ativo em modo `full`**. A escada aplicada aqui deu, em cada degrau:
>
> 1. **Reutilizar** — a trait `HasAuthDesignerLayout` e o precedente `TelaBloqueio` já resolvem
>    "vestir tela de terceiro". Nada de layout novo, nada de blade publicado.
> 2. **Ponto de extensão nativo do pacote** — `enableTwoFactorAuthentication(action:)` em vez do
>    bind no container que `TelaBloqueio` precisou fazer. Menos manobra que o precedente.
> 3. **Uma linha** — o RQ-02 inteiro é a troca de um enum.
> 4. **Recusado**: publicar a view do Breezy, criar chave nova no repositório do Auth Designer,
>    criar channel de log, criar `MediaPosition` próprio para o 2FA.
>
> Atalhos deliberados marcados com comentário `ponytail:`.

## Testes

> Ver `04-casos-de-teste.md` (backend) e `05-casos-de-teste-browser.md` (o único cenário que
> exige navegador).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `composer test:browser`

## Commits

- `:lipstick: fix(auth): 2FA, register e confirmacao de email vestidos pelo auth designer`
- `:white_check_mark: test(auth): cobre o layout das tres telas e o vazamento de $layout`
- `:memo: docs(wiki): wiki da correcao auth-designer-telas`
