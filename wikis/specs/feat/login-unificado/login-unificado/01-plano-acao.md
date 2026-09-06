# Plano de Ação — Página única de login (`/login`) para os três painéis

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: não se aplica. Vizinhas que esta toca: `wikis/specs/feat/login-social-por-painel/` (destino do login social — ADR-06 de lá continua valendo no modo por painel), `wikis/specs/main/pagina-boas-vindas/` (os cartões por painel), `wikis/specs/main/status-e-exclusao-logica-de-usuario/` (a recusa explicada em `TelaLogin::authenticate()` precisa continuar funcionando na página única).
- **Motivo**: feature nova, desligada por default.
- **Toca infra compartilhada?**: **sim** — `TelaLogin` (a tela dos três painéis), o `LoginResponse` do container (destino de **todo** login por senha), `LoginSocialController`, `botoes-sociais.blade.php`, `ConfiguracoesDoKit` (Settings + tela), `KitUpdate::CAMINHOS_DO_KIT`. **Regressão obrigatória** com a configuração **desligada**: `tests/Kit/LoginSocial*Test.php`, `LoginSocialPorPainelTest.php`, `BloqueioDeSessaoTest.php`, `RegistroAbertoTest.php`, `BoasVindasTest.php`, `ConfiguracoesDoKitTest.php`, `KitInfoTest.php`, `KitUpdateTest.php`, e a suíte `Browser` (`RoteiroDoKitTest` F-03 visita `/app/login`).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | configuração que liga/desliga `/login` | 1, 2 | `.env` + Settings (premissa do `00`) |
| RQ-02 | desligado = comportamento atual | 1, 6, 8 | default `false`; `/login` desligado redireciona para o login do painel default (premissa) |
| RQ-03 | ligado = todas as telas de login em `/login` | 6, 8, 9 | `TelaLogin::mount()` redireciona; login social sem `painel` |
| RQ-04 | mais de um painel → tela de escolha | 4, 5, 7, 8 | `DestinoAposLogin` decide; `EscolhaDePainel` exibe |
| RQ-05 | Panel Switch em modo página, senão cartões | 3, 7 | **cartões** — ADR-01; Panel Switch não tem modo página |
| RQ-06 | um só painel → direto | 4, 5 | — |
| RQ-07 | 2FA, lock screen, registro etc. continuam funcionando | 6 | todos chamam `getLoginUrl()` → `TelaLogin` → `/login`; 2FA e lock screen acontecem **dentro** do painel escolhido |
| RQ-08 | escolha só após login e antes de entrar | 7, 8 | rota com `auth`; um painel → nunca renderiza; zero painéis → volta ao login |

## Objetivo

Uma chave `kit.login.unificado` (`.env` e Settings), desligada por default. Ligada: as telas `/admin/login`, `/infra/login` e `/app/login` redirecionam para `/login`, uma única tela de login servida fora dos painéis (mesma classe visual da tela de hoje, mesma arte, mesmos botões sociais, mesmo anti-robô). Depois do login, o kit calcula os painéis que a pessoa pode acessar: um → entra direto nele (ou na URL que ela tinha pedido); mais de um → `/login/painel`, uma página de cartões — os mesmos da tela de boas-vindas — só com os painéis acessíveis; nenhum → a recusa de hoje. O login social segue a mesma regra de destino.

## Contexto

Hoje cada painel tem a própria porta, e quem administra a instalação precisa saber em qual porta bater (`/admin/login` para administrar, `/app/login` para operar). A tela é a mesma classe nos três, o guard é o mesmo, e a decisão "pode entrar neste painel?" já está no model. O que falta é uma porta única e uma decisão de destino depois dela. O Filament oferece os dois pontos de extensão certos: a tela de login é uma página sobrescrevível (o kit já a sobrescreve — `TelaLogin`), e o destino após login é uma classe do container (`LoginResponse`). A tela de boas-vindas já desenha "um cartão por painel" fora dos painéis, com o middleware `panel:app` bootando o painel default para ter tema, cores e layout — é o mesmo molde da página única e da tela de escolha.

## Análise dos Arquivos Existentes

### `app/Filament/Pages/Auth/TelaLogin.php`

- Estende `Caresome\FilamentAuthDesigner\Pages\Auth\Login`; redeclara `$layout` (regra `.ai/rules/auth.md`). Sem `mount()` próprio hoje — herda `Login::mount()` (`Filament`), que redireciona autenticado para `Filament::getUrl()` e preenche o form.
- `authenticate()` embrulha o pai para explicar conta indisponível; usa `Filament::getLoginUrl()` no redirect da conta indisponível (`:60`) — na página única isso resolve para `/app/login`, que redireciona para `/login`. Aceitável (um hop); não mudar.
- **Ganha** `mount()`: redireciona para `route('login')` quando `ConfiguracaoDoLogin::unificado()` e a rota corrente não é `login`. Sai por `HttpResponseException(new RedirectResponse(...))` — o padrão de `RegistroPorConvite::recusar()` e `TelaBloqueio::sairPara()`: dentro de `mount()` de página Livewire, `redirect()` solto devolve o Redirector do Livewire e não interrompe.

