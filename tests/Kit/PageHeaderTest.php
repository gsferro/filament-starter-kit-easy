<?php

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\UserResource as UserResourceAdmin;
use App\Filament\App\Resources\Users\UserResource as UserResourceApp;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use MortalKiller\FilamentPageHeader\PageHeaderPlugin;
use Symfony\Component\Finder\Finder;

/**
 * O cabeçalho rico de `mortalkiller/filament-page-header` nas telas de registro.
 *
 * Ver `wikis/specs/feat/page-header-nas-telas-de-registro/page-header-nas-telas-de-registro/`.
 *
 * ## A região do oráculo, e por que ela é pequena de propósito
 *
 * Todo caso que afirma sobre HTML renderizado roda sobre `regiaoDoCabecalho()`, que recorta
 * EXATAMENTE o `<header class="fph-header …">…</header>` que o pacote emite
 * (`vendor/mortalkiller/filament-page-header/resources/views/header.blade.php:21-32`).
 *
 * Isso não é preciosismo: a rodada anterior desta base produziu um oráculo que rodava sobre a
 * cauda inteira da página, e QUALQUER texto o satisfazia — 46 casos verdes sobre nada. Predicado
 * sobre região grande é predicado que não afirma. O controle negativo do primeiro caso prova que
 * o recortador recorta.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| RQ-02, RQ-03 — o cabeçalho aparece nas telas de registro
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — as telas de registro de USUÁRIO do /admin renderizam o cabeçalho do pacote, com o nome
 * do registro DENTRO dele.
 *
 * As telas de ORGANIZAÇÃO ficam em `tests/Tenancy/PageHeaderTenancyTest.php`: `TenantResource`
 * exige `kit.tenancy.enabled` (`TenantResource::canAccess():106-109`), e esta suíte roda
 * single-tenant — aqui elas respondem 403.
 *
 * O `assertSee` do nome sozinho não serviria: o nome aparece no título da aba, nas migalhas e no
 * formulário. O oráculo é a adjacência — o nome dentro da região que só existe quando o pacote
 * renderizou.
 *
 * Mutações que este caso mata:
 *   M01 — `use HasPageHeader` removido da página: a região some.
 *   M02 — a classe `Schemas\{Model}Header` renomeada: `getPageHeaderSchemaClass()` não acha, o
 *         schema fica vazio e `getHeader()` cai em `parent::getHeader()` — a região some.
 *   M03 — `PageHeaderPlugin::make()` fora do provider: `pageHeaderIsEnabled()` vira falso, idem.
 */
it('[CT-01] renderiza o cabecalho do pacote nas telas de registro do admin', function (string $rota, string $esperado): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');

    $alvo = User::factory()->create(['name' => 'Fulano de Tal', 'email' => 'fulano@example.com']);

    /*
     * `getRouteKey()`, nunca `id`: `App\Traits\TemUuid::getRouteKeyName():35` troca a chave de rota
     * por `uuid` em User e Tenant. Montar a URL com o id devolve 404, e o 404 se le como "a rota
     * nao existe" — que e o diagnostico errado.
     */
    $url       = str_replace('{user}', $alvo->getRouteKey(), $rota);
    $cabecalho = regiaoDoCabecalho($this->actingAs($admin)->get($url)->assertSuccessful()->getContent());

    expect($cabecalho)->not->toBe('', "a tela {$url} nao renderizou o cabecalho do pacote")
        ->and($cabecalho)->toContain($esperado);
})->with([
    'ver usuario'    => ['/admin/users/{user}', 'Fulano de Tal'],
    'editar usuario' => ['/admin/users/{user}/edit', 'Fulano de Tal'],
])->group('kit');

/**
 * CT-02 — CONTROLE NEGATIVO do recortador: tela sem o trait não tem a região.
 *
 * Sem este caso, `regiaoDoCabecalho()` poderia devolver a página inteira (ou qualquer coisa) e o
 * CT-01 ficaria verde sem medir nada. Ele é o que prova que o detector detecta.
 *
 * A listagem é a escolha certa de contraprova: ela é do MESMO resource, no MESMO painel, com o
 * plugin registrado — a única diferença é a Page não usar o trait.
 */
