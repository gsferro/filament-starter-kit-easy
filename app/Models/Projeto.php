<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\BelongsToTenant;
use App\Traits\TemUuid;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * DEMONSTRAÇÃO — criada por `php artisan kit:tenancy --demo`.
 *
 * Existe para PROVAR o isolamento entre tenants numa tela de verdade, não para
 * ser feature do kit. É descartável: apague este arquivo, o resource em
 * `app/Filament/App/Resources/Projetos/`, a migration `*_create_projetos_table`
 * e o `DemoTenancySeeder`.
 *
 * Serve também de exemplo canônico do que uma model de negócio precisa em modo
 * multi-tenant:
 *
 *   - `BelongsToTenant`  → relação, escopo global e preenchimento de tenant_id
 *   - `TemUuid`          → uuid na rota
 *   - `AuditsFillables`  → trilha do que é editável
 *   - `tenant_id` FORA do $fillable — quem preenche é a trait
 */
class Projeto extends Model implements Auditable
{
    use AuditsFillables;
    use BelongsToTenant;
    use TemUuid;

    protected $fillable = [
        'nome',
    ];
}
