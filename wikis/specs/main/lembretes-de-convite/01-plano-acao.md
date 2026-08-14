# Plano de Ação — Lembretes de convite

## Objetivo

Fazer o convite cobrar a si mesmo. Hoje o kit envia o convite uma vez e espera: se a pessoa não
vê o e-mail, ele expira em silêncio (`config/kit.php:89-91`, sete dias por default) e a única
saída é alguém do `/admin` clicar em *Reenviar*
(`app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:69-75`) — o que **mata o link
anterior**, porque `Convite::enviar()` rotaciona o token (`app/Models/Convite.php:125-153`).

Este plano acrescenta um comando agendado, `kit:convites-lembrar`, que uma vez por dia manda
**um** lembrete para cada convite pendente que passou de D+3 e de D+5 desde o envio. O lembrete
leva um **segundo link, paralelo** ao original: nada é invalidado, nenhum prazo é renovado, e o
e-mail que já está na caixa de entrada de alguém continua funcionando.

A ideia vem da leitura do `offload-project/laravel-invite-only`, analisado em 2026-08-14 e **não
instalado** — a wiki irmã `convite-para-usuario-existente` registrou os impedimentos dele em
ADR-07.

## Contexto

### A armadilha central: o token em claro não existe dias depois

`Convite::enviar()` grava `hash('sha256', $token)` (`app/Models/Convite.php:130`). **O token em
claro existe na variável local do método, no corpo do e-mail e no navegador de quem recebeu — e
em lugar nenhum mais.** É a promessa de ADR-02 da wiki `convite-de-usuario`, publicada no
CHANGELOG.

Então um lembrete **não pode** reenviar o mesmo link: precisaria do claro dias depois, dentro de
um processo de cron. A saída é **gerar um segundo token, também hasheado, sem tocar no primeiro**
— coluna nova `token_lembrete`, e `Convite::valido()` passando a aceitar os dois. O link original
continua valendo, nada é revogado nem se o lembrete nunca chegar. **As quatro saídas avaliadas e
o porquê desta estão em ADR-01**, a decisão de que a feature inteira depende.

O custo dela é uma armadilha de SQL, não de segurança: `valido()` ganha um `orWhere`, e `orWhere`
**sem agrupamento explícito escapa dos outros filtros** — convite expirado volta a valer, sem
erro e sem log. Ver o passo 2c e CT-04, que existe só para isso.

### O que copiamos do `laravel-invite-only`, e o que corrigimos

| Ideia dele | Nossa versão |
| --- | --- |
| `after_days` como lista (D+3, D+5) | igual — `kit.convites.lembretes_dias`, com `[]` desligando a feature |
| **um lembrete por convite por execução**, com recuperação dos dias em que o cron não rodou | igual no comportamento, **diferente no código**: laço por convite, não por marco. Ver ADR-03 |
| intervalo medido de `created_at` | **trocado** por `enviado_em`, coluna nova: `enviar()` é também o reenvio, então `created_at` pode estar a semanas do último envio. Ver ADR-02 |
| `max_reminders` além de `after_days` | **cortado**: o teto é `count(dias)`. Dois botões podem discordar em silêncio (ADR-05) |
| `markExpiredInvitations()` — carimba status com `->get()` sem paginação e `get()`/`update()` não atômico | **não existe aqui**: não temos coluna de status, expirado é derivado de `expira_em`. Ver ADR-03 |
| não agenda nada, só documenta | **o kit agenda**, ligado. Ver ADR-04 |
| lembrete reusa o mesmo token (que ele guarda em claro no banco) | **segundo token hasheado.** Ver ADR-01 |

### O que já existe e não muda

`Convite::aceitar()` carimba `aceito_em` (`app/Models/Convite.php:281`), o que tira o convite da
fila de lembretes. `ConviteDeAcesso` é `Notification` + `ShouldQueue`, com o token em claro no
construtor (`app/Notifications/ConviteDeAcesso.php:33`) e a URL montada por `Panel::route()`
(`:88-91`). O channel `autenticacao` (`config/logging.php:101-107`) é o desta família de
features — **nenhum canal novo**. `ConviteFactory` cria `expira_em` em +7 dias e **sem `token`**:
quem preenche é `enviar()`.

`Convite::valido()` (`:163-175`) é a única peça existente cujo comportamento **muda**, e o passo
2c é o mais delicado do plano.

## Análise dos Arquivos Existentes

### `app/Models/Convite.php`

Quatro pontos existentes são tocados — `$hidden` (`:73-75`), `casts()` (`:80-87`), o `forceFill` de
`enviar()` (`:129-133`), o de `aceitar()` (`:281`) — e `valido()` (`:163-175`) é reescrito. O
**como** está nos passos 2a a 2e; o que a análise do arquivo acrescenta é a restrição que decide
tudo:

- `$fillable` (`:60-71`) **não recebe nenhuma das três colunas novas**, porque
  `AuditsFillables::getAuditInclude()` devolve o `$fillable` e hash de credencial não entra na
  trilha de `/infra/audits`; e quem escreve `enviado_em`/`lembretes_enviados` é
  `enviar()`/`lembrar()`, nunca um formulário.
- `$hidden` é o **par obrigatório** dessa escolha: fora do `$fillable` o hash não entra na
  auditoria, dentro do `$hidden` ele não aparece em `toArray()`, num `dd($convite)` nem num
  `$context` de log que passe o model inteiro.

### `app/Notifications/ConviteDeAcesso.php`

Ganha um terceiro parâmetro no construtor (`:31-34`) e duas linhas em `toMail()` (`:42-78`) — o
passo 3 tem o código. `url()` (`:88-91`) **não muda**, e é a razão de não existir uma segunda
Notification (ADR-06). A classe segue com **zero log**: é aqui que o token em claro está em escopo.

### `routes/console.php` e `app/Console/Commands/`

