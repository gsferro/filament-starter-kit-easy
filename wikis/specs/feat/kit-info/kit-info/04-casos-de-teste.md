# Casos de Teste — `kit:info`: um comando que exibe os dados customizados do projeto

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — ela
> não existe. O `01` entrou só para paths, nomes de classe e a decisão "sem superfície de UI".

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Existência e descoberta do comando (RQ-01) | 1 | 1 | 1 | mínimo |
| Exibição das respostas da instalação (RQ-02) | 2 — integra `AdministradorDaInstalacao`, `CorPrimaria`, `Tenant`, settings | 1 | 2 | mínimo, **técnica escalada** para EP no discriminador da cor (ver Mapa) |
| Exibição das configurações do kit sem segredo (RQ-03) | 2 | **3** — vazamento de segredo em saída colável | 6 | padrão |
| Somente leitura (RQ-04) | 2 — o passo 2 do PRD separa ler de escrever num método que hoje escreve | 2 — config do processo alterada em silêncio | 4 | padrão |
| Fonte e divergência `.env` × banco (premissa A2) | **3** — comparação com coerção de tipo | 1 | 3 | mínimo, **técnica escalada** para EP de tipos (falso positivo é o defeito inteiro da área) |
| Banco indisponível (ADR-06) | 2 | 2 — comando morre justamente quando mais serve | 4 | padrão |
| Documentação nos dois idiomas (premissa A6) | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: EP (partição), rastreio de efeito (log, somente leitura), EP de tipos na
  normalização, partição exaustiva de lista (`encrypted()`, `mapaDeConfiguracao()`)
- Cenários: **17** · Regras: **8** · Mutantes previstos: **27** · Sem matador: **1** (declarado)
- Camadas: 16 × `Feature` (console, suíte `Kit`) · 1 × `Feature` (suíte `Tenancy`) · 0 × Browser

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | 1 comando (`app/Console/Commands/KitInfo.php`); 2 métodos extraídos em `ConfiguracoesDoKit`; 1 troca de chamada no provider e no customizador; 5 arquivos de documentação | CT-01, CT-11, CT-17 |
| **F**unction | ler config vigente; listar administradores; contar organizações; decidir rótulo da cor; comparar `.env` × banco; mascarar segredo; logar | CT-02…CT-10, CT-12…CT-16 |
| **D**ata | 44 propriedades do settings (6 segredos); 0..N administradores; 0..N organizações; valores `null` × `''` × `false`; `int` × `string` na porta de e-mail; nome com acento | CT-03, CT-04, CT-06, CT-07, CT-09, CT-13 |
| **I**nterfaces | comando artisan, sem argumento nem opção; canal de log `configuracoes` | CT-01, CT-16 |
| **P**latform | SQLite `:memory:` na suíte; o comando não depende de banco específico. **Declarado vazio**: nenhuma dependência de plataforma além do próprio Laravel | — |
| **O**perations | rodado por quem tem terminal, em qualquer estado da instalação: antes do `migrate`, em CI sem banco, em projeto com settings desalinhado do `.env`; saída colada em chamado de suporte | CT-08, CT-09, CT-14, CT-15 |
| **T**ime | **Declarado vazio**: nenhuma data, expiração ou agendamento. Única dimensão temporal é "rodar duas vezes", coberta como idempotência | CT-12 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O comando `kit:info` existe, é descoberto pelo artisan e termina com sucesso | existência (mínimo) | RQ-01 | EP (1 partição válida) | CT-01 |
| R2 — As cinco respostas da instalação aparecem com o valor **vigente** (o que `config()` diz depois do alinhamento com o banco) | instalação (mínimo → **EP escalada**: a cor tem 3 partições que o rótulo distingue, e o e-mail tem 3 cardinalidades) | RQ-02 | EP no discriminador da cor; EP na cardinalidade de administradores; partição "banco venceu o `.env`" | CT-02, CT-03, CT-04, CT-05, CT-15 |
| R3 — Toda propriedade de `ConfiguracoesDoKit` aparece, na ordem do mapa, com o valor vigente legível | configurações (padrão) | RQ-03 | partição exaustiva da lista; EP no tipo do valor (bool, vazio, texto) | CT-06, CT-07 |
| R4 — Segredo nunca sai em claro — nem na listagem, nem na divergência, nem no log | configurações (padrão) | RQ-03 | partição exaustiva de `encrypted()`; rastreio de efeito (log) | CT-08, CT-16 |
| R5 — O comando não altera nada: `.env`, settings gravados, config do processo, e é idempotente | somente leitura (padrão) | RQ-04 | rastreio de efeito nas três direções (não aconteceu; duas vezes = uma) | CT-10, CT-11, CT-12 |
| R6 — O cabeçalho diz qual fonte está valendo; a seção de divergência só existe quando `.env` e banco dizem coisas diferentes, comparadas pelo texto normalizado | fonte (mínimo → **EP escalada** para tipos) | premissa A2 | EP: com/sem tabela; com/sem divergência; `null`×`''`; `int`×`string` | CT-09, CT-13, CT-14 |
| R7 — Banco indisponível degrada a linha, não o comando | banco indisponível (padrão) | ADR-06 (deriva de RQ-01: "existe um comando" que funcione em qualquer estado) | EP: banco ausente | CT-14, CT-15 |
| R8 — O comando está documentado onde os outros `kit:*` estão, nos dois idiomas | documentação (mínimo) | premissa A6 | partição exaustiva de idiomas | CT-17 |

**Técnica escalada acima do perfil** (permitido; rebaixar não):

