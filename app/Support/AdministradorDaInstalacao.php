<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Quem administra a INSTALAÇÃO — o `master_global`, não o administrador de uma organização.
 *
 * Existe porque a pergunta tem dois consumidores com necessidades diferentes, e a resposta é a
 * mesma consulta de duas etapas: o `UsuarioAdminSeeder` quer saber **se existe** (para não criar
 * um segundo) e o `kit:admin` quer **a lista** (para não adivinhar qual, quando há mais de um).
 *
 * **As duas etapas não são redundância.** O `whereHas` recorta pelo NOME do papel no banco, que é
 * barato e reduz o conjunto a quase nada. O `isMasterGlobal()` do model é quem confere o
 * **contexto**: com `permission.teams` ligado, o papel só vale no contexto global
 * (`Tenant::CONTEXTO_GLOBAL`), e um papel de mesmo nome gravado dentro de uma organização **não**
 * é administrador da instalação. Só o `whereHas` daria falso positivo no modo multi-organização;
 * só o filtro em PHP carregaria a tabela inteira.
 */
class AdministradorDaInstalacao
{
    public static function papel(): string
    {
        return (string) config('filament-shield.super_admin.name', 'master_global');
    }

    /**
     * Todos os administradores da instalação.
     *
     * Devolve **coleção**, e não `?User`, de propósito: mais de um `master_global` é estado
     * possível — alguém pode conceder o papel na tela de papéis — e um método que devolvesse "o"
     * administrador esconderia isso, fazendo o chamador agir sobre o primeiro por acidente. Quem
     * precisa decidir precisa ver quantos são.
     *
     * @return Collection<int, User>
     */
    public static function todos(): Collection
    {
        return User::whereHas(
            'papeisEmQualquerContexto',
            fn (Builder $consulta): Builder => $consulta->where('name', self::papel()),
        )
            ->get()
            ->filter(fn (User $usuario): bool => $usuario->isMasterGlobal())
            ->values();
    }

    public static function existe(): bool
    {
        return self::todos()->isNotEmpty();
    }

    /*
    |--------------------------------------------------------------------------
    | Teto de escalada do /admin (F-01 da auditoria Blueprint)
    |--------------------------------------------------------------------------
    | Só quem já é `master_global` concede `master_global`. Sem isto um `admin` se
    | promovia pela tela de usuários ou por convite. O recorte das opções é UX; a
    | trava que vale é na escrita — `regraDeConcessao()` nos convites e
    | `Admin\Resources\Users\UserResource::gravarPapeis()` no cadastro.
    */

    public static function operadorPodeConceder(): bool
    {
        $operador = Auth::user();

        return $operador instanceof User && $operador->isMasterGlobal();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $papeis
     * @return Builder<TModel>
     */
    public static function recortarConcessao(Builder $papeis): Builder
    {
        return self::operadorPodeConceder() ? $papeis : $papeis->where('name', '!=', self::papel());
    }

    public static function regraDeConcessao(): Exists
    {
        $regra = Rule::exists((string) config('permission.table_names.roles', 'roles'), 'id');

        return self::operadorPodeConceder() ? $regra : $regra->whereNot('name', self::papel());
    }
}
