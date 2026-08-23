# Casos de Teste — Convite para quem já tem conta

## Setup Global

### Estratégia de DB

`RefreshDatabase`, herdado do `tests/Pest.php`. A suíte principal é
`tests/Tenancy/ConviteUsuarioExistenteTest.php` (`Tests\TenancyTestCase`), porque a feature
existe para vincular alguém a uma **organização** — sem tenancy o convite não tem a que
vincular. O que é independente do modo vai em `tests/Kit/ConviteUsuarioExistenteTest.php`
(CT-04, CT-08, CT-13, CT-14). As duas pastas já estão no grupo `kit`.

### Seeders no `beforeEach`

```php
beforeEach(function (): void {
    $this->seed(ShieldPermissionsSeeder::class);
    $this->seed(PapeisSeeder::class);
});
```

`Tests\TestCase::seed()` já usa `Artisan::call` — o `seed()` padrão do Laravel engole comando
aninhado e o `shield:generate` do primeiro seeder gravaria zero permissions.

### Helpers

- `usuarioComPapel(string $papel, ?Tenant $tenant, string $email)` — já existe em
  `tests/Tenancy/TenancyTest.php:44`. **Extrair para um helper compartilhado** em vez de
  escrever a terceira cópia (a wiki `admin-da-organizacao` já pediu a extração).
- `convite(array $atributos = []): Convite` — helper local:

```php
function convite(array $atributos = []): Convite
{
    return Convite::create([
        'email'   => 'convidada@example.test',
        'role_id' => Role::findByName('panel_user')->getKey(),
        ...$atributos,
    ]);
}
```

`ConviteFactory` existe (`database/factories/ConviteFactory.php`) e **não tem state nenhum**:
só `definition()`, com `email` do faker, `expira_em` em sete dias e `token` deliberadamente
nulo. Uma armadilha nela: `role_id` sai de `Config::roleModel()::query()->value('id')` — o
**primeiro** papel da tabela, que é o `master_global`. Todo CT que dependa do papel precisa
passar `role_id` explícito, senão o convite nasce concedendo o papel guarda-chuva.

Usar a factory com `role_id` e `tenant_id` explícitos, e apagar o helper acima.

### Estratégia de Mock

- `Notification::fake()` nos CTs que só conferem disparo. **CT-16 não usa o fake** — ele
  precisa do `toMail()` renderizado, e o mailer do `phpunit.xml` é `array`.
- `Log::spy()` em CT-04 e CT-09.

---

## CT-01: convite para e-mail que já tem conta é criável

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('cria convite para e-mail que ja tem conta')`

### Precondições

- Organização `acme`; `master_global` autenticado.
- `User` existente com `ja@example.test`.

### Dados de Entrada

```php
livewire(CreateConvite::class)
    ->fillForm(['email' => 'ja@example.test', 'role_id' => $panelUser->id, 'tenant_id' => $acme->id])
    ->call('create');
```

### Resultado Esperado

- `assertHasNoFormErrors()`.
- `assertDatabaseHas('convites', ['email' => 'ja@example.test'])`.
- **Falha contra o código atual**: o `->unique('users','email')`
  (`ConviteForm.php:43`) devolve erro de formulário. Escrever e ver falhar antes de
  implementar.

---

## CT-02: aceite pelo link vincula o usuário existente com o papel no contexto certo

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('vincula usuario existente a organizacao do convite')`

### Precondições

- Organizações `acme` e `globex`; usuária `carla@example.test` com `panel_user` **na globex**.
- Convite para `carla@example.test`, papel `panel_user`, organização `acme`.

### Dados de Entrada

```php
$token = $convite->enviar();

$this->actingAs($carla)
    ->get("/app/register?token={$token}")
    ->assertRedirectContains('/app/acme');
```

### Resultado Esperado

- `assertDatabaseHas('tenant_user', ['user_id' => $carla->id, 'tenant_id' => $acme->id])`.
- `assertDatabaseHas('model_has_roles', ['model_id' => $carla->id, 'role_id' => $panelUser->id, 'team_id' => $acme->id])`.
- O vínculo com a `globex` **continua intacto** — é o ponto da feature: aceitar na Acme não
  mexe na Globex.
- `$convite->fresh()->aceito_em` não é nulo.
- **Nenhum usuário novo**: `User::where('email', 'carla@example.test')->count()` é `1`, e o
  `id` é o mesmo de antes do aceite. É a asserção que separa "vincular" de "criar".

