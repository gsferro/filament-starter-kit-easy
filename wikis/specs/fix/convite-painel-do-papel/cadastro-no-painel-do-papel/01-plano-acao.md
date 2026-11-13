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

| RQ / P | Passo(s) | Observação |
|---|---|---|
| RQ-01 | 1, 2 | |
| RQ-02 | 2 | ramo inalterado quando o corrente é acessível |
| RQ-03 | 1, 2 | `painelSemEscolha()` com um painel |
| RQ-04 | 1 | default acessível, senão o primeiro na ordem (P-02) |
| RQ-05 | 2 | logout + login do painel corrente (P-01) |
| RQ-06 | 2 | descarte antes da decisão |
| RQ-07 | 2 | `Paineis::url()` do `app` com tenancy |
| RQ-08 | 3 | nota nos docs pt/en |
| P-03 | 2 | pretendida acessível mantém `parent::toResponse()` |

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

Só o ramo desligado de `RespostaDeCadastro`, e só quando o painel corrente é inacessível e não há
pretendida acessível — caminho que antes terminava sempre em 403.

## Rollback

Reverter o commit; não há migration nem config.

## Dependências

Nenhuma nova.

## Riscos

- `canAccessPanel()` loga `warning` por painel negado: o cadastro de convite `infra` gera avisos
  `sem_papel_do_painel` — o mesmo ruído do Panel Switch (ADR-07 do login unificado)

## Passos

### 1. `DestinoAposLogin::painelSemEscolha(User): ?Panel`

`app/Support/DestinoAposLogin.php` — reusa `paineisDe()`; devolve o default do Filament se estiver
entre os acessíveis, senão o primeiro, senão `null`. Sem log (quem decide loga).

### 2. Ramo desligado de `RespostaDeCadastro::toResponse()`

`app/Http/Responses/RespostaDeCadastro.php` — depois de `descartarPretendidaInacessivel()`:
pretendida restante ou corrente acessível → `parent::toResponse()`; senão `painelSemEscolha()` →
`redirect()->to(Paineis::url($painel))` com log `info`; `null` → log `warning`, `logout()`,
`invalidate()`, `regenerateToken()`, redirect ao `getLoginUrl()` do corrente.

### 3. Docs

`docs/{pt,en}/autenticacao/login-unificado.md` (destino depois do cadastro desligado) e
`docs/{pt,en}/recursos/configuracao-global-filament.md` (nota `DirectorySlash` / `/public/app`).

### 4. Testes

CT-01 a CT-14 do `04`, pelo `fw-executor-ct`.

### 5. Release v0.52.1

CHANGELOG, `config/kit.php`, roteiro `wikis/atualizacoes/v0.52.1.md`, cópia em
`wikis/agentes-e-skills.md`, bumps dos PRs do dependabot e correção do larastan.

## Verificação

`vendor/bin/pint --dirty`, `vendor/bin/phpstan analyse`, os dois arquivos de teste novos e a
regressão listada em *Natureza da Wiki*.

## Commit

`:bug: fix(convite): cadastro cai no painel do papel com o login unificado desligado (#191)`
