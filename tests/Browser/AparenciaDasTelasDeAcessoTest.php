<?php

use App\Models\User;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Aparência das telas de acesso vista pelo navegador (wiki
 * `feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel`, CT-B01..CT-B08).
 *
 * Derivado do `00-requisito.md` e do `05-casos-de-teste-browser.md`, não do código: o que se mede
 * aqui é geometria (`getBoundingClientRect`), estilo computado e o documento interno do iframe —
 * presença no HTML não prova posição nem visibilidade (`.ai/rules/testes-browser.md`).
 *
 * Telas públicas: visitante, sem `actingAs()`. Editor: administrador autenticado. Os cenários que
 * exigem organização estão em `tests/BrowserTenancy/AparenciaDasTelasDeAcessoTest.php`.
 *
 * Sem `assertNoJavaScriptErrors()` no editor: o ColorPicker dentro de Tabs emite ResizeObserver no
 * Chrome headless do CI (`.ai/rules/testes-browser.md`); os oráculos funcionais ficam.
 */
/*
 * Exatamente 80 e 160 pontos de código (os tetos de P-11), com palavras e quebra natural: acima
 * disso a normalização descarta o texto e ele não renderiza. A palavra final fecha o tamanho, e
 * `tests` abaixo confere a contagem.
 */
const FRASE_NO_TETO = 'Conheca a plataforma que reune equipes projetos e resultados num so lugar seguro';
const APOIO_NO_TETO = 'Acompanhe cada etapa do trabalho em tempo real compartilhe documentos com quem precisa e mantenha o historico de decisoes sempre a mao do time sem esforco algum';

it('[CT-B01] pré-condição: os textos no teto têm exatamente 80 e 160 pontos de código', function (): void {
    expect(mb_strlen(FRASE_NO_TETO))->toBe(80)->and(mb_strlen(APOIO_NO_TETO))->toBe(160);
});

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    config(['kit.registro.habilitado' => true]);

    // Paga a compilação dos componentes do painel fora do cronômetro do Playwright.
    $this->get('/admin/login');
    $this->get('/app/register');
});

afterEach(function (): void {
    Storage::disk('public')->delete(['kit/ct-b04-logo.png', 'kit/ct-b04-arte.png', 'kit/ct-b09-logo.png']);
});

/**
 * Textos nos tetos: 80 e 160 pontos de código, com palavras e quebra natural.
 *
 * @return array<string, mixed>
 */
function aparenciaNosTetos(string $lado, string $cor = '#123456'): array
{
    return [
        'layout' => 'painel', 'lado_do_formulario' => $lado, 'fundo' => 'cor', 'cor' => $cor,
        'frase'  => FRASE_NO_TETO, 'apoio' => APOIO_NO_TETO, 'logo_no_painel' => true, 'alternador_de_tema' => true,
    ];
}

/** Medidas de geometria da tela de acesso, como o Chromium as calculou. */
function medirTelaDeAcesso($pagina): array
{
    return json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const caixa = (el) => {
                if (!el) { return null; }
                const b = el.getBoundingClientRect();
                return { left: b.left, right: b.right, top: b.top, bottom: b.bottom, width: b.width, height: b.height };
            };
            const painel = document.querySelector('.kit-painel-de-marca');
            const frase = document.querySelector('.kit-painel-de-marca__frase');
            const apoio = document.querySelector('.kit-painel-de-marca__apoio');
            const moldura = painel ? painel.closest('.fi-auth-media-wrapper') : null;
            const rodape = document.querySelector('.kit-versao');
            return JSON.stringify({
                vw: innerWidth, vh: innerHeight,
                rolagemHorizontal: document.documentElement.scrollWidth > innerWidth,
                painel: caixa(painel), moldura: caixa(moldura),
                raio: moldura ? getComputedStyle(moldura).borderTopLeftRadius : null,
                painelDisplay: painel ? getComputedStyle(painel).display : null,
                form: caixa(document.querySelector('.fi-auth-form-container')),
                frase: caixa(frase), apoio: caixa(apoio),
                fraseCortada: frase ? frase.scrollHeight > frase.clientHeight + 1 : null,
                apoioCortado: apoio ? apoio.scrollHeight > apoio.clientHeight + 1 : null,
                rodape: caixa(rodape),
            });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);
}

function contidaEm(?array $interna, ?array $externa): bool
{
    return $interna !== null && $externa !== null
        && $interna['left'] >= $externa['left'] - 0.5 && $interna['right'] <= $externa['right'] + 0.5
        && $interna['top'] >= $externa['top'] - 0.5 && $interna['bottom'] <= $externa['bottom'] + 0.5;
}

function seInterceptam(array $a, array $b): bool
{
    return $a['left'] < $b['right'] - 0.5 && $b['left'] < $a['right'] - 0.5
        && $a['top'] < $b['bottom'] - 0.5 && $b['top'] < $a['bottom'] - 0.5;
}

it('[CT-B01] a composição respeita o lado e comporta os textos no teto', function (string $lado): void {
    definirAparenciaGlobal(aparenciaNosTetos($lado));

    $pagina = visit('/admin/login')->resize(1440, 900);
    $pagina->assertPathIs('/admin/login')->assertVisible('.kit-painel-de-marca');

    $m = medirTelaDeAcesso($pagina);

    expect($m['painel'])->not->toBeNull('o painel de marca não existe')
        ->and($m['form'])->not->toBeNull('o formulário não existe')
        ->and($m['painel']['width'])->toBeGreaterThan(100)
        ->and($m['form']['width'])->toBeGreaterThan(100);

    // Ordem horizontal conforme o lado escolhido, e interseção vazia.
    if ($lado === 'esquerda') {
        expect($m['form']['right'])->toBeLessThanOrEqual($m['painel']['left'] + 0.5, 'formulário deveria ficar à esquerda da marca');
    } else {
        expect($m['painel']['right'])->toBeLessThanOrEqual($m['form']['left'] + 0.5, 'formulário deveria ficar à direita da marca');
    }
    expect(seInterceptam($m['painel'], $m['form']))->toBeFalse('marca e formulário se sobrepõem');

    // Cantos arredondados na marca.
    expect($m['raio'])->not->toBe('0px', 'a marca não tem cantos arredondados');

    // Os dois textos: visíveis, contidos na marca, fora do formulário, sem corte nem rolagem.
    expect($m['frase'])->not->toBeNull('a frase não foi renderizada')
        ->and($m['apoio'])->not->toBeNull('o apoio não foi renderizado')
        ->and($m['frase']['height'])->toBeGreaterThan(0)
        ->and($m['apoio']['height'])->toBeGreaterThan(0)
        ->and(contidaEm($m['frase'], $m['painel']))->toBeTrue('a frase ultrapassa a marca')
        ->and(contidaEm($m['apoio'], $m['painel']))->toBeTrue('o apoio ultrapassa a marca')
        ->and(seInterceptam($m['frase'], $m['form']))->toBeFalse('a frase invade o formulário')
        ->and(seInterceptam($m['apoio'], $m['form']))->toBeFalse('o apoio invade o formulário')
        ->and($m['fraseCortada'])->toBeFalse('a frase está cortada')
        ->and($m['apoioCortado'])->toBeFalse('o apoio está cortado')
        ->and($m['rolagemHorizontal'])->toBeFalse('há rolagem horizontal');

    $pagina->assertSee(FRASE_NO_TETO)->assertSee(APOIO_NO_TETO);
})->with([
    'formulário à esquerda' => ['esquerda'],
    'formulário à direita'  => ['direita'],
]);

