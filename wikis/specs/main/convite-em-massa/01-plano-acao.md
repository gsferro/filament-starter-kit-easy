# Plano de Ação — Convite em massa

## Objetivo

Convidar **vários e-mails de uma vez**, com um papel e uma organização para o lote inteiro, e
com **resultado parcial**: um endereço que falha não derruba os outros. Quem convida termina a
operação sabendo quantos saíram, quais não saíram e por quê.

Hoje o convite é um por vez: `CreateConvite` grava uma linha e chama `Convite::enviar()` no
`afterCreate()` (`app/Filament/Admin/Resources/Convites/Pages/CreateConvite.php:31-37`). Trazer
uma turma de trinta pessoas são trinta idas ao formulário, com os mesmos dois campos repetidos.

A feature **não** cria um segundo caminho de convite. É uma tela de entrada a mais para o mesmo
`Convite::enviar()` (`app/Models/Convite.php:124-152`), que já gera token, renova prazo e
enfileira a notificação.

## Contexto

### O que já existe e não muda

| Peça | Onde | Papel nesta feature |
| --- | --- | --- |
| `Convite::enviar(): string` | `app/Models/Convite.php:124-152` | o envio do lote é ele, N vezes. Não se escreve um segundo caminho de envio |
| `ConviteDeAcesso` (`ShouldQueue`) | `app/Notifications/ConviteDeAcesso.php:27` | a fila já é responsabilidade da notificação — daí não haver Job de lote |
| `ConviteResource` do `/admin` e do `/app` | `.../Admin/.../ConviteResource.php:38-71` e `.../App/.../ConviteResource.php:39-173` | cada `ListConvites` recebe a ação de header |
| `ConvitePolicy::create()` | `app/Policies/ConvitePolicy.php:25-28` | `can('Create:Convite')` — a autorização do lote é a do convite individual |
| Reenviar / revogar por linha | `app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:63-93` | continuam por linha, e nenhuma delas ganha versão em massa (Filosofia) |
| Channel `autenticacao` | `config/logging.php:101-107` | o log do lote vai aqui. Nenhum channel novo |

### A ideia emprestada, e o pacote que não vem com ela

O formato do resultado parcial vem do `offload-project/laravel-invite-only`
(`BulkInvitationResult` com `successful` / `failed[{email, reason}]`). **O pacote não é
instalado** — a análise de código-fonte e os motivos estão em
`wikis/specs/main/convite-para-usuario-existente/02-decisoes-arquiteturais.md:381-442` (ADR-07
dela) e nada mudou desde então. Copia-se a ideia; corrigem-se dois defeitos, em ADR-01:

| Defeito do `invite-only` | Consequência lá | Aqui |
| --- | --- | --- |
| `inviteMany()` captura só `InvalidArgumentException`, e o `unique(invitable, email)` dele cobre **todos** os status | um e-mail com convite recusado/cancelado estoura `QueryException` crua e **derruba o lote inteiro** | `catch (Throwable)` por e-mail; a falha vira uma linha em `falhas` |
| `BulkInvitationResult::count()` conta **apenas** os `successful` | `count($result)` ≠ total processado: API que mente para quem confia nela | duas chaves, `enviados` e `falhas`, e nenhum total derivado |

E uma ideia dele que **vale copiar**: `getExistingPendingEmails()` pré-carrega os endereços já
convidados com **uma** query antes do laço. O passo 3 faz o mesmo, com duas.

### O que a wiki irmã já entregou

`convite-para-usuario-existente` **está implementada** (`recusado_em` no `$fillable`,
`valido()` excluindo recusados, `aceitarComoUsuarioExistente()`, `recusar()`, `situacao()`), e é
ela que define o conjunto de motivos de falha daqui:

- **"e-mail já tem conta" NÃO é motivo de falha.** É sucesso comum do lote: o convite é criado, o
  e-mail sai, e quem recebe entra com a senha que já tem e confirma. É a maior diferença em
  relação ao `invite-only`. Ver ADR-03.
- o motivo `recusou_antes` se apoia em `recusado_em` e em `valido()`
  (`app/Models/Convite.php:162-174`), que já excluem recusados.

**Pendência dela, não desta wiki**: o `->unique('users', 'email')` saiu do form do `/admin` mas
**continua no `/app`** (`app/Filament/App/Resources/Convites/ConviteResource.php:103`), com um
comentário que já não vale. Não afeta o lote — a modal não tem esse campo —, mas o convite
individual do `/app` ainda recusa quem tem conta. Corrigir lá, não aqui.

## Análise dos Arquivos Existentes

### `app/Models/Convite.php`

- `enviar()` (`:124-152`) — gera `Str::random(64)` (`:126`), grava hash e prazo por `forceFill`
  (`:128-132`), dispara a notificação on-demand (`:134`), loga `[Convite@enviar]` (`:136-149`) e
  **devolve o token em claro** (`:151`). O lote chama e **descarta o retorno**, como a ação de
  reenvio já faz (`ConvitesTable.php:72`).
- `valido()` (`:162-174`) — `whereNull('aceito_em')`, `whereNull('recusado_em')`,
  `expira_em > now()`. O pré-carregamento do passo 3 repete **as mesmas** condições: dois lugares
  com noções diferentes de "pendente" produzem convite duplicado.

### As duas `ListConvites`

`getHeaderActions()` devolve hoje só `CreateAction::make()` nos dois painéis
(`app/Filament/Admin/Resources/Convites/Pages/ListConvites.php:13-18` e
`app/Filament/App/Resources/Convites/Pages/ListConvites.php:16-21`). É onde a ação nova entra.
O Resource do `/app` também declara `->headerActions([CreateAction::make()])` dentro da própria
`table()` (`:145-147`) — a ação nova vai na **Page**, nos dois, para ficar no mesmo lugar.

### `app/Filament/App/Resources/Convites/ConviteResource.php`

Três coisas a preservar no caminho do lote, e nenhuma vem de graça:

| Linha | O que é | Por que o lote precisa repetir |
| --- | --- | --- |
| `:73-91` | `getEloquentQuery()` fail-closed: sem `Filament::getTenant()`, `whereRaw('1 = 0')` + `warning` | a **leitura** já está protegida; a **escrita** do lote não passa por ela |
| `:110` | o Select de papel só oferece `painel = 'app'` | barreira de UX, não de servidor |
| `:121-122` | `->rule(Rule::exists(...)->where('painel', 'app'))` | **a trava real.** Sem ela no lote, um `role_id` forjado no state do Livewire promoveria trinta pessoas a `admin` da instalação de uma vez. É o ponto mais perigoso desta feature |

