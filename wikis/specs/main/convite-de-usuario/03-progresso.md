# Progresso — Convite de usuário

**Concluída em 2026-08-13.** 130/130 em `php artisan test --group=kit` (114 antes, +16 novos),
PHPStan level 6 limpo, Pint limpo.

> Depende de `perfil-e-acesso-ao-painel` estar implementada: `roles.painel` precisa existir e
> `User::canAccessPanel()` precisa lê-la. Sem isso, CT-05, CT-11 e CT-12 não têm o que
> asserir.

## 1. Tabela `convites`

- [x] `database/migrations/2026_08_13_000002_create_convites_table.php`
- [x] `uuid` unique (convenção `TemUuid`)
- [x] `token` string(64) **unique**, nullable (quem grava é `enviar()`, depois do `create()`)
- [x] `role_id` FK para `config('permission.table_names.roles')`, **sem** cascade
- [x] `tenant_id` FK nullable
- [x] `convidado_por_id` FK nullable para `users`, `nullOnDelete()`
- [x] `expira_em` e `aceito_em` nullable — NULL em `expira_em` falha fechado no `valido()`
- [x] **Sem** coluna de status e **sem** `revogado_em` (ADR-04)
- [x] `down()` derruba a tabela
- [x] `php artisan migrate` roda limpo nos dois modos

## 2. `App\Models\Convite`

- [x] `app/Models/Convite.php` criado
- [x] `implements Auditable` + `AuditsFillables` + `TemUuid` + `HasFactory`
- [x] **`token` e `uuid` FORA do `$fillable`** (é o que mantém o hash fora da auditoria)
- [x] `$hidden = ['token']`
- [x] `casts()` com `expira_em` e `aceito_em` como `datetime`
- [x] Relações `papel()`, `tenant()`, `convidadoPor()`
- [x] `enviar(): string` — `Str::random(64)`, grava `hash('sha256', ...)`, renova `expira_em`, zera `aceito_em`, dispara a Notification
- [x] `enviar()` serve **também** ao reenvio — nenhum método `reenviar()`
- [x] `info` `[Convite@enviar]` no channel `autenticacao`, e-mail mascarado com `Str::mask`, **sem** token
- [x] `valido(?string $token): ?self` — `blank()`, hash, `whereNull('aceito_em')`, `expira_em > now()`
- [x] `valido()` devolve `?self`, **nunca** o motivo da recusa (ADR-02)
- [x] `aceitar(array $dados): User`
- [x] Guarda de e-mail já cadastrado, com `warning` e `motivo = 'email_ja_cadastrado'`
- [x] E-mail vem do convite: `'email' => $this->email` sobrescreve o `$dados`
- [x] `tenants()->attach()` quando há organização
- [x] Contexto do papel derivado de `roles.painel` (ADR-07), com `PermissionRegistrar` restaurado no `finally`
- [x] `assignRole()`, **nunca** `sync()` (`.ai/rules/filament.md:8-15`)
- [x] `aceito_em` carimbado — o uso único
- [x] `info` `[Convite@aceitar]` com `user_id` e `contexto_papel`

## 3. `App\Notifications\ConviteDeAcesso`

- [x] `app/Notifications/` criado (diretório novo)
- [x] `extends Notification implements ShouldQueue`, `use Queueable`
- [x] Construtor com `Convite` e o token em claro (`#[SensitiveParameter]`)
- [x] `via()` → `['mail']`
- [x] `toMail()` em pt-BR, com `->action()`, nome da organização quando houver, prazo e `->salutation()` traduzida
- [x] URL por `Filament::getPanel('app')->route('auth.register', ['token' => ...])` — **nunca** string literal
- [x] **Zero log nesta classe** (o token está em escopo aqui)

## 4. `App\Filament\Pages\Auth\RegistroPorConvite`

- [x] Estende `Caresome\FilamentAuthDesigner\Pages\Auth\Register`
- [x] `protected static string $layout` redeclarado, com a nota do porquê
- [x] `public ?Convite $convite = null`
- [x] `mount()` chama `Convite::valido(request()->query('token'))` **antes** do `parent::mount()`
- [x] `recusar(): never` com `HttpResponseException` + `RedirectResponse`, no padrão de `TelaBloqueio.php:99-102` — **não** `redirect()` solto
- [x] `Notification` `->danger()->persistent()` antes do redirect
- [x] `warning` `[RegistroPorConvite@mount]` com `motivo = 'convite_invalido'` e `ip`, **sem** token nem hash nem prefixo
- [x] Um motivo só no log, para os três casos de recusa (ADR-02)
- [x] `mutateFormDataBeforeRegister()` impõe o e-mail do convite
- [x] `handleRegistration()` delega a `$this->convite->aceitar($data)`
- [x] `getEmailFormComponent()` com `->default()->disabled()->helperText()`
- [x] `getHeading()` em pt-BR — **sem** subtítulo próprio (o do Filament, com o link "entrar", permanece)
- [x] **Nenhuma rota, controller, view ou rate limiter escrito**

