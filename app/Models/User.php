<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\ModeloCacheavel;
use App\Traits\TemUuid;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Support\Config;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable, FilamentUser, HasAvatar, HasTenants
{
    use AuditsFillables;
    use AuthenticationLoggable;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use ModeloCacheavel;
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
    | Quem decide é o PAPEL, pela coluna `roles.painel` — não uma lista de nomes
    | escrita aqui. Criar um papel e escolher o painel dele é o ato de "dar acesso
    | a um painel"; usuário sem papel não entra em lugar nenhum.
    |
    | O master_global vence antes de tudo, pelo mesmo motivo do Gate::before
    | (KitServiceProvider): ele é o guarda-chuva da instalação e não tem painel
    | declarado — `roles.painel` nulo NÃO é coringa.
    */

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isMasterGlobal()) {
            return true;
        }

        /*
         * Painel COM tenancy (/app): basta ter o papel em ALGUMA organização — qual
         * organização é decidido depois, por canAccessTenant(). Painel SEM tenancy
         * (/admin, /infra) governa a instalação inteira, então o papel tem de estar
         * atribuído no contexto global: ser `admin` dentro de uma organização não é
         * credencial para administrar o sistema.
         */
        $contexto = $panel->hasTenancy() ? null : $this->contextoGlobal();

        if ($this->temPapelDoPainel($panel->getId(), $contexto)) {
            return true;
        }

        Log::channel('autenticacao')->warning(
            "[User@canAccessPanel] Acesso a painel negado | user: {$this->id} - painel: {$panel->getId()}",
            [
                'user_id' => $this->id,
                'painel'  => $panel->getId(),
                'motivo'  => 'sem_papel_do_painel',
            ],
        );

        return false;
    }

    /**
     * Tem papel que dá acesso a este painel?
     *
     * @param  int|null  $contexto  team_id exigido na atribuição; null aceita qualquer.
     */
    public function temPapelDoPainel(string $painel, ?int $contexto = null): bool
    {
        return $this->temPapelOnde('painel', $painel, $contexto);
    }

    /**
     * O papel que este usuário EXIBE no painel — o que abriu a porta dele.
     *
     * É exibição, não autorização: quem decide entrada é `canAccessPanel()`, e nada
     * aqui deve ser usado como guarda. O que este método faz é responder, para o
     * cabeçalho do menu do usuário, a pergunta "com que papel eu estou aqui".
     *
     * O `master_global` é resolvido antes de qualquer consulta, e não por descuido: ele
     * não tem `roles.painel` preenchido — nulo não é coringa, quem o faz entrar em todo
     * painel é o `Gate::before`. Uma consulta por painel devolveria `null` justamente
     * para quem tem mais acesso, e o cabeçalho ficaria sem badge no caso mais visível.
     *
     * A relação é `papeisEmQualquerContexto()`, a mesma de `canAccessPanel()`, e isso é
     * deliberado: o badge tem de dizer o papel que deu o acesso. Pela `roles()` do
     * spatie, com `permission.teams` ligado, a consulta ganharia
     * `wherePivot(team_id, ...)` do contexto corrente — e no `/admin` e no `/infra`, que
     * não têm tenant na rota, o badge sumiria conforme o `team_id` que estivesse setado.
     *
     * @return string|null Nome do papel (`admin_app`, `panel_user`…) ou null quando
     *                     nenhum papel deste usuário abre este painel.
     */
    public function papelDoPainel(string $painel): ?string
    {
        if ($this->isMasterGlobal()) {
            return config('filament-shield.super_admin.name', 'master_global');
        }

        /*
         * `getAttribute('name')` e não `->name`: o genérico da relação é `Model`, porque
         * `Config::roleModel()` é declarado `class-string<Model>` — a classe do papel sai
         * de `permission.models.role` em runtime. Prometer `Role` aqui seria afirmar mais
         * do que a fonte diz, e o PHPStan reprova o acesso direto à propriedade.
         */
        $papel = $this->papeisEmQualquerContexto()
            ->where('painel', $painel)
            ->where('guard_name', $this->getDefaultGuardName())
            ->first();

        return $papel?->getAttribute('name');
    }

    /** Papel guarda-chuva do kit — o "super admin" do Shield (define_via_gate). */
    public function isMasterGlobal(): bool
    {
        return $this->temPapelOnde(
            'name',
            config('filament-shield.super_admin.name', 'master_global'),
            $this->contextoGlobal(),
        );
    }

    /**
     * A pergunta única: existe papel deste usuário com `$coluna = $valor`?
     *
     * **Nada de `->when()` aqui.** `when()` é encaminhado da relação para o
     * `Eloquent\Builder`, e é o BUILDER que chega ao closure — `wherePivot()` não existe
     * lá. O filtro de contexto simplesmente não era aplicado, e o `isMasterGlobal()`
     * respondia `false` com a pivot correta no banco. Um `if` faz a coisa certa.
     */
    private function temPapelOnde(string $coluna, string $valor, ?int $contexto): bool
    {
        $papeis = $this->papeisEmQualquerContexto()
            ->where($coluna, $valor)
            ->where('guard_name', $this->getDefaultGuardName());

        if ($contexto !== null) {
            $papeis->wherePivot($this->colunaDeTeam(), $contexto);
        }

        return $papeis->exists();
    }

    /**
     * Papéis do usuário em QUALQUER contexto de team.
     *
     * É a `roles()` do spatie sem o `wherePivot(team_id, getPermissionsTeamId())` que
     * ele acrescenta quando `permission.teams` está ligado. Existe porque pergunta de
     * ACESSO A PAINEL não é pergunta de organização: "este usuário é admin em alguma
     * organização?" não pode depender de qual organização está aberta agora — e
     * `canAccessPanel()` roda ANTES de o tenant da rota ser identificado.
     *
     * Substitui o antigo `temPapelGlobal()`, que trocava o `PermissionRegistrar` do
     * container e descarregava a relação duas vezes para responder a mesma coisa.
     *
     * O genérico é `Model`, não `Role`, porque é isso que o próprio spatie garante:
     * `Config::roleModel()` é declarado `class-string<Model>` — a classe do papel sai de
     * `permission.models.role` em runtime (o kit aponta para `App\Models\Role`, um
     * projeto pode apontar para outra). Prometer `Role` aqui seria afirmar mais do que
     * a fonte diz.
     *
     * @return MorphToMany<Model, $this>
     */
    public function papeisEmQualquerContexto(): MorphToMany
    {
        return $this->morphToMany(
            Config::roleModel(),
            'model',
            Config::modelHasRolesTable(),
            Config::morphKey(),
            app(PermissionRegistrar::class)->pivotRole,
        );
    }

    /** Contexto exigido dos papéis que governam a instalação; null quando não há teams. */
    private function contextoGlobal(): ?int
    {
        return config('permission.teams') ? Tenant::CONTEXTO_GLOBAL : null;
    }

    private function colunaDeTeam(): string
    {
        return Config::teamForeignKey();
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
