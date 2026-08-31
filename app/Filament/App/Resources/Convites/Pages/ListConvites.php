<?php

namespace App\Filament\App\Resources\Convites\Pages;

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\Concerns\ConvidaEmMassa;
use App\Models\Convite;
use App\Models\Role;
use App\Support\Papeis;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
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

            /*
             * Export de convites — **desligado de propósito**. Descomente ciente de que a
             * planilha sai com o e-mail de cada convidado. O `ConviteExporter` já deixa
             * `token` e `token_lembrete` fora: exportá-los seria distribuir chaves de
             * entrada, porque `Convite::aceitar()` valida o token e vincula o usuário à
             * organização com o papel do convite.
             *
             * Import não existe: convite é fluxo com e-mail, prazo e token rotativo.
             */
            // ExportAction::make()
            //     ->exporter(ConviteExporter::class)
            //     ->authorize('export'),

            $this->acaoDeConvidarEmMassa(
                Select::make('role_id')
                    ->label('Papel')
                    // Barreira de UX: só papéis do painel app aparecem.
                    ->relationship('papel', 'name', fn (Builder $query): Builder => $query->where('painel', 'app'))
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => Papeis::rotulo($record->name))
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

    /**
     * Abas de recorte: "Todos", "Pendentes" e "Aceitos".
     *
     * O recorte vem do model (`Convite::recorteDePendentes()` / `recorteDeAceitos()`), o
     * mesmo que alimenta o filtro "Pendente" do /admin — uma definição, três consumidores.
     * "Pendente" é o oposto de "aceito", como o ternário sempre foi; quem separa recusado
     * de expirado é a coluna "Situação".
     *
     * Sem badge: convite pendente já é a maioria da listagem, e um número ao lado de
     * "Pendentes" custaria uma `count()` por render para dizer quase o total.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),

            'pendentes' => Tab::make('Pendentes')
                ->icon(Heroicon::OutlinedClock)
                ->modifyQueryUsing(Convite::recorteDePendentes(...)),

            'aceitos' => Tab::make('Aceitos')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->modifyQueryUsing(Convite::recorteDeAceitos(...)),
        ];
    }
}
