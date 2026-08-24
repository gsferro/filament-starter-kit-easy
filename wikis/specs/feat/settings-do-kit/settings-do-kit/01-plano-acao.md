# Plano de Ação — Settings do kit em `/admin`

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: a infraestrutura de settings está instalada no kit e com **zero uso**; esta é a primeira feature a ligá-la.
- **Toca infra compartilhada?**: **sim** →
  - `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (configuração global de TODA tabela dos três painéis)
  - `app/Providers/KitServiceProvider.php` (boot da aplicação inteira)
  - os TRÊS `PanelProvider` (brand, favicon, logo, arte de autenticação)
  - `app/Support/CorPrimaria.php` (cor dos três painéis)
  - `config/kit.php`, `config/logging.php`, `config/settings.php`
  - `tests/Pest.php` (inventário `telasDoKit()`)

> Regressão **obrigatória** apesar de o tipo ser "nova": a lista acima alcança toda tela do kit. Os alvos de regressão são `tests/Kit/CorPrimariaTest.php`, `tests/Kit/InventarioDeTelasTest.php`, `tests/Kit/PaineisTest.php`, `tests/Kit/TelasDeAutenticacaoTest.php`, `tests/Kit/PaginasInfraTest.php`, `tests/Tenancy/IdentidadeVisualTest.php` e a suíte `Browser` inteira.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Página de settings no `/admin` | 4 | `App\Filament\Admin\Pages\ConfiguracoesDoKit`, rota `/admin/configuracoes-do-kit` |
| RQ-02 | Usa o plugin oficial sobre o spatie | 2, 4 | `Filament\Pages\SettingsPage` + `Spatie\LaravelSettings\Settings` |
| RQ-03 | Customizações da instalação viram settings | 2, 3, 5, 7 | recorte e motivo em `00-requisito.md` → Ambiguidades |
| RQ-04 | Favicon | 2, 4, 6 | upload público + `->favicon(Closure)` nos três painéis |
| RQ-05 | Nome da aplicação | 2, 4, 5, 6 | `app.name` alinhado em memória + `->brandName(Closure)` |
| RQ-06 | Seleção pelo Enum `Color` | 2, 4, 5 | `Select` alimentado por `CustomizadorDaInstalacao::CORES` |
| RQ-07 | Cor livre por input de cor | 2, 4, 5 | `ColorPicker` + precedência em `CorPrimaria::paleta()` |
| RQ-08 | Dados de e-mail | 2, 4, 5 | aba E-mail, senha cifrada |
| RQ-09 | Tudo o mais que é customizável | 2, 4 | 21 propriedades em 4 abas |
| RQ-10 | README atualizado | 9 | `README.md` |
| RQ-11 | TODOs de settings implementados | 3, 7, 9 | `ConfiguraFilamentGlobal.php:35-38`, `README.md:1257`, `README.en.md:1221` |
| RQ-12 | Logo | 2, 4, 6 | upload público + `->brandLogo(Closure)` |
| RQ-13 | Imagem das telas de login | 2, 4, 6 | upload público + `->media(Closure)` do Auth Designer |
| RQ-14 | Permissão para operar o settings | 4, 8 | `View:ConfiguracoesDoKit` via `HasPageShield` |
| RQ-15 | Permissão padrão desde o início | 8 | `ShieldPermissionsSeeder` + `PapeisSeeder` **sem edição** — ver ADR-04 |
| RQ-16 | Pontos adicionais de valor | 2, 3, 7 | hub de navegação, rótulos da organização, defaults de tabela |
| RQ-17 | Audits na model do settings | 5 | listener de `SavingSettings` → tabela `audits` — ver ADR-07 |
| RQ-18 | Não confundir com settings do tenant | — | restrição respeitada: nada de `app/Filament/Admin/Resources/Tenants/` é tocado; documentado no README e no PHPDoc da classe de settings |
| RQ-19 | Documentado nos dois READMEs | 9 | `README.md` **e** `README.en.md` |

## Objetivo

Ligar a infraestrutura de settings que já está instalada no kit (`spatie/laravel-settings` 3.9.0 + `filament/spatie-laravel-settings-plugin` 5.7.6, hoje com `config/settings.php` publicado e a lista `settings` **vazia**, sem `app/Settings/` e sem `database/settings/`) numa única tela em `/admin/configuracoes-do-kit`. A tela guarda 21 propriedades em quatro abas — Identidade, E-mail, Tabelas e Kit — e passa a ser o lugar onde se troca o nome, a cor, o favicon, a logo, a arte de login, o transporte de e-mail e os defaults de tabela **sem editar arquivo nem reinstalar**.

A decisão central não é a tela: é a **fonte da verdade**. Hoje o `.env` é a única fonte, e ele alimenta `config/kit.php` e `config/app.php`. Um settings que lê do banco cria uma segunda. A resposta desta wiki é: **o banco vence em tempo de execução, o `.env` é a semente e o plano B**. Nenhum consumidor muda — todos continuam lendo `config()` —, porque um único ponto no boot sobrepõe a config do processo com o que o banco tem, e falha para o lado do `.env` quando o banco não responde ou a propriedade não existe.

## Contexto

O que o `kit:install` customiza hoje (`app/Support/CustomizadorDaInstalacao.php`, 555 linhas):

| Pergunta | Vai para | Alterável depois? |
|---|---|---|
| Nome do projeto | `.env` `APP_NAME` | só editando o `.env` (ou `kit:install --custom`) |
| Banco | `.env` `DB_*` | não (exige recriar) |
| E-mail do admin | `.env` `KIT_ADMIN_EMAIL` | não sincroniza (seeder busca pelo papel) |
| Senha do admin | `.env` `KIT_ADMIN_PASSWORD` | idem — o caminho é a tela de perfil |
| Cor primária | `.env` `KIT_COR_PRIMARIA` (lista fechada em `CustomizadorDaInstalacao.php:55-58`) | só editando o `.env` |
| Multi-organização | `.env` `KIT_TENANCY*` + reescreve `config/permission.php` | não (exige recriar) |

E os sete itens que continuam **manuais**, listados por `CustomizadorDaInstalacao::itensManuais()` (`:321-332`):

```
Arte do login .............. public/images/auth/login.svg
Acesso aos painéis ......... /admin → Funções (o campo Painel de cada papel)
Matriz de permissões ....... database/seeders/PapeisSeeder.php
Health checks .............. KitServiceProvider::configureHealthChecks()
Comandos da UI ............. config/command-center.php
Backups .................... config/backup.php
Agente de IA ............... /admin → Agentes de IA
```

Desses sete, **um** entra nesta entrega: a arte do login. Os outros seis são código ou tela que já existe (`/admin → Funções`, `/admin → Agentes de IA`) e transformá-los em campo de formulário seria trocar um arquivo versionado por um valor de banco sem ganho — a matriz de permissões e os health checks são código executável, e `config/command-center.php` e `config/backup.php` são estrutura, não escalar.

Além disso, três TODOs pedem exatamente esta feature:

- `app/Providers/Concerns/ConfiguraFilamentGlobal.php:35-38`
- `README.md:1257`
- `README.en.md:1221`

## Análise dos Arquivos Existentes

### `config/settings.php`

Publicado e intacto. `'settings' => []` (vazio), `setting_class_path` = `app_path('Settings')`, `migrations_paths` = `[database_path('settings')]`, `auto_discover_settings` = `[app_path('Settings')]`, `default_repository` = `database`, cache desligado por default (`SETTINGS_CACHE_ENABLED`).

**Consequência**: com `auto_discover_settings` ligado, a classe nova é descoberta sozinha e **não precisa** ser listada em `'settings'`. Registrar nas duas listas é redundância; será registrada explicitamente em `'settings'` de qualquer forma, porque `discovered_settings_cache_path` cacheia a descoberta e um registro explícito é o que sobrevive a um cache velho.

### `database/migrations/2022_12_14_083707_create_settings_table.php`

Presente. Cria `settings` com `group`, `name`, `locked`, `payload` (json), timestamps e `unique(['group','name'])`. Nada a fazer.

### `vendor/filament/spatie-laravel-settings-plugin` (5.7.6)

Só quatro arquivos PHP. **Não existe classe `Plugin`** — o `SpatieLaravelSettingsPluginServiceProvider` (`src/SpatieLaravelSettingsPluginServiceProvider.php:9-18`) apenas registra o comando `make:filament-settings-page` e carrega traduções. **Nada a registrar em `PanelProvider`.**

Contrato real de `Filament\Pages\SettingsPage` (`src/Pages/SettingsPage.php`), lido no vendor:

| Membro | Linha | Fato |
|---|---|---|
| `class SettingsPage extends Page` | `:23` | é uma `Page` do painel — logo entra em `discoverPages()` e na matriz do Shield |
| `protected static string $settings` | `:28` | a classe de settings |
| `public ?array $data = []` | `:33` | estado do formulário |
| `mount()` → `fillForm()` | `:35-51` | preenche com `app(static::getSettings())->toArray()` |
| `save()` | `:62-108` | `if (! $this->canEdit()) return;` → transação → `getState()` → `mutateFormDataBeforeSave()` → `$settings->fill($data); $settings->save();` |
| `getSettings()` | `:145-151` | usa `static::$settings` ou deriva do nome da classe |
| `defaultForm()` | `:180-187` | `->columns(2)->disabled(! $this->canEdit())->statePath('data')` |
| `public function canEdit(): bool` | `:248-251` | **método de instância**, não estático — o exemplo do README do vendor mostra `canAccess()` estático e induz ao erro |
| `getRedirectUrl()` | `:243-246` | `null` = fica na tela após salvar |

O README do vendor diz por escrito, na seção Authorization, que `canEdit()` **não** impede ler os valores: *"it does not prevent a user from loading the page and reading every field's current value... restrict viewing with `canAccess()`"*. É esse parágrafo que decide o ADR-04.

### `vendor/spatie/laravel-settings` (3.9.0)

- `Settings` é `abstract class` (`src/Settings.php:19`), **não** Eloquent. `abstract public static function group(): string` (`:31`), `casts()` (`:38`), `encrypted()` (`:43`).
- `save()` (`:187-201`) dispara `SavingSettings($properties, $originalValues, $this)` **antes** de gravar e `SettingsSaved($this)` depois. `SavingSettings` (`src/Events/SavingSettings.php:16-26`) é o **único** ponto que carrega valor novo e valor antigo juntos.
- `DatabaseSettingsRepository::createProperty()` usa `->create()` (`:56`) — dispara evento de Eloquent —, mas `updatePropertiesPayload()` usa `->upsert()` (`:74-77`) — **não dispara**. É por isso que auditar por trait numa model sobre a tabela `settings` audita só a criação.
- `Models\SettingsProperty` (`src/Models/SettingsProperty.php:7-27`) é uma model Eloquent real na tabela `settings`, com `$guarded = []`.

### `app/Support/CorPrimaria.php`

`paleta()` (`:28-43`) lê `config('kit.cor_primaria')`, exige string não vazia, confere `defined(Color::class.'::'.$nome)` e devolve `[]` quando falha. **A tolerância a valor inválido é deliberada** e está no docblock: `constant()` num nome inexistente lança `Error: Undefined constant`, e como isto roda no boot de todo painel, derrubaria toda página. A cor livre (RQ-07) precisa da MESMA tolerância — `Color::generatePalette()` (`vendor/filament/support/src/Colors/Color.php:663`) chama `convertToOklch()` e um valor não-cor produz lixo ou exceção.

### `app/Providers/Concerns/ConfiguraFilamentGlobal.php`

`configuraTable()` (`:177-226`) fixa `deferLoading()`, `striped()`, as quatro persistências em sessão, `reorderableColumns()`, `columnManagerLayout(Modal)`, `filtersLayout(Modal)`, `filtersFormColumns(2)`, `deferFilters()`, `stackedOnMobile()`, `deselectAllRecordsWhenFiltered(false)`, `defaultPaginationPageOption(10)`, `extremePaginationLinks()`. `aplicaMacrosDeColuna()` (`:243-252`) aplica `dragReorderableColumns` se a macro existir.

Está dentro de `Table::configureUsing(fn (Table $table) => ...)`, ou seja, resolvido **por tabela, em tempo de render** — logo o alinhamento de config no boot é visto por ele.

### Os três `PanelProvider`

| Provider | Linha | Chamada |
|---|---|---|
| `AdminPanelProvider` | `:62` | `->brandName(config('app.name').' • Admin')` |
| `AdminPanelProvider` | `:63` | `->colors(fn (): array => CorPrimaria::paleta())` |
| `AppPanelProvider` | `:69` | `->brandName(config('app.name'))` |
| `InfraPanelProvider` | `:78` | `->brandName(config('app.name').' • Infra')` |
| todos | `:112+`, `:186+`, `:145+` | `AuthDesignerPlugin::make()->login(...)->media(asset('images/auth/login.svg'), alt: config('app.name'))` — 3 chamadas por painel (login, password-reset, email-verification) |

**Nenhum** chama `->favicon()` nem `->brandLogo()` hoje.

**Medição feita nesta wiki** (probe com `php artisan tinker`): `config(['app.name' => 'PROBE'])` depois do boot **não** muda `Filament::getPanel('admin')->getBrandName()` — o valor continua `Starter Kit • Admin`. Ou seja, o argumento escalar de `brandName()` é congelado antes de o alinhamento de config poder agir. **Por isso os três brandName, o favicon, a logo e as nove chamadas de `media()` precisam receber `Closure`**, exatamente como `->colors()` já recebe. As assinaturas aceitam: `HasBrandName::brandName(string|Htmlable|Closure|null)` (`vendor/filament/filament/src/Panel/Concerns/HasBrandName.php:12`), `HasFavicon::favicon(string|Closure|null)` (`HasFavicon.php:11`), `HasBrandLogo::brandLogo(string|Htmlable|Closure|null)` (`HasBrandLogo.php:16`).

### `database/seeders/PapeisSeeder.php`

O papel `admin` recebe `$this->permissoesDoPainel('admin', $guard)` (`:57-58`), que é a matriz **inteira** do painel colhida por `App\Support\Paineis::permissoes('admin')`. Uma Page nova em `app/Filament/Admin/Pages/` entra nessa matriz sozinha.

**Consequência prática, e é a que evita conflito com a branch `feat/permissoes-de-telas-e-acoes`: o `PapeisSeeder.php` NÃO é editado nesta entrega.** As duas listas de subtração (`permissoesDeAdministracaoDoApp()` e `permissoesForaDoApp()`) recortam o painel `app`, e esta Page está no `admin`.

### `config/filament-shield.php`

`permissions.separator = ':'`, `permissions.case = 'pascal'`, `pages.prefix = 'view'`, `pages.subject = 'class'`, `pages.exclude = [Dashboard::class]`, `tabs.pages = true`. Logo a permission da Page é **`View:ConfiguracoesDoKit`**.

### `vendor/bezhansalleh/filament-shield` (4.3.1)

A trait é `BezhanSalleh\FilamentShield\Traits\HasPageShield` (`src/Traits/HasPageShield.php:10-37`) — em `Traits/`, **não** em `Concerns/`. Ela sobrescreve `canAccess()` (`:19-27`) para `$user->can($permission)` quando há permission e usuário, e cai em `parent::canAccess()` quando não há; e sobrescreve `shouldRegisterNavigation()` (`:14-17`).

### `tests/Pest.php` e `tests/Kit/InventarioDeTelasTest.php`

`telasDoKit()` (`tests/Pest.php:204-305`) é o inventário à mão dos CT-B, e `InventarioDeTelasTest` compara nos dois sentidos: tela registrada e ausente do inventário **reprova**. Logo `/admin/configuracoes-do-kit` **precisa** entrar em `telasDoKit()['admin']`, e ganha smoke de navegador de graça.

### `phpunit.xml`

Fixa `KIT_COR_PRIMARIA=""`, `KIT_DEMO=false`, `KIT_HUB=false`, os três rótulos de organização, e `LOG_KIT_DRIVER=monolog`. O motivo está escrito lá: a suíte roda contra a configuração DO KIT, não contra a do projeto de quem roda.

**Consequência de desenho**: o alinhamento de config no boot precisa ser **inerte** quando a tabela `settings` não existe ou está vazia. No `RefreshDatabase` o boot do provider acontece antes de as migrations rodarem, então a suíte inteira continua vendo os valores do `phpunit.xml` — e os 662 casos seguem verdes sem nenhuma edição. Quem quiser exercitar o settings alinha explicitamente no caso.

## Autorização

- **Policies**: nenhuma nova. Page não tem policy no Shield — a autorização é a permission `View:ConfiguracoesDoKit` consultada por `HasPageShield::canAccess()`.
- **Gates**: nenhum novo. O `Gate::before` do `master_global` (em `KitServiceProvider::configureGates()`) continua valendo e é o que dá acesso a ele sem permission nenhuma.
- **Middleware**: o do painel `admin`, sem alteração. A checagem re-roda em **todo** request Livewire — `SettingsPage` herda `CanAuthorizeAccess` (confirmado na doc do Filament 5 via `search-docs`, seção "Authorization and the Livewire request lifecycle").
- **Guards**: nenhum.
- **Permissão**: `View:ConfiguracoesDoKit`, gerada pelo `ShieldPermissionsSeeder` e atribuída ao papel `admin` pelo `PapeisSeeder` **sem edição de lista** (ver ADR-04). O `master_global` entra pelo `Gate::before`. `infra` e `panel_user` **não** recebem — a matriz deles é a dos painéis `infra` e `app`.
- **`canEdit()`**: devolve `static::canAccess()`. Uma permissão só, e o motivo está no ADR-04.

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| GET | `/admin/configuracoes-do-kit` | `filament.admin.pages.configuracoes-do-kit` | os do painel `admin` (`panel:admin`, `Authenticate`, …) + `abort_unless(canAccess())` de `CanAuthorizeAccess` |

Rota gerada pela descoberta (`discoverPages(in: app_path('Filament/Admin/Pages'))`, `AdminPanelProvider:73`). Nada a registrar à mão.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `App\Filament\Admin\Pages\ConfiguracoesDoKit` | Filament (`SettingsPage`, Livewire) | `/admin/configuracoes-do-kit` | navega entre 4 abas, preenche campos, escolhe cor no seletor e no `ColorPicker`, anexa favicon/logo/arte, salva com o botão ou `mod+s` | **Sim** — abas, `ColorPicker` (Alpine), `FileUpload` (FilePond) e a notificação de sucesso |
| Cabeçalho dos três painéis | Filament (render) | `/admin`, `/app`, `/infra` | apenas vê o nome/logo/favicon aplicados | Não |
| Telas de autenticação dos três painéis | Auth Designer (Blade) | `/admin/login`, … | apenas vê a arte aplicada | Não |

**Gate de CT-B**: a tabela é o gatilho. Vão ao navegador **apenas** os cenários que só o navegador prova: o `ColorPicker` e as abas executam JavaScript, e a aplicação visual da cor/logo/favicon é render. Preenchimento, validação, gravação, autorização na tela e a trilha de auditoria são **teste de componente Livewire** e ficam no `04`.

**Gate de tela de escrita**: esta é uma tela de escrita (é o `save()` do `SettingsPage`). O `04` tem cenário de **gravação por componente** obrigatório — `livewire(ConfiguracoesDoKit::class)->fillForm([...])->call('save')` + asserção no banco.

## Variáveis de Ambiente

Nenhuma chave nova é **obrigatória**. As que entram são a **semente e o plano B** do banco, e todas têm default:

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_COR_PRIMARIA` | (vazio) | **já existe** — nome da cor do Enum `Color` |
| `KIT_COR_PRIMARIA_HEX` | (vazio) | cor livre em hexadecimal; **vence** o nome quando as duas estão preenchidas |
| `KIT_HUB` | `false` | **já existe** — hub de navegação em cartões |
| `KIT_TABELA_PAGINACAO` | `10` | linhas por página no default de toda tabela |
| `KIT_TABELA_LISTRADA` | `true` | linhas listradas (zebra) |
| `KIT_TABELA_PERSISTIR_FILTROS` | `true` | filtro, busca, ordenação e busca por coluna sobrevivem à navegação |
| `KIT_TABELA_COLUNAS_REDIMENSIONAVEIS` | `true` | arrastar largura de coluna (macro do `asmit/resized-column`) |
| `SETTINGS_CACHE_ENABLED` | `false` | **já existe** em `config/settings.php` |
| `MAIL_*` | os do `.env.example` | **já existem** — semente da aba E-mail |

