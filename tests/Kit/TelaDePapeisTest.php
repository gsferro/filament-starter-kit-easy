<?php

use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Users\UserResource as AdminUserResource;
use App\Models\Role;
use App\Support\Paineis;
use App\Support\Papeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\EmptyState;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

/**
 * A tela de papéis do /admin — rótulo, contagem de usuários, slide-over, contador de
 * permissões e guard.
 *
 * Wiki: `wikis/specs/feat/perfis-e-permissoes/tela-de-perfis/04-casos-de-teste.md`.
 *
 * As três páginas são do /admin, e teste de componente Livewire NÃO atravessa o middleware
 * que define e boota o painel: sem `noPainelBootado('admin')` o painel corrente é o default
 * e a página morre em "Plugin [filament-shield] is not registered for panel [infra]". O
 * mesmo motivo já documentado em `tests/Kit/PaineisTest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    noPainelBootado('admin');
});

/**
 * CT-01 — o recurso se apresenta como "Papéis", não como "Funções".
 *
 * As três asserções juntas, e não só a de navegação: configurar `navigationLabel()` e
 * esquecer `modelLabel()`/`pluralModelLabel()` deixa a navegação certa e o título da página
 * dizendo "Funções". É o mutante mais provável, porque a navegação é o que se vê primeiro.
 *
 * A leitura é pelos getters do Resource, não por uma string na página: é assim que o caso
 * também reprova alguém que escreva o rótulo direto no Resource e deixe o plugin devolvendo
 * "Funções" para quem consultar por ele — a navegação do kit consulta o plugin.
 */
it('apresenta o recurso de papeis com o termo do kit', function (): void {
    expect(RoleResource::getNavigationLabel())->toBe('Papéis')
        ->and(RoleResource::getModelLabel())->toBe('Papel')
        ->and(RoleResource::getPluralModelLabel())->toBe('Papéis');

    $this->actingAs(usuarioCom('master_global'))
        ->get('/admin/shield/roles')
        ->assertSuccessful()
        ->assertSee('Papéis')
        ->assertDontSee('Funções');
})->group('kit');

/**
 * CT-02 — o título do papel é o rótulo legível, nunca a chave.
 *
 * `getRecordTitle()` é o que alimenta breadcrumb, título da tela de edição e resultado da
 * busca global. O default do Filament devolve o atributo cru
 * (`vendor/filament/filament/src/Resources/Resource/Concerns/HasLabels.php:105-108`), e é
 * por isso que o breadcrumb dizia `panel_user`.
 *
 * A linha `auditor` está no dataset de propósito: é o caso em que `Str::headline()` devolve
 * algo indistinguível de um `ucfirst()` da chave, e um mutante que apenas capitalize acerta
 * essa linha. Ela existe para que nenhuma OUTRA linha seja tomada como suficiente — quem
 * discrimina de verdade são `panel_user` e `admin_app`, cujos rótulos vêm do mapa
 * `Papeis::ROTULOS` e não da chave, e `gerente_de_contas`, que exige o `Str::headline()`.
 */
it('usa o rotulo do papel como titulo do registro', function (string $chave, string $rotulo): void {
    // `firstOrCreate` porque três dos quatro papeis do dataset já vieram do `PapeisSeeder`,
    // e o `create` do spatie lança `RoleAlreadyExists`. O que o caso mede é a TRADUÇÃO da
    // chave, e para isso tanto faz quem criou a linha.
    $papel = Role::query()->firstOrCreate(['name' => $chave, 'guard_name' => 'web']);

    expect(RoleResource::getRecordTitle($papel))->toBe($rotulo);
})->with([
    'chave com rótulo próprio'      => ['panel_user', 'Painel App'],
    'outra com rótulo próprio'      => ['admin_app', 'Administrador App'],
    'derivada da chave'             => ['gerente_de_contas', 'Gerente De Contas'],
    'uma palavra, headline estável' => ['auditor', 'Auditor'],
])->group('kit');

/**
 * A metade do CT-02 que prova que o título CHEGA à tela.
 *
 * O caso acima chama o método; este confere que o breadcrumb da tela de alteração o usa.
 * Sem os dois, um `getRecordTitle()` correto que ninguém consome passaria.
 *
 * Sem `assertDontSee('panel_user')`: a chave aparece legitimamente no `value` do campo
 * "Nome" desta mesma tela. Asserção de ausência aqui reprovaria a tela certa.
 */