- R2: perfil `mínimo` pede 1 cenário; a cor tem **três** partições (`[]`, hex, nome) e ADR-03 diz que o
  rótulo é decidido pelo **formato** do retorno — um cenário só não distingue "hex venceu" de "nome
  venceu". Esquema com 3 linhas.
- R6: perfil `mínimo`, mas o defeito inteiro da área é o **falso positivo por tipo**; sem EP de tipos
  o cenário passa com `===` cru e o comando acusa divergência em toda instalação.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nome do comando `kit:info` | escolha registrada como premissa **A3** no `00`; o requisito só diz "command do kit" | oráculo **sob premissa** — CT-01 marcado `@premissa` |
| Textos exatos das linhas (`Nome do projeto`, `padrão do Filament (âmbar)`, `indisponível (banco não acessível)`) | comportamento visível que o requisito não determina | os cenários afirmam sobre o **valor** (o nome gravado, a palavra `Blue`, o hex) e sobre **ausência** (e-mail em claro), não sobre o texto do rótulo. Onde o rótulo é o único observável (`indisponível`, `ligada`/`desligada`, `definida`/`vazia`), o cenário usa a palavra e isso fica registrado como **pergunta P1** |
| `Str::headline($propriedade)` como rótulo | escolha de implementação (ADR-02) | detalhe do cenário: CT-06 afirma que **cada propriedade** aparece, usando a mesma função para gerar o esperado — se a implementação trocar o formato, o teste muda junto, e isso é aceito porque o requisito não fala em rótulo |
| Métodos `gravadoNoBanco()`, `valoresDosArquivos()` | escolha de implementação | não aparecem em nenhum `Então`; CT-11 usa `valoresDosArquivos()` **só para calcular o valor esperado** do arquivo, e isso está declarado |
| Seção de divergência | premissa **A2** | cenários marcados `@premissa` |
| Documentação em pt/en | o requisito não pede; é convenção do projeto guardada por `SiteDeDocumentacaoTest` | **pergunta P2** → premissa A6 no `00`; CT-17 marcado `@premissa` |
| Um único log `debug` no canal `configuracoes` | escolha de implementação (canal e nível) | oráculo só para o que RQ-03 determina — **nenhum segredo** no log. Canal e nível são detalhe do cenário |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **P1** — os textos de estado (`ligada`/`desligada`, `definida`/`vazia`, `indisponível`) são
  aceitáveis como escritos no PRD? Bloqueia o texto exato de CT-05, CT-08, CT-14. Premissa: sim.
- **P2** — o comando deve ser documentado nos dois idiomas, como os demais `kit:*`? Bloqueia R8.
  Premissa: sim (é o que `SiteDeDocumentacaoTest` já exige de qualquer página).

## Setup Global

### Personas

Nenhuma autenticada — é console. O que existe é **estado do banco**:

- `administrador da instalação` — `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class])`,
  o mesmo `beforeEach` de `tests/Kit/KitAdminTest.php:19-21`. E-mail `config('kit.admin.email')`
  (`admin@example.com` na suíte).
- `segundo administrador` — `usuarioDoKit('master_global', 'segundo@example.com')` (`tests/Pest.php:461`).

### Fixtures

- Settings: com `RefreshDatabase`, as migrations de `database/settings/` semeiam o grupo `kit` a
  partir de `config()`. Para fazer o banco divergir do `.env`: `gravarConfiguracao($propriedade, $valor)`
  seguido de `alinharConfiguracoesDoKit()` (`tests/Pest.php:343`, `:364`) — é o que o boot faria.
- Organizações (suíte `Tenancy`): `tenant('Acme', 'acme')`, `tenant('Globex', 'globex')`, `tenant('Initech', 'initech')`
  (`tests/Pest.php:381`).
- Banco indisponível: `Schema::dropAllTables()` antes de rodar — toda consulta lança, inclusive
  `hasTable`.

### Fakes

- `espiarConfiguracoes()` (`tests/Pest.php:372`) — espia só o canal `configuracoes`; os demais seguem reais.
- Nenhum `Http`, `Mail`, `Queue`: o comando não os toca.

### Estratégia de DB

- `RefreshDatabase` global (`tests/Pest.php:53-56` para `Kit`, `:77-80` para `Tenancy`), SQLite `:memory:`.
- Cenário multi-organização vai para **`tests/Tenancy`** — `TenancyTestCase` liga `permission.teams`
  antes das migrations (`.ai/rules/testes.md`).

### Como afirmar sobre a saída

`$this->artisan('kit:info')->expectsOutputToContain(...)` e `->doesntExpectOutputToContain(...)`
(Laravel 13, `console-tests.md`). Para **ordem** e **contagem** (CT-06, CT-12), capturar com
`Artisan::call('kit:info')` + `Artisan::output()`.

**O oráculo é a LINHA, quase sempre** — e por **dois** motivos independentes, os dois medidos em
execução vermelha. O helper é `linhaDoKitInfo($saida, $rotulo)`, em `tests/Pest.php` (uso cruzado
entre `tests/Kit` e `tests/Tenancy`).

1. **O mesmo texto aparece legitimamente em mais de uma linha.** `Starter Kit` é o nome do projeto
   **e** o remetente de e-mail (`mail_from_name` nasce de `${APP_NAME}`); `#zz` está na linha da cor
   **e** na linha `Cor Primaria Hex`, que mostra o valor vigente de propósito. Uma asserção de
   ausência sobre a saída inteira reprova o comando **correto** — foi o que reprovou CT-02 e CT-03.