E `mutateFormDataBeforeCreate()` da `CreateConvite` do `/app` (`:27-35`) é a barreira 6 da wiki
`admin-da-organizacao`: o `tenant_id` vem do painel, **nunca** do payload, porque `Convite` tem
`tenant_id` **dentro** do `$fillable` (`app/Models/Convite.php:59-70`) e não usa
`BelongsToTenant`. O lote do `/app` carimba do mesmo jeito, e nem oferece campo de organização.

### A assimetria que decide onde a tela vive

`PapeisSeeder::permissoesDeAdministracaoDoApp()` (`:111-124`) monta a lista de subtração do
`panel_user` a partir de `Paineis::resources()` (`app/Support/Paineis.php:85-88`) — **só
Resources**. Já a matriz vem de `Paineis::permissoes()` (`:74-77`) →
`FilamentShield::getEntitiesPermissions()`, que mistura Resources **com Pages e Widgets**
(`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:115-125`).

Medido no repositório: **37 permissions no painel `app`, 36 de Resource, 1 fora do alcance da
subtração** (`View:MyProfilePage`). Daí saem duas coisas: a tela é `Action` e não `Page`
(ADR-02), e o mecanismo é fechado no passo 7 (ADR-06).

### `app/Console/Commands/KitUpdate.php`

`CAMINHOS_DO_KIT` (`:66-153`) já cobre tudo que esta feature toca — `app/Filament` (`:74`),
`app/Models/Convite.php` (`:82`), `app/Support` (`:91`), `config/kit.php` (`:93`),
`database/seeders` (`:99`), `tests/Kit` (`:108`), `tests/Pest.php` (`:110`). **Nenhum caminho
novo**, e quem confere é `tests/Kit/KitUpdateTest.php`, que varre a árvore.

### `tests/Kit/ConviteTest.php`

`usuarioDoKit()` (`:40-47`), `conviteCom()` (`:57-66`) e `espiarAutenticacao()` (`:91-98`, espia
**só** o channel `autenticacao`). **Nome de função de teste é global na suíte e inexistente
quando se roda um arquivo só** (`wikis/specs/main/convite-de-usuario/03-progresso.md:208-212`):
é o que decide o passo 8 em vez de uma terceira variação do nome.

## Autorização

- **Policies, Gates, Middleware**: nenhum novo. O lote cria `Convite`, então a permissão é a que
  já existe: `ConvitePolicy::create()` → `can('Create:Convite')`.
- **A ação declara a autorização** com `->authorize('create', Convite::class)`, que faz o botão
  **desaparecer** para quem não pode criar convite. `CreateAction::make()` consulta `canCreate()`
  sozinho; um `Action::make()` cru **não consulta nada** e apareceria para quem só tem
  `ViewAny:Convite` — affordance sem permissão, que `wikis/convencoes.md:84` chama de bug. Ver
  ADR-02.
- **A trava de papel no `/app`** é autorização de verdade e é do formulário do lote:
  `->rule(Rule::exists(roles, 'id')->where('painel', 'app'))`, copiada de
  `app/Filament/App/Resources/Convites/ConviteResource.php:121-122`.
- **O limite de lote não é autorização** e por isso não vive no model. Ver ADR-04.
- **A matriz do `panel_user` ganha uma correção que não é da feature** (passo 7, ADR-06). Nenhuma
  permission nova entra por esta feature.

## Rotas

**Nenhuma rota nova.** A ação é uma modal em `/admin/convites` e `/app/{tenant}/convites`, que já
existem. Uma `Page` própria custaria duas rotas, dois arquivos, dois itens de menu e **duas
permissions novas do Shield** — ver ADR-02.

## Variáveis de Ambiente

| Key | Default | Onde |
| --- | --- | --- |
| `KIT_CONVITE_LIMITE_LOTE` | `100` | `config/kit.php` → `kit.convites.limite_do_lote` |

Duas **existentes** continuam decidindo se o convite sai, e a nota do README sobre elas passa a
valer multiplicada por N: `MAIL_MAILER=log` escreve cem convites em `storage/logs` e entrega
zero; `QUEUE_CONNECTION=database` **sem worker** põe cem linhas em `jobs` e o operador acha que
enviou cem.

## Eventos / Listeners / Observers

Nenhum. Um `LoteDeConvitesEnviado` não tem segundo consumidor — o mesmo argumento que dispensou
`ConviteAceito` na wiki `convite-de-usuario`. O envio fica na ação do Filament e não num hook de
model, senão seeder, teste e tinker passam a mandar e-mail
(`app/Filament/Admin/Resources/Convites/Pages/CreateConvite.php:24-30`).

## Jobs / Queues

**Nenhum Job novo.** `ConviteDeAcesso` já é `ShouldQueue`, e o Laravel a embrulha em
`SendQueuedNotifications`: cada e-mail do lote já é um job. Um `EnviarLoteDeConvitesJob` seria um
job que despacha N jobs.

Por endereço, com `QUEUE_CONNECTION=database`: um `INSERT` em `convites`, um `UPDATE` (o token,
em `enviar()`) e um `INSERT` em `jobs`. Três escritas rápidas, sem rede — é o que torna o lote
síncrono viável, e é o que o limite protege quando a connection é `sync` (ADR-04).

**Sem transação envolvendo o lote**, e isso é definição: transação faria tudo-ou-nada, o oposto
de resultado parcial. Cada e-mail é sua própria unidade.

## Impacto em Features Existentes

| O que | Impacto |
| --- | --- |
| `Convite::enviar()` | **nenhuma mudança de código**, N chamadas. Cada uma continua logando `[Convite@enviar]`: um lote de 30 gera 30 linhas de envio + 1 de resumo |
| `PapeisSeeder` — **pelo passo 7** | `permissoesDeAdministracaoDoApp()` (`:111-124`) perde o corpo e delega a `Paineis::permissoesDe()`. A linha de uso (`:87-93`) não muda. Matriz do `panel_user`: **idêntica** |
| `App\Support\Paineis` | ganha a chave `entidades` no mapa e o método `permissoesDe()` |
| `/infra/audits` | um lote de 30 vira 30 registros `created` de `Convite`. Correto: cada convite é um ato |
| Fila | um lote de 30 põe 30 linhas em `jobs` de uma vez |
| `tests/Kit/ConviteTest.php` | perde `usuarioDoKit()` e `espiarAutenticacao()` para `tests/Pest.php` (passo 8). Nenhum caso muda de expectativa |
| **Não mudam**: `ConvitesTable`, `permissoes()`, `resources()`, a tela de papéis do `/admin`, `CAMINHOS_DO_KIT` | se alguma seção da tela de papéis sumir, o passo 7 mexeu no que não devia |

