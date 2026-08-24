# Casos de Teste de Browser — Login social com Google

> Runtime: `pest-plugin-browser` 5.0.1 (Playwright). O plugin sobe o próprio servidor.
> Comando: `composer test:browser` — ele embute `npm run build` **e** `view:cache`, que são os
> dois pré-requisitos duros medidos em `.ai/rules/testes-browser.md`.
> **Nunca `--parallel`** com browser (medido: derruba 4 dos 11 cenários).

## Gate — por que existe apenas UM cenário

A tabela `## Superfície de UI` do PRD tem duas linhas (o botão e o rodapé), e as duas são HTML
estático dentro de uma página Livewire. Presença, ausência, **ordem no DOM**, ícone, `href` e
escape de HTML se provam com `$this->get()` + `assertSee` / `assertSeeInOrder` / `assertDontSee` —
e é isso que CT-04, CT-14 e CT-15 do `04-casos-de-teste.md` fazem, em milissegundos e nos três
painéis.

Sobra exatamente uma afirmação que o HTML **não** prova, e é a razão de existir deste arquivo:

> `assertSee` fica **verde** com o botão presente no DOM e invisível.

Um erro de JavaScript em qualquer componente da tela de login, um `x-show` herdado do layout do
Auth Designer, ou uma folha de estilo que colapse o contêiner deixam o botão no HTML e fora da
tela. O botão é a única porta do login social: invisível, a feature está entregue e não existe.

**Perfil e teto**: a área "Disponibilidade" é perfil `padrão`, cujo teto é **1 happy path** de
CT-B. O teto é respeitado — não há estouro a justificar.

## Pré-requisitos

- [ ] `npm run build` — sem `public/build/manifest.json` **toda** tela responde `ViteException`
      e o cenário falha por um motivo que não é o dele
- [ ] `php artisan view:cache` — com cache frio o primeiro cenário que renderiza um painel paga a
      compilação **dentro** do timeout de 45 s. Os dois já estão embutidos em `composer test:browser`
- [ ] Aquecimento pelo kernel no `beforeEach` (`$this->get('/app/login')`), pelo mesmo motivo: o
      `view:cache` cobre as Blade do repositório, mas o primeiro render de um painel ainda paga a
      compilação dos componentes Livewire do Filament — e rodando **um arquivo só** ninguém pagou
      essa conta antes. Ver DT-06 em `tests/Browser/CabecalhoDoMenuDoUsuarioTest.php:33-45`
- [ ] `tests/Browser/Screenshots` já está fora do versionamento
- [ ] **Sem `actingAs`**: a tela de login é a única superfície desta feature que o visitante vê
      **sem** sessão. Autenticar antes de visitar redirecionaria para o painel

## Seletores

O kit não tem `data-testid` — é dívida conhecida e registrada em `.ai/rules/testes-browser.md`.
O que esta feature acrescenta:

| Elemento | Seletor | Já existe? |
|---|---|---|
| Botão de entrar com Google | `[aria-label="Entrar com Google"]` | **não** — o `aria-label` é escrito pela feature, no blade do botão, e existe tanto para o leitor de tela quanto para dar uma âncora estável |
| Rodapé da tela de login | `.fi-login-rodape` | **não** — classe escrita pela feature |
| Campo de senha (âncora de "abaixo do form") | `#form\.password` | sim — `id` gerado pelo Filament; o `.` precisa de escape em CSS |

**Nada de classe utilitária como âncora.** O `fi-btn` do botão é do Filament e casaria também o
botão "Entrar" do formulário; o modo estrito do Playwright trata seletor que casa mais de um
elemento como **erro**, não como "o primeiro".

---

## CT-B01: o botão de entrar com Google está visível e clicável na tela de login renderizada

**Por que browser e não Livewire**: a asserção é sobre **visibilidade renderizada**, que só existe
depois de o navegador aplicar CSS e executar o JavaScript da página. `assertVisible` não tem
equivalente em teste de componente, e `assertSee` — que é o que o `04` usa — passa com o elemento
escondido.

