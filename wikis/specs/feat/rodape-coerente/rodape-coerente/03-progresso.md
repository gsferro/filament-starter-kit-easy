# Progresso — Rodapé coerente

## 1. `App\Support\AssinaturaDoRodape` — o ponto único

- [x] Classe criada com `partes(bool $comVersao): array` — `app/Support/AssinaturaDoRodape.php:partes:50`, 2026-09-22
- [x] Composição na ordem `© {ano} {Nome}` · `v{versão}` · `kit {versão}` — medido por sonda: visitante `© 2026 Acme`; autenticado `© 2026 Acme · v1.2.3 · kit 0.38.2`, 2026-09-22
- [x] Cada parte entra só quando `filled()` — medido nas duas fronteiras: nome `'   '` não emite `©` sozinho; versão `'0'` sobrevive como `v0` (`empty('0')` é `true` em PHP), 2026-09-22
- [x] A classe **não** decide a audiência (ADR-02) — assinatura `partes(bool $comVersao)`, sem `filament()` no corpo
- [x] `final class`, como `ConfiguracaoDoLogin` e `CorPrimaria` — achado RD-08 do 6.5, 2026-09-22

## 2. A blade da assinatura

- [x] `git mv` para `assinatura-do-rodape.blade.php` — 2026-09-22
- [x] Referência atualizada em `ConfiguraFilamentGlobal:configuraVersaoNoRodape:129`, e mais 4 em testes e CSS — `grep -rn versao-do-kit` volta só a menção histórica no docblock da própria blade
- [x] A guarda se estreita: envolve só a versão — medido, a versão **não aparece** em nenhuma das 5 superfícies anônimas
- [x] Saída **escapada** (ADR-05) — `{{ implode(…) }}`; `[CT-19]` afirma a forma escapada e a ausência da crua
- [x] `<footer>` em vez de `<div>` — achado QA-09 do step 8: como `<div>` o axe-core acusava `region` em tela pública. Medido: `tag=footer`, filho direto de `body`, `role=contentinfo` implícito, 2026-09-23
- [x] Docblock reescrito com o que mudou e por quê

## 3. O recado do login muda de hook

- [x] Sai de `AUTH_LOGIN_FORM_AFTER`, entra em `FOOTER` com `scopes:` — `KitServiceProvider:configureTelaDeLogin:706`
- [x] O escopo lista **as duas** classes — **provado por mutação**: com só `TelaLogin::class`, `/login` perde o recado em silêncio, 2026-09-22
- [x] Os botões sociais **continuam** em `AUTH_LOGIN_FORM_AFTER`
- [x] **Regra de CSS** para o rodapé caber na dobra — não estava no plano; nasceu do Blocker RD-01 do 6.5. Ver `## Notas de Implementação`

## 4. O prefixo `v` no campo

- [x] `->prefix('v')` em `versao_do_sistema` — `[CT-13]` afirma `getPrefixLabel()==='v'` e um só rótulo "v" no formulário
- [x] `helperText` de `nome_da_aplicacao` corrigido — ele subdeclarava: o nome passa a ser publicado em página anônima
- [x] `helperText` de `versao_do_sistema` avisa do afixo — achado RD-04 do 6.5, **sem Markdown**: o Filament escapa com `e()` e o admin leria os asteriscos (QA-05), 2026-09-23

## 5. Documentação e CHANGELOG

- [x] `docs/pt/` e `docs/en/` — a seção do rodapé reescrita com a tabela de quem vê o quê, o ano corrente, o afixo `v` e o alcance do recado
- [x] `CHANGELOG.md` em `[Unreleased]` — **sem tag** (RQ-09)
- [ ] **Captura de arte NÃO refeita** — `art/login.png` e `art/panel-admin.png` mostram a tela
      **sem** a assinatura, que a entrega passou a renderizar. **Débito explícito**, não
      esquecimento: refazer as capturas exige `composer art`, que roda a suíte de browser inteira
      e regrava binários. Achado QA-18 do ciclo 2 — o item estava no `01` e **não aparecia aqui**,
      nem fechado nem adiado