it('mostra o rotulo do papel no breadcrumb da tela de alteracao', function (): void {
    $papel = Role::findByName('panel_user');

    $this->actingAs(usuarioCom('master_global'))
        ->get("/admin/shield/roles/{$papel->getRouteKey()}/edit")
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo('panel_user'));
})->group('kit');

/**
 * CT-03 — sem registro, o título cai no rótulo singular do recurso.
 *
 * A busca global chama `getRecordTitle(null)`. Um método que assuma registro presente
 * derruba a busca do painel inteiro, não só esta tela.
 */
it('cai no rotulo singular quando nao ha papel', function (): void {
    expect(RoleResource::getRecordTitle(null))->toBe('Papel');
})->group('kit');

/**
 * CT-04 — a coluna diz quantos usuários têm o papel.
 *
 * A asserção é sobre o ESTADO da coluna, não sobre um `assertSee` do número: "3" aparece em
 * qualquer lugar de uma tabela, e `assertSee('0')` passaria com a célula vazia.
 *
 * A linha `0` é a que mata dois mutantes de uma vez: contar `permissions` no lugar de
 * `users` (copiar a coluna vizinha e esquecer de trocar a relação) e renderizar vazio em vez
 * de zero.
 */
it('conta os usuarios de cada papel na listagem', function (int $quantos): void {
    $papel = Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin']);
    $papel->givePermissionTo('ViewAny:Role');

    for ($i = 0; $i < $quantos; $i++) {
        usuario("suporte{$i}@example.com")->assignRole('suporte');
    }

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->assertTableColumnStateSet('users_count', $quantos, $papel);
})->with([
    'nenhum usuário' => 0,
    'um usuário'     => 1,
    'três usuários'  => 3,
])->group('kit');

/**
 * CT-05 — a coluna é ordenável pela quantidade.
 *
 * Ordenar por coluna agregada é o caminho em que um `withCount` mal formado falha com erro
 * de SQL em vez de número errado — e as duas contagens são diferentes de propósito: com
 * empate a ordenação não seria observável.
 */
it('ordena a listagem pela quantidade de usuarios', function (): void {
    $suporte = Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin']);
    $auditor = Role::create(['name' => 'auditor', 'guard_name' => 'web', 'painel' => 'admin']);

    usuario('a@example.com')->assignRole('suporte');
    usuario('b@example.com')->assignRole('suporte');
    usuario('c@example.com')->assignRole('auditor');

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->sortTable('users_count', 'desc')
        ->assertCanSeeTableRecords([$suporte, $auditor], inOrder: true);
})->group('kit');

/**
 * CT-07 — quem só pode LISTAR papéis não vê quem tem cada papel.
 *
 * É o caso que existe por causa de `vendor/filament/actions/src/Concerns/CanBeAuthorized.php`,
 * cujo próprio comentário diz: *"Actions do not have automatic policy-based authorization.
 * Authorization defaults to null (allowed for all users)"*. Sem `->authorize('view')` na
 * action, quem recebeu apenas `ViewAny:Role` para abrir a listagem passaria a ler o e-mail
 * de todos os usuários da instalação.
 *
 * A persona é a peça central: percorrer esta tela com `master_global` faria o caso passar
 * com a autorização removida, porque ele vence pelo `Gate::before`.
 */
it('esconde a lista de usuarios de quem so pode listar papeis', function (): void {
    $operador = Role::create(['name' => 'operador_de_papeis', 'guard_name' => 'web', 'painel' => 'admin']);
    $operador->givePermissionTo('ViewAny:Role');

    $pessoa = usuario('operador@example.com');
    $pessoa->assignRole('operador_de_papeis');

    $this->actingAs($pessoa);

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('usuarios')->table($operador));
})->group('kit');

/**
 * Contraprova do CT-07: com `View:Role` a ação aparece.
 *
 * Sem ela, o caso acima passaria com a action removida da tela — "escondida" e "inexistente"
 * são indistinguíveis por uma asserção de ausência sozinha.
 */
it('mostra a lista de usuarios para quem pode ver o papel', function (): void {
    $operador = Role::create(['name' => 'operador_de_papeis', 'guard_name' => 'web', 'painel' => 'admin']);
    $operador->givePermissionTo(['ViewAny:Role', 'View:Role']);

    $pessoa = usuario('operador@example.com');
    $pessoa->assignRole('operador_de_papeis');

    $this->actingAs($pessoa);

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->assertActionVisible(TestAction::make('usuarios')->table($operador));
})->group('kit');

