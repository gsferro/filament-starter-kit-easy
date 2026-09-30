---
paths:
  - 'app/Console/Commands/**'
---

# Commands

## Chave do .env se grava por SubstituicaoEmArquivo::definirNoEnv() ou definirLinhaNoEnv(), nunca por regex própria
Um comando que grava uma chave no `.env` (como o `kit:tenancy --demo` grava `KIT_DEMO=true`) passa por `App\Support\SubstituicaoEmArquivo::definirNoEnv()` (valor citado e escapado) ou `definirLinhaNoEnv()` (linha pronta). Nunca `aplicar()` com um padrão `/^#?\s*CHAVE=/m`, nem `preg_replace`/`File::put` diretos.

Por quê: o Dotenv e a carga do Laravel ficam com a **última** definição de uma chave repetida, então trocar "só a primeira ocorrência" deixa os dois leitores com o valor velho; e o `#?` do padrão antigo trocava o comentário que vinha antes da linha ativa. `definirLinhaNoEnv()` troca toda linha ativa da chave, nenhuma comentada, descomenta a primeira comentada no lugar quando não há ativa, e anexa quando não há nenhuma. A regra completa está em `.ai/rules/support.md`.

Guardado por `tests/Kit/CustomizadorDaInstalacaoTest.php` (CT-138, CT-139 exercitam o demo do `kit:tenancy`). Origem: ADR-10 e RQ-50..RQ-53 de `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/`.
