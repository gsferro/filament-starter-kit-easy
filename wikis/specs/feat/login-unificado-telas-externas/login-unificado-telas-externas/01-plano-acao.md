# Plano de Ação — Telas externas com o login unificado

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/login-unificado/login-unificado/` (v0.31.0)
- **Motivo**: a primeira entrega fixou os três painéis do kit na tela de escolha e deixou registro e recuperação de senha nas rotas dos painéis. A instalação real (`projeto-3`, cinco painéis, tenancy ligada) expôs: lista fixa, link de registro que leva à recusa, e "Esqueci minha senha" em `/app/password-reset/request`.
- **Toca infra compartilhada?**: sim → `app/Support/Paineis.php` (consumido por `BoasVindas`, `EscolhaDePainel`, selects de papel), `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (Panel Switch dos três painéis), slug da página de configurações (inventário de telas em `tests/Pest.php`). **Regressão obrigatória** contra os CT da ancestral (`tests/Kit/LoginUnificadoTest.php`), `BoasVindasTest`, `RegistroAbertoTest`, `TelasDeAutenticacaoTest`, `ConfiguracoesDoKit*Test`, `InventarioDeTelasTest`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Escolha lista os mesmos painéis, rótulos e ícones do Panel Switch | 1, 2 | rótulo e ícone saem da **mesma** fonte que alimenta o Panel Switch |
| RQ-02 | Painel novo aparece sem mexer no kit | 1, 2 | a lista deixa de ser fixa: `Filament::getPanels()` |
| RQ-03 | URL da página de configurações reflete "Configurações da aplicação" | 3 | slug `configuracoes-da-aplicacao` + redirect do antigo (premissa) |
| RQ-04 | Link de registro da página única leva a registro funcional | 4, 5 | causa: com tenancy o link não carrega `?org=`; correção independe da URL |
| RQ-05 | Análise do `projeto-3` registrada | — (pesquisa) | feita no step 3; achado em `## Contexto` e no `03` |
| RQ-06 | "Esqueci minha senha" em `/esqueci-minha-senha` | 6 | mesmo molde de `/login` |
| RQ-07 | Revisão de todas as telas do Auth Designer com a chave ligada | 7 | tabela `## Revisão das telas externas`; cada ❌ vira passo desta entrega |

## Objetivo

Fazer a página única de login e as telas que a cercam funcionarem para **qualquer** instalação do kit, não só para o kit de fábrica: a escolha de painel passa a nascer da lista de painéis registrados (a mesma que o Panel Switch mostra dentro do Filament, com os mesmos rótulos e ícones), o registro aberto e a recuperação de senha ganham URLs sem prefixo de painel quando a chave está ligada, e o link de registro deixa de levar quem tem tenancy a uma recusa.

De carona, a página de configurações do painel admin passa a responder em `/admin/configuracoes-da-aplicacao`, o nome que ela já exibe no menu.

## Contexto

**O terreno, medido** (citações `arquivo:símbolo:linha`; vendor conferido antes de escrever, regra `.ai/rules/specs.md`):

