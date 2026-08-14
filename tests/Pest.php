<?php

use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenancyTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Testes do SEU projeto
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Testes do KIT
|--------------------------------------------------------------------------
| Ficam isolados em tests/Kit para você conseguir rodar só eles — é o que
| você quer depois de um `kit:update`, para saber se a atualização quebrou
| a fundação sem esperar a suíte inteira do seu negócio:
|
|   composer test:kit
|   php artisan test --testsuite=Kit
|   php artisan test --group=kit
|
| Eles cobrem o que o kit promete: acesso aos três painéis, telas de infra
| e admin de pé, invariantes da fundação (uuid, gates, auditoria) e o
| contrato da camada de IA.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('kit')
    ->in('Kit');

/*
|--------------------------------------------------------------------------
| Testes do KIT — multi-tenancy
|--------------------------------------------------------------------------
| Suíte separada por uma razão de bootstrap, não de organização: a migration
| de permissões do spatie lê `config('permission.teams')` em tempo de execução
| para decidir se cria as colunas de team. Ligar a flag num beforeEach seria
| tarde — o RefreshDatabase já teria migrado sem elas.
|
| O Tests\TenancyTestCase fixa a config em createApplication(), que roda antes
| das migrations; e o Pest não permite dois TestCases na mesma pasta, daí o
| diretório próprio.
|
| Mesmo grupo `kit`, então continua entrando em:
|
|   composer test:kit
|   php artisan test --group=kit
*/

pest()->extend(TenancyTestCase::class)
    ->use(RefreshDatabase::class)
    ->group('kit')
    ->in('Tenancy');

/*
|--------------------------------------------------------------------------
| Testes do KIT — telas em browser real
|--------------------------------------------------------------------------
| Navegador de verdade, com JavaScript executando, sobre as telas dos três
| painéis. O que isto pega e o smoke HTTP de tests/Kit não pega: um painel
| Filament é Livewire + Alpine, então o corpo do HTML pode vir íntegro e a
| tela estar inutilizável porque um x-on:click estourou, porque um asset do
| Vite não subiu ou porque um componente registrou erro no console. Nenhuma
| dessas três falhas move o status HTTP de 200.
|
| Grupo `browser`, e NÃO `kit`, de propósito: o `composer test:kit` é o
| comando de resposta rápida depois de um kit:update, e browser em série
| custa ordens de magnitude mais que HTTP. Rode esta suíte com:
|
|   composer test:browser
|   php artisan test --testsuite=Browser
|   php artisan test --group=browser
|
| `npm run build` é pré-requisito DURO: sem o manifest do Vite toda tela
| responde ViteException e todo cenário falha por um motivo que não é o
| dele. O script test:browser já embute o build.
|
| O plugin sobe servidor HTTP próprio in-process (amphp), em porta
| aleatória — nada de Herd, `artisan serve` ou Sail. E porque é o MESMO
| processo, o `:memory:` do phpunit.xml, o RefreshDatabase e o
| `$this->actingAs()` continuam valendo dentro do navegador.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('browser')
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Test Impact Analysis (Pest 5)
|--------------------------------------------------------------------------
| Roda só os testes afetados pelo diff e replica o resto do cache — inclusive as
| linhas cobertas, então um run replayado reporta a mesma cobertura de um completo.
| Exige driver de cobertura (Xdebug ou PCOV) instalado.
|
| `defaultBranch('main')` porque o TIA precisa saber contra o que diffar, e o
| default dele é `master`. A alternativa seria `git remote set-head origin --auto`,
| uma vez por clone — esta linha vale para todo mundo de uma vez.
|
| `locally()` liga o TIA sem flag no desenvolvimento e o desliga sozinho em CI,
| que é o que a doc do Pest recomenda: o pipeline deve rodar a suíte completa.
|
| Só funciona porque nenhum helper de teste é usado de outro arquivo — o TIA carrega
| um SUBCONJUNTO dos arquivos, e helper cruzado estoura `Call to undefined function`.
| A guarda disso é tests/Kit/HelpersDeTesteTest.php.
*/

pest()->tia()->defaultBranch('main')->locally();

/*
| O plugin reexecuta cada assertion até este teto — é assim que ele espera por
| conteúdo assíncrono, sem nenhum `wait()` de segundos fixos no teste. O default
| de 5 s não alcança o primeiro boot de um painel Filament em ambiente de teste
| (sem opcache, com o Livewire compilando na primeira visita): o login pela tela
| do CT-B06 redirecionava DEPOIS do teto e falhava dizendo que ainda estava em
| `/app/login`. Teto, não espera: cenário verde não gasta esse tempo.
*/
pest()->browser()->timeout(20_000);

