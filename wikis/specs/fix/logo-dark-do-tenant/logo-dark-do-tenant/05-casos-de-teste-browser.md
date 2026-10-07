# Casos de Teste de Browser — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "G6 Swap no navegador" de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/BrowserTenancy/IdentidadeVisualTest.php` (em série — nunca `--parallel`). `phpunit.xml` não declara suíte `Browser` para esta pasta: o caminho do arquivo é o filtro (conferir com `grep -n '<testsuite name="Browser"' phpunit.xml`).
> Perfil **padrão**: teto de 1 happy path. O cenário é um `Esquema do Cenário` (conta 1) com as duas formas da marca (simples e composição, CSS de swap **diferente**) e os dois sentidos da alternância (claro para escuro e escuro para claro); um happy path só deixaria vivo o mutante que escolhe a imagem uma vez.

## Pré-requisitos

- [ ] `npm run build` executado (sem `public/build/manifest.json` toda tela responde `ViteException`)
- [ ] `tests/Browser/Screenshots` no `.gitignore` (nenhuma captura é obrigatória neste CT-B; ver a nota abaixo)
- [ ] Autenticação por `$this->actingAs($usuario)` **antes** do `visit()` (o servidor do plugin é in-process: mesma sessão), com `usuarioComPapel('panel_user', $acme)` + `$usuario->tenants()->attach($acme->id)`
- [ ] Disco `public` **real**, não `Storage::fake()`: o navegador faz request HTTP à URL da logo. Arquivos com nome que não colide com upload de ninguém (`organizacoes/logos/acme-teste-browser.png`, `organizacoes/logos/acme-dark-teste-browser.png`, e para a instalação `kit/logo-teste-browser.png` e `kit/logo-dark-teste-browser.png`), PNG 1x1 válido (a imagem precisa **carregar** para `naturalWidth` valer), apagados no `afterEach` do arquivo
- [ ] O cenário arranja o painel e **aquece pelo kernel** com um `$this->get('/app/acme')` antes do `visit()` (regra de `testes-browser.md`: view e componentes aquecidos); o `beforeEach` só semeia
- [ ] Gravar a identidade da instalação e o cabeçalho antes de aquecer: marca separada, par da instalação, composição conforme a linha do esquema (`gravarConfiguracao()` + `alinharConfiguracoesDoKit()`, mesma cadeia do `04`)

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| imagem da variante clara, marca simples | `img.fi-logo.fi-logo-light` | sim (classes nativas do swap do Filament) |
| imagem da variante escura, marca simples | `img.fi-logo.fi-logo-dark` | sim (idem) |
| imagem clara na composição | `.kit-cabecalho img.fi-logo-light` | sim (`resources/views/filament/cabecalho-do-painel.blade.php`) |
| imagem escura na composição | `.kit-cabecalho img.fi-logo-dark` | sim (idem) |
| raiz com a classe de tema | `html.dark` | sim (o Filament a fixa no load e a alterna pelo seletor de tema) |
| seletor de tema do Filament | o botão do alternador de tema (`fi-theme-switcher`, no menu do usuário; botões por `aria-label`/`x-on:click="setTheme(...)"`) | sim; o seletor exato é medido pelo executor (dívida registrada no `03`, não adivinhado aqui) |

Há **duas** marcas por página (barra lateral e barra superior): os seletores casam mais de um elemento. A asserção de visibilidade é sobre **toda** imagem visível da marca (`getComputedStyle` e `offsetParent`), nunca `assertVisible` solto em seletor que casa as duas.

---

## CT-B01: o topo do `/app` da Acme troca a imagem visível quando o tema é alternado, sem recarregar

**Por que browser e não Livewire/HTTP**: o HTML (CT-42, CT-45) prova que as duas `<img>` estão lá com as URLs certas, **não** qual está visível, nem que a visível troca quando o tema muda **depois** do load. O swap é a classe `dark` no `<html>` (alternada por JS) mais a regra CSS que oculta a `.fi-logo-dark` no claro e a `.fi-logo-light` no escuro. Só `getComputedStyle` no navegador falsifica "as duas visíveis", "a clara continua visível no escuro" (a queixa do requisito), "a imagem escolhida uma vez no load" e "a `<img>` quebrada".

```gherkin
# language: pt
Funcionalidade: A logo da organização, clara e escura, no topo do `/app`

  Regra: a imagem visível do topo troca com o tema, também quando ele é alternado sem recarregar

    Esquema do Cenário: [CT-B01] o topo do /app da Acme troca para a logo do tema novo quando o tema é alternado
      Dado a marca separada, com o par próprio da Acme e outro, distinto, da instalação
      E a composição do cabeçalho <composicao>
      E a pessoa abriu /app/acme no tema <inicial> e viu a imagem <inicial_logo> da Acme, carregada
      Quando ela alterna o tema para <final> pelo seletor de tema, sem recarregar a página
      Então toda imagem visível da marca termina no arquivo <final_logo> da Acme, carregada
      E nenhuma imagem "<oculta>" da marca está visível

      Exemplos:
        | composicao                          | inicial | inicial_logo | final  | final_logo | oculta          | # partição                    |
        | desligada                           | claro   | clara        | escuro | escura     | fi-logo-light   | marca simples, claro→escuro   |
        | desligada                           | escuro  | escura       | claro  | clara      | fi-logo-dark    | marca simples, escuro→claro   |
        | ligada, só o segmento logo da marca | claro   | clara        | escuro | escura     | fi-logo-light   | composição, claro→escuro      |
        | ligada, só o segmento logo da marca | escuro  | escura       | claro  | clara      | fi-logo-dark    | composição, escuro→claro      |
```

