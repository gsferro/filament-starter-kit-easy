<?php

namespace App\Filament\Admin\Resources\Convites\Tables;

use App\Models\Convite;
use App\Support\Papeis;
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

                TextColumn::make('papel.name')->label('Papel')->badge()
                    ->formatStateUsing(fn (?string $state): string => Papeis::rotulo($state)),

                TextColumn::make('tenant.nome')
                    ->label(config('kit.tenancy.label', 'Organização'))
                    ->visible(fn (): bool => (bool) config('kit.tenancy.enabled')),

                // Derivada, sem coluna de status no banco. Quem deriva é o MODEL: esta
                // tela e a do /app mostram o mesmo estado, e derivá-lo em dois lugares foi
                // como elas divergiram (a do /app mostrava `aceito_em` com placeholder
                // "Pendente", que mentiria para um convite recusado).
                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->color(fn (Convite $record): string => match ($record->situacao()) {
                        'Aceito'   => 'success',
                        'Recusado' => 'gray',
                        'Expirado' => 'danger',
                        default    => 'warning',
                    })
                    ->state(fn (Convite $record): string => $record->situacao()),

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
                    /*
                     * `->visible()` abaixo é regra de ESTADO (só pendente ou expirado); esta é a
                     * autorização, e as duas são necessárias. Sem ela a Action ficava liberada para
                     * todo mundo que abrisse a listagem: o default de
                     * `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:21` é `null`, que o
                     * `resolveIsAuthorized()` (`:106-107`) converte em permitido.
                     *
                     * Reenviar não é "editar convite": dispara e-mail e INVALIDA o token anterior.
                     * A permissão nasce em `config('filament-shield.resources.manage')`.
                     */
                    ->authorize('Reenviar:Convite')
                    ->requiresConfirmation()
                    ->modalDescription('O link anterior deixa de funcionar e um novo é enviado.')
                    ->visible(fn (Convite $record): bool => $record->situacao() === 'Pendente' || $record->situacao() === 'Expirado')
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
}
