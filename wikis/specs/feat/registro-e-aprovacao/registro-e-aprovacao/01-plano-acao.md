# Plano de Ação — w3b: registro aberto no /app e aprovação de cadastro

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/main/convite-de-usuario/` e
  `wikis/specs/main/convite-para-usuario-existente/` — as duas que criaram e evoluíram a
  página de registro do `/app`. Também `wikis/specs/fix/auth-designer-telas/`, que vestiu a
  tela de verificação de e-mail e deixou a rota desligada de propósito.
- **Motivo**: hoje o `/app` tem registro **por convite obrigatório**: sem token válido a
  página recusa. Esta feature faz registro aberto e registro por convite **coexistirem** na
  mesma tela, com o registro aberto desligado por default.
- **Toca infra compartilhada?**: **sim** — `App\Models\User` (`canAccessPanel()`, contrato
  `MustVerifyEmail`), `AppPanelProvider` (verificação de e-mail), `config/kit.php`, migration
  em `users` e em `tenants`, e os dois `UserResource`. **A regressão é obrigatória**: os CT
  do convite, dos painéis e da matriz de papéis precisam continuar verdes.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | opção liga/desliga registro no `/app` | 1, 3, 5 | `config('kit.registro.habilitado')` |
| RQ-02 | a opção vem de um Settings | 3 | **ponto único de ligação**: `App\Support\RegistroAberto`. Hoje lê `config()`; ver ADR-02 |
| RQ-03 | por organização, com tenancy | 2, 3, 5, 9 | coluna `tenants.registro_habilitado` + `?org={slug}` |
| RQ-04 | recebe só o perfil do `/app` | 3, 4 | `panel_user`, resolvido em um lugar só |
| RQ-05 | nenhum outro perfil nem acesso | 3, 4, 6 | teste explícito de 403 em `/admin` e `/infra` |
| RQ-06 | administrador edita depois | 7, 8 | os `UserResource` que já existem — nenhuma tela nova |
| RQ-07 | aprovação automática ou pendente | 2, 3, 4, 6, 7, 8 | `users.aprovacao_pendente` |
| RQ-08 | pesquisar nativo/pacote antes | — | ADR-01 registra o que foi avaliado e recusado |
| RQ-09 | opção de validação de e-mail | 3, 4, 5, 10 | `User implements MustVerifyEmail` + `->emailVerification()` condicional |
| RQ-10 | documentar nos README | 11 | `README.md` **e** `README.en.md` |
| RQ-11 | default `false` | 1 | e a suíte inteira roda com o default |
| RQ-12 | `true` reflete em tudo que vem | 5, 6, 7, 8, 10, 11 | rota, link no login, papel, 403, pendência, verificação, README |

## Objetivo

Fazer o painel `/app` aceitar **registro aberto** — cadastro sem convite — como uma opção
desligada por default, sem quebrar o registro por convite, que é o fluxo padrão do kit e tem
teste. Quem se registra por essa via nasce com **um único papel** (`panel_user`) e com nenhum
acesso a `/admin` ou `/infra`; e, se a instalação escolher, nasce **pendente de aprovação** —
estado em que não entra em painel nenhum até alguém aprovar pela tela de usuários que já
existe.

Junto vêm duas opções que o requisito amarra ao registro: exigir **validação de e-mail**
(hoje inexistente no kit — a tela está vestida e a rota desligada de propósito) e, com
multi-tenancy ligada, deixar **cada organização** decidir se aceita registro.

## Contexto

Estado medido em `0d423dd`:

- **Registro hoje é convite obrigatório.** `AppPanelProvider.php:212-224` registra
  `->registration(… ->usingPage(RegistroPorConvite::class))` **pelo plugin do Auth Designer**
  — e passar pelo plugin é obrigatório, porque é ele que grava a chave `registration` no
  `AuthDesignerConfigRepository` (`AuthDesignerPlugin.php:92-94`); sem ela a tela nasce sem
  mídia e sem alternador de tema, **sem erro nenhum**.
- `app/Filament/Pages/Auth/RegistroPorConvite.php` é a página. `mount()` (`:50-66`) exige
  `Convite::valido(request()->query('token'))` e, sem convite, chama `recusar()` (`:200-255`)
  — throttle 5/600 s por IP, `warning` no channel `autenticacao`, notificação genérica e
  redirect ao login. `desviarParaAceite()` (`:76-114`) trata usuário já existente.
  `handleRegistration()` delega a `Convite::aceitar($data)`; `mutateFormDataBeforeRegister()`
  **força** o e-mail do convite.
- **O login não oferece "Cadastre-se".** `TelaLogin::getSubheading()` devolve `null`, porque
  `Login::getSubheading()` do Filament (`vendor/filament/filament/src/Auth/Pages/Login.php:445-456`)
  exibe o link sempre que o painel tem registro — e o link levaria a uma tela que recusa.
- **`/admin` e `/infra` não têm registro** (`AdminPanelProvider.php:59-60`,
  `InfraPanelProvider.php:75-76`) e continuam sem.
- **Verificação de e-mail não existe.** Nenhum painel liga a rota:
  `->emailVerification(null, isRequired: false)` nos três. O comentário em
  `AppPanelProvider.php:341-377` explica que ligar dá **500**, porque
  `EmailVerificationPrompt::getVerifiable()` declara retorno `MustVerifyEmail`
  (`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`)
  e `App\Models\User` não implementa a interface.
- **O papel do `/app`** é `panel_user`, criado por `PapeisSeeder` com painel `app` e a matriz
  do painel **menos** administração (`UserResource`, `ConviteResource`), menos
  `permissoesForaDoApp()` (`ExceptionResource`), menos `Import:`/`Export:`.
  `User::canAccessPanel()` (`User.php:76-105`) compara `roles.painel` com o id do painel.
- **`/app` é multi-tenant** (`->tenant(Tenant::class, slugAttribute: 'slug')`,
  `AppPanelProvider.php:366`) e **não** tem `->tenantRegistration()`, ausente de propósito.

### Um fato do vendor que decide a feature inteira

`Register::sendEmailVerificationNotification()`
(`vendor/filament/filament/src/Auth/Pages/Register.php:161-180`) só dispara o e-mail quando
`$user instanceof MustVerifyEmail` **E** `! $user->hasVerifiedEmail()`.

`Convite::aceitar()` já grava `email_verified_at` (`Convite.php:591`, com o motivo escrito
em `:583-590`: o token PROVA posse do endereço). Logo, **implementar `MustVerifyEmail` não
faz o aceite de convite disparar e-mail de verificação** — o convidado nasce verificado e o
vendor pula o envio. A condição não precisa de flag: ela já está no dado.

Isto é o que torna RQ-09 implementável sem quebrar o convite, e é a razão pela qual esta wiki
**liga** a verificação de e-mail em vez de entregá-la desligada com justificativa.

## Análise dos Arquivos Existentes

### `app/Filament/Pages/Auth/RegistroPorConvite.php` → renomeado para `RegistroPorConvite`

O `mount()` ganha um garfo por **ausência de token**, não por config: token presente ⇒ fluxo
de convite, byte por byte o de hoje; token ausente ⇒ consulta o registro aberto. Os quatro
métodos do convite (`mutateFormDataBeforeRegister`, `handleRegistration`,
`getEmailFormComponent`, `getHeading`) passam a ter dois ramos.

Renomeado porque o nome é a documentação primária de uma superfície de autenticação:
`RegistroPorConvite` que também faz registro aberto é convite para o próximo agente
implementar registro aberto **de novo** em outro lugar. `RegistroPorConvite` segue a convenção do
kit (`TelaLogin`, `TelaBloqueio`).

### `app/Models/User.php`

Recebe `implements MustVerifyEmail` e a guarda de pendência **no topo** de
`canAccessPanel()` — antes do `isMasterGlobal()`, para que "pendente" signifique painel
nenhum, sem exceção.

### `app/Providers/Filament/AppPanelProvider.php`

O `->emailVerification(null, isRequired: false)` de `:377` passa a ser condicional. O bloco
de comentário de `:341-377` é reescrito: os "três passos para ligar" viraram código, e a
frase *"NENHUM usuário semeado tem `email_verified_at`"* é **factualmente falsa hoje** —
`UsuarioAdminSeeder.php:45`, `UserFactory.php:30`, `DemoTenancySeeder.php:103`,
`Convite.php:591` e `KitAdmin.php:204` todos gravam. Corrigir junto.

### `app/Filament/Pages/Auth/TelaLogin.php`

`getSubheading()` volta a devolver o do pai **quando** o registro aberto está ligado — o link
"Cadastre-se" passa a levar a um caminho que existe.

### `app/Filament/App/Resources/Users/UserResource.php` e `.../Admin/Resources/Users/UserResource.php`

Ganham a coluna de situação, o filtro de pendentes e a ação "Aprovar". Nenhuma tela nova:
RQ-06 pede exatamente a tela que já existe.

### `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`

Ganha a seção "Registro" com o `Toggle` de `registro_habilitado`.

## Autorização

- **Policies**: nenhuma nova. A ação "Aprovar" usa `->authorize('update')`, que cai em
  `UserPolicy::update` (gerada pelo Shield). `panel_user` não tem `Update:User` — a subtração
  de administração do `PapeisSeeder` já a remove.
- **Gates**: nenhum novo.
- **Middleware**: nenhum novo escrito pelo kit. Quando a verificação de e-mail está ligada, o
  `EnsureEmailIsVerified` do Filament entra pelo `->emailVerification(…, isRequired: true)`.
- **Papel atribuído no registro**: `panel_user`, e **só ele** — resolvido em
  `RegistroAberto::papel()`, um lugar só.
- **Guarda de pendência**: `User::canAccessPanel()`, primeira instrução. Vale para os três
  painéis e vence até o `master_global`.

## Rotas

Nenhuma rota escrita à mão. As que passam a existir/mudar:

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/app/register` | `filament.app.auth.register` | já existe; passa a aceitar `?org={slug}` e a atender sem token quando ligado |
| GET | `/app/email-verification/prompt` | `filament.app.auth.email-verification.prompt` | nasce **só** quando `RegistroAberto::exigirVerificacaoDeEmail()` |
| GET | `/app/email-verification/verify/{id}/{hash}` | `filament.app.auth.email-verification.verify` | idem |

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `RegistroPorConvite` (modo convite) | Filament (Page) | `/app/register?token=…` | preenche nome e senha | Sim |
| `RegistroPorConvite` (modo aberto) | Filament (Page) | `/app/register` (+ `?org=slug`) | preenche nome, e-mail e senha | Sim |
| `TelaLogin` — link "Cadastre-se" | Filament (Page) | `/app/login` | clica no link | Não |
| `UsersTable` do `/app` — ação Aprovar | Filament (Table action) | `/app/{tenant}/users` | confirma a aprovação | Sim |
| `UsersTable` do `/admin` — ação Aprovar | Filament (Table action) | `/admin/users` | confirma a aprovação | Sim |
| `TenantForm` — toggle de registro | Filament (Form) | `/admin/organizacoes/{id}/edit` | liga/desliga | Sim |
| Tela de verificação de e-mail | Filament (Page, Auth Designer) | `/app/email-verification/prompt` | clica em "reenviar" | Sim |

