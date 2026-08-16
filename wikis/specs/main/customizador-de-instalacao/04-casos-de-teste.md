# Casos de Teste — Customizador de instalação

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação —
> ela não existe ainda.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — aplicação das respostas (`.env`, configs, config em memória) | 3 | 3 | **9** | completo |
| A2 — guardas de pulo e o gate "só no `create-project`" | 2 | 3 | **6** | padrão |
| A3 — coleta das respostas (defaults, validação, domínio das opções) | 2 | 2 | **4** | padrão |
| A4 — pós-instalação (resumo, testes, estrela) | 1 | 1 | **1** | mínimo |
| A5 — cor primária nos painéis e a precedência da cor da organização | 3 | 2 | **6** | padrão |

**Por que A1 é impacto 3**: escreve no `.env` de um projeto novo e decide banco e tenancy. Errar
ali não é cosmético — instala o projeto no banco errado, cria o admin com credencial errada, ou
deixa as tabelas de permissão sem `team_id` (falha silenciosa até o primeiro login).

- Técnicas aplicadas: EP, BVA (contagem e comprimento), tabela de decisão, matriz de estado do
  arquivo (`chave presente` × `chave comentada` × `chave ausente`), rastreio de efeito.
- Cenários: **29** · Regras: **9** · Mutantes previstos: **40** · Sem matador: **2** (declarados)

## Restrições do Arnês (verificadas antes de derivar)

Três fatos do projeto mudam o que é escrevível, e todos foram confirmados no código:

1. **`Illuminate\Console\Concerns\ConfiguresPrompts:33-37`** — `Prompt::interactive()` é ligado
   quando `runningUnitTests()`, e `Prompt::fallbackWhen(windows_os() || runningUnitTests())` faz
   os prompts caírem no `QuestionHelper` do Symfony em teste. Consequência: `expectsQuestion()` e
   `expectsConfirmation()` do `$this->artisan()` **funcionam** sobre os prompts do
   `laravel/prompts`.
2. **A guarda de "sem terminal" precisa dos três termos, e nenhum serve sozinho.**
   `stream_isatty(STDIN)` sozinho faz o customizador se pular dentro da própria suíte (sob
   `$this->artisan()` o STDIN não é tty). `isInteractive()` sozinho deixa passar instalação **sem
   terminal nenhum** no Windows, onde o Symfony não tem `posix_isatty` para consultar — foi assim
   que a v0.16.0 "respondeu" as cinco perguntas com os defaults num `create-project` sem TTY.
   A expressão correta é a que o próprio Laravel usa em `ConfiguresPrompts:33`:
   `($input->isInteractive() && defined('STDIN') && stream_isatty(STDIN)) || runningUnitTests()`.
   *(Corrigido depois da verificação manual; a primeira versão desta seção proibia `stream_isatty`
   categoricamente e estava errada.)*
3. **O alvo da escrita precisa ser injetável.** Os cenários de A1 escrevem em `.env`,
   `config/permission.php` e `config/filament-shield.php`; apontá-los para `base_path()` faria a
   suíte **reescrever o `.env` da máquina de quem roda os testes**. Testabilidade aqui é
   requisito de desenho, não conveniência: o customizador recebe o diretório-base.
   → **Devolvido ao PRD** (passo 4).

**Divergência declarada com a skill**: a skill sugere `pest --parallel --tia` como padrão. Este
projeto tem `pest()->tia()->locally()` no `tests/Pest.php` e a suíte do kit roda por
`composer test:kit` (`--group=kit`). Vale o do projeto.

**`pestphp/pest-plugin-mutate` não está declarado no `composer.json`** — existe em `vendor/` só
como dependência transitiva do Pest 5. O `pest --mutate` do fechamento do ciclo funciona hoje por
acidente da árvore de dependências e some num `composer update`.
→ **Devolvido ao PRD**: incluir `composer require pestphp/pest-plugin-mutate --dev` como passo,
ou declarar que o fechamento por mutação não será feito nesta feature.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S** | `CustomizadorDaInstalacao`, `AtivadorDeTenancy`, `SubstituicaoEmArquivo`, `KitInstall`, 3 `PanelProvider`, `config/kit.php`, `.env.example` | CT-19, CT-20 |
| **F** | perguntar, pular, aplicar no `.env`, alinhar config em memória, ligar tenancy antes do migrate, resumir, oferecer testes, oferecer estrela | CT-01…CT-28 |
| **D** | entrada **livre e não confiável** digitada por humano (nome do projeto, e-mail, senha, rótulos) escrita dentro de um arquivo de configuração; estado prévio do `.env` (chave presente, comentada, ausente, já customizada) | CT-08…CT-13, CT-21 |
| **I** | uma só: `php artisan kit:install`, chamado à mão ou pelo `post-create-project-cmd` do Composer | CT-01…CT-04 |
| **P** | **duas plataformas com comportamento diferente**: em Windows o `laravel/prompts` sempre cai no fallback do Symfony (`ConfiguresPrompts:37`); o `exec()` da estrela muda por `PHP_OS_FAMILY`; SQLite × Postgres × MySQL têm regras de nome de banco diferentes | CT-13, CT-27 |
| **O** | dois modos de uso reais: `create-project` (projeto nascendo) e `kit:install --force` (reinstalação) — o segundo é o que pode destruir customização já feita | CT-03, CT-04, CT-21 |
| **T** | **ordem dentro do mesmo processo**: a escrita no `.env` precisa preceder `prepararBancoSqlite`, `migrate` e `db:seed`, e a config em memória não muda sozinha. Não há concorrência: o comando é single-run | CT-14…CT-18 |

