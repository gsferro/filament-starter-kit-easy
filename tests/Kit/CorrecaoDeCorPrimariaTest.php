<?php

declare(strict_types=1);

/**
 * A guarda do bloco "devolve as utilitárias `*-primary-*` às variáveis" do `kit.css`.
 *
 * O modo de falhar desta família é o de `.ai/rules/css-filament.md`: o pacote registra um CSS
 * GLOBAL com a paleta âmbar do build DELE, a tela sai com a cor errada e o HTML está byte a
 * byte correto. O bloco do kit existe para vencer essas regras por especificidade.
 *
 * O que nenhum teste cobria — e custou um defeito que viveu desde que o bloco nasceu — é a
 * pergunta óbvia: **o kit corrige a classe que o vendor realmente emite?** Ele corrigia
 * `.text-primary-400`, e o vendor emite `.dark\:text-primary-400`, com o prefixo. Duas classes
 * diferentes: a correção nunca tocou nada, e o monitor de jobs saía âmbar no tema escuro de
 * qualquer painel pintado de outra cor.
 *
 * Por isso o oráculo aqui não é "existe uma regra parecida", e sim as duas coisas que
 * determinam o resultado na tela:
 *
 *   1. o kit declara a MESMA classe, escapada como o vendor a escreve;
 *   2. o seletor do kit tem especificidade ESTRITAMENTE maior que a do vendor.
 *
 * A lista de classes é lida do `vendor/` em runtime, nunca congelada aqui — é isso que faz o
 * caso ficar vermelho num `composer update` que acrescente uma utilitária nova, antes de
 * alguém abrir a tela.
 */

/**
 * As regras de vendor que cravam uma cor primária LITERAL.
 *
 * Só `rgb(<número>` conta: `var(--primary-*)` é o pacote fazendo a coisa certa, e a regra do
 * próprio Filament não é problema nenhum.
 *
 * @return array<string, array{arquivo: string, seletor: string}> classe escapada => origem
 */
function regrasDeVendorComCorPrimariaCravada(): array
{
    $encontradas = [];

    $folhas = array_merge(
        glob(base_path('vendor/*/*/resources/dist/*.css')) ?: [],
        glob(base_path('vendor/*/*/dist/*.css')) ?: [],
    );

    foreach ($folhas as $folha) {
        preg_match_all(
            '~([^{};,]*\.((?:dark\\\\:)?(?:text|bg|border|ring|from|to|via)-primary-\d{2,3})[^{]*)\{[^}]*rgb\(\d~',
            (string) file_get_contents($folha),
            $achados,
            PREG_SET_ORDER,
        );

        foreach ($achados as [, $seletor, $classe]) {
            $encontradas[$classe] = [
                'arquivo' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $folha),
                'seletor' => trim($seletor),
            ];
        }
    }

    return $encontradas;
}

/** O `kit.css` sem comentário — citar um seletor não é declará-lo (`.ai/rules/testes.md`). */
function correcoesDeCorDoKit(): string
{
    return (string) preg_replace(
        '~/\*.*?\*/~s',
        '',
        (string) file_get_contents(resource_path('css/filament/kit.css')),
    );
}

/**
 * A especificidade de um seletor, no formato (id, classe, elemento).
 *
 * Cobre o que aparece nestas folhas: classe, id, pseudo-classe, atributo, elemento e os
 * funcionais `:is()`/`:where()`/`:not()`. A regra que importa aqui, e que é a origem do
 * defeito A1 desta mesma família: **`:where()` contribui ZERO**, enquanto `:is()` e `:not()`
 * contribuem o MAIOR dos argumentos.
 *
 * @return array{0: int, 1: int, 2: int}
 */
function especificidadeDe(string $seletor): array
{
    $seletor = trim($seletor);
    $soma    = [0, 0, 0];

    // Os funcionais primeiro: o corpo deles não pode ser contado como se fosse texto solto.
    while (preg_match('~:(is|where|not)\(~i', $seletor, $m, PREG_OFFSET_CAPTURE)) {
        $inicio = (int) $m[0][1];
        $abre   = $inicio + strlen($m[0][0]) - 1;
        $nivel  = 0;
        $fecha  = $abre;

        for ($i = $abre, $n = strlen($seletor); $i < $n; $i++) {
            $nivel += ($seletor[$i] === '(' ? 1 : ($seletor[$i] === ')' ? -1 : 0));

            if ($nivel === 0) {
                $fecha = $i;

                break;
            }
        }

        $funcao    = strtolower((string) $m[1][0]);
        $argumento = substr($seletor, $abre + 1, $fecha - $abre - 1);
        $maior     = [0, 0, 0];

        if ($funcao !== 'where') {
            foreach (explode(',', $argumento) as $ramo) {
                $e = especificidadeDe($ramo);

                if ($e > $maior) {
                    $maior = $e;
                }
            }
        }

        foreach ([0, 1, 2] as $i) {
            $soma[$i] += $maior[$i];
        }

        $seletor = substr($seletor, 0, $inicio).' '.substr($seletor, $fecha + 1);
    }

    // `\:` é dois-pontos ESCAPADO — parte do nome da classe, não início de pseudo-classe.
    $marcado = str_replace('\\:', "\x00", $seletor);

    $soma[0] += preg_match_all('~#[\w-]+~', $marcado);
    $soma[1] += preg_match_all('~\.[\w\x00.-]+~', $marcado)
        + preg_match_all('~\[[^]]+]~', $marcado)
        + preg_match_all('~(?<![:\w-]):(?!:)[\w-]+~', $marcado);
    $soma[2] += preg_match_all('~(?<![\w\x00.#\[-])[a-z][\w-]*~i', preg_replace('~\.[\w\x00.-]+|#[\w-]+|\[[^]]+]|::?[\w-]+~', ' ', $marcado) ?? '');

    return $soma;
}