> **Coerção de booleano na fronteira**: `(bool) env('CHAVE', true)` é o mesmo defeito que `.ai/rules/config.md` documenta para inteiros — com `CHAVE=` (presente e vazia) o `env()` devolve string vazia, `(bool) ''` é `false` e o default `true` **nunca entra**. Os três booleanos novos usam `filter_var($bruto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $padrao`, que trata ausente e vazio igual e cai no default. O `KIT_HUB` existente fica como está: o default dele é `false`, então o defeito é inócuo lá.

## Eventos / Listeners / Observers

- **Eventos escutados**: `Spatie\LaravelSettings\Events\SavingSettings` — o único que carrega valor antigo e novo (`vendor/spatie/laravel-settings/src/Settings.php:191`).
- **Listener**: `App\Listeners\AuditarConfiguracoesDoKit`, registrado em `KitServiceProvider`. Escreve uma linha em `audits` por propriedade **alterada**, com `auditable_type = Spatie\LaravelSettings\Models\SettingsProperty::class` e `auditable_id` = o id da linha real daquela propriedade. Ver ADR-07.
- **Observers**: nenhum.

## Jobs / Queues

Nenhum. A gravação é sincrona dentro do request, e a trilha de auditoria é uma inserção.

## Impacto em Features Existentes

