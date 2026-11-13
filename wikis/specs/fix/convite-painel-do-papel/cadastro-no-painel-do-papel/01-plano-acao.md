# Plano de Ação — fix: cadastro por convite cai no painel do papel

> Requisito: `00-requisito.md` (issue #191)

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/fix/destino-apos-cadastro/destino-apos-cadastro/` (ADR-04 — sem
  tela de escolha no modo desligado; CT-67 a CT-75)
- **Motivo**: o ramo desligado de `RespostaDeCadastro` só descartava a pretendida inacessível e caía
  no painel corrente. O link do convite é sempre o cadastro do `/app`, então convite de papel de outro
  painel terminava em 403
- **Toca infra compartilhada?**: **sim** — a resposta de cadastro vale para convite e registro aberto.
  Regressão obrigatória: `tests/Kit/DestinoAposCadastroTest.php`, `tests/Tenancy/DestinoAposCadastroTenancyTest.php`
  e os testes de convite/registro

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | convite de outro painel cai no painel do papel | 1, 2 | |
| RQ-02 | corrente acessível mantém o destino atual | 2 | ramo inalterado |
| RQ-03 | um painel acessível → URL dele | 1, 2 | |
| RQ-04 | mais de um → default dentre os acessíveis | 1 | P-02 |
| RQ-05 | nenhum → login | 2 | P-01, P-05 |
| RQ-06 | pretendida e corrente inacessíveis → painel acessível | 2, 3 | P-04 (organização) |
| RQ-07 | `panel_user` com organização → `/app/{organizacao}` | 2 | |
| RQ-08 | nota de deploy | 4 | |
| P-03 | pretendida acessível vence | 2 | |
| P-04 | organização da pretendida, só no cadastro | 3 | *(alterado em 2026-11-13: QA-01 — login fora)* |
| P-05 | notificação no ramo sem painel | 2 | *(alterado em 2026-11-13: QA-05)* |

## Objetivo

Com o login unificado desligado, a conta nova nunca termina o cadastro num painel que ela não
acessa. Se o painel corrente é acessível (ou sobrou uma pretendida acessível), nada muda; senão a
conta vai ao painel que ela acessa; sem nenhum, a sessão fecha e ela volta ao login.

## Decisões de Desenho

- Zero painéis: encerrar a sessão antes de mandar ao login (P-01) — sem isso a tela de login do
  Filament devolveria a sessão viva ao painel corrente (403)
- O desempate entre painéis mora em `DestinoAposLogin`, ao lado de `paineisDe()`, e não na resposta:
  é a mesma classe que decide destino no login
- Não mudar o link do convite (fora de escopo declarado)

## Channel de log

`autenticacao` (já existente). Novos logs em `RespostaDeCadastro@toResponse`: `info` quando o
fallback escolhe um painel; `warning` quando não há painel e a sessão é encerrada. Contexto:
`user_id`, `corrente`, `painel`/`motivo`.

## Autorização, Rotas, Variáveis de Ambiente, Eventos, Jobs

Sem mudança. A pergunta de acesso continua `User::canAccessPanel()`.

## Superfície de UI

Sem superfície de UI nova — só o destino do redirect depois do cadastro.

## Modelo de Execução

Um request, sem trabalho adiado.

## Impacto em Features Existentes

Só o cadastro: o ramo desligado de `RespostaDeCadastro` quando o painel corrente é inacessível e não
há pretendida acessível (antes, 403), e o descarte da pretendida de organização sem vínculo
(`descartarPretendidaInacessivel()`, só chamado pelo cadastro). O login (`urlPara()`) não muda —
`painelDe()` só confere organização quando recebe a conta. *(alterado em 2026-11-13: QA-01)*

## Rollback

Reverter o commit; não há migration nem config.

## Dependências

Nenhuma nova.

## Riscos

- `canAccessPanel()` loga `warning` por painel negado: o cadastro de convite `infra` gera avisos
  `sem_papel_do_painel` — o mesmo ruído do Panel Switch (ADR-07 do login unificado)

## Estrutura de Implementação

### 1. `DestinoAposLogin::painelSemEscolha(User): ?Panel`

> Skills: `laravel-best-practices`

- **Path**: `app/Support/DestinoAposLogin.php`
- Reusa `paineisDe()`; devolve o default do Filament se estiver entre os acessíveis, senão o
  primeiro, senão `null`. Sem log (quem decide loga)
- **Atende**: RQ-01, RQ-03, RQ-04

### 2. Ramo desligado de `RespostaDeCadastro::toResponse()`

> Skills: `laravel-best-practices`, `filament-development`

- **Path**: `app/Http/Responses/RespostaDeCadastro.php`
- Depois de `descartarPretendidaInacessivel()`: pretendida restante ou corrente acessível →
  `parent::toResponse()`; senão `painelSemEscolha()` → `redirect()->to(Paineis::url($painel))`;
  `null` → `logout()`, `invalidate()`, `regenerateToken()`, notificação de aviso (P-05) e redirect ao
  `getLoginUrl()` do corrente
- **Atende**: RQ-01, RQ-02, RQ-03, RQ-05, RQ-06, RQ-07, P-01, P-03, P-05
- **Logs**: `info` `[RespostaDeCadastro@toResponse] Painel corrente inacessivel — destino no painel acessivel`
  com `user_id`, `corrente`, `painel`; `warning` `[RespostaDeCadastro@toResponse] Cadastro sem nenhum
  painel acessivel — sessao encerrada` com `user_id`, `corrente`, `motivo`

### 3. Organização da pretendida no cadastro

> Skills: `laravel-best-practices`

- **Path**: `app/Support/DestinoAposLogin.php` — `painelDe(..., ?User $conferirOrganizacaoDe)` e
  `organizacaoDaUrlAcessivel()`: só `descartarPretendidaInacessivel()` passa a conta; casa a rota e,
  com `{tenant}`, exige a organização existente e `canAccessTenant()`; rota sem `{tenant}` aceita;
  URL sem rota recusada
- **Atende**: RQ-06, P-04 *(passo novo em 2026-11-13: achados do step 9 e QA-01)*

### 4. Docs

- **Path**: `docs/{pt,en}/autenticacao/login-unificado.md` (destino depois do cadastro desligado) e
  `docs/{pt,en}/recursos/configuracao-global-filament.md` (nota `DirectorySlash` / `/public/app`)
- **Atende**: RQ-08

### 5. Testes

- CT-01 a CT-16 do `04`, pelo `fw-executor-ct`
- **Atende**: RQ-01, RQ-02, RQ-03, RQ-04, RQ-05, RQ-06, RQ-07, RQ-08, P-01, P-02, P-03, P-04, P-05

## Release v0.52.1 (fora das RQ da issue)

- CHANGELOG, `config/kit.php`, roteiro `wikis/atualizacoes/v0.52.1.md`, cópia em
  `wikis/agentes-e-skills.md`, bumps dos PRs do dependabot e correção do larastan

## Verificação

`vendor/bin/pint --dirty`, `vendor/bin/phpstan analyse`, os dois arquivos de teste novos e a
regressão listada em *Natureza da Wiki*.

## Commit

`:bug: fix(convite): cadastro cai no painel do papel com o login unificado desligado (#191)`
