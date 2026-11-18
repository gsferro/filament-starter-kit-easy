# Casos de Teste de Browser — Roadmap item 1: preferências de aparência por usuário

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "G5 — Layout no navegador" de `## Costuras de Teste` do `04`
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`) ou `vendor/bin/pest tests/Browser/PreferenciasDeAparenciaTest.php` em série — nunca `--parallel` (`.ai/rules/testes-browser.md`)
> Derivado do **requisito** (RQ-02, RQ-04, P-08, P-09), não do plano.
> Revisão adversarial rodada 1: ADV-15 fechado aqui (CT-B01 com um único `Quando`; abrir a página passou ao `Dado`). Demais achados no `04`, seção `## Revisão Adversarial`.

## Pré-requisitos
- [ ] `npm run build` e `php artisan view:cache` executados (o `composer test:browser` faz os dois)
- [ ] Aquecimento pelo kernel no próprio cenário: `$this->get('/admin')` antes do `visit()` (compila os componentes fora do cronômetro)
- [ ] Autenticação por `$this->actingAs($user)` antes do `visit()`; segunda pessoa no mesmo `it()` com `test()->flushSession()`
- [ ] Instalação e escolha pelos helpers do Setup Global do `04` (`aparenciaDaInstalacao()`, `aparenciaDe()`)
- [ ] O `beforeEach` não arranja painel; cada cenário arranja o seu (`.ai/rules/testes-browser.md`)
- [ ] Viewport de desktop (≥ 1024 px): o recolher do menu só existe a partir de `lg`

## Seletores
| Elemento | Seletor | Já existe? |
|---|---|---|
| botão de recolher o menu | `[aria-label="Recolher barra lateral"]` (texto pt_BR do Filament, `layout.actions.sidebar.collapse.label`) — existe nos dois modos: `vendor/filament/filament/resources/views/livewire/sidebar.blade.php:$isSidebarFullyCollapsibleOnDesktop:61` (sem topbar) e `topbar.blade.php` (com topbar); se os dois casarem, restringir pelo contêiner visível (modo estrito do Playwright) | sim (vendor) |
| barra lateral | `.fi-sidebar` | sim (vendor) |
| ícone do primeiro item do menu | `.fi-sidebar-item-icon` (primeiro) | sim (vendor) |
| texto de referência para a fonte | `.fi-header-heading` (título da página) e `.fi-sidebar-item-label` (primeiro rótulo do menu) | sim (vendor) |

Dívida conhecida: o kit não tem `data-testid`; seletores de classe do vendor só para **medir** geometria, nunca para afirmar presença.

---

## CT-B01: "Esconder tudo" some com o menu inteiro ao recolher; "Manter os ícones" deixa os ícones

**Por que browser e não Livewire**: o recolher é Alpine executado no navegador (`$store.sidebar`); o HTML servido é o mesmo antes e depois do clique. O `04` já prova pela classe do `<body>` **qual** modo o painel recebeu; só o navegador prova que, recolhido, o menu some ou fica com os ícones.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B01] recolhido, o menu segue o modo escolhido pela Ana
    Dado a chave da instalação ligada e o menu da instalação em "<menu da instalação>"
    E a Ana com o menu "<menu da Ana>" gravado e o <painel> aberto numa janela de 1280 px, com a barra lateral expandida
    Quando a Ana recolhe a barra lateral
    Então a barra lateral tem largura <largura>
    E o ícone do primeiro item do menu está <ícone>
    E, em "Esconder tudo", o botão do topo reabre a barra na largura aberta, com o primeiro item visível e clicável

    Exemplos:
      | painel | menu da instalação | menu da Ana      | largura                        | ícone                                  |
      | /admin | Esconder tudo      | Manter os ícones | maior que 0 e menor que a aberta | visível, com caixa dentro da viewport |
      | /admin | Manter os ícones   | Esconder tudo    | 0, ou toda fora da viewport     | sem caixa visível                     |
      | /app   | Manter os ícones   | Esconder tudo    | 0, ou toda fora da viewport     | sem caixa visível                     |
      | /infra | Manter os ícones   | Esconder tudo    | 0, ou toda fora da viewport     | sem caixa visível                     |

    *(alterado em 2026-11-18: QA-08 do quality gate, ciclo 2)* — linhas de `/app` e `/infra` e o reabrir, do BP-37 da revisão contra o Blueprint
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjar | `aparenciaDaInstalacao(porUsuario: true, menu: …)`; `aparenciaDe($ana, ['menu' => …])`; `$this->actingAs($ana)->get('/admin')->assertOk()` | — |
| 2 | abrir | `$pagina = visit('/admin')->resize(1280, 800)` | painel aberto, menu expandido |
| 3 | medir aberta | `script()` com `getBoundingClientRect()` de `.fi-sidebar` | largura aberta > 0 |
| 4 | recolher | `->click('[aria-label="Recolher barra lateral"]')` | menu recolhe |
| 5 | medir recolhida | `script()` devolvendo `{largura, direita, iconeLargura, iconeVisivel}` | números do `Então` |

