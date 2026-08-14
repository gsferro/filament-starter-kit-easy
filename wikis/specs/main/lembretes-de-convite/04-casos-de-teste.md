# Casos de Teste — Lembretes de convite

## Setup Global

### Onde os casos moram

**Todos em `tests/Kit/ConviteTest.php`**, apendados ao arquivo que já existe: os três helpers de
que eles precisam já estão lá — `conviteCom()` (`:57-66`), `aceitarConvite()` (`:75-88`) e
`espiarAutenticacao()` (`:91-98`) — e função declarada num arquivo de teste **não** é visível de
outro quando se roda um arquivo só (`Error: Call to undefined function`;
`wikis/specs/main/convite-de-usuario/03-progresso.md`, Notas item 4). Arquivo novo obrigaria a
renomear os três.

**Nada em `tests/Tenancy`.** O comando é global: não olha organização e `Convite` não tem global
scope (`app/Models/Convite.php:44-51`). O único efeito da tenancy no lembrete é o nome da
organização no corpo do e-mail, já coberto pelos casos de tenancy da wiki `convite-de-usuario`.

### DB e seeders

`RefreshDatabase`, herdado de `tests/Pest.php:34-37` (grupo `kit`). O `beforeEach` do arquivo
(`:36-38`) já semeia `ShieldPermissionsSeeder` e `PapeisSeeder`, e é **obrigatório e não
negociável**: `Tests\TestCase::seed()` usa `Artisan::call` de propósito
(`tests/TestCase.php:138-169`) porque o `seed()` do Laravel engole o `shield:generate` chamado de
dentro do seeder e grava **zero** permissions — sem os dois, `Role::findByName('panel_user')` (que
`conviteCom()` usa) não encontra papel nenhum.

### Fixtures e viagem no tempo

`Convite::factory()` cria `expira_em` em +7 dias, `aceito_em` nulo e **sem `token`** — logo sem
`enviado_em`, sem `token_lembrete` e com `lembretes_enviados = 0`. Quem preenche os três primeiros
é `enviar()`.

**Nenhum state novo na factory**: um `lembrado()` ou `enviadoHa(int $dias)` seria uma segunda
definição do que `conviteCom()` (que chama `enviar()`) + `$this->travel(N)->days()` já produzem —
e `travel()` é a forma do arquivo (`:231`) e alcança o `CarbonImmutable` do kit. **Cuidado com o
prazo**: com validade 7, viajar para D+6 mantém o convite válido e D+8 o expira.

A **única** exceção é CT-10, que precisa de um convite com `enviado_em` preenchido e endereço
inválido — impossível por `enviar()`, que estouraria na hora. Lá o fixture é
`Convite::factory()->create([...])` + `forceFill(['enviado_em' => ...])->save()` (a coluna está
fora do `$fillable` de propósito), e **isso basta**: `lembrar()` gera o token na hora e não depende
de coluna anterior — é a consequência bem-vinda de ADR-01.

### Mocks e execução do comando

`Notification::fake()` em todos os casos menos dois: CT-04 (é sobre `Convite::valido()`, sem
e-mail) e CT-08 (**sem fake de propósito**, é o único em que `toMail()` renderiza; o mailer é
`array`, `phpunit.xml:41`). A fila é `sync` (`:42`), então a notificação `ShouldQueue` é entregue
inline e o fake a vê. Destinatário on-demand, então a asserção é
`assertSentOnDemand(ConviteDeAcesso::class, fn ($n, $canais, $notifiable) => ...)`, com o e-mail em
`$notifiable->routes['mail']`, o token em `$n->token` e a flag em `$n->lembrete` (`public
readonly`, `app/Notifications/ConviteDeAcesso.php:31-34`). Os casos de log usam
`espiarAutenticacao()`, que espia **só** o channel `autenticacao`.

O comando roda por `$this->artisan('kit:convites-lembrar')->assertSuccessful()` — sem
`Artisan::call`, que só é necessário para comando aninhado (`tests/TestCase.php:138-157`).

Os casos que dependem do cronograma **fixam a config no próprio caso**
(`config(['kit.convites.lembretes_dias' => [3, 5]])`), senão falham numa máquina com
`KIT_CONVITE_LEMBRETES_DIAS` diferente. CT-11 é o único que lê o default.

