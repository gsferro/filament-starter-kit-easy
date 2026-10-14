# 08: Achados da revisão do diff — custo, organização excluída e aviso

**Entrega**: o favorito do painel e da organização atuais aparece sem troca de contexto nem recarga de papéis; o favorito de uma organização excluída sai quando o usuário salva e libera a vaga; clicar em "Adicionar esta página" quando ela já não pode ser adicionada mostra um aviso.
**RQ cobertas**: P-02, P-03, P-04
**CT que ficam verdes**: CT-71, CT-72, CT-73, CT-74, CT-75
**CT-B**: —
**Passos do 01 envolvidos**: — (as correções do step 9 não viraram passo do 01; ver `## Revisão do Diff (step 9)` do 03)
**Bloqueado por**: 05, 06, 07
**Bloqueia**: —
**Prefactoring**: não
**Costura**: G1 — componente Livewire/Filament; G2 — Pest feature HTTP; G4 — componente Livewire/Filament; G5 — Pest feature HTTP
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-71…75 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 124/124, `pest tests/Tenancy/DockDeAtalhosTenancyTest.php` 44/44); falsificabilidade: CT-72, 74, 75 falham sem a correção
