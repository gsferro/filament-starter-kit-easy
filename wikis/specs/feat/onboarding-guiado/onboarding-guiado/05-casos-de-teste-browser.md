# Casos de Teste de Browser — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Runtime: `pest-plugin-browser` v5.0.1 (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linhas "G13 Tour no navegador" (regra R9 — `RQ-28`, `RQ-18`) e "G14 Acessibilidade do tutorial no navegador" (regras R36 — `P-27` e R37 — `P-28`) de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/Browser/OnboardingGuiadoTest.php` (série; `composer test:browser` embute `npm run build` e `view:cache`). O `phpunit.xml` declara a suíte `Browser`.
> Derivado do **requisito**, não do plano. Teto do perfil padrão: 1 CT-B (happy path), estourado pelo CT-B02 (`/infra`, ADV-26 da revisão adversarial) e pelos CT-B03 e CT-B04 (quality gate, ciclo 3: QA-15 → P-27, QA-16 → P-28), cada um com a justificativa no próprio cenário — o gate de falsificabilidade vence o teto (MB7–MB16 não têm outro matador). Os candidatos cortados estão na `## Tabela de cogitado e cortado` do `04`.
> CT-B: 4 (CT-B01 `/admin`, CT-B02 `/infra`, CT-B03 `/admin` e `/infra` × claro e escuro × sem e com passo concluído, CT-B04 `/admin`, uma e duas teclas Esc) · Mutantes: MB1–MB21 *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*
> Revisão adversarial dos CT-B03 e CT-B04: **não feita** — o teto de 2 rodadas foi atingido (`04`, `## Fechamento da 2ª rodada adversarial`); registrado no `## Fechamento do quality gate, ciclo 3` do `04`. As linhas novas do ciclo 4 (CT-B03 linhas 5–8, CT-B04 linha 3, MB17–MB21) também ficam sem re-revisão independente pelo mesmo teto — registrado no `## Fechamento do quality gate, ciclo 4` do `04`.

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

*(nova em 2026-10-22: QA-15 do quality gate, ciclo 3 → P-27)* *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* · regra **R36** do `04` · grupo G14

**Por que browser e não Livewire**: contraste é cor computada sobre fundo computado, depois do CSS do pacote, do tema do painel e do tema claro/escuro aplicados — só o navegador calcula. O componente prova que o botão existe; não prova que ele é legível (fato 10 do plugin: `assertSee` passa com texto ilegível).

**Estouro do teto declarado**: o perfil padrão (área C) tem teto de 1 CT-B, já estourado pelo CT-B02. Os mutantes MB7–MB11 não têm matador em outra camada; o gate vence o teto (SKILL.md, passo 6, item 5).

**Escolha dos painéis — `/admin` e `/infra`, sem o `/app`**: o defeito medido é igual nos três (mesma regra CSS do pacote, a mesma cor `#e17100`, QA-15), então um painel prova o MB7. O que distingue os painéis é **onde a correção mora**: o `/infra` tem tema e assets próprios (CT-B02, MB6), e a correção plausível no tema do kit pode não chegar a ele (MB9). O `/admin` é o painel do tema do kit; o `/infra` é o painel com tema próprio — e o de mais elementos atingidos (17/15 na página). O `/app` repete a linha do `/admin` se carrega o mesmo tema; se não carrega, ele entra (Q?2).

**Por que página e checklist num só `Quando`**: o checklist é renderizado em toda tela do painel (render hook `BODY_END`, `## Roteiro de Validação`, linha 1), inclusive na própria página "Primeiros passos". Abrir o checklist sobre a página põe as duas superfícies de P-27 sob o mesmo `axe.run()`, e o relatório do plugin lista o seletor de cada violação — a falha diz qual superfície.

**Partição do estado do rótulo de status** *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*: o rótulo de status de um passo na página tem três estados — a seguir, a fazer e concluído —, e cada um é pintado por uma regra de cor própria do pacote. As linhas 1–4 (persona sem passo concluído) só põem na tela os dois primeiros; o rótulo "Concluída" usa o token de sucesso do pacote, que a correção do QA-15 não tocou, e o QA-19 mediu 3,24:1 no tema claro. As linhas 5–8 fixam um passo **concluído** na jornada do painel, pela ação real (como no CT-72 do `04`): no `/admin`, "Configurações da aplicação" por visita; no `/infra`, "Perfil e 2FA" por condição (segundo fator confirmado). Toda partição do estado exibido é classe obrigatória (SKILL.md, passo 3, "Estado exibido"); as linhas 1–4 continuam (Q?1 do ciclo 4).

