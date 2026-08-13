# Progresso — Admin da organização

**Concluída em 2026-08-13.** 150/150 em `php artisan test --group=kit` (133 antes, +17 novos),
PHPStan level 6 limpo, Pint limpo, árvore limpa depois de duas execuções da suíte.

> **Pré-requisito bloqueante**: a wiki `perfil-e-acesso-ao-painel` precisa estar
> implementada e verde. Sem `roles.painel`, `canAccessPanel()` lendo o papel e
> `App\Support\Paineis`, nada abaixo funciona.

- [x] `perfil-e-acesso-ao-painel` concluída (`03-progresso.md` daquela wiki todo marcado)
- [x] `convite-de-usuario` concluída — o passo 4 e a metade `Convite` do passo 1 foram
      entregues

## 1. `PapeisSeeder` — o papel novo e a subtração

- [x] `admin_organizacao` semeado com `$this->papel('admin_organizacao', $guard, 'app')`
      (terceiro argumento **posicional**), **dentro** do `if (config('kit.tenancy.enabled'))`
- [x] `admin_organizacao` recebe `$this->permissoesDoPainel('app', $guard)` — o método
      privado que já existe
- [x] `permissoesDeAdministracaoDoApp()` recorta por **FQCN de Resource**, nunca por
      substring do nome da permissão
- [ ] ~~`class_exists` na lista~~ — cortado, ver Desvios
- [x] `panel_user` passa a receber `permissoesDoPainel('app', $guard)->reject(...)`
- [x] A subtração roda nos **dois** modos (não está dentro do `if` de tenancy)
- [x] O comentário do `panel_user` atualizado: deixa de sugerir o recorte ao projeto e
      passa a descrever o que o kit faz
- [x] Seeder continua idempotente: rodar duas vezes não muda o conjunto

## 2. `UserResource` do painel `app`

- [x] `php artisan make:filament-resource User --panel=app --no-interaction`
- [x] **`protected static bool $isScopedToTenant = false;`** com o comentário explicando o
      `LogicException`
- [x] `shouldRegisterNavigation()` e `canAccess()` com `config('kit.tenancy.enabled')`
- [x] `getEloquentQuery()` com `whereHas('tenants', …)` sobre `Filament::getTenant()`
- [x] Ramo sem tenant: `whereRaw('1 = 0')` + `warning` no canal `autenticacao`
- [x] `parent::getEloquentQuery()` (não `User::query()`)
- [x] `use BadgeContagemNavegacao`
- [x] `TextInput` de nome, e-mail e senha espelhando o Resource do `/admin`
- [x] `Select::make('roles')` com `->relationship('roles', 'name', fn ($q) => $q->where('painel', 'app'))`
- [x] `saveRelationshipsUsing()` com `syncRoles()` **e** o `where('painel', 'app')` na escrita
- [x] `warning` quando algum id é descartado, com `ids_enviados` e `ids_aceitos`
- [x] `info` na gravação, com `alvo_id`, `executor_id`, `tenant_id`, `papeis`
- [x] **Nenhum** campo de organização no formulário
- [x] Tabela sem `Impersonate::make()`, sem `DeleteAction`, sem `DeleteBulkAction`
- [x] `canDelete()` e `canDeleteAny()` fixos em `false`
- [x] `ListUsers` com `HasResizableColumn`
- [x] `CreateUser::afterCreate()` com `syncWithoutDetaching` + `info`
- [x] `EditUser` **sem** a ação de delete no header (o gerador a inclui)

## 3. `UsersRelationManager` — a promoção no `/admin`

- [x] Ação `papeisNaOrganizacao` na linha de cada usuário
- [x] Select de papéis filtrado por `painel = 'app'`
- [x] `fillForm` lendo os papéis atuais **no contexto do tenant**
- [x] Troca de contexto com `setPermissionsTeamId($tenant->getKey())`
- [x] `unsetRelation('roles')` nas duas pontas
- [x] Restauração do contexto no `finally`
- [x] `info` no canal `autenticacao` (o `registrar()` existente continua em `tenancy`)

## 4. `ConviteResource` do painel `app`

