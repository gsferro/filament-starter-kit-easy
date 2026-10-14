# 07: Favoritos do painel de organização presos à organização

**Entrega**: O favorito criado numa organização abre nela de qualquer lugar e some quando o usuário perde o vínculo; o slideover oferece telas só da organização atual, mostra todos os favoritos que o usuário ainda abre, de qualquer organização e painel, e permite removê-los ou reordená-los.
**RQ cobertas**: RQ-16, RQ-25, RQ-35
**CT que ficam verdes**: CT-26, CT-27, CT-28, CT-29, CT-40, CT-43, CT-44, CT-51, CT-52, CT-55, CT-58, CT-61, CT-62, CT-63, CT-64
**CT-B**: —
**Passos do 01 envolvidos**: 6, 7, 8, 10 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: 05, 06
**Bloqueia**: —
**Prefactoring**: não
**Costura**: G1 — componente Livewire/Filament; G4 — componente Livewire/Filament; G5 — Pest feature HTTP
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-26…29, 40, 43, 44, 51, 52, 55, 58, 61…64 verdes (`pest tests/Tenancy/DockDeAtalhosTenancyTest.php` 38/38, Kit 111/111); passo 10 (docs pt/en, CHANGELOG, READMEs) com os 24 testes de docs de `tests/Kit` verdes; suíte completa 4424 testes, só a falha pré-existente da baseline
