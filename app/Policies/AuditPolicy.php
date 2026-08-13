<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Tapp\FilamentAuditing\Models\Audit;

class AuditPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Audit');
    }

    public function view(AuthUser $authUser, Audit $audit): bool
    {
        return $authUser->can('View:Audit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Audit');
    }

    public function update(AuthUser $authUser, Audit $audit): bool
    {
        return $authUser->can('Update:Audit');
    }

    public function delete(AuthUser $authUser, Audit $audit): bool
    {
        return $authUser->can('Delete:Audit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Audit');
    }

    public function restore(AuthUser $authUser, Audit $audit): bool
    {
        return $authUser->can('Restore:Audit');
    }

    public function forceDelete(AuthUser $authUser, Audit $audit): bool
    {
        return $authUser->can('ForceDelete:Audit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Audit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Audit');
    }

    public function replicate(AuthUser $authUser, Audit $audit): bool
    {
        return $authUser->can('Replicate:Audit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Audit');
    }
}
