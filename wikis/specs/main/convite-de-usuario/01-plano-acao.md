# Plano de Ação — Convite de usuário

## Objetivo

Dar ao kit a única porta de entrada que ele não tem: **alguém de fora virar usuário**.
Hoje só existe cadastro por dentro — um administrador abre `/admin/users`, preenche nome,
e-mail e senha por outra pessoa, e entrega a senha por um canal qualquer. O painel `/app`
não tem registro: `AppPanelProvider::panel()` declara `->login()` e `->passwordReset()` e
para aí (`app/Providers/Filament/AppPanelProvider.php:52-57`).

Este plano introduz o **convite**: um administrador escolhe e-mail, papel e organização; o
kit envia um link com um token de uso único; a pessoa abre o link, define a própria senha e
nasce dentro da organização certa, com o papel certo, no contexto de papel certo. Sem
convite válido não há cadastro — a tela de registro nunca vira porta aberta.

A feature **depende** de `perfil-e-acesso-ao-painel`: `roles.painel` já existe e
`User::canAccessPanel()` já lê o papel. Sem isso, "convidar com papel" não daria acesso a
nada, e o convite seria só um usuário a mais sem porta.

## Contexto

### O que existe

| Peça | Estado |
| --- | --- |
| Registro no painel `/app` | **não existe** — `AppPanelProvider.php:52-57` só liga `login()` e `passwordReset()` |
| Página de registro do Filament | existe e é completa: rate limit, transação, evento, auto-login (`vendor/filament/filament/src/Auth/Pages/Register.php:70-113`) |
| Página de registro estilizada | existe no Auth Designer (`vendor/caresome/filament-auth-designer/src/Pages/Auth/Register.php:10`), estende a do Filament |
| Papel → painel | entregue pela wiki `perfil-e-acesso-ao-painel` (`roles.painel`) |
| Contexto de papel | `Tenant::CONTEXTO_GLOBAL` (`app/Models/Tenant.php:62`) |
| Channel de log | `autenticacao`, já declarado e ainda sem uso (`config/logging.php:101-107`) |
| `app/Notifications` | **não existe** — diretório novo |

### A decisão que atravessa tudo

**A tela de aceite é a página de registro nativa do Filament.** Não há rota nova, nem
controller, nem Blade. O que o kit acrescenta é uma guarda no `mount()`: sem token válido
na query string, a página recusa. Registro e convite passam a ser a mesma coisa — não
existe um sem o outro. Ver ADR-01.

O token vai **hasheado** para o banco (`hash('sha256', $token)`); o token em claro só
existe no link do e-mail. Ver ADR-02, que é a ADR de segurança desta feature.

### Fronteira com as wikis irmãs

O Resource de convites **no painel `/app`**, para o administrador da organização convidar
gente da própria organização sem passar pelo `/admin`, é escopo de `admin-da-organizacao`.
Aqui o convite nasce só no `/admin`, onde já existe a tela de organizações e a de usuários.
O model, a migration, a notification e a página de aceite construídos aqui são a fundação
que aquela wiki reusa — ela acrescenta um Resource, não um segundo fluxo.

## Análise dos Arquivos Existentes

### `vendor/filament/filament/src/Auth/Pages/Register.php`

A página que será estendida. O que já é resolvido por ela, e portanto **não** se escreve:

| Linha | O que faz |
| --- | --- |
| `:57-68` | `mount()` — redireciona quem já está autenticado, roda os hooks e preenche o form |
| `:73` | `$this->rateLimit(2)` — duas tentativas por IP (trait `WithRateLimiting`) |
| `:80, :129-151` | segundo limite, por e-mail: `filament-register:{sha1(email)}`, 2 tentativas |
| `:84-102` | tudo dentro de `wrapInDatabaseTransaction()` — usuário e vínculos, ou nada |
| `:91` | `mutateFormDataBeforeRegister($data)` — o gancho onde o e-mail do convite se impõe |
| `:95` | `handleRegistration($data)` — o gancho onde o aceite entra |
| `:104` | `event(new Registered($user))` |
| `:106, :161-181` | e-mail de verificação — **não dispara aqui**: só para `MustVerifyEmail`, e `App\Models\User` não implementa (`app/Models/User.php:26`) |
| `:108-110` | `Filament::auth()->login($user)` + `session()->regenerate()` — auto-login após o aceite |
| `:156-159` | `handleRegistration()` default: `getUserModel()::create($data)` |
| `:216` | e-mail com `->unique($this->getUserModel())` |

O aceite reaproveita **todo** esse fluxo. O kit escreve dois métodos: a guarda no `mount()`
e o `handleRegistration()`.

### `vendor/filament/filament/src/Panel/Concerns/HasAuth.php`

- `registration(string|Closure|array|null $action = Register::class): static` (`:255-260`) —
  grava `registrationRouteAction`.
- `hasRegistration(): bool` (`:635-638`) — `filled($this->getRegistrationRouteAction())`.
- `getRegistrationRouteSlug(): string` (`:579-582`) — default `register` (`:72`).
- `getRegistrationUrl()` (`:392-399`) → nome de rota `auth.register`.

### `vendor/filament/filament/routes/web.php` — onde a rota nasce

```php
if ($panel->hasRegistration()) {                                    // :54
    Route::get($panel->getRegistrationRouteSlug(), $panel->getRegistrationRouteAction())
        ->name('register');                                          // :55-56
}
```

Três fatos que decidem o plano:

1. O bloco está **dentro** do `->prefix($panel->getPath())` (`:30`) e **fora** do
   `Route::middleware($panel->getAuthMiddleware())` (`:60`) — a rota é pública, como tem de
   ser.
2. Está **fora** do grupo do tenant, que só começa em `:119-137` (`$routeGroup` com o
   prefixo `{tenant}`). **Com a tenancy ligada a URL continua `/app/register`**, sem slug de
   organização. É o que torna o link do convite auto-suficiente: a organização vem do
   token, não da URL.
3. O nome final é `filament.app.auth.register` — `Route::name('filament.')` (`:15`) +
   `"{$panelId}."` (`:29`) + `Route::name('auth.')` (`:36`) + `'register'` (`:56`).

### `vendor/caresome/filament-auth-designer/`

- **Existe** uma página `Register` própria: `Caresome\FilamentAuthDesigner\Pages\Auth\Register`
  (`src/Pages/Auth/Register.php:10`), que estende a do Filament e usa
  `HasAuthDesignerLayout`. Chave de config: `registration` (`:18`).
- Ela **declara** `protected static string $layout` (`src/Pages/Auth/Register.php:14`). Isso
  contém o vazamento descrito em `.ai/rules/auth.md`: `HasAuthDesignerLayout::boot()` faz
  `static::$layout = ...` (`src/Concerns/HasAuthDesignerLayout.php:17`), e a redeclaração dá
  storage próprio à hierarquia. Estender essa classe já herda a proteção — **mesmo assim a
  subclasse redeclara**, porque a regra do kit é essa e o par de testes a cobra.
