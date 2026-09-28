> Referência da feature-test-design 1.16.0. Lida em: passo 3 (antes de aplicar a técnica a cada
> regra — o desenvolvimento, as tabelas e os exemplos de cada regra de execução). Fonte única de:
> as regras de execução do passo 3.

# Técnicas por regra — desenvolvimento e exemplos (passo 3)

A regra de cada seção está, em uma linha, no `SKILL.md` §Passo 3; aqui fica o desenvolvimento.
Os casos que motivaram cada regra estão em `references/casos-medidos.md`.

## A regra que mais defeito produz: **criação ≠ edição ≠ uso**

Toda variável tem **três** pontos onde o sistema decide sobre ela, não dois:

| Ponto | Pergunta | Exemplo |
|---|---|---|
| **criação** | esse valor pode sequer ser gravado? | desconto de −5%? validade ontem? limite 0? |
| **edição** | e depois, no `save`? | a mesma validação existe? a unicidade ignora o próprio registro? |
| **uso** (leitura) | dado que está gravado, o que acontece? | cupom expirado é recusado na aplicação |

**Derivar partição e valor limite nos três pontos, sempre.** É a omissão mais cara e a mais fácil
de cometer, porque o requisito costuma descrever só o ponto de uso — *"valida se está dentro da
validade"* fala da aplicação e não diz nada sobre cadastrar, nem sobre editar para, uma validade
no passado.

> Caso medido: `references/casos-medidos.md` §Criação ≠ edição ≠ uso.

**A edição tem duas armadilhas próprias**, que não existem na criação:

- **unicidade contra si mesmo** — salvar sem alterar o campo único deve passar; a validação
  ingênua acusa colisão do registro com ele próprio
- **validação que só roda na criação** — regra escrita no `create` e esquecida no `save` é
  invisível para qualquer cenário que só crie

## Toda partição de EP se repete em cada rastreio de efeito

Quando um campo discriminador particiona o domínio (`tipo = percentual | valor_fixo`), ele
**também particiona o comportamento** — consumo, trilha de auditoria, validação, notificação. Não
basta cruzar discriminador × valor na gravação: cada **rastreio de efeito** precisa ser exercitado
em **cada partição do discriminador**.

> Caso medido: `references/casos-medidos.md` §Toda partição de EP se repete em cada rastreio de efeito.

Na prática: se há `N` partições do discriminador e `M` efeitos rastreados, o mínimo não é `N + M`,
é garantir que nenhum par `(partição, efeito)` fique sem nenhum cenário. Um `Esquema do Cenário`
com o discriminador como coluna resolve sem inflar a contagem.

## Domínio condicionado: a fronteira muda com o outro campo

Quando o domínio válido de um campo **depende do valor de outro**, ele não é um domínio só —
são vários, e cada um tem fronteiras próprias:

| Campo discriminador | Campo dependente | Fronteiras |
|---|---|---|
| `tipo = percentual` | `valor` | 0, 1, **100, 101** |
| `tipo = valor_fixo` | `valor` | 0, 1, sem teto superior |

**Cruzar a partição do discriminador com o valor limite do dependente** — uma tabela de decisão
pequena. Tratar `valor` como um domínio único faz o teto de 100% desaparecer sem que ninguém note,
porque os cenários "cobrem o campo `valor`".

## Ciclo de volta exige **2-switch**, não 1-switch

Quando um estado pode ser **reentrado** — rejeitado volta a rascunho, devolvido volta para
correção, estornado volta a pendente —, cobrir uma transição por vez não prova nada sobre o
**segundo giro**. O defeito mora ali: o ciclo novo herda o que o anterior deixou.

Derivar a **sequência de dois eventos** com oráculo sobre o resultado do segundo:

```
aguardando_diretor → rejeitar → rascunho → enviar → ?
```

O `Então` é sobre o **destino do segundo envio** (`aguardando_gestor`, e não
`aguardando_diretor`) e sobre **quais registros do ciclo anterior ainda contam**.

> Caso medido: `references/casos-medidos.md` §Ciclo de volta (2-switch).

## Estado exibido: partição **exaustiva** do enum