- [x] Contadores dos READMEs — arquivos 152→**153**, total 178→**180**, specs 67→**68**, casos 2.226→**2.872** e asserções 7.428→**11.137**, redatados para 2026-09-23

## Testes

- [x] `04-casos-de-teste.md` derivado — 21 cenários, 7 regras, 57 mutantes, 4 lacunas, após 3 rodadas adversariais
- [x] Casos escritos conforme o `04` — `tests/Kit/RodapeCoerenteTest.php` (21 `it()`, 67 com dataset) e 3 casos reescritos em `VersaoNoRodapeTest.php`. **113 passaram, 1.783 asserções**, 2026-09-23
- [x] `tests/Browser/RodapeNaDobraTest.php` — **não estava no plano, e a própria entrega provou que precisava**. **6 passaram, 35 asserções**, 2026-09-23

## Verificação Final

- [x] `vendor/bin/pint --test --format agent` — **passed na árvore inteira**, 2026-09-23. Corrigiu de passagem uma violação que **eu** introduzi na `v0.38.2` (`ConfiguracoesDoKitTelaTest.php`) e que deixava o check `qualidade` do CI **vermelho na `main`**
- [x] `vendor/bin/filacheck` — **17/17**, 2026-09-23
- [x] `vendor/bin/pest tests/Kit/RodapeCoerenteTest.php tests/Kit/VersaoNoRodapeTest.php` — **113/113**, 2026-09-23
- [x] `vendor/bin/pest tests/Browser/RodapeNaDobraTest.php` — **6/6, 35 asserções**, 2026-09-23
- [x] `vendor/bin/phpstan analyse` — level 7, **0 erros**, 2026-09-23
- [x] `/code-review high main...HEAD` + passe de eixos (step 6.5) — **8 achados: 1 Blocker, 3 Major, 4 Minor**, todos fechados
- [x] `feature-quality-gate` (step 8) — ciclo 1 **REPROVADO → especificação**; 10 achados fechados. Ver `## Quality Gate

| Ciclo | Veredito | Blocker | Major | Minor | Cosmético | Data |
|---|---|---|---|---|---|---|
| 1 | REPROVADO → especificação | 0 | 4 | 5 | 1 | 2026-09-23 |
| 2 | REPROVADO → especificação | 0 | 5 novos + 1 carregado | 6 novos + 2 | 2 | 2026-09-23 |

**O veredito dos dois ciclos é o mesmo, e é preciso**: *"o código está correto e medido, o que
está errado é o que a wiki afirma sobre ele."* Nenhum dos 23 achados foi de comportamento do
produto.

### Ciclo 2 — 11 achados novos, 10 nascidos das correções do ciclo 1

| # | Achado | Fechado por |
|---|---|---|
| QA-11 | `group('kit')` em arquivo de browser faz `--parallel --group=kit` selecionar navegador | `group('browser-kit')` |
| QA-12 | a **regra de CSS**, que é o coração da correção do Blocker, sem passo no `01` nem ADR | passo 6 no `01`, **ADR-07** |
| QA-13 | `[CT-B01]`/`[CT-B02]` só no teste; o `04` ainda dizia que não existiam e contava 4 sem matador | `## Gate de CT-B` reescrito; 4 → **2** |
| QA-14 | *"5/5 ok, 3 ERRO"* falso nas três partes | **7/7 ok, 0 ERRO**, com o comando colado |
| QA-15 | `01` ainda dizia *"nada que só o navegador prove"*, falseado pela própria entrega | riscado e reescrito |
| QA-16 | a ADR-03 ficou com a citação velha que o ciclo 1 corrigiu nos outros três arquivos | `:121` → `:129` |
| QA-17 | o **recado** continua fora de landmark | ver lacuna abaixo |
| QA-18 | captura de arte não refeita, e o item sumiu do `03` | **débito explícito**, registrado |
| QA-19 | 12 linhas de `.gitignore` sem `RQ`, passo ou ADR | **ADR-08** |
| QA-20 | contagem de asserções errando por um | 11.137, medida |
| QA-21 | a guarda de CSS sem controle positivo do detector | ver lacuna abaixo |
| QA-22 | a correção de QA-09 sem oráculo que fixe a tag | ver lacuna abaixo |
| QA-23 | o `06` do ciclo 1 não existe no disco | `06-relatorio-qa.md` gravado |

