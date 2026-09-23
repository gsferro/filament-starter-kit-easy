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
- [x] `<footer>` em vez de `<div>` — achado QA-09 do step 8: como `<div>` o axe-core acusava `region` em tela pública. Medido: `tag=footer`, filho direto de `body`, `role=contentinfo` implícito, 2026-09-23. **Reconferido no ciclo 4** contra o Filament 5.8.1: ele emite `<footer>` só **aninhado** e nenhum `contentinfo` em nível de `<body>`, então este é o único da página — e `[CT-B03]` passou a **medir** isso em vez de o ADR supor
- [x] Docblock reescrito com o que mudou e por quê

## 3. O recado do login muda de hook

- [x] Sai de `AUTH_LOGIN_FORM_AFTER`, entra em `FOOTER` com `scopes:` — `KitServiceProvider:configureTelaDeLogin:706`
- [x] O escopo lista **as duas** classes — **provado por mutação**: com só `TelaLogin::class`, `/login` perde o recado em silêncio, 2026-09-22
- [x] Os botões sociais **continuam** em `AUTH_LOGIN_FORM_AFTER`

## 4. O prefixo `v` no campo

- [x] `->prefix('v')` em `versao_do_sistema` — `[CT-13]` afirma `getPrefixLabel()==='v'` e um só rótulo "v" no formulário
- [x] `helperText` de `nome_da_aplicacao` corrigido — ele subdeclarava: o nome passa a ser publicado em página anônima
- [x] `helperText` de `versao_do_sistema` avisa do afixo — achado RD-04 do 6.5, **sem Markdown**: o Filament escapa com `e()` e o admin leria os asteriscos (QA-05), 2026-09-23

## 5. A regra de CSS que faz o rodapé caber na dobra

> Espelha o passo 5 do `01`. Existia só como checkbox na seção 3 até 2026-09-23 — resíduo do
> QA-12, que era exatamente isto: mudança de código sem passo próprio.

- [x] **Não estava no plano**: nasceu do Blocker RD-01 do step 6.5 — assinatura em `y=1122` e
      recado em `y=1186`, num viewport de 1117px, os dois **abaixo da dobra**, e o recado
      **regredindo** de visível para invisível
- [x] `body.fi-body:has(> .fi-auth-layout)` vira coluna e o layout cede a altura — ADR-07
- [x] Aplicada nas **duas** cópias: `resources/css/filament/kit.css` e a publicada
      `public/css/kit/kit-correcoes.css`. **O Filament serve a publicada** — mutar só uma dá
      verde falso
- [x] Guardada por `[CT-B01]` (geometria em 5 rotas) e `[CT-B02]` (estilo computado)
- [ ] **Débito, e está nomeado**: a guarda não tem **detector** do vendor — se o
      `min-height: 100vh` sumir do `filament-auth-designer`, a regra do kit vira inócua e o
      `[CT-B01]` fica verde. Achado QA-29, reclassificado de *"lacuna"* para **violação** de
      `.ai/rules/css-filament.md`. Ver `## Notas de Implementação`


## 6. Landmarks das duas linhas do rodapé

> Acrescentado em 2026-09-23 — achados QA-17, QA-39 e QA-40 do quality gate. Espelha o passo 6
> do `01`, que também nasceu depois da implementação.

- [x] O recado passa de `<div>` para **`<aside>`** — `resources/views/filament/auth/rodape-login.blade.php`
- [x] As **três** formas medidas com `axe.run()`, e não deduzidas (ADR-09): `<div>` acusa o recado
      por `region`; `<footer>` limpa o `region` e dispara `landmark-no-duplicate-contentinfo` **e**
      `landmark-unique` na **assinatura**; `<aside>` sai limpo nas três
- [x] **QA-17 era defeito, não lacuna** — `[CT-B03]`, escrito antes da mudança, ficou **vermelho
      em 2 das 3 rotas**. A justificativa do ciclo 1 estava certa sobre `<footer>` e nunca tinha
      sido medida (QA-35)
- [x] Conferido contra o **Filament 5.8.1** com a skill `filament-development`: `<footer>` só
      aninhado (`fi-section-footer`, `fi-empty-state-footer`), **zero** `contentinfo` em nível de
      `<body>`, **zero** `<aside>` — nenhuma das duas tags colide
- [x] Mutantes **M58, M59 e M62 medidos mortos**; com `runOnly: ['region']` — a primeira redação
      de `[CT-B03]` — o M62 sobrevivia
