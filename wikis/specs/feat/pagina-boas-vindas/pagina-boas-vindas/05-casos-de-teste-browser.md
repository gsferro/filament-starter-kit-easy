# Casos de Teste de Browser — página de boas-vindas na rota `/`

> Runtime: `pest-plugin-browser` **5.x** (Playwright). O plugin sobe o próprio servidor HTTP
> in-process, em porta aleatória — nada de Herd, `artisan serve`, Sail ou Vite dev server.
> Comando: `composer test:browser` (embute `npm run build` + `view:cache`) ou
> `php artisan test --testsuite=Browser`. **Nunca `--parallel`** — `.ai/rules/testes-browser.md`
> mediu 4 dos 11 cenários caindo com ele.

## Por que existe um `05` nesta feature

O gate desta wiki é estreito: quase tudo o que parece "de tela" aqui é asserção sobre HTML e vive
no `04`. Sobra **um** eixo que só o navegador prova:

> **`assertSee` não valida tema.** Ele passa com texto branco em fundo branco — o texto está no
> DOM, só está invisível (`.ai/rules/testes-browser.md`). O `04` prova que o `<script>` de tema
> **está na resposta** (CT-14); só o navegador prova que ele **roda** e que a página continua
> utilizável sob `prefers-color-scheme: dark`.

O segundo motivo é específico desta feature, e é o risco que o ADR-01 assume: a rota `/` boota o
painel `app` com ~30 plugins **fora** de uma rota de painel. Se algum deles registrar erro de
JavaScript nesse contexto, o corpo do HTML vem íntegro, o status é 200 e nenhum caso do `04` fica
vermelho.

## Pré-requisitos

- [ ] `npm run build` executado — embutido no `composer test:browser`
- [ ] `php artisan view:cache` executado — embutido no `composer test:browser`. Sem ele o primeiro
      cenário de painel paga a compilação inteira **dentro** do teto de 45 s e falha por tempo
- [ ] Aquecimento pelo kernel no `beforeEach`: `$this->get('/')`. O `view:cache` cobre as Blade do
      repositório, **não** os componentes Livewire do Filament — são ~25 s que ele não adianta, e
      rodando este arquivo isolado eles cairiam dentro do cronômetro do Playwright
- [ ] `tests/Browser/Screenshots` já está no fluxo do kit (limpo a cada run, e `KitArte::IMAGENS`
      é lista declarada — este arquivo **não** grava screenshot, então nada a declarar lá)
- [ ] Sem `actingAs()`: o cenário é anônimo por definição

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| título da página | texto visível `Bem-vindo ao Starter Kit Easy` | criado por esta feature |
| cartão de um painel | texto visível do rótulo do cartão | criado por esta feature |
| valor da infolist | texto visível do valor | criado por esta feature |

O kit não tem `data-testid` (dívida conhecida, registrada em `.ai/rules/testes-browser.md`). Este
arquivo usa **texto visível**, que é o que existe. Nenhum seletor por classe de CSS: `fi-*` e as
utilitárias do pacote de cartões mudam entre versões, e um seletor assim quebraria num upgrade sem
defeito nenhum no kit.

---

## CT-B01: a página abre em tema escuro, com conteúdo, e sem erro de JavaScript

**Por que browser e não Livewire ou HTTP**: a asserção é sobre **JavaScript executado** — o
`loadDarkMode()` que o `layout.base` do painel emite lê `localStorage`/`prefers-color-scheme` e
acrescenta a classe `dark` ao `<html>`. Nada disso existe num `$this->get()`, que devolve
exatamente o mesmo HTML nos dois temas. E `assertNoJavaScriptErrors()` é a única forma de saber se
o boot do painel `app` fora de uma rota de painel deixou algum plugin gritando no console.

