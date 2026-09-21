# Decisões Arquiteturais — Layout compacto

> Todos os números desta página foram **medidos**, não estimados. A medição rodou contra
> `demo.filamentphp.com` (Filament **v5.8.2**, a mesma do kit) com Playwright, lendo estilo
> computado. As capturas estão no scratchpad da sessão.

## ADR-01: Não existe densidade global no Filament 5 — é CSS, obrigatoriamente

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-04

### Contexto

A primeira pergunta era se o Filament já resolve isso. **Não resolve.** Varredura completa de
`vendor/filament/*/src`:

| O que existe | Onde | Escopo |
|---|---|---|
| `compact(bool\|Closure)` | `vendor/filament/schemas/src/Components/Concerns/CanBeCompact.php:11` | **por componente** |
| `Section`, `EmptyState`, `Repeater` usam o trait | `Section.php:45`, `EmptyState.php:25`, `Repeater.php:38` | componente |

E o que **não** existe:

- **`Table` não tem `compact()`.** `vendor/filament/tables/src/Table.php:11-45` lista 30 traits e
  nenhuma de densidade; `grep -rn "compact\|dense" vendor/filament/tables/src` devolve **zero**
- **`Panel` não tem `densely()`** nem equivalente. Os 34 concerns de
  `vendor/filament/filament/src/Panel/Concerns/` incluem `HasMaxContentWidth`, `HasTheme`,
  `HasColors`, `HasFont` — **nenhum** de espaçamento

### Decisão

Aceitar que "compacto para tudo" é **CSS**, e escolher o mecanismo de CSS na ADR-03.

### Consequências

- **Positivas**: a decisão para de procurar API que não existe
- **Negativas**: o kit assume manutenção de CSS, que é a família de defeito mais silenciosa que ele
  já teve (`.ai/rules/css-filament.md` registra duas ocorrências, e a `v0.37.1` foi a terceira)
- **Riscos**: o Filament pode ganhar densidade nativa e tornar isto obsoleto. **É desejável** — o
  roadmap registra o gatilho de reavaliação

---

## ADR-02: O toggle é barato; o CSS de qualidade é que é caro

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-04

### Contexto

O requisito trata "ligar/desligar em tempo de execução" como a parte difícil. **A medição inverteu
isso.**

O layout base do Filament avalia os render hooks **por request** —
`vendor/filament/filament/resources/views/components/layout/base.blade.php:44` (`STYLES_BEFORE`),
`:97`, `:128`. Uma closure que lê o settings decide a cada requisição. **O kit já faz exatamente
isso** em `KitServiceProvider::configureOrdemDasCascadeLayers()`, criado na `v0.37.1`.

### Decisão

O interruptor em runtime **entra**, porque é barato. O custo e o risco da entrega estão no **CSS**,
e é lá que a análise se concentra (ADR-03).

### Consequências

- **Positivas**: o mecanismo de decisão por request já existe, já está em produção e já é testado
- **Negativas**: nenhuma
- **Riscos**: nenhum novo

---

## ADR-03: O compacto sai de `--spacing`, não de CSS artesanal por classe `fi-*`

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-04, RQ-05

### Contexto

A CSS publicada do Filament é Tailwind 4: **todo** espaçamento é `calc(var(--spacing) * N)`.

- `var(--spacing)` aparece **1.228 vezes** em `public/css/filament/filament/app.css`
- `--spacing:.25rem` é declarado **uma vez só**, dentro de `@layer theme{:root,:host{…}}`

Uma declaração **fora de cascade layer** vence qualquer layer, independentemente de ordem e
especificidade — o mesmo mecanismo que a `v0.37.1` documentou, usado agora a favor.

### O que a medição mostrou

| Caminho | Altura da tabela | Manutenção |
|---|---|---|
| **1 declaração `--spacing`** | **−11,3%** (782px → 694px) | **zero** — não cita classe `fi-*` nenhuma |
| CSS artesanal, tentativa de 11 seletores | **+21,9%** — *piorou* | alto |
| Tema pago oficial | **−22,5%** (782px → 606px) | — |

### A tentativa ingênua falhou, e falhou para o lado errado

Escrever `.fi-ta-cell{padding-block:.5rem}` — o lugar óbvio — **somou** padding em vez de reduzir.
Causa, com vendor: `vendor/filament/tables/resources/css/cell.css:2` é `@apply p-0`. O padding mora
no **elemento da coluna**, espalhado por nove arquivos: `columns/text.css:26-28`,
`image.css:78`, `icon.css:12`, `color.css:8`, `checkbox.css:4`, `select.css:4`, `toggle.css:4`,
`text-input.css:4`, mais variantes em `cell.css:57-126` e `table.css:68-90`.

**Este é o achado que decide o custo.** A versão ingênua falha **em silêncio e para o lado errado**,
e nenhuma asserção de HTML, status ou console pega — é exatamente o modo de falha que
`.ai/rules/css-filament.md` já registra e que a `v0.37.1` pagou.

### Decisão

`--spacing`, em **níveis de intensidade** (ADR-04). **Não** escrever CSS por classe `fi-*`.

### Alternativas Consideradas

1. **CSS artesanal mirando os −22%** — descartada, e com o usuário: o tema oficial precisa de
   **548 blocos de regra sobre 370 classes `fi-*`** (138 só de tabela) para chegar lá. Um
   subconjunto honesto exigiria acertar os nove arquivos `columns/*.css` mais `cell`, `table` e
   `header-cell`. Isso é **lista congelada de classes de vendor**, que apodrece em silêncio no
   `composer update` — a armadilha que a rule do projeto já proíbe. O ganho extra (−11% → −22%)
   não paga
