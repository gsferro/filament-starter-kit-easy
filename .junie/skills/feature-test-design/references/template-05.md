> Referência da feature-test-design 1.16.0. Lida em: escrita do `05` (só quando uma linha de
> `## Costuras de Teste` do `04` tem costura `browser` — `SKILL.md` §Arquivo 05). Fonte única de: o
> template do arquivo `05-casos-de-teste-browser.md`.

# Template do arquivo 05 — Casos de Teste de Browser

**Path**: `wikis/specs/{branch}/{feature}/05-casos-de-teste-browser.md`. Antes de preencher, os
fatos do plugin que mudam o que se escreve: `references/pest-plugin-browser.md`.

```markdown
# Casos de Teste de Browser — {Card}: {Título}

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "{grupo}" de `## Costuras de Teste` do `04`
> Comando: `vendor/bin/pest tests/Browser --filter={Feature}` (em série — nunca `--parallel`)
<!-- `--testsuite=Browser` só se o phpunit.xml declara a suíte Browser: conferir com
     grep -n '<testsuite name="Browser"' phpunit.xml — sem saída, fica o comando acima -->

## Pré-requisitos
- [ ] `npm run build` executado
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($user)` — {ou o helper do projeto}

## Seletores
| Elemento | Seletor | Já existe? |
|---|---|---|

---

## CT-B01: {o que só o navegador prova}

**Por que browser e não Livewire**: {a asserção depende de JS executado / acessibilidade / cor}

```gherkin
# language: pt
  Cenário: [CT-B01] {…}
    Dado {…}
    Quando {…}
    Então {…}
```

**Roteiro executável**
| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | | `visit('/…')` | |
| 2 | | `->press('…')->assertPathIs('/…')` | |

**Assertions**: `assertPathIs` primeiro · `assertNoJavaScriptErrors()` · uma única âncora de persistência

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
```
