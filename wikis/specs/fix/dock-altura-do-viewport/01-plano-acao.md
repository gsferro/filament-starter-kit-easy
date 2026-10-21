# Plano de Ação — Botão do dock nasce e some se não for movido

## Natureza da Wiki

**correção** — de um comportamento do pacote `hoceineel/filament-quick-action-dock` (v0.2.0). Wikis ancestrais: `wikis/specs/main/dock-de-acoes-rapidas/` e `wikis/specs/fix/pilha-de-botoes-flutuantes/`.

## Cobertura do Requisito

| RQ | Passo | Observação |
|---|---|---|
| RQ-01 | 1 | a altura do viewport passa a ser a da área visível, em todos os chamadores do pacote |
| RQ-02 | 1 | o asset é o do pacote, carregado em todo painel em que o dock aparece |
| RQ-03 | 1, 2 | causa medida no navegador do solicitante; CT-B que reproduz e falha sem a correção |
| P-01 | 1 | só a altura muda; a posição lembrada continua sendo a fração gravada pelo pacote |

## Diagnóstico

Medido no Chrome desktop do solicitante (Chrome 155, Windows, DPR 1,75), pelo trecho de console da sessão:

| Medida | Valor |
|---|---|
| `window.innerHeight` | 867 (igual ao `outerHeight`) |
| `document.documentElement.clientHeight` | 780 |
| `visualViewport.height` | 780 |
| `--qad-transform` depois do reposicionamento | `translate3d(703.5px, 787px, 0)` |

O pacote mede o viewport com `{width: documentElement.clientWidth || innerWidth, height: window.innerHeight}` (função
`b()` do `dist/quick-action-dock.js`; `resources/js/index.js`, linha 60, no fonte). O elemento `fixed` vive no layout
viewport (`clientHeight`). Para o `bottom-center`, `y = altura − 56 − 24`: com 867, `y = 787`, e o botão de 56 px ocupa
787–843, abaixo da borda de 780. O primeiro posicionamento sai certo (y = 700); o reposicionamento seguinte (resize,
commit Livewire — no `/admin` o dashboard faz polling) empurra o botão. Mover o botão grava uma fração no `localStorage`
e muda a conta. Na janela anônima e no headless do Playwright `innerHeight == clientHeight`.

Hipóteses descartadas pela sondagem no Playwright (`/app` e `/admin`, 1280×800 a 1920×1080, com e sem chat, página
longa rolada): raiz sem `data-placed` (o pacote esconde com `visibility: hidden`) e `fixed` preso a ancestral com
`transform`/`filter`. Uma raiz só, sempre `fixed`, sem ancestral de contenção.

## Decisões de Desenho

- **Corrigir `b()` na fonte, servindo uma cópia do bundle** — e não embrulhar o `Alpine.data('quickActionDock')`. A 1ª
  versão desta correção embrulhava o `env()` do componente; o `/code-review` mostrou que `b()` tem três chamadores, e o
  embrulho deixava dois com o `innerHeight`: o `apply()`/`R()` (direção do menu) e o `prePlace` do primeiro paint
  *(alterado em 2026-10-20: achados do /code-review)*. O `innerHeight` aparece uma única vez no bundle, em `b()`.
- **Trocar o `src` do asset do pacote, não registrar outro**: o `AssetManager` do Filament acrescenta registros (o
  bundle subiria duas vezes). `Js::html()` com um valor sem `<script` vira o `src`.
- **`src` calculado na renderização**: no boot, o `asset()` congelaria o `APP_URL`; no servidor do `pest-plugin-browser`
  o dock nem aparecia (medido) — a mesma família de defeito da `.ai/rules/app.md`.
- **Não mexer no `vendor/`** (o `composer install` desfaz) **nem sobrepor `window.innerHeight`** (efeito global).

## Estrutura de Implementação

### 1. Cópia corrigida e troca do asset
- `public/js/kit/quick-action-dock.js` — o `dist/` do pacote com `DockDeAtalhos::TRECHO_DO_PACOTE` → `TRECHO_CORRIGIDO`
- `App\Support\DockDeAtalhos::servirScriptCorrigido()` + `srcDoScriptCorrigido()`, chamado do `KitServiceProvider::boot()`
- `public/js/kit` em `KitUpdate::CAMINHOS_DO_KIT` — servida direto de `public/`, sem depender do `filament:assets`

### 2. Testes
- `tests/Browser/DockAlturaDoViewportTest.php` — [CT-B01] força `innerHeight` = área visível + 87, dispara `resize`,
  espera o reposicionamento (sentinela no `--qad-transform`) e mede por geometria que o botão fica a 24 px da borda
  visível (`/admin` e `/app`); [CT-B02] alturas iguais, posição inalterada
- `tests/Kit/DockScriptCorrigidoTest.php` — [CT-01] a cópia é exatamente o `dist/` instalado com a substituição (num
  bump do pacote reprova, com o comando que regenera); [CT-02] o asset do pacote é servido pela cópia, uma vez só

## Remoção

Quando o pacote publicar a correção (PR HoceineEl/filament-quick-action-dock#3), o [CT-01] reprova no bump (o trecho
deixa de existir): apagar `public/js/kit/quick-action-dock.js`, `servirScriptCorrigido()` e a linha de `CAMINHOS_DO_KIT`.
O CT-B01 continua como guarda.