Quando o usuário vê um rótulo derivado de um enum de estado, **toda partição do enum é uma classe
de equivalência obrigatória** — não se amostra. Cobrir "Aguardando gestor" e "Aprovada" e deixar
"Aguardando diretor" de fora permite exatamente o defeito que importa: a tela dizer "Aprovada"
enquanto falta uma etapa.

Um cenário com `Esquema do Cenário` e uma linha por valor do enum resolve. Se o enum tem 5 casos,
a tabela tem 5 linhas.

## Não-efeito só discrimina se o mundo tiver destinatário

Afirmar que um efeito **não** aconteceu só separa duas implementações se, naquela configuração, o
efeito **poderia** ter acontecido. Num mundo sem ninguém a notificar, sem registro a auditar, sem
saldo a debitar, o mutante e a implementação correta produzem o **mesmo** observável.

**Todo cenário que afirma não-efeito declara, no `Dado`, o destinatário/alvo que existe.** Se o
`Dado` não põe alguém no mundo, o `Então` de ausência é decorativo.

| Cenário de não-efeito | Configuração que **não** discrimina | Configuração que discrimina |
|---|---|---|
| "nenhuma notificação é enviada" | o centro não tem gestor; a organização não tem diretor | o aprovador existe e seria notificado no caminho feliz |
| "nenhuma linha de auditoria é criada" | a entidade auditada não existe no `Dado` | a entidade existe e o caminho feliz gravaria a linha |
| "nenhum job é despachado" | a fila roda em `sync` no ambiente de teste | `Queue::fake()` com o worker que o caminho feliz usaria |
| "o saldo não foi debitado" | o saldo de partida é zero | saldo positivo, e o valor afirmado |

**A partição de cardinalidade do destinatário (0 / 1 / N) não substitui esta regra.** O cenário de
zero destinatários é uma partição legítima — e é justamente o que **não pode** ser citado como
prova de atomicidade ou de não-efeito.

E a atomicidade continua exigindo **falhar depois do ponto do efeito** — constraint violada, mock
do `save`, evento de model lançando. Afirmar ausência num caminho de **pré-validação**, onde nada
seria enviado de qualquer forma, é a mesma falha por outro lado. As duas condições valem juntas:
falha depois do ponto **e** destinatário real. Cumprir uma só é falso ✅.

> Caso medido: `references/casos-medidos.md` §Não-efeito só discrimina se o mundo tiver destinatário.

## Estado × **operação**, não estado × visibilidade

Ao montar a tabela de estados, as colunas são **todas as operações** que a entidade aceita —
`aplicar`, `editar`, `excluir`, `listar`, `exportar` — e não apenas a de leitura. A célula que
mais escapa é *"entidade excluída/desativada × operação de escrita"*: os cenários provam que ela
some da listagem e ninguém prova que ela **deixou de funcionar**.

**A matriz é montada ANTES das regras, e é UMA tabela.** Decompor o ciclo de vida em matrizes por
regra de negócio — uma para `editar/excluir`, outra para `enviar`, outra para o estado terminal,
outra para quem decide a etapa corrente — parece organização e é **perda de cobertura**: cada
operação só aparece nos estados que a regra dela já pressupõe, e os estados que nenhuma regra
menciona junto daquela operação somem sem deixar célula vazia para alguém notar. A matriz é o
**produto cartesiano fechado** `todos os estados × todas as operações`, montada a partir do enum e
da lista de verbos, não a partir do mapa de regras.

**A contagem é o oráculo da própria matriz.** Escrever no `04` o total (`E estados × O operações =
N células`), quantas são válidas e quantas inválidas, e provar que **cada** célula tem `CT-nn`,
`não se aplica: {motivo}` ou `lacuna declarada: {o que foi tentado}`. Matriz sem total declarado
não é auditável: ninguém consegue dizer se falta linha.

**A legenda da matriz é uma asserção, e é auditada.** Escrever `❌ = recusa e não-efeito` obriga a
que **toda** célula inválida afirme **todos** os efeitos que aquela operação dispara no caminho
feliz — não um efeito qualquer, escolhido por coluna. Se o `enviar` notifica, e o `aprovar` notifica
e grava histórico, a coluna de `aprovar` tem as duas asserções de ausência e a de `enviar` tem a
sua. Uma coluna com o não-efeito de histórico e sem o de notificação torna a legenda **falsa** e a
contagem de células **não auditável**.

