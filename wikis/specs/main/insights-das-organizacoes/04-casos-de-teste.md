# Casos de Teste — Insights das organizações no `/admin`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação —
> ela ainda não existe. Do PRD saíram apenas paths, rotas, stack e a tabela `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Carimbo do painel no log de acesso | 3 | 3 | 9 | **completo** |
| Métricas por organização | 2 | 2 | 4 | padrão |
| Autorização e disponibilidade dos widgets | 2 | 3 | 6 | padrão |
| Timeline de atualizações | 1 | 1 | 1 | mínimo |

Justificativa do `completo` no carimbo: o hook vive **dentro do caminho do login**. Uma exceção
ali não produz widget errado — produz instalação em que ninguém entra. E integra com model de
pacote de terceiro, cujo caminho de escrita pode mudar num `composer update` sem aviso.

- Técnicas aplicadas: EP, BVA 3-valores (janela temporal), tabela estado × evento (origem da
  linha de log × presença de painel), matriz papel × visibilidade, rastreio de efeito
- Cenários: **16** · Regras: **9** · Mutantes previstos: **33** · Sem matador: **1** (declarado)
- CT-02 e M5 foram cortados pela auditoria Ponytail da wiki — ver R1

## Divergência declarada com a skill

`.ai/rules/` do projeto não tem rule sobre execução de teste que colida com esta skill. A única
divergência é de comando: a skill sugere `pest --parallel --tia` como padrão, e este conjunto usa
`php artisan test --compact`, que é o comando declarado em `CLAUDE.md` e no `01-plano-acao.md`.
Sem CT-B, o motivo do `--parallel` não se aplica.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | migration aditiva na tabela de `config('authentication-log.table_name')`; hook `creating` no model do pacote; 6 classes de widget fora do `discoverWidgets()`; 2 páginas de Resource alteradas | CT-01, CT-03, CT-16 |
| **F** | carimbar o painel; agregar contagens distintas por organização; agregar acessos por painel; listar alterações; autorizar a exibição | CT-01…CT-15 |
| **D** | `authentication_log` (sucesso × falha, `login_at` dentro × fora da janela, `painel` nulo × preenchido); `tenant_user`; `tenants` (ativo × inativo); `audits`; usuário **em duas organizações**; usuário vinculado que nunca acessou; organização sem nenhum usuário | CT-05…CT-11 |
| **I** | rota `/admin/{slug}` e `/admin/{slug}/{record}`; eventos `Login` e `Failed` do Laravel; **`DB::table()->insert()` direto, que não passa pelo hook** | CT-01, CT-04 |
| **P** | SQLite é o banco da suíte e do default do kit; `dropColumn` recria a tabela no SQLite, então o `down()` é executado, não presumido; `COUNT(DISTINCT)` é portável entre SQLite/MySQL/Postgres | CT-16 |
| **O** | personas `admin`, `master_global`, `infra`, `panel_user` e usuário sem papel; instalação **sem tenancy**; instalação **sem a migration aplicada** | CT-12, CT-13, CT-14, CT-15 |
| **T** | janela de 30 dias com borda exata; ordem cronológica decrescente da timeline; restauração de sessão, que **atualiza** a linha em vez de criar | CT-03, CT-09, CT-10, CT-11 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — toda linha de log criada pelo Eloquent nasce sabendo de qual painel veio | carimbo (completo) | RQ-01 | tabela origem × painel | CT-01 |
| R2 — sem painel corrente, a linha nasce com painel nulo e o login **não** é interrompido | carimbo (completo) | RQ-01 | EP (partição "sem painel") | CT-03, CT-04 |
| R3 — o carimbo acontece na **criação**, e não sobrescreve linha já gravada | carimbo (completo) | RQ-01 | tabela estado × evento (criar × atualizar) | CT-05 |
| R4 — os acessos por painel são agregados por painel, e as linhas sem painel viram fatia própria em vez de sumir | carimbo (completo) | RQ-01 | EP exaustiva do enum de painel + nulo | CT-06, CT-07 |
| R5 — a contagem de uma organização é o número de **pessoas distintas** vinculadas a ela com login bem-sucedido na janela | métricas (padrão) | RQ-02, RQ-04, RQ-05 | EP + rastreio de agregado | CT-08, CT-09 |
| R6 — a janela das métricas é inclusiva na borda | métricas (padrão) | — (**premissa**) | BVA 3-valores | CT-10 |
| R7 — a timeline lista as alterações do cadastro de organizações, mais recente primeiro | timeline (mínimo) | RQ-06 | EP + ordenação | CT-11 |
| R8 — as duas telas exibem os widgets desta feature | métricas (padrão) | RQ-03, RQ-07 | EP (tela de listagem × tela de registro) | CT-12, CT-13 |
| R9 — nenhum widget aparece para quem não alcança o cadastro de organizações, nem quando a fonte de dados não existe | autorização (padrão) | RQ-03, RQ-05 | matriz papel × visibilidade + EP de disponibilidade | CT-14, CT-15, CT-16, CT-17 |

**Técnica escalada acima do perfil**: R4 usa EP **exaustiva** do enum de painel (três painéis mais
a partição nula) numa área que já é `completo` — sem exaustividade, um painel que nunca é
carimbado ficaria invisível e o widget somaria certo mesmo assim.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome da coluna `painel` | escolha de implementação — o requisito pede saber **de qual painel veio o acesso**, não uma coluna com esse nome | detalhe do cenário; o `Então` afirma o valor lido de volta pelo registro |
| FQCN dos seis widgets (`UsuariosUnicosPorOrganizacao`, …) | escolha de implementação | detalhe do cenário |
| `TenantResource::canAccess()` como barreira | escolha de implementação (ADR-03) | detalhe; o `Então` afirma **quem vê e quem não vê**, não qual método decidiu |
| **janela de 30 dias** | só o PRD determina, e é **comportamento visível ao usuário** | ⚠️ **pergunta ao usuário**; R6 e todo cenário dependente marcados `@premissa` |
| **rótulo "antes do registro por painel"** | só o PRD determina, e é texto visível | ⚠️ pergunta; CT-07 afirma que a fatia **existe e soma certo**, não o texto dela |
| `->limit(10)` no breakdown | só o PRD determina, e é visível (organização fora do top some) | ⚠️ pergunta; sem cenário nesta rodada — lacuna declarada em R5 |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **Janela das métricas** — o requisito não diz período nenhum. Premissa adotada: **30 dias**,
  inclusiva na borda. Bloqueia R6. Se negado, CT-09 e CT-10 mudam de valor, não de forma.
- **Organização inativa (`ativo = false`)** — entra nas métricas? Premissa adotada: **entra**,
  porque o requisito fala em "cada tenant" sem recorte. Bloqueia R5. Cenário CT-08 marcado
  `@premissa`.
- **Usuário excluído (soft delete)** — o vínculo permanece em `tenant_user`. Ele conta?
  Premissa adotada: **não conta**, porque a métrica é "quem está usando o sistema" e quem foi
  excluído não está. Bloqueia R5. Cenário CT-09 marcado `@premissa`.
- **Teto de organizações exibidas no breakdown** — o requisito não pede recorte. Premissa
  adotada: mostrar as 10 maiores. Bloqueia nada, mas deixa a 11ª organização invisível sem aviso.

## Setup Global

### Suíte de cada regra

| Regra | Suíte | Por quê |
|---|---|---|
| R1, R2, R3 | `tests/Kit` | o carimbo não depende de tenancy |
| R9 (parcial — sem tenancy) | `tests/Kit` | prova que os widgets somem com `kit.tenancy.enabled = false` |
| R4…R8, R9 (restante) | `tests/Tenancy` | `TenantResource::canAccess()` exige `config('kit.tenancy.enabled')`, que só é `true` em `Tests\TenancyTestCase`. Em `tests/Kit` **todo** cenário de widget passaria por "ninguém vê", medindo o kill-switch em vez da regra |

> Escolher a suíte errada aqui não dá erro: dá conjunto verde que não exercita nada. É a mesma
> armadilha que `.ai/rules/testes.md` documenta para o papel `admin_app`.

### Personas

- `admin` — `usuarioDoKit('admin', 'admin@example.com')` (Kit) / `usuarioComPapel('admin', null, …)` (Tenancy, contexto global)
- `panel_user` — `usuarioComPapel('panel_user', $acme, …)`
- sem papel — `usuarioCom(null)`
- `admin` sem a permissão do cadastro — `semAPermissao('admin', 'ViewAny:Tenant')`

> As três personas de acesso são **pessoas distintas** de propósito. Colapsar dono, administrador
> e chamador na mesma conta deixaria a barreira de R9 sem nenhum cenário que a exercite.

### Fixtures

- `tenant('Acme', 'acme')` e `tenant('Globex', 'globex')` — helpers globais de `tests/Pest.php`
- vínculo: `$user->tenants()->attach($tenant)`
- linha de log: `AuthenticationLog::create([...])` pelo model quando o cenário é sobre **agregação**;
  pelo **evento `Login` real** quando o cenário é sobre o carimbo (R1..R3)
- alteração auditada: `$tenant->update(['nome' => '…'])`, que o `AuditableObserver` já grava

### Fakes

Nenhum. Não há e-mail, fila, HTTP nem notificação nesta feature, e — depois do corte de CT-02 —
nenhum log a espiar.

### Estratégia de DB

`RefreshDatabase` global, aplicado por `tests/Pest.php` em `Kit` e `Tenancy`. Seeders
`ShieldPermissionsSeeder` + `PapeisSeeder`, nessa ordem, no `beforeEach` dos cenários de R9 —
sem eles não há permission no banco e toda persona é indistinguível.

---

## Regra R1 — toda linha de log criada pelo Eloquent nasce sabendo de qual painel veio

> `RQ-01` · perfil **completo** · técnica: **tabela origem × painel** + **rastreio de efeito**

A tabela que dá os cenários — origem da linha × painel corrente:

| Origem da linha | Painel corrente | Esperado |
|---|---|---|
| evento `Login` (sucesso) | `admin` | painel = `admin` |
| evento `Login` (sucesso) | `app` | painel = `app` |
| evento `Failed` (tentativa falha) | `admin` | painel = `admin` |
| evento `Login` | **nenhum** (CLI/console) | painel nulo — R2 |
| `DB::table()->insert()` | qualquer | painel nulo — R2, consequência aceita em ADR-01 |

```gherkin
# language: pt

