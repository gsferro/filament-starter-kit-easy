# Plano de Ação — Proteção anti-robô nas telas públicas

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (é feature nova; as vizinhas `login-social-google`, `mais-provedores-sociais`
  e `settings-do-kit` são referência de padrão, não ancestrais)
- **Motivo**: as três telas públicas com formulário do kit (login, recuperação de senha e registro)
  aceitam qualquer envio que passe pelo rate limit do Filament. O requisito pede um desafio
  anti-robô, desligado por padrão e ligável pela tela de Settings.
- **Toca infra compartilhada?**: sim — os três `PanelProvider` passam a apontar para páginas do kit
  no login (`/admin` e `/infra` usavam a `Login` do Auth Designer) e na recuperação de senha (os
  três), e a tabela de settings ganha quatro propriedades. Regressão obrigatória contra
  `TelasDeAutenticacaoTest`, `BloqueioDeSessaoTest`, `ConviteTest`, `RegistroAbertoTest`,
  `LoginSocialProvedoresTest`, `SegredosDoSettingsTest`, `ConfiguracoesDoKitTest` e
  `ConfiguracoesDoKitTelaTest`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | login protegido | 4, 5, 6 | os três painéis |
| RQ-02 | esqueci a senha protegido | 4, 5, 6 | os três painéis; página nova `TelaRecuperarSenha` |
| RQ-03 | registro protegido | 4, 5 | só o `/app` tem registro |
| RQ-04 | Filament Blueprint | todos | campo próprio com `make()`, namespaces do v5, `Toggle`/`Select`/`TextInput` de `Filament\Forms\Components`, `Section` de `Filament\Schemas\Components`; `AderenciaAoBlueprintTest` varre |
| RQ-05 | análise do `tallcms-registration-plugin` | — | `02-decisoes-arquiteturais.md` ADR-01 (tabela comparativa) |
| RQ-06 | outros pacotes do catálogo | — | ADR-01: nove candidatos levantados |
| RQ-07 | nasce desligada | 1, 2 | `KIT_ANTI_ROBO=false`; e a regra das duas condições (ADR-03) |
| RQ-08 | ligável pela tela de Settings do `/admin` | 2, 3, 7 | chave lida por request → pode ir para o Settings (`.ai/rules/settings.md`) |
| RQ-09 | worktree em paralelo | — | processo; este worktree é a resposta |

## Objetivo

Acrescentar às três telas públicas com formulário do kit um desafio anti-robô do tipo "caixa" —
Google reCAPTCHA v2 por padrão, com Cloudflare Turnstile e hCaptcha como alternativas de mesmo
protocolo — que nasce **desligado** e é ligado pela tela `/admin/configuracoes-do-kit`, aba Login,
com a chave do site e a chave secreta (esta cifrada) gravadas no banco. Com a proteção desligada
as telas ficam exatamente como hoje: nenhum script externo, nenhum campo a mais.

## Contexto

Hoje a única barreira contra envio automatizado nas telas de login, recuperação de senha e registro
é o rate limit do próprio Filament (5, 2 e 2 tentativas por minuto por IP). É pouco para um
formulário de "esqueci a senha" — cada envio bem-sucedido manda um e-mail — e para o registro
aberto, quando ligado. O requisito pede reCAPTCHA; a pesquisa (ADR-01) mostra que nenhum pacote do
catálogo cobre as três telas, com Laravel 13 e Filament 5, lendo a chave do nosso Settings — e que
o protocolo de verificação dos três provedores de "caixa" é o mesmo `POST` com `secret` +
`response`, o que faz a implementação própria caber num campo de formulário e uma regra de
validação (ADR-02).

## Análise dos Arquivos Existentes

### `app/Filament/Pages/Auth/TelaLogin.php`

- Estende `Caresome\FilamentAuthDesigner\Pages\Auth\Login`, que estende `Filament\Auth\Pages\Login`.
  Hoje só sobrescreve `getSubheading()`. Ganha `form()` acrescentando o campo. Hoje só o `/app`
  a usa (`AppPanelProvider` → `usingPage(TelaLogin::class)`); `/admin` e `/infra` usam a do
  vendor. Passam a usar esta — o `getSubheading()` dela devolve `parent::getSubheading()`, que
  para painel sem registro é `null` (`vendor/filament/filament/src/Auth/Pages/Login.php:445-456`),
  então nada muda para eles.
