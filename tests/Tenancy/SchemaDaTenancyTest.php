<?php

use Illuminate\Support\Facades\Schema;

/**
 * O outro lado do que o `kit:install` faz ao ligar a multi-organização.
 *
 * A migration de permissões do spatie decide se cria as colunas de team lendo
 * `permission.teams` em TEMPO DE EXECUÇÃO. Ligar a chave depois do migrate
 * deixa config e schema incoerentes sem erro nenhum — e é por isso que a
 * instalação liga antes do primeiro migrate, e que o `kit:tenancy` (num projeto
 * já migrado) precisa recriar o banco.
 *
 * Aqui o `Tests\TenancyTestCase` reproduz exatamente essa ordem: fixa as chaves
 * em `createApplication()`, antes das migrations. Se a coluna sumir, quem quebra
 * é a instalação com tenancy — não só esta suíte.
 */
it('cria as tabelas de permissão com a coluna de contexto', function (): void {
    expect(Schema::hasColumn(
        config('permission.table_names.model_has_roles', 'model_has_roles'),
        config('permission.column_names.team_foreign_key', 'team_id'),
    ))->toBeTrue();
})->group('kit');