```gherkin
# language: pt
  Esquema do Cenário: [CT-B03] a página "Primeiros passos" com o checklist aberto não tem problema sério de acessibilidade, no <painel>, no tema <tema>, com a persona <estado>
    Dado as jornadas do kit semeadas, o tutorial ligado e <persona> no <painel>, <estado>
    E a página "Primeiros passos" do <painel> aberta no tema <tema>, com o documento confirmando o tema <tema>
    E o checklist flutuante aberto sobre a página, com o painel do checklist visível, o título da jornada do <painel> no conteúdo da página e <controle do estado>
    Quando a tela é avaliada pelo axe no nível serious
    Então nenhuma violação de impacto serious ou critical é relatada

    Exemplos:
      | painel | persona          | tema   | estado                                                                  | controle do estado                                                              | # partição                                                                    |
      | /admin | a administradora | claro  | sem nenhum passo concluído                                              | nenhum rótulo "Concluída" na página                                             | painel do tema do kit; rótulos a seguir e a fazer                             |
      | /admin | a administradora | escuro | sem nenhum passo concluído                                              | nenhum rótulo "Concluída" na página                                             | mesmo painel, outro tema: a correção não pode ser de um só                    |
      | /infra | a Íris           | claro  | sem nenhum passo concluído                                              | nenhum rótulo "Concluída" na página                                             | painel com tema e assets próprios (CT-B02)                                    |
      | /infra | a Íris           | escuro | sem nenhum passo concluído                                              | nenhum rótulo "Concluída" na página                                             | idem, tema escuro                                                             |
      | /admin | a administradora | claro  | com o passo "Configurações da aplicação" concluído por visita, e só ele | o rótulo "Concluída" desse passo visível na página e não coberto pelo checklist | rótulo concluído no tema do kit — o caso medido no QA-19                      |
      | /admin | a administradora | escuro | com o passo "Configurações da aplicação" concluído por visita, e só ele | o rótulo "Concluída" desse passo visível na página e não coberto pelo checklist | rótulo concluído, tema escuro: a correção do claro não pode vazar para ele    |
      | /infra | a Íris           | claro  | com o passo "Perfil e 2FA" concluído por condição, e só ele             | o rótulo "Concluída" desse passo visível na página e não coberto pelo checklist | rótulo concluído no painel de tema próprio — medido no QA-19 (`/infra` claro) |
      | /infra | a Íris           | escuro | com o passo "Perfil e 2FA" concluído por condição, e só ele             | o rótulo "Concluída" desse passo visível na página e não coberto pelo checklist | idem, tema escuro                                                             |
```

