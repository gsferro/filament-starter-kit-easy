# Plano de Ação — Customizador de instalação (`create-project` interativo)

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: — (não há; a feature nasce aqui)
- **Motivo**: —
- **Toca infra compartilhada?**: **sim** → `app/Console/Commands/KitInstall.php` (o caminho de
  instalação de todo projeto novo), `app/Console/Commands/KitTenancy.php` (extração dos passos
  não destrutivos), os três `*PanelProvider` (cor primária), `config/kit.php` e `.env.example`.

> Por tocar infra compartilhada, a regressão no quality gate é **obrigatória**, contra os CT das
> features que consomem o que foi mexido: `tests/Kit/PaineisTest.php` (painéis de pé),
> `tests/Kit/IdentidadeVisualTest.php` (a cor do tenant precisa continuar vencendo a cor global),
> `tests/Tenancy/**` (tenancy ligada) e `tests/Kit/KitUpdateTest.php` (cobertura dos caminhos do kit).

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Perguntas durante o `create-project` | 4, 6 | TTY do Composer confirmado — ver `00`, seção Ambiguidades |
| RQ-02 | Itens da lista "Personalize seu projeto" | 4, 5, 7 | 4 dos 11 viram prompt; 7 viram ponteiro no resumo (passo 6) — decisão registrada no `00` |
| RQ-03 | Só no `create-project`; depois é `kit:update` | 6 | O gate é "o `.env` não existia antes desta execução **ou** `--force`" |
| RQ-04 | Seguir o modelo do instalador do Laravel | 4, 5 | `select` de banco, defaults silenciosos sem TTY, substituição pontual no `.env` |
| RQ-05 | MySQL entre as opções de banco | 4 | Terceira opção do `select` |
| RQ-06 | Observação de que PostgreSQL é o recomendado para IA local | 4 | Rótulo da opção + `note` fixo + linha no resumo |
| RQ-07 | A instalação aplica sozinha | 4, 5, 6 | `.env`, `config/permission.php`, `config/filament-shield.php` e config em memória |
| RQ-08 | Opção de pular | 4, 6 | Três vias: responder "não" na primeira pergunta, `--no-custom`, ou ambiente sem TTY |
| RQ-09 | Oferecer rodar os testes do kit | 6 | `confirm` com default **não**; roda `php artisan test --group=kit` |
| RQ-10 | Resumo do que foi customizado, exceto se pulou | 6 | Tabela + ponteiros; suprimido quando pulou |
| RQ-11 | Itens adicionais sem tornar o processo lento | 4 | Cor primária e multi-tenancy entram; e-mail e IA ficam de fora (ADR-07) |
| RQ-12 | Eficiente e rápido | 4 | 5 perguntas, todas com default; Enter em tudo instala igual ao padrão de hoje |
| RQ-13 | Opção de dar estrela | 6 | Ao final, depois do banner |
| RQ-14 | Estrela no modelo do Pest | 6 | `vendor/pestphp/pest/src/Console/Thanks.php` — `confirm` + `open`/`start`/`xdg-open` por SO |

## Objetivo

Transformar o `php artisan kit:install` — que hoje instala em silêncio com valores fixos — num
instalador que **pergunta cinco coisas** antes de tocar o banco e aplica as respostas sozinho:
nome do projeto, banco de dados, credenciais do administrador, cor primária dos painéis e modo
multi-organização. Como o `kit:install` é o `post-create-project-cmd` do `composer.json`, essas
perguntas aparecem exatamente onde o `laravel new` faz as dele.

A meta de tempo é dura: **Enter em tudo** produz hoje o mesmo projeto que a instalação atual
produz, e responder as cinco leva menos que abrir o `.env` uma vez. Quem não quer nada disso
pula com uma tecla — e em CI, sem TTY, o customizador nem aparece.

## Contexto

O README lista 11 itens de personalização, e todos eles são hoje trabalho manual **depois** da
instalação: editar `.env`, editar `PanelProvider`, rodar `kit:tenancy`. Dois desses itens são
caros de adiar:

1. **Banco** — trocar de SQLite para Postgres depois exige refazer migrations e seeders.
2. **Multi-tenancy** — o `kit:tenancy` é destrutivo por natureza (`migrate:fresh --seed`), porque
   `permission.teams` precisa estar ligado **antes** do migrate. No dia 1 o banco está vazio: a
   mesma ativação sai de graça se acontecer **dentro** da instalação, antes do primeiro migrate.

