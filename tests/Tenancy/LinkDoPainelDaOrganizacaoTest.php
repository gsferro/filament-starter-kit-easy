<?php

use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Filament\Admin\Resources\Tenants\Pages\ViewTenant;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

/**
 * O link de acesso ao painel da organização, nas três telas do `TenantResource`.
 *
 * IDs de CT em
 * `wikis/specs/feat/link-painel-do-tenant/link-painel-do-tenant/04-casos-de-teste.md`.
 *
 * ## O que estes casos afirmam, e o que deliberadamente não afirmam
 *
 * O requisito decidiu a opção **(a)**: o link aparece SEMPRE, clicável, para quem abre a tela de
 * administração — inclusive para quem não consegue entrar no painel de negócio. Por isso a persona
 * de CT-04, CT-05, CT-07 e CT-11 é o **administrador da instalação**, que falha nos DOIS portões:
 * escrito com o `master_global`, a decisão do usuário ficaria sem um único teste e a alternativa
 * recusada (renderizar só para quem entra) passaria verde no conjunto inteiro.
 *
 * Nenhum caso nomeia a função que gera a URL: o oráculo é o endereço, e ele é sempre calculado com
 * `Filament::getPanel('app')->getUrl($organizacao)`
 * (`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:getUrl:170`), nunca escrito à mão.
 * Essa diferença é o ponto de CT-01: a chave de rota do model é o `uuid`
 * (`app/Traits/TemUuid.php:getRouteKeyName:35`), e a rota do tenant é `{tenant:slug}` — quem
 * montasse a URL com `getRouteKey()` produziria o uuid.
 *
 * ## A asserção de HTML é UMA string adjacente
 *
 * Os três caminhos de render passam por `Filament\Support\generate_href_html()`
 * (`vendor/filament/support/src/helpers.php:generate_href_html:153`), que emite exatamente
 * `href="{url}" target="_blank"`, nessa ordem. `assertSee('target="_blank"')` solto é PROIBIDO
 * como oráculo: o topbar e o widget de informação do Filament já emitem `_blank` em toda página do
 * painel, então o caso ficaria verde com a feature inteira removida.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * O endereço esperado, calculado — nunca escrito à mão.
 *
 * `linkDoPainel()` e não uma string montada no caso: um literal `http://localhost/app/acme` no
 * teste reproduziria no oráculo o mesmo defeito que a feature existe para evitar (ADR-01), e os
 * dois erros se cancelariam.
 */
function enderecoEsperadoDoPainel(Tenant $organizacao): string
{
    return (string) Filament::getPanel('app')->getUrl($organizacao);
}

/** O `href` com a nova aba imediatamente ao lado, que é o que `generate_href_html()` produz. */
function linkComNovaAba(Tenant $organizacao): string
{
    return 'href="'.e(enderecoEsperadoDoPainel($organizacao)).'" target="_blank"';
}

/**
 * O administrador da instalação: papel `admin` no contexto global, sem papel do painel de negócio
 * e sem linha no pivot. Ele ABRE as três telas do `/admin` (o `PapeisSeeder` lhe dá a matriz
 * inteira daquele painel) e falha nos dois portões do `/app`. É a persona que falsifica a decisão.
 */
function administradorDaInstalacao(): User
{
    return usuarioComPapel('admin', email: 'admin-instalacao@example.com');
}

/** O painel `admin` bootado e a persona autenticada — o arranjo comum das três telas. */
function noAdminComo(User $usuario): void
{
    noPainelBootado('admin');
    test()->actingAs($usuario);
}

/*
|--------------------------------------------------------------------------
| R1 — o endereço é o que o painel de negócio gera, a partir do slug gravado
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — o endereço é o do painel da organização, e não o do registro nem o da raiz.
 *
 * `acme-do-brasil` e não `acme`: slug de um só termo não distingue "termina com o slug" de
 * "termina com o nome em minúsculas". A SEGUNDA organização é o que mata o gerador que devolve
 * sempre a raiz do painel — com uma só, `getUrl()` e `getUrl($tenant)` dão strings diferentes mas
 * nenhuma comparação as separa.
 */
