<?php

use App\Models\Projeto;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Health\Facades\Health;

/**
 * Invariantes da fundação: convenções que, se quebrarem, quebram em silêncio.
 */
it('gera uuid e usa ele como route key', function (): void {
    $user = User::create([
        'name'     => 'Teste',
        'email'    => 'uuid@example.com',
        'password' => 'password',
    ]);

    expect($user->uuid)->not->toBeEmpty()
        ->and($user->getRouteKeyName())->toBe('uuid')
        ->and($user->getRouteKey())->toBe($user->uuid);
});

it('deixa o master_global vencer qualquer gate pelo Gate::before', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);

    $master = User::where('email', 'admin@example.com')->firstOrFail();

    expect(Gate::forUser($master)->allows('ver-logs'))->toBeTrue()
        ->and(Gate::forUser($master)->allows('uma-ability-que-nao-existe'))->toBeTrue();
});

it('nega abilities de infra para quem não tem papel', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $user = User::create([
        'name'     => 'Comum',
        'email'    => 'comum@example.com',
        'password' => 'password',
    ]);

    expect(Gate::forUser($user)->allows('ver-logs'))->toBeFalse()
        ->and(Gate::forUser($user)->allows('command-center:access'))->toBeFalse();
});

/**
 * A trilha cobre o `$fillable` MAIS o que `auditaAlemDoFillable()` declarar.
 *
 * O contrato era `getAuditInclude() === getFillable()`, e ele escondia um defeito: `ativo` e
 * `aprovacao_pendente` são fronteira de ACESSO e por isso nunca podem ser `$fillable` — só
 * `desativar()`, `reativar()` e `aprovar()` as escrevem, com `forceFill(...)->save()`. O filtro as
 * descartava, e `/infra/audits` registrava a troca do nome do usuário mas **não** o corte do
 * acesso dele.
 *
 * Os dois casos abaixo são o par: um prova que a extensão alcança as duas colunas, o outro prova
 * que ela não vaza para quem não a declara.
 */
it('audita o fillable mais as colunas de fronteira de acesso do usuario', function (): void {
    $user = new User;

    expect($user->getAuditInclude())
        ->toBe([...$user->getFillable(), 'ativo', 'aprovacao_pendente'])
        ->and($user->getAuditInclude())->toContain('ativo')
        ->and($user->getAuditInclude())->toContain('aprovacao_pendente');
});

it('mantem a auditoria no fillable puro para quem nao estende a lista', function (): void {
    $projeto = new Projeto;

    expect($projeto->getAuditInclude())->toBe($projeto->getFillable());
});

it('registra os health checks do kit', function (): void {
    $checks = Health::registeredChecks();

    expect($checks)->not->toBeEmpty();
});
