# Casos de Teste de Browser — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Runtime: `pest-plugin-browser` v5.0.1 (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linhas "G13 Tour no navegador" (regra R9 — `RQ-28`, `RQ-18`) e "G14 Acessibilidade do tutorial no navegador" (regras R36 — `P-27` e R37 — `P-28`) de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/Browser/OnboardingGuiadoTest.php` (série; `composer test:browser` embute `npm run build` e `view:cache`). O `phpunit.xml` declara a suíte `Browser`.
> Derivado do **requisito**, não do plano. Teto do perfil padrão: 1 CT-B (happy path), estourado pelo CT-B02 (`/infra`, ADV-26 da revisão adversarial) e pelos CT-B03 e CT-B04 (quality gate, ciclo 3: QA-15 → P-27, QA-16 → P-28), cada um com a justificativa no próprio cenário — o gate de falsificabilidade vence o teto (MB7–MB16 não têm outro matador). Os candidatos cortados estão na `## Tabela de cogitado e cortado` do `04`.
> CT-B: 4 (CT-B01 `/admin`, CT-B02 `/infra`, CT-B03 `/admin` e `/infra` × claro e escuro, CT-B04 `/admin`) · Mutantes: MB1–MB16
> Revisão adversarial dos CT-B03 e CT-B04: **não feita** — o teto de 2 rodadas foi atingido (`04`, `## Fechamento da 2ª rodada adversarial`); registrado no `## Fechamento do quality gate, ciclo 3` do `04`.

## Pré-requisitos
- [ ] `npm run build` executado (`composer test:browser`)
- [ ] `view:cache` e aquecimento do painel por um `$this->get('/admin')` no `beforeEach` (`.ai/rules/testes-browser.md`, "aqueça pelo kernel")
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($administradora)` antes do `visit()`
- [ ] Tutorial ligado por `ligarTutorialGuiado()` (helper planejado em `tests/Pest.php`) e jornadas por `$this->seed(OnboardingSeeder::class)`
- [ ] CT-B03 — linha de base medida uma vez antes do primeiro vermelho: o dashboard do mesmo painel com o tutorial **desligado** passa `assertNoAccessibilityIssues()` nos dois temas. O axe do plugin avalia o documento inteiro (`window.axe.run()` sem escopo), então uma violação serious alheia ao tutorial deixaria o CT-B03 vermelho por motivo que não é o dele (Q?1). Não é cenário: não mata mutante; é a pré-condição que torna o vermelho atribuível

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| botão do checklist flutuante | `button[aria-label="{título da jornada do /admin}"]` (o `aria-label` é o título da jornada) | sim — vendor `launcher.blade.php` |
| painel do checklist aberto | `[role="dialog"][aria-label="{título da jornada do /admin}"]` | sim — vendor |
| botão de iniciar o tour do passo de Usuários | texto traduzido do botão de tour na linha do passo; no checklist, `[wire\:click*="startTour"][wire\:click*="usuarios"]` (o do CT-B01) | sim — vendor `steps.blade.php` |
| popover do tour | `[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]` | sim — vendor `tour.blade.php` |
| conteúdo da página "Primeiros passos" (CT-B03) | `.fi-main` (o título da jornada lido do banco dentro dele) | sim — Filament |
| tema efetivo do documento (CT-B03) | `document.documentElement.classList.contains('dark')` por `assertScript()` — o Filament aplica o tema pela classe `dark` | sim — Filament |

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

## CT-B03: a página "Primeiros passos" com o checklist aberto passa o axe no nível serious, nos temas claro e escuro

*(nova em 2026-10-22: QA-15 do quality gate, ciclo 3 → P-27)* · regra **R36** do `04` · grupo G14

**Por que browser e não Livewire**: contraste é cor computada sobre fundo computado, depois do CSS do pacote, do tema do painel e do tema claro/escuro aplicados — só o navegador calcula. O componente prova que o botão existe; não prova que ele é legível (fato 10 do plugin: `assertSee` passa com texto ilegível).

**Estouro do teto declarado**: o perfil padrão (área C) tem teto de 1 CT-B, já estourado pelo CT-B02. Os mutantes MB7–MB11 não têm matador em outra camada; o gate vence o teto (SKILL.md, passo 6, item 5).

**Escolha dos painéis — `/admin` e `/infra`, sem o `/app`**: o defeito medido é igual nos três (mesma regra CSS do pacote, a mesma cor `#e17100`, QA-15), então um painel prova o MB7. O que distingue os painéis é **onde a correção mora**: o `/infra` tem tema e assets próprios (CT-B02, MB6), e a correção plausível no tema do kit pode não chegar a ele (MB9). O `/admin` é o painel do tema do kit; o `/infra` é o painel com tema próprio — e o de mais elementos atingidos (17/15 na página). O `/app` repete a linha do `/admin` se carrega o mesmo tema; se não carrega, ele entra (Q?2).

