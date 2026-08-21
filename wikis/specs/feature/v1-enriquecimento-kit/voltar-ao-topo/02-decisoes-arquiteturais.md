# Decisões Arquiteturais — Botão "Voltar ao topo"

## ADR-01: Render hook global, e não o pacote

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

Existe `gboquizosanchez/filament-scroll-to-top`, e o nome descreve exatamente o pedido. O `mini-pff`
leu o código-fonte inteiro antes de instalar, e a varredura dos 547 plugins deste kit chegou à mesma
conclusão por outro caminho, listando-o em "coberto por recurso nativo".

### O que o pacote realmente faz

Quatro arquivos, ~40 linhas, nenhum CSS e nenhum asset:

- `ScrollToTopPlugin::boot()` registra um render hook que injeta um `<script>`
- o script é um listener de 3 linhas: `document.addEventListener('scroll-to-top', ...)`
- `Traits\ScrollToTop` sobrescreve `setPage()` e dispara o evento

| | O que o pedido pede | O que o pacote entrega |
|---|---|---|
| Gatilho | usuário clica quando quiser | troca de página da paginação |
| Elemento visível | botão flutuante | **nenhum** — não renderiza UI |
| Cobertura | todas as telas de todos os painéis | só onde o trait for colado, uma a uma |

### Decisão

**Não instalar.** Implementar como ~25 linhas de Blade com Alpine, injetadas uma vez por
`FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, ...)` **sem `scopes:`**.

### O argumento que decide, e é de cobertura

O kit tem treze plugins que trazem tela própria. Cinco delas são listagens de vendor que **não podem
receber trait**: permissões (Shield), auditoria, log de autenticação, monitor de filas e exceções.
Para o pacote cobri-las seria preciso estender e sobrescrever a página do pacote.

Um render hook global alcança todas, sem tocar em nenhum arquivo. É a diferença entre "o botão está
no kit" e "o botão está em todas as telas" — que é literalmente o que RQ-05 pede.

Some-se que o kit fixa `defaultPaginationPageOption(10)`: numa tela de notebook, a tabela de 10
linhas quase nunca estoura a dobra, então o problema que o pacote resolve — rolar após paginar —
raramente aparece aqui.

### Alternativas Consideradas

1. **Instalar o pacote e colar o trait em cada `ListRecords`** — descartada: não entrega botão
   nenhum, e deixa de fora as cinco telas de vendor.
2. **Registrar o hook em cada PanelProvider** — descartada: três registros para o mesmo
   comportamento global, e um painel novo do seu projeto nasceria sem o botão. O `ViewManager`
   normaliza `scopes: null` para o bucket `''`, lido sempre — um registro basta.
3. **Componente Livewire** — descartada: é chrome sem estado. Livewire aqui seria um roundtrip de
   servidor para rolar a página.

### Consequências

- **Positivas**: cobertura real de todas as telas, inclusive futuras e de vendor; zero dependência;
  ~25 linhas num arquivo.
- **Negativas**: o offset por painel vive dentro do Blade, então mudar a posição do chat exige mexer
  em dois arquivos. Mitigado: os dois se citam.

### Referências

- `mini-pff` — `wikis/specs/main/scroll-to-top-paineis/01-plano-acao.md`
- `wikis/pacotes-ranking.md` → seção 4.2
- `resources/views/livewire/assistente-chat-widget.blade.php:12-13`

---

## ADR-02: `z-20` — abaixo de tudo, de propósito

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

O botão entra em `BODY_END`, portanto **depois** de todo o resto no DOM. Em empate de `z-index`,
quem vem depois pinta por cima.

Camadas ocupadas: topbar e sidebar mobile em `z-30`, chat e modal em `z-40`, slide-over e
notificações em `z-50`.

### Decisão

`z-20`. Abaixo de todas.

### Por que não `z-30`

Empatar com a sidebar mobile faria o botão pintar **por cima do overlay** dela no celular — um botão
flutuante sobre um menu aberto, clicável quando não deveria ser.

E a regra geral é a mesma: com modal, chat ou menu aberto, "voltar ao topo" não faz sentido. Ficar
embaixo não é limitação, é o comportamento correto.

### Consequências

- **Positivas**: nenhuma colisão com o que existe hoje, e a regra é fácil de manter — o botão é
  sempre o mais baixo.
- **Riscos**: plugin futuro que use `z-10` ou menos ficaria abaixo dele. Improvável, e o comentário
  do Blade lista cada camada com arquivo de origem.
