<?php

namespace App\Models;

use App\Support\ContextoDePapeis;
use App\Support\ProvedorSocial;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use OwenIt\Auditing\Contracts\Auditable;
use Promethys\Revive\Concerns\Recyclable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use RuntimeException;
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

    /**
     * Excluir é LÓGICO: `delete()` grava `deleted_at`, a linha fica, e as pivots (`tenant_user`,
     * `model_has_roles`) e `vinculos_sociais` ficam com ela — restaurar devolve o que havia.
     *
     * `Recyclable` é o que faz a Lixeira do /infra (`promethys/revive`) enxergar a exclusão: a
     * tela lista `recycle_bin_items`, e quem grava a linha ali é o evento `deleted` desta trait
     * (`vendor/promethys/revive/src/Concerns/Recyclable.php:29-45`). `SoftDeletes` sozinho é
     * exclusão sem tela para desfazer. Guarda: `tests/Kit/LixeiraTest.php`.
     *
     * Ela sobrescreve `booted()` — uma `booted()` futura nesta classe precisa chamar a da trait.
     * Wiki `status-e-exclusao-logica-de-usuario`, ADR-06.
     */
    use Recyclable;

    use SoftDeletes;
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

    /**
     * Defaults de atributos que vivem FORA do $fillable mas nascem com valor na migration.
     * Sem isso, instâncias recém-criadas em memória leem `null` antes do primeiro refresh,
     * e `ativo` vira `false` no cast boolean — trancando o usuário fora.
     */
    protected $attributes = [
        'ativo' => true,
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
            // Mesmo regime: fora do `$fillable`, só `forceFill` em `desativar()`/`reativar()`.
            'ativo'              => 'boolean',
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
         * Conta inativa ou excluída não entra em painel NENHUM — antes até da pendência, pelo
         * mesmo argumento de ordem escrito logo abaixo. É a única decisão: a tela de login por
         * senha, o login social e o middleware do painel (a cada request de quem já está dentro)
         * perguntam aqui. Quem EXPLICA a negativa para a pessoa é `TelaLogin` e o
         * `LoginSocialController`, via `motivoDeIndisponibilidade()`. ADR-01 da wiki
         * `status-e-exclusao-logica-de-usuario`.
         */
        if (($motivo = $this->motivoDeIndisponibilidade()) !== null) {
            Log::channel('autenticacao')->warning(
                "[User@canAccessPanel] Acesso negado: {$motivo} | user: {$this->id} - painel: {$panel->getId()}",
                [
                    'user_id'     => $this->id,
                    'painel'      => $panel->getId(),
                    'motivo'      => $motivo,
                    'email'       => Str::mask((string) $this->email, '*', 3),
                    'excluida_em' => $this->deleted_at?->toIso8601String(),
                ],
            );

            return false;
        }

        /*
         * Depois da indisponibilidade e antes do master_global — e a ordem é a decisão.
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

    /*
    |--------------------------------------------------------------------------
    | Estado da conta: ativo/inativo e exclusão lógica
    |--------------------------------------------------------------------------
    | `ativo` e `deleted_at` são estado de fronteira de acesso, fora do `$fillable`.
    | As transições vivem aqui, no model, para valer para qualquer chamador — a tela
    | só as espelha. Wiki `status-e-exclusao-logica-de-usuario`.
    */

    /**
     * Por que esta conta não pode entrar — ou `null` quando pode.
     *
     * Excluída vence inativa: a mensagem com a data é a mais informativa, e uma conta excluída
     * que também estava inativa não deve ouvir "reative", e sim "restaure".
     *
     * @return 'conta_excluida'|'conta_inativa'|null
     */
    public function motivoDeIndisponibilidade(): ?string
    {
        return match (true) {
            $this->trashed() => 'conta_excluida',
            ! $this->ativo   => 'conta_inativa',
            default          => null,
        };
    }

    /**
     * A conta com este e-mail, comparada de forma normalizada nos dois lados.
     *
     * `lower()` no SQL e `mb_strtolower()` no valor: e-mail não é case-sensitive na prática. Em
     * MySQL `_ci` o `lower()` é redundante; em SQLite e Postgres não é, e o kit roda nos três.
     * Um escopo só para as três perguntas que existiam em cópia (login social, convite e agora
     * a tela de login) — quem quiser incluir excluídos encadeia `withTrashed()` antes.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeComEmail(Builder $query, string $email): Builder
    {
        return $query->whereRaw('lower('.$query->qualifyColumn('email').') = ?', [mb_strtolower(trim($email))]); // @phpstan-ignore argument.type
    }

    /**
     * Desativa a conta: ela deixa de entrar em qualquer painel até `reativar()`.
     *
     * Idempotente, como `aprovar()`. As duas recusas estão aqui, e não só no `->visible()` da
     * ação, porque barreira que só existe na tela não é barreira (`.ai/rules/filament.md`).
     *
     * @throws RuntimeException quando é a própria conta ou o último `master_global` ativo
     */
    public function desativar(): void
    {
        if (! $this->ativo) {
            return;
        }

        if (($razao = $this->motivoParaNaoDesativar()) !== null) {
            Log::channel('autenticacao')->warning(
                "[User@desativar] Desativação recusada | alvo: {$this->id} - razao: {$razao}",
                [
                    'alvo_id'     => $this->id,
                    'executor_id' => Auth::id(),
                    'motivo'      => 'desativacao_recusada',
                    'razao'       => $razao,
                ],
            );

            throw new RuntimeException($razao === 'propria_conta'
                ? 'Você não pode desativar a própria conta.'
                : 'Este é o último master_global ativo da instalação.');
        }

        $this->forceFill(['ativo' => false])->save();

        Log::channel('autenticacao')->info(
            "[User@desativar] Usuário desativado | alvo: {$this->id}",
            [
                'alvo_id'     => $this->id,
                'executor_id' => Auth::id(),
                'email'       => Str::mask((string) $this->email, '*', 3),
            ],
        );
    }

    /** O inverso de `desativar()`. Idempotente. */
    public function reativar(): void
    {
        if ($this->ativo) {
            return;
        }

        $this->forceFill(['ativo' => true])->save();

        Log::channel('autenticacao')->info(
            "[User@reativar] Usuário reativado | alvo: {$this->id}",
            [
                'alvo_id'     => $this->id,
                'executor_id' => Auth::id(),
                'email'       => Str::mask((string) $this->email, '*', 3),
            ],
        );
    }

    /**
     * Por que esta conta NÃO pode ser desativada agora — ou `null` quando pode.
     *
     * A única fonte da regra: `desativar()` lança quando não é nulo, e a Action da tela se
     * esconde pelo mesmo valor. `Auth::user()` nulo (job, comando) nunca é "a própria conta".
     *
     * @return 'propria_conta'|'ultimo_master_global'|null
     */
    public function motivoParaNaoDesativar(): ?string
    {
        return match (true) {
            $this->is(Auth::user())             => 'propria_conta',
            $this->ehOUltimoMasterGlobalAtivo() => 'ultimo_master_global',
            default                             => null,
        };
    }

    /**
     * É `master_global` e não existe OUTRO `master_global` ativo (e não excluído) no contexto global?
     *
     * Uma consulta só, pela mesma relação de `canAccessPanel()`. Colunas qualificadas porque a
     * subconsulta do `whereHas` junta `roles` a `users`, e `name` existe nas duas.
     */
    public function ehOUltimoMasterGlobalAtivo(): bool
    {
        if (! $this->isMasterGlobal()) {
            return false;
        }

        $outrosAtivos = self::query()
            ->where('ativo', true)
            ->whereKeyNot($this->getKey())
            ->whereHas('papeisEmQualquerContexto', function (Builder $papeis): void {
                $papeis
                    ->where($papeis->qualifyColumn('name'), config('filament-shield.super_admin.name', 'master_global'))
                    ->where($papeis->qualifyColumn('guard_name'), $this->getDefaultGuardName());

                if (config('permission.teams')) {
                    $papeis->where(Config::modelHasRolesTable().'.'.$this->colunaDeTeam(), Tenant::CONTEXTO_GLOBAL);
                }
            });

        return $outrosAtivos->doesntExist();
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
    public function papelDoPainel(string $painel, ?int $contexto = null): ?string
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
        $papeis = $this->papeisEmQualquerContexto()
            ->where('painel', $painel)
            ->where('guard_name', $this->getDefaultGuardName());

        /*
         * `$contexto` é a ORGANIZAÇÃO de quem pergunta, e existe porque a mesma pessoa pode ter
         * papéis diferentes do mesmo painel em organizações diferentes — `panel_user` na Acme e
         * `admin_app` na Globex, os dois com `roles.painel = 'app'`. Sem o filtro, o `first()`
         * abaixo devolve o de menor `id` e o badge mostra o mesmo papel nas duas.
         *
         * Nulo é o default de propósito: quem pergunta por ACESSO ("este usuário entra no /app?")
         * não pode depender da organização aberta, e é assim que `/admin` e `/infra` continuam
         * respondendo — lá não há organização corrente. A separação entre as duas perguntas está
         * na ADR-01 da wiki badge-de-papel-por-organizacao.
         */
        if ($contexto !== null) {
            $papeis->wherePivot($this->colunaDeTeam(), $contexto);
        }

        return $papeis->first()?->getAttribute('name');
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
    /** Valores de `origem` que não são provedor social. O provedor grava o próprio driver. */
    public const ORIGEM_INTERNO = 'interno';

    public const ORIGEM_CONVITE = 'convite';

    public const ORIGEM_REGISTRO = 'registro';

    /**
     * Por qual porta a conta entrou, por extenso — para a lista de usuários e o dashboard.
     *
     * Provedor social devolve o rótulo da marca; o resto, a porta do kit. Valor desconhecido
     * (coluna editada à mão, provedor removido do enum) cai em "Interno", e não em erro: é
     * exibição, nunca autorização.
     */
    public function rotuloDaOrigem(): string
    {
        $origem = (string) ($this->origem ?? self::ORIGEM_INTERNO);

        return ProvedorSocial::tryFrom($origem)?->rotulo() ?? match ($origem) {
            self::ORIGEM_CONVITE  => 'Convite',
            self::ORIGEM_REGISTRO => 'Registro aberto',
            default               => 'Interno',
        };
    }

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
     * As identidades desta conta nos provedores de login social — ver `VinculoSocial`.
     *
     * @return HasMany<VinculoSocial, $this>
     */
    public function vinculosSociais(): HasMany
    {
        return $this->hasMany(VinculoSocial::class);
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

    /**
     * Quem pode ser ALVO de personificação.
     *
     * Três recusas, e as duas últimas são a correção: personificar não pode ser o caminho lateral
     * em volta de `canAccessPanel()`. A conta inativa, a pendente e a excluída são recusadas no
     * login — por senha, por login social e pelo middleware do painel — e entrar nelas pelo
     * `/admin` contornava a decisão da wiki `status-e-exclusao-logica-de-usuario` sem nada acusar:
     * a pessoa desativada via o aviso "procure o administrador", e o administrador entrava por ela.
     *
     * A pergunta é a MESMA de `canAccessPanel()`, e de propósito: `motivoDeIndisponibilidade()`
     * mais `aprovacao_pendente`. Reler `ativo` e `deleted_at` aqui seria a segunda cópia de uma
     * regra que já tem dona, e a cópia divergiria no primeiro ajuste. A pendência fica fora
     * daquele método porque ele alimenta a MENSAGEM que a tela de login mostra, e um terceiro
     * valor mudaria o texto que a pessoa pendente vê hoje — ver ADR-03 da wiki deste fix.
     *
     * **Isto não é barreira de tela.** O pacote consulta este método no `visible()` da ação
     * (`vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:37`) E outra vez
     * antes de executar (`:112` → `:167`), então esconder e recusar vêm da mesma linha.
     *
     * E ele fecha uma guarda que era do vendor: a conta excluída só estava protegida pelo default
     * de `config('filament-impersonate.allow_soft_deleted')` (`:157-159`), config que o kit nunca
     * publicou — um `FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED=true` no `.env` a reabriria.
     */
    public function canBeImpersonated(): bool
    {
        // Master global nunca é alvo de impersonação.
        if ($this->isMasterGlobal()) {
            return false;
        }

        $razao = $this->motivoDeIndisponibilidade()
            ?? ($this->aprovacao_pendente ? 'aprovacao_pendente' : null);

        if ($razao === null) {
            return true;
        }

        /*
         * Log só na recusa, e só com operador autenticado — este método é chamado pelo
         * `visible()` de CADA linha da tabela de usuários, então registrar o caminho feliz
         * produziria uma linha por usuário listado, por render. É o ruído que a nota do canal
         * `autenticacao` mediu em 1,1 MB/dia. Sem `Auth::id()` (comando, fila, teste de model
         * direto) não há ato humano a auditar. Ver ADR-05.
         */
        if (Auth::hasUser()) {
            Log::channel('autenticacao')->warning(
                "[User@canBeImpersonated] Personificação recusada | alvo: {$this->id} - razao: {$razao}",
                [
                    'alvo_id'     => $this->id,
                    'executor_id' => Auth::id(),
                    'motivo'      => 'personificacao_recusada',
                    'razao'       => $razao,
                    'email'       => Str::mask((string) $this->email, '*', 3),
                ],
            );
        }

        return false;
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
