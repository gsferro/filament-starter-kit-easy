# Casos de Teste de Browser — feat/cabecalho-do-painel: cabeçalho dos painéis personalizável pelas Configurações da aplicação

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor, in-process.
> Costura: `browser` — linha "G4 — Layout e tema no navegador" de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest --testsuite=Browser --filter=CabecalhoDoPainel` (o `phpunit.xml` declara a suíte `Browser`, linha 32) — em série, **nunca** `--parallel`; ou `composer test:browser`, que embute `npm run build` e `view:cache`
> Derivado do **requisito** (`P-04`, `P-07`, `P-09`), não do plano.

**Teto e estouro**: o perfil da área é mínimo/padrão (teto 1 happy path). São **três** CT-B *(alterado em 2026-10-05: QA-04 acrescentou o terceiro)* porque cada um é o único matador de mutantes que nenhuma camada mais barata enxerga (gate do passo 6 vence o teto): a media query de P-04, a troca clara/escura por classe `dark` de P-07 e o truncamento numa linha na barra lateral de P-06 (altura e `text-overflow` só o navegador calcula) são CSS que só o navegador calcula; o HTML servido é idêntico com e sem o defeito.

## Pré-requisitos
- [ ] `npm run build` executado (sem `public/build/manifest.json`, toda tela responde `ViteException`)
- [ ] `php artisan view:cache` executado, e um `$this->get('/admin')` de aquecimento antes do `visit()` (rule `testes-browser.md`: o primeiro render paga a compilação dentro do timeout)
- [x] `tests/Browser/Screenshots` no `.gitignore` (linha 28)
- [ ] Autenticação por `$this->actingAs($user)` antes do `visit()`; nada de login pela tela
- [ ] Opções do cabeçalho por `gravarConfiguracao(...)` + `alinharConfiguracoesDoKit()`; logo no disco `public` **real** (o navegador baixa a URL), como `tests/Browser/LogoDarkModeTest.php`
- [ ] O `beforeEach` não arranja painel; cada cenário arranja e visita o `/admin` (rule `testes-browser.md`)

## Seletores
| Elemento | Seletor | Já existe? |
|---|---|---|
| bloco do usuário | `.kit-usuario` | não — nasce com a feature (classe do 01; o kit não tem `data-testid`, dívida conhecida) |
| gatilho do menu do usuário (avatar) | `.fi-user-menu-trigger` (confirmar no HTML do Filament 5.8 ao implementar; alternativa: `[data-user-menu-header]` é o cabeçalho interno, não o gatilho) | sim — Filament |
| composição da marca | `.kit-cabecalho` | não — nasce com a feature |
| logo clara/escura dentro da composição | `.kit-cabecalho .fi-logo-light` / `.kit-cabecalho .fi-logo-dark` | não — nasce com a feature (as classes `fi-logo-*` são as que o CSS nativo do Filament troca) |

Seletor que casa mais de um elemento é erro no modo estrito do Playwright: se o layout tiver duas composições (barra lateral e topbar — R8 do `04`), usar `document.querySelectorAll(...)` dentro de `assertScript`/`script()` e afirmar sobre **toda** ocorrência, nunca `assertVisible` num seletor ambíguo.

---

## CT-B01: o bloco do usuário some abaixo de 768 px e fica à esquerda do avatar

**Por que browser e não Livewire/HTTP**: a ocultação é media query e a posição é layout flex; o HTML servido é o mesmo em toda largura. Só `getComputedStyle`/`getBoundingClientRect` distinguem.

> `P-04`, `P-09` · regra R20 · técnica: **BVA 2-valores** na borda 768 (incremento do tipo: 1 px) — "abaixo de 768" ⇒ 767 oculto, 768 visível.

```gherkin
# language: pt
Funcionalidade: Cabeçalho dos painéis personalizável

  Regra: O bloco do usuário some abaixo de 768 px e complementa o avatar

    Esquema do Cenário: [CT-B01] o bloco do usuário na borda de 768 px
      Dado o bloco do usuário ligado
      E o administrador "Maria Ômega" no painel /admin
      Quando a janela tem <largura> px de largura
      Então o bloco do usuário está <estado>
      E o avatar do menu do usuário está visível
      E <posicao>

      Exemplos:
        | largura | estado   | posicao                                                       | # borda  |
        | 767     | oculto   | nada a medir                                                   | borda−1  |
        | 768     | visível  | a borda direita do bloco fica à esquerda da borda esquerda do avatar | borda |
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `gravarConfiguracao('cabecalho_usuario', true); alinharConfiguracoesDoKit(); $this->actingAs($maria); $this->get('/admin');` | — |
| 2 | abrir na largura da linha | `$pagina = visit('/admin')->resize(<largura>, 900);` | painel aberto |
| 3 | estado do bloco | `->assertScript("getComputedStyle(document.querySelector('.kit-usuario')).display === 'none'")` (767) / `->assertVisible('.kit-usuario')` + `->assertSeeIn('.kit-usuario', 'Maria Ômega')` (768) | bloco oculto / com o nome |
| 4 | avatar | `->assertVisible('<gatilho do menu do usuário>')` | avatar presente |
| 5 | geometria (768) | `json_decode($pagina->script("(() => { const b = document.querySelector('.kit-usuario').getBoundingClientRect(), a = document.querySelector('<gatilho>').getBoundingClientRect(); return JSON.stringify({ direitaDoBloco: b.right, esquerdaDoAvatar: a.left, largura: b.width }); })()"), true)` → `expect($m['direitaDoBloco'])->toBeLessThanOrEqual($m['esquerdaDoAvatar'])` e `expect($m['largura'])->toBeGreaterThan(0)` | bloco à esquerda do avatar |
| 6 | apoio | `->assertNoJavaScriptErrors()` | console limpo |