**Roteiro executável** (a emulação de `prefers-color-scheme` só vale no **load**: o `theme.js` do Filament fixa `dark` no `DOMContentLoaded`; por isso o tema **inicial** vem de `inLightMode()`/`inDarkMode()` no `visit()`, e a troca do `Quando` é feita **no mesmo documento**, pelo seletor do Filament, que é o que o usuário faz)

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjar | `noPainelDa($acme)` ou a sessão por `actingAs()`, identidade e cabeçalho gravados, arquivos no disco real | painel pronto |
| 2 | aquecer pelo kernel | `$this->get('/app/acme')->assertSuccessful()` | views compiladas, sem o custo de 25 s dentro do navegador |
| 3 | visita no tema inicial | `visit('/app/acme')->inLightMode()` (ou `inDarkMode()`) | topo com a logo |
| 4 | `assertPathIs` primeiro | `->assertPathIs('/app/acme')` | espera a navegação |
| 5 | controle: a visível é a do tema inicial | `->assertScript(<toda imagem visível da marca termina em "acme-teste-browser.png" (ou "acme-dark-teste-browser.png") e naturalWidth > 0>)` | a organização, no tema inicial |
| 6 | alternar o tema | `->click(<seletor de tema do Filament>)` (sem `visit()` de novo) | `<html>` troca a classe `dark` |
| 7 | final: a visível é a do tema novo | `->assertScript(<toda imagem visível da marca termina no arquivo do tema final, com naturalWidth > 0>)` | a organização, no tema final |
| 8 | final: a oposta está oculta | `->assertScript(<nenhuma img da classe oposta visível>)` | `display: none` |

**Assertions**: `assertPathIs` primeiro · visibilidade **computada** (`getComputedStyle(el).display` e `offsetParent`, nunca `assertSee`), sobre **toda** imagem visível da marca (as duas marcas da página) · o `src` **da organização**, distinto do da instalação, em cada tema (a organização é o oráculo, a instalação é o que o mutante mostraria) · `naturalWidth > 0` (imagem carregada) · o controle do passo 5 antes da troca · `assertNoJavaScriptErrors()` como apoio, **não** `assertNoSmoke()` (tela de plugin de terceiro) · sem `wait($s)`: o plugin retenta as assertions.

> Nenhuma captura de tela é produto deste CT-B (diferente do `[CT-B01]` da `logo-dark-mode`, que também gerava os assets de `art/`): nenhuma linha em `KitArte::IMAGENS`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M36 | o CSS do kit deixa as duas imagens da composição visíveis em qualquer tema | CT-B01 (linhas `ligada`) | no tema claro nenhuma `.fi-logo-dark` visível; o mutante a deixa visível |
| M37 | a marca simples só tem a clara da organização (a escura não chega): no tema escuro continua visível a clara — a queixa do requisito | CT-B01 (linhas `desligada`, claro→escuro) | no tema escuro toda imagem visível termina em "acme-dark-teste-browser.png"; o mutante mostra "acme-teste-browser.png" |
| M38 | as classes de swap invertidas: a clara no escuro | CT-B01 (as quatro linhas) | no tema escuro a visível é a escura e a `.fi-logo-light` está oculta; o mutante mostra a clara |
| M39 | a URL da logo da organização não é servida ao navegador (`Storage::url()` no lugar de `asset()`: o host é `localhost` do `APP_URL`, o plugin serve em `127.0.0.1:porta`) e a `<img>` quebra | CT-B01 | `naturalWidth > 0` na imagem visível de cada tema; o mutante tem a `<img>` quebrada (`naturalWidth` 0) com o `src` certo no HTML |
| M48 | a imagem é escolhida **uma vez**: pelo servidor (a classe do tema do request) ou por um `init` do Alpine no load, e não reage à alternância do tema depois *(revisão adversarial, ADV-03/ADV-22/ADV-23)* | CT-B01 (as quatro linhas, sentidos claro→escuro e escuro→claro) | depois do clique no seletor, toda imagem visível termina no arquivo do tema novo; o mutante mantém a do tema inicial |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B da tela de bloqueio com a organização | já existe: `exibe a logo da organizacao na tela de bloqueio` em `tests/BrowserTenancy/IdentidadeVisualTest.php` (P-01, sem alteração) |
| CT-B da organização sem logo (queda para a instalação) no navegador | o `src` da queda é provado em HTTP (CT-43, CT-46); o navegador só acrescentaria o swap, que CT-B01 já prova para a imagem da organização |
| CT-B do `/admin` sem logo de organização | já provado em HTTP (CT-51); não depende de JS |
| segundo CT-B com erro visível | o teto do perfil padrão é 1 happy path; os mutantes de erro visível (M37, M39) já morrem nas linhas do esquema |
| screenshot do topo em cada tema | `assertScreenshotMatches()` cria o baseline com o defeito; a prova é `getComputedStyle` |
| atualização Livewire da barra superior no navegador | CT-53 prova o par devolvido por componente; o swap é CSS, independente do redesenho |

## Roteiro de Validação: Desenhado × Implementado

<!-- Preenchido no step 10 (execução dos CT-B contra a UI real). -->

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | no `/app/{slug}` com organização aberta, a marca simples tem `fi-logo-light` e `fi-logo-dark` com o par da organização | a preencher | a preencher | a preencher |
| 2 | na composição, o par da organização dentro de `.kit-cabecalho`, com um só `fi-logo-dark` | a preencher | a preencher | a preencher |
| 3 | o seletor de tema do Filament alterna a imagem visível sem recarregar | a preencher | a preencher | a preencher |
