<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * A coluna, o filtro e as duas ações do estado ativo/inativo de um usuário.
 *
 * Irmã de `AprovacaoDeCadastro`, pelo mesmo argumento: são pedaços de UI cuja regra é idêntica
 * nos dois `UserResource` por definição — "Inativo" tem de significar a mesma coisa no /admin e
 * no /app. A coluna de situação morava naquela trait e mostrava só Pendente/Ativo; com o estado
 * novo ela ganhou o terceiro valor e veio para cá, onde o estado vive.
 *
 * As **ações** só o /admin usa (ADR-04 da wiki `status-e-exclusao-logica-de-usuario`): desativar
 * tira a pessoa de todas as organizações, é ato global como excluir, e o /app não o oferece pela
 * mesma régua da exclusão.
 *
 * A trait não é a barreira. Quem nega a entrada é `User::canAccessPanel()`; quem faz a transição
 * e recusa a própria conta e o último `master_global` é `User::desativar()`. Aqui só vive a
 * apresentação — e o `->authorize()`, que não é decoração: Action do Filament nasce liberada para
 * todo mundo (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:15-22`).
 */
trait SituacaoDaConta
{
    /**
     * Pendente, Inativo ou Ativo — partição exaustiva do estado que a tela mostra.
     *
     * Excluído não aparece aqui: ele só entra na tabela pelo filtro "Lixeira", e ali a própria
     * ação Restaurar é o sinal. Pendente vence Inativo na exibição: quem ainda não foi aprovado
     * não tem estado de acesso a mostrar.
     */
    protected static function colunaDeSituacao(): TextColumn
    {
        return TextColumn::make('situacao')
            ->label('Situação')
            ->badge()
            ->state(fn (User $record): string => match (true) {
                (bool) $record->aprovacao_pendente => 'Pendente',
                ! $record->ativo                   => 'Inativo',
                default                            => 'Ativo',
            })
            ->color(fn (string $state): string => match ($state) {
                'Pendente' => 'warning',
                'Inativo'  => 'danger',
                default    => 'success',
            });
    }

    protected static function filtroDeInativos(): Filter
    {
        return Filter::make('inativos')
            ->label('Somente inativos')
            ->query(fn (Builder $query): Builder => $query->where('ativo', false));
    }

    /**
     * Desativar — visível só para quem está ativo e pode ser desativado; autorizada por permissão
     * própria (`Desativar:User`, gerada por `filament-shield.resources.manage`).
     *
     * O `->visible()` espelha `User::motivoParaNaoDesativar()`, que é a regra de verdade e vale
     * para qualquer chamador. ponytail: sem `try/catch` da exceção do model — a ação já está oculta
     * quando a guarda vale; ela só lançaria numa corrida entre duas abas, e a mensagem é legível.
     */
    protected static function acaoDeDesativar(): Action
    {
        return Action::make('desativar')
            ->label('Desativar')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->authorize('desativar')
            ->visible(fn (User $record): bool => $record->ativo
                && ! $record->trashed()
                && $record->motivoParaNaoDesativar() === null)
            ->requiresConfirmation()
            ->modalHeading('Desativar este usuário?')
            ->modalDescription('A pessoa deixa de entrar em qualquer painel até ser reativada. Nada é apagado.')
            ->successNotificationTitle('Usuário desativado')
            ->action(function (User $record): void {
                $record->desativar();
            });
    }

    protected static function acaoDeReativar(): Action
    {
        return Action::make('reativar')
            ->label('Reativar')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('reativar')
            ->visible(fn (User $record): bool => ! $record->ativo && ! $record->trashed())
            ->requiresConfirmation()
            ->modalHeading('Reativar este usuário?')
            ->modalDescription('A pessoa volta a entrar nos painéis do papel dela.')
            ->successNotificationTitle('Usuário reativado')
            ->action(function (User $record): void {
                $record->reativar();
            });
    }
}
