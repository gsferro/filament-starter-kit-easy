<?php

namespace App\Filament\Admin\Resources\Convites\Pages;

use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Models\Convite;
use Filament\Resources\Pages\CreateRecord;

class CreateConvite extends CreateRecord
{
    protected static string $resource = ConviteResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['convidado_por_id'] = auth()->id();

        return $data;
    }

    /**
     * O envio sai daqui, e NÃO de um Observer.
     *
     * Observer de model dispararia e-mail também a partir de seeder, teste e tinker —
     * efeito colateral escondido onde ninguém procura. Aqui o e-mail sai quando um
     * administrador clica.
     */
    protected function afterCreate(): void
    {
        /** @var Convite $convite */
        $convite = $this->record;

        $convite->enviar();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
