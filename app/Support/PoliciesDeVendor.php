<?php

declare(strict_types=1);

namespace App\Support;

use App\Policies\AiRunPolicy;
use App\Policies\AuditPolicy;
use App\Policies\AuthenticationLogPolicy;
use App\Policies\CommandRecordPolicy;
use App\Policies\ComposerReleasePackageSnapshotPolicy;
use App\Policies\ExceptionPolicy;
use App\Policies\MailLogPolicy;
use App\Policies\OnboardingConditionPolicy;
use App\Policies\OnboardingFlowPolicy;
use App\Policies\QueueMonitorPolicy;
use BezhanSalleh\FilamentExceptions\Models\Exception;
use Bityukov\CommandCenter\Sources\CommandRecord;
use Croustibat\FilamentJobsMonitor\Models\QueueMonitor;
use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Support\Facades\Gate;
use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Tapp\FilamentAuditing\Models\Audit;
use Tapp\FilamentMailLog\Models\MailLog;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingCondition;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow;

/**
 * As policies do kit para modelos de VENDOR — e por que elas precisam ser registradas à mão.
 *
 * ## O achado (auditoria de aderência ao Blueprint, N-29)
 *
 * O Laravel descobre policy por convenção: `App\Models\X` → `App\Policies\XPolicy`. Para um
 * modelo de vendor — `Tapp\FilamentAuditing\Models\Audit` — ele procura
 * `Tapp\FilamentAuditing\Models\Policies\AuditPolicy`, que não existe, e devolve `null`. Nada
 * consulta a `App\Policies\AuditPolicy` que o kit escreveu.
 *
 * O efeito, medido em teste com controle: **oito resources abriam com `ViewAny:X` revogada**
 * (`/infra/audits`, `/infra/mail-logs`, `/infra/queue-monitors`, `/infra/authentication-logs`,
 * `/infra/exceptions`, `/admin/exceptions`, `/infra/composer-release-packages` e a policy do
 * `CommandRecord`), enquanto `/admin/users` — modelo do kit — fechava com 403. As permissões
 * dessas telas existiam no banco, apareciam como checkbox em `/admin/shield/roles`, e não decidiam
 * nada. É a mesma classe de defeito do F-01 (v0.20.0): uma trava que a aplicação acredita ter.
 *
 * O Shield sabe disso — `shield:generate` imprime "(requires registration)" para policy fora da
 * descoberta do Laravel, e oferece `enforcePolicies()`. **Não** usamos o do Shield: ele registra a
 * partir de `FilamentShield::getResources()`, que é `once()` — memoizado por processo no primeiro
 * painel que o tocar. Com três painéis, o que bootar primeiro venceria e os outros ficariam sem
 * registro, em silêncio. Oito linhas explícitas são determinísticas e greppáveis.
 *
 * `tests/Kit/PermissoesDeResourcesTest.php` guarda dois lados: que TODO resource dos painéis
 * globais tem policy registrada (este mapa não pode envelhecer), e que revogar `ViewAny` fecha o
 * índice de fato.
 */
final class PoliciesDeVendor
{
    /**
     * @var array<class-string, class-string>
     */
    public const MAPA = [
        AiRun::class                          => AiRunPolicy::class,
        Audit::class                          => AuditPolicy::class,
        AuthenticationLog::class              => AuthenticationLogPolicy::class,
        CommandRecord::class                  => CommandRecordPolicy::class,
        ComposerReleasePackageSnapshot::class => ComposerReleasePackageSnapshotPolicy::class,
        Exception::class                      => ExceptionPolicy::class,
        MailLog::class                        => MailLogPolicy::class,
        QueueMonitor::class                   => QueueMonitorPolicy::class,
        /*
         * Estes dois o pacote REGISTRA sozinho — com policies que devolvem `true` para tudo
         * (`OnboardingPolicy`, base das duas). O nosso `Gate::policy()` boota depois e vence; o
         * próprio pacote documenta isso no service provider dele.
         */
        OnboardingCondition::class            => OnboardingConditionPolicy::class,
        OnboardingFlow::class                 => OnboardingFlowPolicy::class,
    ];

    public static function registrar(): void
    {
        foreach (self::MAPA as $modelo => $policy) {
            Gate::policy($modelo, $policy);
        }
    }
}
