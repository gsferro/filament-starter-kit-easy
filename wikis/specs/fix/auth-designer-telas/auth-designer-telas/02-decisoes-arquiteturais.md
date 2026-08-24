# Decisões Arquiteturais — auth-designer-telas

## ADR-01: A tela de 2FA entra pelo parâmetro `action:` do Breezy, não por bind no container

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O precedente do kit para vestir tela de terceiro é `TelaBloqueio`, que chega ao lugar da classe
do pacote por `$this->app->bind(LockerScreen::class, TelaBloqueio::class)`
(`app/Providers/AppServiceProvider.php:19`). Foi necessário porque a rota do
`marjose123/filament-lockscreen` resolve `LockerScreen::class` **pelo container**.

O Breezy é diferente. A rota dele é
`Route::get('/two-factor-authentication', $plugin->getTwoFactorRouteAction())`
(`vendor/jeffgreco13/filament-breezy/routes/web.php:24`), e o valor vem de
`enableTwoFactorAuthentication(…, string|Closure|array|null $action = TwoFactorPage::class, …)`
(`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:29,33`).
Existe ponto de extensão declarado.

### Decisão

Passar `action: TelaDoisFatores::class` nos três painéis. Nenhum bind novo em
`AppServiceProvider`.

### Alternativas Consideradas

1. **Bind no container**, imitando `TelaBloqueio` — descartada: o bind não teria efeito nenhum,
   porque a rota do Breezy recebe um **class-string** do plugin e o `Route::get` o entrega ao
   Livewire, sem passar pelo container para escolher a classe. Seria código morto com cara de
   simetria.
2. **Publicar a view `filament-breezy::filament.pages.two-factor`** e envolvê-la no markup do
   Auth Designer à mão — descartada: duplica o layout do pacote, congela a view do Breezy num
   ponto no tempo (toda correção futura dele passaria ao largo) e reimplementa o que a trait
   já faz numa linha.
3. **Registrar a página com `$panel->pages([...])`** — descartada: a rota já existe e é criada
   pelo Breezy; registrar como Page do painel criaria uma segunda rota, item de navegação e
   entrada na matriz do Shield para uma tela que não é de painel.

### Consequências

- **Positivas**: menos manobra que o precedente; a atualização do Breezy continua chegando.
- **Negativas**: a escolha da classe fica espalhada nos três providers, e não num lugar só.
  Aceito — é o mesmo formato de `->login()` / `->passwordReset()`, que já se repetem nos três.
- **Riscos**: o alias Livewire `two-factor-page` que o Breezy registra
  (`vendor/jeffgreco13/filament-breezy/src/FilamentBreezyServiceProvider.php:40`) aponta para a
  classe **dele**; a nossa depende da resolução automática do registry do Livewire. Coberto por
  CT-05 e CT-06, que chamam a submissão do código por `Livewire::test()`.

### Referências

- `app/Providers/AppServiceProvider.php:19` (o precedente que **não** se aplica)
- `.ai/rules/auth.md`

---

## ADR-02: A tela de 2FA usa a chave `login` do Auth Designer

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O `AuthDesignerConfigRepository` só conhece cinco chaves de página: `login`, `registration`,
`password-reset`, `email-verification` e `profile`
(`vendor/caresome/filament-auth-designer/src/AuthDesignerPlugin.php:88-108`). Não há chave para
2FA, e criar uma exigiria mexer no pacote.

Chave inexistente não estoura: `AuthDesignerConfigRepository::getConfig()` cai em
`new AuthPageConfig` quando a chave não foi configurada — a tela nasceria **sem mídia e sem
alternador de tema**, sem erro nenhum. É a mesma armadilha que o comentário de
`AppPanelProvider.php:201-210` já documenta para o `registration`.

### Decisão

`getAuthDesignerPageKey()` devolve `'login'`.

### Alternativas Consideradas

1. **Chave `email-verification`** ou qualquer outra já existente — descartada: mentiria sobre o
   que a tela é, e amarraria o eixo da mídia do 2FA ao de uma tela sem relação.
2. **Chave nova no pacote** (`two-factor`) — descartada: exigiria PR no
   `caresome/filament-auth-designer` ou fork, para uma diferença que o usuário não pediu.
3. **Configurar mídia própria para o 2FA** por `AuthPageConfig` sobrescrito na classe —
   descartada por YAGNI: ninguém pediu arte diferente para a segunda etapa do login.

### Consequências

