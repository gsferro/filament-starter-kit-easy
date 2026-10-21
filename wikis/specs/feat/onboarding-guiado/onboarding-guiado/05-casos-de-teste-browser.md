# Casos de Teste de Browser — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Runtime: `pest-plugin-browser` v5.0.1 (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "G13 Tour no navegador" de `## Costuras de Teste` do `04` (regra R9 — `RQ-28`, `RQ-18`)
> Comando: `vendor/bin/pest tests/Browser/OnboardingGuiadoTest.php` (série; `composer test:browser` embute `npm run build` e `view:cache`). O `phpunit.xml` declara a suíte `Browser`.
> Derivado do **requisito**, não do plano. Teto do perfil padrão: 1 CT-B (happy path), estourado uma vez pelo CT-B02 (`/infra`, ADV-26 da revisão adversarial), com justificativa no próprio cenário. Os candidatos cortados estão na `## Tabela de cogitado e cortado` do `04`.
> CT-B: 2 (CT-B01 `/admin`, CT-B02 `/infra`) · Mutantes: MB1–MB6

## Pré-requisitos
- [ ] `npm run build` executado (`composer test:browser`)
- [ ] `view:cache` e aquecimento do painel por um `$this->get('/admin')` no `beforeEach` (`.ai/rules/testes-browser.md`, "aqueça pelo kernel")
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($administradora)` antes do `visit()`
- [ ] Tutorial ligado por `ligarTutorialGuiado()` (helper planejado em `tests/Pest.php`) e jornadas por `$this->seed(OnboardingSeeder::class)`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| botão do checklist flutuante | `button[aria-label="{título da jornada do /admin}"]` (o `aria-label` é o título da jornada) | sim — vendor `launcher.blade.php` |
| painel do checklist aberto | `[role="dialog"][aria-label="{título da jornada do /admin}"]` | sim — vendor |
| botão de iniciar o tour do passo de Usuários | texto traduzido do botão de tour na linha do passo | sim — vendor `steps.blade.php` |
| popover do tour | `[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]` | sim — vendor `tour.blade.php` |

Sem `data-testid` próprio: o markup é do pacote; dívida registrada, seletor por `aria-*`.

---

## CT-B01: com o tutorial ligado, iniciar o tour de um passo pelo checklist abre o popover com a primeira parada

**Por que browser e não Livewire**: o componente só **despacha** `onboarding-tour-start` com as paradas (provado no CT-14 do `04`); quem abre o popover, posiciona e mostra o texto é o runner Alpine `onboardingTour` do pacote. Só JavaScript executado prova que o tour "aparece" (RQ-28) — e que os assets do pacote foram registrados no painel.

```gherkin
# language: pt
  Cenário: [CT-B01] o tour do passo de Usuários abre no navegador a partir do checklist do /admin
    Dado as jornadas do kit semeadas, o tutorial ligado e a administradora no /admin com o passo de Usuários pendente
    Quando ela abre o checklist flutuante e inicia o tour do passo de Usuários
    Então o popover do tour está visível com o título pt_BR da primeira parada desse passo
    E o console não tem erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | entrar autenticada | `$this->actingAs($administradora); $pagina = visit('/admin');` | dashboard |
| 2 | conferir o caminho | `->assertPathIs('/admin')` | — |
| 3 | abrir o checklist | `->click('button[aria-label="'.$tituloDaJornada.'"]')` | painel do checklist com os passos |
| 4 | iniciar o tour de Usuários | `->click('{botão de tour na linha do passo de Usuários}')` | — |
| 5 | afirmar a parada | `->assertVisible('[x-data^="onboardingTour"] [role="dialog"]')->assertSeeIn('[x-data^="onboardingTour"] [role="dialog"]', $tituloDaPrimeiraParada)` | popover com o título |
| 6 | apoio | `->assertNoJavaScriptErrors()` | — |