| Fato | Onde |
|---|---|
| O Panel Switch lista `Filament::getPanels()` filtrado por `canAccessPanel()`; rótulo de `labels()` com fallback `ucfirst(id)`; ícone de `icons()` com fallback `heroicon-o-square-2-stack`. A 3.1.0 **não** tem `excludes()`/`visible()` | `vendor/bezhansalleh/filament-panel-switch/src/PanelSwitch.php:getPanels():242-262`, `canUserAccessPanel():301-314`, `getLabels():193-196`, `getIcons():166-175`; `vendor/bezhansalleh/filament-panel-switch/resources/views/panel-switch-menu.blade.php:33-34` |
| O kit configura o Panel Switch uma vez, global, com **três** rótulos e **três** ícones fixos | `app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraPanelSwitch():324-346` |
| Os cartões da escolha (e da boas-vindas) são **três**, fixos, com rótulo/ícone **diferentes** dos do Panel Switch (`Painel do negócio` × `config('app.name')`; `OutlinedBuildingOffice2` × `heroicon-o-rocket-launch`) | `app/Support/Paineis.php:cartoes():256-274`; `app/Filament/Pages/Auth/EscolhaDePainel.php:getCards():88-104`; `app/Filament/Pages/BoasVindas.php:getCards():153-158` |
| O `projeto-3` tem **cinco** painéis (`gerencial`, `pedidos` além dos três) e teve de customizar `Paineis.php` à mão | `D:\PROJECTS\FIOTEC\PROJETO 3\projeto-3\bootstrap\providers.php:27-28`; log `autenticacao-2026-09-06.log` mostra `paineis: 5 - destino: /login/painel` |
| `CardItem::icon()` aceita string (`heroicon-o-*`) além do enum — o mesmo valor serve aos dois consumidores | `vendor/filament/support/src/Concerns/HasIcon.php:icon():15` (trait usada por `vendor/harvirsidhu/filament-cards/src/CardItem.php:32`) |
| O slug da página de configurações vem do **nome da classe** (`ConfiguracoesDoKit` → `configuracoes-do-kit`); declarar `$slug` muda URL e nome de rota sem renomear a classe | `vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:getDefaultSlug():74-83`, `getRelativeRouteName():56-58`; página em `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:83-85` (título e rótulo já dizem "Configurações da aplicação") |
| Ninguém usa `ConfiguracoesDoKit::getUrl()`: **20** linhas de teste e o inventário de telas batem na URL literal; `ConfiguracoesDoKitDocumentacaoTest` exige a string no README e no trait global | `tests/Pest.php:270`; `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php:37,44,92,118`; lista completa no `03` |
| O link "Cadastre-se" da tela de login é o `registerAction()` do Filament, com `filament()->getRegistrationUrl()` → rota do painel **corrente** — sob `panel:app` é `/app/register`, **sem** `?org=` | `vendor/filament/filament/src/Auth/Pages/Login.php:registerAction():367-373`, `getSubheading():445-456`; `vendor/filament/filament/src/FilamentManager.php:getRegistrationUrl():406-408`; `app/Filament/Pages/Auth/TelaLogin.php:getSubheading():75-78` só mostra/oculta |
| Com tenancy, o registro aberto **exige** `?org={slug}` de organização ativa com `registro_habilitado`; sem isso `recusar()` — por desenho (ADR-07 da wiki `registro-e-aprovacao`) | `app/Filament/Pages/Auth/RegistroPorConvite.php:mount():101-137` (ramo `:125-134`), `recusar():375-431`; `app/Support/RegistroAberto.php:organizacao():115-127`; `docs/pt/autenticacao/registro-aberto.md:85-92` |
| **`projeto-3`**: `KIT_TENANCY=true`; Settings `login_unificado=true`, `registro_habilitado=true`; único tenant (`padrao`) com `registro_habilitado = 0`; cinco recusas `convite_invalido` no log entre 15:16 e 15:19 de 2026-09-06; **nenhum arquivo do kit divergente** (`diff -qr` limpo em `Pages/Auth`, `Support` exceto `Paineis.php`, `Http/Responses`, `database/settings`) | análise somente leitura do step 3 |
| O link "Esqueci minha senha" é um `hint` do campo de senha com `filament()->getRequestPasswordResetUrl()` → rota do painel corrente (`/app/password-reset/request` sob `panel:app`). O e-mail de reset usa `Filament::getResetPasswordUrl()` do painel corrente | `vendor/filament/filament/src/Auth/Pages/Login.php:getPasswordFormComponent():300-309`; `vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:sendResetLink():68,84-85` |
| A página do pedido de reset do kit só acrescenta o anti-robô; a de redefinição é a do vendor | `app/Filament/Pages/Auth/TelaRecuperarSenha.php:29-35` |
| As telas que voltam ao login (`Register::loginAction()`, `RequestPasswordReset::loginAction()`, `recusar()`) usam `getLoginUrl()` do painel → já redirecionam para `/login` com a chave ligada (um salto) | `vendor/filament/filament/src/Auth/Pages/Register.php:loginAction():243-249`; `vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:loginAction():159-169`; `app/Filament/Pages/Auth/RegistroPorConvite.php:recusar():375-431` |
| Padrão do kit para "página de auth em duas rotas": guarda em `mount()` por método sobrescrevível e `HttpResponseException(RedirectResponse)`; rotas do kit no `KitServiceProvider` com `web` + `panel:app` | `.ai/rules/auth.md:15-16`; `.ai/rules/providers.md:8`; `app/Providers/KitServiceProvider.php:configureLoginUnificado():523-532` |

**Diagnóstico do RQ-04 (projeto-3)**: não é `kit:update` incompleto nem defeito do login unificado em si. É a soma de (a) o link de registro da tela de login nunca carregar `?org=` — vale para `/app/login` também, é anterior à v0.31.0 — e (b) a organização `padrao` não ter aceitado cadastro público (toggle "Aceita cadastro público" na tela da entidade). A correção do kit ataca (a): o link só aparece quando há organização resolvível e a carrega. (b) é ação da instalação e vai para a doc.

## Análise dos Arquivos Existentes

### `app/Support/Paineis.php`
- `cartoes()` fixa três painéis com rótulo, ícone (enum), cor e descrição. Passa a iterar `Filament::getPanels()` e a ler rótulo/ícone de mapas compartilhados com o Panel Switch; descrição e cor ficam num mapa só dos painéis do kit, com fallback.

### `app/Providers/Concerns/ConfiguraFilamentGlobal.php`
- `configuraPanelSwitch()` tem os mapas de rótulo e ícone inline. Passa a ler `Paineis::rotulos()` e `Paineis::icones()` — a fonte única que RQ-01 pede.

