# Casos de Teste — Validação de release em projeto instalado

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths e a declaração `Sem superfície de UI`)
> Derivado do **requisito**, não do plano. O `02-decisoes-arquiteturais.md` e a correção já aplicada
> em `tests/Kit/HostLocalTest.php` foram mantidos fora do contexto de quem derivou.

> ## ⚠️ O oráculo mudou no meio da derivação — e esta é a versão 3
>
> A versão 1 foi derivada contra um `00` de 140 linhas. Durante a derivação, o `00` cresceu para 158
> e **reverteu a decisão central** sobre a qual R6 e R8 estavam construídas:
>
> | Antes (v1 do `00`) | Depois (**RD-02**, correção do step 6.5) |
> |---|---|
> | *"não haverá guarda automatizada; o roteiro é a guarda, e ela é empírica"* | *"a pergunta estava mal posta"* — a opção "nenhuma guarda" **não existia**: `RedeDeDocumentacaoTest [CT-10]` já era uma guarda estática, e estava **verde com o defeito presente** |
> | as três varreduras erraram → a regra é indecidível | erraram **por limitação delas**, não por propriedade do problema. A fatia decidível (caminho literal, no corpo do caso) é **justamente por onde o defeito passou** |
> | pergunta em aberto: a decisão alcança o caso nomeado? | **respondida**: acrescentar guarda estática sobre a fatia decidível, provada por mutação contra o defeito real |
> | — | a pergunta certa não era *"criar guarda?"*, era ***"endurecer a que existe?"*** — o `HostLocalTest` **continha** `naArvoreDoKit()`, no `[CT-34]`, 750 linhas abaixo do `[CT-12]`, e o `str_contains` sobre o arquivo inteiro deu verde |
>
> **O que mudou neste arquivo**: a pergunta 2 saiu (foi respondida); o `@premissa` de CT-10, CT-19,
> CT-20 e CT-21 caiu; e R6 ganhou **CT-22 e CT-23**, que são a cláusula nova — a guarda existente
> tem de reprovar o arquivo cuja sentinela mora em outro caso, e a guarda endurecida tem de declarar
> o que não alcança.
>
> **Colisão de IDs, declarada**: o `00` chama de `[CT-10]` e `[CT-11]` casos de
> `tests/Kit/RedeDeDocumentacaoTest.php`, na numeração **daquele arquivo**. Os `CT-nn` deste
> documento são de **esta wiki**. Onde há risco de leitura cruzada, o arquivo é nomeado junto.

## Perfil de Derivação

| Área | O que é | P | I | P×I | Perfil |
|---|---|---|---|---|---|
| **A** — o roteiro documentado (RQ-05, RQ-06) | texto novo, isolado, sem runtime | 1 | 2 | 2 | mínimo |
| **B** — a guarda: corrigir o caso e **endurecer a que existe** (RQ-07, RQ-08) | mexe numa guarda que já falhou uma vez, e que varre 16 arquivos de suíte | 3 | 3 | 9 | **completo** |
| **C** — a entrega do roteiro pelos dois canais (RQ-05, assumido) | integra com `.gitattributes` e com a lista fechada do `kit:update` | 2 | 2 | 4 | padrão |
| **D** — os quatro cenários rodando liso contra a versão publicada (RQ-01…RQ-04, RQ-09) | ambiente externo, quatro instalações, dois eixos | 3 | 3 | 9 | completo |
| **E** — a versão de correção (RQ-08) | bump de versão, já governado por gate existente | 1 | 2 | 2 | mínimo |

**A área B subiu de `padrão` para `completo` com a RD-02.** Deixou de ser "corrigir um caso" e passou
a ser "endurecer um oráculo que varre 16 arquivos de suíte e que já deu verde com o defeito dentro" —
probabilidade 3 (regra com muitas condições, três mecanismos de guarda distintos) e impacto 3
(a guarda errada é pior que guarda nenhuma, que é o argumento original do usuário).

