# Casos de Teste — Convite em massa

## Setup Global

### Estratégia de DB

`RefreshDatabase`, herdado do `tests/Pest.php` (`:34-37` para `tests/Kit`, `:58-61` para
`tests/Tenancy`). Não há escolha: o modo de tenancy muda o schema, e `Tests\TestCase::setUp()`
invalida `RefreshDatabaseState::$migrated` quando o modo troca (`tests/TestCase.php:128-136`).

| Arquivo | TestCase | Modo | Casos |
| --- | --- | --- | --- |
| `tests/Kit/ConviteEmMassaTest.php` | `Tests\TestCase` | single-tenant | CT-01 a CT-04, CT-06 a CT-08, CT-10 a CT-13, CT-15 |
| `tests/Tenancy/ConviteEmMassaTenancyTest.php` | `Tests\TenancyTestCase` | multi-tenant | CT-05, CT-09, CT-14 |
| `tests/Kit/PaineisTest.php` (**já existe**) | `Tests\TestCase` | single-tenant | CT-16 |

As duas pastas já entram no grupo `kit`, então `composer test:kit` cobre todas.

**CT-16 não mora nos arquivos do lote**: é do passo 7 do plano, que corrige a subtração do
`panel_user`. O lugar dele é onde o mapa painel × permissão já é testado
(`tests/Kit/PaineisTest.php:135-144`), com o `beforeEach` que aquele arquivo já tem (`:18-20`).

### Seeders no `beforeEach`

```php
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});
```

Mesmo padrão de `tests/Kit/ConviteTest.php:36-38`. **Obrigatório**: sem o primeiro não existe
permission no banco, e CT-12 passaria por motivo errado. `Tests\TestCase::seed()` usa
`Artisan::call` (`tests/TestCase.php:158-169`) porque o `seed()` do Laravel engole o
`shield:generate` aninhado e grava zero permissions.

### Helpers

**Compartilhados** — vêm de `tests/Pest.php` depois do passo 8 do plano, que é onde está o motivo
(nome de função de teste é global na suíte e inexistente no arquivo isolado):

| Helper | Origem | Para quê |
| --- | --- | --- |
| `usuarioDoKit(string $papel, string $email)` | hoje em `tests/Kit/ConviteTest.php:40-47` | autenticar quem convida |
| `espiarAutenticacao(): LoggerInterface` | hoje em `tests/Kit/ConviteTest.php:91-98` | espia **só** o channel `autenticacao` |
| `usuarioComPapel(string $papel, ?Tenant $tenant, string $email)` | `tests/Tenancy/TenancyTest.php:45` | persona com papel no contexto certo |

**Local**, com nome que não colide com os de `ConviteTest.php`:

```php
/** Chama a ação de header do /admin e devolve o Testable. */
function chamarLote(string $emails, ?string $papel = 'panel_user', array $extra = []): Testable
{
    Filament::setCurrentPanel('admin');

    return Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('convidarEmMassa'), [
            'emails'  => $emails,
            'role_id' => Role::findByName((string) $papel)->getKey(),
            ...$extra,
        ]);
}
```

`callAction(string | TestAction | array $actions, array $data = [], array $arguments = [])`
(`vendor/filament/actions/src/Testing/TestsActions.php:78-80`) — o segundo argumento é o state do
formulário da modal.

> **Atenção**: `callAction()` chama `assertActionVisible()` por dentro (`:83-84`). Ação escondida
> por autorização **falha ali**, com mensagem de visibilidade e não de permissão — é o que faz
> CT-12 usar `assertActionHidden()` em vez de tentar chamar a ação.

### Estratégia de Mock

| Mock | Onde | Nota |
| --- | --- | --- |
| `Notification::fake()` | CT-01 a CT-07, CT-09, CT-11 a CT-14 | `assertSentOnDemand(...)` com `$notifiable->routes['mail']`, porque o destinatário é `AnonymousNotifiable` (`tests/Kit/ConviteTest.php:117-120`) |
| `espiarAutenticacao()` | CT-08, CT-10 | channel, nível, prefixo `[Classe@Método]` e o `$context` inteiro serializado |
| **nenhum fake de mail** | **CT-10** | o caso precisa que o envio **estoure de verdade**. `MAIL_MAILER=array` (`phpunit.xml:41`) já impede qualquer saída da máquina |