it('[CT-B02] a composição estreita preserva a marca e o rodapé', function (string $conteudo, string $lado, string $tela): void {
    definirAparenciaGlobal($conteudo === 'ausentes'
        ? [...aparenciaDeTeste('P0'), 'lado_do_formulario' => $lado]
        : aparenciaNosTetos($lado));

    $caminho = match ($tela) {
        'login'     => '/admin/login',
        'recuperar' => '/admin/password-reset/request',
        'cadastro'  => '/app/register',
    };

    $pagina = visit($caminho)->resize(390, 844);
    $pagina->assertPathIs($caminho);

    $m = medirTelaDeAcesso($pagina);

    expect($m['painel'])->not->toBeNull('a marca não existe em '.$caminho)
        ->and($m['painelDisplay'])->not->toBe('none', 'a marca foi escondida no celular')
        ->and($m['painel']['width'] * $m['painel']['height'])->toBeGreaterThan(0, 'a marca não tem área visível')
        ->and($m['form'])->not->toBeNull();

    expect($m['painel']['bottom'])->toBeLessThanOrEqual($m['form']['top'] + 0.5, 'a marca deveria ser uma faixa acima do formulário')
        ->and($m['rolagemHorizontal'])->toBeFalse('há rolagem horizontal');

    expect($m['rodape'])->not->toBeNull('o rodapé real não existe em '.$caminho)
        ->and($m['rodape']['top'])->toBeGreaterThanOrEqual(0)
        ->and($m['rodape']['bottom'])->toBeLessThanOrEqual($m['vh'], "o rodapé termina em y={$m['rodape']['bottom']} numa viewport de {$m['vh']}px");
})->with([
    'frases ausentes, direita, login'     => ['ausentes', 'direita', 'login'],
    'teto, direita, login'                => ['teto', 'direita', 'login'],
    'teto, esquerda, login'               => ['teto', 'esquerda', 'login'],
    'teto, direita, recuperar senha'      => ['teto', 'direita', 'recuperar'],
    'teto, esquerda, cadastro'            => ['teto', 'esquerda', 'cadastro'],
]);

it('[CT-B03] a pessoa percorre o formulário por teclado sem armadilha de foco', function (string $tema, string $viewport): void {
    definirAparenciaGlobal(aparenciaDeTeste('P'));

    $pagina = $tema === 'escuro' ? visit('/admin/login')->inDarkMode() : visit('/admin/login')->inLightMode();
    $viewport === 'celular' ? $pagina->resize(390, 844) : $pagina->resize(1440, 900);

    $pagina->assertPathIs('/admin/login')
        ->assertVisible('#form\\.email')
        ->assertScript("document.documentElement.classList.contains('dark') === ".($tema === 'escuro' ? 'true' : 'false'));

    $descritor = <<<'JS'
        (() => {
            const el = document.activeElement;
            const alvo = el.closest('.fi-input-wrp') ?? el;
            const cs = getComputedStyle(el), cw = getComputedStyle(alvo);
            const nome = (el.getAttribute('aria-label') ?? '') + (el.labels && el.labels.length ? el.labels[0].textContent : '') + (el.tagName === 'BUTTON' || el.tagName === 'A' ? el.textContent : '');
            return JSON.stringify({
                tag: el.tagName, id: el.id, tipo: el.type ?? null, aria: el.getAttribute('aria-label'),
                nome: nome.trim(),
                foco: (cs.outlineStyle !== 'none' && cs.outlineWidth !== '0px') || cs.boxShadow !== 'none' || cw.boxShadow !== 'none' || (cw.outlineStyle !== 'none' && cw.outlineWidth !== '0px'),
            });
        })()
    JS;

    // Shift+Tab a partir da senha deve chegar ao e-mail; dali, Tab percorre o resto e volta ao início.
    $pagina->keys('#form\.password', 'Shift+Tab');
    $percurso = [json_decode((string) $pagina->script($descritor), true, flags: JSON_THROW_ON_ERROR)];
    for ($i = 0; $i < 14; $i++) {
        $pagina->script("document.querySelectorAll('[data-kit-foco]').forEach(e => e.removeAttribute('data-kit-foco')); document.activeElement?.setAttribute('data-kit-foco', '1')");
        $pagina->keys('[data-kit-foco="1"]', 'Tab');
        $percurso[] = json_decode((string) $pagina->script($descritor), true, flags: JSON_THROW_ON_ERROR);
    }

    $por = fn (callable $criterio) => collect($percurso)->first($criterio);

    $email    = $por(fn ($p) => $p['id'] === 'form.email');
    $senha    = $por(fn ($p) => $p['id'] === 'form.password');
    $envio    = $por(fn ($p) => $p['tag'] === 'BUTTON' && $p['tipo'] === 'submit');
    // Sem armadilha: depois do envio o foco sai do formulário e o ciclo volta ao e-mail.
    $voltas = collect($percurso)->where('id', 'form.email')->count();

    expect($email)->not->toBeNull('e-mail não recebeu foco por Tab')
        ->and($senha)->not->toBeNull('senha não recebeu foco por Tab')
        ->and($envio)->not->toBeNull('o envio não recebeu foco por Tab')
        ->and($voltas)->toBeGreaterThan(1, 'o foco não voltou ao início: armadilha de foco');

    foreach (['e-mail' => $email, 'senha' => $senha, 'envio' => $envio] as $rotulo => $alvo) {
        expect($alvo['foco'])->toBeTrue("{$rotulo} recebeu foco sem indicação visível")
            ->and($alvo['nome'])->not->toBe('', "{$rotulo} não tem nome acessível");
    }

    /*
     * O alternador de tema NÃO entra no percurso: os botões do `theme-switcher` do Filament nascem
     * com `tabindex="-1"` (vendor/filament/filament/resources/views/components/theme-switcher/button.blade.php),
     * no clássico e no painel igualmente. Limitação anterior à feature, registrada como débito no `03`.
     */
})->with([
    'claro, computador'  => ['claro', 'computador'],
    'claro, celular'     => ['claro', 'celular'],
    'escuro, computador' => ['escuro', 'computador'],
    'escuro, celular'    => ['escuro', 'celular'],
]);