**Por que página e checklist num só `Quando`**: o checklist é renderizado em toda tela do painel (render hook `BODY_END`, `## Roteiro de Validação`, linha 1), inclusive na própria página "Primeiros passos". Abrir o checklist sobre a página põe as duas superfícies de P-27 sob o mesmo `axe.run()`, e o relatório do plugin lista o seletor de cada violação — a falha diz qual superfície.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B03] a página "Primeiros passos" com o checklist aberto não tem problema sério de acessibilidade, no <painel> e no tema <tema>
    Dado as jornadas do kit semeadas, o tutorial ligado e <persona> sem nenhum passo concluído no <painel>
    E a página "Primeiros passos" do <painel> aberta no tema <tema>, com o documento confirmando o tema <tema>
    E o checklist flutuante aberto sobre a página, com o painel do checklist visível e o título da jornada do <painel> no conteúdo da página
    Quando a tela é avaliada pelo axe no nível serious
    Então nenhuma violação de impacto serious ou critical é relatada

    Exemplos:
      | painel | persona          | tema   | # partição                                                 |
      | /admin | a administradora | claro  | painel do tema do kit                                      |
      | /admin | a administradora | escuro | mesmo painel, outro tema: a correção não pode ser de um só |
      | /infra | a Íris           | claro  | painel com tema e assets próprios (CT-B02)                 |
      | /infra | a Íris           | escuro | idem, tema escuro                                          |
```

- **Controle 1 — o checklist está aberto quando o axe roda**: `assertVisible` do painel do checklist **imediatamente antes** do `assertNoAccessibilityIssues()`. O axe pula elemento oculto na regra `color-contrast`; com o checklist fechado, as 3 violações dele somem (QA-15: "os dashboards com o checklist fechado dão 0") e a linha passa no vácuo
- **Controle 2 — a página tem o conteúdo atingido**: o título da jornada do painel (lido do banco, como no CT-B01) está em `.fi-main`; a persona sem passo concluído garante passo pendente na lista (o status de próximo passo e os botões de iniciar tour existem)
- **Controle 3 — o tema é o da linha**: `assertScript("document.documentElement.classList.contains('dark')", true|false)`. `inDarkMode()` só emula a preferência do navegador; se o kit fixar o tema por outra via, a linha "escuro" mediria o claro de novo (pest-plugin-browser, §Tema)
- `assertNoAccessibilityIssues()` com o nível default `1` (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:assertNoAccessibilityIssues:95`): reprova `critical` e `serious`; `color-contrast` é `serious`. O oráculo é o documento inteiro — ver o pré-requisito da linha de base e Q?1
- Não é oráculo de cor específica (`getComputedStyle`): P-27 diz "contraste suficiente (WCAG AA)", não qual cor; qualquer correção que atinja 4,5:1 (texto de 12 px) serve

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | semear, ligar, entrar e aquecer | `$this->seed([...]); ligarTutorialGuiado(); $this->actingAs($persona); $this->get('/{painel}/onboarding');` | — |
| 2 | abrir a página no tema da linha | `$pagina = visit('/{painel}/onboarding')->on()->desktop()->inLightMode();` (ou `->inDarkMode()`) | página "Primeiros passos" |
| 3 | conferir o caminho | `->assertPathIs('/{painel}/onboarding')` | — |
| 4 | controle 3 (tema) | `->assertScript("document.documentElement.classList.contains('dark')", $escuro)` | — |
| 5 | controle 2 (conteúdo) | `->assertSeeIn('.fi-main', $tituloDaJornada)` | lista de passos |
| 6 | abrir o checklist | `->click('button[aria-label="'.$tituloDaJornada.'"]')` | painel do checklist |
| 7 | controle 1 (aberto) | `->assertVisible('[role="dialog"][aria-label="'.$tituloDaJornada.'"]')` | — |
| 8 | oráculo | `->assertNoAccessibilityIssues()` | — |

