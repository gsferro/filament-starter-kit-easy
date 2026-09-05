# Casos de Teste — Stat de logins do dia em "Usuários e acesso"

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação —
> ela ainda não existe. Do PRD saíram apenas paths, stack e a tabela `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Contagem do dia e série de 7 dias | 2 | 2 | 4 | padrão |
| Disponibilidade e degradação sem a tabela de log | 2 | 3 | 6 | padrão |

Nenhuma área chega a `completo`, então **não há revisão adversarial** nesta rodada.

- Técnicas aplicadas: EP, **BVA 3-valores** (escalado — ver abaixo), tabela de decisão pequena
  (fonte presente × ausente)
- Cenários: **8** · Regras: **6** · Mutantes previstos: **16** · Sem matador: **0**

**Técnica escalada acima do perfil**: o perfil `padrão` prevê BVA 2-valores; R2 e R3 usam
**3 valores**. O motivo é que as duas fronteiras desta feature são de **tempo** — a virada da
meia-noite e a ponta de 7 dias — e é exatamente ali que `>` × `>=` e `subDays(6)` × `subDays(7)`
divergem. Com 2 valores, o cenário passa nas duas implementações.

## Divergência declarada com a skill

Nenhuma rule de `.ai/rules/` colide com esta skill nesta feature. Divergência apenas de comando: a
skill sugere `pest --parallel --tia` como padrão; este conjunto usa `php artisan test --compact`,
que é o comando declarado em `CLAUDE.md`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | um widget de painel alterado; nenhuma migration, model, policy, rota, comando ou config | CT-01, CT-07 |
| **F** | contar logins de hoje; montar a série de 7 dias; decidir se o stat existe | CT-02…CT-08 |
| **D** | `authentication_log`: login com sucesso × tentativa que falhou; `login_at` **nullable** no model do pacote; dia sem nenhum login; nenhum login em nenhum dia; tabela ausente | CT-02, CT-06, CT-08 |
| **I** | só o render do widget no dashboard `/admin`. Nenhuma rota nova, nenhum comando, nenhum job, nenhum webhook | CT-01 |
| **P** | agrupamento por dia precisa funcionar em SQLite, MySQL e PostgreSQL (`DATE()` muda de nome nos três); Chart.js renderiza no navegador; `authentication_log` é de plugin opcional | CT-04, CT-08 |
| **O** | quem abre o `/admin` com a permissão do widget. **Não gera cenário novo**: a autorização deste widget já é exercitada por `tests/Kit/PermissoesDeWidgetsTest.php`, e esta feature não acrescenta superfície de autorização — o stat vive dentro de um widget já gated | — (declarado) |
| **T** | **a dimensão principal**: virada da meia-noite; borda de 7 dias; ordem cronológica da série; "hoje" mudando entre o arranjo e a asserção | CT-03, CT-04, CT-05 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o widget passa a exibir **seis** stats, e os cinco existentes continuam | contagem (padrão) | RQ-01, RQ-06 | EP | CT-01 |
| R2 — o valor do stat novo é a contagem de logins bem-sucedidos **de hoje** | contagem (padrão) | RQ-02 | EP + BVA 3-valores | CT-02, CT-03 |
| R3 — a série tem **7 posições**, uma por dia de calendário, da mais antiga até hoje | contagem (padrão) | RQ-04 | BVA 3-valores + ordem | CT-04, CT-05 |
| R4 — dia sem nenhum login entra na série como **zero**, e não é omitido | contagem (padrão) | RQ-04 | EP | CT-06 |
| R5 — o gráfico é propriedade **do próprio stat**, não um widget separado | contagem (padrão) | RQ-03 | EP estrutural | CT-07 |
| R6 — sem a tabela de log de acesso, o stat **não** é exibido e os cinco continuam | disponibilidade (padrão) | RQ-05, RQ-06 | tabela de decisão | CT-08 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `loginsPorDia()` e `logDeAcessoDisponivel()` | escolha de implementação | detalhe do cenário |
| rótulo `'Logins hoje'` do stat | **só o PRD determina, e é texto visível** | ⚠️ pergunta; nenhum `Então` afirma o rótulo literal — CT-01 identifica o stat pelo **gráfico**, que é o que RQ-03 exige e nenhum outro stat tem |
| ícone e cor (`heroicon-o-arrow-right-on-rectangle`, `success`) | só o PRD determina, e é visível | ⚠️ pergunta; sem cenário |
| formato do rótulo do eixo (`d/m`) | só o PRD determina, e é visível | ⚠️ pergunta; CT-04 afirma que há **7 rótulos distintos em ordem cronológica**, não o formato deles |
| "o valor sai da última posição da série" (ADR-04) | **escolha de implementação** | detalhe. CT-02 afirma as duas coisas que o requisito pede separadamente — o valor **é** a contagem de hoje **e** a última posição da série **é** a contagem de hoje. A coerência entre elas é consequência, não oráculo |
| `Stat::chart()` como mecanismo | escolha de implementação (ADR-01) | detalhe; CT-07 afirma que **existe um gráfico dentro deste stat**, que é RQ-03 |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **RQ-02 — tentativa que falhou conta?** Premissa: **não**. Bloqueia R2. CT-02 marcado `@premissa`.
- **RQ-04 — "últimos 7 dias" inclui hoje?** Premissa: **sim**, hoje é a última posição. Bloqueia
  R3. CT-04 e CT-05 marcados `@premissa`.
- **RQ-06 — sem a tabela, o que acontece com a sexta caixa?** Premissa: o stat **não aparece**.
  Bloqueia R6. CT-08 marcado `@premissa`.
- **Novas, levantadas nesta derivação** — rótulo do stat, ícone, cor e formato do rótulo do eixo
  não são determinados pelo requisito. Nenhuma delas bloqueia regra; nenhuma tem cenário. Ver
  bloco pronto para colagem em `## Perguntas para o 00-requisito.md`.

