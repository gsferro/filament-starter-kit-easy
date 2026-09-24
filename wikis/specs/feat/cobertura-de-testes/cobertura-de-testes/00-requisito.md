# Requisito — Cobertura de testes medida, com meta e badge

## Fonte

- **Origem**: pedido do mantenedor no chat, via `/feature-wiki`, em continuação à análise da crítica externa ao kit
- **Data**: 2026-09-24
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado verbatim abaixo

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> estude a melhor forma de colocar o teste com --coverage para termos uma noção real de quanto do nosso kit esta sendo coberto com testes
> - implemente no meu php local o necessário
> - teste quanto tempo leva para rodar e a melhor forma de termos essa cobertura
> - determine um numero aceitavel de cobertura e prossiga para atingirmos essa meta de qualidade de software
> - coloquei tudo na documentação explicitamente e ate com badge no @README.md se tiver como deixar explicito no github

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A suíte passa a poder rodar com medição de cobertura de código | *"estude a melhor forma de colocar o teste com `--coverage`"* | funcional |
| RQ-02 | O número resultante reflete **quanto do kit** está coberto — não um recorte arbitrário | *"para termos uma noção real de quanto do nosso kit esta sendo coberto com testes"* | funcional |
| RQ-03 | O driver de cobertura fica instalado e funcionando no PHP local do mantenedor | *"implemente no meu php local o necessário"* | restrição de ambiente |
| RQ-04 | O **tempo de execução** com cobertura é medido e registrado | *"teste quanto tempo leva para rodar"* | não-funcional |
| RQ-05 | Fica definida **a melhor forma** de obter a cobertura no dia a dia — qual comando, quando, e onde | *"e a melhor forma de termos essa cobertura"* | funcional |
| RQ-06 | Existe um **número mínimo aceitável** de cobertura, escolhido e justificado | *"determine um numero aceitavel de cobertura"* | funcional |
| RQ-07 | O kit **atinge** essa meta | *"prossiga para atingirmos essa meta de qualidade de software"* | funcional |
| RQ-08 | Tudo fica na documentação, **explicitamente** | *"coloquei tudo na documentação explicitamente"* | documentação |
| RQ-09 | Há um **badge de cobertura no `README.md`**, visível no GitHub — se for possível | *"ate com badge no @README.md se tiver como deixar explicito no github"* | documentação |

## Ambiguidades e Perguntas Abertas

### RQ-02 — "quanto do nosso kit" é qual recorte?

O kit é um **starter kit**: parte do código é fundação que o projeto instalado consome
(`app/Support`, `app/Models`, `app/Policies`), e parte é esqueleto do Laravel que quem instala
substitui. Medir os dois juntos produz um número que não responde a pergunta do requisito.

- **Assumido**: o denominador é `app/`, que é o código do kit que roda em produção no projeto
  instalado. `database/`, `config/`, `routes/` e `tests/` ficam fora — os três primeiros são
  configuração/dado, e medir cobertura do próprio teste é circular.
- **Se negado**: RQ-02 muda de escopo e o número da meta (RQ-06) muda junto, porque o denominador
  é outro. O passo 2 do PRD e a meta são refeitos.

### RQ-06 — o número é um piso que **reprova**, ou uma métrica que só se registra?

São coisas diferentes: um piso no CI quebra o build abaixo dele; uma métrica registrada só informa.

- **Assumido**: **piso que reprova**, com `--min`. O requisito diz *"meta de qualidade de
  software"* e *"prossiga para atingirmos"* — meta que não reprova ninguém é registro, não meta.
- **Se negado**: o `--min` sai do comando de CI e vira só relatório; RQ-07 deixa de ter critério
  de conclusão objetivo.

### RQ-06 — o número sai de onde?

Escolher um número redondo antes de medir é chutar. Um kit com 2.904 casos pode estar em 40 % ou
em 85 %, e a meta honesta depende de onde ele está.

- **Assumido**: **medir primeiro, decidir depois.** A meta nasce da medição real mais a margem que
  o passo de melhoria alcançar — e fica registrada com o comando que a produziu.
- **Se negado**: não há alternativa razoável; um número sem medição é exatamente o tipo de
  afirmação que os quatro ciclos de quality gate desta semana derrubaram.

### RQ-09 — badge de cobertura exige serviço externo?

