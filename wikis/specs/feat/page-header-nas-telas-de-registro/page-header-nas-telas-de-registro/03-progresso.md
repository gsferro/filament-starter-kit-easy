# Progresso — Page header nas telas de registro

> Requisito: [`00-requisito.md`](00-requisito.md) · Plano: [`01-plano-acao.md`](01-plano-acao.md) ·
> ADRs: [`02-decisoes-arquiteturais.md`](02-decisoes-arquiteturais.md) ·
> CT: [`04-casos-de-teste.md`](04-casos-de-teste.md) · CT-B: [`05-casos-de-teste-browser.md`](05-casos-de-teste-browser.md)

**O que este arquivo registra.** Os testes desta entrega foram escritos **antes** de o `04` ficar pronto, com
numeração própria e conflitante (`CT-12` e `CT-13` existiam nos dois arquivos, significando coisas diferentes).
Esta reconciliação renumerou os rótulos para os IDs do `04` — **só rótulo e comentário, nenhuma linha de lógica**
— e mede, caso a caso, o que de fato existe.

Contagem do `04`: **55 CT** (o `CT-52` não existe, deliberadamente — ver R13d) **+ 4 CT-B**.
Suíte após a renumeração: `vendor/bin/pest tests/Kit/PageHeaderTest.php tests/Tenancy/PageHeaderTenancyTest.php`
→ **31 casos, 85 asserções, verde**.

---

## Matriz de Rastreabilidade

| RQ | Cláusula (resumo) | Passo do `01` | CT do `04` | Teste implementado | Estado |
|---|---|---|---|---|---|
| RQ-01 | o pacote entra como dependência | 1 | CT-34, CT-35 | `tests/Kit/PacotesRodada2Test.php:[CT-34]:170` e `:[CT-35]:204` | ✅ coberta |
| RQ-02 | header rico nas telas View/Edit de `User`, nos dois painéis | 3, 4, 5 | CT-03, CT-11, CT-16, CT-30, CT-32, CT-49 | `tests/Kit/PageHeaderTest.php:[CT-03]:64`, `:[CT-11]:377`, `:[CT-11]:402`, `:[CT-30]:355`, `:[CT-49]:585`; `tests/Tenancy/PageHeaderTenancyTest.php:[CT-03]:303`, `:[CT-15]:143`, `:[CT-16]:174`, `:[CT-30]:359` | ⚠️ parcial — **CT-32 sem teste** (ficha × edição como telas distintas) e a tela `EditUser` do `/app` não é visitada por caso nenhum |
| RQ-03 | header rico nas telas View/Edit de `Tenant` | 3, 4 | CT-03, CT-10, CT-40 | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-03]:45`, `:[CT-10]:388` | ⚠️ parcial — **CT-40 sem teste** (a tabela de decisão 2×2 dos badges da organização) |
| RQ-04 | uso documentado em Resource com Relations | 7 | CT-46 (+ a existência da receita, já declarada como lacuna no `04`) | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-45/CT-46]:93` | ⚠️ parcial — a convivência tem caso; a **receita** em `wikis/receitas.md` não tem oráculo, como o `04` já registrava |
| RQ-05 | uso de sub-agentes | — | — | — | processo, sem oráculo (declarado no `04`) |
| RQ-06 | branch própria | — | — | — | processo, sem oráculo |
| RQ-07 | registro de pacotes candidatos corrigido para ADOTADO | 7 | CT-33, CT-34 | `tests/Kit/PacotesRodada2Test.php:[CT-33]:118` e `:[CT-34]:170` | ✅ coberta (casos **herdados** daquela wiki, como o `04` manda) |
| RQ-08 | commits agrupados | 9 | — | — | processo, sem oráculo |
| RQ-09 | Blueprint | 10 | — | `tests/Kit/AderenciaAoBlueprintTest.php` (já existia) | processo |
| RQ-10 | `/code-review` no diff | 10 | — | — | processo, sem oráculo |
| RQ-11 | PR com a suíte verde | 11 | — | — | processo, sem oráculo |
| RQ-12 | o `kit:update` leva a melhoria | 7 | CT-36, CT-54 | **nenhum** | ❌ **sem cobertura** — as duas metades da cláusula (o comando avisar, e o passo manual documentado) estão sem caso |
| RQ-13 | `ViewUser` nasce com permissão própria, consultada | 5, 6 | CT-11, CT-13, CT-14, CT-15 | `tests/Kit/PageHeaderTest.php:[CT-11]:377` e `:402`; `tests/Tenancy/PageHeaderTenancyTest.php:[CT-15]:143` e `:336` | ⚠️ parcial — o **par** tem/não-tem existe nos dois painéis; **CT-13** (barreira consultada fora da tela) e **CT-14** (a ação some na listagem) não têm teste |
| RQ-14 | `filament/filament` em `^5.8.1`, caret | 1 | CT-35 | `tests/Kit/PacotesRodada2Test.php:[CT-32]:74` | ✅ coberta (a linha `5.8.0 recusa` do `Esquema` não é asserida) |