/*
|--------------------------------------------------------------------------
| Helpers compartilhados
|--------------------------------------------------------------------------
| Aqui, e não dentro de um arquivo de teste, porque em Pest as funções são
| globais no processo: helper declarado em dois arquivos é fatal error de
| redeclaração, e helper declarado num arquivo só desaparece quando você roda
| o OUTRO arquivo isolado (`php artisan test tests/Tenancy/Algum...Test.php`).
|
| Só entra aqui o que mais de uma suíte usa. Helper de um arquivo continua no
| arquivo.
*/

function tenant(string $nome, string $slug, bool $ativo = true): Tenant
{
    return Tenant::create(['nome' => $nome, 'slug' => $slug, 'ativo' => $ativo]);
}

function usuario(string $email = 'user@example.com'): User
{
    return User::create(['name' => 'Usuário', 'email' => $email, 'password' => 'password']);
}

/**
 * Usuário com e-mail único e papel OPCIONAL — o `null` é o ponto dela.
 *
 * A diferença para `usuarioDoKit()`, que é a vizinha mais parecida: aqui o papel pode ser
 * nulo, porque "quem não tem papel nenhum não entra em painel nenhum" é um caso que precisa
 * de persona própria; e o e-mail é gerado, porque vários casos criam mais de um usuário no
 * mesmo teste.
 */
function usuarioCom(?string $papel): User
{
    $user = User::create([
        'name'     => 'Teste',
        'email'    => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    if ($papel !== null) {
        $user->assignRole($papel);
    }

    return $user;
}

/**
 * O que o middleware do painel faria num request real.
 *
 * Teste de componente Livewire não passa por ele, e as duas chaves são indispensáveis: sem
 * `setTenant` todo caso cairia no ramo fail-closed de `getEloquentQuery()`; sem
 * `setPermissionsTeamId` o `syncRoles()` gravaria em `Tenant::CONTEXTO_GLOBAL`.
 */
function noPainelDa(Tenant $tenant): void
{
    Filament::setCurrentPanel('app');
    Filament::setTenant($tenant, isQuiet: true);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
}

/** Nome da pivot de papéis, que muda com `config('permission.table_names')`. */
function pivotDePapeis(): string
{
    return (string) config('permission.table_names.model_has_roles', 'model_has_roles');
}

/**
 * Usuário com papel atribuído no contexto corrente — a persona de quem OPERA a tela.
 *
 * Sem organização explícita, ao contrário de `usuarioComPapel()`: serve às suítes
 * single-tenant, onde não existe contexto para escolher.
 */
function usuarioDoKit(string $papel, string $email = 'user@example.com'): User
{
    $user = usuario($email);

    $user->assignRole($papel);

    return $user;
}

/** Espia só o channel `autenticacao`; os outros continuam reais. */
function espiarAutenticacao(): LoggerInterface
{
    $canal = Mockery::spy(LoggerInterface::class);

    Log::partialMock()->shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    return $canal;
}

/**
 * Usuário com papel atribuído num contexto explícito.
 *
 * Com `permission.teams` ligado, `model_has_roles.team_id` guarda o contexto e
 * `assignRole()` carimba o que estiver fixado no PermissionRegistrar. Papel do painel
 * /app pertence a uma organização; papel de /admin e /infra pertence ao contexto global.
 */
function usuarioComPapel(string $papel, ?Tenant $tenant = null, string $email = 'user@example.com'): User
{
    return papelNaOrganizacao(usuario($email), $papel, $tenant);
}

/**
 * Atribui papel a um usuário que JÁ existe, dentro do contexto de uma organização.
 *
 * É a diferença entre a persona funcionar e ela entrar num painel vazio: papel gravado em
 * `Tenant::CONTEXTO_GLOBAL` fica invisível dentro do /app, porque o `wherePivot` do spatie
 * filtra pelo team do request. Ver ADR-10 da wiki admin-da-organizacao.
 *
 * `null` no tenant = contexto global, que é onde vivem `admin`, `infra` e `master_global`.
 */
function papelNaOrganizacao(User $user, string $papel, ?Tenant $tenant = null): User
{
    $registrar = app(PermissionRegistrar::class);
    $anterior  = $registrar->getPermissionsTeamId();

    try {
        $registrar->setPermissionsTeamId($tenant?->getKey() ?? Tenant::CONTEXTO_GLOBAL);
        $user->unsetRelation('roles');
        $user->assignRole($papel);
    } finally {
        $registrar->setPermissionsTeamId($anterior);
        $user->unsetRelation('roles');
    }

    return $user;
}
