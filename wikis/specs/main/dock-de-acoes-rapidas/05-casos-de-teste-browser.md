# Casos de Teste de Browser — Dock de ações rápidas (favoritos do usuário)

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "G7 — Dock no navegador" de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/Browser/DockDeAtalhosTest.php` ou `composer test:browser` (em série — `.ai/rules/testes-browser.md`: nunca `--parallel` com browser). O `phpunit.xml` declara a suíte `Browser` (`<testsuite name="Browser">`), então `--testsuite=Browser` também vale

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

Teto do perfil completo: 1 happy path + 1 erro visível. Nenhuma `RQ` define um erro visível próprio do dock no navegador; os cenários cortados estão no `04` (`## Cogitado e cortado`).

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Dock (`quick-action-dock`) em todas as páginas autenticadas dos painéis com dock ligado; abrir/fechar; clicar no favorito navega | | | CT-B01 |
| 2 | Item "Adicionar esta página" (action do dock) | | | CT-30 (componente) |
| 3 | Slideover "Gerenciar atalhos" (`schema` + `slideOver()`) | | | CT-15, CT-16 (componente) |
| 4 | Seção "Dock de atalhos" na aba Kit de `/admin/configuracoes-da-aplicacao` | | | CT-08, CT-09 (componente) |