**Leitura da matriz**: nenhuma cláusula ficou órfã de *passo*; duas ficaram órfãs de *teste* — RQ-12 por
inteiro, e as metades de RQ-02/RQ-03/RQ-13 nomeadas acima.

---

## Correspondência CT ↔ teste

Legenda do estado: **integral** = todas as linhas do `Então` (e todas as linhas de `Exemplos:`) têm asserção ·
**parcial** = o cenário tem teste, mas falta linha ou linha de `Exemplos:` · **não implementado**.

| CT | O que afirma | Teste que o realiza | Estado |
|---|---|---|---|
| CT-01 | o recorte separa dentro de fora (5 regiões) | — | não implementado |
| CT-02 | região ausente devolve vazio | `tests/Kit/PageHeaderTest.php:[CT-02]:99` | parcial — mede sobre página real, não sobre HTML sintético; a linha "uma asserção de presença sobre esse recorte reprova" não existe |
| CT-03 | header do pacote nas seis telas | `tests/Kit/PageHeaderTest.php:[CT-03]:64` (2 linhas) · `tests/Tenancy/PageHeaderTenancyTest.php:[CT-03]:45` (2 linhas) e `:[CT-03]:303` (1 linha) | parcial — 5 das 6 linhas; falta `app \| EditUser` e a asserção "o heading não contém o nome da aplicação" |
| CT-04 | painel sem plugin segue com cabeçalho nativo | — | não implementado |
| CT-05 | a folha de estilo não é servida no `/infra` | — | não implementado |
| CT-06 | o header mostra **este** registro, nem o de outro nem o de quem olha | — | não implementado |
| CT-07 | o `data:` URI não chega ao `<img>` do header | `tests/Kit/PageHeaderTest.php:[CT-07]:172` | parcial — falta a linha de não-vacuidade ("contém `data:` **fora** do header") e a de ausência de `<img>` |
| CT-08 | o URL de storage chega ao `<img>` | `tests/Kit/PageHeaderTest.php:[CT-08]:131` | parcial — sem as linhas de `src` começar por `http` e de `alt` conter o nome |
| CT-09 | esquema do URL × nome decidem se o slot sobrevive (6 partições) | — | não implementado |
| CT-10 | organização sem logo cai nas iniciais tingidas | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-10]:388` | parcial — arranjo diverge (logo **nulo**, não logo apontando para arquivo inexistente) |
| CT-11 | a tela consulta `View:User` nos dois sentidos | `tests/Kit/PageHeaderTest.php:[CT-11]:377` (positiva) e `:[CT-11]:402` (negativa) | parcial — as duas linhas existem; falta o `fph-root` na positiva (armadilha nomeada pelo `04`) e o "não contém o nome" na negativa |
| CT-12 | o 403 tem saída | — | não implementado |
| CT-13 | a barreira existe fora da tela | — | não implementado |
| CT-14 | a ação de ficha some para quem não pode abri-la | — | não implementado |
| CT-15 | a mesma barreira no `/app` | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-15]:143` (linha `admin_app` com) e `:[CT-15]:336` (linha `admin_app` sem) | parcial — 2 das 3 linhas; falta `panel_user`, e falta o `fph-root`/"não contém o nome" que a legenda da matriz de R4 exige |
| CT-16 | URL direta para conta de outra organização não abre | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-16]:174` | parcial — sem a linha "não contém `fph-root`" e sem o par positivo no mesmo cenário |
| CT-17 | a negativa de quem governa a instalação é decidida fora da tela, e vira aviso sem PII | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-17]:207` (nega + par positivo) e `:[CT-17]:241` (o aviso) | parcial — o "**exatamente um** aviso" não é asserido (`shouldHaveReceived` sem `->once()`) e o `ator_id` não é conferido |
| CT-18 | com organização a consulta recorta; sem organização fecha, e o caminho permitido não escreve na trilha | — | não implementado |
| CT-19 | a ficha não é mais permissiva que a edição, e não loga no caminho permitido | — | não implementado |
| CT-20 | o que `getPageHeaderRecord()` devolve ao navegador | `tests/Kit/PageHeaderTest.php:[CT-20]:534` | **integral** — lista nominal fechada em `toBe()`, como o `04` pede |
| CT-21 | `$record` não aceita troca pelo navegador | — | não implementado |
| CT-22 | o estado do formulário da ficha não grava nada | — | não implementado |
| CT-23 | os métodos públicos do trait chamados como ação | — | não implementado |
| CT-24 | método tipado recusa argumento do navegador | — | não implementado |
| CT-25 | `data-fph-options` não carrega dado do registro | — | não implementado |
| CT-26 | as três APIs proibidas não aparecem em `app/`, e a varredura enxerga o que cita | `tests/Kit/PageHeaderTest.php:[CT-26]:210` (ausência) e `:[CT-26]:240` (controle do detector) | parcial — falta o **piso de 200 arquivos** varridos |
| CT-27 | o título escapa marcação e preserva texto (5 partições) | — | não implementado |
| CT-28 | as classes emitidas estão cobertas, com exceção nominal | `tests/Kit/PageHeaderTest.php:[CT-28]:294` | parcial, **com oráculo divergente** — afirma "toda classe emitida é `fph-*` ou `fi-*`", não a subtração emitidas − definidas com exceção nominal; só um dos dois pisos |
| CT-29 | o detector acha a agulha plantada (controle negativo de CT-28) | — | não implementado |
| CT-30 | a rota `view` existe, vem antes da `edit` e vale `/{record}` | `tests/Kit/PageHeaderTest.php:[CT-30]:355` (ordem, nos dois painéis) e `tests/Tenancy/PageHeaderTenancyTest.php:[CT-30]:359` (a URL servida sob o prefixo da organização) | parcial — o caminho é conferido pela URL gerada, não pela declaração |
| CT-31 | a ação de ficha navega em vez de abrir modal | — | não implementado |
| CT-32 | a ficha e a edição são telas distintas | — | não implementado |
| CT-33 | o registro de pacotes diz ADOTADO e onde | `tests/Kit/PacotesRodada2Test.php:[CT-33]:118` | **integral** — caso **herdado**, como o `04` determina |
| CT-34 | o oráculo da rodada anterior muda de veredito sem perder proteção | `tests/Kit/PacotesRodada2Test.php:[CT-34]:170` | **integral** — herdado |
| CT-35 | as constraints aceitam a série e recusam o major seguinte | `tests/Kit/PacotesRodada2Test.php:[CT-32]:74` (Filament) e `:[CT-35]:204` (page-header) | parcial — 8 das 9 linhas; `filament 5.8.0 → recusa` não é asserida |
| CT-36 | a doc de atualização manda ressemear (pt e en) | — | não implementado |
| CT-37 | a situação da conta no badge, nas 4 combinações | `tests/Kit/PageHeaderTest.php:[CT-49]:585` | parcial — só a linha 3 (`pendente=false`, `ativo=false` → "Inativo"), e sem a asserção de exclusividade |
| CT-38 | a origem da conta aparece traduzida | — | não implementado |
| CT-39 | a data de criação sai no fuso do aplicativo | — | não implementado |
| CT-40 | os dois estados da organização decidem dois badges | — | não implementado |
| CT-41 | nenhuma página sobrescreve `getHeader()` | — | não implementado |
| CT-42 | o detector acha o `getHeader()` plantado | — | não implementado |
| CT-43 | a ficha de conta removida não abre | — | não implementado |
| CT-44 | identificador inexistente não abre, mas a rota existe | — | não implementado |
| CT-45 | o header convive com os widgets de cabeçalho | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-45/CT-46]:93` | parcial — só a presença dos widgets na página; falta "nenhum widget dentro da região" e "as ações aparecem dentro dela" |
| CT-46 | o header fica acima das abas de relacionamento | `tests/Tenancy/PageHeaderTenancyTest.php:[CT-45/CT-46]:93` | parcial — a disjunção está provada; a **ordem** não (o próprio teste registra por que descartou o oráculo de posição) |
| CT-47 | o render hook da folha de estilo é escopado por painel | — | não implementado |
| CT-48 | cada painel recebe a própria instância do plugin | `tests/Kit/PageHeaderTest.php:[CT-48]:492` | parcial — objetos distintos sim; "configurar um não altera o outro" não |
| CT-49 | os slots do header preenchidos nas telas de edição | `tests/Kit/PageHeaderTest.php:[CT-49]:585` | parcial — 1 das 3 linhas (`admin \| EditUser`); falta a asserção "nenhuma região contém o nome de quem está autenticado" |
| CT-50 | exatamente seis páginas aplicam o trait | `tests/Kit/PageHeaderTest.php:[CT-02]:99` | parcial — só a **segunda** linha (a listagem responde 200 sem `fph-root`); a igualdade de conjunto não existe |
| CT-51 | os slots da ficha do `/app` ⊆ os do `/admin` | — | não implementado |
| CT-52 | — | — | **não existe**, deliberadamente (R13d do `04`) |
| CT-53 | a ficha mostra o registro fora do cabeçalho | `tests/Kit/PageHeaderTest.php:[CT-53]:433` | parcial — prova que o corpo não está vazio (seção "Vinculos"), sem subtrair a região do header nem contar entradas do infolist |
| CT-54 | o `kit:update` avisa sobre a dependência nova | — | não implementado |
| CT-55 | o papel `infra` não alcança a ficha de conta | — | não implementado |
| CT-56 | abrir a própria ficha também exige a permissão | — | não implementado |
| CT-B01 | os tokens do header mudam entre tema claro e escuro | — | não implementado |
| CT-B02 | o modo compacto reage à rolagem | — | não implementado |
| CT-B03 | console limpo nas telas que ganharam o header | — | não implementado |
| CT-B04 | a árvore de acessibilidade da ficha nova | — | não implementado |

**Totais.** Dos 55 CT: **3 integrais** (CT-20, CT-33, CT-34), **20 parciais**, **32 sem teste**.
Dos 4 CT-B: **0 implementados** — não existe `tests/Browser/PageHeader*.php`.

### Casos implementados que **não** realizam CT nenhum

| Teste | O que afirma | Por que não é CT |
|---|---|---|
| `tests/Kit/PageHeaderTest.php:[EXTRA]:469` | o plugin está registrado no `/admin` e no `/app`, e não no `/infra` | mede a **causa** (registro no painel), enquanto CT-04, CT-05 e CT-47 afirmam sobre a **resposta renderizada**. Fica verde com o render hook emitindo em todo painel |
| `tests/Tenancy/PageHeaderTenancyTest.php:[EXTRA]:273` | a ficha do `/app` não conta que a pessoa pertence a outra organização | o cenário mais próximo, CT-51, compara **rótulos de `fph-metadata`/`fph-badges`**; este afirma sobre o corpo da ficha (a seção "Vinculos" e o nome da outra organização) |

Os dois cobrem comportamento real e ficam. O que eles **não** fazem é dispensar CT-04, CT-05, CT-47 e CT-51.

---

## O que NÃO foi implementado, e por quê

### 1. Exige navegador — 4 casos

`CT-B01`, `CT-B02`, `CT-B03`, `CT-B04`.

O próprio `05` justifica o gate: token de cor só existe depois de o navegador resolver a cascata; o modo
compacto é Alpine + scroll e o HTML servido é idêntico com e sem ele; erro de console não muda status nem corpo;
árvore de acessibilidade não existe fora do navegador. Nenhum dos quatro arquivos que o `05` nomeia
(`tests/Browser/PageHeaderTemaTest.php`, `…CompactoTest.php`, `…TelasTest.php`, `…AcessibilidadeTest.php`)
existe.

### 2. Coberto por um oráculo mais barato e mais fraco, já existente — 4 casos

`CT-04`, `CT-05`, `CT-47` (o trio da fronteira do `/infra`) e `CT-51` (o `/app` não mostra mais que o `/admin`).

Os dois `[EXTRA]` da tabela acima afirmam algo na mesma direção, e é por isso que estes quatro nunca foram
escritos. **Não é equivalência**: o `[EXTRA]` do plugin fica verde com a folha de estilo emitida nos três
painéis, e o `[EXTRA]` do `/app` não olha para os rótulos do cabeçalho. A diferença está declarada no docblock
de cada um.

### 3. Sem motivo registrado — lacuna — 28 casos

`CT-01`, `CT-06`, `CT-09`, `CT-12`, `CT-13`, `CT-14`, `CT-18`, `CT-19`, `CT-21`, `CT-22`, `CT-23`, `CT-24`,
`CT-25`, `CT-27`, `CT-29`, `CT-31`, `CT-32`, `CT-36`, `CT-38`, `CT-39`, `CT-40`, `CT-41`, `CT-42`, `CT-43`,
`CT-44`, `CT-54`, `CT-55`, `CT-56`.

Nenhum documento desta wiki, e nenhum comentário dos dois arquivos de teste, registra tentativa ou impedimento
para qualquer um deles. A explicação de fundo é de **cronologia**, não de dificuldade: os testes foram escritos
antes de o `04` existir, então os cenários que o `04` acrescentou depois — em especial os oito da revisão
adversarial (`CT-49`…`CT-56`, dos quais só CT-49 tem teste) e a regra R6 inteira (`CT-21`…`CT-25`) — nunca
chegaram a ser considerados. Isso explica a ausência; não a justifica.

Dois subconjuntos merecem nome próprio, porque o `04` os marca como não-opcionais:

- **Controles negativos de detector** — `CT-01`, `CT-29`, `CT-42`. O `04` abre com a regra R1 justamente porque a
  rodada anterior produziu 46 casos verdes sobre nada. Hoje `regiaoDoCabecalho()` tem **um** controle
  (`[CT-02]`), e os detectores de CSS e de `getHeader()` não têm nenhum.
- **A direção "não aconteceu" do log** — `CT-18` e `CT-19`. O mutante M57 (o `warning` emitido na entrada do
  método, antes do ramo) atravessa a suíte atual inteira: `[CT-17]:241` só prova que o aviso acontece no
  caminho negado.

### 4. Não é caso — 1

`CT-52` não existe por decisão do `04` (R13d): o achado foi fechado reescrevendo CT-18 e CT-19, e o número ficou
vago de propósito.

---

## Renumeração aplicada

`tests/Kit/PageHeaderTest.php`:

| Rótulo antigo | Rótulo novo | Linha |
|---|---|---|
| CT-01 | **CT-03** | 64 |
| CT-02 | **CT-02** (coincidência, não identidade) | 99 |
| CT-03 | **CT-08** | 131 |
| CT-04 | **CT-07** | 172 |
| CT-05 | **CT-26** | 210 |
| CT-06 | **CT-26** | 240 |
| CT-07 | **CT-28** | 294 |
| CT-08 | **CT-30** | 355 |
| CT-09 | **CT-11** | 377 |
| CT-10 | **CT-11** | 402 |
| CT-11 | **CT-53** | 433 |
| CT-12 | **EXTRA** | 469 |
| CT-13 | **CT-48** | 492 |
| CT-14 | **CT-20** | 534 |
| CT-15 | **CT-49** | 585 |

`tests/Tenancy/PageHeaderTenancyTest.php`:

| Rótulo antigo | Rótulo novo | Linha |
|---|---|---|
| CT-12 | **CT-03** | 45 |
| CT-13 | **CT-45/CT-46** | 93 |
| CT-14 | **CT-15** | 143 |
| CT-15 | **CT-16** | 174 |
| CT-16 | **CT-17** | 207 |
| CT-17 | **CT-17** | 241 |
| CT-18 | **EXTRA** | 273 |
| CT-19 | **CT-03** | 303 |
| CT-20 | **CT-15** | 336 |
| CT-21 | **CT-30** | 359 |
| CT-22 | **CT-10** | 388 |

Referências cruzadas atualizadas junto: os docblocks dos dois arquivos, `wikis/receitas.md:307`
(`[CT-13]` → `[CT-45/CT-46]`) e `wikis/receitas.md:319` (`[CT-05]` → `[CT-26]`). `tests/Pest.php:1084` e
`wikis/receitas.md:356` citam `[CT-02]` e **seguem corretos** — aquele caso manteve o número.
`tests/Kit/PacotesRodada2Test.php` não foi tocado: a numeração dele pertence à wiki `estudo-de-pacotes-rodada-2`.

### Achados da reconciliação (reportados, não corrigidos)

1. **Citação morta, corrigida no comentário**: o docblock de `[CT-53]` apontava a outra metade da fronteira para
   `tests/Tenancy/ViewUserTenancyTest.php` — **arquivo que não existe**. Passou a apontar
   `tests/Tenancy/PageHeaderTenancyTest.php:[EXTRA]`.
2. **`[CT-11]`, linha positiva, tem oráculo fraco**: o `04` avisa em `## Armadilhas` que ela "tem de afirmar
   `fph-root`, senão fica verde numa tela sem cabeçalho". O teste usa `assertSee` do nome, que a barra de título
   e as migalhas também satisfazem. **Não alterado** — é mudança de lógica.
