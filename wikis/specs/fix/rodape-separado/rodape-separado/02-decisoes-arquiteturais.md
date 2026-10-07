# Decisões Arquiteturais — Rodapé separado

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01.
     A ADR não leva arquivo:linha nem contagem: cita módulo ou classe por nome. -->

## ADR-01: Desfaz-se o acoplamento, não a release

**Status**: Aceita
**Data**: 2026-10-07
**Portões**: difícil de reverter ✅ (a junção foi premissa de três ADRs, de uma regra de CSS e de vários casos de teste da `rodape-coerente`; refazê-la é reescrever os oráculos de ausência e de ordem, não trocar uma linha) · surpreendente ✅ (quem lê o histórico vê uma release inteira — v0.39.0 — e espera que ela seja revertida inteira; só uma parte é) · trade-off ✅ (reverter só o acoplamento mantém código e CSS que nasceram *por causa* da junção, e esse resíduo é aceito de propósito)

### Contexto

O commit `bfe9a8d` (v0.39.0, wiki `rodape-coerente`) entregou, num só pacote, coisas de natureza diferente: (a) a assinatura composta num ponto único, com `©`, ano e nome também para o visitante; (b) o prefixo visual `v` no campo da versão; (c) os landmarks `<footer>` e `<aside>`; (d) a regra de CSS que faz o rodapé caber na dobra das telas de autenticação; e (e) a **junção** — o recado Markdown do login saiu do hook do formulário e foi para o `FOOTER`, escopado, para ficar abaixo da assinatura.

O solicitante pediu para desfazer a junção: o recado "como estava" e a assinatura "como está". Ele não pediu para desfazer a release.

### Decisão

Reverte-se **só o item (e)**: o recado volta ao hook `AUTH_LOGIN_FORM_AFTER`, sem escopo (a justificativa está na análise do `01`), depois dos botões sociais, dentro do cartão do formulário — o estado de antes de `bfe9a8d`. Ficam como estão: a composição da assinatura e a visibilidade dela para o visitante (sem versão), o prefixo `v`, o `<footer>` da assinatura, a regra de CSS da dobra e a tag `<aside>` do recado, esta última por P-05 do `00` (a posição é o que o pedido desfaz; o landmark é marco medido).

### Alternativas Consideradas

1. **Reverter o commit `bfe9a8d` inteiro** — descartada: perderia o `©` e o nome da assinatura para o visitante e o prefixo `v`, que o solicitante mandou manter ("o rodape automatico como esta"), e traria de volta a falha de acessibilidade que os landmarks resolveram.
2. **Manter os dois no `FOOTER` e só trocar a ordem** — descartada: é a própria junção que o solicitante recusou ("ao inves de juntar ambos no rodape (footer)").

### Consequências

- **Positivas**: o recado volta ao lugar que o solicitante conhecia; o provider perde um comentário longo e um `use` sem uso; a assinatura não é tocada, então nada que dependia dela muda.
- **Negativas**: ficam vestígios da junção no código — comentários históricos no CSS da dobra. É o preço de P-03.
- **Riscos**: o cartão volta a carregar o recado e fica mais alto; a assinatura pode cair abaixo da dobra (medida pelo CT-B01). O `<aside>` dentro do cartão não foi medido pelo axe (o CT-B03 o mede). Os dois riscos estão no `01` (R1 e R2).

### Referências
- `App\Providers\KitServiceProvider::configureTelaDeLogin()`, `App\Providers\Concerns\ConfiguraFilamentGlobal::configuraVersaoNoRodape()`, `App\Support\AssinaturaDoRodape`.
- ADR-01, ADR-07 e ADR-09 de `wikis/specs/feat/rodape-coerente/rodape-coerente/02-decisoes-arquiteturais.md`; ADR-02 deste arquivo.

---

## ADR-02: O que da `rodape-coerente` fica superado, e o que continua

**Status**: Aceita
**Data**: 2026-10-07
**Portões**: difícil de reverter ✅ (a `rodape-coerente` é registro datado e não se edita; o que esta ADR disser é o único lugar onde o próximo leitor descobre a supersessão) · surpreendente ✅ (a ancestral continua dizendo "Aceita" em decisões cuja parte central deixou de valer, e o INDEX não mostra isso) · trade-off ✅ (não editar a ancestral preserva a história, ao custo de o leitor precisar de dois documentos)

