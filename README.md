# starter-kit-easy

<img alt="Starter Kit Easy" class="filament-hidden" src="https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbnail.png"/>

[![Packagist](https://img.shields.io/packagist/v/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
[![Downloads](https://img.shields.io/packagist/dt/gsferro/starter-kit-easy.svg?style=flat-square)](https://packagist.org/packages/gsferro/starter-kit-easy)
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
| **Infra** | `/infra` | Health checks, backups, filas, logs, auditoria, caches, comandos, Pulse, custos de IA | `master_global`, `infra` |

**Quem entra vem do papel, não de uma lista no código.** Cada papel declara em qual painel vale, na coluna `roles.painel` — é o campo **Painel** na tela `/admin` → Funções. `App\Models\User::canAccessPanel()` compara essa coluna com o painel que está sendo aberto. Criar um papel e escolher o painel dele **é** o ato de dar acesso.

Nulo **não** é coringa: papel sem painel só carrega permissões e não abre painel algum. O papel `master_global` entra nos três de outro jeito — ele vence qualquer gate via `Gate::before` (`App\Providers\KitServiceProvider`), sem precisar de permissions no banco, e o `canAccessPanel()` o libera antes de olhar a coluna.

> ⚠️ **Quebra deliberada:** até a 0.10.0 o `/app` era aberto a **qualquer usuário autenticado**. Deixou de ser — sem papel, ninguém entra em painel nenhum. Se você está atualizando um projeto, rode os dois seeders (`ShieldPermissionsSeeder` e `PapeisSeeder`) e revise os usuários: quem opera o negócio precisa do papel `panel_user`, ou de um papel seu com o painel `app`.

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

## O que já vem pronto

**Administração e segurança**
- Shield (papéis e permissões com UI) sobre spatie/laravel-permission
- Breezy: perfil do usuário, avatar, 2FA e passkeys
- Auth Designer: tela de login em duas colunas (troque a arte em `public/images/auth/login.svg`)
- Lockscreen: bloqueio de sessão por inatividade (30 min), registrado nos 3 painéis — a tela de bloqueio usa o mesmo layout do login (Auth Designer), não o layout simples do Filament
- Impersonate, log de autenticação, auditoria de alterações (owen-it)
- Panel Switch: troca de painel pelo menu do usuário

**Observabilidade e manutenção (painel infra)**
- Spatie Health com checks de banco, cache, filas, agendador, disco, debug mode e IA local
- Backup Monitor (spatie/laravel-backup), Jobs Monitor, Logs Explorer (sem botão de apagar — trilha é evidência)
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

> ⚠️ **Se você está atualizando um projeto:** rode `ShieldPermissionsSeeder` e depois `PapeisSeeder`. O `panel_user` passou a receber a matriz do `/app` **menos** as permissões dessas duas telas — sem rodar os seeders, todo usuário comum ficaria com poder de criar e apagar usuários.

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
  [roteiro de features](#roteiro-de-features) abaixo lista as 56 telas prontas. Feature
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
| F-03 | Registro **só** por convite | `/app/register?token=…` | quem tem token válido | sem token na query, a tela recusa e manda para o login | 🟢 |
| F-04 | Autenticação em dois fatores | `/{painel}/two-factor-authentication` | autenticado | a tela abre e oferece o QR | 🔵 |
| F-05 | Passkeys | Meu perfil | autenticado | cadastro de chave, no perfil do Breezy | ⚪ |
| F-06 | Bloqueio de sessão | menu do usuário → *Bloquear sessão* | autenticado | trava sem deslogar; volta com a senha. Usa o layout do login, não a `SimplePage` | 🟢 |
| F-07 | Meu perfil, avatar e senha | `/{painel}/meu-perfil` | autenticado | edita nome, e-mail, senha e avatar | 🔵 |
| F-08 | Impersonate | `/admin/users` → ação na linha | `master_global` | entra como outro usuário e volta pela faixa no topo | ⚪ |

### Autorização

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-09 | **O papel decide o painel** (`roles.painel`) | `/admin` → Funções | `admin`, `master_global` | crie um papel com painel `infra`: quem o tem entra no `/infra` e toma 403 no `/admin` | 🟢 |
| F-10 | 403 legível no painel errado | qualquer painel | — | a tela de 403 diz a conta, os papéis e oferece saída — e **não** revela permissão em produção | 🔵 |
| F-11 | `master_global` vence por `Gate::before` | os três | `master_global` | ele entra em tudo **sem** nenhuma permission no banco | 🟢 |
| F-12 | Papéis e permissões agrupados por painel | `/admin/shield/roles` | `admin` | a tela separa *Painel /admin*, */app* e */infra* | 🟢 |
| F-13 | `panel_user` **não** administra | `/app{/org}` | `panel_user` | ele usa o negócio e não vê Usuários nem Convites — a matriz dele é a do painel **menos** as telas de administração | 🟢 |
| F-14 | Sem papel, ninguém entra | os três | — | usuário autenticado sem papel toma 403 nos três. Nulo **não** é coringa | 🟢 |

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

### IA

| # | Feature | Onde | Quem alcança | Como conferir | Teste |
|---|---|---|---|---|---|
| F-53 | Chat do assistente | canto de **toda** tela do `/app` | autenticado | streaming; renderiza vazio sem usuário | ⚪ |
| F-54 | Guardrails encadeados | — | — | budget, prompt injection, classificador local, redação de PII e filtro de saída. **Fail-closed** | 🟢 |
| F-55 | Ledger de execuções | `/infra/execucoes-ia` | `infra` | toda chamada vira linha com custo e tokens | 🟢 |
| F-56 | Inferência local | `docker compose --profile ai up -d` | — | llama.cpp; ou troque `AI_PROVIDER` por um SaaS | ⚪ |

### O que o roteiro **não** cobre sozinho

Cinco features dependem de coisa fora do processo, e nenhum teste as substitui:

| Feature | Depende de | Sem isso |
|---|---|---|
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
composer test         # pint + phpstan + a suíte inteira
composer test:kit     # só os testes do kit (a fundação), em paralelo
composer lint         # formata o código
php artisan kit:install --force   # reinstala do zero (apaga o SQLite) e refaz as perguntas
php artisan kit:install --no-custom   # instala sem perguntar nada
php artisan kit:update            # traz melhorias de uma versão nova do kit
php artisan kit:tenancy           # liga o modo multi-tenant (opt-in)
```

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
| 7 | **Acesso aos painéis** | o papel de cada usuário (`/admin` → Funções, campo *Painel*); a regra que o lê é `App\Models\User::canAccessPanel()` | — |
| 8 | **Matriz de permissões** | `database/seeders/PapeisSeeder.php` | — |
| 9 | **Health checks** | `KitServiceProvider::configureHealthChecks()` | — |
| 10 | **Comandos da UI** | `config/command-center.php` | — |
| 11 | **Backups** | destino e agenda em `config/backup.php` | — |
| 12 | **Agente de IA** | `/admin` → Agentes de IA (ou `database/seeders/AssistenteSeeder.php`) | — |

Os sete últimos não entram nas perguntas porque são **código ou dado de tela**, não um valor que caiba num prompt de terminal. O instalador os lista no resumo final, com o arquivo de cada um.

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

> 📌 **TODO:** transformar esses defaults num **Settings em `/admin`**, para que paginação, densidade, persistência de filtros e colunas redimensionáveis virem preferência do projeto pela interface, sem editar código. O `filament/spatie-laravel-settings-plugin` já está instalado para isso.

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

Resources de **plugins de terceiros** (Auditoria, Logins, Filas, Pacotes do Composer, Comandos, Funções do Shield, Onboarding) ficam sem badge: `getNavigationBadge()` é um método estático do resource, e o Filament não oferece API para sobrescrevê-lo de fora — a `ResourceConfiguration` do painel só permite trocar o slug. Dar badge a eles exigiria estender cada resource de vendor e impedir o plugin de registrar o seu, o que quebra a cada atualização do pacote. Se algum for importante no seu projeto, o caminho é esse — resource por resource, conscientemente.

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
4. **Oferece um branch temporário** (`kit-update/v0.2.0`) para não sujar o seu.
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
| `--tag=v0.3.0` | comparar com uma versão específica |
| `--from=v0.1.0` | dizer de qual versão o projeto partiu (quando `config/kit.php` não sabe) |
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

As tags do kit vão para um namespace próprio (`kit-v*`). Isso importa: um `git fetch kit --tags` traria `v0.1.0`, `v0.2.0`… para o seu projeto e colidiria com as **suas** versões depois.

```bash
git fetch --no-tags kit 'refs/tags/*:refs/tags/kit-*'
git tag -l 'kit-*'      # kit-v0.1.0, kit-v0.2.0, ...
```

Depois, a cada versão, veja o que mudou e traga só o que interessa:

```bash
# 1. panorama entre a sua versão e a nova
git diff kit-v0.1.0..kit-v0.2.0 --stat

# 2. o diff da "cola" do kit (ignore o que você já reescreveu)
git diff kit-v0.1.0..kit-v0.2.0 -- app/Providers app/Filament/Concerns \
        app/Filament/Spotlight app/Traits resources/views/errors config/kit.php

# 3. traga arquivo a arquivo, revisando
git checkout kit-v0.2.0 -- resources/views/errors
git checkout kit-v0.2.0 -- app/Filament/Concerns/BadgeContagemNavegacao.php
```

Faça isso num branch (`git switch -c atualiza-kit`) e rode `composer test` antes do merge. Arquivos que você reescreveu: leia o diff e aplique à mão — é o único caminho seguro.

> 💡 **TODO / rumo do projeto:** extrair a "cola" para um pacote Composer próprio (`gsferro/kit-core`) com os providers, traits, widgets e páginas de infra. Aí a camada do meio vira `composer update gsferro/kit-core` e o skeleton fica mínimo — só o que é mesmo ponto de partida. É a evolução natural deste kit.

## Solução de problemas

- **403 em todos os painéis, logo depois de autenticar** — o usuário não tem papel nenhum, ou o papel dele está sem painel declarado (`roles.painel` vazio não é coringa: não abre nada). Dê o papel em `/admin` → Usuários, ou preencha o campo *Painel* em `/admin` → Funções.
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

### Dados e serviços

| Pacote | Para quê |
|---|---|
| [filament/spatie-laravel-settings-plugin](https://packagist.org/packages/filament/spatie-laravel-settings-plugin) | páginas de configuração no painel |
| [spatie/laravel-settings](https://packagist.org/packages/spatie/laravel-settings) | as configurações persistidas por trás delas |
| [mike-bronner/laravel-model-caching](https://packagist.org/packages/mike-bronner/laravel-model-caching) | cache automático de queries do Eloquent |
| [predis/predis](https://packagist.org/packages/predis/predis) | cliente Redis em PHP puro (sem extensão) |
| [laravel/reverb](https://packagist.org/packages/laravel/reverb) | WebSocket para as notificações em tempo real |

> **Motores por baixo dos plugins**, instalados como dependência (você não os declara, mas eles são o que de fato roda): `spatie/laravel-permission` (Shield), `spatie/laravel-health` (os checks), `spatie/laravel-activitylog` (o log de atividades) e `livewire/livewire` (o Filament inteiro).

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
