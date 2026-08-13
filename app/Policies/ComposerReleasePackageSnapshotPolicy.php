<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use MominAlZaraa\FilamentComposerReleaseNotifier\Models\ComposerReleasePackageSnapshot;

class ComposerReleasePackageSnapshotPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ComposerReleasePackageSnapshot');
    }

    public function view(AuthUser $authUser, ComposerReleasePackageSnapshot $composerReleasePackageSnapshot): bool
    {
        return $authUser->can('View:ComposerReleasePackageSnapshot');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ComposerReleasePackageSnapshot');
    }

    public function update(AuthUser $authUser, ComposerReleasePackageSnapshot $composerReleasePackageSnapshot): bool
    {
        return $authUser->can('Update:ComposerReleasePackageSnapshot');
    }

    public function delete(AuthUser $authUser, ComposerReleasePackageSnapshot $composerReleasePackageSnapshot): bool
    {
        return $authUser->can('Delete:ComposerReleasePackageSnapshot');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ComposerReleasePackageSnapshot');
    }

    public function restore(AuthUser $authUser, ComposerReleasePackageSnapshot $composerReleasePackageSnapshot): bool
    {
        return $authUser->can('Restore:ComposerReleasePackageSnapshot');
    }

    public function forceDelete(AuthUser $authUser, ComposerReleasePackageSnapshot $composerReleasePackageSnapshot): bool
    {
        return $authUser->can('ForceDelete:ComposerReleasePackageSnapshot');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ComposerReleasePackageSnapshot');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ComposerReleasePackageSnapshot');
    }

    public function replicate(AuthUser $authUser, ComposerReleasePackageSnapshot $composerReleasePackageSnapshot): bool
    {
        return $authUser->can('Replicate:ComposerReleasePackageSnapshot');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ComposerReleasePackageSnapshot');
    }
}
