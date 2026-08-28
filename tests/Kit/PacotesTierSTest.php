<?php

use App\Models\Projeto;
use App\Models\User;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Promethys\Revive\RevivePlugin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tapp\FilamentMailLog\FilamentMailLogPlugin;

/**
 * Os cinco pacotes do Tier S que rodam em produção — mídia, lixeira, exceções,
 * trilha de e-mail e seletor de idioma.
 *
 * Este arquivo não testa os pacotes: eles têm suíte própria. Testa a AMARRAÇÃO
 * deles ao kit, que é onde os defeitos moram — em qual painel cada um vive, o que a
 * matriz de permissões faz com eles, e as duas armadilhas medidas na instalação.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| A armadilha que derrubou a instalação
|--------------------------------------------------------------------------
*/

/**
 * O caso mais importante do arquivo.
 *
 * O `ExceptionResource` resolve o plugin pelo painel CORRENTE, e o filament-shield
 * percorre `Filament::getPanels()` no boot sem fixar qual é. Com o plugin registrado
 * só no /infra, a resolução caía no painel default (`app`) e estourava
 * `LogicException: Plugin [filament-exceptions] is not registered for panel [app]` em
 * TODO comando artisan — `migrate` e `inspire` inclusive. O kit inteiro parava.
 *
 * A saída foi registrar nos três, com navegação só no /infra. Este caso é o que
 * impede alguém de "limpar" os dois registros aparentemente redundantes.
 */
it('boota qualquer comando artisan com o plugin de exceções registrado nos três painéis', function (): void {
    expect(Artisan::call('inspire'))->toBe(0);
})->group('kit');

it('registra o plugin de exceções nos três painéis', function (string $painel): void {
    expect(Filament::getPanel($painel)->hasPlugin('filament-exceptions'))->toBeTrue();
})->with(['app', 'admin', 'infra'])->group('kit');

/**
 * Registrado nos três, visível em um.
 *
 * Se a navegação vazasse para o /app, um usuário de negócio veria no menu uma tela de
 * stack traces da instalação inteira.
 */
it('mostra a navegação de exceções só no /infra', function (): void {
    noPainelBootado('infra');
    expect(FilamentExceptionsPlugin::get()->shouldRegisterNavigation())->toBeTrue();

    foreach (['app', 'admin'] as $painel) {
        noPainelBootado($painel);
        expect(FilamentExceptionsPlugin::get()->shouldRegisterNavigation())
            ->toBeFalse("O plugin de exceções não pode registrar navegação no painel /{$painel}.");
    }
})->group('kit');

/**
 * A fronteira de segurança que o registro nos três painéis abriu.
 *
 * O resource passou a existir na matriz do /app, então as permissions dele nascem no
 * banco. Sem a subtração no `PapeisSeeder`, TODO `panel_user` as herdaria — e a rota
 * existe naquele painel. Seria acesso a stack trace de qualquer organização, com
 * parâmetro de request dentro.
 *
 * Ver .ai/rules/filament.md §4.
 */
it('não deixa o panel_user herdar nenhuma permission de exceção', function (): void {
    expect(Permission::where('name', 'like', '%Exception%')->exists())
        ->toBeTrue('As permissions de Exception não existem — o ShieldPermissionsSeeder não rodou.');

    $permissoes = Role::where('name', 'panel_user')->firstOrFail()
        ->permissions
        ->pluck('name')
        ->filter(fn (string $nome): bool => str_contains($nome, 'Exception'));

    expect($permissoes)->toBeEmpty(
        'O panel_user herdou permission de Exception: '.$permissoes->implode(', ')
        .'. Falta o ExceptionResource na lista de subtração do PapeisSeeder.',
    );
})->group('kit');

/*
|--------------------------------------------------------------------------
| Onde cada tela vive
|--------------------------------------------------------------------------
*/

it('registra a trilha de e-mail e a lixeira só no /infra', function (string $plugin): void {
    expect(Filament::getPanel('infra')->hasPlugin($plugin))->toBeTrue();

    foreach (['app', 'admin'] as $painel) {
        expect(Filament::getPanel($painel)->hasPlugin($plugin))
            ->toBeFalse("O plugin {$plugin} não deve estar no painel /{$painel}.");
    }
})->with([
    'trilha de e-mail' => [(new FilamentMailLogPlugin)->getId()],
    'lixeira'          => [(new RevivePlugin)->getId()],
])->group('kit');

