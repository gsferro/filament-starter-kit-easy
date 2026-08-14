<?php

namespace App\Filament\App\Resources\Convites\Pages;

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\Concerns\ConvidaEmMassa;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class ListConvites extends ListRecords
{
    use ConvidaEmMassa;
    use HasResizableColumn;

    protected static string $resource = ConviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            $this->acaoDeConvidarEmMassa(
                Select::make('role_id')
                    ->label('Papel')
                    // Barreira de UX: só papéis do painel app aparecem.
                    ->relationship('papel', 'name', fn (Builder $query): Builder => $query->where('painel', 'app'))
                    ->required()
                    ->preload()
                    ->searchable()
                    /*
                     * E a MESMA trava no servidor, copiada do form deste Resource. Sem ela o
                     * lote é o buraco que o convite individual fechou: um `role_id` forjado no
                     * state do Livewire criaria trinta `admin` da instalação de uma vez, a
                     * pedido de quem só administra uma organização.
                     */
                    ->rule(fn (): object => Rule::exists(config('permission.table_names.roles', 'roles'), 'id')
                        ->where('painel', 'app'))
                    ->helperText('Só papéis do painel de negócio.'),
                // Sem campo de organização: ela vem do painel, sempre. É o que faz o trait ler
                // `Filament::getTenant()` e falhar fechado sem organização corrente.
                escolheOrganizacao: false,
            ),
        ];
    }
}
