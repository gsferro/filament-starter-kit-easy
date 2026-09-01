# Casos de Teste — O badge de papel reflete a organização ativa

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · ADRs: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**. Do `01` vieram apenas paths, stack e a tabela `## Superfície de UI`;
> do `02`, a separação exibição × acesso (ADR-01) e a decisão de ausência (ADR-02).
> Nenhum cenário foi escrito olhando a implementação da correção — que ainda não existe.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| **A1 — o papel exibido na organização ativa** (RQ-01, RQ-02, RQ-03) | 3 | 2 | 6 | **padrão** |
| **A2 — o que NÃO pode mudar: painéis sem organização e acesso** (RQ-04, RQ-05) | 3 | 3 | 9 | **completo** |

**A1** — P=3: a regra atravessa três mecanismos que só se encontram em runtime (a pivot
`model_has_roles.team_id` do spatie com `permission.teams`, a organização corrente do Filament e
a relação `papeisEmQualquerContexto()`, que existe justamente por **não** filtrar). I=2: badge
errado é informação enganosa e retrabalho de suporte, não concessão de acesso.

**A2** — I=3 porque uma regressão aqui é **autorização**, não cosmética: o mesmo método é o
ponto de leitura de papel do kit, e `.ai/rules/models.md` registra que o único caso que hoje
guarda a armadilha "`roles()` no lugar de `papeisEmQualquerContexto()`" é exatamente o caso desta
suíte que **muda de oráculo** nesta entrega. Mexer no oráculo dele sem repor a guarda deixa a
regra da rule sem nenhum teste. P=3 pelo mesmo motivo: infra compartilhada com quatro chamadores.

- **Técnicas aplicadas**: partição de equivalência, tabela de decisão (organização × papel, e
  URL × sessão), matriz papel × painel — entre painéis e **dentro do mesmo contexto** —, sequência
  de dois eventos (2-switch) para a troca de organização, partição exaustiva do contexto para o
  `master_global`, partição das três classes de `team_id`, e mapa de consumidores para a restrição
  negativa do RQ-05.
- **Cenários**: 15 (1 reaproveitado da suíte existente) · **Regras**: 7 — **três delas deveriam ser seis**, ver a escalação abaixo · **Mutantes previstos**: 31 · **Sem matador**: 1 (M7) · **Matador parcial**: 3 (M6, M19, M20) · **Lacunas declaradas**: 3

### Revisão adversarial — rodada 1 executada, achados fechados

Executada por agente independente, com o contrato da skill: entrada apenas o `00` e este arquivo;
proibido ver o `01`, o `02` e qualquer código. **13 achados**, todos fechados abaixo. Nenhum
morreu como observação.

| # | Achado | Virou |
|---|---|---|
| I1 | papel do painel `app` gravado no **contexto global** (`team_id = 0`) — partição declarada no SFDIPOT e nunca instanciada. Um filtro `whereIn(team_id, [org, 0])` passava por todo o conjunto | **CT-09** `@premissa` + pergunta nova (R7) |
| I2 | organização lida da **sessão** em vez do request. CT-02 usava visita direta a cada URL, o que mantinha as duas fontes sempre concordando — a premissa "manda a URL" nunca era falsificada | **CT-10** `@premissa` (R2) |
| I3 | o filtro por organização entra e o **filtro por painel sai**. Nenhuma persona tinha dois papéis de painéis **diferentes** no **mesmo** contexto | **CT-11** (R1) — e o oráculo vem do `00`, que afirma isso literalmente |
| I4 | dois papéis do **mesmo** painel na **mesma** organização: a cardinalidade 2+ só era exercitada **entre** organizações, e o `->first()` sem ordenação continuava decidindo | **lacuna declarada** com o que foi tentado (R1) |
| I5 | o contexto vira default implícito e **vaza para os outros consumidores** da leitura de papéis. O perfil declara quatro chamadores e o conjunto observava um | **CT-12** (R6) |
| O1 | CT-08 tinha uma organização só: passava com **zero** filtro, isto é, com o defeito de hoje intacto. E o `Dado` concedia a membresia, que é justamente a condição extra de M14 | CT-08 **reescrito** com duas organizações e sem a membresia na organização desativada |
| O2 | CT-07 afirmava só status; "sucesso"/"negado" não distingue 403 de redirect, e passava com a página sem cabeçalho | CT-07 **reescrito**: resultado concreto + o cabeçalho tem de renderizar nas linhas de sucesso |
| O3 | CT-06 não nomeava usuário, painel nem rótulo — "como antes desta mudança" é referência a arquivo, não oráculo | CT-06 **reescrito** com as linhas concretas |
| O4 | CT-04 sem par "não exibe" e sem status nas quatro linhas | CT-04 **reescrito** |
| O5 | CT-03: "não exibe nenhum badge" sem âncora não mata M11 (badge vazio, ou com o traço) | CT-03 **reescrito** com a âncora do `title`, que só existe quando o badge renderiza |
| O6 | "Admin" e "Infra" fora do vocabulário aceito na `## Fronteira com o Plano` | linha acrescentada à tabela da Fronteira |
| O7 | nenhum `Então` dizia **onde** o rótulo aparece — `assertSee` de substring na resposta inteira | **regra de âncora global**, no Setup, valendo para todo cenário |
| O8 | CT-02 e CT-05 escondiam o primeiro request no `Dado`, e CT-02 **afirmava** o resultado do primeiro giro onde nada o verifica | os dois `Dado` viraram arranjo puro, sem oráculo embutido |

**Correção de honestidade do índice**: a linha "Mutante sem matador: nenhum" era **falsa em quatro
casos** apontados pela revisão — M14, M19, M20 e M24 tinham matador alegado que não mata. Os
quatro estão corrigidos nas tabelas das regras, e o índice agora declara o número certo.

**Achados de dimensão que viraram pergunta, não cenário**: impersonação (já era pergunta; a
revisão reforça que a premissa não tem guarda em suíte nenhuma) e `/app` sem organização corrente
(declarado inalcançável **por leitura do provider**, não por cenário — se a premissa cair, o
fail-closed do RQ-01 fica sem teste). Ambos estão nas perguntas abaixo.

**Terceira organização**: RQ-02 diz "mais de +1" e o conjunto parava em duas. CT-01 ganhou uma
terceira linha — uma implementação que resolvesse por par ("a corrente e a outra") não era
falsificada por nada.

### Revisão adversarial — rodada 2 executada. Teto atingido, com escalação

Executada por um segundo agente independente, mesmo contrato, apontando para os cenários novos e
os oráculos reescritos. **Achou lacuna de segunda ordem em todos eles.** O que foi fechado:

| Achado da rodada 2 | Virou |
|---|---|
| **W2 / M30** — CT-11 instanciava um único painel concorrente (`admin`); uma lista negativa `!= 'admin'` produzia o mesmo conjunto que `= 'app'` | CT-11 virou Esquema com **`infra`** na segunda linha — o terceiro valor da coluna |
| **M5 no arranjo de CT-09** — a linha `globex` era o único lugar do conjunto com dois papéis do painel `app` visíveis à consulta, e não herdou o par "não exibe" | CT-09 ganhou a coluna `ausente` e a linha de sanidade |
| **CT-04 com negação vácua** — Marta não tem `panel_user` em lugar nenhum, então `não exibe "Painel App"` não podia falhar sob implementação alguma | virou `o **único** badge é "Administrador Geral"` |
| **W4 / M14** — tirar a membresia de CT-08 tornou o cenário **inexecutável**: sem vínculo o request morre em `canAccessTenant()`, pela regra que o próprio CT-03 enuncia. O fechamento da rodada 1 trocou "mutante vivo" por "cenário irrealizável" | a membresia voltou a CT-08, e M14 foi para **CT-13**, onde vínculo e papel divergem em organizações **ativas** |
| **W5 / M27** — CT-12 escolhia a única célula em que o vazamento é invisível: o `/admin` é justamente onde não há organização corrente, então o default implícito não dispara | CT-12 passa a exigir que Caio tenha **aberto a organização antes**, e virou cenário único com oráculo no campo de papéis da ficha |
| **M24 / M26** — "uma tela restrita dentro da Acme" não nomeava tela nem permissão; o oráculo "200" passava se a tela escolhida não fosse gated | virou **CT-14**, com a tela e a permissão nomeadas |
| **CT-08 na voz passiva** — "é aberto para ela" escondia o ator e era onde a inexecutabilidade se disfarçava | ator explícito, mais a linha de sanidade |
| Cenários que afirmavam ausência sem a linha "responde 200 / o cabeçalho renderizou", contrariando a regra de âncora que o Setup declara global | corrigidos em CT-04, CT-08, CT-09, CT-11 |

