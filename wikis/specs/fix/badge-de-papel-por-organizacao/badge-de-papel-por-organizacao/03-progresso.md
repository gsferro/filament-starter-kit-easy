# Progresso — O badge de papel reflete a organização ativa

> Correção nascida de uso real na instalação `TESTES KIT/v0223-tenancy`.
> Toca `User::papelDoPainel()`, que tem suíte nas duas suítes do badge → regressão obrigatória.

## 1. `papelDoPainel()` aceita o contexto da organização

- [x] Assinatura `papelDoPainel(string $painel, ?int $contexto = null): ?string`
- [x] `wherePivot($this->colunaDeTeam(), $contexto)` quando há contexto
- [x] Atalho do `master_global` continua antes de tudo
- [x] Default `null` preserva todos os chamadores existentes

## 2. A view passa a organização corrente

- [x] `$contextoDoBadge = filament()->getTenant()?->getKey()`
- [x] Repassado a `papelDoPainel($painelDoBadge, $contextoDoBadge)`

## 3. README

- [x] `README.md` — o badge mostra o papel da organização aberta; sem papel nela, não há badge
- [x] `README.en.md` — mesma nota

## 4. Testes

- [x] `04-casos-de-teste.md` derivado do `00-requisito.md` pela `feature-test-design`, cega à implementação
- [x] CT-01 (3 organizações, bidirecional), CT-02, CT-03, CT-04, CT-05, CT-07 escritos em `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php`
- [x] Sem `05-*-browser.md` — gate confirmado pela derivação: todo oráculo é texto na resposta HTTP
- [x] **Mutação manual**: removido o contexto da view → CT-01, CT-02 e CT-03 ficam vermelhos; restaurado → 45/45

## Verificação Final

- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check`
- [x] `php artisan test tests/Kit/CabecalhoDoMenuDoUsuarioTest.php tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` — 45/45
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --compact` — a regressão que prova RQ-05
- [ ] `/ponytail:ponytail-review` no diff
- [ ] Conferência na instalação `v0223-tenancy` com o usuário do relato
- [ ] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| o "card do perfil" é o badge do menu do usuário | `resources/views/filament/perfil-indicator.blade.php`, incluído por `user-menu-header.blade.php:51` | nenhuma; confirmou o alvo |
| a causa é o `first()` sem contexto | `app/Models/User.php:398` — `papeisEmQualquerContexto()` sem `wherePivot`, resolvido por `first()` | nenhuma |
| o mecanismo de filtrar já existe | `temPapelOnde()` aplica `wherePivot($this->colunaDeTeam(), $contexto)` | nenhuma; é o que torna o passo 1 de três linhas |
| há decisão anterior afirmando o comportamento atual | `tests/Tenancy/...TenancyTest.php` — o caso "acha o papel mesmo fora do contexto" | ADR-01 passa a separar exibição de acesso |
| o caso anterior **mudaria de oráculo** | **errado**: ele chama `papelDoPainel('app')` sem contexto, e o default `null` o preserva | `## Impacto` corrigido — nenhum teste existente precisou mudar |
| "`/app` sem organização selecionada" é um estado possível | **errado**: `AppPanelProvider:504` não tem `tenantRegistration()` e `:510` reescreve toda rota para `/app/{tenant}` | premissa **retirada** do `00` |

### Auditoria Ponytail (step 6)

| # | Sugestão | Aplicada? | Onde |
|---|---|---|---|
| 1 | Parâmetro opcional em vez de método novo (`papelDoPainelNaOrganizacao()`) | sim | ADR-01, alternativa 1 |
| 2 | Reutilizar `wherePivot` + `colunaDeTeam()` em vez de query nova | sim | passo 1 |
| 3 | Não usar `ContextoDePapeis` (é ferramenta de escrita, com efeito global) | sim | ADR-01, alternativa 2 |
| 4 | Nenhum log, nenhum channel — não há decisão de fluxo | sim | `01` → Channel de Log |
| 5 | Ausência de badge sem código novo — a view já trata papel nulo | sim | ADR-02 |

## Blockers

- Nenhum.

## Desvios do Plano

- **Nenhum teste existente mudou de oráculo**, ao contrário do que o `01` previu. A mudança ficou
  aditiva no model (default `null`), e quem passou a filtrar foi a view. A decisão da wiki ancestral
  — acesso não depende da organização aberta — continua intacta **e testada**, sem reescrita.
- **A premissa do `/app` sem organização foi retirada**, não respondida: o painel não tem esse
  estado. Ver a tabela acima.

## Notas de Implementação

- **A ordem do `PapeisSeeder` é o que separa um teste que mata o defeito de um que passa por sorte.**
  `admin_app` nasce **antes** de `panel_user`, logo tem `roles.id` menor, e o `first()` do código
  antigo entregava justamente ele. Um cenário que olhasse só a organização onde a pessoa é
  `admin_app` ficaria **verde com o defeito intacto**. Por isso CT-01 é bidirecional, e por isso tem
  uma terceira organização: com duas, uma resolução "a corrente ou a outra" também passaria.
- **Mutação manual valeu mais que a suíte verde.** Tirar `$contextoDoBadge` da view derruba CT-01,
  CT-02 e CT-03 — é essa a prova de que os cenários afirmam o comportamento novo, e não o antigo
  por outro caminho.
- **O `04` foi derivado cego à implementação**, que rodou em paralelo. O sub-agente registrou os
  arquivos modificados como anomalia ("não fui eu") — eram desta implementação. A cegueira é o
  desenho da esteira, não um acidente.
- **Estado honesto do gate de falsificabilidade** (do `04`): 1 mutante sem matador (M7 — organização
  vinda da sessão, que a visita direta não falsifica) e 3 com matador parcial. Ficam declarados, não
  escondidos.

## Retrospectiva

- **Funcionou**: investigar até a causa antes de escrever o plano. O `00` já nasceu com o
  `arquivo:linha` do defeito e com a decisão anterior conflitante localizada — que é o que fez a
  ADR-01 ser sobre separar duas perguntas, e não sobre "corrigir um bug".
- **Funcionou**: a derivação cega. O achado da ordem do seeder (`admin_app` com id menor) não estava
  no meu plano e é exatamente o que separa um teste útil de um teste decorativo.
- **Faltou no plano**: prever que o default `null` tornaria a mudança aditiva. Eu anunciei que um
  teste mudaria de oráculo e ele não mudou — a previsão era pessimista, mas errada, e uma previsão
  errada no `## Impacto` custa revisão de quem for ler.
- **Faltou no plano**: conferir se o `/app` tem tela sem organização antes de escrever premissa
  sobre ela. Dois greps no `AppPanelProvider` teriam evitado.
