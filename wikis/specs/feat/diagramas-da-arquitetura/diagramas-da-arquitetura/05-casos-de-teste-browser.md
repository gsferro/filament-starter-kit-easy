# Casos de Teste de Browser — Diagramas da arquitetura do kit no README e no site

> Derivado do `00-requisito.md` (RQ-12, RQ-18, RQ-27), como o `04`. *(alterado em 2026-09-29: o step 11 acrescentou
> RQ-28 e RQ-27 no CT-B04 — o `it('captura os quadros do instalador')`, QA-03 — e RQ-47, RQ-48 e RQ-49 no CT-B05 e no
> CT-B06, a legibilidade no site, QA-06 e Adendo 6. Costuras: linhas "Capturas do `composer art`" e "Site
> construído" de `## Costuras de Teste` do `04`.)*
> *(alterado em 2026-09-30: fechamento da revisão adversarial da adição do step 11 — CT-B07 (a escala que o navegador
> aplica, pela largura ou pela altura, ADV-22), CT-B08 (o texto HTML de `foreignObject` e o SVG sem texto medido,
> ADV-23), CT-B09 (o bloco dentro da coluna, ADV-24), CT-B10 (o recorte de cada quadro do `install.gif`, ADV-32) e CT-B11
> (a troca de tema como o evento do cenário, ADV-34); a forma do CT-B04 corrigida (ADV-33). Os achados estão em
> `## Revisão adversarial — adição do step 11 e do Adendo 7` do `04`.)*
> *(alterado em 2026-09-30: re-revisão adversarial da adição, rodada 2 — nenhum CT-B novo: o CT-B05 ganha as linhas
> "escuro desde o carregamento" e o M6 (ADV2-15), e a L-16 do `04` fecha com a evidência de 2026-09-30 (ADV2-23,
> rejeitado como linha permanente). Os achados estão em `## Revisão adversarial — re-revisão da adição (rodada 2)` do
> `04`.)*
>
> **Dois runtimes, e o motivo**:
>
> - **CT-B01, CT-B02** — conferidor Node + Playwright de `site/`, sobre o site **construído** e servido
>   pelo `astro preview`. O `pest-plugin-browser` sobe a aplicação Laravel em processo e não serve site
>   estático de outro toolchain (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`) — é o mesmo
>   arranjo do conferidor de acessibilidade, e o Pest trava que o fluxo o executa (`[CT-37]` do `04`,
>   irmão de `[CT-42]`). Divergência do template da skill, declarada no `04`.
> - **CT-B03** — `pest-plugin-browser`, dentro de `tests/BrowserTenancy/CapturaDeArteTest.php`, sob
>   `KIT_ART=1` (`tests/BrowserTenancy/CapturaDeArteTest.php:KIT_ART:59`). Roda no `composer art`, não
>   no CI — é lá que os quadros nascem.
>
> Comandos:
>
> ```bash
> # CT-B01, CT-B02 (em série; nunca --parallel)
> cd site && npm ci && npm run build
> npx astro preview & npx wait-on "http://localhost:4321/pt/" -t 60000
> node {conferidor de diagramas}      # o recorte toca site/verifica-acessibilidade.mjs
>
> # CT-B03, CT-B04
> composer art                         # npm run build + view:cache + KIT_ART=1 + kit:arte
>
> # CT-B05, CT-B06: o mesmo astro preview e o mesmo conferidor do CT-B01 (node verifica-acessibilidade.mjs)
> ```

## Gate — por que este arquivo existe

- **Linha na Superfície de UI**: "Site (Starlight) | Markdown estático + 1 página nova | +20 diagramas
  distribuídos, +1 página, **+1 dependência de renderização client-side**" e "Telas do app … só
  **fotografadas** pelo pipeline `kit:arte`".
- **Só o navegador prova**: o bloco Mermaid vira SVG por JavaScript executado no cliente (CT-B01); o
  SVG segue o tema claro/escuro (CT-B02, cor/tema); o quadro do GIF mostra o estado que o Alpine abre
  (CT-B03, JS executado). Nenhum dos três é provável por componente Livewire nem por leitura de
  arquivo — o `04` prova a **fonte** (CT-34, CT-35); isto prova a **renderização**.

## Teto e estouro justificado

Perfil `padrão` → teto de **1** CT-B (happy path). São **3**, pelo gate do passo 6 (o gate vence o teto):

| CT-B | Mutante que só ele mata |
|---|---|
| CT-B01 | happy path (integração registrada fora de ordem; bloco com erro de sintaxe que o Mermaid desenha como SVG de erro; axe rodando antes da renderização) |
| CT-B02 | tema fixado **na configuração da integração**, fora do bloco — o CT-34 do `04` só lê o bloco, e a opção de tema do `astro-mermaid` não tem citação de vendor (o pacote ainda não está instalado: `site/node_modules` ausente) |
| CT-B03 | quadro tirado antes do estado que ele devia mostrar — o GIF sai com três fotos da mesma tela e nenhuma asserção de arquivo o vê |
| CT-B04 *(alterado em 2026-09-29: step 11)* | quadro do instalador cortado pela janela de captura — os quadros 3 e 4 já saíram byte a byte iguais, com o texto novo fora da tela — e recorte ignorado; CT-52 do `04` trava a fonte, não o que a janela mostra |
| CT-B05 *(idem)* | fonte efetiva abaixo do piso — fonte computada × escala do `viewBox`; o axe não mede texto em SVG, e o CT-B01 conta SVGs (QA-06) |
| CT-B06 *(idem)* | o excesso fora do bloco: a página rolando na horizontal, ou o diagrama cortado sem rolagem |
| CT-B07 *(alterado em 2026-09-30: revisão adversarial da adição)* | o SVG encolhido pela **altura** (`max-height` com `preserveAspectRatio` meet): a razão largura/`viewBox` dá 1 e o CT-B05 aceita |
| CT-B08 *(idem)* | o rótulo HTML de `foreignObject` fora da medida, e o SVG sem texto medido que passa no piso |
| CT-B09 *(idem)* | o bloco que cresce além da coluna (`width: max-content`) e é cortado por um ancestral: nada rola, a página não rola, e o CT-B06 fica verde |
| CT-B10 *(idem)* | o quadro do `install.gif` recortado um marcador antes ou depois: os PNG continuam diferentes entre si, e o CT-B04 não vê |
| CT-B11 *(idem)* | o conserto que não sobrevive ao redesenho da troca de tema, com a troca como o evento do cenário — no CT-B05 ela estava num parâmetro do `Dado` |

*(alterado em 2026-09-29: são 6 com o step 11, pelo mesmo gate: cada um é o único matador dos seus mutantes. CT-B05 e
CT-B06 medem na mesma passada do conferidor; são dois porque a regra do piso e a da rolagem são duas — juntas, passariam
do teto de mutantes de uma regra.)* *(alterado em 2026-09-30: são 11 com a revisão adversarial da adição, pelo mesmo
gate — cada um dos cinco novos é o único matador de mutante trazido pela revisão, e o gate vence o teto. CT-B07, CT-B08,
CT-B09 e CT-B11 rodam na mesma passada do conferidor que o CT-B05; o CT-B10, no mesmo `it()` de captura do CT-B04.)*
*(alterado em 2026-09-30: re-revisão adversarial da adição, rodada 2 — continuam 11: o ADV2-15 é outra partição do tema
no próprio CT-B05, duas linhas de `Exemplos` e o M6, e não um CT-B novo.)*

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| renderização no **GitHub** | o GitHub não é alcançável pelo navegador de teste; procuração por CT-36 + CT-38 do `04`, conferência manual no PR (lacuna L-03) |
| acessibilidade do SVG como cenário próprio | já é do conferidor de acessibilidade existente, desde que ele rode **depois** do SVG — isso é CT-B01 |
| smoke de cada página com diagrama em lote | mata o mesmo conjunto de CT-B01, que já percorre todas |
| tela de convite, conta, login etc. no app | nenhuma muda (recorte: "Nenhuma mudança de UI"); comportamento já provado em `tests/Browser/LoginUnificadoTest.php` e `tests/Browser/RoteiroDoKitTest.php` |
| o `install.gif` renderizado da fixture | pixel; a fonte é travada por CT-52 do `04`, o resto é lacuna L-04 *(alterado em 2026-09-29: o step 11 prova os quatro PNG de origem no CT-B04; o GIF montado continua em L-04)* |
| *(step 11)* a fonte efetiva em toda largura de janela, de 320 a 1920 | duas janelas — a do QA-06 e a do celular — são as partições do layout do Starlight; mais larguras matam os mesmos mutantes |
| *(step 11)* screenshot comparado do DG-11 antes e depois do CSS | detecta mudança, não piso (`assertScreenshotMatches` cria o baseline com o defeito); a medida numérica é o oráculo |
| *(step 11)* título do índice × `accTitle` e português no bloco en, no site renderizado | texto do Markdown: CT-131 e CT-132 do `04` provam na fonte, mais barato |
| *(step 11)* a legibilidade no GitHub | RQ-48 manda não mudar o que o GitHub mostra; o GitHub não é alcançável pelo navegador de teste (L-03) |

## Pré-requisitos

- [ ] `npm ci` em `site/` com o lock que resolve `mermaid` 11.17.2 (CT-38 do `04`)
- [ ] `npm run build` em `site/` — sem `dist/` o conferidor tem piso e reprova (não fica verde sobre o vazio)
- [ ] `npx playwright install chromium` (já é passo do `pages.yml`)
- [ ] CT-B03: `npm run build` e `view:cache` na raiz (o `composer art` embute os dois), `KIT_ART=1`
- [ ] CT-B03: aquecimento pelo kernel antes do `visit()` (`.ai/rules/testes-browser.md`, "aqueça pelo kernel") e o painel arranjado **no próprio cenário**, nunca no `beforeEach` (mesma rule)
- [ ] CT-B04 *(alterado em 2026-09-29: step 11)*: os mesmos do CT-B03 (`KIT_ART=1`, `npm run build`, `view:cache`); a rota da fixture registrada só no teste, antes do `visit()`
- [ ] CT-B05, CT-B06 *(idem)*: os mesmos do CT-B01 (`npm ci`, `npm run build`, `astro preview` de pé, Chromium do Playwright)

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| blocos da fonte de cada página | contagem de cercas ```` ```mermaid ```` no `.md` correspondente em `docs/` | sim (o conferidor lê o Markdown) |
| SVG que a integração produz para cada bloco | `svg[aria-roledescription]` | **confirmado** — executor do CT-B01 leu o DOM real (`chromium` + `page.evaluate`, sessão de 2026-09-28): `<svg id="mermaid-…" role="graphics-document document" aria-roledescription="flowchart-v2">` (varia por tipo: `"er"` no `erDiagram`) |
| SVG de erro do Mermaid | ~~o mesmo seletor com `aria-roledescription="error"`, ou o texto `Syntax error` dentro do SVG~~ **corrigido pelo executor do CT-B01** (causa a: seletor especificado errado) — o astro-mermaid 2.1.0 real NÃO desenha um SVG de erro; no `catch` de `initMermaid()` ele substitui o bloco por um `<div>` com um `<strong>Error rendering diagram:</strong>` filho direto (`site/node_modules/astro-mermaid/astro-mermaid-integration.js:catch:554-567`, lido pelo executor; o `node_modules` só existe depois do `npm ci` em `site/`). Seletor real: `:scope > div > strong` a partir de `pre.mermaid` — é o que `site/verifica-acessibilidade.mjs` já usava antes deste CT-B (o próprio conferidor já documentava a estrutura real; a tabela deste `05` é que trazia o palpite errado) |
| troca de tema | `document.documentElement.setAttribute('data-theme', t)` — o jeito que o conferidor já usa (`site/verifica-acessibilidade.mjs:setAttribute:231`) | sim |
| overlay da busca ⌘K | `input[placeholder="Buscar registros e telas..."]` e `[x-on\:open-spotlight\.window]` (`tests/Browser/RoteiroDoKitTest.php`, F-45) | sim |
| escolha de painel | `assertPathIs('/login/painel')` + `a[href$="/login/painel/{id}"]` (`tests/Browser/LoginUnificadoTest.php`, CT-B01 de `feat/login-unificado`) | sim |
| *(step 11)* janela da transcrição do instalador | `.janela` (`tests/Browser/Fixtures/terminal-instalacao.blade.php:janela:149`), com `assertScript("document.querySelector('.janela').getBoundingClientRect().bottom <= window.innerHeight")` (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesElementAssertions.php:assertScript:156`) | sim — o comentário do teste mediu a `.janela` assim |
| *(step 11)* escala de um diagrama | `svg.getBoundingClientRect().width / svg.viewBox.baseVal.width`, para cada `pre.mermaid svg` | sim — a medida do QA-06 (`qagate-escala.mjs`) |
| *(step 11)* texto de um diagrama | `text`, `tspan` e os elementos HTML dentro de `foreignObject`, sob o `pre.mermaid svg` | a confirmar pelo executor: o Mermaid 11 desenha rótulo de fluxo em HTML dentro de `foreignObject` e mensagem de sequência em `<text>` |
| *(step 11)* contêiner rolável do bloco | o ancestral do SVG, até o `pre.mermaid` ou o elemento que o envolve, com `getComputedStyle(el).overflowX` em `auto` ou `scroll` | nova |
| *(revisão adversarial da adição, 2026-09-30)* escala real de um texto | a matriz da tela do elemento — `el.getScreenCTM().a` para `text`/`tspan`, e a do `foreignObject` ancestral para o HTML de dentro dele —, que já inclui o `viewBox` e o `preserveAspectRatio` | nova — substitui a razão largura/`viewBox` do passo 5 do CT-B05 (CT-B07) |
| *(idem)* texto de um diagrama | `text`, `tspan` e todo elemento com texto dentro de `foreignObject`, sob o `pre.mermaid svg`; e a contagem de `foreignObject` com texto, para conferir que todos entraram | **obrigatório** pelo CT-B08, que deixa de ser "a confirmar" |
| *(idem)* coluna de conteúdo | `.sl-markdown-content`, o ancestral do bloco (presente no HTML construído: `grep -o sl-markdown-content site/dist`) | sim |

---

## CT-B01: todo bloco Mermaid do site vira SVG sem erro, nos dois temas, antes do axe

**Por que browser e não Livewire/arquivo**: a renderização é JavaScript executado no cliente; um
bloco que o Starlight entrega como código (integração fora de ordem) e um bloco que o Mermaid desenha
como "Syntax error" são **HTML válido** para qualquer leitura de arquivo.

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: Cada página com diagrama, construída, mostra um SVG sem erro para cada bloco da fonte, em pt e en, nos dois temas

    Cenário: [CT-B01] os 20 blocos por idioma viram SVG, sem erro, e o axe roda depois deles
      Dado o site construído com a dependência fixada em 11.17.2
      E as páginas de docs/ que têm blocos Mermaid, com N blocos cada na fonte
      Quando o conferidor abre cada uma dessas páginas no tema escuro e no tema claro
      Então cada página tem exatamente N SVGs de diagrama, e nenhum é SVG de erro nem contém "Syntax error"
      E o console não registrou erro
      E o axe roda só depois do último SVG da página e não reporta violação serious ou critical
      E a soma de SVGs conferidos é 20 por idioma e por tema
```

**Roteiro executável** (Node + Playwright)

| # | Ação | Código | Resultado visível |
|---|---|---|---|
| 1 | lista as páginas com diagrama pela **fonte** | ler `docs/{pt,en}/**/*.md` e contar ```` ```mermaid ```` por arquivo → `{rota: N}` | 20 blocos por idioma (piso) |
| 2 | abre no tema | `browser.newContext({ colorScheme: tema })` → `page.goto(url)` | página carregada |
| 3 | fixa o tema do site | `page.evaluate(t => document.documentElement.setAttribute('data-theme', t), tema)` | tema aplicado |
| 4 | **espera o estado final**, não segundos | `page.locator(SELETOR_SVG).nth(N - 1).waitFor({ state: 'visible' })` | o N-ésimo SVG existe |
| 5 | conta e procura erro | `locator(SELETOR_SVG).count() === N`; nenhum com `aria-roledescription="error"`; `innerText` sem `Syntax error` | N SVGs, zero erro |
| 6 | só então o axe | `page.addScriptTag({ content: AXE })` → `axe.run()` | zero serious/critical |
| 7 | console | `page.on('console', m => m.type() === 'error' && erros.push(...))` desde o passo 2 | `erros.length === 0` |
| 8 | piso | soma de SVGs por idioma e tema === 20 | sai com código ≠ 0 se menor |

**Assertions**: contagem **igual** à da fonte (não "≥ 1"); ausência de SVG de erro; console vazio; axe
depois do passo 4; piso de 20.

*(alterado em 2026-09-29: como o conferidor faz os passos 4 e 5 — espera todo `pre.mermaid` ganhar
`data-processed` (`site/verifica-acessibilidade.mjs:waitForFunction:238`), que o `astro-mermaid` grava
ao terminar, em vez de esperar o N-ésimo SVG; e reconhece o erro pela estrutura que o `astro-mermaid`
2.1.0 põe no lugar do bloco (`:scope > div > strong`, `site/verifica-acessibilidade.mjs:temElementoDeErro:256`),
não por `aria-roledescription="error"` nem pelo texto "Syntax error" — a tabela de Seletores acima já
registra a correção. O CT-B01 achou dois defeitos reais, corrigidos no passo 22 do `01`: o DG-11 que
não renderizava e o contraste do rótulo de aresta no tema escuro)*

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `astro-mermaid` registrado depois do Starlight — o Expressive Code pega a cerca e ela vira bloco de código | CT-B01 (contagem 0 ≠ N) |
| M2 | bloco com sintaxe inválida (rótulo com parênteses sem aspas) — o Mermaid desenha um SVG **de erro**, e "tem SVG" passaria | CT-B01 (SVG de erro / "Syntax error") |
| M3 | axe em `domcontentloaded` (`site/verifica-acessibilidade.mjs:domcontentloaded:230`), sobre o `<pre>` ainda não renderizado | CT-B01 (passo 4 antes do 6) |
| M4 | conferidor sem piso, verde sobre zero páginas com diagrama | CT-B01 (piso de 20) |
| M5 | só o tema escuro conferido (o tema claro é amostra no conferidor atual) | CT-B01 (os dois temas em **toda** página com diagrama) |

---

## CT-B02: o diagrama segue o tema claro/escuro

**Por que browser**: cor e tema. A asserção é **numérica** (`getComputedStyle`), não visual — ela
discrimina "tema fixo" (mesma cor nos dois) sem julgar legibilidade, que continua sendo screenshot e
olhar (`.ai/rules/testes-browser.md`, "assertSee não valida tema").

`@premissa` P-18 (de comportamento): "segue o tema" vale **também** para a troca
depois do carregamento, que é o que o alternador do site faz — o 00 diz "segue o tema claro/escuro"
sem restringir ao carregamento. Se negada, o passo 3 do roteiro vira dois carregamentos, um por tema.

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: O mesmo nó do mesmo diagrama tem cor diferente no tema claro e no tema escuro, inclusive depois de trocar o tema

    Cenário: [CT-B02] trocar o tema troca a cor do diagrama
      Dado a página pt/referencia/arquitetura-em-diagramas/ aberta no tema claro, com o DG-01 renderizado
      E a cor de preenchimento do primeiro nó do DG-01 medida
      Quando o leitor troca o site para o tema escuro
      Então o DG-01 é renderizado de novo
      E a cor de preenchimento do mesmo nó difere da medida no tema claro
      E a cor do texto do nó difere da cor de preenchimento dele
```

**Roteiro executável**

| # | Ação | Código | Resultado visível |
|---|---|---|---|
| 1 | abre no claro | `colorScheme: 'light'` → `goto('/pt/referencia/arquitetura-em-diagramas/')` → espera o SVG do DG-01 | DG-01 visível |
| 2 | mede | `evaluate(() => getComputedStyle(primeiroNo).fill)` e `.color` do rótulo | `{fill, texto}` claro |
| 3 | troca | `evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'))` → espera o SVG **novo** (atributo/`id` diferente do medido, ou `waitForFunction` com a cor) | DG-01 re-renderizado |
| 4 | mede de novo | idem passo 2 | `{fill, texto}` escuro |

**Assertions**: `fill` claro ≠ `fill` escuro; `texto` ≠ `fill` em cada tema. Sem `wait` de segundos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | tema fixado na configuração da integração (`theme: 'default'`) | CT-B02 (fill igual) |
| M2 | tema lido só no carregamento; a troca não re-renderiza | CT-B02 (passo 3) |
| M3 | CSS do kit pintando o SVG com uma cor fixa (texto escuro sobre nó escuro no tema escuro) | CT-B02 (texto ≠ fill) |

---

## CT-B03: cada quadro dos clipes novos é tirado depois do estado que ele mostra

**Por que browser**: o overlay da busca é aberto pelo Alpine (JS executado) e a escolha de painel é
uma navegação; o `->screenshot()` fotografa o que estiver na tela **naquele instante**.

```gherkin
# language: pt
Funcionalidade: GIFs pelo kit:arte

  Regra: Antes de cada quadro de clipe, o cenário de captura afirma o estado visível que o quadro deve mostrar

    Esquema do Cenário: [CT-B03] o quadro "<quadro>" mostra "<estado>"
      Dado KIT_ART=1 e o painel do cenário arranjado nele mesmo, aquecido pelo kernel
      E o usuário "<persona>" autenticado
      Quando o cenário de captura chega ao quadro "<quadro>"
      Então, antes do screenshot, a tela afirma "<estado>"
      E o console não registrou erro

      Exemplos:
        | quadro                          | persona                 | estado                                                                |
        | busca: overlay aberto           | master_global           | o campo "Buscar registros e telas..." visível e ancorado à viewport  |
        | busca: resultado                | master_global           | ao menos um resultado com o termo digitado                            |
        | login unificado: escolha        | admin + infra (global)  | caminho /login/painel com os cartões Administração e Infraestrutura   |
```

> **Correção do executor do CT-B03 (causa a — CT-B especificado errado)**: a linha `login
> unificado: painel escolhido` (`/admin` com "Painel de Controle") foi removida desta tabela de
> Exemplos. Três fontes independentes concordam em parar em "escolha de painel" e nenhuma pede o
> quadro seguinte:
>
> 1. A Superfície de UI do `01-plano-acao.md` declara "GIFs (busca ⌘K, **escolha de painel** do
>    login unificado)" — não "painel escolhido".
> 2. O RQ-27 do `00-requisito.md` diz "login unificado → **escolha de painel**", com a mesma seta
>    parando aí.
> 3. O próprio **Roteiro executável** logo abaixo (passos 1–5, inalterado) nunca implementou esta
>    quarta linha — ela existia só no Esquema do Cenário, e nenhum passo a executava. Uma tabela de
>    Exemplos que promete uma linha que o roteiro do MESMO `05` nunca cobre é a própria definição de
>    "CT-B especificado errado": não é seletor nem rota errada, é uma linha sem execução por trás.
>
> `KitArte::CLIPES['login-unificado']` (`app/Console/Commands/KitArte.php:'login-unificado':76-79`) também declara só
> dois quadros (`login-unificado-1-formulario`, `login-unificado-2-escolha`) — Desenhado,
> Implementado e o próprio Roteiro deste `05` concordam; só a tabela de Exemplos divergia. Ser
> tecnicamente factível (`LoginUnificadoTest.php` já clica em "Administração" e chega a `/admin`)
> não é o padrão: adicionar um quadro que nenhuma das três fontes pede seria escopo por conta
> própria do executor, o que o contrato do CT-B proíbe tão quanto relaxar uma asserção.

**Roteiro executável** (Pest, `pest-plugin-browser`)

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `$this->actingAs($usuario); $this->get('/admin');` (aquece fora do cronômetro) | — |
| 2 | busca | `visit('/admin')->click('.fi-global-search-field')->assertVisible('input[placeholder="Buscar registros e telas..."]')` | overlay aberto |
| 3 | quadro | `->screenshot(fullPage: false, filename: '{quadro}')` **depois** do passo 2 | PNG com o overlay |
| 4 | escolha | `visit('/login')->fill('#form\.email', …)->fill('#form\.password', 'password')->press('Login')->assertPathIs('/login/painel')->assertSee('Administração')` | tela de escolha |
| 5 | quadro | `->screenshot(fullPage: false, filename: '{quadro}')` | PNG da escolha |

*(alterado em 2026-09-29: o que o teste faz, e que o roteiro acima não dizia — a busca é no `/app` com
tenancy (`arranjarPainelApp()` e `visit("/app/{slug}/projetos")`), com um projeto criado no cenário; o
quadro fechado sai depois do `assertSee` do projeto, e o aberto só depois de digitar "Contrato" e o
resultado aparecer **dentro** do overlay (`assertSeeIn('[x-on\:open-spotlight\.window]', …)`); o login
unificado arranja uma conta com `admin` e `infra` globais, desloga, aquece `/login` pelo kernel, fotografa
o formulário vazio (`assertPresent('.fi-auth-layout')`) e, na escolha, afirma os dois cartões,
Administração e Infraestrutura, antes do quadro — `tests/BrowserTenancy/CapturaDeArteTest.php:'[CT-B03]':703` e
`tests/BrowserTenancy/CapturaDeArteTest.php:'[CT-B03]':752`)*

A senha `password` do passo 4 é a da **persona de teste** (`tests/Pest.php:'password' => 'password':393` cria a conta com
ela), não a do administrador instalado — RQ-28 não se aplica a fixture de teste.

**Assertions**: `assertPathIs` primeiro depois de navegar; `assertVisible`/`assertSee` do estado antes de
cada `screenshot()`; `assertNoJavaScriptErrors()` (telas de plugin de terceiro — busca e escolha — não
usam `assertNoSmoke()`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `screenshot()` logo depois do `click()`, antes de o Alpine abrir o overlay | CT-B03 (linha "overlay aberto") |
| M2 | quadro da escolha tirado antes da navegação terminar (foto da tela de login) | CT-B03 (`assertPathIs` antes) |
| M3 | cenário arranjado no `beforeEach` com painel de outro contexto (barra lateral errada no quadro) | CT-B03 (arranjo no próprio cenário) — conferir a barra lateral no olho, nenhum `assertSee` a vê |

---

## CT-B04: cada quadro do `install.gif` é a transcrição recortada até o seu ponto, inteira na área fotografada, e sem `password`

*(alterado em 2026-09-29: cenário novo, do step 11 — QA-03: o `it('captura os quadros do instalador')`,
`tests/BrowserTenancy/CapturaDeArteTest.php:'[CT-B04]':794`, não tinha ID nem cenário. É de R35
do `04`, e o teste passa a levar `[CT-B04]`.)*

**Por que browser e não arquivo**: CT-52 do `04` trava o texto da fixture; o quadro é o **pixel** do que a janela
mostra, e só o navegador prova que o recorte de cada quadro cabe nela. O defeito foi medido: com a altura das outras
capturas, o texto novo dos quadros 3 e 4 nascia fora da tela, e os dois PNG saíam byte a byte iguais (o comentário do
próprio teste).

```gherkin
# language: pt
Funcionalidade: GIFs pelo kit:arte

  Regra: Cada quadro do install.gif é a transcrição do kit:install recortada até o seu ponto, inteira na área fotografada, e nenhum mostra password

    Esquema do Cenário: [CT-B04] o quadro "<quadro>" mostra a transcrição até "<ate>", inteira na tela e sem password
      Dado a fixture da transcrição da instalação recortada até "<ate>", servida por uma rota registrada só no teste
      Quando o cenário de captura tira o quadro "<quadro>"
      Então a página do quadro mostra o título "meu-projeto — instalação" e não mostra "/ password" nem "password (padrão do kit)"
      E a janela da transcrição termina dentro da área visível
      E o PNG do quadro difere do PNG do quadro anterior, quando há um

      Exemplos:
        | quadro                 | ate                                                              |
        | instalacao-1-inicio    | "Rodando migrations .. DONE"                                     |
        | instalacao-2-senha     | "Gerando senha do administrador .. DONE"                         |
        | instalacao-3-progresso | o fim dos passos numerados                                       |
        | instalacao-4-resumo    | o fim da transcrição: os links, o login com a senha mascarada e o aviso |
```

*(alterado em 2026-09-30: revisão adversarial da adição, ADV-33 — a forma. O `Quando` era "abre o quadro e o fotografa",
duas ações, e o `Então` afirmava um estado "antes do screenshot", entre as duas. Tirar o quadro é **uma** ação do
domínio — abrir a rota do recorte na janela de captura e fotografá-la —, e o `Então` afirma a página do quadro e o PNG
que ela gerou; a ordem das asserções antes do screenshot (passos 3 e 4 antes do 6) é do roteiro, onde sempre esteve, e
continua obrigatória. Nenhuma asserção saiu. O conteúdo de cada quadro — o marcador dele à vista e o do seguinte fora —
é o CT-B10, que nasce na forma certa.)*

**Roteiro executável** (Pest, `pest-plugin-browser`, `KIT_ART=1`)

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | serve o recorte | `Route::get("/_teste-arte/{$quadro}", fn () => response(view()->file($fixture, ['ate' => $ate])->render()))` | rota só do teste |
| 2 | abre na janela de captura | `visit("/_teste-arte/{$quadro}")->resize(1400, 2100)` *(alterado em 2026-09-29: QA-13 — a altura é medida no quadro final, `->resize(1400, $alturaDoConteudo)`, a mesma nos quatro quadros)* | o recorte na tela |
| 3 | âncora antes da ausência | `->assertSee('meu-projeto — instalação')` | o título da janela |
| 4 | ausências | `->assertDontSee('/ password')->assertDontSee('password (padrão do kit)')` | — |
| 5 | cabe na tela | `->assertScript("document.querySelector('.janela').getBoundingClientRect().bottom <= window.innerHeight")` | a janela inteira visível |
| 6 | quadro | `->screenshot(fullPage: false, filename: $quadro)` | PNG em `tests/Browser/Screenshots/` |
| 7 | quadros distintos | depois do laço, `md5_file()` de cada PNG ≠ o do anterior | 4 PNG diferentes |

**Assertions**: o `assertSee` do título vem antes das ausências — uma página 404 não mostra `password` e passaria no
vazio; o `assertScript` e o hash são as asserções que só este cenário tem. O teste de hoje faz 1–4 e 6; ganha 5 e 7. *(alterado em 2026-09-29: step 10 do ciclo 2 — o `it()` ganhou o `[CT-B04]` e a altura medida (QA-13), mas **ainda não** o `assertScript` do passo 5 nem o hash do passo 7; divergência declarada no `03`, `## 26.`)*

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a janela de captura do instalador com a altura das outras capturas (875 px): o fim do recorte fica fora da área fotografada | CT-B04 | "a janela da transcrição termina dentro da área visível" (o `bottom` passa de `innerHeight`), e os PNG dos quadros 3 e 4 saem iguais |
| M2 | o recorte ignora `$ate`, e os quatro quadros mostram a transcrição inteira | CT-B04 | "o PNG do quadro difere do PNG do quadro anterior": os quatro hashes são iguais |
| M3 | a fixture ou o recorte traz a linha antiga `admin@example.com / password`, ou o resumo `password (padrão do kit)` | CT-B04 | as duas ausências, antes do screenshot |
| M4 | a rota do quadro não responde (404), e as ausências passam no vazio | CT-B04 | o título "meu-projeto — instalação" não está na página |

---

## CT-B05: a fonte efetiva de todo diagrama do site fica no piso de 12 px, em qualquer janela, tema e idioma

*(alterado em 2026-09-29: cenário novo, do step 11 — QA-06; RQ-47, RQ-49. É de R62 do `04`.)*

**Por que browser**: a fonte efetiva só existe depois de o Mermaid desenhar o SVG no cliente e de o CSS do site o
encaixar na coluna — é o tamanho da fonte computada × a escala do `viewBox`. O axe não mede texto em SVG e o CT-B01
conta SVGs: os dois passam com o DG-11 a 2,2 px (QA-06).

`@premissa` (de mecanismo, Q?8 do `04`): o piso é 12 px, sem tolerância, nas janelas 1280 × 900 e 390 × 844. RQ-49 é
cumprida pela ordem: o cenário **nasce vermelho** — 13 dos 20 diagramas abaixo de 10 px a 1280 × 900 (QA-06) — e só
então vem o CSS.

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: No site, todo texto de diagrama é desenhado com pelo menos 12 px de fonte efetiva, em qualquer janela, tema e idioma

    Esquema do Cenário: [CT-B05] a menor fonte efetiva de cada diagrama fica no piso
      Dado o site construído, servido pelo astro preview
      E a página "<pagina>" na janela "<janela>", em "<idioma>", no tema "<tema>"
      Quando o conferidor mede, em cada SVG de diagrama, a fonte computada de cada texto × a escala do viewBox
      Então o resultado é "<resultado>"

      Exemplos:
        | pagina                                                                              | janela     | idioma | tema                             | resultado                                                                                           | # partição               |
        | cada página com diagrama                                                            | 1280 × 900 | pt     | claro                            | aceita: a menor fonte efetiva de cada SVG é ≥ 12 px, e são 20 SVGs medidos                           | a janela do QA-06        |
        | cada página com diagrama                                                            | 1280 × 900 | pt     | escuro, depois de trocar do claro | aceita, idem                                                                                        | o SVG redesenhado na troca (P-18) |
        | cada página com diagrama                                                            | 1280 × 900 | en     | claro                            | aceita, idem                                                                                        | idioma                   |
        | cada página com diagrama                                                            | 390 × 844  | pt     | claro                            | aceita, idem                                                                                        | a coluna do celular      |
        | cada página com diagrama                                                            | 390 × 844  | en     | escuro, depois de trocar do claro | aceita, idem                                                                                        | celular, en, troca       |
        | cada página com diagrama                                                            | 1280 × 900 | pt     | escuro desde o carregamento, sem troca | aceita, idem                                                                                   | o escuro no primeiro desenho (ADV2-15) |
        | cada página com diagrama                                                            | 390 × 844  | en     | escuro desde o carregamento, sem troca | aceita, idem                                                                                   | celular, en, escuro no primeiro desenho |
        | de controle: um SVG com texto de 16 px e viewBox 5 vezes mais largo que o desenhado | 1280 × 900 | —      | claro                            | reprova, nomeando 3,2 px                                                                            | a escala entra na conta  |
        | de controle: um SVG de escala 1 com um texto de 16 px e outro de 8 px                | 1280 × 900 | —      | claro                            | reprova, nomeando 8 px                                                                              | o menor texto, não o primeiro |
        | de controle: um SVG de escala 1 com texto de 12 px                                  | 1280 × 900 | —      | claro                            | aceita                                                                                              | a borda do piso          |
```

**Roteiro executável** (Node + Playwright, dentro de `site/verifica-acessibilidade.mjs`)

| # | Ação | Código | Resultado visível |
|---|---|---|---|
| 1 | lista as páginas pela fonte | o mesmo passo 1 do CT-B01 | 20 blocos por idioma |
| 2 | abre | `browser.newContext({ viewport: { width, height }, colorScheme: 'light' })` → `page.goto(url)`; nas linhas "escuro desde o carregamento", `colorScheme: 'dark'` **antes** do `page.goto()`, e sem o passo 4 *(alterado em 2026-09-30: rodada 2, ADV2-15 — hoje as combinações abrem só no claro, `site/verifica-acessibilidade.mjs:colorScheme: 'light':581`)* | página carregada |
| 3 | espera o desenho | `page.waitForFunction(() => [...document.querySelectorAll('pre.mermaid')].every(p => p.getAttribute('data-processed')))` — a espera do conferidor (`site/verifica-acessibilidade.mjs:waitForFunction:238`) | todo bloco desenhado |
| 4 | troca o tema, nas linhas "depois de trocar" | `page.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'))` → espera o SVG **novo**, como o passo 3 do CT-B02 | SVG redesenhado |
| 5 | mede | para cada `pre.mermaid svg`: `escala = svg.getBoundingClientRect().width / svg.viewBox.baseVal.width`; para cada texto (`text`, `tspan`, HTML de `foreignObject`): `parseFloat(getComputedStyle(el).fontSize) * escala`; guarda o mínimo | `{dg, minimo}` por SVG |
| 6 | controles | `page.setContent(...)` com os três SVG de controle, medidos pela mesma função do passo 5 | 3,2 px · 8 px · 12 px |
| 7 | relata e decide | imprime `[CT-B05] fonte efetiva mínima — {idioma} {janela} {tema}: {menor} px ({dg})` e sai com código ≠ 0 se algum mínimo < 12 ou se a soma de SVGs medidos por combinação < 20 | a linha `[CT-B05]` |

**Assertions**: o mínimo de **cada** SVG, não a média nem o primeiro texto; a medida depois do desenho (passo 3) e,
nas linhas da troca, depois do redesenho (passo 4); os controles pela **mesma** função de medida; o piso de 20 SVGs.
Sem `wait` de segundos.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o CSS aumenta a fonte do texto e deixa o SVG encolher pelo `max-width` inline do Mermaid: a fonte computada sobe, a efetiva continua abaixo do piso | CT-B05 | linhas reais a 1280 × 900: com a escala do DG-11 (0,14, QA-06), a efetiva fica abaixo de 12 com qualquer fonte até 85 px |
| M2 | o conserto não sobrevive ao redesenho da troca de tema: o SVG novo volta com o `max-width` inline e encolhe | CT-B05 | linhas "escuro, depois de trocar do claro": a medida é depois do redesenho, não no carregamento |
| M3 | o conserto só vale em janela larga (`@media (min-width: …)`) | CT-B05 | linhas 390 × 844: a coluna do celular encolhe o SVG de novo |
| M4 | o medidor lê a fonte computada e ignora a escala — o ponto cego do axe e do CT-B01 | CT-B05 | linha de controle do viewBox 5 vezes mais largo: "reprova, nomeando 3,2 px"; o mutante lê 16 px e aceita |
| M5 | o medidor lê só o primeiro texto do SVG (a medida do QA-06) e deixa passar o rótulo menor — de aresta, de nota, atributo de ER | CT-B05 | linha de controle com 16 px e 8 px: "reprova, nomeando 8 px"; o mutante lê 16 e aceita |
| M6 | *(re-revisão adversarial, ADV2-15)* o conserto do piso fica sob `@media (prefers-color-scheme: light)`: quem abre o site com o sistema no escuro vê o diagrama encolhido; as linhas da troca não veem, porque carregam no claro e a mídia continua clara depois de trocar o `data-theme` | CT-B05 | linhas "escuro desde o carregamento": a menor fonte efetiva fica abaixo de 12 px; o mutante passa por toda linha que carrega no claro |

*(alterado em 2026-09-30: revisão adversarial da adição. **ADV-22**: a escala do passo 5 — `getBoundingClientRect().width
/ viewBox.baseVal.width` — é a da largura só, e um SVG encolhido pela altura (`max-height`, com o `preserveAspectRatio`
meet) dá 1 com a fonte efetiva abaixo do piso; a escala passa a ser a matriz da tela (Seletores), e o controle é o
CT-B07. **ADV-23**: o conjunto de textos do passo 5 inclui o HTML de `foreignObject` — deixou de ser "a confirmar" —, e
todo SVG tem ao menos um texto medido: o CT-B08. **ADV-34**: nas linhas "escuro, depois de trocar do claro", a troca é o
evento cujo efeito o M2 mede, e estava num parâmetro do `Dado`; as linhas ficam, e o matador do M2 na forma certa é o
CT-B11. **ADV-36**: a execução vermelha deste cenário antes do CSS não está registrada — a do QA-06 é do
`qagate-escala.mjs`, que é o M5; é a lacuna L-16 do `04`.)* *(alterado em 2026-09-30: re-revisão adversarial da adição, ADV2-15, ADV2-23)*. **ADV2-15**: o
tema escuro só entrava por troca, depois de carregar no claro; o conserto posto sob a mídia do sistema claro passava. As
duas linhas "escuro desde o carregamento" abrem o contexto já no escuro, e são o matador do M6. **ADV2-23**: a L-16 fecha
— em 2026-09-30, com o `kit.css` de `e597896^`, o conferidor saiu com código 1 e 16 dos 20 SVGs abaixo do piso; com o de
hoje, 0. A evidência vai ao `03`, e a linha permanente com o CSS antigo foi rejeitada (`04`, `## Achados adversariais
rejeitados`).

---

## CT-B06: o diagrama que não cabe na coluna rola na horizontal dentro do próprio bloco, e a página não

*(alterado em 2026-09-29: cenário novo, do step 11 — QA-06; RQ-47 "o que não couber rola na horizontal dentro do
próprio bloco". É de R62 do `04`.)*

**Por que browser**: a rolagem é layout — a largura desenhada do SVG, a do contêiner e a da página depois do CSS.
Mesma passada do conferidor que o CT-B05.

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: O diagrama mais largo que a coluna rola na horizontal dentro do próprio bloco, alcançável até a borda, e a página não rola na horizontal

    Esquema do Cenário: [CT-B06] o excesso de cada diagrama rola dentro do bloco dele
      Dado o site construído, servido pelo astro preview
      E a página "<pagina>" na janela "<janela>", em "<idioma>"
      Quando o conferidor mede a largura desenhada de cada SVG de diagrama, a do contêiner rolável do bloco e a da página
      Então o resultado é "<resultado>"

      Exemplos:
        | pagina                                                                  | janela     | idioma | resultado                                                                                                                                                       | # partição          |
        | cada página com diagrama                                                | 1280 × 900 | pt     | aceita: todo SVG mais largo que a coluna está num contêiner do próprio bloco com rolagem horizontal que alcança a borda direita do SVG, e a página não rola na horizontal | a janela do QA-06   |
        | cada página com diagrama                                                | 390 × 844  | pt     | aceita, idem — e ao menos um diagrama (o DG-11) é mais largo que a coluna                                                                                      | celular, não vácuo  |
        | cada página com diagrama                                                | 390 × 844  | en     | aceita, idem                                                                                                                                                    | idioma              |
        | de controle: um SVG de 2000 px num contêiner sem rolagem               | 390 × 844  | —      | reprova: a página rola na horizontal                                                                                                                             | vazamento           |
        | de controle: um SVG de 2000 px num contêiner com `overflow: hidden`    | 390 × 844  | —      | reprova: a borda direita do SVG é inalcançável                                                                                                                  | corte               |
```

**Roteiro executável** (a mesma passada do CT-B05)

| # | Ação | Código | Resultado visível |
|---|---|---|---|
| 1 | acha o contêiner | do `svg` para cima até o bloco (`pre.mermaid` ou o que o envolve): o primeiro com `getComputedStyle(el).overflowX` em `auto` ou `scroll` | o contêiner, ou nenhum |
| 2 | mede | `svg.getBoundingClientRect().width`, `container.clientWidth`, `container.scrollWidth`; `document.documentElement.scrollWidth` e `window.innerWidth` | as larguras |
| 3 | decide | SVG mais largo que o `clientWidth` do bloco ⇒ existe o contêiner, ele é descendente do bloco, e `scrollWidth ≥` a largura do SVG; e sempre `document.documentElement.scrollWidth ≤ window.innerWidth` | — |
| 4 | não vácuo | na janela 390 × 844, conta os SVG mais largos que a coluna | ≥ 1 |
| 5 | relata | imprime `[CT-B06] rolagem no bloco — {idioma} {janela}: {n} diagramas maiores que a coluna, página sem rolagem` e sai com código ≠ 0 se algo falhar | a linha `[CT-B06]` |

**Assertions**: a página sem rolagem horizontal em toda linha; o contêiner **dentro** do bloco; a âncora de não vácuo
(sem ela, "todo SVG mais largo que a coluna rola" vale no vazio, e vale hoje — o Mermaid encolhe todos); os controles.
**Nasce vermelho** na âncora: hoje nenhum diagrama passa da coluna.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `max-width: none` no SVG sem contêiner rolável: o diagrama vaza da coluna | CT-B06 | "a página não rola na horizontal": `document.documentElement.scrollWidth` passa de `innerWidth` com o DG-11; e a linha de controle "sem rolagem" |
| M2 | `overflow: hidden` no contêiner: a parte além da coluna fica inalcançável | CT-B06 | "a rolagem alcança a borda direita do SVG": o contêiner não rola; e a linha de controle `overflow: hidden` |
| M3 | a rolagem posta num ancestral de todos os blocos — a coluna inteira rola, texto junto —, e não no bloco | CT-B06 | "um contêiner do próprio bloco": o contêiner achado não é descendente do bloco do diagrama |
| M4 | o conserto conferido só onde nada transborda, e o medidor que não acha diagrama largo dá verde | CT-B06 | a âncora de não vácuo na janela 390 × 844: ao menos um diagrama mais largo que a coluna |

*(alterado em 2026-09-30: revisão adversarial da adição, ADV-24 — o passo 3 compara o SVG com o `clientWidth` do
**bloco**, e não da coluna, e o controle `overflow: hidden` fica no próprio contêiner: um `pre.mermaid` que cresce com o
SVG (`width: max-content`) não tem nada a rolar, a página não rola porque um ancestral corta, e este cenário fica verde
com a parte além da coluna inalcançável. É o CT-B09.)*

---

## CT-B07: a escala que entra na fonte efetiva é a que o navegador aplica ao desenho, pela largura ou pela altura

*(alterado em 2026-09-30: cenário novo, da revisão adversarial da adição — ADV-22. É de R62 do `04`.)*

**Por que browser**: o `preserveAspectRatio` (meet, o do Mermaid) encolhe o SVG pelo eixo que limita — e só o layout do
navegador diz qual é. A razão largura/`viewBox` do CT-B05 vê só o eixo x.

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: No site, todo texto de diagrama é desenhado com pelo menos 12 px de fonte efetiva, em qualquer janela, tema e idioma

    Esquema do Cenário: [CT-B07] a fonte efetiva usa a escala real do SVG, limitada pela largura ou pela altura
      Dado o conferidor do CT-B05, com a escala de cada texto lida da matriz da tela do elemento
      E o SVG de controle "<svg>"
      Quando o conferidor mede a menor fonte efetiva do SVG
      Então o resultado é "<resultado>"

      Exemplos:
        | svg                                                                                          | resultado                 | # partição                                         |
        | texto de 16 px, viewBox da largura desenhada, altura limitada por max-height a um quinto da natural | reprova, nomeando 3,2 px | a altura limita: a razão largura/viewBox dá 1   |
        | texto de 16 px, viewBox cinco vezes mais largo que o desenhado                               | reprova, nomeando 3,2 px  | a largura limita (a linha de controle do CT-B05)   |
        | texto de 12 px, escala 1 nos dois eixos                                                      | aceita                    | a borda do piso                                    |
```

**Roteiro executável** (Node + Playwright, na passada do CT-B05)

| # | Ação | Código | Resultado visível |
|---|---|---|---|
| 1 | controles | `page.setContent(...)` com os três SVG, o primeiro dentro de um contêiner com `max-height` | 3 SVG desenhados |
| 2 | mede | para cada texto: `parseFloat(getComputedStyle(el).fontSize) * m.a`, com `m = el.getScreenCTM()` (`text`/`tspan`) ou a do `foreignObject` ancestral (HTML); o mínimo por SVG | 3,2 · 3,2 · 12 |
| 3 | relata | a mesma linha `[CT-B05]`, com os controles do CT-B07 nomeados | — |

**Assertions**: a mesma função de medida do CT-B05, que passa a ler a matriz — os controles e as páginas reais medidos por
ela.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | *(revisão adversarial, ADV-22)* o medidor calcula a escala só por `getBoundingClientRect().width / viewBox.baseVal.width` — o passo 5 do CT-B05 de antes | CT-B07 | linha da altura limitada: "reprova, nomeando 3,2 px"; o mutante lê escala 1 e aceita 16 px |
| M2 | *(ADV-22)* o conserto do piso põe `max-height` nos diagramas altos: a largura fica natural, o SVG encolhe pela altura, e o medidor pela largura dá verde | CT-B07 (+ as linhas reais do CT-B05, medidas pela matriz) | as linhas reais: com a escala da matriz, a fonte efetiva do diagrama alto sai abaixo de 12 |

---

## CT-B08: todo texto de diagrama entra na medida — o de SVG e o HTML de `foreignObject` —, e nenhum SVG passa sem texto medido

*(alterado em 2026-09-30: cenário novo, da revisão adversarial da adição — ADV-23. É de R62 do `04`.)*

**Por que browser**: o Mermaid 11 desenha o rótulo do flowchart em HTML, dentro de `foreignObject`, e a mensagem de
sequência em `<text>`; a fonte computada de cada um só existe depois do desenho e do CSS do site.

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: No site, todo texto de diagrama é desenhado com pelo menos 12 px de fonte efetiva, em qualquer janela, tema e idioma

    Esquema do Cenário: [CT-B08] todo texto do diagrama entra na medida, e nenhum SVG passa sem texto medido
      Dado o conferidor do CT-B05
      E o SVG "<svg>"
      Quando o conferidor mede a menor fonte efetiva do SVG
      Então o resultado é "<resultado>"

      Exemplos:
        | svg                                                                               | resultado                                                                                  | # partição                                    |
        | de controle: só um rótulo HTML de 8 px dentro de foreignObject, nenhum <text>     | reprova, nomeando 8 px                                                                     | o rótulo HTML do flowchart                    |
        | de controle: um <text> de 16 px e um rótulo HTML de 8 px em foreignObject         | reprova, nomeando 8 px                                                                     | o menor, qualquer que seja o tipo de texto    |
        | de controle: um SVG sem texto nenhum                                              | reprova: SVG sem texto medido                                                              | SVG sem texto não é SVG legível               |
        | cada SVG publicado, a 1280 × 900, em pt                                            | aceita, com ao menos um texto medido em cada SVG, e todo foreignObject com texto entre os medidos | não vácuo, por SVG e por tipo de texto |
```

**Roteiro executável** (na passada do CT-B05)

| # | Ação | Código | Resultado visível |
|---|---|---|---|
| 1 | conta | por SVG: os `text`/`tspan` com texto e os `foreignObject` com `textContent.trim()` não vazio | `{svg, textos, foreignObjects}` |
| 2 | mede | a função do CT-B07 sobre `text`, `tspan` e todo elemento com texto dentro de `foreignObject` | o mínimo por SVG |
| 3 | decide | reprova se um SVG tem zero textos medidos, ou se algum `foreignObject` com texto ficou fora da medida | a linha `[CT-B05]` |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | *(revisão adversarial, ADV-23)* o medidor consulta só `text`/`tspan`, e os rótulos HTML do flowchart ficam fora da medida | CT-B08 | linhas de controle com o rótulo HTML de 8 px: "reprova, nomeando 8 px"; o mutante não mede o rótulo e aceita |
| M2 | *(ADV-23)* o SVG sem texto medido dá mínimo vazio, e o vazio passa no piso — o piso de 20 SVGs conta SVGs, não textos | CT-B08 | linha do SVG sem texto: "reprova"; o mutante aceita |
| M3 | *(ADV-23)* uma regra do site sobre `p` reduz os rótulos HTML a menos de 12 px, com o `<text>` das mensagens intacto | CT-B08 | linha real: "todo `foreignObject` com texto entre os medidos" põe o rótulo de `p` na medida, e o mínimo cai abaixo de 12 |

---

## CT-B09: o bloco do diagrama cabe na coluna, e a rolagem dele alcança a borda direita do SVG

*(alterado em 2026-09-30: cenário novo, da revisão adversarial da adição — ADV-24. É de R62 do `04`.)*

**Por que browser**: a caixa do bloco, a da coluna e o que um ancestral corta são layout, depois do CSS.

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: O diagrama mais largo que a coluna rola na horizontal dentro do próprio bloco, alcançável até a borda, e a página não rola na horizontal

    Esquema do Cenário: [CT-B09] o bloco do diagrama cabe na coluna, e a rolagem dele alcança a borda do SVG
      Dado o site construído, servido pelo astro preview
      E a página "<pagina>" na janela "<janela>"
      Quando o conferidor mede a caixa de cada bloco de diagrama, a da coluna de conteúdo e a largura desenhada do SVG
      Então o resultado é "<resultado>"

      Exemplos:
        | pagina                                                             | janela     | resultado                                                                                                                              | # partição                          |
        | cada página com diagrama, em pt                                    | 390 × 844  | aceita: a borda direita de cada bloco fica dentro da coluna, e o scrollWidth do contêiner rolável do bloco alcança a largura do SVG       | a coluna do celular                 |
        | cada página com diagrama, em en                                    | 1280 × 900 | aceita, idem                                                                                                                           | janela larga                        |
        | de controle: pre.mermaid com width: max-content, num ancestral com overflow-x: hidden | 390 × 844 | reprova: a borda direita do bloco passa da coluna                                                                         | o bloco que cresce e o ancestral corta |
        | de controle: idem, com overflow-x: clip no ancestral               | 390 × 844  | reprova, idem                                                                                                                          | clip                                |
```

**Roteiro executável** (na passada do CT-B06)

| # | Ação | Código | Resultado visível |
|---|---|---|---|
| 1 | acha a coluna | o ancestral `.sl-markdown-content` do bloco (Seletores) | a coluna |
| 2 | mede | `bloco.getBoundingClientRect().right` contra `coluna.getBoundingClientRect().right`; `contêiner.scrollWidth` contra a largura desenhada do SVG | as larguras |
| 3 | decide | toda borda de bloco dentro da coluna (1 px de tolerância de arredondamento), e o `scrollWidth` do contêiner ≥ a largura do SVG | a linha `[CT-B06]` |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | *(revisão adversarial, ADV-24)* `pre.mermaid { width: max-content }`: o bloco cresce com o SVG, nada rola dentro dele, e um ancestral com `overflow-x: hidden` corta o que passa da coluna | CT-B09 | linha de controle do `max-content`: "a borda direita do bloco passa da coluna"; o mutante passa pelo CT-B06, que compara o SVG com o próprio bloco |
| M2 | *(ADV-24)* idem, com `overflow-x: clip` no ancestral | CT-B09 | linha do `clip`: reprova |

---

## CT-B10: cada quadro do `install.gif` mostra o seu último passo, e não o do quadro seguinte

*(alterado em 2026-09-30: cenário novo, da revisão adversarial da adição — ADV-32. É de R35 do `04`, e roda no mesmo
`it()` de captura do CT-B04.)*

**Por que browser**: o que o quadro mostra é o recorte renderizado pela fixture na janela de captura. O CT-B04 afirma o
título, as duas ausências, a janela dentro da tela e o PNG diferente do anterior — um recorte deslocado de um marcador
passa por todas: o quadro da senha perde justamente o passo da senha aleatória, e os quatro PNG continuam diferentes.
O recorte é `array_slice()` até o índice de `$ate`, inclusive
(`tests/Browser/Fixtures/terminal-instalacao.blade.php:array_slice:69`), e o índice de cada quadro está no teste
(`tests/BrowserTenancy/CapturaDeArteTest.php:'instalacao-2-senha':806`).

```gherkin
# language: pt
Funcionalidade: GIFs pelo kit:arte

  Regra: Cada quadro do install.gif é a transcrição do kit:install recortada até o seu ponto, inteira na área fotografada, e nenhum mostra password

    Esquema do Cenário: [CT-B10] cada quadro do install.gif mostra o seu último passo e não o do quadro seguinte
      Dado a fixture da transcrição da instalação recortada para o quadro "<quadro>", servida por uma rota registrada só no teste
      Quando o cenário de captura abre o quadro na janela de captura
      Então a página mostra "<mostra>"
      E não mostra "<nao_mostra>"

      Exemplos:
        | quadro                 | mostra                                                       | nao_mostra                                       |
        | instalacao-1-inicio    | Rodando migrations                                           | Gerando senha do administrador                   |
        | instalacao-2-senha     | Gerando senha do administrador                               | Populando papéis, permissões e usuário inicial   |
        | instalacao-3-progresso | Removendo o vínculo com o Snyk do kit                        | Pronto! O projeto está instalado.                |
        | instalacao-4-resumo    | Login inicial: admin@example.com / XXXXXXXXXXXXXXXXXXXXXXXX  | admin@example.com / password                     |
```

**Roteiro executável**: os passos 1–3 do CT-B04 e, antes do screenshot, `->assertSee($mostra)->assertDontSee($naoMostra)`.
O `assertSee` do marcador vem antes da ausência, pelo mesmo motivo do título no CT-B04.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | *(revisão adversarial, ADV-32)* o recorte sai um marcador antes — o índice de `$ate` contado a partir de 1, ou o `array_slice()` sem o `+ 1` —, e o quadro da senha para antes de "Gerando senha do administrador" | CT-B10 | linha `instalacao-2-senha`: "mostra 'Gerando senha do administrador'"; o mutante não mostra, e os quatro PNG continuam diferentes entre si |
| M2 | *(ADV-32)* o recorte sai um marcador depois, e o quadro 1 já mostra a geração da senha | CT-B10 | linha `instalacao-1-inicio`: "não mostra 'Gerando senha do administrador'" |
| M3 | *(ADV-32)* o quadro do resumo sai sem a linha do login mascarado | CT-B10 | linha `instalacao-4-resumo`: "mostra 'Login inicial: … / XXXX…'" |

---

## CT-B11: a troca de tema com a página aberta não desfaz o piso de fonte efetiva

*(alterado em 2026-09-30: cenário novo, da revisão adversarial da adição — ADV-34. É de R62 do `04`; o M2 do CT-B05 com
o evento no `Quando`.)*

```gherkin
# language: pt
Funcionalidade: Diagramas da arquitetura no site

  Regra: No site, todo texto de diagrama é desenhado com pelo menos 12 px de fonte efetiva, em qualquer janela, tema e idioma

    Esquema do Cenário: [CT-B11] a troca de tema com a página aberta não desfaz o piso
      Dado o site construído, e a página "<pagina>" aberta no tema claro, na janela "<janela>", com todo bloco desenhado
      Quando o leitor troca o tema para escuro
      Então, depois do redesenho, a menor fonte efetiva de cada SVG é de pelo menos 12 px, e são 20 SVGs medidos

      Exemplos:
        | pagina                           | janela     | # partição           |
        | cada página com diagrama, em pt  | 1280 × 900 | a janela do QA-06    |
        | cada página com diagrama, em en  | 390 × 844  | celular, en          |
```

**Roteiro executável**: os passos 2–4 do CT-B05 — a troca pelo `data-theme` e a espera do SVG **novo** —, e o passo 5 com
a medida do CT-B07 e do CT-B08.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | *(revisão adversarial, ADV-34; o M2 do CT-B05)* o conserto não sobrevive ao redesenho da troca de tema: o SVG novo volta com o `max-width` inline e encolhe | CT-B11 | "depois do redesenho, a menor fonte efetiva … de pelo menos 12 px": o mutante encolhe o SVG novo |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o requisito pediu | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | diagramas renderizados no site (RQ-12, RQ-18, RQ-32) | `astro-mermaid` + `mermaid` 11.17.2; 20 blocos por idioma em `docs/`, todos SVG, sem erro | ✅ | step 10, 2026-09-29: `npm ci && npm run build` (69 páginas), `node verifica-links.mjs` (2.584 links, 0 quebrados) e `node verifica-acessibilidade.mjs` com o `astro preview` de pé: `[CT-B01] SVGs conferidos — pt: claro=20 escuro=20 (fonte=20); en: claro=20 escuro=20 (fonte=20)`, `OK — nenhuma violacao serious/critical de WCAG 2.1 AA`, exit 0 |
| 2 | "segue o tema claro/escuro" (Adendo 2), com contraste AA nos dois (RQ-33) | tema pelo `data-theme` (`autoTheme`); fundo do rótulo de aresta escurecido no tema escuro em `site/src/styles/kit.css` | ✅ | mesma execução: `[CT-B02] DG-01 claro: fill=rgb(236, 236, 255) texto=rgb(51, 51, 51)` → `escuro: fill=rgb(31, 32, 32) texto=rgb(204, 204, 204)`, com re-render na troca; axe sem `color-contrast` serious |
| 3 | o mesmo bloco renderiza no GitHub (Adendo 2) | o DG-01 do README é o mesmo bloco da página (CT-36); mesma versão do Mermaid (CT-38) | ⚠️ | procuração só: o GitHub não é alcançável pelo navegador de teste (L-03). Conferência manual no PR, que o step 11 registra — **não feita aqui** |
| 4 | GIFs de busca ⌘K, login unificado, densidade, import/export (RQ-27) | cinco clipes no `KitArte::CLIPES`; os quatro PNG novos capturados pelo CT-B03 | ✅ | `KIT_ART=1 vendor/bin/pest tests/BrowserTenancy/CapturaDeArteTest.php --filter="CT-B03"` → 2/2, 10 asserções, 37 s, 2026-09-29; `busca-spotlight-2-aberta.png` e `login-unificado-2-escolha.png` abertos e conferidos no olho (overlay com o resultado "Contrato de fornecimento 2026"; os cartões Administração e Infraestrutura). Os GIFs publicados são os de `2ab9bfa` (`ls -l art/*.gif`) — o `kit:arte` não foi rodado de novo aqui |
| 5 | `install.gif` sem `password` (RQ-28) | fixture com a transcrição real, senha mascarada com 24 `X`; `assertDontSee('/ password')` e `assertDontSee('password (padrão do kit)')` antes de cada quadro | ⚠️ | a fonte é travada pelo CT-52 e pelo `assertDontSee`; o pixel do GIF é lacuna L-04 — `grep -n -i "password" tests/Browser/Fixtures/terminal-instalacao.blade.php` só acha o comentário que proíbe a palavra (linha 11) |
| 6 | *(step 11)* cada quadro do `install.gif` inteiro na tela e sem `password` (RQ-28, RQ-27; QA-03) | o `it('[CT-B04][CT-B10] captura os quadros do instalador')` (`tests/BrowserTenancy/CapturaDeArteTest.php:'[CT-B04]':794`), com a altura da janela medida no quadro final (QA-13) | ✅ | step 10 do ciclo 2, 2026-09-29: `KIT_ART=1 vendor/bin/pest tests/BrowserTenancy/CapturaDeArteTest.php --filter="CT-B0[34]" --no-tia --compact` → 3/3, 22 asserções, 8,5 s; os quatro PNG têm hashes diferentes (`md5sum tests/Browser/Screenshots/instalacao-*.png`, conferido por comando, não pelo teste). *(alterado em 2026-09-30: `KIT_ART=1 XDEBUG_MODE=off vendor/bin/pest tests/BrowserTenancy/CapturaDeArteTest.php --filter="CT-B04|CT-B10" --no-tia --compact` → `{"tool":"pest","result":"passed","tests":1,"passed":1,"assertions":27,"duration_ms":10917}`; o `it('[CT-B04][CT-B10] captura os quadros do instalador')` faz o `assertScript` da `.janela` dentro de `innerHeight` (passo 5) e o md5 de cada PNG diferente do anterior (passo 7), e o `assertSee($mostra)->assertDontSee($naoMostra)` dos quatro quadros antes do screenshot; hashes distintos: `85d326…`, `a24639…`, `faeaef…`, `ad456d…`)* |
| 7 | *(step 11)* nenhum diagrama do site abaixo de 12 px de fonte efetiva (RQ-47, RQ-49; QA-06) | o SVG no tamanho natural, com a largura cortada pelo `max-width` do próprio Mermaid, em `site/src/styles/kit.css` (ADR-02) | ✅ | step 10 do ciclo 2, 2026-09-29: `npm run build` (69 páginas) e `node verifica-acessibilidade.mjs 4399` contra o `astro preview`, exit 0: `[CT-B05] fonte efetiva minima` de 14,0 px (DG-13), 20 SVGs medidos, em pt 1280×900 claro e escuro, en 1280×900 claro, pt 390×844 claro e en 390×844 escuro; os controles do CT-B05 (`viewBox 5x: 3.2 px`, `borda do piso: 12.0 px`) passam *(alterado em 2026-09-30: as sete combinações a 14,0 px (DG-13), 20 SVGs, incluindo `pt 1280x900 escuro (desde o carregamento)` e `en 390x844 escuro (desde o carregamento)` (ADV2-15); e a execução **vermelha** (L-16): com o `site/src/styles/kit.css` de `e597896^`, `npm run build` e o conferidor saíram com exit 1 — `[CT-B05] fonte efetiva minima - pt 1280x900 claro: 2.2 px (DG-11), 20 SVGs medidos`, 125 violações (88 `[CT-B05] abaixo do piso`, 36 `[CT-B11]`, 1 `[CT-B06] ancora de nao vacuo`), 16 dos 20 SVGs abaixo do piso em pt 1280×900; com o `kit.css` de HEAD de volta, exit 0)* |
| 8 | *(step 11)* o que não cabe rola dentro do bloco, e a página não (RQ-47, RQ-48; QA-06) | o `overflow: auto` do `pre.mermaid` que o `astro-mermaid` já põe, com o alinhamento ao início (`site/src/styles/kit.css`); nenhum bloco mudou | ✅ | mesma execução: `[CT-B06] rolagem no bloco - celular (390x844): 40 diagramas maiores que a coluna, pagina sem rolagem: true`, e os dois controles do CT-B06 passam |
| 9 | *(revisão adversarial da adição, 2026-09-30)* a fonte efetiva com a escala real, pela largura ou pela altura (RQ-47; ADV-22) | — | ✅ | *(alterado em 2026-09-30: `node verifica-acessibilidade.mjs 4399` contra o `astro preview` do build de 2026-09-30, exit 0: `[CT-B07] controles - altura limita: 3.2 px; largura limita: 3.2 px; borda do piso: 12.0 px`; a escala vem de `getScreenCTM()` (matriz da tela), em `site/verifica-acessibilidade.mjs`)* |
| 10 | *(idem)* todo texto medido, o HTML de `foreignObject` incluído, e nenhum SVG sem texto medido (RQ-47; ADV-23) | — | ✅ | *(alterado em 2026-09-30: `node verifica-acessibilidade.mjs 4399` contra o `astro preview` do build de 2026-09-30, exit 0: `[CT-B08] controles - so rotulo HTML de 8px: 8.0 px; <text> 16px + HTML 8px: 8.0 px; SVG sem texto reprova: true` e `[CT-B08] textos medidos - SVGs com rotulo HTML de foreignObject: 91, com <text>/<tspan>: 49, sem texto medido ou foreignObject fora da medida: 0`)* |
| 11 | *(idem)* o bloco dentro da coluna, com a rolagem até a borda do SVG (RQ-47; ADV-24) | — | ✅ | *(alterado em 2026-09-30: `node verifica-acessibilidade.mjs 4399` contra o `astro preview` do build de 2026-09-30, exit 0: `[CT-B09] controles - max-content + overflow-x:hidden reprova: true; max-content + overflow-x:clip reprova: true` e `[CT-B09] bloco dentro da coluna e rolagem ate a borda do SVG: 0 violacao(oes)`)* |
| 12 | *(idem)* cada quadro do `install.gif` com o seu último passo e sem o do seguinte (RQ-28, RQ-27; ADV-32) | — | ✅ | `KIT_ART=1 XDEBUG_MODE=off vendor/bin/pest tests/BrowserTenancy/CapturaDeArteTest.php --filter="CT-B04|CT-B10" --no-tia --compact` → `{"tool":"pest","result":"passed","tests":1,"passed":1,"assertions":27,"duration_ms":10917}`; o `it('[CT-B04][CT-B10] captura os quadros do instalador')` (`tests/BrowserTenancy/CapturaDeArteTest.php:'[CT-B04]':794`) confere o `assertSee($mostra)->assertDontSee($naoMostra)` dos quatro quadros antes do screenshot e o md5 de cada PNG diferente do anterior; hashes distintos: `85d326…`, `a24639…`, `faeaef…`, `ad456d…` *(alterado em 2026-09-30: evidência de 2026-09-30)* |
| 13 | *(idem)* o piso depois da troca de tema com a página aberta (RQ-47, P-18; ADV-34) | — | ✅ | `[CT-B11] troca de tema - pt 1280x900: depois do redesenho, 0 SVG(s) abaixo de 12 px, 20 SVGs medidos` e a mesma linha para `en 390x844` (`node verifica-acessibilidade.mjs 4399`, exit 0, 2026-09-30) *(alterado em 2026-09-30: preenchido)*; a linha 7 ganhou a execução **vermelha** do CT-B05 com o `kit.css` de `e597896^` (L-16 do `04`, ADV-36) *(alterado em 2026-09-30: rodada 2, ADV2-23 — a execução vermelha foi feita em 2026-09-30: exit 1, 16 dos 20 SVGs abaixo do piso; com o `kit.css` de hoje, exit 0; o registro com o comando vai ao `03`, e a L-16 fecha)* |
| 14 | *(re-revisão adversarial da adição, 2026-09-30)* o piso com o site aberto já no escuro, sem troca (RQ-47; ADV2-15) | — | ✅ | as duas linhas novas do CT-B05 passam a 14,0 px, 20 SVGs: `pt 1280x900 escuro (desde o carregamento)` e `en 390x844 escuro (desde o carregamento)` (`node verifica-acessibilidade.mjs 4399`, exit 0; ver a linha 7) *(alterado em 2026-09-30: preenchido)* |

*(alterado em 2026-09-29: preenchido no step 10; as duas linhas ⚠️ são as lacunas L-03 e L-04 do `04`,
declaradas desde a derivação, e não divergência entre o desenhado e o implementado. As linhas 6 a 8 são do step 11, e
se preenchem depois da execução)* *(alterado em 2026-09-29: preenchidas no step 10 do ciclo 2; a linha 6 fica ⚠️ porque o
teste do CT-B04 ainda não faz os passos 5 e 7 do roteiro dele)* *(alterado em 2026-09-30: a linha 6 passou a ✅ — o `it('[CT-B04][CT-B10] captura os quadros do instalador')` faz os passos 5 e 7 e o CT-B10; as linhas 9 a 14 são da revisão adversarial da adição e foram preenchidas com a execução de 2026-09-30, verde, e a linha 7 ganhou a execução vermelha do CT-B05 com o `kit.css` de `e597896^` (L-16). As duas ⚠️ que ficam são L-03 e L-04)*