### Como os casos leem o token de um lembrete

Pelo objeto da notificação, **nunca** pelo corpo renderizado — URL longa em e-mail sofre quebra de
linha do quoted-printable, e um `preg_match` no HTML falharia por formatação em vez de por
comportamento:

```php
$tokenDoLembrete = null;

Notification::assertSentOnDemand(
    ConviteDeAcesso::class,
    function (ConviteDeAcesso $n) use (&$tokenDoLembrete): bool {
        if ($n->lembrete) {
            $tokenDoLembrete = $n->token;
        }

        return true;
    },
);
```

---

## CT-01: o cronograma inteiro — um por execução, catch-up sem rajada, teto

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('lembra conforme o cronograma, um lembrete por convite por execucao')`

### Precondições

- Seeders, `Notification::fake()`.
- **Dois** convites pendentes idênticos (`a@example.com`, `b@example.com`) por `conviteCom()`, para
  o caso também provar que os convites **andam juntos**: nenhum recebe dois lembretes enquanto o
  outro recebe zero.

### Dados de Entrada

Um `dataset()` de `(dias, dias viajados, contador esperado depois de cada execução)`. O comando roda
uma vez por elemento da lista de esperados, e o contador é conferido **entre** as execuções — é o
que torna uma rajada detectável:

```php
})->with([
    'nada no dia do envio'          => [[3, 5], 0, [0, 0]],
    'nada antes do primeiro prazo'  => [[3, 5], 2, [0, 0]],
    'primeiro prazo vencido'        => [[3, 5], 4, [1, 1]],
    'dois prazos vencidos'          => [[3, 5], 6, [1, 2]],
    'teto = count(dias)'            => [[3],    6, [1, 1]],
]);
```

### Resultado Esperado

- Depois de **cada** execução, `lembretes_enviados` dos dois convites é o valor daquela posição.
- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2 + 2 * end($esperado))` — os dois
  envios originais mais um lembrete por convite por incremento.
- `token_lembrete` é `null` enquanto o contador é 0, e não-nulo depois do primeiro lembrete.
- `enviado_em` **não** muda em nenhuma execução: lembrete não é envio.

> **Cinco linhas, quatro propriedades travadas.** As duas primeiras são a diferença entre lembrete
> e spam (um erro de sinal na comparação de datas mandaria lembrete no mesmo minuto do convite), e
> são o único resultado esperado do tipo "nada aconteceu". `[[3, 5], 6, [1, 2]]` é a propriedade
> central de ADR-03 e a razão de conferir o contador entre execuções: os **dois** prazos venceram e
> ainda assim sai **um** lembrete por execução — uma reescrita como query por marco (o desenho do
> `laravel-invite-only` sem o acumulador) manda os dois de uma vez e a linha fica vermelha.
> `[[3], 6, [1, 1]]` prova ADR-05: o teto é `count($dias)`, não uma segunda chave de config.

---

## CT-02: o lembrete manda um link NOVO, e o link original continua valendo

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('lembra com um link novo sem invalidar o do envio')`

### Precondições

- Seeders, `Notification::fake()`, `config([... => [3, 5]])`.
- `[$convite, $token] = conviteCom('panel_user');`
- `$hashAntes = $convite->fresh()->token; $expiraAntes = $convite->fresh()->expira_em;`

### Dados de Entrada

```php
$this->travel(4)->days();

$this->artisan('kit:convites-lembrar')->assertSuccessful();
```

### Resultado Esperado

Cinco asserções, e as cinco juntas são a feature:

1. **O lembrete carrega um token DIFERENTE**: capturado por `$n->lembrete === true`, e
   `$tokenDoLembrete !== $token`.
2. **O hash do envio não mudou**: `$convite->fresh()->token === $hashAntes`.
3. **O prazo não foi renovado**: `$convite->fresh()->expira_em->equalTo($expiraAntes)`.
4. **Os dois links abrem o mesmo convite**: `Convite::valido($token)?->is($convite)` e
   `Convite::valido($tokenDoLembrete)?->is($convite)`, os dois `true`.
5. **E o link do lembrete cadastra de verdade**:
   `aceitarConvite($tokenDoLembrete)->assertHasNoFormErrors()` cria o usuário, com
   `assertDatabaseHas('users', ['email' => $convite->email])`.

> **É o caso que a feature existe para não quebrar.** O reflexo de quem for mexer aqui é chamar
> `enviar()`, que rotaciona o token e renova o prazo (`app/Models/Convite.php:129-133`) — e o
> e-mail que a pessoa já tem passa a dar redirect para o login. A asserção 2 acusa isso. Ver ADR-01.

---

## CT-03: dois lembretes — o link do envio sobrevive aos dois, o do primeiro morre

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('mantem vivos apenas o link do envio e o do ultimo lembrete')`

