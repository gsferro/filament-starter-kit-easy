# Dívida Técnica — encontrada na regressão de telas

> Atende RQ-07: *"use esse momento também para identificar possiveis dividas tecnicas para
> correção, antes das proximas evoluções"*.

**Uma dívida foi paga: DT-03**, por decisão explícita do usuário depois de o quality gate
expor que ela bloqueava o `--tia` — a feature que motivou o upgrade para Pest 5. As outras
nove seguem abertas: o requisito posiciona a correção *antes das próximas evoluções*, o que a
coloca **depois** desta entrega.

Confirmação que continua valendo: `git diff main --stat` **não toca `app/`**. DT-03 vive só em
`tests/`.

## Resumo

Sete dívidas nasceram da rodada de CT-B; três (**DT-08**, **DT-09**, **DT-10**) vieram do
`feature-quality-gate` depois, e duas entradas (**DT-01**, **DT-02**) foram **corrigidas** por
ele. O relatório está em `07-relatorio-qa.md`.

> **2026-08-22.** **DT-10 paga** — e a correção prescrita aqui estava **errada**: a de uma linha
> teria piorado o problema em silêncio. Ver a seção dela. **DT-11**: o diagnóstico original se
> confirmou (Xdebug 3.4.4 presente, PCOV ausente) e o PCOV 1.0.12 foi instalado nesta data — a
> seção registra a medição, e por que uma medição minha intermediária estava errada.

| ID | Dívida | Severidade | Custo estimado | Onde |
|----|--------|-----------|----------------|------|
| ~~**DT-03**~~ | ~~Helpers de teste declarados dentro de arquivos de teste~~ | ~~bloqueante~~ | **PAGA** | `tests/Pest.php` |
| **DT-01** | Botão *Clear Cache* sem texto acessível (a11y critical) | relevante | ~15 min | `vendor` + `app/Providers` |
| **DT-08** | Render hook de plugin vaza entre painéis no mesmo processo PHP | relevante | investigação | `vendor` + `tests/Browser` |
| **DT-04** | Assimetria de cobertura HTTP: `/app` quase sem smoke de backend | relevante | ~1 h | `tests/Kit` |
| **DT-06** | Suíte `tests/Kit` leva ~14 min em série | relevante | depende de DT-03 | `tests/` |
| **DT-02** | Contraste 4.25:1 no indicador de ambiente (a11y serious) | cosmética | ~10 min | `vendor` + CSS |
| **DT-05** | Nenhum `data-testid` nas telas do kit | cosmética | ~1 h | `app/Filament` |
| **DT-07** | Inventário de telas dos CT-B é array escrito à mão | cosmética | ~30 min | `tests/Browser` |
| **DT-09** | Telas de `/infra` misturam inglês e português | cosmética | ~2 h | `lang/`, `vendor` |
| ~~**DT-10**~~ | ~~A suíte de testes escreve no `storage/logs` real~~ | ~~cosmética~~ | **PAGA** | `config/logging.php` + `phpunit.xml` |
| ~~**DT-11**~~ | ~~Sem PCOV: o `--tia` roda com Xdebug e fica impraticável em série~~ | ~~relevante~~ | **PAGA nesta máquina** | ambiente (não é código) |

---

## DT-03 — Helpers de teste declarados dentro de arquivos de teste · ✅ **PAGA**

**Severidade**: era **bloqueante**
**Como foi encontrada**: rodando o baseline do upgrade em dois modos. Em série a suíte é
213/213 verde; em `--parallel`, **7 erros**.

### O problema

```
Call to undefined function usuarioCom()
Call to undefined function noPainelDa()
Call to undefined function pivotDePapeis()
```

Três funções são declaradas dentro de um arquivo de teste e **chamadas de outro**:

| Função | Declarada em | Chamada de |
|---|---|---|
| `usuarioCom()` | `tests/Kit/PaineisTest.php:24` | `tests/Kit/AdminDaOrganizacaoTest.php:22` |
| `noPainelDa()` | `tests/Tenancy/AdminDaOrganizacaoTest.php:76` | `tests/Tenancy/ConviteUsuarioExistenteTest.php:129,267` |
| `pivotDePapeis()` | `tests/Tenancy/ConviteTenancyTest.php:54` | `tests/Tenancy/ConviteUsuarioExistenteTest.php:173,213,238,407` |

Em PHP, função é global no processo. Quando o Pest carrega **todos** os arquivos, a declaração
de um vaza para o vizinho e tudo passa — o acoplamento fica invisível. Em qualquer execução
**parcial**, a declaração não está lá.

Reproduz sem `--parallel`:

```bash
vendor/bin/pest tests/Kit/AdminDaOrganizacaoTest.php tests/Tenancy/ConviteUsuarioExistenteTest.php
# 13 testes, 6 passados, 7 errors
```

### Por que é bloqueante

O `--tia` do Pest 5 roda, **por definição**, apenas os arquivos afetados pelo diff. Ou seja: a
feature que motivou o upgrade para Pest 5 é inutilizável neste projeto enquanto a dívida
existir. O mesmo vale para `--parallel`, e para qualquer `pest tests/Algum/ArquivoTest.php`
isolado — que é o comando mais usado no dia a dia de desenvolvimento.

**Não é regressão do upgrade.** A dívida é anterior; o Pest 4 do projeto simplesmente nunca foi
rodado em paralelo, então ela nunca apareceu.

### A ironia

`tests/Pest.php:116-123` documenta **exatamente** esta armadilha:

> *"Aqui, e não dentro de um arquivo de teste, porque em Pest as funções são globais no
> processo: (…) helper declarado num arquivo só desaparece quando você roda o OUTRO arquivo
> isolado"*

A regra está escrita. Três arquivos a violam.

### Correção

Mover as três funções para a seção de helpers compartilhados de `tests/Pest.php` e apagar as
declarações locais. Nenhuma mudança de assinatura, nenhuma mudança de comportamento.

Cuidado com **`usuarioCom()` × `usuarioDoKit()`**: são quase a mesma função. `usuarioCom()`
aceita `?string $papel` (nulo = sem papel) e gera e-mail com `fake()`; `usuarioDoKit()` exige
papel e recebe e-mail fixo. A correção óbvia é fundir as duas em `usuarioDoKit(?string $papel)`
e ajustar os dois chamadores — mas isso toca os testes de quatro features, e a decisão é de
quem for pagar a dívida.

### Resolução — aplicada nesta branch

Movidos para a seção de helpers compartilhados de `tests/Pest.php`: `usuarioCom()`,
`noPainelDa()` e `pivotDePapeis()`.

**Dois clones desapareceram no processo** — e eles são a parte instrutiva. Existiam só para
escapar da colisão de redeclaração, cada um idêntico ao original:

| Clone | Era idêntico a | Chamadores atualizados |
|---|---|---|
| `pivotDePapeisDaOrganizacao()` (`Tenancy/AdminDaOrganizacaoTest.php`) | `pivotDePapeis()` | 12 |
| `entrarNoPainelDa()` (`Tenancy/ConviteEmMassaTenancyTest.php`) | `noPainelDa()` | 2 |

O padrão trocava *um erro que estoura* por *duas funções idênticas que ninguém percebe*. Está
proibido na rule nova `.ai/rules/testes.md`.

**Guarda automática**: `tests/Kit/HelpersDeTesteTest.php`. Ela usa `token_get_all()` e não regex
— a menção a `conviteCom()` dentro de um docblock é comum nesta suíte, e um regex a contaria como
chamada. Guarda com falso positivo é pior que guarda nenhuma, porque ensina o time a ignorá-la.
Verificada nos dois sentidos: verde no estado atual, e vermelha nomeando o arquivo quando uma
violação é injetada de propósito.

A regra que a guarda enforça **não** é "nenhum helper local" — os 16 helpers de arquivo único
continuam onde estão, como `tests/Pest.php` determina. O defeito é o uso **cruzado**.

### Resultado medido

| Comando | Antes | Depois |
|---|---|---|
| `pest --group=kit --parallel` | 206/213, **7 erros** | **214/214**, 727 asserções |
| Tempo do `--parallel` | 249 s (com erros) | **196 s** |
| `pest --group=kit` (série) | 213/213, 818 s | 214/214, ~690 s |
| `pest --parallel --tia` | **abortava** | roda, grafo criado |