3. **`[CT-17]`, o do log, passa com dez avisos**: `shouldHaveReceived('warning')` sem `->once()`. O `04` nomeia
   isso ("era 'um aviso é registrado', que passa com dez"). **Não alterado.**
4. **`[CT-28]` mede outra coisa que o CT-28 desenhado**: namespace em vez de subtração com exceção nominal. O
   teste declara e defende a escolha; a divergência fica registrada aqui para o quality gate decidir.
5. **`[CT-10]` diverge no arranjo**: o cenário quer logo apontando para arquivo **inexistente** (é o `exists()`
   de `Tenant::urlDaLogo()` que ele exercita); o teste usa logo nulo.

---

## Achados fechados depois da reconciliação

Três dos cinco achados acima eram defeito de verdade e foram fechados; dois ficam declarados.

| # | Achado | Desfecho |
|---|---|---|
| 1 | citação para arquivo inexistente | **fechado** na reconciliação |
| 2 | `[CT-11]` com `assertSee` do nome como oráculo único | **fechado** — passou a exigir `regiaoDoCabecalho()` não vazia, o nome DENTRO dela, e a seção "Conta" da ficha |
| 3 | `[CT-17]` passa com dez avisos | **fechado** — `->once()` acrescentado, com o motivo no comentário |
| 4 | `[CT-28]` mede namespace, não subtração | **declarado** — ver abaixo |
| 5 | `[CT-10]` usa logo nulo, não órfão | **fechado** — passou a usar `logos/apagada.png`, que exercita o `exists()` de `Tenant::urlDaLogo():138` |