**E os três que eu tinha declarado como lacuna irredutível no ciclo 1 — QA-01, QA-06 e QA-07 —
foram fechados**, porque o gate estava certo ao chamá-los de racionalização:

- **QA-01**: a pergunta foi para `00 ## Ambiguidades`. O gate apontou que **o oráculo é o `00`**;
  registrá-la só no `03` deixava quem lê o requisito sem ver que ela existe
- **QA-06**: uma linha — `expect(rodapeDe($html))->not->toBe('')` antes de cada ausência
- **QA-07**: um helper de seis linhas, `recadoDoRodape()`, espelhando `assinaturaDoRodape()`

**E o QA-07 cobrou o preço na hora**: o helper novo **falhava aberto pelo mesmo motivo** que o
`rodapeDe()` que ele veio substituir — a classe `fi-login-rodape` aparece mais de uma vez no
documento, e o `preg_match` simples casava a primeira, que não é a faixa renderizada. Quem o pegou
foram os controles positivos do QA-06, vermelhos na hora. Corrigido com `preg_match_all` e a
primeira ocorrência não-vazia.

### Lacunas que ficam declaradas

- **QA-17** — o recado é irmão do `<footer>`, não filho: são dois callbacks independentes do mesmo
  hook, e não há como um envolver o outro sem mover a lógica de escopo para dentro da blade.
  Fechar exigiria um segundo landmark (`landmark-no-duplicate-contentinfo`) ou reunir os dois num
  hook só, o que desfaria a ADR-01. **Declarado, com o custo nomeado**
- **QA-21** — a guarda não tem controle positivo do **detector**: se um `composer update` remover
  o `min-height: 100vh` do vendor, a regra do kit vira inócua e `[CT-B01]` fica verde. O padrão
  que a rule pede (`OrdemDasCascadeLayersTest`) é o candidato