- [x] Conferido, antes de escrever: `App\Models\Convite` tem `tenant_id` **dentro** do
      `$fillable` e **não** usa `App\Traits\BelongsToTenant` — a divergência que a wiki já
      registrava se confirmou
- [x] `shouldRegisterNavigation()` / `canAccess()` com `config('kit.tenancy.enabled')`
- [x] `getEloquentQuery()` com `where('tenant_id', …)` fail-closed
- [x] `mutateFormDataBeforeCreate()` sobrescreve `tenant_id` com `Filament::getTenant()`
      (barreira 6)
- [x] Select de papéis com o **mesmo** filtro `painel = 'app'` na exibição e — por
      `Rule::exists(...)->where('painel','app')`, ver Desvios — na escrita
- [x] Nenhum campo de organização
- [x] `info` no `afterCreate` da Page
- [x] `canDelete()`/`canDeleteAny()` em `false` e sem página de edição

## 5. `DemoTenancySeeder` — a persona na demo

- [x] `papelDoApp()` ganha um terceiro argumento `?string $papel = null` — **sem helper novo**
- [x] Ana → `admin_organizacao` na Acme; Bruno e Carla seguem `panel_user`
- [x] Seeder continua idempotente e sem factory/faker

## 6. Documentação

- [x] `.ai/rules/filament.md` — **quarta** regra, gravada por `record-rule`
- [x] `grep -rin 'isScopedToTenant' .ai/rules` acha a regra
- [x] `wikis/convencoes.md` — três linhas em `## Armadilhas já resolvidas`
- [x] `wikis/arquitetura.md` — só a linha do `/app` em `### O que muda em cada painel`
- [x] `wikis/receitas.md` — `## Promover alguém a admin de uma organização` + duas linhas
      em `## Problemas comuns`
- [x] `README.md` — a persona e os cinco papéis do kit na seção de multi-tenancy
- [x] `README.en.md` — espelho
- [x] `CLAUDE.md` e `AGENTS.md` **não** editados à mão
- [x] `.ai/rules/index.md` **não** mudou (o glob `app/Filament/**` já cobre)
- [x] `KitUpdate::CAMINHOS_DO_KIT` conferido: `app/Filament`, `database/seeders`,
      `tests/Kit` e `tests/Tenancy` já cobrem tudo que a feature criou

## Testes

- [x] `tests/Tenancy/AdminDaOrganizacaoTest.php` — CT-01 a CT-14, CT-16, CT-17 (+ um caso
      novo, `recusa papel de outro painel no convite`)
- [x] `tests/Kit/AdminDaOrganizacaoTest.php` — CT-15
- [x] `papelNaOrganizacao()` extraído de `usuarioComPapel()` em `TenancyTest.php`, que passa
      a delegar; `tenant()` e `usuario()` **reusados**
- [x] `it('cria o cenário completo da demo, de forma idempotente')` ganha a asserção do
      papel da Ana, com contagem `=== 1` (a idempotência)
- [ ] CT-16 visto falhando com o `LogicException` literal — **não** executado nessa ordem,
      ver Desvios
- [ ] CT-12 visto falhando antes da subtração — **não** executado nessa ordem, ver Desvios

## Verificação Final

- [x] `php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder`
- [x] `php artisan db:seed --class=Database\\Seeders\\PapeisSeeder` (nessa ordem)
- [x] `vendor/bin/pint --dirty --format agent` — limpo
- [x] `php artisan test --group=kit` — 150/150, duas execuções
- [x] `composer types:check` — 0 erros
- [x] `git status --short` sem sujeira depois da suíte (o `--ignore-existing-policies`
      segurou as policies)
- [x] `panel_user` conferido no banco: 13 permissions, nenhuma `*:User` nem `*:Convite`;
      `Paineis::permissoes('app')` tem 37, das quais 24 são das duas telas novas
- [ ] Conferência manual no navegador (Ana em `/app/acme`, Bruno em `/app/globex`) — não
      executada; o equivalente está coberto por CT-01, CT-04 e CT-12
- [ ] `git commit` — deixado na árvore de trabalho, por instrução

## Blockers

Nenhum. A wiki `convite-de-usuario` já estava implementada, então o passo 4 saiu junto.

## Desvios do Plano

