<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Onboarding;

use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Support\TutorialGuiado;
use Wallacemartinss\FilamentOnboarding\Resources\OnboardingConditions\OnboardingConditionResource;

/**
 * A autoria das condições de onboarding (`/admin/onboarding-conditions`), com o interruptor do tutorial
 * guiado na frente (wiki `onboarding-guiado`, RQ-15): desligado, sai do menu, do hub, do ⌘K e do
 * dock — inclusive para o `master_global`.
 *
 * Por que subclasse e não a policy: o `Gate::before` do `master_global` responde `true` antes de
 * qualquer policy, então `viewAny()` falso não tiraria o item do menu dele. É o par
 * `canAccess()`/`shouldRegisterNavigation()` de `AgenteIaResource` (`.ai/rules/filament.md`). As
 * Pages continuam as do vendor; o 404 da URL é do middleware `ExigeTutorialGuiadoLigado`.
 * Registrada pelo `discoverResources()` do painel, no lugar do `manageFlows()` do plugin.
 */
class CondicaoResource extends OnboardingConditionResource
{
    use BadgeContagemNavegacao;

    public static function canAccess(): bool
    {
        return TutorialGuiado::habilitado() && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return TutorialGuiado::habilitado() && parent::shouldRegisterNavigation();
    }
}
