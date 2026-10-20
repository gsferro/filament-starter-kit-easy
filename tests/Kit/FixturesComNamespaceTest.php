<?php

use Symfony\Component\Finder\Finder;

it('[CT-01] toda classe nomeada em tests declara namespace', function (): void {
    $violacoes = [];

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

            // Só uma declaração nomeada tem T_STRING após T_CLASS; new class e ::class não têm.
            if ($token[0] === T_CLASS && is_array($proximo) && $proximo[0] === T_STRING && $namespace === '') {
                $violacoes[] = $arquivo->getRelativePathname().':'.$token[2].' '.$proximo[1];
            }
        }
    }

    expect($violacoes)->toBe([], "Classes sem namespace em tests/:\n".implode("\n", $violacoes));
});
