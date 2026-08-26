<?php

use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\RelationManagers\UsersRelationManager;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\App\Pages\HubDoNegocio;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * As permissões de Action que só existem com organização no contexto.
 *
 * Aqui vivem as três Actions do `UsersRelationManager` — que precisam de um `Tenant` como
 * ownerRecord — e as duas de `ConvitesRecebidos`, cuja persona é `panel_user`/`admin_app` dentro de
 * uma organização. `admin_app` **só existe nesta suíte** (`.ai/rules/testes.md` §"Nem todo papel do
 * kit existe em toda suíte").
 *
 * As duas Actions nativas do relation manager entram no escopo por uma razão que o vendor documenta:
 *
 * > `vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:348-353`
 * > *"Security: `AssociateAction`, `AttachAction`, `DetachAction`, and `DissociateAction` only check
 * > `isReadOnly()` — they do not check specific policy methods."*
 *
 * E a linha do pivot `tenant_user` que elas criam é exatamente o que `User::canAccessTenant()`
 * consulta para liberar `/app/{slug}`.
 *
 * Ver `.../04-casos-de-teste.md` (R2, R4 e R6).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Uma organização, um administrador da instalação e um usuário comum vinculado.
 *
 * **Três pessoas distintas**, e não é conveniência: colapsar executor e alvo na mesma pessoa deixaria
 * a barreira de identidade sem exercício nenhum, porque a permissão do executor e o registro-alvo
 * seriam a mesma linha.
 *
 * @return array{acme: Tenant, ana: User, beto: User}
 */
function trioDaOrganizacao(): array
{
    $acme = tenant('Acme', 'acme');

    $ana  = usuarioComPapel('admin', null, 'ana@example.test');
    $beto = usuarioComPapel('panel_user', $acme, 'beto@example.test');

    $acme->users()->attach($beto);

    return ['acme' => $acme, 'ana' => $ana, 'beto' => $beto];
}

/** O relation manager de usuários daquela organização, já no painel /admin. */
function relationManagerDeUsuarios(Tenant $acme, User $executor): Testable
{
    Filament::setCurrentPanel('admin');

    test()->actingAs($executor);

    return Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $acme,
        'pageClass'   => EditTenant::class,
    ]);
}

/*
|--------------------------------------------------------------------------
| R4 — as três Actions do relation manager de usuários da organização
|--------------------------------------------------------------------------
*/

/**
 * CT-09 e CT-10 — as duas metades da visibilidade das três Actions.
 *
 * A última asserção — "as outras Actions da mesma tela continuam visíveis" — é o que mata "a
 * permissão foi posta na Action errada": sem ela, dar a `AttachAction` a chave da `DetachAction`
 * passaria nas duas metades de cada linha.
 */
it('mostra cada Action do relation manager só para quem tem a permissão dela', function (string $acao, string $permissao, bool $comPermissao): void {
    if (! $comPermissao) {
        semAPermissao('admin', $permissao);
    }

    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = trioDaOrganizacao();

    $tela  = relationManagerDeUsuarios($acme, $ana);
    // `attach` é header action da TABELA (`->headerActions()`), então precisa de `->table()` sem
    // registro; as outras duas são de linha e levam o registro.
    $alvo = $acao === 'attach'
        ? TestAction::make($acao)->table()
        : TestAction::make($acao)->table($beto);

    $comPermissao ? $tela->assertActionVisible($alvo) : $tela->assertActionHidden($alvo);

    // Controle: uma Action de OUTRA permissão da mesma tela não foi afetada.
    $outra = $acao === 'papeisNaOrganizacao'
        ? TestAction::make('detach')->table($beto)
        : TestAction::make('papeisNaOrganizacao')->table($beto);

    $tela->assertActionVisible($outra);
})->with(function (): iterable {
    $linhas = [
        'vincular'        => ['attach', 'VincularUsuario:Tenant'],
        'desvincular'     => ['detach', 'DesvincularUsuario:Tenant'],
        'atribuir papéis' => ['papeisNaOrganizacao', 'AtribuirPapeis:Tenant'],
    ];

    foreach ($linhas as $rotulo => $linha) {
        yield "{$rotulo}, com a permissão" => [...$linha, true];
        yield "{$rotulo}, sem a permissão" => [...$linha, false];
    }
});

