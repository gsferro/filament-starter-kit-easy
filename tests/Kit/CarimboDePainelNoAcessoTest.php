<?php

use App\Filament\Admin\Resources\Tenants\Widgets\AcessosPorPainel;
use App\Filament\Admin\Resources\Tenants\Widgets\AtualizacoesDasOrganizacoes;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacaoStats;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacaoUltimosAcessos;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacoesStats;
use App\Filament\Admin\Resources\Tenants\Widgets\UsuariosUnicosPorOrganizacao;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

/**
 * De qual painel veio cada acesso — o carimbo, e a garantia de que ele não derruba o login.
 *
 * `authentication_log` (rappasoft) não sabia de painel: o registro é um `morphTo` para `User` e
 * mais nada sobre origem. A coluna `painel` e o hook `creating` de
 * `KitServiceProvider::registrarPainelNoLogDeAcesso()` é que tornam "quantos acessos cada painel
 * teve" uma pergunta que o dado responde.
 *
 * **Estes casos vivem no caminho do LOGIN**, e é por isso que o perfil de risco da wiki é
 * `completo` aqui: uma exceção no hook não produz widget errado — produz instalação em que
 * ninguém entra. CT-04 é o cenário que mede exatamente isso.
 *
 * O `Quando` é o **evento real** (`Login`/`Failed`), nunca `AuthenticationLog::create()` à mão: é
 * isso que mantém o caso vermelho se o pacote mudar o caminho de escrita num `composer update`.
 *
 * Ver `wikis/specs/main/insights-das-organizacoes/` — ADR-01.
 */

/** Nome da tabela de log, que o pacote permite renomear. */
function tabelaDeLogDeAcesso(): string
{
    return (string) config('authentication-log.table_name', 'authentication_log');
}

/*
|--------------------------------------------------------------------------
| R1 — toda linha criada pelo Eloquent nasce sabendo de qual painel veio
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — o painel corrente é gravado no registro do acesso.
 *
 * A linha `Failed` não é enfeite: ela mata o hook registrado só para o evento `Login`, que é o
 * erro mais provável de quem implementa isso lendo só o requisito.
 *
 * A terceira asserção — exatamente UM registro — mata o hook que cria uma segunda linha em vez de
 * mutar a que está nascendo.
 *
 * O `Então` relê do BANCO (`AuthenticationLog::query()`), não do objeto em memória: um
 * `setAttribute` que não persistisse passaria numa asserção sobre o objeto.
 */