**Gate de CT-B**: a tabela é o gatilho, não o critério. O que só o navegador prova aqui é o
**layout** da tela de registro no modo aberto (a arte do Auth Designer, o eixo espelhado) e a
ausência de erro de JS na tela nova de verificação de e-mail. Gravação, validação, papel,
403, notificação e ação de tabela são componente Livewire e ficam no `04`.

**Gate de tela de escrita**: `/app/register` grava — CT de gravação por componente em CT-06,
CT-10, CT-13. `/admin/organizacoes/{id}/edit` grava — CT-19.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_REGISTRO` | `false` | liga o registro aberto no `/app` |
| `KIT_REGISTRO_APROVACAO_MANUAL` | `false` | registro nasce pendente até alguém aprovar |
| `KIT_REGISTRO_VERIFICAR_EMAIL` | `false` | exige validação de e-mail no `/app` |

`(bool) env(...)` é seguro aqui e a razão importa: `.ai/rules/config.md` proíbe
`(int) env('X', 100)` porque `X=` (presente, vazio) devolve `''` e `(int) ''` é `0`, matando
o default. Para um booleano cujo default é `false`, `(bool) ''` **é** `false` — vazio e
ausente colapsam no mesmo valor, que é o desejado. É o mesmo padrão de `KIT_TENANCY`.

## Eventos / Listeners / Observers

- **Eventos emitidos**: `Filament\Auth\Events\Registered` — já emitido pelo
  `Register::register()` do vendor, nos dois modos. Nada novo.
- **Listeners / Observers**: nenhum. A transição de estado é síncrona e cabe no model.

## Jobs / Queues

Nenhum job. O e-mail de verificação usa o `Notifiable` do usuário, no driver configurado.

## Impacto em Features Existentes

- **Convite de usuário** (`Convite`, `RegistroPorConvite` modo convite, `ConvitesRecebidos`): o
  caminho com token não consulta o registro aberto em momento nenhum, e a classe **não é
  renomeada** (ADR-04), então nenhum teste do convite é editado. O risco residual é o garfo
  novo no `mount()` — coberto por CT-06, CT-07 e CT-20b.
- **Verificação de e-mail nos três painéis**: `User` passa a ser `MustVerifyEmail`
  globalmente. `/admin` e `/infra` mantêm `emailVerification(null, false)` ⇒ nenhum
  middleware ⇒ ninguém barrado. Só o `/app`, e só com a opção ligada.
- **`User::canAccessPanel()`**: instrução nova no topo. Todo caso de acesso a painel passa
  por ela. `aprovacao_pendente` nasce `false` por default de coluna ⇒ inerte.
- **Matriz de papéis** (`PapeisSeeder`): **não muda**. Nenhum Resource, Page ou Widget novo,
  logo nenhuma permissão nova e nenhuma entrada nova na lista de subtração. Esta é a razão de
  a aprovação morar nos `UserResource` que já existem.
- **`tests/Kit/InventarioDeTelasTest.php`**: reconcilia telas alcançáveis por URL. As duas
  rotas de verificação de e-mail **não nascem** com o default `false`, então o inventário não
  muda.
- **`UserExporter`**: exporta `email_verified_at`. `aprovacao_pendente` **não** entra — é
  estado de fronteira de acesso, e a planilha já sai com e-mail de todo mundo.

## Rollback

- **Migration down**: `users.aprovacao_pendente` e `tenants.registro_habilitado` são
  `dropColumn`. Nenhum dado de negócio se perde — as duas são booleanas de fronteira.
- **Feature flag**: `KIT_REGISTRO=false` (o default) devolve o comportamento de hoje por
  completo: `/app/register` sem token volta a recusar, o login volta a não oferecer
  "Cadastre-se", e as rotas de verificação de e-mail deixam de existir. É o desligamento sem
  deploy.
- **Reversão de dados**: usuário já aprovado permanece aprovado; usuário pendente com a
  coluna removida vira aprovado (default da coluna era `false`). Documentado no `down()`.

## Dependências

- **Composer**: nenhuma nova. `spatie/laravel-settings` e
  `filament/spatie-laravel-settings-plugin` **já** estão no `composer.json` — a wiki do
  Settings os usa; esta não os toca.
- **NPM**: nenhuma.

## Riscos

- **O Settings ainda não existe** (`feat/settings-do-kit` mergeia antes). Mitigação: um
  ponto único de leitura (ADR-02); o rebase troca três métodos de um arquivo.
- **Ligar `isRequired: true` na verificação de e-mail barra usuário legado sem
  `email_verified_at`.** Mitigação: os caminhos do kit já gravam a coluna; o README documenta
  a consequência e o comando de reparo em uma linha.
- **Registro aberto é superfície anônima que cria conta.** Mitigação: throttle do vendor (2
  por IP + 2 por e-mail), papel único, guarda de pendência, log no `autenticacao` com e-mail
  mascarado, e CT para cada um.
- **Risco eliminado, e vale registrar por quê**: a primeira versão do plano renomeava a página
  para `TelaRegistro`. A auditoria do step 6 cortou o rename (ADR-04) — era ~10 arquivos, dois
  deles asserções de log de testes do convite, em troca de nada observável. Renomear era a
  opção maior **e** a mais arriscada ao mesmo tempo.

## Channel de Log da Feature

### Verificação de Channel Existente

`grep -n autenticacao config/logging.php` ⇒ o channel **existe** (`config/logging.php:132`),
driver de `LOG_KIT_DRIVER`, path `storage/logs/autenticacao.log`. É o channel de toda
fronteira de acesso do kit: `User::canAccessPanel()`, `Convite::aceitar()`,
`UserResource::gravarPapeis()`, `RegistroPorConvite::recusar()`.

### Decisão

**Reusar `autenticacao`.** Registro, aprovação e negativa de acesso são a mesma pergunta
operacional — *"quem entrou, quem tentou, com que papel"* — e ela se responde lendo **um**
arquivo. Um channel `registro` novo partiria essa leitura em dois e obrigaria o Logs Explorer
do `/infra` a abrir dois arquivos para reconstruir uma sessão.

Todos os logs desta feature usam `Log::channel('autenticacao')`, formato
`[Classe@Método] mensagem | parâmetro`, **e-mail sempre mascarado com `Str::mask($email, '*', 3)`**,
senha nunca.

## Estrutura de Implementação

### 1. `config/kit.php` — o bloco `registro`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`
- Novo bloco, depois de `tenancy` (é dele que RQ-03 depende) e antes de `demo`:

```php
'registro' => [
    'habilitado'       => (bool) env('KIT_REGISTRO', false),
    'aprovacao_manual' => (bool) env('KIT_REGISTRO_APROVACAO_MANUAL', false),
    'verificar_email'  => (bool) env('KIT_REGISTRO_VERIFICAR_EMAIL', false),
],
```

- Comentário obrigatório no bloco: por que `(bool) env()` é seguro aqui e o
  `(int) env()` não é (`.ai/rules/config.md`); e que **ninguém lê estas chaves direto** — a
  leitura é `App\Support\RegistroAberto`.
- `.env.example`: as três chaves comentadas, com o default explícito.
- **Logs**: nenhum (arquivo de config).

### 2. Migrations — as duas colunas de fronteira

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_24_000001_add_aprovacao_pendente_to_users_table.php`
  - `$table->boolean('aprovacao_pendente')->default(false)->after('email_verified_at');`
  - Docblock: por que **boolean com default `false`** e não `aprovado_em` nullable — com o
    timestamp nullable, "pendente" é o default e **todo** caminho existente que cria usuário
    (5 hoje: seeder do admin, factory, seeder da demo, convite, `kit:admin`) teria de lembrar
    de preencher, e esquecer significa trancar alguém para fora sem erro nenhum. Com o
    boolean, só quem se registra pela via aberta grava `true`; o resto nasce aprovado por
    omissão. O "quem aprovou e quando" vem do `laravel-auditing`, que já está na model.
  - `down()`: `dropColumn`. Nota de que usuário pendente vira aprovado ao reverter.
- **Path**: `database/migrations/2026_08_24_000002_add_registro_habilitado_to_tenants_table.php`
  - `$table->boolean('registro_habilitado')->default(false)->after('ativo');`
  - Docblock: default `false` mesmo com o registro global ligado — RQ-03 diz que a
    organização **opta**, e opt-in é a única leitura que não abre organização de cliente sem
    ninguém decidir.
- **Logs**: nenhum.

### 3. `App\Support\RegistroAberto` — o ponto único

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/RegistroAberto.php`
- **É o ponto único de ligação com o Settings (ADR-02).** Todo o resto do código pergunta a
  esta classe; nenhum outro arquivo chama `config('kit.registro.*')`.

