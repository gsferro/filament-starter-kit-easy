# Casos de Teste — Cobertura de testes medida, com meta e badge

> Requisito: `00-requisito.md` (RQ-01…RQ-09 + Adendo 1: RQ-10…RQ-14)
> Derivado do **requisito**, não do plano. **O `01-plano-acao.md`, o `02-decisoes-arquiteturais.md`
> e o `03-progresso.md` não foram abertos**, nem a implementação (`app/Console/Commands/`, os testes
> de cobertura em `tests/Kit/`, `.github/workflows/`, `pestw.cmd`, `.github/badges/`, as seções de
> cobertura de `docs/` e do `CHANGELOG.md`). Nenhum cenário foi escrito olhando implementação.
> A implementação desta feature **já existe e está mergeada** — a derivação às cegas é deliberada
> (princípio 1 da `feature-test-design`), e a reconciliação CT ↔ teste existente está na seção
> `## Reconciliação com os testes existentes`, escrita **depois** de a derivação fechar.

## Perfil de Derivação

| Área | Descrição | P | I | P×I | Perfil |
|---|---|---|---|---|---|
| **A — verificação do piso** | ler um relatório, calcular a razão, comparar com o piso, sair com código | 3 | 2 | 6 | padrão |
| **B — recorte medido** | que código entra no denominador (RQ-02) | 2 | 2 | 4 | padrão |
| **C — ambiente de driver** | PCOV + Xdebug carregados, desligados por padrão, seleção por execução | 3 | 2 | 6 | padrão |
| **D — documentação e badge** | número, comando, tempo e níveis de qualidade nos dois idiomas + badge | 2 | 2 | 4 | padrão |
| **E — atingimento da meta** | RQ-07 | 1 | 2 | 2 | mínimo |
| **F — mutation score / `--tia`** | RQ-14 | 2 | 2 | 4 | padrão |

**Justificativa dos fatores.** P=3 em A e C: A faz *parse* de um formato externo com muitas condições
de borda; C integra duas extensões PHP cuja documentação de uma declara incompatibilidade com a
outra. I=2 em toda parte: nenhuma área toca dinheiro, dado de terceiro, autorização, compliance ou
operação irreversível. **Nenhuma área tem Impacto 3 e nenhuma tem perfil completo** — logo a revisão
adversarial **não era obrigatória** pelo gatilho da skill. Foi executada assim mesmo, e mudou o
conjunto inteiro (ver `## Revisão Adversarial`).

- Técnicas aplicadas: **EP**, **BVA 3-valores** (com linhas extras de arredondamento e de piso
  fracionário), **tabela de decisão** (piso × relatório), **partição de entrada × efeito colateral**,
  **rastreio de efeito nas duas direções**, **normalização** (o mesmo número em quatro artefatos).
- **Não aplicadas, com motivo**: *tabela estado × evento* — a feature não persiste entidade com ciclo
  de vida; não há enum nem verbo de transição. **Substituída** pela matriz *estado da entrada ×
  efeito no badge* (ver `## Matriz de entrada × efeito`), que é o análogo real e estava vazia na
  rodada 1. *Matriz papel × ação* — a feature não cria superfície autenticada; o único ator é quem
  executa o comando, e não há segundo papel contra o qual falsificar. *Pairwise* — o verificador
  recebe dois parâmetros (relatório, piso), não três.
- Cenários: **32** · Regras: **11** · Mutantes previstos: **65** · Sem matador: **3** (M7, M43, M56 —
  duas delas **reduzidas** pela revisão adversarial, nenhuma fechada)
<!-- 28 derivados às cegas do requisito (CT-01…CT-28) + 4 que a implementação revelou e que a
     reconciliação promoveu a cenário (CT-29…CT-32, mutantes M61…M65). Ver
     `## Reconciliação com os testes existentes`. -->
- **Estado da cobertura real** (reconciliação de 2026-09-25): 5 cobertos, 8 parcialmente cobertos,
  18 sem teste, 1 divergente. A lista priorizada está em
  `## O que o requisito pede e nenhum teste cobre hoje`.

### Divergência declarada: rule do projeto × skill

A `feature-test-design` sugere `pest --parallel --tia` como padrão de execução. A rule
`.ai/rules/testes-browser.md` (medida neste projeto) **vence**: `--parallel` derruba os CT-B, e
`--tia` exige run completo, então os dois não convivem numa invocação só. Consequência para este
conjunto: os CTs abaixo rodam na suíte `Kit` (`--group=kit`), **nunca** sob a suíte de browser, e
nenhum deles depende de `--tia` — exceto **CT-28**, que o invoca como subprocesso estreitado e
declara o custo.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | comando de linha que verifica o piso; alvo de execução do projeto; job/step de CI; seção de fonte de cobertura da configuração do PHPUnit; `php.ini` (PCOV, Xdebug); `README.md` + artefato de badge; `docs/pt/**` e `docs/en/**`. **Nenhum** model, migration, policy, rota, página, widget ou componente Livewire | CT-01…CT-05, CT-21…CT-27 |
| **F**unction | ler um relatório; calcular a razão coberto/mensurável; comparar com um piso; devolver código de saída e diagnóstico; **escrever o badge**; documentar | CT-06…CT-20 |
| **D**ata | o relatório (ausente, caminho degenerado, ilegível, válido-mas-vazio, válido); as contagens (0 mensuráveis → divisão por zero); a razão nas bordas; **o piso como texto livre** (negativo, 0, 100, 101, fracionário, com vírgula, com sufixo, com espaços, vazio, não numérico); os caminhos do recorte; os números que doc e badge exibem | CT-04…CT-14, CT-17…CT-21 |
| **I**nterfaces | **só CLI** — o comando de verificação, o alvo do projeto e o job de CI que o invoca. **Sem** HTTP, sem Livewire, sem fila, sem webhook, sem import. É por isso que não há `05-casos-de-teste-browser.md` | CT-16, CT-22…CT-25 |
| **P**latform | PHP 8.4 (NTS, Windows, local) × runner Linux do CI; PCOV **e** Xdebug carregados; **seleção por execução**, não por instalação; Pest 5 / PHPUnit 13 / paratest; `pest --mutate` dá **100 % falso no Windows** | CT-01, CT-02, CT-03, CT-27, CT-28 |
| **O**perations | o mantenedor mede antes do PR; o CI mede a cada push; **o uso indevido é encolher o recorte, relaxar o piso ou congelar o badge** para a meta "passar" — é a operação hostil que esta feature precisa resistir, e ela ganhou cenário em cada um dos três eixos | CT-05, CT-17, CT-18, CT-23, CT-24, CT-25 |
| **T**ime | a duração da execução com cobertura (RQ-04) e o custo de carregar o Xdebug (RQ-12) são o objeto de duas cláusulas; e o badge/documentação **envelhecem** — o número congela enquanto o código muda | CT-03, CT-17, CT-20, CT-21, CT-27 |

Nenhuma dimensão ficou vazia. A dimensão **T** aqui não é fuso nem DST (não há instante comparado
neste requisito — só *duração*): é **obsolescência do número publicado**, e é ela que produz M43.

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — a plataforma coleta cobertura de verdade, e o coletor é ligado **por execução**, não por instalação | C (padrão) | RQ-01, RQ-03, RQ-10, RQ-11, RQ-12 | EP + arnês de subprocesso | CT-01, CT-02, CT-03 |
| **R2** — o recorte **efetivamente medido** é todo o `app/` e nada além — por qualquer via que possa estreitá-lo | B (padrão) | RQ-02 | EP + normalização (config × comando × CI) | CT-04, CT-05 |
| **R3** — a comparação com o piso é inclusiva na borda e não arredonda nem trunca a favor | A (padrão) | RQ-06, RQ-07 | **BVA 3-valores** (+2 linhas) | CT-06, CT-07 |
| **R4** — a razão comparada é a do recorte **inteiro**, ponderada por tamanho, em linhas | A (padrão) | RQ-02, RQ-06 | partição por **peso** + `@premissa` de mecanismo | CT-08, CT-09 |
| **R5** — o piso é validado **onde é informado**: fora do domínio ou fora do formato recusa, e nunca aprova | A (padrão) | RQ-06 | EP sobre texto livre + BVA | CT-10, CT-11 |
| **R6** — relatório ausente, com caminho degenerado, ilegível ou sem nada mensurável **recusa** — e **não toca o badge** | A (padrão) | RQ-01, RQ-06, RQ-09 | EP inválida + rastreio de não-efeito | CT-12, CT-13, CT-14 |
| **R7** — o veredito é idempotente e o diagnóstico é acionável, com cada comando citado existindo | A (padrão) | RQ-05, RQ-06 | rastreio de efeito | CT-15, CT-16 |
| **R8** — o badge **acompanha a medição**, e a meta é um número só nos quatro artefatos | D (padrão) | RQ-06, RQ-08, RQ-09 | rastreio de efeito na direção positiva + normalização | CT-17, CT-18, CT-19, CT-20 |
| **R9** — a documentação registra, na seção do assunto e nos dois idiomas, o que o requisito mandou — e o comando documentado **mede de verdade** | D (padrão) | RQ-04, RQ-05, RQ-08, RQ-12, RQ-13 | rastreio de efeito + EP | CT-21, CT-22 |
| **R10** — o piso **bloqueia** no CI, por toda a cadeia que propaga o código de saída | E (mínimo) → herda **padrão** de A | RQ-06, RQ-07 | EP por **mecanismo de propagação** + fim-a-fim | CT-23, CT-24, CT-25 |
| **R11** — o mutation score e o `--tia` ficam **auditáveis e executáveis**, não só declarados | F (padrão) | RQ-13, RQ-14 | EP + arnês de subprocesso estreitado | CT-26, CT-27, CT-28 |

**Técnica escalada acima do perfil da área**: R3 usa **BVA 3-valores com duas linhas extras** (o perfil
padrão previa 2-valores) porque a comparação tem três armadilhas que 2 valores não separam — `<` × `<=`,
**arredondar** antes de comparar e **truncar** antes de comparar. Com meta inteira, `round()` e
`floor()` produzem o mesmo resultado nas bordas inteiras; só a linha de piso **fracionário** os
separa. **R10 atravessa duas áreas** (E, mínimo; A, padrão) e herda o **maior**, padrão.

**Desdobramento declarado**: o que na rodada 1 era uma regra única sobre o piso virou **R3 + R4**,
porque acumulava 8 mutantes plausíveis — sinal, pela regra do gate, de que eram duas regras.

### `RQ` → regra: cobertura completa da decomposição

| `RQ` | Regra(s) | Ou justificativa de por que não virou regra |
|---|---|---|
| RQ-01 | R1, R6, R9 | — |
| RQ-02 | R2, R4 | — |
| RQ-03 | R1 | o **ato** de instalar no PHP do mantenedor é processo; o que sobra verificável é *"o PHP que roda esta suíte tem com que coletar"* (CT-01) |
| RQ-04 | R9 | **medir** o tempo é processo humano; é falsificável o número medido estar registrado, com unidade, **pareado ao comando com cobertura que o produziu** (CT-21) |
| RQ-05 | R9, R7 | *"a melhor forma"* é juízo, não oráculo; é falsificável o comando documentado **existir, ligar a coleta e gravar o formato que o verificador lê** (CT-22), e o *quando/onde* estar registrado (CT-21) |
| RQ-06 | R3, R4, R5, R8, R10 | — |
| RQ-07 | R10 | **cláusula sem oráculo próprio** — ver `## Achado: RQ-07`. CT-25 é o mais perto que se chega |
| RQ-08 | R9, R8 | — |
| RQ-09 | R8, R6 | — |
| RQ-10 | R1 | duplica RQ-03; o `00` a declara atendida antes do adendo. CT-01/CT-02 seguram a não-regressão |
| RQ-11 | R1 | CT-02 e CT-03 cobrem a convivência e o snippet documentado; a **presença** do Xdebug no PHP local não é verificável do CI → **lacuna reduzida M7** |
| RQ-12 | R1, R9 | **medir** o custo é processo; sobra o **invariante que a decisão produziu** (CT-03, incluindo o snippet de ini, que o override do lançador não mascara) e o **registro do número** (CT-21) |
| RQ-13 | R9 | **levantar e avaliar** é juízo humano; é falsificável o levantamento trazer, **por candidato**, resposta às três perguntas de corte que o `00` fixou, e separar adotado de roadmap (CT-21) |
| RQ-14 | R11 | CT-28 executa o `--tia` num arnês estreitado; CT-26 e CT-27 seguram o mutation score |

Toda `RQ` gerou **regra** ou **justificativa escrita**. Nenhuma ficou órfã.

---

## Achado: RQ-07 não tem oráculo próprio — e o que sobra é mais do que parecia

RQ-07 diz *"o kit **atinge** essa meta"*. Como está escrita, **nenhum cenário a falsifica
diretamente**, e o motivo é a forma da cláusula:

1. **"Atinge" é um estado instantâneo, não um invariante.** Um cenário que afirmasse *"a cobertura do
   kit é ≥ meta"* precisaria **medir a cobertura dentro do próprio caso**, isto é, rodar a suíte
   inteira sob coletor de dentro de um caso da suíte. É recursão, e é inviável.
2. **Ler o último relatório gravado mede o artefato, não o kit.** O cenário ficaria verde para sempre —
   o pior tipo de falso ✅: cobre a cláusula no papel e não pode ficar vermelho.
3. **Sobra a forma invariante**: *"a cada execução, a cobertura medida é ≥ a meta — e, se não for, a
   entrega não passa"*. Nessa forma RQ-07 **não é cláusula nova**: é RQ-06 com o piso efetivamente
   aplicado.

**Correção trazida pela revisão adversarial.** A rodada 1 concluiu que sobravam apenas duas asserções
sobre o **texto** do workflow — e isso subestimava o que é falsificável. Existe um oráculo
**fim-a-fim barato** que não é recursão: rodar, como subprocesso, **a cadeia completa que o passo de
CI executa** (wrapper, pipe e tudo) contra um **relatório sintético a `meta − 0,01 pp`**, e afirmar
que o **código de saída do subprocesso é diferente de zero**. Não roda a suíte sob coletor, usa a
fixture que já existe, e mata de uma vez as quatro formas de neutralização do código de saída que
CT-23 sozinho não cobre. É **CT-25**, e é o cenário mais forte do conjunto para RQ-07.

**Consequência que permanece**: enquanto o job não for *required status check* no PR, o vermelho pode
ser ignorado no merge — e isso vive na configuração do GitHub, **fora da árvore** (M56, lacuna
declarada). A **pergunta** ao `00` continua valendo, mas é menos decisiva do que a rodada 1 dizia:
na leitura de invariante, **existe oráculo**.

---

## Fronteira com o Plano

**Esta derivação não leu o `01-plano-acao.md`.** A skill prevê que o PRD forneça nomes, paths e
superfície; aqui essa entrada foi deliberadamente cortada. Consequência declarada: **os cenários
nomeiam o comportamento, não o símbolo.** Onde um cenário diz *"o verificador de cobertura"*, quem
materializar o teste liga ao ponto de entrada real do projeto.

### Vazamentos acidentais, declarados

