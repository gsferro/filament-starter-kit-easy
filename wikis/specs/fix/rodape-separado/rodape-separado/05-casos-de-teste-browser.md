# Casos de Teste de Browser — Rodapé separado: o recado volta ao cartão do login e a assinatura fica

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Costura: `browser` — linha "Dobra, ordem visual e acessibilidade" de `## Costuras de Teste` do `04` (regra R5).
> Comando: `composer test:browser` (embute `npm run build` e `view:cache`, pré-requisitos duros) ou, para o arquivo, `vendor/bin/pest tests/Browser/RodapeNaDobraTest.php` **depois de aquecer as views pelo kernel** (`.ai/rules/testes-browser.md`, seção "`view:cache` não basta para arquivo isolado — aqueça pelo kernel"). **Nunca `--parallel`.**
> Os dois CT-B **substituem** `[CT-B01]` e `[CT-B03]` de `rodape-coerente`, que vivem no mesmo arquivo; o `[CT-B02]` dela não é tocado (ver `## Regressão em suíte existente` do `04`).
> Os mutantes (M19 a M23) estão na regra R5 do `04`; este arquivo não os repete, para o gate ser um só.

<!-- `--testsuite=Browser` só se o phpunit.xml declara a suíte Browser: o grupo do arquivo é `browser-kit`; conferir com grep -n '<testsuite name="Browser"' phpunit.xml antes de citar esse comando -->

## Pré-requisitos

