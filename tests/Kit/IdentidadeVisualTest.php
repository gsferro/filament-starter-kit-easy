<?php

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

/**
 * A identidade visual da organização no que ela tem de INDEPENDENTE de tenancy: a persistência
 * dos dois campos, a URL da logo e o motivo registrado quando a logo não existe.
 *
 * O que fica de fora, e por quê: a cor aplicada ao painel e o tenant na sessão precisam de
 * `/app/{tenant}`, que só existe com `kit.tenancy.enabled` — e essa flag é decidida em
 * `createApplication()`, antes das migrations. Esses casos vivem em
 * `tests/Tenancy/IdentidadeVisualTenancyTest.php`. Ver a nota de `tests/Pest.php`.
 */
beforeEach(function (): void {
    // Mesmo par de tests/Kit/PaineisTest.php: papel sem a matriz do Shield abre painel e não
    // abre tela — e a tela de organizações é justamente o que o CT-04 consulta.
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-01 — o caso que prova o `$fillable`, e o único que o pega.
 *
 * Sem `cor_primaria` e `logo` no `$fillable`, o `create()` os descarta em SILÊNCIO: o registro
 * nasce com os dois nulos, sem erro nenhum, e todo o resto da feature vira inerte por um motivo
 * que não aparece em lugar algum. `fresh()` e não o objeto em memória, porque um model recém
 * criado carrega os atributos que foram atribuídos — é o banco que tem de responder.
 */
it('guarda cor e logo da organizacao', function (): void {
    $organizacao = Tenant::create([
        'nome'         => 'Acme',
        'slug'         => 'acme',
        'ativo'        => true,
        'cor_primaria' => '#7c3aed',
        'logo'         => 'organizacoes/logos/acme.png',
    ]);

    expect($organizacao->fresh())
        ->cor_primaria->toBe('#7c3aed')
        ->logo->toBe('organizacoes/logos/acme.png');
});

/**
 * CT-02 — a URL da logo, e os DOIS jeitos de não ter uma.
 *
 * `null` é metade do caso: string vazia num `src` de `<img>` faz o navegador requisitar a própria
 * página e renderizar ícone quebrado, e `null` é o que o Auth Designer trata como "sem mídia"
 * (`AuthPageConfig::hasMedia()`).
 *
 * A terceira persona é a que o quality gate acrescentou: **path preenchido com arquivo ausente**.
 * Acontece de verdade — restore de banco sem o storage, `migrate:fresh` com uploads antigos,
 * arquivo apagado à mão. Sem o `exists()` do model, a tela renderiza um `<img>` quebrado no lugar
 * da mídia base, que é o oposto do que ela promete.
 */
it('resolve a url da logo pelo disk publico', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('organizacoes/logos/acme.png', 'png-de-mentira');

    $comLogo  = Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/acme.png')->create();
    $semLogo  = Tenant::factory()->create();
    $comOrfao = Tenant::factory()->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/sumiu.png')->create();

    expect($comLogo->urlDaLogo())->toContain('organizacoes/logos/acme.png')
        ->and($semLogo->urlDaLogo())->toBeNull()
        ->and($comOrfao->urlDaLogo())->toBeNull();
});

/**
 * CT-04 — o recorte do RQ-08: sem tenancy, a organização não é assunto do painel.
 *
 * `canAccess()` é o contrato que `TenantResource` publica (`TenantResource.php:84-87`), e é ele
 * que o menu lateral e a categoria "Telas" do Spotlight consultam. A página `view` é nova, então
 * é ela que poderia ficar de fora do combinado — daí o caso citar a página, e não só o resource.
 */
it('esconde a tela de view sem tenancy', function (): void {
    $organizacao = Tenant::factory()->comIdentidadeVisual()->create();

    expect(config('kit.tenancy.enabled'))->toBeFalse()
        ->and(TenantResource::canAccess())->toBeFalse()
        ->and(TenantResource::shouldRegisterNavigation())->toBeFalse();

    // A rota EXISTE — o Filament registra as páginas do resource independentemente da config —,
    // então o que fecha a porta é o `canAccess()` do resource, checado no mount de toda página de
    // resource por `CanAuthorizeResourceAccess::authorizeResourceAccess()`.
    expect(collect(TenantResource::getPages())->keys()->all())->toContain('view');

    // `master_global` de propósito: ele vence pelo `Gate::before` e passaria em qualquer policy.
    // Se o 403 aparece PARA ELE, veio da tenancy desligada e de mais nada. O contraponto, com a
    // flag ligada e um `admin` comum, é o CT-03.
    $this->actingAs(usuarioDoKit('master_global'))
        ->get("/admin/organizacoes/{$organizacao->getRouteKey()}")
        ->assertForbidden();
});

/**
 * CT-07 — o log que responde "por que apareceu a logo genérica?".
 *
 * Três motivos, três caminhos diferentes até a mesma mídia base, e nenhum deles é visível na
 * tela: quem olha a lock-screen vê a imagem padrão e não tem como distinguir "o painel não tem
 * organização" de "a organização não enviou logo". O log é a única diferença.
 *
 * O do caso neutro da COR não existe de propósito — "por que a cor é a default?" é auto-evidente.
 *
 * ## Handler no channel real, e não `Log::partialMock()`
 *
 * O desenho de `espiarAutenticacao()` (`tests/Pest.php`) não serve a este caso, e a falha é
 * traiçoeira: `partialMock()` monta um mock da CLASSE `LogManager` sem chamar o construtor, então
 * toda chamada que não casa com uma expectativa cai no método real com `$this->app` nulo e morre
 * em `Trying to access array offset on null` — dentro do LogManager, sem relação nenhuma com o que
 * se testa, e mascarando o erro original porque o próprio handler de exceções também loga. O
 * painel `/app` escreve no channel `ai` (o widget do assistente, no render hook BODY_END) em todo
 * request, então isso aconteceria sempre aqui.
 *
 * Trocar os handlers do channel real custa uma linha, não mascara nada e prova mais: que o
 * registro chega ao channel `tenancy`, e não apenas que alguém chamou `Log::channel()`.
 *
 * ## Dataset, e não três fases num caso só
 *
 * Um caso por motivo porque cada um precisa de um PAINEL, e visitar dois painéis no mesmo teste
 * estoura 500: o `SpotlightActionRegistry` do ⌘K é singleton de container
 * (`FilamentSearchSpotlightServiceProvider.php:25`), acumula as ações "Criar X" do primeiro painel
 * e, no segundo, resolve `getUrl('create')` contra a rota errada
 * (`filament.app.resources.agentes-ia.create`, que não existe). Em produção cada request tem
 * container próprio; num teste o container atravessa os `$this->get()`. Achado registrado — não é
 * desta feature.
 */
it('registra o motivo de usar a midia base', function (string $painel, string $motivo, bool $comOrganizacao): void {
    $registros = new TestHandler;
    Log::channel('tenancy')->getLogger()->setHandlers([$registros]);

    $this->actingAs(usuarioDoKit('master_global'));
    session(['lockscreen' => true]);

    if ($comOrganizacao) {
        session(['tenant_corrente' => Tenant::factory()->create()->getKey()]);
    }

    $this->get(route("lockscreen.{$painel}.page"))->assertOk();

    expect($registros->hasDebugThatPasses(
        fn (LogRecord $registro): bool => str_starts_with($registro->message, '[TelaBloqueio@getAuthDesignerConfig]')
            && ($registro->context['motivo'] ?? null) === $motivo,
    ))->toBeTrue("Nenhum registro do channel `tenancy` explicou a mídia base com o motivo `{$motivo}`.");
})->with([
    // O painel do administrador da instalação: a guarda que o impede de ver a logo de um
    // cliente vazada pela sessão — o risco nomeado em ADR-03.
    'painel sem organização' => ['admin', 'painel_sem_tenancy', false],
    // Painel de negócio, mas nenhum request de `/app/{tenant}` gravou a sessão ainda.
    'sem organização na sessão' => ['app', 'sem_tenant', false],
    // Organização resolvida e sem logo — o padrão, já que o campo é opcional.
    'organização sem logo' => ['app', 'sem_logo', true],
]);
