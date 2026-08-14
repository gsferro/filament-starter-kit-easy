# Plano de Ação — Convite para quem já tem conta

## Objetivo

Fazer o convite funcionar para quem **já é usuário**. Hoje ele só serve para criar conta
nova: o formulário bloqueia o endereço com `->unique('users', 'email')`
(`app/Filament/Admin/Resources/Convites/Schemas/ConviteForm.php:43`) e, se um convite
chegasse assim mesmo, `Convite::aceitar()` lança `RuntimeException('E-mail já cadastrado.')`
(`app/Models/Convite.php:196`).

O efeito é uma parede no caso mais comum de SaaS multi-tenant: a consultora que atende dois
clientes, a funcionária que trabalha em duas unidades. Hoje só o `master_global` resolve, por
`/admin` → Organizações → *Vincular usuário*
(`app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php:56`). O
`admin_organizacao` — a persona criada justamente para dar autonomia à organização — **não
consegue**.

Depois deste plano, convidar um endereço que já tem conta deixa de ser erro e passa a ser
uma **oferta de acesso**: a pessoa recebe e-mail, entra com a senha que já tem, confirma, e
é vinculada à organização com o papel do convite. Ela também pode **recusar**, e a recusa
fica registrada.

## Contexto

### As duas metades do problema, e o que cada pacote resolve

Analisamos dois pacotes antes de escrever este plano. Eles resolvem **metades diferentes**
do mesmo problema, e nenhum resolve as duas:

| | Nosso hoje | `jeffersongoncalves/filament-teams` | `offload-project/laravel-invite-only` |
| --- | --- | --- | --- |
| Modelo | link + token | caixa de entrada por e-mail | link + token |
| Público | **só usuário novo** | **só usuário existente** | qualquer |
| Avisa o convidado | e-mail enfileirado | **não avisa** (nenhum Mailable no pacote) | e-mail |
| Recusar | não | **sim, explícito** | sim |
| Papel no convite | sim, com contexto | **não existe** (owner × membro) | string livre |

O teamkit acerta o formato do que nos falta — uma oferta que a pessoa aceita ou recusa
dentro do painel, com **zero superfície pública**. Mas erra em dois pontos que este plano
não vai repetir:

1. **`TeamInvitation::accept(Authenticatable $user)` anexa qualquer usuário sem conferir o
   e-mail.** A única barreira é o `->where('email', $email)` na query da tabela da página. Um
   segundo chamador — job, comando, API — e a barreira desaparece. A verificação pertence ao
   **model**. Ver ADR-03.
2. **A caixa de entrada dele só funciona porque times pessoais são obrigatórios**
   (`config/filament-teams.php:41`, `personal_teams => true`), o que garante que todo usuário
   sempre tem um tenant para a página renderizar. Não temos times pessoais, então a caixa de
   entrada sozinha **não alcança** quem tem zero organizações. Ver ADR-05.

Do `laravel-invite-only` não se aproveita nada aqui: token em claro no banco, rota de aceite
sem `auth` e sem casar e-mail, e o fluxo de aceite **não funciona** (`getAcceptUrl()` resolve
uma rota `POST` usada num `<a href>` de e-mail → 405). Ver ADR-07.

### O que já existe do nosso lado e não muda

- `Convite` com token `sha256` em repouso, fora do `$fillable` e em `$hidden`
  (`app/Models/Convite.php`).
- `Convite::valido(?string $token): ?self` (`:156`) — porta única, três motivos de recusa
  indistinguíveis. **O model não tem scope nenhum hoje**, então `pendentesPara()` é o
  primeiro.
- `Convite::enviar(): string` — gera token, renova prazo, zera `aceito_em`, envia a
  notificação. É também o reenvio.
- `RegistroPorConvite` — a página de registro nativa do Filament com guarda no `mount()`.
- `ConviteResource` no `/admin` e no `/app`, este último carimbando o `tenant_id` à força.

## Análise dos Arquivos Existentes