### Mutantes que a rodada 2 provou SEM MATADOR — e continuam assim

Estes **não** foram fechados, e é deliberado: fechá-los exige decisão de quem mantém a wiki.

| Mutante | Por que o matador não mata | Estado |
|---|---|---|
| **M7** — o badge lê a organização da sessão | CT-10 usa **visita direta**, e o próprio arquivo já registra que a visita direta grava a sessão antes do render: as duas fontes voltam a concordar. CT-10 reproduz o defeito que foi criado para fechar | **lacuna declarada**. Falsificar exige que a divergência sobreviva ao request — outro ator ou outro painel escrevendo a chave. Provavelmente **não é falsificável neste kit**, e nesse caso a premissa "manda a URL" fica sem prova |
| **M6** — memoização | CT-02 mata só as variantes **entre** requests. Memoização por instância (`once()`, propriedade) morre com o request e é invisível em todo o conjunto | **matador parcial**, declarado |
| **M20** — "sem organização" vira contexto global explícito | CT-06 só mata a variante que aplica o filtro **também** com `permission.teams` desligado. A variante idiomática — guardar o filtro atrás de `config('permission.teams')` — nunca é executada | **matador na variante menos plausível**, declarado |
| **M19** — troca para a `roles()` do spatie | a cadeia CT-05 → CT-09 funciona, mas **CT-09 é `@premissa`**. Se a decisão sobre `team_id = 0` inverter, R5 perde o único matador do flanco que `.ai/rules/models.md` registra | **dependência de premissa**, agora declarada |

### Lacuna de dimensão nova: `guard_name`

A varredura SFDIPOT nomeia **três** colunas na chave de leitura — `painel`, `team_id` e
`guard_name`. A rodada 1 particionou o `team_id` em três classes; a rodada 2 mostrou que
`guard_name` continua **declarado e nunca instanciado**, que é literalmente a mesma classe de
omissão do achado I1. Uma reescrita que perdesse o filtro de guard passa por todo o conjunto.

**Estado**: pergunta, abaixo. Se o kit tem guard único por contrato, isso precisa estar **escrito**
— e aí a coluna sai do SFDIPOT. Se não tem, entra um cenário.

### Escalação — o teto de 2 rodadas foi atingido com achado estrutural

A skill é explícita: se a segunda rodada ainda traz achado **estrutural**, o problema não é o
conjunto, é a regra. Trouxe, em três regras. **Não foram desdobradas aqui** porque desdobrar
renumeraria toda a rastreabilidade, e a decisão é de quem mantém a wiki. Fica registrado:

| Regra | Por que deveria ser duas | Sintoma que isso já produziu |
|---|---|---|
| **R2** | "não memorizar o papel entre requests" (sequência) e "qual fonte define a organização corrente" (tabela de decisão sobre origem do dado) têm **pré-condições de falsificação incompatíveis** | CT-10 nasceu com o verbo de CT-02 — "abre diretamente" — e por isso **não falsifica M7** |
| **R3** | a face "ausência de papel" **exige** a membresia (senão o request morre na porta); a face "estado da organização" foi reescrita para **retirá-la**. Alcançabilidade oposta | CT-08 ficou inexecutável por uma rodada inteira, sem que a colisão com CT-03 fosse notada |
| **R6** | "o acesso do próprio usuário não muda" e "os outros consumidores não mudam" não compartilham persona, painel, oráculo nem técnica | CT-12 nasceu como tabela de duas linhas sem nada em comum, e escolheu a célula em que o defeito é invisível |
| **R7** | sinal mais fraco: os dois eixos (classe de `team_id` × coluna `painel`) têm arranjos e mutantes disjuntos | CT-11 com um único painel concorrente, CT-09 sem o par de negação — e a terceira coluna, `guard_name`, sem dono |

**Recomendação**: desdobrar R2, R3 e R6 antes de escrever o arquivo de teste. Enquanto forem uma
regra cada, o cenário da segunda face continuará sendo escrito com o verbo da primeira — que é
exatamente o mecanismo que produziu as três lacunas acima.

### Divergências entre esta skill e as Project Rules do projeto

| Ponto | Quem venceu | Motivo |
|---|---|---|
| Camada "mais barata" para o badge | **`.ai/rules` + a suíte ancestral**: request de página cheia | O badge é emitido por **render hook do layout**, e teste de componente Livewire não passa pelo layout. Não existe camada mais barata que o prove — a "mais barata" aqui é HTTP, e não componente |
| `--parallel` na verificação | o `01-plano-acao.md` | Já é o comando que o kit usa; nenhum CT aqui usa browser, então não há o conflito de `--parallel` com CT-B |

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários |
|---|---|---|
| **S**tructure | `app/Models/User.php` (a leitura do papel do painel) e `resources/views/filament/perfil-indicator.blade.php` (o badge). Sem migration, sem model novo, sem config, sem rota, sem comando | — |
| **F**unction | escolher **qual** papel exibir; o atalho do `master_global`; a ausência (nada renderizado); e a função que **não** existe aqui — decidir acesso | CT-01, CT-03, CT-04, CT-07 |
| **D**ata | `model_has_roles.team_id` (0 = contexto global), `roles.painel`, `roles.guard_name`. O `team_id` tem **três** classes, não duas: organização A, organização B e o **0** (contexto global) — a terceira é instanciada para o painel `app` em CT-09, e era o buraco que a revisão adversarial encontrou. Cardinalidade do papel do painel `app` por usuário: **0, 1 e 2+**, e o 2+ vale em dois eixos — **entre** organizações (CT-01) e **dentro** de uma (lacuna declarada). `roles.id`, que é o que o `->first()` sem `ORDER BY` acaba usando, é dado **incidental** que não pode aparecer em nenhum oráculo | CT-01, CT-03, CT-09 |
| **I**nterfaces | o menu do usuário, em qualquer tela dos três painéis, por request HTTP autenticado. Sem API, sem comando artisan, sem job, sem webhook, sem import. **Mas a leitura de papéis que o badge usa tem outros consumidores** — o cadastro de usuários do `/admin` entre eles —, e eles são interface da mesma regra pelo lado de RQ-05 | CT-01…CT-12 |
| **P**latform | **`config('permission.teams')` é o eixo de plataforma da feature**: ligado, a coluna `team_id` existe e o filtro tem sentido; desligado, não há organização nem coluna. Isso separa fisicamente as suítes (`Tests\TestCase` × `Tests\TenancyTestCase`, decidido em `createApplication()`, antes das migrations). Banco: SQLite `:memory:` — e o `->first()` sem ordenação é **não determinístico por contrato**, o que é parte da razão de o defeito parecer intermitente | CT-06 (single-tenant), demais (tenancy) |
| **O**perations | o uso real do relato: uma pessoa convidada para duas organizações com papéis diferentes. Também: `master_global` operando qualquer organização; e quem entra numa organização em que não tem papel (possível, porque o acesso não depende da organização — RQ-05) | CT-01, CT-03, CT-04 |
| **T**ime | **sequência**, não relógio: trocar de organização dentro da mesma sessão, sem novo login (RQ-03). Nenhum agendamento, expiração, timezone ou DST — a feature não lê data nem hora, e `updated_at` não participa | CT-02 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — Com organização aberta, o badge do `/app` mostra o papel que a pessoa tem **naquela** organização | A1 (padrão) | RQ-01, RQ-02 | tabela de decisão (organização × papel) | CT-01 |
| **R2** — Trocar de organização na mesma sessão troca o badge, sem novo login | A1 (padrão) | RQ-03 | sequência de dois eventos (2-switch) | CT-02 |
| **R3** — Sem papel do painel na organização aberta, **nada** é renderizado — mesmo tendo papel em outra; e o estado da organização não interfere | A1 (padrão) | RQ-01 + ADR-02 | partição de ausência + estado × operação | CT-03, CT-08 |
| **R4** — O `master_global` tem badge em qualquer painel e em qualquer organização | A2 (completo) | RQ-04 + premissa do `00` | partição exaustiva do contexto | CT-04, CT-04b |
| **R5** — `/admin` e `/infra` não mudam: o badge continua saindo do papel do contexto global, **haja ou não** organização corrente fixada no request | A2 (completo) | RQ-04 | matriz painel × contexto corrente | CT-05, CT-06 |
| **R6** — O acesso ao painel não muda, e os outros consumidores da leitura de papéis também não | A2 (completo) | RQ-05 | matriz papel × painel | CT-07, CT-12 |
| **R7** — O contexto global e a coluna de painel continuam decidindo: papel sem organização não vira badge de organização, e papel de outro painel não vira badge do `/app` | A2 (completo) | RQ-01, RQ-04 | partição do contexto + matriz papel × painel no mesmo contexto | CT-09, CT-11 |