it('[CT-01] o endereco e o do painel da organizacao, e nao o do registro nem o da raiz', function (): void {
    $acme   = Tenant::factory()->create(['nome' => 'Acme do Brasil', 'slug' => 'acme-do-brasil']);
    $globex = Tenant::factory()->create(['nome' => 'Globex', 'slug' => 'globex']);

    $endereco = $acme->urlDoPainel();

    expect($endereco)->toEndWith('/acme-do-brasil')
        // O uuid é a chave de rota REAL do model (`TemUuid`). "Não contém o uuid" é o que separa
        // slug de chave de rota, e é o mutante mais plausível de todos.
        ->and($endereco)->not->toContain($acme->uuid)
        // Nem a tela do resource: `/admin/organizacoes/{uuid}` não é o painel da organização.
        ->and($endereco)->not->toContain('organizacoes')
        ->and($endereco)->not->toBe($globex->urlDoPainel());
});

/**
 * CT-02 — o caminho do painel não é escrito à mão no gerador.
 *
 * Escopado ao ARQUIVO DO GERADOR, e não às três superfícies: `TenantForm.php` já contém o literal
 * `/app/{slug}` na `description` da seção, que é prosa exibida ao usuário e não é comentário — um
 * caso escrito sobre ele nasceria vermelho contra a implementação correta. As superfícies são
 * cobertas por CT-04 e CT-15, que afirmam que a URL renderizada é a MESMA que o gerador devolve.
 *
 * `semComentarios()` porque o model documenta `/app/{slug}` no docblock da classe, e citar não é
 * executar (`.ai/rules/testes.md`).
 *
 * ## A segunda asserção saiu (corte C-1 da revisão adversarial)
 *
 * O caso carregava um `preg_match` de regex adivinhado ("o slug concatenado com um caminho"), e o
 * `04` cortou o `Então` correspondente: não é oráculo executável, e `.ai/rules/testes.md` proíbe
 * exatamente isso ("não invente um regex, ele conta comentário como chamada"). O que carrega o
 * cenário é a asserção do LITERAL, que continua aqui. Registrado como C-1 no `03-progresso.md`.
 */
it('[CT-02] o caminho do painel nao e escrito a mao no gerador', function (): void {
    $fonte = semComentarios((string) file_get_contents(app_path('Models/Tenant.php')));

    expect($fonte)->not->toContain('/app');
});

/**
 * CT-03 — trocar o slug move o link.
 *
 * O invariante temporal da regra: o `helperText` do campo já avisa que "mudar invalida os links já
 * compartilhados", então o link da tela tem de acompanhar. Mata o gerador memoizado.
 *
 * O `assertDontSeeHtml` usa o `href` FECHADO por aspas: `/acme` é prefixo de `/acme-2`, e uma
 * asserção sobre o segmento solto ficaria verde por coincidência.
 */
