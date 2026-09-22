# Decisões Arquiteturais — Validação de release em projeto instalado

## ADR-01: A guarda do CT-12 é `naArvoreDoKit()`, e o arquivo já tinha o padrão

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-07, RQ-08

### Contexto

`[CT-12]` de `tests/Kit/HostLocalTest.php` usa um arquivo de `docs/` como **oráculo documental**:
ele extrai o comando de elevação da página `dominio-local.md` e exige que o comando emitido pelo
código seja o mesmo. A intenção é boa e está escrita no docblock — *"sem esta amarra, o comando
poderia divergir da página e nada ficaria vermelho"*.

O problema não é o oráculo. É que `docs/` está no `export-ignore` e o teste não.

### Decisão

`->skip(fn (): bool => ! naArvoreDoKit(), 'O site de documentação não viaja no projeto instalado.')`

**O mesmo padrão que o último caso do próprio arquivo já usava** (`tests/Kit/HostLocalTest.php:[CT-34]:1354`). Não é
mecanismo novo: é o mecanismo existente aplicado ao caso que não o tinha.

### Alternativas Consideradas

1. **Fazer `docs/` viajar** — descartada. A decisão de excluí-lo é anterior, tem motivo próprio
   (o site é do kit, não do projeto de quem instala) e mudá-la por causa de um teste inverteria a
   ordem: o produto serviria ao teste
2. **Mover o oráculo para dentro de `tests/`** — descartada. O valor do CT-12 é justamente
   confrontar o código com **a página que o usuário lê**; uma cópia em `tests/` pode divergir da
   página e o caso perderia o sentido
3. **`File::exists()` antes de ler, e passar quando não existe** — descartada, e é a pior: o caso
   ficaria **verde por ausência**, que é exatamente a classe de defeito que esta sessão inteira
   perseguiu. Pular declarando é honesto; passar em silêncio não

### Consequências

- **Positivas**: o caso continua valendo onde pode valer, e declara por que não vale no resto
- **Negativas**: o oráculo documental deixa de rodar no projeto instalado — mas ali ele **nunca**
  pôde rodar; o que muda é ser explícito em vez de quebrar
- **Riscos**: nenhum novo. `naArvoreDoKit()` é `is_dir(base_path('.github'))`, e `.github` está no
  mesmo `export-ignore` — o sinal é o próprio mecanismo que causa o problema, o que o torna exato

---

## ADR-02: A guarda estática **já existia** e errava por granularidade — endurecê-la, não substituí-la

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-05, RQ-06, RQ-07

> **Esta ADR foi reescrita pelo step 6.5 (achado RD-02).** A primeira versão decidia *"não escrever
> guarda estática"* e não mencionava, em 50 linhas, que **uma guarda estática já existia** —
> `tests/Kit/RedeDeDocumentacaoTest.php` `[CT-10]`. Decidir não criar guarda e decidir não endurecer
> a guarda existente são decisões diferentes, e a segunda foi tomada na prática sem ser registrada.
> O texto original está preservado no fim desta ADR.

### Contexto

O `[CT-10]` varre os arquivos de teste que leem documentação e exige a sentinela `naArvoreDoKit()`.
Ele faz isso com `str_contains` sobre o **arquivo inteiro**.

`tests/Kit/HostLocalTest.php` já continha a sentinela — no `[CT-34]`, outro caso, 750 linhas abaixo
do `[CT-12]`. **O CT-10 esteve verde o tempo todo**, e o `[CT-12]` quebrava em toda instalação nova.

O docblock do CT-10 **declara essa granularidade como decisão consciente**, e com bom argumento:
nesta base a leitura é quase sempre **indireta** — o caminho vem de um dataset e o
`file_get_contents()` está no corpo —, e regra estática não distingue esse literal de um
decorativo. *"A granularidade da sentinela é do AUTOR … Este caso só cobra que a sentinela exista."*

**O argumento está certo para o caso indireto.** Ele deixa aberta a fatia em que a regra **é**
decidível: caminho **literal**, dentro da chamada de leitura, **no corpo do caso**. E foi por essa
fatia que o defeito passou — `File::get(base_path('docs/pt/…'))`, sem ambiguidade nenhuma.

### Decisão

**Acrescentar `[CT-11]`** a `tests/Kit/RedeDeDocumentacaoTest.php`, cobrindo **só** a fatia direta:
leitura literal de caminho não entregue exige a sentinela **no próprio caso**.

