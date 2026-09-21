# Progresso — Link de acesso ao painel da organização

## 1. Gerador da URL
- [ ] Um ponto único que devolve a URL do painel da organização

## 2. `TenantForm` — no `EditTenant`
- [ ] Entrada na seção `Identificação`, junto de nome e slug
- [ ] Ausente no `CreateTenant`

## 3. `TenantInfolist`
- [ ] Entrada na seção `Identificação`

## 4. `TenantsTable`
- [ ] Coluna com a URL clicável

## 5. Documentação
- [ ] `docs/{pt,en}/recursos/multi-tenancy.md`
- [ ] `CHANGELOG.md` → `[Unreleased]` → `Adicionado`

## Testes
- [ ] `tests/Tenancy/{Nome}Test.php` — CTs do `04`

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] CTs da feature
- [ ] Testes existentes do tenant (`tests/Tenancy/**`)
- [ ] `composer test:kit`
- [ ] **Custo medido** — queries da listagem antes × depois
- [ ] Citações `arquivo:símbolo:linha` reverificadas — inclui a **citação de terceiro** do achado R2
- [ ] IDs `[CT-nn]` do teste ⊆ `04` e vice-versa
- [ ] `/code-review` no diff (step 7.5)

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `filament.md` | `app/Filament/**` | a preencher | — |
| `resources.md` | `app/Filament/App/Resources/**` | **n.a.** | a feature toca `Admin/Resources`, não `App/` |
| `filament-resources.md` | `app/Filament/**/Resources/**` | a preencher | — |
| `specs.md` | `wikis/specs/**` | a preencher | citações por símbolo, conferidas por grep |
| `testes.md` | `tests/**` | a preencher | — |

## Quality Gate

<!-- Preenchido no step 8. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| # | Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|---|
| **R1** | Passo 1 manda "conferir se já existe helper de URL de tenant antes de escrever" | **Não existe.** `grep -rn "getTenantUrl\|tenantUrl" app/` devolve **zero**; os únicos hits de `filament.app` são nomes de rota em contextos não relacionados (`DashboardClassico.php`, `AcoesDeCriacao.php`, `ConfiguraFilamentGlobal.php`, `AppPanelProvider.php`). O passo 1 **cria**, não reaproveita | Passo 1 do `01` reescrito de "conferir e reaproveitar" para "criar, e aqui está a evidência de que não havia o que reaproveitar" |
| **R2** | O plano não previa impacto em citação de terceiro | **`tests/Tenancy/FiltrosDeTabelaTenancyTest.php:9` cita `TenantsTable.php:55`** — e a linha 55 hoje é o `->filters([`. A coluna nova entra no `->columns([…])`, que fecha na linha 53: **inserir a coluna desloca a linha 55 e invalida a citação de um teste que não é desta feature** | `## Impacto em Features Existentes` do `01` ganhou o item; entrou na Verificação Final como conferência obrigatória; e virou risco declarado |

> **R2 é o achado que justifica o step 5 nesta feature.** A citação está no **docblock de um teste
> alheio**, não na minha wiki — nenhum gate posterior a procuraria, porque o step 7 confere as
> citações *da wiki da feature*. E a rule `specs.md` do projeto trata citação errada como defeito.
> Uma coluna de tabela, que parece a mudança mais inócua possível, quebra uma afirmação a 40 linhas
> de distância num arquivo que o diff não abre.

### Varredura da classe irmã

**Não se aplica: a feature não cria classe nenhuma.** São três entradas declarativas em schemas
existentes (`TenantForm`, `TenantInfolist`, `TenantsTable`) mais um gerador de URL. Nenhum FQCN novo
para aparecer em `config/`, seeder, inventário de teste ou provider.

Conferido mesmo assim, porque "não se aplica" precisa ser verificado e não deduzido:
`grep -rn "TenantsTable\|TenantForm\|TenantInfolist" app config database tests` — as ocorrências são
só o próprio `TenantResource` e os dois testes de filtro já citados em R2.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | a rodar | — | — |

## Blockers

Nenhum.

## Desvios do Plano

<!-- Preenchido durante a implementação. -->

## Notas de Implementação

<!-- Preenchido durante a implementação. -->

## Retrospectiva

<!-- Preenchido no fim. -->
