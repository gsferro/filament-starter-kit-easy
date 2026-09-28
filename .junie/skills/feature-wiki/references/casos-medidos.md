> Referência da feature-wiki 4.0.0. Lida em: quando um step do `SKILL.md` aponta para cá — nunca é pré-requisito de execução. Fonte única de: os casos medidos que originaram as regras do corpo (a regra fica no `SKILL.md`; aqui fica a evidência).

# Casos medidos

Cada seção guarda o texto do caso como estava no `SKILL.md` até a 3.5.2, quando a regra e o caso
moravam juntos no procedimento. Onde o caso estava na mesma frase da regra, a frase inteira vem
junto — a regra em vigor é a do corpo. As rodadas do protocolo experimental (kit, braços, juiz)
não ficam aqui: a tabela única delas está em [`experimentos/README.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md).

**Numeração dos steps.** Os títulos usam a numeração da 4.0.0. Os trechos citados — os marcados
*"Texto da 3.5.2"* e as tabelas de medição — conservam a numeração da época em que foram escritos.
A correspondência antigo → novo está no `README.md` da skill, seção *Numeração dos steps*.

## Topo — o step 9 é o gate mais produtivo (2026-09-17)

> Texto da 3.5.2 (numeração 3.x: 6.5 = step 9, 8 = step 11) — não normativo. A regra vigente está no `SKILL.md`, step 9.

> Até a 3.3.0 ele era o step 7.5: o último antes do quality gate, no fim do documento, e o mais
> fácil de adiar. **É também o mais produtivo.** Medido em 2026-09-17, numa feature com wiki
> completa — 43 CTs, revisão adversarial com cinco implementações erradas fechadas, auditoria
> Ponytail com dez cortes aplicados, 61 testes verdes:
>
> | Gate | Achados de correção |
> |---|---|
> | step 5 — revisão profunda (premissas do plano) | 2 (nomes de classe e de método errados no PRD) |
> | step 6 — `ponytail-review` (excesso no plano) | 0 — **por charter**: *"correctness bugs, security holes and performance are explicitly out of scope"* |
> | revisão adversarial do `04` (requisito × cenários) | 0 de correção; 5 de **cobertura**, que é o trabalho dela |
> | suíte de testes verde, 2.383 casos | 1 (enforço de arquitetura do próprio projeto) |
> | **step 6.5 (então 7.5) — `/code-review` no diff** | **7**, dois deles produzindo 500 em produção |
>
> Os sete não eram visíveis para nenhum gate anterior, e o motivo é estrutural: o step 6 exclui
> correção por definição, a revisão adversarial só enxerga o que o **requisito** descreve, e o
> step 8 pergunta *"o requisito foi atendido?"* — nenhum deles pergunta *"este código está certo?"*.

## Validado em campo — 2026-09-21, feature completa no `demo-wiki`

> As tabelas desta seção conservam a numeração 3.x da medição: 6.5 = step 9, 7 = step 10, 8 = step 11.

A 3.4.0 desenhou o modelo; a 3.5.0 o **mediu** numa feature real de ponta a ponta (fluxo de
aprovação de compra em Laravel 13 / Filament 5: 18 `RQ`, 28 premissas, 79 CTs, 182 testes,
14 commits, 48 despachos, ~4,6 M tokens de sub-agente, quality gate ciclo 1 devolvido
`REPROVADO → especificação` com 8 achados). O que a medição **confirmou** vira regra dura nesta
versão; o que ela **desmentiu** está corrigido nas seções indicadas, cada uma com o caso.

| Confirmado | Evidência |
|---|---|
| **Cegueira vale mais que modelo.** O juiz cego acha o que a sessão não acha, mesmo com a sessão num modelo mais forte | duas rodadas adversariais (`opus`, só `00` + `04`) acharam 5 implementações erradas sobre 60 CTs derivados por outro `opus`, e uma regra que eram duas; o 6.5 cego produziu 14 achados que mudaram código, `00` (P-24, P-25) e `04` (CT-76..79) |
| **O juiz cego acusa a própria sessão.** O step 8 pegou duas alegações falsas da `## Verificação Final` — *"88 citações ok"* sem comando por trás e *"sem PCOV / mutate não instalado"* sem `php -m` — que nenhum gate anterior tinha como ver | QA-03 e QA-04 do ciclo 1; as duas eram do orquestrador, não do código |
| **O juiz cego faz a pergunta de requisito que ninguém fez** | gestor que acumula `diretor` assinava as duas etapas sozinho; quem já decidiu perdia a solicitação de vista e o link do e-mail virava 404 — QA-01 e QA-02, nascidos só no step 8 |
| **6.5 antes do 7.** Os 14 achados do 6.5 deslocaram citações, criaram IDs de CT e mudaram frases de ADR | se o 7 tivesse rodado antes, teria sido refeito inteiro |
| **Contrato de executor com classificação a/b/c** (teste errado / implementação divergente / flake) e *"nunca tocar `app/`"* | 5 lotes de teste; todo vermelho que sobrou era defeito real (CT-58: ação sem registro → 500; CT-59: justificativa sem teto) |
| **Auditoria do retorno é obrigatória, sobretudo com `haiku`** | 3 de 8 retornos `haiku` tinham defeito (escape de FQCN no grep → *"sem ocorrências"*; `preload()` *"não encontrado"* e existia; *"nada cresce com N"* medido com paginação de 10). Todos pegos por amostragem |
| **Quadro de despacho mede custo** | 48 linhas em `## Despachos`; a maior parte do custo foi `sonnet` construindo; o `Explore` sozinho custou 139 k tokens |

O que ela **desmentiu**, e onde está a correção (seções do `SKILL.md`): `pest --mutate` dá 100 % falso no Windows
(seção *Execução de Testes com Pest 5*); `haiku` em lote misto faz um item e reporta três
(*Rotas*); o `04` derivado antes dos cortes do Ponytail fica com CT órfão (*step 4*, *step 6* e
*step 7*: na 4.0.0 o `04` nasce depois dos cortes); a `## Superfície Livewire` envelhece durante a
implementação (*step 9*); número na `## Verificação Final` sem o comando que o gerou é alegação
(*Arquivo 03* e *Auditoria do retorno*); CT que passa dos dois lados do `git stash` pode ser a
pilha, não o CT (*step 9*).

## Rota `mecânico` — 2026-09-21

> Texto da 3.5.2, com a regra junto do caso — não normativo. A regra vigente está no `SKILL.md`, seção *Rotas*, *Regras da rota `mecânico`*.

**Regras da rota `mecânico`, medidas em 2026-09-21:**

- **Um item por despacho** — um grep em lote com a tabela pronta, uma conversão, um espelho. Lote
  misto (converter + mover + preencher tabela) vai para `construtor`: o `haiku` fez 1 de 5 itens e
  reportou 3 como feitos, com um `git diff --stat` de arquivo que era untracked
- **FQCN em grep vai com `grep -F`** — o escape das barras produziu um *"sem ocorrências"* falso
- **`Explore` (built-in) só para feature grande.** Para o resto, `mecânico` com **trechos**, não
  arquivos: o `Explore` custou 139 k tokens onde cada `haiku` custou 40–60 k

## Sinais que reprovam o retorno — 2026-09-21

> Texto da 3.5.2, com a regra junto do caso — não normativo. A regra vigente está no `SKILL.md`, seção *Auditoria do retorno*.

**Sinais que reprovam o retorno antes da amostragem** (todos medidos em 2026-09-21):

- **número sem comando** — *"88 ok"*, *"32 convertidas"*: se o retorno não traz o comando e a
  saída literal, o número não existe. Um deles chegou à `## Verificação Final` e foi o juiz cego
  quem o derrubou
- **`git diff --stat` como prova de arquivo untracked** — a wiki nova não aparece no diff; retorno
  que a "prova" por ele não a conferiu
- **"não encontrado" / "não instalado" sem a prova negativa** — `ls vendor/…`, `php -m`, `grep -c`
  colados. Duas dessas afirmações (`Select::preload()`, `pest-plugin-mutate`) eram falsas
- **"sem ocorrências" em grep com FQCN** — conferir o escape (`grep -F`) antes de aceitar
- **conclusão de custo sob paginação** — *"nada cresce com N"* medido com página de 10 linhas não
  mede nada; pedir N acima da página

## Step 3 — Superfície Livewire

> **A condição desta seção já foi "quando a feature monta sobre pacote de terceiro", e essa redação
> custou dois defeitos** (caso real, 2026-09-17). A feature montava sobre o **framework**, não sobre
> um pacote; o agente leu a condição ao pé da letra, declarou *"nenhum pacote persiste entidade →
> não se aplica"* e a tabela nunca foi preenchida. Passaram: um método público do componente
> (**ação chamável por `$wire.`**, que devolvia coluna não exposta e produzia 500 com nome
> inexistente) e um array público de filtro consumido sem validação (`$rotulos[$valor]` e
> `Carbon::parse($valor)` → dois 500). A condição certa é a superfície, não a origem dela.

> **Por que este bloco existe** (caso real, 2026-09-15): uma feature com wiki completa — 29 CTs,
> revisão adversarial, 11 regras, 38 mutantes — entregou **quatro defeitos**, dois deles de escrita
> cross-tenant. Os quatro moravam nesta superfície: ação do pacote buscando o filho por id cru do
> cliente, propriedade pública Livewire sem `#[Locked]`, escopo que falhava aberto no caso nulo e
> 403 do vendor sem saída. A wiki citava o vendor corretamente para justificar desenho e **nunca o
> inventariou como superfície de ataque**. O texto que liberou o pior deles foi uma dedução sem
> grep: *"o filho não precisa de escopo, é sempre alcançado pelo pai"*.

## Step 5 — revisão profunda e classe irmã

> Exemplo real (feature/implementar-carga-horaria): a revisão pós-escrita detectou que o import `MbaTrack` já existia no arquivo a editar e confirmou o padrão exato do guard de environment nas migrations com seeder — ambos corrigidos na wiki antes da implementação.

> **Caso real, 2026-09-17.** A feature criou uma Page de dashboard nova. O agente leu a rule do
> projeto sobre tela de entrada, entendeu a decisão e atualizou **a lista do teste**
> (`$telasDeEntrada`). Existia uma **segunda** lista, em `config/filament-shield.php`
> (`pages.exclude`), com as outras quatro telas de entrada — e a informação de que ela existia
> estava só num **comentário dentro do próprio config**. Sem a linha, o Shield geraria uma
> permission `View:{Page}` que apareceria como checkbox na tela de papéis e **não mudaria nada
> quando desmarcada** — o "checkbox que mente". O `grep` pelo FQCN da irmã devolvia as duas listas
> em segundos; nenhum outro gate da wiki olha para listas paralelas.

## Step 6 — ordem com a derivação do 04 (2026-09-21)

> Texto da 3.5.2 (numeração 3.x), com a regra junto do caso — não normativo. A regra vigente está no `SKILL.md`, step 6: na 4.0.0 o `04` passou a nascer no step 7, depois dos cortes, e a re-sincronização só vale para corte depois do step 7.

**Ordem com o step 4 (medido em 2026-09-21)**: o `04` derivado **antes** dos cortes do Ponytail
herda os elementos cortados. Na feature de referência o Ponytail removeu um filtro de tabela e o
CT-42 ficou órfão — só apareceu no `diff` de IDs do step 7. Duas regras:

1. **Preferir** rodar este step sobre `01`/`02` **antes** de invocar a `feature-test-design`: a
   derivação recebe do `01` só paths, rotas e `## Superfície de UI` — exatamente o que os cortes
   mudam
2. Se o `04` já existe quando um corte muda rota, ação, filtro ou coluna da `## Superfície de UI`,
   **re-sincronizar o `04` no mesmo passo**: um `mecânico` cruza o `## Índice de Cenários` com os
   elementos cortados; cada CT atingido vira `@obsoleto` com o motivo (e sai do índice com `~~`)
   ou é re-derivado pela `feature-test-design`. Registrar em `### Auditoria Ponytail (step 6)` do
   `03`. CT órfão descoberto só no step 7 é sinal de que este item foi pulado

## Step 8 — o limiar de tamanho (2026-09-21)

> Justificativa do limiar de tamanho do step 8 (4.0.0). O limiar é hipótese a calibrar; o que está
> aqui é o único ponto medido.

A feature do `demo-wiki` (tabela *Validado em campo*, acima) é a única medida de ponta a ponta: 18
`RQ` no `00`, 60 CT na derivação do `04` (79 no fim, depois dos achados dos gates), 48 despachos e
~4,6 M tokens de sub-agente — numa sessão só. Ela coube: foi até o quality gate. Mas o estudo de
2026-09-26 (§4.2) a usa como o caso em que a coletânea não tinha regra de dimensionamento: a
retomada, se a sessão caísse, seria pelo `03`, por passo, sem nada demonstrável até o último.

Por isso o limiar fica no tamanho dela, e não abaixo:

- **Abaixo** de 18 `RQ` e 60 CT não há medida de que fatiar compense, e a regra da `to-tickets` é
  *"se cabe numa janela de contexto, você não precisa desta skill"*. Um limiar menor seria número
  inventado
- **No tamanho dela ou acima**, a sessão única já exigiu 48 despachos e a retomada seria por passo —
  o custo que a fatia vertical existe para evitar
- Os números são contados **no step 8**: `RQ` com `Estado` diferente de `substituída`, e CT do
  `## Índice de Cenários` do `04`. `P-nn` ficam fora: o número delas no step 8 não é comparável ao
  da feature medida, contado no fim

Para calibrar, a linha `Não fatiado — …` do `03` registra os números conferidos; com mais de um
ponto, o limiar pode mudar.

## Step 9 — revisão do diff

> Texto da 3.5.2 (numeração 3.x: step 6.5), com a regra junto do caso — não normativo. A regra vigente está no `SKILL.md`, step 9.

**Pré-requisitos do lote** (os dois medidos em 2026-09-21):

- **A sessão roda no repositório do projeto.** O `/code-review` só alcança o diretório onde a
  sessão foi aberta; sessão aberta noutro repositório não consegue apontá-lo para o projeto. Nesse
  caso o passe 1 é substituído por um `analista` (`opus`) **cego**, com o mesmo alvo
  (`{base}...HEAD`) e sem os eixos, e a linha de `## Despachos` declara *"passe genérico por
  sub-agente — `/code-review` fora de alcance"*. Vale menos: o comando nativo tem heurísticas
  próprias que o substituto não tem
- **A `## Superfície Livewire` do `02` foi re-varrida sobre o código final.** A tabela nasce no
  planejamento e **envelhece** durante a implementação: na feature de referência ela negava
  superfície de vendor, e as duas Pages herdavam uma trait com quatro métodos `$wire.` que recebem
  índice de array. Um `mecânico` refaz os quatro greps sobre o diff final **antes** de despachar o
  revisor; a tabela atualizada é o que ele recebe — a antiga é insumo do plano, não prova

> Os quatro eixos em negrito vieram do caso de 2026-09-17 — foram exatamente os achados que os
> gates anteriores não tinham como ver, e cada um deles já era um 500 ou um checkbox que mente.

Na feature de referência 3 de 5 CTs novos caíram na terceira linha. Lida como regra absoluta, a
frase *"decoração"* mandaria apagar guardas corretas.

> Caso real (2026-09-15): a revisão de código do diff de uma feature já "verde e concluída" achou
> quatro defeitos — dois de escrita cross-tenant, um de fail-open e um beco sem saída na raiz do
> painel. Os steps 5, 6 e 7 tinham rodado; o 8 não. Nenhum dos quatro seria pego por nenhum deles,
> porque todos os quatro estavam **corretos em relação ao plano**.

## Step 10 — reconciliação

> Texto da 3.5.2 (numeração 3.x: step 7), com a regra junto do caso — não normativo. A regra vigente está no `SKILL.md`, step 10.

Após a implementação, os testes passarem e o **step 6.5 ter rodado** — e **antes de abrir o PR e
antes de escrever "concluída" no `03`**. A ordem é **6.5 → 7 → 8 → PR**: o diff que se reconcilia
aqui é o diff **pós-revisão**. Abrir o PR antes foi o que produziu, num caso
real, uma feature "concluída" com quality gate "para o passo seguinte", quatro quebras reais e 27
afirmações defasadas na wiki e nas docs.

   **Saída vazia é o critério**; linha com `<` é CT sem teste, linha com `>` é teste sem CT. A
   saída vai colada na `## Verificação Final` — sem ela o checkbox não fecha. (Caso real: o `04`
   declarava um CT de ciclo liga/desliga com dois mutantes exclusivos e **nenhum teste o
   implementava**; o checkbox *"testes conforme 04/05"* fechou assim mesmo, e a lacuna só apareceu
   numa revisão de código posterior. O `diff` acima leva segundos e a teria pego no dia.)

   > Medido numa mesma wiki, três vezes: nove citações `arquivo:linha` desatualizadas, uma
   > varredura colada na `## Superfície Livewire` que o código já contradizia, e uma contagem que
   > o quality gate acusou — corrigida no `01`, mantida na ADR do `02`, que repetia o mesmo
   > número. **A defasagem sobreviveu ao gate que existia para pegá-la**, porque o gate leu o
   > arquivo onde esperava a afirmação e conferiu ali.

6. **Conformidade com as rules do projeto.** Para cada rule em `.ai/rules/index.md` cujo glob
   casa com um arquivo do diff, uma linha na tabela `## Conformidade com Rules` do `03`:
   `rule → aplicada / n.a. / violada`, com evidência (`arquivo:símbolo:linha` ou nome do CT).
   Rule violada é blocker do PR. O step 3 manda **ler** as rules antes de planejar; este item
   confere se o **código** as cumpre — são coisas diferentes, e a segunda nunca era feita
   (medido: `group('kit')` em teste de browser, chave `KIT_*` fora do `phpunit.xml`, par de
   cenário exigido pela rule de auth ausente — três rules lidas no step 3, três violadas no código)

## Step 11 — por que a ordem é dura

> Numeração 3.x: o quality gate era o step 8 e a reconciliação, o 7.

> Por que a ordem é dura: "linkar ao PR" ficava no step 7 e o quality gate no step 8, e o
> checklist chamava a seção de "após merge". Lido ao pé da letra, o QA acontecia depois do merge
> — e foi exatamente o que uma sessão real fez: PR aberto, `03` "concluída", quality gate nunca
> executado. O veredito é parte do PR, não um passo depois dele.

## Adendo ao requisito

> Texto da 3.5.2, com a regra junto do caso — não normativo. A regra vigente está no `SKILL.md`, seção *Adendo ao requisito* — na 4.0.0, só para pedido do solicitante; achado de revisão virou premissa `P-nn`.

O `## Texto Original` é imutável, e a skill só previa "sobrescrever / incrementar / retomar" a
wiki inteira. Entre os dois cabia o caso mais comum: o usuário pede **mais uma coisa** no meio da
implementação, na mesma branch e no mesmo PR. Sem procedimento, o pedido novo vai direto para o
código, e os testes dele nascem **do código** — a inversão exata que a `feature-test-design`
existe para proibir. Caso real: o "carimbo do painel no log de acesso" chegou depois da wiki
pronta; cinco cenários foram escritos a partir da implementação e nenhuma cláusula do `00` os
sustentava.

## Arquivo 03 — checkbox com evidência inline

> Texto da 3.5.2, com a regra junto do caso — não normativo. A regra vigente está no `SKILL.md`, seção *Arquivo 03: Progresso / Tracking*.

**Checkbox só fecha com evidência inline.** Formato `- [x] {item} — {evidência}, {data}`; item sem evidência continua `[ ]`. "Atualizar em tempo real, não em lote" já estava escrito aqui e foi ignorado: num caso real a Verificação Final foi fechada por substituição em lote antes de alguns comandos rodarem, e um teste marcado verde estava vermelho. A evidência inline é o que torna o lote impossível — não há o que colar. Conferência: `grep -n '^- \[x\]' 03-progresso.md | grep -v ' — '` tem de voltar vazio na Verificação Final.

## Modelo de Execução do `01` (2026-09-17)

> Texto que ficava dentro do template do `01` até a 3.6.0 — e por isso era copiado para todo `01`
> gerado. A regra vigente está no template, seção `## Modelo de Execução`.

**Este bloco existe porque uma ADR pode estar internamente coerente e apoiada numa premissa que
ninguém escreveu.** Caso real (2026-09-17): uma ADR decidiu, com bom argumento, não cachear o
agregado quando há filtro — e assumiu implicitamente *"uma tela = um request"*. Os widgets eram
`lazy`, ou seja **oito requests independentes**, cada um recalculando: 48 queries viraram ~384 por
carga filtrada. O memo por request que o código documentava **não existia**, e não teria ajudado —
memo estático não atravessa request. Nenhum gate da wiki mede custo; declarar o modelo é o que
torna a premissa falsificável na revisão.

## `$table` em model novo (2026-09-21)

> Texto que ficava dentro do template do `01` (seção `## Filosofia de Implementação`) até a 3.6.0.

> **Model novo declara `$table`** sempre que o nome da tabela não for o plural inglês que o
> Eloquent infere — com nome em pt-BR é sempre: `centros_custo`, não `centro_custos`. Nasceu como
> defeito (2026-09-21) e é candidato natural a Project Rule no step 9.

(Numeração 3.x: o step 9 é o atual step 12.)

Na 4.0.0 a frase *"candidato natural a Project Rule"* saiu do template: se uma decisão vira rule
é a definição única *Vale virar rule* da `requirement-to-rule` que diz, e o gate 3 dela (não-inferível)
pode reprová-la (estudo §7.5). A prática de declarar `$table` continua no template.

## Perguntas-semente do `00` (2026-09-21)

> Texto que ficava no comentário do template do `00` até a 3.6.0, impondo as quatro perguntas a
> toda feature. Na 4.0.0 elas viraram sementes **condicionais** da raia requisito
> (`references/entrevista-tres-raias.md`), porque vieram de um único domínio (fluxo de aprovação de
> compra) — overfitting apontado pelo estudo de 2026-09-26, §7.1 T10.

```text
Perguntas OBRIGATÓRIAS quando o requisito tem papéis, visibilidade, notificação ou texto livre
(as quatro que o juiz cego fez em 2026-09-21 e a sessão não fez):
1. Acumulação de papéis, PAR A PAR: quem é X pode também ser Y? (solicitante × aprovador,
   aprovador × aprovador de outra etapa). Uma pergunta por par, não uma genérica
2. Recorte de visibilidade: quem VÊ agora × quem JÁ PARTICIPOU. O participante histórico
   continua vendo? O que vê quem perdeu a corrida?
3. Toda notificação tem link: para ONDE leva, e o destino ainda existe e está visível para o
   destinatário quando ele clica?
4. Todo texto livre tem teto — no model, não só no formulário?
```

A origem das quatro está na tabela *Validado em campo* acima: QA-01 e QA-02, nascidos só no step 8
(numeração 3.x; hoje step 11).

## Verificação Final sem comando nem prova negativa (2026-09-21)

> Texto que ficava no comentário da `## Verificação Final` do template do `03` até a 3.6.0.

Número sem comando e ausência sem prova foram os dois achados que o juiz cego devolveu CONTRA A
SESSÃO em 2026-09-21 (QA-03, QA-04).