## Rollback

- **Nenhuma migration**: a feature não toca no schema, e não há reversão de dados. Convites
  criados por lote são convites comuns, indistinguíveis dos individuais.
- **Reverter é apagar código**: o trait, as duas linhas de `getHeaderActions()`, os dois métodos
  do model e a chave de config.
- **Sem feature flag.** Quem não deve convidar em massa não recebe `Create:Convite`, e o botão
  desaparece pelo `->authorize()`.
- **O passo 7 reverte separado**, porque vai em commit próprio. A matriz do `panel_user` é a mesma
  nos dois estados hoje, então reverter não muda autorização de ninguém — só reabre o mecanismo.

## Dependências

**Nenhum pacote novo.** O `offload-project/laravel-invite-only` não é instalado (Contexto).

| Peça | Origem |
| --- | --- |
| `Action` com modal de formulário, `Action::halt()` | `filament/actions` (`vendor/filament/actions/src/Action.php:693-696`) |
| `Textarea`, `Select` | `filament/forms` |
| regra `email` do `Validator` | `laravel/framework` |
| `preg_split`, `Str::mask` | stdlib do PHP / Laravel |
| Fila do envio | `ShouldQueue` da notificação que já existe |

**O passo 7 é independente do resto** e vai em commit próprio: toca `app/Support/Paineis.php`,
`database/seeders/PapeisSeeder.php` e `.ai/rules/filament.md`, e é correção de um buraco em
código já entregue. O único acoplamento é de leitura — foi ADR-02 que o encontrou.

## Riscos

| Risco | Sintoma | Mitigação |
| --- | --- | --- |
| Um e-mail derruba o lote (o defeito do `invite-only`) | 40 endereços pastados, o 12º inválido, zero convites criados | `catch (Throwable)` por e-mail; **CT-10** força uma exceção no meio do lote |
| Alguém acrescenta validação de e-mail no campo "para validar direito" | um endereço torto reprova a modal inteira e o resultado parcial morre | ADR-01 e ADR-05 explicam; **CT-02** fica vermelho na hora |
| `role_id` forjado no lote do `/app` | trinta `admin` da instalação criados por quem administra uma organização | a `->rule(Rule::exists(...))` do passo 6; **CT-14** |
| `tenant_id` do payload vencendo o do painel | convites de uma organização nascendo em outra | carimbo à força no passo 6; **CT-09** |
| `Page` em vez de `Action` num refactor futuro | `panel_user` herda a permission da Page e todo usuário comum convida em massa | ADR-02; a contagem de permissions na Verificação Final. E depois do passo 7 a Page pelo menos **pode** ser subtraída |
| `array_column($e['permissions'], 'key')` aplicado a Page no passo 7 | devolve `[]` **sem erro**, e a subtração volta a não subtrair nada — o mesmo buraco com cara de correção | extração própria por família em `entidadesDoPainel()`, com o porquê no PHPDoc; **CT-16** |
| Passo 7 mudando a matriz de algum papel sem querer | papel perdendo ou ganhando permissão em silêncio | contagem antes × depois e **CT-16** nas duas pontas (o que é subtraído e o que **não** é) |

Quatro riscos já estão registrados onde a decisão mora, com mitigação e gatilho, e não se repetem
aqui: limite alto com `QUEUE_CONNECTION=sync` e o `halt()` (ADR-04), `catch (Throwable)` engolindo
bug de código e convite criado sem e-mail entregue (ADR-01, decisão 4 e Riscos), e e-mail com caixa
mista escapando do pré-carregamento (o `ponytail:` do passo 3b).

## Channel de Log da Feature

**Nenhum channel novo.** `Log::channel('autenticacao')` (`config/logging.php:101-107`) é o desta
família de features. O cabeçalho da seção manda: um canal por camada transversal, e **"nunca
logar conteúdo de prompt/notificação em claro; identificadores sempre mascarados"**
(`config/logging.php:76-83`).

1. **O token nunca vai para o log** — nem em claro, nem hasheado, nem em prefixo. O retorno de
   `enviar()` é descartado na mesma linha e nunca entra em variável, resultado ou `$context`.
2. **E-mail sempre mascarado**, com `Str::mask($email, '*', 3)` — inclusive na lista de falhas,
   que é onde o descuido é mais provável, porque ela é o produto do método.
3. **Um resumo por lote, não um log por e-mail.** Cada envio já loga `[Convite@enviar]`
   (`app/Models/Convite.php:136-149`); o resumo carrega **contagens** e a lista de falhas.

## Estrutura de Implementação

### 1. `config/kit.php` — o limite do lote

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`, dentro do bloco `convites` que já existe (`:89-91`)

```php
'convites' => [
    'validade_em_dias' => (int) env('KIT_CONVITE_VALIDADE_DIAS', 7),

    /*
     * Máximo de endereços por lote. Com QUEUE_CONNECTION=sync cada e-mail é um
     * handshake SMTP DENTRO do request: a 200-400 ms por endereço, cem encostam
     * nos 30 s de max_execution_time e o operador leva 504 com metade do lote
     * enviada. Subir daqui exige worker de fila rodando. Ver ADR-04.
     */
    'limite_do_lote' => (int) env('KIT_CONVITE_LIMITE_LOTE', 100),
],
```

- `.env.example` ganha `KIT_CONVITE_LIMITE_LOTE=100`, comentado, junto de
  `KIT_CONVITE_VALIDADE_DIAS`.

### 2. `Convite::separarEmails()` — o parser, uma expressão

> Skills: `laravel-best-practices`

- **Path**: `app/Models/Convite.php`

```php
/**
 * Os endereços de um texto pastado, normalizados e sem repetição.
 *
 * Separadores: qualquer espaço em branco (inclusive quebra de linha e tab),
 * vírgula e ponto-e-vírgula — porque o texto real vem de uma coluna de planilha,
 * de um campo "Para:" ou de alguém digitando. Normalizar em minúsculas é o que
 * torna o pré-carregamento do lote comparável, e é a mesma normalização que
 * `exigirDono()` usa no aceite.
 *
 * @return Collection<int, string>
 */