```php
final class RegistroAberto
{
    public static function habilitado(): bool;
    public static function exigirAprovacao(): bool;
    public static function exigirVerificacaoDeEmail(): bool;
    public static function papel(): string;              // 'panel_user' — RQ-04/RQ-05
    public static function organizacao(?string $slug): ?Tenant;
    public static function registrar(array $dados, ?Tenant $organizacao): User;
}
```

- `organizacao(?string $slug)`:
  - tenancy desligada ⇒ `null` (não há organização a resolver);
  - tenancy ligada ⇒ `Tenant` com aquele `slug`, `ativo = true` **e**
    `registro_habilitado = true`; qualquer falha ⇒ `null`.
- `registrar(array $dados, ?Tenant $organizacao): User` — **reafirma as fronteiras**, porque
  barreira que só existe na tela não é barreira (`.ai/rules/filament.md`):
  1. `habilitado()` falso ⇒ `RuntimeException`;
  2. tenancy ligada e `$organizacao === null` ⇒ `RuntimeException`;
  3. `User::create([...$dados])` (o `$fillable` recorta a nome/e-mail/senha);
  4. `forceFill()` do que **não** é fillable: `email_verified_at = now()` quando
     `! exigirVerificacaoDeEmail()` (é isto que faz o vendor pular o envio do e-mail —
     `Register.php:161-180`), e `aprovacao_pendente = true` quando `exigirAprovacao()`;
  5. `$organizacao` presente ⇒ `$user->tenants()->syncWithoutDetaching([$organizacao->id])`
     — antes da aprovação, de propósito: sem o vínculo o `getEloquentQuery()` do
     `UserResource` do `/app` não lista a pessoa e ninguém tem como aprová-la;
  6. papel: `assignRole(self::papel())` **somente quando não está pendente**. Pendente não
     tem papel, e é isso que torna RQ-05 verdadeiro por construção mesmo se a guarda de
     `canAccessPanel()` fosse removida. Com tenancy, a atribuição usa o contexto do tenant
     (o `PermissionRegistrar` fixado pelo middleware; em chamada fora de request, fixado
     explicitamente para `$organizacao->id`).
