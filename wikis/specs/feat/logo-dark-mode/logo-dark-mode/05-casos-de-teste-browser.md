# Casos de Teste de Browser — feat/logo-dark-mode: Suporte a logo dark / light

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "Swap por tema" de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/Browser --filter=LogoDarkMode` (em série — nunca `--parallel`)

## Pré-requisitos

- [ ] `npm run build` executado
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs(usuarioDoKit('master_global'))` antes do `visit()`
- [ ] Sessão de bloqueio por `session(['lockscreen' => true, 'tenant_corrente' => $org->getKey()])`
- [ ] `Storage::fake('public')` + arquivos de logo materializados (o plugin roda no mesmo processo)

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| `<img>` da variante clara | `.fi-logo-light` | sim (classe nativa do swap do Filament) |
| `<img>` da variante escura | `.fi-logo-dark` | sim (idem) |
| Raiz com a classe de tema | `html.dark` | sim (Filament aplica em `<html>`) |

---

## CT-B01: a troca light/dark acontece no navegador — e gera os assets da documentação

**Por que browser e não Livewire**: a troca é **classe `.dark` no `<html>` aplicada pelo JS do Filament** + regra CSS `display:none` — presença no HTML não prova que a imagem certa está visível; só `getComputedStyle` no navegador falsifica "a light continua visível no dark" ou "a dark nunca aparece". O segundo produto do cenário são os screenshots de `art/` (RQ-10, D5). *(alterado em implementação)*

```gherkin
# language: pt
  Cenário: [CT-B01] o tema escuro mostra a logo_dark e o tema claro a logo
    Dado a marca separada, com a logo e logo_dark da instalação
    E a sessão bloqueada
    Quando a tela de bloqueio é visitada no tema claro
    Então a imagem visível da mídia é a logo clara e a escura está oculta
    E quando a tela é visitada no tema escuro
    Então a imagem visível da mídia é a logo_dark e a clara está oculta
    E os dois estados são capturados em screenshot para "art/" *(alterado em implementação)*
```

**Roteiro executável** *(alterado em implementação — a emulação de `prefers-color-scheme` só vale no LOAD da página: o `theme.js` do Filament lê a preferência no `DOMContentLoaded` e fixa `dark` no `<html>` ali; `inDarkMode()` mid-session não reavalia, então o cenário faz uma visita por tema)*

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | bloquear | `post(route('lockscreen.app.lock-session'))` — sessão real, o cookie é do browser | redirect |
| 2 | visita clara | `visit('/app/screen/lock')->inLightMode()` | lock-screen com a logo |
| 3 | assert tema claro | `->assertVisible('.fi-logo-light')` + `assertScript` de `display:none` na `.fi-logo-dark` | light visível, dark oculta no DOM |
| 4 | screenshot light | `->screenshotElement('.fi-auth-media-wrapper', 'logo-tema-claro')` | PNG salvo |
| 5 | visita escura | `visit('/app/screen/lock')->inDarkMode()` | `<html>` já nasce `dark` |
| 6 | assert tema escuro | `->assertVisible('.fi-logo-dark')` + `assertScript` de `display:none` na `.fi-logo-light` | dark visível, light oculta no DOM |
| 7 | screenshot dark | `->screenshotElement('.fi-auth-media-wrapper', 'logo-tema-escuro')` | PNG salvo |
| 8 | publicar assets | `Copy-Item tests/Browser/Screenshots/logo-tema-*.png → art/` | PNGs na documentação |

**Assertions**: `assertVisible`/`assertNotVisible` (visibilidade computada — não `assertSee`) · `assertNoJavaScriptErrors()` (página de pacote terceiro — não `assertNoSmoke`) · sem `wait($s)` — o plugin retenta as assertions

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M26 | classes de swap ausentes — as duas `<img>` ficam visíveis em qualquer tema | CT-B01 | no tema claro a escura deve estar oculta; o mutante a deixa visível |
| M27 | ordem invertida — a classe dark na imagem clara e vice-versa | CT-B01 | no tema escuro a escura deve ser a visível; o mutante mostra a clara |
| M28 | `visible` de CSS só no `:hover`/foco em vez de `.dark` | CT-B01 | a escura visível sem troca de tema falha o passo 5 |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B da marca do topo (login/topbar) em dark | mecanismo nativo do Filament — CT-17/18 provam a configuração no HTML; o swap em si é do upstream |
| CT-B do toggle `unifica_logo_marca` escondendo o campo | `assertFormFieldIsHidden`/`IsVisible` no componente prova o mesmo sem navegador (CT-08/09) |
| Segundo cenário de browser com erro visível | teto do perfil padrão = 1 happy path; os mutantes de erro já morrem nas costuras HTTP/componente |

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Dois `<img>` com classes nativas `fi-logo-light`/`fi-logo-dark` na lock-screen | Override da partial emite os dois `<img>` com as mesmas classes nativas + `object-fit:contain`, só quando `urlsDasLogos()` existe na página | sim | `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php`; CT-16 (5 linhas do esquema) |
| 2 | `darkModeBrandLogo` nos 3 painéis | `->darkModeBrandLogo(fn (): ?string => IdentidadeDoKit::logoEscura())` em Admin/App/Infra providers | sim | `app/Providers/Filament/*PanelProvider.php`; CT-17/18 |
| 3 | Screenshots em `docs/{pt,en}/assets/` | `art/logo-tema-{claro,escuro}.png` — convenção real das docs (`art/` + raw.githubusercontent), não `docs/{idioma}/assets/` | **desvio justificado** — convenção do repo vence a do plano; CT-20 reescrito para ela | `art/` + CT-20; CT-B01 `screenshotElement` |
