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

    /**
     * Importar CSV é escrita em massa — permissão separada de `create` de propósito.
     */
    public function import(AuthUser $authUser): bool
    {
        return $authUser->can('Import:Tenant');
    }

    /**
     * Exportar é levar a listagem inteira embora num arquivo, e isso não é o mesmo que
     * poder abrir a tela: `ViewAny` mostra na tela, `Export` deixa sair da aplicação.
     */
    public function export(AuthUser $authUser): bool
    {
        return $authUser->can('Export:Tenant');
    }
}