it('[CT-03] trocar o slug move o link', function (): void {
    $organizacao    = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);
    $enderecoAntigo = enderecoEsperadoDoPainel($organizacao);

    noAdminComo(usuarioComPapel('master_global'));

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->fillForm(['slug' => 'acme-2'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertSeeHtml(linkComNovaAba($organizacao->fresh()))
        ->assertDontSeeHtml('href="'.e($enderecoAntigo).'"');

    expect($organizacao->fresh()->urlDoPainel())->toEndWith('/acme-2');
});

/*
|--------------------------------------------------------------------------
| R2 — o link está presente, com o endereço certo, nas três superfícies
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — o link aparece na tela, com o endereço da organização, para quem NÃO passa nos portões.
 *
 * As três linhas são as três cláusulas do requisito: RQ-04 (coluna da listagem), RQ-03 (ficha) e
 * RQ-02 (formulário de edição). A asserção é sobre `href=`, não sobre o texto: uma coluna que
 * exibisse o endereço como texto puro passaria num caso que só olhasse o conteúdo.
 *
 * ## Duas asserções que o HTML sozinho NÃO daria, e sem elas a decisão do usuário fica sem
 * ## falsificador
 *
 * **Coluna, não ação (RQ-04, decidido).** `Action::make()->url(…)->openUrlInNewTab()` emite
 * exatamente o MESMO `href="…" target="_blank"`, pelo mesmo `generate_href_html()` do vendor —
 * então todo cenário de HTML da listagem passaria com a feature implementada como ação por linha,
 * e o motivo da decisão ("a coluna mostra o endereço, então o destino é visível antes do clique")
 * ficaria sem teste. O que separa as duas é o **estado da coluna**: o endereço tem de ser o
 * CONTEÚDO da célula, não só o alvo do link. `assertTableColumnStateSet` é o oráculo, e ele nem
 * compila contra uma ação — não existe coluna para consultar.
 *
 * **Lugar, não só presença (RQ-02, que é cláusula de LUGAR: "na onde tem a parte que cadastra o
 * nome e a slug").** Um link como header action do `EditTenant`, ou numa `Section` própria no
 * rodapé, passaria em todo cenário de HTML e não atenderia a cláusula. A asserção sobe a hierarquia
 * do schema até a `Section` que contém a entrada e afirma o título dela.
 */
it('[CT-04] o link aparece na tela, com o endereco da organizacao', function (string $tela): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    $administrador = administradorDaInstalacao();

    // A persona é o oráculo: ela falha nos dois portões e o link tem de estar lá assim mesmo.
    expect($administrador->canAccessPanel(Filament::getPanel('app')))->toBeFalse()
        ->and($administrador->canAccessTenant($organizacao))->toBeFalse();

    noAdminComo($administrador);

    $esperado = enderecoEsperadoDoPainel($organizacao);

    $componente = match ($tela) {
        'listagem' => Livewire::test(ListTenants::class)->loadTable(),
        'ficha'    => Livewire::test(ViewTenant::class, ['record' => $organizacao->getRouteKey()]),
        'edicao'   => Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()]),
    };

    $componente->assertSeeHtml('href="'.e($esperado).'"');

    if ($tela === 'listagem') {
        // RQ-04: é COLUNA, e o endereço é o conteúdo dela. Uma ação por linha não tem estado de
        // coluna para responder isto.
        $componente->assertTableColumnStateSet('url_do_painel', $esperado, $organizacao);
    }

    if ($tela === 'edicao') {
        // RQ-02 é cláusula de lugar: a entrada vive na seção que cadastra nome e slug.
        $componente->assertSchemaComponentExists(
            'url_do_painel',
            'form',
            fn (TextEntry $entrada): bool => $entrada->getContainer()->getParentComponent() instanceof Section
                && (string) $entrada->getContainer()->getParentComponent()->getHeading() === 'Identificação',
        );
    }
})->with([
    'a listagem de organizações (RQ-04 — coluna)' => 'listagem',
    'a ficha da organização (RQ-03 — view)'       => 'ficha',
    'a edição da organização (RQ-02 — form)'      => 'edicao',
]);

/**
 * CT-05 — a organização inativa também exibe o link, e é o dela.
 *
 * `ativo` é a exclusão lógica desta entidade (não há `SoftDeletes` nem `DeleteAction`), e a
 * listagem mostra as duas. A segunda asserção é a discriminante: uma implementação que resolvesse
 * o link a partir da organização errada — a primeira da página, o tenant corrente — passaria num
 * cenário de uma organização só.
 */
it('[CT-05] a organizacao inativa tambem exibe o link, e e o dela', function (string $tela): void {
    $inativa = tenant('Globex', 'globex', ativo: false);
    $ativa   = tenant('Acme', 'acme');

    noAdminComo(administradorDaInstalacao());

    $componente = match ($tela) {
        'listagem' => Livewire::test(ListTenants::class)->loadTable(),
        'ficha'    => Livewire::test(ViewTenant::class, ['record' => $inativa->getRouteKey()]),
        'edicao'   => Livewire::test(EditTenant::class, ['record' => $inativa->getRouteKey()]),
    };

    $componente->assertSeeHtml('href="'.e(enderecoEsperadoDoPainel($inativa)).'"');

    if ($tela !== 'listagem') {
        $componente->assertDontSeeHtml('href="'.e(enderecoEsperadoDoPainel($ativa)).'"');
    }

    expect(enderecoEsperadoDoPainel($inativa))->not->toBe(enderecoEsperadoDoPainel($ativa));
})->with([
    'a listagem (inativa na listagem)' => 'listagem',
    'a ficha da inativa'               => 'ficha',
    'a edição da inativa'              => 'edicao',
]);

/*
|--------------------------------------------------------------------------
| R3 — nenhum link é renderizado para organização não gravada
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — a tela de cadastro não oferece link, nem depois de o slug ser digitado.
 *
 * O "sem salvar" é o ponto. O campo `nome` é `live(onBlur: true)` e alimenta o `slug` no
 * `afterStateUpdated` (`app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:configure:31`),
 * então o estado do formulário TEM um slug antes de qualquer gravação: uma implementação que
 * montasse o link a partir do state vivo passaria num caso que só abrisse a tela vazia.
 *
 * Duas asserções de ausência, as duas com alvo: a operação dispara renderização de link no caminho
 * feliz (CT-04) e dispara gravação no caminho feliz (CT-20).
 *
 * ## A armadilha, e é por isso que o oráculo é o `href` e nunca o caminho `/app/`
 *
 * O `TenantForm` é o schema do `CreateTenant` **também**, e a `description` da seção
 * (`app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:configure:31`) já contém o literal
 * `/app/{slug}` — **prosa renderizada ao usuário**, não comentário, e é onde está escrito o que o
 * slug significa. Um caso escrito como `assertDontSee('/app/')` nasce VERMELHO contra a
 * implementação correta, e a "correção" óbvia seria apagar a explicação do slug.
 *
 * Então: a ausência afirmada é a do **`href`**, e a terceira asserção é de PRESENÇA — a prosa
 * continua na tela. Ela existe exatamente para travar esse "conserto".
 */
