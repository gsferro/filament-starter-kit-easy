<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tenant padrão do kit (multi-tenancy opcional — veja config/kit.php).
 * Renomeie/adapte para o conceito do seu negócio (empresa, organização, filial...).
 */
class Team extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