### Precondições

- Seeders, `Notification::fake()`, `config([... => [3, 5]])`, `[$convite, $token] = conviteCom('panel_user');`

### Dados de Entrada

```php
$this->travel(4)->days();
$this->artisan('kit:convites-lembrar');        // 1º lembrete → $tokenLembrete1

$this->travel(2)->days();
$this->artisan('kit:convites-lembrar');        // 2º lembrete → $tokenLembrete2
```

Os dois tokens saem da ordem dos disparos com `$n->lembrete === true`.

### Resultado Esperado

- `$tokenLembrete1 !== $tokenLembrete2` — cada lembrete gera um token próprio.
- **O link do envio continua valendo depois dos dois**: `Convite::valido($token)?->is($convite)` é
  `true`. É a propriedade que ADR-01 compra.
- **O do primeiro lembrete morreu**: `Convite::valido($tokenLembrete1)` é `null` — cada lembrete
  **sobrescreve** `token_lembrete`. **O do último vale**:
  `Convite::valido($tokenLembrete2)?->is($convite)`. Portanto **no máximo dois links vivos por
  convite**, e o teste diz quais.
- `$convite->fresh()->token_lembrete === hash('sha256', $tokenLembrete2)` — a coluna guarda o hash,
  nunca o claro. Espelha a asserção que já existe para `token` (`tests/Kit/ConviteTest.php:241`).

---

## CT-04: o `orWhere` de `valido()` não escapa dos filtros de estado

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('nao aceita token de lembrete de convite aceito, recusado nem expirado')`

### Precondições

- Seeders. **Nenhum mock** — é um caso sobre `Convite::valido()`, sem e-mail nenhum.
- Um convite com **os dois** tokens gravados, montado pelo caminho real:

```php
[$convite, $token] = conviteCom('panel_user');

$tokenLembrete = 'x'.Str::random(63);
$convite->forceFill(['token_lembrete' => hash('sha256', $tokenLembrete)])->save();

// Sanidade: antes de qualquer coisa, os dois valem.
expect(Convite::valido($token)?->is($convite))->toBeTrue()
    ->and(Convite::valido($tokenLembrete)?->is($convite))->toBeTrue();
```

### Dados de Entrada

Os **três** filtros de estado que o método tem hoje, um por vez, com os dois tokens em cada:

```php
// 1º estado: aceito.
$convite->forceFill(['aceito_em' => now()])->save();
Convite::valido($token);
Convite::valido($tokenLembrete);

// 2º estado: recusado (a coluna da wiki irmã, já na árvore).
$convite->forceFill(['aceito_em' => null, 'recusado_em' => now()])->save();
Convite::valido($token);
Convite::valido($tokenLembrete);