### `app/Filament/Pages/BoasVindas.php`

- `getCards()` monta três `CardItem` por `cardDoPainel(painel, rotulo, icone, cor, descricao)`. **Refatorar**: os três cartões saem para `Paineis::cartoes()` (passo 3), keyed pelo id do painel; `BoasVindas::getCards()` vira `array_values(Paineis::cartoes())`. Textos, ícones e cores **não mudam** (`BoasVindasTest` `:56` assere um cartão por painel apontando para a raiz).

### `app/Support/ConfiguracaoDoLogin.php`

- Dona das perguntas de login (`disponivel()`, `disponiveis()`, `rodapeDoLogin()`, `registroAberto()`, `vinculoExigeConfirmacao()`). **Ganha** `unificado(): bool` → `(bool) config('kit.login.unificado', false)`.

### `app/Support/Paineis.php`

- `opcoes()` lista `id => /path` a partir de `Filament::getPanels()`. **Ganha** `cartoes(): array<string, CardItem>` (passo 3).

### `app/Http/Controllers/Auth/LoginSocialController.php`

- `retorno()` termina em `redirect()->to($novo ? $this->urlDoPerfil($user) : $this->urlDoPainel())` (`:346`); `confirmarVinculo()` idem (`:562`). `urlDoPainel()` → `painelDeDestino()->getUrl() ?? url('/')` (`:716-719`). **Muda** `urlDoPainel()`: quando unificado, devolve `DestinoAposLogin::urlPara($user)`; senão, o de hoje. Precisa do `$user` — os três chamadores o têm (`retorno()`, `confirmarVinculo()`, `urlDoPerfil()` `:751`).
- `urlDeLoginDoPainel()` (`:722`) continua: o login do painel redireciona para `/login` quando unificado.

### `resources/views/filament/auth/botoes-sociais.blade.php`

- `:55-70`: `$painelCorrente = Filament::getCurrentPanel()?->getId()` alimenta `disponiveis($painelCorrente)` e a query `painel=`. **Muda**: `$painelCorrente = ConfiguracaoDoLogin::unificado() ? null : Filament::getCurrentPanel()?->getId();` — uma linha; `null` já significa "todos" nos dois usos.

### `app/Settings/ConfiguracoesDoKit.php` e `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

- Propriedades de login `:150-198`, mapa `:372-392`. Tela: Tab "Login" `:489` com seções "Login social", anti-robô, "Rodapé". **Ganha** propriedade `login_unificado`, linha no mapa, migration em `database/settings/`, e um `Toggle` numa seção nova "Página única de login" no topo da aba. São **três lugares + a tela** (regra `.ai/rules/settings.md`); `ConfiguracoesDoKitTest.php:306,358,606` e `KitInfoTest.php:169` derivam o esperado do mapa — propriedade nova entra sozinha.

### `app/Providers/KitServiceProvider.php`

- `configureTelaDeLogin()` (`:473`) registra os render hooks dos botões sociais e do rodapé, sem escopo de painel. **Ganha** `configureLoginUnificado()`: bind de `LoginResponse` e as duas rotas (ADR-05).

### `app/Console/Commands/KitUpdate.php` e `tests/Kit/KitUpdateTest.php`

- `CAMINHOS_DO_KIT` não tem `app/Http/Responses` (não existe ainda) nem `database/settings` (existe desde a v0.19, **nunca entregue**). `DIRETORIOS_DE_CODIGO` da varredura não tem `database/settings`. Passo 10.

### `routes/web.php`

- **Não é tocado.** Não está em `CAMINHOS_DO_KIT` (é arquivo do usuário, como `composer.json`) — rota registrada nele não chega a quem atualiza. As rotas novas vão para o provider (ADR-05).

## Autorização

- **Policies / Gates**: nenhuma nova. A pergunta "pode entrar neste painel?" continua sendo `User::canAccessPanel(Panel)`, chamada por `DestinoAposLogin::paineisDe()`.
- **Middleware**: `/login` → `panel:app` (boota o painel default: tema, cores, layout, auth designer — o molde da boas-vindas). `/login/painel` → `panel:app` + `auth` (guard default `web`, o mesmo dos três painéis). Nenhum middleware novo (ADR-03).
- **Guards**: `web` para tudo, como hoje.

## Rotas

| Método | URI | Name | Middleware | Ação |
|--------|-----|------|------------|------|
| GET | `/login` | `login` | `web`, `panel:app` | `App\Filament\Pages\Auth\TelaLoginUnificada` |
| GET | `/login/painel` | `login.painel` | `web`, `panel:app`, `auth` | `App\Filament\Pages\Auth\EscolhaDePainel` |

