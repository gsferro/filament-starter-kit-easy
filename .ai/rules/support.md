---
paths:
  - 'app/Support/**'
---

# Support

## Chave do .env se grava por SubstituicaoEmArquivo::definirNoEnv() ou definirLinhaNoEnv(), nunca por regex própria
Todo código do kit que grava uma chave no `.env` passa por `App\Support\SubstituicaoEmArquivo::definirNoEnv()` (valor citado e escapado) ou `definirLinhaNoEnv()` (linha pronta, como `DB_CONNECTION=pgsql`). Nunca `aplicar()` com um padrão `/^#?\s*CHAVE=/m`, nem `preg_replace`/`File::put` diretos.

Por quê: o Dotenv e a carga do Laravel ficam com a **última** definição de uma chave repetida, então trocar "só a primeira ocorrência" deixa os dois leitores com o valor velho; e o `#?` do padrão antigo trocava o comentário que vinha antes da linha ativa. `definirLinhaNoEnv()` troca toda linha que o Dotenv lê como a chave (`export`, espaço no `=`, indentação), nenhuma comentada, descomenta a primeira comentada no lugar quando não há ativa, e anexa quando não há nenhuma. `aplicar()` fica só para padrão arbitrário de config PHP (`'teams' => false`), uma ocorrência.

Guardado por `tests/Kit/CustomizadorDaInstalacaoTest.php` (CT-117, CT-118, CT-137..CT-139, CT-150, CT-151), que exercita os seis gravadores. Origem: ADR-10 e RQ-50..RQ-53 de `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/`.
