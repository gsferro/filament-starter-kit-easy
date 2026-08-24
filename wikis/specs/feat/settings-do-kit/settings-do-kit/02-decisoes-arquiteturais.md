# Decisões Arquiteturais — Settings do kit em `/admin`

## ADR-01: O banco vence em tempo de execução; o `.env` semeia e é o plano B

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Hoje o `.env` é a **única** fonte de configuração do kit. O `kit:install` escreve nele (`APP_NAME`, `KIT_COR_PRIMARIA`, `KIT_TENANCY*`, `DB_*`, `KIT_ADMIN_*`), o `config/kit.php` e o `config/app.php` leem dele, e todo consumidor lê `config()`. Um settings que persiste no banco cria uma **segunda** fonte, e duas fontes sem regra de precedência é a definição de configuração inconsistente: alguém edita o `.env`, nada muda, e ninguém consegue explicar por quê.

Três respostas eram possíveis, e a escolha decide se a feature é útil ou decorativa.

### Decisão

**O banco vence em tempo de execução. O `.env` é a semente da primeira gravação e o plano B quando o banco não responde.**

Concretamente:

1. A migration de settings (`database/settings/*_create_kit_settings.php`) semeia cada propriedade com o valor **de `config(...)`**, que vem do `.env`. Instalação nova carrega as respostas do `kit:install` para o banco sem uma linha de código de sincronização.
2. Um único ponto no boot (`KitServiceProvider::configureSettingsDoKit()`) sobrepõe a config do processo com o que o banco tem, chamando `ConfiguracoesDoKit::aplicarNaConfig()`.
3. **Nenhum consumidor muda.** `CorPrimaria::paleta()`, os três painéis, `ConfiguraFilamentGlobal`, o `MailManager` do Laravel — todos continuam lendo `config()`.
4. O alinhamento é **inerte e silencioso** quando a tabela `settings` não existe, e **inerte com `warning`** quando o banco lança ou uma propriedade não tem linha. Falha para o lado do `.env`, sempre.

### Alternativas Consideradas

1. **O banco é a única fonte; o `kit:install` grava direto nele.** Descartada por ordem de execução: o `kit:install` escreve a configuração **antes** de o `migrate` rodar (é ele que decide o driver do banco em que vai migrar). Gravar no banco antes de o banco existir não é possível, e inverter a ordem do instalador é reescrever `KitInstall` inteiro por causa de uma tela.
2. **O `.env` vence; o settings é só exibição.** Descartada porque torna a feature decorativa: o usuário salva, vê "salvo", e nada acontece. É pior que não ter a tela.
3. **Uma flag `KIT_SETTINGS_ENABLED` escolhendo quem vence.** Descartada porque cria uma **terceira** fonte da verdade — o interruptor —, e o problema desta ADR é justamente ter mais de uma. O desligamento existe e é honesto: `migrate:rollback` na migration de settings. Sem linha na tabela, o alinhamento é no-op por construção.
4. **Cada consumidor lê o settings direto, sem alinhar config.** Descartada por custo: `config('app.name')` é lido em três `brandName`, nas nove chamadas de `media()` do Auth Designer, nos rótulos do Panel Switch, no `mail.from.name` do próprio Laravel e no `<title>`. Alinhar a config é um arquivo; trocar consumidor é dez arquivos e um esquecimento garantido.

### Consequências

- **Positivas**: consumidor nenhum muda; a suíte de 662 casos continua verde sem edição, porque no `RefreshDatabase` o boot acontece antes das migrations e o alinhamento não acha nada — os valores do `phpunit.xml` seguem valendo; `migrate:rollback` é um desligamento completo e testável.
- **Negativas**: uma query a mais por request e por comando artisan (`Schema::hasTable` + uma leitura do grupo). A leitura do grupo é **uma** query (`DatabaseSettingsRepository::getPropertiesInGroup()`, `vendor/spatie/laravel-settings/src/SettingsRepositories/DatabaseSettingsRepository.php:24-33`), e `SETTINGS_CACHE_ENABLED` existe para quem quiser zerar isso.
- **Riscos**: um `Throwable` no alinhamento derrubaria a aplicação inteira, não uma tela. Mitigado por `try/catch (Throwable)` envolvendo **inclusive** o `Schema::hasTable()` — num banco inexistente ele lança antes de responder, e é isso que acontece no primeiro `migrate` de uma instalação nova. Há caso de teste para esse cenário.