### `app/Models/Convite.php`

- `aceitar(array $dados): User` (`:178-240`) — a guarda de e-mail já cadastrado (`:186-197`)
  **deixa de lançar** e passa a ser o desvio para o caminho novo. O resto do método
  (criar usuário, vincular organização, atribuir papel no contexto certo, marcar
  `aceito_em`) continua sendo o caminho de quem não tem conta.
- O trecho que atribui o papel no contexto correto (`:205-225`) precisa ser **extraído**:
  os dois caminhos usam exatamente a mesma lógica, e duplicá-la é duplicar a decisão mais
  fácil de errar da feature.
- `painelDoPapel()` (`:254`) — reusado como está.

### `app/Filament/Pages/Auth/RegistroPorConvite.php`

`mount()` (`:40-49`) hoje faz `Convite::valido()` e, sem convite, chama `recusar()`. Passa a
ter um terceiro ramo: convite válido **cujo e-mail já é usuário** não mostra formulário
nenhum.

`recusar(): never` (`:118`) sai por `HttpResponseException` — o padrão do kit para
`mount()` de página Livewire, porque `redirect()` solto ali devolve o Redirector do Livewire
onde o Laravel espera um código HTTP e o request morre em 500. **O mesmo mecanismo serve aos
desvios novos**, então nada de `redirect()` cru.

Detalhe do vendor que importa: `Register::mount()`
(`vendor/filament/filament/src/Auth/Pages/Register.php:58-62`) faz
`redirect()->intended(...)` **sem `return`** quando já há usuário autenticado — a mesma
armadilha. Nosso `mount()` roda a lógica própria **antes** de `parent::mount()`, então o
usuário autenticado é tratado por nós e nunca chega lá.

### `app/Filament/Admin/Resources/Convites/Schemas/ConviteForm.php`

O `->unique('users', 'email')` (`:43`) **sai**. Era a materialização da limitação que este
plano remove. O comentário que o justifica também sai.

### `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php`

`situacao()` (`:98-105`) ganha o estado `Recusado`. Continua **derivado**, sem coluna de
status — o que nos poupou dos dois bugs do `invite-only` (validade dependendo do cron, e o
`get()`/`update()` não atômico sobrescrevendo um aceite).

### `app/Providers/Filament/AppPanelProvider.php`

`bootUsing()` (`:88-95`) já registra item de menu do usuário — é onde a caixa de entrada
entra, no mesmo padrão de `TelaBloqueio::itemDeMenu()`
(`app/Filament/Pages/Auth/TelaBloqueio.php:105-114`).

## Autorização

- **Policies**: nenhuma nova. A `ConvitePolicy` existente cobre o CRUD.
- **Gates**: nenhum.
- **Middleware**: nenhum novo.
- **A fronteira nova, e é ela que a feature inteira protege**: aceitar um convite de
  usuário existente exige **duas** condições independentes:
  1. posse do token (o link, ou a linha listada na caixa de entrada);
  2. `$user->email === $convite->email`, verificado **no model**.

  O token sozinho não basta — é o oposto do caminho de conta nova, onde o token é
  suficiente porque a conta ainda não existe. Quem intercepta o link de uma oferta não
  ganha nada sem a senha do endereço convidado.

- A caixa de entrada é escopada por e-mail do usuário autenticado, mas **isso é conveniência
  de UI, não a barreira** — a barreira é a asserção no model. Ver ADR-03.

## Rotas

Nenhuma rota nova. A página da caixa de entrada é uma `Page` do painel `app`, então herda a
rota do painel (com tenancy: sob `/app/{tenant}`). O link do e-mail continua sendo
`filament.app.auth.register` com o token na query string.

## Variáveis de Ambiente

Nenhuma nova.

## Eventos / Listeners / Observers

Nenhum. Sem segundo consumidor, um evento `ConviteAceito` seria camada com um chamador.

## Jobs / Queues

Nenhum novo. A notificação de convite já é `ShouldQueue`.

