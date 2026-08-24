# Plano de Ação — Login social com Google

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (nenhuma; a wiki `main/auth-designer-telas/` é vizinha, não ancestral:
  ela veste as telas de autenticação, esta acrescenta uma superfície nova a uma delas)
- **Motivo**: o kit não tem login social nenhum hoje
- **Toca infra compartilhada?**: **sim** →
  - `app/Providers/KitServiceProvider.php` (render hook global, novo método no `boot()`)
  - `config/services.php` (bloco `google` novo)
  - `config/kit.php` (bloco `login` novo)
  - `routes/web.php` (duas rotas públicas novas — a superfície pública do kit dobra de uma
    rota para três)
  - `composer.json` / `composer.lock` (dependência nova)

> Marcar "sim" **força a regressão** no quality gate mesmo com o tipo `nova`. A regressão
> obrigatória é contra `tests/Kit/TelasDeAutenticacaoTest.php` (24 casos, as três telas de login
> incluídas) e `tests/Kit/BloqueioDeSessaoTest.php`: o render hook novo entra em **toda** tela de
> login dos três painéis, e o par de casos de vazamento de layout é o que denuncia estrago.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Segue a doc oficial do Socialite 13.x | 1, 5 | doc lida via WebFetch + `vendor/` conferido com `file:line` no ADR-01 |
| RQ-02 | Config decide se o login com Google é usado | 2, 3, 4 | interruptor `kit.login.google.habilitado` |
| RQ-03 | Botão abaixo do formulário | 7, 8 | render hook `AUTH_LOGIN_FORM_AFTER` |
| RQ-04 | Config expõe `client_id`, `client_secret`, `redirect` | 2 | metade "abrir os campos" é da tela de Settings — fora de escopo declarado |
| RQ-05 | `redirect` é `/auth/google/callback` | 2, 6 | rota nomeada `auth.google.callback` no mesmo path |
| RQ-06 | Botão só com todos os dados preenchidos | 4, 7 | `ConfiguracaoDoLogin::googleDisponivel()` |
| RQ-07 | Quem se registra por social vai para o perfil | 5 | criação gated por registro aberto (ver Ambiguidades do `00`) |
| RQ-08 | Só Google nesta entrega | 2, 4, 5 | nenhuma abstração de provedor — ver ADR-06 |
| RQ-09 | Ícone do Google no botão | 8 | SVG inline; Heroicons não tem logo de marca — ADR-04 |
| RQ-10 | Rodapé na tela de login | 7, 9 | mesmo render hook, depois do botão |
| RQ-11 | Rodapé vem do Settings | 4, 9 | lido pelo ponto único; hoje de `config`, amanhã do Settings — ADR-02 |
| RQ-12 | Muito bem documentado no README | 11 | `README.md` **e** `README.en.md` |
| RQ-13 | Default do socialite é false | 3, 4 | `KIT_SOCIALITE_GOOGLE` ausente ⇒ false |
| RQ-14 | Default do register é false | 4 | ⚠️ **fora desta entrega** — só o *consumo* do interruptor, com default false; o interruptor é da branch `feat/registro-e-aprovacao` |
| RQ-15 | Com true, reflete em tudo que vem | 4, 6, 7 | o mesmo predicado governa botão **e** rotas — ADR-03 |

## Objetivo

Dar ao kit um segundo caminho de autenticação — "Entrar com Google" — sem abrir o portão que o
convite obrigatório fecha. O botão nasce **fora do ar**: só aparece quando o interruptor está
ligado e as três credenciais estão preenchidas, e as rotas de OAuth respondem 404 na mesma
condição. Na volta do Google, o login **autentica quem já tem conta**; criar conta nova continua
sendo privilégio do convite, a menos que o registro aberto esteja ligado.

Junto vem o rodapé da tela de login, pedido no mesmo requisito e pela mesma razão: as duas coisas
são configuração da tela de login, e as duas passam pelo **mesmo ponto único de leitura** que a
tela de Settings vai alimentar quando ela existir.

## Contexto

O kit tem três telas de login (`/app`, `/admin`, `/infra`), todas vestidas pelo
`caresome/filament-auth-designer` 3.1.0, e uma única porta de entrada para quem vem de fora: o
convite (`app/Filament/Pages/Auth/RegistroPorConvite.php`). `laravel/socialite` **não estava
instalado** — nem direto nem transitivo.

O problema que o requisito resolve é de adoção: senha é fricção, e "entrar com Google" tira a
fricção de quem já tem conta. O problema que o requisito **cria**, e que este plano trata como
fronteira, é o oposto: um callback de OAuth que faz `User::updateOrCreate()` — como o exemplo da
própria doc do Laravel — transforma qualquer pessoa com uma conta Google em usuário do sistema,
contornando convite, verificação e atribuição de papel.