public static function separarEmails(?string $texto): Collection
{
    return collect(preg_split('/[\s,;]+/', (string) $texto, flags: PREG_SPLIT_NO_EMPTY) ?: [])
        ->map(fn (string $email): string => mb_strtolower(trim($email)))
        ->unique()
        ->values();
}
```

- **Público e estático** porque tem **dois** chamadores: a checagem de limite na ação (passo 4) e
  `convidarEmMassa()` (passo 3).
- Duplicado dentro do próprio texto **não é falha**: é o mesmo endereço, pastado duas vezes.
  `unique()` resolve e ninguém precisa ser avisado (CT-11).
- **Logs**: nenhum. É função pura.

### 3. `Convite::convidarEmMassa()` — o laço com resultado parcial

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/Convite.php`

```php
/**
 * Convida vários endereços com o MESMO papel e a MESMA organização, e devolve o
 * que saiu e o que não saiu.
 *
 * O lote NÃO aborta por causa de um endereço: é a razão de a feature existir. Cada
 * e-mail é sua própria unidade — sem transação envolvendo o conjunto, porque
 * transação faria tudo-ou-nada.
 *
 * O que conta como falha está em ADR-03. "Já tem conta" NÃO conta: o convite para
 * quem já é usuário é uma oferta de acesso legítima, e sai como qualquer outro.
 *
 * @param  Collection<int, string>  $emails  já normalizados por `separarEmails()`
 * @return array{enviados: list<string>, falhas: list<array{email: string, motivo: string}>}
 */
public static function convidarEmMassa(
    Collection $emails,
    int $roleId,
    ?int $tenantId,
    ?int $convidadoPorId,
): array
```

Lógica, em ordem:

**3a. Formato — parte antes do laço, e não reprova o lote**

```php
[$validos, $tortos] = $emails->partition(
    fn (string $email): bool => Validator::make(['email' => $email], ['email' => ['email']])->passes(),
);
```

- A regra é a **mesma** `email` do Laravel que o campo do convite individual usa
  (`ConviteForm.php:32`): o lote não pode aceitar endereço que o formulário individual recusaria,
  nem o contrário. `filter_var(..., FILTER_VALIDATE_EMAIL)` seria mais curto e divergiria em
  casos de borda.
- **ponytail**: um `Validator` por endereço, com N ≤ 100. Um `Validator` só, com regra `emails.*`,
  exigiria mapear `emails.3` de volta ao índice.

**3b. Dois pré-carregamentos, duas queries, antes do laço**

Sem eles o laço faria 2N queries. `$normalizar` é a normalização de `separarEmails()` aplicada ao
que vem **do banco** — a entrada já chegou minúscula, os registros não necessariamente:

```php
$normalizar = fn (string $email): string => mb_strtolower(trim($email));

// Convites que já existem para estes endereços NESTA organização. Uma query, e as
// mesmas condições de `valido()` — pendente é pendente nos dois lugares, senão o
// lote cria duplicata do que a tela mostra como pendente.
$existentes = static::query()
    ->whereIn('email', $validos->all())
    ->when(
        $tenantId === null,
        fn (Builder $q): Builder => $q->whereNull('tenant_id'),
        fn (Builder $q): Builder => $q->where('tenant_id', $tenantId),
    )
    ->get(['email', 'aceito_em', 'recusado_em', 'expira_em']);

$pendentes = $existentes
    ->filter(fn (self $c): bool => $c->aceito_em === null
        && $c->recusado_em === null
        && ($c->expira_em?->isFuture() ?? false))
    ->pluck('email')
    ->map($normalizar);

$recusaram = $existentes
    ->filter(fn (self $c): bool => $c->recusado_em !== null)
    ->pluck('email')
    ->map($normalizar);

// Quem JÁ é membro desta organização. Sem organização a pergunta não existe:
// "já tem conta" não é falha (ADR-03).
$membros = $tenantId === null
    ? collect()
    : User::query()
        ->whereIn('email', $validos->all())
        ->whereHas('tenants', fn (Builder $q): Builder => $q->whereKey($tenantId))
        ->pluck('email')
        ->map($normalizar);
```

- `->when()` aqui é sobre um **query builder**, não sobre uma relação: a armadilha registrada em
  `wikis/specs/main/perfil-e-acesso-ao-painel/03-progresso.md` (Notas, item 1) é do `->when()` em
  relação Eloquent. `whereNull('tenant_id')` é obrigatório porque `where('tenant_id', null)` nunca
  casa. `User::tenants()` é a `belongsToMany` de `app/Models/User.php:200-203`.
- **ponytail**: `whereIn('email', …)` compara a coluna crua contra endereços já minúsculos.
  Registro com caixa mista escapa do filtro, e a consequência é um convite pendente a mais —
  nunca uma conta duplicada, porque `users.email` é único e o aceite da wiki irmã é idempotente.
  Se virar problema real, normalize na **escrita**; `lower(email)` no `whereIn` derruba o índice.

**3c. O laço**

```php
$enviados = [];
$falhas   = $tortos->map(fn (string $email): array => [
    'email'  => $email,
    'motivo' => 'formato_invalido',
])->all();

foreach ($validos as $email) {
    $motivo = match (true) {
        $pendentes->contains($email) => 'convite_pendente',
        $recusaram->contains($email) => 'recusou_antes',
        $membros->contains($email)   => 'ja_e_membro',
        default                      => null,
    };

    if ($motivo !== null) {
        $falhas[] = ['email' => $email, 'motivo' => $motivo];

        continue;
    }

    try {
        $convite = static::create([
            'email'            => $email,
            'role_id'          => $roleId,
            'tenant_id'        => $tenantId,
            'convidado_por_id' => $convidadoPorId,
        ]);

        // O retorno é o token EM CLARO e morre nesta linha — como na ação de
        // reenvio (ConvitesTable.php:72). Nunca entra em variável, resultado ou log.
        $convite->enviar();

        $enviados[] = $email;
    } catch (Throwable $e) {
        /*
         * `Throwable`, e não uma exceção específica: é EXATAMENTE aqui que o
         * `inviteMany()` do laravel-invite-only quebra. Ele captura só
         * InvalidArgumentException, então um duplicado não-pendente estoura
         * QueryException crua e derruba o lote inteiro. Falha de driver de e-mail,
         * de fila ou de banco é motivo para o ENDEREÇO falhar, nunca para os
         * outros 39 não serem convidados. Nada é engolido: o warning leva a
         * exception inteira.
         */
        Log::channel('autenticacao')->warning(
            '[Convite@convidarEmMassa] Falha no envio de um endereço do lote, seguindo | email: '.Str::mask($email, '*', 3),
            [
                'email'     => Str::mask($email, '*', 3),
                'role_id'   => $roleId,
                'tenant_id' => $tenantId,
                'motivo'    => 'erro_no_envio',
                'exception' => $e,
            ],
        );

        $falhas[] = ['email' => $email, 'motivo' => 'erro_no_envio'];
    }
}
```