## Impacto em Features Existentes

| O que | Impacto |
| --- | --- |
| `Convite::aceitar()` | Deixa de lançar para e-mail já cadastrado. **Quem chamava esperando a exceção muda de comportamento** — só o `RegistroPorConvite` chama. |
| `ConviteForm` | Perde o `unique('users','email')`. Um convite para endereço existente passa a ser criável — que é o objetivo. |
| CT-15 de `convite-de-usuario` | Provava as duas barreiras contra e-mail já cadastrado. **Inverte de sentido**: passa a provar que o convite é criado e que o aceite vincula em vez de recusar. |
| Auditoria | `recusado_em` entra no `$fillable`, logo entra na trilha de `/infra/audits`. Correto: recusa é informação de acesso. |
| `/app` sem organização | Continua não abrindo (o painel precisa de um tenant para renderizar). O **link** é o que resolve esse caso; a caixa de entrada não. Ver ADR-05. |

## Rollback

- **Migration down**: `dropColumn('recusado_em')`. Aditiva e nullable.
- **Sem feature flag.** O caminho novo é parte da fronteira de acesso; um interruptor que o
  desliga é uma porta.
- Reverter o código devolve o comportamento antigo sem perda de dado: convites para
  endereços existentes ficariam pendentes e não aceitáveis, exatamente como hoje.

## Dependências

Nenhum pacote novo. **Nenhum dos dois pacotes analisados é instalado** — ver ADR-07.

## Riscos

| Risco | Mitigação |
| --- | --- |
| Alguém "simplificar" a asserção de e-mail para a query da caixa de entrada, repetindo o erro do teamkit | CT-04 chama `aceitarComoUsuarioExistente()` **direto**, com o usuário errado, e cobra a exceção. Barreira sem teste direto não é barreira. |
| Usuário com zero organizações não alcança a caixa de entrada | Deliberado e documentado (ADR-05). O link cobre. |
| Convite para o próprio e-mail de quem já está na organização | Validação no form (já é membro) + o aceite ser idempotente por `syncWithoutDetaching`. CT-07. |
| Duas abas aceitando o mesmo convite | `aceito_em` é gravado dentro da transação e `Convite::valido()` deixa de devolvê-lo. Diferente do caminho de conta nova, aqui **não há** `users.email` unique para nos salvar — daí o `update` condicional do passo 3. |

## Channel de Log da Feature

**Nenhum channel novo.** `autenticacao` (`config/logging.php:101-106`) é o canal desta
família — as três wikis anteriores já o usam. Regra do arquivo: identificador mascarado,
nunca conteúdo em claro. **O token não vai para o log em nenhuma hipótese.**

O que se loga: negativas e mudanças de poder. Aceite e recusa são as duas.

## Estrutura de Implementação

### 1. Coluna `recusado_em`

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_14_000001_add_recusado_em_to_convites_table.php`

```php
Schema::table('convites', function (Blueprint $table): void {
    $table->timestamp('recusado_em')->nullable()->after('aceito_em');
});
```

- Por que uma coluna e não apagar a linha (o que o teamkit faz): recusa é **informação** —
  "ela disse não, não convide de novo" é diferente de "o convite desapareceu". O
  `invite-only` também tem `declined_at`, e nisso os dois concordam.
- O estado do convite **continua derivado**, agora de três fatos em vez de dois. Nenhuma
  coluna de status.
- `down()`: `dropColumn('recusado_em')`.

### 2. `Convite` — o estado novo e o contexto extraído

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/Convite.php`

**2a. `recusado_em` no `$fillable` e nos `casts()`**

Entra no `$fillable` de propósito: `AuditsFillables::getAuditInclude()` devolve o
`$fillable`, então a recusa aparece na trilha de `/infra/audits`. O `token` continua fora,
pela razão oposta.

**2b. `valido()` passa a excluir recusados**

```php
->whereNull('aceito_em')
->whereNull('recusado_em')
->where('expira_em', '>', now())
```

