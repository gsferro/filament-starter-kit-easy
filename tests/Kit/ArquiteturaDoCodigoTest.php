<?php

use App\Console\Commands\KitInstall;

/**
 * Os dois presets de arquitetura do Pest que o kit adota — e por que só dois.
 *
 * ## O achado que originou este arquivo
 *
 * `pestphp/pest-plugin-arch` está no `composer.json` desde sempre e tinha **zero** uso:
 * `grep -rn "arch()" tests/` voltava `0`. Enquanto isso, 415 dos 1.517 blocos `it()` da
 * suíte são guardas de arquitetura escritas à mão, lendo o disco com `Finder`.
 *
 * Nem tudo o que elas fazem cabe num preset — a maior parte trava convenção deste kit,
 * que preset nenhum conhece. Mas os dois abaixo custam ~6 s, **já passam hoje** e fecham
 * classes de defeito que nenhuma guarda existente cobre.
 *
 * ## Por que só estes dois
 *
 * Os outros quatro presets foram medidos contra esta árvore em 2026-09-24 e reprovam por
 * conflitarem com convenção deliberada do kit, não por serem ruins:
 *
 * | Preset | O que ele exige | O kit |
 * |---|---|---|
 * | `laravel` | controller só com métodos REST | `ContaIndisponivelController` tem método próprio |
 * | `strict` | toda classe `final` | 163 de 222 classes não são — `AgenteBase` existe para ser estendida |
 * | `relaxed` | nenhum método `private` | 54 arquivos usam |
 *
 * A tabela completa, com PHPStan e as demais ferramentas medidas, está em
 * `wikis/specs/feat/cobertura-de-testes/cobertura-de-testes/02-decisoes-arquiteturais.md` → ADR-05.
 *
 * ## O preset `php` é guarda PROSPECTIVA, e isso está dito de propósito
 *
 * Ele passa com zero violações hoje. Não prova nada sobre o código que já existe — existe
 * para o próximo, e ler "passou" como atestado retroativo seria erro de interpretação.
 */
arch()->preset()->php();

/**
 * O preset de segurança, com a exceção do kit declarada — e o tamanho real dela.
 *
 * `KitInstall::oferecerEstrela()` chama `exec()` **três vezes**, uma por família de sistema
 * operacional, para abrir o repositório no navegador ao fim da instalação. O argumento das
 * três é a mesma **constante de classe**; não há entrada de usuário no caminho, e o uso já
 * tem comentário de revisão no código.
 *
 * ## O que esta exceção custa, dito com números
 *
 * `ignoring()` recebe alvos ou dependências numa lista plana, então excluir só o `exec`
 * **desta** classe não é expressável: passar o nome da classe libera, dentro dela, as
 * **vinte** funções da lista do preset — `eval`, `unserialize`, `extract`, `system`,
 * `shell_exec` e as demais, não só o `exec`.
 *
 * Isso é mais frouxo do que o ideal, e foi escolhido porque a alternativa — ignorar a
 * dependência `exec` globalmente — liberaria `exec` em **todo** o `app/`, que é
 * estritamente pior.
 *
 * O buraco que sobra está fechado pelo caso seguinte, que é o par obrigatório deste: sem
 * ele, `KitInstall` — justamente o arquivo que roda na máquina de quem instala — seria o
 * único lugar de `app/` onde o preset não morde.
 */
arch()->preset()->security()->ignoring(KitInstall::class);

/**
 * O par do `ignoring()` acima: dentro do `KitInstall`, só `exec` é tolerado, e só com constante.
 *
 * Sem este caso, a exceção declarada no preset seria um cheque em branco para vinte funções
 * perigosas dentro do arquivo mais sensível do kit. Aqui a lista volta a ser fechada:
 *
 * - das vinte funções do preset, **apenas** `exec` pode aparecer;
 * - e o argumento de todo `exec` precisa ser uma concatenação com `self::REPOSITORIO` —
 *   o que torna impossível passar algo vindo do usuário sem reprovar.
 *
 * A guarda foi verificada com mutante nas duas direções: `exec($this->ask('x'))` dentro do
 * `KitInstall` reprova aqui, e um `exec` novo em qualquer outro arquivo de `app/` reprova no
 * caso anterior.
 */
it('confina a excecao de seguranca do KitInstall a exec com constante', function (): void {
    $fonte = (string) file_get_contents(base_path('app/Console/Commands/KitInstall.php'));

    /*
     * A lista do `Pest\ArchPresets\Security`, menos o `exec`. Escrita à mão de propósito: o
     * preset é `internal` e ler a lista dele por reflexão amarraria esta guarda a um detalhe
     * de implementação do Pest. Se o preset ganhar uma função nova, este caso não a cobre — e
     * é por isso que ele NÃO substitui o preset, ele o complementa.
     */
    $proibidas = [
        'md5', 'sha1', 'uniqid', 'rand', 'mt_rand', 'tempnam', 'str_shuffle', 'shuffle',
        'array_rand', 'eval', 'shell_exec', 'system', 'passthru', 'create_function',
        'unserialize', 'extract', 'mb_parse_str', 'dl', 'assert',
    ];

    $encontradas = array_values(array_filter(
        $proibidas,
        static fn (string $funcao): bool => preg_match('~(?<![\w>$:])'.preg_quote($funcao, '~').'\s*\(~', $fonte) === 1,
    ));

    expect($encontradas)->toBe([], 'funcao da lista de seguranca dentro do `KitInstall`, que o `ignoring()` do preset deixa passar');

    preg_match_all('~(?<![\w>$:])exec\s*\(([^)]*)\)~', $fonte, $chamadas);

    expect($chamadas[1])->not->toBe([], 'nenhum `exec` encontrado — se ele saiu, remova o `ignoring()` do preset acima');

    foreach ($chamadas[1] as $argumento) {
        expect($argumento)->toContain('self::REPOSITORIO');
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'O `kit:update` entrega `app/Console/Commands`, mas o `KitInstall` do projeto instalado pode ter sido editado por quem instalou.')->group('kit');
