<?php

namespace App\Filament\Spotlight;

use Wezlo\FilamentSearchSpotlight\Categories\PagesCategory;

/**
 * Categoria "Páginas" do Spotlight com checagem de autorização.
 *
 * Mesmo motivo da [ResourcesAutorizadasCategory]: a categoria do vendor lista
 * as páginas do painel sem consultar `canAccess()`. Páginas de infra (Comandos,
 * Logs, Pulse) têm gate próprio e não podem aparecer na busca de quem não pode
 * abri-las.
 */
class PagesAutorizadasCategory extends PagesCategory
{
    public function search(string $query, int $limit): array
    {
        $resultados = parent::search($query, ($limit * 5) + 10);

        $autorizadas = array_filter($resultados, function ($resultado): bool {
            $classe = $resultado->payload['page'] ?? null;

            return is_string($classe)
                && class_exists($classe)
                && method_exists($classe, 'canAccess')
                && $classe::canAccess();
        });

        return array_slice(array_values($autorizadas), 0, $limit);
    }
}