it('[CT-01] grava o painel corrente no registro do acesso', function (string $painel, string $evento, bool $sucesso): void {
    $usuario = User::factory()->create();

    Filament::setCurrentPanel($painel);

    if ($evento === 'Login') {
        event(new Login('web', $usuario, false));
    } else {
        event(new Failed('web', $usuario, ['email' => $usuario->email]));
    }

    $registro = AuthenticationLog::query()->latest('login_at')->first();

    expect($registro?->getAttribute('painel'))->toBe($painel)
        ->and((bool) $registro?->getAttribute('login_successful'))->toBe($sucesso)
        ->and(AuthenticationLog::query()->count())->toBe(1);
})->with([
    'painel administrativo'    => ['admin', 'Login', true],
    'painel do negócio'        => ['app', 'Login', true],
    'painel de infraestrutura' => ['infra', 'Login', true],
    'tentativa malsucedida'    => ['admin', 'Failed', false],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — sem painel corrente, nulo; e o login nunca é interrompido
|--------------------------------------------------------------------------
*/

/**
 * CT-03 — autenticação fora de painel registra o acesso sem painel.
 *
 * A segunda asserção separa `null` de `''`: string vazia passaria numa asserção de "ausente" e
 * depois viraria uma fatia fantasma no widget de acessos por painel.
 */
it('[CT-03] grava painel nulo quando não há painel corrente', function (): void {
    $usuario = User::factory()->create();

    event(new Login('web', $usuario, false));

    $painel = AuthenticationLog::query()->latest('login_at')->first()?->getAttribute('painel');

    expect(AuthenticationLog::query()->count())->toBe(1)
        ->and($painel)->toBeNull();
})->group('kit');

/**
 * CT-04 — a coluna ausente NÃO derruba a autenticação.
 *
 * O cenário mais importante deste arquivo. Reproduz fielmente quem atualizou o código e ainda não
 * rodou `migrate`: a tabela existe, a coluna não. Um hook que fizesse `setAttribute` sem guarda
 * estouraria no `INSERT` e o login pararia de funcionar — para todo mundo, em silêncio até o
 * primeiro usuário reclamar.
 */
it('[CT-04] não derruba o login numa instalação sem a coluna do painel', function (): void {
    /*
     * Índice antes da coluna, e em dois `Schema::table()` separados: no SQLite, derrubar a coluna
     * com o índice ainda apontando para ela estoura
     * `error in index authentication_log_painel_index after drop column`. É a mesma ordem que o
     * `down()` da migration usa, e por isso mesmo — este arranjo foi o que descobriu o defeito lá.
     */
    Schema::table(tabelaDeLogDeAcesso(), function (Blueprint $table): void {
        $table->dropIndex(['painel']);
    });

    Schema::table(tabelaDeLogDeAcesso(), function (Blueprint $table): void {
        $table->dropColumn('painel');
    });

    $usuario = User::factory()->create();

    event(new Login('web', $usuario, false));

    expect(AuthenticationLog::query()->count())->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — o painel é decidido no nascimento e não muda depois
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — atualizar o registro não troca o painel gravado.
 *
 * O pacote ATUALIZA a linha existente quando reconhece restauração de sessão, em vez de criar
 * outra (`LoginListener` mexe em `last_activity_at`). Com o carimbo em `saving` em vez de
 * `creating`, um refresh de página no `/app` reescreveria para `app` um acesso que nasceu no
 * `/admin`.
 */
it('[CT-05] mantém o painel gravado quando o registro é atualizado', function (): void {
    $usuario = User::factory()->create();

    Filament::setCurrentPanel('admin');
    event(new Login('web', $usuario, false));

    $registro = AuthenticationLog::query()->latest('login_at')->first();

    Filament::setCurrentPanel('app');
    $registro->update(['last_activity_at' => now()->addMinute()]);

    expect($registro->fresh()?->getAttribute('painel'))->toBe('admin');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — sem tenancy, nenhum widget desta feature é exibido
|--------------------------------------------------------------------------
*/

/**
 * CT-17 — com a tenancy desligada, nenhum widget desta feature é visível.
 *
 * Roda em `tests/Kit`, que é a suíte single-tenant: aqui `config('kit.tenancy.enabled')` é
 * `false`, e `TenantResource::canAccess()` devolve `false` por causa do kill-switch — antes mesmo
 * de olhar permissão. É o único lugar onde este cenário é observável.
 *
 * A lista é escrita à mão porque estes widgets NÃO estão registrados em painel nenhum (é o ponto
 * de ADR-03), então não há de onde derivá-la. `tests/Tenancy/InsightsDasOrganizacoesTest.php`
 * tem a varredura do diretório, que é quem pega a classe nova.
 */
it('[CT-17] esconde todos os widgets desta feature com a tenancy desligada', function (): void {
    $this->actingAs(User::factory()->create());

    $visiveis = collect([
        OrganizacoesStats::class,
        UsuariosUnicosPorOrganizacao::class,
        AcessosPorPainel::class,
        AtualizacoesDasOrganizacoes::class,
        OrganizacaoStats::class,
        OrganizacaoUltimosAcessos::class,
    ])
        ->filter(fn (string $widget): bool => $widget::canView())
        ->values()
        ->all();

    expect($visiveis)->toBe([], 'Widgets visíveis sem tenancy: '.implode(', ', $visiveis));
})->group('kit');
