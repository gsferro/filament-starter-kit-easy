<?php

declare(strict_types=1);

namespace App\Policies;

use Croustibat\FilamentJobsMonitor\Models\QueueMonitor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class QueueMonitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QueueMonitor');
    }

    public function view(AuthUser $authUser, QueueMonitor $queueMonitor): bool
    {
        return $authUser->can('View:QueueMonitor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QueueMonitor');
    }

    public function update(AuthUser $authUser, QueueMonitor $queueMonitor): bool
    {
        return $authUser->can('Update:QueueMonitor');
    }

    public function delete(AuthUser $authUser, QueueMonitor $queueMonitor): bool
    {
        return $authUser->can('Delete:QueueMonitor');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QueueMonitor');
    }

    public function restore(AuthUser $authUser, QueueMonitor $queueMonitor): bool
    {
        return $authUser->can('Restore:QueueMonitor');
    }

    public function forceDelete(AuthUser $authUser, QueueMonitor $queueMonitor): bool
    {
        return $authUser->can('ForceDelete:QueueMonitor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QueueMonitor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QueueMonitor');
    }

    public function replicate(AuthUser $authUser, QueueMonitor $queueMonitor): bool
    {
        return $authUser->can('Replicate:QueueMonitor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QueueMonitor');
    }
}
