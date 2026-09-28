> Referência da feature-test-design 1.16.0. Lida em: qualquer passo, quando for preciso o caso
> que motivou uma regra (o `SKILL.md`, as outras references e o agente `fw-adversario-ct` têm a
> regra; aqui fica a evidência). Fonte única de: os casos medidos que originaram as regras da skill
> e do agente. Exceção, por ser texto de outro tema: os números do lançador `.cmd` do
> `pest --mutate` (`references/mutation-testing.md`).

# Casos medidos — a evidência por trás de cada regra

Cada bloco abaixo saiu, verbatim, do lugar onde a regra está. A regra continua valendo sem o caso;
o caso existe para mostrar que ela nasceu de um defeito que escapou. Números agregados de rodadas
experimentais (defeitos detectados de 18, lacunas cegas por cenário) não ficam aqui: a única tabela
está em `experimentos/README.md` do repositório da coletânea
(<https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md>).

## Princípios — por quê

### Princípio 1 — o caso de teste deriva do requisito, nunca do código

> **Por quê**: medido em 318 métodos focais cobrindo 233 defeitos reais do Defects4J com 11
> modelos — derivar testes a partir do código defeituoso em vez da especificação multiplica por
> ~1,4 os testes que **codificam o bug como comportamento esperado** (2,69% → 3,84%) e corta por
> ~1,5 os testes que detectam o defeito (4,50% → 2,98%). Trocar o código por uma descrição do
> comportamento pretendido no prompt mitiga o efeito; não o reverte. *(arXiv 2607.22883)*
>
> É o mesmo mecanismo pelo qual o PRD não serve de oráculo para o `feature-quality-gate`:
> validar contra a interpretação confirma a interpretação.

### Princípio 1 — corolário: log não é cláusula (regra: Proibição 12)

Caso medido: o template antigo da `feature-wiki` pedia "CTs de log" e esta
skill deriva só do `00` — o conflito foi medido em 2026-09-21 (helper de log declarado e nunca
usado, zero CT de log, 17 logs conferidos um a um pela dimensão D do quality gate).

### Princípio 2 — cenário sem mutante morto não é caso de teste

> **Por quê**: cobertura de linha não prevê eficácia quando se controla o tamanho da suíte
> *(Inozemtseva & Holmes, ICSE 2014)*; detecção de mutantes correlaciona ~73% com detecção de
> defeitos reais e carrega informação que a cobertura não carrega *(Just et al., FSE 2014)*.
> Gerar o teste mirando um mutante é a técnica que o Meta industrializou em 10.795 classes *(ACH, FSE 2025)*.

## Passo 0 — gatilho da revisão adversarial

Caso medido: a adversarial rodou
"para a área C" (P×I 9) e o achado que importou, um laço de redirecionamento com sessão viva,
estava nas áreas D e F — Impacto 3, perfil padrão, fora do gatilho antigo. Com Impacto 3 em
qualquer área, o custo marginal de estender é zero e o risco de não estender é o defeito de
autorização que passa.

## Passo 0 — por que o perfil existe

> Sem este passo o pipeline explode: tabela de decisão e pairwise crescem rápido, e conjunto
> grande demais é abandonado, o que dá cobertura zero.

## Precedência da Project Rule — o caso

O caso concreto: a `feature-wiki` sugere `pest --parallel --tia`
como padrão, e um projeto pode ter medido que `--parallel` derruba os CT-B e que sem PCOV o `--tia`
não termina.

## Passo 3 — regras de execução (desenvolvimento em `references/tecnicas-por-regra.md`)

### Criação ≠ edição ≠ uso

> Medido em experimento controlado: dois conjuntos independentes deixaram passar os mesmos três
> defeitos de **criação** (valor negativo, valor acima do teto, data no passado) — ambos haviam
> testado o mesmo campo exaustivamente pelo lado do cálculo. Fechada a criação, uma revisão
> adversarial encontrou **quatro defeitos que viviam só na edição**: normalização, unicidade,
> autorização e domínio existiam no `create` e sumiam no `save`.

### Toda partição de EP se repete em cada rastreio de efeito

> Medido: todos os cenários de consumo e trilha de um conjunto usavam cupom de porcentagem. Um
> atalho no ramo `valor_fixo` — ignorando validade, limite e o registro de auditoria — ficava
> **verde no conjunto inteiro**. Foi o achado mais caro da revisão adversarial.

### Ciclo de volta (2-switch)

Defeito do segundo giro (`aguardando_diretor → rejeitar → rascunho → enviar` com destino errado ou
registros do ciclo anterior contando). Medido: os dois
conjuntos avaliados pararam no primeiro evento e deixaram passar exatamente esse defeito.

### Não-efeito só discrimina se o mundo tiver destinatário