### `app/Filament/Pages/Auth/EscolhaDePainel.php`
- `getCards()` já filtra por `DestinoAposLogin::paineisDe()`. Não muda de lógica; só herda a lista dinâmica.

### `app/Filament/Pages/BoasVindas.php`
- `getCards()` já usa `Paineis::cartoes()`. Ganha a lista dinâmica de graça; o subtítulo "Três painéis prontos para usar" perde o número.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- Sem `$slug`. Ganha `protected static ?string $slug = 'configuracoes-da-aplicacao';`. Docblocks `:30` e `:339` citam a URL antiga.

### `app/Filament/Pages/Auth/TelaLogin.php`
- `getSubheading()` decide mostrar o link de registro só por `RegistroAberto::habilitado()`. Passa a exigir também organização resolvível quando há tenancy, e o `registerAction()` passa a apontar para a URL do cadastro do kit (com `?org=` quando houver). `getPasswordFormComponent()` passa a apontar o hint para a URL de recuperação do kit.

### `app/Filament/Pages/Auth/RegistroPorConvite.php`
- `mount()` ganha a guarda de laço do padrão `TelaLogin` (`ehAPaginaUnica()`), redirecionando `/app/register?…` para `/cadastro?…` com a chave ligada, preservando a query (`token`, `org`).

### `app/Filament/Pages/Auth/TelaRecuperarSenha.php`
- Idem: guarda de laço para `/esqueci-minha-senha`.

### `app/Support/RegistroAberto.php`
- Ganha `urlDoCadastro(?string $org): string` — a URL certa do registro (página única ou painel) com `?org=` quando houver.

### `app/Providers/KitServiceProvider.php`
- `configureLoginUnificado()` ganha as rotas `/cadastro` e `/esqueci-minha-senha` e o redirect do slug antigo.

## Autorização

- **Policies / Gates**: nenhuma nova. `EscolhaDePainel` continua filtrando por `User::canAccessPanel()` via `DestinoAposLogin::paineisDe()`.
- **Middleware**: as rotas novas usam o grupo existente `['web', 'panel:app']`; `/cadastro` e `/esqueci-minha-senha` são públicas como as do painel que espelham. Nenhuma rota nova autenticada.
- **Guards**: `web`, inalterado.

## Rotas

| Método | URI | Name | Middleware | Handler |
|--------|-----|------|------------|---------|
| GET | `/cadastro` | `cadastro` | `web`, `panel:app` | `App\Filament\Pages\Auth\CadastroUnificado` |
| GET | `/esqueci-minha-senha` | `esqueci-minha-senha` | `web`, `panel:app` | `App\Filament\Pages\Auth\TelaRecuperarSenhaUnificada` |
| GET | `/admin/configuracoes-do-kit` | — | `web` | `Route::redirect(..., '/admin/configuracoes-da-aplicacao', 301)` (premissa RQ-03; **301** porque o slug não volta atrás) |
| GET | `/admin/configuracoes-da-aplicacao` | `filament.admin.pages.configuracoes-da-aplicacao` | painel admin | gerada pelo Filament a partir do `$slug` |

As rotas existentes `/login`, `/login/painel`, `/login/painel/{painel}` não mudam. Registradas **sempre** e decididas por request pela chave (ADR-05 da ancestral).

Os redirects **painel → página única** (`/app/register` → `/cadastro`, `/{painel}/password-reset/request` → `/esqueci-minha-senha`) e os inversos com a chave desligada são **302** (`RedirectResponse` default): dependem da chave e um 301 cacheado pelo navegador viraria laço ao desligá-la (achado da revisão adversarial do `04`).

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `EscolhaDePainel` | Filament (`CardsPage`) | `/login/painel` | vê um cartão por painel acessível, com rótulo e ícone do Panel Switch; clica | Não |
| `TelaLoginUnificada` | Filament (Auth Designer) | `/login` | links "Cadastre-se" (condicional) e "Esqueci minha senha" apontam para `/cadastro` e `/esqueci-minha-senha` | Não |
| `CadastroUnificado` | Filament (Auth Designer) | `/cadastro?org=&token=` | registra (aberto ou por convite) | Não |
| `TelaRecuperarSenhaUnificada` | Filament (Auth Designer) | `/esqueci-minha-senha` | pede o e-mail de reset | Não |
| `ConfiguracoesDoKit` (Page) | Filament | `/admin/configuracoes-da-aplicacao` | mesma tela, URL nova | Não |
| `BoasVindas` | Filament (`CardsPage`) | `/` | um cartão por painel registrado | Não |