| Feature | O que pode quebrar e por quê |
|---|---|
| Cor primária dos três painéis | `CorPrimaria::paleta()` ganha um segundo caminho (hex). Se a precedência ou a tolerância a valor inválido regredir, **toda página** do projeto morre com `Error: Undefined constant` ou com lixo de `convertToOklch`. Cobertura: `tests/Kit/CorPrimariaTest.php` (5 casos existentes, todos precisam continuar verdes) |
| Identidade visual da organização | a cor do tenant é registrada em `FilamentColor::register()` no `bootUsing()` do `AppPanelProvider` e **vence** a do kit dentro de `/app/{slug}`. Se o alinhamento de config mexer na ordem, o cliente vê a cor da instalação. Cobertura: `tests/Tenancy/IdentidadeVisualTest.php` |
| TODA tabela dos três painéis | `configuraTable()` passa a ler config. Erro aqui derruba as telas dos plugins de terceiro (o comentário de `:204-214` registra oito telas de `/infra` em 500 por causa de uma configuração global) |
| Telas de autenticação | as nove chamadas de `media()` passam a receber `Closure`. Cobertura: `tests/Kit/TelasDeAutenticacaoTest.php`, `tests/Kit/BloqueioDeSessaoTest.php` |
| Inventário de telas dos CT-B | tela nova → `tests/Kit/InventarioDeTelasTest.php` reprova até a URL entrar em `telasDoKit()` |
| Matriz de permissões | permission nova entra na matriz do painel `admin`; se a Page fosse do painel `app`, exigiria a lista de subtração. Cobertura: `tests/Kit/PaineisTest.php` |
| Boot da aplicação | o alinhamento roda em **todo** request e **todo** comando artisan. Precisa ser inerte quando a tabela não existe e nunca lançar. Um `migrate` num banco vazio é o caso de teste |
| `kit:install --custom` | `aplicarSemBanco()` reescreve `APP_NAME` e `KIT_COR_PRIMARIA` no `.env`; com o banco vencendo, a resposta do usuário pareceria não ter efeito. Passo 7 fecha isso |

