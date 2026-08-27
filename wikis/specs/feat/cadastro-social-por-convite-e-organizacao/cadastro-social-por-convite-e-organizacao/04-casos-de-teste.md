# Casos de Teste — Cadastro pelo provedor social

> Derivados do `00-requisito.md`. Arquivos: `tests/Kit/CadastroSocialPorConviteTest.php` (sem tenancy)
> e `tests/Tenancy/CadastroSocialPorOrganizacaoTenancyTest.php` (tenancy ligada).
>
> **Desvio declarado**: escritos inline pelo mesmo agente do plano, durante a rodada de validação
> real. Mitigação: cada caso cita a RQ; dois casos existem para refutar a implementação plausível
> (CT-C05 e CT-C07).

## Perfil de risco
Autorização (em qual organização e com qual papel a conta nasce) e fronteira (o que vem do provedor
× o que veio do convite). Sem volume.

## Mapa de regras → casos

| Regra | RQ | Casos |
|---|---|---|
| R1 — os botões sociais aparecem no `/app/register` (registro aberto), com `org`/`token` no link | RQ-01 | CT-C01, CT-C02 |
| R2 — `?org=` cria a conta na organização, com `panel_user` nela | RQ-03 | CT-C03 |
| R3 — `?token=` válido + e-mail igual cria a conta com organização e papel do convite, e consome o convite | RQ-04, RQ-02 | CT-C04 (Kit e Tenancy) |
| R4 — e-mail do provedor ≠ e-mail do convite → recusa, convite intacto | RQ-05 | CT-C05 |
| R5 — organização inexistente/fechada → recusa, nada criado | RQ-05 | CT-C06 |
| R6 — o contexto morre no callback: um segundo callback sem novo redirect não herda `org` | RQ-05 (segurança) | CT-C07 |
| R7 — conta existente + convite válido → ganha organização/papel, convite consumido | RQ-04 (premissa) | CT-C08 |
| R8 — a tela de bloqueio continua sem `org`/`token` no link | regressão | coberto por `BloqueioDeSessaoTest` (href sem query) |

## Casos

### CT-C01 (Kit) — botões no registro aberto
- Dado registro aberto ligado e Google ligado · Quando `GET /app/register` · Então 200 e "Entrar com Google" com `href` para `auth/google/redirect`.

### CT-C02 (Tenancy) — link carrega `org` e `token`
- Dado `acme` com registro habilitado · Quando `GET /app/register?org=acme` · Então o `href` do botão contém `org=acme`. E com `?token=T` (convite válido) contém `token=T`.

### CT-C03 (Tenancy) — cria na organização
- Dado `acme` aberta, papéis semeados · Quando `GET /auth/google/redirect?org=acme` (grava sessão) e depois callback com e-mail novo · Então conta criada, `tenants` contém `acme`, linha em `model_has_roles` com `team_id = acme` e papel `panel_user`, sessão autenticada, redirect para o perfil da `acme`.

### CT-C04 — cria a partir do convite
- Dado convite para `nova@example.com` (papel `panel_user`; na Tenancy, `tenant = globex` fechada ao registro aberto) · Quando redirect com `?token=` e callback com esse e-mail · Então conta criada, `origem = google`, e-mail verificado, `aceito_em` preenchido, `token_lembrete` nulo; na Tenancy, `tenants` contém `globex` e o papel está no contexto `globex` — **mesmo com o registro aberto fechado** (a porta é o convite).

### CT-C05 — convite para outro e-mail
- Dado convite para `dona@example.com` · Quando callback com `outra@example.com` e o token na sessão · Então redirect ao login com recusa, `guest`, nenhum usuário criado, convite com `aceito_em` nulo.

### CT-C06 (Tenancy) — organização inválida
- Dado `?org=inexistente` (e, em dataset, `?org=globex` fechada) · Quando callback com e-mail novo · Então recusa, `guest`, nada criado.

### CT-C07 (Tenancy) — o contexto não sobrevive ao callback
- Dado redirect com `?org=acme` e um callback que **recusa** (e-mail não verificado) · Quando um segundo callback (agora verificado) chega sem novo redirect · Então, com tenancy, recusa por `sem_organizacao` — o `org` foi consumido.

### CT-C08 (Tenancy) — conta existente aceita convite pelo provedor
- Dado conta `ja.tem@example.com` sem organização e convite para ela em `acme` · Quando redirect com `?token=` e callback · Então `tenants` contém `acme`, convite consumido, sessão da conta.

## Sem CT-B
Botão é `<a href>`; nada só o navegador prova.

## Regressão
`LoginSocialGoogleTest`, `LoginSocialProvedoresTest`, `VinculoDeProvedorSocialTest`, `BloqueioDeSessaoTest`, `ConviteTest`, `RegistroAbertoTest` (Kit) e `LoginSocialGoogleTenancyTest` (Tenancy).