2. **`expectsOutputToContain()` casa no máximo UMA substring esperada por linha impressa.**
   `PendingCommand::createABufferedOutputMock()` registra uma expectativa de Mockery por substring
   (`vendor/laravel/framework/src/Illuminate/Testing/PendingCommand.php:615-622`) e o Mockery
   satisfaz **uma** expectativa por chamada de `doWrite` — a primeira que casa. Duas substrings
   esperadas na mesma linha deixam a segunda pendente, e `verifyExpectations()` (`:531-533`) falha
   com `Output does not contain "…"` **com o texto na tela**. Foi assim que CT-13b
   (`mail.mailers.smtp.password` + `valores não exibidos`) e CT-15 (`ligada` + `Organizações` +
   `3 cadastrada`) reprovaram sem defeito nenhum no comando.

Empilhar `expectsOutputToContain()` continua correto para substrings em linhas **diferentes** —
é o que CT-04, CT-08 e CT-14 fazem. Os rótulos das duas seções nunca colidem: a de instalação é
escrita à mão (`Cor primária`) e a do settings sai do `Str::headline()` (`Cor Primaria`).

### Arranjo de propriedade cifrada

**`gravarConfiguracao()` não serve para os seis segredos.** Ela grava o `payload` em texto claro, e
a leitura do settings tenta decifrar — o valor nunca chega ao comando, e o caso reprova por defeito
do arranjo. Para segredo, o arranjo é a API: `$settings->{$p} = $valor; $settings->save();`. É a
mesma separação que o projeto já documenta em `tests/Kit/ConfiguracoesDoKitTest.php:85-88`.

### Como derrubar o banco (CT-14)

`Schema::drop()` nas tabelas que o comando consulta, **não `Schema::dropAllTables()`**: em SQLite o
`dropAllTables()` do Laravel emite um `vacuum`, e sob `RefreshDatabase` a suíte roda dentro de uma
transação — `cannot VACUUM from within a transaction`.

---

## Regra R1 — O comando existe, é descoberto e termina com sucesso

> `RQ-01` · perfil **mínimo** · técnica: **EP** (uma partição válida; a inválida — comando
> inexistente — é o estado atual e não precisa de cenário)

```gherkin
# language: pt

Funcionalidade: kit:info exibe os dados customizados do projeto

  Regra: Existe um comando do kit, descoberto pelo artisan, que termina com sucesso

    @premissa(A3)
    Cenário: [CT-01] o comando está registrado no namespace kit e termina com sucesso
      Dado uma instalação migrada e semeada
      Quando o operador executa "php artisan kit:info"
      Então o código de saída é 0
      E a lista de comandos do artisan contém "kit:info"
```

**Nota de camada**: "a lista contém" é `Artisan::all()` com a chave `kit:info` — prova a
**descoberta**, não só a execução; um comando fora de `app/Console/Commands` roda por `Artisan::call`
de classe e não aparece em `php artisan list kit`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | signature `kit-info` ou `info` (fora do namespace) | CT-01 (chave na lista) |
| M2 | `return self::FAILURE` / `return 1` por engano no fim | CT-01 (código 0) |
| M3 | classe criada fora de `app/Console/Commands` (não descoberta) | CT-01 (chave na lista) |

---

## Regra R2 — As cinco respostas da instalação aparecem com o valor vigente

> `RQ-02` · perfil **mínimo, EP escalada** · técnica: **EP** no discriminador da cor (3 partições) e
> na cardinalidade de administradores (0, 1, 2); partição "banco venceu o `.env`"

```gherkin
# language: pt

  Regra: As cinco respostas da instalação aparecem com o valor que está valendo agora

    Cenário: [CT-02] o nome vigente é o do banco, não o do .env, e sobrevive a acento
      Dado que o banco de settings guarda o nome "Organização Ção & Cia"
      E a configuração do processo foi alinhada com o banco
      Quando o operador executa "php artisan kit:info"
      Então a saída contém "Organização Ção & Cia"
      E a saída não contém o nome que o arquivo de configuração dizia antes

    Esquema do Cenário: [CT-03] a cor primária é rotulada pela partição que venceu
      Dado a cor primária em hexadecimal "<hex>" e o nome de paleta "<nome>" na configuração vigente
      Quando o operador executa "php artisan kit:info"
      Então a saída contém "<esperado>"
      E a saída não contém "<nao_esperado>"

      Exemplos:
        | hex      | nome  | esperado            | nao_esperado | # partição                          |
        | ""       | ""    | padrão do Filament  | Blue         | nenhuma escolha                     |
        | ""       | Blue  | Blue                | padrão do    | nome venceu                         |
        | "#ff0000"| Blue  | #ff0000             | Blue (paleta | hex venceu o nome                   |
        | "#zz"    | Blue  | Blue                | #zz          | hex inválido cai para o nome        |

    Esquema do Cenário: [CT-04] o administrador da instalação aparece mascarado, um por linha
      Dado <quantos> administradores da instalação
      Quando o operador executa "php artisan kit:info"
      Então a saída contém "<visivel>"
      E a saída não contém "admin@example.com"
      E a saída não contém "segundo@example.com"

      Exemplos:
        | quantos | visivel                          | # cardinalidade                      |
        | 0       | nenhum                           | coleção vazia — aponta o seeder      |
        | 1       | adm**                            | mascarado como no kit:admin          |
        | 2       | seg**                            | os dois aparecem; nenhum escolhido   |

    Cenário: [CT-05] multi-organização desligada é dita como desligada e aponta o comando que liga
      Dado a multi-organização desligada
      Quando o operador executa "php artisan kit:info"
      Então a saída contém "desligada"
      E a saída contém "kit:tenancy"
      E a saída não contém "cadastrada"
```