`$tituloDaPrimeiraParada` é lido do banco (a primeira parada do passo cuja tela é a lista de Usuários), nunca escrito à mão. Se a primeira parada navega para `/admin/users`, `assertPathIs('/admin/users')` vem antes do passo 5 (fato 4 do plugin). Nada de `wait()`.

**Assertions**: `assertPathIs` primeiro · `assertNoJavaScriptErrors()` só como apoio (tela de pacote — nada de `assertNoSmoke()`) · âncora única: o título da parada dentro do popover do tour

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB1 | `tours(false)` fixo (ou closure ignorada) — o runner não é renderizado no painel | CT-B01 | popover visível; o mutante não tem o `x-data="onboardingTour"` |
| MB2 | assets JS/CSS do pacote registrados só no painel onde o plugin já estava, ou não publicados no build | CT-B01 | popover visível e console limpo; o mutante tem erro de JS (`onboardingTour is not defined`) |
| MB3 | parada semeada com `title` monolíngue/ausente (popover centralizado vazio) | CT-B01 | título pt_BR da parada no popover; o mutante mostra vazio |
| MB4 | a primeira parada aponta rota de outro painel (o runner navega para fora e o popover não abre) | CT-B01 (e CT-17 no backend) | popover no `/admin`; o mutante sai do painel |

---

## CT-B02: no `/infra`, iniciar o tour de um passo pelo checklist abre o popover com a primeira parada

**Por que browser e não Livewire**: mesmo motivo do CT-B01, num painel que hoje não registra o plugin, com tema e assets próprios. O backend prova que o checklist é renderizado no `/infra` (CT-15) e que o evento é despachado (CT-14); só o navegador prova que o runner existe e abre ali (RQ-28, "nos três painéis"). ADV-26 — estouro do teto do perfil padrão (1 CT-B) declarado: o gate de falsificabilidade vence o teto (MB5, MB6 não têm outro matador).

```gherkin
# language: pt
  Cenário: [CT-B02] o tour do passo de Pulse abre no navegador a partir do checklist do /infra
    Dado as jornadas do kit semeadas, o tutorial ligado e a Íris no /infra com o passo de Pulse pendente
    Quando ela abre o checklist flutuante e inicia o tour do passo de Pulse
    Então o popover do tour está visível com o título pt_BR da primeira parada desse passo
    E o console não tem erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | aquecer o painel pelo kernel | `$this->actingAs($iris); $this->get('/infra');` (no `beforeEach`) | — |
| 2 | entrar | `$pagina = visit('/infra');` | dashboard do `/infra` |
| 3 | conferir o caminho | `->assertPathIs('/infra')` | — |
| 4 | abrir o checklist | `->click('button[aria-label="'.$tituloDaJornadaInfra.'"]')` | painel do checklist |
| 5 | iniciar o tour de Pulse | `->click('{botão de tour na linha do passo de Pulse}')` | — |
| 6 | afirmar a parada | `->assertSeeIn('[x-data^="onboardingTour"] [role="dialog"]', $tituloDaPrimeiraParadaDePulse)` (antes, `assertPathIs` se a parada navega) | popover com o título |
| 7 | apoio | `->assertNoJavaScriptErrors()` | — |

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB5 | `tours()` ligado só no `/admin` (o plugin do `/infra` registrado com o default do pacote ou `tours(false)`) — ADV-26 | CT-B02 | popover visível no `/infra`; o mutante não renderiza o runner |
| MB6 | assets do pacote (`onboardingTour`) não registrados no painel `/infra`, que tem tema próprio | CT-B02 | console limpo e popover; o mutante tem `onboardingTour is not defined` |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Checklist flutuante em todas as telas dos três painéis (render hook `BODY_END`) | | | |
| 2 | Tour Alpine do pacote, iniciado pelo checklist e pela página | | | |
| 3 | Paradas por `route` + alvo nomeado pelo pacote ou popover centralizado | | | |
