<?php /*
@echo off
php "%~f0" %*
exit /b %errorlevel%

pestw.cmd - lançador poliglota (batch + PHP) do Pest no Windows. Arquivo CRLF.

Por que existe: o pest --mutate relança argv[0] (vendor/bin/pest, proxy sem extensão: script sh ou
PHP, conforme a versão do Composer) por Symfony Process; o cmd não executa arquivo sem extensão, o
subprocesso sai com código 1 em ~30 ms e o plugin conta qualquer saída não-zero como mutante morto:
100 % falso (feature-wiki, references/pest-5.md). Com este lançador, argv[0] é o próprio .cmd (%~f0,
path completo), que o cmd executa. O score só vale com Duration compatível com N x tempo dos testes
cobridores e com a lista de sobreviventes.

Como funciona: o cmd lê a 1ª linha como comando inválido (ecoa a linha, reclama e segue), roda o
php sobre este mesmo arquivo com os argumentos e sai com o código dele. O PHP vê a 1ª linha como
abertura de tag + comentário e só executa o require abaixo. O Pest é procurado pelo diretório
corrente (getcwd()): rodar na raiz do projeto. Nada de path fixo; o arquivo fica em {skills}.

Uso (na raiz do projeto Laravel):
  Git Bash:    XDEBUG_MODE=coverage cmd //c "$(cygpath -w {skills}/feature-wiki/scripts/pestw.cmd)" tests/Feature/{Feature} --mutate --path=app/Models/X.php --covered-only --parallel
  PowerShell:  $env:XDEBUG_MODE='coverage'; & "{skills}\feature-wiki\scripts\pestw.cmd" tests/Feature/{Feature} --mutate --path=app/Models/X.php --covered-only --parallel
  cmd:         set XDEBUG_MODE=coverage& "{skills}\feature-wiki\scripts\pestw.cmd" tests/Feature/{Feature} --mutate --path=app/Models/X.php --covered-only --parallel
  (--path= é o filtro verificado; --class= é o fallback se a versão instalada do Pest não aceitar --path.)
  (No Git Bash, o cygpath -w é obrigatório: o cmd lê a / como início de opção — ".ai/skills/..." falha
  com "'.ai' não é reconhecido como um comando interno" — e o nome solto (pestw.cmd) não é procurado
  na pasta corrente com NoDefaultCurrentDirectoryInExePath definida, como no Bash do Claude Code.)

Quem chama: feature-wiki step 10 / Verificação Final (pest --mutate); feature-quality-gate,
dimensão K2; feature-test-design, fechamento do ciclo de mutação.
Contrato: repassa os argumentos e devolve o código de saída do Pest. Fora da raiz do projeto (sem
vendor/pestphp/pest/bin/pest no diretório corrente), o require falha e sai com 255.

Exemplo de falha (saída real, 2026-09-27; diretório sem vendor/, paths encurtados com ...):
  $ cmd //c "$(cygpath -w .ai/skills/feature-wiki/scripts/pestw.cmd)" --version
  C:\...\sem-vendor>/*0<?php
  A sintaxe do nome do arquivo, do nome do diretório ou do rótulo do volume está incorreta.
  PHP Warning:  require(C:\...\sem-vendor/vendor/pestphp/pest/bin/pest): Failed to open stream: No such file or directory in C:\...\pestw.cmd on line 42
  PHP Fatal error:  Uncaught Error: Failed opening required 'C:\...\sem-vendor/vendor/pestphp/pest/bin/pest' (include_path='.;C:\php\pear') in C:\...\pestw.cmd:42
  (exit 255. A 2ª e a 3ª linhas são o cmd ecoando e rejeitando a 1ª linha do arquivo: aparecem em
  toda execução e não afetam o código de saída. O PHP repete o Warning e o Fatal error no stdout.)
*/ require getcwd() . '/vendor/pestphp/pest/bin/pest';