- `AuthDesignerPlugin::register()` chama `$panel->registration(...)`, **mas só se o plugin
  tiver recebido `->registration(...)`**:

```php
if ($this->hasRegistration()) {                                     // AuthDesignerPlugin.php:33
    $panel->registration($this->getRegistrationPageClass());        // :34
}
```

  A flag nasce `false` (`src/Concerns/HasPages.php:20`) e só vira `true` em
  `registration(?Closure $configure = null)` (`src/Concerns/HasPages.php:44-46`). Os três
  painéis do kit hoje chamam só `->login(...)`, então o plugin nunca registra registro.
- A classe da página customizada se aponta por `AuthPageConfig::usingPage(string $pageClass)`
  (`src/Data/AuthPageConfig.php:57`), lida em `getRegistrationPageClass()`
  (`src/Concerns/HasPages.php:119-124`).

**A armadilha silenciosa**: registrar por `$panel->registration(...)` direto deixa a tela sem
mídia e sem alternador de tema, em silêncio. O porquê, com as linhas do vendor, está em
ADR-06 — por isso o registro passa pelo plugin (passo 6).

### `vendor/filament/filament/src/Auth/Pages/Login.php` — o efeito colateral

`Login::getSubheading()` (`:445-455`, ação em `:367-372`) exibe **"Cadastre-se"** sempre que
`hasRegistration()` é true (`:451`). O link levaria a uma página que sempre recusa quem não
tem token — o que `wikis/convencoes.md:84` chama de bug, não de detalhe. O passo 5 o remove.

### `app/Models/User.php`

- `canAccessPanel()` (`:71-82`) — já reescrito pela wiki `perfil-e-acesso-ao-painel` para
  ler `roles.painel`. O aceite tem de atribuir o papel no contexto que **esse** método
  exige, senão o usuário nasce sem porta.
- `tenants()` (`:136-139`) — `belongsToMany`, pivot `tenant_user`. É o vínculo que o aceite
  cria.
- `canAccessTenant()` (`:167-187`) — a fronteira depois do painel; loga negativa no channel
  `tenancy`.
- `$fillable` (`:39-44`) — `name`, `email`, `password`, `avatar_url`. `handleRegistration()`
  do Filament faz `create($data)`, então os três campos do form passam.
- **Não implementa `MustVerifyEmail`** (`:26`): nenhum e-mail de verificação é disparado
  após o aceite (`Register.php:163-165`). É o correto — o convite chegou no endereço, a
  posse do e-mail já está provada.

### `app/Models/Tenant.php`

`CONTEXTO_GLOBAL = 0` (`:62`), com a explicação em `:47-61`: `model_has_roles.team_id` é
NOT NULL, então toda atribuição pertence a um contexto. É o valor que o aceite usa para
papel de painel sem tenancy.

### `app/Filament/Pages/Auth/TelaBloqueio.php`

Dois padrões a reusar, não a reinventar:

- a redeclaração de `$layout` (`:40`) com a nota do porquê (`:31-39`);
- **`sairPara()` (`:99-102`)**: `throw new HttpResponseException(new RedirectResponse($url))`.
  O comentário em `:74-85` explica: `redirect()` solto dentro de `mount()` de uma página
  Livewire devolve o Redirector do Livewire onde o Laravel espera código HTTP — 500. O
  próprio `Register::mount()` do Filament usa `redirect()` solto (`Register.php:60`); a
  guarda do convite **não** vai copiar esse jeito.

### `app/Console/Commands/KitUpdate.php`

`CAMINHOS_DO_KIT` (`:66-115`) lista `app/Models` **arquivo a arquivo** (`:79-82`),
justamente para não colidir com os models do usuário. Dois caminhos novos precisam entrar:
`app/Models/Convite.php` e `app/Notifications`. O resto já está coberto (`app/Filament`
`:74`, `database/migrations` `:91`, `database/seeders` `:92`, `database/factories` `:90`,
`config/kit.php` `:86`, `tests/Kit` `:101`, `tests/Tenancy` `:102`).

O comentário em `:57-62` é literal: "Arquivo do kit fora daqui simplesmente não chega a
quem já instalou — a feature existe no repositório e é invisível na prática."

## Autorização

- **Policies**: **nenhuma escrita à mão.** `ShieldPermissionsSeeder` roda
  `shield:generate --all`, que gera `ConvitePolicy` e as permissions do Resource novo. A
  policy do Shield delega a `$authUser->can('Ação:Convite')`, como as quatro que já existem.
  Escrever uma policy própria seria duplicar o que o gerador entrega.
- **Gates**: nenhum novo.
- **Middleware**: nenhum novo. A rota de aceite usa o middleware do painel `app`
  (`AppPanelProvider.php:161-171`) e **não** o `authMiddleware` (`routes/web.php:54-57` está
  fora do grupo de `:60`).
- **Guards**: um só, `web`, nos três painéis (ADR-02 de `perfil-e-acesso-ao-painel`).
- **A fronteira de quem convida**: o Resource vive no `/admin`, atrás de
  `canAccessPanel('admin')` + as permissions `*:Convite`. `PapeisSeeder` entrega essas
  permissions ao papel `admin` porque o Resource está em
  `app/Filament/Admin/Resources/Convites` — o mapa de `App\Support\Paineis` o classifica no
  painel `admin` sozinho.
- **A fronteira de quem aceita**: o token. Não há usuário autenticado a autorizar; a
  autorização **é** a posse do token válido, não expirado e não usado. Ver ADR-02.

## Rotas

| Método | URI | Name | Middleware | Origem |
| --- | --- | --- | --- | --- |
| GET | `/app/register?token={token}` | `filament.app.auth.register` | middleware do painel `app`, **sem** auth | nativa do Filament (`vendor/filament/filament/routes/web.php:54-57`) |
| GET | `/admin/convites` | `filament.admin.resources.convites.index` | painel `admin` + auth | Resource novo |
| GET | `/admin/convites/create` | `filament.admin.resources.convites.create` | painel `admin` + auth | Resource novo |

**Nenhuma rota escrita à mão. `routes/web.php` não é tocado** — ele tem sete linhas e só a
`/` (`routes/web.php:5-7`).

Com a tenancy ligada a URL de aceite continua `/app/register` (sem `{tenant}`), pelo motivo
provado na análise de `routes/web.php` acima. Não há página de edição de convite:
`getPages()` devolve só `index` e `create` (ADR-04).

## Variáveis de Ambiente

| Key | Default | Onde | Descrição |
| --- | --- | --- | --- |
| `KIT_CONVITE_VALIDADE_DIAS` | `7` | `config/kit.php` → `kit.convites.validade_em_dias` | dias entre o envio e a expiração |

Nenhuma outra chave nova. Duas **existentes** mudam de importância e precisam de nota no
README:

| Key | Valor em `.env.example` | O que muda |
| --- | --- | --- |
| `MAIL_MAILER` | `log` (`.env.example:56`) | o convite é escrito em `storage/logs/laravel.log` e **não sai para o mundo**. Instalação nova convida e ninguém recebe. É o default do Laravel (`config/mail.php:17`) e serve para desenvolver; produção exige SMTP configurado. |
| `QUEUE_CONNECTION` | `database` (`.env.example:42`) | ver "Jobs / Queues" abaixo |

