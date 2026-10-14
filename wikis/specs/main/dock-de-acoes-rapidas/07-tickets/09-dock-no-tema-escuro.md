# 09: O dock acompanha o tema escuro do painel

**Entrega**: com o painel em modo escuro — pelo navegador ou escolhido no próprio painel —, a superfície do dock e os itens usam as cores escuras; no modo claro, as claras.
**RQ cobertas**: P-05
**CT que ficam verdes**: — (prova por navegador, no CT-B)
**CT-B**: CT-B02
**Passos do 01 envolvidos**: — (o pacote já traz a variante escura; a premissa nasceu da dimensão G do quality gate, sem passo novo no 01)
**Bloqueado por**: 01
**Bloqueia**: —
**Prefactoring**: não
**Costura**: G7 — browser
**Status**: concluído — 2026-10-14, aprovado por gsferro (pedido do solicitante: capturas e prova em modo escuro); CT-B02 3/3 verde (`pest tests/Browser/DockDeAtalhosTest.php` 4/4 na repetição; luminância escuro 0,009, claro 1,0, painel escuro com navegador claro 0,009)
