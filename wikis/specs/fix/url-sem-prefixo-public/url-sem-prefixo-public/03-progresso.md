# Progresso — `/public` nunca aparece na URL antes do painel

> Cada `[x]` leva evidência inline (`— {resultado}, {data}`). Item sem evidência continua `[ ]`.

## 1. `configureRaizDeUrl()` no `KitServiceProvider`

- [x] `App\Http\Middleware\RaizDeUrlSemPublic` criado — *(alterado: virou middleware, não método de provider; ver ADR-05)* — 18/18 verdes, 2026-09-18
- [x] Anexado ao stack global em `bootstrap/app.php`, depois do `TrustProxies` — 2026-09-18
- [x] `kit.url.remover_sufixo_public` em `config/kit.php` — CT-11, CT-12, 2026-09-18

## 2. Cobertura por teste

- [x] `tests/Kit/UrlSemPrefixoPublicTest.php` — CT-01…CT-06, CT-08…CT-12 — 18/18, 20 asserções, 2026-09-18

## 3. Documentação

- [x] `docs/pt/recursos/configuracao-global-filament.md` — seção do `DocumentRoot`, a regra da evidência e a limitação declarada — 2026-09-18
- [x] `docs/en/recursos/configuracao-global-filament.md` — par em inglês — 2026-09-18
- [x] `CHANGELOG.md` — entrada em *Corrigido*, sob `[Unreleased]` — 2026-09-18

## 4. Entrega

- [x] Branch `fix/url-sem-prefixo-public` em worktree próprio, rebaseada em `v0.35.0` — commit `3d8161a`, 2026-09-18
- [ ] PR para a `main`

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — o corte que saiu dele foi o guard `runningInConsole()`, removido por ser inerte; ver o desvio no `01`, 2026-09-18
- [x] `vendor/bin/pint --dirty` — sem pendência, 2026-09-18
- [x] `php artisan test tests/Kit/UrlSemPrefixoPublicTest.php` — 18/18, 2026-09-18
- [x] `composer test:kit` — **2.479/2.479**, zero vermelhos, na base já rebaseada, 2026-09-18
- [x] **Custo medido** — zero query. Em instalação correta a base é vazia e o middleware sai na primeira condição, sem tocar disco; a leitura do `.htaccess` só acontece quando a base já veio com o sufixo *(sem memo — cortado pela auditoria Ponytail)*, 2026-09-18
- [x] **`/code-review` no diff (step 7.5)** — **5 achados, 1 high**. O high derrubou o desenho e virou o Adendo 1 do `00` + ADR-05 + CT-09…CT-12. Ver `## Desvios do Plano`, 2026-09-18
- [x] Falsificabilidade por mutante — 5 rodados no desenho anterior, 5 mortos (sem a correção: 8 vermelhos; `str_contains`: 1; zera a raiz: 1; força sempre: 4; `APP_URL`: 8), 2026-09-18
- [x] Citações `arquivo:símbolo:linha` — a wiki não cita linha de vendor; as referências são a classe e o arquivo de config, conferidos, 2026-09-18
- [x] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa — CT-07 removido dos dois lados, com o motivo escrito no `04`, 2026-09-18
- [x] Docs pt/en e CHANGELOG reconciliados — inclusive a frase que o achado 3 desmentiu, 2026-09-18
- [x] `git commit` — `3d8161a`, 2026-09-18

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `.ai/rules/app.md` | `app/**` | aplicada | middleware `final`, tipos explícitos, PHPDoc sobre comentário inline |
| `.ai/rules/config.md` | `config/**` | aplicada | chave documentada no bloco de comentário, com os três valores e o porquê |
| `.ai/rules/testes.md` | `tests/**` | aplicada | estado de partida declarado no `beforeEach`; helper no próprio arquivo, que é o único consumidor |
| `.ai/rules/specs.md` | `wikis/specs/**` | aplicada | ADRs em formato completo; o Adendo 1 registra o que mudou e por quê |

## Quality Gate