**Discriminância de CT-02**: o valor `Organização Ção & Cia` tem acento, cedilha e `&` — mata
`Str::ascii`/`e()` indevidos e o `twoColumnDetail` calculando largura por `strlen`. O "nome que o
arquivo dizia antes" é lido no `Dado` via `config('app.name')` **antes** de `gravarConfiguracao`, e
o cenário exige que os dois sejam diferentes (senão o `não contém` é vácuo).

**Discriminância de CT-03**: a linha `#zz` × `Blue` separa "leu `CorPrimaria::paleta()`" de "copiou a
precedência e esqueceu a validação do hex" (ADR-03). O `nao_esperado` de cada linha é o rótulo da
partição vizinha.

**Discriminância de CT-04**: `adm**` e `seg**` são os três primeiros caracteres seguidos de máscara
(`Str::mask($email, '*', 3)`). O e-mail completo **não pode** aparecer em nenhuma linha.

**A senha (quinta resposta)**: não tem valor a exibir — o cenário que a cobre é CT-08 (partição
"segredo"), com `config(['kit.admin.password' => 'SenhaUnicaXYZ'])` no `Dado` e `não contém
"SenhaUnicaXYZ"` no `Então`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | lê `env('APP_NAME')` em vez de `config('app.name')` — ignora o banco | CT-02 |
| M5 | copia a precedência da cor e esquece a validação do hex (mostra `#zz`) | CT-03 (linha 4) |
| M6 | decide a cor por `filled(config('kit.cor_primaria_hex'))` sem consultar `paleta()` | CT-03 (linha 4) |
| M7 | rótulo trocado: hex rotulado como paleta e vice-versa | CT-03 (linhas 2 e 3, `nao_esperado`) |
| M8 | `AdministradorDaInstalacao::todos()->first()` — mostra só um | CT-04 (linha `2`) |
| M9 | e-mail sem `Str::mask` | CT-04 (linhas `1` e `2`) |
| M10 | coleção vazia → `->first()->email` estoura | CT-04 (linha `0`) |
| M11 | multi-organização lida de `env('KIT_TENANCY')` ou de `permission.teams` | CT-05 + CT-15 (a suíte `Kit` roda com `permission.teams` desligado e a `Tenancy` ligado — se ler a chave errada, um dos dois falha) |

---

## Regra R3 — Toda propriedade do settings aparece, na ordem do mapa, legível

> `RQ-03` · perfil **padrão** · técnica: **partição exaustiva** de `mapaDeConfiguracao()`; **EP** no
> tipo do valor (`bool`, vazio, texto, número)

```gherkin
# language: pt

  Regra: Toda configuração do kit aparece, na ordem do mapa, com o valor vigente legível

    Cenário: [CT-06] cada propriedade do settings tem uma linha, e a ordem é a do mapa
      Dado o mapa de configuração do kit com 44 propriedades
      Quando o operador executa "php artisan kit:info"
      Então cada propriedade aparece na saída, pelo rótulo derivado do nome dela
      E a primeira propriedade do mapa aparece antes da última

    Esquema do Cenário: [CT-07] o valor vigente é exibido no formato do tipo dele
      Dado a propriedade "<propriedade>" gravada no banco com o valor <gravado>
      E a configuração do processo foi alinhada com o banco
      Quando o operador executa "php artisan kit:info"
      Então a linha dessa propriedade traz "<exibido>"

      Exemplos:
        | propriedade              | gravado   | exibido | # partição de tipo                 |
        | paginacao_padrao         | 37        | 37      | inteiro — não redondo              |
        | tabela_listrada          | false     | não     | booleano falso (não `''` nem `0`)  |
        | hub_de_navegacao         | true      | sim     | booleano verdadeiro (não `1`)      |
        | login_rodape             | null      | —       | vazio                              |
        | rotulo_da_organizacao    | Unidade   | Unidade | texto                              |
```

**Discriminância de CT-06**: o esperado é gerado por `array_keys(ConfiguracoesDoKit::mapaDeConfiguracao())`
passando pela **mesma** função de rótulo — o cenário não é sobre o rótulo, é sobre **nenhuma
propriedade faltar**. A ordem é verificada por `strpos` do primeiro rótulo `<` `strpos` do último.
Mata a implementação que itera `encrypted()`, uma lista escrita à mão, ou os grupos por prefixo
(ADR-02, alternativa 2), porque `hub_de_navegacao` e `rotulo_da_organizacao` não têm prefixo.

**Discriminância de CT-07**: `37` em vez de `10` (o default de fábrica) — o cenário falha se a linha
mostrar o valor do arquivo. `false` → `não` mata `(string) false` (vazio). `null` → `—` mata o
comando que imprime `""` e deixa a linha em branco.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | itera uma lista própria e esquece propriedade (ex.: as 6 do anti-robô) | CT-06 |
| M13 | itera `encrypted()` em vez de `mapaDeConfiguracao()` | CT-06 |
| M14 | lê a propriedade do objeto settings (`$settings->x`) em vez de `config($chave)` — igual aqui, mas divergiria se o banco lançasse; **não discriminável por este cenário** — ver R7/M23 | CT-14 |
| M15 | `(string) $bool` → `""`/`"1"` | CT-07 (linhas 2 e 3) |
| M16 | `null` impresso como string vazia (linha sem valor) | CT-07 (linha 4) |
| M17 | ordena alfabeticamente (`ksort`) | CT-06 (ordem) |

---

