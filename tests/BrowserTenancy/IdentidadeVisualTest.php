<?php

use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * A identidade visual vista pelo navegador, com multi-tenancy ligada.
 *
 * Por que esta feature depende de navegador mais que a média: cor e logo são INVISÍVEIS para
 * teste HTTP. `$this->get('/app/acme')` devolve o mesmo corpo para qualquer cor — as CSS vars
 * entram no `<head>` pelo `@filamentStyles` (`AssetManager.php:279-305`) e a cor efetiva depende
 * de o navegador aplicar a variável. `tests/Tenancy/IdentidadeVisualTenancyTest.php` prova que a
 * cor foi REGISTRADA; só o navegador prova que ela APARECE.
 *
 * Pasta separada de `tests/Browser` pela mesma razão que separa `tests/Tenancy` de `tests/Kit`:
 * `Tests\TenancyTestCase` fixa `permission.teams` antes das migrations, e o Pest não permite dois
 * TestCases na mesma pasta.
 *
 * O arquivo do fallback — sem organização nenhuma — é `tests/Browser/IdentidadeVisualPadraoTest`.
 */
beforeEach(function (): void {
    // Mesmo par de tests/Kit/PaineisTest.php: papel sem a matriz do Shield abre painel e não
    // abre tela.
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * O CT-B04 escreve no disk `public` DE VERDADE — `Storage::fake()` não serve, porque o navegador
 * faz request HTTP à URL da logo. A limpeza vive aqui, e não no fim do caso, para acontecer também
 * quando ele falha no meio; apagar arquivo que não existe é no-op.
 */
afterEach(function (): void {
    Storage::disk('public')->delete('organizacoes/logos/acme-teste-browser.png');

    foreach (['organizacoes/logos/acme-dark-teste-browser.png', 'kit/logo-teste-browser.png', 'kit/logo-dark-teste-browser.png'] as $arquivo) {
        Storage::disk('public')->delete($arquivo);
    }
});

/**
 * CT-B01 — o caso que RESPONDE a ambiguidade do RQ-09, antes de qualquer correção.
 *
 * O requisito diz "acho que hoje está apenas abrindo uma modal". O código diz o contrário para a
 * tabela: com a página `edit` registrada, `Page::getDefaultActionUrl()` devolve URL e o Filament
 * renderiza `<a href>` em vez de `wire:click` (`Page.php:373-380`, `Action.php:889`). Este caso
 * decide qual dos dois vale — e por isso ele existe mesmo que passe de primeira. Ver ADR-06.
 *
 * Uma organização só, e não o cenário de duas: com dois registros na tabela haveria dois botões
 * "Editar" e o clique por texto seria ambíguo.
 */
it('abre a tela cheia de edicao da organizacao', function (): void {
    $organizacao = Tenant::factory()->comIdentidadeVisual('#7c3aed')->create(['nome' => 'Acme', 'slug' => 'acme']);

    $this->actingAs(usuarioCom('admin'));

    visit('/admin/organizacoes')
        ->assertSee('Acme')
        ->click('Editar')
        // `assertPathIs` PRIMEIRO: é ele que espera a navegação terminar. Invertido, o
        // `assertSee` é avaliado contra o snapshot da página anterior e falha dizendo que não
        // achou o texto — com a ação tendo funcionado. Ver `.ai/rules/testes-browser.md`.
        ->assertPathIs("/admin/organizacoes/{$organizacao->getRouteKey()}/edit")
        // A Section nova só existe na tela cheia: se o EditAction tivesse aberto modal, o path
        // continuaria em `/admin/organizacoes` e esta linha nunca seria alcançada.
        ->assertSee('Identidade visual')
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B02 — o CT-B central: a cor chega à tela, e duas organizações diferem.
 *
 * `script()` e não screenshot: cor exata em pixel é frágil (antialiasing, perfil de cor), e a CSS
 * var é o contrato que o Filament publica. Comparar dois valores entre si é determinístico.
 *
 * ## O que este caso NÃO consegue provar, e por quê
 *
 * O desenho original o encarregava de provar que o cache de `ColorManager::$cachedColors` não
 * congela a cor do primeiro tenant do processo. Ele não pode: o servidor do plugin roda
 * IN-PROCESS, então as duas visitas compartilham o container — coisa que nenhum request de
 * produção faz (o PHP-FPM cria um container por request, e o Octane descarta os `scoped` e as
 * facades entre requests). Sem a `fronteiraDeRequest()`, este caso ficaria vermelho por um
 * artefato do arnês, não por um defeito. Com ela, prova o que continua observável e é o que a
 * feature promete: cada organização registra a SUA cor, e ela chega ao `--primary-500`.
 */
it('aplica a cor da organizacao no painel de negocio', function (): void {
    ['usuario' => $usuario] = duasOrganizacoes();

    $this->actingAs($usuario);

    $lerCorPrimaria = 'getComputedStyle(document.documentElement).getPropertyValue("--primary-500")';

    fronteiraDeRequest();
    $daAcme = visit('/app/acme')
        // O `<h1>` do dashboard em pt_BR — `Dashboard` não existe nesta instalação.
        ->assertSee('Painel de Controle')
        ->assertNoJavaScriptErrors()
        ->script($lerCorPrimaria);

    fronteiraDeRequest();
    $daGlobex = visit('/app/globex')
        ->assertSee('Painel de Controle')
        ->assertNoJavaScriptErrors()
        ->script($lerCorPrimaria);

    expect(trim((string) $daAcme))->not->toBeEmpty()
        ->and(trim((string) $daGlobex))->not->toBeEmpty()
        ->and($daGlobex)->not->toBe($daAcme);
});

/**
 * CT-B03 — o CT-B de segurança: a cor de um cliente não pinta o painel de administração.
 *
 * `FilamentColor` é GLOBAL, não por painel. A guarda de painel do `AppPanelProvider::bootUsing()`
 * é a única coisa entre esta feature e o `/admin` com a cor de um cliente.
 *
 * A ordem dos passos é o que torna o caso capaz de pegar o vazamento: visitar o `/app` PRIMEIRO.
 */
it('nao vaza a cor da organizacao para o painel admin', function (): void {
    ['usuario' => $usuario] = duasOrganizacoes();

    $this->actingAs($usuario);

    $lerCorPrimaria = 'getComputedStyle(document.documentElement).getPropertyValue("--primary-500")';

    fronteiraDeRequest();
    $daAcme = visit('/app/acme')
        ->assertSee('Painel de Controle')
        ->script($lerCorPrimaria);

    fronteiraDeRequest();
    $doAdmin = visit('/admin')
        ->assertSee('Painel de Controle')
        ->assertNoJavaScriptErrors()
        ->script($lerCorPrimaria);

    expect(trim((string) $doAdmin))->not->toBeEmpty()
        ->and($doAdmin)->not->toBe($daAcme);
});

/**
 * CT-B04 — a logo da organização na tela de bloqueio.
 *
 * `Storage::disk('public')` de verdade, e não `Storage::fake()`: o navegador faz request HTTP à
 * URL da logo, e disk fake não é servido por ninguém. O disk é o REAL, daí o nome do arquivo não
 * poder colidir com upload de ninguém — e a limpeza ficar no `afterEach`.
 *
 * A visita ao painel antes de travar não é cerimônia: é ela que faz o `DefinirTenantDePermissoes`
 * gravar `session('tenant_corrente')`, a única fonte de tenant que a lock-screen tem (ADR-03).
 */
it('exibe a logo da organizacao na tela de bloqueio', function (): void {
    ['acme' => $acme, 'usuario' => $usuario] = duasOrganizacoes();

    // 1x1 transparente: o menor PNG válido. O que importa é a URL no `src`, não a imagem.
    $caminho = 'organizacoes/logos/acme-teste-browser.png';
    Storage::disk('public')->put($caminho, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
    ));

    $acme->update(['logo' => $caminho]);

    $this->actingAs($usuario);

    visit('/app/acme')->assertSee('Painel de Controle');

    // A trava pela rota do pacote — o mesmo POST que o item "Bloquear sessão" do menu dispara.
    // Sai do processo do teste, e não do navegador, porque o servidor do plugin é in-process:
    // é a MESMA sessão.
    $this->post(route('lockscreen.app.lock-session'))->assertRedirect();

    visit('/app/screen/lock')
        ->assertPathIs('/app/screen/lock')
        // `.fi-logo-light`, e não `.fi-auth-media`: desde `feat/logo-dark-mode` a logo da
        // tela de bloqueio sai pelo override de `partials/media.blade.php` com as classes
        // nativas do swap do Filament (`fi-logo fi-logo-light`), contida — `fi-auth-media`
        // ficou só para a ARTE do login, que continua `cover`. Ver RQ-05 daquela wiki.
        ->assertAttributeContains('.fi-logo-light', 'src', $caminho)
        // A tela não veio vazia: o formulário de desbloqueio está lá.
        ->assertSee('Desbloquear')
        // E o alternador de tema sobreviveu. É a asserção que pega o erro de trocar a mídia com
        // `setPageConfig()` — que SUBSTITUI o config inteiro e apagaria `themeToggle()` e
        // `mediaPosition()` sem nenhum sinal. Ver ADR-04.
        ->assertPresent('.fi-auth-theme-switcher-wrapper')
        ->assertNoJavaScriptErrors();
});

/**
 * CT-B06 — nenhum elemento da tela fica na cor default quando a organização definiu a dela.
 *
 * Este caso nasceu de um achado do `feature-quality-gate` que quase virou "artefato do arnês":
 * dentro de um painel pintado de VERDE, sete elementos continuavam âmbar. A causa não era o
 * cache de cor nem o servidor in-process — era CSS.
 *
 * O `croustibat/filament-jobs-monitor` registra o CSS dele como asset GLOBAL
 * (`FilamentJobsMonitorServiceProvider.php:36`), e lá dentro `.text-primary-600` vem com a paleta
 * âmbar LITERAL do build daquele pacote. Quem usa a utilitária fica âmbar mesmo com
 * `--primary-600` dizendo outra coisa — no caso, o alternador de painel do
 * `bezhansalleh/filament-panel-switch`, que aparece em toda tela.
 *
 * A correção é `resources/css/filament/kit.css`, registrado por
 * `KitServiceProvider::configureCorrecoesDeCss()`. Este teste é o que impede a regressão: ele não
 * olha uma classe específica, olha a TELA INTEIRA procurando a cor default. Um plugin novo que
 * repita o padrão cai aqui.
 *
 * `rgb(217, 119, 6)` é o âmbar-600 do Tailwind, que é o `primary` default do Filament.
 */
it('nao deixa nenhum elemento na cor default quando a organizacao tem a sua', function (): void {
    ['usuario' => $usuario] = duasOrganizacoes();

    $this->actingAs($usuario);

    fronteiraDeRequest();

    $ambar = visit('/app/globex')
        ->assertSee('Painel de Controle')
        ->script(<<<'JS'
            [...document.querySelectorAll('*')]
                .filter((el) => getComputedStyle(el).color === 'rgb(217, 119, 6)')
                .length
        JS);

    expect($ambar)->toBe(0, 'Há elementos na cor primária DEFAULT do Filament numa tela que '
        .'deveria estar inteira na cor da organização. Provável causa: um plugin registrou CSS '
        .'global com a paleta literal, sequestrando as utilitárias `*-primary-*` — ver '
        .'resources/css/filament/kit.css.');
});

/**
 * Cria `public/storage` (o `storage:link`) se ainda não existir. Idempotente; o link é gitignored.
 * Local a este arquivo: só o CT-B01 precisa da imagem carregada, não só da URL.
 */
function garantirLinkPublicoDoStorage(): void
{
    $link = public_path('storage');

    if (is_link($link) || is_dir($link)) {
        return;
    }

    Artisan::call('storage:link');
}

/**
 * [CT-B01] (wiki `logo-dark-do-tenant`) — a imagem visível da marca troca com o tema, SEM recarregar.
 *
 * O HTML prova que as duas `<img>` estão lá; só `getComputedStyle` no navegador prova qual está
 * visível e que ela TROCA quando o tema muda depois do load. O tema inicial vem de
 * `inLightMode()`/`inDarkMode()` (a emulação só vale no load); a troca é o clique real no
 * alternador do Filament, no mesmo documento.
 *
 * Alternador, medido no DOM: o menu do usuário (`button[aria-label="Menu do usuário"]`, rótulo do pt_BR do Filament) abre o dropdown, e nele
 * `button.fi-theme-switcher-btn[aria-label="Mudar para tema escuro|claro"]`
 * (`x-on:click="(theme = 'dark') && close()"`). O rótulo é o do pt_BR do Filament.
 *
 * O oráculo é sobre TODA imagem visível da marca (barra lateral e barra superior) terminar no
 * arquivo da ORGANIZAÇÃO — a da instalação, distinta, é o que o mutante mostraria.
 */
dataset('alternancia de tema da marca', [
    '[CT-B01] marca simples, claro para escuro' => [false, 'claro', 'acme-teste-browser.png', 'escuro', 'acme-dark-teste-browser.png', 'fi-logo-light'],
    '[CT-B01] marca simples, escuro para claro' => [false, 'escuro', 'acme-dark-teste-browser.png', 'claro', 'acme-teste-browser.png', 'fi-logo-dark'],
    '[CT-B01] composicao, claro para escuro'    => [true, 'claro', 'acme-teste-browser.png', 'escuro', 'acme-dark-teste-browser.png', 'fi-logo-light'],
    '[CT-B01] composicao, escuro para claro'    => [true, 'escuro', 'acme-dark-teste-browser.png', 'claro', 'acme-teste-browser.png', 'fi-logo-dark'],
]);

it('troca a imagem visivel da marca quando o tema e alternado sem recarregar', function (
    bool $composicao,
    string $inicial,
    string $arquivoInicial,
    string $final,
    string $arquivoFinal,
    string $oculta,
): void {
    // 1x1 transparente válido: a imagem precisa CARREGAR para `naturalWidth` valer.
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    foreach (['organizacoes/logos/acme-teste-browser.png', 'organizacoes/logos/acme-dark-teste-browser.png', 'kit/logo-teste-browser.png', 'kit/logo-dark-teste-browser.png'] as $arquivo) {
        Storage::disk('public')->put($arquivo, $png);
    }

    // Marca separada, par da instalação distinto do par da Acme.
    gravarConfiguracao('logo', 'kit/logo-teste-browser.png');
    gravarConfiguracao('logo_dark', 'kit/logo-dark-teste-browser.png');
    gravarConfiguracao('unifica_logo_marca', false);
    gravarConfiguracao('cabecalho_nome_do_projeto', false);
    gravarConfiguracao('cabecalho_nome_do_painel', false);
    gravarConfiguracao('cabecalho_logo_da_marca', $composicao);
    alinharConfiguracoesDoKit();

    $acme = Tenant::factory()
        ->comIdentidadeVisual('#7c3aed', 'organizacoes/logos/acme-teste-browser.png', null, 'organizacoes/logos/acme-dark-teste-browser.png')
        ->create(['nome' => 'Acme', 'slug' => 'acme']);

    $usuario = usuarioComPapel('panel_user', $acme);
    $usuario->tenants()->attach($acme->id);

    $this->actingAs($usuario);

    // O navegador só CARREGA `/storage/...` se `public/storage` existir. O job de telas do CI copia o
    // `.env.example` e nunca roda `storage:link`; o CT-B04 acima só confere a URL no `src`, então a
    // suíte ficava verde sem o link — e este caso, que exige `naturalWidth > 0`, caía só no CI.
    garantirLinkPublicoDoStorage();

    // Aquece pelo kernel: a compilação dos componentes fica fora do cronômetro do Playwright.
    $this->get('/app/acme')->assertSuccessful();

    $seletor = $composicao ? '.kit-cabecalho img.fi-logo' : 'img.fi-logo';

    // Toda imagem visível da marca termina em `$arquivo`, carregada, e há ao menos uma.
    $todaVisivelTermina = fn (string $arquivo): string => <<<JS
        (() => {
            const visiveis = [...document.querySelectorAll('{$seletor}')]
                .filter((img) => img.offsetParent !== null && getComputedStyle(img).display !== 'none');

            return visiveis.length > 0
                && visiveis.every((img) => img.src.endsWith('/{$arquivo}') && img.naturalWidth > 0);
        })()
    JS;

    $nenhumaOculta = <<<JS
        [...document.querySelectorAll('{$seletor}.{$oculta}')]
            .every((img) => img.offsetParent === null || getComputedStyle(img).display === 'none')
    JS;

    $pagina = $inicial === 'claro' ? visit('/app/acme')->inLightMode() : visit('/app/acme')->inDarkMode();

    $pagina
        ->assertPathIs('/app/acme')
        // Controle positivo: no tema inicial a visível já é a da organização.
        ->assertScript($todaVisivelTermina($arquivoInicial))
        // Alterna pelo alternador real, no MESMO documento (sem `visit()` de novo).
        ->click('button[aria-label="Menu do usuário"]')
        ->click($final === 'escuro'
            ? 'button.fi-theme-switcher-btn[aria-label="Mudar para tema escuro"]'
            : 'button.fi-theme-switcher-btn[aria-label="Mudar para tema claro"]')
        ->assertScript("document.documentElement.classList.contains('dark') === ".($final === 'escuro' ? 'true' : 'false'))
        ->assertScript($todaVisivelTermina($arquivoFinal))
        ->assertScript($nenhumaOculta)
        ->assertNoJavaScriptErrors();
})->with('alternancia de tema da marca');
