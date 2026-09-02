# Casos de Teste de Browser — A busca ⌘K (Spotlight) abre fora da tela

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Comando: `composer test:browser` — ou, para o arquivo isolado depois de `npm run build` e
> `php artisan view:cache`: `vendor/bin/pest --no-tia tests/Browser/RoteiroDoKitTest.php --filter=F-45`.
> **Nunca `--parallel`** (`.ai/rules/testes-browser.md`).

## Por que este cenário é de browser

O HTML do overlay é **byte a byte idêntico** com e sem a correção — a diferença é o que o Chromium
calcula ao aplicar (ou não) o CSS. Nenhum teste de componente Livewire vê `position`, `top` ou
`background-color`. E, diferente do `cards.css`, onde o defeito era cor e a wiki ancestral ficou no
screenshot, aqui o defeito é **geométrico** e `getBoundingClientRect()` / `getComputedStyle()` o
medem com números.

## Pré-requisitos

- [ ] `npm run build` executado (sem o manifest, toda tela é `ViteException`)
- [ ] `php artisan view:cache` (cache frio estoura os 45 s no primeiro cenário)
- [ ] `tests/Browser/Screenshots` no `.gitignore` — já está
- [ ] Autenticação por `$this->actingAs(usuarioDoKit('master_global'))`, depois do `beforeEach` que
      semeia `ShieldPermissionsSeeder` + `PapeisSeeder` (padrão de `RoteiroDoKitTest.php`)
- [ ] **Tema declarado em toda linha**: `inDarkMode()` vaza para o cenário seguinte
      (`tests/Browser/TemaEscuroTest.php:97`). A linha `claro` chama `inLightMode()` explicitamente

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| gatilho da busca (topbar) | `.fi-global-search-field` | sim — é o que o F-45 usa |
| input do overlay | `input[placeholder="Buscar registros e telas..."]` | sim — texto vem do `->placeholder()` do plugin |
| raiz do overlay (para `script()`) | `document.querySelector('[x-on\\:open-spotlight\\.window]')` | atributo da blade do vendor (`spotlight.blade.php:60`) — é o mesmo âncora do CSS (ADR-02) |
| caixa do overlay | `raiz.firstElementChild` (o `div` com `x-on:click.outside`) | estrutura da blade do vendor |

> Dívida conhecida: o kit não tem `data-testid`, e o pacote tampouco. O seletor por atributo Alpine
> é o mais estável disponível: é o evento que o gatilho do kit dispara, então renomeá-lo quebra o
> gatilho antes de quebrar o teste.

---

## CT-B01: o overlay cobre a viewport, acima do conteúdo, escurecido, com o campo de busca na tela — nos dois temas

**Por que browser e não Livewire**: a asserção é sobre `position`, `top`, `z-index` e
`background-color` **computados** — não existem fora do navegador.

**É o F-45 reescrito**, não um caso novo: mantém o `it('F-45: …')`, o clique e o `assertVisible`
como primeira metade, e ganha a medição como segunda — com um dataset de tema. **Precisa ficar
vermelho antes da correção** (R5 do `04`).