- **Revisão adversarial**: obrigatória por Impacto 3 (B, D) e perfil completo (B, D). **Duas rodadas**
  executadas por sub-agente cego. Ver [`## Revisão Adversarial`](#revisão-adversarial).
- Técnicas: **EP exaustiva** (as quatro combinações de RQ-01…RQ-04), **tabela ambiente × artefato**
  (substitui a matriz estado × operação), **rastreio de efeito de entrega até o disco**, **controle
  positivo bilateral** em toda sentinela e em toda asserção de ausência, **partição sobre os três
  mecanismos de guarda**, **valor limite** sobre contagens.
- Cenários: **23** · Regras: **9** · Mutantes previstos: **42** · Sem matador: **7** · Parciais: **4**
- **Corrigido pelo quality gate, ciclo 1 (QA-04)**: era "sem matador: 5". O CT-18 foi escrito com
  **corpo vazio e pulado** — o `kit:update` opera sobre `base_path()` fixo e exige `.git` real,
  então exercitá-lo destruiria o próprio checkout. A razão é boa; o que faltou foi **mover M25 e
  M27 para a coluna certa** em vez de deixá-los contados como mortos. Ver [L6](#l6--a-entrega-em-disco-pelo-kitupdate-nao-e-exercitavel-aqui).

> **A superfície testável é pequena e incomum, e isso é um fato do requisito.** A maior parte da
> entrega é texto, e RQ-09 **só é observável fora da árvore do kit**. A seção
> [`## Lacunas Declaradas`](#lacunas-declaradas) é a parte mais importante deste arquivo.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S** | Nenhum model, migration, policy, rota, job ou comando novo. Os artefatos estão listados um a um na [tabela ambiente × artefato](#tabela-ambiente--artefato) — são **15** | todos |
| **F** | Nenhuma função de runtime. Quatro funções: **instruir** (CT-01…CT-06, CT-12…CT-17) · **entregar** (CT-07…CT-09, CT-18) · **não quebrar** (CT-10, CT-19…CT-21) · **detectar** (CT-22, CT-23 — a guarda que já falhou) | — |
| **D** | Texto em markdown; o atributo `export-ignore` resolvido pelo git; a lista fechada de caminhos; a versão; a contagem de pulados. **Cardinalidade é oráculo** em dois lugares: quatro cenários (nem três nem cinco) e um número de pulados que não pode inflar em silêncio. **Dado ausente é o defeito** | CT-01 · CT-13 · CT-10, CT-21 |
| **I** | **Sem superfície de UI** (declarado no `01`). Entradas humanas: `CONTRIBUTING.md`, `wikis/README.md`, os dois READMEs. Entradas de máquina: `composer create-project` (`.gitattributes`) e `php artisan kit:update` (lista fechada) | CT-05, CT-16 · CT-07, CT-09, CT-18 |
| **P** | **É a dimensão onde o defeito mora.** Dois ambientes separados pelo recorte do `git archive`: a árvore do kit (tem `.github`, `docs/`, `CHANGELOG.md`, repositório git) e o projeto instalado (não tem nenhum). `git check-attr` **não responde fora de um repositório git**. E a distância física importa: a sentinela do `HostLocalTest` estava **750 linhas** abaixo do caso desprotegido, e por isso o `str_contains` sobre o arquivo passou | CT-07, CT-08, CT-22 · toda a tabela |
| **O** | Um único operador: o mantenedor, uma vez por tag. **Acumulação de papéis**: quem publica a tag é quem declara "liso" — sem segundo par de olhos e sem automação separando os dois atos. Usos indevidos: pular o roteiro; rodar só o cenário sem tenancy; rodar na árvore do kit; rodar contra a branch | CT-03, CT-04, CT-15 · [pergunta 6](#perguntas-para-o-00-requisitomd) |
| **T** | Ordem fixa e assimétrica: publicar a tag → validar → corrigir → publicar outra tag. O `create-project … vX` exige a tag publicada, então **toda validação é post-mortem**. A ordem *validar antes de publicar* é exigida por CT-15 e não é verificável por máquina. Sem timezone, sem concorrência | CT-15 · [pergunta 3](#perguntas-para-o-00-requisitomd) |

---

## Tabela ambiente × artefato

Não há entidade com ciclo de vida, então **não existe matriz estado × operação** — declarado, não
omitido. O eixo que a substitui é o que produziu o defeito.

**Produto cartesiano fechado: 15 artefatos × 2 ambientes = 30 células.** Uma está **não resolvida**,
e está declarada como tal. Mecanismos (comandos) têm tabela própria, porque a legenda não os alcança.

**Legenda, auditada célula a célula**
- **presente** = `file_get_contents(base_path(…))` devolve conteúdo naquele ambiente
- **ausente** = o `git archive` do `composer create-project` não o exporta
- **com guarda** = o caso que o lê carrega `->skip(fn (): bool => ! naArvoreDoKit(), …)` **na própria
  declaração do caso**, nunca no `beforeEach` do arquivo
- **não resolvida** = o `00` não determina, e a pergunta continua bloqueando

| # | Artefato | Árvore do kit | Projeto instalado | CT desta wiki que o lê | Guarda |
|---|---|---|---|---|---|
| 1 | `wikis/checklist-de-release.md` | presente | **presente** | **redação**: CT-01…CT-04, CT-06, CT-12…CT-15, CT-17 · **existência**: CT-08 | **com guarda**, exceto CT-08 — ver [a regra do canário único](#a-regra-do-canário-único) |
| 2 | `wikis/README.md` | presente | presente — único `wikis/` que a lista fechada já trazia | CT-05, CT-16 | **com guarda** (o caso é compartilhado com `CONTRIBUTING.md`) |
| 3 | `CONTRIBUTING.md` | presente | **não resolvida** — [pergunta 1](#perguntas-para-o-00-requisitomd) | CT-05, CT-16 | **com guarda**, por falha fechado |
| 4 | `README.md` | presente | presente, mas **passa a ser do projeto** | nenhum CT desta wiki | n/a — o leitor é `SiteDeDocumentacaoTest [CT-48]`, **já guardado por caso** (conferido) |
| 5 | `README.en.md` | presente | idem | nenhum CT desta wiki | idem |
| 6 | `docs/pt/comecar/dominio-local.md` | presente | **ausente** — `/docs export-ignore` | nenhum CT desta wiki o abre | **com guarda** — é o caso corrigido, `HostLocalTest [CT-12]`; CT-10, CT-19, CT-20 leem o *arquivo de teste*, não o documento |
| 7 | `CHANGELOG.md` | presente | **ausente** | CT-11 | n/a — **não há caso Pest**; CT-11 é gate de CI, que só roda no repositório |
| 8 | `.gitattributes` | presente | presente, porém **inerte**: sem repositório git nada o aplica | **nenhum** — as Armadilhas **proíbem** regex sobre ele; CT-07 consulta o mecanismo | n/a |
| 9 | `config/kit.php` | presente | presente — entregue pelo `kit:update` | CT-11 | n/a (CI) |
| 10 | `tests/Kit/HostLocalTest.php` (como texto) | presente | **presente** — os 178 arquivos de teste viajam | CT-10, CT-19, CT-20, CT-22 | sem guarda — ler o *arquivo de teste* é seguro nos dois ambientes |
| 11 | `app/Console/Commands/KitUpdate.php` | presente | presente — é código entregue | CT-09 | sem guarda |
| 12 | `.github/workflows/release.yml` | presente | **ausente** — `/.github export-ignore` | CT-11 | n/a — é o próprio gate |
| 13 | `tests/Kit/ChecklistDeReleaseTest.php` (como texto) | presente | **presente** — passa a ser o 194º que viaja | CT-21 | sem guarda |
| 14 | `tests/Pest.php` (onde a sentinela é definida) | presente | **presente** — viaja com a suíte | CT-19 | sem guarda |
| 15 | `tests/Kit/RedeDeDocumentacaoTest.php` (a guarda que já falhou) | presente | **presente** | CT-22, CT-23 | sem guarda |

**Mecanismos — a legenda acima não se aplica**

| Mecanismo | Árvore do kit | Projeto instalado | Consequência |
|---|---|---|---|
| `git check-attr` | responde | **não responde** — não há repositório git | segunda razão, independente do `export-ignore`, para CT-07 levar guarda; e o motivo de CT-08 existir desguardado |
| `php artisan kit:update` | roda | roda | é o `Quando` de CT-18, o único oráculo de entrega **até o disco** |

### A regra do canário único

**Achado da rodada 2, e é de desenho, não de redação.** A versão 2 deixava **onze** casos afirmando
sobre a **redação** do roteiro rodarem sem guarda em todo projeto instalado. O roteiro é material de
quem **mantém** o kit: reescrever uma frase dele deixaria vermelha a suíte de todo projeto de
terceiro, por um motivo que o terceiro não consegue ler — exatamente o modo de falha que esta wiki
existe para eliminar, institucionalizado em onze casos novos.

A regra que ficou: **apenas CT-08 lê o roteiro sem guarda**, e ele afirma **só existência e
destinatário** — as duas coisas que valem para quem instalou. Toda asserção sobre redação de processo
é do kit e leva guarda. CT-21 executa esta regra.

> **Esta tabela é a asserção central deste `04`.** Cada rodada da revisão adversarial encontrou
> artefatos faltando nela — a rodada 1 achou três (entre eles o arquivo de teste que a própria
> entrega cria), a rodada 2 achou dois (o arquivo da sentinela e a guarda que já falhou). É o sinal
> de que ela é o eixo certo: é onde a omissão aparece.

---

## Mapa de Regras

| Regra | Área (perfil) | `RQ` | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — quatro cenários, cruzando origem × tenancy, cada um com a versão-alvo fixada por marcador | A (mínimo) | RQ-01…RQ-05 | EP exaustiva (2×2) + partição do argumento de versão (fixado × ausente × branch × **literal envelhecido**) | CT-01, CT-02, CT-12 |
| **R2a** — a obrigação é declarada sem hedge e **sem nenhuma cláusula de exceção** | A (mínimo) | RQ-06 | EP + oráculo estrutural (contagem de exceções = 0) | CT-03, CT-14 |
| **R2b** — o critério de aprovação é fechado e não infla em silêncio | A (mínimo) | RQ-09, RQ-06 | EP + valor limite (n, n+1) | CT-04, CT-13, CT-15 |
| **R3** — o roteiro é alcançável pelos dois pontos de entrada, e o alvo do link resolve **a partir do arquivo que o declara** | A (mínimo) | RQ-05 | EP (2 entradas) + rastreio até o destino + partição da base do link | CT-05, CT-16 |
| **R4** — o roteiro registra a medição empírica **e o limite dela**, conforme a RD-02 | A (mínimo) | RQ-05, RQ-06, RQ-07 | EP + rastreio da cláusula revista | CT-06, CT-17 |
| **R5** — o roteiro chega a quem instala pelos dois canais, **até o disco**, declarando de quem é o processo | C (padrão) | RQ-05 (assumido) | rastreio de efeito de entrega (2 canais) + partição do estado do destino | CT-07, CT-08, CT-09, CT-18 |
| **R6** — a guarda é do caso, a sentinela discrimina, a leitura não sumiu, **e a guarda existente é endurecida** | B (**completo**) | RQ-07, RQ-08, RQ-09, **RD-02** | partição dos 3 mecanismos + controle positivo bilateral + exercício contra o defeito real | CT-10, CT-19, CT-20, CT-22, CT-23 |
| **R7** — sai uma versão de correção, coerente e descrita | E (mínimo) | RQ-08 | EP | CT-11 |
| **R8** — os quatro cenários rodam liso, e esta entrega não abre um caso novo da mesma classe | D (completo) | RQ-01…RQ-04, RQ-09 | inspeção fechada de 1 arquivo, com a lista de agulhas vinda **da tabela** | CT-21 (+ [L1](#l1--a-execução-dos-quatro-cenários-continua-fora-do-arnês)) |

**Estouros de teto, declarados** — o gate vence o teto: R1 e R2b têm 3 cenários em área `mínimo`
(a cardinalidade *é* o requisito, e o valor limite sobre pulados é o único matador de um mutante que
a própria correção produz). R6 tem 5 em área `completo` (teto 5) — no limite, e é a regra que a
RD-02 reescreveu.

**R2 foi dividida em R2a e R2b** no fechamento da rodada 1: cinco cenários numa regra era o sintoma
de duas regras falsificáveis de forma independente.

---

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `KitUpdate::CAMINHOS_DO_KIT` — o nome da lista | escolha de implementação | detalhe de CT-09; o oráculo real é CT-18, sobre o **disco** |
| "a lista ganha `wikis/checklist-de-release.md`" | comportamento visível que só o plano determina | [pergunta 2](#perguntas-para-o-00-requisitomd) + CT-09/CT-18 `@premissa` |
| "o comentário do `.gitattributes` passa de onze para doze" | comentário não é comportamento, e o número envelhece | não vira `Então` |
| `v0.38.1` — o número da versão | RQ-08 diz "uma versão de correção", sem número | detalhe de CT-11 |
| `tests/Kit/HostLocalTest.php`, `RedeDeDocumentacaoTest.php` | o `00` nomeia os dois, e a RD-02 nomeia o caso e o mecanismo | podem virar `Então` |
| `wikis/checklist-de-release.md`, `CONTRIBUTING.md` | o `00` os **DECIDIU** em `## Ambiguidades` | podem virar `Então` |
| `## Superfície de UI` → **"Sem superfície de UI"** | é para isso que o plano serve | **apaga o `05`** |

---

## Perguntas para o `00-requisito.md`

> **Desvio declarado**: o `00` foi tratado como somente leitura. Bloco pronto para colagem em
> `## Ambiguidades e Perguntas Abertas`. As perguntas **continuam bloqueando**.
>
> **A pergunta 2 da versão anterior saiu**: a RD-02 a respondeu. Ficam seis.

```markdown
- **RQ-05 — o `CONTRIBUTING.md` viaja para o projeto instalado?**
  O `.gitattributes` não o alcança, então viaja por padrão — mas os três motivos escritos para
  criá-lo (botão de contribuir, template de PR, insights) são todos do **repositório**. É o mesmo
  par de perguntas do `CHANGELOG.md`, que É `export-ignore`.
  **Assumido (falha fechado)**: **não viaja** — CT-05 e CT-16 levam guarda.

- **RQ-05 — "viaja, como o `roadmap.md`" inclui o segundo canal?**
  O `.gitattributes` serve quem instala **agora**; a lista fechada serve quem **já instalou**. O
  texto do `00` descreve só o primeiro.
  **Assumido (falha fechado)**: **inclui os dois** (CT-09, CT-18).

- **RQ-06 / RQ-09 — a validação é sempre post-mortem?**
  O `create-project … vX` exige a tag publicada. Toda validação é depois da tag, e toda correção é
  outra tag. É o desenho aceito, ou o roteiro deveria rodar contra uma tag de ensaio?

- **RQ-09 — liso contra qual versão, e há teto?**
  Se a reexecução revelar um quinto defeito da mesma classe, RQ-09 obriga outra versão, e assim por
  diante. Há teto, ou o critério é "até fechar"?

- **RQ-09 — "pulado declarado não conta" tem teto? E quem o aprova?**
  A premissa registrada é **anti-conservadora nesta feature**: a correção canônica desta classe de
  defeito **é adicionar um `skip`**. Sem teto, cada release converte erro em pulado e o roteiro
  segue dizendo "liso" — falha **aberto**. CT-13 fecha o teto, **mas a revisão adversarial objetou
  em dois pontos, e a objeção procede**: (a) o `00` diz que decidir sobre os 145 "é outra entrega",
  e cobrar justificativa por pulado a mais é essa decisão pela porta dos fundos; (b) sem oráculo
  sobre a execução, o teto é um número que a mesma pessoa edita no mesmo commit em que adiciona o
  `skip`. **CT-13 está escrito e marcado `@premissa` — é acréscimo de processo que precisa de
  aprovação explícita. Se negado, CT-13 cai, e com ele M10 e M12 ficam sem matador.**

- **RQ-09 — quem declara "liso" é quem publica a tag. Há segundo par de olhos?**
  Nenhuma automação separa os dois atos. O par *executor da validação × publicador da tag* não tem
  cenário possível enquanto for a mesma pessoa. Mitigado, não resolvido, por CT-15.
```

---

## Setup Global

### Suíte e camada
- Toda a entrega é asserção sobre **arquivo em disco**. Sem persona, fixture de banco ou fake.
- Camada: **`tests/Kit`** (`Tests\TestCase` + `RefreshDatabase` + grupo `kit`). Arquivo novo:
  `tests/Kit/ChecklistDeReleaseTest.php`. CT-22 e CT-23 alteram `tests/Kit/RedeDeDocumentacaoTest.php`.
- **`tests/Unit` não está ligado** ao `TestCase` da aplicação (conferido: só `Feature`, `Kit`,
  `Tenancy`, `Browser`, `BrowserTenancy` têm `->in(…)`). Nenhum cenário vai para lá.

### Restrição dura desta entrega — e **ela é testada**, não só escrita
> Todo caso que leia um artefato marcado **ausente** ou **não resolvida** na
> [tabela ambiente × artefato](#tabela-ambiente--artefato) carrega a sentinela **na própria
> declaração do caso**. Toda asserção sobre a **redação** do roteiro também, pela
> [regra do canário único](#a-regra-do-canário-único). **Exatamente um** caso lê o roteiro sem
> guarda: CT-08.
>
> A rodada 1 provou que a restrição, sozinha, é prosa. **CT-21 a executa**, e a lista de artefatos
> dele vem **da tabela**, não de uma enumeração ad hoc dentro do cenário — foi o achado D2 da
> rodada 2: a versão anterior enumerava dois artefatos de quatro, e o que ficava de fora era
> `docs/`, o caminho que originou esta wiki.

### Controle positivo é obrigatório em quatro lugares
1. **Toda asserção de ausência** (CT-01, CT-02, CT-03, CT-06, CT-08, CT-14) — a forma negativa de
   `toContain()` **nunca falha**, e o markdown do kit cita o que proíbe.
2. **A consulta ao `git`** (CT-07) — um `git check-attr` que não responde devolve o mesmo
   `unspecified` de um caminho inexistente.
3. **A sentinela** (CT-19) — e **bilateral**: o ramo verdadeiro e o ramo falso. Achado C1 da
   rodada 2: afirmar só o ramo verdadeiro na árvore do kit não distingue a sentinela correta de
   `fn () => true`.
4. **A guarda endurecida** (CT-22) — exercida contra o arranjo real do defeito (sentinela num caso,
   leitura desguardada em outro), que é a "mutação contra o defeito real" que a RD-02 exige.

### Armadilhas do projeto que invalidam estes CT
| Armadilha | Onde ela morde |
|---|---|
| `toContain($x, $msg)` é **variádico** — o 2º argumento é outra agulha | todo CT afirma sobre texto. Use `assertStringContainsString(...)`. A negativa **nunca falha** |
| asserção de ausência sobre arquivo documentado reprova pela própria documentação | CT-02, CT-03, CT-06, CT-08, CT-14. Filtre citação antes |
| regex sobre o `.gitattributes` em vez de `git check-attr` | falso negativo demonstrado: `wikis/** export-ignore` e `*.md export-ignore` removem o arquivo e o regex não os vê |
| asserção sobre a seção do topo do `CHANGELOG.md` expira sozinha | CT-11: arquivo inteiro, ou leia `config('kit.version')` |
| `git check-attr` fora de repositório git | razão independente para CT-07 levar guarda, e motivo de CT-08 existir |
| link markdown ≠ menção em prosa, **e a base do link importa** | CT-16: extraia o alvo e resolva **a partir do diretório do arquivo que o declara** |
| `token_get_all()` e não regex, para inspecionar código de teste | CT-10, CT-19…CT-23 leem PHP. É a técnica que `HelpersDeTesteTest` já usa, e a razão está no `.ai/rules/testes.md`: regex conta comentário como chamada — e uma das três varreduras do `00` errou por não atravessar chaves aninhadas |

### Divergência declarada: skill × ambiente
- `pest --mutate` **não roda aqui** (sem PCOV, sem Xdebug; e no Windows o score é falso sem o
  lançador `.cmd`). Os 42 mutantes são derivados à mão. Ver [L4](#l4--o-fechamento-do-ciclo-por-pest---mutate-não-roda-neste-ambiente).
  **Atenção**: a RD-02 exige a guarda nova *"provada por mutação contra o defeito real"* — isso é
  factível **sem** o plugin: é CT-22, que exercita a guarda contra o arranjo do defeito.
- O `--filter` do Pest casa a **descrição** do `it()`, não o `[CT-nn]`. Confira o campo `tests:`.

---

## Regra R1 — quatro cenários, cruzando origem com tenancy, com a versão-alvo por marcador

> `RQ-01`…`RQ-05` · área A · **EP exaustiva** (2×2) + **partição do argumento de versão**
> (fixado por marcador × ausente × branch × **literal de outra release**)

```gherkin
# language: pt
Funcionalidade: Validação de release em projeto instalado

  Regra: o roteiro descreve quatro cenários de validação, um por combinação de origem e tenancy

    Esquema do Cenário: [CT-01] cada combinação tem uma seção própria e distinta
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor conta as seções de cenário de validação do documento
      Então existe exatamente uma seção cujo título nomeia "<origem>" e "<tenancy>"
      E o total de seções de cenário de validação é quatro

      Exemplos:
        | origem                  | tenancy     | # partição |
        | composer create-project  | sem tenancy | RQ-01      |
        | composer create-project  | com tenancy | RQ-02      |
        | php artisan kit:update   | sem tenancy | RQ-03      |
        | php artisan kit:update   | com tenancy | RQ-04      |

    Cenário: [CT-02] cada cenário de atualização parte, ele próprio, de uma versão anterior publicada
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê a seção de cada um dos dois cenários de kit:update
      Então cada seção carrega um comando de criação com uma versão de origem explícita
      E nenhuma seção manda rodar o kit:update sobre o projeto recém-criado da versão corrente

    Cenário: [CT-12] os comandos fixam a versão-alvo por marcador, e não por número literal
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê os comandos de criação das quatro seções
      Então nenhum comando traz "a última", um nome de branch, nem um número de release literal
      E cada comando usa o marcador que o roteiro define como a tag sendo validada
```

**Por que CT-01 conta seções e não procura tokens** (rodada 1): um parágrafo com os quatro tokens
satisfaz um oráculo de co-ocorrência, e um roteiro com **um** cenário de update dizendo *"repita com
e sem tenancy"* faria RQ-04 desaparecer. O oráculo é **estrutural**.
**Controle positivo de CT-01**: o contador é exercido contra um texto com uma quinta seção e tem de
reprovar — sem isso, "o total é quatro" passa num documento vazio.

**Por que CT-12 proíbe o literal** (rodada 2, achado A1): a versão anterior exigia "uma versão
explícita", satisfeita por `v0.38.0` escrito à mão — e na tag seguinte o mantenedor validaria a
release **anterior**, que é M40 inteiro. A partição declarada tinha três valores e faltava o quarto:
*tag fixada, porém de outra release*. O oráculo é um **marcador substituível**, não um número.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | dois cenários — "instalação" e "atualização" — com a tenancy como observação no corpo | CT-01 |
| M2 | esquece o `kit:update` **com** tenancy, o mais caro de montar | CT-01 (linha 4 + contagem) |
| M3 | um único cenário de update dizendo "repita com e sem tenancy" | CT-01 (contagem == 4) |
| M4 | manda rodar o `kit:update` no projeto recém-criado da versão corrente | CT-02 (2ª linha) |
| M5 | os comandos não fixam versão: valida o que o Packagist servir naquele minuto | CT-12 (1ª linha) |
| M6 | o comando fixa um número literal, que envelhece: a release seguinte valida a anterior | CT-12 — *origem: revisão adversarial, rodada 2 (não conta para o teto)* |

---

## Regra R2a — a obrigação é declarada sem hedge e sem cláusula de exceção

> `RQ-06` · área A · **EP** + **oráculo estrutural** (contagem de exceções = 0)

```gherkin
  Regra: os quatro cenários são obrigatórios a cada nova tag, sem exceção declarada

    Cenário: [CT-03] a obrigação é incondicional e nomeia o evento que a dispara
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê a declaração de escopo no topo do documento
      Então ela diz que os quatro cenários são obrigatórios
      E nomeia a publicação de uma nova tag como o evento que os dispara
      E o mesmo período não carrega atenuação do tipo "quando possível" ou "idealmente"

    Cenário: [CT-14] a obrigação não abre exceção para nenhuma classe de tag
      Dado o roteiro de release entregue dentro do pacote
      Quando as cláusulas condicionais e de exceção da seção da obrigação são contadas
      Então a contagem é zero
```

**Por que CT-14 conta em vez de enumerar** (rodada 2, achado A2): a versão anterior proibia duas
dispensas nomeadas ("tag que altere código", "minor ou maior") — e *"tag de republicação"*, *"tag já
validada em ensaio"* e *"tag que só mexe na wiki"* passavam. É o mesmo defeito que CT-01 já havia
aprendido a evitar, e que voltou pela porta do fechamento. **O conjunto de dispensas é aberto; a
contagem de exceções é fechada.**
**Controle positivo**: o contador é exercido contra um período com uma cláusula de exceção presente.
E o filtro de citação roda antes — o roteiro **explica** por que não há dispensa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | o procedimento é documentado como recomendação ("quando possível") | CT-03 (3ª linha) |
| M8 | a obrigação é amarrada ao merge na `main`, e o `create-project` da tag nunca acontece | CT-03 (2ª linha) |
| M9 | a obrigação abre exceção para alguma classe de tag — qualquer uma | CT-14 |

---

## Regra R2b — o critério de aprovação é fechado e não infla em silêncio

> `RQ-09`, `RQ-06` · área A · **EP** + **valor limite** (n, n+1)

```gherkin
  Regra: o critério de aprovação é fechado, por cenário de validação

    @premissa
    Cenário: [CT-04] o critério é zero erro e zero falha, e pulado declarado não conta contra
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê o critério de aprovação
      Então ele define aprovação como zero erro e zero falha
      E declara que caso pulado por não se aplicar fora da árvore do kit não conta contra

    @premissa
    Cenário: [CT-13] o critério registra um número esperado de pulados por cenário de validação
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê o critério de aprovação
      Então o roteiro registra um número esperado de pulados para cada um dos quatro cenários
      E exige que uma execução acima do esperado do seu cenário seja nomeada antes de aprovada
      E não aceita como aprovação uma execução na árvore do kit

    Cenário: [CT-15] o roteiro exige o registro da evidência antes da tag de correção
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê o que precisa ficar registrado ao fim da validação
      Então o roteiro exige a versão validada, os quatro diretórios e a saída colada de cada execução
      E exige que esse registro exista antes de a tag de correção ser publicada
```

**Premissa de CT-04** — o `00` assume "liso" = zero erro e zero falha, com os pulados fora da conta.
**Se negado, CT-04 inverte.**

**Premissa de CT-13, e a objeção que a rodada 2 levantou — registrada, não descartada**: a premissa
do `00` é **anti-conservadora nesta feature** (a correção canônica desta classe de defeito *é* um
`skip`, então a premissa isenta a métrica que toda correção futura faz crescer — falha **aberto**).
CT-13 fecha o teto. **Mas a revisão objetou, com razão, em dois pontos**: (a) o `00` diz que decidir
sobre os 145 *"é outra entrega"*, e cobrar justificativa por pulado a mais é essa decisão pela porta
dos fundos; (b) sem oráculo sobre a **execução**, o teto é um número que a mesma pessoa edita no
mesmo commit em que adiciona o `skip`. Por isso CT-13 é `@premissa` e está na
[pergunta 5](#perguntas-para-o-00-requisitomd) como **acréscimo de processo que precisa de aprovação
explícita**. **Se negado, CT-13 cai — e com ele M10 e M12, não só M12.**

**O número não é o 145.** "Cogitado e Cortado" corta afirmar os números da validação da `v0.38.0`
porque envelhecem; CT-13 exige que o **roteiro declare** um esperado por cenário, e o roteiro é o
dono desse número. A contradição que a rodada 2 apontou era real na versão anterior, que falava em
"o número esperado" no singular — e os quatro cenários podem divergir legitimamente: a identidade
byte a byte da `v0.38.0` é um fato daquela release, não um invariante.

**CT-15 é o único rastro de papel de R8.** Ele não prova que a validação aconteceu — prova que o
roteiro **exige** a prova, e que a exigência é anterior à tag.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | o critério é "a suíte fica verde", que a árvore do kit já satisfaz | CT-13 (3ª linha) — **órfão se a premissa cair** |
| M11 | o critério é "100% passando", que reprova os pulados deliberados e é impossível de cumprir | CT-04 (2ª linha) |
| M12 | o critério isenta pulados **sem teto**: cada release converte erro em pulado e o roteiro segue dizendo "liso" | **parcial** — CT-13 fecha o texto; a execução fica em [L1](#l1--a-execução-dos-quatro-cenários-continua-fora-do-arnês) |
| M13 | a validação roda e nada fica registrado: nenhuma versão, nenhum diretório, nenhuma saída | **parcial** — CT-15 exige que o roteiro peça; nada verifica que foi feito |

---

## Regra R3 — os dois pontos de entrada levam ao roteiro, e o alvo do link resolve

> `RQ-05` · área A · **EP** (2 entradas) + **rastreio até o destino** + **partição da base do link**

```gherkin
  Regra: os dois pontos de entrada levam ao roteiro por um link que resolve

    Esquema do Cenário: [CT-05] o ponto de entrada tem seção que remete ao roteiro
      Dado o arquivo "<entrada>" na árvore do kit
      Quando o mantenedor procura a seção que trata do processo de release
      Então a seção existe e remete ao roteiro de release

      Exemplos:
        | entrada         | # ponto de entrada                          |
        | CONTRIBUTING.md | o que o GitHub oferece a quem chega de fora |
        | wikis/README.md | o índice de quem já está dentro da wiki     |

    Esquema do Cenário: [CT-16] o alvo do link resolve a partir do arquivo que o declara
      Dado o arquivo "<entrada>" na árvore do kit
      Quando o alvo de cada link markdown é extraído e resolvido a partir do diretório desse arquivo
      Então existe ao menos um link cujo alvo resolvido é o roteiro de release
      E nenhum link declarado para o roteiro resolve para um caminho que não existe em disco

      Exemplos:
        | entrada         |
        | CONTRIBUTING.md |
        | wikis/README.md |
```

**Por que a base do link é uma partição** (rodada 2, achado A3): CT-16 nasceu contra o 404 e não
dizia *relativo a quê* resolvia. Um link `wikis/checklist-de-release.md` escrito **dentro** de
`wikis/README.md` resolve a partir de `base_path()` e dá 404 no GitHub, que o resolve como
`wikis/wikis/…`. O oráculo resolve a partir do **diretório do arquivo que declara o link** — que é o
que o GitHub faz.

**Guarda**: CT-05 e CT-16 leem `CONTRIBUTING.md`, cujo destino está em aberto
([pergunta 1](#perguntas-para-o-00-requisitomd)). Por falha fechado, os dois levam guarda — e por
isso a linha 2 da tabela marca `wikis/README.md` como **com guarda** também: os `Esquema` são um
caso só, e a guarda vale para as duas linhas. **É uma perda declarada**: o oráculo do link no índice
da wiki não roda no projeto instalado. Fechá-la exigiria separar os dois `Esquema` em quatro casos, o
que a [pergunta 1](#perguntas-para-o-00-requisitomd) pode tornar desnecessário.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | o `CONTRIBUTING.md` cobre estilo e PR e nunca remete ao roteiro | CT-05 |
| M15 | a prosa cita o nome certo e o alvo do link não resolve | CT-16 (2ª linha) |
| M16 | o índice de `wikis/README.md` não ganha a linha | CT-05 e CT-16 (linha `wikis/README.md`) |
| M17 | o link dentro de `wikis/` é escrito relativo à raiz do repositório e dá 404 no GitHub | CT-16 (resolução a partir do diretório) |

---

## Regra R4 — o roteiro registra a medição empírica **e o limite dela**, conforme a RD-02

> `RQ-05`, `RQ-06`, `RQ-07`, **RD-02** · área A · **EP** + **rastreio da cláusula revista**

```gherkin
  Regra: o roteiro explica o que só instalar e rodar alcança, e o que a guarda estática alcança

    Cenário: [CT-06] a justificativa empírica está escrita, delimitada ao que lhe cabe
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê a justificativa do procedimento
      Então ela afirma que instalar e rodar é a única medição do que nenhuma varredura alcança
      E delimita esse "o que" ao que só existe fora da árvore do kit

    Cenário: [CT-17] o roteiro não apresenta a falha das varreduras como propriedade do problema
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor procura o que caracteriza o defeito que o procedimento pega
      Então o roteiro descreve a classe "caso de teste que viaja lendo arquivo que não viaja"
      E registra que a fatia de caminho literal no corpo do caso é decidível e tem guarda estática
      E não afirma que uma varredura não pode acertar os três mecanismos de guarda
```

**CT-17 foi invertido pela RD-02** (rodada 2, achado A4 — e é o achado mais caro das duas rodadas).
A versão anterior exigia que o roteiro registrasse *"as varreduras estáticas tentadas erraram"* e que
não oferecesse varredura como substituta. **O `00` retratou exatamente isso**: as três erraram *"por
limitação delas, não por propriedade do problema"*, e o revisor do step 6.5 escreveu uma varredura
por caso em ~25 linhas que achou **1** lacuna — a certa. Manter o texto anterior obrigaria o roteiro
a levar a próxima pessoa à conclusão que o requisito desfez. CT-17 agora cobra a **cláusula revista**:
a fatia decidível é decidível, e está guardada.

**Consequência em CT-06**: a asserção de ausência ("não oferece substituto estático") saiu. O que
resta é a **delimitação** — instalar e rodar é a medição do que nenhuma varredura alcança, e isso só
existe fora da árvore do kit. As duas metades passaram a conviver, que é o que a RD-02 decidiu.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | o roteiro vira passo a passo de comandos, sem o porquê | CT-06 (1ª linha) |
| M19 | o roteiro não delimita: "instalar e rodar pega tudo", e a guarda estática vira supérflua | CT-06 (2ª linha) |
| M20 | o roteiro conta a história da `v0.38.0` sem nomear a classe do defeito | CT-17 (1ª linha) |
| M21 | o roteiro não registra que a fatia decidível tem guarda: a próxima pessoa não sabe que ela existe e não a endurece | CT-17 (2ª linha) |
| M22 | o roteiro mantém o texto retratado — "uma varredura precisa acertar os três mecanismos, e por isso não há guarda" — e a próxima pessoa remove a guarda que a RD-02 mandou criar | CT-17 (3ª linha) — *origem: revisão adversarial, rodada 2* |

---

## Regra R5 — o roteiro chega a quem instala pelos dois canais, até o disco

> `RQ-05` (assumido) · área C (padrão) · **rastreio de efeito de entrega** (2 canais, oráculo no
> destino) + **partição do estado do destino** + controle positivo

```gherkin
  Regra: o roteiro viaja pelos dois canais e declara de quem é o processo

    @premissa
    Cenário: [CT-07] nenhuma regra de export-ignore alcança o roteiro
      Dado o roteiro presente em disco na árvore do kit
      Quando o git é consultado sobre o atributo export-ignore do caminho do roteiro
      Então o git responde "unspecified" para o caminho do roteiro
      E responde "set" para um caminho dentro de "wikis/specs", provando que a consulta discrimina

    Cenário: [CT-08] o roteiro está presente onde quem instalou o lê, e diz de quem é o processo
      Dado o projeto em que a suíte está executando, seja a árvore do kit ou uma instalação
      Quando o usuário do kit abre o roteiro de release e lê o topo
      Então o arquivo existe neste projeto
      E o topo declara que o processo descrito é de quem MANTÉM o kit

    @premissa
    Cenário: [CT-09] o roteiro está entre os caminhos que o kit:update oferece
      Dado a lista fechada de caminhos que o comando entrega
      Quando o mantenedor procura o roteiro nessa lista
      Então o caminho do roteiro está nela

    @premissa
    Esquema do Cenário: [CT-18] o kit:update deixa o roteiro em disco, qualquer que seja o destino
      Dado um projeto de versão anterior cujo roteiro em disco está "<estado>"
      Quando o usuário do kit roda o kit:update aceitando as atualizações
      Então o roteiro existe em disco no projeto, com o conteúdo da versão publicada
      E o comando não o pula em silêncio

      Exemplos:
        | estado                | # partição do destino          |
        | ausente               | quem nunca o teve              |
        | presente e idêntico   | quem já atualizou uma vez      |
        | presente e modificado | quem editou o arquivo local    |
```

**CT-08 é o canário, e é o único caso desguardado sobre o roteiro.** CT-07 detecta M23 na árvore do
kit e **está guardado** — o detector está desligado exatamente no ambiente onde M23 faz o estrago.
CT-08 roda nos dois e falha no projeto instalado se o roteiro deixar de viajar, com uma mensagem que
o instalador consegue ler. Ele afirma **só existência e destinatário**; toda asserção sobre redação
de processo leva guarda, pela [regra do canário único](#a-regra-do-canário-único).

**Por que CT-18 ganhou a partição do destino** (rodada 2, achado A5): a versão anterior tinha
`Dado um projeto sem o roteiro em disco` — e o próprio texto de justificativa listava como risco *"o
pulo quando o destino já tem um `wikis/`"*. O `Dado` eliminava justamente o ramo perigoso. Pior: o
Checklist de Taxonomia creditava a **idempotência** a um cenário que não a exercitava. Agora as três
partições do destino estão na tabela, e a idempotência é a linha `presente e idêntico`.

**Premissa de CT-07** — o `00` assume que o roteiro viaja; *"se negado, uma linha no `.gitattributes`
reverte"*. **Se negado: CT-07 inverte (passa a exigir `set`), CT-08 passa a precisar de guarda, e a
3ª linha do `Então` de CT-21 inverte junto.** Está escrito aqui, e não mais embutido no `Dado` de
CT-08 — o invariante vale para o **texto**, não para o **caso**.
**Premissa de CT-09 e CT-18** — [pergunta 2](#perguntas-para-o-00-requisitomd). **Se negada, os dois
caem** e R5 fica com CT-07 e CT-08.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | uma regra de `export-ignore` alcança `wikis/*.md`: o roteiro some do `create-project` **sem quebrar mais nada** | CT-07 (na árvore) **e** CT-08 (no projeto instalado, que é onde importa) |
| M24 | o roteiro viaja e se lê como promessa a quem instalou ("a cada release **sua**, rode…") | CT-08 (2ª linha) |
| M25 | o roteiro entra na lista fechada e o comando **não o grava**: ramo de `--all`, ou lista que só reporta | ⚠️ **sem matador** — CT-18 existe e está **pulado** ([L6](#l6--a-entrega-em-disco-pelo-kitupdate-nao-e-exercitavel-aqui)) |
| M26 | o roteiro fica fora da lista fechada: quem já instalou nunca o recebe | CT-09 |
| M27 | o comando pula o arquivo quando o destino já tem um `wikis/`, sem relatar | ⚠️ **sem matador** — CT-18 existe e está **pulado** ([L6](#l6--a-entrega-em-disco-pelo-kitupdate-nao-e-exercitavel-aqui)). *origem: revisão adversarial, rodada 2* |

---

## Regra R6 — a guarda é do caso, a sentinela discrimina, a leitura não sumiu, e a existente é endurecida

> `RQ-07`, `RQ-08`, `RQ-09` e **RD-02** · área B (**completo**, Impacto 3) · **partição dos três
> mecanismos** + **controle positivo bilateral** + **exercício contra o defeito real**
>
> **Esta regra foi reescrita pela RD-02.** Deixou de ser "existe guarda?" e passou a ser
> "**a guarda que existe reprova o arranjo que a enganou?**".

```gherkin
  Regra: o caso é guardado por caso, a sentinela discrimina, e a guarda da suíte reprova o arranjo real

    Cenário: [CT-10] a guarda é do caso, e não do arquivo inteiro
      Dado o arquivo de teste do domínio local, que viaja para todo projeto instalado
      Quando o código-fonte desse arquivo é analisado por tokens
      Então o caso que lê o documento do site carrega a guarda na própria declaração do caso
      E o arquivo não tem markTestSkipped no beforeEach, que silenciaria os demais casos

    Cenário: [CT-19] a sentinela discrimina nos dois sentidos
      Dado a sentinela que decide se a suíte está na árvore do kit
      Quando ela é consultada com a âncora acessível e depois com a âncora inacessível
      Então ela responde verdadeiro no primeiro caso e falso no segundo
      E sua âncora é um dos artefatos marcados ausente na tabela ambiente x artefato
      E sua âncora não é o diretório do site, que é o que os casos guardados leem

    Cenário: [CT-20] a correção guardou a leitura, não a removeu
      Dado o arquivo de teste do domínio local, que viaja para todo projeto instalado
      Quando o código-fonte desse arquivo é analisado por tokens
      Então o caso guardado continua abrindo o documento do site para afirmar sobre o conteúdo
      E não passou a apenas citar o caminho do documento como string

    Cenário: [CT-22] a guarda da suíte reprova o arranjo que a enganou na v0.38.0
      Dado um arquivo de teste em que a sentinela aparece num caso e outro caso lê documento sem ela
      Quando a guarda de documentação da suíte é executada sobre esse arquivo
      Então ela reprova, nomeando o caso desprotegido
      E reprova mesmo com a sentinela a centenas de linhas de distância do caso desprotegido

    Cenário: [CT-23] a guarda endurecida declara a fatia que não alcança
      Dado a guarda de documentação da suíte, depois de endurecida
      Quando o mantenedor lê o que ela cobre
      Então ela decide sobre caminho literal no corpo do caso
      E declara em texto a fatia que não decide, em vez de reprovar por suspeita
```

**CT-22 é a cláusula nova do `00`, e é o cenário mais importante de R6.** O `00` mede: o
`RedeDeDocumentacaoTest [CT-10]` já era guarda estática, cobrava a sentinela com `str_contains` sobre
o **arquivo inteiro**, o `HostLocalTest` **continha** `naArvoreDoKit()` no `[CT-34]` — 750 linhas
abaixo do `[CT-12]` desprotegido — e **passou durante toda a `v0.38.0` com o defeito presente**.
CT-22 é a "mutação contra o defeito real" que a RD-02 exige, e é expressável sem o plugin de mutação:
monta-se o arranjo e exige-se o vermelho. **Sem CT-22, a entrega pode corrigir o caso e deixar a
guarda exatamente como estava — e o próximo `HostLocalTest` atravessa idêntico.**

**CT-23 é o contrapeso, e vem do argumento original do usuário**: *"guarda que erra é pior que guarda
nenhuma, porque dá falsa segurança e vira ruído que se aprende a ignorar"*. A RD-02 não revogou esse
argumento — revogou a premissa de que **toda** varredura erra. A guarda endurecida cobre a fatia
decidível e **declara** a que não cobre, em vez de reprovar por suspeita. Sem CT-23, M33 fica vivo e
o argumento do usuário volta a valer contra a própria correção.

**Por que CT-19 é bilateral** (rodada 2, achado C1): a versão anterior afirmava só o ramo verdadeiro,
na árvore do kit — onde a sentinela correta e `fn () => true` respondem igual. O controle positivo
pela metade deixava o defeito de WI-3 sobreviver **dentro do cenário criado para matá-lo**. E o
escopo da 2ª linha foi fechado: a âncora vem da **tabela**, um artefato nomeado, não de "algum caso
desta suíte" — que era ou quase vazio ou a varredura genérica.

**`token_get_all()`, não regex**: uma das três varreduras do `00` errou por não atravessar chaves
aninhadas, e o `.ai/rules/testes.md` registra que regex conta comentário como chamada. CT-10, CT-20,
CT-21 e CT-22 analisam PHP por tokens, como `HelpersDeTesteTest` já faz.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | a correção **apaga o caso inteiro**: os quatro cenários ficam limpos e o kit perde a conferência | CT-10 e CT-20 |
| M29 | a guarda vai para o `beforeEach`, e os casos que conferem código entregue param de rodar lá fora | CT-10 (2ª linha) |
| M30 | a guarda é `->skip()` por caso, correta na forma, com a sentinela ancorada no próprio `docs/` — auto-anulante | CT-19 (2ª e 3ª linhas) |
| M31 | a correção troca a leitura por uma citação do caminho: a suíte fica limpa e a asserção documental some | CT-20 |
| M32 | o caso é corrigido e **a guarda da suíte continua com `str_contains` sobre o arquivo inteiro** — o arranjo que enganou na `v0.38.0` segue passando | CT-22 — *origem: `00`, RD-02* |
| M33 | a guarda endurecida tenta decidir o indecidível, erra, e vira ruído que se aprende a ignorar — o argumento original do usuário, agora contra a própria correção | CT-23 — *origem: `00`, RD-02* |

---

## Regra R7 — sai uma versão de correção, coerente e descrita

> `RQ-08` · área E · **EP**

```gherkin
  Regra: a versão de correção é publicada de forma coerente, e o CHANGELOG descreve o que ela corrige

    Cenário: [CT-11] a publicação é coerente e a seção descreve a correção
      Dado a tag de correção sendo publicada
      Quando o gate de release confere a publicação
      Então a versão marcada em config/kit.php é a mesma da tag
      E o CHANGELOG.md tem a seção daquela versão
      E essa seção registra o caso corrigido, e não apenas o número da versão
```

**Materializado por gate existente, sem caso Pest novo**: `.github/workflows/release.yml` compara a
tag, o marcador de `config/kit.php` e a seção do `CHANGELOG.md`. Um caso Pest equivalente teria de
ler o `CHANGELOG.md`, que **não viaja** — criando uma instância nova do defeito desta wiki para
provar o que a CI já prova antes de a tag existir.
**A terceira linha não está coberta pelo gate**: ele confere a existência da seção, não o conteúdo.
É M36, e é [L3](#l3--o-conteúdo-da-seção-do-changelog-não-é-conferido-por-gate-nenhum).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M34 | a versão sobe em `config/kit.php` e a tag não é publicada | CT-11 (o gate roda sobre a tag) |
| M35 | a tag sai sem seção no `CHANGELOG.md` | CT-11 |
| M36 | a seção existe e está vazia, ou não menciona o caso corrigido | ⚠️ **sem matador** — [L3](#l3--o-conteúdo-da-seção-do-changelog-não-é-conferido-por-gate-nenhum) |
| M37 | a versão de correção sai **sem** que os quatro cenários tenham rodado contra ela | ⚠️ **sem matador — por decisão (ADR-05)**: o gate possível mediria a alegação, não o fato — CT-15 exige o registro; nada verifica. É RQ-09 |

---

## Regra R8 — os quatro cenários rodam liso, e esta entrega não abre um caso novo da mesma classe

> `RQ-01`…`RQ-04`, `RQ-09` · área D (**completo**) · **inspeção fechada de um arquivo**

A execução dos quatro cenários continua fora do arnês — [L1](#l1--a-execução-dos-quatro-cenários-continua-fora-do-arnês).
Mas uma metade é expressável, e declará-la impossível era falso: o argumento que justifica CT-10
(*"não é varredura: um arquivo nomeado"*) se aplica inteiro ao **único arquivo que esta entrega cria**.

```gherkin
  Regra: o arquivo de teste que esta entrega adiciona ao conjunto que viaja não repete o defeito

    Cenário: [CT-21] os casos novos respeitam a fronteira entre o que viaja e o que não viaja
      Dado o arquivo de teste desta entrega, que passa a viajar para todo projeto instalado
      E a lista de artefatos que a tabela ambiente x artefato marca ausente ou nao resolvida
      Quando as chamadas de leitura de arquivo desse arquivo de teste são levantadas por tokens
      Então todo caso que abre um artefato dessa lista carrega a guarda na própria declaração
      E todo caso que afirma sobre a redação do roteiro carrega a guarda na própria declaração
      E exatamente um caso lê o roteiro sem guarda, e é o que afirma existência e destinatário
```

**A lista de agulhas vem da tabela, e não do cenário** (rodada 2, achado D2): a versão anterior
enumerava `CONTRIBUTING.md` e `CHANGELOG.md` — dois de quatro — e o que ficava de fora era
**`docs/`**, precisamente o caminho que originou esta wiki. Um caso novo que lesse `docs/…` passaria
intocado. A lista sai da coluna fechada da tabela.

**Leitura, não menção** (rodada 2, achado D1): o oráculo levanta **chamadas de leitura de arquivo por
tokens**, não ocorrências do caminho em texto — senão CT-21 conclui que ele próprio "lê
`CONTRIBUTING.md`" (a string está no corpo dele), exige guarda em si mesmo, e passa a se pular no
único ambiente onde valeria. O caso é **membro declarado do conjunto que inspeciona**, e a análise
por tokens é o que torna isso consistente.

**A terceira linha é afirmativa, e inverte com a premissa de CT-07** (rodada 2, achado B1): se o
roteiro deixar de viajar, o canário passa a precisar de guarda e o "exatamente um sem guarda" vira
"nenhum". A reversão que o `00` chama de *"uma linha no `.gitattributes`, sem tocar em mais nada"*
custa, além dela, inverter CT-07 e esta linha — está escrito, e é o preço declarado de ter canário.

**Escopo honesto**: CT-21 cobre **um** arquivo. Não é a varredura genérica, e não protege os outros
177 — esses são de CT-22, que endurece a guarda que já existe para os 16 que ela varre.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M38 | os quatro cenários são "reexecutados" rodando a suíte na árvore do kit | ⚠️ **sem matador automatizado** — o roteiro, via CT-13 (3ª linha) |
| M39 | só os dois cenários de instalação são reexecutados; os de `kit:update` são presumidos equivalentes | ⚠️ **sem matador automatizado** — CT-01 e CT-15 deixam rastro; nada prova que os quatro rodaram |
| M40 | a reexecução acontece contra a branch, ou contra uma tag anterior | **parcial** — CT-12 mata a metade documental; a execução fica sem matador |
| M41 | a correção fecha o caso do domínio local e abre outro da mesma classe | **parcial** — CT-21 para o arquivo novo, CT-22 para os 16 que a guarda varre |
| M42 | "liso" é declarado com um erro considerado conhecido, e a tag sai | ⚠️ **sem matador automatizado** — o roteiro, via CT-04 e CT-15 |

---

## Lacunas Declaradas

**Seis** — a L6 entrou no ciclo 1 do quality gate, e este contador ficou em "cinco" até o
ciclo 2 o pegar (QA-15). **L1 e L2 encolheram** nas duas rodadas de revisão, que provaram que as duas eram
parcialmente falsas; o recuperado virou CT-12, CT-15, CT-20, CT-21, CT-22.

### L1 — a execução dos quatro cenários continua fora do arnês

**O que é**: M38, M39, M42, e as metades não documentais de M12, M13, M37 e M40.

**Por que não dá**: o observável exige `composer create-project` contra uma tag **publicada**, fora
deste repositório, quatro vezes, uma com `kit:tenancy` e duas partindo de versão anterior. O `00` põe
a automação **fora de escopo**. A RD-02 **não** mudou isto: ela reverteu a decisão sobre a guarda
estática e manteve que *"o roteiro continua sendo a guarda do que nenhuma varredura alcança — o que
só acontece fora da árvore do kit"*.

**O que foi tentado**:

| Tentativa | Por que não resolve |
|---|---|
| inverter a sentinela em runtime (`.github` renomeado) | flipa para o processo inteiro, destrutivo, dependente de ordem — e não remove `docs/`, então a sentinela auto-anulante continuaria passando. CT-19 resolve isso melhor, e sem efeito colateral |
| rodar a suíte com `docs/` renomeado | é a terceira opção levada ao usuário e recusada |
| varredura pelo `git archive` num diretório temporário | é a segunda opção recusada — e a RD-02 reabilitou a varredura **estática por caso**, não esta |
| um `create-project` em `tests/` | exige rede, tag publicada e minutos por cenário; a versão de correção não existe quando a suíte roda |

**O que foi recuperado**: a versão-alvo por marcador (**CT-12**), o rastro de papel anterior à tag
(**CT-15**), o arquivo novo não repetir o defeito (**CT-21**) e — o maior — a guarda existente
reprovando o arranjo real (**CT-22**), que a RD-02 transformou de lacuna em cláusula.

**Quem fecha o resto**: a execução manual do roteiro, registrada no `03` com a saída colada dos
quatro projetos.

### L6 — a entrega em disco pelo `kit:update` não é exercitável aqui

**O que é**: M25 e M27 — o roteiro entra na lista fechada e o comando **não o grava**, ou o grava
só quando o destino está vazio.

**Por que não dá**: `KitUpdate::handle()` opera sobre `base_path()` **fixo**, não injetável
(`app/Console/Commands/KitUpdate.php:preVoo:414`), e exige repositório git real — ele cria um
remote `kit`, busca uma tag **publicada** e aplica o diff **no próprio checkout onde a suíte
roda** (`app/Console/Commands/KitUpdate.php:git:1089`). Executá-lo de verdade destruiria a
árvore que está sendo medida. É a mesma classe de ambiente que [L1](#l1--a-execução-dos-quatro-cenários-continua-fora-do-arnês)
já declara para os quatro cenários.

**O que foi feito, e por que não é o suficiente**: CT-18 está **escrito**, com os três datasets e
`->skip()` nomeado — não foi apagado nem preenchido com asserção falsa. Isso preserva a intenção
e deixa o rastro, mas **não mata mutante nenhum**.

**Como isto entrou**: o quality gate (QA-04) achou o `04` ainda creditando M25 e M27 a CT-18
depois de ele ter sido pulado. É a forma clássica de cobertura fantasma — o caso existe, o número
fecha, e nada é medido.

**Quem fecha**: o cenário **3** do roteiro de release, que roda `kit:update --all` num projeto
instalado de verdade e confere o arquivo em disco. É oráculo empírico, como L1.

### L2 — a força da asserção documental continua fora de alcance

**O que é**: o que sobrou de M31 depois de CT-20. O **mecanismo** (o caso continua abrindo o
documento) é assertável; a **força** da asserção — se ela ainda discrimina — exigiria saber o que o
caso afirmava antes da correção.

**O que mudou**: a versão 1 declarava M31 inteiro indistinguível. **Estava errado**: mecanismo e
conteúdo são separáveis, e CT-20 fecha o mecanismo.

**Quem fecha**: o `feature-quality-gate`, comparando o antes e o depois do caso.

### L3 — o conteúdo da seção do CHANGELOG não é conferido por gate nenhum

**O que é**: M36. O gate confere que a seção **existe**; uma seção vazia passa.
**O que foi tentado**: um caso Pest sobre o `CHANGELOG.md` — que não viaja, exigiria guarda, e criaria
uma instância nova do defeito desta wiki para provar o que é do repositório.
**Quem fecha**: a dimensão de consistência documental do quality gate, ou um passo a mais no
`release.yml` — entrega de infraestrutura, não desta wiki.

### L4 — o fechamento do ciclo por `pest --mutate` não roda neste ambiente

Sem PCOV nem Xdebug; no Windows o score é falso sem o lançador `.cmd`. Os 42 mutantes são derivados à
mão. **Não é perda relevante aqui**: mutation testing só muta código que existe, e esta entrega quase
não tem código. Os mutantes que importam (M5, M12, M23, M25, M28…M33, M38…M42) são de **omissão**, e
a ferramenta é cega a eles. **A "prova por mutação" que a RD-02 exige está atendida por CT-22**, que
é mutação manual contra o defeito real — o único mutante cuja mortalidade o `00` cobra nominalmente.

### L5 — RQ-07 ("entender os erros") não tem oráculo integral, por decisão registrada

RQ-07 é restrição sobre o **processo**; o `00` a converte na seção *"por que nenhum gate pegou"* do
`03` e na decisão sobre a guarda. A metade que **é** texto virou R4 — e a RD-02 mudou o que essa
metade tem de dizer: CT-17 agora cobra a cláusula **revista**, não a retratada. A outra metade é
verificação documental do quality gate.

---

## Cobertura já existente (nenhum CT novo)

| O que | Onde | O que isso força |
|---|---|---|
| a contagem `Documentos de referência (wikis/)` nos dois READMEs | `SiteDeDocumentacaoTest [CT-48]` — **guardado por caso**, conferido | o roteiro novo em `wikis/` obriga a atualizar a contagem nos dois READMEs, ou a suíte do kit fica vermelha; e o caso não quebra no projeto instalado |
| todo `wikis/*.md` na lista fechada do `kit:update` | `KitUpdateTest`, varredura genérica | **materializa CT-09** — mas é tautológica em relação à entrega, e por isso **CT-18 é escrito à parte** |
| a coerência tag ↔ `config/kit.php` ↔ `CHANGELOG.md` | `.github/workflows/release.yml` | **materializa CT-11** (duas primeiras linhas), antes de a tag existir |
| a sentinela nos arquivos que leem documentação | `RedeDeDocumentacaoTest [CT-10]` | **não basta** — é a guarda que passou verde com o defeito presente. É o alvo de CT-22 e CT-23 |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi
> tentado}`. Nunca "sim".

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota, recurso com `{id}` nem ator autenticado |
| Autorização exercida na ação | não se aplica: nenhuma policy, permission ou gate |
| **Idempotência** (ancorada no agregado) | CT-18, linha `presente e idêntico` — o agregado é o **arquivo em disco**, não a lista. *(A versão anterior creditava isto a um cenário que não o exercitava — achado A5 da rodada 2.)* |
| Concorrência | não se aplica: um único operador. **A acumulação de papéis foi separada deste item** — [pergunta 6](#perguntas-para-o-00-requisitomd) e CT-15 |
| Fronteira no ponto de entrada | CT-01 (4 seções, nem 3 nem 5), CT-13 (esperado e esperado+1), CT-14 (exceções = 0) |
| Domínio condicionado (tipo × valor) | CT-01 — a tenancy condiciona o cenário; as quatro combinações são exaustivas |
| Estado × operação de escrita | não se aplica: nenhuma entidade com ciclo de vida. Substituído pela [tabela ambiente × artefato](#tabela-ambiente--artefato), **30 células**, uma declarada não resolvida |
| **Ausente ≠ null ≠ vazio** | CT-10, CT-19, CT-21, CT-22 (arquivo **ausente**) e CT-18 (presente porém **vazio** ou **modificado**). `null` não existe em sistema de arquivos |
| Paginação / ordenação | não se aplica: nenhuma listagem |
| Timezone / DST | não se aplica: nenhuma data comparada. A dimensão **T** é de ordem — virou CT-15 e a [pergunta 3](#perguntas-para-o-00-requisitomd) |
| Unicode / limite de varchar | não se aplica: nenhum campo persistido |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: nenhum payload |
| Upload | não se aplica |
| Precisão monetária | não se aplica: só inteiros (versão, contagem de pulados) |
| Superfície Livewire | não se aplica: o `01` declara **"Sem superfície de UI"** — a entrega é guarda em casos de teste e três arquivos de markdown |
| Estado do framework usado sem validar | não se aplica |
| IDOR por entidade | não se aplica: zero tabelas persistidas |
| Escopo com discriminante nulo | não se aplica: nenhuma query |
| Saída do estado de erro | não se aplica: nenhum `Então` é resposta HTTP |
| **Link com alvo que não resolve** | CT-16 — o análogo documental de "notificação cujo link leva a 404", com a base do link como partição |
| **Oráculo que se inspeciona** | CT-21 — leitura por tokens, e o caso é membro declarado do conjunto que mede |
| **[linha nova, deste projeto] caso de teste que viaja lendo arquivo que não viaja** | CT-10, CT-19, CT-20 (o caso que originou) · CT-22, CT-23 (a guarda da suíte) · CT-21 (o arquivo novo) · CT-17 (a classe nomeada no roteiro) |
| **[linha nova] guarda de arquivo não é guarda de caso** | CT-22 — o `str_contains` sobre o arquivo inteiro deu verde com a sentinela 750 linhas longe do caso desprotegido |

> **As duas últimas linhas são candidatas a `.ai/rules/testes.md`.** São defeitos reais deste
> projeto: o primeiro atravessou três ciclos de quality gate, um `/code-review`, um `fw-revisor-diff`
> e dois `fw-qa-gate`; o segundo passou verde uma release inteira **dentro de uma guarda que existia
> para pegá-lo**.

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Guarda | Mata |
|----|---------|-------|---------|--------|---------|--------|------|
| CT-01 | cada combinação tem seção própria; o total é quatro | R1 | EP exaustiva (`Esquema`, 4) | Kit | `ChecklistDeReleaseTest` | `naArvoreDoKit()` | M1, M2, M3 |
| CT-02 | cada cenário de update parte de versão anterior explícita | R1 | EP | Kit | idem | `naArvoreDoKit()` | M4 |
| CT-12 | os comandos fixam a versão por marcador, não por literal | R1 | partição do argumento de versão | Kit | idem | `naArvoreDoKit()` | M5, M6, M40 (parcial) |
| CT-03 | a obrigação é incondicional e nomeia a tag | R2a | EP + controle positivo | Kit | idem | `naArvoreDoKit()` | M7, M8 |
| CT-14 | a contagem de cláusulas de exceção é zero | R2a | oráculo estrutural | Kit | idem | `naArvoreDoKit()` | M9 |
| CT-04 | `@premissa` zero erro e zero falha | R2b | EP | Kit | idem | `naArvoreDoKit()` | M11 |
| CT-13 | `@premissa` esperado de pulados por cenário | R2b | valor limite (n, n+1) | Kit | idem | `naArvoreDoKit()` | M10, M12 (parcial) |
| CT-15 | o roteiro exige a evidência antes da tag | R2b | rastreio documental | Kit | idem | `naArvoreDoKit()` | M13 (parcial) |
| CT-05 | os dois pontos de entrada remetem ao roteiro | R3 | EP (`Esquema`, 2) | Kit | idem | `naArvoreDoKit()` | M14, M16 |
| CT-16 | o alvo do link resolve a partir do arquivo que o declara | R3 | rastreio + partição da base | Kit | idem | `naArvoreDoKit()` | M15, M16, M17 |
| CT-06 | a justificativa empírica, delimitada | R4 | EP + controle positivo | Kit | idem | `naArvoreDoKit()` | M18, M19 |
| CT-17 | a classe do defeito, e a fatia decidível declarada coberta | R4 | rastreio da cláusula revista | Kit | idem | `naArvoreDoKit()` | M20, M21, M22 |
| CT-07 | `@premissa` nenhum `export-ignore` alcança o roteiro | R5 | rastreio + controle positivo | Kit | idem | `naArvoreDoKit()` (sem repo git lá fora) | M23 (na árvore) |
| CT-08 | **canário** — o roteiro existe aqui e diz de quem é o processo | R5 | invariante + canário | Kit | idem | **nenhuma, de propósito** | M23 (no projeto instalado), M24 |
| CT-09 | `@premissa` o roteiro está na lista do `kit:update` | R5 | pertinência | Kit | *materializado por `KitUpdateTest`* | — | M26 |
| CT-18 | `@premissa` o comando deixa o roteiro em disco, em três destinos | R5 | rastreio até o destino + partição (`Esquema`, 3) | Kit | `ChecklistDeReleaseTest` — **escrito, pulado** | — | ⚠️ nenhum (L6) |
| CT-10 | a guarda é do caso, não do arquivo | R6 | partição dos 3 mecanismos, por tokens | Kit | idem | — | M28, M29 |
| CT-19 | a sentinela discrimina nos dois sentidos | R6 | controle positivo **bilateral** | Kit | idem | — | M30 |
| CT-20 | a correção guardou a leitura, não a removeu | R6 | partição mecanismo × conteúdo | Kit | idem | — | M28, M31 |
| CT-22 | a guarda da suíte reprova o arranjo da `v0.38.0` | R6 | exercício contra o defeito real | Kit | `RedeDeDocumentacaoTest` | — | M32 |
| CT-23 | a guarda endurecida declara a fatia que não alcança | R6 | EP | Kit | idem | — | M33 |
| CT-11 | a publicação é coerente e a seção descreve a correção | R7 | EP | CI | *materializado por `release.yml`* | n/a | M34, M35 |
| CT-21 | os casos novos respeitam a fronteira do que viaja | R8 | inspeção fechada, por tokens | Kit | `ChecklistDeReleaseTest` | — | M41 (parcial) |

**Casos Pest novos**: 21 (CT-09 é materializado por caso existente; CT-11 por gate de CI).
**Arquivos tocados**: `tests/Kit/ChecklistDeReleaseTest.php` (novo) e
`tests/Kit/RedeDeDocumentacaoTest.php` (CT-22, CT-23 — endurecer a guarda existente).

**Sem matador** (7): M25 e M27 (**L6**), M36 (L3), M37, M38, M39, M42 (L1).
**Parciais** (4): M12, M13, M40, M41.
**Órfão condicional**: M10 — se a [pergunta 5](#perguntas-para-o-00-requisitomd) for negada, CT-13 cai
e M10 fica sem matador junto com M12. A versão anterior registrava só M12; **é o achado F4 da
rodada 2**, e M10 é o mais grave dos dois, porque é o mutante que o próprio `00` nomeia como
inaceitável em RQ-09.

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| afirmar a contagem `Documentos de referência (wikis/)` nos READMEs | já provado por `SiteDeDocumentacaoTest [CT-48]`, que **já é guardado por caso** — conferido, e por isso a suspeita da rodada 2 sobre ele não procede |
| um caso Pest para a coerência tag ↔ `config/kit.php` ↔ `CHANGELOG.md` | duplicaria o `release.yml` e teria de ler o `CHANGELOG.md`, que não viaja |
| **a varredura genérica sobre os 178 arquivos** | recusada pelo `00`, e a RD-02 **não** a reabilitou: ela reabilitou a guarda **por caso** sobre a fatia decidível, que é CT-22 e CT-23 sobre a guarda que já existe |
| afirmar que o comentário do `.gitattributes` diz "doze documentos de topo" | comentário não é comportamento, e o número envelhece |
| afirmar que o roteiro cita os números da validação (2773 / 2627 / 145 / 1) | envelhecem e reprovariam entregas alheias. O que não envelhece é o **critério** (CT-04, CT-13), e por isso CT-13 fala em "um número esperado que o roteiro declara", nunca no 145 |
| um cenário por caso "protegido" dos outros 17 | **fora de escopo declarado no `00`** — e CT-22 os cobre por outro caminho, endurecendo a guarda comum |
| um cenário sobre o `kit:tenancy --force` | comando existente com suíte própria; esta entrega não o altera |
| afirmar que os quatro diretórios de validação existem em disco | mede a máquina de quem derivou, não o produto |

## Sem CT-B

Nenhum caso de browser. **Gate do `05` não passa**: o `01-plano-acao.md` declara *"Sem superfície de
UI. A entrega é uma linha de teste e três arquivos de documentação."* Não há rota, tela, JavaScript,
tema nem acessibilidade — nada que só o navegador prove. O `05-casos-de-teste-browser.md` **não é
criado**.

---

## Revisão Adversarial

Obrigatória por Impacto 3 (áreas B e D) e perfil completo (B e D). **Duas rodadas** — o teto da
skill — por sub-agente independente (`fw-adversario-ct`, `opus`), que recebeu **apenas**
`00-requisito.md` e este `04`: sem o PRD, sem o `02`, sem o código, sem o raciocínio de quem derivou.

### Rodada 1 — 5 implementações erradas que passavam pelo conjunto inteiro

| Achado | Virou |
|---|---|
| **WI-1** o roteiro pode não fixar a versão-alvo | **CT-12** |
| **WI-2** "pulado não conta" sem teto — premissa falhando **aberto** | **CT-13** + pergunta 5 |
| **WI-3** a sentinela não era afirmada: o mutante auto-anulante ressuscitava dentro do próprio matador | **CT-19** |
| **WI-4** "estar na lista" ≠ "chegar ao disco"; o materializador era tautológico | **CT-18** |
| **WI-5** CT-05 resolvia a menção em prosa, não o alvo do link | **CT-16** |
| **L1-a** M28 era matável pelo próprio argumento usado em CT-10; a "restrição dura" era só prosa | **CT-21** |
| **L1-b** a exigência de registro da evidência é texto, logo assertável | **CT-15** |
| **L2 parcialmente falsa** — mecanismo e conteúdo são separáveis | **CT-20** |
| 11 oráculos fracos; 4 cenários malformados; 7 defeitos na tabela (3 artefatos ausentes) | oráculos reescritos, `Quando` corrigidos, tabela refeita |

### Rodada 2 — a lacuna de segunda ordem, e a mudança do oráculo

A rodada 2 leu um `00` **que havia mudado durante a derivação** (RD-02) e trouxe, junto com os
achados sobre os cenários novos, a correção mais cara das duas rodadas.

| Achado | Disposição |
|---|---|
| **A4 / E6** CT-17 imortalizava a metade **retratada** do `00`; e a guarda existente (`RedeDeDocumentacaoTest [CT-10]`), que o `00` mandou **endurecer**, não tinha nenhum cenário | **aceito — o mais grave.** CT-17 invertido; **CT-22 e CT-23 criados**; R6 reescrita; área B subiu para perfil `completo` |
| **B2** onze casos afirmando sobre a **redação** do roteiro rodavam sem guarda em todo projeto instalado — o defeito desta wiki institucionalizado em onze casos novos | **aceito.** [Regra do canário único](#a-regra-do-canário-único): só CT-08 fica desguardado; CT-21 a executa |
| **A1** CT-12 aceitava versão literal, que envelhece | aceito — CT-12 passou a exigir **marcador** |
| **A2** CT-14 enumerava um conjunto aberto de dispensas | aceito — virou **contagem de exceções = 0** |
| **A3** CT-16 não fixava a base do link | aceito — resolve a partir do diretório do arquivo |
| **A5** o `Dado` de CT-18 excluía o ramo perigoso, e a idempotência era creditada sem cenário | aceito — `Esquema` com três estados do destino |
| **C1** CT-19 era controle positivo pela metade; `fn () => true` passava | aceito — **bilateral**, e a âncora vem da tabela |
| **C2 / E7** faltavam `tests/Pest.php` e `RedeDeDocumentacaoTest.php` na tabela | aceito — **15 artefatos, 30 células** |
| **D1 / D2** CT-21 se auto-inspecionava por menção, e sua lista de agulhas omitia `docs/` | aceito — leitura por **tokens**, lista vinda da tabela, membro declarado |
| **B1** CT-21 travava a reversão que o `00` chama de barata | aceito — a inversão está escrita como preço declarado do canário |
| **E1, E2, E3, E5** células com guarda errada, leitor falso ou `n/a` indevido | aceito — tabela corrigida |
| **F1…F6** contagens erradas (sem matador, atribuições de M11/M12, M9 órfão condicional, faixa de IDs no SFDIPOT) | aceito — recontado: **42 mutantes, 5 sem matador, 4 parciais, 1 órfão condicional** |
| **G1** CT-13 contradizia "Cogitado e Cortado" e importava outra entrega | **parcialmente aceito**: o número deixou de ser o 145 e passou a ser por cenário; a objeção de processo está **escalada** na pergunta 5, e CT-13 continua `@premissa` |
| **E4** `[CT-48]` dos READMEs estaria desprotegido | **refutado com evidência**: o caso já carrega `->skip(fn () => ! naArvoreDoKit(), …)`. Registrado na tabela, linhas 4 e 5 |

**Saldo das duas rodadas**: 12 cenários novos, 1 cenário invertido, 13 oráculos reescritos, 1 regra
dividida, 1 regra reescrita, 1 área reclassificada de `padrão` para `completo`, 1 tabela refeita duas
vezes, 2 lacunas encolhidas, 6 perguntas ao `00`. **Um achado refutado com evidência; nenhum
descartado em silêncio.**

**Teto de rodadas atingido.** A rodada 2 ainda trouxe achado estrutural (A4/E6), e a skill manda
**registrar e escalar** em vez de abrir a rodada 3. O escalonamento é a
[pergunta 5](#perguntas-para-o-00-requisitomd) — o único achado cuja disposição depende de decisão do
usuário — e o registro é esta seção.
