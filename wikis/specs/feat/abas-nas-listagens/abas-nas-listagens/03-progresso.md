# Progresso — Abas de recorte nas listagens

> Nível (a) do estudo `estudo-advanced-tables`, aprovado em 2026-08-30.

## 1. Extrair o recorte de pendentes

- [ ] `AprovacaoDeCadastro::recorteDePendentes(Builder): Builder`
- [ ] `filtroDePendentes()` passa a usá-lo
- [ ] Testes de aprovação de cadastro verdes **antes** de acrescentar a aba

## 2. Abas nas listagens de usuários

- [ ] `Admin\Users\Pages\ListUsers::getTabs()`
- [ ] `App\Users\Pages\ListUsers::getTabs()`
- [ ] Badge pela `getResource()::getEloquentQuery()`

## 3. Extrair o recorte de convites

- [ ] `ConvitesTable::pendentes(Builder): Builder`
- [ ] `ConvitesTable::aceitos(Builder): Builder`
- [ ] `TernaryFilter` passa a usá-los

## 4. Abas nas listagens de convites

- [ ] `Admin\Convites\Pages\ListConvites::getTabs()`
- [ ] `App\Convites\Pages\ListConvites::getTabs()`

## 5. Testes

- [ ] `04-casos-de-teste.md` derivado do `00-requisito.md` pela `feature-test-design`
- [ ] Casos escritos e verdes
- [ ] Sem `05-*-browser.md` — a aba é troca de `activeTab` pelo Livewire, com oráculo no banco

## 6. README

- [ ] Convenção "estados distintos ganham `getTabs()`"
- [ ] Aba ativa não persiste na sessão; `?tab=` é o jeito de linkar
- [ ] Por que `/infra/ai-runs` fica de fora

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `composer types:check`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact`
- [ ] `git commit` por bloco

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| nenhuma das quatro páginas sobrescreve `getTabs()` | confirmado nas quatro | nenhuma |
| o recorte de pendentes está numa closure de `Filter` | `AprovacaoDeCadastro.php:75-80` | nenhuma; é a base da ADR-01 |
| o recorte de convites já está em closures separadas | `ConvitesTable.php:60-66`, `queries(true:, false:, blank:)` | nenhuma |
| `Tab` vem de `Filament\Schemas\Components\Tabs\Tab` | usado em `RoleResource.php` | nenhuma |
| `?tab=` funciona sem passo extra | `ListRecords.php:39,54` | nenhuma; o passo do estudo já dizia isso |

### Auditoria Ponytail (step 6)

Herdada do estudo ancestral, que já cortou este nível de 4 passos para o mínimo: sem trait
compartilhada, sem helper, sem env var, sem log, e `/infra/ai-runs` fora. Nada a cortar além.

## Blockers

- Nenhum.

## Desvios do Plano

<!-- preencher na implementação -->

## Notas de Implementação

<!-- preencher na implementação -->

## Retrospectiva

<!-- preencher no fim -->