- O `form()` do vendor (`Login.php:244-252`) lista três componentes e o `authenticate()`
  (`:73`) valida via `$this->form->getState()` — a regra do campo novo roda ali, antes de
  `retrieveByCredentials()`.

### `app/Filament/Pages/Auth/RegistroPorConvite.php`

- Estende `Caresome\...\Register` → `Filament\Auth\Pages\Register`. O `form()` do vendor
  (`Register.php:189-198`) lista quatro componentes; `register()` (`:70`) valida via
  `getState()`. Ganha `form()`. O campo é `->dehydrated(false)`, então não entra em `$data` e não
  chega a `handleRegistration()`.

### Recuperação de senha

- Não há página do kit. `Filament\Auth\Pages\PasswordReset\RequestPasswordReset::form()`
  (`RequestPasswordReset.php:141-147`) tem só o e-mail; `request()` (`:56`) valida por
  `getState()`, aplica `rateLimit(2)` e chama `Password::broker()->sendResetLink()`. O Auth
  Designer tem a subclasse `Caresome\...\Pages\Auth\RequestPasswordReset` com `$layout` e a chave
  `password-reset` — é ela que a página nova estende. O plugin resolve a classe por
  `AuthPageConfig::usingPage()` (`AuthDesignerPlugin.php:38-41` → `getRequestPasswordResetPageClass()`).

### `app/Providers/Filament/{App,Admin,Infra}PanelProvider.php`

- Blocos `AuthDesignerPlugin::make()->login(...)->passwordReset(...)`. `/admin` e `/infra` ganham
  `->usingPage(TelaLogin::class)` no login; os três ganham `->usingPage(TelaRecuperarSenha::class)`
  na recuperação.

### `app/Settings/ConfiguracoesDoKit.php`

- Três lugares por propriedade (classe, `mapaDeConfiguracao()`, migration) mais o quarto para
  segredo (`encrypted()`). Quatro propriedades novas. `CT-11` de `ConfiguracoesDoKitTest` confere
  classe × banco por reflexão; `CT-12` percorre todas as migrations de `database/settings/`.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

- `abaLogin()` (linha 471) tem duas `Section`: "Login social" e "Rodapé da tela de login". Ganha
  uma terceira entre elas, "Proteção anti-robô". `mutateFormDataBeforeFill()` (linha 150) zera os
  segredos — ganha a chave nova.

### `app/Support/ConfiguracaoDoLogin.php`

- Ponto único de leitura de `kit.login.*`. Ganha `antiRobo()`, `chaveDoSiteAntiRobo()` e
  `chaveSecretaAntiRobo()`.

### `config/kit.php` e `.env.example`

- Bloco `login` (linha ~490-530) e bloco "Login social" do `.env.example` (linha ~180-247).
  Ganham o sub-bloco `anti_robo` e as quatro chaves `KIT_ANTI_ROBO*`.

### `config/logging.php`

- Canal `autenticacao` já existe (driver `LOG_KIT_DRIVER`, 14 dias) e é o canal de toda decisão de
  entrada do kit. Reutilizado; não se cria canal novo.

## Autorização

- **Policies / Gates**: nenhum novo. As três telas são públicas por natureza; a tela de Settings já
  é governada por `View:ConfiguracoesDoKit` (`ExigePermissaoDaTela`), e os campos novos ficam
  dentro dela.
- **Middleware**: nenhum novo. A verificação é regra de validação do formulário, não middleware —
  ela precisa do token que só existe depois do widget ser resolvido no navegador.

## Rotas

Nenhuma rota nova. As URLs das três telas continuam as do Filament:
`/{painel}/login`, `/{painel}/password-reset/request`, `/app/register`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `TelaLogin` (3 painéis) | Filament (Livewire) | `/{painel}/login` | resolve o desafio e envia | Sim (widget do provedor) |
| `TelaRecuperarSenha` (3 painéis) | Filament | `/{painel}/password-reset/request` | idem | Sim |
| `RegistroPorConvite` | Filament | `/app/register?token=…` ou registro aberto | idem | Sim |
| `ConfiguracoesDoKit` → aba Login → seção "Proteção anti-robô" | Filament `SettingsPage` | `/admin/configuracoes-do-kit?tab=login` | liga o toggle, escolhe o provedor, cola as duas chaves, salva | Não (o `->live()` do toggle é o mesmo das seções vizinhas) |

