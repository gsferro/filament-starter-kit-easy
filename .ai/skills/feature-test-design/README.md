# feature-test-design — Casos de Teste que Matam Defeito

> **Skill**: [`SKILL.md`](SKILL.md) · versão **1.16.0**
> Este README fala com a **pessoa**: por que a skill existe, que problema ela resolve, a
> evidência por trás de cada decisão e o que ela não faz. O procedimento que o agente segue
> está no `SKILL.md` — e, sob demanda, nos arquivos de [`references/`](references/) que cada passo
> manda abrir — e não é duplicado aqui.

## O problema

A `feature-wiki` já escrevia casos de teste antes do código. E mesmo assim, na prática:

> *"os CTs que estão sendo escritos cobrem alguns erros, mas outros passam"*

Isso não é falta de disciplina — é uma consequência previsível de **como** os casos eram
derivados.

### O que a auditoria mediu

Auditoria de 9 wikis reais desta coletânea, 125 casos de teste e 164 testes Pest em produção:

| Medida | Resultado |
|---|---|
| Casos que caem nos 4 arquétipos que o próprio template nomeava | **52%** — e nada além |
| Análise de valor limite genuína | **1 ocorrência em 125** |
| Tabela de decisão implementada · pairwise | **0** · **0** |
| Cláusulas `RQ` rastreáveis sem nenhum caso | **9 de 19** (razão CT/RQ = 1,11) |
| Casos com oráculo fraco (implementação defeituosa passa) | **19 de 125**; 7 graves cobrindo 52 telas |
| Telas `create` cobertas só por `visit()`, sem gravação | **5** |
| Testes "órfãos" — achados depois, não pelo processo do `04` | **9**, dos quais 5 um particionamento formal listaria em minutos |

Os 4 arquétipos eram happy path, falha, autorização e log. A auditoria não é rodada do protocolo
de [`experimentos/`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md): é a leitura forense da saída real que
motivou a skill, e esta seção é a fonte dela.

Três causas, todas mensuráveis:

### 1. O caso de teste era derivado do plano, não do requisito

O `04-casos-de-teste.md` dizia, literalmente, que os CTs "validam os passos do PRD". Só que o
PRD é a **interpretação** do requisito feita pelo mesmo agente que depois escreve o teste e
implementa. Testar o plano confirma o plano.