- **Controle 1 — o checklist está aberto quando o axe roda**: `assertVisible` do painel do checklist **imediatamente antes** do `assertNoAccessibilityIssues()`. O axe pula elemento oculto na regra `color-contrast`; com o checklist fechado, as 3 violações dele somem (QA-15: "os dashboards com o checklist fechado dão 0") e a linha passa no vácuo
- **Controle 2 — a página tem o conteúdo atingido**: o título da jornada do painel (lido do banco, como no CT-B01) está em `.fi-main`; a persona sem passo concluído garante passo pendente na lista (o status de próximo passo e os botões de iniciar tour existem)
- **Controle 4 — o estado da linha é o da tela** *(novo em 2026-10-22: QA-19, ciclo 4)*: nas linhas 1–4, `assertNotPresent('.fi-main .fio-tile--done')` — a persona não tem passo concluído e a partição é a que a linha diz. Nas linhas 5–8, **depois** de abrir o checklist e **antes** do axe: (a) `assertVisible('.fi-main .fio-tile--done .fio-tile-status')` — o rótulo de concluído está na tela; e (b) o rótulo **não está coberto** pelo painel do checklist: `assertScript` que rola o rótulo ao centro da tela e confere que `document.elementFromPoint` no centro dele devolve o próprio rótulo (ou um filho). Por que (b): o checklist é um painel flutuante sobre a página, e o axe classifica o texto coberto por outro elemento como `incomplete` na regra `color-contrast`, não como violação — com o rótulo atrás do painel, a linha passaria no vácuo com o MB17 vivo. `assertVisible` sozinho não vê sobreposição (Playwright `isVisible` olha só caixa e `visibility`). Seletor por classe do pacote (`.fio-tile--done .fio-tile-status`, o alvo que o axe acusou no QA-19): sem `data-testid`, mesma dívida da tabela de seletores. O texto é conferido pela classe e não por `assertSee('Concluída')` porque o pacote o mostra em caixa alta por CSS ("CONCLUÍDA", QA-19), e o texto lido pode vir transformado
- **A conclusão do passo vem pela ação real**, nunca por `insert` na tabela de progresso (`## Setup Global` do `04`): no `/admin`, o GET real de `/admin/configuracoes-da-aplicacao` com o tutorial ligado, antes do `visit()` (linha 1 do CT-72); no `/infra`, a sessão do Breezy confirmada para a Íris (`breezySession`, linha 7 do CT-72). "E só ele" faz da linha uma partição com os três estados na tela ao mesmo tempo (concluído, a seguir, a fazer), mas não é oráculo: o oráculo continua sendo o axe
- **Controle 3 — o tema é o da linha**: `assertScript("document.documentElement.classList.contains('dark')", true|false)`. `inDarkMode()` só emula a preferência do navegador; se o kit fixar o tema por outra via, a linha "escuro" mediria o claro de novo (pest-plugin-browser, §Tema)
- `assertNoAccessibilityIssues()` com o nível default `1` (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesConsoleAssertions.php:assertNoAccessibilityIssues:95`): reprova `critical` e `serious`; `color-contrast` é `serious`. O oráculo é o documento inteiro — ver o pré-requisito da linha de base e Q?1
- Não é oráculo de cor específica (`getComputedStyle`): P-27 diz "contraste suficiente (WCAG AA)", não qual cor; qualquer correção que atinja 4,5:1 (texto de 12 px) serve

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | semear, ligar, entrar e aquecer | `$this->seed([...]); ligarTutorialGuiado(); $this->actingAs($persona); $this->get('/{painel}/onboarding');` | — |
| 1a | linhas 5–8: concluir o passo pela ação real | `/admin`: `$this->get('/admin/configuracoes-da-aplicacao')`; `/infra`: sessão do Breezy confirmada para a Íris (`breezySession`); depois `fronteiraDeRequest()` | — |
| 2 | abrir a página no tema da linha | `$pagina = visit('/{painel}/onboarding')->on()->desktop()->inLightMode();` (ou `->inDarkMode()`) | página "Primeiros passos" |
| 3 | conferir o caminho | `->assertPathIs('/{painel}/onboarding')` | — |
| 4 | controle 3 (tema) | `->assertScript("document.documentElement.classList.contains('dark')", $escuro)` | — |
| 5 | controle 2 (conteúdo) | `->assertSeeIn('.fi-main', $tituloDaJornada)` | lista de passos |
| 5a | controle 4, linhas 1–4 | `->assertNotPresent('.fi-main .fio-tile--done')` | — |
| 6 | abrir o checklist | `->click('button[aria-label="'.$tituloDaJornada.'"]')` | painel do checklist |
| 7 | controle 1 (aberto) | `->assertVisible('[role="dialog"][aria-label="'.$tituloDaJornada.'"]')` | — |
| 7a | controle 4, linhas 5–8 (visível e não coberto) | `->assertVisible('.fi-main .fio-tile--done .fio-tile-status')->assertScript("(() => { const r = document.querySelector('.fi-main .fio-tile--done .fio-tile-status'); r.scrollIntoView({block: 'center'}); const c = r.getBoundingClientRect(); return r.contains(document.elementFromPoint(c.left + c.width / 2, c.top + c.height / 2)); })()", true)` | rótulo "CONCLUÍDA" fora do painel |
| 8 | oráculo | `->assertNoAccessibilityIssues()` | — |

`$tituloDaJornada` sai de `titulosDoTourSemeado()` (já existe no arquivo). Se a página também tiver um elemento com `aria-label` igual ao título, o seletor do passo 6 fica ambíguo: confirmar no primeiro vermelho e restringir ao botão fora do `.fi-main`. O dataset ganha a coluna do estado (`bool $comPassoConcluido`) e as linhas 5–8. Nada de `wait()`. `assertScript` avalia uma vez, sem reexecutar (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/MakesElementAssertions.php:assertScript:156`, `page->evaluate`); a expressão contém `>`, então o plugin a passa sem embrulhar. Se o passo 7a falhar porque o painel do checklist cobre o rótulo mesmo depois de rolar, o vermelho é do controle, não do contraste: escolher outro passo concluído da tabela do CT-72, nunca tirar o controle. **Tem de falhar antes da correção**: as linhas 1–4 já passaram (correção do QA-15); as linhas 5 e 7 (claro, com passo concluído) têm de ficar vermelhas com `color-contrast` em `.fio-tile--done .fio-tile-status` (o estado atual é o MB17); as linhas 6 e 8 podem ficar verdes já (QA-19: "no tema escuro dá 0"), e são as guardas do MB19.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB7 | **central** — os botões primários do pacote ficam com `var(--primary-600)` e texto branco (3,2:1 com `#e17100`), sem sobrescrita no tema do kit: o estado medido no QA-15 | CT-B03 (as quatro linhas) | nenhuma violação serious; o mutante relata `color-contrast` em "Iniciar tour", no status de próximo passo e no botão da jornada |
| MB8 | sobrescrita escrita para um tema só (ex.: só sob `.dark`, ou só fora dele, ou com par `dark:` que volta à cor do pacote) | CT-B03 (linhas do tema esquecido) | nenhuma violação serious no outro tema; o controle 3 garante que o tema da linha é o efetivo |
| MB9 | sobrescrita só no CSS do tema do kit carregado pelo `/admin`; o `/infra`, com tema e assets próprios, continua com a regra do pacote | CT-B03 (linhas 3 e 4) | nenhuma violação serious no `/infra`; o mutante relata `color-contrast` ali |
| MB10 | sobrescrita só do botão primário (`.fio-button--primary`); o rótulo de status do próximo passo continua com a mesma combinação de 3,2:1 | CT-B03 (linhas 1–4; persona sem passo concluído — e linhas 5–8, que também têm passo a seguir) | nenhuma violação serious; o mutante relata o status |
| MB11 | sobrescrita escopada ao conteúdo da página (`.fi-main …`); o checklist, renderizado fora dele (`BODY_END`), fica com a cor do pacote | CT-B03 (as oito linhas; controle 1) | nenhuma violação serious com o checklist visível; o mutante relata as 3 do checklist |
| MB17 | **central do QA-19** — o rótulo de status concluído fica com o token de sucesso do pacote, o verde 600 (`#00a63e`) sobre branco (3,24:1, 10 px em negrito); a sobrescrita do tema cobre destaque, texto secundário e botão primário, mas não o sucesso: o estado medido no QA-19 *(novo em 2026-10-22: QA-19, ciclo 4)* | CT-B03 (linhas 5 e 7; controle 4) | nenhuma violação serious com o rótulo "Concluída" visível e não coberto; o mutante relata `color-contrast` em `.fio-tile--done .fio-tile-status` |
| MB18 | verde do concluído corrigido só no CSS do tema do kit carregado pelo `/admin`; o `/infra`, com tema e assets próprios, continua com o verde 600 do pacote *(novo em 2026-10-22: QA-19, ciclo 4)* | CT-B03 (linha 7) | nenhuma violação serious no `/infra` claro com passo concluído; o mutante relata `color-contrast` no rótulo |
| MB19 | verde do concluído escurecido para o tema claro sem escopo de tema: a mesma cor vale no escuro, e o rótulo fica verde-escuro sobre o fundo escuro *(novo em 2026-10-22: QA-19, ciclo 4)* | CT-B03 (linhas 6 e 8; controles 3 e 4) | nenhuma violação serious no escuro com o rótulo visível; o mutante relata `color-contrast` no rótulo |

