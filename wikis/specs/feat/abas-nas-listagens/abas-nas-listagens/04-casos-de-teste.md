# Casos de Teste — Abas de recorte nas listagens de usuários e convites

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · ADRs: `02-decisoes-arquiteturais.md`
> Derivado do **requisito**. Do plano vieram apenas paths, rotas, stack e a tabela
> `## Superfície de UI`. Nenhum cenário foi escrito olhando `getTabs()` — ele ainda não existe.
> O que foi lido do código é **comportamento pré-existente que RQ-05/RQ-06/RQ-08 mandam preservar**
> (`AprovacaoDeCadastro::filtroDePendentes():75`, `ConvitesTable.php:60-66`) e a **convenção de
> teste** das listagens (`tests/Kit/FiltrosDeTabelaTest.php`, `tests/Kit/SituacaoDaContaTest.php`,
> `tests/Tenancy/AdminDaOrganizacaoTest.php`).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Recorte da aba nas quatro listagens (RQ-01, RQ-02, RQ-07) | 2 | 2 | 4 | **padrão** |
| Badge da aba "Pendentes" (RQ-03) | 2 | 2 | 4 | **padrão** |
| Fronteira de organização da aba e do badge no `/app` (RQ-04) | 3 | 3 | 9 | **completo** |
| Extração do recorte sem mudar o filtro existente (RQ-05, RQ-06, RQ-08) | 2 | 2 | 4 | **padrão** |
| Restrição negativa `/infra/ai-runs` (RQ-09) | 1 | 1 | 1 | **mínimo** |
| README (RQ-10) | 1 | 1 | 1 | **mínimo** |

**Por que a área do badge no `/app` é `completo`**: P=3 porque a contagem atravessa três mecanismos
que só se encontram ali (o `getEloquentQuery()` que falha fechado, a pivot `tenant_user`, e o
`Filament::getTenant()` que teste de componente não recebe de graça) — e porque a ADR-02 **nomeia
a implementação errada** (`User::query()`), o que é o sinal mais forte de que ela é a que sai
sozinha. I=3 porque contagem de outra organização é dado de terceiro: não expõe o registro, mas
informa quantos existem fora da fronteira, ao lado de uma tabela recortada que diz outro número.

- Técnicas aplicadas: **EP** (partição exaustiva de `aprovacao_pendente` e das quatro situações de
  convite), **BVA 3-valores** sobre a contagem do badge (0, 1, 2), **tabela de decisão** (situação
  do convite × aba), **partição de contexto** (organização A × B × sem organização), **rastreio de
  equivalência de recorte** (aba × filtro), **asserção de fonte** para a restrição estrutural.
- Cenários: **21** · Regras: **9** · Mutantes previstos: **37** · Sem matador: **1** (M15, em R3).

### Divergência declarada: rule do projeto × skill

A skill sugere `pest --parallel --tia` como verificação padrão. **A rule do projeto vence**:
`.ai/rules/testes-browser.md:106,115` mediu que sem PCOV o `--tia` não termina (abortado após
35 min) e que `--parallel --tia` derruba cenários. O comando desta feature é o do kit:

```bash
composer test:kit                 # --testsuite=Kit,Tenancy --parallel
vendor/bin/pest tests/Kit/AbasDeListagemTest.php --mutate --path=app/Filament/Concerns/AprovacaoDeCadastro.php
```

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | Nenhum artefato novo. Quatro `ListRecords` ganham `getTabs()`; uma trait (`AprovacaoDeCadastro`) e uma classe de tabela (`ConvitesTable`) ganham um método de recorte cada. Sem migration, sem model, sem policy, sem config, sem rota, sem job, sem evento, sem log — o `01` e o `02` declaram todos como "nenhum", e a varredura confirma: a aba só restringe uma query já autorizada. | CT-15, CT-20, CT-21 |
| **F** | Recortar a listagem por um clique; contar os pendentes num badge; preservar o filtro do modal e o `TernaryFilter`; **não** recortar `/infra/ai-runs`. Função administrativa escondida: nenhuma — a aba não decide nada, e a autorização continua na policy do Resource, intocada. | CT-01…CT-06, CT-13, CT-14, CT-16…CT-19, CT-21 |
| **D** | Entra: `aprovacao_pendente` (booleano, sem nulo — coluna com default) e `aceito_em` (timestamp nulável). Sai: um conjunto de registros e **um número**. Já existe: usuário com `SoftDeletes` (a aba herda o escopo do model) e convite com **quatro** situações derivadas (`Aceito`, `Recusado`, `Expirado`, `Pendente`) de que a aba só enxerga **uma** coluna. Dado de outro tenant: o `/app` recorta por `whereHas('tenants')` (usuários) e `where('tenant_id')` (convites). Cardinalidade zero: badge sem nenhum pendente. | CT-03…CT-06, CT-08…CT-12, CT-16 |
| **I** | Duas portas para o mesmo estado: o **clique** (propriedade Livewire `activeTab`, `HasTabs.php:11`) e a **URL** (`#[Url(as: 'tab')]`, `ListRecords.php:53-54`) — que é como um card ou uma notificação linkaria a listagem já recortada (`getUrl(['tab' => 'pendentes'])`, citado no requisito). A URL é a porta que ninguém lembra de testar e a única que quebra em silêncio quando a **chave** da aba muda: `HasTabs::modifyQueryWithActiveTab()` devolve a query intocada para chave desconhecida, sem erro. | CT-02, CT-17 |
| **P** | Filament 5.7.6 / Livewire 3 / Laravel 13 / PHP 8.4 / Pest 5.1.1. Banco de teste é SQLite em memória; `whereNull`/`whereNotNull` e `where(<coluna>, true)` não dependem de colação nem de case-sensitivity, então a plataforma não gera cenário próprio. **Declarado sem cenário.** | — |
| **O** | Quatro personas reais e distintas: `admin` no `/admin`, `admin_app` no `/app` (que **só existe na suíte `tests/Tenancy`** — `.ai/rules/testes.md`), `infra` no `/infra`. Uso indevido plausível: usar a aba como se fosse barreira ("está fora da aba, logo a pessoa não alcança") — não é, e nenhum cenário deve sugerir que seja; a barreira continua sendo a policy do Resource, que esta feature não toca. | CT-08…CT-12, CT-21 |
| **T** | Concorrência e agendamento: não se aplicam — a aba é leitura sem escrita, e não há contador nem saldo a estourar. **Tempo importa em um ponto, e ele é do convite**: `situacao()` chama `expira_em?->isPast()`, então um convite pendente **vira expirado com o relógio andando**, sem ninguém escrever no banco. A aba recorta por `aceito_em`, que o tempo não move — e é por isso que expirado cai na aba "Pendentes". O cenário congela o relógio para que a partição "Pendente" seja estável. | CT-16 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| **R1** — as quatro listagens têm as abas declaradas, nesta ordem, e "Todos" é a ativa sem `?tab=` | recorte (padrão) | RQ-01, RQ-02, RQ-07 | EP sobre o conjunto de abas + partição da porta de entrada (clique × URL) | CT-01, CT-02 |
| **R2** — a aba "Pendentes de aprovação" mostra exatamente os usuários com `aprovacao_pendente` verdadeiro | recorte (padrão) | RQ-01, RQ-02 | EP exaustiva do domínio (`true` / `false` / excluído logicamente) | CT-03, CT-04 |
| **R3** — o badge da aba traz a contagem dos pendentes, pela query do Resource, e concorda com o que a aba mostra | badge (padrão) | RQ-03 | BVA 3-valores sobre a contagem (0, 1, 2) + rastreio de equivalência badge × tabela | CT-05, CT-06 |
| **R4** — no `/app`, aba e badge param na fronteira da organização corrente, e sem organização param em nada | fronteira (**completo**) | RQ-04 | partição de contexto (Acme × Globex × sem organização) + troca de contexto | CT-08…CT-12 |
| **R5** — o recorte de pendentes tem uma definição só; o filtro do modal continua na tela, recorta igual e combina com a aba | extração (padrão) | RQ-05, RQ-06 | rastreio de equivalência de recorte + asserção de fonte | CT-13, CT-14, CT-15 |
| **R6** — as abas de convites recortam por `aceito_em`, e cada uma das quatro situações cai onde a coluna manda | recorte (padrão) | RQ-07 | tabela de decisão (4 situações × 3 abas) | CT-16, CT-17 |
| **R7** — o recorte de convites tem uma definição só; o `TernaryFilter` continua com os três ramos e recorta igual às abas | extração (padrão) | RQ-08 | partição dos três ramos do ternário + equivalência + asserção de fonte | CT-18, CT-19, CT-20 |
| **R8** — `/infra/ai-runs` não tem aba nenhuma, e o `SelectFilter('status')` continua na tela | negativa (mínimo) | RQ-09 | asserção negativa + controle positivo | CT-21 |
| **R9** — o README registra a convenção e a não-persistência da aba | README (mínimo) | RQ-10 | asserção de seção, em pt e en | CT-22 |