Funcionalidade: Controle de acessos por painel

  Regra: toda linha de log de acesso criada pelo Eloquent nasce sabendo de qual painel veio

    Esquema do Cenário: [CT-01] o painel corrente é gravado no registro do acesso
      Dado que o usuário está autenticando no painel "<painel>"
      Quando o evento de autenticação "<evento>" é disparado para ele
      Então o registro de acesso mais recente dele aponta o painel "<painel>"
      E o registro guarda o resultado "<sucesso>" da tentativa
      E exatamente um registro de acesso foi criado por esse evento

      Exemplos:
        | painel | evento | sucesso   | # partição              |
        | admin  | Login  | sucesso   | painel administrativo   |
        | app    | Login  | sucesso   | painel do negócio       |
        | infra  | Login  | sucesso   | painel de infraestrutura|
        | admin  | Failed | falha     | tentativa malsucedida   |

```

> **CT-02 foi cortado pela auditoria Ponytail** (`03-progresso.md` → *Auditoria Ponytail*). Ele
> afirmava sobre uma linha de log `debug` a cada carimbo, e o log deixou de existir: ele duplicaria
> um registro que já é gravado. O ID não foi reaproveitado — renumerar quebraria as referências
> cruzadas do `03` e das ADRs. **M6, que só CT-02 matava, migrou para CT-01**, que passa a afirmar
> que o evento produz **exatamente um** registro de acesso.

**Camada**: `Feature` (suíte `Kit`). O `Então` afirma sobre o **registro persistido**, então não é
`Unit`; e o `Quando` é o evento real, não a criação manual do model — é isso que mantém o cenário
vermelho se o pacote mudar o caminho de escrita.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | hook registrado só para o evento `Login`, deixando `Failed` sem carimbo | CT-01 (linha `Failed`) |
| M2 | painel obtido de `config('filament.default_panel')` em vez do painel corrente | CT-01 (linhas `app` e `infra` — todas dariam `admin`) |
| M3 | painel gravado com o **nome de classe** do painel em vez do id | CT-01 (todas as linhas) |
| M4 | o hook grava, mas o valor não é persistido (`$acesso->painel = …` num model sem a coluna no `$fillable`, via `fill()`) | CT-01 (todas — o `Então` relê do banco, não do objeto em memória) |
| M6 | o hook cria uma **segunda** linha em vez de mutar a que está nascendo | CT-01 (terceira asserção) |

---

## Regra R2 — sem painel corrente, a linha nasce com painel nulo e o login não é interrompido

> `RQ-01` · perfil **completo** · técnica: **EP** (partição "sem painel")

Esta é a regra que protege o login. O hook roda dentro do caminho de autenticação; se ele lançar
quando não há painel — ou quando a migration ainda não rodou —, ninguém entra no sistema.

```gherkin
# language: pt

  Regra: sem painel corrente, o acesso é registrado sem painel e a autenticação segue

    Cenário: [CT-03] autenticação fora de painel registra o acesso sem painel
      Dado que não há painel corrente definido
      Quando o evento de login é disparado para o usuário
      Então o registro de acesso mais recente dele tem o painel ausente
      E o campo do painel é nulo, e não uma string vazia

    Cenário: [CT-04] a coluna ausente não derruba a autenticação
      Dado uma instalação em que a coluna do painel ainda não existe na tabela de acessos
      Quando o evento de login é disparado para o usuário
      Então um registro de acesso é criado para ele
      E nenhuma exceção é lançada
