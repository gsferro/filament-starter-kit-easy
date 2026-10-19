<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;

const MEDIR_ESTILO_DO_CHAT = <<<'JS'
(() => {
    const botao = document.querySelector('[data-assistente-chat] button[aria-label="Abrir assistente"]');
    const painel = document.querySelector('[data-assistente-chat] [aria-label="Assistente"]');
    const vw=document.documentElement.clientWidth, vh=document.documentElement.clientHeight;
    const canvas=document.createElement('canvas'); canvas.width=canvas.height=1;
    const ctx=canvas.getContext('2d',{willReadFrequently:true});
    const rgba=cor => {
        ctx.clearRect(0,0,1,1); ctx.fillStyle=cor; ctx.fillRect(0,0,1,1);
        return Array.from(ctx.getImageData(0,0,1,1).data);
    };
    const luminancia=cor => {
        const canais=cor.slice(0,3).map(v => {v/=255; return v<=0.04045 ? v/12.92 : ((v+0.055)/1.055)**2.4;});
        return .2126*canais[0]+.7152*canais[1]+.0722*canais[2];
    };
    const medir=el => {
        if (!el) return null;
        const r=el.getBoundingClientRect(), s=getComputedStyle(el);
        return {left:r.left,right:r.right,top:r.top,bottom:r.bottom,width:r.width,height:r.height,
            visivel:s.display!=='none' && s.visibility!=='hidden' && Number(s.opacity)>0 && r.width>0 && r.height>0,
            position:s.position,raio:parseFloat(s.borderTopLeftRadius),cor:rgba(s.backgroundColor)};
    };
    const b=medir(botao), p=medir(painel);
    const paleta=[];
    for (const tom of [50,100,200,300,400,500,600,700,800,900,950]) {
        const sonda=document.createElement('span');
        sonda.style.backgroundColor=`var(--primary-${tom})`;
        botao.parentElement.append(sonda);
        paleta.push(rgba(getComputedStyle(sonda).backgroundColor)); sonda.remove();
    }
    const titulo=painel.querySelector('h2');
    const texto=rgba(getComputedStyle(titulo).color);
    let fundo=null;
    for (let el=painel; el; el=el.parentElement) {
        const cor=rgba(getComputedStyle(el).backgroundColor);
        if (cor[3]>0) { fundo=cor; break; }
    }
    const lFundo=fundo ? luminancia(fundo) : null, lTexto=luminancia(texto);
    const pertence=(x,y) => painel.contains(document.elementFromPoint(x,y));
    return JSON.stringify({vw,vh,escuro:document.documentElement.classList.contains('dark'),botao:b,paleta,painel:p,
        luminancia:lFundo,contraste:(Math.max(lFundo,lTexto)+.05)/(Math.min(lFundo,lTexto)+.05),
        titulo:medir(titulo),textoTitulo:titulo.textContent.trim(),
        conversa:medir(painel.querySelector(':scope > div[x-init]')),
        entrada:medir(painel.querySelector('#assistente-mensagem')),
        centroPertence:pertence(p.left+p.width/2,p.top+p.height/2),
        cantoPertence:pertence(p.right-2,p.top+2)});
})()
JS;

function abrirPainelDoEstiloDoChat(string $tema): object
{
    test()->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    test()->actingAs(usuarioDoKit('panel_user', 'ana@example.com'));
    ligarDock([]);
    ligarAgentesDeIa(true, ['app']);
    test()->get('/app')->assertOk();
    $pagina = $tema === 'escuro' ? visit('/app')->on()->desktop()->inDarkMode() : visit('/app')->on()->desktop()->inLightMode();
    $pagina->resize(1280, 800)->assertPathIs('/app')
        ->assertVisible('[aria-label="Abrir assistente"]')
        ->assertScript('document.querySelector(".qad-root") === null')
        ->assertScript('document.documentElement.classList.contains("dark")', $tema === 'escuro');

    return $pagina;
}

