# Casos de Teste — Host local no final do `kit:install`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Decisões: `02-decisoes-arquiteturais.md`
> Derivado do **requisito** e das **ADRs decididas**. Nenhum cenário foi escrito olhando
> implementação — a classe `App\Support\HostLocal` ainda não existe.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — Oferta, gate de terminal e ramo negativo | 2 | 3 | 6 | padrão |
| B — Sugestão do domínio e validação da entrada | 3 | 3 | 9 | **completo** |
| C — Cadastro no `hosts`: elevação, oráculo, idempotência, plataforma | 3 | 3 | 9 | **completo** |
| D — `APP_URL`, `config()` em memória e isolamento do `.env` | 2 | 3 | 6 | padrão |
| E — Não-abortância e avisos | 2 | 2 | 4 | padrão |
| F — Documentação de instalação (RQ-08) | 1 | 2 | 2 | mínimo |

**Justificativa do Impacto 3**: as áreas A, B, C e D escrevem **fora do projeto** — em
`C:\Windows\System32\drivers\etc\hosts`, arquivo de sistema gravado por um processo **elevado**, e
no `.env`, que não é versionado. Errar aqui não é retrabalho: é alterar a máquina de quem instala
(A, B, C) ou destruir o `.env` de quem roda a suíte (D). Nada disso é revertido por `git checkout`.

- Técnicas aplicadas: EP, BVA (borda do slug vazio), tabela de decisão, matriz estado × operação,
  rastreio de efeito, idempotência ancorada no agregado, **round-trip (auto-consistência)**,
  **TOCTOU**, **oráculo documental**
- **Revisão adversarial**: executada por sub-agente independente — 19 achados, todos fechados.
  Ver `## Revisão Adversarial` no fim do arquivo
- Cenários: **34** · Regras: **11** · Mutantes previstos: **58** · Sem matador: **0**

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | classe nova `App\Support\HostLocal` (base **e** executor injetáveis); método novo no `KitInstall`; reuso de `SubstituicaoEmArquivo::definirNoEnv()`; páginas de documentação em `pt` e `en`. **Sem model, sem migration, sem policy, sem rota** | CT-01, CT-24, CT-34 |
| **F**unction | oferecer · sugerir · validar · **sondar se já resolve** · cadastrar (executar + **conferir**) · escrever `APP_URL` · alinhar `config()` · avisar | CT-03…CT-33 |
| **D**ata | nome do projeto (texto livre: acento, símbolo, emoji, vazio); domínio digitado (texto livre que vira **argumento de processo elevado** e **linha de arquivo de sistema**); conteúdo do `hosts` (vazio / linha exata / caixa alta / **comentada** / prefixo / sufixo maior / linhas de terceiros); `.env` com `APP_URL` **preenchida, comentada ou ausente** | CT-06, CT-09, CT-11, CT-17, CT-19, CT-25, CT-27, CT-30, CT-31 |
| **I**nterfaces | **um só ponto de entrada**: `php artisan kit:install` em terminal, com e sem `--force`. Sem HTTP, sem Livewire, sem job, sem webhook. O caminho não-interativo (`-n`, CI, `composer create-project` sem TTY) é ausência de interface, e é um cenário | CT-02, CT-29 |
| **P**latform | `PHP_OS_FAMILY` (Windows × Darwin × Linux); caminho do `hosts` divergente; `pwsh` presente ou não; UAC; Acesso Controlado a Pastas; `-Encoding ascii`; **resolvedor alternativo** (Herd/Valet/dnsmasq) que responde `*.test` **sem** linha no arquivo | CT-12, CT-15, CT-22, CT-32 |
| **O**perations | primeira instalação em máquina de desenvolvedor; reinstalação com `--force`; máquina com Herd/Valet; máquina onde o domínio já foi cadastrado à mão; máquina onde outro processo (Docker Desktop, VPN, segundo `kit:install`) mexe no `hosts` enquanto o diálogo de UAC espera resposta | CT-16, CT-25, CT-29, CT-32, CT-33 |
| **T**ime | nenhum valor temporal, nenhuma expiração. Dois eixos temporais reais: **ordem** (a etapa depois do nome escolhido e **antes** da impressão das URLs) e **janela TOCTOU** — entre a sonda e a confirmação há **minutos** de diálogo de UAC esperando uma pessoa | CT-01, CT-08, CT-18, CT-33 |

---

## Mapa de Regras

| Regra | Área (perfil) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — A etapa acontece ao final do trabalho de instalação, antes da impressão das URLs, e só quando há alguém para responder | A (padrão) | RQ-01, ADR-02, ADR-05 | ordem por **vizinho imediato** + tabela de decisão | CT-01, CT-02, CT-29 |
| **R2** — A primeira pergunta exibe um exemplo de URL, tem default negativo, e a recusa encerra a etapa sem nenhum efeito | A (padrão) | RQ-02, RQ-03, ADR-05 | EP + **rastreio de efeito** | CT-03, CT-04, CT-05 |
| **R3** — A segunda pergunta vem pré-preenchida com o domínio derivado do nome escolhido antes, e o que a pessoa digitar vence a sugestão | B (completo) | RQ-04, RQ-05, ADR-04 | EP + **BVA na borda do slug vazio** + partição default × sobrescrita | CT-06, CT-07, CT-08, CT-26 |
| **R4** — O domínio informado é validado antes de qualquer efeito, e a recusa é visível | B (completo) | RQ-06 (`@premissa` P1) | EP, cada partição inválida isolada + saída do estado de erro | CT-09, CT-10, CT-11 |
| **R5** — O cadastro reproduz o procedimento documentado, não corrompe o arquivo nem o comando, e o sucesso só é declarado **relendo o arquivo** | C (completo) | RQ-06, ADR-01, ADR-03 | tabela de decisão (SO × releitura) + **oráculo documental** + rastreio de efeito | CT-12, CT-13, CT-14, CT-15, CT-27, CT-30 |
| **R6** — Rodar a etapa de novo não duplica nada, nem quando o arquivo muda no meio | C (completo) | RQ-06 (`@premissa` P3) | **round-trip** + idempotência no agregado + **TOCTOU** | CT-16, CT-28, CT-33 |
| **R7** — Confirmado o cadastro, `APP_URL` passa a ser a URL escolhida **e** `config('app.url')` acompanha | D (padrão) | RQ-07, ADR-02 | rastreio de efeito (3 direções) + EP dos 3 estados da chave | CT-18, CT-19, CT-20 |
| **R8** — Nenhuma falha da etapa aborta a instalação | E (padrão) | RQ-01 + invariante do `KitInstall` ("nenhum passo aborta"), ADR-01 | EP de modos de falha | CT-21, CT-22 |
| **R9** — A escrita acontece no `.env` do projeto que está sendo instalado, e em nenhum outro | D (padrão) | RQ-07 | rastreio de efeito com asserção de ausência + **guarda de origem por lista branca** | CT-23, CT-24 |
| **R10** — Só uma resolução real do domínio exato conta como "já resolve" | C (completo) | RQ-06 (`@premissa` P3, P6) | EP de homógrafos e tombstone + partição do mecanismo de resolução | CT-17, CT-25, CT-31, CT-32 |
| **R11** — A documentação de instalação descreve a etapa, nos dois idiomas | F (mínimo) | RQ-08 | rastreio de cláusula → artefato | CT-34 |

**Técnica escalada acima do perfil da área**: R7 está em área `padrão` e recebe **rastreio de
efeito com três direções**. Motivo: o mutante do banner defasado (ADR-02, "Riscos") é o defeito que
a própria ADR antecipa, e BVA/EP não o distinguem — só a direção "aconteceu no `.env` **e** em
memória" o mata.

**Cobertura das cláusulas**: RQ-01→R1 · RQ-02→R2 · RQ-03→R2 · RQ-04→R3 · RQ-05→R3 · RQ-06→R4, R5,
R6, R10 · RQ-07→R7, R9 · RQ-08→**R11** · RQ-09→R1 (CT-29 prova a decisão de ADR-05 nos **dois**
ramos da flag; a ADR em si é a entrega documental da cláusula de investigação).

---

## Matriz Estado × Operação (única)

O ciclo de vida não é de um registro: é do **domínio local, do ponto de vista de quem o resolve**.
O espaço de estados é derivado do **fato observável** (o domínio resolve, ou não) — não do artefato
escolhido para observá-lo. Foi um achado da revisão adversarial: uma matriz definida como "o
domínio dentro do arquivo `hosts`" deixa fora o estado em que Herd/Valet já resolvem `*.test` por
dnsmasq, **sem** linha no arquivo, e é nele que a etapa pede elevação à toa.

**Estados** (5):

| | Estado |
|---|---|
| `E1` | não resolve, e o arquivo não tem texto do domínio |
| `E2` | resolve por **linha ativa** no arquivo |
| `E3` | o arquivo tem texto **parecido** que não resolve o domínio pedido (prefixo `app.x.test`, sufixo maior `x.test.br`) |
| `E4` | **tombstone**: a linha do domínio exato existe, **comentada** — não resolve |
| `E5` | resolve **fora** do arquivo (Herd, Valet, dnsmasq, entrada de DNS corporativo) |

**Operações** (3): `O1` sondar · `O2` cadastrar · `O3` aplicar `APP_URL`

**5 estados × 3 operações = 15 células.**

