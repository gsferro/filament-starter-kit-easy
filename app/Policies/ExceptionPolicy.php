<?php

declare(strict_types=1);

namespace App\Policies;

use BezhanSalleh\FilamentExceptions\Models\Exception;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExceptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Exception');
    }

    public function view(AuthUser $authUser, Exception $exception): bool
    {
        return $authUser->can('View:Exception');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Exception');
    }

    public function update(AuthUser $authUser, Exception $exception): bool
    {
        return $authUser->can('Update:Exception');
    }

    public function delete(AuthUser $authUser, Exception $exception): bool
    {
        return $authUser->can('Delete:Exception');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Exception');
    }

    public function restore(AuthUser $authUser, Exception $exception): bool
    {
        return $authUser->can('Restore:Exception');
    }

    public function forceDelete(AuthUser $authUser, Exception $exception): bool
    {
        return $authUser->can('ForceDelete:Exception');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Exception');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Exception');
    }

    public function replicate(AuthUser $authUser, Exception $exception): bool
    {
        return $authUser->can('Replicate:Exception');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Exception');
    }

    /**
     * Importar CSV é escrita em massa — permissão separada de `create` de propósito.
     */
    public function import(AuthUser $authUser): bool
    {
        return $authUser->can('Import:Exception');
    }

    /**
     * Exportar é levar a listagem inteira embora num arquivo, e isso não é o mesmo que
     * poder abrir a tela: `ViewAny` mostra na tela, `Export` deixa sair da aplicação.
     */
    public function export(AuthUser $authUser): bool
    {
        return $authUser->can('Export:Exception');
    }
}