A fila é `sync` (`phpunit.xml:42`), então a notificação `ShouldQueue` roda inline — é o que permite
ao `Notification::fake()` vê-la, e o que faz a exceção de CT-10 chegar ao nosso `catch`:
`SyncQueue::handleException()` relança.

---

## CT-01: lote todo válido cria um convite por endereço

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('convida todos os enderecos de um lote valido')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`.
- `usuarioDoKit('master_global')` autenticado, painel `admin`.

### Dados de Entrada

```php
chamarLote("um@example.com\ndois@example.com\ntres@example.com");
```

### Resultado Esperado

- `Convite::count()` é `3`, e os três têm o `role_id` de `panel_user`.
- Os três têm `token` **não vazio** e `expira_em` no futuro — prova que o laço chamou `enviar()`
  (`app/Models/Convite.php:124-152`) e não só gravou a linha.
- `convidado_por_id` é o id do autenticado nos três.
- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 3)`.
- `->assertNotified()` — o resumo de sucesso na tela.

---

## CT-02: um endereço inválido não impede os outros — o ponto da feature

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('envia os validos mesmo com um endereco torto no meio')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`; `master_global` autenticado.

### Dados de Entrada

```php
chamarLote("um@example.com\nnao-e-email\ntres@example.com")
    ->assertHasNoActionErrors();
```

### Resultado Esperado

- `Convite::count()` é **2** (`um@` e `tres@`); `Convite::where('email', 'nao-e-email')->doesntExist()`.
- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2)`.
- **Nenhum erro de formulário na modal.** Se `assertHasNoActionErrors()` falhar, alguém
  acrescentou `->email()` ou `->nestedRecursiveRules()` no campo e o resultado parcial morreu
  (ADR-05).
- Pelo model, para asserir a forma do retorno:
  `Convite::convidarEmMassa(Convite::separarEmails("a@b.com\nxxx"), $roleId, null, null)` devolve
  `['enviados' => ['a@b.com'], 'falhas' => [['email' => 'xxx', 'motivo' => 'formato_invalido']]]`.
- **É o caso central**: sem ele a feature vira tudo-ou-nada num refactor e nada acusa.

---

## CT-03: endereço com convite pendente é pulado, e o convite antigo continua valendo

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('pula endereco que ja tem convite pendente')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`.
- Um convite pendente para `repetida@example.com`, com o token em mãos:
  `Convite::factory()->create([...])` + `$token = $convite->enviar()`.

### Dados de Entrada

```php
Convite::convidarEmMassa(
    Convite::separarEmails("repetida@example.com\nnova@example.com"),
    $roleId, null, null,
);
```

### Resultado Esperado

- `enviados` tem só `nova@example.com`; `falhas` tem
  `['email' => 'repetida@example.com', 'motivo' => 'convite_pendente']`.
- `Convite::where('email', 'repetida@example.com')->count()` continua **1**.
- **O token antigo continua válido**: `Convite::valido($token)?->is($convite)` é `true`. O lote não
  pode invalidar o link de quem já foi convidado — é por isso que ele **pula** em vez de chamar
  `enviar()` de novo, o que sobrescreveria a coluna (`app/Models/Convite.php:128-132`).
- Um convite **expirado** para o mesmo endereço **não** bloqueia: repetir com
  `forceFill(['expira_em' => now()->subDay()])` e esperar `enviados` com o endereço. É a mesma
  noção de pendente de `Convite::valido()` (`:162-174`), e é o que impede as duas definições de
  divergirem (ADR-03).

---

## CT-04: endereço que já tem conta é SUCESSO, não falha

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('convida quem ja tem conta como oferta de acesso')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`.
- `User::factory()->create(['email' => 'existente@example.com'])`.

### Dados de Entrada

```php
chamarLote("existente@example.com\nnova@example.com");
```

### Resultado Esperado

- `Convite::count()` é **2** — o endereço com conta gerou convite como qualquer outro.
- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2)`.
- `falhas` está **vazio**. Nenhum motivo `email_ja_cadastrado` existe mais.
- É a maior diferença de comportamento em relação ao `invite-only` (ADR-03), e só passa porque a
  wiki `convite-para-usuario-existente` já removeu o `unique` do form do `/admin` e o `throw` do
  `aceitar()`.

---

## CT-05: quem já é membro da organização é pulado

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteEmMassaTenancyTest.php`
**Método**: `it('pula quem ja e membro da organizacao do lote')`