**Gate de CT-B**: nenhuma afirmação desta entrega depende de JavaScript, tema ou layout — rótulo, ícone, URL de link, redirect e recusa são provados por HTTP e por componente. O CT-B01 da ancestral (login pela tela → escolha → `/admin`) continua sendo a regressão de navegador; o rótulo que ele clica muda (ver passo 2). Decisão final é da `feature-test-design`.

**Gate de tela de escrita**: `/cadastro` grava conta — o `04` precisa do cenário de gravação por componente (o `RegistroAbertoTest` já tem o molde).

## Variáveis de Ambiente

Nenhuma nova. `KIT_LOGIN_UNIFICADO` continua sendo a semente; o Settings `login_unificado` manda. `KIT_REGISTRO` e `KIT_TENANCY` são lidas como hoje.

## Eventos / Listeners / Observers

Nenhum novo. O `creating` do `AuthenticationLog` (carimbo do painel, ADR-09 da ancestral) não muda.

## Jobs / Queues

Não se aplica.

## Impacto em Features Existentes

- **Boas-vindas (`/`)**: cartões passam a vir de `Filament::getPanels()` — no kit continuam três, com **rótulo e ícone novos** (`config('app.name')` em vez de "Painel do negócio"; `heroicon-o-rocket-launch` etc.). `tests/Kit/BoasVindasTest.php` assere só `href`; `tests/Browser/BoasVindasTest.php:54-56` assere os três rótulos → atualizar.
- **Escolha de painel**: `LoginUnificadoTest` CT-17 (rótulos literais) e `tests/Browser/LoginUnificadoTest.php:33-38` (`assertSee('Administração')`, `click('Administração')`) → "Administração" e "Infraestrutura" continuam iguais; "Painel do negócio" vira `config('app.name')`.
- **Panel Switch**: comportamento idêntico (mesmos valores, agora vindos de `Paineis`).
- **Selects de papel por painel** (`Paineis::opcoes()`): não mudam.
- **Registro**: com a chave **desligada**, o único efeito é o link de login carregar `?org=` quando houver e sumir com tenancy sem organização — corrige o dead end que já existia. `RegistroAbertoTest` CT-04b (`getSubheading() !== null`) precisa do caso "tenancy sem org → null". `TelasDeAutenticacaoTest:227,234` (`/app/register?token=`, chave desligada) não muda.
- **Login unificado (ancestral)**: CT-05 (`/app/register` → login) muda: sem token e sem registro aberto continua recusando (→ `/login`); **com a chave ligada `/app/register` agora redireciona para `/cadastro`** primeiro. CT-28 (`/app/register?token=` → 200) vira 302 → `/cadastro?token=` → 200. CT-40 (página única aponta para `/app/password-reset/request`) **inverte**: aponta para `/esqueci-minha-senha`.
- **Inventário de telas** (`tests/Pest.php:270`) e os 20 `get()`/`visit()` literais → slug novo. `ConfiguracoesDoKitDocumentacaoTest` exige a URL no README e no trait → atualizar os textos e o teste juntos.
- **`kit:update`**: `app/Filament/Pages/Auth`, `app/Support`, `app/Providers` já estão em `CAMINHOS_DO_KIT`. Nada a acrescentar (conferir no passo 8).
- **Instalações que customizaram `Paineis.php`** (o `projeto-3` customizou): o `kit:update` vai mostrar o arquivo como modificado; a doc de atualização já explica o diff. Registrar na nota da release.

## Rollback

- Sem migration. Reverter é `git revert` do PR.
- Com a chave `login_unificado` **desligada**, as rotas `/cadastro` e `/esqueci-minha-senha` redirecionam para as do painel default (mesmo padrão de `/login`), então a feature continua segura de desligar pelo toggle.
- O redirect do slug antigo é uma linha; removê-lo não afeta a página.

## Dependências

Nenhuma nova. `bezhansalleh/filament-panel-switch` 3.1.0, `harvirsidhu/filament-cards`, `caresome/filament-auth-designer` já instalados.

## Riscos

- **Rótulo do `app` vira `config('app.name')`** (paridade com o Panel Switch): muda texto visível na boas-vindas e na escolha. Mitigação: premissa declarada no `00`; testes atualizados; nota no CHANGELOG.
- **Redirect `/app/register` → `/cadastro`** pode surpreender e-mails de convite antigos: o token vai na query e sobrevive (CT). Mitigação: CT com token e com `org`.
- **Broker de senha da página única é o do painel default**: o e-mail de reset leva a `/app/password-reset/reset?...`. Aceito (premissa RQ-06); documentado.
- **Instalações que customizaram `Paineis::cartoes()`**: conflito no `kit:update`. Mitigação: o mapa de descrições/cores fica num único array fácil de estender; nota na release.

## Channel de Log da Feature

Existe: `config/logging.php:'autenticacao':132`. Toda linha nova usa `Log::channel('autenticacao')`, no padrão `[Classe@metodo] mensagem | parametro`. Nenhum canal novo.

## Estrutura de Implementação