- **Logs**:
  - sucesso: `Log::channel('autenticacao')->info('[RegistroAberto@registrar] Registro aberto concluido | user: {id}', ['user_id', 'email' => Str::mask(...), 'tenant_id', 'pendente' => bool, 'papel' => string|null, 'verificacao_email' => bool])`
  - recusa por fronteira: `->warning('[RegistroAberto@registrar] Registro aberto recusado | motivo: {motivo}', ['motivo' => 'desabilitado'|'sem_organizacao', 'ip'])`

### 4. `App\Models\User` — contrato, cast e as duas guardas

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/User.php`
- `implements … , MustVerifyEmail` (`Illuminate\Contracts\Auth\MustVerifyEmail`). O trait
  `Notifiable` já dá o `notify()` que `sendEmailVerificationNotification()` exige, e
  `Illuminate\Foundation\Auth\User` já traz `MustVerifyEmail` (o trait) com
  `hasVerifiedEmail()`/`markEmailAsVerified()`.
- `casts()`: `'aprovacao_pendente' => 'boolean'`.
- **`aprovacao_pendente` fora do `$fillable`**, deliberadamente — mesmo motivo de
  `email_verified_at`: o estado da fronteira não se escreve por mass assignment vindo de
  formulário. Só `forceFill`.
- `canAccessPanel()` — **primeira** instrução, antes do `isMasterGlobal()`:

```php
if ($this->aprovacao_pendente) {
    Log::channel('autenticacao')->warning(
        "[User@canAccessPanel] Acesso negado: cadastro pendente de aprovacao | user: {$this->id} - painel: {$panel->getId()}",
        ['user_id' => $this->id, 'painel' => $panel->getId(), 'motivo' => 'aprovacao_pendente'],
    );

    return false;
}
```

- `aprovar(): void` — a transição, no model e não na Action, porque a Action não é o único
  chamador possível (`.ai/rules/filament.md`):

```php
public function aprovar(): void   // idempotente; sem pendência, no-op silencioso
```

  1. `! $this->aprovacao_pendente` ⇒ retorna (idempotente);
  2. `forceFill(['aprovacao_pendente' => false])->save()`;
  3. `assignRole(RegistroAberto::papel())` se ainda não tiver o papel;
  4. `info` no `autenticacao` com `alvo_id`, `executor_id` (`Auth::id()`), `tenant_id`,
     `papel`.
- **Logs**: os dois acima.

### 5. `App\Filament\Pages\Auth\TelaRegistro` — a coexistência

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php` — **sem rename** (ADR-04). O
  docblock da classe é reescrito para abrir com os dois modos e a tabela do garfo; é ele que
  substitui o nome como documentação.