## Rollback

- **Migration de settings**: `database/settings/*_create_kit_settings.php` implementa `down()` com `$this->migrator->deleteIfExists('kit.<propriedade>')` para cada uma. Rodar `php artisan migrate:rollback` devolve a tabela ao estado anterior e o alinhamento volta a não achar nada — ou seja, o `.env` volta a ser a única fonte, sem editar código.
- **Sem feature flag**: um interruptor para "usar ou não o settings" seria uma terceira fonte da verdade, que é exatamente o problema que esta wiki existe para resolver. O desligamento é o `down()` da migration, e ele é completo por construção: sem linha na tabela, o alinhamento é no-op.
- **Reversão de dados**: os valores originais continuam no `.env` (é ele que semeia a migration), então nada é perdido.

## Dependências

- **Composer**: nenhuma nova. `spatie/laravel-settings` 3.9.0 e `filament/spatie-laravel-settings-plugin` 5.7.6 **já estão instalados**; `owen-it/laravel-auditing` 14.0.6 e `tapp/filament-auditing` 4.0.9 também.
- **NPM**: nenhuma.

## Riscos

| Risco | Mitigação |
|---|---|
| O alinhamento no boot lança e derruba a aplicação inteira (banco fora, tabela ausente, propriedade nova sem linha — `MissingSettings`) | `try/catch (Throwable)` envolvendo inclusive o `Schema::hasTable()`, `warning` no channel `configuracoes`, e retorno silencioso para o `.env`. Caso de teste próprio |
| Cor livre inválida derruba todo painel | validação por regex antes de usar (`/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/`), mesma tolerância documentada de `CorPrimaria` |
| `brandName` escalar congelado antes do alinhamento | medido nesta wiki com probe de tinker; corrigido passando `Closure` nos três painéis |
| Senha de SMTP em claro no banco | `encrypted()` do spatie + a permissão única do ADR-04 |
| Upload privado por default no Filament | `->disk('public')->visibility('public')` explícito nos três uploads; `storage:link` já roda no `kit:install` (`app/Console/Commands/KitInstall.php:353`) |
| Conflito de rebase com `feat/permissoes-de-telas-e-acoes` | `PapeisSeeder.php` **não** é editado; a Page nasce com `HasPageShield`, que é a convenção que aquela branch estabelece |
| Queda de performance por uma query a mais no boot | `SETTINGS_CACHE_ENABLED` existe e é documentado no README; e a leitura é **uma** query para o grupo inteiro (`getPropertiesInGroup`, `DatabaseSettingsRepository.php:24-33`) |

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` tem três channels do kit — `ai` (`:114`), `tenancy` (`:123`) e `autenticacao` (`:132`) — e nenhum de configuração. Os três seguem a mesma forma, e o bloco de comentário `:85-112` explica por que: driver vindo de `LOG_KIT_DRIVER` (o `phpunit.xml` fixa `monolog`) e `handler` sempre presente, porque `LOG_KIT_DRIVER=null` falha em silêncio e cai no emergency logger.

### Decisão

Channel novo, `configuracoes`, com a **mesma forma dos três existentes** — inclusive o `handler`, sem o qual a suíte escreveria no `storage/logs` de quem a roda:

```php
'configuracoes' => [
    'driver'               => env('LOG_KIT_DRIVER', 'daily'),
    'handler'              => NullHandler::class,
    'path'                 => storage_path('logs/configuracoes.log'),
    'level'                => env('LOG_LEVEL', 'debug'),
    'days'                 => 14,
    'replace_placeholders' => true,
],
```

Nome no plural e sem o sufixo da feature porque é o que os três vizinhos fazem (`ai`, `tenancy`, `autenticacao`), e porque o arquivo vai continuar servindo às wikis de register e de login social.

## Estrutura de Implementação

### 1. Channel de log

> Skills: `laravel-best-practices`

- **Path**: `config/logging.php`
- Acrescentar o channel `configuracoes` logo depois de `autenticacao` (`:140`), com a forma acima e um comentário curto dizendo que ele segue a nota de `:85-112`.
- **Logs**: nenhum (é a definição do canal).

### 2. A classe de settings

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php` (namespace `App\Settings`)
- `final class ConfiguracoesDoKit extends Spatie\LaravelSettings\Settings`
- `public static function group(): string { return 'kit'; }`
- `public static function encrypted(): array { return ['mail_password']; }`
- 21 propriedades públicas tipadas, nesta ordem:

| Propriedade | Tipo | Chave de config alinhada | RQ |
|---|---|---|---|
| `nome_da_aplicacao` | `string` | `app.name` | RQ-05 |
| `cor_primaria` | `?string` | `kit.cor_primaria` | RQ-06 |
| `cor_primaria_hex` | `?string` | `kit.cor_primaria_hex` | RQ-07 |
| `logo` | `?string` | `kit.identidade.logo` | RQ-12 |
| `favicon` | `?string` | `kit.identidade.favicon` | RQ-04 |
| `arte_do_login` | `?string` | `kit.identidade.arte_do_login` | RQ-13 |
| `mail_mailer` | `string` | `mail.default` | RQ-08 |
| `mail_host` | `?string` | `mail.mailers.smtp.host` | RQ-08 |
| `mail_port` | `?int` | `mail.mailers.smtp.port` | RQ-08 |
| `mail_scheme` | `?string` | `mail.mailers.smtp.scheme` | RQ-08 |
| `mail_username` | `?string` | `mail.mailers.smtp.username` | RQ-08 |
| `mail_password` | `?string` | `mail.mailers.smtp.password` | RQ-08 |
| `mail_from_address` | `?string` | `mail.from.address` | RQ-08 |
| `mail_from_name` | `?string` | `mail.from.name` | RQ-08 |
| `paginacao_padrao` | `int` | `kit.tabelas.paginacao` | RQ-11 |
| `tabela_listrada` | `bool` | `kit.tabelas.listrada` | RQ-11 |
| `persistir_filtros` | `bool` | `kit.tabelas.persistir_filtros` | RQ-11 |
| `colunas_redimensionaveis` | `bool` | `kit.tabelas.colunas_redimensionaveis` | RQ-11 |
| `hub_de_navegacao` | `bool` | `kit.hub` | RQ-16 |
| `rotulo_da_organizacao` | `string` | `kit.tenancy.label` | RQ-03 |
| `rotulo_das_organizacoes` | `string` | `kit.tenancy.label_plural` | RQ-03 |

