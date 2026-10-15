# Casos de Teste de Browser — fix/pilha-de-botoes-flutuantes: Pilha harmônica dos botões flutuantes

> **Runtime**: `pest-plugin-browser` v5.0.1 (Playwright); o plugin sobe o próprio servidor. Pest v5.1.1 · Filament v5.8.2 · Livewire v4.4.7 · Laravel v13.36.0.
> **Costura**: `browser`, nas linhas "Pilha e reação ao dock" (CT-B01…CT-B08, CT-B11) e "Chat estilizado" (CT-B09, CT-B10) do `04`, confirmadas pela sessão em 2026-10-14. Os Adendos 4 a 8 não têm CT-B: HTTP e componente provam o que afirmam (CT-04 a CT-11 no `04`).
> **Revisão adversarial**: ciclo 1 executado; neste arquivo entraram ADV-05 e ADV-06 (CT-B08 linhas c e d), ADV-18 (CT-B06) e ADV-20 (CT-B03). **Ciclo 2 executado; teto de 2 rodadas atingido; achados incorporados sem nova rodada.** Neste arquivo entraram ADV-28 e ADV-29 (CT-B08 linhas e e f), ADV-30 e ADV-32 (CT-B06) e ADV-35 (válvula do CT-B06 com recontagem).
> **Comando**: `composer test:browser`, ou, depois do build e de `php artisan view:cache`: `vendor/bin/pest tests/Browser/PilhaDeBotoesFlutuantesTest.php tests/Browser/AssistenteChatEstiloTest.php`. Em série.

## Pré-requisitos
- [ ] `npm run build` executado.
- [ ] `php artisan view:cache` e aquecimento por `$this->get('<painel>')` antes do `visit()`.
- [ ] `tests/Browser/Screenshots` no `.gitignore`.
- [ ] `$this->actingAs($usuario)` antes do `visit()`; cada cenário arranja o próprio painel.
- [ ] Uma visita por tema.
- [ ] Sem `wait()`: espera por `assertScript(...)`; `script()` só para medir.
- [ ] Desktop por `->on()->desktop()`; celular por `->on()->iPhone15()` **antes** do load.

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| raiz do dock (também a superfície da barra) | `.qad-root` (`data-open`, `data-mode`, `data-settling`, `--qad-offset`) | sim (vendor) |
| gatilho do dock | `[data-layout] button[x-ref="trigger"]` | sim |
| "Mover o dock" | `.qad-move-toggle` | sim |
| cantos do mapa | `[data-corner="bottom-right"]`, `[data-corner="bottom-left"]`, `[data-corner="top-right"]` | sim |
| itens do leque | `.qad-root[data-open="true"] .qad-item` | sim |
| "Voltar ao topo" | `[data-voltar-ao-topo]` | sim |
| raiz do chat | `[data-assistente-chat]` | nasce nesta feature |
| botão do chat | `[data-assistente-chat] button[aria-label="Abrir assistente"]` | sim |
| slide-over / título | `[data-assistente-chat] [aria-label="Assistente"]` / `… h2` | sim |
| "Nova conversa" / "Fechar assistente" | `button[wire\:click="novaConversa"]` / `[aria-label="Fechar assistente"]` | sim |

Dívida registrada: o kit não tem `data-testid`.

## Medidas e roteiros comuns

**`medirPilha()`.** Devolve `vw`, `vh`, `offset` e, para `dock`, `chat` e `voltar`: `rect`, `visivel` e `centroX`.
- O `dock` é o gatilho, unido aos `.qad-item` visíveis quando aberto.
- No layout barra, há também `barra` (`rect` da `.qad-root`) e `gatilho`.

Definições: `04` §Setup Global → Medidas. Tolerância: ±1 px.

**Mover o dock pela UI.** Gatilho, depois `.qad-move-toggle`, depois `[data-corner="<canto>"]`. Esperar com `assertScript` até o gatilho estar no canto (folgas = `offset` ±1).