/** Logo e arte da identidade, em arquivos reais do disco público (o navegador os baixa por HTTP). */
function identidadeComArquivosReais(): void
{
    Storage::disk('public')->put('kit/ct-b04-logo.png', UploadedFile::fake()->image('logo.png', 413, 80)->get());
    Storage::disk('public')->put('kit/ct-b04-arte.png', UploadedFile::fake()->image('arte.png', 777, 333)->get());

    gravarConfiguracao('logo', 'kit/ct-b04-logo.png');
    gravarConfiguracao('arte_do_login', 'kit/ct-b04-arte.png');
    gravarConfiguracao('cor_primaria_hex', '#654321');
    alinharConfiguracoesDoKit();
}

it('[CT-B04] o navegador obedece à opção visual', function (string $opcao, string $tema): void {
    identidadeComArquivosReais();

    $base = aparenciaDeTeste('P');

    definirAparenciaGlobal(match ($opcao) {
        'hex'        => [...$base, 'lado_do_formulario' => 'direita', 'cor' => '#123456', 'fundo' => 'cor'],
        'primaria'   => [...$base, 'cor' => null],
        'arte'       => [...$base, 'fundo' => 'arte'],
        'logo-on'    => [...$base, 'logo_no_painel' => true],
        'logo-off'   => [...$base, 'logo_no_painel' => false],
        'tema-on'    => [...$base, 'alternador_de_tema' => true],
        'tema-off'   => [...$base, 'alternador_de_tema' => false],
        'defaults'   => ['layout' => 'painel'],
    });

    $pagina = $tema === 'escuro' ? visit('/admin/login')->inDarkMode() : visit('/admin/login')->inLightMode();
    $pagina->resize(1440, 900)
        ->assertPathIs('/admin/login')
        ->assertVisible('.kit-painel-de-marca')
        ->assertScript("document.documentElement.classList.contains('dark') === ".($tema === 'escuro' ? 'true' : 'false'));

    $fundo = fn () => (string) $pagina->script("getComputedStyle(document.querySelector('.kit-painel-de-marca')).backgroundColor");

    switch ($opcao) {
        case 'hex':
            expect($fundo())->toBe('rgb(18, 52, 86)');
            break;
        case 'primaria':
            expect($fundo())->toBe('rgb(101, 67, 33)');
            break;
        case 'arte':
            // A arte pode ainda estar carregando quando o elemento aparece: espera o decode.
            $pagina->assertVisible('.kit-painel-de-marca__arte')
                ->script("document.querySelector('.kit-painel-de-marca__arte').decode().catch(() => null)");
            $pagina->assertScript("(() => { const i = document.querySelector('.kit-painel-de-marca__arte'); return i.complete && i.naturalWidth === 777 && i.naturalHeight === 333 && i.getBoundingClientRect().width > 0; })()")
                ->assertScript("document.querySelector('.kit-painel-de-marca__arte').src.includes('ct-b04-arte')");
            break;
        case 'logo-on':
            $pagina->assertVisible('.kit-painel-de-marca__logo')
                ->assertScript("(() => { const i = document.querySelector('.kit-painel-de-marca__logo'); return i.complete && i.naturalWidth === 413 && i.getBoundingClientRect().width > 0 && i.src.includes('ct-b04-logo'); })()");
            break;
        case 'logo-off':
            $pagina->assertMissing('.kit-painel-de-marca__logo')
                ->assertScript("document.querySelector('.kit-painel-de-marca__selo') === null");
            break;
        case 'tema-on':
            $pagina->assertVisible('.fi-auth-theme-switcher-wrapper')
                ->keys('[aria-label="Mudar para tema escuro"]', 'Enter')
                ->assertScript("document.documentElement.classList.contains('dark')");
            break;
        case 'tema-off':
            $pagina->assertMissing('.fi-auth-theme-switcher-wrapper');
            break;
        case 'defaults':
            $m = medirTelaDeAcesso($pagina);
            expect($fundo())->toBe('rgb(101, 67, 33)')
                ->and($m['painel']['right'])->toBeLessThanOrEqual($m['form']['left'] + 0.5, 'o padrão do formulário é à direita');
            $pagina->assertScript("document.querySelector('.kit-painel-de-marca__frase') === null && document.querySelector('.kit-painel-de-marca__apoio') === null && document.querySelector('.kit-painel-de-marca__textos') === null")
                ->assertVisible('.kit-painel-de-marca__logo')
                ->assertVisible('.fi-auth-theme-switcher-wrapper');
            break;
    }
})->with([
    'hex, claro'        => ['hex', 'claro'],
    'hex, escuro'       => ['hex', 'escuro'],
    'primária, claro'   => ['primaria', 'claro'],
    'arte, claro'       => ['arte', 'claro'],
    'logo ligada'       => ['logo-on', 'claro'],
    'logo desligada'    => ['logo-off', 'claro'],
    'alternador ligado' => ['tema-on', 'claro'],
    'alternador off'    => ['tema-off', 'escuro'],
    'defaults'          => ['defaults', 'claro'],
]);

/** Abre o editor da instalação na aba "Telas de acesso", como administrador. */
function abrirEditorDaInstalacao()
{
    test()->actingAs(usuarioDoKit('admin'));
    test()->get('/admin/configuracoes-da-aplicacao');

    return visit('/admin/configuracoes-da-aplicacao')
        ->assertVisible('#form\\.configuracoes\\.identidade\\.nome_da_aplicacao')
        ->click('Telas de acesso')
        ->assertVisible('[id$="aparencia_das_telas.frase"]');
}