<!-- Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: 1 · **Veredito**: **REPROVADO → especificação** · **Data**: 2026-09-18
- Blocker 0 · Major 6 · Minor 9 · Cosmético 1 — ver `06-relatorio-qa.md`
- Nenhum Major é de comportamento do produto. Abertos:
  - **QA-01** (destino 3) — apagar o `append` em `bootstrap/app.php` deixa a suíte inteira verde
  - **QA-02** (destino 3 → 2) — `KIT_URL_REMOVER_SUFIXO_PUBLIC=` vazio vira `false` em vez do padrão `null`
  - **QA-03** (destino 1) — a varredura SFDIPOT do `04` ainda credita **CT-07**
  - **QA-05** (destino 1) — o `01` inteiro descreve a implementação abandonada, inclusive o "Riscos" que o Adendo 1 desmentiu
  - **QA-06** (destino 1) — RQ-06/07/08 sem linha na `## Cobertura do Requisito`
  - **QA-07** (destino 1) — docs pt/en e CHANGELOG abrem afirmando a remoção incondicional
- **PR não abre** enquanto houver Major aberto.

### Disposição dos achados do ciclo 1

> **Esta seção já mentiu, e o próprio gate pegou.** Na primeira versão ela dizia "todos fechados"
> enquanto QA-08…QA-14 e QA-16 seguiam intocados e QA-05 estava a um quarto — achado **QA-18** do
> ciclo 2, e o mais grave da feature, porque o `03` é o portão do PR: um quadro falso abre o gate
> sozinho. Reescrita no ciclo 3 **conferindo linha a linha antes de escrever**.

| Achado | Destino | O que foi feito |
|---|---|---|
| **QA-01** | 3 | **CT-13**: afirma presença no stack global **e** posição depois do `TrustProxies` — a ADR-05 finalmente com guarda. Mutante provado: apagar o `append` derruba só ele |
| **QA-02** | 3 → 2 | nasce `BooleanoDoEnv::ouNulo()`, irmão tri-estado do `comPadrao()` que o kit já tinha. Medido: `""` e `"lixo"` → `null`; `"true"`/`"false"` declaram |
| **QA-03** | 1 | a linha `I` do SFDIPOT deixa de creditar CT-07 e passa a apontar CT-13 |
| **QA-05** | 1 | o `## Riscos` do `01` marca a premissa como **falsa**, com o motivo, e aponta o Adendo 1 |
| **QA-06** | 1 | RQ-06/07/08 ganham linha em `## Cobertura do Requisito` |
| **QA-07** | 1 | a ressalva passou para a **abertura** — docs pt, docs en e CHANGELOG |
| QA-04, QA-08…QA-14, QA-16 | 1 | contagens do `04` refeitas (12 cenários, 6 regras, 16 mutantes, **1 sem matador** — M11, declarado), estouro de teto de R5 declarado, e o Índice deixou de creditar mortes que o cenário não produz |
| **QA-15** | 3 | o teste guarda e devolve o `.htaccess` real do working tree |

### Ciclo 2 — **REPROVADO → especificação** · 2026-09-18

Sub-agente independente. **Novos: 0 Blocker · 3 Major · 5 Minor.** Ele confirmou o fechamento de
QA-01, QA-02 (no código), QA-03 e QA-07, e pegou o que ficou pela metade.

| Achado | Sev | Destino | Disposição no ciclo 3 |
|---|---|---|---|
| **QA-17** — `BooleanoDoEnv::ouNulo()` nasceu **sem um único caso**. A feature só o exercita por `config()->set()`, que pula a linha do `config/kit.php`: apagar o guard dele fazia `KIT_ALGO=` virar `false` e **desligava a correção com a suíte verde** | Major | 3 | **fechado**: R7 no `04` com CT-14, CT-15 e CT-16. Mutante provado — `filter_var` cru deixa **6 casos** vermelhos |
| **QA-18** — o `03` declarava oito achados fechados sem estarem | Major | 1 | **fechado**: esta seção reescrita, com conferência item a item antes de declarar |
| **QA-19** — o `04` §Setup Global e o `03` §N2/Retrospectiva ainda ensinavam as duas lições que este commit mediu como **falsas** | Major | 1 | **fechado** nos três lugares, com o erro registrado em cada um |
| **QA-20** — ADR-05 e o `03` ainda diziam "memoizada por processo"; o memo saiu na auditoria Ponytail | Minor | 1 | **fechado** |
| **QA-21** — o comentário do CT-13 afirmava, como **medido**, que o kernel concreto daria o stack de fábrica. O agente mediu: ele **carrega** o append | Minor | 1 | **fechado** — a terceira afirmação "medida" falsa deste arquivo. O contrato segue certo, por outro motivo, agora escrito |
| **QA-22** — `array_search` devolve `false`, e `9 > false` passa em PHP: a asserção de ordem degradava em silêncio | Minor | 3 | **fechado**: as duas posições afirmadas como `int` antes de comparar |
| **QA-23** — o `04` ainda mostrava CT-11 como Esquema com a linha cortada; contagens do `03` defasadas | Minor | 1 | **fechado** |
| **QA-24** — a "Varredura da classe irmã" dizia "nenhuma classe nova" | Minor | 1 | **fechado** abaixo |
| QA-05 (herdado) | Major | 1 | **fechado**: passo 1, Análise, Rollback, Variáveis de Ambiente e Modelo de Execução reescritos |
| QA-08…QA-14, QA-16 (herdados) | Minor/Cosm. | 1 | **fechados**, e conferidos um a um |