**Um bloqueio extra apareceu ao testar o `--tia`, e também foi resolvido**: `tests/Unit/ExampleTest.php`
era o scaffolding class-based do Laravel, e o `--tia` **aborta a execução inteira** ao encontrar
uma classe PHPUnit (*"Encountered PHPUnit class … Convert it to a Pest test, or run without
Tia"*). Um único arquivo esquecido desliga o Test Impact Analysis para todo o projeto. Convertido
para Pest, com o motivo no cabeçalho do arquivo.

Também configurado em `tests/Pest.php`: `pest()->tia()->defaultBranch('main')->locally()`. O
default do TIA é `master`, e sem isso ele pede `git remote set-head origin --auto` uma vez por
clone; a linha vale para todo mundo de uma vez. O `locally()` liga o TIA no desenvolvimento e o
desliga sozinho em CI, que é o que a doc do Pest recomenda.

---

## DT-01 — Botão *Clear Cache* sem texto acessível

**Severidade**: relevante (a11y **critical** pelo axe-core)
**Como foi encontrada**: `assertNoAccessibilityIssues()` em **`/infra`**.

> **Correção de proveniência (QA-02 do `07-relatorio-qa.md`).** A primeira escrita desta
> entrada dizia `/admin`, porque foi ali que a sonda registrou o botão. Estava errado: o
> `FilamentClearCachePlugin` é registrado **só** em `InfraPanelProvider.php:198`, e o botão
> apareceu no `/admin` por contaminação de processo — ver **DT-08**. Verificado: `/admin`
> isolado tem **0** ocorrências de `clear-cache-button`.
>
> Isto importa para quem for pagar a dívida: verificando em `/admin`, não encontraria o botão
> e concluiria que já estava resolvida.

### O problema

```html
<button x-tooltip="{ content: 'Clear Cache', ... }"
        class="fi-icon-btn fi-size-md flex-shrink-0 w-10 h-10 rounded-full"
        type="button" wire:click="clear" wire:key="clear-cache-button">
```

Sem texto interno, sem `aria-label`, sem `title`, sem `<label>`. O rótulo existe **só** dentro
do `x-tooltip`, que o Alpine renderiza em tempo de hover — leitor de tela nunca o alcança.

Diagnóstico do axe-core: *"Buttons must have discernible text"*. Para quem usa leitor de tela,
o botão é anunciado como *"botão"*, sem nada mais — e ele **limpa o cache da aplicação**.

**Origem**: `cms-multi/filament-clear-cache`, em `vendor/`.

### Correção

Não editar `vendor/`. Duas saídas:

1. **PR upstream** — acrescentar `aria-label` ao Blade do pacote. Certo, mas fora do controle
   do cronograma do kit.
2. **`renderHook` no painel** com um trecho de JS que preenche o `aria-label` a partir do
   `x-tooltip`. Funciona já, e some quando o upstream corrigir.

O kit já injeta comportamento neste pacote — `KitServiceProvider::configureClearCacheButton()`
acrescenta os comandos extras (`config:clear`, `view:clear`, `modelCache:clear`). É o lugar
natural para o `aria-label` também.

**Verificação**: remover `->todo()` do CT-B09 e rodar `pest --testsuite=Browser`.

**Atenção**: o CT-B09 é um lote, e o lote **aborta na primeira falha** — o `/app` já falha no
contraste, então o `/infra` nunca é avaliado e esta `critical` não aparece. Separar o cenário
por painel antes de usá-lo como verificação. Ver QA-03 do `07-relatorio-qa.md`.

---

## DT-04 — Assimetria de cobertura: o painel `/app` quase sem smoke de backend

**Severidade**: relevante
**Como foi encontrada**: inventariando a cobertura existente para escrever
`04-casos-de-teste.md`.

### O problema

| Painel | Rotas com smoke HTTP em `tests/Kit` |
|---|---|
| `/infra` | 15 (`PaginasInfraTest.php:51-68`) |
| `/admin` | 3 (`PaginasInfraTest.php:70-78`) |
| `/app` | **1** — só o `GET /app` genérico de `PaineisTest.php:115-119` |

O painel de negócio — o único que o consumidor do kit usa todo dia — é o menos coberto dos
três. `/app/convites`, `/app/projetos`, `/app/users`, `/app/convites-recebidos` e
`/app/meu-perfil` não tinham nenhuma visita testada antes desta wiki.

### O que esta wiki resolve, e o que não

Os CT-B01 cobrem as 9 telas de `/app` em browser real, que é **mais** do que smoke HTTP prova.
Mas o desequilíbrio na suíte de backend permanece, e ele importa: browser é caro e fica num job
de CI separado; o smoke HTTP é o que roda em `composer test:kit`, o comando de resposta rápida
depois de um `kit:update`.

### Correção

Acrescentar a `tests/Kit/PaginasInfraTest.php` (ou arquivo novo `PaginasAppTest.php`) o dataset
das telas de `/app`, no mesmo formato dos dois blocos que já existem. Cerca de 10 linhas.

---

## DT-06 — A suíte `tests/Kit` leva ~14 minutos em série

**Severidade**: relevante
**Como foi encontrada**: medindo o baseline do upgrade.

### Os números

| Modo | Tempo | Resultado |
|---|---|---|
| Série | **818 s** (13 min 38 s) | 213/213 verde |
| `--parallel` | **249 s** (4 min 9 s) | 206/213 — 7 erros de DT-03 |

O comando que **funciona** é o lento. O rápido está quebrado por DT-03.

Uma suíte de 14 minutos não é rodada durante o desenvolvimento — é rodada no CI, ou depois. Isso
desloca a descoberta de regressão para horas depois da causa, que é justamente o problema que
`composer test:kit` foi criado para resolver.

### Correção

**Dependia de DT-03, que foi paga.** Com `--parallel` de volta, o tempo caiu de **818 s para
196 s** (4,2×) — a suíte volta a caber no ciclo de desenvolvimento.

O que resta: 196 s ainda é muito para rodar a cada passo. Agora **vale medir** com
`pest --profile`, que aponta o cenário lento em vez de adivinhar. Otimizar antes de medir é
adivinhação — e era por isso que esta dívida esperava a DT-03.

---

## DT-02 — Contraste 4.25:1 no indicador de ambiente

**Severidade**: cosmética (a11y **serious** pelo axe-core)
**Como foi encontrada**: `assertNoAccessibilityIssues()` em `/admin`, **no tema claro**.

### O problema

`.environment-indicator` renderiza `#e60076` sobre `#fdf2f8` a 12 px. Contraste **4.25:1**;
mínimo WCAG AA para texto pequeno é **4.5:1**.

> **Vale só no tema claro** (QA-04 do `07-relatorio-qa.md`). O elemento é
> `fi-text-color-600 dark:fi-text-color-400`: no escuro usa a cor 400 e **atravessa** o
> limiar. Verificado — `visit('/admin')->inDarkMode()->assertNoAccessibilityIssues()`
> **passa**, e sem `inDarkMode()` **falha**. A sonda que originou esta entrada só mediu no
> claro, que é justamente o eixo que o requisito (RQ-06) pedia distinguir.

Falta pouco — e é o suficiente para o badge ficar ilegível em tela com brilho baixo ou para
quem tem baixa visão. O badge diz em que ambiente a pessoa está, o que o torna exatamente o
tipo de informação que não deveria depender de boa visão.

**Origem**: `pxlrbt/filament-environment-indicator`, cor definida via `--color-*` inline.

### Correção

Uma regra de CSS no tema do painel, escurecendo o texto de `--color-600` para `--color-700`
(`oklch(0.525 …)`), que atravessa o limiar. O plugin já aceita cor customizada — vale checar
`EnvironmentIndicatorPlugin::color()` antes de escrever CSS.

---

## DT-05 — Nenhum `data-testid` nas telas do kit

**Severidade**: cosmética
**Como foi encontrada**: escrevendo os CT-B. `grep -rn "data-testid\|data-test=" app/ resources/`
não retorna nada.

### O problema

Os seletores dos CT-B são, hoje:

| Tipo de seletor | Exemplo | Estabilidade |
|---|---|---|
| `id` gerado pelo Filament | `#form\.email` | frágil — convenção de framework, muda entre majors |
| Texto visível | `Login`, `Faça login`, `Painel de Controle` | frágil — quebra ao traduzir ou reescrever rótulo |
| `aria-label` | `[aria-label="Mudar para tema escuro"]` | razoável — é contrato de acessibilidade |

Já custou nesta rodada: o CT-B07 foi escrito assertando `Dashboard` e falhou porque o texto real
é `Painel de Controle` (tradução pt_BR do Filament). Um `data-testid` no heading teria evitado.

### Correção

`->extraAttributes(['data-testid' => '...'])` nos elementos que os testes tocam — não em todos.
Começar pelos três campos de login e pelo heading do dashboard.

**Contra-argumento honesto**: o kit tem 40 pacotes Filament, e `data-testid` só cobriria as
telas próprias. Nas de plugin o problema continua. A dívida é real, mas o retorno é parcial —
por isso cosmética, e não relevante.

---

## DT-07 — O inventário de telas dos CT-B é array escrito à mão

**Severidade**: cosmética
**Como foi encontrada**: escrevendo os CT-B (e prevista em ADR-02 como risco aceito).

### O problema

`tests/Browser/TelasDoKitTest.php` lista as 52 rotas em quatro arrays literais. Tela nova que
alguém acrescente ao kit **não entra sozinha** — e a suíte segue verde, dando a impressão de
cobertura completa que não existe mais.

### Correção

Derivar as rotas de `Filament::getPanel($id)->getPages()` + `getResources()`, filtrando as que
exigem `{record}`. Cerca de 15 linhas.

### Por que não foi feito agora

Ponytail: abstração antes da segunda necessidade. O array explícito falha de forma legível
(o plugin nomeia a URL que quebrou) e é lido em dois segundos. A derivação automática é o
caminho certo **quando** o kit ganhar o quarto painel ou quando alguém esquecer uma tela de
verdade — o que é exatamente o gatilho a esperar.

Registrado aqui para que, quando acontecer, ninguém precise redescobrir a solução.

---

## DT-08 - Render hook de plugin vaza entre paineis no mesmo processo PHP

**Severidade**: relevante
**Como foi encontrada**: `feature-quality-gate`, ciclo 1 - QA-01. E a **causa raiz** do erro de
proveniencia de DT-01.

### O problema

`FilamentClearCachePlugin` esta registrado so em `InfraPanelProvider.php:198`. Ainda assim:

| Cenario, no mesmo processo PHP | ocorrencias de `clear-cache-button` em `/admin` |
|---|---|
| `/admin` e a primeira tela do processo | **0** - correto |
| `/infra` visitado antes, depois `/admin` | **9** - o painel renderiza um botao que nao e dele |
| `/infra` tres vezes seguidas | 9, 9, 9 - estavel; o vazamento e **cross-painel**, nao acumulo |

O `register()` do pacote registra um `renderHook` no painel, e o hook sobrevive a troca de painel
dentro do processo.

### Por que importa aqui

Os CT-B01-04 visitam os tres paineis **no mesmo processo**. Logo **o DOM que a suite valida nao e
o DOM que o usuario ve** - e essa e a premissa da suite inteira. Foi tambem o que fez a sonda
desta wiki atribuir o botao ao `/admin`, produzindo um erro de documentacao que so o quality gate
pegou.

### Impacto em producao hoje: nulo

Octane, FrankenPHP e RoadRunner **nao** estao instalados, entao cada request web e um processo
novo. O risco e latente: ligar um worker persistente o torna real, e a manifestacao seria um
botao de administracao aparecendo em painel que nao o registrou.

### Correcao

Duas frentes, e a ordem importa:

1. **Nos testes** (barata): isolar processo por painel nos CT-B de smoke, ou aceitar por escrito
   que o lote valida um DOM contaminado. Aceitar sem escrever e o que produziu QA-02.
2. **No pacote** (upstream): o `renderHook` deveria ser registrado em `boot()` e nao em
   `register()`, ou ser idempotente. Fora do controle do kit.

**Verificacao**: os cenarios da tabela acima, num teste efemero.

---

## DT-09 - Telas de `/infra` misturam ingles e portugues

**Severidade**: cosmetica
**Como foi encontrada**: `feature-quality-gate`, ciclo 1 - QA-06, por inspecao visual de
screenshot.

### O problema

O kit traduz o essencial (`Dashboard` para *Painel de Controle*), mas telas de plugin do `/infra`
ficaram para tras:

| Tela | Strings em ingles |
|---|---|
| `/infra/logs` | *"Logs explorer"*, *"Browse, read and search your application log files, grouped by channel."*, *"1 file"* / *"3 files"*, *"Refresh"* |
| `/infra` (dashboard) | *"Composer releases"*, *"From composer.json / composer.lock on GitHub"*, *"Informational - no auto-updates"* |
| Tela de 403 | *"Message no. SNT-403-783"* |

Pior que ingles puro e o hibrido: *"Modified ha 1 hora"*.

### Correcao

Publicar as traducoes dos pacotes envolvidos (`laboiteacode/filament-logs-explorer`,
`mominalzaraa/filament-composer-release-notifier`) em `lang/vendor/`, ou sobrescrever os rotulos
pelos setters de plugin que ja sao usados para outros - o `InfraPanelProvider` ja faz isso com o
Command Center (`CommandCenterCommands::navigationLabel('Comandos')`).

Nenhuma das outras nove dividas cobre i18n, e o projeto e pt-BR por decisao.

---

## DT-10 - A suite de testes escreve no `storage/logs` real · ✅ **PAGA**

**Severidade**: cosmetica, destino **infra**
**Como foi encontrada**: `feature-quality-gate`, ciclo 1 - QA-07.

### O problema

`phpunit.xml` fixa `DB_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`, `MAIL_MAILER` e
`QUEUE_CONNECTION` em drivers de teste - mas **nao** redireciona o log. O channel `autenticacao`
e `daily` em disco, entao a suite escreve nos logs de trabalho do desenvolvedor:

```
storage/logs/autenticacao-2026-08-14.log  ->  4.463 linhas, 1,1 MB
                                              1.033 delas sao [User@canAccessPanel]
```

Tudo produzido pelas rodadas de teste do dia. Precede esta branch (vem de `tests/Kit`), e a suite
de browser nova soma ao volume.

### Correcao prescrita aqui — **estava errada**

O que esta secao mandava fazer era uma linha em `phpunit.xml`:

```xml
<env name="LOG_CHANNEL" value="null"/>
```

Nao resolve, e **piora em silencio**. Duas razoes, as duas medidas contra o vendor:

1. `LOG_CHANNEL` troca so o canal **default**. As **60** chamadas de log do kit sao
   `Log::channel('ai'|'tenancy'|'autenticacao')` nomeadas (21 + 16 + 23, contadas com
   `grep -rho "Log::channel('[a-z_]*'" app/`) e passam por cima dele. Os tres canais eram
   `daily` fixo em `config/logging.php` — continuavam gravando.

2. A extensao obvia — `<env name="LOG_KIT_DRIVER" value="null"/>` com `'driver' => env(...)` —
   **tambem** nao funciona, e foi medida falhando. Nao existe `createNullDriver` no
   `LogManager` (os drivers vao de `:260` a `:433` em
   `vendor/laravel/framework/src/Illuminate/Log/LogManager.php`; `resolve()` estoura em `:240`),
   e o `env()` do Laravel converte a string `"null"` em `null` de verdade. O `resolve()` lanca,
   o `get()` captura o `Throwable` e devolve o **emergency logger** — que grava em
   `storage/logs/laravel.log`. O log continuaria em disco, no arquivo errado, com um
   `emergency` a cada resolucao de canal. O `null` do `config/logging.php` e um **canal**
   (`driver: monolog` + `NullHandler`), nunca um driver.

### Resolucao — aplicada em 2026-08-22

Driver dos tres canais por env, com o `handler` sempre presente:

```php
// config/logging.php — nos canais 'ai', 'tenancy' e 'autenticacao'
'driver'  => env('LOG_KIT_DRIVER', 'daily'),
'handler' => NullHandler::class,
```

```xml
<!-- phpunit.xml -->
<env name="LOG_CHANNEL" value="null"/>
<env name="LOG_KIT_DRIVER" value="monolog" force="true"/>
```

`createDailyDriver` ignora a chave `handler`; quem a usa e o `createMonologDriver`
(`LogManager.php:433`). Verificado nos dois lados:

| Contexto | Handler resolvido |
|---|---|
| suite (`LOG_KIT_DRIVER=monolog`) | `Monolog\Handler\NullHandler` |
| producao (env ausente, driver `daily`) | `Monolog\Handler\RotatingFileHandler` |

**Guarda**: `tests/Kit/QualidadeDeCodigoTest.php` — *"nao escreve log em disco durante a suite"*,
dataset de `ai`, `tenancy`, `autenticacao` e do canal default. Assere o **handler resolvido**, nao
a chave de config, entao cobre a corrente inteira (env do `phpunit.xml` -> `env()` -> `LogManager`)
e morre se alguem errar o nome da variavel, tirar o `env()` de um canal ou apagar a linha do
`phpunit.xml`. Foi visto **falhando** com `StreamHandler` antes da correcao final.

**Medido depois de aplicar**, e nao so pelo teste-guarda:

- `php artisan test tests/Kit/PaineisTest.php` — o arquivo que produzia
  `[User@canAccessPanel]` — deixou `autenticacao-2026-08-22.log` em **2.304 linhas antes e 2.304
  depois**: zero escrita.
- `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel` — **524/524, 1.444
  asseroes**, e nenhuma escrita nova em `autenticacao-*`, `tenancy-*` ou `laravel.log` (a ultima
  linha dos tres e anterior a rodada). Vale tambem em `--parallel`, onde os workers sao processos
  proprios.
- Pint e PHPStan verdes.

O aviso original se confirmou inofensivo: `tests/Kit/PaineisTest.php` usa `Log::shouldReceive()`,
nao arquivo, e nenhum teste da suite le `storage/logs` (`grep -rn "storage_path('logs" tests/` =
vazio).

---

## DT-11 — Sem PCOV, o `--tia` é impraticável em série · ✅ **PAGA nesta máquina**

**Severidade**: relevante
**Como foi encontrada**: tentando fechar a Verificação Final com `pest --tia` em série, que é a
única combinação em que o TIA liga **e** os CT-B ficam estáveis.

### O problema

> **Confirmada em 2026-08-22, e o diagnóstico original estava certo.** Registro aqui uma medição
> minha que estava **errada**, porque o modo de errar se repete: `php -m | grep -i -e pcov -e
> xdebug` devolveu **vazio** neste ambiente (Git Bash no Windows) e eu li isso como "não há driver
> algum". Não era. O pipe do `grep` aborta contra a saída do `php -m` aqui — a mensagem
> `Aborted` aparece no stderr e o exit code é de erro, não de "zero linhas". O `php.ini`
> declarava `zend_extension = xdebug` com `xdebug.mode = debug,develop,coverage`, e o próprio
> `php -v` imprime `with Xdebug v3.4.4` na quarta linha. **Sinal de alerta**: concluir ausência a
> partir de um `grep` vazio sem checar o exit code — leia a fonte (`php.ini`, `php -v`), não o
> filtro.
>
> O que o TIA diz quando de fato falta driver está em
> `vendor/pestphp/pest/src/Plugins/Tia.php:741-742` — *"Recorded zero edges — coverage driver
> likely missing"*. Não era o caso: o `--tia` sobe e roda neste checkout (`vendor/bin/pest --tia`
> imprime `─ Running in TIA mode.`), só era lento, exatamente como esta seção dizia desde o
> começo.