## Eventos / Listeners / Observers

- **Emitidos**: `Filament\Auth\Events\Registered` no aceite — disparado pela página nativa
  (`Register.php:104`), de graça.
- **Listeners**: **nenhum.** Não há segundo consumidor do evento. Um listener para
  "marcar o convite como aceito" seria indireção pura: quem cria o usuário é
  `Convite::aceitar()`, que carimba `aceito_em` na mesma transação.
- **Observers**: **nenhum.** O envio no momento da criação cabe no hook `afterCreate()` da
  página do Resource, que é onde o kit já põe esse tipo de coisa. Um Observer de model
  dispararia e-mail também a partir de seeder, teste e tinker — efeito colateral escondido
  onde ninguém procura.

## Jobs / Queues

- **Nenhum Job escrito.** O envio é uma `Notification` com `ShouldQueue`; a fila é
  responsabilidade da Notification, não de um Job intermediário (ADR-05).
- Connection: a do projeto. Sem `->onQueue()` fixo — enfileirar numa fila nomeada obrigaria
  o kit a documentar um worker extra.
- **Sem worker, o convite não sai**: o kit roda `QUEUE_CONNECTION=database` (`sync` é só o
  `phpunit.xml`). O comportamento em cada modo está na tabela de ADR-05, e a consequência
  para o README está em "Variáveis de Ambiente" acima.

## Impacto em Features Existentes

| O que | Impacto |
| --- | --- |
| Tela de login do `/app` | **Ganha um link "Cadastre-se"** assim que `hasRegistration()` vira true (`Login.php:445-455`). Removido no passo 5 — sem isso, affordance para uma tela que sempre recusa. |
| Painel `/app` | Ganha uma rota **pública** nova, `/app/register`. Era um painel 100% autenticado. A guarda do `mount()` é a única coisa entre essa rota e um cadastro aberto: é o ponto mais sensível da feature e tem três CTs (CT-02, CT-03, CT-04). |
| `tests/Kit/BloqueioDeSessaoTest.php` | O par de casos de layout (`.ai/rules/auth.md`) ganha um irmão para a tela de aceite. O caso existente **não** muda. |
| `PapeisSeeder` | A matriz de `admin` cresce com as permissions `*:Convite`, automaticamente — `Paineis::permissoes('admin')` lê do Shield, não de uma lista. Nada a editar. |
| `kit:update` | Dois caminhos novos na constante, senão `Convite` e a Notification não chegam a quem instalou (passo 10). |
| Auditoria | `Convite` entra na trilha de `/infra/audits` via `AuditsFillables`. Como `token` fica **fora** do `$fillable`, o hash nunca aparece na trilha — e a revogação (um `DELETE`) fica registrada de graça. |
| `/admin` | Um item novo no grupo "Administração". |

## Rollback

- **Migration down**: `Schema::dropIfExists('convites')`. Tabela nova, sem escrita em tabela
  existente — nada mais a desfazer. Usuários criados por convite permanecem: são usuários
  comuns, indistinguíveis dos criados à mão.
- **Desligar a porta sem rollback de dados**: tirar `->registration(...)` do
  `AuthDesignerPlugin` no `AppPanelProvider`. `hasRegistration()` volta a `false`
  (`HasAuth.php:635-638`), a rota deixa de ser registrada (`routes/web.php:54`) e o link
  some do login (`Login.php:451`). Uma linha, e a superfície pública desaparece.
- **Sem feature flag em config.** Uma chave que liga e desliga cadastro público é uma porta
  com interruptor — o mesmo argumento de ADR-07 da wiki irmã. Quem quiser desligar comenta a
  linha do provider, e isso aparece no diff.
- **Reversão de dados**: nenhuma. Convite não altera registro existente; só cria.

## Dependências

**Nenhum pacote novo.** Tudo já instalado:

| Peça | Origem |
| --- | --- |
| Página de registro | `filament/filament` 5.7.6 |
| Página de registro estilizada | `caresome/filament-auth-designer` |
| `Notification` + `toMail()` + `ShouldQueue` | Laravel |
| `Str::random()`, `hash()`, `Str::mask()` | stdlib do PHP / Laravel |
| Rate limit da tela | `danharrin/livewire-rate-limiting` (transitiva do Filament, usada em `Register.php:45`) |
| Permissions do Resource | `bezhansalleh/filament-shield` |

## Riscos

| Risco | Mitigação |
| --- | --- |
| A guarda do `mount()` cair num refactor e a tela virar cadastro aberto | CT-02, CT-03 e CT-04 batem nos três motivos de recusa. É a razão de os três existirem em vez de um. |
| Papel atribuído no contexto errado → usuário aceita e leva 403 | CT-05 (sem tenancy) e CT-11/CT-12 (com tenancy) travam o contexto nos dois modos. |
| Registro ligado sem passar pelo plugin → tela sem mídia, em silêncio | `AuthDesignerConfigRepository.php:80` cai em `new AuthPageConfig` sem erro. Passo 6 registra pelo plugin; CT-13 assere `fi-auth-layout`. |
| `$layout` vazando para toda página Filament | A classe pai já redeclara (`caresome/.../Register.php:14`) e a nossa redeclara de novo. CT-13 é o par exigido por `.ai/rules/auth.md`. |
| Convite sai, mas o worker não roda (`QUEUE_CONNECTION=database`) | Documentado acima e no README; o `/infra` já mostra a fila. CT-01 roda em `sync` e prova o disparo, não a entrega. |
| Token em claro no payload da tabela `jobs` enquanto a notificação espera o worker | Consequência aceita e registrada em ADR-05. A linha some quando o job completa; quem lê `jobs` já lê `convites`. Projeto com exigência dura tira o `ShouldQueue`. |
| Papel do convite apagado antes do aceite | FK `role_id` sem `cascadeOnDelete` — o banco recusa apagar o papel e o erro aparece na hora, em vez de o convite aceitar com papel nulo. |
| `MAIL_MAILER=log` numa instalação nova | Nota no README e no `helperText` do formulário. |

## Channel de Log da Feature

**Nenhum channel novo.** `Log::channel('autenticacao')` — o canal já existe em
`config/logging.php:101-107` (driver `daily`, 14 dias, `replace_placeholders`) e a wiki
`perfil-e-acesso-ao-painel` acabou de adotá-lo para as negativas de painel. Convite é evento
de autenticação e de concessão de acesso: mesmo assunto, mesmo arquivo.

O cabeçalho da seção de canais é explícito (`config/logging.php:76-83`): "um por camada
transversal" e **"Regra LGPD: nunca logar conteúdo de prompt/notificação em claro;
identificadores sempre mascarados"**. Daqui saem duas regras não negociáveis:

1. **O token nunca vai para o log.** Nem em claro, nem hasheado, nem em prefixo — prefixo de
   segredo é segredo parcial. Quando o convite é conhecido, o log carrega `convite_id`; é o
   suficiente para correlacionar sem carregar credencial.
2. **E-mail sempre mascarado**, com `Str::mask($email, '*', 3)` — stdlib do Laravel, sem
   helper novo. `fulano@example.com` vira `ful**************`.

O que se loga: **envio, recusa e aceite**. Isto é, os dois lados de uma mudança de poder e
toda negativa. Abrir a tela com token válido não gera log — é o caminho feliz.

## Estrutura de Implementação

### 1. Tabela `convites`

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_13_000002_create_convites_table.php`
- O prefixo é de data e vem **depois** de `2026_08_13_000001` (a coluna `roles.painel` da
  wiki irmã) e de `2026_08_12_164859_create_permission_tables`, porque `role_id` referencia
  `roles`. `tenants` nasce em `0001_01_01_000020` nos dois modos, então a FK nullable é
  segura com ou sem tenancy.

```php
Schema::create('convites', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->string('email')->index();

    // sha256 em hex: 64 chars, determinístico, indexável. Ver ADR-02.
    // Nullable: quem grava é `enviar()`, depois do `create()` da tela.
    $table->string('token', 64)->nullable()->unique();

    $table->foreignId('role_id')
        ->constrained(config('permission.table_names.roles', 'roles'));

    // Só preenchida com a tenancy ligada. A tabela `tenants` existe nos dois modos.
    $table->foreignId('tenant_id')->nullable()->constrained();

    // Quem convidou. nullOnDelete: apagar o admin não apaga o histórico do convite.
    $table->foreignId('convidado_por_id')->nullable()
        ->constrained('users')->nullOnDelete();

    // Nullable porque só `enviar()` sabe o prazo: a linha nasce no `create()` do
    // Filament e ganha token e validade no `afterCreate()`. NULL falha fechado —
    // `expira_em > now()` não casa com NULL, então convite sem envio não vale.
    $table->timestamp('expira_em')->nullable();
    $table->timestamp('aceito_em')->nullable();

    $table->timestamps();
});
```

- `uuid` + `unique`: convenção de toda tabela nova (`wikis/convencoes.md:7-28`).
- `token` é `unique` — colisão de `Str::random(64)` é fantasia, mas o índice único é o que
  torna a busca por token um lookup de índice, e é ele que serve à validação. Também
  nullable, pela mesma razão de `expira_em`.
- **Sem coluna de status.** Pendente / aceito / expirado se derivam de `aceito_em` e
  `expira_em`. Uma coluna a mais seria um terceiro estado a manter em sincronia com dois
  fatos que já estão no banco.
- **Sem `revogado_em`.** Revogar é apagar a linha (ADR-04); a trilha fica na auditoria.
- `down()`: `Schema::dropIfExists('convites')`.
- **Logs**: nenhum. Migration não loga.

### 2. `App\Models\Convite`

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/Convite.php`

```php
class Convite extends Model implements Auditable
{
    use AuditsFillables;
    use HasFactory;
    use TemUuid;

    /** `uuid` e `token` ficam FORA: o trait cuida do uuid, e o token é segredo. */
    protected $fillable = [
        'email',
        'role_id',
        'tenant_id',
        'convidado_por_id',
        'expira_em',
        'aceito_em',
    ];

    protected $hidden = ['token'];
}
```

- **`token` fora do `$fillable` é decisão de segurança, não estilo.**
  `AuditsFillables::getAuditInclude()` devolve o `$fillable`
  (`wikis/convencoes.md:30-41`), então o hash nunca entra na trilha de auditoria de
  `/infra/audits`. Mesma razão do `uuid`.
- `casts()`: `expira_em` e `aceito_em` como `datetime`.
- Relações: `papel(): BelongsTo` (para `Spatie\Permission\Models\Role`, via
  `config('permission.models.role')`), `tenant(): BelongsTo`,
  `convidadoPor(): BelongsTo` (para `User`).

**2a. `enviar(): string` — gera o token e manda o e-mail**

```php
/**
 * Gera um token novo, invalida o anterior e envia o convite.
 *
 * Serve tanto ao primeiro envio quanto ao reenvio: reenviar é gerar de novo.
 * Devolve o token EM CLARO — que existe aqui, no e-mail, e em lugar nenhum
 * mais. Nunca logar, nunca guardar, nunca devolver numa resposta HTTP.
 */
public function enviar(): string
```

Lógica, em ordem:

1. `$token = Str::random(64);`
2. `$this->forceFill(['token' => hash('sha256', $token), 'expira_em' => now()->addDays((int) config('kit.convites.validade_em_dias', 7)), 'aceito_em' => null])->save();`
   — `forceFill` porque `token` está fora do `$fillable`, de propósito. Zerar `aceito_em` é
   o que faz o reenvio devolver o convite ao estado pendente.
3. `Notification::route('mail', $this->email)->notify(new ConviteDeAcesso($this, $token));`

- **`enviar()` é o método do reenvio.** Não existe `reenviar()`: reenviar **é** enviar de
  novo, e o token velho morre porque a coluna foi sobrescrita. Um segundo método seria o
  mesmo corpo com outro nome.
- **Logs**:

```php
Log::channel('autenticacao')->info(
    "[Convite@enviar] Convite enviado | convite: {$this->id} - email: ".Str::mask($this->email, '*', 3),
    [
        'convite_id'    => $this->id,
        'email'         => Str::mask($this->email, '*', 3),
        'role_id'       => $this->role_id,
        'papel'         => $this->papel?->name,
        'painel'        => $this->papel?->painel,
        'tenant_id'     => $this->tenant_id,
        'expira_em'     => $this->expira_em?->toIso8601String(),
        'convidado_por' => $this->convidado_por_id,
        'reenvio'       => $this->wasRecentlyCreated === false,
    ],
);
```

  Sem `token`, sem hash, sem prefixo.

**2b. `Convite::valido(?string $token): ?self` — a única porta**

```php
/**
 * O convite utilizável por este token, ou null.
 *
 * Um método só para os três motivos de recusa (inexistente, expirado, já
 * aceito) porque o chamador não deve poder distingui-los: a tela responde
 * igual nos três casos. Ver ADR-02.
 */
public static function valido(?string $token): ?self
{
    if (blank($token)) {
        return null;
    }

    return static::query()
        ->where('token', hash('sha256', $token))
        ->whereNull('aceito_em')
        ->where('expira_em', '>', now())
        ->first();
}
```

- **Logs**: nenhum aqui. Quem recusa é quem tem contexto de request — a página (passo 4).

**2c. `aceitar(array $dados): User` — o coração**

```php
/**
 * Cria o usuário, vincula a organização e atribui o papel NO CONTEXTO CERTO.
 *
 * Roda dentro da transação que Register::register() já abriu
 * (vendor/filament/filament/src/Auth/Pages/Register.php:84-102): se
 * qualquer passo falhar, não sobra usuário órfão nem convite meio-aceito.
 *
 * @param  array{name: string, password: string}  $dados  já validados e com a senha hasheada pelo form
 */
public function aceitar(array $dados): User
```