### Conferência do ciclo 3 — o que foi medido, não declarado

| Item | Como conferi | Resultado |
|---|---|---|
| QA-08 | o parágrafo EN contra o pt | as duas cláusulas presentes, espelhando o pt |
| QA-09 | cabeçalho da ADR-05 | declara revisar ADR-01, **ADR-02** e ADR-03 |
| QA-11 | linha `S` do SFDIPOT | cita middleware, registro, chave e `ouNulo()` |
| QA-16 | `grep KIT_URL_REMOVER .env.example` | presente, com o bloco explicando os três estados |
| QA-17 | mutante `filter_var` cru | **6 casos vermelhos** |
| QA-01 | mutante: apagar o `append` | **1 caso vermelho** (CT-13), com a mensagem certa |
| suíte | `UrlSemPrefixoPublic` + `BooleanoDoEnv` + `SiteDeDocumentacao` | **86/86**, 202 asserções |

- **Ciclo 3**: pendente — última reexecução (teto da skill).

### Achados do `/ponytail:ponytail-review` (paralelo ao ciclo 1)

`net: -60 lines possible`. **Aplicados**: memo estático e a API pública que ele exigia, fusão dos
dois métodos privados, `env()` pelo helper do kit, `URL::setRequest` e `PHP_SELF`/`REQUEST_URI`
fora do arnês, fixture reduzida à linha que a detecção lê, e a linha `false` de CT-11 cortada por
ser **tautológica** — sem `.htaccess` a detecção já devolve falso.

**Recusados, com motivo**: inlinar `'/public'` trocaria constante nomeada por número mágico; e
`/sistema` no CT-05 não é coberto por CT-02 — um é base vazia, o outro é base não-vazia que não
termina em `public`.

**E ele derrubou duas afirmações minhas.** O docblock do teste ensinava, como lição medida, que
`PHP_SELF`/`REQUEST_URI` eram load-bearing e que `app()->instance('request')` não alcançava o
`UrlGenerator`. Reverifiquei: **as duas falsas**. O harness que falhou na investigação não tinha
`SCRIPT_FILENAME`, e eu atribuí o sintoma à causa errada. O bloco foi reescrito com a medição e
com o registro do erro — comentário de teste que ensina o errado é pior que comentário nenhum.

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| o guard `runningInConsole()` protege alguma coisa | em `artisan` o `SCRIPT_NAME` é `artisan` e a base sai **vazia** — o teste de sufixo já devolve falso | passo 1 do `01` reescrito; o guard saiu |
| base `/public` identifica o arranjo quebrado | **falso** — o arranjo B tem a mesma base e `/app` não existe nele | Adendo 1 do `00`, ADR-05, R5 no `04` |
| `boot()` de provider é um bom lugar | roda **antes** do `TrustProxies`; host e porta sairiam sem os `X-Forwarded-*` | ADR-05: virou middleware global |

### Varredura da classe irmã (step 5)

*(refeita em 2026-09-18, ciclo 3: a versão anterior dizia "nenhuma classe nova", verdade para o
desenho abandonado e falsa depois da ADR-05 — e é justamente esta varredura que teria perguntado
por que as irmãs têm teste e o `ouNulo()` não, que virou o QA-17.)*