## Setup Global

### Suíte

`tests/Kit` — `Tests\TestCase`, single-tenant. O widget é do painel `admin`, que não tem tenancy,
e nada nesta feature depende de organização.

### Personas

- `admin` — `usuarioDoKit('admin', 'admin@example.com')`. **Persona única**, e isso é decisão: esta
  feature não acrescenta superfície de autorização, e a barreira do widget já é exercitada em
  `tests/Kit/PermissoesDeWidgetsTest.php`. Repetir a matriz de papéis aqui seria cobertura
  duplicada sem mutante novo.

### Fixtures

Não há factory para `AuthenticationLog` no projeto. As linhas nascem pela relação morph do
pacote — **caminho medido**, não presumido:

```php
$usuario->authentications()->create([
    'login_at'         => $quando,
    'login_successful' => true,
    'ip_address'       => '1.2.3.4',
]);
```

> `AuthenticationLoggable::authentications()` é um `MorphMany`
> (`vendor/rappasoft/laravel-authentication-log/src/Traits/AuthenticationLoggable.php:11-14`), então
> as duas colunas do morph são preenchidas pela relação — `$fillable` do model não as inclui, e
> `AuthenticationLog::create([...])` com elas no array **não** grava o vínculo.
>
> Medido em 2026-09-04 com um teste descartável em `tests/Kit`: 1 linha criada, `authenticatable_id`
> igual à chave do usuário. Confirmado antes de replicar em oito cenários.

### Fakes

Nenhum. Não há e-mail, fila, HTTP, notificação nem log nesta feature.

### Estratégia de DB

`RefreshDatabase`, aplicado globalmente a `tests/Kit` por `tests/Pest.php`.

### Como ler os stats

Os cenários afirmam sobre os objetos `Stat`, não sobre o HTML. `getStats()` e `getCachedStats()`
são `protected`, então o acesso é por closure vinculada ao componente montado:

```php
$componente = livewire(UsuariosVisaoGeralStats::class)->instance();
$stats      = (fn (): array => $this->getCachedStats())->call($componente);
```

**Por que assim, e não `assertSee()` no HTML**: a série vai para a página dentro de um atributo
Alpine JSON-escapado. `assertSee('0')` casaria com qualquer zero da página — inclusive o de outro
stat —, e um conjunto inteiro passaria com a série errada. O oráculo é o **valor**, e ele está no
objeto. Ver "Assertion proibida como oráculo único".

> `->instance()` existe em `Livewire\Features\SupportTesting\Testable:332`.