O CT-10 fica como está. O CT-11 não tenta decidir o indecidível — ele cobre o que o CT-10 declarou
fora do próprio alcance.

**Medido nos dois sentidos, com o defeito original restaurado na árvore:**

| Guarda | Com o `[CT-12]` defeituoso |
|---|---|
| `[CT-10]` — a que existia | **verde** |
| `[CT-11]` — a nova | **vermelho** |

### O que a primeira versão desta ADR errou, e por quê

Ela apoiava a decisão em *"três varreduras estáticas foram escritas e as três erraram"* — 8, 20 e
17 desprotegidos contra **1** real. O dado é verdadeiro e a conclusão não se sustenta: os erros
eram **limitações daquelas três tentativas**, não propriedade do problema.

A prova é que o revisor do 6.5 escreveu uma varredura por caso em ~25 linhas e ela achou **1
lacuna real** — a certa. E o `[CT-11]` desta ADR, escrito depois, **repetiu o erro da terceira
tentativa** na primeira versão: acusou os 15 cenários do `SiteDeDocumentacaoTest`, porque não via o
`markTestSkipped` do `beforeEach`. Corrigido acrescentando a checagem do preâmbulo — e o motivo
está escrito **dentro do caso**, porque a próxima pessoa vai esquecer de novo.

### Alternativas Consideradas

1. **Nenhuma guarda, só o roteiro** — a decisão da primeira versão. Descartada por RD-02: deixava
   o gate existente exatamente como estava, sem registrar que essa era uma decisão
2. **Endurecer o próprio CT-10 para granularidade por caso** — descartada: ele cobre também a
   leitura indireta, onde a regra não é decidível, e apertá-lo ali produziria o falso alarme que o
   docblock dele já argumenta evitar. Caso novo separado mantém as duas granularidades explícitas
3. **Varredura ampla pelo `git archive`** — descartada: resolve *o que viaja* (exato) e continua
   tendo de decidir *quais casos estão protegidos*, que é a parte difícil

### Consequências

- **Positivas**: a classe do defeito passa a ter matador automático na fatia em que isso é
  decidível — e ele foi **provado** contra o defeito real, não assumido
- **Negativas**: a leitura indireta continua coberta só pelo CT-10, na granularidade de arquivo.
  É limitação declarada, não esquecimento
- **Riscos**: o CT-11 produzir falso positivo numa forma de leitura que eu não previ. Mitigação: a
  mensagem nomeia o caso e explica que sentinela em caso vizinho não protege — quem for acusado
  sabe o que fazer

### O roteiro continua sendo necessário

O CT-11 cobre **uma** classe. O roteiro dos quatro cenários cobre o que nenhuma varredura cobre:
**tudo o que só acontece fora da árvore do kit**. Os dois não se substituem, e o caso do roadmap
(ADR original, RD-03) mostra o inverso — um defeito que o gate automatizado pegava e o roteiro
também pegaria.

<details>
<summary>Texto original desta ADR, antes do RD-02 (preservado)</summary>

A versão original decidia *"não escrever guarda estática"*, com o argumento das três varreduras
que erraram, e concluía que *"guarda que erra é pior que guarda nenhuma"*. A frase continua
verdadeira — e é por isso que o `[CT-11]` foi verificado por mutação antes de entrar. O que estava
errado era o pressuposto de que **não havia** guarda a endurecer.

</details>

---

## ADR-03: O checklist viaja; o `CONTRIBUTING.md` não — e ele vive em `.github/`

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-05

### Contexto

`wikis/*.md` está **fora** do `export-ignore` por decisão registrada no `.gitattributes` — *"a wiki
de referência é material de trabalho de quem instala"*. O `roadmap.md` já seguiu essa regra na
`v0.38.0`. O checklist de release, porém, descreve o processo de quem **mantém** o kit.

### Decisão

**Viaja**, como os demais `wikis/*.md`. E, como o `roadmap.md`, o texto **abre declarando** que
descreve o processo de quem mantém o kit, não de quem o instala.

### Alternativas Consideradas

1. **`export-ignore` só para este arquivo** — descartada: criaria a primeira exceção dentro de
   `wikis/*.md`, e exceção de uma linha é o tipo de regra que ninguém lembra depois. Quem derivar
   um projeto do kit e quiser apagar o arquivo apaga; é dele
