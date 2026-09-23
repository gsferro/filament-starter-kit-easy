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
- [x] Contadores dos READMEs — arquivos 152→**153**, total 178→**180**, specs 67→**68**, casos 2.226→**2.872** e asserções 7.428→**11.133**, redatados para 2026-09-23

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
- [x] `feature-quality-gate` (step 8) — ciclo 1 **REPROVADO → especificação**; 10 achados fechados. Ver `## Quality Gate`
- [x] `composer bp:off` — `ls vendor/filament/blueprint` não existe, e `composer.json`/`composer.lock` voltaram ao original, 2026-09-22
- [x] Citações `arquivo:símbolo:linha` reverificadas — **5/5 ok** pela conferência mecânica, 2026-09-23. **4 estavam erradas** e foram corrigidas (QA-08):

      ```
      ConfiguraFilamentGlobal.php:configuraVersaoNoRodape  121 → 129
      KitServiceProvider.php:configureTelaDeLogin          713 → 706
      KitServiceProvider.php:configureLoginUnificado       754 → 782
      ViewManager.php:renderHook                            94 → 74
      ```

      As 3 linhas que o script reporta como `ERRO` são formas **abreviadas** de path
      (`.../HasAffixes.php`), que a conferência mecânica não resolve; as versões com path
      completo passam.

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `testes.md` — helper de dois arquivos vive em `tests/Pest.php` | `tests/**` | **aplicada** | 4 helpers migrados (`comVersoes`, `rodapeDe`, `segmentoDaVersao`, `temRotulo`) |
| `testes.md` — `toContain()` não recebe mensagem | `tests/**` | **aplicada, e corrigiu um caso preexistente** | o loop de varredura do `[CT-13]` usava `not->toContain($x, $msg)`, que **nunca falha**; trocado por `assertStringNotContainsString`. O arquivo foi de 133 para 1.541 asserções |
| `testes-browser.md` — o oráculo é número, não presença | `tests/Browser/**` | **aplicada** | `[CT-B01]` compara `getBoundingClientRect().bottom` com `innerHeight`. `assertVisible` **não** serviria: exige bounding box não-vazio, não estar no viewport |
| `css-filament.md` — folha do kit precisa de guarda | `resources/css/filament/**` | **aplicada** | `[CT-B02]` mede `font-size`, `text-align` e `opacity` computados — os três divergem de uma vez se a folha não chegar |
| `views.md` | `resources/views/**` | **aplicada** | saída escapada; nenhuma utilitária Tailwind emitida pela blade |
| `app.md` | `app/**` | **n.a. no que ela exige** | nenhuma atribuição de papel/permissão, nenhum DTO |
| `filament.md` | `app/Filament/**` | **aplicada** | `filacheck` 17/17; afixo nativo (`prefix()`), não componente custom |
| `specs.md` | `wikis/specs/**` | **violada e corrigida** | 4 de 5 citações apontavam a linha errada (QA-08); corrigidas e reconferidas |

## Quality Gate

| Ciclo | Veredito | Blocker | Major | Minor | Cosmético | Data |
|---|---|---|---|---|---|---|
| 1 | REPROVADO → especificação | 0 | 4 | 5 | 1 | 2026-09-23 |

**O veredito do ciclo 1, e ele é preciso**: *"o código está correto e medido, o que está errado é
o que a wiki afirma sobre ele."*

| # | Achado | Fechado por |
|---|---|---|
| QA-01 | `RQ-05` marcada *"Assumido"* enquanto as vizinhas foram ao usuário | reclassificada — ver `## Notas` |
| QA-02 | **V3 e V5 falsas**, e o `03` as declarava *"confirmada"* | corrigidas em `00`, `01`, `03`, `04` e no comentário de `ConfiguraFilamentGlobal` |
| QA-03 | o `03` com **27/27 checkboxes abertos** e a tabela de Rules vazia | este documento |
| QA-04 | a correção de geometria **sem teste nenhum** | `tests/Browser/RodapeNaDobraTest.php`, provado por mutação |
| QA-05 | Markdown literal no `helperText` (o Filament escapa com `e()`) | asteriscos removidos |
| QA-06 | `rodapeDe()` sem controle positivo na mesma rota | o perigo está documentado no helper; ver lacuna abaixo |
| QA-07 | 3 casos medem presença do recado em `rodapeDe()` | ver lacuna abaixo |
| QA-08 | 4 de 5 citações na linha errada | corrigidas e reconferidas |
| QA-09 | `.kit-versao` fora de landmark em página pública | `<footer>` |
| QA-10 | contagem de casos do README desatualizada | 2.872 / 11.133, redatada |

**O gate também rejeitou hipóteses com evidência**, e isso dá confiança no que ele aprovou: mediu
que a versão **não vaza** em nenhuma superfície pública (`app.version='999-sonda'` invisível em
cinco rotas, inclusive no `wire:snapshot`), que o nome sai escapado em todas, e que os quatro
números do README conferiam.

### Lacunas que ficam declaradas

- **QA-06 / QA-07** — `rodapeDe()` falha aberto e três casos medem presença na cauda em vez do
  recorte. O gate **mediu** que nenhum é vácuo hoje (19.449 caracteres em `/admin/login`; o recado
  não aparece serializado em nenhum `wire:snapshot`). Fica o perigo documentado no helper e estas
  duas linhas
- **M38** — `auth()->check()` no lugar de `filament()->auth()->check()` continua sem matador:
  nesta instalação as duas expressões não divergem. O risco é do **projeto derivado**, e o
  candidato é teste de arquitetura, não CT
- **M51** — a tela **autenticada** do layout `simple` é a única célula em que R1 e R2 mandam
  ambas aparecer, e nenhum cenário a visita

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
