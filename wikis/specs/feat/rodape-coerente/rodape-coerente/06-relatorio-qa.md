# Relatório de QA — Rodapé coerente

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** (UI com CSS medido em navegador + superfície pública nova)
> Natureza da wiki: evolução · Regressão: **sim**
> Independência: sub-agente `fw-qa-gate`/opus, sem acesso à conversa, **nos quatro ciclos**

## Vereditos

| Ciclo | Veredito | Blocker | Major | Minor | Cosmético | Data |
|---|---|---|---|---|---|---|
| 1 | REPROVADO → especificação | 0 | 4 | 5 | 1 | 2026-09-23 |
| 2 | REPROVADO → especificação | 0 | 5 novos + 1 carregado | 6 novos + 2 | 2 | 2026-09-23 |
| 3 | REPROVADO → especificação | 0 | 8 | 5 | 2 | 2026-09-23 |
| 4 | REPROVADO → especificação | 0 | 6 | 7 | 2 | 2026-09-23 |

**O teto da skill é 3 ciclos.** O ciclo 4 rodou por autorização explícita do usuário
(*"roda o ciclo 4"*), que é o que a `## Convergência do Loop` manda fazer quando o teto estoura:
escalar, e não decidir sozinho. **Total: 53 achados em 4 ciclos.**

**O veredito dos quatro ciclos é o mesmo, e vale citá-lo porque resume a entrega:**

> *"O código está correto e medido, o que está errado é o que a wiki afirma sobre ele."*

**52 dos 53 achados não foram de comportamento do produto** — a única exceção é o QA-17, que só
apareceu no ciclo 4, quando o oráculo finalmente **rodou**. Quanto ao resto, os quatro ciclos
mediram o comportamento e o aprovaram: a versão **não vaza** em nenhuma superfície anônima
(`app.version='999-sonda'` invisível em cinco rotas, inclusive no `wire:snapshot`), o nome sai
escapado em todas, o escopo do hook cobre exatamente as duas classes que existem, e a regressão
fechou em `2.872 / 2.869 / 11.146` nas duas execuções.

## O que cada ciclo achou

### Ciclo 1 — 10 achados

| # | Sev | Achado |
|---|---|---|
| QA-01 | Major | `RQ-05` marcada *"Assumido"* enquanto as vizinhas foram ao usuário |
| QA-02 | Major | **V3 e V5 falsas**, e o `03` as declarava *"confirmada"* |
| QA-03 | Major | o `03` com **27/27 checkboxes abertos** e a tabela de Rules vazia |
| QA-04 | Major | a correção de geometria **sem teste nenhum** |
| QA-05 | Minor | Markdown literal no `helperText`, que o Filament escapa com `e()` |
| QA-06 | Minor | `rodapeDe()` sem controle positivo na mesma rota |
| QA-07 | Minor | 3 casos medem presença do recado em `rodapeDe()` |
| QA-08 | Minor | 4 de 5 citações na linha errada |
| QA-09 | Minor | `.kit-versao` fora de landmark em página pública |
| QA-10 | Cosmético | contagem de casos do README desatualizada |

### Ciclo 2 — 11 achados novos, **10 nascidos das correções do ciclo 1**

| # | Sev | Achado |
|---|---|---|
| QA-11 | Major | `group('kit')` em arquivo de browser viola *"nunca `--parallel` com browser"* |
| QA-12 | Major | a **regra de CSS** sem passo no `01` nem ADR no `02` |
| QA-13 | Major | `[CT-B01]`/`[CT-B02]` só no teste; o `04` dizia que não existiam |
| QA-14 | Major | *"5/5 ok, 3 ERRO"* falso nas três partes |
| QA-15 | Major | `01` ainda dizia *"nada que só o navegador prove"* |
| QA-16 | Minor | a ADR-03 ficou com a citação velha |
| QA-17 | Minor | o **recado** continua fora de landmark |
| QA-18 | Minor | captura de arte não refeita, item sumiu do `03` |
| QA-19 | Minor | `.gitignore` sem `RQ`, passo ou ADR |
| QA-20 | Cosmético | contagem de asserções errando por um |
| QA-21 | Minor | guarda de CSS sem controle positivo do detector |
| QA-22 | Minor | a correção de QA-09 sem oráculo que fixe a tag |
| QA-23 | Cosmético | este arquivo não existia |

### Ciclo 3 — 15 achados, **11 nascidos das correções do ciclo 2**

O gate nomeou o padrão, e ele não é da wiki — é meu:

> *"As correções fecham o caso citado e deixam a classe aberta."*
> `QA-11 → QA-24` · `QA-06 → QA-30` · `QA-07 → QA-31` · `QA-13 → QA-26`

**Cinco dos oito Major eram o vizinho do achado que o ciclo anterior tinha fechado.**