**Soltar o dock fora do canto (Q11, forma corrigida).** A partir do canto inferior direito, a cada passo:
1. `keys('[data-layout] button[x-ref="trigger"]', 'Alt+ArrowUp')`, ou `'Alt+ArrowLeft'` para o eixo x, com a combinação numa string só.
2. `assertScript` até `!document.querySelector('.qad-root').hasAttribute('data-settling')`.

Repetir até a folga da linha. `drag()` só se funcionar. `Alpine.$data(.qad-root).commit(...)` é o último recurso, também quando o passo do teclado não cair na faixa da linha; o teste registra o uso.

**Rolar.** `document.body.style.minHeight='4000px'; scrollTo(0, 800)`, depois `assertScript` até o voltar ficar visível.

---

## CT-B01: pilha com o dock no canto inferior direito e a página rolada

**Por que browser e não Livewire.** Posição, centro, interseção e espaço só existem com o JS do dock e o da pilha executados.

```gherkin
# language: pt
  Esquema do Cenário: [CT-B01] com o dock no canto inferior direito e a página rolada, a pilha fica acima dele, alinhada, a 16 px e sem sobreposição
    Dado <persona> autenticado em <painel>, com o dock liberado, num viewport <viewport>
    E o dock levado ao canto inferior direito pelo "Mover o dock"
    Quando a página é rolada 800 px
    Então o dock tem folga direita e inferior iguais ao --qad-offset da raiz
    E de baixo para cima a ordem é <ordem>, sem nenhum par de retângulos se intersectando
    E o centro x de cada botão da pilha é o do dock
    E o espaço entre cada par de vizinhos é 16 px

    Exemplos:
      | persona | painel | viewport | ordem              | # partição             |
      | bruno   | /admin | 1280×800 | dock, voltar       | sem chat               |
      | iris    | /infra | 1280×800 | dock, voltar       | terceiro painel (P-02) |
      | ana     | /app   | 1280×800 | dock, chat, voltar | com chat               |
      | ana     | /app   | celular  | dock, chat, voltar | dock compacto          |
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | `seed`, `usuarioDoKit`, `concederDock(papel)`, `ligarDock(['admin','app','infra'])`, `actingAs`, `$this->get(painel)->assertOk()` | — |
| 2 | abrir | `$pagina = visit(painel)` (no celular, `->on()->iPhone15()`) | painel carregado |
| 3 | mover | roteiro comum, `bottom-right` | dock no canto |
| 4 | rolar | roteiro comum | voltar aparece |
| 5 | medir | `$m = json_decode($pagina->script(medirPilha), true, flags: JSON_THROW_ON_ERROR)` | — |
| 6 | afirmar | folgas do dock = `offset` ±1; ordem; nenhuma interseção; centros iguais ±1; cada espaço = 16 ±1 | — |
| 7 | apoio | `->assertNoJavaScriptErrors()` | — |

#### Mutantes previstos

| # | Resumo (tabela completa no `04`) | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M01 | voltar no recuo de hoje | CT-B01 `/admin` | sem interseção voltar × dock |
| M02 | lê `data-position` do servidor | CT-B01 todas | `chat.bottom ≤ dock.top` |
| M03 | ordem invertida | CT-B01 `/app` | `voltar.bottom ≤ chat.top` |
| M04 | chat fora da pilha | CT-B01 `/app` | sem interseção chat × dock |
| M05 | lista fixa de painéis | CT-B01 `/infra` | sem interseção voltar × dock |
| M06 | sobe o dock | CT-B01 `/app` | folga inferior do dock = `offset` |
| M07 | afasta o dock | CT-B01 `/app`, `/admin` | folga direita do dock = `offset` |
| M08 | alinha pela borda | CT-B01 `/app` | centros iguais ±1 |
| M09 | espaços desiguais | CT-B01 `/app` | espaços iguais ±1 |
| M10 | gatilho de desktop no celular | CT-B01 celular | espaços = 16, sem interseção |
| M11 | centraliza só o chat | CT-B01 `/admin`, `/app` | centro do voltar = centro do dock |
| M41 | espaço com outro valor | CT-B01 todas | espaço = 16 ±1 |
| M42 | 16 px só no `/app` | CT-B01 `/admin`, `/infra` | `espaço(dock→voltar)` = 16 ±1 |

---

## CT-B02: a pilha já está formada ao abrir a tela com a posição lembrada

**Por que browser.** A posição lembrada vive no `localStorage` e é aplicada pelo JS do dock depois do HTML.

```gherkin
  Cenário: [CT-B02] ao abrir a tela com o dock lembrado no canto inferior direito, o chat já está acima do dock
    Dado a Ana autenticada em /app, com o dock liberado
    E o dock movido ao canto inferior direito numa visita anterior
    Quando a Ana abre /app de novo, sem rolar nem mexer no dock
    Então o chat não intersecta o dock e fica 16 px acima dele
    E o centro x do chat é o do dock
