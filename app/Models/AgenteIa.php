<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\TemUuid;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Catálogo de agentes de IA — o "paper" do agente como DADO, não como código.
 *
 * Este é o registro que `App\Ai\Agents\AgenteBase` lê por `slug`: system prompt,
 * provider, modelo, temperatura, allowlist de tools e guardrails são colunas,
 * editáveis pelo painel admin sem deploy. Catálogo global (o kit não tem tenancy).
 *
 * Exclusão é sempre lógica (flag `ativo`) — desligar um agente é dado, não DELETE.
 *
 * @property int $id
 * @property string $uuid
 * @property string $slug
 * @property string $nome
 * @property ?string $descricao
 * @property bool $ativo
 * @property ?string $provider
 * @property ?string $modelo
 * @property ?float $temperatura
 * @property ?int $max_tokens
 * @property list<string>|null $tools
 * @property list<string>|null $guardrails
 * @property string $instrucoes
 * @property int $versao
 */
class AgenteIa extends Model implements Auditable
{
    use AuditsFillables;
    use TemUuid;

    protected $table = 'agentes_ia';

    /** `uuid` fica fora do fillable de propósito (convenção do trait TemUuid). */
    protected $fillable = [
        'slug',
        'nome',
        'descricao',
        'ativo',
        'provider',
        'modelo',
        'temperatura',
        'max_tokens',
        'tools',
        'guardrails',
        'instrucoes',
        'versao',
    ];

    /**
     * Registro do catálogo pelo slug (chave estável usada pela classe base do agente).
     * Devolve `null` quando não cadastrado — quem decide o fail-closed é o AgenteBase.
     */
    public static function doSlug(string $slug): ?self
    {
        return static::query()->where('slug', $slug)->first();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo'       => 'boolean',
            'tools'       => 'array',
            'guardrails'  => 'array',
            // `float` (e não `decimal:2`): a temperatura vai direto para o provider como
            // número — `decimal:2` devolveria string e obrigaria cast na borda.
            'temperatura' => 'float',
            'max_tokens'  => 'integer',
            'versao'      => 'integer',
        ];
    }
}
