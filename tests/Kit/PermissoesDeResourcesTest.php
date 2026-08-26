<?php

use App\Filament\Admin\Resources\AgentesIa\AgenteIaResource;
use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use App\Filament\Infra\Resources\ComposerReleasePackages\ComposerReleasePackageResource;
use BezhanSalleh\FilamentExceptions\Resources\ExceptionResource;
use Bityukov\CommandCenter\Filament\Resources\CommandRecordResource;
use Croustibat\FilamentJobsMonitor\Resources\QueueMonitorResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Tapp\FilamentAuditing\Filament\Resources\Audits\AuditResource;
use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource;
use Tapp\FilamentMailLog\Resources\MailLogResource;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingConditions\OnboardingConditionResource;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingFlows\OnboardingFlowResource;

/**
 * A permissão `ViewAny:{Model}` decide o acesso a TODO resource dos painéis globais — de fato.
 *
 * ## O buraco que este sweep fecha (N-29 da auditoria de aderência ao Blueprint)
 *
 * O kit tinha enforço estrutural para Pages (`PermissoesDeTelasTest`) e Widgets
 * (`PermissoesDeWidgetsTest`), e nenhum para Resources. A primeira rodada deste sweep encontrou o
 * que a falta de enforço escondia: **oito resources de modelo de vendor abriam com `ViewAny`
 * revogada**, porque o Laravel só descobre policy para `App\Models\*` e ninguém registrava as oito
 * `App\Policies\*Policy` de vendor. Permissão no banco, checkbox na tela de papéis, e nada
 * decidindo. Ver `App\Support\PoliciesDeVendor`.
 *
 * ## Um teste por caso, e por quê
 *
 * A primeira versão percorria os 16 resources num único `it()`. Deu 302 e 500 que não existem em
 * request real — vazamento de sessão e de painel entre iterações no mesmo processo. Dataset dá app
 * e banco frescos por caso, e a falha diz o nome do resource.
 *
 * ## O par tem / não-tem
 *
 * Revogar do papel REAL via `semAPermissao()`, nunca papel vazio — papel vazio perde
 * `canAccessPanel()` e o 403 vem da porta do painel, não do resource.
 *
 * Só `/admin` e `/infra`: o `/app` é escopado e os resources dele têm `canAccess()` condicionado a
 * `kit.tenancy.enabled`, `false` nesta suíte. O sweep do `/app` vive em `tests/Tenancy`.
 *
 * Ver ADR-04 da wiki `aderencia-ao-blueprint`: enforço automático antes de prosa.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * Os resources dos dois painéis globais, escritos — e não descobertos — porque dataset roda antes
 * do app existir. O caso de âncora abaixo compara esta lista com `getResources()` de cada painel:
 * resource novo fica vermelho lá, com o nome, até entrar aqui.
 *
 * @return array<string, array{0: string, 1: class-string<\Filament\Resources\Resource>}>
 */
function resourcesDosPaineisGlobais(): array
{
    return [
        'admin: AgenteIa'            => ['admin', AgenteIaResource::class],
        'admin: Convite'             => ['admin', ConviteResource::class],
        'admin: Role'                => ['admin', RoleResource::class],
        'admin: User'                => ['admin', UserResource::class],
        'admin: OnboardingFlow'      => ['admin', OnboardingFlowResource::class],
        'admin: OnboardingCondition' => ['admin', OnboardingConditionResource::class],
        'admin: Exception'           => ['admin', ExceptionResource::class],
        'infra: AiRun'               => ['infra', AiRunResource::class],
        'infra: QueueMonitor'        => ['infra', QueueMonitorResource::class],
        'infra: AuthenticationLog'   => ['infra', AuthenticationLogResource::class],
        'infra: Audit'               => ['infra', AuditResource::class],
        'infra: ComposerRelease'     => ['infra', ComposerReleasePackageResource::class],
        'infra: Exception'           => ['infra', ExceptionResource::class],
        'infra: MailLog'             => ['infra', MailLogResource::class],
    ];
}