**Estouro do teto de mutantes da R36 declarado** *(2026-10-22: QA-19, ciclo 4)*: MB7–MB11 e MB17–MB19 são 8, acima do teto de 5 do perfil padrão. MB17–MB19 vêm de achado **medido** do quality gate (destino 3), da mesma natureza que o mutante da revisão adversarial (SKILL.md, passo 6, item 1: achado medido, não enchimento); desdobrar a R36 em "botões" e "rótulos de status" renumeraria a rastreabilidade de quatro ciclos por motivo cosmético.

---

## CT-B04: a tecla Esc fecha o checklist aberto, sem concluir nem dispensar nada

*(nova em 2026-10-22: QA-16 do quality gate, ciclo 3 → P-28)* *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)* · regra **R37** do `04` · grupo G14

**Por que browser e não Livewire**: a tecla é um evento de teclado tratado no navegador (Alpine/JS) e depende de onde está o foco; `Livewire::test` não tem foco nem tecla. O fechamento é visibilidade no DOM.

**Estouro do teto declarado**: o mesmo do CT-B03 — MB12–MB16 não têm matador em outra camada.

**Um painel só (`/admin`)**: o checklist é o mesmo componente, montado pelo mesmo render hook nos três painéis (`## Roteiro de Validação`, linha 1), e o QA-16 mediu o mesmo comportamento nas seis combinações (3 painéis × 2 temas). Nenhum mutante plausível do tratamento do Esc depende do painel; o que muda o resultado é **o foco** — daí a partição.

