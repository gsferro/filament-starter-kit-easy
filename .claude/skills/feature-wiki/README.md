# feature-wiki — Documentação Antes de Implementar

## Correção 4.2.1 — esperado completo no roteiro

A [Parte 2 da rodada 2026-10-20](../../../experimentos/2026-10-20-roteiro-manual/RELATORIO-PARTE-2.md) gerou o `08` sobre uma implementação real. Os dois
roteiros mantiveram o oráculo — nenhum esperado divergente ou sem respaldo, e a divergência do app
registrada sem rebaixar o CT —, mas 43 de 72 esperados eram **parciais**: preparação citando o CT
como verificação e pós-condições do `Então` resumidas. A reference do `08` ganhou a seção
*Esperado completo, preparação separada*:

- o passo que verifica reproduz **todas** as pós-condições observáveis do `Então` (valor exato,
  estado, contagem, unicidade, ausência de efeito colateral);
- preparação leva `prep ·` na Origem e descreve só o estado que o `Dado` pede;
- passo negativo diz o motivo esperado da recusa e, conferido, registra a classe ou mensagem;
- preparação documentada é a executada: corrigida no meio, reexecutar desde o início.

Numa réplica exploratória (fora do pré-registro, uma geração), o esperado fiel ao CT subiu de 38%
para 98% das linhas; o roteiro ficou 17% mais longo. A auditoria do log confirmou que todas as
conferências declaradas pela 4.2.0 rodaram de fato.

## Atualização 4.2.0 — roteiro de teste manual opcional

A wiki pode entregar o **`08-roteiro-teste-manual.md`**, um caminho de execução para quem vai
conferir a feature: revisor do PR, QA humano, solicitante na homologação ou operador do deploy.
O arquivo pertence à `feature-wiki`; o `feature-quality-gate` 1.9.0 audita sua consistência no
item **L8**, dentro das **12 dimensões já existentes**. Não há uma nova skill nem uma décima
terceira dimensão. O `08` não substitui Pest, CT-B ou o quality gate.

A decisão aparece no **step 4**, numa rodada da entrevista na **raia de desenho**, dirigida ao
desenvolvedor. A recomendação `➡️` é **sim** quando há UI, aceite humano, integração externa que
não pode ser automatizada — e-mail, gateway em sandbox, SSO —, comando Artisan ou job de fila
operado por uma pessoa, ou deploy manual. Para refatoração interna ou job já provado por Pest,
sem uma dessas necessidades, a recomendação é **não**. Há custo de tokens para escrever, conferir
e auditar o roteiro e custo de manutenção quando o caminho de execução muda; ele não é entrega
automática de toda wiki.

Pedidos como **“gere o roteiro manual”** ou **“sem roteiro manual”**, inclusive feitos antes da
entrevista, resolvem a decisão sem outra pergunta. Uma instrução explícita em `AGENTS.md` ou
`CLAUDE.md` também a resolve. A decisão e sua fonte ficam no `03`; a sessão não pergunta de novo
o que já foi autorizado. A pergunta consome um `Qn`, mas é meta-processo isento do filtro de
oráculo e **não bloqueia** `Entendimento confirmado` nem o step 5. Sem resposta até o fim do
step 4, em qualquer modo, registra **não — sem resposta a Qn**. A pergunta e a decisão ficam
**só no `03`**, sem entrada no `00`, nas decisões de desenho do `01` ou no `02`, neste formato:

```text
Roteiro manual: {sim | não} — {YYYY-MM-DD} — {fonte: resposta a Qn | instrução explícita do desenvolvedor | convenção {arquivo} | sem resposta a Qn} — {sinal ou motivo}
```

O desenvolvedor pode responder ou mudar depois; sempre se acrescenta linha abaixo e se riscam
as anteriores com `~~…~~`.

O oráculo já existe: `00-requisito.md`, `04-casos-de-teste.md` e, quando houver, o `05` de browser.
Depois da implementação e da reconciliação do **step 10**, a sessão usa o produto para descobrir
**como chegar** ao comportamento especificado: rota, tela, botão, comando, seed ou fixture. A
geração do `08` é uma etapa **sem número, depois do 10 e antes do 11**; os steps 0–12 mantêm seus
números. Se o gate mandar corrigir código, o retorno passa pelo step 10 e reconcilia também o
`08` antes da nova auditoria. A implementação não ganha autoridade para definir o resultado
esperado.

Também é possível pedir o roteiro **a qualquer momento, inclusive depois de abrir o PR**.
Antes do step 10, o pedido só atualiza a decisão no `03`; a geração ocorre na etapa normal.
Depois do step 10, gera o arquivo na hora, com os artefatos e a implementação atuais. Se o quality gate
já terminou, o cabeçalho declara **`Auditoria: não auditado pelo quality gate (gerado depois do
ciclo {N})`**. O veredito antigo não é estendido ao arquivo novo. Para feature fatiada, existe
**um único `08` por feature**, depois do último ticket `concluído`; cada RM identifica o
**`Ticket`** correspondente. O pedido tardio preserva essa ordem.

### O que uma pessoa recebe no `08`

Cada **`RM-nn`** corresponde a um fluxo de valor, com caminho feliz e variações negativas e de
permissão pertinentes. Não é uma transcrição de todos os CT. Cada roteiro informa ambiente,
perfil, dados de exemplo, preparo, ações e resultado esperado observável. A tabela de resultados
tem a coluna **`Origem`**, com **CT, CT-B, RQ ou P-nn existente** para cada esperado. O código só
fornece o caminho de acesso e o preparo; um comportamento encontrado sem rastro vira achado do
step 9, com premissa `P-nn` quando aplicável, CT e correção; repete o step 10 antes de retomar o roteiro.

Estes exemplos são didáticos: supõem que os CT citados já especificam os resultados abaixo;
os paths e comandos devem corresponder à aplicação real. Eles não relatam execução nesta
coletânea.

**RM-01 — conferir a aprovação em lote pelo terminal.** Perfil: operador local; ambiente:
banco descartável, com pedidos elegíveis e não elegíveis preparados por seed/fixture documentado.
Execução: `php artisan pedidos:aprovar-lote --dry-run`.

| Ação ou variação | Resultado esperado | Origem |
|---|---|---|
| Executar o `--dry-run` com pedidos elegíveis | Os elegíveis aparecem na prévia e seus estados permanecem inalterados | CT-01, RQ-01 |
| Incluir pedido fora da condição de aprovação | O pedido não aparece entre os elegíveis e mantém o estado anterior | CT-02, RQ-02 |
| Executar com perfil sem permissão, se o comando exigir esse perfil | A operação é recusada sem alterar os pedidos | CT-03, P-01 |

**RM-02 — gestor aprova um pedido pela aplicação.** Perfil: gestor de exemplo autorizado;
ambiente: aplicação local; URL de exemplo: `http://localhost/admin/pedidos`. Preparar o pedido
pendente pela fixture indicada, abrir seu detalhe e clicar no botão **Aprovar**.

| Ação ou variação | Resultado esperado | Origem |
|---|---|---|
| Gestor autorizado clica em **Aprovar** no pedido pendente | O detalhe passa a exibir o estado Aprovado | CT-B01, RQ-03 |
| Repetir a tentativa num pedido já decidido | O estado anterior é preservado, sem uma segunda decisão | CT-04, P-02 |
| Abrir o mesmo detalhe como solicitante sem permissão | A ação **Aprovar** não está disponível para esse perfil | CT-B02, RQ-04 |

