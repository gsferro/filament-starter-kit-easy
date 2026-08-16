<?php

namespace App\Filament\Admin\Resources\Convites\Pages;

use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Concerns\ConvidaEmMassa;
use App\Models\Role;
use App\Support\Papeis;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListConvites extends ListRecords
{
    use ConvidaEmMassa;

    protected static string $resource = ConviteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            $this->acaoDeConvidarEmMassa(
                // O mesmo Select do ConviteForm, sem o `->live()`: aqui nenhum campo depende
                // do painel do papel escolhido.
                Select::make('role_id')
                    ->label('Papel')
                    ->relationship('papel', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => Papeis::rotulo($record->name))
                    ->required()
                    ->preload()
                    ->searchable()
                    // O parâmetro TEM de se chamar `$record`: o Filament injeta closure de
                    // opção por NOME, não por tipo.
                    ->getOptionLabelFromRecordUsing(function (Model $record): string {
                        $painel = $record->getAttribute('painel');

                        return is_string($painel)
                            ? "{$record->getAttribute('name')} — /{$painel}"
                            : "{$record->getAttribute('name')} — sem painel";
                    })
                    ->helperText('Todos os endereços do lote nascem com este papel.'),
                escolheOrganizacao: true,
            ),
        ];
    }
}