Os outros sete itens da lista (arte do login, papéis, matriz de permissões, health checks,
comandos da UI, backups e agente de IA) são edição de código ou dado de tela — não cabem num
prompt e não entram (ver `00-requisito.md`, Ambiguidades).

## Análise dos Arquivos Existentes

### `app/Console/Commands/KitInstall.php`

Sequência atual do `handle()`: `prepararEnv` → `gerarAppKey` → `prepararBancoSqlite` → `migrar` →
`semear` + `formatarCodigoGerado` → `publicarAssets` → `construirFrontend` → `banner`. Nenhum
passo aborta a instalação: o que falha vira aviso em `$this->avisos`. Já usa
`Laravel\Prompts\note`.

Dois pontos determinam o desenho:

- `prepararBancoSqlite()` decide pelo `config('database.default')`, e `semear()` cria o admin com
  `config('kit.admin.*')`. Escrever no `.env` **não** muda config já carregada — é preciso alinhar
  a config em memória, exatamente como o `KitTenancy::alinharConfigEmMemoria()` faz e documenta.
- A customização precisa acontecer **depois** do `prepararEnv()` (o `.env` tem de existir) e
  **antes** do `prepararBancoSqlite()`.

### `app/Console/Commands/KitTenancy.php`

Faz três coisas que precisam acontecer juntas: `KIT_TENANCY=true` no `.env`,
`permission.teams = true` + `filament-shield.tenant_model` nos configs, e `migrate:fresh --seed`.
As duas primeiras são **não destrutivas** e são exatamente o que a instalação precisa; a terceira
existe só porque, num projeto já migrado, não há como ligar teams aditivamente.

Tem `preVoo()` exigindo repositório git com árvore limpa — que um `create-project` não tem — e um
`substituirNoArquivo()` privado que o customizador também precisa. Ambos motivam a extração dos
passos 2 e 3.

### `app/Providers/Filament/{App,Admin,Infra}PanelProvider.php`

Nenhum dos três chama `->colors()`. A única cor registrada é a **da organização**, no
`AppPanelProvider`, via `FilamentColor::register()` dentro de `bootUsing()` — e o comentário do
arquivo explica em detalhe por que ali e não em `->colors()`. A cor global do projeto é, hoje,
inexistente: o Filament usa o âmbar padrão.

### `config/logging.php`

Tem channels próprios do kit (`ai`, `tenancy`, `autenticacao`), todos `daily`, e **não** está em
`KitUpdate::CAMINHOS_DO_KIT`: channel novo chega em projeto novo e não chega em projeto que só
roda `kit:update`. É metade da razão de a feature **não** criar channel próprio — ver
`## Channel de Log da Feature`. O arquivo, portanto, **não é tocado**.

### `phpunit.xml`

Fixa `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:`. Ou seja: oferecer "rodar os testes do kit"
ao final é **seguro** — a suíte não toca o banco recém-criado nem depende do Postgres estar de pé.
E `Tests\TestCase` força `permission.teams` por suíte, então a suíte continua correta com a
tenancy ligada na instalação.

## Autorização