- **QA-22** — nenhum caso afirma a **tag** `<footer>`; reverter para `<div>` deixa 113/113 verdes
- **M38** e **M51** — já declarados no `04`

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 3 | `planning-filament` (Blueprint) | o da sessão | — | V1–V7 | **2 reprovadas** (V3, V5) — só o step 8 as pegou |
| 2 | 4 | derivação do `04` | opus | o `01` inteiro | 21 cenários, 57 mutantes | estrutura conferida; contagem reconciliada por `grep -cE` |
| 3 | 4 | revisão adversarial (dentro do #2) | opus | só `00` + `04` | 3 rodadas | derrubou 3 dos 4 eixos que a sessão pediu |
| 4 | impl. | `fw-executor-ct` | sonnet | `01`, `02` | 21 casos novos + 3 reescritos | **travou uma vez**; a auditoria da sessão declarou ausente o que estava noutro arquivo |
| 5 | 6.5 | `fw-revisor-diff` | opus | `01`, `03` | **1 Blocker, 3 Major, 4 Minor** | Blocker reproduzido em navegador antes de aceitar |
| 6 | 8 | `fw-qa-gate` | opus | a conversa | **REPROVADO**, 10 achados | 4 alegações amostradas, todas confirmadas |

## Notas de Implementação

### O Blocker do 6.5 nasceu de eu medir o layout errado

A verificação **V5** do plano mediu `simple.blade.php` do Filament. **8 das 10 páginas de
`app/Filament/Pages/Auth/` redeclaram `$layout`** para o do `filament-auth-designer`, que não tem
`<main>` e fixa `.fi-auth-layout` em `min-height: 100vh`, emitindo o `FOOTER` **depois** de
fechá-lo.

Consequência medida em navegador, viewport de 1117px:

| | antes | depois |
|---|---|---|
| assinatura | y=**1122** (fora) | y=**1035** |
| recado | y=**1186** (fora) | y=**1099** |

E era **regressão**: o recado vivia dentro do cartão do formulário.

**A lição**: `grep` num vendor só responde sobre aquele vendor. A V3 dizia *"exatamente dois
layouts"* porque varreu `vendor/filament/` — são três.

### O `05` não existia, e a entrega provou que precisava

O `01` declarou *"nada que só o navegador prove"*. O Blocker acima é exatamente o contrário, e a
correção — uma regra de CSS — entrou **sem teste**, o que o step 8 pegou (QA-04).

`[CT-B01]` compara `bottom` com `innerHeight` em 5 rotas; `[CT-B02]` mede o estilo computado.
**Provados por mutação**, e o percurso da prova vale registro: a **primeira** mutação removeu a
regra só de `resources/css/filament/kit.css` e o teste ficou **verde** — porque o Filament serve a
cópia publicada em `public/css/kit/`. Mutando as duas, os 5 casos ficam vermelhos. Foi a mesma
classe de erro do Blocker: medir a coisa errada.

### `[CT-22]` é o antídoto da regra que a própria feature criou

O ano corrente obriga todo cenário a congelar o tempo, senão a suíte quebra sozinha em 1º de
janeiro. Mas congelar em 2026 é o que **cega** o conjunto para um `'© 2026 '` cravado na blade.
`[CT-22]` atravessa a virada e tem uma linha de fuso: com o app em `America/Sao_Paulo`,
`2027-01-01 02:30 UTC` ainda é 31/12/2026 lá.

### O que a revisão adversarial derrubou

Dos quatro eixos que a sessão mandou atacar, **três foram falseados**: a guarda escrita por
**rota** passava em tudo (e vazava em recuperação de senha); faltava o espelho do escopo (escopar
na mãe **e** na filha duplica); e assinatura e recado concatenados no **mesmo nó** passariam em
"antes". Mais três defeitos do próprio conjunto, incluindo dez cenários medindo o `rodapeDe()`,
que inclui o **snapshot do Livewire**.

### Sobre a `RQ-05` (QA-01)

O gate notou que ela foi marcada *"Assumido"* enquanto as vizinhas voltaram *"DECIDIDO com o
usuário"*, e que a assunção trocou **dados** por **composição**: as três chaves que compõem uma
faixa continuam em duas abas que não se mencionam.

**A observação procede.** A entrega atende a cláusula no sentido de ponto único de composição, e
**não** toca a superfície de edição. Fica registrado como o que é — uma leitura assumida, não
decidida — e a pergunta *"gerir melhor os dados inclui a tela de edição?"* vai ao usuário antes de
qualquer trabalho nesse sentido.

### Uma violação de `pint` que era minha, e estava na `main`

O check `qualidade` do CI estava **vermelho na `main`** desde a `v0.38.2`: uma violação de
espaçamento em `ConfiguracoesDoKitTelaTest.php`, introduzida pelo meu commit do autocomplete. O
`pint --dirty` daquele ciclo não pegou porque a edição veio depois dele. Corrigida aqui.

## Retrospectiva

- **Funcionou**: a cegueira dos gates. O 6.5 achou um Blocker que 113 asserções de HTML não viam,
  e o step 8 achou que a correção do Blocker não tinha teste. Nenhum dos dois era visível de dentro
- **Faltou no plano**: verificar API **por vendor**, e não por pacote. A V3 e a V5 erraram pela
  mesma causa, e uma delas custou o Blocker
- **Faltou no processo**: o step 7. Eu fui do 6.5 direto ao PR, e o `03` chegou ao quality gate com
  27 de 27 checkboxes abertos sobre uma feature commitada