- Método `public function aplicarNaConfig(): void` — o **mapa** propriedade → chave de config, num só lugar, e a única coisa que o boot chama. Ele existe aqui, e não num provider, porque a classe é a dona da lista de propriedades: acrescentar propriedade e esquecer o mapa fica visível no mesmo arquivo.
- PHPDoc de classe: explica o grupo `kit`, a fonte da verdade (banco vence, `.env` semeia), e diz **explicitamente** que isto não é settings de organização (RQ-18), apontando para `app/Filament/Admin/Resources/Tenants/`.
- **Logs**: `Log::channel('configuracoes')->debug('[ConfiguracoesDoKit@aplicarNaConfig] Configuração do processo alinhada com o banco | grupo: kit', ['chaves' => array_keys($mapa)])`.

### 3. As chaves novas de `config/kit.php`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php`
- Acrescentar, com bloco de comentário no estilo do arquivo:
  - `cor_primaria_hex` → `env('KIT_COR_PRIMARIA_HEX')`, explicando que ela **vence** `cor_primaria` quando preenchida, e que valor inválido é ignorado (mesma tolerância do nome).
  - `identidade` → `['logo' => null, 'favicon' => null, 'arte_do_login' => 'images/auth/login.svg']`. Sem `env()`: são caminhos de arquivo que só a tela grava, e o default da arte é o que os três painéis usam hoje.
  - `tabelas` → as quatro chaves, com `NumeroDoEnv::positivo(env('KIT_TABELA_PAGINACAO'), 10)` para a paginação e `filter_var(..., FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true` para os três booleanos, com uma frase citando `.ai/rules/config.md`.
- **Path**: `.env.example` — as quatro chaves de tabela e a de cor livre, comentadas, no bloco do kit.
- **Logs**: nenhum (config é declarativo).

### 4. A página de settings

> Skills: `laravel-best-practices`, `tailwindcss-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- Criar com `php artisan make:filament-settings-page ConfiguracoesDoKit ConfiguracoesDoKit --panel=admin --no-interaction` e então ajustar; o comando existe (`vendor/filament/spatie-laravel-settings-plugin/src/Commands/MakeSettingsPageCommand.php`).
- `class ConfiguracoesDoKit extends Filament\Pages\SettingsPage`
- `use BezhanSalleh\FilamentShield\Traits\HasPageShield;`
- `protected static string $settings = \App\Settings\ConfiguracoesDoKit::class;`
- `protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';`
- `protected static ?string $title = 'Configurações do kit';`, `$navigationLabel` igual, `$navigationSort` no fim do grupo.
- `public function canEdit(): bool { return static::canAccess(); }` — **instância**, conforme `SettingsPage.php:248`.
- `form(Schema $schema): Schema` com `Tabs::make()->columnSpanFull()` e quatro `Tab`:
  - **Identidade** — `TextInput` (nome, `required`), `Select` (cor, opções de `CustomizadorDaInstalacao::CORES` com `'' => 'Padrão do Filament (âmbar)'`), `ColorPicker` (cor livre, `helperText` dizendo que ela vence a seleção), três `FileUpload` (logo, favicon, arte do login) com `->disk('public')->directory('kit')->visibility('public')->image()`.
  - **E-mail** — `Select` do mailer (`log`, `array`, `smtp`), e os seis campos de SMTP `->visible(fn (Get $get) => $get('mail_mailer') === 'smtp')`, com `mail_password` em `->password()->revealable()`, mais `mail_from_address` (`->email()`) e `mail_from_name` sempre visíveis.
  - **Tabelas** — `TextInput::make('paginacao_padrao')->numeric()->minValue(1)->maxValue(100)->required()` e três `Toggle`.
  - **Kit** — `Toggle` do hub, e os dois `TextInput` de rótulo da organização, com `helperText` dizendo que é o vocabulário da instalação e **não** a configuração de uma organização (RQ-18).
- **Logs**:
  - `afterSave()` → `Log::channel('configuracoes')->info('[ConfiguracoesDoKit@afterSave] Configurações do kit salvas | usuario: '.auth()->id(), ['user_id' => auth()->id(), 'alteradas' => array_keys($this->data ?? [])])`
  - Sem log em `beforeFill`: abrir a tela não é evento, e um `info` por abertura é o ruído que o comentário de `config/logging.php:90-95` mediu em 1,1 MB por dia.

### 5. Migration de settings + alinhamento no boot + auditoria

> Skills: `laravel-best-practices`

- **Path**: `database/settings/2026_08_24_000000_create_kit_settings.php`
  - `php artisan make:settings-migration CreateKitSettings`
  - `up()`: `$this->migrator->add('kit.<propriedade>', <valor semeado da config>)` para as 21. **O valor semeado vem de `config(...)`**, que vem do `.env` — é isso que faz a resposta do `kit:install` chegar ao banco sem nenhum código de sincronização.
  - `down()`: `deleteIfExists` para as 21.