Três arquivos **permitidos** pela instrução (`composer.json` para versões, `tests/Pest.php`,
`.ai/rules/`) revelaram detalhe de implementação. Registro o que vazou e o que foi feito com isso:

| Vazou de | O que revelou | Tratamento |
|---|---|---|
| `composer.json` → `scripts` | o nome do comando de verificação, **o valor numérico do piso**, o formato e o nome do arquivo de relatório, as suítes medidas, a flag do driver e a de escrita do badge | **recusado como oráculo em bloco.** Nenhum cenário cita o nome do comando, o nome do arquivo, o formato ou **o número do piso**. O piso é sempre lido em tempo de execução da fonte única (CT-18) e os cenários afirmam **a relação**, nunca o literal |
| `phpunit.xml` (por um `grep` de `testsuite\|directory`) | a seção de fonte de cobertura aponta `app` | **não usado como oráculo de valor**, e é o que CT-04/CT-05 **verificam**: a afirmação do requisito (RQ-02) é que o denominador é o código do kit; que esteja configurado assim hoje é o que o cenário existe para travar amanhã |
| `ls tests/Kit/` | existe um arquivo de teste de cobertura (**só o nome**; o conteúdo não foi aberto) | nenhum uso na derivação. A sincronia de IDs CT ↔ teste está na `## Reconciliação`, escrita depois |
| `vendor/phpunit/php-code-coverage`, `vendor/sebastian/environment` | a mecânica de seleção de driver | leitura **de vendor**, não da feature — e obrigatória pela rule `.ai/rules/specs.md` ("justificativa de comportamento de pacote se escreve depois de ler o vendor"). Evitou um oráculo invertido em CT-01/CT-02: ver a armadilha de API em R1 |

| Item de plano/implementação | Recusado como oráculo porque | Destino |
|---|---|---|
| nome do comando de verificação | escolha de implementação | detalhe do cenário; o Gherkin diz "o verificador de cobertura" |
| nome e formato do arquivo de relatório | escolha de implementação | detalhe do cenário |
| **o número do piso** | o requisito **deliberadamente não fixa número** (RQ-06: *"medir primeiro, decidir depois"*) | ver a regra do valor literal, abaixo |
| escolha de quais suítes entram na medição | escolha de implementação; afeta o **numerador** e a direção é conservadora | pergunta ao usuário, não cenário — **e a premissa não se estende ao denominador**, onde a direção se inverte (é o que CT-05 passou a cobrir) |
| nome do arquivo de badge / do lançador `.cmd` | escolha de implementação | detalhe do cenário |

### A regra do "valor literal do requisito" é **inaplicável por construção** aqui

A skill exige que ao menos um cenário use o **número literal do requisito**, para que um default
errado não sobreviva. **Este requisito não tem número**: RQ-06 pede que um número seja *escolhido e
justificado*, e o `00` registra a premissa *"medir primeiro, decidir depois"*. Escrever `78` (ou
qualquer outro) num `Então` seria copiar a implementação para dentro do oráculo — exatamente o
defeito que a skill mede em ~8×.

O substituto, que preserva a **função** da regra:

- **CT-07** roda o verificador **sem informar o piso**, contra um relatório a `meta − 0,01 pp`, e
  exige reprovação. É esse cenário que exercita o valor efetivo do default, sem citá-lo.
- **CT-18** exige que o valor efetivo do default seja **igual ao número que a documentação declara**,
  extraído **ancorado ao rótulo de meta, dentro da seção de cobertura** — e não "aparece em algum
  lugar do arquivo", que casaria com "78 testes" ou "PHP 7.8".
- **CT-10** cobre o **tipo e o formato** do piso, e não só o valor: um default correto no número e
  errado na coerção (`(int) '78,5'`) atravessaria CT-07 e CT-18 juntos.
- Nenhum cenário injeta o piso por configuração em **todos** os casos: CT-07 é o caso sem injeção.

---

## Perguntas para o 00-requisito.md

> O `00-requisito.md` está fechado nesta derivação (a feature já foi entregue e o `00` é a linha de
> base de comparação). **Desvio declarado**: as perguntas abaixo vão neste `04`, em bloco pronto para
> colagem em `## Ambiguidades e Perguntas Abertas`. Cada uma continua **bloqueando** o que depende
> dela.

```markdown
### RQ-07 — "atingir a meta" é estado de entrega ou invariante de execução?

Se for **estado de entrega** ("na v0.40.0 o kit estava em X %"), RQ-07 não é caso de teste: é
evidência no `03-progresso.md`. Se for **invariante**, RQ-07 é RQ-06 com o piso aplicado, e as duas
cláusulas deveriam ser fundidas — a matriz de rastreabilidade hoje conta duas onde há uma.

- **Assumido**: **invariante** (falha fechado — a leitura que exige mais do sistema). CT-23, CT-24 e
  CT-25 escritos em cima dela.
- **Se negado**: CT-23…CT-25 deixam de ser obrigatórios como cenário e viram item de evidência.
- **Invariante afirmado de qualquer forma**: seja qual for a leitura, um relatório abaixo da meta
  **nunca** produz entrega aprovada (CT-06, CT-07, CT-25).

### RQ-06 — a unidade da cobertura é linha, elemento ou branch?

O requisito diz "cobertura" sem qualificar. O relatório carrega, no mínimo, duas contagens
independentes (linhas e elementos/métodos), e o Adendo 1 traz uma terceira (branch, via Xdebug).
Um piso de 78 % significa coisas diferentes em cada uma.

- **Assumido** (premissa de **mecanismo**): **linha**. CT-09 é escrito nesse mecanismo, com fixture
  em que as duas razões **divergem**.
- **Se negado**: CT-09 inverte, e o piso precisa ser reexpresso na unidade escolhida.
- **Lacuna declarada vinculada**: nenhum cenário verifica a razão de **branch**, que o RQ-11
  destravou. Tentado: derivar um cenário de branch coverage do requisito — o requisito não pede piso
  de branch, só instalação do Xdebug.

### RQ-06 — o piso aceita valor fracionário?

`78,5` é um piso legítimo e um valor que distingue `round()` de `floor()` na comparação. O requisito
não diz se a meta é inteira.

- **Assumido** (premissa de **mecanismo**): **aceita fracionário**, e a linha correspondente de
  CT-06 e CT-10 é escrita nesse mecanismo.
- **Se negado** (só inteiro): a linha fracionária de CT-10 passa de `aceito` para `recusa`, e CT-06
  perde a única linha capaz de separar arredondamento de truncamento — o que é **achado**, não
  simplificação: a regra precisaria de outro discriminante.

### RQ-06 — um piso de 0 é um piso válido?

Um piso de 0 aprova qualquer relatório, inclusive um vazio. O requisito pede "meta de qualidade".

- **Assumido** (premissa de **comportamento**, direção por **falha fechado**): **recusa** — um piso
  que não reprova ninguém não é piso.
- **Se negado**: a linha `0` de CT-10 inverte para `aceito`.
- **Invariante afirmado junto, em CT-11**: seja qual for a decisão sobre o 0, **nenhum piso inválido
  faz o verificador aprovar um relatório abaixo da meta documentada**.

### RQ-02 — medir com um subconjunto de suítes é deliberado?

O denominador é `app/` (já assumido no `00`). O **numerador** depende de quais suítes rodam sob o
coletor. Rodar menos suítes **subestima** — direção conservadora. **Atenção**: esse conservadorismo
vale só para o numerador; estreitar o **denominador** (por `include`, por ini do driver ou por flag
no comando) **infla** o número, e por isso ganhou cenário próprio (CT-05).

- **Assumido**: subconjunto de suítes é deliberado e conservador; sem cenário.
- **Se negado**: RQ-02 ganha um cenário exigindo que todas as suítes de backend entrem na medição.

### RQ-07 / RQ-09 — o job que aplica o piso é *required status check* no PR?

O workflow expressa o **gatilho** (e CT-23 o verifica), mas a obrigatoriedade do check no merge vive
na configuração do GitHub, **fora da árvore**. Sem ela, o vermelho pode ser ignorado (M56).

- **Assumido**: é obrigatório.
- **Se negado**: RQ-07 é inverificável na prática, e o achado é de processo, não de teste.
```

---

## Setup Global

### Personas

Esta feature **não tem persona**. Não há usuário autenticado, papel, painel nem organização. O único
ator é **quem executa o comando** — o mantenedor no terminal ou o runner do CI —, e o requisito não
distingue os dois. Declarado, e é o motivo de a matriz papel × ação não existir.

### Fixtures

- **Relatório de cobertura sintético**, gravado em diretório temporário do caso:
  `relatorioCom(int $mensuraveis, int $cobertas): string` — devolve o caminho de um relatório no
  **formato que o comando de medição documentado produz**, com as contagens pedidas.
  `relatorioComDesvio(float $pontosPercentuais): string` — monta as contagens a partir da **meta lida
  em tempo de execução** (nunca de um literal): com `mensuraveis = 100000`, `0,001 pp` é
  representável e as bordas do BVA saem exatas.
  `relatorioComArquivos(array $arquivos): string` — vários arquivos com tamanhos e coberturas
  **desiguais**, para separar razão agregada de média por arquivo.
  `relatorioComRazoesDivergentes(float $linhas, float $elementos): string` — para CT-09.
- **Relatórios degenerados**: caminho inexistente; caminho que é diretório; caminho com espaço;
  arquivo não-parseável; arquivo válido com `mensuraveis = 0`. Cada um em **seu** cenário ou **sua
  linha de `Esquema`** — partições inválidas não se combinam.
- **Badge de partida**: um artefato de badge com um percentual **conhecido e diferente** do que a
  fixture do cenário produziria. É ele que põe o **destinatário do efeito** no mundo: sem um badge
  de partida com valor distinto, toda asserção sobre o badge (positiva ou de ausência) é vácuo.
- **Arnês de subprocesso**: `php -d <ini> -r <script>` sobre o autoload do projeto (CT-02);
  execução do **comando completo do passo de CI** (CT-25); execução estreitada do `--tia` sobre um
  único arquivo de teste, com teto de tempo (CT-28). Em todos, o oráculo é a **saída** e o **código
  de saída** do subprocesso, nunca o processo corrente.
- Por `.ai/rules/testes.md`, esses helpers ficam **no próprio arquivo de teste** enquanto **um só**
  arquivo os usar. Se um segundo passar a usá-los, mudam para `tests/Pest.php` — nunca um clone com
  outro nome.
- Helpers do projeto reaproveitados, já em `tests/Pest.php`: `documentacaoDoKit($idioma)`,
  `paginasDoSite($idioma)`, `secoesDoMarkdown($caminho)`, `readmeSemCitacao($arquivo)`,
  `naArvoreDoKit()`, `semComentarios($codigo)`, `codigoSemComentario($codigo)`.

### Fakes

**Nenhum.** Não há fila, e-mail, notificação, evento nem HTTP de saída nesta feature. Declarado para
que a ausência não passe por esquecimento.

### Estratégia de DB

`RefreshDatabase` global, herdado de `tests/Pest.php` para a suíte `Kit`. **Nenhum cenário deste
conjunto toca o banco** — a estratégia é herdada, não requerida.

### Camada e suíte

- **Todos os 28 cenários vão para `tests/Kit/`** (grupo `kit`), a suíte da fundação do kit.
- **Por que nenhum vai para `tests/Unit/`**: `tests/Pest.php` liga `TestCase::class` apenas a
  `Feature`, `Kit`, `Tenancy`, `Browser` e `BrowserTenancy`. **`tests/Unit` não recebe o `TestCase`
  da aplicação** — um caso ali roda sem container, sem `config()` e sem `Artisan`. A escada real
  deste projeto começa em `Kit`, não em `Unit`; a lógica do verificador (R3, R4, R5, R6) seria
  candidata natural a `Unit` e não pode ir.
- **Nenhum vai para `Browser`**: ver `## Sem CT-B`.

### Convenções de asserção herdadas do projeto

- **`toContain()` é variádico** — o 2º argumento é outra agulha, não mensagem. Asserção de
  **ausência** usa `assertStringNotContainsString($agulha, $palheiro, $mensagem)`
  (`.ai/rules/testes.md`).
- **Asserção de ausência sobre arquivo documentado filtra comentário antes** — os arquivos do kit
  **citam** o que proíbem, e é lá que está escrito o porquê. Vale para **CT-05** e **CT-23**: um
  comentário do workflow explicando *"nunca use `|| true` aqui"* reprovaria o cenário correto. Use
  `semComentarios()` / `codigoSemComentario()` — mantendo a asserção de **presença** sobre o texto
  cru, porque **citar não é executar**.
- **Asserção sobre documentação é ancorada, nunca `str_contains` solto.** Extraia a **seção** com
  `secoesDoMarkdown()`, e dentro dela o **número junto do seu rótulo**. `78` casa com "78 testes",
  "PHP 7.8" e com uma tabela comparativa de ferramentas.
- Comando: `$this->artisan(...)` com `assertSuccessful()` / `assertFailed()`; `Artisan::call()` +
  `Artisan::output()` quando a saída em texto for o oráculo. Para os cenários de subprocesso, o
  oráculo é o código de saída do processo filho, não o do teste.
- `assertDatabaseHas` não aparece: não há persistência.

---

## Matriz de entrada × efeito no badge

A feature não tem estado × operação (não persiste entidade). O **análogo real** — e que estava vazio
até a revisão adversarial — é *estado do relatório de entrada × o efeito colateral que o verificador
dispara no caminho feliz*, que é **escrever o badge**.

**6 estados × 1 operação (verificar) = 6 células.** Legenda: **✅** = o badge passa a exibir a razão
do relatório · **⛔** = o badge permanece **byte a byte** como estava.

| Estado do relatório | Efeito esperado | Célula |
|---|---|---|
| ausente / caminho degenerado | ⛔ | CT-12 |
| ilegível | ⛔ | CT-13 |
| `mensuraveis = 0` | ⛔ | CT-14 |
| razão abaixo do piso | ⛔ | CT-16 |
| razão na borda do piso | ✅ | CT-06 (linha `0,00`) + CT-17 |
| razão acima do piso | ✅ | CT-17, CT-15 |

**6 de 6 células resolvidas**, nenhuma por argumento. A legenda ⛔ é auditada: em **cada** uma das
três células de erro, o `Dado` coloca **um badge de partida com valor conhecido e diferente** — sem
isso, "o badge não mudou" seria verdadeiro num mundo sem badge, e o mutante *"escreve o badge antes
de validar"* atravessaria intacto.

---

## Regra R1 — a plataforma coleta cobertura de verdade, e o coletor é ligado por execução

