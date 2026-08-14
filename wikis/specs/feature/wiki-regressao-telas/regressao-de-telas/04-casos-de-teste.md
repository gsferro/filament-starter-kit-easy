# Casos de Teste — Regressão de telas em browser real

## Nenhum CT de backend novo — ausência deliberada

Este arquivo existe para **registrar a decisão**, não por formalidade de template. A skill
exige que a fronteira entre `04` (backend) e `05` (browser) seja explícita, e a fronteira
desta wiki é: **tudo está no `05`**.

### Por que

A camada de backend das seis features que produziram estas telas **já está coberta**:

| Suíte | Arquivos | Testes | Asserções |
|---|---|---|---|
| `tests/Kit` | 9 | — | — |
| `tests/Tenancy` | 5 | — | — |
| **Total medido** | 14 | **213** | **701** |

Medido em `vendor/bin/pest --group=kit` após o upgrade para Pest 5 (ver `03-progresso.md`).

Escrever CT de `Feature` ou `Unit` nesta wiki significaria uma destas três coisas, e nenhuma
se sustenta:

1. **Duplicar** asserção de regra de negócio que já existe — custo de manutenção sem cobertura
   nova. É o que a escada do Ponytail chama de reinventar o que está a alguns arquivos de
   distância.
2. **Testar o ato de testar** — assertar que os CT-B rodaram. Sem consumidor.
3. **Testar regra de negócio nova** — não há. Esta wiki não escreve uma linha em `app/`.

### A fronteira, aplicada

| Pergunta | Arquivo | Tipo |
|---|---|---|
| A regra de negócio está correta? | `tests/Kit/*`, `tests/Tenancy/*` — **já existem** | `Feature` / `Unit` |
| A tela está de pé, com JS executando, em tema claro e escuro? | `05-casos-de-teste-browser.md` | `Browser` |
| O papel entra onde deve, e vê o barramento na tela? | `05-casos-de-teste-browser.md` | `Browser` |

Regra de diagnóstico, que é o que dá valor a essa separação: **se um CT-B falha e nenhum CT de
backend falha, o defeito é de UI.** Se ambos falham, corrigir o backend primeiro.

### O buraco que esta análise revelou

Ao inventariar a cobertura existente para escrever esta nota, apareceu um vazio que **não** é
resolvido nesta wiki e virou dívida registrada:

> `tests/Kit/PaginasInfraTest.php` cobre 15 rotas de `/infra` e 3 de `/admin`. O painel `/app`
> — o único que o consumidor do kit usa todo dia — tem **zero** telas com smoke HTTP, além do
> `GET /app` genérico de `PaineisTest.php:115-119`.

Os CT-B01 do arquivo `05` passam a cobrir as 12 telas de `/app` em browser, o que é *mais* do
que HTTP. Mas a assimetria da suíte de backend permanece: `06-divida-tecnica.md` → **DT-04**.

## Índice de Casos

Vazio por decisão. Ver `05-casos-de-teste-browser.md` → `## Índice de CT-B`.
