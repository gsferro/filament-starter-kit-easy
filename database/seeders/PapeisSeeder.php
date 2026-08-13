<?php

namespace Database\Seeders;

use App\Support\Paineis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Papéis do kit — a fronteira de acesso aos painéis (User::canAccessPanel).
 *
 * Cada papel declara em QUAL PAINEL vale, na coluna `roles.painel`:
 *
 *   master_global → painel nulo; entra em tudo pelo Gate::before, não pela coluna
 *   admin         → /admin: usuários, papéis, agentes de IA
 *   infra         → /infra: health, filas, logs, auditoria, comandos
 *   panel_user    → /app: o perfil básico da operação de negócio
 *
 * A matriz de permissões de cada papel é EXATAMENTE a do painel dele, colhida por
 * `App\Support\Paineis` na mesma fonte que o `shield:generate` usa. Antes isso era
 * casamento por substring (`str_contains($p, 'User')`), que colocava um Resource futuro
 * chamado `UserPreference` no papel `admin` sem ninguém decidir.
 *
 * Idempotente: pode rodar de novo depois de criar Resources novos — e DEVE, junto com o
 * ShieldPermissionsSeeder. Ver `.ai/rules/filament.md`.
 *
 * ## Com multi-tenancy ligada (`permission.teams`)
 *
 * Os papéis continuam sendo criados SEM team (`roles.team_id` nulo) — no spatie isso
 * significa "papel global, disponível em qualquer tenant". O que passa a ser por tenant
 * é a ATRIBUIÇÃO: `$user->assignRole('admin')` grava em `model_has_roles` o team
 * corrente, fixado a cada request pelo middleware `DefinirTenantDePermissoes`.
 *
 * Efeito prático: o mesmo usuário pode ser `admin` num tenant e usuário comum em outro,
 * sem duplicar a definição do papel.
 */
class PapeisSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // master_global fica sem permissions de propósito: o acesso vem do Gate::before
        // (KitServiceProvider). Sincronizar tudo aqui só criaria uma lista que apodrece a
        // cada Resource novo. O painel é nulo pelo mesmo motivo — ele não entra pela
        // coluna, e nulo NÃO é coringa (ADR-03 da wiki perfil-e-acesso-ao-painel).
        $this->papel(config('filament-shield.super_admin.name', 'master_global'), $guard, null)
            ->syncPermissions([]);

        $this->papel('admin', $guard, 'admin')
            ->syncPermissions($this->permissoesDoPainel('admin', $guard));

        $this->papel('infra', $guard, 'infra')
            ->syncPermissions($this->permissoesDoPainel('infra', $guard));

        // panel_user deixou de ser "papel sem nada": é o perfil básico do /app, e é ele
        // que dá acesso ao painel de negócio.
        //
        // Ele nasce com TODAS as permissões do painel porque o /app do kit vem vazio de
        // propósito — o único Resource é o `Projeto` da demo. No seu projeto, este seeder
        // é o lugar da matriz de autorização: recorte o que o usuário comum pode fazer
        // (`Paineis::permissoes('app')->reject(...)`) ou crie papéis mais finos.
        $this->papel(config('filament-shield.panel_user.name', 'panel_user'), $guard, 'app')
            ->syncPermissions($this->permissoesDoPainel('app', $guard));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * As permissões do painel que EXISTEM no banco.
     *
     * A interseção não é preciosismo: `syncPermissions()` recebendo um nome que não está
     * na tabela lança `PermissionDoesNotExist` e derruba o seeder inteiro. Isso acontece
     * sempre que este seeder roda sem o `ShieldPermissionsSeeder` antes — cenário comum
     * em teste, que semeia só o que o caso precisa.
     *
     * Tolerar o banco incompleto também é o comportamento antigo: antes a lista vinha de
     * `Permission::pluck('name')` e um banco sem permissions simplesmente dava papel
     * vazio, em vez de erro.
     *
     * @return Collection<int, string>
     */
    private function permissoesDoPainel(string $painel, string $guard): Collection
    {
        return Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', Paineis::permissoes($painel))
            ->pluck('name');
    }

    /**
     * Papel com DEFINIÇÃO global (`roles.team_id` nulo) e painel declarado.
     *
     * Com `permission.teams` ligado, o `Role::findOrCreate` do spatie carimba o team
     * corrente na definição do papel, e um papel carimbado no tenant A fica invisível no
     * tenant B — não haveria como atribuir `admin` em dois tenants sem duplicar a
     * definição.
     *
     * `roles.team_id` é nullable justamente para isso: nulo = papel disponível em
     * qualquer contexto. O que varia por tenant é a ATRIBUIÇÃO
     * (`model_has_roles.team_id`, essa sim NOT NULL).
     *
     * `updateOrCreate` e não `firstOrCreate`: papel que já existe precisa receber o
     * painel, senão quem atualiza o kit fica com papéis sem painel — ou seja, sem acesso.
     */
    private function papel(string $nome, string $guard, ?string $painel): Role
    {
        $chave = ['name' => $nome, 'guard_name' => $guard];

        if (config('permission.teams')) {
            $chave[config('permission.column_names.team_foreign_key', 'team_id')] = null;
        }

        return Role::query()->updateOrCreate($chave, ['painel' => $painel]);
    }
}