Registradas **sempre**, em `KitServiceProvider::configureLoginUnificado()`; o comportamento com a chave desligada é decidido dentro da página (redirect para o login do painel default). Nome `login` é o que o Laravel espera por convenção (`Authenticate` do framework usa `route('login')` quando existe) — aqui não há conflito: os painéis usam `filament.{id}.auth.login`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `TelaLoginUnificada` | Filament (página de auth, layout do Auth Designer) | `/login` | preenche e-mail/senha (+ anti-robô, + botões sociais), envia | Sim (Livewire; anti-robô) |
| `EscolhaDePainel` | Filament `CardsPage`, layout `simple`, escopo CSS `kit-cards-page` | `/login/painel` | clica no cartão do painel | Não (links) |
| Toggle "Página única de login" | Filament (Settings, aba Login) | `/admin/configuracoes-do-kit` | liga/desliga e salva | Não |
| `TelaLogin` dos três painéis | Filament | `/{admin,infra,app}/login` | com a chave ligada, é redirecionado antes de ver a tela | Não |

**Gate de CT-B**: o que só o navegador prova aqui é o **layout do Auth Designer fora de rota de painel** (a arte à esquerda, o toggle de tema, sem erro de JS) e os cartões renderizados sob `kit-cards-page` (CSS escopado — `.ai/rules/css-filament.md`). Login, redirecionamentos, escolha e recusa são teste de componente/HTTP e pertencem ao `04`.

**Gate de tela de escrita**: a única "escrita" é o toggle no Settings — `ConfiguracoesDoKitTest` já tem o padrão de gravação por componente (`gravarConfiguracao()` em `tests/Pest.php:344`); o `04` precisa de um cenário de gravação do toggle **e** do efeito (ligar pela tela muda `/admin/login`).

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_LOGIN_UNIFICADO` | `false` | Página única de login em `/login` para os três painéis. `filter_var(FILTER_VALIDATE_BOOLEAN)`: só `true`/`1` ligam (`.ai/rules/config.md`) |

## Eventos / Listeners / Observers

Nenhum novo. Os eventos `Attempting`/`Failed`/`Login` continuam vindo do fluxo padrão do Filament (o `tapp/filament-authentication-log` e o stat de logins do dia dependem deles — nada muda porque `authenticate()` do pai continua sendo quem autentica).

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Toda tela de login por painel** ganha um `mount()` com uma leitura de config. Desligado: nenhuma mudança observável (`parent::mount()`).
- **Todo login por senha** passa pela `RespostaDeLogin` do kit. Desligado: `parent::toResponse()` — idêntico ao Filament.
- **Login social**: desligado, `urlDoPainel()` e os botões são os de hoje. Ligado: destino pela regra unificada; botões sem `painel`.
- **Stat de logins do dia / log de autenticação (`tapp`)**: gravam o painel do login a partir do painel corrente. Na página única o painel corrente é `app` (`panel:app`). **Consequência aceita**: com a chave ligada, o breakdown por painel registra `app` para todo login por senha — registrar nos docs. Se isso incomodar, é wiki própria (gravar o painel **escolhido**).
- **`LoginSocialContaIndisponivelTest`, `BloqueioDeSessaoTest`, `RegistroAbertoTest`, `RoteiroDoKitTest` F-03** (`/app/register` → `/app/login`): com a chave desligada, inalterados. O `04` precisa de um cenário por fluxo com a chave **ligada** (RQ-07).
- **`BoasVindasTest`**: a refatoração para `Paineis::cartoes()` não muda textos nem URLs.
- **`KitUpdateTest` varredura**: `app/Http/Responses` novo é acusado se faltar na lista; `database/settings` passa a ser varrido e listado.
- **`kit:info`**: mostra as configurações do mapa; a propriedade nova aparece sozinha (`KitInfoTest:163-169`).

## Rollback

- Sem migration de schema. A migration de **settings** adiciona uma propriedade; `down()` a remove.
- Chave `false` (default) desliga tudo em runtime; `git revert` remove o código.

## Dependências

Nenhuma nova. `harvirsidhu/filament-cards` (cartões), `caresome/filament-auth-designer` (layout do login) e `bezhansalleh/filament-panel-switch` já estão instalados.

## Riscos

- **Recusa na página única**: `Login::authenticate()` do Filament recusa quem não acessa o painel **corrente** (`app`). Sem sobrescrever `isUserAllowedToAccessPanel()`, um `admin` sem papel no `/app` **não conseguiria entrar** pela página única. Mitigação: passo 6 sobrescreve para "acessa ao menos um painel"; CT com usuário só-admin.
- **`canAccessPanel()` loga `warning` por painel negado**: calcular os painéis acessíveis loga até dois avisos por login (quem só tem `app` recebe "negado" em `admin` e `infra`). O Panel Switch já faz exatamente isso a **cada request** de página (`getPanels()` → `canUserAccessPanel`), então o ruído não é novo. Registrar; não filtrar o log.
- **Laço de redirecionamento**: `TelaLogin::mount()` redireciona para `route('login')`; a página única **estende** `TelaLogin`. Guarda: a condição inclui `! request()->routeIs('login')`, e a página única desligada redireciona para o login do painel **default** (que, desligado, não redireciona de volta). CT nos dois sentidos.
- **Livewire fora de rota de painel**: `BoasVindas` prova que uma `Page` do Filament serve como ação de rota com `panel:app`, mas ela não tem ação Livewire; a `TelaLoginUnificada` tem **formulário** (`/livewire/update`). Medido: o Filament registra `SetUpPanel` como **middleware persistente** do Livewire (`vendor/filament/filament/src/FilamentServiceProvider.php:106-116`), e middleware persistente é reaplicado pelo Livewire no update a partir da rota **original** do componente — o `panel:app` da rota `/login` volta a rodar no `authenticate()`. Ainda assim, **medir** no primeiro item do passo 6 (submeter o formulário com a chave ligada) e registrar no `03`; o `04` cobre com o cenário de login por componente.
- **`redirect()->intended()`** lê `url.intended` da sessão; o `Authenticate` do Filament grava. A URL pretendida pode ser de um painel que a pessoa **não** acessa (ex.: colou `/admin`); `DestinoAposLogin` só honra a pretendida se o painel dela estiver na lista acessível. Comparação por **prefixo de path** do painel (`/admin/...`), não por igualdade.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php:132` — channel `autenticacao`, usado por `TelaLogin`, `User::canAccessPanel()`, `LoginSocialController`, `UserResource` do `/app`. Toda decisão desta feature é decisão de autenticação.

