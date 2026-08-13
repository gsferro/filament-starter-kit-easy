<?php

use App\Console\Commands\KitUpdate;

/**
 * O `kit:update` compara duas versões do kit restrito a uma lista fechada de
 * caminhos. Arquivo do kit fora dessa lista **não chega a quem já instalou**:
 * a feature existe no repositório e é invisível na prática.
 *
 * Foi exatamente o que aconteceu com a multi-tenancy — três versões inteiras
 * (0.9.1 a 0.9.3) em que o `kit:update` só oferecia `config/kit.php`. Este
 * teste é o que faz a lista envelhecer com barulho em vez de em silêncio.
 */
function caminhosDoKit(): array
{
    $reflexao = new ReflectionClass(KitUpdate::class);

    /** @var list<string> $caminhos */
    $caminhos = $reflexao->getConstant('CAMINHOS_DO_KIT');

    return $caminhos;
}

function estaCoberto(string $arquivo): bool
{
    foreach (caminhosDoKit() as $caminho) {
        if ($arquivo === $caminho || str_starts_with($arquivo, rtrim($caminho, '/').'/')) {
            return true;
        }
    }

    return false;
}

it('cobre os arquivos da fundação na lista de caminhos do kit', function (string $arquivo): void {
    expect(estaCoberto($arquivo))->toBeTrue(
        "`{$arquivo}` é do kit mas não está em KitUpdate::CAMINHOS_DO_KIT — "
        .'quem já instalou o projeto nunca receberá este arquivo.'
    );
})->with([
    // A cola
    'app/Providers/KitServiceProvider.php',
    'app/Providers/Concerns/ConfiguraFilamentGlobal.php',
    'app/Traits/TemUuid.php',
    'app/Traits/AuditsFillables.php',
    'app/Models/User.php',

    // Comandos
    'app/Console/Commands/KitInstall.php',
    'app/Console/Commands/KitUpdate.php',
    'app/Console/Commands/KitTenancy.php',

    // Multi-tenancy
    'app/Models/Tenant.php',
    'app/Traits/BelongsToTenant.php',
    'app/Http/Middleware/DefinirTenantDePermissoes.php',
    'app/Policies/TenantPolicy.php',
    'app/Filament/Admin/Resources/Tenants/TenantResource.php',
    'app/Ai/Support/ResolvedorDeTenant.php',
    'database/migrations/0001_01_01_000020_create_tenants_table.php',
    'database/seeders/TenantsSeeder.php',
    'database/factories/TenantFactory.php',

    // Suítes do kit
    'tests/Pest.php',
    'tests/TestCase.php',
    'tests/TenancyTestCase.php',
    'tests/Kit/FundacaoTest.php',
    'tests/Tenancy/TenancyTest.php',
]);

it('só lista caminhos que existem de fato', function (): void {
    $ausentes = array_values(array_filter(
        caminhosDoKit(),
        fn (string $caminho): bool => ! file_exists(base_path($caminho)),
    ));

    // Caminho que não existe mais vira ruído no diff e esconde erro de digitação.
    expect($ausentes)->toBe([]);
});

/**
 * O `.gitattributes` marca com `export-ignore` o que fica fora do pacote
 * distribuído — o CI e o changelog são do kit, não do projeto que nasce dele.
 * Caminho assim não existe em projeto instalado por `create-project`: listá-lo
 * aqui faria o `kit:update` oferecer arquivo que o projeto não deveria ter, e
 * derrubaria o teste acima em toda instalação (foi o que aconteceu com
 * `.github`).
 */
it('não lista caminho que o pacote distribuído deixa de fora', function (): void {
    $linhas = file(base_path('.gitattributes'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $exportIgnore = [];

    foreach ($linhas as $linha) {
        if (preg_match('/^(\S+)\s+export-ignore\b/', trim($linha), $captura) === 1) {
            $exportIgnore[] = ltrim($captura[1], '/');
        }
    }

    expect($exportIgnore)->not->toBeEmpty('.gitattributes sem export-ignore: o teste perdeu o alvo.')
        ->and(array_values(array_intersect(caminhosDoKit(), $exportIgnore)))->toBe([]);
});