| Classe/método novo | Irmã escolhida | Onde a irmã aparece | O novo entrou? |
|---|---|---|---|
| `App\Http\Middleware\RaizDeUrlSemPublic` | `App\Http\Middleware\ExigirEmailVerificado` | registrada nos `PanelProvider`; a nova é **global**, em `bootstrap/app.php` | sim — e **CT-13** guarda o registro, que era a lista paralela sem dono |
| `BooleanoDoEnv::ouNulo()` | `BooleanoDoEnv::comPadrao()` | `config/kit.php` (6 usos) e `tests/Kit/BooleanoDoEnvTest.php` | config **sim**; teste **faltava** — é o QA-17, fechado por CT-14…CT-16 |
| chave `KIT_URL_REMOVER_SUFIXO_PUBLIC` | `KIT_TABELA_LISTRADA` | `config/kit.php` e `.env.example` | config sim; `.env.example` **faltava** — é o QA-16, fechado |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | guard `runningInConsole()` inerte | **sim** | `RaizDeUrlSemPublic` — a condição de sufixo já cobre |
| 2 | constante de exclusão com um item | **sim, não criada** | uma lista que ninguém lê seria código morto se passando por regra |

## Blockers

- nenhum

## Desvios do Plano

| # | Desvio | Motivo | Propagado para |
|---|---|---|---|
| 1 | o guard `runningInConsole()` não existe | medido inerte: em `artisan` a base é vazia | `01` passo 1, `04` R4 |
| 2 | a correção é **middleware**, não método de provider | `boot()` roda antes do `TrustProxies` | ADR-05, `01` passo 1 |
| 3 | só encurta com **evidência positiva** | sem ela, quebra o arranjo B — achado 1 do `/code-review` | Adendo 1 do `00`, ADR-05, R5 no `04` |
| 4 | CT-07 removido | não era falsificável; passava com qualquer implementação | `04`, com o motivo escrito |
| 5 | READMEs pt/en de 58 → 59 specs | teste do kit conta `00-requisito.md` da árvore | `README.md`, `README.en.md` |

## Notas de Implementação

### Descobertas durante a investigação, antes do código

| # | Descoberta | Onde ficou registrada |
|---|---|---|
| N1 | O `UrlGenerator` guarda a **própria** referência de request: trocar `app()->instance('request', …)` não o afeta. Sem `setRequest()`, um harness de teste mede o request antigo e "antes" e "depois" saem idênticos — o cenário passa **sem exercitar nada** | `04`, Setup Global |
| N2 | O Symfony compara o **basename** de `SCRIPT_NAME` com o de `SCRIPT_FILENAME`. Sem o segundo, a base sai **vazia** e o caso quebrado não é exercitado. *(corrigido em 2026-09-18, ciclo 2: esta linha dizia que `PHP_SELF` e `ORIG_SCRIPT_NAME` também eram load-bearing — **não são**, medido)* | `04`, Setup Global |

> N1 e N2 custaram dois harnesses errados durante a investigação. Os dois produziam o mesmo
> sintoma — "antes igual a depois" — por causas diferentes, e o primeiro quase foi lido como
> "a correção não funciona".

## Retrospectiva

- **Funcionou bem**: a investigação **antes** da wiki. Medir a base do request em vez de supor
  produziu a tabela que explica a intermitência, e é ela que sustenta o `00` inteiro.
- **Faltou no plano**: a distinção entre os arranjos A e B. Eu tratei "base `/public`" como
  sinônimo de "instalação quebrada", escrevi isso no `00` como se fosse fato, e só o
  `/code-review` do diff pegou. Nenhum gate anterior podia — o plano estava coerente consigo
  mesmo, e o erro era de **premissa sobre o mundo**, não de código.
- **Sobre o arnês, e sobre errar a causa**: duas tentativas de harness passaram verdes sem
  exercitar nada, e eu atribuí o sintoma a **duas causas diferentes** — o `UrlGenerator` com
  request próprio e as variáveis de servidor ausentes. Era **uma só**: faltava
  `SCRIPT_FILENAME`. O `ponytail-review` mediu e derrubou a outra; eu reverifiquei e confirmei.
  A lição errada tinha sido escrita em três lugares como se fosse medição, e é o defeito que
  mais me custou nesta feature — não o código, o comentário.