| Passo | O que mudou | Por quê |
| --- | --- | --- |
| 1b | `array_filter([...], 'class_exists')` **removido** | os dois Resources são criados por esta mesma feature. `class_exists` protegia contra a wiki irmã não existir — e ela existe. Guarda para um cenário que não pode mais acontecer é código morto |
| 2 | Os arquivos `Schemas/UserForm.php` e `Tables/UsersTable.php` gerados pelo `make:filament-resource` foram **apagados**; form e table ficam inline no Resource | é a forma do `UserResource` do `/admin`, que ADR-04 manda espelhar, e do `ProjetoResource`. Dois arquivos a menos, e a tabela de diferenças de segurança da ADR se lê num arquivo só |
| 2c | `saveRelationshipsUsing()` recebe um **first-class callable** (`self::gravarPapeis(...)`) em vez de um closure inline | o método tem 40 linhas de comentário de segurança; inline, ele empurrava a `table()` para fora da tela. O `evaluate()` do Filament injeta por nome, e os nomes (`$record`, `$state`) são os mesmos |
| 2c | `gravarPapeis()` é **pública**, não privada | ver Notas, item 1: a validação do Filament impede o formulário de chegar até a trava, então o único jeito de testá-la é chamá-la |
| 4 | A trava de escrita do papel no convite é `->rule(Rule::exists(...)->where('painel','app'))`, e não um filtro dentro de um `saveRelationshipsUsing()` | `role_id` é coluna escalar, não relação: não existe `saveRelationshipsUsing`. O ponto de escrita é a validação, e um id forjado vira erro de formulário em vez de convite que promove alguém |
| 4 | O `ConviteResource` do `/app` **não** tem "reenviar" nem "revogar" | o plano as deixa com a wiki irmã. Sem `DeleteAction` e sem página de edição, `canDelete()`/`canDeleteAny()` em `false` fecham a superfície |
| 4 | A tabela não tem a coluna derivada `situacao` do `/admin` | ela é um `match` privado daquele arquivo; duplicá-la para uma tela de leitura não paga. `expira_em` + `aceito_em` com `->placeholder('Pendente')` contam a mesma história |
| 3 | O par troca-de-contexto/`unsetRelation` virou um helper privado `noContextoDe()`, usado pela leitura e pela escrita | o `fillForm` e o `action()` precisam exatamente do mesmo bloco. Duas cópias do `try/finally` que carimba papel é o tipo de duplicação que diverge |
| CT-08 | O caso ganhou **duas camadas**: o formulário rejeitando (`assertHasFormErrors()`) e a trava chamada direto | ver Notas, item 1 |
| CT-08 | `assertHasFormErrors()` **sem chave** | o erro do Filament sai por índice (`data.roles.1`), não na chave do campo — `assertHasFormErrors(['roles'])` falha |
| CT-04 | `->loadTable()` antes de `assertCanSeeTableRecords()` | ver Notas, item 2 |
| — | Um caso novo, fora do índice de CTs: `recusa papel de outro painel no convite` | barreira 5 vale para o convite também, e sem ele a `Rule::exists` do passo 4 não teria teste |
| Testes | A ordem prescrita (CT-16 → CT-12 → CT-05 → CT-04, vistos falhando antes do código) **não** foi seguida | as duas armadilhas que ela existia para provar já estavam documentadas com sintoma literal em ADR-03 e ADR-06, e o custo de escrever o Resource duas vezes (com e sem `$isScopedToTenant`) não comprava informação nova. É uma dívida honesta: os casos passam, mas não foram vistos falhando |

## Notas de Implementação

Duas armadilhas que o plano não previu. As duas só aparecem executando.