### Tempo

Todo cenário que fala em dia usa `travelTo()` com instante explícito e `travelBack()` (ou a forma
de closure). Sem congelar, "hoje" pode virar entre o arranjo e a asserção, e o cenário da borda
vira flake — que é pior que ausente, porque é desligado.

O app roda em `app.timezone = UTC` (`config/app.php:68`) e o SQLite da suíte grava `datetime` sem
fuso, então "hoje" é o dia UTC. Isso não é escolha desta feature; é o que existe.

---

## Regra R1 — o widget passa a exibir seis stats, e os cinco existentes continuam

> `RQ-01`, `RQ-06` · perfil **padrão** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Stat de logins do dia no painel administrativo

  Regra: o widget "Usuários e acesso" passa a exibir seis stats, sem perder nenhum dos cinco que já tinha

    Cenário: [CT-01] o sexto stat é acrescentado, não substitui nenhum
      Dado uma instalação com a tabela de log de acesso disponível
      Quando o administrador carrega o widget "Usuários e acesso"
      Então o widget expõe 6 stats
      E exatamente 1 deles tem gráfico
      E os 5 sem gráfico continuam sendo os mesmos rótulos de antes desta feature
```

O `E` sobre os cinco rótulos é o que separa "acrescentou" de "substituiu": sem ele, uma
implementação que troca o stat de Permissões pelo de logins também devolve 6.

**Camada**: componente Livewire (`tests/Kit`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o stat novo **substitui** um dos cinco (`$stats[4] = …` em vez de `$stats[] = …`) | CT-01 (terceira asserção) |
| M2 | o widget inteiro some numa instalação sem o plugin, porque a guarda foi para `fonteDeDadosDisponivel()` | CT-08 |
| M3 | o stat é acrescentado duas vezes (condicional dentro do array **e** depois dele) | CT-01 (primeira asserção) |

---

## Regra R2 — o valor do stat novo é a contagem de logins bem-sucedidos de hoje

> `RQ-02` · perfil **padrão** · técnica: **EP** (sucesso × falha) + **BVA 3-valores** na virada do dia (granularidade: 1 segundo, `login_at` é `datetime`)

Fixture discriminante — hoje e ontem têm contagens **diferentes**, e há uma falha hoje:

| Dia | Logins com sucesso | Tentativas que falharam |
|---|---|---|
| hoje | **3** | 2 |
| ontem | **5** | 0 |

Valores diferentes de propósito: com 3 e 3, um off-by-one no índice da série passaria. Com a
falha de hoje, ignorar `login_successful` daria 5 em vez de 3.

```gherkin
# language: pt

  Regra: o valor exibido é a quantidade de logins bem-sucedidos de hoje

    Cenário: [CT-02] tentativa que falhou não conta, e o dia é hoje @premissa
      Dado 3 logins com sucesso hoje e 2 tentativas que falharam hoje
      E 5 logins com sucesso ontem
      Quando o administrador carrega o widget
      Então o valor do stat de logins é 3
      E a última posição da série também é 3

    Esquema do Cenário: [CT-03] a virada da meia-noite decide de que dia é o login
      Dado um único login com sucesso em "<instante>"
      Quando o administrador carrega o widget às 12:00 de hoje
      Então o valor do stat de logins é <valor_hoje>

      Exemplos:
        | instante                       | valor_hoje | # borda    |
        | ontem às 23:59:59              | 0          | borda−1s   |
        | hoje às 00:00:00               | 1          | borda      |
        | hoje às 00:00:01               | 1          | dentro     |
        | hoje às 23:59:59               | 1          | fim do dia |
