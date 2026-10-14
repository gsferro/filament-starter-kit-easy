# 02: Ligar o dock não acrescenta consulta ao banco

**Entrega**: O administrador liga o dock num painel e a página continua fazendo o mesmo número de consultas ao banco que fazia com o dock desligado ou sem o pacote.
**RQ cobertas**: RQ-22
**CT que ficam verdes**: CT-35, CT-36, CT-56, CT-65
**CT-B**: —
**Passos do 01 envolvidos**: 3, 4, 7 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: 01
**Bloqueia**: —
**Prefactoring**: não
**Costura**: G2 — Pest feature HTTP
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-35, 36, 56, 65 verdes (`pest tests/Kit/DockDeAtalhosTest.php --filter="CT-(35|36|56|65)\]"` 5/5); suíte completa sem regressão contra a baseline