- `protected static string $layout` **permanece redeclarado** — regra do kit
  (`.ai/rules/auth.md`), e o par de testes que a cobra continua valendo.
- `public ?Tenant $organizacao = null;` ao lado do `?Convite $convite`.
- `mount()`:

```php
$token = request()->query('token');

// Token presente: o fluxo de convite, inalterado. Nem consulta o registro aberto.
if (filled($token)) {
    $this->convite = Convite::valido($token);
    if (! $this->convite instanceof Convite) { $this->recusar(); }
    if (($existente = $this->convite->usuarioExistente()) !== null) { $this->desviarParaAceite($existente); }
    parent::mount();

    return;
}

// Sem token: só é caminho legítimo se o registro aberto estiver ligado.
if (! RegistroAberto::habilitado()) { $this->recusar(); }

if (config('kit.tenancy.enabled')) {
    $this->organizacao = RegistroAberto::organizacao(request()->query('org'));
    if (! $this->organizacao instanceof Tenant) { $this->recusar(); }
}

parent::mount();
```

  - **Token presente e inválido continua recusando**, mesmo com o registro aberto ligado: o
    garfo é por ausência, nunca por invalidez. Sem isso, `?token=lixo` viraria uma segunda
    porta para o modo aberto — e a recusa é onde vive o throttle.
- `getEmailFormComponent()`: o `->default()->disabled()->helperText()` passa a valer **só**
  no modo convite; no modo aberto devolve `parent::getEmailFormComponent()` cru (com o
  `->unique()` do Filament, que é quem recusa e-mail já cadastrado).
- `getHeading()`: `'Aceitar convite'` no modo convite; `'Criar conta'` no aberto (com o nome
  da organização quando houver).
- `mutateFormDataBeforeRegister()`: força o e-mail **só** no modo convite.
- `handleRegistration()`: convite ⇒ `$this->convite()->aceitar($data)`; aberto ⇒
  `RegistroAberto::registrar($data, $this->organizacao)`.