## Análise dos Arquivos Existentes

### `app/Filament/Pages/Auth/TelaLogin.php`

A tela de login do `/app`. Existe só para remover o link "Cadastre-se" (`:22-25`) e para
redeclarar `$layout` (`:20`), como manda `.ai/rules/auth.md`. **Não é tocada por esta feature**:
`/admin` e `/infra` usam a `Login` do pacote direto, e pendurar o botão na subclasse do `/app`
deixaria os outros dois sem botão e sem rodapé. Ver ADR-05.

### `app/Providers/KitServiceProvider.php`

O `boot()` (`:47-59`) chama um método por assunto. Ganha o décimo:
`configureTelaDeLogin()`. É o lugar certo porque o render hook é global aos três painéis e não
pertence a painel nenhum — exatamente o critério do docblock da classe (`:38-42`).

### `config/services.php`

Hoje só `postmark`, `resend`, `ses` e `slack`. O bloco `google` entra no formato que o Socialite
espera e que o requisito escreveu literalmente.

### `config/kit.php`

291 linhas, um bloco comentado por assunto. Ganha o bloco `login`, entre `idiomas` e `convites` —
antes de `convites` de propósito: quem lê o arquivo de cima para baixo encontra o interruptor de
login social **antes** de ler que "o convite é a única forma de alguém de fora virar usuário".

### `routes/web.php`

Hoje **uma** rota: a de boas-vindas (`:21-23`). As duas rotas de OAuth são a segunda e a terceira
superfície pública do kit, e é por isso que elas ganham `throttle` e o guarda de disponibilidade.

### `app/Models/User.php`

Não muda. Nenhuma coluna nova, nenhum `google_id`: o vínculo é pelo e-mail — ver ADR-07, que
também registra o que se ganha e o que se perde com isso.

## Autorização

- **Policies**: nenhuma. A superfície é de autenticação, anterior a qualquer policy.
- **Gates**: nenhum.
- **Middleware**: `web` (grupo do `routes/web.php`, dá sessão — o `state` de CSRF do Socialite
  depende dela) + `throttle:10,1` nas duas rotas.
- **Guards**: o `web` default. `Auth::login()` do `SessionGuard` já faz `session()->migrate(true)`
  (regeneração do id de sessão), então não há passo extra contra fixação de sessão.
- **Barreira própria**: `abort_unless(ConfiguracaoDoLogin::googleDisponivel(), 404)` nas **duas**
  rotas. Interruptor desligado tem de derrubar a rota, não só esconder o botão — ver ADR-03.