Dimensão sem cenário: nenhuma.

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — o customizador só pergunta quando há terminal interativo, nenhuma flag pede pulo e o projeto está nascendo | A2 (padrão) | RQ-01, RQ-03, RQ-08 | tabela de decisão | CT-01…CT-05 |
| **R2** — pular deixa o projeto idêntico à instalação padrão de hoje, e sem resumo | A2 (padrão) | RQ-08, RQ-10 | rastreio de efeito | CT-06, CT-07 |
| **R3** — cada resposta grava a sua chave no `.env`, qualquer que seja o estado prévio da linha | A1 (completo) | RQ-07 | matriz de estado do arquivo + EP | CT-08…CT-11 |
| **R4** — entrada de humano não corrompe nem injeta linha no `.env` | A1 (completo) | RQ-07 | BVA + taxonomia (fronteira de confiança) | CT-12, CT-13 |
| **R5** — o banco escolhido vale para a **mesma** execução: migrations e seeders vão para ele | A1 (completo) | RQ-05, RQ-07 | EP (3 partições) + rastreio de efeito | CT-14…CT-17 |
| **R6** — a observação sobre pgvector/IA local é exibida na escolha do banco | A3 (padrão) | RQ-06 | rastreio de efeito | CT-18 |
| **R7** — a multi-organização ligada na instalação nasce coerente, sem `migrate:fresh` | A1 (completo) | RQ-02, RQ-07 | rastreio de efeito + estado | CT-19, CT-20, CT-21 |
| **R8** — a cor escolhida veste os três painéis e não rouba a cor da organização | A5 (padrão) | RQ-02, RQ-07 | EP + precedência | CT-22…CT-24 |
| **R9** — o fecho oferece resumo, testes e estrela, cada um com o seu gatilho | A4 (mínimo) + A3 | RQ-09, RQ-10, RQ-13, RQ-14 | tabela de decisão | CT-25…CT-28 |

RQ-04, RQ-11, RQ-12 e RQ-14 são **não-funcionais** e não geram regra própria: RQ-12 é medida por
CT-06/CT-07 (Enter em tudo = instalação de hoje), RQ-04 e RQ-14 são verificáveis por leitura da
implementação contra as fontes citadas no `00`, e RQ-11 é decisão de escopo já registrada na
ADR-07. **Declarado, não esquecido.**

## Fronteira com o Plano

| Item que veio do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nomes `CustomizadorDaInstalacao`, `AtivadorDeTenancy`, `SubstituicaoEmArquivo` | escolha de implementação | detalhe do cenário |
| Channel de log usado | escolha de implementação (e, após a auditoria do plano, não há channel próprio) | detalhe — os cenários afirmam que **houve** registro e o que ele **não** pode conter, nunca em qual arquivo |
| `starter_kit` / `secret` como usuário e senha do Postgres | escolha de implementação (vieram do `docker-compose.yml`) | detalhe |
| Texto exato do rótulo de cada opção de banco | comportamento visível que o requisito **não** determina | o `Então` afirma a presença de `pgvector` e de "recomendad", não a frase inteira |
| Lista fechada de 16 cores | escolha de implementação | o `Então` afirma que a escolha vira `KIT_COR_PRIMARIA` e que nome fora do domínio não derruba painel |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **P1 — reinstalação com `--force` sobre um `.env` já customizado**: o requisito só fala
  do `create-project`. Sobrescrever o que o usuário já ajustou é destrutivo e não está autorizado
  por nenhuma `RQ`. **Premissa adotada**: reescreve **apenas** as chaves respondidas, preservando
  todo o resto do arquivo (CT-21, marcado `@premissa`). **Se negado**: CT-21 muda de oráculo e o
  passo 6 do PRD ganha uma confirmação explícita antes de sobrescrever.
- **P2 — RQ-09, "os testes do kit"**: `composer test:kit` (grupo `kit`) ou a suíte inteira
  (`composer test`, que inclui Pint e PHPStan)? **Premissa adotada**: o grupo `kit`, que é o que o
  README chama de "os testes do kit" (CT-26, `@premissa`).

## Setup Global

### Personas

Não há persona de usuário: a feature é um comando de console. O "ator" dos cenários é **quem
instala**, no terminal.