> `RQ-01`, `RQ-03`, `RQ-10`, `RQ-11`, `RQ-12` · área **C** · perfil **padrão** ·
> técnica: **EP** (nenhum driver / um / dois; ligado / desligado) + **arnês de subprocesso**

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: o PHP do kit tem com que coletar cobertura, liga o coletor por execução e não paga o custo dele fora disso

    Cenário: [CT-01] o PHP corrente tem com que coletar cobertura
      Dado o PHP em que esta suíte está rodando
      Quando o kit pergunta quais extensões de cobertura estão carregadas
      Então ao menos uma entre "pcov" e "xdebug" está na lista

    Esquema do Cenário: [CT-02] com as duas carregadas, coleta quem foi ligado naquela execução — e coleta de verdade
      Dado as duas extensões de cobertura carregadas no PHP corrente
      E um arquivo de "app/" com linhas executáveis conhecidas
      Quando o kit pede um coletor de cobertura de linha numa execução em que <ligado> está ligado e a outra está desligada
      Então o coletor devolvido é o da extensão "<driver>", nomeada
      E ao executar esse arquivo sob o coletor, a contagem de linhas cobertas dele é maior que zero

      Exemplos:
        | ligado | driver | # partição                                              |
        | pcov   | pcov   | granularidade de linha com o coletor barato              |
        | xdebug | xdebug | o outro coletor assume quando o primeiro está desligado  |

    Cenário: [CT-03] fora de uma execução de medição, as duas ficam desligadas — inclusive no arquivo de configuração
      Dado o PHP em que esta suíte está rodando, com as extensões de cobertura carregadas
      Quando o kit lê a configuração efetiva de cada uma e o trecho de configuração de PHP que a documentação manda instalar
      Então "pcov.enabled" vale "0" no processo corrente e "0" no trecho documentado, quando a extensão "pcov" está carregada
      E "xdebug.mode" vale "off" no processo corrente e "off" no trecho documentado, quando a extensão "xdebug" está carregada
```

**Notas de materialização — uma armadilha de API que invalidaria CT-01 e CT-02.**

**Não chame o seletor de driver do PHPUnit em processo, dentro da suíte comum.** A seleção é
`Selector::select()`
(`vendor/phpunit/php-code-coverage/src/Driver/Selector.php:Selector::select:27-43`): para
granularidade de linha ela escolhe o PCOV **apenas se** `Runtime::hasPCOV()`, que exige
`ini_get('pcov.enabled') === '1'`
(`vendor/sebastian/environment/src/Runtime.php:hasPCOV:241-244`); caindo no Xdebug, o construtor do
`XdebugDriver` lança `XdebugNotEnabledException` quando o modo não inclui `coverage`
(`vendor/phpunit/php-code-coverage/src/Driver/XdebugDriver.php:160`). Ou seja: **na configuração que
CT-03 exige — as duas carregadas e desligadas —, pedir um coletor em processo lança exceção contra a
implementação correta.** Um CT-01 escrito com `forLineCoverage()` seria **oráculo invertido**:
ficaria verde só num ambiente que paga o custo do coletor o tempo todo, que é o defeito M1/M2.

**O arnês que funciona é o subprocesso**, e ele foi tentado antes de declarar lacuna: cada linha de
CT-02 roda `php -d pcov.enabled=<x> -d xdebug.mode=<y> -r` sobre o autoload do projeto. O `E` final
existe porque *"devolveu um coletor sem lançar"* **não prova que ele coleta**: um `pcov.directory`
apontando para fora de `app/`, ou um `pcov.exclude`, devolve coletor e mede zero.

CT-03 tem **duas fontes** de propósito: a configuração **efetiva do processo** e o **trecho de
configuração que a documentação manda instalar**. Um `php.ini` com o coletor permanentemente ligado,
neutralizado por `-d` só no lançador da suíte, passa na primeira e é pego pela segunda — e é a única
guarda automática do RQ-12.

CT-02 e CT-03 são condicionais às extensões estarem carregadas, o que os torna auto-anuláveis num
ambiente onde uma falta (lacuna M7). O caso deve **declarar a condição na mensagem da asserção**, não
pular em silêncio: *"a extensão X não está carregada — RQ-11 não verificada neste ambiente"*.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o `php.ini` liga `xdebug.mode=coverage` permanentemente — toda execução comum paga o coletor | CT-03 |
| M2 | o `php.ini` liga `pcov.enabled=1` permanentemente, pelo mesmo motivo | CT-03 |
| M3 | um bump de ambiente (imagem de CI, upgrade de PHP local) deixa a instalação **sem nenhum** driver | CT-01 |
| M4 | a leitura ingênua do README do PCOV leva a desinstalar um dos dois; ou os dois carregados derrubam a coleta; ou a granularidade de linha passa a ser coletada pelo Xdebug mesmo com o PCOV ligado, e toda medição fica lenta sem ninguém notar | CT-02 (as duas linhas) |
| M5 | o coletor é selecionado corretamente e **não coleta** — `pcov.directory` fora de `app/`, ou `pcov.exclude` — e o relatório sai vazio | CT-02 (`E` da contagem > 0) |
| M6 *(revisão adversarial — não conta para o teto)* | o `php.ini` mantém o coletor ligado e o lançador da suíte o neutraliza com `-d`: a configuração efetiva do processo mente sobre o custo pago pelas demais execuções | CT-03 (trecho documentado) |
| M7 *(revisão adversarial)* | o Xdebug nunca foi instalado (RQ-11 não cumprida): CT-02 e CT-03 passam vacuamente | ⚠️ **lacuna reduzida, não fechada**. Tentado e recusado: (a) asserção incondicional `extension_loaded('xdebug')` → vermelha num CI que legitimamente não o tem; (b) condicionar ao sistema operacional → acopla o oráculo à plataforma. **Tentado e adotado** (fecha metade): o trecho de configuração documentado, em CT-03, **nomeia o Xdebug** — se ele não existir na receita, o cenário cai. O que resta sem matador é o PHP do mantenedor **divergir da receita**, e isso é evidência do `03-progresso.md` (`php -m`), não cenário |

---

## Regra R2 — o recorte efetivamente medido é todo o `app/`, e nada além

> `RQ-02` · área **B** · perfil **padrão** ·
> técnica: **EP** sobre partições de caminho + **normalização** (configuração × comando documentado ×
> passo de CI × ini do driver)

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: o recorte medido é o código do kit que roda em produção, por inteiro, e nada além dele

    Esquema do Cenário: [CT-04] a fonte de cobertura é declarada, e cada diretório está dentro ou fora dela
      Dado a configuração de fonte de cobertura que a ferramenta de teste usa, que existe e não está vazia
      Quando o mantenedor verifica se "<diretorio>" entra no cálculo da cobertura
      Então o resultado é "<situacao>"

      Exemplos:
        | diretorio   | situacao | # partição                                    |
        | app         | dentro   | o código do kit — o denominador do requisito  |
        | tests       | fora     | medir o próprio teste é circular              |
        | config      | fora     | configuração, não código de negócio           |
        | database    | fora     | migration e seeder são dado                   |
        | routes      | fora     | declaração, não comportamento                 |
        | vendor      | fora     | código de terceiro infla o denominador        |

    Cenário: [CT-05] todo subdiretório de app existente na árvore está no recorte, por qualquer via que possa estreitá-lo
      Dado a lista dos subdiretórios de "app/" presentes na árvore do kit
      Quando o mantenedor compara essa lista com o recorte efetivo — o declarado na configuração, o resultante das opções do alvo de medição documentado, o resultante das opções do passo de integração contínua e o resultante da configuração das extensões de cobertura
      Então os dois conjuntos são iguais
      E nenhuma dessas quatro origens restringe o recorte a um subconjunto de "app/"
```

**Notas de materialização.** CT-05 foi reescrito depois da revisão adversarial, e a mudança é a mais
importante do conjunto: a versão anterior procurava **exclusões** e passava com qualquer
estreitamento feito por outra via — um `<include>app/Models</include>` em vez de um `<exclude>`, um
`pcov.directory=app/Support` no `php.ini`, ou um filtro de cobertura na linha de comando do alvo e
do passo de CI. O oráculo agora é **igualdade de conjuntos** sobre o recorte **efetivo**, e cobre as
quatro origens.
A asserção de ausência ("nenhuma restringe") incide sobre arquivos que provavelmente **comentam** por
que não restringem — filtre comentário antes (`.ai/rules/testes.md`), mantendo a presença sobre o
texto cru.
CT-04 ganhou *"que existe e não está vazia"* no `Dado` porque as cinco linhas `fora` passavam
**vacuamente** numa configuração **sem seção de fonte nenhuma** — que é o pior caso, o que mede o
projeto inteiro.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M8 | não há seção de fonte declarada e a ferramenta mede o projeto inteiro, `vendor/` incluído | CT-04 (`Dado` + linha `vendor`) |
| M9 | o recorte inclui `tests/` — os testes cobrem a si mesmos e o número sobe sozinho | CT-04 (linha `tests`) |
| M10 | o recorte inclui `database/seeders`, que os testes executam inteiro | CT-04 (linha `database`) |
| M11 | um subdiretório de `app/` com pouca cobertura é **excluído** para a meta passar | CT-05 |
| M12 *(revisão adversarial — não conta para o teto)* | o recorte é estreitado por **inclusão** (`<include>app/Models</include>`), o que satisfaz "app está dentro" sem medir `app` inteiro | CT-05 |
| M13 *(revisão adversarial)* | o estreitamento acontece **fora do arquivo de configuração** — `pcov.directory` no `php.ini`, ou um filtro de cobertura na linha de comando do alvo documentado e do passo de CI | CT-05 (as quatro origens) |

---

## Regra R3 — a comparação com o piso é inclusiva e não arredonda nem trunca a favor

> `RQ-06`, `RQ-07` · área **A** · perfil **padrão** · técnica: **BVA 3-valores + 2 linhas**
> (fronteira: a razão contra o piso; granularidade **0,01 pp**, com uma linha em **0,004 pp** para
> separar `round()` e uma linha de **piso fracionário** para separar `floor()`)

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: o piso é inclusivo na borda, e a razão é comparada como foi calculada

    Esquema do Cenário: [CT-06] o piso é inclusivo na borda e não arredonda nem trunca a favor
      Dado um relatório de cobertura cuja razão medida é exatamente <razao>
      Quando o mantenedor executa o verificador de cobertura informando o piso <piso>
      Então o verificador termina com o veredito "<veredito>"
      E a razão exibida na saída é exatamente <razao>

      Exemplos:
        | razao      | piso       | veredito | # borda                                              |
        | meta−0,01  | meta       | reprova  | borda−1, no incremento do tipo (0,01 pp)             |
        | meta−0,004 | meta       | reprova  | arredonda para a meta, mas está abaixo — mata round()|
        | meta       | meta       | aprova   | borda exata — o piso é inclusivo                     |
        | meta+0,01  | meta       | aprova   | borda+1                                              |
        | 78,40      | 78,50      | reprova  | piso fracionário: truncar para 78 aprovaria          |
        | 78,50      | 78,50      | aprova   | piso fracionário na borda exata                      |

    Cenário: [CT-07] sem piso informado, vale a meta padrão do kit
      Dado um relatório de cobertura cuja razão medida está a 0,01 ponto percentual abaixo da meta
      Quando o mantenedor executa o verificador de cobertura sem informar nenhum piso
      Então o verificador reprova
      E o piso citado na saída é o mesmo número que a documentação do kit declara como meta
```

**Notas de materialização.**
As quatro primeiras linhas usam a **meta lida em tempo de execução**; as duas últimas usam **números
literais fracionários**, e é a única forma de existirem: com meta inteira, `round()` e `floor()`
produzem resultado idêntico em todas as bordas inteiras, e a rodada 1 do conjunto deixava o
truncamento vivo sem perceber. A linha `78,40 / 78,50` é `@premissa` de mecanismo (ver a pergunta
devolvida ao `00` sobre piso fracionário).
O segundo `Então` exige o **valor exato**, não *"a razão com as mesmas casas decimais"* — esta última
forma, da rodada 1, degenerava em "a saída contém um número com vírgula".
CT-07 é o **único cenário sem injeção de piso** — é ele que impede um default errado de sobreviver.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M14 | `>` no lugar de `>=` — a meta exata reprova | CT-06 (linha `meta`) |
| M15 | `<` no lugar de `<=` do outro lado — a razão logo abaixo da meta aprova | CT-06 (linha `meta−0,01`) |
| M16 | a razão é **arredondada** (`round()`, `number_format()`) antes da comparação | CT-06 (linha `meta−0,004`) |
| M17 *(revisão adversarial — não conta para o teto)* | a razão ou o piso são **truncados** (`(int)`, `floor()`) antes da comparação | CT-06 (linhas `78,40` / `78,50`) |
| M18 | quando a opção de piso não vem, o default é `0` — o gate nunca reprova ninguém | CT-07 |

---

## Regra R4 — a razão comparada é a do recorte inteiro, ponderada por tamanho, em linhas

> `RQ-02`, `RQ-06` · área **A** · perfil **padrão** ·
> técnica: **partição por peso** (a fixture precisa ter arquivos de tamanhos **desiguais**) +
> `@premissa` de mecanismo (a unidade é linha)

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: a razão é o total coberto sobre o total mensurável do recorte, e não uma estatística por arquivo

    Cenário: [CT-08] a razão é ponderada pelo tamanho dos arquivos, não a média dos percentuais
      Dado um relatório de cobertura com um arquivo de 10 linhas mensuráveis totalmente coberto e outro de 990 linhas mensuráveis com metade coberta
      Quando o mantenedor executa o verificador de cobertura informando o piso 60
      Então o verificador reprova
      E a razão exibida é 50,5 por cento

    @premissa
    Cenário: [CT-09] a razão comparada é a de linhas, e não a de elementos
      Dado um relatório de cobertura em que a razão de linhas é 60 por cento e a razão de elementos é 90 por cento
      Quando o mantenedor executa o verificador de cobertura informando o piso 80
      Então o verificador reprova
      E a razão exibida é 60 por cento
```

**Notas de materialização.**
CT-08 é o exemplo **discriminante** que a rodada 1 não tinha: com "dois arquivos de mesmo tamanho",
média por arquivo e razão agregada **coincidem por construção**, e o cenário parecia cobrir sem
cobrir. Com 10 e 990 linhas, a agregada é `(10 + 495)/1000 = 50,5 %` e a média dos percentuais é
`(100 + 50)/2 = 75 %` — as duas caem em lados opostos do piso 60, e o cenário separa três
implementações de uma vez (agregada, média, maior).
CT-09 é escrito sobre a **premissa de mecanismo** "a unidade é linha". A fixture é deliberadamente
divergente (60 % × 90 %) e os dois lados do piso 80 são escolhidos para que a implementação errada
**aprove**. O mecanismo descartado — **branch coverage**, que o RQ-11 destravou — fica como lacuna
declarada vinculada à mesma pergunta.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M19 *(revisão adversarial — não conta para o teto)* | a razão é a **média aritmética dos percentuais por arquivo** | CT-08 |
| M20 | o piso é aplicado arquivo a arquivo, ou sobre o **maior** percentual do relatório | CT-08 |
| M21 | a razão é lida da contagem de **elementos/métodos**, e não da de linhas | CT-09 |

---

## Regra R5 — o piso é validado onde é informado: fora do domínio ou do formato, recusa

