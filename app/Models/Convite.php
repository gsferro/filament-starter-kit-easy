<?php

namespace App\Models;

use App\Notifications\ConviteDeAcesso;
use App\Traits\AuditsFillables;
use App\Traits\TemUuid;
use Database\Factories\ConviteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Support\Config;

/**
 * Convite de acesso — a única porta pela qual alguém de fora vira usuário.
 *
 * O convite carrega TRÊS decisões que já foram tomadas por quem convidou: o e-mail, o
 * papel e (com tenancy) a organização. Quem clica no link só escolhe nome e senha — o
 * resto é imposto pelo servidor, porque estado de formulário é do cliente.
 *
 * O token é a credencial: quem o tem cria uma conta com o papel do convite. Por isso ele
 * vai HASHEADO para o banco, vale UMA vez (`aceito_em`) e por um PRAZO (`expira_em`).
 * Ver ADR-02 em `wikis/specs/main/convite-de-usuario/02-decisoes-arquiteturais.md`.
 *
 * @property int $id
 * @property string $uuid
 * @property string $email
 * @property ?string $token
 * @property int $role_id
 * @property ?int $tenant_id
 * @property ?int $convidado_por_id
 * @property ?Carbon $expira_em
 * @property ?Carbon $aceito_em
 * @property ?Carbon $recusado_em
 */
class Convite extends Model implements Auditable
{
    use AuditsFillables;

    /** @use HasFactory<ConviteFactory> */
    use HasFactory;

    use TemUuid;

