<?php

namespace App\Models;

use App\Support\ProvedorSocial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A identidade de uma conta num provedor de login social: (`provedor`, `sub`).
 *
 * Nasce na primeira entrada por aquele provedor e é consultado ANTES do e-mail nas seguintes —
 * `LoginSocialController::retorno()`. Não guarda token nem credencial: é reconhecimento, não
 * acesso. Ver ADR-02 da wiki `vinculo-de-provedor-social`.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provedor
 * @property string $sub
 * @property Carbon $confirmado_em
 * @property Carbon|null $ultimo_acesso_em
 */
class VinculoSocial extends Model
{
    protected $table = 'vinculos_sociais';

    protected $fillable = [
        'user_id',
        'provedor',
        'sub',
        'confirmado_em',
        'ultimo_acesso_em',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'confirmado_em'    => 'datetime',
            'ultimo_acesso_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** O vínculo desta identidade de provedor, se alguma conta já a tem. */
    public static function de(ProvedorSocial $provedor, string $sub): ?self
    {
        return self::query()
            ->where('provedor', $provedor->value)
            ->where('sub', $sub)
            ->first();
    }

    /**
     * Grava o vínculo — idempotente pela chave única (`provedor`, `sub`).
     *
     * Quem chama já decidiu que ESTA conta é a dona da identidade; a decisão (e-mail verificado,
     * ou confirmação pelo link) mora no controller.
     */
    public static function vincular(User $user, ProvedorSocial $provedor, string $sub): self
    {
        return self::query()->firstOrCreate(
            ['provedor' => $provedor->value, 'sub' => $sub],
            ['user_id' => $user->getKey(), 'confirmado_em' => now(), 'ultimo_acesso_em' => now()],
        );
    }

    public function registrarAcesso(): void
    {
        $this->forceFill(['ultimo_acesso_em' => now()])->save();
    }
}
