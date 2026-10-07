---
paths:
  - 'app/Console/Commands/**'
  - app/Console/Commands/KitUpdate.php
---

# Commands

## Chave do .env se grava por SubstituicaoEmArquivo::definirNoEnv() ou definirLinhaNoEnv(), nunca por regex própria
Um comando que grava uma chave no `.env` (como o `kit:tenancy --demo` grava `KIT_DEMO=true`) passa por `App\Support\SubstituicaoEmArquivo::definirNoEnv()` (valor citado e escapado) ou `definirLinhaNoEnv()` (linha pronta). Nunca `aplicar()` com um padrão `/^#?\s*CHAVE=/m`, nem `preg_replace`/`File::put` diretos.

Por quê: o Dotenv e a carga do Laravel ficam com a **última** definição de uma chave repetida, então trocar "só a primeira ocorrência" deixa os dois leitores com o valor velho; e o `#?` do padrão antigo trocava o comentário que vinha antes da linha ativa. `definirLinhaNoEnv()` troca toda linha ativa da chave, nenhuma comentada, descomenta a primeira comentada no lugar quando não há ativa, e anexa quando não há nenhuma. A regra completa está em `.ai/rules/support.md`.

Guardado por `tests/Kit/CustomizadorDaInstalacaoTest.php` (CT-138, CT-139 exercitam o demo do `kit:tenancy`). Origem: ADR-10 e RQ-50..RQ-53 de `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/`.

## kit:update roda a classe instalada: correção de entrega que precise valer já vai nos dados, e caminho novo na lista não entrega arquivo que não mudou desde a origem
A primeira rodada do `kit:update` num projeto executa a classe ANTIGA (a instalada): qualquer mudança neste comando só vale a partir da rodada seguinte. O diff é tag de origem → tag de destino dentro de `CAMINHOS_DO_KIT`, então acrescentar um caminho à lista NÃO entrega arquivo que não mudou entre as duas tags — quem já está na versão em que o arquivo nasceu nunca o recebe, e o aviso "rode de novo com --from" só dispara quando a lista do destino não pôde ser lida. Logo: arquivo que precisa chegar nesta release tem de MUDAR entre as tags (na v0.45.1 as dez views autorais ganharam uma linha de comentário Blade por isso). Desde a v0.45.1, `caminhosNovosNaLista()` compara com a árvore do projeto todo caminho que entrou na lista depois da origem, e `rotularDiff()` rotula "novo no kit" o arquivo com status M que o projeto não tem (é o que `--only-new` aplica) — mas só com a classe nova já instalada. Medido 4 vezes antes (0.9.8, 0.23.0, 0.22.3→0.24.1, v0.43.0). Issue #148; `wikis/specs/fix/kit-update-views-vendor/`.