it('[CT-02] nao renderiza o cabecalho do pacote em tela que nao optou', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');

    $html = $this->actingAs($admin)->get('/admin/users')->assertSuccessful()->getContent();

    expect(regiaoDoCabecalho($html))->toBe('')
        // E a página de fato renderizou — senão o vazio acima seria por engano.
        ->and($html)->toContain('Usuários');
})->group('kit');

/*
|--------------------------------------------------------------------------
| ADR-02 — o avatar do header NÃO pode sair do provider do painel
|--------------------------------------------------------------------------
*/

/**
 * CT-03 — com foto, o `<img>` do cabeçalho aponta para o disco público.
 *
 * Metade positiva do ADR-02. `Header::getAvatarUrl()`
 * (`vendor/mortalkiller/filament-page-header/src/Components/Header.php:169-180`) só aceita
 * `http`/`https`, e `User::getFilamentAvatarUrl():857-861` devolve `Storage::disk('public')->url()`
 * — medido, aceito.
 *
 * Mutação que este caso mata: trocar `getFilamentAvatarUrl()` por
 * `Filament::getUserAvatarUrl($record)`, que passa pelo `AvatarDeIniciais` do kit e devolve
 * `data:image/svg+xml;base64,…`. O validador do pacote recusa o esquema `data` e devolve `null`
 * SEM erro: o `<img>` some, a página segue 200 e um `assertSee` do nome continua verde.
 */
it('[CT-03] leva a foto do usuario ao cabecalho quando ela existe', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Com Foto', 'avatar_url' => 'avatars/fulano.png']);

    $cabecalho = regiaoDoCabecalho(
        $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent(),
    );

    expect($cabecalho)->toContain('<img')
        ->and($cabecalho)->toContain('storage/avatars/fulano.png');
})->group('kit');

/**
 * CT-04 — sem foto, o cabeçalho cai nas INICIAIS e nenhum `data:` URI entra nele.
 *
 * `Header::getInitials():187-195` recorta as duas primeiras palavras; "Sem Foto" vira "SF".
 *
 * ## A matriz de mutantes, medida — e o que ela NÃO mata
 *
 * | Mutante | CT-03 | CT-04 |
 * |---|---|---|
 * | base | verde | verde |
 * | M-A: `->avatar()` recebe o SVG do kit sempre (`data:` URI incondicional) | **vermelho** | verde |
 * | M-B: `->initials()` removido | verde | **vermelho** |
 * | M-C: `getFilamentAvatarUrl()` trocado por `Filament::getUserAvatarUrl()` | verde | verde |
 *
 * **M-C sobrevive, e isso não é lacuna do oráculo — é o fato.** Medido: `getUserAvatarUrl()`
 * devolve `getFilamentAvatarUrl()` quando há foto e só cai no provider quando não há; nesse caso o
 * `data:` URI é descartado e o slot cai nas MESMAS iniciais. A saída renderizada é idêntica nos
 * dois mundos, então nenhum oráculo sobre HTML pode distinguí-los.
 *
 * O que o ADR-02 evita, então, não é um defeito visível: é a expectativa falsa de que o
 * `AvatarDeIniciais` do kit aparece no cabeçalho. Ele não aparece em nenhuma das duas versões.
 * Escrever `getFilamentAvatarUrl()` deixa isso explícito no código; escrever `getUserAvatarUrl()`
 * sugere um fallback que o pacote silenciosamente não honra.
 */
it('[CT-04] cai nas iniciais e nao admite data URI no cabecalho', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Sem Foto', 'avatar_url' => null]);

    $cabecalho = regiaoDoCabecalho(
        $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent(),
    );

    expect($cabecalho)->not->toBe('')
        ->and(str_contains($cabecalho, 'data:image'))->toBeFalse(
            'o avatar em data: URI chegou ao cabecalho — Header::getAvatarUrl() o descarta em silencio, ver ADR-02',
        )
        ->and($cabecalho)->toContain('>SF<');
})->group('kit');

