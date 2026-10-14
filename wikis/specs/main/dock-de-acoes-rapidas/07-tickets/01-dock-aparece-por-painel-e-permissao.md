# 01: Quem tem Usar:Dock vê o dock nos painéis ligados

**Entrega**: Quem tem a permissão Usar:Dock vê o dock nos painéis que o administrador ligou no settings, com os favoritos gravados como links; quem não tem a permissão, ou está num painel desligado, não vê o dock; instalação nova concede a permissão aos papéis de administração e não ao usuário comum.
**RQ cobertas**: RQ-02, RQ-13, RQ-14, RQ-24
**CT que ficam verdes**: CT-01, CT-02, CT-03, CT-04, CT-05, CT-06, CT-07, CT-37, CT-41, CT-42, CT-59
**CT-B**: CT-B01
**Passos do 01 envolvidos**: 1, 2, 3, 4, 5, 7, 8, 9 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: —
**Bloqueia**: 02, 03, 04
**Prefactoring**: não
**Costura**: G1 — componente Livewire/Filament; G2 — Pest feature HTTP; G4 — componente Livewire/Filament; G6 — Pest feature HTTP; G7 — browser; G8 — Pest feature HTTP
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-01…07, 37, 41, 42, 59 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 23/23, Tenancy CT-04 2/2, Permissoes 61/61); suíte completa sem regressão contra a baseline
