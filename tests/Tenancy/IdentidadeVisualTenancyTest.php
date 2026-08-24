<?php

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * A identidade visual no que ela só significa com tenancy ligada: a tela de leitura da
 * organização, a cor que NÃO pode atravessar de uma organização para outra nem para os painéis de
 * instalação, e o tenant corrente gravado na sessão.
 *
 * Aqui e não em `tests/Kit` porque `Tests\TenancyTestCase` fixa `permission.teams` em
 * `createApplication()`, antes das migrations — ligar a flag num `beforeEach` seria tarde. Ver a
 * nota de `tests/Pest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-03 — a tela de leitura da organização abre para quem a administra.
 *
 * A asserção de conteúdo está aqui, e não em navegador, porque a tela é de leitura pura: não há
 * JavaScript interativo para o navegador provar. O hex da cor no corpo é o que distingue "a tela
 * abriu" de "a tela abriu mostrando a identidade visual".
 */
it('abre a tela de view da organizacao', function (): void {
    $organizacao = Tenant::factory()->comIdentidadeVisual('#7c3aed')->create(['nome' => 'Acme', 'slug' => 'acme']);

    // Antes do request, e com mensagem: sem esta permission o caso falharia com 403 e a causa
    // (seeder do Shield não rodado, ou página `view` fora da matriz) custaria meia hora.
    expect(Permission::where('name', 'View:Tenant')->exists())->toBeTrue(
        'A permission `View:Tenant` não existe. O ShieldPermissionsSeeder não rodou, ou a página '
        .'`view` do TenantResource não entrou na matriz do painel /admin.',
    );

    $this->actingAs(usuarioCom('admin'))
        ->get("/admin/organizacoes/{$organizacao->getRouteKey()}")
        ->assertSuccessful()
        ->assertSee('Acme')
        ->assertSee('#7c3aed');
});

/**
 * CT-05 — o caso mais importante do arquivo.
 *
 * `FilamentColor` é GLOBAL, não por painel: a guarda dupla do `AppPanelProvider::bootUsing()`
 * (painel `app` + tenant com `cor_primaria`) é a única coisa entre esta feature e o painel de
 * administração pintado com a cor de um cliente.
 *
 * Dá para provar sem navegador porque a cor REGISTRADA é estado de PHP: o `@filamentStyles` chama
 * `FilamentColor::getColors()` no render (`AssetManager.php:286`), então depois do request a
 * paleta está no manager. Que ela CHEGUE à tela como CSS var é o CT-B02.
 */
it('nao vaza a cor entre organizacoes e paineis', function (): void {
    ['usuario' => $usuario] = duasOrganizacoes();

    // Medida antes de qualquer request: nenhum painel bootou, então nenhuma Closure de cor está
    // registrada e o que sai daqui é o default do Filament (Amber).
    fronteiraDeRequest();
    $default = FilamentColor::getColors()['primary'];

    $this->actingAs($usuario);

    $corPrimariaApos = function (string $url): array {
        fronteiraDeRequest();

        $this->get($url)->assertSuccessful();

        return FilamentColor::getColors()['primary'];
    };

    $daAcme   = $corPrimariaApos('/app/acme');
    $daGlobex = $corPrimariaApos('/app/globex');
    $doAdmin  = $corPrimariaApos('/admin');

    expect($daAcme)->toBe(Color::generatePalette('#7c3aed'))
        ->and($daGlobex)->toBe(Color::generatePalette('#059669'))
        // A asserção que o cache de `$cachedColors` derrubaria.
        ->and($daGlobex)->not->toBe($daAcme)
        // E a que a falta da guarda de painel derrubaria: o /admin não é de ninguém.
        ->and($doAdmin)->toBe($default)
        ->and($doAdmin)->not->toBe($daAcme);
});

/**
 * CT-06 — a via pela qual a tela de bloqueio descobre a organização.
 *
 * A lock-screen é registrada em `/{painel}/screen/lock`, sem o segmento `{tenant}` e sem o
 * `tenantMiddleware` (`vendor/marjose123/filament-lockscreen/routes/web.php`), então
 * `Filament::getTenant()` é null lá. A sessão é a única fonte — ADR-03.
 *
 * ## Divergência conhecida com o texto do CT-06
 *
 * O caso desenhado pedia `session('tenant_corrente')` NULA depois do `/admin`. Isso não é o que a
 * implementação faz, e não por descuido: o `DefinirTenantDePermissoes` é `tenantMiddleware` do
 * painel `/app` e não roda no `/admin` — não existe ponto onde limpar a chave sem criar um
 * middleware novo nos outros dois painéis. ADR-03 assume isso explicitamente ("a chave ainda diz
 * acme") e paga com a guarda de PAINEL na `TelaBloqueio`.
 *
 * Então a segunda metade do caso afirma o que protege de verdade, que é mais forte do que a chave
 * nula: com a chave apontando para uma organização QUE TEM LOGO, a tela de bloqueio do `/admin`
 * continua sem exibi-la.
 */
it('guarda o tenant corrente na sessao', function (): void {
    ['acme' => $acme, 'usuario' => $usuario] = duasOrganizacoes();

    $acme->update(['logo' => 'organizacoes/logos/acme.png']);

    $this->actingAs($usuario)->get('/app/acme')->assertSuccessful();

    expect(session('tenant_corrente'))->toBe($acme->getKey());

    fronteiraDeRequest();
    session(['lockscreen' => true]);

    $this->get(route('lockscreen.admin.page'))
        ->assertOk()
        ->assertDontSee('organizacoes/logos/acme.png');

    // A chave SOBREVIVE ao /admin — é o risco nomeado em ADR-03, e quem o neutraliza é a guarda
    // de painel, não a limpeza da sessão. Se um dia alguém limpar a chave, este `toBe` acusa e o
    // comentário acima explica por que a mudança precisa ser deliberada.
    expect(session('tenant_corrente'))->toBe($acme->getKey());
});

/**
 * CT-08 — a cor só entra no formato que a coluna e o Filament esperam.
 *
 * `ColorPicker::hex()` NÃO valida: ele troca o formato do picker e nada mais
 * (`vendor/filament/forms/src/Components/ColorPicker.php:31-36`). E `Color::generatePalette()`
 * não estoura com lixo — o `sscanf` falha, o chroma cai abaixo de 0.03 e a paleta sai
 * ACROMÁTICA. O painel do cliente ficaria cinza, sem erro em lugar nenhum.
 *
 * Achado do `feature-quality-gate` (QA-03); a regra no form é a correção.
 */
it('recusa cor fora do formato hexadecimal', function (string $corInvalida): void {
    // `setCurrentPanel` antes do Livewire::test: o componente de resource resolve o schema pelo
    // painel corrente, e sem ele o teste morre em `getDefaultTestingSchemaName() on null`. Mesmo
    // padrão de tests/Kit/PaginasInfraTest.php:94.
    Filament::setCurrentPanel('admin');

    $this->actingAs(usuarioComPapel('master_global'));

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'nome'         => 'Acme',
            'slug'         => 'acme',
            'ativo'        => true,
            'cor_primaria' => $corInvalida,
        ])
        ->call('create')
        ->assertHasFormErrors(['cor_primaria']);

    expect(Tenant::where('slug', 'acme')->exists())->toBeFalse();
})->with([
    'nome de cor'      => 'roxo',
    'hex invalido'     => '#ZZZZZZ',
    'formato rgb'      => 'rgb(124, 58, 237)',
    'tentativa de xss' => '"><script>alert(1)</script>',
]);

/**
 * CT-29 — a cor da organização vence a cor LIVRE do kit dentro de /app/{slug}.
 *
 * A regressão que este caso protege é RQ-18 do requisito de settings: "não confundir
 * com o settings do tenant". A tela `/admin/configuracoes-do-kit` passou a oferecer
 * uma cor livre em hexadecimal, no mesmo formato da cor de uma organização — se a
 * precedência regredir, o cliente abre o painel DELE e vê a cor da instalação.
 *
 * O caso vizinho ("não vaza a cor entre organizações e painéis") prova que o
 * /admin não recebe cor de cliente. Este prova o sentido inverso, que só passou a
 * ser possível com a cor livre: o kit não invade o /app.
 *
 * A cor do kit é registrada por `$panel->colors()`, avaliada no `Panel::boot()`
 * (middleware, primeiro da pilha); a da organização, por `FilamentColor::register()`
 * no `bootUsing()`, avaliada no render. Quem registra por último vence — e o
 * `/admin` é a prova de que o kit continua valendo onde não há organização.
 */
it('mantem a cor da organizacao vencendo a cor livre do kit', function (): void {
    ['usuario' => $usuario] = duasOrganizacoes();

    config(['kit.cor_primaria_hex' => '#7c3aed', 'kit.cor_primaria' => '']);

    $this->actingAs($usuario);

    $corPrimariaApos = function (string $url): array {
        fronteiraDeRequest();

        $this->get($url)->assertSuccessful();

        return FilamentColor::getColors()['primary'];
    };

    // A Acme é #7c3aed de propósito: é a MESMA cor livre do kit, então esta
    // asserção sozinha não distinguiria nada. Quem discrimina é a Globex.
    $daGlobex = $corPrimariaApos('/app/globex');
    $doAdmin  = $corPrimariaApos('/admin');

    expect($daGlobex)->toBe(Color::generatePalette('#059669'))
        ->and($daGlobex)->not->toBe(Color::generatePalette('#7c3aed'))
        // E o kit continua valendo onde não existe organização.
        ->and($doAdmin)->toBe(Color::generatePalette('#7c3aed'));
});
