<?php

use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Infra\Resources\AiRuns\Pages\ListAiRuns;
use App\Support\Paineis;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * As 6 permissões novas de Action, e o inventário que garante que nenhuma superfície ficou fora.
 *
 * Action do Filament **não consulta policy sozinha**: o vendor diz isso em comentário em
 * `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:16-21` — *"Authorization defaults to
 * `null` (allowed for all users)"* — e o `resolveIsAuthorized()` (`:106-107`) converte `null` em
 * permitido. Nem a Action NATIVA está coberta quando vive num RelationManager
 * (`vendor/filament/filament/src/Resources/RelationManagers/RelationManager.php:348-353`).
 *
 * O que vive aqui: `reenviar` (painel /admin, single-tenant), a matriz papel × permissão, a
 * selecionabilidade na tela de papéis, o recorte de painel das custom permissions, o inventário de
 * superfícies e o link externo.
 *
 * O que vive em `tests/Tenancy/PermissoesDeAcoesTenancyTest.php`: as três Actions do
 * `UsersRelationManager` (exigem organização no contexto) e as duas de `ConvitesRecebidos` (exigem
 * `admin_app`/`panel_user` com organização) — ver `.ai/rules/testes.md` §"Nem todo papel do kit
 * existe em toda suíte".
 *
 * Ver `.../04-casos-de-teste.md` (R4, R5, R6, R7, R8 e R10).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| R4 — a Action só aparece e só executa para quem tem a permissão dela
|--------------------------------------------------------------------------
*/

/**
 * CT-09 e CT-10 — as duas metades da visibilidade do `reenviar`.
 *
 * A segunda asserção (`revogar` continua visível) é o que mata "a permissão foi posta na Action
 * errada da mesma tela": sem ela, trocar `Reenviar:Convite` de lugar passaria nas duas metades.
 */
it('mostra a Action de reenvio só para quem tem a permissão dela', function (bool $comPermissao): void {
    if (! $comPermissao) {
        semAPermissao('admin', 'Reenviar:Convite');
    }

    // `ofertaPara()` vem do `tests/Pest.php`; o `enviar()` e o que grava o hash do token,
    // que e o oraculo de CT-11.
    $convite = ofertaPara('alvo@example.com')->fresh();
    $convite->enviar();

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));
    noPainelDoShield('admin');

    $tela = Livewire::test(ListConvites::class);

    $comPermissao
        ? $tela->assertActionVisible(TestAction::make('reenviar')->table($convite))
        : $tela->assertActionHidden(TestAction::make('reenviar')->table($convite));

    // A outra Action da mesma linha continua onde estava, com ou sem a permissão de reenvio.
    $tela->assertActionVisible(TestAction::make('delete')->table($convite));
})->with([
    'com a permissão' => [true],
    'sem a permissão' => [false],
]);

/**
 * CT-11 — sem a permissão, o reenvio não acontece: nem e-mail, nem token novo.
 *
 * ## Uma correção de fato, medida ao escrever este caso
 *
 * O `04-casos-de-teste.md` previa o mutante "autorização posta no `->visible()` em vez do
 * `->authorize()` — esconde na UI e o `callAction` executa". **Esse mutante não existe.** As duas
 * coisas passam pelo mesmo ponto: `mountAction()` consulta `isVisible()` E `isAuthorized()`, e uma
 * Action oculta por qualquer dos dois não é montada nem executada. A diferença entre `->visible()` e
 * `->authorize()` é semântica e de recurso (`authorizationTooltip()`, `authorizationNotification()`),
 * não de enforço.
 *
 * Consequência para o oráculo: `callAction()` não serve aqui, porque o helper do Pest assere a
 * visibilidade ANTES de chamar. O que resta — e é suficiente — é o par "oculta" mais as duas direções
 * do não-efeito, e a asserção de que a Action **existe** na definição da tela, que é o que separa
 * "escondida pela permissão" de "removida do código".
 */