**Sobre o `[CT-28]`**, a divergência é deliberada e o desenho estava estrito demais. O oráculo
desenhado era "toda classe `fph-*` emitida tem regra na CSS". Medido: `fph-schema` é emitida e
**não tem regra** — é um invólucro estrutural que não precisa de estilo. Reprovar por causa dele
seria ruído, e o risco que a `.ai/rules/css-filament.md` descreve é outro: a **utilitária crua**
(`flex`, `mt-4`), que o kit não tem de onde carregar. O oráculo implementado é o namespace, que é
o que de fato separa "o pacote se basta" de "o pacote depende de um Tailwind que não existe aqui".

---

## Achados do `/code-review` — o que entrou

Sete confirmados. Cinco fechados, dois declarados.

| # | Achado | Sev. | Desfecho |
|---|---|---|---|
| 1 | `ViewAction` visível na Lixeira levava a **404**, e a entrada `deleted_at` da ficha era código morto | Média | **fechado** — ação oculta em registro excluído, entrada removida, os dois com o motivo escrito; regressão travada por caso |
| 2 | o clique na LINHA mudou de destino (edição → ficha) sem registro nem teste | Baixa/Média | **fechado** — comportamento mantido (é o desejado) e travado por caso, para que removê-lo seja decisão |
| 3 | `[CT-10]` não matava o mutante que o docblock declarava matar | Média | **fechado** — oráculo refeito com três organizações; mutante morto, medido |
| 4 | os dois `ViewAction::make()` não tinham oráculo nenhum | Baixa | **fechado** — caso novo |
| 5 | a guarda de CSS lia o vendor, não o arquivo servido | Baixa | **fechado** — caso novo compara os md5 |
| 6 | `User::getFilamentAvatarUrl()` não confere `exists()`, ao contrário de `Tenant::urlDaLogo()` | Baixa/Média | **declarado** — ver abaixo |
| 7 | 13 citações reprovavam a conferência mecânica da rule | Baixa | **fechado** — 91/91 conferem |