- [x] A classe `fi-login-rodape` usa o prefixo reservado do Filament e **não tem CSS nenhum**
      apontando para ela — registrado em `## Débitos Declarados`, não renomeada aqui

## 7. Documentação e CHANGELOG

- [x] `docs/pt/` e `docs/en/` — a seção do rodapé reescrita com a tabela de quem vê o quê, o ano corrente, o afixo `v` e o alcance do recado
- [x] `CHANGELOG.md` em `[Unreleased]` — **sem tag** (RQ-09)
- [ ] **Captura de arte NÃO refeita** — `art/login.png` e `art/panel-admin.png` mostram a tela
      **sem** a assinatura, que a entrega passou a renderizar. **Débito explícito**, não
      esquecimento: refazer as capturas exige `composer art`, que roda a suíte de browser inteira
      e regrava binários. Achado QA-18 do ciclo 2 — o item estava no `01` e **não aparecia aqui**,
      nem fechado nem adiado
- [x] Contadores dos READMEs — arquivos 152→**153**, total 178→**180**, specs 67→**68**, casos 2.226→**2.872** e asserções 7.428→**11.146**, redatados para 2026-09-23

## Testes

- [x] `04-casos-de-teste.md` derivado — **26 cenários** (CT-01…CT-22 com CT-06 fundido em CT-04, mais CT-24, CT-25 e CT-B01…CT-B03), **9 regras** (R1…R9), **67 mutantes**, **2 lacunas** (M38, M51), após 3 rodadas adversariais e 4 ciclos de quality gate

  *(o número de lacunas dizia 4 aqui e 2 no `04` — achado QA-53. O valor é **2**, conferido contra a seção `## Mutantes sem matador`: M33, M35 e M44 foram fechados nos ciclos anteriores e continuavam contados.)*
- [x] Casos escritos conforme o `04` — `tests/Kit/RodapeCoerenteTest.php` (**23 `it()`**, 69 com dataset) e 3 casos reescritos em `VersaoNoRodapeTest.php`. **115 passaram, 1.801 asserções**, 2026-09-23
- [x] `tests/Browser/RodapeNaDobraTest.php` — **não estava no plano, e a própria entrega provou que precisava**. **9 passaram, 44 asserções**, 2026-09-23

  *(dizia 6/35 aqui e 9/41 na `## Verificação Final` — achado QA-42. Os dois estavam velhos: o `[CT-B03]` cresceu de 1 para 3 rotas e passou a varrer 3 regras de landmark.)*

## Verificação Final