```gherkin
# language: pt

Funcionalidade: Botão de entrar com Google na tela de login renderizada

  Regra: o botão fica visível e clicável na tela de login de verdade

    Cenário: [CT-B01] o botão aparece visível abaixo do formulário, sem erro de JavaScript
      Dado que o login com Google está disponível
      E que o rodapé da tela de login tem o texto "Kit — todos os direitos reservados"
      Quando um visitante sem sessão abre a tela de login do painel /app
      Então o campo de senha está visível
      E o botão "Entrar com Google" está visível
      E o botão aponta para a rota de redirecionamento do Google
      E o rodapé está visível com o texto configurado
      E o console do navegador não acusa erro de JavaScript
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | ligar a feature | `config()->set([...])` no cenário — interruptor, as três credenciais e o rodapé | — |
| 2 | aquecer fora do cronômetro | `$this->get('/app/login')` | nada; o efeito é em disco |
| 3 | abrir a tela | `visit('/app/login')` | tela de login vestida pelo Auth Designer |
| 4 | fixar a âncora do formulário | `->assertVisible('#form\\.password')` | campo de senha na tela |
| 5 | a asserção da feature | `->assertVisible('[aria-label="Entrar com Google"]')` | botão na tela |
| 6 | o destino do botão | `->assertAttributeContains('[aria-label="Entrar com Google"]', 'href', '/auth/google/redirect')` | — |
| 7 | o rodapé | `->assertVisible('.fi-login-rodape')` e `->assertSeeIn('.fi-login-rodape', 'todos os direitos reservados')` | rodapé na base da tela |
| 8 | console | `->assertNoJavaScriptErrors()` | — |

**Assertions**:

- `assertNoJavaScriptErrors()` e **não** `assertNoSmoke()`: a tela de login é de plugin de
  terceiro (`caresome/filament-auth-designer`), e `assertNoSmoke()` deixaria a suíte vermelha por
  `console.log` alheio que ninguém vai corrigir. É a regra de `.ai/rules/testes-browser.md`.
- Console é **assertion de apoio**, nunca o oráculo: o cenário afirma sobre o **botão** e o
  **rodapé**, e é o `assertVisible` de cada um que o falsifica.
- **Nenhum `assertPathIs`** neste cenário, porque nenhuma ação navega. Se um dia houver clique,
  ele vem primeiro — invertido, o `assertSee` roda contra o snapshot da página anterior e falha
  com a ação tendo funcionado.
- **Nenhum `wait($segundos)`**: o plugin reexecuta cada assertion até o teto de 45 s. `waitForText`,
  `waitForSelector` e `waitUntil` **não existem** neste plugin.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | o botão é renderizado dentro de um contêiner que o CSS do Auth Designer colapsa (altura 0) | CT-B01 (passo 5) — o HTML tem o botão, `assertSee` do `04` fica verde |
| MB2 | o botão é renderizado antes de o layout carregar e um erro de JS aborta o render do trecho | CT-B01 (passos 5 e 8) |
| MB3 | o `href` do botão é montado com uma rota inexistente e vira `#` no HTML final | CT-B01 (passo 6) |
| MB4 | o rodapé é renderizado com cor e fundo iguais, ou dentro de um bloco escondido | CT-B01 (passo 7) — `assertVisible` pega o bloco escondido; **cor sobre cor é lacuna declarada**: `assertSee` não valida tema e para defeito de cor não há saída barata além de screenshot e olhar (`.ai/rules/testes-browser.md`) |
| MB5 | o SVG do ícone é inválido e o navegador descarta o nó, deixando só o texto | ⚠️ **sem matador** em CT-B01. Lacuna declarada: tentado `assertVisible` sobre um seletor do `<svg>`; recusado porque o seletor teria de ser uma classe inventada só para o teste, e o `04` já assere as quatro cores da marca no HTML (CT-04). Um SVG malformado é defeito de sintaxe que o `assertNoJavaScriptErrors` não vê e o Pint não checa — fica como débito |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| clicar no botão e afirmar que o navegador saiu da aplicação | o `redirect()` do provedor falso aponta para `https://socialite.fake/google/authorize` (`vendor/laravel/socialite/src/Testing/FakeProvider.php:61-64`), um domínio que **não resolve**: o clique produziria erro de navegação do Playwright, não uma asserção. Com o provedor real, seria uma chamada à internet dentro da suíte. O `href` (passo 6) prova o destino sem sair |
| o botão **não** aparece com o interruptor desligado, no navegador | mata o mesmo mutante que CT-01 do `04`, que é ~40× mais barato. Ausência no DOM não precisa de navegador |
| o botão em tema escuro | `assertSee` não valida tema, e `->inDarkMode()->assertVisible(...)` provaria que a tela abre sob `prefers-color-scheme: dark` — não que o botão é legível. Sem oráculo barato; o botão é `<x-filament::button>`, que já é vestido pelo Filament nos dois temas |
| acessibilidade da tela de login com o botão novo | a tela é de plugin de terceiro e já tinha achados próprios; um cenário aqui mediria a dívida do vendor e não a da feature. Fica como débito no `03-progresso.md` |
| os três painéis no navegador | CT-04 do `04` já cobre os três em HTTP, e a registração do render hook é **única** (ADR-05) — se aparece num painel renderizado, o mecanismo funciona. Repetir no navegador triplicaria o cenário mais caro para matar mutante nenhum |
| screenshot versionado do botão | `tests/Browser/Screenshots` recebe também os screenshots que o Pest grava quando um cenário FALHA, e `KitArte::IMAGENS` é uma lista declarada. Captura de arte é decisão de outra feature, não desta |

