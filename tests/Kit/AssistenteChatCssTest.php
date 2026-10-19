<?php

use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Symfony\Component\CssSelector\CssSelectorConverter;

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

function cssDoChat(): string
{
    return (string) preg_replace('~/\*.*?\*/~s', '', file_get_contents(resource_path('css/filament/assistente-chat.css')));
}

/** @return list<string> */
function classesLiteraisDoChat(string $html): array
{
    preg_match_all('/\bclass="([^"]+)"/', $html, $atributos);

    return array_values(array_unique(preg_split('/\s+/', trim(implode(' ', $atributos[1]))) ?: []));
}

function classeDoChatCoberta(string $classe, string $css, bool $escopada = true): bool
{
    $classe  = preg_quote(addcslashes($classe, ':/[].'), '~');
    $prefixo = $escopada ? '\[data-assistente-chat\][^{}]*' : '';

    return preg_match('~'.$prefixo.'\.'.$classe.'(?![\w-])[^{}]*\{~', $css) === 1;
}

function xpathDoChat(string $html): DOMXPath
{
    // DOMDocument nao reconhece nomes de atributo Alpine iniciados por @.
    $html      = preg_replace('/\s@[\w.:-]+="[^"]*"/', '', $html);
    $documento = new DOMDocument;
    @$documento->loadHTML('<?xml encoding="UTF-8">'.$html);

    return new DOMXPath($documento);
}

it('[CT-01] toda classe emitida pelo chat tem regra escopada ou no Filament', function (string $origem, int $piso): void {
    $ana = usuarioDoKit('panel_user', 'ana@example.com');
    $this->actingAs($ana);
    ligarAgentesDeIa(true, ['app']);
    if ($origem === 'historico') {
        conversaPara($ana, 'Conversa de teste');
    }
    if ($origem === 'fonte') {
        $html = file_get_contents(resource_path('views/livewire/assistente-chat-widget.blade.php'));
        $html = preg_replace('/\{\{--.*?--\}\}/s', '', $html);
    } else {
        $xpath = xpathDoChat($this->get('/app')->assertOk()->getContent());
        $raiz  = $xpath->query('//*[@data-assistente-chat]')->item(0);
        expect($raiz)->not->toBeNull();
        $html = $raiz->ownerDocument->saveHTML($raiz);
    }

    $classes  = classesLiteraisDoChat($html);
    $css      = cssDoChat();
    $filament = file_get_contents(public_path('css/filament/filament/app.css'));
    $semRegra = array_values(array_filter($classes, static fn (string $classe): bool => ! classeDoChatCoberta($classe, $css) && ! classeDoChatCoberta($classe, $filament, false)));

    expect(count($classes))->toBeGreaterThanOrEqual($piso);
    expect($semRegra)->toBe([], 'Classes do chat sem regra');
})->with([
    'GET inicial'               => ['inicial', 20],
    'GET com conversa anterior' => ['historico', 25],
    'literais da blade'         => ['fonte', 40],
]);

it('[CT-02] toda regra do chat permanece sob o escopo', function (): void {
    preg_match_all('/([^{}]+)\{/', cssDoChat(), $regras);
    $foraDoEscopo = [];
    $seletores    = 0;
    foreach ($regras[1] as $lista) {
        foreach (explode(',', $lista) as $seletor) {
            $seletor = trim($seletor);
            if ($seletor === '' || str_starts_with($seletor, '@') || preg_match('/^(from|to|\d+(?:\.\d+)?%)$/', $seletor)) {
                continue;
            }
            $seletores++;
            if (! str_starts_with($seletor, '[data-assistente-chat]')) {
                $foraDoEscopo[] = $seletor;
            }
        }
    }

    expect($seletores)->toBeGreaterThan(0);
    expect($foraDoEscopo)->toBe([], 'Seletores fora do escopo do chat');
});

it('[CT-03] o HTML servido posiciona os botoes sem JavaScript', function (string $papel, string $painel, string $recuo): void {
    ligarAgentesDeIa(true, ['app']);
    $this->actingAs(usuarioDoKit($papel, 'pessoa@example.com'));
    $html   = $this->get($painel)->assertOk()->getContent();
    $xpath  = xpathDoChat($html);
    $inicio = strpos($html, 'data-voltar-ao-topo');
    expect($inicio)->not->toBeFalse();
    $fim       = strpos($html, '</button>', $inicio);
    $classes   = classesLiteraisDoChat(substr($html, $inicio, $fim - $inicio));
    $cssVoltar = file_get_contents(resource_path('css/filament/voltar-ao-topo.css'));
    foreach (['fixed', 'right-6', $recuo] as $classe) {
        expect($classes)->toContain($classe);
        expect($cssVoltar)->toContain('[data-voltar-ao-topo].'.$classe);
    }
    $cssVoltar = preg_replace('~/\*.*?\*/~s', '', $cssVoltar);
    $conversor = new CssSelectorConverter;
    $botao     = $xpath->query('//button[@data-voltar-ao-topo]')->item(0);
    expect($botao)->not->toBeNull();
    $regraDoEixo = null;
    preg_match_all('/([^{}]+)\{([^{}]*)\}/', $cssVoltar, $regras, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($regras as $indice => $regra) {
        $seletor = trim($regra[1][0]);
        $antes   = substr($cssVoltar, 0, $regra[1][1]);
        if (! in_array($seletor, ['[data-voltar-ao-topo]', '[data-voltar-ao-topo].right-6'], true)
            || substr_count($antes, '{') !== substr_count($antes, '}')
            || ! preg_match('/(?:^|;)\s*right\s*:\s*1\.5rem\s*(?:;|$)/', $regra[2][0])
            || ! preg_match('/(?:^|;)\s*margin-right\s*:\s*0\.375rem\s*(?:;|$)/', $regra[2][0])) {
            continue;
        }
        expect($xpath->query($conversor->toXPath($seletor)))->toContain($botao);
        $regraDoEixo = $indice;
        break;
    }
    expect($regraDoEixo)->not->toBeNull('Regra exata dos 30 px ausente fora de @media');
    foreach (array_slice($regras, $regraDoEixo + 1) as $regra) {
        if (! preg_match('/(?:^|;)\s*(?:right|margin-right)\s*:/', $regra[2][0])) {
            continue;
        }
        $seletor = trim($regra[1][0]);
        expect($xpath->query($conversor->toXPath($seletor)))->not->toContain($botao);
    }
    $chat = $xpath->query('//*[@data-assistente-chat]//button[@aria-label="Abrir assistente"]');
    expect($chat->length)->toBe($painel === '/app' ? 1 : 0);
    if ($painel === '/app') {
        foreach (['fixed', 'bottom-6', 'right-6'] as $classe) {
            expect(explode(' ', $chat->item(0)->getAttribute('class')))->toContain($classe);
            expect(classeDoChatCoberta($classe, cssDoChat()))->toBeTrue();
        }
    }
})->with([
    'ana /app'     => ['panel_user', '/app', 'bottom-24'],
    'bruno /admin' => ['admin', '/admin', 'bottom-6'],
]);
