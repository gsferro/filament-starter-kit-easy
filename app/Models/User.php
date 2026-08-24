<?php

namespace App\Models;

use App\Support\ContextoDePapeis;
use App\Support\RegistroAberto;
use App\Traits\AuditsFillables;
use App\Traits\ModeloCacheavel;
use App\Traits\TemUuid;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Support\Config;
use Spatie\Permission\Traits\HasRoles;

/**
 * `MustVerifyEmail` é o CONTRATO, e ele é global — vale nos três painéis.
 *
 * Ligá-lo não exige e-mail de ninguém: quem exige é o painel, por
 * `->emailVerification(…, isRequired: true)`, e só o /app o liga, e só quando
 * `RegistroAberto::exigirVerificacaoDeEmail()`. Sem o contrato, porém, a tela de confirmação
 * responde 500 — `EmailVerificationPrompt::getVerifiable()` declara retorno `MustVerifyEmail`
 * (`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`)
 * — e o middleware do Laravel não barra ninguém
 * (`Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40`). Era o passo 1 dos três que o
 * `AppPanelProvider` deixou escritos em comentário.
 *
 * O que o contrato NÃO faz, e é o que permitiu ligá-lo sem quebrar o convite: ele não dispara
 * e-mail sozinho. `Register::sendEmailVerificationNotification()` retorna cedo para quem já tem
 * o endereço validado (`Register.php:167-169`), e `Convite::aceitar()` grava `email_verified_at`
 * de propósito (`Convite.php:591`) — o token já provou posse do endereço. Convidado nasce
 * validado, vendor pula o envio.
 */
class User extends Authenticatable implements Auditable, FilamentUser, HasAvatar, HasTenants, MustVerifyEmail
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
            /*
             * `aprovacao_pendente` fica FORA do `$fillable`, como `email_verified_at`: é estado
             * de fronteira de acesso, e estado de fronteira não se escreve por atribuição em
             * massa vinda de formulário. Só `forceFill`, e só em `RegistroAberto::registrar()`
             * e em `aprovar()`.
             */
            'aprovacao_pendente' => 'boolean',
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
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
        /*
         * PRIMEIRA instrução, antes até do master_global — e a ordem é a decisão.
         *
         * "Pendente de aprovação" tem de significar painel NENHUM, sem exceção. Posta depois do
         * atalho do `master_global`, esta guarda teria um furo que ninguém veria: o atalho
         * devolve `true` sem consultar mais nada.
         *
         * Na prática, quem nasce pendente nasce SEM papel — `RegistroAberto::registrar()` só
         * atribui o papel depois da aprovação —, então nenhum painel abriria de qualquer forma.
         * Esta é a segunda barreira, deliberadamente redundante: ela vale para qualquer caminho
         * futuro que marque alguém como pendente, inclusive um que já tenha papel.
         */
        if ($this->aprovacao_pendente) {
            Log::channel('autenticacao')->warning(
                "[User@canAccessPanel] Acesso negado: cadastro pendente de aprovacao | user: {$this->id} - painel: {$panel->getId()}",
                [
                    'user_id' => $this->id,
                    'painel'  => $panel->getId(),
                    'motivo'  => 'aprovacao_pendente',
                ],
            );

            return false;
        }

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

    /**
     * Libera um cadastro que estava pendente, dando-lhe o papel do painel de negócio.
     *
     * **No model, e não no corpo da Action da tabela**, pela regra de
     * `.ai/rules/filament.md`: enquanto a tela for o único chamador funciona, e o primeiro job,
     * comando ou seeder chama o método direto. Aqui a transição é uma só, para todos.
     *
     * **Idempotente**, e isto não é zelo: a Action pode ser disparada duas vezes (duplo clique,
     * retry), e sem a saída antecipada o `assignRole()` rodaria de novo. O oráculo do caso de
     * teste é o agregado — "o usuário tem exatamente um papel depois de duas execuções".
     *
     * O contexto de papéis vem do request: no /app o middleware `DefinirTenantDePermissoes` já
     * fixou a organização corrente, que é justamente a organização de quem está aprovando. No
     * /admin (sem tenancy) o contexto é o global. Nos dois casos o `assignRole()` grava no lugar
     * certo sem esta função precisar saber onde está.
     */
    public function aprovar(): void
    {
        if (! $this->aprovacao_pendente) {
            return;
        }

        $this->forceFill(['aprovacao_pendente' => false])->save();

        $papel = RegistroAberto::papel();

        /*
         * O papel vai no contexto da ORGANIZAÇÃO, não no do request.
         *
         * `assignRole()` cru grava em `model_has_roles.team_id` o contexto corrente, e quem
         * aprova está no `/admin`, cujo contexto é o global. Resultado medido pelo quality
         * gate: `team_id = 0` com a organização em `id = 1`; dentro do `/app` o `wherePivot`
         * do spatie filtra pelo team do request, `roles` volta vazia, e a pessoa **autentica
         * e não vê nada** — `GET /app/{slug}` responde 200 com um painel vazio.
         *
         * E o estado é sem saída pela própria tela: a ação de aprovar tem
         * `->visible(fn () => $record->aprovacao_pendente)`, e a pendência já foi baixada
         * na linha acima — a mesma tela não conserta o que acabou de fazer.
         *
         * Cada organização do usuário recebe o papel no contexto dela. É o que `Convite`
         * já fazia por `contextoDoPapel()` (`app/Models/Convite.php:785-789`) e o que
         * `RegistroAberto::atribuirPapel()` faz no cadastro. Sem tenancy, `contextos()`
         * devolve só o global, e o spatie ignora o team quando a flag está desligada.
         */
        foreach ($this->contextosDePapelDoApp() as $contexto) {
            ContextoDePapeis::em($contexto, $this, function () use ($papel): void {
                if (! $this->hasRole($papel)) {
                    $this->assignRole($papel);
                }
            });
        }

        Log::channel('autenticacao')->info(
            "[User@aprovar] Cadastro aprovado | alvo: {$this->id}",
            [
                'alvo_id'     => $this->id,
                'executor_id' => Auth::id(),
                'email'       => Str::mask($this->email, '*', 3),
                'tenant_id'   => Filament::getTenant()?->getKey(),
                'papel'       => $papel,
            ],
        );
    }

    /**
     * Em quais contextos o papel do painel `app` deve ser gravado para este usuário.
     *
     * Sem tenancy, um: o global — e o spatie ignora o team quando `permission.teams` está
     * desligado, então o valor é indiferente ali. Com tenancy, um por organização do usuário,
     * porque é o `team_id` que decide se o papel é visível dentro de `/app/{slug}`.
     *
     * Usuário com tenancy e **nenhuma** organização cai no global. Não é caminho do registro
     * aberto (`RegistroAberto::exigirPortaAberta()` recusa antes), mas é alcançável por quem
     * marcou a pendência à mão — e nesse caso o global é o menos errado: não concede acesso a
     * organização nenhuma, e `canAccessPanel()` continua sendo a barreira.
     *
     * @return list<int>
     */
    private function contextosDePapelDoApp(): array
    {
        if (! (bool) config('kit.tenancy.enabled')) {
            return [Tenant::CONTEXTO_GLOBAL];
        }

        $organizacoes = array_values(
            array_map(intval(...), $this->tenants()->pluck('tenants.id')->all()),
        );

        return $organizacoes === []
            ? [Tenant::CONTEXTO_GLOBAL]
            : $organizacoes;
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
