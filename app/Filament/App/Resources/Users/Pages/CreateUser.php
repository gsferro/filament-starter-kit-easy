<?php

namespace App\Filament\App\Resources\Users\Pages;

use App\Filament\App\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * O usuário nasce vinculado à organização corrente.
     *
     * Uma linha em vez do observer nativo do Filament (`observeTenancyModelCreation`, que
     * faria o mesmo `syncWithoutDetaching` para relação BelongsToMany): aquele observer só
     * existe quando `$isScopedToTenant` é true, e ligá-lo traria junto o escopo de leitura
     * que ADR-03 recusa. Não dá para ter metade.
     *
     * Sem esta linha o usuário nasceria órfão: criado, e invisível na própria tela que o
     * criou — `getEloquentQuery()` filtra pela pivot.
     */
    protected function afterCreate(): void
    {
        $tenant  = Filament::getTenant();
        $usuario = $this->record;

        if (! $tenant instanceof Tenant || ! $usuario instanceof User) {
            return;
        }

        $usuario->tenants()->syncWithoutDetaching([$tenant->getKey()]);

        Log::channel('autenticacao')->info(
            "[CreateUser@afterCreate] Usuário criado e vinculado à organização | alvo: {$usuario->id} - tenant: {$tenant->getKey()}",
            [
                'alvo_id'     => $usuario->id,
                'tenant_id'   => $tenant->getKey(),
                'executor_id' => Auth::id(),
            ],
        );
    }
}
