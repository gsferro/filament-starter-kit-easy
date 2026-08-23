# Casos de Teste — Convite de usuário

## Setup Global

### Estratégia de DB

`RefreshDatabase`, herdado do `tests/Pest.php` (`:34-37` para `tests/Kit`, `:58-61` para
`tests/Tenancy`). Não há escolha: o modo de tenancy muda o schema — as colunas de team só
existem com `permission.teams` ligado —, e `Tests\TestCase::setUp()` já invalida
`RefreshDatabaseState::$migrated` quando o modo troca (`tests/TestCase.php:126-134`).

| Arquivo | TestCase | Modo |
| --- | --- | --- |
| `tests/Kit/ConviteTest.php` | `Tests\TestCase` | single-tenant |
| `tests/Tenancy/ConviteTenancyTest.php` | `Tests\TenancyTestCase` | multi-tenant |

Os dois já entram no grupo `kit` pelo `tests/Pest.php`, então `composer test:kit` cobre ambos.

### Seeders no `beforeEach`

```php
beforeEach(function (): void {
    $this->seed(ShieldPermissionsSeeder::class);
    $this->seed(PapeisSeeder::class);
});
```

Mesmo padrão de `tests/Kit/PaineisTest.php:13-15`. **Obrigatório**: sem
`ShieldPermissionsSeeder` não existe permission no banco, o `PapeisSeeder` semeia papéis
vazios e — o que importa aqui — os papéis não teriam `roles.painel` preenchido. Os CTs de
contexto (CT-05, CT-11, CT-12) passariam por motivo errado ou falhariam sem relação com o
convite.

### Factories / Fixtures

| Factory | Estado | Observação |
| --- | --- | --- |
| `User::factory()` | existe (`database/factories/UserFactory.php`), state `unverified()` (`:39`) | nenhum state de papel — atribuir com `assignRole()` depois do `create()` |
| `Tenant::factory()` | existe (`database/factories/TenantFactory.php`), state `inativo()` (`:33`) | `definition()` gera `nome`, `slug` e `ativo = true` (`:27-29`) |
| `Convite::factory()` | **a criar** (passo 8f do plano) | `email` de faker, `expira_em` no futuro, `aceito_em` nulo. **Sem `token`** — a coluna é nullable e quem a preenche é `enviar()` |

**Quem quer o token em claro chama `enviar()`**, que o devolve. É o único ponto do sistema em
que o token em claro é acessível fora do e-mail, e os testes são o consumidor legítimo disso.

Helper local, para os dois arquivos:

```php
/** Cria um convite pendente e devolve o par [convite, token em claro]. */
function conviteCom(string $papel, ?Tenant $tenant = null, ?string $email = null): array
{
    $convite = Convite::factory()->create([
        'email'     => $email ?? 'convidado@example.com',
        'role_id'   => Role::findByName($papel)->getKey(),
        'tenant_id' => $tenant?->getKey(),
    ]);

    return [$convite, $convite->enviar()];
}
```

### Estratégia de Mock

| Mock | Onde | Para quê |
| --- | --- | --- |
| `Notification::fake()` | CT-01, CT-08, CT-09 | assere o disparo sem tocar no mailer. Como o destinatário é on-demand, a asserção é `assertSentOnDemand(ConviteDeAcesso::class, fn ($n, $channels, $notifiable) => ...)` — o `$notifiable` é um `AnonymousNotifiable` e o e-mail se lê em `$notifiable->routes['mail']` |
| `Log::spy()` | CT-04, CT-10 | verifica channel, nível, prefixo `[Classe@Método]` e context |

Nada mais é mockado. A fila roda em `sync` no ambiente de teste
(`phpunit.xml`, `<env name="QUEUE_CONNECTION" value="sync"/>`), então a `Notification` com
`ShouldQueue` é entregue inline — **é o que permite a `Notification::fake()` vê-la**. Com
`database` seria preciso `Queue::fake()` também. O mailer é `array` no `phpunit.xml`, então
nada sai da máquina mesmo sem o fake.