| # | Sev | Achado |
|---|---|---|
| QA-24 | Major | `group('kit')` sobrevivente em `tests/Browser/` — o vizinho do QA-11 |
| QA-25 | Major | *"8 das 10 páginas"* — são 9, em 4 lugares |
| QA-26 | Major | `[CT-B01]`/`[CT-B02]` ainda descritos no `04` como candidatos, já escritos no disco |
| QA-27 | Major | *"113 asserções"* onde são 113 **casos**, em 4 lugares |
| QA-28 | Major | a tabela `## Conformidade com Rules` **apagada inteira** por um corte meu |
| QA-29 | Major | guarda de CSS sem detector — reclassificado de *"lacuna"* para **violação** de `.ai/rules/css-filament.md` |
| QA-30 | Major | 4 asserções de ausência ainda sem controle positivo — o vizinho do QA-06 |
| QA-31 | Major | **3 cópias** do mesmo regex, divergentes — o vizinho do QA-07 |
| QA-32…QA-34 | Minor | resíduos de redação, citação e contagem |
| QA-35 | Minor | **justifiquei não fechar uma lacuna com uma consequência do axe que nunca tinha rodado** |
| QA-36 | Minor | degradação do `--mutate` declarada sem a prova negativa (`php -m`) |
| QA-37, QA-38 | Cosmético | contagens |

**QA-28 é o achado que explica os outros dois.** Ao reescrever uma seção, casei uma **referência**
(`Ver ## Quality Gate`) dentro de um checkbox em vez do **cabeçalho**, e o corte levou junto a
tabela de Conformidade com Rules inteira — o que deixou o eixo L do gate **cego por dois ciclos**.
QA-24 e QA-29 estavam escondidos atrás dela. Foi o mesmo erro que eu tinha cometido no `04` horas
antes.

**QA-31 é o mais instrutivo, e não pelo regex.** A premissa escrita no comentário era falsa: ele
afirmava *"a classe aparece mais de uma vez no documento"*, e o gate mediu `substr_count() = 1`.
O que consertou o helper foi o `[^>]*`, não o laço. **Afirmar uma causa sem medi-la, três vezes na
mesma feature** — com o Blocker do 6.5 (medi o layout errado) e as verificações V3/V5 falsas.

### Ciclo 4 — 15 achados, **acima do teto, por autorização explícita**

| # | Sev | Achado | Desfecho |
|---|---|---|---|
| QA-39 | Major | `[CT-B03]` **nasceu no disco**, sem passar pelo `04` — Proibição 11 da `feature-test-design` | Regra **R8** derivada no `04`, com M58–M62 |
| QA-40 | Major | o oráculo de `[CT-B03]` era cego ao defeito que veio guardar: `assertNoAccessibilityIssues($level = 1)` mantém só `critical`/`serious`, e `region` é **`moderate`** | oráculo de **pertinência** nas 3 regras de landmark, com controle positivo do detector |
| QA-44 | Major | cópia do extrator com tag fixa em `VersaoNoRodapeTest.php:574` — sobreviveu à unificação do QA-31 | trocada por `assinaturaDoRodape()` |
| QA-45 | Major | a segunda cópia, em `RodapeCoerenteTest.php:381` | trocada por `recadoDoRodape()` |
| QA-47 | Major | a linha **T** do SFDIPOT dizia *"não se aplica: nada depende de instante"* — na mesma página em que **R1** lista *"valor limite temporal"* e o `©` calcula `now()->year` | linha reescrita |
| QA-50 | Major | este relatório tinha **2 dos 4 ciclos** | os quatro, com o total |
| QA-41 | Minor | `CHANGELOG.md` dizia que o prefixo *"evita o `vv1.2.3`"*; `[CT-15]` afirma o contrário, e o `[CT-15]` estava certo | texto corrigido |
| QA-42 | Minor | contagem do browser: **6/35** no `## Testes`, **9/41** na `## Verificação Final` — as duas velhas | **9/44**, medido |
| QA-43 | Minor | *"7/7 citações ok"* — o verificador não alcançava as 4 citações abreviadas com `...` | as 4 expandidas; **21/21**, medido |
| QA-46 | Minor | docblock órfão, a 70 linhas da função que dizia descrever | fundido no bloco dos extratores, e agora vale para os três |
| QA-51 | Minor | `## Despachos` registrava **1 dos 4** ciclos de gate | os quatro |
| QA-52 | Minor | o `02` dizia `© Nome`, sem o ano que o ADR-04 fixa | corrigido |
| QA-53 | Minor | o `03` dizia **4 lacunas**, o `04` dizia **2** | **2** (M38, M51), conferido contra `## Mutantes sem matador` |
| QA-48, QA-49 | Cosmético | contagens de `it()` e de asserções | medidas |

#### O ciclo 4 achou um defeito de produto — o primeiro em quatro ciclos

QA-17 estava classificado desde o ciclo 1 como **lacuna aceitável**, com a justificativa de que
envolver o recado num `<footer>` dispararia `landmark-no-duplicate-contentinfo`. O ciclo 3 (QA-35)
já tinha achado o problema dessa frase: **o axe nunca tinha rodado**.

Quando o `[CT-B03]` passou a medir de verdade, ele ficou **vermelho em 2 das 3 rotas**: o recado
estava mesmo fora de landmark. As três formas foram então medidas, e não deduzidas:

| Tag do recado | `region` | duplicidade de `contentinfo` |
|---|---|---|
| `<div>` — o que estava | **acusa o recado** | limpo |
| `<footer>` — o que a lacuna supunha | limpo | **acusa a assinatura**, nas duas regras |
| **`<aside>`** — adotado (ADR-09) | limpo | limpo |

A dedução do ciclo 1 estava certa **sobre `<footer>`**. O que faltava não era rigor: era a
**terceira opção**. Os dois lados discutiram `<div>` contra `<footer>` e ninguém perguntou se
havia outro landmark. `complementary` estava disponível o tempo todo.

#### E o QA-44/45 cobrou o preço na hora, como o QA-07 tinha cobrado

Quando o ADR-09 trocou a tag, **as duas cópias quebraram junto com a própria constante, e 8 casos
caíram de uma vez**. Os que afirmavam ausência teriam ficado **verdes sobre o recorte vazio** se
não fosse o controle positivo que o QA-06 obrigou a escrever no ciclo 2.

A correção foi por **classe**, e não por ocorrência: extrator agnóstico de tag
(`(?P<tag>[a-z]+)` … `</(?P=tag)>`) e a **sentinela `[CT-24]`**, que reprova qualquer `preg_*` novo
citando os marcadores do rodapé — com dois controles positivos da varredura e um do reconhecedor
(`[CT-25]`). É a primeira correção desta feature que impede o **próximo** caso da classe, em vez
de fechar o atual.

## Os três que eu declarei irredutíveis, e não eram

O ciclo 2 chamou pelo nome:

> *"Declarar como lacuna irredutível o que custa 7 linhas é o que a skill chama de teatro de
> qualidade."*

**Estava certo.** QA-01 custou mover a pergunta para o `00` — que é o **oráculo**, e registrá-la
só no `03` deixava quem lê o requisito sem saber que ela existe. QA-06 custou uma linha por caso.
QA-07 custou um helper de seis linhas.

E o QA-07 **cobrou o preço na hora**: o helper novo falhava aberto **pelo mesmo motivo** que o
`rodapeDe()` que ele veio substituir — a classe aparece mais de uma vez no documento, e o
`preg_match` simples casava a ocorrência errada. Quem o pegou foram os controles positivos do
QA-06, vermelhos no primeiro `pest`.

## Lacunas que ficam declaradas

| # | Lacuna | Por que não foi fechada |
|---|---|---|
| ~~QA-17~~ | ~~o recado fora de landmark~~ | **FECHADO no ciclo 4, e era defeito, não lacuna.** `<aside>` (landmark `complementary`) resolve o `region` sem duplicar o `contentinfo`. As três formas foram medidas — ver ADR-09. A justificativa original estava certa sobre `<footer>`, e nunca tinha sido medida (QA-35) |
| QA-21 | guarda sem controle positivo do **detector** | se o vendor remover `min-height: 100vh`, a regra do kit vira inócua e `[CT-B01]` fica verde. O padrão de `OrdemDasCascadeLayersTest` é o candidato |
| ~~QA-22~~ | ~~nenhum caso afirma a **tag**~~ | **FECHADO no ciclo 4.** `[CT-B03]` mede a **propriedade** (a tela pública não ganha problema de acessibilidade) em vez de congelar a tag. Mutantes M58, M59 e M62 **medidos mortos**: reverter a assinatura para `<div>` deixa 3 de 3 rotas vermelhas |
| QA-18 | captura de arte | **débito explícito**: refazer exige `composer art`, que roda a suíte de browser inteira e regrava binários |
| M38 | `auth()->check()` × `filament()->auth()->check()` | nesta instalação as duas não divergem; o risco é do projeto derivado |
| M51 | tela autenticada do layout `simple` | atravessar aquele middleware arrasta fronteira de outra feature |

## O que os gates não puderam verificar

- **`pest --mutate`** — sem PCOV e sem Xdebug. Degradação **conferida e verdadeira**: o plugin
  `pest-plugin-mutate` está instalado; falta o driver
- **`pest --agent`** — a opção não existe no Pest 5.0.5
- **Playwright MCP** — indisponível. Os gates usaram Playwright direto e o axe do
  `pest-plugin-browser`
- **Veredito do axe em tela de login** — nenhum CT-B o roda, e o gate não escreve teste. O QA-17
  reporta o fato estrutural medido, não a saída do axe

## O que este relatório não é

Não é aprovação. **Os quatro ciclos reprovaram.** O teto da skill é 3, e o quarto rodou por
autorização explícita do usuário.

O que se registra aqui é que **os 53 achados foram fechados ou declarados com o custo nomeado**, e
que **52 deles não eram de comportamento do produto** — eram do que a wiki afirmava sobre ele, ou
do que a suíte deixava de medir.

O 53º é o QA-17, e ele só apareceu no ciclo 4 porque foi o primeiro em que o oráculo **rodou** em
vez de ser deduzido. Quatro ciclos é o preço de ter escrito, no ciclo 1, uma justificativa que
soava certa. Ela era certa — sobre a alternativa errada.