**Assertions**: o bloco está no DOM nas duas linhas (`assertScript("document.querySelector('.kit-usuario') !== null")`) — senão "oculto" passa com o bloco nunca renderizado · console é apoio, não oráculo · se `resize()` depois do `visit()` não reaplicar a media query no Chromium headless, abrir uma visita por linha com a largura já definida (mesmo espírito da regra "uma visita por tema").

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | bloco sem regra de ocultação (sempre visível) | CT-B01 | linha 767: `display === 'none'`; o mutante `inline-flex`/`block` |
| M2 | breakpoint deslocado (`max-width: 768px` oculta, ou `min-width: 769px` exibe) | CT-B01 | linha 768: `assertVisible('.kit-usuario')`; o mutante o oculta em 768 |
| M3 | ocultação invertida (some no desktop, aparece no celular) | CT-B01 | linha 768: visível; o mutante oculto (e 767 visível) |
| M4 | bloco à direita do avatar (ordem do flex ou hook depois do menu) | CT-B01 | linha 768: `direitaDoBloco <= esquerdaDoAvatar`; o mutante tem `direitaDoBloco > esquerdaDoAvatar` |
| M5 | regra de ocultação aplicada ao contêiner que inclui o avatar | CT-B01 | linha 767: avatar visível; o mutante o esconde junto |

---

## CT-B02: na marca separada, a composição mostra a variante escura só no tema escuro

**Por que browser e não Livewire/HTTP**: o HTML tem as duas imagens nos dois temas (CT-06 do `04` já as afirma); qual delas o usuário **vê** depende da classe `dark` no `<html>`, que o `theme.js` do Filament fixa no load, e do CSS. Só o navegador calcula.

> `P-07` · regra R21 · técnica: **EP** (tema claro, tema escuro), uma visita por tema (rule `testes-browser.md`: `inDarkMode()` só vale no load).