### O achado 6, declarado com o motivo

`User::getFilamentAvatarUrl():888` devolve `Storage::disk('public')->url($this->avatar_url)` **sem
checar existência**, enquanto `Tenant::urlDaLogo():138` confere `exists()` e documenta por quê
(restore de banco sem o storage, `migrate:fresh` com uploads antigos). São duas respostas diferentes
para a mesma pergunta, e o cabeçalho de usuário herda a resposta pior: `<img>` quebrado onde o
desenho pede iniciais.

**Não corrigido nesta entrega**, por dois motivos que não se anulam:

1. É **pré-existente** — o método já era assim, e já é consumido pelo menu do usuário e pelo widget
   `UltimosUsuariosCadastrados`. A feature expõe o defeito, não o cria.
2. A correção põe uma chamada de `Storage::exists()` num caminho renderizado **em toda página** (o
   avatar do menu do usuário). Isso é decisão de desempenho que merece medição própria, e resolvê-la
   dentro do escopo de outra feature é a classe de erro que o quality gate da rodada anterior
   nomeou: *"a remediação fechou o ponto citado e não a classe"*.

Dívida registrada, com o caminho: ou `exists()` com cache por request, ou um accessor que o header
use e o menu não.

---

## Verificação Final

### Conferência mecânica de citações — **91/91 ok**

