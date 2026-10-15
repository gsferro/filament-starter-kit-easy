# Decisões Arquiteturais — Pilha harmônica dos botões flutuantes

Nenhuma decisão passou nos três portões. As escolhas de desenho estão em `## Decisões de Desenho` do `01` (D1 a D5): todas se revertem trocando um script e duas folhas, sem dado nem migration.

## Superfície Livewire

A feature não cria página, widget nem componente, e não muda propriedade nem método público do componente do chat: a blade dele só ganha dois atributos de dado. O script novo roda no navegador e não chama o servidor.

| Origem | Ponto | Tipo | O que o cliente controla | Fronteira / validação |
|---|---|---|---|---|
| projeto | `App\Livewire\AssistenteChatWidget` | componente existente | o mesmo de antes desta feature | inalterada; a feature não toca a classe |
| vendor | `.qad-root` do dock (posição, `data-open`, `data-mode`) | estado só do navegador | posição e abertura do dock | só lido pelo script; nada vai ao servidor |
