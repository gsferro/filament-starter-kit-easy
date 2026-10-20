<?php

use Symfony\Component\Finder\Finder;

/*
 * Issue #170: classe declarada em `tests/` fora do PSR-4 (`Tests\` => `tests/`) vira aviso
 * `does not comply with psr-4` no `composer dump-autoload --optimize` de quem instala.
 */

/**
 * As declarações nomeadas (classe, interface, trait, enum) de cada arquivo de `tests/`, com o
 * namespace em que cada uma está.
 *
 * @return list<array{arquivo: string, linha: int, nome: string, namespace: string}>
 */
function declaracoesNomeadasEmTests(): array
{
    $declaracoes = [];

    foreach (Finder::create()->files()->in(base_path('tests'))->name('*.php') as $arquivo) {
        $tokens = array_values(array_filter(
            token_get_all($arquivo->getContents()),
            static fn (array|string $token): bool => ! is_array($token)
                || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));
        $namespace               = '';
        $profundidade            = 0;
        $profundidadeDoNamespace = null;

        foreach ($tokens as $indice => $token) {
            if ($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
                $profundidade++;
            } elseif ($token === '}') {
                if ($profundidade === $profundidadeDoNamespace) {
                    $namespace               = '';
                    $profundidadeDoNamespace = null;
                }

                $profundidade--;
            }

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = '';
                $fim       = $indice + 1;

                while (is_array($tokens[$fim])) {
                    $namespace .= $tokens[$fim][1];
                    $fim++;
                }

                $profundidadeDoNamespace = $tokens[$fim] === '{' ? $profundidade + 1 : null;
            }

            $proximo = $tokens[$indice + 1] ?? null;

            // Só uma declaração nomeada tem T_STRING logo depois; `new class` e `::class` não têm.
            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true) && is_array($proximo) && $proximo[0] === T_STRING) {
                $declaracoes[] = ['arquivo' => $arquivo->getRelativePathname(), 'linha' => $token[2], 'nome' => $proximo[1], 'namespace' => $namespace];
            }
        }
    }

    return $declaracoes;
}

it('[CT-01] toda classe, interface, trait e enum nomeado em tests declara namespace', function (): void {
    $violacoes = array_map(
        static fn (array $d): string => "{$d['arquivo']}:{$d['linha']} {$d['nome']}",
        array_values(array_filter(declaracoesNomeadasEmTests(), static fn (array $d): bool => $d['namespace'] === '')),
    );

    expect($violacoes)->toBe([], "Declarações sem namespace em tests/:\n".implode("\n", $violacoes));
});

it('[CT-02] a declaração fora de arquivo *Test.php casa com o PSR-4: namespace = pasta, nome = arquivo', function (): void {
    $violacoes = [];

    foreach (declaracoesNomeadasEmTests() as $d) {
        if (str_ends_with($d['arquivo'], 'Test.php') || $d['namespace'] === '') {
            continue;
        }

        $caminho  = str_replace('/', '\\', str_replace('\\', '/', $d['arquivo']));
        $pasta    = dirname($caminho);
        $esperado = 'Tests'.($pasta === '.' ? '' : '\\'.$pasta);

        if ($d['namespace'] !== $esperado || $d['nome'] !== pathinfo($d['arquivo'], PATHINFO_FILENAME)) {
            $violacoes[] = "{$d['arquivo']}:{$d['linha']} {$d['namespace']}\\{$d['nome']} (esperado {$esperado}\\".pathinfo($d['arquivo'], PATHINFO_FILENAME).')';
        }
    }

    expect($violacoes)->toBe([], "Fora do PSR-4 (Tests\\ => tests/):\n".implode("\n", $violacoes));
});