### Precondições

- Seeders no `beforeEach`.
- `$acme` e `$globex` (`Tenant::factory()`).
- `$dentro` = `dentro@example.com` **vinculada à acme**; `$fora` = `fora@example.com` vinculada
  **só à globex**.

### Dados de Entrada

```php
Convite::convidarEmMassa(
    Convite::separarEmails("dentro@example.com\nfora@example.com\nnova@example.com"),
    $roleId, $acme->getKey(), null,
);
```

### Resultado Esperado

- `falhas` tem exatamente `['email' => 'dentro@example.com', 'motivo' => 'ja_e_membro']`.
- `enviados` tem `fora@example.com` **e** `nova@example.com` — ser membro de **outra** organização
  não é motivo de nada: é justamente o caso de uso da feature (a consultora com dois clientes).
- `Convite::count()` é `2`.
- O mesmo lote com `tenantId = null` (em `tests/Kit/…`) **não** pula ninguém (ADR-03).

---

## CT-06: lote acima do limite não envia nada e a modal continua aberta

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('recusa o lote inteiro acima do limite sem enviar nada')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`; `master_global` autenticado.
- `config(['kit.convites.limite_do_lote' => 3])` — o limite é lido de config, então o caso o
  aperta em vez de gerar 101 endereços.

### Dados de Entrada

```php
chamarLote("a@example.com\nb@example.com\nc@example.com\nd@example.com");
```

### Resultado Esperado

- `Convite::count()` é **0**: lote acima do limite é entrada inválida, e a resposta é não começar
  (ADR-04).
- `Notification::assertNothingSent()`; `->assertNotified()` com a notificação `danger` de limite.
- **A modal não fechou**: `->assertActionMounted('convidarEmMassa')`
  (`vendor/filament/actions/src/Testing/TestsActions.php:411`), porque a ação foi interrompida por
  `$action->halt()`. É a metade do caso que importa para quem colou cento e vinte linhas.
- Com **exatamente** o limite (três endereços), envia os três — a comparação é `>`, não `>=`.

---

## CT-07: uma notificação por endereço bem-sucedido, e nenhuma para os pulados

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('dispara uma notificacao por endereco enviado e nenhuma para os pulados')`

### Precondições

- Seeders no `beforeEach`.
- Um convite pendente para `repetida@example.com` (factory + `enviar()`), com o
  `Notification::fake()` ligado **depois** dele, para que a contagem seja só a do lote.

### Dados de Entrada

```php
Convite::convidarEmMassa(
    Convite::separarEmails("repetida@example.com\nnao-e-email\nnova@example.com"),
    $roleId, null, null,
);
```

### Resultado Esperado

- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1)` — só `nova@`, e
  `assertSentOnDemand(..., fn ($n, $canais, $notifiable): bool => $notifiable->routes['mail'] === 'nova@example.com')`.
- Trava o par "N enviados ⇒ N e-mails": ninguém pulado recebe e-mail.

---

## CT-08: o resumo do lote vira log no channel `autenticacao`, mascarado e sem token

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('registra o resumo do lote sem vazar endereco nem token')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`; `$canal = espiarAutenticacao();`
- Um convite pendente para `repetida@example.com`.

### Dados de Entrada

```php
Convite::convidarEmMassa(
    Convite::separarEmails("repetida@example.com\nnao-e-email\nnova@example.com"),
    $roleId, null, $master->id,
);
```

### Resultado Esperado

- Um `info` com mensagem começando por `[Convite@convidarEmMassa]`, e `$context` com `recebidos`
  = 3, `enviados` = 1, `falhas` = 2, `motivos` = `['convite_pendente' => 1, 'formato_invalido' => 1]`
  (o `countBy`) e `convidado_por` = o id de quem operou.
- **Nenhum endereço em claro no `$context` serializado**:
  `expect(json_encode($contexto))->not->toContain('nova@example.com')->not->toContain('repetida@example.com')`,
  e `com_falha` contendo `Str::mask('repetida@example.com', '*', 3)`. A lista de falhas é onde o
  descuido é mais provável, porque ela é o produto do método.
- **Nenhum token, em forma nenhuma**: nem em claro, nem `hash('sha256', …)`. A asserção é sobre o
  `$context` inteiro serializado, como em `tests/Kit/ConviteTest.php:319-327`.