O agendamento vai para `routes/console.php`, ao lado de `health:check` (`:24`) e
`authentication-log:purge` (`:27`) — o `KitServiceProvider` não agenda nada, e o docblock dele
aponta para cá (`app/Providers/KitServiceProvider.php:127`). Ver ADR-04. O arquivo já está em
`KitUpdate::CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:105`).

O comando novo segue o padrão dos três existentes: docblock de classe explicando **por que** ele
existe, saída por `$this->components->info()`, `Log::channel()` com prefixo `[Classe@Método]` e
`$context`, retorno `self::SUCCESS` — `KitTenancy.php:18-39, 55, 58, 80-91` tem os quatro.

## Autorização

**Nenhuma policy, nenhum gate, nenhum middleware, nenhuma permission e nenhum seeder a rodar** —
não há Resource nem entidade nova, então as duas regras de `.ai/rules/filament.md` sobre gerar
permissões não se aplicam. O comando roda no CLI, sem request e sem usuário autenticado.

A fronteira que a feature move é `Convite::valido()`: **duas** chaves de entrada em vez de uma. A
autorização do aceite continua sendo exatamente a mesma coisa — posse de um token válido, de um
convite pendente e não expirado — e o chamador continua sem saber qual dos dois links foi usado.

Quem pode disparar um lembrete é quem pode rodar `php artisan` na máquina, a mesma fronteira de
`kit:tenancy` e `kit:update`. O comando **não** aceita filtro por organização nem por e-mail,
justamente para não virar um "mandar e-mail para quem eu quiser" com argumento de linha de comando.

## Rotas

**Nenhuma.** O link do lembrete é a rota que já existe, `filament.app.auth.register`, montada pelo
mesmo `ConviteDeAcesso::url()` — muda só o token na query string.

## Variáveis de Ambiente

| Key | Default | Onde | Descrição |
| --- | --- | --- | --- |
| `KIT_CONVITE_LEMBRETES_DIAS` | `3,5` | `config/kit.php` → `kit.convites.lembretes_dias` | dias, desde o envio, em que cada lembrete é devido. Lista separada por vírgula. **Vazio desliga a feature** |

Uma chave só: o teto é `count(dias)` e a hora do agendamento é uma linha em `routes/console.php`
(ADR-05).

Duas chaves **existentes** ganham importância: `MAIL_MAILER=log` (`.env.example:56`) escreve o
lembrete em `storage/logs` e não manda nada para fora — é o ensaio do kit antes da primeira
execução de verdade; `QUEUE_CONNECTION=database` (`:42`) exige worker (ver "Jobs / Queues").

E uma restrição nova entre chaves, que vai como comentário no `config/kit.php`: **todo dia de
lembrete tem de ser menor que `KIT_CONVITE_VALIDADE_DIAS`**. Com validade 3 e lembrete em D+3, o
convite expira antes de o lembrete ser devido e nenhum lembrete jamais sai — sem erro, sem log,
sem pista. Não há validação em código para isso (ADR-05).

## Eventos / Listeners / Observers

**Nenhum.** Não há segundo consumidor de "lembrete enviado", e um evento `ConviteLembrado` com um
único listener seria camada com um chamador. **Nenhum Observer**: quem escreve o token de lembrete
e incrementa o contador é `Convite::lembrar()`, o único ponto que manda esse e-mail — um Observer
de `updated` disparia também em seeder, teste e tinker.

## Jobs / Queues

- **Nenhum Job escrito.** O comando já roda fora do request: quem o executa é o scheduler. Um Job
  despachado pelo comando seria um job para agendar um job.
- `ConviteDeAcesso` é `ShouldQueue` (`app/Notifications/ConviteDeAcesso.php:27`), então cada
  lembrete vira uma linha em `jobs`. **Sem worker rodando, o comando termina dizendo "3 lembretes
  enviados" e nenhum e-mail sai.** O contador já subiu e o `token_lembrete` já foi gravado, então
  esses três **não serão tentados de novo** — e o hash gravado fica sem par em claro em lugar
  nenhum, o que é inofensivo: é um token que ninguém tem. É a consequência mais desconfortável
  desta feature, e está no README e em ADR-03.
- Sem `->onQueue()`: a fila é a do projeto, e a fila parada aparece no `filament-jobs-monitor` do
  `/infra`. O token em claro é serializado no payload do job, como já era no envio.
- Na suíte, `QUEUE_CONNECTION=sync` e `MAIL_MAILER=array` (`phpunit.xml:41-42`) — é o que permite
  aos CTs verem a notificação sem worker e sem nada sair da máquina.

## Impacto em Features Existentes

| O que | Impacto |
| --- | --- |
| **`Convite::valido()`** | **Passa a aceitar dois tokens**, com agrupamento explícito do `orWhere`. É a porta única de aceite: se o agrupamento sair errado, o prazo do convite deixa de valer. Ver passo 2c, ADR-01 e CT-04 |
| `Convite::enviar()` | Três colunas a mais **no mesmo `forceFill`**. Zera `lembretes_enviados` e limpa `token_lembrete`: reenviar reinicia o relógio e mata os links anteriores, os dois. Nenhum chamador muda de assinatura |
| `Convite::aceitar()` | Passa a limpar `token_lembrete` no carimbo de `aceito_em`. Comportamento externo idêntico |
| `Convite` (auditoria) | As três colunas ficam **fora** do `$fillable`, então nenhum dos dois hashes entra na trilha de `/infra/audits` |
| `ConviteDeAcesso` | Terceiro parâmetro **com default**: os dois chamadores atuais (`enviar()` e a ação *Reenviar*) seguem válidos sem edição |
| `routes/console.php` | Uma linha agendada nova. Quem já tem scheduler rodando passa a mandar lembretes **sem fazer nada** — é o objetivo, e a razão de ADR-04 existir |
| `kit:update` | **Nada a acrescentar**: `app/Console/Commands` (`KitUpdate.php:70`), `app/Models/Convite.php` (`:82`), `app/Notifications` (`:88`), `config/kit.php` (`:93`), `database/migrations` (`:98`), `routes/console.php` (`:105`) e `tests/Kit` (`:108`) já cobrem tudo. `tests/Kit/KitUpdateTest.php` **prova** isso (Verificação Final) em vez de supor |
| Tabelas de convite do `/admin` e do `/app` | **Nada.** Lembrete não é estado novo: convite lembrado continua Pendente, e `Convite::situacao()` (`:228`) não ganha caso novo |
| `tests/Kit/ConviteTest.php` | Cresce; nenhum caso existente muda de expectativa |

