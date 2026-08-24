<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
use App\Models\Convite;
use App\Models\User;
use App\Support\Papeis;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * As ofertas de acesso endereçadas a quem está logado — a caixa de entrada de convites.
 *
 * É a metade do problema que o link não resolve bem: aceitar sem precisar achar o e-mail,
 * e **recusar**, que o link nem tem como oferecer (link tem um destino só).
 *
 * ## Ela não alcança todo mundo, e isso é conhecido
 *
 * A página vive no painel `app`; com a tenancy ligada, sob `/app/{tenant}`. Logo não
 * alcança quem tem zero organizações nem quem só tem papel de `/admin` ou `/infra` — para
 * esses, o **link** é a via, e é por isso que o convite continua carregando token nas duas
 * vias. O `jeffersongoncalves/filament-teams` escapa disso tornando times pessoais
 * obrigatórios; não inventamos organização fantasma para destravar uma tela. Ver ADR-02 e
 * ADR-05 em `wikis/specs/main/convite-para-usuario-existente/`.
 *
 * ## A autorização NÃO está aqui
 *
 * A query filtra por e-mail, mas isso é **filtro de UI**. Quem garante que o convite é
 * desta pessoa é `Convite::aceitarComoUsuarioExistente()`/`recusar()`, que reconferem no
 * model. Foi confiar só no filtro da tela que abriu o furo do teamkit, onde
 * `TeamInvitation::accept()` anexa qualquer usuário. Ver ADR-03.
 */
class ConvitesRecebidos extends Page implements HasTable
{
    use ExigePermissaoDaTela;
    use InteractsWithTable;

    protected string $view = 'filament.pages.convites-recebidos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    /** Fora da navegação: o caminho é o item no menu do usuário, com a contagem. */
    protected static bool $shouldRegisterNavigation = false;

    /**
     * Hook de `ExigePermissaoDaTela`: tenancy e sessão, ALÉM da permissão `View:ConvitesRecebidos`.
     *
     * A permissão nasce com o `panel_user` e com o `admin_app` — é a caixa de entrada de quem opera
     * o negócio, não tela de administração, então ela NÃO entra em
     * `PapeisSeeder::permissoesDeAdministracaoDoApp()`. Numa subtração o erro é espelhado:
     * acrescentá-la "por precaução" deixaria o usuário comum sem como aceitar o convite dele.
     */
    protected static function regraLocalDeAcesso(): bool
    {
        return (bool) config('kit.tenancy.enabled') && Auth::check();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Convites recebidos';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Convite::pendentesPara($this->usuario()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tenant.nome')
                    ->label(config('kit.tenancy.label', 'Organização'))
                    ->placeholder('—'),
                TextColumn::make('papel.name')->label('Papel')->badge()
                    ->formatStateUsing(fn (?string $state): string => Papeis::rotulo($state)),
                TextColumn::make('convidadoPor.name')->label('Convidado por')->placeholder('—'),
                TextColumn::make('expira_em')->label('Expira em')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                Action::make('aceitar')
                    ->label('Aceitar')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    /*
                     * Permissão de configuração, não barreira de identidade. Quem garante que o
                     * convite é DESTA pessoa continua sendo `Convite::exigirDono()`, chamado na
                     * primeira linha de `aceitarComoUsuarioExistente()` — a permissão não substitui
                     * a asserção do model, e a ordem importa: ter `Aceitar:Convite` não deixa
                     * ninguém aceitar convite de outro. Ver `.ai/rules/filament.md` §2.
                     *
                     * `Aceitar:Convite` nasce em `config('filament-shield.custom_permissions')` e
                     * chega ao `panel_user` e ao `admin_app` pelo mapa de painéis do `PapeisSeeder`.
                     */
                    ->authorize('Aceitar:Convite')
                    // Entrar numa organização é ato explícito — a confirmação é a razão de
                    // o aceite pós-login não ser automático.
                    ->requiresConfirmation()
                    ->modalHeading('Aceitar convite')
                    // `Papeis::rotulo()` aqui também, e não só na coluna acima: a chave crua
                    // (`panel_user`) aparecia na confirmação do aceite — na MESMA tela em que a
                    // coluna já mostrava "Painel App". Escapou da varredura original porque o
                    // acesso é `$record->papel?->getAttribute('name')`, que nenhum grep por
                    // `papel.name` alcança.
                    ->modalDescription(fn (Convite $record): string => 'Você passa a fazer parte de '
                        .($record->tenant->nome ?? config('app.name')).' com o papel '
                        .Papeis::rotulo((string) $record->papel?->getAttribute('name')).'.')
                    ->action(function (Convite $record): void {
                        $record->aceitarComoUsuarioExistente($this->usuario());
                    })
                    ->successNotificationTitle('Convite aceito'),

                Action::make('recusar')
                    ->label('Recusar')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    // Permissão própria, e não a de aceitar: são verbos irmãos, e verbo irmão não
                    // herda autorização. A barreira de identidade é `Convite::exigirDono()` dentro
                    // de `recusar()`.
                    ->authorize('Recusar:Convite')
                    ->requiresConfirmation()
                    ->modalHeading('Recusar convite')
                    ->modalDescription('A recusa fica registrada e este convite deixa de valer. Quem convidou pode enviar outro.')
                    ->action(function (Convite $record): void {
                        $record->recusar($this->usuario());
                    })
                    ->successNotificationTitle('Convite recusado'),
            ])
            ->emptyStateHeading('Nenhum convite pendente')
            ->emptyStateDescription('Quando alguém convidar você para uma '
                .mb_strtolower((string) config('kit.tenancy.label', 'Organização')).', o convite aparece aqui.');
    }

    /**
     * O item no menu do usuário, com a contagem das ofertas pendentes.
     *
     * Mesmo padrão de `TelaBloqueio::itemDeMenu()`. A contagem sai de
     * `Convite::pendentesPara()` — a MESMA query da tabela acima, porque duas cópias
     * divergem e a que divergisse seria o contador dizendo "1" numa tela vazia.
     */
    public static function itemDeMenu(): Action
    {
        return Action::make('convitesRecebidos')
            ->label('Convites recebidos')
            ->icon(Heroicon::OutlinedEnvelopeOpen)
            ->url(fn (): string => static::getUrl())
            // `?: null` porque badge zero é ruído — e `visible()` esconde o item inteiro
            // quando não há nada a decidir.
            ->badge(fn (): ?int => static::pendentes() ?: null)
            ->badgeColor('warning')
            ->visible(fn (): bool => static::canAccess() && static::pendentes() > 0)
            ->sort(-1);
    }

    /** Chamada por `static::` nos closures de `itemDeMenu()`, logo não pode ser privada. */
    protected static function pendentes(): int
    {
        return Convite::pendentesPara(Filament::auth()->user() instanceof User
            ? Filament::auth()->user()
            : null)->count();
    }

    private function usuario(): User
    {
        $user = Auth::user();

        // Página atrás do Authenticate do painel: sem usuário não se chega aqui. O
        // instanceof é o que permite tipar o retorno sem mentir.
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