it('[CT-06] a tela de cadastro nao oferece link, nem depois de o slug ser digitado', function (): void {
    noAdminComo(administradorDaInstalacao());

    $enderecoQueNaoDeveExistir = Filament::getPanel('app')->getUrl().'/acme';

    Livewire::test(CreateTenant::class)
        ->fillForm(['nome' => 'Acme', 'slug' => 'acme'])
        ->assertSchemaStateSet(['slug' => 'acme'])
        ->assertDontSeeHtml('href="'.e($enderecoQueNaoDeveExistir).'"')
        ->assertSchemaComponentHidden('url_do_painel', 'form')
        // PRESENÇA, e não ausência: a prosa que explica o slug fica. Sem esta linha, um agente
        // futuro "conserta" a ausência apagando a `description` da seção.
        ->assertSee('O slug vira o endereço do painel de negócio: /app/{slug}.');

    $this->assertDatabaseMissing('tenants', ['slug' => 'acme']);
});

/**
 * CT-20 — o cadastro continua gravando, e o link aparece na edição do que foi gravado.
 *
 * É o par do gate de tela de escrita para a rota `create`: uma entrada condicionada a "o registro
 * existe" é exatamente o tipo de condição que estoura com registro nulo e derruba a gravação com a
 * tela abrindo verde.
 */
it('[CT-20] o cadastro continua gravando, e o link aparece na edicao do que foi gravado', function (): void {
    noAdminComo(usuarioComPapel('master_global'));

    Livewire::test(CreateTenant::class)
        ->fillForm(['nome' => 'Acme', 'slug' => 'acme', 'ativo' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $gravada = Tenant::where('slug', 'acme')->firstOrFail();

    expect($gravada->ativo)->toBeTrue();

    Livewire::test(EditTenant::class, ['record' => $gravada->getRouteKey()])
        ->assertSeeHtml(linkComNovaAba($gravada));
});

/*
|--------------------------------------------------------------------------
| R4 — o link abre em nova aba nas três superfícies
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — o link da tela abre em nova aba.
 *
 * O oráculo é a ADJACÊNCIA (`href="{endereço}" target="_blank"`), e não o `target` solto: o topbar
 * do Filament já o emite em toda página do painel, então a asserção solta é decorativa.
 */
it('[CT-07] o link da tela abre em nova aba', function (string $tela): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    noAdminComo(administradorDaInstalacao());

    $componente = match ($tela) {
        'listagem' => Livewire::test(ListTenants::class)->loadTable(),
        'ficha'    => Livewire::test(ViewTenant::class, ['record' => $organizacao->getRouteKey()]),
        'edicao'   => Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()]),
    };

    $componente->assertSeeHtml(linkComNovaAba($organizacao));
})->with([
    'a listagem (RQ-04)' => 'listagem',
    'a ficha (RQ-03)'    => 'ficha',
    'a edição (RQ-02)'   => 'edicao',
]);

/*
|--------------------------------------------------------------------------
| R5 — o link navega, não autoriza
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — seguir o link devolve o que os portões decidem.
 *
 * `GET` real, por fora do componente de UI: é o gate de camada da regra de autorização — uma
 * barreira que existisse só no `Resource` ficaria verde em qualquer teste de componente.
 *
 * **403 e 404 são códigos diferentes, e a diferença é deliberada.** O portão 1 (`canAccessPanel`)
 * responde 403 (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:authenticate:15`);
 * o portão 2 (`canAccessTenant`) responde 404
 * (`vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:handle:13`), porque um 403
 * confirmaria que a organização EXISTE e bastaria varrer slugs para enumerar os clientes da
 * instalação — o mesmo argumento de `tests/Tenancy/AdminDaOrganizacaoTest.php:98`.
 */