/**
 * CT-08 — consultar quem tem o papel deixa rastro.
 *
 * Nenhuma cláusula do requisito pede log; este caso existe porque a superfície nova expõe
 * e-mail de terceiro, e leitura de dado de terceiro sem rastro é o tipo de coisa de que
 * ninguém sente falta até precisar. Ver ADR-07.
 *
 * O `Então` nomeia o CANAL: um log emitido no channel default em vez de `autenticacao` sai
 * do arquivo que o Logs Explorer do /infra mostra, e a trilha de autorização fica partida.
 *
 * `mountAction` e NÃO `callAction`, e a diferença é o ponto do caso: o slide-over não tem botão
 * de submit (`modalSubmitAction(false)`), então `callMountedAction` nunca acontece na tela. Um
 * log escrito em `->action()` ficaria verde com `callAction()` e **nunca aconteceria de
 * verdade** — e foi exatamente o defeito que a auditoria do diff encontrou. `mountAction`
 * reproduz o que o clique faz: abrir o painel lateral, e nada mais.
 */
it('registra a consulta da lista de usuarios do papel', function (): void {
    $canal  = espiarAutenticacao();
    $papel  = Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin']);
    $master = usuarioCom('master_global');

    usuario('suporte@example.com')->assignRole('suporte');

    $this->actingAs($master);

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->mountAction(TestAction::make('usuarios')->table($papel));

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[RoleResource@usuarios]')
            && $contexto['papel'] === 'suporte'
            && $contexto['role_id'] === $papel->getKey()
            && $contexto['executor'] === $master->getKey())
        ->once();
})->group('kit');

/**
 * CT-15 — papel novo mostra zero selecionadas em cada grupo.
 *
 * O total vem de `Paineis::resources()`, e não de um número escrito à mão: número de
 * permissão em rule ou em teste é o que faz o próximo agente concluir que a contagem está
 * certa quando não está. É a mesma lição registrada em `.ai/rules/filament.md`.
 *
 * `0/` e não apenas o total: o badge que o Shield já punha no tab externo é o total, e um
 * contador que só repita o total não acrescenta nada — passaria sem a feature.
 */
it('mostra zero permissoes selecionadas num papel novo', function (): void {
    $this->actingAs(usuarioCom('master_global'));

    $totalDoAdmin = collect(Paineis::resources()['admin'])
        ->sum(fn (array $entidade): int => count($entidade['permissions']));

    Livewire::test(CreateRole::class)
        ->assertSee('0/'.$totalDoAdmin);
})->group('kit');

/**
 * CT-16 — marcar uma permissão move a contagem daquele grupo, e só daquele.
 *
 * A segunda asserção é o que separa "conta o grupo" de "conta o formulário": um contador que
 * somasse o state inteiro mostraria 1 nos dois grupos.
 *
 * O nome do grupo é o FQCN do Resource, porque é esse o `name` que o Shield dá ao
 * CheckboxList (`HasShieldFormComponents::getCheckBoxListComponentForResource()` chama
 * `getCheckboxListFormComponent(name: $entity['resourceFqcn'], …)`). Se um upgrade trocar
 * essa chave, este caso é o que cai.
 */
it('soma as permissoes selecionadas por grupo', function (): void {
    $this->actingAs(usuarioCom('master_global'));

    $entidades = collect(Paineis::resources()['admin'])->values();
    $primeira  = $entidades->first();
    $segunda   = $entidades->get(1);

    expect($primeira)->not->toBeNull()->and($segunda)->not->toBeNull();

    $umaPermissao = array_column($primeira['permissions'], 'key')[0];

    Livewire::test(CreateRole::class)
        ->fillForm([$primeira['resourceFqcn'] => [$umaPermissao]])
        ->assertSee('1/'.count($primeira['permissions']))
        ->assertSee('0/'.count($segunda['permissions']));
})->group('kit');

/**
 * CT-18 — as opções de guard vêm de `config('auth.guards')`.
 *
 * O guard acrescentado em tempo de teste é o que discrimina: com uma lista escrita à mão
 * (`['web' => 'web']`) a opção `api` não existiria e a regra de servidor recusaria o valor.
 * Um `assertSee('web')` passaria nos dois casos, porque `web` é o default do campo.
 *
 * E prova de tabela junto: o papel gravado tem o guard escolhido.
 */