**3d. O resumo, no log**

```php
Log::channel('autenticacao')->info(
    '[Convite@convidarEmMassa] Lote de convites processado | enviados: '.count($enviados).' - falhas: '.count($falhas),
    [
        'recebidos'     => $emails->count(),
        'enviados'      => count($enviados),
        'falhas'        => count($falhas),
        'motivos'       => collect($falhas)->countBy('motivo')->all(),
        'com_falha'     => collect($falhas)
            ->map(fn (array $f): array => ['email' => Str::mask($f['email'], '*', 3), 'motivo' => $f['motivo']])
            ->all(),
        'role_id'       => $roleId,
        'tenant_id'     => $tenantId,
        'convidado_por' => $convidadoPorId,
    ],
);

return ['enviados' => $enviados, 'falhas' => $falhas];
```

- `motivos` é `countBy` — é o que responde "o que aconteceu neste lote" numa linha.
- **Sem chave `total`**: `recebidos` é o que entrou, `enviados + falhas` é o que saiu. Um total
  calculado seria a versão nova do `BulkInvitationResult::count()`.
- Os e-mails de `com_falha` vão **mascarados**. O resultado devolvido ao chamador, não — ele é
  exibido para quem operou, e quem operou digitou os endereços.

**O que NÃO se escreve neste passo**: Job de lote, Service/Action class, DTO de resultado, Enum de
motivo, validação de e-mail à mão, limite aqui dentro. A tabela da Filosofia diz por quê e quando
cada um valeria.

### 4. `App\Filament\Concerns\ConvidaEmMassa` — a ação, uma vez

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Concerns/ConvidaEmMassa.php`, no padrão de `BadgeContagemNavegacao`
  (`app/Filament/Concerns/BadgeContagemNavegacao.php:20-38`)

```php
/**
 * A ação de header que convida vários endereços de uma vez.
 *
 * Trait, e não classe de Action, porque o campo de PAPEL é diferente em cada painel:
 * no /admin ele oferece todos os papéis; no /app só os de `painel = 'app'`, com a
 * trava de servidor. O painel injeta esse campo; o trait cuida de tudo que NÃO pode
 * divergir entre os dois — o parser, o limite, a chamada do lote e o resumo. Duas
 * cópias do resumo é como um painel passa a esconder as falhas do outro.
 *
 * @param  Select  $papel  o campo de papel deste painel
 * @param  bool  $escolheOrganizacao  true no /admin (campo no form); false no /app
 *                                    (a organização vem do painel, nunca do payload)
 */
protected function acaoDeConvidarEmMassa(Select $papel, bool $escolheOrganizacao = false): Action
```

**4a. A ação e o formulário**

```php
return Action::make('convidarEmMassa')
    ->label('Convidar em massa')
    ->icon(Heroicon::OutlinedUserGroup)
    // Esconde E recusa para quem não tem Create:Convite. Sem isto, um Action cru
    // apareceria para quem só tem ViewAny — affordance sem permissão (ADR-02).
    ->authorize('create', Convite::class)
    ->modalHeading('Convidar em massa')
    ->modalDescription('Um papel e uma '.$rotulo.' para o lote inteiro. Um endereço com problema não impede os outros.')
    ->modalSubmitActionLabel('Enviar convites')
    ->schema([
        Textarea::make('emails')
            ->label('E-mails')
            ->required()
            ->rows(8)
            ->helperText("Um por linha, ou separados por vírgula. Até {$limite} por lote. Endereços repetidos são ignorados.")
            ->columnSpanFull(),

        $papel,

        // Só no /admin, e só com tenancy — o mesmo par de condições do
        // ConviteForm.php:66-80.
        ...($escolheOrganizacao ? [
            Select::make('tenant_id')
                ->label(config('kit.tenancy.label', 'Organização'))
                ->relationship('tenant', 'nome')
                ->preload()
                ->searchable()
                ->visible(fn (): bool => (bool) config('kit.tenancy.enabled')),
        ] : []),
    ])
