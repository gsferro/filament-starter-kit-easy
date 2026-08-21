# Casos de Teste de Browser — Hub de cards fora do padrão da instalação

> Requisito: `00-requisito.md` · Casos de backend: `04-casos-de-teste.md`
> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor, in-process.
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`) — **em série, nunca
> `--parallel`**.

## Por que este arquivo existe — e por que ele tem um cenário só

O gate de CT-B pergunta o que **só o navegador prova**. Desta entrega, uma coisa: que a descrição
recém-acrescentada aos cartões **apareça desenhada** e caiba no cartão.

Não é preciosismo. É o risco central que ADR-02 da wiki ancestral mediu e registrou: o
`harvirsidhu/filament-cards` **não registra CSS nenhum**, o kit mantém à mão um subconjunto de
utilitárias em `resources/css/filament/cards.css`, e utilitária que falte no arquivo produz **HTML
byte a byte correto, sem estilo nenhum** — com `assertSee`, `assertOk` e todo teste de componente
verdes.

Conferi as três classes que o bloco de descrição da blade emite
(`vendor/harvirsidhu/filament-cards/resources/views/pages/cards-page.blade.php:373-381`) contra o
arquivo do kit, e as três já estão cobertas: `text-sm` em `cards.css:114`, `text-gray-500` em
`:120`, e `dark:text-gray-400` pela regra `.dark .kit-cards-page .text-gray-500` em `:141`. Isto
**reduz** o risco; não o elimina — a conferência foi de leitura, e o que ela não cobre é o efeito
composto: dezesseis cartões que antes tinham uma linha e agora têm três, num `grid-cols-3`.

Todo o resto que RQ-07 afirma é **texto no DOM**, e mora no `04`: CT-07 e CT-08 rodam por
componente Livewire, em milissegundos, sem Node.

## Pré-requisitos

- [ ] `npm run build` executado — sem `public/build/manifest.json` **toda** tela responde
      `ViteException` e o cenário falha por um motivo que não é o dele
- [ ] `php artisan view:cache` executado — com cache frio o primeiro cenário paga a compilação
      dentro do teto de 45 s e falha por tempo (`.ai/rules/testes-browser.md` mede: 50 s e vermelho
      contra 10,6 s e verde)
- [ ] `tests/Browser/Screenshots` no `.gitignore` — já está, `.gitignore:28`
- [ ] Autenticação por `$this->actingAs()` antes do `visit()`, nunca login pela tela
- [ ] `php artisan filament:assets` se o `cards.css` tiver sido tocado

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| grade do hub, com escopo do CSS do kit | `.kit-cards-page` | sim — vem de `getPageClasses()`; é o seletor que o CT-B01 já usa |
| cartão pesquisável | `a[data-search-text]` | sim — contrato do pacote quando `$searchable` é `true`; o CT-B01 já o usa |
| descrição de um cartão | texto visível da frase | **novo** — sem seletor próprio; a blade emite um `<p>` sem classe de identificação |

> O kit não tem `data-testid` (dívida registrada em `.ai/rules/testes-browser.md`). Para a
> descrição, o texto visível é o que existe — e serve, porque o oráculo aqui é justamente o texto
> aparecer.
>
> ⚠️ **Modo estrito do Playwright**: seletor que casa mais de um elemento é **erro**, não "o
> primeiro". Um `text=Backups` casaria o rótulo do cartão e o item da barra lateral. Por isso
> CT-B02 afirma sobre um trecho **da frase**, que existe num lugar só da página.

---

## CT-B02: a descrição aparece desenhada no cartão

**Por que browser e não Livewire**: a asserção é sobre **estilo e layout** — a frase renderizada
com a tipografia do kit, dentro do cartão, sem estourar a grade de três colunas. Componente
Livewire prova que o texto está no DOM (é o que CT-07 faz) e passa igual com o `<p>` sem estilo
nenhum ou transbordando o cartão.

**Segundo cenário do arquivo, não substituto do primeiro.** O CT-B01 da wiki ancestral
(`tests/Browser/HubDeCardsTest.php`) continua como está — ele prova que a **grade** aparece
pintada. Este prova o mesmo sobre a **descrição**.

```gherkin

