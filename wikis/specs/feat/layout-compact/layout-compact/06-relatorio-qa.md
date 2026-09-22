# Relatório de QA — Opção de layout compacto

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **padrão** (wiki `nova`, UI simples sem JS, domínio comum) · Natureza: nova
> Regressão: **sim** — rodada mesmo sendo wiki `nova`, porque o `01` declara que a entrega toca infra compartilhada

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 3 · Minor: 3 · Cosmético: 0
- Ambiente: app **não servido** (validação estática + suíte) · PHP 8.4.25 · Pest 5.0.5 · Playwright MCP e Boost MCP **indisponíveis**
- Regressão reproduzida pelo gate: `php artisan test --testsuite=Kit,Tenancy --parallel` → **2.717 passaram, 10.525 asserções, 0 falhas** (bate com o `03:311`)
- `vendor/bin/pint --test` **passed** · `vendor/bin/filacheck` **17/17**
- Skills de `qa-skills` não instaladas — usados os fallbacks inline desta skill (gate de esforço, tabela de severidade, redução de repro)

Nenhum defeito de comportamento foi encontrado no código entregue. Os três Major são de
**texto contra código**: um invariante do `00` fechado por releitura em vez de decisão, e a wiki
(`01`/`04`) parada no estado anterior ao `/code-review` do step 7.5.

## Achados

### QA-01 — O menu entrega só metade do compacto, e o invariante do `00` é fechado por releitura · Major · destino 1

- **Dimensão**: A (cobertura do requisito)
- **Relacionado a**: RQ-05, invariante do `00`, `03` § A1, `wikis/roadmap.md` item 5
- **Esperado**: `00-requisito.md` § Ambiguidades, decisão fechada com o usuário — escopo de RQ-05 são
  **stats, table, menu e button**, e *"**Invariante afirmado junto**: seja qual for a decisão,
  **nenhuma superfície fica parcialmente compacta** — meia tela compacta é pior que nenhuma."*
- **Observado**: das quatro superfícies, o **menu** responde em **altura de item** (40,0 → 32,8 → 31,2 px)
  e **não** em **largura** (320 px nos três níveis). O `03:55-56` fecha o ponto afirmando que *"a largura
  da sidebar não é nenhuma delas"* — mas a largura é **atributo do menu**, que é uma das quatro; a
  frase troca a categoria "superfície" por "dimensão de uma superfície". A topbar (64 px fixos) está
  no mesmo caso, e os cartões do Pulse (128 px nos três níveis) são um terceiro.
- **Repro** (estática, sem navegador):
  1. `grep -n -- "--sidebar-width" vendor/filament/filament/resources/views/components/layout/base.blade.php`
     → `:85  --sidebar-width: {{ filament()->getSidebarWidth() }};` — valor inline, **não** `calc(var(--spacing) * N)`
  2. `grep -rn -- "--topbar-height:" vendor/filament/filament/resources/css/components/topbar.css` → `:2 --topbar-height: 4rem`
  3. Logo, a declaração do kit (`:root{--spacing:…}`) **não alcança** nem uma nem outra — o que a medição do `03` já mostrava
- **Evidência**: os dois greps acima · `03` § Medição (linhas *largura da sidebar* e *topbar*) · `wikis/roadmap.md` item 5
- **Destino**: 1 — especificação
- **Ação exigida**: perguntar ao usuário e fechar num **Adendo numerado no `00`** — ou o invariante passa a
  valer só para as dimensões que `--spacing` governa, ou `--sidebar-width` entra na escala (o próprio
  roadmap diz que é barato e é o candidato mais provável). Não corrigir código antes dessa resposta.

### QA-02 — O `01-plano-acao.md` ainda carrega a evidência que o próprio `/code-review` invalidou · Major · destino 1

