:<?php /*
@echo off
rem A linha 1 e um ROTULO para o cmd (ele ignora tudo que comeca com ":") e a ABERTURA
rem de um comentario para o PHP. Sem o ":", o cmd le o "<" como redirecionamento de
rem entrada a partir de um arquivo chamado "?php" e morre com
rem "A sintaxe do nome do arquivo ... esta incorreta".
rem PAO_DISABLE: ver "O printer, e por que ele precisa desta linha" abaixo.
set PAO_DISABLE=1
php -d pcov.enabled=1 "%~f0" %*
exit /b %errorlevel%
*/

/*
|--------------------------------------------------------------------------
| Lancador poliglota do Pest para Windows - existe por causa do --mutate
|--------------------------------------------------------------------------
|
| Este arquivo e valido em DOIS interpretadores ao mesmo tempo: o `cmd` le o bloco
| entre `@echo off` e `exit /b`, e o PHP le tudo ate aqui como comentario.
|
| ## Por que ele precisa existir
|
| `pest --mutate` relanca a suite uma vez por mutante, e monta o comando a partir de
| `$_SERVER['argv']` -- confira em `vendor/pestphp/pest/bin/pest:25` e em
| `vendor/pestphp/pest-plugin-mutate/src/MutationTest.php:91`. O `argv[0]` e, portanto,
| exatamente o que foi digitado.
|
| Digitando `php vendor/bin/pest`, o `argv[0]` vira `vendor/bin/pest`: um script `sh`,
| que o `cmd` NAO executa. O subprocesso sai com codigo 1 em ~30 ms, e o plugin conta
| QUALQUER saida nao-zero como "mutante morto".
|
| O sintoma e um score perfeito e instantaneo -- "206 mutantes, 100%, 3 s" para uma
| suite que leva minutos. O numero e inteiramente falso, e ja enganou um juiz cego do
| quality gate desta esteira ("2 mutantes, 100%").
|
| Rodando por AQUI, o `argv[0]` passa a ser este `.cmd`, que o `cmd` sabe executar, e o
| mutante roda de verdade. O `-d pcov.enabled=1` da linha do `cmd` viaja junto para cada
| relancamento -- sem ele o plugin recusa com
| "Mutation testing requires code coverage to be enabled".
|
| ## Como usar -- e por que o `--no-tia` NAO e opcional
|
|   cmd /c pestw.cmd tests/Kit/AlgumTest.php --mutate --path=app/Support/X.php --covered-only --no-tia
|
| O `--no-tia` e obrigatorio, e a razao e sutil. Este projeto liga o TIA por padrao em
| `tests/Pest.php:149` (`pest()->tia()->defaultBranch('main')->locally()`), e com o TIA ligado o
| `PcovRestarter` (`vendor/pestphp/pest/src/Restarters/PcovRestarter.php:44`) relanca o PHP para
| ajustar o `pcov.directory`. O comando que ele monta e
|
|   [PHP_BINARY, '-d', 'pcov.directory=<raiz>', ...$arguments]
|
| -- ou seja, ele reconstroi a linha do zero e o `-d pcov.enabled=1` que este lancador passa
| **se perde**. O sintoma e enganoso: o `--mutate` morre com
| "Mutation testing requires code coverage to be enabled" mesmo com o PCOV instalado,
| habilitado e comprovadamente disponivel (`Pest\Support\Coverage::isAvailable()` devolve
| `true` se testado na mao, o que manda a investigacao para o lado errado).
|
| Nao ha perda: TIA e mutacao nao se combinam de qualquer forma -- um escolhe quais testes
| rodar pelo diff, o outro precisa rodar todos os cobridores de cada mutante.
|
| Do Git Bash, com caminho absoluto entre aspas:
|
|   cmd //c "C:\...\starter-kit-easy\pestw.cmd tests/... --mutate --path=... --no-tia"
|
| ## Como ler o resultado
|
| Score so vale com `Duration` compativel com N x o tempo dos testes cobridores, e com a
| lista de sobreviventes impressa. Score alto e instantaneo e sintoma, nao resultado.
|
| Verificado em 2026-09-24, e e esta a evidencia de que o lancador funciona:
| `app/Support/CustomizadorDaInstalacao.php` devolveu **225 mutantes, 4 nao testados,
| score 98,22%, 43,95 s**. Um score ABAIXO de 100% com duracao plausivel e a prova de que os
| mutantes rodaram de verdade -- pelo caminho falso, tudo morre e tudo da 100%.
|
| ## O printer, e por que ele precisa da linha `set PAO_DISABLE=1`
|
| O `laravel/pao` entra pelo autoload do Composer e, quando detecta um agente de IA pela
| variavel `AI_AGENT`, faz duas coisas em `vendor/laravel/pao/src/Autoload.php`: apaga o
| `$_SERVER['COLLISION_PRINTER']` (linha 31) e escolhe um driver por
| `basename($argv[0])` (`Execution.php:47-55`), que so casa com os nomes exatos `pest`,
| `paratest`, `phpunit`, `phpstan` e `rector`.
|
| Rodando por aqui, o `argv[0]` e `pestw.cmd` de proposito -- e exatamente por isso NENHUM
| driver casa. Resultado sem a linha: o Collision ja foi desligado, o Pao nao assume o lugar
| dele, e a saida cai no printer cru do PHPUnit. O `--compact` some junto, porque ele vira
| `COLLISION_PRINTER_COMPACT` e nao ha mais Collision para ler.
|
| `PAO_DISABLE=1` faz o Pao sair antes de apagar qualquer coisa, e o Pest volta a imprimir
| como imprime para um humano.
|
| **O que se perde**: a saida JSON de agente (`{"tool":"pest",...}`) nao sai por este
| lancador, em nenhuma configuracao -- ela depende do `basename($argv[0]) === 'pest'`, que e
| justamente o que precisa ser outra coisa para o `--mutate` funcionar. Para ler o score, ler
| a linha `Score:` da saida normal.
|
| ## Duas pegadinhas conhecidas
|
| - A saida comeca com um ":" solto: e a linha 1 sendo impressa pelo PHP, que nao tem como
|   ignorar texto fora de tag. E o preco do rotulo que o `cmd` exige, e nao ha como suprimi-lo
|   -- o PHP imprime antes de executar qualquer codigo que pudesse bufferizar. Consequencia
|   pratica: a saida deste lancador **nao e consumivel por parser**; nao canalize para `jq`.
| - Em Linux e macOS este arquivo e desnecessario: `vendor/bin/pest` ja e executavel e o
|   relancamento funciona.
*/

require __DIR__.'/vendor/pestphp/pest/bin/pest';
