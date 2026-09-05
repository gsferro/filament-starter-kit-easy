<?php

use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\Admin\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Admin\Resources\Tenants\Widgets\AcessosPorPainel;
use App\Filament\Admin\Resources\Tenants\Widgets\AtualizacoesDasOrganizacoes;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacaoStats;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacaoUltimosAcessos;
use App\Filament\Admin\Resources\Tenants\Widgets\OrganizacoesStats;
use App\Filament\Admin\Resources\Tenants\Widgets\UsuariosUnicosPorOrganizacao;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/**
 * Os seis widgets de insight das organizações, no painel admin.
 *
 * Estes casos vivem em `tests/Tenancy` porque `TenantResource::canAccess()` exige
 * `config('kit.tenancy.enabled')`, que só é `true` em `Tests\TenancyTestCase`. Em `tests/Kit`
 * TODO cenário de widget passaria por "ninguém vê", medindo o kill-switch em vez da regra — a
 * mesma armadilha que `.ai/rules/testes.md` documenta para o papel `admin_app`.
 *
 * O oráculo é sempre o objeto (`BreakdownItem`, `TimelineEvent`, `Stat`), nunca o HTML: um
 * `assertSee('3')` casaria com qualquer 3 da página, inclusive de outro widget.
 *
 * Ver `wikis/specs/main/insights-das-organizacoes/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Nome da tabela de log, que o pacote permite renomear. */
function tabelaDeLog(): string
{
    return (string) config('authentication-log.table_name', 'authentication_log');
}

/**
 * Um acesso gravado com o painel que o cenário pede — inclusive NULO.
 *
 * Sempre por `DB::table()->insert()`, e isso é requisito, não atalho: o hook `creating` de
 * `KitServiceProvider::registrarPainelNoLogDeAcesso()` carimba o painel corrente sempre que o
 * atributo está vazio, então um `save()` com `painel = null` NÃO produz linha com painel nulo —
 * produz linha com o painel que estiver corrente no teste.
 *
 * `insert` direto não dispara evento de Eloquent. É a consequência que ADR-01 registra como custo
 * aceito do hook de model, e aqui ela é justamente o que torna CT-07 expressável.
 */
function acessoDe(User $usuario, ?string $painel, DateTimeInterface $quando, bool $sucesso = true): void
{
    DB::table(tabelaDeLog())->insert([
        'authenticatable_type' => $usuario->getMorphClass(),
        'authenticatable_id'   => $usuario->getKey(),
        'login_at'             => $quando,
        'login_successful'     => $sucesso,
        'ip_address'           => '203.0.113.7',
        'painel'               => $painel,
    ]);
}

/**
 * Os itens de um `BreakdownWidget`, como mapa rótulo => valor.
 *
 * `getItems()` é `protected`, então o acesso é por closure vinculada ao componente montado — o
 * mesmo padrão de `tests/Kit/StatDeLoginsDoDiaTest.php`.
 *
 * @return array<string, int>
 */
function fatiasDoBreakdown(string $widget, array $parametros = []): array
{
    $componente = Livewire::test($widget, $parametros)->instance();

    $itens = (fn (): array => $this->getItems())->call($componente);

    $fatias = [];

    foreach ($itens as $item) {
        $fatias[$item->getLabel()] = (int) $item->getValue();
    }

    return $fatias;
}

/*
|--------------------------------------------------------------------------
| R4 — acessos por painel, com os sem painel visíveis
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — cada painel aparece com a sua própria contagem.
 *
 * Contagens diferentes entre si de propósito (3, 2, 1): com valores iguais, um mutante que
 * somasse tudo numa fatia só, ou que devolvesse a contagem da tabela inteira por grupo, poderia
 * acertar por acidente.
 *
 * A tentativa FALHA no `admin` é o que mata o filtro ausente de `login_successful`, que daria 4.
 */