# language: pt

Funcionalidade: O hub em cards fora do padrão da instalação

  Regra: cada cartão do hub de infraestrutura explica para que o destino serve

    Cenário: [CT-B02] a descrição é desenhada dentro do cartão, com o console limpo
      Dado um usuário que administra a instalação
      Quando ele abre o hub de infraestrutura no navegador
      Então a grade em cartões está presente
      E a página mostra "quando rodaram, tamanho e se o destino respondeu"
      E o console não registra erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | autenticar e aquecer o painel pelo kernel | no `beforeEach`: `$this->actingAs(...)` + `$this->get('/infra/hub-de-infraestrutura')` | — |
| 2 | abrir o hub | `visit('/infra/hub-de-infraestrutura')` | a grade de cartões |
| 3 | fixar a proporção da galeria | `->resize(1400, 875)` | — |
| 4 | provar que o CSS do kit está em vigor | `->assertPresent('.kit-cards-page')` | grade em colunas, não lista |
| 5 | provar que a frase chegou à tela | `->assertSee('quando rodaram, o tamanho e se o destino respondeu')` | a descrição sob o rótulo "Backups" |
| 6 | **produzir a imagem da documentação** | `->screenshot(fullPage: false, filename: 'infra-hub')` | `art/infra-hub.png` + thumb, pelo `composer art` |
| 7 | console limpo | `->assertNoJavaScriptErrors()` | — |

> **Este cenário é a captura de arte.** Não existe um segundo screenshot desta tela: o
> `->screenshot()` do CT-B01 foi removido, e o `composer art` passou a invocar este arquivo. Uma
> tela, uma captura, um nome — ver ADR-05, que foi **reescrita** por causa disto.

**Assertions**: nenhuma navegação, então não há `assertPathIs` a ordenar ·
`assertNoJavaScriptErrors()` e não `assertNoSmoke()`, porque a página renderiza blade de plugin de
terceiro · `.kit-cards-page` é a âncora de que o CSS escopado está valendo.

> **`assertSee` não prova que está legível.** Ele passa com texto cinza-claro sobre fundo
> cinza-claro, e `.ai/rules/testes-browser.md` é explícita: para defeito de cor não há saída barata
> — é screenshot e olhar. É por isso que o passo 5 existe e por que a
> `## Verificação Final` do PRD tem um item de **abrir a imagem**.
>
> **A captura de arte deixou de ser um cenário separado.** A primeira versão desta wiki previa um
> cenário na suíte de arte (`tests/BrowserTenancy/CapturaDeArteTest.php`) com o nome `infra-hub`,
> justamente para não colidir com o `hub-infraestrutura` do CT-B01. Na implementação, aquela captura
> saiu com a **barra lateral do painel errado** — o `beforeEach` da suíte de arte aquece `/app` e
> `/admin`, e o estado de painel atravessa o servidor in-process. Duas tentativas de corrigir
> falharam.
>
> Este cenário, que **não** atravessa painéis, renderiza correto. Então ele virou a captura: ganhou
> `resize(1400, 875)` e o nome `infra-hub`, e o `->screenshot()` do CT-B01 — que fotografava a mesma
> tela — saiu. Ver ADR-05 reescrita e "Notas de Implementação" do `03-progresso.md`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | a descrição é anexada e a classe `.kit-cards-page` sai de `getPageClasses()` — a grade inteira se desmancha | CT-B01 (existente) e **CT-B02** (passo 3) |
| MB2 | uma utilitária do bloco de descrição falta no `cards.css` — a frase sai sem tipografia | ⚠️ **matador parcial**: CT-B02 prova que o texto **chegou**, não que está estilizado. O oráculo real é o screenshot do passo 5, olhado por uma pessoa. Ver lacuna LB1 |
| MB3 | a frase é renderizada fora do `<a>` do cartão, abaixo da grade | **CT-B02** — na verdade não: `assertSee` acha o texto em qualquer lugar da página. Ver lacuna LB1 |
| MB4 | um erro de Alpine no campo de busca derruba a interatividade da página | **CT-B02** (passo 6) |

