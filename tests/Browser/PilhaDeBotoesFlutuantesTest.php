<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\DB;

const MEDIR_PILHA_FLUTUANTE = <<<'JS'
(() => {
    const vw = document.documentElement.clientWidth, vh = document.documentElement.clientHeight;
    const raiz = document.querySelector('.qad-root');
    const medir = el => {
        if (!el) return null;
        const r = el.getBoundingClientRect(), s = getComputedStyle(el);
        return {left:r.left, right:r.right, top:r.top, bottom:r.bottom, width:r.width, height:r.height,
            centroX:r.left+r.width/2, direita:vw-r.right, inferior:vh-r.bottom,
            visivel:s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity)>0 && r.width>0 && r.height>0};
    };
    const gatilho = medir(document.querySelector('.qad-root .qad-trigger'));
    let dock = gatilho ? {...gatilho} : null;
    if (dock && raiz.dataset.open === 'true') {
        for (const el of raiz.querySelectorAll('.qad-item')) {
            const r = medir(el);
            if (!r.visivel) continue;
            dock.left=Math.min(dock.left,r.left); dock.right=Math.max(dock.right,r.right);
            dock.top=Math.min(dock.top,r.top); dock.bottom=Math.max(dock.bottom,r.bottom);
        }
    }
    if (raiz?.dataset.mode === 'bar') dock = medir(raiz);
    return {vw,vh,offset:raiz ? parseFloat(getComputedStyle(raiz).getPropertyValue('--qad-offset')) : null,
        raiz:medir(raiz),gatilho,dock,chat:medir(document.querySelector('[data-assistente-chat] button[aria-label="Abrir assistente"]')),
        voltar:medir(document.querySelector('[data-voltar-ao-topo]'))};
})()
JS;

function abrirPainelDaPilha(string $painel = '/app', bool $dock = true, bool $celular = false, bool $barra = false): object
{
    test()->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $papel   = ['/app' => 'panel_user', '/admin' => 'admin', '/infra' => 'infra'][$painel];
    $usuario = usuarioDoKit($papel, 'pessoa@example.com');
    if ($dock) {
        concederDock($papel);
    }
    ligarDock(['admin', 'app', 'infra']);
    if ($barra) {
        DB::table('users')->where('id', $usuario->id)->update(['dock_layout' => 'bar']);
        $usuario->refresh();
    }
    test()->actingAs($usuario);
    test()->get($painel)->assertOk();
    $pagina = $celular ? visit($painel)->on()->iPhone15()->inLightMode() : visit($painel)->on()->desktop()->inLightMode();
    if (! $celular) {
        $pagina->resize(1280, 800);
    }
    $pagina->assertPathIs($painel);
    if ($dock) {
        $pagina->assertVisible('.qad-root .qad-trigger');
    }

    return $pagina;
}

function moverDockDaPilha(object $pagina, string $canto = 'bottom-right'): void
{
    if ($pagina->attribute('.qad-root', 'data-open') !== 'true') {
        $pagina->click('.qad-root .qad-trigger');
    }
    $pagina->click('.qad-move-toggle')->click('[data-corner="'.$canto.'"]');
    // A barra tem padding interno: o canto e da raiz, o eixo continua sendo do gatilho.
    $base       = $pagina->attribute('.qad-root', 'data-mode') === 'bar' ? 'm.raiz' : 'm.gatilho';
    $horizontal = $base.(str_ends_with($canto, 'right') ? '.direita' : '.left');
    $vertical   = $base.(str_starts_with($canto, 'bottom') ? '.inferior' : '.top');
    $pagina->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return Math.abs('.$horizontal.'-m.offset)<=1 && Math.abs('.$vertical.'-m.offset)<=1 && !document.querySelector(".qad-root").hasAttribute("data-moving") && !document.querySelector(".qad-root").hasAttribute("data-settling"); }');
}

function rolarPaginaDaPilha(object $pagina): void
{
    $pagina->script("document.body.style.minHeight='4000px'; window.scrollTo({top:800,behavior:'instant'})");
    $pagina->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return window.scrollY>=799 && m.voltar.visivel; }');
}

/** @return array<string, mixed> */
function medirPilhaFlutuante(object $pagina): array
{
    return json_decode($pagina->script('JSON.stringify('.MEDIR_PILHA_FLUTUANTE.')'), true, flags: JSON_THROW_ON_ERROR);
}