O `--tia` exige driver de cobertura. O ambiente tinha **Xdebug 3.4.4 e não tinha PCOV**
(`php -m | grep -i pcov` → vazio). Xdebug com cobertura ativa é ordens de magnitude mais lento
que PCOV, e o efeito era este:

| Combinação | Tempo |
|---|---|
| `pest --parallel --tia` (completo) | 507 s — mas derruba 4 dos 11 CT-B, porque `--parallel` não convive com browser |
| `pest --tia` (série, completo) | **abortado após 35 min sem terminar** |

Ou seja: das duas formas de rodar o TIA no run completo que ele exige, uma é rápida e instável, e
a outra é estável e não termina. O TIA fica tecnicamente desbloqueado (DT-03 foi paga) e
praticamente inutilizável.

### Correção

Instalar PCOV no ambiente de desenvolvimento e no job de baseline do CI:

```bash
pecl install pcov
```

No Windows não é `pecl install`: é baixar a DLL de PCOV que casa com a assinatura do PHP
(8.4, **ZTS**, VC22, x64), pôr em `ext/` e declarar `extension=pcov` + `pcov.enabled=1` no
`php.ini`.

E, no CI, um job dedicado — nunca `--tia` no job que roda a suíte, que deve rodar tudo. **Mas a
linha abaixo é palpite, não medição**, e está aqui só como ponto de partida:

```yaml
- run: vendor/bin/pest --tia --coverage --fresh   # job de baseline, artefato pest-tia-baseline
```

O TIA do Pest 5 **não** é um artefato solto: ele guarda estado próprio (`coverage.bin.gz`,
`Tia.php:89`), resolve o *default branch* de que os outros branches herdam o baseline (daí
`TiaRequiresRemote` e `TiaRequiresDefaultBranch`, e o `pest()->tia()->defaultBranch()` que as duas
sugerem) e tem `--baselined` / `--refetch` / `--locally` para isso (`Tia.php:55-63`). Um quarto
job só entrega ganho se esse estado **persistir entre execuções**; sem isso ele reconstrói o
baseline toda vez, gasta minutos e não acelera nada. Quem for fazer, lê o `Tia.php` primeiro.

O `.github/workflows/ci.yml` hoje usa `coverage: none` nos três jobs, que é o correto para eles.
**Deliberadamente não foi adicionado um quarto**: a suíte inteira já roda verde em minutos, o
ganho do TIA é local, e um job de baseline sem persistência seria custo sem benefício.

### Resolução — 2026-08-22, e por que ela não vale para as outras máquinas

PCOV **1.0.12** instalado, com autorização explícita do usuário. No Windows não é
`pecl install`: é a DLL que casa com a assinatura exata do PHP.