- [x] `vendor/bin/pint --test --format agent` — **passed na árvore inteira**, 2026-09-23. Corrigiu de passagem uma violação que **eu** introduzi na `v0.38.2` (`ConfiguracoesDoKitTelaTest.php`) e que deixava o check `qualidade` do CI **vermelho na `main`**
- [x] `vendor/bin/filacheck` — **17/17**, 2026-09-23
- [x] `vendor/bin/pest tests/Kit/RodapeCoerenteTest.php tests/Kit/VersaoNoRodapeTest.php` — **115/115, 1.801 asserções**, 2026-09-23
- [x] `vendor/bin/pest tests/Browser/RodapeNaDobraTest.php` — **9/9, 44 asserções**, 2026-09-23
- [x] `vendor/bin/phpstan analyse` — level 7, **0 erros**, 2026-09-23
- [x] `/code-review high main...HEAD` + passe de eixos (step 6.5) — **8 achados: 1 Blocker, 3 Major, 4 Minor**, todos fechados
- [x] `feature-quality-gate` (step 8) — **quatro ciclos, os quatro REPROVADO → especificação**; **53 achados**, dos quais **52 não eram de comportamento do produto**. O 53º é o QA-17, achado no ciclo 4: o recado fora de landmark, classificado como lacuna aceitável desde o ciclo 1 com uma justificativa que nunca tinha sido medida (QA-35). Ver `06-relatorio-qa.md`. Ver a seção de Quality Gate abaixo e o `06-relatorio-qa.md`
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel` — **regressão obrigatória** (o `01` declara *toca infra compartilhada*): **2.874 testes, 2.871 passaram, 11.152 asserções, 3 pulados, 0 falhas**, 2026-09-23 (ciclo 4)

  Os **+2 testes e +6 asserções** sobre a medição do ciclo 3 (2.872/2.869/11.146) são exatamente `[CT-24]` e `[CT-25]`, a sentinela da regra R9 e o controle positivo dela. A conferência importa: um delta diferente deste seria mudança não contabilizada.
- [x] `composer bp:off` — `filament/blueprint` ausente de `composer.json` e de `vendor/`; as três skills pagas ficam no disco e **gitignoradas** (ADR-08), 2026-09-23

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `testes.md` — helper de dois arquivos vive em `tests/Pest.php` | `tests/**` | **aplicada** | 4 helpers migrados; `HelpersDeTesteTest` verde |
| `testes.md` — `toContain()` não recebe mensagem | `tests/**` | **aplicada, e corrigiu um caso preexistente** | o loop do `[CT-13]` usava `not->toContain($x, $msg)`, que nunca falha; o arquivo foi de 133 para 1.541 asserções |
| `testes-browser.md` — o oráculo é número, não presença | `tests/Browser/**` | **aplicada** | `[CT-B01]` compara `bottom` com `innerHeight` |
| `testes-browser.md` — nunca `--parallel` com browser | `tests/Browser/**` | **violada e corrigida** | `group('kit')` em arquivo de navegador fazia `--parallel --group=kit` selecioná-lo. Os **dois** casos agora são `browser-kit` — QA-11 fechou um, QA-24 pegou o outro |
| `css-filament.md` — folha do kit precisa de guarda que leia o vendor | `resources/css/filament/**` | **VIOLADA** | `[CT-B01]` mede o **efeito**, não o **detector**: se o vendor remover `min-height: 100vh`, a regra vira inócua e o caso fica verde. Achado QA-29 — **débito declarado**, ver Quality Gate |
| `views.md` | `resources/views/**` | **aplicada** | saída escapada; nenhuma utilitária Tailwind emitida pela blade |
| `app.md` | `app/**` | **n.a. no que ela exige** | nenhuma atribuição de papel/permissão, nenhum DTO |
| `filament.md` | `app/Filament/**` | **aplicada** | `filacheck` 17/17; afixo nativo, não componente custom |
| `providers.md` | `app/Providers/**` | **aplicada** | hook registrado uma vez, sem `scopes:` para a assinatura e com escopo para o recado |
| `pages.md` | `app/Filament/Admin/Pages/**` | **n.a.** | nenhuma página nova; só um afixo num campo existente |
| `specs.md` | `wikis/specs/**` | **violada e corrigida** | 5 citações erradas ao todo, nos três ciclos; hoje **21/21 ok** pela conferência mecânica. O número dizia 7/7 — achado QA-43 — porque a conferência anterior só alcançava as citações de caminho **completo**: quatro estavam abreviadas com `...` e eram invisíveis para ela. As quatro foram expandidas, e o verificador passou a cobrir o conjunto inteiro |


## Quality Gate

**Teto de 3 ciclos ESTOURADO — a skill manda escalar ao usuário, e isto está escalado.**

| Ciclo | Veredito | Blocker | Major | Minor | Cosmético | Data |
|---|---|---|---|---|---|---|
| 1 | REPROVADO → especificação | 0 | 4 | 5 | 1 | 2026-09-23 |
| 2 | REPROVADO → especificação | 0 | 5 novos + 1 carregado | 6 novos + 2 | 2 | 2026-09-23 |
| 3 | REPROVADO → especificação · **teto estourado** | 0 | 8 | 5 | 2 | 2026-09-23 |
| 4 | REPROVADO → especificação · **acima do teto, autorizado** | 0 | 6 | 7 | 2 | 2026-09-23 |

### Ciclo 4 — 15 achados, e o primeiro defeito de **produto** em quatro ciclos

Rodou **acima do teto**, por autorização explícita do usuário (*"roda o ciclo 4"*) — que é o que a
`## Convergência do Loop` manda fazer quando o teto estoura: escalar, e não decidir sozinho.

**6 Major, 7 Minor, 2 Cosméticos.** O detalhamento está no `06-relatorio-qa.md`. O que importa
registrar aqui são as duas coisas que este ciclo mudou de natureza:

**1. QA-17 não era lacuna — era defeito.** O ciclo 1 aceitou o recado fora de landmark porque
envolvê-lo num `<footer>` duplicaria o `contentinfo`. O ciclo 3 (QA-35) já tinha visto o problema
da frase: **o axe nunca tinha rodado**. Quando `[CT-B03]` passou a medir, ficou vermelho em 2 das
3 rotas. As três formas foram então medidas — `<div>`, `<footer>`, `<aside>` — e `<aside>`
(landmark `complementary`) sai limpo nas três regras. **ADR-09.**

> A dedução do ciclo 1 estava certa **sobre `<footer>`**. O que faltava não era rigor: era a
> **terceira opção**. Os dois lados discutiram `<div>` contra `<footer>` e ninguém perguntou se
> havia outro landmark.

**2. A primeira correção que impede o PRÓXIMO caso da classe.** QA-44 e QA-45 eram duas cópias do
extrator que **sobreviveram à unificação do QA-31**, no ciclo 3. Quando o ADR-09 trocou a tag, as
duas quebraram junto com a própria constante e **8 casos caíram de uma vez** — e os que afirmavam
ausência teriam ficado **verdes sobre o recorte vazio**, não fosse o controle positivo que o QA-06
obrigou a escrever no ciclo 2.

A correção foi por classe: extrator agnóstico de tag (`(?P<tag>[a-z]+)` … `</(?P=tag)>`) e a
**sentinela `[CT-24]`**, que reprova qualquer `preg_*` novo citando os marcadores do rodapé, com
dois controles positivos da varredura e um do reconhecedor (`[CT-25]`). Regra **R9** no `04`.

> É a resposta direta ao diagnóstico que o gate repetiu por três ciclos. As correções anteriores
> fechavam a ocorrência; esta **recusa a próxima**.


### Ciclo 3 — 15 achados, 11 nascidos das correções do ciclo 2

**O diagnóstico do ciclo 3 é sobre o orquestrador, não sobre a wiki**, e vale citá-lo:

> *"As correções fecham o caso citado e deixam a classe aberta."*
> QA-11 → QA-24 · QA-06 → QA-30 · QA-07 → QA-31 · QA-13 → QA-26 · QA-12 → QA-38.
> Cinco dos oito Major são o vizinho do achado que o ciclo anterior fechou.

Está certo, e a prova apareceu no próprio ciclo: ao reescrever a seção de Quality Gate eu casei
uma **referência** (`Ver \`## Quality Gate\``) dentro de um checkbox em vez do cabeçalho, e o
corte **levou junto a tabela de Conformidade com Rules inteira** — que foi o QA-28 e que deixou o
L4 do gate cego por dois ciclos. É o mesmo erro que eu já tinha cometido no `04` horas antes.

**Por isso o ciclo 3 foi corrigido por VARREDURA, não por ponto**, e cada classe foi zerada com
o `grep` de confirmação colado:

| Classe | Antes | Depois |
|---|---|---|
| `group('kit')` em `tests/Browser/` | 1 | **0** |
| *"8 das 10 páginas"* | 4 | **0** |
| *"113 asserções"* (são casos) | 4 | **0** |
| ausências sem controle positivo | 4 | **0** — de 2 para **6** controles |
| extratores com regex própria | 3 cópias | **1** constante cada |

| # | Achado | Fechado por |
|---|---|---|
| QA-24 | `[CT-B01]` ainda em `group('kit')` | varredura em `tests/Browser/` |
| QA-25 | *"8 das 10"* é falso — são 9 arquivos | varredura nos quatro, inclusive o comentário de produção |
| QA-26 | o `04` se contradizia sobre mutantes sem matador | M33 e M35 riscados na tabela |
| QA-27 | 1.783 asserções, medida 1.786 (hoje **1.801**) | varredura |
| QA-28 | **a tabela de Rules não existia** | restaurada, com 11 globs |
| QA-29 | a regra de CSS viola `css-filament.md` | **débito declarado** na tabela de Rules |
| QA-30 | 4 de 6 ausências sem controle positivo | os quatro |
| QA-31 | `recadoDoRodape()` empurrou o problema; os espelhos ficaram para trás | **uma constante por recorte**, `["']` e `[^>]*` dos dois lados |
| QA-32 | citação apontando para a blade, que não tem o `'v'` | `AssinaturaDoRodape:partes:63` |
| QA-33 | frase falsa nas docs pt e en | corrigida nas duas |
| QA-34 | regressão e `bp:off` sem linha na Verificação Final | acrescentadas |
| QA-35 | justifiquei uma lacuna com consequência do axe **nunca medida** | **medido**: o axe passa. Virou `[CT-B03]` |
| QA-36 | o gatilho de M51 depende de driver que não existe | declarado, com gatilho alcançável |
| QA-37 | *"113 asserções"* | varredura |
| QA-38 | passos do `01` fora de ordem | reordenados, 5 ↔ 6 |

**A premissa do `recadoDoRodape()` era falsa, e isso importa mais que a linha de código.** O
comentário afirmava *"a classe aparece mais de uma vez no documento"*; o gate mediu
`substr_count()` = **1**. O que consertou o helper foi o `[^>]*`, não o laço. É o mesmo erro que
produziu o Blocker do 6.5 (medir o layout errado) e as verificações V3/V5 falsas — **afirmar uma
causa sem medi-la**, três vezes na mesma feature.


**O veredito dos quatro ciclos é o mesmo, e foi preciso até o último**: *"o código está correto e
medido, o que está errado é o que a wiki afirma sobre ele."* **52 dos 53** achados foram de
produto.

### Ciclo 2 — 11 achados novos, 10 nascidos das correções do ciclo 1

| # | Achado | Fechado por |
|---|---|---|
| QA-11 | `group('kit')` em arquivo de browser faz `--parallel --group=kit` selecionar navegador | `group('browser-kit')` |
| QA-12 | a **regra de CSS**, que é o coração da correção do Blocker, sem passo no `01` nem ADR | passo 6 no `01`, **ADR-07** |
| QA-13 | `[CT-B01]`/`[CT-B02]` só no teste; o `04` ainda dizia que não existiam e contava 4 sem matador | `## Gate de CT-B` reescrito; 4 → **2** |
| QA-14 | *"5/5 ok, 3 ERRO"* falso nas três partes | **8/8 ok, 0 ERRO**, com o comando colado |
| QA-15 | `01` ainda dizia *"nada que só o navegador prove"*, falseado pela própria entrega | riscado e reescrito |
| QA-16 | a ADR-03 ficou com a citação velha que o ciclo 1 corrigiu nos outros três arquivos | `:121` → `:129` |
| QA-17 | o **recado** continua fora de landmark | ver lacuna abaixo |
| QA-18 | captura de arte não refeita, e o item sumiu do `03` | **débito explícito**, registrado |
| QA-19 | 12 linhas de `.gitignore` sem `RQ`, passo ou ADR | **ADR-08** |
| QA-20 | contagem de asserções errando por um | 11.146, medida |
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
- ~~**QA-22**~~ — **FECHADO no ciclo 4**: `[CT-B03]` mede a **propriedade** em vez de congelar a
  tag, e os mutantes M58, M59 e M62 foram **medidos mortos**. Reverter a assinatura para `<div>`
  deixa 3 de 3 rotas vermelhas
- **M38** e **M51** — já declarados no `04`

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 3 | `planning-filament` (Blueprint) | o da sessão | — | V1–V7 | **2 reprovadas** (V3, V5) — só o step 8 as pegou |
| 2 | 4 | derivação do `04` | opus | o `01` inteiro | 21 cenários, 57 mutantes **à época** (hoje 26 e 67, depois de R8 e R9) | estrutura conferida; contagem reconciliada por `grep -cE` |
| 3 | 4 | revisão adversarial (dentro do #2) | opus | só `00` + `04` | 3 rodadas | derrubou 3 dos 4 eixos que a sessão pediu |
| 4 | impl. | `fw-executor-ct` | sonnet | `01`, `02` | 21 casos novos + 3 reescritos | **travou uma vez**; a auditoria da sessão declarou ausente o que estava noutro arquivo |
| 5 | 6.5 | `fw-revisor-diff` | opus | `01`, `03` | **1 Blocker, 3 Major, 4 Minor** | Blocker reproduzido em navegador antes de aceitar |
| 6 | 8 | `fw-qa-gate` — **ciclo 1** | opus | a conversa | **REPROVADO**, 10 achados | 4 alegações amostradas, todas confirmadas |
| 7 | 8 | `fw-qa-gate` — **ciclo 2** | opus | a conversa | **REPROVADO**, 13 achados (11 novos) | **10 dos 11 nasceram das correções do ciclo 1**; o gate chamou de *"teatro de qualidade"* as três lacunas que eu declarara irredutíveis, e estava certo |
| 8 | 8 | `fw-qa-gate` — **ciclo 3** | opus | a conversa | **REPROVADO**, 15 achados (8 Major) | **teto de 3 ciclos atingido**. 11 nasceram do ciclo 2. O gate nomeou o padrão: *"as correções fecham o caso citado e deixam a classe aberta"*. Correção refeita **por varredura**, com o grep de confirmação a zero em cada classe |
| 9 | 8 | `fw-qa-gate` — **ciclo 4** | opus | a conversa | **REPROVADO**, 15 achados (6 Major) | **acima do teto, por autorização explícita do usuário** (*"roda o ciclo 4"*). Achou o único defeito de **produto** dos quatro ciclos: QA-17 não era lacuna |
| 10 | 8 | `filament-development` (Blueprint) — conferência das tags contra o Filament 5.8.1 | o da sessão | — | `<footer>` e `<aside>` **aprovados**; achado novo na classe `fi-login-rodape` | medido no `vendor/`, não deduzido: o Filament emite `<footer>` só **aninhado** (`fi-section-footer`, `fi-empty-state-footer`), **zero** `contentinfo` em nível de `<body>` e **zero** `<aside>` |

> **Sem despacho no ciclo 4** para a correção dos achados: ela foi cirúrgica e em arquivos com
> forte interdependência (a mesma troca de tag atravessa blade, CSS, dois extratores e dois
> arquivos de teste), que é a exceção declarada em `## Rodam em linha, sem despacho`. Os quatro
> **julgamentos** — os quatro ciclos de gate — foram todos a sub-agente cego, que é o que a
> cegueira exige.

## Débitos Declarados

| Débito | Por que não foi fechado aqui | Como fechar |
|---|---|---|
| a classe **`fi-login-rodape`** usa o prefixo `fi-`, reservado ao Filament | ela **nasceu na feature `login-social-google`**, não nesta, e é citada em 4 arquivos de teste e 3 wikis de outras duas features. Renomeá-la aqui contraria o `RQ-09` (*"não precisamos já lançar uma nova versão somente com essa melhoria"*), que pede um PR pequeno | `grep -rl 'fi-login-rodape' --include='*.php' --include='*.md' .` e trocar por `kit-login-rodape`. **Medido**: `grep -rn 'fi-login-rodape' resources/css/ public/css/` não devolve nada — **nenhuma regra de CSS aponta para ela**, então o rename é de seletor de teste, sem risco visual |
| QA-18 — captura de arte não refeita | `composer art` roda a suíte de browser inteira e regrava binários | rodar junto da próxima release |
| M38, M51 — mutantes sem matador | ver `## Mutantes sem matador` no `04` | gatilho: qualquer mudança no escopo do hook ou na guarda da blade |

## Notas de Implementação

### O Blocker do 6.5 nasceu de eu medir o layout errado

A verificação **V5** do plano mediu `simple.blade.php` do Filament. **8 das 9 páginas de
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

### O erro que cometi QUATRO vezes na mesma feature — e a regra que o fecha

**Casar uma referência a um cabeçalho em vez do cabeçalho.** As quatro:

| # | Onde | Estrago |
|---|---|---|
| 1 | `04-casos-de-teste.md` | seção corrompida |
| 2 | `03-progresso.md`, ciclo 3 | **a tabela `## Conformidade com Rules` inteira apagada** — deixou o eixo L do gate cego por dois ciclos (QA-28), escondendo QA-24 e QA-29 |
| 3 | `04-casos-de-teste.md` | seção corrompida de novo |
| 4 | `03-progresso.md`, ciclo 4 | a tabela de débitos inserida **dentro de um checkbox**, porque `## Notas de Implementação` aparecia ali como referência entre crases |

A quarta aconteceu **enquanto eu escrevia, no `06`, o parágrafo que explica a segunda**. Isso
descarta "desatenção" como causa: o problema é a **técnica**, não a lembrança.

**Regra, e ela é mecânica:** editar Markdown por cabeçalho exige as duas coisas juntas —

1. ancorar em **início de linha** (`l.rstrip() == '## Titulo'`), nunca `'## Titulo' in texto`,
   porque a referência entre crases casa a segunda forma e não casa a primeira;
2. **afirmar que há exatamente uma ocorrência** antes de escrever (`assert len(alvo) == 1`).

O item 2 é o que importa: ele transforma um estrago silencioso num erro que para o script. Foi
assim que a quarta ocorrência foi desfeita — e é a mesma lição de `[CT-24]`, um nível acima:
**não basta corrigir a ocorrência; é preciso um instrumento que recuse a próxima.**


- **Funcionou**: a cegueira dos gates. O 6.5 achou um Blocker que 113 casos de HTML não viam,
  e o step 8 achou que a correção do Blocker não tinha teste. Nenhum dos dois era visível de dentro
- **Faltou no plano**: verificar API **por vendor**, e não por pacote. A V3 e a V5 erraram pela
  mesma causa, e uma delas custou o Blocker
- **Faltou no processo**: o step 7. Eu fui do 6.5 direto ao PR, e o `03` chegou ao quality gate com
  27 de 27 checkboxes abertos sobre uma feature commitada