function esperarPilhaFlutuante(object $pagina, bool $comVoltar = true): void
{
    $voltar = $comVoltar ? ' && m.voltar.visivel && Math.abs((m.chat ?? m.dock).top-m.voltar.bottom-16)<=1 && Math.abs(m.voltar.centroX-m.gatilho.centroX)<=1' : '';
    $pagina->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return m.dock && (!m.chat || (m.chat.visivel && Math.abs(m.dock.top-m.chat.bottom-16)<=1 && Math.abs(m.chat.centroX-m.gatilho.centroX)<=1))'.$voltar.'; }');
}

/** @param array<string, mixed> $m */
function afirmarPilhaFlutuante(array $m): void
{
    $ordem = $m['chat'] ? ['dock', 'chat', 'voltar'] : ['dock', 'voltar'];
    foreach (array_slice($ordem, 1) as $indice => $nome) {
        expect($m[$nome]['visivel'])->toBeTrue();
        expect(abs($m[$nome]['centroX'] - $m['gatilho']['centroX']))->toBeLessThanOrEqual(1);
        expect(abs($m[$ordem[$indice]]['top'] - $m[$nome]['bottom'] - 16))->toBeLessThanOrEqual(1);
    }
    afirmarParesDaPilha($m);
}

/** @param array<string, mixed> $m */
function afirmarParesDaPilha(array $m): void
{
    $retangulos = array_filter([$m['dock'], $m['chat'], $m['voltar']]);
    $retangulos = array_values($retangulos);
    foreach ($retangulos as $i => $a) {
        foreach (array_slice($retangulos, $i + 1) as $b) {
            expect($a['left'] < $b['right'] && $b['left'] < $a['right'] && $a['top'] < $b['bottom'] && $b['top'] < $a['bottom'])->toBeFalse('Retangulos da pilha se intersectam');
        }
    }
}

/** @param array<string, mixed> $m */
function afirmarPosicoesOriginaisDaPilha(array $m, int $recuo): void
{
    expect(abs($m['voltar']['direita'] - 30))->toBeLessThanOrEqual(1);
    expect(abs($m['voltar']['inferior'] - $recuo))->toBeLessThanOrEqual(1);
    if ($m['chat']) {
        expect(abs($m['chat']['direita'] - 24))->toBeLessThanOrEqual(1);
        expect(abs($m['chat']['inferior'] - 24))->toBeLessThanOrEqual(1);
    }
}

/** @param array<string, mixed> $antes @param array<string, mixed> $depois */
function afirmarRetanguloDaPilha(array $antes, array $depois): void
{
    foreach (['left', 'right', 'top', 'bottom', 'width', 'height'] as $coordenada) {
        expect(abs($antes[$coordenada] - $depois[$coordenada]))->toBeLessThanOrEqual(1, $coordenada);
    }
}

function soltarDockDaPilha(object $pagina, bool $longe = false): void
{
    moverDockDaPilha($pagina);
    $alvo = $longe ? medirPilhaFlutuante($pagina)['vh'] / 2 : 48;
    for ($passo = 0; $passo < 100; $passo++) {
        if (medirPilhaFlutuante($pagina)['gatilho']['inferior'] >= $alvo) {
            break;
        }
        // keys() envia cada item isoladamente; Playwright recebe o acorde numa unica string.
        $pagina->keys('[data-layout] button[x-ref="trigger"]', 'Alt+ArrowUp');
        $pagina->assertScript('!document.querySelector(".qad-root").hasAttribute("data-settling")');
    }
    $pagina->assertScript('!document.querySelector(".qad-root").hasAttribute("data-moving")');
    $m = medirPilhaFlutuante($pagina);
    expect(abs($m['gatilho']['direita'] - $m['offset']))->toBeLessThanOrEqual(1);
    expect($m['gatilho']['inferior'])->toBeGreaterThanOrEqual($longe ? $m['vh'] / 2 : 32);
    if (! $longe) {
        expect($m['gatilho']['inferior'])->toBeLessThanOrEqual(72);
    }
}