it('não reenvia nem notifica quando a permissão de reenvio foi revogada', function (): void {
    semAPermissao('admin', 'Reenviar:Convite');

    $convite = ofertaPara('alvo@example.com')->fresh();
    $convite->enviar();
    $hashAntes = $convite->fresh()->getRawOriginal('token');

    // O fake entra DEPOIS do arranjo: `convitePendente()` chama `enviar()`, que notifica de
    // verdade. Fakear antes tornaria a asserção de "nada foi enviado" falsa por construção.
    Notification::fake();

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));
    noPainelDoShield('admin');

    Livewire::test(ListConvites::class)
        ->assertActionExists(TestAction::make('reenviar')->table($convite))
        ->assertActionHidden(TestAction::make('reenviar')->table($convite));

    Notification::assertNothingSent();

    expect($convite->fresh()?->getRawOriginal('token'))->toBe($hashAntes);
});

/*
|--------------------------------------------------------------------------
| R5 — as 6 permissões novas existem e são selecionáveis na tela de papéis
|--------------------------------------------------------------------------
*/

/**
 * CT-15 — a permissão nova existe no banco depois dos dois seeders.
 *
 * Exaustivo nas 6, e não amostrado: elas nascem por DOIS mecanismos diferentes (`resources.manage` e
 * `custom_permissions`), então amostrar uma de cada deixaria quatro sem prova de existência.
 *
 * A asserção de controle sobre `ViewAny:Convite` é o que mata "ligar `resources.manage` desligou o
 * `policies.merge` e o Resource perdeu as 14 chaves default".
 */
it('cria a permissão nova de Action no banco', function (string $permissao): void {
    expect(Permission::query()->where('name', $permissao)->where('guard_name', 'web')->exists())
        ->toBeTrue("a permissão {$permissao} não foi gerada — ressemeie o ShieldPermissionsSeeder")
        ->and(Permission::query()->where('name', 'ViewAny:Convite')->exists())->toBeTrue();
})->with([
    ['Reenviar:Convite'],
    ['VincularUsuario:Tenant'],
    ['DesvincularUsuario:Tenant'],
    ['AtribuirPapeis:Tenant'],
    ['Aceitar:Convite'],
    ['Recusar:Convite'],
]);

/**
 * CT-16 — a permissão nova é OFERECIDA como opção na tela de papéis.
 *
 * Distinto de CT-15, e é a cláusula "deixar disponivel para seleção" do requisito: uma permissão pode
 * existir no banco e não ser oferecida — é exatamente o que acontece com as duas custom se
 * `shield_resource.tabs.custom_permissions` ficar em `false`, e nada acusaria.
 *
 * O oráculo é o conjunto de opções que a tela monta, não o HTML: `getResourcePermissionsWithLabels()`
 * e `getCustomPermissions()` são o que os `CheckboxList` do vendor consomem
 * (`HasShieldFormComponents::getResourcePermissionOptions()` e
 * `getTabFormComponentForCustomPermissions()`). Afirmar sobre eles sobrevive a mudança de layout — o
 * que importa, porque a tela de papéis é escopo de outra feature.
 */
it('oferece a permissão nova de Resource como opção na tela de papéis', function (): void {
    noPainelDoShield('admin');

    $opcoes = array_keys((array) FilamentShield::getResourcePermissionsWithLabels(ConviteResource::class));

    expect($opcoes)->toContain('Reenviar:Convite');

    $doTenant = array_keys((array) FilamentShield::getResourcePermissionsWithLabels(
        TenantResource::class
    ));

    expect($doTenant)
        ->toContain('VincularUsuario:Tenant')
        ->toContain('DesvincularUsuario:Tenant')
        ->toContain('AtribuirPapeis:Tenant');
});

it('oferece a permissão custom como opção, com a aba ligada', function (): void {
    expect(config('filament-shield.shield_resource.tabs.custom_permissions'))
        ->toBeTrue('a aba de custom permissions está desligada — as duas chaves existem e ninguém consegue marcá-las');

    expect(array_keys((array) FilamentShield::getCustomPermissions()))
        ->toContain('Aceitar:Convite')
        ->toContain('Recusar:Convite');
});