// 3º estado: pendente de novo, mas expirado.
$convite->forceFill(['recusado_em' => null, 'expira_em' => now()->subMinute()])->save();
Convite::valido($token);
Convite::valido($tokenLembrete);
```

### Resultado Esperado

- **Nos seis casos, `null`.** Nenhum dos dois tokens abre convite aceito, recusado ou expirado —
  um por filtro de estado, e é o que prova que **nenhum** deles ficou dentro do agrupamento.
- Fecha pela porta HTTP também, que é onde a falha apareceria de verdade:
  `get("/app/register?token={$tokenLembrete}")` responde
  `assertRedirect(Filament::getPanel('app')->getLoginUrl())` nos três estados.

> **É o CT que existe para uma linha de SQL.** `valido()` ganhou um `orWhere` (ADR-01), e
> `orWhere` **sem agrupamento explícito escapa dos outros filtros**: o SQL sairia como
> `WHERE token = ? AND aceito_em IS NULL AND ... OR token_lembrete = ?`, e o `OR`
> parte o `WHERE` inteiro — o token de lembrete passaria a valer **sozinho, sem prazo e sem
> estado**. O sintoma é o pior possível: um convite expirado volta a ser aceitável pelo link do
> lembrete, sem erro, sem log, e a tela simplesmente aceita.
>
> E ele cobra os **três** filtros de estado que o método tem hoje, um por vez, porque a wiki
> `convite-para-usuario-existente` já acrescentou o `whereNull('recusado_em')`
> (`app/Models/Convite.php:172`) à mesma cadeia: nenhum dos três pode acabar dentro do
> agrupamento, e um caso por filtro é o que diz **qual** deles escapou.

---

## CT-05: convite aceito não recebe lembrete, e o token de lembrete morre no aceite

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('nao lembra convite ja aceito')`

### Precondições

- Seeders, `Notification::fake()`, `config([... => [3, 5]])`.
- `[$convite, $token] = conviteCom('panel_user', email: 'aceitou@example.com');`
- Um lembrete já enviado, para haver o que apagar (`travel(4)` + uma execução), e então o aceite
  completo pela tela: `aceitarConvite($token)->assertHasNoFormErrors();`

### Dados de Entrada

```php
$this->travel(2)->days();

$this->artisan('kit:convites-lembrar')->assertSuccessful();
```

### Resultado Esperado

- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2)` — o envio e o lembrete de
  antes do aceite, e **nada** depois dele. `lembretes_enviados` continua `1`.
- **`$convite->fresh()->token_lembrete` é `null`**: `aceitar()` fecha as duas portas. Sem esta
  asserção, um link de lembrete continuaria pendurado num convite consumido — barrado pelo
  `whereNull('aceito_em')` de `valido()`, mas vivo no banco sem razão.

---

## CT-06: convite expirado ou recusado não recebe lembrete

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('nao lembra convite fora de jogo')`

### Precondições

- Seeders, `Notification::fake()`, `config([... => [3, 5]])`, `[$convite] = conviteCom('panel_user');`
- A linha `recusado` usa o caminho real de `convite-para-usuario-existente`, que já está na árvore:
  `$convite->recusar($usuaria)` (`app/Models/Convite.php:372`), com `$usuaria` sendo a conta do
  e-mail do convite — o método exige o dono.

### Dados de Entrada

Um `dataset()` de dois estados, aplicado depois do envio e antes do primeiro prazo —
`forceFill(['expira_em' => now()->subMinute()])` e `recusar($usuaria)` —, seguido em cada linha de
`$this->travel(4)->days()` e de uma execução do comando.

### Resultado Esperado

- `Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1)` — só o envio original.
- `lembretes_enviados` é `0` e `token_lembrete` é `null`.
- **Nenhuma coluna de status foi escrita**: `aceito_em` continua nulo e nada além do estado montado
  mudou. É a asserção que documenta ADR-03 — não existe `--marcar-expirados` aqui, porque expirado
  é derivado de `expira_em`.
- **"Ela disse não" é diferente de "ela não viu".** Sem a linha `recusado`, o kit insistiria com
  quem recusou — o pior comportamento possível desta feature.

---

## CT-07: a execução vira log no channel `autenticacao`, e nenhum log carrega token

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('registra a execucao no channel autenticacao sem vazar token')`

### Precondições

- Seeders, `Notification::fake()`, `config([... => [3, 5]])`, `$canal = espiarAutenticacao();`
- Dois convites pendentes, um deles `fulano@example.com`; o token do envio fica em `$token`.

### Dados de Entrada

```php
$this->travel(4)->days();