Um convite recusado não volta a valer nem pelo link. Reconvidar é criar outro — que é o que
as mensagens de erro do `invite-only` mandam fazer e o schema deles proíbe (ADR-07).

**2c. Extrair a atribuição de papel no contexto certo**

O trecho hoje em `aceitar()` (`:206-233` — o comentário do contexto, o `$contexto` em `:216`
e o `try/finally` com `setPermissionsTeamId` em `:225-231`) vira método privado, porque os dois caminhos
precisam dele e é a decisão mais fácil de errar da feature:

```php
/**
 * Atribui o papel do convite no contexto que o painel dele exige.
 *
 * Painel sem tenancy (/admin, /infra) governa a instalação inteira, e
 * User::canAccessPanel() exige o papel no contexto global. Painel de negócio (/app)
 * exige o papel dentro da organização. Errar aqui cria um usuário que entra e leva
 * 403 — sem erro nenhum no caminho.
 */
private function atribuirPapel(User $user): void
```

Corpo: o mesmo `setPermissionsTeamId()` em `try/finally` que já existe, com
`$this->painelDoPapel() === 'app' ? $this->tenant_id : Tenant::CONTEXTO_GLOBAL`.

**2d. `aceitar()` deixa de lançar e passa a desviar**

```php
public function aceitar(array $dados): User
{
    // Quem já tem conta não cria outra: o convite vira oferta de acesso, e quem
    // confirma é a própria pessoa, autenticada. Ver ADR-01.
    if ($existente = $this->usuarioExistente()) {
        return $this->aceitarComoUsuarioExistente($existente);
    }

    // ... resto igual, mais o email_verified_at do passo 2f
}
```

**2e. `aceitarComoUsuarioExistente(User $user): User` — o coração**

```php
/**
 * Vincula um usuário QUE JÁ EXISTE à organização do convite, com o papel dele.
 *
 * A asserção de e-mail está AQUI, e não na query da tela que lista as ofertas. É a
 * diferença entre este método e o `TeamInvitation::accept()` do
 * `jeffersongoncalves/filament-teams`, que anexa qualquer `Authenticatable` e confia
 * no `->where('email', …)` da tabela da página: naquele desenho, o primeiro chamador
 * novo — um job, um comando, uma rota de API — passa por cima da barreira sem que
 * nada acuse. Ver ADR-03.
 *
 * @throws RuntimeException quando o e-mail não corresponde
 */
public function aceitarComoUsuarioExistente(User $user): User
```

Lógica, em ordem:

1. **A asserção.** Comparação normalizada, porque e-mail não é case-sensitive na prática e
   o convite pode ter sido digitado com maiúsculas:

```php
if (mb_strtolower(trim($user->email)) !== mb_strtolower(trim($this->email))) {
    Log::channel('autenticacao')->warning(
        "[Convite@aceitarComoUsuarioExistente] Aceite recusado, e-mail nao corresponde | convite: {$this->id} - user: {$user->id}",
        [
            'convite_id'    => $this->id,
            'user_id'       => $user->id,
            'email_convite' => Str::mask($this->email, '*', 3),
            'email_usuario' => Str::mask($user->email, '*', 3),
            'motivo'        => 'email_nao_corresponde',
        ],
    );

    throw new RuntimeException('Este convite não é para a sua conta.');
}
```

2. **Consumo atômico.** Diferente do caminho de conta nova, aqui não existe o
   `users.email` unique para abortar um segundo aceite concorrente. O consumo é um `update`
   condicional, e só se ele afetou uma linha o vínculo acontece:

```php
$consumido = static::query()
    ->whereKey($this->getKey())
    ->whereNull('aceito_em')
    ->whereNull('recusado_em')
    ->update(['aceito_em' => now()]);

if ($consumido !== 1) {
    throw new RuntimeException('Este convite já foi usado.');
}

$this->refresh();
```

   É o que o `invite-only` **não** faz (`accept()` é check-then-act sem transação nem lock,
   então clique duplo dispara dois `InvitationAccepted` e duplica o grant de papel).

