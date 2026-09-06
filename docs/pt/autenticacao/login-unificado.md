---
title: Página única de login
parent: Autenticação
grand_parent: Português
nav_order: 7
---

# Página única de login (`/login`), opcional

Por default cada painel tem a própria porta — `/admin/login`, `/infra/login` e `/app/login` —, que é
o comportamento do Filament. Com a chave ligada, as três levam a **`/login`**, uma única tela de
login (mesma arte, mesmo anti-robô, mesmos botões de login social), e o kit decide para onde a
pessoa vai **depois** de entrar.

## Como ligar

Duas formas, mesma chave:

```dotenv
KIT_LOGIN_UNIFICADO=true
```

ou, em `/admin/configuracoes-do-kit` → aba **Login** → "Unificar o login em /login". O toggle vale
na hora, sem deploy: a chave é lida a cada request, não no boot. Só `true` e `1` ligam — qualquer
outro valor no `.env` mantém desligado.

## O que muda para quem entra

| Situação | O que acontece |
|---|---|
| Abre `/admin/login`, `/infra/login` ou `/app/login` | é levada a `/login` |
| Entra e tem acesso a **um** painel | entra direto nele — ou na URL que tinha pedido antes do login, se for daquele painel |
| Entra e tem acesso a **mais de um** | `/login/painel`: uma tela com um cartão por painel acessível (os mesmos cartões da tela de boas-vindas) |
| Entra e não tem acesso a **nenhum** | recusada, como hoje ("credenciais inválidas"; conta inativa ou excluída recebe a explicação de sempre) |
| Já está autenticada e abre `/login` | vai para onde iria depois de entrar |

A regra "pode entrar neste painel?" é a mesma de sempre — `User::canAccessPanel()` —, só que a
página única pergunta por **algum** painel em vez de pelo painel corrente. Um `admin` sem papel no
`/app` entra por `/login` e cai no `/admin`.

A URL pretendida (você abriu `/admin/users` sem sessão) só vence quando é de um painel que você
acessa e do próprio host. Fora disso é descartada, e vale a regra acima.

## O que continua por painel

- **Recuperação de senha, registro por convite, verificação de e-mail**: as telas continuam nos
  painéis. Elas redirecionam para o login do painel quando precisam, e ele leva a `/login`.
- **2FA (Breezy) e lock screen**: acontecem **dentro** do painel escolhido, como hoje. A tela de
  escolha aparece antes do desafio de segundo fator — ela só lista os painéis; ao entrar em um, o
  desafio é exigido normalmente.
- **Login social**: com a chave ligada, o botão na página única não carrega painel de origem (o
  provedor precisa estar habilitado, em qualquer painel), e o destino da volta segue a mesma regra
  da tabela acima. Com a chave desligada, tudo como em [Login social](login-social.md).

## Uma consequência para o log de acessos

A página única roda no contexto do painel default (`app`) — é o que dá tema, cores e o layout de
login a uma tela fora dos painéis. Por isso, com a chave ligada, o log de autenticação registra o
painel `app` para todo login por senha, e não o painel que a pessoa escolheu depois. O breakdown
por painel nos insights das organizações reflete isso.

## Se quiser voltar

Desligue a chave (ou o toggle). Nada foi migrado nem gravado além da propriedade no Settings; as
rotas `/login` e `/login/painel` continuam existindo — desligadas, `/login` só redireciona para o
login do painel default.

Detalhes e decisões: `wikis/specs/feat/login-unificado/` no repositório.