it('[CT-B05] atualizar o preview mostra o que ainda não foi salvo', function (string $tela, string $dimensao): void {
    definirAparenciaGlobal(aparenciaDeTeste('P'));

    $pagina = abrirEditorDaInstalacao();

    $sentinela = 'RASCUNHO'.strtoupper($tela === 'recuperar-senha' ? 'REC' : substr($tela, 0, 3)).'X'.($dimensao === 'celular' ? 'C' : 'D');

    // Tab tira o foco do campo (o live onBlur grava o rascunho), como a pessoa faria.
    escreverNoEditorDeTexto($pagina, 'frase', $sentinela);
    $pagina->wait(0.5)
        ->select('.kit-preview-das-telas__select', $tela)
        ->click($dimensao === 'celular' ? 'Celular' : 'Computador')
        ->click('Atualizar preview');

    $iframe = "document.querySelector('.kit-preview-das-telas__iframe')";

    // Espera o iframe recarregar na tela e na largura escolhidas, com o rascunho: antes disso o
    // documento ainda é o anterior, e uma asserção negativa passaria ou falharia por corrida (CI).
    [$larguraInterna] = $dimensao === 'celular' ? [390] : [1440];
    esperarNoNavegador($pagina, "(() => { const f = {$iframe}; const d = f?.contentDocument;
        return !!d && d.readyState === 'complete' && f.contentWindow.location.href.includes('tela={$tela}')
            && f.contentWindow.innerWidth === {$larguraInterna} && d.body.innerHTML.includes('{$sentinela}'); })()",
        aCada: fn () => $pagina->click('Atualizar preview'));

    // O documento interno recebe o rascunho (A), não o persistido (P). No celular a faixa não
    // mostra texto (P-22, Adendo 1): o rascunho chega ao documento, mas a frase não fica visível.
    if ($dimensao === 'celular') {
        $pagina->assertScript("{$iframe}.contentDocument?.body?.innerHTML.includes('{$sentinela}') === true")
            ->assertScript("!{$iframe}.contentDocument.body.innerText.includes('{$sentinela}')");
    } else {
        $pagina->assertScript("{$iframe}.contentDocument?.body?.innerText.includes('{$sentinela}') === true");
    }

    $pagina->assertScript("!{$iframe}.contentDocument.body.innerHTML.includes('PADRAO')");

    // A tela escolhida: login tem senha e não tem nome; recuperação não tem senha; cadastro tem nome e senha.
    $esperado = match ($tela) {
        'login'           => ['senha' => 1, 'nome' => 0],
        'recuperar-senha' => ['senha' => 0, 'nome' => 0],
        'cadastro'        => ['senha' => 1, 'nome' => 1],
    };
    $pagina->assertScript("{$iframe}.contentDocument.querySelectorAll('input[type=password]').length === {$esperado['senha']}")
        ->assertScript("{$iframe}.contentDocument.querySelectorAll('input[id=\"form.name\"]').length === {$esperado['nome']}");

    // A dimensão interna escolhida, medida no documento interno.
    [$w, $h] = $dimensao === 'celular' ? [390, 844] : [1440, 900];
    $pagina->assertScript("{$iframe}.contentWindow.innerWidth === {$w}");
    expect((int) $pagina->script("{$iframe}.contentWindow.innerHeight"))->toBeGreaterThan(0);

    // Os controles demonstrativos não navegam o iframe nem o topo.
    $pagina->assertPathIs('/admin/configuracoes-da-aplicacao');

    // O persistido continua P.
    expect(configuracaoGravada('aparencia_das_telas'))->toBe(aparenciaDeTeste('P'));
})->with(function () {
    foreach (['login', 'recuperar-senha', 'cadastro'] as $tela) {
        foreach (['computador', 'celular'] as $dimensao) {
            yield "{$tela} em {$dimensao}" => [$tela, $dimensao];
        }
    }
});

it('[CT-B06] o clássico não acrescenta painel, faixa nem frases', function (string $tela): void {
    definirAparenciaGlobal([...aparenciaDeTeste('P'), 'layout' => 'classico']);

    $caminho = '/admin/login';

    switch ($tela) {
        case 'login':
            break;
        case 'cadastro':
            $caminho = '/app/register';
            break;
        case 'recuperar':
            $caminho = '/admin/password-reset/request';
            break;
        case 'redefinir':
            $caminho = '/admin/password-reset/reset?token=abc&email=ana%40example.com';
            break;
        case 'bloqueio':
            $this->actingAs(usuarioDoKit('master_global'));
            $this->post(route('lockscreen.app.lock-session'))->assertRedirect();
            $caminho = '/app/screen/lock';
            break;
        case 'confirmacao':
            $this->actingAs(User::factory()->unverified()->create());
            $caminho = '/app/email-verification/prompt';
            break;
        case 'dois-fatores':
            $this->actingAs(usuarioDoKit('admin'));
            $caminho = '/admin/two-factor-authentication';
            break;
    }

    $pagina = visit($caminho)->resize(1440, 900);
    $pagina->assertPathIs(parse_url($caminho, PHP_URL_PATH));

    $pagina->assertScript("document.querySelector('.kit-painel-de-marca') === null && document.querySelector('[data-kit-aparencia]') === null")
        ->assertScript("!document.body.innerText.includes('PADRAO') && !document.body.innerText.includes('APOIO P')")
        ->assertScript('document.documentElement.scrollWidth <= innerWidth');
})->with([
    'login'                 => ['login'],
    'cadastro'              => ['cadastro'],
    'recuperar senha'       => ['recuperar'],
    'redefinir senha'       => ['redefinir'],
    'bloqueio de sessão'    => ['bloqueio'],
    'confirmação de e-mail' => ['confirmacao'],
    'dois fatores'          => ['dois-fatores'],
]);