```

**Roteiro executável** (Q10: recarregar a mesma página)

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo | como CT-B01, persona ana | — |
| 2 | 1ª visita e mover | `visit('/app')` + mover para `bottom-right` | dock no canto |
| 3 | reabrir | `->script('location.reload()')` + `assertScript` até `.qad-root[data-placed]` e o gatilho no canto | recarregada |
| 4 | afirmar | sem interseção, `dock.top − chat.bottom` = 16 ±1, centros iguais ±1 | — |

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M12 | reage só à mudança ao vivo | CT-B02 | sem interseção, sem ação depois do load |
| M13 | calcula antes de o dock se posicionar | CT-B02 | idem |

---

## CT-B03: a pilha sobrevive ao round-trip Livewire do chat

**Por que browser.** O morph do Livewire sobre o DOM alterado pelo script só acontece com os dois JS rodando.

```gherkin
  Cenário: [CT-B03] depois de uma nova conversa no chat, fechar o assistente devolve o chat ao seu lugar na pilha
    Dado a Ana em /app com o dock no canto inferior direito e a página rolada
    E o retângulo do chat medido nesse momento
    E o assistente aberto, com "Nova conversa" acionada e o round-trip do componente concluído
    Quando a Ana fecha o assistente
    Então o retângulo do chat é igual ao medido antes, com tolerância de 1 px
    E o chat não intersecta o dock nem o "Voltar ao topo"
```

> *(alterado em 2026-10-14: ADV-20)* Abrir e "Nova conversa" passaram ao `Dado`; o `Quando` é a única ação: fechar.

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | arranjo, canto e rolagem | roteiros comuns, `/app` | pilha formada |
| 2 | medir antes | `$antes = medirPilha` | — |
| 3 | `Dado`: abrir | `->click('[aria-label="Abrir assistente"]')->assertVisible('[aria-label="Assistente"]')` | slide-over aberto |
| 4 | `Dado`: round-trip | `->click('button[wire\\:click="novaConversa"]')` + `assertScript` sobre o fim da requisição | — |
| 5 | `Quando`: fechar | `->click('[aria-label="Fechar assistente"]')` + `assertScript` (chat visível) | — |
| 6 | afirmar | `chat.rect` = `$antes` ±1, sem interseção | — |

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M14 | `style` apagado pelo morph | CT-B03 | `chat.rect` = antes ±1 |

---

## CT-B04: dock nos cantos fora da área, posições sem o dock

```gherkin
  Esquema do Cenário: [CT-B04] com o dock fora do lugar da pilha, chat e "Voltar ao topo" ficam nas posições sem o dock
    Dado <persona> em <painel> com o dock liberado
    E o dock <situação>
    Quando a página é rolada 800 px
    Então o "Voltar ao topo" tem folga direita 30 px e folga inferior <folga_voltar> px
    E o chat <chat>

    Exemplos:
      | persona | painel | situação                                                       | folga_voltar | chat                                  | # partição          |
      | ana     | /app   | levado ao canto inferior esquerdo                              | 96           | tem folga direita 24 e inferior 24 px | canto inferior esq. |
      | ana     | /app   | levado ao canto superior direito                               | 96           | tem folga direita 24 e inferior 24 px | coluna, no topo     |
      | ana     | /app   | levado ao canto inferior direito e depois ao inferior esquerdo | 96           | tem folga direita 24 e inferior 24 px | volta (2-switch)    |
      | bruno   | /admin | levado ao canto inferior esquerdo                              | 24           | não existe no painel                  | sem chat            |