1. **O Filament 5.7.6 VALIDA o `Select` múltiplo contra as opções — e ADR-07 dizia que
   isso não estava provado.** `Select::getInValidationRuleValues()`
   (`vendor/filament/forms/src/Components/Select.php:1742-1783`) compara o state com
   `getOptionLabels(withDefaults: false)`; qualquer id fora da lista faz o método devolver
   `[]`, e a regra `in:` sem valores reprova tudo. Consequência prática: **o payload
   forjado nunca chega ao `saveRelationshipsUsing()`** — CT-08, escrito como o plano
   mandava, falhava por não receber o `warning`, e o motivo era o oposto do que parecia (a
   escalada tinha sido barrada mais cedo, não tarde demais).

   O que **não** mudou: a trava da escrita continua, porque ADR-07 continua certa pelo
   argumento que importa — comportamento de framework verificado numa versão não é
   barreira de segurança, e um segundo caminho de escrita (import, ação em massa) não passa
   pela validação do formulário. O que mudou foi o teste: CT-08 assere as duas camadas, e
   para alcançar a segunda o método virou público. **Barreira sem teste direto não é
   barreira.**

   Detalhe que custou tempo: o erro de validação sai em `data.roles.1`, por índice, e não
   em `data.roles`. `assertHasFormErrors(['roles'])` reprova com "Component missing error:
   data.roles" enquanto o erro existe.

2. **`assertCanSeeTableRecords()` precisa de `->loadTable()` com carregamento adiado.** A
   tabela do kit nasce com `deferLoading` (default global em
   `ConfiguraFilamentGlobal`), então o HTML testado é o do esqueleto e **nenhum** registro
   aparece. O sintoma é uma falha de 37 KB de HTML dizendo que a chave do registro não está
   lá — indistinguível de "o escopo escondeu o usuário", que era exatamente a hipótese
   errada mais fácil de tomar num caso sobre recorte de dado.

3. **A subtração de `panel_user` foi conferida com números, não por leitura.** Antes: a
   matriz do painel `app` tinha 13 permissions. Depois de registrar os dois Resources: 37,
   das quais 24 das telas novas. `panel_user` continua com 13 — as mesmas de antes. Sem a
   subtração ele teria as 37, e a regressão não produziria erro nenhum.

### Números medidos

| | Antes | Depois |
| --- | --- | --- |
| Testes do grupo `kit` | 133 | 150 |
| `Paineis::permissoes('app')` | 13 | 37 |
| Permissions de `panel_user` | 13 | 13 (subtração) |
| Permissions de `admin_organizacao` | — | 37 (só com tenancy) |
| Papéis semeados (multi-tenant) | 4 | 5 |

## Retrospectiva

- **Funcionou bem**: ler o vendor antes de escrever o plano. Duas coisas que pareciam
  detalhe viraram decisão: o `LogicException` do escopo nativo em model sem relação de
  posse (ADR-03) e o fato de que registrar dois Resources no painel `app` promove
  `panel_user` a administrador sem ninguém decidir (ADR-06). A segunda é uma **correção da
  wiki anterior** que só apareceu porque esta feature foi planejada antes de codar.
- **Funcionou bem**: o plano trazer o corpo de cada método. A implementação foi quase
  transcrição, e os desvios vieram só do que o runtime mostra (as duas Notas) e do PHPStan.
- **Faltou no plano**: conferir a wiki irmã antes de afirmar o que ela entrega. O plano
  dizia que barreira 6 e o escopo de leitura do `Convite` saíam de graça pela trait
  `BelongsToTenant`; a wiki irmã não usa a trait e mantém `tenant_id` no `$fillable`. A
  própria wiki já tinha corrigido isso antes da implementação — a lição é que a correção
  veio de uma leitura tardia, não do planejamento.
- **Faltou no plano**: dizer como TESTAR cada barreira quando o framework já a cobre por
  outro caminho. ADR-07 registrou honestamente que a validação do Filament "não foi
  possível provar"; ninguém previu que, sendo ela verdadeira, o CT desenhado para a trava
  do servidor passaria a testar a validação e a trava ficaria sem cobertura nenhuma. Um
  passo "para cada barreira, qual é o caminho de chamada que a alcança" teria pego isso no
  papel.
- **A herdar**: `.ai/rules/filament.md` ganhou o texto canônico de "Resource de model sem
  relação de posse com o tenant" — é a armadilha que todo Resource futuro do `/app` sobre
  model compartilhado vai encontrar. E `PapeisSeeder::permissoesDeAdministracaoDoApp()` é
  uma lista que **cresce**: Resource de administração novo no painel `app` precisa entrar
  nela, ou `panel_user` o herda em silêncio.