Badge de cobertura no GitHub normalmente vem de Codecov ou Coveralls, que são **serviços de
terceiro** com upload do relatório. O kit tem histórico de recusar dependência externa evitável.

- **Assumido**: avaliar as três formas (serviço externo, gist + shields.io, SVG gerado no CI e
  commitado) e escolher **medindo o custo de cada uma**, registrando em ADR.
- **Se negado**: se o mantenedor quiser Codecov explicitamente, é uma linha no workflow e um
  token — decisão dele, não minha.

### RQ-07 — "atingir a meta" tem teto de esforço?

Subir cobertura pode virar um poço sem fundo: sempre há mais uma linha a cobrir, e teste escrito
só para subir número é o anti-padrão que esta esteira inteira combate.

- **Assumido**: a melhoria persegue **lacuna real** — arquivo de `app/` com zero ou pouca
  cobertura que tenha regra de negócio —, não a última linha de um getter. Se a meta exigir cobrir
  código sem risco, a meta é que está errada e será renegociada com o número medido na mão.
- **Se negado**: precisa de teto explícito de esforço do mantenedor.

## Fora de Escopo (declarado)

- **Mutation score.** É a outra métrica que o PCOV destrava (`pest --mutate`), e é melhor que
  cobertura de linha para medir eficácia — mas o requisito pede cobertura, e misturar as duas
  numa entrega só torna nenhuma verificável. Fica como candidato a roadmap.
- **Cobertura das suítes de browser.** O requisito diz *"quanto do nosso kit está sendo coberto"*;
  os CT-B rodam num job separado, em série, e somá-los exigiria merge de relatórios — complexidade
  que não muda a resposta à pergunta feita.
- **Type coverage** (`pest --type-coverage`). Métrica diferente, não pedida.

## Adendo 1 — 2026-09-24

- **Fonte**: pedido do mantenedor no chat, via `/feature-wiki`, durante a medição do passo 3
- **Fidelidade**: **alta** — texto escrito, colado verbatim abaixo

### Texto Original

<!-- IMUTÁVEL, mesmo regime do Texto Original acima. -->

> no artefato que foi proposto para melhorar o controle de testes, foi sugerido o seguinte:
> - 'Instalar o PCOV é barato e destrava duas coisas de uma vez: o --tia e a dimensão K do quality gate (mutation score), que hoje aparece como "não verificado" em todo relatório.'
> - link do artefato: "https://claude.ai/code/artifact/c5e9afc5-9cbc-4a62-ac78-a4262b78b911?sk=kFpB7ZK8SMxfwaEZGbRWxQ#bf267453-c5b6.m56ch99v7x1.3803"
> - instale o PCOV na minha versão do php local para que possamos evoluir na performance e cobertura de testes
> - instale também o XDEBUG para --coverage e outras melhorias na cobertura completa para garantirmos qualidade total
> - analise e pesquise se temos mais niveis de qualidade para implementarmos

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-10 | O PCOV está instalado no PHP local do mantenedor | *"instale o PCOV na minha versão do php local"* | restrição de ambiente | **duplica RQ-03** — já atendida e verificada antes deste adendo |
| RQ-11 | O Xdebug está instalado no PHP local | *"instale também o XDEBUG"* | restrição de ambiente | — |
| RQ-12 | O custo de ter o Xdebug carregado é **medido**, não presumido | *"para --coverage e outras melhorias"* — a melhoria precisa valer o custo | não-funcional | — |
| RQ-13 | Fica levantado e avaliado **quais outros níveis de qualidade** o kit pode adotar | *"analise e pesquise se temos mais niveis de qualidade para implementarmos"* | funcional | — |
| RQ-14 | O `--tia` e o **mutation score** passam a funcionar, que era a promessa citada | *"destrava duas coisas de uma vez: o --tia e a dimensão K"* | funcional | — |

### Ambiguidades e Perguntas Abertas do Adendo

#### RQ-10 já estava atendida quando o pedido chegou

O PCOV **foi instalado nesta sessão**, antes deste adendo, como passo 1 do plano. Verificado:

```
php -i | grep -iE '^PCOV (support|version)'
→ PCOV support => Disabled
→ PCOV version => 1.0.12
```

Registrado como cláusula por honestidade de rastreamento — o adendo pede, e a matriz precisa
mostrar que está feito —, mas **não gera trabalho novo**.

#### RQ-11 — "Xdebug para `--coverage`" parte de uma premissa que a pesquisa desmente