2. **Comprar o tema oficial (US$ 29)** — ver ADR-05

### Consequências

- **Positivas**: uma declaração; **imune a `composer update`**, porque não referencia nada do
  vendor; vence sem `!important`
- **Negativas**: entrega **metade** do ganho do tema pago, e **distorce proporções** — ícones
  20→15px, checkbox 16→12px, e o input fica **menor** que o botão (27px × 32px). Medido, aceito
  pelo usuário com a mitigação da ADR-04
- **Riscos**: a medição foi na **demo limpa**. O kit tem jobs-monitor, auth-designer,
  resized-column e outros que podem reagir diferente. **Mitigação decidida com o usuário: medir no
  kit antes de fechar** — ver `03-progresso.md`

---

## ADR-04: Níveis de intensidade, não um booleano

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-05

### Contexto

A distorção de proporção da ADR-03 é real e não tem conserto barato. Mas ela **escala com a
intensidade**: quanto menos se encolhe, menos ela aparece.

### Decisão

Expor **níveis** (confortável / compacto / denso), não liga-desliga. Custa a mesma declaração.

**Decidido com o usuário em 2026-09-21**, entre quatro opções apresentadas com os números.

### Alternativas Consideradas

1. **Booleano** — descartada pelo usuário: fixaria uma intensidade para todo mundo, e a distorção
   é questão de gosto e de tela
2. **Só o estudo documentado** (RQ-06 pura) — descartada pelo usuário, porque os três critérios de
   "fácil" passaram

### Consequências

- **Positivas**: quem acha a distorção incômoda escolhe o nível intermediário; o custo é o mesmo
- **Negativas**: três valores para documentar e testar em vez de um
- **Riscos**: nível mais agressivo pode quebrar layout de plugin. **A medição no kit cobre os três
  níveis**, não só um

---

## ADR-05: O tema pago é a melhor compra — e não pode entrar no kit

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-01, RQ-06

### Contexto

`filament/compact-theme` é da **própria Filament** — oficial, não de terceiro. US$ 29 avulso,
Filament v4 e v5.

Medido por diff dos bundles da demo (`stock-yzSkOu0_.css` × `stock-compact-BRj9qN_J.css`):

- superset puro: **1.354 linhas acrescentadas, 1 removida**
- tudo em `@layer compact-theme`, dirigido por **11 variáveis** de ajuste
- **548 blocos de regra**, **370 classes `fi-*`**, **138 regras só de tabela**
- **não** mexe em `--spacing` nem em `--text-*` — por isso não distorce ícone nem fonte
- guarda `:not(.fi-compact)`, então o `compact()` por componente continua vencendo

### Decisão

**Não entra no kit.** Entra no **roadmap**, documentado com os números, como a recomendação para
quem tem um projeto único.

### O que o desqualifica — e não é o preço

1. **Licença de projeto único.** Um starter kit é distribuído; embarcá-lo obrigaria **cada
   instalação** a comprar. É a mesma família do veto que `.ai/rules/general.md` já registra para o
   `filament/blueprint`
2. **É build-time, não runtime.** Exige `viteTheme()` — que o kit **não usa em nenhum dos três
   painéis** — mais `npm run build`, e uma ordem de imports que conflita com o
   `configureOrdemDasCascadeLayers()` da `v0.37.1`
3. **As regras não são escopadas** por classe de raiz, então **não dá para ligar e desligar em
   runtime** com o CSS como vem

### Consequências

- **Positivas**: a análise fica registrada com número, e quem derivar um projeto do kit tem a
  informação para comprar com conhecimento de causa
- **Negativas**: o kit fica com metade do ganho possível
- **Riscos**: nenhum

---

## ADR-06: A armadilha do `settings.md` **não** se aplica — desde que seja render hook

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-04

### Contexto

`.ai/rules/settings.md` registra um caso doloroso do próprio kit: um toggle que **gravava sem
fazer efeito**, porque o middleware era fixado no array da rota no momento do registro, não por
request. A pergunta obrigatória era se densidade cai na mesma armadilha.

### Decisão

**Não cai, pelo caminho do render hook. Cairia pelo caminho do tema Vite.**

| Caminho | Avaliado quando | Toggle funciona? |
|---|---|---|
| **Render hook** (`STYLES_BEFORE`) | **no render**, por request — `base.blade.php:44` | **sim** |
| `viteTheme()` | no **registro do painel** — `HasTheme.php:27-33`, **não aceita `Closure`** | **não** — grava e só vale no próximo deploy |

O critério que a própria rule fixa (`settings.md:13`) é *"lida por request… pode ir para o
Settings"*. O render hook satisfaz.

### Consequências

- **Positivas**: o toggle em tela governa de verdade, e isso está provado pelo mecanismo, não
  assumido
- **Negativas**: fecha a porta para o tema Vite, e portanto para o tema pago em runtime (ADR-05)
- **Riscos**: se alguém no futuro migrar o kit para `viteTheme()`, este toggle **silenciosamente
  para de funcionar**. Registrado no roadmap como armadilha conhecida

### Referências

- `vendor/filament/filament/resources/views/components/layout/base.blade.php:44`
- `vendor/filament/filament/src/Panel/Concerns/HasTheme.php:27-33`
- `.ai/rules/settings.md:13`
- `app/Settings/ConfiguracoesDoKit.php:aplicarNaConfig():504`, chamado em
  `app/Providers/KitServiceProvider.php:aplicarNaConfig():355` *(alterado em 2026-09-21: :478 e :346 eram as linhas de antes de a propriedade nova desta feature entrar no arquivo; deslocamento pego pela reverificação de citações do step 7)*