- **2FA continua obrigatório**: `Auth::login()` não abre sessão de 2FA. O middleware
  `MustTwoFactor` do Breezy redireciona para o desafio sempre que
  `hasConfirmedTwoFactor() && ! hasValidTwoFactorSession()`
  (`vendor/jeffgreco13/filament-breezy/src/Middleware/MustTwoFactor.php:42-43`), e ele está no
  stack por default: o kit chama `->enableTwoFactorAuthentication(action: TelaDoisFatores::class)`
  sem tocar no 4º parâmetro `$authMiddleware`
  (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:29`).
  É caso de teste (CT-12), não comentário.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/auth/google/redirect` | `auth.google.redirect` | web, throttle:10,1 |
| GET | `/auth/google/callback` | `auth.google.callback` | web, throttle:10,1 |

O path do callback é **literal do requisito** (RQ-05) e é o mesmo valor que vai em
`config('services.google.redirect')`. O Socialite resolve caminho relativo para URL absoluta
sozinho — a doc oficial: *"If the `redirect` option contains a relative path, it will
automatically be resolved to a fully qualified URL."*

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Botão "Entrar com Google" | Blade (render hook em página Filament) | `/app/login`, `/admin/login`, `/infra/login` | clica e sai para o Google | **Não** — é um `<a href>` |
| Rodapé da tela de login | Blade (render hook) | as mesmas três | nenhuma; é texto | Não |

**Gate de CT-B**: as duas superfícies são HTML estático dentro de uma página Livewire. Presença,
ausência, posição no DOM, ícone e texto se provam com `$this->get()` + `assertSee`/`assertSeeInOrder`
— não é preciso navegador. O **único** cenário que só o navegador prova é o par
*botão visível e clicável na tela renderizada* + *nenhum erro de JS novo na tela de login*, porque
`assertSee` fica verde com o botão presente no DOM e escondido. É por isso que existe **um**
arquivo de CT-B, com o mínimo.

**Gate de tela de escrita**: esta feature não tem rota `create`/`edit`. O equivalente aqui é o
callback, e ele é coberto por chamada HTTP direta com o Socialite falseado (CT-06 a CT-12) — o
oráculo é a sessão autenticada e a linha no banco, nunca "a tela abriu".

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_SOCIALITE_GOOGLE` | `false` | Interruptor do login com Google. **Ausente ou vazio ⇒ false** (RQ-13) |
| `GOOGLE_CLIENT_ID` | vazio | Client ID do OAuth do Google |
| `GOOGLE_CLIENT_SECRET` | vazio | **Segredo.** Client secret do OAuth do Google |
| `KIT_LOGIN_RODAPE` | vazio | Texto do rodapé da tela de login. Vazio ⇒ sem rodapé |

`KIT_SOCIALITE_GOOGLE` usa `filter_var(..., FILTER_VALIDATE_BOOLEAN)` sobre `env()`, e **não**
`(bool) env()`: `.ai/rules/config.md` documenta que o segundo argumento do `env()` só vale para
chave **ausente**, e `KIT_SOCIALITE_GOOGLE=` (presente, vazia) devolve string vazia. Para booleano
o efeito de `(bool) ''` é `false`, que por acidente é o default certo — mas `KIT_SOCIALITE_GOOGLE=false`
como **string** seria `true` em `(bool)`. O `filter_var` acerta os dois. Ver ADR-08.

## Eventos / Listeners / Observers

Nenhum novo. O `Auth::login()` dispara os eventos nativos do Laravel, e o
`rappasoft/laravel-authentication-log` (trait `AuthenticationLoggable` em `App\Models\User:24`) já
escuta `Login` — então **o login por Google entra na trilha de acesso do `/infra` de graça**,
sem uma linha escrita. É verificado em CT-13.

## Jobs / Queues

Nenhum. O callback é sincrônico por natureza: o usuário está esperando o redirecionamento.

## Impacto em Features Existentes

- **As três telas de login** (`tests/Kit/TelasDeAutenticacaoTest.php`): o render hook global
  injeta HTML em todas. O risco é o hook estourar quando a config está ausente e derrubar a tela
  — daí o blade sair vazio, não com erro, quando não há nada a mostrar.
- **Layout do Auth Designer** (`.ai/rules/auth.md`): esta feature **não** cria página de
  autenticação nova e **não** mexe em `$layout`, logo não pode reintroduzir o vazamento. O par de
  casos que a rule cobra é herdado, e a regressão contra ele é obrigatória (CT-14, CT-15).
- **Convite obrigatório** (`RegistroPorConvite`): é a feature em risco de ser contornada. O
  desenho a protege por default (sem criação de conta) e CT-08 é o caso que mata o contorno.
- **2FA do Breezy**: em risco de bypass. CT-12.
- **`composer.lock`**: o CI roda `composer install` a partir dele. `composer.json` **e**
  `composer.lock` vão no mesmo commit.

## Rollback

- **Migration down**: não há migration. Nada de schema muda.
- **Feature flag**: `KIT_SOCIALITE_GOOGLE=false` (ou simplesmente apagar a chave) devolve o kit ao
  estado anterior: botão fora do ar, rotas em 404. É o default, então o rollback é o estado de
  nascimento.
- **Reversão de dados**: nada a reverter. Nenhuma conta é criada por default.
- **Remoção total**: `composer remove laravel/socialite` + apagar as duas rotas, o controller, o
  support, os dois blades e o método do `KitServiceProvider`.

## Dependências

- **Composer**: `laravel/socialite` `^5.30` — **instalada nesta feature**, com aprovação
  explícita do solicitante (o requisito nomeia o pacote em RQ-01 e RQ-02).
- **NPM**: nenhuma. O ícone é SVG inline; nenhum pacote de ícones é adicionado (ADR-04).

## Riscos

- **Tomada de conta por e-mail não verificado no provedor** → o callback recusa quando
  `email_verified` (ou o alias legado `verified_email`) não é verdadeiro. CT-10.
- **`client_secret` em log, tela ou erro** → nenhuma linha de log desta feature carrega o segredo,
  e o teste CT-11 assere a ausência dele no HTML da tela e no que o channel grava.
- **Contorno do convite** → criação gated por `registroAberto()`, default false. CT-08.
- **Rota viva com interruptor desligado** → `abort_unless` nas duas. CT-04, CT-05.
- **Depender de rede em teste** → `Socialite::fake()` em 100% dos casos. Nenhum teste sai para a
  internet.
- **A classe de Settings não existe ainda** → tudo o que lê configuração passa por
  `App\Support\ConfiguracaoDoLogin`. Mitigação estrutural, não promessa. ADR-02.

## Channel de Log da Feature

### Verificação de Channel Existente

`grep -n autenticacao config/logging.php` → o channel **`autenticacao`** existe
(`config/logging.php:132-135`), driver por `LOG_KIT_DRIVER`, path `storage/logs/autenticacao.log`.
É onde `User::canAccessPanel()` já grava a negativa de acesso a painel
(`app/Models/User.php:95-102`).

### Decisão

**Reusar `autenticacao`.** Não se cria channel novo. O assunto desta feature é exatamente o do
channel — quem entrou, quem foi recusado e por quê — e um segundo arquivo de log para a mesma
pergunta obriga quem investiga um acesso a ler dois arquivos e correlacionar. A escada do Ponytail
para no primeiro degrau: já existe.

Todos os logs usam `Log::channel('autenticacao')`, formato `[Classe@Método] mensagem | chave: valor`,
e **e-mail mascarado com `Str::mask($email, '*', 3)`** — a régua que o kit já segue em
`Convite.php:176-179` e `KitAdmin.php:216-226`.

## Estrutura de Implementação

### 1. Instalar o `laravel/socialite`

> Skills: — (comando)

```bash
composer require laravel/socialite
```

- Versão resolvida: `^5.30`. Confere `laravel/socialite` em `composer show --direct`.
- **Commitar `composer.json` e `composer.lock` juntos** — o CI roda `composer install` do lock.
- Nenhum `vendor:publish`: o Socialite não publica config (ele lê `config/services.php`).
- **Logs**: nenhum (passo de infraestrutura).

### 2. Bloco `google` em `config/services.php`

> Skills: `laravel-best-practices`

- **Path**: `config/services.php`
- Acrescentar, em ordem alfabética (antes de `postmark`):

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => '/auth/google/callback',
],
```

- O bloco é **literal do requisito**, inclusive o `redirect` relativo.
- Comentário de bloco explicando: (a) por que o `redirect` é relativo e não `env()` — o Socialite
  resolve para URL absoluta e assim o valor acompanha o `APP_URL` de cada ambiente sem uma chave a
  mais para esquecer; (b) que o interruptor de ligar/desligar **não** vive aqui, e sim em
  `config/kit.php`, porque `services.php` é o formato que o pacote exige e não o lugar das opções
  do kit.
- **Logs**: nenhum (config).

### 3. Bloco `login` em `config/kit.php`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`, entre `idiomas` e `convites`
- Acrescentar:

```php
'login' => [
    'google' => [
        'habilitado' => filter_var(env('KIT_SOCIALITE_GOOGLE', false), FILTER_VALIDATE_BOOLEAN),
    ],
    'rodape' => env('KIT_LOGIN_RODAPE'),
],
```

- Comentário de bloco cobrindo, na ordem: o default `false` e por que (RQ-13); por que
  `filter_var` e não `(bool) env()` (`.ai/rules/config.md`); que ligar o interruptor **sem** as
  credenciais não põe o botão no ar; que o login social autentica quem já tem conta e **não**
  cria conta enquanto o registro aberto estiver desligado; e que estas chaves são o **destino** da
  tela de Settings, não o lugar final.
- **Logs**: nenhum (config).

### 4. `App\Support\ConfiguracaoDoLogin` — o ponto único de ligação com o Settings

> Skills: `laravel-best-practices`

- **Path**: `app/Support/ConfiguracaoDoLogin.php` (novo)
- Classe `final`, só métodos estáticos, sem construtor. Segue o padrão dos vizinhos de
  `app/Support/` (`CorPrimaria`, `NumeroDoEnv`, `ValidadeDoConvite`).

Assinaturas:

```php
final class ConfiguracaoDoLogin
{
    /** O provedor social desta entrega. Um só, de propósito — ver ADR-06. */
    public const PROVEDOR_GOOGLE = 'google';

    /** O botão e as rotas do Google entram no ar? Interruptor ligado E credenciais completas. */
    public static function googleDisponivel(): bool;

    /** Texto do rodapé da tela de login, ou null quando não há rodapé. */
    public static function rodapeDoLogin(): ?string;

    /** Registro aberto está ligado? Default false — ver ADR-05 e as Ambiguidades do 00. */
    public static function registroAberto(): bool;
}
```

Corpo de `googleDisponivel()`:

```php
if (! config('kit.login.google.habilitado')) {
    return false;
}

$credenciais = config('services.google', []);

return filled($credenciais['client_id'] ?? null)
    && filled($credenciais['client_secret'] ?? null)
    && filled($credenciais['redirect'] ?? null);
```

- `filled()` e não `isset()`: chave presente com valor vazio é o caso real — é o que sobra quando
  alguém apaga o valor do `.env` e deixa o `=`.
- `rodapeDoLogin()`: `$rodape = config('kit.login.rodape'); return filled($rodape) ? trim((string) $rodape) : null;`
- `registroAberto()`: `return (bool) config('kit.registro.aberto', false);` — a chave **não
  existe** hoje; é a branch `feat/registro-e-aprovacao` que a cria. Ausente ⇒ `false`, que é o
  default que RQ-14 pede.
- **Docblock da classe é obrigatório e é o contrato**: dizer, em letras, que esta classe é o
  **único** ponto do código que lê configuração da tela de login, e que quando a tela de Settings
  existir **só o corpo destes três métodos muda** — nem o controller, nem os blades, nem as rotas,
  nem os testes.
- **Logs**: nenhum. Predicado de leitura não loga; logar aqui poluiria toda renderização de tela
  de login.

### 5. `App\Http\Controllers\Auth\LoginComGoogleController`

> Skills: `laravel-best-practices`, `laravel-specialist`

- **Path**: `app/Http/Controllers/Auth/LoginComGoogleController.php` (novo)
- Estende `App\Http\Controllers\Controller`.

```php
final class LoginComGoogleController extends Controller
{
    public function redirecionar(): RedirectResponse|SymfonyRedirectResponse;
    public function retorno(): RedirectResponse;
}
```

#### `redirecionar()`

1. `abort_unless(ConfiguracaoDoLogin::googleDisponivel(), 404);`
2. `return Socialite::driver('google')->redirect();`

Nada de `->stateless()`. O `state` de CSRF é do Socialite e fica **ligado**:
`AbstractProvider::$stateless = false` (`vendor/laravel/socialite/src/Two/AbstractProvider.php:83`),
`redirect()` grava `state` na sessão (`:166`) e `user()` compara com `hash_equals` e lança
`InvalidStateException` quando não casa (`:236-237`, `:288-290`).

**Logs**:
- `Log::channel('autenticacao')->info('[LoginComGoogleController@redirecionar] Redirecionando para o Google | ip: {ip}', ['ip' => request()->ip(), 'provedor' => 'google'])`

#### `retorno()`

Na ordem, e a ordem importa — cada passo é uma barreira:

1. `abort_unless(ConfiguracaoDoLogin::googleDisponivel(), 404);`
2. `try { $googleUser = Socialite::driver('google')->user(); } catch (Throwable $e) { … }`
   → no `catch`: log `warning` com `'exception' => $e` e `recusar('Não foi possível concluir a
   entrada com o Google.')`. Cobre `InvalidStateException` (state de CSRF), erro de rede e
   credencial inválida com **uma** cláusula, porque a resposta ao usuário é a mesma nos três e
   detalhar o motivo na tela é dizer a um atacante qual barreira ele encostou.
3. **E-mail verificado no provedor.** `$raw = $googleUser->getRaw();` e
   `$verificado = filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);`
   Não verificado ⇒ log `warning` + `recusar('A sua conta do Google não tem o e-mail verificado.')`.
   As duas chaves porque o `GoogleProvider` popula as duas: `email_verified` é a do userinfo v3 e
   `verified_email` é o alias que ele mantém por compatibilidade
   (`vendor/laravel/socialite/src/Two/GoogleProvider.php:91`).
4. **E-mail presente.** Vazio ⇒ mesma recusa (log `warning`, motivo `email_ausente`).
5. **Encontrar a conta.** `User::query()->whereRaw('lower(email) = ?', [mb_strtolower(trim($email))])->first()`
   — comparação normalizada nos dois lados, a mesma régua de `Convite::exigirDono()`.
6. **Não encontrou**:
   - `ConfiguracaoDoLogin::registroAberto()` **false** ⇒ log `warning` (motivo
     `conta_inexistente_registro_fechado`) + `recusar('Não há conta com este e-mail. O acesso a
     este sistema é por convite.')`. **É a barreira do convite.**
   - `true` ⇒ cria: `User::create(['name' => $googleUser->getName() ?: $email, 'email' => $email, 'password' => Str::password(32)])`.
     Senha aleatória e descartada — a conta nasce sem senha utilizável, e quem quiser uma usa a
     recuperação de senha. **Nenhum papel é atribuído** (ver Ambiguidades do `00`).
     `$novo = true;` e log `info` (motivo `conta_criada_por_login_social`).
7. `Auth::login($user);` — o `SessionGuard` já regenera o id de sessão.
8. Log `info` de sucesso.
9. **Destino**: `$novo ? $this->urlDoPerfil() : Filament::getPanel('app')->getUrl()`.
   `urlDoPerfil()` resolve pela rota nomeada do Breezy do painel `app`
   (`filament.app.pages.meu-perfil`, do `->myProfile(slug: 'meu-perfil')` em
   `AppPanelProvider.php:271`), com fallback para a URL do painel quando a rota não existe. Rota
   nomeada e não string literal, como manda a guideline do Boost.

`recusar(string $mensagem): RedirectResponse` (privado):
`Notification::make()->title($mensagem)->danger()->send();` e
`return redirect()->to(Filament::getPanel('app')->getLoginUrl());`
Nunca autentica, nunca cria nada, e **nunca** diz qual barreira reprovou além do necessário.

**Logs** — todos em `Log::channel('autenticacao')`, e-mail sempre `Str::mask(..., '*', 3)`,
**nenhum** carregando `client_secret`, token de acesso ou refresh token:

| Ponto | Nível | Mensagem |
|---|---|---|
| falha ao obter o usuário | `warning` | `[LoginComGoogleController@retorno] Falha ao obter o usuário no Google \| ip: {ip}` + `['exception' => $e, 'motivo' => 'falha_no_provedor', 'ip' => …]` |
| e-mail não verificado | `warning` | `[LoginComGoogleController@retorno] Recusado: e-mail não verificado no Google \| email: {mascarado}` + `['motivo' => 'email_nao_verificado', 'email' => mascarado]` |
| e-mail ausente | `warning` | `[LoginComGoogleController@retorno] Recusado: provedor não devolveu e-mail \| ip: {ip}` + `['motivo' => 'email_ausente']` |
| conta inexistente, registro fechado | `warning` | `[LoginComGoogleController@retorno] Recusado: não há conta e o registro está fechado \| email: {mascarado}` + `['motivo' => 'conta_inexistente_registro_fechado', 'email' => mascarado]` |
| conta criada | `info` | `[LoginComGoogleController@retorno] Conta criada por login social \| user: {id} - email: {mascarado}` + `['user_id' => …, 'email' => mascarado, 'motivo' => 'conta_criada_por_login_social']` |
| autenticado | `info` | `[LoginComGoogleController@retorno] Autenticado pelo Google \| user: {id} - email: {mascarado}` + `['user_id' => …, 'email' => mascarado, 'conta_nova' => bool, 'provedor' => 'google']` |

### 6. As duas rotas em `routes/web.php`

> Skills: `laravel-best-practices`

- **Path**: `routes/web.php`

```php
Route::middleware('throttle:10,1')
    ->prefix('auth/google')
    ->name('auth.google.')
    ->group(function (): void {
        Route::get('redirect', [LoginComGoogleController::class, 'redirecionar'])->name('redirect');
        Route::get('callback', [LoginComGoogleController::class, 'retorno'])->name('callback');
    });
```

- O path do callback fica **exatamente** `/auth/google/callback` (RQ-05) — o mesmo valor de
  `config('services.google.redirect')`.
- `throttle:10,1`: superfície pública que dispara chamada HTTP externa. Dez por minuto por IP é
  folgado para uma pessoa e apertado para um script.
- Comentário de bloco: por que as rotas existem **sempre** (a rota tem de existir para o
  `route()` do blade e para o `redirect` da config resolver) e por que o `abort_unless` no
  controller é o que as tira do ar quando o interruptor está desligado.
- **Logs**: nenhum (roteamento).

### 7. Registrar os dois render hooks no `KitServiceProvider`

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/KitServiceProvider.php`
- `boot()` ganha `$this->configureTelaDeLogin();`
- Método novo:

```php
protected function configureTelaDeLogin(): void
{
    FilamentView::registerRenderHook(
        PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
        fn (): View => view('filament.auth.botao-google'),
    );

    FilamentView::registerRenderHook(
        PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
        fn (): View => view('filament.auth.rodape-login'),
    );
}
```

Por que assim, e o que cada escolha compra:

- **`FilamentView::registerRenderHook()` e não `$panel->renderHook()`**: sem `$scopes` o hook cai
  no escopo `''` (`vendor/filament/support/src/View/ViewManager.php:32-34`), que o `renderHook()`
  renderiza em **qualquer** escopo (`:93-96`). Uma registração cobre os três painéis; pelo painel
  seriam três blocos idênticos em três providers — o defeito histórico do kit nessa área é
  configurar um painel e esquecer os outros dois (é o que o docblock de
  `tests/Kit/TelasDeAutenticacaoTest.php:76-78` diz).
- **`AUTH_LOGIN_FORM_AFTER`**: é o hook que a `content()` da tela de login do Filament emite
  **depois** do formulário (`vendor/filament/filament/src/Auth/Pages/Login.php:465`), e é o que
  RQ-03 pede. Global é seguro porque a chave só é emitida ali.
- **Dois hooks e não um blade com dois blocos**: as duas superfícies têm condições
  independentes — botão depende das credenciais, rodapé do texto. A ordem de renderização é a de
  registro (`ViewManager::renderHook()` percorre o array), então botão vem antes do rodapé.
- **`FOOTER` foi recusado** para o rodapé: o layout do Auth Designer o renderiza
  (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:63`),
  mas o layout **de painel** também — o rodapé apareceria em toda tela autenticada dos três
  painéis. Ver ADR-05.
