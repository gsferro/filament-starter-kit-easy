<?php

namespace App\Filament\Admin\Resources\Convites;

use App\Filament\Admin\Resources\Convites\Pages\CreateConvite;
use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\Admin\Resources\Convites\Schemas\ConviteForm;
use App\Filament\Admin\Resources\Convites\Tables\ConvitesTable;
use App\Filament\Concerns\BadgeContagemNavegacao;
use App\Models\Convite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Convites de acesso — a porta de entrada de quem ainda não é usuário.
 *
 * ## Não existe página de edição, e isso é decisão de domínio
 *
 * `getPages()` devolve só `index` e `create`. O convite já foi ENVIADO: existe um e-mail
 * na caixa de entrada de alguém, com um link funcionando. Editar o papel de um convite
 * pendente faria a pessoa que clicar receber um papel diferente do que o e-mail dela
 * anunciou, sem forma nenhuma de detectar a divergência; editar o e-mail é pior, porque o
 * link continua válido no endereço antigo.
 *
 * As duas operações sobre um convite pendente estão na listagem:
 *
 *   - **Reenviar** — gera token novo, renova o prazo e MATA o link anterior;
 *   - **Revogar** — apaga a linha; o link para de funcionar no mesmo instante. A trilha
 *     fica na auditoria (/infra/audits), sem o hash do token.
 *
 * Errou o papel? Revogue e crie outro. Ver ADR-04 em
 * `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md`.
 */
class ConviteResource extends Resource
{
    use BadgeContagemNavegacao;

    protected static ?string $model = Convite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?string $modelLabel = 'Convite';

    protected static ?string $pluralModelLabel = 'Convites';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return ConviteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConvitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListConvites::route('/'),
            'create' => CreateConvite::route('/create'),
        ];
    }
}