### Decisão

**Channel existente `autenticacao`**, `Log::channel('autenticacao')`. Sem channel novo: a pergunta "quem entrou e para onde foi" já mora lá, e um segundo arquivo separaria a decisão de destino da autenticação que a motivou.

## Estrutura de Implementação

### 1. A chave: `config/kit.php`, `.env.example`, `ConfiguracaoDoLogin::unificado()`

> Skills: `laravel-best-practices`, `ponytail`

- **`config/kit.php`**, dentro de `'login' => [ … ]`, antes de `'rodape'`:

  ```php
  /*
   * PÁGINA ÚNICA DE LOGIN. Desligada, cada painel tem a própria tela
   * (/admin/login, /infra/login, /app/login) — o default do Filament. Ligada, as
   * três redirecionam para /login, e depois do login o kit decide o destino: um
   * painel acessível → entra nele; mais de um → /login/painel escolhe.
   *
   * Lida POR REQUEST (mount da tela de login, resposta do login, rota /login),
   * por isso pode ser editada na tela de configurações. Ver
   * wikis/specs/feat/login-unificado/.
   */
  'unificado' => filter_var(env('KIT_LOGIN_UNIFICADO', false), FILTER_VALIDATE_BOOLEAN),
  ```

- **`.env.example`**, junto de `KIT_LOGIN_RODAPE`:

  ```dotenv
  # Página única de login em /login para os três painéis. Desligado (default),
  # cada painel tem a própria tela. Ligado, /admin/login, /infra/login e
  # /app/login levam a /login; quem tem acesso a mais de um painel escolhe depois
  # de entrar, quem tem um só entra direto. Só `true`/`1` ligam.
  KIT_LOGIN_UNIFICADO=false
  ```

- **`app/Support/ConfiguracaoDoLogin.php`**:

  ```php
  /** Página única de login em /login para os três painéis. Lida por request — pode vir do Settings. */
  public static function unificado(): bool
  {
      return (bool) config('kit.login.unificado', false);
  }
  ```

- **Logs**: nenhum (leitura de config).

### 2. Settings: propriedade, mapa, migration, toggle

> Skills: `laravel-best-practices`

- **`app/Settings/ConfiguracoesDoKit.php`**: `public bool $login_unificado;` no bloco de login (antes das propriedades por provedor) e `'login_unificado' => 'kit.login.unificado',` no `mapaDeConfiguracao()` junto de `'login_rodape'`.
- **`database/settings/2026_09_05_100000_add_login_unificado_to_kit_settings.php`** (`php artisan make:settings-migration AddLoginUnificadoToKitSettings --no-interaction` — conferir o nome que o comando gera e renomear para o padrão do diretório):

  ```php
  public function up(): void
  {
      $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
          $blueprint->add('login_unificado', (bool) config('kit.login.unificado', false));
      });
  }

  public function down(): void
  {
      $this->migrator->inGroup('kit', function (SettingsBlueprint $blueprint): void {
          $blueprint->delete('login_unificado');
      });
  }
  ```

- **`app/Filament/Admin/Pages/ConfiguracoesDoKit.php`**, aba "Login", **primeira** seção:

  ```php
  Section::make('Página única de login')
      ->description('Uma tela em /login para os três painéis, em vez de /admin/login, /infra/login e /app/login.')
      ->columnSpanFull()
      ->schema([
          Toggle::make('login_unificado')
              ->label('Unificar o login em /login')
              ->helperText('Desligado: cada painel tem a própria tela de login (padrão do Filament). Ligado: as três telas levam a /login; quem tem acesso a mais de um painel escolhe qual abrir depois de entrar, quem tem um só entra direto. Vale na hora, sem deploy.')
              ->columnSpanFull(),
      ]),
  ```