`$tituloDaJornada` sai de `titulosDoTourSemeado()` (já existe no arquivo). Se a página também tiver um elemento com `aria-label` igual ao título, o seletor do passo 6 fica ambíguo: confirmar no primeiro vermelho e restringir ao botão fora do `.fi-main`. Nada de `wait()`. **Tem de falhar antes da correção** (o estado atual é o MB7), nas quatro linhas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB7 | **central** — os botões primários do pacote ficam com `var(--primary-600)` e texto branco (3,2:1 com `#e17100`), sem sobrescrita no tema do kit: o estado medido no QA-15 | CT-B03 (as quatro linhas) | nenhuma violação serious; o mutante relata `color-contrast` em "Iniciar tour", no status de próximo passo e no botão da jornada |
| MB8 | sobrescrita escrita para um tema só (ex.: só sob `.dark`, ou só fora dele, ou com par `dark:` que volta à cor do pacote) | CT-B03 (linhas do tema esquecido) | nenhuma violação serious no outro tema; o controle 3 garante que o tema da linha é o efetivo |
| MB9 | sobrescrita só no CSS do tema do kit carregado pelo `/admin`; o `/infra`, com tema e assets próprios, continua com a regra do pacote | CT-B03 (linhas 3 e 4) | nenhuma violação serious no `/infra`; o mutante relata `color-contrast` ali |
| MB10 | sobrescrita só do botão primário (`.fio-button--primary`); o rótulo de status do próximo passo continua com a mesma combinação de 3,2:1 | CT-B03 (as quatro linhas; persona sem passo concluído) | nenhuma violação serious; o mutante relata o status |
| MB11 | sobrescrita escopada ao conteúdo da página (`.fi-main …`); o checklist, renderizado fora dele (`BODY_END`), fica com a cor do pacote | CT-B03 (as quatro linhas; controle 1) | nenhuma violação serious com o checklist visível; o mutante relata as 3 do checklist |

---

## CT-B04: a tecla Esc fecha o checklist aberto, sem concluir nem dispensar nada

*(nova em 2026-10-22: QA-16 do quality gate, ciclo 3 → P-28)* · regra **R37** do `04` · grupo G14

**Por que browser e não Livewire**: a tecla é um evento de teclado tratado no navegador (Alpine/JS) e depende de onde está o foco; `Livewire::test` não tem foco nem tecla. O fechamento é visibilidade no DOM.

**Estouro do teto declarado**: o mesmo do CT-B03 — MB12–MB16 não têm matador em outra camada.

**Um painel só (`/admin`)**: o checklist é o mesmo componente, montado pelo mesmo render hook nos três painéis (`## Roteiro de Validação`, linha 1), e o QA-16 mediu o mesmo comportamento nas seis combinações (3 painéis × 2 temas). Nenhum mutante plausível do tratamento do Esc depende do painel; o que muda o resultado é **o foco** — daí a partição.

