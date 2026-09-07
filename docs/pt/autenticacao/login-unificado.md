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

ou, em `/admin/configuracoes-da-aplicacao` → aba **Login** → "Unificar o login em /login". O toggle vale
na hora, sem deploy: a chave é lida a cada request, não no boot. Só `true` e `1` ligam — qualquer
outro valor no `.env` mantém desligado.

**Depois de instalado, quem manda é a tela.** Como toda chave de login do kit, o `.env` só dá o
valor **inicial**: a migration de Settings semeia `login_unificado` com o que estava no `.env` no
momento da instalação, e daí em diante o valor do banco sobrepõe o `.env` a cada boot
(`ConfiguracoesDoKit::aplicarNaConfig()`). Trocar `KIT_LOGIN_UNIFICADO` numa instalação existente
**não** liga nem desliga nada — use o toggle (ou `kit:install --force`, que recria o banco). Medido
nas instalações de teste da feature.

**Projeto criado antes da v0.31.0**: `php artisan kit:update` traz a migration
`database/settings/*_add_login_unificado_to_kit_settings.php` junto com o código. Rode
`php artisan migrate` antes de abrir a tela de configurações — sem a linha no banco, ela quebra.
O valor semeado é o do `.env` naquele momento (`false` se a chave não existir).

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

## Painel novo depois da instalação

A escolha lista **um cartão por painel registrado** — os mesmos que o Panel Switch mostra dentro do
Filament, com o mesmo rótulo e o mesmo ícone. Se a sua aplicação registrar um `PanelProvider` novo
depois da instalação, ele aparece sozinho, sem alterar arquivo nenhum do kit; o rótulo padrão é o
id capitalizado e o ícone é o genérico, exatamente como o Panel Switch faria. Para dar nome e
ícone próprios, acrescente a linha nos mapas de `App\Support\Paineis` — os mesmos que alimentam o
Panel Switch, para os dois nunca divergirem.

Só entram os painéis que a pessoa acessa: o filtro é o mesmo `canAccessPanel()` de sempre.

## As outras telas sem prefixo de painel

Com a chave ligada, além de `/login`:

| Tela | Endereço | A rota do painel |
|---|---|---|
| Cadastro (convite e cadastro aberto) | `/cadastro` | `/{painel}/register` redireciona para lá, com a query intacta |
| Esqueci minha senha | `/esqueci-minha-senha` | `/{painel}/password-reset/request` redireciona para lá |

O `?token=` do convite e o `?org=` da organização atravessam o redirect — um link antigo de convite
continua funcionando. Desligue a chave e as duas voltam para as rotas dos painéis, sem nada a
migrar.

**Cadastro com multi-organização**: o link "Cadastre-se" da tela de login só aparece quando existe
uma organização de destino, e carrega o `?org=`. Divulgue `/login?org={slug}` (ou
`/cadastro?org={slug}`) e ligue "Aceita cadastro público" na tela da organização — sem isso, o
cadastro recusa, e o link some em vez de levar à recusa. Ver [Registro aberto](registro-aberto.md).

## O que continua por painel

- **Redefinição de senha (o link do e-mail) e verificação de e-mail**: continuam nos painéis. As
  duas chegam por link assinado ou já autenticadas, então não há o que unificar. O link do e-mail
  abre no painel **da própria pessoa** — quem só acessa o `/admin` recebe um link `/admin/...` e
  entra por ele.
- **2FA (Breezy) e lock screen**: acontecem **dentro** do painel escolhido, como hoje. A tela de
  escolha aparece antes do desafio de segundo fator — ela só lista os painéis; ao entrar em um, o
  desafio é exigido normalmente.
- **Login social**: com a chave ligada, o botão na página única não carrega painel de origem (o
  provedor precisa estar habilitado, em qualquer painel), e o destino da volta segue a mesma regra
  da tabela acima **restrita aos painéis em que o provedor está autorizado** — GitHub liberado só
  no `/infra` não entrega ninguém no `/admin`; sem painel autorizado acessível, a sessão é
  encerrada. Com a chave desligada, tudo como em [Login social](login-social.md).

## O log de acessos registra o painel em que a pessoa entrou

O `authentication_log` carimba o painel de cada acesso (é o que alimenta "acessos por painel" nos
insights das organizações e o stat de logins do dia). A página única roda no contexto do painel
default só para ter tema e layout — isso **não** vai para o log: o login que começa em `/login`
nasce sem painel e recebe o painel de **entrada** — o único acessível, o da URL pretendida, ou o
cartão clicado na escolha. Enquanto a pessoa está na tela de escolha, o acesso fica sem painel; se
ela fechar a aba sem escolher, fica assim (é um login que não entrou em painel nenhum).

## Atenção: SSO externo ainda não está pré-configurado

A página única cobre o login por senha e o login social do kit (Google, GitHub, LinkedIn, X). Um
**SSO externo** — SAML, OpenID Connect corporativo, Keycloak, Entra ID — que autentique a pessoa
fora do Filament e a devolva já com sessão **não passa** por `/login` nem pela regra de destino:
ela cairia no painel default e, sem acesso a ele, veria 403. Se você integrar um SSO por conta
própria, faça o callback dele redirecionar para o mesmo decisor da página única (a classe de
destino após login, documentada em `wikis/specs/feat/login-unificado/`) em vez de para uma URL
fixa de painel. SSOs externos pré-configurados são um item planejado do kit.

## Se quiser voltar

Desligue o toggle. Nada foi migrado nem gravado além da propriedade no Settings; as rotas `/login`,
`/login/painel`, `/cadastro` e `/esqueci-minha-senha` continuam existindo — desligadas, cada uma
redireciona para a rota equivalente do painel default.

Detalhes e decisões: `wikis/specs/feat/login-unificado/` e
`wikis/specs/feat/login-unificado-telas-externas/` no repositório.