## Rollback

- **Migration down**: `dropColumn(['token_lembrete', 'enviado_em', 'lembretes_enviados'])`. Aditiva
  e nullable/default — nenhuma escrita destrutiva, nenhum dado de convite perdido. Consequência
  honesta: um rollback com lembretes em circulação **mata os links de lembrete**. O link
  **original** de cada convite continua funcionando, que é exatamente a propriedade que ADR-01
  compra.
- **Desligar sem rollback de dados**: `KIT_CONVITE_LEMBRETES_DIAS=` (vazio). O comando termina na
  primeira linha do `handle()` e o agendamento passa a chamar um no-op.
- **Isto é uma feature flag — e é diferente da que a wiki `convite-de-usuario` recusou.** Lá o
  interruptor proposto ligava e desligava o **cadastro público**: interruptor numa porta é porta.
  Aqui a chave é a própria configuração do cronograma, e "nenhum dia" é um cronograma válido. Nada
  de segurança depende dela.
- **Reversão de dados**: nenhuma. O lembrete não altera `token`, prazo, papel nem vínculo.
- O que **não** se reverte: e-mail enviado. Numa instalação com convites antigos acumulados, o
  ensaio antes da primeira execução de verdade é `MAIL_MAILER=log`, que é o default do kit.

## Dependências

**Nenhum pacote novo.** `Str::random`, `hash`, `Notification`, `Schedule`, `chunkById` e
`forceFill` são Laravel/PHP.

### Dependência de ORDEM — satisfeita, e o que ela deixou para esta feature

`convite-para-usuario-existente` tinha de vir antes (a query do comando filtra
`whereNull('recusado_em')`, e sem a coluna nada roda: `no such column: recusado_em`). **Ela já está
na árvore de trabalho** — `2026_08_14_000001_add_recusado_em_to_convites_table.php`,
`Convite::situacao()` no model (`app/Models/Convite.php:228`), `aceitarComoUsuarioExistente()`
(`:311`), `recusar()` (`:372`) e o `whereNull('recusado_em')` já dentro de `valido()` (`:172`).
Ainda **não commitada**, então confira o estado do arquivo antes de aplicar patch.

Três consequências práticas, agora concretas:

1. A migration desta feature é `2026_08_14_000002_*`, depois da `000001` que já existe.
2. **Os dois pontos de consumo novos precisam gravar `'token_lembrete' => null`** nos `update`
   condicionais que já estão escritos: `aceitarComoUsuarioExistente()` (`:325-329`, hoje
   `update(['aceito_em' => now()])`) e `recusar()` (`:376-380`, hoje
   `update(['recusado_em' => now()])`). É uma chave a mais num array que já existe, e esquecer deixa
   um link de lembrete vivo apontando para um convite já consumido — o `whereNull('aceito_em')` de
   `valido()` o barra, então não é furo de segurança, mas é um link que devia ter morrido.
3. `valido()` já tem **três** filtros de estado em `:171-173`. O agrupamento do passo 2c envolve
   **só** o par de tokens; mover qualquer um dos três para dentro dele produz o bug de CT-04. Leia o
   método inteiro antes de editar.

(`convite-de-usuario` está implementada e é a base de tudo — `Convite`, `enviar()`, `valido()`,
`aceitar()`, `ConviteDeAcesso`, os helpers de teste. `admin-da-organizacao` não é tocada.)

## Riscos

| Risco | Mitigação |
| --- | --- |
| **O `orWhere` de `valido()` sem agrupamento** | O risco número um desta feature: o `OR` parte o `WHERE` inteiro e **convite expirado volta a ser aceitável pelo link do lembrete**, sem erro e sem log. O SQL errado e o sintoma ficam no PHPDoc do método (passo 2c); CT-04 existe só para isso |
| Alguém "simplificar" o lembrete para chamar `enviar()` | CT-02 e CT-03: o hash de `token` e o `expira_em` não podem mudar, e o token original tem de continuar aceitando depois de dois lembretes |
| Dois links vivos por convite, e o do primeiro lembrete morrendo quando o segundo sai | Deliberado e limitado a dois (ADR-01). Quem clicar num link de lembrete antigo cai no login; o original e o do último continuam valendo. CT-03 |
| Cron parado uma semana → rajada de e-mails | Estrutural: **um** `lembrar()` por convite por execução (ADR-03), provado por CT-01 |
| Dia de lembrete ≥ validade do convite → nenhum lembrete, em silêncio | Comentário no `config/kit.php` e linha no README. Sem validação em código (ADR-05) |
| Duas máquinas rodando o mesmo scheduler → lembrete duplicado | Real, e o kit não presume cluster: nem `health:check` nem `authentication-log:purge` usam `onOneServer()` (`routes/console.php:24, 27`). Upgrade nomeado num `ponytail:` no arquivo |
| Volume grande: um `SELECT` sem paginação | `chunkById(100)`. **Nunca `->get()`** — é o defeito literal do `markExpiredInvitations()` do `invite-only` |
| Falha ao notificar um convite derrubar o lote | `try/catch` por convite, com `warning` e sem falhar o comando. O `chunkById` ordena por `id`, então um convite estragado de id baixo deixaria todos os outros sem lembrete em **toda** execução — starvation silenciosa. CT-10 |
| Alguém acrescentar um global scope de tenant em `Convite` | O comando roda sem tenant e o lote viraria vazio, em silêncio. Hoje o model não tem scope global (`app/Models/Convite.php:44-51`) e o comando é deliberadamente global |

