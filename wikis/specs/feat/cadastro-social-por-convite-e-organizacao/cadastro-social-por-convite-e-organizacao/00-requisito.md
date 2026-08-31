# Requisito — Cadastro pelo provedor social: pela tela de registro, por organização e por convite

## Fonte

- **Origem**: mensagens do solicitante no chat, durante a validação real dos provedores (2026-08-26)
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - na tela de "/app/register" ter a opção de usar o login social porque ai nem precisaria cadsatrar senha, e caso tenha vindo de algum "convite", precisa registrar também as informações de tenant (se tiver ativo) e as demais validações

E, antes, a preocupação que motivou (mesma conversa):

> precisamo fazer esse mesmo teste do login social quando o tenancy estiver ativo. e isso é uma preocupação, pois como saberiamos qual tenancy ele tem permissão de entrar?

> - vamos replicar o mesmo teste que voce criou na pasta, mas agora para o cenario do tenancy ligado.
> - ja garantimos que o login com o google e o github estão funcionando, vamos garantir agora no tenant e com o '/register' ligado para testar todos os cenarios!

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A tela `/app/register` oferece os botões de login social (os mesmos do login), quando o registro aberto está ligado | "na tela de "/app/register" ter a opção de usar o login social" | funcional |
| RQ-02 | Quem se cadastra pelo provedor não precisa de senha: a conta nasce sem senha conhecida, com o e-mail verificado pelo provedor | "porque ai nem precisaria cadsatrar senha" | funcional |
| RQ-03 | Com a multi-organização ligada, o cadastro pelo provedor a partir de `/app/register?org={slug}` cria a conta **naquela organização**, com o papel do registro aberto nela | "como saberiamos qual tenancy ele tem permissão de entrar?" / "registrar também as informações de tenant (se tiver ativo)" | autorização |
| RQ-04 | A partir do link de um **convite** (`/app/register?token=…`), entrar pelo provedor aceita o convite: a conta nasce (ou a existente ganha) a organização e o papel do convite, e o convite é consumido | "caso tenha vindo de algum "convite", precisa registrar também as informações de tenant (se tiver ativo)" | autorização |
| RQ-05 | As validações do formulário valem no caminho social: organização inexistente/fechada recusa; convite inválido/expirado recusa; e-mail do provedor diferente do e-mail convidado recusa | "e as demais validações" | autorização |
| RQ-06 | Tudo isso é medido numa instalação real com tenancy ligada e `/register` ligado, com Google e GitHub | "vamos garantir agora no tenant e com o '/register' ligado para testar todos os cenarios!" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-04** — o convite de quem **já tem conta**: hoje o formulário aceita o convite para a conta
  existente (`Convite::aceitarComoUsuarioExistente`). Pelo provedor, fazer o mesmo?
  - **Assumido**: sim — se o e-mail verificado do provedor é o do convite, a conta existente ganha
    organização e papel, e entra. É a mesma prova do formulário (token do e-mail + dono do e-mail).
  - **Se negado**: o ramo "conta existe + token" só entra, sem aceitar; o convite continua pendente
    para a tela. Um caso muda.
  - **NEGADO em 2026-08-31** pela auditoria de segurança com o Filament Blueprint (F-03 e F-04, wiki
    `wikis/specs/feat/travas-de-escalada-de-papeis/`). Dois defeitos na mesma linha: o `?token=` entra
    pela rota GET pública do `redirecionar()`, sem CSRF, e com SSO silencioso do provedor o convite
    era aceito **sem clique da vítima** — o `state` do Socialite protege o callback, não o início do
    fluxo; e o aceite rodava **antes** de `redirecionarSeIndisponivel()`, então conta desativada ou
    excluída queimava o convite sem entrar. A premissa "é a mesma prova do formulário" estava errada:
    no formulário há um POST autenticado pela própria pessoa; aqui não havia clique nenhum.
    O ramo de conta existente passou a só **registrar** que há convite pendente
    (`LoginSocialController::avisarConvitePendente()`), e o aceite por conta existente é na tela
    autenticada `ConvitesRecebidos`. **RQ-04 continua valendo para conta NOVA**, que é o caminho de
    `criarContaPorConvite()` e não mudou.
- **RQ-03** — sem `?org=` na multi-organização: o formulário recusa (`RegistroPorConvite::mount()`).
  - **Assumido**: o provedor recusa igual (já é o comportamento desde `8c92658`) — a mensagem é a
    da conta inexistente.
- **Como o `org`/`token` viaja pelo OAuth**: o `state` do Socialite é dele (anti-CSRF).
  - **Assumido**: na **sessão** (`login_social.contexto`), gravado no `redirect` e consumido (`pull`)
    no `callback`, mesmo em recusa. Não vai na URL de volta nem no `state`.
  - **Se negado**: `state` customizado exigiria sobrescrever o driver — muito mais código para o
    mesmo efeito.

## Fora de Escopo (declarado)

- Botões sociais na tela de **aceite de convite de quem já tem conta** pela senha (`RegistroPorConvite` com token e conta existente já mostra "Entrar e aceitar"); os botões entram na mesma tela porque o hook é da página de registro — e funcionam para os dois casos.
- Cadastro pelo provedor em painel que não é o `/app` (o registro só existe no `/app`).
- Escolher a organização **depois** de voltar do provedor (uma tela intermediária). O `?org=` decide antes; sem ele, recusa.
