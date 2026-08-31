<?php

namespace App\Models;

use App\Notifications\ConviteDeAcesso;
use App\Support\ContextoDePapeis;
use App\Traits\AuditsFillables;
use App\Traits\ModeloCacheavel;
use App\Traits\TemUuid;
use Database\Factories\ConviteFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;
use RuntimeException;
use Spatie\Permission\Support\Config;
use Throwable;

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
 * @property ?string $token_lembrete
 * @property ?Carbon $enviado_em
 * @property int $lembretes_enviados
 */
class Convite extends Model implements Auditable
{
    use AuditsFillables;

    /** @use HasFactory<ConviteFactory> */
    use HasFactory;

    use ModeloCacheavel;
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

    /**
     * Os DOIS hashes de token, pelo mesmo motivo: fora do `$fillable` eles não entram na
     * auditoria, e aqui não aparecem em `toArray()`, num `dd($convite)` nem num `$context`
     * de log que passe o model inteiro. Hash de credencial não é dado de diagnóstico.
     */
    protected $hidden = [
        'token',
        'token_lembrete',
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
            'enviado_em'  => 'datetime',
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
        /*
         * O `save()` abaixo grava só o que está SUJO, e é isso que obriga o refresh: uma
         * instância carregada ANTES de um lembrete tem `lembretes_enviados` e
         * `token_lembrete` velhos em memória, o `forceFill` os igualaria ao valor antigo,
         * o Eloquent não veria mudança nenhuma e a reinicialização seria descartada EM
         * SILÊNCIO — o link do último lembrete continuaria valendo depois de um reenvio
         * que promete matá-lo. Um SELECT por chave primária, num método que já faz UPDATE
         * + e-mail + log. Ver CT-09.
         */
        $this->refresh();

        $token = Str::random(64);

        $this->forceFill([
            'token'     => hash('sha256', $token),
            'expira_em' => now()->addDays((int) config('kit.convites.validade_em_dias', 7)),
            'aceito_em' => null,
            /*
             * Reenviar é emitir um convite novo: o link anterior morre, e o link do
             * ÚLTIMO LEMBRETE tem de morrer com ele. Sem esta linha, um lembrete enviado
             * antes do reenvio continuaria aceitando — e a promessa da modal de
             * confirmação ("o link anterior deixa de funcionar") seria mentira pela
             * metade.
             */
            'token_lembrete' => null,
            // O relógio dos lembretes começa AQUI, não em `created_at`. Ver ADR-02.
            'enviado_em'         => now(),
            'lembretes_enviados' => 0,
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
                'enviado_em'    => $this->enviado_em?->toIso8601String(),
                'expira_em'     => $this->expira_em?->toIso8601String(),
                'convidado_por' => $this->convidado_por_id,
                'reenvio'       => $this->wasRecentlyCreated === false,
            ],
        );

        return $token;
    }

    /**
     * Manda um lembrete com um SEGUNDO link, sem tocar no primeiro.
     *
     * É a diferença entre lembrete e reenvio, e ela é a feature inteira: `enviar()`
     * rotaciona o token e renova o prazo, matando o link que a pessoa já tem na caixa de
     * entrada; um lembrete que fizesse isso e caísse no spam teria REVOGADO o único link
     * válido. Aqui `token` e `expira_em` não são tocados.
     *
     * O token novo em claro existe nesta variável local, no e-mail e em lugar nenhum mais
     * — a mesma regra do token do envio. Ver ADR-01.
     */
    public function lembrar(): void
    {
        $token = Str::random(64);

        /*
         * Grava ANTES de notificar, por duas razões independentes: o hash precisa estar no
         * banco antes de o link existir numa caixa de entrada, senão o e-mail sai com um
         * token que `valido()` não encontra; e um endereço permanentemente quebrado não
         * pode fazer o cron tentar o mesmo convite todo dia para sempre — o contador sobe e
         * o convite acaba saindo do lote.
         */
        $this->forceFill([
            'token_lembrete'     => hash('sha256', $token),
            'lembretes_enviados' => $this->lembretes_enviados + 1,
        ])->save();

        Notification::route('mail', $this->email)->notify(new ConviteDeAcesso($this, $token, lembrete: true));

        Log::channel('autenticacao')->info(
            "[Convite@lembrar] Lembrete de convite enviado | convite: {$this->id} - email: ".Str::mask($this->email, '*', 3),
            [
                'convite_id'         => $this->id,
                'email'              => Str::mask($this->email, '*', 3),
                'role_id'            => $this->role_id,
                'tenant_id'          => $this->tenant_id,
                'enviado_em'         => $this->enviado_em?->toIso8601String(),
                'expira_em'          => $this->expira_em?->toIso8601String(),
                'lembretes_enviados' => $this->lembretes_enviados,
            ],
        );
    }

