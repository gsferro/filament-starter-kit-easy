<?php

use App\Filament\App\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * O cabeçalho rico e a ficha de usuário no painel COM organização.
 *
 * Aqui mora o que a suíte `tests/Kit` não consegue medir, e por dois motivos distintos:
 *
 *   1. `TenantResource` exige `kit.tenancy.enabled` (`TenantResource::canAccess():106-109`), então
 *      as telas de organização respondem 403 em single-tenant;
 *   2. vazamento entre organizações precisa de DUAS organizações. Um caso com uma só mediria a
 *      ausência de dado, não a fronteira — e passaria com a fronteira inteira removida.
 *
 * Ver `wikis/specs/feat/page-header-nas-telas-de-registro/page-header-nas-telas-de-registro/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| RQ-03 — o cabeçalho nas telas de organização
|--------------------------------------------------------------------------
*/

/**
 * CT-12 — as telas de View e Edit de organização renderizam o cabeçalho, com o nome dentro dele.
 *
 * O `ViewTenant` é o caso mais informativo da entrega: é o único `ViewRecord` do kit que já
 * renderiza RelationManagers (`TenantResource::getRelations():129-134` → `UsersRelationManager`) e
 * widgets de cabeçalho. Este caso prova que as três coisas convivem — o cabeçalho do pacote, os
 * widgets e as abas ocupam regiões de DOM disjuntas.
 */
it('[CT-12] renderiza o cabecalho nas telas de organizacao', function (string $sufixo): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $acme  = tenant('Acme Ltda', 'acme');

    $cabecalho = regiaoDoCabecalho(
        $this->actingAs($admin)
            ->get("/admin/organizacoes/{$acme->getRouteKey()}{$sufixo}")
            ->assertSuccessful()
            ->getContent(),
    );

    expect($cabecalho)->not->toBe('', "a tela /admin/organizacoes/…{$sufixo} nao renderizou o cabecalho")
        ->and($cabecalho)->toContain('Acme Ltda');
})->with([
    'ver'    => [''],
    'editar' => ['/edit'],
]);

/**
 * CT-13 — o cabecalho e o relation manager do `ViewTenant` ocupam regioes DISJUNTAS.
 *
 * Controle de regressao do ADR-05: a receita afirma que header e relations nao disputam espaco, e
 * afirmacao de wiki que vale um teste nao deve ficar so na wiki.
 *
 * ## Duas tentativas de oraculo foram descartadas, e o motivo de cada uma fica aqui
 *
 * **Conteudo do relation manager** nao serve: toda tabela do kit carrega adiada (`deferLoading`
 * global), entao o e-mail do membro nao esta no HTML inicial. Um `assertSee` dele reprovaria por
 * motivo errado, e um `assertDontSee` passaria por engano.
 *
 * **Posicao da primeira ocorrencia** tambem nao: medido, `UsersRelationManager` aparece em 107650
 * e o `</header>` em 131413 — a primeira ocorrencia esta no `wire:snapshot` da PAGINA, que o
 * Livewire serializa no topo, e nao no componente renderizado. Comparar posicoes ali mede a ordem
 * do snapshot, nao a do layout, e reprova uma implementacao correta.
 *
 * O que sobra e o que a afirmacao de fato diz: o relation manager nao esta DENTRO do cabecalho, e
 * esta na pagina. As duas metades sao necessarias — sem a segunda, uma pagina sem relation manager
 * nenhum passaria.
 */
it('[CT-13] mantem o relation manager fora da regiao do cabecalho na view da organizacao', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $acme  = tenant('Acme Ltda', 'acme');

    $membro = usuario('membro@example.com');
    $membro->tenants()->attach($acme->id);

    $html      = $this->actingAs($admin)
        ->get("/admin/organizacoes/{$acme->getRouteKey()}")
        ->assertSuccessful()
        ->getContent();
    $cabecalho = regiaoDoCabecalho($html);

    expect($cabecalho)->not->toBe('', 'a view da organizacao nao renderizou o cabecalho do pacote');

    expect(str_contains($html, 'UsersRelationManager'))->toBeTrue(
        'o relation manager nao esta na pagina — sem ele o caso mediria o vazio',
    )
        ->and(str_contains($cabecalho, 'UsersRelationManager'))->toBeFalse(
            'o relation manager foi renderizado DENTRO do cabecalho do pacote',
        )
        // E os widgets de cabecalho do ViewTenant, que ocupam a terceira regiao, seguem na pagina.
        ->and(str_contains($html, 'OrganizacaoStats'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| RQ-13 e ADR-07 — a ficha no /app
|--------------------------------------------------------------------------
*/

/**
 * CT-14 — a ficha do /app abre para alguém DA organização e responde 404 para quem governa a
 * instalação.
 *
 * O 404 (e não 403) é o route binding falando: `ViewRecord::mount():69` chama `resolveRecord()`
 * ANTES de `authorizeAccess()`, e a query do resource já não contém o alvo. Quem governa não
 * existe para este painel.
 *
 * Molde: `tests/Tenancy/FronteiraDoAdminAppTest.php:[CT-02]:109-125`.
 */
it('[CT-14] abre a ficha de quem e da organizacao e responde 404 para quem governa a instalacao', function (): void {
    $acme = tenant('Acme', 'acme');

    $ana  = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $beto = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    $gil  = papelNaOrganizacao(usuario('gil@example.com'), 'master_global');

    $ana->tenants()->attach($acme->id);
    $beto->tenants()->attach($acme->id);
    $gil->tenants()->attach($acme->id);

    $this->actingAs($ana)
        ->get("/app/acme/users/{$beto->getRouteKey()}")
        ->assertSuccessful()
        ->assertSee('beto@example.com');

    $this->actingAs($ana)
        ->get("/app/acme/users/{$gil->getRouteKey()}")
        ->assertNotFound()
        ->assertDontSee('gil@example.com');
});

/**
 * CT-15 — a URL direta para conta de OUTRA organização responde 404.
 *
 * A fronteira que importa, e a que um caso de uma organização só não consegue medir.
 */
it('[CT-15] responde 404 na ficha de conta de outra organizacao', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    $zoe = papelNaOrganizacao(usuario('zoe@example.com'), 'panel_user', $globex);
    $zoe->tenants()->attach($globex->id);

    $this->actingAs($ana)
        ->get("/app/acme/users/{$zoe->getRouteKey()}")
        ->assertNotFound()
        ->assertDontSee('zoe@example.com');
});

/**
 * CT-16 — `getViewAuthorizationResponse()` nega DIRETO o alvo que governa a instalação.
 *
 * Chamada direta, com o alvo em mãos — nunca pela tela. Caso que passa pela tela mede o recorte da
 * query (o 404 do CT-14), não a barreira: ele ficaria verde com a sobrescrita inteira removida,
 * que é exatamente a mutação que este caso existe para matar.
 *
 * É a mesma forma do caso irmão de `getEditAuthorizationResponse()`, e o motivo de as duas
 * existirem está no ADR-07: a edição tinha duas camadas e a visualização teria uma só, o que
 * deixaria a tela de LEITURA mais permissiva que a de ESCRITA sobre o mesmo alvo.
 */
it('[CT-16] nega direto a visualizacao de quem governa a instalacao no painel app', function (): void {
    $acme = tenant('Acme', 'acme');

    $gil  = papelNaOrganizacao(usuario('gil@example.com'), 'master_global');
    $beto = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);

    /*
     * O ATOR precisa estar autenticado e com a organizacao corrente. Sem isso o `parent::` nega os
     * DOIS — `Gate::inspect()` sem usuario recusa —, e o par positivo/negativo passaria a medir a
     * ausencia de sessao em vez da barreira. Foi o que aconteceu na primeira escrita deste caso.
     */
    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    $this->actingAs($ana);
    noPainelDa($acme);

    expect(UserResource::getViewAuthorizationResponse($gil)->denied())->toBeTrue()
        // O par positivo: sem ele, um `Response::deny()` incondicional passaria.
        ->and(UserResource::getViewAuthorizationResponse($beto)->denied())->toBeFalse();
});

/**
 * CT-17 — a negação vira registro no canal `autenticacao`, sem PII no context.
 *
 * O canal já é lido pela trilha de `/infra`. O context leva chaves, nunca e-mail nem nome: a
 * dimensão D do quality gate reprova PII em log, e "máximo de contexto" é justamente o hábito que
 * produz vazamento ali.
 */
it('[CT-17] registra a negacao de visualizacao no canal de autenticacao, sem PII', function (): void {
    $gil = papelNaOrganizacao(usuario('gil@example.com'), 'master_global');

    $espiao = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('autenticacao')->andReturn($espiao);

    UserResource::getViewAuthorizationResponse($gil);

    $espiao->shouldHaveReceived('warning')->withArgs(function (string $mensagem, array $context) use ($gil): bool {
        return str_contains($mensagem, '[UserResource@getViewAuthorizationResponse]')
            && $context['alvo_id'] === $gil->id
            && $context['motivo'] === 'alvo_governa_a_instalacao'
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'gil@example.com');
    });
});

/**
 * CT-18 — a ficha do /app NÃO mostra as organizações da pessoa.
 *
 * O par de `[CT-11]` de `tests/Kit/PageHeaderTest.php`, e é este que consegue reprovar: só aqui
 * existe uma segunda organização para vazar.
 *
 * Quem administra a Acme pode ver quem é da Acme — isso é a feature. O que ele não pode é
 * descobrir que aquela pessoa também é da Globex. O recorte de `getEloquentQuery():197` decide
 * QUEM aparece; ele não decide o que a ficha conta sobre quem aparece.
 */
it('[CT-18] nao conta na ficha do app que a pessoa pertence a outra organizacao', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex Confidencial', 'globex');

    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    // Beto opera as duas organizações; para a Ana, só a Acme deveria existir.
    $beto = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    papelNaOrganizacao($beto, 'panel_user', $globex);
    $beto->tenants()->attach([$acme->id, $globex->id]);

    $this->actingAs($ana)
        ->get("/app/acme/users/{$beto->getRouteKey()}")
        ->assertSuccessful()
        ->assertSee('beto@example.com')
        ->assertDontSee('Globex Confidencial')
        ->assertDontSee('Vinculos');
});

/**
 * CT-19 — o cabeçalho do /app renderiza, e a `ViewUser` do /app tem cabeçalho igual à do /admin.
 *
 * Fecha a RQ-02 do lado do painel de negócio: sem este caso, `use HasPageHeader` removido só da
 * `ViewUser` do /app passaria despercebido — todos os outros casos de cabeçalho rodam no /admin.
 */
it('[CT-19] renderiza o cabecalho na ficha de usuario do painel de negocio', function (): void {
    $acme = tenant('Acme', 'acme');

    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    $beto = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    $beto->forceFill(['name' => 'Beto Silva'])->save();
    $beto->tenants()->attach($acme->id);

    $cabecalho = regiaoDoCabecalho(
        $this->actingAs($ana)
            ->get("/app/acme/users/{$beto->getRouteKey()}")
            ->assertSuccessful()
            ->getContent(),
    );

    expect($cabecalho)->not->toBe('')
        ->and($cabecalho)->toContain('Beto Silva')
        // As iniciais do pacote, porque Beto não tem foto — e nenhum `data:` URI. Ver ADR-02.
        ->and(str_contains($cabecalho, 'data:image'))->toBeFalse();
});

/**
 * CT-20 — a permissão `View:User` decide a ficha também no /app.
 *
 * `admin_app` só existe nesta suíte (`.ai/rules/testes.md`), então o par tem/não-tem do painel de
 * negócio não cabe em `tests/Kit`.
 */
it('[CT-20] recusa a ficha no painel de negocio para quem nao tem View User', function (): void {
    $acme = tenant('Acme', 'acme');

    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    $beto = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    $beto->tenants()->attach($acme->id);

    semAPermissao('admin_app', 'View:User');

    $this->actingAs($ana)
        ->get("/app/acme/users/{$beto->getRouteKey()}")
        ->assertForbidden();
});

/**
 * CT-21 — a ficha existe nos dois painéis com a rota `view` registrada, e a organização também.
 *
 * Complementa `[CT-08]` de `tests/Kit`: lá a asserção é sobre a ORDEM das chaves; aqui é sobre a
 * rota responder de fato no painel com organização, que é onde o prefixo `{tenant}` entra na URL.
 */
it('[CT-21] serve a rota view do painel de negocio sob o prefixo da organizacao', function (): void {
    $acme = tenant('Acme', 'acme');

    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    noPainelDa($acme);

    expect(UserResource::getUrl('view', ['record' => $ana], panel: 'app'))
        ->toContain('/app/acme/users/'.$ana->getRouteKey());
});

/**
 * CT-22 — o `Tenant` sem logo cai nas iniciais tingidas pela cor da organização.
 *
 * Mata a mutação de escrever a precedência de cor à mão em vez de delegar a
 * `CorPrimaria::resolver()`: o hexadecimal livre vence o nome da paleta, e o `/app` usa a mesma
 * função no `bootUsing()` (`app/Providers/Filament/AppPanelProvider.php:185`). Cabeçalho que
 * discorde do painel que descreve é defeito silencioso.
 *
 * O hex passa por `Color::generatePalette()` porque `Header::getIdentityStyles():215-235` lê
 * `[600] ?? [500]` de um ARRAY. Hex cru devolveria `null` de `FilamentColor::getColor()` e o tom
 * sumiria sem erro — é isso que a variável CSS abaixo prova que não aconteceu.
 */
it('[CT-22] tinge as iniciais da organizacao com a cor primaria dela', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');

    $acme = Tenant::factory()->create([
        'nome'          => 'Acme Ltda',
        'slug'          => 'acme',
        'logo'          => null,
        'cor_primaria'  => '#7c3aed',
    ]);

    $cabecalho = regiaoDoCabecalho(
        $this->actingAs($admin)
            ->get("/admin/organizacoes/{$acme->getRouteKey()}")
            ->assertSuccessful()
            ->getContent(),
    );

    expect($cabecalho)->toContain('--fph-avatar-background')
        // Sem logo, o slot é o de iniciais, nunca um <img>.
        ->and(str_contains($cabecalho, '<img'))->toBeFalse()
        ->and($cabecalho)->toContain('>AL<');
});
