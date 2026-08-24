<img alt="Starter Kit Easy" class="filament-hidden" src="https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbnail.png"/>

[![Packagist](https://img.shields.io/packagist/v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Downloads](https://img.shields.io/packagist/dt/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Plumb](https://plumbphp.dev/badges/gsferro/starter-kit-easy/composite.svg)](https://plumbphp.dev/gsferro/starter-kit-easy)
[![Testes](https://img.shields.io/github/actions/workflow/status/gsferro/filament-starter-kit-easy/ci.yml?branch=main&style=flat-square&label=testes)](https://github.com/gsferro/filament-starter-kit-easy/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/packagist/php-v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Filament](https://img.shields.io/badge/Filament-5.x-FFAA00?style=flat-square)](https://filamentphp.com)
[![License](https://img.shields.io/packagist/l/gsferro/starter-kit-easy.svg?style=flat-square)](LICENSE)

> 🇧🇷 Português · 🇺🇸 [English](https://github.com/gsferro/filament-starter-kit-easy/blob/main/README.en.md)

Starter kit **Laravel 13 + Filament 5** pronto para uso. Um comando cria o projeto, instala tudo, migra, popula o banco e entrega três painéis funcionando: **negócio**, **administração** e **infraestrutura**.

```bash
composer create-project gsferro/starter-kit-easy meu-projeto
cd meu-projeto
composer dev
```

Não há passo manual: o `create-project` já cria o `.env`, gera a `APP_KEY`, cria o banco, roda as migrations, semeia papéis/permissões/usuário, publica os assets do Filament e faz o build do front-end. Ao final ele imprime as URLs e o login inicial.

Antes de tocar no banco, ele **pergunta cinco coisas** — como o `laravel new` faz:

| | Pergunta | Padrão |
|---|---|---|
| 1 | Nome do projeto | o nome da pasta |
| 2 | Banco de dados | SQLite · **PostgreSQL** (recomendado: é o único com `pgvector`, exigido pelas funções de IA local) · MySQL |
| 3 | E-mail e senha do administrador | `admin@example.com` / `password` |
| 4 | Cor primária dos painéis | o padrão do Filament |
| 5 | Multi-organização (multi-tenancy) | desligada |

**Enter em tudo instala exatamente como antes** — nenhuma pergunta é obrigatória, e a primeira delas é "personalizar agora?", que pula todas de uma vez. Sem terminal (CI, Docker, `--no-interaction`) nada é perguntado. Ao final o instalador mostra o resumo do que mudou, o que continua sendo editado à mão, e oferece rodar os testes do kit.

> **No Windows as perguntas não aparecem, e isso não é bug do kit.** Medido nos dois shells,
> PowerShell e Git Bash: o Composer nunca liga TTY em Windows — `ProcessExecutor::runProcess()`
> descarta o modo TTY quando `Platform::isWindows()`, porque o `symfony/process` lançaria
> `TTY mode is not supported on Windows platform`. O `artisan` recebe pipes, e o instalador se
> pula pelo próprio guarda de terminal, avisando na tela.
>
> **O que fazer**, e a ordem importa:
>
> ```bash
> php artisan kit:install --force    # as cinco perguntas — RECRIA o banco
> ```
>
> Rode **logo depois de instalar**, com o banco ainda só com os dados de seed: aí o `--force` é
> inócuo. Mais tarde ele é destrutivo, porque apaga o SQLite antes de perguntar.
>
> Se já tem dado no banco e quer só o nome e a cor:
>
> ```bash
> php artisan kit:install --custom   # nome e cor, sem tocar em nada
> ```
>
> As outras três perguntas não têm versão não destrutiva, e o comando explica por quê: banco e
> multi-organização exigem recriar (as tabelas de permissão só nascem com a coluna de contexto
> antes do `migrate`), e as credenciais do administrador **não são sincronizadas pelo seeder** —
> ele garante que exista um administrador, e não que ele espelhe o `.env`, porque roda em todo
> `db:seed` e sobrescrever ali reverteria a senha trocada à mão.
>
> Para trocar e-mail ou senha do administrador, o caminho é deliberado:
>
> ```bash
> php artisan kit:admin
> ```
>
> Ele pede confirmação, nunca ecoa a senha, recusa e-mail que já pertence a outra conta e **para**
> se houver mais de um `master_global` — em vez de escolher um por ordenação. A tela de perfil do
> painel também serve.
>
> Em Linux, macOS e WSL as perguntas aparecem no `create-project` e nada disso é necessário.

> A multi-organização é o item que mais compensa decidir agora: ligada na instalação, ela custa zero; ligada depois, o `kit:tenancy` **recria o banco** (as tabelas de permissão só nascem com a coluna de contexto se a flag estiver ativa antes do migrate).

![Instalação do starter-kit-easy em um comando](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/install.gif)

Prefere clonar? O mesmo instalador roda sozinho:

```bash
git clone https://github.com/gsferro/filament-starter-kit-easy.git meu-projeto
cd meu-projeto && rm -rf .git && git init   # descarta o histórico do kit
composer setup
```

## Acesso de demonstração

O seeder cria um usuário master que já entra nos três painéis:

| | |
|---|---|
| **Usuário** | `admin@example.com` |
| **Senha** | `password` |
| **Papel** | `master_global` (vence qualquer permissão via `Gate::before`) |

Entre por `/app`, `/admin` ou `/infra` — a mesma sessão vale para os três, e o menu do usuário troca de painel.

> ⚠️ **Troque a senha antes de expor o ambiente.** Para nascer com outra credencial, defina `KIT_ADMIN_EMAIL`, `KIT_ADMIN_PASSWORD` e `KIT_ADMIN_NAME` no `.env` **antes** de rodar a instalação (os valores ficam em `config/kit.php`). Num projeto já instalado, troque pelo próprio painel em `/admin/users` ou em **Meu perfil**.

Para testar o recorte de acesso, crie um usuário só com o papel `admin` ou `infra`: ele entra no painel correspondente e toma 403 no outro.

## Os três painéis

| Painel | URL | Para quê | Quem entra |
|---|---|---|---|
| **App** | `/app` | A operação do negócio. **Vem vazio de propósito** — é aqui que seu projeto nasce | `master_global`, `panel_user`, `admin_app` (com tenancy) |
| **Admin** | `/admin` | Usuários, papéis e permissões (Shield), catálogo de agentes de IA, autoria de onboarding | `master_global`, `admin` |
| **Infra** | `/infra` | Health checks, backups, filas, logs, exceções, trilha de e-mails, lixeira, auditoria, caches, comandos, Pulse, custos de IA | `master_global`, `infra` |

**Quem entra vem do papel, não de uma lista no código.** Cada papel declara em qual painel vale, na coluna `roles.painel` — é o campo **Painel** na tela `/admin` → Papéis. `App\Models\User::canAccessPanel()` compara essa coluna com o painel que está sendo aberto. Criar um papel e escolher o painel dele **é** o ato de dar acesso.

Nulo **não** é coringa: papel sem painel só carrega permissões e não abre painel algum. O papel `master_global` entra nos três de outro jeito — ele vence qualquer gate via `Gate::before` (`App\Providers\KitServiceProvider`), sem precisar de permissions no banco, e o `canAccessPanel()` o libera antes de olhar a coluna.

Nos painéis **sem** tenancy (`/admin`, `/infra`) o papel precisa estar atribuído no contexto global: ser `admin` dentro de uma organização não é credencial para administrar a instalação. No `/app` vale o papel em qualquer organização — qual delas você abre é decidido depois, por `canAccessTenant()`.

> Com o [modo multi-tenant](#multi-tenancy-opt-in) ligado, o **App** vira `/app/{tenant}` e passa a mostrar só os dados do tenant selecionado. Admin e Infra seguem globais.

Separar admin de infra é o ponto do kit: quem administra usuários não precisa (nem deve) enxergar logs, filas e comandos operacionais, e vice-versa.

### Como cada um se parece

| Login | Administração |
|---|---|
| [![Tela de login](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/login.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login.png) | [![Painel admin](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-admin.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-admin.png) |
| Auth Designer em duas colunas — troque a arte em `public/images/auth/login.svg` | Usuários, papéis, agentes de IA e indicadores de administração |

| Infraestrutura | Negócio |
|---|---|
| [![Painel infra](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-infra.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-infra.png) | [![Painel app](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/panel-app.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/panel-app.png) |
| Saúde, filas, trilhas, comandos e custos de IA — agrupados em Observabilidade, IA, Trilhas e Sistema | Vazio de propósito: é onde o seu projeto nasce |

Mais telas: [saúde da aplicação](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/infra-health.png) · [usuários](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-users.png) · [permissões (Shield)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-roles.png) · [catálogo de agentes de IA](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-agentes-ia.png) · [central de comandos](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/infra-comandos.png) · [busca ⌘K](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/spotlight.png) · [acesso negado](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/erro-403.png)

## Nossos números

Não é vitrine: é o inventário de tudo que já existe, e o que você não vai precisar escrever.

| | `/app` | `/admin` | `/infra` | **Total** |
|---|---:|---:|---:|---:|
| **Telas navegáveis** | 12 | 28 | 27 | **67** |
| Resources | 4 | 8 | 8 | **20** |
| Páginas próprias | 4 | 3 | 12 | **19** |
| Widgets | 1 | 9 | 19 | **29** |
| Rotas `GET` | 19 | 34 | 33 | **86** |

O `/app` é o menor de propósito — ele nasce **vazio**, porque é onde o seu negócio entra. Os outros
dois já vêm completos.

| Fundação | |
|---|---:|
| Pacotes de produção | **55** |
| Pacotes de desenvolvimento | **15** |
| Migrations | **48** |
| Policies | **14** |
| Comandos `kit:*` | **4** |

| Qualidade | |
|---|---:|
| Casos de teste (`Kit` + `Tenancy`) | **411**, com 1138 asserções |
| Telas varridas em navegador real | **55** |
| Arquivos de teste | **94** |
| PHPStan | **level 7**, zero erros |
| FilaCheck | **17** regras, todas passando |

| Documentação | |
|---|---:|
| Documentos de referência (`wikis/`) | **9** |
| Features especificadas (`wikis/specs/`) | **28** |
| Project rules para agentes de IA (`.ai/rules/`) | **13** |

### PHPStan no level 7 — e por que isso é um ponto forte

A maioria dos projetos Laravel para no level 5 ou 6. O kit roda no **7, com zero erros e sem
baseline**: não há `@phpstan-ignore` espalhado, não há `phpstan-baseline.neon` escondendo dívida.

O que o level 7 pega e o 6 não pega, na prática:

- **Nulo não checado.** `Filament::getCurrentPanel()` devolve `?Panel`; `auth()->user()` devolve
  `?User`. No level 6 você chama método neles e passa. No 7, precisa provar que existe.
- **Tipo largo do vendor entrando no seu código.** `session()` é `mixed`, `env()` é `bool|string`,
  os getters do Shield são `?array`. O 7 obriga a estreitar na **fronteira**, uma vez, em vez de
  torcer para o valor ser o esperado em cada uso.
- **`list<T>` vs `array<int,T>`.** `filter()` e `map()` preservam chave. Um array com buracos
  entregue onde se esperava lista é bug que só aparece no `json_encode` — vira objeto em vez de
  array, e o front quebra.

Subir de 6 para 7 expôs **29 erros reais** no kit, e um deles era bug latente de verdade: um
`Convite|null` com método chamado direto. Todos corrigidos na origem — nenhum silenciado.

> ### ⚠️ Ponto de atenção ao implementar no seu projeto
>
> **O level 7 vale para o código que você escrever também.** `composer test` roda
> `phpstan analyse` e reprova o build inteiro.
>
> O que mais aparece quando alguém começa a escrever no kit:
>
> | Você escreve | O que o PHPStan cobra |
> |---|---|
> | `auth()->user()->id` | prove que há usuário: `auth()->user()?->id`, ou um `if` antes |
> | `Filament::getTenant()->nome` | `?Model` — use `instanceof Tenant` como guarda |
> | `->filter()->all()` num `@return list<string>` | `array_values()` no fim |
> | `env('ALGUMA_COISA')` direto num `str_*` | `(string) env(...)`, ou `config()` com default tipado |
> | método sem tipo de retorno | declare o tipo; o kit exige em tudo |
>
> **Não resolva com `@phpstan-ignore` nem baseline.** O kit tem exatamente **uma** exceção em
> `phpstan.neon`, e ela é para um macro de vendor resolvido em runtime — com o motivo, as duas
> alternativas testadas e descartadas, e o teste que cobre o ponto de verdade. Esse é o padrão:
> se precisar de exceção, ela vem com a justificativa e com o teste que a substitui.
>
> Se quiser afrouxar no seu projeto, é uma linha em `phpstan.neon`. Mas saiba o que está trocando:
> os 29 erros acima eram todos reais.

## O que já vem pronto

**Porta de entrada**
- **Página de boas-vindas na rota `/`**, no lugar da welcome padrão do Laravel: um cartão por
  painel e as informações do que o `kit:install` personalizou ([detalhes](#a-rota--é-pública-e-não-mostra-segredo))

**Administração e segurança**
- Shield (papéis e permissões com UI) sobre spatie/laravel-permission
- Breezy: perfil do usuário, avatar, 2FA e passkeys
- Auth Designer: tela de login em duas colunas (troque a arte em `public/images/auth/login.svg`)
- **Registro aberto opcional** (desligado por default): cadastro sem convite no `/app`, com papel único, aprovação manual e validação de e-mail — cada um em sua chave ([detalhes](#registro-aberto-e-aprovação))
- Lockscreen: bloqueio de sessão por inatividade (30 min), registrado nos 3 painéis — a tela de bloqueio usa o mesmo layout do login (Auth Designer), não o layout simples do Filament
- Impersonate, log de autenticação, auditoria de alterações (owen-it)
- Panel Switch: troca de painel pelo menu do usuário

**Observabilidade e manutenção (painel infra)**
- Spatie Health com checks de banco, cache, filas, agendador, disco, debug mode e IA local
- Backup Monitor (spatie/laravel-backup), Jobs Monitor, Logs Explorer (sem botão de apagar — trilha é evidência)
- **Exceções agrupadas** por tipo e frequência — o que Health, Pulse e arquivo de log não respondem
- **Trilha de e-mails enviados**: separa "não foi enviado" de "foi enviado e caiu no spam"
- **Lixeira**: restaura o que foi apagado com `SoftDeletes` ([detalhes](#trilhas-do-infra-exceções-e-mails-e-lixeira))
- Command Center: comandos Artisan pré-aprovados pela UI, com histórico
- Laravel Pulse embutido como página do painel
- Dependency Graph: mapa de models, relações, resources e painéis
- Release Notifier: avisa quando há versão nova dos pacotes Composer

**IA (opcional, local por padrão)**
- `laravel/ai` com catálogo de agentes no banco: system prompt, provider, modelo, tools e guardrails são **dados**, editáveis no `/admin` sem deploy
- Guardrails encadeados: budget, prompt injection, classificador local, redação de PII e filtro de saída sensível
- Ledger de execuções (`ai_runs`) com custo e tokens no painel infra
- Widget de chat com streaming
- Inferência 100% local via llama.cpp (`docker compose --profile ai up -d`) ou qualquer provider SaaS trocando `AI_PROVIDER`

**Produtividade**
- **Busca ⌘K** no lugar do campo nativo da topbar: encontra registros, telas, páginas e ações de criação — tudo recortado por permissão (detalhes abaixo)
- Badges de contagem animados no menu, centro de notificações com abas, indicador de ambiente
- **Dashboards já preenchidos** nos painéis admin e infra: 20 widgets (stat cards com contador animado, funis, metas, breakdowns, timelines) sobre os dados que os painéis já têm — nada de tela vazia esperando você
- Páginas de erro brandadas (Sentinel) em pt-BR — a de 403 só mostra o diagnóstico de permissão fora de produção
- UI 100% em pt-BR, inclusive nos plugins que só trazem inglês (traduções em `lang/vendor/`)
- **Seletor de idioma** nos três painéis e nas telas de login — dirigido por dado, não por flag (detalhes abaixo)
- **Camada de mídia** (spatie/laravel-medialibrary) nos componentes do Filament: upload, coleções e conversões em formulário, tabela e infolist ([detalhes](#anexos-e-mídia))

### A busca ⌘K

[![Busca ⌘K](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/spotlight.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/spotlight.png)

O campo na topbar é o **nativo do Filament** — mesma marcação, mesma aparência, mesmo `Ctrl/⌘+K`. O que muda é o que acontece ao clicar: em vez de digitar ali, abre o overlay do Spotlight, que busca em quatro frentes:

| Categoria | O que encontra |
|---|---|
| **Registros** | a busca global nativa do Filament (respeita `getGloballySearchableAttributes()` dos seus resources) |
| **Telas** | os resources do painel, **filtrados por `canAccess()`** |
| **Páginas** | as páginas do painel, também por `canAccess()` |
| **Ações** | "Criar X" para cada resource, com `canAccess()` + `canCreate()` + `shouldRegisterNavigation()` |

O filtro por permissão é a razão de existirem `App\Filament\Spotlight\*` no kit: as categorias do pacote **não** chamam `canAccess()`, e sem isso a busca oferece telas que resultariam em 403 — vazamento de affordance. As sugestões "Criar X" também são do kit (`AcoesDeCriacao`), pelo mesmo motivo e mais um: o discovery do pacote resolve URLs sem checar contexto e derruba a tela de login com 500.

### O seletor de idioma

O botão de idioma (`bezhansalleh/filament-language-switch`) está registrado nos **três painéis e também nas telas de login** — que é justamente onde alguém que não lê português precisa trocar, antes de existir sessão.

**Ele é dirigido por dado, não por flag.** A lista de locales fica em `config/kit.php`:

```php
'idiomas' => ['pt_BR'],           // como o kit nasce: um idioma, sem botão
'idiomas' => ['pt_BR', 'en'],     // dois idiomas: o seletor aparece sozinho
```

Com **um único idioma** — que é o padrão — o seletor não aparece: não há para onde trocar. É a razão de isto ser uma lista e não um booleano; ninguém esquece uma flag ligada com um idioma só.

> ⚠️ **O seletor traduz a camada do Filament e dos pacotes, não os rótulos do kit.** A cobertura vem do Filament e do `laravel-lang/common`. "Administrador Geral", "Acesso ao painel /app", os títulos dos hubs e os labels dos resources são strings pt-BR escritas no código — há dez `__()` em todo o app. Com `en` ligado hoje, **metade da tela troca de idioma e a outra metade não**. Internacionalizar o kit é trabalho declarado e ainda não feito.

## Convite de usuário

Alguém de fora vira usuário **por convite, e só por convite**. Um administrador abre
`/admin/convites` — ou, com tenancy, quem tem `admin_app` abre
`/app/{organizacao}/convites` — e escolhe e-mail, papel e organização; o kit envia um link
com token de uso único.

**Quem convida não precisa saber se o endereço já tem conta.** O kit decide no aceite, e as
duas vias usam o mesmo convite e o mesmo link:

| O endereço | O que acontece no aceite |
|---|---|
| **não tem** conta | a pessoa define a própria senha e nasce com o papel certo, no contexto certo, e com o e-mail já verificado — o token prova a posse do endereço |
| **já tem** conta | é uma **oferta de acesso**: ninguém é cadastrado de novo. A pessoa entra com a senha que já tem, confirma, e é vinculada à organização com o papel do convite — os acessos dela nas outras organizações ficam intactos |

Na via de oferta o token **não basta**: o aceite exige que a conta autenticada seja a do
e-mail convidado, conferido no model e não na query da tela. Link interceptado não vira
acesso sem a senha do endereço convidado.

E dá para dizer **não**. O menu do usuário ganha **Convites recebidos**, com a contagem das
ofertas pendentes e as ações de aceitar e recusar; a recusa fica **registrada**, o convite
deixa de valer (inclusive pelo link) e quem administra vê "Recusado" na listagem em vez de
reconvidar alguém que já disse não. O link do e-mail continua sendo a via canônica: ele
funciona também para quem ainda não pertence a nenhuma organização e por isso não alcança
essa tela.

A tela de aceite é a página de registro nativa do Filament (`/app/register`), com uma
guarda: **sem token válido na query string ela recusa e manda para o login**. Não existe
cadastro aberto.

| O que | Como |
|---|---|
| Token | `Str::random(64)`, guardado **hasheado** (`sha256`) — banco vazado não vira acesso |
| Validade | `KIT_CONVITE_VALIDADE_DIAS` (7 dias por padrão) |
| Em massa | **Convidar em massa** no header da listagem: cole os endereços, um papel e uma organização para o lote. Até `KIT_CONVITE_LIMITE_LOTE` (100 por padrão) — um endereço com problema **não impede os outros**, e o resumo diz quantos saíram e por que os outros não |
| Uso | **único**: na conta nova, `aceito_em` é carimbado na mesma transação que cria o usuário; na oferta, por `update` condicional — é o que impede dois cliques de valerem duas vezes |
| Lembrete | `KIT_CONVITE_LEMBRETES_DIAS` (D+3 e D+5 por padrão, contados do envio): o kit manda **um** lembrete por convite por dia devido, com um **segundo link paralelo** — o link original **continua valendo**, e nada é revogado nem se o lembrete cair no spam. O teto é a quantidade de dias da lista, e a lista vazia desliga a feature. Todo dia precisa ser **menor** que a validade, senão o convite expira antes de o lembrete ser devido e nenhum lembrete sai |
| Reenviar | gera token novo e **mata os links anteriores** — o do envio e o do último lembrete |
| Revogar | apaga o convite; o link para de funcionar na hora, e a exclusão fica em `/infra/audits` |
| Editar | **não existe** — o convite já foi enviado; corrija revogando e criando outro |

> ⚠️ **O convite depende de duas coisas de ambiente.** `MAIL_MAILER` no default `log` só
> escreve o e-mail em `storage/logs` — nada sai para o mundo. E a notificação é
> enfileirável com `QUEUE_CONNECTION=database`: **sem um worker rodando o convite não
> sai**. O `composer dev` sobe um; num deploy, `php artisan queue:work`. A fila parada
> aparece no monitor do `/infra`. **Multiplique por N no convite em massa**: um lote de cem
> põe cem linhas em `jobs` e entrega zero, e a tela diz "cem enviados" — porque foram, para
> a fila. Com `QUEUE_CONNECTION=sync` é o oposto: cada e-mail é um handshake SMTP dentro do
> request, e cem encostam no `max_execution_time`. É o que o limite do lote protege.

> ⚠️ **O lembrete exige as duas coisas acima E o scheduler.** Quem manda é
> `kit:convites-lembrar`, agendado em `routes/console.php` para as 08:00 — sem
> `php artisan schedule:work` (ou o serviço `scheduler` do docker compose) ele nunca é
> chamado. E o contador do convite **sobe mesmo com o worker parado**: a gravação acontece
> antes de a notificação ser enfileirada, de propósito, para que um endereço permanentemente
> quebrado não faça o cron tentar o mesmo convite todo dia para sempre. A consequência é
> honesta: worker parado gasta lembretes sem entregar e-mail. Numa instalação com convites
> antigos acumulados, ensaie com `MAIL_MAILER=log` — que é o default do kit.

O papel do convite decide o contexto da atribuição: papel do painel `/app` nasce dentro da
organização do convite; papel de `/admin` ou `/infra` nasce no contexto global — ser
administrador de uma organização não é credencial para administrar a instalação.

## A rota `/` é pública e não mostra segredo

No lugar da `welcome.blade.php` do Laravel, a raiz serve `App\Filament\Pages\BoasVindas`: um
cartão por painel (`/app`, `/admin`, `/infra`) e uma infolist com o que a instalação
personalizou — nome, cor, tenancy, prazos de retenção, versão do kit.

Ela é **anônima**, como a página que substitui, e é por isso que a lista do que ela **não**
mostra importa: e-mail, nome e senha do administrador, host e usuário do banco, URL do
repositório, `app.env`, `app.debug`, `app.url` e a configuração de e-mail. Há caso de teste que
planta uma sentinela em cada um desses valores e assere a ausência dela no HTML — junto de um
`assertOk()`, senão um 500 passaria em todas as linhas por engano.

Foi recusada, de propósito, a alternativa "exibir tudo fora de produção": segurança que depende
de `APP_ENV` estar certo não é segurança.

A rota carrega o middleware `panel:app`, e isso não é decoração — é o alias de `SetUpPanel`, que
boota o painel e com isso traz a folha do Filament, a paleta do projeto e o alternador de tema.
Foi medido: `@filamentStyles` sozinho não traz a folha e a página sai âmbar mesmo com
`KIT_COR_PRIMARIA=Violet`. O middleware não autentica ninguém.

```php
// routes/web.php
Route::get('/', BoasVindas::class)->middleware('panel:app')->name('boas-vindas');
```

## Registro aberto e aprovação

O convite é a porta padrão do kit. A segunda porta — **cadastro aberto**, sem convite — existe,
e **nasce desligada**:

```dotenv
KIT_REGISTRO=false                      # a porta pública
KIT_REGISTRO_APROVACAO_MANUAL=false     # nasce pendente até alguém aprovar?
KIT_REGISTRO_VERIFICAR_EMAIL=false      # exige e-mail validado no /app? (tambem editavel na tela)
```

Com `KIT_REGISTRO=false`, `/app/register` responde **só** a quem traz um token de convite
válido — sem token, recusa e manda para o login. É o comportamento que o kit sempre teve, e
nada nesta seção acontece até você ligar a chave.

### As duas portas convivem na mesma tela

`/app/register` decide o caminho pela **ausência** do parâmetro `token`:

| URL | Com `KIT_REGISTRO=false` | Com `KIT_REGISTRO=true` |
|---|---|---|
| `?token=` válido | aceite de convite | aceite de convite (igual) |
| `?token=` inválido, expirado ou usado | recusa → login | **recusa → login** (igual) |
| sem `token` | recusa → login | formulário de cadastro |

A segunda linha é deliberada: token inválido **nunca** cai no cadastro aberto. Se caísse,
`?token=qualquer-coisa` seria uma segunda entrada para a porta pública — justamente a que não
passa pelo limite de tentativas da recusa nem pela mensagem genérica que não revela se o token
não existe, venceu ou já foi usado.

### O que ligar `KIT_REGISTRO=true` faz refletir

| Onde | O que muda |
|---|---|
| `/app/register` sem token | passa a exibir o formulário em vez de recusar |
| `/app/login` | passa a oferecer o link "Criar conta" (antes escondido, porque levaria a uma tela que recusa) |
| papel de quem se cadastra | **só** `panel_user` — nenhum outro perfil, e 403 em `/admin` e `/infra` |
| `/admin/organizacoes` (com tenancy) | aparece o campo *"Aceita cadastro público"* em cada organização |
| tela de usuários (`/admin` e `/app`) | ganha a coluna **Situação**, o filtro *"Somente pendentes"* e a ação **Aprovar** |
| channel de log `autenticacao` | passa a registrar cada cadastro e cada aprovação, com o e-mail mascarado |

### O papel de quem se cadastra, e nada além dele

Quem entra por essa porta recebe **um único** papel: `panel_user`, o perfil básico do painel de
negócio. Não recebe `admin_app`, não alcança `/admin` nem `/infra` — os dois respondem **403**.
Quem administra ajusta os papéis depois, na tela de usuários, que é onde essa decisão vive.

A atribuição é feita em um lugar só (`App\Support\RegistroAberto::papel()`), e vale também para
quem chamar o registro de fora da tela — um comando, um job, um seeder.

### Aprovação manual: pendente não entra em painel nenhum

Com `KIT_REGISTRO_APROVACAO_MANUAL=true`, o cadastro nasce **pendente**:

- não recebe papel nenhum;
- a sessão que o cadastro abriu é encerrada na hora, e a pessoa volta ao login com a mensagem
  de que a conta aguarda liberação — em vez de levar um 403 depois de um cadastro que funcionou;
- `/app`, `/admin` e `/infra` respondem **403** enquanto ele estiver pendente.

Quem libera abre a tela de usuários, filtra por *"Somente pendentes"* e usa a ação **Aprovar**.
Aprovar dá o papel do painel de negócio (na organização corrente, quando há tenancy) e é
idempotente — clicar duas vezes não duplica papel. A ação exige permissão de **editar usuário**,
então o usuário comum do `/app` não a vê.

> Enquanto o cadastro está pendente ele **não tem papel**, e o formulário de edição sabe disso:
> o campo *Papéis*, normalmente obrigatório, deixa de ser exigido nesse caso. Sem essa exceção a
> edição de um pendente seria impossível de salvar, e a única saída seria dar um papel à mão —
> o que concede acesso sem passar pela aprovação.

### Cadastro por organização (multi-tenancy)

Com a multi-organização ligada, **duas** condições valem juntas: a instalação libera o cadastro
(`KIT_REGISTRO=true`) **e** cada organização opta, no campo *"Aceita cadastro público"* da tela
dela. O default de cada organização é **não** — ligar a chave global não abre cadastro em
nenhuma organização existente sem alguém decidir.

O endereço carrega o slug:

```text
/app/register?org=acme
```

Sem `?org`, com slug desconhecido, com a organização **inativa** ou com o cadastro **desligado**
nela, a tela devolve **a mesma** recusa — quem visita não descobre qual das condições falhou. Ou
seja: numa instalação multi-organização, divulgar `/app/register` sem o `?org=` leva as pessoas
à recusa; divulgue o link da organização.

Isto não se confunde com *criar* organização: registrar-se **numa** organização não é criá-la, e
quem cria organização continua sendo quem administra a instalação, pelo `/admin`.

### Validação de e-mail (opcional)

Exige e-mail confirmado para entrar no `/app`. **Editável em `/admin/configuracoes-do-kit` → aba
Registro**, e o valor gravado vale no **request seguinte** — sem deploy. `KIT_REGISTRO_VERIFICAR_EMAIL`
continua existindo: ele semeia a instalação nova e é o plano B, como as demais configurações da
tela.

> Até a v0.19.3 esta chave valia **só** pelo `.env`, e a tela dizia isso. O motivo era real: o
> Filament fixa o middleware de e-mail verificado no array da rota no momento do registro, então a
> decisão era tomada no boot e um toggle na tela gravava sem fazer efeito. O conserto foi tirar a
> *decisão* do array da rota e pôr lá um *decisor* — `App\Http\Middleware\ExigirEmailVerificado`,
> que pergunta a cada request. A tela de confirmação, por consequência, passou a existir sempre.

**Leia esta parte antes de ligar em ambiente com gente dentro — agora um clique basta.** A
exigência vale para **todo** usuário do `/app`, não só para os recém-cadastrados, e **não** depende
de o cadastro aberto estar ligado: quem estiver sem `email_verified_at` é levado à tela de
confirmação. Numa instalação limpa isso não atinge ninguém, porque os caminhos que o kit usa para
criar usuário já gravam a coluna — o seeder do administrador, a factory, o seeder da demo, o
aceite de convite e o `kit:admin`. Quem **não** grava é a criação manual pela tela de usuários.

Para marcar como validada a base que já existe, antes de ligar:

```bash
php artisan tinker --execute 'App\Models\User::whereNull("email_verified_at")->update(["email_verified_at" => now()]);'
```

**Quem vem de convite nunca é afetado**, e vale saber por quê: `Convite::aceitar()` grava
`email_verified_at` de propósito — o token chegou no endereço da pessoa, então ele já provou a
posse, e pedir a mesma prova duas vezes é atrito sem ganho. O Filament só envia o pedido de
confirmação para quem ainda não validou, então o convidado nunca recebe esse e-mail.

### O limite de tentativas

O envio do formulário usa o limite do próprio Filament: **2 tentativas por IP** e 2 por
endereço de e-mail, por janela — o mesmo que o aceite de convite já usava. A recusa por falta de
convite tem limite próprio (5 por 10 minutos por IP), que protege o **arquivo de log** contra um
laço anônimo, sem mudar o que a pessoa vê.

### Onde isso vive no código

| O quê | Onde |
|---|---|
| as três opções, lidas em um lugar só | `app/Support/RegistroAberto.php` |
| a tela, com os dois modos | `app/Filament/Pages/Auth/RegistroPorConvite.php` |
| a barreira do pendente | `App\Models\User::canAccessPanel()` — primeira instrução |
| a liberação | `App\Models\User::aprovar()` |
| coluna, filtro e ação de aprovar | `app/Filament/Concerns/AprovacaoDeCadastro.php` |
| o campo por organização | `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php` |

> **Para quem for ligar as opções numa tela de Settings**: `App\Support\RegistroAberto` é o ponto
> único de leitura. Trocar `config()` pelo Settings é reescrever o corpo de três métodos naquele
> arquivo — nenhum outro lugar do projeto lê essas chaves, e há um teste que reprova se alguém
> passar a ler.

## Login social: quatro provedores (opt-in, um por um)

Um segundo caminho de entrada, ao lado da senha: os botões **Entrar com…** abaixo do formulário de
login dos três painéis. Cada provedor nasce **desligado**, é ligado **individualmente**, e ligado
faz uma coisa só — autenticar quem **já tem conta**.

| Provedor | Driver do Socialite | URI de redirecionamento | Como o kit confirma o e-mail verificado |
|---|---|---|---|
| **Google** | `google` | `/auth/google/callback` | campo `email_verified` no payload |
| **GitHub** | `github` | `/auth/github/callback` | o kit consulta `/user/emails` e exige `primary` + `verified` |
| **LinkedIn** | `linkedin-openid` | `/auth/linkedin-openid/callback` | campo `email_verified` do userinfo OpenID |
| **X** (antigo Twitter) | `x` | `/auth/x/callback` | o X só devolve `confirmed_email` — a presença é a prova |

**Facebook e Discord ficaram de fora**, e não por esquecimento. A seção
[Facebook e Discord: por que não estão aqui](#facebook-e-discord-por-que-não-estão-aqui) explica o
que faltaria para incluí-los.

### O que o login social faz, e o que ele deliberadamente não faz

Vale para os **quatro** provedores, sem exceção:

| | |
|---|---|
| **Autentica** quem já tem conta com o e-mail que o provedor devolve | ✅ sempre, quando ligado |
| **Cria** conta para quem não tem | ❌ só com o registro aberto ligado, que nasce desligado |
| Aceita e-mail **não verificado** no provedor | ❌ nunca — recusa e registra o motivo |
| Contorna o **segundo fator** | ❌ nunca — quem tem 2FA confirmado cai no desafio igual |
| Guarda token de acesso ou `refresh_token` | ❌ nada é gravado |
| Cria coluna nova em `users` | ❌ nenhuma; o vínculo é pelo e-mail verificado |

A linha que mais importa é a segunda, e ela não é timidez: **o convite é a única porta de entrada
do kit**. O exemplo que a documentação do Laravel Socialite dá para o callback é
`User::updateOrCreate()` — copiado para cá, ele transformaria qualquer pessoa com uma conta em
**qualquer** um dos provedores em usuária do sistema, contornando convite, verificação e atribuição
de papel. Isso é furo de autorização, não conveniência. Se você **quer** cadastro por login social,
ligue o registro aberto: o kit passa a criar a conta e a levar a pessoa para a tela do perfil dela,
onde ela completa o que falta.

E lembre do resto do kit: **conta sem papel não abre painel nenhum** (`User::canAccessPanel()`).
Quem entra por login social precisa de papel como qualquer outra pessoa.

### Ligando um provedor, em quatro passos

O roteiro é o mesmo para os quatro; só muda onde se cria o app OAuth. Você pode fazer tudo pelo
`.env` **ou** pela tela `/admin/configuracoes-do-kit` → aba **Login** (o valor gravado na tela vence
o `.env` em tempo de execução).

**1. Crie o app OAuth no provedor** e cadastre a URI de redirecionamento — que é o seu `APP_URL`
mais o caminho da tabela acima:

| Provedor | Onde criar | O que pedir lá |
|---|---|---|
| **Google** | [console.cloud.google.com](https://console.cloud.google.com) → *APIs e serviços* → *Credenciais* → *ID do cliente OAuth*, tipo **Aplicativo da Web** | nada além do padrão |
| **GitHub** | [github.com/settings/developers](https://github.com/settings/developers) → *OAuth Apps* → *New OAuth App* | nada a marcar; o kit pede o escopo `user:email` no código, e é ele que permite confirmar a verificação |
| **LinkedIn** | [linkedin.com/developers](https://www.linkedin.com/developers) → *Create app* → aba *Products* | **habilite o produto _Sign In with LinkedIn using OpenID Connect_**. Sem ele o provedor não devolve `email_verified`, e o kit recusa todo login |
| **X** | [developer.x.com](https://developer.x.com) → *Projects & Apps* → *User authentication settings* | tipo **Web App**, **OAuth 2.0**, e os escopos `users.read` e `users.email` |

Exemplo de URI a cadastrar:

```text
https://seu-dominio.com.br/auth/github/callback
http://localhost:8000/auth/github/callback     # para desenvolvimento
```

Esse caminho não é escolha: ele está em `config/services.php` como caminho **relativo**, de
propósito, para acompanhar o `APP_URL` de cada ambiente sem uma variável a mais para esquecer.

**2. Escreva as três chaves do provedor no `.env`:**

```dotenv
# Google
KIT_SOCIALITE_GOOGLE=true
GOOGLE_CLIENT_ID=1234567890-abc.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-seu-segredo

# GitHub
KIT_SOCIALITE_GITHUB=true
GITHUB_CLIENT_ID=Iv1.abc123
GITHUB_CLIENT_SECRET=seu-segredo

# LinkedIn (driver linkedin-openid)
KIT_SOCIALITE_LINKEDIN=true
LINKEDIN_CLIENT_ID=86abc123
LINKEDIN_CLIENT_SECRET=seu-segredo

# X (antigo Twitter)
KIT_SOCIALITE_X=true
X_CLIENT_ID=seu-client-id
X_CLIENT_SECRET=seu-segredo
```

**3. Limpe a config** (`php artisan config:clear`) e recarregue a tela de login.

**4. Confirme que o botão apareceu.** Se não apareceu, é uma das duas condições abaixo.

> **Pela tela, em vez do `.env`**: em `/admin/configuracoes-do-kit` → **Login** há uma seção por
> provedor. Ligar o interruptor **abre** os campos de *Client ID* e *Client Secret* daquele
> provedor — e só dele. O *Client Secret* é guardado **cifrado**, nunca é exibido de volta e não
> aparece no código-fonte da página; deixar o campo em branco **mantém** o que estava gravado.

### O botão só aparece com TUDO preenchido — e por provedor

São **duas** condições, em conjunção, e elas falham por motivos diferentes:

- o interruptor daquele provedor ligado — desligado é escolha de quem instalou;
- o `client_id`, o `client_secret` e o `redirect` **todos preenchidos** — credencial vazia é
  descuido de quem configurou.

Interruptor ligado com o `client_secret` vazio mantém o botão fora do ar, e é de propósito: botão
que leva a um OAuth inexistente é uma promessa que a tela não pode cumprir.

**E o desligamento derruba a ROTA, não só o botão.** Com um provedor indisponível,
`/auth/{provedor}/redirect` e `/auth/{provedor}/callback` respondem **404** — e só os dele, os
outros seguem no ar. Esconder o botão não seria barreira nenhuma: a URL é fixa, pública e conhecida.

**Provedor fora da lista responde 404 sem nem chegar ao controller.** `/auth/facebook/callback`,
`/auth/discord/callback` ou qualquer outro segmento devolvem 404 porque o parâmetro da rota é
tipado como `App\Support\ProvedorSocial` — a lista branca é o próprio enum, e o roteador a consulta.

Cada interruptor também **falha fechado**: `false`, `0`, `off`, `no`, vazio e qualquer valor
irreconhecível o mantêm desligado. Só `true` e `1` ligam.

### O rodapé da tela de login

A mesma configuração traz um rodapé de texto na base da tela de login dos três painéis:

```dotenv
KIT_LOGIN_RODAPE="Fiotec · Todos os direitos reservados"
```

Vazio (ou só espaços) = sem rodapé, sem faixa vazia.

É **texto, não HTML**, e o valor sai escapado. A tela de login é pública e não autenticada: HTML
cru vindo de um campo editável ali seria XSS armazenado com o pior alcance possível — a tela por
onde todo mundo entra. Se você precisar de link no rodapé, o caminho é um campo estruturado
(texto + URL, com validação), não um campo de HTML solto.

### E-mail não verificado: por que recusamos, e como cada provedor prova

O vínculo com a conta do kit é feito **pelo e-mail**, comparado sem diferenciar caixa nem espaços
nas bordas. Isso é simples e não custa coluna nova — mas tem um risco conhecido: se o provedor
devolvesse um e-mail **não verificado**, bastaria criar uma conta naquele provedor com o e-mail de
outra pessoa para entrar na conta dela. Com o registro do kit fechado — o default — esse é
justamente o caminho principal, não um caso de borda.

Então o kit **exige prova positiva** em todos os provedores. Ausente, falsa, ou com um valor que
não seja claramente verdadeiro ⇒ **recusa**, com aviso na tela e o motivo `email_nao_verificado` no
log. Falha fechado, sempre.

O que muda de provedor para provedor é **onde** a prova está, e a diferença é grande:

- **Google** — informa `email_verified` no payload (e um alias `verified_email`). O kit lê e exige
  verdadeiro.
- **LinkedIn** — o userinfo do OpenID Connect traz `email_verified`. É por isso que o kit usa o
  driver `linkedin-openid` e não o `linkedin` legado: o legado **não informa verificação nenhuma**,
  e os escopos dele foram descontinuados pela própria LinkedIn.
- **X** — não tem campo de verificação, porque não precisa: o X só devolve `confirmed_email`, ou
  seja, um endereço que ele já confirmou. **A presença do e-mail é a prova.** Se o X não devolver
  e-mail (app sem o escopo `users.email`, ou conta sem endereço confirmado), o kit recusa com o
  motivo `email_ausente`.
- **GitHub** — este é o caso interessante. O GitHub **verifica** e depois **descarta a evidência**:
  o Socialite consulta `/user/emails`, escolhe a entrada `primary` **e** `verified`, e guarda só a
  string do endereço. Pior, se aquela consulta falhar ele engole o erro e deixa no lugar o e-mail do
  **perfil público**. Ou seja, "e-mail não vazio" **não** é prova de verificação: é prova de que ou
  a verificação passou, ou a chamada falhou — e de fora os dois casos são idênticos.

  Por isso o kit **refaz a consulta** a `https://api.github.com/user/emails` com o token que acabou
  de receber, e exige uma entrada `primary: true` **e** `verified: true` cujo endereço case com o
  que veio. É uma requisição HTTP a mais por login, e é o que torna a garantia do GitHub igual à
  dos outros. Se a API do GitHub estiver fora, o login é **recusado** — a direção certa do erro — e
  o motivo (`github_emails_indisponivel`) vai para o log.

Consequência a conhecer, em todos: se a pessoa **trocar o e-mail** na conta do provedor, o vínculo
se perde e ela volta a entrar por senha.

### Limitação conhecida: o destino é sempre o painel `/app`

Os botões aparecem nas telas de login dos **três** painéis, porque o render hook é único. Mas quem
entra por login social cai sempre no `/app`, mesmo tendo clicado em `/admin/login` ou
`/infra/login` — e uma recusa também volta para o login do `/app`.

Não é furo de segurança: a pessoa é autenticada e o papel dela continua governando o que ela
alcança. É atrito de navegação, e está registrado como limitação aceita porque guardar o painel de
origem entre a ida e a volta do OAuth é feature nova, não conserto desta. Quem administra e quem
opera infra normalmente entra por senha; o login social existe para o caminho do `/app`.

### Facebook e Discord: por que não estão aqui

O requisito original pedia os dois. Nenhum entrou, e cada um por um motivo diferente.

**Facebook — não há como confirmar o e-mail.** O Socialite tem o driver, e ele funciona; o que não
existe é um campo que afirme que **aquele endereço** foi confirmado. O `verified` que o provider
pede é de nível de **conta**, legado, e ausente na versão da Graph API que ele usa; o caminho
OIDC/Limited Login devolve claims sem `email_verified`. Aceitar o Facebook faria o nível de garantia
do seu login depender de **qual botão a pessoa clicou** — e o botão mais fraco seria o vetor. Se
você aceitar esse risco conscientemente, o que falta é: um caso em `App\Support\ProvedorSocial` com
o ramo de verificação declarando a premissa, o bloco em `config/services.php` (chave `facebook`) e
em `config/kit.php`, e as três propriedades no Settings. **Leia o ADR-05 antes** — ele lista as
alternativas que foram consideradas e por que cada uma é pior.

**Discord — não é driver do Socialite.** A documentação oficial suporta Facebook, X, LinkedIn,
Google, GitHub, GitLab, Bitbucket e Slack; o resto vem do catálogo comunitário
[socialiteproviders.com](https://socialiteproviders.com). Incluí-lo exige
`composer require socialiteproviders/discord` **e** o registro de um listener de `SocialiteWasCalled`
— uma dependência nova e um segundo mecanismo de extensão. O kit não adiciona dependência por
conta própria; se você quiser, o caminho é esse mais um caso no enum (o Discord expõe um campo
`verified` no payload, então a barreira tem onde encostar).

### Onde ficam os registros

Tudo no channel **`autenticacao`** (`storage/logs/autenticacao-*.log`), no mesmo formato do resto
do kit — `[Classe@Método] mensagem | chave: valor`, com **e-mail mascarado**, o `provedor` em todas
as linhas e um `motivo` legível em cada recusa:

| `motivo` | O que aconteceu |
|---|---|
| `falha_no_provedor` | `state` de CSRF inválido, rede fora, ou credencial recusada pelo provedor |
| `email_ausente` | o provedor não devolveu e-mail (no X, é o caso de escopo faltando) |
| `email_nao_verificado` | o e-mail não está verificado no provedor |
| `github_emails_indisponivel` | a consulta a `/user/emails` do GitHub não respondeu 2xx |
| `github_email_nao_verificado` | nenhum e-mail `primary` + `verified` do GitHub casou com o recebido |
| `conta_inexistente_registro_fechado` | não há conta e o registro aberto está desligado |
| `conta_criada_por_login_social` | conta nova criada (registro aberto ligado) |

Nenhum **`client_secret` aparece** — não em log, não em tela, não em mensagem de erro, e não no
HTML da tela de configuração. E as mensagens devolvidas ao visitante são propositalmente genéricas:
dizer qual barreira reprovou é entregar informação de reconhecimento a quem estiver sondando. O
motivo fica no log, para você.

O login social também entra na **trilha de acesso** do painel `/infra` (quem entrou, quando, de
onde), como qualquer outro login — sem configuração nenhuma.

### O segredo do Google ficou em claro na trilha de auditoria até a v0.19.3

**Se você configurou o `GOOGLE_CLIENT_SECRET` pela tela `/admin/configuracoes-do-kit` em alguma
versão entre a 0.19.2 e a 0.19.3, rotacione esse segredo no console do Google.**

O motivo: a máscara de segredo da trilha de auditoria decide o que esconder consultando a lista
`ConfiguracoesDoKit::encrypted()`, e o `client_secret` do Google estava fora dessa lista. Então
cada gravação pela tela escreveu o valor **em claro** nas colunas `old_values`/`new_values` da
tabela `audits` — e a tela de auditoria exibe essas colunas para leitura.

O que esta versão faz por você:

- **corrige a lista**, o que fecha o vazamento daqui para a frente nos quatro segredos de provedor
  e na senha de SMTP, de uma vez (uma lista, três consumidores: o decifrador da leitura, o
  cifrador da gravação e a máscara da trilha);
- **mascara o que já está gravado**, numa migration que substitui o valor pela mesma máscara que a
  trilha usa hoje. A linha da trilha é preservada — quem alterou, quando e de onde continua
  registrado; sai só o valor que nunca deveria ter entrado;
- **avisa no log** (channel `configuracoes`) quantas linhas foram mascaradas, com a instrução de
  rotacionar.

Mascarar a trilha **não desfaz** o fato de o valor ter estado legível. Por isso a rotação é sua, e
é o único passo que o kit não pode fazer no seu lugar.

### Acrescentando o próximo provedor

O kit **tem** abstração de provedor agora, e ela é um enum: `App\Support\ProvedorSocial`. A decisão
foi tomada com quatro casos na mão, não com um — o que revelou que o eixo a abstrair não era o
redirect nem o botão (idênticos em todos), mas a **verificação de e-mail** (radicalmente diferente
em cada um).

O roteiro do quinto provedor, e é curto de propósito:

1. um caso novo no enum, com o `value` = **nome do driver do Socialite** (esse mesmo valor é o
   segmento da URL e a chave nos dois arquivos de config), mais os ramos de `rotulo()`, `icone()` e
   **`emailVerificado()`**;
2. um bloco em `config/services.php` e um em `config/kit.php` → `login`, e as chaves no
   `.env.example`;
3. três propriedades em `App\Settings\ConfiguracoesDoKit`, a linha de cada uma em
   `mapaDeConfiguracao()`, o `client_secret` em **`encrypted()`**, e o par `add`/`addEncrypted` numa
   migration nova em `database/settings/`;
4. uma partial de SVG em `resources/views/filament/auth/icones/`.

**Nenhum arquivo de lógica muda**: as rotas, o controller, o blade dos botões e a aba Login da tela
de Settings percorrem `ProvedorSocial::cases()`. E o `match` exaustivo de `emailVerificado()`
**cobra** o ramo novo — esquecê-lo não compila em análise estática.

**Se o provedor não permitir confirmar a verificação do e-mail, essa é uma decisão de arquitetura,
não um `?? true`.** Foi o que tirou o Facebook da lista. Registre a escolha antes de escrever o
ramo.

> O raciocínio completo, com as alternativas recusadas e o `file:line` do `vendor/` de cada
> afirmação sobre o Socialite, está em
> `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/`. A decisão anterior — a de
> **não** abstrair, com um provedor só — está em
> `wikis/specs/feat/login-social-google/login-social-google/`, ADR-10.

## Trilhas do `/infra`: exceções, e-mails e lixeira

O painel de infraestrutura já mostrava **saúde** (Health), **desempenho** (Pulse), **arquivo de
log** (Logs Explorer) e **filas** (Jobs Monitor) — e nenhum deles respondia "qual exception está
estourando, e quantas vezes", "o convite chegou?" ou "dá para desfazer aquele delete?". Três telas
respondem cada uma dessas perguntas:

| Tela | Onde | O que responde |
|---|---|---|
| **Exceções** | `/infra`, grupo *Observabilidade* | as exceptions agrupadas por tipo e frequência, com badge de contagem no menu |
| **Trilha de e-mails** | `/infra`, grupo *Trilhas* | todo e-mail que o kit enviou — separa "não foi enviado" de "foi enviado e caiu no spam" |
| **Lixeira** | `/infra`, grupo *Sistema* | restaura registro apagado com `SoftDeletes` |

### As duas trilhas guardam dado sensível

É por isso que elas vivem **só** no `/infra`, onde entrar já exige papel `master_global` ou
`infra` — no `/app` qualquer papel do painel as veria:

- o **stack trace** da exceção pode carregar parâmetro de request, logo pode carregar dado pessoal;
- o **corpo do e-mail** é gravado, e o convite de acesso carrega o link de aceite.

### Retenção: o número é a intenção, o agendador é a execução

As duas tabelas crescem por evento — um bug em laço enche o disco em horas. Por isso a poda tem
prazo, em `config/kit.php`:

| Chave | `.env` | Padrão |
|---|---|---|
| `kit.retencao.excecoes_em_dias` | `KIT_RETENCAO_EXCECOES_DIAS` | 14 |
| `kit.retencao.emails_em_dias` | `KIT_RETENCAO_EMAILS_DIAS` | 14 |

Os 14 dias acompanham o `days` da rotação em `config/logging.php`: a trilha morre junto com o log
que a originou, não depois dele. **Zero ou negativo desliga a poda** daquela trilha — e aí a tabela
cresce sem teto, o que é uma escolha, não um esquecimento.

> ⚠️ **Quem aplica a retenção é o agendador.** As rotinas estão em `routes/console.php`; sem
> `php artisan schedule:work` (ou o serviço `scheduler` do docker compose) o número no config é só
> intenção declarada.

### A Lixeira lista o que você declarar

O `RevivePlugin` recebe uma **lista explícita** de models em
`app/Providers/Filament/InfraPanelProvider.php` — hoje só `App\Models\Projeto`, a única model do
kit com `SoftDeletes`:

```php
RevivePlugin::make()
    ->navigationGroup('Sistema')
    ->navigationLabel('Lixeira')
    ->models([
        Projeto::class,
    ])
    ->withoutScoping(),
```

**Model nova com `SoftDeletes` precisa entrar nessa lista**, senão fica apagada sem tela para
restaurar. A varredura automática de `app/Models` foi evitada de propósito: alcançaria `User`,
`Role` e `Tenant`, cuja restauração tem consequência de **autorização** — um usuário volta com
papel numa organização que pode nem existir mais. A trava é a lista, como na allow-list do
Command Center.

## Multi-tenancy (opt-in)

O kit nasce **single-tenant**. Um comando liga o modo multi-tenant — e quem não precisa não paga nada por ele:

```bash
php artisan kit:tenancy          # liga o modo
php artisan kit:tenancy --demo   # liga + cria um cenário de demonstração
```

> O `--demo` também escreve `KIT_DEMO=true` no `.env`. É essa chave que faz o resource de exemplo
> **Projetos** aparecer no `/app` — sem ela o painel de negócio continua vazio, que é o desenho do
> kit. Para tirar a demo da vista sem apagar nada, `KIT_DEMO=false`; para removê-la de vez, apague
> os arquivos que o comando lista ao final.

| Painel | Com o modo ligado |
|---|---|
| **App** | vira `/app/{tenant}`. O usuário só enxerga os tenants a que está vinculado, e ganha a **administração da própria organização** |
| **Admin** | ganha o cadastro de tenants e o **vínculo de usuários** — não é escopado, quem administra vê todos |
| **Infra** | inalterado: saúde, filas e logs são da instalação, não de um cliente |

### Quem administra uma organização não administra a instalação

Os quatro papéis do kit, e o que cada um significa com o modo ligado:

| Papel | Painel | Contexto da atribuição | O que faz |
|---|---|---|---|
| `master_global` | todos | global | vence qualquer permissão, por `Gate::before` |
| `admin` | `/admin` | global | usuários, papéis e permissões da **instalação** |
| `infra` | `/infra` | global | saúde, filas, logs, auditoria, comandos |
| `admin_app` | `/app` | **a organização** | usuários e convites **da organização dele** |
| `panel_user` | `/app` | a organização | usa o negócio; não vê a administração |

`admin_app` é a persona que o modo multi-tenant cria: alguém que administra **uma** organização sem administrar o sistema. Dentro de `/app/{slug}` ele ganha **Usuários** e **Convites**, recortados àquela organização — e nada além disso. Ele não entra em `/admin` nem `/infra`, leva 404 no painel de outra organização, não alcança usuário de fora nem por URL direta, não cria nem edita papéis (só atribui, e só papéis do painel `/app`), não exclui usuário — o delete apagaria a pessoa de **todas** as organizações — e o convite que ele cria nasce carimbado com a organização dele, ignorando o formulário.

O papel só existe com a tenancy ligada, e a concessão é em `/admin` → organizações → **Usuários vinculados** → *Papéis nesta organização*. **Não** pelo cadastro do usuário: ali a atribuição vai para o contexto global e a pessoa entra no `/app` sem enxergar nada. A receita completa, com o sintoma, está em [`wikis/receitas.md`](wikis/receitas.md#promover-alguém-a-admin-de-uma-organização).

### Código em inglês, interface no seu idioma

O código segue o vocabulário da API do Filament — model `Tenant`, tabela `tenants`, `getTenants()`, `canAccessTenant()` — para que a documentação oficial se leia sem tradução mental. **O que o usuário vê é configurável**, e nasce como "Organização":

```php
// config/kit.php
'tenancy' => [
    'label'        => 'Empresa',    // Organização · Cliente · Escola · Unidade · Loja
    'label_plural' => 'Empresas',
    'slug'         => 'empresas',   // /admin/empresas
],
```

### Nas suas models

Toda model do negócio usa a trait do kit:

```php
use App\Traits\BelongsToTenant;

class Projeto extends Model
{
    use BelongsToTenant;

    protected $fillable = ['nome'];   // `tenant_id` fora: a trait preenche
}
```

Ela dá a relação `tenant()`, um **escopo global** e o preenchimento automático de `tenant_id`. O escopo importa porque o Filament só recorta o que passa por um Resource — job, comando, listener e API ficariam de fora, e é aí que dado de um cliente vaza para outro.

> ⚠️ **`kit:tenancy` recria o banco.** Ele liga `permission.teams`, e a migration do spatie só cria as colunas de tenant se a flag estiver ativa **antes** do migrate. Por isso exige árvore git limpa, confirmação explícita e roda `migrate:fresh --seed`. **A hora de rodar é o dia 1 do projeto.** O caminho detalhado — inclusive papéis globais × por tenant e `scopedUnique()` — está em [`wikis/arquitetura.md`](wikis/arquitetura.md#multi-tenancy-opt-in).

## Anexos e mídia

O `filament/spatie-laravel-media-library-plugin` entrega a camada de mídia — upload, coleções e
conversões — nos componentes de formulário, tabela e infolist do Filament. A model de demonstração
`App\Models\Projeto` mostra o desenho completo:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Projeto extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('anexos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('miniatura')
            ->nonQueued()   // sem garantia de worker no ar: enfileirada, a
            ->width(200)    // conversão só existiria com um worker de pé, e a coluna
            ->height(200);  // ficaria vazia sem erro nenhum
    }
}
```

E o `ProjetoResource` consome as duas pontas:

```php
SpatieMediaLibraryFileUpload::make('anexos'),   // no formulário

SpatieMediaLibraryImageColumn::make('anexos')   // na tabela
    ->simpleLightbox(),
```

O `->simpleLightbox()` funciona sem cola porque `SpatieMediaLibraryImageColumn` **estende
`ImageColumn`**, que é exatamente onde o macro do lightbox é registrado.

[![Listagem de Projetos no /app com a coluna de anexos: miniaturas circulares empilhadas na linha de cada registro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/app-projetos-anexos.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/app-projetos-anexos.png)

Repare nas miniaturas empilhadas na linha do registro: cada uma é servida por **URL assinada**,
porque o disco é privado — o mesmo arquivo pedido sem a assinatura responde 403.

**O escopo por organização vem de graça** — e é o ponto. A tabela `media` do Spatie é polimórfica:
o arquivo pertence ao registro, e o registro já é escopado por `BelongsToTenant`. Quem não alcança
o projeto não alcança o anexo, sem coluna de tenant em `media` e sem configuração para lembrar de
ligar.

> ⚠️ **O disco default da mídia é `local`, e é privado de propósito.** Com `MEDIA_DISK=public` o
> arquivo cai em `storage/app/public`, servido pelo symlink `public/storage`: caminho
> `/storage/{id}/{arquivo}`, ID sequencial, alcançável **sem sessão** — a multi-organização do
> Filament não chega ao sistema de arquivos. Use `public` só para avatar e logo, que aparecem na
> tela de login.
>
> Duas consequências práticas do disco privado:
>
> 1. **`Media::getUrl()` responde 403.** É falha fechada, e é o que se espera. Quem publica link de
>    mídia privada usa **`getTemporaryUrl()`**, que assina a URL.
> 2. **Quem tem o link entra, durante a validade da assinatura, sem sessão.** A rota
>    `storage.local` do Laravel valida a assinatura, não o usuário: compartilhar o link é
>    compartilhar o arquivo até ele expirar. Para anexo que precise de autorização por
>    organização, sirva por rota própria que consulte a policy antes de entregar.
>
> Já tem instalação rodando com `MEDIA_DISK=public`? A config nova protege só o arquivo NOVO.
> Rode **`php artisan kit:midia-privada`** (aceita `--dry-run`) para mover o que já foi gravado —
> sem ele, a mídia antiga continua servida pelo symlink.

## Import e export (CSV)

O mecanismo é **nativo do Filament 5**: `ImportAction`, `ExportAction`, os jobs, o batch e a
notificação de conclusão com botão de download. As tabelas `imports`, `exports` e
`failed_import_rows` já vêm migradas, e o kit **não escreve wrapper nenhum** em volta disso. O que
ele acrescenta são duas classes base, uma permissão própria para cada lado e a decisão — resource
por resource — de ligar ou não.

![Fluxo de import e export no /app: a listagem de Projetos com os botões no cabeçalho, o modal de exportação com um campo por coluna e o modal de importação com o CSV de exemplo](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/fluxo-import-export.gif)

Os dois botões vivem no cabeçalho da listagem, ao lado do "Novo": nada de tela nova, nada de rota
própria — o que muda de resource para resource é só a permissão que cada um exige.

### `ImportadorDoKit`: a fronteira de organização que o pacote não entrega

`Importer::resolveRecord()` roda **dentro do worker**. Lá não há painel nem rota na sessão, então
`Filament::getTenant()` devolve `null` e o escopo global de `BelongsToTenant` vira **no-op** — o
`ImportCsv` restaura o `auth()->setUser()`, o **usuário**, e nada restaura o tenant. Duas
consequências, as duas silenciosas:

| Linha do CSV | Sem `App\Support\ImportExport\ImportadorDoKit` |
|---|---|
| com chave de **outra** organização | UPDATE no registro alheio, sem 403 e sem log |
| nova | nasce com `tenant_id` **nulo** — invisível para todo mundo, inclusive para quem importou |

A correção tem duas pontas. A **Action** captura o tenant no request, onde ele existe
(`->options(['tenant_id' => Filament::getTenant()?->getKey()])`), e a classe base o usa nas duas
pontas: filtra a resolução do registro e preenche a criação, no lugar do hook `creating` que ali
não tem contexto.

E ela **falha fechada**: tenancy ligada + model que usa `BelongsToTenant` + nenhum `tenant_id` nas
options = a linha é **recusada** com `RowImportFailedException` (vai para `failed_import_rows`, sai
no CSV de falhas da notificação) e o motivo é logado. Seguir sem escopo seria exatamente o defeito
que a classe existe para fechar.

### `ExportadorDoKit`: fórmula neutralizada em toda coluna

`preventFormulaInjection()` existe no Filament **por coluna**, e nasce **desligado**. Uma célula
começando em `=`, `+`, `-` ou `@` vira fórmula quando alguém abre o CSV no Excel — e o dado que a
preencheu veio de formulário de usuário. `App\Support\ImportExport\ExportadorDoKit` aplica a
neutralização a **toda** coluna que a subclasse declarar; por isso a subclasse declara `colunas()`,
e não `getColumns()`.

**O export não tem uma linha de código de tenant, e é isso que interessa entender.** A query dele
vem da tabela da tela (`getTableQueryForExport()`), montada no request, onde o escopo global já
aplicou o `where tenant_id = X`; ela é serializada **com** esse `where` dentro, e é isso que o job
executa. O isolamento do export é **herdado**; o do import é **construído** — o inverso exato. O
raciocínio completo está em
[`wikis/arquitetura.md`](wikis/arquitetura.md#import-e-export-o-worker-perde-o-tenant-o-export-o-herda).

Os dois modais são os nativos do Filament — o kit não desenha tela nenhuma aqui:

| Importar | Exportar |
|---|---|
| [![Modal de importação de Projetos, com o link para baixar um CSV de exemplo e o campo de upload do arquivo](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/import-modal.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/import-modal.png) | [![Modal de exportação de Projetos, com um campo por coluna do exporter: Nome, Organização, Criado em e Atualizado em, cada um com checkbox e rótulo editável](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/export-modal.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/export-modal.png) |
| **Baixar um arquivo CSV de exemplo** monta o cabeçalho a partir das colunas do importer — é ali que se vê, na prática, que `tenant` não está entre elas | Um campo por coluna declarada em `colunas()`, com checkbox e rótulo editável: quem exporta escolhe o recorte e renomeia o cabeçalho, mas não acrescenta coluna que o exporter não declarou |

### Permissão própria, e ela não é opcional

`import` e `export` são **acréscimo do kit** aos 12 métodos default do Shield, em
`config/filament-shield.php` → `policies.methods` — e também em `single_parameter_methods`, porque
nenhum dos dois recebe registro (fora dessa lista o Shield geraria
`import(User $user, Model $record)` na policy, e a Action, que chama `Gate::authorize('import')` sem
registro, estouraria `ArgumentCountError`). Daí saem `Import:{Model}` e `Export:{Model}` para todo
resource.

[![Tela de edição de um papel no Filament Shield, com as checkboxes Import e Export ao lado de View Any, Create e Delete](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-papeis-import-export.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-papeis-import-export.png)

Na tela de papéis, `Import` e `Export` aparecem lado a lado com `View Any`, `Create` e `Delete` —
para **todo** resource, inclusive os que não ligaram as Actions. É o que permite conceder ou tirar
cada lado por papel, em `/admin` → Papéis, sem tocar em código.

Elas são necessárias porque **Action do Filament não consulta policy sozinha** — o próprio vendor
diz isso em `Actions/Concerns/CanBeAuthorized.php`: a autorização default é `null`, ou seja,
liberada. Por isso toda Action do kit carrega `->authorize('import')` ou `->authorize('export')`
explícito. Sem a linha, quem abre a listagem leva a listagem inteira embora.

> ⚠️ **Mexeu nesse config? Ressemeie.** A permission nova não existe no banco até o
> `shield:generate` rodar de novo, e o sintoma é a Action **desaparecer da tela sem erro nenhum**:
>
> ```bash
> php artisan db:seed --class=Database\Seeders\ShieldPermissionsSeeder
> php artisan db:seed --class=Database\Seeders\PapeisSeeder
> ```

### `panel_user` não nasce com nenhuma das duas

A subtração está em `PapeisSeeder::ehPermissaoDeImportOuExport()`, e casa por **prefixo de ação**
(`Import:` / `Export:`), não por lista de FQCN — de propósito: **resource novo nasce com as duas
fora do usuário comum sem ninguém precisar lembrar de acrescentá-lo a lista nenhuma.** O critério é
o que cada uma é de fato: import é **escrita em massa**; export **tira o dado da organização da
aplicação** num arquivo. Quem usa o negócio faz isso um registro por vez; quem move planilha é quem
opera a organização. O `admin_app` fica com as duas, porque recebe a matriz inteira do painel — e
conceder ao `panel_user` é um clique em `/admin` → Papéis, se fizer sentido no seu caso.

### Quem tem o quê hoje

| Painel | Resource | Import | Export | Motivo |
|---|---|---|---|---|
| `/app` | **Projeto** | ✅ | ✅ | resource de demonstração — é o exemplo de referência dos dois |
| `/admin` | **AgenteIa** | ✅ | ✅ | configuração, sem dado pessoal |
| `/admin` | **Tenant** | — | ✅ | criar organização por CSV pularia o provisionamento: papéis por tenant, primeiro administrador, identidade visual. Uma linha de planilha viraria uma organização que ninguém alcança |
| `/admin`, `/app` | **User** | — | 💤 comentado | a planilha sai com o e-mail de todo mundo que tem acesso; e import contornaria convite, verificação de e-mail e atribuição de papel — os três pilares do acesso no kit |
| `/admin`, `/app` | **Convite** | — | 💤 comentado | e-mail do convidado |
| `/admin` | **Role** | — | — | papel é identificador de código, não dado de planilha |
| `/infra` | **AiRun** | — | ✅ | ledger de custo; a pergunta que ele responde é "quanto gastamos" |

**Comentado** quer dizer que as duas linhas **já estão** no arquivo da Page, comentadas, com o aviso
do que ligar expõe — o exporter existe pronto, é descomentar uma linha. A decisão nasce **escrita**,
e não esquecida: é a convenção que `.ai/rules/filament.md` cobra de todo resource novo, porque
ausência silenciosa não é decisão — ninguém volta para reavaliar o que nunca foi escrito.

### As colunas que faltam de propósito

O gerador do Filament infere as colunas do banco, e três delas o kit tira na mão. Não as devolva:

| Classe | Coluna ausente | O que ela entregaria |
|---|---|---|
| `ConviteExporter` | `token`, `token_lembrete` | `Convite::aceitar()` valida o token e vincula o usuário à organização com o papel do convite: um CSV com essa coluna é uma **planilha de chaves de entrada** |
| `AiRunExporter` | `request`, `response` | prompt e resposta completos, de qualquer organização — e o `/infra` não tem tenant na rota |
| `ProjetoImporter` | `tenant` | o gerador cria `ImportColumn::make('tenant')->relationship()` para toda FK; aceitá-la deixaria o **CSV escolher a organização de destino** e tornaria a fronteira do `ImportadorDoKit` decorativa |

O gerador recoloca todas elas em `--force`. Quem guarda a ausência são os testes de
`tests/Kit/ImportExportTest.php`.

### Sem worker, nada acontece

Import e export do Filament são **jobs**. O kit nasce com `QUEUE_CONNECTION=database` no `.env`;
`composer dev` já sobe um worker, e em produção quem processa é o serviço `worker` do docker
compose. Com a fila parada, o arquivo é aceito, a linha entra em `imports`/`exports` e a notificação
de conclusão nunca chega — fila parada aparece no **Jobs Monitor** do `/infra`.

### Rastro: sem tabela nova

`imports` e `exports` já guardam quem pediu, qual importador, quantas linhas e quando terminou. O
que **não** está lá é justamente o que uma auditoria de vazamento pergunta — **de qual organização
saiu o arquivo** —, porque as duas tabelas são do pacote e não têm `tenant_id`. É o que
`KitServiceProvider::configureRastroDeImportExport()` acrescenta, no channel **`tenancy`**: o
assunto é cruzamento de organização.

Os dois lados usam gancho diferente porque o pacote é assimétrico: o import tem eventos de verdade
(`ImportStarted` / `ImportCompleted`), o export **não tem nenhum**, então o gancho é o próprio model
`Export` — `created` marca o pedido e o `completed_at` recém-preenchido marca a conclusão.

### Retenção: 30 dias, e a do export apaga o arquivo

| Chave | `.env` | Padrão |
|---|---|---|
| `kit.retencao.importacoes_em_dias` | `KIT_RETENCAO_IMPORTACOES_DIAS` | 30 |
| `kit.retencao.exportacoes_em_dias` | `KIT_RETENCAO_EXPORTACOES_DIAS` | 30 |

**30, e não os 14 das trilhas de exceção e e-mail**: o histórico de uma escrita em massa é o que
responde "quem escreveu isso na semana passada", e essa pergunta costuma chegar depois do fechamento
do mês. `failed_import_rows` cai por cascata; **a poda do export apaga o ARQUIVO**, não só a linha —
sem isso o disco cresce para sempre com CSV que ninguém mais consegue baixar, porque o link de
download é assinado e a linha que o autorizava já foi.

Os dois agendamentos estão em `routes/console.php` (02:20 e 02:30), como `Schedule::call` e não como
`model:prune`: os models `Import` e `Export` do Filament **usam a trait `Prunable` mas não declaram
`prunable()`**, então o comando estouraria `LogicException` — e não há como acrescentar o método sem
editar o `vendor/`. É o mesmo padrão já usado na poda da trilha de e-mails. Zero ou negativo desliga
aquela poda, e **quem executa é o agendador**: sem `php artisan schedule:work` (ou o serviço
`scheduler` do compose) o número no config é só intenção.

### Ligar num resource novo

```bash
php artisan make:filament-importer Produto -G
php artisan make:filament-exporter Produto -G
```

Troque o `extends Importer` / `extends Exporter` gerado pelas classes base do kit (no exporter,
renomeie `getColumns()` para `protected static function colunas()`), **apague a coluna `tenant`** do
importer, e acrescente as Actions no `getHeaderActions()` da Page de listagem:

```php
ImportAction::make()
    ->importer(ProdutoImporter::class)
    ->authorize('import')
    ->options(fn (): array => ['tenant_id' => Filament::getTenant()?->getKey()]),

ExportAction::make()
    ->exporter(ProdutoExporter::class)
    ->authorize('export'),
```

Depois **ressemeie os dois seeders** (`ShieldPermissionsSeeder`, então `PapeisSeeder`) e confira que
há worker no ar. A receita completa, inclusive o que fazer quando a decisão é *não* ligar, está em
[`wikis/receitas.md`](wikis/receitas.md#ligar-importexport-num-resource).

## Trabalhando com agentes de IA

O kit já vem preparado para você desenvolver com um agente de código (Claude Code, Codex, Cursor, Junie, OpenCode) — e, mais importante, com a **documentação que o agente precisa ler** para não reinventar nem quebrar o que já está pronto.

### 📚 `wikis/` — a documentação do kit

**[`wikis/README.md`](wikis/README.md) é o ponto de entrada.** É onde mora tudo que um agente (ou uma pessoa nova no time) precisa saber antes da primeira linha de código:

| Documento | O que responde |
|---|---|
| [`wikis/arquitetura.md`](wikis/arquitetura.md) | três painéis, a "cola" do kit, ciclo de um request, os três níveis de autorização |
| [`wikis/convencoes.md`](wikis/convencoes.md) | as regras inegociáveis e as **armadilhas já resolvidas** — o documento que evita o "conserto" que quebra |
| [`wikis/ia.md`](wikis/ia.md) | agente como dado, guardrails fail-closed, ledger de execuções |
| [`wikis/receitas.md`](wikis/receitas.md) | passo a passo: Resource, página, widget, health check, comando, agente |
| [`wikis/agentes-e-skills.md`](wikis/agentes-e-skills.md) | Boost, MCP, as skills instaladas e o trio de execução |
| [`wikis/pacotes.md`](wikis/pacotes.md) | qual pacote é dono de qual tela — para não reimplementar vendor |

É também a pasta onde **você** escreve o que for do seu projeto: `wikis/specs/{branch}/{feature}/` recebe uma pasta por feature, criada pela skill abaixo.

### As skills instaladas

O [Laravel Boost](https://github.com/laravel/boost) está configurado (`boost.json`) para cinco agentes, com servidor MCP (`php artisan boost:mcp`) e nove skills sincronizadas — entre elas `laravel-best-practices`, `pest-testing`, `ai-sdk-development`, `tailwindcss-development`, `pulse-development`, `laravel-backup` e `blaze-optimize`.

A que muda o fluxo de trabalho é a **[`feature-wiki`](https://github.com/gsferro/laravel-ai-skills)**: invocada **antes** de implementar qualquer feature, ela cria `wikis/specs/{branch}/{feature}/` com plano de ação (PRD), decisões arquiteturais (ADR), progresso e casos de teste — além de fixar o padrão de log do projeto.

> 💡 **Feature nova? Chame `/feature-wiki`.** É o primeiro passo, antes de qualquer `php artisan make:*`. A skill pesquisa o código, escreve o plano e só então começa a implementação. Para typo, ajuste de config, refactor puro ou bump de dependência, pule — ela mesma diz quando não vale a pena.

No Claude Code ela trabalha com dois plugins já habilitados em `.claude/settings.json`, cada um cobrindo uma camada diferente:

| Camada | Ferramenta | Papel |
|---|---|---|
| Comunicação | [Caveman](https://github.com/JuliusBrussee/caveman) | resposta enxuta — **não** se aplica a wiki, código, commits e avisos de segurança |
| Planejamento | [feature-wiki](https://github.com/gsferro/laravel-ai-skills) | PRD + ADR + casos de teste + tracking |
| Execução | [Ponytail](https://github.com/DietrichGebert/ponytail) | mínimo código que funciona — sem cortar validação, segurança ou tratamento de erro |

```bash
php artisan boost:add-skill gsferro/laravel-ai-skills   # a skill
php artisan boost:update                                # sincroniza para todos os agentes
```

> `AGENTS.md` e `CLAUDE.md` são **gerados** pelo Boost — editar à mão é trabalho perdido no próximo `boost:update`. Regra durável vai em `.ai/rules` (ferramenta `record-rule`) ou na `wikis/`.

#### Caveman e Ponytail fora do Claude Code

O trio acima só é trio de verdade se as três camadas existirem. No Claude Code, Caveman e
Ponytail chegam como **plugin** (`.claude/settings.json`) — com ativação automática por hook e
comandos no namespace `/ponytail:…` e `/caveman:…`. Nos outros agentes não há sistema de plugin,
e a `feature-wiki` invocaria um `/ponytail-review` que não existe.

Por isso o kit **versiona uma cópia** das três skills que a `feature-wiki` cita por nome, em
`.agents/skills/`, `.ai/skills/` e `.junie/skills/`:

| Skill | Para quê a `feature-wiki` usa |
|---|---|
| `ponytail` | a escada de simplicidade durante a implementação (step 7) |
| `ponytail-review` | auditoria do plano contra over-engineering (step 6, obrigatório) e do diff no fim |
| `caveman` | comunicação enxuta agent ↔ você; **não** vale para wiki, código, commit ou aviso de segurança |

Duas consequências práticas:

- **A invocação muda de nome.** No Claude Code é `/ponytail:ponytail-review`; nos demais agentes,
  a cópia local responde por `/ponytail-review`, sem namespace.
- **`.claude/skills/` fica de fora de propósito.** Copiar para lá criaria duas `ponytail` ativas
  ao mesmo tempo — a do plugin e a do projeto.

`boost:update` **não** apaga essas pastas: ele só remove skill que já rastreou e saiu do
`boost.json`, e nenhuma das três está listada lá. São cópias MIT, com o `LICENSE` original junto —
atualizar é recopiar do upstream ([Caveman](https://github.com/JuliusBrussee/caveman),
[Ponytail](https://github.com/DietrichGebert/ponytail)).

### O ciclo de uma feature com agente

O kit não pede que você confie no agente: pede que ele **deixe rastro**. Cada etapa produz um
arquivo que a etapa seguinte confere.

| # | Você faz | O agente produz | Por que existe |
|---|---|---|---|
| 1 | `/feature-wiki` com o pedido em texto corrido | `wikis/specs/{branch}/{feature}/00-requisito.md` — **cópia imutável** do que você pediu | O requisito nunca é reescrito para caber no que foi implementado. É ele que julga a entrega |
| 2 | lê e ajusta | `01-plano-acao.md` (PRD passo a passo), `02-decisoes.md` (ADR), `04-casos-de-teste.md`, e `05-…-browser.md` quando tem tela | Revisar plano é barato; revisar 900 linhas de diff, não |
| 3 | aprova | auditoria automática do plano por `ponytail-review` | Corta passo desnecessário e abstração prematura **antes** de virar código |
| 4 | — | implementação seguindo o plano, com `03-progresso.md` atualizado | Sessão que cai retoma de onde parou, sem reconstruir contexto |
| 5 | — | testes rodando (`--parallel --tia`) | Verde é pré-condição do passo seguinte, não a entrega |
| 6 | — | `/feature-quality-gate` → `06-relatorio-qa.md` | Confronta requisito × plano × app rodando. A **matriz de rastreabilidade** expõe a cláusula que nunca virou passo, teste nem código — a omissão que suíte verde não denuncia |
| 7 | aprova | `/requirement-to-rule` → regra em `.ai/rules` | Decisão que vale além desta feature passa a valer para **toda sessão futura**, de qualquer agente |

**O que isso muda na prática:**

- **O agente lê antes de escrever.** `wikis/` e `.ai/rules` respondem o que já existe, e o
  [roteiro de features](#roteiro-de-features) abaixo lista as 61 telas prontas. Feature
  reimplementada do zero porque o agente não sabia que existia é o custo mais caro e mais invisível.
- **Contexto vira arquivo, não histórico de chat.** Trocar de agente, de máquina ou de pessoa não
  perde o porquê da decisão — ele está no ADR, versionado no mesmo commit do código.
- **Simples por padrão, sem cortar o que importa.** Ponytail nunca simplifica validação em fronteira
  de confiança, tratamento de erro que evita perda de dado, segurança ou acessibilidade.
- **Menos token por resposta.** Caveman corta a prosa da conversa; wiki, código e commit continuam
  em português normal.
- **Cada correção fica.** Armadilha resolvida vira `.ai/rules` — e o gate seguinte já a verifica.
  Quando dá para provar por `pest --arch`, PHPStan ou Rector, a regra aponta para o teste em vez de
  pedir boa vontade.

> Para typo, ajuste de `.env`, bump de dependência ou refactor puro, **pule o ciclo**. A skill
> mesma diz quando não compensa — cerimônia em mudança de uma linha é o over-engineering que o
> Ponytail existe para cortar.

## Roteiro de features

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

### Acesso e autenticação

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
| F-06 | Bloqueio de sessão | menu do usuário → *Bloquear sessão* | autenticado | trava sem deslogar; volta com a senha. Usa o layout do login, não a `SimplePage` | 🟢 |
| F-07 | Meu perfil, avatar e senha | `/{painel}/meu-perfil` | autenticado | edita nome, e-mail, senha e avatar | 🔵 |
| F-08 | Impersonate | `/admin/users` → ação na linha | `master_global` | entra como outro usuário e volta pela faixa no topo | ⚪ |

### Autorização

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

### Convites

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-15 | Convite individual | `/admin/convites` · `/app/{org}/convites` | `admin`, `admin_app` | e-mail + papel + organização; o link vai por e-mail com token de uso único | 🟢 |
| F-16 | Convite para quem **já tem conta** | mesmo lugar | idem | vira *oferta de acesso*: a pessoa entra com a senha que já tem e é vinculada | 🟢 |
| F-17 | Caixa de convites recebidos | menu do usuário → *Convites recebidos* | qualquer autenticado | aceitar **ou recusar**; a recusa fica registrada | 🟢 |
| F-18 | Convite em massa | header da listagem | `admin`, `admin_app` | cole N endereços; um com problema **não** derruba os outros, e o resumo diz por quê | 🟢 |
| F-19 | Lembretes automáticos | `kit:convites-lembrar` (cron 08:00) | — | D+3 e D+5, com um **segundo link paralelo**; o original continua valendo | 🟢 |
| F-20 | Reenviar / revogar | ação na linha | `admin` | reenviar **mata** os links anteriores; revogar apaga e fica em `/infra/audits` | 🟢 |

### Multi-tenancy (opt-in)

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

### Administração

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-29 | Usuários | `/admin/users` | `admin` | CRUD, com papel **obrigatório** no cadastro | 🟢 |
| F-30 | Catálogo de agentes de IA | `/admin/agentes-ia` | `admin` | prompt, provider, modelo, tools e guardrails são **dados**, editáveis sem deploy | 🟢 |
| F-31 | Autoria de onboarding | `/admin/onboarding-flows` | `admin` | checklists e tours; o consumo fica no painel de negócio | 🔵 |
| F-32 | Dashboard preenchido | `/admin` | `admin` | 6 widgets sobre os dados que o painel já tem | 🔵 |

### Infraestrutura

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

### Produtividade e UI

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

### IA

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-53 | Chat do assistente | canto de **toda** tela do `/app` | autenticado | streaming; renderiza vazio sem usuário | ⚪ |
| F-54 | Guardrails encadeados | — | — | budget, prompt injection, classificador local, redação de PII e filtro de saída. **Fail-closed** | 🟢 |
| F-55 | Ledger de execuções | `/infra/execucoes-ia` | `infra` | toda chamada vira linha com custo e tokens | 🟢 |
| F-56 | Inferência local | `docker compose --profile ai up -d` | — | llama.cpp; ou troque `AI_PROVIDER` por um SaaS | ⚪ |

### O que o roteiro **não** cobre sozinho

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

## Requisitos

- PHP 8.3+ e Composer 2
- Node 20+ (opcional — sem ele a instalação segue e avisa como fazer o build depois)
- Docker (opcional — só para Postgres, Redis, IA local e e-mail)

## Banco de dados

**A instalação pergunta** — SQLite, PostgreSQL ou MySQL. O padrão é **SQLite**, para não depender de nada.

**PostgreSQL é o recomendado**, e por um motivo funcional: ele é o único que traz `pgvector`, de que dependem as funções de IA local que usam busca semântica (embeddings). Com SQLite ou MySQL o resto do kit roda igual — só essas funções ficam indisponíveis.

Escolhendo Postgres na instalação, o `.env` já sai com o bloco que o `docker-compose.yml` lê, e falta só subir o container. Se ele não estiver de pé na hora da instalação, o kit avisa, **pula as migrations** e diz o comando para refazer:

```bash
docker compose up -d
php artisan migrate --seed
```

Para trocar depois da instalação, suba os containers e copie as variáveis:

```bash
docker compose up -d              # pgsql (com pgvector) + redis
# copie o bloco de banco de .env.docker para o seu .env
php artisan migrate --seed
```

## Docker

Tudo é opt-in por profile. Um container por feature:

```bash
docker compose up -d                            # pgsql + redis
docker compose --profile ai up -d               # + llama.cpp (chat e embeddings)
docker compose --profile mail up -d             # + mailpit (1025 / 8025)
docker compose --profile full up -d             # infra completa
docker compose --profile app up -d --build      # a aplicação containerizada
docker compose --profile realtime up -d reverb pulse
```

| Serviço | Porta | Profile |
|---|---|---|
| PostgreSQL 17 + pgvector | 5432 | base |
| Redis 7 (só cache) | 6379 | base |
| llama.cpp (chat) | 8080 | `ai` |
| llama.cpp (embeddings) | 8081 | `ai` |
| Mailpit | 1025 / 8025 | `mail` |
| App (nginx + php-fpm) | 8000 | `app` |
| Reverb (WebSocket) | 8090 | `app`, `realtime` |

O Reverb usa 8090 e não o default 8080 para não colidir com o llama.cpp.

## Comandos

```bash
composer dev          # servidor + fila + vite juntos
composer test         # pint + phpstan + filacheck + a suíte inteira
composer test:kit     # só os testes do kit (a fundação), em paralelo
composer lint         # formata o código
composer filament:check   # só o lint específico de Filament (FilaCheck)
composer refactor:preview # o que o Rector reescreveria (dry-run) — FORA do composer test
composer refactor:apply   # aplica a reescrita do Rector — FORA do composer test
php artisan kit:install --force   # reinstala do zero (APAGA o SQLite) e refaz as perguntas
php artisan kit:install --custom   # refaz só nome e cor, sem tocar no banco
php artisan kit:install --no-custom   # instala sem perguntar nada
php artisan kit:admin             # troca e-mail e senha do administrador (pede confirmação)
php artisan kit:update            # traz melhorias de uma versão nova do kit
php artisan kit:tenancy           # liga o modo multi-tenant (opt-in)
```

### FilaCheck: o lint que só entende de Filament

`composer filament:check` roda o `laraveldaily/filacheck` — 17 regras que o Pint e o PHPStan não
têm como ter: método depreciado da API do Filament, namespace errado de action, chamada que mudou
entre versões. Ele entra no `composer test` junto com o pint e o phpstan, então a CI reprova o
mesmo que a sua máquina.

Ao ser adotado, ele encontrou **7 problemas preexistentes** no próprio kit — seis métodos de teste
depreciados e um `ImageColumn::size()` — todos corrigidos.

### Rector: upgrade de major, não lint

O kit tem **quatro** ferramentas de qualidade, em quatro eixos — e só **três** estão no gate:

| Ferramenta | Eixo | Ao achar problema | Roda |
|---|---|---|---|
| **Pint** | estilo | **corrige** | sempre (gate) |
| **PHPStan** + larastan | tipos | reporta | sempre (gate), **level 7** |
| **FilaCheck** | API do Filament | reporta | sempre (gate) |
| **Rector** | reescrita de código | **muda semântica** | **sob demanda** |

`composer refactor:preview` e `composer refactor:apply` **não** estão no `composer test` — e isso é
deliberado.

**Para que o Rector serve aqui: upgrade de major.** Laravel 13 → 14, PHP 8.4 → 8.5. O `rector.php`
da raiz nasce **sem nenhum set ligado**, e traz, num bloco de comentário, qual set ligar em cada
caso. O fluxo é: descomentar o set → `composer refactor:preview` → ler o diff inteiro →
`composer refactor:apply` → `composer test` → desligar o set de novo.

**Por que ele fica fora do gate — foi medido, não opinado.** Com os sets de qualidade do Laravel
ligados, o Rector reescreveria **103 arquivos** deste projeto. Os três maiores motivos:

| Regra | Arquivos | O que propõe |
|---|---:|---|
| `EloquentMagicMethodToQueryBuilderRector` | 35 | `User::find()` → `User::query()->find()` |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 26 | `: void` em closure |
| `AppToResolveRector` | 21 | `app()` → `resolve()` |

São opinião de estilo, não correção. Num kit cujo produto **é o código-exemplo legível**,
`User::find()` e `app()` são o idioma que o ecossistema lê sem parar.

E há um caso que fecha a questão. `CarbonToDateFacadeRector` propõe, no `InfraPanelProvider`:

```diff
- Carbon::now()->subDays(...)
+ Date::now()->subDays(...)
```

E isso **quebra**, por três fatos verificáveis:

1. `now()` **é** `Date::now()` — `Illuminate/Foundation/helpers.php:623`
2. O kit faz `Date::use(CarbonImmutable::class)` — `KitServiceProvider.php:57`
3. `FilamentExceptionsPlugin::modelPruneInterval()` exige `Carbon` **mutável**

O PHPStan level 7 **já reportou exatamente esse erro** quando o código usava `now()`. O
`Carbon::now()` explícito é a correção — e o Rector a desfaria.

> **Ferramenta de qualidade que reverte a correção de outra não é gate, é disputa** — e o build
> passaria a depender de qual das duas rodou por último.

`tests/Kit/QualidadeDeCodigoTest.php` fixa isso: falha se o Rector entrar no `composer test`, ou se
um set de qualidade for ligado.

**Upgrade de Filament é outra ferramenta.** **Não existe regra de Filament no
`driftingly/rector-laravel`** — busca por "filament" no pacote devolve zero. Não é lacuna: o
Filament distribui a **própria** ferramenta, também baseada em Rector.

```bash
composer require filament/upgrade --dev -W
vendor/bin/filament-vN     # N = major de destino
```

Ela é mantida em lockstep com o framework — quem escreve as regras é quem quebra a API.

A leitura completa das quatro ferramentas está em
[`wikis/qualidade-de-codigo.md`](wikis/qualidade-de-codigo.md).

### Os testes do kit

O kit traz sua própria suíte, isolada em `tests/Kit/` — acesso aos três painéis, telas de infra e admin de pé, invariantes da fundação (uuid, gates, auditoria) e o contrato da camada de IA.

Ela fica separada da sua de propósito: depois de um `kit:update` você quer saber se a **fundação** continua íntegra, sem esperar a suíte do seu negócio.

```bash
composer test:kit                     # em paralelo — ~3 min
composer test:kit:serial              # em série, para investigar falha
php artisan test --testsuite=Feature  # só os SEUS testes
```

**Roda em paralelo por padrão.** Medido nesta suíte: **12m26s → ~3min** (20 núcleos), mesmos casos e
mesmas asserções. Cada worker tem o próprio banco, porque o `phpunit.xml` usa SQLite `:memory:`, que
é por processo.

Se uma falha aparecer só em paralelo, é sinal de teste que depende de ordem ou de estado
compartilhado — `composer test:kit:serial` isola isso, e a diferença entre os dois é o diagnóstico.

> **Por que `--testsuite` e não `--group=kit`**: o `pest-plugin-browser` sobe o Playwright já na
> **coleta**, ao parsear qualquer arquivo com `visit()` — antes de qualquer filtro de grupo ser
> consultado. Num projeto recém-instalado, sem os browsers baixados, `--group=kit` morre em
> `PlaywrightNotInstalledException` sem rodar um único teste.

> **Argumento extra precisa de `--`**: `composer test:kit --parallel` é engolido em silêncio pelo
> Composer; o que funciona é `composer test:kit -- --parallel`. Como o paralelo já é o padrão, você
> não precisa disso — mas vale saber para qualquer outra flag.

Seus testes vão em `tests/Feature` e `tests/Unit`, como de costume — o kit não encosta neles.

### As imagens do README saem de um teste

As capturas de tela deste README **não são feitas à mão**. Elas nascem de
`tests/BrowserTenancy/CapturaDeArteTest.php`, na mesma suíte que prova que as telas funcionam:

```bash
composer art
```

O comando navega de verdade, salva os PNG, publica em `art/`, gera as thumbs de `art/thumbs/` e
monta o GIF do fluxo. É o único jeito que encontramos de a documentação não envelhecer: ninguém
refaz quinze imagens a cada release, e o resultado é um README mostrando uma versão do kit que
não existe mais.

| Etapa | O que faz |
|---|---|
| `npm run build` + `view:cache` | pré-requisitos duros da suíte de navegador |
| `KIT_ART=1 pest tests/BrowserTenancy/CapturaDeArteTest.php` | navega e escreve os PNG em `tests/Browser/Screenshots/` (caminho fixo do plugin) |
| `php artisan kit:arte` | copia para `art/`, redimensiona as thumbs e monta o GIF |

Três decisões que valem saber antes de mexer:

- **`KIT_ART=1` não é enfeite.** Sem a variável o arquivo é *skipped*. Ele escreve em `art/`, e uma
  suíte de CI que suja a árvore de trabalho é pior que uma suíte lenta.
- **As medidas são fixas: 1400x875 no cheio, 760x475 na thumb.** É a proporção das imagens que já
  estavam no `art/`, e a galeria põe duas thumbs por linha — thumb com outra proporção desalinha a
  tabela.
- **O GIF é slideshow, montado com `ffmpeg` a partir de três quadros.** O plugin de navegador não
  grava vídeo, e quadro capturado é o que dá para reproduzir de forma determinística. Sem `ffmpeg`
  no PATH o comando avisa e segue: as imagens estáticas já foram publicadas.

Precisa só refazer as thumbs, sem repetir a navegação? `php artisan kit:arte --sem-gif`.

### Como os testes são pensados: varredura SFDIPOT

Toda feature nova passa por uma varredura **SFDIPOT** antes de virar caso de teste. A heurística, criada por James Bach, divide o sistema em sete perspectivas para que nenhuma dimensão seja esquecida na especificação:

| Letra | Perspectiva | O que cobre |
|---|---|---|
| **S** — Structure | Estrutura | Código, arquivos, componentes físicos ou lógicos |
| **F** — Function | Função | O que o software faz, suas funcionalidades |
| **D** — Data | Dados | O que o sistema processa, armazena ou manipula |
| **I** — Interfaces | Interfaces | Telas, APIs, integrações, entradas e saídas |
| **P** — Platform | Plataforma | Sistema operacional, hardware ou ambiente onde roda |
| **O** — Operations | Operações | Como o usuário ou administrador usa o sistema no dia a dia |
| **T** — Time | Tempo | Concorrência, desempenho, histórico ou a sequência dos eventos |

O benefício está em não derivar os testes só do "caminho feliz". O que escapa raramente é mais um caso a mais — geralmente é uma dimensão inteira (dados, plataforma, tempo, operações) que ninguém lembrou de cobrir. A varredura força essa revisão no plano, antes do código existir.

## Personalize seu projeto

**Os cinco primeiros o instalador já pergunta** (ver [a instalação](#starter-kit-easy)) — a lista abaixo é para mudar depois, ou para quem pulou as perguntas.

| # | O quê | Onde | Perguntado na instalação? |
|---|---|---|---|
| 1 | **Nome** | `APP_NAME` no `.env` | ✅ |
| 2 | **Banco de dados** | bloco `DB_*` no `.env` | ✅ |
| 3 | **Credenciais do seeder** | `KIT_ADMIN_EMAIL` / `KIT_ADMIN_PASSWORD` no `.env` | ✅ |
| 4 | **Cor primária** | `KIT_COR_PRIMARIA` no `.env` (nome de uma cor da paleta do Filament) | ✅ |
| 5 | **[Multi-tenancy](#multi-tenancy-opt-in)** | `php artisan kit:tenancy`, e o termo exibido em `config/kit.php` → `tenancy.label` | ✅ |
| 6 | **Arte do login** | `public/images/auth/login.svg` | — |
| 7 | **Acesso aos painéis** | o papel de cada usuário (`/admin` → Papéis, campo *Painel*); a regra que o lê é `App\Models\User::canAccessPanel()` | — |
| 8 | **Matriz de permissões** | `database/seeders/PapeisSeeder.php` | — |
| 9 | **Health checks** | `KitServiceProvider::configureHealthChecks()` | — |
| 10 | **Comandos da UI** | `config/command-center.php` | — |
| 11 | **Backups** | destino e agenda em `config/backup.php` | — |
| 12 | **Agente de IA** | `/admin` → Agentes de IA (ou `database/seeders/AssistenteSeeder.php`) | — |
| 13 | **[Idiomas do painel](#o-seletor-de-idioma)** | `config/kit.php` → `idiomas` (lista de locales; com um só, o seletor não aparece) | — |
| 14 | **[Retenção das trilhas](#retenção-o-número-é-a-intenção-o-agendador-é-a-execução)** | `KIT_RETENCAO_EXCECOES_DIAS` / `KIT_RETENCAO_EMAILS_DIAS` no `.env` | — |
| 15 | **[Disco da mídia](#anexos-e-mídia)** | `MEDIA_DISK` no `.env` (`local` por padrão — privado, servido por URL assinada) | `php artisan kit:midia-privada` migra a mídia já gravada em disco público |
| 16 | **[Import e export CSV](#import-e-export-csv)** | a Action em cada `app/Filament/**/Pages/List*.php` (ligada ou comentada); a permissão em `config/filament-shield.php` → `policies.methods`; a retenção do histórico em `KIT_RETENCAO_IMPORTACOES_DIAS` / `KIT_RETENCAO_EXPORTACOES_DIAS` no `.env` | ressemeie `ShieldPermissionsSeeder` + `PapeisSeeder` depois de mexer no config |

Os onze últimos não entram nas perguntas porque são **código ou dado de tela**, não um valor que caiba num prompt de terminal. O instalador os lista no resumo final, com o arquivo de cada um.

> ⚠️ O item 5 é o único que **não** é "edite um arquivo" depois de instalado: o `kit:tenancy` roda `migrate:fresh --seed` e **apaga os dados**. Ele exige árvore git limpa e confirmação explícita. **Respondido na instalação, ele não apaga nada** — o banco ainda nem existe, e é essa a hora certa de decidir.

> A cor primária vale para os três painéis. Com o [modo multi-tenant](#multi-tenancy-opt-in) ligado, a cor de cada organização **vence** esta dentro de `/app/{slug}` — o `/admin` e o `/infra` continuam com a do projeto. Para uma paleta completa, e não só a `primary`, o caminho continua sendo `->colors([...])` em cada `app/Providers/Filament/*PanelProvider.php`.

## Configuração global do Filament

Um único arquivo define como **toda** tabela, toggle, modal e coluna do projeto se comporta: `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (aplicado pelo `KitServiceProvider`). Mudou ali, mudou em todo lugar — inclusive nas telas dos plugins de terceiros, que você não conseguiria editar de outro jeito.

**Toda tabela nasce com:**

| Comportamento | Por quê |
|---|---|
| `deferLoading()` | a tela aparece antes da query terminar |
| `striped()` + `stackedOnMobile()` | leitura em lista no desktop, cartão no celular |
| `persistFilters/Search/Sort/ColumnSearchesInSession()` | o recorte do usuário sobrevive à navegação |
| `reorderableColumns()` + `dragReorderableColumns()` + `stickableColumns()` | colunas reordenáveis, arrastáveis e fixáveis |
| **colunas redimensionáveis** (`asmit/resized-column`) | largura ajustável pelo usuário, preservada na sessão |
| `filtersLayout(Modal)` + `filtersFormColumns(2)` + `deferFilters()` | com 3+ filtros o dropdown vira rolagem; o modal não |
| `defaultPaginationPageOption(10)` + `extremePaginationLinks()` | paginação previsível, com atalhos de primeira/última |
| `deselectAllRecordsWhenFiltered(false)` | filtrar não descarta a seleção |

Também são globais: modal que **não** fecha no Esc (um toque acidental descartaria o formulário), toggles com cor e ícone de estado, coluna de ícone booleana com check/x colorido, `CreateAction` com ícone padrão e o alternador de painéis.

> **Colunas redimensionáveis em telas novas:** o comportamento padrão já vale para qualquer tabela; para que a largura escolhida seja **lembrada**, a página de listagem precisa do trait:
>
> ```php
> use Asmit\ResizedColumn\HasResizableColumn;
>
> class ListProdutos extends ListRecords
> {
>     use HasResizableColumn;
> }
> ```

> **Quatro desses defaults são editáveis em [Configurações do kit](#configurações-do-kit-em-admin)**, na aba *Tabelas*: linhas por página, linhas listradas, persistência do recorte e colunas arrastáveis. O resto continua sendo decisão de código, de propósito — são escolhas com motivo escrito, não preferência de gosto.
>
> ⚠️ **Densidade de tabela não existe no Filament 5** e por isso não está na tela. O TODO antigo daqui prometia os quatro itens, e um deles não tem API: varredura em `vendor/filament/tables/src` não devolve nenhuma ocorrência de `density`, e `vendor/filament/tables/src/Enums/` traz sete enums, nenhum de densidade. O que o framework oferece de controle visual de aperto é o `striped()`, e é ele que ficou configurável.

## Configurações do kit em `/admin`

O que a instalação perguntou — e mais um punhado de coisas que antes só se mudava editando arquivo — vive em **`/admin/configuracoes-do-kit`**, em quatro abas. Nada de `.env`, nada de deploy.

| Aba | O que você troca |
|---|---|
| **Identidade** | nome da aplicação, cor primária (a paleta do Filament **ou** um hexadecimal livre), logo da marca, favicon e a arte das telas de autenticação |
| **E-mail** | transporte (`log`, `array`, `smtp`), servidor, porta, criptografia, usuário, senha e remetente |
| **Tabelas** | linhas por página, linhas listradas, persistência do recorte do usuário e colunas arrastáveis — os defaults de **toda** tabela dos três painéis |
| **Kit** | hub de navegação em cartões, e como o seu negócio chama cada organização (singular e plural) |

Tudo é gravado pelo `spatie/laravel-settings` na tabela `settings`, com a tela vindo do `filament/spatie-laravel-settings-plugin` — os dois já estavam instalados no kit e sem uso até esta versão.

### Quem manda: o banco ou o `.env`?

Esta é a pergunta que decide se a tela é útil ou decorativa, e a resposta é uma só:

> **O banco vence em tempo de execução. O `.env` semeia a primeira gravação e é o plano B.**

Como isso funciona sem que nenhum consumidor saiba que o settings existe:

1. A migration `database/settings/*_create_kit_settings.php` semeia cada propriedade com o valor **de `config(...)`**, que vem do `.env`. Numa instalação nova, a cor e o nome que você escolheu no `kit:install` chegam ao banco sozinhos — o `migrate` roda depois de o instalador ter escrito o arquivo.
2. `App\Providers\KitServiceProvider::configureSettingsDoKit()` sobrepõe a configuração do processo com o que está no banco, uma vez por request e por comando artisan.
3. `App\Support\CorPrimaria`, os três `PanelProvider`, a configuração global de tabela e o próprio `MailManager` do Laravel continuam lendo `config()`. Nenhum deles foi alterado.

O que acontece em cada situação:

| Situação | Quem vence |
|---|---|
| a propriedade tem linha no banco | **o banco** |
| a propriedade não tem linha (você acrescentou uma e não migrou) | o `.env`, com um `warning` no log |
| a tabela `settings` não existe (antes do primeiro `migrate`) | o `.env`, em silêncio |
| o banco está inacessível | o `.env`, com um `warning` |
| `kit:install` numa instalação nova | o `.env` → a migration leva os valores para o banco |
| `kit:install --force` | apaga o banco, reescreve o `.env` e re-migra → o banco nasce igual ao `.env` novo |
| `kit:install --custom` num projeto já instalado | reescreve o `.env` **e** grava no settings — as duas fontes ficam iguais |

**Não existe interruptor para "usar ou não o settings"**, e isso é decisão, não esquecimento: uma flag seria uma terceira fonte da verdade, que é justamente o problema que a regra acima resolve. Para desligar, `php artisan migrate:rollback` na migration de settings — sem linha na tabela, o alinhamento é no-op e o `.env` volta a ser a única fonte.

### Cor: lista fechada e cor livre

São dois campos, e a precedência é declarada:

**hexadecimal válido → nome da paleta → padrão do Filament.**

O hexadecimal vence porque é o campo mais específico: quem digita `#7c3aed` escolheu aquela cor, enquanto o seletor da lista tem valor padrão e pode nunca ter sido tocado. Valor fora do formato (`#abcd`, `azul`, `#gggggg`) é **ignorado** e a resolução cai para o nome — a mesma tolerância que o kit já tinha para nome de cor inválido, e pelo mesmo motivo: isto roda no boot de todo painel, e uma exceção ali derrubaria **toda** página do projeto, não uma tela.

Dentro de `/app/{organização}`, a cor da **organização** continua vencendo as duas.

### Permissão

Uma só: **`View:ConfiguracoesDoKit`**, gerada pelo `ShieldPermissionsSeeder` e entregue ao papel `admin` pelo `PapeisSeeder` — sem nenhuma lista para editar, porque a matriz do papel é a do painel inteiro. `master_global` entra pelo `Gate::before`; `infra` e `panel_user` não recebem.

É uma permissão para abrir **e** para salvar, de propósito. O `canEdit()` do plugin desabilita o formulário mas **não esconde valor** — o próprio README do pacote diz isso por escrito —, e esta tela guarda a senha do SMTP. Um papel "só leitura" aqui seria um papel que lê credencial.

### Teto de upload: 10 MB, e onde mudar

Todo upload do kit — a logo, o favicon e a arte do login desta tela, a logo da organização em
`/admin/organizacoes` e os anexos de Projeto — aceita arquivo de **até 10 MB**, e **recusa SVG**.

O número é **uma** chave, no `.env`:

```dotenv
# Em MEGABYTES. Vazio, 0 ou ausente = 10.
KIT_UPLOAD_MAXIMO_MB=10
```

Ela alimenta `config('kit.uploads.maximo_em_kb')` — a config guarda **kilobytes**, porque é a
unidade que o `->maxSize()` do Filament e a regra de upload temporário do Livewire recebem. A
multiplicação por 1024 vive num lugar só, no `config/kit.php`, e quem lê a chave é
`App\Support\TetoDeUpload`. Não há campo na tela para isto de propósito: é decisão de
instalação, não de operação diária.

**Um upload atravessa quatro limites, e o menor manda.** Eles não recusam igual, e é isso que
torna o desalinhamento caro:

| Camada | Onde | Valor no kit | Como aparece o erro |
|---|---|---|---|
| nginx | `docker/nginx/nginx.conf` | `client_max_body_size 60M` | falha de rede no console |
| PHP | `docker/php/uploads.ini` | `upload_max_filesize=52M`, `post_max_size=60M` | idem |
| Livewire (upload temporário) | alinhado à chave do kit por `KitServiceProvider`, com 1 MB de folga | 11 MB | 422 no XHR, erro genérico |
| Filament (`->maxSize()`) | a chave do kit | 10 MB | **mensagem em português, no campo** |

Só a última recusa com mensagem clara — por isso o kit alinha o Livewire à chave em vez de deixar
o default dele (12 MB) mais frouxo que a tela.

**Para subir muito o teto**, mude junto:

1. `KIT_UPLOAD_MAXIMO_MB` — cobre a tela e o Livewire de uma vez;
2. acima de 52 MB, `docker/php/uploads.ini` (`upload_max_filesize` e `post_max_size`);
3. acima de 60 MB, `docker/nginx/nginx.conf` (`client_max_body_size`).

⚠️ **Fora do Docker do kit, o PHP costuma vir com `upload_max_filesize=2M` de fábrica.** Ali o
teto real é 2 MB, não o da chave — e o erro aparece como falha de rede, sem mencionar tamanho.
Confira com `php -i | grep upload_max_filesize` antes de culpar o kit.

### Por que SVG é recusado

SVG é XML, e XML aceita `<script>`. A logo, o favicon e a arte do login são servidos pelo
**mesmo origin** da aplicação, com visibilidade pública: abrir a URL de um SVG enviado executaria
o script com acesso ao cookie de sessão — XSS armazenado. Quem envia é o `admin`, que já tem
acesso total, então é escalada de insider e não porta anônima; num starter kit vale fechar.

A barreira é a regra `mimes` do **Laravel** (não o `->image()` do Filament, que é outra coisa e
aceita `image/*`, SVG incluído), com a lista de formatos em
`ConfiguracoesDoKit::FORMATOS_DE_IMAGEM`: jpg, jpeg, png, gif, bmp, webp, avif, heic, heif, **ico**,
**tif** e **tiff**. SVG é o único formato de imagem fora, e é o único que carrega script.

E ela **não** olha a extensão: o MIME vem do conteúdo do arquivo no disco temporário, então
renomear `logo.svg` para `logo.png` não passa. Nos anexos de Projeto, onde uma allow-list fecharia
o campo para PDF e planilha, a regra recusa apenas `image/svg+xml`.

### Trilha de alterações

Toda alteração aparece em **`/infra/audits`**, com quem mudou, quando, o nome da propriedade e os valores antigo e novo. Uma linha por propriedade alterada; salvar sem mudar nada não gera registro.

A senha de e-mail é **cifrada** na tabela `settings` e entra na trilha **mascarada** (`••••••`): o registro diz que o segredo mudou, nunca qual é.

Dois detalhes que valem para quem for mexer nisso:

- A trilha **não** vem da trait `App\Traits\AuditsFillables`. Um settings do spatie não é um model Eloquent, e apontar o repositório dele para um model com a trait auditaria só a **criação** — a alteração de propriedade existente passa por `upsert()`, que não dispara evento de Eloquent. A trilha sai de um listener de `SavingSettings`, que é o único ponto do pacote com valor antigo e novo juntos.
- O evento gravado é `settings-updated`, e não `updated`, para o botão "restaurar" da trilha **não** aparecer: ele faria `fill(['nome_da_aplicacao' => …])` numa linha cujas colunas são `group`/`name`/`payload`.

### Isto não é o settings de uma organização

A identidade visual de um tenant (cor e logo por organização) continua sendo CRUD comum em **`/admin/organizacoes`**, nas colunas `cor_primaria` e `logo` do model `Tenant`, e ela vence a do kit dentro de `/app/{slug}`. Nada foi movido para cá.

### O que ficou fora, e por quê

| Item | Por quê |
|---|---|
| driver, host e nome do **banco** | trocar depois do `migrate` não é reescrita de configuração, é outra instalação |
| ligar/desligar a **multi-organização** | as tabelas de permissão só nascem com a coluna de contexto se `permission.teams` estiver ativo **antes** do migrate; o caminho é `php artisan kit:tenancy` |
| **e-mail e senha do administrador** | o `UsuarioAdminSeeder` não sincroniza, de propósito (ele roda em todo `db:seed`, e atualizar senha ali reverteria em silêncio a troca feita no perfil). Um campo que não troca a credencial é pior que campo nenhum — o caminho é a tela de perfil |
| **slug** do CRUD de organizações | é lido no registro de rota, não no render, e a URL é identificador permanente |
| **idiomas** do painel | a internacionalização do kit não está feita: ligar um segundo idioma hoje troca metade da tela. Ver o bloco `idiomas` de `config/kit.php` |
| **retenção** das trilhas | não é pergunta da instalação; fica no `.env`, onde o zero tem semântica documentada |

### Desempenho

O alinhamento custa **uma** query por boot (o grupo inteiro vem de uma só leitura). Se isso incomodar, `SETTINGS_CACHE_ENABLED=true` no `.env` — lembrando que, com o cache ligado, gravar pela tela exige `php artisan settings:clear-cache`.

### Acrescentando uma propriedade

Três lugares, sempre, e o teste `tests/Kit/ConfiguracoesDoKitTest.php` reprova se você esquecer um:

1. a propriedade tipada em `app/Settings/ConfiguracoesDoKit.php`;
2. a linha em `ConfiguracoesDoKit::mapaDeConfiguracao()` (propriedade → chave de `config()`);
3. o par `add()` / `deleteIfExists()` numa migration nova em `database/settings/`.

E o campo na aba certa de `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`.

## Convenções do kit

- **UUID nas rotas, `id` int como PK.** Toda tabela nova ganha `$table->uuid('uuid')->unique()` e o model usa `App\Traits\TemUuid`. URL com id numérico devolve 404 e ninguém enumera registros por sequência. UUID não é autorização — policies continuam obrigatórias.
- **Auditoria no que é editável.** `App\Traits\AuditsFillables` audita exatamente o `$fillable`, sem vazar colunas técnicas para a trilha.
- **Seeder nunca usa factory nem faker.** `fakerphp/faker` é `require-dev` e a imagem Docker roda `--no-dev`.
- **Permissões vêm de seeder, não de `shield:generate` interativo** — é o que permite instalar sem intervenção. O `ShieldPermissionsSeeder` gera para os **três** painéis (o comando do Shield só enxerga o painel corrente); o `PapeisSeeder` recorta a matriz por painel e entrega aos papéis. Depois de criar Resources novos, rode os dois (veja [abaixo](#depois-de-criar-seus-resources)).
- **Acesso a painel é dado do papel**, na coluna `roles.painel` — não uma lista de nomes no código. Papel sem painel não abre painel nenhum: o default fecha.
- **Nada de affordance sem permissão.** Menu, busca e ações consultam `canAccess()`/`canCreate()` antes de aparecer. Encontrar algo que resulta em 403 é considerado bug.
- **Tradução de plugin vai em `lang/vendor/`.** Vários pacotes só trazem inglês; o kit traduz sem tocar no vendor.

### Armadilhas já resolvidas

Coisas que custaram tempo para descobrir e que o kit já entrega prontas — se você mexer nelas, saiba o porquê:

| Onde | O quê |
|---|---|
| Lockscreen | precisa estar registrado nos **três** painéis: o `routes/web.php` do pacote resolve o plugin pelo painel corrente e estoura `LogicException` em todo request — até `artisan package:discover` morre |
| Tela de bloqueio | é uma `SimplePage` e ignora o layout do Auth Designer. `App\Filament\Pages\Auth\TelaBloqueio` a veste com o layout do login (bind em `AppServiceProvider`), **redeclarando `$layout`** — a trait do pacote atribui a propriedade estática, e sem a redeclaração o layout de login vaza para toda página Filament do processo |
| "Bloquear sessão" no menu | o item que o pacote registra nasce sem `sort` e cai depois do alternador de tema; o kit o substitui num `bootUsing()` com `sort(-1)` (no corpo de `panel()` não funciona: plugin boota antes, e quem registra por último vence) |
| Command Center | **sem** `->cluster()`: com cluster a página raiz devolve 500 |
| `databaseNotifications()` | declarado **depois** de `plugins()`, senão o Notification Center apaga o recorte, sem erro nenhum |
| Dependency Graph | `canAccessUsing()` substitui a regra local-only do pacote (sem ele, 404 em homologação) |
| Logs Explorer | `deletable(false)`: o delete do pacote faz `@unlink()` sem gravar rastro |
| Ações de filtro | **fora** do `configureUsing()` global: em tabela sem filtro a ação nasce sem nome e derruba a página |
| Pulse + resized-column | os dois bundles declaram constantes no escopo global; carregados como ES module para o segundo não morrer calado |
| Busca ⌘K | gatilho no hook `GLOBAL_SEARCH_BEFORE` (o `USER_MENU_BEFORE` renderiza dentro do dropdown) e overlay aberto em `setTimeout`, senão o próprio clique fecha o painel |

## Depois de criar seus Resources

```bash
php artisan make:filament-resource Produto --panel=app
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

**Os dois, nesta ordem, sempre.** O primeiro roda `shield:generate --all` em **cada** painel e escreve as policies; o segundo recorta a matriz pelo painel em que o Resource está registrado e devolve as permissões aos papéis. Só o primeiro cria a permission e não a entrega a ninguém — a tela continua em 403 para quem não é `master_global`. Os dois são idempotentes: rodar de novo é operação normal.

### Page, Widget e Action novos

Resource é o caso fácil: os dois seeders resolvem. As outras três famílias exigem uma linha de código,
porque os defaults do Filament são **permissivos** — o vendor diz isso em comentário, em
`Pages/Concerns/CanAuthorizeAccess.php` (`canAccess()` retorna `true`), em `Widget.php` (`canView()`
retorna `true`) e em `Actions/Concerns/CanBeAuthorized.php` (autorização default `null`, liberada).

O Shield **gera** `View:{Page}` e `View:{Widget}` por descoberta, o `PapeisSeeder` **entrega** aos
papéis do painel e a tela de papéis **mostra** o checkbox — mas nada disso faz a permissão ser
consultada. Sem o trait, desmarcar o checkbox não muda nada.

```php
// Page de painel nova
use App\Filament\Concerns\ExigePermissaoDaTela;

class MinhaPage extends Page
{
    use ExigePermissaoDaTela;

    // Regra local (flag de config, tenancy) vai NO HOOK, nunca sobrescrevendo canAccess():
    protected static function regraLocalDeAcesso(): bool
    {
        return (bool) config('kit.minha_flag');
    }
}

// Widget novo
use App\Filament\Concerns\ExigePermissaoDoWidget;

class MeuWidget extends StatsOverviewWidget
{
    use ExigePermissaoDoWidget;

    // Checagem de fonte opcional vai NO HOOK, nunca sobrescrevendo canView():
    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(fn (): bool => Schema::hasTable('minha_tabela'), false);
    }
}
```

> ⚠️ **Sobrescrever `canAccess()`/`canView()` na classe desliga a permissão em silêncio.** Método de
> classe vence método de trait, sem erro e sem aviso. É por isso que os dois concerns publicam um
> **hook** para a regra local, e por isso `tests/Kit/PermissoesDeTelasTest.php` e
> `PermissoesDeWidgetsTest.php` têm um caso que percorre TODAS as classes e reprova a que não consulta.

**Action** é declaração explícita, porque o Shield não descobre Action nenhuma:

| A Action é de… | A permissão nasce em | E na Action |
|---|---|---|
| Resource (tabela, header, RelationManager) | `config('filament-shield.resources.manage')` no Resource daquele painel | `->authorize('MinhaAcao:MeuModel')` |
| Page | `config('filament-shield.custom_permissions')` **e** `PapeisSeeder::paineisDasPermissoesCustomizadas()` | `->authorize('MinhaAcao:MeuModel')` |

A segunda linha tem duas metades porque `custom_permissions` **não conhece painel**: sem o mapa do
seeder, a chave nova cai em `admin`, `infra`, `admin_app` **e `panel_user`**. Chave sem entrada no
mapa não vai para papel nenhum (fail-closed) e o caso `CT-19` de
`tests/Kit/PermissoesDeAcoesTest.php` fica vermelho nomeando a chave.

> ⚠️ **Em RelationManager, nem a Action NATIVA está coberta.** `AttachAction`, `DetachAction`,
> `AssociateAction` e `DissociateAction` só checam `isReadOnly()` — o comentário está no
> `getDefaultActionAuthorizationResponse()` do vendor. No kit, o vínculo `tenant_user` que a
> `AttachAction` cria é exatamente o que `User::canAccessTenant()` consulta para liberar
> `/app/{slug}`, então as duas levam `->authorize()`.

**Page e Widget de vendor ficam fora**: são classes de pacote, sem ponto de extensão. A permissão
delas existe no banco e no checkbox, e **não é consultada** — a barreira é `canAccessPanel()` mais os
gates nomeados de `KitServiceProvider` (`ver-logs`, `command-center:access`, `viewPulse`,
`ver-ai-tasks`).

> **RelationManager o Shield não enxerga.** A descoberta dele cobre apenas Resources, Pages e Widgets, então nenhuma permission é gerada e a autorização recai na **policy do model relacionado**. Se esse model já tem Resource em algum painel, não há nada a fazer. Se não tem, crie a policy à mão (`php artisan make:policy`) e declare as chaves em `config('filament-shield.custom_permissions')` **antes** de rodar os seeders — do contrário o RelationManager fica aberto a qualquer um que consiga abrir o Resource pai.

Adicione os dois traits do kit ao que foi gerado:

```php
// No Resource — badge de contagem animado no menu:
use App\Filament\Concerns\BadgeContagemNavegacao;

class ProdutoResource extends Resource
{
    use BadgeContagemNavegacao;
}

// Na List page — lembra a largura das colunas escolhida pelo usuário:
use Asmit\ResizedColumn\HasResizableColumn;

class ListProdutos extends ListRecords
{
    use HasResizableColumn;
}
```

### Badges de contagem

Todos os Resources **do kit** já têm badge no menu (Usuários, Agentes de IA, Execuções de IA). A contagem sai de `getEloquentQuery()`, nunca de `Model::count()`: a query do resource carrega os escopos que valem para aquele painel, e contar direto no model mostraria um número que a listagem não confirma. Zero não vira badge — um "0" cinza em todo item só polui.

Resources de **plugins de terceiros** (Auditoria, Logins, Filas, Pacotes do Composer, Comandos, Papéis do Shield, Onboarding) ficam sem badge: `getNavigationBadge()` é um método estático do resource, e o Filament não oferece API para sobrescrevê-lo de fora — a `ResourceConfiguration` do painel só permite trocar o slug. Dar badge a eles exigiria estender cada resource de vendor e impedir o plugin de registrar o seu, o que quebra a cada atualização do pacote. Se algum for importante no seu projeto, o caminho é esse — resource por resource, conscientemente.

## Atualizando um projeto que já nasceu do kit

**O kit é um ponto de partida, não uma dependência.** Depois do `create-project` o projeto é seu: você renomeia painéis, muda `canAccessPanel()`, edita seeders. Por isso **não existe** um `kit:update` que sobrescreve arquivos — ele reescreveria justamente o que você personalizou, e um starter kit que estraga o projeto do usuário não serve para nada.

O que muda separa-se em três camadas, e cada uma tem um caminho próprio:

| Camada | O que é | Como atualizar |
|---|---|---|
| **Dependências** | Filament, plugins, Laravel | `composer update` — é a maior parte das melhorias e chega sozinha |
| **Cola do kit** | providers, traits, widgets, views de erro | diff manual contra a tag nova (abaixo) |
| **Seu negócio** | tudo que você escreveu | nunca é tocado |

### O jeito fácil: `php artisan kit:update`

O comando automatiza a etapa do git inteira e **não aplica nada sem sua aprovação**:

```bash
php artisan kit:update --dry-run   # só mostra o que mudou
php artisan kit:update             # revisa e aplica, arquivo a arquivo
```

O que ele faz, em ordem:

1. **Confere o terreno** — exige repositório git com a árvore limpa. Sem isso não haveria como reverter, e ele recusa rodar (mostrando os comandos para versionar o projeto).
2. **Vincula o kit temporariamente** — adiciona o remote `kit` com **push bloqueado** e busca as tags num namespace próprio (`kit-v*`), para não colidirem com as versões do seu projeto.
3. **Compara** — da versão em `config('kit.version')` até a tag escolhida, restrito aos caminhos que pertencem ao kit. Seu código de negócio nunca entra na conta.
4. **Oferece um branch temporário** (`kit-update/v0.16.0`) para não sujar o seu.
5. **Pergunta arquivo a arquivo** — ver o diff, aplicar, pular ou parar. Dá para mudar de ideia no meio e aplicar o resto em lote. Arquivo removido do kit nunca é apagado automaticamente: ele só avisa.
6. **Desfaz o vínculo** — remove o remote e as tags `kit-*` ao sair, mesmo se você interromper no meio. O projeto não fica com nada de terceiros pendurado.

7. **Marca a versão aplicada** em `config/kit.php` — só aquela linha, sem tocar no resto do arquivo. É o ponto de partida da próxima comparação.

Dois detalhes que aparecem na prática:

- **`config/kit.php` sempre consta como "modificado"** (ele carrega a marca de versão). Aplicá-lo traz as chaves novas do kit, mas **substitui o arquivo inteiro** — se você mudou credenciais do seeder ou adicionou chaves próprias ali, veja o diff e copie só o que interessa em vez de aplicar.
- **O próprio `kit:update` se atualiza.** Como o PHP já carregou a classe em memória, o comportamento novo (e as mensagens novas) só valem a partir da execução seguinte. O comando avisa quando isso acontece.

Ao final nada está commitado: você revisa com `git diff`, roda `composer test:kit` (a fundação) e commita. Deu errado? `git checkout -- .` desfaz, ou apague o branch e volte para o seu.

**Não precisa aprovar 30 arquivos um a um.** Durante a revisão, o menu oferece *"Aplicar todos os arquivos NOVOS daqui em diante"* e *"Aplicar TUDO daqui em diante"* — uma confirmação vale para o conjunto. E dá para começar já em lote:

```bash
php artisan kit:update --only-new   # só o que ainda não existe no projeto
php artisan kit:update --all        # tudo, inclusive o que sobrescreve
```

A distinção é o ponto: **arquivo novo não tem o que sobrescrever**, então aplicá-los em massa é seguro — é o caso dos widgets, do Spotlight e das concerns. Já um **modificado** substitui o conteúdo atual, e se você editou aquele arquivo a sua versão se perde (recuperável com `git checkout -- <arquivo>`, já que nada é commitado). Por isso `--only-new` é o lote recomendado para a primeira passada, deixando os modificados para revisar com calma.

| Opção | Para quê |
|---|---|
| `--only-new` | aplica de uma vez só os arquivos novos (não sobrescreve nada) |
| `--all` | aplica tudo de uma vez, com uma confirmação para o conjunto |
| `--dry-run` | só o relatório, não altera nada |
| `--tag=v0.16.0` | comparar com uma versão específica |
| `--from=v0.15.0` | dizer de qual versão o projeto partiu (quando `config/kit.php` não sabe) |
| `--branch=nome` | escolher o nome do branch temporário |
| `--no-branch` | aplicar no branch atual |
| `--keep-remote` | manter o remote e as tags do kit ao final |

Sem terminal (CI, `--no-interaction`) o comando vira relatório e não altera nada — a menos que você passe `--only-new` ou `--all`, que **são** a aprovação, dada na linha de comando.

### O jeito manual

Se preferir controlar cada passo — ou entender o que o comando faz por baixo:

Adicione o kit como um **segundo remote**, uma única vez. Seu `origin` continua sendo o seu projeto; o `kit` é só uma fonte de leitura:

```bash
git remote add kit https://github.com/gsferro/filament-starter-kit-easy.git

# o remote do kit é somente-leitura: evita um `git push kit main` acidental
# mandar o SEU projeto para dentro do repositório do kit
git remote set-url --push kit no_push
```

As tags do kit vão para um namespace próprio (`kit-v*`). Isso importa: um `git fetch kit --tags` traria `v0.15.0`, `v0.16.0`… para o seu projeto e colidiria com as **suas** versões depois.

```bash
git fetch --no-tags kit 'refs/tags/*:refs/tags/kit-*'
git tag -l 'kit-*'      # kit-v0.15.0, kit-v0.16.0, ...
```

Depois, a cada versão, veja o que mudou e traga só o que interessa:

```bash
# 1. panorama entre a sua versão e a nova
git diff kit-v0.15.0..kit-v0.16.0 --stat

# 2. o diff da "cola" do kit (ignore o que você já reescreveu)
git diff kit-v0.15.0..kit-v0.16.0 -- app/Providers app/Filament/Concerns \
        app/Filament/Spotlight app/Traits resources/views/errors config/kit.php

# 3. traga arquivo a arquivo, revisando
git checkout kit-v0.16.0 -- resources/views/errors
git checkout kit-v0.16.0 -- app/Filament/Concerns/BadgeContagemNavegacao.php
```

Faça isso num branch (`git switch -c atualiza-kit`) e rode `composer test` antes do merge. Arquivos que você reescreveu: leia o diff e aplique à mão — é o único caminho seguro.

> 💡 **TODO / rumo do projeto:** extrair a "cola" para um pacote Composer próprio (`gsferro/kit-core`) com os providers, traits, widgets e páginas de infra. Aí a camada do meio vira `composer update gsferro/kit-core` e o skeleton fica mínimo — só o que é mesmo ponto de partida. É a evolução natural deste kit.

## Solução de problemas

- **403 em todos os painéis, logo depois de autenticar** — o usuário não tem papel nenhum, ou o papel dele está sem painel declarado (`roles.painel` vazio não é coringa: não abre nada). Dê o papel em `/admin` → Usuários, ou preencha o campo *Painel* em `/admin` → Papéis.
- **`/infra` ou `/admin` dando 403** — seu usuário precisa de um papel cujo painel seja esse (`master_global`, `admin` ou `infra`), e com a tenancy ligada o papel tem de estar atribuído no contexto global. A tela de 403 mostra qual permissão faltou, mas **só fora de produção**: em produção ela não revela papéis nem permissões.
- **Assets do Filament sumidos** — `php artisan filament:assets`.
- **Pulse sem dados** — falta o daemon: `php artisan pulse:check` (ou o serviço `pulse` do compose).
- **Sininho não atualiza em tempo real** — `BROADCAST_CONNECTION=reverb` exige o processo Reverb no ar; sem ele o kit cai para polling de 30s.
- **Assistente de IA indisponível** — suba `docker compose --profile ai up -d` (o primeiro boot baixa ~4,5 GB de modelo) ou troque `AI_PROVIDER` para um provider SaaS com API key.

## Pacotes instalados

Tudo abaixo já vem instalado, publicado e registrado nos painéis — não existe passo de "agora instale o plugin X". A fonte da verdade das versões é o `composer.json`; a tabela diz **para que serve cada um dentro do kit**.

### Base

| Pacote | Para quê |
|---|---|
| [laravel/framework](https://packagist.org/packages/laravel/framework) | o framework |
| [filament/filament](https://packagist.org/packages/filament/filament) | os painéis, tabelas, formulários e widgets |
| [laravel/tinker](https://packagist.org/packages/laravel/tinker) | REPL do Laravel |
| [livewire/blaze](https://packagist.org/packages/livewire/blaze) | otimiza componentes Blade dobrando-os no template pai |

### Administração e segurança

| Pacote | Para quê |
|---|---|
| [bezhansalleh/filament-shield](https://packagist.org/packages/bezhansalleh/filament-shield) | papéis e permissões com UI, sobre spatie/laravel-permission |
| [jeffgreco13/filament-breezy](https://packagist.org/packages/jeffgreco13/filament-breezy) | perfil do usuário, avatar, 2FA e passkeys |
| [caresome/filament-auth-designer](https://packagist.org/packages/caresome/filament-auth-designer) | tela de login em duas colunas |
| [marjose123/filament-lockscreen](https://packagist.org/packages/marjose123/filament-lockscreen) | bloqueio de sessão por inatividade, sem deslogar |
| [stechstudio/filament-impersonate](https://packagist.org/packages/stechstudio/filament-impersonate) | entrar como outro usuário |
| [tapp/filament-authentication-log](https://packagist.org/packages/tapp/filament-authentication-log) | histórico de logins, IP e dispositivo |
| [owen-it/laravel-auditing](https://packagist.org/packages/owen-it/laravel-auditing) | trilha de alterações dos models |
| [tapp/filament-auditing](https://packagist.org/packages/tapp/filament-auditing) | a tela dessa trilha no painel |
| [syriable/filament-activitylog](https://packagist.org/packages/syriable/filament-activitylog) | log de atividades (spatie/laravel-activitylog) no Filament |
| [bezhansalleh/filament-panel-switch](https://packagist.org/packages/bezhansalleh/filament-panel-switch) | troca de painel pelo menu do usuário |

### Observabilidade e manutenção

| Pacote | Para quê |
|---|---|
| [shuvroroy/filament-spatie-laravel-health](https://packagist.org/packages/shuvroroy/filament-spatie-laravel-health) | health checks (banco, cache, filas, agendador, disco, IA) |
| [spatie/laravel-backup](https://packagist.org/packages/spatie/laravel-backup) | backup da aplicação e do banco |
| [brimham/filament-backup-monitor](https://packagist.org/packages/brimham/filament-backup-monitor) | histórico e saúde dos backups por destino |
| [croustibat/filament-jobs-monitor](https://packagist.org/packages/croustibat/filament-jobs-monitor) | monitor de filas para qualquer driver |
| [laboiteacode/filament-logs-explorer](https://packagist.org/packages/laboiteacode/filament-logs-explorer) | leitura e busca nos logs sem sair do painel |
| [ssbityukov/filament-command-center](https://packagist.org/packages/ssbityukov/filament-command-center) | comandos Artisan pré-aprovados pela UI, com histórico |
| [laravel/pulse](https://packagist.org/packages/laravel/pulse) | performance e uso da aplicação em tempo real |
| [dotswan/filament-laravel-pulse](https://packagist.org/packages/dotswan/filament-laravel-pulse) | o Pulse embutido como página do painel |
| [laboiteacode/filament-dependency-graph](https://packagist.org/packages/laboiteacode/filament-dependency-graph) | mapa visual de models, relações, resources e painéis |
| [mominalzaraa/filament-composer-release-notifier](https://packagist.org/packages/mominalzaraa/filament-composer-release-notifier) | avisa quando há versão nova dos pacotes Composer |
| [cms-multi/filament-clear-cache](https://packagist.org/packages/cms-multi/filament-clear-cache) | limpar caches pelo painel |
| [bezhansalleh/filament-exceptions](https://packagist.org/packages/bezhansalleh/filament-exceptions) | exceções agrupadas por tipo e frequência, com retenção |
| [tapp/filament-maillog](https://packagist.org/packages/tapp/filament-maillog) | trilha de todo e-mail enviado |
| [promethys/revive](https://packagist.org/packages/promethys/revive) | a Lixeira: restaura registro apagado com `SoftDeletes` |

### IA

| Pacote | Para quê |
|---|---|
| [laravel/ai](https://packagist.org/packages/laravel/ai) | o SDK oficial de IA do Laravel (agentes, tools, streaming) |
| [fomvasss/laravel-ai-tasks](https://packagist.org/packages/fomvasss/laravel-ai-tasks) | orquestração das tarefas de IA: roteamento, fila, auditoria e budget |

### UI e produtividade

| Pacote | Para quê |
|---|---|
| [wezlo/filament-search-spotlight](https://packagist.org/packages/wezlo/filament-search-spotlight) | o overlay da busca ⌘K |
| [prodstarter/filament-notification-center](https://packagist.org/packages/prodstarter/filament-notification-center) | centro de notificações com abas e categorias |
| [pxlrbt/filament-environment-indicator](https://packagist.org/packages/pxlrbt/filament-environment-indicator) | indicador de ambiente (local, homologação, produção) |
| [gsferro/filament-odometer-easy](https://packagist.org/packages/gsferro/filament-odometer-easy) | contadores animados em tabelas, infolists, stats e badges |
| [gsferro/odometer-easy](https://packagist.org/packages/gsferro/odometer-easy) | a base do odometer fora do Filament |
| [gsferro/filament-stat-plus-easy](https://packagist.org/packages/gsferro/filament-stat-plus-easy) | stat cards com ícone de canto, borda colorida e skeleton |
| [awcodes/filament-badgeable-column](https://packagist.org/packages/awcodes/filament-badgeable-column) | badges dentro de colunas de tabela |
| [asmit/resized-column](https://packagist.org/packages/asmit/resized-column) | colunas redimensionáveis pelo usuário |
| [laboiteacode/filament-dashboard-widgets](https://packagist.org/packages/laboiteacode/filament-dashboard-widgets) | widgets prontos de métrica, meta, breakdown e tendência |
| [mddev31/filament-dynamic-dashboard](https://packagist.org/packages/mddev31/filament-dynamic-dashboard) | dashboard configurável pelo usuário: arrastar e redimensionar widgets |
| [lara-zeus/progress](https://packagist.org/packages/lara-zeus/progress) | barras de progresso em colunas e entries |
| [wallacemartinss/filament-onboarding](https://packagist.org/packages/wallacemartinss/filament-onboarding) | checklists e tours guiados, com autoria no `/admin` |
| [anselmokossa/filament-sentinel](https://packagist.org/packages/anselmokossa/filament-sentinel) | páginas de erro (403, 404, 419, 500, 503) com a cara do painel |
| [flowframe/laravel-trend](https://packagist.org/packages/flowframe/laravel-trend) | agregação por período para os gráficos dos widgets |
| [bezhansalleh/filament-language-switch](https://packagist.org/packages/bezhansalleh/filament-language-switch) | seletor de idioma nos três painéis e nas telas de login |

### Dados e serviços

| Pacote | Para quê |
|---|---|
| [filament/spatie-laravel-settings-plugin](https://packagist.org/packages/filament/spatie-laravel-settings-plugin) | páginas de configuração no painel |
| [spatie/laravel-settings](https://packagist.org/packages/spatie/laravel-settings) | as configurações persistidas por trás delas |
| [filament/spatie-laravel-media-library-plugin](https://packagist.org/packages/filament/spatie-laravel-media-library-plugin) | a camada de mídia (upload, coleções, conversões) nos componentes de form, tabela e infolist |
| [mike-bronner/laravel-model-caching](https://packagist.org/packages/mike-bronner/laravel-model-caching) | cache automático de queries do Eloquent |
| [predis/predis](https://packagist.org/packages/predis/predis) | cliente Redis em PHP puro (sem extensão) |
| [laravel/reverb](https://packagist.org/packages/laravel/reverb) | WebSocket para as notificações em tempo real |

> **Motores por baixo dos plugins**, instalados como dependência (você não os declara, mas eles são o que de fato roda): `spatie/laravel-permission` (Shield), `spatie/laravel-health` (os checks), `spatie/laravel-activitylog` (o log de atividades), `spatie/laravel-medialibrary` (os anexos) e `livewire/livewire` (o Filament inteiro).

### Model Caching

O kit aplica a trait `App\Traits\ModeloCacheavel` nas models que têm Resource no painel `/app` — hoje `User`, `Convite` e `Projeto`. O pacote `mike-bronner/laravel-model-caching` cacheia as queries Eloquent quando `MODEL_CACHE_ENABLED=true`.

- O default é `false` (`MODEL_CACHE_ENABLED=false` no `.env.example`).
- Para ligar, defina `MODEL_CACHE_ENABLED=true` e use `MODEL_CACHE_STORE=model-cache` (store Redis configurado em `config/cache.php`).
- A invalidação é automática: `save`, `update` e `delete` limpam o cache da model.
- Painéis `/admin` e `/infra` continuam **sem** model caching por padrão, reduzindo o risco de stale data em telas administrativas.

```bash
php artisan modelCache:clear      # limpa o cache das models
```


### Desenvolvimento (`require-dev`)

| Pacote | Para quê |
|---|---|
| [pestphp/pest](https://packagist.org/packages/pestphp/pest) + [pest-plugin-laravel](https://packagist.org/packages/pestphp/pest-plugin-laravel) | a suíte de testes |
| [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit) | o motor por baixo do Pest |
| [larastan/larastan](https://packagist.org/packages/larastan/larastan) | análise estática (`composer types:check`) |
| [laravel/pint](https://packagist.org/packages/laravel/pint) | formatação (`composer lint`) |
| [laraveldaily/filacheck](https://packagist.org/packages/laraveldaily/filacheck) | lint específico de Filament (`composer filament:check`) |
| [laravel-lang/common](https://packagist.org/packages/laravel-lang/common) | traduções pt-BR do Laravel |
| [laravel/pail](https://packagist.org/packages/laravel/pail) | logs em tempo real no terminal |
| [laravel/pao](https://packagist.org/packages/laravel/pao) | ferramentas de desenvolvimento do Laravel |
| [nunomaduro/collision](https://packagist.org/packages/nunomaduro/collision) | erros legíveis no terminal |
| [mockery/mockery](https://packagist.org/packages/mockery/mockery) | mocks nos testes |
| [fakerphp/faker](https://packagist.org/packages/fakerphp/faker) | dados falsos **só em teste** — seeder do kit nunca usa |

### Front-end (`package.json`)

| Pacote | Para quê |
|---|---|
| [vite](https://www.npmjs.com/package/vite) + [laravel-vite-plugin](https://www.npmjs.com/package/laravel-vite-plugin) | o build dos assets |
| [tailwindcss](https://www.npmjs.com/package/tailwindcss) + [@tailwindcss/vite](https://www.npmjs.com/package/@tailwindcss/vite) | o CSS (v4, sem arquivo de config) |
| [concurrently](https://www.npmjs.com/package/concurrently) | roda servidor, fila e vite juntos no `composer dev` |
| [@laravel/multiplex](https://www.npmjs.com/package/@laravel/multiplex) | agrupa requests do Livewire (opcional) |

## Licença

MIT.
