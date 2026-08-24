<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Concerns\ExigePermissaoDoWidget;
use App\Models\User;
use Filament\Actions\Action;
use LaBoiteACode\FilamentDashboardWidgets\Data\RecentItem;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\RecentItemsWidget;

/**
 * Quem entrou por último no cadastro.
 *
 * RecentItems e não DetailList: cada linha é um REGISTRO navegável (avatar,
 * nome, e-mail, quando entrou, link para editar), não um par rótulo/valor. O
 * DetailList serve para ficha técnica; aqui a linha precisa levar a algum lugar.
 */
class UltimosUsuariosCadastrados extends RecentItemsWidget
{
    use ExigePermissaoDoWidget;

    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return 'Últimos usuários cadastrados';
    }

    public function getEmptyStateHeading(): string
    {
        return 'Nenhum usuário cadastrado';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-user-group';
    }

    /**
     * @return array<int, RecentItem>
     */
    protected function getItems(): array
    {
        return User::query()
            ->with('roles')
            ->latest('created_at')
            // O limite do pacote (getLimit) corta depois; cortar já no SQL
            // evita trazer a tabela inteira só para descartar quase tudo.
            ->limit(5)
            ->get()
            ->map(fn (User $usuario): RecentItem => RecentItem::make(
                (string) $usuario->name,
                (string) $usuario->email,
            )
                ->avatar($usuario->getFilamentAvatarUrl())
                ->meta($usuario->created_at?->diffForHumans())
                // Papéis viram badge: é a informação que decide se o cadastro
                // ficou pela metade (usuário criado, acesso não atribuído).
                ->badge($this->rotuloDosPapeis($usuario))
                ->badgeColor($usuario->roles->isEmpty() ? 'warning' : 'primary')
                ->url($this->urlDeEdicao($usuario)))
            ->all();
    }

    /**
     * Só mostra o "ver todos" para quem realmente pode listar — link que leva a
     * um 403 é pior do que link nenhum.
     */
    protected function getViewAllUrl(): ?string
    {
        return UserResource::canViewAny() ? UserResource::getUrl() : null;
    }

    /**
     * O rótulo padrão do pacote ("View all") só tem tradução en/fr.
     */
    public function viewAllAction(): Action
    {
        return parent::viewAllAction()->label('Ver todos');
    }

    private function rotuloDosPapeis(User $usuario): string
    {
        $papeis = $usuario->roles
            ->pluck('name')
            ->map(fn (mixed $nome): string => (string) $nome)
            ->all();

        return $papeis === [] ? 'sem papel' : implode(', ', $papeis);
    }

    private function urlDeEdicao(User $usuario): ?string
    {
        return UserResource::canEdit($usuario)
            ? UserResource::getUrl('edit', ['record' => $usuario])
            : null;
    }
}