### Fixtures

- **Diretório-base temporário** por cenário, com um `.env` semeado a partir do `.env.example` real
  do repositório — é o único jeito de os cenários de A1 serem verdadeiros sobre o arquivo que o
  usuário terá, sem tocar o `.env` da máquina.
- Para R7 e R8, os helpers que já existem em `tests/Pest.php`: `tenant()`, `usuarioComPapel()`,
  `duasOrganizacoes()`, `fronteiraDeRequest()`, `noPainelBootado()`.

### Fakes

- `Log::spy()` para os cenários de rastreio de registro.
- Nenhum `Http::fake` / `Mail::fake`: a feature não faz rede nem envia e-mail.
- Os cenários **não** executam `npm`, `filament:assets` nem `db:seed` completo: quando o comando
  inteiro é exercitado, é com `--no-npm --no-seed`.

### Estratégia de DB

`RefreshDatabase` global (`tests/Pest.php`), banco `:memory:` (`phpunit.xml`). Os cenários de
tenancy (CT-19, CT-20) vão para `tests/Tenancy/`, porque `permission.teams` precisa estar decidido
antes das migrations — regra do `Tests\TenancyTestCase`.

---

## Regra R1 — o customizador só pergunta quando há terminal, nenhuma flag pede pulo e o projeto está nascendo

> `RQ-01`, `RQ-03`, `RQ-08` · perfil **padrão** · técnica: **tabela de decisão**

| # | terminal interativo | `--no-custom` | `.env` já existia | `--force` | Pergunta? | CT |
|---|---|---|---|---|---|---|
| 1 | sim | não | não | — | **sim** | CT-01 |
| 2 | **não** | não | não | — | não | CT-02 |
| 3 | sim | **sim** | não | — | não | CT-03 |
| 4 | sim | não | **sim** | não | não | CT-04 |
| 5 | sim | não | **sim** | **sim** | **sim** | CT-05 |

```gherkin

# language: pt

Funcionalidade: Customização durante a instalação

  Regra: o customizador só pergunta quando há terminal, nenhuma flag pede pulo e o projeto está nascendo

    Cenário: [CT-01] projeto nascendo em terminal interativo recebe as perguntas
      Dado um diretório de projeto sem arquivo ".env"
      E um terminal interativo
      Quando quem instala executa a instalação
      Então a primeira pergunta exibida é a de personalizar o projeto

    Cenário: [CT-02] sem terminal interativo a instalação não pergunta nada
      Dado um diretório de projeto sem arquivo ".env"
      E um terminal não interativo
      Quando quem instala executa a instalação
      Então nenhuma pergunta é exibida
      E o arquivo ".env" fica com o valor padrão de APP_NAME

    Cenário: [CT-03] a opção de pular impede as perguntas mesmo com terminal
      Dado um diretório de projeto sem arquivo ".env"
      E um terminal interativo
      Quando quem instala executa a instalação pedindo para pular a customização
      Então nenhuma pergunta é exibida
      E o arquivo ".env" fica com o valor padrão de APP_NAME

    Cenário: [CT-04] projeto que já tem ".env" não é perguntado de novo
      Dado um diretório de projeto com um ".env" cujo APP_NAME é "Projeto Em Uso"
      E um terminal interativo
      Quando quem instala executa a instalação
      Então nenhuma pergunta é exibida
      E o APP_NAME no ".env" continua "Projeto Em Uso"

    Cenário: [CT-05] a reinstalação do zero pergunta de novo
      Dado um diretório de projeto com um ".env" cujo APP_NAME é "Projeto Em Uso"
      E um terminal interativo
      Quando quem instala executa a reinstalação do zero
      Então a primeira pergunta exibida é a de personalizar o projeto
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | guarda usa `stream_isatty(STDIN)` em vez de `isInteractive()` | CT-01 (nenhuma pergunta apareceria em teste) |
| M2 | a flag `--no-custom` é lida mas não interrompe as perguntas | CT-03 |
| M3 | o gate de "`.env` já existia" é avaliado **depois** de o `.env` ser copiado — e portanto é sempre verdadeiro | CT-01 (nunca perguntaria) |
| M4 | o gate ignora `--force` e a reinstalação nunca volta a perguntar | CT-05 |
| M5 | condição com `\|\|` no lugar de `&&`: qualquer uma das guardas sozinha já pula | CT-01 |

---

## Regra R2 — pular deixa o projeto idêntico à instalação padrão, e sem resumo

> `RQ-08`, `RQ-10`, `RQ-12` · perfil **padrão** · técnica: **rastreio de efeito** (as três direções)

```gherkin
  Regra: pular a customização não altera nada e não imprime resumo

    Cenário: [CT-06] pular preserva todos os valores padrão do .env
      Dado um diretório de projeto sem arquivo ".env"
      Quando quem instala pula a customização
      Então o ".env" tem APP_NAME "Starter Kit"
      E o ".env" tem DB_CONNECTION "sqlite"
      E o ".env" tem KIT_ADMIN_EMAIL "admin@example.com"
      E o ".env" tem KIT_TENANCY "false"
      E o ".env" tem KIT_COR_PRIMARIA vazio

    Cenário: [CT-07] quem pulou não vê o resumo da customização
      Dado um diretório de projeto sem arquivo ".env"
      Quando quem instala pula a customização
      Então a saída não contém o resumo do que foi customizado
      E a saída contém as URLs dos três painéis
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | pular ainda escreve os defaults por cima do `.env` (reescrevendo linhas comentadas) | CT-06 (as chaves `DB_*` comentadas continuariam comentadas) |
| M7 | o resumo é impresso sempre, com os valores padrão | CT-07 |
| M8 | pular também suprime o banner de URLs e login | CT-07 (segunda asserção) |