it('[CT-08] seguir o link devolve o que os portoes decidem', function (string $persona, int $resposta): void {
    $organizacao = tenant('Acme', 'acme');

    $usuario = match ($persona) {
        'mestre'      => usuarioComPapel('master_global'),
        'admin'       => administradorDaInstalacao(),
        'sem_vinculo' => usuarioComPapel('panel_user', $organizacao),
        'vinculada'   => tap(usuarioComPapel('admin_app', $organizacao), fn (User $u) => $u->tenants()->attach($organizacao)),
    };

    $this->actingAs($usuario)
        ->get((string) $organizacao->urlDoPainel())
        ->assertStatus($resposta);
})->with([
    'o mestre da instalação (passa nos dois)'                      => ['mestre', 200],
    'o administrador da instalação (barra no portão 1 — 403)'      => ['admin', 403],
    'a operadora do negócio sem vínculo (barra no portão 2 — 404)' => ['sem_vinculo', 404],
    'a administradora da organização, vinculada (passa nos dois)'  => ['vinculada', 200],
]);

/**
 * CT-09 — a negação do vínculo fica registrada, e o acesso legítimo não registra nada.
 *
 * O não-efeito tem destinatário: o canal `tenancy` é o MESMO nos dois acessos, e o caminho de
 * negação grava nele — é o que a primeira metade do caso prova. A segunda não é feita num mundo sem
 * canal, é feita no mundo onde o aviso acabou de ser visto.
 *
 * `TestHandler` trocado no logger real, como `tests/Tenancy/PaletaDaOrganizacaoTest.php:190`.
 *
 * **`flushSession()` entre os dois acessos, e sem ele o caso mede o arnês.** O painel de negócio
 * tem `AuthenticateSession` na pilha: ele grava o hash da senha do usuário na sessão e DESLOGA
 * quando o hash do request seguinte não corresponde. Com a mesma sessão atravessando as duas
 * personas, o segundo `GET` vinha 302 para o login em vez de 200 — falha que se lê como "a
 * administradora vinculada não entra", quando a única coisa errada era a sessão da anterior.
 */
it('[CT-09] a negacao do vinculo fica registrada, e o acesso legitimo nao registra nada', function (): void {
    $organizacao = tenant('Acme', 'acme');

    $semVinculo = usuarioComPapel('panel_user', $organizacao, email: 'sem-vinculo@example.com');
    $vinculada  = usuarioComPapel('admin_app', $organizacao, email: 'vinculada@example.com');
    $vinculada->tenants()->attach($organizacao);

    $registros = new TestHandler;
    Log::channel('tenancy')->getLogger()->setHandlers([$registros]);

    $negouOVinculo = fn (): bool => $registros->hasWarningThatPasses(
        fn (LogRecord $registro): bool => str_starts_with($registro->message, '[User@canAccessTenant]')
            && ($registro->context['motivo'] ?? null) === 'sem_vinculo'
            && ($registro->context['tenant_id'] ?? null) === $organizacao->getKey(),
    );

    $this->actingAs($semVinculo)->get((string) $organizacao->urlDoPainel())->assertNotFound();

    expect($negouOVinculo())->toBeTrue('o canal `tenancy` não registrou a negação do vínculo');

    $registros->clear();
    fronteiraDeRequest();
    $this->flushSession();

    $this->actingAs($vinculada)->get((string) $organizacao->urlDoPainel())->assertSuccessful();

    expect($negouOVinculo())->toBeFalse('o acesso legítimo registrou aviso de negação');
});

/**
 * CT-10 — a recusa não tranca o administrador fora da administração.
 *
 * A saída do estado de erro. O `AuthenticateSession` está na pilha do painel de negócio: um clique
 * que invalidasse a sessão deixaria o administrador fora do `/admin` também — o defeito que nenhum
 * cenário de 403/404 isolado enxerga, porque cada um, sozinho, está certo.
 */
it('[CT-10] a recusa nao tranca o administrador fora da administracao', function (): void {
    $organizacao   = tenant('Acme', 'acme');
    $administrador = administradorDaInstalacao();

    $this->actingAs($administrador);

    $this->get('/admin/organizacoes')->assertSuccessful();

    fronteiraDeRequest();
    $this->get((string) $organizacao->urlDoPainel())->assertForbidden();

    $this->assertAuthenticatedAs($administrador);

    fronteiraDeRequest();
    $this->get('/admin/organizacoes')->assertSuccessful();
});