**Técnica escalada acima do perfil da área**: R3 usa **BVA 3-valores** (0, 1, 2) num perfil
`padrão`, que só previa 2 valores. Motivo: contagem em `int` tem duas fronteiras baratas de errar
ao mesmo tempo — o zero (que decide se aparece badge "0" ou badge nenhum) e o "conta o total em
vez do recorte", que **só é observável com pendentes ≠ total**. Dois valores não separam as duas.

---

## Fronteira com o Plano

O `00-requisito.md` desta feature é atípico: o `## Texto Original` **cita o plano do estudo
ancestral verbatim**, com nomes de método e de classe dentro. Isso muda a fronteira — o que está
citado ali é requisito, não plano, e pode ser oráculo. O que segue é o que veio do
`01-plano-acao.md` **desta** wiki e foi recusado.

| Item do `01` | Recusado como oráculo porque | Destino |
|---|---|---|
| Ordem dos passos (extrair antes das abas), commits, Pint, `composer types:check` | processo de implementação, não comportamento | fora dos cenários |
| `->deferBadge()` | saída **condicional** registrada como risco, não como cláusula | fora dos cenários |
| `Heroicon::OutlinedClock` na aba | está no `00` verbatim, mas nenhuma `RQ` o decompôs; é escolha visual | detalhe do cenário, nunca `Então` |
| Rótulos "Pendentes"/"Aceitos" das abas de convites | o `00` fixa as **chaves** (`todos`, `pendentes`, `aceitos`), não os rótulos — quem os gera é `HasTabs::generateTabLabel()` | asserção sobre a **chave**; rótulo só onde o `00` o escreve ("Pendentes de aprovação") |
| "o badge da aba não substitui o `BadgeContagemNavegacao` do menu" | só o `01` diz isso; nenhuma `RQ` | fora dos cenários (podado — ver `## Cogitado e cortado`) |
| `/app/{tenant}/convites` e as demais rotas | path e superfície — uso permitido | arranjo dos cenários |

**Aproveitado como oráculo por vir do `00`, e não do `01`**: os nomes
`AprovacaoDeCadastro::recorteDePendentes()`, `ConvitesTable::pendentes()`/`aceitos()`, as chaves
`todos`/`pendentes`/`aceitos`, o rótulo "Pendentes de aprovação", `whereNull('aceito_em')` /
`whereNotNull('aceito_em')`, `where('aprovacao_pendente', true)` e a exigência de contar por
`static::getResource()::getEloquentQuery()`. Todos estão no `## Texto Original` do `00`.

### Perguntas para o `00-requisito.md`

> **Desvio declarado**: a skill manda replicar as perguntas em `## Ambiguidades e Perguntas
> Abertas` do `00`. O `00` foi entregue a esta derivação como **oráculo fechado, somente leitura**.
> As perguntas ficam abaixo, em bloco pronto para colagem, e continuam bloqueando o que dependem
> delas. Cada cenário afetado está marcado `@premissa`.

- **RQ-03 — a aba "Pendentes" mostra badge "0" quando não há nenhum pendente?** O trecho verbatim é
  `->badge(fn (): int => …->count())`, e `HasBadge::getBadge()` devolve a avaliação da closure: com
  zero pendentes o badge é a string `"0"`, e a aba nasce com um zero cinza ao lado. O kit já decidiu
  o contrário no menu — `BadgeContagemNavegacao` devolve `null` no zero, com o motivo escrito
  ("um `0` cinza em todo item só polui o menu"). **Assumido**: o literal do requisito vence, badge
  `"0"` aparece. **Se negado**: o badge devolve `null` no zero, e CT-06 muda de oráculo na linha do
  zero. Bloqueia R3.
- **RQ-07 — a aba "Pendentes" de convites mostra convite recusado e expirado?** O recorte é
  `whereNull('aceito_em')`, e `Convite::situacao()` (`Convite.php:547-555`) tem **quatro** valores:
  recusado e expirado também têm `aceito_em` nulo. Na mesma linha, a coluna "Situação" dirá
  "Recusado" dentro de uma aba chamada "Pendentes". **Assumido**: o literal do requisito vence — a
  aba recorta por `aceito_em` e mostra os três. **Se negado**: ou o rótulo vira "Não aceitos", ou o
  recorte passa a ser `whereNull('aceito_em')->whereNull('recusado_em')->where('expira_em', '>', now())`
  — e aí o recorte da aba **deixa de ser o mesmo** do `TernaryFilter`, o que derruba RQ-08.
  Bloqueia R6 e R7.
- **RQ-03/RQ-04 — o badge segue os filtros ativos da tabela?** A ADR-02 justifica a decisão com "o
  badge nunca discorda da tabela que ele rotula", mas a decisão em si é contar por
  `getEloquentQuery()`, que **não** enxerga filtro de tabela. Com o `TrashedFilter` em "somente
  excluídos" os dois números divergem em tela. **Assumido**: o badge conta a query do Resource e
  ignora filtro de tabela. **Se negado**: a contagem vira `$this->getFilteredTableQuery()`, e CT-05
  inverte. Bloqueia R3.
- **RQ-08 — onde moram `pendentes()`/`aceitos()`, se a tabela do `/app` não tem o `TernaryFilter`?**
  `ConvitesTable` é `App\Filament\Admin\Resources\Convites\Tables\ConvitesTable`, e a tabela de
  convites do `/app` é montada **dentro** de
  `App\Filament\App\Resources\Convites\ConviteResource::table()`, **sem filtro nenhum**. Logo RQ-08
  tem dois consumidores no `/admin` (filtro + aba) e um terceiro no `/app` (aba) que teria de
  importar do namespace `Admin`. **Assumido**: os métodos ficam em `ConvitesTable` como o `00`
  escreve, e a `ListConvites` do `/app` a importa. **Se negado**: eles vão para o model `Convite`
  ou para uma classe neutra — e CT-20 muda de alvo. Bloqueia R7.