it('aceita qualquer guard configurado na aplicacao', function (): void {
    config(['auth.guards.api' => ['driver' => 'session', 'provider' => 'users']]);

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'suporte', 'guard_name' => 'api', 'painel' => 'admin'])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(config('permission.table_names.roles', 'roles'), [
        'name'       => 'suporte',
        'guard_name' => 'api',
    ]);
})->group('kit');

/**
 * CT-19 — guard fora da lista é recusado, e nada é gravado.
 *
 * O `Select` do Filament valida no SERVIDOR sozinho, e é isso que este caso prova:
 * `Select::getInValidationRuleValues()` (`vendor/filament/forms/src/Components/Select.php:1742-1774`)
 * devolve `[]` quando o state não casa com nenhuma opção, e
 * `CanBeValidated::getInValidationRule()` (`:808-815`) transforma isso em `Rule::in([])`, que
 * reprova qualquer valor. Um `->in()` nosso no campo SOBRESCREVERIA essa lógica por uma pior
 * — a nativa também cobre opção desabilitada. A primeira versão desta feature acrescentou o
 * `->in()`, com um comentário afirmando o contrário; a auditoria do diff derrubou os dois.
 *
 * Não confunda com `ConviteResource::role_id`, que PRECISA de `->rule()` explícito: lá o
 * Select é de `->relationship()` e a trava é de ESCOPO (só papéis do painel app), não de
 * domínio — `Rule::in` das opções não saberia recortar por painel.
 *
 * A segunda asserção não é redundante: uma implementação que valide DEPOIS de gravar passa
 * num caso que só confere o erro de formulário.
 */
it('recusa guard fora da lista de guards da aplicacao', function (): void {
    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'suporte', 'guard_name' => 'api-inventado', 'painel' => 'admin'])
        ->call('create')
        ->assertHasFormErrors(['guard_name']);

    $this->assertDatabaseMissing(config('permission.table_names.roles', 'roles'), ['name' => 'suporte']);
})->group('kit');

/**
 * CT-20 — guard vazio é recusado.
 *
 * O campo era `->nullable()` antes desta feature, e guard nulo não é inofensivo: ele chega
 * ao `firstOrCreate` de permission em `CreateRole@afterCreate` (`CreateRole.php:44-47`) e
 * cria permission com `guard_name` nulo, que `checkPermissionTo()` nunca encontra. O papel
 * grava, a tela responde, e as permissões dele não valem para ninguém.
 */
it('recusa papel sem guard', function (): void {
    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'suporte', 'guard_name' => null, 'painel' => 'admin'])
        ->call('create')
        ->assertHasFormErrors(['guard_name']);

    $this->assertDatabaseMissing(config('permission.table_names.roles', 'roles'), ['name' => 'suporte']);
})->group('kit');

/**
 * O estado vazio do slide-over — a lacuna que o roteiro "Desenhado × Implementado" do `05` abriu.
 *
 * O `EmptyState` e o `RepeatableEntry` têm `->visible()` COMPLEMENTARES, e é isso que torna o caso
 * discriminante: um erro de sinal num dos dois deixa os dois visíveis (a tabela de usuários vazia
 * ao lado do "nenhum usuário") ou nenhum (slide-over em branco). Por isso as duas asserções — o
 * texto do estado vazio E a ausência do cabeçalho da tabela.
 */
it('mostra estado vazio no slide-over de papel sem usuario', function (): void {
    $papel = Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin']);

    $this->actingAs(usuarioCom('master_global'));

    /*
     * A VISIBILIDADE dos dois componentes, e não `assertSee` do texto: o Filament não imprime o
     * conteúdo do modal no HTML do componente pai — ele vai por partial separado. Um `assertSee`
     * aqui ficaria vermelho com a tela certa.
     */
    Livewire::test(ListRoles::class)
        ->loadTable()
        ->mountAction(TestAction::make('usuarios')->table($papel))
        ->assertSchemaComponentExists(
            'semUsuarios',
            'mountedActionSchema0',
            fn (EmptyState $componente): bool => $componente->isVisible(),
        )
        ->assertSchemaComponentExists(
            'usuarios',
            'mountedActionSchema0',
            fn (RepeatableEntry $componente): bool => ! $componente->isVisible(),
        );
})->group('kit');

/**
 * A contraprova do caso acima: com usuário, a tabela aparece e o estado vazio não.
 *
 * Sem ela, o par de `->visible()` complementares passaria com os dois sinais invertidos.
 */