### Contexto

A `rodape-coerente` é uma wiki concluída e mergeada. Esta feature a contradiz em parte. Duas saídas ruins: editar a ancestral (reescreve história e quebra a rastreabilidade do que foi decidido naquele dia) ou deixar a contradição solta (o leitor acredita numa decisão cuja premissa não vale mais).

### Decisão

A ancestral **não é editada**. Esta ADR é o registro de supersessão, só do que muda; o que não aparece na tabela (ADR-02, 03, 04, 06 e 08 da ancestral) segue vigente sem nota. A prática do projeto é que wiki concluída é registro datado e o que a supera é dito na wiki nova (conferido: `.ai/rules/specs.md` não tem seção sobre isso).

| Item da `rodape-coerente` | Situação | O que fica e o que sai |
|---|---|---|
| `00` — RQ-06 (decisão de 2026-09-22: o recado vira linha adicional abaixo da automática) | **superada** | o recado é bloco do cartão do formulário, não linha do rodapé |
| `01` — passo 3 (o recado muda para o `FOOTER`, escopado às duas classes de login) | **superado** | o recado volta a `AUTH_LOGIN_FORM_AFTER`, sem escopo |
| ADR-01 — "Coerente" é a mesma linha nos dois | **superada em parte** | **Fica**: coerência de composição e não de conteúdo; a audiência decide a versão. **Sai**: a frase "na tela de login, o texto livre do admin vira uma linha adicional, abaixo da automática" |
| ADR-05 — assinatura escapada; recado por Markdown | **decisão vigente; contexto superado** | **Fica**: escapar a assinatura e renderizar o recado por Markdown com HTML cru descartado. **Sai**: o contexto de "duas vias no mesmo bloco visual", que o motivava |
| ADR-07 — o rodapé cabe na dobra por CSS | **vigente, com nota** | a regra de CSS e o motivo (a assinatura, fora do `.fi-auth-layout`, cai abaixo da dobra) valem. A alternativa 2, "voltar o recado para `AUTH_LOGIN_FORM_AFTER`", foi lá descartada por quebrar a ordem que o solicitante escolhera; é exatamente o que esta feature faz, por pedido posterior do mesmo solicitante |
| ADR-09 — o recado é `<aside>` | **vigente, com nota** | a tag e a medição das três formas contra a assinatura valem; o recado deixa de ser filho direto de `<body>`, e a tag permanece por P-05 do `00` (D1 do `01`). O axe com o recado aninhado é medido pelo CT-B03 desta entrega |

O INDEX é **gerado** pelo `indice.sh` da `feature-tickets`: esta wiki o cita, nunca o edita à mão. O comentário do provider (`KitServiceProvider::configureTelaDeLogin()`) e o da blade do recado, que hoje descrevem o `FOOTER`, são reescritos nos passos 1 e 2 do `01` e apontam para esta wiki.

### Alternativas Consideradas

1. **Editar a ancestral, marcando as ADRs como "superadas"** — descartada: reescreve a prova datada do que foi decidido e por quê, e a prática do projeto é registrar a supersessão na wiki nova.

### Consequências

- **Positivas**: a história da ancestral fica intacta; quem chegar aqui pela referência cruzada sabe o que vale.
- **Negativas**: ler a ancestral sem ler esta ADR leva a acreditar na junção. A tabela acima é o remédio, e o CHANGELOG a repete numa frase.
- **Riscos**: uma feature futura citar o ADR-01 da ancestral inteiro. Mitigação: o candidato a rule do step 12 pode registrar "o hook `FOOTER` é da assinatura; o recado do login é do cartão" em `.ai/rules/`, se o solicitante aprovar.

### Referências
- ADR-01, ADR-05, ADR-07 e ADR-09, RQ-06 do `00` e passo 3 do `01` de `wikis/specs/feat/rodape-coerente/rodape-coerente/`; ADR-01 deste arquivo.
- `wikis/specs/INDEX.md` (gerado por `.claude/skills/feature-tickets/scripts/indice.sh`).

---

## Superfície Livewire

Não exigida: a entrega não cria página, widget nem componente, e nenhuma `public function` ou `public $` nova. Muda apenas a chave de um render hook e o texto de comentários; a tela que grava o recado (`ConfiguracoesDoKit`) não é tocada.
