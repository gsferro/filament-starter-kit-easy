<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingFlow;

/**
 * A policy do kit para o modelo de vendor — no lugar da do pacote, que devolve `true` para tudo.
 *
 * `Wallacemartinss\FilamentOnboarding\Policies\OnboardingPolicy` (base das duas do pacote) é
 * `return true` em todas as abilities. O Shield gera `ViewAny:OnboardingFlow` e companhia, a
 * checkbox aparece em `/admin/shield/roles`, e nada a consulta: a auditoria de aderência ao
 * Blueprint mediu o índice abrindo com a permissão revogada.
 *
 * Registrada por `App\Support\PoliciesDeVendor` — `Gate::policy()` no provider do kit boota depois
 * do do pacote e vence, como o próprio pacote documenta no service provider dele.
 */
class OnboardingFlowPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OnboardingFlow');
    }

    public function view(AuthUser $authUser, OnboardingFlow $onboardingFlow): bool
    {
        return $authUser->can('View:OnboardingFlow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OnboardingFlow');
    }

    public function update(AuthUser $authUser, OnboardingFlow $onboardingFlow): bool
    {
        return $authUser->can('Update:OnboardingFlow');
    }

    public function delete(AuthUser $authUser, OnboardingFlow $onboardingFlow): bool
    {
        return $authUser->can('Delete:OnboardingFlow');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OnboardingFlow');
    }

    public function restore(AuthUser $authUser, OnboardingFlow $onboardingFlow): bool
    {
        return $authUser->can('Restore:OnboardingFlow');
    }

    public function forceDelete(AuthUser $authUser, OnboardingFlow $onboardingFlow): bool
    {
        return $authUser->can('ForceDelete:OnboardingFlow');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OnboardingFlow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OnboardingFlow');
    }

    public function replicate(AuthUser $authUser, OnboardingFlow $onboardingFlow): bool
    {
        return $authUser->can('Replicate:OnboardingFlow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OnboardingFlow');
    }
}