```

> P-03: o "Voltar ao topo" fica sempre no eixo da pilha, a 30 px da borda; o chat continua a 24 px.

**Roteiro executável.** Arranjo e "Mover o dock" (na linha 3, duas passagens); rolar; medir; conferir as folgas ±1 com `expect`.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M15 | reage a qualquer canto inferior | CT-B04 linha 1 | voltar 96/30, chat 24/24 |
| M16 | reage ao canto superior direito | CT-B04 linha 2 | chat 24/24 |
| M17 | não volta ao sair do canto | CT-B04 linha 3 | chat 24/24 e voltar 96/30 |

---

## CT-B05: sem o dock na página, alturas de hoje e voltar no eixo

```gherkin
  Esquema do Cenário: [CT-B05] sem o dock na página, os botões ficam nas alturas de hoje e o "Voltar ao topo" no eixo da pilha
    Dado <persona> em <painel>, <motivo>
    Quando a página é rolada 800 px
    Então não existe .qad-root na página
    E o "Voltar ao topo" tem folga direita 30 px e folga inferior <folga_voltar> px
    E o chat <chat>
    E o console não tem erro de JavaScript

    Exemplos:
      | persona | painel | motivo                                               | folga_voltar | chat                                                                                              | # partição |
      | ana     | /app   | sem a permissão Usar:Dock                            | 96           | tem folga direita 24 e inferior 24 px, com o mesmo centro x do voltar, e não intersecta o voltar | com chat   |
      | bruno   | /admin | com a permissão, mas /admin fora dos painéis do dock | 24           | não existe no painel                                                                              | sem chat   |
```

> "Não existe `.qad-root`" é o controle positivo do arranjo.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M18 | dock virtual quando ausente | CT-B05 `/app` | chat 24/24 |
| M19 | exceção com a raiz ausente | CT-B05 todas | `assertNoJavaScriptErrors()` (apoio) + folgas |
| M20 | voltar perde o recuo do `/app` | CT-B05 `/app` | voltar 96, sem interseção com o chat |
| M69 | voltar em `right-6` sem o dock | CT-B05 todas | folga direita do voltar = 30 ±1 |

---

## CT-B06: dock aberto, ocultos sobre o lugar da pilha e visíveis no canto esquerdo

**Por que browser.** A abertura é o Alpine do dock mudando `data-open`; a ocultação, quem recebe o clique e para onde vai o foco são estilo computado, empilhamento e foco reais.

```gherkin
  Esquema do Cenário: [CT-B06] ao abrir o dock, chat e "Voltar ao topo" <efeito> conforme o lugar do dock
    Dado a Ana em /app com o dock <lugar> e a página rolada
    E os retângulos do chat e do "Voltar ao topo" medidos com o dock fechado
    Quando a Ana abre o dock
    Então a raiz do dock tem data-open="true"
    E o chat e o "Voltar ao topo" estão <estado>

    Exemplos:
      | lugar                                                                               | efeito    | estado                                                                                                                                                                                                                       | # decisão      |
      | no canto inferior direito                                                           | somem     | ocultos (display none, visibility hidden ou opacity 0); o centro da interseção entre cada .qad-item visível e o antigo retângulo do chat ou do voltar devolve, por elementFromPoint, um descendente desse item; com o foco no último item do leque, Tab não põe o foco no chat nem no voltar | RQ-09          |
      | no canto inferior esquerdo                                                          | continuam | visíveis; o chat com folgas direita e inferior de 24 px; o voltar com folga direita 30 e inferior 96 px                                                                                                                       | Fora de Escopo |
      | solto na borda direita, com folga inferior entre 32 e 72 px (sobre o lugar do chat) | somem     | ocultos; o centro da interseção entre cada .qad-item visível e o antigo retângulo do chat ou do voltar devolve, por elementFromPoint, um descendente desse item; com o foco no último item do leque, Tab não põe o foco no chat nem no voltar | RQ-14          |