> `RQ-06` · área **A** · perfil **padrão** · técnica: **EP sobre texto livre** + **BVA** na borda
> superior — uma partição inválida por linha, nunca duas no mesmo cenário
>
> Premissa de **comportamento** (`@premissa`): o requisito não decide se `0` é piso válido. Direção
> fixada por **falha fechado**: **recusa**.

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: um piso fora do domínio ou fora do formato recusa a execução, e nunca resulta em aprovação

    @premissa
    Esquema do Cenário: [CT-10] cada valor de piso é aceito ou recusado no ponto em que é informado
      Dado um relatório de cobertura em que todas as linhas mensuráveis estão cobertas
      Quando o mantenedor executa o verificador de cobertura informando o piso "<piso>"
      Então o verificador termina com o veredito "<veredito>"
      E quando o veredito é "recusa", a saída nomeia a opção de piso como inválida, e não a cobertura

      Exemplos:
        | piso   | veredito | # partição                                                       |
        | -1     | recusa   | abaixo do domínio                                                 |
        | 0      | recusa   | @premissa: piso que não reprova ninguém não é piso (falha fechado) |
        | abc    | recusa   | não numérico — a coerção silenciosa para 0 é o defeito            |
        |        | recusa   | vazio ≠ ausente: ausente cai em CT-07, vazio é entrada inválida    |
        | 101    | recusa   | acima do domínio                                                  |
        | 78%    | recusa   | sufixo: aceitar e truncar é coerção silenciosa                    |
        | " 78 " | recusa   | espaços nas bordas — ou normaliza, ou recusa; nunca vira 0        |
        | 1e2    | recusa   | notação científica: 100 disfarçado, que a coerção aceita           |
        | 78,5   | aceito   | @premissa de mecanismo: piso fracionário é válido                 |
        | 100    | aceito   | borda superior válida — e, com este relatório, aprova             |

    Cenário: [CT-11] piso inválido nunca transforma uma medição abaixo da meta em aprovação
      Dado um relatório de cobertura cuja razão medida está a 0,01 ponto percentual abaixo da meta
      Quando o mantenedor executa o verificador de cobertura informando o piso "abc"
      Então a saída em nenhum momento diz que a cobertura foi aprovada
      E o artefato de badge do kit continua com o conteúdo que tinha antes da execução
```

**Notas de materialização — a correção mais cara da revisão adversarial.**
A rodada 1 usava **um relatório abaixo da meta** para todas as linhas do `Esquema`. Com essa fixture,
*"recusa o piso"* e *"reprova a cobertura"* produzem **o mesmo código de saída**, e três linhas do
`Esquema` (`vazio`, `101`, `100`) não matavam nada — o Índice as creditava mesmo assim. A fixture
agora é **100 % coberta**, de modo que um piso válido **aprova** e a recusa é observável por
diferença de veredito **e** por mensagem.
O **invariante das duas leituras da premissa** mudou-se para **CT-11**, com a fixture correta
(abaixo da meta) e com o badge no mundo: qualquer que seja a resposta sobre o piso `0`, um piso
inválido **não pode** transformar uma medição abaixo da meta em aprovação, nem tocar o badge.
**Se negado** (o mantenedor decidir que `0` é piso válido): a linha `0` de CT-10 inverte para
`aceito`, e CT-11 continua valendo sem alteração.
"Vazio" e "ausente" são partições **diferentes**: ausente é CT-07 (default), vazio é CT-10.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | `(int) $opcao` converte `"abc"` em `0` e o verificador aprova tudo em silêncio | CT-10 (linha `abc`), CT-11 |
| M23 | piso negativo é aceito e o gate nunca reprova | CT-10 (linha `-1`) |
| M24 | piso acima de 100 é aceito e o gate reprova **sempre**, inclusive com 100 % — o CI vermelho que ninguém consegue consertar | CT-10 (linha `101`) |
| M25 | `100` é rejeitado por um `< 100` no validador, e a cobertura total deixa de ser atingível | CT-10 (linha `100`) |
| M26 *(revisão adversarial — não conta para o teto)* | a opção vazia é tratada como ausente e cai no default, mascarando um erro de digitação no script de CI | CT-10 (linha vazia) |
| M27 *(revisão adversarial)* | o piso é validado só pela borda inferior e depois coagido a inteiro: `78,5` → `78`, `78%` → `78`, `1e2` → `100` | CT-10 (linhas `78,5`, `78%`, `1e2`), CT-06 (linhas fracionárias) |

---

## Regra R6 — relatório ausente, degenerado, ilegível ou vazio recusa — e não toca o badge

> `RQ-01`, `RQ-06`, `RQ-09` · área **A** · perfil **padrão** ·
> técnica: **EP** — partições inválidas isoladas + **rastreio de não-efeito** com destinatário real

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: sem uma medição legível, o verificador recusa, diz que não mediu, e não publica número nenhum

    Esquema do Cenário: [CT-12] caminho de relatório que não é um relatório recusa, nomeando o caminho
      Dado um artefato de badge do kit exibindo 42 por cento
      E um caminho de relatório "<caminho>"
      Quando o mantenedor executa o verificador de cobertura sobre esse caminho
      Então o verificador reprova
      E a saída nomeia o caminho recebido e diz que não encontrou um relatório legível nele
      E a saída não exibe percentual algum de cobertura
      E o artefato de badge continua exibindo 42 por cento

      Exemplos:
        | caminho                       | # partição                                  |
        | um arquivo que não existe     | ausente                                     |
        | um diretório existente        | existe, e não é arquivo                     |
        | um caminho com espaço, válido | Windows: aceito, e o cenário exige sucesso  |

    Cenário: [CT-13] relatório ilegível recusa, sem aprovar e sem publicar número
      Dado um artefato de badge do kit exibindo 42 por cento
      E um arquivo de relatório de cobertura cujo conteúdo não é parseável no formato esperado
      Quando o mantenedor executa o verificador de cobertura sobre esse arquivo
      Então o verificador reprova
      E a saída diz que o relatório não pôde ser lido
      E a saída não exibe percentual algum de cobertura
      E o artefato de badge continua exibindo 42 por cento

    Cenário: [CT-14] relatório sem nenhuma linha mensurável recusa, e não vale 100 por cento
      Dado um artefato de badge do kit exibindo 42 por cento
      E um relatório de cobertura válido em que o total de linhas mensuráveis é zero
      Quando o mantenedor executa o verificador de cobertura informando a meta como piso
      Então o verificador reprova
      E a saída diz que não havia nada a medir
      E a saída não exibe "100"
      E o artefato de badge continua exibindo 42 por cento
```

**Notas de materialização.**
A linha do **caminho com espaço** é a única partição **válida** do `Esquema` e está ali de propósito:
sem ela, uma implementação que recusa **todo** caminho passaria nas três linhas. O `Então` dela é o
sucesso, e o `Esquema` precisa de uma coluna de veredito se o executor preferir explicitá-la.
As asserções sobre o badge são o fechamento da **matriz de entrada × efeito**, e só valem porque o
`Dado` **põe um badge de partida com valor conhecido e diferente** no mundo — sem destinatário, "o
badge não mudou" é verdadeiro em qualquer implementação.
CT-12 e CT-13 afirmam sobre a **mensagem**, não só sobre o veredito: "reprova" sozinho não separa *"o
relatório sumiu"* de *"a cobertura caiu"*, e confundir os dois é o que leva alguém a baixar o piso
para consertar um CI vermelho por outro motivo. É a versão CLI de *todo estado de erro declara a
saída*.
CT-14 é **o cenário mais importante do conjunto**: `0/0` é a divisão que, na implementação ingênua,
resulta em `100 %` — e um relatório vazio é exatamente o que um job produz quando o driver de
cobertura não carregou. Esse mutante faz o gate aprovar precisamente quando nada foi medido.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | o arquivo ausente vira razão `0 %` e reprova "por acidente", com a mensagem errada | CT-12 (asserção sobre a mensagem) |
| M29 | `0` mensuráveis → `0/0` → `100 %` → **aprova um relatório vazio** | CT-14 |
| M30 | o parse lança exceção não tratada e o processo sai com código `0`, ou com stack trace sem diagnóstico | CT-13 |
| M31 | um `try/catch` genérico engole o erro e o verificador aprova por falta de dado | CT-12, CT-13, CT-14 |
| M32 *(revisão adversarial — não conta para o teto)* | o verificador **escreve o badge antes de validar** o relatório: entrada degenerada zera ou trunca o badge e só então reprova | CT-12, CT-13, CT-14 (`E` do badge) |

---

## Regra R7 — o veredito é idempotente e o diagnóstico é acionável

> `RQ-05`, `RQ-06` · área **A** · perfil **padrão** · técnica: **rastreio de efeito**

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: verificar a mesma medição duas vezes produz o mesmo veredito e não move nada na segunda vez

    Cenário: [CT-15] a primeira verificação move o badge; a segunda não move mais nada
      Dado um artefato de badge do kit exibindo 42 por cento
      E um relatório de cobertura gravado, cuja razão medida é 5 pontos percentuais acima da meta
      Quando o mantenedor executa o verificador de cobertura duas vezes sobre o mesmo relatório
      Então os dois vereditos são "aprova" e as duas razões exibidas são iguais
      E depois da primeira execução o badge deixou de exibir 42 por cento e passou a exibir a razão do relatório
      E o conteúdo do badge depois da segunda execução é byte a byte igual ao de depois da primeira
      E o relatório de cobertura continua com o mesmo conteúdo que tinha antes da primeira execução

    Cenário: [CT-16] ao reprovar, a saída diz o medido, o piso e um próximo passo que existe
      Dado um artefato de badge do kit exibindo 42 por cento
      E um relatório de cobertura cuja razão medida está a 0,01 ponto percentual abaixo da meta
      Quando o mantenedor executa o verificador de cobertura informando a meta como piso
      Então o verificador reprova
      E a saída exibe a razão medida e o piso aplicado
      E o comando que a saída indica para obter o detalhe por arquivo existe entre os alvos declarados do projeto
      E o artefato de badge continua exibindo 42 por cento
```

**Notas de materialização — a segunda correção cara da revisão adversarial.**
A rodada 1 afirmava apenas *"o badge depois da segunda execução é igual ao de depois da primeira"*.
Isso é **verdadeiro por definição** num escritor de badge que **não escreve** — um no-op, um literal
fixo ou um `max(medido, meta)` ficavam verdes **por causa** do defeito. O cenário agora tem a
**direção positiva** antes da idempotência: o badge **saiu** de 42 % e **chegou** ao valor do
relatório. Só depois disso a segunda execução prova idempotência, e o oráculo está ancorado no
**agregado persistido** (o arquivo de badge), nunca no retorno de duas chamadas.
CT-16 ganhou a asserção de que o comando indicado **existe** — a rodada 1 exigia que a saída *nomeasse*
um comando, sem nada que impedisse apontar para um alvo inexistente, que é a versão CLI do
redirecionamento para um destino que não existe.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M33 | a segunda execução acumula sobre a primeira (append no relatório, soma nas contagens) e a razão muda | CT-15 |
| M34 | o badge é reescrito com carimbo de data a cada execução — diff eterno, e ninguém mais lê a mudança do número | CT-15 |
| M35 | ao reprovar, o verificador só devolve o código de saída, sem número algum | CT-16 |
| M36 | a saída exibe o piso e omite o medido (ou o contrário): não dá para saber a distância | CT-16 |
| M37 *(revisão adversarial — não conta para o teto)* | a saída de erro aponta para um comando que não existe, e quem a segue recebe "command not found" | CT-16 |

---

## Regra R8 — o badge acompanha a medição, e a meta é um número só nos quatro artefatos

> `RQ-06`, `RQ-08`, `RQ-09` · área **D** · perfil **padrão** ·
> técnica: **rastreio de efeito na direção positiva** + **normalização**
>
> **Estouro de teto declarado**: 4 cenários no perfil padrão (teto 3). Justificativa — M38 (badge
> no-op/clamped) só morre com a direção positiva, e ela não cabe em nenhum dos outros três. O gate
> vence o teto.

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: o badge publica a medição corrente, e o número da meta é o mesmo no verificador, na documentação pt, na en e no README

    Esquema do Cenário: [CT-17] o badge publicado é a razão do relatório verificado, qualquer que seja ela
      Dado um artefato de badge do kit exibindo 42 por cento
      E um relatório de cobertura cuja razão medida é <razao>
      Quando o mantenedor executa o verificador de cobertura escrevendo o badge
      Então o badge passa a exibir <razao>

      Exemplos:
        | razao      | # partição                                         |
        | meta+5,00  | acima da meta — mata o badge constante e o no-op   |
        | meta       | na borda — mata o badge que só sobe                |

    Cenário: [CT-18] a meta aplicada pelo verificador é a meta declarada na seção de cobertura em português
      Dado a seção de cobertura de testes da documentação do kit em português
      E um relatório de cobertura cuja razão medida está a 0,01 ponto percentual abaixo da meta
      Quando o mantenedor executa o verificador de cobertura sem informar piso
      Então o verificador reprova
      E o piso citado na saída é igual ao número que, nessa seção, está rotulado como a meta de cobertura

    Cenário: [CT-19] a documentação em inglês declara os mesmos quatro números que a em português
      Dado as seções de cobertura de testes da documentação do kit em português e em inglês
      Quando o mantenedor compara as duas
      Então as duas declaram a mesma meta de cobertura
      E as duas declaram o mesmo tempo medido de execução com cobertura
      E as duas declaram o mesmo custo medido de manter o Xdebug carregado
      E as duas citam o mesmo comando de medição de cobertura

    Cenário: [CT-20] o badge do README aponta para um alvo que existe e não contradiz a meta
      Dado o README do kit
      Quando o mantenedor localiza o badge de cobertura
      Então o badge referencia um alvo que existe — um arquivo presente na árvore ou uma URL absoluta
      E o percentual que ele exibe é maior ou igual à meta que a documentação declara
```

**Notas de materialização.**
**CT-17 é novo e nasceu da revisão adversarial.** Sem ele, todo o eixo do badge era tautológico:
`badge ≥ meta` (CT-20) é **verdadeiro por construção** num badge estático, num badge com número
escrito à mão e num `max(medido, meta)` — que é precisamente a "operação hostil" que a varredura
SFDIPOT declarou. A fixture sintética fecha a metade que importa **sem recursão e sem artefato
versionado**: dois relatórios de razões diferentes têm de produzir dois badges diferentes.
CT-18 usa **extração ancorada**: o número **rotulado como meta**, **dentro da seção de cobertura** —
não "o número aparece no arquivo", que casaria com "78 testes" ou com uma tabela comparativa. E o
cenário fixa a fixture e afirma o **veredito**, para não ficar indefinido caso o piso só seja
impresso no caminho de reprovação.
CT-19 normaliza **quatro** números, não dois: a rodada 1 comparava só meta e comando, e deixava o
tempo (RQ-04) e o custo (RQ-12) divergirem livremente entre os idiomas.
CT-20 permanece com a **desigualdade**, que é a relação correta (o badge mostra a medição corrente; a
meta é o piso) — mas agora ela não é a única defesa do badge.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M38 *(revisão adversarial — não conta para o teto)* | o escritor de badge é **no-op**, grava um literal fixo, ou publica `max(medido, meta)` — o badge nunca desce | CT-17 (as duas linhas) |
| M39 | a meta muda no verificador e a documentação fica para trás | CT-18 |
| M40 | só a documentação em português é atualizada; a em inglês segue com o número antigo | CT-19 |
| M41 *(revisão adversarial)* | meta e comando são normalizados entre idiomas, mas o tempo e o custo divergem | CT-19 |
| M42 | o badge aponta para um arquivo que não existe, ou para um caminho relativo que o GitHub não resolve | CT-20 |
| M43 | o badge fica defasado **entre execuções do CI**: a cobertura cai e o badge só é reescrito quando alguém roda o verificador localmente | ⚠️ **lacuna reduzida, não fechada**. Tentado e recusado: (a) recomputar a cobertura dentro do caso — recursão; (b) comparar o badge com o último relatório gravado — mede o artefato, e um relatório velho passa junto com o badge velho. **Tentado e adotado** (fecha a metade do escritor): CT-17 prova que o badge **acompanha** o relatório que lhe é dado. O que resta é **estrutural** e está em CT-24: o badge é reescrito no mesmo job que aplica o piso |