### Referências

- `app/Support/CustomizadorDaInstalacao.php:36-42` (o docblock de `alinharConfigEmMemoria`, que resolve o mesmo problema para o instalador — esta ADR é a versão de runtime dele)
- `app/Support/AtivadorDeTenancy::alinharConfigEmMemoria()` (o mesmo padrão, para a tenancy)
- `vendor/spatie/laravel-settings/src/SettingsRepositories/DatabaseSettingsRepository.php:24-33`

---

## ADR-02: `brandName`, `favicon` e `brandLogo` recebem `Closure`, não escalar

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Os três painéis passam **escalar** para `brandName`:

```php
->brandName(config('app.name').' • Admin')   // AdminPanelProvider.php:62
```

e `Closure` para `colors`:

```php
->colors(fn (): array => CorPrimaria::paleta())   // AdminPanelProvider.php:63
```

A diferença não é estilo, e o kit já a documenta para as cores (`AppPanelProvider.php:104-126`): o valor escalar é resolvido quando o `Panel` é construído, e a Closure quando o valor é **usado**.

**Medição feita nesta wiki**, com probe direto:

```bash
php artisan tinker --execute 'config(["app.name" => "PROBE"]); echo \Filament\Facades\Filament::getPanel("admin")->getBrandName();'
# → brand=Starter Kit • Admin
```

Ou seja: mexer em `config('app.name')` depois do boot **não** muda o brand. O alinhamento do ADR-01 roda no `boot()` do `KitServiceProvider`, e o brand escalar já está congelado.

### Decisão

Os três `brandName`, o `favicon` novo e o `brandLogo` novo recebem `Closure`. As assinaturas aceitam:

- `HasBrandName::brandName(string|Htmlable|Closure|null)` — `vendor/filament/filament/src/Panel/Concerns/HasBrandName.php:12`
- `HasFavicon::favicon(string|Closure|null)` — `HasFavicon.php:11`
- `HasBrandLogo::brandLogo(string|Htmlable|Closure|null)` — `HasBrandLogo.php:16`

### Alternativas Consideradas

1. **Alinhar a config no `register()` de um provider, antes dos painéis.** Descartada: `register()` roda antes de qualquer `boot()`, e o banco não é lugar de ser consultado em `register()` — o container ainda está sendo montado, e uma exceção ali não tem para onde ir. Também não resolveria o Auth Designer, cuja config é gravada no `boot()` do plugin.
2. **Ler o settings direto dentro do `panel()`, sem alinhar config.** Descartada pelo ADR-01, alternativa 4.
3. **Deixar o brand escalar e aceitar que o nome só muda depois de um `config:clear`.** Descartada: é o comportamento que a feature existe para eliminar.

### Consequências

- **Positivas**: o valor passa a ser resolvido no render, então salvar no settings vale **no request seguinte**, sem cache para limpar.
- **Negativas**: a Closure é avaliada a cada render do cabeçalho. É uma leitura de config em memória — custo desprezível.
- **Riscos**: uma Closure que lança derruba o `<head>` de toda página. Por isso `App\Support\IdentidadeDoKit` tem a guarda de arquivo ausente e nunca lança (ADR-03).

### Referências

- `app/Providers/Filament/AppPanelProvider.php:104-126` — a nota que já documenta exatamente este mecanismo para as cores
- Probe de medição registrado em `03-progresso.md` → Auditoria Pré-Implementação

