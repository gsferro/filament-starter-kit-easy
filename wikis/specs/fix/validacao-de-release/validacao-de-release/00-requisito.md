# Requisito — Validação de release em projeto instalado

## Fonte

- **Origem**: duas mensagens do usuário no chat, em sequência, depois da publicação da `v0.38.0`
- **Data**: 2026-09-22
- **Autor / solicitante**: guilhermeferro@fiotec.fiocruz.br (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado verbatim
- **Gênese**: o usuário pediu a validação **antes** de haver defeito conhecido. Ela encontrou um na
  primeira execução, e é esse defeito que a segunda mensagem manda corrigir.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> assim que terminar de publicar a nova release, crie um novo teste em
> "C:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY" sendo um com e outro sem tenant e pegue algum
> que voce ja tenha testado, na versão antiga, e atualize usando o command kit:update, para também
> validar.
> - aproveite e documente isso para dentro do pacote, que sempre que lançar uma nova tag, fazer
>   esses 4 testes: 2 testes com novas instalações (com e sem tenant) + 2 kit:updates no mesmo
>   cenario

> quando fechar tudo e entender os erros, abrimos +1 branch de correção com a /feature-wiki nova e
> lançamos uma versão de correção para rodar liso de ponta a ponta em todos os cenarios

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Existe **instalação limpa sem tenancy** validada | "2 testes com novas instalações (com e sem tenant)" | funcional |
| RQ-02 | Existe **instalação limpa com tenancy** validada | idem | funcional |
| RQ-03 | Existe **`kit:update` a partir de versão antiga, sem tenancy**, validado | "pegue algum que voce ja tenha testado, na versão antiga, e atualize usando o command kit:update" | funcional |
| RQ-04 | Existe **`kit:update` a partir de versão antiga, com tenancy**, validado | "2 kit:updates no mesmo cenario" | funcional |
| RQ-05 | O roteiro dos quatro cenários fica **documentado dentro do pacote** | "aproveite e documente isso para dentro do pacote" | não-funcional |
| RQ-06 | O roteiro é declarado como obrigatório **a cada nova tag** | "que sempre que lançar uma nova tag, fazer esses 4 testes" | restrição |
| RQ-07 | Os erros encontrados são **entendidos**, não só corrigidos | "quando fechar tudo e **entender os erros**" | restrição |
| RQ-08 | Sai uma **versão de correção** | "lançamos uma versão de correção" | funcional |
| RQ-09 | A versão de correção roda **liso de ponta a ponta nos quatro cenários** | "para rodar liso de ponta a ponta em todos os cenarios" | restrição |

> **RQ-09 é o critério de aceitação da entrega inteira**, e ele não é satisfeito por suíte verde na
> árvore do kit: exige os quatro cenários reexecutados **contra a versão de correção publicada**.
> O defeito que originou esta wiki é invisível na árvore do kit, por construção.

## O que a validação mediu (entrada, não a reinvestigar)

Executada em `C:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\validacao-v0380\`, quatro projetos:

| Cenário | Como nasceu | Resultado |
|---|---|---|
| `v0380-sem-tenant` | `create-project … v0.38.0` | `2773 / 2627 verdes / 145 pulados / 1 erro` |
| `v0380-com-tenant` | idem + `kit:tenancy --force` | idem, **byte a byte** |
| `v0371-sem-tenant` | `create-project … v0.37.1` + `kit:update --all` | idem |
| `v0371-com-tenant` | idem + `kit:tenancy` antes do update | idem |

**O número idêntico nos quatro é informação, não coincidência**: não há nada específico de tenancy
nem específico de update. O `kit:update` funcionou nos dois cenários — a versão foi para `0.38.0`,
a migration de densidade rodou, `wikis/roadmap.md` chegou e a tenancy sobreviveu.

### O erro único

`tests/Kit/HostLocalTest.php` `[CT-12]` lê `docs/pt/comecar/dominio-local.md` como oráculo
documental. `docs/` está no `export-ignore`: **o teste viaja e o arquivo que ele lê não**.

```
File does not exist at path …/docs/pt/comecar/dominio-local.md
```

**Só é observável de dentro de um projeto instalado.** Rodada na árvore do kit, a suíte tem o
`docs/` presente. Por isso ele atravessou três ciclos de quality gate, um `/code-review`, um
`fw-revisor-diff` e dois `fw-qa-gate` sem ser visto por nenhum.

**Não é regressão da `v0.38.0`**: o `HostLocalTest` nasceu nesta release, já assim.

### A varredura por outros casos da mesma forma — e por que o método é o achado

| | |
|---|---|
| arquivos de teste que viajam | **193** |
| arquivos de `docs/` que viajam | **0** |
| casos que leem arquivo não entregue | **18** |
| casos **desprotegidos** | **1** — o CT-12 |

O kit tem **três** mecanismos de guarda diferentes, e nenhum é sinônimo dos outros:

1. `->skip(fn () => ! naArvoreDoKit(), …)` **por caso**
2. `markTestSkipped()` no **`beforeEach` do arquivo** (`tests/Kit/SiteDeDocumentacaoTest.php:naArvoreDoKit:30`)
3. **não-leitura** — o caso só cita o caminho como string, sem abrir o arquivo
   (`tests/Kit/KitUpdateTest.php`, o argumento de `estaCoberto()`)

Foram escritas **três varreduras estáticas** e as três erraram, cada uma por não enxergar um dos
mecanismos: 8 arquivos "com guarda" (guarda de arquivo não é guarda de caso), 20 casos
desprotegidos (não via o `beforeEach`), 17 casos desprotegidos (o regex não atravessava chaves
aninhadas).

**A única medição confiável foi instalar e rodar** — para descobrir o defeito. Isso é o argumento
central de RQ-05 e RQ-06, e precisa estar escrito no roteiro.

> **Correção do step 6.5 (RD-02).** A primeira versão desta seção seguia daí para *"uma varredura
> precisa acertar os três mecanismos, e por isso não haverá guarda automatizada"*. **Não segue.**
> O revisor do 6.5 escreveu uma varredura por caso em ~25 linhas e ela achou exatamente **1**
> lacuna — a certa. As três tentativas erraram por limitação delas, não por propriedade do
> problema, e a fatia em que a regra **é** decidível (caminho literal, no corpo do caso) é
> justamente por onde o defeito passou. Ver ADR-02, reescrita.

### E já existia uma guarda estática — verde o tempo todo

`tests/Kit/RedeDeDocumentacaoTest.php` `[CT-10]` cobra a sentinela `naArvoreDoKit()` nos arquivos
que leem documentação, com `str_contains` sobre o **arquivo inteiro**. O `HostLocalTest` já a
continha — no `[CT-34]`, 750 linhas abaixo do `[CT-12]`. **O CT-10 passou durante toda a `v0.38.0`
com o defeito presente.** Isso não aparecia na primeira versão deste documento, e muda a pergunta:
não era *"criar guarda?"*, era *"endurecer a que existe?"*.

## Ambiguidades e Perguntas Abertas

- **RQ-05 — onde documentar?**
  **DECIDIDO com o usuário em 2026-09-22**: `wikis/checklist-de-release.md`, com uma linha
  apontando de `CONTRIBUTING.md` e de `wikis/README.md`.
  **Problema descoberto depois da decisão**: **`CONTRIBUTING.md` não existe no repositório.**
  **DECIDIDO com o usuário em 2026-09-22**: **criar o `CONTRIBUTING.md`**, mínimo, com a seção de
  release apontando para o checklist. É um arquivo que o GitHub já espera — aparece no botão de
  contribuir, no template de PR e na página de insights — e que hoje falta no repositório.

- **RQ-05 — o checklist viaja para o projeto instalado?**
  `wikis/*.md` está **fora** do `export-ignore` (decisão registrada no `.gitattributes`: *"a wiki
  de referência é material de trabalho de quem instala"*), então ele viaja por padrão.
  **Assumido**: **viaja**, como o `roadmap.md` — e, como ele, o texto declara logo no topo que
  descreve o processo de quem **mantém o kit**, não de quem o instala.
  **Se negado**: uma linha no `.gitattributes` reverte, sem tocar em mais nada.

- **RQ-09 — "liso" inclui os 145 pulados?**
  Os 145 são deliberados: são os casos que não se aplicam fora da árvore do kit.
  **Assumido**: "liso" significa **zero erro e zero falha**; pulado declarado não conta contra.
  **Se negado**: seria preciso decidir o que fazer com cada um dos 145, o que é outra entrega.

- **A premissa acima falha ABERTO — achado da derivação do `04`, DECIDIDO com o usuário em 2026-09-22.**
  *"Pulado declarado não conta contra"* é **anti-conservadora nesta feature em específico**: a
  correção canônica desta classe de defeito **é acrescentar um `skip`**. A premissa isenta
  justamente a métrica que toda correção futura faz crescer — e já cresceu **nesta entrega**: 145
  → 147, sendo as duas unidades o `[CT-12]` corrigido e o `[CT-11]` novo. Um caso que passe a ser
  pulado **indevidamente** é indistinguível, por este critério, de uma correção legítima.
  **DECIDIDO: manter o teto.** O roteiro passa a exigir teto de pulados com **justificativa por
  aumento**, não só registro — registrar é anotação, teto é gate. Materializado em CT-13.

- **RQ-07 — "entender os erros" tem oráculo?**
  Não é testável como está.
  **Assumido**: o entendimento é entregue como a seção *"por que nenhum gate pegou"* do `03` e
  como a decisão sobre a guarda automatizada — se a causa está entendida, ou existe guarda que a
  pegue, ou existe motivo escrito para não haver.

- **Guarda automatizada para esta classe de defeito — DECIDIDO, e depois REVISTO.**
  A pergunta foi levada ao usuário com três opções (nenhuma guarda · varredura pelo
  `git archive` · rodar a suíte com `docs/` renomeado), e a decisão foi **não escrever guarda
  estática**, com o argumento de que guarda que erra é pior que guarda nenhuma.
  **A pergunta estava mal posta**, e o step 6.5 mostrou por quê (RD-02): a opção *"nenhuma
  guarda"* não existia — o `[CT-10]` já era uma guarda estática, e estava verde com o defeito
  presente. **Decisão corrigida**: acrescentar o `[CT-11]`, cobrindo só a fatia decidível, e
  provado por mutação contra o defeito real. O roteiro dos quatro cenários continua sendo a
  guarda do que **nenhuma** varredura alcança — o que só acontece fora da árvore do kit.
  Ver ADR-02.

## Fora de Escopo (declarado)

- **Criar guarda para os outros 17 casos** — eles estão protegidos; a varredura confirmou
- **Mexer nos três mecanismos de guarda** para unificá-los — é refactor de suíte inteira
- **Automatizar o `composer create-project`** dos quatro cenários — o roteiro descreve, não executa
- **Rever a decisão de `docs/` estar no `export-ignore`** — ela é anterior e tem motivo próprio