- **Logs**: nenhum. Registro de hook é declaração, e logar em `boot()` grava uma linha por request.

### 8. Blade do botão

> Skills: `tailwindcss-development`

- **Path**: `resources/views/filament/auth/botao-google.blade.php` (novo)
- Estrutura:

```blade
@php
    $disponivel = \App\Support\ConfiguracaoDoLogin::googleDisponivel();
@endphp

@if ($disponivel)
    <div class="fi-login-social" style="margin-top:1.5rem">
        {{-- divisor, rótulo "ou" --}}
        <x-filament::button tag="a" :href="route('auth.google.redirect')" color="gray" size="lg" class="w-full">
            {{-- SVG do G do Google, inline, 4 cores --}}
            Entrar com Google
        </x-filament::button>
    </div>
@endif
```

Decisões:
- **SVG inline** e não pacote de ícones: Heroicons não tem logo de marca, e o kit tem
  `blade-ui-kit/blade-icons` + `blade-heroicons` — nenhum dos dois traz o G. Acrescentar
  `blade-icons`-de-marcas por um ícone é o degrau errado da escada. Ver ADR-04.
- **`<x-filament::button>`** e não `<a class="...">` com utilitárias Tailwind: a CSS pré-compilada
  do Filament 5 carrega quase só as classes `fi-*` — é a armadilha que
  `.ai/rules/filament.md` documenta para os cartões de hub. O componente do Filament sai vestido e
  com tema claro/escuro de graça.