/**
 * CT-12 — sem a permissão, a atribuição de papéis não grava.
 *
 * A metade de EFEITO, que a visibilidade não prova. `admin_app` é o papel mais sensível do kit — é
 * quem administra uma organização — e é o que esta Action concede.
 */
it('não grava papel na organização quando a permissão de atribuir foi revogada', function (): void {
    semAPermissao('admin', 'AtribuirPapeis:Tenant');

    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = trioDaOrganizacao();

    $adminDaOrg  = Role::findByName('admin_app');
    $papeisAntes = $beto->roles->pluck('name')->sort()->values()->all();

    relationManagerDeUsuarios($acme, $ana)
        ->assertActionHidden(TestAction::make('papeisNaOrganizacao')->table($beto));

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $adminDaOrg->getKey(),
    ]);

    expect($beto->fresh()?->roles->pluck('name')->sort()->values()->all())->toBe($papeisAntes);
});

/**
 * CT-28 — sem a permissão, o desvínculo não remove o vínculo.
 *
 * `detach` é o **verbo irmão** de `attach`, e verbo irmão não herda evidência: uma implementação que
 * autorizasse só a `AttachAction` passaria em todo caso cuja prova de efeito viesse do primeiro
 * verbo. O oráculo é o pivot `tenant_user`, que é a linha que `User::canAccessTenant()` consulta.
 */
it('não remove o vínculo com a organização quando a permissão de desvincular foi revogada', function (): void {
    semAPermissao('admin', 'DesvincularUsuario:Tenant');

    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = trioDaOrganizacao();

    relationManagerDeUsuarios($acme, $ana)
        ->assertActionHidden(TestAction::make('detach')->table($beto));

    $this->assertDatabaseHas('tenant_user', [
        'tenant_id' => $acme->id,
        'user_id'   => $beto->id,
    ]);
});

/**
 * CT-29 — ter a permissão de atribuir papéis não deixa pedir papel de OUTRO painel.
 *
 * Mass assignment: o state vem do cliente, e um id de papel `admin` ou `infra` gravado por aqui daria
 * acesso à INSTALAÇÃO a partir da tela de uma organização. O filtro `where('painel','app')` é defesa
 * anterior a esta feature e nenhum caso a exercitava — e agora que a Action tem permissão própria, o
 * caminho "tenho a permissão, logo peço o papel que eu quiser" é novo.
 *
 * O oráculo é o pivot: a chamada pode até ser aceita, mas o papel de fora do `/app` **não grava**.
 */
