{{--
    Botão flutuante "Voltar ao topo".

    Registrado UMA vez, em `App\Providers\Concerns\ConfiguraFilamentGlobal`, no render hook
    `BODY_END` e **sem `scopes:`** — vale para os três painéis, para qualquer painel futuro
    e, o que importa mais, para as telas que vêm de plugin de terceiro. Auditoria, log de
    autenticação, exceções, trilha de e-mail, monitor de filas e releases do Composer não
    podem receber trait nem edição; um render hook global alcança todas.

    ── Por que não um pacote ──

    Existe `gboquizosanchez/filament-scroll-to-top`, e ele NÃO faz isto. O código-fonte dele
    é um listener de 3 linhas de JS mais um override de `setPage()`: rola ao trocar de página
    da paginação, e não renderiza botão nenhum. Para "cobrir" um painel seria preciso colar o
    trait em cada `ListRecords` e `RelationManager` — e ainda ficariam de fora justamente as
    listagens de vendor. Ver ADR-01 da wiki desta feature, e a seção "coberto por recurso
    nativo" de wikis/pacotes-ranking.md, que chegou à mesma conclusão pela varredura.

    ── Quem rola é a window ──

    O layout do Filament só declara `min-h-dvh` em `.fi-body` e `overflow-x-clip` em
    `.fi-layout` (vendor/filament/filament/resources/css/components/layout.css). Não há
    container com scroll próprio, então `window.scrollY` e `window.scrollTo` bastam — nada de
    descobrir elemento rolável.

    ── Camada: z-20, e não z-30 ──

    Está deliberadamente ABAIXO de tudo. Ocupam camadas acima:

      z-30  topbar          (vendor/filament/filament/resources/css/components/topbar.css)
      z-30  sidebar mobile e o overlay dela   (.../sidebar.css)
      z-40  botão do chat   (resources/views/livewire/assistente-chat-widget.blade.php:20)
      z-40  overlay e janela de modal         (vendor/filament/support/.../modal.css)
      z-50  slide-over do chat                (assistente-chat-widget.blade.php:34)
      z-50  notificações    (pointer-events-none, não bloqueia clique)

    Como este Blade entra em `BODY_END` — depois de todos eles no DOM — empatar em z-30 faria
    o botão pintar por cima do overlay da sidebar no celular. Ficar embaixo é o certo: com
    modal, chat ou menu aberto, "voltar ao topo" não faz sentido.

    ── Offset por painel ──

    No `/app` o botão do chat ocupa de 24px a 80px do rodapé (`bottom-6` + `h-14`). Este sobe
    para `bottom-24` = 96px, deixando 16px de folga. Nos outros dois painéis não há chat, e
    ele fica no `bottom-6` padrão.

    SE A POSIÇÃO DO CHAT MUDAR, ESTE OFFSET MUDA JUNTO — os dois arquivos se citam.

    Os dois literais de classe ficam escritos no texto do arquivo de propósito: é assim que o
    Tailwind v4 os encontra e gera.

    ── prefers-reduced-motion ──

    O `@click` precisa do `matchMedia`: `scrollTo({behavior:'smooth'})` é argumento de JS e,
    por especificação, sobrepõe o `scroll-behavior` do CSS — só `'auto'` devolve a decisão ao
    CSS. Já o fade do `x-transition` NÃO precisa de nada: o Filament tem um reset global com
    `transition-duration: 0.01ms !important` em `*` (vendor/filament/support/.../base.css),
    deliberadamente 0.01ms e não 0 para que o `transitionend` continue disparando e o
    `x-transition` do Alpine resolva.

    ── focus-visible, não focus ──

    Divergência deliberada do botão do chat, que usa `focus:` e pinta o anel também no clique
    de mouse. Aqui o anel só aparece para quem navega por teclado.

    `data-voltar-ao-topo` é âncora de teste, não estilo: o CT-B precisa afirmar que o botão
    APARECE ao rolar, e não há texto visível para ancorar — o rótulo é `aria-label`.
--}}
@php
    $recuoDoBotao = filament()->getCurrentOrDefaultPanel()?->getId() === 'app'
        ? 'bottom-24'
        : 'bottom-6';
@endphp

<button
    type="button"
    data-voltar-ao-topo
    x-data="{ visivel: false }"
    x-init="visivel = window.scrollY > 400"
    @scroll.window.passive="visivel = window.scrollY > 400"
    x-show="visivel"
    x-cloak
    x-transition.opacity
    @click="window.scrollTo({
        top: 0,
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
    })"
    aria-label="Voltar ao topo"
    title="Voltar ao topo"
    class="fixed {{ $recuoDoBotao }} right-6 z-20 flex h-11 w-11 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg transition hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2"
>
    {{-- ponytail: `aria-hidden` vem de fábrica nos SVG dos sets embarcados do Filament. --}}
    <x-filament::icon icon="heroicon-o-arrow-up" class="h-5 w-5" />
</button>