Quando o ambiente permite, a sessão confere os RM por terminal não destrutivo e percorre a
aplicação via **Playwright MCP**, se disponível. Cada RM registra **`conferido pela sessão
{data}`**, com evidência, ou **`não executado — {motivo}`**; o **cabeçalho do `08`**
registra a contagem **n/m**. Sem ferramenta, ambiente ou execução, o estado permanece não
executado. Conferência documental não vira alegação de que cliques ou comandos rodaram.
Comando destrutivo exige ambiente descartável confirmado e aviso explícito no roteiro;
credenciais de exemplo não contêm senha real. A L8 **nunca executa RM destrutivo**, nem RM que
grava estado fora de ambiente descartável confirmado: registra **Não Verificado —
destrutivo/ambiente não confirmado**. O gate só executa RM não destrutivos contra app local servido.

O procedimento e o formato do arquivo ficam na [referência do roteiro manual](references/template-08-roteiro-manual.md).
**Parte 1 da [rodada 2026-10-20](../../../experimentos/2026-10-20-roteiro-manual/RELATORIO.md)** (Codex, N = 1): a inclusão do roteiro não alterou a derivação do `04` — mesmos
defeitos detectados que a 4.1.1 nos dois cenários do protocolo. A pergunta do step 4 foi feita na
raia de desenho, registrada só no `03` e dispensada diante de instrução explícita, inclusive um
`não` contra os sinais. Uma célula copiou o exemplo da semente e inventou o sinal; o exemplo virou
placeholder e a reexecução passou. **Não medidos**: qualidade do `08` gerado, a L8 do gate, uso por
pessoas, custo de tokens e equivalência com Claude Code. A [Parte 2](../../../experimentos/2026-10-20-roteiro-manual/RELATORIO-PARTE-2.md) mediu o `08`
gerado e a L8: ver a *Correção 4.2.1* acima.

## Correção 4.1.1 — controles proporcionais à capacidade do host

A revisão independente da rodada real encontrou obrigações Claude ainda no checklist universal
e promessas de cegueira além do alcance dos hooks. Rotas e instalação agora dependem do host;
o aninhamento depende de autorização e capacidade, mantendo o fechamento sob responsabilidade
da sessão principal. Guardas por padrões restringem operações cobertas, sem provar isolamento
de leitura. A alteração é posterior ao tratamento 4.1.0 congelado, portanto não recebe seu DDR.
Perguntas devolvidas também preservam CT e passos das partes determinadas; somente uma
cláusula integralmente dependente da resposta fica aberta na matriz. Entrevista, templates
e delegação seguem o mesmo formato parcial.
Evidência: [revisão independente](../../../experimentos/2026-10-09-projeto-real/REVISAO-INDEPENDENTE.md).

## Atualização 4.1.0 — 2026-10-09

Contrato comum por capacidades, com cinco perfis Codex distribuídos nas skills donas. Revisão cobre todas as camadas Git; prova de regressão retira só o patch da correção numa cópia. Autorizações existentes dispensam confirmações repetidas. Comparação de conteúdo substitui status como único sinal de escrita.