- Um `info` `[Convite@enviar]` por endereço enviado — **um**, não três: o resumo não repete o
  envio.

---

## CT-09: o `admin_organizacao` convida em massa, e o `tenant_id` é carimbado à força

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteEmMassaTenancyTest.php`
**Método**: `it('carimba a organizacao corrente no lote do admin da organizacao')`

### Precondições

- Seeders no `beforeEach`; `$acme` e `$globex` criadas.
- `$ana = usuarioComPapel('admin_organizacao', $acme, 'ana@example.com');`
- `Notification::fake()`; `Filament::setCurrentPanel('app')`; `Filament::setTenant($acme)`;
  `actingAs($ana)`.

### Dados de Entrada

```php
Livewire::test(App\Filament\App\Resources\Convites\Pages\ListConvites::class)
    ->callAction(TestAction::make('convidarEmMassa'), [
        'emails'    => "uma@example.com\noutra@example.com",
        'role_id'   => Role::findByName('panel_user')->getKey(),
        // Forjado no state do Livewire: o formulário do /app NÃO tem este campo.
        'tenant_id' => $globex->getKey(),
    ]);
```

### Resultado Esperado

- Os dois convites nascem com `tenant_id` da **acme**:
  `Convite::pluck('tenant_id')->unique()->all() === [$acme->id]`, e
  `Convite::where('tenant_id', $globex->id)->doesntExist()`.
- É a barreira 6 da wiki `admin-da-organizacao`, e **não vem de graça**: `Convite` tem `tenant_id`
  **dentro** do `$fillable` (`app/Models/Convite.php:59-70`) e não usa `BelongsToTenant`, então o
  mass assignment aceitaria o valor forjado. Quem sobrescreve é o trait do lote, no padrão de
  `app/Filament/App/Resources/Convites/Pages/CreateConvite.php:27-35`.
- `admin_organizacao` tem `Create:Convite` porque recebe a matriz inteira do painel `app`
  (`database/seeders/PapeisSeeder.php:70-73`).

---

## CT-10: o lote NÃO aborta quando o envio de um endereço estoura

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('segue o lote quando o envio de um endereco lanca excecao')`

### Precondições

- Seeders no `beforeEach`; `$canal = espiarAutenticacao();`
- **Sem `Notification::fake()`** — o caso precisa do envio real chegando ao mailer.
  `MAIL_MAILER=array` (`phpunit.xml:41`) e fila `sync` (`:42`): nada sai da máquina e a exceção
  volta pelo `SyncQueue::handleException()`, que relança.
- Um listener que derruba **um** destinatário:

```php
Event::listen(MessageSending::class, function (MessageSending $evento): void {
    $para = $evento->message->getTo()[0]?->getAddress() ?? '';

    if ($para === 'quebra@example.com') {
        throw new RuntimeException('SMTP fora do ar');
    }
});
```

  `Mailer::shouldSendMessage()` chama `events->until(new MessageSending(...))`
  (`vendor/laravel/framework/src/Illuminate/Mail/Mailer.php:602-611`), então o `throw` sobe pelo
  envio.

### Dados de Entrada

```php
$resultado = Convite::convidarEmMassa(
    Convite::separarEmails("antes@example.com\nquebra@example.com\ndepois@example.com"),
    $roleId, null, null,
);
```

### Resultado Esperado

- `enviados` tem **`antes@` e `depois@`** — o endereço **depois** do que estourou é o que prova que
  o laço continuou.
- `falhas` tem `['email' => 'quebra@example.com', 'motivo' => 'erro_no_envio']`.
- `$canal->shouldHaveReceived('warning')` com mensagem começando por
  `[Convite@convidarEmMassa]`, `$context['motivo'] === 'erro_no_envio'`, e-mail mascarado e
  `$context['exception']` presente. **Nada é engolido em silêncio.**
- O convite de `quebra@` **existe** no banco, pendente e com token — o `create()` e o `forceFill`
  acontecem antes da notificação (`app/Models/Convite.php:126-134`). É o failure mode desejado:
  aparece como Pendente e o `Reenviar` por linha resolve (ADR-01).
- **É o caso que existe por causa do defeito do `laravel-invite-only`**: se alguém estreitar o
  `catch (Throwable)`, este caso fica vermelho — e é o único que ficaria.

---