Toda `RQ` do `00` gerou ao menos uma regra. R4, R5 e R6 são **restrições negativas** — e por isso
têm cenário próprio: restrição sem cenário é a linha que a Matriz de Rastreabilidade marca ✅ sem
nada por trás.

**Técnica escalada acima do perfil da área**: nenhuma. R1 está em área `padrão` e usa tabela de
decisão em vez de EP simples porque o domínio é **condicionado** (o papel exibido depende da
organização aberta) — é a técnica que o passo 3 manda usar nesse caso, não um escalonamento.

---

## Fronteira com o Plano

| Item que veio do `01`/`02` | Recusado como oráculo porque | Destino |
|---|---|---|
| A assinatura `papelDoPainel(string $painel, ?int $contexto = null)` | escolha de implementação — a ADR-01 escolheu parâmetro opcional em vez de método novo, e o requisito não fala de método nenhum | detalhe; **nenhum `Então` deste arquivo afirma sobre a assinatura** |
| `wherePivot($this->colunaDeTeam(), $contexto)` como mecanismo | escolha de implementação. A alternativa 4 da ADR-01 — "ordenar o `->first()`" — produziria o mesmo resultado em vários arranjos, e é justamente o mutante M2 | detalhe |
| `$contextoDoBadge = filament()->getTenant()?->getKey()` na view | escolha de implementação | detalhe |
| Passo 3 do `01` (frase no README) | documentação, não comportamento observável do sistema | fora dos CT |
| Os rótulos `Admin` e `Infra` | **aceito como oráculo, com âncora obrigatória**: são o Title Case da própria chave do papel, e por isso substrings do layout dos painéis homônimos. Só valem colados ao `title` do badge | oráculo (herdado, ancorado) |
| Os rótulos "Painel App", "Administrador App", "Administrador Geral" | **aceito como oráculo**, e não por vir do `01`: eles vêm de `App\Support\Papeis`, fixados pela wiki ancestral e já cobertos pela suíte existente. Este arquivo os herda como **vocabulário de tela**, não os inventa | oráculo (herdado) |

**Consequência prática desta fronteira**: todo cenário deste arquivo tem oráculo em **o que
aparece na tela** ou em **o código da resposta HTTP**. Nenhum tem oráculo no retorno de um método,
porque o método com contexto é invenção do plano. Se a implementação escolher outro caminho —
uma segunda função, um cache no request, qualquer coisa —, estes cenários continuam válidos sem
uma linha de mudança. Esse é o teste da fronteira.

### Perguntas para o `00-requisito.md`

> O `00` é imutável nesta entrega e não foi editado. Bloco pronto para colagem em
> `## Ambiguidades e Perguntas Abertas`.

- **A premissa "no `/app` sem organização selecionada, sem badge" descreve um estado inalcançável neste kit.**
  O `AppPanelProvider` registra `->tenant(Tenant::class, slugAttribute: 'slug')` **sem**
  `->tenantRegistration()`, e toda rota do painel é `/app/{tenant}`: não existe tela do `/app`
  servida sob o layout do painel sem organização corrente. O único lugar em que "sem organização
  corrente" é observável são o `/admin` e o `/infra` — que já são a R5.
  - **Assumido**: a premissa é redundante com a R5 e **não gera cenário próprio**. Nenhum CT a
    exercita; nenhum CT a contradiz.
  - **Se negado** (existe uma tela do `/app` sem organização corrente): entra um CT, e a
    `## Superfície de UI` do `01` está incompleta.

- **`master_global` que TAMBÉM tem papel do painel numa organização: o badge no `/app/{org}` diz "Administrador Geral" ou o papel daquela organização?**
  O `00` responde que o `master_global` "não muda nada", mas o caso do acúmulo não está escrito, e
  ele é plausível — quem mantém a instalação costuma ser convidado para as organizações.
  - **Assumido**: **"Administrador Geral"** — o atalho vence, porque é resolvido antes de qualquer
    pergunta de painel ou de organização. É o que CT-04b afirma, e por isso ele está `@premissa`.
  - **Se negado**: o atalho passa a ceder para o papel da organização, o passo 1 do `01` muda e
    CT-04b muda de oráculo.

- **RQ-03 — a organização "aberta" é a da URL ou a da sessão?**
  O painel usa `isPersistent: true` e o kit guarda `tenant_corrente` na sessão. Numa divergência
  (link direto para `/app/globex` com `acme` na sessão), qual manda para o badge?
  - **Assumido**: **a da URL** — é o que o request resolve e o que a pessoa está vendo. CT-02 é
    escrito com visita direta a cada URL, justamente para não depender da resposta.
  - **Se negado**: entra um CT de divergência sessão × URL.

- **Papel do painel `app` gravado no CONTEXTO GLOBAL (`team_id = 0`): vira badge em toda organização, ou em nenhuma?** *(trazida pela revisão adversarial)*
  O `00` estrutura a distinção "contexto global × organização" e nunca decide este caso para o
  painel de negócio. O estado é alcançável — papel do `/app` gravado sem organização é o defeito
  que `PapeisPorOrganizacaoTest` existe para impedir, o que quer dizer que o banco o aceita.
  - **Assumido**: **em nenhuma** — fail-closed, mesma lógica da ADR-02. A pergunta é "qual papel
    nesta organização", e papel sem organização não a responde. É CT-09, marcado `@premissa`.
  - **Se negado**: a implementação passa a admitir `organização OU global`, a linha `acme` de
    CT-09 inverte, e M28/M31 deixam de ser mutantes.

- **Dois papéis do MESMO painel na MESMA organização: qual vence?** *(trazida pela revisão adversarial)*
  `panel_user` **e** `admin_app` na mesma organização é um estado que o banco aceita e que o
  requisito não decide. Filtrar por organização não resolve: dentro dela, a escolha volta a ser
  "o que o banco entregar", sem ordenação declarada — o mesmo defeito do relato, um nível abaixo.
  - **Assumido**: nada. É **lacuna declarada**, não premissa — inventar um vencedor (o mais
    privilegiado? o mais recente?) seria escrever requisito, e a ADR-01 já descartou "escolher um
    papel plausível em vez do papel certo".
  - **Se respondido**: entra um cenário com as duas atribuições na mesma organização e o vencedor
    nomeado.

- **`guard_name`: o kit tem guard único por contrato?** *(trazida pela rodada 2 da revisão adversarial)*
  A chave de leitura do papel tem **três** colunas — `painel`, `team_id` e `guard_name`. As duas
  primeiras têm cenário; a terceira está declarada no SFDIPOT e nunca instanciada, que é a mesma
  omissão que produziu o achado I1. Uma reescrita que perdesse o filtro de guard passa por todo o
  conjunto.
  - **Assumido**: nada. É **lacuna declarada**.
  - **Se a resposta for "guard único"**: escrever isso e **remover a coluna do SFDIPOT** — coluna
    declarada que ninguém pode instanciar é convite para a próxima omissão.
  - **Se não for**: entra um cenário com papel homônimo em outro guard, na mesma organização.

- **Impersonação: o badge mostra o papel de quem está sendo personificado, na organização aberta?**
  O `00` cita impersonação como parte do contexto do cabeçalho, mas não a decide nesta mudança.
  - **Assumido**: **sim**, sem cenário próprio — o badge lê o usuário autenticado do request, e a
    impersonação troca justamente esse usuário. Não há ramo novo de código.
  - **Se negado**: entra um CT na suíte de impersonação, não nesta.
  - **Ressalva da revisão adversarial**: a premissa não tem guarda em suíte nenhuma. Nada garante
    que a suíte de impersonação passe a olhar o badge, e o `00` cita impersonação como parte do
    contexto deste cabeçalho. Se a resposta for "sim", vale registrar isso lá — senão a premissa
    fica sendo uma afirmação sem teste em lugar algum.

---

## Setup Global

### Personas

Três pessoas distintas nos cenários de A2, nunca colapsadas numa só — persona colapsada é a forma
mais comum de uma matriz de autorização parecer coberta sem exercitar barreira nenhuma.