**Assertions**: oráculo é número (`getBoundingClientRect`/`getComputedStyle`), nunca `assertVisible` sozinho (não prova posição); `assertNoJavaScriptErrors()` de apoio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB1 | os dois modos ligados no painel ao mesmo tempo (`sidebarCollapsibleOnDesktop()` mantido e `sidebarFullyCollapsibleOnDesktop()` somado) | CT-B01 (linha Esconder tudo) | recolhida, largura > 0 com ícones; esperado 0 |
| MB2 | o modo do menu lido da instalação, não da pessoa (decidido no registro do painel, antes do usuário) | CT-B01 (as duas linhas: a instalação é o oposto) | largura da linha invertida |
| MB3 | "Esconder tudo" só oculta os rótulos (ícones ficam) | CT-B01 (linha Esconder tudo) | ícone com caixa visível |
| MB4 | "Manter os ícones" mapeado para recolher tudo | CT-B01 (linha Manter os ícones) | largura 0; ícone sem caixa |
| MB9 | em "Esconder tudo", nenhum botão visível reabre a barra (pessoa fica sem menu) | CT-B01 (linhas Esconder tudo) | depois do clique no botão do topo, largura ≠ aberta ou primeiro item fora da viewport *(alterado em 2026-11-18: QA-08 do quality gate, ciclo 2)* |

---

## CT-B02: os tamanhos de fonte escalam o texto pelos percentuais de P-08

**Por que browser e não Livewire**: o requisito é o tamanho que a pessoa vê; a declaração emitida no HTML pode estar certa e **perder a cascata** para o tema do Filament (cascade layers), ou escalar só parte do texto. Só o valor calculado pelo Chromium prova o percentual. É o único cenário que usa o **número literal** do requisito.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B02] o texto da Ana fica no percentual do tamanho escolhido
    Dado a chave da instalação ligada e a fonte da instalação "Normal"
    E a Bia sem escolha, cujo /admin dá o tamanho de referência do título e do primeiro rótulo do menu
    E a Ana com a fonte "<fonte>" gravada
    Quando a Ana abre /admin
    Então o tamanho calculado do título e do primeiro rótulo do menu é <razão> do tamanho de referência, com tolerância de 0,01

    Exemplos:
      | fonte        | razão |
      | Pequena      | 0,875 |
      | Grande       | 1,125 |
      | Muito grande | 1,25  |
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | referência | `actingAs($bia)`; `visit('/admin')`; `script()` → `parseFloat(getComputedStyle(el).fontSize)` de `.fi-header-heading` e do primeiro `.fi-sidebar-item-label` | dois tamanhos em px |
| 2 | trocar de pessoa | `test()->flushSession()`; `actingAs($ana)`; `$this->get('/admin')->assertOk()` (aquecimento) | — |
| 3 | medir | `visit('/admin')`; o mesmo `script()` | dois tamanhos em px |
| 4 | comparar | `expect($ana / $referencia)->toEqualWithDelta($razao, 0.01)` para cada elemento | — |

**Assertions**: razão calculada × valor literal de P-08; `assertNoJavaScriptErrors()` de apoio.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB5 | declaração da fonte emitida dentro de uma cascade layer (perde para o tema) | CT-B02 (todas as linhas) | razão 1,0 |
| MB6 | percentual errado (Grande 110 %, Pequena 90 %) | CT-B02 (linhas Grande, Pequena) | razão 1,10 / 0,90 |
| MB7 | Muito grande mapeado para o mesmo valor de Grande | CT-B02 (linha Muito grande) | razão 1,125; esperado 1,25 |
| MB8 | só a raiz (`html { font-size }`) escalada, com o texto do Filament em `--text-*` fixo em px | CT-B02 | razão 1,0 em um dos dois elementos |

**Estouro de teto justificado**: o perfil `padrão` prevê 1 CT-B (happy path); aqui são 2. O gate vence o teto: MB1–MB4 só morrem com JavaScript executado (CT-B01) e MB5–MB8 só com o tamanho calculado depois da cascata (CT-B02) — nenhum dos dois conjuntos é falsificável pelo HTML servido.

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| densidade medida no navegador | já tem contrato HTTP existente (`DensidadeDoLayoutTest`) e a feature só muda **quem** escolhe; CT-09 do `04` prova pela declaração servida |
| cor primária por `getComputedStyle` | `FilamentColor::getColors()` depois do GET já discrimina (CT-09, CT-12 do `04`) |
| `inDarkMode()` com cada preferência | o `00` não fala de tema; nenhum mutante previsto depende dele |
| seção Aparência visível no navegador | provado por HTTP e componente (CT-29…CT-31 do `04`); a seção não depende de JS (Superfície de UI) |

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Painéis — layout: recolher do menu por Alpine do Filament | `->sidebarCollapsibleOnDesktop()`/`->sidebarFullyCollapsibleOnDesktop()` por Closure nos três providers, lendo `AparenciaDoUsuario::modoDoMenu()` | sim | CT-B01: 4 linhas (`/admin` nos dois modos; `/app` e `/infra` em Esconder tudo, recolhendo e reabrindo pelo botão do topo), 7/7 do arquivo em série |
| 2 | Painéis — layout: fonte efetiva em toda tela autenticada | variáveis `--text-*` redeclaradas no `<style>` do render hook `STYLES_BEFORE` | sim | CT-B02: razões 0,875 / 1,125 / 1,25 medidas no título e no menu, delta 0,01 |