it('[CT-B01] a pilha fica acima do dock, alinhada, a 16 px e sem sobreposicao', function (string $painel, bool $celular): void {
    $pagina = abrirPainelDaPilha($painel, celular: $celular);
    moverDockDaPilha($pagina);
    rolarPaginaDaPilha($pagina);
    esperarPilhaFlutuante($pagina);
    $m = medirPilhaFlutuante($pagina);

    expect(abs($m['gatilho']['direita'] - $m['offset']))->toBeLessThanOrEqual(1);
    expect(abs($m['gatilho']['inferior'] - $m['offset']))->toBeLessThanOrEqual(1);
    expect($m['chat'] !== null)->toBe($painel === '/app');
    afirmarPilhaFlutuante($m);
    $pagina->assertNoJavaScriptErrors();
})->with([
    'bruno /admin desktop' => ['/admin', false],
    'iris /infra desktop'  => ['/infra', false],
    'ana /app desktop'     => ['/app', false],
    'ana /app celular'     => ['/app', true],
]);

it('[CT-B02] a posicao lembrada ja forma a pilha ao carregar', function (): void {
    $pagina = abrirPainelDaPilha();
    moverDockDaPilha($pagina);
    $pagina->script('location.reload()');
    $pagina->assertScript('document.querySelector(".qad-root[data-placed]") !== null');
    esperarPilhaFlutuante($pagina, false);
    $m = medirPilhaFlutuante($pagina);

    expect($m['chat']['visivel'])->toBeTrue();
    expect(abs($m['dock']['top'] - $m['chat']['bottom'] - 16))->toBeLessThanOrEqual(1);
    expect(abs($m['chat']['centroX'] - $m['gatilho']['centroX']))->toBeLessThanOrEqual(1);
    expect($m['chat']['bottom'])->toBeLessThanOrEqual($m['dock']['top']);
    $pagina->assertNoJavaScriptErrors();
});

it('[CT-B03] fechar depois de nova conversa devolve o chat ao lugar na pilha', function (): void {
    $pagina = abrirPainelDaPilha();
    moverDockDaPilha($pagina);
    rolarPaginaDaPilha($pagina);
    esperarPilhaFlutuante($pagina);
    $antes = medirPilhaFlutuante($pagina);
    $pagina->click('[aria-label="Abrir assistente"]')->assertVisible('[aria-label="Assistente"]');
    // Nao devolve a funcao de cleanup: evaluate() do Playwright a executaria imediatamente.
    $pagina->script('() => { window.pilhaCommits=0; Livewire.interceptMessage(({onSuccess}) => onSuccess(({onRender}) => onRender(() => window.pilhaCommits++))); }');
    $pagina->click('button[wire\\:click="novaConversa"]')->assertScript('window.pilhaCommits > 0');
    $pagina->click('[aria-label="Fechar assistente"]');
    esperarPilhaFlutuante($pagina);
    $depois = medirPilhaFlutuante($pagina);

    afirmarRetanguloDaPilha($antes['chat'], $depois['chat']);
    afirmarParesDaPilha($depois);
    $pagina->assertNoJavaScriptErrors();
});

it('[CT-B04] fora da area da pilha os botoes ficam nas posicoes originais', function (string $painel, string $canto, bool $idaEVolta, int $recuo): void {
    $pagina = abrirPainelDaPilha($painel);
    if ($idaEVolta) {
        moverDockDaPilha($pagina);
        esperarPilhaFlutuante($pagina, false);
    }
    moverDockDaPilha($pagina, $canto);
    rolarPaginaDaPilha($pagina);
    $m = medirPilhaFlutuante($pagina);

    afirmarPosicoesOriginaisDaPilha($m, $recuo);
    expect($m['chat'] !== null)->toBe($painel === '/app');
    $pagina->assertNoJavaScriptErrors();
})->with([
    'inferior esquerdo /app'   => ['/app', 'bottom-left', false, 96],
    'superior direito /app'    => ['/app', 'top-right', false, 96],
    'ida e volta /app'         => ['/app', 'bottom-left', true, 96],
    'inferior esquerdo /admin' => ['/admin', 'bottom-left', false, 24],
]);

