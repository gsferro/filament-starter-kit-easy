# Decisões Arquiteturais — Host local no `kit:install`

## ADR-05: A etapa **não** usa `--force`, e fica na instalação normal

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-09

> Esta ADR vem primeiro por ser a resposta direta à pergunta do requisito.

### Contexto

O requisito pede explicitamente: *"veja se precisa do paramentro `--force` ou se numa instalação
normal também faz sentindo"*.

### Decisão

**Não usar `--force`. A etapa entra no fluxo da instalação normal**, ao lado de `oferecerTestes()`
e `oferecerEstrela()`, gated por `temTerminal()`.

### Alternativas Consideradas

1. **Exigir `--force`** — descartada, e não por gosto: `--force` é **destrutivo por definição**.
   Ele chama `BancoSqlite::recriar($caminho)`, que **apaga o arquivo do banco**
   (`KitInstall.php:306-308`), e o próprio docblock classifica isso como *"inócuo no minuto
   seguinte à instalação, destrutivo depois"* (`:86-87`). Amarrar o cadastro de um DNS local a ele
   seria pedir para a pessoa **apagar o banco para ganhar um domínio**.
2. **Flag nova `--host`** — descartada por inversão de default. O precedente do projeto para
   etapa opcional é a flag **negativa** de opt-out (`--no-support`, `:45`). Mas mesmo `--no-host`
   é desnecessário: a etapa já é uma pergunta com `default: false`, e `temTerminal()` a pula
   sozinha onde não há TTY. Flag que não muda nada é superfície morta — e cada flag nova entra
   também em `README.md:341-346`, `README.en.md` e `docs/{pt,en}/comecar/instalacao-avancada.md:119-124`.
3. **Reaproveitar `--custom`** — descartada para o fluxo principal. `--custom` é o caminho de
   **reconfigurar projeto já instalado** (`customizarSemBanco():159-187`), e o requisito fala em
   "no final do comando `kit:install`". Fica registrado como evolução natural: reoferecer o host
   no `--custom` é barato e resolve quem já instalou.

### Consequências

- **Positivas**: nenhum dado em risco; a etapa aparece para todo mundo que instala com terminal;
  zero flag nova para documentar
- **Negativas**: quem instalou antes desta versão só ganha a etapa reinstalando — ou pela
  evolução citada na alternativa 3
- **Riscos**: mais uma pergunta no fim da instalação. Mitigado por `default: false`

---

## ADR-01: A elevação é por UAC, e o oráculo é reler o arquivo

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-06

### Contexto

`C:\Windows\System32\drivers\etc\hosts` só aceita escrita de `BUILTIN\Administradores`, e o
`kit:install` roda em terminal comum — inclusive dentro do `composer create-project`, muitas vezes
sem TTY nenhum (`KitInstall.php:126-143`). O requisito diz **"rode"**, não "informe como rodar".

### Decisão

Executar via **`Start-Process pwsh -Verb RunAs`**, que é literalmente o comando que a documentação
do próprio repositório já ensina (`docs/pt/comecar/dominio-local.md:100-105`). E, logo depois,
**reler o `hosts` e procurar o domínio** — é isso, e só isso, que decide se deu certo.

### Alternativas Consideradas

1. **Só imprimir o comando** — descartada por contrariar o "rode" do requisito. Fica como
   **fallback** quando a conferência falha.
2. **Tentar escrever direto e degradar no `Access is denied`** — descartada como caminho
   principal: em terminal não-elevado ela falha **sempre**, então o caminho real seria o fallback
   disfarçado de tentativa.
3. **Criar um `.ps1` na raiz** — descartada: o único `.ps1` versionado é `deploy_docker_local.ps1`
   (deploy Docker), e criar arquivo novo na raiz sujaria o repositório de quem instala.

### Consequências

- **Positivas**: atende o "rode" literalmente; reusa comando já documentado e já testado na prática
- **Negativas**: janela de UAC fora do terminal; se a pessoa negar, nada acontece
- **Riscos**: `Start-Process -Verb RunAs` é assíncrono e **não devolve código de retorno
  confiável**. Mitigação é a regra dura desta ADR: **o código de saída não é oráculo — a releitura
  do arquivo é.** O mesmo vale para `ipconfig /flushdns`, que responde "bem-sucedida" mesmo **sem**
  elevação (`dominio-local.md:96-98`) e portanto não prova nada

### Referências

- `docs/pt/comecar/dominio-local.md:100-105` — o comando de elevação
- `docs/pt/comecar/dominio-local.md:96-98` — por que o `flushdns` mente
- `KitInstall.php:25-26` — "nenhum passo aborta a instalação"

---

## ADR-02: A etapa roda **antes** do `banner()`, não depois de tudo

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-01, RQ-07

### Contexto

O requisito diz "**no final** do comando". O fim literal do `handle()` é depois de
`oferecerEstrela()` (`KitInstall.php:120`). Mas `banner()` (`:117`) imprime as URLs de acesso
(`{$url}/app`, `/admin`, `/infra`) a partir de **`config('app.url')`** (`banner():416`).

