# Progresso — Estudo de viabilidade: Advanced Tables e alternativas

> **Esta wiki é um ESTUDO (RQ-05).** Nenhum passo do `01-plano-acao.md` foi ou será executado nesta branch.
> Não há `04-casos-de-teste.md`, `05-*-browser.md` nem `06-relatorio-qa.md`: sem código não há superfície
> validável, e o quality gate é pulado por esse motivo. Quando um nível for aprovado, abre-se uma wiki nova
> (tipo `nova`, ancestral = esta) e a `feature-test-design` deriva os CTs do `00-requisito.md` daquela.

## Método (RQ-07)

- [x] Worktree isolado; branch `feat/estudo-advanced-tables`
- [x] Sub-agente 1 — pacote pago: página do plugin, `docs.advancedtables.com/v5/*` (índice via `/llms.txt`), changelog Anystack/Privato. 63 chamadas de ferramenta
- [x] Sub-agente 2 — alternativas gratuitas: `filamentphp.com/plugins`, busca web, Packagist API e raw `composer.json` no GitHub para 7 dos 14 candidatos; API do GitHub para stars e último push. 90 chamadas de ferramenta
- [x] Agente principal — vendor do kit (`filament/filament v5.7.6`, `filament/tables v5.7.6`, `asmit/resized-column 4.0.2`, `laravel/framework v13.26.1`), `search-docs` do Boost (tabs, filters, column manager, query builder), código do kit (`ConfiguraFilamentGlobal`, `config/kit.php`, dez `List*.php`, filtros existentes)
- **Limitação registrada**: a varredura do diretório é por termos (table, filter, view, column, preset, saved); um plugin com nome fora desses termos pode ter escapado. Repetir antes de aprovar o nível (b).

## Estudo

- [x] `00-requisito.md` — texto verbatim + 7 cláusulas + 3 ambiguidades com premissa e "se negado"
- [x] `01-plano-acao.md` — funcionalidades do pago, 14 alternativas em tabela, o que o kit já cobre (com `arquivo:linha`), 16 passos em 3 níveis, estimativa em dias
- [x] `02-decisoes-arquiteturais.md` — 6 ADRs; recomendação na ADR-06
- [x] `03-progresso.md` — este arquivo

## Níveis do plano (NÃO executados — referência para a wiki futura)

### Nível (a) — abas e botões nativos — 1 a 2 dias
- [ ] Passo 1 — abas em `ListUsers` (admin e app)
- [ ] Passo 2 — abas em `ListConvites` (admin e app)
- [ ] Passo 3 — testes em `tests/Kit` e `tests/Tenancy`
- [ ] Passo 4 — README

### Nível (b) — visões salvas por usuário — 4 a 6 dias
- [ ] Passo 5 — migration e model `VisaoDeTabela`
- [ ] Passo 6 — policy e `custom_permissions` do Shield + `PapeisSeeder`
- [ ] Passo 7 — trait `TemVisoesSalvas`
- [ ] Passo 8 — testes (Kit + Tenancy + inventário de Actions)
- [ ] Passo 9 — README

### Nível (c) — pacote gratuito publicável — 15 a 25 dias + manutenção
- [ ] Passo 10 — extração para `gsferro/filament-table-views-easy`
- [ ] Passo 11 — Preset Views
- [ ] Passo 12 — compartilhamento e View Manager
- [ ] Passo 13 — publicação

## Verificação Final

- [x] `/ponytail:ponytail-review` na wiki (ver Auditoria abaixo)
- [x] Commit `:memo: docs(wiki): estudo de viabilidade — advanced tables e alternativas`
- [x] `git push -u origin feat/estudo-advanced-tables` — sem PR, por instrução

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `applyTableColumnManager()` talvez não seja público | é público: `vendor/filament/tables/src/Concerns/HasColumnManager.php:77` | passo 9 reescrito sem o "confirmar na implementação" |
| render hook `RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE` "a confirmar" | existe em `vendor/filament/filament/src/View/PanelsRenderHook.php:103` | tabela de UI cita a linha |
| colunas persistem na sessão "por default, segundo a doc" | `protected bool | Closure $persistsColumnsInSession = true` em `HasColumnManager.php:39` | tabela "o que o kit já tem" cita a linha em vez da doc |
| `search-docs` do Boost disponível | primeira chamada com 10 queries devolveu erro 500; com 4 queries funcionou | nada a corrigir; registrado para a próxima wiki reduzir o lote |
| worktree já estaria em `feat/estudo-advanced-tables` | estava em `worktree-agent-…`; `git checkout -b` criou o branch pedido | nada na wiki |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | Nível (a) não precisa de trait nem helper compartilhado — `getTabs()` direto em cada `ListRecords` | sim | `01`, passos 1–2 (só a extração da closure já duplicada em `AprovacaoDeCadastro`) |
| 2 | Nível (b) sem Resource de administração de views, sem aprovação, sem "pública" — só pessoal | sim | `01`, passo 5; compartilhamento fica no nível (c) |
| 3 | Sem `04`/`05` para estudo — cenários inline nos passos 3 e 8 bastam como insumo | sim | `01` → Testes; `03` → nota de abertura |
| 4 | Channel de log só no nível (b); (a) não loga | sim | `01` → Channel de Log |
| 5 | Barra de favoritos própria (Blade + CSS) recusada em favor de `ActionGroup` | sim | ADR-05 |
| 6 | yagni: aba por status em `/infra/ai-runs` duplicava o `SelectFilter('status')` já na tela — passo removido | sim | `01`, passo 2 registra a exclusão; Análise de `AiRunsTable` |
| 7 | yagni: "botão que abre outra tela filtrada" sem card que o peça — deixou de ser passo, virou frase no passo 1 (o mecanismo de URL) | sim | `01`, passo 1 |
| 8 | yagni: `KIT_TABELA_VISOES_SALVAS` + toggle em settings + migration quando o trait já é opt-in por tela — passo removido, env var removida | sim | `01`, Variáveis de Ambiente, Rollback, passo 7 |
| 9 | delete: colunas `icone`, `cor`, `favorita` na tabela de views — Favorites Bar sem Favorites Bar; ficam `nome`, `estado`, `padrao` | sim | `01`, passo 5; ADR-04 |

Resultado da auditoria: 16 passos → 13; 9 colunas → 6; 1 env var → 0. Estimativa em dias mantida (as faixas já absorviam o corte).

## Blockers

- Nenhum.

## Desvios do Plano

- Não se aplica (estudo).

## Notas de Implementação

- Não se aplica (estudo). Descobertas de vendor relevantes para quem implementar: ADR-02 (URL vence sessão; URL funciona com `deferFilters()`), ADR-04 (formato interno do estado de filtro), ADR-05 (`configureUsing()` global derruba tabelas de vendor).

## Retrospectiva

- **Funcionou bem**: dois sub-agentes em paralelo (pago × gratuitos) com escopo fechado; o agente principal ficou só com vendor + código do kit. As duas pesquisas voltaram em menos de 5 minutos cada.
- **Faltou no plano**: o worktree nasce em branch com nome de agente, não no branch da feature — a instrução precisa incluir o `checkout -b`. E o `search-docs` do Boost não aguenta lotes grandes de queries.