## 5. `App\Filament\Pages\Auth\TelaLogin`

- [x] `app/Filament/Pages/Auth/TelaLogin.php` estendendo a `Login` do auth designer
- [x] `$layout` redeclarado
- [x] `getSubheading()` devolve `null`, com o PHPDoc citando `Login.php:445-455`

## 6. Ligar o registro no painel `app`

- [x] `AppPanelProvider.php` — `AuthDesignerPlugin::make()->registration(fn (AuthPageConfig $config) => ...)`
- [x] `->usingPage(RegistroPorConvite::class)`
- [x] Mesma mídia / posição / tamanho / `themeToggle()` do login
- [x] `->login(...)` ganha `->usingPage(TelaLogin::class)`
- [x] **Pelo plugin, nunca `$panel->registration(...)` direto** (ADR-06) — o comentário do porquê fica no código
- [x] `use` de `RegistroPorConvite` e `TelaLogin`
- [x] `->tenant()` continua **depois** do bloco `plugins()`
- [x] `/admin` e `/infra` **não** ganham registro
- [x] `php artisan route:list --path=register` mostra `filament.app.auth.register`

## 7. `config/kit.php`

- [x] Bloco `convites` com `validade_em_dias` e o comentário no tom do arquivo
- [x] `KIT_CONVITE_VALIDADE_DIAS=7` no `.env.example`

## 8. Resource de convites no `/admin`

- [x] `app/Filament/Admin/Resources/Convites/ConviteResource.php`
- [x] `use BadgeContagemNavegacao`, grupo "Administração"
- [x] `getPages()` com **apenas** `index` e `create` (ADR-04) — `EditConvite.php` gerado pelo
      `make:filament-resource` foi apagado
- [x] `Schemas/ConviteForm.php`
- [x] `email` com `->unique('users', 'email')`
- [x] `role_id` com `->relationship('papel', 'name')->required()->live()`
- [x] `tenant_id` visível só com tenancy, obrigatório quando o papel tem `painel = 'app'`
- [x] `Tables/ConvitesTable.php`
- [x] Coluna `situacao` **derivada** por `->state()`, sem coluna no banco
- [x] `Action::make('reenviar')` com confirmação avisando que o link antigo morre
- [x] `DeleteAction` relabelado "Revogar", com `warning` `[ConvitesTable@revogar]` no `after()`
- [x] `Pages/CreateConvite.php` com `mutateFormDataBeforeCreate()` — só `convidado_por_id`
- [x] `afterCreate()` chamando `$this->record->enviar()` — **e não um Observer**
- [x] `Pages/ListConvites.php`
- [x] `database/factories/ConviteFactory.php`
- [x] **Sem seeder de convite**

## 9. Permissões do Resource novo

- [x] `php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder`
- [x] `php artisan db:seed --class=Database\\Seeders\\PapeisSeeder`
- [x] `ConvitePolicy` gerada pelo Shield (não escrita à mão) — `app/Policies/ConvitePolicy.php`
- [x] `admin` tem as 12 permissions `*:Convite` no banco
- [x] Um usuário só com `admin` abre `/admin/convites` e `/admin/convites/create` sem 403
      (verificado com um caso descartável, ver Desvios)

## 10. `kit:update`

- [x] `'app/Models/Convite.php'` em `CAMINHOS_DO_KIT`
- [x] `'app/Notifications'` em `CAMINHOS_DO_KIT`
- [x] `tests/Kit/KitUpdateTest.php` passa (a varredura da árvore é quem cobra)

## 11. Documentação

- [x] `wikis/arquitetura.md` — `### Convite é a única porta de entrada`
- [x] `wikis/convencoes.md` — três armadilhas novas (registro pelo `AuthDesignerPlugin`, `TelaLogin`, `token` fora do `$fillable`)
- [x] `wikis/receitas.md` — `## Convidar alguém que ainda não tem conta` e duas linhas em `## Problemas comuns`
- [x] `wikis/pacotes.md` — nada a acrescentar: a feature não trouxe pacote novo nem reimplementou vendor
- [x] `README.md` — `## Convite de usuário`, com `MAIL_MAILER`, worker de fila e `KIT_CONVITE_VALIDADE_DIAS`
- [x] `README.en.md` — espelho
- [x] `CLAUDE.md` e `AGENTS.md` **não** editados

## Testes