- **Logs**: a gravação já é logada pelo channel `configuracoes` da tela (existente).

### 3. `Paineis::cartoes()` — os cartões por painel saem da boas-vindas

> Skills: `ponytail`

- **`app/Support/Paineis.php`**:

  ```php
  /**
   * Um cartão por painel — os da tela de boas-vindas. Keyed pelo id do painel para quem
   * precisa filtrar (a escolha de painel após o login mostra só os acessíveis).
   *
   * `CardItem` não verifica autorização (.ai/rules/filament.md, "CardItem do hub"). Aqui
   * isso é deliberado nos dois consumidores: a boas-vindas é pública e mostra os três de
   * propósito; a escolha filtra por `canAccessPanel()` ANTES de montar.
   *
   * @return array<string, CardItem>
   */
  public static function cartoes(): array
  {
      $cartao = static function (string $painel, string $rotulo, Heroicon $icone, string $cor, string $descricao): CardItem {
          $instancia = Filament::getPanel($painel);

          return CardItem::make($instancia->getUrl() ?? url($instancia->getPath()))
              ->label($rotulo)->description($descricao)->icon($icone)->color($cor)
              ->badge('/'.$instancia->getPath());
      };

      return [
          'app'   => $cartao('app', 'Painel do negócio', Heroicon::OutlinedBuildingOffice2, 'primary', 'Onde o seu produto vive. Multi-organização, convites e o cadastro do dia a dia.'),
          'admin' => $cartao('admin', 'Administração', Heroicon::OutlinedUsers, 'info', 'Usuários, papéis e permissões, convites, organizações e agentes de IA.'),
          'infra' => $cartao('infra', 'Infraestrutura', Heroicon::OutlinedServerStack, 'gray', 'Filas, logs, exceções, backups, saúde da aplicação e o Pulse.'),
      ];
  }
  ```

  Textos **idênticos** aos de `BoasVindas::getCards()` hoje.

- **`app/Filament/Pages/BoasVindas.php`**: `getCards()` → `return array_values(Paineis::cartoes());`; `cardDoPainel()` sai. Imports de `CardItem`/`Heroicon` saem se não sobrarem usos.
- **Logs**: nenhum.