O pedido associa o Xdebug a *"cobertura completa"*. **Para cobertura de linha, o Xdebug não
acrescenta nada** sobre o PCOV — os dois produzem relatórios de precisão comparável, e o próprio
README do PCOV diz que a análise dos dois é equivalente em acurácia.

O que o Xdebug acrescenta é **outra coisa, e é real**: `branch coverage` e `path coverage`
(`XDEBUG_CC_BRANCH_CHECK`), além de detecção de código morto (`XDEBUG_CC_DEAD_CODE`). O PCOV não
faz nenhum dos três.

- **Assumido**: instalar, e justificar pelo **branch coverage**, não por "cobertura completa de
  linha". O valor existe; a razão escrita no pedido é que não se sustenta.
- **Se negado**: se o mantenedor quiser o Xdebug pelo **depurador** (breakpoints em IDE) e não
  pela métrica, a instalação é a mesma e a justificativa muda — e aí `xdebug.mode=debug` entra na
  documentação, que não é o caso deste plano.

#### RQ-11 — os dois convivem? A premissa de conflito também não se sustenta

O README do PCOV diz *"interoperability with Xdebug is not possible"*, o que soa como conflito. A
leitura completa mostra outra coisa: *"Xdebug **may be loaded**"* — o que é impossível é os dois
coletarem **ao mesmo tempo**.

E o PHPUnit resolve isso sozinho
(`vendor/phpunit/php-code-coverage/src/Driver/Selector.php:31-36`): para granularidade de
**linha** ele escolhe o PCOV; para o resto, cai no Xdebug. **São complementares, e a seleção é
automática.**

- **Assumido**: os dois carregados, PCOV com `pcov.enabled=0` e Xdebug com `xdebug.mode=off`; cada
  um ligado por execução, conforme a métrica desejada.
- **Se negado**: se a medição do RQ-12 mostrar que só **carregar** o Xdebug custa caro demais, ele
  sai do `php.ini` e vira instrução de "ligar quando precisar" na documentação.

#### RQ-12 — o custo de carregar o Xdebug precisa ser medido, e a fonte disponível não é neutra

O README do **PCOV** afirma: *"when you load it, you incur the overhead of a debugger even when
it's disabled"*. É uma afirmação de um produto **concorrente** sobre o outro, e o Xdebug 3
introduziu exatamente o `xdebug.mode=off` para resolver isso.

- **Assumido**: **medir**. Suíte com e sem o Xdebug carregado, `xdebug.mode=off` nos dois casos.
  O número decide se ele fica no `php.ini` ou não.
- **Se negado**: não há alternativa razoável — repetir a afirmação de um concorrente sem medir é
  o erro que os quatro ciclos de quality gate desta semana derrubaram.

#### RQ-13 — "níveis de qualidade" é aberto; qual o critério de corte?

Há dezenas de ferramentas de qualidade em PHP. Listar todas é ruído.

- **Assumido**: o corte é **valor por esforço para ESTE kit**, e cada candidato é avaliado por
  três perguntas: (a) responde a uma crítica ou lacuna já **medida** neste projeto? (b) o custo de
  adoção é conhecido? (c) o que ele pega **não** é pego por nada que já existe?
- **Se negado**: o mantenedor nomeia os que quer, e a análise se restringe a eles.

#### RQ-13 — levantar é diferente de implementar. O adendo pede qual das duas?

*"analise e pesquise se temos mais níveis"* pede **levantamento**. *"para implementarmos"* sugere
execução.

- **Assumido**: **levantar e recomendar** nesta entrega, com custo e valor de cada um, e
  **implementar só o que for de custo desprezível e já estiver pago** — concretamente, o mutation
  score, cujo plugin **já está instalado** e que o PCOV acabou de destravar. O resto vira roadmap
  com a medição na mão.
- **Se negado**: se o mantenedor quiser todos implementados nesta entrega, o escopo muda de
  tamanho e vira wiki própria por item — cada um tem CT, documentação e job de CI.

### Fora de Escopo do Adendo (declarado)

- **Xdebug como depurador** (`xdebug.mode=debug`, integração com IDE). O pedido o cita no contexto
  de `--coverage`; configurar breakpoint remoto é outra feature.
- **Xdebug profiler** (`xdebug.mode=profile`) e tracing. Mesma razão.