/**
 * A lixeira lista o que foi declarado, não o que ela descobre sozinha.
 *
 * `modelsNamespace()` varreria `app/Models` e alcançaria `Role` e `Tenant` —
 * restaurar qualquer um deles tem consequência de autorização. A lista explícita é a
 * trava, do mesmo jeito que a allow-list do command-center. `User` entrou com a
 * exclusão lógica (wiki `status-e-exclusao-logica-de-usuario`, ADR-06).
 */
it('restringe a lixeira à lista explícita de models', function (): void {
    noPainelBootado('infra');

    expect(RevivePlugin::get()->getModels())->toBe([Projeto::class, User::class]);
})->group('kit');

/**
 * `Projeto` declara `SoftDeletes` — é o que dá conteúdo à Lixeira.
 *
 * A trait é verificada aqui, sem tocar no banco: `projetos.tenant_id` é NOT NULL com FK,
 * e nesta suíte não há organização para preencher. Os casos que CRIAM projeto (apagar,
 * restaurar, anexar mídia) vivem em `tests/Tenancy/PacotesTierSTenancyTest.php`.
 */
it('declara soft delete no model de demonstração', function (): void {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Projeto::class), true))
        ->toBeTrue('Projeto perdeu SoftDeletes — a Lixeira do /infra fica sem nada para restaurar.');
})->group('kit');

/*
|--------------------------------------------------------------------------
| Seletor de idioma
|--------------------------------------------------------------------------
*/

/**
 * O kit nasce com um idioma só, e aí o seletor não aparece.
 *
 * É dirigido por dado e não por flag de propósito: não existe booleano para alguém
 * esquecer ligado com uma lista de um item. Ver `config('kit.idiomas')`.
 */
it('esconde o seletor de idioma quando só há um idioma', function (): void {
    expect(config('kit.idiomas'))->toBe(['pt_BR'])
        ->and(LanguageSwitch::make()->isVisibleInsidePanels())->toBeFalse()
        ->and(LanguageSwitch::make()->isVisibleOutsidePanels())->toBeFalse();
})->group('kit');

it('mostra o seletor de idioma quando há um segundo idioma', function (): void {
    config()->set('kit.idiomas', ['pt_BR', 'en']);

    $seletor = LanguageSwitch::make();

    expect($seletor->isVisibleInsidePanels())->toBeTrue()
        ->and($seletor->getLocales())->toBe(['pt_BR', 'en']);
})->group('kit');

/**
 * O seletor também alcança as telas de login dos três painéis.
 *
 * A asserção é sobre a LISTA de rotas, e não sobre `isVisibleOutsidePanels()`: o getter
 * do pacote exige que o nome da rota CORRENTE case com a lista
 * (`HasOutsidePanel.php:55-60`), e num teste sem request não há rota corrente — ele
 * responderia `false` por ausência de contexto, não por configuração errada.
 *
 * A tela de login é justamente onde alguém que não lê português precisa trocar de
 * idioma, e ela é servida antes de existir sessão.
 */
it('oferece o seletor nas telas de login dos três painéis', function (): void {
    config()->set('kit.idiomas', ['pt_BR', 'en']);

    expect(LanguageSwitch::make()->getOutsidePanelRoutes())->toBe([
        'filament.app.auth.login',
        'filament.admin.auth.login',
        'filament.infra.auth.login',
    ]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| Retenção
|--------------------------------------------------------------------------
*/

/**
 * A retenção é de dado sensível: stack trace com parâmetro de request e corpo de
 * e-mail com o link de aceite do convite. O prazo mora no config do kit, não espalhado
 * pelos providers.
 */
it('lê a retenção das trilhas do config do kit', function (): void {
    expect(config('kit.retencao.excecoes_em_dias'))->toBe(14)
        ->and(config('kit.retencao.emails_em_dias'))->toBe(14);
})->group('kit');

/**
 * O corte que o pacote usa sai do config — e é uma DATA, não uma quantidade de dias.
 *
 * `Exception::prunable()` faz `whereDate('created_at', '<=', $intervalo)`. Passar `14`
 * ali compararia com o ano 14 e nunca podaria nada: agendamento verde, tabela crescendo.
 */
it('converte a retenção de exceções em data de corte', function (): void {
    noPainelBootado('infra');

    expect(FilamentExceptionsPlugin::get()->getModelPruneInterval()->toDateString())
        ->toBe(now()->subDays(14)->toDateString());
})->group('kit');
