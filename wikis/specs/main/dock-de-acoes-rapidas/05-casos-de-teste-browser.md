# Casos de Teste de Browser — Dock de ações rápidas (favoritos do usuário)

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "G7 — Dock no navegador" de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/Browser/DockDeAtalhosTest.php` ou `composer test:browser` (em série — `.ai/rules/testes-browser.md`: nunca `--parallel` com browser). O `phpunit.xml` declara a suíte `Browser` (`<testsuite name="Browser">`), então `--testsuite=Browser` também vale
> CT-B: 2 · Mutantes previstos: 6 · Sem matador: 0
> Estouro declarado do teto (perfil completo: 1 happy path + 1 erro visível): CT-B02 é o segundo cenário e não é erro visível. Ele vem da P-05 (tema escuro), e cor computada só o navegador prova — pelo passo 6, item 5, o gate vence o teto

## Pré-requisitos
- [ ] `npm run build` executado (`composer test:browser` já embute, junto com `view:cache`)
- [ ] `php artisan filament:assets` executado depois do `composer require` do pacote — sem os assets do dock, é exatamente o defeito que este CT-B existe para pegar, então rodar o cenário **antes** e **depois** da publicação (antes: vermelho)
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($bruno)` antes do `visit()`
- [ ] Aquecimento pelo kernel dentro do cenário, antes do `visit()`: `$this->get('/admin')` (compila os componentes fora do cronômetro — `.ai/rules/testes-browser.md`); o `beforeEach` não arranja painel

## Seletores
| Elemento | Seletor | Já existe? |
|---|---|---|
| gatilho que abre o dock | `aria-label`/atributo do botão do pacote — confirmar no vendor após a instalação e citar `arquivo:símbolo:linha` | não (pacote não instalado); dívida: sem `data-testid` |
| favorito "lista de usuários do /admin" | `a[href$="/admin/users"]` dentro do contêiner do dock (único: seletor por atributo, não por texto — o menu lateral também tem "Usuários") | não |
| superfície do dock (contêiner aberto) | `.qad-root` (sugerido) — confirmar no vendor qual elemento pinta o fundo do dock aberto e citar `arquivo:símbolo:linha` | não |
| alternador de tema do painel | `[aria-label="Mudar para tema escuro"]` (`.ai/rules/testes-browser.md`), dentro do menu do usuário no painel — confirmar o caminho até ele | sim (rótulo); caminho no painel a confirmar |

---

## CT-B01: o dock abre e o favorito leva ao destino

**Por que browser e não Livewire**: abrir o dock depende do JavaScript do pacote (Alpine e assets publicados por `filament:assets`). O teste de componente prova o `href`, mas não prova que o dock abre e que o clique navega. Asset ausente ou erro de Alpine deixam todos os CT de componente verdes e o dock inutilizável.

```gherkin
# language: pt
  Cenário: [CT-B01] bruno abre o dock e chega à tela do favorito
    Dado o dock ligado no /admin e bruno, com Usar:Dock, com o favorito "lista de usuários do /admin"
    E bruno na página inicial do /admin
    Quando ele abre o dock e clica no favorito
    Então o navegador está em /admin/users
    E nenhum erro de JavaScript foi registrado
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | aquecer e autenticar | `$this->actingAs($bruno); $this->get('/admin')->assertOk();` | — |
| 2 | visitar o painel | `$pagina = visit('/admin');` | dock fechado na tela |
| 3 | abrir o dock | `->click('{gatilho do dock}')` | favorito visível |
| 4 | clicar no favorito | `->click('a[href$="/admin/users"]')` | navega |
| 5 | conferir destino | `->assertPathIs('/admin/users')` (primeiro, espera a navegação) | lista de usuários |
| 6 | console | `->assertNoJavaScriptErrors()` (tela de plugin de terceiro: não `assertNoSmoke()`) | — |

**Assertions**: `assertPathIs('/admin/users')` primeiro (é o oráculo) · `assertNoJavaScriptErrors()` como apoio, nunca sozinho

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-B1 | assets do pacote não publicados (`filament:assets` fora do passo de instalação) — o dock não abre | CT-B01 | passo 4: o favorito nunca fica clicável; `assertPathIs('/admin/users')` estoura no timeout |
| M-B2 | favorito registrado como action sem URL (abre modal ou não faz nada) | CT-B01 | `assertPathIs('/admin/users')`: o caminho continua `/admin` |
| M-B3 | erro de Alpine/JS no componente do dock ao montar | CT-B01 | `assertNoJavaScriptErrors()` reprova |

---

## CT-B02: o dock acompanha o tema escuro do painel

> Origem: `P-05` (dimensão G do quality gate não verificada; pedido do solicitante em 2026-10-14) · técnica: **EP** do tema efetivo do painel (escuro pelo navegador / claro / escuro escolhido no painel com o navegador claro) + **controle positivo** (a linha clara)

**Por que browser e não Livewire**: o tema é aplicado pelo Alpine do Filament, a partir de `prefers-color-scheme` e da escolha gravada no navegador. Um `$this->get()` devolve o mesmo HTML nos dois temas (`tests/Browser/TemaEscuroTest.php`), e `assertSee` passa com texto claro sobre fundo claro (`.ai/rules/testes-browser.md` §"`assertSee` não valida tema"). Só a cor **computada** pelo navegador distingue as duas implementações.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B02] a superfície do dock aberto usa as cores do tema efetivo do painel
    Dado o dock ligado no /admin e bruno, com Usar:Dock, com o favorito "lista de usuários do /admin"
    E o /admin carregado com <tema_do_painel>, e o fundo computado da página é <fundo_da_pagina>
    Quando ele abre o dock
    Então a luminância relativa da cor de fundo efetiva da superfície do dock é <luminancia>
    E a do item do favorito, também

    Exemplos:
      | tema_do_painel                                                          | fundo_da_pagina | luminancia | # partição                                     |
      | o navegador em modo escuro (`inDarkMode()`)                             | escuro (≤ 0,2)  | ≤ 0,2      | escuro pelo navegador                          |
      | o navegador em modo claro (`inLightMode()`)                             | claro (≥ 0,5)   | ≥ 0,5      | controle positivo                              |
      | o navegador em modo claro e o tema escuro escolhido no alternador do painel | escuro (≤ 0,2) | ≤ 0,2   | escuro pela escolha do painel, não do sistema  |
```