- **Path**: `config/settings.php` — registrar `App\Settings\ConfiguracoesDoKit::class` em `'settings'`, com um comentário dizendo que a descoberta automática também acharia, mas que o registro explícito é o que sobrevive a um `discovered_settings_cache_path` velho.
- **Path**: `app/Providers/KitServiceProvider.php`
  - `boot()` ganha `$this->configureSettingsDoKit();` — **antes** de `configuraFilamentGlobal()`, porque a configuração global de tabela lê as chaves de `kit.tabelas`.
  - O método:
    ```php
    private function configureSettingsDoKit(): void
    {
        try {
            if (! Schema::hasTable(config('settings.repositories.database.table') ?? 'settings')) {
                return;
            }

            app(ConfiguracoesDoKit::class)->aplicarNaConfig();
        } catch (Throwable $e) {
            Log::channel('configuracoes')->warning(
                '[KitServiceProvider@configureSettingsDoKit] Configuração do banco ignorada, valendo o .env | motivo: '.$e->getMessage(),
                ['exception' => $e],
            );
        }
    }
    ```
    O `Schema::hasTable()` **dentro** do `try` não é preciosismo: num banco inexistente ele lança antes de responder, e é isso que acontece no primeiro `migrate` de uma instalação nova.
  - Registrar o listener: `Event::listen(SavingSettings::class, AuditarConfiguracoesDoKit::class);`
- **Path**: `app/Listeners/AuditarConfiguracoesDoKit.php`
  - `handle(SavingSettings $evento): void`
  - Sai cedo se `$evento->settings` não é `ConfiguracoesDoKit`, se `config('audit.enabled')` é falso, ou se a tabela `audits` não existe.
  - Diffa `$evento->properties` contra `$evento->originalValues`; para cada propriedade alterada, resolve o id da linha em `settings` (`group = 'kit'`, `name = <propriedade>`) e grava `OwenIt\Auditing\Models\Audit`:
    - `auditable_type` = `Spatie\LaravelSettings\Models\SettingsProperty::class`, `auditable_id` = o id resolvido — **model Eloquent real**, porque a listagem de `/infra/audits` faz `->with(['user','auditable'])` (`vendor/tapp/filament-auditing/src/Filament/Resources/Audits/Tables/AuditsTable.php:38`) e um `auditable_type` que não é model derruba a tela.
    - `event` = `'settings-updated'` — **não** `'updated'`, de propósito: `RestoreAuditAction` só fica visível com `event === 'updated'` (`vendor/tapp/filament-auditing/src/Filament/Actions/RestoreAuditAction.php:46`) e o restore faria `$record->fill(['nome_da_aplicacao' => ...])->save()` numa linha cujas colunas são `group/name/payload` — SQL inválido. Ver ADR-07.
    - `old_values` / `new_values` = `[<nome da propriedade> => <valor>]`, para o nome aparecer na coluna da listagem.
    - `tags` = `'configuracoes-do-kit'`.
    - Propriedade cifrada (`mail_password`) entra com o valor **mascarado** (`'••••••'`), nunca em claro — a trilha registra QUE mudou, não o segredo.
  - **Logs**: `Log::channel('configuracoes')->info('[AuditarConfiguracoesDoKit@handle] Trilha de alteração gravada | propriedades: '.count($alteradas), ['alteradas' => array_keys($alteradas), 'user_id' => auth()->id()])` e `warning` quando a linha de `settings` não é encontrada (propriedade nova sem migration rodada).

### 6. Os três painéis passam a ler o settings

> Skills: `laravel-best-practices`

- **Paths**: `app/Providers/Filament/AdminPanelProvider.php`, `AppPanelProvider.php`, `InfraPanelProvider.php`
- Trocar por `Closure` **todas** as leituras que hoje são escalares:
  - `->brandName(fn (): string => config('app.name').' • Admin')` (e as variantes de `app` e `infra`)
  - acrescentar `->favicon(fn (): ?string => IdentidadeDoKit::favicon())`
  - acrescentar `->brandLogo(fn (): ?string => IdentidadeDoKit::logo())` e `->brandLogoHeight('2rem')`
  - nas nove chamadas do Auth Designer: `->media(fn (): string => IdentidadeDoKit::arteDoLogin(), alt: config('app.name'))` — **conferir a assinatura de `AuthPageConfig::media()` antes**; se ela não aceitar `Closure`, o valor é resolvido na hora, e como o `AuthDesignerPlugin` é registrado no `->plugins([...])` (avaliado na construção do painel), isso é aceitável: a arte só muda por upload, e o `Panel` é construído por request.
- **Path novo**: `app/Support/IdentidadeDoKit.php` — três métodos estáticos (`logo()`, `favicon()`, `arteDoLogin()`) que resolvem caminho de disco público para URL, com fallback. Existe pela mesma razão de `CorPrimaria`: a resolução tem uma guarda (arquivo declarado mas ausente no disco → cai no default em vez de servir 404 no `<head>` de toda página), e repeti-la em nove lugares é onde ela deixa de existir num deles.
- **Logs**: `Log::channel('configuracoes')->warning('[IdentidadeDoKit@resolver] Arquivo declarado e ausente no disco, usando o padrão | caminho: '.$caminho, ['caminho' => $caminho, 'chave' => $chave])` — uma vez por resolução falha, com `debug` e não `warning` se virar ruído.

### 7. Cor, tabelas e `kit:install --custom`

> Skills: `laravel-best-practices`

- **Path**: `app/Support/CorPrimaria.php`
  - `paleta()` ganha a precedência, nesta ordem: hex válido → nome válido do Enum → `[]` (padrão do Filament).
  - O hex é validado por `preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex)`. Inválido é **ignorado**, e cai para o nome — nunca lança. Docblock atualizado explicando que `Color::generatePalette()` (`vendor/filament/support/src/Colors/Color.php:663`) chama `convertToOklch()` e que valor não-cor não tem tratamento lá.
  - Devolve `['primary' => $hex]` (string) para o hex: o `ColorManager` chama `Color::generatePalette()` sozinho quando recebe string, exatamente como o comentário de `AppPanelProvider.php:124-126` já registra.
- **Path**: `app/Providers/Concerns/ConfiguraFilamentGlobal.php`
  - `configuraTable()` passa a ler `config('kit.tabelas.*')`: paginação, `striped()` condicional, as quatro persistências condicionais, e `aplicaMacrosDeColuna()` gateada por `colunas_redimensionaveis`.
  - **O TODO de `:35-38` é substituído** por um bloco apontando para `/admin/configuracoes-do-kit` e dizendo que densidade de tabela não existe no Filament 5 (com a varredura como evidência).
