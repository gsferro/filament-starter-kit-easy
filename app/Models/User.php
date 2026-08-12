<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\TemUuid;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, FilamentUser, HasAvatar
{
    use AuditsFillables;
    use AuthenticationLoggable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;
    use TemUuid;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Acesso aos painéis
    |--------------------------------------------------------------------------
    | admin → administração da aplicação (usuários, papéis, agentes de IA)
    | infra → observabilidade e manutenção (health, filas, logs, auditoria)
    | app   → a operação de negócio; nasce aberto a qualquer autenticado
    |
    | O papel master_global também vence via Gate::before (KitServiceProvider),
    | mas o acesso a painel é checado aqui, antes de qualquer gate.
    */

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isMasterGlobal() || $this->hasRole('admin'),
            'infra' => $this->isMasterGlobal() || $this->hasRole('infra'),
            'app'   => true,
            default => false,
        };
    }

    /** Papel guarda-chuva do kit — o "super admin" do Shield (define_via_gate). */
    public function isMasterGlobal(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name', 'master_global'));
    }

    /*
    |--------------------------------------------------------------------------
    | Impersonate (stechstudio/filament-impersonate)
    |--------------------------------------------------------------------------
    */

    public function canImpersonate(): bool
    {
        return $this->isMasterGlobal();
    }

    public function canBeImpersonated(): bool
    {
        // Master global nunca é alvo de impersonação.
        return ! $this->isMasterGlobal();
    }

    /*
    |--------------------------------------------------------------------------
    | Avatar (Breezy faz o upload; Filament lê daqui)
    |--------------------------------------------------------------------------
    */

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url
            ? Storage::disk('public')->url($this->avatar_url)
            : null;
    }
}
