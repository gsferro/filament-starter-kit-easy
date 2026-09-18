<?php

namespace App\Traits;

use OwenIt\Auditing\Auditable;

/**
 * Auditoria (owen-it/laravel-auditing) restrita ao que é editável.
 *
 * Auditar exatamente o $fillable evita vazar para a trilha colunas técnicas
 * (tokens, contadores, caches) e mantém uma regra única: o que o usuário pode
 * alterar é o que fica registrado. A trilha aparece no painel infra (/infra/audits).
 */
trait AuditsFillables
{
    use Auditable;

    /**
     * @return array<int, string>
     */
    public function getAuditInclude(): array
    {
        return [...$this->getFillable(), ...$this->auditaAlemDoFillable()];
    }

    /**
     * Colunas auditáveis que NÃO são `$fillable`.
     *
     * A regra do bloco acima continua a mesma — *o que o usuário pode alterar é o que fica
     * registrado*. O que mudou foi o proxy: `getFillable()` sozinho não alcança coluna que só uma
     * Action altera, e a atribuição em massa é justamente o que ela não pode permitir.
     *
     * O caso que obrigou este ponto de extensão: `users.ativo`. Ela fica em `$attributes`
     * (`app/Models/User.php:106-108`) e nunca em `$fillable`, porque `User::create($request->all())`
     * com `ativo` fillable deixaria qualquer formulário destrancar uma conta. Só `desativar()` e
     * `reativar()` a escrevem, com `forceFill(...)->save()` (`User.php:291`, `:310`) — o evento
     * `updated` dispara, o auditor observa, e o atributo era **descartado por este filtro**.
     * Resultado: desativar uma conta não aparecia em `/infra/audits`. A trilha registrava a
     * edição do nome e não registrava o corte de acesso, que é o evento que importa.
     *
     * Model que não sobrescreve continua auditando exatamente o `$fillable`, como antes.
     *
     * @return list<string>
     */
    protected function auditaAlemDoFillable(): array
    {
        return [];
    }
}