---

## Regra R3 — cada resposta grava a sua chave, qualquer que seja o estado prévio da linha

> `RQ-07` · perfil **completo** · técnica: **matriz de estado do arquivo × EP**

O `.env` recém-copiado tem as chaves em **três estados diferentes**, e é aí que mora o defeito:
`APP_NAME` está presente e preenchida, `DB_HOST` está **comentada**, e `KIT_COR_PRIMARIA` pode
estar **ausente** num `.env` antigo. Uma substituição que só trata o primeiro caso perde as
outras duas em silêncio.

| Estado prévio da linha | Exemplo | Resultado exigido | CT |
|---|---|---|---|
| presente e preenchida | `APP_NAME="Starter Kit"` | substituída no lugar | CT-08 |
| presente e comentada | `# DB_HOST=127.0.0.1` | descomentada e preenchida | CT-09 |
| ausente do arquivo | sem `KIT_COR_PRIMARIA` | acrescentada **uma única vez** | CT-10 |
| presente, e a resposta é "manter o padrão" | `KIT_ADMIN_PASSWORD=password` | inalterada | CT-11 |

```gherkin
  Regra: cada resposta grava a sua chave, qualquer que seja o estado prévio da linha

    Cenário: [CT-08] resposta substitui a chave já preenchida sem tocar no resto do arquivo
      Dado um ".env" com APP_NAME "Starter Kit" e um comentário logo acima dessa linha
      Quando quem instala responde "Loja do Ferro" para o nome do projeto
      Então o ".env" tem APP_NAME "Loja do Ferro"
      E o comentário acima da linha continua no arquivo
      E o número de linhas do arquivo não mudou

    Cenário: [CT-09] resposta descomenta a chave que estava comentada
      Dado um ".env" com as linhas DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME e DB_PASSWORD comentadas
      Quando quem instala escolhe o banco PostgreSQL
      Então o ".env" tem DB_CONNECTION "pgsql"
      E o ".env" tem DB_HOST "127.0.0.1" em linha não comentada
      E o ".env" tem DB_PORT "5432" em linha não comentada

    Cenário: [CT-10] chave ausente é acrescentada uma única vez
      Dado um ".env" sem nenhuma linha KIT_COR_PRIMARIA
      Quando quem instala escolhe a cor "Blue"
      Então o ".env" tem exatamente uma linha KIT_COR_PRIMARIA
      E o valor dessa linha é "Blue"

    Cenário: [CT-11] resposta vazia na senha mantém a senha padrão
      Dado um ".env" com KIT_ADMIN_PASSWORD "password"
      Quando quem instala responde vazio para a senha do administrador
      Então o ".env" tem KIT_ADMIN_PASSWORD "password"
      E o usuário criado pelo seeder autentica com a senha "password"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | o padrão de busca não aceita a linha comentada (`/^CHAVE=/` em vez de `/^#?\s*CHAVE=/`) | CT-09 |
| M10 | o fallback de append roda **também** quando a chave existe, duplicando a linha | CT-10, CT-21 |
| M11 | a substituição reescreve o arquivo inteiro a partir de um template, perdendo comentários | CT-08 |
| M12 | resposta vazia da senha grava string vazia em vez de manter o default | CT-11 |
| M13 | `preg_replace` sem limite de 1 substitui ocorrências dentro de comentários | CT-08 (contagem de linhas) |

---

## Regra R4 — entrada de humano não corrompe nem injeta linha no `.env`

> `RQ-07` · perfil **completo** · técnica: **BVA + taxonomia (fronteira de confiança)**

O nome do projeto e os rótulos da organização são texto livre digitado por uma pessoa e escritos
**dentro de um arquivo de configuração**. É a única fronteira de confiança da feature — e é
exatamente onde o Ponytail proíbe simplificar.