Não se aplica: comando de console, executado por quem tem acesso ao shell do projeto. Nenhuma
policy, gate ou middleware é criado ou alterado.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** A feature é inteiramente CLI (`php artisan kit:install`). Nenhuma tela,
componente Livewire ou rota é criada. Logo, **não haverá `05-casos-de-teste-browser.md`**.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_COR_PRIMARIA` | *(vazio)* | Nome da cor da paleta do Filament (`Blue`, `Emerald`, …) aplicada como `primary` nos três painéis. Vazio = âmbar padrão do Filament |
| `KIT_ADMIN_EMAIL` | `admin@example.com` | Já existe em `config/kit.php`; passa a constar no `.env.example` para o customizador ter linha a substituir |
| `KIT_ADMIN_PASSWORD` | `password` | idem |
| `KIT_TENANCY_LABEL` / `_LABEL_PLURAL` / `_SLUG` | `Organização` / `Organizações` / `organizacoes` | Já existem em `config/kit.php`; passam a constar comentadas no `.env.example` |

`KIT_ADMIN_NAME` **não** entra: nenhuma pergunta a escreve, e chave nova que ninguém define é
imposto de leitura no `.env` de todo projeto. Quem quiser trocar o nome do admin usa
`config/kit.php`, como hoje. *(corte da auditoria Ponytail)*

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Impacto em Features Existentes

- **`kit:tenancy`** — perde os três métodos não destrutivos para `App\Support\AtivadorDeTenancy` e
  passa a delegar. Comportamento externo idêntico; `tests/Tenancy/**` é a rede.
- **Identidade visual da organização** (`/app`) — a cor do tenant precisa **continuar vencendo** a
  cor global agora registrada por `->colors()`. Regressão obrigatória em
  `tests/Kit/IdentidadeVisualTest.php`.
- **`/admin` e `/infra`** — passam a ter cor primária configurável; sem `KIT_COR_PRIMARIA` nada
  muda em relação a hoje.
- **`kit:install` sem TTY** (CI, Docker build, `--no-interaction`) — precisa continuar instalando
  exatamente como hoje, sem travar em prompt. É o cenário mais perigoso da feature.
- **`kit:update`** — `app/Support` já está em `CAMINHOS_DO_KIT`; `config/logging.php` **não** está,
  e continuará não estando (ver ADR-05).

## Rollback

- **Migration**: nenhuma migration nova.
- **Reversão de dados**: não há dado a reverter — o efeito da feature é o conteúdo do `.env` e de
  dois arquivos de config no dia da instalação. Reverter é editar o `.env` (ou reinstalar com
  `php artisan kit:install --force --no-custom`).
- **Multi-tenancy ligada por engano na instalação**: o banco acabou de nascer; `KIT_TENANCY=false`
  no `.env`, `'teams' => false` em `config/permission.php`, `tenant_model => null` em
  `config/filament-shield.php` e `php artisan migrate:fresh --seed`.

## Dependências

**Uma, opcional**: `composer require pestphp/pest-plugin-mutate --dev`. O plugin já existe em
`vendor/` como dependência transitiva do Pest 5, mas **não** está declarado no `composer.json` — o
`pest --mutate` do fechamento do ciclo funciona hoje por acidente da árvore de dependências e some
num `composer update`. Ou se declara, ou se declara que o fechamento por mutação não será feito
nesta feature. Achado devolvido pela `feature-test-design`.

Nenhuma dependência de produção nova. `laravel/prompts` v0.3.22 já está instalado (dependência do framework) e já é usado
por `KitInstall` (`note`) e `KitUpdate` (`confirm`, `select`, `table`, `note`).

## Riscos

- **R1 — o prompt não aparecer no `create-project`.** Mitigado por verificação prévia
  (`EventDispatcher::executeTty` do Composer 2.9.5, ver `00`) e por teste manual real no passo 9.
  Se falhar em algum ambiente, o comportamento degrada para "instalação de hoje", que é RQ-08.
- **R2 — config escrita no `.env` × config em memória.** É a armadilha já documentada no
  `KitTenancy`: o mesmo processo segue com a config antiga e migra/semeia errado, **sem erro**.
  Mitigação: `alinharConfigEmMemoria()` explícito no passo 5, com CT dedicado.
- **R3 — banco externo indisponível.** Postgres/MySQL escolhidos com o servidor fora do ar fariam
  `migrate` e `db:seed` falharem em cascata. Mitigação: conferência de conexão antes de migrar,
  com aviso acionável e instalação seguindo sem migrar (passo 6).
- **R4 — precedência de cor.** `->colors()` do painel × `FilamentColor::register()` do tenant. Se a
  ordem for a errada, todo tenant perde a cor própria — e ninguém percebe até abrir um `/app`.
  Mitigação: CT de regressão em `IdentidadeVisualTest`.
- **R5 — senha do admin em log ou em tela.** Mitigação: a senha nunca vai para o log (mascarada) e
  no resumo aparece só se for diferente do default, ainda assim mascarada.
- **R6 — `kit:install` re-executado num projeto vivo.** Perguntar de novo poderia reescrever o
  `.env` de um projeto em uso. Mitigação: o customizador só roda quando o `.env` **não existia**
  antes desta execução — ou quando `--force` foi passado, que já significa "reinstala do zero,
  apagando o banco" e é o único jeito de alguém pedir isso conscientemente.

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem `ai`, `tenancy` e `autenticacao` — nenhum serve para instalação.
`Grep` por `Log::channel(` em `app/Console/Commands/`: só `KitTenancy` usa (`tenancy`).

### Decisão — **sem channel próprio** (corte da auditoria Ponytail)

Os logs da feature vão para o **channel default** do projeto, com o mesmo prefixo
`[Classe@Método]` de sempre. O channel dedicado foi cortado na auditoria do passo 6, e a razão é
que ele não sobrevive ao próprio motivo de existir:

- A justificativa seria o post-mortem da **instalação desatendida** — mas instalação desatendida
  não tem terminal e, por R1, **pula a customização inteira**. O conteúdo que o channel guardaria
  não chega a existir nesse caso.
- Na instalação atendida, o registro está no terminal, na frente de quem instala.
- E ele arrastava uma dependência tortuosa: `config/logging.php` **não** é distribuído pelo
  `kit:update`, então um projeto antigo rodando o `kit:install` novo não teria o channel, e
  `Log::channel('instalacao')` lançaria `InvalidArgumentException` — o que exigiria um helper de
  fallback só para não derrubar a reinstalação.

Três arquivos a menos, um helper a menos, e o registro continua existindo.

## Estrutura de Implementação

### 1. Cor primária como configuração

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`
- Nova chave de topo, com bloco de comentário no estilo do arquivo:
  ```php
  'cor_primaria' => env('KIT_COR_PRIMARIA'),
  ```
  O comentário precisa dizer três coisas: que o valor é o **nome de uma constante da paleta**
  (`Filament\Support\Colors\Color`), que vazio significa "o padrão do Filament", e que a cor da
  **organização** (multi-tenancy) continua vencendo esta no painel `/app`.
- **Path**: `.env.example`
  - `KIT_COR_PRIMARIA=` (vazio) no bloco de painéis, com uma linha de comentário listando alguns
    nomes válidos.
  - Bloco novo "Usuário inicial" com `KIT_ADMIN_EMAIL` e `KIT_ADMIN_PASSWORD` **descomentados**,
    com os defaults de hoje. Sem a linha presente, o customizador teria de anexar chave no fim do
    arquivo — funciona, mas produz `.env` desorganizado. `KIT_ADMIN_NAME` fica de fora: nenhuma
    pergunta a escreve.
  - `KIT_TENANCY_LABEL`, `KIT_TENANCY_LABEL_PLURAL` e `KIT_TENANCY_SLUG` **comentados**, ao lado do
    `KIT_TENANCY` que já existe.
- **Logs**: nenhum.

### 2. `App\Support\SubstituicaoEmArquivo`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/SubstituicaoEmArquivo.php`
- Extração literal do `KitTenancy::substituirNoArquivo()` — a mesma substituição pontual que
  preserva comentários e chaves acrescentadas pelo usuário. É o `replaceInFile()` do instalador do
  Laravel, com fallback de append.
  ```php
  final class SubstituicaoEmArquivo
  {
      public static function aplicar(string $caminho, string $padrao, string $novo, ?string $fallback = null): bool;
  }
  ```
  Devolve `true` quando o arquivo foi alterado — o customizador usa o retorno para montar o resumo.
- **Path**: `app/Console/Commands/KitTenancy.php` — remover o método privado e delegar.
- **Logs**: nenhum (a classe é utilitária; quem loga é o chamador).

### 3. `App\Support\AtivadorDeTenancy`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/AtivadorDeTenancy.php`
- Recebe os três passos **não destrutivos** que hoje vivem em `KitTenancy`:
  ```php
  final class AtivadorDeTenancy
  {
      /** KIT_TENANCY=true + rótulos no .env. */
      public static function escreverEnv(string $label, string $labelPlural, string $slug): void

      /** permission.teams = true + filament-shield.tenant_model. */
      public static function ligarPapeisPorTenant(): void

      /** Alinha a config JÁ CARREGADA e renasce o PermissionRegistrar. */
      public static function alinharConfigEmMemoria(): void
  }
  ```
- O bloco de docblock do `KitTenancy` que explica **por que** as três chaves precisam concordar, e
  o prazo de cada uma, migra junto — é o conhecimento caro do arquivo.
- `KitTenancy` passa a chamar os três e mantém para si o que é dele: `preVoo()` (git limpo),
  `confirmarDestruicao()`, `recriarBanco()`, `conferirSchema()`, demo e banner.
- **Logs**: nenhum aqui; os chamadores (`KitTenancy` e `KitInstall`) já logam nos seus channels.

### 4. `App\Support\CustomizadorDaInstalacao` — as perguntas

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/CustomizadorDaInstalacao.php`
- Superfície pública:
  ```php
  final class CustomizadorDaInstalacao
  {
      /** @return array<string, mixed>|null  null = o usuário pulou */
      public function perguntar(Command $comando): ?array

      /** Escreve .env e configs; devolve as linhas do resumo. @return list<array{0:string,1:string}> */
      public function aplicar(array $respostas): array
  }
  ```