> **Resolvido**: o token vai por QUERY STRING, nunca pelo construtor. `mount(): void` não
> aceita argumento e lê `request()->query('token')`, então `livewire(…, ['token' => …])` não
> tem onde entregar o valor — o `mount()` cairia no ramo de convite inválido. Aqui o caminho é
> o request HTTP, porque as três saídas do desvio são REDIRECTS por `HttpResponseException` e
> é o request que as expõe (num `Livewire::test()` a exceção sobe e derruba o caso). Para
> exercitar o FORMULÁRIO dentro do componente, o kit usa
> `Livewire::withQueryParams(['token' => …])->test(...)` — ver `aceitarConvite()` em
> `tests/Kit/ConviteTest.php`. A mesma anotação da wiki `convite-de-usuario` (CT-05 dela) foi
> corrigida.

---

## CT-04: a asserção de e-mail está no model — chamada direta

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteUsuarioExistenteTest.php`
**Método**: `it('recusa aceite quando o e-mail nao corresponde')`

### Precondições

- `Log::spy()`.
- Convite para `dona@example.test`; usuária `outra@example.test` existente.

### Dados de Entrada

```php
$convite->aceitarComoUsuarioExistente($outra);
```

Chamada **direta no model**, sem passar por tela nenhuma.

### Resultado Esperado

- Lança `RuntimeException` com mensagem `'Este convite não é para a sua conta.'`.
- `assertDatabaseMissing('tenant_user', ['user_id' => $outra->id])`.
- `$convite->fresh()->aceito_em` é **nulo** — nada foi consumido.
- `Log::shouldHaveReceived('channel')->with('autenticacao')`; o `warning` tem mensagem
  iniciando em `[Convite@aceitarComoUsuarioExistente]` e context com
  `motivo = 'email_nao_corresponde'`.

> **É o caso central da feature.** Ele existe porque a query da caixa de entrada já filtra por
> e-mail, o que faz a asserção do model *parecer* redundante — e foi esse raciocínio que
> produziu o furo do `jeffersongoncalves/filament-teams`, onde
> `TeamInvitation::accept(Authenticatable $user)` anexa qualquer usuário. Se alguém remover a
> asserção "porque a tela já garante", este teste fica vermelho. Ver ADR-03.

---

## CT-05: a caixa de entrada lista só as ofertas do e-mail autenticado

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('lista apenas as ofertas do proprio e-mail')`

### Precondições

- Organizações `acme` e `globex`.
- `carla@example.test` já é `panel_user` na `globex` (para alcançar o painel).
- Convite pendente para `carla@example.test` na `acme`.
- Convite pendente para `outra@example.test` na `acme`.

### Dados de Entrada

```php
$this->actingAs($carla);
livewire(ConvitesRecebidos::class)->loadTable();
```

### Resultado Esperado

- `assertCanSeeTableRecords([$conviteDaCarla])`.
- `assertCanNotSeeTableRecords([$conviteDaOutra])`.
- `->loadTable()` antes das asserções: `ConfiguraFilamentGlobal.php:72` liga
  `deferLoading()` em toda tabela do kit.

---

## CT-06: aceite concorrente consome uma vez só

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('consome a oferta uma unica vez')`

### Precondições

- Convite pendente para `carla@example.test` na `acme`.

### Dados de Entrada

```php
$convite->aceitarComoUsuarioExistente($carla);

// Segunda tentativa com a MESMA instância em memória, que ainda tem aceito_em nulo —
// é o que simula a segunda requisição que já passou pelo próprio check.
$convite->aceitarComoUsuarioExistente($carla);
```

### Resultado Esperado

- A segunda chamada lança `RuntimeException` com `'Este convite já foi usado.'`.
- `model_has_roles` tem **uma** linha para `(carla, panel_user, acme)` — não duas.
- `tenant_user` tem **uma** linha.

> Prova o `update` condicional de ADR-04. Diferente da via de conta nova, aqui não existe o
> `unique` de `users.email` para abortar o segundo aceite — se o consumo voltar a ser
> check-then-act (o desenho do `laravel-invite-only`), este teste fica vermelho.

---

## CT-07: reconvite de quem já é membro é idempotente

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('aceita oferta de quem ja e membro sem duplicar vinculo')`

### Precondições

- `carla@example.test` **já** vinculada à `acme`.
- Convite novo para ela na `acme`, com papel diferente do atual.

### Resultado Esperado

- Nenhuma exceção — `syncWithoutDetaching` não estoura o unique de `tenant_user`.
- `tenant_user` continua com **uma** linha para o par.
- O papel novo está atribuído no contexto da `acme`.

---