it('[CT-B07] emojis válidos não são cortados pelo controle de entrada', function (string $campo, int $n): void {
    definirAparenciaGlobal(aparenciaDeTeste('P'));

    $emojis  = str_repeat('😀', $n);
    $seletor = "[id\$=\"aparencia_das_telas.{$campo}\"]";

    $pagina = abrirEditorDaInstalacao();

    escreverNoEditorDeTexto($pagina, $campo, $emojis);
    $pagina->assertScript("[...document.querySelector('{$seletor} .CodeMirror').CodeMirror.getValue()].length === {$n}");

    $pagina->press('Salvar');

    // Espera pelo estado final (o registro gravado), com teto: sem espera fixa de segundos.
    $limite = microtime(true) + 30;
    do {
        $gravado = configuracaoGravada('aparencia_das_telas');
        $salvou  = ($gravado[$campo] ?? null) === $emojis;
        // A espera passa pelo navegador: o servidor do plugin roda neste processo e só atende
        // requests enquanto o laço de eventos roda (um usleep o deixaria sem resposta).
        $salvou || $pagina->script('new Promise((r) => setTimeout(r, 250))');
    } while (! $salvou && microtime(true) < $limite);

    expect($gravado[$campo] ?? null)->toBe($emojis, 'o texto integral deveria estar persistido');
    $pagina->assertScript("document.querySelector('[data-field-wrapper]:has({$seletor}) .fi-fo-field-wrp-error-message') === null");
})->with([
    'destaque 80' => ['frase', 80],
    'apoio 160'   => ['apoio', 160],
]);

it('[CT-B08] payload de aspas permanece texto inerte no login e no preview', function (string $saida, string $campo, string $texto): void {
    $aparencia = aparenciaDeTeste('P');

    $inerte = <<<'JS'
        (doc) => {
            const marca = doc.querySelector('.kit-painel-de-marca');
            if (!marca) { return 'sem painel'; }
            const alvo = marca.querySelector('.kit-painel-de-marca__CAMPO');
            if (!alvo) { return 'sem campo'; }
            const ativos = [...marca.querySelectorAll('*')].filter((e) => e.hasAttribute('autofocus') || [...e.attributes].some((a) => a.name.startsWith('on')));
            const focado = doc.activeElement;
            return JSON.stringify({ texto: alvo.textContent, ativos: ativos.length, autofocus: marca.querySelectorAll('[autofocus]').length + doc.querySelectorAll('[onfocus]').length, focoNoCampo: focado === alvo || marca.contains(focado) && focado !== marca });
        }
    JS;
    $inerte = str_replace('CAMPO', $campo === 'frase' ? 'frase' : 'apoio', $inerte);

    if ($saida === 'login') {
        definirAparenciaGlobal([...$aparencia, $campo => $texto]);

        $pagina = visit('/admin/login')->resize(1440, 900);
        $pagina->assertPathIs('/admin/login')->assertVisible('.kit-painel-de-marca');
        $alvoDoc = 'document';
    } else {
        definirAparenciaGlobal($aparencia);
        $pagina        = abrirEditorDaInstalacao();
        // O texto vai como Markdown ESCAPADO (P-27): aspas e sinais saem literais na tela.
        escreverNoEditorDeTexto($pagina, $campo, preg_replace('/([\\\\`*_{}\\[\\]()#+\\-.!|<>~])/', '\\\\$1', $texto));
        $pagina->click('Atualizar preview');
        $alvoDoc = "document.querySelector('.kit-preview-das-telas__iframe').contentDocument";
        $pagina->assertScript("{$alvoDoc}?.querySelector('.kit-painel-de-marca__{$campo}')?.textContent === ".json_encode($texto));
    }

    // Percorre o foco por teclado: um autofocus/onfocus injetado se revelaria aqui.
    if ($saida === 'login') {
        $pagina->keys('#form\\.password', 'Shift+Tab');
        for ($i = 0; $i < 6; $i++) {
            $pagina->script("document.querySelectorAll('[data-kit-foco]').forEach(e => e.removeAttribute('data-kit-foco')); document.activeElement?.setAttribute('data-kit-foco', '1')");
            $pagina->keys('[data-kit-foco="1"]', 'Tab');
        }
    }
    $medida = json_decode((string) $pagina->script('('.$inerte.')('.$alvoDoc.')'), true, flags: JSON_THROW_ON_ERROR);

    expect($medida['texto'])->toBe($texto, 'o texto deveria sair literal')
        ->and($medida['ativos'])->toBe(0, 'um atributo de evento ou autofocus nasceu do texto')
        ->and($medida['autofocus'])->toBe(0, 'um autofocus nasceu do texto');
})->with([
    'login, quebra de JavaScript'   => ['login', 'frase', "';alert(1);//"],
    'login, quebra de atributo'     => ['login', 'apoio', '" autofocus onfocus="alert(1)'],
    'preview, quebra de JavaScript' => ['preview', 'frase', "';alert(1);//"],
    'preview, quebra de atributo'   => ['preview', 'apoio', '" autofocus onfocus="alert(1)'],
]);

/*
|--------------------------------------------------------------------------
| Adendo 1 — celular só com a marca, ilustração em movimento, largura do painel
|--------------------------------------------------------------------------
*/

/** O painel cheio do Adendo 1: textos, destaque e rede em movimento. */
function aparenciaCheiaDoAdendo(array $extra = []): array
{
    return [...aparenciaDeTeste('P'), 'destaque' => 'logo', 'ilustracao' => 'rede', 'movimento' => true, ...$extra];
}

it('[CT-B09] no celular a faixa mostra só a logo ou o nome da aplicação', function (bool $comLogo): void {
    // Logo num arquivo só deste teste: o `afterEach` apaga a do CT-B04, e um nome compartilhado
    // deixava a faixa às vezes sem a logo (QA-21).
    if ($comLogo) {
        Storage::disk('public')->put('kit/ct-b09-logo.png', UploadedFile::fake()->image('logo.png', 413, 80)->get());
        gravarConfiguracao('logo', 'kit/ct-b09-logo.png');
        alinharConfiguracoesDoKit();
    }
    definirAparenciaGlobal(aparenciaCheiaDoAdendo());

    $pagina = visit('/admin/login')->resize(390, 844);

    $visivel = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const painel = document.querySelector('.kit-painel-de-marca');
            const visto = (sel) => [...painel.querySelectorAll(sel)].some((el) => el.getClientRects().length > 0);
            return JSON.stringify({
                texto: painel.innerText.trim(),
                logo: visto('.kit-painel-de-marca__topo img'),
                meio: visto('.kit-painel-de-marca__meio'),
                base: visto('.kit-painel-de-marca__base'),
                rede: visto('.kit-painel-de-marca__rede'),
            });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    expect($visivel['texto'])->toBe($comLogo ? '' : config('app.name'))
        ->and($visivel['logo'])->toBe($comLogo)
        ->and($visivel['meio'])->toBeFalse()
        ->and($visivel['base'])->toBeFalse()
        ->and($visivel['rede'])->toBeFalse();

    // No computador, o mesmo painel mostra tudo.
    $pagina->resize(1440, 900);
    $pagina->assertScript("document.querySelector('.kit-painel-de-marca__frase').getClientRects().length > 0")
        ->assertScript("document.querySelector('.kit-painel-de-marca__rede').getClientRects().length > 0")
        ->assertNoJavaScriptErrors();
})->with(['com logo' => true, 'sem logo' => false]);

