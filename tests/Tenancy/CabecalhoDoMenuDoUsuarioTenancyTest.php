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