- **`aria-label`** no botão, porque `.ai/rules/testes-browser.md` diz que o kit não tem
  `data-testid` e o seletor disponível é `aria-label` ou texto.
- **`style` inline** para o espaçamento e o divisor, em vez de uma regra nova em
  `resources/css/filament/kit.css`: três propriedades não justificam um arquivo de CSS mais um
  `php artisan filament:assets` no fluxo de quem instala o kit. O divisor usa `currentColor` com
  `opacity`, o que segue o tema sem uma variável de cor escrita à mão.
  Marcar com `{{-- ponytail: --}}` **em português por extenso, sem citar diretiva** — comentário
  Blade não protege diretiva (`.ai/rules/views.md`).
- **Logs**: nenhum (view).

### 9. Blade do rodapé

> Skills: `tailwindcss-development`

- **Path**: `resources/views/filament/auth/rodape-login.blade.php` (novo)

```blade
@php
    $rodape = \App\Support\ConfiguracaoDoLogin::rodapeDoLogin();
@endphp

@if ($rodape !== null)
    <div class="fi-login-rodape" style="margin-top:1.5rem;text-align:center;font-size:.75rem;color:inherit;opacity:.65">
        {{ $rodape }}
    </div>
@endif
```

- **`{{ }}` e nunca `{!! !!}`**: o texto vem da tela de Settings, que é campo editável, e a tela
  de login é **pública e não autenticada**. HTML cru ali é XSS armazenado servido a quem nem
  entrou. Decisão de segurança, não de estilo — ADR-09, e CT-16 é o caso.
