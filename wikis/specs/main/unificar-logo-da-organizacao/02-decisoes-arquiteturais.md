# Decisões Arquiteturais — unificar-logo-da-organizacao

## ADR-01: Backfill da `unifica_logo` por evidência de uso — `logo_dark` gravada implica marca separada

**Status**: Aceita
**Data**: 2026-10-09
**Portões**: difícil de reverter ✅ (o valor gravado decide o render até alguém editar a organização — desfazer exige outra migration) · surpreendente ✅ (default `true` na coluna mas `false` em parte das linhas existentes — quem lê o `default(true)` sem ler o backfill conclui errado) · trade-off ✅ (zero-mudança-de-tela × comportamento pedido pelo requisito)

### Contexto

A coluna nasce com `default(true)` — organização nova começa unificada, que é o comportamento da marca da instalação (`unifica_logo_marca` nasce `true`). Mas em update, `default(true)` valeria também para quem já enviou `logo_dark`: a escura gravada ficaria inerte e o dark voltaria a mostrar a clara — mudança de tela sem gesto, a armadilha que o kit inteiro evita (mesmo argumento do `unifica_logo_marca` nascer `true` na feature `logo-dark-mode`).

Na direção oposta, `default(false)` para todos os existentes preserva a tela de quem **tem** `logo_dark`, mas deixa quem nunca mandou a escura exatamente no buraco que motivou o pedido: no dark, a marca da instalação no lugar da dela.

### Decisão

`unifica_logo = (logo_dark IS NULL)` no backfill. Quem gravou a escura demonstrou a escolha pela separação → `false`, tela idêntica. Quem não gravou → `true`: o dark passa a mostrar a clara da organização, que é exatamente o comportamento do requisito (RQ-03).

### Alternativas Consideradas

1. `false` para todas as linhas existentes — zero mudança de tela, mas entrega a feature desligada para o público que mais precisa dela; exigiria um segundo gesto do admin por organização.
2. `true` para todas — muda a tela de quem separou de propósito; a `logo_dark` enviada ficaria inerte sem aviso.
3. Inferir "unificada = logo_dark vazia" sem coluna — recusado na D1: booleano implícito não é decisão gravada, e quem esvaziasse a `logo_dark` depois mudaria de modo sem ver o toggle.

### Consequências

- **Positivas**: nenhuma organização que separou a marca perde a separação; toda organização que tinha só a clara ganha o comportamento pedido sem gesto; organização nova nasce no modo simples.
- **Negativas**: organização sem `logo_dark` vê o dark mudar (da logo_dark da instalação para a clara dela) — mudança de tela por update, declarada no CHANGELOG.
- **Riscos**: quem quiser a `logo_dark` da instalação no dark sem mandar nada perde essa opção — mitigação: enviar a clara de novo como escura, ou desligar e deixar vazio (cai na da instalação, RQ-03/Q4).

### Referências

- `IdentidadeDoKit::logosPara` (regra do par) · `Tenant::urlDaLogoEscura` · migration `add_logo_dark_to_tenants_table` (mesma filosofia de coluna inerte quando vazia)
- Wiki ancestral `wikis/specs/fix/logo-dark-do-tenant/` (a queda por variante que esta feature mantém no modo separado)

---

Nenhuma outra decisão passou nos três portões — o restante (onde mora o toggle, condição de visibilidade, consulta no `logosPara()`) é mecânica, registrado em `## Decisões de Desenho` do `01`.