/*
|--------------------------------------------------------------------------
| R6 — cada permissão nova nasce no papel certo e não nos outros
|--------------------------------------------------------------------------
*/

/**
 * CT-17 — a matriz permissão × papel, nas duas direções.
 *
 * As linhas de **vazamento custom** são o coração da regra e a única barreira executável de ADR-03:
 * sem o recorte de `PapeisSeeder::paineisDasPermissoesCustomizadas()`, `Aceitar:Convite` cai em
 * `admin` e `infra` — porque `transformCustomPermissions()` não conhece painel algum
 * (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:88-112`) e
 * `getEntitiesPermissions()` faz merge das chaves na matriz de todo painel (`FilamentShield.php:119`).
 *
 * Hoje o estrago seria inócuo (a Page é do `/app`), mas a PRÓXIMA custom permission — que
 * provavelmente será de administração — cairia no `panel_user`, que é a falha mais cara desta parte
 * do kit (`.ai/rules/filament.md` §4).
 */
it('entrega a permissão nova ao papel certo e a nenhum outro', function (string $permissao, string $papel, bool $tem): void {
    expect(papelDoKit($papel)->hasPermissionTo($permissao))->toBe($tem);
})->with([
    'reenviar no admin'                 => ['Reenviar:Convite', 'admin', true],
    'reenviar fora do painel vizinho'   => ['Reenviar:Convite', 'infra', false],
    'reenviar fora do usuário comum'    => ['Reenviar:Convite', 'panel_user', false],
    'vincular no admin'                 => ['VincularUsuario:Tenant', 'admin', true],
    'vincular fora do usuário comum'    => ['VincularUsuario:Tenant', 'panel_user', false],
    'desvincular no admin'              => ['DesvincularUsuario:Tenant', 'admin', true],
    'desvincular fora do vizinho'       => ['DesvincularUsuario:Tenant', 'infra', false],
    'desvincular fora do comum'         => ['DesvincularUsuario:Tenant', 'panel_user', false],
    'atribuir papéis no admin'          => ['AtribuirPapeis:Tenant', 'admin', true],
    'atribuir papéis fora do comum'     => ['AtribuirPapeis:Tenant', 'panel_user', false],
    'aceitar não vaza para o admin'     => ['Aceitar:Convite', 'admin', false],
    'aceitar não vaza para o infra'     => ['Aceitar:Convite', 'infra', false],
    'aceitar fica com o usuário comum'  => ['Aceitar:Convite', 'panel_user', true],
    'recusar não vaza para o admin'     => ['Recusar:Convite', 'admin', false],
    'recusar fica com o usuário comum'  => ['Recusar:Convite', 'panel_user', true],
]);

/**
 * CT-27 — rodar os dois seeders de novo não muda a matriz.
 *
 * `php artisan db:seed` sobre banco existente é o caminho real de quem atualiza o kit, e é a dimensão
 * **I**nterfaces da varredura. Nenhum outro caso roda dois passes: um recorte aplicado só na criação
 * do papel reintroduziria `Aceitar:Convite` em `admin` na segunda volta, e ficaria verde em CT-17.
 */
