# Decisões Arquiteturais — Issue #148

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01. -->

Nenhuma decisão passou nos três portões.

As quatro decisões da entrega (onde mora a varredura, granularidade por pasta, oráculo de autoria por conteúdo, nenhum channel de log) são todas reversíveis num commit e estão em `## Decisões de Desenho` do `01-plano-acao.md` (D1–D4). O que é surpreendente sem contexto — publish cru congela a tela do pacote e por isso não pode ser entregue — está registrado como P-01/P-03 no `00` e no comentário da constante `CAMINHOS_DO_KIT`, que é onde o próximo mantenedor vai ler.

## Superfície Livewire

Não exigida: a entrega não cria página, widget nem componente. Nenhuma `public function` ou `public $` nova; nenhum ponto que o navegador alcance muda.
