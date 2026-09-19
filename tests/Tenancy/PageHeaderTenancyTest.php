<?php

use App\Filament\App\Resources\Users\Pages\ViewUser;
use App\Filament\App\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
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
 * CT-03 — as telas de View e Edit de organização renderizam o cabeçalho, com o nome dentro dele.
 *
 * Realiza as linhas `admin | EditTenant` e `admin | ViewTenant` do `Esquema` desenhado; as duas de
 * `User` no /admin ficam em `tests/Kit/PageHeaderTest.php:[CT-03]` e a `app | ViewUser` no
 * `[CT-03]` de baixo, neste mesmo arquivo. A linha `app | EditUser` continua sem caso.
 *
 * O `ViewTenant` é o caso mais informativo da entrega: é o único `ViewRecord` do kit que já
 * renderiza RelationManagers (`TenantResource::getRelations():129-134` → `UsersRelationManager`) e
 * widgets de cabeçalho. Este caso prova que as três coisas convivem — o cabeçalho do pacote, os
 * widgets e as abas ocupam regiões de DOM disjuntas.
 */
it('[CT-03] renderiza o cabecalho nas telas de organizacao', function (string $sufixo): void {
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
 * CT-45/CT-46 — o cabecalho, os widgets e o relation manager do `ViewTenant` ocupam regioes
 * DISJUNTAS.
 *
 * CT-46: o `fph-root` existe, o relation manager esta na pagina e NAO dentro dele. Fica de fora a
 * linha de ORDEM ("a barra de abas aparece depois do fechamento da regiao") — e o motivo esta
 * medido abaixo, no descarte do oraculo de posicao.
 *
 * CT-45: a ultima assercao cobre a metade de presenca ("a pagina contem os widgets de cabecalho").
 * As outras duas linhas daquele cenario — nenhum widget DENTRO da regiao, e as acoes de cabecalho
 * dentro dela — nao estao implementadas.
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
it('[CT-45/CT-46] mantem o relation manager fora da regiao do cabecalho na view da organizacao', function (): void {
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
 * CT-15 — a ficha do /app abre para alguém DA organização e responde 404 para quem governa a
 * instalação.
 *
 * A primeira metade realiza a linha POSITIVA do `Esquema` desenhado (`admin_app` **com** a
 * permissão → 200); a linha negativa dele está no `[CT-15]` de baixo. Falta, nas duas, a asserção
 * de `fph-root` que a legenda da matriz de R4 exige em toda célula.
 *
 * A segunda metade — o 404 para quem governa a instalação — **não tem cenário próprio no `04`**, e
 * de propósito: R5 manda consultar a autorização DIRETO (CT-17, CT-19), porque pela tela quem
 * responde é o recorte da query, e o caso ficaria verde com a sobrescrita inteira removida. Fica
 * aqui como o que ele é: a evidência de que o recorte da query está de pé, não a da barreira.
 *
 * O 404 (e não 403) é o route binding falando: `ViewRecord::mount():67` chama `resolveRecord()`
 * ANTES de `authorizeAccess()`, e a query do resource já não contém o alvo. Quem governa não
 * existe para este painel.
 *
 * Molde: `tests/Tenancy/FronteiraDoAdminAppTest.php:[CT-02]:109-125`.
 */
it('[CT-15] abre a ficha de quem e da organizacao e responde 404 para quem governa a instalacao', function (): void {
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
 * CT-16 — a URL direta para conta de OUTRA organização responde 404.
 *
 * A fronteira que importa, e a que um caso de uma organização só não consegue medir.
 *
 * Do cenário desenhado ficam de fora duas linhas: "o corpo não contém nenhum `fph-root`" e o par
 * positivo no mesmo cenário ("a mesma pessoa, abrindo a ficha de um colega da Acme, recebe 200 com
 * `fph-root`") — este último existe, com outro arranjo, no `[CT-15]` acima.
 */
it('[CT-16] responde 404 na ficha de conta de outra organizacao', function (): void {
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
 * CT-17 — `getViewAuthorizationResponse()` nega DIRETO o alvo que governa a instalação.
 *
 * Primeira linha do cenário desenhado ("ela nega"), mais o par positivo. O efeito — o aviso no
 * canal de autenticação — está no caso irmão, logo abaixo, com o mesmo rótulo.
 *
 * O ator e o alvo são pessoas distintas, como o `04` exige depois da revisão adversarial (mutante
 * M58). O que o cenário desenhado tem e aqui não está: o alvo pertencer também à organização.
 *
 * Chamada direta, com o alvo em mãos — nunca pela tela. Caso que passa pela tela mede o recorte da
 * query (o 404 do `[CT-15]` que abre a ficha pela URL), não a barreira: ele ficaria verde com a
 * sobrescrita inteira removida, que é exatamente a mutação que este caso existe para matar.
 *
 * É a mesma forma do caso irmão de `getEditAuthorizationResponse()`, e o motivo de as duas
 * existirem está no ADR-07: a edição tinha duas camadas e a visualização teria uma só, o que
 * deixaria a tela de LEITURA mais permissiva que a de ESCRITA sobre o mesmo alvo.
 */
it('[CT-17] nega direto a visualizacao de quem governa a instalacao no painel app', function (): void {
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
 * Linhas 2 a 4 do cenário desenhado, com duas ressalvas: o `shouldHaveReceived` sem `->once()` não
 * afirma "EXATAMENTE um aviso" (passa com dez, que é o defeito que o `04` nomeia), e o contexto é
 * conferido só pelo `alvo_id` — o `ator_id` que o cenário exige não é asserido. A direção
 * complementar ("nenhum aviso no caminho permitido", CT-18 e CT-19) não tem caso.
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

    /*
     * `->once()` nao e detalhe: sem ele o caso passa com dez avisos, e log duplicado em barreira
     * de autorizacao e ruido que esconde o evento real na trilha de /infra.
     */
    $espiao->shouldHaveReceived('warning')->once()->withArgs(function (string $mensagem, array $context) use ($gil): bool {
        return str_contains($mensagem, '[UserResource@getViewAuthorizationResponse]')
            && $context['alvo_id'] === $gil->id
            && $context['motivo'] === 'alvo_governa_a_instalacao'
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'gil@example.com');
    });
});

/**
 * EXTRA — a ficha do /app NÃO mostra as organizações da pessoa.
 *
 * **Não realiza nenhum CT do `04`.** O cenário mais próximo, CT-51, afirma que o conjunto de
 * RÓTULOS de `fph-metadata` e de `fph-badges` do /app é subconjunto do do /admin — região do
 * cabeçalho, oráculo de conjunto. Aqui a afirmação é sobre o CORPO da ficha (a seção "Vinculos" e o
 * nome da outra organização), o que é uma fronteira de informação legítima e sem cenário desenhado.
 * CT-51 segue sem teste.
 *
 * O par de `[CT-53]` de `tests/Kit/PageHeaderTest.php`, e é este que consegue reprovar: só aqui
 * existe uma segunda organização para vazar.
 *
 * Quem administra a Acme pode ver quem é da Acme — isso é a feature. O que ele não pode é
 * descobrir que aquela pessoa também é da Globex. O recorte de `getEloquentQuery():197` decide
 * QUEM aparece; ele não decide o que a ficha conta sobre quem aparece.
 */
it('[EXTRA] nao conta na ficha do app que a pessoa pertence a outra organizacao', function (): void {
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
 * CT-03 — o cabeçalho do /app renderiza na ficha de usuário: a linha `app | ViewUser` do `Esquema`
 * desenhado.
 *
 * A asserção extra sobre o `data:` URI repete, no painel de negócio, metade do que `[CT-07]` de
 * `tests/Kit/PageHeaderTest.php` afirma no /admin.
 *
 * Fecha a RQ-02 do lado do painel de negócio: sem este caso, `use HasPageHeader` removido só da
 * `ViewUser` do /app passaria despercebido — todos os outros casos de cabeçalho rodam no /admin.
 */
it('[CT-03] renderiza o cabecalho na ficha de usuario do painel de negocio', function (): void {
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
 * CT-15 — a permissão `View:User` decide a ficha também no /app.
 *
 * Linha NEGATIVA do `Esquema` desenhado (`admin_app` **sem** a permissão → 403); a positiva está no
 * `[CT-15]` de cima. A terceira linha — `panel_user`, que nunca recebe a permissão — não tem caso,
 * e as asserções de "o corpo não contém o nome do alvo" e "nenhum `fph-root`" também não.
 *
 * `admin_app` só existe nesta suíte (`.ai/rules/testes.md`), então o par tem/não-tem do painel de
 * negócio não cabe em `tests/Kit`.
 */
it('[CT-15] recusa a ficha no painel de negocio para quem nao tem View User', function (): void {
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
 * CT-30 — a ficha existe nos dois painéis com a rota `view` registrada, e a organização também.
 *
 * Complementa `[CT-30]` de `tests/Kit/PageHeaderTest.php`: lá a asserção é sobre a ORDEM das
 * chaves; aqui é a terceira linha do `Então` desenhado — o caminho da rota `view` —, no painel com
 * organização, que é onde o prefixo `{tenant}` entra na URL.
 */
it('[CT-30] serve a rota view do painel de negocio sob o prefixo da organizacao', function (): void {
    $acme = tenant('Acme', 'acme');

    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    noPainelDa($acme);

    expect(UserResource::getUrl('view', ['record' => $ana], panel: 'app'))
        ->toContain('/app/acme/users/'.$ana->getRouteKey());
});

/**
 * CT-10 — a cor das iniciais da organizacao sai de `CorPrimaria::resolver()`, com a precedencia
 * dele.
 *
 * ## O oraculo anterior nao matava o que o docblock dizia matar
 *
 * A primeira versao deste caso afirmava so `toContain('--fph-avatar-background')`, e o
 * `/code-review` mediu: substituir o corpo inteiro de `TenantHeader::paleta()` por
 * `return Color::generatePalette('#00ff00');` — sem chamar `CorPrimaria::resolver()` — deixava a
 * suite 12/12 VERDE. "Alguma paleta resolveu" nao e a afirmacao; a afirmacao e QUAL.
 *
 * As tres organizacoes sao as tres celulas que importam, e nenhuma delas e amostra:
 *
 *   A (so hex)          -> um tom qualquer, mas DETERMINADO pelo hex
 *   B (so nome)         -> tom DIFERENTE do de A. Mata a paleta cravada: com ela, A == B
 *   C (hex E nome)      -> tom IGUAL ao de A. Mata a precedencia invertida: com ela, C == B
 *
 * A precedencia nao e escolha deste cabecalho — e a de `CorPrimaria::resolver():80`, a MESMA
 * funcao que o `/app` usa no `bootUsing()` (`app/Providers/Filament/AppPanelProvider.php:185`).
 * Um cabecalho que discordasse do painel que descreve seria defeito silencioso.
 *
 * E o hex passa por `Color::generatePalette()` porque `Header::getIdentityStyles():215-235` le
 * `[600] ?? [500]` de um ARRAY. Hex cru devolveria `null` de `FilamentColor::getColor()` e o tom
 * sumiria sem erro.
 */
it('[CT-10] tinge as iniciais com a cor da organizacao, respeitando a precedencia do kit', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');

    $tom = function (array $cores) use ($admin): string {
        $org = Tenant::factory()->create([
            'nome'  => 'Acme Ltda',
            'slug'  => 'acme-'.bin2hex(random_bytes(4)),
            /*
             * Logo ORFAO, nao nulo: o path aponta para arquivo que nao existe no disco. E o caso
             * que exercita o `Storage::disk('public')->exists()` de `Tenant::urlDaLogo():138`, que
             * existe justamente para nao renderizar <img> quebrado depois de um restore de banco
             * sem o storage. Com `null` o metodo devolveria cedo e o `exists()` nunca rodaria.
             */
            'logo'  => 'logos/apagada.png',
            ...$cores,
        ]);

        $cabecalho = regiaoDoCabecalho(
            $this->actingAs($admin)
                ->get("/admin/organizacoes/{$org->getRouteKey()}")
                ->assertSuccessful()
                ->getContent(),
        );

        expect($cabecalho)->not->toBe('')
            // Sem logo servivel, o slot e o de iniciais, nunca um <img>.
            ->and(str_contains($cabecalho, '<img'))->toBeFalse()
            ->and($cabecalho)->toContain('>AL<');

        expect(preg_match('/--fph-avatar-background:\s*([^;]+);/', $cabecalho, $m))->toBe(
            1,
            'o cabecalho nao tingiu as iniciais',
        );

        return trim($m[1]);
    };

    $soHex  = $tom(['cor_primaria' => '#7c3aed', 'cor_primaria_nome' => null]);
    $soNome = $tom(['cor_primaria' => null, 'cor_primaria_nome' => 'Emerald']);
    $ambos  = $tom(['cor_primaria' => '#7c3aed', 'cor_primaria_nome' => 'Emerald']);

    expect($soHex)->not->toBe($soNome, 'a paleta esta cravada: duas cores diferentes deram o mesmo tom')
        ->and($ambos)->toBe($soHex, 'a precedencia inverteu: o nome venceu o hexadecimal')
        ->and($ambos)->not->toBe($soNome);
});

/**
 * [EXTRA] — o par de `[CT-20]` no painel de negócio: a ação do trait não entrega a outra
 * organização.
 *
 * Achado F-01 da auditoria do Filament Blueprint. `[CT-20]` de `tests/Kit/PageHeaderTest.php`
 * trava a lista nominal de chaves que `$wire.getPageHeaderRecord()` devolve — mas só no `/admin`.
 * E é no `/app` que existe uma fronteira a proteger: a ficha de lá omite as organizações **de
 * propósito**, e essa omissão é de apresentação.
 *
 * O risco que este caso fecha não é hipotético: um `->with('tenants')` futuro em
 * `getEloquentQuery()`, ou um accessor entrando em `$appends`, entregaria a organização alheia por
 * `$wire.getPageHeaderRecord()` **sem tocar em nenhuma tela** — e o `[CT-18]`, que mede o HTML,
 * continuaria verde.
 *
 * A lista é fechada com `toBe()`, e não `toContain()`: coluna nova que passe a sair por aqui
 * deixa isto vermelho, que é o momento certo de decidir.
 */
it('[EXTRA] nao entrega a organizacao alheia pela acao do trait no painel de negocio', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex Confidencial', 'globex');

    $ana = papelNaOrganizacao(usuario('ana@example.com'), 'admin_app', $acme);
    $ana->tenants()->attach($acme->id);

    $beto = papelNaOrganizacao(usuario('beto@example.com'), 'panel_user', $acme);
    papelNaOrganizacao($beto, 'panel_user', $globex);
    $beto->tenants()->attach([$acme->id, $globex->id]);

    // GET real primeiro: `noPainelBootado('app')` morre no boot do Breezy, e sem painel bootado o
    // componente resolve o painel errado. Ver `.ai/rules/testes.md`.
    $this->actingAs($ana)->get("/app/acme/users/{$beto->getRouteKey()}")->assertSuccessful();

    $retorno = Livewire::actingAs($ana)
        ->test(ViewUser::class, ['record' => $beto->getRouteKey()])
        ->call('getPageHeaderRecord')
        ->effects['returns'][0] ?? null;

    expect($retorno)->toBeArray();

    $chaves = array_keys($retorno);

    sort($chaves);

    expect($chaves)->toBe([
        'aprovacao_pendente', 'ativo', 'avatar_url', 'created_at', 'deleted_at', 'email',
        'email_verified_at', 'id', 'name', 'origem', 'updated_at', 'uuid',
    ])
        // E nenhuma relação carregada: é por ela que a organização alheia sairia.
        ->and(json_encode($retorno, JSON_THROW_ON_ERROR))->not->toContain('Globex');
});
