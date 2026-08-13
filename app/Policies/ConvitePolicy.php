<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Convite;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ConvitePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Convite');
    }

    public function view(AuthUser $authUser, Convite $convite): bool
    {
        return $authUser->can('View:Convite');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Convite');
    }

    public function update(AuthUser $authUser, Convite $convite): bool
    {
        return $authUser->can('Update:Convite');
    }

    public function delete(AuthUser $authUser, Convite $convite): bool
    {
        return $authUser->can('Delete:Convite');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Convite');
    }

    public function restore(AuthUser $authUser, Convite $convite): bool
    {
        return $authUser->can('Restore:Convite');
    }

    public function forceDelete(AuthUser $authUser, Convite $convite): bool
    {
        return $authUser->can('ForceDelete:Convite');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Convite');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Convite');
    }

    public function replicate(AuthUser $authUser, Convite $convite): bool
    {
        return $authUser->can('Replicate:Convite');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Convite');
    }
}