it('conhece a especificidade das formas que decidem este arquivo', function (): void {
    // Controle positivo do detector: sem isto, um cálculo quebrado devolveria (0,0,0)
    // para tudo e "o kit vence" ficaria verde sobre nada.
    expect(especificidadeDe('.text-primary-600'))->toBe([0, 1, 0])
        ->and(especificidadeDe(':root .text-primary-600'))->toBe([0, 2, 0])
        ->and(especificidadeDe('.dark\:text-primary-400:is(.dark *)'))->toBe([0, 2, 0])
        ->and(especificidadeDe(':root .dark\:text-primary-400:is(.dark *)'))->toBe([0, 3, 0])
        // O caso que originou tudo: `:where()` vale ZERO.
        ->and(especificidadeDe('.fi-topbar-item-label:where(.dark, .dark *)'))->toBe([0, 1, 0]);
});

it('encontra as regras de vendor que cravam cor primaria', function (): void {
    // Sem controle positivo, uma regex quebrada devolve lista vazia e todo `foreach`
    // abaixo fica verde sem exercitar nada.
    $regras = regrasDeVendorComCorPrimariaCravada();

    expect($regras)->not->toBeEmpty(
        'nenhum vendor crava cor primária — ou a varredura quebrou, ou o bloco do kit.css pode sair.',
    )->and($regras)->toHaveKey('text-primary-600');
});

it('corrige a MESMA classe que o vendor emite, e nao uma parecida', function (): void {
    $kit = correcoesDeCorDoKit();

    foreach (regrasDeVendorComCorPrimariaCravada() as $classe => $origem) {
        // A classe vem do vendor JÁ escapada (`dark\:text-primary-400`) — escapar de novo
        // produz `dark\\:`, que não existe em folha nenhuma e deixa o caso vermelho por
        // motivo errado. Aconteceu na segunda execução deste arquivo.
        $escapada = $classe;

        /*
         * `toContain()` recebe VÁRIOS needles, não uma mensagem — passar a explicação como
         * segundo argumento a transforma numa segunda busca, e o caso fica vermelho pelo
         * motivo errado. Foi o que aconteceu na primeira execução deste arquivo.
         */
        expect(str_contains($kit, '.'.$escapada))->toBeTrue(
            "o kit não declara `.{$escapada}`, que {$origem['arquivo']} crava com cor literal. "
            .'Declarar uma classe PARECIDA não corrige nada: `.text-primary-400` e '
            .'`.dark\\:text-primary-400` são classes diferentes, e foi assim que o tema escuro do '
            .'monitor de jobs ficou âmbar em painel de outra cor.',
        );
    }
});

it('vence o vendor por especificidade, em toda classe cravada', function (): void {
    $kit = correcoesDeCorDoKit();

    foreach (regrasDeVendorComCorPrimariaCravada() as $classe => $origem) {
        $escapada = preg_quote($classe, '~');

        preg_match_all('~([^{};,\n]*\.'.$escapada.'(?![\w-])[^{;,\n]*)\{~', $kit, $achados);

        $doVendor = especificidadeDe($origem['seletor']);
        $vencem   = array_filter(
            $achados[1] ?? [],
            static fn (string $seletor): bool => especificidadeDe($seletor) > $doVendor,
        );

        expect($vencem)->not->toBeEmpty(
            "nenhuma regra do kit para `.{$escapada}` vence a do vendor.\n"
            ."  vendor : {$origem['seletor']}  →  (".implode(',', $doVendor).")\n"
            .'  kit    : '.(($achados[1] ?? []) === [] ? '(nenhuma)' : implode(' | ', $achados[1])),
        );
    }
});

/**
 * O seletor escuro do kit precisa ser `.dark:root`, nunca `.dark :root` nem `:root .dark`.
 *
 * `:root` É o `<html>`, e o alternador de tema do Filament escreve `dark` nele próprio: as duas
 * outras formas pedem que a raiz seja descendente de algo, ou que contenha a classe que está
 * nela mesma. Nunca casam. As duas viveram neste arquivo, e é por isso que este caso existe.
 */
it('nao deixa entrar seletor escuro que nunca casa', function (): void {
    $mortos = [];

    if (preg_match_all('~\.dark\s+:root[^{,]*~', correcoesDeCorDoKit(), $a)) {
        $mortos = array_merge($mortos, $a[0]);
    }

    /*
     * A barra invertida na exclusão é o que separa `:root .dark …` (descendente, morto) de
     * `:root .dark\:text-primary-400` (nome de classe escapado, vivo). Sem ela o caso acusa
     * como morta justamente a regra que corrige o defeito.
     */
    if (preg_match_all('~:root\s+\.dark(?![\w:\\\\-])[^{,]*~', correcoesDeCorDoKit(), $a)) {
        $mortos = array_merge($mortos, $a[0]);
    }

    expect($mortos)->toBeEmpty(
        "seletor que nunca casa no kit.css — `:root` é o `<html>`:\n  ".implode("\n  ", $mortos)
        ."\nA forma viva é `.dark:root`, com a classe na PRÓPRIA raiz.",
    );
});