/**
 * CT-11 — renderizar o link não cria vínculo nem papel.
 *
 * O não-efeito tem alvo: a pivot `tenant_user` existe, a organização existe, o usuário existe, e há
 * um caminho no kit que GRAVA ali (o `UsersRelationManager` da própria tela). A contagem é tirada
 * antes e depois, e não "nenhum registro" genérico.
 */
it('[CT-11] renderizar o link nao cria vinculo nem papel', function (): void {
    $organizacao   = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);
    $administrador = administradorDaInstalacao();

    $vinculos = fn (): int => DB::table('tenant_user')->where('user_id', $administrador->id)->count();
    $papeis   = fn (): int => DB::table(pivotDePapeis())->where('model_id', $administrador->id)->count();

    [$vinculosAntes, $papeisAntes] = [$vinculos(), $papeis()];

    noAdminComo($administrador);

    Livewire::test(ListTenants::class)->loadTable();
    Livewire::test(ViewTenant::class, ['record' => $organizacao->getRouteKey()]);
    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()]);

    expect($vinculos())->toBe($vinculosAntes)
        ->and($papeis())->toBe($papeisAntes);
});

/*
|--------------------------------------------------------------------------
| R6 — o link não é campo
|--------------------------------------------------------------------------
*/

/** CT-12 — a edição continua gravando os campos da organização. */
it('[CT-12] a edicao continua gravando os campos da organizacao', function (): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    noAdminComo(usuarioComPapel('master_global'));

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->fillForm(['nome' => 'Acme Brasil', 'slug' => 'acme-brasil'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organizacao->fresh())
        ->nome->toBe('Acme Brasil')
        ->slug->toBe('acme-brasil');
});

/**
 * CT-13 — salvar sem alterar nada não muda nada.
 *
 * Três armadilhas num caso, todas da EDIÇÃO e nenhuma da criação: (i) a unicidade do slug acusando
 * colisão do registro consigo mesmo; (ii) a entrada nova entrando no `dehydrate` e escrevendo
 * alguma coluna no save — é por isso que a entrada do link é `TextEntry` e não `TextInput`
 * desabilitado; (iii) a linha `inativa`, que fecha a célula `inativa × gravar`: o registro
 * logicamente excluído ainda tem de funcionar na operação de escrita.
 *
 * O oráculo é o AGREGADO PERSISTIDO — os atributos do registro relido —, não o retorno da chamada.
 */
it('[CT-13] salvar sem alterar nada nao muda nada', function (bool $ativo): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme', 'ativo' => $ativo]);
    $antes       = $organizacao->fresh()->getAttributes();

    noAdminComo(usuarioComPapel('master_global'));

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    $depois = $organizacao->fresh()->getAttributes();

    unset($antes['updated_at'], $depois['updated_at']);

    expect($depois)->toBe($antes);
})->with([
    'ativa (estado normal)'      => [true],
    'inativa (exclusão lógica)'  => [false],
]);

/*
|--------------------------------------------------------------------------
| R7 — a URL sai do slug do próprio registro: a listagem não paga query por linha
|--------------------------------------------------------------------------
*/

/**
 * CT-14 — resolver o endereço das organizações da página não custa consulta nenhuma.
 *
 * INVARIÂNCIA à cardinalidade, e não um número: o número de queries da listagem é do plano, não do
 * requisito, e envelhece com qualquer mudança de painel. A invariância é derivada de RQ-05 ("a URL
 * é derivada do slug", isto é, do próprio registro) e é o que distingue uma coluna que lê o
 * atributo de uma que consulta por linha.
 *
 * ## O cenário do `04` foi escrito sobre uma listagem que já paga por linha — e isto foi medido
 *
 * O `04` escreveu este CT como "a listagem custa o mesmo com uma e com cinco organizações". Medido
 * nas duas pontas do diff, com o mesmo arnês:
 *
 * | | 1 organização | 5 organizações |
 * |---|---|---|
 * | **antes** da feature (`git stash push -- app/`) | 33 | 53 |
 * | **depois** | 33 | 53 |
 *
 * A coluna nova custa **zero** — o que o `## Modelo de Execução` do plano afirma, e está
 * confirmado. Mas a listagem **já** crescia 5 consultas por linha antes da feature (autorização de
 * `ViewAction`/`EditAction` por registro, entre outras), então o oráculo do `04` nasceria VERMELHO
 * contra a implementação correta, medindo um N+1 de terceiro que a feature não introduziu e não
 * pode consertar (está fora de escopo). Divergência registrada no `03-progresso.md`.
 *
 * O que ficou no lugar é o MESMO oráculo — invariância à cardinalidade — aplicado ao que a feature
 * de fato possui: a resolução do endereço. Zero consultas para uma, zero para cinco. É isto que
 * mata M22 (a coluna resolvendo por relação ou consulta, uma por linha): um resolvedor assim
 * marcaria N aqui, com os registros já carregados.
 *
 * A contagem é feita com os registros JÁ hidratados — `->all()` antes de ligar o log —, senão o
 * caso mediria a query da própria listagem em vez da resolução do endereço.
 */
