<?php

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use App\Models\Tenant;
use App\Support\CorPrimaria;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Components\MetadataEntry;

/**
 * O cabeçalho rico das telas de View/Edit de organização.
 *
 * O NOME e o LUGAR desta classe não são escolha: o pacote a descobre por convenção sobre o MODEL
 * do resource — `{namespace do resource}\Schemas\{class_basename do model}Header` — em
 * `vendor/mortalkiller/filament-page-header/src/Concerns/HasPageHeader.php:getPageHeaderSchemaClass:65-66`.
 * `configure()` estática é exigida pela mesma função (`:68`, `is_callable([$class, 'configure'])`).
 *
 * Sem par em outro painel: `Tenant` só tem resource no /admin.
 */
class TenantHeader
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Header::make()
                /*
                 * `urlDaLogo()` já confere `Storage::disk('public')->exists()` antes de devolver
                 * (`app/Models/Tenant.php:urlDaLogo:138`), então não chega `<img>` quebrado aqui.
                 * Devolve `null` sem logo, e o slot cai nas iniciais.
                 *
                 * É `asset()`, não `Storage::url()` — logo URL absoluta com esquema http/https, que
                 * é o que `Header::getAvatarUrl()` (`…/Components/Header.php:174-177`) aceita. Ver
                 * ADR-02: avatar em `data:` URI seria descartado sem erro nenhum.
                 */
                ->avatar(fn (Tenant $record): ?string => $record->urlDaLogo())
                ->initials(fn (Tenant $record): string => $record->nome)
                ->initialsBgColor(fn (Tenant $record): ?array => self::paleta($record))
                /*
                 * Explícito, e só o nome. Herdado viria "Editar {nome}" de `getTitle()`
                 * (`vendor/filament/filament/src/Resources/Pages/EditRecord.php:getTitle:307-316`)
                 * — bom para a aba do navegador, que o pacote não toca, redundante no cabeçalho.
                 */
                ->heading(fn (Tenant $record): string => $record->nome)
                ->badges([
                    TextEntry::make('ativo')
                        ->badge()
                        ->state(fn (Tenant $record): string => $record->ativo ? 'Ativa' : 'Inativa')
                        ->color(fn (Tenant $record): string => $record->ativo ? 'success' : 'danger'),
                    TextEntry::make('registro_habilitado')
                        ->badge()
                        ->state(fn (Tenant $record): string => $record->registro_habilitado
                            ? 'Registro aberto'
                            : 'Registro fechado')
                        ->color(fn (Tenant $record): string => $record->registro_habilitado ? 'info' : 'gray'),
                ])
                ->metadata([
                    MetadataEntry::make('slug')
                        ->label('Identificador')
                        ->fieldIcon(Heroicon::OutlinedLink)
                        ->copyable(),
                    MetadataEntry::make('usuarios')
                        ->label('Usuários')
                        ->fieldIcon(Heroicon::OutlinedUserGroup)
                        ->state(fn (Tenant $record): int => $record->users()->count()),
                    MetadataEntry::make('created_at')
                        ->label('Criada em')
                        ->fieldIcon(Heroicon::OutlinedCalendar)
                        ->dateTime('d/m/Y'),
                ]),
        ]);
    }

    /**
     * A paleta da organização, para tingir as iniciais quando não há logo.
     *
     * Sai de `CorPrimaria::resolver()`, que é o dono da precedência no kit — o hexadecimal livre
     * vence o nome da paleta — e é a MESMA função que o `/app` usa no `bootUsing()`
     * (`app/Providers/Filament/AppPanelProvider.php:185`). Reescrever a precedência aqui faria o
     * cabeçalho do /admin discordar do painel que ele descreve.
     *
     * Ela devolve `['primary' => <paleta>]` para nome conhecido e `['primary' => '#rrggbb']` para
     * hex. O `Header::getIdentityStyles()` (`…/Components/Header.php:215-235`) precisa de ARRAY
     * indexado por tom — ele lê `[600] ?? [500]` —, então o hex passa por `generatePalette()`.
     * Passar o hex cru devolveria `null` de `FilamentColor::getColor()` e o tom sumiria em
     * silêncio.
     *
     * @return array<int, string>|null null = sem cor própria, o pacote usa o cinza dele
     */
    private static function paleta(Tenant $record): ?array
    {
        $primaria = CorPrimaria::resolver($record->cor_primaria, $record->cor_primaria_nome)['primary'] ?? null;

        if (is_array($primaria)) {
            return $primaria;
        }

        return is_string($primaria) ? Color::generatePalette($primaria) : null;
    }
}