## Regra R4 — Segredo nunca sai em claro

> `RQ-03` (restrição) · perfil **padrão** · técnica: **partição exaustiva** de `encrypted()` (as seis) +
> a senha do administrador; **rastreio de efeito** no log

```gherkin
# language: pt

  Regra: Nenhum segredo aparece em claro — na listagem, na divergência ou no log

    Esquema do Cenário: [CT-08] cada segredo aparece só como presença, nunca como valor
      Dado a propriedade secreta "<propriedade>" gravada no banco com o valor "Segredo-<propriedade>-9f3"
      E a configuração do processo foi alinhada com o banco
      E a senha do administrador no .env é "SenhaUnicaXYZ"
      Quando o operador executa "php artisan kit:info"
      Então a saída contém "definida"
      E a saída não contém "Segredo-<propriedade>-9f3"
      E a saída não contém "SenhaUnicaXYZ"

      Exemplos:
        | propriedade                          |
        | mail_password                        |
        | login_google_client_secret           |
        | login_github_client_secret           |
        | login_linkedin_openid_client_secret  |
        | login_x_client_secret                |
        | login_anti_robo_chave_secreta        |
```

O `Dado` itera **`ConfiguracoesDoKit::encrypted()`** como dataset, e o cenário também afirma que a
lista tem **exatamente seis** entradas — se um segredo novo entrar na classe sem entrar aqui, o
dataset e o `04` divergem e alguém olha. Cada linha grava um valor **diferente** (`9f3` e o nome da
propriedade) para que `não contém` não passe por acidente de outro segredo.

O segredo **divergente** (a seção de divergência não pode vazá-lo) é CT-13, linha 3. O **log** sem
segredo é CT-16.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | lista de segredos escrita à mão no comando, faltando um | CT-08 (a linha do que faltou) |
| M19 | `filled($valor) ? $valor : 'vazia'` — mostra o valor quando definido | CT-08 (todas) |
| M20 | imprime `config('kit.admin.password')` na linha da senha | CT-08 (`SenhaUnicaXYZ`) |
| M21 | segredo divergente impresso com `.env: X → banco: Y` | CT-13 (linha 3) |
| M22 | contexto do log leva os **valores** divergentes, não as chaves | CT-16 |

---

## Regra R5 — O comando não altera nada, e rodar duas vezes é igual a rodar uma

> `RQ-04` · perfil **padrão** · técnica: **rastreio de efeito** — três direções: não gravou; não
> mudou a config do processo; segunda execução idêntica

```gherkin
# language: pt

  Regra: Exibir é somente leitura

    Cenário: [CT-10] o banco de settings sai do comando exatamente como entrou
      Dado o banco de settings com o nome "Do Banco" e a paginação 37
      E uma foto de todas as linhas da tabela de settings
      Quando o operador executa "php artisan kit:info"
      Então todas as linhas da tabela de settings são idênticas à foto
      E o arquivo .env do projeto tem o mesmo conteúdo de antes

    @premissa(A2)
    Cenário: [CT-11] a config do processo continua dizendo o que o banco diz depois do comando
      Dado o banco de settings com o nome "Do Banco"
      E a configuração do processo foi alinhada com o banco
      E o arquivo de configuração diz um nome diferente de "Do Banco"
      Quando o operador executa "php artisan kit:info"
      Então config('app.name') continua "Do Banco"

    Cenário: [CT-12] duas execuções produzem a mesma saída
      Dado uma instalação migrada e semeada
      Quando o operador executa "php artisan kit:info" pela segunda vez
      Então a saída da segunda execução é idêntica à da primeira
```

**Por que CT-11 existe**: o passo 2 do PRD separa `valoresDosArquivos()` (lê) de
`devolverConfigAoEnv()` (lê **e escreve** em `config()`). A implementação preguiçosa chama a segunda
para obter os valores do arquivo e, sem querer, **devolve a config do processo ao `.env`** — a tela
seguinte no mesmo processo (num teste, no `--agent`, num `tinker`) passa a ler o nome errado. O
`Dado` exige que arquivo e banco **difiram**, senão o cenário é vácuo. O "nome que o arquivo diz" é
`ConfiguracoesDoKit::valoresDosArquivos()['app.name']`, usado **só para calcular a precondição**
(declarado na Fronteira com o Plano).

**CT-10, o `.env`**: comparar `file_get_contents(base_path('.env'))` antes e depois, **se o arquivo
existir** (em CI pode não existir; o cenário pula essa asserção com `skip` explícito, nunca em silêncio).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | usa `devolverConfigAoEnv()` para obter os valores do arquivo | CT-11 |
| M24 | chama `$settings->save()` por engano (ex.: reaproveitando `propagarParaOSettings`) | CT-10 |
| M25 | grava cache/`config:cache` ou toca o `.env` "para alinhar" | CT-10 |
| M26 | acumula estado estático entre execuções (ex.: `$divergencias` como propriedade não zerada) | CT-12 |

---

## Regra R6 — Fonte declarada; divergência só quando existe, comparada por texto normalizado

> premissa **A2** · perfil **mínimo, EP escalada para tipos** · técnica: **EP** — {tabela presente,
> ausente} × {divergente, igual} × {mesmo tipo, `null`×`''`, segredo}