```

> A segunda asserção de CT-02 **não** afirma que o valor sai da série — afirma que as duas
> grandezas que o requisito pede separadamente (o número do dia, RQ-02, e a ponta da série de 7
> dias, RQ-04) medem a mesma coisa. Uma implementação com duas consultas divergentes fica vermelha
> aqui, e é o único lugar onde ela fica.

**Camada**: componente Livewire com `travelTo()` (`tests/Kit`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | o valor é o de **ontem** (off-by-one no índice da série) | CT-02 (3 × 5) |
| M5 | `login_successful` não é filtrado | CT-02 (daria 5 hoje) |
| M6 | fronteira do dia com `>` em vez de `>=` no início — o login de 00:00:00 cai fora | CT-03 (linha "borda") |
| M7 | fim do dia fechado em `endOfHour()`/`00:00` em vez de `endOfDay()` — o login das 23:59:59 some | CT-03 (linha "fim do dia") |
| M8 | número e série vindos de duas consultas com filtros diferentes | CT-02 (segunda asserção) |

---

## Regra R3 — a série tem 7 posições, uma por dia de calendário, da mais antiga até hoje

> `RQ-04` · perfil **padrão** · técnica: **BVA 3-valores** na ponta da janela (granularidade: 1 dia) + ordem

```gherkin
# language: pt

  Regra: a série cobre exatamente sete dias de calendário, terminando em hoje

    Cenário: [CT-04] sete posições, em ordem cronológica crescente @premissa
      Dado 1 login com sucesso em cada um dos últimos 7 dias, hoje inclusive
      Quando o administrador carrega o widget
      Então a série do stat de logins tem exatamente 7 posições
      E os 7 rótulos são distintos entre si
      E o rótulo da última posição corresponde a hoje

    Esquema do Cenário: [CT-05] a ponta antiga da janela @premissa
      Dado um único login com sucesso "<quando>"
      Quando o administrador carrega o widget
      Então a soma das 7 posições da série é <somatorio>

      Exemplos:
        | quando        | somatorio | # borda   |
        | há 5 dias     | 1         | dentro    |
        | há 6 dias     | 1         | borda     |
        | há 7 dias     | 0         | borda+1d  |
```

> O `Então` de CT-05 é a **soma da série**, e não "a posição 0 vale 1": somar não pressupõe onde a
> implementação colocaria o dia, então o cenário mede a janela sem medir o layout do array.

**Camada**: componente Livewire com `travelTo()` (`tests/Kit`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | laço com `DIAS` em vez de `DIAS - 1` no primeiro dia — a série tem 8 posições ou começa um dia antes | CT-04 (primeira asserção) e CT-05 (linha "borda+1d") |
| M10 | `subDays(7)` no início da janela — o dia de 7 dias atrás entra | CT-05 (linha "borda+1d") |
| M11 | série montada do mais recente para o mais antigo | CT-04 (terceira asserção) |
| M12 | rótulo derivado do índice do laço e não da data — os 7 rótulos repetem ou não correspondem aos dias | CT-04 (segunda e terceira asserções) |

---

## Regra R4 — dia sem nenhum login entra na série como zero

> `RQ-04` · perfil **padrão** · técnica: **EP** (dia com registro × dia vazio)

Esta é a regra que mais importa visualmente e a que uma implementação ingênua erra: construir a
série a partir do **resultado da consulta** em vez do calendário faz o dia vazio sumir, a série
encolher e a curva "pular" o buraco — um fim de semana sem ninguém vira um trecho reto.

```gherkin
# language: pt

  Regra: dia sem nenhum login ocupa a sua posição na série, com valor zero

    Cenário: [CT-06] o dia vazio não é omitido
      Dado 2 logins com sucesso hoje
      E 4 logins com sucesso há 3 dias
      E nenhum login nos demais dias da semana
      Quando o administrador carrega o widget
      Então a série do stat de logins tem 7 posições
      E 5 dessas posições valem exatamente 0
      E nenhuma posição da série é nula
```

**Camada**: componente Livewire com `travelTo()` (`tests/Kit`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | série construída a partir do resultado da consulta — o dia vazio some e a série tem 2 posições | CT-06 (primeira asserção) |
| M14 | dia vazio vira `null` em vez de `0` | CT-06 (terceira asserção) |

---

## Regra R5 — o gráfico é propriedade do próprio stat, não um widget separado

> `RQ-03` · perfil **padrão** · técnica: **EP estrutural**

```gherkin
# language: pt

  Regra: o histórico é desenhado dentro da mesma caixa do número, e só nela

    Cenário: [CT-07] só o stat de logins tem gráfico
      Dado uma instalação com logins registrados nos últimos 7 dias
      Quando o administrador carrega o widget "Usuários e acesso"
      Então exatamente 1 dos 6 stats tem gráfico
      E o stat que tem gráfico é o mesmo cujo valor é a contagem de logins de hoje