**Partição do foco**: (1) no botão do checklist que abriu o painel — o caso medido no QA-16 (Enter abre e o foco fica no botão, que está fora do `role="dialog"`); (2) dentro do painel, num controle do próprio checklist. Um tratador preso ao painel só ouve o Esc da linha 2; um preso ao botão só o da linha 1.

**Partição da repetição** *(alterado em 2026-10-22: QA-19/QA-20 do quality gate, ciclo 4)*: com **uma** tecla, fechar e inverter o estado do painel (toggle) dão o mesmo resultado — o QA-20 mediu que o tratador atual inverte, e as linhas 1 e 2 ficam verdes com ele. Só a **segunda** tecla, apertada antes de a resposta da primeira voltar, separa os dois: fechar é idempotente (o painel continua fechado), inverter reabre. A linha 3 é essa repetição, com o foco no botão (o caso medido no QA-20: 3 de 3 reabertos a 20, 150 e 400 ms). O número de teclas é **par** de propósito: com três, o toggle volta a terminar fechado e passaria.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B04] a tecla Esc fecha o checklist aberto, sem concluir nem dispensar nada, com o foco <foco> e <teclas>
    Dado as jornadas do kit semeadas, o tutorial ligado e a administradora no /admin, sem passo concluído nem jornada dispensada
    E o checklist aberto pela tecla Enter no botão do checklist, com o painel do checklist visível e o foco <foco>
    Quando ela tecla <teclas>
    Então, depois de a página assentar, o painel do checklist não está visível, o botão do checklist está visível e nenhum popover de tour está aberto
    E, reaberto pelo botão, o checklist mostra a jornada do /admin com o botão de iniciar o tour do passo de Usuários
    E o progresso de passos, o progresso da jornada e as preferências do tutorial dela têm as mesmas linhas de antes do Esc
    E, recarregada a página, o botão do checklist continua visível

    Exemplos:
      | foco                                                              | teclas                                                         | # partição                                                                 |
      | no botão do checklist que abriu o painel                          | Esc uma vez                                                    | o caso medido no QA-16; o botão fica fora do painel                        |
      | dentro do painel, no botão de iniciar o tour do passo de Usuários | Esc uma vez                                                    | foco dentro do `role="dialog"`                                             |
      | no botão do checklist que abriu o painel                          | Esc duas vezes seguidas, sem esperar a resposta entre elas     | repetição (QA-20): fechar é idempotente, inverter reabre na segunda tecla |