it('[CT-14] resolver o endereco das organizacoes da pagina nao custa consulta nenhuma', function (): void {
    Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    noAdminComo(administradorDaInstalacao());

    $custoDeResolver = function (int $quantas): int {
        $organizacoes = Tenant::query()->limit($quantas)->get()->all();

        expect($organizacoes)->toHaveCount($quantas);

        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($organizacoes as $organizacao) {
            expect($organizacao->urlDoPainel())->toEndWith('/'.$organizacao->slug);
        }

        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    };

    // Aquecimento: o primeiro `getUrl()` do processo resolve o painel e a rota.
    $custoDeResolver(1);

    $comUma = $custoDeResolver(1);

    foreach (['beta', 'gama', 'delta', 'epsilon'] as $slug) {
        Tenant::factory()->create(['nome' => ucfirst($slug), 'slug' => $slug]);
    }

    $comCinco = $custoDeResolver(5);

    expect($comUma)->toBe(0, "resolver o endereço de uma organização custou {$comUma} consultas")
        ->and($comCinco)->toBe($comUma, "a resolução passou de {$comUma} para {$comCinco} consultas ao ganhar quatro linhas");
});

/**
 * CT-15 — cada linha exibe o endereço da sua própria organização.
 *
 * Mata o mutante OPOSTO do CT-14: o endereço resolvido uma vez e repetido em todas as linhas custa
 * o mesmo e está errado. Sem este caso, "zero query nova" ficaria satisfeito por uma listagem em
 * que as cinco linhas apontam para a mesma organização.
 */
it('[CT-15] cada linha exibe o endereco da sua propria organizacao', function (): void {
    $organizacoes = collect(['acme', 'beta', 'gama', 'delta', 'epsilon'])
        ->map(fn (string $slug): Tenant => Tenant::factory()->create(['nome' => ucfirst($slug), 'slug' => $slug]));

    noAdminComo(administradorDaInstalacao());

    $componente = Livewire::test(ListTenants::class)->loadTable();

    foreach ($organizacoes as $organizacao) {
        $componente->assertSeeHtml(linkComNovaAba($organizacao));
    }
});

/*
|--------------------------------------------------------------------------
| R8 — o slug que compõe a URL continua restrito
|--------------------------------------------------------------------------
*/

/**
 * CT-16 — a edição recusa slug que não é slug, e não altera o gravado.
 *
 * Uma inválida por linha: combinar duas deixaria a primeira validação a disparar mascarando a
 * segunda. O não-efeito tem alvo — a organização existe, gravada com `acme`, e o caminho feliz
 * (CT-12) altera esse mesmo campo.
 *
 * A validação é do `TenantForm` e NÃO é desta feature; o caso afirma a DEPENDÊNCIA dela: sem
 * `->alphaDash()` o `href` de uma tela de administração passa a ser escolhido por quem cadastra a
 * organização, e removê-lo no futuro fica vermelho aqui e não só na tela de cadastro.
 *
 * ## A linha `acento` do `04` NÃO existe, e a verificação é esta
 *
 * O `04` listou `organização` como partição inválida. `alpha_dash` do Laravel é **unicode-aware**:
 * sem o argumento `ascii` a regra é `/\A[\pL\pM\pN_-]+\z/u`
 * (`vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:validateAlphaDash:403`),
 * e `ç`/`ã` são `\pL`. O slug acentuado **grava**, e a linha nasceria vermelha contra a
 * implementação correta. Trocar `->alphaDash()` por `->alphaDash(ascii: true)` seria mexer numa
 * validação que não é desta feature para fazer um caso passar — recusado.
 *
 * As duas linhas que entraram no lugar cobrem o que a linha do acento pretendia cobrir e o
 * `\pL` **não** aceita: o **ponto** e o **percent-encoding**, que são os metacaracteres de
 * caminho e de escape de URL. O acento passou para CT-18, do lado VÁLIDO: ele grava, e o link
 * segue o gravado — percent-encodado pelo gerador, que é o comportamento correto.
 * Divergência registrada no `03-progresso.md`.
 */
