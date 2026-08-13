<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use LogicException;
use Override;
use Spatie\Permission\Models\Role as SpatieRole;

class EditRole extends EditRecord
{
    /** @var Collection<int, string> */
    public Collection $permissions;

    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    #[Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->permissions = collect($data)
            ->filter(fn (mixed $permission, string $key): bool => ! in_array($key, ['name', 'guard_name', 'painel', 'select_all', Utils::getTenantModelForeignKey()], true))
            ->values()
            ->flatten()
            ->unique();

        if (Utils::isTenancyEnabled() && Arr::has($data, Utils::getTenantModelForeignKey()) && filled($data[Utils::getTenantModelForeignKey()])) {
            return Arr::only($data, ['name', 'guard_name', 'painel', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name', 'painel']);
    }

    protected function afterSave(): void
    {
        $permissionModels = collect();
        $this->permissions->each(function (string $permission) use ($permissionModels): void {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name'       => $permission,
                'guard_name' => $this->data['guard_name'],
            ]));
        });

        $papel = $this->record;

        /*
         * `CreateRecord::$record` é tipado como `Model`, e `syncPermissions()` vem da
         * trait `HasPermissions`. A checagem narra o tipo para o analisador E fecha o
         * caso impossível: um `permission.models.role` que não seja um papel do spatie
         * faria esta tela gravar o papel e perder as permissões, em silêncio.
         */
        if (! $papel instanceof SpatieRole) {
            throw new LogicException(
                'permission.models.role precisa estender '.SpatieRole::class.' para esta tela gravar permissões.'
            );
        }

        $papel->syncPermissions($permissionModels);

        Log::channel('autenticacao')->info(
            '[EditRole@afterSave] Papel gravado | papel: '.$this->data['name'].' - painel: '.($this->data['painel'] ?? 'nenhum'),
            [
                'role_id'    => $papel->getKey(),
                'papel'      => $this->data['name'],
                'painel'     => $this->data['painel'] ?? null,
                'permissoes' => $this->permissions->count(),
                'executor'   => auth()->id(),
            ],
        );

    }
}
