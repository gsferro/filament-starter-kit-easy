<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tenant;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TenantPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tenant');
    }

    public function view(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('View:Tenant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tenant');
    }

    public function update(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('Update:Tenant');
    }

    public function delete(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('Delete:Tenant');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Tenant');
    }

    public function restore(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('Restore:Tenant');
    }

    public function forceDelete(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('ForceDelete:Tenant');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Tenant');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Tenant');
    }

    public function replicate(AuthUser $authUser, Tenant $tenant): bool
    {
        return $authUser->can('Replicate:Tenant');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Tenant');
    }
}