```gherkin
  Regra: entrada de humano não corrompe nem injeta linha no .env

    Esquema do Cenário: [CT-12] nome hostil é gravado sem quebrar o arquivo
      Dado um ".env" recém-criado
      Quando quem instala responde <nome> para o nome do projeto
      Então o valor efetivo de APP_NAME é <nome>
      E o ".env" não ganhou nenhuma chave além das que já existiam

      Exemplos:
        | nome                          | # o que ataca                    |
        | Loja "do" Ferro               | aspas duplas dentro do valor      |
        | Ação & Cia                    | acento e "e" comercial            |
        | Loja\nAPP_DEBUG=false         | injeção de linha nova             |
        | Ferro's                       | apóstrofo                         |
        | Kit 🚀                        | caractere de 4 bytes              |

    Esquema do Cenário: [CT-13] o nome do banco derivado é sempre um identificador válido
      Dado um ".env" recém-criado
      Quando quem instala responde <nome> para o nome do projeto e escolhe o banco MySQL
      Então o DB_DATABASE gravado é "<esperado>"

      Exemplos:
        | nome            | esperado        | # regra              |
        | Loja do Ferro   | loja_do_ferro   | espaço vira "_"      |
        | Ação & Cia      | acao_cia        | acento e símbolo caem |
        | Kit-2026        | kit_2026        | hífen vira "_"        |
        | 2026 Kit        | _2026_kit       | não começa com dígito |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | valor gravado sem aspas — nome com espaço quebra o parse do `.env` | CT-12 (primeira linha) |
| M15 | aspas do valor não escapadas — `APP_NAME="Loja "do" Ferro"` trunca o valor | CT-12 (aspas duplas) |
| M16 | quebra de linha não neutralizada — o nome injeta uma chave nova no `.env` | CT-12 (linha de injeção) |
| M17 | nome do banco por `Str::slug()` puro: mantém hífen, inválido sem aspas em Postgres | CT-13 (hífen) |
| M18 | nome do banco começando com dígito, recusado pelo MySQL | CT-13 (última linha) |

---

## Regra R5 — o banco escolhido vale para a mesma execução

> `RQ-05`, `RQ-07` · perfil **completo** · técnica: **EP (3 partições) + rastreio de efeito**

A armadilha desta regra não é a escrita no arquivo: é a **config já carregada**. Escrever
`DB_CONNECTION=pgsql` no `.env` não muda `config('database.default')` do processo que está
rodando — e o `migrate` do mesmo comando iria para o SQLite, **sem erro nenhum**.

```gherkin
  Regra: o banco escolhido vale para a execução corrente, não só para o arquivo

    Esquema do Cenário: [CT-14] a escolha do banco grava o bloco correspondente
      Dado um ".env" recém-criado
      Quando quem instala escolhe o banco <opcao>
      Então o ".env" tem DB_CONNECTION "<driver>"
      E o ".env" tem DB_PORT "<porta>"

      Exemplos:
        | opcao      | driver | porta | # partição          |
        | SQLite     | sqlite | —     | sem serviço externo |
        | PostgreSQL | pgsql  | 5432  | recomendado         |
        | MySQL      | mysql  | 3306  | RQ-05               |

    Cenário: [CT-15] a conexão da execução corrente passa a ser a escolhida
      Dado um ".env" recém-criado
      Quando quem instala escolhe o banco PostgreSQL
      Então a conexão padrão em uso na execução é "pgsql"

    Cenário: [CT-16] escolher um banco externo não cria o arquivo SQLite
      Dado um ".env" recém-criado
      Quando quem instala escolhe o banco MySQL
      Então nenhum arquivo "database/database.sqlite" é criado

    Cenário: [CT-17] banco externo inacessível não migra e diz o que fazer
      Dado um ".env" recém-criado
      E um servidor PostgreSQL inacessível
      Quando quem instala escolhe o banco PostgreSQL
      Então a instalação termina com sucesso
      E a saída avisa que as migrations foram puladas
      E a saída contém o comando para subir o serviço
      E nenhuma tabela do kit foi criada
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M19 | grava no `.env` e não alinha a config em memória | CT-15, CT-16 |
| M20 | alinha `database.default` mas não descarta a conexão já resolvida | CT-15 |
| M21 | `prepararBancoSqlite()` roda antes da customização e cria o arquivo mesmo com Postgres escolhido | CT-16 |
| M22 | falha de conexão apenas registrada, com `migrate` e `db:seed` seguindo e falhando em cascata | CT-17 (a instalação terminaria em erro) |
| M23 | porta do MySQL escrita como 5432 (copiada do Postgres) | CT-14 (última linha) |

---

## Regra R6 — a observação sobre IA local é exibida na escolha do banco

> `RQ-06` · perfil **padrão** · técnica: **rastreio de efeito**