- **Bianca, a pessoa do relato** — papéis **diferentes** do painel `app` em duas organizações:
  `usuarioComPapel('panel_user', $acme)` + `papelNaOrganizacao($bianca, 'admin_app', $globex)` +
  `$bianca->tenants()->attach([$acme->id, $globex->id])`.
  Só existe em `tests/Tenancy`: `admin_app` **não é criado pelo `PapeisSeeder` sem tenancy**
  (`.ai/rules/testes.md`), e um cenário com ele em `tests/Kit` morre no arranjo com
  `RoleDoesNotExist` — defeito de suíte que se lê como defeito de código.
- **Caio, que administra a instalação e opera uma organização** — `admin` no contexto global
  (`papelNaOrganizacao($caio, 'admin')`, sem tenant) **e** `panel_user` na Acme. É a persona da R5:
  sem os dois papéis o `/admin` responderia 403 e o cenário mediria a porta em vez do badge.
- **Marta, `master_global`** — `usuarioCom('master_global')`: papel no contexto global e
  `roles.painel` **nulo**.

### Fixtures

- `tenant('Acme', 'acme')`, `tenant('Globex', 'globex')` e `tenant('Initech', 'initech')` — três
  organizações ativas. A terceira existe por RQ-02 ("mais de +1"): com duas, uma implementação que
  resolvesse por par — "a corrente e a outra" — não é falsificada por nada.
- `Tenant::create([..., 'ativo' => false])` para CT-08.
- `duasOrganizacoes()` **não serve aqui**: ela dá o **mesmo** papel (`panel_user`) nas duas
  organizações, que é exatamente o arranjo em que o defeito é invisível.
- `beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`, como as duas
  suítes do badge já fazem.

### Fakes

Nenhum. A feature não envia e-mail, não despacha job, não emite evento e não chama HTTP externo —
`## Eventos`, `## Jobs` e `## Channel de Log` do `01` são todos "nenhum", e a varredura SFDIPOT
confirma pelo lado do requisito.

### Estratégia de DB

`RefreshDatabase`, global em `tests/Pest.php` para as quatro suítes. Nada a acrescentar.

### Âncora de TODO oráculo — sem ela nenhum cenário deste arquivo prova nada

Toda afirmação sobre o badge — presença **e** ausência — é feita **dentro do cabeçalho do menu do
usuário**, nunca sobre a resposta inteira. O cabeçalho tem gancho próprio (`data-user-menu-header`)
e o badge tem `title` único por painel (`Acesso ao painel /app`, `/admin`, `/infra`).

Por quê, em três linhas:

- **Presença**: procurar o rótulo na página toda é procurar substring. `Admin` aparece na navegação
  do `/admin`; `Painel App` pode aparecer numa tabela de papéis. O cenário ficaria verde com o
  badge removido.
- **Ausência**: "não exibe badge" sem âncora vira `assertDontSee` de rótulos, e isso **não
  distingue** "nada renderizado" de "badge vazio" ou "badge com o traço" — que é exatamente o
  mutante M11. A ausência é afirmada sobre o `title`, que só existe quando o badge renderiza.
- **Sanidade**: todo cenário que afirma ausência tem de afirmar, antes, que o cabeçalho
  **renderizou**. Sem isso, página em branco, 403 renderizado e layout quebrado passam como
  sucesso.

### Atravessar dois painéis ou duas organizações no mesmo cenário

CT-02, CT-04, CT-05 e CT-07 fazem mais de um request. Entre eles vai `fronteiraDeRequest()` — o
helper de `tests/Pest.php` que descarta `ColorManager`, `AssetManager`, `FilamentManager` e o
`SpotlightActionRegistry`. Sem ele o segundo request morre em 500 por ação de ⌘K registrada pelo
painel anterior, e o cenário falha por um motivo que não é o dele. É arranjo, não oráculo.

---

## Regra R1 — o badge do `/app` mostra o papel da organização aberta

> `RQ-01`, `RQ-02` · área A1, perfil **padrão** · técnica: **tabela de decisão** (organização aberta × papel por organização)
> Camada: request HTTP de página cheia · `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`

**Por que não há camada mais barata**: o badge é emitido por render hook do layout do painel, e
teste de componente Livewire não renderiza o layout — a suíte ancestral registra isso no próprio
docblock. A camada mais barata **que existe neste projeto** e falsifica a regra é o request de
página cheia.

**Por que o cenário é bidirecional, e não uma organização só.** É a diferença entre matar o
mutante e passar por sorte. O `PapeisSeeder` cria os papéis nesta ordem — `master_global`,
`admin`, `infra`, `admin_app`, `panel_user` —, então **`admin_app` tem `roles.id` MENOR que
`panel_user`**. Um cenário que só olhasse a Globex (onde Bianca é `admin_app`) ficaria **verde com
o defeito de hoje intacto**, porque o `->first()` sem ordenação entrega o de menor id, que é
justamente `admin_app`. Quem falsifica é a linha da **Acme**. E manter as duas linhas mata também
o mutante simétrico — ordenar por id **descendente** —, que acertaria a Acme e erraria a Globex.
Nenhuma ordenação estática sobrevive às duas linhas juntas. Por isso o oráculo de cada linha
afirma **o rótulo certo E a ausência do outro**.

```gherkin
# language: pt

Funcionalidade: O badge de papel do cabeçalho do menu do usuário

  Regra: com organização aberta, o badge do /app mostra o papel daquela organização

    Esquema do Cenário: [CT-01] o badge diz o papel da organização aberta
      Dado que Bianca é "panel_user" na Acme, "admin_app" na Globex e "panel_user" na Initech
      E que ela participa das três organizações
      Quando ela abre o painel de negócio da organização "<organizacao>"
      Então o cabeçalho do menu do usuário exibe o badge "<rotulo>"
      E o cabeçalho do menu do usuário não exibe "<rotulo_ausente>"

      Exemplos:
        | organizacao | rotulo            | rotulo_ausente    | # por que esta linha existe                                      |
        | acme        | Painel App        | Administrador App | discriminante: é a linha que o "primeiro do banco" erra hoje     |
        | globex      | Administrador App | Painel App        | discriminante: é a linha que uma ordenação descendente erraria   |
        | initech     | Painel App        | Administrador App | a terceira organização: RQ-02 diz "mais de +1", e uma resolução  |
        |             |                   |                   | por par ("a corrente e a outra") passa com duas e erra com três  |
```

**Nota de oráculo**: o `E ... não exibe` não é redundante. Sem ele, uma implementação que
renderizasse **os dois** badges passaria nas duas linhas — e "exibir todos os papéis" é
exatamente o que o `00` declarou fora de escopo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | mantém a consulta sem filtro de organização e resolve com `->first()` — o defeito de hoje | CT-01, linha `acme` |
| M2 | **ordena** a consulta (por `roles.id`, ou por "papel de maior privilégio") em vez de filtrar pela organização — a alternativa 4 que a ADR-01 descartou | CT-01, linha `globex` |
| M3 | filtra pela organização, mas compara o **slug** ou o objeto `Tenant` com a coluna que guarda a chave | CT-01, as duas linhas (nenhum papel casa; o badge some das duas) |
| M4 | filtra pela organização **e também** exige o contexto global, somando as duas condições | CT-01, as duas linhas |
| M5 | renderiza todos os papéis do painel que a pessoa tem, em vez de um | CT-01, o `não exibe` de cada linha |

---

## Regra R2 — trocar de organização troca o badge, sem novo login

> `RQ-03` · área A1, perfil **padrão** · técnica: **sequência de dois eventos (2-switch)** — o oráculo é o resultado do **segundo** evento
> Camada: request HTTP · `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`

O que a regra acrescenta a R1 não é "mostrar o papel certo" — é **não guardar o primeiro**. Dois
cenários separados, um por organização, não provam nada sobre o segundo giro: cada teste nasce com
sessão limpa. A regra só é falsificável com as duas visitas **na mesma sessão autenticada**.

```gherkin
# language: pt

  Regra: trocar de organização troca o badge, sem novo login

    Cenário: [CT-02] o badge acompanha a troca de organização na mesma sessão
      Dado que Bianca é "panel_user" na Acme e "admin_app" na Globex
      E que ela está autenticada e já abriu o painel da Acme nesta mesma sessão
      Quando ela abre o painel de negócio da Globex sem autenticar de novo
      Então o cabeçalho do menu do usuário exibe o badge "Administrador App"
      E o cabeçalho do menu do usuário não exibe "Painel App"

    @premissa
    Cenário: [CT-10] com a sessão apontando para outra organização, manda a da URL
      Dado que Bianca é "panel_user" na Acme e "admin_app" na Globex
      E que a organização corrente guardada na sessão dela é a Acme
      Quando ela abre diretamente o painel de negócio da Globex
      Então o cabeçalho do menu do usuário exibe o badge "Administrador App"
      E o cabeçalho do menu do usuário não exibe "Painel App"
```