### 4. `App\Support\DestinoAposLogin` — a decisão de destino

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/DestinoAposLogin.php`

  ```php
  final class DestinoAposLogin
  {
      /** Painéis em que a pessoa pode entrar, na ordem de `Filament::getPanels()`. @return list<Panel> */
      public static function paineisDe(User $user): array
      {
          return array_values(array_filter(
              Filament::getPanels(),
              fn (Panel $painel): bool => $user->canAccessPanel($painel),
          ));
      }

      /**
       * Para onde ir depois de entrar pela página única: a URL pretendida se for de um painel
       * acessível; senão o único painel; senão a tela de escolha. Nunca devolve URL de painel
       * que a pessoa não acessa.
       */
      public static function urlPara(User $user): string
      {
          $paineis   = self::paineisDe($user);
          $pretendida = (string) session()->pull('url.intended', '');

          foreach ($paineis as $painel) {
              if ($pretendida !== '' && str_starts_with(parse_url($pretendida, PHP_URL_PATH) ?: '', '/'.$painel->getPath())) {
                  return $pretendida;
              }
          }

          $destino = match (count($paineis)) {
              0       => Filament::getDefaultPanel()->getLoginUrl() ?? url('/'),
              1       => $paineis[0]->getUrl() ?? url($paineis[0]->getPath()),
              default => route('login.painel'),
          };

          Log::channel('autenticacao')->info(
              "[DestinoAposLogin@urlPara] Destino após login decidido | user: {$user->getKey()} - paineis: ".count($paineis).' - destino: '.parse_url($destino, PHP_URL_PATH),
              ['user_id' => $user->getKey(), 'paineis' => array_map(fn (Panel $p): string => $p->getId(), $paineis), 'pretendida' => $pretendida !== '' ? parse_url($pretendida, PHP_URL_PATH) : null, 'destino' => $destino],
          );

          return $destino;
      }
  }
  ```

  Detalhes: `session()->pull()` consome a pretendida como `redirect()->intended()` faria. `str_starts_with` com `'/'.getPath()` **e** o caso exato (`/admin` sem barra final é `str_starts_with('/admin', '/admin')` — verdadeiro; `/administracao` seria falso positivo para `/admin`: acrescentar a comparação `=== '/'.path` **ou** prefixo `'/'.path.'/'` — escrever assim no código, e o `04` tem o caso).

- **Logs**: o `info` acima (decisão de fluxo, com contexto). `warning` quando `count === 0` (alguém autenticado sem painel) — mesma linha, nível decidido pelo `match`.

### 5. `App\Http\Responses\RespostaDeLogin` + bind

> Skills: `laravel-best-practices`

- **Path**: `app/Http/Responses/RespostaDeLogin.php` (diretório novo — entra em `CAMINHOS_DO_KIT`, passo 10)

  ```php
  final class RespostaDeLogin extends LoginResponse
  {
      public function toResponse($request): RedirectResponse | Redirector
      {
          $user = Filament::auth()->user();

          if (! ConfiguracaoDoLogin::unificado() || ! $user instanceof User) {
              return parent::toResponse($request);
          }

          return redirect()->to(DestinoAposLogin::urlPara($user));
      }
  }
  ```

- **`KitServiceProvider::configureLoginUnificado()`** chamado de `boot()` junto dos outros `configure*` (`KitServiceProvider.php:55-74`; o provider **não** tem `register()` — o bind fica no `boot()`, como o `AppServiceProvider` faz com o `LockerScreen`): `$this->app->bind(LoginResponse::class /* contrato */, RespostaDeLogin::class);`. O contrato é `Filament\Auth\Http\Responses\Contracts\LoginResponse`; a classe base é `Filament\Auth\Http\Responses\LoginResponse`. Imports novos no provider: `Route`, o contrato, `RespostaDeLogin`, `TelaLoginUnificada`, `EscolhaDePainel`.
- **Logs**: nenhum aqui (o `DestinoAposLogin` loga).

### 6. `TelaLogin::mount()` e `TelaLoginUnificada`

> Skills: `laravel-best-practices`, `ponytail`

**Primeiro item deste passo — medir a hidratação Livewire fora de rota de painel** (risco 4): registrar a rota, abrir `/login` com a chave ligada e **submeter** o formulário com credenciais válidas (`vendor/bin/pest --agent` ou o CT de login por componente). Se o update do Livewire não tiver painel corrente, registrar no `03` e decidir entre `Filament::setCurrentPanel()` no `boot()` da página ou `SetUpPanel` persistente — antes de seguir.

- **`app/Filament/Pages/Auth/TelaLogin.php`** — acrescentar:

  ```php
  /**
   * Com a página única ligada, as telas de login dos painéis não são mais a porta: quem
   * chega aqui por qualquer caminho (Authenticate, lock screen, registro, logout, reset de
   * senha — todos passam por getLoginUrl()) é levado a /login. Um ponto cobre todos.
   * A página única ESTENDE esta classe e vive na rota `login` — a condição evita o laço.
   */
  public function mount(): void
  {
      if (ConfiguracaoDoLogin::unificado() && ! request()->routeIs('login')) {
          throw new HttpResponseException(new RedirectResponse(route('login')));
      }

      parent::mount();
  }
  ```

- **`app/Filament/Pages/Auth/TelaLoginUnificada.php`**:

  ```php
  final class TelaLoginUnificada extends TelaLogin
  {
      protected static string $layout = 'filament-auth-designer::components.layouts.auth'; // .ai/rules/auth.md

      public function mount(): void
      {
          if (! ConfiguracaoDoLogin::unificado()) {
              throw new HttpResponseException(new RedirectResponse(Filament::getDefaultPanel()->getLoginUrl() ?? url('/')));
          }

          if (($user = Filament::auth()->user()) instanceof User) {
              throw new HttpResponseException(new RedirectResponse(DestinoAposLogin::urlPara($user)));
          }

          parent::mount();
      }

      /** Na página única, "pode entrar" é "pode entrar em ALGUM painel" — o pai pergunta só pelo corrente (app). */
      protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
      {
          return ! $user instanceof User || DestinoAposLogin::paineisDe($user) !== [];
      }
  }
  ```

  `parent::mount()` é `TelaLogin::mount()`, que com a chave ligada e rota `login` cai em `Login::mount()` (Filament): o `redirect()->intended(Filament::getUrl())` para autenticado nunca roda porque o `if` acima já saiu. A recusa explicada de `TelaLogin::authenticate()` (conta indisponível) continua valendo — ela embrulha `parent::authenticate()`.

- **Logs**: `TelaLogin::authenticate()` já loga a recusa; `User::canAccessPanel()` loga as negativas por painel. Acrescentar em `TelaLoginUnificada::isUserAllowedToAccessPanel()` um `warning` quando a lista é vazia: `"[TelaLoginUnificada@isUserAllowedToAccessPanel] Login recusado na página única: nenhum painel acessível | user: {id}"`, contexto `user_id`, `email` mascarado, `ip`.

### 7. `EscolhaDePainel`

> Skills: `laravel-best-practices`, `tailwindcss-development` (só se precisar de CSS — a expectativa é zero)

- **Path**: `app/Filament/Pages/Auth/EscolhaDePainel.php`

  ```php
  final class EscolhaDePainel extends CardsPage
  {
      use RestrictsFileUploadsToSchemaComponents;

      protected static string $layout = 'filament-panels::components.layout.simple';
      protected static ?string $title = 'Em qual painel você quer entrar?';
      protected static int | string | array $columns = 3;
      protected Width | string | null $maxContentWidth = Width::SevenExtraLarge;

      public function getPageClasses(): array { return ['kit-cards-page']; }

      public function mount(): void
      {
          $user    = Filament::auth()->user();
          $paineis = $user instanceof User ? DestinoAposLogin::paineisDe($user) : [];

          // Dois ou mais: a tela existe para isso. Um: nunca mostra a escolha. Nenhum: encerra a
          // sessão e volta ao login com aviso — o 403 do painel seria uma tela em branco.
          $destino = match (count($paineis)) {
              0       => $this->encerrarSemPainel(),                                  // devolve route('login')
              1       => $paineis[0]->getUrl() ?? url($paineis[0]->getPath()),
              default => null,
          };

          if ($destino !== null) {
              throw new HttpResponseException(new RedirectResponse($destino));
          }
      }

      private function encerrarSemPainel(): string
      {
          Filament::auth()->logout();
          session()->invalidate();
          session()->regenerateToken();
          Notification::make()->title('Sua conta não tem acesso a nenhum painel.')->body('Procure quem administra o sistema.')->warning()->persistent()->send();

          return route('login');
      }

      protected static function getCards(): array
      {
          $user = Filament::auth()->user();
          $ids  = array_map(fn (Panel $p): string => $p->getId(), $user instanceof User ? DestinoAposLogin::paineisDe($user) : []);

          return array_values(array_intersect_key(Paineis::cartoes(), array_flip($ids)));
      }
  }
  ```

  `getCards()` é estático no pacote (`CardsPage`); usa `Filament::auth()->user()` — a rota tem `auth`, então há usuário. `EscolhaDePainel` **não** entra em `getPages()` de nenhum painel (não é navegação); é ação de rota.

- **Logs**: `info` no redirect de painel único e `warning` no caso zero — formato `[EscolhaDePainel@mount] …`, contexto `user_id`, `paineis`.

### 8. Rotas em `KitServiceProvider::configureLoginUnificado()`

> Skills: `laravel-best-practices`

- **`app/Providers/KitServiceProvider.php`**:

  ```php
  /**
   * A página única de login e a escolha de painel. Registradas SEMPRE — rota dentro de
   * `if` quebra route() e route:cache (ver routes/web.php) — e decididas por request pela
   * chave `kit.login.unificado`. Aqui e não em routes/web.php porque esse arquivo é do
   * usuário e o kit:update não o entrega (ADR-05 da wiki login-unificado).
   *
   * `panel:app` boota o painel default: tema, cores, layout do Auth Designer — o mesmo
   * molde da rota `boas-vindas`.
   */
  protected function configureLoginUnificado(): void
  {
      $this->app->bind(LoginResponse::class, RespostaDeLogin::class);

      Route::middleware(['web', 'panel:app'])->group(function (): void {
          Route::get('/login', TelaLoginUnificada::class)->name('login');
          Route::get('/login/painel', EscolhaDePainel::class)->middleware('auth')->name('login.painel');
      });
  }
  ```

  Conferir se o grupo `web` já é aplicado às rotas registradas em provider (em Laravel 11+ com `bootstrap/app.php` → `withRouting(web: …)` **só** `routes/web.php` recebe `web` automaticamente; rota de provider precisa do `middleware('web')` explícito — por isso ele está no grupo). Conferir também que `Route::` em provider roda **depois** de o Filament registrar os aliases (`panel` é alias do `SetUpPanel`, registrado pelo `FilamentServiceProvider`) — a boas-vindas usa o alias em `routes/web.php`, que carrega depois de todos os providers; no `boot()` do `KitServiceProvider` o alias já existe se o provider do Filament bootou antes — **medir com `route:list`** no passo, e usar `$this->app->booted(fn () => …)` se a ordem falhar.

- **Logs**: nenhum.

### 9. Login social no modo unificado

> Skills: `laravel-best-practices`

- **`resources/views/filament/auth/botoes-sociais.blade.php`** `:55`: `$painelCorrente = \App\Support\ConfiguracaoDoLogin::unificado() ? null : \Filament\Facades\Filament::getCurrentPanel()?->getId();` + uma frase no comentário do bloco (sem `@` — `.ai/rules/views.md`).
- **`LoginSocialController::urlDoPainel()`** ganha o `User`: `private function urlDoPainel(User $user): string` → `ConfiguracaoDoLogin::unificado() ? DestinoAposLogin::urlPara($user) : ($this->painelDeDestino()->getUrl() ?? url('/'))`. **Três** chamadores passam `$user`: `retorno()` (`:346`), `confirmarVinculo()` (`:562`) e `urlDoPerfil()` (`:751`, que já recebe `$user`).
- **Logs**: o `info` existente "Autenticado pelo provedor" continua; o destino é logado por `DestinoAposLogin`.

### 10. `kit:update`: `app/Http/Responses` e `database/settings`

> Skills: `ponytail`

- **`app/Console/Commands/KitUpdate.php`** `CAMINHOS_DO_KIT`: `'app/Http/Responses',` (junto de `app/Http/Middleware`) e `'database/settings',` (junto de `database/seeders`), cada um com uma linha de comentário dizendo o que é.
- **`tests/Kit/KitUpdateTest.php`** `DIRETORIOS_DE_CODIGO`: `'database/settings',`.
- **Logs**: nenhum.

### 11. CHANGELOG e documentação

> Skills: nenhuma

- **`CHANGELOG.md`** → `## [Unreleased]` → `### Adicionado`: a página única, a escolha, o default desligado, o alcance (2FA/lock screen/registro), a consequência do painel `app` no log de autenticação, **e** `### Corrigido`: `database/settings` passa a ser entregue pelo `kit:update` (nunca foi).
- **`docs/pt/autenticacao/login-unificado.md`** e **`docs/en/autenticacao/login-unificado.md`** (página nova, `nav_order` depois de `login-social`): o que liga, o que muda para quem entra, a escolha de painel, o que continua por painel, a nota do log. Acrescentar a frase de resumo no `index.md` da seção (pt/en). Conferir o front matter das páginas irmãs (`parent: Autenticação`, `grand_parent`).
- **`docs/pt|en/recursos/configuracoes-do-kit.md`** `:18`: a linha da aba **Login** na tabela lista o que ela controla — acrescentar "página única de login".
- **`README.md` `:191` / `README.en.md`**: bullet novo logo após "**Login social por painel**" — "**Página única de login** (desligada por default): …".

