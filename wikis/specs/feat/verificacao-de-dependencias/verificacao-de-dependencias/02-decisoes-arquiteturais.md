# Decisões Arquiteturais — Roadmap item 8.2: `composer-require-checker` + `composer-unused`

Nenhuma decisão passou nos três portões. As escolhas de desenho estão em `## Decisões de Desenho` do `01`.

## Superfície Livewire

A feature não cria página, widget nem componente: é ferramental de desenvolvimento (`composer.json`,
CI e um arquivo de configuração). Nenhum `public function` nem `public $` novo.
