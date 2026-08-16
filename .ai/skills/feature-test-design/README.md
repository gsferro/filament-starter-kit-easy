# feature-test-design — Casos de Teste que Matam Defeito

> **Skill**: [`SKILL.md`](SKILL.md) · versão **1.0.0**
> Este README fala com a **pessoa**: por que a skill existe, que problema ela resolve, a
> evidência por trás de cada decisão e o que ela não faz. O procedimento que o agente segue
> está no `SKILL.md` e não é duplicado aqui.

## O problema

A `feature-wiki` já escrevia casos de teste antes do código. E mesmo assim, na prática:

> *"os CTs que estão sendo escritos cobrem alguns erros, mas outros passam"*

Isso não é falta de disciplina — é uma consequência previsível de **como** os casos eram
derivados. Três causas, todas mensuráveis:

### 1. O caso de teste era derivado do plano, não do requisito

O `04-casos-de-teste.md` dizia, literalmente, que os CTs "validam os passos do PRD". Só que o
PRD é a **interpretação** do requisito feita pelo mesmo agente que depois escreve o teste e
implementa. Testar o plano confirma o plano.

Não é opinião. Medido sobre **318 defeitos reais** do Defects4J com 11 modelos: gerar teste a
partir do **código** em vez da **especificação** multiplica por ~8 os testes que codificam o bug
como comportamento esperado (0,46% → 3,84%) e derruba por ~3 os que detectam o defeito
(8,51% → 2,98%). Trocar o código por uma descrição do comportamento pretendido reverte os dois
números — [arXiv 2607.22883](https://arxiv.org/html/2607.22883v1).

### 2. O gabarito determinava a cobertura

O template oferecia CT-01 happy path, CT-02 falha, CT-03 autorização, CT-04 log. Um agente
preenche o gabarito — e o número de casos converge para o número de linhas do gabarito,
**independente da complexidade do requisito**. Não havia nenhum passo que *derivasse* a
quantidade e a escolha dos casos a partir da estrutura do problema.

### 3. Não havia critério de suficiência

A rastreabilidade existente (`cada CT cita o RQ que cobre`) mede **existência**, não
**adequação**: um único caminho feliz basta para a cláusula aparecer ✅ na Matriz de
Rastreabilidade. É exatamente por isso que o `feature-quality-gate` aprovava e o defeito passava.

E a métrica clássica não salva: com o tamanho da suíte controlado, **cobertura de linha não
prevê eficácia de detecção** ([Inozemtseva & Holmes, ICSE 2014](https://www.cs.ubc.ca/~rtholmes/papers/icse_2014_inozemtseva.pdf)).
100% de cobertura é compatível com zero assertion útil.

## O que a skill faz

Substitui **preencher gabarito** por um **pipeline de derivação** em 7 passos, com um gate no fim
que responde à pergunta que interessa: *este conjunto pega defeito?*

| Passo | O que é | Que problema ataca |
|---|---|---|
| **0. Perfil por risco** | P×I por área → mínimo / padrão / completo | impede o pipeline de explodir e ser abandonado |
| **1. Varredura SFDIPOT** | 7 dimensões: Structure, Function, Data, Interfaces, Platform, Operations, Time | o que escapa quase nunca é um caso a mais — é uma **dimensão inteira esquecida** |
| **2. Mapa de Regras** | Example Mapping: regras 🟦, exemplos 🟩, perguntas 🟥 | separa *descobrir* de *escrever*; regra é o eixo de cobertura |
| **3. Técnica por regra** | partição, valor limite 3-valores, tabela de decisão, tabela estado×evento, matriz papel×ação, pairwise, rastreio de efeito | cada técnica pega uma **classe de defeito que as outras não pegam** |
| **4. Checklist de taxonomia** | IDOR, idempotência, concorrência, timezone/DST, nulo≠vazio≠ausente, paginação, ordenação, unicidade+soft delete, mass assignment, precisão monetária | cobre o que a especificação **nunca menciona** |
| **5. Gherkin pt-BR** | `Funcionalidade` → `Regra` → `Cenário`, com Dado/Quando/Então | força um oráculo observável e linguagem de domínio |
| **6. Gate de falsificabilidade** | toda regra declara os mutantes plausíveis e aponta quem mata cada um | **o passo que não existia** |
| **7. Camada e poda** | o nível mais barato que prova; teto por perfil | evita empurrar para browser o que um teste de componente resolve |

### A varredura SFDIPOT

A varredura SFDIPOT é uma heurística de teste de software criada por James Bach para dividir problemas complexos em perspectivas menores e distintas durante o planejamento e exploração de aplicações. Cada letra do acrônimo representa um ponto de vista diferente para analisar o sistema:

| Letra | Ponto de vista | O que cobre |
|---|---|---|
| **S** — Structure | Estrutura | Código, arquivos, componentes físicos ou lógicos |
| **F** — Function | Função | O que o software faz, suas funcionalidades |
| **D** — Data | Dados | O que o sistema processa, armazena ou manipula |
| **I** — Interfaces | Interfaces | Telas, APIs, integrações, entradas e saídas |
| **P** — Platform | Plataforma | Sistema operacional, hardware ou ambiente onde roda |
| **O** — Operations | Operações | Como o usuário ou administrador usa o sistema no dia a dia |
| **T** — Time | Tempo | Concorrência, desempenho, histórico ou a sequência dos eventos |

O passo 1 do pipeline existe porque o que escapa quase nunca é um caso a mais — é uma **dimensão inteira esquecida**. A varredura força o agente a olhar o requisito por essas sete lentes antes de escrever qualquer regra ou cenário.

### O passo 6 é o coração

Para cada regra, o agente escreve as implementações erradas plausíveis e aponta qual cenário
falharia diante de cada uma:

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `<` no lugar de `<=` no limite de usos | CT-04 (linha "borda") |
| M2 | contador incrementado antes de validar | CT-06 |
| M3 | contador não incrementado no sucesso | ⚠️ **sem matador** |

Mutante sem matador vira cenário novo — ou lacuna **declarada**, com motivo. É isto que torna o
conjunto auditável: hoje não havia como perguntar "o que este conjunto deixa passar?".

A técnica tem validação industrial: detecção de mutantes correlaciona ~73% com detecção de
defeitos reais e carrega informação que a cobertura não carrega
([Just et al., FSE 2014](https://homes.cs.washington.edu/~rjust/publ/mutants_real_faults_fse_2014.pdf));
o Meta industrializou geração de teste guiada por mutante em 10.795 classes
([ACH, FSE 2025](https://arxiv.org/pdf/2501.12862)).

E o ciclo fecha com medição real: depois de implementar, `pest --mutate` mede. **Todo mutante
sobrevivente é traduzido de volta para a lacuna de derivação que o deixou vivo** (`>` → `>=` =
falta valor limite; `&&` → `||` = falta linha da tabela de decisão; chamada removida = falta
assertion de efeito colateral).

> **Mas `pest --mutate` não substitui o passo 6, e é importante saber por quê.** Mutation testing
> só muta **código que existe**. Cláusula do requisito que nunca virou código não gera mutante
> nenhum, e o score não cai — a métrica é **estruturalmente cega à omissão**.
>
> Medido neste projeto, contra a **mesma** implementação: a suíte derivada por gabarito e a
> derivada por este pipeline tiveram **100% de mutation score cada uma** (24 de 24 mutantes
> mortos), e detectaram **7 e 12** defeitos plantados de 18. A métrica saturou e não distinguiu
> as duas.
>
> Os mutantes do passo 6 são **de especificação**: nascem do requisito, não do código, e por isso
> enxergam o que nunca foi escrito. O `--mutate` é o piso de qualidade de assertion; o passo 6 é o
> teto de cobertura de comportamento.

## Por que Gherkin — e por que sem runner

O Gherkin entra como **linguagem de especificação no markdown**, traduzida para
`describe()`/`it()` do Pest. Não há runner Gherkin.

**Por que Gherkin ajuda**: força um `Então` sobre saída observável (o oráculo), empurra para
linguagem de domínio em vez de mecânica de implementação, e o `Regra:` (Gherkin 6+) dá um lugar
de primeira classe para o critério de aceite — que é exatamente o eixo de cobertura do passo 2.
O `Esquema do Cenário` é a forma canônica de expressar partição e valor limite, com a coluna do
rótulo dizendo qual borda cada linha representa.

**Por que sem runner**: não existe plugin Gherkin viável para Pest — o único que promete
([`cborgas/pickles`](https://github.com/cborgas/pickles)) tem 0 estrelas e parou em 2023, e
`pest-plugin-gwt` está travado em Pest ^3 enquanto o Pest está na 5. Behat exigiria a
[extensão Laravel abandonada](https://github.com/laracasts/Behat-Laravel-Extension) (sem commits
desde 2022) ou um [fork de 4 estrelas](https://packagist.org/packages/cevinio/behat-laravel-extension).
O custo de *step definitions* e "step soup" é documentado e não vale a pena importar.

**E isso não é meio-caminho.** O BDD se decompõe em *Discovery → Formulation → Automation*, e a
Formulation entrega valor sozinha ([Rose & Nagy](https://cucumber.io/blog/bdd/bdd-is-not-test-automation/)).
O próprio criador do Cucumber é explícito: *"If all you need is a testing tool for driving a mouse
and a keyboard, don't use Cucumber"*
([Hellesøy](https://cucumber.io/blog/collaboration/the-worlds-most-misunderstood-collaboration-tool/)).

**Gherkin sozinho não resolveria o problema.** Ele é *formato*, não *técnica de derivação* —
escrever Gherkin ruim é tão fácil quanto escrever CT ruim, e os anti-padrões estão catalogados
("noisy scenarios", "vague scenarios", "testing through the UI"). Por isso ele entra no passo 5,
**depois** da derivação, e não no lugar dela.

## Por que uma skill separada da feature-wiki

Quatro razões, em ordem de peso:

1. **Independência de julgamento.** É o mesmo princípio que fez o `00-requisito.md` existir e que
   proíbe o `feature-quality-gate` de corrigir o que julga: quem escreveu o plano não deve
   derivar o teste do próprio plano. A skill separada torna a fronteira executável — a entrada é
   o requisito, e o PRD entra só para paths e superfície.
2. **Reuso fora do fluxo da wiki.** A derivação é necessária também quando o quality gate roteia
   um achado para *destino 3 — teste*, quando se escreve a regressão de um bug de produção, e
   para cobrir código legado sem wiki.
3. **Tamanho.** O `SKILL.md` da `feature-wiki` já tem ~1.700 linhas. Embutir o pipeline levaria a
   ~2.200 — um monólito que o agente precisa carregar inteiro para qualquer feature, inclusive
   as que não têm teste a derivar.
4. **Ciclo de vida próprio.** A tabela de taxonomia do passo 4 é **viva**: cada defeito que
   escapa para produção vira uma linha nova. Isso é manutenção contínua, com cadência diferente
   da wiki.

O ciclo da coletânea passa a ser: **planejar** (`feature-wiki`) → **especificar teste**
(`feature-test-design`) → **executar** (Ponytail) → **validar** (`feature-quality-gate`) →
**memorizar** (`requirement-to-rule`).

## A camada que faltava: teste de componente Livewire

O `04` era declarado "100% backend" e todo cenário de UI ia para o `05` (browser), com teto de
1 happy path + 1 erro. O efeito colateral: **a UI ficava praticamente sem cobertura**, porque o
teto do browser virou o teto de toda a superfície de tela.

Em Laravel + Filament, a maior parte do que parece exigir browser é **teste de componente
Livewire** — milissegundos, sem Node e sem Playwright: validação de formulário
(`fillForm` → `assertHasFormErrors`), gravação (`->call('create')`), listagem e filtro
(`assertCanSeeTableRecords`, `searchTable`, `filterTable`), ações
(`callAction(TestAction::make(...)->table())`), notificação (`assertNotified`) e autorização
(`livewire(...)->assertForbidden()`).

Browser só se justifica quando a asserção depende de **JavaScript executado, pixel ou
acessibilidade**. A skill fixa isso numa tabela de decisão de camada, com a **regra do par**
aprendida em produção: *uma tela aberta não é uma tela que grava* — um `GET` fica verde com o
salvamento quebrado, então toda tela de escrita gera dois cenários.

## Fatos corrigidos sobre `pest-plugin-browser`

A skill carrega dez fatos verificados que contradizem crenças comuns — e que a documentação
anterior da coletânea trazia errados:

- **O plugin sobe o próprio servidor** (HTTP in-process, porta aleatória). Nada de Herd,
  `artisan serve`, Sail ou `APP_URL` a configurar
- Como é o **mesmo processo**, `$this->actingAs($user)` antes do `visit()` funciona — e é o
  recomendado. Login pela tela custa dezenas de segundos por cenário
- **Nunca `wait($segundos)`**: o plugin reexecuta cada assertion até o teto de
  `pest()->browser()->timeout()`. `waitForText`/`waitForSelector` **não existem**
- **`assertPathIs` antes das asserções de conteúdo** — invertido, o `assertSee` roda contra o
  snapshot da página anterior e falha com a ação tendo funcionado
- **Nunca `--parallel` com browser**; e como `--tia` exige run completo, os dois não convivem
  numa invocação só
- `assertNoSmoke()` só em tela de autoria própria; em tela de plugin de terceiro,
  `assertNoJavaScriptErrors()`

## O que foi medido

A skill não foi escrita e publicada — foi submetida a um experimento controlado antes, e corrigida
com o resultado.

**Montagem**: mesmo requisito (um card com 9 ambiguidades plantadas), mesmo projeto, mesmo
`00-requisito.md`, dois agentes independentes — um seguindo a `feature-wiki` 2.10.0, outro este
pipeline. Um catálogo de **18 defeitos plantados foi escrito antes** de qualquer conjunto existir.
Um juiz cego pontuou os dois, sem saber qual processo gerou qual, exigindo **citação literal** da
assertion que mataria cada defeito.

**Cenário 1 — cupons** (cálculo, dinheiro, datas, unicidade):

| Métrica | Gabarito (2.10.0) | v1.0.0 | v1.1.0 | v1.5.0 |
|---|---|---|---|---|
| Defeitos detectados (de 18) | 7 | 12 | 16 | **16** |
| Taxa de detecção | 38,9% | 66,7% | 88,9% | **88,9%** |
| Lacunas **cegas** | 10 | 2 | 1 | **1** |
| Casos de teste | 12 | 37 | 41 | 47 |
| Oráculos fracos | — | — | 7 de 41 | **3 de 47** |

**Cenário 2 — aprovação em duas etapas** (máquina de estados, autorização, efeito colateral):

| Métrica | Gabarito | v1.0.0 | v1.5.0 |
|---|---|---|---|
| Defeitos detectados (de 18) | 11 | 15 | **17** |
| Taxa de detecção | 61,1% | 83,3% | **94,4%** |
| Lacunas **cegas** | 7 | 2 | **1** |
| Células inválidas da matriz estado × operação **executadas** | 9 de 21 | 21 de 21 | 21 de 21 |

Depois, as duas especificações foram **materializadas em Pest** contra a **mesma** implementação
(escrita por um terceiro agente que nunca viu nenhum dos dois `04`):

| | Suíte do gabarito | Suíte deste pipeline |
|---|---|---|
| Testes verdes | 22 (55 assertions) | **38 (91 assertions)** |
| Defeitos reais encontrados na implementação | 2 | 2 |
| Mutation score em `app/Services` | 100% (24/24) | 100% (24/24) |

Os dois defeitos reais foram **diferentes**: o gabarito pegou "unicidade não imposta quando
`tenant_id` é NULL"; este pipeline pegou a **borda exata da validade** (`>=` no lugar de `>`).
Ambos pegaram "trilha de auditoria sobrescrita a cada aplicação".

**Toda regra desta skill nasceu de um defeito que escapou.** Cada rodada mediu, listou os defeitos
que atravessaram os conjuntos, e a versão seguinte fechou exatamente aqueles:

| Versão | Regra que entrou | Defeito que a motivou |
|---|---|---|
| 1.1.0 | criação/uso, domínio condicionado, estado × operação, `sim` não é resposta | valor negativo, teto do percentual, data no passado, excluído ainda aplicável |
| 1.2.0 | 2-switch, enum exaustivo, injeção de falha | ciclo de volta, tela mentindo o estado, e-mail fora da transação |
| 1.4.0 | o exemplo tem de ser discriminante | precisão de `float` marcada como coberta |
| 1.5.0 | criação ≠ **edição** ≠ uso, partição repetida em cada efeito | quatro defeitos que viviam só no `save` |
| 1.6.0 | parâmetro livre é o instante/contexto; matriz é 3D; canal do efeito | fuso que virou lacuna cega; barreira de identidade nunca exercitada |
| 1.7.0 | campo fora do estado inicial; célula argumentada não conta; verbo irmão | valor alterado após o envio sem reavaliar a alçada |

Os três defeitos mais teimosos do cenário 2 — ciclo de volta, tela mentindo o estado e e-mail fora
da transação — sobreviveram a **dois** conjuntos e caíram no terceiro, cada um pelo mecanismo que
a versão correspondente introduziu. É o argumento mais forte a favor do método: as regras não são
opinião, são o registro do que já escapou.

## O que a skill não faz

- **Não escreve o código de teste** — ela produz a especificação. Quem materializa em `.php` é o
  agente implementador (ou o sub-agente do CT-B)
- **Não corrige implementação**
- **Não substitui o `feature-quality-gate`**: a derivação acontece *antes* do código; o gate
  valida o produto *depois*. Uma acha lacuna de especificação de teste, o outro acha lacuna
  entre requisito e produto
- **Não garante ausência de defeito.** Pairwise deixa passar de 10% a 40% das falhas de
  interação; mutation score não é prova. A skill declara suas lacunas em vez de fingir cobertura

## Dependências

Nenhuma obrigatória além de um projeto com testes. Degradações declaradas:

| Item | O que habilita | Sem ele |
|---|---|---|
| `00-requisito.md` (feature-wiki ≥ 2.10) | o oráculo do pipeline | a skill **para e pede** o requisito |
| `pestphp/pest-plugin-mutate` + PCOV/Xdebug | fechamento do ciclo (`pest --mutate`) | o passo 6 fica só como previsão, sem medição |
| `pest-plugin-livewire` | camada de componente | cai para `Feature` HTTP, mais cara e mais cega |
| `pest-plugin-browser` + Playwright | CT-B executáveis | o `05` fica como roteiro manual |
| Sub-agente disponível | revisão adversarial | perfil completo perde o gate independente |
