<?php

use App\Models\Tenant;
use App\Support\Papeis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

/**
 * O cabeçalho do menu do usuário no que ele só significa com tenancy ligada.
 *
 * Com `permission.teams` ligado, `roles()` do spatie passa a filtrar por
 * `model_has_roles.team_id`. É exatamente aí que a implementação errada aparece: um
 * badge que consultasse `roles()` sumiria no `/admin` e no `/infra`, porque esses
 * painéis não têm tenant na rota e o `team_id` corrente é o que o middleware tiver
 * deixado. `User::papelDoPainel()` usa `papeisEmQualquerContexto()` justamente por
 * isso (ADR-03 da feature).
 *
 * Aqui e não em `tests/Kit` porque `Tests\TenancyTestCase` fixa `permission.teams` em
 * `createApplication()`, antes das migrations — ligar a flag num `beforeEach` seria
 * tarde. Ver a nota de `tests/Pest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * `admin_app` só existe com tenancy — é o papel de quem administra UMA organização.
 *
 * O caso irmão em `tests/Kit` cobre `admin`, `infra` e `panel_user`; este fecha a
 * tabela do `PapeisSeeder`.
 */
it('devolve admin_app como papel do painel /app', function (): void {
    $organizacao = tenant('Acme', 'acme');

    expect(usuarioComPapel('admin_app', $organizacao)->papelDoPainel('app'))->toBe('admin_app');
});

/**
 * O caso que a tenancy existe para pegar.
 *
 * O papel foi atribuído no contexto de UMA organização (`model_has_roles.team_id` = id
 * dela). Consultado pela `roles()` do spatie fora daquele contexto, ele desapareceria.
 * `papelDoPainel()` tem de continuar respondendo — porque a pergunta "com que papel
 * este usuário entra no /app" não depende de qual organização está aberta agora, do
 * mesmo jeito que `canAccessPanel()` não depende.
 */
it('acha o papel mesmo fora do contexto de organização em que foi atribuído', function (): void {
    $acme  = tenant('Acme', 'acme');
    $outra = tenant('Outra', 'outra');

    $user = usuarioComPapel('panel_user', $acme);

    // Contexto trocado para a OUTRA organização — como num request do painel dela.
    noPainelDa($outra);

    expect($user->papelDoPainel('app'))->toBe('panel_user');
});

/**
 * O master_global continua com badge nos três painéis, com teams ligado.
 *
 * O papel dele mora no contexto GLOBAL (`Tenant::CONTEXTO_GLOBAL`), e `roles.painel` é
 * nulo. É a combinação que mais chance tem de devolver null numa implementação ingênua.
 */
it('mantém o badge do master_global nos três painéis com teams ligado', function (string $painel): void {
    expect(usuarioCom('master_global')->papelDoPainel($painel))->toBe('master_global');
})->with(['app', 'admin', 'infra']);

/**
 * A ponta a ponta: o cabeçalho renderiza dentro do painel com organização na URL.
 *
 * O `/app` com tenancy tem o slug da organização na rota, e é o único painel do kit em
 * que o layout é montado com um tenant resolvido. Se o hook fosse registrado antes de
 * `->tenant()` no provider, ou se a view estourasse ao ler o tenant, é aqui que
 * apareceria.
 */
it('injeta o cabeçalho no painel /app com organização na URL', function (): void {
    $organizacao = tenant('Acme', 'acme');
    $user        = usuarioComPapel('panel_user', $organizacao);
    $user->tenants()->attach($organizacao);

    $this->actingAs($user)
        ->get("/app/{$organizacao->slug}")
        ->assertSuccessful()
        ->assertSee('data-user-menu-header', escape: false)
        ->assertSee($user->email)
        ->assertSee(Papeis::rotulo('panel_user'));
});

/**
 * Fronteira: organização inativa não muda o badge.
 *
 * O badge diz o PAPEL, não o estado da organização. Misturar as duas coisas faria o
 * cabeçalho sumir num cenário em que o usuário continua com o papel — e sumiço
 * silencioso é o pior modo de falha desta feature.
 */
it('não deixa o estado da organização mexer no papel exibido', function (): void {
    $inativa = Tenant::create(['nome' => 'Inativa', 'slug' => 'inativa', 'ativo' => false]);

    expect(usuarioComPapel('panel_user', $inativa)->papelDoPainel('app'))->toBe('panel_user');
});

/*
|--------------------------------------------------------------------------
| O badge diz o papel da ORGANIZAÇÃO ABERTA
|--------------------------------------------------------------------------
| Wiki: `wikis/specs/fix/badge-de-papel-por-organizacao/`.
|
| Quem pertence a mais de uma organização pode ter papéis DIFERENTES do mesmo painel em
| cada uma. Até a v0.22.5 o badge consultava `papelDoPainel()` sem contexto, e o `first()`
| entregava o papel de menor `id` — o mesmo nas duas organizações.
|
| A ordem em que o `PapeisSeeder` cria os papéis é o que separa um caso que mata o defeito
| de um que passa por sorte: `admin_app` nasce ANTES de `panel_user`, então um cenário que
| olhasse só a organização onde a pessoa é `admin_app` ficaria verde com o defeito intacto.
| Por isso o esquema abaixo é bidirecional.
*/

