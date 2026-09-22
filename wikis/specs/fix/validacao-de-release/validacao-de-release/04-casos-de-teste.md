# Casos de Teste — Validação de release em projeto instalado

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths e a declaração `Sem superfície de UI`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — o
> `02-decisoes-arquiteturais.md` e a correção já aplicada em `tests/Kit/HostLocalTest.php` foram
> deliberadamente mantidos fora do contexto de quem derivou.
>
> **Versão 2**, depois da revisão adversarial cega. A rodada 1 achou 5 implementações erradas que
> passavam pelo conjunto inteiro, 11 oráculos fracos, 4 cenários malformados, 7 defeitos na tabela
> de ambientes e provou que **duas das quatro lacunas declaradas eram parcialmente falsas**. Tudo
> está fechado abaixo e registrado em [`## Revisão Adversarial`](#revisão-adversarial).

## Perfil de Derivação

| Área | O que é | P | I | P×I | Perfil |
|---|---|---|---|---|---|
| **A** — o roteiro documentado (RQ-05, RQ-06) | texto novo, isolado, sem runtime | 1 | 2 | 2 | mínimo |
| **B** — a correção do caso que quebra em todo projeto instalado (RQ-07, RQ-08) | integra com três mecanismos de guarda existentes | 2 | 3 | 6 | padrão |
| **C** — a entrega do roteiro pelos dois canais (RQ-05, assumido) | integra com `.gitattributes` e com a lista fechada do `kit:update` | 2 | 2 | 4 | padrão |
| **D** — os quatro cenários rodando liso contra a versão publicada (RQ-01…RQ-04, RQ-09) | ambiente externo, quatro instalações, dois eixos | 3 | 3 | 9 | completo |
| **E** — a versão de correção (RQ-08) | bump de versão, já governado por gate existente | 1 | 2 | 2 | mínimo |

**Por que Impacto 3 na área B**: o defeito não degrada nada dentro do kit — ele quebra a suíte de
**todo projeto de terceiro** nascido da tag, e a única correção possível é publicar outra tag. A tag
ruim fica publicada para sempre.
**Por que Impacto 3 na área D**: é o critério de aceite da entrega inteira, e é a única barreira
entre o defeito e quem instala.

- **Revisão adversarial**: disparada por Impacto 3 na área B **e** por perfil completo na área D.
  Duas rodadas executadas por sub-agente cego. Ver [`## Revisão Adversarial`](#revisão-adversarial).
- Técnicas aplicadas: **EP exaustiva** (as quatro combinações de RQ-01…RQ-04), **tabela
  ambiente × artefato** (produto cartesiano que substitui a matriz estado × operação), **rastreio de
  efeito de entrega até o destino** (dois canais), **controle positivo** em toda asserção de ausência
  e em toda sentinela, **partição sobre os três mecanismos de guarda** do `00`.
- Cenários: **21** · Regras: **9** · Mutantes previstos: **36** · Sem matador: **6**

> **A superfície testável desta feature é pequena e incomum, e isso é um fato do requisito.** A
> maior parte da entrega é texto; o critério de aceite principal (RQ-09) **só é observável fora da
> árvore do kit**. A seção [`## Lacunas Declaradas`](#lacunas-declaradas) é a parte mais importante
> deste arquivo — não o apêndice dele.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | Nenhum model, migration, policy, rota, job ou comando novo. Os artefatos estão listados um a um na [tabela ambiente × artefato](#tabela-ambiente--artefato) — são **13** | CT-01…CT-21 |
| **F** | Nenhuma função de runtime. As três funções da entrega são: **instruir** (o roteiro), **entregar** (os dois canais até quem instala) e **não quebrar** (a suíte que viaja não pode ler arquivo que não viaja) | CT-01…CT-17 · CT-07…CT-09, CT-18 · CT-10, CT-19…CT-21 |
| **D** | Texto em markdown; o atributo `export-ignore` resolvido pelo git; a lista fechada de caminhos; a versão; **a contagem de casos pulados**. **Cardinalidade é oráculo** em dois lugares: são quatro cenários (nem três nem cinco) e é um número fixo de pulados (que não pode inflar em silêncio). **Dado ausente é o defeito**: o arquivo que existe na árvore do kit e não no projeto instalado | CT-01 · CT-13 · CT-10, CT-21 |
| **I** | **Sem superfície de UI** (declarado no `01`). Pontos de entrada humanos: `CONTRIBUTING.md`, `wikis/README.md`, os dois READMEs. Pontos de entrada de máquina: `composer create-project` (governado pelo `.gitattributes`) e `php artisan kit:update` (governado pela lista fechada) | CT-05, CT-16 · CT-07, CT-09, CT-18 |
| **P** | **É a dimensão onde o defeito mora.** O mesmo código roda em dois ambientes que diferem pelo recorte do `git archive`: a árvore do kit (tem `.github`, `docs/`, `CHANGELOG.md`, repositório git) e o projeto instalado (não tem nenhum dos quatro). Também: `git check-attr` **não responde fora de um repositório git**, e é por isso que o detector de M20 é duplo — um guardado (CT-07) e um desguardado (CT-08) | CT-07, CT-08, CT-10, CT-21 · toda a tabela |
| **O** | Um único operador: o mantenedor do kit, uma vez por tag. **Acumulação de papéis**: quem publica a tag é a mesma pessoa que declara "liso" — não há segundo par de olhos, e nenhuma automação separa os dois atos. Usos indevidos plausíveis: pular o roteiro; rodar só o cenário sem tenancy; rodar na árvore do kit; rodar contra a branch em vez da tag | CT-03, CT-04, CT-15 · [pergunta 7](#perguntas-para-o-00-requisitomd) |
| **T** | A ordem é fixa e **assimétrica**: publicar a tag → validar → corrigir → publicar outra tag. O `composer create-project … vX` exige a tag já publicada, então **toda validação é post-mortem e toda correção é outra tag**. A ordem *validar antes de publicar a correção* é exigida por CT-15 e não é verificável por máquina. Nenhum timezone, nenhuma concorrência | CT-15 · [pergunta 4](#perguntas-para-o-00-requisitomd) |

---

## Tabela ambiente × artefato

Não há entidade com ciclo de vida, então **não existe matriz estado × operação** — declarado, não
omitido. O eixo que a substitui é o que produziu o defeito: o mesmo arquivo de teste roda em dois
ambientes com conteúdos de disco diferentes.

**Produto cartesiano fechado: 13 artefatos × 2 ambientes = 26 células.** Uma célula está
**não resolvida**, e está declarada como tal.

**Legenda, auditada célula a célula**
- **presente** = `file_get_contents(base_path(…))` devolve conteúdo naquele ambiente
- **ausente** = o `git archive` do `composer create-project` não o exporta
- **com guarda** = o caso Pest que o lê carrega `->skip(fn (): bool => ! naArvoreDoKit(), …)`
  **no caso**, nunca no `beforeEach` do arquivo
- **não resolvida** = o `00` não determina, e a pergunta continua bloqueando
- A legenda **não** se aplica a mecanismo (comando), que tem tabela própria logo abaixo

| # | Artefato | Árvore do kit | Projeto instalado | CT que o lê | Guarda |
|---|---|---|---|---|---|
| 1 | `wikis/checklist-de-release.md` | presente | **presente** — `wikis/*.md` está fora do `export-ignore` | CT-01…CT-04, CT-06, CT-08, CT-12…CT-17 | **sem guarda**, de propósito: são eles o raio de alcance de M20, e CT-08 é o canário |
| 2 | `wikis/README.md` | presente | presente — é o único `wikis/` que a lista fechada já trazia | CT-05, CT-16 | sem guarda |
| 3 | `CONTRIBUTING.md` | presente | **não resolvida** — [pergunta 1](#perguntas-para-o-00-requisitomd) | CT-05, CT-16 | **com guarda**, por falha fechado |
| 4 | `README.md` | presente | presente, mas **passa a ser do projeto** — o `kit:update` não o entrega | nenhum CT novo | — (ver [cobertura existente](#cobertura-já-existente-nenhum-ct-novo)) |
| 5 | `README.en.md` | presente | idem | nenhum CT novo | — |
| 6 | `docs/pt/comecar/dominio-local.md` | presente | **ausente** — `/docs export-ignore` | **é o defeito** — CT-10, CT-19, CT-20 leem o *arquivo de teste*, não o documento | n/a |
| 7 | `CHANGELOG.md` | presente | **ausente** — `CHANGELOG.md export-ignore` | CT-11 | **n/a — não há caso Pest**: CT-11 é materializado por gate de CI, que roda só no repositório |
| 8 | `.gitattributes` | presente | presente, porém **inerte**: sem repositório git, nada o aplica | CT-07 (indiretamente, pelo mecanismo) | com guarda |
| 9 | `config/kit.php` | presente | presente — entregue pelo `kit:update` | CT-11 | n/a (CI) |
| 10 | `tests/Kit/HostLocalTest.php` (como texto) | presente | **presente** — os 193 arquivos de teste viajam | CT-10, CT-19, CT-20 | sem guarda — ler o *arquivo de teste* é seguro nos dois ambientes |
| 11 | `app/Console/Commands/KitUpdate.php` (a lista fechada) | presente | presente — é código entregue | CT-09 | sem guarda |
| 12 | `.github/workflows/release.yml` | presente | **ausente** — `/.github export-ignore` | CT-11 | **n/a — não há caso Pest**; é o próprio gate |
| 13 | `tests/Kit/ChecklistDeReleaseTest.php` (como texto) | presente | **presente** — passa a ser o 194º arquivo que viaja | CT-21 | sem guarda |

**Mecanismos (não são arquivos; a legenda acima não se aplica)**

| Mecanismo | Árvore do kit | Projeto instalado | Consequência |
|---|---|---|---|
| `git check-attr` | responde | **não responde** — não há repositório git | segunda razão, independente do `export-ignore`, para CT-07 levar guarda |
| `php artisan kit:update` | roda | roda | é o `Quando` de CT-18, que é o único oráculo de entrega **até o disco** |

> **Esta tabela é a asserção central deste `04`.** Todo CT novo desta entrega aparece numa linha
> dela antes de virar código — e **a linha 13 é a que a rodada 1 da revisão adversarial provou que
> faltava**: o arquivo de teste que esta entrega cria entra no conjunto que viaja e lê
> `CONTRIBUTING.md`, cujo destino é indeterminado. Sem essa linha, a entrega repetiria o defeito que
> existe para corrigir. É o que CT-21 fecha.

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — o roteiro nomeia quatro cenários de validação, cruzando origem × tenancy, cada um com a versão-alvo fixada | A (mínimo) | RQ-01…RQ-05 | EP exaustiva (2×2) + partição do argumento de versão | CT-01, CT-02, CT-12 |
| **R2a** — o roteiro declara a obrigação, sem hedge e sem qualificação por tipo de tag | A (mínimo) | RQ-06 | EP + partição do disparador | CT-03, CT-14 |
| **R2b** — o critério de aprovação é fechado: zero erro, zero falha, pulados com teto e evidência registrada | A (mínimo) | RQ-09, RQ-06 | EP + valor limite sobre a contagem de pulados | CT-04, CT-13, CT-15 |
| **R3** — o roteiro é alcançável pelos dois pontos de entrada, e o **alvo do link** resolve | A (mínimo) | RQ-05 | EP (2 pontos de entrada) + rastreio até o destino do link | CT-05, CT-16 |
| **R4** — o roteiro registra que a medição é empírica, nomeando a classe do defeito e os três mecanismos | A (mínimo) | RQ-05, RQ-06, RQ-07 | EP | CT-06, CT-17 |
| **R5** — o roteiro chega a quem instala pelos dois canais, **até o disco**, declarando de quem é o processo | C (padrão) | RQ-05 (assumido) | rastreio de efeito de entrega (2 canais) + controle positivo | CT-07, CT-08, CT-09, CT-18 |
| **R6** — o caso que originou o defeito é guardado **por caso**, a sentinela é discriminante, e a leitura não desapareceu | B (padrão) | RQ-07, RQ-08, RQ-09 | partição sobre os 3 mecanismos do `00` + controle positivo na sentinela | CT-10, CT-19, CT-20 |
| **R7** — sai uma versão de correção, e os três lugares contam a mesma versão e o mesmo conteúdo | E (mínimo) | RQ-08 | EP | CT-11 |
| **R8** — os quatro cenários rodam liso contra a versão publicada, e esta entrega não abre um caso novo da mesma classe | D (completo) | RQ-01…RQ-04, RQ-09 | inspeção fechada de 1 arquivo | CT-21 (+ [lacuna L1](#l1--a-execução-dos-quatro-cenários-continua-fora-do-arnês)) |

**Técnica escalada acima do perfil da área**: R1 e R2b estão em área `mínimo` (teto de 1 cenário por
regra) e receberam três cada. Motivo declarado — **o gate vence o teto**: em R1 a cardinalidade *é* o
requisito (amostrar as quatro partições é não testar); em R2b o valor limite sobre a contagem de
pulados é o único matador de um mutante que a própria correção desta classe de defeito produz.

**R2 foi dividida em R2a e R2b** no fechamento da revisão adversarial: cinco cenários numa regra só
era o sintoma de duas regras — a *obrigação* e o *critério* são falsificáveis de forma independente.

---

## Fronteira com o Plano

O `01-plano-acao.md` foi lido **apenas** em `## Superfície de UI` e nos paths.

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `KitUpdate::CAMINHOS_DO_KIT` — o nome da lista | escolha de implementação; o comportamento é "chega a quem já instalou" | detalhe de CT-09; o oráculo real é CT-18, que afirma sobre o **disco** |
| "a lista ganha `wikis/checklist-de-release.md`" | **comportamento visível** que só o plano determina — o `00` diz "viaja, como o `roadmap.md`" e descreve só o canal do `export-ignore` | **pergunta 3** + CT-09/CT-18 `@premissa` |
| "o comentário do `.gitattributes` passa de onze para doze documentos de topo" | comentário não é comportamento, e o número envelhece a cada arquivo novo | não vira `Então` |
| `v0.38.1` — o número da versão de correção | RQ-08 diz "uma versão de correção", sem número | detalhe de CT-11 |
| `tests/Kit/HostLocalTest.php` — o path | o `00` já nomeia o arquivo e o caso | pode virar `Então` (CT-10, CT-19, CT-20) |
| `wikis/checklist-de-release.md`, `CONTRIBUTING.md` | o `00` os **DECIDIU** em `## Ambiguidades`, com o usuário | podem virar `Então` |
| `## Superfície de UI` → **"Sem superfície de UI"** | é exatamente para isso que o plano serve | **apaga o `05`** |

Não foi lido: `02-decisoes-arquiteturais.md`, o raciocínio de quem planejou, e o diff de
`tests/Kit/HostLocalTest.php`.

---

## Perguntas para o `00-requisito.md`

> **Desvio declarado**: o `00` foi tratado como **somente leitura** nesta derivação. O bloco abaixo
> está pronto para colagem em `## Ambiguidades e Perguntas Abertas`. As perguntas **continuam
> bloqueando** o que delas depende.

```markdown
- **RQ-05 — o `CONTRIBUTING.md` viaja para o projeto instalado?**
  O `.gitattributes` não o alcança hoje, então ele viaja por padrão — mas os três motivos escritos
  para criá-lo (botão de contribuir, template de PR, página de insights) são todos do
  **repositório**. É o mesmo par de perguntas do `CHANGELOG.md`, que É `export-ignore`.
  **Assumido (falha fechado)**: **não viaja** — CT-05 e CT-16 são guardados por `naArvoreDoKit()`.
  A guarda é inofensiva se ele viajar; a falta dela repete o defeito desta wiki se ele não viajar.

- **RQ-07 / guarda automatizada — a decisão "não haverá" alcança o caso nomeado?**
  As três opções recusadas eram **varreduras genéricas** sobre a suíte inteira. Um oráculo sobre o
  caso que originou o defeito (CT-10, CT-19, CT-20) e sobre o único arquivo que esta entrega cria
  (CT-21) é fechado e nomeado — não pode errar do jeito que as três erraram.
  **Assumido (falha fechado)**: **está fora da decisão**. Se a resposta for "está dentro", CT-10,
  CT-19, CT-20 e CT-21 viram lacuna declarada, e R6 e R8 ficam sem nenhum cenário.

- **RQ-05 — "viaja, como o `roadmap.md`" inclui o segundo canal?**
  "Viajar" tem dois caminhos: o `.gitattributes` serve quem instala **agora**, e a lista fechada
  serve quem **já instalou**. O texto do `00` descreve só o primeiro.
  **Assumido (falha fechado)**: **inclui os dois**.

- **RQ-06 / RQ-09 — a validação é sempre post-mortem?**
  O `composer create-project … vX` exige a tag **publicada**. Toda validação é depois da tag, e toda
  correção é outra tag. É o desenho aceito, ou o roteiro deveria rodar contra uma tag de ensaio?

- **RQ-09 — liso contra qual versão, e há teto?**
  Se a reexecução contra a versão de correção revelar um quinto defeito da mesma classe, RQ-09
  obriga outra versão, e assim por diante. Existe teto, ou o critério é "até fechar"?

- **RQ-09 — "pulado declarado não conta" tem teto?** (achado da revisão adversarial)
  A premissa registrada é **anti-conservadora nesta feature**: a correção canônica desta classe de
  defeito **é adicionar um `skip`**. Sem teto, cada release converte erro em pulado e o roteiro
  segue declarando "liso" — 145 → 160 → 200, sem nada vermelho. É falha **aberto**, não fechado.
  **Assumido (falha fechado, contra a leitura branda)**: o critério fixa o número esperado de
  pulados e exige justificativa escrita para cada aumento (CT-13). **Se negado, CT-13 cai** e M11
  fica sem matador.

- **RQ-09 — quem declara "liso" é quem publica a tag. Há segundo par de olhos?**
  Não há automação separando os dois atos, e o `00` não pede revisão independente. O par de papéis
  *executor da validação × publicador da tag* não tem cenário possível enquanto for a mesma pessoa.
  Mitigado, não resolvido, por CT-15 (a evidência fica registrada e é auditável depois).
```

---

## Setup Global

### Suíte e camada
- **Toda esta entrega é asserção sobre arquivo em disco.** Sem persona, fixture de banco, fake de
  fila ou ator autenticado.
- Camada: **`tests/Kit`** (`Tests\TestCase` + `RefreshDatabase` + grupo `kit`, ligados em
  `tests/Pest.php`). Arquivo: `tests/Kit/ChecklistDeReleaseTest.php`.
- **`tests/Unit` não está ligado** ao `TestCase` da aplicação em `tests/Pest.php` (conferido: só
  `Feature`, `Kit`, `Tenancy`, `Browser` e `BrowserTenancy` têm `->in(…)`). Nenhum cenário vai para
  lá, mesmo os que pareceriam "unitários".

### Restrição dura desta entrega (vem do próprio defeito) — e **ela é testada**, não só escrita
> Todo CT novo que leia um artefato marcado **ausente** ou **não resolvida** na
> [tabela ambiente × artefato](#tabela-ambiente--artefato) carrega a sentinela `naArvoreDoKit()`
> **no caso**, não no arquivo. O CT que lê `wikis/checklist-de-release.md` **não** leva guarda: ele
> viaja, e é CT-08 que o prova de dentro do projeto instalado.
>
> Guarda de arquivo **não** é guarda de caso — achado literal do `00`. Um `beforeEach` no arquivo
> novo silenciaria também os casos que precisam continuar rodando lá fora.
>
> **A rodada 1 da revisão adversarial provou que esta restrição, sozinha, é prosa.** CT-21 a
> executa: ele lê `tests/Kit/ChecklistDeReleaseTest.php` como texto e reprova se ela for violada.

### Controle positivo é obrigatório em três lugares
1. **Toda asserção de ausência** (CT-01, CT-02, CT-03, CT-06, CT-08) — porque a forma negativa de
   `toContain()` **nunca falha** e o markdown do kit cita o que proíbe. Cada uma roda depois de um
   `Então` positivo sobre o mesmo texto, e o cenário declara o controle.
2. **A consulta ao `git`** (CT-07) — um `git check-attr` que não responde devolve o mesmo
   `unspecified` de um caminho inexistente.
3. **A sentinela `naArvoreDoKit()`** (CT-19) — foi o achado WI-3: o conjunto exigiu controle
   positivo para o `git` e o dispensou para a sentinela, onde mora o mutante auto-anulante.

### Armadilhas do projeto que invalidam estes CT
| Armadilha | Onde ela morde aqui |
|---|---|
| `toContain($x, $msg)` é **variádico** — o 2º argumento é outra agulha | todo CT afirma sobre texto. Use `assertStringContainsString(...)`. A forma negativa **nunca falha** e é a perigosa (`.ai/rules/testes.md`) |
| asserção de **ausência** sobre arquivo documentado reprova pela própria documentação | CT-02, CT-03, CT-06 e CT-08 afirmam ausência sobre um markdown que **cita** o que proíbe. Filtre comentário/citação antes |
| regex sobre o `.gitattributes` em vez de `git check-attr` | falso negativo demonstrado: `wikis/** export-ignore` e `*.md export-ignore` removem o arquivo e o regex não os reconhece |
| asserção sobre a **seção do topo** do `CHANGELOG.md` expira sozinha | CT-11: arquivo inteiro, ou leia a versão de `config('kit.version')` |
| `git check-attr` fora de repositório git | razão independente do `export-ignore` para CT-07 levar guarda — e motivo de CT-08 existir desguardado |
| link markdown ≠ menção em prosa | CT-16: extraia o **alvo** do link e resolva-o; achar a string na prosa é o falso ✅ (WI-5) |

### Divergência declarada: skill × ambiente do projeto
- `pest --mutate` **não roda aqui**: sem PCOV e sem Xdebug. Os 36 mutantes são derivados à mão,
  como o passo 6 manda. Ver [L4](#l4--o-fechamento-do-ciclo-por-pest---mutate-não-roda-neste-ambiente).
- O `--filter` do Pest casa a **descrição** do `it()`, não o `[CT-nn]`. Confira o campo `tests:` da
  saída para saber se algo executou.

---

## Regra R1 — quatro cenários, cruzando origem com tenancy, cada um com a versão-alvo fixada

> `RQ-01`…`RQ-05` · área A (mínimo, escalada) · técnica: **EP exaustiva** (2×2) + **partição do
> argumento de versão** (tag fixada × ausente × branch)

```gherkin
# language: pt
Funcionalidade: Validação de release em projeto instalado

  Regra: o roteiro descreve quatro cenários de validação, um por combinação de origem e tenancy

    Esquema do Cenário: [CT-01] cada combinação tem uma seção própria e distinta no roteiro
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
      E a versão de origem de cada seção é menor que a versão sendo validada
      E nenhuma seção manda rodar o kit:update sobre o projeto recém-criado da versão corrente

    Cenário: [CT-12] os quatro cenários fixam a versão-alvo no próprio comando
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê os comandos de criação das quatro seções
      Então cada comando fixa uma versão explícita, e não "a última" nem um nome de branch
      E o roteiro diz que a versão fixada é a tag que está sendo validada
```

**Por que CT-01 conta seções e não procura tokens** (achado da rodada 1): um único parágrafo
contendo `create-project`, `kit:update`, "com tenancy" e "sem tenancy" satisfaz as quatro linhas de
um oráculo de co-ocorrência — e um roteiro com **um** cenário de update dizendo *"repita com e sem
tenancy"* faria RQ-04 desaparecer sem nada ficar vermelho. O oráculo é **estrutural**: uma seção por
combinação, e a contagem total.
**Controle positivo de CT-01**: o contador de seções é exercido contra um texto com uma quinta
seção, e tem de reprovar. Sem isso, "o total é quatro" passa num documento vazio.

**Por que CT-12 existe** (achado WI-1): um roteiro que mande `composer create-project vendor/pacote
proj` **sem versão** valida o que o Packagist servir naquele minuto — cache, `dev-main`, ou a tag
anterior — e nunca a tag que está sendo validada. Os dois cenários de instalação limpa ficariam
verdes sem jamais tocar a release. É a metade documental de M34, e é assertável sobre texto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o roteiro lista dois cenários — "instalação" e "atualização" — e trata a tenancy como observação no corpo | CT-01 (uma seção por combinação) |
| M2 | o roteiro cruza os eixos mas esquece o `kit:update` **com** tenancy, o mais caro de montar | CT-01 (linha 4 + contagem) |
| M3 | um único cenário de update dizendo "repita com e sem tenancy" | CT-01 (contagem de seções == 4) |
| M4 | o roteiro manda rodar o `kit:update` no projeto recém-criado da versão corrente | CT-02 (3ª linha do `Então`) |
| M5 | os comandos não fixam versão: o roteiro valida o que o Packagist servir | CT-12 |

---

## Regra R2a — a obrigação é declarada, sem hedge e sem qualificação por tipo de tag

> `RQ-06` · área A (mínimo) · técnica: **EP** + partição do disparador

```gherkin
  Regra: os quatro cenários são obrigatórios a cada nova tag publicada, sem exceção declarada

    Cenário: [CT-03] a obrigação é incondicional e nomeia o evento que a dispara
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê a declaração de escopo no topo do documento
      Então ela diz que os quatro cenários são obrigatórios
      E nomeia a publicação de uma nova tag como o evento que os dispara
      E o mesmo período não carrega nenhuma atenuação do tipo "quando possível" ou "idealmente"

    Cenário: [CT-14] a obrigação não é qualificada por tipo de tag
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor procura uma classe de tag dispensada da validação
      Então a declaração não restringe a obrigação a tag que altere código
      E não restringe a obrigação a release minor ou maior
```

**Controle positivo** de CT-03 (3ª linha) e de CT-14: as duas asserções são de ausência sobre um
texto que **cita** o que proíbe (o roteiro explica por que não há dispensa). O filtro de citação é
aplicado antes, e o oráculo é exercido contra um texto com a atenuação presente.

**Por que CT-14 é uma partição, e não zelo**: o defeito desta wiki nasceu numa release que mudou
documentação e código de teste. Uma dispensa para *"tag que só mexe em docs"* teria deixado
exatamente ela de fora.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | o procedimento é documentado como recomendação ("quando possível", "idealmente") | CT-03 (3ª linha) |
| M7 | a obrigação é amarrada ao merge na `main`, não à publicação da tag — e o `create-project` da tag nunca acontece | CT-03 (2ª linha) |
| M8 | a obrigação vale só para tags que alterem código, ou só para minor e maior | CT-14 |

---

## Regra R2b — o critério de aprovação é fechado: zero erro, zero falha, pulados com teto, evidência registrada

> `RQ-09`, `RQ-06` · área A (mínimo, escalada) · técnica: **EP** + **valor limite sobre a contagem de
> pulados** (145 / 146 / n+k)

```gherkin
  Regra: o critério de aprovação é fechado e não pode inflar em silêncio

    @premissa
    Cenário: [CT-04] o critério é zero erro e zero falha, e pulado declarado não conta contra
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê o critério de aprovação dos quatro cenários
      Então ele define aprovação como zero erro e zero falha
      E declara que caso pulado por não se aplicar fora da árvore do kit não conta contra

    @premissa
    Cenário: [CT-13] o critério fixa o número esperado de pulados e cobra justificativa por aumento
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê o critério de aprovação dos quatro cenários
      Então ele registra o número esperado de casos pulados
      E exige justificativa escrita para cada caso pulado a mais que o esperado
      E não aceita como aprovação uma execução na árvore do kit

    Cenário: [CT-15] o roteiro exige o registro da evidência antes da tag de correção
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê o que precisa ficar registrado ao fim da validação
      Então o roteiro exige a versão validada, os quatro diretórios e a saída colada de cada execução
      E exige que esse registro exista antes de a tag de correção ser publicada
```

**Premissa de CT-04** — o `00` assume que *"liso"* = zero erro e zero falha, com os 145 pulados
deliberados fora da conta. **Se negado, CT-04 inverte.**
**Premissa de CT-13 e a direção que a revisão adversarial corrigiu** (achado WI-2): a premissa do
`00` é, nesta feature, **anti-conservadora** — a correção canônica desta classe de defeito **é
adicionar um `skip`**, então a premissa isenta exatamente a métrica que toda correção futura faz
crescer. É falha **aberto**. CT-13 restaura a direção fechada pelo único caminho que não contradiz o
`00`: aceita a isenção e **fecha o teto**. [Pergunta 6](#perguntas-para-o-00-requisitomd) registra a
divergência. **Se negado, CT-13 cai e M11 fica sem matador.**

**Invariante das duas leituras, afirmado em CT-13**: qualquer que seja a decisão sobre os pulados, o
critério **não** pode ser satisfeito por uma execução na árvore do kit — lá ele é verde por
construção, e o próprio `00` diz que RQ-09 *"não é satisfeito por suíte verde na árvore do kit"*.

**CT-15 é o único rastro de papel de R8** (achado L1-b). Ele não prova que a validação aconteceu —
prova que o roteiro **exige** a prova, e que a exigência é anterior à tag. É a diferença entre um
processo auditável e um indistinguível de nada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | o critério é "a suíte fica verde", que a árvore do kit já satisfaz | CT-13 (3ª linha) |
| M10 | o critério é "100% dos testes passando", que reprova os pulados deliberados e torna o roteiro impossível de cumprir | CT-04 (2ª linha) |
| M11 | o critério isenta pulados **sem teto**: cada release converte erro em pulado e o roteiro segue dizendo "liso" | CT-13 (1ª e 2ª linhas) |
| M12 | a validação roda e nada fica registrado: nenhuma versão, nenhum diretório, nenhuma saída | CT-15 |

---

## Regra R3 — o roteiro é alcançável pelos dois pontos de entrada, e o alvo do link resolve

> `RQ-05` · área A (mínimo) · técnica: **EP** (2 pontos de entrada) + **rastreio até o destino do link**

```gherkin
  Regra: os dois pontos de entrada do repositório levam ao roteiro por um link que resolve

    Esquema do Cenário: [CT-05] o ponto de entrada tem uma seção que remete ao roteiro
      Dado o arquivo "<entrada>" na árvore do kit
      Quando o mantenedor procura a seção que trata do processo de release
      Então a seção existe e remete ao roteiro de release

      Exemplos:
        | entrada         | # ponto de entrada                          |
        | CONTRIBUTING.md | o que o GitHub oferece a quem chega de fora |
        | wikis/README.md | o índice de quem já está dentro da wiki     |

    Esquema do Cenário: [CT-16] o alvo do link, e não a menção em prosa, é o roteiro
      Dado o arquivo "<entrada>" na árvore do kit
      Quando o alvo de cada link markdown declarado no arquivo é extraído e resolvido em disco
      Então existe ao menos um link cujo alvo resolve para "wikis/checklist-de-release.md"
      E nenhum link declarado para o roteiro aponta para um caminho que não existe em disco

      Exemplos:
        | entrada         |
        | CONTRIBUTING.md |
        | wikis/README.md |
```

**Por que CT-16 foi separado de CT-05** (achado WI-5): a versão anterior afirmava *"o arquivo cita o
caminho"* e *"o caminho citado resolve"* — e resolvia **a string que tinha achado na prosa**, não o
alvo do link. Um arquivo que escreva `` `wikis/checklist-de-release.md` `` em code-span e linke
`[checklist](wikis/checklist-release.md)` passa nos dois `Então` e dá 404 no GitHub. O oráculo tem
de **extrair o alvo do link** e resolvê-lo. É o mesmo padrão do "e-mail com link que leva a 404".

**Guarda**: CT-05 e CT-16 leem `CONTRIBUTING.md`, cujo destino no projeto instalado está em aberto
([pergunta 1](#perguntas-para-o-00-requisitomd)). Por falha fechado, os dois levam
`->skip(fn (): bool => ! naArvoreDoKit(), …)` — que é inofensiva se o arquivo viajar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | o `CONTRIBUTING.md` é criado, cobre estilo e PR, e nunca remete ao roteiro | CT-05 (linha `CONTRIBUTING.md`) |
| M14 | o link existe, a prosa cita o nome certo, e o alvo aponta para um caminho que não resolve | CT-16 (2ª linha) |
| M15 | o índice de `wikis/README.md` não ganha a linha: o roteiro só é alcançável por quem já sabe que ele existe | CT-05 e CT-16 (linha `wikis/README.md`) |

---

## Regra R4 — o roteiro registra que a medição é empírica, nomeando a classe do defeito

> `RQ-05`, `RQ-06`, `RQ-07` · área A (mínimo) · técnica: **EP**
> Origem literal no `00`: *"A única medição confiável foi instalar e rodar. […] é o argumento central
> de RQ-05 e RQ-06, e **precisa estar escrito no roteiro**."*

```gherkin
  Regra: o roteiro explica por que instalar e rodar é a única medição confiável

    Cenário: [CT-06] a justificativa está escrita e não oferece substituto estático
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor lê a justificativa do procedimento
      Então ela afirma que instalar e rodar é a única medição confiável desta classe de defeito
      E não oferece nenhuma varredura estática como substituta do procedimento
      E onde cita a guarda estática que existe, declara que ela cobre outra classe

    Cenário: [CT-17] o roteiro nomeia a classe do defeito e os três mecanismos de guarda
      Dado o roteiro de release entregue dentro do pacote
      Quando o mantenedor procura o que caracteriza o defeito que o procedimento pega
      Então o roteiro descreve a classe "caso de teste que viaja lendo arquivo que não viaja"
      E nomeia os três mecanismos de guarda do kit: por caso, por arquivo e por não-leitura
      E registra que as varreduras estáticas tentadas erraram por não enxergar um deles
```

**Por que CT-17 existe** (achado RQ-07 da rodada 1): CT-06 sozinho exigia *"as varreduras erraram"* —
uma frase genérica satisfaz. O que **transfere** o entendimento para a próxima pessoa é a **classe
do defeito nomeada** e os **três mecanismos**, que é exatamente o que o `00` descobriu e o que as
três varreduras não enxergaram. É a metade testável de RQ-07.
**Controle positivo** da 2ª linha de CT-06: asserção de ausência sobre um texto que descreve as
varreduras recusadas — filtre a citação antes de afirmar que não há oferta.

> **Terceira linha acrescentada pelo achado RD-02 do step 6.5.** Quando este `04` foi derivado, a
> decisão registrada era *"não haverá guarda estática"*, e CT-06 podia se contentar com a ausência.
> A decisão mudou: **existe** guarda estática (`[CT-11]` do `RedeDeDocumentacaoTest`), ela cobre a
> fatia decidível, e o roteiro agora a cita. Ausência deixou de ser o oráculo certo — o texto tem
> de **distinguir** a guarda que existe do procedimento que ela não substitui. Sem esta linha, um
> roteiro que citasse o `[CT-11]` como atalho (*"se o CT-11 está verde, pode pular os quatro
> cenários"*) passaria em CT-06.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | o roteiro vira um passo a passo de comandos, sem o porquê | CT-06 (1ª linha) |
| M17 | o roteiro sugere uma varredura pelo `git archive` "para quem tiver pressa" | CT-06 (2ª linha) |
| M18 | o roteiro cita "instalar e rodar" como **uma** das formas de medir | CT-06 (1ª linha) |
| M19 | o roteiro conta a história da `v0.38.0` sem nomear a classe do defeito nem os três mecanismos: a próxima pessoa não reconhece o caso seguinte | CT-17 |
| M35 | o roteiro cita o `[CT-11]` como atalho — *"guarda verde dispensa os quatro cenários"* — e o procedimento vira opcional na prática | CT-06 (3ª linha) |

---

## Regra R5 — o roteiro chega a quem instala pelos dois canais, até o disco

> `RQ-05` (assumido no `00`) · área C (padrão) · técnica: **rastreio de efeito de entrega** — dois
> canais, duas populações, **oráculo no destino** — com **controle positivo**

```gherkin
  Regra: o roteiro viaja pelos dois canais de entrega e declara de quem é o processo

    @premissa
    Cenário: [CT-07] nenhuma regra de export-ignore alcança o roteiro
      Dado o roteiro presente em disco na árvore do kit
      Quando o git é consultado sobre o atributo export-ignore do caminho do roteiro
      Então o git responde "unspecified" para "wikis/checklist-de-release.md"
      E responde "set" para um caminho dentro de "wikis/specs", provando que a consulta discrimina

    Cenário: [CT-08] o roteiro está presente onde o lê quem instalou, e declara de quem é o processo
      Dado o projeto em que a suíte está executando, seja ele a árvore do kit ou uma instalação
      Quando o usuário do kit abre "wikis/checklist-de-release.md" e lê o topo
      Então o arquivo existe neste projeto
      E o topo declara que o processo descrito é de quem MANTÉM o kit
      E nenhum imperativo do corpo se dirige a quem apenas instalou o kit

    @premissa
    Cenário: [CT-09] o roteiro está entre os caminhos que o kit:update oferece
      Dado a lista fechada de caminhos que o comando entrega
      Quando o mantenedor procura o roteiro nessa lista
      Então "wikis/checklist-de-release.md" está nela

    @premissa
    Cenário: [CT-18] quem já instalou recebe o arquivo em disco, e não só na lista
      Dado um projeto nascido de uma versão anterior, sem o roteiro em disco
      Quando o usuário do kit roda o kit:update aceitando as atualizações
      Então "wikis/checklist-de-release.md" existe em disco no projeto
      E seu conteúdo é o da versão publicada, e não um arquivo vazio
```

**CT-08 é o canário, e é por isso que ele não tem guarda** (achado da auditoria de premissas): CT-07
detecta M20 na árvore do kit e **está guardado** — ou seja, o detector está desligado exatamente no
ambiente onde M20 faz o estrago. CT-08 roda **nos dois** ambientes e falha no projeto instalado se o
roteiro deixar de viajar. Sem ele, doze casos desta entrega leem sem guarda um arquivo cuja viagem
nada verifica lá fora, e o dia em que uma linha de `export-ignore` alcançar `wikis/*.md` a suíte de
todo projeto instalado fica vermelha — a repetição literal do defeito desta wiki.

**Por que CT-18 foi separado de CT-09** (achado WI-4): *"estar na lista"* não é *"chegar ao disco"*.
Entre a lista e o arquivo existem o ramo que só `--all` consome, o pulo quando o destino já tem um
`wikis/`, e a lista que só reporta. Pior: CT-09 é materializado por uma varredura genérica que
compara o diretório com a constante — **tautológica em relação à entrega**. CT-18 afirma sobre o
destino.

**Premissa de CT-07** — o `00` assume que o roteiro viaja; *"se negado, uma linha no `.gitattributes`
reverte"*. **Se negado, CT-07 inverte** (passa a exigir `set`) **e CT-08 passa a precisar de guarda**
— o que a revisão adversarial mostrou que o invariante anterior escondia: o invariante vale para o
**texto**, não para o **caso**. Está escrito aqui, e não mais dentro do `Dado` de CT-08.
**Premissa de CT-09 e CT-18** — [pergunta 3](#perguntas-para-o-00-requisitomd); direção fechada (os
dois canais). **Se negada, os dois caem** e R5 fica com CT-07 e CT-08.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | uma regra de `export-ignore` passa a alcançar `wikis/*.md`: o roteiro some do `create-project` **sem quebrar mais nada** — o arquivo continua no repo, os links funcionam, a suíte do kit fica verde | CT-07 (na árvore do kit) **e** CT-08 (no projeto instalado, que é onde importa) |
| M21 | o roteiro viaja e se lê como promessa a quem instalou ("a cada release **sua**, rode…") | CT-08 (3ª linha do `Então`) |
| M22 | o roteiro entra na lista fechada e o comando **não o grava**: ramo de `--all`, pulo por destino existente, ou lista que só reporta | CT-18 |
| M23 | o roteiro entra no repositório e fica fora da lista fechada: quem já instalou nunca o recebe | CT-09 |

---

## Regra R6 — a guarda é do caso, a sentinela é discriminante, e a leitura não desapareceu

> `RQ-07`, `RQ-08`, `RQ-09` · área B (padrão, **Impacto 3**) · técnica: **partição sobre os três
> mecanismos de guarda que o `00` enumera** + **controle positivo na sentinela**

```gherkin
  Regra: o caso que lê documento não entregue é guardado por caso, por uma sentinela discriminante

    @premissa
    Cenário: [CT-10] a guarda é do caso, e não do arquivo inteiro
      Dado o arquivo de teste do domínio local, que viaja para todo projeto instalado
      Quando o código-fonte desse arquivo é lido como texto
      Então o caso que lê o documento do site carrega a guarda na própria declaração do caso
      E o arquivo não tem markTestSkipped no beforeEach, que silenciaria os demais casos

    @premissa
    Cenário: [CT-19] a sentinela discrimina, e não se ancora no que o caso lê
      Dado a sentinela que decide se a suíte está na árvore do kit
      Quando ela é consultada durante a execução na árvore do kit
      Então ela responde verdadeiro
      E sua âncora não é nenhum artefato que algum caso desta suíte leia como oráculo
      E sua âncora é um artefato marcado ausente no projeto instalado

    @premissa
    Cenário: [CT-20] a correção guardou a leitura, não a removeu
      Dado o arquivo de teste do domínio local, que viaja para todo projeto instalado
      Quando o código-fonte desse arquivo é lido como texto
      Então o caso guardado continua abrindo o documento do site para afirmar sobre o conteúdo
      E não passou a apenas citar o caminho do documento como string
```

**Premissa dos três** — [pergunta 2](#perguntas-para-o-00-requisitomd). O `00` decidiu **não haver
guarda automatizada**, mas as três opções recusadas eram **varreduras genéricas** sobre a suíte
inteira, e as três erraram por não enxergar um dos três mecanismos. CT-10, CT-19 e CT-20 leem **um**
arquivo nomeado e não podem errar do mesmo jeito. Direção por falha fechado — *ausência de barreira
nunca se assume*. **Se negado, os três viram lacuna declarada e R6 fica sem cenário.**

**Por que CT-19 existe** (achado WI-3, o mais perigoso da rodada 1): a versão anterior afirmava o
**mecanismo** (`->skip()` por caso) e o **nome** da sentinela, e nada sobre o **discriminante** dela.
Uma sentinela textualmente perfeita ancorada em `is_dir(base_path('docs'))` ressuscitava o mutante
auto-anulante **por dentro do matador que o conjunto lhe dera**: se o site sumisse da árvore do kit,
tudo seria pulado e a suíte ficaria verde com zero conferência. O conjunto exigira controle positivo
para o oráculo do `git` (CT-07) pela razão exata que se aplica aqui — *"um oráculo que sempre
responde a mesma coisa fica verde afirmando nada"* — e o dispensara para a sentinela.

**Por que CT-20 existe** (L2 era parcialmente falsa): a não-leitura é um dos três mecanismos
legítimos, e por isso o conjunto declarara M27 indistinguível. Está errado: o que separa *não-leitura
legítima* de *oráculo esvaziado* é o **mecanismo**, não o conteúdo — e o mecanismo é assertável sem
saber o que o caso afirmava antes. O que continua fora de alcance é a **força** da asserção
documental, e só isso permanece em [L2](#l2--a-força-da-asserção-documental-continua-fora-de-alcance).

**Por que a 2ª linha de CT-10 importa**: o `beforeEach` é mecanismo legítimo e é a correção mais
barata de escrever. Aplicado a este arquivo, silenciaria também os casos que conferem **código
entregue**, e o projeto instalado perderia cobertura real sem nada ficar vermelho.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | a correção **apaga o caso inteiro**: os quatro cenários ficam limpos e o kit perde a conferência do documento | CT-10 e CT-20 (o `Então` nomeia o caso; sua ausência reprova) |
| M25 | a guarda vai para o `beforeEach` do arquivo, e os casos que conferem código entregue param de rodar em todo projeto instalado | CT-10 (2ª linha) |
| M26 | a guarda é `->skip()` por caso, correta na forma, com a sentinela ancorada no próprio `docs/` — auto-anulante | CT-19 (2ª e 3ª linhas) |
| M27 | a correção troca a leitura do documento por uma citação do caminho como string: a suíte fica limpa nos dois ambientes e a asserção documental some | CT-20 |

---

## Regra R7 — sai uma versão de correção, e os três lugares contam a mesma versão e o mesmo conteúdo

> `RQ-08` · área E (mínimo) · técnica: **EP**

```gherkin
  Regra: a versão de correção é publicada de forma coerente, e o CHANGELOG registra o que ela corrige

    Cenário: [CT-11] a publicação da correção é coerente e descreve a correção
      Dado a tag de correção sendo publicada
      Quando o gate de release confere a publicação
      Então a versão marcada em config/kit.php é a mesma da tag
      E o CHANGELOG.md tem a seção daquela versão
      E essa seção registra o caso corrigido, e não apenas o número da versão
```

**Materializado por gate existente, sem caso Pest novo**: `.github/workflows/release.yml` compara a
tag, o marcador de `config/kit.php` e a seção do `CHANGELOG.md`, e falha a publicação se divergirem.
Um caso Pest equivalente teria de ler o `CHANGELOG.md`, que **não viaja** — criando uma instância
nova do defeito desta wiki para provar o que a CI já prova antes de a tag existir.
**A terceira linha do `Então` é adição da rodada 1** e **não está coberta pelo gate atual**: ele
confere a existência da seção, não o conteúdo dela. Fica registrada como a única parte de CT-11 sem
matador automatizado — e é M30.

**O que RQ-08 continua sem oráculo**: que a versão saia **depois** de os quatro cenários rodarem
limpos. É M31, e o rastro de papel dele é CT-15.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | a versão sobe em `config/kit.php` e a tag não é publicada — nenhum `create-project` a alcança | CT-11 (o gate roda sobre a tag; sem tag não há release) |
| M29 | a tag sai sem seção no `CHANGELOG.md` | CT-11 (`release.yml`) |
| M30 | a seção existe e está vazia, ou não menciona o caso corrigido | ⚠️ **sem matador** — o gate confere a existência da seção, não o conteúdo. Ver [L3](#l3--o-conteúdo-da-seção-do-changelog-não-é-conferido-por-gate-nenhum) |
| M31 | a versão de correção sai **sem** que os quatro cenários tenham sido reexecutados contra ela | ⚠️ **sem matador** — CT-15 exige o registro; nada verifica que ele foi feito. É RQ-09 |

---

## Regra R8 — os quatro cenários rodam liso, e esta entrega não abre um caso novo da mesma classe

> `RQ-01`…`RQ-04`, `RQ-09` · área D (**completo**) · técnica: **inspeção fechada de um arquivo**

A execução dos quatro cenários continua fora do arnês — ver [L1](#l1--a-execução-dos-quatro-cenários-continua-fora-do-arnês).
Mas **uma metade de RQ-09 é expressável**, e a rodada 1 da revisão adversarial provou que declará-la
impossível era falso: o argumento que o conjunto usou para justificar CT-10 (*"não é varredura: ele
nomeia **um** caso"*) se aplica inteiro ao **único arquivo que esta entrega cria**.

```gherkin
  Regra: o arquivo de teste que esta entrega adiciona ao conjunto que viaja não repete o defeito

    @premissa
    Cenário: [CT-21] os casos novos respeitam a fronteira entre o que viaja e o que não viaja
      Dado o arquivo de teste desta entrega, que passa a viajar para todo projeto instalado
      Quando o código-fonte desse arquivo é lido como texto
      Então todo caso que lê CONTRIBUTING.md ou CHANGELOG.md carrega a guarda na própria declaração
      E o caso que lê wikis/checklist-de-release.md não carrega guarda nenhuma
      E nenhuma guarda do arquivo se ancora na existência do artefato que o caso lê
```

**Por que a segunda linha do `Então` é afirmativa, e não uma dispensa**: se o caso que lê o roteiro
ganhasse guarda "por segurança", o canário de M20 (CT-08) morreria em silêncio e a única prova de que
o roteiro viaja deixaria de rodar onde ela importa. Guarda a mais aqui é tão defeito quanto guarda a
menos.
**Premissa** — mesma [pergunta 2](#perguntas-para-o-00-requisitomd) de R6.
**Escopo honesto**: CT-21 cobre **um** arquivo, o que esta entrega cria. Ele **não** é a varredura
que o `00` recusou, e **não** protege os outros 193. É por isso que M35 fica marcado como parcial.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M32 | os quatro cenários são "reexecutados" rodando a suíte na árvore do kit — o defeito desta wiki é, por construção, invisível ali | ⚠️ **sem matador automatizado** — o roteiro, via o critério de CT-13 (3ª linha) |
| M33 | só os dois cenários de instalação limpa são reexecutados; os de `kit:update` são presumidos equivalentes porque "os números foram idênticos nos quatro" | ⚠️ **sem matador automatizado** — CT-01 garante que os quatro estão escritos; CT-15 exige o registro dos quatro diretórios; nada prova que os quatro rodaram |
| M34 | a reexecução acontece contra a **branch** e não contra a tag publicada | **parcial** — CT-12 mata a metade documental (o roteiro fixa a versão); a execução em si fica sem matador |
| M35 | a correção fecha o caso do domínio local e **abre outro da mesma classe** | **parcial** — CT-21 mata para o arquivo que esta entrega cria; os outros 193 ficam fora, por [decisão do `00`](#l1--a-execução-dos-quatro-cenários-continua-fora-do-arnês) |
| M36 | "liso" é declarado com um erro considerado conhecido, e a tag sai | ⚠️ **sem matador automatizado** — o roteiro, via CT-04 e CT-15 |

---

## Lacunas Declaradas

Cinco lacunas. Cada uma registra **o que foi tentado**, não só que ficou de fora. **L1 e L2
encolheram** depois da revisão adversarial, que provou que as duas eram parcialmente falsas — o que
foi recuperado virou CT-12, CT-15, CT-20 e CT-21.

### L1 — a execução dos quatro cenários continua fora do arnês

**O que é**: M32, M33, M36, e as metades não documentais de M31 e M34.

**Por que não dá**: o observável exige `composer create-project` contra uma tag **publicada**, num
diretório fora deste repositório, quatro vezes, uma delas com `kit:tenancy` e duas partindo de uma
versão anterior. O `00` põe a automação explicitamente **fora de escopo**, e a decisão sobre guarda
foi tomada com o usuário: *"o roteiro dos quatro cenários é a guarda, e ela é empírica"*.

**O que foi tentado, antes de declarar**:

| Tentativa | Por que não resolve |
|---|---|
| inverter a sentinela no teste (`.github` renomeado em runtime) | flipa a sentinela para o processo inteiro, é destrutivo, depende de ordem — e **não remove `docs/`**, então um caso ancorado no próprio `docs/` continuaria passando |
| rodar a suíte com `docs/` renomeado | é literalmente a **terceira opção levada ao usuário e recusada** |
| varredura pelo `git archive` num diretório temporário | é a **segunda opção recusada**, e é a mesma família das três varreduras que erraram |
| um `create-project` em `tests/` | exige rede, tag publicada e minutos por cenário; a versão de correção não existe quando a suíte roda |

**O que foi recuperado** (a lacuna era maior do que precisava ser):

| Metade recuperada | Cenário | Achado |
|---|---|---|
| a versão-alvo fixada no comando — metade documental de M34 | **CT-12** | WI-1 |
| o rastro de papel da execução, anterior à tag | **CT-15** | L1-b |
| o arquivo novo desta entrega não repetir o defeito — M35 | **CT-21** | L1-a |

**Quem fecha o resto**: a execução manual do roteiro, registrada no `03` com a saída colada dos
quatro projetos. É o único fechamento que o `00` aceita.

### L2 — a força da asserção documental continua fora de alcance

**O que é**: o que sobrou de M27 depois de CT-20. O mecanismo (o caso continua abrindo o documento) é
assertável; a **força** da asserção documental — se ela ainda afirma algo que discrimina — exige
saber o que o caso afirmava antes da correção.

**O que mudou**: a versão 1 declarava M27 inteiro indistinguível. **Estava errado**, e a revisão
adversarial provou: mecanismo e conteúdo são separáveis. CT-20 fecha o mecanismo.

**O que foi tentado**: procurar no `00` qual conteúdo o caso afirmava (o `00` cita o erro e o
caminho, não a asserção); ancorar o oráculo no conteúdo (sem o conteúdo de partida, ele seria
inventado — Proibição 1, e esta derivação não pode ler o diff).

**Quem fecha**: o `feature-quality-gate`, na dimensão de adequação da suíte, comparando o antes e o
depois do caso.

### L3 — o conteúdo da seção do CHANGELOG não é conferido por gate nenhum

**O que é**: M30. O gate de release confere que a seção da versão **existe**; uma seção vazia, ou que
não mencione o caso corrigido, passa.

**O que foi tentado**: um caso Pest sobre o `CHANGELOG.md` — que **não viaja**, e exigiria guarda,
criando uma instância nova do defeito desta wiki para provar o que é do repositório e não do produto.

**Quem fecha**: a dimensão de consistência documental do `feature-quality-gate`, ou um passo a mais
no gate de release — que é entrega de infraestrutura, não desta wiki.

### L4 — o fechamento do ciclo por `pest --mutate` não roda neste ambiente

**Por que não dá**: não há PCOV nem Xdebug. E, no Windows, o score do plugin é **falso por
construção** sem o lançador `.cmd` poliglota.

**Consequência**: os 36 mutantes deste arquivo são previsões de especificação, derivadas à mão.
Isso não é perda relevante aqui — **mutation testing só muta código que existe**, e esta entrega
quase não tem código: ela é texto, configuração de entrega e uma guarda. Os mutantes que importam
(M5, M11, M20, M22, M24…M27, M32…M36) são todos de **omissão**, e a ferramenta é estruturalmente
cega a eles.

### L5 — RQ-07 ("entender os erros") não tem oráculo integral, por decisão registrada

**O que é**: RQ-07 é restrição sobre o **processo**. O `00` já a marca como não testável e a converte
em duas entregas: a seção *"por que nenhum gate pegou"* do `03` e a decisão sobre a guarda.

**O que esta derivação recuperou**: a metade que **é** texto virou R4 — e a rodada 1 mostrou que
CT-06 sozinho era fraco demais. CT-17 exige a **classe do defeito nomeada** e os **três mecanismos**,
que é o que transfere o entendimento para a próxima pessoa. A outra metade (a seção do `03`) é
verificação documental do quality gate.

---

## Cobertura já existente (nenhum CT novo)

| O que | Onde já está coberto | O que isso força |
|---|---|---|
| a contagem `Documentos de referência (wikis/)` nos dois READMEs | `tests/Kit/SiteDeDocumentacaoTest.php` `[CT-48]` | o roteiro novo em `wikis/` **obriga** a atualizar a contagem nos dois READMEs, ou a suíte fica vermelha |
| todo `wikis/*.md` estar na lista fechada do `kit:update` | `tests/Kit/KitUpdateTest.php`, varredura genérica sobre `.ai/rules` e `wikis` | **materializa CT-09** — mas é tautológica em relação à entrega, e por isso **CT-18 é escrito à parte** |
| a coerência tag ↔ `config/kit.php` ↔ `CHANGELOG.md` | `.github/workflows/release.yml` | **materializa CT-11** (duas primeiras linhas) sem caso Pest, e antes de a tag existir |

---

## Checklist de Taxonomia

> Resposta válida: um ID de cenário, `não se aplica: {motivo}` ou `lacuna declarada: {o que foi
> tentado}`. Nunca "sim".

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota, recurso com `{id}` nem ator autenticado |
| Autorização exercida na ação (não só `can()`) | não se aplica: nenhuma policy, permission ou gate é tocado |
| Idempotência (ancorada no agregado) | CT-18 — o `kit:update` rodado num projeto que já tem o arquivo não pode esvaziá-lo; o agregado é o **arquivo em disco**, não a lista |
| Concorrência | não se aplica: um único operador. **Mas a acumulação de papéis foi separada disso** — ver [pergunta 7](#perguntas-para-o-00-requisitomd) e CT-15 |
| Fronteira no ponto de entrada (gravação) | CT-01 (cardinalidade do roteiro: 4, não 3 nem 5) e CT-13 (contagem de pulados: o esperado, e o esperado+1) |
| Domínio condicionado (tipo × valor) | CT-01 — a tenancy condiciona o cenário, e as quatro combinações são exaustivas |
| Estado × operação de escrita | não se aplica: nenhuma entidade com ciclo de vida. **Substituído pela [tabela ambiente × artefato](#tabela-ambiente--artefato)**, 26 células, uma declarada não resolvida |
| **Ausente ≠ null ≠ vazio** | CT-10, CT-19, CT-21 (arquivo **ausente** no projeto instalado) e CT-18 (arquivo presente porém **vazio** — a 2ª linha do `Então`). A terceira partição, `null`, não existe em sistema de arquivos |
| Paginação / ordenação | não se aplica: nenhuma listagem |
| Timezone / DST | não se aplica: nenhuma data é comparada. A dimensão **T** é de ordem, não de relógio — virou CT-15 e a pergunta 4 |
| Unicode / limite de varchar | não se aplica: nenhum campo persistido |
| Unicidade + soft delete | não se aplica: nenhuma entidade |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: nenhum payload |
| Upload | não se aplica |
| Precisão monetária | não se aplica: nenhum valor numérico além da versão e da contagem de pulados, ambos inteiros |
| Superfície Livewire | não se aplica: o `01` declara **"Sem superfície de UI"** — a entrega é uma guarda em um caso de teste e três arquivos de markdown. Nenhuma página, widget ou componente |
| Estado do framework usado sem validar | não se aplica: nenhum estado de framework é consumido |
| IDOR por entidade | não se aplica: **zero tabelas persistidas** |
| Escopo com discriminante nulo | não se aplica: nenhuma query |
| Saída do estado de erro | não se aplica: nenhum `Então` desta derivação é resposta HTTP |
| **Link com alvo que não resolve** | CT-16 — o análogo documental de "notificação cujo link leva a 404" |
| **[linha nova, deste projeto] caso de teste que viaja lendo arquivo que não viaja** | CT-10, CT-19, CT-20 (o caso que originou o defeito) · CT-21 (o arquivo que esta entrega cria) · CT-17 (a classe nomeada no roteiro) |

> **A última linha é candidata a `.ai/rules/testes.md`.** É um defeito real deste projeto, que
> atravessou três ciclos de quality gate, um `/code-review`, um `fw-revisor-diff` e dois
> `fw-qa-gate`. A decisão do `00` foi **não escrever guarda estática** — ela não impede registrar a
> regra, que é instrução para quem escreve o próximo caso, não varredura sobre os que existem.

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Guarda | Mata |
|----|---------|-------|---------|--------|---------|--------|------|
| CT-01 | cada combinação tem seção própria; o total é quatro | R1 | EP exaustiva (`Esquema`, 4) | Kit | `tests/Kit/ChecklistDeReleaseTest.php` | — | M1, M2, M3 |
| CT-02 | cada cenário de update parte de versão anterior explícita | R1 | EP | Kit | idem | — | M4 |
| CT-12 | os quatro comandos fixam a versão-alvo | R1 | partição do argumento de versão | Kit | idem | — | M5, M34 (parcial) |
| CT-03 | a obrigação é incondicional e nomeia a tag | R2a | EP + controle positivo | Kit | idem | — | M6, M7 |
| CT-14 | a obrigação não é qualificada por tipo de tag | R2a | partição do disparador | Kit | idem | — | M8 |
| CT-04 | `@premissa` zero erro e zero falha | R2b | EP | Kit | idem | — | M10 |
| CT-13 | `@premissa` teto de pulados e justificativa por aumento | R2b | valor limite (n, n+1) | Kit | idem | — | M9, M11 |
| CT-15 | o roteiro exige a evidência registrada antes da tag | R2b | rastreio de efeito documental | Kit | idem | — | M12 |
| CT-05 | os dois pontos de entrada têm seção que remete ao roteiro | R3 | EP (`Esquema`, 2) | Kit | idem | **`naArvoreDoKit()`** | M13, M15 |
| CT-16 | o **alvo do link** resolve para o roteiro | R3 | rastreio até o destino | Kit | idem | **`naArvoreDoKit()`** | M14, M15 |
| CT-06 | a justificativa empírica, sem substituto estático | R4 | EP + controle positivo | Kit | idem | — | M16, M17, M18, M35 |
| CT-17 | a classe do defeito e os três mecanismos, nomeados | R4 | EP | Kit | idem | — | M19 |
| CT-07 | `@premissa` nenhum `export-ignore` alcança o roteiro | R5 | rastreio + controle positivo | Kit | idem | **`naArvoreDoKit()`** (sem repo git lá fora) | M20 (na árvore) |
| CT-08 | o roteiro existe aqui e declara de quem é o processo | R5 | invariante + **canário desguardado** | Kit | idem | **nenhuma, de propósito** | M20 (no projeto instalado), M21 |
| CT-09 | `@premissa` o roteiro está na lista do `kit:update` | R5 | pertinência | Kit | *materializado por `tests/Kit/KitUpdateTest.php`* | — | M23 |
| CT-18 | `@premissa` o `kit:update` grava o arquivo em disco | R5 | rastreio até o destino | Kit | idem | — | M22 |
| CT-10 | `@premissa` a guarda é do caso, não do arquivo | R6 | partição dos 3 mecanismos | Kit | idem | — | M24, M25 |
| CT-19 | `@premissa` a sentinela discrimina e não se ancora no que o caso lê | R6 | controle positivo na sentinela | Kit | idem | — | M26 |
| CT-20 | `@premissa` a correção guardou a leitura, não a removeu | R6 | partição mecanismo × conteúdo | Kit | idem | — | M24, M27 |
| CT-11 | a publicação é coerente e a seção descreve a correção | R7 | EP | CI | *materializado por `.github/workflows/release.yml`* | n/a | M28, M29 |
| CT-21 | `@premissa` os casos novos respeitam a fronteira do que viaja | R8 | inspeção fechada de 1 arquivo | Kit | idem | — | M35 (parcial) |

**Cenários executáveis como caso Pest novo**: 19 (CT-09 é materializado por caso existente; CT-11
por gate de CI).

**Mutantes sem matador**: M30 (L3), M31, M32, M33, M36 (L1), e **parciais** M34 e M35 —
**6 de 36 sem matador efetivo**, 2 parciais.

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| afirmar a contagem `Documentos de referência (wikis/)` nos READMEs | já provado por `[CT-48]` de `SiteDeDocumentacaoTest` |
| um caso Pest para a coerência tag ↔ `config/kit.php` ↔ `CHANGELOG.md` | duplicaria o `release.yml` e teria de ler o `CHANGELOG.md`, que não viaja — criando uma instância nova do defeito desta wiki |
| varredura estática de toda a suíte atrás de outros casos da mesma classe | **decidido no `00`: não haverá**. As três varreduras escritas erraram, cada uma por um mecanismo diferente. CT-21 cobre **um** arquivo, o que esta entrega cria — é inspeção fechada, não varredura |
| afirmar que o comentário do `.gitattributes` diz "doze documentos de topo" | comentário não é comportamento, e o número envelhece a cada arquivo novo |
| afirmar que o roteiro cita os números da validação da `v0.38.0` (2773 / 2627 / 145 / 1) | os números envelhecem e reprovariam entregas alheias. O que não envelhece é o **critério** (CT-04, CT-13) |
| um cenário por caso "protegido" dos outros 17 que leem arquivo não entregue | **fora de escopo declarado no `00`** |
| um cenário sobre o `kit:tenancy --force` do cenário RQ-02 | comando existente com suíte própria; esta entrega não o altera |
| um cenário afirmando que os quatro diretórios de validação existem em disco | mede a máquina de quem derivou, não o produto; e o `00` põe a automação fora de escopo |

## Sem CT-B

Nenhum caso de browser. **Gate do `05` não passa**: o `01-plano-acao.md` declara literalmente
*"Sem superfície de UI. A entrega é uma linha de teste e três arquivos de documentação."* Não há
rota, tela, JavaScript, tema ou elemento de acessibilidade — nada que só o navegador prove. O arquivo
`05-casos-de-teste-browser.md` **não é criado**.

---

## Revisão Adversarial

**Disparada** por Impacto 3 na área B **e** por perfil completo na área D. Executada por sub-agente
independente (`fw-adversario-ct`, `opus`), que recebeu **apenas** `00-requisito.md` e este `04` — sem
o PRD, sem o `02`, sem o código, sem o raciocínio de quem derivou.

### Rodada 1 — o que ela achou, e o que virou cada achado

| Achado | Severidade | Virou |
|---|---|---|
| **WI-1** — o roteiro pode não fixar a versão-alvo: os dois cenários de instalação validam o que o Packagist servir | alta | **CT-12** (novo) |
| **WI-2** — "pulado não conta" sem teto: a correção desta classe de defeito *é* um `skip`, então cada release infla os pulados e o roteiro segue dizendo "liso" | **crítica** — premissa falhando **aberto** | **CT-13** (novo) + [pergunta 6](#perguntas-para-o-00-requisitomd) |
| **WI-3** — o conjunto afirmava o mecanismo e o nome da sentinela, e nada sobre o discriminante dela: o mutante auto-anulante ressuscitava por dentro do próprio matador | **crítica** | **CT-19** (novo) |
| **WI-4** — "estar na lista" não é "chegar ao disco", e o materializador era tautológico | alta | **CT-18** (novo) |
| **WI-5** — CT-05 resolvia a **menção em prosa**, não o alvo do link: M10 não era morto | alta | **CT-16** (novo), CT-05 reescrito |
| **L1-a** — M28 era matável pelo próprio argumento que o conjunto usou em CT-10; a "restrição dura" era só prosa | **crítica** — lacuna falsa | **CT-21** (novo), L1 encolhida |
| **L1-b** — a obrigação de registro da evidência é texto, logo assertável | alta | **CT-15** (novo) |
| **L1-c** — a versão-alvo fixada é assertável | média | coberto por CT-12 |
| **L2 parcialmente falsa** — mecanismo e conteúdo são separáveis | alta | **CT-20** (novo), L2 encolhida |
| 11 oráculos fracos (CT-01…CT-11, todos por menção em vez de estrutura) | alta | **todos reescritos**: contagem de seções, alvo do link, ausência de hedge, presença desguardada, controle positivo declarado em três lugares |
| 4 cenários malformados (CT-05 com ação escondida no `Então`; CT-09 e CT-11 não executáveis; CT-10 com `Quando` inobservável) | média | `Quando` corrigidos; CT-09 e CT-11 marcados no índice como materializados por outra coisa, com a contagem de executáveis declarada |
| 7 defeitos na tabela ambiente × artefato (contagem por linha e não por artefato; célula resolvida por vacuidade; `CONTRIBUTING.md` não resolvida contada como resolvida; `CHANGELOG.md` com guarda que não existe; legenda não cobrindo comando; **3 artefatos ausentes**, entre eles o arquivo de teste que a própria entrega cria) | **crítica** | tabela **refeita**: 13 artefatos × 2 = 26 células, tabela de mecanismos à parte, legenda reescrita, célula não resolvida declarada como tal |
| RQ-04 sem cenário isolante; RQ-06 sem partição de tipo de tag; RQ-07 sem a classe do defeito nomeada | alta | CT-01 (contagem), **CT-14** e **CT-17** (novos) |
| premissa de CT-07 com invariante **falso como enunciado** — o `Dado` de CT-08 embutia a premissa | alta | `Dado` de CT-08 reescrito; o invariante passou a valer para o **texto**, e a consequência para o **caso** está escrita |
| acumulação de papéis: quem publica a tag declara "liso" — dispensado como "um único operador" | média | separado do item de concorrência do checklist; [pergunta 7](#perguntas-para-o-00-requisitomd) |
| contagem de mutantes contestada: ≥11 sem matador, não 7 | — | recontado após o fechamento: **6 sem matador, 2 parciais, de 36** |

**Saldo da rodada 1**: 10 cenários novos (CT-12…CT-21), 11 oráculos reescritos, 1 regra dividida em
duas, 1 tabela refeita, 2 lacunas encolhidas, 2 perguntas novas ao `00`. **Nenhum achado foi
descartado.**

### Rodada 2

Disparada porque o fechamento criou cenário novo — é onde mora a lacuna de segunda ordem. Resultado
em [`## Rodada 2 — achados`](#rodada-2--achados).
