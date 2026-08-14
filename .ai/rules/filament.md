---
paths:
  - 'app/Filament/**'
---

# Filament

## Papel e permissão se gravam pela API do spatie, nunca por sync da relação
Em campo de formulário que grava `roles` ou `permissions`, o `->relationship()` do Filament NÃO serve sozinho: ele salva com `$relationship->sync()`, que escreve na pivot só as colunas da chave.

Com multi-tenancy ligada isso estoura 500 (`NOT NULL constraint failed: model_has_roles.team_id`) — o `wherePivot` que o spatie põe em `roles()` filtra LEITURA e não alimenta escrita; quem passa o `team_id` do contexto é o `assignRole()`/`syncRoles()`. Mesmo em single-tenant o `sync()` deixa o cache de papéis velho.

Use `->saveRelationshipsUsing()` chamando `syncRoles()`, e resolva os papéis em MODELOS antes (o state vem do Livewire como string, e o `collectRoles()` do spatie trata string como nome de papel — `"4"` viraria `RoleDoesNotExist`). Ver `app/Filament/Admin/Resources/Users/UserResource.php`.

Teste em par: um caso em `tests/Kit` (single-tenant) e um em `tests/Tenancy` conferindo o `team_id` da pivot. Abrir a tela não cobre — o `GET /admin/users` seguia verde com o salvamento quebrado.

## Asserção de identidade vive no model, não na query da tela

Ação de tabela que age **sobre o registro de outra pessoa** (aceitar um convite, confirmar um convite, assumir algo endereçado a um e-mail) não se protege com o `where` da query que lista os registros. A query é **filtro de UI**; a barreira é uma asserção no método do model, que lança quando o registro não é de quem chamou.

O raciocínio que produz o furo é sempre o mesmo: "a tabela já filtra por e-mail, então conferir de novo no model é redundância". Não é. Enquanto a página for o único chamador, funciona; o primeiro job, comando, action em massa, seeder ou rota de API chama o método direto e passa por cima da barreira **sem nada acusar** — e o resultado é alguém dentro de uma organização com o papel de um convite que não era dele.

É literalmente o furo do `jeffersongoncalves/filament-teams`: `TeamInvitation::accept(Authenticatable $user)` faz `attach()` + `delete()` sem comparar e-mail nenhum, e a única barreira é o `->where('email', $email)` da página de aceite.

No kit: `Convite::exigirDono()`, chamado na primeira linha de `aceitarComoUsuarioExistente()` e de `recusar()`. Comparação normalizada (`mb_strtolower(trim(...))`) nos dois lados — e-mail não é case-sensitive na prática. `App\Filament\App\Pages\ConvitesRecebidos` continua escapando a query por e-mail, e o PHPDoc dela diz que isso é conveniência.

Policy não substitui: policy é autorização de ação por perfil, isto é identidade do **dono do registro** — e policy não é consultada por job nem por comando, que é justamente o chamador que se quer cobrir.

Teste: chame o método **direto**, com o usuário errado, e cobre a exceção (`it('recusa aceite quando o e-mail nao corresponde')` em `tests/Kit/ConviteUsuarioExistenteTest.php`). Barreira sem teste direto não é barreira — o caso que passa pela tela continuaria verde com a asserção removida.

## Resource ou RelationManager novo exige gerar as permissões

Resource novo nasce sem permission no banco: a tela responde 403 para todo mundo que não seja `master_global`. Depois de `make:filament-resource`, rode sempre os dois seeders, nesta ordem:

```bash
php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
```

O primeiro roda `shield:generate --all` **em cada painel** (o comando só enxerga o painel corrente) e escreve as policies; o segundo recorta a matriz por painel via `App\Support\Paineis::permissoes()` e devolve as permissões aos papéis. Os dois são idempotentes.

