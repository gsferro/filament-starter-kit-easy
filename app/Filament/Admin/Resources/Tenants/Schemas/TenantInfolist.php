<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use App\Models\Tenant;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * A ficha somente-leitura de uma organização.
 *
 * ## Por que ela existe
 *
 * Sem `infolist()`, o `ViewRecord` cai no formulário DESABILITADO
 * (`vendor/filament/filament/src/Resources/Pages/ViewRecord.php:defaultInfolist:182`, que só é
 * consultado quando há componentes — sem eles a página monta o form). O Filament Blueprint chama
 * isso de *"looks poor"*, e aqui ficaria pior que o normal: a `ViewTenant` acabou de ganhar um
 * cabeçalho rico, e o contraste seria topo elaborado sobre campos cinzentos inertes.
 *
 * A `ViewTenant` é a tela que a receita de `wikis/receitas.md` usa como exemplo executável do
 * padrão "resumo do registro no topo + abas para os relacionamentos". Exemplo com o corpo meio
 * reformado ensina o meio errado.
 *
 * ## `columns(1)` no pai
 *
 * `defaultInfolist()` aplicaria `columns(2)`, e como cada `Section` daqui divide o interior em 2,
 * a entrada acabaria com 25% da largura. É o item "Nested Columns Too Narrow" do `checklist.md`
 * do Blueprint.
 */
class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identificação')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nome')->label('Nome'),
                        TextEntry::make('slug')->label('Identificador')->copyable(),
                        /*
                         * O atalho para o painel da organização — RQ-01/RQ-03 da wiki
                         * `link-painel-do-tenant`. Aqui, ao lado do `slug` que é `copyable()`:
                         * o slug responde "qual é o identificador", este responde "onde ele
                         * leva". Nova aba (ADR-04).
                         */
                        TextEntry::make('url_do_painel')
                            ->label('Painel da organização')
                            ->state(fn (Tenant $record): ?string => $record->urlDoPainel())
                            ->url(fn (Tenant $record): ?string => $record->urlDoPainel())
                            ->openUrlInNewTab(),
                        TextEntry::make('ativo')
                            ->label('Situação')
                            ->badge()
                            ->state(fn (Tenant $record): string => $record->ativo ? 'Ativa' : 'Inativa')
                            ->color(fn (Tenant $record): string => $record->ativo ? 'success' : 'danger'),
                        TextEntry::make('registro_habilitado')
                            ->label('Registro aberto')
                            ->badge()
                            ->state(fn (Tenant $record): string => $record->registro_habilitado ? 'Sim' : 'Não')
                            ->color(fn (Tenant $record): string => $record->registro_habilitado ? 'info' : 'gray'),
                        TextEntry::make('created_at')->label('Criada em')->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')->label('Atualizada em')->dateTime('d/m/Y H:i'),
                    ]),
                Section::make('Identidade visual')
                    ->columns(2)
                    ->schema([
                        /*
                         * `urlDaLogo()` e não a coluna crua: ele confere
                         * `Storage::disk('public')->exists()` antes (`app/Models/Tenant.php:urlDaLogo:164`),
                         * então path órfão degrada para o placeholder em vez de renderizar imagem
                         * quebrada — que é o oposto do que a tela promete.
                         */
                        ImageEntry::make('logo')
                            ->label('Logo')
                            ->state(fn (Tenant $record): ?string => $record->urlDaLogo())
                            ->placeholder('Sem logo'),
                        TextEntry::make('cor_primaria_nome')
                            ->label('Cor da paleta')
                            ->placeholder('Cor da aplicação (padrão)'),
                        TextEntry::make('cor_primaria')
                            ->label('Cor livre')
                            ->placeholder('—')
                            ->helperText('Quando preenchida, vence o nome da paleta.'),
                    ]),
            ]);
    }
}