```gherkin
# language: pt

  Cenário: [CT-B01] a página de boas-vindas abre em tema escuro, legível e sem erro no console
    Dado que ninguém está autenticado
    E que o navegador anuncia preferência por tema escuro
    Quando o visitante abre a rota "/"
    Então a página mostra o título "Bem-vindo ao Starter Kit Easy"
    E a página mostra o rótulo do cartão de cada um dos três painéis
    E a página mostra a versão do kit lida da config
    E o console do navegador não registra nenhum erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | aquecer a compilação fora do cronômetro | `$this->get('/')` no `beforeEach` | — |
| 2 | abrir a raiz com o navegador em tema escuro | `visit('/')->inDarkMode()` | a página renderiza escura |
| 3 | provar que renderizou conteúdo, não uma casca | `->assertSee('Bem-vindo ao Starter Kit Easy')` | o `<h1>` |
| 4 | provar que os três cartões estão lá | `->assertSee(...)` para cada rótulo | as três grades |
| 5 | provar que a infolist renderizou | `->assertSee(config('kit.version'))` | a versão |
| 6 | provar que o painel bootado não sujou o console | `->assertNoJavaScriptErrors()` | console limpo |

**Assertions**

- `assertNoJavaScriptErrors()` e **não** `assertNoSmoke()`. A `/` é HTML de autoria do kit **dentro**
  de um painel bootado com ~30 plugins e o render hook `BODY_END` do `assistente-chat-widget`
  (`AppPanelProvider.php:94-97`). `assertNoSmoke()` reprova em qualquer `console.log` de vendor, e a
  suíte ficaria vermelha por dívida de terceiro — o mesmo raciocínio que o docblock do CT-B04 de
  `tests/Browser/TelasDoKitTest.php` usa para justificar o inverso lá. Ver ADR-05.
- **Console não é o oráculo deste cenário.** Os passos 3, 4 e 5 são as âncoras: uma página em
  branco, um 403 renderizado ou uma tela sem conteúdo passam em `assertNoJavaScriptErrors()`
  sozinho.
- **Sem `assertPathIs`**: o cenário não navega — não há `press` nem `click`. A regra "`assertPathIs`
  antes das asserções de conteúdo" vale depois de ação que navega, e aqui não há.
- **Sem `wait()`**: o plugin reexecuta cada assertion até o teto de 45 s de
  `pest()->browser()->timeout()`. `waitForText`, `waitForSelector` e `waitUntil` não existem.
- **Sem `waitForEvent('networkidle')`**: a rede nunca fica ociosa numa página que carrega um painel
  Filament — `.ai/rules/testes-browser.md` mediu isso derrubando cenários no teto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M29 | a página monta um layout próprio, sem o `layout.base` do painel — o script de tema não sai | CT-B01 (a página abriria clara sob preferência escura; e CT-14 pega a ausência do script no HTML) |
| M30 | o tema é forçado em claro no painel ou na página | CT-B01 |
| M31 | um plugin do painel `app` registra erro de JavaScript quando bootado fora de rota de painel | CT-B01 (`assertNoJavaScriptErrors`) — **é o único cenário de toda a wiki que pega isso** |

M31 é o mutante que justifica este arquivo existir. Ele não é "implementação errada" nossa: é o
risco que o ADR-01 assumiu, e este é o instrumento que o mede.

---

## CT-B02: a página é legível nos dois temas

**Origem**: **achado do quality gate**, QA-01 do `06-relatorio-qa.md`. Este cenário **não** estava
neste arquivo no ciclo 1 — ele foi cortado, e o corte estava errado.

**Por que o corte estava errado**: a tabela "cogitados e cortados" abaixo dizia que
`assertNoAccessibilityIssues()` não mataria nenhum mutante previsto. O argumento confundia dois
mutantes diferentes. **M30** ("o tema é forçado em claro") morre em CT-B01, porque a página abriria
clara sob preferência escura. Um **defeito de contraste próprio** é outra coisa, e nenhum cenário o
matava: `inDarkMode()->assertSee(...)` passa com texto branco em fundo branco — o texto está no DOM
e na árvore de acessibilidade, só está invisível. RQ-11 ficava, portanto, com oráculo apenas
estático: a presença do `<script>` de tema no HTML (CT-14).

**Por que browser**: contraste é cor computada. Nenhuma camada mais barata a observa.

```gherkin
# language: pt

  Esquema do Cenário: [CT-B02] a página não tem problema de acessibilidade no tema <tema>
    Dado que ninguém está autenticado
    E que o navegador anuncia preferência pelo tema <tema>
    Quando o visitante abre a rota "/"
    Então a página mostra o título "Bem-vindo ao Starter Kit Easy"
    E a varredura de acessibilidade não encontra nenhum problema

    Exemplos:
      | tema   |
      | claro  |
      | escuro |