- **Path**: `app/Support/CustomizadorDaInstalacao.php`
  - `aplicarSemBanco()` (`:237-253`) passa a gravar **também** no settings quando a tabela existe, depois de reescrever o `.env`. Sem isso, o `kit:install --custom` num projeto instalado reescreve o `.env` e a tela continua mostrando o valor antigo — a resposta do usuário pareceria não ter efeito. É o caso concreto do conflito entre as duas fontes, e o lugar certo de resolvê-lo.
  - `itensManuais()` (`:321-332`): a linha "Arte do login" sai da lista de itens manuais e o resumo passa a citar `/admin/configuracoes-do-kit`.
- **Logs**: `Log::channel('configuracoes')->info('[CustomizadorDaInstalacao@aplicarSemBanco] Nome e cor propagados para o settings | cor: '.$cor, ['nome' => $nome, 'cor' => $cor])`.

### 8. Permissões

> Skills: `laravel-best-practices`

- **Nenhum arquivo de seeder é editado.** A permission `View:ConfiguracoesDoKit` é gerada pelo `ShieldPermissionsSeeder` (que roda `shield:generate --all --panel=admin`) e atribuída ao papel `admin` por `PapeisSeeder::permissoesDoPainel('admin', ...)`, que colhe a matriz inteira do painel.
- Executar, nesta ordem, e registrar a contagem antes/depois:
  ```bash
  php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
  php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
  ```
- **Verificar** que `infra` e `panel_user` **não** recebem a permission (a matriz deles é de outros painéis), e que `admin` recebe.
- **Logs**: nenhum (os seeders já logam pelo `$this->command`).

### 9. Documentação e inventário

> Skills: —

- **Path**: `tests/Pest.php` — `/admin/configuracoes-do-kit` entra em `telasDoKit()['admin']`, com comentário curto.
- **Path**: `README.md` — seção nova "Configurações do kit em `/admin`" com: as quatro abas e o que cada campo faz; a **regra da fonte da verdade** (banco vence, `.env` semeia, `--force` re-semeia); a permissão e em que papel ela nasce; a trilha de auditoria e onde vê-la (`/infra/audits`, evento `settings-updated`); o aviso de RQ-18 (não é settings de organização); `SETTINGS_CACHE_ENABLED`; e o que ficou **fora** com o motivo. O TODO de `:1257` é substituído.
- **Path**: `README.en.md` — a mesma seção, e o TODO de `:1221` substituído.
- **Path**: `CHANGELOG.md` — entrada da versão.
- **Path**: `config/kit.php` — `version` bumped.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> 1. Reutilizar antes de criar: o pacote de settings, o de audits, `CustomizadorDaInstalacao::CORES`, `NumeroDoEnv`, os channels de log e o `HasPageShield` do vendor **já existem**. Nada novo é instalado.
> 2. `config()` continua sendo a interface de leitura de todo consumidor — o alinhamento em memória é o que torna isso possível com um arquivo em vez de dez.
> 3. Uma classe de settings, uma página, um listener, um resolvedor de identidade. Quatro arquivos novos de código.
> 4. `PapeisSeeder.php` não é editado — a matriz do painel já faz o trabalho.
> 5. Atalhos deliberados levam comentário `ponytail:`.
>
> **Caveman** não se aplica a arquivos wiki, código, commits nem PRs.

## Mapeamentos

### Propriedade → chave de config

Ver a tabela do passo 2. É a **única** cópia dessa lista; o código a implementa em `ConfiguracoesDoKit::aplicarNaConfig()`.

### Precedência da cor primária

| `kit.cor_primaria_hex` | `kit.cor_primaria` | Resultado |
|---|---|---|
| `#7c3aed` | qualquer | `['primary' => '#7c3aed']` |
| inválido (`azul`, `#12`) | `Blue` | `['primary' => Color::Blue]` |
| inválido | inválido/vazio | `[]` (padrão do Filament) |
| vazio | `Blue` | `['primary' => Color::Blue]` |
| vazio | vazio | `[]` |

Dentro de `/app/{slug}`, a cor da organização continua vencendo as duas — ela é registrada mais tarde no ciclo (`FilamentColor::register()` no `bootUsing()` do `AppPanelProvider`).

### Fonte da verdade

| Situação | Quem vence |
|---|---|
| Banco tem a linha | **banco** |
| Banco não tem a linha (propriedade nova, migration não rodada) | `.env` / default da config, e um `warning` no channel |
| Tabela `settings` não existe (antes do `migrate`) | `.env`, silenciosamente |
| Banco inacessível | `.env`, com `warning` |
| `kit:install` (instalação nova) | `.env` → a migration de settings **semeia** o banco com ele |
| `kit:install --force` | apaga o SQLite, reescreve o `.env` e re-migra → o banco é re-semeado do `.env` novo |
| `kit:install --custom` (projeto já instalado) | reescreve o `.env` **e** grava no settings (passo 7) — as duas fontes ficam iguais |

## Testes

> Ver `04-casos-de-teste.md` para os cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários de UI.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` (0 erros)
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` (662 na base — não deixar cair)
- [ ] `composer test:browser`
- [ ] `php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder && php artisan db:seed --class=Database\\Seeders\\PapeisSeeder` sem erro, e `View:ConfiguracoesDoKit` presente no papel `admin`
- [ ] `php artisan migrate:rollback --path=database/settings` volta e `migrate` refaz, sem quebrar o boot

## Commits

- `:sparkles: feat(settings): pagina de configuracoes do kit em /admin`
- `:sparkles: feat(settings): identidade, e-mail e defaults de tabela vindos do banco`
- `:lock: feat(settings): trilha de auditoria das alteracoes de configuracao`
- `:white_check_mark: test(settings): casos de backend e de navegador`
- `:memo: docs(settings): README pt/en e wiki da feature`
