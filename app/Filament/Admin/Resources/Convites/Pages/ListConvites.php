<?php

namespace App\Filament\Admin\Resources\Convites\Pages;

use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Concerns\ConvidaEmMassa;
use App\Models\Convite;
use App\Support\AdministradorDaInstalacao;
use App\Support\Papeis;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ListConvites extends ListRecords
{
    use ConvidaEmMassa;

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
                // O mesmo Select do ConviteForm, sem o `->live()`: aqui nenhum campo depende
                // do painel do papel escolhido.
                Select::make('role_id')
                    ->label('Papel')
                    ->relationship('papel', 'name', fn (Builder $query): Builder => AdministradorDaInstalacao::recortarConcessao($query))
                    ->required()
                    ->preload()
                    ->searchable()
                    // Teto de escalada (F-01) na escrita, como no ConviteForm.
                    ->rule(fn (): object => AdministradorDaInstalacao::regraDeConcessao())
                    // O parâmetro TEM de se chamar `$record`: o Filament injeta closure de
                    // opção por NOME, não por tipo.
                    ->getOptionLabelFromRecordUsing(function (Model $record): string {
                        $painel = $record->getAttribute('painel');

                        return Papeis::rotulo((string) $record->getAttribute('name')).' — '.(is_string($painel) ? "/{$painel}" : 'sem painel');
                    })
                    ->helperText('Todos os endereços do lote nascem com este papel.'),
                escolheOrganizacao: true,
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