**Diretório-base injetável — requisito de testabilidade, não conveniência.** A classe recebe no
construtor o diretório em que vai escrever (`__construct(private string $base = '')`, resolvido
para `base_path()` quando vazio). Sem isso, todo cenário de aplicação (CT-08…CT-21) reescreveria o
**`.env` da máquina de quem roda a suíte**. Devolvido pela `feature-test-design`.

**As perguntas, na ordem** (todas com default; Enter em tudo = instalação de hoje):

| # | Prompt | Tipo | Default | Aplica em |
|---|--------|------|---------|-----------|
| 0 | `Personalizar o projeto agora?` | `confirm` | **sim** | — (não = pula tudo, RQ-08) |
| 1 | `Nome do projeto` | `text` | `Str::headline(basename(base_path()))` | `APP_NAME` |
| 2 | `Banco de dados` | `select` | `sqlite` | bloco `DB_*` |
| 3 | `E-mail do administrador` | `text` + validação de e-mail | `admin@example.com` | `KIT_ADMIN_EMAIL` |
| 4 | `Senha do administrador` | `password`, vazio mantém o default | `password` | `KIT_ADMIN_PASSWORD` |
| 5 | `Cor primária dos painéis` | `select` | `Padrão do Filament` | `KIT_COR_PRIMARIA` |
| 6 | `Ligar o modo multi-organização?` | `confirm` | **não** | tenancy (passo 3) |
| 6a | `Como chamar cada organização?` (só se 6 = sim) | `text` | `Organização` | `KIT_TENANCY_LABEL` |
| 6b | `E no plural?` (só se 6 = sim) | `text` | `{6a}s` | `KIT_TENANCY_LABEL_PLURAL` + slug |