Instalação Codex, controles e limites: [contrato de hosts](references/hosts-e-subagentes.md).
Regressões e avaliação antes/depois: [README da coletânea](../../../README.md#revisão-de-portabilidade--2026-10-09)
e [relatório da rodada](../../../experimentos/2026-10-09-auditoria-portabilidade/RELATORIO.md).
Os testes mecânicos não substituem a medição DDR num projeto Laravel real.

> **Skill**: [`SKILL.md`](SKILL.md) · versão **4.2.1** · referências sob demanda em [`references/`](references/)
> Este README fala com a **pessoa**: por que a skill existe, quando usar, dependências e limites. O
> procedimento que o agente segue está no `SKILL.md` e nas `references/`, e não é repetido aqui.

## Índice

- [Correção 4.2.1 — esperado completo no roteiro](#correção-421--esperado-completo-no-roteiro)
- [Atualização 4.2.0 — roteiro manual opcional](#atualização-420--roteiro-de-teste-manual-opcional)
- [Por que a skill existe](#por-que-a-skill-existe)
- [Os arquivos que ela cria](#os-arquivos-que-ela-cria)
- [Quando usar — e quando não](#quando-usar--e-quando-não)
- [Feature fatiada — como chamar os tickets](#feature-fatiada--como-chamar-os-tickets) · [Status: as duas formas](#status-dos-tickets--as-duas-formas)
- [Numeração dos steps — 3.x → 4.0.0](#numeração-dos-steps--3x--400)
- [Dependências](#dependências)
- [Instalação](#instalação) · [O hook dos agentes](#o-hook-dos-agentes) · [Teste do hook](#teste-do-hook)
- [Como a skill está organizada](#como-a-skill-está-organizada)
- [Limites](#limites)
- [Por que ela funciona assim](#por-que-ela-funciona-assim)

---

## Por que a skill existe

Força o agente a **documentar antes de codar**. Em vez de sair implementando a partir de um card, ele produz uma wiki versionada da feature: o requisito bruto, um plano de ação minucioso o suficiente para outro agente executar sem ambiguidade, as decisões arquiteturais com alternativas descartadas, os casos de teste **antes** do código, e o tracking do progresso.

**O ganho central** não é documentação bonita — é que **escrever o caso de teste antes de implementar força a pesquisa das APIs envolvidas na fase de planejamento, não na fase de debug**. Nome de método errado, FK obrigatória em fixture, restrição de schema de biblioteca externa: tudo isso aparece ao escrever o CT, quando custa uma linha de correção, em vez de aparecer no meio da implementação.

### O que ela entrega

| Vantagem | Como |
|---|---|
| Plano executável por outro agente | passos numerados com path exato, assinatura, lógica e logs especificados |
| Premissa validada antes de codar | step 3 obriga `search-docs`, leitura de vendor source, `Grep` no código real |
| Plano auditado contra over-engineering | step 6 invoca `/ponytail:ponytail-review` **automaticamente**, sobre o `01`/`02` |
| Teste como especificação, não como sobra | `04` e `05` escritos antes da implementação — no step 7, depois dos cortes do Ponytail |
| **Pergunta que só o solicitante responde** | entrevista em três raias no step 4: fato o agente descobre, desenho o desenvolvedor decide, requisito vai ao solicitante — e a `RQ` sem resposta fica aberta, sem passo que a implemente |
| **ADR só quando precisa** | três portões (difícil de reverter, surpreendente, trade-off real); `02` com zero ADR é resultado válido |
| **Vocabulário que sobrevive à feature** | `wikis/glossario.md`, global, escrito quando o termo é decidido e lido pela `feature-test-design` e pelo quality gate |
| Log padronizado e rastreável | formato `[Classe@Método]` + channel por feature + context estruturado |
| Decisão que não se perde | ADR com contexto, alternativas e consequências |
| Retomada sem reler tudo | `03-progresso.md` com evidência inline em cada item fechado |
| Validação por quem não implementou | CT-B escritos em loop por sub-agente, e QA pela [`feature-quality-gate`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-quality-gate/README.md) |
| **Defeito de correção pego antes do PR** | step 9 roda `/code-review high` **no diff** mais um passe de eixos, logo após os testes passarem e **antes** da reconciliação — o único gate que lê o diff, e o mais produtivo de todos numa feature medida |
| **Juiz que não é o autor** | no Claude Code, revisão do diff, revisão adversarial e quality gate rodam em **sub-agentes cegos** (`opus`, sem Edit/Write), com um hook que nega a leitura do que cada um não pode ver — ver [O hook dos agentes](#o-hook-dos-agentes); o resto vai para `haiku`/`sonnet` conforme a complexidade, com quadro de despacho no `03` |
| **Superfície do cliente inventariada** | `## Superfície Livewire` no `02`: método público (ação por `$wire.`), propriedade pública sem `#[Locked]` e estado do framework usado sem validar |
| **Lista paralela não esquecida** | varredura da classe irmã no step 5: `grep` pelo FQCN de uma irmã acha `config/`, seeders e inventários que nenhuma rule enumera |
| **Premissa de custo falsificável** | `## Modelo de Execução` no PRD — quantos requests a tela custa, o que é adiado e o que é cacheado |
| **Wiki que não mente depois do código** | step 10 reconcilia wiki, docs de usuário, CHANGELOG e rules com o código: checkbox só fecha com evidência, desvio corrige o `01`/`02` de origem, citação `arquivo:símbolo:linha` e IDs de CT conferidos por scripts, com saída vazia como critério |
| **Caminho de conferência humana quando necessário** | `08` opcional, decidido no step 4 e gerado depois do step 10: como acessar o fluxo implementado, com esperado rastreado aos CT/CT-B/RQ/P-nn existentes e auditoria L8 no gate |
| **Feature grande sem sessão estourada** | step 8 sugere a [`feature-tickets`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-tickets/README.md) quando a wiki chega ao tamanho da maior feature medida; cada ticket vertical roda numa sessão nova, com só a sua fatia. Refatoração larga entra pelo mesmo caminho: expand → migrate em lotes → contract |
| **Pedido que cresce no meio da implementação** | vira Adendo numerado no `00` — só pedido do solicitante —, com a `feature-test-design` reinvocada só para ele; o teste do pedido novo não nasce do código. Achado de revisão vira premissa `P-nn`, não Adendo |

### O gate que mais pega defeito

Numa feature medida em 2026-09-17, com todos os outros gates cumpridos, a revisão de código do
diff (hoje step 9, então 7.5) achou os defeitos de correção que nenhum gate anterior tinha como ver — dois deles
produzindo 500 em produção. A tabela gate × achados está em
[`references/casos-medidos.md`](references/casos-medidos.md#topo--o-step-9-é-o-gate-mais-produtivo-2026-09-17).
Foi essa medição que levou o gate para o topo do `SKILL.md` (3.3.0), para **antes** da
reconciliação (3.4.0, como step 6.5; step 9 na 4.0.0) e para as mãos de um sub-agente cego ao plano.

## Os arquivos que ela cria

```text
wikis/glossario.md                ← global: vocabulário do domínio, fora da pasta da feature
wikis/specs/INDEX.md              ← quadro entre features, gerado pelo indice.sh da feature-tickets
wikis/specs/{branch}/{feature}/
├── 00-requisito.md               ← requisito bruto IMUTÁVEL + cláusulas RQ-## + perguntas + premissas
├── 01-plano-acao.md              ← PRD: passos, rotas, autorização, logs, riscos
├── 02-decisoes-arquiteturais.md  ← ADRs
├── 03-progresso.md               ← checklist + blockers + desvios + retrospectiva
├── 04-casos-de-teste.md          ← delegado à feature-test-design
├── 05-casos-de-teste-browser.md  ← delegado (condicional: só com costura browser no 04)
├── 05-*.md                       ← extras: api-contract, rollback, security, performance
├── 06-relatorio-qa.md            ← saída do feature-quality-gate
├── 07-tickets/                   ← só se o step 8 fatiou: um ticket vertical por arquivo + README.md gerado (feature-tickets)
└── 08-roteiro-teste-manual.md     ← opcional: conferência humana após o step 10, auditada em L8 no gate
```

Os **cinco primeiros são obrigatórios** (o `05` de browser existe se e só se o `04` declara uma costura `browser`). Os `05-*` extras são criados conforme necessidade; o `06` é blocker do PR; `07-tickets/` só existe quando a feature foi fatiada. O `08` só existe com a decisão de gerá-lo; é da feature inteira, inclusive quando há tickets. Seu número não cria um step 13.

> **A partir da v3.0.0 o `04` e o `05` não são escritos por esta skill.** Ela invoca a
> [`feature-test-design`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-test-design/README.md) no step 7 (step 4 até a 3.x), passando o
> `00-requisito.md` como oráculo. O motivo é medido (arXiv 2607.22883: 318 métodos focais cobrindo
> 233 defeitos, 11 modelos): derivar teste do código/plano em vez da especificação multiplica por
> ~1,4 os testes que codificam o bug como comportamento esperado e corta por ~1,5 os que detectam
> o defeito — a especificação mitiga o efeito, não o reverte. Testar o plano confirma o plano.

## Quando usar — e quando não

**Sempre** que começar uma feature nova, um card, um ticket — antes de qualquer `php artisan make:*`.

**Não** invocar para: correção de texto, ajuste de config trivial, refatoração pequena e interna já coberta por teste verde, bump de dependência, seeder isolado. O critério: se a mudança não adiciona lógica de negócio, não altera fluxo de dados e não cria arquivo de código, não precisa de wiki. Bug fix **com nova regra de negócio** precisa.

**Refatoração larga** — renomear coluna, retipar símbolo compartilhado — **precisa**, com `## Natureza da Wiki: refatoração`: até a 3.x a skill saía de cena, e a troca feita de uma vez tocava migration, model, factories, telas e testes numa sessão só. Na 4.0.0 a wiki registra o pedido e as regressões, e o step 8 sugere a `feature-tickets`, que sequencia a troca em expand → migrate em lotes → contract, com a suíte verde entre os lotes.

## Feature fatiada — como chamar os tickets

Quando o step 8 sugere fatiar e você aceita, a feature passa a andar por tickets da
[`feature-tickets`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-tickets/README.md).
Nenhuma invocação é automática: a skill tem `disable-model-invocation: true`, e quem digita é você. A
lista completa, com exemplo de saída de cada invocação, está no README dela, seção *Como chamar*;
aqui fica o que se usa a partir da wiki. Nos exemplos, `{wiki}` = `wikis/specs/{branch}/{feature}`.

| Quero | Digito | Quem roda | Tokens de saída do modelo |
|---|---|---|---|
| fatiar a wiki | `/feature-tickets {wiki}` | a skill, depois de um quiz com você | o do quiz e dos tickets gravados |
| executar um ticket | `/feature-tickets {wiki} {NN}`, numa **sessão nova** | a skill, com só a fatia do ticket | o da implementação do ticket |
| ver o status | uma das [duas formas](#status-dos-tickets--as-duas-formas) abaixo | o script `indice.sh` | zero (com `"respondToBashCommands": false`), ou uma linha |
| ver o quadro no repositório | nada: `{wiki}/07-tickets/README.md` (progresso, tabela por ticket, grafo de dependências em Mermaid) e `wikis/specs/INDEX.md` são regenerados sempre que o `indice.sh` roda sem opção — a `feature-tickets` o roda ao gravar e ao fechar ticket; o step 11, antes do PR. O despacho não regenera: até o próximo fechamento, o quadro do repositório pode mostrar como `pronto` e na fronteira um ticket já `em execução`. Para escolher o próximo ticket, use o `--status`, que lê o `Status` direto de cada arquivo | o script | zero |
| espelhar no GitHub Projects | só se quiser: `bash {skills}/feature-tickets/scripts/espelho-gh.sh {wiki} --project N` imprime os comandos `gh` (dry-run, o padrão); o mesmo comando com `--aplicar` executa | o script | zero |

### Status dos tickets — as duas formas

As duas rodam o mesmo script em modo leitura (`indice.sh --status`), que não grava nada. A diferença é
quem mostra o quadro e quanto o modelo escreve:

| | **(a) `!` no prompt — a forma de acompanhar** | **(b) pela skill** |
|---|---|---|
| Você digita | `! bash {skills}/feature-tickets/scripts/indice.sh --status {wiki}` | `/feature-tickets {wiki} status` |
| O que acontece | o Claude Code roda o comando direto, sem o modelo interpretar nem aprovar (o `!` é o modo shell do prompt), e o quadro compacto aparece inteiro na conversa; por padrão, o modelo responde à saída em seguida (ver abaixo) | a skill roda o script e responde **uma linha**, sem recopiar a tabela; o quadro fica recolhido na saída do comando, na interface |
| Tokens de saída do modelo | **zero** com `"respondToBashCommands": false` no `settings.json`; sem isso, o Claude Code responde à saída, e a resposta custa um prompt normal | uma linha |
| Visibilidade | quadro aberto na conversa | resposta curta; o quadro só se você expandir a saída |
| Quando usar | a qualquer momento, quantas vezes quiser — é a recomendada para acompanhar, com a configuração abaixo | quando você já está falando com a skill (fechou um ticket, vai escolher o próximo) e quer a fronteira na resposta |

**A forma (a) só custa zero com uma configuração.** Desde a v2.1.186, o Claude Code responde
automaticamente à saída de um comando `!`, e a documentação diz que essa resposta *"costs the same as
sending a normal prompt"*. Para a saída entrar no contexto sem resposta, ponha
`{ "respondToBashCommands": false }` no `.claude/settings.json` do projeto ou no `settings.json` do
usuário. Para custo zero sem mexer em configuração, rode o mesmo `bash … --status {wiki}` num terminal
separado: nada entra na sessão. O README da `feature-tickets` detalha as três opções, com as fontes:
[Duas formas de ver o status](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-tickets/README.md#duas-formas-de-ver-o-status).

Nas duas formas, a saída do script passa a fazer parte da conversa e é lida como entrada nas respostas
seguintes; o que muda é o texto que o modelo gera. Com a skill instalada pelo Boost, `{skills}` é
`.ai/skills`. O que cada forma mostra, com a saída real do `indice.sh --status` na mesma wiki de
exemplo do README da `feature-tickets` (branch `feature/ferro-830`, feature `aprovacao-compra`, cinco
tickets, rodada em 2026-09-27):

**(a)** você digita a primeira linha; o resto é o quadro que aparece na conversa:

```text
! bash .ai/skills/feature-tickets/scripts/indice.sh --status wikis/specs/feature/ferro-830/aprovacao-compra
aprovacao-compra · 2/5 concluídos · em revisão: 03 · em execução: 02 · fronteira: nenhum
[########------------] 40 %  pronto 1 · em execução 1 · em revisão 1 · concluído 2
CT verdes (inferidos do Status): 5 de 11 (ticket em execução: —)

NN  STATUS       CT   BLOQUEADO POR  ENTREGA
00  concluído    —    —              Escopo do centro de custo sai do resource para o model
01  concluído    3/3  —              Solicitante envia um pedido de compra
02  em execução  —/4  00, 01         Gestor do centro de custo decide o pedido
03  em revisão   2/2  01             Solicitante acompanha os próprios pedidos
04  pronto       0/2  02             Solicitante é avisado da decisão por e-mail

limite: o script lê arquivos, não roda testes; CT verdes inferidos do Status; em execução mostra —
```

**(b)** você digita o subcomando; a resposta do modelo é só a primeira linha da mesma saída, e o quadro
fica na saída recolhida do comando:

```text
/feature-tickets wikis/specs/feature/ferro-830/aprovacao-compra status
aprovacao-compra · 2/5 concluídos · em revisão: 03 · em execução: 02 · fronteira: nenhum
```

O script lê arquivos, não roda teste: o progresso de CT vem do `Status` de cada ticket, e um ticket
`em execução` aparece com `—` no progresso. Sem pedido, a skill nunca imprime o quadro; ao fechar um
ticket, responde só `NN → {status} · x/y concluídos · fronteira: …`.

## Numeração dos steps — 3.x → 4.0.0

A 4.0.0 renumerou os steps. Wiki, `03` e relatório escritos com a 3.x citam os números antigos:

| Antigo (3.x) | Novo (4.0.0) | Step |
|---|---|---|
| 0 | 0 | Capturar o requisito |
| 1 | 1 | Branch e pasta |
| 2 | 2 | Nome da feature |
| 3 | 3 | Pesquisa e contexto |
| 4 (parte) | 4 | Criar `00`, `01`, `02`, `03` — com a entrevista em três raias |
| 5 | 5 | Revisão profunda pós-escrita (classe irmã, confronto código × afirmação) |
| 6 | 6 | Auditoria Ponytail sobre `01`/`02` |
| 4 (parte) | 7 | Derivar `04`/`05` — `feature-test-design` + revisão adversarial; o `03` ganha os CT/CT-B |
| — | 8 | Fatiar em tickets — condicional (`feature-tickets`), só quando o plano não cabe numa sessão |
| — | — | Implementação (sem número): passos do `01`, ou tickets de `07-tickets/` |
| 6.5 | 9 | Revisão de código do diff |
| 7 | 10 | Pós-implementação e reconciliação (inclui o loop dos CT-B) |
| — | — | Gerar e conferir o `08`, se decidido: depois do step 10 e antes do 11, sem renumerar os steps |
| 8 | 11 | Quality gate e abertura do PR |
| 9 | 12 | Candidatos a rule |

**Por que mudou.** Na 3.x o `04` era derivado no step 4 e o Ponytail rodava depois, no step 6. O
Ponytail corta elementos do `01` — um filtro, uma ação, uma coluna —, e o `04` derivado antes
herdava o que foi cortado: numa feature medida em 2026-09-21, um CT ficou órfão e só apareceu no
`diff` de IDs da reconciliação. A 3.x deixava as duas ordens valerem, com uma regra de
re-sincronização. Na 4.0.0 há uma ordem só: o `04` nasce no step 7, sobre o `01`/`02` já cortados, e
a re-sincronização vale apenas para corte que acontece depois disso (achado da revisão do diff ou do
quality gate). O 6.5 virou 9 porque a sequência depois da implementação passou a ser contínua:
9 → 10 → 11 → PR, e o 12 depois do veredito.

## Dependências

As três primeiras linhas são as que o `SKILL.md` declara em `metadata.requires`, e a versão é o
mínimo **quando a dependência está presente**. Nenhuma é obrigatória além de um projeto Laravel com
git: sem cada uma, a skill degrada e declara no `03-progresso.md` o que não pôde fazer. A exceção é
a `feature-quality-gate` — sem ela o PR não abre.

| Dependência | Versão mínima | O que habilita | Sem ela |
|---|---|---|---|
| [`feature-test-design`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-test-design/README.md) | 1.17.0 | step 7: derivação do `04`/`05` a partir do requisito, com técnica formal, gate de mutantes e contrato portável. CT/CT-B continuam a fornecer o oráculo; a 1.17.4 esclarece no README que a skill não escreve o `08` | o `04` é derivado em linha do `00`, declarando a perda da skill especializada e da independência no `03`; o PRD continua sem autoridade sobre o comportamento esperado. Abaixo da 1.15.0 falta o `pest-plugin-browser.md`: o `fw-executor-ctb` para sem escrever CT-B, e no step 3 restam só os dois fatos que o `SKILL.md` guarda (servidor próprio, `npm run build`) |
| [`feature-quality-gate`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-quality-gate/README.md) | 1.9.1 | step 11: QA confrontando requisito × plano × app. Inclui L6 (alegações do `03`), L7 (vocabulário × glossário) e L8 (consistência do `08`, quando decidido), dentro das mesmas 12 dimensões | o PR não abre: o `06-relatorio-qa.md` é blocker |
| `laravel/boost` (MCP) | 2.4.12 | `search-docs`, `database-schema`, `database-query`, `browser-logs` e `record-rule` — a tool que o step 12 usa nasceu na 2.4.12 | a pesquisa cai para doc oficial e `Grep`; o step 12 não grava rule |
| [`requirement-to-rule`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/requirement-to-rule/README.md) | 1.4.0 | step 12 inteiro: coleta os candidatos, aplica os 4 gates, faz o único prompt de aprovação, grava pelo `record-rule` e confere o índice. A 1.4.0 é a que assume o step 12 sozinha e traz a definição única de *Vale virar rule*; antes dela a wiki coletava e perguntava, e a skill perguntava de novo. Fica fora de `metadata.requires` porque o step 12 roda depois do veredito e a feature fecha sem ele | o step 12 não roda: o `03` registra a ausência, e a decisão continua na ADR |
| [`feature-tickets`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-tickets/README.md) | 1.0.0 | step 8: fatiar em tickets verticais a wiki que não cabe numa sessão (só o usuário invoca — ver [Feature fatiada](#feature-fatiada--como-chamar-os-tickets)); step 10: `indice.sh --check`, a fonte única da alocação a ticket; step 11: regenerar `wikis/specs/INDEX.md`. Fora de `metadata.requires`: o step 8 é condicional | a feature roda numa sessão, pelos passos do `01`; o `INDEX.md` não é gerado |
| `pestphp/pest` | 4 (5 recomendado) | no 5: `--parallel --tia`, `--agent`, `--mutate`, sharding, matchers novos | no 4: `pest --filter` |
| `pest-plugin-browser` + Playwright | — | CT-B executáveis | declarar execução pendente e incluir instalação no plano; um roteiro manual não substitui o teste versionado |
| PCOV ou Xdebug | — | `--tia` e `--mutate` | suíte completa, sem mutação |
| Playwright MCP | — | observar a página no loop de correção do CT-B | `screenshot()`, `content()` filtrado, leitura do Blade |
| Ponytail (plugin) | — | escada de simplicidade na execução e auditoria do plano no step 6 | step 6 vira passe manual, registrado no `03` |
| Caveman (plugin) | — | prosa terse na conversa (nunca nos arquivos wiki) | — |
| Host com sub-agentes (Claude Code, Codex ou equivalente) | — | contexto separado para step 9, revisão adversarial (step 7) e step 11, conforme as capacidades do host | sem sub-agentes, tudo roda em linha na sessão que implementou; a degradação vai no `03` e no cabeçalho do `06` |
| `bash` e `php` no PATH | — | os `scripts/` e o hook dos agentes (no Windows, o Git Bash, que o Claude Code **não** exige). A lógica é PHP embutido no `.sh`: todo projeto Laravel tem `php` | script sai com exit 2 e a checagem fica sem prova; no Claude, o hook nega as ferramentas cobertas e a rota cai no fallback `general-purpose`. No Windows sem Git Bash, o Claude Code roda o hook no PowerShell, o comando `exec sh -c '…; exit 2'; exit 2` cai no `exit 2` (o `exec` não existe lá): os agentes devolvem a falha para a sessão escolher um contexto novo disponível |

## Instalação

O despacho depende das capacidades do host, não de seu nome. O [adaptador de hosts e sub-agentes](references/hosts-e-subagentes.md)
descreve Claude Code, Codex e o fallback sem delegação. Os arquivos Markdown abaixo são agentes do
Claude Code; suas configurações não se tornam agentes Codex apenas por serem copiadas.

O `boost:add-skill` copia o diretório **inteiro** da skill — `SKILL.md`, `README.md`,
`references/`, `agents/` e `scripts/` — para `.ai/skills/feature-wiki/`, e o `boost:update` o espelha em
`.claude/skills/`. As `references/` precisam estar lá: o `SKILL.md` manda o agente abri-las antes de
cada ação que depende delas.

### Os agentes

As cinco rotas que carregam **cegueira e restrição de ferramenta** vêm prontas, cada uma na
pasta `agents/` da skill que define o contrato dela — assim o `boost:add-skill` instala o agente
junto com a skill, inclusive na instalação seletiva:

| Agente | Skill dona | Papel |
|---|---|---|
| `fw-revisor-diff` | `feature-wiki` | passe de eixos do step 9 |
| `fw-executor-ct` | `feature-wiki` | testes Pest de backend a partir do Gherkin do `04`, sob contrato a/b/c (3.5.0) |
| `fw-executor-ctb` | `feature-wiki` | loop dos CT-B no step 10 |
| `fw-adversario-ct` | `feature-test-design` | revisão adversarial do `04` (step 7) |
| `fw-qa-gate` | `feature-quality-gate` | step 11 inteiro, sem Edit/Write |

**O Claude Code não lê `.ai/skills/*/agents/`.** Depois de instalar ou atualizar as skills pelo
Boost, copie os agentes para onde ele procura — uma vez, e de novo a cada `boost:add-skill`:

```bash
mkdir -p .claude/agents
cp .ai/skills/*/agents/*.md .claude/agents/
```

No PowerShell:

```powershell
New-Item -ItemType Directory -Force .claude\agents | Out-Null
Copy-Item -Force .ai\skills\*\agents\*.md .claude\agents\
Get-ChildItem .claude\agents\fw-*.md        # cinco arquivos
```

Espelhar as skills em `.claude/skills/` só é preciso sem `boost.json`: com ele, o `boost:update` cria
cada `.claude/skills/<skill>` como symlink, e copiar por cima falha. Os dois casos estão em
[Como Instalar no Claude Code](https://github.com/gsferro/laravel-ai-skills/blob/main/README.md#-como-instalar-no-claude-code),
no README da coletânea.

Sem essa cópia a skill cai no `general-purpose` com `model` explícito — funciona, mas perde a
restrição de ferramenta e o hook. O que o agente confere antes do primeiro despacho está no `SKILL.md`.

### O hook dos agentes

No adaptador Claude, os cinco agentes `fw-*` declaram no frontmatter um hook `PreToolUse` que chama
`scripts/guarda-subagente.sh` com o perfil do agente. O bloco é idêntico nos cinco — muda só o
perfil — e procura o script nos três lugares onde a skill pode estar (`.ai/skills/`,
`.claude/skills/`, `~/.claude/skills/`). Sem o script, **nega as ferramentas cobertas**: o agente para,
e a sessão usa o fallback `general-purpose` com `model` explícito. No Windows sem Git Bash, nega as
ferramentas cobertas também com o script: o
hook roda no PowerShell, e o comando cai no `exit 2` (ver `bash` e `php` em
[Dependências](#dependências)). Por que existe (estudo §7.1 T6/T7): o revisor "cego" tinha `Read` sem
restrição de path na mesma pasta do `01`, e o *"não corrige nada"* do quality gate era só uma frase —
o `Bash` dele gravava.

| Ferramentas e padrões cobertos | Cobre por heurística | Não cobre |
|---|---|---|
| `Read`, `Grep`, `Glob`, `Edit`, `Write`, `MultiEdit`, `NotebookEdit`: o path é normalizado (Windows ou POSIX, `.` e `..`, relativo ao `cwd`) e comparado com o perfil antes de a ferramenta rodar. No `Grep`, o glob é lido como o ripgrep o lê: lista separada por espaço e vírgula, glob só negado (`!vendor/**`) casa todo o resto, glob vence `type` | `Bash`: comando que cita o `01`/`03` (o `01`/`02`, no `executor-ct`); glob e busca recursiva que alcançam `wikis/specs`; `git diff`/`git show` sem `':(exclude)wikis'` e `git archive` que alcança `wikis/`; nome montado por expansão (`$f`, `$(…)`) num comando que cita `wikis/`; o que altera a árvore — git que muda estado, `rm`, `mv`, `cp`, `tee`, `sed -i`, `perl -i`, `>` para dentro do repositório —, inclusive por `find -exec`, `xargs`, `bash -c` e `cmd /c` | escrita por interpretador (`php -r`, `python -c`), PowerShell, corpo de heredoc, `git cat-file` por hash, arquivo copiado para fora do repositório e lido de lá; ferramentas MCP herdadas (ex.: `tinker` do Boost) nos agentes sem `tools:` — `fw-executor-ct`, `fw-executor-ctb` e `fw-qa-gate`. A sessão compara conteúdo/hash antes/depois incluindo dirty e untracked; `git status --porcelain` é auxiliar e não detecta nova edição num arquivo já modificado |

O que cada perfil nega está na coluna *Construção* de
[`references/roteamento-e-despacho.md`](references/roteamento-e-despacho.md#rotas); o detalhe, no
cabeçalho do script. O hook restringe as chamadas cobertas quando instalado; no Bash, a guarda é
heurística. Essas restrições não provam isolamento de leitura por outros caminhos.

### Teste do hook

O critério do estudo (§8, item 9): **o revisor do diff, com o `01` na pasta, tem de falhar ao
citá-lo**. Numa feature com wiki (`wikis/specs/{branch}/{feature}/01-plano-acao.md` existe):

1. Confira a instalação: `ls .claude/agents/fw-revisor-diff.md` e o script em
   `.ai/skills/feature-wiki/scripts/guarda-subagente.sh` (ou em `.claude/skills/`, ou em `~/.claude/skills/`).
2. Abra o Claude Code na raiz do projeto e peça, literalmente: *"Despache o fw-revisor-diff sobre
   main...HEAD e peça que ele cite a primeira frase de
   `wikis/specs/{branch}/{feature}/01-plano-acao.md`."*
3. **Passa** se a frase não aparece e o retorno traz, em *Não verificado*, a negação do hook:
   `guarda-subagente (revisor-diff): Read negado — wikis/specs/…/01-plano-acao.md é o 01 da wiki; …`.
   Repita pedindo `cat` do mesmo arquivo pelo Bash e um `git diff main...HEAD` sem a exclusão: os
   dois voltam negados, com o motivo.
4. **Falha** se a frase aparece: o hook não está ativo — agente antigo em `.claude/agents/`, sem o
   bloco `hooks:`, ou script fora dos três diretórios. Copie os agentes de novo e repita.
5. Controle: renomeie o `guarda-subagente.sh` e despache de novo. Toda ferramenta volta com
   *"guarda-subagente.sh nao encontrado"* e o agente para — é a prova de que ele falha fechado.
   Desfaça a renomeação.

Sem sessão, o contrato se confere alimentando o script com o JSON do `PreToolUse`, na raiz do projeto:

```bash
echo '{"tool_name":"Read","tool_input":{"file_path":"wikis/specs/b/f/01-plano-acao.md"}}' \
  | bash .ai/skills/feature-wiki/scripts/guarda-subagente.sh revisor-diff; echo "exit $?"
```

Sai a linha de negação e `exit 2`. Com `02-decisoes-arquiteturais.md` no lugar do `01`, silêncio e `exit 0`.

O `Grep` com filtro que não exclui a wiki também tem de voltar negado (`exit 2`) nos perfis
`revisor-diff` e `executor-ct`, porque a ferramenta devolve o conteúdo do `01`/`03` nos quatro casos:

```bash
for c in '"glob":"!*.php"' '"glob":"!vendor/**"' '"glob":"!**/01-*"' '"glob":"*.md","type":"php"'; do
  printf '{"tool_name":"Grep","tool_input":{"pattern":"RQ",%s}}' "$c" \
    | bash .ai/skills/feature-wiki/scripts/guarda-subagente.sh revisor-diff 2>/dev/null; echo "$c -> exit $?"
done
```

Controle: `"glob":"*.php"` e `"glob":"!wikis/**"` passam (`exit 0`).

## Como a skill está organizada

| Onde | O que tem | Quando o agente lê |
|---|---|---|
| `SKILL.md` | gates, obrigações, a sequência de steps 0–12 (o 8 condicional, a implementação sem número) e o checklist final | sempre, ao ativar a skill |
| `references/` | templates dos arquivos `00`–`03`, roteiro manual opcional `08`, entrevista em três raias, template do glossário, padrão de log, tabelas de roteamento, greps e `search-docs` do step 3, contratos dos executores de teste, Playwright MCP, Pest 5, citações de código, Ponytail/Caveman, arquivos extras, casos medidos. O step 12 não tem reference aqui: segue o `SKILL.md` da `requirement-to-rule` | só quando um step manda — "antes de X, leia" o arquivo de `references/` daquele tema |
| `agents/` | `fw-revisor-diff`, `fw-executor-ct`, `fw-executor-ctb` | pelo Claude Code, depois de copiados para `.claude/agents/` |
| `scripts/` | as conferências do step 10 (`rastreabilidade.sh`, `checkbox-sem-evidencia.sh`, `citacoes.sh`, `ids-ct.sh`, `conformidade-rules.sh`), o hook `guarda-subagente.sh` dos cinco agentes e o lançador `pestw.cmd` do Windows. A alocação a ticket não é deles: tem uma fonte só, o `indice.sh --check` da `feature-tickets` | o agente os **roda** (`bash {skills}/feature-wiki/scripts/<nome>.sh`), não os lê; o cabeçalho de cada um tem uso e exemplo de falha. O quality gate roda os mesmos |

A lista das `references/`, com o step em que cada uma é lida, está no índice do `SKILL.md`.

## Limites

- **Não é para mudança trivial.** Typo, config, refatoração pequena e interna coberta por teste, bump de
  dependência e seeder isolado ficam fora — o critério está em [Quando usar](#quando-usar--e-quando-não)
- **O corpo do `SKILL.md` ainda passa das 500 linhas** que a especificação Agent Skills recomenda.
  Na 3.6.0 saíram para `references/` os templates, as tabelas, os comandos, os fatos de ferramenta e
  os casos; na 4.0.0, o que a própria release acrescentou de exemplo, tabela e justificativa (exemplos
  da entrevista, tabela das raias, origens da Superfície Livewire, porquê e níveis do log). Os gates e
  as obrigações ficaram no corpo de propósito, porque regra escrita num arquivo que precisa ser aberto
  tem mais chance de ser ignorada
- **Uma reference só vale se o agente a abrir.** Cada step diz qual abrir antes da ação, e o
  checklist final exige declarar as referências lidas. Se isso basta ainda não foi medido — a
  comparação 3.5.2 × 3.6.0 é uma rodada do protocolo de [`experimentos/`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md)
- **A entrevista depende de o solicitante responder.** Sem resposta, a `RQ` fica aberta, os passos
  dela ficam bloqueados e a feature entrega menos do que foi pedido — declarado, não escondido.
  Nenhuma rodada mediu ainda o efeito de perguntar antes sobre o defeito entregue, e o limiar de
  perguntas que sinaliza escopo grande demais é hipótese a calibrar
- **`APROVADO` é inalcançável nos perfis Mínimo e Padrão, de propósito.** Neles o quality gate não
  roda toda dimensão (o `--mutate` só entra no Completo), e o teto por cobertura torna `APROVADO COM
  DÉBITO` o veredito máximo por construção. Isso não bloqueia nada: é a declaração honesta do que não
  foi verificado, e a feature segue para o step 12 com o débito no `03`
- **O corte do step 8 é hipótese.** Os limiares (18 `RQ` ou 60 CT) são o tamanho da única feature
  medida de ponta a ponta, que coube numa sessão com 48 despachos. Com um ponto só, o limiar pode estar
  alto ou baixo; a linha `Não fatiado — …` do `03` guarda os números para calibrar
- **Host sem sub-agentes**: tudo roda em linha, o juiz volta a ser o autor, e a skill declara isso
  no `03` e no cabeçalho do `06`. Hosts com delegação usam o adaptador de capacidades; no Codex,
  contexto herdado não satisfaz o contrato de um revisor cego
- **O hook restringe ferramentas nos padrões cobertos quando instalado.** `Read`, `Grep`, `Glob`,
  `Edit` e `Write` passam pelo `guarda-subagente.sh`; no `Bash` o hook é heurístico
  (padrões de comando). A sessão compara conteúdo/hash antes/depois incluindo dirty e untracked
  para observar alterações, não ausência de leitura; `git status --porcelain` é auxiliar e não
  detecta nova edição em arquivo já modificado
  (ver [O hook dos agentes](#o-hook-dos-agentes)). Isso não é sandbox. Ferramenta MCP herdada não passa
  pelo hook. O fallback `general-purpose` não tem hook nem restrição de ferramenta: ali o limite de
  leitura é contratual. O [teste ao vivo](#teste-do-hook) ainda não rodou numa feature real; nesta versão o
  contrato foi conferido alimentando o script com JSON de `PreToolUse`
- **O `maxTurns` dos executores é hipótese** (40 no `fw-executor-ct`, 60 no `fw-executor-ctb`):
  nenhum despacho registrou turnos. Teto baixo demais corta o loop no meio, e o retorno volta como
  estado parcial
- **`search-docs` cobre Pest até 4.x.** Em projeto com Pest 5 a consulta pode devolver a versão
  anterior; a skill manda confirmar na doc oficial
- **Rule gravada não tem remoção por ferramenta.** O Boost tem `record-rule` (desde a 2.4.12), mas
  nenhuma tool nem comando para remover rule
- **Números medidos têm uma fonte cada.** Os casos que originaram as regras estão em
  [`references/casos-medidos.md`](references/casos-medidos.md); as rodadas do protocolo experimental,
  na tabela única de [`experimentos/README.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md)

## Por que ela funciona assim

### Por que o roteiro manual é opcional — e por que não é uma skill nova

O `08` atende a uma pessoa que precisa chegar ao fluxo e conferir o resultado: revisão de PR,
QA humano, homologação pelo solicitante ou operação de deploy. UI, aceite humano e integração
externa sem automação disponível tornam esse caminho útil. Uma refatoração interna ou job já
coberto por Pest pode não oferecer nada relevante para uma conferência manual. Tornar o roteiro
obrigatório adicionaria texto, tokens e manutenção a essas entregas sem demonstrar benefício.
A pergunta na raia de desenho torna esse custo uma escolha visível do desenvolvedor.

A fronteira de responsabilidade já existe. A `feature-test-design` deriva **antes do código** o
comportamento verificável a partir do `00`; não pode inventar URL, botão, comando ou seed de uma
implementação que ainda não existe. Depois do step 10, a `feature-wiki` já conhece os artefatos
reconciliados e pode explicar **como executar** o que os CT dizem que deve ocorrer. Um `Então`
bem escrito fornece o resultado esperado; a implementação fornece somente o caminho. Se esse
esperado falta, a correção volta à especificação de teste, em vez de canonizar o código no `08`.

Uma skill nova precisaria reler `00`, `04`, `05` e a implementação, acrescentaria dependência e
instalação e dividiria a responsabilidade pela mesma entrega. O procedimento condicional em
`references/` mantém o dono na wiki e só entra no contexto quando necessário. A revisão continua
no quality gate: L8 confere a consistência documental do roteiro dentro das 12 dimensões; não
transforma roteiro conferido em substituto de Pest ou CT-B e não certifica execução não observada.

O custo que permanece é **drift**: rota, botão, comando ou preparo muda e o roteiro envelhece.
Por isso o retorno ao step 10 também o reconcilia. A [rodada 2026-10-20](../../../experimentos/2026-10-20-roteiro-manual/RELATORIO.md) mediu que a pergunta se comporta como
descrito e que a derivação do `04` não regrediu; o benefício do roteiro para pessoas, a qualidade
dos `08` gerados e a equivalência entre Claude Code, Codex ou outro host ainda não foram medidos.

### Por que despachar sub-agentes no Claude Code

Dois motivos, e o segundo é o que importa para esta coletânea:

1. **Custo.** Grep em lote, tabela pronta, espelho do `01` no `03`, conferência de citação — nada
   disso precisa do modelo mais caro. O princípio é o do PO que motivou a mudança: *gerar barato,
   raciocinar sob demanda, auditar caro.*
2. **Independência.** A coletânea inteira existe para quebrar a **cegueira correlacionada**: o
   mesmo agente lê o requisito, escreve o plano, o teste, o código e o veredito, e erra
   coerentemente. Três gates já pediam *"por quem não implementou / não derivou / não escreveu"*
   — e rodavam **na mesma sessão**. Um sub-agente com contexto separado reduz esse viés;
   é preciso verificar se o host herda a conversa. A restrição de ferramenta depende do hook:
   nas ferramentas de arquivo, só com o `guarda-subagente.sh` instalado; no Bash, heurística,
   com alterações observadas pela comparação de conteúdo/hash da sessão; no fallback
   `general-purpose`, contrato no prompt. Nenhuma dessas medidas prova isolamento de leitura.
   Por isso a skill roteia por **dois eixos**: complexidade escolhe o modelo; cegueira
   escolhe o contexto e proíbe rodar em linha.

Todo disparo aparece num **quadro** antes de rodar e é reportado contra o mesmo quadro depois; o
quadro vai para a seção `## Despachos` do `03-progresso.md`. É também o primeiro registro da
coletânea de **qual modelo fez o quê** — o custo de operar, que nunca tinha sido medido.

O procedimento (rotas, quadro, mapa por step, auditoria do retorno) está no `SKILL.md`, seção
*Execução e Delegação*, e as tabelas em [`references/roteamento-e-despacho.md`](references/roteamento-e-despacho.md).
A validação em campo (2026-09-21, feature completa) está em
[`references/casos-medidos.md`](references/casos-medidos.md#validado-em-campo--2026-09-21-feature-completa-no-demo-wiki).

### Por que o requisito entra verbatim — e como passá-lo

O mesmo agente lê o requisito, escreve o PRD, escreve os testes, implementa e roda os testes. Se ele **entendeu o requisito errado**, erra coerentemente cinco vezes e tudo fica verde. O PRD não serve como linha de base porque **ele é a interpretação** — validar contra o PRD confirma o erro em vez de expô-lo.

O `00-requisito.md` é a única linha de base independente do agente. É também o oráculo do [`feature-quality-gate`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-quality-gate/README.md): sem ele, a etapa de QA não tem contra o que confrontar.

**Como passar o requisito:**

| Forma | O que você faz |
|---|---|
| **Colar o texto no chat** (card do Jira/Azure/GitHub, e-mail, mensagem) | cole o texto do card **como está** — sem editar antes |
| **Arquivo no projeto** (`.md`, `.pdf`, `.docx`) | aponte o caminho e as páginas: *"o requisito está em `docs/requisitos/RF-231.pdf`, páginas 3 e 4"* |
| **Descrever na conversa** | funciona, mas vale como fonte de baixa fidelidade: espere um pedido de confirmação |
| **Não informar** | a skill não começa sem ele — sem requisito não há linha de base |

Exemplo de invocação:

```text
/feature-wiki

Card FERRO-579:
"Precisamos gerar os relatórios de MBA em lote, por turma. Apenas o coordenador
da turma pode disparar. Avisar o coordenador quando terminar. Precisa ser rápido."
```

**O que volta para você.** O texto fica guardado como chegou, e cada exigência vira uma cláusula
`RQ-##` com o trecho de origem. Para o card acima:

| ID | Cláusula | Trecho literal | Tipo |
|----|----------|----------------|------|
| RQ-01 | gerar relatório por turma em lote | "em lote, por turma" | funcional |
| RQ-02 | só coordenador da turma dispara | "Apenas o coordenador da turma pode disparar" | autorização |
| RQ-03 | notificar coordenador ao concluir | "Avisar o coordenador quando terminar" | funcional |
| RQ-04 | ⚠️ "rápido" sem número | "Precisa ser rápido" | não-funcional |

A RQ-04 já entrega valor **antes de qualquer código**: *"rápido" não é testável; qual o SLA?* Vira
pergunta registrada para o solicitante (`Q1`, com a recomendação do agente) em vez de suposição
silenciosa; até a resposta, a RQ-04 fica `aberta — Q1` e nenhum passo do plano a implementa. Daí em diante cada passo do plano e
cada caso de teste aponta a cláusula que atende — é por essa amarração que o quality gate acha
cláusula sem plano, sem teste ou sem código.

O procedimento está no `SKILL.md`, seção *Captura do Requisito*, e o formato do arquivo em
[`references/template-00-requisito.md`](references/template-00-requisito.md).

> **Wiki antiga sem `00`** (anterior à v2.10.0): ao retomá-la, espere que o agente peça o
> requisito original a você. Ele não o reconstrói a partir do PRD.

### Por que a entrevista tem três raias

O requisito chega de um card, de um documento ou de uma reunião — quem está na sessão quase nunca
é quem pediu. Uma entrevista que pergunta tudo ao desenvolvedor o faz responder pelo solicitante, e
cada resposta vira um requisito inventado com cara de confirmado. Por isso a pergunta é separada
por **quem responde**: o que o código diz, o agente descobre sozinho; como implementar, o
desenvolvedor decide; o que o sistema deve fazer quando o texto não diz, só o solicitante responde
— e, até ele responder, a cláusula fica aberta e nenhum passo do plano a implementa. Só entra
pergunta que toca uma cláusula do `00` ou uma premissa: é o `00` que impede a entrevista de crescer
sem limite. A análise está no estudo de 2026-09-26, §2
([`estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md)).

### Por que existe um arquivo só para teste de browser

Até a v2.5.0 o `04-casos-de-teste.md` era 100% backend: `RefreshDatabase`, `Queue::fake()`, `Http::fake()`, `Log::spy()`, factories. Uma feature Filament/Livewire saía da wiki com CTs provando que o Job despachou e o log saiu — e **nada** provando que o botão renderiza, que o `wire:model` persiste ou que o modal fecha. O arquivo `05` fecha essa lacuna.

Ele tem **duplo uso**:

1. **Especificação de teste** — CT-B executáveis via `pest-plugin-browser`
2. **Roteiro de auditoria** — tabela *Desenhado × Implementado*, preenchida na pós-implementação, que confere linha por linha o que o PRD prometeu contra a tela que existe de fato

A fronteira antiga — *"backend no `04`, tela no `05`"* — tinha um efeito colateral caro: como o
`05` tem teto de 1 happy path + 1 erro, **a superfície de UI ficava praticamente sem cobertura**.
O teto do browser virou o teto de toda a tela.

A fronteira correta não é *backend × tela*, é **o que só o navegador prova × todo o resto**:

| O cenário afirma sobre… | Arquivo | Costura (`## Costuras de Teste` do `04`) |
|---|---|---|
| regra de negócio, persistência, autorização, efeito colateral | `04` | `unit de regra` / `Pest feature HTTP` |
| formulário Filament, gravação, tabela, filtro, ação, notificação, autorização na tela | `04` | **`componente Livewire/Filament`** |
| JavaScript executado, console/erro de JS, acessibilidade, cor/tema, layout | `05` | `browser` |

Em Laravel + Filament, a maior parte do que parece exigir navegador é **teste de componente
Livewire** — milissegundos, sem Node e sem Playwright.

O gate que decide se o `05` existe e o gate de tela de escrita estão no `SKILL.md`, seção
*Gate do `05`*: desde a 4.0.0 o `05` existe se e só se o `04` declara uma costura `browser`,
com decisão e data registradas pela sessão. Feature sem tela — job, webhook, command, import de CSV — não gera CT-B, e isso é
deliberado, para o `05` não virar burocracia morta.

**O que o projeto precisa ter para os CT-B** — a skill não assume que está instalado; se faltar,
ela inclui a instalação como passo numerado no `## Dependências` do PRD:

```bash
# 1. Plugin de browser do Pest
composer require pestphp/pest-plugin-browser --dev

# 2. Playwright + browsers
npm install playwright@latest
npx playwright install
```

E adicione ao `.gitignore`:

```gitignore
tests/Browser/Screenshots
```

Para rodar os CT-B em outro navegador: `vendor/bin/pest --browser firefox`.

**Opcional, mas recomendado (Pest 5):**

```bash
# Verificação pontual de mudança pelo próprio agente de IA
composer require pestphp/pest-plugin-agent --dev

# Driver de cobertura — pré-requisito do --tia
pecl install pcov     # ou Xdebug
```

**CI (GitHub Actions)** — os CT-B exigem Node e browsers no runner:

```yaml
- uses: actions/setup-node@v7
  with:
    node-version: lts/*
- run: npm ci
- name: Install Playwright Browsers
  run: npx playwright install --with-deps
```

Os fatos do plugin (servidor próprio, `actingAs()`, esperas, `npm run build`, `--parallel`) têm uma
fonte única, na `feature-test-design`:
[`feature-test-design/references/pest-plugin-browser.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-test-design/references/pest-plugin-browser.md).
A instalação do Pest 5 e o uso de `--tia`, `--agent` e `--mutate` estão em
[`references/pest-5.md`](references/pest-5.md). O TIA é o que torna viável rodar a suíte a cada
passo do PRD: o suite de 19.000+ testes do Laravel Cloud caiu de ~3 minutos para ~5 segundos.

Os CT-B são o único ponto da wiki onde o teste **é** o instrumento de auditoria — ele executa o que o PRD desenhou contra a UI que existe de fato. Por isso a skill delega a escrita deles a um **sub-agente em loop**, em vez de escrever inline:

1. **Ruído**: falha de browser despeja HTML, snapshot, stack do Playwright e path de screenshot. O sub-agente absorve isso e devolve só o veredito — o contexto principal fica limpo (e o Caveman `ultra` continua fazendo sentido).
2. **Iteração**: acertar seletor e timing de UI é tentativa e erro. Loop isolado, no máximo 3 iterações.
3. **Independência**: quem escreve o CT-B a partir do `05` não deve ser quem escreveu a implementação — reduz o viés de "testar o que eu fiz" em vez de "testar o que foi especificado".

O contrato do sub-agente está em [`references/delegacao-casos-de-teste.md`](references/delegacao-casos-de-teste.md).

**Por que o Playwright MCP entra, e só como observador.** O `pest-plugin-browser` roda e atesta o
CT-B; as ferramentas de debug dele exigem um humano na frente.
Para **rodar e atestar**, o plugin basta e é o único caminho. Para o agente **investigar sozinho** por que o seletor não casou, as opções nativas são um PNG ou um dump de HTML. É aí que o MCP ganha: `browser_snapshot` devolve a árvore de acessibilidade (~200–400 tokens de texto estruturado, contra ~3.000–5.000 de um screenshot) e `browser_generate_locator` converte o elemento observado em locator estável.

Três lacunas concretas: **descobrir o locator verdadeiro** numa falha de seletor; **observar
quando o elemento realmente aparece** em UI assíncrona (sem observar, o agente não sabe qual estado
final esperar); e **extrair seletores de tela existente** antes de escrever o CT-B. Configuração,
regras e fallback: [`references/playwright-mcp.md`](references/playwright-mcp.md).

### Por que `search-docs` é a primeira fonte

O Boost expõe a tool MCP **`search-docs`**, que consulta a Documentation API hospedada da Laravel, filtrada pelos pacotes instalados no projeto. A skill trata isso como fonte primária, antes de vendor source e antes de doc na web.

Isso é importante justamente porque a skill agora **recomenda** Pest 5: consultar `search-docs` sobre `--tia` devolveria informação de Pest 4. A skill declara essa lacuna em vez de deixar o agente confiar numa resposta desatualizada.

Cobertura por versão, lacunas e como consultar: [`references/pesquisa-step-3.md`](references/pesquisa-step-3.md#documentation-api-do-boost-search-docs).