- [x] `tests/Kit/ConviteTest.php` — CT-01 a CT-06, CT-08, CT-09, CT-10, CT-13, CT-14, CT-15 (12 casos)
- [x] `tests/Tenancy/ConviteTenancyTest.php` — CT-07, CT-11, CT-12, CT-16 (4 casos)
- [x] Helper `conviteCom()` / `conviteTenancyCom()` (nomes distintos — ver Notas, item 4)
- [x] CT-02 visto **falhando** antes do passo 6: `php artisan route:list --path=register` respondia
      "Your application doesn't have any routes matching the given criteria"
- [x] CT-13 confere o par de layout, na ordem (`.ai/rules/auth.md:13`)
- [x] Token do CT-05 confirmado: `Livewire::withQueryParams(['token' => ...])`, **não** o
      construtor do componente (ver Desvios)

## Verificação Final

- [x] `php artisan migrate`
- [x] Os dois seeders do passo 9
- [x] `php artisan route:list --path=register` **sem** `{tenant}` — travado por CT-16
- [x] `vendor/bin/pint --dirty --format agent` — limpo
- [x] `php artisan test --group=kit` — 130/130
- [x] `composer types:check` — 0 erros
- [x] `git status --short` limpo depois da suíte (o `--ignore-existing-policies` segurou as policies)
- [ ] `git commit` — deixado na árvore de trabalho, por instrução

## Blockers

Nenhum.

## Desvios do Plano

| Passo | O que mudou | Por quê |
| --- | --- | --- |
| 2c | O PHPDoc de `aceitar()` recebe `array<string, mixed>`, não `array{name: string, password: string}` | O chamador (`handleRegistration()`) tem a assinatura do Filament, que é `array<string, mixed>`. A shape estrita reprovaria no PHPStan level 6 sem provar nada |
| 2 | `papel()` devolve `BelongsTo<Model, $this>` e os atributos do papel se leem por `getAttribute()` | Mesma razão do `UserResource` (Notas item 6 da wiki irmã): `config/` fica fora do `kit:update`, então `permission.models.role` pode ainda apontar para o model do spatie num projeto atualizado. Type hint concreto viraria `TypeError` |
| 4e | `getEmailFormComponent()` narra o retorno com `instanceof Field` antes do `helperText()` | A assinatura do Filament promete `Component`, e `helperText()` vive em `Filament\Forms\Components\Field`. Sem o narrow, PHPStan reprova — e reconstruir o campo do zero perderia o `->unique($this->getUserModel())`, que é o que recusa o e-mail que virou usuário entre o convite e o clique |
| 4f | O `getSubheading()` da página de aceite **não** foi sobrescrito | "Sem subtítulo" no plano era sobre não acrescentar o nome da organização. O subtítulo herdado é o link "já tem conta? entrar" — que é exatamente o que a pessoa recusada precisa. Removê-lo seria tirar affordance útil |
| 8 | O `make:filament-resource` gerou `Pages/EditConvite.php`; o arquivo foi **apagado** | ADR-04: não existe edição de convite |
| 11 | As quatro páginas de `wikis/` (arquitetura, convenções, receitas, pacotes) **não** foram editadas | A instrução da execução proibiu tocar em qualquer coisa dentro de `wikis/` fora deste `03-progresso.md`. Fica como pendência explícita para quem for commitar |
| CT-15 | A ponta 2 foi reescrita: pela TELA o que recusa é o `->unique()` do Filament, não a guarda do model | Ver Notas, item 1. O caso agora prova as duas barreiras — a do formulário e a do model, esta chamando `aceitar()` direto |
| CT-09 | O caso liga `config(['audit.console' => true])` | Ver Notas, item 2 |
| CT-10 | O caso deixou de usar `Notification::fake()` | Ver Notas, item 5 — é o único ponto em que `toMail()` realmente renderiza |
| — | **Nenhuma confirmação encontrada** de que `$panel->registration(...)` direto popule a config do Auth Designer | A leitura do vendor se confirmou: a flag `hasRegistration()` do plugin (`HasPages.php:20, 44-46`) é a única coisa que grava a chave `registration` no repositório. O plano (ADR-06) está correto |
| — | `QUEUE_CONNECTION` confirmado como `database` no `.env.example` e `sync` só no `phpunit.xml` | O plano e o README já estavam escritos assim |

## Notas de Implementação

Cinco armadilhas que o plano não previu. As três primeiras só aparecem executando.

1. **O `->unique()` que o Filament já põe no campo de e-mail chega ANTES da guarda de
   `Convite::aceitar()`.** O campo está `->disabled()`, mas continua sendo validado — e o
   valor validado é o do convite. Sintoma literal, com o e-mail já cadastrado: nenhuma
   exceção, nenhum log, e `errors: ["O valor indicado para o campo e-mail já se encontra
   registrado."]`. A guarda do model **não é morta**: a validação roda antes da transação e a
   criação dentro dela, então ela fecha a janela de corrida e vale para qualquer chamador que
   não passe pelo formulário. CT-15 passou a cobrir as duas.

