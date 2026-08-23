<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Support\RetencaoDeExcecoes;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

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

/**
 * O papel `infra` abrindo as telas dele — e não só o master_global.
 *
 * A diferença é o que se prova: o master_global vence pelo `Gate::before` SEM permission
 * nenhuma no banco, então testar só com ele deixa a matriz de permissões inteira sem
 * cobertura. Passou a importar de verdade quando o `shield:generate` começou a rodar nos
 * três painéis: os Resources de `/infra` ganharam policy (`AiRunPolicy`, `AuditPolicy`,
 * `QueueMonitorPolicy`…), e tela sem policy — que antes era tela aberta — virou tela que
 * exige permission. Se a matriz do papel `infra` deixar de cobrir alguma, é aqui que
 * aparece o 403.
 */
it('abre as telas do painel infra com o papel infra', function (string $rota): void {
    $infra = User::create(['name' => 'Infra', 'email' => 'infra@example.com', 'password' => 'password']);
    $infra->assignRole('infra');

    $this->actingAs($infra)->get($rota)->assertSuccessful();
})->with([
    'auditoria'       => '/infra/audits',
    'logins'          => '/infra/authentication-logs',
    'filas'           => '/infra/queue-monitors',
    'execuções de IA' => '/infra/execucoes-ia',
]);

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
    // Telas da 0.17.0. A de exceções é a que mais precisa estar aqui: o plugin dela
    // resolve o painel corrente, e um registro errado derruba a aplicação inteira.
    'exceções'              => '/infra/exceptions',
    'e-mails enviados'      => '/infra/mail-logs',
    'lixeira'               => '/infra/recycle-bin',
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

/**
 * A tela de usuários é a única do admin que GRAVA relação, e abrir não é
 * gravar: o `Select::make('roles')` já derrubou o salvamento com 500 enquanto
 * o `GET /admin/users` seguia verde. Por isso o par — aqui em single-tenant, e
 * em `tests/Tenancy` com o `team_id` da pivot.
 */
it('salva os papéis do usuário no painel admin', function (): void {
    $alvo  = User::create([
        'name'     => 'Alvo',
        'email'    => 'alvo@example.com',
        'password' => 'password',
    ]);
    $papel = Role::findByName('admin');

    Filament::setCurrentPanel('admin');

    $this->actingAs($this->master);

    Livewire::test(EditUser::class, ['record' => $alvo->getRouteKey()])
        ->fillForm(['roles' => [$papel->getKey()]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($alvo->fresh()->hasRole('admin'))->toBeTrue();
});

/**
 * A poda de exceções com prazo zero NÃO apaga a trilha — o contrário do que fazia.
 *
 * `Exception::prunable()` faz `whereDate('created_at', '<=', $intervalo)`
 * (`vendor/bezhansalleh/filament-exceptions/src/Models/Exception.php:44`), e `whereDate` compara
 * só a data. Com `subDays(0)` o corte era HOJE, então casava com a tabela inteira, inclusive as
 * linhas do dia; `subDays(-5)` punha o corte no futuro. E o bloco `retencao` do `config/kit.php`
 * promete que zero ou negativo **desliga** a poda — as três de `routes/console.php` honram com
 * `if ($dias <= 0) return;`, esta era a quarta e fazia o oposto.
 *
 * **A primeira versão deste caso não provava nada**, e vale registrar: ela media
 * `FilamentExceptionsPlugin::get()->getModelPruneInterval()` depois de mudar a config. Só que o
 * provider registra o plugin **uma vez, no boot** — o corte medido era o do boot, com o valor do
 * `.env`, e a config nova não chegava lá. Ficava vermelho por motivo errado. A decisão saiu do
 * provider para `RetencaoDeExcecoes::corte()`, que lê a config na chamada.
 */
it('desliga a poda de excecoes com prazo zero ou negativo', function (mixed $dias): void {
    config(['kit.retencao.excecoes_em_dias' => $dias]);

    expect(RetencaoDeExcecoes::corte()->lessThan(now()->subYears(50)))->toBeTrue();
})->with([0, -5, '0', ''])->group('kit');

/**
 * E com prazo válido a poda continua ligada, no dia certo.
 *
 * A metade positiva, e ela não é decoração: sem este caso, devolver `subYears(100)` sempre
 * passaria no caso acima e desligaria a poda para todo mundo — a tabela cresceria sem teto, que
 * é exatamente o que a retenção existe para evitar.
 */
it('mantem a poda de excecoes no prazo configurado', function (): void {
    config(['kit.retencao.excecoes_em_dias' => 14]);

    expect(RetencaoDeExcecoes::corte()->toDateString())->toBe(now()->subDays(14)->toDateString());
})->group('kit');