- **Positivas**: o desafio de 2FA fica **visualmente contínuo** com o login de onde a pessoa
  acabou de sair — mesma arte, mesmo lado, mesmo alternador de tema. É o que o usuário está
  descrevendo quando diz que a tela "não está usando o pacote".
- **Negativas**: mudar a arte do login muda a do 2FA e a do bloqueio de sessão junto. É
  intencional, e é o que `TelaBloqueio::getAuthDesignerPageKey()` já faz
  (`app/Filament/Pages/Auth/TelaBloqueio.php:52-55`).

### Referências

- `app/Filament/Pages/Auth/TelaBloqueio.php:52-55` — o mesmo raciocínio, escrito antes
- Refine: ADR-03 e ADR-04 de `wikis/specs/main/identidade-visual-da-organizacao/`

---

## ADR-03: A confirmação de e-mail nasce VESTIDA e com a rota DESLIGADA

**Status**: Aceita (substitui a versão de planejamento desta mesma ADR, que propunha
`isRequired: false` com a rota no ar — reprovada por medição, ver abaixo)
**Data**: 2026-08-24

### Contexto

O requisito pede duas coisas que, na API do Filament, vêm no mesmo interruptor: a tela precisa
existir vestida (RQ-04) **e** não pode passar a ser usada como default (RQ-05).

`Panel::emailVerification(string|Closure|array|null $promptAction = EmailVerificationPrompt::class, bool|Closure $isRequired = true)`
(`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:110`). O `AuthDesignerPlugin` a chama
com **um argumento** (`vendor/caresome/filament-auth-designer/src/AuthDesignerPlugin.php:45-47`),
então passar pelo plugin liga `isRequired = true`.

**A medição que virou a decisão.** A primeira versão desta ADR propunha registrar a página e
desarmar a exigência com `->emailVerification(EmailVerification::class, isRequired: false)`. Foi
implementada, testada, e o teste reprovou nos três painéis:

```
TypeError: Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt::getVerifiable():
Return value must be of type Illuminate\Contracts\Auth\MustVerifyEmail,
App\Models\User returned
```

A causa está no vendor: `getVerifiable()` declara retorno `MustVerifyEmail`
(`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`)
e é chamada no `mount()` (`:31`); `App\Models\User` não implementa a interface
(`app/Models/User.php:29`). **A tela é estruturalmente inutilizável enquanto o model não
implementar `MustVerifyEmail`** — não é questão de configuração.

Isso muda o custo das alternativas: deixar a rota no ar significaria **publicar uma rota que
sempre responde 500**.

### Decisão

Registrar a configuração do Auth Designer pelo plugin (bloco `->emailVerification(...)` dentro
do `AuthDesignerPlugin`), e **apagar a ação da rota** com
`->emailVerification(null, isRequired: false)` depois do `->plugins([...])`.

Por que os dois efeitos se separam:

| O que | Depende de | Estado após a decisão |
|---|---|---|
| a configuração do Auth Designer para a chave `email-verification` | o flag do **plugin**, gravado em `AuthDesignerPlugin::configureRepository()` (`AuthDesignerPlugin.php:99-101`), chamado no `boot()` do plugin | **presente** — mídia, eixo espelhado, alternador de tema |
| a rota da tela | a **ação** do painel: `hasEmailVerification()` é `filled($this->getEmailVerificationPromptRouteAction())` (`HasAuth.php:620-623`) | **ausente** — `null` apaga a ação, e `routes/web.php:75-84` não registra nada |
| a exigência de verificação | `isEmailVerificationRequired()` (`HasAuth.php:303-306`) | **falsa** |

`Panel::plugin()` roda `$plugin->register($this)` na hora
(`vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:15-21`), então a chamada depois do
`->plugins([...])` é o que vence. **A ordem é load-bearing.**

Ligar de verdade são três passos deliberados, escritos no comentário do `AppPanelProvider`:
`User implements MustVerifyEmail`, trocar o `null` pela classe, trocar `isRequired` por `true`.

### Alternativas Consideradas

1. **`isRequired: false` com a rota no ar** — era a decisão de planejamento. **Reprovada por
   medição**: publica uma rota que responde 500 para quem a abrir. Rota que sempre erra é pior
   que rota que não existe, e a rota anterior a esta entrega era 404.
2. **Deixar o bloco `->emailVerification(...)` comentado** no provider, seguindo o padrão do kit
   para decisão que não deve nascer ligada (`.ai/rules/filament.md`) — descartada por dois
   motivos: descomentar entregaria o `isRequired: true` sem aviso, e o RQ-04 ficaria sem
   nenhum oráculo de runtime (o teste só poderia afirmar sobre o texto do arquivo).