---

## Regra R9 — a documentação registra o que o requisito mandou, e o comando documentado mede de verdade

> `RQ-04`, `RQ-05`, `RQ-08`, `RQ-12`, `RQ-13` · área **D** · perfil **padrão** ·
> técnica: **rastreio de efeito** (cinco itens obrigatórios) × **EP** (dois idiomas)
>
> `@premissa` — o item do levantamento usa os **três critérios de corte** que o `00` fixou para
> RQ-13: (a) responde a uma lacuna já medida neste projeto? (b) o custo de adoção é conhecido? (c) o
> que ele pega não é pego por nada que já existe?

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: cada coisa que o requisito mandou registrar está na seção do seu assunto, nos dois idiomas, na forma que a torna verificável

    @premissa
    Esquema do Cenário: [CT-21] cada item obrigatório está documentado na forma exigida, no idioma
      Dado a seção de cobertura de testes da documentação do kit no idioma "<idioma>"
      Quando o mantenedor procura pelo item "<item>"
      Então ele está presente nessa seção — e não apenas numa linha de índice, de sumário ou de roadmap
      E ele satisfaz a exigência de forma "<forma>"

      Exemplos:
        | idioma | item                                          | forma                                                                                       | # origem |
        | pt     | o comando de medição de cobertura             | é o mesmo comando, com a mesma grafia, que CT-22 valida                                      | RQ-05    |
        | pt     | quando e onde medir                           | diz em que momento o mantenedor mede localmente e em que ponto o CI mede                     | RQ-05    |
        | pt     | o tempo medido da suíte com cobertura         | um número com unidade, pareado ao mesmo comando com cobertura que o produziu                 | RQ-04    |
        | pt     | a meta de cobertura                           | um número rotulado como meta, acompanhado da justificativa de por que é esse                 | RQ-06    |
        | pt     | o custo de manter o Xdebug carregado          | dois números com unidade — com e sem a extensão carregada — e a decisão que deles saiu       | RQ-12    |
        | pt     | o levantamento de níveis de qualidade         | por candidato, resposta às três perguntas de corte, e a separação entre adotado e roadmap    | RQ-13    |
        | en     | o comando de medição de cobertura             | idem pt                                                                                      | RQ-08    |
        | en     | quando e onde medir                           | idem pt                                                                                      | RQ-08    |
        | en     | o tempo medido da suíte com cobertura         | idem pt                                                                                      | RQ-08    |
        | en     | a meta de cobertura                           | idem pt                                                                                      | RQ-08    |
        | en     | o custo de manter o Xdebug carregado          | idem pt                                                                                      | RQ-08    |
        | en     | o levantamento de níveis de qualidade         | idem pt                                                                                      | RQ-08    |

    Cenário: [CT-22] o comando de medição documentado mede de verdade
      Dado o comando de medição de cobertura citado na documentação do kit
      Quando o mantenedor confronta esse comando com os alvos declarados do projeto
      Então ele existe declarado, com a mesma grafia
      E ele habilita a coleta de cobertura
      E ele grava o relatório no formato que o verificador de cobertura consegue ler
      E o recorte que ele mede é o mesmo que CT-04 e CT-05 verificam
```

**Notas de materialização.**
A coluna `forma` foi acrescentada depois da revisão adversarial, e é ela que impede o cenário de
degenerar em `str_contains` dentro de um recorte de seção. Na rodada 1, as exigências de "número com
unidade" e "três critérios de corte" moravam nas **notas de materialização** — que **não são
oráculo** — e o texto do `Esquema` pedia apenas "separando adotado de roadmap". Um documento que
listasse nomes de ferramentas, publicasse como "tempo com cobertura" o tempo da suíte **sem**
cobertura e citasse o número da meta numa tabela comparativa passava nas dez linhas.
Duas linhas novas por idioma: *quando e onde medir* (RQ-05 pede *"qual comando, **quando**, e
**onde**"*, e a rodada 1 só tinha oráculo para o "qual") e o pareamento do tempo ao comando **com**
cobertura.
**CT-22** fecha o laço que faltava — configuração → comando → relatório → verificador. A rodada 1
verificava só a **grafia do nome** do alvo: um alvo com o nome certo que não ligasse a coleta, ou
gravasse em outro formato, ou medisse outro recorte, passava.
Fonte: `documentacaoDoKit($idioma)` cobre README + `docs/{idioma}`, `paginasDoSite($idioma)` cobre o
site, `secoesDoMarkdown()` recorta a seção; `naArvoreDoKit()` protege o caso num projeto instalado,
onde `docs/` é `export-ignore` — **sem torná-lo auto-anulante**, porque o README continua sendo lido.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M44 *(revisão adversarial — não conta para o teto)* | o tempo publicado como "com cobertura" é, na verdade, o da suíte **sem** cobertura | CT-21 (linha do tempo, coluna `forma`) |
| M45 | o item aparece só numa linha de índice, de sumário ou de roadmap — citado, não explicado | CT-21 (`Então` de presença na seção) |
| M46 | o número da meta é documentado **sem** a justificativa, e RQ-06 ("escolhido **e** justificado") fica meio cumprida | CT-21 (linha da meta) |
| M47 *(revisão adversarial)* | o levantamento de RQ-13 é uma lista de nomes de ferramentas, sem as três perguntas de corte e sem separar adotado de roadmap | CT-21 (linha do levantamento) |
| M48 *(revisão adversarial)* | o comando documentado existe pelo nome e não liga a coleta, ou grava em formato que o verificador não lê, ou mede outro recorte | CT-22 |

---

## Regra R10 — o piso bloqueia no CI, por toda a cadeia que propaga o código de saída

> `RQ-06`, `RQ-07` · áreas **E** + **A** → herda **padrão** ·
> técnica: **EP por mecanismo de propagação** + **cenário fim-a-fim**
>
> **Estas são as células que derrubam RQ-07.**

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: a aplicação do piso derruba a integração contínua quando a cobertura fica abaixo da meta

    Cenário: [CT-23] nenhuma etapa da cadeia descarta o código de saída do verificador
      Dado a definição de integração contínua do kit
      Quando o mantenedor localiza o passo que aplica o piso de cobertura
      Então esse passo não está marcado como tolerante a erro
      E o job que o contém não está marcado como tolerante a erro
      E o comando desse passo não está em cadeia de tubulação nem dentro de invólucro que descarte o código de saída do processo interno
      E o fluxo de trabalho que contém esse passo dispara em pedido de integração

    Cenário: [CT-24] o piso é aplicado sobre um relatório produzido na mesma execução, pelo comando documentado
      Dado a definição de integração contínua do kit
      Quando o mantenedor compara o passo que mede a cobertura com o passo que aplica o piso
      Então os dois estão no mesmo job
      E o passo de medição é o mesmo alvo que CT-22 valida
      E o relatório que o piso consome é o que a medição acabou de produzir, e não um arquivo versionado na árvore

    Cenário: [CT-25] a cadeia completa do passo de CI sai com erro quando a cobertura fica abaixo da meta
      Dado um relatório de cobertura sintético cuja razão medida está a 0,01 ponto percentual abaixo da meta
      Quando o mantenedor executa, como subprocesso, a linha de comando completa que o passo de integração contínua aplica sobre esse relatório
      Então o código de saída do subprocesso é diferente de zero
```

**Notas de materialização.**
**CT-25 é novo e é o cenário mais forte do conjunto para RQ-07.** A rodada 1 acreditava que só restava
asserção sobre o **texto** do workflow; a revisão adversarial mostrou que um oráculo fim-a-fim
**barato** existe. Ele não é recursão (não roda a suíte sob coletor), usa a fixture que já existe, e
mata de uma vez as quatro formas de neutralização — tolerância no passo, tolerância no job, tubulação
sem propagação, invólucro que não repassa `%ERRORLEVEL%`/`$?`. Enquanto CT-23 enumera mecanismos
conhecidos, CT-25 **mede o resultado**, e por isso pega também os que ninguém enumerou.
CT-23 foi ampliado: a rodada 1 nomeava **duas** formas de neutralização e as tratava como a partição
inteira. As três novas cláusulas (job, tubulação/invólucro, gatilho) vieram da revisão — a do gatilho
fecha metade de M56, porque um fluxo que só dispara em `workflow_dispatch` torna o piso decorativo.
CT-23 é asserção de **ausência** sobre um arquivo que provavelmente **comenta** por que não usa
`|| true` — filtre comentário antes, mantendo a presença sobre o texto cru.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M49 | o passo recebe tolerância a erro para "destravar o PR" e nunca mais é revertido | CT-23, CT-25 |
| M50 *(revisão adversarial — não conta para o teto)* | a tolerância a erro é declarada no **job**, não no passo | CT-23, CT-25 |
| M51 | o comando ganha `\|\| true` / `\|\| exit 0` — o CI fica verde com a cobertura abaixo da meta | CT-23, CT-25 |
| M52 *(revisão adversarial)* | o comando entra numa tubulação sem propagação de erro, ou num invólucro `.cmd`/`.sh` que não repassa o código de saída | CT-23, CT-25 |
| M53 *(revisão adversarial)* | o fluxo de trabalho que contém o passo não dispara em pedido de integração | CT-23 (cláusula do gatilho) |
| M54 | o piso é aplicado sobre um relatório versionado na árvore, que ninguém regenera | CT-24 |
| M55 *(revisão adversarial)* | o passo de medição produz relatório com **outro recorte** ou de **outro subconjunto** do que o alvo documentado | CT-24 (cláusula do alvo) |
| M56 | o job existe, bloqueia e **não está entre os checks obrigatórios do PR** — o merge acontece por cima do vermelho | ⚠️ **lacuna reduzida, não fechada**. Tentado e recusado: inferir a obrigatoriedade do workflow — ele não a expressa. **Tentado e adotado** (fecha metade): o **gatilho** está na árvore e é assertável (CT-23). O que resta — a proteção de branch — vive na configuração do GitHub. Pergunta devolvida ao mantenedor |

---

## Regra R11 — o mutation score e o `--tia` ficam auditáveis e executáveis

> `RQ-13`, `RQ-14` · área **F** · perfil **padrão** ·
> técnica: **EP** (dependência direta / transitiva; score com evidência / sem evidência) +
> **arnês de subprocesso estreitado**

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: a métrica que o driver de cobertura destravou é declarada, executável e publicada com o que a torna auditável

    Cenário: [CT-26] o plugin de mutação é dependência direta e o comando de mutação é invocável
      Dado as dependências declaradas do kit
      Quando o mantenedor procura o plugin de mutação do Pest
      Então ele aparece entre as dependências de desenvolvimento declaradas, com restrição de versão explícita
      E a opção de mutação é reconhecida pelo executor de testes do projeto, e não só pelo pacote instalado

    Cenário: [CT-27] o mutation score publicado vem com a duração, a plataforma e os sobreviventes nomeados
      Dado a seção da documentação do kit que publica o mutation score
      Quando o mantenedor lê o número publicado
      Então na mesma seção constam a duração da execução que o produziu e a plataforma em que ela rodou
      E consta a contagem de mutantes sobreviventes
      E, quando há sobreviventes, cada um está nomeado por arquivo e linha

    Cenário: [CT-28] a análise de impacto de testes termina e grava o que promete
      Dado um único arquivo de teste do kit
      Quando o mantenedor executa, como subprocesso e com teto de tempo, o executor de testes em modo de análise de impacto sobre esse arquivo
      Então o subprocesso termina dentro do teto, com código de saída zero
      E o artefato de estado que a análise de impacto mantém foi criado ou atualizado