- **RQ-10 — "o README" são os dois?** O kit mantém `README.md` e `README.en.md` em par, e há cinco
  `*DocumentacaoTest` que cobram os dois. **Assumido**: os dois. **Se negado**: CT-22 perde a linha
  `en`. Bloqueia R9.

---

## Setup Global

### Personas

Três pessoas distintas, e a distinção é discriminante: percorrer tudo com uma só colapsa a
fronteira de organização, que é o assunto de R4.

- `admin` — `usuarioDoKit('admin', 'admin@example.com')` + `noPainelDoShield('admin')` +
  `noPainelBootado('admin')`. Opera as listagens do `/admin`.
- `admin_app` da Acme — `usuarioComPapel('admin_app', $acme)` + `noPainelDa($acme)`.
  **Só existe em `tests/Tenancy`** (`.ai/rules/testes.md`): o `PapeisSeeder` só cria `admin_app`
  no ramo de tenancy. Cenário que o use em `tests/Kit` morre no arranjo com `RoleDoesNotExist`,
  que parece defeito de código e é defeito de suíte.
- `infra` — `usuarioDoKit('infra', 'infra@example.com')` + `noPainelDoShield('infra')` +
  `noPainelBootado('infra')`. Só CT-21.

### Fixtures

- Usuário pendente: `usuario('x@example.com')` + `forceFill(['aprovacao_pendente' => true])->save()`
  — `aprovacao_pendente` está **fora do `$fillable`** de propósito (`RegistroAberto.php:158-161`),
  então `User::create([... 'aprovacao_pendente' => true])` grava `false` em silêncio e o cenário
  vira falso ✅. É o mesmo caminho que `tests/Kit/SituacaoDaContaTest.php` (CT-30) usa.
- Convite: `ofertaPara($email, $tenant, atributos: [...])` (`tests/Pest.php:792`), com
  `aceito_em`, `recusado_em` e `expira_em` conforme a partição.
- Duas organizações com uma pessoa nas duas: `duasOrganizacoes()` (`tests/Pest.php:628`), e
  `fronteiraDeRequest()` (`:662`) entre a visita a uma e a outra.

### Fakes

Nenhum. A feature não envia e-mail, não enfileira, não chama HTTP e não loga — o `01` declara
"nenhum log e nenhum channel novo", e a varredura **S** confirma. **Exceção de arranjo**: CT-10
exercita o ramo fail-closed do `/app`, que **loga** um `warning` no channel `autenticacao`
(`UserResource.php:169-176`); o cenário não afirma sobre o log (não é cláusula desta feature), mas
quem escrever o teste precisa saber que ele sai.

### Estratégia de DB

`RefreshDatabase` global, ligado no `tests/Pest.php` para `Kit` e `Tenancy`. Seed obrigatório em
todo caso, no `beforeEach`: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`.

### Notas de arnês que invalidam o cenário se ignoradas

1. **`->loadTable()` antes de qualquer asserção de registro.** A tabela do kit carrega adiada
   (`deferLoading` global) — sem ele o HTML testado é o do esqueleto e nenhum registro aparece
   (`.ai/rules/testes.md`).
2. **A ordem é `->loadTable()->set('activeTab', 'pendentes')`.** `HasTabs::updatedActiveTab()`
   chama `resetPage()` e `applyTableColumnManager()`; setar antes do load faz o reset acontecer
   sobre uma tabela que ainda não existe.
3. **A porta da URL é `Livewire::withQueryParams(['tab' => 'pendentes'])`**, não um parâmetro de
   `Livewire::test()` — `activeTab` é `#[Url(as: 'tab')]`. Precedente no kit:
   `tests/Kit/ConviteTest.php:70`.
4. **O badge se lê da instância**, não do HTML:
   `$componente->instance()->getCachedTabs()['pendentes']->getBadge()` devolve `?string`
   (`HasBadge.php:53`; a closure `fn (): int` é coagida a string, e é por isso que o zero vira
   `"0"` — ver a primeira pergunta aberta). `assertSee('2')` seria oráculo fraco: o algarismo 2
   aparece em qualquer paginação, data ou id.
5. **"mostra N registros" é `assertCountTableRecords(N)`** (`TestsRecords.php:64`), e ele
   **acompanha** `assertCanSeeTableRecords`/`assertCanNotSeeTableRecords` — não os substitui.
   Contagem sozinha passa com os registros errados; identidade sozinha passa com registros a mais.
6. **Helper usado pelos dois arquivos (Kit e Tenancy) vai para `tests/Pest.php`**, nunca duplicado
   com outro nome (`.ai/rules/testes.md`). O candidato óbvio aqui é "criar usuário pendente".
7. **Asserção de ausência sobre arquivo comentado filtra comentário antes**
   (`preg_replace('~/\*.*?\*/~s', '', $codigo)` mais as linhas `//`) — CT-15 e CT-20 dependem
   disso, e o kit já reprovou três vezes por esquecer (`.ai/rules/testes.md`). Os `getTabs()` novos
   quase certamente terão um docblock explicando *por que* o `where` não está ali, e a menção
   derrubaria a asserção. A asserção de **presença** continua sobre o texto cru.
8. **Filament 5: nada de `assertFormSet`, `callTableAction` ou `assertTableActionExists`** — são
   `@deprecated` nos `.stubs.php` e passam sem avisar (`.ai/rules/filament.md:178`). Nenhum cenário
   deste arquivo precisa deles, e nenhum deve ganhá-los na tradução para Pest.

---

## Regra R1 — as quatro listagens têm as abas declaradas, e "Todos" é a ativa por padrão

> `RQ-01`, `RQ-02`, `RQ-07` · perfil **padrão** · técnica: **EP sobre o conjunto de abas**
> (chave, rótulo e ordem) + **partição da porta de entrada** (clique × URL)

```gherkin
# language: pt

Funcionalidade: Abas de recorte nas listagens de usuários e convites

  Regra: cada listagem declara as abas do requisito, na ordem, e abre em "Todos"

    Esquema do Cenário: [CT-01] a listagem <listagem> abre em "Todos" com as abas do requisito
      Dado que a pessoa com o papel <persona> abre a listagem <listagem>
      Quando a página termina de montar, sem "?tab=" na URL
      Então as chaves das abas são exatamente "<chaves>", nesta ordem
      E a aba ativa é "todos"
      E a aba de chave "pendentes" tem o rótulo "<rotulo_pendentes>"

      Exemplos:
        | listagem        | persona   | chaves                  | rotulo_pendentes       | # partição |
        | /admin/users    | admin     | todos,pendentes         | Pendentes de aprovação | RQ-01      |
        | /app/users      | admin_app | todos,pendentes         | Pendentes de aprovação | RQ-02      |
        | /admin/convites | admin     | todos,pendentes,aceitos | Pendentes              | RQ-07      |
        | /app/convites   | admin_app | todos,pendentes,aceitos | Pendentes              | RQ-07      |

    Cenário: [CT-02] a URL com "?tab=pendentes" abre a listagem de usuários já recortada
      Dado um usuário pendente de aprovação e dois usuários já aprovados no /admin
      Quando o administrador abre "/admin/users?tab=pendentes"
      Então a aba ativa é "pendentes"
      E a tabela mostra só o usuário pendente
      E os dois aprovados não aparecem
```

