<?php

declare(strict_types=1);

namespace App\Policies;

use Bityukov\CommandCenter\Sources\CommandRecord;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CommandRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommandRecord');
    }

    public function view(AuthUser $authUser, CommandRecord $commandRecord): bool
    {
        return $authUser->can('View:CommandRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommandRecord');
    }

    public function update(AuthUser $authUser, CommandRecord $commandRecord): bool
    {
        return $authUser->can('Update:CommandRecord');
    }

    public function delete(AuthUser $authUser, CommandRecord $commandRecord): bool
    {
        return $authUser->can('Delete:CommandRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommandRecord');
    }

    public function restore(AuthUser $authUser, CommandRecord $commandRecord): bool
    {
        return $authUser->can('Restore:CommandRecord');
    }

    public function forceDelete(AuthUser $authUser, CommandRecord $commandRecord): bool
    {
        return $authUser->can('ForceDelete:CommandRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommandRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommandRecord');
    }

    public function replicate(AuthUser $authUser, CommandRecord $commandRecord): bool
    {
        return $authUser->can('Replicate:CommandRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommandRecord');
    }
}