it('[CT-B10] a rede se move, o botão pausa e retoma', function (): void {
    definirAparenciaGlobal(aparenciaCheiaDoAdendo());

    $pagina = visit('/admin/login')->resize(1440, 900);
    $ponto  = "document.querySelector('.kit-painel-de-marca__rede circle').getAttribute('cx')";

    $pagina->assertScript("!document.querySelector('[data-kit-pausa]').hidden");
    $antes = $pagina->script($ponto);
    $pagina->wait(0.6);
    expect($pagina->script($ponto))->not->toBe($antes, 'a rede deveria estar em movimento');

    $pagina->click('Pausar o movimento')->wait(0.2);
    $parado = $pagina->script($ponto);
    $pagina->wait(0.6);
    // O rótulo diz a próxima ação, e o botão não usa `aria-pressed` junto (QA-25: os dois ao mesmo
    // tempo fazem o leitor de tela anunciar "Retomar o movimento, pressionado").
    expect($pagina->script($ponto))->toBe($parado, 'a rede deveria ter parado')
        ->and($pagina->script("document.querySelector('[data-kit-pausa]').hasAttribute('aria-pressed')"))->toBeFalse()
        ->and(trim((string) $pagina->script("document.querySelector('[data-kit-pausa]').innerText")))->toBe('Retomar o movimento');

    $pagina->click('Retomar o movimento')->wait(0.6);
    expect($pagina->script($ponto))->not->toBe($parado, 'a rede deveria ter voltado a se mover');
    $pagina->assertNoJavaScriptErrors();
});

it('[CT-B10] movimento desligado deixa a rede parada e sem botão', function (): void {
    definirAparenciaGlobal(aparenciaCheiaDoAdendo(['movimento' => false]));

    $pagina = visit('/admin/login')->resize(1440, 900);
    $ponto  = "document.querySelector('.kit-painel-de-marca__rede circle').getAttribute('cx')";

    $antes = $pagina->script($ponto);
    $pagina->wait(0.6);

    expect($pagina->script($ponto))->toBe($antes)
        ->and($pagina->script("document.querySelectorAll('[data-kit-pausa]').length"))->toBe(0);
});

it('[CT-B11] a largura escolhida divide a tela entre painel e formulário', function (int $tamanho, string $lado): void {
    definirAparenciaGlobal(['tamanho_do_painel' => $tamanho, 'lado_do_formulario' => $lado] + aparenciaDeTeste('P'));

    $pagina = visit('/admin/login')->resize(1440, 900);
    $m      = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const secao = document.querySelector('.fi-auth-media-section').getBoundingClientRect();
            const form = document.querySelector('.fi-auth-form-container').getBoundingClientRect();
            return JSON.stringify({ largura: secao.width, esquerda: secao.left, form: form.left });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    expect($m['largura'])->toEqualWithDelta(1440 * $tamanho / 100, 2)
        ->and($lado === 'direita' ? $m['form'] > $m['esquerda'] : $m['form'] < $m['esquerda'])->toBeTrue();
})->with([
    'estreito, formulário à direita' => [30, 'direita'],
    'largo, formulário à esquerda'   => [70, 'esquerda'],
]);

it('[CT-B13] com "reduzir movimento" no sistema a rede fica parada e sem botão', function (): void {
    definirAparenciaGlobal(aparenciaCheiaDoAdendo());

    $pagina = visitaComMovimentoReduzido('/admin/login')->resize(1440, 900);
    $ponto  = "document.querySelector('.kit-painel-de-marca__rede circle').getAttribute('cx')";

    $pagina->assertScript("matchMedia('(prefers-reduced-motion: reduce)').matches");
    $antes = $pagina->script($ponto);
    $pagina->wait(0.6);

    expect($pagina->script($ponto))->toBe($antes)
        ->and($pagina->script("document.querySelector('[data-kit-pausa]').hidden"))->toBeTrue();
});

it('[CT-B14] no celular, onde a rede não aparece, o laço de animação não roda', function (): void {
    definirAparenciaGlobal(aparenciaCheiaDoAdendo());

    $pagina = visit('/admin/login')->resize(390, 844);
    $ponto  = "document.querySelector('.kit-painel-de-marca__rede circle').getAttribute('cx')";

    $antes = $pagina->script($ponto);
    $pagina->wait(0.6);
    expect($pagina->script($ponto))->toBe($antes, 'a rede escondida não deveria ser animada');

    // De volta à largura de computador, a animação recomeça.
    $pagina->resize(1440, 900)->wait(0.6);
    expect($pagina->script($ponto))->not->toBe($antes);
});

it('[CT-B12] a imagem grande do destaque não cobre os textos numa tela baixa', function (): void {
    Storage::disk('public')->put('telas-de-acesso/ct-b12.png', UploadedFile::fake()->image('alta.png', 400, 1200)->get());
    definirAparenciaGlobal([
        ...aparenciaNosTetos('direita'),
        'tamanho_do_painel'   => 30, 'destaque' => 'imagem', 'imagem_do_destaque' => 'telas-de-acesso/ct-b12.png',
        'tamanho_do_destaque' => 'grande',
    ]);

    $pagina = visit('/admin/login')->resize(1366, 768);
    $m      = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const img = document.querySelector('[data-kit-destaque="imagem"]').getBoundingClientRect();
            const base = document.querySelector('.kit-painel-de-marca__base').getBoundingClientRect();
            return JSON.stringify({ imagem: img.bottom, base: base.top, altura: img.height });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    Storage::disk('public')->delete('telas-de-acesso/ct-b12.png');

    expect($m['altura'])->toBeGreaterThan(0)
        ->and($m['imagem'])->toBeLessThanOrEqual($m['base'] + 0.5, 'a imagem invadiu a base com os textos');
});