- `register()` — sobrescrito **só** para o caso pendente:

```php
public function register(): ?RegistrationResponse
{
    $resposta = parent::register();      // throttle, transação, evento, login: tudo do vendor

    // Pendente não segue para painel nenhum: o vendor já autenticou, então desfaz.
    if ($resposta !== null && Filament::auth()->user()?->aprovacao_pendente) { … logout + notificação + redirect ao login; return null; }

    return $resposta;
}
```

  - `logout()` + `session()->invalidate()` + `regenerateToken()`, notificação persistente
    *"Cadastro recebido — aguarde a aprovação"*, e `$this->redirect(loginUrl)`. Aqui
    `redirect()` é seguro: estamos numa **ação** Livewire, não no `mount()` — a armadilha
    documentada em `recusar()` é específica do `mount()`.
- Docblock da classe reescrito: os dois modos, por que o garfo é por ausência de token, e o
  que **não** muda no caminho do convite.
- **Logs**: `recusar()` mantém o `warning` de hoje (mensagem passa a
  `[RegistroPorConvite@mount]`). O log do sucesso vive em `RegistroAberto::registrar()`. Novo
  `warning` no ramo pendente:
  `'[RegistroPorConvite@register] Registro pendente de aprovacao — sessao encerrada | user: {id}'`.

### 6. `TelaLogin` — o link "Cadastre-se"

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Pages/Auth/TelaLogin.php`
- `getSubheading()`: `RegistroAberto::habilitado() ? parent::getSubheading() : null`.
- Docblock atualizado: o link é affordance **honesta** quando existe caminho aberto, e
  continua suprimida quando o registro é só por convite.
- **Logs**: nenhum (apresentação).

### 7. `AppPanelProvider` — a verificação de e-mail condicional

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AppPanelProvider.php`
- `->emailVerification(null, isRequired: false)` (`:377`) vira:

```php
->emailVerification(
    RegistroAberto::exigirVerificacaoDeEmail() ? EmailVerification::class : null,
    isRequired: RegistroAberto::exigirVerificacaoDeEmail(),
)
```

  com `EmailVerification` = `Caresome\FilamentAuthDesigner\Pages\Auth\EmailVerification`.
- O bloco de comentário de `:341-377` é reescrito: os três passos viraram código; sai a
  afirmação falsa sobre usuário semeado; entra a consequência real de ligar (usuário legado
  sem `email_verified_at` é barrado no `/app`) e como reparar.