/*
|--------------------------------------------------------------------------
| ADR-04 — três APIs do pacote nascem proibidas
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — `app/` não usa `html: true` nem as duas APIs já `@deprecated` no vendor.
 *
 * `html: true` desliga o `e($state)` de `Heading::getContent():26-31`, e o estado dos cabeçalhos do
 * kit é `$record->name`/`$record->nome` — entrada de usuário.
 *
 * `hideWhenCompact()` (`Header.php:385`) e `retainSummaryWhenCompact()` (`:328`) estão marcadas
 * `@deprecated` no próprio vendor, num repositório que já trocou a API de major em 24 h e não tem
 * CHANGELOG.
 *
 * **Comentário é filtrado antes da asserção de ausência**: os arquivos do kit CITAM o que proíbem,
 * e é lá que está o porquê. Sem o filtro, esta varredura reprovaria a própria documentação —
 * `.ai/rules/testes.md` registra o padrão, que já custou três vezes nesta base.
 */
it('[CT-05] nao usa html cru nem API deprecada do page-header', function (): void {
    $proibidos = ['html: true', 'hideWhenCompact', 'retainSummaryWhenCompact'];
    $achados   = [];

    foreach (Finder::create()->files()->in(base_path('app'))->name('*.php') as $arquivo) {
        $codigo = (string) $arquivo->getContents();

        // Bloco `/* */`, docblock `/** */` e linha `//` saem antes de afirmar ausência.
        $codigo = (string) preg_replace('~/\*.*?\*/~s', '', $codigo);
        $codigo = (string) preg_replace('~^\s*//.*$~m', '', $codigo);

        foreach ($proibidos as $proibido) {
            if (str_contains($codigo, $proibido)) {
                $achados[] = $arquivo->getRelativePathname().' → '.$proibido;
            }
        }
    }

    expect($achados)->toBe([]);
})->group('kit');

/**
 * CT-06 — CONTROLE POSITIVO do detector do CT-05.
 *
 * O CT-05 é uma asserção de ausência sobre um filtro de comentário. Um regex de filtro quebrado
 * (que apagasse o arquivo inteiro, por exemplo) o deixaria verde para sempre. Este caso roda o
 * MESMO filtro sobre um código sintético que contém as três construções fora de comentário, e
 * exige que as três sejam vistas.
 */
