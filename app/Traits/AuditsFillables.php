<?php

namespace App\Traits;

use OwenIt\Auditing\Auditable;

/**
 * Auditoria (owen-it/laravel-auditing) restrita ao que é editável.
 *
 * Auditar exatamente o $fillable evita vazar para a trilha colunas técnicas
 * (tokens, contadores, caches) e mantém uma regra única: o que o usuário pode
 * alterar é o que fica registrado. A trilha aparece no painel infra (/infra/audits).
 */
trait AuditsFillables
{
    use Auditable;

    public function getAuditInclude(): array
    {
        return $this->getFillable();
    }
}