it('[CT-B15] num painel estreito o botão de pausar continua inteiro dentro do painel', function (): void {
    definirAparenciaGlobal([...aparenciaNosTetos('direita'), 'tamanho_do_painel' => 30, 'ilustracao' => 'rede', 'movimento' => true]);

    $pagina = visit('/admin/login')->resize(1024, 768);
    $m      = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const botao = document.querySelector('[data-kit-pausa]').getBoundingClientRect();
            const painel = document.querySelector('.kit-painel-de-marca').getBoundingClientRect();
            return JSON.stringify({ botao: { l: botao.left, r: botao.right, t: botao.top, b: botao.bottom, w: botao.width }, painel: { l: painel.left, r: painel.right, t: painel.top, b: painel.bottom } });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    expect($m['botao']['w'])->toBeGreaterThan(0)
        ->and($m['botao']['l'])->toBeGreaterThanOrEqual($m['painel']['l'] - 0.5)
        ->and($m['botao']['r'])->toBeLessThanOrEqual($m['painel']['r'] + 0.5)
        ->and($m['botao']['b'])->toBeLessThanOrEqual($m['painel']['b'] + 0.5, 'o botão de pausar foi cortado pelo painel');
});

/** Repete a expressão no navegador até ela dar `true` — asserção que tolera o recarregamento de um iframe. */
function esperarNoNavegador(mixed $pagina, string $expressao, float $segundos = 30, ?Closure $aCada = null): void
{
    $limite   = microtime(true) + $segundos;
    $proximo  = microtime(true) + 6;

    while (microtime(true) < $limite) {
        if ($pagina->script($expressao) === true) {
            return;
        }

        // Num runner lento o primeiro clique pode chegar antes do rascunho: repete a ação da pessoa.
        if ($aCada !== null && microtime(true) > $proximo) {
            $aCada();
            $proximo = microtime(true) + 6;
        }

        $pagina->wait(0.25);
    }

    expect(false)->toBeTrue("o navegador não chegou a: {$expressao}");
}

/*
|--------------------------------------------------------------------------
| Adendo 2 e 3 — arte no celular, login social e anti-robô no painel
|--------------------------------------------------------------------------
*/

it('[CT-B16] com fundo de arte, a faixa do celular não mostra a imagem da arte; no computador, mostra', function (int $largura, bool $arteVisivel): void {
    definirAparenciaGlobal(['fundo' => 'arte', 'destaque' => 'nenhum'] + aparenciaDeTeste('P'));

    $pagina = visit('/admin/login')->resize($largura, 900);

    expect($pagina->script("getComputedStyle(document.querySelector('.kit-painel-de-marca__arte')).display !== 'none'"))->toBe($arteVisivel)
        ->and($pagina->script("document.querySelector('.kit-painel-de-marca').getClientRects().length > 0"))->toBeTrue();

    // Sem a arte, a faixa fica com a cor do painel sob o véu escuro (P-30): fundo não transparente e véu à vista.
    if (! $arteVisivel) {
        // A cor configurada (#123456, da aparência P) e nenhuma imagem de fundo na faixa (ADV-C24, C29).
        expect($pagina->script("getComputedStyle(document.querySelector('.kit-painel-de-marca')).backgroundColor"))->toBe('rgb(18, 52, 86)')
            ->and($pagina->script("getComputedStyle(document.querySelector('.kit-painel-de-marca')).backgroundImage"))->toBe('none')
            ->and($pagina->script("getComputedStyle(document.querySelector('.kit-painel-de-marca__veu')).display !== 'none'"))->toBeTrue();
    }
})->with([
    'celular 390'     => [390, false],
    'limite 1023'     => [1023, false],
    'computador 1024' => [1024, true],
]);

it('[CT-B17] no painel, o botão de login social e o desafio anti-robô ficam visíveis no formulário', function (int $largura): void {
    ligarProvedor(ProvedorSocial::Google);
    config()->set('kit.login.anti_robo', [
        'habilitado'    => true, 'local' => true, 'provedor' => 'recaptcha_v2',
        'chave_do_site' => 'SITE-42', 'chave_secreta' => 'SEGREDO-42', 'pontuacao_minima' => 0.5,
    ]);
    definirAparenciaGlobal(aparenciaCheiaDoAdendo());

    $pagina = visit('/admin/login')->resize($largura, 900);

    $m = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const form = document.querySelector('.fi-auth-form-container').getBoundingClientRect();
            const social = document.querySelector('[data-provedor="google"]');
            const anti = document.querySelector('[data-anti-robo]');
            const caixa = (el) => el ? el.getBoundingClientRect() : null;
            return JSON.stringify({
                form: { l: form.left, r: form.right },
                social: caixa(social), socialVisivel: !!social && social.getClientRects().length > 0,
                anti: caixa(anti),
                // Visível de verdade (CX-B4): nenhum ancestral escondido, `visibility` e opacidade.
                antiExibido: !!anti && anti.checkVisibility({ checkOpacity: true, checkVisibilityCSS: true })
                    && !!anti.closest('.fi-auth-form-container'),
            });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    expect($m['socialVisivel'])->toBeTrue('o botão de login social sumiu no painel')
        ->and($m['social']['left'])->toBeGreaterThanOrEqual($m['form']['l'] - 0.5)
        ->and($m['social']['right'])->toBeLessThanOrEqual($m['form']['r'] + 0.5)
        ->and($m['antiExibido'])->toBeTrue('o desafio anti-robô foi escondido no painel')
        ->and($m['anti']['left'])->toBeGreaterThanOrEqual($m['form']['l'] - 0.5)
        ->and($m['anti']['right'])->toBeLessThanOrEqual($m['form']['r'] + 0.5);
})->with(['computador' => 1440, 'celular' => 390]);

/**
 * Escreve na frase ou no apoio, que desde o Adendo 2 são o editor Markdown do rodapé (EasyMDE sobre
 * CodeMirror): o texto substitui o conteúdo com a origem `+input` — a da digitação; o componente do
 * Filament ignora a origem `setValue` — e o foco sai do editor, que é quando o `live(onBlur)` grava.
 */
