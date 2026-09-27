# Casos de Teste de Browser — Diagramas da arquitetura do kit no README e no site

> Derivado do `00-requisito.md` (RQ-12, RQ-18, RQ-27), como o `04`.
>
> **Dois runtimes, e o motivo**:
>
> - **CT-B01, CT-B02** — conferidor Node + Playwright de `site/`, sobre o site **construído** e servido
>   pelo `astro preview`. O `pest-plugin-browser` sobe a aplicação Laravel em processo e não serve site
>   estático de outro toolchain (`site/verifica-acessibilidade.mjs:pest-plugin-browser:6`) — é o mesmo
>   arranjo do conferidor de acessibilidade, e o Pest trava que o fluxo o executa (`[CT-37]` do `04`,
>   irmão de `[CT-42]`). Divergência do template da skill, declarada no `04`.
> - **CT-B03** — `pest-plugin-browser`, dentro de `tests/BrowserTenancy/CapturaDeArteTest.php`, sob
>   `KIT_ART=1` (`tests/BrowserTenancy/CapturaDeArteTest.php:KIT_ART:58`). Roda no `composer art`, não
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
> # CT-B03
> composer art                         # npm run build + view:cache + KIT_ART=1 + kit:arte
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

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| renderização no **GitHub** | o GitHub não é alcançável pelo navegador de teste; procuração por CT-36 + CT-38 do `04`, conferência manual no PR (lacuna L-03) |
| acessibilidade do SVG como cenário próprio | já é do conferidor de acessibilidade existente, desde que ele rode **depois** do SVG — isso é CT-B01 |
| smoke de cada página com diagrama em lote | mata o mesmo conjunto de CT-B01, que já percorre todas |
| tela de convite, conta, login etc. no app | nenhuma muda (recorte: "Nenhuma mudança de UI"); comportamento já provado em `tests/Browser/LoginUnificadoTest.php` e `tests/Browser/RoteiroDoKitTest.php` |
| o `install.gif` renderizado da fixture | pixel; a fonte é travada por CT-52 do `04`, o resto é lacuna L-04 |

## Pré-requisitos

- [ ] `npm ci` em `site/` com o lock que resolve `mermaid` 11.17.2 (CT-38 do `04`)
- [ ] `npm run build` em `site/` — sem `dist/` o conferidor tem piso e reprova (não fica verde sobre o vazio)
- [ ] `npx playwright install chromium` (já é passo do `pages.yml`)
- [ ] CT-B03: `npm run build` e `view:cache` na raiz (o `composer art` embute os dois), `KIT_ART=1`
- [ ] CT-B03: aquecimento pelo kernel antes do `visit()` (`.ai/rules/testes-browser.md`, "aqueça pelo kernel") e o painel arranjado **no próprio cenário**, nunca no `beforeEach` (mesma rule)

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| blocos da fonte de cada página | contagem de cercas ```` ```mermaid ```` no `.md` correspondente em `docs/` | sim (o conferidor lê o Markdown) |
| SVG que a integração produz para cada bloco | `svg[aria-roledescription]` | **a confirmar no DOM do build** — o pacote não está instalado e não há citação de vendor; registrar o seletor real no `03` |
| SVG de erro do Mermaid | o mesmo seletor com `aria-roledescription="error"`, ou o texto `Syntax error` dentro do SVG | a confirmar, idem |
| troca de tema | `document.documentElement.setAttribute('data-theme', t)` — o jeito que o conferidor já usa (`site/verifica-acessibilidade.mjs:setAttribute('data-theme':78`) | sim |
| overlay da busca ⌘K | `input[placeholder="Buscar registros e telas..."]` e `[x-on\:open-spotlight\.window]` (`tests/Browser/RoteiroDoKitTest.php`, F-45) | sim |
| escolha de painel | `assertPathIs('/login/painel')` + `a[href$="/login/painel/{id}"]` (`tests/Browser/LoginUnificadoTest.php`, CT-B01 de `feat/login-unificado`) | sim |

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

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `astro-mermaid` registrado depois do Starlight — o Expressive Code pega a cerca e ela vira bloco de código | CT-B01 (contagem 0 ≠ N) |
| M2 | bloco com sintaxe inválida (rótulo com parênteses sem aspas) — o Mermaid desenha um SVG **de erro**, e "tem SVG" passaria | CT-B01 (SVG de erro / "Syntax error") |
| M3 | axe em `domcontentloaded` (`site/verifica-acessibilidade.mjs:domcontentloaded:77`), sobre o `<pre>` ainda não renderizado | CT-B01 (passo 4 antes do 6) |
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
        | login unificado: painel escolhido | admin + infra (global) | caminho /admin com "Painel de Controle"                              |
```

**Roteiro executável** (Pest, `pest-plugin-browser`)

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `$this->actingAs($usuario); $this->get('/admin');` (aquece fora do cronômetro) | — |
| 2 | busca | `visit('/admin')->click('.fi-global-search-field')->assertVisible('input[placeholder="Buscar registros e telas..."]')` | overlay aberto |
| 3 | quadro | `->screenshot(fullPage: false, filename: '{quadro}')` **depois** do passo 2 | PNG com o overlay |
| 4 | escolha | `visit('/login')->fill('#form\.email', …)->fill('#form\.password', 'password')->press('Login')->assertPathIs('/login/painel')->assertSee('Administração')` | tela de escolha |
| 5 | quadro | `->screenshot(fullPage: false, filename: '{quadro}')` | PNG da escolha |

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

## Roteiro de Validação: Desenhado × Implementado

| # | O que o requisito pediu | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | diagramas renderizados no site (RQ-12, RQ-18) | | | saída do conferidor (20 SVGs por idioma e tema) |
| 2 | "segue o tema claro/escuro" (Adendo 2) | | | medidas do CT-B02 coladas no `03` |
| 3 | o mesmo bloco renderiza no GitHub (Adendo 2) | | | print do README e da página no GitHub (L-03) |
| 4 | GIFs de busca ⌘K, login unificado, densidade, import/export (RQ-27) | | | os quatro GIFs abertos e conferidos, barra lateral inclusive |
| 5 | `install.gif` sem `password` (RQ-28) | | | quadro do GIF com a senha gerada (L-04) |