```

Nota do executor (2026-10-22, decidida pela sessão — erro de teste, classe a): na linha 3, as duas teclas saem por `script()` em `window`, a segunda **5 ms** depois da primeira, e o script só devolve depois de 1,5 s sem chamada nova a `/livewire` (o `networkidle` não assenta nada numa página já carregada); no mesmo tick o Livewire junta os dois `toggle` e o painel termina fechado, e com 20 ms ou mais o resultado varia com a latência — e não por `keys(..., ['Escape', 'Escape'])` — cada `press` do Playwright tem round-trip próprio, a resposta do primeiro `toggle` chega antes do segundo e o listener já saiu com o painel: a corrida do QA-20 não aparece e o MB20 sobrevive. *(alterado em 2026-10-22)*

- **"Visível antes"** é o `assertVisible` do painel no `Dado`, logo antes da tecla. **"Depois de a página assentar"** *(alterado em 2026-10-22: QA-20, ciclo 4)* é `waitForEvent('networkidle')` (`vendor/pestphp/pest-plugin-browser/src/Api/Concerns/HasWaitCapabilities.php:waitForEvent:17`, `waitForLoadState` do Playwright) **antes** do `assertMissing`: todas as chamadas Livewire que as teclas dispararam voltaram e foram aplicadas. Não é `wait($segundos)`: espera um estado, não um tempo. Correção de uma afirmação anterior deste arquivo: o `assertMissing` **não** reexecuta até o teto do `timeout()` — ele lê `isVisible()` uma vez (`…/Api/Concerns/MakesElementAssertions.php:assertMissing:532`; `vendor/pestphp/pest-plugin-browser/src/Playwright/Locator.php:isVisible:41`). Sem o assentamento, a linha 3 tem dois falsos possíveis: verde com o MB20 vivo, se a leitura cair entre a resposta da primeira tecla (fecha) e a da segunda (reabre); vermelho com uma correção certa feita no servidor, se a leitura vier antes de qualquer resposta. Nas linhas 1 e 2 o assentamento só remove a corrida, sem mudar o que matam
- **"Sem esperar a resposta entre elas"** é `keys($seletor, ['Escape', 'Escape'])`: o plugin faz um `Locator::press` por tecla, em sequência, sem esperar rede entre elas (`…/Api/Concerns/InteractsWithElements.php:keys:48`). O intervalo resultante é de milissegundos, dentro da faixa em que o QA-20 mediu a reabertura (20–400 ms)
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
| 5 | Esc no foco da linha | `->keys($seletorDoFoco, ['Escape'])` nas linhas 1 e 2; na linha 3, `->script(...)` dispara dois Escape em `window` com 5 ms entre eles e espera 1,5 s sem chamada nova a `/livewire` (ver a nota do executor) | — |
| 5a | assentar | `->waitForEvent('networkidle')` | — |
| 6 | fechado | `->assertMissing($painel)->assertVisible('button[aria-label="'.$titulo.'"]')->assertMissing('[x-data^="onboardingTour"] [role="dialog"][aria-modal="true"]')` | painel fechado |
| 7 | reabrir | `->click('button[aria-label="'.$titulo.'"]')->assertVisible('[role="dialog"][aria-label="'.$titulo.'"] [wire\:click*="startTour"][wire\:click*="usuarios"]')` | painel com o passo de Usuários |
| 8 | contar depois | `expect(linhasDoTutorialDa($administradora))->toBe($antes)` | — |
| 9 | recarregar | `$pagina->refresh()->assertPathIs('/admin')->assertVisible('button[aria-label="'.$titulo.'"]')` | botão do checklist |

`linhasDoTutorialDa()` é helper novo do arquivo (contagem por tabela, filtrada pela pessoa). `refresh()` confirmado em `vendor/pestphp/pest-plugin-browser/src/Api/Concerns/InteractsWithToolbar.php:refresh:18`; `keys()` em `…/Api/Concerns/InteractsWithElements.php:keys:48`; `assertMissing()` em `…/Api/Concerns/MakesElementAssertions.php:assertMissing:532`. O dataset ganha a coluna das teclas (`array $teclas`) e a linha 3. **Tem de falhar antes da correção**: na derivação do ciclo 3, as linhas 1 e 2 (o estado de então era o MB12); no ciclo 4, a linha 3 tem de ficar vermelha com o tratador atual (o painel visível depois de assentar — o estado atual é o MB20), e as linhas 1 e 2 seguem verdes.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| MB12 | Esc ignorado — nenhum tratador de `escape` no checklist: o estado medido no QA-16 | CT-B04 (linhas 1 e 2) | painel não visível depois do Esc; o mutante o mantém aberto |
| MB13 | tratador `keydown.escape` no elemento do painel, sem `.window`/documento: só fecha com o foco dentro dele | CT-B04 (linha 1) | painel não visível com o foco no botão; o mutante não ouve o Esc que nasce fora do painel |
| MB14 | tratador preso ao botão do checklist: só fecha com o foco no botão | CT-B04 (linha 2) | painel não visível com o foco dentro; o mutante não ouve o Esc do controle interno |
| MB15 | Esc ligado a um verbo do pacote que também grava: dispensar a jornada, "lembrar depois" ou "não mostrar mais" (fechar = sair do onboarding) | CT-B04 (linhas 1 e 2) | mesmas linhas de progresso de jornada e de preferências; botão visível depois de recarregar; o mutante grava a dispensa/preferência ou some na recarga |
| MB16 | Esc tratado como "pular" o passo em foco (`skipStep`), ou acionando o botão focado | CT-B04 (linha 2) | mesmas linhas de progresso de passos e nenhum popover de tour; o mutante grava o passo pulado ou abre o tour |
| MB20 | **central do QA-20** — o tratador do Esc **inverte** o estado do painel (toggle) em vez de fechar: com uma tecla fecha, com a segunda tecla, antes de a resposta da primeira voltar, reabre *(novo em 2026-10-22: QA-20, ciclo 4)* | CT-B04 (linha 3) | painel não visível depois de assentar; o mutante termina com o painel aberto (QA-20: 3 de 3 a 20, 150 e 400 ms) |
| MB21 | correção do QA-20 por guarda no cliente: o tratador só inverte "se o painel está aberto", lido de um estado que só muda quando a resposta volta — a segunda tecla ainda vê "aberto" e inverte de novo *(novo em 2026-10-22: QA-20, ciclo 4)* | CT-B04 (linha 3) | painel não visível depois de assentar; o mutante reabre como o MB20 |

**Estouro do teto de mutantes da R37 declarado** *(2026-10-22: QA-20, ciclo 4)*: MB12–MB16 e MB20–MB21 são 7, acima do teto de 5 do perfil padrão. MB20 é o achado medido do quality gate e MB21 a correção plausível que ainda o deixa vivo; mesma justificativa do estouro da R36.

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Checklist flutuante em todas as telas dos três painéis (render hook `BODY_END`) | render hook `BODY_END` do plugin (`vendor/wallacemartinss/filament-onboarding/src/FilamentOnboardingPlugin.php:BODY_END:109`), montando a subclasse `App\Livewire\ChecklistDoOnboarding` registrada sob o nome do pacote | ✅ | CT-B01 (`/admin`) e CT-B02 (`/infra`) verdes; CT-15 nos três painéis; `vendor/bin/pest tests/Browser/OnboardingGuiadoTest.php` 2026-10-21 |
| 2 | Tour Alpine do pacote, iniciado pelo checklist e pela página | tour do pacote (`x-data="onboardingTour"`) iniciado pelo `startTour` do checklist; pela página "Primeiros passos" só no CT-16 de componente | ⚠️ | checklist: CT-B01/CT-B02 (popover visível, console sem erro); início pela página não tem CT-B — a costura `browser` cobre só o checklist (teto do perfil, `05` linha 6) |
| 4 | Contraste AA no checklist e na página, nos dois temas (P-27) | `kit.css` define `--fio-theme-accent`, `--fio-theme-text-muted` e `--fio-theme-success` no tom 700 (claro) e o fundo do `.fio-button--primary` no tom 700 | ✅ *(alterado em 2026-10-22: QA-19 corrigido)* | CT-B03 8/8 em 3 rodadas seguidas, 2026-10-22; antes, linhas 5 e 7 vermelhas (`.fio-tile--done .fio-tile-status`, 3,12:1 e 3,24:1) |
| 5 | Esc fecha o checklist aberto (P-28) | `x-on:keydown.escape.window` no painel da view publicada do launcher, gravando `isOpen = false` (idempotente) | ✅ *(alterado em 2026-10-22: QA-20 corrigido)* | CT-B04 3/3 em 3 rodadas seguidas, 2026-10-22; antes, linha 3 (dois Esc a 5 ms) vermelha 3/3 com o `toggle` |
| 3 | Paradas por `route` + alvo nomeado pelo pacote ou popover centralizado | paradas semeadas com `route` do painel da jornada; primeira parada aberta no navegador | ✅ | CT-17 (rotas resolvem no painel da jornada) e CT-B01/CT-B02 (título pt_BR da primeira parada visível) |