### 12. Testes

> Skills: `pest-testing`

- `04-casos-de-teste.md` (derivado pela `feature-test-design`) e, se o gate passar, `05-casos-de-teste-browser.md`.
- Arquivo previsto: `tests/Kit/LoginUnificadoTest.php` (+ eventual `tests/Browser/LoginUnificadoTest.php`). Helpers existentes: `usuarioDoKit($papel)`, `gravarConfiguracao()`, `alinharConfiguracoesDoKit()`, `espiarAutenticacao()`, `noPainelBootado()`, `telasDoKit()`.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** Dois arquivos novos de código (`DestinoAposLogin`, `RespostaDeLogin`), duas páginas novas (uma estende a tela que já existe; a outra é a boas-vindas filtrada), um método por classe tocada, zero middleware, zero dependência, zero migration de schema. A extração de `Paineis::cartoes()` existe porque há **dois** consumidores — não é abstração antecipada.
>
> Atalhos deliberados com `ponytail:`: o painel `app` como contexto da página única (`panel:app`, herdado da boas-vindas) em vez de um "painel de autenticação" próprio — teto: o log de autenticação registra `app` para logins pela página única.
>
> **Caveman** na conversa; arquivos desta wiki, código e commits em prosa normal.

## Mapeamentos

| Painéis acessíveis | URL pretendida acessível? | Destino |
|---|---|---|
| 0 | — | login do painel default (`TelaLoginUnificada` recusa antes; `EscolhaDePainel` encerra a sessão) |
| 1 | sim | a pretendida |
| 1 | não | `Panel::getUrl() ?? url(path)` do único |
| ≥2 | sim | a pretendida |
| ≥2 | não | `route('login.painel')` |