Lógica, em ordem:

1. **Guarda de e-mail já cadastrado** — fronteira de confiança, não se simplifica:

```php
if (User::query()->where('email', $this->email)->exists()) {
    Log::channel('autenticacao')->warning(
        "[Convite@aceitar] Aceite recusado, e-mail ja cadastrado | convite: {$this->id}",
        ['convite_id' => $this->id, 'email' => Str::mask($this->email, '*', 3), 'motivo' => 'email_ja_cadastrado'],
    );

    throw new RuntimeException('E-mail já cadastrado.');
}
```

   O e-mail pode ter virado usuário por outro caminho entre o convite e o clique. A coluna
   `users.email` é única, então o banco também recusa — mas com uma exceção de driver, que
   não vira mensagem legível.

2. `$user = User::create([...$dados, 'email' => $this->email]);` — **o e-mail vem do
   convite, sempre.** O que veio do formulário é descartado nesta linha; ver o passo 4c.

3. Vínculo da organização, só quando há uma: `$this->tenant_id && $user->tenants()->attach($this->tenant_id);`

4. **Papel no contexto certo** — o passo que decide se o usuário nasce com porta ou sem:

```php
/*
 * Painel sem tenancy (/admin, /infra) governa a instalação inteira, e
 * User::canAccessPanel() exige o papel no contexto global. Painel de
 * negócio (/app) exige o papel dentro da organização. Errar aqui cria um
 * usuário que entra e leva 403 — sem erro nenhum no caminho.
 */
$contexto = $this->papel->painel === 'app'
    ? $this->tenant_id
    : Tenant::CONTEXTO_GLOBAL;

$registrar = app(PermissionRegistrar::class);
$anterior  = $registrar->getPermissionsTeamId();

try {
    $registrar->setPermissionsTeamId($contexto ?? Tenant::CONTEXTO_GLOBAL);
    $user->assignRole($this->papel);
} finally {
    $registrar->setPermissionsTeamId($anterior);
}
```

   - `assignRole()`, nunca `sync()` na relação — a armadilha já registrada em
     `.ai/rules/filament.md:8-15`: o `sync()` só escreve as colunas da chave e estoura
     `NOT NULL constraint failed: model_has_roles.team_id`.
   - Sem `permission.teams`, `setPermissionsTeamId()` é inofensivo — o spatie ignora. Um
     caminho de código para os dois modos.
   - `$this->tenant_id` nulo com papel de painel `app` só acontece sem tenancy; o `??`
     cobre.

5. `$this->forceFill(['aceito_em' => now()])->save();` — **o uso único**. `Convite::valido()`
   já não devolve este convite.

6. **Logs**:

```php
Log::channel('autenticacao')->info(
    "[Convite@aceitar] Convite aceito | convite: {$this->id} - user: {$user->id}",
    [
        'convite_id'     => $this->id,
        'user_id'        => $user->id,
        'email'          => Str::mask($this->email, '*', 3),
        'papel'          => $this->papel->name,
        'painel'         => $this->papel->painel,
        'tenant_id'      => $this->tenant_id,
        'contexto_papel' => $contexto ?? Tenant::CONTEXTO_GLOBAL,
    ],
);
```

**O que NÃO se escreve neste passo** (escada do Ponytail, subida e anotada):

- Nenhuma `ConviteService` / `AceitadorDeConvite`. São três métodos que só mexem no próprio
  registro e nas relações dele: é model.
- Nenhum Enum de status. Ver passo 1.
- Nenhum `reenviar()`. Ver 2a.
- Nenhum evento `ConviteAceito`. Sem segundo consumidor.

### 3. `App\Notifications\ConviteDeAcesso`

> Skills: `laravel-best-practices`

- **Path**: `app/Notifications/ConviteDeAcesso.php` (**diretório novo** — entra em
  `CAMINHOS_DO_KIT` no passo 10)

```php
class ConviteDeAcesso extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Convite $convite,
        #[SensitiveParameter] public readonly string $token,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage;
}
```

- **`Notification`, não `Mailable`** (ADR-05): o destinatário ainda não é um `User`, então o
  envio é `Notification::route('mail', $email)->notify(...)` — a rota on-demand do Laravel.
- **Sem `SerializesModels`**: `Queueable` já basta, e o `Convite` é serializado por
  identificador pelo próprio Laravel. Se o convite for revogado antes de o worker rodar, o
  job falha com `ModelNotFoundException` — que é o comportamento certo: convite revogado não
  deve ser entregue.
- `toMail()`:
  - `->subject('Você foi convidado para o '.config('app.name'))`
  - `->greeting('Olá!')`
  - linha com o nome da organização, **só quando há uma**:
    `config('kit.tenancy.label')` + `$this->convite->tenant?->nome`
  - `->action('Aceitar convite', $this->url())`
  - linha de validade: `'Este convite expira em '.$this->convite->expira_em->format('d/m/Y H:i').'.'`
  - `->salutation(...)` em pt-BR — o default do Laravel é em inglês
    (`wikis/convencoes.md:117-120`).
- A URL:

```php
private function url(): string
{
    return Filament::getPanel('app')->route('auth.register', ['token' => $this->token]);
}
```

  `Panel::route()` (`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:104-107`)
  resolve `filament.app.auth.register`. **Não montar a URL à mão**: o slug vem de
  `getRegistrationRouteSlug()` (`HasAuth.php:579-582`) e o path do painel de `->path('app')`
  — duas coisas configuráveis que uma string literal dessincronizaria.

- **Logs**: **nenhum nesta classe.** Quem loga o envio é `Convite@enviar` (passo 2a), com o
  contexto todo. Logar aqui dentro seria a mesma informação num lugar onde o objeto `token`
  está em escopo — exatamente o lugar em que um `$context` descuidado vazaria o segredo.

### 4. `App\Filament\Pages\Auth\RegistroPorConvite`

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php`
- Estende `Caresome\FilamentAuthDesigner\Pages\Auth\Register`
  (`vendor/caresome/filament-auth-designer/src/Pages/Auth/Register.php:10`), que estende a do
  Filament e traz o layout.

**4a. `$layout` redeclarado**

```php
/**
 * A classe pai já declara a propriedade (caresome/.../Pages/Auth/Register.php:14),
 * então o vazamento de `HasAuthDesignerLayout::boot()` já está contido lá. A
 * redeclaração aqui é a regra do kit (.ai/rules/auth.md) e o que o par de testes
 * de CT-13 cobra: uma linha custa menos que descobrir de novo por que a página de
 * 2FA do Breezy morreu.
 */
protected static string $layout = 'filament-auth-designer::components.layouts.auth';
```

**4b. `mount()` — a guarda**

```php
public ?Convite $convite = null;