$this->artisan('kit:convites-lembrar')->assertSuccessful();
// $tokenDoLembrete capturado pelo objeto da notificação.
```

### Resultado Esperado

- Um `info` `[KitConvitesLembrar@handle]` com `$context` de `total === 2` e `dias === [3, 5]`, e
  dois `info` `[Convite@lembrar]` com `convite_id`, `enviado_em`, `expira_em` e
  `lembretes_enviados === 1`.
- `Log::shouldHaveReceived('channel')->with('autenticacao')` — nenhum channel novo.
- Convite **não devido** não gera linha: repetir a execução no mesmo instante produz só o resumo
  com `total === 0`.
- **Para todo log recebido no channel**, o `$context` serializado não contém nenhuma das cinco
  coisas — e o e-mail aparece mascarado (`Str::mask('fulano@example.com', '*', 3)`):

```php
$semSegredo = function (array $contexto) use ($token, $tokenDoLembrete): bool {
    $serializado = (string) json_encode($contexto);

    return ! str_contains($serializado, $token)
        && ! str_contains($serializado, hash('sha256', $token))
        && ! str_contains($serializado, $tokenDoLembrete)
        && ! str_contains($serializado, hash('sha256', $tokenDoLembrete))
        && ! str_contains($serializado, 'fulano@example.com');
};
```

> **Agora há DOIS segredos por convite**, e o `autenticacao.log` é aberto na tela pelo Logs
> Explorer do `/infra`: a asserção é a tradução literal da regra de `config/logging.php:80-81`. A
> trilha de `/infra/audits` também não guarda nenhum dos dois hashes, porque as duas colunas estão
> fora do `$fillable` — e o caso existente (`tests/Kit/ConviteTest.php:293-299`) já cobre as duas de
> graça, porque `not->toContain('token')` falha também para `token_lembrete`.

---

## CT-08: o e-mail de lembrete tem assunto próprio e o mesmo botão

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('manda o e-mail de lembrete com assunto proprio')`

### Precondições

- **Sem `Notification::fake()`**, de propósito: é o único caso em que `toMail()` renderiza. O
  mailer é `array` (`phpunit.xml:41`), então nada sai da máquina.
- Seeders, `config([... => [3, 5]])`, `[$convite] = conviteCom('panel_user');`

### Dados de Entrada

```php
$this->travel(4)->days();

$this->artisan('kit:convites-lembrar')->assertSuccessful();

$mensagens = Mail::mailer()->getSymfonyTransport()->messages();
```

### Resultado Esperado

- Duas mensagens: a do envio, com assunto começando por `Você foi convidado`, e a do lembrete,
  começando por `Lembrete:`.
- A segunda contém `Aceitar convite` (o `->action()` de
  `app/Notifications/ConviteDeAcesso.php:74`), a frase de lembrete e `/app/register?token=` — o
  link montado pelo mesmo `url()` (`:88-91`), com outro token.

> **É o único caso que exercita o corpo do e-mail do lembrete.** A retrospectiva da wiki
> `convite-de-usuario` registrou a lacuna: "uma feature cujo produto final é um e-mail não pode ter
> zero teste que o construa". Um erro no ternário do assunto ou no `url()` só apareceria como job
> falhado em produção.

---

## CT-09: reenviar zera o contador, reinicia o relógio e mata o link de lembrete

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('reinicia o relogio de lembretes quando o convite e reenviado')`

### Precondições

- Seeders, `Notification::fake()`, `config([... => [3, 5]])`, `[$convite, $token] = conviteCom('panel_user');`

### Dados de Entrada

```php
$this->travel(6)->days();
$this->artisan('kit:convites-lembrar');           // contador vai a 1, nasce $tokenDoLembrete

$tokenNovo = $convite->enviar();                   // reenvio

$this->artisan('kit:convites-lembrar')->assertSuccessful();
```

### Resultado Esperado

- Depois do `enviar()`: `lembretes_enviados` é `0`, `enviado_em` é "agora" e **`token_lembrete` é
  `null`**.
- **Os dois links anteriores morreram**: `Convite::valido($token)` e
  `Convite::valido($tokenDoLembrete)` são `null`; só `Convite::valido($tokenNovo)` devolve o
  convite. É o que mantém verdadeira a promessa da modal de *Reenviar* ("o link anterior deixa de
  funcionar", `ConvitesTable.php:73`) — **os dois** links, não só o do envio.
- A execução seguinte, no mesmo instante do reenvio, não manda lembrete nenhum: o relógio
  recomeçou. Total de disparos: 1 envio + 1 lembrete + 1 reenvio = 3.

> **É o caso de ADR-02.** Se o intervalo contasse de `created_at`, esta última execução mandaria um
> "lembrete" no mesmo dia do reenvio, e em duas execuções o teto se esgotaria — os lembretes do
> envio que importa nunca sairiam, sem erro nenhum no caminho.

---

## CT-10: falha ao notificar não derruba o lote, e convite sem `enviado_em` é ignorado

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('nao deixa um convite estragado derrubar o lote')`

