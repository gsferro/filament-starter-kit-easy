# Plano de Ação — O carimbo de organização do Filament sobre fixture de teste

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção (de **suíte e documentação** — nenhuma linha de `app/` muda)
- **Wiki ancestral**: `wikis/specs/feat/abas-nas-listagens/abas-nas-listagens/` — o achado saiu das Notas de Implementação dela
- **Motivo**: a investigação que aquela wiki não tinha orçamento para fazer foi concluída, e a causa derruba a suposição de defeito
- **Toca infra compartilhada?**: **sim** — `ofertaPara()` em `tests/Pest.php` é usado por quatro arquivos de teste. Regressão obrigatória em `tests/Kit` e `tests/Tenancy`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Comportamento documentado onde se tropeça | 3 | rule em `.ai/rules/testes.md` |
| RQ-02 | Caminho explícito para fixture de outra organização | 1 | `ofertaPara()` passa a garantir a organização pedida |
| RQ-03 | Substitui o contorno do CT-12 | 2 | |
| RQ-04 | Registrado que é do vendor e fail-safe | 3 e ADR-01 | a rule diz o que **não** fazer |

## Objetivo

Fechar a armadilha que custou uma hora de investigação: com o painel `app` bootado, o Filament
carimba o `tenant_id` do registro com a organização corrente, **descartando** o valor que o teste
passou. O helper de fixture passa a entregar o que promete, a armadilha vira rule, e o contorno
manual que ficou no CT-12 da wiki anterior sai.

Nada em `app/` muda: a trava do vendor é desejável e o `00-requisito.md` a declara fora de escopo.

## Contexto

`ofertaPara($email, $tenant)` promete um convite naquela organização. Com o painel `app` bootado —
o que acontece em qualquer arquivo de `tests/Tenancy` que faça um request ao `/app` antes — ele
entrega um convite na organização **corrente**. O teste que depende da fronteira falha, e falha
**longe da causa**: a mensagem é "o registro da Globex apareceu na listagem da Acme", que se lê
como vazamento de dados. Foi exatamente essa leitura errada que a wiki anterior teve de descartar
com três medições.

## Análise dos Arquivos Existentes

### `tests/Pest.php:792` — `ofertaPara()`

Faz `Convite::factory()->create([... 'tenant_id' => $tenant?->getKey()])`. O `creating` do vendor
roda depois e sobrescreve. O helper não tem como saber: ele não controla o painel.

### `tests/Tenancy/AbasDeListagemTenancyTest.php` — CT-12

Contorna com `DB::table('convites')->where(...)->update([...])` mais um `refresh()`, e cinco linhas
de comentário explicando. É o contorno que a RQ-03 manda substituir.

## Autorização

Nenhuma mudança. Nenhum arquivo de `app/` é tocado.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** A entrega é helper de teste, rule e documentação.

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum criado. A wiki **documenta** um listener de vendor (`BelongsToTenant::observeTenancyModelCreation()`),
sem alterá-lo.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **Quatro arquivos consomem `ofertaPara()`**. Hoje eles passam porque criam a fixture na
  organização corrente, ou sem painel bootado — nos dois casos o valor gravado já é o esperado, e a
  correção não muda o resultado deles. A regressão existe para provar isso, não porque se espera
  quebra.
- **`tests/Tenancy/AbasDeListagemTenancyTest.php`** perde o contorno e o comentário longo.

## Rollback

`git revert`. Nada de migration, nada de dado, nada em `app/`.

## Dependências

Nenhuma.

## Riscos

- **O helper corrigir demais**: forçar a coluna depois do insert mascararia um dia em que o carimbo
  mudasse de comportamento. Mitigado pelo passo 4 — um caso que afirma o carimbo do vendor
  diretamente, e que fica vermelho se o Filament mudar.
- **A rule virar prosa que ninguém lê**: mitigado por ela ser curta e pelo caso do passo 4 ser o
  enforço.

## Channel de Log da Feature

**Nenhum.** Entrega de suíte; não há execução de aplicação para registrar.

## Estrutura de Implementação

### 1. `ofertaPara()` entrega a organização que promete (RQ-02)

> Skills: `pest-testing`, `ponytail`

- **Path**: `tests/Pest.php`
- Depois do `create()`, quando `$tenant` foi pedido e o gravado divergir, corrigir a coluna e
  `refresh()` — no próprio helper, uma vez, em vez de em cada teste.
- A correção é **condicional** (só quando divergiu): assim ela não esconde o caso em que o vendor
  se comporta como o teste espera, e o passo 4 continua sendo quem mede o carimbo.
- Docblock curto explicando **por que** o helper faz isso, com `vendor:linha`.

### 2. CT-12 perde o contorno (RQ-03)

> Skills: `pest-testing`

- **Path**: `tests/Tenancy/AbasDeListagemTenancyTest.php`
- Remover o `DB::table(...)->update(...)`, o `refresh()` e o bloco de comentário; o helper já
  entrega o convite na Globex.
- Manter a asserção de fonte (`ConviteResource::getEloquentQuery()`), que é o oráculo que separa
  "a aba recorta" de "o Resource recorta".

### 3. A armadilha vira rule (RQ-01, RQ-04)

> Skills: `requirement-to-rule`

- **Path**: `.ai/rules/testes.md` (glob `tests/**`)
- Emenda curta: com o painel bootado, o Filament carimba o `tenant_id` do registro de Resource com
  `$isScopedToTenant`; o valor passado à factory é descartado; **isto é do vendor e é fail-safe —
  não "conserte" a trava**; para fixture de outra organização use `ofertaPara()`, que já trata.
- Preferir emendar rule existente sobre fixtures/tenancy a criar uma nova.
- **Gravar por `record-rule`** se o MCP do Boost estiver de pé; se não, escrever à mão e declarar a
  degradação, como na wiki `travas-de-escalada-de-papeis`.

### 4. Um caso que mede o carimbo do vendor (risco 1)

> Skills: `pest-testing`

- **Path**: `tests/Tenancy/` (arquivo a decidir na derivação dos casos)
- Afirma o comportamento do vendor **diretamente**: com o painel `app` bootado e a Acme corrente,
  um `Convite` criado pedindo a Globex nasce na Acme. É o caso que fica vermelho no dia em que o
  Filament mudar — e que impede o helper do passo 1 de virar mágica silenciosa.
- **Sem este caso, o passo 1 é um curativo que esconde o que ele contorna.**

### 5. Verificação e regressão

- `vendor/bin/pint --dirty --format agent`
- `composer types:check`
- `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — os quatro consumidores de
  `ofertaPara()` são a regressão

## Filosofia de Implementação

> **Ponytail em `full`.** O que a escada decidiu:
> 1. **Não** mexer no vendor nem no `$isScopedToTenant` — a trava é desejável, e o requisito a
>    declara fora de escopo.
> 2. Corrigir **no helper**, uma vez, em vez de em cada teste que precise.
> 3. Emendar rule existente em vez de criar rule nova.
> 4. Um caso de teste, não um parágrafo, como guarda do comportamento de vendor.

## Testes

> Ver `04-casos-de-teste.md`. Sem `05-*-browser.md`: não há superfície de UI.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`

## Commits

- `🧪 test(tenancy): ofertaPara() entrega o convite na organização que promete`
- `📝 docs(rules): o carimbo de organização do Filament sobre fixture, e por que não se conserta`
