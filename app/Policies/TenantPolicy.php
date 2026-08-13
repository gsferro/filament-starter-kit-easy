<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Policy do cadastro de tenants — mesmo padrão Shield das demais do kit
 * (permissions geradas pelo ShieldPermissionsSeeder, no formato `Acao:Model`).
 *
 * `master_global` atravessa tudo pelo `Gate::before` do KitServiceProvider.
 */
class TenantPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tenant');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Tenant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tenant');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Tenant');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Tenant');
    }
}
