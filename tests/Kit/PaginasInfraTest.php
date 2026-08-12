<?php

use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * Smoke test das telas de infra e admin: cada plugin registra rota própria e
 * uma incompatibilidade de versão costuma aparecer como 500 na primeira visita,
 * não no boot. Aqui isso vira teste vermelho em vez de descoberta em produção.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->master = User::create([
        'name'     => 'Master',
        'email'    => 'master@example.com',
        'password' => 'password',
    ]);

    $this->master->assignRole('master_global');
});

it('abre as telas do painel infra', function (string $rota): void {
    $this->actingAs($this->master)
        ->get($rota)
        ->assertSuccessful();
})->with([
    'health'                => '/infra/health-check-results',
    'backups'               => '/infra/backup-runs',
    'filas'                 => '/infra/queue-monitors',
    'auditoria'             => '/infra/audits',
    'logins'                => '/infra/authentication-logs',
    'logs'                  => '/infra/logs',
    'pulse'                 => '/infra/pulse',
    'comandos'              => '/infra/command-center/commands',
    'histórico de comandos' => '/infra/command-center/history',
    'grafo de dependências' => '/infra/dependency-graph',
    'releases do composer'  => '/infra/composer-release-packages',
    'execuções de IA'       => '/infra/execucoes-ia',
]);

it('abre as telas do painel admin', function (string $rota): void {
    $this->actingAs($this->master)
        ->get($rota)
        ->assertSuccessful();
})->with([
    'usuários'      => '/admin/users',
    'papéis'        => '/admin/shield/roles',
    'agentes de IA' => '/admin/agentes-ia',
]);