```

> A segunda asserção é o que impede o falso ✅: "um stat tem gráfico" ficaria verde se o gráfico
> tivesse sido pendurado no stat de Permissões.

**Camada**: componente Livewire (`tests/Kit`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | o gráfico é aplicado a todos os stats do widget | CT-07 (primeira asserção) |
| M16 | o gráfico é aplicado ao stat errado | CT-07 (segunda asserção) |

---

## Regra R6 — sem a tabela de log de acesso, o stat não é exibido e os cinco continuam

> `RQ-05`, `RQ-06` · perfil **padrão** · técnica: **tabela de decisão**

| Tabela de log presente? | Stat de logins | Os outros cinco |
|---|---|---|
| sim | exibido | exibidos |
| **não** | **não exibido** | **exibidos** |

A segunda linha é o cenário, e ela tem **duas** afirmações que falham por motivos opostos: o stat
aparecendo (com zero, mentindo sobre segurança) e os cinco sumindo junto (guarda no lugar errado).

```gherkin
# language: pt

  Regra: numa instalação sem a tabela de log de acesso o stat de logins não existe, e nenhum outro é afetado

    Cenário: [CT-08] o plugin ausente derruba só o stat que depende dele @premissa
      Dado uma instalação em que a tabela de log de acesso não existe
      Quando o administrador carrega o widget "Usuários e acesso"
      Então o widget expõe 5 stats
      E nenhum deles tem gráfico
      E a contagem de usuários continua sendo exibida