Não é opinião. Medido sobre **318 métodos focais cobrindo 233 defeitos reais** do Defects4J com
11 modelos: gerar teste a partir do **código defeituoso** em vez da **especificação** multiplica por
~1,4 os testes que codificam o bug como comportamento esperado (2,69% → 3,84%) e derruba por ~1,5
os que detectam o defeito (4,50% → 2,98%). Trocar o código por uma descrição do comportamento
pretendido mitiga o efeito; não o reverte — [arXiv 2607.22883](https://arxiv.org/html/2607.22883v1).

### 2. O gabarito determinava a cobertura

O template oferecia CT-01 happy path, CT-02 falha, CT-03 autorização, CT-04 log. Um agente
preenche o gabarito — e o número de casos converge para o número de linhas do gabarito,
**independente da complexidade do requisito**. Não havia nenhum passo que *derivasse* a
quantidade e a escolha dos casos a partir da estrutura do problema.

### 3. Não havia critério de suficiência

A rastreabilidade existente (`cada CT cita o RQ que cobre`) mede **existência**, não
**adequação**: um único caminho feliz basta para a cláusula aparecer ✅ na Matriz de
Rastreabilidade. É exatamente por isso que o `feature-quality-gate` aprovava e o defeito passava.

O critério que existia não servia: *"todo método público tem 1 CT, cada branch tem um CT"* —
cobertura de um **código que ainda não existe** quando o `04` é escrito. Isso obriga o agente a
imaginar a implementação e testá-la.

E a métrica clássica não salva: com o tamanho da suíte controlado, **cobertura de linha não
prevê eficácia de detecção** ([Inozemtseva & Holmes, ICSE 2014](https://www.cs.ubc.ca/~rtholmes/papers/icse_2014_inozemtseva.pdf)).
100% de cobertura é compatível com zero assertion útil.

## O que a skill faz

Substitui **preencher gabarito** por um **pipeline de derivação** em 8 passos (0 a 7), com um gate no fim
que responde à pergunta que interessa: *este conjunto pega defeito?*

| Passo | O que é | Que problema ataca |
|---|---|---|
| **0. Perfil por risco** | P×I por área → mínimo / padrão / completo | impede o pipeline de explodir e ser abandonado |
| **1. Varredura SFDIPOT** | 7 dimensões: Structure, Function, Data, Interfaces, Platform, Operations, Time | o que escapa quase nunca é um caso a mais — é uma **dimensão inteira esquecida** |
| **2. Mapa de Regras** | Example Mapping: regras 🟦, exemplos 🟩, perguntas 🟥; fecha com as costuras de teste, confirmadas antes do primeiro cenário | separa *descobrir* de *escrever*; regra é o eixo de cobertura |
| **3. Técnica por regra** | partição, valor limite 3-valores, tabela de decisão, tabela estado×evento, matriz papel×ação, pairwise, rastreio de efeito | cada técnica pega uma **classe de defeito que as outras não pegam** |
| **4. Checklist de taxonomia** | IDOR, idempotência, concorrência, timezone/DST, nulo≠vazio≠ausente, paginação, ordenação, unicidade+soft delete, mass assignment, precisão monetária, superfície Livewire (método público chamável por `$wire.`, propriedade pública sem `#[Locked]`, estado do framework — `$filters`, `$pageFilters`, `$tableFilters` — que vira índice de array, argumento de `parse` ou nome de coluna) | cobre o que a especificação **nunca menciona** |
| **5. Gherkin pt-BR** | `Funcionalidade` → `Regra` → `Cenário`, com Dado/Quando/Então | força um oráculo observável e linguagem de domínio |
| **6. Gate de falsificabilidade** | toda regra declara os mutantes plausíveis e aponta quem mata cada um, e com que asserção | **o passo que não existia** |
| **7. Camada e poda** | o nível mais barato que prova, conferido contra a costura do grupo; teto por perfil | evita empurrar para browser o que um teste de componente resolve |

### O passo 6 é o coração

Para cada regra, o agente escreve as implementações erradas plausíveis e aponta qual cenário
falharia diante de cada uma:

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `<=` no lugar de `<` no limite de usos | CT-04 (linha "borda") | linha `borda` (3, 3): "recusado"; o mutante aceita |
| M2 | contador incrementado antes de validar | CT-06 | depois da recusa, o contador não muda; o mutante soma 1 |
| M3 | contador não incrementado no sucesso | ⚠️ **sem matador** | — (lacuna declarada) |

Mutante sem matador vira cenário novo — ou lacuna **declarada**, com motivo. É isto que torna o
conjunto auditável: hoje não havia como perguntar "o que este conjunto deixa passar?".

A coluna **Asserção que mata** é obrigatória em todo perfil desde a 1.16.0. Sem ela, "CT-04 mata
M1" era afirmação do mesmo agente que tinha escrito o CT e o mutante — um gate que se certifica
sozinho. Com o valor escrito, qualquer leitor (e o adversário da revisão) confere, sem rodar nada,
se aquele valor diverge mesmo sob o mutante. A auditoria interna de 2026-09-26 apontou esse furo
nos perfis mínimo e padrão
([estudo](https://github.com/gsferro/laravel-ai-skills/blob/main/estudos/2026-09-26-agentskills-spec-to-spec-to-tickets.md), §7.3).

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
> derivada por este pipeline reportaram o mesmo mutation score e detectaram quantidades
> diferentes de defeitos plantados — números e a ressalva sobre aquele score em
> [`experimentos/README.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md).
>
> Os mutantes do passo 6 são **de especificação**: nascem do requisito, não do código, e por isso
> enxergam o que nunca foi escrito. O `--mutate` é o piso de qualidade de assertion; o passo 6 é o
> teto de cobertura de comportamento.

### Revisão adversarial

No perfil completo, ou com Impacto 3 em qualquer área, o conjunto vai para um sub-agente que não o
derivou — no Claude Code, o `fw-adversario-ct`, que recebe só o `00` e o `04`/`05` (e o
`wikis/glossario.md`, se existir: vocabulário não é plano nem código) — com a tarefa de provar que
ele deixa passar defeito: **no mínimo** cinco implementações erradas que passam por todos os
cenários. Cinco é piso; um número fixo virava gabarito. A revisão recebe o conjunto **inteiro**,
não só a área que a disparou: o achado cai onde cai. Sem sub-agente, a skill não se autorrevisa;
declara a lacuna no `04`, e o `feature-quality-gate` a reporta como débito.

**Até onde vai a cegueira.** Até a 1.15.0 este README dizia que ela "vem da construção". Não vinha:
o agente tinha `Read` e `Glob` sem restrição de path, e o `01` mora na mesma pasta do `00`. Desde a
1.16.0 o agente declara um hook `PreToolUse` que chama o `guarda-subagente.sh` da `feature-wiki`
(perfil `adversario-ct`): `Read` e `Grep` fora do `00`, do `04`, do `05`, do glossário e dos
arquivos das skills são negados **por construção** — desde que o hook esteja instalado (`feature-wiki`
≥ 4.0.0). Se o script não é encontrado, o hook nega tudo, e a sessão cai no `general-purpose`, em
que a cegueira volta a ser de prompt. `Glob` continua liberado: devolve só nomes de arquivo.

## Costuras de teste e perguntas em raias (1.16.0)

**Costuras declaradas.** O `04` ganhou `## Costuras de Teste`: uma linha por grupo de cenários
dizendo onde ele se prende ao sistema — `unit de regra`, `Pest feature HTTP`, `componente
Livewire/Filament` ou `browser` —, se a costura já existe no projeto ou é nova, e quem a
confirmou. `Pest feature HTTP` é todo teste em `tests/Feature` com a aplicação de pé, pela rota ou
chamando action/service/model direto — é ali que mora o cenário "por fora do componente". O
`## Índice de Cenários` repete o valor na coluna `Costura`, que até a 1.15.0 se chamava `Camada`.
A declaração fecha o passo 2 e sai do que cada regra afirma, para que a confirmação aconteça antes
de existir cenário. Antes, a camada era escolhida cenário a cenário, e o gate do `05`
era uma regra à parte.
Agora o `05` existe se e só se uma costura é `browser`, e toda linha do checklist de taxonomia
aponta o grupo onde o cenário vive. A ideia vem da skill `to-spec` (`mattpocock/skills`); a skill **não** importou
o "o ideal é uma costura", que é meta de custo de manutenção sem medição — os mutantes mortos das
rodadas vieram de derivação por regra, não de minimizar costuras (estudo, §3.2 e §3.4).

**Pergunta não vira cenário chutado.** O requisito chega de quem não está na sessão. Quando a
derivação acha uma regra que o texto não decide, ela escreve uma pergunta numerada, com raia e
recomendação: **requisito** (só o solicitante responde) ou **desenho** (o desenvolvedor responde).
O `Qn` é uma sequência só por feature, nas três raias; derivando em sub-agente, a skill não sabe o
próximo número e usa `Q?1, Q?2…`, que a sessão renumera ao gravar. A `RQ` afetada fica aberta e
sem cenário até a resposta — antes, a skill escrevia um cenário `@premissa` na direção "falha
fechado", e esse cenário, materializado, forçava a implementação a seguir a interpretação de quem
derivou. A direção "falha fechado" continua, agora como a recomendação da pergunta; o invariante
que vale para as duas respostas continua virando cenário (estudo, §2.3). A troca tem custo, e ele fica declarado: até a resposta, some a detecção que o
cenário direcional dava ([`references/casos-medidos.md`](references/casos-medidos.md), *Premissa de
comportamento*), e o invariante não a repõe.

**Glossário.** Quando o projeto tem `wikis/glossario.md`, o Gherkin usa o termo de lá; termo que
não está no glossário fica como o `00` o escreveu. Sinônimo inventado no cenário é um conceito que
ninguém decidiu.

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
3. **Tamanho.** O pipeline tem o porte de uma skill inteira. Embutido na `feature-wiki`, ele
   entraria no contexto de toda feature, inclusive das que não têm teste a derivar; separado, só
   carrega quem o invoca.
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

O teste de componente também tem um limite, e a skill o fixa em gate: toda regra de autorização e
de validação ganha ao menos um cenário **por fora da UI**, porque o componente não distingue a regra
que vive no domínio da regra que vive só no formulário.

## Fatos corrigidos sobre `pest-plugin-browser`

A skill carrega os fatos verificados do plugin que contradizem crenças comuns — e que a
documentação anterior da coletânea trazia errados (o plugin sobe o próprio servidor, `actingAs()`
funciona, nunca `wait()`, nunca `--parallel`). A fonte única deles é
[`references/pest-plugin-browser.md`](references/pest-plugin-browser.md), que a `feature-wiki`, o
agente `fw-executor-ctb` e o `feature-quality-gate` também referenciam.

## O que foi medido

A skill não foi escrita e publicada — foi submetida a um experimento controlado antes, e corrigida
com o resultado.

**Montagem**: mesmo requisito (um card com 9 ambiguidades plantadas), mesmo projeto, mesmo
`00-requisito.md`, dois agentes independentes — um seguindo a `feature-wiki` 2.10.0, outro este
pipeline. Um catálogo de **18 defeitos plantados foi escrito antes** de qualquer conjunto existir.
Um juiz cego pontuou os dois, sem saber qual processo gerou qual, exigindo **citação literal** da
assertion que mataria cada defeito.

Os números — defeitos detectados de 18, lacunas cegas e declaradas por cenário (cenário 1, cupons:
cálculo, dinheiro, datas, unicidade; cenário 2, aprovação em duas etapas: máquina de estados,
autorização, efeito colateral), as células da matriz estado × operação executadas e a materialização
das duas especificações em Pest contra a mesma implementação — vivem numa tabela só, rodada a
rodada, em [`experimentos/README.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md). A contagem de oráculos
fracos por versão está na entrada 1.6.0 da `feature-test-design` no
[`CHANGELOG.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/CHANGELOG.md). Este README não repete número de rodada, para que nenhum
deles apareça em dois lugares com valores diferentes.

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
| 1.8.0 | cenário por fora da UI; premissa de mecanismo não apaga cenário; matriz cartesiana fechada; oráculo invertido | policy só no form; excluído ainda aplicável; aprovar em rascunho |
| 1.9.0 | premissa de comportamento falha fechado; não-efeito exige destinatário real; legenda da matriz auditada | validade no passado assumida como aceita; e-mail fora da transação |

> Regras das versões 1.10.0 em diante estão no `CHANGELOG.md`.

Os três defeitos mais teimosos do cenário 2 — ciclo de volta, tela mentindo o estado e e-mail fora
da transação — sobreviveram a **dois** conjuntos e caíram no terceiro, cada um pelo mecanismo que
a versão correspondente introduziu. É o argumento mais forte a favor do método: as regras não são
opinião, são o registro do que já escapou.

## O que a skill não faz

- **Não escreve o código de teste** — ela produz a especificação. Quem materializa em `.php` é o
  sub-agente `fw-executor-ct` — por construção quem **não** implementou — ou o `fw-executor-ctb`
  para os CT-B (ver feature-wiki). Cenário descoberto durante a implementação nasce no `04` antes
  de virar teste, e os IDs `[CT-nn]` do teste e do `04`/`05` são conferidos nos dois sentidos
- **Não corrige implementação**
- **Não substitui o `feature-quality-gate`**: a derivação acontece *antes* do código; o gate
  valida o produto *depois*. Uma acha lacuna de especificação de teste, o outro acha lacuna
  entre requisito e produto
- **Não garante ausência de defeito.** Pairwise deixa passar de 10% a 40% das falhas de
  interação; mutation score não é prova. A skill declara suas lacunas em vez de fingir cobertura

## Dependências

**Obrigatória**: a `feature-wiki` ≥ 4.0.0 (`metadata.requires` do `SKILL.md`). O `00-requisito.md`
existe desde a 2.10.0 e é o oráculo do pipeline — sem ele a skill **para e pede** o requisito (nunca
deriva do PRD) —, mas esta versão depende também do que veio depois: `## Superfície Livewire` no
`02` (3.3.0), `## Despachos` no `03` (3.4.0), o `@obsoleto` para CT de elemento cortado e o
`fw-executor-ct` (3.5.0), contagens por `grep -c` (3.5.1), o contrato de delegação que entrega o
`02` à derivação (3.5.2) e, na 4.0.0, a derivação no step 7 (depois da auditoria Ponytail),
`## Perguntas ao Solicitante` e `## Premissas` (`P-nn`) no `00`, o glossário `wikis/glossario.md`
e o hook `guarda-subagente.sh`, que dá ao `fw-adversario-ct` a cegueira por construção nas
ferramentas de arquivo, com o hook instalado (sem o script, o hook nega tudo e a revisão cai no
`general-purpose`, com cegueira de prompt). Além disso,
um projeto com testes. Degradações declaradas:

| Item | O que habilita | Sem ele |
|---|---|---|
| `pestphp/pest-plugin-mutate` + PCOV/Xdebug | fechamento do ciclo (`pest --mutate`) | o passo 6 fica só como previsão, sem medição — declarar a ausência só com `php -m` / `ls vendor/pestphp/` colados. **No Windows o score é 100 % falso** sem o lançador `.cmd` (`feature-wiki/scripts/pestw.cmd`; explicação em [`feature-wiki/references/pest-5.md`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-wiki/references/pest-5.md)) |
| `pest-plugin-livewire` | camada de componente | cai para `Feature` HTTP, mais cara e mais cega |
| `pest-plugin-browser` + Playwright | CT-B executáveis | o `05` fica como roteiro manual |
| Sub-agente disponível | revisão adversarial | perfil completo ou Impacto 3 perde o gate independente |
| Claude Code com o agente `fw-adversario-ct` instalado | adversarial em `opus`, **sem Edit/Write/Bash**, recebendo só `00` + `04`/`05` (+ `wikis/glossario.md`, se existir); `Read`/`Grep` fora dessa lista negados por hook — por construção só com o `guarda-subagente.sh` da `feature-wiki` ≥ 4.0.0 instalado; sem o script, o hook nega tudo | cai em `general-purpose` com `model: opus` explícito; sem sub-agente nenhum, **não** autorrevisa: o `04` declara *"Revisão adversarial: NÃO FEITA — host sem sub-agente"* e o `feature-quality-gate` reporta como débito |

### Instalação do sub-agente (Claude Code)

A definição vem nesta skill, em [`agents/fw-adversario-ct.md`](agents/fw-adversario-ct.md), para o
`boost:add-skill` instalá-la junto. **O Claude Code só lê `.claude/agents/`** — depois de instalar
ou atualizar as skills, copie os agentes de toda a esteira, uma vez e a cada atualização:

```bash
mkdir -p .claude/agents
cp .ai/skills/*/agents/*.md .claude/agents/
```

```powershell
New-Item -ItemType Directory -Force .claude\agents | Out-Null
Copy-Item -Force .ai\skills\*\agents\*.md .claude\agents\
Get-ChildItem .claude\agents\fw-*.md        # cinco arquivos
```

O hook do agente procura `feature-wiki/scripts/guarda-subagente.sh` em `.ai/skills/`,
`.claude/skills/` e `~/.claude/skills/`, nessa ordem. Sem a `feature-wiki` ≥ 4.0.0 em nenhum dos
três, o agente não lê nada (o hook falha fechado), e a revisão vai pela rota `general-purpose`.

Espelhar as skills em `.claude/skills/` só é preciso sem `boost.json`: com ele, o `boost:update` cria
cada `.claude/skills/<skill>` como symlink, e copiar por cima falha. Os dois casos estão em
[Como Instalar no Claude Code](https://github.com/gsferro/laravel-ai-skills/blob/main/README.md#-como-instalar-no-claude-code),
no README da coletânea.