> **O `Dado` de CT-02 é arranjo, não oráculo.** A versão anterior dizia "já abriu o painel da Acme,
> **onde o badge disse 'Painel App'**" — uma afirmação escrita onde nada a verifica. Se o primeiro
> giro estivesse errado, o cenário não acusaria. Quem afirma o primeiro giro é CT-01.
>
> **CT-10 é o que falsifica a premissa** de que a organização vem da URL, e não da sessão. Sem ele
> as duas fontes concordam em todo cenário — inclusive em CT-02, cuja visita direta grava a sessão
> antes de renderizar — e uma implementação que lesse `session('tenant_corrente')` passaria por
> todo o conjunto. É `@premissa` porque a resposta ainda é uma pergunta em aberto: se a decisão for
> "manda a sessão", este cenário inverte.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | o papel é memorizado no request/sessão (`once()`, propriedade estática, cache de model) e o segundo request devolve o do primeiro | CT-02 |
| M7 | o badge lê a organização de `session('tenant_corrente')` em vez da organização do request | **CT-10** — CT-02 **não** mata: a visita direta grava a sessão antes de renderizar, então as duas fontes concordam |
| M8 | o papel é resolvido no evento de autenticação, então a troca de organização só se reflete com novo login | CT-02 |

---

## Regra R3 — sem papel na organização aberta, nenhum badge; e o estado da organização não interfere

> `RQ-01` + **ADR-02** · área A1, perfil **padrão** · técnicas: **partição de ausência** (CT-03) e **estado × operação** (CT-08)
> Camada: request HTTP · `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`

O estado de CT-03 **nasce com a mudança**: antes dela era impossível ter organização aberta e
nenhum badge. E ele é alcançável de verdade, porque o acesso não depende da organização (RQ-05):
Bianca entra na Globex mesmo sem papel lá.

**O arranjo discriminante é membresia sim, papel não.** Se ela também não participasse da
organização, o request pararia antes, em `canAccessTenant()`, e o cenário mediria a porta em vez
do badge.

**O `Então` de CT-03 afirma três coisas, e as três são necessárias**: o cabeçalho **existe**, o
badge **não** existe, e o rótulo da **outra** organização não aparece. Sem a primeira, página em
branco, 403 renderizado e layout quebrado passariam como sucesso — assertion de ausência lida como
prova de comportamento é falso ✅ clássico.

CT-08 é a outra face: o badge diz o **papel**, não o estado da organização. A suíte de tenancy já
tem essa fronteira como caso próprio, e a mudança a torna **mais** frágil, não menos — passar a
filtrar por organização cria uma segunda chance de o estado dela entrar na conta por acidente.

```gherkin
# language: pt

  Regra: sem papel do painel na organização aberta, nenhum badge é renderizado

    Cenário: [CT-03] papel em outra organização não vira badge na organização aberta
      Dado que Bianca é "panel_user" apenas na Acme
      E que ela participa também da Globex, onde não tem papel nenhum
      Quando ela abre o painel de negócio da Globex
      Então a página responde 200 e o cabeçalho do menu do usuário é renderizado
      E o cabeçalho do menu do usuário exibe o nome e o e-mail de Bianca
      E o cabeçalho do menu do usuário não contém badge de papel algum — nem o rótulo
        "Painel App", nem o traço de papel ausente, nem o título "Acesso ao painel /app"

    Cenário: [CT-08] organização desativada mostra o papel daquela organização, não o da outra
      Dado que Bianca é "admin_app" na Acme, que está ativa
      E que ela é "panel_user" na organização "Inativa", que está desativada
      E que ela participa das duas
      Quando Bianca abre o painel de negócio da organização "Inativa"
      Então a página responde 200 e o cabeçalho do menu do usuário é renderizado
      E o único badge do cabeçalho é "Painel App"

    Cenário: [CT-13] vínculo e papel divergentes: quem decide o badge é o papel
      Dado que Bianca participa da Acme e da Globex, as duas ativas
      E que ela tem "admin_app" na Globex e nenhum papel na Acme
      Quando ela abre o painel de negócio da Globex
      Então o único badge do cabeçalho é "Administrador App"
```

> **Por que CT-08 mudou de arranjo.** A versão anterior dava a Bianca **uma** organização só — e
> um cenário com uma organização passa com **zero** filtro, isto é, com o defeito de hoje intacto:
> ele não falsificava nada da regra nova. Com duas organizações e papéis diferentes, ele passa a
> falsificar as duas coisas ao mesmo tempo: que o estado da organização não entra na conta **e**
> que o filtro está de fato aplicado.
>
> E o `Dado` deixou de conceder a membresia na organização desativada, porque essa concessão era
> justamente a condição extra do mutante M14 — com ela no arranjo, M14 sobrevivia ao cenário que o
> índice dizia matá-lo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | sem papel na organização ativa, cai de volta para "o primeiro papel em qualquer organização" — a alternativa 1 que a ADR-02 descartou | CT-03 |
| M10 | sem papel na organização ativa, cai para o papel do **contexto global** | CT-03 (a ausência, para Bianca) + CT-05 (que exige o global no `/admin`, para Caio) |
| M11 | renderiza badge vazio, ou com o traço de `Papeis::rotulo(null)` (`—`) | CT-03 — e só porque a ausência é ancorada no `title`; um `assertDontSee` de rótulos **não** o mataria |
| M12 | a ausência de papel derruba a renderização do cabeçalho (erro ao ler nulo) em vez de simplesmente não desenhar | CT-03, a linha do cabeçalho renderizado |
| M13 | o filtro por organização passa a considerar só organizações **ativas**, e o badge some para quem está numa organização desativada | CT-08 |
| M14 | o filtro casa pela organização **e** exige o vínculo em `tenants`, somando duas condições onde o requisito pede uma | **CT-08**, agora que o arranjo dele não concede a membresia na organização desativada. CT-04 **não** o mata: Marta resolve pelo atalho do master, antes da consulta |

> **Estouro do teto, declarado**: R3 fica com 2 cenários e 6 mutantes, acima dos 5 do perfil
> `padrão`. O motivo é que ela junta duas faces — a ausência decidida pela ADR-02 e a fronteira
> "estado da organização", que já tinha caso na suíte. Desdobrá-la em duas regras renumeraria a
> rastreabilidade por motivo cosmético.

---

## Regra R4 — o `master_global` tem badge em toda organização e em todo painel

> `RQ-04` + premissa do `00` · área A2, perfil **completo** · técnica: **partição exaustiva do contexto**
> Camada: request HTTP · `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`

O papel dele vive no contexto **global** e tem `roles.painel` **nulo**. É a combinação com mais
chance de sumir quando alguém acrescenta um filtro por organização — e some justamente para quem
tem mais acesso, no lugar mais visível do kit. A partição do contexto é percorrida inteira, não
amostrada: duas organizações e os dois painéis sem organização.

```gherkin
# language: pt

  Regra: o badge do master global não depende da organização aberta

    Esquema do Cenário: [CT-04] o master global tem badge em toda organização e em todo painel
      Dado que Marta é "master_global" e participa da Acme e da Globex
      Quando ela abre "<tela>"
      Então a página responde 200 e o cabeçalho do menu do usuário é renderizado
      E o único badge do cabeçalho é "Administrador Geral", com o título "<titulo>"

      Exemplos:
        | tela                      | titulo                  | # partição do contexto                                         |
        | o painel da Acme          | Acesso ao painel /app   | organização em que ela não tem papel do painel `app`           |
        | o painel da Globex        | Acesso ao painel /app   | a segunda organização, para que "a primeira" não explique nada |
        | o painel de administração | Acesso ao painel /admin | sem organização corrente                                       |
        | o painel de infra         | Acesso ao painel /infra | sem organização corrente, painel diferente                     |

    @premissa
    Cenário: [CT-04b] o master global que também tem papel na organização continua com o badge de master
      Dado que Marta é "master_global" e também é "panel_user" na Acme
      Quando ela abre o painel de negócio da Acme
      Então o cabeçalho do menu do usuário exibe o badge "Administrador Geral"
      E o cabeçalho do menu do usuário não exibe "Painel App"
```

