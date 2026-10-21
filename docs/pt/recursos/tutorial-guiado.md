---
title: "Tutorial guiado"
description: "Jornadas de primeiros passos, com tours que apontam cada tela, nos três painéis: semeadas por um seeder pai com um seeder filho por feature, e ligadas por um interruptor na tela de configurações…"
sidebar:
  order: 11
---
O tutorial guiado mostra a quem chega **onde fica cada coisa**: uma jornada "Primeiros passos" por
painel, com um passo por feature do kit, e cada passo com um **tour** que destaca a tela, a tabela,
a busca ou o botão de que está falando (`wallacemartinss/filament-onboarding`). A pessoa acompanha o
progresso no **checklist flutuante**, presente em toda tela, e na página **Primeiros passos** do menu.

Alguns passos **concluem sozinhos**: o de perfil conclui quando a autenticação em dois fatores está
confirmada, e o de convites conclui depois do primeiro convite enviado, mesmo que ele tenha sido
enviado antes de a jornada existir.

## Duas coisas separadas: semear e ligar

| | O que faz | Como |
|---|---|---|
| **Semear** | cria as jornadas, os passos, os tours e as condições no banco | `php artisan db:seed --class=OnboardingSeeder`, ou `kit:install --onboarding` |
| **Ligar** | mostra o checklist, os tours, a página "Primeiros passos" e a autoria | **Usar o tutorial guiado**, na aba **Aplicação** de `/admin/configuracoes-da-aplicacao` (seção *Tutorial guiado*) |

Semear **não** liga, e as duas coisas podem ser feitas em qualquer ordem. Numa instalação nova o
interruptor nasce **desligado** (`KIT_ONBOARDING=false`). Desligado:

- os menus **Onboarding** e **Condições** somem do `/admin`, e as URLs deles respondem **404**,
  mesmo para quem tem a permissão;
- o checklist, os tours e a página "Primeiros passos" não aparecem em painel nenhum;
- o widget "Jornadas de onboarding concluídas" some do dashboard do `/admin`;
- quem estava com uma tela aberta e clica no checklist recebe a ação recusada, e nada é gravado.

Nada é apagado ao desligar. Religado, tudo volta com o progresso de cada pessoa.

## Como iniciar as jornadas

**Na instalação.** O `kit:install` pergunta *Popular os tutoriais guiados?*, com **não** como
resposta padrão. Sem terminal (CI, Docker), passe a opção:

```bash
php artisan kit:install --onboarding
```

**Depois, a qualquer momento:**

```bash
php artisan db:seed --class=OnboardingSeeder
```

Depois de semear, ligue o interruptor em `/admin/configuracoes-da-aplicacao`. O seeder não entra no
`php artisan db:seed` padrão: semear o tutorial é escolha de quem instala.

**Rodar de novo é seguro, e preserva o que foi editado.** O seeder identifica jornada, passo e
condição pela chave e cria **só o que falta**. Ele nunca sobrescreve o que já existe, nem o texto que
alguém editou em `/admin/onboarding-flows`. O conteúdo semeado é o ponto de partida; depois disso a
autoria é de quem administra a instalação.

## O que cada seeder popula

O seeder pai (`database/seeders/OnboardingSeeder.php`) chama um **seeder filho por feature**, em
`database/seeders/Onboarding/`. Cada filho acrescenta o seu passo à jornada **Primeiros passos** do
painel em que a feature vive.

