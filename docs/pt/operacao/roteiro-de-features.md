---
title: "Roteiro de features"
parent: "Operação"
grand_parent: "Português"
nav_order: 2
---

# Roteiro de features

Tudo que o kit entrega, numerado, com **onde fica**, **quem alcança** e **como conferir**. Serve
para três coisas: saber o que já existe antes de reimplementar, ter um roteiro de teste manual
depois de um `kit:update`, e dar nome às features nos testes automatizados.

**A coluna "Teste"** diz o que já é verificado sozinho:

| Marca | Significa |
|---|---|
| 🟢 | coberto por teste automatizado — `composer test:kit` ou `composer test:browser` |
| 🔵 | coberto **em navegador real**, com JS executando |
| ⚪ | sem teste: depende de serviço externo (worker, cron, Docker, SMTP) ou de julgamento visual |

Onde a rota tem `{org}`, é o modo multi-tenant — sem ele, o caminho é `/app` direto.

## Acesso e autenticação

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-01 | Login nos três painéis | `/app/login`, `/admin/login`, `/infra/login` | qualquer um | as três telas abrem sem autenticação, no layout de duas colunas | 🔵 |
| F-02 | Recuperação de senha | `/{painel}/password-reset/request` | qualquer um | a tela abre; o e-mail depende de `MAIL_MAILER` | 🔵 |
| F-03 | Registro por convite | `/app/register?token=…` | quem tem token válido | sem token na query, a tela recusa e manda para o login (com `KIT_REGISTRO=false`, o default) | 🟢 |
| F-03a | Cadastro aberto (opt-in) | `/app/register` | qualquer um, com `KIT_REGISTRO=true` | o formulário aparece; quem se cadastra recebe **só** `panel_user` e leva 403 em `/admin` e `/infra` | 🟢 |
| F-03b | Aprovação de cadastro (opt-in) | tela de usuários → ação *Aprovar* | quem pode editar usuário | com `KIT_REGISTRO_APROVACAO_MANUAL=true` o cadastro nasce pendente e não entra em painel nenhum | 🟢 |
| F-03c | Validação de e-mail (opt-in) | `/app/email-verification/prompt` | autenticado, com a exigência ligada (na tela ou no `.env`) | a rota existe sempre — quem decide é um middleware do kit, por request; quem vem de convite nunca é barrado | 🟢 |
| F-04 | Autenticação em dois fatores | `/{painel}/two-factor-authentication` | autenticado | a tela abre e oferece o QR | 🔵 |
| F-05 | Passkeys | Meu perfil | autenticado | cadastro de chave, no perfil do Breezy | ⚪ |
| F-06 | Bloqueio de sessão | menu do usuário → *Bloquear sessão* | autenticado | trava sem deslogar; volta com a senha **ou** com o login social (os mesmos botões do login). Usa o layout do login, não a `SimplePage` | 🟢 |
| F-07 | Meu perfil, avatar e senha | `/{painel}/meu-perfil` | autenticado | edita nome, e-mail, senha e avatar | 🔵 |
| F-08 | Impersonate | `/admin/users` → ação na linha | `master_global` | entra como outro usuário e volta pela faixa no topo | ⚪ |