| Item | Valor |
|---|---|
| Assinatura do PHP | `API20240924`, **TS**, `VS17`, x64 (PHP 8.4.10 ZTS VC22) |
| Artefato | `php_pcov-1.0.12-8.4-**ts**-vs17-x64.zip`, de `windows.php.net/downloads/pecl/releases/pcov/1.0.12/` |
| DLL | `C:\php-8.4.10\ext\php_pcov.dll` |
| `php.ini` | `extension=pcov` + `pcov.enabled=1` |
| Backup do ini | `C:\php-8.4.10\php.ini.bak-2026-08-22-pcov` |

**O `xdebug.mode` não foi tocado, de propósito.** O `Selector::select()` do php-code-coverage testa
PCOV **antes** do Xdebug quando a granularidade é por linha
(`vendor/phpunit/php-code-coverage/src/Driver/Selector.php:30-34`), então os dois convivem: Xdebug
segue servindo debug/step, PCOV atende cobertura. Confirmado — o `Selector` devolve
`PCOV 1.0.12`. As duas linhas do ini são obrigatórias porque `Runtime::hasPCOV()` exige extensão
carregada **e** `pcov.enabled === '1'` (`vendor/sebastian/environment/src/Runtime.php:243`).

### O ganho, medido

| Comando | Antes (Xdebug) | Depois (PCOV) |
|---|---|---|
| `pest --tia` em série, run completo | **abortado após 35 min** sem terminar | `--tia --fresh`: **24m59s**, 559 casos, 553 passados, 6 skipped, 1.603 asserções |
| `pest --tia` na sequência, sem mudança de código | não chegava a existir | **6,4 s** de suíte (18,4 s de parede, com boot) |

