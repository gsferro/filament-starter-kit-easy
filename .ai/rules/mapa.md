---
paths:
  - 'site/mapa/**'
  - site/verifica-mapa.mjs
  - 'tests/Kit/MapaInterativo*Test.php'
  - tests/Tenancy/MapaInterativoTenancyTest.php
---

# Mapa

## A mesma regra do mapa vive em cópias independentes — mudou uma, mudam todas
O gerador (`site/mapa/gerar.mjs`), o conferidor do site (`site/verifica-mapa.mjs`) e as guardas Pest
(`tests/Kit/MapaInterativo*Test.php`, `tests/Tenancy/MapaInterativoTenancyTest.php` e os helpers delas em
`tests/Pest.php`) implementam, cada um por conta própria, as mesmas regras do mapa: quem entra no diagrama de um
modo, a ligação que o diagrama dos dois modos desenha, a forma da condição de disponibilidade e a marca. Quem muda
uma regra numa cópia muda todas no mesmo commit, e nunca faz uma importar a outra: o conferidor e as guardas são os
oráculos do gerador, e um módulo compartilhado deixaria o mesmo defeito passar nos três. A regra "o diagrama dos dois
modos só desenha o que vale em cada modo dele" derivou três vezes entre as cópias, e em cada vez o oráculo concordou
com o defeito ou falhou aberto. Origem: AD-02, S-AD-01 e AD2-02 da wiki `wikis/specs/feat/mapa-interativo-do-kit/`.