> `@premissa`: CT-04b depende da pergunta *"master_global que acumula papel na organização"*,
> registrada acima. A premissa adotada é que o atalho vence.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | o atalho do `master_global` passa a ser avaliado **depois** da consulta com organização, e o papel dele (contexto global) não casa com organização nenhuma | CT-04, linhas `acme` e `globex` |
| M16 | o filtro por organização é aplicado também à checagem de master, que passa a exigir o papel dentro da organização | CT-04, todas as linhas |
| M17 | com papel do painel na organização, o papel da organização vence o atalho | CT-04b |
| M26 | o master **entra** em tudo, mas dentro de uma organização deixa de receber as permissões do `Gate::before` — o badge continua certo, porque vem do atalho, e as telas restritas somem | **CT-12** — o badge do master **não** observa isto, e o "Cogitado e Cortado" alegava cobertura que CT-04 não dá |

---

## Regra R5 — `/admin` e `/infra` não mudam

> `RQ-04` · área A2, perfil **completo** · técnica: **matriz painel × contexto corrente**
> Camada: request HTTP — `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` (CT-05) e a suíte `tests/Kit` existente (CT-06)

**A dimensão que não pode ser fixada aqui é o contexto corrente do request**, e é ela que
`.ai/rules/models.md` diz estar guardada por um único caso — o mesmo que esta entrega reescreve.

Um cenário que abrisse o `/admin` com o request "limpo" **não provaria nada**: o painel de negócio
usa `isPersistent: true`, então na vida real a pessoa chega ao `/admin` **depois** de ter aberto
uma organização, com o contexto de permissões já apontando para ela. É nesse estado que o badge do
`/admin` some, se o filtro passar a valer onde não deve. Por isso CT-05 visita a organização
**antes** — e é essa ordem que o torna discriminante.

Persona: **Caio** — `admin` (ou `infra`) no contexto global e `panel_user` na Acme. Dois papéis de
painéis diferentes na mesma pessoa é o que separa "achou o papel do painel certo" de "achou um
papel qualquer".

**Âncora obrigatória do oráculo, e ela não é opcional.** Os rótulos destes dois papéis são
literalmente `Admin` e `Infra` — substrings que aparecem no layout do próprio painel (navegação,
títulos, breadcrumb). Um `Então` que só procurasse o texto passaria **com o badge removido**. O
oráculo de CT-05 é o par: o texto do rótulo **e** o `title` do badge, que é único por painel
(`Acesso ao painel /admin`, `Acesso ao painel /infra`) e só é emitido quando o badge renderiza.
Vale para CT-06 pelo mesmo motivo. Nos demais cenários o problema não existe: `Painel App`,
`Administrador App` e `Administrador Geral` não aparecem em nenhum outro lugar da página.

```gherkin
# language: pt

  Regra: o badge dos painéis sem organização não depende da organização aberta

    Esquema do Cenário: [CT-05] o badge do /admin e do /infra sobrevive à organização aberta
      Dado que Caio tem o papel "<papel_global>" na instalação e "panel_user" na Acme
      E que ele abriu o painel de negócio da Acme antes, na mesma sessão, deixando a
        organização corrente fixada no contexto de permissões do request
      Quando ele abre "<painel>" sem trocar de sessão
      Então o cabeçalho do menu do usuário exibe o badge "<rotulo>", identificado pelo título "<titulo>"
      E o cabeçalho do menu do usuário não exibe "Painel App"

      Exemplos:
        | papel_global | painel                    | rotulo | titulo                   | # partição             |
        | admin        | o painel de administração | Admin  | Acesso ao painel /admin  | painel sem organização |
        | infra        | o painel de infra         | Infra  | Acesso ao painel /infra  | o outro painel sem org |

    Esquema do Cenário: [CT-06] sem multi-organização, o badge continua exatamente como era
      Dado uma instalação com a multi-organização desligada
      E um usuário com o papel "<papel>", atribuído sem organização nenhuma
      Quando ele abre "<painel>"
      Então o cabeçalho do menu do usuário exibe o badge "<rotulo>", com o título "<titulo>"

      Exemplos:
        | papel      | painel | rotulo     | titulo                  |
        | admin      | /admin | Admin      | Acesso ao painel /admin |
        | infra      | /infra | Infra      | Acesso ao painel /infra |
        | panel_user | /app   | Painel App | Acesso ao painel /app   |
```

> **CT-06 já existe**: é a suíte `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` inteira, que não deve
> mudar **nenhuma linha** nesta entrega. O cenário está escrito aqui para que a regressão apareça
> na Matriz de Rastreabilidade como cobertura de RQ-04, e não como suíte "que por acaso continuou
> verde". Se algum caso dela precisar de ajuste, a mudança extrapolou o escopo declarado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M18 | o filtro por organização passa a ser aplicado **sempre**, com o contexto que estiver fixado no `PermissionRegistrar` — e no `/admin`, depois de visitar uma organização, esse contexto é a organização | CT-05, as duas linhas |
| M19 | a consulta troca `papeisEmQualquerContexto()` pela `roles()` do spatie, que já traz o filtro de team — a armadilha que a ADR-01 nomeia como alternativa 3 e que `.ai/rules/models.md` proíbe | CT-05, as duas linhas — **desde que** o papel global de Caio seja gravado com o contexto global e a organização corrente seja outra. Se o escopo de teams do spatie admitir também o papel sem team, o badge do `/admin` sobrevive e CT-05 fica verde: quem fecha esse flanco é **CT-09** |
| M20 | "sem organização" vira o contexto global explícito em vez de "não filtre" | **CT-06**, linha `panel_user` — é a única em que existe papel do painel `app` **e** nenhuma organização corrente. CT-01 **não** o mata: no `/app` com tenancy sempre há organização, então o ramo "sem organização" nunca é percorrido lá |
| M21 | a view lê a organização do painel **default** em vez do painel corrente, e o `/admin` herda a organização persistida | CT-05, as duas linhas |
| M22 | o badge do `/admin` passa a mostrar o papel do painel `app` da organização aberta | CT-05, a linha `não exibe "Painel App"` |

---

## Regra R6 — o acesso ao painel não muda

> `RQ-05` · área A2, perfil **completo** · técnica: **matriz papel × painel**
> Camada: request HTTP · `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`

Restrição negativa, e a mais fácil de deixar sem cenário: "não mudei `canAccessPanel()`" é uma
afirmação sobre o diff, não sobre o comportamento — e não sobrevive à refatoração seguinte. A
matriz é percorrida com a persona **discriminante**: alguém com papel do painel em **uma**
organização, pedindo as duas.

A célula que carrega a regra é `/app/globex` para quem só tem papel na Acme: ela responde
**sucesso**, porque a barreira de painel não pergunta pela organização — quem decide a organização
é `canAccessTenant()`. É exatamente essa célula que uma implementação apressada transformaria em
403 ao "corrigir o papel por organização".

```gherkin
# language: pt

  Regra: o acesso ao painel não depende da organização aberta

    Esquema do Cenário: [CT-07] quem entrava continua entrando
      Dado que Bianca é "panel_user" apenas na Acme
      E que ela participa da Acme e da Globex
      Quando ela pede "<tela>"
      Então a resposta é "<resultado>"
      E o cabeçalho do menu do usuário "<cabecalho>"

      Exemplos:
        | tela                      | resultado | cabecalho                        | # célula da matriz                                          |
        | o painel da Acme          | 200       | é renderizado, com o e-mail dela | papel na organização pedida — o caminho feliz               |
        | o painel da Globex        | 200       | é renderizado, com o e-mail dela | **a célula da regra**: sem papel lá, e ainda assim entra     |
        | o painel de administração | 403       | não é renderizado                | sem papel do painel `admin` — a barreira que continua de pé |

    Cenário: [CT-12] a ficha de um usuário no /admin continua enxergando os papéis de todas as organizações
      Dado que Bianca é "panel_user" na Acme e "admin_app" na Globex
      E que Caio, "admin" da instalação, abriu o painel de negócio da Acme antes, na mesma sessão
      Quando ele abre a ficha de Bianca no cadastro de usuários do /admin
      Então o campo de papéis da ficha lista "panel_user" e "admin_app"

    Cenário: [CT-14] o master global continua podendo, e não só entrando, dentro de uma organização
      Dado que Marta é "master_global" e participa da Acme
      Quando ela abre, dentro do painel de negócio da Acme, a tela de cadastro de usuários —
        que exige a permissão `ViewAny:User`, e que "panel_user" não tem
      Então a página responde 200
```

