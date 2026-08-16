# Casos de Teste de Browser — Hub de navegação em cards

> Runtime: `pest-plugin-browser` 5 (Playwright). O plugin sobe o próprio servidor HTTP in-process.
> Comando: `composer test:browser` · **nunca `--parallel`** (`.ai/rules/testes-browser.md`).

## Por que existem CT-B nesta feature

Uma afirmação não tem como ser provada fora do navegador:

| Afirmação | Por que só o navegador prova | Cenário |
|---|---|---|
| o cartão tem aparência de cartão | é o risco central do ADR-02. Sem o CSS, o HTML é **idêntico** e `assertSee` passa. Falha 100% silenciosa | **CT-B01** |
| a busca filtra os cartões | é Alpine puro — mas o comportamento é **do vendor**, e o único mutante é `$searchable` esquecido em `false` | **cortado** na auditoria Ponytail; conferido no roteiro *Desenhado × Implementado* |

**Teto do perfil**: a área A3 (aparência) é `mínimo`, teto 0 CT-B. **Estouro justificado** pela
regra "o gate vence o teto": M-CSS não tem matador em nenhuma outra camada, e é a falha que a
wiki inteira antecipa.

## Pré-requisitos

- [ ] `npm run build` (pré-requisito **duro**)
- [ ] `php artisan filament:assets` — publica o `cards.css` registrado pelo `KitServiceProvider`
- [ ] Seeders no `beforeEach`: `ShieldPermissionsSeeder` + `PapeisSeeder`
- [ ] `$this->actingAs(usuarioDoKit('master_global'))` antes do `visit()` — nunca login pela tela
- [ ] `tests/Browser/Screenshots` no `.gitignore`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| cartão | `a[data-search-text]` — atributo que a blade do pacote injeta quando `$searchable` é true | sim (contrato do pacote) |
| rótulo do cartão | texto visível do destino | sim |

> **Dívida conhecida**: o kit não tem `data-testid` (`.ai/rules/testes-browser.md`). O
> `data-search-text` é contrato do pacote e serve para localizar o cartão.

---

## CT-B01: o cartão tem aparência de cartão

**Por que browser e não componente**: é o risco do ADR-02 em forma executável. Sem o
`cards.css`, o HTML é **byte a byte o mesmo** — muda apenas o CSS aplicado. Todo `assertSee`,
todo `assertOk`, todo teste de componente passa com a grade completamente despintada.

```gherkin
  Regra: o cartão é renderizado como cartão, não como link solto

    Cenário: [CT-B01] a grade do hub aparece pintada
      Dado o administrador de infraestrutura no hub do painel de infraestrutura
      Quando a página termina de carregar
      Então cada cartão tem fundo próprio, separado do fundo da página
      E a grade dispõe os cartões em colunas, não em uma coluna só
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | autenticar e abrir | `visit('/infra/hub-de-infraestrutura')` | grade |
| 2 | evidência visual | `->screenshot('hub-infra')` | PNG para conferência |
| 3 | console | `->assertNoJavaScriptErrors()` | sem erro |

**Assertions**

Este é o cenário mais honesto do conjunto sobre a própria limitação: **não existe assertion barata
para "está pintado"**. `.ai/rules/testes-browser.md` já registra isso — *"para defeito de cor não
há saída barata: é screenshot e olhar"*.

Duas opções, nesta ordem de preferência:

1. **`assertScreenshotMatches()`**, se o projeto adotar baseline de screenshot. Custo: um PNG
   versionado e uma tolerância a calibrar; benefício: a regressão de CSS vira teste vermelho.
2. **Screenshot como evidência + conferência humana** no roteiro *Desenhado × Implementado*.
   É o que o kit faz hoje, e o que este cenário assume até decisão em contrário.

> **O que este cenário NÃO faz**: `->inDarkMode()->assertSee('Execuções de IA')` **não testa tema**.
> O texto está no DOM em qualquer cor, inclusive branco sobre branco. Escrever isso e chamar de
> cobertura de aparência seria falso ✅ — exatamente o que a skill proíbe.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-CSS | o `cards.css` não é criado, ou não é registrado no `KitServiceProvider`, ou o `filament:assets` não roda: a grade vira uma lista de links sem cartão | CT-B01 — **e apenas por screenshot**, salvo se o projeto adotar a opção 1 |
| M-CSS2 | `$columns` deixado em 5 ou mais: a classe `2xl:grid-cols-{n}` é interpolada e **nunca é gerada** (ADR-03) — a grade perde a coluna extra em telas grandes | CT-B01 (passo 2, em viewport largo). **Cobertura fraca, declarada** |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| **a busca filtra os cartões** (era CT-B01) | **cortado na auditoria Ponytail**: testa o Alpine do vendor. O único mutante — `$searchable` esquecido em `false` — é uma propriedade booleana, conferida a olho no roteiro abaixo |
| clicar num cartão e conferir que chega ao destino | a URL já é provada por CT-05 (componente), e clicar num `<a href>` testa o navegador, não o kit |
| busca nos três hubs | mesmo mutante; browser em série é o recurso mais caro da suíte |
| grupo colapsável (`collapsible()`) | o kit não usa a API nesta entrega |
| hub em tema escuro | `assertSee` não valida tema, e o dark mode do kit já tem cobertura própria em `tests/Browser/TemaEscuroTest.php` |
| hub do `/app` em browser | `BrowserTenancy` é a suíte mais cara (o primeiro cenário paga ~25 s de compilação de componentes com views frias) e mataria os mesmos mutantes |
| acessibilidade da grade (`assertNoAccessibilityIssues`) | candidato legítimo, cortado por escopo: a acessibilidade dos cartões é do vendor. **Registrado como dívida** — se o kit adotar auditoria de acessibilidade, o hub entra nela |

---

## Roteiro de Validação: Desenhado × Implementado

> Preencher no step 7 da `feature-wiki`. Divergência vira linha em "Desvios do Plano".

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `/infra/hub-de-infraestrutura` com grade de 3 colunas e busca | igual | ✅ | CT-03 + CT-B01 (`a[data-search-text]` presente) |
| 2 | `/admin/hub-de-administracao` com grade de 3 colunas e busca | igual | ✅ | CT-01, CT-03 |
| 3 | `/app/{tenant}/hub-do-negocio` com grade de 3 colunas, sem busca | igual | ✅ | CT-02 (`tests/Tenancy/HubDoNegocioTest.php`) |
| 4 | cartões agrupados pelo grupo de navegação, na ordem do painel | igual | ✅ | CT-05 assere o grupo "IA" no cartão de Execuções de IA |
| 5 | cartão com fundo, canto arredondado, anel de 1 px e sombra no hover | igual — **mas o CSS foi ~45 regras, não "as que faltarem"** | ⚠️ | CT-B01 + screenshot `hub-infraestrutura`; ver Notas de Implementação no `03` |
| 6 | cartão de destino não autorizado ausente para o `panel_user` | igual | ✅ | CT-02 |
| 7 | os três hubs no topo da barra lateral, fora dos grupos | igual (`$navigationSort = -10`, sem grupo) | ✅ | conferência visual |
| 8 | a busca do `/infra` e do `/admin` esconde os cartões que não casam (conferência manual — CT-B cortado) | **pendente de conferência manual** | ⬜ | — |