### 1. `Paineis` vira a fonte única de rótulo, ícone e cartão por painel (RQ-01, RQ-02)

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/Paineis.php`
- Adicionar:
  ```php
  /** @return array<string, string> id => rótulo; painel fora do mapa usa ucfirst(id), o mesmo fallback do Panel Switch */
  public static function rotulos(): array
  {
      return ['app' => (string) config('app.name'), 'admin' => 'Administração', 'infra' => 'Infraestrutura'];
  }

  /** @return array<string, string> id => ícone (heroicon-o-*); fallback heroicon-o-square-2-stack, o do Panel Switch */
  public static function icones(): array
  {
      return ['app' => 'heroicon-o-rocket-launch', 'admin' => 'heroicon-o-wrench-screwdriver', 'infra' => 'heroicon-o-server-stack'];
  }

  public static function rotulo(Panel $painel): string
  {
      return self::rotulos()[$painel->getId()] ?? Str::ucfirst($painel->getId());
  }

  public static function icone(Panel $painel): string
  {
      return self::icones()[$painel->getId()] ?? 'heroicon-o-square-2-stack';
  }
  ```
- `cartoes()` passa a iterar `Filament::getPanels()`:
  ```php
  /** @return array<string, CardItem> keyed pelo id, um por painel REGISTRADO — painel novo entra sozinho */
  public static function cartoes(): array
  {
      $descricoes = [
          'app'   => ['primary', 'Onde o seu produto vive. Multi-organização, convites e o cadastro do dia a dia.'],
          'admin' => ['info', 'Usuários, papéis e permissões, convites, organizações e agentes de IA.'],
          'infra' => ['gray', 'Filas, logs, exceções, backups, saúde da aplicação e o Pulse.'],
      ];

      return collect(Filament::getPanels())
          ->mapWithKeys(function (Panel $painel) use ($descricoes): array {
              [$cor, $descricao] = $descricoes[$painel->getId()] ?? ['gray', null];

              return [$painel->getId() => CardItem::make(self::url($painel))
                  ->label(self::rotulo($painel))
                  ->description($descricao)
                  ->icon(self::icone($painel))
                  ->color($cor)
                  ->badge('/'.$painel->getPath())];
          })
          ->all();
  }
  ```
- Remover o import `Heroicon` se ficar sem uso; adicionar `Illuminate\Support\Str`.
- **Logs**: nenhum (função pura de apresentação).

### 2. Panel Switch e boas-vindas leem de `Paineis` (RQ-01)

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraPanelSwitch()` — `->labels(Paineis::rotulos())->icons(Paineis::icones())`; manter o comentário sobre `canSwitchPanels()`.
- **Path**: `app/Filament/Pages/BoasVindas.php:getSubheading()` — texto sem número: `'Um cartão por painel. O acesso a cada um continua pedindo login.'`.
- **Path**: `app/Filament/Pages/Auth/EscolhaDePainel.php` — sem mudança de lógica; atualizar o docblock que fala em "três".
- Testes a alinhar (ver `04`): `LoginUnificadoTest` CT-17, `tests/Browser/LoginUnificadoTest.php`, `tests/Browser/BoasVindasTest.php`.
- **Logs**: nenhum.

### 3. Slug `configuracoes-da-aplicacao` (RQ-03)

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — `protected static ?string $slug = 'configuracoes-da-aplicacao';` logo abaixo de `$navigationLabel`; docblocks `:30` e `:339` com a URL nova.
- **Path**: `app/Providers/KitServiceProvider.php:configureLoginUnificado()` — dentro do grupo `web` já existente, uma linha: `Route::redirect('/admin/configuracoes-do-kit', '/admin/configuracoes-da-aplicacao', 301);` *(premissa RQ-03; cai se negada; sem método novo — ponytail)*.
- **Textos que citam a URL** (todos para `configuracoes-da-aplicacao`): `app/Console/Commands/KitInfo.php:20,75,103,177` (saída ao usuário); docblocks em `app/Settings/ConfiguracoesDoKit.php:12`, `app/Support/CustomizadorDaInstalacao.php:380`, `app/Support/CorPrimaria.php:21`, `app/Providers/KitServiceProvider.php:230`, `app/Providers/Concerns/ConfiguraFilamentGlobal.php:35,202,270`, `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php:70,78,92`, `config/kit.php` (6), `resources/views/**` (1), `.ai/rules/pages.md:11`; `README.md`, `README.en.md`; `docs/{pt,en}/**` (24 ocorrências de URL — **o nome do arquivo `configuracoes-do-kit.md` não muda**, ver ADR-03).
- **Não mexer**: tag de auditoria `'tags' => 'configuracoes-do-kit'` (`app/Listeners/AuditarConfiguracoesDoKit.php:140`, migration, `ConfiguracoesDoKitTest:398…`), `CHANGELOG.md`, `wikis/`.
- **Testes** (ver `04`): `tests/Pest.php:270`; `tests/Kit/ConfiguracoesDoKitTelaTest.php:286`; `SegredosDoSettingsTest.php:358`; `LoginSocialGoogleTest.php:892`; `ProtecaoAntiRoboTest.php:737,923,944`; `tests/Browser/ConfiguracoesDoKitTest.php:44,77,101`; `tests/Browser/LoginSocialTest.php:158,163`; `tests/BrowserTenancy/CapturaDeArteTest.php:324,326,482,494`; `ConfiguracoesDoKitDocumentacaoTest.php:37,44,92,118`.
- **Logs**: nenhum.