## CT-11: endereço repetido no texto vira um convite só

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('deduplica endereco repetido no proprio texto')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`.

### Dados de Entrada

```php
Convite::convidarEmMassa(
    Convite::separarEmails("uma@example.com, uma@example.com\nUMA@EXAMPLE.COM"),
    $roleId, null, null,
);
```

### Resultado Esperado

- `Convite::count()` é **1**, com `email = 'uma@example.com'` (minúsculas).
- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1)`.
- `enviados` tem um item e `falhas` está **vazio**: repetição não é falha.
- Cobre as três variações juntas: vírgula, quebra de linha e caixa diferente.

---

## CT-12: quem não pode criar convite não vê a ação

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('esconde a acao de lote de quem nao pode criar convite')`

### Precondições

- Seeders no `beforeEach`.
- `$comum = usuarioDoKit('panel_user', …)` — **não** tem `Create:Convite`
  (`database/seeders/PapeisSeeder.php:87-93`); `$admin = usuarioDoKit('admin', …)` — tem.

### Dados de Entrada

```php
Filament::setCurrentPanel('admin');

Livewire::actingAs($admin)->test(ListConvites::class)->assertActionVisible('convidarEmMassa');
Livewire::actingAs($comum)->test(ListConvites::class)->assertActionHidden('convidarEmMassa');
```

### Resultado Esperado

- Visível para `admin`, **escondida** para `panel_user`.
- `expect($comum->can('create', Convite::class))->toBeFalse()` — prova que a tela reflete a
  permission e não uma condição inventada.
- **As duas pontas são obrigatórias**: só a segunda passaria se a ação estivesse escondida para
  todo mundo (por exemplo, com o `->authorize()` apontando para uma habilidade inexistente).
- Sem o `->authorize('create', Convite::class)` este caso fica vermelho — e é a única coisa que
  acusa a affordance sem permissão (ADR-02, `wikis/convencoes.md:84`).

---

## CT-13: quem recusou um convite anterior não é reconvidado pelo lote

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('nao reconvida pelo lote quem recusou antes')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`.
- Um convite para `recusou@example.com` com `recusado_em` preenchido (`recusar($user)` ou
  `forceFill(['recusado_em' => now()])->save()`).

### Dados de Entrada

```php
Convite::convidarEmMassa(
    Convite::separarEmails("recusou@example.com\nnova@example.com"),
    $roleId, null, null,
);
```

### Resultado Esperado

- `falhas` tem `['email' => 'recusou@example.com', 'motivo' => 'recusou_antes']`, e
  `Convite::where('email', 'recusou@example.com')->count()` continua **1**.
- **E o convite individual continua podendo**: `Convite::create(...)` + `enviar()` para o mesmo
  endereço funciona, e `Convite::valido($tokenNovo)` devolve o convite. É a metade que resolve a
  contradição aparente com a wiki irmã: o model permite, o **lote** é que não faz automaticamente
  (ADR-03).

---

## CT-14: papel de outro painel forjado no lote do `/app` é recusado

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteEmMassaTenancyTest.php`
**Método**: `it('recusa papel de outro painel no lote do painel de negocio')`

### Precondições

- Seeders no `beforeEach`; `Notification::fake()`.
- `$acme` criada; `$ana = usuarioComPapel('admin_organizacao', $acme, …)` autenticada, painel
  `app`, tenant `acme`.

### Dados de Entrada

```php
Livewire::test(App\Filament\App\Resources\Convites\Pages\ListConvites::class)
    ->callAction(TestAction::make('convidarEmMassa'), [
        'emails'  => "uma@example.com\noutra@example.com",
        // O papel de /admin, forjado: o Select do /app só OFERECE painel = 'app'.
        'role_id' => Role::findByName('admin')->getKey(),
    ])
    ->assertHasActionErrors(['role_id']);
```

### Resultado Esperado

- `Convite::count()` é **0**, e o erro é de **validação**, vindo do
  `->rule(Rule::exists(roles, 'id')->where('painel', 'app'))` copiado de
  `app/Filament/App/Resources/Convites/ConviteResource.php:121-122`.
- **É o caso mais perigoso da feature**: sem a trava, quem administra **uma** organização criaria
  um lote de trinta `admin` da instalação. A barreira de UX (`:110`, o Select filtrado) não conta —
  state de Livewire chega do cliente.
- Repetir com `role_id` de `panel_user` para provar que o caminho legítimo passa: dois convites.

---

## CT-15: `separarEmails()` cobre os separadores reais

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteEmMassaTest.php`
**Método**: `it('separa e normaliza os enderecos do texto')` — com `dataset`