```

- **Sem `->email()` e sem `->nestedRecursiveRules()` no campo.** Não é esquecimento: validação de
  formato no formulário reprova a modal inteira e o lote deixa de ter resultado parcial. Ver
  ADR-01, ADR-05 e CT-02.
- **Sem `->requiresConfirmation()`**: a modal de formulário já é a confirmação.

**4b. O corpo da ação**

```php
->action(function (array $dados, Action $action): void {
    $emails = Convite::separarEmails($dados['emails'] ?? null);
    $limite = (int) config('kit.convites.limite_do_lote', 100);

    /*
     * O limite ABORTA, e é a única coisa nesta feature que aborta: lote acima do
     * limite é entrada inválida, e o certo é não mandar nada. `halt()` mantém a
     * modal ABERTA — sem isso a pessoa perde as cem linhas que acabou de colar.
     */
    if ($emails->count() > $limite) {
        Notification::make()
            ->title('Lote acima do limite')
            ->body("Você informou {$emails->count()} endereços e o limite é {$limite}. Nenhum convite foi enviado.")
            ->danger()
            ->persistent()
            ->send();

        $action->halt();
    }

    $tenantId = $escolheOrganizacao
        ? ($dados['tenant_id'] ?? null)
        : Filament::getTenant()?->getKey();

    /*
     * Fail-closed no /app: sem organização corrente o convite nasceria sem
     * `tenant_id`, e o aceite atribuiria o papel no contexto global — trinta
     * pessoas dentro do painel de negócio sem organização nenhuma. Mesmo padrão do
     * getEloquentQuery() deste Resource (App/.../ConviteResource.php:73-91).
     */
    if (! $escolheOrganizacao && $tenantId === null) {
        Log::channel('autenticacao')->warning(
            '[ConvidaEmMassa@acaoDeConvidarEmMassa] Lote recusado, sem organização corrente | painel: app',
            ['executor_id' => Auth::id(), 'motivo' => 'sem_tenant_corrente', 'recebidos' => $emails->count()],
        );

        Notification::make()->title('Sem '.$rotulo.' corrente')->danger()->persistent()->send();

        $action->halt();
    }

    $resultado = Convite::convidarEmMassa(
        $emails,
        (int) $dados['role_id'],
        $tenantId === null ? null : (int) $tenantId,
        Auth::id(),
    );

    $this->notificarResultadoDoLote($resultado);
})
```

**4c. O resumo na tela**

`notificarResultadoDoLote(array $resultado): void` — uma `Notification` com o título contando
enviados e não-enviados, o corpo listando `e-mail — motivo legível` (uma linha por falha, vazio
quando não há) e `->persistent()`, porque um resumo que some em seis segundos é inútil quando lista
doze falhas. `success` só quando não houve falha; `warning` nos outros dois casos.

```php
/** Os cinco motivos, em pt-BR, num lugar só. */
private function motivoLegivel(string $motivo): string
{
    return match ($motivo) {
        'formato_invalido' => 'endereço inválido',
        'convite_pendente' => 'já tem convite pendente',
        'recusou_antes'    => 'recusou o convite anterior',
        'ja_e_membro'      => 'já faz parte desta '.mb_strtolower((string) config('kit.tenancy.label', 'Organização')),
        default            => 'falha no envio — veja o log de autenticação',
    };
}
```

- Este `match` é o motivo de **não** existir Enum: a string só é traduzida aqui, e o `default`
  cobre `erro_no_envio` e qualquer motivo futuro sem quebrar a tela.
- **Logs desta classe**: só o `warning` de fail-closed em 4b. Quem loga lote e envio é o model.

### 5. `/admin` — a ação no header da listagem

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/Admin/Resources/Convites/Pages/ListConvites.php`

A classe passa a usar `ConvidaEmMassa` e o `getHeaderActions()` ganha a segunda entrada:

```php
return [
    CreateAction::make(),
    $this->acaoDeConvidarEmMassa(
        // O mesmo Select do ConviteForm.php:47-64, sem o `->live()`: aqui nenhum
        // campo depende do painel do papel.
        Select::make('role_id')
            ->label('Papel')
            ->relationship('papel', 'name')
            ->required()
            ->preload()
            ->searchable()
            ->helperText('Todos os endereços do lote nascem com este papel.'),
        escolheOrganizacao: true,
    ),
];
```

- O rótulo com o painel (`ConviteForm.php:57-63`) **pode** ser copiado; se for, o parâmetro da
  closure tem de se chamar `$record` — o Filament injeta por nome, não por tipo
  (`wikis/specs/main/perfil-e-acesso-ao-painel/03-progresso.md`, Notas item 4).

### 6. `/app` — a mesma ação, com a organização carimbada e a trava de papel

> Skills: `laravel-best-practices`

- **Path**: `app/Filament/App/Resources/Convites/Pages/ListConvites.php`

```php
protected function getHeaderActions(): array
{
    return [
        CreateAction::make(),
        $this->acaoDeConvidarEmMassa(
            Select::make('role_id')
                ->label('Papel')
                // Barreira de UX: só papéis do painel app aparecem.
                ->relationship('papel', 'name', fn (Builder $query): Builder => $query->where('painel', 'app'))
                ->required()
                ->preload()
                ->searchable()
                /*
                 * E a MESMA trava no servidor, copiada de
                 * app/Filament/App/Resources/Convites/ConviteResource.php:121-122.
                 * Sem ela o lote é o buraco que o convite individual fechou: um
                 * `role_id` forjado no state do Livewire criaria trinta `admin` da
                 * instalação de uma vez, a pedido de quem só administra uma
                 * organização.
                 */
                ->rule(fn (): object => Rule::exists(config('permission.table_names.roles', 'roles'), 'id')
                    ->where('painel', 'app'))
                ->helperText('Só papéis do painel de negócio.'),
            // Sem campo de organização: ela vem do painel, sempre (barreira 6).
            escolheOrganizacao: false,
        ),
    ];
}
```

- `escolheOrganizacao: false` é o que faz o trait ler `Filament::getTenant()` e falhar fechado
  quando não há organização corrente (4b).

### 7. Fechar a assimetria da subtração do `panel_user` — Page e Widget também

> Skills: `laravel-best-practices`
> **Este passo não é da feature de lote. É a correção de um buraco em código já entregue**, que
> ADR-02 encontrou ao decidir onde a tela vive. Independente dos passos 1 a 6, e vai em **commit
> próprio, primeiro**. Ver ADR-06.

O que está aberto, medido no repositório:

```
permissoes(app) total: 37
vindas de Resource:   36
NÃO cobertas pela subtração (Pages/Widgets/custom): 1 -> View:MyProfilePage
```

A subtração cobre **metade do espaço** que a matriz preenche (Análise dos Arquivos). A única
permission fora de alcance hoje é a página de perfil do Breezy, que deve mesmo ser de todos — o
buraco é **inofensivo hoje e mecanismo aberto amanhã**: a próxima Page de administração no painel
`app` entra na matriz do `panel_user` e a subtração não tem como removê-la.

**7a. `Paineis` passa a conhecer as três famílias de entidade**

- **Path**: `app/Support/Paineis.php`

A varredura de `mapa()` (`:95-128`) hoje colhe `getEntitiesPermissions()` e `getResources()`
(`:112-113`). **Não** colhe `getPages()` nem `getWidgets()`, que existem no Shield
(`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:66-79`) e é isso que falta pedir, na
mesma volta do laço, com a mesma instância limpa por painel:

```php
// dentro do foreach de mapa(), junto de :112-113
$entidades[$id] = self::entidadesDoPainel($shield);
```