```

**Camada**: `Feature` (suíte `Kit`). CT-04 derruba a coluna com `Schema::dropColumn` no arranjo —
é a reprodução fiel de quem atualizou o código e ainda não rodou `migrate`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | `Filament::getCurrentPanel()->getId()` sem o operador seguro — lança fora de painel | CT-03 |
| M8 | painel ausente gravado como `''` em vez de `null` | CT-03 (segunda asserção) |
| M9 | hook registrado sem a guarda de coluna — o `INSERT` estoura e o login morre | CT-04 |
| M10 | guarda de coluna que **desliga o hook para sempre** por memoização estática resolvida antes da migration | ⚠️ **sem matador** — exigiria dois processos no mesmo teste. Lacuna declarada: tentado `Schema::flushCache()` + re-boot do provider no mesmo processo, mas o `boot()` do provider não é re-executável sem recriar a aplicação. Mitigação escrita no PRD: a guarda é `rescue(...)` avaliada no registro, e o registro acontece a cada boot |

---

## Regra R3 — o carimbo acontece na criação, e não sobrescreve linha já gravada

> `RQ-01` · perfil **completo** · técnica: **tabela estado × evento**

O pacote **atualiza** a linha existente quando reconhece restauração de sessão
(`last_activity_at`), em vez de criar outra. Se o carimbo estiver no `saving` em vez do
`creating`, essa atualização reescreve o painel — e um refresh de página no `/app` reescreveria
para `app` o acesso que nasceu no `/admin`.

| Evento no model | Painel corrente na hora | Esperado |
|---|---|---|
| `creating` | `admin` | grava `admin` |
| `updating` (restauração de sessão) | `app` | **mantém** `admin` |

```gherkin
# language: pt

  Regra: o painel é decidido no nascimento do registro e não muda depois

    Cenário: [CT-05] atualizar o registro de acesso não troca o painel gravado
      Dado um registro de acesso nascido no painel "admin"
      Quando esse mesmo registro é atualizado enquanto o painel corrente é "app"
      Então o painel gravado continua sendo "admin"