**Opções do `select` de banco** (RQ-05, RQ-06) — o rótulo carrega a observação, porque `hint` de
`select` no Prompts só aparece na opção destacada:

```php
'sqlite' => 'SQLite — padrão, não depende de nenhum serviço externo',
'pgsql'  => 'PostgreSQL — recomendado: é o único com pgvector, exigido pelas funções de IA local',
'mysql'  => 'MySQL / MariaDB — traga o seu servidor (o kit não sobe container MySQL)',
```

E, logo abaixo do `select`, um `note()` fixo com a observação de RQ-06 por extenso: busca semântica
e embeddings do kit dependem de `pgvector`, que só existe no Postgres; com SQLite ou MySQL o resto
do kit funciona e só essas funções ficam indisponíveis.

**Opções do `select` de cor**: `Padrão do Filament` (valor `null`) + as constantes de
`Filament\Support\Colors\Color` numa **lista fechada** — `Amber`, `Blue`, `Cyan`, `Emerald`,
`Fuchsia`, `Indigo`, `Lime`, `Orange`, `Pink`, `Purple`, `Red`, `Rose`, `Sky`, `Slate`, `Teal`,
`Violet`. Lista fechada e não `get_class_vars`: a paleta tem constantes que não são cor
(`WCAG_AA_TEXT` e afins) e neutros que ninguém escolhe como primária.

**Guardas de pulo** (RQ-08), nesta ordem, todas antes da primeira pergunta:

1. `$comando->option('no-custom')` → pula
2. terminal não interativo — **`! $this->input->isInteractive()`** (CI, Docker,
   `--no-interaction`) → pula
3. resposta "não" na pergunta 0 → pula

> **Nunca guardar por `stream_isatty(STDIN)`.**
> `Illuminate\Console\Concerns\ConfiguresPrompts:33-37` liga `Prompt::interactive()` quando
> `runningUnitTests()` e cai no fallback do Symfony em Windows e em teste. Sob `$this->artisan()`
> o STDIN **não** é tty: uma guarda por `stream_isatty` faria o customizador se pular dentro da
> própria suíte, e todo cenário de coleta passaria sem exercitar nada. `isInteractive()` é `true`
> em teste e `false` com `--no-interaction`, que é o que se quer nos dois casos.
>
> Efeito colateral favorável: num `create-project` sem tty, o Prompts responde sozinho com os
> defaults em vez de travar — o resultado é a instalação de hoje, que é RQ-08.