```gherkin
  Regra: A composição segue a variante escura da marca separada

    Esquema do Cenário: [CT-B02] a logo da composição troca com o tema
      Dado a logo e a variante escura enviadas, com a marca separada
      E só o segmento logo da marca ligado
      E o administrador no painel /admin
      Quando o painel abre no tema <tema>
      Então em toda composição a logo <visivel> está visível
      E em toda composição a logo <oculta> está oculta

      Exemplos:
        | tema   | visivel | oculta |
        | claro  | clara   | escura |
        | escuro | escura  | clara  |
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `Storage::disk('public')->put('kit/logo-cab.png', UploadedFile::fake()->image('a.png', 400, 80)->get())` (e `kit/logo-cab-dark.png`); `gravarConfiguracao('logo', …)`, `('logo_dark', …)`, `('unifica_logo_marca', false)`, `('cabecalho_logo_da_marca', true)`; `alinharConfiguracoesDoKit()`; `$this->actingAs(usuarioDoKit('admin'))`; `$this->get('/admin')` | — |
| 2 | tema claro | `$clara = visit('/admin')->inLightMode();` | painel claro |
| 3 | visibilidade | `->assertScript("[...document.querySelectorAll('.kit-cabecalho .fi-logo-dark')].length > 0 && [...document.querySelectorAll('.kit-cabecalho .fi-logo-dark')].every(e => getComputedStyle(e).display === 'none')")` e o par `.fi-logo-light` com `.some(e => getComputedStyle(e).display !== 'none' && e.getBoundingClientRect().width > 0)` | só a clara |
| 4 | tema escuro | `$escura = visit('/admin')->inDarkMode();` + as duas asserções com as classes trocadas | só a escura |
| 5 | apoio | `->assertNoJavaScriptErrors()` em cada visita; `->screenshot()` só como evidência, nunca como oráculo | console limpo |

**Assertions**: cada asserção de "oculta" exige `length > 0` — senão passa com a imagem nunca renderizada · a composição da barra lateral e a do topbar (se as duas existirem no DOM) são afirmadas juntas pelo `querySelectorAll` · `assertSee`/`assertScreenshotMatches` não provam tema (fato 10 do plugin).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | composição só com a logo clara (a variante escura não entra) | CT-B02 | linha escuro: `.kit-cabecalho .fi-logo-dark` com `length > 0` e visível; o mutante tem 0 |
| M2 | classes trocadas (a escura recebe `fi-logo-light`) | CT-B02 | linha claro: a imagem visível é a de `src` `logo-cab.png`; o mutante mostra a escura |
| M3 | CSS do kit esconde toda logo da composição no escuro (`.dark .kit-cabecalho__logo { display: none }`) | CT-B02 | linha escuro: alguma `.fi-logo-dark` com `display !== 'none'`; o mutante esconde todas |
| M4 | composição desenhada dentro de um contêiner com `display` próprio que ignora o par nativo `.dark .fi-logo-light` (as duas visíveis) | CT-B02 | linha claro: toda `.fi-logo-dark` com `display === 'none'`; o mutante mostra as duas |

---

## CT-B03: a composição cabe numa linha na barra lateral aberta, cortando o texto com reticências *(criado em 2026-10-05: QA-04 do ciclo 1 — P-06 revisada no step 9)*

**Por que browser e não Livewire/HTTP**: altura, quebra de linha e `text-overflow` são layout calculado; o HTML é o mesmo com ou sem a regra.

> `P-06` (revisada: "sempre numa linha; na barra lateral os textos encolhem e cortam com reticências") · regra R26 *(alterado em 2026-10-05: QA-10 — regra própria da P-06, não a R20 do bloco do usuário)* · técnica: **BVA** — nome de projeto longo (59 caracteres) que não cabe nos ~13,5 rem úteis da barra lateral confortável.

```gherkin
  Regra: A composição não vaza por baixo do cabeçalho da barra lateral

    Cenário: [CT-B03] o nome longo trunca e a composição fica dentro do cabeçalho da barra lateral
      Dado o nome do projeto com 59 caracteres, o nome do painel e a logo ligados
      E o administrador no painel /admin, numa janela de 900 px, abrindo a barra lateral pelo botão do topbar (`.fi-topbar-open-sidebar-btn`) *(alterado em 2026-10-05, causa a: em 1280 px o Filament desenha a marca só no topbar e a composição da barra lateral fica 0×0 no DOM; a marca da barra aparece abaixo de 1024 px)*
      Quando a barra lateral abre
      Então a composição do cabeçalho da barra lateral (`.fi-sidebar .kit-cabecalho`) ocupa uma linha só (altura computada ≤ 2,5 rem)
      E a borda inferior dela não passa da borda inferior do cabeçalho da barra lateral (`.fi-sidebar-header`)
      E o segmento do projeto tem `scrollWidth` maior que `clientWidth` (foi truncado) e `text-overflow: ellipsis` computado
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `flex-wrap: wrap` na barra lateral (o desenho antigo) | CT-B03 | altura computada da composição > 2,5 rem; o mutante quebra em 2–3 linhas |
| M2 | `__projeto` sem `overflow: hidden` + `text-overflow: ellipsis` (o texto não é cortado e a composição vaza para fora da barra) | CT-B03 | **provado pela sessão (2026-10-05)**: sem as duas declarações, `Failed asserting that 463 is greater than 463` (`scrollWidth == clientWidth`); com elas, verde. *Medido também*: tirar só `min-width: 0` (dos filhos ou da raiz `.kit-cabecalho`) **não** derruba o teste — `overflow: hidden` já zera o mínimo automático do item flex (spec do Flexbox) e o contêiner do Filament limita a largura; as declarações de `min-width`/`max-width` ficam como defesa para consumidores fora do Filament (Sentinel), não como o que o CT-B03 prova |
| M3 | `text-overflow` ausente (corte seco sem reticências) | CT-B03 | `getComputedStyle(projeto).textOverflow === 'ellipsis'`; o mutante devolve `clip` |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| `assertNoAccessibilityIssues()` no cabeçalho composto | acessibilidade não é cláusula do `00`; nenhum mutante previsto depende dela — entra pela dimensão de acessibilidade do quality gate |
| composição quebrando linha dentro da barra lateral (Q6 do `00`: "dentro da barra lateral ela quebra linha para caber") | está na **recomendação** de Q6, não em P-06; quebra de linha não tem valor afirmável sem baseline (screenshot cria o baseline com o defeito) |
| bloco do usuário no tema escuro (contraste) | cor não se prova por assertion barata (fato 10); fica com o nível visual da dimensão G do quality gate |
| clicar no avatar e abrir o menu com o bloco ligado | o menu continuar no HTML já é CT-13 do `04`; abrir o dropdown é Alpine nativo do Filament, não código da feature |
| largura 1280 (desktop) além de 768 | não mata mutante novo: M2/M3 morrem em 768 |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `.kit-usuario` oculto abaixo de 768 px por `@media (min-width: 768px)` no `kit.css` | idêntico: a 767 px o bloco está no DOM com `display: none`; a 768 px visível com "Maria Ômega" e largura > 0 (o `resize()` depois do `visit()` reaplica a media query) | ✅ | CT-B01 (767 e 768) verde — `fw-executor-ctb`, 2026-10-05, `tests/Browser/CabecalhoDoPainelTest.php` |
| 2 | bloco no hook `USER_MENU_BEFORE`, à esquerda do avatar | idêntico: `direitaDoBloco <= esquerdaDoAvatar` (`.fi-user-menu-trigger`) a 768 px, avatar visível nas duas larguras | ✅ | CT-B01 (768) verde |
| 4 | P-06 revisada: composição de uma linha na barra lateral, texto cortado com reticências | a 900 px com a barra aberta: altura ≤ 40 px, base dentro de `.fi-sidebar-header`, `text-overflow: ellipsis` com `scrollWidth > clientWidth`; a 1280 px a marca da barra lateral não é exibida (só a do topbar) | ✅ | CT-B03 verde — `fw-executor-ctb`, 2026-10-05 (primeira rodada vermelha por causa a: viewport do `05`); falsificado pela sessão (M2) |
| 3 | composição com `<img class="… fi-logo fi-logo-light">` e, na marca separada, `<img class="… fi-logo fi-logo-dark">`, com par `.dark:root` no `kit.css` | estrutura e classes conferem (duas composições no DOM, barra lateral e topbar, ambas dentro de `div.fi-logo`). **Divergência na 1ª rodada (causa b)**: o swap não funcionava — `.kit-cabecalho__logo { display: block }` vencia o `:where(.fi-logo-dark)` do Filament e as duas logos ficavam visíveis no tema claro (mutante M4 materializado). Corrigido no `kit.css`: sem `display` na classe da imagem, e o swap repetido pelo kit com especificidade própria (`.kit-cabecalho .fi-logo-light/.fi-logo-dark` + `.dark:root …`) | ⚠️ → ✅ | CT-B02 vermelho (`toda .fi-logo-dark oculta no tema claro` → false), depois verde na re-rodada da sessão: `{"result":"passed","tests":3,"passed":3,"assertions":20}` |
