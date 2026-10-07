# Casos de Teste de Browser — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "G6 Swap no navegador" de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/BrowserTenancy/IdentidadeVisualTest.php` (em série — nunca `--parallel`). `phpunit.xml` não declara suíte `Browser` para esta pasta: o caminho do arquivo é o filtro (conferir com `grep -n '<testsuite name="Browser"' phpunit.xml`).
> Perfil **padrão**: teto de 1 happy path. O cenário é um `Esquema do Cenário` de duas linhas (conta 1), porque a marca simples e a composição têm CSS de swap **diferente** (a `.fi-logo-dark` nativa do Filament contra as imagens da composição, com classes do kit), e um happy path só deixaria viva a metade.

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
| raiz com a classe de tema | `html.dark` | sim (o Filament a fixa no load, lendo a preferência no `DOMContentLoaded`) |

Há **duas** marcas por página (barra lateral e barra superior): os seletores casam mais de um elemento. A asserção de visibilidade é sobre o **primeiro visível** de cada tema (`assertScript` com `getComputedStyle` e `offsetParent`), nunca `assertVisible` solto em seletor que casa as duas.

---

## CT-B01: o topo do `/app` da Acme mostra a logo clara da organização no tema claro e a escura dela no tema escuro

**Por que browser e não Livewire/HTTP**: o HTML (CT-42, CT-45) prova que as duas `<img>` estão lá com as URLs certas, **não** qual está visível. O swap é a classe `dark` no `<html>` (fixada por JS no load) mais a regra CSS que oculta a `.fi-logo-dark` no claro e a `.fi-logo-light` no escuro. Só `getComputedStyle` no navegador falsifica "as duas visíveis", "a clara continua visível no escuro" (a queixa do requisito) e "a `<img>` quebrada" (URL não servida ao navegador, onde o corpo HTTP não distingue).

```gherkin
# language: pt
Funcionalidade: A logo da organização, clara e escura, no topo do `/app`

  Regra: a imagem visível do topo troca com o tema

    Esquema do Cenário: [CT-B01] o topo do /app da Acme mostra a logo clara no tema claro e a escura no tema escuro
      Dado a marca separada, com o par próprio da Acme e outro, distinto, da instalação
      E a composição do cabeçalho <composicao>
      Quando uma pessoa com papel na Acme abre /app/acme no tema claro, e outra vez no tema escuro
      Então no tema claro a imagem visível do topo é a clara da Acme, carregada, e a escura está oculta
      E no tema escuro a imagem visível do topo é a escura da Acme, carregada, e a clara está oculta

      Exemplos:
        | composicao                          | # partição    |
        | desligada                           | marca simples |
        | ligada, só o segmento logo da marca | composição    |
```

**Roteiro executável** (a emulação de `prefers-color-scheme` só vale no **load** da página: o `theme.js` do Filament fixa `dark` no `<html>` no `DOMContentLoaded`, então `inDarkMode()` no meio da sessão não reavalia — uma visita por tema, como no `[CT-B01]` da `logo-dark-mode`)

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjar | `noPainelDa($acme)` ou a sessão por `actingAs()`, identidade e cabeçalho gravados, arquivos no disco real | painel pronto |
| 2 | aquecer pelo kernel | `$this->get('/app/acme')->assertSuccessful()` | views compiladas, sem o custo de 25 s dentro do navegador |
| 3 | visita no tema claro | `visit('/app/acme')->inLightMode()` | topo com a logo |
| 4 | `assertPathIs` primeiro | `->assertPathIs('/app/acme')` | espera a navegação |
| 5 | claro: a visível é a clara da Acme | `->assertScript(<primeira img visível do topo tem src terminando em "acme-teste-browser.png" e naturalWidth > 0>)` | clara da Acme, carregada |
| 6 | claro: a escura está oculta | `->assertScript(<nenhuma img.fi-logo-dark visível>)` | `display: none` na escura |
| 7 | visita no tema escuro | `visit('/app/acme')->inDarkMode()` | `<html>` já nasce `dark` |
| 8 | `assertPathIs` primeiro | `->assertPathIs('/app/acme')` | espera a navegação |
| 9 | escuro: a visível é a escura da Acme | `->assertScript(<primeira img visível do topo tem src terminando em "acme-dark-teste-browser.png" e naturalWidth > 0>)` | escura da Acme, carregada |
| 10 | escuro: a clara está oculta | `->assertScript(<nenhuma img.fi-logo-light visível>)` | `display: none` na clara |

