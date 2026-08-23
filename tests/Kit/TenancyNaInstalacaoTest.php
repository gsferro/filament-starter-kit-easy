<?php

use App\Console\Commands\KitInstall;
use App\Models\Tenant;
use App\Support\AtivadorDeTenancy;
use Illuminate\Support\Facades\File;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ligar a multi-organização DURANTE a instalação, sem recriar o banco.
 *
 * O `kit:tenancy` é destrutivo (`migrate:fresh --seed`) porque, num banco já
 * migrado, as tabelas de permissão nasceram sem a coluna de contexto e não há
 * como acrescentá-la aditivamente. Na instalação o banco ainda não existe: as
 * mesmas três chaves, ligadas ANTES do primeiro migrate, custam zero.
 *
 * "Antes" é a palavra que este arquivo protege. Ligar depois do migrate produz o
 * pior tipo de defeito: config dizendo "teams" e banco sem a coluna, sem erro
 * nenhum até o primeiro login.
 */
it('roda a customização antes de preparar o banco e de migrar', function (): void {
    $handle = File::get((new ReflectionClass(KitInstall::class))->getFileName());

    $customizar = strpos($handle, '$this->customizar(');
    $sqlite     = strpos($handle, '$this->prepararBancoSqlite();');
    $migrar     = strpos($handle, '$this->migrar();');

    expect($customizar)->toBeInt()
        ->and($sqlite)->toBeGreaterThan($customizar)
        ->and($migrar)->toBeGreaterThan($customizar);
})->group('kit');

it('não recria o banco durante a instalação', function (): void {
    $fonte = File::get((new ReflectionClass(KitInstall::class))->getFileName());

    // `migrate:fresh` segue como busca de texto crua: não existe motivo legítimo para essa
    // string aparecer neste arquivo, nem em comentário. Se aparecer, é para ser investigado.
    expect($fonte)->not->toContain('migrate:fresh');

    /*
     * `kit:tenancy` precisa de oráculo mais fino, e a mudança é para ESTREITAR, não afrouxar.
     *
     * A versão anterior proibia a string em qualquer lugar do arquivo, e isso confundia
     * INVOCAR com CITAR: o `--custom` imprime `php artisan kit:tenancy` como orientação para
     * quem quer ligar a multi-organização depois — mencionar o comando ao usuário é o oposto de
     * executá-lo escondido. O oráculo antigo dava falso positivo justamente na mensagem que
     * existe para evitar que alguém rode o destrutivo sem saber.
     *
     * O que a dívida original protege é "a instalação não CHAMA o comando destrutivo", e é isso
     * que se assere agora, nas duas formas que o kit usa para chamar comando de dentro de
     * comando. Uma terceira forma (variável, constante) escaparia — e é por isso que o
     * `migrate:fresh` acima continua cru: as duas asserções juntas cobrem o caminho real, já que
     * qualquer recriação de banco passa por ele.
     */
    expect($fonte)->not->toContain("->call('kit:tenancy'")
        ->and($fonte)->not->toContain("Artisan::call('kit:tenancy'");
})->group('kit');

it('alinha as três chaves e o contexto de papéis em memória', function (): void {
    expect(config('permission.teams'))->toBeFalse();

    AtivadorDeTenancy::alinharConfigEmMemoria();

    expect(config('kit.tenancy.enabled'))->toBeTrue()
        ->and(config('permission.teams'))->toBeTrue()
        ->and(config('filament-shield.tenant_model'))->toBe(Tenant::class)
        ->and(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(Tenant::CONTEXTO_GLOBAL);
})->group('kit');

// O contrato inverso — schema nascido COM a coluna de contexto — está em
// tests/Tenancy/SchemaDaTenancyTest.php: só lá as migrations rodam com as
// chaves ligadas antes, que é exatamente o que a instalação faz.