it('[CT-06] soma os acessos de cada painel separadamente', function (): void {
    $usuario = usuarioComPapel('admin', null, 'medidor@example.com');

    foreach (range(1, 3) as $i) {
        acessoDe($usuario, 'admin', now()->subDays($i));
    }
    foreach (range(1, 2) as $i) {
        acessoDe($usuario, 'app', now()->subDays($i));
    }
    acessoDe($usuario, 'infra', now()->subDay());
    acessoDe($usuario, 'admin', now()->subDay(), sucesso: false);

    expect(fatiasDoBreakdown(AcessosPorPainel::class))
        ->toBe(['admin' => 3, 'app' => 2, 'infra' => 1]);
})->group('kit');

/**
 * CT-07 — os acessos anteriores ao carimbo continuam somando.
 *
 * O cenário que impede o widget de mentir. Um `whereNotNull('painel')` deixaria o gráfico bonito
 * e a soma das fatias divergiria do total real de acessos, sem nada na tela dizendo por quê.
 */
it('[CT-07] mantém os acessos sem painel numa fatia própria', function (): void {
    $usuario = usuarioComPapel('admin', null, 'medidor@example.com');

    foreach (range(1, 4) as $i) {
        acessoDe($usuario, null, now()->subDays($i));
    }
    foreach (range(1, 3) as $i) {
        acessoDe($usuario, 'admin', now()->subDays($i));
    }

    $fatias = fatiasDoBreakdown(AcessosPorPainel::class);

    expect($fatias)->toHaveKey('Antes do registro por painel')
        ->and($fatias['Antes do registro por painel'])->toBe(4)
        ->and(array_sum($fatias))->toBe(7);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — pessoas distintas por organização
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — pessoas distintas, e não acessos.
 *
 * Fixture desenhada para que cada implementação errada produza um número DIFERENTE:
 * Ana pertence às duas organizações e entrou 3 vezes, Bruno só à Acme e entrou 1 vez, Célia
 * pertence à Acme e nunca entrou. Esperado: Acme 2, Globex 1.
 *
 * Ana nas duas é o valor que separa as duas leituras de "exclusivo" que o `00-requisito.md`
 * registra: pela leitura escolhida ela conta nas duas; pela recusada, em nenhuma.
 */
it('[CT-08] conta pessoas distintas, não acessos', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $ana = usuarioComPapel('admin', null, 'ana@example.com');
    $ana->tenants()->attach([$acme->getKey(), $globex->getKey()]);
    foreach (range(1, 3) as $i) {
        acessoDe($ana, 'app', now()->subDays($i));
    }

    $bruno = usuarioComPapel('admin', null, 'bruno@example.com');
    $bruno->tenants()->attach($acme->getKey());
    acessoDe($bruno, 'app', now()->subDay());

    $celia = usuarioComPapel('admin', null, 'celia@example.com');
    $celia->tenants()->attach($acme->getKey());

    expect(fatiasDoBreakdown(UsuariosUnicosPorOrganizacao::class))
        ->toBe(['Acme' => 2, 'Globex' => 1]);
})->group('kit');

/**
 * CT-09 — tentativa falha e pessoa excluída não contam.
 *
 * Davi só falhou; Elena entrou com sucesso e foi excluída. A Globex continua no breakdown com
 * zero, porque o requisito pede a métrica de cada organização e ausência de uso também é dado.
 */
it('[CT-09] não conta tentativa falha nem pessoa excluída', function (): void {
    $globex = tenant('Globex', 'globex');

    $davi = usuarioComPapel('admin', null, 'davi@example.com');
    $davi->tenants()->attach($globex->getKey());
    acessoDe($davi, 'app', now()->subDay(), sucesso: false);

    $elena = usuarioComPapel('admin', null, 'elena@example.com');
    $elena->tenants()->attach($globex->getKey());
    acessoDe($elena, 'app', now()->subDay());
    $elena->delete();

    expect(fatiasDoBreakdown(UsuariosUnicosPorOrganizacao::class))
        ->toHaveKey('Globex', 0);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — a borda da janela de 30 dias
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — a borda da janela.
 *
 * `@premissa`: a janela de 30 dias não está no requisito, veio do plano. O número aparece
 * literalmente aqui — injetá-lo por config deixaria o único valor da decisão sem teste.
 *
 * BVA com granularidade de 1 segundo, porque `login_at` é `datetime`.
 */
it('[CT-10] inclui a borda de 30 dias e exclui um segundo além', function (string $quando, int $esperado): void {
    Carbon::setTestNow(Carbon::parse('2026-09-04 12:00:00'));

    $acme = tenant('Acme', 'acme');

    $pessoa = usuarioComPapel('admin', null, 'pessoa@example.com');
    $pessoa->tenants()->attach($acme->getKey());

    acessoDe($pessoa, 'app', match ($quando) {
        'há 29 dias'                => now()->subDays(29),
        'há 30 dias menos 1 s'      => now()->subDays(30)->addSecond(),
        'há exatamente 30 dias'     => now()->subDays(30),
        'há 30 dias e 1 s'          => now()->subDays(30)->subSecond(),
    });

    $fatias = fatiasDoBreakdown(UsuariosUnicosPorOrganizacao::class);

    expect($fatias['Acme'] ?? 0)->toBe($esperado);
})->with([
    'dentro'   => ['há 29 dias', 1],
    'borda−1s' => ['há 30 dias menos 1 s', 1],
    'borda'    => ['há exatamente 30 dias', 1],
    'borda+1s' => ['há 30 dias e 1 s', 0],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — a timeline de atualizações
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — a alteração mais recente aparece antes da mais antiga, e só as de organização.
 *
 * A terceira asserção é o que mata o filtro de `auditable_type` ausente: sem ele a timeline
 * misturaria a auditoria de usuários, papéis e convites na tela de organizações.
 */
it('[CT-11] lista as alterações das organizações, da mais recente para a mais antiga', function (): void {
    /*
     * `audit.console` é `false` de fábrica (`config/audit.php:203`), e teste roda em CONSOLE —
     * um `$acme->update(...)` aqui NÃO produz linha em `audits`. Ligar a config no teste
     * dispararia a trilha de tudo o que o seeder faz e o cenário mediria ruído.
     *
     * As linhas são escritas direto, que é o único arranjo que isola o que este caso afirma: o
     * FILTRO por `auditable_type` e a ORDEM. Nada aqui testa o mecanismo de auditoria — isso é
     * assunto de `tests/Kit/ConfiguracoesDoKitTest.php`.
     */
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $usuario = usuarioComPapel('admin', null, 'ruido@example.com');

    auditoriaDe($acme, now()->subDays(2));
    auditoriaDe($globex, now()->subHour());
    // Ruído: alteração de OUTRO model, que a timeline das organizações não deve mostrar.
    auditoriaDe($usuario, now()->subMinutes(10));

    $componente = Livewire::test(AtualizacoesDasOrganizacoes::class)->instance();
    $eventos    = (fn (): array => $this->getEvents())->call($componente);

    $titulos = array_map(fn ($evento): string => $evento->getTitle(), $eventos);

    expect($titulos)->toHaveCount(2)
        ->and($titulos[0])->toContain('Tenant #'.$globex->getKey())
        ->and($titulos[1])->toContain('Tenant #'.$acme->getKey());
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — as duas telas declaram os widgets
|--------------------------------------------------------------------------
*/

/**
 * CT-12 — a listagem declara os quatro widgets agregados.
 *
 * Este caso existe porque CT-06 a CT-11 montam os componentes DIRETO: um widget escrito e nunca
 * ligado à página passaria em todos eles.
 */
it('[CT-12] declara os quatro widgets agregados na listagem', function (): void {
    $admin = usuarioComPapel('admin', null, 'adm@example.com');
    $this->actingAs($admin);
    noPainelBootado('admin');

    $pagina  = Livewire::test(ListTenants::class)->instance();
    $widgets = (fn (): array => $this->getHeaderWidgets())->call($pagina);

    expect($widgets)->toBe([
        OrganizacoesStats::class,
        UsuariosUnicosPorOrganizacao::class,
        AcessosPorPainel::class,
        AtualizacoesDasOrganizacoes::class,
    ]);
})->group('kit');

/**
 * CT-13 — a tela do registro declara os dois widgets, e eles recebem a organização.
 *
 * A segunda asserção mata o widget declarado com a propriedade de outro nome, que o Filament não
 * injetaria — ele ficaria na tela mostrando array vazio, sem erro nenhum.
 */
it('[CT-13] declara os dois widgets do registro e injeta a organização', function (): void {
    $acme  = tenant('Acme', 'acme');
    $admin = usuarioComPapel('admin', null, 'adm@example.com');
    $admin->tenants()->attach($acme->getKey());

    $this->actingAs($admin);
    noPainelBootado('admin');

    $pagina  = Livewire::test(ViewTenant::class, ['record' => $acme->getRouteKey()])->instance();
    $widgets = (fn (): array => $this->getHeaderWidgets())->call($pagina);

    $stats = Livewire::test(OrganizacaoStats::class, ['record' => $acme])->instance();

    expect($widgets)->toBe([OrganizacaoStats::class, OrganizacaoUltimosAcessos::class])
        ->and($stats->record?->getKey())->toBe($acme->getKey());
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — a barreira e a disponibilidade da fonte
|--------------------------------------------------------------------------
*/

/**
 * CT-14 — a visibilidade segue quem alcança o cadastro de organizações.
 *
 * A lista de widgets é VARRIDA do diretório, nunca escrita à mão: escrita à mão ela não pega a
 * classe nova, que é exatamente o risco que ADR-03 aponta — estes widgets ficam fora do sweep de
 * `tests/Kit/PermissoesDeWidgetsTest.php`, porque não estão registrados em painel nenhum.
 */
it('[CT-14] só exibe os widgets para quem alcança o cadastro de organizações', function (string $persona, bool $esperado): void {
    $usuario = match ($persona) {
        'master_global'         => usuarioComPapel('master_global', null, 'master@example.com'),
        'admin'                 => usuarioComPapel('admin', null, 'adm@example.com'),
        'admin sem a permissão' => (function (): User {
            semAPermissao('admin', 'ViewAny:Tenant');

            return usuarioComPapel('admin', null, 'adm@example.com');
        })(),
        'panel_user' => usuarioComPapel('admin', tenant('Acme', 'acme'), 'user@example.com'),
        'sem papel'  => usuarioCom(null),
    };

    $this->actingAs($usuario);
    noPainelDoShield('admin');

    $visiveis = collect(widgetsDeInsight())
        ->filter(fn (string $widget): bool => $widget::canView())
        ->count();

    expect($visiveis > 0)->toBe($esperado);
})->with([
    'master_global'         => ['master_global', true],
    'admin'                 => ['admin', true],
    'admin sem a permissão' => ['admin sem a permissão', false],
    'panel_user'            => ['panel_user', false],
    'sem papel'             => ['sem papel', false],
])->group('kit');

/**
 * CT-15 — revogar a permissão nega a página inteira.
 *
 * O par comportamental de CT-14: lá o oráculo é o predicado, aqui é a resposta da tela. Sem este
 * caso, um `canView()` correto conviveria com uma página que abre.
 */
it('[CT-15] nega a listagem a quem perdeu a permissão do cadastro', function (): void {
    semAPermissao('admin', 'ViewAny:Tenant');

    $this->actingAs(usuarioComPapel('admin', null, 'adm@example.com'));
    noPainelBootado('admin');

    Livewire::test(ListTenants::class)->assertForbidden();
})->group('kit');

/**
 * CT-16 — sem a coluna do painel, só o widget dela some.
 *
 * A terceira asserção é a que importa: uma guarda ampla demais esconderia os quatro widgets da
 * listagem, e "nenhum tem gráfico" também seria satisfeito por "nenhum widget".
 */
it('[CT-16] esconde só o widget de acessos por painel quando a coluna não existe', function (): void {
    Schema::table(tabelaDeLog(), function (Blueprint $table): void {
        $table->dropIndex(['painel']);
    });

    Schema::table(tabelaDeLog(), function (Blueprint $table): void {
        $table->dropColumn('painel');
    });

    $this->actingAs(usuarioComPapel('admin', null, 'adm@example.com'));
    noPainelDoShield('admin');

    expect(AcessosPorPainel::canView())->toBeFalse()
        ->and(UsuariosUnicosPorOrganizacao::canView())->toBeTrue()
        ->and(OrganizacoesStats::canView())->toBeTrue()
        ->and(AtualizacoesDasOrganizacoes::canView())->toBeTrue();
})->group('kit');

/**
 * Uma linha de auditoria escrita direto, para um model e um instante.
 *
 * `audit.console` é `false` de fábrica e teste roda em console, então o observer do pacote não
 * grava nada. Ver o comentário de CT-11.
 */
function auditoriaDe(Model $modelo, DateTimeInterface $quando): void
{
    DB::table((string) config('audit.drivers.database.table', 'audits'))->insert([
        'user_type'      => null,
        'user_id'        => null,
        'event'          => 'updated',
        'auditable_type' => $modelo->getMorphClass(),
        'auditable_id'   => $modelo->getKey(),
        'old_values'     => json_encode(['nome' => 'antes']),
        'new_values'     => json_encode(['nome' => 'depois']),
        'url'            => 'console',
        'ip_address'     => '127.0.0.1',
        'user_agent'     => 'phpunit',
        'tags'           => null,
        'created_at'     => $quando,
        'updated_at'     => $quando,
    ]);
}

/**
 * Os widgets de insight, VARRIDOS do diretório.
 *
 * Derivada e não escrita à mão: é o que faz CT-14 pegar a classe nova. Helper de um arquivo só,
 * então fica no arquivo (`.ai/rules/testes.md`).
 *
 * @return list<class-string>
 */
function widgetsDeInsight(): array
{
    $diretorio = app_path('Filament/Admin/Resources/Tenants/Widgets');

    return collect(glob($diretorio.'/*.php') ?: [])
        ->map(fn (string $arquivo): string => 'App\\Filament\\Admin\\Resources\\Tenants\\Widgets\\'.basename($arquivo, '.php'))
        ->filter(fn (string $classe): bool => class_exists($classe))
        ->values()
        ->all();
}

/*
|--------------------------------------------------------------------------
| Lacuna fechada pelo mutation testing — CT-18 a CT-20
|--------------------------------------------------------------------------
|
| A primeira medição de `pest --mutate` deu 32,53% (168 não cobertos), e a causa não era artefato
| de texto de tela: TRÊS widgets tinham zero cenário de comportamento. `OrganizacoesStats`,
| `OrganizacaoStats` e `OrganizacaoUltimosAcessos` só apareciam em CT-12 e CT-13, que afirmam
| apenas que a página os DECLARA.
|
| Lacuna de derivação do `04`, traduzida de volta em cenário — que é o que a skill manda fazer com
| mutante sobrevivente. CT-20 é o mais importante: era o único widget de fronteira de organização
| sem nenhuma cobertura.
*/

/**
 * CT-18 — a taxa de ativação é o percentual dos vinculados que entraram.
 *
 * Dois vinculados, um deles com acesso: 50%. O valor é discriminante — com 1 de 1 daria 100% e um
 * mutante que devolvesse sempre 100 acertaria; com 1 de 2, ele erra.
 *
 * A quarta asserção mata o `join` com a pivot ausente em `usuariosVinculadosComAcesso()`: sem
 * ele, quem só acessa o `/admin` (o próprio administrador do cenário) entraria na conta e a taxa
 * passaria de 100%.
 */
it('[CT-18] calcula a taxa de ativação sobre os usuários vinculados', function (): void {
    $acme = tenant('Acme', 'acme');

    $comAcesso = usuarioComPapel('admin', null, 'comacesso@example.com');
    $comAcesso->tenants()->attach($acme->getKey());
    acessoDe($comAcesso, 'app', now()->subDay());

    $semAcesso = usuarioComPapel('admin', null, 'semacesso@example.com');
    $semAcesso->tenants()->attach($acme->getKey());

    // Ruído: alguém que acessa e NÃO tem vínculo. Não pode entrar em nenhuma das duas contas.
    $soAdmin = usuarioComPapel('admin', null, 'soadmin@example.com');
    acessoDe($soAdmin, 'admin', now()->subDay());

    $componente = Livewire::test(OrganizacoesStats::class)->instance();
    $stats      = (fn (): array => $this->getCachedStats())->call($componente);

    $valores = array_map(fn ($stat): ?int => valorDoStatDeInsight($stat), $stats);

    expect($valores[0])->toBe(1)   // organizações ativas
        ->and($valores[1])->toBe(2) // usuários vinculados
        ->and($valores[2])->toBe(1) // ativos na janela
        ->and($valores[3])->toBe(50); // taxa de ativação
})->group('kit');

/**
 * CT-19 — os números da organização aberta contam só quem pertence a ela.
 *
 * A pessoa da Globex tem acesso e NÃO pode aparecer nos números da Acme. É a fronteira de
 * organização no widget de registro, que CT-13 não exercita — lá o oráculo é só a injeção do
 * `$record`.
 */
it('[CT-19] conta nos números da organização apenas quem pertence a ela', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $daAcme = usuarioComPapel('admin', null, 'daacme@example.com');
    $daAcme->tenants()->attach($acme->getKey());
    acessoDe($daAcme, 'app', now()->subDay());

    $daGlobex = usuarioComPapel('admin', null, 'daglobex@example.com');
    $daGlobex->tenants()->attach($globex->getKey());
    acessoDe($daGlobex, 'app', now()->subDay());

    $componente = Livewire::test(OrganizacaoStats::class, ['record' => $acme])->instance();
    $stats      = (fn (): array => $this->getCachedStats())->call($componente);

    expect(valorDoStatDeInsight($stats[0]))->toBe(1)
        ->and(valorDoStatDeInsight($stats[1]))->toBe(1);
})->group('kit');

/**
 * CT-20 — a lista de últimos acessos não mostra o acesso de outra organização.
 *
 * O widget que estava com ZERO cobertura, e é de fronteira: um `whereIn` ausente exibiria, na tela
 * da Acme, quem entrou pela Globex.
 *
 * A tentativa FALHA entra na lista de propósito — uma lista só de sucessos esconde exatamente a
 * sequência que se quer flagrar.
 */
it('[CT-20] lista só os acessos de quem pertence à organização aberta', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $daAcme = usuarioComPapel('admin', null, 'daacme@example.com');
    $daAcme->tenants()->attach($acme->getKey());
    acessoDe($daAcme, 'app', now()->subDay());
    acessoDe($daAcme, 'app', now()->subHours(2), sucesso: false);

    $daGlobex = usuarioComPapel('admin', null, 'daglobex@example.com');
    $daGlobex->tenants()->attach($globex->getKey());
    acessoDe($daGlobex, 'app', now()->subMinutes(10));

    $componente = Livewire::test(OrganizacaoUltimosAcessos::class, ['record' => $acme])->instance();
    $itens      = (fn (): array => $this->getItems())->call($componente);

    $titulos = array_map(fn ($item): string => $item->getTitle(), $itens);

    expect($titulos)->toHaveCount(2)
        ->and($titulos)->each->toBe($daAcme->name);
})->group('kit');

/**
 * O NÚMERO de um `StatPlus`, e não o markup dele.
 *
 * `StatPlus` estende `OdometerStat`: `getValue()` devolve `<number-flow data-value="3">0</…>`, com
 * o corpo em `0` porque o odômetro anima no navegador. Só o atributo carrega o número. Mesmo
 * helper de `tests/Kit/StatDeLoginsDoDiaTest.php`, e o nome difere para não colidir — função em
 * PHP é global no processo (`.ai/rules/testes.md`).
 */
function valorDoStatDeInsight(mixed $stat): ?int
{
    return preg_match('/data-value="(\d+)"/', (string) $stat->getValue(), $achado) === 1
        ? (int) $achado[1]
        : null;
}