## Channel de Log da Feature

**Nenhum channel novo.** `Log::channel('autenticacao')` — o canal existe em
`config/logging.php:101-107` e é o desta família de features (três wikis anteriores já o usam).
Convite é evento de concessão de acesso; lembrete de convite é o mesmo assunto, mesmo arquivo.

O cabeçalho dos canais é explícito (`config/logging.php:80-81`): **"nunca logar conteúdo de
prompt/notificação em claro; identificadores sempre mascarados"**. Daqui saem três regras não
negociáveis:

1. **Token nenhum vai para o log** — nem o do envio, nem o do lembrete, nem em claro, nem
   hasheado, nem em prefixo. Prefixo de segredo é segredo parcial. Correlaciona-se por
   `convite_id`.
2. **E-mail sempre mascarado**, com `Str::mask($email, '*', 3)` (stdlib do Laravel, sem helper
   novo), como em `Convite::enviar()` (`app/Models/Convite.php:138, 141`).
3. Formato `[Classe@Método] mensagem | chave: valor`, com `$context` rico.

| Evento | Nível | Onde |
| --- | --- | --- |
| Lembrete enviado | `info` | `Convite@lembrar` |
| Falha ao notificar um convite (o lote segue) | `warning` | `KitConvitesLembrar@handle` |
| Resumo da execução | `info` | `KitConvitesLembrar@handle` |

Convite que **não** é devido não gera log: é o caminho feliz, e uma linha por convite pendente por
dia entupiria o arquivo que o Logs Explorer do `/infra` abre na tela.

## Estrutura de Implementação

### 1. Migration — três colunas em `convites`

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_08_14_000002_add_lembretes_to_convites_table.php`
- O prefixo vem **depois** da migration da wiki irmã (`2026_08_14_000001_*`).

```php
Schema::table('convites', function (Blueprint $table): void {
    /*
     * O SEGUNDO token do convite, hasheado como o primeiro. Um lembrete gera um
     * token novo e grava o hash aqui, SEM tocar em `token`: o link original
     * continua valendo, então nada é revogado nem se o lembrete cair no spam.
     * Cada lembrete sobrescreve esta coluna — no máximo dois links vivos por
     * convite, os dois morrendo com o mesmo `expira_em`. Ver ADR-01.
     */
    $table->string('token_lembrete', 64)->nullable()->unique();

    /*
     * Quando o convite foi enviado de verdade — e NÃO `created_at`: `enviar()` é
     * também o reenvio, então `created_at` pode estar a semanas do último envio.
     * Nulo em toda linha anterior a esta migration, o que as mantém fora dos
     * lembretes: `enviado_em <= ?` nunca casa com NULL. Ver ADR-02.
     */
    $table->timestamp('enviado_em')->nullable();

    // Quantos lembretes já saíram para o envio corrente. Zerado por `enviar()`.
    $table->unsignedTinyInteger('lembretes_enviados')->default(0);
});
```

- `unique` e 64 chars, como a coluna `token` do `create` original: é o tamanho exato do sha256 em
  hexadecimal, e o índice único torna a busca por token um lookup de índice. Vários NULLs
  convivem num índice único em SQLite e em MySQL, então a coluna nullable é segura.
- **Sem `->after()`**: amarrar a ordem física a `recusado_em` acrescentaria uma segunda dependência
  da wiki irmã, e o SQLite ignora `after` de qualquer forma.
- **Sem backfill de `enviado_em`** e **sem índice nela**. `created_at` não é a data do último envio
  (é o argumento de ADR-02), então backfill fabricaria um relógio que pode estar semanas errado —
  e todo convite pendente antigo receberia lembrete na primeira execução. Linha antiga fica de fora
  até alguém clicar em *Reenviar*.
- `down()`: `dropColumn(['token_lembrete', 'enviado_em', 'lembretes_enviados'])`.

### 2. `App\Models\Convite` — o segundo token, o relógio e `lembrar()`

> Skills: `laravel-best-practices`, `eloquent-best-practices`

- **Path**: `app/Models/Convite.php`

**2a. `$hidden` e `casts()`**

```php
protected $hidden = [
    'token',
    'token_lembrete',
];
```

`casts()` (`:80-87`) ganha `'enviado_em' => 'datetime'`. Os três `@property` novos entram no
docblock da classe (`:33-42`).

`$fillable` (`:60-71`) **não muda**: as três colunas ficam fora, o que as mantém fora da trilha de
auditoria. O par `$fillable`/`$hidden` é o que garante que **nenhum dos dois hashes** apareça em
`toArray()`, num `dd($convite)` ou num `$context` que passe o model inteiro — hash de credencial
não é dado de diagnóstico.

**2b. `enviar()` grava o relógio e mata o link de lembrete**

O `forceFill` de `:129-133` passa a:

```php
$this->forceFill([
    'token'     => hash('sha256', $token),
    'expira_em' => now()->addDays((int) config('kit.convites.validade_em_dias', 7)),
    'aceito_em' => null,
    /*
     * Reenviar é emitir um convite novo: o link anterior morre, e o link do
     * ÚLTIMO LEMBRETE tem de morrer com ele. Sem esta linha, um lembrete
     * enviado antes do reenvio continuaria aceitando — e a promessa da modal de
     * confirmação ("o link anterior deixa de funcionar") seria mentira pela
     * metade.
     */
    'token_lembrete' => null,
    // O relógio dos lembretes começa AQUI, não em `created_at`. Ver ADR-02.
    'enviado_em'         => now(),
    'lembretes_enviados' => 0,
])->save();
```

Mesma query, três chaves a mais. O log de `:137-150` ganha `'enviado_em'` no `$context`.

**2c. `valido()` aceita os dois tokens — o ponto mais delicado do plano**

```php
/**
 * O convite utilizável por este token, ou null.
 *
 * DOIS tokens abrem o mesmo convite: o do envio (`token`) e o do último lembrete
 * (`token_lembrete`). O chamador não sabe (nem precisa saber) qual foi usado — a
 * autorização é a mesma nos dois casos, e é por isso que não existe um segundo
 * método. Ver ADR-01.
 *
 * O `where(closure)` em volta do par NÃO é estilo. Sem ele o SQL sai como
 *
 *     WHERE token = ? AND aceito_em IS NULL AND ... OR token_lembrete = ?
 *
 * e o `OR` parte o WHERE inteiro: o token de lembrete passaria a valer SOZINHO,
 * sem prazo e sem estado — um convite expirado (ou já aceito, ou recusado)
 * voltaria a ser aceitável pelo link do lembrete. Nada acusa; a tela
 * simplesmente aceita. Ver CT-04, que existe só para isso.
 */