### Precondições

- Seeders, `Notification::fake()`, `config([... => [3, 5]])`, `$canal = espiarAutenticacao();`
- **Três** convites, nesta ordem de criação (o `chunkById` ordena por `id`, e o estragado tem de
  vir primeiro):

```php
// 1º — endereço inválido: o Symfony Mailer lança ao montar o destinatário.
//      Não pode nascer por `conviteCom()`, porque `enviar()` estouraria na hora.
$estragado = Convite::factory()->create([
    'email'   => 'sem-arroba',
    'role_id' => Role::findByName('panel_user')->getKey(),
]);
$estragado->forceFill(['enviado_em' => now()->subDays(4)])->save();

// 2º — anterior à migration: `enviado_em` nulo, e nunca foi enviado.
$antigo = Convite::factory()->create([...]);

// 3º — o bom.
[$bom] = conviteCom('panel_user', email: 'novo@example.com');
```

### Dados de Entrada

```php
$this->travel(4)->days();

$this->artisan('kit:convites-lembrar')->assertSuccessful();
```

### Resultado Esperado

- O comando termina em **sucesso** — não `FAILURE`: um cron que sai com erro por causa de um
  endereço inválido gera alarme falso diário.
- `$bom->fresh()->lembretes_enviados` é `1` e o lembrete dele saiu (`$notifiable->routes['mail']`).
- Um `warning` `[KitConvitesLembrar@handle]` com `convite_id` do estragado, `exception` presente e
  o e-mail **mascarado**.
- `$antigo->fresh()->lembretes_enviados` é `0`: sem `enviado_em`, o kit não sabe de quando contar e
  a linha fica fora do lote (ADR-02). **Nenhum log** para ela — não é anomalia, é ausência de dado.
- `$estragado->fresh()->lembretes_enviados` é `1`: a escrita acontece **antes** do `notify()`, de
  propósito, para que um endereço permanentemente quebrado saia do lote em vez de ser tentado todo
  dia (ADR-03).

> **A ordem é o ponto**: o estragado tem `id` menor, então é o primeiro do chunk. Sem o
> `try/catch`, ele derrubaria a execução e o convite bom ficaria sem lembrete **em toda execução,
> para sempre** — starvation silenciosa.

---

## CT-11: sem convite pendente, e com a feature desligada, o comando termina em sucesso

**Tipo**: `Feature`
**Arquivo**: `tests/Kit/ConviteTest.php`
**Método**: `it('termina com sucesso sem convite pendente e com os lembretes desligados')`

### Precondições

- Seeders, `Notification::fake()`. Nenhum convite no banco para a primeira metade.

### Dados de Entrada

```php
// 1ª metade — banco vazio, e com o default de config (sem override).
$this->artisan('kit:convites-lembrar')
    ->expectsOutputToContain('Nenhum convite pendente')
    ->assertSuccessful();

// 2ª metade — convite devido, mas a lista vazia desliga a feature.
config(['kit.convites.lembretes_dias' => []]);
[$convite] = conviteCom('panel_user');
$this->travel(4)->days();

$this->artisan('kit:convites-lembrar')->assertSuccessful();
```

### Resultado Esperado

- 1ª metade: sucesso, nenhum disparo, e o `info` de resumo com `total === 0` — é o que garante que
  o agendamento diário não vira ruído nem falha numa instalação sem convites (ADR-04, que liga o
  agendamento por default).
- 2ª metade: sucesso, um único disparo (o envio original), `lembretes_enviados` **ainda 0**,
  `token_lembrete` ainda `null`, e a saída diz que os lembretes estão desligados. É o "rollback sem
  migration" do plano, e o convite estar **devido** é o ponto: a chave desliga a feature, não só o
  cronograma.

---

## Índice de Casos

Todos em `tests/Kit/ConviteTest.php`, todos `Feature`.