```

**Camada**: `Feature` (suíte `Kit`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | hook em `saving` em vez de `creating` | CT-05 |
| M12 | hook em `creating` **sem** a guarda de valor já preenchido, combinado com `updateOrCreate` | CT-05 |

---

## Regra R4 — os acessos por painel são agregados por painel, e as linhas sem painel viram fatia própria

> `RQ-01` · perfil **completo** · técnica: **EP exaustiva do enum de painel + partição nula**

Fixture discriminante — os números são diferentes entre si de propósito, para que somar errado
mude o resultado:

| Painel | Acessos com sucesso na janela | Acessos com falha |
|---|---|---|
| `admin` | 3 | 1 |
| `app` | 2 | 0 |
| `infra` | 1 | 0 |
| nulo (histórico) | 4 | 0 |

```gherkin
# language: pt

Funcionalidade: Acessos por painel na tela de organizações

  Regra: o widget de acessos por painel soma os acessos bem-sucedidos de cada painel, e não descarta os acessos sem painel

    Cenário: [CT-06] cada painel aparece com a sua própria contagem
      Dado 3 acessos com sucesso no painel "admin", 2 no "app" e 1 no "infra"
      E 1 tentativa de acesso que falhou no painel "admin"
      Quando o administrador abre o widget de acessos por painel
      Então o painel "admin" aparece com 3 acessos
      E o painel "app" aparece com 2 acessos
      E o painel "infra" aparece com 1 acesso

    Cenário: [CT-07] os acessos anteriores ao carimbo continuam somando
      Dado 4 acessos com sucesso gravados sem painel
      E 3 acessos com sucesso no painel "admin"
      Quando o administrador abre o widget de acessos por painel
      Então existe uma fatia para os acessos sem painel com 4 acessos
      E a soma das fatias exibidas é 7
