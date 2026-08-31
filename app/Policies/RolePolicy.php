<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Support\AdministradorDaInstalacao;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    /**
     * O registro do papel super-admin não é editado por quem não é `master_global`.
     *
     * A guarda é AQUI, e não nas Actions: `EditAction`, `DeleteAction` e
     * `DeleteBulkAction` consultam a policy do registro, então uma trava por
     * `->visible()` seria só UX — o `mountAction` do Livewire alcança ação escondida.
     * Sem isto, `admin` renomeia `master_global`, renomeia o próprio papel para
     * `master_global` e vira administrador da instalação em duas edições. F-01 da
     * auditoria Blueprint; ADR-01 da wiki travas-de-escalada-de-papeis.
     */
    public function update(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Update:Role') && AdministradorDaInstalacao::papelEditavelPor($role, $authUser);
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Delete:Role') && AdministradorDaInstalacao::papelEditavelPor($role, $authUser);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Role');
    }

    // `restore` e `forceDelete` não têm caminho de execução hoje — `App\Models\Role` não
    // usa `SoftDeletes` e nenhuma Action os alcança. A guarda entra assim mesmo: ligar
    // `SoftDeletes` amanhã reabriria o buraco sem ninguém notar. Sem CT por isso mesmo.
    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Restore:Role') && AdministradorDaInstalacao::papelEditavelPor($role, $authUser);
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('ForceDelete:Role') && AdministradorDaInstalacao::papelEditavelPor($role, $authUser);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Role');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Replicate:Role');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Role');
    }

    /**
     * Importar CSV é escrita em massa — permissão separada de `create` de propósito.
     */
    public function import(AuthUser $authUser): bool
    {
        return $authUser->can('Import:Role');
    }

    /**
     * Exportar é levar a listagem inteira embora num arquivo, e isso não é o mesmo que
     * poder abrir a tela: `ViewAny` mostra na tela, `Export` deixa sair da aplicação.
     */
    public function export(AuthUser $authUser): bool
    {
        return $authUser->can('Export:Role');
    }
}
