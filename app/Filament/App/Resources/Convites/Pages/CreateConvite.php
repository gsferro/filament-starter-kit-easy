<?php

namespace App\Filament\App\Resources\Convites\Pages;

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Models\Convite;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateConvite extends CreateRecord
{
    protected static string $resource = ConviteResource::class;

    /**
     * Barreira 6: a organização vem do PAINEL, nunca do payload.
     *
     * `Convite` tem `tenant_id` dentro do `$fillable` — o Select de organização do /admin
     * depende disso — então o `$fillable` NÃO protege aqui: um `tenant_id` forjado no
     * state do Livewire chegaria ao insert. Esta linha o sobrescreve antes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();

        $data['tenant_id']        = $tenant instanceof Tenant ? $tenant->getKey() : null;
        $data['convidado_por_id'] = Auth::id();

        return $data;
    }

    /**
     * O envio sai daqui, e não de um Observer — mesmo motivo do /admin: observer
     * dispararia e-mail a partir de seeder, teste e tinker.
     */
    protected function afterCreate(): void
    {
        /** @var Convite $convite */
        $convite = $this->record;

        $convite->enviar();

        Log::channel('autenticacao')->info(
            "[CreateConvite@afterCreate] Convite criado pela administração da organização | convite: {$convite->id} - tenant: {$convite->tenant_id}",
            [
                'convite_id'  => $convite->id,
                'tenant_id'   => $convite->tenant_id,
                'executor_id' => Auth::id(),
                'papeis'      => [$convite->papel?->getAttribute('name')],
            ],
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