```

> *(alterado em 2026-10-14: ADV-18, ADV-32, ADV-35)* Nas linhas 1 e 3, o executor afirma que ao menos um `.qad-item` visível cruza o antigo lugar. Se nenhum cruzar, o executor **reporta**, a parte do `elementFromPoint` não conta como matadora, M86 passa a "sem matador", e a contagem do cabeçalho do `04` é refeita.
> *(alterado em 2026-10-14: ADV-30)* O foco parte do último item do leque em ordem de tabulação. Depois de um `Tab`, `document.activeElement` não é o botão do chat nem o `[data-voltar-ao-topo]`.

**Roteiro executável.**
1. Arranjo; mover (linhas 1 e 2) ou soltar pelo teclado (linha 3); rolar; `$antes = medirPilha`.
2. `->click('[data-layout] button[x-ref="trigger"]')`; `assertScript` até `.qad-root[data-open="true"]` e até o estado esperado.
3. Para cada `.qad-item` visível cuja caixa cruza `$antes.chat.rect` ou `$antes.voltar.rect`: `script()` calcula o retângulo da interseção e chama `elementFromPoint` no centro dele.
4. Linhas 1 e 3: `script()` foca o último `.qad-item` focável; `keys(<esse item>, 'Tab')`; `script()` lê `document.activeElement`.
5. `expect`.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M21 | oculta só o voltar | CT-B06 linha 1 | chat oculto |
| M22 | oculta em qualquer canto | CT-B06 linha 2 | visíveis, nas folgas sem o dock |
| M27 | oculta só no canto exato | CT-B06 linha 3 | chat e voltar ocultos |
| M86 | `opacity: 0` com chat e voltar acima do leque, capturando o clique | CT-B06 linhas 1 e 3 | `elementFromPoint` no centro da interseção devolve descendente do `.qad-item` |
| M90 | ocultos só da vista, ainda focáveis | CT-B06 linhas 1 e 3 | `document.activeElement` depois do Tab não é o chat nem o voltar |

---

## CT-B07: fechar o dock devolve chat e "Voltar ao topo" aos lugares da pilha

```gherkin
  Cenário: [CT-B07] ao fechar o dock aberto no canto inferior direito, chat e "Voltar ao topo" voltam aos lugares da pilha
    Dado a Ana em /app com o dock no canto inferior direito e a página rolada
    E os retângulos do chat e do "Voltar ao topo" medidos com o dock fechado
    E o dock aberto, com os dois ocultos
    Quando a Ana fecha o dock
    Então o chat e o "Voltar ao topo" estão visíveis
    E os retângulos deles são iguais aos medidos antes de abrir, com tolerância de 1 px
