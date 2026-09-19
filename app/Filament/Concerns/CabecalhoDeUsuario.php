<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Components\MetadataEntry;

/**
 * O cabeçalho rico das telas de View/Edit de usuário, idêntico nos dois painéis.
 *
 * Irmã de `SituacaoDaConta` e `AprovacaoDeCadastro`, pelo mesmo argumento: é pedaço de UI cuja
 * regra é a mesma no /admin e no /app por definição. O que o cabeçalho mostra sobre uma conta não
 * muda por painel — muda quem pode abrir a tela, e isso é decidido em outro lugar.
 *
 * As duas classes `Schemas\UserHeader` existem por CONVENÇÃO do pacote, não por escolha: o
 * `HasPageHeader` descobre o schema montando o nome a partir do MODEL do resource
 * (`vendor/mortalkiller/filament-page-header/src/Concerns/HasPageHeader.php:getPageHeaderSchemaClass:50`),
 * e o namespace vem do resource. Com dois `UserResource`, são dois nomes obrigatórios. Elas
 * delegam para cá em uma linha.
 */
trait CabecalhoDeUsuario
{
    /**
     * O bloco de identidade + situação + metadados da conta.
     *
     * O AVATAR é `getFilamentAvatarUrl()`, nunca o provider de avatar do painel. O provider do kit
     * (`App\Support\AvatarDeIniciais`) devolve `data:image/svg+xml;base64,…`, e
     * `Header::getAvatarUrl()` (`vendor/mortalkiller/filament-page-header/src/Components/Header.php:174-177`)
     * recusa todo esquema fora de `http`/`https` devolvendo `null` — SEM erro. O bloco de
     * identidade simplesmente sumiria, com a suíte verde. `getFilamentAvatarUrl()` devolve
     * `http://…/storage/…` quando há foto, e `null` quando não há; nesse caso o slot cai nas
     * iniciais, que é o desenho. Ver ADR-02 de
     * `wikis/specs/feat/page-header-nas-telas-de-registro/`.
     *
     * O HEADING é explícito e é só o nome. Herdado, ele viria de `getTitle()`, que em página de
     * registro é "Editar {nome}" / "Visualizar {nome}"
     * (`vendor/filament/filament/src/Resources/Pages/EditRecord.php:getTitle:307-316`) — bom para
     * a aba do navegador, redundante num cabeçalho que já tem avatar e ações do lado. O pacote não
     * mexe no título da aba, então os dois convivem.
     *
     * Sem `html: true` em lugar nenhum: o estado aqui é `$record->name`, que é entrada de usuário,
     * e `Heading::getContent()` (`…/Components/Heading.php:26-31`) troca `e($state)` por estado
     * cru quando ligado. Proibido por `tests/Kit/PageHeaderTest.php`.
     */
    protected static function cabecalhoDeUsuario(): Header
    {
        return Header::make()
            ->avatar(fn (User $record): ?string => $record->getFilamentAvatarUrl())
            ->initials(fn (User $record): string => $record->name)
            ->heading(fn (User $record): string => $record->name)
            ->badges([
                // A decisão mora em User::rotuloDaSituacao(); aqui só a apresentação.
                TextEntry::make('situacao')
                    ->badge()
                    ->state(fn (User $record): string => $record->rotuloDaSituacao())
                    ->color(fn (User $record): string => $record->corDaSituacao()),
            ])
            ->metadata([
                MetadataEntry::make('email')
                    ->label('E-mail')
                    ->fieldIcon(Heroicon::OutlinedEnvelope)
                    ->copyable(),
                MetadataEntry::make('origem')
                    ->label('Origem')
                    ->fieldIcon(Heroicon::OutlinedArrowRightEndOnRectangle)
                    ->state(fn (User $record): string => $record->rotuloDaOrigem()),
                MetadataEntry::make('created_at')
                    ->label('Cadastrado em')
                    ->fieldIcon(Heroicon::OutlinedCalendar)
                    ->dateTime('d/m/Y'),
            ]);
    }
}