---

## ADR-03: `IdentidadeDoKit` resolve logo, favicon e arte de login — e nunca lança

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Três caminhos de arquivo (`logo`, `favicon`, `arte_do_login`) precisam virar URL em **doze** pontos: três `brandLogo`, três `favicon` e nove `media()` do Auth Designer (login, password-reset e email-verification em cada painel). Cada resolução tem a mesma lógica e a mesma guarda: caminho vazio → default; caminho declarado mas **ausente no disco** → default, porque um `<link rel="icon">` apontando para 404 no `<head>` de toda página é pior que o ícone padrão.

### Decisão

Uma classe `App\Support\IdentidadeDoKit` com três métodos estáticos, pelo mesmo motivo que `App\Support\CorPrimaria` existe: a guarda precisa existir em **um** lugar. Repetida em doze, ela deixa de existir em um deles — e o modo de falhar é silencioso.

Os três uploads gravam em `->disk('public')->visibility('public')`. O default de visibilidade do Filament é `private` (confirmado na doc do Filament 5 via `search-docs`, seção "Configuring the storage disk and directory": *"files are uploaded with `private` visibility... unless the disk is set to `public`"*), e favicon e logo aparecem **antes de haver sessão**, na tela de login. O `storage:link` já roda no `kit:install` (`app/Console/Commands/KitInstall.php:353`).

### Alternativas Consideradas

1. **`spatie/laravel-medialibrary`**, que o kit já usa para o `Projeto` e o `Tenant`. Descartada: a medialibrary é polimórfica e exige um **model** dono. O settings do spatie não é model (ADR-07), e criar uma model-fantasma só para pendurar três arquivos é mais código que três caminhos numa string.
2. **Resolver inline em cada painel.** Descartada pela guarda: doze cópias.
3. **Sem guarda de arquivo ausente.** Descartada: o caso acontece de verdade (alguém apaga `storage/app/public/kit/`, ou clona o repo sem o `storage/` do colega) e o sintoma é um 404 em toda página, difícil de rastrear até a configuração.

### Consequências

- **Positivas**: um ponto de guarda; o fallback da arte de login continua sendo `images/auth/login.svg`, então uma instalação que nunca abriu a tela de settings se comporta exatamente como hoje.
- **Negativas**: um `Storage::disk('public')->exists()` por resolução. Chamado no render do cabeçalho, é um `stat` de arquivo local.
- **Riscos**: em disco remoto (S3) o `exists()` é uma chamada de rede por render. O kit nasce com disco local; se alguém trocar, o `ponytail:` comment aponta que a guarda deve virar cache.

### Referências

- `app/Support/CorPrimaria.php:7-22` — o precedente exato: classe que existe pela guarda, não pela lógica
- `.ai/rules/models.md`, seção "Mídia e soft delete" — "avatar e logo ficam em `->disk('public')` explícito, porque aparecem antes de haver sessão"

---

## ADR-04: Uma permissão só (`View:ConfiguracoesDoKit`), e o `PapeisSeeder` não é editado

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-14 pede "as permissões para operar o settings" e RQ-15 que elas sejam padrão desde o início. Duas perguntas:

1. Uma permissão (acessar) ou duas (ver e editar)?
2. Como ela chega ao papel `admin` sem passo manual?

Sobre (1), o vendor é explícito no próprio README (`vendor/filament/spatie-laravel-settings-plugin/README.md`, seção Authorization): *"`canEdit()` only gates saving — it does not prevent a user from loading the page and reading every field's current value. If the settings themselves are sensitive (API keys, internal flags, etc.), restrict viewing with `canAccess()`"*. E o código confirma: `save()` faz `if (! $this->canEdit()) return;` (`src/Pages/SettingsPage.php:64`) e `defaultForm()` faz `->disabled(! $this->canEdit())` (`:184`) — nenhum dos dois esconde valor.

