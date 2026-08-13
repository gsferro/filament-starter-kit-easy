<?php

declare(strict_types=1);

namespace App\Policies;

use Fomvasss\AiTasks\Models\AiRun;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AiRunPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AiRun');
    }

    public function view(AuthUser $authUser, AiRun $aiRun): bool
    {
        return $authUser->can('View:AiRun');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AiRun');
    }

    public function update(AuthUser $authUser, AiRun $aiRun): bool
    {
        return $authUser->can('Update:AiRun');
    }

    public function delete(AuthUser $authUser, AiRun $aiRun): bool
    {
        return $authUser->can('Delete:AiRun');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AiRun');
    }

    public function restore(AuthUser $authUser, AiRun $aiRun): bool
    {
        return $authUser->can('Restore:AiRun');
    }

    public function forceDelete(AuthUser $authUser, AiRun $aiRun): bool
    {
        return $authUser->can('ForceDelete:AiRun');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AiRun');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AiRun');
    }

    public function replicate(AuthUser $authUser, AiRun $aiRun): bool
    {
        return $authUser->can('Replicate:AiRun');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AiRun');
    }
}