it('mantém a matriz depois de rodar os dois seeders uma segunda vez', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    expect(papelDoKit('admin')->hasPermissionTo('Reenviar:Convite'))->toBeTrue()
        ->and(papelDoKit('admin')->hasPermissionTo('Aceitar:Convite'))->toBeFalse()
        ->and(papelDoKit('panel_user')->hasPermissionTo('Aceitar:Convite'))->toBeTrue()
        // Uma linha só por permissão: sem isto, um `givePermissionTo` sem sync duplicaria a pivot.
        ->and(papelDoKit('admin')->permissions->where('name', 'Reenviar:Convite')->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| R7 — custom permission sem painel declarado não chega a papel nenhum
|--------------------------------------------------------------------------
*/

/**
 * CT-19 — as duas fontes da declaração de custom permission não divergem.
 *
 * O único caso desta wiki cujo oráculo é a consistência entre dois arquivos, e existe porque a
 * alternativa é prosa. A chave nasce em `config('filament-shield.custom_permissions')` e o painel
 * dela em `PapeisSeeder::paineisDasPermissoesCustomizadas()` — duas metades, dois arquivos.
 *
 * A primeira asserção pega a chave nova SEM painel declarado: ela não iria para papel nenhum
 * (fail-closed), o botão sumiria para todos e nada mais acusaria. A segunda pega a chave órfã —
 * mapa apontando para custom permission removida da config —, que é inócua hoje e vira mentira de
 * documentação amanhã.
 */
it('exige painel declarado para toda custom permission, e não aceita órfã', function (): void {
    $configuradas = array_keys((array) FilamentShield::getCustomPermissions());

    $mapa = new ReflectionMethod(PapeisSeeder::class, 'paineisDasPermissoesCustomizadas');
    $mapa->setAccessible(true);
    /** @var array<string, list<string>> $declaradas */
    $declaradas = $mapa->invoke(new PapeisSeeder);

    $semPainel = array_values(array_diff($configuradas, array_keys($declaradas)));
    $orfas     = array_values(array_diff(array_keys($declaradas), $configuradas));

    expect($semPainel)->toBe([], 'custom permission sem painel em PapeisSeeder::paineisDasPermissoesCustomizadas(): '.implode(', ', $semPainel))
        ->and($orfas)->toBe([], 'painel declarado para custom permission que não existe na config: '.implode(', ', $orfas));

    // E o painel declarado precisa ser um painel de verdade.
    foreach ($declaradas as $chave => $paineis) {
        expect(array_diff($paineis, array_keys(Paineis::opcoes())))
            ->toBe([], "painel inexistente declarado para {$chave}");
    }
});

/*
|--------------------------------------------------------------------------
| R8 — o link para destino protegido acompanha o acesso ao destino
|--------------------------------------------------------------------------
*/

/**
 * CT-20 e CT-31 — o link do dashboard externo de IA e o gate do destino dele.
 *
 * ## Hipótese REJEITADA, e é o resultado do caso
 *
 * A varredura desta feature listou este botão como furo de affordance: link sem `->visible()`, que
 * apareceria para quem abrisse a listagem e daria 403 no destino. **A leitura estava errada**, e a
 * medição está aqui: `AiRunResource::canAccess()` é `Auth::user()?->can('ver-ai-tasks')`
 * (`AiRunResource.php:81-84`), a MESMA expressão que protege a rota de destino. Não existe persona
 * que abra a listagem e falhe no gate — a tentativa de arranjar uma redefinindo o gate para `false`
 * fecha a tela inteira, porque é o mesmo gate.
 *
 * Logo um `->visible()` no botão seria no-op **e infalsificável**, e ele não foi acrescentado. O que
 * este caso afirma é a propriedade que existe de verdade: a affordance é gated pela própria tela.
 *
 * As duas linhas são o par. A de `admin` é a persona discriminante — papel de painel legítimo, sem o
 * gate de infra — e ela prova que a tela não é alcançável sem o gate do destino, que é o que a
 * cláusula "todo link precisa ter sua permissão especifica" pede aqui.
 */
it('gate a tela do ledger de IA com o mesmo gate do destino do link dela', function (string $papel, bool $alcanca): void {
    $usuario = usuarioDoKit($papel, "{$papel}@example.com");

    expect($usuario->can('ver-ai-tasks'))->toBe($alcanca);

    $this->actingAs($usuario)
        ->get('/infra/execucoes-ia')
        ->assertStatus($alcanca ? 200 : 403);
})->with([
    'passa no gate do destino'  => ['infra', true],
    'papel de outro painel'     => ['admin', false],
]);

/**
 * CT-20 (segunda metade) — para quem alcança a tela, o link está lá.
 *
 * Sem esta metade, o caso acima ficaria verde com o botão removido do `getHeaderActions()`.
 */
it('oferece o link do dashboard de IA para quem alcança o ledger', function (): void {
    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));

    noPainelDoShield('infra');
    noPainelBootado('infra');

    Livewire::test(ListAiRuns::class)->assertActionVisible('dashboardAiTasks');
});

