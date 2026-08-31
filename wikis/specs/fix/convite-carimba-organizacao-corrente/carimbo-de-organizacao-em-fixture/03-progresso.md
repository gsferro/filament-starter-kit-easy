# Progresso — O carimbo de organização sobre fixture de teste

> Correção de **suíte e documentação**. Nenhuma linha de `app/` muda — ver ADR-01.

## 1. `ofertaPara()` entrega a organização que promete

- [ ] Correção condicional no helper, com `vendor:linha` no docblock
- [ ] Os quatro consumidores existentes continuam verdes

## 2. CT-12 perde o contorno

- [ ] `DB::table()->update()`, `refresh()` e o bloco de comentário removidos
- [ ] Asserção de fonte (`ConviteResource::getEloquentQuery()`) mantida

## 3. A armadilha vira rule

- [ ] Emenda em `.ai/rules/testes.md`, dizendo também o que **não** fazer (ADR-01)
- [ ] Gravada por `record-rule` — ou degradação declarada se o MCP do Boost não conectar

## 4. Um caso que mede o carimbo do vendor

- [ ] Cenário que afirma o carimbo diretamente, e fica vermelho se o Filament mudar

## Testes

- [ ] `04-casos-de-teste.md` derivado do `00-requisito.md` pela `feature-test-design`
- [ ] Sem `05-*-browser.md` — sem superfície de UI

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
- [ ] `git commit` por bloco

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

<!-- preencher na implementação -->

## Notas de Implementação

<!-- preencher na implementação -->

## Retrospectiva

<!-- preencher no fim -->