Esta tela guarda a **senha de SMTP**. Um papel "só leitura" nela é um papel que lê credencial.

Sobre (2), o `PapeisSeeder` dá ao papel `admin` a matriz **inteira** do painel `admin`, colhida por `App\Support\Paineis::permissoes('admin')` (`database/seeders/PapeisSeeder.php:57-58`). As duas listas de subtração (`permissoesDeAdministracaoDoApp()`, `permissoesForaDoApp()`) recortam o painel **`app`**.

### Decisão

**Uma** permissão, `View:ConfiguracoesDoKit`, governando acesso e gravação. A Page usa `BezhanSalleh\FilamentShield\Traits\HasPageShield` (`vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:10-37`), e `canEdit()` devolve `static::canAccess()`.

**O `PapeisSeeder.php` não é editado.** A permission entra na matriz do painel `admin` sozinha, porque a Page é descoberta por `discoverPages(in: app_path('Filament/Admin/Pages'))` e `config('filament-shield.tabs.pages')` é `true` com `pages.prefix = 'view'` e `permissions.case = 'pascal'`.

### Alternativas Consideradas

1. **Duas permissões via `custom_permissions`** (`View:` + `Update:`). Descartada por (1) acima: separar leitura de escrita numa tela que exibe senha de SMTP vende uma granularidade que o pacote não entrega. Se um papel de auditoria aparecer, o caminho correto é uma tela de leitura própria, sem os campos de segredo — não uma permission a mais nesta.
2. **Acrescentar a permission à mão no `PapeisSeeder`.** Descartada: a matriz do painel já faz o trabalho, e uma lista explícita apodrece a cada Page nova. É também o que evita conflito de rebase com a branch `feat/permissoes-de-telas-e-acoes`, que está mexendo naquele arquivo.
3. **`canAccess()` próprio, sem `HasPageShield`.** Descartada: a convenção que a branch paralela está estabelecendo é "toda Page nasce com permissão do Shield", e uma exceção logo depois seria dívida na primeira semana.

### Consequências

- **Positivas**: zero edição em seeder; a permission aparece sozinha na tela de Funções e pode ser concedida a outro papel por lá; `master_global` entra pelo `Gate::before` como em tudo mais.
- **Negativas**: quem quiser "ver sem editar" não tem esse recorte. Declarado, não escondido.
- **Riscos**: se alguém mover a Page para o painel `app`, ela precisa entrar em `PapeisSeeder::permissoesDeAdministracaoDoApp()` — senão todo `panel_user` herda a configuração da instalação. O PHPDoc da Page registra esse aviso.

### Referências

- `.ai/rules/filament.md`, "Resource ou RelationManager novo exige gerar as permissões" e "Resource, Page ou Widget de administração no painel `app` entra na lista de subtração"
- `vendor/filament/spatie-laravel-settings-plugin/README.md`, seção Authorization
- `vendor/bezhansalleh/filament-shield/src/Traits/HasPageShield.php:19-27`

---

## ADR-05: A senha de SMTP é cifrada no banco e mascarada na trilha

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-08 leva a senha de SMTP para a tabela `settings`, cujo `payload` é JSON em claro. E RQ-17 leva as alterações para a tabela `audits`, cujo `old_values`/`new_values` também é texto.

### Decisão

Duas coisas, e as duas são necessárias:

1. `ConfiguracoesDoKit::encrypted()` devolve `['mail_password']`. O spatie cifra e decifra por propriedade (`vendor/spatie/laravel-settings/src/Settings.php:136-171`, com `Support\Crypto`).
2. O listener de auditoria grava `'••••••'` no lugar do valor, para `mail_password`. A trilha registra **que** o segredo mudou, **quem** mudou e **quando** — nunca o segredo.

### Alternativas Consideradas