> Medido: um conjunto de 49 cenários fechou 21 de 21 células da matriz e marcou atomicidade como
> coberta em dois lugares do texto, citando um cenário de centro **sem gestor** e outro de
> organização **sem diretor** — argumentando, corretamente, que a falha acontecia no mesmo ponto em
> que o caminho feliz notificaria. O mutante *"o e-mail sai com a gravação da aprovação falhando"*
> atravessou intacto: **não havia ninguém para notificar nas duas configurações**. O juiz cego:
> *"os cenários que 'provam' atomicidade o fazem em configurações de zero destinatários, onde o
> mutante e a implementação correta produzem o mesmo observável."*

### Estado × operação

> Medido: um conjunto declarou a legenda `❌ = recusa e não-efeito` nas 8 colunas da matriz. A
> revisão adversarial do próprio braço achou a legenda **falsa** em duas delas — `enviar` sem o
> não-efeito de notificação e `rejeitar` sem o de histórico —, o que invalidava a atribuição do
> mutante *"grava a etapa e só depois recusa a transição"* em 10 células marcadas como cobertas. A
> coluna `aprovar` seguiu sem o não-efeito de notificação até o juiz.

> Medido: um conjunto de 63 cenários fechou dez células de papel × verbo, afirmou o não-efeito em
> cada uma, e ainda assim executou **17 das 21** células inválidas. As quatro ausentes eram o mesmo
> par de verbos (`aprovar`/`rejeitar`) nos dois estados que nenhuma regra cita junto deles —
> `rascunho` e `cancelada`. O mutante *"aprovar solicitação ainda em rascunho"* atravessou intacto,
> com o checklist marcando a linha como coberta. O juiz cego chamou de **buraco de enquadramento,
> não de rigor**: o orçamento inteiro foi para o eixo do ator, e o eixo do estado ficou com as
> células que as regras já sugeriam.

### O exemplo tem de ser discriminante

Medido: um conjunto marcou "precisão monetária" como
coberta, citou dois cenários, e **nenhum dos cinco exemplos numéricos distinguia `float` de
inteiro** — o item ficou ✅ no checklist com o defeito intacto, que é pior que lacuna declarada,
porque ninguém volta a olhar.

### Fechar uma lacuna declarada sem discriminar

> Medido: entre duas rodadas, o fuso horário saiu de *lacuna declarada com quatro tentativas
> registradas* para *item ✅ do checklist apontando um cenário que não mata o mutante*. A taxa de
> detecção não mudou; a honestidade do conjunto, sim — para pior.

### Premissa de comportamento

> Medido: um braço fixou *"cadastrar cupom já vencido é permitido"* e escreveu a linha da partição
> com o `Então` **aceito**. O defeito plantado era exatamente *"validade no passado aceita na
> criação"* — a premissa coincidiu com o defeito, e o cenário, materializado, ficaria **vermelho
> contra a implementação correta**. Na rodada anterior, o mesmo braço assumiu o contrário e
> **detectou**. No mesmo conjunto, a premissa de domínio numérico foi assumida como **recusa** e
> matou dois defeitos. Nas três premissas de comportamento cuja verdade foi medida, a resposta certa
> foi sempre **recusar**.
>
> O juiz cego: *"onde o card não decide, o conjunto fixa uma suposição e escreve o cenário em cima
> dela. […] ele mora na escolha do oráculo, não na escolha do valor."* A correção não é deixar de
> escrever o cenário — é **fixar o sinal por regra**.

**Nota 1.16.0.** A conclusão acima é de antes da raia requisito. Desde a 1.16.0, *fixar o sinal por
regra* vale para a recomendação (➡️) da pergunta de requisito — falha fechado —, não para um
cenário: a `RQ` fica `aberta — Qn`, e o cenário da direção só nasce da resposta, no Adendo. A regra e
o custo da troca estão em `references/tecnicas-por-regra.md` §Premissa.

### Premissa de mecanismo

> Medido: um conjunto fixou *"a exclusão é física"* e registrou no checklist *"unicidade +
> exclusão lógica — não se aplica"*. O mutante *entidade excluída continua aplicável* atravessou
> como **lacuna cega**, enquanto a linha *"estado × operação de escrita — o inativo ainda
> funciona?"* aparecia marcada como coberta. A premissa não estava errada; usá-la para não
> escrever o cenário, sim.

### Afirmação negativa

> Medido (2026-09-15): *"`DashboardWidget` não precisa de escopo próprio: é filho, sempre alcançado
> via `dashboard_id` dentro de um dashboard já escopado"* — frase sem `arquivo:linha`, factualmente
> falsa (três ações do pacote buscavam o filho por id cru do cliente), e nenhum dos 29 CTs a
> tocava. O cenário que a falsifica cabe em 12 linhas e falha no primeiro `run`.

### Todo estado de erro declara a saída