public static function valido(?string $token): ?self
{
    if (blank($token)) {
        return null;
    }

    $hash = hash('sha256', (string) $token);

    return static::query()
        // SÓ o par de tokens entra no agrupamento.
        ->where(fn (Builder $consulta) => $consulta
            ->where('token', $hash)
            ->orWhere('token_lembrete', $hash))
        // Os TRÊS filtros de estado ficam de fora, em AND.
        ->whereNull('aceito_em')
        ->whereNull('recusado_em')
        ->where('expira_em', '>', now())
        ->first();
}
```

- **A cadeia atual do método já tem os três filtros de estado** (`app/Models/Convite.php:170-174`,
  incluindo o `whereNull('recusado_em')` que a wiki irmã acrescentou): a única mudança é trocar o
  `->where('token', ...)` de `:170` pelo agrupamento, **sem mover nenhum dos outros três**.
- `blank($token)` continua sendo o primeiro `return null`, e o hash é calculado **uma** vez (hoje
  ele é calculado inline em `:170`).
- **Nenhum `validoPorLembrete()`.** Seriam dois métodos com o mesmo corpo e a mesma resposta, e o
  chamador teria de escolher — o que na prática significaria a tela adivinhando qual link a pessoa
  clicou.

**2d. `aceitar()` apaga o token de lembrete**

O `forceFill` de `:281`:

```php
// Convite consumido fecha as DUAS portas: o link do último lembrete morre junto.
$this->forceFill(['aceito_em' => now(), 'token_lembrete' => null])->save();
```

**2e. `lembrar(): void` — o método novo**

```php
/**
 * Manda um lembrete com um SEGUNDO link, sem tocar no primeiro.
 *
 * É a diferença entre lembrete e reenvio, e ela é a feature inteira: `enviar()`
 * rotaciona o token e renova o prazo, matando o link que a pessoa já tem na
 * caixa de entrada; um lembrete que fizesse isso e caísse no spam teria
 * REVOGADO o único link válido. Aqui `token` e `expira_em` não são tocados.
 *
 * O token novo em claro existe nesta variável local, no e-mail e em lugar
 * nenhum mais — a mesma regra do token do envio. Ver ADR-01.
 */
public function lembrar(): void
{
    $token = Str::random(64);

    /*
     * Grava ANTES de notificar, por duas razões independentes: o hash precisa
     * estar no banco antes de o link existir numa caixa de entrada, senão o
     * e-mail sai com um token que `valido()` não encontra; e um endereço
     * permanentemente quebrado não pode fazer o cron tentar o mesmo convite todo
     * dia para sempre — o contador sobe e o convite acaba saindo do lote.
     */
    $this->forceFill([
        'token_lembrete'     => hash('sha256', $token),
        'lembretes_enviados' => $this->lembretes_enviados + 1,
    ])->save();

    Notification::route('mail', $this->email)->notify(new ConviteDeAcesso($this, $token, lembrete: true));

    Log::channel('autenticacao')->info(
        "[Convite@lembrar] Lembrete de convite enviado | convite: {$this->id} - email: ".Str::mask($this->email, '*', 3),
        [
            'convite_id'         => $this->id,
            'email'              => Str::mask($this->email, '*', 3),
            'role_id'            => $this->role_id,
            'tenant_id'          => $this->tenant_id,
            'enviado_em'         => $this->enviado_em?->toIso8601String(),
            'expira_em'          => $this->expira_em?->toIso8601String(),
            'lembretes_enviados' => $this->lembretes_enviados,
        ],
    );
}
```

- **`void`, não `bool`.** Não existe caminho de falha próprio do método: o token é gerado na hora,
  então não há "token não recuperável". Quem falha é o `notify()`, e quem trata é o comando.
- Uma escrita só, com `forceFill` — as duas colunas estão fora do `$fillable` de propósito, e o
  `+ 1` explícito dispensa um `increment()` numa segunda query.
- O log **não** carrega token nem hash.

**O que NÃO se escreve neste passo** (escada do Ponytail, subida e anotada): nenhum
`scopePendentes()` (a query tem um chamador), nenhum `validoPorLembrete()` (ver 2c), nenhuma
Notification nova (passo 3 e ADR-06), nenhum `EnviarLembretesJob` (o comando já roda fora do
request), nenhum Enum de marco (nada lê "qual marco"), nenhuma tabela `convite_lembretes`
(ADR-05), nenhum `podeSerLembrado(): bool` (a condição é SQL e vive na query).

### 3. `App\Notifications\ConviteDeAcesso` — a mesma classe, um texto a mais

> Skills: `laravel-best-practices`

- **Path**: `app/Notifications/ConviteDeAcesso.php`

```php
public function __construct(
    public readonly Convite $convite,
    #[SensitiveParameter] public readonly string $token,
    /**
     * Muda o assunto e acrescenta uma linha. O token é OUTRO (o do lembrete),
     * montado pelo mesmo `url()` — e o link original continua valendo. Ver ADR-01.
     */
    public readonly bool $lembrete = false,
) {}
```

Em `toMail()` (`:42-78`), duas mudanças e nada mais:

```php
->subject($this->lembrete
    ? 'Lembrete: seu convite para o '.config('app.name').' ainda está esperando'
    : 'Você foi convidado para o '.config('app.name'))
