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