---

## CT-01: convite criado dispara a notificação para o e-mail convidado

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('cria convite pela tela e dispara a notificacao')`

### Precondições

- Seeders no `beforeEach`.
- `Notification::fake()`.
- Usuário `master_global` autenticado.

### Dados de Entrada

```php
livewire(CreateConvite::class)
    ->fillForm([
        'email'   => 'novo@example.com',
        'role_id' => Role::findByName('panel_user')->getKey(),
    ])
    ->call('create')
    ->assertHasNoFormErrors();
```

### Resultado Esperado

- `assertDatabaseHas('convites', ['email' => 'novo@example.com', 'aceito_em' => null])`.
- `Notification::assertSentOnDemand(ConviteDeAcesso::class, fn ($n, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'novo@example.com')`.
- O convite gravado tem `token` **não vazio** e `expira_em` no futuro — prova que o
  `afterCreate()` chamou `enviar()`, e não só que a linha nasceu. Que a coluna guarda o
  **hash** e não o token é CT-08, onde o token em claro está em mãos.
- `convidado_por_id` é o id do usuário autenticado.

---

## CT-02: token inválido não cadastra ninguém

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('recusa registro com token inexistente')`

### Precondições

- Seeders no `beforeEach`.
- Nenhum convite no banco.

### Dados de Entrada

```
get('/app/register?token='.Str::random(64))
get('/app/register')            // sem token nenhum
```

### Resultado Esperado

- Os dois respondem `assertRedirect(Filament::getPanel('app')->getLoginUrl())`.
- `User::count()` permanece igual ao de antes (só o do setup, se houver).
- **Não** responde `200` — se um dia responder, a tela de cadastro virou pública. Este é o
  caso central da feature: sem ele, a guarda do `mount()` pode desaparecer num refactor e
  nada acusa.
- O caso sem token existe separado porque `blank($token)` é o primeiro `return null` de
  `Convite::valido()`, um branch próprio.

---

## CT-03: token expirado não cadastra ninguém

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('recusa registro com convite expirado')`

### Precondições

- Seeders no `beforeEach`.
- `[$convite, $token] = conviteCom('panel_user');`
- `$convite->forceFill(['expira_em' => now()->subMinute()])->save();`

### Dados de Entrada

```
get("/app/register?token={$token}")
```

### Resultado Esperado

- `assertRedirect` para o login — **a mesma resposta de CT-02**, byte a byte. É o que ADR-02
  chama de resposta uniforme: quem tem o link não descobre se o token não existe ou se
  venceu.
- `User::where('email', $convite->email)->doesntExist()`.
- `$convite->fresh()->aceito_em` continua `null`.

---

## CT-04: token já usado não cadastra de novo, e a recusa vira log sem o token

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('recusa reuso do convite e loga sem expor o token')`

### Precondições

- Seeders no `beforeEach`.
- `Log::spy()`.
- `[$convite, $token] = conviteCom('panel_user');`
- `$convite->forceFill(['aceito_em' => now()])->save();`

### Dados de Entrada

```
get("/app/register?token={$token}")
```

### Resultado Esperado

- `assertRedirect` para o login — de novo, a mesma resposta.
- `Log::shouldHaveReceived('channel')->with('autenticacao')` ao menos uma vez.
- O `warning` recebido tem mensagem começando por `[RegistroPorConvite@mount]` e context com
  `motivo = 'convite_invalido'` e `ip` preenchido.
- **O context NÃO contém o token**, em nenhuma forma: nem `$token`, nem
  `hash('sha256', $token)`, nem prefixo de nenhum dos dois. A asserção é sobre o array
  serializado inteiro:
  `expect(json_encode($context))->not->toContain($token)->not->toContain(hash('sha256', $token))`.
- É a tradução literal da regra LGPD do cabeçalho de `config/logging.php:80-81`. Sem este
  caso, um `$context` "mais completo" acrescentado por boa intenção vazaria a credencial no
  arquivo que o Logs Explorer do `/infra` exibe na tela.

---

## CT-05: aceite cria o usuário com o papel no contexto certo

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('aceita o convite e cria o usuario com o papel')`

### Precondições

- Seeders no `beforeEach`.
- `[$convite, $token] = conviteCom('panel_user', email: 'aceito@example.com');`

### Dados de Entrada

```php
Livewire::withQueryParams(['token' => $token])
    ->test(RegistroPorConvite::class)
    ->fillForm([
        'name'                 => 'Fulano',
        'password'             => 'segredo-bem-longo-123',
        'passwordConfirmation' => 'segredo-bem-longo-123',
    ])
    ->call('register')
    ->assertHasNoFormErrors();
```

> **Resolvido na implementação**: o token vai por QUERY STRING, nunca pelo construtor.
> `RegistroPorConvite::mount()` é `mount(): void` e lê `request()->query('token')` — um
> `livewire(…, ['token' => …])` não tem onde entregar o valor, e o `mount()` cairia no ramo
> de convite inválido. No componente, `Livewire::withQueryParams([...])->test(...)` (ver o
> helper `aceitarConvite()` em `tests/Kit/ConviteTest.php`); quando o que se prova é um
> REDIRECT do `mount()`, o caminho é o request HTTP `get("/app/register?token={$token}")`,
> porque a saída é `HttpResponseException` e é o request que a expõe.

### Resultado Esperado

- `assertDatabaseHas('users', ['email' => 'aceito@example.com', 'name' => 'Fulano'])`.
- O usuário criado tem o papel `panel_user`: `$novo->hasRole('panel_user')` é `true`.
- **O papel está no contexto global**: sem `permission.teams` não há coluna de team, então a
  asserção é `assertDatabaseHas('model_has_roles', ['model_id' => $novo->id])` — o contexto é
  travado de verdade em CT-11 e CT-12, onde a coluna existe.
- `$novo->canAccessPanel(Filament::getPanel('app'))` é `true` — o teste que prova que o
  convite entregou **acesso**, não só um registro.
- `$convite->fresh()->aceito_em` **não** é nulo.
- A senha foi hasheada: `Hash::check('segredo-bem-longo-123', $novo->password)` é `true`
  (vem de graça de `Register.php:228`, mas é o que garante que o `handleRegistration()`
  sobrescrito não passou por cima).

---

## CT-06: o e-mail do usuário criado vem do convite, não do formulário

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('ignora o email enviado pelo formulario e usa o do convite')`

### Precondições

- Seeders no `beforeEach`.
- `[$convite, $token] = conviteCom('panel_user', email: 'verdadeiro@example.com');`

### Dados de Entrada

```php
livewire(RegistroPorConvite::class, ['token' => $token])
    ->fillForm([
        'name'                 => 'Fulano',
        'email'                => 'atacante@example.com',   // estado de Livewire é do cliente
        'password'             => 'segredo-bem-longo-123',
        'passwordConfirmation' => 'segredo-bem-longo-123',
    ])
    ->call('register');
```

### Resultado Esperado

- `assertDatabaseHas('users', ['email' => 'verdadeiro@example.com'])`.
- `User::where('email', 'atacante@example.com')->doesntExist()`.
- Prova que a autoridade é `mutateFormDataBeforeRegister()` (passo 4c do plano) e **não** o
  `->disabled()` do campo. Campo desabilitado é apresentação; estado de Livewire chega do
  cliente. Sem este caso, alguém "simplifica" removendo o `mutate` porque "o campo já está
  travado".

---

## CT-07: aceite vincula a organização do convite

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteTenancyTest.php`
**Método**: `it('vincula o usuario a organizacao do convite')`

### Precondições

- Seeders no `beforeEach`.
- `$acme = Tenant::factory()->create(['slug' => 'acme']);`
- `$globex = Tenant::factory()->create(['slug' => 'globex']);`
- `[$convite, $token] = conviteCom('panel_user', $acme);`

### Dados de Entrada

O mesmo `livewire(RegistroPorConvite::class, ...)->call('register')` de CT-05.

### Resultado Esperado

- `assertDatabaseHas('tenant_user', ['tenant_id' => $acme->id, 'user_id' => $novo->id])`.
- `assertDatabaseMissing('tenant_user', ['tenant_id' => $globex->id, 'user_id' => $novo->id])`.
- `$novo->getTenants(Filament::getPanel('app'))` contém `acme` e **não** contém `globex`.

---

## CT-08: reenvio gera token novo e invalida o antigo

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('reenvia com token novo e mata o anterior')`

### Precondições

- Seeders no `beforeEach`.
- `Notification::fake()`.
- `[$convite, $tokenAntigo] = conviteCom('panel_user');`

### Dados de Entrada

```php
$tokenNovo = $convite->enviar();
```

### Resultado Esperado

- `$tokenNovo !== $tokenAntigo`.
- `Convite::valido($tokenNovo)?->is($convite)` é `true`.
- **`Convite::valido($tokenAntigo)` é `null`** — o link antigo morreu. É a propriedade que
  ADR-04 usa para justificar "reenviar em vez de editar": o convite anterior deixa de valer
  sem coluna de revogação.
- A coluna guarda o hash do novo: `$convite->fresh()->token === hash('sha256', $tokenNovo)`.
  É a asserção que prova ADR-02 na prática — o token em claro não está no banco.
- `expira_em` foi renovado (`> ` o valor anterior).
- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2)` — o do
  `conviteCom()` e o do reenvio.
- Repetir pela tela, com a ação do Resource:
  `livewire(ListConvites::class)->callAction(TestAction::make('reenviar')->table($convite))->assertNotified()`.

---

## CT-09: revogar apaga o convite e o link para de funcionar

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('revoga o convite e o link deixa de valer')`