```php
/**
 * FQCN => chaves de permission, para Resource, Page e Widget do painel.
 *
 * As três famílias guardam `permissions` em formatos DIFERENTES, e é a armadilha
 * deste método: Resource guarda [affix => ['key' => …, 'label' => …]] e Page/Widget
 * guardam [chave => rótulo] (`getDefaultPermissionKeys()` ramifica por
 * `is_array($affixes)`, FilamentShield.php:91-113). Aplicar `array_column($…, 'key')`
 * numa Page devolve `[]` — sem erro, sem aviso, e a subtração volta a não subtrair
 * nada. É o mesmo caminho que `FilamentShield::getEntityPermissionKeys()`
 * (`:140-145`) usa para Page e Widget.
 *
 * A chave vem do campo `*Fqcn` de dentro da entidade, e não da chave externa do
 * array: em `transformWidgets()` (HasEntityTransformers.php:56-70) a chave externa
 * pode ser um `WidgetConfiguration`, e só `widgetFqcn` é garantidamente a string.
 *
 * @return array<string, list<string>>
 */
private static function entidadesDoPainel(object $shield): array
{
    return collect($shield->getResources() ?? [])
        ->keyBy('resourceFqcn')
        ->map(fn (array $e): array => array_column($e['permissions'], 'key'))
        ->merge(
            collect($shield->getPages() ?? [])
                ->keyBy('pageFqcn')
                ->map(fn (array $e): array => array_keys($e['permissions'])),
        )
        ->merge(
            collect($shield->getWidgets() ?? [])
                ->keyBy('widgetFqcn')
                ->map(fn (array $e): array => array_keys($e['permissions'])),
        )
        ->all();
}
```

E o método público que o seeder consome:

```php
/**
 * As permissões destas entidades neste painel — Resource, Page ou Widget.
 *
 * `->only()` casa por FQCN exato. NUNCA por substring: o PHPDoc de
 * `PapeisSeeder::permissoesDeAdministracaoDoApp()` já registra que
 * `str_contains($p, 'User')` foi removido de lá porque um `UserPreferenceResource`
 * futuro cairia nele — e numa SUBTRAÇÃO o erro é o espelhado, tirar permissão de
 * quem deveria tê-la.
 *
 * @param  list<class-string>  $fqcns
 * @return Collection<int, string>
 */
public static function permissoesDe(string $painel, array $fqcns): Collection
{
    return collect(self::mapa()['entidades'][$painel] ?? [])
        ->only($fqcns)
        ->flatten()
        ->unique()
        ->values();
}
```

- `permissoes()` (`:74-77`) e `resources()` (`:85-88`) **não mudam**: a tela de papéis do Shield
  consome o formato de `resources()` e continua consumindo. `entidades` é um mapa novo ao lado.
- O PHPDoc de retorno de `mapa()` (`:93`) ganha a terceira chave.

**7b. `PapeisSeeder` delega e vira uma lista de FQCN**

- **Path**: `database/seeders/PapeisSeeder.php`

```php
/**
 * Entidades de ADMINISTRAÇÃO do painel `app`.
 *
 * Resource, Page OU Widget: as três entram na matriz do painel por
 * `FilamentShield::getEntitiesPermissions()`, então as três precisam poder ser
 * subtraídas. Até a 0.11.0 esta lista varria só Resources, e uma Page de
 * administração registrada no `app` era herdada pelo `panel_user` sem que nada
 * pudesse removê-la. Ver ADR-06 da wiki convite-em-massa.
 *
 * @return list<string>
 */
private function permissoesDeAdministracaoDoApp(): array
{
    return Paineis::permissoesDe('app', [
        UserResource::class,
        ConviteResource::class,
    ])->all();
}
```

- O corpo antigo (`:118-124`) sai inteiro — o `array_column` era o detalhe que fixava o método em
  Resource. A linha de uso (`:87-93`) **não muda**.

**7c. `.ai/rules/filament.md` — a regra passa a dizer as três**

A regra "Resource de administração no painel `app` entra na lista de subtração" (`:30-38`) está
**incompleta como escrita**: diz Resource, e o mecanismo vale para Resource, Page e Widget.
Reescrever título e corpo para as três famílias, com o número medido (37 / 36 / 1) e o sintoma —
nenhum erro, nenhum 403, nenhuma migration, e o cliente editando os próprios colegas. **É edição
obrigatória deste passo**: é o que o próximo agente lê antes de registrar uma Page nova.

**7d. Conferir que a matriz não mudou**

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

Os dois são idempotentes, e **o resultado esperado é "nada mudou"**: matriz do `panel_user` e do
`admin` idênticas antes e depois, contagem de `permissions` igual, e as seções da tela
`/admin/shield/roles/create` inalteradas. Diferença aqui significa que o passo fez mais do que
devia — ou que alguém trocou a `Action` do lote por uma `Page` (ADR-02).

### 8. Helpers de teste compartilhados

> Skills: `pest-testing`

- **Path**: `tests/Pest.php` (em `CAMINHOS_DO_KIT:110`)

`espiarAutenticacao()` (`tests/Kit/ConviteTest.php:91-98`) e `usuarioDoKit()` (`:40-47`) sobem
para `tests/Pest.php` e saem de `tests/Kit/ConviteTest.php`. Motivo, medido em outra feature:
função declarada num arquivo de teste **não existe** quando se roda aquele arquivo isolado, e
**colide** ("cannot redeclare") na suíte inteira
(`wikis/specs/main/convite-de-usuario/03-progresso.md:208-212`). Sem a subida, os testes novos
precisariam de `espiarAutenticacaoDoLote()` — a terceira variação do mesmo nome.

`usuarioComPapel()` (`tests/Tenancy/TenancyTest.php:45`) **também** é candidato: se já subiu,
reuse; se não, suba junto.

### 9. Documentação

> Skills: nenhuma

