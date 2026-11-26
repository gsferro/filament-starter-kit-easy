---
paths:
  - 'app/**'
  - composer.json
  - package.json
  - 'routes/**'
  - docs/pt/operacao/roteiro-de-features.md
  - docs/en/operacao/roteiro-de-features.md
---

# Catálogo de features

Vale no repositório do kit, onde existe `docs/pt/operacao/roteiro-de-features.md`. Em projeto derivado do
`composer create-project` a pasta `docs/` não chega (`export-ignore` no `.gitattributes`), e esta rule não se
aplica.

## O roteiro de features é o catálogo único — feature nova entra nele, nos dois idiomas
O "Roteiro de features" (`docs/pt/operacao/roteiro-de-features.md` e `docs/en/operacao/roteiro-de-features.md`) é o
lugar único das features do kit: o README, as páginas do site, o mapa interativo, os testes e o CHANGELOG citam os
números dele, e nenhum segundo catálogo nasce ao lado. Quem cria um recurso, uma página, um widget, um plugin, um
comando ou uma rota, ou instala um pacote que entrega algo a quem usa o kit, acrescenta a linha da feature nos dois
idiomas, com a coluna Origem (o pacote pelo nome do Composer ou do npm, código próprio, ou pacote com customização
própria) e o link da página do site que a descreve, quando ela existir. O catálogo já nasceu para isso em agosto de
2026 e defasou cerca de cinquenta features por falta desta obrigação. Origem: RQ-21 a RQ-23 da wiki
`wikis/specs/feat/mapa-interativo-do-kit/`.

## O número só cresce, e nunca muda de dono
Feature nova recebe o próximo número livre: o maior F-nn mais um. É proibido trocar o número de uma feature,
reaproveitar o número de outra ou remover um número sem registro: a feature que sai continua na tabela, marcada como
removida e com o motivo. Os números nomeiam testes, wikis, entradas do CHANGELOG e peças do mapa, e um número
reaproveitado já causou colisão.

## A peça nova entra no mapa com as F-nn que entrega
O mapa interativo do site sai do modelo em `site/mapa/pecas.json` e `site/mapa/ligacoes.json`. A peça que um painel
registra e a classe nova de `app/` fora de `app/Filament` entram no modelo com as F-nn que entregam, ou na lista das
peças sem feature própria, com o motivo — a guarda do mapa reprova a que fica de fora. Ligação nova traz a prova
como âncora de trecho (`{arquivo, trecho}`), nunca como número de linha.