| ID | Cenário |
| --- | --- |
| CT-01 | **cronograma inteiro em dataset**: nada antes do prazo, um por execução, catch-up sem rajada, teto = `count(dias)` |
| CT-02 | **link novo, e o do envio continua valendo** |
| CT-03 | **dois links vivos: o do envio e o do último lembrete** |
| CT-04 | **o `orWhere` não escapa dos filtros de estado** |
| CT-05 | aceito não recebe (e o token de lembrete morre no aceite) |
| CT-06 | expirado e recusado não recebem |
| CT-07 | log de resumo no channel `autenticacao`, e nenhum log com token (os dois) |
| CT-08 | e-mail de lembrete renderizado |
| CT-09 | reenvio zera o contador e mata os dois links |
| CT-10 | convite estragado não derruba o lote; `enviado_em` nulo fica fora |
| CT-11 | banco vazio e feature desligada |

### Cobertura do que mudou

| Método | CTs |
| --- | --- |
| `Convite::lembrar()` (novo) | CT-01, CT-02, CT-03, CT-07, CT-10 |
| **`Convite::valido()`** | CT-02 e CT-03 (os dois tokens abrem), **CT-04 (o agrupamento)**, CT-05, CT-06, CT-09 |
| `Convite::enviar()` | CT-01 (`enviado_em`), CT-09 (zera contador e limpa `token_lembrete`) |
| `Convite::aceitar()` | CT-05 (limpa `token_lembrete`) |
| `ConviteDeAcesso::toMail()` com `$lembrete` | CT-08, e o `$n->lembrete` de CT-01, CT-02 e CT-03 |
| `KitConvitesLembrar::handle()` | todos; os ramos próprios em CT-01 (nada devido), CT-10 (`catch` e `enviado_em` nulo), CT-11 (vazio e desligado) |

### Rodar antes de implementar

**CT-04, CT-02 e a primeira linha do dataset de CT-01, vistos falhando.**

- **CT-04 é o mais importante dos três**, e é o único que se consegue ver falhar pelo motivo
  certo *depois* de a feature funcionar: escreva `valido()` **sem** o closure de agrupamento de
  propósito, veja o caso ficar vermelho (convite expirado aceitando pelo token de lembrete), e só
  então acrescente o `where(fn ...)`. É a única forma de saber que o teste cobre a armadilha em
  vez de passar por acidente — o caso passa com a implementação certa **e** com uma implementação
  que nem tem `token_lembrete`.
- CT-02 falha antes de tudo porque o comando não existe (`Command "kit:convites-lembrar" is not
  defined.`) — e falha **de novo**, de forma útil, se alguém implementar `lembrar()` chamando
  `enviar()`: aí o hash de `token` muda e a asserção 2 acusa.
- As linhas "nada antes do prazo" de CT-01 são o guarda contra o erro de sinal na comparação de
  datas, e as únicas cujo resultado esperado é "nada aconteceu" — o tipo de asserção que passa por
  acidente quando o comando está quebrado de outra forma.

### Testes existentes a reconferir

| Arquivo | Por quê |
| --- | --- |
| `tests/Kit/ConviteTest.php` | `it('reenvia com token novo e mata o anterior')` (`:225-256`) passa a exercitar também o zeramento do contador e a limpeza de `token_lembrete`. Não muda de expectativa — CT-09 é quem cobra as três coisas |
| `tests/Kit/ConviteTest.php` | `it('recusa registro com convite expirado')` (`:146-158`) e `it('recusa reuso do convite e loga sem expor o token')` (`:160-186`) continuam válidos e ganham importância: são a versão "só com o token do envio" do que CT-04 prova para os dois tokens |
| `tests/Kit/ConviteTest.php` | `it('revoga o convite e o link deixa de valer')` (`:258-300`) já assere que a trilha de auditoria não contém `token`, e a mesma asserção cobre `token_lembrete` de graça (prefixo de string) |
| `tests/Kit/KitUpdateTest.php` | prova que os caminhos novos (o comando, a migration) estão cobertos por `CAMINHOS_DO_KIT` |
| `tests/Tenancy/ConviteTenancyTest.php` | nada a mudar. Conferir apenas que nenhum caso assere o conjunto de colunas de `convites` |