it('[CT-B09] o botao do chat e fixo, redondo e tem cor primaria em cada tema', function (string $tema): void {
    $pagina = abrirPainelDoEstiloDoChat($tema);
    $m      = json_decode($pagina->script(MEDIR_ESTILO_DO_CHAT), true, flags: JSON_THROW_ON_ERROR);
    $b      = $m['botao'];

    expect($b['position'])->toBe('fixed');
    expect($b['visivel'])->toBeTrue();
    expect($b['left'])->toBeGreaterThanOrEqual(0);
    expect($b['top'])->toBeGreaterThanOrEqual(0);
    expect($b['right'])->toBeLessThanOrEqual($m['vw']);
    expect($b['bottom'])->toBeLessThanOrEqual($m['vh']);
    expect($b['left'] + $b['width'] / 2)->toBeGreaterThan($m['vw'] / 2);
    expect($b['top'] + $b['height'] / 2)->toBeGreaterThan($m['vh'] / 2);
    expect(abs($b['width'] - $b['height']))->toBeLessThanOrEqual(1);
    expect($b['raio'])->toBeGreaterThanOrEqual($b['width'] / 2 - 1);
    expect($b['cor'][3])->toBeGreaterThan(0);
    expect(in_array($b['cor'], $m['paleta'], true))->toBeTrue('Fundo do botao fora da paleta primaria');
    $pagina->screenshot(filename: 'chat-botao-'.$tema)->assertNoJavaScriptErrors();
})->with([
    'claro'  => ['claro'],
    'escuro' => ['escuro'],
]);

it('[CT-B10] o slide-over e lateral, completo e acompanha o tema com contraste legivel', function (string $tema): void {
    $pagina = abrirPainelDoEstiloDoChat($tema);
    $pagina->click('[aria-label="Abrir assistente"]')->assertVisible('[aria-label="Assistente"]');
    $pagina->assertScript('getComputedStyle(document.querySelector("[aria-label=Assistente]")).opacity === "1"');
    $m = json_decode($pagina->script(MEDIR_ESTILO_DO_CHAT), true, flags: JSON_THROW_ON_ERROR);
    $p = $m['painel'];

    expect($p['position'])->toBe('fixed');
    expect(abs($p['top']))->toBeLessThanOrEqual(1);
    expect(abs($p['right'] - $m['vw']))->toBeLessThanOrEqual(1);
    expect(abs($p['bottom'] - $m['vh']))->toBeLessThanOrEqual(1);
    expect($p['width'])->toBeLessThan($m['vw']);
    expect($p['cor'][3])->toBeGreaterThan(0, 'Fundo no proprio painel');
    if ($tema === 'claro') {
        expect($m['luminancia'])->toBeGreaterThanOrEqual(0.5);
    } else {
        expect($m['luminancia'])->toBeLessThanOrEqual(0.2);
    }
    expect($m['textoTitulo'])->toBe('Assistente');
    foreach (['titulo', 'conversa', 'entrada'] as $nome) {
        $r = $m[$nome];
        expect($r)->not->toBeNull();
        expect($r['visivel'])->toBeTrue($nome);
        expect($r['left'])->toBeGreaterThanOrEqual($p['left'] - 1);
        expect($r['right'])->toBeLessThanOrEqual($p['right'] + 1);
        expect($r['top'])->toBeGreaterThanOrEqual($p['top'] - 1);
        expect($r['bottom'])->toBeLessThanOrEqual($p['bottom'] + 1);
    }
    expect($m['contraste'])->toBeGreaterThanOrEqual(4.5);
    expect($m['centroPertence'])->toBeTrue();
    expect($m['cantoPertence'])->toBeTrue();
    $pagina->screenshot(filename: 'chat-slideover-'.$tema)->assertNoJavaScriptErrors();
})->with([
    'claro'  => ['claro'],
    'escuro' => ['escuro'],
]);