```gherkin
# language: pt

  Regra: O comando diz qual fonte está valendo, e só fala em divergência quando ela existe

    @premissa(A2)
    Cenário: [CT-09] sem divergência, o comando não fala em .env além do cabeçalho
      Dado o banco de settings recém-semeado a partir dos arquivos de configuração
      Quando o operador executa "php artisan kit:info"
      Então a saída contém "banco"
      E a saída não contém "diz diferente"

    @premissa(A2)
    Esquema do Cenário: [CT-13] a divergência aparece quando é real, e não aparece quando é só de tipo
      Dado a propriedade "<propriedade>" gravada no banco com <banco>
      E o arquivo de configuração diz <arquivo> para a mesma chave
      E a configuração do processo foi alinhada com o banco
      Quando o operador executa "php artisan kit:info"
      Então a saída <resultado>

      Exemplos:
        | propriedade         | banco        | arquivo (fixado pelo phpunit.xml)      | resultado                                                        | # partição                       |
        | nome_da_aplicacao   | "Do Banco"   | o APP_NAME do arquivo (≠ "Do Banco")   | contém "app.name", "Do Banco" e o valor do arquivo               | divergência real                 |
        | login_rodape        | null         | "" (KIT_LOGIN_RODAPE="" forçado, :102) | não contém "kit.login.rodape"                                    | `null` × `''` — mesmo texto      |
        | mail_password       | "Seg-9f3"    | null (MAIL_PASSWORD não definido)      | contém "mail.mailers.smtp.password" e "não exibidos"; não contém "Seg-9f3" | segredo divergente     |
        | tabela_listrada     | false        | true (KIT_TABELA_LISTRADA=true, :98)   | contém "kit.tabelas.listrada"                                    | booleano divergente de verdade   |
```

**CT-09 já é discriminante para a normalização, sem arranjo**: a migration de settings semeia os
campos de texto com `textoOuNulo()` (`database/settings/2026_08_24_000000_create_kit_settings.php:84`),
então **toda instalação recém-semeada** tem `null` no banco onde o arquivo diz `''` (`login_rodape`,
e os demais opcionais vazios). Uma comparação `!==` crua acusa divergência **logo depois do
`migrate`** — CT-09 (`não contém "diz diferente"`) mata M27 sozinho. CT-13 linha 2 é a versão
explícita do mesmo par.

**Linha 2 é o coração da regra**: `KIT_LOGIN_RODAPE=""` está **forçado** em `phpunit.xml:102`, então o
arquivo devolve `''` deterministicamente e o banco recebe `null` — os dois normalizam para texto
vazio. Uma implementação com `!==` cru acusa divergência aqui e em toda instalação real que tenha
um campo opcional vazio. O `Dado` afirma o **valor efetivo lido** do arquivo
(`valoresDosArquivos()['kit.login.rodape'] === ''`) como precondição, para o cenário não medir o
ambiente por acidente.

**Linha 4 é o contrapeso**: booleano que diverge de verdade **tem** de aparecer — mata a normalização
que colapsa tudo em vazio (`(string) false === ''`).

**Partição `int` × `string` (`mail_port`)**: seria a linha ideal (`MAIL_PORT` chega string do `.env`),
mas o arquivo devolve `int 2525` quando a chave **não existe** no `.env` da máquina e `"2525"` quando
existe — o resultado dependeria do ambiente. **Lacuna declarada**: coberta indiretamente pela linha 2
(mesma normalização por texto) e registrada no `## Checklist` como "ausente ≠ null ≠ vazio".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | compara com `!==` sem normalizar | CT-09 (o seed já produz `null` × `''`) e CT-13 (linha 2) |
| M28 | normaliza tudo com `(string)` — `false` vira `''` e casa com `null` | CT-13 (linha 4) |
| M29 | imprime a seção mesmo vazia (título sem linhas) | CT-09 |
| M30 | compara `config()` com `env()` direto, sem a coerção dos arquivos | CT-13 (linha 4: `env('KIT_TABELA_LISTRADA')` é a string `"true"`, e o banco tem `bool`) |
| M31 | cabeçalho diz `.env` com a tabela presente | CT-09 |

---

## Regra R7 — Banco indisponível degrada a linha, não o comando

> ADR-06 (deriva de RQ-01) · perfil **padrão** · técnica: **EP** — banco presente × ausente

```gherkin
# language: pt

  Regra: Sem banco o comando ainda responde o que não depende de banco

    Cenário: [CT-14] sem nenhuma tabela, o comando termina com sucesso e diz o que não conseguiu
      Dado que todas as tabelas do banco foram removidas
      Quando o operador executa "php artisan kit:info"
      Então o código de saída é 0
      E a saída contém o nome da aplicação vigente
      E a saída contém "indisponível"
      E a saída contém ".env"
      E a saída não contém "diz diferente"
```

**Por que `dropAllTables` e não desligar a conexão**: `Schema::hasTable()` lança em banco inexistente
(`KitServiceProvider.php:150-160`), e derrubar as tabelas faz **todas** as consultas lançarem —
settings, `users`, `tenants` — no mesmo cenário. É a partição "ausente" completa, com o arnês real.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | `AdministradorDaInstalacao::todos()` sem guarda → `QueryException` derruba o comando | CT-14 (código 0) |
| M33 | guarda só no settings, não nos administradores | CT-14 (`indisponível`) |
| M34 | seção de divergência tentada sem tabela → lança ou imprime vazio | CT-14 (`diz diferente` ausente + código 0) |
| M35 | `catch` devolve `FAILURE` | CT-14 (código 0) |

---

## Regra R2 (continuação, suíte `Tenancy`) — multi-organização ligada

> `RQ-02` · **arquivo separado** porque a suíte `Kit` não tem `permission.teams` (`.ai/rules/testes.md`)