Conferida por símbolo, sem lista escolhida à mão, como manda `.ai/rules/specs.md`. Duas formas,
dois scripts:

| Forma | Conferidas | Erro |
|---|---|---|
| `{path}:{símbolo}:{linha}` | 34 | 0 |
| `Classe::método():linha` | 65 | 0 |

Antes da conferência havia **40 citações erradas** (16 na primeira forma, 24 na segunda) — todas com
a afirmação **certa** e o número velho, que é exatamente o padrão que a rule descreve. Sete casos
o script não resolve sozinho (dois arquivos com o mesmo basename, um por painel) e foram conferidos
à mão contra o arquivo certo.

> Isto é a rule registrada na v0.35.0 pegando quem a escreveu, na primeira feature depois dela. O
> valor dela não é ter evitado o erro: é ter tornado o erro **detectável em segundos**.

**E aconteceu de novo, dentro da mesma feature.** Acrescentar `TenantResource::infolist()` para
fechar o achado BP-03 deslocou **8 âncoras** em quatro arquivos — `getPages()`, `canAccess()` e
`getRelations()` do mesmo resource. A segunda conferência achou as oito e corrigiu. É a prova
prática do que a rule afirma: o número envelhece pela sua própria edição, e conferir por lista
escolhida à mão não teria achado, porque essas três não estavam na lista de quem escreveu.