it('[CT-B05] sem dock os botoes ficam nas posicoes originais', function (string $painel, bool $permissao, int $recuo): void {
    $pagina = abrirPainelDaPilha($painel, false);
    if ($permissao) {
        concederDock('admin');
        ligarDock(['app', 'infra']);
        test()->get($painel)->assertOk();
        $pagina = visit($painel)->on()->desktop()->inLightMode();
        $pagina->resize(1280, 800)->assertPathIs($painel);
    }
    rolarPaginaDaPilha($pagina);
    $pagina->assertScript('document.querySelector(".qad-root") === null');
    $m = medirPilhaFlutuante($pagina);

    afirmarPosicoesOriginaisDaPilha($m, $recuo);
    expect($m['chat'] !== null)->toBe($painel === '/app');
    afirmarParesDaPilha($m);
    if ($m['chat']) {
        expect(abs($m['chat']['centroX'] - $m['voltar']['centroX']))->toBeLessThanOrEqual(1);
    }
    $pagina->assertNoJavaScriptErrors();
})->with([
    'ana sem permissao'      => ['/app', false, 96],
    'bruno painel desligado' => ['/admin', true, 24],
]);

it('[CT-B06] abrir o dock oculta a pilha somente sobre seu lugar', function (string $lugar, bool $ocultos): void {
    $pagina = abrirPainelDaPilha();
    if ($lugar === 'solto') {
        soltarDockDaPilha($pagina);
    } else {
        moverDockDaPilha($pagina, $lugar);
    }
    rolarPaginaDaPilha($pagina);
    $antes = medirPilhaFlutuante($pagina);
    $pagina->click('.qad-root .qad-trigger')->assertAttribute('.qad-root', 'data-open', 'true');
    $valor = $ocultos ? 'false' : 'true';
    $pagina->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return m.chat.visivel === '.$valor.' && m.voltar.visivel === '.$valor.'; }');
    $m = medirPilhaFlutuante($pagina);

    expect($m['chat']['visivel'])->toBe(! $ocultos);
    expect($m['voltar']['visivel'])->toBe(! $ocultos);
    if (! $ocultos) {
        afirmarPosicoesOriginaisDaPilha($m, 96);
    } else {
        $retangulos = json_encode([$antes['chat'], $antes['voltar']], JSON_THROW_ON_ERROR);
        $medirAlvos = '(() => { const antigos = '.$retangulos.';
            const cruza = (a,b) => a.left<b.right && b.left<a.right && a.top<b.bottom && b.top<a.bottom;
            return Array.from(document.querySelectorAll(".qad-root[data-open=true] .qad-item")).filter(el => {
                const r=el.getBoundingClientRect(), s=getComputedStyle(el);
                return r.width>0 && r.height>0 && s.display!=="none" && s.visibility!=="hidden" && Number(s.opacity)>0 && antigos.some(a=>cruza(a,r));
            }).flatMap(el => { const r=el.getBoundingClientRect(); return antigos.filter(a=>cruza(a,r)).map(a => {
                const x=(Math.max(a.left,r.left)+Math.min(a.right,r.right))/2;
                const y=(Math.max(a.top,r.top)+Math.min(a.bottom,r.bottom))/2;
                return el.contains(document.elementFromPoint(x,y));
            }); }); })()';
        $pagina->assertScript('() => { const alvos = '.$medirAlvos.'; return alvos.length>0 && alvos.every(Boolean); }');
        $alvos = json_decode($pagina->script('JSON.stringify('.$medirAlvos.')'), true, flags: JSON_THROW_ON_ERROR);
        expect($alvos)->not->toBeEmpty('Nenhum item do leque cruza o antigo lugar dos botões');
        foreach ($alvos as $recebeClique) {
            expect($recebeClique)->toBeTrue();
        }
        $pagina->script('() => {
            const itens=Array.from(document.querySelectorAll(".qad-root[data-open=true] .qad-item button, .qad-root[data-open=true] .qad-item a[href], .qad-root[data-open=true] .qad-item [tabindex]")).filter(el => {
                const r=el.getBoundingClientRect(), s=getComputedStyle(el);
                return !el.disabled && r.width>0 && r.height>0 && s.display!=="none" && s.visibility!=="hidden" && Number(s.opacity)>0;
            });
            itens.sort((a,b)=>(a.tabIndex>0 ? a.tabIndex : Infinity)-(b.tabIndex>0 ? b.tabIndex : Infinity));
            const ultimo=itens.at(-1);
            ultimo.setAttribute("data-ultimo-item-pilha", "");
            ultimo.focus();
        }');
        $pagina->assertScript('document.activeElement === document.querySelector("[data-ultimo-item-pilha]")');
        $pagina->keys('[data-ultimo-item-pilha]', 'Tab');
        $pagina->assertScript('!document.activeElement.matches(\'[data-assistente-chat] button[aria-label="Abrir assistente"], [data-voltar-ao-topo]\')');
    }
    $pagina->assertNoJavaScriptErrors();
})->with([
    'canto inferior direito'  => ['bottom-right', true],
    'canto inferior esquerdo' => ['bottom-left', false],
    'solto sobre o chat'      => ['solto', true],
]);

