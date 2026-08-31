# Casos de Teste de Browser — Gráficos com ApexCharts

> Runtime: `pest-plugin-browser` 5 (Playwright). O plugin sobe o próprio servidor HTTP in-process.
> Comando: `composer test:browser` · **nunca `--parallel`** (`.ai/rules/testes-browser.md`).

## Por que existe CT-B nesta feature

Um gráfico ApexCharts **não existe no HTML** que o servidor devolve. O componente Livewire entrega
um `<div>` vazio e um array de opções; quem desenha é o JavaScript, no navegador.

| Afirmação | Só o navegador prova? | Onde vive |
|---|---|---|
| os números da série estão certos | não — é a propriedade `$options` do componente | CT-01…CT-09 (componente) |
| o widget se esconde sem a tabela de origem | não — HTML renderizado | CT-10 (Feature) |
| o intervalo de atualização foi declarado | não — propriedade da classe | CT-11 (Feature) |
| **o gráfico é efetivamente desenhado** | **sim** — o SVG é construído em runtime pelo ApexCharts | **CT-B01** |
| **o dashboard não quebra com os gráficos juntos** | **sim** — erro de JS de um widget derruba os demais sem mover o status HTTP | **CT-B01** |

**Teto do perfil**: a área A4 é `mínimo` (teto 0 CT-B). **Estouro justificado**: o mutante M-B1
abaixo não tem matador em nenhuma outra camada, e ele é o modo de falha mais provável da entrega —
o gráfico existir como configuração e não aparecer na tela.

## Pré-requisitos

- [ ] `npm run build` (pré-requisito **duro** — sem o manifest do Vite toda tela responde `ViteException`)
- [ ] `php artisan filament:assets` — publica o JS do ApexCharts registrado pelo plugin
- [ ] `$this->actingAs(usuarioDoKit('master_global'))` antes do `visit()`
- [ ] **Dado semeado**: o dashboard com todas as bases zeradas desenha gráficos vazios, e um gráfico vazio não distingue "desenhou" de "não desenhou". O cenário cria ao menos um registro de cada fonte.

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| container do gráfico | o `id` vem de `$chartId` (`iaExecucoesPorDia`, …) — **declarado por nós**, portanto estável | sim, se `$chartId` for declarado em cada widget |
| SVG desenhado | `svg.apexcharts-svg` dentro do container | contrato do ApexCharts |
| título do widget | texto do `$heading` | sim |

> **Vantagem desta feature sobre as outras duas**: o seletor não depende de dívida de
> `data-testid`. O `$chartId` é escrito pelo kit e vira o `id` do elemento — é a coisa mais
> próxima de um `data-testid` que a entrega tem. **Declarar `$chartId` em todos os widgets de gráfico**
> deixa de ser detalhe de apresentação e passa a ser requisito de testabilidade.

---

## CT-B01: os gráficos do dashboard são desenhados de fato

**Por que browser e não componente**: a assertion é sobre um `<svg>` que o servidor nunca enviou.
Ele é construído pelo ApexCharts depois que a página carrega. Um teste de componente prova que os
**dados** chegaram; nada além do navegador prova que o **gráfico** apareceu.