| Seeder filho | Painel | Tela | Conclui | Some quando |
|---|---|---|---|---|
| `PerfilEDoisFatoresSeeder` | admin, app, infra | Meu perfil | sozinho, com o 2FA confirmado | — |
| `BuscaGlobalSeeder` | admin, app, infra | Dashboard (busca ⌘K / Ctrl+K) | ao terminar o tour | — |
| `HubDeNavegacaoSeeder` | admin, app | Hub de cartões | ao terminar o tour | o hub está desligado |
| `DashboardDinamicoSeeder` | admin, app, infra | Dashboard dinâmico | ao terminar o tour | o dashboard dinâmico está desligado no painel |
| `DockDeAtalhosSeeder` | admin, app, infra | Dock de atalhos | ao terminar o tour | o dock está fora do painel |
| `BloqueioDeTelaSeeder` | admin, app, infra | Bloqueio de tela | ao terminar o tour | o bloqueio de tela está desligado |
| `UsuariosSeeder` | admin | Usuários | ao terminar o tour | — |
| `ConvitesSeeder` | admin | Convites | sozinho, depois do primeiro convite | — |
| `PapeisEPermissoesSeeder` | admin | Papéis | ao terminar o tour | — |
| `ConfiguracoesDaAplicacaoSeeder` | admin | Configurações da aplicação | ao visitar a tela | — |
| `AgentesDeIaSeeder` | admin | Agentes de IA | ao terminar o tour | os agentes de IA estão desligados |
| `HubDeInfraestruturaSeeder` | infra | Central de infraestrutura | ao terminar o tour | — |
| `PulseSeeder` | infra | Pulse | ao terminar o tour | — |
| `BackupsSeeder` | infra | Backups | ao terminar o tour | — |
| `LogsDaAplicacaoSeeder` | infra | Logs | ao terminar o tour | — |
| `ExcecoesSeeder` | infra | Exceções | ao terminar o tour | — |
| `FilasSeeder` | infra | Monitor de filas | ao terminar o tour | — |
| `AuditoriaSeeder` | infra | Auditoria | ao terminar o tour | — |
| `ExecucoesDeIaSeeder` | infra | Execuções de IA | ao terminar o tour | — |
| `LixeiraSeeder` | infra | Lixeira | ao terminar o tour | — |
| `CentralDeComandosSeeder` | infra | Comandos e histórico | ao terminar o tour | a central de comandos está desligada |

Um passo de feature desligada **some do checklist e não conta no percentual** enquanto ela estiver
desligada, e volta quando ela é religada.

### Com multi-tenancy

Seis filhos ensinam telas que **só existem com o [modo multi-tenant](../multi-tenancy/)** ligado, e o
seeder pai os chama **só** quando `KIT_TENANCY=true`:

| Seeder filho | Painel | Tela | Conclui | Some quando |
|---|---|---|---|---|
| `OrganizacoesSeeder` | admin | Organizações | ao terminar o tour | — |
| `MembrosDaOrganizacaoSeeder` | app | Usuários da organização | ao terminar o tour | — |
| `ConvitesDaOrganizacaoSeeder` | app | Convites da organização | sozinho, depois do primeiro convite na organização | — |
| `ConvitesRecebidosSeeder` | app | Convites recebidos | ao terminar o tour | — |
| `TrocaDeOrganizacaoSeeder` | app | Seletor de organização | ao terminar o tour | — |
| `ProjetosSeeder` | app | Projetos (demonstração) | ao terminar o tour | o cenário de demonstração está desligado |

As jornadas são as mesmas em todas as organizações, mas **o progresso é por organização**: a mesma
pessoa faz os primeiros passos separadamente em cada uma. Ligou a tenancy depois de semear? O
`kit:tenancy` recria o banco; rode o `OnboardingSeeder` de novo em seguida.

### Fora do tutorial

As telas de login, cadastro, recuperação de senha e verificação de e-mail não têm tutorial: o
checklist só existe dentro de um painel. A página pública de boas-vindas (`/`) também não, porque
fica fora de painel. Os widgets soltos não ganham passo próprio: eles fazem parte do passo do
dashboard. A tela de boas-vindas do próprio pacote fica desligada. A lista completa das telas sem
tutorial, com o motivo de cada uma, está em `OnboardingSeeder::ISENTAS`.

## As condições

As condições são as perguntas que fazem um passo concluir ou aparecer sozinho. Há dois tipos:

- **No banco**, editáveis em **Onboarding → Condições**: `convidou_alguem` e
  `convidou_alguem_na_organizacao` (esta só com tenancy) contam os convites que a pessoa enviou.
- **No código**, em `App\Support\TutorialGuiado::condicoes()`: `tem_dois_fatores` e as que leem
  se uma feature está ligada (`dock_no_painel`, `dashboard_dinamico_no_painel`,
  `bloqueio_de_tela_ligado`, `hub_ligado`, `agentes_de_ia_ligados`, `central_de_comandos_ligada`,
  `demo_ligada`). Elas aparecem no seletor do editor de passos.

## Quem vê

Quem tem a permissão da página **Primeiros passos** (`View:OnboardingProgress`, na tela de papéis),
com o interruptor ligado. Sem ela, a pessoa não vê o checklist nem o item de menu. A autoria (jornadas
e condições) segue as permissões `ViewAny:OnboardingFlow` e `ViewAny:OnboardingCondition`.

## Feature nova

Toda tela de painel nova precisa de um seeder filho ou de uma isenção declarada. O
`tests/Kit/InventarioDeOnboardingTest.php` fica vermelho até ela entrar em `OnboardingSeeder::TELAS`
ou em `OnboardingSeeder::ISENTAS`. O passo a passo para agentes e pessoas está na rule
`.ai/rules/onboarding.md`.
