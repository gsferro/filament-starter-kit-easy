<?php

use App\Console\Commands\KitArte;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Arr;

it('[CT-49] declara os adotados em produção e mantém os recusados fora do composer e do lock', function (): void {
    $composer = composerDoKit();
    $lock     = json_decode((string) file_get_contents(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);

    foreach (['sehsah/filament-remember-table', 'asignua/filament-infinite-scroll'] as $pacote) {
        expect($composer['require'])->toHaveKey($pacote);
    }

    $resolvidos = array_column([...$lock['packages'], ...($lock['packages-dev'] ?? [])], 'name');

    foreach (['spiggle/list-view', 'happenv-com/filament-saved-views'] as $pacote) {
        expect($composer['require'])->not->toHaveKey($pacote);
        expect($composer['require-dev'] ?? [])->not->toHaveKey($pacote);
        expect($resolvidos)->not->toContain($pacote);
    }
})->group('kit');

it('[CT-50] documenta critério, cinco vereditos, reabertura e efeito das três opções', function (string $idioma): void {
    $arquivo = base_path("docs/{$idioma}/recursos/visoes-de-tabela-e-carregamento.md");
    expect(is_file($arquivo))->toBeTrue();
    $pagina   = (string) file_get_contents($arquivo);
    $faltas   = [];
    $conferir = static function (bool $condicao, string $mensagem) use (&$faltas): void {
        if (! $condicao) {
            $faltas[] = $mensagem;
        }
    };

    $criterio    = '/^#{1,6}[^\n]*(?:critério|criteria|criterion)[^\n]*\n(?<texto>.*?)(?=^#{1,6}\s|\z)/msiu';
    $temCriterio = preg_match($criterio, $pagina, $secao) === 1;
    $conferir($temCriterio && preg_match('/(?:valor|value)/iu', $secao['texto'] ?? '') === 1,
        'seção com o critério de agregar valor ao kit');

    foreach ([
        'sehsah/filament-remember-table'      => 'ADOTAR|ADOPT(?:ED)?',
        'asignua/filament-infinite-scroll'    => 'ADOTAR|ADOPT(?:ED)?',
        'happenv-com/filament-phpstan-macros' => 'ADOTAR|ADOPT(?:ED)?',
        'spiggle/list-view'                   => 'RECUSAR|REJECT(?:ED)?',
        'happenv-com/filament-saved-views'    => 'RECUSAR|REJECT(?:ED)?',
    ] as $pacote => $veredito) {
        $nome = preg_quote($pacote, '/');
        $conferir(preg_match('/^[^\n]*'.$nome.'[^\n]*(?:'.$veredito.')[^\n]*$/miu', $pagina) === 1,
            "nome Composer e veredito na linha de {$pacote}");

        if (str_starts_with($veredito, 'RECUSAR')) {
            $blocos = preg_split('/(?=^#{1,6}\s)/m', $pagina) ?: [];
            $bloco  = implode("\n", array_filter($blocos, static fn (string $texto): bool => str_contains($texto, $pacote)));
            $conferir(preg_match('/(?:motivo|reason)\s*[:|—-]\s*\S/iu', $bloco) === 1,
                "motivo da recusa de {$pacote}");
            $conferir(preg_match('/(?:reabrir\s+quando|reopen\s+when)\s*[:|—-]?\s*\S/iu', $bloco) === 1,
                "condição de reabertura de {$pacote}");
        }
    }

    foreach (['KIT_TABELA_CARREGAMENTO', 'KIT_TABELA_VISOES_SALVAS', 'KIT_TABELA_VISOES_COMPARTILHADAS'] as $variavel) {
        $conferir(str_contains($pagina, $variavel), "variável {$variavel}");
    }

    $conferir(preg_match('/(?:aba\s+\*{0,2}Tabelas|\*{0,2}Tables\*{0,2}\s+tab)/iu', $pagina) === 1, 'aba Tabelas / Tables');
    $efeitos = $idioma === 'pt' ? [
        '/\|[^\n]*Carregamento da tabela[^\n]*\|[^\n]*(?:Rolagem|rolagem)[^\n]*Carregar mais/u',
        '/\|[^\n]*Visões salvas[^\n]*\|[^\n]*botão[^\n]*Visões/u',
        '/\|[^\n]*Compartilhar visões com todos[^\n]*\|[^\n]*Compartilhar com todos[^\n]*visões ligadas/u',
    ] : [
        '/\|[^\n]*Table loading[^\n]*\|[^\n]*Infinite scroll[^\n]*Load more/u',
        '/\|[^\n]*Saved views[^\n]*\|[^\n]*Views[^\n]*button/u',
        '/\|[^\n]*Share views with everyone[^\n]*\|[^\n]*Share with everyone[^\n]*views on/u',
    ];

    foreach ($efeitos as $indice => $padrao) {
        $conferir(preg_match($padrao, $pagina) === 1, 'efeito da opção '.($indice + 1));
    }

    $padroesDeFabrica = $idioma === 'pt' ? [
        '/\|[^\n]*Carregamento da tabela[^\n]*\|\s*`Paginação`\s*\|/u',
        '/\|[^\n]*Visões salvas[^\n]*\|\s*desligado\s*\|/u',
        '/\|[^\n]*Compartilhar visões com todos[^\n]*\|\s*desligado\s*\|/u',
    ] : [
        '/\|[^\n]*Table loading[^\n]*\|\s*`Pagination`\s*\|/u',
        '/\|[^\n]*Saved views[^\n]*\|\s*off\s*\|/u',
        '/\|[^\n]*Share views with everyone[^\n]*\|\s*off\s*\|/u',
    ];

    foreach ($padroesDeFabrica as $indice => $padrao) {
        $conferir(preg_match($padrao, $pagina) === 1, 'opção '.($indice + 1).' nasce desligada (contínuo = paginação)');
    }

    expect($faltas)->toBeEmpty(implode('; ', $faltas));
})->with([
    'pt' => ['pt'],
    'en' => ['en'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'A documentação não viaja no projeto instalado.')->group('kit');

it('[CT-51] publica capturas e clipe do contínuo com seus pares no tema escuro', function (): void {
    $reflexao = new ReflectionClass(KitArte::class);
    $imagens  = $reflexao->getConstant('IMAGENS');
    $clipes   = $reflexao->getConstant('CLIPES');
    $visoes   = array_values(array_filter($imagens, static fn (string $nome): bool => str_starts_with($nome, 'visoes-')));
    $continuo = array_values(array_filter($imagens, static fn (string $nome): bool => preg_match('/^(?:tabela-(?:rolagem|carregar-mais)|carregamento)/', $nome) === 1));
    $faltas   = [];

    foreach (['visões' => $visoes, 'contínuo' => $continuo] as $recurso => $capturas) {
        if ($capturas === []) {
            $faltas[] = "captura de {$recurso}";
        }

        foreach ($capturas as $nome) {
            if (! str_ends_with($nome, '-escuro') && ! in_array("{$nome}-escuro", $imagens, true)) {
                $faltas[] = "imagem declarada {$nome}-escuro";
            }

            if (! is_file(base_path("art/{$nome}.png"))) {
                $faltas[] = "art/{$nome}.png";
            }
        }
    }

    $clipesContinuos = array_filter(array_keys($clipes), static fn (string $nome): bool => ! str_ends_with($nome, '-escuro')
        && preg_match('/(?:carregamento|rolagem|carregar-mais|continuo)/', $nome) === 1);

    if ($clipesContinuos === []) {
        $faltas[] = 'clipe animado declarado do carregamento contínuo';
    }

    foreach ($clipesContinuos as $nome) {
        if (! array_key_exists("{$nome}-escuro", $clipes)) {
            $faltas[] = "clipe declarado {$nome}-escuro";
        }

        foreach ([$nome, "{$nome}-escuro"] as $clipe) {
            if (! is_file(base_path("art/{$clipe}.gif"))) {
                $faltas[] = "art/{$clipe}.gif";
            }
        }
    }

    expect($faltas)->toBeEmpty(implode('; ', $faltas));
})->skip(fn (): bool => ! naArvoreDoKit(), 'A arte não viaja no projeto instalado.')->group('kit');

it('[CT-52] traduz todas as chaves do remember-table e preserva seus marcadores', function (): void {
    $ingles    = Arr::dot(require base_path('vendor/sehsah/filament-remember-table/resources/lang/en/remember-table.php'));
    $portugues = Arr::dot(require base_path('lang/vendor/filament-remember-table/pt_BR/remember-table.php'));
    app()->setLocale('pt_BR');

    expect($ingles)->not->toBeEmpty();

    foreach ($ingles as $chave => $texto) {
        expect($portugues)->toHaveKey($chave);
        expect($portugues[$chave])->toBeString()->not->toBe('')->not->toBe($texto);
        expect(trim($portugues[$chave]))->not->toBe('');
        preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $texto, $marcadoresIngles);
        preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $portugues[$chave], $marcadoresPortugues);
        sort($marcadoresIngles[0]);
        sort($marcadoresPortugues[0]);
        expect($marcadoresPortugues[0])->toBe($marcadoresIngles[0]);
    }

    expect(__('filament-remember-table::remember-table.button.label'))
        ->toBe($portugues['button.label'])->not->toBe('Views');
})->group('kit');

it('[CT-62] resolve Filament desde 5.10 na série 5 com hook e assets correspondentes', function (): void {
    $lock   = json_decode((string) file_get_contents(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);
    $pacote = collect($lock['packages'])->firstWhere('name', 'filament/filament');
    expect($pacote)->not->toBeNull();
    $versao = ltrim($pacote['version'], 'v');

    expect(version_compare($versao, '5.10.0', '>='))->toBeTrue();
    expect(version_compare($versao, '6.0.0', '<'))->toBeTrue();
    expect(defined(TablesRenderHook::class.'::CONTENT_AFTER'))->toBeTrue();

    foreach ([
        'public/js/filament/filament/app.js'   => 'vendor/filament/filament/dist/index.js',
        'public/css/filament/filament/app.css' => 'vendor/filament/filament/dist/theme.css',
    ] as $publicado => $origem) {
        expect(is_file(base_path($origem)))->toBeTrue();
        expect(is_file(base_path($publicado)))->toBeTrue();
        expect(md5_file(base_path($publicado)))->toBe(md5_file(base_path($origem)));
    }
})->group('kit');