### Precondições

- Seeders no `beforeEach`.
- Usuário `master_global` autenticado.
- `[$convite, $token] = conviteCom('panel_user');`

### Dados de Entrada

```php
livewire(ListConvites::class)
    ->callAction(TestAction::make('delete')->table($convite));
```

### Resultado Esperado

- `assertDatabaseMissing('convites', ['id' => $convite->id])`.
- `Convite::valido($token)` é `null`.
- `get("/app/register?token={$token}")` responde `assertRedirect` para o login — a revogação
  fecha a porta de verdade, não só some da listagem.
- A trilha existe: `assertDatabaseHas('audits', ['auditable_type' => Convite::class, 'auditable_id' => $convite->id, 'event' => 'deleted'])`.
- **A trilha não guarda o hash**: o `old_values` do registro de auditoria não contém a chave
  `token`, porque ela está fora do `$fillable` e `AuditsFillables::getAuditInclude()` devolve
  o `$fillable` (`wikis/convencoes.md:30-41`).

---

## CT-10: envio e aceite viram log no channel `autenticacao`, sem token e com e-mail mascarado

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('registra envio e aceite no channel autenticacao sem vazar segredo')`

### Precondições

- Seeders no `beforeEach`.
- `Log::spy()`.
- `Notification::fake()`.

### Dados de Entrada

```php
[$convite, $token] = conviteCom('panel_user', email: 'fulano@example.com');
// ... e depois o aceite completo, como em CT-05
```

### Resultado Esperado

- `Log::shouldHaveReceived('channel')->with('autenticacao')` ao menos duas vezes.
- Um `info` com mensagem começando por `[Convite@enviar]`, context com `convite_id`,
  `role_id`, `papel`, `painel` e `expira_em`.
- Um `info` com mensagem começando por `[Convite@aceitar]`, context com `user_id` e
  `contexto_papel`.
- **`email` no context vem mascarado**: vale `Str::mask('fulano@example.com', '*', 3)`, e
  **não** `'fulano@example.com'`. A asserção é sobre o context serializado:
  `expect(json_encode($context))->not->toContain('fulano@example.com')`.
- Nenhuma das duas mensagens, nem os dois contexts, contêm `$token` ou
  `hash('sha256', $token)`.
- Espelha o par que já existe para `canAccessTenant` (`tests/Tenancy/TenancyTest.php:94-110`).

---

## CT-11: com tenancy, papel de `/app` é atribuído no contexto da organização

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteTenancyTest.php`
**Método**: `it('atribui papel de app no contexto da organizacao do convite')`