```

**Camada**: componente Livewire (suíte `Tenancy`) — o widget **é** um componente Livewire, então
`livewire(AcessosPorPainel::class)` o monta e renderiza sem subir a página inteira. O `Então`
afirma rótulo e número adjacentes (`assertSeeInOrder`), não `assertSee` isolado de um dígito.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M13 | `whereNotNull('painel')` para "limpar" o gráfico | CT-07 (a fatia some e a soma cai para 3) |
| M14 | filtro de `login_successful` esquecido | CT-06 (`admin` daria 4) |
| M15 | `groupBy` num campo errado, devolvendo tudo numa fatia só | CT-06 (`app` e `infra` sumiriam) |
| M16 | `COUNT(*)` sobre a tabela inteira em vez de por grupo | CT-06 (todos os painéis com o mesmo número) |

---

## Regra R5 — a contagem de uma organização é o número de pessoas distintas vinculadas com login bem-sucedido na janela

> `RQ-02`, `RQ-04`, `RQ-05` · perfil **padrão** · técnica: **EP + rastreio de agregado**

Fixture discriminante — construída para que cada implementação errada dê um número **diferente**:

| Pessoa | Vínculos | Acessos na janela |
|---|---|---|
| Ana | Acme **e** Globex | 3 com sucesso |
| Bruno | Acme | 1 com sucesso |
| Célia | Acme | nenhum |
| Davi | Globex | 1 que **falhou** |
| Elena | Globex, **excluída** (soft delete) | 1 com sucesso |

Esperado: **Acme = 2** (Ana, Bruno) · **Globex = 1** (Ana).

Ana em duas organizações é o valor que separa as duas leituras de "exclusivo" que o
`00-requisito.md` registra: pela leitura escolhida ela conta nas duas; pela recusada, em nenhuma.

```gherkin
# language: pt

Funcionalidade: Usuários únicos por organização

  Regra: a contagem de uma organização é o número de pessoas distintas vinculadas a ela que entraram na janela

    Cenário: [CT-08] pessoas distintas, e não acessos @premissa
      Dado que Ana pertence à Acme e à Globex e entrou 3 vezes
      E que Bruno pertence à Acme e entrou 1 vez
      E que Célia pertence à Acme e nunca entrou
      Quando o administrador abre o widget de usuários únicos por organização
      Então a Acme aparece com 2 usuários
      E a Globex aparece com 1 usuário

    Cenário: [CT-09] tentativa falha e pessoa excluída não contam @premissa
      Dado que Davi pertence à Globex e a única tentativa dele falhou
      E que Elena pertence à Globex, entrou com sucesso e foi excluída
      Quando o administrador abre o widget de usuários únicos por organização
      Então a Globex não conta Davi nem Elena
```

> `@premissa` em CT-08: a organização inativa entra na contagem.
> `@premissa` em CT-09: pessoa excluída não conta. As duas estão em `## Ambiguidades` do `00`.

