# Progresso — O carimbo de organização sobre fixture de teste

> Correção de **suíte e documentação**. Nenhuma linha de `app/` muda — ver ADR-01.

## 1. `ofertaPara()` entrega a organização que promete

- [x] Correção condicional no helper, com `vendor:linha` no docblock
- [x] Os quatro consumidores existentes continuam verdes

## 2. CT-12 perde o contorno

- [x] `DB::table()->update()`, `refresh()` e o bloco de comentário removidos
- [x] Asserção de fonte (`ConviteResource::getEloquentQuery()`) mantida

## 3. A armadilha vira rule

- [x] Emenda em `.ai/rules/testes.md`, dizendo também o que **não** fazer (ADR-01)
- [x] Escrita à mão: o MCP `laravel-boost` não conectou nesta sessão (`CONNECT_TIMEOUT`). Emenda de rule já indexada, então o `index.md` não muda — mas rule NOVA exige `record-rule` com o Boost de pé

## 4. Um caso que mede o carimbo do vendor

- [x] Cenário que afirma o carimbo diretamente, e fica vermelho se o Filament mudar

## Testes

- [x] `04-casos-de-teste.md` derivado do `00-requisito.md` pela `feature-test-design` — 5 cenários, 4 regras, 19 mutantes, 1 sem matador declarado. Os rótulos dos testes foram realinhados a ele e CT-05 entrou depois
- [x] Sem `05-*-browser.md` — sem superfície de UI

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff — um corte aplicado (`getTable()`)
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check`
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — 1753 passando
- [x] `git commit` por bloco

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "o convite nasce na organização errada" (defeito do kit) | é o `creating` do vendor em `BelongsToTenant.php:158-185`, e ele é **fail-safe** | o `00` registra que a suposição do pedido foi derrubada; escopo mudou de "corrigir" para "documentar e guardar" |
| o carimbo vale sempre | só com o painel do Resource **bootado** e organização corrente — medido: 0 listeners sem boot, 1 com boot | as duas condições estão na tabela de medições do `00` |
| afeta o convite | afeta todo Resource com `$isScopedToTenant`: `Convite` e `Projeto` no kit; o `UserResource` do /app declara `false` (`:73`) | `Projeto` declarado fora de escopo no `00`, coberto pela rule |
| o modo de falha é falso verde | é **vermelho longe da causa** ("o registro da Globex apareceu na listagem da Acme") | severidade rebaixada; a justificativa da wiki passa a ser o custo de investigação, não risco de falso verde |
| `ofertaPara()` é usado por poucos | quatro arquivos (o próprio docblock dele registra a unificação de dois near-clones) | regressão declarada como obrigatória |

### Auditoria Ponytail (step 6)

| # | Sugestão | Aplicada? | Onde |
|---|---|---|---|
| 1 | Não tocar em `app/` nem no `$isScopedToTenant` — a alternativa perigosa | sim | ADR-01, alternativa 1 |
| 2 | Corrigir no helper, uma vez, em vez de em cada teste | sim | passo 1 |
| 3 | Emendar rule existente em vez de criar rule nova | sim | passo 3 |
| 4 | Correção **condicional**, para não mascarar mudança de vendor | sim | passo 1 |
| 5 | Um caso de teste como guarda, não um parágrafo | sim | passo 4 |

## Blockers

- Nenhum.

## Desvios do Plano

- **O passo 4 foi implementado ANTES do passo 2.** O plano os numerava na ordem inversa; escrever
  primeiro o caso que mede o carimbo e só então tirar o contorno do CT-12 significa que o
  comportamento nunca ficou desguardado entre um commit e outro. Ordem, não escopo.
- **O caso do passo 4 virou cinco cenários, não um.** O plano pedia "um caso que mede o carimbo".
  A derivação mostrou que um só não distingue as coisas que precisam ser distinguidas: as duas
  guardas do vendor (painel bootado, organização corrente) são partições próprias, e a
  condicionalidade da correção do helper precisa de cenário dedicado — senão uma correção
  incondicional passa despercebida.

- **O `01` subestimava o raio em uma ordem de grandeza.** Ele dizia "quatro arquivos consomem
  `ofertaPara()`"; são **52 chamadas em 11 arquivos**, medido na derivação dos casos. Corrigido no
  plano — é esse número que define o perfil de risco da entrega.
- **Os rótulos `[CT-nn]` dos testes foram realinhados** ao `04` depois que ele chegou: os cenários
  tinham sido escritos antes da derivação, com numeração própria. Rastreabilidade quebrada é pior
  que rótulo feio.
- **CT-05 entrou depois**, vindo da derivação: cardinalidade do efeito mais preservação dos demais
  argumentos. Sem ele, uma correção escrita com `where('email', …)` em vez de `where('id', …)`
  passaria em todos os outros cenários.

## Notas de Implementação

- **A correção do helper compara `(int)` dos dois lados.** `tenant_id` volta do banco como int e o
  `getKey()` também, mas o valor pode chegar por `$atributos['tenant_id']` como string; sem o cast
  a comparação diverge e o `update` roda sempre — que é justamente o incondicional que o CT-04
  reprova.
- **O `$pedido` sai de `$atributos['tenant_id'] ?? $tenant?->getKey()`**, nessa ordem: o array de
  atributos vence o parâmetro no `create()` (`...$atributos` vem por último), então a correção tem
  de honrar a mesma precedência — senão ela "corrige" para o valor errado quando alguém passa a
  organização pelos atributos.
- **CT-04 usa `updated_at` como oráculo de não-escrita.** É o que distingue "não precisou corrigir"
  de "corrigiu para o mesmo valor": um `update` incondicional mexeria no timestamp mesmo gravando o
  valor idêntico.
- **O caso do passo 4 carrega a mensagem de falha explicando o que fazer** ("O Filament deixou de
  carimbar: revise ofertaPara() e a rule de testes"). Teste que guarda comportamento de vendor falha
  no upgrade, e quem lê a falha meses depois não tem o contexto desta sessão.

- **O global scope de LEITURA do vendor mordeu dentro do próprio caso que mede a escrita.** CT-05
  contava com `Convite::query()->where('email', …)->count()` e obtinha **1** em vez de 2: o mesmo
  trait que carimba na criação registra, no boot, um `addGlobalScope($panel->getTenancyScopeName())`
  (`BelongsToTenant.php:143-156`), então a leitura por Eloquent devolve só a organização corrente. A
  contagem foi para o banco cru — senão o caso mediria o escopo em vez da cardinalidade da correção.
  É a confirmação prática do que a ADR-01 argumenta: `$isScopedToTenant = false` desligaria os dois
  lados, não só o carimbo.
- **`DB::table($convite->getTable())`, não `DB::table('convites')`** — único corte do
  `/ponytail:ponytail-review` no diff. Mesmo tamanho, sobrevive a rename de tabela.

## Retrospectiva

- **Funcionou**: investigar até a causa antes de escrever a wiki. A premissa do pedido ("um convite
  criado no contexto errado nasce na organização errada" = defeito) estava errada, e uma wiki de
  correção teria produzido a alternativa perigosa da ADR-01 — `$isScopedToTenant = false`, que
  desligaria o global scope de leitura junto.
- **Funcionou**: o par correção + caso de medição. Cada um sozinho seria pior que nada: a correção
  vira no-op silenciosa no próximo upgrade, e o caso sozinho deixaria todo teste de fronteira com
  cinco linhas de contorno.
- **Faltou na wiki anterior**: uma hora de investigação foi gasta lendo "o registro da Globex
  apareceu na listagem da Acme" como vazamento de dados. A rule agora nomeia esse modo de falha,
  que é o que teria encurtado a busca.
