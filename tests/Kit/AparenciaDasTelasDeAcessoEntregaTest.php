<?php

/**
 * Entrega documental da aparência das telas de acesso (CT-23, CT-24). Lê docs/ e README: só existe
 * na árvore do kit (`export-ignore`), por isso cada caso pula fora dela.
 */
dataset('canais da aparencia', [
    'documentação pt' => ['docs/pt/autenticacao/aparencia-das-telas.md', 'pt'],
    'documentação en' => ['docs/en/autenticacao/aparencia-das-telas.md', 'en'],
    'README pt'       => ['README.md', 'pt'],
    'README en'       => ['README.en.md', 'en'],
]);

it('[CT-23] cada canal permite localizar o contrato completo', function (string $arquivo, string $idioma): void {
    $texto = mb_strtolower((string) file_get_contents(base_path($arquivo)));

    $topicos = $idioma === 'pt'
        ? [
            'layouts'                => ['layout', 'clássico'],
            'campos e limites'       => ['80', '160'],
            'preview'                => ['pré-visualiza'],
            'padrão versus própria'  => ['padrão', 'própria'],
            'independência da cópia' => ['cópia'],
            'descarte ao voltar'     => ['descart'],
            'instalação existente'   => ['instalaç'],
        ]
        : [
            'layouts'                => ['layout', 'classic'],
            'fields and limits'      => ['80', '160'],
            'preview'                => ['preview'],
            'default versus own'     => ['default', 'own'],
            'copy independence'      => ['copy'],
            'discard on return'      => ['discard'],
            'existing installation'  => ['updat'],
        ];

    foreach ($topicos as $topico => $termos) {
        foreach ($termos as $termo) {
            $this->assertStringContainsString($termo, $texto, "{$arquivo} não localiza: {$topico} ({$termo})");
        }
    }

    if (str_starts_with($arquivo, 'README')) {
        // O README resume e leva ao detalhe por link navegável (relativo existente ou o da página publicada).
        preg_match_all('/\]\(([^)\s]*aparencia-das-telas[^)\s]*)\)/', $texto, $links);
        $achou = collect($links[1])->contains(fn (string $l): bool => str_starts_with($l, 'https://gsferro.github.io/') || is_file(base_path(preg_replace('~^\./~', '', explode('#', $l)[0]))));
        expect($achou)->toBeTrue("{$arquivo} não tem link navegável para o detalhe da aparência");
    }
})->with('canais da aparencia')
    ->skip(fn (): bool => ! naArvoreDoKit(), 'a documentação e o README só existem na árvore do kit')
    ->group('kit');

it('[CT-24] links externos respeitam a lista permitida', function (): void {
    $arquivos = [
        'docs/pt/autenticacao/aparencia-das-telas.md', 'docs/en/autenticacao/aparencia-das-telas.md',
        'README.md', 'README.en.md', 'docs/pt/recursos/configuracoes-do-kit.md', 'docs/en/recursos/configuracoes-do-kit.md',
        'docs/pt/recursos/multi-tenancy.md', 'docs/en/recursos/multi-tenancy.md', 'site/sidebar.json', 'site/redirects.json',
    ];

    $fora = [];

    foreach ($arquivos as $arquivo) {
        $conteudo = (string) file_get_contents(base_path($arquivo));

        // README e docs de recursos têm links antigos: o corpus novo é a linha que fala da aparência.
        if (! str_contains($arquivo, 'aparencia-das-telas') && str_ends_with($arquivo, '.md')) {
            $conteudo = implode('
', array_filter(explode('
', $conteudo), fn (string $l): bool => preg_match('/apar[eê]ncia|appearance|telas de acesso|sign-in screens/iu', $l) === 1));
        }

        preg_match_all('~(?:https?:)?//[^\s)"\'<>\]]+~i', $conteudo, $achados);

        foreach ($achados[0] as $url) {
            $url      = str_starts_with($url, '//') ? 'https:'.$url : $url;
            $partes   = parse_url($url);
            $host     = strtolower((string) ($partes['host'] ?? ''));
            $primeiro = explode('/', ltrim((string) ($partes['path'] ?? ''), '/'))[0];

            $permitido = ($host === 'gsferro.github.io' && ! isset($partes['user']))
                || (in_array($host, ['github.com', 'raw.githubusercontent.com'], true) && strtolower($primeiro) === 'gsferro' && ! isset($partes['user']));

            if (! $permitido) {
                $fora[] = "{$arquivo}: {$url}";
            }
        }
    }

    expect($fora)->toBe([]);
})->skip(fn (): bool => ! naArvoreDoKit(), 'documentação, README e site só existem na árvore do kit')
    ->group('kit');