```

**Roteiro executável.** Roteiro comum; `$antes = medirPilha`; abrir e esperar os dois ocultos; clicar de novo no gatilho e esperar `data-open="false"` com os dois visíveis; medir; `expect` da igualdade ±1.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M23 | não restaura | CT-B07 | visíveis |
| M24 | restaura fora da pilha | CT-B07 | `rect` = antes ±1 |

---

## CT-B08: dock solto, a pilha reage se ele cruza o lugar dos botões e não reage longe deles nem logo acima

**Por que browser.** A posição livre é estado do Alpine do dock; reação e não-reação são geometria calculada.

```gherkin
  Esquema do Cenário: [CT-B08] com o dock solto <onde>, a pilha <reação>
    Dado <persona> em <painel> com o dock liberado, no layout speed-dial, fechado
    E o dock solto <onde>, com <posição_dock>
    E o retângulo do dock medido logo depois de solto
    Quando a página é rolada 800 px
    Então o retângulo do dock é igual ao medido logo depois de solto
    E <então>

    Exemplos:
      | linha | persona | painel | onde                                                                                    | reação          | posição_dock                                                                                                                                   | então                                                                                                                          | # fronteira                         |
      | a     | ana     | /app   | na borda direita, sobre o lugar do chat                                                 | sobe acima dele | folga inferior entre 32 e 72 px (fora do canto)                                                                                                | o chat fica acima do dock e o voltar acima do chat, com centros no eixo do dock, espaços de 16 px e nenhum par se intersectando | cruza                               |
      | b     | ana     | /app   | na borda direita, a meia altura, longe da pilha                                         | não muda        | folga inferior maior ou igual à metade do viewport                                                                                             | o chat tem folgas direita e inferior de 24 px, o voltar folga direita 30 e inferior 96 px, e nenhum par se intersecta          | não cruza                           |
      | c     | bruno   | /admin | na borda direita, sobre o lugar do "Voltar ao topo"                                     | sobe acima dele | folga inferior entre 32 e 52 px (o voltar sem o dock ocupa de 24 a 68 px do rodapé)                                                            | o voltar fica 16 px acima do dock, com o centro no eixo do dock, e não intersecta o dock                                       | cruza, sem chat (ADV-05)            |
      | d     | ana     | /app   | na borda direita, logo acima do lugar do "Voltar ao topo"                               | não muda        | borda inferior de 1 a 8 px acima do topo do voltar na posição sem o dock (folga inferior = 96 + altura medida do voltar + 1 a 8 px)            | o chat tem folgas direita e inferior de 24 px, o voltar folga direita 30 e inferior 96 px, e nenhum par se intersecta          | fronteira real, não cruza (ADV-06)  |
      | e     | ana     | /app   | na borda direita, cobrindo o topo do lugar do "Voltar ao topo"                          | sobe acima dele | borda inferior de 1 a 8 px abaixo do topo do voltar na posição sem o dock (folga inferior = 96 + altura medida do voltar − 1 a 8 px)           | o chat fica acima do dock e o voltar acima do chat, com espaços de 16 px e nenhum par se intersectando                          | fronteira real, cruza (ADV-28)      |
      | f     | ana     | /app   | na altura do chat, com a borda direita uns 10 px dentro da coluna do chat, sem encostar na borda da tela | sobe acima dele | folga inferior entre 32 e 72 px; folga direita = 24 + largura medida do chat − 10 px (borda direita do dock 10 px à direita da borda esquerda do chat) | o chat fica 16 px acima do dock e o voltar 16 px acima do chat, chat e voltar com o mesmo centro x, e nenhum par se intersecta | eixo x, cruza (ADV-29)              |