```

e, imediatamente antes do `return $mensagem` de `:73`:

```php
if ($this->lembrete) {
    $mensagem->line('Este é um lembrete: o convite abaixo continua valendo e ainda não foi usado.');
}
```

- **O default `false` é o que mantém os dois chamadores atuais intactos** — `Convite::enviar()`
  (`app/Models/Convite.php:135`) e a ação *Reenviar* da tabela, que chama `enviar()`.
- A linha de prazo (`:75`) já diz quando expira, e continua correta no lembrete porque o prazo
  **não** foi renovado: nenhuma cópia nova sobre urgência.
- O eixo `$lembrete` é **ortogonal** ao `$jaTemConta` que a wiki `convite-para-usuario-existente`
  já pôs no método (`:52`, derivado de `usuarioExistente()`, variando as linhas de `:57-59` e
  `:69-71` e o rótulo do `->action()` em `:74`): um decide **o que a pessoa vai fazer**, o outro
  **por que ela está recebendo de novo**. Nenhuma combinação nova de corpo a manter — mas o método
  passa a ter dois eixos, e **no terceiro extrai-se** (ADR-06).
- **Logs**: **nenhum**, como hoje. É o escopo onde o token em claro existe.

### 4. `App\Console\Commands\KitConvitesLembrar` — o comando

> Skills: `laravel-best-practices`

- **Path**: `app/Console/Commands/KitConvitesLembrar.php` (coberto por
  `KitUpdate::CAMINHOS_DO_KIT`, `app/Console/Commands/KitUpdate.php:70`)

```php
protected $signature = 'kit:convites-lembrar';

protected $description = 'Envia lembrete dos convites pendentes nos prazos configurados';
```

- **`kit:convites-lembrar`, e não `lembrar:convites`**: os comandos do kit vivem no namespace
  `kit:` (`kit:install`, `kit:tenancy`, `kit:update`) e o substantivo primeiro agrupa os futuros
  (`kit:convites-*`) no `php artisan list`.
- **Nenhuma opção.** Não há `--dry-run`: o agendamento nasce ligado (ADR-04), então a primeira
  execução numa instalação é do cron e não de um humano — a opção guardaria uma porta que já está
  aberta. O ensaio é `MAIL_MAILER=log`, o default do kit, que escreve o e-mail em `storage/logs` em
  vez de mandá-lo. E não há `--convite=`/`--email=`: lembrar uma pessoa só é *Reenviar*, que já
  existe.

**4a. `handle(): int`**

```php
public function handle(): int
{
    /** @var list<int> $dias */
    $dias = config('kit.convites.lembretes_dias', []);

    if ($dias === []) {
        $this->components->info('Lembretes de convite desligados (kit.convites.lembretes_dias vazio).');

        return self::SUCCESS;
    }

    $enviados = 0;

    Convite::query()
        ->whereNull('aceito_em')
        // Convite recusado não recebe lembrete. A coluna vem da wiki
        // `convite-para-usuario-existente` — sem ela, "no such column: recusado_em".
        ->whereNull('recusado_em')
        // Expirado também não. Não há coluna de status: o estado é derivado.
        ->where('expira_em', '>', now())
        // O teto, do lado do banco: quem já recebeu todos sai do lote.
        ->where('lembretes_enviados', '<', count($dias))
        // Nunca casa com `enviado_em` NULL — é o que exclui convite anterior à
        // migration sem precisar de um `whereNotNull` a mais.
        ->where('enviado_em', '<=', now()->subDays(min($dias)))
        ->chunkById(100, function ($convites) use ($dias, &$enviados): void {
            foreach ($convites as $convite) {
                /*
                 * Quantos lembretes JÁ eram devidos até hoje. Mandou menos que
                 * isso? Manda UM. É toda a lógica: um por convite por execução
                 * por construção (há um único `lembrar()` neste laço), e o dia
                 * em que o cron não rodou se recupera nas execuções seguintes,
                 * sem rajada. Ver ADR-03.
                 */
                $devidos = count(array_filter(
                    $dias,
                    fn (int $prazo): bool => (bool) $convite->enviado_em?->addDays($prazo)->isPast(),
                ));

                if ($devidos <= $convite->lembretes_enviados) {
                    continue;
                }

                try {
                    $convite->lembrar();
                    $enviados++;
                } catch (Throwable $e) {
                    // Um convite com endereço quebrado não pode derrubar o lote:
                    // o `chunkById` ordena por id, então um id baixo estragado
                    // deixaria todos os outros sem lembrete em TODA execução.
                    Log::channel('autenticacao')->warning(
                        "[KitConvitesLembrar@handle] Falha ao lembrar convite | convite: {$convite->id}",
                        [
                            'convite_id' => $convite->id,
                            'email'      => Str::mask($convite->email, '*', 3),
                            'exception'  => $e,
                        ],
                    );
                }
            }
        });

    $this->components->info($enviados === 0
        ? 'Nenhum convite pendente para lembrar.'
        : "{$enviados} lembrete(s) de convite enviado(s).");

    Log::channel('autenticacao')->info(
        "[KitConvitesLembrar@handle] Lembretes de convite processados | total: {$enviados}",
        [
            'total' => $enviados,
            'dias'  => $dias,
        ],
    );

    return self::SUCCESS;
}
```

- **`chunkById(100)`, nunca `->get()`.** É o defeito do `markExpiredInvitations()` do
  `invite-only`, e aqui o `chunkById` também é o que torna a mutação do contador segura durante a
  iteração: as páginas são faixas disjuntas de `id`, então nenhum convite é visitado duas vezes na
  mesma execução.
- `min($dias)` na query e `array_filter` no PHP: a query traz **candidatos**, o laço decide. Um
  `where` por marco (o desenho do `invite-only`) seria N queries e um invariante de ordem de índice
  para manter — ver ADR-03.
- **Nunca `self::FAILURE`** por convite que falhou: o comando termina em sucesso com `warning` no
  log. Um cron que sai com código de erro por causa de um endereço inválido gera alarme falso todo
  dia.
- **Nenhuma tabela de resumo bonita.** Uma linha de total.

### 5. `config/kit.php` + `.env.example`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`, dentro do bloco `convites` que já existe (`:89-91`)