### Dados de Entrada

```php
dataset('textos', [
    'quebra de linha'  => ["a@x.com\nb@x.com",        ['a@x.com', 'b@x.com']],
    'virgula'          => ['a@x.com, b@x.com',        ['a@x.com', 'b@x.com']],
    'ponto e virgula'  => ['a@x.com;b@x.com',         ['a@x.com', 'b@x.com']],
    'espaco e tab'     => ["a@x.com \t b@x.com",      ['a@x.com', 'b@x.com']],
    'caixa e espacos'  => ["  A@X.com \n a@x.COM  ",  ['a@x.com']],
    'linhas vazias'    => ["a@x.com\n\n\nb@x.com\n",  ['a@x.com', 'b@x.com']],
    'vazio'            => ['',                        []],
    'nulo'             => [null,                      []],
]);
```

### Resultado Esperado

- `Convite::separarEmails($texto)->all()` é igual ao esperado, **na ordem** de entrada
  (`->values()` depois do `unique()`).
- `'caixa e espacos'` trava duas decisões: normalização em minúsculas e deduplicação depois dela.
- `'nulo'` existe porque a assinatura aceita `?string` e o campo pode vir vazio da modal — o parser
  não pode estourar antes de o `->required()` falar.

---

## CT-16: a subtração do `panel_user` alcança Page e Widget, não só Resource

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/PaineisTest.php` — **não** nos arquivos do lote
**Método**: `it('alcanca Page e Widget na subtracao do painel app')`

> **Este caso é do passo 7, que não é da feature de lote.** Mora onde o mapa painel × permissão já
> é testado (`tests/Kit/PaineisTest.php:135-144`), com os seeders do `beforeEach` (`:18-20`), e vai
> no commit do passo 7.

### Precondições

- Os seeders do `beforeEach` que o arquivo já tem. Nenhuma factory: o sujeito é o mapa derivado do
  código.

### Dados de Entrada

```php
$daPagina = Paineis::permissoesDe('app', [Jeffgreco13\FilamentBreezy\Pages\MyProfilePage::class]);
$doResource = Paineis::permissoesDe('app', [App\Filament\App\Resources\Convites\ConviteResource::class]);
$doPanelUser = Role::findByName('panel_user')->permissions->pluck('name');
```

### Resultado Esperado

- **A metade nova**: `$daPagina->all()` contém `'View:MyProfilePage'`. Se a extração de chave de
  Page usar `array_column($e['permissions'], 'key')` — o formato de **Resource** —, a coleção volta
  **vazia** sem erro nenhum, e esta é a única asserção que acusa (ADR-06, decisão 5).
- **A metade antiga continua**: `$doResource->all()` contém `'Create:Convite'` e `'ViewAny:Convite'`.
- **A subtração continua subtraindo o que deve**: `$doPanelUser` **não** contém `'Create:Convite'`.
- **E não subtrai o que não deve**: `$doPanelUser` **contém** `'View:MyProfilePage'` — a página de
  perfil é de todos, e não está na lista de FQCN de administração. É a ponta que prova que o passo
  7 fechou o mecanismo **sem** mudar a matriz de ninguém.
- FQCN que não existe no painel devolve coleção vazia:
  `Paineis::permissoesDe('app', ['App\\Nada'])->isEmpty()`.
- **Nenhuma Page de administração é registrada de mentira**: `Paineis::mapa()` varre
  `Filament::getPanels()` (`app/Support/Paineis.php:108`) e troca/restaura o painel corrente dentro
  do próprio `try/finally` (`:107-121`), então um sujeito falso exigiria um painel de teste inteiro.
  O que pode dar errado no passo 7 é o **alcance** de `permissoesDe()` sobre uma Page, e a asserção
  estrutural trava isso com a Page real que o painel já tem.

---

## Índice de Casos

| ID | Cenário | Arquivo |
| --- | --- | --- |
| CT-01 | lote todo válido | `tests/Kit/ConviteEmMassaTest.php` |
| CT-02 | **um inválido, os outros passam** | `tests/Kit/ConviteEmMassaTest.php` |
| CT-03 | duplicado pendente é pulado, link antigo sobrevive | `tests/Kit/ConviteEmMassaTest.php` |
| CT-04 | e-mail com conta é **sucesso** | `tests/Kit/ConviteEmMassaTest.php` |
| CT-05 | já é membro da organização é pulado | `tests/Tenancy/ConviteEmMassaTenancyTest.php` |
| CT-06 | acima do limite: nada enviado, modal aberta | `tests/Kit/ConviteEmMassaTest.php` |
| CT-07 | uma notificação por enviado, nenhuma por pulado | `tests/Kit/ConviteEmMassaTest.php` |
| CT-08 | resumo do lote no log, mascarado e sem token | `tests/Kit/ConviteEmMassaTest.php` |
| CT-09 | `admin_organizacao` com `tenant_id` carimbado | `tests/Tenancy/ConviteEmMassaTenancyTest.php` |
| CT-10 | **o lote não aborta com exceção no envio** | `tests/Kit/ConviteEmMassaTest.php` |
| CT-11 | repetido no texto vira um só | `tests/Kit/ConviteEmMassaTest.php` |
| CT-12 | ação escondida sem `Create:Convite` | `tests/Kit/ConviteEmMassaTest.php` |
| CT-13 | quem recusou não é reconvidado pelo lote | `tests/Kit/ConviteEmMassaTest.php` |
| CT-14 | papel de outro painel forjado no `/app` | `tests/Tenancy/ConviteEmMassaTenancyTest.php` |
| CT-15 | `separarEmails()` com dataset | `tests/Kit/ConviteEmMassaTest.php` |
| CT-16 | **subtração alcança Page e Widget** (passo 7) | `tests/Kit/PaineisTest.php` |

### Cobertura dos métodos públicos

| Método | CTs |
| --- | --- |
| `Convite::separarEmails()` | CT-11, CT-15 |
| `Convite::convidarEmMassa()` | caminho feliz CT-01, CT-04, CT-05; um motivo por CT — CT-02 `formato_invalido`, CT-03 `convite_pendente`, CT-05 `ja_e_membro`, CT-13 `recusou_antes`, CT-10 `erro_no_envio`; log CT-08 |
| `ConvidaEmMassa::acaoDeConvidarEmMassa()` | CT-01, CT-02, CT-06 (limite e `halt()`), CT-09 (carimbo), CT-12 (autorização), CT-14 (trava de papel) |
| `Paineis::permissoesDe()` e a lista do `PapeisSeeder` (passo 7) | CT-16 — Page, Resource, FQCN inexistente, e as duas pontas da subtração |

`motivoLegivel()` não tem CT próprio: é um `match` de rótulos em pt-BR, e asserir a string exata
seria asserir uma decisão de cópia escrita no mesmo commit. Os motivos estão travados pela
**chave**, que é o que o resto do sistema usa.

### Rodar antes de implementar

| CT | Falha esperada antes | O que a falha prova |
| --- | --- | --- |
| CT-02 | a ação `convidarEmMassa` não existe | o teste aponta para a ação certa antes de haver comportamento |
| CT-12 | a ação aparece para `panel_user` (se implementada sem `->authorize()`) | é a única coisa que acusa a affordance sem permissão |
| CT-16 | `Paineis::permissoesDe()` não existe | o buraco de ADR-06 está aberto **agora**, no repositório |

CT-10 merece uma execução deliberada com o `catch` estreitado para `catch (QueryException $e)` —
vê-lo falhar assim é o que prova que ele detecta o defeito do `invite-only`, em vez de passar por
acidente.

### Testes existentes a reconferir

| Arquivo | Por quê |
| --- | --- |
| `tests/Kit/ConviteTest.php` | perde `usuarioDoKit()` e `espiarAutenticacao()` para `tests/Pest.php` (passo 8). **Nenhum caso muda de expectativa** — se algum mudar, a subida levou mais que as duas funções |
| `tests/Kit/KitUpdateTest.php` | varre a árvore e acusa arquivo do kit fora de `CAMINHOS_DO_KIT`. Esperado **verde sem edição** |
| `tests/Kit/PaineisTest.php` | os casos existentes têm de continuar verdes depois do passo 7 — em especial `:135-144` e `:155-162`. O segundo acusa se `resources()` for mexido: a tela de papéis consome aquele formato |
| `tests/Tenancy/ConviteTenancyTest.php` | nada muda; roda para confirmar que o lote não mexeu no convite individual |

Nada mais à mão: `php artisan test --group=kit` roda as quatro suítes.