- O comentário de `:249-262` (que diz "o kit não exige verificação — quem entra é
  convidado") passa a dizer que a exigência é opcional e por que o convidado nunca é afetado
  (`Convite.php:591`).
- **Logs**: nenhum.

### 8. `TenantForm` — o toggle por organização

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- `Toggle::make('registro_habilitado')` **dentro da `Section::make('Identificação')` que já
  existe**, ao lado do `Toggle::make('ativo')` — não numa seção nova. Os dois são booleanos de
  fronteira da organização, da mesma natureza ("está no ar" / "aceita cadastro"), e uma
  `Section` inteira para um campo contraria o próprio arquivo. *(Corte aplicado pela auditoria
  do step 6.)*
- `->visible(fn (): bool => RegistroAberto::habilitado())` — RQ-03 amarra a opção do tenant à
  global ("**e** o register estiver liberado"), e um toggle inerte é pior que nenhum.
- `helperText` dizendo o endereço exato que passa a funcionar: `/app/register?org={slug}`.
- `App\Models\Tenant`: `registro_habilitado` no `$fillable` e `'boolean'` nos `casts()`.
- **Logs**: nenhum. A mudança é auditada por `AuditsFillables`, que a model já usa.

### 9. Os dois `UserResource` — situação, filtro e aprovar

> Skills: `laravel-best-practices`

- **Paths**: `app/Filament/App/Resources/Users/UserResource.php` e
  `app/Filament/Admin/Resources/Users/UserResource.php` — os dois, e separados de propósito
  (ADR-04 da wiki `admin-da-organizacao`: base compartilhada faz edição pensada no `/admin`
  alargar o `/app` em silêncio).
- Na `table()` de cada um:
  - `TextColumn::make('aprovacao_pendente')->label('Situação')->badge()` com
    `Pendente`/`Ativo` e cor `warning`/`success`;
  - `Filter::make('pendentes')->query(fn (Builder $q) => $q->where('aprovacao_pendente', true))`
    — é a metade operacional de "alguém aprova": sem ela, achar o pendente entre 500 é olho
    no olho;
  - `Action::make('aprovar')`:
    - `->visible(fn (User $record): bool => $record->aprovacao_pendente)`
    - `->authorize('update')` — **não é opcional**: Action do Filament não consulta policy
      sozinha (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php`, default `null` =
      liberada para todo mundo). `.ai/rules/filament.md` cobra a linha.
    - `->requiresConfirmation()`
    - `->action(fn (User $record) => $record->aprovar())`
    - `->successNotificationTitle('Cadastro aprovado')`
- **Logs**: os de `User::aprovar()`.

### 10. Testes

> Skills: `pest-testing`

- Ver `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`.
- Arquivos: `tests/Kit/RegistroAbertoTest.php`, `tests/Tenancy/RegistroAbertoTenancyTest.php`,
  `tests/Browser/RegistroAbertoTest.php`.
- Nenhum helper novo em `tests/Pest.php` a não ser que dois arquivos usem o mesmo
  (`.ai/rules/testes.md`).

### 11. `README.md` e `README.en.md`

> Skills: nenhuma específica

- Seção nova **`## Registro aberto e aprovação`**, logo depois de `## Convite de usuário`
  (`README.md:280`, `README.en.md:242`) — é a continuação natural: as duas portas de entrada
  ficam lado a lado.
- Conteúdo obrigatório (RQ-10, RQ-12):
  1. o default é `false` e o que isso significa (o `/app/register` sem token recusa, como
     hoje);
  2. as três chaves, o que cada uma liga e o que muda na tela quando liga;
  3. **a tabela "o que ligar cada chave faz refletir"** — RQ-12 escrito por extenso: rota,
     link no login, papel atribuído, e-mail de verificação, pendência, tela de aprovação,
     log;
  4. com tenancy: o toggle por organização e a URL `?org={slug}`;
  5. o papel que o registrado recebe, e por que é só ele;
  6. a consequência de ligar a verificação de e-mail em base legada, com o reparo;
  7. por que o convite **não** dispara e-mail de verificação (`Convite.php:591`);
  8. o ponto único de ligação com o Settings, para quem for costurar depois.
- Também: `## Roteiro de features` → `### Acesso e autenticação` ganha as linhas do registro
  aberto e da aprovação, nos dois idiomas.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada foi aplicada e produziu estas escolhas:
>
> 1. **Reuso antes de criar**: a aprovação vive nos `UserResource` que já existem — nenhum
>    Resource novo, logo nenhuma permissão nova, nenhuma entrada nova na lista de subtração
>    do `PapeisSeeder`, nenhum risco de promover usuário comum a administrador por omissão.
> 2. **Feature nativa antes de código**: o throttle do registro é o do vendor
>    (`Register::register()` ⇒ `rateLimit(2)` + limiter por e-mail); a transação, o evento e o
>    auto-login também. O kit escreve o garfo e as fronteiras, nada do encanamento.
> 3. **Uma tela, dois modos** em vez de duas telas: o painel do Filament tem **uma** chave
>    `registration`, e uma segunda página exigiria rota à mão, layout do Auth Designer à mão
>    e uma segunda superfície pública para auditar.
> 4. **Um boolean em vez de um timestamp**: `aprovacao_pendente` com default `false` não
>    obriga nenhum dos 5 caminhos existentes de criação de usuário a lembrar de nada.
> 5. **Sem dependência nova**: nada instalado (ADR-01).
>
> Atalhos deliberados marcados com comentário `ponytail:`. Após implementar,
> `/ponytail:ponytail-review` no diff.

## Mapeamentos

| Estado | `aprovacao_pendente` | Papel | Entra em painel? |
|---|---|---|---|
| registrado com aprovação automática | `false` | `panel_user` | `/app` sim; `/admin` e `/infra` **403** |
| registrado com aprovação manual | `true` | **nenhum** | **nenhum** |
| aprovado depois | `false` | `panel_user` | `/app` sim |
| vindo de convite | `false` | o papel do convite | conforme o papel |
| semeado / criado pela tela | `false` | o que lhe derem | conforme o papel |

## Testes

> Ver `04-casos-de-teste.md` (backend) e `05-casos-de-teste-browser.md` (navegador).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` — 0 erros
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — 662 na base, nenhuma queda
- [ ] `composer test:browser`

## Commits

- `:sparkles: feat(registro): registro aberto no /app com papel unico e aprovacao opcional`
- `:memo: docs(readme): registro aberto e aprovacao nos dois README`
- `:memo: docs(wiki): wiki da feature registro-e-aprovacao`
