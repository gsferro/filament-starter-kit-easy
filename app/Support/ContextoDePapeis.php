<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roda um callback com o contexto de papéis do spatie fixado — e o devolve ao lugar depois.
 *
 * ## Por que isto existe
 *
 * `model_has_roles.team_id` é NOT NULL com `permission.teams` ligado, e quem o preenche é o
 * `PermissionRegistrar`, não o `assignRole()`. Errar o contexto não dá erro: cria um usuário que
 * autentica e leva 403, ou um papel que fica invisível dentro do `/app` porque o `wherePivot` do
 * spatie filtra por um team diferente. É a decisão mais fácil de errar de toda a área de acesso
 * do kit, e a que menos avisa quando erra.
 *
 * Antes desta classe o padrão existia **quatro** vezes, copiado: `Convite::atribuirPapel()`,
 * `UsersRelationManager::noContextoDe()`, `DemoTenancySeeder::papelDoApp()` e o registro aberto.
 * Quatro cópias de um guard que ninguém percebe quando divergem — e a divergência não quebra
 * teste nenhum dos quatro, porque cada um testa o seu.
 *
 * ## As duas coisas que não podem sair daqui
 *
 * **O `finally`.** Este código roda dentro do request de outra pessoa: deixar o registrar sujo
 * contamina tudo o que vem depois no mesmo request — a listagem seguinte, o menu, o badge.
 *
 * **O `unsetRelation('roles')` nas DUAS pontas.** O Eloquent cacheia `roles` na instância, e o
 * cache do contexto anterior contamina tanto a leitura quanto a escrita. Sem o de entrada, um
 * `syncRoles()` opera sobre a lista do contexto errado; sem o de saída, quem ler `$usuario->roles`
 * depois recebe a lista do contexto interno.
 *
 * ## Um caminho para os dois modos
 *
 * Não há guard de `config('permission.teams')`, e a ausência é deliberada: com teams desligado o
 * spatie simplesmente ignora o team fixado. Um `if` aqui criaria dois caminhos para testar sem
 * mudar resultado nenhum — foi o que `Convite::atribuirPapel()` já havia concluído, e está
 * medido pelas duas suítes do convite (`tests/Kit` single-tenant e `tests/Tenancy` com teams).
 */
class ContextoDePapeis
{
    /**
     * @template T
     *
     * @param  int  $contexto  o `team_id` a fixar — `Tenant::CONTEXTO_GLOBAL` para papel de
     *                         painel sem tenancy (`/admin`, `/infra`)
     * @param  callable(): T  $callback
     * @return T
     */
    public static function em(int $contexto, User $usuario, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $anterior  = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId($contexto);
            $usuario->unsetRelation('roles');

            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($anterior);
            $usuario->unsetRelation('roles');
        }
    }
}