**Camada**: componente Livewire (suíte `Tenancy`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | `COUNT(*)` no lugar de `COUNT(DISTINCT user_id)` | CT-08 (Acme daria 4) |
| M18 | contar os **vinculados** em vez de quem acessou | CT-08 (Acme daria 3, com Célia) |
| M19 | ignorar `login_successful` | CT-09 (Globex daria 2, com Davi) |
| M20 | join sem o filtro de morph, casando `authenticatable_id` com id de outro model | CT-08 (números inflados de forma arbitrária) |
| M21 | contar pessoa excluída | CT-09 |
| M22 | atribuir o acesso de Ana a uma organização só (`groupBy` no primeiro vínculo) | CT-08 (Globex daria 0) |

**Lacuna declarada**: o teto de 10 organizações no breakdown não tem cenário. Precisaria de 11
organizações no arranjo para uma regra que o requisito não pede — o custo não se justifica antes
de a pergunta ser respondida. Registrado em `## Fronteira com o Plano`.

---

## Regra R6 — a janela das métricas é inclusiva na borda

> perfil **padrão** · técnica: **BVA 3-valores** · granularidade: 1 segundo (`login_at` é `datetime`)

> **Regra inteiramente `@premissa`.** O requisito não menciona período nenhum. O valor 30 vem do
> plano, e o cenário usa o número literal — não injetado por config — justamente para que um
> default errado fique vermelho.

```gherkin
# language: pt

  Regra: um acesso na borda exata da janela de 30 dias ainda conta

    Esquema do Cenário: [CT-10] a borda da janela @premissa
      Dado uma pessoa da Acme cujo único acesso aconteceu <quando>
      Quando o administrador abre o widget de usuários únicos por organização
      Então a Acme aparece com <usuarios> usuário

      Exemplos:
        | quando                              | usuarios | # borda   |
        | há 29 dias                          | 1        | dentro    |
        | há 30 dias menos 1 segundo          | 1        | borda−1s  |
        | há exatamente 30 dias               | 1        | borda     |
        | há 30 dias e 1 segundo              | 0        | borda+1s  |
```

**Camada**: componente Livewire (suíte `Tenancy`), com `travelTo()` fixando o instante — sem
congelar o tempo, "há exatamente 30 dias" escorrega entre o arranjo e a consulta e a linha da
borda vira flake.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M23 | `>` no lugar de `>=` na comparação de `login_at` | CT-10 (linha "borda") |
| M24 | janela em dias corridos com `startOfDay()`, alargando a borda | CT-10 (linha "borda+1s", que passaria a contar) |
| M25 | janela com número diferente de 30 | CT-10 (linhas "dentro" e "borda+1s") |

---

## Regra R7 — a timeline lista as alterações do cadastro de organizações, mais recente primeiro

> `RQ-06` · perfil **mínimo** · técnica: **EP + ordenação**

```gherkin
# language: pt

Funcionalidade: Timeline de atualizações das organizações

  Regra: a timeline mostra as alterações do cadastro de organizações, da mais recente para a mais antiga

    Cenário: [CT-11] a alteração mais recente aparece antes da mais antiga
      Dado que a Acme foi renomeada há 2 dias
      E que a Globex foi renomeada há 1 hora
      E que um usuário qualquer foi alterado há 10 minutos
      Quando o administrador abre a timeline de atualizações
      Então a Globex aparece antes da Acme
      E a alteração do usuário não aparece
```

**Camada**: componente Livewire (suíte `Tenancy`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | filtro de `auditable_type` ausente — a timeline mistura auditoria de outros models | CT-11 (terceira asserção) |
| M27 | ordenação ascendente | CT-11 (primeira asserção) |

---

## Regra R8 — as duas telas exibem os widgets desta feature

> `RQ-03`, `RQ-07` · perfil **padrão** · técnica: **EP** (listagem × registro)

Esta regra é barata e existe por um motivo específico: widget escrito e **nunca ligado à página**
passa em todos os cenários de R4…R7, que montam o componente direto. O `Então` aqui é sobre a
página, não sobre o widget.

```gherkin
# language: pt

  Regra: a listagem e a tela de uma organização declaram os widgets desta feature

    Cenário: [CT-12] a listagem de organizações declara os quatro widgets agregados
      Dado um administrador da instalação
      Quando ele abre a listagem de organizações
      Então os widgets de visão geral, de usuários únicos, de acessos por painel e de atualizações estão entre os widgets de cabeçalho da página

    Cenário: [CT-13] a tela de uma organização declara os dois widgets do registro
      Dado um administrador da instalação e a organização Acme
      Quando ele abre a tela da Acme
      Então os widgets de métricas e de últimos acessos estão entre os widgets de cabeçalho da página
      E o widget de métricas recebe a Acme como registro
```

**Camada**: componente Livewire (suíte `Tenancy`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | `getHeaderWidgets()` esquecido numa das duas páginas | CT-12 ou CT-13 |
| M29 | widget de registro declarado, mas sem receber o registro (propriedade com outro nome que o Filament não injeta) | CT-13 (segunda asserção) |

---

## Regra R9 — nenhum widget aparece para quem não alcança o cadastro, nem quando a fonte não existe

> `RQ-03`, `RQ-05` · perfil **padrão** · técnica: **matriz papel × visibilidade + EP de disponibilidade**

Matriz — cada célula é uma linha do `Esquema do Cenário`:

| Persona | Tenancy | Fonte de dados | Vê os widgets? |
|---|---|---|---|
| `master_global` | ligada | presente | sim |
| `admin` | ligada | presente | sim |
| `admin` sem `ViewAny:Tenant` | ligada | presente | **não** |
| `panel_user` | ligada | presente | **não** |
| sem papel | ligada | presente | **não** |
| `admin` | **desligada** | presente | **não** |
| `admin` | ligada | **ausente** | **não** |

```gherkin
# language: pt

Funcionalidade: Barreira dos widgets de organização

  Regra: só quem alcança o cadastro de organizações vê os widgets dele

    Esquema do Cenário: [CT-14] a visibilidade segue quem alcança o cadastro
      Dado um usuário com o perfil "<persona>"
      Quando a visibilidade dos widgets de organização é avaliada
      Então o resultado é "<visivel>"

      Exemplos:
        | persona                          | visivel |
        | master_global                    | sim     |
        | admin                            | sim     |
        | admin sem a permissão do cadastro| não     |
        | panel_user                       | não     |
        | sem papel nenhum                 | não     |

    Cenário: [CT-15] revogar a permissão tira os widgets da página
      Dado um administrador sem a permissão de ver o cadastro de organizações
      Quando ele abre a listagem de organizações
      Então a resposta é negada
      E nenhum número de acesso é exibido para ele

  Regra: widget cuja fonte de dados não existe não é exibido, em vez de derrubar a tela

    Cenário: [CT-16] sem a coluna do painel, o widget de acessos por painel some
      Dado uma instalação em que a coluna do painel ainda não existe na tabela de acessos
      Quando um administrador abre a listagem de organizações
      Então a página responde com sucesso
      E o widget de acessos por painel não é exibido
      E os demais widgets continuam sendo exibidos

    Cenário: [CT-17] com a tenancy desligada, nenhum widget desta feature é exibido
      Dado uma instalação com o modo multi-organização desligado
      Quando a visibilidade dos widgets de organização é avaliada
      Então nenhum deles é visível
```

**Camada**: CT-14, CT-16 e CT-17 avaliam o **predicado de visibilidade** — é o oráculo forte, e é
o mesmo desenho de `tests/Kit/PermissoesDeWidgetsTest.php` CT-32, que existe porque afirmar só
"o dado não aparece na página" passa numa implementação que renderiza caixa vazia. CT-15 é o par
comportamental, na página. CT-17 vive em `tests/Kit`; os demais em `tests/Tenancy`.

> **A lista de widgets de CT-14 e CT-17 é varrida do diretório, não escrita à mão.** Escrita à
> mão ela não pega a classe nova — que é exatamente o que ADR-03 aponta como risco, já que estes
> widgets ficam fora do sweep de `PermissoesDeWidgetsTest`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | `canView()` não sobrescrito — o default do Filament é `true` | CT-14 (as três linhas "não") |
| M31 | `canView()` confere só `config('kit.tenancy.enabled')`, sem a permissão | CT-14 (linha "admin sem a permissão") |
| M32 | `canView()` confere só a permissão, sem o kill-switch | CT-17 |
| M33 | verificação de fonte de dados dentro do `getItems()` em vez do `canView()` — a tela renderiza caixa vazia consultando coluna inexistente | CT-16 (segunda asserção) |
| M34 | verificação de fonte de dados aplicada a **todos** os widgets, não só ao que depende da coluna | CT-16 (terceira asserção) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: o `/admin` não é escopado por organização por decisão de projeto (`TenantResource` — "quem administra os tenants precisa enxergar todos"). Não há recurso de "outra pessoa" a proteger; a barreira é vertical e está em CT-14 |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: os seis widgets são leitura, sem nenhuma Action |
| Idempotência (ancorada no agregado) | CT-05 — o agregado é o registro de acesso, e o segundo evento sobre ele não deve mudar o painel |
| Concorrência | **não se aplica**: não há contador, saldo nem limite; dois logins simultâneos produzem duas linhas, que é o comportamento certo |
| **Fronteira no ponto de entrada** (janela temporal) | CT-10 |
| **Domínio condicionado** (painel presente × ausente) | CT-06, CT-07 |
| **Estado × operação** (organização inativa; usuário excluído) | CT-08 (`@premissa`), CT-09 (`@premissa`) |
| Ausente ≠ null ≠ vazio | CT-03 (painel nulo, não string vazia) |
| Paginação / ordenação | CT-11 (ordenação da timeline). Teto do breakdown: **lacuna declarada** em R5 |
| Timezone / DST | **lacuna declarada**: tentado divergir `app.timezone` do banco para expor comparação em fuso errado; o SQLite da suíte grava `datetime` sem fuso e o Carbon do arranjo e da consulta usam o mesmo `app.timezone`, então as duas implementações convergem e o cenário não discriminaria. CT-10 cobre a fronteira em segundos, que é o que resta observável |
| Unicode / limite de varchar | **não se aplica**: o único campo escrito por esta feature é o id do painel, cujo domínio é fechado pelo Filament |
| Unicidade + soft delete | CT-09 (pessoa excluída com vínculo vivo) |
| CRUD combinado | **não se aplica**: a feature não acrescenta operação de escrita além do carimbo |
| Mass assignment | **não se aplica**: a coluna do painel é escrita só pelo hook e não está no `$fillable` do model do pacote; nenhum formulário a alcança |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica**: todas as métricas são contagens inteiras |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | painel corrente gravado no registro | R1 | tabela origem × painel | Feature | `tests/Kit/CarimboDePainelNoAcessoTest.php` | M1, M2, M3, M4, M6 |
| CT-03 | fora de painel grava nulo | R2 | EP | Feature | `tests/Kit/CarimboDePainelNoAcessoTest.php` | M7, M8 |
| CT-04 | coluna ausente não derruba o login | R2 | EP | Feature | `tests/Kit/CarimboDePainelNoAcessoTest.php` | M9 |
| CT-05 | atualizar não troca o painel | R3 | estado × evento | Feature | `tests/Kit/CarimboDePainelNoAcessoTest.php` | M11, M12 |
| CT-06 | contagem por painel | R4 | EP exaustiva | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M14, M15, M16 |
| CT-07 | fatia dos acessos sem painel | R4 | EP (partição nula) | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M13 |
| CT-08 | pessoas distintas, não acessos | R5 | EP + agregado | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M17, M18, M20, M22 |
| CT-09 | falha e pessoa excluída não contam | R5 | EP | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M19, M21 |
| CT-10 | borda da janela | R6 | BVA 3-valores | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M23, M24, M25 |
| CT-11 | ordem e recorte da timeline | R7 | EP + ordenação | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M26, M27 |
| CT-12 | listagem declara os quatro widgets | R8 | EP | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M28 |
| CT-13 | tela do registro declara os dois widgets | R8 | EP | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M28, M29 |
| CT-14 | matriz de visibilidade | R9 | matriz papel × visibilidade | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M30, M31 |
| CT-15 | permissão revogada tira da página | R9 | matriz | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M30 |
| CT-16 | fonte ausente esconde só o widget dela | R9 | EP de disponibilidade | Livewire | `tests/Tenancy/InsightsDasOrganizacoesTest.php` | M33, M34 |
| CT-17 | tenancy desligada esconde todos | R9 | EP | Feature | `tests/Kit/CarimboDePainelNoAcessoTest.php` | M32 |

## Sem CT-B

Nenhum cenário desta feature afirma sobre algo que **só o navegador prova**.

Os seis widgets são leitura renderizada no servidor: não têm JavaScript próprio, não abrem modal,
não dependem de Alpine, não introduzem cor nem componente de tema, e não acrescentam elemento
interativo. Tudo o que eles afirmam — número, rótulo, ordem, presença, ausência — é falsificável
por teste de componente Livewire, em milissegundos e sem Node.

Cenários cogitados e cortados:

| Cenário cogitado | Por que foi cortado |
|---|---|
| `/admin/organizacoes` sem erro de JavaScript com os widgets novos | já coberto pela varredura de telas existente do kit, que visita as rotas dos três painéis |
| o breakdown renderiza a barra proporcional certa | é pixel; o oráculo útil é o número, e ele está em CT-06 |
| a timeline agrupa por dia | comportamento do componente de vendor, não desta feature |

## Fechamento do ciclo — mutation testing

Depois de implementar:

```bash
php artisan test --compact tests/Kit/CarimboDePainelNoAcessoTest.php tests/Tenancy/InsightsDasOrganizacoesTest.php
vendor/bin/pest tests/Tenancy/InsightsDasOrganizacoesTest.php --mutate --path=app/Filament/Admin/Resources/Tenants/Widgets
vendor/bin/pest tests/Kit/CarimboDePainelNoAcessoTest.php --mutate --path=app/Providers/KitServiceProvider.php
```

`pestphp/pest-plugin-mutate ^5.0` está declarado direto no `composer.json` — não é transitivo,
então o comando não some num `composer update`.

Mutante sobrevivente é traduzido de volta para a lacuna de derivação (borda faltando, linha de
tabela de decisão faltando, oráculo fraco, efeito não verificado) e vira cenário novo aqui —
nunca ajuste no teste para "ficar verde".
