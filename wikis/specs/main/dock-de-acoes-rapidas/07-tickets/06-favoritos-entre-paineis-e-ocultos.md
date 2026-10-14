# 06: Favoritos de outro painel e favoritos ocultos

**Entrega**: O favorito de um painel aparece no dock de qualquer painel com o dock ligado e abre no painel dele; quando o usuário perde o acesso, o favorito some do dock e do slideover, continua gravado, conta no limite e volta com o acesso.
**RQ cobertas**: RQ-15, RQ-19, RQ-27, RQ-32
**CT que ficam verdes**: CT-23, CT-24, CT-25, CT-45, CT-46, CT-47, CT-53, CT-54, CT-60
**CT-B**: —
**Passos do 01 envolvidos**: 7, 8 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: 03, 05
**Bloqueia**: 07
**Prefactoring**: não
**Costura**: G1 — componente Livewire/Filament; G2 — Pest feature HTTP
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-23, 24, 25, 45, 46, 47, 53, 54, 60 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 111/111) — comportamento entregue pelo código dos tickets 03 e 05; suíte completa sem regressão