**Medida (o oráculo)**: `script()` devolve, em JSON, para a superfície do dock e para o item do favorito, a **cor de fundo efetiva** e a luminância relativa dela.
- **Cor efetiva**: o primeiro `getComputedStyle(el).backgroundColor` não transparente, subindo do elemento até a raiz do dock, **sem sair dela**. Se tudo dentro do dock for transparente, a medida devolve `null` e o cenário falha — a cor da página atrás não vale como cor do dock.
- **Luminância relativa**: WCAG 2.x, `0,2126·R + 0,7152·G + 0,0722·B`, com cada canal linearizado.
- **Limiares declarados**: escuro ≤ 0,2 e claro ≥ 0,5. A faixa entre eles reprova as duas linhas de propósito, porque um cinza médio não é "acompanhar o tema".
- **Fundo da página**: medido no `<body>` do painel antes de abrir o dock. É a pré-condição de que o tema pedido foi aplicado, sem depender da classe `dark`, que `TemaEscuroTest` registra como detalhe do Filament que muda entre versões.

**Roteiro executável** (uma visita por tema — `.ai/rules/testes-browser.md` §"`inDarkMode()`/`inLightMode()` só valem no LOAD")
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | aquecer e autenticar | `$this->actingAs($bruno); $this->get('/admin')->assertOk();` | — |
| 2 | visitar no tema da linha | linhas 1 e 2: `$pagina = visit('/admin')->inDarkMode()` / `->inLightMode()`; linha 3: `visit('/admin')->inLightMode()` e, em seguida, o clique no alternador do painel (`[aria-label="Mudar para tema escuro"]`, pelo caminho confirmado no vendor) | painel no tema da linha |
| 3 | pré-condição | `->assertScript("{luminância do fundo do body} {≤ 0,2 \| ≥ 0,5}")` | — |
| 4 | abrir o dock | `->click('{gatilho do dock}')` e `->assertVisible('{superfície do dock}')` | dock aberto |
| 5 | medir | `$medida = json_decode((string) $pagina->script(<<<'JS' … JS), true, flags: JSON_THROW_ON_ERROR);` com a cor efetiva e a luminância da superfície e do item | — |
| 6 | afirmar | `expect($medida['superficie']['luminancia'])->toBeLessThanOrEqual(0.2)` (escuro) ou `->toBeGreaterThanOrEqual(0.5)` (claro); idem para `item` | — |
| 7 | evidência (apoio, não oráculo) | `->screenshot(filename: 'dock-tema-{linha}')` — fica em `tests/Browser/Screenshots`; só vira arte se entrar em `KitArte::IMAGENS` | captura |
| 8 | console | `->assertNoJavaScriptErrors()` | — |

**Assertions**: a luminância medida é o oráculo. `assertSee` não entra, e `assertNoJavaScriptErrors()` é só apoio. Um screenshot sem baseline não prova cor certa (`references/pest-plugin-browser.md` §"Tema, cor e acessibilidade").

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M-B4 | o dock não tem regra para o tema escuro: fundo branco sobre a página escura | CT-B02 | linhas 1 e 3: luminância ≤ 0,2; o mutante mede ~1,0 |
| M-B5 | o dock é forçado escuro em qualquer tema | CT-B02 | linha 2 (controle positivo): luminância ≥ 0,5; o mutante mede ≤ 0,2 |
| M-B6 | o CSS do dock segue `prefers-color-scheme` do sistema e não o tema escolhido no painel | CT-B02 | linha 3: com o navegador claro e o painel escuro, luminância ≤ 0,2; o mutante fica claro |

Teto do perfil completo: 1 happy path + 1 erro visível. CT-B02 é estouro declarado no cabeçalho (P-05; o gate vence o teto). Os cenários cortados estão no `04` (`## Cogitado e cortado`).

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Dock (`quick-action-dock`) em todas as páginas autenticadas dos painéis com dock ligado; abrir/fechar; clicar no favorito navega | | | CT-B01 |
| 2 | Item "Adicionar esta página" (action do dock) | | | CT-30 (componente) |
| 3 | Slideover "Gerenciar atalhos" (`schema` + `slideOver()`) | | | CT-15, CT-16 (componente) |
| 4 | Seção "Dock de atalhos" na aba Kit de `/admin/configuracoes-da-aplicacao` | | | CT-08, CT-09 (componente) |
| 5 | Dock no tema escuro do painel (P-05) | | | CT-B02 (luminância medida; capturas `dock-tema-*` como apoio) |