**Gate de CT-B**: o que só o navegador prova é (a) o widget de terceiro renderizar e (b) o token
chegar ao estado do Livewire via `$wire.set`. Os dois dependem de rede externa (Google/Cloudflare/
hCaptcha) e de chaves reais — não há como rodar em CI sem chave de teste do provedor. **Sem
`05-casos-de-teste-browser.md`**: o motivo está registrado no `04`. Tudo o mais (script presente
ou ausente no HTML, validação, gravação, cifra, visibilidade dos campos) é teste de componente.

**Gate de tela de escrita**: a seção nova da tela de Settings tem cenário de gravação por
componente (CT-13, CT-15 do `04`).

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_ANTI_ROBO` | `false` | interruptor; `FILTER_VALIDATE_BOOLEAN` como os irmãos `KIT_SOCIALITE_*` |
| `KIT_ANTI_ROBO_PROVEDOR` | `recaptcha` | `recaptcha` (Google reCAPTCHA v2), `turnstile` (Cloudflare) ou `hcaptcha`; valor fora da lista = proteção desligada, com `warning` |
| `KIT_ANTI_ROBO_CHAVE_DO_SITE` | vazio | a chave pública, que vai para o HTML |
| `KIT_ANTI_ROBO_CHAVE_SECRETA` | vazio | a chave do servidor; **segredo** — cifrada no banco, fora de log e de tela |

Config: `kit.login.anti_robo.{habilitado,provedor,chave_do_site,chave_secreta}`. As quatro são
semeadas para o banco pela migration de settings e vencidas por ele em tempo de execução.

## Eventos / Listeners / Observers

- **Eventos emitidos**: um evento de navegador, `kit-anti-robo-redefinir`, disparado pelo
  componente Livewire da página depois de cada verificação (o token do widget é de uso único; sem
  redefinir, o segundo envio depois de uma senha errada falharia por "token já usado").
- **Listeners / Observers**: nenhum. `AuditarConfiguracoesDoKit` já mascara o que está em
  `encrypted()`.

## Jobs / Queues

Não se aplica. A verificação é síncrona, dentro do request de envio do formulário — é o único
momento em que a resposta importa.

## Impacto em Features Existentes

- **Login de `/admin` e `/infra`** passa a usar `TelaLogin` em vez da `Login` do Auth Designer:
  mesma classe-mãe, mesmo layout. O único método que a subclasse muda é `getSubheading()`, que
  para painel sem registro devolve o mesmo `null` do vendor.
- **Recuperação de senha** dos três painéis passa a usar `TelaRecuperarSenha`: mesma classe-mãe.
  `TelasDeAutenticacaoTest` ('espelha a recuperação de senha') e o caso da tela de redefinição
  continuam valendo — a chave `password-reset` do Auth Designer é a mesma.
- **Registro** (`ConviteTest`, `RegistroAbertoTest`, `VerificacaoDeEmailTest`): com a proteção
  desligada o campo está oculto, não é validado nem dehidratado — os cenários existentes não
  mudam.
- **Tela de Settings**: `CT-11`/`CT-12` de `ConfiguracoesDoKitTest` contam propriedades por
  reflexão e percorrem as migrations por pasta — entram sozinhas.
- **`PermissoesDeAcoesTest`** inventaria Actions e itens de navegação; a página nova não cria
  Action. **`AderenciaAoBlueprintTest`** varre `app/` e `tests/` — nada do que está aqui usa as
  construções proibidas.

## Rollback

- **Migration down**: `deleteIfExists` das quatro propriedades. Com as linhas fora do banco o
  `aplicarNaConfig()` estoura `MissingSettings` — por isso o `down()` só se roda junto com a
  reversão do código, como toda migration de settings do kit (docblock de `ConfiguracoesDoKit`).
- **Feature flag**: `KIT_ANTI_ROBO=false` (ou o toggle da tela) devolve as telas ao estado atual
  sem nenhum deploy — é a razão de a proteção ser lida por request.
- **Reversão de dados**: não há dado migrado.

## Dependências

- **Composer**: nenhuma nova. Ver ADR-01/ADR-02 — a implementação própria custa menos que o
  adaptador que qualquer pacote exigiria, e `CLAUDE.md` proíbe mudar dependência sem aprovação.
- **NPM**: nenhuma. O script do provedor é carregado do domínio dele, só quando a proteção está
  ligada.

## Riscos

- **Provedor fora do ar**: a verificação falha → o kit **recusa** (falha fechado) e registra
  `warning`. Ninguém entra até o provedor voltar ou alguém desligar o toggle. É a decisão
  registrada em ADR-04; a mitigação é o `timeout(5)` (a pessoa vê o erro em cinco segundos, não em
  trinta) e o fato de o toggle ser por request.
- **Chave errada**: o widget não renderiza e o campo obrigatório nunca é preenchido — o login
  trava. Mitigação: a regra das duas condições não liga sem as duas chaves preenchidas, o
  `helperText` da tela diz onde criar as chaves e o README diz como testar antes de sair da tela.
- **Token consumido por envio anterior**: o widget é redefinido depois de cada verificação
  (evento `kit-anti-robo-redefinir`).
- **`APP_KEY` rotacionada**: a chave secreta cifrada vira ilegível — comportamento normal de todo
  segredo do Settings, coberto pelo `catch (Throwable)` do `KitServiceProvider`.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem `autenticacao` (linha 132), usado por `LoginSocialController`,
`RegistroPorConvite::recusar()`, `ExigirEmailVerificado` e `User::aprovar()`. É o canal de toda
decisão de entrada.

### Decisão

**Reutilizar `autenticacao`.** Um canal por feature aqui separaria "recusei este login por
captcha" de "recusei este login por senha" em dois arquivos, e quem investiga um ataque precisa
dos dois na mesma linha do tempo. Nível `warning` para recusa e para provedor indisponível;
nenhum `info` no sucesso — o login que passou já é registrado pelo `authentication-log`, e um
`info` por login é o ruído que a nota do canal mediu em 1,1 MB/dia.

## Estrutura de Implementação

### 1. Config e `.env.example`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php` — dentro de `'login'`, depois de `'vinculo_confirmar'`:

  ```php
  'anti_robo' => [
      'habilitado'    => filter_var(env('KIT_ANTI_ROBO', false), FILTER_VALIDATE_BOOLEAN),
      'provedor'      => env('KIT_ANTI_ROBO_PROVEDOR') ?: 'recaptcha',
      'chave_do_site' => env('KIT_ANTI_ROBO_CHAVE_DO_SITE'),
      'chave_secreta' => env('KIT_ANTI_ROBO_CHAVE_SECRETA'),
  ],
  ```

  Comentário no estilo do arquivo: as duas condições, o que cada provedor é, e que valor fora da
  lista desliga. `?:` para o provedor porque `env()` devolve string vazia para chave presente e
  vazia (`.ai/rules/config.md`).