- **Logs**:
  - `Log::info('[CustomizadorDaInstalacao@perguntar] Customização pulada | motivo: sem-tty', ['motivo' => 'sem-tty'])` — motivo ∈ `flag`, `sem-tty`, `usuario`
  - `Log::debug('[CustomizadorDaInstalacao@perguntar] Respostas coletadas | banco: pgsql', ['respostas' => $respostasMascaradas])` — **senha nunca vai no context** (R5)

### 5. `App\Support\CustomizadorDaInstalacao` — a aplicação

> Skills: `laravel-best-practices`, `ponytail`

Cada gravação usa `SubstituicaoEmArquivo::aplicar()` no `.env` — nunca reescrita do arquivo
inteiro. Os padrões aceitam a linha comentada (`/^#?\s*CHAVE=.*$/m`), para funcionar tanto no
`.env.example` copiado quanto num `.env` já editado.

| Resposta | Escrita |
|---|---|
| Nome | `APP_NAME="{nome}"` |
| Banco = `sqlite` | `DB_CONNECTION=sqlite`; as demais `DB_*` seguem comentadas (é o estado do `.env.example`) |
| Banco = `pgsql` | `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE={slug do nome}`, `DB_USERNAME=starter_kit`, `DB_PASSWORD=secret` — os valores que o `docker-compose.yml` lê do próprio `.env`, então `docker compose up -d` sobe o container já com esse banco |
| Banco = `mysql` | `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE={slug do nome}`, `DB_USERNAME=root`, `DB_PASSWORD=` — o mesmo default que o instalador do Laravel escreve (RQ-04) |
| Admin | `KIT_ADMIN_EMAIL`, `KIT_ADMIN_PASSWORD` (e `KIT_ADMIN_NAME` inalterado) |
| Cor | `KIT_COR_PRIMARIA={Nome}` (ou linha vazia quando "padrão") |
| Tenancy = sim | `AtivadorDeTenancy::escreverEnv(...)` + `ligarPapeisPorTenant()` |

**Alinhamento da config em memória** (R2) — obrigatório, no fim do `aplicar()`:

```php
config([
    'app.name'            => $nome,
    'database.default'    => $driver,
    'kit.admin.email'     => $email,
    'kit.admin.password'  => $senha,
    'kit.cor_primaria'    => $cor,
]);
DB::purge();                       // a conexão antiga já foi resolvida com o driver velho
```
e, quando a tenancy foi ligada, `AtivadorDeTenancy::alinharConfigEmMemoria()`.

Sem isso: `prepararBancoSqlite()` criaria um arquivo SQLite mesmo com Postgres escolhido, o
`migrate` iria para o banco errado e o seeder criaria `admin@example.com` — tudo **sem erro**.

- **Logs**:
  - `info('[CustomizadorDaInstalacao@aplicar] Customização aplicada | banco: {driver}', ['banco' => …, 'cor' => …, 'tenancy' => …, 'admin_email' => …])` — sem senha
  - `warning('[CustomizadorDaInstalacao@aplicar] Chave não encontrada no .env, anexada ao final | chave: {chave}', ['chave' => …])`