it('[CT-B07] fechar o dock restaura os retangulos e a visibilidade', function (): void {
    $pagina = abrirPainelDaPilha();
    moverDockDaPilha($pagina);
    rolarPaginaDaPilha($pagina);
    esperarPilhaFlutuante($pagina);
    $antes = medirPilhaFlutuante($pagina);
    $pagina->click('.qad-root .qad-trigger')->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return !m.chat.visivel && !m.voltar.visivel; }');
    $pagina->click('.qad-root .qad-trigger')->assertAttribute('.qad-root', 'data-open', 'false');
    esperarPilhaFlutuante($pagina);
    $depois = medirPilhaFlutuante($pagina);

    foreach (['chat', 'voltar'] as $nome) {
        expect($depois[$nome]['visivel'])->toBeTrue();
        afirmarRetanguloDaPilha($antes[$nome], $depois[$nome]);
    }
    $pagina->assertNoJavaScriptErrors();
});

it('[CT-B08] dock solto só desloca a pilha quando cruza seu lugar', function (string $linha, string $painel): void {
    $pagina = abrirPainelDaPilha($painel);
    $pagina->assertAttribute('.qad-root', 'data-mode', 'speed-dial')->assertAttribute('.qad-root', 'data-open', 'false');
    if (in_array($linha, ['d', 'e'], true)) {
        moverDockDaPilha($pagina, 'bottom-left');
        rolarPaginaDaPilha($pagina);
        $original = medirPilhaFlutuante($pagina);
        $alvo     = 96 + $original['voltar']['height'] + ($linha === 'd' ? 4 : -4);
        moverDockDaPilha($pagina);
        for ($passo = 0; $passo < 100 && medirPilhaFlutuante($pagina)['gatilho']['inferior'] < $alvo - 3; $passo++) {
            $pagina->keys('[data-layout] button[x-ref="trigger"]', 'Alt+ArrowUp');
            $pagina->assertScript('!document.querySelector(".qad-root").hasAttribute("data-settling")');
        }
        $m = medirPilhaFlutuante($pagina);
        if (abs($m['gatilho']['inferior'] - $alvo) > 3) {
            // O passo do teclado pode saltar a faixa de 1 a 8 px autorizada no CT-B08 linha d.
            $pagina->script('() => { const raiz=document.querySelector(".qad-root"), r=raiz.getBoundingClientRect(); Alpine.$data(raiz).commit({x:r.left,y:document.documentElement.clientHeight-r.height-'.$alvo.'}); }');
            $pagina->assertScript('!document.querySelector(".qad-root").hasAttribute("data-settling")');
            fwrite(STDOUT, "\nCT-B08 {$linha}: fallback Alpine.commit; passo do teclado fora da faixa de 1 a 8 px.\n");
        }
        $m     = medirPilhaFlutuante($pagina);
        $folga = $m['gatilho']['inferior'] - 96 - $original['voltar']['height'];
        if ($linha === 'e') {
            $folga *= -1;
        }
        expect($folga)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(8);
    } elseif ($linha === 'f') {
        moverDockDaPilha($pagina, 'bottom-left');
        $original = medirPilhaFlutuante($pagina);
        $alvo     = 24 + $original['chat']['width'] - 10;
        soltarDockDaPilha($pagina);
        for ($passo = 0; $passo < 100 && medirPilhaFlutuante($pagina)['gatilho']['direita'] < $alvo - 1; $passo++) {
            $pagina->keys('[data-layout] button[x-ref="trigger"]', 'Alt+ArrowLeft');
            $pagina->assertScript('!document.querySelector(".qad-root").hasAttribute("data-settling")');
        }
        if (abs(medirPilhaFlutuante($pagina)['gatilho']['direita'] - $alvo) > 1) {
            $pagina->script('() => { const raiz=document.querySelector(".qad-root"), r=raiz.getBoundingClientRect(); Alpine.$data(raiz).commit({x:document.documentElement.clientWidth-r.width-'.$alvo.',y:r.top}); }');
            $pagina->assertScript('!document.querySelector(".qad-root").hasAttribute("data-settling")');
            fwrite(STDOUT, "\nCT-B08 f: fallback Alpine.commit; passo do teclado fora da folga horizontal.\n");
        }
        $m = medirPilhaFlutuante($pagina);
        expect(abs($m['gatilho']['direita'] - $alvo))->toBeLessThanOrEqual(1);
        expect($m['gatilho']['direita'])->toBeGreaterThan($m['offset']);
        expect($m['gatilho']['inferior'])->toBeGreaterThanOrEqual(32)->toBeLessThanOrEqual(72);
    } else {
        soltarDockDaPilha($pagina, $linha === 'b');
        if ($linha === 'c') {
            expect(medirPilhaFlutuante($pagina)['gatilho']['inferior'])->toBeLessThanOrEqual(52);
        }
    }
    $antes = medirPilhaFlutuante($pagina);

    rolarPaginaDaPilha($pagina);

    if ($linha === 'f') {
        $pagina->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return m.chat.visivel && m.voltar.visivel && Math.abs(m.dock.top-m.chat.bottom-16)<=1 && Math.abs(m.chat.top-m.voltar.bottom-16)<=1; }');
    } elseif (in_array($linha, ['a', 'c', 'e'], true)) {
        esperarPilhaFlutuante($pagina);
    } else {
        $pagina->assertScript('() => { const m = '.MEDIR_PILHA_FLUTUANTE.'; return Math.abs(m.voltar.inferior-96)<=1 && Math.abs(m.chat.inferior-24)<=1; }');
    }
    $m = medirPilhaFlutuante($pagina);
    afirmarRetanguloDaPilha($antes['dock'], $m['dock']);
    if (in_array($linha, ['b', 'd'], true)) {
        afirmarPosicoesOriginaisDaPilha($m, 96);
        afirmarParesDaPilha($m);
    } elseif ($linha === 'f') {
        expect(abs($m['dock']['top'] - $m['chat']['bottom'] - 16))->toBeLessThanOrEqual(1);
        expect(abs($m['chat']['top'] - $m['voltar']['bottom'] - 16))->toBeLessThanOrEqual(1);
        expect(abs($m['chat']['centroX'] - $m['voltar']['centroX']))->toBeLessThanOrEqual(1);
        afirmarParesDaPilha($m);
    } else {
        afirmarPilhaFlutuante($m);
    }
    $pagina->assertNoJavaScriptErrors();
})->with([
    'a: sobre o chat'                => ['a', '/app'],
    'b: longe a meia altura'         => ['b', '/app'],
    'c: sobre o voltar sem chat'     => ['c', '/admin'],
    'd: de 1 a 8 px acima do voltar' => ['d', '/app'],
    'e: de 1 a 8 px sobre o voltar'  => ['e', '/app'],
    'f: cruza o chat pelo eixo x'    => ['f', '/app'],
]);

it('[CT-B11] a barra mantem a pilha visivel a 16 px no eixo do gatilho', function (): void {
    $pagina = abrirPainelDaPilha(barra: true);
    $pagina->assertAttribute('.qad-root', 'data-mode', 'bar')->assertAttribute('.qad-root', 'data-open', 'true');
    moverDockDaPilha($pagina);
    $pagina->assertAttribute('.qad-root', 'data-open', 'true');
    rolarPaginaDaPilha($pagina);
    esperarPilhaFlutuante($pagina);
    $m = medirPilhaFlutuante($pagina);

    afirmarPilhaFlutuante($m);
    expect(abs($m['chat']['direita'] - 24))->toBeLessThanOrEqual(1);
    expect(abs($m['raiz']['direita'] - $m['offset']))->toBeLessThanOrEqual(1);
    expect(abs($m['raiz']['inferior'] - $m['offset']))->toBeLessThanOrEqual(1);
    $pagina->assertNoJavaScriptErrors();
});