```

**Notas de materialização.**
CT-26 fecha uma armadilha que a própria skill nomeia: o plugin de mutação costuma existir em
`vendor/` como dependência **transitiva** do Pest 5 — o comando funciona **por acidente da árvore de
dependências** e desaparece num `composer update`, sem nada ficar vermelho. A cláusula da
**invocabilidade** veio da revisão adversarial: presença em `require-dev` passa com restrição `*`,
com versão não instalada, ou com o pacote listado e desabilitado.
**CT-27 foi reescrito.** A rodada 1 exigia "duração" e "sobreviventes" na seção — e o **100 % falso do
Windows** publicado como *206 mutantes / 3 s / 0 sobreviventes* satisfazia as duas cláusulas, que é
exatamente a assinatura que a nota descrevia como falsa. Agora o cenário exige a **plataforma** e os
sobreviventes **nomeados por arquivo e linha**: um score falso não tem o que nomear, e uma duração
incompatível com a contagem de mutantes fica visível ao lado da plataforma em que ocorreu.
**CT-28 é novo.** A rodada 1 declarava o `--tia` como lacuna, tendo recusado "rodar a suíte inteira
como subprocesso". A revisão adversarial lembrou que *impossibilidade de arnês é hipótese*: o
subprocesso **estreitado** — um único arquivo, com teto de tempo — é viável e prova o que importa
(termina, e grava seu estado). O custo em segundos é declarado, e o caso deve levar o teto de tempo
explícito para não virar flake.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M57 | o plugin some num `composer update` porque só existia como dependência transitiva | CT-26 |
| M58 *(revisão adversarial — não conta para o teto)* | o plugin é declarado com restrição frouxa, ou declarado e desabilitado: a opção não é reconhecida | CT-26 (2ª cláusula) |
| M59 | o `100 %` falso da plataforma do mantenedor é publicado como "qualidade atingida" | CT-27 |
| M60 | o `--tia` é documentado e, na prática, não termina ou não mantém estado | CT-28 |

---

## Checklist de Taxonomia

<!-- Resposta válida: um ID de cenário, "não se aplica: {motivo}", ou
     "lacuna declarada: {o que foi tentado}". NUNCA "sim". -->

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a feature não expõe rota nem ação que receba `{id}` de recurso; a única interface é CLI local e de CI |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: não há policy, permission, gate nem usuário autenticado nesta feature |
| Cenário por fora do componente de UI (gate de camada) | **não se aplica**: não há componente de UI; **todos** os 28 cenários já estão fora de qualquer componente |
| Idempotência (ancorada no agregado persistido) | CT-15 — **e só depois de CT-17 provar que o efeito acontece**; sem a direção positiva, a idempotência é satisfeita por um escritor que não escreve |
| Concorrência | **não se aplica** no sentido de contador/saldo: o verificador é processo único. **Ressalvas declaradas**: (a) dois jobs escrevendo o mesmo badge em paralelo — o requisito não pede badge por branch, e não foi derivado; (b) o análogo mais barato, **escrita parcial no caminho de erro**, não é concorrência e **tem** cenário: CT-12, CT-13, CT-14 |
| **Fronteira no ponto de entrada (gravação, não só uso)** | CT-10 — o piso é validado **onde é informado**, não só onde é comparado |
| **Criação ≠ edição ≠ uso** | **criação/edição do piso**: CT-10, CT-11. **uso do piso**: CT-06, CT-07, CT-08, CT-09. Declarado: o piso não tem ponto de "edição" próprio — é informado a cada execução, então criação e edição colapsam no mesmo ponto de entrada |
| **Domínio condicionado** (fronteira que muda com outro campo) | **não se aplica**: os dois parâmetros do verificador (relatório, piso) têm domínios independentes; nenhum discriminador muda a fronteira do outro |
| **Estado × operação de escrita** | **substituído pelo análogo, que existe e está fechado**: a matriz **estado da entrada × efeito no badge**, `6 estados × 1 operação = 6 células`, todas resolvidas — ver `## Matriz de entrada × efeito`. Declarar "não se aplica" aqui era o que deixava o mutante *"escreve o badge antes de validar"* (M32) atravessar |
| Ausente ≠ `null` ≠ vazio | CT-07 (piso **ausente** → default), CT-10 linha vazia (piso **vazio** → recusa), CT-12 (relatório **ausente** e **degenerado**), CT-14 (relatório **presente e vazio de conteúdo**) |
| Paginação | **não se aplica**: não há listagem |
| Ordenação por coluna | **não se aplica**: nenhuma entrada de usuário vira nome de coluna ou `orderBy` |
| Timezone / DST / virada de dia | **não se aplica**: o requisito trata de **duração** (RQ-04, RQ-12), não de instante comparado. Nenhum `Então` compara datas. A face temporal real é a **obsolescência do número publicado**, em M43 |
| **Texto livre — teto em (n, n+1) e normalização** | **aplica-se em dois campos, e os dois têm cenário**: o **piso** como string (CT-10: negativo, zero, não numérico, vazio, acima do domínio, com sufixo, com espaços nas bordas, notação científica, fracionário, borda superior) e o **caminho do relatório** (CT-12: inexistente, diretório em vez de arquivo, caminho com espaço). Foi este item que a rodada 1 dispensou como "entrada numérica" |
| Unicode / limite de `varchar` | **não se aplica**: nenhum campo persistido, nenhum limite de coluna |
| Unicidade + `SoftDeletes` | **não se aplica**: sem persistência, sem coluna única, sem exclusão lógica |
| CRUD combinado | **não se aplica**: o verificador não cria, edita nem exclui registro. O análogo — executar duas vezes — é CT-15 |
| Mass assignment | **substituído pelo análogo**: a **superfície de opções** do verificador e do passo de CI. CT-05 exige que nenhuma das quatro origens estreite o recorte; CT-10 exige que opção de piso fora do domínio recuse. É a tradução de "campo não previsto é ignorado" para uma interface de linha de comando |
| Upload | **não se aplica**: sem upload |
| Precisão numérica / representação | CT-06 (linhas `meta−0,004`, `78,40`, `78,50`) — a armadilha não é dinheiro, é **arredondar ou truncar a razão antes de comparar**; e CT-10 (`1e2`, `78%`, `78,5`), que é a mesma armadilha no ponto de entrada |
| **Superfície Livewire** (método público, prop pública, estado do framework) | **não se aplica**: a feature não cria página, widget nem componente Livewire. Nenhum `$filters`, `$pageFilters`, `$tableFilters` é consumido |
| **Estado do framework usado sem validar** | **não se aplica** pela mesma razão. **Ressalva**: a entrada não confiável desta feature é o **relatório**, tratada em CT-12 (caminho), CT-13 (não parseável) e CT-14 (métricas degeneradas) |
| **IDOR por entidade** (uma linha por tabela persistida) | **não se aplica**: **zero** tabelas persistidas por esta feature |
| Escopo com discriminante nulo (fecha ou abre?) | **substituído pelo análogo**: o **recorte sem declaração**. Uma configuração sem seção de fonte de cobertura *abre* — mede o projeto inteiro, `vendor/` junto. CT-04 exige, no `Dado`, que a seção **exista e não esteja vazia**; era essa ausência que fazia as cinco linhas `fora` passarem vacuamente |
| **Saída do estado de erro** (4xx/redirect/exit tem destino) | CT-16 (o que fazer depois de reprovar, **com o comando indicado existindo**), CT-12 e CT-13 (a mensagem separa "não medi" de "medi e deu baixo") |
| **Não-efeito em mundo com destinatário** | CT-11, CT-12, CT-13, CT-14, CT-16 — em **todos**, o `Dado` põe um badge de partida com valor **conhecido e diferente**; e CT-17 prova, na direção positiva, que o caminho feliz de fato o moveria |
| **Efeito na direção positiva** (entrada diferente → efeito diferente) | CT-17 — o item que faltava, e sem o qual todas as asserções de ausência acima eram satisfeitas por um escritor no-op |
| **Valor literal do requisito** | **não se aplica por construção**: o requisito deliberadamente não fixa número (RQ-06: *"medir primeiro, decidir depois"*). Substituído por CT-07 (default exercitado sem injeção) + CT-18 (default = documentado, extração ancorada) + CT-10 (o **tipo** e o **formato** do piso, que um default certo no número e errado na coerção atravessaria) |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | o PHP corrente tem com que coletar cobertura | R1 | EP | Kit | `tests/Kit/KitCoberturaTest.php` | M3 |
| CT-02 | coleta quem foi ligado naquela execução, e coleta de verdade | R1 | EP (Esquema, subprocesso) | Kit | idem | M4, M5 |
| CT-03 | as duas ficam desligadas, inclusive no trecho de ini documentado | R1 | EP | Kit | idem | M1, M2, M6 |
| CT-04 | a fonte é declarada, e cada diretório está dentro ou fora | R2 | EP (Esquema) | Kit | idem | M8, M9, M10 |
| CT-05 | todo subdiretório de `app` está no recorte, por qualquer via | R2 | normalização | Kit | idem | M11, M12, M13 |
| CT-06 | o piso é inclusivo e não arredonda nem trunca a favor | R3 | **BVA 3-valores +2** | Kit | idem | M14, M15, M16, M17, M27 |
| CT-07 | sem piso informado, vale a meta padrão | R3 | EP | Kit | idem | M18 |
| CT-08 | a razão é ponderada por tamanho, não a média por arquivo | R4 | partição por peso | Kit | idem | M19, M20 |
| CT-09 | a razão comparada é a de linhas | R4 | EP (`@premissa` de mecanismo) | Kit | idem | M21 |
| CT-10 | cada valor de piso é aceito ou recusado onde é informado | R5 | EP texto livre (Esquema, `@premissa`) | Kit | idem | M22, M23, M24, M25, M26, M27 |
| CT-11 | piso inválido nunca aprova medição abaixo da meta | R5 | invariante da premissa | Kit | idem | M22 |
| CT-12 | caminho que não é relatório recusa e não toca o badge | R6 | EP inválida (Esquema) | Kit | idem | M28, M31, M32 |
| CT-13 | relatório ilegível recusa e não toca o badge | R6 | EP inválida | Kit | idem | M30, M31, M32 |
| CT-14 | zero mensuráveis recusa, não vale 100 % e não toca o badge | R6 | EP inválida | Kit | idem | M29, M31, M32 |
| CT-15 | a 1ª verificação move o badge; a 2ª não move mais nada | R7 | rastreio de efeito | Kit | idem | M33, M34 |
| CT-16 | ao reprovar, diz o medido, o piso e um próximo passo que existe | R7 | rastreio de efeito | Kit | idem | M35, M36, M37 |
| CT-17 | o badge publicado é a razão do relatório verificado | R8 | rastreio na direção positiva (Esquema) | Kit | idem | M38 |
| CT-18 | a meta aplicada é a meta rotulada na seção em português | R8 | normalização | Kit | idem | M39 |
| CT-19 | a documentação en declara os mesmos quatro números | R8 | normalização | Kit | idem | M40, M41 |
| CT-20 | o badge aponta para alvo existente e não contradiz a meta | R8 | normalização | Kit | idem | M42 |
| CT-21 | cada item obrigatório está na forma exigida, nos dois idiomas | R9 | rastreio × EP (Esquema, `@premissa`) | Kit | idem | M44, M45, M46, M47 |
| CT-22 | o comando de medição documentado mede de verdade | R9 | rastreio de efeito | Kit | idem | M48 |
| CT-23 | nenhuma etapa da cadeia descarta o código de saída | R10 | EP por mecanismo | Kit | idem | M49, M50, M51, M52, M53 |
| CT-24 | o piso é aplicado sobre relatório da mesma execução | R10 | EP | Kit | idem | M54, M55 |
| CT-25 | a cadeia completa sai com erro abaixo da meta | R10 | fim-a-fim (subprocesso) | Kit | idem | M49, M50, M51, M52 |
| CT-26 | o plugin de mutação é direto e o comando é invocável | R11 | EP | Kit | idem | M57, M58 |
| CT-27 | o score publicado vem com duração, plataforma e sobreviventes | R11 | EP | Kit | idem | M59 |
| CT-28 | a análise de impacto termina e grava o que promete | R11 | EP (subprocesso estreitado) | Kit | idem | M60 |

**Sem matador**: M7 (Xdebug ausente — **reduzida** por CT-03), M43 (badge defasado entre execuções do
CI — **reduzida** por CT-17 e CT-24), M56 (job não obrigatório no PR — **reduzida** por CT-23). Três
lacunas, todas com o que foi tentado registrado na tabela da sua regra e duas com pergunta devolvida
ao `00`.

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| "a suíte roda com cobertura e produz um relatório" | rodar a suíte inteira sob coletor de dentro da própria suíte é recursão; a capacidade é provada por CT-02 e a execução é evidência do `03` |
| "a cobertura medida do kit é ≥ a meta" (RQ-07 literal) | não falsificável sem recursão; a variante que lê um relatório gravado mede o artefato. Substituída por CT-25, que é o fim-a-fim sem recursão |
| "o tempo de execução com cobertura é menor que N" | o requisito pede **medir e registrar** (RQ-04), não um teto; um teto inventado seria oráculo fabricado, e o número varia com a máquina |
| "carregar o Xdebug custa menos de X %" | idem RQ-12. O invariante que sobrou (carregado e desligado, inclusive na receita de ini) é CT-03 |
| "existem N níveis de qualidade levantados" | contar itens não distingue levantamento de lista de nomes; CT-21 exige as **três perguntas de corte por candidato**, que é o que discrimina |
| "o comando de cobertura termina em menos de T segundos" | não mata mutante que outro cenário não mate, e introduz flake por máquina |
| "o relatório produzido contém apenas arquivos de `app/`" | exigiria um relatório real produzido por uma medição completa; CT-04 e CT-05 provam o mesmo pelo recorte efetivo, mais barato e sem depender de artefato |
| "o badge renderiza no GitHub" | depende de serviço de terceiro; CT-20 prova a metade verificável (o alvo existe) e CT-17 prova o valor |
| "dois jobs escrevendo o badge em paralelo" | o requisito não pede badge por branch; registrado como ressalva no checklist, não como cenário |
| "a cobertura de branch tem piso próprio" | o requisito instala o Xdebug (RQ-11) e **não pede** piso de branch; inventá-lo seria oráculo fabricado. Registrado como lacuna vinculada à pergunta sobre a unidade da cobertura |

---

## Sem CT-B

**Não existe `05-casos-de-teste-browser.md` para esta feature.**

O gate do `05` exige (a) linha em `## Superfície de UI` e (b) asserção sobre algo que **só o navegador
prova**. **Nenhuma das duas passa**:

- **(a)** A feature não cria página, widget, componente Livewire, rota, model nem tela. A varredura
  SFDIPOT registra, na dimensão **I**, que a única interface é a **linha de comando** — o comando de
  verificação, o alvo do projeto e o job de CI. Não há superfície de UI a listar.
- **(b)** Nenhuma asserção deste conjunto depende de JavaScript executado, de console, de
  acessibilidade, de cor ou de layout. A única coisa que se aproxima de "visual" é o **badge do
  README (RQ-09)** — e ele é renderizado pelo **GitHub**, não pela aplicação. O `pest-plugin-browser`
  sobe o **próprio servidor da aplicação**; ele não alcança `github.com`, e um cenário que fosse até
  lá seria teste de disponibilidade de serviço de terceiro, não do kit. As partes verificáveis de
  RQ-09 — *o badge aponta para um alvo que existe*, *exibe número coerente com a meta* e *acompanha a
  medição* — são provadas em **CT-20** e **CT-17**, na camada mais barata.

---

## Revisão Adversarial

**Gatilho da skill**: não disparado — nenhuma área tem perfil **completo** e nenhuma tem **Impacto 3**
(a tabela do Passo 0 reserva I=3 para dinheiro, dado de terceiro, autorização, irreversibilidade e
compliance; nada disso existe aqui).

**Executada assim mesmo**, por sub-agente independente (`fw-adversario-ct`, `opus`), com entrada
restrita a `00-requisito.md` + a rodada 1 deste `04`. Motivo do gasto extra: o conjunto é composto
quase todo por asserções sobre **artefato** (configuração, documentação, workflow), a classe de
oráculo mais fácil de escrever fraco sem perceber — e o requisito é sobre **medir qualidade**, onde um
falso ✅ custa mais do que o P×I sugere. **A decisão se pagou**: a rodada 1 tinha 25 cenários e três
eixos inteiros tautológicos.

### O que a rodada produziu, e o que virou cada achado

| Achado | Disposição |
|---|---|
| **8 implementações erradas** que passavam por todos os 25 cenários da rodada 1 | 3 cenários **novos** (CT-17, CT-25, CT-28), 11 cenários com **oráculo reescrito**, 1 regra **desdobrada** (o piso virou R3 + R4) |
| CT-10 com fixture abaixo da meta: "recusa" ≡ "reprova" em 3 das 6 linhas | fixture trocada para 100 % coberto; invariante extraído para CT-11 |
| CT-07 (agora CT-08) com arquivos de **mesmo tamanho**: média ≡ agregado por construção | fixture 10/990, agregado 50,5 % × média 75 %, piso 60 |
| CT-06 não separava `round()` de `floor()` com meta inteira | duas linhas de **piso fracionário** acrescentadas |
| CT-04/CT-05 não pegavam estreitamento por `include`, por ini do driver ou por flag de comando | CT-05 reescrito como **igualdade de conjuntos** sobre quatro origens; CT-04 ganhou "a seção existe" |
| CT-15 provava idempotência num escritor **no-op** | **CT-17 criado** (direção positiva); CT-15 passou a exigir que o badge **tenha mudado** |
| CT-19 (`badge ≥ meta`) tautológico em badge estático/clamped | coberto por CT-17; CT-20 deixou de ser a única defesa |
| CT-12/13/14 não afirmavam nada sobre o badge | não-efeito acrescentado aos três, com badge de partida no `Dado`; **matriz de entrada × efeito** criada |
| CT-22 nomeava só 2 dos 5 mecanismos de neutralização | 3 cláusulas novas + **CT-25 criado** (fim-a-fim) |
| CT-20/CT-21 com exigências reais nas **notas**, não no oráculo | coluna `forma` acrescentada ao `Esquema`; **CT-22 criado** (o comando mede de verdade) |
| CT-25 (agora CT-27) satisfeito pelo próprio 100 % falso | passou a exigir plataforma e sobreviventes **nomeados por arquivo e linha** |
| CT-01 provava "não lançou", não "coletou" | cláusula de linhas cobertas > 0 acrescentada a CT-02 |
| 4 lacunas declaradas reatacadas com arnês não tentado | M43 e M56 **reduzidas**; M7 **reduzida**; a do `--tia` **fechada** por CT-28 |
| Ataque ao `## Achado: RQ-07` | aceito: o argumento estava certo na forma e errado na conclusão prática. Seção reescrita, CT-25 criado |
| 6 linhas do checklist dispensadas rápido demais | *estado × operação*, *escopo com discriminante nulo*, *mass assignment* e *texto livre* reabertas com o análogo desta feature; *concorrência* com ressalva escrita |
| 4 contradições internas (índice creditando mutantes que o cenário não matava; "Cogitado e Cortado" afirmando exigência que o `Esquema` não fazia) | todas corrigidas |
| Itens 5 e 6 do contrato (pares de papéis, recortes de visibilidade) | **sem objeto**, confirmado pelo revisor: o `00` não define papel algum e a feature não persiste nada |

