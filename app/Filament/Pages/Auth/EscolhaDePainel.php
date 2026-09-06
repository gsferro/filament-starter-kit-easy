<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\DestinoAposLogin;
use App\Support\Paineis;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Support\Enums\Width;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * A escolha de painel depois do login pela página única, em `/login/painel`.
 *
 * Os cartões são os da tela de boas-vindas (`Paineis::cartoes()`), filtrados por
 * `canAccessPanel()` ANTES de montar — `CardItem` não verifica autorização
 * (`.ai/rules/filament.md`), então o filtro é desta página. Panel Switch não serve aqui: ele é um
 * gatilho de topbar com dropdown/modal, não uma página (ADR-01).
 *
 * A rota exige `auth` e `panel:app` (tema e layout; ver `TelaLoginUnificada`). Quem tem um só
 * painel nunca vê esta tela; quem não tem nenhum tem a sessão encerrada e volta ao login.
 * Ver `wikis/specs/feat/login-unificado/`.
 */
class EscolhaDePainel extends CardsPage
{
    use RestrictsFileUploadsToSchemaComponents;

    /** Sem sidebar nem topbar: a pessoa ainda não está em painel nenhum. */
    protected static string $layout = 'filament-panels::components.layout.simple';

    protected static ?string $title = 'Em qual painel você quer entrar?';

    /**
     * Três, como na boas-vindas: `resources/css/filament/cards.css` cobre até `xl:grid-cols-4`;
     * acima disso a grade fica sem estilo com todo teste verde.
     *
     * @var int|string|array<string, int|string>
     */
    protected static int|string|array $columns = 3;

    protected Width|string|null $maxContentWidth = Width::SevenExtraLarge;

    /** @return array<string> */
    public function getPageClasses(): array
    {
        return ['kit-cards-page'];
    }

    public function mount(): void
    {
        $user    = Filament::auth()->user();
        $paineis = $user instanceof User ? DestinoAposLogin::paineisDe($user) : [];

        // Dois ou mais: a tela existe para isso. Um: nunca mostra a escolha. Nenhum: encerra a
        // sessão e volta ao login com aviso — o 403 do painel seria uma tela em branco.
        $destino = match (count($paineis)) {
            0       => $this->encerrarSemPainel($user),
            1       => $this->entrarDireto($user, $paineis[0]),
            default => null,
        };

        if ($destino !== null) {
            throw new HttpResponseException(new RedirectResponse($destino));
        }
    }

    /** @return array<CardItem> */
    protected static function getCards(): array
    {
        $user = Filament::auth()->user();
        $ids  = $user instanceof User
            ? array_map(fn (Panel $painel): string => $painel->getId(), DestinoAposLogin::paineisDe($user))
            : [];

        // O cartão passa pela rota que CARIMBA o painel escolhido no log de acesso antes de
        // redirecionar — o link direto para o painel deixaria o acesso sem painel.
        $cartoes = array_intersect_key(Paineis::cartoes(), array_flip($ids));

        return array_map(
            fn (CardItem $cartao, string $id): CardItem => $cartao->url(route('login.painel.entrar', ['painel' => $id])),
            array_values($cartoes),
            array_keys($cartoes),
        );
    }

    private function entrarDireto(?User $user, Panel $painel): string
    {
        Log::channel('autenticacao')->info(
            "[EscolhaDePainel@mount] Um só painel acessível — sem escolha | user: {$user?->getKey()} - painel: {$painel->getId()}",
            ['user_id' => $user?->getKey(), 'painel' => $painel->getId()],
        );

        return $painel->getUrl() ?? url($painel->getPath());
    }

    private function encerrarSemPainel(?User $user): string
    {
        Log::channel('autenticacao')->warning(
            "[EscolhaDePainel@mount] Autenticado sem nenhum painel acessível — sessão encerrada | user: {$user?->getKey()}",
            ['user_id' => $user?->getKey(), 'motivo' => 'nenhum_painel_acessivel'],
        );

        Filament::auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        Notification::make()
            ->title('Sua conta não tem acesso a nenhum painel.')
            ->body('Procure quem administra o sistema.')
            ->warning()
            ->persistent()
            ->send();

        return route('login');
    }
}