it('[CT-06] enxerga as construcoes proibidas fora de comentario', function (): void {
    $codigo = <<<'PHP'
    <?php
    // hideWhenCompact aqui e comentario, nao vale
    /* retainSummaryWhenCompact aqui tambem nao vale */
    /** html: true no docblock tambem nao */
    $h->heading($nome, html: true);
    $h->hideWhenCompact(['metadata']);
    $h->retainSummaryWhenCompact();
    PHP;

    $limpo = (string) preg_replace('~/\*.*?\*/~s', '', $codigo);
    $limpo = (string) preg_replace('~^\s*//.*$~m', '', $limpo);

    expect(substr_count($limpo, 'html: true'))->toBe(1)
        ->and(substr_count($limpo, 'hideWhenCompact'))->toBe(1)
        ->and(substr_count($limpo, 'retainSummaryWhenCompact'))->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| A guarda de CSS — lista congelada envelhece em silêncio no composer update
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — toda classe que as blades do pacote emitem existe na CSS dele ou é `fi-*`.
 *
 * O kit não tem tema Filament customizado, e a CSS pré-compilada do Filament carrega quase só as
 * classes `fi-*`. Pacote que emitisse utilitária Tailwind crua não ganharia estilo nenhum — e o
 * modo de falhar é HTML byte a byte correto e sem estilo, com todo teste de componente verde.
 * `.ai/rules/css-filament.md` registra dois casos medidos nesta base, um deles abrindo um overlay
 * a 1.800 px do topo com o teste de browser verde.
 *
 * ## O oráculo é o NAMESPACE, e não "toda classe tem regra"
 *
 * O risco não é classe `fph-*` sem regra — `fph-schema` é exatamente isso hoje, um invólucro
 * estrutural que não precisa de estilo, e reprovar por causa dele seria ruído. O risco é a
 * utilitária CRUA (`flex`, `mt-4`, `text-gray-500`): essa o kit não tem de onde carregar, e o modo
 * de falhar é HTML correto e sem estilo nenhum.
 *
 * Então a asserção é: toda classe emitida está em `fph-*` (namespace do pacote, que traz a própria
 * CSS) ou em `fi-*` (do Filament, pré-compilado). Qualquer outra é utilitária solta.
 *
 * O PISO de contagem é o controle positivo do detector: regex quebrado devolve lista vazia, e
 * "toda classe está no namespace certo" ficaria verde sobre nada.
 */
it('[CT-07] cobre no CSS do pacote toda classe que as blades dele emitem', function (): void {
    $base = base_path('vendor/mortalkiller/filament-page-header');
    $css  = (string) file_get_contents($base.'/resources/css/page-header.css');

    $classes = [];

    foreach (Finder::create()->files()->in($base.'/resources/views')->name('*.blade.php') as $blade) {
        $conteudo = (string) $blade->getContents();

        // `class="..."` literal e `->class([...])` do ComponentAttributeBag.
        preg_match_all('~class="([^"{]*)"~', $conteudo, $literais);
        preg_match_all("~->class\(\[([^\]]*)\]\)~", $conteudo, $bags);

        foreach ($literais[1] as $lista) {
            $classes = [...$classes, ...preg_split('~\s+~', $lista, flags: PREG_SPLIT_NO_EMPTY)];
        }

        foreach ($bags[1] as $lista) {
            preg_match_all("~'([a-z0-9-]+)'~i", $lista, $itens);
            $classes = [...$classes, ...$itens[1]];
        }
    }

    $classes = array_values(array_unique(array_filter($classes)));

    // Piso: as blades emitem dezenas de classes. Zero, ou um punhado, é regex quebrado.
    expect(count($classes))->toBeGreaterThanOrEqual(20, 'o extrator de classes devolveu pouco — regex quebrado');

    $soltas = array_values(array_filter(
        $classes,
        static fn (string $classe): bool => ! str_starts_with($classe, 'fph-')
            && ! str_starts_with($classe, 'fi-'),
    ));

    expect($soltas)->toBe([], 'utilitaria crua emitida pelas blades do pacote — o kit nao tem de onde carrega-la');

    // E a CSS do pacote de fato cobre o grosso do que ele emite: o `fph-root`, que carrega as
    // variaveis, e o `fph-header`, que e o cartao. Sem estes dois o cabecalho sai sem forma.
    expect($css)->toContain('.fph-root')->toContain('.fph-header');
})->group('kit');

/*
|--------------------------------------------------------------------------
| RQ-13 — a ViewUser, a rota e a permissão
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — a rota `view` está registrada ANTES da de edição, nos dois painéis.
 *
 * `/{record}` é a rota mais curta e o Filament casa na ordem de declaração; registrada depois de
 * `/{record}/edit` ela ainda funciona, mas a ordem é a convenção do kit
 * (`TenantResource::getPages():141-144`) e a inversão é o tipo de coisa que um gerador desfaz.
 *
 * A existência da rota é o que faz o `ViewAction` da tabela NAVEGAR em vez de abrir modal:
 * `Resources\Pages\Page::getDefaultActionUrl():382-389` só devolve URL quando `hasPage('view')`.
 */
it('[CT-08] registra a rota view antes da de edicao nos dois paineis', function (string $resource): void {
    $chaves = array_keys($resource::getPages());

    expect($chaves)->toContain('view')
        ->and(array_search('view', $chaves, true))
        ->toBeLessThan(array_search('edit', $chaves, true));
})->with([
    'admin' => [UserResourceAdmin::class],
    'app'   => [UserResourceApp::class],
])->group('kit');

/**
 * CT-09 — a ficha abre para quem tem `View:User`.
 *
 * Metade positiva. Sozinha ela passaria numa tela que não consulta permissão NENHUMA — por isso o
 * CT-10 existe, e por isso os dois são um par indivisível.
 */
it('[CT-09] abre a ficha do usuario para quem tem a permissao', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ciclano Alvo']);

    expect($admin->can('View:User'))->toBeTrue('o papel admin deveria carregar View:User');

    $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertSuccessful()
        ->assertSee('Ciclano Alvo');
})->group('kit');

/**
 * CT-10 — sem `View:User`, a ficha responde 403.
 *
 * Metade negativa, e é ela que mata a mutação que importa: `ViewRecord::authorizeAccess():80`
 * removido, ou a policy `UserPolicy::view()` trocada por `return true`. Sem este caso a tela
 * poderia nascer aberta a qualquer um que alcance o painel, com a suíte inteira verde — e é
 * exatamente o defeito que a RQ-13 nomeia.
 *
 * A permissão é revogada do papel REAL (`semAPermissao()`), nunca por papel vazio: papel sem
 * permissão nenhuma perde também o `canAccessPanel()`, e o 403 passaria a vir da porta do painel
 * — o caso ficaria verde medindo outra coisa. `.ai/rules/testes.md` e o docblock de
 * `semAPermissao()` registram isso.
 */
it('[CT-10] recusa a ficha do usuario para quem nao tem a permissao', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ciclano Alvo']);

    semAPermissao('admin', 'View:User');

    expect($admin->fresh()->can('View:User'))->toBeFalse('a revogacao nao surtiu efeito');

    $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertForbidden();
})->group('kit');