```gherkin

# language: pt

Funcionalidade: Gráficos do dashboard

  Regra: os gráficos configurados são desenhados na tela

    Cenário: [CT-B01] o painel de infraestrutura desenha os três gráficos dele
      Dado execuções de IA e jobs registrados
      Quando o administrador de infraestrutura abre o painel de infraestrutura
      Então o gráfico de execuções por dia está desenhado
      E o gráfico de execuções por status está desenhado
      E o gráfico de taxa de sucesso das filas está desenhado
      E o console do navegador não registra erro
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | semear as fontes | `AiRun::create([...]); DB::table('queue_monitors')->insert([...]);` | dado nas três fontes |
| 2 | autenticar | `$this->actingAs(usuarioDoKit('master_global'));` | — |
| 3 | abrir o dashboard | `$pagina = visit('/infra');` | dashboard |
| 3b | **janela alta** | `->resize(1440, 4000)` | os widgets abaixo da dobra entram em viewport |
| 4 | gráfico 1 | `->assertPresent('#iaExecucoesPorDia svg.apexcharts-svg')` | SVG desenhado |
| 5 | gráfico 2 | `->assertPresent('#iaExecucoesPorStatus svg.apexcharts-svg')` | SVG desenhado |
| 6 | gráfico 3 | `->assertPresent('#filasTaxaDeSucesso svg.apexcharts-svg')` | SVG desenhado |
| 7 | console | `->assertNoJavaScriptErrors()` | sem erro |

> ⚠️ **O passo 3b não é detalhe de conforto — sem ele o cenário falha sem defeito nenhum.**
> Widget do Filament carrega **adiado**, e o gatilho é a entrada em viewport. Medido na
> implementação: `/infra` traz **3 widgets** na resposta inicial; os gráficos ficam abaixo da
> dobra e nunca chegam a ser pedidos. O erro sai como *"Expected element […] to be present in the
> DOM"*, que se lê como "o gráfico não desenhou" quando o que houve foi outra coisa.

**Assertions**

- **Âncora**: a presença do `<svg>` **dentro do container de cada gráfico**. Afirmar só
  `assertSee($heading)` seria falso ✅ — o título é HTML do servidor e aparece com o ApexCharts
  completamente quebrado.
- `assertNoJavaScriptErrors()` e **não** `assertNoSmoke()`: o dashboard do `/infra` carrega
  componentes de sete plugins de terceiros (`.ai/rules/testes-browser.md`).
- **Assertion de apoio, nunca oráculo único**: o console limpo passa com a página em branco.
- **Nenhum `wait()`**: o plugin reexecuta as assertions até o teto de 20 s do
  `pest()->browser()->timeout()`, o que cobre o tempo de o ApexCharts montar o SVG.

**Por que os três gráficos no mesmo cenário e não três cenários**

Um boot de navegador serve aos três, e o cenário ainda ganha uma propriedade que três cenários
separados não teriam: ele prova que os três **convivem** na mesma página. Erro de JS de um widget
derruba o Alpine dos demais, e nenhum teste de componente enxerga isso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-B1 | `FilamentApexChartsPlugin::make()` não registrado no painel: o JS do ApexCharts não é carregado, o container fica vazio e **nada indica erro** | CT-B01 (passos 4-6) |
| M-B2 | `$chartId` duplicado entre dois widgets: o segundo gráfico não desenha, porque o ApexCharts liga o desenho ao `id` | CT-B01 (o passo do gráfico duplicado falha) |
| M-B3 | `getOptions()` devolve estrutura que o ApexCharts recusa (`series` no formato errado para donut) — erro no console, gráfico ausente | CT-B01 (passos 5 e 7) |
| M-B4 | polling de 5 s herdado: o gráfico redesenha em laço | ⚠️ **sem matador aqui** — coberto por CT-11 na camada barata. Registrado para não parecer omissão |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| um CT-B para o dashboard do `/admin` (rosca de convites, radial de organizações) | mata exatamente os mesmos mutantes que CT-B01, num segundo boot de navegador. Se M-B1 ocorrer, ele ocorre nos dois painéis |
| conferir o valor exibido dentro do gráfico (o "75%" do radial) | o ApexCharts renderiza o rótulo dentro do SVG; a assertion seria sobre texto gerado pela biblioteca, não pelo kit. O valor já é provado por CT-04, na camada barata |
| gráfico em tema escuro | `assertSee` não valida tema, e a paleta é a do ADR-05 (tokens semânticos). Defeito de cor aqui é conferência visual, e está no roteiro *Desenhado × Implementado* |
| medir o intervalo de polling pela aba Network | não há API do plugin para isso; a verificação é manual, e está na Verificação Final do PRD |
| dashboard com **todas as bases zeradas** em navegador | candidato legítimo — é o estado de uma instalação nova. Cortado porque o modo de falha dele (estouro por divisão por zero) já é morto por CT-05 e CT-07, que são ordens de magnitude mais baratos |

---

## Roteiro de Validação: Desenhado × Implementado

> Preencher no step 7 da `feature-wiki`. Divergência vira linha em "Desvios do Plano".

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `/infra` com gráfico de área de execuções por dia (14 dias, largura total) | igual | ✅ | CT-08 (14 pontos) + CT-B01 (`#iaExecucoesPorDia svg.apexcharts-svg`) |
| 2 | o subtítulo desse gráfico com a comparação percentual contra os 14 dias anteriores | igual — `getSubheading()` monta total + variação | ✅ | `IaExecucoesPorDia::getSubheading()` |
| 3 | `/infra` com rosca de execuções por status | igual | ✅ | CT-09 + CT-B01 |
| 4 | `/infra` com radial de taxa de sucesso das filas | igual | ✅ | CT-04…CT-07 + CT-B01 |
| 5 | `/admin` com rosca de convites por situação, quatro fatias na ordem fixa | igual | ✅ | CT-01, CT-02, CT-03 |
| 6 | cores vindas de token semântico, coerentes em tema claro e escuro | `var(--success-500)` e irmãs em todos os gráficos | ⬜ **conferência visual pendente** | — |
| 7 | nenhum gráfico atualizando a cada 5 s | igual | ✅ | CT-11, derivado dos widgets registrados |
| 8 | dashboard com banco vazio: gráficos zerados, sem erro | igual | ✅ | CT-02 (rosca zerada), CT-05 (radial em 0%) |
| 9 | *(não desenhado)* widget carrega **adiado**, por viewport | ⚠️ o CT-B precisa de `->resize(1440, 4000)`: com janela padrão os gráficos ficam abaixo da dobra e nunca são pedidos | ⚠️ | ver Notas de Implementação no `03` |