- **Dimensão**: L (L3 — PRD × código)
- **Relacionado a**: step 7.5, achado 3 · `03:311-315`
- **Esperado**: step 7 da `feature-wiki` — desvio corrige o `01`/`02` **de origem**, não só o `03`.
- **Observado**, item a item:
  | Onde | Afirma | Medido agora |
  |---|---|---|
  | `01:28-29`, `01:428` | `composer test:kit` **2.715 / 10.508 asserções** | **2.717 / 10.525** — o `03:311` foi remedido, o `01` não. É o achado 3 do 7.5 reaberto no arquivo vizinho |
  | `01:359`, `01:420`, `01:427` | **14 CTs, 25 casos**, 25/25 e 47 asserções | **15 `it()`, 26 casos, 54 asserções** desde CT-16 |
  | `01:435` e `01:56-58` | `- [ ] ⚠️ CT-15 … **pendente**` / *"não foi implementado"* | CT-15 **está** escrito e roda: `tests/Kit/SiteDeDocumentacaoTest.php` `[CT-48]`, 10 asserções (`03:331`) |
  | `01:84-85` | **398 linhas / 1 removida / seis arquivos** | **420 / 1 / oito arquivos**; e em `bf6e799` já eram **sete**, não seis |
  | `01` § Estrutura de Implementação | passos 1–7 | **nenhum passo** para `mutateFormDataBeforeFill()` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:199`) nem para `KitUpdate::CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:261`) — dois trechos entregues sem passo no PRD |
- **Repro**:
  1. `php artisan test --testsuite=Kit,Tenancy --parallel` → `{"tests":2717,"assertions":10525}`
  2. `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --compact` → `{"tests":26,"assertions":54}`
  3. `vendor/bin/pest tests/Kit/SiteDeDocumentacaoTest.php --filter="CT-48"` → `{"tests":1,"assertions":10}` (não skipado)
  4. `git diff --stat main...HEAD -- app/ config/ database/ .env.example` → `8 files changed, 420 insertions(+), 1 deletion(-)`
- **Destino**: 1 — especificação/wiki
- **Ação exigida**: remedir e reescrever as cinco linhas do `01`, e acrescentar os dois passos que faltam (o
  `mutateFormDataBeforeFill()` e a linha de `CAMINHOS_DO_KIT`) com commit apontado.

### QA-03 — Oito citações `arquivo:símbolo:linha` erradas, com a Verificação Final declarando "21/21 ok" · Minor · destino 1

- **Dimensão**: L (L2)
- **Observado**:
  - `04:112,125,126,127,128` — `tests/Pest.php:usuarioDoKit():490`, `gravarConfiguracao():347`,
    `alinharConfiguracoesDoKit():368`, `configuracaoGravada():699`, `kitConfigCom():581`.
    Reais: **491, 348, 369, 700, 582**. Causa: o `use App\Console\Commands\KitUpdate;` acrescentado ao topo de
    `tests/Pest.php` no commit `8c3ef0b` (7.5) empurrou o arquivo inteiro em **+1**
  - `01:331`, `03:15`, `03:407` — `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:densidade_do_layout:**789**`.
    Real: **810**. A linha 789 é `->label('Avisar sobre alterações não salvas')`, do campo **vizinho** —
    símbolo errado, não só linha. Causa: o bloco de `mutateFormDataBeforeFill()` do commit `d19e1e3` (7.5) empurrou **+22**
  - `03:327` afirma *"Citações `arquivo:símbolo:linha` **reverificadas — 21/21 ok**"* — a reverificação é
    **anterior** aos dois commits do 7.5 e o checkbox está marcado
- **Repro**: `grep -n "^function usuarioDoKit" tests/Pest.php` → `491` · `grep -n "Select::make('densidade_do_layout')" app/Filament/Admin/Pages/ConfiguracoesDoKit.php` → `810`
- **Destino**: 1
- **Ação exigida**: refazer o grep de citações **depois** do último commit de código e desmarcar/remarcar o `03:327` com a data certa. As 13 demais citações do gate conferiram (vendor inclusive: `base.blade.php:44`, `HasTheme.php:viteTheme():27`, `cell.css:2`, `CanBeCompact.php:11`, `Settings.php:fill():178`, e `app/Support/DensidadeDoLayout.php` nas cinco linhas citadas).

### QA-04 — CT-16 e CT-48 existem no código sem cenário derivado no `04`; o índice e as contagens do `04` contradizem o teste · Major · destino 3

- **Dimensão**: L (L1) + K
- **Observado**:
  - `04:34`, `04:38`, `04:639` — *"Cenários: **14** (25 casos)"*, *"o arquivo tem 14 `it()`"*, *"**14 cenários,
    25 casos executados.** **Todos** em `tests/Kit/DensidadeDoLayoutTest.php`"*. Reais: **15 `it()` / 26 casos**
    naquele arquivo, e **CT-15 vive em outro arquivo**. Espelhado em `03:17` e `03:310`
  - **CT-16 não tem cenário Gherkin em lugar nenhum do `04`** — só a linha `04:637` do Índice e a linha do
    gate. Essa linha o atribui à regra **R3** (*"Desligada, nada muda"*), e o caso é sobre **vocabulário
    ilegível**, que é **R8**; a coluna *Camada* traz "**1**", que não é camada. O mutante **M33** não aparece
    em nenhuma tabela de *Mutantes previstos*
  - `04:636` dá a CT-15 a regra **R8** (vocabulário fechado) — nada a ver com oráculo documental
  - `04:564-569` — o Gherkin de CT-15 tem **três** asserções e ainda diz "**NÃO ESCRITO**"; o `[CT-48]`
    implementado tem **quatro** (a quarta é `expect(caminhosDoKit())->toContain('wikis/roadmap.md')`), e essa
    quarta — a que o `/code-review` obrigou — **não tem cenário de origem**
  - `04:511` e `04:575` — `L3` descrita como *"continua **aberto**"*; está fechada desde `9ff8ccb`
  - `04:34` e `04:648` — *"Mutantes previstos: **30**"*; o próprio arquivo cita M31, M32 e M33 → **33**
- **Repro**: `grep -c "^it(" tests/Kit/DensidadeDoLayoutTest.php` → 15 · `grep -n "CT-16" 04-casos-de-teste.md` → só `:637` e `:646`
- **Destino**: 3 — teste (o cenário nasce no `04` via `feature-test-design`, com mutante nomeado); a parte de contagem/índice é destino 1
- **Ação exigida**: derivar CT-16 e a quarta asserção de CT-48 como cenários de verdade (regra, Gherkin,
  mutante), corrigir as regras/camadas do Índice, e recontar cenários, casos e mutantes nos três arquivos.

### QA-05 — A guarda de `export-ignore` de `[CT-48]` reimplementa o `.gitattributes` com regex e deixa passar padrões plausíveis · Minor · destino 3

- **Dimensão**: K (oráculo fraco)
- **Relacionado a**: RQ-10, achado 1 do step 7.5
- **Esperado**: o caso trava a decisão de 2026-09-21 — o roadmap **não** pode ganhar `export-ignore`.
- **Observado**: `tests/Kit/SiteDeDocumentacaoTest.php` usa
  `preg_match('~^/?wikis/?\*?\.?m?d?\s~', $linha)`. Dois padrões que **removeriam** o roadmap do
  `composer create-project` passam despercebidos e deixam o caso **verde**.
- **Repro**:
  ```
  php -r 'foreach (["wikis/** export-ignore","*.md export-ignore","wikis/*.md export-ignore"] as $l)
          printf("%-28s => %d\n", $l, preg_match("~^/?wikis/?\*?\.?m?d?\s~", $l));'
  wikis/** export-ignore       => 0     <-- não pega
  *.md export-ignore           => 0     <-- não pega
  wikis/*.md export-ignore     => 1
  ```
- **Evidência**: existe oráculo exato e de uma linha — `git check-attr export-ignore -- wikis/roadmap.md`
  (devolve `unspecified` hoje, `set` se qualquer regra alcançar o arquivo). O gate usou o irmão dele,
  `git archive HEAD | tar -t | grep '^wikis/'`, e **confirmou os dois caminhos de entrega**: 11 arquivos de
  topo entregues, `wikis/specs` com **zero** arquivos, e `wikis/roadmap.md` presente em
  `KitUpdate::CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:261`, 11 entradas de `wikis/`, batendo
  com o comentário corrigido do `.gitattributes`).
- **Destino**: 3
- **Ação exigida**: trocar o regex por `git check-attr`, mantendo a mensagem de falha.

### QA-06 — As docs de usuário dizem "duas coisas não apertam"; são três · Minor · destino 1

- **Dimensão**: L (L5)
- **Observado**: `docs/pt/recursos/configuracoes-do-kit.md` e `docs/en/…` — *"Duas coisas **não** apertam, e
  não é defeito: a **largura** do menu lateral … e a barra superior"*. O `wikis/roadmap.md` item 5 e o
  `03` § A2 listam **três**, incluindo os **cartões do Pulse** (`/infra/pulse`, 128 px nos três níveis).
- **Repro**: `grep -n "Duas coisas" docs/pt/recursos/configuracoes-do-kit.md` × tabela de três linhas em `wikis/roadmap.md` § 5
- **Destino**: 1
- **Ação exigida**: "três" nas duas línguas, ou remeter ao item 5 do roadmap em vez de contar.

## Matriz de Rastreabilidade

<!-- Só as linhas com lacuna. RQ-01, RQ-02, RQ-03, RQ-07, RQ-08 e RQ-09 fecharam sem achado. -->

| RQ | Cláusula | Passo PRD | CT | Código | Resultado | Veredito |
|----|----------|-----------|----|--------|-----------|----------|
| RQ-04 | Medir profundidade e peso — o gatilho | 1 | — (`L2`, declarada) | ADR-01…ADR-06 | os três critérios de "fácil" do `00` medidos e passados; RQ-05 escolhida com número | ✅ OK |
| RQ-05 | Se fácil, implementar nas quatro superfícies | 2–6 | CT-01…CT-14 | `DensidadeDoLayout`, hook, settings | stats/table/button ✅; **menu só em altura** | ⚠️ **QA-01** |
| RQ-06 | Se complexo, só documentar | — | — | — | ⛔ excluída por RQ-04, corretamente: mutuamente exclusiva com RQ-05, e o estudo foi entregue como acompanhamento | ✅ OK |
| RQ-10 | Documento de futuras melhorias ligado ao README | 7 | CT-15 = `[CT-48]` | `wikis/roadmap.md` + 2 READMEs + `wikis/README.md` | entregue e **verificado nos dois caminhos** pelo gate (`git archive` e `CAMINHOS_DO_KIT`) | ⚠️ guarda parcial — **QA-05** |
| — | Código sem passo no PRD | — | CT-16 | `mutateFormDataBeforeFill()`, `CAMINHOS_DO_KIT` | entregue no 7.5, sem passo no `01` | ⚠️ **QA-02** |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | 1 achado (QA-01). Nenhuma cláusula sem rastro: as dez têm passo, artefato ou exclusão declarada |
| B | Fronteiras e dados | ✅ | `coagir()` nas duas portas; 7 classes de entrada inválida em CT-10; `.env` ilegível coagido em `config/kit.php:287`. Lacuna M30 já declarada pelo `04` (`L4`) — não é achado novo |
| C | Matriz de permissão | ✅ | Nenhuma célula nova: o campo herda `ExigePermissaoDaTela` da Page, e `tests/Kit/ConfiguracoesDoKitTelaTest.php:197` já assere `assertForbidden()` |
| D | Observabilidade real | ✅ | Ausência de log é decisão declarada (`01` § Channel de Log). `git diff main...HEAD -- app/ config/ database/ \| grep -c "Log::"` → **0**. Sem log, sem PII em log |
| E | Performance | ✅ | Zero query e zero request a mais: a leitura sai de `config()`, já em memória; o hook é uma `Closure` por render. Sem memo estático — e CT-06 é o caso que existe para isso |
| F | UX de erro | ✅ | Nível ilegível é coagido em silêncio ao abrir a tela — sem mensagem, mas com vocabulário fechado e valor cosmético; decisão registrada no docblock e em CT-16. Não é achado |
| G | Tema e cor | ⏭️ pulada | Superfície de cor **nula**: `git diff main...HEAD -- app/ resources/ \| grep -E "bg-\|text-(white\|black\|gray)\|border-\|#hex"` → vazio. A parte visual está em "Não Verificado" |
| H | Acessibilidade | ⏭️ pulada | Fora do perfil `padrão`; o único elemento novo é um `Select` com `->label()` e `->helperText()`, sem JS |
| I | Segurança da superfície nova | ✅ | Nenhuma rota, policy, propriedade Livewire ou método público novo. O texto concatenado no `<style>` vem de **literais do enum**, nunca do request — `coagir()` antes, `espacamento()` depois. Sem IDOR (a chave não tem dono) |
| J | Regressão adjacente | ✅ | Rodada por tocar infra compartilhada: **2.717 / 10.525 / 0 falhas**. O risco declarado no `01` (a tela que salva todas as abas) é real e está coberto por CT-14 e CT-16 |
| K | Adequação da suíte | ⚠️ | Passo estático **limpo** — nenhum caso sem assertion, nenhum `assertOk()` solitário, nenhum `assertSee` de layout. 1 achado (QA-05). Passo medido em "Não Verificado" |
| L | Consistência documental | ❌ | 4 achados (QA-02, QA-03, QA-04, QA-06). L4 (rules × diff) **conferiu**: as 10 linhas da tabela `## Conformidade com Rules` do `03` batem com o código, inclusive `settings.md:13` (citação correta) e o helper cruzado movido para `tests/Pest.php` |

## Débitos Aceitos

Nenhum. Os três Minor (QA-03, QA-05, QA-06) são baratos e vão junto com os Major no ciclo 2.

## Suspeitas Não Confirmadas

- `CT-05` recorta a tag do kit com `substr($estilos, strrpos(...))` **até o fim da string**, não até o
  `</style>`. Hoje o hook da densidade é o último registrado em `STYLES_BEFORE`, então funciona; um hook
  futuro que emitisse `@layer` depois dele tornaria o caso vermelho sem defeito. Sem repro de dano hoje.
- Payload **não-escalar** (array/objeto) na linha `settings` — não sondado. Com payload escalar não há risco:
  `vendor/spatie/laravel-settings/src/Settings.php` não tem `declare(strict_types=1)`, então `5` vira `"5"` e
  `coagir()` fecha em `confortavel`.

## Não Verificado

- **Dimensão K, passo medido (mutation score)** — `vendor/bin/pest … --mutate --path=app/Support/DensidadeDoLayout.php`
  aborta com `Pest\Exceptions\InvalidOption: Mutation testing requires code coverage to be enabled`.
  `php -m` não lista **PCOV nem Xdebug**. A falsificabilidade do `03` (M12 reprovando 7 dos 14 CTs, M33
  nascendo vermelho) **não foi reproduzida** — reproduzi-la exigiria mutar código de aplicação, que esta skill proíbe.
- **Medição em pixel** (`01` passo 6, `03` § Medição) — de sessão, não de suíte, e o gate **não** a refez:
  Playwright MCP indisponível e app não servido. É a lacuna `L1`, já declarada pelo `04`. O gate confirmou
  **estaticamente** só o mecanismo (1.228 ocorrências de `var(--spacing)` e **uma** declaração `--spacing:.25rem`,
  dentro de `@layer theme`, em `public/css/filament/filament/app.css`).
- **ADR-05** — 1.354 linhas / 548 blocos / 370 classes / 11 variáveis do tema pago: os bundles
  `stock-*.css` da demo não estão no repositório. Idem RQ-02 (navegação da demo).
- **`pest --tia`** para impacto medido: não rodado; a regressão completa cobriu o mesmo escopo com mais folga.

---

## Ciclo 2

> Escopo: verificar o **fechamento** dos seis achados do ciclo 1 e tratar o código que entrou
> depois dos gates (a largura do menu, commit `ec665a5`) como **diff não revisado**.
> Regra do loop: os seis do ciclo 1 **não** são re-reportados; o que está abaixo é achado novo, ou
> fechamento que não fechou.

### Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: **1** · Minor: **3** · Cosmético: 0
- Ambiente: app **servido** em `http://127.0.0.1:8347` (`php artisan serve`, derrubado ao fim) ·
  PHP 8.4.25 · Pest 5.0.5 · **Playwright 1.63.0 usado** (pacote npm; MCP continua indisponível)
- Regressão reproduzida pelo gate: `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
  → **2.721 passaram, 10.537 asserções, 0 falhas** — bate com o `03` § Verificação Final
- `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --compact` → **30 passaram, 66 asserções**
- `vendor/bin/pint --test` **passed** · `vendor/bin/filacheck` **17/17**

**Quatro dos seis fecharam de verdade** — QA-01, QA-05, QA-06 e a parte de *cenário* do QA-04;
a prova de cada um está em `## Hipóteses Rejeitadas`. O que sobrou é do mesmo tipo do ciclo 1 e tem
a mesma causa: **a largura do menu entrou depois de todos os gates e a wiki a montante não foi
reconciliada** — a ADR não mudou, três citações novas nasceram erradas, e os contadores do `04`/`03`
fecharam pela metade.

### Achados

#### QA-07 — O mecanismo ganhou um segundo caminho e o `02` não mudou; o `01` ainda diz "uma declaração CSS" · Major · destino 1

- **Dimensão**: L (L3 — PRD/ADR × código; com um item de L4)
- **Relacionado a**: RQ-05, ADR-03, ADR-06, passo 10 do `01`, commit `ec665a5`
- **Esperado**: step 7 da `feature-wiki` e a tabela L3 desta skill — afirmação que o código
  contradiz se corrige **na fonte**, com a marca `*(alterado em …)*`. O próprio `02:240` já usa
  essa marca, então a convenção existe dentro do arquivo.
- **Observado**:

  | Onde | Afirma | O código faz |
  |---|---|---|
  | `02` ADR-03 § Decisão | o compacto sai de **`--spacing`**, e não de outro lugar | a largura do menu sai de `Panel::sidebarWidth()`, que não é `--spacing` — o próprio docblock de `app/Support/DensidadeDoLayout.php:larguraDaSidebar():201` diz isso |
  | `02` ADR-03 § Consequências | *"uma declaração; **imune a `composer update`**, porque **não referencia nada do vendor**"* | referencia: `->sidebarWidth()` é API de painel, e o `'20rem'` do nível confortável **congela o default do vendor** (`vendor/filament/filament/src/Panel/Concerns/HasSidebar.php:11`) |
  | `02` ADR-06 § Decisão | tabela de **dois** caminhos (render hook × `viteTheme()`) | há um **terceiro** em uso — `sidebarWidth(Closure)`, avaliado no render (`HasSidebar.php:getSidebarWidth():68`). A ADR-06 é citada como justificativa nos três providers e não conhece o caso |
  | `01:462-464` § Filosofia | *"o mecanismo inteiro é **uma declaração CSS**"* | são dois mecanismos — e o passo 10, ~50 linhas acima **no mesmo arquivo**, descreve o segundo |
  | `01:170-175` § Superfície de UI | duas linhas de emissão | `--sidebar-width` é um terceiro ponto, emitido em **toda** rota dos três painéis (`base.blade.php:85`) |
  | `CHANGELOG.md:22` | *"**É uma declaração de CSS**, e nenhuma classe `fi-*` foi escrita"* | idem — o parágrafo novo logo acima já diz o contrário |
  | `03:371-385` § Conformidade com Rules | dez linhas, nenhuma para `providers-filament.md` | o glob `app/Providers/Filament/**` passou a casar **três** arquivos do diff. A rule **não** está violada (a chamada está nos três painéis, que é justamente o que ela exige), mas não tem linha na tabela — L4 |

  O `01:451` escreve, de próprio punho, *"**Esse fato não estava na ADR-03.**"* — o desvio foi visto,
  registrado no arquivo vizinho e **não** corrigido na fonte. É o mesmo padrão que o QA-02 do ciclo 1
  apontou, repetido no artefato seguinte.
- **Repro**:
  1. `git diff a95d2f0..HEAD -- wikis/specs/feat/layout-compact/layout-compact/02-decisoes-arquiteturais.md` → **vazio**
  2. `git diff a95d2f0..HEAD --stat -- app/` → `11 files changed, 515 insertions(+)`, três deles `app/Providers/Filament/*PanelProvider.php`
  3. `grep -n "o mecanismo inteiro é" 01-plano-acao.md` → `:462`
  4. `sed -n '11p' vendor/filament/filament/src/Panel/Concerns/HasSidebar.php` → `protected string | Closure $sidebarWidth = '20rem';`
- **Destino**: 1 — especificação/wiki
- **Ação exigida**: reconciliar o `02` (ADR-03 § Decisão e § Consequências; ADR-06 § Decisão e
  § Riscos) com o segundo mecanismo, marcando `*(alterado em …)*`; corrigir `01:462` e
  `CHANGELOG.md:22`; acrescentar a linha de `providers-filament.md` à tabela do `03`.

#### QA-08 — A correção do QA-03 criou três citações novas erradas, e declarou "36/36 ok" · Minor · destino 1

- **Dimensão**: L (L2)
- **Relacionado a**: QA-03 do ciclo 1 · `.ai/rules/specs.md` § *Citação de vendor se confere por
  símbolo, nunca por número de linha* · commit `9c52204`
- **Esperado**: `{path}:{símbolo}:{linha}` aponta para **a declaração** do símbolo. No ciclo 1 essas
  três citações estavam **certas** (`:183` e `:189`); foi a remediação que as quebrou.
- **Observado**:

  | Citação | Onde | A linha citada contém | Declaração real |
  |---|---|---|---|
  | `app/Support/DensidadeDoLayout.php:coagir():219` | `01:308` | `return self::coagir(config('kit.densidade_do_layout'));` — corpo de `deConfig()` | **236** |
  | `app/Support/DensidadeDoLayout.php:padrao():238` | `01:312` | `... ?? self::padrao();` — corpo de `coagir()` | **242** |
  | `app/Support/DensidadeDoLayout.php:coagir():219` | `03:456` | idem da primeira | **236** |

  As três **passam** pela conferência que a rule prescreve (*"verifique se `sed -n "{linha}p"` contém
  o símbolo"*), porque o nome aparece na linha como **chamada**. O furo é da conferência, não do
  leitor: quem seguir a citação cai 17 e 4 linhas antes, dentro de outro método — exatamente o tipo
  de erro que o QA-03 chamou de *"símbolo errado, não só linha"*.
  Consequência: `01:489` e `03:348` declaram **"36/36 ok"**, e `03:383` registra `specs.md` como
  *"aplicada — toda afirmação sobre vendor tem `arquivo:símbolo:linha` conferido"*.
- **Repro** — varredura própria, com padrão que cobre `arquivo:simbolo():linha` **com parênteses**,
  sobre `00`–`06`, `docs/pt`, `docs/en` e `wikis/roadmap.md`: 97 casamentos; para cada símbolo de
  função, exigir `function <nome>` na linha citada.
  - 3 apontam para **chamada** em vez de declaração — as da tabela acima
  - 2 são ponto-de-chamada **deliberado e declarado** (`base.blade.php:STYLES_BEFORE:44` e
    `KitServiceProvider.php:aplicarNaConfig():355`, este com a nota inline) — **não** são achado

  Conferência manual: `grep -n "function coagir\|function padrao" app/Support/DensidadeDoLayout.php`
  → `236`, `242`.
- **Destino**: 1
- **Ação exigida**: corrigir as três e refazer a contagem; e trocar a conferência por uma que exija
  `function <símbolo>` na linha citada, em vez da mera presença do nome.

#### QA-09 — Os contadores do QA-04 fecharam pela metade, e o `04` se contradiz em duas linhas vizinhas · Minor · destino 1

- **Dimensão**: L (L1)
- **Relacionado a**: QA-04 do ciclo 1 — a parte de **cenário** fechou; a de contagem/índice, não
- **Esperado**: cabeçalho, nota explicativa, rodapé do Índice e tabela do gate concordando entre si
  e com o runner.
- **Medido**: `grep -c "^it(" tests/Kit/DensidadeDoLayoutTest.php` → **16** ·
  `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --compact` → **30 casos / 66 asserções** ·
  CT-15 vive em `tests/Kit/SiteDeDocumentacaoTest.php` (**+1 caso**) → **17 cenários, 31 casos**.

  | Onde | Diz | Real |
  |---|---|---|
  | `04:43-44` | *"o arquivo tem **14** `it()`"*, *"**25** casos"*; datasets CT-03/CT-10/CT-12 | 16 `it()`, 30 casos; falta CT-17, que tem 4 exemplos |
  | `04:715` | *"**14 cenários, 25 casos executados.** **Todos** em `tests/Kit/DensidadeDoLayoutTest.php`"* | 17 / 31 — e a linha `04:711`, **imediatamente acima**, diz que CT-15 vive em outro arquivo |
  | `04:725` | `Mutantes previstos: **30**` | `04:34` já diz **34** |
  | `04:34` | *"17 (30 casos executados)"* | 30 é a contagem de **um** arquivo; com CT-15 são 31. É a mesma conflação do ciclo 1, com números novos |
  | `04:344` | *"medido: **7 dos 14 CTs** reprovam"* | remedido para **10 dos 30** em `01:480` e `03:343`; esta célula não acompanhou |
  | `03:17` | *"**14 CTs, 25 casos**, 47 asserções"* | 16 / 30 / 66 — o `01:362` e o `01:471` foram corrigidos; a linha 5 da tabela `## Estado` do `03`, não |
- **Repro**: os dois comandos acima, mais
  `grep -n "25 casos\|14 CTs\|Mutantes previstos" 03-progresso.md 04-casos-de-teste.md`
- **Destino**: 1
- **Ação exigida**: recontar nos seis pontos, separando **cenários do conjunto** (17) de **casos por
  arquivo** (30 + 1) — ou remover a contagem manual onde ela não paga a manutenção.

#### QA-10 — O menu colapsado ficou fora da escala, e a lista de "o que não aperta" não o cita · Minor · destino 1

- **Dimensão**: A (invariante do `00`) + L (L5)
- **Relacionado a**: RQ-05, invariante do `00`, QA-01 do ciclo 1
- **Esperado**: a decisão do ciclo 1 estabeleceu que **largura é atributo do menu** e por isso entra
  na escala — *"nenhuma superfície fica parcialmente compacta"*. O menu tem **dois** estados de
  largura, e os dois são alcançáveis: `->sidebarCollapsibleOnDesktop()` está ligado nos três painéis.
- **Observado**: `--collapsed-sidebar-width` fica em **`4.5rem` (72 px) nos três níveis**. O valor é
  o default do vendor e **nada no kit o sobrescreve** — exatamente a situação em que `--sidebar-width`
  estava antes do ciclo 1. As docs pt/en e o `wikis/roadmap.md` § 5 listam **duas** superfícies que
  não apertam (topbar e cartões do Pulse); esta seria a terceira, ou entra na escala.
- **Repro** (estática e conclusiva — é literal sem override):
  1. `grep -rn "collapsedSidebarWidth" app/ resources/` → só `badgeOnCollapsedSidebar()`, que é outra
     coisa; **nenhum** `->collapsedSidebarWidth(...)`
  2. `grep -n "collapsedSidebarWidth" vendor/filament/filament/src/Panel/Concerns/HasSidebar.php` →
     `:13 protected string | Closure $collapsedSidebarWidth = '4.5rem';` e o getter que o avalia
  3. `sed -n '86p' vendor/filament/filament/resources/views/components/layout/base.blade.php` →
     `--collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};`
- **Destino**: 1 — decisão do usuário, como foi a do QA-01: ou o rail colapsado entra na escala, ou
  vira a terceira linha da lista de "não aperta", nas docs e no roadmap
- **Ação exigida**: fechar a decisão num Adendo do `00` ou numa ADR, e refletir nas docs pt/en e no
  `wikis/roadmap.md` § 5.
- **Nota de severidade**: classificado **Minor**, e não Major por paridade com o QA-01, porque o rail
  colapsado é estado **secundário e opcional** (o usuário precisa colapsar), é *icon-only* — não há
  rótulo para apertar — e a correção documental custa uma linha. Um revisor que leia o invariante ao
  pé da letra pode chamá-lo de Major; fica registrado para a decisão ser do usuário, não do gate.

### Hipóteses Rejeitadas

Registradas com o motivo, porque custaram o mesmo que os achados.

| Hipótese | Resultado | Evidência |
|---|---|---|
| **CT-17 lê um valor congelado**, não a avaliação do `Closure` | **rejeitada** | `HasSidebar.php:getSidebarWidth():68` faz `evaluate($this->sidebarWidth)`. Confirmado em processo único: `php artisan tinker --execute` trocando `config('kit.densidade_do_layout')` entre leituras devolve `20rem / 17rem / 16.5rem / 20rem` (ilegível) nos **três** painéis. Valor fixo reprovaria 3 das 4 linhas do dataset |
| **Os três painéis não estão cobertos** | **rejeitada** | `grep -rn "sidebarWidth" app/` → `AdminPanelProvider:117`, `AppPanelProvider:128`, `InfraPanelProvider:138`; CT-17 itera `['app','admin','infra']` com `toBe()` |
| **A largura não aperta de verdade na tela** | **rejeitada** | Playwright 1.63.0 + Chromium, 1600×1000, `/admin` e `/admin/users` autenticado: menu **320,0 / 272,0 / 264,0 px**; item **40,0 / 32,8 / 31,2 px**; linha da tabela **56,0 / 46,4 / 42,9 px**; topbar **64 px** nos três (coerente com o documentado); **0 rótulos truncados** (`scrollWidth > clientWidth`) e `estouraHorizontal: false`. O limiar confere: no denso o rótulo mais longo — *"Configurações da aplicação"* — mede **187,2 px** numa caixa de **190 px**, 2,8 px de folga; em `16rem` a caixa seria 182 px, os ~5 px que o docblock declara |
| **`git check-attr` teria o mesmo falso negativo do regex** | **rejeitada** | repositório de prova no scratchpad: `wikis/** export-ignore`, `*.md export-ignore`, `wikis/*.md export-ignore` e `/wikis/roadmap.md export-ignore` devolvem **todos** `set`; sem regra, `unspecified`. Os dois padrões que o regex deixava passar agora reprovam — **QA-05 fechado** |
| **QA-06 continuaria divergente entre pt e en** | **rejeitada** | as duas línguas dizem a mesma coisa, com o mesmo número de itens, e o `wikis/roadmap.md` § 5 tem as mesmas duas linhas — **fechado** |
| **QA-01 continuaria aberto** | **rejeitada** | ver a medição acima — **fechado** |
| **A parte de cenário do QA-04 continuaria aberta** | **rejeitada** | CT-15, CT-16 e CT-17 têm Gherkin com `Exemplos`; o Índice atribui CT-16 → **R8** e CT-17 → **R1**, e a coluna *Camada* está preenchida. Sobrou a contagem — QA-09 |

### Dimensões — Ciclo 2

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | 1 achado (QA-10). QA-01 fechado e medido no navegador |
| B | Fronteiras e dados | ✅ | `larguraDaSidebar()` é `match` total sobre os três casos do enum, sem `default` e sem entrada externa — o valor já chega coagido por `deConfig()` |
| C | Matriz de permissão | ⏭️ pulada | nenhuma célula nova no diff do ciclo 2: sem rota, policy ou ação |
| D | Observabilidade real | ✅ | `git diff a95d2f0..HEAD -- app/ \| grep -c "Log::"` → **0**. Sem log, e portanto sem PII em log |
| E | Performance | ✅ | uma chamada a `DensidadeDoLayout::deConfig()` por render de layout, lendo `config()` já em memória. Zero query nova |
| F | UX de erro | ✅ | nenhuma superfície de mensagem nova |
| G | Tema e cor | ⏭️ pulada | superfície de cor **nula** no diff do ciclo 2: `git diff a95d2f0..HEAD -- app/ resources/ \| grep -E "bg-\|text-(white\|black\|gray)\|border-\|#hex"` → vazio |
| H | Acessibilidade | ⚠️ parcial | verificado o recorte que a largura afeta: **0 rótulo truncado** nos três níveis e sem scroll horizontal. Tab order e axe não rodados — ver *Não Verificado* |
| I | Segurança da superfície nova | ✅ | `larguraDaSidebar()` devolve **literal** de um `match`; nada vindo do request alcança o `<style>` inline. Nenhuma propriedade Livewire, rota ou método público novo |
| J | Regressão adjacente | ✅ | **2.721 / 10.537 / 0 falhas**, reproduzida pelo gate |
| K | Adequação da suíte | ✅ | passo estático **limpo** nos dois testes novos: CT-17 usa `toBe()` com valor esperado por nível e por painel; a guarda de `export-ignore` virou `git check-attr`, com o oráculo verificado em repositório de prova. Passo medido continua em *Não Verificado* |
| L | Consistência documental | ❌ | 3 achados — QA-07, QA-08, QA-09 |

### Não Verificado — Ciclo 2

- **Dimensão K, passo medido (mutation score)** — `php -m` não lista **PCOV nem Xdebug**, e
  `--mutate` aborta com `Mutation testing requires code coverage to be enabled`. A falsificabilidade
  declarada no `04` (M34 morto ao remover `sidebarWidth()` do `InfraPanelProvider`) **não foi
  reproduzida**: reproduzi-la exigiria mutar código de aplicação, que esta skill proíbe.
- **Acessibilidade completa** (axe, tab order, foco) — fora do perfil `padrão`; verificado só o
  recorte que a largura nova afeta.
- **Rail colapsado medido em pixel** — QA-10 está provado **estaticamente** (literal do vendor sem
  override); a medição do estado colapsado no navegador não foi feita.
- **`pest --tia`** — não rodado; a regressão completa cobriu o mesmo escopo com mais folga.
- **Dado de desenvolvimento** — a medição de uma sessão anterior deixou **14 usuários
  `medicao*@example.com`** no banco de desenvolvimento. Não é defeito do produto (destino 4, infra)
  e o gate não os tocou. Para medir, o gate trocou a senha de `admin@example.com` e **restaurou o
  hash original** ao fim (verificado), devolvendo a densidade a `confortavel` e derrubando o
  `artisan serve`.

---

## Ciclo 3 — o teto da skill

> Escopo: verificar o **fechamento** dos quatro achados do ciclo 2 e tratar como **diff não
> revisado** tudo que entrou depois dele (commit `da93083`, documentação apenas). Os dez achados
> dos ciclos 1 e 2 **não** são re-reportados; o que está abaixo é achado **novo**, ou fechamento
> que não fechou.
>
> **Este é o terceiro ciclo, e a skill não permite um quarto.** Achado Blocker ou Major aqui
> **escala ao usuário** — não abre novo ciclo.

### Veredito — Ciclo 3

**REPROVADO → especificação · ESCALADO AO USUÁRIO (teto de 3 ciclos atingido)**

- Blocker: 0 · Major: **1** · Minor: **3** · Cosmético: 0
- Ambiente: app **não servido** (o diff do ciclo 3 é texto; nada novo para observar em tela) ·
  PHP 8.4.25 · Pest 5.0.5 · Playwright e MCP **não usados neste ciclo**
- Regressão reproduzida pelo gate: `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
  → **2.721 passaram, 10.537 asserções, 0 falhas** — idêntico ao ciclo 2
- `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --compact` → **30 passaram, 66 asserções**
- `vendor/bin/pest tests/Kit/CitacoesDeCodigoTest.php --compact` → **3 passaram** — e é justamente
  esse verde que o QA-14 põe em dúvida

**Os quatro do ciclo 2 fecharam** — QA-07, QA-08, QA-09 e QA-10; a prova de cada um está em
`## Hipóteses Rejeitadas — Ciclo 3`. O que sobrou tem, de novo, a mesma origem: **a remediação
produziu texto novo que ninguém releu**. O Major não é defeito de código — é uma **garantia falsa**
escrita na ADR sobre o que o teste protege, e ela foi medida como falsa.

### Achados

#### QA-11 — A ADR-03 promete que CT-17 fica vermelho se o vendor mudar o default; medido, ele fica **verde** · Major · destino 1

- **Dimensão**: L (L3 — ADR × código), com origem em K (o contrato não tem matador)
- **Relacionado a**: ADR-03 § *Revisão de 2026-09-21*, CT-17, QA-07 do ciclo 2 (foi a remediação
  dele que escreveu a frase)
- **Esperado**: `02:126-129` afirma, de próprio punho, que o contrato *"o confortável do kit é
  idêntico ao kit sem a feature"* está guardado — *"Se o Filament mudar esse default, o confortável
  do kit deixa de ser idêntico ao kit sem a feature — que é o contrato da ADR. **CT-17 fica
  vermelho** nesse dia, porque afirma o valor literal."*
- **Observado**: CT-17 compara o **retorno do kit** com um **literal do kit**. Os dois lados da
  asserção são `'20rem'` escritos no kit (`DensidadeDoLayout.php:larguraDaSidebar():201` e o
  dataset em `tests/Kit/DensidadeDoLayoutTest.php:479`). O default do vendor **não entra na
  comparação em ponto nenhum**, e como os três painéis chamam `->sidebarWidth()`, ele é
  inalcançável em runtime. Resultado: o dia em que o Filament mudar o default, **nada fica
  vermelho** — o contrato quebra em silêncio, com a ADR dizendo que não quebraria.
- **Repro** — mutação no vendor, com restauração conferida por hash:
  1. `md5sum vendor/filament/filament/src/Panel/Concerns/HasSidebar.php` → `9add012f…`
  2. `sed -i "11s/'20rem'/'18rem'/"` no mesmo arquivo — é exatamente o evento que a ADR descreve
  3. `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --filter="a largura do menu acompanha o nivel nos tres paineis" --compact`
     → **`passed`, 4 casos, 12 asserções**. A ADR previa vermelho
  4. Arquivo restaurado; `md5sum` volta a `9add012f…` e `sed -n '11p'` volta a `'20rem'`
- **Evidência**: a mutação acima; `sed -n '52,56p;66,70p'` do mesmo arquivo confirma que
  `sidebarWidth()` (`:54`) grava e `getSidebarWidth()` (`:68`) faz `evaluate()` do que foi gravado —
  o default só valeria se ninguém chamasse o setter
- **Destino**: 1 — especificação. A frase da ADR é a única coisa errada; o código está certo
- **Ação exigida**: corrigir `02:126-129` (o contrato **não** tem guarda hoje) e decidir se ele
  merece uma: o CT que de fato o guarda precisa **ler o default do vendor** — por reflexão sobre
  `HasSidebar::$sidebarWidth` ou por `Panel` sem `sidebarWidth()` chamado — e compará-lo com
  `larguraDaSidebar()` do confortável. Isso é derivação nova, destino 3, e sai da
  `feature-test-design`, não daqui
- **Nota**: o mesmo texto aparece como comentário em `tests/Kit/DensidadeDoLayoutTest.php:478`
  (*"`20rem` é o default do vendor — o confortável não muda nada"*). Ali é descrição de intenção e
  está correta; o que não existe é a asserção que a sustente

#### QA-12 — A correção do QA-09 deixou a nota de contagem aritmeticamente impossível, e dois pontos com o número velho · Minor · destino 1

- **Dimensão**: L (L1)
- **Relacionado a**: QA-09 do ciclo 2 · commit `da93083`
- **Esperado**: 16 `it()`, **4** datasets (CT-03 4 exemplos, CT-10 7, CT-12 3, CT-17 4) → 12 casos
  simples + 18 de dataset = **30** casos no arquivo, **31** com CT-15
- **Observado**:

  | Onde | Diz | Real |
  |---|---|---|
  | `04:43-44` | *"o arquivo tem 16 `it()`. **Quatro** deles têm dataset — CT-03 (4 exemplos), CT-10 (7) e CT-12 (3) —, e é daí que saem os **25** casos"* | diz "quatro" e **lista três**; e 12 + 14 nunca deu 25 nem 30. Falta CT-17 na lista, e o **25** é o número de antes do QA-09 |
  | `04:728` | linha M12 do *Gate de falsificabilidade*: *"**7 dos 14 CTs reprovam**"* | `04:344`, corrigido no mesmo commit, diz **10 dos 30 casos** — o arquivo se contradiz de novo, em outro par de linhas |
  | `01:261` | *"a mutação confirmou que apagar a linha reprova **7 dos 14 casos**"* | `01:483`, no mesmo arquivo, já diz **10 dos 30** |

  A linha `04:43-44` é a mais grave das três porque **foi editada pela remediação** (`14 → 16`,
  `Três → Quatro`) e saiu pior: antes era coerente e velha, agora é incoerente consigo mesma.
- **Repro**:
  1. `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --compact` → `30 passed, 66 assertions`
  2. `grep -c "^it(" tests/Kit/DensidadeDoLayoutTest.php` → **16** ·
     `grep -n "})->with(\[" tests/Kit/DensidadeDoLayoutTest.php` → **4** ocorrências (`:116`, `:284`,
     `:327`, `:477`)
  3. `grep -n "7 dos 14\|25 casos" 01-plano-acao.md 04-casos-de-teste.md`
- **Destino**: 1
- **Ação exigida**: corrigir os três pontos — ou, como o ciclo 2 já sugeriu, **remover a contagem
  manual** onde ela não paga a manutenção. Três ciclos seguidos de achado no mesmo lugar são o
  argumento a favor de remover

#### QA-13 — As docs pt/en passaram a listar três coisas que não apertam, e a frase seguinte continua dizendo "as duas" · Minor · destino 1

- **Dimensão**: L (L5)
- **Relacionado a**: QA-06 do ciclo 1, QA-10 do ciclo 2 · commit `da93083`
- **Esperado**: o parágrafo enumera três superfícies e a frase de fecho as retoma
- **Observado**: nas **duas** línguas, a contagem foi atualizada na abertura e não no fecho:
  - `docs/pt/recursos/configuracoes-do-kit.md:116` — *"**Três** coisas não apertam…"*; `:119` —
    *"**As duas** estão no roadmap do kit."*
  - `docs/en/recursos/configuracoes-do-kit.md:117` — *"**Three** things do not tighten…"*; `:121` —
    *"**Both** are on the kit roadmap."*

  E as três **estão** mesmo no roadmap: `wikis/roadmap.md` § 5 ganhou a linha do rail no mesmo
  commit. O número é que ficou para trás. Não é divergência pt × en — as duas línguas erram igual,
  o que confirma que a frase foi traduzida e não relida.
- **Repro**: `grep -n "As duas estão\|Both are on" docs/pt/recursos/configuracoes-do-kit.md docs/en/recursos/configuracoes-do-kit.md`
- **Destino**: 1
- **Ação exigida**: "As três" / "All three", nas duas línguas. Vale conferir se o fecho deve mesmo
  agrupar o rail com as outras duas: o roadmap o separa como *"escolha, não limitação"*

#### QA-14 — A largura do menu empurrou o `InfraPanelProvider` em 14 linhas e quebrou uma citação em doc de usuário; a guarda `[CT-26]` não a vê · Minor · destino 1 (+ 3)

- **Dimensão**: L (L2), com um item de K
- **Relacionado a**: `.ai/rules/specs.md:48` — *"Vale para citação em QUALQUER arquivo, não só na
  wiki"* · `03:348` (*"36/36 ok"*) · commit `ec665a5`
- **Esperado**: citação `{path}:{símbolo}:{linha}` aponta para a declaração. A reverificação do
  step 7 varreu as citações **da wiki para fora**; faltou a direção oposta — as citações **de fora
  para dentro** dos arquivos que o diff deslocou
- **Observado**: `->models([` do `RevivePlugin` estava em `:581` no `main` e está em **`:595`**
  hoje, empurrado pelas 14 linhas que `->sidebarWidth()` acrescentou ao `InfraPanelProvider`. Duas
  docs de usuário continuam citando `:581`, que hoje contém `->navigationGroup('Sistema')`:
  - `docs/pt/recursos/trilhas-de-infraestrutura.md:51`
  - `docs/en/recursos/trilhas-de-infraestrutura.md:51`

  **E a guarda que existe para isso fica verde.** `tests/Kit/CitacoesDeCodigoTest.php` `[CT-26]`
  varre `docs/`, mas descarta a citação em `:200` — `! is_file(base_path($caminho))` —, e o caminho
  aqui é só o basename (`InfraPanelProvider.php`), que não resolve a partir da raiz. Medido na
  superfície viva que o caso varre: **38 citações com símbolo, 30 conferidas, 8 puladas**, e o piso
  do caso é `toBeGreaterThan(5)` (`:223`) — folgado demais para acusar a perda. Das 8 puladas, 5 são
  elisão de vendor (descarte **declarado** no docblock) e 3 são arquivo do kit citado por basename,
  que poderia resolver.
- **Repro**:
  1. `git show main:app/Providers/Filament/InfraPanelProvider.php | grep -n "\->models(\["` → **581**
  2. `grep -n "\->models(\[" app/Providers/Filament/InfraPanelProvider.php` → **595**
  3. `sed -n '581p' app/Providers/Filament/InfraPanelProvider.php` → `->navigationGroup('Sistema')`
  4. `vendor/bin/pest tests/Kit/CitacoesDeCodigoTest.php --compact` → **3 passaram**
- **Destino**: 1 para as duas citações. A guarda é **destino 3**: o descarte silencioso de caminho
  não-resolvível é a lacuna de derivação, e fechá-la pede caso novo pela `feature-test-design`
- **Ação exigida**: corrigir `:581` → `:595` nas duas docs; e decidir se `[CT-26]` passa a resolver
  basename por busca no repo (e a **contar** quantas pulou, em vez de pular calado)
- **Fora de escopo, registrado**: `site-vitepress/pt|en/recursos/trilhas-de-infraestrutura.md:51`
  repetem a citação, mas aquele espelho já está defasado desde a `v0.35.0` (diz `kit 0.35.0` onde o
  `docs/` diz `0.37.1`) e não é varrido por `[CT-26]`. Não é defeito desta feature

### Hipóteses Rejeitadas — Ciclo 3

| Hipótese | Resultado | Evidência |
|---|---|---|
| **QA-07 continuaria aberto** | **rejeitada — fechado** | ADR-03 ganhou § *Revisão de 2026-09-21* com a tabela dos dois caminhos e corrigiu a consequência do "não referencia nada do vendor"; ADR-06 ganhou a terceira linha (`sidebarWidth(Closure)`) com nota datada; o `01` § Filosofia e o `CHANGELOG.md` não dizem mais "uma declaração CSS"; `03:380` tem a linha de `providers-filament.md`. **Mas a correção da ADR trouxe o QA-11** |
| **QA-08 continuaria aberto** | **rejeitada — fechado** | varredura própria com padrão que cobre `simbolo():linha` **com parênteses** sobre `00`–`06`, `docs/pt`, `docs/en`, `wikis/*.md`, `CHANGELOG` e os dois READMEs: **53 citações**, e **nenhuma** das citações vivas da wiki aponta para chamada. `coagir():236` e `padrao():242` conferidos por `function <símbolo>` na linha. As 5 suspeitas restantes em `06` são o próprio relatório **citando** as citações erradas dos ciclos 1 e 2, e `02:272` é ponto-de-chamada deliberado com nota inline — ambos já classificados |
| **QA-09 continuaria aberto** | **rejeitada — fechado em 4 dos 6 pontos** | `04:34`, `04:715`, `04:729`, `04:344` e `03:17` recontados e corretos. Os que sobraram viraram QA-12 |
| **QA-10 continuaria aberto** | **rejeitada — fechado** | `wikis/roadmap.md` § 5 tem a linha do rail **e** um parágrafo declarando que ele é *"o único item desta página que está fora por escolha, e não por limitação… fica registrado com o motivo para não ser reaberto como se fosse esquecimento"*. ADR-03 repete a decisão. Docs pt/en passaram a três — com o defeito de fecho do QA-13 |
| **O roadmap inventa a medição do ícone** (*"24 → 19,2 → 16,8 px"*) | **rejeitada** | é aritmética verificável, não estimativa: o ícone do Filament é `size-6` = `6 × --spacing`, e `larguraDaSidebar()`/`espacamento()` fixam `.25rem / 0.2rem / 0.175rem` (`DensidadeDoLayout.php:espacamento():148`) → `1.5rem / 1.2rem / 1.05rem` = **24 / 19,2 / 16,8 px** |
| **As citações novas da ADR-06 estariam erradas** | **rejeitada** | `HasSidebar.php:54` é `public function sidebarWidth(...)`, `:68` é `getSidebarWidth()` com `evaluate()`, `:11` é o default `'20rem'`, `base.blade.php:85` emite `--sidebar-width`. Quatro conferidas, quatro certas |
| **O diff do ciclo 3 mexeria em código ou teste** | **rejeitada** | `git diff 9cc1be2..HEAD --stat` → 8 arquivos, **todos `.md`**: CHANGELOG, docs pt/en, roadmap e `01`/`02`/`03`/`04`. Nenhuma linha de `app/`, `tests/`, `config/` ou `database/` |

### Dimensões — Ciclo 3

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | as dez `RQ` têm rastro; RQ-05 fechou com QA-01 e a decisão do QA-10 registrada como **escolha**. Nenhuma lacuna nova |
| B | Fronteiras e dados | ⏭️ pulada | **motivo**: o diff do ciclo 3 não tem código. Reconfirmada indiretamente pela regressão |
| C | Matriz de permissão | ⏭️ pulada | **motivo**: nenhuma célula nova — sem rota, policy ou ação no diff |
| D | Observabilidade real | ⏭️ pulada | **motivo**: sem código novo. `git diff 9cc1be2..HEAD -- app/` → vazio; a ausência de log continua sendo decisão declarada |
| E | Performance | ⏭️ pulada | **motivo**: sem código novo |
| F | UX de erro | ⏭️ pulada | **motivo**: nenhuma superfície de mensagem no diff |
| G | Tema e cor | ⏭️ pulada | **motivo**: superfície de cor nula — o diff é texto |
| H | Acessibilidade | ⏭️ pulada | **motivo**: sem elemento novo. O recorte medido no ciclo 2 (0 rótulo truncado) continua valendo |
| I | Segurança da superfície nova | ⏭️ pulada | **motivo**: nenhuma rota, propriedade Livewire ou método público no diff |
| J | Regressão adjacente | ✅ | **2.721 / 10.537 / 0 falhas**, reproduzida pelo gate neste ciclo |
| K | Adequação da suíte | ⚠️ | passo estático: nenhum teste novo no diff. **Um achado por outro caminho** — o contrato da ADR-03 sem matador (QA-11, provado por mutação no vendor) e o descarte silencioso do `[CT-26]` (QA-14). Passo medido continua em *Não Verificado* |
| L | Consistência documental | ❌ | 4 achados — QA-11, QA-12, QA-13, QA-14 |

### Não Verificado — Ciclo 3

- **Dimensão K, passo medido (mutation score)** — `php -m` continua sem **PCOV e sem Xdebug**, e
  `--mutate` aborta com `Mutation testing requires code coverage to be enabled`. A mutação do QA-11
  foi feita **à mão no vendor**, com restauração conferida por `md5sum`, justamente porque o
  caminho automático não existe nesta máquina.
- **Validação em tela** — app **não servido** neste ciclo: o diff é texto, e nada do que ele mudou
  se observa em navegador. As medições de pixel do ciclo 2 não foram refeitas.
- **`pest --tia`** — não rodado; a regressão completa cobre o mesmo escopo com mais folga.
- **`site-vitepress/`** — espelho defasado por decisão anterior ao escopo desta feature; varrido só
  para registrar que a citação do QA-14 se repete lá.

---

## Revalidação dirigida do QA-11

> **Não é um ciclo 4.** O teto de três ciclos foi atingido e o QA-11 escalou ao usuário. Esta
> seção verifica **quatro afirmações** sobre a remediação feita depois do veredito (commit
> `5740ebc`, diff `47e7fec..HEAD`), por execução, e nada mais. As 12 dimensões **não** foram
> rodadas.
>
> Ambiente: worktree `starter-kit-easy-compact`, branch `feat/layout-compact` · `vendor/bin/pest`
> (sem `composer`) · app **não servido** · Playwright **não usado**.

### Veredito da revalidação

**Afirmações 1, 2 e 3: confirmadas. Afirmação 4: derrubada.**

O código e o teste estão certos — a guarda é real, não é circular, e mata o mutante que a ADR
descreve. O que não fecha é o **texto que veio junto**: a remediação acrescentou um CT e deixou
sete contadores para trás em quatro arquivos, não derivou o CT no `04`, e não registrou nada no
`03`. É a **quarta** repetição seguida da mesma família (QA-04 → QA-09 → QA-12 → QA-16), e o
achado de derivação (QA-15) é da mesma classe que o ciclo 1 classificou como **Major**.

**Não vai para PR como está.** Os três achados abaixo são texto, são baratos, e nenhum deles pede
tocar em código ou em teste.

### Afirmação 1 — existe uma guarda real do contrato · **CONFIRMADA**

`[CT-18]` (`tests/Kit/DensidadeDoLayoutTest.php:516-525`) lê o default do vendor por
`(new ReflectionClass(Panel::class))->getDefaultProperties()['sidebarWidth']` e o compara com
`DensidadeDoLayout::Confortavel->larguraDaSidebar()`.

**Não é circular, e isso foi medido, não deduzido.** `getDefaultProperties()` devolve o valor
**declarado** na propriedade do trait (`Panel` usa `Panel\Concerns\HasSidebar` em
`vendor/filament/filament/src/Panel.php:41`), não o estado de um painel resolvido. Os três
painéis do kit chamam `->sidebarWidth(Closure)` — `AdminPanelProvider.php:117`,
`AppPanelProvider.php:128`, `InfraPanelProvider.php:138` —, então perguntar a
`Filament::getPanel($x)->getSidebarWidth()` devolveria o valor que o **próprio kit** setou, e a
asserção não valeria nada. A prova de que a reflexão de fato alcança o vendor está na afirmação 2:
sob o mutante, o lado *esperado* da falha veio `'18rem'`, que é o literal do vendor mutado — se a
leitura fosse do painel, teria vindo `'20rem'` dos dois lados e o caso ficaria verde.

A primeira asserção do caso (`toBeString`) cobre o sumiço da propriedade: sem ela o contrato
perderia a referência em silêncio, e agora falha com mensagem própria.

**Limitação, declarada e não um defeito**: CT-18 guarda a **propriedade**. Um Filament que
mantivesse `$sidebarWidth = '20rem'` e mudasse o `getSidebarWidth()` passaria por baixo. A ADR não
afirma o contrário — ela diz "por reflexão sobre a propriedade", que é exatamente o que o código
faz.

### Afirmação 2 — a guarda mata o mutante · **CONFIRMADA**

Mutação à mão no vendor, sem suíte de fundo na árvore, com restauração conferida por hash:

| Passo | Comando | Resultado |
|---|---|---|
| 1 | `md5sum vendor/filament/filament/src/Panel/Concerns/HasSidebar.php` | `9add012f96c1d5de34baf38386790c1f` |
| 2 | backup em scratchpad, `md5sum` do backup | idêntico ao original |
| 3 | `sed -i "11s/'20rem'/'18rem'/"` · `sed -n '11p'` | a propriedade passa a `'18rem'` · md5 `3ba7afe2fbe063b5994c7b51c4fa7f7a` |
| 4 | `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php` | **`failed` · tests: 31 · passed: 30 · failed: 1** |
| 5 | a **única** falha | `[CT-18]` — *"Failed asserting that two strings are identical. — `'18rem'` / `'20rem'`"*, com a mensagem da ADR-03 |
| 6 | CT-17 (4 casos de dataset) | **verde**, dentro dos 30 que passaram |
| 7 | restauração do backup · `md5sum` · `sed -n '11p'` | **`9add012f96c1d5de34baf38386790c1f`** · de volta a `'20rem'` |
| 8 | reexecução na árvore restaurada | **`passed` · 31 testes · 68 asserções** |

É o controle que faltava no ciclo 3: o mesmo evento que deixava **tudo verde** agora deixa
**exatamente um caso vermelho**, e é o caso escrito para isso. A ADR-03 estava errada e a guarda
nova é necessária — as duas metades ficam provadas pelo mesmo experimento.

**Alcance do "nenhum outro caso o veria mudar"** (afirmação da ADR): verificado por
`grep -rn "sidebarWidth|sidebar-width|20rem|17rem|16.5rem" tests/` → **zero ocorrências** fora de
`DensidadeDoLayoutTest.php`. Some a isso os 30 verdes do passo 4. A suíte completa (2.721) **não**
foi rodada sob o mutante — a varredura torna o resultado estrutural, não amostral.

### Afirmação 3 — o texto da ADR-03 diz a verdade · **CONFIRMADA, com uma ressalva**

`02-decisoes-arquiteturais.md:128-144`. Conferido frase a frase contra o que foi medido:

| O que a ADR afirma | Veredito |
|---|---|
| *"trocando `'20rem'` por `'18rem'` em `HasSidebar.php:11`, CT-17 passa"* | **verdade** — passo 6 acima |
| *"os três painéis chamam `->sidebarWidth()`, o default fica inalcançável em runtime"* | **verdade** — as três chamadas conferidas por `grep` |
| *"CT-18 … lê o default do vendor por reflexão sobre a propriedade — e não perguntando ao painel"* | **verdade** — afirmação 1 |
| *"com o default em `'18rem'`, CT-18 fica vermelho e CT-17 segue verde"* | **verdade** — passos 4-6 |
| `HasSidebar.php:11` | **citação certa** — `sed -n '11p'` é a declaração da propriedade |
| *"É a terceira vez nesta feature que uma correção de texto afirma mais do que o código sustenta"* | **defensável, e não medido** — ver ressalva |

**Ressalva.** A última linha é a única afirmação da seção que não é verificável por execução. Ela
fecha se os três forem *"21/21 ok"* (QA-03), *"36/36 ok"* (QA-08) e *"CT-17 fica vermelho"*
(QA-11) — três asserções sobre o que uma verificação ou um teste pega, todas falsas. Nesse
recorte o número está certo. Mas o commit que a escreve **produz a quarta** (QA-16 e QA-17
abaixo), e a regra que a própria seção deriva — *"afirmação sobre o que um teste pega tem de ser
verificada"* — não foi aplicada ao resto do commit que a introduziu.

Fora isso, **a ADR não afirma mais do que o código sustenta em ponto nenhum.** A frase *"Quem
guarda o contrato é CT-18"* é lida no escopo da seção (o **segundo caminho**, a largura do menu);
a outra metade do contrato — o `<style>` que não é emitido no confortável — continua guardada por
CT-01, CT-04 e CT-12, e a ADR não diz o contrário.

### Afirmação 4 — a correção não abriu buraco novo · **DERRUBADA**

Diff `47e7fec..HEAD`: 8 arquivos, `+79 −11`. Uma única mudança de código-fonte (o CT-18 no arquivo
de teste); o resto é `.md`.

**O que fechou, e está conferido:**

- **QA-13** — `docs/pt/…:119` → *"As três"* e `docs/en/…:121` → *"All three"*. Fechado nas duas
  línguas.
- **QA-14, a parte de citação** — `InfraPanelProvider.php:models:581` → `:595` nas duas docs.
  `sed -n '595p' app/Providers/Filament/InfraPanelProvider.php` devolve a linha do `->models([`.
  **Certa.**
- **QA-12, os três pontos exigidos** — `04:34`, `04:43-46` e `01:261` recontados e corretos
  (17 `it()`, quatro datasets **listados**, 31 casos, 68 asserções — bate com o runner).
- **Varredura de citação própria**, com padrão que cobre `simbolo():linha` **com parênteses** e que
  exige apontar para a **declaração** (`function|const|case|class|trait|enum <símbolo>`,
  propriedade, ou chave de array), resolvendo basename por busca no repo: **232 citações
  conferidas** em `app/`, `tests/Kit/`, `docs/pt`, `docs/en`, `.ai/rules/`, `wikis/*.md` e as wikis
  de spec. **Nenhuma citação nova ou alterada pelo diff está errada.**

**Os três achados novos:**

#### QA-15 — CT-18 entrou no código sem derivação no `04`; M35 é citado e nunca declarado · Major · destino 1 (+ 3)

- **Dimensão**: L (L1) com origem em K · **Relacionado a**: QA-04 do ciclo 1 (**Major**, mesma
  classe), QA-11 · commit `5740ebc`
- **Esperado**: todo CT do arquivo tem, no `04`, cenário em Gherkin, bloco `#### Mutantes
  previstos` e — quando o mutante foi rodado — linha no `### Gate de falsificabilidade`. É o
  padrão que CT-16 e CT-17 seguem, em `04:613-656`.
- **Observado**: `grep -rn "CT-18" wikis/specs/feat/layout-compact/layout-compact/` devolve **três**
  linhas — duas na ADR-03 e **uma** no `04`, a do Índice (`04:719`). Não há cenário, não há bloco de
  mutantes, e **M35 aparece na coluna *Mata* sem existir em lugar nenhum**: é o único mutante do
  documento que nunca é enunciado. A seção que abrigaria o cenário ainda se chama *"Os **dois**
  cenários que os gates acrescentaram"* (`04:613`).
- **E a mutação foi rodada** — a ADR-03 declara o resultado (`'18rem'` → CT-18 vermelho, CT-17
  verde) e esta revalidação o reproduziu. Ela simplesmente não chegou ao `### Gate de
  falsificabilidade`, que é o lugar onde o kit registra mutante morto.
- **Repro**: `grep -rn "CT-18|M35" wikis/specs/feat/layout-compact/layout-compact/`
- **Ação exigida**: derivar CT-18 no `04` como CT-16 e CT-17 foram — Gherkin, M35 enunciado, linha
  no gate com o resultado já medido — e corrigir o título da seção. A derivação sai da
  `feature-test-design`, não daqui.

#### QA-16 — o CT novo deixou sete contadores para trás, em quatro arquivos · Minor · destino 1

- **Dimensão**: L (L1) · **Relacionado a**: QA-04 (ciclo 1), QA-09 (ciclo 2), QA-12 (ciclo 3) —
  **quarta repetição consecutiva** · commit `5740ebc`
- **Real, medido pelo runner**: `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php` →
  **31 passaram, 68 asserções**; `grep -c "^it(" …` → **17**.

  | Onde | Diz | Real |
  |---|---|---|
  | `04:36-39` | *"Os **três** cenários novos entraram depois dos gates: CT-15…, CT-16…, CT-17 (QA-01)"* | são **quatro** — CT-18 entrou pelo QA-11. A mesma nota ainda se data *"Remedido em 2026-09-21"* num bullet editado em 22/09 |
  | `04:721` | *"**17 cenários, 31 casos executados.** **Dezesseis** cenários (30 casos) vivem em `DensidadeDoLayoutTest`"* | contradiz `04:34` (**18** / **32**) e `04:46` (17 `it()` / 31 casos) **no mesmo arquivo** |
  | `04:735` | linha *Mutantes previstos* do gate: **34** | `04:34`, corrigido no mesmo commit, diz **35** |
  | `01:362` | *"**16 CTs, 30 casos** com datasets"* | 17 / 31 |
  | `01:474` | *"**16 CTs, 30 casos**"* | 17 / 31 |
  | `03:17` | *"**16 CTs, 30 casos, 66 asserções**… CT-16 e CT-17 entraram pelos gates"* | 17 / 31 / **68**, e falta CT-18 |
  | `03:343` e `04:735` | *"10 dos **30** casos"* (mutação M12) | a medição é de 21/09 e continua válida, mas o denominador hoje é **31** |

  `04:721` e `04:735` são o defeito **exato** que o QA-12 descreveu — *"o arquivo se contradiz de
  novo, em outro par de linhas"* —, agora em dois pares novos. `01:362` e `01:474` estavam
  **corretos** antes do commit e foram invalidados por ele.
- **Repro**: `grep -n "16 CT|30 casos|Mutantes previstos|cenários" 01-plano-acao.md
  03-progresso.md 04-casos-de-teste.md`
- **Ação exigida**: recontar os sete pontos — ou aceitar a sugestão que o ciclo 2 e o ciclo 3 já
  fizeram e **remover a contagem manual** de onde ela não paga a manutenção. Quatro ciclos
  seguidos de achado no mesmo lugar são o argumento fechado.

#### QA-17 — o `03` não registra a remediação, e a Verificação Final declara uma varredura que não cobre o que mudou depois dela · Minor · destino 1

- **Dimensão**: L (L1/L2) · **Relacionado a**: QA-03 → QA-08 (a mesma classe: declaração de
  verificação mais forte que a medição) · commit `5740ebc`
- **Observado**:
  1. `git diff 47e7fec..HEAD --stat` **não toca `03-progresso.md`**. A tabela do ciclo 3
     (`03:458-461`) segue listando **QA-11, QA-12, QA-13 e QA-14 como abertos**, sem nenhuma linha
     de fechamento — embora três deles tenham sido remediados neste commit. Quem ler o `03` conclui
     que a feature parou reprovada no teto.
  2. `03:348` continua declarando *"**Citações `arquivo:símbolo:linha` reverificadas — 36/36 ok**,
     por varredura própria em **2026-09-21**"*. Duas citações vivas (`docs/pt|en/recursos/
     trilhas-de-infraestrutura.md:51`) foram corrigidas em **2026-09-22**, depois dessa varredura e
     por causa do QA-14. O número e a data ficaram; a frase afirma uma conferência que, por
     construção, não viu o que mudou depois dela.
  3. `03:17` não menciona CT-18 (ver QA-16).
- **Repro**: `git diff 47e7fec..HEAD --stat` · `sed -n '17p;348p;458,461p' 03-progresso.md`
- **Ação exigida**: registrar no `03` o fechamento de QA-11/12/13/14 com a evidência (a mutação
  desta seção serve para o QA-11), incluir CT-18 no passo 5, e **redatar a Verificação Final**
  com a varredura de hoje em vez de manter a de 21/09.

### Hipóteses Rejeitadas — revalidação

| Hipótese | Resultado | Evidência |
|---|---|---|
| **CT-18 seria circular** (leria o painel configurado) | **rejeitada** | sob o mutante, o lado esperado da falha veio `'18rem'` — o literal do vendor. A reflexão alcança a declaração, não o painel |
| **CT-18 ficaria verde sob o mutante** | **rejeitada** | é a **única** falha entre 31 casos |
| **CT-17 ficaria vermelho junto** (o que tornaria CT-18 redundante) | **rejeitada** | os 4 casos de dataset de CT-17 passam sob o mutante — a ADR-03 estava errada, e é por isso que CT-18 precisa existir |
| **O vendor teria ficado sujo** | **rejeitada** | `md5sum` antes e depois: `9add012f96c1d5de34baf38386790c1f` nos dois; `sed -n '11p'` volta a `'20rem'`; suíte volta a 31/68 |
| **A correção do QA-14 teria criado citação nova errada** (foi o padrão do QA-03 → QA-08) | **rejeitada** | `:595` é a linha do `->models([`, conferida nas duas línguas. Varredura própria de 232 citações: nenhuma citação do diff está errada |
| **As docs pt e en divergiriam de novo** | **rejeitada** | *"As três"* / *"All three"*, mesma contagem, mesmo lugar |
| **Os testes de documentação quebrariam com as edições** | **rejeitada** | `CitacoesDeCodigo`, `SiteDeDocumentacao`, `RedeDeDocumentacao` e `ConfiguracoesDoKitDocumentacao` → **85 passaram, 330 asserções** |

### Registrado, fora do escopo desta revalidação

- **O `[CT-26]` confere por `str_contains`, não por declaração.** `.ai/rules/specs.md:46` manda
  verificar *"se `sed -n "{linha}p" {path}` **contém** o símbolo"*, e é isso que o caso implementa —
  então ele está fiel à rule. Mas o critério deixa passar citação que aponta para **chamada** em vez
  de declaração. Achado da varredura própria, em código **vivo** e alheio a esta feature:
  `app/Filament/Admin/Resources/Users/Schemas/UserInfolist.php:50` cita
  `app/Models/User.php:papeisEmQualquerContexto:683`, que é uma **chamada**; a declaração está em
  `:714`. O `[CT-26]` fica verde. É uma segunda lacuna da mesma guarda, independente da que o QA-14
  registrou (descarte silencioso de caminho não-resolvível), e as duas continuam **abertas**. Não é
  defeito desta feature.
- **`InfraPanelProvider.php:models:595`**, a citação corrigida pelo QA-14, é um **ponto de chamada**:
  `models` não é declarado nesse arquivo, é método do `RevivePlugin`. Satisfaz a rule do kit e é o
  único ancoradouro possível — registrado para que uma futura varredura por *declaração* não o leia
  como defeito.
- **Suíte completa não rodada.** Esta revalidação rodou `DensidadeDoLayoutTest` (31/68) e os quatro
  arquivos de documentação (85/330). A regressão de 2.721 do ciclo 3 não foi refeita: o diff tem
  **uma** adição de teste e nenhuma linha de `app/`, `config/` ou `database/`.

### O que falta para o PR

Código e teste estão prontos. Faltam **QA-15** (Major — derivar CT-18 no `04`), **QA-16** (sete
contadores) e **QA-17** (registrar no `03`). Os três são texto, nenhum deles toca código, teste ou
`00-requisito.md`.