2. **Colocar em `.github/`** (que não viaja) — descartada: `.github/` é fluxo de CI, não
   documentação de processo, e o arquivo perderia a vizinhança dos outros `wikis/`

### Consequências

- **Positivas**: coerente com a decisão já tomada para o `roadmap.md`; zero linha nova de
  configuração
- **Negativas**: quem instala recebe um roteiro que não é dele. Mitigado pela declaração no topo —
  a mesma solução que o roadmap usou
- **Riscos**: nenhum medido

### O `CONTRIBUTING.md` é o caso oposto, e esta ADR não o decidia

**Achado RD-01 do step 6.5.** O arquivo nasceu na **raiz**, e a raiz viaja: quem rodasse
`composer create-project` receberia, no próprio projeto, um guia dizendo *"antes de lançar uma tag,
rode os quatro cenários"* sobre um kit que já virou o código dele. E não chegava por
`kit:update`, porque a varredura de `KitUpdateTest` cobre `wikis/` e `.ai/rules/`, **não a raiz** —
entrega assimétrica, e nenhum teste ficava vermelho.

**Decisão**: `.github/CONTRIBUTING.md`. O GitHub procura o arquivo em três lugares — raiz,
`.github/` e `docs/` — então o botão de contribuir e o template de PR continuam funcionando. E
`/.github` já está no `export-ignore`, então ele **não viaja**, que é o correto: é documento de
quem mantém o kit, não de quem o instala.

**O precedente é do próprio repositório**: `.github/SECURITY.md` já é exatamente isso. A primeira
versão quebrou as duas pontas da convenção que o repo já tinha.

**Consequência que fecha um erro da ADR-02**: ela citava *"é linkado do `CONTRIBUTING.md`"* como
mitigação do risco de o checklist cair em desuso — e **nada apontava para o `CONTRIBUTING.md`**.
Uma mitigação que depende de um arquivo sem porta de entrada não é mitigação.

---

## ADR-04: O critério de "liso" é zero erro e zero falha, não zero pulado

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-09

### Contexto

RQ-09 pede *"rodar liso de ponta a ponta em todos os cenarios"*. A execução nos quatro cenários
devolveu **145 pulados** em cada um, além do erro único.

### Decisão

**"Liso" significa zero erro e zero falha.** Pulado **declarado** não conta contra — **mas a
contagem tem teto, e todo aumento exige justificativa escrita.**

> **A segunda metade entrou depois, e a primeira sozinha estava errada.** A derivação do `04`
> mostrou que *"pulado não conta contra"* falha **aberto nesta feature em específico**: a correção
> canônica desta classe de defeito **é acrescentar um `skip`**, então o critério isenta exatamente
> a métrica que toda correção futura infla. **Nesta própria entrega** ela foi de 145 para 147.
> Sem teto, um caso que deixe de rodar **indevidamente** é indistinguível de uma correção
> legítima — os dois são "+1 pulado, zero falha".
>
> **Confirmado com o usuário em 2026-09-22**: mantém o teto. É acréscimo de processo, e foi
> aceito como tal.

### Alternativas Consideradas

1. **Exigir zero pulado** — descartada. Os 145 são casos que **não se aplicam** fora da árvore do
   kit: conferem o site de documentação, os fluxos do GitHub Actions, o histórico de planejamento.
   Fazê-los rodar exigiria entregar `docs/`, `.github/` e `wikis/specs/` a todo projeto instalado —
   o oposto de três decisões anteriores do kit
2. **Só registrar a contagem, sem teto** — foi a primeira versão desta ADR, e **descartada** depois
   do achado da derivação do `04`. Registrar deixa o sinal visível e não obriga ninguém a olhar: a
   release sai com "zero erro, zero falha" e o número cresce em silêncio numa linha de log. Numa
   feature cuja correção canônica é *acrescentar um `skip`*, isso é pedir para a métrica apodrecer

### Consequências

- **Positivas**: o critério é verificável, e o teto o torna **acionável** — o aumento tem de ser
  explicado por quem publica, no momento em que publica
- **Negativas**: é acréscimo de processo, e processo que ninguém cumpre é pior que processo nenhum.
  Aceito explicitamente pelo usuário em 2026-09-22, sabendo disso