/** Bianca, a persona do relato: papéis diferentes do painel `app` em três organizações. */
function biancaEmTresOrganizacoes(): array
{
    $acme    = tenant('Acme', 'acme');
    $globex  = tenant('Globex', 'globex');
    $initech = tenant('Initech', 'initech');

    $bianca = usuarioComPapel('panel_user', $acme, 'bianca@example.com');
    papelNaOrganizacao($bianca, 'admin_app', $globex);
    papelNaOrganizacao($bianca, 'panel_user', $initech);
    $bianca->tenants()->attach([$acme->id, $globex->id, $initech->id]);

    return [$bianca, $acme, $globex, $initech];
}

it('[CT-01] exibe no badge o papel da organização aberta', function (string $slug, string $papelEsperado, string $papelAusente): void {
    [$bianca] = biancaEmTresOrganizacoes();

    $this->actingAs($bianca)
        ->get("/app/{$slug}")
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo($papelEsperado))
        ->assertDontSee(Papeis::rotulo($papelAusente));
})->with([
    // A linha que o defeito original erra: na Acme ela é `panel_user`, mas `admin_app` tem
    // `roles.id` menor e vencia o `first()`.
    'acme e panel_user'      => ['acme', 'panel_user', 'admin_app'],
    // A linha que uma ordenação DESCENDENTE erraria — as duas juntas não deixam ordenação
    // estática nenhuma passar.
    'globex e admin_app'     => ['globex', 'admin_app', 'panel_user'],
    // A terceira organização: RQ-02 diz "mais de +1", e uma resolução por par ("a corrente e a
    // outra") passa com duas e erra com três.
    'initech e panel_user'   => ['initech', 'panel_user', 'admin_app'],
])->group('kit');

/** CT-02 — a troca de organização troca o badge, na mesma sessão. */
it('[CT-02] troca o badge ao trocar de organização, sem novo login', function (): void {
    [$bianca] = biancaEmTresOrganizacoes();

    $this->actingAs($bianca)
        ->get('/app/acme')
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo('panel_user'));

    fronteiraDeRequest();

    $this->get('/app/globex')
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo('admin_app'))
        ->assertDontSee(Papeis::rotulo('panel_user'));
})->group('kit');

/**
 * CT-03 — papel em OUTRA organização não vira badge aqui.
 *
 * Ela entra: o acesso ao painel não depende da organização aberta (é a decisão da wiki
 * ancestral, preservada). O que não acontece é o badge afirmar um papel que ela não tem
 * nesta organização. ADR-02: sem papel na organização ativa, sem badge.
 */
it('[CT-03] não exibe badge na organização em que a pessoa não tem papel', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $user = usuarioComPapel('panel_user', $acme, 'so.na.acme@example.com');
    $user->tenants()->attach([$acme->id, $globex->id]);

    $this->actingAs($user)
        ->get("/app/{$globex->slug}")
        ->assertSuccessful()
        ->assertSee('data-user-menu-header', escape: false)
        ->assertDontSee(Papeis::rotulo('panel_user'));
})->group('kit');

/**
 * CT-05 — os painéis SEM organização não mudam.
 *
 * A organização é aberta ANTES de visitar o /admin de propósito: o painel de negócio é
 * `isPersistent`, e é nesse estado — com um tenant já resolvido na sessão — que o badge
 * do /admin sumiria se o filtro vazasse para onde não há organização.
 */
it('[CT-05] mantém o badge no /admin e no /infra depois de abrir uma organização', function (string $painel, string $papel): void {
    $acme = tenant('Acme', 'acme');

    $caio = usuarioComPapel('panel_user', $acme, 'caio@example.com');
    papelNaOrganizacao($caio, $papel);
    $caio->tenants()->attach($acme->id);

    $this->actingAs($caio)->get("/app/{$acme->slug}")->assertSuccessful();

    fronteiraDeRequest();

    $this->get("/{$painel}")
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo($papel));
})->with([
    'admin' => ['admin', 'admin'],
    'infra' => ['infra', 'infra'],
])->group('kit');

/**
 * CT-07 — o ACESSO não mudou: a célula que carrega a RQ-05.
 *
 * Entrar na organização em que ela não tem papel continua respondendo 200. Se o filtro do
 * badge tivesse vazado para `canAccessPanel()`, é aqui que viraria 403 — e o kit teria
 * trocado um badge errado por uma porta fechada.
 */
it('[CT-07] não muda o acesso ao painel da organização sem papel', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $user = usuarioComPapel('panel_user', $acme, 'acesso@example.com');
    $user->tenants()->attach([$acme->id, $globex->id]);

    $this->actingAs($user)->get("/app/{$globex->slug}")->assertSuccessful();

    expect($user->temPapelDoPainel('app'))->toBeTrue()
        ->and($user->papelDoPainel('app'))->toBe('panel_user');
})->group('kit');

/** CT-04 — o master global tem badge em qualquer organização e em qualquer painel. */
it('[CT-04] mantém o badge do master global em qualquer organização', function (string $rota): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $marta = usuarioCom('master_global');
    $marta->tenants()->attach([$acme->id, $globex->id]);

    $this->actingAs($marta)
        ->get($rota)
        ->assertSuccessful()
        ->assertSee(Papeis::rotulo('master_global'));
})->with([
    'app/acme'   => ['/app/acme'],
    'app/globex' => ['/app/globex'],
    'admin'      => ['/admin'],
    'infra'      => ['/infra'],
])->group('kit');