### 4. `RegistroAberto::urlDoCadastro()` e o link de registro da tela de login (RQ-04)

> Skills: `laravel-best-practices`

- **Path**: `app/Support/RegistroAberto.php`
  ```php
  /**
   * A URL do cadastro aberto: a página única (`/cadastro`) com a chave ligada, a rota do painel
   * `app` sem ela — com `?org=` quando a organização veio no pedido. Fonte única para o link do
   * login e para a doc.
   */
  public static function urlDoCadastro(?string $org = null): string
  {
      $query = filled($org) ? ['org' => $org] : [];

      return ConfiguracaoDoLogin::unificado()
          ? route('cadastro', $query)
          : (string) Filament::getPanel('app')->getRegistrationUrl($query);
  }
  ```
  Assinatura confirmada: `vendor/filament/filament/src/Panel/Concerns/HasAuth.php:getRegistrationUrl():392` aceita `array $parameters` e devolve `?string` — `null` se o painel não tiver registro; o `(string)` é deliberado (o link só é montado quando `filament()->hasRegistration()`).
- **Path**: `app/Filament/Pages/Auth/TelaLogin.php`
  - `getSubheading()`: `RegistroAberto::habilitado() && $this->cadastroTemDestino() ? parent::getSubheading() : null`, onde
    ```php
    /** Com tenancy o cadastro precisa de organização (?org=); sem ela o link levaria à recusa. */
    protected function cadastroTemDestino(): bool
    {
        if (! config('kit.tenancy.enabled')) {
            return true;
        }

        return RegistroAberto::organizacao($this->orgDoPedido()) instanceof Tenant;
    }

    private function orgDoPedido(): ?string
    {
        $org = request()->query('org');

        return is_string($org) ? $org : null;
    }
    ```
  - `registerAction()`: `parent::registerAction()->url(RegistroAberto::urlDoCadastro($this->orgDoPedido()))`.
  - **Logs**: nenhum. Um `info` por render da tela de login com tenancy e sem `?org=` seria o ruído por request que a nota do canal `autenticacao` mediu em 1,1 MB/dia (`config/logging.php:151`); quem estranha o link ausente encontra a resposta na doc (passo 8). *(cortado na auditoria ponytail, 2026-09-06)*
- **Docs**: `docs/{pt,en}/autenticacao/registro-aberto.md` — o link de login só aparece com `?org=`; a organização precisa aceitar cadastro público (é o caso medido no `projeto-3`).

### 5. `/cadastro` — a página única de registro (RQ-04, premissa (b))

> Skills: `laravel-best-practices`

- **Path (novo)**: `app/Filament/Pages/Auth/CadastroUnificado.php`
  ```php
  /** O registro em `/cadastro`, fora dos painéis, quando o login é unificado. Mesmo molde de TelaLoginUnificada. */
  class CadastroUnificado extends RegistroPorConvite
  {
      protected static string $layout = 'filament-auth-designer::components.layouts.auth'; // rule auth.md

      protected function ehAPaginaUnica(): bool { return true; }

      public function mount(): void
      {
          if (! ConfiguracaoDoLogin::unificado()) {
              throw new HttpResponseException(new RedirectResponse(
                  Filament::getPanel('app')->getRegistrationUrl(request()->query()),
              ));
          }

          // *(alterado em 2026-09-07: o `mount()` do vendor manda autenticado para o /app.)*
          if (($user = Filament::auth()->user()) instanceof User) {
              throw new HttpResponseException(new RedirectResponse(DestinoAposLogin::urlPara($user)));
          }

          parent::mount();
      }
  }
  ```
- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php:mount()` — primeira coisa:
  ```php
  if (ConfiguracaoDoLogin::unificado() && ! $this->ehAPaginaUnica()) {
      throw new HttpResponseException(new RedirectResponse(route('cadastro', request()->query())));
  }
  ```
  e `protected function ehAPaginaUnica(): bool { return false; }`. A query (`token`, `org`) atravessa o redirect.
- **Path**: `app/Providers/KitServiceProvider.php:configureLoginUnificado()` — `Route::get('/cadastro', CadastroUnificado::class)->name('cadastro');` dentro do grupo `['web', 'panel:app']`.
- `recusar()` continua redirecionando para `getLoginUrl()` do `app`, que leva a `/login` (um salto a mais, aceito — ADR-04).
- **Logs**: nenhum — o redirect é determinístico (chave ligada → `/cadastro`) e um `info` por acesso repetiria o ruído por request do canal. *(cortado na auditoria ponytail, 2026-09-06)*

### 6. `/esqueci-minha-senha` — a página única do pedido de reset (RQ-06)

> Skills: `laravel-best-practices`

- **Path (novo)**: `app/Filament/Pages/Auth/TelaRecuperarSenhaUnificada.php` — `extends TelaRecuperarSenha`, redeclara `$layout`, `ehAPaginaUnica(): true`, `mount()` com chave desligada → `Filament::getPanel('app')->getRequestPasswordResetUrl()`; autenticado → `DestinoAposLogin::urlPara()` *(alterado em 2026-09-07: sem isto o `mount()` do vendor manda para o `/app`, onde um `admin` toma 403)*; senão `parent::mount()`.
- **Path**: `app/Filament/Pages/Auth/TelaRecuperarSenha.php` — `mount()` com a guarda (`unificado && ! ehAPaginaUnica` → `route('esqueci-minha-senha')`) e `ehAPaginaUnica(): false`. Atualizar o docblock ("dos três painéis").
- **Path**: `app/Filament/Pages/Auth/TelaLogin.php:getPasswordFormComponent()` — `parent::getPasswordFormComponent()->hint(...)` com o mesmo Blade do vendor, trocando a URL por `self::urlDeRecuperacaoDeSenha()`:
  ```php
  public static function urlDeRecuperacaoDeSenha(): string
  {
      return ConfiguracaoDoLogin::unificado()
          ? route('esqueci-minha-senha')
          : (string) filament()->getRequestPasswordResetUrl();
  }
  ```
  Manter `filament()->hasPasswordReset()` como condição do hint.
- **Path**: `app/Providers/KitServiceProvider.php:configureLoginUnificado()` — `Route::get('/esqueci-minha-senha', TelaRecuperarSenhaUnificada::class)->name('esqueci-minha-senha');`.
- **`TelaRecuperarSenhaUnificada::request()` reposiciona o painel corrente no primeiro painel que
  a conta acessa, antes de chamar o pai** *(alterado em 2026-09-07: achado da revisão do passo 7)*.
  O `request()` do Filament só envia o link se
  `$user->canAccessPanel(Filament::getCurrentOrDefaultPanel())`
  (`vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:request():71-77`),
  e sob `panel:app` o corrente é o `app`: quem só acessa o `/admin` não recebia e-mail nenhum.
  O usuário sai de `PasswordBroker::getUser()`, o mesmo caminho do vendor.
- O e-mail continua com `Filament::getResetPasswordUrl()` — agora do painel **da pessoa**
  (`/admin/password-reset/reset?...` para quem só acessa o `/admin`), o que atende a premissa
  RQ-06 ("continua por painel") sem o dead end.
- **Logs**: nenhum novo (o Filament já loga o envio; a decisão de rota é determinística).

### 7. Revisão das telas externas com a chave ligada (RQ-07)

> Skills: `pest-testing`

Tabela viva — preenchida agora com o que a pesquisa mediu, fechada no step 7 com o resultado dos CT. Cada ❌ é passo desta entrega; ⚠️ é aceito com motivo.

| Tela (Auth Designer) | Rota com a chave ligada | Chega em | Volta ao login por | Estado medido | Passo |
|---|---|---|---|---|---|
| `TelaLoginUnificada` | `/login` | direto | — | ✅ v0.31.0 | — |
| `TelaLogin` (painel) | `/{painel}/login` | redireciona `/login` | — | ✅ v0.31.0 | — |
| `EscolhaDePainel` | `/login/painel` | após login | encerra sessão sem painel | ✅ lógica; ❌ lista fixa | 1, 2 |
| Link "Cadastre-se" em `/login` | → `/app/register` sem `?org=` | recusa com tenancy | `recusar()` → `/app/login` → `/login` | ❌ | 4, 5 |
| `RegistroPorConvite` / `CadastroUnificado` | `/app/register?token=` → `/cadastro?token=` | convite ou aberto | `loginAction()` → `/app/login` → `/login` | ❌ URL por painel | 5 |
| Link "Esqueci minha senha" em `/login` | → `/app/password-reset/request` | funciona | — | ❌ URL por painel | 6 |
| `TelaRecuperarSenha` / `…Unificada` | `/{painel}/password-reset/request` → `/esqueci-minha-senha` | pede e-mail | `loginAction()` → `/login` | ❌ URL por painel | 6 |
| Redefinição (vendor) | `/{painel da pessoa}/password-reset/reset?token=…` (do e-mail) | com token assinado | após salvar → `getLoginUrl()` → `/login` | ❌→✅ o e-mail **não saía** para quem não acessa o `/app`; corrigido em 2026-09-07 | 6 |
| Verificação de e-mail (vendor) | `/app/email-verification/prompt` | autenticado | — | ⚠️ pós-login, dentro do painel | — |
| `TelaDoisFatores` (Breezy) | `/{painel}/two-factor…` | após entrar no painel | — | ✅ CT da ancestral | — |
| `TelaBloqueio` (lock screen) | `/{painel}/screen/lock` | autenticado | sair → `/login` | ✅ CT-39 ancestral | — |
| Conta indisponível | `auth.conta-indisponivel` | recusa no login | link para `/login` | conferir no step 7 | — |
| Login social (botões) | `/login/social/{provedor}` | volta pela regra de destino | — | ✅ CT-20/21/41 ancestral | — |

### 8. Docs, CHANGELOG, `kit:update` e wiki ancestral

> Skills: —

- `docs/{pt,en}/autenticacao/login-unificado.md`: seção "O que continua por painel" **reescrita** — registro e pedido de reset agora em `/cadastro` e `/esqueci-minha-senha`; a escolha lista todos os painéis registrados com rótulo/ícone do Panel Switch; e-mail de reset e verificação continuam por painel. Nova subseção "Painel novo depois da instalação".
- `docs/{pt,en}/autenticacao/registro-aberto.md`: link do login só com `?org=`; caso medido.
- `docs/{pt,en}/recursos/configuracoes-do-kit.md` e demais 24 ocorrências: URL nova (arquivo mantém o nome).
- `README.md`/`README.en.md`: URL nova.
- `CHANGELOG.md` `[Unreleased]`: Adicionado (`/cadastro`, `/esqueci-minha-senha`, escolha dinâmica), Alterado (rótulo/ícone dos cartões; slug com redirect), Corrigido (link de registro com tenancy).
- `app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT`: conferir que `app/Filament/Pages/Auth`, `app/Support`, `app/Providers` cobrem os arquivos novos (sem mudança prevista).
- Ancestral: `wikis/specs/feat/login-unificado/login-unificado/02-decisoes-arquiteturais.md` ADR-01 ganha nota "refinada por `login-unificado-telas-externas` ADR-01" (não reescrever).

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** Reutilizar o padrão `ehAPaginaUnica()` + `HttpResponseException` da ancestral; nenhum middleware; nenhuma config nova; um mapa por conceito em `Paineis`. Atalhos deliberados com `ponytail:`.
>
> **Caveman** na conversa; arquivos da wiki, código, commits e PR em prosa normal.

## Testes

> Ver `04-casos-de-teste.md` (derivado do `00`, pela `feature-test-design`): CT-43…CT-65, sem `05` (gate de browser fechado; o CT-B01 da ancestral é atualizado como regressão). Células com tenancy vivem em `tests/Tenancy/LoginUnificadoTenancyTest.php` (novo) — a tenancy é decidida em `tests/TestCase.php:createApplication()`, não por `config()->set()`.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix` (arquivos em `app/Filament` tocados)
- [ ] `php artisan test tests/Kit/LoginUnificadoTest.php tests/Kit/RegistroAbertoTest.php tests/Kit/TelasDeAutenticacaoTest.php tests/Kit/BoasVindasTest.php --compact`
- [ ] `php artisan test tests/Kit/InventarioDeTelasTest.php tests/Kit/ConfiguracoesDoKit*Test.php tests/Kit/SegredosDoSettingsTest.php tests/Kit/ProtecaoAntiRoboTest.php tests/Kit/LoginSocialGoogleTest.php --compact`
- [ ] `php artisan test tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php tests/Kit/KitUpdateTest.php tests/Kit/KitInfoTest.php --compact`
- [ ] CT-B de regressão: `npm run build && php artisan view:cache && vendor/bin/pest tests/Browser/LoginUnificadoTest.php tests/Browser/BoasVindasTest.php tests/Browser/ConfiguracoesDoKitTest.php` (em série)
- [ ] `vendor/bin/phpstan analyse --memory-limit=1G`
- [ ] Regressão em série (chave desligada) — lista do `## Impacto`

## Commits

- `:sparkles: feat(login): escolha de painel lê os painéis registrados com rótulo e ícone do Panel Switch`
- `:sparkles: feat(login): /cadastro e /esqueci-minha-senha na página única`
- `:bug: fix(registro): link de cadastro do login carrega ?org= e some sem organização`
- `:truck: refactor(admin): slug configuracoes-da-aplicacao com redirect do antigo`
- `:white_check_mark: test: casos da wiki login-unificado-telas-externas`
- `:memo: docs: login unificado — telas externas, registro com tenancy, URL das configurações`
- `:memo: docs(wiki): login-unificado-telas-externas`