    /**
     * Os endereços de um texto pastado, normalizados e sem repetição.
     *
     * Separadores: qualquer espaço em branco (inclusive quebra de linha e tab), vírgula e
     * ponto-e-vírgula — porque o texto real vem de uma coluna de planilha, de um campo
     * "Para:" ou de alguém digitando. Normalizar em minúsculas é o que torna o
     * pré-carregamento do lote comparável, e é a mesma normalização que `exigirDono()` usa
     * no aceite.
     *
     * Endereço repetido dentro do próprio texto NÃO é falha: é o mesmo endereço colado duas
     * vezes, e ninguém precisa ser avisado.
     *
     * @return Collection<int, string>
     */
    public static function separarEmails(?string $texto): Collection
    {
        return collect(preg_split('/[\s,;]+/', (string) $texto, flags: PREG_SPLIT_NO_EMPTY) ?: [])
            // `Str::of(...)->lower()` em vez de `mb_strtolower(trim(...))`, que é o mesmo
            // resultado: o segundo tipa como `lowercase-string`, e `Collection` é INVARIANTE
            // no PHPStan — `Collection<int, lowercase-string>` não satisfaz
            // `Collection<int, string>` e o nível 6 reprova a assinatura pública.
            ->map(fn (string $email): string => Str::of($email)->trim()->lower()->toString())
            ->unique()
            ->values();
    }