3. Vínculo idempotente: `$this->tenant_id && $user->tenants()->syncWithoutDetaching([$this->tenant_id]);`
   — `syncWithoutDetaching` e não `attach`, para reconvite de quem já é membro não estourar
   o unique de `tenant_user`.

4. `$this->atribuirPapel($user);` — o método de 2c.

5. **Logs**:

```php
Log::channel('autenticacao')->info(
    "[Convite@aceitarComoUsuarioExistente] Oferta de acesso aceita | convite: {$this->id} - user: {$user->id}",
    [
        'convite_id'     => $this->id,
        'user_id'        => $user->id,
        'email'          => Str::mask($user->email, '*', 3),
        'papel'          => $this->papel?->getAttribute('name'),
        'painel'         => $this->painelDoPapel(),
        'tenant_id'      => $this->tenant_id,
        'contexto_papel' => $this->painelDoPapel() === 'app' ? $this->tenant_id : Tenant::CONTEXTO_GLOBAL,
    ],
);
```

6. `return $user;`

**2f. `recusar(User $user): void`**

Mesma asserção de e-mail (extraída para um `exigirDono(User $user): void` privado, usado
pelos dois), depois `update` condicional gravando `recusado_em`. Log em `warning` — recusa
não é falha, mas é o fim de uma concessão de acesso e merece o nível que se procura no log.

**2g. `email_verified_at` no caminho de conta nova**

```php
$user = User::create([
    ...$dados,
    'email' => $this->email,

    // O token PROVA posse do endereço: a pessoa recebeu o link nele. Pedir
    // verificação depois disso é pedir a mesma prova duas vezes. Hoje nenhum painel
    // liga ->emailVerification(), então isto é inócuo; no dia em que ligar, sem esta
    // linha todo usuário nascido de convite é barrado na porta.
    'email_verified_at' => now(),
]);
```

`email_verified_at` **não** está no `$fillable` de `User`
(`app/Models/User.php:42-47`) — então isto precisa de `forceFill` ou de entrada explícita.
**Conferir na implementação** e usar `forceFill(['email_verified_at' => now()])->save()`
depois do `create()` se o mass assignment o descartar.

### 3. Caixa de entrada — `ConvitesRecebidos`

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/App/Pages/ConvitesRecebidos.php`
- Uma `Page` com `InteractsWithTable`, no painel `app`, no grupo do menu do usuário.

```php
public function table(Table $table): Table
{
    return $table
        ->query(fn (): Builder => Convite::pendentesPara(Auth::user()))
        ->columns([/* organização, papel, quem convidou, expira em */])
        ->recordActions([
            Action::make('aceitar')->requiresConfirmation()->action(…),
            Action::make('recusar')->requiresConfirmation()->color('danger')->action(…),
        ]);
}
```

- `Convite::pendentesPara(?User $user): Builder` — scope estático no model, e **não** um
  `where` escrito na página: a mesma query alimenta o badge do menu (passo 4), e duas
  cópias divergem.
- As duas ações chamam os métodos do model, que reafirmam o e-mail. `requiresConfirmation()`
  nos dois — entrar numa organização é ato explícito, ideia boa do teamkit que vale copiar.
- Depois de aceitar, redirecionar para `/app/{slug}` da organização aceita.
- `canAccess()`: só com a tenancy ligada. Sem organizações não há oferta de organização.
- **Logs**: nenhum na página. Quem loga é o model, que é onde a decisão acontece.

### 4. Item de menu com contagem

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AppPanelProvider.php`, dentro do `bootUsing()` que já
  existe (`:88-95`)
- No padrão de `TelaBloqueio::itemDeMenu()`: `Action::make('convitesRecebidos')` com
  `->url()`, `->icon()`, `->badge(fn () => Convite::pendentesPara(Auth::user())->count() ?: null)`
  e `->visible()` amarrado a haver pelo menos uma oferta.