public function mount(): void
{
    $this->convite = Convite::valido(request()->query('token'));

    if (! $this->convite) {
        $this->recusar();
    }

    parent::mount();
}
```

`recusar()` reusa o padrão de `TelaBloqueio::sairPara()` (`app/Filament/Pages/Auth/TelaBloqueio.php:99-102`):

```php
/**
 * Sem convite válido não existe cadastro. Sai por HttpResponseException, e não
 * por `redirect()` solto: dentro de `mount()` de página Livewire o `redirect()`
 * devolve o Redirector do Livewire onde o Laravel espera um código HTTP, e o
 * request morre em 500 — foi o bug de TelaBloqueio (ver a nota em :74-85 dela).
 * O `Register::mount()` do Filament faz exatamente isso em :60; aqui não.
 *
 * Resposta ÚNICA para os três motivos: quem tem o link não descobre se o token
 * não existe, se expirou ou se já foi usado. Ver ADR-02.
 */
private function recusar(): never
{
    Notification::make()
        ->title('Convite inválido ou expirado')
        ->body('Peça um convite novo a quem administra o sistema. Se você já tem conta, entre por aqui.')
        ->danger()
        ->persistent()
        ->send();

    throw new HttpResponseException(
        new RedirectResponse(Filament::getPanel('app')->getLoginUrl()),
    );
}
```

- **Redirect para o login, não 403** (ADR-03): quem clica num convite vencido é uma pessoa
  de fora, frequentemente dias depois, e o login é para onde ela precisa ir de qualquer
  forma — inclusive no caso "já tenho conta". Um 403 é um beco. A notificação persistente
  sobrevive ao redirect e explica o que houve.
- **Logs**:

```php
Log::channel('autenticacao')->warning(
    '[RegistroPorConvite@mount] Registro sem convite valido recusado',
    [
        'motivo' => 'convite_invalido',
        'ip'     => request()->ip(),
    ],
);
```

  Um motivo só, deliberadamente: distinguir `token_inexistente` de `token_expirado` no log
  exigiria que `Convite::valido()` devolvesse o porquê, e esse porquê acabaria chegando à
  tela. Nem token, nem hash, nem prefixo, nem e-mail — no caso "token inexistente" não há
  e-mail a mascarar.

**4c. `mutateFormDataBeforeRegister()` — o e-mail vem do convite**

```php
/**
 * Gancho do Filament em Register.php:91, dentro da transação.
 *
 * O campo de e-mail é exibido desabilitado, e estado de Livewire é do cliente:
 * a autoridade sobre QUEM está sendo cadastrado é o convite, não o formulário.
 */
protected function mutateFormDataBeforeRegister(array $data): array
{
    $data['email'] = $this->convite->email;

    return $data;
}
```

**4d. `handleRegistration()` — delega ao model**

```php
protected function handleRegistration(array $data): Model
{
    return $this->convite->aceitar($data);
}
```

Substitui o `create($data)` default (`Register.php:156-159`). A senha já chega hasheada:
`getPasswordFormComponent()` faz `->dehydrateStateUsing(fn ($state) => Hash::make($state))`
(`Register.php:228`).

**4e. Campo de e-mail visível e travado**

```php
protected function getEmailFormComponent(): Component
{
    return parent::getEmailFormComponent()
        ->default(fn (): string => $this->convite->email)
        ->disabled()
        ->helperText('O convite foi enviado para este endereço.');
}
```

Mostrar o e-mail é o que faz a pessoa saber que chegou ao lugar certo. Estar desabilitado
não é a segurança — a segurança é 4c.

**4f. Título**

`getHeading()` devolve "Aceitar convite", em pt-BR (`wikis/convencoes.md:117-120`). Sem
subtítulo: o e-mail do convite já disse qual é a organização, e o campo de e-mail travado
(4e) já mostra à pessoa que ela chegou ao lugar certo.

**O que NÃO se escreve**: nenhuma rota, nenhum controller, nenhuma view Blade, nenhum
`FormRequest`, nenhum rate limiter — os dois limites já vêm de `Register.php:73` e
`Register.php:80`.

### 5. `App\Filament\Pages\Auth\TelaLogin` — tirar o "Cadastre-se" do login

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Pages/Auth/TelaLogin.php`
- Estende `Caresome\FilamentAuthDesigner\Pages\Auth\Login`.

```php
protected static string $layout = 'filament-auth-designer::components.layouts.auth';

/**
 * O login do Filament exibe "Cadastre-se" sempre que o painel tem registro
 * (vendor/filament/filament/src/Auth/Pages/Login.php:445-455). Aqui o registro
 * existe, mas só serve a quem tem convite: o link levaria toda visita a uma
 * página que recusa. `wikis/convencoes.md:84` chama isso de bug, não de detalhe.
 */
public function getSubheading(): string|Htmlable|null
{
    return null;
}
```

- Registrada no plugin junto com a de registro (passo 6).
- **Logs**: nenhum. É supressão de affordance.

### 6. Ligar o registro no painel `app`

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AppPanelProvider.php`, bloco `AuthDesignerPlugin`
  (`:111-117`)

```php
AuthDesignerPlugin::make()
    ->login(fn (AuthPageConfig $config): AuthPageConfig => $config
        ->usingPage(TelaLogin::class)
        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
        ->mediaPosition(MediaPosition::Left)
        ->mediaSize('70%')
        ->themeToggle()
    )
    // A tela de aceite do convite. Passa pelo PLUGIN, e não por
    // $panel->registration(...) direto: é o plugin que grava a chave
    // 'registration' no AuthDesignerConfigRepository (AuthDesignerPlugin.php:92-94).
    // Sem ela o repositório cai em `new AuthPageConfig`
    // (AuthDesignerConfigRepository.php:80) e a tela nasce sem mídia e sem
    // alternador de tema — diferente do login ao lado, sem erro nenhum.
    ->registration(fn (AuthPageConfig $config): AuthPageConfig => $config
        ->usingPage(RegistroPorConvite::class)
        ->media(asset('images/auth/login.svg'), alt: config('app.name'))
        ->mediaPosition(MediaPosition::Left)
        ->mediaSize('70%')
        ->themeToggle()
    ),
```

- `->registration()` no plugin faz `$panel->registration($this->getRegistrationPageClass())`
  (`AuthDesignerPlugin.php:33-35`), que é a chamada nativa de
  `HasAuth::registration()` (`:255-260`). A rota nasce em `routes/web.php:54-57`.
- **`->login()` continua onde está**, no bloco de `plugins()` — e o `->tenant()` continua
  **depois** dele (`AppPanelProvider.php:192-196`), pela armadilha já documentada em
  `wikis/convencoes.md:169`.
- `use` novos: `App\Filament\Pages\Auth\RegistroPorConvite`,
  `App\Filament\Pages\Auth\TelaLogin`. `AuthPageConfig` e `MediaPosition` já estão
  importados (`:13-14`).
- **Só no painel `app`.** `/admin` e `/infra` não ganham registro: quem administra e quem
  opera infraestrutura não nasce de convite público.
- **Logs**: nenhum. Provider não loga.

### 7. `config/kit.php` — validade do convite

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`, bloco novo depois de `tenancy` (`:57-70`)