**RelationManager o Shield não enxerga.** A descoberta cobre apenas Resources, Pages e Widgets (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php`), então nenhuma permission é gerada para ele e a autorização recai na policy do model relacionado. Se esse model já tem Resource em algum painel, não há nada a fazer. Se não tem, crie a policy à mão (`php artisan make:policy`) e declare as chaves em `config('filament-shield.custom_permissions')` **antes** de rodar os seeders — do contrário o RelationManager fica aberto a qualquer um que consiga abrir o Resource pai.

## Resource, Page ou Widget de administração no painel `app` entra na lista de subtração

O `panel_user` recebe a matriz do painel `app` **menos** as permissões das entidades de administração — a lista `PapeisSeeder::permissoesDeAdministracaoDoApp()`, hoje só FQCN. Entidade nova de administração nesse painel (qualquer uma que mexa em quem entra: usuários, convites, papéis) precisa entrar nessa lista, e **as três famílias contam**: a matriz vem de `FilamentShield::getEntitiesPermissions()`, que mistura Resource **com Page e Widget** (e com `custom_permissions`, hoje vazia).

Medido no painel `app`: **38 permissions, 36 de Resource e 2 de Page** (`View:MyProfilePage` e `View:ConvitesRecebidos`, as duas de todo mundo por direito). Até a 0.11.0 a subtração varria só `Paineis::resources()` e essas duas eram **inalcançáveis** — o furo era inofensivo por acidente, porque nenhuma Page de administração existia ainda. `Paineis::permissoesDe()` fechou: as 38 são alcançáveis.

Esquecer não dá erro: os dois seeders rodam, tudo fica verde, e **todo usuário comum do negócio vira administrador da organização** — sem migration, sem 403, sem log. É a falha mais cara desta parte do kit porque ela só aparece quando alguém repara que o cliente está editando os próprios colegas.

A lista casa por **FQCN exato**, nunca por substring do nome da permission. Numa subtração o erro do substring é espelhado: tirar permissão de quem deveria tê-la.

Ao mexer em `Paineis::entidadesDoPainel()`, cuidado com o formato: Resource guarda `permissions` como `[affix => ['key' => …, 'label' => …]]`, Page e Widget como `[chave => rótulo]`. `array_column($e['permissions'], 'key')` numa Page devolve `[]` **sem erro nenhum** e a subtração volta a não subtrair nada — com cara de correção aplicada. Page e Widget usam `array_keys()`, como o próprio Shield em `getEntityPermissionKeys()`.

Teste: um caso conferindo que `panel_user` **não** tem `ViewAny:{SuaEntidade}` e **tem** a permissão de uma entidade de negócio — ver `it('mantem o usuario comum fora da administracao da organizacao')` e `it('alcanca Page e Widget na subtracao do painel app')` (este assere a chave de uma **Page** saindo de `permissoesDe()`; com a extração errada ele é o único que fica vermelho).

## Papel novo precisa declarar o painel

`roles.painel` é o que dá acesso ao painel — `User::canAccessPanel()` compara a coluna com o id do painel. **Nulo não é coringa**: papel sem painel não abre painel algum (o `master_global` entra pelo `Gate::before`, não pela coluna). Papel criado sem painel só carrega permissões, e quem o tiver sozinho autentica e leva 403 nos três painéis.

Papel semeado entra em `database/seeders/PapeisSeeder.php`, com o painel no terceiro argumento de `papel()`.

## Resource de model sem relação de posse com o tenant

Resource de painel COM tenancy cujo model não tem a relação `tenant()` (ex.: `User`, cujo vínculo é a pivot many-to-many `tenant_user`) precisa de `protected static bool $isScopedToTenant = false;`. Sem isso `Panel::boot()` registra o global scope nativo e a PRIMEIRA query do painel morre com `LogicException: The model [App\Models\User] does not have a relationship named [tenant]`.

Desligado o escopo nativo, o recorte é seu: escreva em `getEloquentQuery()` (nunca só na `table()` — o `getEloquentQuery()` é o que também alimenta o route binding, a busca ⌘K e o badge de contagem) e faça-o FALHAR FECHADO: sem `Filament::getTenant()`, devolva `->whereRaw('1 = 0')` e um `warning` no channel `autenticacao`. O escopo nativo, no mesmo cenário, falha ABERTO e devolve a base inteira da instalação. Consulta em Page nova é sempre `static::getEloquentQuery()`, nunca `Model::query()`.

Apontar `$tenantOwnershipRelationshipName` para a relação plural funciona e foi recusado: falha aberto, registra global scope no model compartilhado com o guard de autenticação, e traz junto o observer de vendor do `created`. Ver `app/Filament/App/Resources/Users/UserResource.php` e ADR-03 em `wikis/specs/main/admin-da-organizacao/`.

Teste em par: um caso conferindo `isScopedToTenant() === false` + `Model::query()->count()` sem exception, e outro conferindo que a query fecha (0, não "todos") sem tenant corrente.