- **Logs**: nenhum (view).

### 10. Chaves novas no `.env.example`

> Skills: —

- **Path**: `.env.example`
- Bloco novo depois de `KIT_HUB` e antes de `--- Convite de usuário ---`:

```dotenv
# --- Login social (laravel/socialite) --------------------------------------
# Desligado por default. Ligar aqui NÃO põe o botão no ar sozinho: as três
# chaves de GOOGLE_* abaixo também precisam estar preenchidas, e com o
# interruptor desligado as rotas /auth/google/* respondem 404.
# O login com Google AUTENTICA quem já tem conta; ele não cria conta enquanto
# o registro aberto estiver desligado, porque o convite continua sendo a única
# porta de entrada do kit.
KIT_SOCIALITE_GOOGLE=false

# Credenciais do OAuth do Google (console.cloud.google.com). O redirect fica em
# config/services.php e é /auth/google/callback — cadastre esse mesmo caminho,
# absoluto, no console do Google. GOOGLE_CLIENT_SECRET é SEGREDO: não vai para
# log, nem para tela, nem para o repositório.
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

# Rodapé da tela de login dos três painéis. Vazio = sem rodapé. TEXTO, não HTML:
# a tela de login é pública, e o valor é escapado ao renderizar.
KIT_LOGIN_RODAPE=
```