> **Por que CT-07 deixou de dizer "sucesso"/"negado".** Status genérico não distingue 403 de
> redirect de 404, e as duas linhas de sucesso passavam com a página inteira sem cabeçalho, sem
> badge ou com o badge errado. O `E` do cabeçalho é o que impede a linha de virar um `assertOk`
> com outro nome.
>
> **Por que CT-12 existe.** O perfil de risco declara **quatro chamadores** da leitura de papéis e
> o conjunto observava **um** — o badge. Um contexto que virasse default implícito dentro do
> método mudaria a resposta dos outros três em silêncio, e RQ-05 diz que o que não é exibição não
> muda. A linha do `/admin` é a que mata isso: a ficha de usuário depende de enxergar papéis de
> **todas** as organizações. A linha da Marta fecha o flanco que o "Cogitado e Cortado" alegava
> coberto por CT-04 e não estava — o badge do master vem do atalho, então ele fica certo mesmo se
> o `Gate::before` parar de conceder permissões dentro da organização.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | o filtro por organização vaza para a checagem de papel do painel, e o acesso passa a exigir papel na organização pedida | CT-07, linha `globex` |
| M24 | o filtro vaza para a checagem de master, e o master perde o `Gate::before` **dentro** de uma organização — continua entrando, deixa de poder | **CT-12**, linha da Marta. CT-04 **não** o mata: o badge do master vem do atalho e fica certo de qualquer jeito |
| M25 | ao "consertar" o acesso junto com a exibição, a barreira do `/admin` afrouxa e passa a aceitar papel de painel qualquer | CT-07, linha `administração` |
| M27 | o contexto vira default implícito dentro da leitura de papéis (`??= organização corrente`), e todo consumidor passa a responder escopado sem ter pedido | **CT-12**, linha do `/admin` |

---

## Regra R7 — o contexto global e a coluna de painel continuam decidindo

> `RQ-01`, `RQ-04` · área A2, perfil **completo** · técnicas: **partição do contexto** (CT-09) e **matriz papel × painel dentro do mesmo contexto** (CT-11)
> Camada: request HTTP · `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`

Regra trazida inteira pela revisão adversarial. Ela cobre os dois eixos que o conjunto declarava
no SFDIPOT e **nunca instanciava** — e cada um sustentava uma implementação errada que passava por
todos os cenários anteriores.

**CT-09 — papel do painel `app` no contexto global.** O `team_id` tem três classes, não duas: a
organização A, a organização B e o **0**. O conjunto só instanciava as duas primeiras para o painel
de negócio. Isso deixava viva a implementação mais preguiçosa de todas — filtrar por
`organização OU contexto global`, que é o comportamento nativo do escopo de teams e que um dev lê
como óbvio, porque o próprio `00` diz que papéis de painel sem tenancy vivem no contexto global.
O estado é alcançável: papel do painel `app` gravado sem organização é o defeito que
`tests/Tenancy/PapeisPorOrganizacaoTest.php` existe para impedir — quer dizer, é um estado que o
banco aceita.

**CT-11 — o filtro de painel não pode sair junto.** Ao reescrever a consulta em torno da pivot, o
raciocínio "dentro de uma organização a pessoa só tem papel do painel de negócio" faz o filtro por
`roles.painel` parecer redundante. Nenhuma persona anterior tinha dois papéis de painéis
**diferentes** no **mesmo** contexto — Caio tem os dois, mas em contextos separados. O oráculo
aqui não é premissa: está escrito no `00`, com essas palavras — o badge do `/app` não mostraria um
papel do painel `admin` **"em hipótese alguma"**.

```gherkin
# language: pt

  Regra: o contexto global e a coluna de painel continuam decidindo o badge

    @premissa
    Esquema do Cenário: [CT-09] papel do painel gravado sem organização não vira badge de organização
      Dado que Bianca tem "panel_user" atribuído no contexto global, sem organização
      E que ela é "admin_app" na Globex e participa da Acme e da Globex
      Quando ela abre o painel de negócio da organização "<organizacao>"
      Então a página responde 200 e o cabeçalho do menu do usuário é renderizado
      E o badge do cabeçalho "<oraculo>"
      E o cabeçalho do menu do usuário não exibe "<ausente>"

      Exemplos:
        | organizacao | oraculo                          | ausente           | # classe do team_id       |
        | globex      | é único e diz "Administrador App" | Painel App        | papel na organização      |
        | acme        | não existe                        | Administrador App | só o global (team_id 0)   |

    Esquema do Cenário: [CT-11] papel de outro painel gravado dentro da organização não vira badge do /app
      Dado que Bianca é "panel_user" na Acme
      E que ela também tem um papel do painel "<outro_painel>" gravado no contexto da Acme
      Quando ela abre o painel de negócio da Acme
      Então a página responde 200 e o cabeçalho do menu do usuário é renderizado
      E o único badge do cabeçalho é "Painel App", com o título "Acesso ao painel /app"

      Exemplos:
        | outro_painel | # partição da coluna `roles.painel`                                   |
        | admin        | o papel que o `00` cita nominalmente                                  |
        | infra        | o **terceiro** valor: sem ele, uma lista negativa `!= 'admin'` produz |
        |              | exatamente o mesmo conjunto que `= 'app'` e o cenário fica verde      |
```

> `@premissa` em CT-09: o `00` não decide o que fazer com papel do painel `app` gravado no contexto
> global. A premissa adotada é a mesma da ADR-02 — **fail-closed**: a pergunta é "qual papel nesta
> organização", e um papel sem organização não responde a ela. Se a decisão for a contrária (o
> papel global vale em toda organização), a linha `acme` inverte, e a implementação passa a ser
> `whereIn(team_id, [organização, 0])`. As duas versões são baratas; o que não pode é a escolha
> ficar implícita.
>
> **Armadilha de oráculo em CT-11**, e ela não é teórica: `Papeis::rotulo('admin')` é `Admin`, que
> é **substring de `Administrador App`**. Um `não exibe "Admin"` reprovaria o cenário correto.
> Por isso o `Então` afirma **qual é o único badge**, em vez de negar um texto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | o filtro admite a organização **ou** o contexto global (`whereIn(team_id, [org, 0])`), que é o escopo nativo de teams | CT-09, linha `acme` |
| M29 | o filtro por organização entra e o filtro por `roles.painel` sai, porque "dentro da organização só há papel do painel de negócio" | CT-11 |
| M30 | o filtro de painel vira "papel que não é do `admin`", por lista negativa em vez da coluna | CT-11 |
| M31 | papel gravado no contexto global passa a ser exibido em **todas** as organizações, reintroduzindo a mentira do RQ-01 de forma permanente | CT-09, linha `acme` |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| Repetir CT-01 com a atribuição **invertida** (`admin_app` na Acme, `panel_user` na Globex), para não depender da ordem de `roles.id` | não mata nenhum mutante novo: as **duas linhas** de CT-01 já falsificam qualquer ordenação estática, ascendente ou descendente. A inversão protegeria a fragilidade do teste, não o produto |
| Um cenário afirmando que a leitura do papel aceita um segundo parâmetro | oráculo sobre a assinatura, que é escolha do plano — proibido pela `## Fronteira com o Plano` |
| ~~`master_global` entra nos três painéis (status 200)~~ | **corte revogado pela revisão adversarial**: CT-04 não afirmava status e não pedia tela restrita nenhuma, então a cobertura alegada não existia. Virou a linha da Marta em **CT-12** |
| Badge sob impersonação | premissa declarada, sem ramo novo de código; se virar cenário, pertence à suíte de impersonação |
| Badge na tela de login | já existe em `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php`, e esta mudança não o toca |
| Duas organizações com o **mesmo** papel | é o arranjo em que o defeito é invisível: passa com e sem a correção |
| Painel inexistente devolve ausência sem estourar | já existe em `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` e não muda |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | CT-03 e CT-07 — a pessoa pede a organização em que **não** tem papel; o oráculo é que ela entra e **não** vê o papel da outra |
| Autorização exercida na ação, não só declarada | CT-07 — a matriz é percorrida por request real; nenhum `can()` é afirmado isoladamente |
| Idempotência (ancorada no agregado) | **não se aplica**: leitura pura, sem escrita, sem contador, sem agregado a ancorar. O cenário seria tautológico — duas leituras de algo sem efeito |
| Concorrência | **não se aplica**: nenhum contador, saldo, estoque ou limite |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: nada é gravado. Criar e editar papel por organização é da wiki de convites e do `UserResource`, cobertos por `tests/Tenancy/PapeisPorOrganizacaoTest.php` |
| Domínio condicionado (fronteira que depende de outro campo) | CT-01 — é a própria regra: o papel exibido depende da organização aberta |
| Estado × operação | CT-08 (organização desativada) · a metade de **escrita** não se aplica: a única operação é ler |
| Ausente ≠ nulo ≠ vazio | CT-03 (nenhum papel na organização) e CT-04 (`roles.painel` **nulo** do `master_global`, que não é coringa) |
| Paginação / ordenação | **a ordenação é o defeito, não o oráculo**: a implementação de hoje depende de uma ordem que o banco não promete. CT-01, com as duas linhas, é o cenário que fecha isso — e nenhum `Então` menciona ordem |
| Timezone / DST / virada de dia | **não se aplica**: a feature não lê relógio, não compara datas e não expira nada. A dimensão T da SFDIPOT aqui é sequência (CT-02), não tempo |
| Unicode / limite de varchar / acentuação | **não se aplica** ao comportamento derivado: os rótulos vêm de `App\Support\Papeis`, cuja normalização é da wiki ancestral |
| Unicidade + soft delete | **não se aplica**: nenhuma criação, e nada entra em soft delete nesta mudança |
| CRUD combinado (ler/editar/excluir inexistente) | **não se aplica**: sem CRUD. O análogo — painel inexistente — já existe em `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` e não muda |
| Mass assignment | **não se aplica**: sem formulário e sem payload |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: sem número no domínio |
| **Dois papéis do MESMO painel na MESMA organização** | **lacuna declarada**. O requisito não diz qual vence, e a cardinalidade 2+ só é exercitada **entre** organizações — dentro de uma, o `->first()` sem ordenação continua decidindo. Tentado derivar um oráculo de determinismo ("duas renderizações devolvem o mesmo rótulo"): descartado porque não falsifica — o banco devolve a mesma linha nas duas leituras do mesmo processo, e o cenário ficaria verde com a indeterminação intacta. Vira **pergunta** |
| **Organização removida do vínculo depois do papel atribuído** | **lacuna declarada**: o requisito não decide o que o badge faz para quem tem papel numa organização de que foi desvinculado. Tentado derivar da ADR-02 — mas ali a ausência é de **papel**, não de vínculo, e o request pararia antes em `canAccessTenant()`. Sem oráculo no requisito, não vira cenário |