```php
'convites' => [
    'validade_em_dias' => (int) env('KIT_CONVITE_VALIDADE_DIAS', 7),

    /*
    | Dias, contados do ENVIO, em que cada lembrete do convite pendente é
    | devido. O lembrete manda um SEGUNDO link, paralelo ao do envio: nada é
    | invalidado e o prazo não é renovado, então o e-mail que a pessoa já tem
    | continua valendo.
    |
    | Lista vazia desliga a feature. Todo dia aqui precisa ser MENOR que
    | `validade_em_dias`: com validade 3 e lembrete em D+3 o convite expira
    | antes de o lembrete ser devido, e nenhum lembrete sai — sem erro nenhum.
    |
    | O teto de lembretes por convite é a quantidade de dias desta lista. Não
    | existe um segundo botão de máximo: dois botões discordam.
    */
    'lembretes_dias' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('KIT_CONVITE_LEMBRETES_DIAS', '3,5')),
    ))),
],
```

- Uma chave escalar, não um sub-array `lembretes` com um único filho: os irmãos que ele
  anteciparia (`maximo`, `hora`, `enabled`) estão todos recusados em ADR-04 e ADR-05.
- `array_filter` derruba o `0` que um valor vazio produz — é o que faz
  `KIT_CONVITE_LEMBRETES_DIAS=` virar `[]` em vez de `[0]` (um lembrete devido no mesmo instante do
  envio).
- `.env.example`: a chave entra **no bloco de convite que já existe** (`:78-82`), logo abaixo de
  `KIT_CONVITE_VALIDADE_DIAS`, com uma linha sobre a exigência de scheduler e worker.

### 6. Agendamento em `routes/console.php`

> Skills: `laravel-best-practices`

- **Path**: `routes/console.php`, junto das duas linhas ativas (`:24, :27`)

```php
// Lembrete dos convites pendentes (prazos em config/kit.php). Não manda nada
// quando a lista de dias está vazia, quando MAIL_MAILER=log ou quando não há
// convite pendente — então nasce ligado, e inerte numa instalação nova.
//
// ponytail: sem onOneServer(); o kit não presume cluster, e nem o health:check
// nem o purge acima usam. Em cluster, acrescente ->onOneServer() aqui.
Schedule::command('kit:convites-lembrar')->dailyAt('08:00');
```

- **Ligado, não comentado.** Os backups ficam comentados porque exigem destino configurado
  (`:30-31`); este não exige nada e é inerte por default. Ver ADR-04.
- `dailyAt('08:00')` no timezone da aplicação. Consequência a documentar em uma linha: o lembrete
  de D+3 chega entre D+3 e D+4, dependendo da hora do envio. Diário é o ritmo certo para e-mail;
  precisão de hora não vale um cron horário.

### 7. Documentação

> Skills: nenhuma

| Arquivo | O que muda |
| --- | --- |
| `wikis/arquitetura.md` | a subseção de convite ganha o lembrete: **dois tokens hasheados** abrindo o mesmo convite, os dois presos ao mesmo `expira_em` |
| `wikis/convencoes.md` | `## Armadilhas já resolvidas` ganha duas linhas: "lembrete que chama `enviar()` revoga o link que a pessoa tem" e **"`orWhere` sem agrupamento escapa dos filtros de estado — em `Convite::valido()` isso faz convite expirado voltar a valer"** |
| `wikis/receitas.md` | `## Problemas comuns` ganha "convidei e a pessoa não respondeu" (o cronograma) e "o lembrete não sai" (scheduler, worker, `MAIL_MAILER`, dias ≥ validade) |
| `README.md` | seção de convite: o cronograma default, `KIT_CONVITE_LEMBRETES_DIAS`, a exigência de scheduler **e** de worker, que o contador sobe mesmo se o worker estiver parado, e que o link do lembrete **não** invalida o original |
| `README.en.md` | espelho obrigatório |
| `.env.example` | `KIT_CONVITE_LEMBRETES_DIAS=3,5` (passo 5) |
| `CHANGELOG` | **nada a corrigir**: "em claro o token existe no e-mail e em lugar nenhum mais" continua verdadeira, agora para os dois tokens |
| `wikis/pacotes.md` | nada: nenhum pacote novo, nenhum vendor reimplementado |

## Filosofia de Implementação

