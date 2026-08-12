<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AgenteIa;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AgenteIaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AgenteIa');
    }

    public function view(AuthUser $authUser, AgenteIa $agenteIa): bool
    {
        return $authUser->can('View:AgenteIa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AgenteIa');
    }

    public function update(AuthUser $authUser, AgenteIa $agenteIa): bool
    {
        return $authUser->can('Update:AgenteIa');
    }

    public function delete(AuthUser $authUser, AgenteIa $agenteIa): bool
    {
        return $authUser->can('Delete:AgenteIa');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AgenteIa');
    }

    public function restore(AuthUser $authUser, AgenteIa $agenteIa): bool
    {
        return $authUser->can('Restore:AgenteIa');
    }

    public function forceDelete(AuthUser $authUser, AgenteIa $agenteIa): bool
    {
        return $authUser->can('ForceDelete:AgenteIa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AgenteIa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AgenteIa');
    }

    public function replicate(AuthUser $authUser, AgenteIa $agenteIa): bool
    {
        return $authUser->can('Replicate:AgenteIa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AgenteIa');
    }
}