```gherkin
  Regra: a escolha do banco exibe a observação sobre IA local

    Cenário: [CT-18] a observação sobre pgvector aparece junto da escolha do banco
      Dado um terminal interativo
      Quando quem instala chega à pergunta do banco de dados
      Então a saída menciona "pgvector"
      E a saída menciona que o PostgreSQL é o recomendado
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | a observação existe só no README e não no terminal | CT-18 |
| M25 | a observação é exibida só **depois** da escolha, quando já não ajuda a decidir | CT-18 (o `Quando` é "chega à pergunta") |

---

## Regra R7 — a multi-organização ligada na instalação nasce coerente, sem `migrate:fresh`

> `RQ-02`, `RQ-07` · perfil **completo** · técnica: **rastreio de efeito + estado do schema**

```gherkin
  Regra: ligar a multi-organização na instalação produz config e schema coerentes

    Cenário: [CT-19] as três chaves da tenancy ficam coerentes entre si
      Dado um ".env" recém-criado
      Quando quem instala liga a multi-organização com o rótulo "Empresa"
      Então o ".env" tem KIT_TENANCY "true"
      E o ".env" tem KIT_TENANCY_LABEL "Empresa"
      E a configuração de papéis por tenant está ligada
      E o modelo de tenant do Shield aponta para o modelo de organização do kit

    Cenário: [CT-20] as tabelas de permissão nascem com a coluna de contexto
      Dado um projeto instalado com a multi-organização ligada na instalação
      Quando as migrations terminam
      Então a tabela de papéis por modelo tem a coluna de contexto de tenant
      E o banco não foi recriado durante a instalação

    Cenário: [CT-21] @premissa reinstalar com customização preserva o que não foi perguntado
      Dado um ".env" com uma chave própria do usuário chamada "MINHA_CHAVE"
      Quando quem instala reinstala forçando a customização e responde um nome novo
      Então o ".env" tem o nome novo em APP_NAME
      E o ".env" ainda tem a chave "MINHA_CHAVE" com o valor original
      E o ".env" tem exatamente uma linha APP_NAME
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | escreve `KIT_TENANCY=true` no `.env` e não toca `permission.teams` | CT-19, CT-20 |
| M27 | liga as chaves **depois** do `migrate` — schema sem coluna de contexto, sem erro | CT-20 |
| M28 | resolve chamando o comando destrutivo, recriando o banco | CT-20 (segunda asserção) |
| M29 | rótulo plural derivado por concatenação ingênua, gravando "Empresas" quando o singular tem acento | ⚠️ **sem matador** — o requisito não determina como derivar o plural; a pergunta 6b coleta o plural do usuário, então não há derivação a testar. **Lacuna declarada** |

---

## Regra R8 — a cor escolhida veste os três painéis e não rouba a cor da organização

> `RQ-02`, `RQ-07` · perfil **padrão** · técnica: **EP + precedência**

```gherkin
  Regra: a cor primária escolhida vale para os três painéis, e a da organização vence no painel de negócio

    Cenário: [CT-22] a cor escolhida veste os três painéis
      Dado um projeto instalado com a cor primária "Blue"
      Quando quem opera abre cada um dos três painéis
      Então a paleta primária de cada painel é a da cor "Blue"

    Cenário: [CT-23] cor não configurada mantém a paleta padrão do Filament
      Dado um projeto instalado sem cor primária definida
      Quando quem opera abre o painel de administração
      Então a paleta primária é a padrão do Filament

    Cenário: [CT-24] a cor da organização continua vencendo no painel de negócio
      Dado um projeto com a cor primária "Blue" e uma organização com identidade visual própria
      Quando quem opera abre o painel de negócio daquela organização
      Então a paleta primária é a da organização
      E ao abrir o painel de administração a paleta primária volta a ser "Blue"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | a cor global é registrada numa janela posterior à da organização e passa a vencer no `/app` | CT-24 |
| M31 | nome de cor inválido resolvido por `constant()` sem guarda: todo painel morre com `Error` | ⚠️ **sem matador** — a lista de opções é fechada e o cenário exigiria gravar um valor fora do domínio direto no `.env`. **Lacuna declarada**: tentado modelar como cenário de `.env` adulterado; ficou desproporcional ao risco (o valor só chega ali por edição manual). Se a guarda cair na revisão de código, vira CT |
| M38 | a cor é registrada só no painel de negócio (a linha entra num provider e não nos três) | CT-22 |
| M39 | a resolução devolve uma cor mesmo com a config vazia — o kit ganha um "azul padrão" que o Filament não tem | CT-23 |

---

## Regra R9 — o fecho oferece resumo, testes e estrela, cada um com o seu gatilho

> `RQ-09`, `RQ-10`, `RQ-13`, `RQ-14` · perfil **mínimo/padrão** · técnica: **tabela de decisão**

```gherkin
  Regra: o fecho da instalação oferece resumo, testes e estrela

    Cenário: [CT-25] o resumo lista o que foi escolhido e o que continua manual
      Dado uma instalação em que quem instala escolheu nome, banco, credenciais, cor e organização
      Quando a instalação termina
      Então o resumo mostra cada um dos cinco valores escolhidos
      E o resumo não mostra a senha do administrador em texto legível
      E o resumo aponta os itens que continuam sendo editados à mão

    Cenário: [CT-26] @premissa os testes do kit só rodam com aceite explícito
      Dado uma instalação personalizada
      Quando quem instala recusa rodar os testes
      Então nenhum processo de teste é executado
      E a saída informa o comando para rodá-los depois

    Cenário: [CT-27] a estrela é oferecida e o endereço é sempre exibido
      Dado uma instalação personalizada em terminal interativo
      Quando a instalação termina
      Então quem instala é perguntado sobre dar uma estrela ao projeto
      E o endereço do repositório do kit é exibido

    Cenário: [CT-28] a estrela pode ser desligada
      Dado uma instalação em que quem instala pediu para não ser convidado a apoiar
      Quando a instalação termina
      Então nenhuma pergunta sobre estrela é exibida
      E o endereço do repositório do kit continua sendo exibido
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | a senha escolhida aparece em texto claro no resumo | CT-25 |
| M33 | os testes rodam sem esperar resposta (ou rodam no "não") | CT-26 |
| M34 | a recusa dos testes não informa como rodá-los depois | CT-26 |
| M35 | o desligamento da estrela também suprime o endereço | CT-28 |
| M36 | a pergunta da estrela é feita mesmo com o desligamento pedido | CT-28 |
| M40 | o convite à estrela nunca chega a ser feito — o trecho fica depois de um `return` do caminho de aviso | CT-27 |