    /**
     * Convida vários endereços com o MESMO papel e a MESMA organização, e devolve o que saiu
     * e o que não saiu.
     *
     * O lote NÃO aborta por causa de um endereço: é a razão de a feature existir. Cada e-mail
     * é sua própria unidade — sem transação envolvendo o conjunto, porque transação faria
     * tudo-ou-nada, que é a decisão oposta.
     *
     * O que conta como falha está em ADR-03 da wiki convite-em-massa. **"Já tem conta" NÃO
     * conta**: o convite para quem já é usuário é uma oferta de acesso legítima, e sai como
     * qualquer outro.
     *
     * O limite de tamanho do lote não vive aqui: ele protege o REQUEST, não o dado, e mora na
     * ação do Filament (ADR-04). Um job futuro tem o direito de convidar mil endereços.
     *
     * @param  Collection<int, string>  $emails  já normalizados por `separarEmails()`
     * @return array{enviados: list<string>, falhas: list<array{email: string, motivo: string}>}
     */
    public static function convidarEmMassa(
        Collection $emails,
        int $roleId,
        ?int $tenantId,
        ?int $convidadoPorId,
    ): array {
        /*
         * O formato se decide ANTES do laço, e reprovar um endereço não reprova o lote. A
         * regra é a MESMA `email` do Laravel que o campo do convite individual usa
         * (`ConviteForm.php:32`): o lote não pode aceitar endereço que o formulário
         * individual recusaria, nem o contrário. `filter_var()` seria mais curto e
         * divergiria em casos de borda.
         *
         * ponytail: um Validator por endereço, com N ≤ 100. Um Validator só, com regra
         * `emails.*`, exigiria mapear `emails.3` de volta ao índice.
         */
        [$validos, $tortos] = $emails->partition(
            fn (string $email): bool => Validator::make(['email' => $email], ['email' => ['email']])->passes(),
        );

        // A normalização de `separarEmails()` aplicada ao que vem DO BANCO: a entrada já
        // chegou minúscula, os registros não necessariamente.
        $normalizar = fn (string $email): string => mb_strtolower(trim($email));

        /*
         * Convites que já existem para estes endereços NESTA organização. Uma query, e as
         * mesmas condições de `valido()` — pendente é pendente nos dois lugares, senão o
         * lote cria duplicata do que a tela mostra como pendente.
         *
         * ponytail: o `whereIn` compara a coluna crua contra endereços já minúsculos.
         * Registro com caixa mista escapa do filtro, e a consequência é um convite pendente
         * a mais — nunca conta duplicada, porque `users.email` é único e o aceite é
         * idempotente. Se virar problema real, normalize na ESCRITA: `lower(email)` no
         * `whereIn` derruba o índice.
         */
        $existentes = static::query()
            ->whereIn('email', $validos->all())
            ->when(
                $tenantId === null,
                fn (Builder $q): Builder => $q->whereNull('tenant_id'),
                fn (Builder $q): Builder => $q->where('tenant_id', $tenantId),
            )
            ->get(['email', 'aceito_em', 'recusado_em', 'expira_em']);

        $pendentes = $existentes
            ->filter(fn (self $c): bool => $c->aceito_em === null
                && $c->recusado_em === null
                && ($c->expira_em?->isFuture() ?? false))
            ->pluck('email')
            ->map($normalizar);

        $recusaram = $existentes
            ->filter(fn (self $c): bool => $c->recusado_em !== null)
            ->pluck('email')
            ->map($normalizar);

        // Quem JÁ é membro desta organização. Sem organização a pergunta não existe: "já tem
        // conta" não é falha.
        $membros = $tenantId === null
            ? collect()
            : User::query()
                ->whereIn('email', $validos->all())
                ->whereHas('tenants', fn (Builder $q): Builder => $q->whereKey($tenantId))
                ->pluck('email')
                ->map($normalizar);

        $enviados = [];
        $falhas   = $tortos->map(fn (string $email): array => [
            'email'  => $email,
            'motivo' => 'formato_invalido',
        ])->values()->all();

        foreach ($validos as $email) {
            $motivo = match (true) {
                $pendentes->contains($email) => 'convite_pendente',
                $recusaram->contains($email) => 'recusou_antes',
                $membros->contains($email)   => 'ja_e_membro',
                default                      => null,
            };

            if ($motivo !== null) {
                $falhas[] = ['email' => $email, 'motivo' => $motivo];

                continue;
            }

            try {
                $convite = static::create([
                    'email'            => $email,
                    'role_id'          => $roleId,
                    'tenant_id'        => $tenantId,
                    'convidado_por_id' => $convidadoPorId,
                ]);

                // O retorno é o token EM CLARO e morre nesta linha — como na ação de
                // reenvio. Nunca entra em variável, resultado ou log.
                $convite->enviar();

                $enviados[] = $email;
            } catch (Throwable $e) {
                /*
                 * `Throwable`, e não uma exceção específica: é EXATAMENTE aqui que o
                 * `inviteMany()` do laravel-invite-only quebra. Ele captura só
                 * `InvalidArgumentException`, então um duplicado não-pendente estoura
                 * `QueryException` crua e derruba o lote inteiro. Falha de driver de e-mail,
                 * de fila ou de banco é motivo para o ENDEREÇO falhar, nunca para os outros
                 * 39 não serem convidados. Nada é engolido: o warning leva a exception
                 * inteira, com stack trace.
                 */
                Log::channel('autenticacao')->warning(
                    '[Convite@convidarEmMassa] Falha no envio de um endereço do lote, seguindo | email: '.Str::mask($email, '*', 3),
                    [
                        'email'     => Str::mask($email, '*', 3),
                        'role_id'   => $roleId,
                        'tenant_id' => $tenantId,
                        'motivo'    => 'erro_no_envio',
                        'exception' => $e,
                    ],
                );

                $falhas[] = ['email' => $email, 'motivo' => 'erro_no_envio'];
            }
        }

        /*
         * Um resumo por lote, não um log por e-mail: cada envio já loga `[Convite@enviar]`.
         *
         * Sem chave `total`: `recebidos` é o que entrou, e `enviados + falhas` é o que saiu.
         * Um total calculado seria a versão nova do `BulkInvitationResult::count()` do
         * invite-only, que conta só os sucessos.
         */
        Log::channel('autenticacao')->info(
            '[Convite@convidarEmMassa] Lote de convites processado | enviados: '.count($enviados).' - falhas: '.count($falhas),
            [
                'recebidos' => $emails->count(),
                'enviados'  => count($enviados),
                'falhas'    => count($falhas),
                'motivos'   => collect($falhas)->countBy('motivo')->all(),
                // Mascarados: a lista de falhas é onde o descuido é mais provável, porque ela
                // é o produto do método. O resultado devolvido ao chamador vai em claro — ele
                // é exibido para quem operou, e quem operou digitou os endereços.
                'com_falha' => collect($falhas)
                    ->map(fn (array $f): array => ['email' => Str::mask($f['email'], '*', 3), 'motivo' => $f['motivo']])
                    ->all(),
                'role_id'       => $roleId,
                'tenant_id'     => $tenantId,
                'convidado_por' => $convidadoPorId,
            ],
        );

        // `array_values()` em `falhas`: o array nasce de um `->values()->all()` de Collection
        // (que o analisador só sabe ser `array<int, …>`) e depois cresce por `[]=`. O contrato
        // publicado é `list`, e `notificarResultadoDoLote()` itera por posição.
        return ['enviados' => $enviados, 'falhas' => array_values($falhas)];
    }