---

## Índice de Cenários

Arquivo de todos os cenários novos: `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`.
Camada de todos: request HTTP de página cheia — não há camada mais barata que renderize o layout.

| ID | Cenário | Regra | Técnica | Mata |
|---|---|---|---|---|
| CT-01 | o badge diz o papel da organização aberta (3 linhas) | R1 | tabela de decisão | M1, M2, M3, M4, M5 |
| CT-11 | papel de outro painel na organização não vira badge do `/app` (2 linhas) | R7 | matriz papel × painel no mesmo contexto | M29, M30 |
| CT-02 | trocar de organização troca o badge, sem novo login | R2 | 2-switch | M8 · M6 **parcial** |
| CT-10 | sessão apontando para outra organização — manda a URL `@premissa` | R2 | tabela de decisão (URL × sessão) | ⚠️ **nenhum** — ver M7 |
| CT-03 | papel em outra organização não vira badge | R3 | partição de ausência | M9, M10, M11, M12 |
| CT-08 | organização desativada mostra o papel dela | R3 | estado × operação | M13 |
| CT-13 | vínculo e papel divergentes em organizações ativas | R3 | estado × operação | M14 |
| CT-09 | papel do painel gravado sem organização (2 linhas) `@premissa` | R7 | partição do contexto | M28, M31 · sustenta M19 |
| CT-04 | badge do master global em 2 organizações e 2 painéis (4 linhas) | R4 | partição exaustiva | M15, M16 |
| CT-04b | master global que acumula papel na organização `@premissa` | R4 | partição | M17 |
| CT-05 | `/admin` e `/infra` depois de abrir uma organização (2 linhas) | R5 | matriz painel × contexto | M10, M18, M21, M22 · M19 **depende de CT-09** |
| CT-06 | single-tenant inalterado (3 linhas) — `tests/Kit/...Test.php`, **já existe** | R5 | regressão | M20 **parcial** |
| CT-07 | acesso ao painel inalterado (3 linhas) | R6 | matriz papel × painel | M23, M25 |
| CT-12 | a ficha no `/admin` enxerga os papéis de todas as organizações | R6 | mapa de consumidores | M27 |
| CT-14 | o master continua **podendo** dentro de uma organização | R6 | mapa de consumidores | M24, M26 |

**Mutantes previstos**: 31 · **sem matador**: **1** (M7) · **matador parcial ou dependente de
premissa**: **3** (M6, M19, M20). Os quatro estão detalhados na seção da rodada 2.

**Lacunas declaradas**: 3 — dois papéis do mesmo painel na mesma organização; vínculo removido
depois do papel atribuído; e a coluna `guard_name`. As três estão na taxonomia ou nas perguntas,
com o que foi tentado.

> **Nota de honestidade, e ela é a lição mais cara desta derivação.** A versão anterior deste
> índice dizia "sem matador: nenhum" e estava **errada em quatro linhas**: M14, M19, M20 e M24
> tinham matador alegado que não matava — o cenário citado concedia no arranjo justamente a
> condição que o mutante acrescentava, ou exercitava outro ramo, ou resolvia por um atalho antes
> de chegar ao código mutado. Nenhuma delas era visível de dentro: só apareceram quando alguém que
> não derivou os cenários tentou construir a implementação errada que passaria por todos.
> Cobertura alegada e não conferida é pior que lacuna declarada, porque ninguém volta a olhar.

---

## O caso existente que MUDA DE ORÁCULO

`tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` →
`it('acha o papel mesmo fora do contexto de organização em que foi atribuído')`.

Ele afirma hoje, de propósito, o comportamento que RQ-01 reverte **para o badge**. Mas o argumento
escrito nele — "não depende de qual organização está aberta agora, do mesmo jeito que
`canAccessPanel()` não depende" — **continua verdadeiro para acesso**, e `.ai/rules/models.md`
registra que este é o **único** caso que guarda a proibição de trocar `papeisEmQualquerContexto()`
pela `roles()` do spatie. Apagá-lo deixaria a rule sem teste.

**O que fazer**: não apagar. Reescrever, separando as duas metades que hoje moram nele.

| Metade do caso de hoje | Para onde vai |
|---|---|
| "o papel é encontrado mesmo com outra organização no contexto", como afirmação sobre o **badge do `/app`** | **revogada** por RQ-01. Substituída por CT-01 e CT-03, que afirmam o contrário |
| "o papel é encontrado mesmo com outra organização no contexto", como afirmação sobre **acesso** e sobre o **badge dos painéis sem organização** | **preservada e reforçada** por CT-05 (badge do `/admin` e do `/infra` com organização já fixada no contexto) e CT-07 (acesso inalterado) |

Na prática: o caso vira o par CT-05 + CT-07, e o docblock dele passa a dizer **qual metade do
argumento antigo sobreviveu** e por quê. Caso que some sem deixar registro é o que faz a rule
apontar para um teste que não existe mais.

---

## Sem CT-B

O gate do `05` **não passa**, e a declaração do `01` está correta — confirmada aqui, não apenas
herdada:

- O badge é um `<span>` estático emitido por render hook do Blade. Nenhum cenário deste arquivo
  afirma sobre JavaScript executado, console, acessibilidade, cor, tema ou layout.
- Todo oráculo aqui é **texto na resposta HTTP** ou **código de status**, e os dois estão íntegros
  no corpo servido pelo servidor, antes de qualquer Alpine ou Livewire rodar.
- O que só o navegador provaria — o dropdown **abrir** ao clique — já é coberto por
  `tests/Browser`, e esta mudança não o toca.
- O par claro/escuro do badge é da wiki ancestral, e nenhuma classe muda aqui.

Um CT-B custaria dezenas de segundos por cenário para afirmar o mesmo `assertSee`, com dependência
dura de `npm run build`. **Não criar `05-casos-de-teste-browser.md`.**

---

## Comandos de Verificação

```bash
vendor/bin/pint --dirty --format agent
composer types:check
php artisan test tests/Kit/CabecalhoDoMenuDoUsuarioTest.php tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php --compact
php artisan test --testsuite=Kit,Tenancy --parallel --compact
```

Fechamento do ciclo, depois de implementar:

```bash
vendor/bin/pest tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php --mutate --path=app/Models/User.php
```

Exige driver de cobertura. Mutante sobrevivente volta para este arquivo como **lacuna de
derivação**, não como ajuste de assertion — e vale lembrar o limite da medição: `--mutate` só muta
código que existe, então ele **não** enxerga RQ-04, RQ-05 nem a ADR-02, que são comportamentos que
uma implementação errada simplesmente não escreveria. Quem responde por essas é a rastreabilidade
`RQ` → regra → cenário deste arquivo.