| | O1 sondar | O2 cadastrar | O3 aplicar `APP_URL` |
|---|---|---|---|
| **E1** | ○ responde "não resolve" — CT-17 | ✅ executa e **confere relendo** — CT-12, CT-13, CT-14, CT-15, CT-27, CT-28, CT-30 | ◐ **condicional**: com a confirmação, grava — CT-18; sem ela, `APP_URL` **e** `config('app.url')` ficam como estavam **e** sai aviso — CT-13, CT-20 |
| **E2** | ✅ responde "já resolve" — CT-17 | ⛔ **não executa**: executor não chamado **e** arquivo byte a byte igual **e** nenhum aviso de falha — CT-16, CT-28 | ✅ ajusta, porque o domínio de fato resolve (`@premissa` P3) — CT-16 |
| **E3** | ○ responde "não resolve" — o texto parecido não conta — CT-17 | ✅ executa; a linha nova entra e a de terceiros permanece — CT-25, CT-27 | ✅ grava o domínio **pedido**, nunca o parecido — CT-25 |
| **E4** | ○ responde "não resolve" — comentário não resolve — CT-17 | ✅ executa; a linha comentada **permanece** e uma linha ativa é acrescentada — CT-31 | ✅ grava — CT-31 |
| **E5** | ✅ responde "já resolve" (`@premissa` P6) — CT-32 | ⛔ **não executa**: executor não chamado **e** arquivo byte a byte igual **e** nenhum aviso de falha — CT-32 | ✅ ajusta — CT-32 |

**Contagem**: 15 células = **9 ✅** (a operação prossegue) + **3 ○** (leitura que responde
"não resolve" — resposta válida de `O1`, não abstenção) + **2 ⛔** (abstenção) + **1 ◐**
(condicional, com os dois ramos escritos).

**Legenda auditada célula a célula**: cada `⛔` afirma **todos** os efeitos que `O2` dispara no
caminho feliz — executor não chamado, arquivo inalterado **e** ausência de aviso de falha (um
"pulei" não pode ser reportado como "falhei"). O ramo negativo do `◐` afirma os dois efeitos de
`O3` (`.env` e memória) **mais** o aviso. Nenhuma célula foi fechada com um não-efeito só, e
nenhuma célula é creditada a um cenário que executa outra operação ou parte de outro estado —
as três atribuições erradas da primeira versão (E2×O1 creditada a um cenário que só cadastra;
E1×O3 creditada ao cenário em que a URL **muda**; E3 creditado a uma única sub-partição) foram
corrigidas na revisão adversarial.

---

## Fronteira com o Plano

> O `01-plano-acao.md` entrou **só** para paths, assinaturas e stack. O `02` entrou como
> **restrição decidida** — ADR aceita é decisão de projeto, não interpretação do implementador, e
> por isso vale como oráculo onde o requisito é omisso mas a ADR fecha (ADR-02 a ordem, ADR-03 a
> plataforma, ADR-04 a derivação do nome, ADR-05 o `--force`).

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `App\Support\HostLocal`; nomes `jaResolve()`, `cadastrar()`, `comandoDeElevacao()` | escolha de implementação | detalhe do cenário, nunca `Então` |
| canal de log `configuracoes` e o formato `[HostLocal@cadastrar] …` | escolha de implementação; o requisito não menciona log | detalhe — nenhum `Então` afirma sobre log |
| validação "sem esquema, sem barra, sem espaço, recusa `.local`" | **comportamento visível ao usuário** que só o PRD determina | **pergunta P1** + cenários `@premissa` |
| sondar Herd/Valet com `ping` antes de editar | **comportamento visível ao usuário** que só o PRD determina — mas a revisão adversarial mostrou que ignorá-lo deixa a etapa sujando o `hosts` de quem já tem Herd | **pergunta P6** + CT-32 `@premissa` |
| `note()` avisando sobre login social e Vite antes de executar | **comportamento visível ao usuário** que só o PRD determina | **pergunta P4b** — sem CT bloqueante |
| construtor com base e executor injetáveis | é **mecanismo**, e mecanismo não apaga cenário: ele fixa que R9 é escrita contra o base injetado, e a guarda de origem (CT-24) cobre o mecanismo descartado | CT-23, CT-24 |

**Valor literal do requisito**: RQ-02 fixa o formato do exemplo (`http://…`, sem porta) e ADR-04
fixa a derivação. CT-06 e CT-18 usam os valores **escritos**, sem injetar nada por `config()` além
do `app.name` que a própria ADR-04 nomeia como fonte.

---

## Perguntas em aberto

Replicadas em `00-requisito.md` → `## Ambiguidades e Perguntas Abertas`. Cada uma bloqueia a regra
indicada; os cenários dependentes estão marcados `@premissa`, e a **direção da premissa foi fixada
por falha fechado**, com o invariante das duas leituras afirmado no mesmo cenário.

| # | Pergunta | Bloqueia | Premissa (falha fechado) | Invariante afirmado junto |
|---|---|---|---|---|
| **P1** | O requisito não menciona validação do domínio digitado. Quais entradas são recusadas, e recusar significa reperguntar, encerrar a etapa ou normalizar? | R4 | **recusa, com mensagem, e repergunta** — aceitar cria um estado (`APP_URL` inválida, argumento de processo elevado emendado) que nenhuma outra cláusula sabe tratar | seja qual for a decisão, **nada do que foi digitado vira comando novo no executor nem linha nova no `hosts`** — CT-11 e **CT-30, que vive fora do bloco de premissa exatamente por isso** |
| **P2** | Com a elevação negada no Windows (UAC fechado), `APP_URL` é ajustada mesmo assim? | R7 | **não ajusta** quando a releitura não encontra o domínio — gravar uma URL que não resolve deixaria a impressão final com um endereço morto | seja qual for a decisão, **a falha vira aviso citando o domínio e trazendo o comando para colar, e o comando termina em `SUCCESS`** — CT-13, CT-20, CT-21 |
| **P3** | O domínio já resolve (instalação anterior, ou Herd/Valet). A etapa encerra sem tocar em nada, ou segue e ajusta `APP_URL`? | R6, R10 | **não reescreve o `hosts`** e **ajusta o `APP_URL`** — o domínio de fato resolve, então recusar o `APP_URL` puniria quem já tinha feito o passo à mão | seja qual for a decisão, **o arquivo continua com exatamente uma linha ativa do domínio e nenhuma linha de terceiros é removida** — CT-16, CT-32 |
| **P4** | A URL gravada leva porta? Sem `FORWARD_APP_PORT` (fora de escopo) o `php artisan serve` segue em 8000, e a impressão final passará a mostrar `http://meu-projeto.test/app`, que não abre | R7 | **sem porta**, pela literalidade do exemplo de RQ-02 | seja qual for a decisão, **o valor gravado em `APP_URL` é exatamente a URL que a pergunta confirmou, e nenhuma outra** — CT-18, CT-26 |
| **P6** | O domínio pode já resolver **sem** linha no `hosts` (Herd, Valet, dnsmasq, DNS corporativo). A etapa sonda a resolução de verdade, ou só lê o arquivo? | R10 | **sonda a resolução, e não eleva quando o domínio já responde** — elevar à toa suja o arquivo de sistema de quem já tinha a máquina configurada | seja qual for a decisão, **o `hosts` nunca ganha uma linha para um domínio que já resolve** — CT-32 |
| **P4b** | O `note()` de aviso sobre login social e Vite faz parte da etapa? | — | — | não bloqueia regra nenhuma; nenhum CT depende dele |
| **P5** | Grafia do exemplo (`staterkit` × `starterkit`) — **já registrada no `00`**, aqui só referenciada | R2 | CT-03 afirma sobre o **domínio derivado do nome**, não sobre a string literal do exemplo, para não congelar a grafia antes da resposta | — |

**Escopo de P2**: a premissa de falha fechado vale para o **Windows**, onde uma execução foi
tentada e não se confirmou. No Linux e no macOS a ADR-03 decide explicitamente que o `.env` é
ajustado **sem** execução nenhuma — por isso CT-15 tem coluna de `APP_URL`, e não há contradição
entre P2 e ADR-03: são estados diferentes, não leituras diferentes do mesmo estado.

---

## Setup Global

### Personas
Não há **usuários**. Há duas **fronteiras de privilégio**: o terminal comum, onde a pessoa digita,
e o processo elevado, que escreve no arquivo de sistema. É essa fronteira — não uma fronteira entre
contas — que a taxonomia trata no lugar do IDOR.

### Fixtures
Padrão **obrigatório** de `tests/Kit/CustomizadorDaInstalacaoTest.php:24-34`:

```php
beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/kit-host-'.bin2hex(random_bytes(4));

    File::ensureDirectoryExists($this->base);
    File::copy(base_path('.env.example'), $this->base.'/.env');

    // O `hosts` de mentira: um arquivo no diretório temporário, nunca o do sistema.
    $this->hosts = $this->base.'/hosts';
    File::put($this->hosts, "127.0.0.1\tlocalhost\n");
});

afterEach(fn () => File::deleteDirectory($this->base));
```

### Fakes — e a armadilha que a revisão adversarial encontrou
- **Executor injetado**, sempre. Nenhum caso dispara `Start-Process`, UAC ou `sudo` de verdade.
- **O executor de teste não pode ser um closure que escreve o que o cenário mandou.** Se ele
  escrever a linha "porque o cenário pediu", toda asserção "o arquivo passa a conter o domínio"
  é **auto-realizável**: ela mede o fixture, não o código. O mutante que usa `Set-Content` (ou um
  redirecionamento `>`) no lugar de `Add-Content` monta um comando que contém a linha, em `ascii`,
  no caminho do Windows, com elevação — e **apaga o `hosts` da máquina de quem instala**.
  O arnês precisa de **duas** formas de executor:

| Executor | Papel | Usado por |
|---|---|---|
| **registrador** | guarda o comando recebido e devolve o código de saída que o cenário mandar, **sem tocar no arquivo** | CT-12, CT-13, CT-30, CT-32 — cenários sobre o comando e sobre o oráculo |
| **intérprete** | reconhece a forma do comando emitido (acrescentar × sobrescrever) e a aplica ao `hosts` de mentira | CT-10, CT-14, CT-16, CT-25, CT-27, CT-28, CT-31, CT-33 — cenários sobre o efeito |

  Só o intérprete torna CT-27 e CT-28 falsificáveis. Um executor único que sempre anexa deixa
  M46 (`Set-Content`) e M47 (formato não reconhecido pela própria sonda) vivos com o conjunto verde.