**Partição do foco**: (1) no botão do checklist que abriu o painel — o caso medido no QA-16 (Enter abre e o foco fica no botão, que está fora do `role="dialog"`); (2) dentro do painel, num controle do próprio checklist. Um tratador preso ao painel só ouve o Esc da linha 2; um preso ao botão só o da linha 1.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B04] a tecla Esc fecha o checklist aberto, sem concluir nem dispensar nada, com o foco <foco>
    Dado as jornadas do kit semeadas, o tutorial ligado e a administradora no /admin, sem passo concluído nem jornada dispensada
    E o checklist aberto pela tecla Enter no botão do checklist, com o painel do checklist visível e o foco <foco>
    Quando ela tecla Esc
    Então o painel do checklist não está visível, o botão do checklist está visível e nenhum popover de tour está aberto
    E, reaberto pelo botão, o checklist mostra a jornada do /admin com o botão de iniciar o tour do passo de Usuários
    E o progresso de passos, o progresso da jornada e as preferências do tutorial dela têm as mesmas linhas de antes do Esc
    E, recarregada a página, o botão do checklist continua visível

    Exemplos:
      | foco                                                         | # partição                                       |
      | no botão do checklist que abriu o painel                     | o caso medido no QA-16; o botão fica fora do painel |
      | dentro do painel, no botão de iniciar o tour do passo de Usuários | foco dentro do `role="dialog"`                 |
