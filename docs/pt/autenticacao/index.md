---
title: "Autenticação"
description: "Como alguém entra — e como alguém deixa de entrar: convite, registro aberto com aprovação, login social nos quatro provedores, a página única de login…"
sidebar:
  label: "Visão geral"
  order: 0
---
Como alguém entra — e como alguém deixa de entrar: convite, registro aberto com aprovação, login
social nos quatro provedores, a página única de login opcional (`/login`), proteção anti-robô, os
três estados da conta e o que a rota pública `/` mostra (e o que ela deliberadamente não mostra).

A ordem exata das checagens de acesso a um painel — indisponibilidade, aprovação pendente,
`master_global`, papel — está no diagrama [Regra de acesso ao painel](../referencia/arquitetura-em-diagramas/)
(DG-03), na página de arquitetura em diagramas.

## Sequência: login por senha e 2FA

```mermaid
sequenceDiagram
%% DG-04
accTitle: Login por senha e segundo fator
accDescr: A checagem canAccessPanel() vem antes de qualquer desafio de 2FA, que só aparece dentro de um bloco condicional no próximo request ao painel.
  participant visitante as Visitante
  participant tela_login as Tela de login
  participant vendor_login as Login (Filament)
  participant authenticate as Authenticate
  participant authenticate_session as AuthenticateSession
  participant must_two_factor as MustTwoFactor
  participant locker as Locker
  participant exigir_email as ExigirEmailVerificado (/app, KIT_REGISTRO_VERIFICAR_EMAIL)
  participant resposta_login as RespostaDeLogin
  visitante->>tela_login: credenciais
  tela_login->>vendor_login: authenticate()
  note over vendor_login: rate limit 5 tentativas
  alt conta indisponível (senha certa)
    vendor_login-->>visitante: recusa (403) via ContaIndisponivelController
  else canAccessPanel() permite
    vendor_login->>resposta_login: attemptWhen, sessão regenerada
    resposta_login-->>visitante: decide o destino
    visitante->>authenticate: request seguinte ao painel
    authenticate->>authenticate_session: sessão validada
    authenticate_session->>must_two_factor: checagem de 2FA
    alt 2FA ligado na conta
      must_two_factor-->>visitante: desafio 2FA
    end
    must_two_factor->>locker: checa bloqueio (ociosidade/manual)
    locker->>exigir_email: e-mail verificado?
  end
```

A tela de login do kit intercepta a falha do Filament só para explicar a conta indisponível
(`app/Filament/Pages/Auth/TelaLogin.php:authenticate:196`); o rate limit de 5 tentativas é do
próprio Filament (`vendor/filament/filament/src/Auth/Pages/Login.php:rateLimit(5):70`), que também
decide com `canAccessPanel()` (`vendor/filament/filament/src/Auth/Pages/Login.php:canAccessPanel:172`).
O destino do login é sempre `app(LoginResponse::class)`, vinculado a `RespostaDeLogin`
(`app/Providers/KitServiceProvider.php:LoginResponse:863`). A pilha do próximo request (ordem real,
conferida com `php artisan route:list --json`) é `Authenticate` → `AuthenticateSession` →
`MustTwoFactor` → `Locker` → `ExigirEmailVerificado` (só obrigatório no `/app`, com a chave ligada).

## Sessão autenticada

```mermaid
stateDiagram-v2
%% DG-10
accTitle: Sessão autenticada
accDescr: Depois do login, a sessão alterna entre autenticada, aguardando 2FA, bloqueada e encerrada, conforme a ociosidade, o bloqueio manual e as tentativas de senha.
  state "Autenticada" as autenticada
  state "Aguardando 2FA" as aguardando_2fa
  state "Bloqueada" as bloqueada
  state "Encerrada" as encerrada
  [*] --> autenticada
  autenticada --> aguardando_2fa : ainda não confirmou o 2FA
  aguardando_2fa --> autenticada : confirma o 2FA
  autenticada --> bloqueada : ociosidade 1800s ou bloqueio manual
  bloqueada --> autenticada : senha correta
  bloqueada --> encerrada : 5 tentativas erradas (force logout)
  autenticada --> encerrada : logout manual
```

O desafio de 2FA só existe para quem o confirmou antes — é o `MustTwoFactor` do próprio diagrama de
cima. O bloqueio por ociosidade usa `config('lockscreen.idle_timeout')` = 1800
(`config/lockscreen.php:idle_timeout:16`); os três painéis compartilham o mesmo limite de
tentativas com força de logout (`app/Providers/Filament/AdminPanelProvider.php:enableRateLimit:267`,
`app/Providers/Filament/AppPanelProvider.php:enableRateLimit:381`,
`app/Providers/Filament/InfraPanelProvider.php:enableRateLimit:290`).