- **O que a mitigação anterior não cobria**: *"registrar, e a variação é o sinal"* supõe alguém
  comparando duas releases. O teto não supõe ninguém: ele **reprova** na release em que o número
  sobe sem justificativa escrita
- **Riscos**: o número 145 envelhece — e **já envelheceu nesta própria entrega**. A primeira
  versão desta linha dizia *"o `[CT-12]` corrigido leva a 146 e o `[CT-11]` novo a 147"*, e
  **errou por ~25** (QA-10, ciclo 2): eu contei os dois casos que tinha em mente, quando o que
  pula lá fora é **todo** caso guardado por `naArvoreDoKit()` — e esta entrega acrescentou um
  arquivo inteiro deles. Por isso o checklist pede **medir e registrar**, nunca **prever**, e o
  passo 6 é quem mede.
- **Ajuste do ciclo 2**: a justificativa do aumento é **por causa**, não por unidade. Exigir um
  parágrafo por pulado tornaria o gate impossível de cumprir na primeira release que acrescenta
  um arquivo de teste — e gate impossível vira gate ignorado

---

## ADR-05: RQ-06 fica **sem gate automatizado**, e isso é decisão, não esquecimento

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-06 · **Origem**: QA-14, quality gate ciclo 2

### Contexto

RQ-06 é uma **restrição**: *"sempre que lançar uma nova tag, fazer esses 4 testes"*. Hoje o
cumprimento dela depende inteiramente de prosa — o `## A regra` do checklist e a seção do
`.github/CONTRIBUTING.md`. O `04` já registra `M37` (*"a versão de correção sai **sem** que os
quatro cenários tenham rodado contra ela"*) como **sem matador**.

O gate apontou, com razão, que **a opção óbvia nunca foi pesada**: o
`.github/workflows/release.yml` já roda em `push: tags: ['v*']` e já reprova duas invariantes de
release — marcador × tag e a seção do `CHANGELOG`. Exigir ali o registro dos quatro cenários é o
**mesmo mecanismo**, não a automação de `composer create-project` que o `00` põe fora de escopo.

### Decisão

**Sem gate automatizado.** RQ-06 continua sendo cumprida por processo, e `M37` fica **declarado
sem matador**, apontando para esta ADR.

### Por quê

O gate possível verificaria que a **seção de release contém um texto**. Ele não tem como saber se
os quatro cenários rodaram — só se alguém **escreveu que rodaram**.

Isso é pior do que parece, e a razão é o próprio assunto desta wiki: **um gate que aprova a
alegação em vez do fato produz exatamente a falsa segurança que esta correção existe para
desmontar.** Com ele no lugar, o CI ficaria verde na tag e a pergunta *"os quatro rodaram?"*
passaria a ter uma resposta automática e sem valor. Sem ele, a pergunta continua aberta e alguém
tem de respondê-la.

O paralelo interno é direto: o `[CT-10]` cobrava a sentinela **no arquivo** e por isso passou verde
com o defeito dentro. Um gate de tag que cobra **a frase** tem a mesma forma — mede o rastro, não
o fato.

### Alternativas Consideradas

1. **Passo no `release.yml` exigindo o registro** — recusada pelo argumento acima. Custo baixo
   (~10 linhas), mas mede a alegação. **Foi levada ao usuário e recusada explicitamente**, em
   2026-09-22, com a opção à vista
2. **Automatizar os quatro cenários no CI** — fora de escopo por decisão do `00`: exige rede,
   tag publicada e minutos por cenário, e a versão de correção não existe quando a suíte roda
3. **Deixar como estava** — recusada: não estava *"sem gate por decisão"*, estava **sem decisão**.
   É a diferença que o QA-14 apontou, e é a mesma que a ADR-02 teve de corrigir

### Consequências

- **Positivas**: nenhum gate que aprove alegação. A obrigação fica onde pode ser cumprida de
  verdade — no roteiro, que exige a **saída colada** de cada execução, e não um "sim"
- **Negativas**: `M37` sem matador. Quem publicar uma tag pulando o roteiro não encontra
  resistência nenhuma do sistema
- **Riscos**: o roteiro cair em desuso, que é o risco já nomeado no `01`. A mitigação continua
  sendo a mesma: ele carrega dois casos reais de *"o que já quebrou aqui"* em vez de ser roteiro
  genérico, e o registro da evidência vem **antes** da tag