**Nota de derivação (CT-01)**: o rótulo de `pendentes` nas linhas de convite é `Pendentes` porque o
`00` não escreve rótulo para elas e `HasTabs::generateTabLabel()` gera o `ucfirst` da chave. Se a
implementação passar um rótulo próprio, o cenário reprova — e reprova **certo**: rótulo não
determinado pelo requisito é decisão nova que precisa voltar ao `00`.

**Nota de derivação (CT-02)**: esta é a única porta que o clique não exerce. `HasTabs.php:73-75`
devolve a query **intocada** quando `activeTab` não bate com nenhuma chave — sem erro, sem aviso,
com a tela mostrando tudo. É o modo de falhar mais silencioso da feature inteira, e o requisito
depende dele (`getUrl(['tab' => 'pendentes'])` está no `## Texto Original`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | a aba "Pendentes" é declarada primeiro, e o recorte vira a tela default de todo mundo | CT-01 (`a aba ativa é "todos"`) |
| M2 | a chave é `pendente`, `aprovacao_pendente` ou `pendentes_de_aprovacao` — o clique funciona, o `?tab=pendentes` cai no ramo "chave desconhecida" e mostra tudo | CT-02 |
| M3 | `getTabs()` só na página do `/admin`; o `/app` fica sem abas (as quatro páginas são arquivos independentes, e é o esquecimento mais barato de cometer) | CT-01 (linhas `/app`) |
| M4 | rótulo deixado ao `generateTabLabel()` nos usuários: a aba vira "Pendentes" em vez de "Pendentes de aprovação" | CT-01 (`rotulo_pendentes`) |
| M5 | convites com só duas abas (`todos`, `pendentes`), esquecendo `aceitos` | CT-01 (linhas de convite) |

---

## Regra R2 — a aba "Pendentes de aprovação" mostra exatamente os pendentes

> `RQ-01`, `RQ-02` · perfil **padrão** · técnica: **EP exaustiva** do domínio de
> `aprovacao_pendente` — as duas partições do booleano **mais** a terceira que o `SoftDeletes` do
> `User` cria: o pendente excluído logicamente, que não é `true` nem `false` — é invisível.

```gherkin
# language: pt

  Regra: a aba "Pendentes de aprovação" recorta a tabela por aprovacao_pendente

    Esquema do Cenário: [CT-03] a aba <aba> mostra <visiveis> e esconde <ocultos>
      Dado dois usuários pendentes de aprovação e três usuários já aprovados no /admin
      Quando o administrador ativa a aba "<aba>"
      Então a tabela mostra <visiveis>
      E não mostra <ocultos>

      Exemplos:
        | aba       | visiveis          | ocultos           | # partição         |
        | todos     | os cinco usuários | nenhum            | controle           |
        | pendentes | os dois pendentes | os três aprovados | aprovacao_pendente |

    Cenário: [CT-04] o pendente excluído logicamente não aparece na aba "Pendentes de aprovação"
      Dado um usuário pendente de aprovação e outro usuário pendente que foi excluído
      Quando o administrador ativa a aba "Pendentes de aprovação"
      Então a tabela mostra só o pendente que não foi excluído
      E o excluído não aparece
      E o badge da aba traz "1"
```

**Por que a fixture de CT-03 é 2 e 3, e não 1 e 1**: com um de cada lado, a implementação que
**ignora o recorte e mostra tudo** ainda passaria no `assertCanSeeTableRecords`, e só o
`assertCanNotSeeTableRecords` a separaria — o mesmo raciocínio que o kit já escreveu em
`tests/Kit/FiltrosDeTabelaTest.php`. Com 2 e 3 o número visível (2) difere do total (5), o que faz
a mesma fixture servir de arranjo discriminante para o badge em CT-06 sem montar cenário novo.

**Por que CT-04 afirma sobre o badge também**: o excluído é a única partição em que "a tabela
esconde" e "o badge não conta" podem divergir sem que ninguém perceba — a tabela herda o
`SoftDeletingScope` do model, e uma contagem escrita à mão pode não herdar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | `->modifyQueryUsing()` esquecido: a aba existe, é clicável e não recorta nada | CT-03 (linha `pendentes`, no `não mostra`) |
| M7 | `where('aprovacao_pendente', false)` — a condição invertida, que mostra exatamente o complemento | CT-03 (linha `pendentes`) |
| M8 | `whereNotNull('aprovacao_pendente')` em vez de `where(..., true)`: a coluna tem default e nunca é nula, então a aba mostra **todos** e parece funcionar em qualquer base pequena | CT-03 (linha `pendentes`, no `não mostra`) |
| M9 | recorte escrito com `withTrashed()` (por analogia com a lixeira que a mesma tela tem): o excluído volta para a aba | CT-04 |
| M10 | a aba "Todos" recebe um `modifyQueryUsing` qualquer e deixa de mostrar tudo | CT-03 (linha `todos`) |

---

## Regra R3 — o badge conta os pendentes pela query do Resource e concorda com a aba

> `RQ-03` · perfil **padrão**, com **BVA 3-valores escalado** · técnica: **BVA** sobre a contagem
> (0, 1, 2) + **rastreio de equivalência** entre o número do badge e o número de linhas da aba

```gherkin
# language: pt

  Regra: o badge da aba "Pendentes de aprovação" traz a contagem dos pendentes do recorte do Resource

    @premissa
    Esquema do Cenário: [CT-06] o badge traz <badge> com <pendentes> pendentes entre <total> usuários
      Dado <total> usuários no /admin, dos quais <pendentes> estão pendentes de aprovação
      Quando o administrador abre a listagem de usuários
      Então o badge da aba "Pendentes de aprovação" é "<badge>"
      E a aba "Pendentes de aprovação", ativada, mostra <pendentes> registros

      Exemplos:
        | total | pendentes | badge | # borda                                     |
        | 4     | 0         | 0     | borda inferior — nenhum pendente            |
        | 4     | 1         | 1     | borda+1 — um só                             |
        | 5     | 2         | 2     | dentro, e distinto do total (discriminante) |

    @premissa
    Cenário: [CT-05] o badge continua contando a query do Resource com a lixeira aberta na tela
      Dado dois usuários pendentes de aprovação e um terceiro pendente já excluído
      E o filtro de lixeira posto em "somente excluídos"
      Quando o administrador ativa a aba "Pendentes de aprovação"
      Então a tabela mostra só o pendente excluído
      E o badge da aba continua trazendo "2"
```

**Por que a terceira linha de CT-06 tem total ≠ pendentes**: com `total == pendentes` a
implementação que conta **a listagem inteira** produz o mesmo número e o cenário fica decorativo —
é o "valor redondo" na sua forma não-numérica. Nas três linhas o total difere do recorte.

**Premissa de CT-06**: a linha do zero afirma badge `"0"`, o literal do `00`. Se a resposta à
primeira pergunta aberta for "suprimir o zero", esta linha vira `Então a aba não tem badge`.

**Premissa e valor de CT-05**: este é o único cenário em que "contar pela query do Resource" e
"contar pela query filtrada da tabela" produzem **números diferentes** (2 contra 1). Ele é o
oráculo da ADR-02 no ponto em que ela é ambígua — e a implementação errada que ele mata é plausível
justamente porque a ADR fala em "o badge nunca discorda da tabela": quem lê isso e tenta cumprir
literalmente escreve `$this->getFilteredTableQuery()->count()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | `->badge(fn (): int => static::getResource()::getEloquentQuery()->count())` — o recorte esquecido, o badge mostra o total | CT-06 (linhas com total ≠ pendentes) |
| M12 | badge contado por `$this->getFilteredTableQuery()->count()` para "concordar com a tabela" | CT-05 |
| M13 | `->badge()` ausente: a aba existe sem número, e nada em tela avisa | CT-06 |
| M14 | badge lido do model com `withTrashed()` | CT-04, CT-05 |
| M15 | badge calculado uma vez e memoizado em propriedade estática da página | ⚠️ **sem matador** — dentro de um único componente Livewire a memoização é indistinguível do cálculo por render. Tentado: um segundo `->set('activeTab', …)` na mesma instância não invalida nada, e reinstanciar o componente recria a estática. **Lacuna declarada**; o parente mais próximo é CT-11, que troca de organização com `fronteiraDeRequest()` entre as duas leituras — mata a memoização **entre organizações**, que é a variante que causa dano |

---

## Regra R4 — no `/app`, aba e badge param na fronteira da organização

> `RQ-04` · perfil **completo** · técnica: **partição de contexto** (organização corrente A,
> organização B, sem organização) + **troca de contexto** dentro do mesmo processo
>
> Suíte: `tests/Tenancy` — obrigatório. `admin_app` só existe lá, e
> `App\...\UserResource::canAccess()` exige `config('kit.tenancy.enabled')`.

```gherkin
# language: pt

  Regra: no painel /app a aba e o badge enxergam apenas a organização corrente

    Cenário: [CT-08] a aba "Pendentes" da Acme não mostra o pendente da Globex
      Dado a Acme com um usuário pendente de aprovação
      E a Globex com dois usuários pendentes de aprovação
      Quando a administradora da Acme ativa a aba "Pendentes de aprovação" no /app da Acme
      Então a tabela mostra só o pendente da Acme
      E nenhum dos dois pendentes da Globex aparece

    Cenário: [CT-09] o badge da Acme conta um, e não os três da instalação
      Dado a Acme com um usuário pendente de aprovação
      E a Globex com dois usuários pendentes de aprovação
      Quando a administradora da Acme abre a listagem de usuários do /app da Acme
      Então o badge da aba "Pendentes de aprovação" é "1"

    Cenário: [CT-10] sem organização corrente, a aba não mostra ninguém e o badge é zero
      Dado três usuários pendentes de aprovação distribuídos entre a Acme e a Globex
      E o painel /app sem organização corrente
      Quando a administradora ativa a aba "Pendentes de aprovação"
      Então a tabela não mostra nenhum usuário
      E o badge da aba é "0"

    Cenário: [CT-11] trocada a organização, o badge e a tabela trocam junto
      Dado a Acme com um usuário pendente e a Globex com dois, e uma pessoa que administra as duas
      E que ela já leu o badge "1" no /app da Acme
      Quando ela abre a listagem de usuários no /app da Globex
      Então o badge da aba "Pendentes de aprovação" é "2"
      E a aba "Pendentes de aprovação", ativada, mostra os dois pendentes da Globex

    Cenário: [CT-12] a aba "Pendentes" de convites da Acme não mostra convite da Globex
      Dado um convite não aceito na Acme e um convite não aceito na Globex
      Quando a administradora da Acme ativa a aba "Pendentes" na listagem de convites do /app da Acme
      Então a tabela mostra só o convite da Acme
      E o convite da Globex não aparece
```

**Por que Acme=1 e Globex=2, e nunca 1 e 1**: com uma organização de cada lado empatada, o badge
que conta a instalação inteira mostraria 2 e o correto mostraria 1 — ainda separa. Mas o
**recorte** com empate produz conjuntos de mesmo tamanho, e um `assertCanSeeTableRecords` frouxo
não os distingue. Com 1 e 2, todo número em jogo (1, 2, 3) é distinto, e nenhuma confusão entre
"corrente", "outra" e "instalação" sobrevive.

**Por que CT-11 precisa de `fronteiraDeRequest()`**: o mesmo container atravessa as duas visitas no
teste, e o kit já documentou que sem essa fronteira o estado da primeira organização vaza para a
segunda (`tests/Pest.php:662`). Sem ela, CT-11 pode ficar **verde por acidente do arnês** — que é
exatamente o defeito que ele existe para pegar.

**Por que CT-10 é obrigatório e não redundante**: `App\...\UserResource::getEloquentQuery()` falha
**fechado** por decisão de projeto (`.ai/rules/filament.md`: "o escopo nativo, no mesmo cenário,
falha ABERTO e devolve a base inteira"). Uma aba ou um badge que não passem por ele falham
**aberto** — e o único cenário em que a diferença entre aberto e fechado é visível é este.

**Estouro de teto declarado**: 7 mutantes contra o teto de 6 do perfil `completo`. Motivo: a
fronteira de organização tem **duas entidades** (usuários e convites) e M21 é o mutante próprio dos
convites. Desdobrar R4 em duas regras renumeraria toda a rastreabilidade por motivo cosmético — o
gate vence o teto.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | `->badge(fn (): int => User::query()->where('aprovacao_pendente', true)->count())` — a implementação que a ADR-02 nomeia; a Acme mostra "3" | CT-09 |
| M17 | `->badge(fn (): int => User::where('aprovacao_pendente', true)->count())` (a mesma coisa sem o `query()`) | CT-09 |
| M18 | `modifyQueryUsing(fn () => User::query()->where('aprovacao_pendente', true))` — substitui o builder em vez de restringi-lo, e com ele vai embora o `whereHas('tenants')` | CT-08 |
| M19 | a página do `/app` copiada da do `/admin` com `Admin\...\UserResource` no `static::getResource()`: o badge conta a instalação e a tabela também | CT-08, CT-09 |
| M20 | badge memoizado entre organizações (estática de classe, ou cache sem chave de tenant) | CT-11 |
| M21 | a aba de convites do `/app` recorta só por `aceito_em`, sem o `where('tenant_id')` do Resource | CT-12 |
| M22 | sem organização corrente a aba ignora o `getEloquentQuery()` e cai na base inteira (falha **aberta**) | CT-10 |

---

## Regra R5 — o recorte de pendentes tem uma definição só, e o filtro continua

> `RQ-05`, `RQ-06` · perfil **padrão** · técnica: **rastreio de equivalência de recorte**
> (aba × filtro × combinação) + **asserção de fonte** para a parte estrutural

```gherkin
# language: pt

  Regra: aba e filtro de pendentes recortam pela mesma definição, e o filtro continua na tela

    Cenário: [CT-13] o filtro "Somente pendentes de aprovação" continua filtrando depois da extração
      Dado dois usuários pendentes de aprovação e três usuários já aprovados no /admin
      Quando o administrador marca o filtro "Somente pendentes de aprovação" na aba "Todos"
      Então a tabela mostra os dois pendentes
      E não mostra os três aprovados

    Cenário: [CT-14] a aba e o filtro recortam o mesmo conjunto, e combiná-los não muda nada
      Dado dois usuários pendentes de aprovação e três usuários já aprovados no /admin
      Quando o administrador marca o filtro "Somente pendentes de aprovação" com a aba "Pendentes de aprovação" ativa
      Então a tabela mostra exatamente os mesmos dois pendentes que a aba sozinha mostra
      E não mostra os três aprovados

    Cenário: [CT-15] nenhuma página de listagem escreve o recorte de pendentes por conta própria
      Dado o código-fonte das quatro páginas de listagem, sem os comentários
      Quando se procura a condição "aprovacao_pendente" nelas
      Então nenhuma das quatro a contém
      E "AprovacaoDeCadastro" é o único arquivo de app/Filament que escreve where('aprovacao_pendente', true)
```

**Por que CT-15 existe, sendo estrutural**: RQ-05 é uma restrição de **uma definição só**, e ela é
comportamentalmente **invisível hoje** — duas cópias de `where('aprovacao_pendente', true)`
produzem exatamente o mesmo conjunto que uma definição compartilhada, e CT-13/CT-14 ficam verdes
com as cópias. O dano é futuro e é o que a ADR-01 descreve: no dia em que "pendente" mudar de
definição, o filtro diz uma coisa e a aba diz outra, **ambos verdes**. A única asserção que falha
diante da cópia é sobre a fonte. Precedente no kit: `tests/Kit/HelpersDeTesteTest.php` usa
`token_get_all()` pelo mesmo motivo.

**Armadilha de CT-15, medida três vezes neste repositório**: os `getTabs()` novos quase certamente
trarão um docblock dizendo *por que* o `where` não está ali — e a menção derruba a asserção de
ausência. Filtrar comentário **antes** de afirmar ausência é obrigatório (`.ai/rules/testes.md`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | a extração leva o `Filter` junto e o filtro do modal **some** da tela | CT-13 |
| M24 | `filtroDePendentes()` passa a chamar o método extraído **sem o `->query()`**, e o filtro vira decoração que não filtra | CT-13 |
| M25 | o recorte é copiado para dentro de cada `getTabs()` em vez de extraído — quatro cópias, todas corretas hoje | CT-15 |
| M26 | o método extraído é `where('aprovacao_pendente', true)->whereNull('deleted_at')` (o autor "ajuda" o escopo), e a aba passa a divergir do filtro sob o `TrashedFilter` | CT-05, CT-14 |

---

## Regra R6 — as abas de convites recortam por `aceito_em`

> `RQ-07` · perfil **padrão** · técnica: **tabela de decisão** — as quatro situações derivadas de
> `Convite::situacao()` × as três abas. Toda situação é uma partição obrigatória: o estado é
> exibido na coluna "Situação" da mesma linha, e amostrar deixa passar exatamente o caso em que a
> aba e a coluna se contradizem.

| Situação (`Convite::situacao()`) | `aceito_em` | aba `todos` | aba `pendentes` | aba `aceitos` |
|---|---|---|---|---|
| Aceito | preenchido | mostra | **esconde** | **mostra** |
| Recusado | nulo | mostra | **mostra** | **esconde** |
| Expirado | nulo | mostra | **mostra** | **esconde** |
| Pendente | nulo | mostra | **mostra** | **esconde** |

```gherkin
# language: pt

  Regra: a aba "Pendentes" de convites mostra os não aceitos e a aba "Aceitos" mostra os aceitos

    @premissa
    Esquema do Cenário: [CT-16] a aba <aba> de convites mostra <visiveis> e esconde <ocultos>
      Dado quatro convites no /admin, um aceito, um recusado, um expirado e um pendente
      E o relógio parado num instante em que só o convite expirado já passou da validade
      Quando o administrador ativa a aba "<aba>"
      Então a tabela mostra <visiveis>
      E não mostra <ocultos>

      Exemplos:
        | aba       | visiveis                            | ocultos                             | # regra da tabela |
        | todos     | os quatro convites                  | nenhum                              | controle          |
        | pendentes | o recusado, o expirado e o pendente | o aceito                            | aceito_em nulo    |
        | aceitos   | o aceito                            | o recusado, o expirado e o pendente | aceito_em cheio   |

    Cenário: [CT-17] a URL com "?tab=aceitos" abre a listagem de convites já recortada
      Dado um convite aceito e um convite pendente no /admin
      Quando o administrador abre "/admin/convites?tab=aceitos"
      Então a aba ativa é "aceitos"
      E a tabela mostra só o convite aceito
```

**Premissa de CT-16**: as linhas `recusado` e `expirado` dentro de "Pendentes" seguem o literal do
requisito (`whereNull('aceito_em')`) e são o objeto da segunda pergunta aberta. Se a resposta for
"a aba só mostra o realmente pendente", esta é a linha que muda — **e RQ-08 cai junto**, porque o
`TernaryFilter` continuaria com o recorte antigo e o recorte deixaria de ser um só.

**Por que o relógio é congelado**: `situacao()` deriva "Expirado" de `expira_em?->isPast()`, e um
convite criado com validade curta muda de situação sozinho durante a suíte. Sem `travelTo()` /
`freezeTime()` a partição "Pendente" pode virar "Expirado" no meio do caso — flake que se disfarça
de defeito de recorte. O recorte **não** depende do relógio; a *fixture* depende.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | as duas abas trocadas: `pendentes` com `whereNotNull` e `aceitos` com `whereNull` | CT-16 (as duas linhas) |
| M28 | `aceitos` implementada como `where('aceito_em', '!=', null)` — que em SQL **não casa nada** e devolve tabela vazia | CT-16 (linha `aceitos`) |
| M29 | a aba `pendentes` recorta por `situacao() === 'Pendente'` em PHP, depois da query: paginação e contagem passam a mentir, e recusado/expirado somem | CT-16 (linha `pendentes`) |
| M30 | chave `aceito` no singular, e o `?tab=aceitos` cai no ramo silencioso | CT-17 |

---

## Regra R7 — o recorte de convites tem uma definição só, e o ternário continua com três ramos

> `RQ-08` · perfil **padrão** · técnica: **partição dos três ramos do `TernaryFilter`**
> (`true` / `false` / `blank`) + equivalência com a aba + asserção de fonte

```gherkin
# language: pt

  Regra: o TernaryFilter "Pendente" continua com os três ramos e recorta como as abas

    Cenário: [CT-18] o ramo em branco do filtro "Pendente" devolve a listagem inteira
      Dado um convite aceito e um convite pendente no /admin
      Quando o administrador abre o filtro "Pendente" e o deixa em branco
      Então a tabela mostra os dois convites

    Cenário: [CT-19] o filtro e a aba de convites recortam o mesmo conjunto
      Dado um convite aceito e um convite pendente no /admin
      Quando o administrador marca o filtro "Pendente" como "Sim" na aba "Todos"
      Então a tabela mostra exatamente o mesmo convite que a aba "Pendentes" mostra sozinha
      E o convite aceito não aparece

    Cenário: [CT-20] nenhuma página de listagem de convites escreve o recorte por conta própria
      Dado o código-fonte das duas páginas de listagem de convites, sem os comentários
      Quando se procura "aceito_em" nelas
      Então nenhuma das duas o contém
```

**Por que o ramo `blank`, e não os outros dois**: `tests/Kit/FiltrosDeTabelaTest.php` já exercita
`true` e `false` do mesmo filtro, com dataset e com o `assertCanNotSeeTableRecords` do lado oposto —
reexecutar os dois aqui seria cenário que não mata mutante novo (poda do passo 7). O `blank` é o
ramo que **nenhum** teste toca hoje, e é o que a extração pode apagar sem nada ficar vermelho:
`blank: fn ($query) => $query` some, o filtro em branco passa a esconder metade da tabela, e a
suíte inteira continua verde.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M31 | a extração deixa `blank:` para trás e o filtro em branco passa a recortar | CT-18 |
| M32 | `ConvitesTable::pendentes()` criado e o `TernaryFilter` não passa a usá-lo; a aba usa o método, o filtro guarda a cópia | CT-20 (fonte); CT-19 no dia em que os dois divergirem |
| M33 | `pendentes()`/`aceitos()` declarados `protected` numa classe que a `ListConvites` precisa alcançar de fora — a página não compila e as duas telas de convite caem juntas | CT-01 (linhas de convite), CT-16 |

---

## Regra R8 — `/infra/ai-runs` não recebe abas

> `RQ-09` · perfil **mínimo** · técnica: **asserção negativa com controle positivo** — a negativa
> sozinha ficaria verde com a tela inteira quebrada

```gherkin
# language: pt

  Regra: o ledger de execuções de IA continua sem abas, com o filtro de status que já tinha

    Cenário: [CT-21] a listagem de execuções de IA não tem aba nenhuma
      Dado uma execução de IA com status "ok" e outra com status "error"
      Quando a pessoa de infraestrutura abre "/infra/ai-runs"
      Então a listagem não declara nenhuma aba
      E a tabela mostra as duas execuções
```

**Por que o segundo `Então`**: `getTabs()` vazio é o **default** de `HasTabs`, então a asserção
negativa passa também numa tela que não carregou, num 403 renderizado ou num resource apagado. O
controle positivo (as duas execuções na tabela) é o que separa "não ganhou abas" de "não existe
mais". O recorte por `SelectFilter('status')` já tem cenário próprio em
`tests/Kit/FiltrosDeTabelaTest.php` e **não** é reexecutado aqui.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M34 | o padrão dos passos 1 e 2 é aplicado "por consistência" também ao ledger, duplicando o `SelectFilter('status')` em abas | CT-21 (primeiro `Então`) |
| M35 | a asserção negativa é escrita sem controle, e a tela pode quebrar sem ninguém ver | CT-21 (segundo `Então`) — mutante do próprio conjunto de teste, registrado de propósito |

---

## Regra R9 — o README registra a convenção e a não-persistência da aba

> `RQ-10` · perfil **mínimo** · técnica: **asserção de seção**, recortada ao título — não ao
> arquivo inteiro

```gherkin
# language: pt

  Regra: o README explica quando usar aba, quando usar filtro, e que a aba não persiste

    @premissa
    Esquema do Cenário: [CT-22] a seção de convenções de <arquivo> explica a convenção de abas
      Dado o arquivo <arquivo>
      Quando se lê a seção "<titulo>"
      Então ela contém "getTabs", a distinção entre o filtro e a aba, "?tab=" e a negação da persistência

      Exemplos:
        | arquivo      | titulo               |
        | README.md    | ## Convenções do kit |
        | README.en.md | ## Kit conventions   |
```

**Este cenário não tem poder de falsificação sobre o código** — fica verde com a feature quebrada e
vermelho com a feature certa e o texto velho. Está aqui porque RQ-10 é cláusula do requisito, e
cláusula sem caso é omissão que ninguém percebe. É a mesma justificativa, escrita da mesma forma,
dos cinco `*DocumentacaoTest` que o kit já mantém.

**O oráculo é recortado à seção**, do título até o próximo `\n## `, e não ao arquivo inteiro:
"filtro", "aba" e "URL" já aparecem no README por outras razões, e uma busca no arquivo todo
passaria sem a seção existir. Achado transposto de
`tests/Kit/SituacaoDaContaDocumentacaoTest.php`, que já pagou por isso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | só o `README.md` recebe a seção, e o `README.en.md` fica para trás | CT-22 (linha `en`) |
| M37 | a seção descreve as abas e omite que a aba **não** persiste na sessão — que é metade da cláusula, e a metade que gera chamado de suporte | CT-22 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | CT-08, CT-12 — a fronteira aqui é a organização, e "o recurso do outro" é o registro da Globex |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: a feature não cria ação, não muda policy e não é barreira. Quem autoriza é o Resource, e os cenários de barreira vivem em `tests/Tenancy/AdminDaOrganizacaoTest.php`, intocados |
| Idempotência (ancorada no agregado) | **não se aplica**: nenhuma operação de escrita. Ativar a mesma aba duas vezes não tem agregado a ancorar — escrever o cenário produziria um caso tautológico |
| Concorrência | **não se aplica**: leitura sem contador, saldo ou limite |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: nenhuma rota `create`/`edit` é tocada (o `01` declara, e a varredura **S** confirma). O **gate de tela de escrita** não tem alvo nesta feature |
| Domínio condicionado (tipo × valor) | CT-16 — a situação do convite condiciona em que aba ele cai, e é por isso que a técnica é tabela de decisão e não partição de um campo só |
| Estado × operação de escrita | **não se aplica**: a única operação é `listar` |
| Ausente ≠ null ≠ vazio | CT-16 — `aceito_em` nulo é o discriminante das duas abas; `aprovacao_pendente` tem default e nunca é nulo, o que M8 explora |
| Paginação / ordenação | **lacuna declarada**: nenhum cenário insere registros além da primeira página. Tentado enquadrar em CT-03 com o `KIT_TABELA_PAGINACAO=10` fixado no `phpunit.xml` — precisaria de 11+ fixtures para provar algo, e o único defeito plausível (recorte aplicado **depois** da paginação) já morre em M29/CT-16, que afirma sobre o conjunto e não sobre a página. Registrado, não coberto |
| Timezone / DST | **não se aplica ao recorte**: `whereNull('aceito_em')` e `where('aprovacao_pendente', true)` não comparam instantes. O relógio entra só na **fixture** de CT-16, e por isso ele é congelado |
| Unicode / limite de varchar | **não se aplica**: nenhum texto entra pela feature |
| Unicidade + soft delete | CT-04, CT-05 — a metade do soft delete que existe aqui (o pendente excluído). Unicidade não tem alvo: a feature não grava |
| CRUD combinado | **não se aplica**: sem C, U nem D |
| Mass assignment | **não se aplica** à feature — mas é armadilha de **arranjo**: `aprovacao_pendente` está fora do `$fillable`, e a fixture escrita com `User::create([...])` grava `false` em silêncio. Registrado em `## Setup Global`; já coberto como invariante por `tests/Kit/SituacaoDaContaTest.php` (CT-35) |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: o único número é uma contagem inteira |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | abas declaradas, ordem e aba ativa, nas quatro listagens | R1 | EP | componente Livewire | `tests/Kit/AbasDeListagemTest.php` (linhas `/admin`) + `tests/Tenancy/AbasDeListagemTenancyTest.php` (linhas `/app`) | M1, M3, M4, M5, M33 |
| CT-02 | `?tab=pendentes` abre recortado | R1 | partição da porta de entrada | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M2 |
| CT-03 | a aba de pendentes recorta; "Todos" mostra tudo | R2 | EP | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M6, M7, M8, M10 |
| CT-04 | pendente excluído fora da aba e do badge | R2 | EP (terceira partição) | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M9, M14 |
| CT-05 | badge conta o Resource com a lixeira aberta | R3 | equivalência badge × query | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M12, M14, M26 |
| CT-06 | badge em 0, 1 e 2 pendentes | R3 | BVA 3-valores | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M11, M13 |
| CT-08 | a aba do `/app` não mostra pendente de outra organização | R4 | partição de contexto | componente Livewire | `tests/Tenancy/AbasDeListagemTenancyTest.php` | M18, M19 |
| CT-09 | o badge do `/app` conta só a organização corrente | R4 | partição de contexto | componente Livewire | `tests/Tenancy/AbasDeListagemTenancyTest.php` | M16, M17, M19 |
| CT-10 | sem organização corrente, aba vazia e badge zero | R4 | partição de contexto (fail-closed) | componente Livewire | `tests/Tenancy/AbasDeListagemTenancyTest.php` | M22 |
| CT-11 | trocada a organização, badge e tabela trocam junto | R4 | troca de contexto | componente Livewire | `tests/Tenancy/AbasDeListagemTenancyTest.php` | M20, M15 (parcial) |
| CT-12 | a aba de convites do `/app` respeita `tenant_id` | R4 | partição de contexto | componente Livewire | `tests/Tenancy/AbasDeListagemTenancyTest.php` | M21 |
| CT-13 | o filtro do modal continua filtrando | R5 | regressão da extração | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M23, M24 |
| CT-14 | aba e filtro recortam igual e combinam | R5 | equivalência de recorte | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M26 |
| CT-15 | nenhuma página escreve o recorte de pendentes | R5 | asserção de fonte | `tests/Kit` (sem tela) | `tests/Kit/AbasDeListagemTest.php` | M25 |
| CT-16 | as quatro situações de convite × as três abas | R6 | tabela de decisão | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M27, M28, M29, M33 |
| CT-17 | `?tab=aceitos` abre recortado | R6 | partição da porta de entrada | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M30 |
| CT-18 | o ramo em branco do ternário devolve tudo | R7 | partição de ramo | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M31 |
| CT-19 | filtro e aba de convites recortam igual | R7 | equivalência de recorte | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M32 |
| CT-20 | nenhuma página de convites escreve `aceito_em` | R7 | asserção de fonte | `tests/Kit` (sem tela) | `tests/Kit/AbasDeListagemTest.php` | M32 |
| CT-21 | o ledger de IA continua sem abas, e de pé | R8 | negativa + controle | componente Livewire | `tests/Kit/AbasDeListagemTest.php` | M34, M35 |
| CT-22 | o README, em pt e en, traz a convenção | R9 | asserção de seção | `tests/Kit` (sem tela) | `tests/Kit/AbasDeListagemDocumentacaoTest.php` | M36, M37 |

**Numeração**: CT-07 não existe — foi podado (ver abaixo) e o ID **não** é reaproveitado, para que
a rastreabilidade com esta wiki não mude de significado depois.

### Camada: por que nenhum cenário desce a `tests/Unit`

`tests/Pest.php` liga `TestCase` e `RefreshDatabase` a `Feature`, `Kit`, `Tenancy`, `Browser` e
`BrowserTenancy` — **e a nada em `tests/Unit`**. Um caso "unitário" ali rodaria sem container e sem
banco, e todo oráculo desta feature é um conjunto de registros ou uma contagem. A camada mais
barata que o arnês **deste** projeto sustenta é `tests/Kit`; `tests/Tenancy` só onde a organização
é o assunto.

### Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-07 — "o badge da aba não substitui o badge do menu" | só o `01` afirma isso; nenhuma `RQ`. Testar o plano é o defeito que a fronteira existe para evitar |
| a aba `pendentes` com os ramos `true`/`false` do ternário | já provados por `tests/Kit/FiltrosDeTabelaTest.php`, mais barato e mais antigo. Só o `blank` (CT-18) é lacuna real |
| `assertSee('Pendentes de aprovação')` no HTML da listagem | oráculo de layout: passa com a aba renderizada e sem recortar nada. CT-01 afirma sobre a chave, o rótulo e a aba ativa — o mesmo, com oráculo |
| aba "Todos" no `/app` mostrando exatamente os usuários da organização | mata o mesmo mutante que CT-08 (M18/M19) e já é coberto por `tests/Tenancy/AdminDaOrganizacaoTest.php`, que afirma o recorte da listagem sem aba nenhuma |
| ordenação e paginação dentro da aba recortada | não mata nenhum mutante previsto; o defeito plausível (recorte depois da paginação) morre em CT-16 via M29 |
| o ícone `Heroicon::OutlinedClock` na aba | escolha visual que nenhuma `RQ` decompôs — viraria `Então` frouxo e quebraria ao primeiro ajuste de ícone |
| `/infra/ai-runs` com o `SelectFilter('status')` ainda filtrando | já é `tests/Kit/FiltrosDeTabelaTest.php`; CT-21 só precisa do controle de que a tela está de pé |

---

## Sem CT-B

**Confirmado.** O gate do `01` está certo, e a derivação chegou ao mesmo lugar por conta própria.

O gate do `05` exige que o cenário afirme sobre **JavaScript executado, console, acessibilidade,
cor/tema ou layout**. Nenhum dos 21 cenários afirma sobre nada disso:

- a troca de aba é a propriedade Livewire `activeTab` (`HasTabs.php:11`), ligada pelo
  `Tabs::livewireProperty('activeTab')` do próprio Filament — `->set('activeTab', …)` exerce
  exatamente o mesmo caminho do clique, e quem recorta é o servidor;
- a entrada por URL é `#[Url(as: 'tab')]` (`ListRecords.php:53-54`), exercida por
  `Livewire::withQueryParams()`;
- todo `Então` é um conjunto de registros ou uma string de badge, com oráculo no banco;
- não há modal novo, atalho, Alpine, tema nem elemento cuja visibilidade dependa de JS.

**O que um CT-B provaria a mais**: que a tela **renderiza** com as abas presentes. Isso já está
coberto, e não por esta wiki — `/admin/users` e `/admin/convites` estão no lote de `telasDoKit()`
(`tests/Pest.php:243,249`), visitado com `assertNoJavaScriptErrors()`. Um `getTabs()` que estoure na
renderização derruba aquele lote sem cenário novo.

**Registrado como pré-existente, e não criado por esta feature**: `/app/users` e `/app/convites`
estão naquele mesmo lote **respondendo 403**, porque a suíte `tests/Browser` roda single-tenant — o
comentário em `tests/Pest.php:226-237` já declara que aquelas linhas "nunca provaram nada sobre as
telas" e que o conserto é movê-las para `tests/BrowserTenancy`. As abas do `/app` herdam essa
lacuna de smoke. Ela não bloqueia esta feature e não vira CT-B aqui; fica anotada para quem fechar
aquele item.

---

## Fechamento do Ciclo

```bash
composer test:kit
vendor/bin/pest tests/Kit/AbasDeListagemTest.php --mutate --path=app/Filament/Concerns/AprovacaoDeCadastro.php
vendor/bin/pest tests/Kit/AbasDeListagemTest.php --mutate --path=app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php
```

`pestphp/pest-plugin-mutate` 5.0.2 está declarado como dependência direta — não é acidente da
árvore de dependências. Escopar sempre por `--path` (o `--class` não casa de forma confiável).

**O que o mutation score NÃO vai medir aqui, e é o núcleo desta feature**: mutação só muta código
que existe. Os mutantes M1 (ordem das abas), M3 (`getTabs()` esquecido numa das quatro páginas),
M5 (aba `aceitos` ausente), M13 (`->badge()` ausente), M25 (recorte copiado em vez de extraído) e
M36 (README só em pt) são **omissões** — não há linha para mutar, e o score não cai. Quem responde
por eles é a rastreabilidade `RQ` → regra → cenário deste arquivo, não a ferramenta.
