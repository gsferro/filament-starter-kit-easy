<?php

namespace App\Models;

use App\Notifications\ConviteDeAcesso;
use App\Traits\AuditsFillables;
use App\Traits\TemUuid;
use Database\Factories\ConviteFactory;
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
            'expira_em' => 'datetime',
            'aceito_em' => 'datetime',
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
     * Um método só para os três motivos de recusa (inexistente, expirado, já aceito)
     * porque o chamador não deve poder distingui-los: a tela responde igual nos três
     * casos. Devolver o motivo faria alguém exibi-lo "para ajudar", e "este convite já
     * foi usado" confirma que o convite existiu. Ver ADR-02.
     */
    public static function valido(?string $token): ?self
    {
        if (blank($token)) {
            return null;
        }

        return static::query()
            ->where('token', hash('sha256', (string) $token))
            ->whereNull('aceito_em')
            ->where('expira_em', '>', now())
            ->first();
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
         * Fronteira de confiança, e não preciosismo: entre o convite e o clique podem
         * passar dias, e o e-mail pode ter virado usuário por outro caminho. A coluna
         * `users.email` é única e o banco também recusaria — mas com uma exceção de
         * driver, que não vira mensagem legível.
         */
        if (User::query()->where('email', $this->email)->exists()) {
            Log::channel('autenticacao')->warning(
                "[Convite@aceitar] Aceite recusado, e-mail ja cadastrado | convite: {$this->id}",
                [
                    'convite_id' => $this->id,
                    'email'      => Str::mask($this->email, '*', 3),
                    'motivo'     => 'email_ja_cadastrado',
                ],
            );

            throw new RuntimeException('E-mail já cadastrado.');
        }

        // O e-mail vem do CONVITE, sempre. O que veio do formulário morre nesta linha.
        $user = User::create([...$dados, 'email' => $this->email]);

        if ($this->tenant_id !== null) {
            $user->tenants()->attach($this->tenant_id);
        }

        /*
         * Painel sem tenancy (/admin, /infra) governa a instalação inteira, e
         * User::canAccessPanel() exige o papel no contexto global. Painel de negócio
         * (/app) exige o papel dentro da organização. Errar aqui cria um usuário que
         * entra e leva 403 — sem erro nenhum no caminho. Ver ADR-07.
         *
         * ponytail: comparação literal com 'app' porque o kit tem um painel com tenancy.
         * Quando houver um segundo, troque por `Filament::getPanel($painel)->hasTenancy()`
         * — a mesma fonte que `canAccessPanel()` usa.
         */
        $contexto = $this->painelDoPapel() === 'app'
            ? $this->tenant_id
            : Tenant::CONTEXTO_GLOBAL;

        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();

        try {
            // Sem `permission.teams` o spatie ignora — um caminho para os dois modos.
            $registrar->setPermissionsTeamId($contexto ?? Tenant::CONTEXTO_GLOBAL);

            // assignRole(), NUNCA sync() na relação: o sync escreve só as colunas da
            // chave e estoura `NOT NULL constraint failed: model_has_roles.team_id`.
            $user->assignRole($this->papel);
        } finally {
            $registrar->setPermissionsTeamId($anterior);
        }

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
                'contexto_papel' => $contexto ?? Tenant::CONTEXTO_GLOBAL,
            ],
        );

        return $user;
    }

    /** O painel que o papel do convite declara (`roles.painel`), ou null. */
    private function painelDoPapel(): ?string
    {
        $painel = $this->papel?->getAttribute('painel');

        return is_string($painel) ? $painel : null;
    }
}