/*
|--------------------------------------------------------------------------
| R10 — nenhuma Action nem link do kit fica fora do inventário
|--------------------------------------------------------------------------
*/

/**
 * CT-25 — o inventário de autorização das Actions e dos links do kit.
 *
 * R4 e R5 cobrem uma lista FECHADA de 6 Actions, e a lista foi escolhida pelo mesmo agente que
 * escreveu os cenários. Isso deixaria a cláusula "ver quais [...] ainda não tem permissões" e
 * "TODAS as actions" sem barreira executável: Action esquecida na varredura passa por todo o
 * conjunto, e Action NOVA nasce aberta em silêncio.
 *
 * Este caso é a barreira. **Fica vermelho quando alguém acrescenta uma Action ou um item de
 * navegação ao kit**, e a mensagem nomeia o arquivo e o nome. A linha a acrescentar é uma, no
 * inventário abaixo, e escrevê-la obriga a decidir como aquela superfície é autorizada — que é
 * exatamente o que o requisito pede.
 *
 * A varredura é sobre o TEXTO dos arquivos, não por reflexão: a maioria das Actions só existe dentro
 * de um closure de `recordActions()`, e instanciá-las exigiria montar cada tabela de cada painel. A
 * varredura de texto é grosseira e é o ponto — ela erra na direção certa, achando coisa a mais, e
 * coisa a mais custa uma linha.
 *
 * As Actions NATIVAS não casam com `::make('nome')` (a assinatura delas não recebe nome) e a
 * autorização delas é a policy do Resource — **exceto em RelationManager**, e é por isso que a
 * segunda varredura existe.
 */
it('não deixa nenhuma Action ou item de navegação do kit fora do inventário', function (): void {
    $encontradas = superficiesDeAcaoDoKit();
    $inventario  = inventarioDeAutorizacao();

    $fora  = array_values(array_diff(array_keys($encontradas), array_keys($inventario)));
    $orfas = array_values(array_diff(array_keys($inventario), array_keys($encontradas)));

    expect($fora)->toBe([], 'superfície no código e fora do inventário: '.implode(', ', $fora))
        ->and($orfas)->toBe([], 'inventário apontando para superfície que não existe mais: '.implode(', ', $orfas));

    // Toda entrada declara COMO é autorizada. Sem isto, o inventário viraria lista de nomes.
    $mecanismos = ['permissao', 'gate', 'gate-da-tela', 'policy', 'permissao-da-tela', 'aberta-por-decisao'];

    foreach ($inventario as $chave => $mecanismo) {
        expect($mecanismo)->toBeIn($mecanismos, "mecanismo desconhecido declarado para {$chave}");
    }
});

/**
 * CT-25 (segunda metade) — a Action NATIVA de RelationManager também entra.
 *
 * `getDefaultActionAuthorizationResponse()` do RelationManager só checa `isReadOnly()` para
 * `AttachAction`, `DetachAction`, `AssociateAction` e `DissociateAction` — o vendor diz isso em
 * comentário em `RelationManager.php:348-353`. A varredura de nome não as alcança, então elas têm
 * inventário próprio.
 */
it('não deixa nenhuma Action de RelationManager fora do inventário', function (): void {
    $encontradas = superficiesDeRelationManagerDoKit();
    $inventario  = inventarioDeRelationManager();

    $fora  = array_values(array_diff(array_keys($encontradas), array_keys($inventario)));
    $orfas = array_values(array_diff(array_keys($inventario), array_keys($encontradas)));

    expect($fora)->toBe([], 'Action de RelationManager fora do inventário: '.implode(', ', $fora))
        ->and($orfas)->toBe([], 'inventário de RelationManager apontando para Action inexistente: '.implode(', ', $orfas));
});