**Áreas e regras percorridas pela revisão**: A, B, C, D, E, F; R1…R10 da rodada 1; CT-01…CT-25,
incluindo cada linha dos quatro `Esquema`; RQ-01…RQ-14; e todas as seções meta do arquivo.

### Rodada 2: devida e não executada

A skill manda re-revisar **uma vez** quando o fechamento cria **cenário novo** — e ele criou três
(CT-17, CT-25, CT-28), além de um desdobramento de regra. **A rodada 2 não foi executada**, e a razão
é declarada em vez de omitida: o gatilho da revisão adversarial nunca foi obrigatório nesta feature
(sem perfil completo, sem Impacto 3), e a rodada 1 já foi gasto voluntário. **Fica registrada como
pendência para o orquestrador**, com a superfície nova a atacar nomeada: CT-17 (o oráculo do badge
depende do formato em que o percentual é escrito no artefato — um badge SVG com o número em dois
lugares tem dois pontos de divergência), CT-25 (o subprocesso herda o ambiente do teste, e um
`continue-on-error` que só existe no runner não é reproduzido localmente) e CT-28 (teto de tempo é
fonte clássica de flake em CI compartilhado).

---

## RQ sem nenhum cenário automatizável

Lista fechada, com o motivo de cada uma. Estas cláusulas **não** ficaram sem tratamento: cada uma tem
um **resíduo verificável** (coluna da direita) e uma parte que só um humano confere.

| `RQ` | O que só um humano / uma execução confere | Resíduo automatizado |
|---|---|---|
| **RQ-03 / RQ-10** | que o driver foi instalado **no PHP do mantenedor** — a suíte só sabe do PHP em que ela mesma roda | CT-01 |
| **RQ-04** | o **ato** de medir o tempo da suíte com cobertura | CT-21 (o número medido, com unidade, **pareado ao comando com cobertura**) |
| **RQ-05** | que a forma escolhida é *a melhor* — juízo, não oráculo | CT-22 (o comando documentado existe, liga a coleta e grava o formato lido), CT-21 (o quando/onde), CT-16 |
| **RQ-07** | que "atingir" seja um estado demonstrado uma vez — se a leitura for essa, é evidência do `03`, não cenário | CT-25 (a cadeia de CI sai com erro abaixo da meta), CT-23, CT-24 |
| **RQ-11** | que o Xdebug esteja instalado no PHP **local** — CT-02/CT-03 são condicionais à presença dele (M7) | CT-02, CT-03 (incluindo o trecho de ini documentado, que nomeia a extensão) |
| **RQ-12** | o **ato** de medir o custo de carregar o Xdebug, com e sem | CT-03 (o invariante que a decisão produziu, nas duas fontes), CT-21 (os dois números registrados) |
| **RQ-13** | o **juízo** de valor por esforço sobre cada candidato | CT-21 item do levantamento: **por candidato**, resposta às três perguntas de corte, com adotado separado de roadmap |
| **RQ-06** (parcial) | a escolha do número em si | tudo o mais: R3, R4, R5, R8, R10 |

**Uma cláusula ficou sem resíduo próprio: RQ-07.** Ela não tem oráculo que a separe de RQ-06 com o
piso aplicado — e é por isso que a pergunta de fusão foi devolvida ao `00`.

**Em uma frase**: as cláusulas de **processo** (instalar, medir, pesquisar, decidir) não viram
cenário — vira cenário **o invariante que a decisão deixou para trás** e **o registro auditável do
número**. As que não deixaram nem invariante nem registro são as três lacunas declaradas (M7, M43,
M56), todas reduzidas pela revisão adversarial e nenhuma fechada.

---

## Reconciliação com os testes existentes

<!-- Escrita DEPOIS de a derivação fechar. A cegueira cumpriu o papel dela; ler a implementação
     aqui não contamina mais nada. Lidos nesta etapa: app/Console/Commands/KitCobertura.php,
     tests/Kit/KitCoberturaTest.php, tests/Kit/PoliciesTest.php, tests/Kit/ArquiteturaDoCodigoTest.php,
     tests/Kit/SiteDeDocumentacaoTest.php, .github/workflows/ci.yml, composer.json, pestw.cmd,
     phpunit.xml, docs/{pt,en}/referencia/qualidade-de-codigo.md. -->

### (a) Colisão de IDs — decisão

Hoje `[CT-01]` significa **três** coisas no repositório: *"aprova quando a cobertura está acima do
piso"* (`KitCoberturaTest`), *"toda policy confere a permissão do próprio modelo"* (`PoliciesTest`)
e, desde esta derivação, *"o PHP corrente tem com que coletar cobertura"*. Os três arquivos
pertencem a **esta mesma pasta de wiki** — `PoliciesTest.php` nasceu nesta branch como instrumento
do RQ-07 (subir a cobertura de `app/Policies`, que a documentação mede a 23 %) —, então não é
colisão entre wikis, que o par *(arquivo de teste, pasta da wiki)* resolveria. É colisão **dentro de
um namespace só**.

**Decidido: o `04` é a fonte, e os IDs dele valem. As duas exceções são nomeadas.**

| Arquivo | Decisão | Por quê |
|---|---|---|
| `tests/Kit/KitCoberturaTest.php` | **renumerar para os IDs deste `04`**, pelo mapa de (c) | Seus `[CT-01]`..`[CT-12]` foram escritos **a partir do comando**, sem cenário no `04` — é a Proibição 11 da skill (*"não escrever teste `[CT-nn]` sem o cenário no `04`"*), e o cabeçalho do próprio arquivo o confirma: ele nasceu porque *"os caminhos de saída foram verificados à mão e a verificação não foi versionada"*. Renumerar é o custo de trazer esses casos para dentro da rastreabilidade, e quatro deles viram **cenário novo** no `04` (CT-29…CT-32), não o contrário |
| `tests/Kit/PoliciesTest.php` | **prefixo `CT-P##`** — `[CT-01]`→`[CT-P01]`, `[CT-03]`→`[CT-P03]`, `[CT-04]`→`[CT-P04]` | Esses casos não testam a feature de cobertura: testam **policies**. Eles entraram nesta wiki como *instrumento* do RQ-07 (o meio de subir o número), não como *oráculo* dele. Prefixo em vez de renumeração porque fundi-los na sequência CT-01…CT-32 misturaria dois assuntos num índice só — e, no dia em que `app/Policies` ganhar wiki própria, o bloco `CT-P` migra inteiro sem tocar em nada daqui. **Cuidado na execução**: o `[CT-03]` daquele arquivo **confere o dataset do `[CT-01]` por `grep` no próprio código** (`PoliciesTest.php:187-209`) — renomear sem ajustar a expectativa `toHaveCount(2, 'não achei o dataset do [CT-01] neste arquivo')` derruba o caso |
| `tests/Kit/SiteDeDocumentacaoTest.php` | **nenhuma mudança** | `[CT-49]` e `[CT-50]` pertencem à wiki `site-de-documentacao`. Não colidem com nada deste `04`, que vai até `CT-32`. `[CT-49]` é referenciado aqui como cobertura **parcial** de CT-19 e CT-20, e a referência é cruzada, não uma reivindicação de posse |
| `tests/Kit/ArquiteturaDoCodigoTest.php` | **nenhum ID a mover** | Não usa `[CT-nn]`. Cita esta wiki em prosa (`ADR-05`) e seu `it()` de exceção de segurança não é desta feature. **Achado menor**: caso sem ID é caso fora de toda matriz de rastreabilidade — nos dois sentidos |

**O que não pode ficar**, e é o que esta decisão remove: o mesmo `[CT-01]` significando três coisas,
e doze IDs existindo só no arquivo de teste.

### Cenários que a implementação revelou e o `04` não tinha

A skill manda: *cenário descoberto na implementação nasce no `04` antes de virar teste*. Quatro
casos de `KitCoberturaTest` provam comportamento real que nenhuma regra minha previa — todos sobre
o **mecanismo do badge**, que a derivação às cegas tratou como "o badge exibe a razão". Eles entram
em **R8**, e sobem a contagem do conjunto de 28 para **32 cenários**.

```gherkin
# language: pt
Funcionalidade: Cobertura de testes medida, com meta e badge

  Regra: o badge publica a medição corrente, e a meta é um número só nos quatro artefatos

    Esquema do Cenário: [CT-29] o badge guarda o inteiro truncado e a cor da faixa
      Dado um relatório de cobertura cuja razão medida é <razao>
      Quando o mantenedor executa o verificador escrevendo o badge
      Então o badge exibe "<mensagem>" e a cor "<cor>"

      Exemplos:
        | razao | mensagem | cor         | # borda                                   |
        | 79,99 | 79%      | green       | trunca, não arredonda — nunca anuncia mais |
        | 80,00 | 80%      | brightgreen | borda da faixa superior                    |
        | 70,00 | 70%      | green       | borda                                      |
        | 60,00 | 60%      | yellow      | borda                                      |
        | 59,99 | 59%      | red         | borda−1 da faixa                           |

    Cenário: [CT-30] conferindo, o badge reformatado passa e o badge que mente reprova
      Dado um badge cujo conteúdo é o mesmo valor reindentado e com as chaves reordenadas
      Quando o mantenedor executa o verificador sem escrever o badge
      Então o verificador aprova
      E, com um badge de percentual ou de cor diferentes, ele reprova nomeando o commitado e o medido

    Cenário: [CT-31] fora da árvore do kit não há badge, e o piso continua valendo
      Dado um projeto instalado, sem o diretório de badges do kit
      Quando o mantenedor executa o verificador com um piso, sobre um relatório acima e outro abaixo dele
      Então o veredito segue só o piso
      E nenhum arquivo de badge é criado

    Cenário: [CT-32] escrever o badge substitui o anterior sem reclamar dele
      Dado um badge de partida exibindo 12 por cento
      Quando o mantenedor executa o verificador escrevendo o badge sobre um relatório de 79 por cento
      Então o verificador aprova
      E o badge passa a exibir 79 por cento
```

**Mutantes que eles matam** (acréscimo à tabela de R8): **M61** o badge **arredonda** em vez de
truncar, e anuncia mais cobertura do que existe → CT-29 (linha `79,99`); **M62** as faixas de cor
saem trocadas ou com a borda errada → CT-29 (quatro bordas); **M63** a conferência compara **bytes**
e reprova por reindentação, com a mensagem autocontraditória *"o commitado diz 79 % e o medido é
79 %"* → CT-30; **M64** num projeto instalado o comando cobra um badge que nunca foi dele → CT-31;
**M65** o caminho de escrita perde o `return` e cai na conferência logo depois de gravar → CT-32.

**Um deles é achado de escopo, não só de teste**: CT-31 é o **único** cenário do conjunto que
distingue *a árvore do kit* de *um projeto instalado* — uma dimensão que o `00-requisito.md` não
menciona em cláusula nenhuma, e que só aparece porque `.github/` é `export-ignore`. Ela merece uma
linha no `00`, já que decide o comportamento de RQ-09 para todo mundo que não é o mantenedor.

### (b) Cada CT deste `04` → existe teste?

Critério duro: **teste que afirma menos do que o meu `Então` é parcial, não coberto.**