```

- **"Visível antes"** é o `assertVisible` do painel no `Dado`, logo antes da tecla; **"invisível depois"** é `assertMissing`, que o plugin reexecuta até o teto do `timeout()` — cobre a transição do Alpine sem `wait()`
- **Não-efeito com destinatário** (gate, item 7): a administradora tem passos pendentes e a jornada não dispensada, então concluir, pular, dispensar, "lembrar depois" e "não mostrar mais" teriam linha ou marca a gravar. As contagens (`onboarding_step_progress`, `onboarding_flow_progress`, `onboarding_preferences` da pessoa) são lidas **depois** de reabrir o checklist: a reabertura vai ao servidor depois de qualquer chamada que o Esc tenha disparado, e a leitura não corre antes dela. A marca de sessão do "lembrar depois" não está no banco: quem a pega é a recarga (última linha)
- **Foco**: `keys($seletor, 'Escape')` foca o elemento do seletor e tecla (`Locator::press` do Playwright). Linha 1: `keys('button[aria-label="'.$titulo.'"]', 'Escape')`; linha 2: `keys('[role="dialog"][aria-label="'.$titulo.'"] [wire\:click*="startTour"][wire\:click*="usuarios"]', 'Escape')`. A abertura nas duas linhas é `keys('button[aria-label="'.$titulo.'"]', 'Enter')` — teclado, como no QA-16
- Linha 2: o foco está num botão de iniciar tour; o popover ausente prova que o Esc não acionou o botão focado

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | semear, ligar, entrar e aquecer | `$this->seed([...]); ligarTutorialGuiado(); $this->actingAs($administradora); $this->get('/admin');` | — |
| 2 | entrar | `$pagina = visit('/admin')->on()->desktop()->inLightMode(); $pagina->assertPathIs('/admin')` | dashboard |
| 3 | abrir pelo teclado | `->keys('button[aria-label="'.$titulo.'"]', 'Enter')->assertVisible($painel)` | painel do checklist |
| 4 | contar antes | `$antes = linhasDoTutorialDa($administradora)` (progresso de passos, de jornada e preferências) | — |
| 5 | Esc no foco da linha | `->keys($seletorDoFoco, 'Escape')` | — |
| 6 | fechado | `->assertMissing($painel)->assertVisible('button[aria-label="'.$titulo.'"]')->assertMissing('[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]')` | painel fechado |
| 7 | reabrir | `->click('button[aria-label="'.$titulo.'"]')->assertVisible('[role="dialog"][aria-label="'.$titulo.'"] [wire\:click*="startTour"][wire\:click*="usuarios"]')` | painel com o passo de Usuários |
| 8 | contar depois | `expect(linhasDoTutorialDa($administradora))->toBe($antes)` | — |
| 9 | recarregar | `$pagina->refresh()->assertPathIs('/admin')->assertVisible('button[aria-label="'.$titulo.'"]')` | botão do checklist |

`linhasDoTutorialDa()` é helper novo do arquivo (contagem por tabela, filtrada pela pessoa). `refresh()` confirmado em `vendor/pestphp/pest-plugin-browser/src/Api/Concerns/InteractsWithToolbar.php:refresh:18`; `keys()` em `…/Api/Concerns/InteractsWithElements.php:keys:48`; `assertMissing()` em `…/Api/Concerns/MakesElementAssertions.php:assertMissing:532`. **Tem de falhar antes da correção** (o estado atual é o MB12), nas duas linhas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB12 | Esc ignorado — nenhum tratador de `escape` no checklist: o estado medido no QA-16 | CT-B04 (linhas 1 e 2) | painel não visível depois do Esc; o mutante o mantém aberto |
| MB13 | tratador `keydown.escape` no elemento do painel, sem `.window`/documento: só fecha com o foco dentro dele | CT-B04 (linha 1) | painel não visível com o foco no botão; o mutante não ouve o Esc que nasce fora do painel |
| MB14 | tratador preso ao botão do checklist: só fecha com o foco no botão | CT-B04 (linha 2) | painel não visível com o foco dentro; o mutante não ouve o Esc do controle interno |
| MB15 | Esc ligado a um verbo do pacote que também grava: dispensar a jornada, "lembrar depois" ou "não mostrar mais" (fechar = sair do onboarding) | CT-B04 (linhas 1 e 2) | mesmas linhas de progresso de jornada e de preferências; botão visível depois de recarregar; o mutante grava a dispensa/preferência ou some na recarga |
| MB16 | Esc tratado como "pular" o passo em foco (`skipStep`), ou acionando o botão focado | CT-B04 (linha 2) | mesmas linhas de progresso de passos e nenhum popover de tour; o mutante grava o passo pulado ou abre o tour |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Checklist flutuante em todas as telas dos três painéis (render hook `BODY_END`) | render hook `BODY_END` do plugin (`vendor/wallacemartinss/filament-onboarding/src/FilamentOnboardingPlugin.php:BODY_END:109`), montando a subclasse `App\Livewire\ChecklistDoOnboarding` registrada sob o nome do pacote | ✅ | CT-B01 (`/admin`) e CT-B02 (`/infra`) verdes; CT-15 nos três painéis; `vendor/bin/pest tests/Browser/OnboardingGuiadoTest.php` 2026-10-21 |
| 2 | Tour Alpine do pacote, iniciado pelo checklist e pela página | tour do pacote (`x-data="onboardingTour"`) iniciado pelo `startTour` do checklist; pela página "Primeiros passos" só no CT-16 de componente | ⚠️ | checklist: CT-B01/CT-B02 (popover visível, console sem erro); início pela página não tem CT-B — a costura `browser` cobre só o checklist (teto do perfil, `05` linha 6) |
| 4 | Contraste AA no checklist e na página, nos dois temas (P-27) | `kit.css` define `--fio-theme-accent` e `--fio-theme-text-muted` e o fundo do `.fio-button--primary` no tom 700 | ✅ | CT-B03 4/4 em 4 rodadas seguidas, 2026-10-22; antes da correção, 4/4 vermelhos (3,17:1) e `.fio-hero-description` intermitente no `/admin` claro |
| 5 | Esc fecha o checklist aberto (P-28) | `x-on:keydown.escape.window` no painel da view publicada do launcher | ✅ | CT-B04 2/2, 2026-10-22; antes, 2/2 vermelhos (painel continuava visível) |
| 3 | Paradas por `route` + alvo nomeado pelo pacote ou popover centralizado | paradas semeadas com `route` do painel da jornada; primeira parada aberta no navegador | ✅ | CT-17 (rotas resolvem no painel da jornada) e CT-B01/CT-B02 (título pt_BR da primeira parada visível) |