> **Fora do alcance dos CT**: a abertura do navegador (`exec('start …')`). O oráculo é a
> **decisão** — perguntou, respeitou a flag, exibiu o endereço —, nunca o processo do SO.
> Registrado, não esquecido.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: comando de console, sem recurso endereçável por id |
| Autorização exercida na ação | não se aplica: quem roda o comando já tem o shell do projeto |
| **Idempotência** (mesma operação duas vezes) | CT-10, CT-21 — segunda passada não duplica chave nem perde chave do usuário |
| Concorrência | não se aplica: instalação é single-run, não há recurso disputado |
| **Fronteira no ponto de entrada** (o valor digitado) | CT-12, CT-13 |
| **Domínio condicionado** (driver × campos do bloco `DB_*`) | CT-14 |
| Estado × operação de escrita | CT-08, CT-09, CT-10, CT-11 (matriz de estado da linha do `.env`) |
| **Ausente ≠ null ≠ vazio** | CT-10 (ausente), CT-11 (vazio = manter default) |
| Paginação / ordenação | não se aplica: sem listagem |
| Timezone / DST | não se aplica: nenhuma decisão da feature depende de data ou hora |
| **Unicode / limite de tamanho** | CT-12 (emoji de 4 bytes, acento, 256 caracteres) |
| Unicidade + soft delete | não se aplica: não persiste entidade |
| CRUD combinado | CT-21 (instalar → reinstalar) |
| **Mass assignment** | não se aplica: sem payload de formulário |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Injeção em arquivo de configuração** (item novo, nascido desta feature) | CT-12 (linha de injeção) |
| **Segredo em log ou em tela** | CT-25 + CT-29 (abaixo) |
| **Plataforma** (Windows × Unix) | CT-13 (separadores/identificador) e a nota de `ConfiguresPrompts`; o `exec()` da estrela é declarado fora do alcance |