- **Logs**: nenhum.

### 11. Documentação nos dois READMEs

> Skills: —

- **Paths**: `README.md` e `README.en.md`
- Seção nova "Login social (Google)" cobrindo: como obter as credenciais no console do Google;
  qual URI de redirecionamento cadastrar; as quatro chaves do `.env`; que o default é desligado;
  que o botão exige as três credenciais **e** o interruptor; que o login autentica quem já tem
  conta e não cria conta enquanto o registro aberto estiver desligado; o que acontece com e-mail
  não verificado no Google; que o 2FA continua obrigatório; onde fica o rodapé e que ele é texto
  escapado; e como acrescentar um segundo provedor no futuro.
- `README.en.md` é a tradução do mesmo conteúdo, na mesma posição.
- **Logs**: nenhum.

### 12. Testes

> Skills: `pest-testing`

- **Path**: `tests/Kit/LoginSocialGoogleTest.php` — os CT do `04-casos-de-teste.md`
- **Path**: `tests/Browser/LoginSocialGoogleTest.php` — os CT-B do `05-casos-de-teste-browser.md`
- **Nenhum teste sai para a rede.** `Socialite::fake('google', User::fake([...]))` em todos os
  casos de callback — é a API oficial de teste do pacote
  (`vendor/laravel/socialite/src/Socialite.php:39-52`, `vendor/laravel/socialite/src/Two/User.php:43-62`).
- Ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> 1. Reutilizar antes de criar: o channel `autenticacao` já existe; o `Str::mask` já é a régua;
>    o `<x-filament::button>` já está vestido; a trilha de acesso já escuta o evento `Login`.
> 2. Stdlib/framework antes de código custom: `filter_var`, `filled`, `Str::password`,
>    `Socialite::fake`.
> 3. Feature nativa antes de dependência: render hook do Filament em vez de sobrescrever três
>    telas; SVG inline em vez de pacote de ícones.
> 4. Uma linha quando possível.
> 5. Nada de abstração de provedor com uma implementação (ADR-06).
>
> Atalhos deliberados marcados com comentário `ponytail:`.
> Depois de implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **O que NÃO se simplifica**: o `abort_unless` das rotas, a checagem de `email_verified`, o
> escape do rodapé, o mascaramento do e-mail no log e a ausência do segredo em toda saída.

## Mapeamentos

Do usuário do Google para o usuário do kit — o que é usado e o que é deliberadamente ignorado:

| Campo do provedor | Origem no Socialite | Destino no kit | Observação |
|---|---|---|---|
| `email` | `$user->getEmail()` | chave de busca em `users.email` | normalizado (`mb_strtolower(trim())`) nos dois lados |
| `email_verified` / `verified_email` | `$user->getRaw()` | — (só barreira) | `GoogleProvider.php:91` popula as duas |
| `name` | `$user->getName()` | `users.name`, **só na criação** | nunca sobrescreve o nome de conta existente |
| `sub` / `id` | `$user->getId()` | — | **não é gravado**; nenhuma coluna `google_id`. Ver ADR-07 |
| `picture` | `$user->getAvatar()` | — | o kit guarda avatar em `avatar_url` no disco `public`; puxar imagem de terceiro no callback é chamada de rede num fluxo que o usuário está esperando |
| `token` / `refreshToken` | propriedades públicas | — | **não são gravados**. O kit não chama API do Google em nome do usuário; guardar token é passivo de segurança sem uso |

## Testes

> Ver `04-casos-de-teste.md` para os cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários que só o navegador prova.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` — 0 erros
- [ ] `php artisan test --testsuite=Kit --filter=LoginSocialGoogle --compact`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — a base tem **662** passando; não cair
- [ ] `composer test:browser`
- [ ] `composer filament:check` (filacheck)
- [ ] `git diff --stat composer.json composer.lock` — os dois no mesmo commit

## Commits

- `:sparkles: feat(login-social): entrar com Google atrás de interruptor desligado por default`
- `:sparkles: feat(login): rodape da tela de login vindo da configuracao`
- `:white_check_mark: test(login-social): casos do callback, das barreiras e da tela`
- `:memo: docs(readme): secao de login social nos dois READMEs`
- `:memo: docs(wiki): wiki da feature login-social-google`
