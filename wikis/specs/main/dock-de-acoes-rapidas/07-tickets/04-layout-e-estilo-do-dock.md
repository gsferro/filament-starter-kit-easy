# 04: Layout e estilo do dock escolhíveis

**Entrega**: O administrador define o layout e o estilo padrão do dock nas configurações; cada usuário pode trocar só o layout no slideover, e o dock aparece com essa escolha.
**RQ cobertas**: RQ-08, RQ-17
**CT que ficam verdes**: CT-08, CT-09, CT-57, CT-10, CT-11, CT-12
**CT-B**: —
**Passos do 01 envolvidos**: 3, 4, 6, 8 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: 01, 03
**Bloqueia**: —
**Prefactoring**: não
**Costura**: G1 — componente Livewire/Filament; G3 — componente Livewire/Filament
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-08, 09, 57 verdes (`pest tests/Kit/ConfiguracoesDoKitTelaTest.php` 38/38), CT-10, 11, 12 verdes; suíte sem regressão de código contra a baseline