---

## Roteiro de Validação: Desenhado × Implementado

Cada linha é uma promessa da `## Superfície de UI` do PRD, conferida contra a tela real.
As seis são provadas em HTTP (suíte `Kit`); o navegador acrescenta **uma** coisa que o HTML não
prova — que o botão e o rodapé estão de fato **visíveis**.

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | botão "Entrar com Google" abaixo do formulário, nos três painéis | idem, via render hook `AUTH_LOGIN_FORM_AFTER` registrado **uma vez** sem escopo | ✅ | CT-04, `assertSeeInOrder(['form.password', 'Entrar com Google'])` nos três painéis |
| 2 | ícone do Google (SVG inline, quatro cores) no botão | idem; o `<span>` flex que envolvia o SVG **saiu** — `.fi-btn` já é `inline-grid grid-flow-col items-center gap-1.5` (`button.css:3`) | ✅ com desvio | CT-04, as quatro cores da marca asseridas uma a uma |
| 3 | rodapé de texto na base da tela de login | idem, `.fi-login-rodape`, saída escapada | ✅ | CT-14 (presença), CT-15 (escape) |
| 4 | botão ausente quando o interruptor está desligado | idem, e **a rota também cai** (404) — não só o botão | ✅ **acima do desenhado** | CT-01 linha 2, CT-02 linhas 1–2 |
| 5 | botão ausente quando falta uma credencial | idem, com `client_secret` **vazio** (não ausente) | ✅ | CT-01 linha 3, CT-02 linhas 3–4 |
| 6 | rodapé ausente quando não há texto | idem, e também com **só espaços** | ✅ | CT-14 linhas 2–3 |

**Visibilidade renderizada** (o que só o navegador prova): CT-B01 **verde** — 7 asserções em
10,5 s. `assertVisible` no campo de senha, no botão e no rodapé, `assertAttributeContains` no
`href` e `assertSeeIn` no texto do rodapé, mais o console limpo. Ou seja: o botão e o rodapé não
estão só no DOM, estão na tela.

A suíte de navegador inteira: **42 casos, 37 verdes, 5 pulados** (os de captura de arte, que
dependem de `KIT_ART=1`), **0 vermelhos** — o cenário novo não derrubou nenhum vizinho.

### Divergências para o `03-progresso.md`

Uma, e é subtrativa: o `<span>` de layout do ícone foi removido depois de a revisão de
over-engineering apontar que `.fi-btn` já o faz. Nada do que o PRD prometeu deixou de existir.
