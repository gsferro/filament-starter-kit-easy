<?php

namespace App\Filament\Admin\Resources\Convites\Tables;

use App\Models\Convite;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Listagem dos convites. Sem `EditAction` de propósito — ver o PHPDoc do
 * `ConviteResource`: o convite já foi enviado, então ele se revoga ou se reenvia, nunca
 * se edita.
 */
class ConvitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),

                TextColumn::make('papel.name')->label('Papel')->badge(),

                TextColumn::make('tenant.nome')
                    ->label(config('kit.tenancy.label', 'Organização'))
                    ->visible(fn (): bool => (bool) config('kit.tenancy.enabled')),

                // Derivada de `aceito_em` + `expira_em`, sem coluna no banco: um terceiro
                // estado a manter em sincronia com dois fatos que já estão lá.
                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->color(fn (Convite $record): string => match (self::situacao($record)) {
                        'Aceito'   => 'success',
                        'Expirado' => 'danger',
                        default    => 'warning',
                    })
                    ->state(fn (Convite $record): string => self::situacao($record)),

                TextColumn::make('expira_em')->label('Expira em')->dateTime('d/m/Y H:i')->sortable(),

                TextColumn::make('convidadoPor.name')
                    ->label('Convidado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('pendente')
                    ->label('Pendente')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('aceito_em'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('aceito_em'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                // Três linhas úteis porque o model já faz o trabalho. O retorno (o token
                // em claro) é ignorado de propósito: ele morre aqui.
                Action::make('reenviar')
                    ->label('Reenviar')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->requiresConfirmation()
                    ->modalDescription('O link anterior deixa de funcionar e um novo é enviado.')
                    ->visible(fn (Convite $record): bool => $record->aceito_em === null)
                    ->action(fn (Convite $record) => $record->enviar())
                    ->successNotificationTitle('Convite reenviado'),

                // Revogar é o DeleteAction nativo relabelado: a linha some e o link para
                // de valer no mesmo instante, porque `Convite::valido()` não acha mais
                // nada. A trilha (quem, quando, para qual e-mail) fica na auditoria, sem
                // o hash — `token` está fora do $fillable.
                DeleteAction::make()
                    ->label('Revogar')
                    ->modalHeading('Revogar convite')
                    ->modalDescription('O link para de funcionar imediatamente. A revogação fica na auditoria.')
                    ->after(fn (Convite $record) => Log::channel('autenticacao')->warning(
                        "[ConvitesTable@revogar] Convite revogado | convite: {$record->id}",
                        [
                            'convite_id'   => $record->id,
                            'email'        => Str::mask($record->email, '*', 3),
                            'role_id'      => $record->role_id,
                            'tenant_id'    => $record->tenant_id,
                            'revogado_por' => auth()->id(),
                        ],
                    )),
            ])
            ->emptyStateHeading('Nenhum convite enviado')
            ->emptyStateDescription('Convide alguém para que ela crie a própria senha e nasça com o papel certo.');
    }

    private static function situacao(Convite $convite): string
    {
        return match (true) {
            $convite->aceito_em !== null                  => 'Aceito',
            $convite->expira_em?->isPast() ?? true        => 'Expirado',
            default                                       => 'Pendente',
        };
    }
}