/**
 * CT-11 — a ficha do /admin mostra os vinculos da pessoa.
 *
 * Metade desta fronteira. A outra metade — que a ficha do /app NAO os mostra — vive em
 * `tests/Tenancy/ViewUserTenancyTest.php`, porque so la existe uma segunda organizacao para
 * vazar. Um caso sem duas organizacoes nao consegue reprovar o vazamento: ele mediria a ausencia
 * de dado, nao a fronteira.
 *
 * Nao e preferencia de layout. Listar as organizacoes de alguem no /app contaria a quem administra
 * a Acme que aquela pessoa tambem e da Globex. O recorte de `UserResource::getEloquentQuery():197`
 * garante que so se veja gente DA organizacao corrente; ele nao garante que se possa ver onde mais
 * ela esta.
 */
it('[CT-11] mostra os vinculos da pessoa na ficha do admin', function (): void {
    $admin  = usuarioDoKit('admin', 'admin@example.com');
    $acme   = Tenant::factory()->create(['nome' => 'Acme Ltda', 'slug' => 'acme']);
    $alvo   = User::factory()->create(['name' => 'Ciclano Vinculado']);

    $alvo->tenants()->attach($acme->id);

    $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertSuccessful()
        ->assertSee('Vinculos')
        ->assertSee('Acme Ltda');
})->group('kit');

/*
|--------------------------------------------------------------------------
| ADR-01 — o plugin entra em dois painéis, não em três
|--------------------------------------------------------------------------
*/

/**
 * CT-12 — o plugin está no /admin e no /app, e NÃO no /infra.
 *
 * O `register()` do plugin emite o `<link>` da CSS em toda página do painel
 * (`PageHeaderPlugin::register():60-68`), tenha ela cabeçalho ou não. O /infra não tem tela alvo.
 *
 * O caso assere os TRÊS painéis, e não só os dois positivos: sem a linha do /infra, registrar o
 * plugin nos três — que é o reflexo natural de "simetria entre providers" — passaria despercebido.
 */
it('[CT-12] registra o page-header so nos paineis que tem tela alvo', function (string $painel, bool $esperado): void {
    expect(Filament::getPanel($painel)->hasPlugin(PageHeaderPlugin::ID))->toBe($esperado);
})->with([
    'admin' => ['admin', true],
    'app'   => ['app', true],
    'infra' => ['infra', false],
])->group('kit');

/**
 * CT-13 — cada painel tem a PRÓPRIA instância do plugin.
 *
 * `PageHeaderPlugin::make()` é `app(self::class)` (`PageHeaderPlugin.php:33-36`) e o provider do
 * pacote não registra binding de singleton (`PageHeaderServiceProvider::boot():14-23`), então o
 * container constrói instância nova a cada chamada. O estado (`$options`, `$resourceSchemas`) é
 * por instância.
 *
 * Mutação que este caso mata: guardar `PageHeaderPlugin::make()` numa variável e passá-la aos dois
 * providers. Tudo continuaria renderizando, e a configuração de um painel passaria a valer no
 * outro — em silêncio, até alguém configurar modo compacto só no /app.
 */
it('[CT-13] da uma instancia propria do plugin a cada painel', function (): void {
    $doAdmin = Filament::getPanel('admin')->getPlugin(PageHeaderPlugin::ID);
    $doApp   = Filament::getPanel('app')->getPlugin(PageHeaderPlugin::ID);

    expect(spl_object_id($doAdmin))->not->toBe(spl_object_id($doApp));
})->group('kit');

/*
|--------------------------------------------------------------------------
| A superfície Livewire que o trait acrescenta
|--------------------------------------------------------------------------
*/