## CT-08: a comparação de e-mail ignora caixa e espaços

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteUsuarioExistenteTest.php`
**Método**: `it('compara e-mail sem depender de caixa')`

### Precondições

- Usuária com `Carla@Example.test`.
- Convite criado com `  carla@example.TEST  `.

### Resultado Esperado

- `aceitarComoUsuarioExistente($carla)` **não** lança.
- O desvio de `aceitar()` também reconhece a conta existente (não tenta criar outra).

---

## CT-09: recusar registra e invalida

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('registra a recusa e invalida o convite')`

### Precondições

- `Log::spy()`. Convite pendente para `carla@example.test`.

### Dados de Entrada

```php
$convite->recusar($carla);
```

### Resultado Esperado

- `$convite->fresh()->recusado_em` não é nulo.
- `assertDatabaseMissing('tenant_user', [...])` — recusar não vincula.
- `Convite::valido($token)` devolve `null`.
- `warning` no channel `autenticacao` com `[Convite@recusar]`.
- `Convite::situacao()` devolve `'Recusado'`.

---

## CT-10: convite recusado não volta a valer pelo link

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('nao aceita convite recusado nem pelo link')`

### Precondições

- Convite recusado (CT-09).

### Dados de Entrada

```php
$this->actingAs($carla);
$this->get("/app/register?token={$token}");
```

### Resultado Esperado

- Redirect para o login (o desvio de `recusar()` da página), **sem** vincular.
- `assertDatabaseMissing('tenant_user', [...])`.

---

## CT-11: link aberto por outra conta autenticada não vincula

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('nao vincula quando o link e aberto por outra conta')`

### Precondições

- Convite para `carla@example.test`.
- `bruno@example.test` autenticado, membro da `globex`.

### Dados de Entrada

```php
$this->actingAs($bruno)->get("/app/register?token={$token}");
```

### Resultado Esperado

- Nenhum vínculo novo: `assertDatabaseMissing('tenant_user', ['user_id' => $bruno->id, 'tenant_id' => $acme->id])`.
- `$convite->fresh()->aceito_em` é nulo — o convite da Carla **não** foi queimado.
- A sessão do Bruno continua ativa (não somos deslogados por causa de um link).

> É o caso que o `laravel-invite-only` erra: lá `accept()` não compara e-mail nenhum, então o
> Bruno entraria na Acme com o papel do convite da Carla.

---

## CT-12: link sem autenticação manda ao login e não consome nada

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('manda visitante ao login sem consumir a oferta')`

### Precondições

- Convite pendente para `carla@example.test`, que já tem conta.

### Dados de Entrada

```php
$this->get("/app/register?token={$token}");    // sem actingAs
```

### Resultado Esperado

- Redirect para a URL de login do painel `app`.
- `$convite->fresh()->aceito_em` é nulo.
- Nenhum usuário criado — o formulário de registro **não** é exibido para um e-mail que já
  tem conta.

---

## CT-13: conta nova nasce com o e-mail verificado

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteUsuarioExistenteTest.php`
**Método**: `it('cria o usuario com o e-mail ja verificado')`

### Precondições

- Convite para `nova@example.test`, que **não** tem conta.

### Dados de Entrada

```php
$novo = $convite->aceitar(['name' => 'Nova', 'password' => 'senha-forte-123']);
```

### Resultado Esperado

- `$novo->email_verified_at` não é nulo.
- **Falha contra o código atual** (`app/Models/Convite.php:200` cria sem a coluna).
- Se `email_verified_at` estiver fora do `$fillable` de `User` — e está
  (`app/Models/User.php:42-46`) — o teste também prova que a implementação usou `forceFill`
  em vez de confiar no mass assignment.

---

## CT-14: `situacao()` cobre os quatro estados

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteUsuarioExistenteTest.php`
**Método**: `it('deriva a situacao do convite')` — com `dataset`

### Dados de Entrada

```php
dataset('situacoes', [
    'pendente' => [['expira_em' => now()->addDay()], 'Pendente'],
    'aceito'   => [['expira_em' => now()->addDay(), 'aceito_em' => now()], 'Aceito'],
    'recusado' => [['expira_em' => now()->addDay(), 'recusado_em' => now()], 'Recusado'],
    'expirado' => [['expira_em' => now()->subDay()], 'Expirado'],
    'sem envio'=> [['expira_em' => null], 'Expirado'],
]);
```

### Resultado Esperado

- `Convite::situacao()` devolve a string esperada em cada linha.
- Aceito vence expirado (um convite aceito ontem não vira "Expirado" hoje) — é a ordem de
  precedência do `match`, e é o que o dataset trava.

---

## CT-15: o item de menu conta só as ofertas pendentes

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('conta as ofertas pendentes no menu do usuario')`

