<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
     * O recorte é sobre o que se CONCEDE, nunca sobre o que a pessoa já tem.
     *
     * `$alvo` entra na alternativa porque a ficha de quem já tem papel fora do alcance
     * precisa continuar salvável: sem ele o papel nem aparece nas opções, o estado
     * carregado reprova no `in` implícito do Select ("não contém um valor válido") e um
     * `admin` deixa de conseguir editar o nome de quem tem `infra`. Pior, se passasse, o
     * `syncRoles()` de `gravarPapeis()` REVOGARIA o papel alheio a cada Salvar.
     *
     * Acrescentar continua fechado: papel fora do alcance que o alvo não tem não entra na
     * alternativa, então não vira opção nem sobrevive à escrita.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $papeis
     * @return Builder<TModel>
     */
    public static function recortarConcessao(Builder $papeis, ?User $alvo = null): Builder
    {
        if (self::operadorPodeConceder()) {
            return $papeis;
        }

        $alcance = self::paineisAoAlcance();
        $jaTem   = $alvo instanceof User
            ? $alvo->papeisEmQualquerContexto()->pluck($alvo->papeisEmQualquerContexto()->getRelated()->getQualifiedKeyName())->all()
            : [];

        return $papeis
            ->where('name', '!=', self::papel())
            ->where(fn (Builder $consulta): Builder => $consulta
                ->whereNull('painel')
                ->orWhereIn('painel', $alcance)
                ->orWhereIn('id', $jaTem));
    }

    public static function regraDeConcessao(): Exists
    {
        $regra = Rule::exists((string) config('permission.table_names.roles', 'roles'), 'id');

        if (self::operadorPodeConceder()) {
            return $regra;
        }

        $alcance = self::paineisAoAlcance();

        /*
         * A closure já chega agrupada: `DatabasePresenceVerifier::addConditions()` a envolve
         * num `where(fn ($query) => …)` (`:82-86`). Sem esse agrupamento o `orWhereIn` seria
         * alternativa de TOPO e derrubaria a trava do nome junto.
         */
        return $regra
            ->whereNot('name', self::papel())
            ->where(function (QueryBuilder $consulta) use ($alcance): void {
                $consulta->whereNull('painel')->orWhereIn('painel', $alcance);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Teto de escalada por PAINEL (F-02 da auditoria Blueprint)
    |--------------------------------------------------------------------------
    | Cunhar um papel com `painel = infra` e atribuí-lo a si mesmo era escalada em
    | três cliques para qualquer `admin`. Quem não é `master_global` concede papel
    | SEM painel, papel do painel de NEGÓCIO (o `->default()` do kit) e papel de
    | painel que ele PRÓPRIO acessa — nunca de painel que governa a instalação e ao
    | qual ele não tem acesso.
    |
    | O painel de negócio entra pelo `->default()`, e não por `Panel::hasTenancy()`:
    | em instalação single-tenant o /app não tem tenancy, e o `admin` continua
    | precisando conceder `panel_user`. Ver ADR-02 da wiki
    | travas-de-escalada-de-papeis.
    */

    /**
     * @return list<string>
     */
    private static function paineisAoAlcance(): array
    {
        $paineis  = [Filament::getDefaultPanel()->getId()];
        $operador = Auth::user();

        if ($operador instanceof User) {
            $paineis = array_merge($paineis, $operador->papeisEmQualquerContexto()->pluck('painel')->all());
        }

        return array_values(array_unique(array_filter(array_map(strval(...), $paineis))));
    }

    /*
    |--------------------------------------------------------------------------
    | O papel super-admin é guardado como REGISTRO (F-01 da auditoria Blueprint)
    |--------------------------------------------------------------------------
    | O teto de escalada casa por NOME, e a tela de papéis do /admin edita nome e
    | exclui registro: renomear `master_global` para outra coisa e depois renomear
    | o próprio papel para `master_global` promovia um `admin` em duas edições.
    |
    | `papelEditavelPor()` é consultada pela `RolePolicy`, que é o que toda Action
    | do Filament pergunta; `regraDeNomeDePapel()` fecha o nome na escrita.
    */

    public static function papelEditavelPor(Role $papel, Authenticatable $operador): bool
    {
        if (! self::ehNomeReservado((string) $papel->getAttribute('name'))) {
            return true;
        }

        return $operador instanceof User && $operador->isMasterGlobal();
    }

    /**
     * Regra de validação do campo `name` da tela de papéis.
     */
    public static function regraDeNomeDePapel(): Closure
    {
        return static function (string $atributo, mixed $valor, Closure $falha): void {
            if (self::operadorPodeConceder() || ! self::ehNomeReservado((string) $valor)) {
                return;
            }

            Log::channel('autenticacao')->warning(
                '[AdministradorDaInstalacao@regraDeNomeDePapel] Nome reservado recusado | operador: '.(Auth::id() ?? 'nenhum'),
                ['operador_id' => Auth::id(), 'atributo' => $atributo, 'valor' => (string) $valor],
            );

            $falha('Este nome é reservado ao administrador da instalação.');
        };
    }

    /**
     * Caixa e espaços das bordas normalizados: o `unique` do `name` é case-insensitive em
     * MySQL (`utf8mb4_*_ci`) e case-sensitive em SQLite. Sem normalizar, `Master_Global`
     * passa na suíte e É o papel super-admin em produção.
     */
    private static function ehNomeReservado(string $nome): bool
    {
        return Str::lower(trim($nome)) === Str::lower(self::papel());
    }
}