- **Path**: `.env.example` — bloco novo depois de `KIT_SOCIALITE_VINCULO_CONFIRMAR` e antes do
  parágrafo "Facebook e Discord", no mesmo estilo de comentário: quatro chaves, onde criar as
  chaves em cada provedor, aviso de que a secreta é segredo.
- **Logs**: nenhum (é config).

### 2. Enum do provedor e ponto único de leitura

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/ProvedorAntiRobo.php` — `enum ProvedorAntiRobo: string implements
  Filament\Support\Contracts\HasLabel`. Casos `Recaptcha = 'recaptcha'`, `Turnstile = 'turnstile'`,
  `Hcaptcha = 'hcaptcha'`. Métodos, todos `match` exaustivo:
  - `getLabel(): string` — "Google reCAPTCHA v2", "Cloudflare Turnstile", "hCaptcha";
  - `urlDoScript(): string` — `https://www.google.com/recaptcha/api.js`,
    `https://challenges.cloudflare.com/turnstile/v0/api.js`, `https://js.hcaptcha.com/1/api.js`.
    Os parâmetros `?render=explicit&onload=…` são acrescentados pelo blade, que é quem define o
    nome da função;
  - `urlDeVerificacao(): string` — `https://www.google.com/recaptcha/api/siteverify`,
    `https://challenges.cloudflare.com/turnstile/v0/siteverify`, `https://api.hcaptcha.com/siteverify`;
  - `objetoJs(): string` — `grecaptcha`, `turnstile`, `hcaptcha` (o global que expõe `render()` e
    `reset()`);
  - `ondeCriarAsChaves(): string` — o roteiro para o `helperText` da tela e para o README.
