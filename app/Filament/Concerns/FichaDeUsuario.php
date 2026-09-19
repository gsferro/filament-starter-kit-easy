<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

/**
 * A ficha somente-leitura de uma conta, idêntica nos dois painéis.
 *
 * Irmã de `CabecalhoDeUsuario`, `SituacaoDaConta` e `AprovacaoDeCadastro`, pelo mesmo argumento:
 * o que uma conta É não muda por painel. O que muda é QUEM abre a tela, e isso é decidido pelo
 * recorte de `getEloquentQuery()` e pela policy — não pelo que a ficha mostra.
 *
 * ## O que esta seção NÃO contém, de propósito
 *
 * **As organizações da pessoa.** Elas entram só na ficha do /admin
 * (`App\Filament\Admin\Resources\Users\Schemas\UserInfolist`). No /app, listar as organizações de
 * um usuário contaria a quem administra a Acme que aquela pessoa também é da Globex — o recorte de
 * `UserResource::getEloquentQuery()` (`app/Filament/App/Resources/Users/UserResource.php:197`)
 * garante que só se veja gente DA organização corrente, e não que se possa ver onde mais ela está.
 * Fronteira de tenancy vazada por campo de exibição é a variedade que nenhum teste de rota pega.
 *
 * **A senha, o token e o `remember_token`.** Não estão no `$hidden` por acaso
 * (`app/Models/User.php:$hidden:96-99`), e ficha de leitura não é o lugar de reabri-los.
 */
trait FichaDeUsuario
{
    /**
     * Identidade e estado da conta — o que vale igual nos dois painéis.
     *
     * A situação sai de `User::rotuloDaSituacao()`, a mesma decisão que a coluna da listagem e o
     * cabeçalho usam. Três consumidores, um `match`.
     */
    protected static function secaoDaConta(): Section
    {
        return Section::make('Conta')
            ->columns(2)
            ->schema([
                TextEntry::make('name')->label('Nome'),
                TextEntry::make('email')->label('E-mail')->copyable(),
                TextEntry::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (User $record): string => $record->rotuloDaSituacao())
                    ->color(fn (User $record): string => $record->corDaSituacao()),
                TextEntry::make('origem')
                    ->label('Origem')
                    ->state(fn (User $record): string => $record->rotuloDaOrigem()),
                TextEntry::make('email_verified_at')
                    ->label('E-mail confirmado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Não confirmado'),
                TextEntry::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y H:i'),
                TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i'),
                TextEntry::make('deleted_at')
                    ->label('Excluído em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->color('danger')
                    // Só aparece em conta excluída logicamente; em conta viva a linha seria ruído.
                    ->visible(fn (User $record): bool => $record->trashed()),
            ]);
    }
}