/**
 * Toda `Action::make('nome')`, `SpotlightAction::make(...)` e `NavigationItem::make('nome')` escrita
 * no kit, por `arquivo relativo::primeiro argumento`.
 *
 * Varre `app/Filament` e `app/Providers/Filament`. Comentário é filtrado com `token_get_all()`, e não
 * com regex: `.ai/rules/testes.md` registra que os arquivos bem comentados do kit CITAM o que
 * proíbem — o docblock de `ConvidaEmMassa` menciona `Action::make()` justamente para explicar por que
 * ele não basta, e um regex contaria a menção como declaração.
 *
 * @return array<string, string>
 */
function superficiesDeAcaoDoKit(): array
{
    $classes = ['Action', 'SpotlightAction', 'NavigationItem'];
    $achadas = [];

    foreach (arquivosPhpDoKit() as $relativo => $codigo) {
        $tokens = token_get_all($codigo);
        $total  = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            $token = $tokens[$i];

            if (! is_array($token) || $token[0] !== T_STRING || ! in_array($token[1], $classes, true)) {
                continue;
            }

            // `Classe` `::` `make` `(` `'nome'`
            if (($tokens[$i + 1][0] ?? null) !== T_DOUBLE_COLON) {
                continue;
            }

            if (($tokens[$i + 2][1] ?? null) !== 'make') {
                continue;
            }

            $argumento = $tokens[$i + 4] ?? null;

            if (! is_array($argumento) || $argumento[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $achadas["{$relativo}::".trim($argumento[1], "'\"")] = $token[1];
        }
    }

    return $achadas;
}

/**
 * Toda `*Action::make()` escrita dentro de um `RelationManagers/`, por `arquivo::ClasseDaAction`.
 *
 * Aqui o nome não existe (as nativas não recebem nome), então a chave é a CLASSE. É a granularidade
 * certa: o que decide se a Action está coberta é o tipo dela, não a instância.
 *
 * @return array<string, string>
 */
function superficiesDeRelationManagerDoKit(): array
{
    $achadas = [];

    foreach (arquivosPhpDoKit() as $relativo => $codigo) {
        if (! str_contains($relativo, 'RelationManagers/')) {
            continue;
        }

        $tokens = token_get_all($codigo);
        $total  = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            $token = $tokens[$i];

            if (! is_array($token) || $token[0] !== T_STRING || ! str_ends_with($token[1], 'Action')) {
                continue;
            }

            if (($tokens[$i + 1][0] ?? null) !== T_DOUBLE_COLON || ($tokens[$i + 2][1] ?? null) !== 'make') {
                continue;
            }

            $achadas["{$relativo}::{$token[1]}"] = $token[1];
        }
    }

    return $achadas;
}

/**
 * Os arquivos PHP do kit onde Action e navegação podem nascer, já com comentário removido.
 *
 * @return array<string, string> path relativo => código sem comentário
 */
function arquivosPhpDoKit(): array
{
    $arquivos = [];

    foreach (['app/Filament', 'app/Providers/Filament'] as $raiz) {
        $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($raiz)));

        foreach ($iterador as $arquivo) {
            if (! $arquivo->isFile() || $arquivo->getExtension() !== 'php') {
                continue;
            }

            // Duas normalizações, nesta ordem, porque no Windows o `base_path()` vem com
            // barra invertida: trocar as barras primeiro e só depois cortar o prefixo.
            $absoluto = str_replace('\\', '/', $arquivo->getPathname());
            $raizAbs  = str_replace('\\', '/', base_path()).'/';
            $relativo = str_replace($raizAbs, '', $absoluto);

            $codigo = (string) file_get_contents($arquivo->getPathname());

            $arquivos[$relativo] = implode('', array_map(
                static fn (array|string $t): string => is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : (is_array($t) ? $t[1] : $t),
                token_get_all($codigo),
            ));
        }
    }

    return $arquivos;
}