```

**Assertions**

- O `assertSee` do título vem **antes** do axe, e não é enfeite: `assertNoAccessibilityIssues()`
  passa numa página em branco. É a âncora que separa "acessível" de "vazio".
- **O tema é declarado em cada linha, nunca herdado.** `tests/Browser/TemaEscuroTest.php` mediu que
  a emulação de `prefers-color-scheme` **vaza** para o cenário seguinte: um cenário escuro antes de
  um cenário sem declaração produziu quatro achados `serious` falsos — paleta escura sobre fundo
  claro, com todo o texto da página em cinza-claro ao mesmo tempo. O sinal que denuncia o falso
  positivo é justamente esse: paleta inteira trocada é defeito de tema, não de um elemento.
- **Sem `waitForEvent('networkidle')`**, ao contrário do que o `TemaEscuroTest` faz: numa página que
  carrega um painel Filament a rede nunca fica ociosa. Lá ele sobrevive porque o painel tem
  polling de notificações que eventualmente assenta; aqui é risco sem retorno, e o plugin já
  reexecuta a asserção até o teto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | um par de cor do kit com contraste abaixo do limiar sob tema escuro | CT-B02 (linha `escuro`) |
| M33 | idem sob tema claro — o caso do `pxlrbt/filament-environment-indicator`, que já produziu um achado `serious` real neste kit e **só** no tema claro | CT-B02 (linha `claro`) |

**Estouro de teto declarado**: o perfil `padrão` dá teto de 1 CT-B, e agora há 2. O gate de
falsificabilidade da skill `feature-test-design` vence o teto — mutante vivo é pior que cenário a
mais, e M32/M33 não tinham matador nenhum.

---

## Cenários cogitados e cortados

O teto de CT-B do perfil `padrão` é **1 happy path**. Os candidatos abaixo foram cortados com
motivo, para que "só há 1 CT-B" não se confunda com "só pensamos em 1":

| Cenário cogitado | Por que foi cortado |
|---|---|
| clicar num cartão e chegar ao login do painel | o `href` já é provado por CT-03, mais barato; o clique só exercitaria o `<a>` do navegador |
| alternar o tema pelo botão e ver a página continuar utilizável | **não há botão de tema nesta página** — o `layout.simple` só mostra chrome sob `filament()->auth()->check()`, e o alternador vive na topbar. Já coberto para o kit em `tests/Browser/TemaEscuroTest.php` CT-B08, na tela de login |
| ~~`assertNoAccessibilityIssues()` na página~~ | **corte revertido.** O argumento original — "nenhum mutante previsto morre com ele" — confundia M30 (tema forçado em claro, que CT-B01 mata) com um defeito de contraste próprio, que nada matava. Virou **CT-B02** por achado do quality gate (QA-01) |
| screenshot comparado com baseline | o kit não versiona baseline de screenshot, e `tests/Browser/Screenshots` é limpo a cada run |
| acrescentar `/` ao lote do CT-B04 de `TelasDoKitTest` | ADR-05: aquele cenário usa `assertNoSmoke()`, e a `/` o deixaria vermelho por `console.log` de vendor. Relaxar o `assertNoSmoke()` de lá enfraqueceria as sete telas que já estão no cenário, e o relaxamento seria invisível no diff |
| um cenário em tema **claro** | `assertSee` devolve o mesmo HTML nos dois temas: o cenário claro não distingue nenhuma implementação da outra. E `.ai/rules/testes-browser.md` registra que declarar o tema é obrigatório quando o arquivo tem mais de um — com um cenário só, `inDarkMode()` é suficiente e não vaza para ninguém |

---

## Roteiro de Validação: Desenhado × Implementado

Preenchido na pós-implementação (step 7 da `feature-wiki`), conferindo a tabela
`## Superfície de UI` do `01-plano-acao.md` e o artboard
`design/Main.dc.html` contra a tela real.

| # | O que o desenho previu | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | cabeçalho centralizado: marca, `<h1>`, subtítulo, badge de versão | marca, `<h1>` e subtítulo pelo `<x-filament-panels::header>` nativo (`$title` + `getSubheading()`). **Sem** o badge de versão no cabeçalho | ⚠️ | CT-01 (título), CT-B01 |
| 2 | grade de 3 cartões, um por painel, com borda esquerda colorida | idem — `CardItem::color('primary'\|'info'\|'gray')`, as três que o `cards.css` cobre | ✅ | CT-03, CT-05 |
| 3 | cartão com ícone, rótulo, badge do caminho e frase | idem — `->icon()`, `->label()`, `->badge('/app')`, `->description()` | ✅ | CT-B01 |
| 4 | seção "Este projeto" com 6 entradas | idem: nome, cor primária, multi-organização, rótulo da organização, demo, hub | ✅ | CT-07, CT-16 |
| 5 | seção "Configuração do kit" com 6 entradas | 6 entradas, **composição diferente**: versão e idiomas entraram; as três retenções foram fundidas numa entrada só | ⚠️ | CT-07, CT-08, CT-10 |
| 6 | tema claro e escuro pelo mesmo par de tokens do Filament | idem, herdado do `layout.base` do painel bootado | ✅ | CT-14, CT-15, CT-B01 |
| 7 | sem barra lateral, sem menu de usuário, sem topbar para anônimo | idem | ✅ | CT-06 |

As duas linhas ⚠️ estão detalhadas em `03-progresso.md` → `## Desvios do Plano`. Nenhuma delas é
defeito: são escolhas tomadas na implementação, com o motivo escrito.