/**
 * CT-14 — `$wire.getPageHeaderRecord()` devolve o registro sem segredo, e o que ele devolve a mais
 * que a ficha é inócuo.
 *
 * Fecha o item que o `02` deixou explicitamente **a medir**, em vez de deduzir.
 *
 * O `HasPageHeader` é trait, e método de trait conta como declarado pela classe que o usa
 * (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:570-580`,
 * `Utils::getPublicMethodsDefinedBySubClass`, que só subtrai `render`). Logo os nove métodos
 * públicos do trait viram ação chamável pelo navegador em toda página que o aplica — e um deles
 * devolve o registro.
 *
 * Não há escalada: `ViewRecord::hydrate():85` reautoriza a cada hidratação, então só chega aqui
 * quem já pode ver a ficha. A pergunta que sobrava era de DIVULGAÇÃO: o retorno traz coluna que a
 * ficha não mostra?
 *
 * Medido: 12 chaves. `password` e `remember_token` ficam de fora pelo `$hidden`
 * (`app/Models/User.php:96-99`). A mais que a ficha vêm `id`, `uuid` e `avatar_url` — o uuid já
 * está na própria URL, o id é chave e o avatar_url é caminho de arquivo público. Divulgação
 * declarada e aceita, não fronteira rompida.
 *
 * O caso trava as duas pontas: o segredo NUNCA entra, e a lista de chaves é fixa — coluna nova no
 * `users` que passe a sair por aqui deixa este caso vermelho, que é o momento certo de decidir.
 */
it('[CT-14] nao entrega segredo na acao do trait que devolve o registro', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Alvo', 'email' => 'alvo@example.com']);

    /*
     * O GET real antes do Livewire::test() nao e cerimonia: sem ele o painel corrente e o ultimo
     * registrado (o /infra), e o componente estoura
     * "Route [filament.infra.resources.users.index] not defined" — medido. Quem boota o painel e o
     * middleware SetUpPanel, que teste de componente nao atravessa. Ver `.ai/rules/testes.md`.
     */
    $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful();

    $retorno = Livewire\Livewire::actingAs($admin)
        ->test(ViewUser::class, ['record' => $alvo->getRouteKey()])
        ->call('getPageHeaderRecord')
        ->effects['returns'][0] ?? null;

    expect($retorno)->toBeArray();

    $chaves = array_keys($retorno);

    sort($chaves);

    expect($chaves)->toBe([
        'aprovacao_pendente', 'ativo', 'avatar_url', 'created_at', 'deleted_at', 'email',
        'email_verified_at', 'id', 'name', 'origem', 'updated_at', 'uuid',
    ])
        ->and($chaves)->not->toContain('password')
        ->and($chaves)->not->toContain('remember_token');
})->group('kit');

/**
 * CT-15 — as telas de EDIÇÃO recebem o cabeçalho COMPLETO, não só o título.
 *
 * O CT-01 já visita as telas de edição, mas para no `fph-heading`. Uma implementação que
 * resolvesse o schema só nas páginas `View*` — e é uma implementação plausível, porque "ficha" é a
 * palavra que puxa o desenho — entregaria as telas de edição com um cabeçalho pelado e passaria em
 * todos os outros casos. A RQ-02 diz literalmente "View/**Edit**".
 *
 * O oráculo são os slots que o CT-01 não olha: as iniciais (identidade), o badge de situação e o
 * e-mail em metadata. Os três vivem no mesmo `Schemas\UserHeader`, mas é a AUSÊNCIA deles na tela
 * de edição que a partição unidimensional do CT-01 não consegue enxergar.
 */
it('[CT-15] leva identidade, badge e metadata tambem para a tela de edicao', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create([
        'name'               => 'Editado Aqui',
        'email'              => 'editado@example.com',
        'ativo'              => false,
        'aprovacao_pendente' => false,
    ]);

    $cabecalho = regiaoDoCabecalho(
        $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}/edit")->assertSuccessful()->getContent(),
    );

    expect($cabecalho)->not->toBe('')
        // identidade: as iniciais do pacote, porque o alvo não tem foto
        ->and($cabecalho)->toContain('>EA<')
        // badge: a situação, que sai de User::rotuloDaSituacao()
        ->and($cabecalho)->toContain('Inativo')
        // metadata: o e-mail
        ->and($cabecalho)->toContain('editado@example.com');
})->group('kit');
