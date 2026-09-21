<?php

namespace App\Models;

use App\Traits\AuditsFillables;
use App\Traits\TemUuid;
use Database\Factories\TenantFactory;
use Filament\Facades\Filament;
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
 * @property ?string $cor_primaria_nome
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
        // Fillable, ao contrário de `users.aprovacao_pendente`: aqui o campo É a decisão de
        // quem administra a instalação, tomada no formulário. Lá era estado de fronteira que
        // nenhum formulário deve escrever. A auditoria de `AuditsFillables` cobre esta.
        'registro_habilitado',
        'cor_primaria',
        // O nome de uma cor da paleta do Filament — a mesma lista do settings do kit. Coluna
        // separada da do hex de propósito; ver a migration `add_cor_primaria_nome`.
        'cor_primaria_nome',
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
            'ativo'               => 'boolean',
            'registro_habilitado' => 'boolean',
        ];
    }

    /**
     * O endereço do painel de negócio DESTA organização — o ponto único da feature do link.
     *
     * Aqui, ao lado de `urlDaLogo()`, por simetria: as duas são "o endereço de algo desta
     * organização", e as três telas do `TenantResource` (form, ficha e listagem) já recebem o
     * registro — não precisam de mais nada para montar o link.
     *
     * `Panel::getUrl($this)` e NÃO concatenação: o endereço é derivado do `path` do painel, do
     * `APP_URL` corrente e do `slugAttribute` declarado no `AppPanelProvider`
     * (`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:getUrl:170`). Escrever o
     * caminho à mão quebraria em silêncio em três situações que o kit já permite: o `path` do
     * painel mudar, a instalação rodar sob subdiretório, e a chave de rota do tenant deixar de
     * ser o slug. Ver ADR-01 de `wikis/specs/feat/link-painel-do-tenant/link-painel-do-tenant/`.
     *
     * Sem normalizar o slug gravado: `Str::slug()` aqui produziria um endereço que **não
     * existe** para quem gravou `ACME-Brasil` por fora do formulário, e o link nasceria
     * quebrado sem nada avisar. O link segue o que está no banco.
     *
     * `null` com a multi-tenancy DESLIGADA, e a guarda é o achado que ela registra: `getUrl()`
     * NÃO falha nesse caso. Sem `->tenant()` o painel não tem `slugAttribute`, e o gerador cai no
     * último ramo de `HasRoutes::getUrl()` — `url($path.'/'.$tenant->getRouteKey())`
     * (`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:193`) —, que é **concatenação
     * de string sem consultar `Route::has()`**. Ou seja: exatamente o que a ADR-01 recusou fazer
     * à mão, o vendor faz como último recurso. O resultado é `http://host/app/{uuid}`: uma URL
     * bem-formada que responde **404**.
     *
     * Medido em 2026-09-21 na suíte `Kit`, a única com `kit.tenancy.enabled` falso. O risco
     * declarado na ADR-03 dizia "o gerador falha"; ele não falha, entrega um link morto em
     * silêncio — que é pior, porque nenhuma asserção de status ou de exceção enxerga.
     *
     * A pergunta é feita ao PAINEL (`hasTenancy()`), não à config: quem sabe se existe rota por
     * organização é o dono da rota. Ler `config('kit.tenancy.enabled')` aqui criaria uma segunda
     * dona para a mesma pergunta, que é o que `.ai/rules/config.md` proíbe.
     *
     * `?string` porque é o retorno de `getUrl()`.
     */
    public function urlDoPainel(): ?string
    {
        $painel = Filament::getPanel('app');

        if (! $painel->hasTenancy()) {
            return null;
        }

        return $painel->getUrl($this);
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
        if (blank($this->logo) || ! Storage::disk('public')->exists($this->logo)) {
            // `exists()` e não só `blank()`: path órfão (arquivo apagado à mão, restore de banco
            // sem o storage, `migrate:fresh` com os uploads antigos) renderizaria um <img>
            // quebrado no lugar da mídia base — exatamente o oposto do que a tela promete, que é
            // DEGRADAR para o genérico quando não há logo confiável.
            return null;
        }

        // `asset()` e não `Storage::disk('public')->url()`: a URL do disk é a string congelada
        // `APP_URL . '/storage'` (config/filesystems.php:44), enquanto o `asset()` segue o host do
        // request corrente. Os dois divergem sempre que o host efetivo não é o APP_URL — domínio
        // próprio de organização, staging, `config:cache` com APP_URL velho — e aí a logo quebra
        // enquanto o resto da página carrega. (A mídia base dos painéis não usa mais `asset()`:
        // a arte padrão virou SVG gerado com o nome da aplicação, embutido como data URI.)
        return asset('storage/'.$this->logo);
    }
}
