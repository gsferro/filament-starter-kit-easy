<?php

namespace App\Filament\Spotlight;

use Wezlo\FilamentSearchSpotlight\Categories\ResourcesCategory;

/**
 * Categoria "Telas" do Spotlight com o filtro que falta no pacote.
 *
 * A `ResourcesCategory` do vendor varre `$panel->getResources()` e devolve tudo:
 * ela não chama `canAccess()`. Num painel com Shield isso vaza affordance — o
 * usuário busca, vê a tela na lista, clica e toma 403.
 *
 * Aqui a busca do pacote é reaproveitada inteira (rótulo, ícone, grupo, URL) e
 * só o resultado é recortado pela autorização. Pede-se uma janela maior ao
 * vendor porque ele corta em `$limit` ANTES de qualquer filtro; sem isso, um
 * usuário restrito receberia uma lista quase vazia.
 */
class ResourcesAutorizadasCategory extends ResourcesCategory
{
    public function search(string $query, int $limit): array
    {
        $resultados = parent::search($query, ($limit * 5) + 10);

        $autorizados = array_filter($resultados, function ($resultado): bool {
            $classe = $resultado->payload['resource'] ?? null;

            return is_string($classe)
                && class_exists($classe)
                && method_exists($classe, 'canAccess')
                && $classe::canAccess();
        });

        return array_slice(array_values($autorizados), 0, $limit);
    }
}