| Arquivo | O que muda |
| --- | --- |
| `wikis/receitas.md` | `## Convidar várias pessoas de uma vez` — o que é falha, o que não é, e o que fazer com o resumo |
| `wikis/convencoes.md` | `## Armadilhas já resolvidas`: (a) validação de formato no campo mata o resultado parcial; (b) `Action` cru precisa de `->authorize()`, `CreateAction` não; (c) a subtração do `panel_user` cobre Resource, Page **e** Widget, com os números medidos e o formato diferente de `permissions`. A seção `## Autorização` (`:82-94`) **não tem hoje nenhum item sobre a subtração** — ganha um, porque é lá que se procura |
| `wikis/arquitetura.md` | a subseção de convite anota que existe uma entrada em lote, sem segundo fluxo de envio |
| `README.md` / `README.en.md` | `KIT_CONVITE_LIMITE_LOTE` e a nota de que sem worker cem convites param na fila |
| `.env.example` | `KIT_CONVITE_LIMITE_LOTE=100` |
| `.ai/rules/filament.md` | **já feito no passo 7c** — vai no commit que fecha o mecanismo, não neste |

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** O que a escada cortou, e quando valeria acrescentar:
>
> | Cortado | Por quê | Quando acrescentar |
> | --- | --- | --- |
> | `Job` de lote | a `Notification` com `ShouldQueue` já é o job | se o limite passar de alguns milhares |
> | `ConviteEmMassaService` / Action class | método estático num model que já tem `enviar()` e `aceitar()` | se o lote falar com serviço externo |
> | DTO `ResultadoDoLote` | array shape no PHPDoc é a convenção do projeto, e não tem `count()` para mentir (ADR-01) | se o resultado precisar de comportamento |
> | Enum de motivo | cinco strings, um `match` de rótulo num lugar só (ADR-03) | se o motivo for persistido ou filtrado numa tela |
> | Validação de e-mail própria | `Validator` do Laravel, mesma regra do form individual | nunca |
> | `->nestedRecursiveRules(['email'])` no campo | **mataria o resultado parcial** (ADR-01) | nunca, enquanto o lote for parcial |
> | `TagsInput` no lugar do `Textarea` | o `paste` dele não quebra por `\n` (ADR-05) | se o input passar a ser digitado item por item |
> | `Page` própria | duas rotas, dois arquivos, menu e **permission que o `panel_user` herdaria** (ADR-02) | se a tela crescer para importação de CSV com pré-visualização |
> | `pages()` e `widgets()` públicos em `Paineis` | a pergunta real é "as permissões destas entidades", que é **uma** e virou **um** método (ADR-06) | quando alguma tela precisar listar Pages por painel |
> | Transação em volta do lote | tornaria o lote tudo-ou-nada | nunca |
> | Coluna de lote (`convite_lote_id`) | o resumo é da operação, não do dado; o log guarda | se "reenviar o lote inteiro" virar requisito |
> | Ação em massa de revogar / reenviar | `DeleteAction` por linha já revoga; reenviar em massa **mata N links antigos** e manda N e-mails duplicados. Nenhuma das duas reduz trabalho sem multiplicar o dano de um clique | quando alguém pedir, com o caso de uso |
> | Limite dentro do model | o limite protege o **request**, não o dado (ADR-04) | se existir caminho não-HTTP que precise do mesmo teto |
> | Chave `total` no resultado | é o `count()` do `invite-only` de volta | nunca |
>
> Reuso deliberado: `Convite::enviar()`, `ConviteDeAcesso`, `ConvitePolicy`, `Action::halt()`,
> `Action::authorize()`, `Validator`, `preg_split`, `Str::mask`, o padrão de trait de
> `BadgeContagemNavegacao`, o fail-closed de `ConviteResource::getEloquentQuery()`.
>
> Atalhos `ponytail:` são dois: o `Validator` por endereço e o `whereIn` sobre a coluna crua.
> Ao final, `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em `full`** na conversa com o usuário. Arquivo wiki, código, commit e README
> são boundary — prosa normal.

## Mapeamentos

### O que acontece com cada endereço do lote

| Situação do endereço | Resultado | Motivo |
| --- | --- | --- |
| novo, formato válido | **enviado** | — |
| **já tem conta** | **enviado** | é oferta de acesso (wiki irmã). Não é falha |
| repetido dentro do próprio texto | **enviado uma vez** | `unique()` no parser, sem aviso |
| formato inválido | falha | `formato_invalido` |
| convite pendente para o mesmo endereço + organização | falha | `convite_pendente` |
| recusou um convite anterior desta organização | falha | `recusou_antes` |
| já é membro desta organização | falha | `ja_e_membro` |
| exceção no `create()` ou no `enviar()` | falha | `erro_no_envio` (com `warning` e a exception no log) |
| lote acima do limite | **nada é enviado** | entrada inválida, e a modal fica aberta |

Sem organização (`tenant_id` nulo) sobram três motivos: `ja_e_membro` não existe, porque não há
do que ser membro.

## Testes

> Ver `04-casos-de-teste.md`. Dezesseis casos: `tests/Kit/ConviteEmMassaTest.php`
> (single-tenant), `tests/Tenancy/ConviteEmMassaTenancyTest.php` (multi-tenant) e **CT-16 em
> `tests/Kit/PaineisTest.php`**, que é onde o mapa painel × permissão já é testado — o passo 7 não
> é da feature de lote e o caso dele não pertence ao arquivo dela. As duas pastas já entram no
> grupo `kit` (`tests/Pest.php:34-37` e `:58-61`).

## Verificação Final

- [ ] `php artisan config:show kit.convites.limite_do_lote` devolve `100`
- [ ] contagem de `permissions` no banco **igual** à de antes da feature
- [ ] nenhuma permission de `Paineis::permissoes('app')` fora do alcance de
      `Paineis::permissoesDe('app', $todosOsFqcns)` — 37 no total, 36 de Resource e 1 de Page
      antes, 37 alcançáveis depois
- [ ] Matriz do `panel_user` e do `admin` **idênticas** antes e depois do passo 7
- [ ] Um lote com um endereço torto no meio: os outros chegam, e a modal **não** reprova
- [ ] `grep -rn "token" storage/logs/autenticacao*.log` não devolve nada depois de um lote
- [ ] Um usuário só com `panel_user` **não** vê "Convidar em massa" no `/app`
- [ ] `php artisan test --compact tests/Kit/KitUpdateTest.php` verde **sem** editar
      `CAMINHOS_DO_KIT`
- [ ] `/ponytail:ponytail-review` no diff, `vendor/bin/pint --dirty --format agent`,
      `composer types:check`
- [ ] `php artisan test --group=kit` — todos, não só os novos. Suíte rodada duas vezes;
      `git status --short` limpo depois

## Commits

Três, e a ordem importa: o do passo 7 é independente e vai **primeiro**, para poder ser revertido
ou cherry-pickado sem levar a feature de lote com ele.

- `:lock: subtracao do panel_user cobre Page e Widget, nao so Resource` — passo 7 (`Paineis`,
  `PapeisSeeder`, `.ai/rules/filament.md`, CT-16)
- `:sparkles: convite em massa com resultado parcial` — passos 1 a 6, 8 e os CTs
- `:memo: wiki da feature convite-em-massa`
