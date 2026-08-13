<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Projeto;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjetoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Projeto');
    }

    public function view(AuthUser $authUser, Projeto $projeto): bool
    {
        return $authUser->can('View:Projeto');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Projeto');
    }

    public function update(AuthUser $authUser, Projeto $projeto): bool
    {
        return $authUser->can('Update:Projeto');
    }

    public function delete(AuthUser $authUser, Projeto $projeto): bool
    {
        return $authUser->can('Delete:Projeto');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Projeto');
    }

    public function restore(AuthUser $authUser, Projeto $projeto): bool
    {
        return $authUser->can('Restore:Projeto');
    }

    public function forceDelete(AuthUser $authUser, Projeto $projeto): bool
    {
        return $authUser->can('ForceDelete:Projeto');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Projeto');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Projeto');
    }

    public function replicate(AuthUser $authUser, Projeto $projeto): bool
    {
        return $authUser->can('Replicate:Projeto');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Projeto');
    }
}