1. **Não cifrar** e confiar na permissão. Descartada: um dump de banco, um backup e a tela de auditoria são três caminhos que a permissão da tela não cobre.
2. **Cifrar tudo.** Descartada: `payload` cifrado inteiro torna a tabela ilegível para depuração e não protege nada além do único campo que é segredo.
3. **Não auditar a senha.** Descartada: "a senha do SMTP foi trocada às 3h da manhã" é exatamente o que uma trilha existe para responder. Mascarar dá a resposta sem dar o segredo.

### Consequências

- **Positivas**: segredo cifrado no repositório de settings e ausente da trilha.
- **Negativas**: rotação de `APP_KEY` torna a senha ilegível — comportamento normal de valor cifrado no Laravel, e o README avisa.
- **Riscos**: `Crypto::decrypt` lança com `APP_KEY` trocada, e isso aconteceria **no boot** (o alinhamento lê o grupo inteiro). Coberto pelo `try/catch` do ADR-01: cai para o `.env` com `warning`, não derruba a aplicação.

---

## ADR-06: O recorte de RQ-03 é o mesmo do `kit:install --custom`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-03 diz "todas as informações que são customizadas na instalação". O `kit:install` faz seis perguntas, e três delas não podem virar campo de tela sem mentir:

- **banco** — trocar depois do `migrate` não é reescrita de config
- **multi-organização** — as tabelas de permissão só nascem com a coluna de contexto se `permission.teams` estiver ativo **antes** do migrate
- **e-mail e senha do admin** — o `UsuarioAdminSeeder` **não sincroniza**, de propósito: ele busca pelo papel e, achando administrador, não faz nada, porque roda em todo `db:seed` e atualizar senha ali reverteria em silêncio a troca feita na tela de perfil

Esse recorte **já existe no kit**, escrito e justificado: `CustomizadorDaInstalacao::perguntarSemBanco()` (`app/Support/CustomizadorDaInstalacao.php:170-229`) é o caminho do `--custom`, e ele oferece exatamente **nome e cor**.

### Decisão

O settings recebe o recorte de `perguntarSemBanco()` (nome e cor) **mais** o que também é reescrita de config e não estava lá porque não era pergunta de terminal: rótulos da organização, hub de navegação, identidade visual, e-mail e defaults de tabela.

E o `--custom` passa a gravar **também** no settings (passo 7 do PRD). Sem isso, `kit:install --custom` num projeto instalado reescreveria o `.env`, o banco continuaria vencendo, e a resposta do usuário pareceria não ter efeito nenhum.

### Alternativas Consideradas

1. **Campos de banco e de credencial do admin, com aviso de "não tem efeito".** Descartada: campo que não faz o que diz é pior que campo ausente, e o kit já tem essa decisão escrita para o `--custom`.
2. **Um segundo recorte, diferente do `--custom`.** Descartada: duas regras para a mesma pergunta ("o que dá para mudar depois?") é onde o kit começa a se contradizer.
3. **Não mexer no `--custom`.** Descartada: é o conflito entre as duas fontes se manifestando no caminho mais comum de quem já instalou.

### Consequências

- **Positivas**: uma regra só no kit; o `--custom` continua sendo o caminho de terminal e a tela o caminho de painel, e os dois concordam.
- **Negativas**: quem esperava trocar o banco pela tela não vai poder. Declarado em `00-requisito.md` → Fora desta entrega, e no README.
- **Riscos**: `aplicarSemBanco()` roda em contexto de comando, onde a tabela pode não existir (projeto antes do migrate). A gravação é condicional a `Schema::hasTable`.

### Referências

- `app/Support/CustomizadorDaInstalacao.php:170-229` — o docblock que já argumenta este recorte, item por item
- Refine: ADR-01

---

## ADR-07: A trilha de auditoria sai de um listener de `SavingSettings`, não de uma trait em model

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-17 pede "a model do settings com o pacote do audits implementado". O padrão do kit para isso é a trait `App\Traits\AuditsFillables` (`use Auditable` + `getAuditInclude()` devolvendo `getFillable()`), aplicada em `User`, `Tenant`, `Convite`, `Projeto` e `AgenteIa`.