É o ponto todo do TIA: o `--fresh` grava o grafo uma vez e caro; a partir daí a suíte inteira
custa segundos. A matriz de comandos da seção seguinte continua valendo para `--parallel` × browser
— o que mudou aqui é só o driver de cobertura.

### Por que continua aberta para os outros

Instalar extensão PHP é mudança de **ambiente**, não de código: não entra num commit, não é
revisável em PR, e afeta a máquina de quem rodar. É a razão de a coluna "Onde" dizer *ambiente
(não é código)*, e **nenhum commit fecha este item para quem clonar o kit** — cada máquina precisa
da DLL que casa com a própria assinatura de PHP. O CI segue com `coverage: none` nos três jobs, que
é o correto para eles.

O contorno usado nesta wiki: `pest --parallel --group=kit` para o backend (196 s, verde) e
`pest --testsuite=Browser` em série para as telas (120 s, verde). Cobre o mesmo, sem o TIA.

---

## Nota — a matriz de comandos de teste, medida

Não é dívida; é característica das ferramentas, e custa uma tarde para redescobrir. Registrada
aqui e em `.ai/rules/testes-browser.md`.

| Comando | TIA liga? | Browser estável? | Resultado medido |
|---|---|---|---|
| `pest --parallel --tia` (completo) | ✅ | ❌ | **4 dos 11 CT-B falham** por timeout — `--parallel` multiplica processos de navegador |
| `pest --parallel --tia --exclude-group=browser` | ❌ *"does not apply to partial runs"* | n/a | 217/217 verde |
| `pest --parallel --group=kit` | ❌ (partial) | n/a | 214/214 verde, 196 s |
| `pest --testsuite=Browser` (série) | ❌ (partial) | ✅ | 11 cenários, verde em 4 execuções |
| `pest --tia` (série, completo) | ✅ | ✅ | **abortado após 35 min** — ver DT-11 |