- ~~`Laravel\Prompts\Prompt::fake([...])` para os cenários que afirmam sobre o texto da pergunta e
  sobre o default~~ — **corrigido na implementação** (`03-progresso.md` → D4). `Prompt::fake()` não
  serve de arnês de pergunta no Windows, que é justamente o sistema desta etapa: `Prompt::prompt()`
  chama `checkEnvironment()`, que lança ali sempre que o fallback está desligado — e desligá-lo não
  é possível, porque `fallbackWhen($c)` faz `$c || static::$shouldFallback` e só sabe **ligar**.
  O caminho que roda de verdade no Windows é o **fallback**, ligado por
  `ConfiguresPrompts::configurePrompts()` em `windows_os() || runningUnitTests()`. O arnês registra
  o próprio fallback (`ConfirmPrompt::fallbackUsing()`, `TextPrompt::fallbackUsing()`) e com isso
  afirma sobre o **objeto do prompt** — `label`, `default` e `validate` como o código os construiu.
  M7 e M14 ficaram mais fortes: são afirmados no valor, não no texto renderizado.
  `Prompt::fake([])` continua no `beforeEach`, só para capturar em buffer a saída de `note()`, que
  é o que CT-15 afirma sobre a instrução do Unix (`assertStrippedOutputContains()` existe em
  `vendor/laravel/prompts/src/Concerns/FakesInputOutput.php` — conferido, não inventado)
- Nenhum `Queue::fake()`/`Mail::fake()`: a etapa não despacha job nem envia nada

