<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\TemUuid;
use Database\Factories\TenantFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Tenant — a unidade de isolamento do kit.
 *
 * ## Código em inglês, interface no idioma do negócio
 *
 * A classe, a tabela e os métodos seguem o vocabulário da API do Filament
 * (`Tenant`, `tenants`, `tenant_id`, `getTenants()`, `canAccessTenant()`), para
 * que a documentação oficial se leia sem tradução mental. O que o usuário vê
 * sai de `config('kit.tenancy.label')` — "Organização" por default, trocável
 * por Empresa, Cliente, Escola, Unidade, sem tocar em código.
 *
 * O `HasCurrentTenantLabel` é o gancho oficial do Filament para isso: é o
 * rótulo que aparece acima do nome do tenant no seletor do painel.
 *
 * Só entra em cena com `config('kit.tenancy.enabled')` ligado
 * (`php artisan kit:tenancy`). Aí o painel /app vira `/app/{slug}` e o Filament
 * escopa sozinho as queries dos resources que tenham a relação de posse.
 *
 * Quem pode entrar em qual tenant é decidido em `User::canAccessTenant()`, a
 * partir do pivot `tenant_user`.
 *
 * Exclusão é sempre lógica (`ativo`) — tenant desligado some do seletor sem
 * levar os dados junto.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property string $slug
 * @property bool $ativo
 * @property ?string $cor_primaria
 * @property ?string $logo
 */
class Tenant extends Model implements Auditable, HasCurrentTenantLabel, HasName
{
    /**
     * Contexto de papéis fora de qualquer tenant.
     *
     * Com `permission.teams` ligado, `model_has_roles.team_id` é NOT NULL: não
     * existe "atribuição global" no spatie. Mas o kit PRECISA de papéis
     * globais — `master_global`, `admin` e `infra` governam os painéis /admin e
     * /infra, que não têm tenant nenhum.
     *
     * A saída é este sentinela: atribuição feita em `team_id = 0` vale no
     * contexto global (painéis admin/infra, console, jobs, seeders); atribuição
     * feita com o id de um tenant vale só dentro dele, no /app.
     *
     * A DEFINIÇÃO do papel continua global (`roles.team_id` nulo, que é
     * nullable) — o que muda por tenant é quem o tem.
     */
    public const CONTEXTO_GLOBAL = 0;

    use AuditsFillables;

    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use TemUuid;

    /** `uuid` fica fora do fillable de propósito (convenção do trait TemUuid). */
    protected $fillable = [
        'nome',
        'slug',
        'ativo',
        'cor_primaria',
        'logo',
    ];

    /** Rótulo exibido acima do nome no seletor de tenant do painel. */
    public function getCurrentTenantLabel(): string
    {
        return (string) config('kit.tenancy.label', 'Organização');
    }

    /**
     * Nome exibido pelo Filament no seletor e no menu de tenant.
     *
     * OBRIGATÓRIO neste kit: sem o contrato, o `FilamentManager::getTenantName()`
     * cai em `$tenant->getAttributeValue('name')` — e a coluna aqui é `nome`,
     * pela convenção pt-BR do domínio. O retorno vira `null` e o método,
     * tipado como `string`, estoura TypeError ao montar o menu.
     */
    public function getFilamentName(): string
    {
        return $this->nome;
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    /**
     * URL pública da logo, ou `null` quando a organização não enviou uma.
     *
     * Mesma forma de `User::getFilamentAvatarUrl()`: o banco guarda o path relativo e o disk
     * resolve a URL. O disk `public` é explícito porque o default é `local`, que aponta para
     * `storage/app/private` e não é servível por URL — logo herdada do default nasceria quebrada.
     *
     * `null` e não string vazia: string vazia num `src` de `<img>` faz o navegador requisitar a
     * própria página e renderizar ícone quebrado, e `null` é o que o Auth Designer trata como
     * "sem mídia".
     */
    public function urlDaLogo(): ?string
    {
        return $this->logo
            ? Storage::disk('public')->url($this->logo)
            : null;
    }
}
