<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Tapp\FilamentMailLog\Models\MailLog;

class MailLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MailLog');
    }

    public function view(AuthUser $authUser, MailLog $mailLog): bool
    {
        return $authUser->can('View:MailLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MailLog');
    }

    public function update(AuthUser $authUser, MailLog $mailLog): bool
    {
        return $authUser->can('Update:MailLog');
    }

    public function delete(AuthUser $authUser, MailLog $mailLog): bool
    {
        return $authUser->can('Delete:MailLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MailLog');
    }

    public function restore(AuthUser $authUser, MailLog $mailLog): bool
    {
        return $authUser->can('Restore:MailLog');
    }

    public function forceDelete(AuthUser $authUser, MailLog $mailLog): bool
    {
        return $authUser->can('ForceDelete:MailLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MailLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MailLog');
    }

    public function replicate(AuthUser $authUser, MailLog $mailLog): bool
    {
        return $authUser->can('Replicate:MailLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MailLog');
    }
}