### Matriz de mutantes — medida, não alegada

| Mutante | Caso | Resultado |
|---|---|---|
| `->avatar()` recebendo `data:` URI incondicional | `[CT-08]` | **morto** |
| `->initials()` removido | `[CT-07]` | **morto** |
| `getFilamentAvatarUrl()` → `Filament::getUserAvatarUrl()` | — | **sobrevive** (equivalente: mesma saída renderizada) |
| `TenantHeader::paleta()` → paleta cravada | `[CT-10]` | **morto** |
| `[CT-34]` na forma antiga, com o pacote já no `require` | — | **sobrevivia** — era o defeito, agora corrigido |

### Defeito de processo registrado

Os scripts de edição usados nesta feature gravaram **CRLF** em 16 arquivos rastreados. O Git
normaliza no commit, então os blobs ficaram corretos e **nenhum diff acusou** — mas a árvore de
trabalho ficou divergente, e `SiteDeDocumentacaoTest::[CT-20]` reprovou porque
`frontMatterDe():56` casa `/\A---
/`, que CRLF não satisfaz. O sintoma ("duas páginas sumiram da
navegação") não aponta a causa. Normalizado; vale como aviso para a próxima edição em lote.

### Ciclo do Blueprint (RQ-09) — com controle positivo

| Passo | Resultado |
|---|---|
| `composer bp:on` | `filament/blueprint ^2.4` instalado |
| **guard do kit com o Blueprint ligado** | **3 de 6 VERMELHOS**, com as três mensagens certas (`composer.json`, `repositories`, `composer.lock`) — é o controle positivo: o guard detecta |
| auditoria de aderência | 9 achados; ver abaixo |
| `composer bp:off` | removido |
| **guard com o Blueprint desligado** | **6/6 verde**, árvore limpa |

O guard rodado só no estado final provaria apenas que o arquivo está limpo — não que ele acusaria
sujeira. Rodá-lo nos dois estados é o que o torna oráculo.

### Auditoria do Filament Blueprint

Perfil: auditoria de segurança da skill `filament-security-audit` (catálogo A–E completo) mais
`checklist.md` item a item. **Nenhum furo de segurança novo**: A=0, B=0, C=0, D=0, E=0.

| ID | Sev. | Achado | Desfecho |
|---|---|---|---|
| BP-01 | Média | entradas de infolist a 25% da largura ("Nested Columns Too Narrow") | **fechado** — `columns(1)` nos dois `UserInfolist` |
| BP-02 | Baixa | metade direita da ficha do `/app` vazia | **fechado** pelo mesmo `columns(1)` |
| BP-03 | Baixa/Média | `TenantResource` sem `infolist()` → `ViewTenant` com formulário desabilitado sob cabeçalho rico | **fechado** — entra `TenantInfolist` |
| F-01 | Baixa | `getPageHeaderRecord()` em 6 Pages, oráculo em 1 — e a fronteira a proteger é a do `/app` | **fechado** — par escrito |
| F-02 | Baixa | 8 métodos públicos do trait sem caso | **fechado** — e a dedução da auditoria estava errada, ver abaixo |
| BP-04 | Baixa | 8 *record actions* no `/admin` sem `ActionGroup` | **declarado** — ver abaixo |
| BP-05 | Média | 32 dos 55 CTs sem implementação | **declarado** — já é esta seção |
| BP-06 | Info | casos de renderização que o Blueprint desaconselha | sem ação — rule do projeto vence, com motivo |
| F-03 | Info | `data-fph-options` não carrega PII | **fecha por inspeção**: `HeaderOptions` é `final readonly` só com campos de layout |

**A dedução da auditoria que a medição derrubou.** F-02 previa que `headerSchema`,
`defaultHeaderSchema` e `pageHeaderOptions` estourariam `TypeError` por terem parâmetro tipado,
produzindo 500. **Não estouram** — os três respondem. E a medição achou o que a dedução não viu:
`defaultHeaderSchema` devolve `{"model": {…}}`, o registro serializado. Não é furo (mesmo registro
autorizado, mesmas 12 chaves, sem `password` nem `remember_token`), mas é superfície que ninguém
sabia que existia. Agora está travada, com controle de não-vacuidade.

> Vale registrar a forma do erro: a auditoria marcou o item como **deduzido, não reproduzido**, e
> foi honesta nisso. Foi essa honestidade que fez a medição acontecer. Achado marcado como
> reproduzido quando não foi é o que faz a próxima pessoa não conferir.

**BP-04, declarado.** `UserResource(Admin)::table()` passou de sete para oito ações por linha, e o
`actions.md` pede `ActionGroup` acima de três. Não agrupado nesta entrega: reorganizar a barra de
ações de uma listagem que o requisito não pediu para mudar é alargar escopo, e a oitava ação é a
única que esta feature acrescenta. Fica como dívida nomeada.

**O desvio de colunas é da base, não desta entrega.** `AiRunInfolist:21` e `TenantForm:34` têm o
mesmo padrão `columns(2)` aninhado. Corrigidos aqui só os do diff; os outros dois ficam registrados
para serem corrigidos juntos, senão a próxima auditoria os acha de novo. O lugar natural do enforço
é `tests/Kit/AderenciaAoBlueprintTest.php`, que hoje varre construção depreciada e **não** varre
aritmética de coluna — que é justamente o defeito que passa porque a suíte fica verde e a tela fica
feia.

### Suítes

| Suíte | Resultado |
|---|---|
| `composer test` | **2502/2502**, 9787 asserções — corrida final, com tudo fechado |
| `tests/Kit/PageHeaderTest.php` | 24/24 |
| `tests/Tenancy/PageHeaderTenancyTest.php` | 13/13 |
| `tests/Kit/PacotesRodada2Test.php` | 13/13 |
| Pint · PHPStan · FilaCheck | verde · 0 erros · 17/17 regras |