**LB1 — lacuna declarada.** MB2 e MB3 são defeitos de **posição e pintura**, e não existe assertion
barata para eles neste arnês. Tentado e recusado:

- `assertScreenshotMatches()` — o kit não tem baseline de screenshot versionado, e criar uma para
  uma tela de dezesseis cartões vindos de plugins produziria falso positivo a cada plugin novo;
- seletor de posição (`.kit-cards-page a p`) — resolveria MB3, mas o modo estrito do Playwright
  recusa seletor que casa dezesseis elementos, e restringi-lo ao cartão certo exigiria um
  `data-testid` que a blade do vendor não emite. **Isto é dívida de fato**: no dia em que o kit
  ganhar `data-testid`, MB3 vira assertion.

O que resta é o screenshot do passo 5 e o item de conferência visual da `## Verificação Final`.
Declarado, não escondido.

---

## Roteiro de Validação: Desenhado × Implementado

> Preenchido no step 7 da `feature-wiki`, rodando os CT-B e conferindo linha a linha a tabela
> `## Superfície de UI` do PRD contra a tela real. Divergência vai para "Desvios do Plano" no
> `03-progresso.md`.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | `/infra/hub-de-infraestrutura` — grade com descrição em cada cartão, busca por texto | 14 cartões visíveis para `master_global`, em 4 grupos (Observabilidade, IA, Trilhas, Sistema), cada um com a frase sob o rótulo; campo "Buscar destino..." presente | ✅ | `tests/Browser/Screenshots/hub-infraestrutura.png`, **aberta e olhada** |
| 2 | `/admin/hub-de-administracao` — 403 no default | 403, inclusive para `master_global` | ✅ | CT-01 |
| 3 | `/app/{tenant}/hub-do-negocio` — 403 no default | 403 para `panel_user` | ✅ | CT-01 em `tests/Tenancy` |
| 4 | barra lateral do `/admin` sem o item "Hub de administração" | item ausente | ✅ | CT-01 (última assertion) |
| 5 | as dezesseis frases legíveis, sem estourar o cartão | frases em duas linhas, centradas, dentro do cartão, sem transbordo na grade de 3 colunas | ✅ | conferência visual da captura de `tests/Browser` |
| 6 | a mesma tela publicada em `art/infra-hub.png` | publicada pelo próprio CT-B02 | ✅ | `composer art` |
| 7 | tema escuro | **não conferido** | ⚠️ | sem baseline de screenshot; declarado em LB1 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B para o 403 do hub de `/admin` | 403 é HTTP, e CT-01 o prova por `$this->get()`. Navegador não acrescenta nada — a tela de erro do `filament-sentinel` já tem cobertura própria no kit |
| CT-B para a barra lateral sem o item | texto ausente no DOM é componente/HTTP. A última assertion do CT-01 cobre por um centésimo do custo |
| CT-B da busca filtrando pela descrição | filtrar é Alpine do **vendor**, e **injetar** a frase no `data-search-text` também é (`cards-page.blade.php:264`). A wiki ancestral já cortou "busca client-side", e a auditoria Ponytail cortou o cenário de componente que restava (o antigo CT-09) pelo mesmo motivo: nenhum código do kit pode errar isso |
| CT-B em dark mode | `->inDarkMode()->assertSee(...)` prova que a tela abre sob `prefers-color-scheme: dark` e **nada** sobre legibilidade. Sem baseline de screenshot, o cenário seria decorativo. O tema escuro entra na conferência visual (linha 5 do roteiro), não como assertion |
| CT-B de acessibilidade da grade | `assertNoAccessibilityIssues()` na página inteira acusaria problemas de dezesseis blades de plugin de terceiro, nenhum acionável por esta entrega. Candidato a auditoria própria, não a esta wiki |
