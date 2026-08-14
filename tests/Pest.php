<?php

use App\Models\Tenant;
use App\Models\User;
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