```php
/*
|--------------------------------------------------------------------------
| Convites de acesso
|--------------------------------------------------------------------------
| O convite é a única forma de alguém de fora virar usuário: a tela de
| registro do painel /app recusa quem não traz um token válido.
|
| O token é de uso único e vale pelo prazo abaixo. Prazo curto reduz a
| janela de um link vazado (encaminhado, esquecido na caixa de entrada);
| prazo longo evita reenvio para quem só demorou a ver o e-mail. Sete dias
| é o meio-termo — troque no .env, não aqui.
|
| Lembre que o envio depende de MAIL_MAILER configurado (o default `log`
| escreve o convite em storage/logs e não manda nada) e de um worker de
| fila rodando, porque a notificação é enfileirável.
*/

'convites' => [
    'validade_em_dias' => (int) env('KIT_CONVITE_VALIDADE_DIAS', 7),
],
```

- `.env.example` ganha `KIT_CONVITE_VALIDADE_DIAS=7`, comentado, junto das outras chaves
  `KIT_*`.

### 8. Resource de convites no `/admin`

> Skills: `laravel-best-practices`

Estrutura espelhando `app/Filament/Admin/Resources/Tenants/` (Resource + `Schemas/` +
`Tables/` + `Pages/`).

**8a. `app/Filament/Admin/Resources/Convites/ConviteResource.php`**

- `$model = Convite::class`, `$navigationIcon = Heroicon::OutlinedEnvelope`,
  `$navigationGroup = 'Administração'`, `$recordTitleAttribute = 'email'`.
- `use BadgeContagemNavegacao;` — como em `TenantResource.php:44`.
- `getPages()`: **só `index` e `create`**. Sem `edit` (ADR-04).

**8b. `Schemas/ConviteForm.php`**

| Campo | Componente | Regras |
| --- | --- | --- |
| `email` | `TextInput::make('email')->email()->required()->unique('users', 'email', ignoreRecord: false)` | o `unique` aponta para **`users`**: convidar quem já tem conta é erro de formulário, e aqui dizer isso é correto — quem preenche é um administrador autorizado (ADR-02) |
| `role_id` | `Select::make('role_id')->relationship('papel', 'name')->required()->preload()` | rótulo mostrando o painel, como em `UserResource` |
| `tenant_id` | `Select::make('tenant_id')->relationship('tenant', 'nome')->preload()->visible(fn () => (bool) config('kit.tenancy.enabled'))->required(fn (Get $get) => ...)` | obrigatório quando o papel escolhido tem `painel = 'app'`; ver abaixo |

A regra do campo de organização:

```php
->required(fn (Get $get): bool => config('kit.tenancy.enabled')
    && Role::find($get('role_id'))?->painel === 'app')
->helperText('Papel do painel de negócio precisa de uma organização: é nela que o papel será atribuído.')
```

com `->live()` no `Select` do papel. Sem isso o convite nasce com papel de `/app` e sem
organização — e o aceite atribui no contexto global, criando alguém que entra no painel e
não vê organização nenhuma.

- `->relationship()` puro serve nos dois selects: são FKs simples, não a pivot
  `model_has_roles`. A armadilha de `.ai/rules/filament.md:8-15` é específica de `roles`
  como **relação many-to-many** do usuário.

**8c. `Tables/ConvitesTable.php`**

| Coluna | Detalhe |
| --- | --- |
| `email` | `searchable()` |
| `papel.name` | `badge()` |
| `tenant.nome` | `visible(fn () => (bool) config('kit.tenancy.enabled'))` |
| `situacao` | `TextColumn::make('situacao')->state(fn (Convite $r): string => ...)->badge()` — "Aceito" / "Expirado" / "Pendente", derivado, sem coluna no banco |
| `expira_em` | `dateTime('d/m/Y H:i')->sortable()` |
| `convidadoPor.name` | `toggleable(isToggledHiddenByDefault: true)` |

Filtros: `TernaryFilter` para pendentes (`whereNull('aceito_em')`).

Ações de linha:

```php
Action::make('reenviar')
    ->label('Reenviar')
    ->icon(Heroicon::OutlinedPaperAirplane)
    ->requiresConfirmation()
    ->modalDescription('O link anterior deixa de funcionar e um novo é enviado.')
    ->visible(fn (Convite $record): bool => $record->aceito_em === null)
    ->action(fn (Convite $record) => $record->enviar())
    ->successNotificationTitle('Convite reenviado'),

DeleteAction::make()
    ->label('Revogar')
    ->modalHeading('Revogar convite')
    ->modalDescription('O link para de funcionar imediatamente. A revogação fica na auditoria.')
    ->after(fn (Convite $record) => Log::channel('autenticacao')->warning(
        "[ConvitesTable@revogar] Convite revogado | convite: {$record->id}",
        [
            'convite_id' => $record->id,
            'email'      => Str::mask($record->email, '*', 3),
            'role_id'    => $record->role_id,
            'tenant_id'  => $record->tenant_id,
            'revogado_por' => auth()->id(),
        ],
    )),
```

- **`reenviar` é uma `Action` de três linhas úteis**, porque o model já faz o trabalho
  (passo 2a). O `->action()` ignora o retorno — o token em claro morre ali.
- **`revogar` é o `DeleteAction` nativo, relabelado.** Ver ADR-04.
- O `->action()` do reenvio **não** loga: `Convite@enviar` já loga, com `reenvio => true`.

**8d. `Pages/CreateConvite.php`**

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['convidado_por_id'] = auth()->id();

    return $data;
}