**Mas um settings do spatie não é uma model Eloquent.** `Spatie\LaravelSettings\Settings` é `abstract class` com propriedades tipadas (`vendor/spatie/laravel-settings/src/Settings.php:19`), persistida como linhas chave-valor. A trait não se aplica.

E a saída óbvia — apontar `config('settings.repositories.database.model')` para uma model do kit com a trait — produz uma trilha **pela metade**, em silêncio:

| Operação | Método do repositório | Linha | Dispara evento Eloquent? |
|---|---|---|---|
| criar propriedade | `->create()` | `DatabaseSettingsRepository.php:56` | **sim** |
| atualizar propriedades | `->upsert()` | `DatabaseSettingsRepository.php:74-77` | **não** |

Ou seja: a criação (que acontece uma vez, na migration) seria auditada e **toda alteração pela tela não seria**. Trilha verde e vazia — o pior resultado possível para uma trilha.

### Decisão

A trilha é escrita por `App\Listeners\AuditarConfiguracoesDoKit`, ouvindo `Spatie\LaravelSettings\Events\SavingSettings` — que o `save()` dispara em `vendor/spatie/laravel-settings/src/Settings.php:191` e que é o **único** ponto do pacote carregando `properties` (novo) e `originalValues` (antigo) juntos (`src/Events/SavingSettings.php:16-26`).

Cada propriedade alterada gera uma linha em `audits`, com três escolhas que vêm de leitura do vendor:

1. **`auditable_type` = `Spatie\LaravelSettings\Models\SettingsProperty::class`, `auditable_id` = o id da linha real.** A listagem de `/infra/audits` faz `$query->with(['user', 'auditable'])` (`vendor/tapp/filament-auditing/src/Filament/Resources/Audits/Tables/AuditsTable.php:38`). Um `auditable_type` que não seja model Eloquent quebra o eager load do morph e derruba a tela. `SettingsProperty` **é** model real na tabela `settings` (`vendor/spatie/laravel-settings/src/Models/SettingsProperty.php:7-16`), então o morph resolve para o registro de verdade.
2. **`event` = `'settings-updated'`, não `'updated'`.** `RestoreAuditAction` fica visível só quando `$record->event === 'updated'` (`vendor/tapp/filament-auditing/src/Filament/Actions/RestoreAuditAction.php:46`), e o restore faz `$record->fill($audit->old_values)` + `save()` (`vendor/tapp/filament-auditing/src/Concerns/CanRestoreAudit.php:53-54`). Nossos `old_values` são `['nome_da_aplicacao' => …]`, e a linha de `settings` tem colunas `group/name/payload` — o restore produziria SQL inválido. Um nome de evento diferente esconde o botão sem policy nenhuma, e o `event` é `string` livre na migration (`database/migrations/2026_08_12_164915_create_audits_table.php`).
3. **`old_values`/`new_values` = `[<nome da propriedade> => <valor>]`.** É o que faz o nome da propriedade aparecer na coluna da listagem, em vez de um `payload` anônimo.

`tags = 'configuracoes-do-kit'` discrimina a trilha. `mail_password` entra mascarado (ADR-05).

### Alternativas Consideradas