it('mostra a tabela de usuarios no slide-over de papel com usuario', function (): void {
    $papel = Role::create(['name' => 'suporte', 'guard_name' => 'web', 'painel' => 'admin']);
    usuario('suporte@example.com')->assignRole('suporte');

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(ListRoles::class)
        ->loadTable()
        ->mountAction(TestAction::make('usuarios')->table($papel))
        ->assertSchemaComponentExists(
            'usuarios',
            'mountedActionSchema0',
            fn (RepeatableEntry $componente): bool => $componente->isVisible(),
        )
        ->assertSchemaComponentExists(
            'semUsuarios',
            'mountedActionSchema0',
            fn (EmptyState $componente): bool => ! $componente->isVisible(),
        );
})->group('kit');

/**
 * QA-03 — marcar a permissão NA TELA de papéis destrava a tela protegida.
 *
 * Débito herdado de `feat/permissoes-de-telas-e-acoes`: o QA daquela branch deixou este cenário
 * pendente de propósito, para não conflitar com a reescrita do `RoleResource` que acontece aqui.
 *
 * É o único caso da suíte que fecha o ciclo inteiro de ponta a ponta — tela de papéis grava
 * permissão, `spatie` recarrega, policy responde diferente, HTTP muda de 403 para 200. Cada
 * metade tem cobertura própria em outro lugar; o que só este caso prova é que as duas se
 * ligam. Um `syncPermissions()` que gravasse na pivot errada, ou uma chave de CheckboxList que
 * não fosse o FQCN do Resource, passariam por todos os outros casos.
 *
 * O 403 ANTES não é cerimônia: sem ele, um papel que já tivesse a permissão por acidente (ou uma
 * policy que devolvesse `true` para todo mundo) faria o 200 depois parecer prova.
 *
 * `forgetCachedPermissions()` + `unsetRelation()` porque o `PermissionRegistrar` e o Eloquent
 * cacheiam permissões e papéis dentro do MESMO processo — num request real o cache nasce limpo, e
 * sem isto o caso mediria o cache em vez do banco.
 */