- **Path**: `app/Support/ConfiguracaoDoLogin.php` — três métodos:
  - `antiRobo(): ?ProvedorAntiRobo` — `null` quando `kit.login.anti_robo.habilitado` é falso, quando
    qualquer das duas chaves não está `filled()`, ou quando `ProvedorAntiRobo::tryFrom(provedor)`
    é `null` (aí também um `warning` — ver logs). É a regra das duas condições do login social,
    com a terceira (provedor válido) porque o valor vem de texto livre no `.env`;
  - `chaveDoSiteAntiRobo(): string` e `chaveSecretaAntiRobo(): string` — `trim((string) config(...))`.
    Só fazem sentido com `antiRobo() !== null`; quem chama já garantiu isso.
- **Logs**:
  - `Log::channel('autenticacao')->warning('[ConfiguracaoDoLogin@antiRobo] Provedor anti-robô desconhecido — proteção tratada como desligada | provedor: {valor}', ['provedor' => $valor, 'conhecidos' => [...]])`
    — só no ramo de provedor inválido. Sem log nos ramos normais: `antiRobo()` roda em todo render
    das telas públicas.

### 3. Settings: propriedades, mapa, cifra e migration

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php` — depois de `$login_vinculo_confirmar`:
  `public bool $login_anti_robo_habilitado; public string $login_anti_robo_provedor;
  public ?string $login_anti_robo_chave_do_site; public ?string $login_anti_robo_chave_secreta;`.
  Em `encrypted()`: `'login_anti_robo_chave_secreta'`. Em `mapaDeConfiguracao()`, as quatro
  linhas para `kit.login.anti_robo.*`.
- **Path**: `database/settings/2026_08_26_100000_add_anti_robo_to_kit_settings.php` — `up()` com
  `add()` para as três primeiras (semeadas de `config()`, com `textoOuNulo` para as chaves, como a
  migration dos provedores) e `addEncrypted()` para a secreta; `down()` com quatro
  `deleteIfExists`. Migration **nova**, nunca a que já rodou (ADR-05 de
  `verificacao-de-email-editavel`).
- **Logs**: nenhum (o `aplicarNaConfig()` já loga o alinhamento).

### 4. O campo de formulário e a view

> Skills: `laravel-best-practices`, `tailwindcss-development` (só para o `x-data`/estilo mínimo)

- **Path**: `app/Filament/Forms/Components/CampoAntiRobo.php` — `final class CampoAntiRobo extends
  Filament\Forms\Components\Field`. `protected string $view = 'filament.forms.components.campo-anti-robo'`.
  `getDefaultName(): 'anti_robo'`. Em `setUp()`, depois de `parent::setUp()`:
  - `->hiddenLabel()`, `->validationAttribute('verificação anti-robô')`;
  - `->visible(fn (): bool => ConfiguracaoDoLogin::antiRobo() !== null)` — oculto, o campo não é
    renderizado (sem script externo, RQ-07) nem validado
    (`Schema::getValidationRules()` pula `isNeitherDehydratedNorValidated()`, que é verdadeiro para
    componente oculto: `vendor/filament/schemas/src/Components/Concerns/HasState.php:801-821`);
  - `->required()`;
  - `->dehydrated(false)` — o token não entra em `$data` (`handleRegistration()` recebe o mesmo
    array de antes). O campo continua validado: `isNeitherDehydratedNorValidated()` devolve
    `false` quando `isValidatedWhenNotDehydrated()` é verdadeiro, que é o default (`:796-810`);
  - `->rules([fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {...}])`
    — a regra: `$this->confirmarToken((string) $value)`; se falso, `$fail('Não foi possível confirmar
    que você não é um robô. Tente de novo.')`. Antes de sair, em qualquer resultado,
    `$this->getLivewire()->dispatch('kit-anti-robo-redefinir')`.
  - `confirmarToken(string $token): bool` (privado) — `Http::asForm()->timeout(5)->post($provedor->urlDeVerificacao(), ['secret' => ConfiguracaoDoLogin::chaveSecretaAntiRobo(), 'response' => $token, 'remoteip' => request()->ip()])`; devolve `$resposta->successful() && $resposta->json('success') === true`. `ConnectionException` e `RequestException` caem no `catch (Throwable)` → `false` + `warning`.
  - Métodos públicos para o blade: `getProvedor(): ProvedorAntiRobo`, `getChaveDoSite(): string`.
  - `public static function acrescentarA(Schema $schema): Schema` — `$schema->components([...$schema->getComponents(withHidden: true), self::make()])`. É a única linha que as três páginas chamam.
- **Path**: `resources/views/filament/forms/components/campo-anti-robo.blade.php` —
  `<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">` com um `<div
  wire:ignore x-data="{...}">` que: (1) define `window.kitAntiRoboPronto = () => render()`;
  (2) se `window[objeto]?.render` já existe, renderiza; senão injeta `<script src="{url}?render=explicit&onload=kitAntiRoboPronto" async defer>`;
  (3) `render()` chama `window[objeto].render($refs.widget, { sitekey, theme, callback: token => $wire.set(statePath, token, false), 'expired-callback' e 'error-callback': () => $wire.set(statePath, null, false) })` e guarda o id;
  (4) `x-on:kit-anti-robo-redefinir.window="window[objeto].reset(id)"`. O `theme` segue
  `document.documentElement.classList.contains('dark')`. Comentário do blade sem nome de diretiva
  (`.ai/rules/views.md`). Nada da chave secreta chega aqui — a view só conhece `getChaveDoSite()`.
- **Logs**:
  - `warning('[CampoAntiRobo@confirmarToken] Token anti-robô recusado pelo provedor | provedor: {p} - ip: {ip}', ['motivo' => 'token_invalido', 'provedor', 'ip', 'erros' => $resposta->json('error-codes')])`
  - `warning('[CampoAntiRobo@confirmarToken] Verificação anti-robô indisponível — envio recusado | provedor: {p} - ip: {ip}', ['motivo' => 'verificacao_indisponivel', 'provedor', 'ip', 'exception' => $e])`
  - Nunca o token nem a chave secreta no contexto.

### 5. As três páginas

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Pages/Auth/TelaLogin.php` — `public function form(Schema $schema): Schema
  { return CampoAntiRobo::acrescentarA(parent::form($schema)); }`. Docblock ganha um parágrafo:
  agora é a tela dos três painéis.
- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php` — mesmo `form()`.
- **Path**: `app/Filament/Pages/Auth/TelaRecuperarSenha.php` (novo) — `class TelaRecuperarSenha
  extends Caresome\FilamentAuthDesigner\Pages\Auth\RequestPasswordReset`, redeclara
  `protected static string $layout` (`.ai/rules/auth.md`) e tem o mesmo `form()`.
- **Logs**: nenhum próprio — a recusa é logada pelo campo.

### 6. Os três `PanelProvider`

> Skills: `laravel-best-practices`

- **Path**: `AdminPanelProvider.php` e `InfraPanelProvider.php` — `->login(fn (AuthPageConfig $config)
  => $config->usingPage(TelaLogin::class)->media(...)...)`.
- **Path**: os três — `->passwordReset(fn ... => $config->usingPage(TelaRecuperarSenha::class)->media(...)...)`.
  `usingPage()` na chave `password-reset` é a página do **pedido**; a de redefinição continua a do
  vendor (`AuthPageConfig::usingResetPage()` não é chamado).
- Comentário curto nos dois `login` novos remetendo ao docblock de `TelaLogin`.

### 7. A tela de Settings

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`:
  - `abaLogin()`: entre "Login social" e "Rodapé", `Section::make('Proteção anti-robô')
    ->description(...)->columnSpanFull()->schema([...])` com
    `Toggle::make('login_anti_robo_habilitado')->live()` (helperText: as duas condições e as três
    telas), `Select::make('login_anti_robo_provedor')->options(ProvedorAntiRobo::class)->required()->native(false)->visible($ligado)`,
    `TextInput::make('login_anti_robo_chave_do_site')->maxLength(255)->visible($ligado)` e
    `TextInput::make('login_anti_robo_chave_secreta')->password()->revealable()->dehydrated(fn (?string $state): bool => filled($state))->placeholder(...)->maxLength(255)->visible($ligado)`.
    O `helperText` do provedor sai de `ondeCriarAsChaves()` do provedor selecionado
    (`fn (Get $get): string`).
  - `mutateFormDataBeforeFill()`: `$data['login_anti_robo_chave_secreta'] = null;`.
  - Método privado `chaveSecretaAntiRoboGuardada(): ?string` para o placeholder, como
    `senhaDeSmtpGuardada()`.
- **Logs**: `afterSave()` já registra quem salvou.

### 8. Testes

> Skills: `pest-testing`

- **Path**: `tests/Kit/ProtecaoAntiRoboTest.php` — os cenários de `04-casos-de-teste.md`. Helper
  local `ligarAntiRobo()` (só este arquivo usa — `.ai/rules/testes.md`).
- `php artisan test --compact tests/Kit/ProtecaoAntiRoboTest.php`, depois os arquivos da lista de
  regressão da seção "Natureza da Wiki".

### 9. README

> Skills: —

- **Path**: `README.md` — seção `### Proteção anti-robô nas telas públicas (opt-in)` dentro de
  "Login social", imediatamente antes de `### Facebook e Discord: por que não estão aqui`.
  Conteúdo: o que é, as três telas, por que reCAPTCHA v2 e não v3, os dois outros provedores, como
  ligar pela tela e pelo `.env` (as duas condições), onde criar as chaves, que nasce desligado, o
  que acontece se o provedor cair, e o que fica de fora.
- **Path**: `README.en.md` — o equivalente antes de `### Facebook and Discord: why they are not here`.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação: reutilizar (`ConfiguracaoDoLogin`,
> `encrypted()`, `mutateFormDataBeforeFill()`, canal `autenticacao`) antes de criar; um enum, um
> campo, uma view, uma página nova. Atalhos deliberados marcados com `ponytail:`. Wiki em prosa
> normal; código e commits também.

## Mapeamentos

| Propriedade do Settings | Chave de config | Env | Tela |
|---|---|---|---|
| `login_anti_robo_habilitado` | `kit.login.anti_robo.habilitado` | `KIT_ANTI_ROBO` | Toggle |
| `login_anti_robo_provedor` | `kit.login.anti_robo.provedor` | `KIT_ANTI_ROBO_PROVEDOR` | Select |
| `login_anti_robo_chave_do_site` | `kit.login.anti_robo.chave_do_site` | `KIT_ANTI_ROBO_CHAVE_DO_SITE` | TextInput |
| `login_anti_robo_chave_secreta` | `kit.login.anti_robo.chave_secreta` | `KIT_ANTI_ROBO_CHAVE_SECRETA` | TextInput password (cifrada) |

| Provedor | Script | Verificação | Objeto JS |
|---|---|---|---|
| `recaptcha` | `https://www.google.com/recaptcha/api.js` | `https://www.google.com/recaptcha/api/siteverify` | `grecaptcha` |
| `turnstile` | `https://challenges.cloudflare.com/turnstile/v0/api.js` | `https://challenges.cloudflare.com/turnstile/v0/siteverify` | `turnstile` |
| `hcaptcha` | `https://js.hcaptcha.com/1/api.js` | `https://api.hcaptcha.com/siteverify` | `hcaptcha` |

Os três aceitam `?render=explicit&onload={fn}` no script, `render(el, {sitekey, theme, callback,
'expired-callback', 'error-callback'})` → id, `reset(id)`, e o `POST` de verificação em
`application/x-www-form-urlencoded` com `secret`, `response` e `remoteip`, respondendo
`{"success": bool, "error-codes": [...]}`.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05`: motivo registrado lá.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse <arquivos tocados> --no-progress`
- [ ] `php artisan test --compact tests/Kit/ProtecaoAntiRoboTest.php`
- [ ] regressão: os oito arquivos da seção "Natureza da Wiki"
- [ ] mutação manual: remover a regra do campo → CT de token inválido cai; remover `visible()` → CT de tela sem script cai; tirar a chave de `encrypted()` → CT de cifra cai

## Commits

- `:sparkles: feat(anti-robo): config, enum do provedor e ponto único de leitura`
- `:sparkles: feat(anti-robo): campo de formulário com verificação no provedor`
- `:sparkles: feat(anti-robo): campo nas três telas públicas dos três painéis`
- `:sparkles: feat(anti-robo): seção na tela de Settings, propriedades e migration`
- `:white_check_mark: test(anti-robo): proteção desligada, ligada, segredo e tela`
- `:memo: docs(anti-robo): README pt/en e wiki da feature`