1. **`App\Traits\AuditsFillables` numa model sobre a tabela `settings`.** Descartada pela tabela acima: audita a criação e perde toda alteração. É a alternativa que **parece** seguir o padrão do kit e entrega o oposto.
2. **Model própria do kit (`App\Models\ConfiguracaoDoKit`) como `auditable_type`.** Descartada por YAGNI: `SettingsProperty` já é a model daquela tabela, resolve o morph e não acrescenta arquivo. O ganho seria um nome mais bonito na coluna "Tipo" da listagem — e `tags` já discrimina.
3. **`auditable_id` apontando para nada (0/null).** Descartada: com `auditable` nulo, `RestoreAuditAction::visible()` avalia `can('restoreAudit', null)`, que para o `master_global` passa pelo `Gate::before` — botão visível que não faz nada. Apontar para a linha real é honesto e não custa nada.
4. **`SettingsSaved` em vez de `SavingSettings`.** Descartada: `SettingsSaved` carrega só `$settings` (`src/Events/SettingsSaved.php:9`), sem os valores antigos. Sem o antigo não há diff, e sem diff a trilha registra tudo a cada salvamento.
5. **Só logar no channel `configuracoes`, sem tabela `audits`.** Descartada: RQ-17 pede o pacote de audits, e a tela de `/infra/audits` é onde o operador já procura.

### Consequências

- **Positivas**: audita a operação que importa (alteração pela tela); aparece em `/infra/audits` junto com o resto; uma linha por propriedade alterada, com o nome da propriedade legível; nenhuma model nova.
- **Negativas**: a trilha **não** é a `Auditable` do owen-it — não há `$model->audits()`, não há `RestoreAuditAction`, e um `audits()` futuro em cima disso precisaria de outra decisão. Declarado, não escondido.
- **Riscos**: um `SavingSettings` de OUTRA classe de settings (uma futura, de register ou de login social) cairia no listener. Mitigado por uma saída cedo `instanceof ConfiguracoesDoKit` — e quando aquelas wikis chegarem, a decisão de auditá-las é delas, explícita.

### Referências

- `app/Traits/AuditsFillables.php` — o padrão do kit, e por que não serve aqui
- `vendor/spatie/laravel-settings/src/SettingsRepositories/DatabaseSettingsRepository.php:56` e `:74-77`
- `vendor/tapp/filament-auditing/src/Filament/Resources/Audits/Tables/AuditsTable.php:38`
- `vendor/tapp/filament-auditing/src/Filament/Actions/RestoreAuditAction.php:46`
- `vendor/tapp/filament-auditing/src/Concerns/CanRestoreAudit.php:24-54`

---

## ADR-08: Cor livre vence a seleção, e valor inválido continua sendo ignorado

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-06 pede seleção pelo Enum `Color`; RQ-07 pede também um input de cor livre. Dois campos para um conceito exigem uma regra de precedência, e o kit já tem uma regra dura sobre cor: **nome inválido é ignorado e o painel volta ao padrão em vez de morrer** (`app/Support/CorPrimaria.php:7-22` e `config/kit.php:49-50`). O motivo está escrito: `constant()` num nome inexistente lança `Error: Undefined constant`, e como isso roda no boot de todo painel, derrubaria **toda página** do projeto.

A cor livre tem o mesmo perigo por outro caminho: `Color::generatePalette()` (`vendor/filament/support/src/Colors/Color.php:663`) chama `convertToOklch()` e faz `sscanf` no resultado. Um valor que não é cor produz lixo silencioso ou exceção — e o `ColorManager` chama `generatePalette()` sozinho quando recebe string.

### Decisão

Precedência: **hex válido → nome válido → padrão do Filament**. O hex é validado por `preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')` antes de ser usado; inválido **cai para o nome**, e nome inválido cai para `[]`.

O hex vence porque ele é o campo mais específico: quem digita `#7c3aed` escolheu aquela cor, enquanto o `Select` tem um valor default (vazio) e pode simplesmente nunca ter sido tocado. A precedência inversa faria a cor livre ser ignorada sempre que a seleção estivesse preenchida, que é o caso comum numa instalação que escolheu cor no `kit:install`.

Dentro de `/app/{slug}` a cor da **organização** continua vencendo as duas: ela é registrada mais tarde no ciclo, no `bootUsing()` do `AppPanelProvider`, e o `ColorManager::getColors()` sobrescreve a chave na ordem de registro.

### Alternativas Consideradas