### Precondições

- Seeders no `beforeEach`.
- `$acme = Tenant::factory()->create(['slug' => 'acme']);`
- `[$convite, $token] = conviteCom('panel_user', $acme);`

### Dados de Entrada

O aceite completo de CT-05.

### Resultado Esperado

- `assertDatabaseHas('model_has_roles', ['model_id' => $novo->id, 'role_id' => Role::findByName('panel_user')->getKey(), 'team_id' => $acme->id])`.
- **`team_id` é o id da organização, não `Tenant::CONTEXTO_GLOBAL` (`0`)** — é a metade de
  ADR-07 que faz o painel de negócio funcionar.
- `assertDatabaseMissing('model_has_roles', ['model_id' => $novo->id, 'team_id' => Tenant::CONTEXTO_GLOBAL])`.

---

## CT-12: com tenancy, papel de painel sem tenancy vai para o contexto global

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteTenancyTest.php`
**Método**: `it('atribui papel de admin no contexto global mesmo com organizacao no convite')`

### Precondições

- Seeders no `beforeEach`.
- `$acme = Tenant::factory()->create(['slug' => 'acme']);`
- `[$convite, $token] = conviteCom('admin', $acme);` — convite de `admin` **com** organização
  preenchida, de propósito.

### Dados de Entrada

O aceite completo de CT-05.

### Resultado Esperado

- `assertDatabaseHas('model_has_roles', ['model_id' => $novo->id, 'team_id' => Tenant::CONTEXTO_GLOBAL])`.
- `assertDatabaseMissing('model_has_roles', ['model_id' => $novo->id, 'team_id' => $acme->id])`.
- `$novo->canAccessPanel(Filament::getPanel('admin'))` é `true`.
- Este é o caso de **segurança** de ADR-07, e por isso o convite carrega uma organização que
  deve ser ignorada para o papel: se alguém "simplificar" `aceitar()` para usar sempre o
  `tenant_id` do convite, o papel `admin` nasceria dentro da Acme, `canAccessPanel('admin')`
  devolveria `false` (ADR-04 da wiki irmã exige contexto global) e o usuário viraria um
  administrador que não administra. O caso falha alto nas duas pontas.

---

## CT-13: a tela de aceite usa o layout do login, e não contamina o resto do painel

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('veste o layout do auth designer sem vazar para as outras paginas')`