```gherkin
# language: pt

  Regra: Com a multi-organização ligada, o comando diz o rótulo escolhido e quantas existem

    Cenário: [CT-15] multi-organização ligada mostra o rótulo plural e a contagem
      Dado a multi-organização ligada com o rótulo plural "Organizações"
      E três organizações cadastradas
      Quando o operador executa "php artisan kit:info"
      Então a saída contém "ligada"
      E a saída contém "Organizações"
      E a saída contém "3 cadastrada"
      E a saída não contém "desligada"
```

**Discriminância**: três organizações, não uma nem duas — `0.24.0` (a versão do kit) contém `2` e
`0`; `3` não aparece em mais nada da saída padrão. A contagem é `Tenant::count()`, então uma
organização **inativa** conta — e isso fica registrado: o cenário cria as três ativas, e "inativa
conta ou não?" não é decidido pelo requisito (não vira cenário; se alguém precisar, é pergunta).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | rótulo lido de `kit.tenancy.label` (singular) | CT-15 (`Organizações`) |
| M37 | contagem omitida ou `Tenant::where('ativo', true)` sem dizer | CT-15 (`3 cadastrada`) — o segundo só se houver inativa; ver nota |
| M38 | `ligada`/`desligada` invertido | CT-05 + CT-15 |

---

## Regra R4/R6 (continuação) — o log

> `RQ-03` (restrição de segredo) · técnica: **rastreio de efeito** — aconteceu, uma vez, sem valor

```gherkin
# language: pt

  Regra: O comando registra que foi executado, uma vez, sem carregar valor nenhum

    Cenário: [CT-16] uma linha de log no canal de configurações, com as chaves e sem os valores
      Dado a propriedade secreta "mail_password" gravada no banco com "Seg-9f3"
      E a configuração do processo foi alinhada com o banco
      E o canal de log "configuracoes" espiado
      Quando o operador executa "php artisan kit:info"
      Então o canal recebeu exatamente uma chamada
      E a mensagem contém "[KitInfo@handle]"
      E nem a mensagem nem o contexto contêm "Seg-9f3"
```

**Nota**: o nível (`debug`) e o canal são escolha do PRD, não do requisito — o `Então` afirma o
formato `[Classe@Método]` (convenção do projeto) e a **ausência do segredo** (RQ-03). Se o PRD mudar
o nível, o cenário não muda.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | contexto com `['divergencias' => $divergencias]` (valores) em vez de `array_keys` | CT-16 |
| M39 | log em `Log::info()` sem canal, ou no canal `autenticacao` | CT-16 (o espião do canal `configuracoes` não recebe) |
| M40 | um log por seção (ruído) | CT-16 (exatamente uma) |

---

## Regra R8 — Documentado nos dois idiomas

> premissa **A6** · perfil **mínimo** · técnica: **partição exaustiva** de idiomas