/**
 * Fora do par tem/não-tem, com o motivo — e nunca fora da âncora.
 *
 * `TenantResource`: `canAccess()` exige `kit.tenancy.enabled` (`TenantResource.php`), que é `false`
 * nesta suíte — o papel `admin` toma 403 com e sem a permissão. O par tem/não-tem dele vive em
 * `tests/Tenancy/PermissoesDeTenantResourceTest.php`, com a tenancy ligada.
 *
 * `CommandRecordResource`: o vendor decide o acesso pelo gate `command-center:manage-commands`
 * (`CommandRecordResource.php:46`), que o kit deixa deliberadamente SEM `define` para só o
 * `master_global` passar (ver `KitServiceProvider`). O papel `infra` toma 403 com e sem a
 * permissão, então o par não discrimina nada aqui. A policy dele está registrada mesmo assim —
 * o caso de registro abaixo cobre.
 *
 * @return array<string, array{0: string, 1: class-string<\Filament\Resources\Resource>}>
 */
function resourcesForaDoPar(): array
{
    return [
        'admin: Tenant'        => ['admin', TenantResource::class],
        'infra: CommandRecord' => ['infra', CommandRecordResource::class],
    ];
}

function chaveViewAny(string $resource): string
{
    return 'ViewAny:'.class_basename($resource::getModel());
}

/**
 * Âncora de população: a lista escrita bate com o que os painéis registram. Resource novo em
 * qualquer dos dois fica vermelho aqui, com o FQCN, até entrar no sweep.
 */
it('conhece todos os resources dos paineis globais', function (): void {
    $esperados = collect(resourcesDosPaineisGlobais())->merge(resourcesForaDoPar());

    foreach (['admin', 'infra'] as $painel) {
        noPainelDoShield($painel);

        $registrados = array_values(Filament::getPanel($painel)->getResources());
        $listados    = $esperados->filter(fn (array $par): bool => $par[0] === $painel)->pluck(1)->values()->all();

        expect($registrados)->toEqualCanonicalizing($listados,
            "Os resources do painel {$painel} mudaram. Atualize resourcesDosPaineisGlobais() em ".__FILE__
        );
    }
})->group('kit');

/**
 * Toda policy consultável: `Gate::getPolicyFor()` do modelo de cada resource NÃO é null.
 *
 * É o oráculo direto do achado — antes de `PoliciesDeVendor::registrar()`, oito destes davam null.
 * Sem este caso, remover a linha do provider passaria no par tem/não-tem de qualquer resource cujo
 * papel não tenha a permissão por outro motivo.
 */
it('tem policy registrada para o modelo de cada resource', function (string $painel, string $resource): void {
    noPainelDoShield($painel);

    expect(Gate::getPolicyFor($resource::getModel()))->not->toBeNull(
        class_basename($resource).': nenhuma policy registrada para '.$resource::getModel().'. '
        .'Modelo de vendor não é descoberto pelo Laravel — ver App\Support\PoliciesDeVendor.'
    );
})->with(array_merge(resourcesDosPaineisGlobais(), resourcesForaDoPar()))->group('kit');

/**
 * A metade válida: o papel do painel, COM a permissão, abre o índice.
 *
 * Sem ela, uma implementação que negasse tudo passaria no caso seguinte.
 */
it('abre o indice para o papel do painel que tem a permissao', function (string $painel, string $resource): void {
    noPainelDoShield($painel);

    $this->actingAs(usuarioDoKit($painel, "{$painel}@example.com"))
        ->get($resource::getUrl('index', panel: $painel))
        ->assertOk();
})->with(resourcesDosPaineisGlobais())->group('kit');

/**
 * A metade que faltava: sem `ViewAny:X`, o mesmo papel toma 403.
 *
 * Reprova resource novo sem policy, policy não registrada, ou policy que o Filament não consulta.
 */
it('nega o indice ao papel do painel sem a permissao ViewAny', function (string $painel, string $resource): void {
    noPainelDoShield($painel);
    semAPermissao($painel, chaveViewAny($resource));

    $this->actingAs(usuarioDoKit($painel, "{$painel}@example.com"))
        ->get($resource::getUrl('index', panel: $painel))
        ->assertForbidden();
})->with(resourcesDosPaineisGlobais())->group('kit');