### Precondições

- Seeders no `beforeEach`.
- `[$convite, $token] = conviteCom('panel_user');`
- Um segundo usuário, com `panel_user`, para abrir uma página comum depois.

### Dados de Entrada

```
get("/app/register?token={$token}")           // 1º
actingAs($outro)->get('/app')                 // 2º, no MESMO processo
```

### Resultado Esperado

- O primeiro responde `200` e o HTML contém `fi-auth-layout`.
- O segundo responde `200` e o HTML **não** contém `fi-auth-layout`.
- **O par é obrigatório**, e a ordem importa: é a regra de `.ai/rules/auth.md:13`. Um caso só
  (o primeiro) passaria mesmo com o layout vazando para toda página Filament do processo —
  que é exatamente o bug que a redeclaração de `$layout` previne e que já matou a página de
  2FA do Breezy. Espelha `tests/Kit/BloqueioDeSessaoTest.php`.
- Bônus da mesma requisição: o HTML do primeiro contém a mídia do login
  (`images/auth/login.svg`). É o que acusa ADR-06 — se o registro for ligado fora do
  `AuthDesignerPlugin`, a config cai em `new AuthPageConfig`
  (`vendor/caresome/filament-auth-designer/src/AuthDesignerConfigRepository.php:80`) e a
  imagem some, **sem erro nenhum**.

---

