# 05: Adicionar a página atual aos favoritos, até 10

**Entrega**: Na lista de um Resource ou numa Page, o usuário escolhe "Adicionar esta página" e ela entra no fim dos favoritos; a opção não aparece em tela de registro nem quando a página já é favorita; com 10 favoritos, a adição e o slideover recusam com aviso e nada muda.
**RQ cobertas**: RQ-18, RQ-21, RQ-23, RQ-26, RQ-28, RQ-33
**CT que ficam verdes**: CT-30, CT-31, CT-32, CT-33, CT-34, CT-38, CT-39, CT-48, CT-49
**CT-B**: —
**Passos do 01 envolvidos**: 7, 8 — [01 › Estrutura de Implementação](../01-plano-acao.md#estrutura-de-implementação)
**Bloqueado por**: 03
**Bloqueia**: 06, 07
**Prefactoring**: não
**Costura**: G1 — componente Livewire/Filament
**Status**: concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-30…34, 38, 39, 48, 49 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 111/111); suíte completa 4424 testes, só a falha pré-existente da baseline