> **Ponytail ativo em modo `full`.** A escada em cada passo: isso precisa existir? já existe no
> repo? stdlib? feature nativa? dependência instalada? uma linha? mínimo que funciona.
>
> O que a escada cortou deste plano — os itens recusados dentro de uma decisão estão nas
> Alternativas da ADR correspondente, não aqui:
>
> | Cortado | Por quê | Quando acrescentar |
> | --- | --- | --- |
> | `validoPorLembrete()`, `scopePendentes()`, `podeSerLembrado()` | um chamador cada, e os dois últimos seriam uma segunda definição de "pendente" para desincronizar com o SQL | no segundo consumidor SQL de "pendente" |
> | `lembrar(): bool` | não há caminho de falha próprio do método — o token é gerado na hora | nunca |
> | `--dry-run` | o agendamento nasce ligado, então a primeira execução é do cron e não de um humano: a opção guardaria uma porta já aberta. `MAIL_MAILER=log`, o default do kit, é o ensaio | se aparecer em suporte |
> | `--convite=` / `--email=` para lembrar um só | é *Reenviar*, que já existe (`ConvitesTable.php:69-75`) e é a operação correta | nunca |
> | Coluna `Lembretes` na tabela do `/admin`, e estado "Lembrado" na `situacao()` | o `autenticacao.log` já responde quem recebeu quantos, e asserir que uma `TextColumn` de um atributo existe é asserir um literal escrito no mesmo commit — coluna escondida por default e sem CT. Lembrete também não muda o estado do convite | quando a pergunta aparecer na tela; é aditivo |
> | Coluna `ultimo_lembrete_em` | `enviado_em` + contador respondem o cronograma; o horário exato está no log | se a listagem precisar da data |
> | Índice em `enviado_em`, e backfill dela | uma linha por convite emitido; e `created_at` não é a data do último envio, então backfill fabricaria um relógio errado e mandaria lembrete para todo convite antigo na primeira execução | índice quando a tabela doer; backfill nunca |
> | CT em `tests/Tenancy` | o comando é global; o único efeito da tenancy é o nome da organização no corpo do e-mail, já coberto | se o lembrete passar a ser por organização |
>
> Reuso deliberado, em vez de código novo: `ConviteDeAcesso` (com a flag), `Str::random` +
> `hash('sha256', ...)` (o mesmo par de `enviar()`), `chunkById`, `forceFill`, `Str::mask`, o
> channel `autenticacao`, os helpers de `tests/Kit/ConviteTest.php:40-98`.
>
> Atalhos deliberados marcados com comentário `ponytail:`.
> Ao final, `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em `full`** na conversa com o usuário. Arquivo wiki, código, commit e README
> são boundary — prosa normal.

## Mapeamentos

### Qual token abre o convite

| Token apresentado | Estado do convite | `Convite::valido()` |
| --- | --- | --- |
| o do envio (`token`) | pendente, no prazo | devolve o convite |
| o do **último** lembrete | pendente, no prazo | devolve o convite — **o mesmo**, e o chamador não distingue |
| o de um lembrete **anterior** | pendente, no prazo | `null` — cada lembrete sobrescreve `token_lembrete` |
| qualquer um dos dois | aceito, recusado ou expirado | `null` — os filtros de estado ficam **fora** do agrupamento (passo 2c) |
| qualquer um dos dois | revogado (linha apagada) | `null` |
| o do envio, depois de um *Reenviar* | — | `null`: `enviar()` sobrescreve `token` **e** limpa `token_lembrete` |

### Quando um convite recebe lembrete

Com `dias = [3, 5]`, e é o que o dataset de CT-01 implementa:

| `aceito_em` | `recusado_em` | `expira_em` | `enviado_em` | `lembretes_enviados` | Recebe? |
| --- | --- | --- | --- | --- | --- |
| nulo | nulo | futuro | há 2 dias | 0 | **não** — nenhum marco vencido |
| nulo | nulo | futuro | há 4 dias | 0 | **sim** (1 devido, 0 enviados) |
| nulo | nulo | futuro | há 4 dias | 1 | não — em dia |
| nulo | nulo | futuro | há 6 dias | 1 | **sim** (2 devidos, 1 enviado) |
| nulo | nulo | futuro | há 6 dias | 2 | não — teto de `count(dias)` |
| preenchido | — | — | — | — | não |
| — | preenchido | — | — | — | não |
| nulo | nulo | **passado** | há 9 dias | 0 | não |
| nulo | nulo | futuro | **nulo** | 0 | não — convite anterior à migration |

## Testes

> Ver `04-casos-de-teste.md`. Onze casos, **todos** em `tests/Kit/ConviteTest.php` — o arquivo que
> já tem os helpers `conviteCom()`, `aceitarConvite()` e `espiarAutenticacao()` (`:40-98`).
> Arquivo novo obrigaria a renomear os três (a armadilha de função de teste entre arquivos,
> `wikis/specs/main/convite-de-usuario/03-progresso.md`, Notas item 4). Nada em `tests/Tenancy`:
> o comando é global.

## Verificação Final

- [ ] `php artisan migrate` (depois da migration da wiki irmã)
- [ ] Num banco com convite pendente antigo e `MAIL_MAILER=log`: rodar
      `php artisan kit:convites-lembrar` e conferir o e-mail em `storage/logs/laravel.log`
- [ ] `php artisan schedule:list` mostra `kit:convites-lembrar` diário às 08:00
- [ ] Um convite lembrado: **os dois links abrem a tela de aceite**, e depois de expirar
      **nenhum dos dois** abre (é o par que prova o agrupamento do passo 2c à mão)
- [ ] `grep -rn "token" storage/logs/autenticacao*.log` não devolve nada depois de um ciclo
      completo de envio + lembrete
- [ ] `php artisan test --compact tests/Kit/KitUpdateTest.php` — prova que os caminhos novos já
      estão em `CAMINHOS_DO_KIT` (confirmado, não suposto)
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --group=kit`
- [ ] `composer types:check`
- [ ] Suíte rodada duas vezes; `git status --short` limpo depois

## Commits

- `:sparkles: lembrete automatico de convite pendente`
- `:memo: wiki da feature lembretes-de-convite`