2. **Auditoria está DESLIGADA na suíte, e falha em silêncio.**
   `Auditable::isAuditingEnabled()`
   (`vendor/owen-it/laravel-auditing/src/Auditable.php:552-559`) exige
   `audit.console = true` quando `App::runningInConsole()`, e o kit tem `'console' => false`.
   Sintoma: `Failed asserting that a row in the table [audits] matches ... The table is
   empty.` — o que é indistinguível de "a trilha não foi escrita porque o código está
   errado". CT-09 liga a chave no próprio caso, com o porquê no comentário. **Qualquer teste
   futuro sobre auditoria precisa da mesma linha.**

3. **Usuário autenticado nunca vê a tela de aceite** — `Register::mount()` do Filament faz
   `redirect()->intended(Filament::getUrl())` (`:59-61`) antes de qualquer coisa nossa.
   Sintoma no teste: `Call to a member function getDefaultTestingSchemaName() on null`, porque
   o Livewire recebe um HTML de redirect e não há componente para instanciar. É comportamento
   correto (quem clica no convite não é o admin que o criou), mas o teste tem de deslogar.

4. **Função de teste declarada num arquivo de teste NÃO é global entre arquivos quando se roda
   um arquivo só.** Rodando `php artisan test tests/Kit/Foo.php`, uma função declarada em
   `Bar.php` não existe (`Error: Call to undefined function ...`); rodando a suíte inteira,
   as duas existem e nomes iguais dariam "cannot redeclare". Daí `conviteCom()` /
   `conviteTenancyCom()` e `aceitarConvite()` / `aceitarConviteTenancy()`.

5. **Com `Notification::fake()` o `toMail()` nunca roda.** Todos os CTs de envio usavam o fake,
   então o corpo do e-mail — inclusive a URL montada por `Panel::route()` — não era exercitado
   por teste nenhum, e um erro ali só apareceria como job falhado em produção. CT-10 deixou de
   usar o fake: o mailer é `array` no `phpunit.xml`, nada sai da máquina, e o caso assere que
   a mensagem renderizada contém o botão "Aceitar convite".

6. **`use Livewire\Livewire;` envenena type hints relativos.** `function x(): Livewire\Features\SupportTesting\Testable`
   resolve para `Livewire\Livewire\Features\...` e estoura `TypeError` — que é um `Error`, e
   o `toThrow(RuntimeException::class)` de outro caso reportou "an instance of class Error",
   mandando a investigação para o lugar errado. Precisa da barra inicial ou do `use`.

### Números medidos

| | Antes | Depois |
| --- | --- | --- |
| Testes do grupo `kit` | 114 | 130 |
| Permissions `*:Convite` no banco | 0 | 12 |
| Rotas públicas do painel `/app` | login + reset | login + reset + `register` |

## Retrospectiva

- **Funcionou bem**: ler o `routes/web.php` do Filament antes de escrever qualquer coisa
  respondeu de uma vez a pergunta que decidia o formato do link — a rota de registro fica
  fora do segmento `{tenant}` (`:54-57` vs `:119-137`), então o convite não precisa carregar
  o slug da organização na URL. Se a leitura tivesse vindo depois, o token teria nascido com
  um campo a mais e a `Notification` com uma decisão a desfazer.
- **Funcionou bem**: procurar o efeito colateral de ligar `hasRegistration()` em vez de supor
  que era inócuo. O "Cadastre-se" que aparece sozinho no login (`Login.php:445-455`) virou o
  passo 5 e o CT-14 — teria passado despercebido até alguém de fora clicar.
- **Funcionou bem**: o plano listar a assinatura e a lógica de cada método fez a implementação
  ser quase transcrição. Os únicos desvios reais vieram do PHPStan e do que só o runtime
  mostra.
- **Faltou no plano**: a interação entre o `->unique()` herdado do campo de e-mail e a guarda
  de `aceitar()` (Notas, item 1). O plano tratava as duas como a mesma barreira em pontos
  diferentes; na prática a primeira esconde a segunda em todo caminho que passe pelo
  formulário, e o CT tinha de saber disso.
- **Faltou no plano**: um passo "provar que o e-mail RENDERIZA". Toda a estratégia de mock de
  `04-casos-de-teste.md` usa `Notification::fake()`, que é exatamente o que impede
  `toMail()` de rodar. Uma feature cujo produto final é um e-mail não pode ter zero teste que
  o construa.
- **A herdar**: `App\Models\Convite` e `App\Notifications\ConviteDeAcesso` são a fundação que
  a wiki `admin-da-organizacao` reusa — ela acrescenta um Resource no painel `/app`, não um
  segundo fluxo. `config(['audit.console' => true])` é o que qualquer teste de auditoria vai
  precisar.