Isso não cria matriz nova: as direções do rastreio de efeito são **colunas do `Esquema` de cada
operação**, dentro da matriz única. Um `Esquema` continua contando como 1 cenário, então o custo é
em colunas, não em teto de perfil. E cada coluna de ausência só vale se
[o mundo tiver destinatário](#não-efeito-só-discrimina-se-o-mundo-tiver-destinatário) — o escopo é
**os efeitos que aquela operação dispara no caminho feliz**, e nada além, senão a grade vira
asserção de vácuo.

> Casos medidos (legenda falsa; 17 de 21 células): `references/casos-medidos.md` §Estado × operação.

**A matriz cobra as duas metades.** "Toda célula vazia vira cenário negativo" é só metade da
regra — e seguir só ela deixa colunas inteiras sem **nenhuma operação bem-sucedida**. O caso
concreto: a coluna `editar` fica com três recusas e nenhuma edição que funciona, e a armadilha da
unicidade contra o próprio registro passa inteira. Cada coluna precisa de **ao menos uma célula
válida exercitada**, e é ela que se liga ao
gate de tela de escrita (`SKILL.md` §Escolha de Camada em Laravel/Filament).

**A matriz não é bidimensional.** `estado × operação` é a face visível; **quem** executa e **qual
campo** muda são dimensões, não detalhes do exemplo:

| Dimensão | Fixar significa perder |
|---|---|
| **estado** | transição ilegal |
| **operação** | ação sem barreira |
| **persona** | autorização inteira — percorrer toda a matriz com o dono do registro deixa a barreira de identidade sem um único cenário |
| **campo alterado** | a regra que depende do campo. Editar sempre "a descrição" deixa sem cenário justamente o campo que decide o fluxo (valor, centro de custo, papel) |

Percorrer estado × operação com persona e campo fixos produz uma matriz **"100% coberta"** com
duas dimensões intocadas. Escolher a persona e o campo é escolha **discriminante** — vale a mesma
regra dos valores: fixe o que revela a diferença, não o que é conveniente.

**A dimensão do campo tem de ser exercitada FORA do estado inicial.** Trocar o campo decisivo em
`rascunho`, onde tudo ainda é editável, não reabre a dimensão para os estados de trânsito — e é
exatamente ali que mora o defeito de recomputação (alterar o valor depois do envio sem reavaliar a
alçada). A linha inválida de `editar` precisa afirmar o **valor gravado**, não só que a operação
foi recusada.

**Célula só conta se a operação daquela célula for executada.** Apontar para um cenário que
executa **outra** operação — a listagem no lugar do detalhe, o `rascunho` no lugar do estado em
trânsito — é falso ✅. E **argumentar** que "uma implementação correta se comportaria igual nas
duas linhas" não é executar: o argumento pressupõe a corretude que a célula existe para testar.

**Verbo irmão não herda evidência.** Quando a regra diz "aprova **ou** rejeita", "edita **ou**
exclui", "publica **ou** arquiva, a autorização precisa ser falsificada em **cada verbo**. Uma
implementação que confere o ator em `aprovar()` e esquece em `rejeitar()` passa em todo conjunto
cuja evidência de autorização venha só do primeiro verbo — e o checklist lê ✅ cobrindo metade da
regra.

## Efeito idempotente: ancorar no agregado, não no recurso

Para verificar idempotência, a assertion vai sobre **o que sofre o efeito**, não sobre o que é
consumido. Aplicar o mesmo cupom duas vezes: o oráculo é *"o total do pedido é o mesmo depois da
segunda aplicação"*, não *"o contador do cupom foi a 2"*. Ancorar no recurso consumido prova
contabilidade e não prova idempotência.

**E o agregado tem de ser o persistido, não o retorno da chamada.** Se o `Então` afirma sobre o
valor **devolvido** por duas chamadas independentes, o cenário passa por construção quando o motor
é uma função pura — o mutante "acumula" nem sequer é expressável ali. O cenário só falsifica se
aplicar duas vezes **ao mesmo registro persistido** e afirmar sobre o estado dele.

**Quando o agregado está fora de escopo**, o cenário de idempotência é **inexpressável** — e
escrevê-lo assim mesmo produz um caso tautológico que parece cobertura. O procedimento é: **não
escrever o cenário**, registrar como **lacuna declarada** vinculada à premissa que tirou o agregado
do escopo, e transformá-la em **pergunta ao usuário**. Idempotência que não se pode ancorar não é
lacuna do conjunto — é consequência de uma decisão de escopo que alguém precisa confirmar.

## O exemplo tem de ser **discriminante**

Um cenário só mata um mutante se os **valores escolhidos** distinguem a implementação certa da
errada. Valor redondo é a forma mais comum de um cenário parecer cobrir e não cobrir.

Antes de fixar cada valor de um `Exemplos:`, perguntar: **a implementação defeituosa produziria
um resultado diferente com este valor?** Se produz o mesmo, o exemplo é decorativo.

| Defeito | Valor que **não** discrimina | Valor que discrimina |
|---|---|---|
| percentual em `float` em vez de inteiro | 10% de 10.000 → 1.000 nos dois | **29% de 10.000** → `(int)(10000*0.29)` = 2.899, inteiro dá 2.900 |
| arredondamento vs truncamento | qualquer divisão exata | resto ≠ 0 (5% de 50 → 2 ou 3) |
| off-by-one em limite | 1 e 10 num limite de 3 | **2, 3, 4** |
| unicidade sem normalização | `PROMO10` × `BLACKFRIDAY` | `PROMO10` × `promo10` × `" PROMO10 "` |
| ordenação instável | 3 registros distintos | dois registros **empatados** na coluna de ordenação |
| **autorização por identidade** | solicitante = gestor = quem chama, tudo na **mesma pessoa** | três pessoas distintas, e o ator sendo cada uma delas por vez |
| **canal do efeito** | "uma notificação foi enviada" | o **canal** que o requisito nomeia (`mail`, e não `database`) |
| **valor do requisito parametrizado** | injetar o limite por `config()` em todo cenário | ao menos um cenário com o **número literal do requisito** |
| **não-efeito** | "nenhuma notificação foi enviada" num mundo sem destinatário | o destinatário existe no `Dado` e o caminho feliz o notificaria |
| **direção da premissa** | cenário que assume "aceito" onde o requisito é silencioso | nenhum cenário da direção até a resposta (a `RQ` fica aberta); a ➡️ recomenda **falha fechado**, e o invariante vira cenário já |

As três últimas linhas são a versão não-numérica do valor redondo. **Persona colapsada** é o caso
mais comum: quando o mesmo usuário é dono, aprovador e chamador, nenhuma barreira de identidade é
exercitada, e todo cenário passa com a autorização removida.

Isto vale sobretudo para **precisão numérica e representação**, onde a implementação errada
acerta a maioria dos valores por acidente.

> Caso medido: `references/casos-medidos.md` §O exemplo tem de ser discriminante.

**O parâmetro livre nem sempre é o dado de entrada.** Em defeito de contexto — fuso, relógio,
locale, tenant — o que precisa cair na janela de divergência é o **instante ou o ambiente da
observação**, não o valor do formulário. Escolher o instante "bonito" é o mesmo erro do valor
redondo:

| Defeito de contexto | Parâmetro livre | Janela em que é observável |
|---|---|---|
| validade lida em UTC com app em `America/Sao_Paulo` | **o instante da aplicação** | as 3 h de deslocamento — testar às 20:00 não distingue nada; às 23:30 sim |
| virada de dia | o instante | os minutos ao redor da meia-noite **do fuso do app** |
| locale na formatação/ordenação | o locale ativo | um em que a ordem ou o separador difere (`pt_BR` × `en_US`) |
| escopo por tenant | o tenant do ator | um recurso que existe **no outro** tenant |

Antes de fixar o instante ou o ambiente, calcular **onde as duas implementações divergem** e
escolher um ponto lá dentro. E o `Então` precisa afirmar mais do que "aceito": o valor comparado,
o registro ou o estado.

**A discriminância vale para o `Dado`, não só para os `Exemplos:`.** O valor da coluna é o parâmetro
óbvio; a **configuração do mundo** é o parâmetro esquecido. Antes de fechar um cenário, perguntar
também: *nesta fixture, a implementação defeituosa produziria observável diferente?*

## Fechar uma lacuna declarada sem discriminar é **piorar**

Ao converter uma lacuna declarada em cenário, o gate é mais duro que o normal: **provar que o
novo cenário discrimina**, escrevendo em uma linha por que a implementação defeituosa produz
resultado diferente ali. Se não discriminar, a lacuna deixa de ser **declarada** (dívida que
alguém conhece) e vira **cega** (item ✅ com o defeito dentro) — regressão, mesmo que a contagem
de cenários suba.

> Caso medido: `references/casos-medidos.md` §Fechar uma lacuna declarada sem discriminar.

## Premissa: escopo apaga, mecanismo escolhe, comportamento **falha fechado**

Três coisas diferentes andam com o mesmo nome:

| Tipo de premissa | O que ela decide | Efeito legítimo no conjunto |
|---|---|---|
| **de escopo** | o comportamento **está fora** desta entrega (o agregado `Pedido` não existe) | o cenário é **inexpressável** → lacuna declarada + pergunta (raia requisito) |
| **de mecanismo** | **como** o sistema faz o que o requisito pede (a exclusão é física; `ativo` é derivado; o valor vem por `config`) | o cenário **continua obrigatório** → a premissa (raia desenho) só fixa em que mecanismo ele é escrito, marcado `@premissa` |
| **de comportamento** | **o que** o sistema faz quando o texto não diz: **se** aceita ou recusa algo que o requisito não decidiu (cadastrar cupom já vencido; percentual de 150; reduzir o limite abaixo dos usos feitos) | **pergunta da raia requisito** → a `RQ` fica `aberta — Qn`, sem cenário da direção até a resposta; a regra abaixo decide a **recomendação** (➡️), e o invariante vira cenário já. Mesma regra na entrevista do step 4 da `feature-wiki` e na derivação do step 7 |

**A direção da premissa de comportamento — hoje, a recomendação (➡️) da pergunta de requisito — é
escolhida por regra, não por conveniência: falha fechado.** Quando o requisito não decide se um
estado pode ser criado, e **outra cláusula do mesmo requisito já trata esse estado como inválido no
uso**, a recomendação é que a **gravação recuse**. Assumir "aceita" cria por decisão um estado que o
sistema depois precisa saber tratar — e é a suposição que, quando erra, deixa o cenário **vermelho
contra a implementação correta**.

**E o invariante das duas leituras vira cenário já**, porque ele vale qualquer que seja
a resposta: *seja qual for a decisão sobre gravar um cupom vencido, ele **não pode** ser aplicável*;
*seja qual for a decisão sobre reduzir o limite abaixo dos usos, o contador **não** é corrigido e a
trilha **não** é truncada*. O invariante é a parte do oráculo que nenhuma resposta à pergunta
inverte — e é ela que impede a lacuna de virar cega. Ele vem de uma cláusula **fechada** (a
aplicação, o total, a comparação de uso), e o cenário mora na regra dela, com origem nessa `RQ`.

| Pergunta de comportamento | ➡️ por falha fechado | Invariante — cenário já, na regra fechada |
|---|---|---|
| "cadastrar cupom já vencido é permitido?" | **recusa** — a cláusula da aplicação já trata o vencido como inválido | gravado por qualquer via, ele não é aplicável |
| "percentual de 150 é erro ou desconto?" | **recusa** — o total não pode ficar negativo | aplicado, o desconto nunca excede o total |
| "reduzir o limite abaixo dos usos feitos?" | **recusa** — cria `usos > limite`, estado que a comparação de uso já trata como esgotado | o contador não é corrigido; a trilha não é truncada |
| "qualquer papel pode executar a ação?" | **recusa** — ausência de barreira nunca se assume | nenhum cenário afirma que a barreira não existe |

**A direção não vira cenário enquanto a pergunta está aberta.** A `RQ` afetada fica
`aberta — Qn` e nenhum passo do `01` a implementa: um cenário `@premissa` sobre ela afirmaria um
comportamento que ninguém vai construir — ou forçaria a construção com a interpretação do dev, que
é o que a raia requisito existe para impedir (estudo 2026-09-26 §2.3). O buraco na partição não
fica cego: o `04` lista `RQ-nn — aberta (Qn), sem cenário até a resposta`, o item do passo 4
(*"valor abaixo do mínimo, acima do máximo e no limite — na gravação"*) responde
`lacuna declarada: RQ-nn aberta (Qn)`, e o invariante já tem cenário. Quando a resposta entra como
Adendo, o cenário da direção é derivado dela.

**Custo declarado da troca**: a detecção medida do cenário direcional (a recusa na criação, em
`references/casos-medidos.md` §Premissa de comportamento) fica suspensa até a resposta, e o
invariante não a substitui — *gravado por qualquer via, não é aplicável* não pega *vencido aceito na
criação*. A lacuna fica declarada, não cega; o preço é detecção até o Adendo.

> Caso medido: `references/casos-medidos.md` §Premissa de comportamento.

Premissa de mecanismo, por sua vez, não tira comportamento nenhum do escopo. Usá-la para apagar o
cenário é converter uma escolha de implementação em cobertura — e o resultado é sempre o pior dos
dois mundos: item ✅ no checklist com o defeito dentro.

| Premissa de mecanismo | A pergunta que ela **não** dispensa |
|---|---|
| "a exclusão é física" | o registro removido ainda funciona nas operações de escrita? |
| "`ativo` é estado derivado, não tem coluna" | o derivado desligado (vencido, esgotado) ainda é aplicável? |
| "o limite vem de `config`, não do banco" | o valor literal do requisito produz o mesmo resultado? |
| "o histórico é uma tabela própria, não a trilha de auditoria" | o registro sai completo pelo caminho que **não** dispara evento de model? |

O procedimento: escrever o cenário **no mecanismo assumido**, e registrar o mecanismo descartado
como **lacuna declarada** vinculada à premissa, com a pergunta de desenho ao desenvolvedor. Duas
linhas de custo.

> Caso medido: `references/casos-medidos.md` §Premissa de mecanismo.

## Impossibilidade de arnês é hipótese, não conclusão

Antes de declarar um mutante como "sem matador porque o arnês não permite", **tente mudar o
arnês**: `config(['app.timezone' => ...])` para divergir app e banco, `travelTo()` para a virada
do dia, `DB::statement` para pragmas, factory com estado inválido gravado direto. Só depois de
tentar é que a lacuna é real — e aí ela é declarada com **o que foi tentado**.

## Afirmação negativa é hipótese até um `grep` prová-la

*"Não precisa de escopo"*, *"não se aplica: não há upload"*, *"o filho já está protegido pelo pai"*.
Toda negativa que **dispensa um controle** entra na wiki com a mesma exigência de evidência que a
positiva: `arquivo:símbolo:linha` do vendor — e, quando dispensa um controle de **fronteira** (escopo,
autorização, trava de escrita), também **um cenário escrito como se ela fosse falsa**. O formato é o
da `feature-wiki` (`{skills}/feature-wiki/SKILL.md`, *Citações de código*): o `04` é conferido pelo
`citacoes.sh` no step 10 e na dimensão L2 do quality gate, e `arquivo:linha` sem símbolo é achado lá.

O motivo é assimétrico e vale a pena enunciar: a tabela de mutantes deriva mutantes das regras
**escritas**. O que a wiki declara desnecessário não vira regra, não vira mutante e não vira
cenário — fica fora do gate de falsificabilidade inteiro. É o único ponto do pipeline onde **uma
frase sozinha remove um controle sem deixar rastro vermelho**.

> Caso medido: `references/casos-medidos.md` §Afirmação negativa.

## Todo estado de erro declara a saída

Um cenário que termina em `Então a resposta é 403` está metade escrito. A outra metade é **para
onde o usuário vai depois** — e ela pertence ao cenário irmão, nunca ao "ficou implícito".

A classe de defeito que isso pega não aparece em cenário nenhum **isolado**: *A devolve para B e B
devolve para A*. Cada um, sozinho, está certo; juntos, trancam o usuário fora da aplicação. É a
mesma cegueira do 1-switch: o defeito mora na transição de volta.

Regra: todo cenário cujo `Então` é 4xx, 5xx ou redirect ganha um par que afirma **um destino
alcançável a partir dali**. Se não existir destino, o achado não é do teste — é de desenho, e volta
para o `00` como pergunta.

> Caso medido: `references/casos-medidos.md` §Todo estado de erro declara a saída.
