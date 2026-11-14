<?php

use App\Models\User;
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
    Storage::disk('public')->delete(['kit/ct-b04-logo.png', 'kit/ct-b04-arte.png']);
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

    $pagina->fill('[id$="aparencia_das_telas.frase"]', $sentinela)
        ->select('.kit-preview-das-telas__select', $tela)
        ->click($dimensao === 'celular' ? 'Celular' : 'Computador')
        ->click('Atualizar preview');

    $iframe = "document.querySelector('.kit-preview-das-telas__iframe')";

    // O documento interno recebe o rascunho (A), não o persistido (P).
    $pagina->assertScript("{$iframe}.contentDocument?.body?.innerText.includes('{$sentinela}') === true")
        ->assertScript("!{$iframe}.contentDocument.body.innerText.includes('PADRAO')");

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

    $pagina->type($seletor, $emojis)
        ->assertScript("[...document.querySelector('{$seletor}').value].length === {$n}");

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
        $campoDoEditor = '[id$="aparencia_das_telas.'.$campo.'"]';
        // Tab tira o foco do campo (o live onBlur grava o rascunho), como a pessoa faria.
        $pagina->fill($campoDoEditor, $texto)->keys($campoDoEditor, 'Tab')->click('Atualizar preview');
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