```

> **Notas de execução:**
> - **Linhas d e e**: a altura do voltar é medida antes de soltar o dock, com o dock no canto inferior esquerdo e a página rolada. Se o passo do teclado não cair na faixa de 1 a 8 px, o executor usa o `commit()` do componente e registra.
> - **Linha f**: o dock sobe até a altura do chat com `Alt+ArrowUp` e depois vai para a esquerda com `Alt+ArrowLeft` até a folga direita da linha (ou `commit()`, registrado). A largura do chat é medida antes, com o dock fora da área.
> - **Linha f, o que não se afirma**: a qual eixo a pilha se alinha (coluna de sempre ou eixo do dock deslocado). O requisito não determina isso (ver Cobertura parcial no `04`).

**Roteiro executável.** Arranjo do painel da linha; mover para `bottom-right`; soltar pelo teclado, esperando `data-settling` sumir a cada passo, até a posição da linha; `$solto = dock.rect`; rolar; medir; `expect`.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | reage só ao canto exato | CT-B08 linha a | chat acima do dock, sem interseção, espaço 16 |
| M43 | reage a toda a coluna direita | CT-B08 linha b | chat 24/24 e voltar 96/30 |
| M44 | devolve o dock ao canto | CT-B08 linhas a, c, e, f | `dock.rect` = `$solto` ±1 |
| M78 | reação ao dock solto só com chat | CT-B08 linha c | voltar 16 px acima do dock, no eixo |
| M79 | reage com margem, sem cruzar | CT-B08 linha d | chat 24/24 e voltar 96/30 |
| M88 | reage só acima de um limiar de sobreposição | CT-B08 linha e | pilha acima do dock, 16 px, sem interseção |
| M89 | "na coluna" só quando encosta na borda direita | CT-B08 linha f | chat e voltar acima do dock, 16 px, sem interseção |

---

## CT-B11: layout barra no canto inferior direito, visíveis e empilhados acima da barra

**Por que browser.** O modo barra e o `data-open` permanente são estado do Alpine do dock; visibilidade e empilhamento são geometria.

```gherkin
  Cenário: [CT-B11] no layout barra no canto inferior direito, chat e "Voltar ao topo" continuam visíveis, empilhados acima da barra
    Dado a Ana em /app com o dock liberado e a preferência de layout barra
    E o dock levado ao canto inferior direito, com a raiz em data-mode="bar" e data-open="true"
    Quando a página é rolada 800 px
    Então o chat e o "Voltar ao topo" estão visíveis
    E o chat fica 16 px acima do topo da superfície da barra e o voltar 16 px acima do chat
    E nenhum par entre a superfície da barra, o chat e o voltar se intersecta
    E o chat e o voltar têm o mesmo centro x do gatilho da barra, que fica no canto, com a folga direita do chat igual a 24 px
```

**Roteiro executável.**
1. Arranjo com `dock_layout = 'bar'`; `visit('/app')`.
2. `assertScript` até `.qad-root[data-mode="bar"][data-open="true"]`.
3. Mover para `bottom-right` (estado final com `data-open="true"`) e rolar.
4. `medirPilha`: topo da barra, espaço e interseções em `barra` (o `rect` da `.qad-root`, com o padding de 6 px); eixo em `gatilho`.
5. `expect`.

> *(alterado em 2026-10-14: Adendo 3, RQ-18)* O eixo da pilha é o do gatilho da barra, no canto: a mesma coluna do modo speed-dial.
> *(alterado em 2026-10-14: correção do executor dos CT-B)* O retângulo da barra é a superfície `.qad-root`, e não a união do gatilho com os `.qad-item`, que deixava de fora o padding da barra.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M26 | só reconhece speed-dial | CT-B11 | `chat.bottom ≤ barra.top`, sem interseção com a barra |
| M45 | barra tratada como leque aberto | CT-B11 | chat e voltar visíveis |
| M48 | pilha no meio da barra | CT-B11 | centro do chat = centro do gatilho ±1; folga direita 24 |

---

## CT-B09: botão do chat fixo no canto inferior direito, redondo, na cor primária

**Por que browser.** `position`, raio e cor só existem com a folha aplicada.

```gherkin
  Esquema do Cenário: [CT-B09] no tema <tema>, o botão do chat é fixo, redondo, na cor primária e fica no canto inferior direito
    Dado a Ana autenticada em /app, sem o dock na página
    Quando a Ana abre /app no tema <tema>
    Então o botão "Abrir assistente" tem position fixed
    E está inteiro dentro do viewport, com o centro no quadrante inferior direito
    E tem largura igual à altura e raio de borda de ao menos metade da largura
    E a cor de fundo é uma das cores da paleta primária do painel, não transparente

    Exemplos:
      | tema   |
      | claro  |
      | escuro |
