<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * O papel do spatie com a coluna que o kit acrescentou.
 *
 * `painel` é o que dá acesso a painel: `User::canAccessPanel()` compara esta coluna com
 * o id do painel corrente. **Nulo não é coringa** — papel sem painel não abre painel
 * algum, e quem entra em todos é o `master_global`, pelo `Gate::before`.
 *
 * Este model existe por causa da coluna. Sem ele, `$papel->painel` é atributo dinâmico:
 * sem tipo, sem autocomplete, e o PHPStan reprova todo acesso. Apontar
 * `permission.models.role` para cá basta — spatie e Shield resolvem o model pela config
 * (`Config::roleModel()`, `Utils::getRoleModel()`), então nada mais precisa saber disto.
 *
 * @property ?string $painel
 */
class Role extends SpatieRole
{
    // Sem nada além do @property acima: a coluna é `$guarded = []` no spatie, então
    // mass assignment, casts e queries já funcionam. Scope e helper entram quando
    // houver um segundo chamador.
}
