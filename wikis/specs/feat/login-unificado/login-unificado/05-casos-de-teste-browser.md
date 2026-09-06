# Casos de Teste de Browser — Página única de login (`/login`)

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Comando: `composer test:browser` (em série — nunca `--parallel`; `npm run build` e `view:cache` são pré-requisitos — `.ai/rules/testes-browser.md`).

## Gate

O que **só o navegador prova** nesta feature: a página única é uma tela do Auth Designer servida **fora** de rota de painel, com formulário Livewire — o layout (arte, toggle de tema) e o console limpo no ciclo completo *login → escolha → painel* não se provam por componente. O `04` prova cada decisão; o CT-B prova que as três telas se encadeiam no navegador sem erro de JavaScript. Um cenário: o teto do perfil `padrão`.

## Pré-requisitos

- [ ] `npm run build` executado (`public/build/manifest.json`)
- [ ] `php artisan view:cache` (regra do projeto)
- [ ] Aquecimento pelo kernel antes do primeiro `visit()` num arquivo isolado (`.ai/rules/testes-browser.md`, "aqueça pelo kernel"): um `$this->get('/login')` antes
- [ ] Chave ligada por `config()->set('kit.login.unificado', true)` — mesmo processo, vale dentro do navegador
- [ ] Persona `admin+infra` criada com a factory; **login pela tela** neste único cenário (é o caminho real do usuário — os demais usam `actingAs()`)

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| campo de e-mail | `#form\.email` (id gerado do Filament) | sim — `PerfisTest` usa `fill('#form\.email', …)` |
| campo de senha | `#form\.password` | sim |
| botão entrar | texto visível "Login" (`press('Login')`, como em `PerfisTest`) | sim |
| cartão do painel | texto visível "Administração" dentro de `.kit-cards-page` | sim (boas-vindas) |
| layout de auth | `.fi-auth-layout` | sim — `BloqueioDeSessaoTest` afirma a classe |

---

## CT-B01: entrar pela página única, escolher o painel e chegar nele, sem erro de JavaScript

**Por que browser e não Livewire**: encadeia três páginas com redirecionamentos reais e um formulário Livewire fora de rota de painel; o console limpo e o layout do Auth Designer não são observáveis por componente.

```gherkin
# language: pt

  Cenário: [CT-B01] quem tem dois painéis entra por /login, escolhe Administração e chega ao /admin
    Dado a chave ligada
    E uma pessoa com os papéis admin e infra
    Quando ela abre /login, preenche e-mail e senha e envia
    Então a URL passa a ser /login/painel
    E a página mostra os cartões Administração e Infraestrutura, e não o do Painel do negócio
    E ao clicar em Administração a URL passa a ser /admin
    E nenhuma das três páginas registrou erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | aquecer e abrir | `$this->get('/login'); $p = visit('/login');` | tela de login com a arte e o layout `fi-auth-layout` |
| 2 | conferir layout | `->assertPresent('.fi-auth-layout')->assertNoJavaScriptErrors()` | — |
| 3 | preencher | `->fill('#form\.email', $email)->fill('#form\.password', 'password')` | — |
| 4 | enviar | `->press('Login')` | navega |
| 5 | esperar a navegação | `->assertPathIs('/login/painel')` — **antes** de qualquer `assertSee` | tela de escolha |
| 6 | cartões | `->assertSee('Administração')->assertSee('Infraestrutura')->assertDontSee('Painel do negócio')->assertNoJavaScriptErrors()` | dois cartões |
| 7 | escolher | `->click('Administração')->assertPathIs('/admin')` | dashboard do admin |
| 8 | console | `->assertNoJavaScriptErrors()` | — |

**Assertions**: `assertPathIs` primeiro (passos 5 e 7) · `assertNoJavaScriptErrors()` em cada página (tela de terceiro: **não** `assertNoSmoke()`) · âncora de conteúdo: os dois cartões presentes e o terceiro ausente.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-B01 | o `/livewire/update` da página única não tem painel corrente e o `authenticate()` estoura (risco 4 do PRD) — o formulário não navega | CT-B01 (passo 5) |
| M-B02 | a escolha renderiza com o layout de painel ou sem a folha dos cartões — cartões invisíveis/desalinhados e erro de JS de asset ausente | CT-B01 (passos 6 e 8) |
| M-B03 | o cartão aponta para a URL errada (ex.: `getUrl()` nulo por tenancy resolvido como `''`) | CT-B01 (passo 7) |

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| dark mode na página única | `assertSee` não valida tema (`.ai/rules/testes-browser.md`); o toggle de tema é do Auth Designer, já coberto pelos CT-B das telas de login existentes |
| login com um só painel pela tela | mesmo formulário e mesmo layout do CT-B01; o destino é provado em CT-12 por componente |
| anônimo em `/admin/x` seguindo os redirects até `/login` | HTTP puro — CT-05 |
| `assertNoAccessibilityIssues()` na escolha | os cartões são do pacote da boas-vindas, que já tem CT-B; nada novo de a11y aqui |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `/login` com o layout do Auth Designer (arte à esquerda, toggle de tema) fora de painel | `TelaLoginUnificada` sob `panel:app`; arte, toggle e formulário renderizados | ✅ | screenshot do CT-B01 (2026-09-05); CT-07 (`fi-auth-layout`) |
| 2 | `/login/painel` em `CardsPage`, layout `simple`, escopo `kit-cards-page`, só os acessíveis | `EscolhaDePainel`; dois cartões para `admin+infra`, sem "Painel do negócio" | ✅ | CT-B01 passos 5-6; CT-17 |
| 3 | um painel → direto; dois → escolha; cartão leva à raiz do painel | clique em "Administração" → `/admin` com dashboard | ✅ | CT-B01 passo 7; CT-12, CT-13 |
| 4 | toggle na aba Login do Settings, efeito sem deploy | seção "Página única de login", toggle liga e desliga com efeito no request seguinte | ✅ | CT-03 |