/**
 * COMO cada Action e cada link do kit é autorizado.
 *
 * Quatro mecanismos, e a escolha de cada um está no arquivo correspondente:
 *
 *   permissao          → `->authorize('{Acao}:{Model}')`, com a chave nascendo em
 *                        `config('filament-shield')`
 *   gate               → `->visible()` com o MESMO gate que protege o destino
 *   policy             → `->authorize('{metodo}')`, caindo na policy do model
 *   permissao-da-tela  → item de menu cuja visibilidade herda o `canAccess()` da Page destino
 *   aberta-por-decisao → deliberadamente sem barreira, com o motivo escrito aqui
 *
 * @return array<string, string>
 */
function inventarioDeAutorizacao(): array
{
    return [
        // Painel /admin
        'app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php::reenviar'                            => 'permissao',
        // Slide-over de leitura que lista quem tem o papel — logo, nome e e-mail de terceiros.
        // `->authorize('view')` resolve contra o record, ou seja `View:Role`: quem alcança UM papel
        // vê quem o tem. Não é `ViewAny:User` de propósito (ADR-07 da wiki `tela-de-perfis`), e o
        // contrapeso é o log em `Log::channel('autenticacao')` registrando quem consultou a lista.
        'app/Filament/Admin/Resources/Roles/RoleResource.php::usuarios'                                       => 'policy',
        'app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php::papeisNaOrganizacao' => 'permissao',
        'app/Filament/Concerns/ConvidaEmMassa.php::convidarEmMassa'                                           => 'policy',

        // Painel /app
        'app/Filament/App/Pages/ConvitesRecebidos.php::aceitar'           => 'permissao',
        'app/Filament/App/Pages/ConvitesRecebidos.php::recusar'           => 'permissao',
        // Item no menu do usuário: `->visible(static::canAccess() && ...)`, logo herda
        // `View:ConvitesRecebidos` da própria Page.
        'app/Filament/App/Pages/ConvitesRecebidos.php::convitesRecebidos' => 'permissao-da-tela',

        // Painel /infra — os dois apontam para a mesma rota de pacote e usam o mesmo gate dela.
        // O botão do header NÃO tem `->visible()`, e a ausência é medida: `AiRunResource::canAccess()`
        // é o mesmo gate do destino, então a tela inteira já é a barreira. Ver o docblock do arquivo.
        'app/Filament/Infra/Resources/AiRuns/Pages/ListAiRuns.php::dashboardAiTasks' => 'gate-da-tela',
        // O item de menu é renderizado FORA do Resource, então lá o `->visible()` não é redundante.
        'app/Providers/Filament/InfraPanelProvider.php::dashboard-ia'                => 'gate',

        /*
         * Travar a PRÓPRIA sessão. Aberta por decisão, e não por esquecimento: uma permissão aqui
         * significaria "você não pode bloquear a sua tela", que não protege nada e tranca a pessoa
         * fora de um recurso de segurança dela mesma. A tela de destino é de autenticação e tem as
         * guardas próprias dela (`TelaBloqueio::mount()`).
         */
        'app/Filament/Pages/Auth/TelaBloqueio.php::lockSession' => 'aberta-por-decisao',

        /*
         * Sugestão "Criar X" do Spotlight. O nome é dinâmico (`'criar-'.$resource::getSlug()`), então
         * a varredura casa o prefixo literal. A autorização é do DESTINO: `AcoesDeCriacao::registrar()`
         * só registra o resource que passa em `canAccess()`, `canCreate()` e
         * `shouldRegisterNavigation()`.
         */
        'app/Filament/Spotlight/AcoesDeCriacao.php::criar-' => 'policy',
    ];
}

/**
 * As Actions de RelationManager do kit — inclusive as NATIVAS, que ali não são autorizadas por nada.
 *
 * @return array<string, string>
 */
function inventarioDeRelationManager(): array
{
    $arquivo = 'app/Filament/Admin/Resources/Tenants/RelationManagers/UsersRelationManager.php';

    return [
        // As três com `->authorize()`. `AttachAction` e `DetachAction` são NATIVAS e ainda assim
        // precisam — ver ADR-04 e o docblock do RelationManager.
        "{$arquivo}::Action"       => 'permissao',
        "{$arquivo}::AttachAction" => 'permissao',
        "{$arquivo}::DetachAction" => 'permissao',
    ];
}
