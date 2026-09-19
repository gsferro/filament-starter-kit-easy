<?php

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\UserResource as UserResourceAdmin;
use App\Filament\App\Resources\Users\UserResource as UserResourceApp;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use MortalKiller\FilamentPageHeader\PageHeaderPlugin;
use Spatie\Permission\PermissionRegistrar;
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
 * CT-03 — as telas de registro de USUÁRIO do /admin renderizam o cabeçalho do pacote, com o nome
 * do registro DENTRO dele.
 *
 * Realiza as linhas `admin | EditUser` e `admin | ViewUser` do `Esquema` de CT-03 do `04`. As
 * outras quatro linhas ficam em `tests/Tenancy/PageHeaderTenancyTest.php:[CT-03]` (as duas de
 * organização e a `app | ViewUser`); a linha `app | EditUser` não tem caso — ver `03-progresso.md`.
 * A linha "a região não contém o nome da aplicação" do cenário desenhado também não está aqui.
 *
 * As telas de ORGANIZAÇÃO ficam em `tests/Tenancy/PageHeaderTenancyTest.php`: `TenantResource`
 * exige `kit.tenancy.enabled` (`TenantResource::canAccess():107`), e esta suíte roda
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
it('[CT-03] renderiza o cabecalho do pacote nas telas de registro do admin', function (string $rota, string $esperado): void {
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
 * `[CT-03]` ficaria verde sem medir nada. Ele é o que prova que o detector detecta.
 *
 * O cenário desenhado de CT-02 usa HTML sintético; aqui a região ausente é medida sobre uma página
 * real, o que realiza a mesma afirmação (região ausente devolve vazio) e, de quebra, a SEGUNDA
 * linha de CT-50 ("a listagem de contas do /admin responde 200 sem nenhum `fph-root`"). A primeira
 * linha de CT-50 — o conjunto de páginas com o trait ser IGUAL à lista nominal de seis — não está
 * coberta aqui nem em lugar nenhum.
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
 * CT-08 — com foto, o `<img>` do cabeçalho aponta para o disco público.
 *
 * Do cenário desenhado ficam de fora a asserção sobre o `alt` conter o nome da conta e a linha de
 * `src` começar por "http" (aqui o oráculo é o caminho `storage/…`).
 *
 * Metade positiva do ADR-02. `Header::getAvatarUrl()`
 * (`vendor/mortalkiller/filament-page-header/src/Components/Header.php:169-180`) só aceita
 * `http`/`https`, e `User::getFilamentAvatarUrl():888` devolve `Storage::disk('public')->url()`
 * — medido, aceito.
 *
 * Mutação que este caso mata: trocar `getFilamentAvatarUrl()` por
 * `Filament::getUserAvatarUrl($record)`, que passa pelo `AvatarDeIniciais` do kit e devolve
 * `data:image/svg+xml;base64,…`. O validador do pacote recusa o esquema `data` e devolve `null`
 * SEM erro: o `<img>` some, a página segue 200 e um `assertSee` do nome continua verde.
 */
it('[CT-08] leva a foto do usuario ao cabecalho quando ela existe', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Com Foto', 'avatar_url' => 'avatars/fulano.png']);

    $cabecalho = regiaoDoCabecalho(
        $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent(),
    );

    expect($cabecalho)->toContain('<img')
        ->and($cabecalho)->toContain('storage/avatars/fulano.png');
})->group('kit');

/**
 * CT-07 — sem foto, o cabeçalho cai nas INICIAIS e nenhum `data:` URI entra nele.
 *
 * `Header::getInitials():187-195` recorta as duas primeiras palavras; "Sem Foto" vira "SF".
 *
 * **Realização parcial**: falta a linha de NÃO-VACUIDADE do cenário desenhado — "a página contém
 * `data:image/svg+xml;base64,` **fora** do cabeçalho, no avatar da barra superior". Sem ela, a
 * ausência aqui também passaria com o provider do kit desligado. E a linha "a região não contém
 * nenhuma tag `<img>`" ficou de fora; o que se afirma é a presença das iniciais.
 *
 * ## A matriz de mutantes, medida — e o que ela NÃO mata
 *
 * | Mutante | `[CT-08]` | `[CT-07]` |
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
it('[CT-07] cai nas iniciais e nao admite data URI no cabecalho', function (): void {
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
 * CT-26 — `app/` não usa `html: true` nem as duas APIs já `@deprecated` no vendor.
 *
 * Primeira linha do cenário desenhado. O PISO de 200 arquivos varridos, que o `04` exige como
 * segunda trava contra a varredura que roda sobre diretório vazio, **não** está aqui.
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
it('[CT-26] nao usa html cru nem API deprecada do page-header', function (): void {
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
 * CT-26 — CONTROLE POSITIVO do detector, que é a SEGUNDA linha do mesmo cenário desenhado ("a
 * mesma varredura **com** os comentários encontra as citações que explicam a proibição").
 *
 * O caso irmão acima é uma asserção de ausência sobre um filtro de comentário. Um regex de filtro quebrado
 * (que apagasse o arquivo inteiro, por exemplo) o deixaria verde para sempre. Este caso roda o
 * MESMO filtro sobre um código sintético que contém as três construções fora de comentário, e
 * exige que as três sejam vistas.
 */
it('[CT-26] enxerga as construcoes proibidas fora de comentario', function (): void {
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
 * CT-28 — toda classe que as blades do pacote emitem existe na CSS dele ou é `fi-*`.
 *
 * **Divergência de oráculo, declarada.** O cenário desenhado subtrai os seletores definidos das
 * classes emitidas e exige que o resto seja EXATAMENTE o conjunto de exceções nominais (hoje
 * `fph-schema`). Este caso afirma outra coisa — que toda classe emitida está em `fph-*` ou `fi-*` —
 * e o motivo está abaixo. Os dois pisos de contagem do cenário desenhado viram um só (o das
 * emitidas); o do conjunto de DEFINIDAS não está aqui. O controle negativo CT-29 (agulha plantada)
 * não foi implementado.
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

/**
 * As classes CSS que as blades de um diretório emitem, nas duas formas.
 *
 * Função, e não código inline no caso, porque `[CT-29]` é o controle negativo dela: ele planta uma
 * agulha num par sintético de blade e exige que este MESMO extrator a encontre. Controle negativo
 * rodando sobre outro extrator não prova nada sobre o que o caso de verdade usa.
 *
 * As duas formas de emissão existem no vendor: `class="…"` literal e
 * `ComponentAttributeBag->class([…])`. `fph-heading`, `fph-subheading` e `fph-layout` saem só da
 * segunda — um regex que lesse apenas a primeira acharia 24 de 27 e pareceria bom.
 *
 * @return list<string>
 */
function classesEmitidasPorBlades(string $diretorio): array
{
    $classes = [];

    foreach (Finder::create()->files()->in($diretorio)->name('*.blade.php') as $blade) {
        $conteudo = (string) $blade->getContents();

        preg_match_all('~class="([^"{]*)"~', $conteudo, $literais);
        preg_match_all('~->class\(\[([^\]]*)\]\)~', $conteudo, $bags);

        foreach ($literais[1] as $lista) {
            $classes = [...$classes, ...(preg_split('~\s+~', $lista, flags: PREG_SPLIT_NO_EMPTY) ?: [])];
        }

        foreach ($bags[1] as $lista) {
            preg_match_all("~'([a-zA-Z0-9-]+)'~", $lista, $itens);
            $classes = [...$classes, ...$itens[1]];
        }
    }

    return array_values(array_unique(array_filter($classes)));
}

it('[CT-28] cobre no CSS do pacote toda classe que as blades dele emitem', function (): void {
    $base = base_path('vendor/mortalkiller/filament-page-header');
    $css  = (string) file_get_contents($base.'/resources/css/page-header.css');

    $classes = classesEmitidasPorBlades($base.'/resources/views');

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
 * CT-30 — a rota `view` está registrada ANTES da de edição, nos dois painéis.
 *
 * Duas das três linhas do `Então` desenhado: a chave existe e vem antes de "edit". A terceira — o
 * caminho declarado para "view" ser `/{record}` — não está aqui; o que mais perto chega dela é
 * `tests/Tenancy/PageHeaderTenancyTest.php:[CT-30]`, que confere a URL servida no painel /app.
 *
 * `/{record}` é a rota mais curta e o Filament casa na ordem de declaração; registrada depois de
 * `/{record}/edit` ela ainda funciona, mas a ordem é a convenção do kit
 * (`TenantResource::getPages():142`) e a inversão é o tipo de coisa que um gerador desfaz.
 *
 * A existência da rota é o que faz o `ViewAction` da tabela NAVEGAR em vez de abrir modal:
 * `Resources\Pages\Page::getDefaultActionUrl():361` só devolve URL quando `hasPage('view')`.
 */
it('[CT-30] registra a rota view antes da de edicao nos dois paineis', function (string $resource): void {
    $chaves = array_keys($resource::getPages());

    expect($chaves)->toContain('view')
        ->and(array_search('view', $chaves, true))
        ->toBeLessThan(array_search('edit', $chaves, true));
})->with([
    'admin' => [UserResourceAdmin::class],
    'app'   => [UserResourceApp::class],
])->group('kit');

/**
 * CT-11 — a ficha abre para quem tem `View:User`.
 *
 * Linha POSITIVA do `Esquema` desenhado. Sozinha ela passaria numa tela que não consulta permissão
 * NENHUMA — por isso o caso irmão logo abaixo (a linha negativa, com o mesmo rótulo) existe, e por
 * isso os dois são um par indivisível.
 *
 * **Fraqueza herdada, declarada**: o `04` avisa em `## Armadilhas` que "a linha positiva tem de
 * afirmar `fph-root`, senão fica verde numa tela sem cabeçalho". Aqui o oráculo é `assertSee` do
 * nome, que a barra de título e as migalhas também satisfazem.
 */
it('[CT-11] abre a ficha do usuario para quem tem a permissao', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ciclano Alvo']);

    expect($admin->can('View:User'))->toBeTrue('o papel admin deveria carregar View:User');

    /*
     * `assertSee` do nome NAO serve de oraculo sozinho: o nome aparece no titulo da aba e nas
     * migalhas, entao ele ficaria verde numa tela que abriu sem cabecalho e sem ficha. O oraculo e
     * a regiao do cabecalho mais a secao da ficha — as duas coisas que so existem se a pagina
     * montou de verdade.
     */
    $html = $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertSuccessful()
        ->getContent();

    expect(regiaoDoCabecalho($html))->not->toBe('', 'a ficha abriu sem o cabecalho do pacote')
        ->and(regiaoDoCabecalho($html))->toContain('Ciclano Alvo')
        // A ficha em si, e nao so o cabecalho: a secao compartilhada de FichaDeUsuario.
        ->and($html)->toContain('Conta');
})->group('kit');

/**
 * CT-11 — sem `View:User`, a ficha responde 403.
 *
 * Linha NEGATIVA do mesmo `Esquema`, e é ela que mata a mutação que importa: `ViewRecord::authorizeAccess():78`
 * removido, ou a policy `UserPolicy::view()` trocada por `return true`. Sem este caso a tela
 * poderia nascer aberta a qualquer um que alcance o painel, com a suíte inteira verde — e é
 * exatamente o defeito que a RQ-13 nomeia.
 *
 * A permissão é revogada do papel REAL (`semAPermissao()`), nunca por papel vazio: papel sem
 * permissão nenhuma perde também o `canAccessPanel()`, e o 403 passaria a vir da porta do painel
 * — o caso ficaria verde medindo outra coisa. `.ai/rules/testes.md` e o docblock de
 * `semAPermissao()` registram isso.
 */
it('[CT-11] recusa a ficha do usuario para quem nao tem a permissao', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ciclano Alvo']);

    semAPermissao('admin', 'View:User');

    expect($admin->fresh()->can('View:User'))->toBeFalse('a revogacao nao surtiu efeito');

    $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertForbidden();
})->group('kit');

/**
 * CT-53 — a ficha do /admin mostra o registro FORA do cabeçalho: os vinculos da pessoa.
 *
 * **Realização parcial.** O cenário desenhado subtrai a região do cabeçalho da página e exige, no
 * complemento, o e-mail, a data de criação e um infolist com pelo menos tres entradas. Aqui a
 * afirmacao e mais fraca — a secao "Vinculos" e o nome da organizacao aparecem na pagina —, mas e
 * a mesma direcao: a ficha nao nasce vazia sob o cabecalho (mutante M62).
 *
 * Metade desta fronteira. A outra metade — que a ficha do /app NAO os mostra — vive em
 * `tests/Tenancy/PageHeaderTenancyTest.php:[EXTRA]`, porque so la existe uma segunda organizacao
 * para vazar. Um caso sem duas organizacoes nao consegue reprovar o vazamento: ele mediria a
 * ausencia de dado, nao a fronteira.
 *
 * Nao e preferencia de layout. Listar as organizacoes de alguem no /app contaria a quem administra
 * a Acme que aquela pessoa tambem e da Globex. O recorte de `UserResource::getEloquentQuery():169`
 * garante que so se veja gente DA organizacao corrente; ele nao garante que se possa ver onde mais
 * ela esta.
 */
it('[CT-53] mostra os vinculos da pessoa na ficha do admin', function (): void {
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
 * EXTRA — o plugin está no /admin e no /app, e NÃO no /infra.
 *
 * **Não realiza nenhum CT do `04`, e o motivo importa.** Os três cenários desenhados que guardam
 * esta fronteira afirmam sobre a RESPOSTA RENDERIZADA: CT-04 (a tela do /infra não traz `fph-root`
 * nem o `<link>`), CT-05 (a folha de estilo sai no /admin e não sai no /infra) e CT-47 (o render
 * hook `STYLES_AFTER` é escopado por painel). Este caso mede a CAUSA — o registro do plugin no
 * painel —, que é um oráculo mais barato e mais fraco: ele fica verde com o render hook emitindo
 * em todo painel. Os três continuam sem teste; ver `03-progresso.md`.
 *
 * O `register()` do plugin emite o `<link>` da CSS em toda página do painel
 * (`PageHeaderPlugin::register():60-68`), tenha ela cabeçalho ou não. O /infra não tem tela alvo.
 *
 * O caso assere os TRÊS painéis, e não só os dois positivos: sem a linha do /infra, registrar o
 * plugin nos três — que é o reflexo natural de "simetria entre providers" — passaria despercebido.
 */
it('[EXTRA] registra o page-header so nos paineis que tem tela alvo', function (string $painel, bool $esperado): void {
    expect(Filament::getPanel($painel)->hasPlugin(PageHeaderPlugin::ID))->toBe($esperado);
})->with([
    'admin' => ['admin', true],
    'app'   => ['app', true],
    'infra' => ['infra', false],
])->group('kit');

/**
 * CT-48 — cada painel tem a PRÓPRIA instância do plugin.
 *
 * Primeira linha do cenário desenhado (objetos distintos). A segunda — "configurar opções na
 * instância de um painel não altera as do outro" — não está implementada.
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
it('[CT-48] da uma instancia propria do plugin a cada painel', function (): void {
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
 * CT-20 — `$wire.getPageHeaderRecord()` devolve o registro sem segredo, e o que ele devolve a mais
 * que a ficha é inócuo.
 *
 * É a medição que o `02` declarou em aberto, com o oráculo já fechado em `toBe()` da lista nominal,
 * como o `04` manda. Os cenários irmãos de R6 — CT-21 (`$record` travado), CT-22 (estado do
 * formulário), CT-23 (os nove métodos como ação), CT-24 (argumento tipado) e CT-25
 * (`data-fph-options` sem PII) — não foram implementados.
 *
 * Fecha o item que o `02` deixou explicitamente **a medir**, em vez de deduzir.
 *
 * O `HasPageHeader` é trait, e método de trait conta como declarado pela classe que o usa
 * (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:570-580`,
 * `Utils::getPublicMethodsDefinedBySubClass`, que só subtrai `render`). Logo os nove métodos
 * públicos do trait viram ação chamável pelo navegador em toda página que o aplica — e um deles
 * devolve o registro.
 *
 * Não há escalada: `ViewRecord::hydrate():83` reautoriza a cada hidratação, então só chega aqui
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
it('[CT-20] nao entrega segredo na acao do trait que devolve o registro', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Alvo', 'email' => 'alvo@example.com']);

    /*
     * O GET real antes do Livewire::test() nao e cerimonia: sem ele o painel corrente e o ultimo
     * registrado (o /infra), e o componente estoura
     * "Route [filament.infra.resources.users.index] not defined" — medido. Quem boota o painel e o
     * middleware SetUpPanel, que teste de componente nao atravessa. Ver `.ai/rules/testes.md`.
     */
    $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful();

    $retorno = Livewire::actingAs($admin)
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
 * CT-49 — as telas de EDIÇÃO recebem o cabeçalho COMPLETO, não só o título.
 *
 * Realiza a linha `admin | EditUser` do `Esquema` desenhado; as linhas `app | EditUser` e
 * `admin | EditTenant` não têm caso. A última asserção do cenário — "nenhuma dessas regiões contém
 * o nome de quem está autenticado" — também não está aqui.
 *
 * De quebra, cobre a LINHA 3 da tabela de decisão de CT-37 (`pendente=false`, `ativo=false` →
 * "Inativo"), sem a asserção de exclusividade que aquele cenário exige ("não contém nenhum dos
 * outros dois rótulos"). As outras três linhas de CT-37 seguem sem caso.
 *
 * O `[CT-03]` já visita as telas de edição, mas para no `fph-heading`. Uma implementação que
 * resolvesse o schema só nas páginas `View*` — e é uma implementação plausível, porque "ficha" é a
 * palavra que puxa o desenho — entregaria as telas de edição com um cabeçalho pelado e passaria em
 * todos os outros casos. A RQ-02 diz literalmente "View/**Edit**".
 *
 * O oráculo são os slots que o `[CT-03]` não olha: as iniciais (identidade), o badge de situação e o
 * e-mail em metadata. Os três vivem no mesmo `Schemas\UserHeader`, mas é a AUSÊNCIA deles na tela
 * de edição que a partição unidimensional do `[CT-03]` não consegue enxergar.
 */
it('[CT-49] leva identidade, badge e metadata tambem para a tela de edicao', function (): void {
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

/*
|--------------------------------------------------------------------------
| Achados do /code-review — o que a revisão pegou e a suíte não pegava
|--------------------------------------------------------------------------
*/

/**
 * [EXTRA] — a `ViewAction` existe nas listagens dos dois painéis.
 *
 * Achado 4 do `/code-review`: nada asseria isso. Removê-la das duas `table()` mantinha a suíte
 * inteira verde, e o único sintoma seria a ficha ficar inalcançável pela tela — que é a forma mais
 * silenciosa de a feature deixar de existir.
 *
 * A asserção é sobre a estrutura da tabela, e não sobre o HTML: a tabela do kit carrega adiada, e
 * `assertSee('Ver')` dependeria do rótulo traduzido.
 */
it('[EXTRA] oferece a acao de ver na listagem de usuarios dos dois paineis', function (string $painel, string $pagina): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');

    $this->actingAs($admin)->get("/{$painel}/users")->assertSuccessful();

    Livewire::actingAs($admin)
        ->test($pagina)
        ->loadTable()
        ->assertActionExists(TestAction::make('view')->table());
})->with([
    'admin' => ['admin', ListUsers::class],
])->group('kit');

/**
 * [EXTRA] — na Lixeira a ação de ver NÃO aparece, e a ficha da conta excluída responde 404.
 *
 * Regressão do achado 1 do `/code-review`, que era o único de comportamento.
 *
 * O route binding passa por `getEloquentQuery()`
 * (`vendor/filament/filament/src/Resources/Resource/Concerns/HasRoutes.php:resolveRecordRouteBinding:49`)
 * e o `Resource::getEloquentQuery()` do Filament **não** remove o `SoftDeletingScope` — quem o
 * remove é só o `TrashedFilter`, na tabela. Então a linha existia na Lixeira, a ação aparecia
 * (porque `UserPolicy::view()` ignora o registro) e o clique dava 404.
 *
 * As duas metades são necessárias: sem o 404 o caso não explica por que a ação some; sem a ausência
 * da ação o `->visible()` pode ser removido sem nada acusar.
 */
it('[EXTRA] esconde a acao de ver na lixeira, onde a ficha responderia 404', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Conta Excluida']);

    $alvo->delete();

    $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertNotFound();

    $this->actingAs($admin)->get('/admin/users')->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->loadTable()
        ->filterTable('trashed', 'only')
        ->assertCanSeeTableRecords([$alvo])
        ->assertActionHidden(TestAction::make('view')->table($alvo));
})->group('kit');

/**
 * [EXTRA] — o clique na LINHA passa a abrir a ficha, e isso é decisão, não acidente.
 *
 * Achado 2 do `/code-review`. `ListRecords::getTableRecordUrlUsing()` itera `['view', 'edit']` e
 * para na primeira página que existir
 * (`vendor/filament/filament/src/Resources/Pages/ListRecords.php:162-164`). Registrar a rota
 * `view` portanto **mudou o destino do clique** nas duas listagens, de editar para ver.
 *
 * É o comportamento desejado — ver antes de editar é a ordem natural, e é o que `TenantResource`
 * já fazia. Mas é mudança de comportamento numa tela que ninguém pediu para mudar, então ela fica
 * travada por caso: quem remover a rota `view` no futuro vê isto vermelho e decide de propósito.
 */
it('[EXTRA] aponta o clique da linha para a ficha, e nao mais para a edicao', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Destino do Clique']);

    $this->actingAs($admin)->get('/admin/users')->assertSuccessful();

    $pagina = Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->loadTable()
        ->instance();

    $url = ($pagina->getTable()->getRecordUrl($alvo));

    expect($url)->toContain('/admin/users/'.$alvo->getRouteKey())
        ->and($url)->not->toContain('/edit');
})->group('kit');

/**
 * [EXTRA] — a CSS SERVIDA é a CSS do vendor.
 *
 * Achado 5 do `/code-review`. A guarda de namespace lê
 * `vendor/mortalkiller/filament-page-header/resources/css/page-header.css`, mas o navegador recebe
 * `public/css/mortalkiller/filament-page-header/page-header.css`, publicado por
 * `php artisan filament:assets`.
 *
 * O modo de falha da divergência é o de sempre neste assunto: HTML byte a byte correto e sem
 * estilo. Acontece quando o pacote sobe de versão e ninguém republica — e o `post-update-cmd` do
 * `composer.json` republica, mas quem atualiza por `kit:update` não passa por ele.
 *
 * Comparar por conteúdo, e não por data: o `filament:assets` reescreve o arquivo a cada execução.
 */
it('[EXTRA] serve exatamente a CSS que o vendor traz', function (): void {
    $doVendor  = base_path('vendor/mortalkiller/filament-page-header/resources/css/page-header.css');
    $publicada = public_path('css/mortalkiller/filament-page-header/page-header.css');

    expect(file_exists($publicada))->toBeTrue(
        'a CSS do pacote nao esta publicada — rode `php artisan filament:assets`',
    )
        ->and(md5_file($publicada))->toBe(
            md5_file($doVendor),
            'a CSS publicada divergiu da do vendor — rode `php artisan filament:assets`',
        );
})->group('kit');

/**
 * [CT-23/CT-24] — as NOVE ações que o trait acrescenta ao componente não entregam segredo, e as
 * duas que carregam o registro carregam exatamente o mesmo que `[CT-20]` já trava.
 *
 * `HasPageHeader` acrescenta nove métodos públicos, e método de trait conta como declarado pela
 * subclasse — o portão do Livewire é `Utils::getPublicMethodsDefinedBySubClass($root)`
 * (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:570`), que só
 * subtrai `render`. Logo os nove viram ação chamável por `$wire.`, em **seis** telas do kit.
 *
 * ## A auditoria do Blueprint deduziu errado, e este caso é o que mede
 *
 * A auditoria previu que `headerSchema`, `defaultHeaderSchema` e `pageHeaderOptions` — que têm
 * parâmetro tipado (`Schema`, `HeaderOptions`) — estourariam `TypeError` ao serem chamados sem
 * argumento, produzindo 500. **Não estouram.** Medido: os três respondem normalmente.
 *
 * E a medição achou o que a dedução não viu: `defaultHeaderSchema` devolve
 * `{"model": {…as 12 chaves…}}` — o registro, serializado. Não é furo: é o MESMO registro que a
 * pessoa já está autorizada a ver (`ViewRecord::hydrate():83` reautoriza a cada request) e as
 * MESMAS 12 chaves de `[CT-20]`, sem `password` e sem `remember_token`, que o `$hidden` recorta
 * (`app/Models/User.php:96`).
 *
 * O caso trava as duas pontas: nenhuma das nove entrega segredo, e as duas que carregam registro
 * carregam a lista fechada. Coluna nova no `users`, ou accessor entrando em `$appends`, deixa isto
 * vermelho — que é o momento certo de decidir se ela pode viajar.
 */
it('[CT-23/CT-24] nao entrega segredo pelas acoes que o trait acrescenta ao componente', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Alvo', 'email' => 'alvo@example.com']);

    $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful();

    $chamar = fn (string $metodo): mixed => Livewire::actingAs($admin)
        ->test(ViewUser::class, ['record' => $alvo->getRouteKey()])
        ->call($metodo)
        ->effects['returns'][0] ?? null;

    $esperadas = [
        'aprovacao_pendente', 'ativo', 'avatar_url', 'created_at', 'deleted_at', 'email',
        'email_verified_at', 'id', 'name', 'origem', 'updated_at', 'uuid',
    ];

    $osNove = [
        'headerSchema', 'defaultHeaderSchema', 'getPageHeaderRecord', 'getPageHeaderSchemaClass',
        'pageHeaderOptions', 'getPageHeaderOptions', 'getPageHeaderComponent', 'pageHeaderIsEnabled',
        'getHeader',
    ];

    $comRegistro = 0;

    foreach ($osNove as $metodo) {
        $retorno = $chamar($metodo);
        $json    = json_encode($retorno, JSON_THROW_ON_ERROR);

        expect($json)->not->toContain('password', "{$metodo} devolveu algo com 'password'")
            ->and($json)->not->toContain('remember_token', "{$metodo} devolveu algo com 'remember_token'");

        // As que carregam o registro: a lista de chaves é fechada.
        $modelo = match (true) {
            is_array($retorno) && array_key_exists('model', $retorno) => $retorno['model'],
            is_array($retorno) && array_key_exists('email', $retorno) => $retorno,
            default                                                   => null,
        };

        if ($modelo === null) {
            continue;
        }

        $comRegistro++;
        $chaves = array_keys($modelo);
        sort($chaves);

        expect($chaves)->toBe($esperadas, "{$metodo} devolveu um conjunto de colunas diferente do travado");
    }

    // Controle de não-vacuidade: se nenhuma devolvesse registro, o laço acima não afirmaria nada.
    expect($comRegistro)->toBe(2, 'mudou quantas acoes do trait carregam o registro — reavalie');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R1 — o recorte de região é exato (controles negativos do detector)
|--------------------------------------------------------------------------
*/

/**
 * O HTML sintético que os controles de R1 usam.
 *
 * Tem sentinela antes e depois do `fph-root`, um irmão por região e **divs aninhadas** dentro de
 * `fph-badges` e `fph-metadata` — que é o que distingue um recorte balanceado de um `strpos` até o
 * próximo fechamento.
 */
function htmlSinteticoDeHeader(): string
{
    return <<<'HTML'
    <html><body>
      <div class="fi-topbar">SENTINELA-FORA topbar</div>
      <div class="fph-root" data-fph-root data-fph-options='{"mode":"normal"}'>
        <header class="fph-header fi-section" data-fph-header>
          <div class="fph-schema">
            <div class="fph-layout">
              <div class="fph-top">
                <div class="fph-content">
                  <div class="fph-avatar"><span aria-hidden="true">SD-AVATAR</span></div>
                  <div class="fph-main">
                    <div class="fph-title-row">
                      <h1 class="fi-header-heading fph-heading"><span class="fph-heading-icon">ICONE</span>SD-HEADING</h1>
                    </div>
                    <div class="fph-slot fph-inline fph-badges"><div class="fi-badge"><div class="fi-badge-label">SD-BADGES</div></div></div>
                  </div>
                </div>
              </div>
              <div class="fph-details">
                <div class="fph-slot fph-metadata"><div class="fph-field"><div class="fph-field-icon">I</div>SD-METADATA</div></div>
                <div class="fph-slot fph-summary"><div>SD-SUMMARY</div></div>
              </div>
            </div>
          </div>
        </header>
      </div>
      <div class="fi-main">SENTINELA-FORA rodape</div>
    </body></html>
    HTML;
}

/**
 * [CT-01] — o recorte separa o que está dentro do que está fora, inclusive com divs aninhadas.
 *
 * **Este caso vem primeiro no `04` por um motivo medido**: na rodada anterior desta base um
 * predicado foi aplicado sobre uma região grande demais do HTML e **qualquer** texto o satisfazia —
 * 46 de 46 verdes sobre nada. Toda asserção de header roda **dentro** de uma região, e a região sai
 * de um extrator. Extrator não testado é o oráculo nascendo inerte outra vez.
 *
 * As cinco linhas são as cinco formas estruturais distintas, não amostra:
 *
 * | região | o que ela prova |
 * |---|---|
 * | `fph-root` | o header inteiro, e que a sentinela **depois** dele fica fora — mata o extrator que devolve o resto do documento |
 * | `fph-heading` | elemento simples (`<h1>`), com um **filho** cuja classe é prefixo da dele |
 * | `fph-avatar` | elemento simples que pode estar ausente |
 * | `fph-badges` | **com divs aninhadas** — mata o recorte até o primeiro `</div>` |
 * | `fph-metadata` | idem, e é irmão do último bloco da região |
 */
it('[CT-01] recorta exatamente o interior da regiao pedida', function (string $regiao, string $dentro, string $irmao): void {
    $html = htmlSinteticoDeHeader();

    $recorte = regiaoDoHeader($html, $regiao);

    expect($recorte)->not->toBe('', "a regiao {$regiao} nao foi encontrada no HTML sintetico")
        ->and($recorte)->toContain($dentro)
        ->and(str_contains($recorte, 'SENTINELA-FORA'))->toBeFalse(
            "o recorte de {$regiao} vazou para fora do header",
        )
        ->and(str_contains($recorte, $irmao))->toBeFalse(
            "o recorte de {$regiao} engoliu o irmao {$irmao}",
        );
})->with([
    'root, contra a barra superior'        => ['fph-root', 'SD-HEADING', 'topbar'],
    'heading, com filho de classe prefixo' => ['fph-heading', 'SD-HEADING', 'SD-BADGES'],
    'avatar, elemento simples'             => ['fph-avatar', 'SD-AVATAR', 'SD-HEADING'],
    'badges, com divs aninhadas'           => ['fph-badges', 'SD-BADGES', 'SD-METADATA'],
    'metadata, com divs aninhadas'         => ['fph-metadata', 'SD-METADATA', 'SD-SUMMARY'],
])->group('kit');

/**
 * [CT-01] — a classe casa como TOKEN, nunca como substring.
 *
 * `fph-heading-icon` é **filho** de `fph-heading`
 * (`vendor/mortalkiller/filament-page-header/resources/views/components/heading.blade.php:4`).
 * Um extrator que casasse por substring devolveria o ícone no lugar do título, e a asserção
 * "o heading contém o nome" ficaria vermelha por um motivo que não é o defeito.
 *
 * O par positivo/negativo é obrigatório: só a primeira linha passaria num extrator que não
 * achasse nada.
 */
it('[CT-01] casa a classe como token inteiro, nao como substring', function (): void {
    $html = htmlSinteticoDeHeader();

    expect(regiaoDoHeader($html, 'fph-heading'))->toContain('SD-HEADING')
        ->and(regiaoDoHeader($html, 'fph-heading-icon'))->toBe('ICONE')
        // O prefixo sozinho não existe como classe — e não pode cair no irmão mais próximo.
        ->and(regiaoDoHeader($html, 'fph-head'))->toBe('');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — o header mostra ESTE registro
|--------------------------------------------------------------------------
*/

/**
 * [CT-06] — o header mostra este registro, não o de outra conta nem o de quem está olhando.
 *
 * **Três personas distintas, e as três são necessárias.** O mutante que este caso existe para matar
 * (M53) é o metadata lendo o **usuário autenticado** em vez do registro. Com duas personas apenas —
 * ator e alvo — ele morreria; mas a primeira escrita deste caso no `04` usava o próprio ator como
 * uma das contas, e aí o e-mail do ator **nunca apareceria** por outro caminho e a ausência seria
 * vácua. A revisão adversarial chamou isso de "o mutante sobrevivia ao próprio matador".
 *
 * As ausências têm destinatário: as três contas existem, e as três aparecem em ALGUM lugar do
 * banco. O que se afirma é a adjacência — quem aparece DENTRO da região.
 */
it('[CT-06] mostra no cabecalho o registro aberto, nao outro nem o de quem olha', function (): void {
    $rita = usuarioDoKit('admin', 'rita@example.com');
    $rita->forceFill(['name' => 'Rita Nunes'])->save();

    $ana   = User::factory()->create(['name' => 'Ana Prado', 'email' => 'ana@example.com']);
    $bruno = User::factory()->create(['name' => 'Bruno Sales', 'email' => 'bruno@example.com']);

    $html = $this->actingAs($rita)
        ->get("/admin/users/{$ana->getRouteKey()}")
        ->assertSuccessful()
        ->getContent();

    $heading  = regiaoDoHeader($html, 'fph-heading');
    $metadata = regiaoDoHeader($html, 'fph-metadata');

    expect($heading)->not->toBe('')
        ->and($metadata)->not->toBe('')
        ->and($heading)->toContain('Ana Prado')
        ->and(str_contains($heading, 'Bruno Sales'))->toBeFalse()
        ->and(str_contains($heading, 'Rita Nunes'))->toBeFalse()
        ->and($metadata)->toContain('ana@example.com')
        ->and(str_contains($metadata, $bruno->email))->toBeFalse()
        ->and(str_contains($metadata, 'rita@example.com'))->toBeFalse();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — a partição do avatar por esquema de URL
|--------------------------------------------------------------------------
*/

/**
 * [CT-09] — o esquema do URL e o nome decidem se o slot de identidade sobrevive.
 *
 * Partição de domínio de um campo, com a linha de fronteira que quase ninguém escreve: **vazio não
 * é o mesmo que ausente**, e os dois caem no mesmo destino aqui — mas por caminhos diferentes
 * (`blank()` na coluna × `getAvatarUrl()` recusando string vazia).
 *
 * A última linha é o único caminho em que a região **some**: sem avatar E sem nome não há iniciais
 * a calcular, e o `@elseif` de `layout.blade.php:24` não entra em ramo nenhum. Ela é o que impede
 * as outras cinco de passarem num extrator que devolvesse a página inteira.
 */
it('[CT-09] decide o slot de identidade pelo esquema do avatar e pelo nome', function (?string $avatar, string $nome, string $resultado): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => $nome === '' ? 'X' : $nome, 'avatar_url' => $avatar]);

    if ($nome === '') {
        // `factory` e `forceFill` passam por validação nenhuma; o nome vazio é estado de banco.
        $alvo->forceFill(['name' => ''])->save();
    }

    $html = $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertSuccessful()
        ->getContent();

    $avatarRegiao = regiaoDoHeader($html, 'fph-avatar');

    expect(regiaoDoHeader($html, 'fph-root'))->not->toBe('', 'o header nao renderizou');

    match ($resultado) {
        'img'      => expect($avatarRegiao)->toContain('<img')->and($avatarRegiao)->toContain('/storage/'),
        'iniciais' => expect($avatarRegiao)->not->toBe('')
            ->and(str_contains($avatarRegiao, '<img'))->toBeFalse()
            ->and($avatarRegiao)->toContain('<span'),
        default => expect($avatarRegiao)->toBe('', 'sem avatar e sem nome o slot tem de sumir'),
    };
})->with([
    'arquivo no disco publico' => ['avatars/a.png', 'Ana Prado', 'img'],
    'coluna nula'              => [null, 'Ana Prado', 'iniciais'],
    'coluna vazia'             => ['', 'Ana Prado', 'iniciais'],
    'sem avatar e sem nome'    => [null, '', 'ausente'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — a permissão, fora da tela e na barra de ações
|--------------------------------------------------------------------------
*/

/**
 * [CT-12] — o 403 da ficha tem saída.
 *
 * Estado de erro sem caminho de volta é Blocker no catálogo do quality gate, e o par de redirect
 * que se devolve mutuamente já aconteceu nesta base. Aqui a pergunta é mais simples e igualmente
 * necessária: negar a ficha **não** pode derrubar a listagem nem o painel.
 *
 * A ordem importa — o 403 vem primeiro, e as duas respostas de saída depois, no mesmo processo e
 * com a mesma permissão revogada. Conferir a listagem antes provaria só que ela abria **antes** da
 * revogação.
 */
it('[CT-12] nega a ficha sem trancar a listagem nem o painel', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    semAPermissao('admin', 'View:User');

    $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertForbidden();
    $this->actingAs($admin)->get('/admin/users')->assertSuccessful();
    $this->actingAs($admin)->get('/admin')->assertSuccessful();
})->group('kit');

/**
 * [CT-13] — a barreira existe fora da tela.
 *
 * Chamada **direta** à resposta de autorização, sem passar pela página. Um caso que só passa pela
 * tela mede o caminho HTTP; ele ficaria verde numa implementação que barrasse no middleware e
 * deixasse a policy aberta para job, comando e ação em massa — que é o furo que
 * `.ai/rules/filament.md` chama de *"barreira sem teste direto não é barreira"*.
 *
 * O par é indivisível: só o lado que nega passaria numa implementação que nega **todo mundo**.
 */
it('[CT-13] decide a autorizacao de ficha fora da tela, nos dois sentidos', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    $this->actingAs($admin);

    expect(UserResourceAdmin::getViewAuthorizationResponse($alvo)->allowed())->toBeTrue();

    semAPermissao('admin', 'View:User');

    expect(UserResourceAdmin::getViewAuthorizationResponse($alvo)->denied())->toBeTrue();
})->group('kit');

/**
 * [CT-14] — a ação de ficha some para quem não pode abri-la, e volta quando a permissão volta.
 *
 * `Action` do Filament nasce com autorização `null`, ou seja, **liberada para todo mundo**
 * (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:15-22`). Uma `ViewAction` visível para
 * quem leva 403 no clique é a forma mais comum desse default vazar.
 *
 * As duas metades no mesmo caso, e nessa ordem: sem a segunda, uma implementação que escondesse a
 * ação de **todo mundo** passaria.
 */
it('[CT-14] esconde a acao de ficha de quem nao tem a permissao, e a devolve com ela', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    $this->actingAs($admin)->get('/admin/users')->assertSuccessful();

    $papel = semAPermissao('admin', 'View:User');

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('view')->table($alvo));

    $papel->givePermissionTo('View:User');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Livewire::actingAs($admin->fresh())
        ->test(ListUsers::class)
        ->loadTable()
        ->assertActionVisible(TestAction::make('view')->table($alvo));
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — a superfície Livewire: estado forjado não é aceito
|--------------------------------------------------------------------------
*/

/**
 * [CT-21] — o identificador do registro não aceita troca pelo navegador.
 *
 * `$record` é `#[Locked]` em `vendor/filament/filament/src/Resources/Pages/Concerns/InteractsWithRecord.php:16`,
 * e o `02` registra isso como fronteira aplicada. Fronteira de framework herdada é a que ninguém
 * testa — e é a que some numa sobrescrita da `ViewUser` que redeclare a propriedade.
 *
 * O caso escreve a negativa do `02` **como se fosse falsa**: tenta a troca de verdade e exige que
 * o Livewire a recuse. As duas asserções de conteúdo depois são o que prova que a recusa não veio
 * de a página ter morrido.
 */
it('[CT-21] recusa a troca do registro pelo navegador', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $ana   = User::factory()->create(['name' => 'Ana Prado']);
    $bruno = User::factory()->create(['name' => 'Bruno Sales']);

    $this->actingAs($admin)->get("/admin/users/{$ana->getRouteKey()}")->assertSuccessful();

    $componente = Livewire::actingAs($admin)->test(ViewUser::class, ['record' => $ana->getRouteKey()]);

    expect(fn () => $componente->set('record', $bruno->getRouteKey()))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    $html = $componente->html();

    expect(regiaoDoHeader($html, 'fph-heading'))->toContain('Ana Prado')
        ->and(str_contains(regiaoDoHeader($html, 'fph-heading'), 'Bruno Sales'))->toBeFalse();
})->group('kit');

/**
 * [CT-22] — o estado do formulário da ficha não grava nada.
 *
 * `ViewRecord::$data` é `public ?array` **sem** `#[Locked]` (`ViewRecord.php:39`), então o
 * navegador o escreve entre requisições. O `02` declara que isso é inócuo porque a ficha é
 * somente-leitura — e declaração é exatamente o que precisa de oráculo.
 *
 * O mutante que ele mata (M29): alguém copia o molde de um `EditRecord` para a `ViewUser` e traz
 * junto um caminho que persiste `$data`. O caso injeta nome E papel: o nome cobre a coluna, o papel
 * cobre a relação — dois mecanismos de escrita diferentes.
 */
it('[CT-22] nao grava nada a partir do estado de formulario da ficha', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $ana   = User::factory()->create(['name' => 'Ana Prado', 'email' => 'ana@example.com']);

    $this->actingAs($admin)->get("/admin/users/{$ana->getRouteKey()}")->assertSuccessful();

    $html = Livewire::actingAs($admin)
        ->test(ViewUser::class, ['record' => $ana->getRouteKey()])
        ->set('data', ['name' => 'INJETADO', 'roles' => ['admin']])
        ->html();

    $ana->refresh();

    expect($ana->name)->toBe('Ana Prado')
        ->and($ana->hasRole('admin'))->toBeFalse()
        ->and(regiaoDoHeader($html, 'fph-heading'))->toContain('Ana Prado')
        ->and(str_contains(regiaoDoHeader($html, 'fph-heading'), 'INJETADO'))->toBeFalse();
})->group('kit');

/**
 * [CT-25] — o atributo de opções no DOM não carrega dado do registro.
 *
 * `data-fph-options` é lido pelo Alpine (`header.blade.php:16`). O mutante M30 é o schema injetando
 * o registro ali "para facilitar o Alpine" — e o dado sairia no HTML de toda tela com header, fora
 * de qualquer recorte de infolist.
 *
 * **A ausência tem destinatário**: a primeira asserção prova que o e-mail ESTÁ na página. Sem ela,
 * a ausência dentro do atributo passaria com a página em branco. E a última linha impede o caso de
 * passar com o atributo inexistente.
 */
it('[CT-25] nao leva dado do registro para o atributo de opcoes do header', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $ana   = User::factory()->create(['name' => 'Ana Prado', 'email' => 'ana@example.com']);

    $html = $this->actingAs($admin)
        ->get("/admin/users/{$ana->getRouteKey()}")
        ->assertSuccessful()
        ->getContent();

    expect(regiaoDoHeader($html, 'fph-metadata'))->toContain('ana@example.com');

    /*
     * Aspas DUPLAS, e o JSON dentro sai escapado em `&quot;` — medido no HTML servido. A primeira
     * escrita deste caso procurou aspas simples (a forma que o fixture sintético de R1 usa) e
     * reprovou com "o atributo nao esta no HTML", que se lê como o defeito e não era.
     */
    expect(preg_match('~data-fph-options="([^"]*)"~', $html, $achado))->toBe(
        1,
        'o atributo de opcoes do header nao esta no HTML',
    );

    $opcoes = $achado[1];

    expect($opcoes)->not->toBe('')
        ->and(str_contains($opcoes, 'ana@example.com'))->toBeFalse()
        ->and(str_contains($opcoes, 'Ana Prado'))->toBeFalse();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — o conteúdo do header é escapado
|--------------------------------------------------------------------------
*/

/**
 * [CT-27] — o título escapa marcação e preserva texto.
 *
 * A metade **comportamental** de R7. O caso de arquitetura (`[CT-26]`) varre o fonte e morre no dia
 * em que alguém desligar o escape por outro caminho — `Str::of()->toHtmlString()`, por exemplo, que
 * nenhum `grep` por `html: true` encontra (M35). Este prova o escape no HTML servido e não sabe
 * nada sobre a linha que o desligou. Os dois, e nenhum substitui o outro.
 *
 * As cinco linhas cobrem a partição do campo: marcação (a que mata), entidade simples, acento,
 * emoji de 4 bytes e o limite do `varchar`.
 */
it('[CT-27] escapa marcacao no titulo e preserva o resto do texto', function (string $nome, string $esperado, ?string $proibido): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create();

    $alvo->forceFill(['name' => $nome])->save();

    $heading = regiaoDoHeader(
        $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent(),
        'fph-heading',
    );

    expect($heading)->not->toBe('')->and($heading)->toContain($esperado);

    if ($proibido !== null) {
        expect(str_contains($heading, $proibido))->toBeFalse(
            "o titulo nao escapou: {$proibido} chegou cru ao HTML",
        );
    }
})->with([
    'marcacao — a linha que mata' => ['<script>alert(1)</script>', '&lt;script&gt;', '<script>'],
    'entidade simples'            => ['Ana & Bruno', '&amp;', null],
    'acento'                      => ['José Antônio', 'José Antônio', null],
    'emoji de 4 bytes'            => ['Ana 🎯 Prado', '🎯', null],
    'limite do varchar'           => [str_repeat('a', 255), str_repeat('a', 255), null],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — o controle negativo da guarda de CSS
|--------------------------------------------------------------------------
*/

/**
 * [CT-29] — o detector de classes acha uma agulha plantada, nas DUAS formas de emissão.
 *
 * Controle negativo de `[CT-28]`, e ele não é opcional: um extrator que devolvesse lista vazia, ou
 * que só lesse `class="…"`, passaria em CT-28 pelo piso e pela subtração vazia ao mesmo tempo.
 *
 * As duas formas existem no vendor de verdade: `fph-heading`, `fph-subheading` e `fph-layout` saem
 * de `ComponentAttributeBag->class([…])`, não de `class="…"` — e são justamente as que o `05` usa
 * como âncora de tema. Um regex que só lesse a primeira forma acharia 24 de 27 e pareceria bom.
 */
it('[CT-29] acha a agulha plantada nas duas formas de emissao', function (): void {
    $pasta = sys_get_temp_dir().'/fph-agulha-'.bin2hex(random_bytes(6));

    mkdir($pasta, 0o777, true);

    file_put_contents($pasta.'/plantada.blade.php', implode("\n", [
        '<div class="fph-agulha fi-section">',
        '    <h1 {{ (new \Illuminate\View\ComponentAttributeBag($x))->class([\'fph-agulha-bag\', \'fi-header-heading\']) }}>t</h1>',
        '</div>',
    ]));

    try {
        $classes = classesEmitidasPorBlades($pasta);
    } finally {
        array_map(unlink(...), glob($pasta.'/*') ?: []);
        rmdir($pasta);
    }

    expect(in_array('fph-agulha', $classes, true))->toBeTrue('o extrator perdeu a classe emitida por class="…"')
        ->and(in_array('fph-agulha-bag', $classes, true))->toBeTrue('o extrator perdeu a classe emitida por ComponentAttributeBag')
        // E o detector não inventa: a classe do Filament também é vista, e nada além.
        ->and(in_array('fi-section', $classes, true))->toBeTrue()
        ->and(count($classes))->toBe(4);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — a ação navega, e a ficha não é a edição
|--------------------------------------------------------------------------
*/

/**
 * [CT-31] — a ação de ficha navega em vez de abrir modal.
 *
 * `Resources\Pages\Page::getDefaultActionUrl():361` só devolve URL quando `hasPage('view')` é
 * verdadeiro; sem a página registrada a `ViewAction` abre **modal**, que é outra tela, sem rota e
 * sem o header do pacote. O mutante M41 é exatamente isso, e ele é invisível para todo caso que só
 * abra a URL direto.
 *
 * A última asserção separa a ficha da edição: sem ela, uma rota `view` apontando para a `EditUser`
 * (M42, o copiar-e-colar) passaria.
 */
it('[CT-31] faz a acao de ficha navegar para a rota da ficha', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    $this->actingAs($admin)->get('/admin/users')->assertSuccessful();

    $pagina = Livewire::actingAs($admin)->test(ListUsers::class)->loadTable()->instance();
    $acao   = $pagina->getTable()->getAction('view')->record($alvo);

    $url = $acao->getUrl();

    expect($url)->not->toBeNull('a acao de ficha nao tem destino — ela abriria modal')
        ->and($url)->toContain('/admin/users/'.$alvo->getRouteKey())
        ->and(str_ends_with((string) $url, '/edit'))->toBeFalse();
})->group('kit');

/**
 * [CT-32] — a ficha e a edição são telas distintas, e as duas têm o header.
 *
 * O oráculo que distingue as duas é o **campo editável**: a edição tem `<input name="data.name"`,
 * a ficha não. `assertSee` do nome não distingue — ele passa nas duas.
 *
 * A última linha é o que impede o caso de virar prova de que a ficha está quebrada: as duas telas
 * respondem 200 **e** as duas renderizam o header do pacote.
 */
it('[CT-32] separa a ficha da edicao, e poe o header nas duas', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    $ficha  = $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent();
    $edicao = $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}/edit")->assertSuccessful()->getContent();

    /*
     * O marcador NAO e `<input name="data.name">`: o Filament 5 nao emite `name` nos campos, e a
     * primeira escrita deste caso reprovou por isso — media a ausencia do ATRIBUTO em vez da
     * ausencia do formulario, e teria ficado verde na ficha por acidente.
     *
     * O que distingue as duas telas e o `wire:partial` que cada schema emite. Medido: a edicao tem
     * 5 ocorrencias de `form.` e zero de `infolist.`; a ficha, zero e 11.
     */
    expect(substr_count($edicao, 'schema-component::form.'))->toBeGreaterThan(0, 'a edicao nao renderizou formulario')
        ->and(substr_count($ficha, 'schema-component::form.'))->toBe(0, 'a ficha renderizou formulario — ela e a edicao')
        ->and(substr_count($ficha, 'schema-component::infolist.'))->toBeGreaterThan(0, 'a ficha nao renderizou infolist')
        ->and(regiaoDoHeader($ficha, 'fph-root'))->not->toBe('')
        ->and(regiaoDoHeader($edicao, 'fph-root'))->not->toBe('');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R11 — os slots refletem ESTE registro
|--------------------------------------------------------------------------
*/

/**
 * [CT-38] — a origem da conta aparece traduzida, nunca crua.
 *
 * Partição da coluna `origem`, com a linha de ausência decidindo por **presença do default**: com
 * `origem` nula não existe "valor cru" para não conter, e uma asserção de ausência ali seria
 * indefinida. `User::rotuloDaOrigem():478` faz o default cair em "Interno".
 *
 * Os quatro rótulos ficam **fixados aqui**, e não lidos da implementação. Valor esperado não
 * especificado obriga quem escreve o teste a ir buscá-lo no código — e aí o caso afirma que a
 * implementação concorda consigo mesma. Se os rótulos reais divergirem destes, **a divergência é o
 * achado**.
 */
it('[CT-38] mostra a origem da conta traduzida no metadata', function (string $origem, string $rotulo, ?string $proibido): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    $alvo->forceFill(['origem' => $origem])->save();

    $metadata = regiaoDoHeader(
        $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent(),
        'fph-metadata',
    );

    expect($metadata)->not->toBe('')->and($metadata)->toContain($rotulo);

    if ($proibido !== null) {
        expect(str_contains($metadata, $proibido))->toBeFalse("a origem saiu crua: {$proibido}");
    }
})->with([
    'default da coluna'      => ['interno', 'Interno', 'interno'],
    'provedor social'        => ['google', 'Google', 'google'],
    'fluxo de convite'       => ['convite', 'Convite', 'convite'],
    /*
     * O `04` pede aqui a linha do NULO. Medido: `users.origem` e NOT NULL, entao o nulo nao existe
     * como estado de banco — a linha reprovava com violacao de integridade, que nao e o defeito.
     *
     * A particao equivalente, e a que de fato acontece, e o valor DESCONHECIDO: provedor social
     * removido do enum, coluna editada a mao, restore de dump antigo. `rotuloDaOrigem():482` manda
     * os dois para o mesmo `default`, e e esse caminho que se quer cobrir.
     */
    'valor desconhecido cai no default' => ['provedor-extinto', 'Interno', 'provedor-extinto'],
])->group('kit');

/**
 * [CT-39] — a data de criação sai no fuso configurado do aplicativo, não em UTC cravado.
 *
 * **O `04` supõe que o kit roda em `America/Sao_Paulo`. Não roda**: `config('app.timezone')` é
 * `UTC` (medido). E mudar o config em tempo de execução **não** desloca a data: entrada de data do
 * Filament resolve o fuso por `FilamentTimezone::get()`, e não pelo config direto
 * (`vendor/filament/infolists/src/Components/Concerns/CanFormatState.php:472`). A primeira escrita
 * deste caso trocava o config e reprovava com `11/03/2026`, medindo a coisa errada.
 *
 * O invariante que sobrevive, e é o que importa, é: a data exibida segue o **fuso de exibição**, e
 * não um `UTC` cravado no formatador. Esse é o botão que um usuário do kit gira. As duas metades
 * ficam no mesmo caso — sem a primeira, um formatador cravado em São Paulo passaria.
 *
 * O instante é escolhido dentro da janela de divergência: 02:30 UTC é 23:30 do dia anterior em São
 * Paulo. Às 14:00 as duas implementações dariam a mesma resposta e o caso não distinguiria nada.
 *
 * 2026-03-10 evita a virada de horário de verão do hemisfério norte, que traria um segundo motivo
 * de falha ao mesmo caso.
 */
it('[CT-39] mostra a data de criacao no fuso de exibicao do Filament', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    // 02:30 UTC de 11/03 é 23:30 de 10/03 em São Paulo — a janela em que os dois divergem.
    $alvo->forceFill(['created_at' => '2026-03-11 02:30:00'])->save();

    $metadataDaFicha = function () use ($admin, $alvo): string {
        $metadata = regiaoDoHeader(
            $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent(),
            'fph-metadata',
        );

        expect($metadata)->not->toBe('');

        return $metadata;
    };

    // Com o fuso de exibição do kit (UTC), o instante cai no dia 11.
    expect($metadataDaFicha())->toContain('11/03/2026');

    FilamentTimezone::set('America/Sao_Paulo');

    try {
        $comSaoPaulo = $metadataDaFicha();

        // O MESMO instante, no fuso de exibição de São Paulo, cai no dia 10.
        expect($comSaoPaulo)->toContain('10/03/2026')
            ->and(str_contains($comSaoPaulo, '11/03/2026'))->toBeFalse(
                'a data ignorou o fuso de exibicao — o formatador esta com UTC cravado',
            );
    } finally {
        FilamentTimezone::set(config('app.timezone'));
    }
})->group('kit');

/*
|--------------------------------------------------------------------------
| R12 — o trait não é neutralizado por sobrescrita
|--------------------------------------------------------------------------
*/

/**
 * As páginas de `app/Filament/**\/Pages` que aplicam o trait do header e as que declaram
 * `getHeader()`.
 *
 * Função, e não código inline, porque `[CT-42]` é o controle negativo dela — ele planta um arquivo
 * sintético que faz as duas coisas e exige que este MESMO detector o acuse.
 *
 * @return array{comTrait: list<string>, sobrescrevem: list<string>}
 */
function paginasComTraitDeHeader(string $diretorio): array
{
    $comTrait     = [];
    $sobrescrevem = [];

    foreach (Finder::create()->files()->in($diretorio)->name('*.php') as $arquivo) {
        // Comentário fora ANTES de afirmar ausência: os arquivos do kit citam o que proíbem.
        $codigo = semComentarios((string) $arquivo->getContents());
        $nome   = $arquivo->getRelativePathname();

        if (preg_match('~\buse\s+[\w\\\\]*HasPageHeader\s*;~', $codigo) !== 1) {
            continue;
        }

        $comTrait[] = $nome;

        // `function getHeader`, nunca a string solta: `$page->getHeader()` é chamada, não declaração.
        if (preg_match('~function\s+getHeader\s*\(~', $codigo) === 1) {
            $sobrescrevem[] = $nome;
        }
    }

    sort($comTrait);
    sort($sobrescrevem);

    return ['comTrait' => $comTrait, 'sobrescrevem' => $sobrescrevem];
}

/**
 * [CT-41] — nenhuma página que aplica o trait declara `getHeader()`.
 *
 * É o modo de falhar mais silencioso da entrega inteira: uma página com `getHeader()` próprio torna
 * o pacote **inerte, sem erro nenhum** — o vendor diz isso em
 * `vendor/mortalkiller/filament-page-header/docs/specification.md:15` (*"a page getHeader override
 * wins"*). Hoje `getHeader()` tem zero ocorrências em `app/`; o caso existe para que continue assim
 * quando alguém copiar um molde de outra base.
 *
 * **Os dois pisos são o que impede o falso verde.** Sem eles, o caso fica verde no dia em que a
 * varredura olhar um diretório vazio (regex, glob ou caminho errados) e no dia em que o trait não
 * for aplicado a página nenhuma — que é o mutante M5.
 */
it('[CT-41] nao deixa pagina com o trait declarar getHeader', function (): void {
    $paginas = paginasComTraitDeHeader(base_path('app/Filament'));

    expect(count($paginas['comTrait']))->toBeGreaterThanOrEqual(
        6,
        'menos de seis paginas aplicam o trait — a varredura olhou o lugar errado, ou o trait sumiu',
    )
        ->and($paginas['sobrescrevem'])->toBe([]);
})->group('kit');

/**
 * [CT-50] — o trait está nas seis telas do escopo e **em mais nenhuma**.
 *
 * Cenário trazido pela revisão adversarial (R13b). O piso de `[CT-41]` não basta, e ela nomeou
 * por quê: quem aplica o trait numa
 * `Page` base "para não esquecer nenhuma" satisfaz "pelo menos seis" e leva o header para telas que
 * não o pediram. A lista é **nominal**, com `toBe` — nunca `toContain` nem piso.
 *
 * A segunda asserção é a metade comportamental: a listagem de contas, que é do mesmo painel e do
 * mesmo resource, continua **sem** header.
 */
it('[CT-50] aplica o trait do header exatamente nas seis telas do escopo', function (): void {
    $paginas = paginasComTraitDeHeader(base_path('app/Filament'));

    $esperadas = [
        'Admin/Resources/Tenants/Pages/EditTenant.php',
        'Admin/Resources/Tenants/Pages/ViewTenant.php',
        'Admin/Resources/Users/Pages/EditUser.php',
        'Admin/Resources/Users/Pages/ViewUser.php',
        'App/Resources/Users/Pages/EditUser.php',
        'App/Resources/Users/Pages/ViewUser.php',
    ];

    $normalizadas = array_map(
        static fn (string $caminho): string => str_replace(DIRECTORY_SEPARATOR, '/', $caminho),
        $paginas['comTrait'],
    );

    sort($normalizadas);

    expect($normalizadas)->toBe($esperadas);

    $admin = usuarioDoKit('admin', 'admin@example.com');
    $html  = $this->actingAs($admin)->get('/admin/users')->assertSuccessful()->getContent();

    expect(regiaoDoHeader($html, 'fph-root'))->toBe('', 'a listagem ganhou header — o trait vazou');
})->group('kit');

/**
 * [CT-42] — o detector acha a agulha plantada, e não confunde chamada com declaração.
 *
 * Controle negativo de `[CT-41]`. Sem ele, um detector que procurasse no diretório errado, ou que
 * nunca casasse o `use` do trait, devolveria conjuntos vazios e as duas asserções de CT-41
 * passariam por vacuidade.
 *
 * A terceira asserção cobre M56: procurar a string `getHeader` sem o `function` casaria a **chamada**
 * `$page->getHeader()`, e a varredura acusaria uma página legítima.
 */
it('[CT-42] acha a pagina plantada que neutraliza o trait', function (): void {
    $pasta = sys_get_temp_dir().'/fph-paginas-'.bin2hex(random_bytes(6));

    mkdir($pasta, 0o777, true);

    file_put_contents($pasta.'/Neutralizada.php', implode("\n", [
        '<?php',
        'class Neutralizada extends ViewRecord {',
        '    use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;',
        '    public function getHeader(): ?View { return null; }',
        '}',
    ]));

    file_put_contents($pasta.'/Legitima.php', implode("\n", [
        '<?php',
        'class Legitima extends ViewRecord {',
        '    use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;',
        '    public function algo(): void { $outro = $this->getHeader(); }',
        '}',
    ]));

    file_put_contents($pasta.'/SoComentario.php', implode("\n", [
        '<?php',
        '// NAO declare function getHeader aqui — o trait vira inerte.',
        'class SoComentario extends ViewRecord {',
        '    use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;',
        '}',
    ]));

    try {
        $paginas = paginasComTraitDeHeader($pasta);
    } finally {
        array_map(unlink(...), glob($pasta.'/*') ?: []);
        rmdir($pasta);
    }

    expect($paginas['comTrait'])->toHaveCount(3)
        // A declaração é acusada.
        ->and($paginas['sobrescrevem'])->toBe(['Neutralizada.php'])
        // A chamada não é declaração, e o comentário não é código.
        ->and(in_array('Legitima.php', $paginas['sobrescrevem'], true))->toBeFalse()
        ->and(in_array('SoComentario.php', $paginas['sobrescrevem'], true))->toBeFalse();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R13i — o ciclo de vida do registro
|--------------------------------------------------------------------------
*/

/**
 * [CT-43] — a ficha de uma conta excluída logicamente não abre, e volta a abrir ao restaurar.
 *
 * O route binding passa por `getEloquentQuery()`, e o `Resource::getEloquentQuery()` do Filament
 * **não** remove o `SoftDeletingScope` — quem o remove é só o `TrashedFilter`, na tabela. Logo a
 * conta excluída responde 404.
 *
 * **A última metade é o controle, e sem ela o caso é vácuo**: um 404 também é o que uma rota
 * inexistente devolve. Restaurar e receber 200 com `fph-root` é o que prova que o 404 veio do
 * estado do registro, e não da tela faltando.
 */
it('[CT-43] nao abre a ficha de conta excluida, e abre de novo ao restaurar', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Conta Excluida']);

    $alvo->delete();

    $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertNotFound()
        ->assertDontSee('Conta Excluida');

    $alvo->restore();

    $html = $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertSuccessful()
        ->getContent();

    expect(regiaoDoHeader($html, 'fph-root'))->not->toBe('');
})->group('kit');

/**
 * [CT-44] — identificador inexistente não abre, mas a rota existe.
 *
 * O par do CT-43 pelo outro lado da mesma armadilha: **404 é a resposta de uma rota que não
 * existe**. Sem a segunda metade — o mesmo endereço, com um identificador real, respondendo 200
 * com `fph-root` — este caso ficaria verde com a `ViewUser` inteira removida.
 *
 * Era a linha que faltava no `04` original, e a revisão a acrescentou: CT-43 tinha o controle,
 * CT-44 não.
 */
it('[CT-44] nao abre a ficha de identificador inexistente, e a rota continua existindo', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = User::factory()->create(['name' => 'Ana Prado']);

    $this->actingAs($admin)
        ->get('/admin/users/'.Str::uuid()->toString())
        ->assertNotFound();

    $html = $this->actingAs($admin)
        ->get("/admin/users/{$alvo->getRouteKey()}")
        ->assertSuccessful()
        ->getContent();

    expect(regiaoDoHeader($html, 'fph-root'))->not->toBe('');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R13g — as duas células que a matriz de R4 omitia
|--------------------------------------------------------------------------
*/

/**
 * [CT-55] — o papel de infraestrutura não alcança a ficha de conta.
 *
 * **Esta célula existe porque a justificativa anterior era falsa.** Ela saíra da matriz como
 * *"não se aplica: `User` não é do painel `/infra`"*, o que confunde duas afirmações diferentes:
 * *"o Resource não está registrado no `/infra`"* e *"o papel `infra` não alcança a tela do
 * `/admin`"*. `View:User` é permissão do Shield — **global, não por painel** — e o papel `infra`
 * existe, é semeado e autentica.
 *
 * **O que de fato barra, e o caso diz isso em voz alta**: a porta do painel.
 * `User::canAccessPanel():156` compara `roles.painel` com o id do painel, e `infra` não é `admin`.
 * A barreira que dispara é mais forte que a permissão — mas é a permissão que a matriz precisava
 * auditar, e agora a célula está exercitada em vez de dispensada por um motivo inventado.
 *
 * A última asserção é o controle no mesmo processo: sem ela, um 403 universal passaria.
 */
it('[CT-55] nao deixa o papel de infraestrutura abrir a ficha de conta', function (bool $proprioRegistro): void {
    $deInfra = usuarioDoKit('infra', 'infra@example.com');
    $deInfra->forceFill(['name' => 'Ivo Infra'])->save();

    $alvo = $proprioRegistro ? $deInfra : User::factory()->create(['name' => 'Ana Prado']);

    $resposta = $this->actingAs($deInfra)->get("/admin/users/{$alvo->getRouteKey()}");

    expect(in_array($resposta->getStatusCode(), [403, 404], true))->toBeTrue(
        'esperava 403 ou 404, veio '.$resposta->getStatusCode(),
    )
        ->and(str_contains($resposta->getContent(), $alvo->name))->toBeFalse()
        ->and(regiaoDoHeader($resposta->getContent(), 'fph-root'))->toBe('');

    // Controle no MESMO processo: a tela existe e abre para quem pode.
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $html  = $this->actingAs($admin)->get("/admin/users/{$alvo->getRouteKey()}")->assertSuccessful()->getContent();

    expect(regiaoDoHeader($html, 'fph-root'))->not->toBe('');
})->with([
    'conta de terceiro' => [false],
    'conta propria'     => [true],
])->group('kit');

/**
 * [CT-56] — abrir a **própria** ficha também exige a permissão.
 *
 * Dimensão que a matriz não tinha, e é onde `View:User` costuma ser contornada com um
 * `if ($record->is($user))`. O requisito não decide o caso da conta própria, então a direção é por
 * **falha fechado**: nega.
 *
 * **O invariante vale em qualquer resposta** a essa pergunta em aberto: seja qual for a decisão
 * sobre a conta própria, ela nunca é *mais permissiva* do que a decisão sobre a conta do colega.
 * É a última asserção, e é ela que sobrevive se o solicitante decidir o contrário.
 */
it('[CT-56] exige a permissao tambem para abrir a propria ficha', function (): void {
    $pessoa = usuarioDoKit('admin', 'pessoa@example.com');
    $colega = User::factory()->create(['name' => 'Ana Prado']);

    $papel = semAPermissao('admin', 'View:User');

    $propria  = $this->actingAs($pessoa)->get("/admin/users/{$pessoa->getRouteKey()}");
    $doColega = $this->actingAs($pessoa)->get("/admin/users/{$colega->getRouteKey()}");

    $propria->assertForbidden();
    $doColega->assertForbidden();

    expect(regiaoDoHeader($propria->getContent(), 'fph-root'))->toBe('');

    $papel->givePermissionTo('View:User');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $comPermissao = $this->actingAs($pessoa->fresh())->get("/admin/users/{$pessoa->getRouteKey()}");

    $comPermissao->assertSuccessful();

    expect(regiaoDoHeader($comPermissao->getContent(), 'fph-root'))->not->toBe('')
        // O invariante, que vale mesmo se a premissa da conta própria for invertida.
        ->and($propria->getStatusCode())->toBe($doColega->getStatusCode());
})->group('kit');

/*
|--------------------------------------------------------------------------
| R10 e R13f — o registro documental e o aviso do kit:update
|--------------------------------------------------------------------------
*/

/**
 * [CT-36] — a doc de atualização manda instalar a dependência, publicar os assets e ressemear.
 *
 * RQ-12 assume **duas** coisas: que o comando avise, e que o passo manual esteja documentado. Esta
 * é a segunda metade; a primeira é `[CT-54]`.
 *
 * Os dois seeders estão aqui porque eles são a lacuna real que a ADR-06 nomeia: os "próximos
 * passos" que o `kit:update` imprime citam `filament:assets` e os testes, e **não** citam os
 * seeders — e tela nova costuma trazer permissão que nasce sem dono no banco de quem atualiza.
 *
 * A última asserção é a que impede a doc `en` de ficar para trás da `pt` (M47). Ela compara o
 * **número de seções**, que é estrutura, e não texto — tradução muda as palavras e não a forma.
 *
 * **`skip` fora da árvore do kit, e não é opcional**: `docs/` não está em `KitUpdate::CAMINHOS_DO_KIT`
 * e sai do pacote por `export-ignore`, então num projeto que nasceu do kit estes dois arquivos não
 * existem — sem a sentinela, o caso ficaria vermelho em TODA instalação. É o que
 * `tests/Kit/RedeDeDocumentacaoTest.php:[CT-10]:210` reprova, e ele reprovou esta entrega.
 */
it('[CT-36] manda instalar, publicar assets e ressemear na doc de atualizacao', function (string $pagina): void {
    $texto = (string) file_get_contents(base_path($pagina));

    expect($texto)->toContain('mortalkiller/filament-page-header')
        ->and($texto)->toContain('composer require')
        ->and($texto)->toContain('filament:assets')
        ->and($texto)->toContain('ShieldPermissionsSeeder')
        ->and($texto)->toContain('PapeisSeeder');
})->with([
    'pt' => ['docs/pt/comecar/atualizando-o-projeto.md'],
    'en' => ['docs/en/comecar/atualizando-o-projeto.md'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update nao entrega o site (export-ignore): fora da arvore do kit estes arquivos nao existem.')->group('kit');

/**
 * [CT-36] — as duas traduções da página de atualização têm a mesma estrutura.
 *
 * Caso irmão do de cima, e separado dele de propósito: aquele afirma sobre CADA página; este afirma
 * sobre a RELAÇÃO entre as duas. Um dataset não expressa relação.
 *
 * O piso de seções é o controle de não-vacuidade: duas páginas vazias também têm o mesmo número de
 * seções.
 */
it('[CT-36] mantem a doc de atualizacao em paridade entre pt e en', function (): void {
    $pt = secoesDoMarkdown('docs/pt/comecar/atualizando-o-projeto.md');
    $en = secoesDoMarkdown('docs/en/comecar/atualizando-o-projeto.md');

    expect(count($pt))->toBeGreaterThanOrEqual(8, 'a pagina pt tem poucas secoes — o extrator olhou o lugar errado')
        ->and(count($en))->toBe(count($pt), 'a doc en ficou para tras da pt');
})->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update nao entrega o site (export-ignore): fora da arvore do kit estes arquivos nao existem.')->group('kit');

/**
 * [CT-54] — o relatório do `kit:update` acusa a dependência nova.
 *
 * **Divergência declarada do `04`, com o motivo.** O cenário pede o comando rodando "em modo de
 * relatório". Rodar de verdade exigiria um repositório git de mentira, com duas tags do kit e um
 * remote — e o que isso mediria a mais seria o `git diff`, que é do git, não do kit. O que é DO KIT
 * aqui é o **filtro**: qual linha do diff vira aviso.
 *
 * Então o caso extrai o filtro do fonte **em tempo de execução** e o aplica a um diff sintético.
 * Extrair em vez de copiar é o que impede o caso de virar documentação do passado: mudar o regex na
 * fonte muda o que o caso mede, e um regex quebrado deixa as duas primeiras asserções vermelhas.
 *
 * As três linhas do diff sintético são as três partições: a dependência entrando (tem de aparecer),
 * um script entrando (tem de aparecer — o aviso não é só sobre pacote) e uma linha de contexto que
 * não é nem uma nem outra (não pode aparecer, senão o aviso vira o diff inteiro).
 */
it('[CT-54] filtra do diff do composer.json a dependencia nova e os scripts', function (): void {
    $fonte = (string) file_get_contents(base_path('app/Console/Commands/KitUpdate.php'));

    expect(preg_match('~preg_match\(\'(/\^\[\+-\][^\']+/)\'~', $fonte, $extraido))->toBe(
        1,
        'o filtro de linhas do relatorio do composer.json nao foi encontrado no fonte',
    );

    $filtro = $extraido[1];

    $diffSintetico = [
        '+        "mortalkiller/filament-page-header": "^2.1.5",',
        '+        "test:kit": "@php artisan test --testsuite=Kit",',
        '-        "filament/filament": "^5.6",',
        ' {',
        '     "require": {',
        '@@ -30,6 +30,7 @@',
    ];

    $relevantes = array_values(array_filter(
        $diffSintetico,
        static fn (string $linha): bool => (bool) preg_match($filtro, $linha),
    ));

    expect($relevantes)->toHaveCount(3)
        ->and(implode("\n", $relevantes))->toContain('mortalkiller/filament-page-header')
        ->and(implode("\n", $relevantes))->toContain('test:kit')
        // Contexto e cabeçalho de hunk ficam de fora: o aviso não pode virar o diff inteiro.
        ->and(implode("\n", $relevantes))->not->toContain('@@');
})->group('kit');

/**
 * [CT-54] — o relatório existe, é chamado, e a ressalva que o torna inerte está registrada.
 *
 * A outra metade: o filtro acima não serve de nada se ninguém o chamar. E há uma ressalva medida
 * que o `02` registra — `relatarComposerJson()` **retorna cedo** quando a origem é nula, ou seja,
 * quem não tem `config('kit.version')` casando com uma tag do kit **nunca vê o aviso**.
 *
 * O caso trava as três coisas: o método existe, o fluxo o chama, e o retorno cedo continua lá. A
 * terceira não é decoração — se alguém "consertar" o retorno cedo sem entender, o comando passa a
 * estourar em projeto sem versão marcada.
 */
it('[CT-54] chama o relatorio do composer.json no fluxo, e mantem a guarda de origem nula', function (): void {
    $fonte = semComentarios((string) file_get_contents(base_path('app/Console/Commands/KitUpdate.php')));

    expect(substr_count($fonte, 'function relatarComposerJson'))->toBe(1)
        // Chamado de algum lugar, e não só declarado.
        ->and(substr_count($fonte, 'relatarComposerJson('))->toBeGreaterThan(1)
        // O `composer.json` continua na lista do que é SÓ relatado, nunca aplicado.
        ->and($fonte)->toContain('CAMINHOS_SO_RELATORIO');

    $corpo = substr($fonte, (int) strpos($fonte, 'function relatarComposerJson'));
    $corpo = substr($corpo, 0, (int) strpos($corpo, "\n    }\n"));

    expect($corpo)->toContain('$origem === null');
})->group('kit');
