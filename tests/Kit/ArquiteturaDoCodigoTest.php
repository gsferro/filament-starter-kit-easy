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
 */
arch()->preset()->php();

/**
 * O preset de segurança, com a única exceção do kit declarada — e o motivo dela.
 *
 * `KitInstall::oferecerEstrela()` chama `exec('open '.self::REPOSITORIO)` para abrir o
 * repositório no navegador ao fim da instalação. O argumento é uma **constante de classe**;
 * não há entrada de usuário no caminho, e o uso já tem comentário de revisão no código.
 *
 * ## O que esta exceção custa, dito com honestidade
 *
 * `ignoring()` recebe alvos ou dependências numa lista plana, então excluir só o `exec`
 * **desta** classe não é expressável: passar o nome da classe libera as 20 funções da lista
 * dentro dela. É mais frouxo do que o ideal.
 *
 * Foi escolhido mesmo assim porque a alternativa — ignorar a dependência `exec`
 * globalmente — liberaria `exec` em **todo** o `app/`, que é estritamente pior. E
 * `KitInstall` é o arquivo mais revisado do kit: é o que roda na máquina de quem instala.
 *
 * O ganho é a inversão: hoje qualquer `exec` novo passa em silêncio; a partir daqui, um
 * `exec` em qualquer outro lugar de `app/` reprova.
 */
arch()->preset()->security()->ignoring(KitInstall::class);