it('recusa papel de fora do painel de negócio mesmo com a permissão de atribuir', function (): void {
    ['acme' => $acme, 'ana' => $ana, 'beto' => $beto] = trioDaOrganizacao();

    $doInfra = Role::findByName('infra');

    expect($ana->can('AtribuirPapeis:Tenant'))->toBeTrue();

    relationManagerDeUsuarios($acme, $ana)
        ->callAction(TestAction::make('papeisNaOrganizacao')->table($beto), [
            'roles' => [$doInfra->getKey()],
        ]);

    $this->assertDatabaseMissing(pivotDePapeis(), [
        'model_id' => $beto->id,
        'role_id'  => $doInfra->getKey(),
    ]);

    expect($beto->fresh()?->hasRole('infra'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| R4 — as duas Actions da caixa de convites recebidos
|--------------------------------------------------------------------------
*/

/**
 * CT-13 — @premissa o usuário comum aceita o convite dele, porque a permissão nasce concedida.
 *
 * É a metade positiva de RQ-07 sob a premissa registrada no `00-requisito.md`: `Aceitar:Convite` é
 * knob de configuração, e ela nasce **com** o `panel_user`. Se ela nascesse fora, o fluxo de convite
 * quebraria para o público-alvo dele sem erro nenhum — só o botão desapareceria.
 */
it('deixa o usuário comum aceitar o convite endereçado a ele', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = usuarioComPapel('panel_user', $globex, 'carla@example.test');
    $globex->users()->attach($carla);

    $oferta = ofertaPara('carla@example.test', $acme);

    noPainelDa($globex);
    $this->actingAs($carla);

    Livewire::test(ConvitesRecebidos::class)
        ->loadTable()
        ->callAction(TestAction::make('aceitar')->table($oferta));

    expect($oferta->fresh()?->aceito_em)->not->toBeNull()
        ->and($acme->users()->whereKey($carla->getKey())->exists())->toBeTrue();
});

/**
 * CT-14 — @premissa revogar a permissão de aceite impede o aceite pela tela.
 *
 * A outra metade da premissa: quem usa o kit pode revogar. As duas asserções de não-efeito são o que
 * mata "recusa depois de gravar".
 */
it('impede o aceite pela tela quando a permissão de aceitar é revogada', function (): void {
    semAPermissao('panel_user', 'Aceitar:Convite');

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = usuarioComPapel('panel_user', $globex, 'carla@example.test');
    $globex->users()->attach($carla);

    $oferta = ofertaPara('carla@example.test', $acme);

    noPainelDa($globex);
    $this->actingAs($carla);

    Livewire::test(ConvitesRecebidos::class)
        ->loadTable()
        ->assertActionExists(TestAction::make('aceitar')->table($oferta))
        ->assertActionHidden(TestAction::make('aceitar')->table($oferta))
        // O verbo irmão tem permissão PRÓPRIA: revogar o aceite não pode levar a recusa junto.
        ->assertActionVisible(TestAction::make('recusar')->table($oferta));

    expect($oferta->fresh()?->aceito_em)->toBeNull()
        ->and($acme->users()->whereKey($carla->getKey())->exists())->toBeFalse();
});

/**
 * CT-30 — ter a permissão de aceite NÃO deixa aceitar o convite de outra pessoa.
 *
 * A ordem entre a checagem de permissão e a barreira de identidade é nova nesta feature, e é o que
 * este caso fixa. A persona é discriminante justamente porque ela **tem** `Aceitar:Convite`.
 *
 * A barreira real continua sendo `Convite::exigirDono()`, chamado na primeira linha de
 * `aceitarComoUsuarioExistente()` (`.ai/rules/filament.md` §2) — a permissão não a substitui, e o
 * filtro por e-mail da tabela é conveniência de UI, não barreira.
 */
it('não deixa quem tem a permissão de aceite assumir o convite de outra pessoa', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $intruso = usuarioComPapel('panel_user', $globex, 'intruso@example.test');
    $globex->users()->attach($intruso);

    $daOutra = ofertaPara('dono@example.test', $acme);

    // Contexto da organização do intruso ANTES do `can()`: com `permission.teams` ligado, a relação
    // `roles` do spatie é filtrada pelo team corrente, e no contexto global ele não tem papel algum.
    // Sem esta linha o caso morreria no arranjo, e não na regra.
    noPainelDa($globex);
    $this->actingAs($intruso);

    expect($intruso->can('Aceitar:Convite'))->toBeTrue();

    // Direto no model, que é o chamador que job, comando e action em massa usariam — a tela é só um
    // deles. `.ai/rules/filament.md` §2: barreira sem teste direto não é barreira.
    expect(fn (): User => $daOutra->aceitarComoUsuarioExistente($intruso))
        ->toThrow(RuntimeException::class, 'Este convite não é para a sua conta.');

    expect($daOutra->fresh()?->aceito_em)->toBeNull()
        ->and($acme->users()->whereKey($intruso->getKey())->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| R2 e R6 — o painel de negócio: permissão + contexto, e a matriz do admin_app
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — no painel de negócio a permissão e o contexto de organização valem as duas.
 *
 * Duas visitas no mesmo caso, e é deliberado: o valor está em provar que a MESMA persona, na MESMA
 * organização, muda de resultado só pela permissão. Partido em dois cenários, cada metade passaria
 * com a permissão ignorada, porque a diferença estaria no arranjo e não na regra.
 */
it('exige a permissão e a organização para abrir o hub do negócio', function (): void {
    config(['kit.hub' => true]);

    $acme  = tenant('Acme', 'acme');
    $carla = usuarioComPapel('panel_user', $acme, 'carla@example.test');
    $acme->users()->attach($carla);

    $this->actingAs($carla);

    $rota = "/app/{$acme->slug}/".HubDoNegocio::getSlug();

    $this->get($rota)->assertSuccessful();

    semAPermissao('panel_user', 'View:HubDoNegocio');

    $this->get($rota)->assertForbidden();
});

/**
 * CT-18 — @premissa o administrador da organização recebe as duas permissões de convite.
 *
 * `admin_app` recebe a matriz do painel `app` inteira menos `permissoesForaDoApp()`, e as duas custom
 * entram nela pelo mapa de painéis do `PapeisSeeder`. Sem este caso, um recorte aplicado ao
 * `panel_user` e esquecido no `admin_app` (ou o contrário) ficaria verde na suíte `Kit`, onde
 * `admin_app` não existe.
 */
it('entrega as duas permissões de convite ao administrador da organização', function (string $permissao): void {
    expect(papelDoKit('admin_app')->hasPermissionTo($permissao))->toBeTrue();
})->with([
    ['Aceitar:Convite'],
    ['Recusar:Convite'],
]);

/*
|--------------------------------------------------------------------------
| A Action `recusar` e o item de menu `convitesRecebidos`
|--------------------------------------------------------------------------
| A auditoria de aderência ao Blueprint (N-30) achou a Action `recusar` de
| `ConvitesRecebidos` (`:139`) declarada, autorizada — e NUNCA executada pela tela: os casos
| acima só a veem (`assertActionVisible`). E o item de menu (`:162`) não tinha oráculo nenhum.
*/

/**
 * A recusa pela tela chega ao model: `recusado_em` carimbado, `situacao()` diz "Recusado", e a
 * pessoa NÃO entra na organização — recusar não pode vincular por engano.
 */
it('deixa o usuário comum recusar o convite endereçado a ele', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = usuarioComPapel('panel_user', $globex, 'carla@example.test');
    $globex->users()->attach($carla);

    $oferta = ofertaPara('carla@example.test', $acme);

    noPainelDa($globex);
    $this->actingAs($carla);

    Livewire::test(ConvitesRecebidos::class)
        ->loadTable()
        ->callAction(TestAction::make('recusar')->table($oferta))
        ->assertNotified('Convite recusado');

    $oferta->refresh();

    expect($oferta->recusado_em)->not->toBeNull('a Action rodou e o convite continua sem `recusado_em` — a recusa não chegou ao model')
        ->and($oferta->situacao())->toBe('Recusado')
        ->and($oferta->aceito_em)->toBeNull('recusar carimbou `aceito_em` — os dois verbos se misturaram')
        ->and($acme->users()->whereKey($carla->getKey())->exists())->toBeFalse('recusar vinculou a pessoa à organização');
});

/**
 * A metade negativa: sem `Recusar:Convite` a Action some da tela e o convite não muda.
 *
 * `callAction()` não serve como oráculo do não-efeito — o helper assere a visibilidade ANTES de
 * chamar (ver o comentário de CT-11 em `tests/Kit/PermissoesDeAcoesTest.php`). O que prova é o par
 * "existe na definição" + "oculta", mais o não-efeito no registro. O verbo irmão continua visível:
 * revogar a recusa não pode levar o aceite junto.
 */
it('impede a recusa pela tela quando a permissão de recusar é revogada', function (): void {
    semAPermissao('panel_user', 'Recusar:Convite');

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = usuarioComPapel('panel_user', $globex, 'carla@example.test');
    $globex->users()->attach($carla);

    $oferta = ofertaPara('carla@example.test', $acme);

    noPainelDa($globex);
    $this->actingAs($carla);

    Livewire::test(ConvitesRecebidos::class)
        ->loadTable()
        ->assertActionExists(TestAction::make('recusar')->table($oferta))
        ->assertActionHidden(TestAction::make('recusar')->table($oferta))
        ->assertActionVisible(TestAction::make('aceitar')->table($oferta));

    expect($oferta->fresh()?->recusado_em)->toBeNull('a Action estava oculta e o convite foi recusado mesmo assim')
        ->and($oferta->fresh()?->situacao())->toBe('Pendente');
});

/**
 * O item `convitesRecebidos` do menu do usuário (`ConvitesRecebidos::itemDeMenu()`).
 *
 * É Action de PAINEL, registrada em `AppPanelProvider::bootUsing()` — não é Action de componente,
 * então `assertActionVisible` não a alcança. O oráculo é o próprio painel: `getUserMenuItemGroups()`
 * é a DEFINIÇÃO (o item existe, com ou sem permissão) e `getUserMenuItems()` é o que o menu
 * RENDERIZA — ele já filtra por `isVisible()` (`HasUserMenu.php:206`), então "está na lista" é
 * "aparece no menu".
 *
 * A pessoa TEM um convite pendente nos dois lados: sem isso `visible()` devolveria `false` pela
 * contagem, e o caso negativo ficaria verde sem consultar a permissão.
 *
 * ## Por que o request ganha uma rota
 *
 * `Panel::boot()` do painel `app` passa pelo Breezy, que com tenancy lê
 * `request()->route()->parameter('tenant')` (`BreezyCore.php:112`) para montar a URL de "Meu
 * perfil". Fora de um request HTTP não há rota e o boot morre em `parameter() on null` — no
 * ARRANJO, sem defeito nenhum no código. A rota ligada abaixo é o mínimo que um request real teria.
 */
it('mostra o item de convites recebidos no menu só para quem tem View:ConvitesRecebidos', function (bool $comPermissao): void {
    if (! $comPermissao) {
        semAPermissao('panel_user', 'View:ConvitesRecebidos');
    }

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = usuarioComPapel('panel_user', $globex, 'carla@example.test');
    $globex->users()->attach($carla);

    ofertaPara('carla@example.test', $acme);

    $this->actingAs($carla);
    noPainelDoShield('app');
    noPainelDa($globex);

    $rota = (new Route('GET', '/app/{tenant}', fn (): null => null))->bind(Request::create("/app/{$globex->slug}"));
    request()->setRouteResolver(fn (): Route => $rota);

    Filament::bootCurrentPanel();

    $panel = Filament::getPanel('app');
    $nome  = fn (Action $acao): bool => $acao->getName() === 'convitesRecebidos';

    expect(collect($panel->getUserMenuItemGroups())->collapse()->contains($nome))
        ->toBeTrue('o item `convitesRecebidos` saiu do `userMenuItems()` do painel app')
        ->and(collect($panel->getUserMenuItems())->contains($nome))
        ->toBe($comPermissao, $comPermissao
            ? 'com a permissão e um convite pendente, o item sumiu do menu'
            : 'sem View:ConvitesRecebidos o item continua no menu — o `visible()` não consulta `canAccess()`');
})->with([
    'com a permissão' => [true],
    'sem a permissão' => [false],
]);