1. **Um campo só, aceitando nome ou hex.** Descartada: RQ-06 e RQ-07 pedem os dois controles, e um `TextInput` que aceita "Blue" ou "#0000ff" perde o seletor visual de cor.
2. **A seleção vence.** Descartada pelo argumento acima: tornaria a cor livre inalcançável para a maioria.
3. **Validar o hex só no formulário (`->regex()`).** Descartada: o valor também entra pelo `.env` (`KIT_COR_PRIMARIA_HEX`) e por `db:seed`. Validação de formulário protege o formulário; a guarda precisa estar onde o valor é **usado**, que é a mesma lição de `CorPrimaria`.
4. **Um `Select` com `'personalizada'` abrindo o `ColorPicker`.** Descartada por complexidade sem ganho: um estado a mais, um `->live()` e uma condição, para expressar o que a precedência já expressa.

### Consequências

- **Positivas**: a tolerância deliberada do kit é preservada nos dois caminhos; os cinco casos de `tests/Kit/CorPrimariaTest.php` continuam válidos sem edição, porque o hex é nulo neles.
- **Negativas**: dois campos e uma precedência para explicar. Vai para o `helperText` do campo e para o README.
- **Riscos**: alguém preenche o hex, esquece, e não entende por que a seleção não funciona. Mitigado pelo `helperText` dizendo, no próprio campo, que a cor livre vence.

### Referências

- `app/Support/CorPrimaria.php:7-22`
- `config/kit.php:49-50`
- `vendor/filament/support/src/Colors/Color.php:647-685`
- `app/Providers/Filament/AppPanelProvider.php:104-130` (por que a cor do tenant vence)

---

## ADR-09: "Densidade de tabela" não existe no Filament 5 — o TODO é reescrito, não apagado

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O TODO de `app/Providers/Concerns/ConfiguraFilamentGlobal.php:35-38` (repetido em `README.md:1257` e `README.en.md:1221`) promete quatro coisas: **paginação**, **densidade da tabela**, **persistência de filtros** e **colunas redimensionáveis**.

Varredura em `vendor/filament/tables/src`: nenhuma ocorrência de `density`. `vendor/filament/tables/src/Enums/` contém `ColumnManagerLayout`, `ColumnManagerResetActionPosition`, `FiltersLayout`, `FiltersResetActionPosition`, `PaginationMode`, `RecordActionsPosition`, `RecordCheckboxPosition` — nenhum de densidade. O Filament 5 instalado (5.7.6) não tem essa API.

### Decisão

Entregar os três que existem e acrescentar o alternador de **linhas listradas** (`striped()`), que é o único controle visual de aperto que o framework oferece — e é justamente o que `configuraTable()` já fixa hoje (`:182`).

E **reescrever** o texto do TODO nos dois READMEs, dizendo que densidade de tabela não existe na versão instalada. Apagar o TODO como se os quatro itens tivessem sido entregues é a única saída inaceitável: a próxima pessoa acreditaria que existe um controle de densidade em algum lugar da tela.

### Alternativas Consideradas

1. **Implementar densidade à mão** (CSS por classe no `<body>` da tabela). Descartada: seria um tema Filament customizado, e o kit **não tem** `viteTheme()` em nenhum painel (`.ai/rules/css-filament.md`). O custo é um tema; o ganho é uma preferência de gosto.
2. **Apagar o item do TODO em silêncio.** Descartada pelo motivo acima.
3. **Deixar o TODO inteiro no lugar.** Descartada: três dos quatro itens **foram** entregues, e um TODO que não distingue entregue de impossível não informa nada.

### Consequências

- **Positivas**: o README passa a registrar um limite real do framework, que é informação útil e não estava escrita em lugar nenhum.
- **Negativas**: RQ-11 fica atendido em 3/4, e isso aparece na matriz de rastreabilidade do quality gate como parcial — corretamente.
- **Riscos**: uma versão futura do Filament pode ganhar densidade, e o texto do README envelhece. Aceito: ele nomeia a versão medida.
