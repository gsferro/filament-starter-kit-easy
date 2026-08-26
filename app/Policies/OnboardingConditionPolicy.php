<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Wallacemartinss\FilamentOnboarding\Models\OnboardingCondition;

/**
 * A policy do kit para o modelo de vendor — ver `OnboardingFlowPolicy` para o motivo.
 */
class OnboardingConditionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OnboardingCondition');
    }

    public function view(AuthUser $authUser, OnboardingCondition $onboardingCondition): bool
    {
        return $authUser->can('View:OnboardingCondition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OnboardingCondition');
    }

    public function update(AuthUser $authUser, OnboardingCondition $onboardingCondition): bool
    {
        return $authUser->can('Update:OnboardingCondition');
    }

    public function delete(AuthUser $authUser, OnboardingCondition $onboardingCondition): bool
    {
        return $authUser->can('Delete:OnboardingCondition');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OnboardingCondition');
    }

    public function restore(AuthUser $authUser, OnboardingCondition $onboardingCondition): bool
    {
        return $authUser->can('Restore:OnboardingCondition');
    }

    public function forceDelete(AuthUser $authUser, OnboardingCondition $onboardingCondition): bool
    {
        return $authUser->can('ForceDelete:OnboardingCondition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OnboardingCondition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OnboardingCondition');
    }

    public function replicate(AuthUser $authUser, OnboardingCondition $onboardingCondition): bool
    {
        return $authUser->can('Replicate:OnboardingCondition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OnboardingCondition');
    }
}