## Autorização

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-09 | **O papel decide o painel** (`roles.painel`) | `/admin` → Papéis | `admin`, `master_global` | crie um papel com painel `infra`: quem o tem entra no `/infra` e toma 403 no `/admin` | 🟢 |
| F-10 | 403 legível no painel errado | qualquer painel | — | a tela de 403 diz a conta, os papéis e oferece saída — e **não** revela permissão em produção | 🔵 |
| F-11 | `master_global` vence por `Gate::before` | os três | `master_global` | ele entra em tudo **sem** nenhuma permission no banco | 🟢 |
| F-12 | Papéis e permissões agrupados por painel | `/admin/shield/roles` | `admin` | a tela separa *Painel /admin*, */app* e */infra* | 🟢 |
| F-13 | `panel_user` **não** administra | `/app{/org}` | `panel_user` | ele usa o negócio e não vê Usuários nem Convites — a matriz dele é a do painel **menos** as telas de administração | 🟢 |
| F-14 | Sem papel, ninguém entra | os três | — | usuário autenticado sem papel toma 403 nos três. Nulo **não** é coringa | 🟢 |
| F-62 | **Toda tela do painel tem permissão própria, e ela é consultada** | `/admin/shield/roles` → abas *Pages* e *Widgets* | `admin` | desmarque `View:Pulse` no papel `infra`: a tela responde 403 e o item sai do menu. Vale para as 7 Pages e os 24 Widgets do kit **e** para as 7 telas que vêm de pacote no `/infra` — exceto as três da Central de comandos, ver F-67 | 🟢 |
| F-63 | **Toda action e todo link do kit tem permissão própria** | `/admin/shield/roles` → aba *Resources* e *Custom* | `admin` | desmarque `Reenviar:Convite`: o botão *Reenviar* sai da listagem de convites. As de RelationManager (vincular, desvincular, atribuir papéis) idem | 🟢 |
| F-64 | Action e link novos **não** nascem abertos por esquecimento | `tests/Kit/PermissoesDeAcoesTest.php` | — | acrescente uma `Action::make('x')` em `app/Filament/` e rode a suíte: o caso do inventário fica vermelho nomeando o arquivo | 🟢 |
| F-65 | **Boas-vindas na raiz**, com o que a instalação personalizou | `/` | anônimo | abra sem autenticar: os três cartões e a config aparecem, e nada de segredo — o caso planta sentinela em 8 valores e assere a ausência | 🟢 |
| F-66 | A raiz herda tema e cor do projeto | `/` | anônimo | troque `KIT_COR_PRIMARIA`, rode `npm run build` e recarregue: o botão muda de cor. Sem o `panel:app` da rota, sairia âmbar | 🟢 |
| F-67 | As três exceções estão **declaradas**, não escondidas | `/infra/command-center/commands` | `infra` | desmarque `View:Commands`: a tela **continua** abrindo. O pacote expõe um callback só para as três Pages dele, e a barreira delas é `command-center:access`. `tests/Kit/PermissoesDeTelasTest.php` tem o caso que assere essa lacuna e fica vermelho no dia em que ela fechar | 🔵 |
| F-68 | **[Hub de navegação em cartões](../recursos/hub-de-navegacao.md)** | `/infra/hub-de-infraestrutura` (sempre); `/admin/hub-de-administracao` e `/app{/org}/hub-do-negocio` com `KIT_HUB=true` | quem entra no painel | abra o hub do `/infra`: uma grade de cartões, um por destino que o seu papel alcança. Com `KIT_HUB=false` os hubs de `/admin` e `/app` somem do menu, da URL e da busca ⌘K | 🟢 |

## Convites

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-15 | Convite individual | `/admin/convites` · `/app/{org}/convites` | `admin`, `admin_app` | e-mail + papel + organização; o link vai por e-mail com token de uso único | 🟢 |
| F-16 | Convite para quem **já tem conta** | mesmo lugar | idem | vira *oferta de acesso*: a pessoa entra com a senha que já tem e é vinculada | 🟢 |
| F-17 | Caixa de convites recebidos | menu do usuário → *Convites recebidos* | qualquer autenticado | aceitar **ou recusar**; a recusa fica registrada | 🟢 |
| F-18 | Convite em massa | header da listagem | `admin`, `admin_app` | cole N endereços; um com problema **não** derruba os outros, e o resumo diz por quê | 🟢 |
| F-19 | Lembretes automáticos | `kit:convites-lembrar` (cron 08:00) | — | D+3 e D+5, com um **segundo link paralelo**; o original continua valendo | 🟢 |
| F-20 | Reenviar / revogar | ação na linha | `admin` | reenviar **mata** os links anteriores; revogar apaga e fica em `/infra/audits` | 🟢 |