## CT-14: a tela de login não oferece "Cadastre-se"

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('nao oferece cadastro na tela de login')`

### Precondições

Nenhuma além do boot da aplicação.

### Dados de Entrada

```
get('/app/login')
```

### Resultado Esperado

- `200`.
- O HTML **não** contém a URL de registro (`Filament::getPanel('app')->getRegistrationUrl()`),
  nem o texto do link de cadastro.
- `Filament::getPanel('app')->hasRegistration()` é `true` — ou seja, a rota existe e mesmo
  assim o link não aparece. As duas asserções juntas são o ponto: sem a segunda, o teste
  passaria por o registro estar desligado.
- Cobre o efeito colateral de `Login::getSubheading()`
  (`vendor/filament/filament/src/Auth/Pages/Login.php:445-455`) e a convenção de
  `wikis/convencoes.md:84` ("nada de affordance sem permissão").

---

## CT-15: e-mail já cadastrado é CONVIDADO, não recusado

> **Invertido em 2026-08-23.** Este caso provava as duas barreiras contra e-mail já cadastrado
> — o `->unique('users','email')` do form e a `RuntimeException` do `aceitar()`. A feature
> `convite-para-usuario-existente` **removeu as duas de propósito**, e a tabela "Impacto em
> Features Existentes" do plano dela (`01:155`) previu esta inversão por escrito. O teste real
> mudou de nome na mesma entrega (`it('convida quem ja tem conta em vez de recusar')`) e este
> documento ficou para trás. O que se prova hoje: o convite **é criado** para endereço
> existente, e o aceite **vincula** em vez de recusar.

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('recusa email ja cadastrado ao convidar e ao aceitar')`

### Precondições

- Seeders no `beforeEach`.
- Usuário `master_global` autenticado.
- `User::factory()->create(['email' => 'existente@example.com']);`

### Dados de Entrada

```php
// ponta 1 — o formulário de convite
livewire(CreateConvite::class)
    ->fillForm(['email' => 'existente@example.com', 'role_id' => $panelUser->id])
    ->call('create');

// ponta 2 — a corrida: convite emitido ANTES de o e-mail virar usuário
[$convite, $token] = conviteCom('panel_user', email: 'corrida@example.com');
User::factory()->create(['email' => 'corrida@example.com']);
// ... e então o aceite
```

### Resultado Esperado

- Ponta 1: `->assertHasFormErrors(['email'])` e `Convite::where('email', 'existente@example.com')->doesntExist()`.
  Dizer "já cadastrado" **aqui** é correto: quem preenche é um administrador que já pode
  buscar o e-mail em `/admin/users` (ADR-02, item 8).
- Ponta 2: o aceite não cria um segundo usuário —
  `User::where('email', 'corrida@example.com')->count()` continua `1` — e `aceito_em`
  permanece `null`.
- Ponta 2 emite `warning` com `[Convite@aceitar]` e `motivo = 'email_ja_cadastrado'`, com o
  e-mail mascarado.

---

