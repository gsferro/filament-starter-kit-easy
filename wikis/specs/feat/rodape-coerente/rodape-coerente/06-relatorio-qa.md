# Relatório de QA — Rodapé coerente

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** (UI com CSS medido em navegador + superfície pública nova)
> Natureza da wiki: evolução · Regressão: **sim**
> Independência: sub-agente `fw-qa-gate`/opus, sem acesso à conversa, em ambos os ciclos

## Vereditos

| Ciclo | Veredito | Blocker | Major | Minor | Cosmético | Data |
|---|---|---|---|---|---|---|
| 1 | REPROVADO → especificação | 0 | 4 | 5 | 1 | 2026-09-23 |
| 2 | REPROVADO → especificação | 0 | 5 novos + 1 carregado | 6 novos + 2 | 2 | 2026-09-23 |

**O veredito dos dois ciclos é o mesmo, e vale citá-lo porque resume a entrega:**

> *"O código está correto e medido, o que está errado é o que a wiki afirma sobre ele."*

**Nenhum dos 23 achados foi de comportamento do produto.** Os dois ciclos mediram o
comportamento e o aprovaram: a versão **não vaza** em nenhuma superfície anônima
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
| QA-17 | o recado fora de landmark | são **dois callbacks independentes** do mesmo hook; um não pode envolver o outro sem mover a lógica de escopo para a blade. A alternativa (segundo `<footer>`) dispara `landmark-no-duplicate-contentinfo` |
| QA-21 | guarda sem controle positivo do **detector** | se o vendor remover `min-height: 100vh`, a regra do kit vira inócua e `[CT-B01]` fica verde. O padrão de `OrdemDasCascadeLayersTest` é o candidato |
| QA-22 | nenhum caso afirma a **tag** `<footer>` | reverter para `<div>` deixa 113/113 verdes |
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

Não é aprovação. Os dois ciclos reprovaram, e o teto da skill é 3 — o terceiro escala ao usuário.
O que se registra aqui é que **todos os 23 achados foram fechados ou declarados com o custo
nomeado**, e que nenhum deles era de comportamento do produto.