    /**
     * `uuid` e `token` ficam FORA: o trait cuida do uuid, e o token é segredo.
     *
     * Não é estilo, é segurança: `AuditsFillables::getAuditInclude()` devolve o
     * `$fillable`, então o hash nunca entra na trilha de `/infra/audits` — nem na de
     * exclusão, que é como a revogação fica registrada.
     */
    protected $fillable = [
        'email',
        'role_id',
        'tenant_id',
        'convidado_por_id',
        'expira_em',
        'aceito_em',

        // Dentro do $fillable de propósito, ao contrário do `token`: a recusa é
        // informação de acesso e deve aparecer na trilha de /infra/audits.
        'recusado_em',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expira_em'   => 'datetime',
            'aceito_em'   => 'datetime',
            'recusado_em' => 'datetime',
        ];
    }

    /**
     * O papel concedido no aceite.
     *
     * O genérico é `Model` porque a classe do papel sai de `permission.models.role` em
     * runtime — o kit aponta para `App\Models\Role`, um projeto pode apontar para outra.
     * Pela mesma razão os atributos se leem por `getAttribute()`.
     *
     * @return BelongsTo<Model, $this>
     */
    public function papel(): BelongsTo
    {
        return $this->belongsTo(Config::roleModel(), 'role_id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function convidadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'convidado_por_id');
    }

    /**
     * Gera um token novo, invalida o anterior e envia o convite.
     *
     * Serve tanto ao primeiro envio quanto ao reenvio: reenviar é gerar de novo, e o link
     * antigo morre porque a coluna que ele casaria foi sobrescrita. Não existe
     * `reenviar()` — seria este mesmo corpo com outro nome.
     *
     * Devolve o token EM CLARO — que existe aqui, no e-mail e no link de quem recebeu, e
     * em lugar nenhum mais. Nunca logar, nunca guardar, nunca devolver numa resposta HTTP.
     */
    public function enviar(): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'token'     => hash('sha256', $token),
            'expira_em' => now()->addDays((int) config('kit.convites.validade_em_dias', 7)),
            'aceito_em' => null,
        ])->save();

        Notification::route('mail', $this->email)->notify(new ConviteDeAcesso($this, $token));

        Log::channel('autenticacao')->info(
            "[Convite@enviar] Convite enviado | convite: {$this->id} - email: ".Str::mask($this->email, '*', 3),
            [
                'convite_id'    => $this->id,
                'email'         => Str::mask($this->email, '*', 3),
                'role_id'       => $this->role_id,
                'papel'         => $this->papel?->getAttribute('name'),
                'painel'        => $this->painelDoPapel(),
                'tenant_id'     => $this->tenant_id,
                'expira_em'     => $this->expira_em?->toIso8601String(),
                'convidado_por' => $this->convidado_por_id,
                'reenvio'       => $this->wasRecentlyCreated === false,
            ],
        );

        return $token;
    }

    /**
     * O convite utilizável por este token, ou null.
     *
     * Um método só para os motivos de recusa (inexistente, expirado, já aceito, recusado)
     * porque o chamador não deve poder distingui-los: a tela responde igual em todos.
     * Devolver o motivo faria alguém exibi-lo "para ajudar", e "este convite já foi usado"
     * confirma que o convite existiu. Ver ADR-02.
     */
    public static function valido(?string $token): ?self
    {
        if (blank($token)) {
            return null;
        }

        return static::query()
            ->where('token', hash('sha256', (string) $token))
            ->whereNull('aceito_em')
            ->whereNull('recusado_em')
            ->where('expira_em', '>', now())
            ->first();
    }

    /**
     * A conta que já existe para o e-mail deste convite, ou null.
     *
     * Um método para as duas perguntas: `aceitar()` precisa do objeto para desviar, o
     * `mount()` da tela de registro só precisa saber se existe. Dois métodos seriam duas
     * formas da mesma query, que é como uma delas envelhece sozinha.
     *
     * Comparação normalizada, pelo mesmo motivo de `exigirDono()`: e-mail não é
     * case-sensitive na prática, e um convite gravado com maiúsculas criaria conta
     * duplicada em vez de desviar.
     */
    public function usuarioExistente(): ?User
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($this->email))])
            ->first();
    }

    /**
     * Ofertas de acesso pendentes endereçadas a este usuário.
     *
     * Alimenta a caixa de entrada E o contador do menu do usuário. Vive aqui, e não num
     * `where` escrito na página, porque duas cópias divergem — e a que divergisse seria o
     * contador dizendo "1" numa tela vazia.
     *
     * @return Builder<static>
     */
    public static function pendentesPara(?User $user): Builder
    {
        $query = static::query()
            ->whereNull('aceito_em')
            ->whereNull('recusado_em')
            ->where('expira_em', '>', now());

        return $user instanceof User
            ? $query->whereRaw('lower(email) = ?', [mb_strtolower(trim($user->email))])
            // Sem usuário não há oferta endereçada a ninguém. Fecha em vez de devolver
            // tudo: o mesmo princípio do getEloquentQuery() do UserResource do /app.
            : $query->whereRaw('1 = 0');
    }

    /**
     * O estado do convite, derivado — não há coluna de status.
     *
     * Vive no model porque DUAS telas o mostram: a de /admin e a de /app. Elas divergiam
     * (a do /app mostrava `aceito_em` com placeholder "Pendente", que mentiria para um
     * convite recusado), e duas telas derivando o mesmo estado por dois caminhos é como a
     * divergência volta.
     *
     * Aceito vence expirado: um convite aceito ontem não passa a ser "Expirado" hoje.
     */
    public function situacao(): string
    {
        return match (true) {
            $this->aceito_em !== null              => 'Aceito',
            $this->recusado_em !== null            => 'Recusado',
            $this->expira_em?->isPast() ?? true    => 'Expirado',
            default                                => 'Pendente',
        };
    }

    /**
     * Cria o usuário, vincula a organização e atribui o papel NO CONTEXTO CERTO.
     *
     * Roda dentro da transação que `Register::register()` já abriu
     * (`vendor/filament/filament/src/Auth/Pages/Register.php:84-102`): se qualquer passo
     * falhar, não sobra usuário órfão nem convite meio-aceito.
     *
     * @param  array<string, mixed>  $dados  o estado do formulário, já validado e com a senha hasheada
     */
    public function aceitar(array $dados): User
    {
        /*
         * Quem já tem conta não ganha outra: o convite vira OFERTA DE ACESSO, e quem
         * confirma é a própria pessoa, autenticada. Até a v0.11.0 isto lançava
         * `RuntimeException('E-mail já cadastrado.')`, o que fazia do convite uma parede
         * no caso mais comum de SaaS multi-tenant — a consultora que atende dois clientes.
         * Ver ADR-01 de `wikis/specs/main/convite-para-usuario-existente/`.
         */
        if ($existente = $this->usuarioExistente()) {
            return $this->aceitarComoUsuarioExistente($existente);
        }

        // O e-mail vem do CONVITE, sempre. O que veio do formulário morre nesta linha.
        $user = User::create([...$dados, 'email' => $this->email]);

        /*
         * O token PROVA posse do endereço: a pessoa recebeu o link nele, e o link é a
         * única forma de chegar a esta linha. Pedir verificação depois disso é pedir a
         * mesma prova duas vezes. `email_verified_at` está fora do `$fillable` do User,
         * então mass assignment o descartaria em silêncio — daí o forceFill.
         *
         * Hoje nenhum painel liga `->emailVerification()`, então isto é inócuo; no dia em
         * que ligar, sem esta linha todo usuário nascido de convite é barrado na porta.
         */
        $user->forceFill(['email_verified_at' => now()])->save();

        if ($this->tenant_id !== null) {
            $user->tenants()->syncWithoutDetaching([$this->tenant_id]);
        }

        $this->atribuirPapel($user);

        // O uso único: `Convite::valido()` já não devolve este convite.
        $this->forceFill(['aceito_em' => now()])->save();

        Log::channel('autenticacao')->info(
            "[Convite@aceitar] Convite aceito | convite: {$this->id} - user: {$user->id}",
            [
                'convite_id'     => $this->id,
                'user_id'        => $user->id,
                'email'          => Str::mask($this->email, '*', 3),
                'papel'          => $this->papel?->getAttribute('name'),
                'painel'         => $this->painelDoPapel(),
                'tenant_id'      => $this->tenant_id,
                'contexto_papel' => $this->contextoDoPapel(),
            ],
        );

        return $user;
    }

    /**
     * Vincula à organização do convite um usuário QUE JÁ EXISTE, com o papel dele.
     *
     * A asserção de e-mail está AQUI, e não na query da tela que lista as ofertas. É a
     * diferença entre este método e o `TeamInvitation::accept()` do
     * `jeffersongoncalves/filament-teams`, que anexa qualquer `Authenticatable` e confia
     * no `->where('email', …)` da tabela da página: naquele desenho o primeiro chamador
     * novo — um job, um comando, uma rota de API — passa por cima da barreira sem que nada
     * acuse. Ver ADR-03.
     *
     * @throws RuntimeException quando o e-mail não corresponde, ou o convite já foi usado
     */
    public function aceitarComoUsuarioExistente(User $user): User
    {
        $this->exigirDono($user, 'aceitarComoUsuarioExistente');

        /*
         * Consumo ATÔMICO, e é aqui que esta via difere da de conta nova.
         *
         * Lá o `unique` de `users.email` aborta um segundo aceite concorrente, e a
         * transação inteira volta atrás. Aqui não existe unique que salve: `attach` e
         * `assignRole` são idempotentes, então duas requisições simultâneas passariam as
         * duas e o papel seria atribuído duas vezes. O `UPDATE ... WHERE aceito_em IS NULL`
         * é atômico no banco — a segunda recebe 0 e para antes de vincular. É o defeito do
         * `laravel-invite-only`, cujo `accept()` é check-then-act puro. Ver ADR-04.
         */
        $consumido = static::query()
            ->whereKey($this->getKey())
            ->whereNull('aceito_em')
            ->whereNull('recusado_em')
            ->update(['aceito_em' => now()]);

        if ($consumido !== 1) {
            throw new RuntimeException('Este convite já foi usado.');
        }

        // `update()` não toca a instância em memória; sem o refresh o log abaixo sairia
        // com `aceito_em` nulo.
        $this->refresh();

        // syncWithoutDetaching e não attach: reconvite de quem já é membro não pode
        // estourar o unique de `tenant_user`.
        if ($this->tenant_id !== null) {
            $user->tenants()->syncWithoutDetaching([$this->tenant_id]);
        }

        $this->atribuirPapel($user);

        Log::channel('autenticacao')->info(
            "[Convite@aceitarComoUsuarioExistente] Oferta de acesso aceita | convite: {$this->id} - user: {$user->id}",
            [
                'convite_id'     => $this->id,
                'user_id'        => $user->id,
                'email'          => Str::mask($user->email, '*', 3),
                'papel'          => $this->papel?->getAttribute('name'),
                'painel'         => $this->painelDoPapel(),
                'tenant_id'      => $this->tenant_id,
                'contexto_papel' => $this->contextoDoPapel(),
            ],
        );

        return $user;
    }

    /**
     * O convidado diz não.
     *
     * Registra em vez de apagar a linha (que é o que o teamkit faz): "ela recusou" é
     * informação diferente de "o convite desapareceu", e é o que impede reconvidar alguém
     * que já disse não.
     *
     * @throws RuntimeException quando o e-mail não corresponde, ou o convite já foi usado
     */
    public function recusar(User $user): void
    {
        $this->exigirDono($user, 'recusar');

        $consumido = static::query()
            ->whereKey($this->getKey())
            ->whereNull('aceito_em')
            ->whereNull('recusado_em')
            ->update(['recusado_em' => now()]);

        if ($consumido !== 1) {
            throw new RuntimeException('Este convite já foi usado.');
        }

        $this->refresh();

        // `warning` e não `info`: recusa não é falha, mas é o fim de uma concessão de
        // acesso — e é no nível de aviso que se procura por isso no log.
        Log::channel('autenticacao')->warning(
            "[Convite@recusar] Oferta de acesso recusada | convite: {$this->id} - user: {$user->id}",
            [
                'convite_id' => $this->id,
                'user_id'    => $user->id,
                'email'      => Str::mask($user->email, '*', 3),
                'papel'      => $this->papel?->getAttribute('name'),
                'tenant_id'  => $this->tenant_id,
            ],
        );
    }

    /**
     * O convite é DESTE usuário?
     *
     * Comparação normalizada: e-mail não é case-sensitive na prática, e o convite pode ter
     * sido digitado com maiúsculas por quem convidou.
     *
     * @throws RuntimeException quando não é
     */
    private function exigirDono(User $user, string $metodo): void
    {
        if (mb_strtolower(trim($user->email)) === mb_strtolower(trim($this->email))) {
            return;
        }

        Log::channel('autenticacao')->warning(
            "[Convite@{$metodo}] Acao recusada, e-mail nao corresponde | convite: {$this->id} - user: {$user->id}",
            [
                'convite_id'    => $this->id,
                'user_id'       => $user->id,
                'email_convite' => Str::mask($this->email, '*', 3),
                'email_usuario' => Str::mask($user->email, '*', 3),
                'motivo'        => 'email_nao_corresponde',
            ],
        );

        throw new RuntimeException('Este convite não é para a sua conta.');
    }

    /**
     * Atribui o papel do convite no contexto que o painel dele exige.
     *
     * Painel sem tenancy (/admin, /infra) governa a instalação inteira, e
     * `User::canAccessPanel()` exige o papel no contexto global. Painel de negócio (/app)
     * exige o papel dentro da organização. Errar aqui cria um usuário que entra e leva 403
     * — sem erro nenhum no caminho.
     *
     * Extraído porque as duas vias de aceite usam exatamente isto, e é a decisão mais
     * fácil de errar da feature.
     */
    private function atribuirPapel(User $user): void
    {
        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();

        try {
            // Sem `permission.teams` o spatie ignora — um caminho para os dois modos.
            $registrar->setPermissionsTeamId($this->contextoDoPapel());

            // assignRole(), NUNCA sync() na relação: o sync escreve só as colunas da chave
            // e estoura `NOT NULL constraint failed: model_has_roles.team_id`.
            $user->assignRole($this->papel);
        } finally {
            $registrar->setPermissionsTeamId($anterior);
            $user->unsetRelation('roles');
        }
    }

    /**
     * O `model_has_roles.team_id` em que o papel deste convite deve ser gravado.
     *
     * ponytail: comparação literal com 'app' porque o kit tem um painel com tenancy.
     * Quando houver um segundo, troque por `Filament::getPanel($painel)->hasTenancy()` — a
     * mesma fonte que `canAccessPanel()` usa.
     */
    private function contextoDoPapel(): int
    {
        return $this->painelDoPapel() === 'app'
            ? ($this->tenant_id ?? Tenant::CONTEXTO_GLOBAL)
            : Tenant::CONTEXTO_GLOBAL;
    }

    /** O painel que o papel do convite declara (`roles.painel`), ou null. */
    private function painelDoPapel(): ?string
    {
        $painel = $this->papel?->getAttribute('painel');

        return is_string($painel) ? $painel : null;
    }
}