```

> A terceira asserção é a que mata o mutante mais caro: a guarda declarada como
> `fonteDeDadosDisponivel()` esconderia o widget **inteiro**, e "0 stats" também satisfaria
> "nenhum tem gráfico".

**Camada**: componente Livewire (`tests/Kit`), com a coluna/tabela derrubada no arranjo
(`Schema::drop(config('authentication-log.table_name'))` dentro do `RefreshDatabase`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M2 | a guarda vai para `fonteDeDadosDisponivel()` e esconde o widget inteiro | CT-08 (primeira e terceira asserções) |
| M17 | sem a tabela, o stat aparece com valor `0` | CT-08 (primeira e segunda asserções) |
| M18 | sem a tabela, a consulta estoura e o widget derruba o dashboard | CT-08 (o cenário nem chega a asserir — falha na montagem) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: o widget não recebe id de recurso nenhum; ele agrega a instalação inteira |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: sem Action. A barreira do widget já é exercitada por `tests/Kit/PermissoesDeWidgetsTest.php`, e esta feature não acrescenta superfície |
| Idempotência (ancorada no agregado) | **não se aplica**: a feature só lê. Carregar o widget duas vezes não muda nada — não há agregado a ancorar |
| Concorrência | **não se aplica**: sem contador, saldo ou limite |
| **Fronteira no ponto de entrada** | CT-03 (virada do dia), CT-05 (ponta da janela) |
| Domínio condicionado | **não se aplica**: um único campo de data, sem discriminador |
| Estado × operação de escrita | **não se aplica**: a feature não escreve |
| Ausente ≠ null ≠ vazio | CT-06 (dia vazio é `0`, nunca `null`). **`login_at` nulo**: lacuna declarada — a coluna é nullable no model do pacote (`AuthenticationLog` PHPDoc), e uma linha com `login_at` nulo não cai em nenhuma janela nem em nenhum dia. Tentado escrevê-la no arranjo; o cenário resultante não discrimina, porque **toda** implementação plausível a ignora (`whereBetween` exclui `null` em SQL). Cenário não escrito por não matar mutante |
| Paginação / ordenação | CT-04 (ordem cronológica da série). Paginação não se aplica |
| Timezone / DST | **lacuna declarada**: tentado divergir `config(['app.timezone' => 'America/Sao_Paulo'])` do banco para expor leitura em fuso errado — não discrimina, porque o SQLite da suíte grava `datetime` sem fuso e o mesmo Carbon escreve o arranjo e lê a consulta, então as duas implementações convergem. O que **é** observável é a virada do dia no fuso do app, e está em CT-03 |
| Unicode / limite de varchar | **não se aplica**: nada de texto livre |
| Unicidade + soft delete | **não se aplica**: sem unicidade. Usuário excluído com login no histórico continua contando — o requisito conta **logins**, não pessoas |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: nenhum payload chega a esta feature |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: contagem inteira |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | seis stats, o sexto acrescentado | R1 | EP | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M1, M3 |
| CT-02 | falha não conta, o dia é hoje | R2 | EP | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M4, M5, M8 |
| CT-03 | virada da meia-noite | R2 | BVA 3-valores | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M6, M7 |
| CT-04 | sete posições em ordem crescente | R3 | BVA + ordem | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M9, M11, M12 |
| CT-05 | ponta antiga da janela | R3 | BVA 3-valores | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M9, M10 |
| CT-06 | dia vazio vale zero e não some | R4 | EP | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M13, M14 |
| CT-07 | só o stat de logins tem gráfico | R5 | EP estrutural | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M15, M16 |
| CT-08 | plugin ausente derruba só o stat dele | R6 | tabela de decisão | Livewire | `tests/Kit/StatDeLoginsDoDiaTest.php` | M2, M17, M18 |

## Sem CT-B

**O `05` não é criado.**

O gate exige que o cenário afirme sobre algo que só o navegador prova. Aqui existe exatamente uma
afirmação assim — *"o `<canvas>` do sparkline renderiza sem erro de JavaScript"* — e ela **já é
coberta**: `/admin` está em `telasDoKit()['admin']` (`tests/Pest.php:250`) e
`tests/Browser/TelasDoKitTest.php:41-45` roda `visit($rotas)->assertNoJavaScriptErrors()` sobre a
lista inteira. Um CT-B novo repetiria essa asserção com outro nome.

Todo o resto do que esta feature afirma — quantidade de stats, valor do dia, 7 posições, zeros,
rótulos, qual stat tem gráfico, degradação sem o plugin — está nos objetos `Stat`, é falsificável
por teste de componente e roda em milissegundos.

Cenários cogitados e cortados:

| Cenário cogitado | Por que foi cortado |
|---|---|
| `/admin` sem erro de JS com o `<canvas>` novo | já coberto por `TelasDoKitTest` |
| a curva do sparkline tem 7 pontos desenhados | é pixel; o oráculo útil é o array, e está em CT-04 |
| o sparkline acompanha o tema escuro | a cor sai de `chartColor()`, que é token do Filament; testar isso é testar o framework |
| o widget fica com 3 colunas em vez de 4 com 6 stats | é `StatsOverviewWidget::getColumns()` do vendor (`6 % 3 !== 1` → 3 colunas). RQ-06 é satisfeito por consequência, não por código nosso |

## Perguntas para o `00-requisito.md`

Bloco pronto para colagem em `## Ambiguidades e Perguntas Abertas`. Nenhuma bloqueia regra —
todas são texto visível que o requisito não determina, e por isso **nenhuma tem cenário**:

```markdown
- **Levantadas na derivação dos casos de teste** — o requisito não determina o texto visível do
  stat novo. Nenhuma bloqueia implementação; todas mudam só o que se lê na tela.
  - Rótulo do stat: assumido `Logins hoje`.
  - Ícone e cor: assumidos `heroicon-o-arrow-right-on-rectangle` e `success`.
  - Formato do rótulo de cada dia no gráfico: assumido `d/m`.
  - **Se negados**: troca de string, sem efeito em nenhum caso de teste — nenhum `Então` afirma
    esses valores, de propósito.
```

## Fechamento do ciclo — mutation testing

Depois de implementar:

```bash
php artisan test --compact tests/Kit/StatDeLoginsDoDiaTest.php
vendor/bin/pest tests/Kit/StatDeLoginsDoDiaTest.php --mutate --path=app/Filament/Admin/Widgets/UsuariosVisaoGeralStats.php
```

`pestphp/pest-plugin-mutate ^5.0` está declarado direto no `composer.json` — não é transitivo.

Mutante sobrevivente é traduzido de volta para a lacuna de derivação (borda faltando, oráculo
fraco, partição não exercitada) e vira cenário novo **aqui** — nunca ajuste no teste para ficar
verde.