**A consequência prática**: o `--tia` exige run **completo** — `--group` e `--exclude-group` o
desligam. E `--parallel` é incompatível com browser. Logo os dois **não convivem numa invocação
só** enquanto os CT-B estiverem na mesma suíte. Use dois comandos, que é o que a Verificação
Final da wiki faz.

---

## O que **não** é dívida, e por que está escrito aqui

Três coisas que pareceriam dívida numa leitura rápida:

1. **`->todo()` no CT-B09** — é o registro de DT-01 e DT-02, não dívida própria. Ver ADR-07.
2. **CT-B usando `actingAs()` em vez de login pela UI** — deliberado, e o CT-B06 cobre o
   formulário. Ver `05-casos-de-teste-browser.md` → Setup Global.
3. **`pest()->browser()->timeout(20_000)`** — é **teto** de reexecução de assertion, não espera
   fixa. Cenário verde não gasta esse tempo. O default de 5 s não alcança o primeiro boot de um
   painel Filament em teste, sem opcache e com Livewire compilando.
4. **Stats do dashboard mostrando `0` por ~2,5 s** — é o odômetro funcionando como
   configurado (`FilamentOdometerEasyPlugin::make()->delay(1000)->duration(1500)`,
   `AdminPanelProvider.php:146-148`). Escolha explícita do mantenedor, não defeito. O que
   **é** lacuna: nenhum CT-B assere o valor de um indicador, então se o custom element
   falhar o `0` fica permanente e silencioso. Ver QA-05 do `07-relatorio-qa.md`.
5. **Senha preservada no formulário após login inválido** — default do Filament, e
   defensável. Sem requisito contra. Ver R-2 do `07-relatorio-qa.md`.
6. **N+1 em `/admin/users`** — medido: **13 queries constantes** com 1, 10 e 30 usuários. A
   aparência de N+1 vinha de deriva de medição no mesmo processo. Ver R-4.