### Precondições

- `carla@example.test` membro da `globex`.
- Duas ofertas pendentes para ela, uma aceita e uma recusada.

### Resultado Esperado

- `Convite::pendentesPara($carla)->count()` é `2`.
- Sem oferta nenhuma, a contagem é `0` e o item de menu não aparece
  (`->badge(… ?: null)` + `->visible()`).

> A mesma query alimenta a página (CT-05) e o badge. Duas cópias divergiriam — daí ela viver
> no model.

---

## CT-16: o e-mail da oferta tem texto próprio

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('manda texto de oferta para quem ja tem conta')`

### Precondições

- **Sem** `Notification::fake()` — precisamos do `toMail()` renderizado. O mailer do
  `phpunit.xml` é `array`.
- Usuária existente e convite para o e-mail dela.

### Resultado Esperado

- A mensagem renderizada menciona entrar com a senha existente (não "crie sua senha").
- O botão aponta para a rota de registro com o token — **o mesmo link das duas vias**.
- O corpo **não** contém o token em claro fora da URL do botão.

---

## CT-17: o admin da organização convida quem já tem conta

**Tipo**: `Feature`
**Arquivo**: `tests/Tenancy/ConviteUsuarioExistenteTest.php`
**Método**: `it('deixa o admin da organizacao convidar quem ja tem conta')`

### Precondições

- Ana com `admin_app` na `acme`.
- `carla@example.test` existente, membro só da `globex`.

### Dados de Entrada

```php
$this->actingAs($ana);
livewire(App\Filament\App\Resources\Convites\Pages\CreateConvite::class)
    ->fillForm(['email' => 'carla@example.test', 'role_id' => $panelUser->id])
    ->call('create');
```

### Resultado Esperado

- `assertHasNoFormErrors()`.
- O convite nasce com `tenant_id` da `acme` — carimbado à força, ignorando o formulário
  (barreira 6 da wiki `admin-da-organizacao`).
- **É a razão de a feature existir**: hoje o `admin_app` não tem nenhum caminho para
  trazer alguém que já tem conta.

---

## Índice de Casos

| ID | Cenário | Tipo | Arquivo |
| --- | --- | --- | --- |
| CT-01 | convite para e-mail existente é criável | Feature | `tests/Tenancy/…` |
| CT-02 | aceite vincula com papel no contexto certo | Feature | `tests/Tenancy/…` |
| CT-04 | **asserção de e-mail no model, chamada direta** | Feature | `tests/Kit/…` |
| CT-05 | caixa de entrada só do próprio e-mail | Feature | `tests/Tenancy/…` |
| CT-06 | consumo atômico, uma vez só | Feature | `tests/Tenancy/…` |
| CT-07 | reconvite de membro é idempotente | Feature | `tests/Tenancy/…` |
| CT-08 | e-mail comparado sem caixa | Feature | `tests/Kit/…` |
| CT-09 | recusa registra e invalida | Feature | `tests/Tenancy/…` |
| CT-10 | recusado não vale pelo link | Feature | `tests/Tenancy/…` |
| CT-11 | link aberto por outra conta não vincula | Feature | `tests/Tenancy/…` |
| CT-12 | visitante vai ao login sem consumir | Feature | `tests/Tenancy/…` |
| CT-13 | conta nova nasce verificada | Feature | `tests/Kit/…` |
| CT-14 | `situacao()` nos quatro estados | Feature | `tests/Kit/…` |
| CT-15 | badge conta só pendentes | Feature | `tests/Tenancy/…` |
| CT-16 | e-mail com texto de oferta | Feature | `tests/Tenancy/…` |
| CT-17 | admin da organização convida quem já tem conta | Feature | `tests/Tenancy/…` |

## Testes existentes que mudam de expectativa

| Arquivo | Caso | O que muda |
| --- | --- | --- |
| `tests/Kit/ConviteTest.php` | o caso de e-mail já cadastrado (CT-15 da wiki `convite-de-usuario`) | **Inverte**. Provava as duas barreiras contra e-mail existente; passa a provar que o convite é criado e que o aceite **vincula** em vez de recusar. A ponta que continua valendo é a do `->unique()` do campo de e-mail na tela pública de aceite — lá o comportamento não muda. |
| `tests/Tenancy/ConviteTenancyTest.php` | conferir se algum caso pressupõe que o convite falha para e-mail existente | Ajustar ao contrato novo. |