protected function afterCreate(): void
{
    $this->record->enviar();
}
```

- `getRedirectUrl()` → `$this->getResource()::getUrl('index')`.
- **`afterCreate()` e não Observer**: o e-mail sai quando um administrador clica, não quando
  um seeder ou um teste toca a tabela.

**8e. `Pages/ListConvites.php`** — `CreateAction` no header, sem nada de especial.

**8f. Factory** — `database/factories/ConviteFactory.php`, para os CTs. `definition()`
devolve e-mail de faker, `expira_em` futuro e `aceito_em` nulo; `token` fica nulo até os
testes chamarem `enviar()`, que é quem devolve o real. **Sem seeder de convite**: convite é
dado de operação, não de fundação — e `wikis/convencoes.md:95` proíbe faker em seeder.

### 9. Permissões do Resource novo

> Skills: nenhuma

Regra do kit, gravada em `.ai/rules/filament.md` pela wiki `perfil-e-acesso-ao-painel`:

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

- O primeiro roda `shield:generate --all` em cada painel e escreve `ConvitePolicy`.
- O segundo devolve as permissions aos papéis; `admin` recebe as de `Convite` sozinho,
  porque `App\Support\Paineis::permissoes('admin')` lê do Shield e o Resource está sob
  `app/Filament/Admin/Resources`.
- Os dois são idempotentes.
- **Pular isto** faz a tela responder 403 para todo mundo que não é `master_global` — sem
  pista da causa.

### 10. `kit:update` conhece os caminhos novos

> Skills: `laravel-best-practices`

- **Path**: `app/Console/Commands/KitUpdate.php`, constante `CAMINHOS_DO_KIT` (`:66-115`)

Dois acréscimos:

| Caminho | Por quê |
| --- | --- |
| `app/Models/Convite.php` | a lista é arquivo a arquivo em `app/Models` (`:79-82`), justamente para não colidir com os models do projeto |
| `app/Notifications` | diretório novo; o padrão da lista é diretório inteiro quando é área do kit (como `app/Policies`, `:83`) |

`app/Support` entra pela wiki `perfil-e-acesso-ao-painel`, passo 10 — não repetir aqui.

`tests/Kit/KitUpdateTest.php` varre a árvore e cobra a cobertura arquivo a arquivo.
**Rodá-lo antes da correção, para vê-lo falhar**, é o que prova que a varredura funciona.

### 11. Documentação

> Skills: nenhuma

| Arquivo | O que muda |
| --- | --- |
| `wikis/arquitetura.md` | `## Autorização, em três níveis` ganha a nota de que existe uma rota pública em `/app`; subseção nova `### Convite` explicando token hasheado + uso único + contexto de papel |
| `wikis/convencoes.md` | `## Armadilhas já resolvidas` ganha duas linhas: "ligar `registration()` põe 'Cadastre-se' no login" e "registrar a página de auth fora do `AuthDesignerPlugin` deixa a tela sem mídia, sem erro" |
| `wikis/receitas.md` | `## Convidar um usuário` (nova); `## Problemas comuns` ganha "convite não chega" (mailer/worker) e "aceitei o convite e levo 403" (papel sem `painel`) |
| `wikis/pacotes.md` | linha do auth-designer anota que a página de registro é a do projeto |
| `README.md` | `## Convite de usuário` — o fluxo, a exigência de `MAIL_MAILER` e de worker, e `KIT_CONVITE_VALIDADE_DIAS` |
| `README.en.md` | espelho obrigatório |
| `.env.example` | `KIT_CONVITE_VALIDADE_DIAS=7` |

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada em cada passo: isso precisa existir? já existe
> no repo? stdlib? feature nativa? dependência instalada? uma linha? mínimo que funciona.
>
> O que a escada cortou deste plano, e quando valeria acrescentar:
>
> | Cortado | Por quê | Quando acrescentar |
> | --- | --- | --- |
> | Rota + controller + view de aceite | a página de registro do Filament já é pública, tem rate limit, transação e auto-login | nunca, enquanto o painel for Filament |
> | `ConvitePolicy` à mão | `shield:generate --all` a gera | se o convite precisar de regra que não seja "tem a permission" |
> | `ConviteService` | três métodos que só tocam o próprio registro | se o aceite passar a falar com serviço externo |
> | `Job` de envio | a `Notification` com `ShouldQueue` já é o job | se o envio virar lote (convidar 500 e-mails de uma vez) |
> | Evento `ConviteAceito` + listener | sem segundo consumidor | quando existir o segundo |
> | `reenviar()` no model | reenviar **é** `enviar()` de novo | nunca |
> | Coluna de status / Enum | derivável de `aceito_em` + `expira_em` | se aparecer um estado que não seja derivável |
> | Coluna `revogado_em` | revogar é apagar; a auditoria guarda | se revogado precisar aparecer na listagem |
> | `Str::mask()` em helper próprio | é stdlib do Laravel | nunca |
> | Cache de validação de token | um `SELECT` por índice único, num clique por convite | nunca |
> | Feature flag do cadastro | interruptor numa porta é porta | nunca |
> | `expira_em` NOT NULL + valor provisório no `create()` | a coluna nullable falha fechado sozinha, e a linha mentirosa some | nunca |
> | CT do `/app/{outra}` → 404 | `tests/Tenancy/TenancyTest.php:205-216` já trava `canAccessTenant()`; o convite só precisa provar o vínculo (CT-07) e o contexto (CT-11) | se o convite passar a criar vínculo por outro caminho |
> | CT do `getPages()` sem `edit` | assere um literal escrito no mesmo commit; a razão vive no PHPDoc do Resource (ADR-04) | se `getPages()` passar a ser calculado |
> | Subtítulo com o nome da organização na tela de aceite | o e-mail já disse, e o campo travado já situa | nunca |
>
> Reuso deliberado, em vez de código novo: `TelaBloqueio::sairPara()` (padrão de saída em
> `mount()`), `AuditsFillables` (trilha da revogação), `Panel::route()` (URL do convite),
> `DeleteAction` (revogação), rate limit do `Register`.
>
> Atalhos deliberados marcados com comentário `ponytail:`.
> Ao final, `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em `full`** na conversa com o usuário. Arquivos wiki, código, commits e
> READMEs são boundary — prosa normal.

## Mapeamentos

### Papel do convite → contexto da atribuição

| `roles.painel` do convite | `tenant_id` do convite | Contexto de `assignRole()` | Resultado |
| --- | --- | --- | --- |
| `app` | uma organização | o `tenant_id` | entra em `/app/{slug}` daquela organização |
| `app` | nulo (modo single-tenant) | `CONTEXTO_GLOBAL` | entra em `/app` |
| `admin` | irrelevante | `CONTEXTO_GLOBAL` | entra em `/admin` |
| `infra` | irrelevante | `CONTEXTO_GLOBAL` | entra em `/infra` |
| `null` | irrelevante | `CONTEXTO_GLOBAL` | não entra em painel nenhum (ADR-03 da wiki irmã) |

### Estado do convite (derivado, sem coluna)

| `aceito_em` | `expira_em` | Situação | `Convite::valido()` |
| --- | --- | --- | --- |
| nulo | futuro | Pendente | devolve o convite |
| nulo | passado | Expirado | `null` |
| preenchido | qualquer | Aceito | `null` |
| — | — | Revogado (linha apagada) | `null` |

## Testes

> Ver `04-casos-de-teste.md`. Dezesseis casos: `tests/Kit/ConviteTest.php` (single-tenant) e
> `tests/Tenancy/ConviteTenancyTest.php` (multi-tenant). As duas suítes já entram no grupo
> `kit` (`tests/Pest.php:34-37` e `:58-61`), então `composer test:kit` cobre as duas.

## Verificação Final

- [ ] `php artisan migrate`
- [ ] `php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder`
- [ ] `php artisan db:seed --class=Database\\Seeders\\PapeisSeeder`
- [ ] `php artisan route:list --path=register` mostra `filament.app.auth.register` **sem**
      `{tenant}` nos dois modos
- [ ] `grep -rn "token" storage/logs/autenticacao*.log` não devolve nada depois de um
      ciclo completo de convite
- [ ] Tela de login do `/app` **não** mostra "Cadastre-se"
- [ ] Tela de aceite tem a mesma mídia do login; uma página comum do painel **não** tem
      `fi-auth-layout` depois dela
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact --group=kit`
- [ ] `composer types:check`

## Commits

- `:sparkles: cadastro de usuario por convite com token de uso unico`
- `:memo: wiki da feature convite-de-usuario`