### Helpers — restrição do projeto que muda o arquivo de teste
`envDoTeste()`, `valorNoEnv()` e `customizadorNoTemp()` hoje **nascem dentro de**
`tests/Kit/CustomizadorDaInstalacaoTest.php`. `tests/Kit/HostLocalTest.php` precisa de
`envDoTeste()` e `valorNoEnv()` → pela rule `.ai/rules/testes.md` ("helper usado por mais de um
arquivo vive em `tests/Pest.php`"), enforçada por `tests/Kit/HelpersDeTesteTest.php`, os dois
**devem ser movidos para `tests/Pest.php`** na mesma entrega. Não clonar com outro nome: a rule
proíbe explicitamente, e o clone é pior que a colisão. CT-34 usa `documentacaoDoKit($idioma)`, que
**já** vive em `tests/Pest.php:933`.

### Estratégia de DB
`tests/Pest.php` já liga `TestCase` + `RefreshDatabase` + `group('kit')` a `tests/Kit`. Nenhum
cenário desta feature toca banco; o `RefreshDatabase` vem de graça.

### Camada
**Todos os cenários são `Unit`/`Feature` em `tests/Kit/HostLocalTest.php`**, exceto CT-01, CT-24 e
CT-34, que são asserções sobre a **fonte** e sobre a **documentação** — precedente do projeto em
`tests/Kit/CustomizadorDaInstalacaoTest.php:117-131` ("olha a fonte porque o defeito é de ORIGEM
DO SINAL"). Ver `## Sem CT-B`.

---

## Regra R1 — A etapa acontece ao final do trabalho de instalação, antes da impressão das URLs, e só quando há alguém para responder

> RQ-01, RQ-09 · ADR-02, ADR-05 · área A, perfil **padrão** · técnica: **ordem por vizinho imediato** + **tabela de decisão**

```gherkin
# language: pt
Funcionalidade: Host local ao final do kit:install

  Regra: a etapa é o último passo interativo, depois do trabalho pesado e antes da impressão das URLs

    Cenário: [CT-01] a oferta vem depois do trabalho de instalação e antes das URLs de acesso
      Dado o fluxo do comando "kit:install" em uma instalação normal
      Quando a sequência de passos do comando é percorrida
      Então a oferta do host local aparece depois da execução das migrations
      E aparece depois do build de assets
      E aparece antes da impressão das URLs de acesso
      E nenhum passo que escreve em banco ou em disco do projeto aparece depois dela

    Cenário: [CT-02] sem alguém para responder, nenhuma pergunta é exibida e nada é decidido
      Dado uma instalação sem terminal interativo
      E um arquivo de hosts que existe e não contém "loja-do-ferro.test"
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando o comando chega à etapa do host local
      Então nenhuma pergunta do host local é exibida
      E o executor de elevação não é chamado nenhuma vez
      E o arquivo de hosts permanece byte a byte igual
      E a chave APP_URL no .env continua "http://localhost:8000"

    Esquema do Cenário: [CT-29] a oferta acontece com e sem --force
      Dado uma instalação com terminal, executada com a opção "<flag>"
      Quando o comando chega à etapa do host local
      Então a primeira pergunta do host local é exibida

      Exemplos:
        | flag    | # partição                                  |
        | nenhuma | instalação normal — ADR-05                  |
        | --force | reinstalação: a etapa **não** é pulada      |
```

**A partição positiva do gate** (terminal presente → a pergunta aparece) vive em **CT-03**, que
afirma sobre o **texto** exibido. CT-02 ficou só com o ramo negativo, de propósito: um `Esquema`
único aplicaria as asserções de não-efeito também à linha positiva, e ali elas passariam com a
etapa perguntando e ignorando a resposta.

**Nota de arnês (obrigatória para CT-02)**: `temTerminal()` (`KitInstall.php:144-148`) devolve
`true` sempre que `runningUnitTests()` é verdadeiro. Um CT-02 escrito por
`$this->artisan('kit:install')` seria **falso ✅**: o ramo "sem terminal" nunca é alcançado dentro da
suíte. O cenário precisa exercitar a **decisão isolada** — o precedente é
`CustomizadorDaInstalacao::devePerguntar()`, testado por tabela em
`tests/Kit/CustomizadorDaInstalacaoTest.php:97-118`. **Se a implementação não expuser uma decisão
isolada equivalente, CT-02 não tem matador garantido** — e isso é achado de implementação a ser
resolvido no `01`, não lacuna a declarar aqui.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a oferta é chamada **depois** da impressão das URLs (o fim literal do `handle()`) — o banner mostra `localhost:8000` logo depois de a pessoa escolher outro endereço | CT-01 |
| M2 | o gate de terminal não existe: a etapa "pergunta" em CI e responde sozinha com o default | CT-02 |
| M3 | a oferta é chamada logo depois do bloco de perguntas, **antes** de migrate, seed e build | CT-01 (primeira e segunda asserções — *revisão adversarial*) |
| M4 | a etapa só existe sob `--force` | CT-29 (linha "nenhuma") |
| M48 | a etapa é **pulada** sob `--force` ("na reinstalação o host já existe") — a partição complementar de M4 | CT-29 (linha `--force`) — *revisão adversarial* |

---

## Regra R2 — A primeira pergunta exibe um exemplo de URL, tem default negativo, e a recusa encerra a etapa sem nenhum efeito

> RQ-02, RQ-03 · ADR-05 · área A, perfil **padrão** · técnica: **EP** + **rastreio de efeito**

```gherkin
# language: pt
  Regra: a oferta é opt-in e a recusa não produz efeito nenhum

    Cenário: [CT-03] a oferta mostra um exemplo de URL construído com o nome do projeto
      Dado uma instalação com terminal
      E que o instalador informou "Loja do Ferro" como nome do projeto
      Quando a etapa do host local exibe a primeira pergunta
      Então o texto da pergunta contém "http://loja-do-ferro.test"

    Cenário: [CT-04] recusar o host local não toca no sistema nem no .env
      Dado um arquivo de hosts que existe e que não contém "loja-do-ferro.test"
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando o instalador responde "não" à oferta de host local
      Então o arquivo de hosts permanece byte a byte igual
      E a chave APP_URL no .env continua "http://localhost:8000"
      E config('app.url') continua "http://localhost:8000"
      E o executor de elevação não é chamado nenhuma vez
      E nenhuma segunda pergunta é exibida

    Cenário: [CT-05] apertar Enter na oferta equivale a recusar
      Dado um arquivo de hosts que existe e que não contém "loja-do-ferro.test"
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando o instalador apenas confirma a pergunta sem digitar resposta
      Então o arquivo de hosts permanece byte a byte igual
      E a chave APP_URL no .env continua "http://localhost:8000"
      E o executor de elevação não é chamado nenhuma vez
```

**Por que o `Dado` de CT-04 e CT-05 põe o arquivo de hosts no mundo**: a asserção de ausência só
discrimina se, naquela configuração, o efeito **poderia** ter acontecido. Um `hosts` inexistente,
ou que já contivesse o domínio, faria o mutante "o ramo negativo ainda cadastra" produzir o mesmo
observável da implementação correta. O caminho feliz **acrescentaria** a linha exatamente nesta
fixture — é isso que torna o não-efeito falsificável.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | o ramo "não" ainda escreve `APP_URL` (a escrita ficou fora do `if`) | CT-04 |
| M6 | o ramo "não" ainda dispara a elevação | CT-04 |
| M7 | `default: true` na confirmação — o Enter vira "sim" e a pessoa ganha um domínio que não pediu | CT-05 |
| M8 | a pergunta é genérica, sem exemplo nenhum ("Cadastrar domínio local?") — RQ-02 pede o exemplo | CT-03 |
| M9 | o exemplo é uma string fixa, não derivada do nome | CT-03 |

---

## Regra R3 — A segunda pergunta vem pré-preenchida com o domínio derivado do nome escolhido antes, e o que a pessoa digitar vence a sugestão

> RQ-04, RQ-05 · ADR-04 · área B, perfil **completo** · técnica: **EP** + **BVA na borda do slug vazio** + partição default × sobrescrita

```gherkin
# language: pt
  Regra: a sugestão deriva do nome do projeto, e a digitação da pessoa prevalece sobre ela

    Esquema do Cenário: [CT-06] o domínio sugerido é o nome do projeto transformado em slug
      Dado que o nome do projeto em memória é "<nome>"
      Quando a etapa calcula o domínio sugerido
      Então o domínio sugerido é "<dominio>"

      Exemplos:
        | nome            | dominio             | # partição                                 |
        | Loja do Ferro   | loja-do-ferro.test  | nome comum, com espaços                    |
        | Ação & Cia      | acao-cia.test       | acento e símbolo — discrimina "usa o cru"  |
        | Clínica 24h     | clinica-24h.test    | acento + dígito                            |
        | ---             | starter-kit.test    | **borda**: slug vazio → fallback           |
        | ✳ ✳ ✳           | starter-kit.test    | **borda**: só símbolos fora do ASCII       |
        |                 | starter-kit.test    | **borda**: nome vazio                      |

    Cenário: [CT-07] a sugestão já vem escrita na segunda pergunta
      Dado que o nome do projeto em memória é "Loja do Ferro"
      E que o instalador aceitou a oferta de host local
      Quando a segunda pergunta é exibida
      Então o campo já vem preenchido com "loja-do-ferro.test"
      E confirmar sem digitar nada escolhe "loja-do-ferro.test"

    Cenário: [CT-08] a sugestão sai do nome recém-escolhido, não do que está gravado em disco
      Dado um .env em disco cujo APP_NAME ainda é "Laravel"
      E que o instalador acabou de informar "Loja do Ferro" como nome do projeto
      Quando a etapa calcula o domínio sugerido
      Então o domínio sugerido é "loja-do-ferro.test"

    Cenário: [CT-26] o domínio digitado vence a sugestão
      Dado que o nome do projeto em memória é "Loja do Ferro"
      E um arquivo de hosts que não contém nenhum domínio do projeto
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando o instalador apaga a sugestão e informa "outro-nome.test"
      Então a linha cadastrada aponta "outro-nome.test" para 127.0.0.1
      E o arquivo de hosts não passa a conter "loja-do-ferro.test"
      E a chave APP_URL no .env passa a ser "http://outro-nome.test"
```

**CT-26 é o cenário que a primeira versão não tinha, e a lacuna era grave**: em todos os demais
cenários o domínio informado coincide com a sugestão, de modo que uma implementação que exibe o
prompt e **descarta o retorno dele** — usando sempre a sugestão — passaria no conjunto inteiro,
cadastrando um domínio que a pessoa não escolheu. Só um valor **diferente** da sugestão discrimina.

**Discriminância de CT-06**: `Ação & Cia` é a linha que obriga a transliteração; as três linhas de
borda são o único ponto em que o fallback `?: 'starter-kit'` de ADR-04 é exercido. Os seis valores
foram conferidos contra `Str::slug()` real, não presumidos.

**Discriminância de CT-08**: a divergência é deliberada — `config('app.name')` já realinhado em
memória contra o `.env` em disco ainda com o valor antigo. Sem ela, "lê de `config()`" e "relê o
`.env`" produzem o mesmo observável.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | usa o nome cru, sem `Str::slug()` — `Loja do Ferro.test` vai para dentro do `hosts` | CT-06 (linhas "Loja do Ferro" e "Ação & Cia") |
| M11 | sem o fallback `?: 'starter-kit'` — a sugestão vira `.test` sozinho, ou string vazia | CT-06 (três linhas de borda) |
| M12 | sufixo diferente de `.test`, ou nenhum sufixo | CT-06 (todas as linhas) |
| M13 | lê o nome do `.env` em disco em vez de `config('app.name')` — vazio nos caminhos não-interativos | CT-08 |
| M14 | a sugestão é calculada mas não vira o default da segunda pergunta (campo em branco) | CT-07 |
| M45 | o retorno da segunda pergunta é descartado (`text(default: $s); $dominio = $s;`) — cadastra sempre a sugestão | CT-26 — *revisão adversarial* |

---

## Regra R4 — O domínio informado é validado antes de qualquer efeito, e a recusa é visível `@premissa P1`

> RQ-06 · área B, perfil **completo** · técnica: **EP com cada partição inválida isolada** + **saída do estado de erro**
>
> **Se P1 for respondida com "não valida nada"**, CT-09 inverte: as linhas passam a ser aceitas, e
> R4 precisa ser reescrita como regra de **normalização**. **CT-11 e CT-30 não invertem** — eles
> afirmam o invariante, e por isso CT-30 foi movido para fora deste bloco, para R5.

```gherkin
# language: pt
  Regra: entrada que corromperia a linha do hosts, o comando de elevação ou o .env é recusada, e a pessoa fica sabendo

    Esquema do Cenário: [CT-09] @premissa domínio malformado é recusado, com motivo e sem efeito
      Dado um arquivo de hosts que existe e não contém nenhum domínio do projeto
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando o instalador informa "<entrada>" como domínio
      Então uma mensagem de erro citando "<entrada>" é exibida
      E a pergunta do domínio é exibida novamente
      E o arquivo de hosts permanece byte a byte igual
      E a chave APP_URL no .env continua "http://localhost:8000"
      E o executor de elevação não é chamado nenhuma vez

      Exemplos:
        | entrada                       | # partição inválida, isolada              |
        | http://loja.test              | traz esquema — viraria "http://http://…"  |
        | loja.test/app                 | traz caminho                              |
        | loja do ferro.test            | traz espaço                               |
        | loja.local                    | sufixo reservado ao mDNS pela RFC 6762    |
        | loja.test'; Remove-Item C:\   | fecha o argumento do comando de elevação  |
        |                               | vazio                                     |

    Cenário: [CT-10] um domínio bem formado é aceito e chega inteiro ao arquivo
      Dado um arquivo de hosts que existe e não contém "loja-do-ferro.test"
      Quando o instalador informa "loja-do-ferro.test" como domínio
      Então a linha cadastrada aponta "loja-do-ferro.test" para 127.0.0.1
      E nenhuma mensagem de erro é exibida

    Cenário: [CT-11] quebra de linha na entrada não injeta linha no hosts nem chave no .env
      Dado um arquivo de hosts com 1 linha
      E um .env com um número conhecido de chaves
      Quando o instalador informa um domínio que contém uma quebra de linha seguida de "127.0.0.1 invasor.test"
      Então o arquivo de hosts não passa a conter "invasor.test"
      E o .env continua com o mesmo número de chaves
      E nenhuma chave nova aparece no .env
```

**"A entrada é recusada" não era observável** — a revisão adversarial mostrou que, com quatro
asserções de **ausência** e nenhuma de presença, uma implementação que encerrasse a etapa em
silêncio passaria, deixando quem instala achando que o host foi cadastrado. As duas primeiras
asserções de CT-09 são o **destino alcançável** do estado de erro.

Partições inválidas nunca combinadas: cada linha de CT-09 carrega **uma** violação.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | aceita domínio com esquema — `APP_URL` vira `http://http://loja.test` | CT-09 (primeira linha) |
| M16 | aceita `.local`, contrariando a RFC 6762 e a própria documentação do repositório | CT-09 (linha `loja.local`) |
| M17 | valida com `filter_var(..., FILTER_VALIDATE_URL)`, que **aprova** `http://loja.test` e **reprova** `loja.test` — o inverso do necessário | CT-09 (primeira linha) + CT-10 |
| M18 | aceita espaço e quebra de linha — linha corrompida ou **linha extra** no `hosts` | CT-09 (linha do espaço), CT-11 |
| M19 | valida, avisa, e **segue usando o valor mesmo assim** | CT-09 (asserções de não-efeito) |
| M20 | validação boa demais: rejeita domínio bem formado e a etapa nunca conclui | CT-10 |
| M55 | recusa em silêncio, sem mensagem nem repergunta — a pessoa acha que deu certo | CT-09 (duas primeiras asserções) — *revisão adversarial* |

---

## Regra R5 — O cadastro reproduz o procedimento documentado, não corrompe o arquivo nem o comando, e o sucesso só é declarado relendo o arquivo

> RQ-06 · ADR-01, ADR-03 · área C, perfil **completo** · técnica: **tabela de decisão (SO × releitura)** + **oráculo documental** + **rastreio de efeito**

```gherkin
# language: pt
  Regra: o que decide se o host foi cadastrado é o conteúdo do arquivo, nunca o código de saída

    Cenário: [CT-12] o comando emitido é o procedimento documentado, com o domínio substituído
      Dado um arquivo de hosts que não contém "loja-do-ferro.test"
      Quando a etapa monta o comando de cadastro de "loja-do-ferro.test" em um sistema Windows
      Então o comando corresponde ao trecho de criação de host da documentação do repositório
      E o comando pede elevação
      E o comando grava em codificação ascii
      E o comando aponta para o arquivo de hosts do Windows, não para "/etc/hosts"

    Cenário: [CT-27] o comando emitido preserva o conteúdo anterior do hosts
      Dado um arquivo de hosts com "127.0.0.1 localhost" e "10.0.0.5 intranet.example"
      Quando o comando de cadastro de "loja-do-ferro.test" é interpretado contra esse arquivo
      Então o arquivo continua contendo "127.0.0.1 localhost"
      E continua contendo "10.0.0.5 intranet.example"
      E passa a conter uma linha ativa para "loja-do-ferro.test"
      E tem exatamente 3 linhas

    Cenário: [CT-30] nada do que foi digitado vira comando novo no executor
      Dado um arquivo de hosts que existe
      Quando o instalador informa "loja.test'; Remove-Item C:\" como domínio
      Então o executor recebe no máximo 1 comando
      E nenhum comando recebido pelo executor contém "Remove-Item"

    Cenário: [CT-13] código de saída de sucesso com o arquivo intocado não confirma o cadastro
      Dado um arquivo de hosts que não contém "loja-do-ferro.test"
      E um .env cuja APP_URL é "http://localhost:8000"
      E um executor que devolve código 0 sem alterar o arquivo
      Quando a etapa cadastra "loja-do-ferro.test"
      Então um aviso citando "loja-do-ferro.test" é acrescentado à instalação
      E o aviso contém o comando de elevação
      E a chave APP_URL no .env continua "http://localhost:8000"
      E config('app.url') continua "http://localhost:8000"

    Cenário: [CT-14] código de saída de falha com a linha presente confirma o cadastro
      Dado um arquivo de hosts que não contém "loja-do-ferro.test"
      E um .env cuja APP_URL é "http://localhost:8000"
      E um executor que devolve código de falha mas cuja execução escreve a linha no arquivo
      Quando a etapa cadastra "loja-do-ferro.test"
      Então a chave APP_URL no .env passa a ser "http://loja-do-ferro.test"
      E nenhum aviso de falha é acrescentado à instalação

    Esquema do Cenário: [CT-15] fora do Windows a etapa instrui, e ainda assim ajusta a URL
      Dado um sistema operacional "<so>"
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando a etapa processa "loja-do-ferro.test"
      Então o arquivo de hosts consultado é "<caminho>"
      E o executor de elevação é chamado "<execucoes>" vez(es)
      E a instrução impressa para o instalador "<instrucao>" a linha pronta com o domínio
      E a chave APP_URL no .env passa a ser "<app_url>"

      Exemplos:
        | so      | caminho                                | execucoes | instrucao | app_url                     |
        | Linux   | /etc/hosts                             | 0         | traz      | http://loja-do-ferro.test   |
        | Darwin  | /etc/hosts                             | 0         | traz      | http://loja-do-ferro.test   |
        | Windows | C:\Windows\System32\drivers\etc\hosts  | 1         | não traz  | http://loja-do-ferro.test   |
```

**CT-13 e CT-14 são o par que mata o oráculo falso nas duas direções.** Um só deles deixaria viva a
metade complementar. E, depois da revisão adversarial, nenhum dos dois afirma sobre um predicado
interno ("o cadastro é dado como confirmado", que é a pergunta reescrita): os dois afirmam sobre
**efeito visível** — o aviso com o comando, e o valor de `APP_URL`.

**CT-15 traz coluna de `APP_URL` porque ADR-03 decide o caso**: no Unix o `.env` é ajustado **sem**
execução nenhuma. Sem essa coluna, a implementação que não ajusta nada no Unix e a que ajusta
produzem o mesmo observável — e a premissa P2 (falha fechado no Windows) seria lida, erradamente,
como se valesse nos três sistemas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M21 | usa o código de saída do `Start-Process` como oráculo — falso positivo com UAC negado | CT-13 |
| M22 | usa o código de saída como oráculo — falso negativo com o UAC aceito e retorno assíncrono | CT-14 |
| M23 | não confere nada: assume sucesso sempre que chamou o executor | CT-13 |
| M24 | confere com `ipconfig /flushdns`, que responde "bem-sucedida" **sem** elevação | CT-13 |
| M25 | eleva também no Linux e no macOS — pede senha de `sudo` em processo não-interativo | CT-15 (linhas Linux/Darwin) |
| M26 | caminho do `hosts` fixo em `/etc/hosts` nos três sistemas | CT-15 · CT-12 |
| M46 | o comando usa `Set-Content` / redirecionamento `>` em vez de `Add-Content` — **apaga o `hosts` da máquina** e ainda assim contém a linha, o domínio, o `ascii` e a elevação | CT-27 — *revisão adversarial* |
| M49 | o domínio é interpolado cru no `-ArgumentList`; a aspa fecha o argumento e emenda um segundo comando **no processo elevado** | CT-30 — *revisão adversarial* |
| M50 | o comando diverge do procedimento documentado, e RQ-08 passa a descrever outra coisa sem nada ficar vermelho | CT-12 (primeira asserção) — *revisão adversarial* |
| M56 | o cadastro não confirmado devolve o booleano certo e **não avisa nada** | CT-13 — *revisão adversarial* |

> **Estouro declarado**: 10 mutantes contra um teto de 6. Quatro vieram da **revisão adversarial**,
> e pela regra do gate não contam para o teto. Os 6 originais cabem: ADR-01 (oráculo) e ADR-03
> (plataforma) atendem a mesma cláusula RQ-06 e não se separam sem duplicar a fixture.

---

## Regra R6 — Rodar a etapa de novo não duplica nada, nem quando o arquivo muda no meio `@premissa P3`

> RQ-06 · área C, perfil **completo** · técnica: **round-trip (auto-consistência)** + **idempotência ancorada no agregado** + **TOCTOU**
>
> **Se P3 for respondida com "encerra sem tocar em nada"**, a terceira asserção de CT-16 inverte:
> `APP_URL` passa a ficar como estava. As demais continuam valendo.

```gherkin
# language: pt
  Regra: o arquivo de hosts termina com exatamente uma linha ativa para o domínio, quantas vezes a etapa rodar

    Cenário: [CT-28] a etapa reconhece a linha que ela mesma escreveu
      Dado um arquivo de hosts sem nenhum domínio do projeto
      Quando a etapa cadastra "loja-do-ferro.test" e em seguida roda de novo com o mesmo domínio
      Então o arquivo contém exatamente 1 linha ativa para "loja-do-ferro.test"
      E o executor de elevação é chamado exatamente 1 vez

    Cenário: [CT-16] @premissa com o domínio já resolvendo, a etapa não mexe no arquivo
      Dado um arquivo de hosts que já aponta "loja-do-ferro.test" para 127.0.0.1
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando a etapa processa "loja-do-ferro.test"
      Então o arquivo de hosts permanece byte a byte igual
      E o executor de elevação não é chamado nenhuma vez
      E a chave APP_URL no .env passa a ser "http://loja-do-ferro.test"
      E nenhum aviso de falha é acrescentado à instalação

    Cenário: [CT-33] o arquivo muda entre a sonda e a confirmação
      Dado um arquivo de hosts sem "loja-do-ferro.test"
      E que outro processo acrescenta "127.0.0.1 loja-do-ferro.test" enquanto o diálogo de elevação espera resposta
      Quando a etapa confere o resultado do cadastro
      Então o arquivo contém exatamente 1 linha ativa para "loja-do-ferro.test"
      E nenhuma linha de terceiros é removida
```

**CT-28 é o cenário de idempotência de verdade, e CT-16 não era.** A primeira versão prometia
"rodar a etapa duas vezes" no título e semeava o estado final no `Dado`: a segunda execução real —
com a linha escrita **pelo próprio código** — nunca acontecia. Isso deixava vivo o mutante mais
plausível da regra: gravar num formato (`TAB`, comentário de assinatura, `0.0.0.0`) que a **própria
sonda** não reconhece, e duplicar a linha a cada `kit:install --force`. CT-16 continua no conjunto,
agora honestamente rotulado: é o cenário do estado `E2`, não o de idempotência.

**Ancoragem**: o agregado é o **arquivo `hosts` relido**, não o retorno da chamada. Um cenário que
afirmasse "as duas chamadas devolveram `true`" passaria por construção.

**CT-33 é TOCTOU, e a janela é de minutos**: entre a sonda e a confirmação, o diálogo de UAC espera
uma pessoa. Docker Desktop, uma VPN corporativa ou um segundo `kit:install` em outro diretório
reescrevem o `hosts` nesse intervalo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | cadastra sem sondar — segunda linha idêntica no `hosts` a cada reinstalação | CT-16, CT-28 |
| M31 | no caminho "já resolve", pula junto o ajuste de `APP_URL` — quem já tinha o host fica sem a URL | CT-16 (terceira asserção) |
| M47 | grava num formato que a própria sonda não reconhece — duplica a cada reinstalação, e nenhuma fixture autoral revela isso | CT-28 — *revisão adversarial* |
| M51 | confirma com base no resultado da sonda **anterior** à elevação, ignorando o que o arquivo virou | CT-33 — *revisão adversarial* |

---

## Regra R7 — Confirmado o cadastro, `APP_URL` passa a ser a URL escolhida e `config('app.url')` acompanha

> RQ-07 · ADR-02 · área D, perfil **padrão** · técnica: **rastreio de efeito (3 direções)** + **EP dos 3 estados da chave**

```gherkin
# language: pt
  Regra: a URL nova vale no arquivo e em memória, para que a impressão final mostre o endereço escolhido

    Cenário: [CT-18] o ajuste vale no .env e em memória, na mesma execução
      Dado um .env cuja APP_URL é "http://localhost:8000"
      E que config('app.url') vale "http://localhost:8000"
      Quando o cadastro de "loja-do-ferro.test" é confirmado
      Então a chave APP_URL no .env passa a ser "http://loja-do-ferro.test"
      E config('app.url') passa a ser "http://loja-do-ferro.test"

    Esquema do Cenário: [CT-19] a chave APP_URL termina única, qualquer que fosse o estado dela
      Dado um .env em que a chave APP_URL está "<estado>"
      E um número conhecido de chaves no arquivo
      Quando o cadastro de "loja-do-ferro.test" é confirmado
      Então o arquivo contém exatamente 1 ocorrência de "APP_URL="
      E o valor efetivo de APP_URL é "http://loja-do-ferro.test"
      E nenhuma outra chave do arquivo muda de valor

      Exemplos:
        | estado     | # partição                        |
        | preenchida | o estado do `.env.example`        |
        | comentada  | `# APP_URL=` — precedente DB_HOST |
        | ausente    | a chave não existe no arquivo     |

    Cenário: [CT-20] @premissa elevação negada no Windows não muda a URL do projeto
      Dado um sistema operacional Windows
      E um .env cuja APP_URL é "http://localhost:8000"
      E que config('app.url') vale "http://localhost:8000"
      E um arquivo de hosts que continua sem "loja-do-ferro.test" depois da tentativa
      Quando a etapa tenta cadastrar "loja-do-ferro.test" e a releitura não encontra o domínio
      Então a chave APP_URL no .env continua "http://localhost:8000"
      E config('app.url') continua "http://localhost:8000"
      E um aviso é acrescentado à instalação
```

**CT-20 é o cenário de P2 e afirma o invariante das duas leituras**: a última asserção vale qualquer
que seja a resposta sobre gravar ou não o `APP_URL`. Se P2 for respondida com "grava mesmo assim",
as duas primeiras invertem e a terceira fica. O `Dado` fixa **Windows** de propósito — é o único
sistema em que uma execução foi tentada e falhou (ver o escopo de P2 acima).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M33 | escreve o `.env` e **não** chama `config(['app.url' => …])` — a impressão final mostra o endereço velho, o defeito que ADR-02 antecipa | CT-18 (segunda asserção) |
| M34 | grava com `file_put_contents` / `str_replace` cru — a chave comentada ou ausente não é tratada | CT-19 (linhas "comentada" e "ausente") |
| M35 | grava o domínio sem o esquema (`loja-do-ferro.test`) — `APP_URL` deixa de ser URL | CT-18 (primeira asserção) |
| M36 | grava `APP_URL` mesmo sem o cadastro confirmado | CT-20 |
| M37 | acrescenta uma **segunda** linha `APP_URL=` em vez de substituir | CT-19 (primeira asserção) |

---

## Regra R8 — Nenhuma falha da etapa aborta a instalação

> RQ-01 + invariante declarado do `KitInstall` ("nenhum passo aborta a instalação") · ADR-01 · área E, perfil **padrão** · técnica: **EP de modos de falha**

```gherkin
# language: pt
  Regra: a etapa é acessória: o que falhar nela vira aviso, e a instalação termina com sucesso

    Cenário: [CT-21] a elevação negada deixa um aviso com o comando pronto para colar
      Dado uma instalação em Windows em que o executor devolve código 0 sem alterar o arquivo
      Quando a etapa termina
      Então um aviso citando "loja-do-ferro.test" é acrescentado à instalação
      E o aviso contém o comando de elevação
      E o comando termina com código de sucesso

    Esquema do Cenário: [CT-22] qualquer modo de falha da etapa vira aviso
      Dado uma instalação em que a etapa falha por "<falha>"
      Quando a etapa termina
      Então nenhuma exceção escapa da etapa
      E um aviso é acrescentado à instalação
      E o comando termina com código de sucesso

      Exemplos:
        | falha                                    | # partição               |
        | o executor lança exceção                 | pwsh ausente do PATH     |
        | o arquivo de hosts não pode ser lido      | antivírus / permissão    |
        | o arquivo de hosts não existe no caminho  | ambiente atípico         |
```

**CT-21 é o par de saída do estado de erro**: o aviso entrega o **destino alcançável** — o comando
que a pessoa cola num terminal elevado. O `Dado` é concreto (o executor mentiroso), e não a
descrição abstrata "uma instalação em que o cadastro não se confirmou", justamente para amarrar a
condição de CT-13 ao aviso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M38 | devolve `FAILURE` quando a elevação falha — uma instalação inteira reprovada por um passo opcional | CT-21, CT-22 |
| M39 | a exceção do executor sobe e derruba o comando no último passo, depois de migrate, seed e build | CT-22 (linha da exceção) |
| M40 | falha silenciosa: nenhum aviso, nenhum comando para colar | CT-21, CT-22 |
| M41 | o aviso existe mas não traz o domínio nem o comando | CT-21 (duas primeiras asserções) |

---

## Regra R9 — A escrita acontece no `.env` do projeto que está sendo instalado, e em nenhum outro

> RQ-07 · área D, perfil **padrão** · técnica: **rastreio de efeito com asserção de ausência** + **guarda de origem por lista branca**

```gherkin
# language: pt
  Regra: a etapa escreve no .env do diretório que recebeu, e o .env de fora dele não é tocado

    Cenário: [CT-23] o ajuste de APP_URL cai no diretório recebido
      Dado uma instalação apontada para um diretório temporário com um .env próprio
      E um segundo .env fora desse diretório, com APP_URL "http://sentinela.test"
      Quando o cadastro de "loja-do-ferro.test" é confirmado
      Então o .env do diretório temporário passa a ter APP_URL "http://loja-do-ferro.test"
      E o .env de fora do diretório continua com APP_URL "http://sentinela.test"

    Cenário: [CT-24] o caminho do .env vem exclusivamente do diretório injetado
      Dado o código-fonte da etapa de host local
      Quando o código é inspecionado
      Então toda montagem de caminho de arquivo parte da propriedade de base recebida no construtor
      E nenhuma chamada a base_path, app_path, config_path, storage_path, getcwd ou realpath aparece
      E nenhuma leitura de $_SERVER['PWD'] aparece
```

**Por que os dois, e por que CT-24 não é redundante**: CT-23 **mata** o mutante `base_path()` — mas
o mata **executando-o**, e a execução reescreve o `.env` real de quem roda a suíte antes de a
asserção ficar vermelha. O prejuízo já aconteceu quando o teste reprova. CT-24 é a guarda que
reprova **sem** disparar a escrita, e tem precedente direto no projeto:
`tests/Kit/CustomizadorDaInstalacaoTest.php:117-131`, escrito pelo mesmo motivo — defeito de
**origem do sinal** não se prova só pelo efeito.

**A guarda é por lista branca, não por lista negra.** A primeira versão citava só `base_path` e
`config_path`, e a revisão adversarial mostrou que `getcwd()`, `realpath('.')`,
`app_path('../.env')` e `$_SERVER['PWD']` passariam intactos. A asserção primária é a positiva
(todo caminho parte da propriedade injetada); a lista de nomes é reforço, não o critério.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M42 | `base_path('.env')` em vez do diretório injetado — a suíte reescreve o `.env` de quem a roda | CT-23, CT-24 |
| M43 | o construtor aceita o diretório-base mas o método de escrita continua usando `base_path()` | CT-23, CT-24 |
| M44 | usa `env()`/`putenv()` em vez de gravar o arquivo — o valor morre ao fim do processo | CT-23 |
| M57 | o caminho vem de `getcwd()`, `realpath('.')` ou `app_path('../.env')` — a lista negra curta não o vê | CT-24 (primeira asserção) — *revisão adversarial* |

---

## Regra R10 — Só uma resolução real do domínio exato conta como "já resolve"

> RQ-06 · área C, perfil **completo** · técnica: **EP de homógrafos e tombstone** + **partição do mecanismo de resolução**

```gherkin
# language: pt
  Regra: o que decide se o domínio já resolve é a resolução real do nome exato, não a aparência do texto no arquivo

    Esquema do Cenário: [CT-17] só uma linha ativa do domínio exato conta como já resolvido
      Dado um arquivo de hosts cuja única entrada é "<conteudo>"
      E nenhum resolvedor alternativo respondendo pelo domínio
      Quando a etapa sonda se "loja-do-ferro.test" já resolve
      Então a resposta é "<resolve>"

      Exemplos:
        | conteudo                             | resolve | # partição                        |
        | 127.0.0.1 loja-do-ferro.test         | sim     | E2 — linha exata                  |
        | 127.0.0.1 LOJA-DO-FERRO.TEST         | sim     | E2 — caixa alta, DNS não difere   |
        | # 127.0.0.1 loja-do-ferro.test       | não     | E4 — tombstone, não resolve       |
        | 127.0.0.1 app.loja-do-ferro.test     | não     | E3 — prefixo, outro domínio       |
        | 127.0.0.1 loja-do-ferro.test.br      | não     | E3 — sufixo maior, outro domínio  |
        | 127.0.0.1 localhost                  | não     | E1 — ausente                      |

    Cenário: [CT-25] um domínio parecido no arquivo não impede nem contamina o cadastro
      Dado um arquivo de hosts que aponta "app.loja-do-ferro.test" para 127.0.0.1
      Quando a etapa cadastra "loja-do-ferro.test"
      Então o arquivo de hosts passa a conter uma linha ativa para "loja-do-ferro.test"
      E a linha de "app.loja-do-ferro.test" permanece no arquivo
      E a chave APP_URL no .env passa a ser "http://loja-do-ferro.test"

    Cenário: [CT-31] cadastrar sobre uma linha comentada do mesmo domínio
      Dado um arquivo de hosts cuja única entrada é "# 127.0.0.1 loja-do-ferro.test"
      Quando a etapa cadastra "loja-do-ferro.test"
      Então o arquivo contém exatamente 1 linha ativa para "loja-do-ferro.test"
      E a linha comentada continua no arquivo
      E a chave APP_URL no .env passa a ser "http://loja-do-ferro.test"

    Cenário: [CT-32] @premissa domínio que já resolve fora do arquivo hosts
      Dado um arquivo de hosts sem "loja-do-ferro.test"
      E um resolvedor alternativo que já responde "loja-do-ferro.test" como 127.0.0.1
      E um .env cuja APP_URL é "http://localhost:8000"
      Quando a etapa processa "loja-do-ferro.test"
      Então o executor de elevação não é chamado nenhuma vez
      E o arquivo de hosts permanece byte a byte igual
      E a chave APP_URL no .env passa a ser "http://loja-do-ferro.test"
      E nenhum aviso de falha é acrescentado à instalação
```

**CT-32 é o estado que a primeira matriz não tinha.** Herd e Valet resolvem `*.test` por dnsmasq,
**sem** linha no `hosts` — e a varredura SFDIPOT creditava esse caso a um cenário cujo `Dado` era
justamente uma linha no arquivo. O espaço de estados tinha sido derivado do artefato observado, não
do fato observável. Sem CT-32, a etapa pede elevação e suja o arquivo de sistema de quem já tinha a
máquina configurada.

**CT-31 é a célula `E4 × O2`**: o `#` é o tombstone deste domínio, e cadastrar sobre ele é
exatamente o análogo de "criar → excluir → recriar com o mesmo valor único" da taxonomia.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | sonda com `str_contains($conteudo, $dominio)` cru — o prefixo `app.loja-do-ferro.test` bloqueia o cadastro legítimo | CT-17 (linhas prefixo/sufixo), CT-25 |
| M29 | sonda sensível a caixa — `LOJA-DO-FERRO.TEST` não é vista e vira duplicata | CT-17 (linha caixa alta) |
| M30 | sonda sem ignorar linha comentada — dá por cadastrado um domínio que não resolve | CT-17 (linha comentada) |
| M32 | ao cadastrar, reescreve o arquivo inteiro e perde as linhas alheias | CT-25 (segunda asserção), CT-27 |
| M52 | a sonda olha **só** o arquivo — com Herd/Valet a etapa eleva à toa e suja o `hosts` | CT-32 — *revisão adversarial* |
| M53 | descomenta ou apaga a linha em tombstone em vez de acrescentar uma linha nova — perde o que a pessoa comentou de propósito | CT-31 (segunda asserção) — *revisão adversarial* |

---

## Regra R11 — A documentação de instalação descreve a etapa, nos dois idiomas

> RQ-08 · área F, perfil **mínimo** · técnica: **rastreio de cláusula → artefato verificável**

```gherkin
# language: pt
  Regra: a etapa entregue está descrita na documentação de instalação em português e em inglês

    Esquema do Cenário: [CT-34] a documentação de instalação descreve a etapa do host local
      Dado a documentação de instalação no idioma "<idioma>"
      Quando ela é lida
      Então ela descreve a etapa de cadastro do host local no final do "kit:install"
      E cita o ajuste de APP_URL decorrente

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

**Por que esta regra existe**: a primeira versão declarava RQ-08 "sem regra de teste", delegando à
suíte `tests/Kit/SiteDeDocumentacaoTest.php`. A revisão adversarial apontou o óbvio — aquela suíte é
**anterior** à feature e não pode afirmar sobre uma etapa que ainda não existe. O mutante "a etapa
foi implementada e a documentação não a menciona" ficava sem matador, contra uma cláusula explícita
do requisito. O helper `documentacaoDoKit($idioma)` (`tests/Pest.php:933`) já existe.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M54 | a etapa é entregue e a documentação de instalação não a menciona | CT-34 |
| M58 | a documentação é escrita só em `pt`, e a versão `en` fica descrevendo a instalação antiga | CT-34 (linha `en`) |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi tentado}`.

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **reenquadrado**: a fronteira de confiança aqui não é entre contas, é entre **níveis de privilégio do processo** — a etapa transporta texto digitado num terminal comum para dentro de um processo Administrador → CT-30 |
| Autorização exercida na ação (não só consultada) | CT-13, CT-14 — a barreira é a do sistema operacional, e os cenários provam que a etapa **verifica o resultado** dela em vez de confiar no retorno |
| Idempotência (ancorada no agregado) | CT-28 (round-trip real), CT-16 (estado E2) |
| Concorrência | CT-33 — **não é "não se aplica"**: o `hosts` é recurso compartilhado do sistema, e entre a sonda e a confirmação há minutos de diálogo de UAC |
| Fronteira no ponto de entrada (gravação) | CT-09, CT-11, CT-30 |
| Domínio condicionado (SO × comportamento) | CT-15 |
| Estado × operação de escrita | CT-16, CT-25, CT-31, CT-32 (matriz de 15 células acima, 15/15) |
| Ausente ≠ `null` ≠ vazio | CT-06 (nome vazio), CT-09 (domínio vazio), CT-19 (chave preenchida / comentada / ausente) |
| Paginação / ordenação | **não se aplica**: sem listagem |
| Timezone / DST | **não se aplica**: nenhum valor temporal entra ou sai da etapa |
| Unicode / limite de campo | CT-06 (acento, símbolo fora do ASCII), CT-11 (quebra de linha), CT-12 (`-Encoding ascii`) |
| Unicidade + tombstone (o análogo local de soft delete) | CT-17 (linha comentada), CT-31 — **não é "não se aplica"**: a persistência existe, é o arquivo `hosts`, e o `#` é o tombstone |
| CRUD combinado | CT-28 (executar duas vezes), CT-31 (recriar sobre tombstone). Remover a entrada do `hosts` está **fora de escopo** por declaração do `00` |
| Mass assignment | **não se aplica**: sem payload, sem model |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| Superfície Livewire (método público, prop, estado do framework) | **não se aplica**: nenhum componente, página ou widget — ver `## Sem CT-B` |
| **Entrada de usuário que vira argumento de processo elevado ou linha de arquivo de sistema** (equivalente de console do item de superfície Livewire) | CT-30, CT-09, CT-11 |
| **Comando emitido preserva o recurso compartilhado** (`Add-Content` × `Set-Content`) | CT-27 |
| **Round-trip: o sistema reconhece o que ele próprio escreveu** | CT-28 |
| IDOR por entidade | **não se aplica**: a feature não persiste nenhuma tabela |
| Escopo com discriminante nulo | **não se aplica**: sem query |
| Saída do estado de erro (4xx/redirect tem destino) | CT-21 (aviso com o comando para colar), CT-09 (mensagem + repergunta) |
| **Escrita fora do sandbox da suíte** (linha nascida deste projeto) | CT-23, CT-24 |
| Cláusula de documentação com falsificador próprio | CT-34 |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|---|---|---|---|---|---|---|
| CT-01 | a oferta vem depois do trabalho e antes das URLs | R1 | ordem por vizinho | Unit (fonte) | `tests/Kit/HostLocalTest.php` | M1, M3 |
| CT-02 | sem terminal, nenhuma pergunta | R1 | tabela de decisão | Unit | `tests/Kit/HostLocalTest.php` | M2 |
| CT-03 | a oferta mostra o exemplo derivado do nome | R2 | EP | Feature | `tests/Kit/HostLocalTest.php` | M8, M9 |
| CT-04 | recusar não toca em nada | R2 | rastreio de efeito | Feature | `tests/Kit/HostLocalTest.php` | M5, M6 |
| CT-05 | Enter equivale a recusar | R2 | EP | Feature | `tests/Kit/HostLocalTest.php` | M7 |
| CT-06 | domínio sugerido é o slug do nome | R3 | EP + BVA | Unit | `tests/Kit/HostLocalTest.php` | M10, M11, M12 |
| CT-07 | a sugestão vem pré-preenchida | R3 | EP | Feature | `tests/Kit/HostLocalTest.php` | M14 |
| CT-08 | a sugestão sai do nome em memória | R3 | EP discriminante | Unit | `tests/Kit/HostLocalTest.php` | M13 |
| CT-09 | domínio malformado é recusado, com motivo | R4 | EP, inválidas isoladas | Feature | `tests/Kit/HostLocalTest.php` — **dois casos**: os não-efeitos contra `processar()` e a mensagem + repergunta pelo diálogo (`03-progresso.md` → D6) | M15, M16, M17, M18, M19, M55 |
| CT-10 | domínio bem formado é aceito | R4 | EP | Feature | `tests/Kit/HostLocalTest.php` | M17, M20 |
| CT-11 | quebra de linha não injeta | R4 | rastreio de efeito | Feature | `tests/Kit/HostLocalTest.php` | M18 |
| CT-12 | o comando é o da documentação | R5 | oráculo documental | Unit | `tests/Kit/HostLocalTest.php` | M26, M50 |
| CT-13 | sucesso mentiroso vira aviso, não URL | R5 | oráculo por releitura | Feature | `tests/Kit/HostLocalTest.php` | M21, M23, M24, M56 |
| CT-14 | falha com efeito confirma | R5 | oráculo por releitura | Feature | `tests/Kit/HostLocalTest.php` | M22 |
| CT-15 | fora do Windows instrui, e ajusta a URL | R5 | tabela de decisão | Unit/Feature | `tests/Kit/HostLocalTest.php` | M25, M26 |
| CT-16 | com o domínio já resolvendo, não mexe | R6 | estado E2 | Feature | `tests/Kit/HostLocalTest.php` | M27, M31 |
| CT-17 | só o domínio exato conta | R10 | EP de homógrafos | Unit | `tests/Kit/HostLocalTest.php` | M28, M29, M30 |
| CT-18 | ajuste no `.env` e em memória | R7 | rastreio de efeito | Feature | `tests/Kit/HostLocalTest.php` | M33, M35 |
| CT-19 | a chave termina única | R7 | EP dos 3 estados | Feature | `tests/Kit/HostLocalTest.php` | M34, M37 |
| CT-20 | elevação negada não muda a URL | R7 | rastreio de efeito | Feature | `tests/Kit/HostLocalTest.php` | M36 |
| CT-21 | aviso com o comando para colar | R8 | EP | Feature | `tests/Kit/HostLocalTest.php` | M38, M40, M41 |
| CT-22 | todo modo de falha vira aviso | R8 | EP de modos de falha | Feature | `tests/Kit/HostLocalTest.php` | M38, M39, M40 |
| CT-23 | a escrita cai no diretório recebido | R9 | rastreio de efeito | Feature | `tests/Kit/HostLocalTest.php` | M42, M43, M44 |
| CT-24 | o caminho vem só do base injetado | R9 | guarda por lista branca | Unit (fonte) | `tests/Kit/HostLocalTest.php` | M42, M43, M57 |
| CT-25 | domínio parecido não contamina | R10 | EP de homógrafos | Feature | `tests/Kit/HostLocalTest.php` | M28, M32 |
| CT-26 | o domínio digitado vence a sugestão | R3 | partição default × sobrescrita | Feature | `tests/Kit/HostLocalTest.php` | M45 |
| CT-27 | o comando preserva o conteúdo anterior | R5 | rastreio de efeito | Feature | `tests/Kit/HostLocalTest.php` | M46, M32 |
| CT-28 | reconhece a linha que ela mesma escreveu | R6 | round-trip | Feature | `tests/Kit/HostLocalTest.php` | M47, M27 |
| CT-29 | a oferta acontece com e sem `--force` | R1 | tabela de decisão | Feature | `tests/Kit/HostLocalTest.php` | M4, M48 |
| CT-30 | nada digitado vira comando novo | R5 | invariante de privilégio | Feature | `tests/Kit/HostLocalTest.php` | M49 |
| CT-31 | cadastrar sobre linha comentada | R10 | tombstone | Feature | `tests/Kit/HostLocalTest.php` | M53 |
| CT-32 | domínio que já resolve fora do arquivo | R10 | partição do mecanismo | Feature | `tests/Kit/HostLocalTest.php` | M52 |
| CT-33 | o arquivo muda entre sonda e confirmação | R6 | TOCTOU | Feature | `tests/Kit/HostLocalTest.php` | M51 |
| CT-34 | a documentação descreve a etapa | R11 | rastreio de cláusula | Unit (doc) | `tests/Kit/HostLocalTest.php` | M54, M58 |

**34 cenários · 58 mutantes · 0 sem matador.**

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "o `ipconfig /flushdns` é executado depois do cadastro" | não mata mutante nenhum: o próprio `dominio-local.md:96-98` diz que ele responde sucesso sem elevação, logo não é observável de nada |
| "o log em `configuracoes` registra o cadastro" | o requisito não menciona log; o canal é escolha de implementação (ver `## Fronteira com o Plano`) |
| "a etapa não roda sob `--custom`" | ADR-05 registra `--custom` como **evolução futura**, não como comportamento desta entrega — cenário sobre algo que não foi decidido |
| "a pergunta avisa sobre login social antes de executar" | depende de P4b, que não bloqueia regra nenhuma; escrever o cenário congelaria um texto que o requisito não pede |

> **Corrigido na revisão adversarial**: "reinstalar com `--force` reoferece o host" havia sido
> cortado aqui com a justificativa de que mataria o mesmo mutante de CT-01. Era falso — CT-01 mata
> `if ($force)` e não mata `if (! $force)`, que é a partição complementar e a leitura mais provável
> de RQ-09 ("na reinstalação o host já existe, então pulo"). O cenário voltou como **CT-29**.

---

## Sem CT-B

**Não existe `05-casos-de-teste-browser.md`, e não deve existir.**

- `01-plano-acao.md` → `## Superfície de UI` declara **"Sem superfície de UI"**: a feature é um
  comando de console, e a interação é por Laravel Prompts no terminal
- Nenhum cenário afirma sobre **JavaScript executado, console do navegador, acessibilidade, cor,
  tema ou layout** — as únicas coisas que só o navegador prova
- A superfície real são duas perguntas de terminal, uma escrita em `.env`, uma escrita em arquivo
  do sistema (com executor injetado) e o `config()` em memória. Tudo isso é falsificável em
  `tests/Kit/HostLocalTest.php`, que é a camada mais barata que o arnês do projeto sustenta
- Consequência: o gate de tela de escrita e o gate de camada da regra **não se aplicam** — não há
  rota `create`/`edit` nem componente de UI, e portanto não há a diferença entre "a regra existe" e
  "a tela chama a regra". As regras R4 (validação) e R5 (barreira do sistema) já são exercidas fora
  de qualquer camada de apresentação, por construção

O único caminho que **nenhuma** suíte cobre, por desenho, é a janela de UAC real. Ele fica como
**verificação manual** no `01-plano-acao.md` → `## Verificação Final`, com o mesmo raciocínio que o
docblock de `tests/Kit/CustomizadorDaInstalacaoTest.php:12-23` já registra para o TTY do Composer:
o oráculo automatizável aqui é a **decisão**, não o efeito externo.

---

## Revisão Adversarial

**Disparo**: perfil completo nas áreas B e C, e Impacto 3 em A, B, C e D.
**Execução**: sub-agente independente, que recebeu **apenas** o `00-requisito.md` e a primeira
versão do `04`. Não recebeu o `01-plano-acao.md`, o `02-decisoes-arquiteturais.md`, o código nem o
raciocínio desta derivação. Áreas percorridas: A, B, C, D e E; regras R1–R9; os 25 cenários e os 44
mutantes da primeira versão; matriz, checklist, índice, "Cogitado e Cortado" e "Sem CT-B".

**19 achados. Todos fechados.**

| # | Lacuna apontada | O que virou |
|---|---|---|
| 1 | CT-01 ancorava a posição em extremos distantes — chamar a etapa logo após as perguntas, antes de migrate/seed/build, passava | CT-01 reescrito com **vizinho imediato**: depois das migrations, depois do build, e nada que escreve depois dela. M3 reapontado |
| 2 | em todos os 25 cenários o domínio informado coincidia com a sugestão: descartar o retorno do prompt passava no conjunto inteiro | **CT-26** + M45 |
| 3 | o executor falso escrevia "o que o cenário pedisse" → toda asserção de efeito era auto-realizável; `Set-Content` apagaria o `hosts` e passaria | **CT-27** + M46, e a seção `### Fakes` reescrita com **dois** executores (registrador × intérprete) |
| 4 | nenhum round-trip: gravar num formato que a própria sonda não reconhece duplica a linha a cada reinstalação | **CT-28** + M47 |
| 5 | "não depende de `--force`" mata `if ($force)` e não mata `if (! $force)` — a partição complementar estava cortada por engano | **CT-29** + M48; a linha errada de "Cogitado e Cortado" foi corrigida com nota |
| 6 | CT-15 não tinha nenhum `Então` sobre `APP_URL`: as duas leituras opostas do Unix passavam | coluna `app_url` em CT-15, e o **escopo de P2 fixado ao Windows** |
| 7 | a partição de injeção vivia só dentro de um `@premissa` que pode inverter — e é a que roda como Administrador | **CT-30**, movido para R5, **fora** do bloco de premissa, + M49 |
| 8 | CT-09 só tinha asserções de ausência: recusar em silêncio passava | `Então` de mensagem + repergunta, + M55 |
| 9 | "o cadastro é/não é dado como confirmado" é a pergunta reescrita | CT-13 passou a afirmar **aviso citando o domínio + comando + `APP_URL` intacta**, + M56; CT-21 ganhou `Dado` concreto |
| 10 | CT-02 tinha `Então` circular, e aplicava asserções de não-efeito também à linha positiva | CT-02 virou cenário só do ramo negativo, com oráculos concretos; a partição positiva ficou em CT-03 |
| 11 | a guarda de origem era lista negra de dois nomes — `getcwd()`, `realpath()`, `app_path('../.env')` passavam | CT-24 reescrito como **lista branca** (todo caminho parte da propriedade injetada), + M57 |
| 12 | CT-12 não amarrava o comando ao procedimento documentado, que é a cláusula literal de RQ-06 | **oráculo documental** em CT-12 + M50 |
| 13 | CT-16 prometia duas execuções no título e semeava o estado final no `Dado` | CT-16 rerrotulado como cenário do estado E2; a idempotência real foi para CT-28 |
| 14 | três células da matriz creditadas a cenários que executavam outra operação ou outra sub-partição | matriz refeita; E2×O1 → CT-17; E1×O3 explicitada como **condicional** com os dois ramos; E4 separado de E3 e fechado por **CT-31** |
| 15 | Herd/Valet resolve `*.test` **sem** linha no `hosts` — estado fora do espaço declarado | espaço de estados refeito sobre o **fato observável**; **E5** e **CT-32** criados, + M52 e a **pergunta P6** |
| 16 | "concorrência: não se aplica" era frágil — o `hosts` é compartilhado e o UAC espera minutos | **CT-33** (TOCTOU) + M51; linha do checklist corrigida |
| 17 | "unicidade + soft delete: não se aplica" confundia mecanismo com conceito — o `#` é o tombstone | linha do checklist reescrita; **CT-31** + M53 |
| 18 | "IDOR: não se aplica" contradizia a linha seguinte do próprio checklist | item reenquadrado como **fronteira de privilégio** → CT-30 |
| 19 | RQ-08 declarada "sem regra de teste", delegada a uma suíte anterior à feature | **R11** + **CT-34** + M54, M58 |

**Sem achado**: nenhum cenário sem `Então`; nenhum cenário com mais de um `Quando` literal.

**Segunda rodada**: não executada, e a decisão é declarada. O teto da skill é de 2 rodadas, e a
segunda só se justifica quando o fechamento cria superfície nova de comportamento. Aqui os 9
cenários novos (CT-26…CT-34) e as 6 reescritas de oráculo não introduzem mecanismo novo: todos
operam sobre os mesmos três verbos (`sondar`, `cadastrar`, `aplicar`) e sobre o mesmo par de
artefatos (`hosts`, `.env`) que a primeira rodada já percorreu inteiro. A nova superfície real é a
**pergunta P6**, que está aberta e bloqueando CT-32 — e uma segunda rodada adversarial sobre uma
premissa não respondida mediria a premissa, não o conjunto. **A segunda rodada fica condicionada à
resposta de P1, P2, P3 e P6**, e deve rodar junto com a materialização dos cenários em Pest.