```gherkin
# language: pt

  Regra: O comando está documentado onde os outros comandos do kit estão

    @premissa(A6)
    Esquema do Cenário: [CT-17] a documentação de cada idioma cita o comando
      Dado a documentação do kit no idioma "<idioma>"
      Quando o texto é lido
      Então ele contém "php artisan kit:info"

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

Usa `documentacaoDoKit($idioma)` (`tests/Pest.php:856`), que concatena README + `docs/{idioma}` —
o cenário não fixa **em que página** está, só que está (mesma decisão de
`ConfiguracoesDoKitDocumentacaoTest.php:27`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M41 | documentado só em português | CT-17 (`en`) |
| M42 | documentado no `CHANGELOG.md` e em nenhuma página (o changelog não entra em `documentacaoDoKit`) | CT-17 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: comando de console sem `{id}` |
| Autorização exercida na ação | não se aplica: sem policy — quem tem terminal tem o `.env`; o que existe é **mascaramento**, CT-04 e CT-08 |
| Idempotência (ancorada no agregado) | CT-12 (saída) + CT-10 (agregado: tabela de settings) |
| Concorrência | não se aplica: sem escrita |
| Fronteira no ponto de entrada (gravação) | não se aplica: sem entrada — o comando não tem argumento nem opção |
| Domínio condicionado (tipo × valor) | CT-03 (hex × nome: a validade do hex decide qual campo vale) |
| Estado × operação de escrita | não se aplica: sem estado próprio |
| Ausente ≠ null ≠ vazio | CT-13 (linha 2: `null` × `''`); CT-07 (linha 4: `null` → `—`). **Lacuna declarada**: `int` × `string` (`mail_port`) depende do `.env` da máquina — coberto pela mesma normalização por texto, não por cenário próprio |
| Paginação / ordenação | CT-06 (ordem do mapa) |
| Timezone / DST | não se aplica: sem data |
| Unicode / limite de varchar | CT-02 (acento, cedilha, `&`) |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: sem payload |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Vazamento de segredo em saída** (item do projeto — `KitAdmin`, ADR-04) | CT-08, CT-13 (linha 3), CT-16 |
| **Banco ausente no boot** (item do projeto — `KitServiceProvider:150-160`) | CT-14 |

## Índice de Cenários

Todos implementados e **verdes**: 37 testes, 140 asserções (as contagens diferem dos 19 IDs porque
os `Esquema do Cenário` viram datasets — CT-03 vale 4, CT-04 vale 3, CT-07 vale 5, CT-08 vale 6 e
CT-13 vale 3).

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | registrado no namespace `kit`, exit 0 | R1 | EP | Feature (console) | `tests/Kit/KitInfoTest.php` | M1, M2, M3 |
| CT-02 | nome vigente é o do banco, com acento | R2 | partição "banco venceu" | Feature | `tests/Kit/KitInfoTest.php` | M4 |
| CT-03 | cor rotulada pela partição que venceu (4 linhas) | R2 | EP no discriminador | Feature | `tests/Kit/KitInfoTest.php` | M5, M6, M7 |
| CT-04 | administrador mascarado, 0/1/2 | R2 | EP cardinalidade | Feature | `tests/Kit/KitInfoTest.php` | M8, M9, M10 |
| CT-05 | multi-organização desligada | R2 | EP | Feature | `tests/Kit/KitInfoTest.php` | M11, M38 |
| CT-06 | toda propriedade aparece, na ordem | R3 | partição exaustiva | Feature | `tests/Kit/KitInfoTest.php` | M12, M13, M17 |
| CT-07 | valor no formato do tipo (5 linhas) | R3 | EP de tipo | Feature | `tests/Kit/KitInfoTest.php` | M15, M16 |
| CT-08 | cada segredo só como presença (6 linhas) | R4 | partição exaustiva | Feature | `tests/Kit/KitInfoTest.php` | M18, M19, M20 |
| **CT-08b** | a lista de segredos tem as seis entradas | R4 | contagem da lista | Feature | `tests/Kit/KitInfoTest.php` | M18 (a variante "esqueceu de atualizar o dataset") |
| CT-09 | sem divergência, sem seção | R6 | EP | Feature | `tests/Kit/KitInfoTest.php` | M27, M29, M31 |
| CT-10 | tabela de settings intacta | R5 | rastreio (não aconteceu) | Feature | `tests/Kit/KitInfoTest.php` | M24, M25 |
| CT-11 | config do processo intacta | R5 | rastreio (não aconteceu) | Feature | `tests/Kit/KitInfoTest.php` | M23 |
| CT-12 | duas execuções idênticas | R5 | rastreio (uma vez) | Feature | `tests/Kit/KitInfoTest.php` | M26 |
| CT-13 | divergência real × só de tipo (3 linhas) | R6 | EP de tipos | Feature | `tests/Kit/KitInfoTest.php` | M27, M28, M30 |
| **CT-13b** | segredo divergente sem os dois lados | R4/R6 | rastreio de segredo | Feature | `tests/Kit/KitInfoTest.php` | M21 |
| CT-14 | sem as tabelas que consulta: exit 0 e `indisponível` | R7 | EP | Feature | `tests/Kit/KitInfoTest.php` | M14, M32, M33, M34, M35 |
| CT-15 | multi-organização ligada: rótulo e contagem | R2 | EP | Feature | `tests/Tenancy/KitInfoTenancyTest.php` | M11, M36, M38 |
| CT-16 | um log, com chaves, sem valores | R4 | rastreio de efeito | Feature | `tests/Kit/KitInfoTest.php` | M22, M39, M40 |
| CT-17 | documentado em pt e en | R8 | partição exaustiva | Feature (arquivo) | `tests/Kit/KitInfoTest.php` | M41, M42 |

**Helpers**: `saidaDoKitInfo()` e `linhaDoKitInfo()` vivem em `tests/Pest.php` porque os dois
arquivos os usam (`.ai/rules/testes.md`, guardado por `tests/Kit/HelpersDeTesteTest.php`).

**Sem matador declarado**, depois da implementação:

- **M37** na variante "conta só ativas" — o cenário cria três **ativas**, então `count()` e
  `where('ativo', true)->count()` coincidem. Não é lacuna do conjunto: é **decisão que o requisito
  não tomou**, registrada como nota em CT-15 e não como cenário com valor chutado.
- **M32/M33 na variante "conexão morta"** — CT-14 prova o `noBanco()` protegendo a consulta de
  administradores, com as tabelas derrubadas. A variante em que `gravadoNoBanco()` **lança** (em vez
  de responder `false`) não é expressável sob `RefreshDatabase`: purgar a conexão quebra o rollback
  do tearDown. Tentado: `Schema::dropAllTables()` (morre no `vacuum` dentro da transação). O helper
  é o mesmo nas três chamadas, então o que CT-14 prova vale para elas.
- **Partição `int` × `string` da normalização** (`mail_port`) — o valor do arquivo depende do `.env`
  da máquina, então o caso mediria o ambiente. Coberta indiretamente por CT-09 e por CT-13
  (`null` × `''`), que exercitam a mesma normalização por texto.

**Helpers**: nenhum helper novo cruzado entre arquivos. Se `tests/Tenancy/KitInfoTenancyTest.php`
precisar de algo além de `tenant()`, vai para `tests/Pest.php` (`.ai/rules/testes.md`).

## Sem CT-B

- **Motivo**: a feature é um comando de console; `## Superfície de UI` do PRD declara *"Sem
  superfície de UI"*. Nenhum cenário afirma sobre JavaScript, acessibilidade, cor ou layout.
- Não existe `05-casos-de-teste-browser.md`.

## Revisão Adversarial

Nenhuma área no perfil **completo** (maior P×I = 6). Não obrigatória. Se o usuário elevar RQ-03 a
`completo` (segredo em saída colável é argumento razoável), delegar ao sub-agente com o contrato da
skill, **sem** o PRD.

## Pós-implementação

- [ ] `vendor/bin/pest tests/Kit/KitInfoTest.php --mutate --path=app/Console/Commands/KitInfo.php`
      (`pestphp/pest-plugin-mutate ^5.0` está declarado em `composer.json:94`; exige PCOV/Xdebug)
- [ ] Mutante sobrevivente → lacuna de derivação → cenário novo neste arquivo
- [ ] Índice atualizado com o nome real de cada `it()`