```

**Roteiro executável.** Arranjo sem `concederDock`; `visit('/app')->inLightMode()` ou `->inDarkMode()`; `assertScript` até o botão visível; `script()` com `getComputedStyle`, `rect` e sondas `var(--primary-50…950)`; `expect`; `screenshot(filename: "chat-botao-{tema}")`; `assertNoJavaScriptErrors()` como apoio.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | folha não carregada | CT-B09 as duas | `position = fixed` |
| M29 | sem `rounded-full` | CT-B09 | raio ≥ largura/2 − 1 |
| M30 | sem `bottom/right` | CT-B09 | dentro do viewport, quadrante inferior direito |
| M31 | cor só no tema claro | CT-B09 escuro | fundo = sonda primária, alfa > 0 |

---

## CT-B10: slide-over lateral fixo à direita, opaco, completo, no tema do painel, com contraste ≥ 4,5:1

```gherkin
  Esquema do Cenário: [CT-B10] no tema <tema>, o slide-over do chat é um painel lateral fixo à direita, no tema do painel e com o título legível
    Dado a Ana autenticada em /app, sem o dock na página, num viewport de 1280 px de largura
    Quando a Ana abre o assistente no tema <tema>
    Então o painel "Assistente" tem position fixed, top 0, borda direita igual à largura do viewport e borda inferior igual à altura do viewport
    E é mais estreito que o viewport
    E tem cor de fundo não transparente achada no próprio painel, com luminância <luminancia>
    E o título "Assistente", a área da conversa e o campo de mensagem estão visíveis dentro do retângulo do painel
    E o contraste entre a cor do título e o fundo do painel é de pelo menos 4,5:1
    E o elemento no centro do painel e no seu canto superior direito pertence ao painel

    Exemplos:
      | tema   | luminancia           |
      | claro  | maior ou igual a 0,5 |
      | escuro | menor ou igual a 0,2 |
```

> "Segue o tema" usa os limiares do projeto (`DockDeAtalhosTest` CT-B02); o 4,5:1 é literal da RQ-17.

**Roteiro executável.**
1. `visit('/app')->in{Light|Dark}Mode()`.
2. `->click('[aria-label="Abrir assistente"]')`.
3. `assertScript` até `[aria-label="Assistente"]` visível.
4. `script()` mede o `rect` do painel, o fundo (por canvas, a partir da raiz do painel) e a luminância dele, a cor do `h2` e o contraste, os `rect` do título, da conversa e do campo, e `elementFromPoint` em dois pontos.
5. `expect` e `screenshot(filename: "chat-slideover-{tema}")`.

#### Mutantes previstos

| # | Resumo | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M32 | painel sem fundo | CT-B10 as duas | alfa > 0 no próprio painel |
| M33 | sem `inset-y-0` | CT-B10 | `top = 0`, `bottom = vh` ±1 |
| M34 | sem `max-w-md` | CT-B10 | largura < `vw` |
| M35 | sem `z-50` | CT-B10 | `elementFromPoint` no painel |
| M46 | variantes `dark:` do fundo sem regra | CT-B10 escuro | luminância ≤ 0,2 |
| M47 | `dark:text-white` do título sem regra | CT-B10 escuro | contraste ≥ 4,5 |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou (Superfície de UI) | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Botão "Voltar ao topo": aparece ao rolar e volta ao topo, em toda tela dos painéis | | | CT-B01, CT-B04, CT-B05, CT-B08; CT-03 |
| 2 | Botão e slide-over do chat: abre, conversa e fecha, em toda tela do `/app` (fora das telas de autenticação, RQ-23) | | | CT-B03, CT-B09, CT-B10; ausência: CT-04, CT-10; volta: CT-09 |
| 3 | Dock: move para um canto ou arrasta; abre e fecha o leque (fora das telas de autenticação, RQ-19 a RQ-22) | | | CT-B01, CT-B04, CT-B06, CT-B07, CT-B08, CT-B11; ausência: CT-04, CT-10; volta: CT-09; round-trip: CT-11 |