it('[CT-16] a edicao recusa slug que nao e slug, e nao altera o gravado', function (string $slug): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => 'acme']);

    noAdminComo(usuarioComPapel('master_global'));

    Livewire::test(EditTenant::class, ['record' => $organizacao->getRouteKey()])
        ->fillForm(['slug' => $slug])
        ->call('save')
        ->assertHasFormErrors(['slug']);

    expect($organizacao->fresh()->slug)->toBe('acme');
})->with([
    'caminho relativo'       => '../outra',
    'separador de caminho'   => 'acme/painel',
    'espaço'                 => 'acme painel',
    'início de query string' => 'acme?x=1',
    'ponto'                  => 'acme.painel',
    'percent-encoding'       => 'acme%2fpainel',
    'vazio'                  => '',
]);

/**
 * CT-17 — o comprimento do slug é inclusivo em 120.
 *
 * BVA 3-valores (119/120/121) e não 2-valores: 2-valores não distingue `maxLength(120)` de
 * `maxLength(121)`, e 120 é o único número literal que o requisito herda do campo.
 *
 * **E o 120 não tem rede embaixo.** A coluna é `$table->string('slug')`, ou seja 255
 * (`database/migrations/0001_01_01_000020_create_tenants_table.php:29`). O teto de 120 vive APENAS
 * no `TenantForm`: se ele sair, nada estoura, nada avisa, e um slug de 200 caracteres passa a
 * compor a URL. Este caso escreve o número literal e é a única guarda desse teto em todo o kit.
 */
it('[CT-17] o comprimento do slug e inclusivo em 120', function (int $tamanho, bool $gravado): void {
    noAdminComo(usuarioComPapel('master_global'));

    $slug = str_repeat('a', $tamanho);

    $componente = Livewire::test(CreateTenant::class)
        ->fillForm(['nome' => 'Acme', 'slug' => $slug, 'ativo' => true])
        ->call('create');

    $gravado
        ? $componente->assertHasNoFormErrors()
        : $componente->assertHasFormErrors(['slug']);

    expect(Tenant::where('slug', $slug)->exists())->toBe($gravado);
})->with([
    'borda−1 (119)' => [119, true],
    'borda (120)'   => [120, true],
    'borda+1 (121)' => [121, false],
]);

/**
 * CT-18 — o link segue o slug gravado, sem normalizar.
 *
 * O cenário por fora do componente de UI para esta regra: a gravação é feita por factory, sem
 * formulário, e o oráculo é o INVARIANTE DAS DUAS LEITURAS — seja o slug barrado no model ou apenas
 * no formulário, a feature não CONSERTA o que está gravado.
 *
 * `ACME-Brasil` passa pelo `alphaDash` (maiúscula é permitida) mas não pelo `Str::slug` — um
 * gerador que normalizasse produziria `/acme-brasil`, um endereço que NÃO EXISTE, e o link
 * nasceria quebrado sem ninguém notar. É valor discriminante escolhido para isso.
 *
 * `organização` é a segunda linha, e ela veio da linha `acento` do CT-16 no `04` — que estava do
 * lado errado da fronteira: `alpha_dash` é unicode-aware e o slug acentuado GRAVA (ver o docblock
 * de CT-16). O invariante é o mesmo dos dois lados: o link segue o gravado, byte a byte.
 *
 * **Medido, e não suposto: o gerador NÃO percent-encoda.** `Panel::getUrl()` devolve
 * `http://…/app/organização` com o UTF-8 cru, e o `href` sai assim (o `e()` de
 * `generate_href_html()` escapa HTML, não URL). Navegador e servidor fazem o encoding do
 * caminho, então o link funciona — mas quem for comparar strings de endereço em outro lugar do kit
 * precisa saber que a forma canônica aqui é a crua.
 */
it('[CT-18] o link segue o slug gravado, sem normalizar', function (string $slug): void {
    $organizacao = Tenant::factory()->create(['nome' => 'Acme', 'slug' => $slug]);

    noAdminComo(administradorDaInstalacao());

    expect($organizacao->urlDoPainel())->toEndWith('/'.$slug);

    Livewire::test(ViewTenant::class, ['record' => $organizacao->getRouteKey()])
        ->assertSeeHtml(linkComNovaAba($organizacao));
})->with([
    'caixa preservada (o Str::slug destruiria)' => 'ACME-Brasil',
    'acento gravado, endereço encodado'         => 'organização',
]);
