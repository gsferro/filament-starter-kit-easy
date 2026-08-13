<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\TemUuid;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, FilamentUser, HasAvatar, HasTenants
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
            // Papel GLOBAL, não por tenant: /admin e /infra governam a
            // instalação inteira. Ser `admin` dentro de um tenant não é
            // credencial para administrar o sistema.
            'admin' => $this->temPapelGlobal('admin') || $this->isMasterGlobal(),
            'infra' => $this->temPapelGlobal('infra') || $this->isMasterGlobal(),
            'app'   => true,
            default => false,
        };
    }

    /** Papel guarda-chuva do kit — o "super admin" do Shield (define_via_gate). */
    public function isMasterGlobal(): bool
    {
        return $this->temPapelGlobal(config('filament-shield.super_admin.name', 'master_global'));
    }

    /**
     * Checa um papel no contexto GLOBAL, ignorando o tenant corrente.
     *
     * Sem tenancy, é `hasRole()` puro. Com tenancy, o spatie filtra a relação
     * `roles` pelo team corrente (`wherePivot(team_id, getPermissionsTeamId())`):
     * dentro do /app o team é o tenant, e um papel global — atribuído em
     * `Tenant::CONTEXTO_GLOBAL` — sumiria justamente onde mais importa. Sem esta
     * troca temporária de contexto, o master_global perderia os poderes ao
     * entrar num tenant.
     *
     * A relação é descarregada nas duas pontas porque o Eloquent cacheia
     * `roles` na instância — reaproveitar o cache traria o resultado do outro
     * contexto.
     */
    public function temPapelGlobal(string $papel): bool
    {
        if (! config('kit.tenancy.enabled')) {
            return $this->hasRole($papel);
        }

        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);
            $this->unsetRelation('roles');

            return $this->hasRole($papel);
        } finally {
            $registrar->setPermissionsTeamId($anterior);
            $this->unsetRelation('roles');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy (Filament\Models\Contracts\HasTenants)
    |--------------------------------------------------------------------------
    | Só entra em jogo com `config('kit.tenancy.enabled')` ligado — sem tenancy
    | o Filament nunca chama estes métodos. Ver `php artisan kit:tenancy`.
    |
    | O vocabulário aqui é o da API do Filament; o rótulo que o usuário lê sai
    | de `config('kit.tenancy.label')` (default: "Organização").
    */

    /** @return BelongsToMany<Tenant, $this> */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class);
    }

    /**
     * Tenants oferecidos no seletor do painel.
     *
     * Só os ativos: desligar um tenant o tira da vista de todo mundo sem
     * precisar mexer nos vínculos.
     *
     * O master_global recebe TODOS — inclusive os que não estão no pivot. Sem
     * isso ele passaria pelo `canAccessTenant()` mas o seletor viria vazio, e
     * um painel sem tenant para escolher não abre.
     *
     * @return Collection<int, Tenant>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isMasterGlobal()) {
            return Tenant::query()->where('ativo', true)->get();
        }

        return $this->tenants()->where('ativo', true)->get();
    }

    /**
     * A fronteira real do tenant: é o que impede alguém de trocar o slug na URL
     * e cair nos dados de outro tenant. O Filament chama este método a cada
     * request, depois de identificar o tenant da rota.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isMasterGlobal()) {
            return true;
        }

        if ($this->tenants()->whereKey($tenant->getKey())->exists()) {
            return true;
        }

        Log::channel('tenancy')->warning(
            "[User@canAccessTenant] Acesso a tenant negado | user: {$this->id} - tenant: {$tenant->getKey()}",
            [
                'user_id'   => $this->id,
                'tenant_id' => $tenant->getKey(),
                'motivo'    => 'sem_vinculo',
            ],
        );

        return false;
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
