# 03: O usuário gerencia os favoritos no slideover

**Entrega**: O usuário abre "Gerenciar atalhos" no dock, escolhe e reordena destinos de um catálogo com as telas que ele pode abrir em qualquer painel, cada uma identificada pelo painel, e salva; envio adulterado é recusado e ninguém vê favorito de outro usuário.
**RQ cobertas**: RQ-04, RQ-10, RQ-11, RQ-12, RQ-20, RQ-29, RQ-30, RQ-31
**CT que ficam verdes**: CT-13, CT-14, CT-15, CT-16, CT-17, CT-18, CT-19, CT-20, CT-21, CT-22, CT-50
**CT-B**: —
**Passos do 01 envolvidos**: 6, 7, 8 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: 01
**Bloqueia**: 04, 05, 06
**Prefactoring**: não
**Costura**: G1 — componente Livewire/Filament; G2 — Pest feature HTTP; G4 — componente Livewire/Filament
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-13…21 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 29/29), CT-22, CT-50 verdes (Tenancy 5/5); suíte sem regressão de código contra a baseline