## Multi-tenancy (opt-in)

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-21 | Ligar o modo | `php artisan kit:tenancy` | — | roda `migrate:fresh --seed`; **exige árvore git limpa** | ⚪ |
| F-22 | Painel por organização | `/app/{org}` | vinculados | o seletor lista só as organizações do usuário; a de outro dá 404 | 🟢 |
| F-23 | Cadastro de organizações | `/admin/organizacoes` | `admin` | create, **view** e edit em tela cheia | 🔵 |
| F-24 | Vínculo de usuários | organização → *Usuários vinculados* | `admin` | vincular, desvincular e dar papel **naquela** organização | 🟢 |
| F-25 | `admin_app` | `/app/{org}` | o papel | administra **uma** organização: usuários e convites recortados. Não entra no `/admin` | 🟢 |
| F-26 | Escopo por trait | seus models | — | `BelongsToTenant` dá relação, escopo global e preenchimento — vale fora do Filament também | 🟢 |
| F-27 | **Identidade visual: cor** | organização → *Identidade visual* | `admin` | escolha a cor e abra `/app/{org}`: o painel inteiro veste a cor dela, e o `/admin` **não** muda | 🔵 |
| F-28 | **Identidade visual: logo** | idem | `admin` | a logo aparece na tela de bloqueio do `/app` no lugar da imagem base | 🔵 |

## Administração

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-29 | Usuários | `/admin/users` | `admin` | CRUD, com papel **obrigatório** no cadastro | 🟢 |
| F-30 | Catálogo de agentes de IA | `/admin/agentes-ia` | `admin` | prompt, provider, modelo, tools e guardrails são **dados**, editáveis sem deploy | 🟢 |
| F-31 | Autoria de onboarding | `/admin/onboarding-flows` | `admin` | checklists e tours; o consumo fica no painel de negócio | 🔵 |
| F-32 | Dashboard preenchido | `/admin` | `admin` | 6 widgets sobre os dados que o painel já tem | 🔵 |

## Infraestrutura

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-33 | Health checks | `/infra/health-check-results` | `infra` | banco, cache, filas, agendador, debug mode e IA local. **Abre vazia até rodar `php artisan health:check`** | 🔵 |
| F-34 | Backups | `/infra/backup-runs` | `infra` | histórico e saúde por destino | 🔵 |
| F-35 | Filas | `/infra/queue-monitors` | `infra` | pendentes, falhas e histórico — de qualquer driver | 🔵 |
| F-36 | Logs | `/infra/logs` | `infra` (`ver-logs`) | leitura e busca por channel. **Sem botão de apagar**: trilha é evidência | 🔵 |
| F-37 | Auditoria de alterações | `/infra/audits` | `infra` | quem mudou o quê, campo a campo | 🔵 |
| F-38 | Trilha de acesso | `/infra/authentication-logs` | `infra` | logins, IP e dispositivo | 🔵 |
| F-39 | Central de comandos | `/infra/command-center/commands` | `infra` (`command-center:access`) | comandos **pré-aprovados** em `config/command-center.php`, com histórico | 🔵 |
| F-40 | Pulse | `/infra/pulse` | `infra` | performance em tempo real. Precisa de `pulse:check` para ter dados | 🔵 |
| F-41 | Grafo de dependências | `/infra/dependency-graph` | autenticado no `/infra` | mapa de models, relações, resources e painéis | 🔵 |
| F-42 | Releases do Composer | `/infra/composer-release-packages` | `infra` | avisa versão nova. **Informativo — nunca atualiza nada.** O sync é um job: sem worker, a tela fica vazia | 🔵 |
| F-43 | Execuções de IA | `/infra/execucoes-ia` | `infra` (`ver-ai-tasks`) | ledger com custo e tokens por execução | 🔵 |
| F-44 | Limpar caches | topbar do `/infra` | `infra` | `cache`, `config`, `view` e `modelCache` juntos | ⚪ |
| F-57 | Exceções agrupadas | `/infra/exceptions` | `infra` | por tipo e frequência, com badge no menu. A retenção (`KIT_RETENCAO_EXCECOES_DIAS`) só acontece **com o agendador rodando** | 🟢 |
| F-58 | Trilha de e-mails | `/infra/mail-logs` | `infra` | todo e-mail enviado. Guarda o **corpo** — inclusive o link de aceite do convite | 🟢 |
| F-59 | Lixeira | `/infra/recycle-bin` | `infra` | restaura o que foi apagado com `SoftDeletes`. Lista **só** as models declaradas em `models()` no `InfraPanelProvider` | 🟢 |