function escreverNoEditorDeTexto(mixed $pagina, string $campo, string $texto): void
{
    $seletor = "[id\$=\"aparencia_das_telas.{$campo}\"] .CodeMirror";

    $pagina->assertScript("!!document.querySelector('{$seletor}')?.CodeMirror");
    $pagina->script("(() => { const cm = document.querySelector('{$seletor}').CodeMirror; cm.focus(); cm.execCommand('selectAll'); cm.replaceSelection(".json_encode($texto, JSON_UNESCAPED_UNICODE).", null, '+input'); cm.getInputField().blur(); })()");
}

it('[CT-B18] sobre um gradiente claro, os textos ficam numa superfície escura, em branco, com links sublinhados', function (): void {
    definirAparenciaGlobal([
        ...aparenciaDeTeste('P'),
        'fundo' => 'gradiente', 'gradiente_de' => '#eeeeee', 'gradiente_ate' => '#ffffff',
        'frase' => 'Frase', 'apoio' => 'Veja a [ajuda](https://exemplo.test/ajuda).',
    ]);

    $pagina = visit('/admin/login')->resize(1440, 900);
    $m      = json_decode((string) $pagina->script(<<<'JS'
        (() => {
            const textos = getComputedStyle(document.querySelector('.kit-painel-de-marca__textos'));
            const link = getComputedStyle(document.querySelector('.kit-painel-de-marca__apoio a'));
            return JSON.stringify({ fundo: textos.backgroundColor, cor: textos.color, sublinhado: link.textDecorationLine });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    expect($m['fundo'])->toBe('rgba(17, 24, 39, 0.9)')
        ->and($m['cor'])->toBe('rgb(255, 255, 255)')
        ->and($m['sublinhado'])->toContain('underline');
});

/**
 * [CT-B19] Adendo 4 (RQ-34..RQ-36): do login (formulário à direita) para o "esqueci a senha"
 * (formulário à esquerda), o navegador faz a transição entre os documentos — o `pageswap` da página
 * de origem traz a `viewTransition` — com o painel nomeado para deslizar e o formulário nomeado para
 * só aparecer. Desligada a opção, a troca é seca. No celular, nada é nomeado.
 */
it('[CT-B19] do login para o "esqueci a senha", a troca de tela tem transição só com a opção ligada', function (bool $ligada): void {
    definirAparenciaGlobal([
        ...aparenciaDeTeste('P'), 'lado_do_formulario' => 'direita', 'transicao_entre_telas' => $ligada,
        'telas'                                        => ['recuperacao' => [...aparenciaDeTeste('P'), 'lado_do_formulario' => 'esquerda']],
    ]);
    $this->get('/admin/password-reset/request');

    $nomeDe = fn ($pagina, string $seletor): string => (string) $pagina->script("getComputedStyle(document.querySelector('{$seletor}')).viewTransitionName");

    $pagina = visit('/admin/login')->resize(1440, 900)->assertPresent('.kit-painel-de-marca');

    expect($nomeDe($pagina, '.fi-auth-media-wrapper'))->toBe($ligada ? 'kit-painel' : 'none')
        ->and($nomeDe($pagina, '.fi-auth-form-container'))->toBe($ligada ? 'kit-formulario' : 'none');

    $pagina->script("window.addEventListener('pageswap', (e) => sessionStorage.setItem('kit-transicao', e.viewTransition ? 'sim' : 'nao'))");

    // A outra ponta: a página de destino também aderiu, e a transição chegou a ficar pronta nela
    // (o `pageswap` só prova a origem — RD-D3/CX-D2 da revisão do Adendo 4).
    $pagina->page()->context()->addInitScript(<<<'JS'
        window.kitTransicaoPronta = new Promise((resolve) => {
            addEventListener('pagereveal', (e) => {
                if (! e.viewTransition) { resolve(false); return; }
                e.viewTransition.ready.then(() => resolve(true), () => resolve(false));
            }, { once: true });
        });
    JS);

    $pagina->click('Esqueceu sua senha?')
        ->assertPathIs('/admin/password-reset/request')
        ->assertPresent('.fi-auth-layout.media-right .kit-painel-de-marca');

    expect($pagina->script("sessionStorage.getItem('kit-transicao')"))->toBe($ligada ? 'sim' : 'nao')
        ->and($pagina->script('window.kitTransicaoPronta'))->toBe($ligada);

    // No celular o painel é a faixa do topo e não troca de lado: nada é nomeado (P-33).
    $pagina->resize(390, 844);
    expect($nomeDe($pagina, '.fi-auth-media-wrapper'))->toBe('none');
})->with(['ligada' => true, 'desligada' => false]);

/**
 * [CT-B20] Teste manual da v0.53.1: entrando pelo login no painel de marca com a transição ligada,
 * o painel do Filament não declara a transição. A página que sai a pula, e nenhum erro de
 * JavaScript é registrado — antes, o navegador abortava a transição e registrava
 * "Transition was aborted because of invalid state" a cada login.
 */
it('[CT-B20] entrar pelo login com transição não registra erro de JavaScript', function (): void {
    definirAparenciaGlobal([...aparenciaDeTeste('P'), 'transicao_entre_telas' => true]);
    $usuario = usuarioDoKit('master_global', 'login@example.com');

    // Aquece as duas pontas: na primeira compilação das views, o clique chega antes de a página
    // estar pronta, com ou sem transição — o ruído que se quer medir aqui é só o da transição.
    $this->get('/app/login');
    $this->actingAs($usuario)->get('/app');
    auth()->logout();

    $pagina = visit('/app/login')
        ->resize(1440, 900)
        ->assertPresent('.kit-painel-de-marca[data-kit-transicao]');

    // A transição abortada chega à página de destino como promessa rejeitada sem tratamento —
    // o que um monitor de erros captura e o `assertNoJavaScriptErrors` não vê.
    $pagina->page()->context()->addInitScript(<<<'JS'
        addEventListener('unhandledrejection', (e) => {
            const lista = JSON.parse(sessionStorage.getItem('kit-rejeicoes') || '[]');
            lista.push(String(e.reason && e.reason.message || e.reason));
            sessionStorage.setItem('kit-rejeicoes', JSON.stringify(lista));
        });
    JS);

    $pagina
        ->fill('#form\\.email', 'login@example.com')
        ->fill('#form\\.password', 'password')
        ->press('Login')
        ->assertPathIs('/app')
        ->assertSee('Painel de Controle')
        ->assertNoJavaScriptErrors();

    expect($pagina->script("sessionStorage.getItem('kit-rejeicoes') || '[]'"))->toBe('[]');
});