> Medido (2026-09-15): o cenário *"o único dashboard da organização exclui o papel do usuário → 403"*
> foi escrito, passou e virou **contrato** na wiki. Ninguém escreveu que a tela de fallback devolvia
> o usuário para o mesmo 403 — a raiz inteira do painel inacessível para o papel comum, suíte verde.

## Passo 4 — superfície Livewire na taxonomia (tabela em `references/taxonomia-de-defeito.md`)

> **As três linhas de superfície Livewire vieram de um caso medido (2026-09-17).** A feature montava
> sobre o **framework**, não sobre um pacote; a linha antiga dizia *"feature monta sobre pacote de
> terceiro"*, o agente leu ao pé da letra, declarou *"nenhum pacote persiste entidade → não se
> aplica"*, e o conjunto de 43 CTs saiu sem um único cenário de entrada inválida. Passaram três
> defeitos: `$rotulos[$valor]` sem `??` (500 dentro da renderização da tabela), `Carbon::parse($valor)`
> sem guarda (500 no widget) e um método público que devolvia coluna não exposta ao navegador. Os
> três foram achados só no `/code-review` do diff. **A condição certa é a superfície, não a origem
> dela** — e o discriminante que falta quase sempre é este: *a página sanitiza o valor, o widget o
> recebe cru*, então o cenário precisa entrar pelo widget.

## Setup Global do `04` — fixture por transições reais

Estava no template do `04` (`references/template-04.md`, helper `{entidade}Em`), que é copiado
para todo `04` gerado; saiu dele na 1.16.0 e ficou aqui:

> Medido (2026-09-21): a fixture por transição expôs que a notificação real exigia
> contexto de painel — um `create(['situacao' => …])` nunca mostraria

## Passo 6 — oráculo invertido (gate, item 6)

> Medido: `CT-22` de um conjunto dizia *"Dado uma solicitação de valor 3.000,00 criada pela
> Beatriz / Quando a Beatriz aprova a solicitação / Então a solicitação fica aprovada"* — sem
> nunca dizer que ela havia sido enviada. O próprio índice do conjunto já o marcava com
> *"Mata: —"*. Escrito em Pest exatamente como está, ele **exige** que aprovar um rascunho
> funcione. É o único caso em que um cenário a mais deixa o conjunto pior que o conjunto vazio.

## Escolha de camada — gate de tela de escrita

> Medido num kit real: 45 telas cobertas por `visit($rotas)->assertNoJavaScriptErrors()`, das
> quais **5 telas `create` não tinham gravação testada em lugar nenhum** — exatamente o defeito
> (`Select::make('roles')` derrubando o `save` com o `GET` verde) que a regra do projeto fora
> escrita para prevenir.

## Escolha de camada — gate de camada da regra

> Medido: um conjunto com 51 cenários fechou a matriz papel × ação pela tela, afirmou o não-efeito
> em cada célula e marcou *"autorização exercida na ação, não só consultada"* como coberta. O
> mutante *policy aplicada só no form do Filament; request direto ao backend passa* ficou **verde
> no conjunto inteiro**. É o pedágio da regra da camada mais barata: economizar a camada externa
> em toda a superfície apaga a diferença entre **a regra existe** e **a tela chama a regra**.

## Revisão adversarial — primeira feature completa com adversário cego

As "perguntas 5–7" citadas no bloco (numeração da época, mantida porque o bloco é verbatim) são as
sondas 6–8 do contrato em `agents/fw-adversario-ct.md` e do resumo em
`references/revisao-adversarial.md`.

> **Medido em 2026-09-21** (feature de aprovação de compra, 60 CTs derivados por `opus`): a rodada 1
> do adversário cego (`opus`, só `00` + `04`) achou **5 implementações erradas que passavam por
> todos os cenários**; a rodada 2 achou o estrutural — `R6` eram duas regras (`R6a`/`R6b`). E as
> perguntas 5–7 acima nasceram do que **nem o adversário** perguntou e o quality gate depois
> perguntou: gestor que acumula `diretor` assinava as duas etapas sozinho; quem já decidiu perdia a
> solicitação de vista. A cegueira pesou mais que o modelo — os dois eram `opus`.

### Sondas 6 e 7 do agente — o caso, fora do prompt

Até a 1.15.0 o caso ia dentro das sondas 6 e 7 de `agents/fw-adversario-ct.md`, e portanto no
prompt de **todo** despacho — enviesando a revisão de qualquer feature para o domínio de aprovação
(estudo 2026-09-26 §7.6). A sonda ficou no agente; o caso ficou aqui, verbatim:

- Sonda 6 (acumulação de papéis): em 2026-09-21 o conjunto tinha o par
  solicitante × gestor e não tinha gestor × diretor, e a mesma pessoa assinava as duas etapas
- Sonda 7 (participante histórico e destino do link): em 2026-09-21 o aprovador perdia o registro
  de vista no instante em que decidia e o link do e-mail virava 404