    /**
     * O convite utilizável por este token, ou null.
     *
     * Um método só para os motivos de recusa (inexistente, expirado, já aceito, recusado)
     * porque o chamador não deve poder distingui-los: a tela responde igual em todos.
     * Devolver o motivo faria alguém exibi-lo "para ajudar", e "este convite já foi usado"
     * confirma que o convite existiu. Ver ADR-02.
     *
     * DOIS tokens abrem o mesmo convite: o do envio (`token`) e o do último lembrete
     * (`token_lembrete`). O chamador não sabe (nem precisa saber) qual foi usado — a
     * autorização é a mesma nos dois casos, e é por isso que não existe um segundo método.
     * Ver ADR-01 de `wikis/specs/main/lembretes-de-convite/`.
     *
     * O `where(closure)` em volta do par NÃO é estilo. Sem ele o SQL sai como
     *
     *     WHERE token = ? OR token_lembrete = ? AND aceito_em IS NULL AND ...
     *
     * e o `OR` parte o WHERE inteiro: cada token passa a valer SOZINHO, sem prazo e sem
     * estado — um convite expirado (ou já aceito, ou recusado) volta a ser aceitável. Nada
     * acusa; a tela simplesmente aceita. Visto vermelho antes de o closure entrar, e CT-04
     * existe só para isso.
     */
    public static function valido(?string $token): ?self
    {
        if (blank($token)) {
            return null;
        }

        $hash = hash('sha256', (string) $token);

        return static::query()
            // SÓ o par de tokens entra no agrupamento.
            ->where(fn (Builder $consulta) => $consulta
                ->where('token', $hash)
                ->orWhere('token_lembrete', $hash))
            // Os TRÊS filtros de estado ficam de fora, em AND.
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
     * ## Por que ignorar o escopo de tenancy do painel
     *
     * O `Panel::boot()` do Filament registra um escopo global NO MODEL de todo resource
     * escopado por tenant (`Panel.php:85-90`, nome em `getTenancyScopeName()`). Como existe
     * um `ConviteResource` no painel /app, TODA query de Convite dentro de um request de
     * `/app/{tenant}` nasce filtrada pela organização corrente — inclusive esta.
     *
     * O efeito era um beco sem saída, encontrado em teste manual: quem já tem conta e é
     * convidado para OUTRA organização entrava, e o convite não aparecia em lugar nenhum. A
     * tela de aceite promete "o convite aparece no menu do seu usuário" e o menu contava
     * zero — porque a oferta pertence à organização de destino, que não é a corrente.
     *
     * Esta pergunta é, por definição, entre organizações: "o que endereçaram a esta pessoa,
     * em qualquer lugar". Removemos só o escopo do painel, e não `withoutGlobalScopes()`,
     * para não derrubar de carona um escopo futuro que seja legítimo aqui.
     *
     * @return Builder<static>
     */
    public static function pendentesPara(?User $user): Builder
    {
        $query = static::query()
            ->withoutGlobalScope(Filament::getPanel('app')->getTenancyScopeName())
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
     * Os dois recortes da listagem — uma definição para as abas dos dois painéis e para o
     * filtro do /admin.
     *
     * Ficam no MODEL, e não na tabela do /admin, pelo mesmo motivo que `situacao()` mora
     * aqui: a tela do /app tem tabela própria (`App\Resources\Convites\ConviteResource`) e
     * não consome a `ConvitesTable`. Recorte escrito nos dois lugares é exatamente como as
     * duas telas já divergiram uma vez. Desvio declarado do plano da wiki
     * abas-nas-listagens, que previa métodos em `ConvitesTable`.
     *
     * "Pendente" aqui é o oposto de "aceito", como o `TernaryFilter` sempre foi — recusado
     * e expirado entram em "Pendentes". Quem separa os três estados é `situacao()`, na
     * coluna.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function recorteDePendentes(Builder $query): Builder
    {
        return $query->whereNull('aceito_em');
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function recorteDeAceitos(Builder $query): Builder
    {
        return $query->whereNotNull('aceito_em');
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
        $user->forceFill(['email_verified_at' => now(), 'origem' => User::ORIGEM_CONVITE])->save();

        if ($this->tenant_id !== null) {
            $user->tenants()->syncWithoutDetaching([$this->tenant_id]);
        }

        $this->atribuirPapel($user);

        // O uso único: `Convite::valido()` já não devolve este convite. Convite consumido
        // fecha as DUAS portas — o link do último lembrete morre junto.
        $this->forceFill(['aceito_em' => now(), 'token_lembrete' => null])->save();

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
            // `token_lembrete` junto: convite consumido não deixa link de lembrete vivo.
            ->update(['aceito_em' => now(), 'token_lembrete' => null]);

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
            // `token_lembrete` junto: quem disse não também não deixa link vivo atrás.
            ->update(['recusado_em' => now(), 'token_lembrete' => null]);

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
     *
     * O mecanismo (fixar, restaurar no `finally`, limpar o cache da relação nas duas pontas)
     * mora em `App\Support\ContextoDePapeis` desde que ele passou a ter QUATRO cópias no
     * projeto — aqui, no gerenciador de usuários da organização, no seeder da demo e no
     * registro aberto. O que fica aqui é a única coisa que é decisão do convite: **qual**
     * contexto.
     */
    private function atribuirPapel(User $user): void
    {
        ContextoDePapeis::em($this->contextoDoPapel(), $user, function () use ($user): void {
            // assignRole(), NUNCA sync() na relação: o sync escreve só as colunas da chave
            // e estoura `NOT NULL constraint failed: model_has_roles.team_id`.
            $user->assignRole($this->papel);
        });
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