- [ ] `npm run build` executado (sem `public/build/manifest.json` toda tela responde `ViteException`)
- [ ] `php artisan view:cache` aquecido pelo kernel antes de rodar o arquivo isolado
- [ ] a cópia publicada `public/css/kit/kit-correcoes.css` acompanha `resources/css/filament/kit.css` (o Filament serve a cópia publicada; a regra da dobra é matéria do CT-B01)
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] nenhuma autenticação: as telas de login e de recuperação de senha são do visitante

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| a assinatura | `.kit-versao` | sim — o `<footer>` do kit, usado pelo `[CT-B01]` da ancestral |
| o recado | `.fi-login-rodape` | sim — o `<aside>` do recado, usado pelo `[CT-B01]` da ancestral |
| o cartão do formulário | `.fi-auth-card` (modo cover) ou `.fi-auth-form-container` (modo sem cartão): o que envolve o `{{ $slot }}` | sim, no pacote (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:'fi-auth-card':51` e `vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:'fi-auth-form-container':56`); **qual dos dois o kit emite é medido no step 10** e fixado no teste. Os dois no mesmo `closest()` cobrem o par |
| o viewport | `innerHeight` | sim (JS) |

O kit não tem `data-testid` (dívida conhecida); as classes são as do kit e do pacote, como no `[CT-B01]` da ancestral (divergência declarada no `04`).

---

## CT-B01: a assinatura e o recado cabem na dobra, o recado está dentro do cartão e acima da assinatura

**Por que browser e não HTTP**: a dobra e a ordem **visual** são geometria (`getBoundingClientRect` contra `innerHeight`); o DOM sozinho não as garante (o CSS pode reordenar, e um cartão mais alto empurra a assinatura para fora do viewport sem mudar uma linha de HTML). A contenção no cartão é a única asserção que distingue "dentro do cartão" de "dentro do layout" (M20); o HTTP só vê "depois do formulário".

**substitui o `[CT-B01]` de `rodape-coerente`** (`tests/Browser/RodapeNaDobraTest.php:'[CT-B01]':37`): a asserção de ordem **inverte** (antes: a assinatura acima do recado; agora: o recado acima da assinatura), as mensagens de `recadoBottom` e de `temRecado` deixam de falar de "antes desta feature" e de "escopo do hook", e ganha a asserção de contenção no cartão. A medição de `assinaturaBottom <= vh` **fica**: é ela que detecta o risco R1 do plano.

```gherkin
# language: pt
Funcionalidade: O rodapé do login cabe na dobra e fica separado da assinatura

  Regra: o recado dentro do cartão não empurra a assinatura para fora da dobra e aparece acima dela

    # @premissa P-01 (posição como estava) e P-02 (a assinatura segue visível)
    Esquema do Cenário: [CT-B01] a assinatura e o recado cabem na dobra, o recado dentro do cartão e acima da assinatura
      Dado o recado do rodapé gravado como "Fale com o suporte"
      E o login unificado <unificado>
      Quando o visitante abre "<rota>" num navegador
      Então a assinatura existe no documento e o recado <existe>
      E a borda inferior da assinatura cabe no viewport
      E, havendo recado, a borda inferior do recado cabe no viewport e o recado está dentro do cartão
      E, havendo recado, o topo do recado fica acima do topo da assinatura

      Exemplos:
        | rota                          | unificado | existe       | # partição                                            |
        | /admin/login                  | desligado | existe       | login do admin                                        |
        | /app/login                    | desligado | existe       | login do app                                          |
        | /infra/login                  | desligado | existe       | login do infra                                        |
        | /login                        | ligado    | existe       | página única de login                                 |
        | /admin/password-reset/request | desligado | não existe   | recuperação de senha: a assinatura sozinha (controle) |
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | fixar o recado e o login unificado | `config(['kit.login.rodape' => 'Fale com o suporte']); ligarLoginUnificado($unificado);` (`tests/Pest.php:ligarLoginUnificado():508`) | — |
| 2 | abrir a rota e medir num único `script()` | `visit($rota)->script(...)` devolve `JSON.stringify` com `temAssinatura`, `temRecado`, `vh`, `assinaturaTop`, `assinaturaBottom`, `recadoTop`, `recadoBottom` e **`recadoNoCartao`** (`r.closest('.fi-auth-card, .fi-auth-form-container') !== null`) | — |
| 3 | controle positivo, primeiro | `expect($medida['temAssinatura'])->toBeTrue(...)` e `expect($medida['temRecado'])->toBe($esperaRecado, ...)` | a geometria abaixo não mede o vazio |
| 4 | a dobra | `expect($medida['assinaturaBottom'])->toBeLessThanOrEqual($medida['vh'], ...)` | a assinatura inteira cabe |
| 5 | com recado: a dobra, o cartão e a ordem | `recadoBottom <= vh`; `expect($medida['recadoNoCartao'])->toBeTrue(...)`; `expect($medida['recadoTop'])->toBeLessThan($medida['assinaturaTop'], 'o recado deveria aparecer ACIMA da assinatura, dentro do cartão')` | recado no cartão, assinatura abaixo |

**Assertions**: controle positivo primeiro · oráculo é **número** (geometria), não presença (`assertVisible` passa com o elemento fora do viewport; `.ai/rules/testes-browser.md`) · **uma única** mensagem de ordem · `assertNoJavaScriptErrors()` **não** é o oráculo.

**Falsificabilidade (medida no step 10, registrada no `03`)**:

1. contra o provider de `main`, sem o passo 1 (o recado no `FOOTER`): a ordem invertida fica **vermelha nas quatro telas de login** (a assinatura fica acima do recado) — mata M1;
2. com o recado emitido em um hook fora do cartão, mas dentro do `.fi-auth-layout` (por exemplo `CardAfter` do designer): `recadoNoCartao` fica falso — mata M20; **se o seletor do cartão estiver errado, o teste fica vermelho no estado correto**, e é por isso que o nome é medido antes de ser fixado;
3. cartão mais alto sem a regra de CSS da dobra: `assinaturaBottom` passa de `vh` — mata M19. Mutar **as duas** cópias do CSS (`resources/css/filament/kit.css` e `public/css/kit/kit-correcoes.css`), porque o Filament serve a cópia publicada.

---

## CT-B03: o recado dentro do cartão não acrescenta elemento sem landmark nem landmark duplicado

**Por que browser e não HTTP**: acessibilidade é o que o axe calcula sobre a árvore renderizada (landmarks implícitos por tag, nome acessível, aninhamento); a tag no HTML não prova a regra.

**substitui o `[CT-B03]` de `rodape-coerente`** (`tests/Browser/RodapeNaDobraTest.php:'[CT-B03]':153`): a constante `regras` do script do axe ganha `landmark-complementary-is-top-level` (a regra que um `<aside>` aninhado em outro landmark acusaria: o risco R2 do plano, que as três regras anteriores nunca mediram com o recado dentro do cartão), e o **M21** (`<aside>` → `<div>`) é medido **com o recado no cartão**.

```gherkin
# language: pt
  Regra: o recado e a assinatura não aparecem entre os elementos acusados pelo axe

    # @premissa P-05 (a tag do recado fica <aside>; o oráculo é a propriedade, não a tag)
    Esquema do Cenário: [CT-B03] o recado no cartão e a assinatura não são acusados pelas regras de landmark
      Dado o recado do rodapé gravado como "Fale com o **suporte**"
      E o login unificado <unificado>
      Quando o visitante abre "<rota>" num navegador e o axe varre a página nas regras `region`, `landmark-no-duplicate-contentinfo`, `landmark-unique` e `landmark-complementary-is-top-level`
      Então o axe acusou pelo menos um elemento, provando que a varredura rodou
      E os elementos acusados não incluem a assinatura
      E os elementos acusados não incluem o recado

      Exemplos:
        | rota                          | unificado | # partição                                                       |
        | /admin/login                  | desligado | login do admin: o recado dentro do cartão                        |
        | /login                        | ligado    | página única de login                                            |
        | /admin/password-reset/request | desligado | recuperação de senha: sem recado (o escopo do hook, não lacuna)  |
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | fixar o recado e o login unificado | `config(['kit.login.rodape' => 'Fale com o **suporte**']); ligarLoginUnificado($unificado);` | — |
| 2 | varrer com o axe só nas quatro regras e coletar os alvos | `visit($rota)->script(...)` com `axe.run(document, { runOnly: { type: 'rule', values: regras } })`, `regras` = as três do caso da ancestral **mais** `landmark-complementary-is-top-level` | lista de alvos acusados |
| 3 | controle positivo do detector, primeiro | `expect($acusados['total'])->toBeGreaterThan(0, ...)` — a tela já acusa elementos do **vendor** (`landmark-one-main`, `region` na mídia e nos campos); lista vazia é varredura que falhou (M23) | — |
| 4 | a propriedade | `assertStringNotContainsString('kit-versao', $lista, ...)` e `assertStringNotContainsString('fi-login-rodape', $lista, ...)` | nenhum elemento da feature entre os acusados |

**Assertions**: o oráculo é de **pertinência** (os elementos da feature não estão na lista), e não `assertNoAccessibilityIssues()` puro: o nível padrão é cego para `moderate`, e a tela já tem dois problemas do vendor. As **quatro** regras, e não só `region`: com só uma, o recado `<footer>` (M22) sobrevive.

**A bifurcação do passo 4 do plano (resolver e registrar no `03`, como Desvio do Plano quando a branch (b) for a que vale):**

| Medição com o recado no cartão | O que o CT-B03 faz | Efeito em P-05 / D1 |
|---|---|---|
| **(a)** o CT-B03 fica **vermelho** com o `<aside>` no cartão (a regra nova acusa o recado aninhado) | a tag volta a `<div>` na blade e **P-05 é revisada** no `00` (Adendo); o oráculo continua sendo a lista do axe | D1 e P-05 caem; Desvio do Plano |
| **(b)** o CT-B03 fica **verde** com o `<aside>` e **verde também com a `<div>`** (a mutação M21 sobrevive) | o oráculo ganha a asserção de contenção: o recado está contido num landmark — `closest('aside,[role],main,form')` não nulo — no lugar da ausência na lista de acusados, que não distingue as duas tags aqui | D1 e P-05 se confirmam |
| **(c)** o CT-B03 fica **verde** com o `<aside>` e **vermelho** com a `<div>` (M21 morre) | nada muda | D1 e P-05 se confirmam |

A branch (b) é a que o plano já pré-decidiu; a (a) e a (c) são as outras duas saídas possíveis da mesma medição. **Sem a medição de M21 com o recado no cartão, M21 não tem matador comprovado** (o matador (b) existe por construção, mas não foi executado).

**Falsificabilidade (medida no step 10, registrada no `03`)**: M22 (`<footer>` no recado) fica vermelho nas duas telas de login pelas regras de `contentinfo`; M23 (sem o axe) fica vermelho pelo controle positivo; M21 conforme a tabela acima.

---

## Roteiro de Validação: Desenhado × Implementado

(Preenchido no step 10, depois da implementação, pelo `fw-executor-ctb`.)

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | recado dentro do cartão, assinatura abaixo, as duas na dobra nas quatro telas de login | | | |
| 2 | o recado é `<aside>` e não é acusado pelas quatro regras de landmark | | | |