```gherkin
# language: pt

  Esquema do Cenário: [CT-B01] o clique na busca abre o overlay ancorado à viewport
    Dado um usuário master_global no /admin, em tema "<tema>"
    Quando ele clica no campo de busca da topbar
    Então o overlay está com position fixed e top e left iguais a 0
    E a largura e a altura do overlay são as da viewport
    E o z-index do overlay é ao menos 50
    E o fundo do overlay tem alfa maior que 0
    E o campo de busca do overlay está dentro da viewport
    E o fundo da caixa do overlay é "<caixa>"

    Exemplos:
      | tema   | caixa                     | # partição                          |
      | claro  | rgb(255, 255, 255)        | classes base                        |
      | escuro | diferente de rgb(255, 255, 255) | variantes dark: — metade do arquivo |
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir o painel no tema da linha | `visit('/admin')` + `->inLightMode()` ou `->inDarkMode()` + `->assertSee('Painel de Controle')` | dashboard |
| 2 | clicar na busca | `->click('.fi-global-search-field')` | overlay abre |
| 3 | esperar o overlay | `->assertVisible('input[placeholder="Buscar registros e telas..."]')` | input presente (é a assertion que espera o Alpine) |
| 4 | medir | `$medida = json_decode($pagina->script(<<<'JS' … JS), true)` — ver bloco abaixo | — |
| 5 | asserir | `expect($medida['position'])->toBe('fixed')` … | — |
| 6 | evidência | `->screenshot(fullPage: false, filename: "spotlight-{$tema}")` | PNG para a Verificação Final |

```js
(() => {
    const raiz  = document.querySelector('[x-on\\:open-spotlight\\.window]');
    const caixa = raiz.firstElementChild;
    const input = raiz.querySelector('input');
    const r  = raiz.getBoundingClientRect();
    const cs = getComputedStyle(raiz);
    return JSON.stringify({
        position: cs.position, zIndex: cs.zIndex, fundo: cs.backgroundColor,
        top: r.top, left: r.left, largura: r.width, altura: r.height,
        viewportW: innerWidth, viewportH: innerHeight,
        inputTop: input.getBoundingClientRect().top,
        caixaFundo: getComputedStyle(caixa).backgroundColor,
    });
})()
```

**Assertions** (todas sobre a medida; `assertNoJavaScriptErrors()` é apoio, porque a blade é de terceiro):

- `position === 'fixed'`, `top === 0`, `left === 0`
- `largura === viewportW`, `altura === viewportH`
- `(int) zIndex >= 50` — `'auto'` vira 0 e reprova
- alfa de `fundo` > 0 — parse de `rgba(r, g, b, a)`; `rgba(0, 0, 0, 0)` reprova
- `inputTop < viewportH`
- `caixaFundo` conforme a linha: `=== 'rgb(255, 255, 255)'` no claro, `!==` no escuro

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `fixed` sem `inset-0` — o estado de hoje (`top: 1833`) | CT-B01 (`top === 0`) |
| M2 | sem `z-index` — overlay atrás da topbar | CT-B01 (`zIndex >= 50`) |
| M3 | backdrop sem fundo | CT-B01 (alfa > 0) |
| M4 | variantes `dark:` ausentes, ou com `@media (prefers-color-scheme: dark)` em vez do `.dark` que o Filament põe no `<html>` — caixa branca sobre página escura | CT-B01, linha `escuro` (`caixaFundo` branco reprova) |
| M5 | escopo sem escape, CSS nunca casa | CT-B01 (tudo reprova) |
| M7 | classes da raiz escritas com espaço — descendentes estilizados, raiz não | CT-B01 (`top`, `zIndex`, `fundo` reprovam; `caixaFundo` passa — é o que **separa** M7 de M5) |

**Estado medido hoje, sem a correção** (2026-09-02, `/admin`):
`{"position":"fixed","zIndex":"auto","background":"rgba(0, 0, 0, 0)","top":1833,"viewportH":1117}` —
três das seis asserções de geometria reprovam. É a evidência de que CT-B01 é falsificável (R5).

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B02 separado para o tema escuro | repetia clique, espera e `script()` para trocar uma asserção. Virou linha do `Esquema` de CT-B01 (auditoria Ponytail) |
| Abrir por `Ctrl+K` (`->keys('body', 'Control+k')`) | o atalho é do **pacote** (`x-mousetrap.global.mod-k`), não do kit, e já funcionava antes — o defeito não é abrir, é onde abre. Mata os mesmos mutantes de CT-B01. Se o gatilho por teclado quebrar, é bug do vendor |
| Um CT-B por painel (`/app`, `/infra`) | CT-03 do `04` prova que a folha chega aos três; o CSS é o mesmo arquivo. Repetir a geometria três vezes triplica o tempo de browser sem mutante novo |
| Digitar no overlay e ver resultados | comportamento do pacote, coberto por `PermissoesDeAcoesTest` (categorias) — nada muda com esta correção |
| `Esc` fecha o overlay | idem: comportamento do vendor, sem relação com CSS |
| `assertScreenshotMatches()` | congela pixel; qualquer mudança de paleta do painel reprova sem defeito. A geometria numérica diz o que importa e envelhece bem |

---

## Roteiro de Validação: Desenhado × Implementado

Preenchido em 2026-09-02, depois de rodar o F-45 nos dois temas.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | overlay ancorado, `z-50`, backdrop `gray-900/70` | idem: `position: fixed`, `top/left 0`, viewport inteira, `z-index 50`, fundo com alfa, blur visível | ✅ | `spotlight-claro.png`; F-45 `claro` verde, 14 asserções |
| 2 | caixa branca no claro, `gray-900` no escuro | idem — caixa `rgb(255,255,255)` no claro; escura sob `.dark` | ✅ | `spotlight-escuro.png`; F-45 `escuro` verde |
| 3 | input focado ao abrir | focado (placeholder visível, `esc` à direita) — comportamento do pacote | ✅ | os dois PNGs |
| 4 | CT-B01 vermelho antes / verde depois (R5) | vermelho na geometria (`1833` ≠ `0`) antes; `2 passed, 28 assertions` depois | ✅ | linhas coladas no `03`, seção 4 |
| 5 | cada classe declarada nas duas formas (composta e descendente) | desvio do plano: o `01` previa distinguir raiz de descendente; declarar ambas custa uma vírgula por regra e dispensa a distinção | ⚠️ desvio aceito | `03` → Desvios do Plano |