## CT-16: a rota de aceite não fica atrás do segmento de organização

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteTenancyTest.php`
**Método**: `it('mantem a url de aceite fora do segmento de organizacao')`

### Precondições

- Seeders no `beforeEach`.
- `[$convite, $token] = conviteCom('panel_user', Tenant::factory()->create(['slug' => 'acme']));`

### Dados de Entrada

```php
Filament::getPanel('app')->route('auth.register', ['token' => $token]);
```

### Resultado Esperado

- A URL contém `/app/register` e **não** contém `/acme`.
- `get("/app/register?token={$token}")` responde `200` **com a tenancy ligada** — sem estar
  autenticado e sem organização na URL.
- Prova a leitura de `vendor/filament/filament/routes/web.php`: o bloco de registro está em
  `:54-57`, dentro do `->prefix($panel->getPath())` de `:30`, e **fora** do grupo do tenant,
  que só começa em `:119-137`. É isso que torna o link do convite auto-suficiente — a
  organização vem do token, não do endereço.
- Se um upgrade do Filament mover o bloco para dentro do grupo do tenant, todo link de
  convite já enviado passa a dar 404. Este caso é o alarme.

---

## Índice de Casos

| ID | Cenário | Tipo | Arquivo |
| --- | --- | --- | --- |
| CT-01 | convite criado dispara a notificação | Feature | `tests/Kit/ConviteTest.php` |
| CT-02 | token inválido / ausente nega | Feature | `tests/Kit/ConviteTest.php` |
| CT-03 | token expirado nega | Feature | `tests/Kit/ConviteTest.php` |
| CT-04 | token já usado nega, e o log não tem o token | Feature | `tests/Kit/ConviteTest.php` |
| CT-05 | aceite cria usuário com o papel | Feature | `tests/Kit/ConviteTest.php` |
| CT-06 | e-mail vem do convite, não do formulário | Feature | `tests/Kit/ConviteTest.php` |
| CT-07 | aceite vincula a organização do convite | Feature | `tests/Tenancy/ConviteTenancyTest.php` |
| CT-08 | reenvio gera token novo e mata o antigo | Feature | `tests/Kit/ConviteTest.php` |
| CT-09 | revogação apaga e fecha a porta | Feature | `tests/Kit/ConviteTest.php` |
| CT-10 | log no channel `autenticacao`, mascarado | Feature | `tests/Kit/ConviteTest.php` |
| CT-11 | papel de `/app` no contexto da organização | Feature | `tests/Tenancy/ConviteTenancyTest.php` |
| CT-12 | papel de `/admin` no contexto global | Feature | `tests/Tenancy/ConviteTenancyTest.php` |
| CT-13 | layout do auth designer, sem vazamento | Feature | `tests/Kit/ConviteTest.php` |
| CT-14 | login não oferece "Cadastre-se" | Feature | `tests/Kit/ConviteTest.php` |
| CT-15 | e-mail já cadastrado é convidado, não recusado | Feature | `tests/Kit/ConviteTest.php` |
| CT-16 | URL de aceite fora do segmento de organização | Feature | `tests/Tenancy/ConviteTenancyTest.php` |

### Cobertura dos métodos públicos

| Método | CTs |
| --- | --- |
| `Convite::enviar()` | CT-01, CT-08, CT-10 |
| `Convite::valido()` | CT-02 (inexistente + vazio), CT-03 (expirado), CT-04 (usado), CT-08, CT-09 |
| `Convite::aceitar()` | CT-05, CT-06, CT-07, CT-11, CT-12, CT-15 |
| `RegistroPorConvite::mount()` | CT-02, CT-03, CT-04, CT-13, CT-16 |
| `RegistroPorConvite::mutateFormDataBeforeRegister()` | CT-06 |
| `RegistroPorConvite::handleRegistration()` | CT-05 |
| `TelaLogin::getSubheading()` | CT-14 |
| Ações `reenviar` / `delete` | CT-08, CT-09 |

`ConviteResource::getPages()` não tem CT: asserir as chaves de um array literal escrito no
mesmo commit não é teste de comportamento. A razão de não existir `edit` fica no PHPDoc do
Resource (ADR-04).

### Rodar antes de implementar

CT-02 e CT-14 devem ser escritos e **vistos falhando** antes de qualquer código:

- CT-02 falha porque a rota `/app/register` ainda não existe (404 em vez de redirect) — prova
  que o teste está apontando para o lugar certo antes de a guarda existir;
- CT-14 falha se o passo 6 for feito sem o passo 5 — que é a janela exata em que o
  "Cadastre-se" aparece no login. Ver o teste ficar vermelho nessa janela é o que prova que
  ele detecta o efeito colateral, em vez de passar por acidente.

### Testes existentes a reconferir

| Arquivo | Por quê |
| --- | --- |
| `tests/Kit/KitUpdateTest.php` | falha até `app/Models/Convite.php` e `app/Notifications` entrarem em `CAMINHOS_DO_KIT` (passo 10) — rodar antes da correção, para vê-lo acusar |

Nada mais a reconferir à mão: `composer test:kit` roda as duas suítes inteiras, e um caso que
quebre aparece sozinho.