**Assertions**: `assertPathIs` primeiro · visibilidade **computada** (`getComputedStyle(el).display` e `offsetParent`, nunca `assertSee`) · o `src` **da organização**, distinto do da instalação, em cada tema (a organização é o oráculo, a instalação é o que o mutante mostraria) · `naturalWidth > 0` (imagem carregada) · `assertNoJavaScriptErrors()` como apoio, **não** `assertNoSmoke()` (tela de plugin de terceiro) · sem `wait($s)`: o plugin retenta as assertions.

> Nenhuma captura de tela é produto deste CT-B (diferente do `[CT-B01]` da `logo-dark-mode`, que também gerava os assets de `art/`): nenhuma linha em `KitArte::IMAGENS`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M36 | o CSS do kit deixa as duas imagens da composição visíveis em qualquer tema | CT-B01 (linha `ligada`) | no tema claro nenhuma `.fi-logo-dark` visível; o mutante a deixa visível |
| M37 | a marca simples só tem a clara da organização (a escura não chega): no tema escuro continua visível a clara — a queixa do requisito | CT-B01 (linha `desligada`) | no tema escuro a primeira imagem visível termina em "acme-dark-teste-browser.png"; o mutante mostra "acme-teste-browser.png" |
| M38 | as classes de swap invertidas: a clara no escuro | CT-B01 (as duas linhas) | no tema escuro a visível é a escura e a `.fi-logo-light` está oculta; o mutante mostra a clara |
| M39 | a URL da logo da organização não é servida ao navegador (`Storage::url()` no lugar de `asset()`: o host é `localhost` do `APP_URL`, o plugin serve em `127.0.0.1:porta`) e a `<img>` quebra | CT-B01 | `naturalWidth > 0` na imagem visível de cada tema; o mutante tem a `<img>` quebrada (`naturalWidth` 0) com o `src` certo no HTML |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B da tela de bloqueio com a organização | já existe: `exibe a logo da organizacao na tela de bloqueio` em `tests/BrowserTenancy/IdentidadeVisualTest.php` (P-01, sem alteração) |
| CT-B da organização sem logo (queda para a instalação) no navegador | o `src` da queda é provado em HTTP (CT-43, CT-46); o navegador só acrescentaria o swap, que CT-B01 já prova para a imagem da organização |
| CT-B do `/admin` sem logo de organização | já provado em HTTP (CT-51); não depende de JS |
| segundo CT-B com erro visível | o teto do perfil padrão é 1 happy path; os mutantes de erro visível (M37, M39) já morrem nas linhas do esquema |
| screenshot do topo em cada tema | `assertScreenshotMatches()` cria o baseline com o defeito; a prova é `getComputedStyle` |
| atualização Livewire da barra superior no navegador | CT-53 prova o par devolvido em HTTP; o swap é CSS, independente do redesenho |

## Roteiro de Validação: Desenhado × Implementado

<!-- Preenchido no step 10 (execução dos CT-B contra a UI real). -->

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | no `/app/{slug}` com organização aberta, a marca simples tem `fi-logo-light` e `fi-logo-dark` com o par da organização | a preencher | a preencher | a preencher |
| 2 | na composição, o par da organização dentro de `.kit-cabecalho`, com um só `fi-logo-dark` | a preencher | a preencher | a preencher |