## Produtividade e UI

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-45 | Busca ⌘K | topbar dos três | autenticado | registros, telas, páginas e ações "Criar X" — **tudo recortado por permissão** | ⚪ |
| F-46 | Badges de contagem animados | menu lateral | autenticado | a contagem sai de `getEloquentQuery()`; zero não vira badge | 🟢 |
| F-47 | Centro de notificações | sininho | autenticado | abas e categorias; tempo real com Reverb, senão polling de 30 s | ⚪ |
| F-48 | Troca de painel | menu do usuário | quem alcança mais de um | vai direto ao painel escolhido | 🔵 |
| F-49 | **Tema claro/escuro** | alternador no topo | qualquer um | as telas seguem `prefers-color-scheme` e o alternador; a escolha persiste | 🔵 |
| F-50 | Colunas redimensionáveis | qualquer tabela | autenticado | largura ajustável, lembrada na sessão | ⚪ |
| F-51 | Indicador de ambiente | topbar | qualquer um | badge de `local`/`homologação`; some em produção | 🔵 |
| F-52 | Páginas de erro brandadas | 403, 404, 419, 500, 503 | — | com a cara do painel, em pt-BR | 🔵 |
| F-60 | **Seletor de idioma** | topbar dos três e telas de login | qualquer um | só aparece com **dois** locales em `kit.idiomas`; traduz Filament e pacotes, **não** os rótulos do kit | 🟢 |
| F-61 | **Anexos e mídia** | formulário e tabela de Projetos | quem alcança o resource | upload, coleção `anexos`, conversão `miniatura` e lightbox na tabela. O anexo herda o escopo da organização do próprio registro | 🟢 |

## IA

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-53 | Chat do assistente | canto de **toda** tela do `/app` | autenticado | streaming; renderiza vazio sem usuário | ⚪ |
| F-54 | Guardrails encadeados | — | — | budget, prompt injection, classificador local, redação de PII e filtro de saída. **Fail-closed** | 🟢 |
| F-55 | Ledger de execuções | `/infra/execucoes-ia` | `infra` | toda chamada vira linha com custo e tokens | 🟢 |
| F-56 | Inferência local | `docker compose --profile ai up -d` | — | llama.cpp; ou troque `AI_PROVIDER` por um SaaS | ⚪ |

## O que o roteiro **não** cobre sozinho

Algumas features dependem de coisa fora do processo, e nenhum teste as substitui:

| Feature | Depende de | Sem isso |
|---|---|---|
| F-57, F-58 (retenção das trilhas) | o scheduler (`schedule:work`) | as tabelas de exceções e de e-mails crescem sem teto; o prazo em `config/kit.php` fica só declarado |
| F-15…F-20 (entrega do e-mail) | `MAIL_MAILER` real **e** um worker (`QUEUE_CONNECTION=database`) | o convite é gravado e a fila enche; nada sai |
| F-33 (health checks) | uma execução de `php artisan health:check` | a tela abre **vazia**, sem estado explicando — o widget do dashboard avisa, a página do recurso não |
| F-35, F-42 (filas e releases) | um worker | o job de sync do Composer fica na fila: F-42 mostra "sem registros" e F-35 conta pendências contra uma tabela vazia |
| F-19 (lembretes) | o scheduler (`schedule:work`) | o comando nunca é chamado |
| F-34 (backups) | destino configurado em `config/backup.php` | a tela abre vazia |
| F-40 (Pulse) | `pulse:check` rodando | a tela abre sem dados |
| F-53, F-56 (IA) | llama.cpp ou uma API key | o assistente responde indisponível |

Os três primeiros o `composer dev` já resolve em desenvolvimento: ele sobe servidor, fila e Vite
juntos.