- `?: null` porque badge zero é ruído: o item só aparece quando há o que aceitar.

### 5. `RegistroPorConvite` — o link que atende os dois casos

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php`

`mount()` ganha o ramo do meio:

```php
public function mount(): void
{
    $this->convite = Convite::valido(request()->query('token'));

    if (! $this->convite instanceof Convite) {
        $this->recusar();
    }

    // Já tem conta? Então não há o que registrar. Quem confirma é a própria pessoa,
    // autenticada — o token abre a porta, a senha dela é que atravessa.
    if ($this->convite->usuarioExistente() !== null) {
        $this->desviarParaAceite();
    }

    parent::mount();
}
```

`desviarParaAceite(): never`, também por `HttpResponseException`:

- **Autenticada com o e-mail certo** → chama `aceitarComoUsuarioExistente()`, notifica
  sucesso e manda para `/app/{slug}` da organização.
- **Autenticada com OUTRO e-mail** → notificação de que o convite não é daquela conta, e
  manda para a caixa de entrada (não para o login: derrubar a sessão de alguém por causa de
  um link é pior que explicar).
- **Não autenticada** → guarda o token na sessão, manda para o login do painel com a
  notificação "entre para aceitar o convite". Depois do login, um `Authenticated` listener
  não é necessário: o **item de menu com badge** (passo 4) já a encontra. **Decisão**: não
  consumir o token pós-login automaticamente — aceite é ato explícito, e um redirecionamento
  automático que vincula alguém a uma organização no primeiro login é exatamente o que
  `requiresConfirmation()` existe para evitar.

`Convite::usuarioExistente(): ?User` — a conta do e-mail deste convite, ou null. **Um método
para as duas perguntas**: `aceitar()` precisa do objeto para desviar, e o `mount()` só precisa
saber se existe (`instanceof`/truthiness). Dois métodos seriam duas formas da mesma query,
que é como uma delas envelhece sozinha.

Normalização: `whereRaw('lower(email) = ?', [mb_strtolower(trim($this->email))])` — o mesmo
critério de `exigirDono()`, senão um convite gravado com maiúsculas cria conta duplicada em
vez de desviar (CT-08).

- **Logs**: o desvio não-autenticado não loga (é caminho feliz). O desvio com e-mail
  divergente loga `warning` — mas quem loga é o model, ao lançar.

### 6. Notificação: dois textos, um objeto

> Skills: `laravel-best-practices`

- **Path**: `app/Notifications/ConviteDeAcesso.php`
- `toMail()` passa a alternar o corpo: para quem já tem conta, o texto é "entre com a sua
  senha e confirme", com o botão levando ao mesmo link; para quem não tem, o texto atual.
- **Uma classe, dois textos** — não duas Notifications. A diferença é uma linha de cópia e o
  rótulo do botão; duas classes seriam duas cópias do assunto, do rodapé e da saudação.
- O teamkit **não tem nenhuma notificação** e depende de a pessoa lembrar de olhar o painel.
  Esta é a metade que ele não tem e nós temos.

### 7. Form e tabela do `/admin` e do `/app`

> Skills: `laravel-best-practices`

- `ConviteForm`: remover `->unique('users','email')` e o comentário que o justificava.
  Acrescentar `helperText` dizendo o que acontece quando o endereço já tem conta — a tela
  deve explicar a bifurcação, não esconder.

**`situacao()` sai para o model.** Verificado na revisão: as duas tabelas mostram o estado de
formas **diferentes**, e as duas ficam erradas com `recusado_em`:

- `/admin` — `ConvitesTable::situacao()`
  (`app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:98-105`), um `match` de
  três estados;
- `/app` — `ConviteResource::table()`
  (`app/Filament/App/Resources/Convites/ConviteResource.php:139-144`) não tem coluna de
  situação: mostra `aceito_em` com `->placeholder('Pendente')`. **Um convite recusado
  apareceria como "Pendente" para sempre**, e o admin da organização reconvidaria alguém que
  já disse não.

Então: `Convite::situacao(): string` no model (`Aceito` / `Recusado` / `Expirado` /
`Pendente`, nessa ordem de precedência), e as duas tabelas passam a usá-lo. Duas telas
mostrando o mesmo estado por dois caminhos é a duplicação que produziu esta divergência —
o model é o único lugar onde ela não volta.

- A coluna de situação ganha `gray` para recusado, nas duas tabelas.

### 8. `kit:update` conhece o caminho novo

> Skills: `laravel-best-practices`

- `app/Filament/App/Pages` é coberto por `'app/Filament'`, que já está em
  `KitUpdate::CAMINHOS_DO_KIT`. **Conferir** rodando `tests/Kit/KitUpdateTest.php`: ele varre
  a árvore e falha sozinho se algum arquivo novo ficar fora.

### 9. Regra de IA

> Skills: nenhuma

- **Path**: `.ai/rules/filament.md`
- Regra nova: **asserção de identidade vive no model, não na query da tela**. Com o sintoma
  concreto e a referência ao erro do teamkit, para o próximo agente não "simplificar" a
  barreira para o `where` da tabela.

### 10. Documentação

> Skills: nenhuma

| Arquivo | O que muda |
| --- | --- |
| `wikis/arquitetura.md` | `### Convite é a única porta de entrada` ganha as duas vias (conta nova × oferta de acesso) |
| `wikis/convencoes.md` | `## Armadilhas já resolvidas`: asserção no model; `update` condicional para consumo sem unique que salve |
| `wikis/receitas.md` | `## Convidar alguém que ainda não tem conta` vira `## Convidar alguém`, com as duas vias; `## Problemas comuns` ganha "convite diz que não é para a minha conta" |
| `README.md` / `README.en.md` | seção de convite: as duas vias, e que recusar fica registrado |

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** Aplicações concretas já embutidas neste plano:
>
> - **Nenhuma tabela nova** — mesma `convites`, duas vias de consumo (ADR-01).
> - **Nenhum pacote novo** — os dois analisados custam mais para contornar do que a
>   feature inteira custa para escrever (ADR-07).
> - **Nenhum evento, nenhum Job, nenhum Service** — três métodos que mexem no próprio
>   registro e nas relações dele são model.
> - **Uma Notification com dois textos**, não duas classes.
> - **Uma query (`pendentesPara`) para a página e para o badge**, não duas.
> - `atribuirPapel()` e `exigirDono()` extraídos porque têm **dois** chamadores cada — não
>   antes.
>
> Atalhos deliberados marcados com comentário `ponytail:`.
> Ao final, `/ponytail:ponytail-review` no diff.
>
> **Caveman** não se aplica a arquivo wiki, código, commit ou README.

## Mapeamentos

### Estado do convite (derivado, sem coluna)

| Situação | Condição |
| --- | --- |
| Aceito | `aceito_em !== null` |
| Recusado | `recusado_em !== null` |
| Expirado | `expira_em` no passado (ou nulo — convite nunca enviado) |
| Pendente | os três acima falsos |

### As duas vias, por condição do e-mail

| E-mail do convite | Via | O que o token faz | Quem confirma |
| --- | --- | --- | --- |
| não tem conta | registro | **suficiente** — cria a conta | quem tem o link |
| já tem conta | oferta de acesso | **necessário, não suficiente** | a pessoa, autenticada, com e-mail conferido no model |

## Testes

> Ver `04-casos-de-teste.md`. A suíte principal é `tests/Tenancy/ConviteUsuarioExistenteTest.php`;
> `tests/Kit/` cobre o que faz sentido sem tenancy (o desvio existe nos dois modos).

## Verificação Final

- [ ] `php artisan migrate`
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --group=kit` — todos, não só os novos
- [ ] Suíte rodada duas vezes; `git status --short` limpo depois

## Commits

- `:sparkles: convite para quem ja tem conta`