### Decisão

Chamar `oferecerHostLocal()` **entre `desvincularDoSnyk()` (114) e `banner()` (117)** — depois de
todo o trabalho de instalação (migrate, seed, assets, build) e **antes** da impressão final. E
escrever `config(['app.url' => $url])` em memória junto com o `.env`.

### Alternativas Consideradas

1. **Depois de `oferecerEstrela()`** (o fim literal) — descartada: o banner **já teria impresso**
   `http://localhost:8000/app`, e a pessoa terminaria a instalação com o endereço errado na tela,
   logo depois de escolher outro. É a pior combinação: a feature funciona e parece não ter
   funcionado.
2. **Depois do banner, reimprimindo as URLs** — descartada por duplicar a saída e por deixar duas
   verdades na tela.

### Consequências

- **Positivas**: o banner final já mostra `http://meu-projeto.test/app`; uma só verdade na tela
- **Negativas**: "final" passa a significar "final do trabalho", não "última linha do `handle()`".
  Divergência **deliberada** da literalidade do requisito, registrada aqui
- **Riscos**: escrever só o `.env` e esquecer o `config()` em memória reintroduz exatamente o
  defeito da alternativa 1 — o `.env` novo e o banner velho. Tem CT dedicado

### Referências

- `KitInstall.php:416` — `banner()` lê `config('app.url')`
- `CustomizadorDaInstalacao.php:561-566` — `alinharConfigEmMemoria()`, o precedente do padrão

---

## ADR-03: Windows executa; Linux e macOS recebem a instrução

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-06

### Contexto

O requisito diz "host do **windowns**", mas o kit roda nos três sistemas e em Docker, e
`dominio-local.md:24-29` já documenta `/etc/hosts` no mesmo bloco.

### Decisão

`match (PHP_OS_FAMILY)` — reusando o precedente de `KitInstall.php:565-570`:

| SO | Comportamento |
|---|---|
| `Windows` | executa via UAC (ADR-01) |
| `Darwin` / `Linux` | imprime a linha `sudo` equivalente, já com o domínio escolhido, e **ajusta o `.env` normalmente** |

### Alternativas Consideradas

1. **Só Windows, etapa invisível no Unix** — descartada: quem instala em Mac perderia a
   funcionalidade sem saber que ela existe, e o ajuste de `APP_URL` **não precisa de elevação**
   em sistema nenhum.
2. **Elevar com `sudo` no Unix também** — descartada por ora: `sudo` em processo não-interativo
   tem os mesmos problemas do UAC e mais um (senha no terminal). O ganho não paga a superfície.

### Consequências

- **Positivas**: nenhum sistema fica sem a etapa; o `.env` é ajustado em todos
- **Negativas**: assimetria — Windows executa, Unix instrui. Está declarada, não é acidente
- **Riscos**: o CT precisa rodar nos três; o projeto já usa `skip(PHP_OS_FAMILY !== 'Windows', …)`
  (`tests/Kit/BancoSqliteTest.php:76`)

---

## ADR-04: A sugestão sai de `config('app.name')`, não do resumo

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-05

### Contexto

RQ-05 pede "sugestão de acordo com o nome que ele escolheu anteriormente". O nome é a **1ª**
pergunta do customizador (`CustomizadorDaInstalacao.php:136-141`).

### Decisão

`Str::slug(config('app.name')) ?: 'starter-kit'`, mais `.test`.

É **exatamente** a mesma derivação que já produz o `COMPOSE_PROJECT_NAME`
(`nomeDeProjetoDocker():544-547`), e o resultado casa com o `meu-projeto.test` que a documentação
usa nos exemplos.

### Alternativas Consideradas

1. **Ler de `$this->resumo`** (`KitInstall.php:61`) — descartada: o resumo guarda pares
   rótulo/valor **só para impressão** e fica **vazio** quando as perguntas são puladas
   (`--no-custom`, sem TTY). A sugestão sairia vazia justamente nos caminhos não-interativos.
2. **Ler o `.env` de volta** — descartada: `config('app.name')` já está alinhado em memória
   (`alinharConfigEmMemoria():562`), e reler arquivo para saber o que acabamos de escrever é
   trabalho a mais com uma fonte a mais para divergir.
3. **String fixa `starterkit.test`** — descartada por não atender RQ-05.

### Consequências

- **Positivas**: uma só derivação de nome no projeto, compartilhada com o Docker; funciona nos
  caminhos interativo e não-interativo
- **Negativas**: acopla a sugestão ao `APP_NAME`; renomear o projeto depois não renomeia o host
  (correto — o `hosts` é do sistema, não do projeto)
- **Riscos**: `Str::slug()` de nome só com acentos/símbolos devolve vazio — daí o `?: 'starter-kit'`,
  idêntico ao fallback já existente