3. **Ligar a verificação de e-mail de verdade** (`User implements MustVerifyEmail` +
   `isRequired: true`) — descartada: muda o fluxo de todo mundo e está **fora do pedido**. Duas
   consequências medidas no vendor: `Register::register()` passaria a disparar
   `sendEmailVerificationNotification()` (`vendor/filament/filament/src/Auth/Pages/Register.php:106,161-164`),
   ou seja, todo aceite de convite mandaria um e-mail de verificação; e o middleware
   `EnsureEmailIsVerified` passaria a barrar quem não tem `email_verified_at`
   (`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40`)
   — o que inclui **todo** usuário que o kit semeia. Registrada como ambiguidade no
   `00-requisito.md`, com o par Assumido / Se negado.
4. **Chamar só `$panel->emailVerification(...)`, sem o bloco no plugin** — descartada: a chave
   `email-verification` não seria gravada no repositório de config e a tela nasceria sem mídia
   e sem alternador de tema. É o defeito que `AppPanelProvider.php` já documenta para o
   `registration`.

### Consequências

- **Positivas**: RQ-04 fica verificável por asserção de runtime sobre o repositório de config
  (mídia, eixo, alternador), não sobre um comentário; RQ-05 fica verificável por três
  asserções (exigência desarmada, `hasEmailVerification()` falso, rota inexistente); e nenhuma
  rota nova entra no ar — zero superfície acrescentada, pública ou autenticada.
- **Negativas**: a tela não pode ser aberta hoje, nem para conferir visualmente. O oráculo é o
  objeto de configuração, um nível abaixo do HTML. Aceito: a alternativa era um 500.
- **Riscos**: a ordem no `panel()` é load-bearing. Reordenar a cadeia e mover a linha para
  antes do `->plugins([...])` recolocaria a rota no ar **e** rearmaria a exigência, em silêncio.
  Mitigado por CT-08, que assere as três coisas nos três painéis.

### Referências

- `vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:31,36-43` — a causa do 500
- `vendor/filament/filament/src/Panel/Concerns/HasAuth.php:110,303-306,620-623`
- `vendor/caresome/filament-auth-designer/src/AuthDesignerPlugin.php:45-47,99-101`
- `vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:15-21`
- `vendor/filament/filament/src/Auth/Pages/Register.php:106,161-164`
- `vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40`

---

## ADR-04: `$layout` redeclarado na classe nova — e o par de testes que prova

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

`HasAuthDesignerLayout::boot()` faz `static::$layout = 'filament-auth-designer::components.layouts.auth'`
(`vendor/caresome/filament-auth-designer/src/Concerns/HasAuthDesignerLayout.php:14`). `$layout`
é **estático**: sem redeclaração na subclasse, a atribuição escreve no estático do ancestral
mais próximo que o declara — no caso do 2FA, `Filament\Pages\SimplePage`
(`vendor/filament/filament/src/Pages/SimplePage.php:12`), que é a base de **toda** página
simples do processo.

`.ai/rules/auth.md` já registra o defeito, e registra que ele mata justamente a página de 2FA
do Breezy com `getAuthDesignerConfig does not exist`. Esta feature é a que fecha o círculo: a
tela vítima passa a ser a tela vestida.

### Decisão

`protected static string $layout = 'filament-auth-designer::components.layouts.auth';` declarado
na `TelaDoisFatores`, com docblock que proíbe a remoção "por parecer redundante com a trait".

E o par de casos que a rule cobra: **CT-03** assere `fi-auth-layout` na tela de 2FA; **CT-04**
assere que uma página comum do painel, visitada **depois** dela no mesmo processo, **não** tem
`fi-auth-layout`.

### Alternativas Consideradas

1. **Confiar na trait** — descartada: é literalmente o defeito documentado.
2. **Só o CT-03**, sem o contrapeso — descartada: o CT-03 fica verde com o vazamento presente.
   Um caso que não distingue as duas situações não é oráculo.

### Consequências

- **Positivas**: a armadilha nº 1 desta feature tem prova mecânica, não prosa.
- **Negativas**: uma linha aparentemente redundante em cada página de auth do kit. É o preço
  documentado na rule.

### Referências

- `.ai/rules/auth.md`
- `app/Filament/Pages/Auth/TelaBloqueio.php:41-50` — o docblock que já diz isso
- `tests/Kit/BloqueioDeSessaoTest.php:93-109` — o par que serve de molde