| CT | Situação | Detalhe |
|---|---|---|
| CT-01 | **SEM TESTE** | nenhum caso toca `extension_loaded` nem o inventário de drivers |
| CT-02 | **SEM TESTE** | nenhum arnês de subprocesso; a seleção PCOV × Xdebug nunca é exercitada |
| CT-03 | **SEM TESTE** | `pcov.enabled`/`xdebug.mode` não são lidos em lugar nenhum da suíte |
| CT-04 | **SEM TESTE** | `phpunit.xml:42-46` declara `<source><include><directory>app</directory>`, e **nada** afirma isso |
| CT-05 | **SEM TESTE** | nenhuma guarda sobre as quatro origens de estreitamento |
| CT-06 | **parcialmente coberto** — `KitCoberturaTest::[CT-03]` (`7799/7800/7801` sobre `10 000`, piso `78`) | falta a linha de **arredondamento** (`meta−0,004`), faltam as linhas de **piso fracionário** (`--min=78.5` × razão `78,40`) e falta o `E a razão exibida é exatamente <razao>` — o caso existente assere só o veredito |
| CT-07 | **DIVERGE — o cenário é que está errado** | `KitCoberturaTest::[CT-05]` prova o contrário e está certo: sem `--min`, `KitCobertura::pisoPedido` devolve `null` e **não existe piso padrão**. Ver o achado #1 de (4): o `78` mora, literal, em `composer.json` e em `ci.yml:201`. **CT-07 precisa ser reescrito** para *"sem piso informado o comando mede e não reprova"*, e a função anti-default-errado migra inteira para CT-18 |
| CT-08 | **SEM TESTE** | risco baixo por construção — o comando lê `<project><metrics>`, que já é agregado; um mutante de média por arquivo não é expressável nesta redação. Fica como guarda de regressão |
| CT-09 | **SEM TESTE** | **risco real**: o comando lê `statements`/`coveredstatements`, e o Clover também traz `elements`/`coveredelements`. Todas as fixtures existentes só emitem `statements`, então trocar o atributo não fica vermelho |
| CT-10 | **parcialmente coberto** — `KitCoberturaTest::[CT-04]` (`abc`, vazio, `-1`, `101`, com mensagem) | faltam seis linhas: `0` (**aceito** pela implementação — `pisoPedido` valida `0 <= piso <= 100`; contraria a premissa de falha fechado), `100` (borda superior), `78%`, `" 78 "` e `1e2` (os dois últimos são aceitos por `is_numeric` e **normalizados**, não recusados — o `Então` do CT admite, a coluna do `Esquema` não), e `78.5` aceito. **Correção de notação**: o `Esquema` escreve `78,5` em notação pt-BR do *valor*; a opção de linha de comando usa ponto (`--min=78.5`), e é essa a grafia a materializar |
| CT-11 | **parcialmente coberto** — `KitCoberturaTest::[CT-04]` | a fixture é `1/100`, **abaixo** do piso: "recusa o piso" e "reprova a cobertura" produzem o mesmo código de saída. Só a asserção de **mensagem** salva o caso. Falta a fixture 100 % coberta que discrimina, e falta a cláusula do badge |
| CT-12 | **parcialmente coberto** — `KitCoberturaTest::[CT-06]`, linha `ausente` | só `assertFailed()`. Faltam: a mensagem nomeando o caminho, as linhas `diretório em vez de arquivo` e `caminho com espaço`, e a cláusula do badge intocado |
| CT-13 | **parcialmente coberto** — `KitCoberturaTest::[CT-06]`, linhas `ilegivel` e `sem metricas` | só `assertFailed()`. O comando tem **três mensagens distintas** (`Clover ilegível`, `sem <project><metrics>`, `zero statements`) e nenhuma é afirmada — apagar as três deixa a suíte verde |
| CT-14 | **parcialmente coberto** — `KitCoberturaTest::[CT-06]`, linha `zero statements` | falta `E a saída não exibe "100"`, que é o oráculo do mutante `0/0 → 100 %`, e falta a cláusula do badge |
| CT-15 | **SEM TESTE** | `[CT-12]` existente prova que `--write` substitui, mas **não** a segunda execução, nem que o relatório fica intacto |
| CT-16 | **parcialmente coberto** — `KitCoberturaTest::[CT-10]` (o piso citado como pedido, `79.9` e não `80`) | faltam: a **razão medida** na saída (ela sai por `twoColumnDetail` e ninguém a assere) e a verificação de que o comando indicado como próximo passo **existe** — hoje a mensagem manda rodar `composer test:coverage`, e nada trava esse alvo |
| CT-17 | **coberto** — `KitCoberturaTest::[CT-07]` | o badge acompanha a medição, nas cinco faixas. Mecanismo detalhado promovido a CT-29 |
| CT-18 | **SEM TESTE** | **a lacuna mais grave do conjunto** — ver (4), achado #1 |
| CT-19 | **parcialmente coberto** — `SiteDeDocumentacaoTest::[CT-49]` | trava **um** número (o percentual inteiro) em README pt, README en, `docs/pt` e `docs/en`. Faltam os outros três que o cenário exige: a **meta** (78), o **tempo medido** (25 min local / 52 min no runner) e o **custo do Xdebug** (+44 %) — e o comando |
| CT-20 | **parcialmente coberto** — `SiteDeDocumentacaoTest::[CT-49]` | garante o formato `\d{1,3}%` e a igualdade com os READMEs. **Não** garante que o alvo do badge exista, nem que o percentual seja ≥ a meta |
| CT-21 | **SEM TESTE** | nenhuma guarda de conteúdo sobre a seção de cobertura. E ver (4) #10: o item do **levantamento de níveis de qualidade** não existe no texto |
| CT-22 | **SEM TESTE** | nada confronta o comando da documentação (`docs/.../qualidade-de-codigo.md:173-177`) com `composer.json` e com `ci.yml:199-201` |
| CT-23 | **SEM TESTE — e falharia hoje** | ver (4), achado #2: `ci.yml:155` traz `if: github.event_name != 'pull_request'` |
| CT-24 | **SEM TESTE** | é verdade no `ci.yml` (medição e verificação no mesmo `run`, sobre `cobertura.xml` recém-gerado), e nada trava |
| CT-25 | **SEM TESTE** | nenhum arnês fim-a-fim |
| CT-26 | **SEM TESTE** | `pestphp/pest-plugin-mutate` está em `require-dev` com `^5.0`, e nada trava |
| CT-27 | **SEM TESTE** | ver (4) #11: **nenhum score é publicado** |
| CT-28 | **SEM TESTE** | nenhuma guarda sobre o `--tia` |
| CT-29…CT-32 | **cobertos** — `KitCoberturaTest::[CT-07]`, `[CT-08]`+`[CT-11]`, `[CT-09]`, `[CT-12]` | nasceram da implementação; entram no `04` para deixarem de existir só no arquivo de teste |

**Resumo**: de 32 cenários — **5 cobertos**, **8 parcialmente cobertos**, **18 sem teste**, **1
divergente** (CT-07, cujo cenário é que precisa mudar).

### (c) Cada teste existente → existe CT?

| Teste | CT deste `04` | Leitura |
|---|---|---|
| `KitCoberturaTest::[CT-01]` aprova acima do piso | **CT-06**, linha acima | redundante com o `[CT-03]` do mesmo arquivo; candidato a fusão na renumeração |
| `KitCoberturaTest::[CT-02]` reprova abaixo | **CT-06**, linha abaixo | idem |
| `KitCoberturaTest::[CT-03]` piso inclusivo | **CT-06** | é o núcleo do BVA; renumera para `[CT-06]` e recebe as três linhas que faltam |
| `KitCoberturaTest::[CT-04]` `--min` ilegível | **CT-10** + **CT-11** | renumera para `[CT-10]`; a fixture muda para 100 % coberta e o invariante vira `[CT-11]` |
| `KitCoberturaTest::[CT-05]` sem `--min` não aplica piso | **CT-07, depois de corrigido** | **teste sem requisito por trás**: *"modo só me diga o número"* é decisão do plano, não cláusula do `00`. É legítimo e deve ficar — mas foi ele que expôs que **meu CT-07 assumiu um default que não existe**. O achado real está em (4) #1 |
| `KitCoberturaTest::[CT-06]` relatório indomável | **CT-12, CT-13, CT-14** | um caso com quatro linhas vira três cenários, porque os `Então` divergem (mensagem do caminho × mensagem de leitura × não exibir `100`) |
| `KitCoberturaTest::[CT-07]` badge truncado + cores | **CT-17** + **CT-29** | |
| `KitCoberturaTest::[CT-08]` badge por valor | **CT-30** | |
| `KitCoberturaTest::[CT-09]` fora da árvore do kit | **CT-31** | **cenário sem RQ**: a dimensão *árvore do kit × projeto instalado* não está no `00`. Achado de especificação, não de teste |
| `KitCoberturaTest::[CT-10]` piso como pedido | **CT-16** | |
| `KitCoberturaTest::[CT-11]` mensagem de badge divergente | **CT-30** | |
| `KitCoberturaTest::[CT-12]` `--write` substitui | **CT-32** | |
| `PoliciesTest::[CT-01]`, `[CT-03]`, `[CT-04]` | **nenhum** | **instrumento do RQ-07, não oráculo dele**: existem para elevar a cobertura de `app/Policies` (23 % na medição documentada). Vão para o prefixo `CT-P##`, e a wiki de policies é quem deve derivá-los do requisito próprio — hoje eles não têm nenhum |
| `SiteDeDocumentacaoTest::[CT-49]` | **CT-19**, **CT-20** (parcial) | pertence a outra wiki; referência cruzada |
| `SiteDeDocumentacaoTest::[CT-50]` | **nenhum** | badges de casos de teste e de PHPStan — outra feature, corretamente fora daqui |
| `ArquiteturaDoCodigoTest` (`it` sem ID) | **nenhum** | caso sem `[CT-nn]` fica fora da rastreabilidade nos dois sentidos. Achado menor, comum a todo o `tests/Kit/` |

**Dois testes sem requisito por trás** (`[CT-05]` e `[CT-09]` de `KitCoberturaTest`), e nenhum deles
é ruído: os dois descrevem decisões de **entregabilidade** do comando (medir sem cobrar; degradar
fora da árvore do kit) que o `00-requisito.md` nunca enunciou. Viram CT-07 corrigido e CT-31, e o
`00` ganha a pergunta sobre a dimensão *kit × projeto instalado*.

---

## O que o requisito pede e nenhum teste cobre hoje

Ordenado por risco. Uma linha por item dizendo o que escrever para fechar.

| # | `RQ` | O buraco | Fechar com |
|---|---|---|---|
| **1** | RQ-06, RQ-07 | **O piso `78` não está ligado a nada.** Ele é literal em `composer.json` (`test:coverage`) e em `.github/workflows/ci.yml:201` — escrito à mão nos **dois** —, e o `78 %` da documentação é prosa. Baixar o `--min` para 70, ou apagar a segunda linha do `run:`, **não deixa nada vermelho**. O próprio docblock de `KitCobertura::pisoPedido` já registra que *"o `--min` está escrito à mão em dois lugares, que é exatamente onde erro de digitação mora"* — a guarda foi feita contra o valor **ilegível** e não contra o valor **trocado** | **CT-18**: extrair o número rotulado como meta na seção de cobertura de `docs/pt` e exigir que ele seja o mesmo que aparece no `--min` do `composer.json` e do `ci.yml` |
| **2** | RQ-07 | **O gate não roda em pull request.** `ci.yml:155`: `if: github.event_name != 'pull_request'`. A justificativa é boa e está escrita (52 min no runner × 6 min da suíte paralela), mas a consequência não está: **a cobertura de uma PR nunca é verificada antes do merge**. RQ-07 como invariante fica aberto entre o merge e o próximo push em `main`. É achado de **especificação**, não de teste — CT-23 reprovaria hoje, e reprovar seria correto | Decisão do mantenedor primeiro. Se a exceção fica, o `00` ganha a cláusula e **CT-23** perde a linha do gatilho, mantendo as quatro de propagação do código de saída. Se não fica, um job leve em PR medindo só `--testsuite=Kit` |
| **3** | RQ-06 | **`--min=0` é aceito.** `pisoPedido` valida `0 <= piso <= 100`, então `--min=0` passa e o comando imprime *"Badge confere e o piso foi respeitado"*. É a mesma classe do `--min=abc` que a revisão de diff pegou (RD-02), pela porta que ficou aberta ao lado | **CT-10**, linha `0` — com a premissa de falha fechado do `00`. Se o mantenedor quiser `0` válido, a linha inverte e a decisão fica registrada |
| **4** | RQ-01, RQ-02 | **O recorte medido não tem guarda nenhuma.** `phpunit.xml:42-46` é a única coisa que define o denominador, e nenhum teste o afirma. Trocar `<directory>app</directory>` por `app/Models`, ou acrescentar um `<exclude>`, **sobe a cobertura** e passa por todo o CI | **CT-04** (a seção existe; cada diretório dentro/fora) e **CT-05** (igualdade de conjuntos entre os subdiretórios de `app/` e o recorte efetivo). São os dois cenários mais baratos de escrever da lista |
| **5** | RQ-01, RQ-06 | **As três mensagens de recusa de relatório não são asseridas.** `[CT-06]` existente só faz `assertFailed()`; o comando distingue *"não encontrado"*, *"ilegível"*, *"sem `<project><metrics>`"* e *"zero statements"*, e apagar as quatro deixa a suíte verde. É o que separa *"a cobertura caiu"* de *"o relatório sumiu"* — e confundir os dois é o que leva alguém a baixar o piso | **CT-12**, **CT-13**, **CT-14**: `expectsOutputToContain` por mensagem, e `E a saída não exibe "100"` em CT-14 |
| **6** | RQ-09 | **O badge não é afirmado como intocado no caminho de erro.** O comando faz certo hoje (sai por `INVALID` antes de `cuidarDoBadge`), e nada trava. O mutante *"escreve o badge antes de validar"* zeraria o badge num CI com relatório vazio | cláusula `E o artefato de badge continua exibindo 42 por cento` em **CT-12/13/14**, com badge de partida no `Dado` |
| **7** | RQ-09 | **O alvo do badge do README não é verificado.** `[CT-49]` trava o **número** nos quatro arquivos; ninguém verifica que o badge do README aponta para algo que existe, nem que o percentual é ≥ a meta | **CT-20** |
| **8** | RQ-06 | **A unidade da razão pode mudar sem nada vermelho.** O comando lê `statements`/`coveredstatements`; o Clover também traz `elements`/`coveredelements`, e todas as fixtures existentes só emitem o primeiro par | **CT-09**: fixture com as duas razões divergentes (linhas 60 %, elementos 90 %), piso 80, exigindo reprovação |
| **9** | RQ-03, RQ-10, RQ-11, RQ-12 | **Nada na suíte verifica o ambiente de driver.** Nenhum caso toca PCOV ou Xdebug. As quatro cláusulas de ambiente do Adendo 1 vivem só como evidência de sessão. O invariante mais valioso — *carregado e desligado* — é o que impede a suíte do dia a dia de pagar os **+44 %** que a própria documentação mediu | **CT-01**, **CT-02**, **CT-03** (este último lendo **também** o trecho de `php.ini` que a documentação manda instalar, que o `-d` do lançador não mascara) |
| **10** | RQ-13 | **O levantamento de níveis de qualidade não existe.** `grep` por *"níveis de qualidade"*, *"quality levels"* e pelos nomes de candidatos em `docs/` e nos dois READMEs devolve **zero**. RQ-13 pede *"analise e pesquise se temos mais níveis de qualidade"*, e o `00` fixou os três critérios de corte — não há seção, não há candidatos, não há roadmap. **É a cláusula com menos entrega da feature inteira** | escrever a seção primeiro; depois **CT-21**, linha do levantamento: por candidato, resposta às três perguntas de corte, com adotado separado de roadmap |
| **11** | RQ-14 | **O mutation score não é publicado.** A documentação cita `pest --mutate` e o `pestw.cmd`, e não traz score, duração, plataforma nem sobreviventes. O `pestw.cmd` registra uma medição real (225 mutantes, 98,22 %, 43,95 s, em `CustomizadorDaInstalacao`), mas ela vive no **cabeçalho de um lançador**, não na documentação. RQ-14 pede que o score *"passe a funcionar"*, e funcionar é produzir número auditável | publicar o número com duração, plataforma e sobreviventes nomeados; depois **CT-27** |
| **12** | RQ-04, RQ-05, RQ-08 | **Os números da documentação são prosa.** O tempo (25 min / 52 min), o custo do Xdebug (+44 %) e o comando de medição não têm guarda: só o percentual inteiro tem (`[CT-49]`). A própria página admite — *"os números com casa decimal desta página são datados, não derivados"* —, o que é honesto e não é guarda | **CT-19** (os quatro números iguais em pt e en), **CT-21** (cada item na forma exigida), **CT-22** (o comando documentado é o do `composer.json` e do `ci.yml`, e liga a coleta) |
| **13** | RQ-06, RQ-07 | **A cadeia de CI não tem guarda de propagação.** Hoje o `run:` do passo é um bloco de duas linhas sem tubulação nem invólucro, e o job não é tolerante a erro — está certo, e nada impede que mude | **CT-23** (as quatro cláusulas de propagação), **CT-24** (mesmo job, relatório da execução), **CT-25** (fim-a-fim: a cadeia completa sai com código ≠ 0 contra relatório a `meta − 0,01 pp`) |
| **14** | RQ-14 | **O `--tia` não tem guarda**, e o plugin de mutação pode voltar a ser transitivo num `composer update` | **CT-28** e **CT-26** |
| **15** | RQ-05, RQ-06 | **O próximo passo da mensagem de erro não é verificado.** A reprovação manda rodar `composer test:coverage`; nada garante que esse alvo continue existindo | cláusula de **CT-16** |

**Dois deles não são trabalho de teste, e sim de decisão**: o **#2** (gate fora das PRs) precisa de
uma cláusula no `00` antes de virar cenário, e o **#10** (níveis de qualidade) precisa da seção
existir antes de ter o que travar. Os outros treze são cenário a escrever.
