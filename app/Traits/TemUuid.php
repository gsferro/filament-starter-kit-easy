<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * UUID nas rotas, PK numérica no banco.
 *
 * O model continua com `id` int como chave primária (joins/índices baratos),
 * mas expõe `uuid` como route key: id numérico na URL devolve 404 nativo e
 * ninguém enumera registros por sequência.
 *
 * Checklist ao criar tabela nova (cobrado por convenção do kit):
 *   1. migration com $table->uuid('uuid')->unique() (tipo nativo, NOT NULL);
 *   2. model usa este trait;
 *   3. `uuid` fica FORA do $fillable.
 *
 * UUID não é autorização: policies continuam obrigatórias.
 */
trait TemUuid
{
    use HasUuids;

    /**
     * Gera uuid apenas para a coluna `uuid`, preservando o auto-increment de `id`.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