### 6. `KitInstall` — orquestração

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Console/Commands/KitInstall.php`
- **Assinatura** — duas opções novas (`--force` já existe e passa a valer como "pergunte de novo"):
  ```
  {--no-custom  : Pula as perguntas de customização e instala com os padrões}
  {--no-support : Pula a pergunta da estrela no GitHub}
  ```
  Não existe um `--custom`: `--force` já significa "reinstala do zero, apagando o banco", e quem
  passa isso está reinstalando de propósito. Duas portas para o mesmo pedido só criam a dúvida de
  qual usar. *(corte da auditoria Ponytail)*
- **`handle()` na ordem nova**:
  ```
  1. $envJaExistia = File::exists(base_path('.env'));
  2. prepararEnv();
  3. $respostas = $envJaExistia && ! $this->option('force') ? null : $customizador->perguntar($this);   // RQ-03
  4. $resumo = $respostas ? $customizador->aplicar($respostas) : [];
  5. gerarAppKey();
  6. prepararBancoSqlite();      // já com database.default alinhado
  7. conferirConexao();          // NOVO — decide se migra
  8. migrar(); semear(); formatarCodigoGerado();
  9. publicarAssets(); construirFrontend();
  10. banner();  resumo($resumo);  oferecerTestes();  oferecerEstrela();
  ```
- **`conferirConexao()`** (novo, R3): só quando o driver **não** é sqlite. Faz
  `DB::connection()->getPdo()` dentro de `try`. Em falha: registra em `$this->avisos` a instrução
  exata (`docker compose up -d` para pgsql; "confira credenciais e crie o banco `{nome}`" para
  mysql) e **marca a instalação para não migrar nem semear** — em vez de derramar duas falhas em
  cascata. O resto (assets, front-end, banner) segue, fiel ao princípio do comando de que nenhum
  passo aborta a instalação.
- **`resumo(array $linhas)`** (RQ-10): só imprime quando houve customização. Usa
  `$this->components->twoColumnDetail()` para os valores escolhidos e, em seguida, um
  `bulletList()` com os **sete itens que continuam manuais** — cada um com o arquivo:
  arte do login (`public/images/auth/login.svg`), acesso aos painéis (`/admin` → Funções), matriz
  de permissões (`database/seeders/PapeisSeeder.php`), health checks
  (`KitServiceProvider::configureHealthChecks()`), comandos da UI
  (`config/command-center.php`), backups (`config/backup.php`), agente de IA (`/admin` → Agentes
  de IA). É o que fecha RQ-02 na parte que o instalador não pergunta.
- **`oferecerTestes()`** (RQ-09): só com TTY e só se houve customização.
  `confirm('Rodar os testes do kit agora?', default: false)`. Roda
  `Process([PHP_BINARY, 'artisan', 'test', '--group=kit'])` com saída em streaming e timeout de
  900 s. Seguro por construção: o `phpunit.xml` fixa `DB_CONNECTION=sqlite` /
  `DB_DATABASE=:memory:`, então a suíte **não toca** o banco recém-instalado.
- **`oferecerEstrela()`** (RQ-13, RQ-14): modelo do
  `vendor/pestphp/pest/src/Console/Thanks.php` — `confirm` e, no "sim",
  `open` (Darwin) / `start` (Windows) / `xdg-open` (Linux) para
  `https://github.com/gsferro/filament-starter-kit-easy`. Diferenças deliberadas: a URL é
  **sempre impressa** (mesmo sem TTY, para quem lê o log depois) e o desligamento é **só** por
  `--no-support` — sem variável de ambiente equivalente, que seria um segundo interruptor para o
  mesmo botão. *(corte da auditoria Ponytail)*
- **Logs**:
  - `info('[KitInstall@handle] Instalação iniciada | customizacao: {sim|nao}', ['customizacao' => …, 'interativo' => …])`
  - `warning('[KitInstall@conferirConexao] Banco inacessível, migrations puladas | driver: {driver}', ['driver' => …, 'exception' => $e])`
  - `info('[KitInstall@handle] Instalação concluída | avisos: {n}', ['avisos' => $this->avisos])`

### 7. Cor primária nos três painéis

> Skills: `laravel-best-practices`, `filament`

- **Paths**: `app/Providers/Filament/AppPanelProvider.php`, `AdminPanelProvider.php`,
  `InfraPanelProvider.php`
- Uma linha no corpo do `panel()`, antes dos plugins:
  ```php
  ->colors(fn (): array => filled(config('kit.cor_primaria'))
      ? ['primary' => constant(Color::class.'::'.config('kit.cor_primaria'))]
      : [])
  ```
  A Closure não é enfeite: `Panel::boot()` avalia `getColors()` na hora do boot, e o valor precisa
  vir da config resolvida naquele request, não do momento do registro do provider.
- **Guarda de valor inválido**: `KIT_COR_PRIMARIA=Roxo` derrubaria todo painel com
  `Error: Undefined constant`. A resolução passa por um helper que devolve `[]` quando a constante
  não existe — e o `CustomizadorDaInstalacao` já só oferece nomes da lista fechada.
- **Precedência (R4)**: no `/app`, a cor da organização é registrada em `bootUsing()` via
  `FilamentColor::register()`, avaliada no `renderStyles()` — **depois** do `->colors()` do painel.
  A ordem precisa ser confirmada na implementação com o CT de regressão; se ela se inverter, o
  caminho é registrar a cor global também por `FilamentColor::register()`, na mesma janela.
- **Logs**: nenhum (é caminho de render de toda página; log aqui seria ruído por request).

### 8. Documentação

> Skills: —