## Testes

> Ver `04-casos-de-teste.md`; `05-casos-de-teste-browser.md` se o gate passar.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress`
- [ ] `php artisan route:list --name=login` — `/login` e `/login/painel` com `web`, `panel:app` (e `auth` na segunda)
- [ ] `php artisan test tests/Kit/LoginUnificadoTest.php --compact`
- [ ] Regressão com a chave desligada: `php artisan test tests/Kit/LoginSocialTest.php tests/Kit/LoginSocialPorPainelTest.php tests/Kit/LoginSocialContaIndisponivelTest.php tests/Kit/BloqueioDeSessaoTest.php tests/Kit/RegistroAbertoTest.php tests/Kit/BoasVindasTest.php tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/KitInfoTest.php tests/Kit/KitUpdateTest.php --compact`
- [ ] `composer test:kit` (Kit + Tenancy em paralelo)
- [ ] `composer test:browser` (F-03 visita `/app/login`; + CT-B desta wiki se houver)
- [ ] Docs: `php artisan test tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php --compact`

## Commits

- `✨ feat(login): página única de login em /login, com escolha de painel após entrar`
- `♻️ refactor(boas-vindas): os cartões por painel saem para Paineis::cartoes()`
- `🐛 fix(kit:update): entrega database/settings — as migrations de Settings nunca chegaram a quem atualiza`
- `✅ test(login): a página única — chave, redirecionamentos, destino, escolha, fluxos vizinhos`
- `📝 docs: login unificado — CHANGELOG, autenticação (pt/en), configurações`
- `📝 docs(wiki): feat/login-unificado — requisito, plano, ADRs, casos e progresso`