> **CT-29 — a senha não vai para o registro de log.**
> Não cabe em nenhuma das nove regras (é invariante que atravessa todas), e por isso fica aqui, com
> ID próprio:
>
> ```gherkin
>     Cenário: [CT-29] a senha escolhida nunca é registrada
>       Dado uma instalação em que quem instala define a senha "s3nh4-secreta"
>       Quando a instalação termina
>       Então nenhum registro de log contém "s3nh4-secreta"
>       E existe um registro informando o banco escolhido
> ```
> Mata: **M37** — o array de contexto do log leva as respostas inteiras, senha inclusive.

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | perguntas em projeto nascendo | R1 | tabela de decisão | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M1, M3, M5 |
| CT-02 | sem terminal, sem pergunta | R1 | tabela de decisão | Feature | idem | M1 |
| CT-03 | `--no-custom` pula | R1 | tabela de decisão | Feature | idem | M2 |
| CT-04 | `.env` existente não pergunta | R1 | tabela de decisão | Feature | idem | M3 |
| CT-05 | `--force` pergunta de novo | R1 | tabela de decisão | Feature | idem | M4 |
| CT-06 | pular preserva os defaults | R2 | rastreio de efeito | Feature | idem | M6 |
| CT-07 | pular não imprime resumo | R2 | rastreio de efeito | Feature | idem | M7, M8 |
| CT-08 | substitui chave preenchida | R3 | matriz de estado | Unit | idem | M11, M13 |
| CT-09 | descomenta chave comentada | R3 | matriz de estado | Unit | idem | M9 |
| CT-10 | acrescenta chave ausente uma vez | R3 | matriz de estado | Unit | idem | M10 |
| CT-11 | senha vazia mantém default | R3 | EP (vazio) | Feature | idem | M12 |
| CT-12 | nome hostil não corrompe o `.env` | R4 | BVA + fronteira | Unit | idem | M14, M15, M16 |
| CT-13 | nome do banco é identificador válido | R4 | BVA | Unit | idem | M17, M18 |
| CT-14 | bloco `DB_*` por driver | R5 | EP | Feature | idem | M23 |
| CT-15 | conexão corrente é a escolhida | R5 | rastreio de efeito | Feature | idem | M19, M20 |
| CT-16 | banco externo não cria SQLite | R5 | rastreio de efeito | Feature | idem | M21 |
| CT-17 | banco inacessível não migra e orienta | R5 | rastreio de efeito | Feature | idem | M22 |
| CT-18 | observação do pgvector | R6 | rastreio de efeito | Feature | idem | M24, M25 |
| CT-19 | três chaves da tenancy coerentes | R7 | rastreio de efeito | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` + `tests/Kit/TenancyNaInstalacaoTest.php` | M26 |
| CT-20 | schema nasce com contexto, sem recriar | R7 | estado + ordem | Feature | `tests/Kit/TenancyNaInstalacaoTest.php` (ordem) + `tests/Tenancy/SchemaDaTenancyTest.php` (schema) | M27, M28 |
| CT-21 | reinstalar preserva chave do usuário | R7 | idempotência | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M10 |
| CT-22 | cor veste os três painéis | R8 | EP | Feature | `tests/Kit/CorPrimariaTest.php` | M38 |
| CT-23 | sem cor, paleta padrão | R8 | EP | Feature | idem | M39 |
| CT-24 | cor da organização vence no `/app` | R8 | precedência | Feature | `tests/Tenancy/IdentidadeVisualTest.php` (existente) | M30 |
| CT-25 | resumo completo e sem senha | R9 | tabela de decisão | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M32 |
| CT-26 | testes só com aceite | R9 | tabela de decisão | Feature | idem | M33, M34 |
| CT-27 | estrela oferecida, endereço exibido | R9 | tabela de decisão | Feature | idem | M40 |
| CT-28 | estrela desligável | R9 | tabela de decisão | Feature | idem | M35, M36 |
| CT-29 | senha nunca registrada | taxonomia | rastreio de efeito | Feature | idem | M37 |

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Rodar `kit:install` completo (com `npm` e `db:seed`) num cenário | executa `npm install` e publica assets — minutos por cenário, e nada do que ele provaria deixa de ser provado por CT-01…CT-07 com `--no-npm --no-seed` |
| Verificar que o navegador abriu na estrela | `exec()` de comando do SO; o oráculo é a decisão, e CT-27/CT-28 já a cobrem |
| Cada uma das 16 cores da lista | uma classe de equivalência só; CT-22 com uma cor e CT-23 com nenhuma cobrem as duas partições |
| Testar o texto exato dos rótulos das opções | é comportamento visível que o requisito não determina — ver `## Fronteira com o Plano` |
| Instalar de verdade contra um Postgres real | exige serviço externo no CI; CT-17 cobre o ramo que importa (inacessível), e o caminho feliz do driver é responsabilidade do Laravel |
| Nome de projeto com 256 caracteres (linha do CT-12) | cortado na auditoria Ponytail: não discrimina nenhum dos mutantes M14-M16 — `.env` não impõe limite de comprimento |
| Cenário para o channel de log `instalacao` | o channel foi cortado do plano na mesma auditoria; CT-29 continua valendo porque afirma sobre o **conteúdo** do registro, não sobre o destino |

## Sem CT-B

- **Motivo**: a feature não tem superfície de UI. É um comando de console (`php artisan
  kit:install`) — nenhuma tela, componente Livewire ou rota é criada, e o `01-plano-acao.md`
  declara "Sem superfície de UI". Nenhum cenário afirma sobre JavaScript executado, console,
  acessibilidade, cor de pixel ou layout.
- **Ressalva**: R8 (cor primária) **toca** telas, mas o que ela afirma é a **paleta registrada**,
  observável por `ColorManager` no nível de componente — não o pixel. Se algum dia o oráculo virar
  "o botão é azul na tela", aí sim vira CT-B.

## Fechamento do Ciclo (pós-implementação)

1. `php artisan test --compact --filter=Customizador`
2. `composer test:kit` — com atenção a `IdentidadeVisualTest` (M30) e `KitUpdateTest`
3. `php artisan test --testsuite=Tenancy` — CT-19, CT-20
4. Mutação, **se** o plugin for declarado (ver Restrições do Arnês):
   `vendor/bin/pest tests/Kit/CustomizadorDaInstalacaoTest.php --mutate --path=app/Support`
   Mutante sobrevivente volta para este arquivo como lacuna de derivação, não como ajuste de teste.