- **Path**: `README.md` e `README.en.md`
  - Na abertura, dizer que o `create-project` **pergunta** cinco coisas e que Enter em tudo instala
    como hoje.
  - Em "Personalize seu projeto", marcar quais itens o instalador já pergunta e quais seguem
    manuais.
  - Em "Banco de dados", registrar a escolha na instalação e a observação do pgvector.
  - Em "Comandos", as opções novas (`--no-custom`, `--no-support`) e o fato de `--force` fazer as
    perguntas de novo.

`wikis/receitas.md` **não** entra: a "receita" seria uma linha (`kit:install --force`) que a
própria tabela de comandos do README já traz. *(corte da auditoria Ponytail)*

### 9. Verificação manual do `create-project` (não automatizável)

> Skills: —

O único jeito de provar RQ-01 de ponta a ponta é rodar
`composer create-project gsferro/starter-kit-easy /tmp/teste-kit` a partir de um checkout local e
ver as perguntas aparecerem. Nenhum teste automatizado cobre a camada TTY do Composer. Registrar o
resultado (SO, terminal, versão do Composer) em `03-progresso.md` → Notas de Implementação.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> A escada aplicada aqui, explicitamente:
> 1. **Reutilizar**: `laravel/prompts` (já instalado, já usado pelos dois comandos do kit), o
>    `substituirNoArquivo` que já existe no `KitTenancy`, os três passos de ativação de tenancy que
>    já existem e funcionam.
> 2. **Nada de camada de abstração**: sem interface `Customizador`, sem "driver" por item de
>    customização, sem DTO com getters — as respostas são um array e a aplicação é uma sequência de
>    substituições.
> 3. **Nenhuma dependência nova.**
> 4. **Nenhum prompt que não mude bit no disco**: cinco perguntas, todas com efeito.
>
> Atalhos deliberados marcados com `ponytail:`.
> Após implementar, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário. Arquivos wiki (00-06), código,
> commits e PRs são boundary do Caveman — prosa normal.

## Mapeamentos

**Item do README → destino no instalador** (fecha RQ-02):

| # | Item "Personalize seu projeto" | No instalador |
|---|---|---|
| 1 | Nome (`APP_NAME`) | **pergunta 1** |
| 2 | Arte do login (`public/images/auth/login.svg`) | ponteiro no resumo — arquivo de imagem |
| 3 | Cores dos painéis | **pergunta 5** (via `KIT_COR_PRIMARIA`) |
| 4 | Acesso aos painéis (papel de cada usuário) | ponteiro no resumo — é dado criado depois, no `/admin` |
| 5 | Matriz de permissões (`PapeisSeeder`) | ponteiro no resumo — é código |
| 6 | Health checks (`KitServiceProvider`) | ponteiro no resumo — é código |
| 7 | Comandos da UI (`config/command-center.php`) | ponteiro no resumo — é código |
| 8 | Credenciais do seeder | **perguntas 3 e 4** |
| 9 | Backups (`config/backup.php`) | ponteiro no resumo — é código |
| 10 | Agente de IA | ponteiro no resumo — é dado editável no `/admin` |
| 11 | Multi-tenancy | **pergunta 6** (+ rótulos), sem `migrate:fresh` |
| — | Banco de dados (não está na lista, é RQ-05/RQ-06) | **pergunta 2** |

## Testes

> Ver `04-casos-de-teste.md` para a especificação completa dos cenários de backend.
> **Sem `05-casos-de-teste-browser.md`**: a feature não tem superfície de UI (comando de console).

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact --filter=Customizador`
- [ ] `composer test:kit` — a fundação inteira, com atenção a `IdentidadeVisualTest` (R4) e `KitUpdateTest`
- [ ] `php artisan test --testsuite=Tenancy` — o `KitTenancy` refatorado
- [ ] `vendor/bin/pest --parallel --tia` — nada mais no suite quebrou
- [ ] `composer types:check`
- [ ] Verificação manual do passo 9 (create-project real) registrada no progresso

## Commits

- `:sparkles: feat: customizador de instalação no create-project`
- `:recycle: refactor: extrai ativação de tenancy e substituição em arquivo para app/Support`
- `:lipstick: feat: cor primária dos painéis por KIT_COR_PRIMARIA`
- `:white_check_mark: test: casos do customizador de instalação`
- `:memo: docs: instalação interativa no README`
- `:memo: docs: wiki da feature customizador-de-instalacao`