it('destrava a tela protegida ao marcar a permissao na tela de papeis', function (): void {
    noPainelBootado('admin');

    $papel  = Role::create(['name' => 'operador', 'guard_name' => 'web', 'painel' => 'admin']);
    $pessoa = usuario('operador@example.com');
    $pessoa->assignRole('operador');

    // Antes: o papel abre o painel (a coluna `painel` casa) e nenhuma tela dentro dele.
    $this->actingAs($pessoa)->get('/admin/users')->assertForbidden();

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(EditRole::class, ['record' => $papel->getRouteKey()])
        ->fillForm([
            'name'                 => 'operador',
            'guard_name'           => 'web',
            'painel'               => 'admin',
            // A chave do grupo é o FQCN do Resource — é esse o `name` que o Shield dá ao
            // CheckboxList. Se um upgrade trocar isso, este caso é o que cai.
            AdminUserResource::class => ['ViewAny:User'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($papel->fresh()->hasPermissionTo('ViewAny:User'))->toBeTrue();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $pessoa->unsetRelation('roles');
    $pessoa->unsetRelation('permissions');

    // Depois: a MESMA tela, o MESMO usuário, 200.
    $this->actingAs($pessoa)->get('/admin/users')->assertSuccessful();
})->group('kit');

/*
|--------------------------------------------------------------------------
| Abrir e salvar sem mexer devolve o que recebeu
|--------------------------------------------------------------------------
| O caso que faltava, e que custou perda de dado em produção.
|
| Toda a cobertura desta tela media MARCAR permissão: o cenário do checkbox
| que destranca a tela, o do select_all, o da contagem por grupo. Nenhum media
| a identidade — abrir um papel, não tocar em nada, salvar, e receber de volta
| exatamente o que estava lá.
|
| O defeito que isso esconde é do vendor, na fronteira com a customização do
| kit: `HasShieldFormComponents::setPermissionStateForRecordPermissions()`
| (`vendor/bezhansalleh/filament-shield/src/Traits/HasShieldFormComponents.php:81`)
| só hidrata o CheckboxList `if ($component->isVisible() ...)`. Com o collapse
| por painel do vendor, todas as seções estavam visíveis. Com o tab vertical
| que esta tela passou a usar, só a aba ativa está — as abas dos outros dois
| painéis nascem VAZIAS, e o `syncPermissions()` do `afterSave` remove o que
| não veio no state.
|
| Medido antes da correção, no papel `admin_app`: 47 permissões antes de
| salvar, 31 depois. As 14 de `Projeto` desapareceram porque `Projeto` só
| existe no painel /app — as de `Convite` e `User` sobreviveram por acidente,
| porque também aparecem na aba do /admin, que estava visível.
|
| O papel de OUTRO painel é o oráculo certo: um papel do /admin não falsifica
| nada, porque a aba dele é a que abre.
*/
it('preserva as permissoes de um papel de outro painel ao salvar sem alterar nada', function (string $papel): void {
    $registro = Role::query()->where('name', $papel)->sole();
    $antes    = $registro->permissions()->pluck('name')->sort()->values()->all();

    expect($antes)->not->toBeEmpty("o papel {$papel} precisa ter permissões para este caso valer");

    $this->actingAs(usuarioCom('master_global'));

    Livewire::test(EditRole::class, ['record' => $registro->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    $depois = $registro->fresh()->permissions()->pluck('name')->sort()->values()->all();

    expect($depois)->toBe($antes);
})->with([
    // Papel do /app editado pelo /admin: a aba dele NÃO é a que abre. `admin_app` seria o
    // caso mais gordo, mas ele só existe em `tests/Tenancy` (ver `.ai/rules/testes.md`), e
    // `panel_user` prova a mesma propriedade — é do painel /app e tem permissões.
    'panel_user' => ['panel_user'],
    // O de infra, pelo mesmo motivo.
    'infra' => ['infra'],
    // E o do próprio painel, que é o controle: se este quebrar, o problema é outro.
    'admin' => ['admin'],
]);

/*
|--------------------------------------------------------------------------
| A regra de conjunto do salvamento, provada direto
|--------------------------------------------------------------------------
| `EditRole::permissoesFinais()` é a única parte desta tela que dá para provar
| sem subir Livewire — e é a parte onde o erro custa dado.
|
| Por que não pelo componente: `fillForm()` refaz o preenchimento, e o
| preenchimento passa por `mutateFormDataBeforeFill()`, que semeia o state a
| partir do banco — ou seja, desfaz qualquer desmarcação que o caso tente
| arranjar. `set('data', …)` também não vence, porque as chaves são FQCN com
| barras invertidas e o caminho do Livewire é por pontos. Medido: as marcadas
| chegavam ao save ainda contendo `ViewAny:User`, e o caso reprovava com o
| código CORRETO.
|
| No navegador nada disso acontece: o fill roda uma vez, no mount, e o clique
| no checkbox altera o state sem refazer o fill. O par abaixo prova a regra; a
| ida ao banco fica com os três casos de preservação acima, que passam pelo
| componente de verdade.
*/
it('resolve o salvamento pela regra de conjunto', function (array $atuais, array $oferecidas, array $marcadas, array $esperado): void {
    $final = EditRole::permissoesFinais($atuais, $oferecidas, $marcadas);

    sort($final);
    sort($esperado);

    expect($final)->toBe($esperado);
})->with([
    // O defeito original: o formulário não mostrou nada do /infra, então não pode apagar nada.
    'o que o formulario nao mostrou fica' => [
        ['View:LogsExplorer', 'View:Pulse', 'ViewAny:User'],
        ['ViewAny:User'],
        ['ViewAny:User'],
        ['View:LogsExplorer', 'View:Pulse', 'ViewAny:User'],
    ],
    // O contrapeso: o que ele mostrou e voltou desmarcado SAI. Sem isto a tela vira
    // somente-adição e revogar acesso deixa de funcionar — igualmente grave, e mais silencioso.
    'o que ele mostrou e foi desmarcado sai' => [
        ['View:LogsExplorer', 'ViewAny:User', 'View:User'],
        ['ViewAny:User', 'View:User'],
        ['View:User'],
        ['View:LogsExplorer', 'View:User'],
    ],
    // Marcar algo novo entra.
    'o que foi marcado entra' => [
        ['ViewAny:User'],
        ['ViewAny:User', 'Create:User'],
        ['ViewAny:User', 'Create:User'],
        ['ViewAny:User', 'Create:User'],
    ],
    // Papel sem permissão nenhuma continua sem, e não estoura.
    'papel vazio continua vazio' => [[], ['ViewAny:User'], [], []],
    // Desmarcar tudo o que o formulário mostrou esvazia SÓ o alcance dele.
    'desmarcar tudo esvazia so o alcance' => [
        ['View:LogsExplorer', 'ViewAny:User'],
        ['ViewAny:User'],
        [],
        ['View:LogsExplorer'],
    ],
]);
