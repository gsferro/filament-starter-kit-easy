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
      z-40  botão do chat   (resources/views/livewire/assistente-chat-widget.blade.php:'Abrir assistente':25)
      z-40  overlay e janela de modal         (vendor/filament/support/.../modal.css)
      z-50  slide-over do chat                (assistente-chat-widget.blade.php:'Assistente':39)
      z-50  notificações    (pointer-events-none, não bloqueia clique)

    Como este Blade entra em `BODY_END` — depois de todos eles no DOM — empatar em z-30 faria
    o botão pintar por cima do overlay da sidebar no celular. Ficar embaixo é o certo: com
    modal, chat ou menu aberto, "voltar ao topo" não faz sentido.

    ── Offset por painel e a pilha do canto inferior direito ──

    No `/app` o botão do chat ocupa de 24px a 80px do rodapé (`bottom-6` + `h-14`). Este sobe
    para `bottom-24` = 96px, deixando 16px de folga. Nos outros dois painéis não há chat, e
    ele fica no `bottom-6` padrão.

    Os dois valores são o FALLBACK: a folha (`voltar-ao-topo.css`) soma a partir da
    `--kit-pilha-base`, que o script abaixo publica no `<html>` quando o dock de atalhos ocupa
    o lugar dos botões flutuantes. Aí a pilha inteira — chat e este botão — sobe para começar
    16px acima do topo do dock. Sem o script ou sem o dock, a variável não existe e valem os
    96px/24px de sempre. Wiki `pilha-de-botoes-flutuantes`.

    SE A POSIÇÃO DO CHAT MUDAR, ESTE OFFSET MUDA JUNTO — os três arquivos se citam
    (esta blade, `voltar-ao-topo.css` e `assistente-chat.css`).

    Os dois literais de classe ficam escritos no texto do arquivo de propósito: é assim que o
    Tailwind v4 os encontra e gera.

    ── Eixo comum ──

    O chat e o gatilho do dock têm 56px a 24px da borda direita; o centro deles fica a 52px.
    Este botão tem 44px: para cair no mesmo eixo vertical ele fica 6px mais para dentro
    (`margin-right` na folha).

    ── O script da pilha ──

    Mora aqui, e não num `Js` do `FilamentAsset`, porque esta blade já é renderizada em toda
    tela dos painéis pelo render hook global — e `resources/views/filament` já viaja pelas
    duas rotas de entrega (`create-project` e `kit:update`); `resources/js` e `public/js/kit`
    não estão em `KitUpdate::CAMINHOS_DO_KIT`.

    Ele só LÊ o dock (`.qad-root`, do `hoceineel/filament-quick-action-dock`): a posição que o
    pacote aplica por `transform`, `data-open` e `data-mode`. Não chama o servidor. Reage
    quando o retângulo do dock cruza o lugar de algum botão da pilha nas posições de hoje, ou o
    espaço entre eles; o dock em qualquer outro ponto não muda nada. Com o leque (`speed-dial`)
    aberto ali, põe `data-kit-pilha-oculta` no `<html>` e as duas folhas escondem os botões;
    a barra (`bar`) fica sempre expandida, então com ela a pilha só sobe.

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

<script>
    (() => {
        if (window.kitPilhaDeFlutuantes) {
            return;
        }

        window.kitPilhaDeFlutuantes = true;

        // Medidas de hoje, em rem como nas folhas: margem de 1.5rem (24px), botão de 3.5rem (chat
        // e gatilho do dock), "Voltar ao topo" de 2.75rem e 1rem entre vizinhos. Convertidas
        // pela fonte da raiz a cada cálculo: com outra fonte padrão no navegador, a faixa
        // acompanha os botões.
        const MARGEM = 1.5;
        const LARGURA = 3.5;
        const VOLTAR = 2.75;
        const ESPACO = 1;

        const html = document.documentElement;
        const observadorDoDock = new MutationObserver(() => agendar());
        let dockObservado = null;
        let quadro = null;

        function limpar() {
            html.style.removeProperty('--kit-pilha-base');
            html.removeAttribute('data-kit-pilha-oculta');
        }

        function calcular() {
            quadro = null;

            const dock = document.querySelector('.qad-root[data-placed]');

            if (! dock) {
                limpar();

                return;
            }

            const rem = parseFloat(getComputedStyle(html).fontSize) || 16;
            const largura = html.clientWidth;
            const altura = html.clientHeight;
            const temChat = document.querySelector('[data-assistente-chat] .bottom-6') !== null;
            const alturaDaPilha = ((temChat ? LARGURA + ESPACO : 0) + VOLTAR) * rem;
            const r = dock.getBoundingClientRect();

            // O lugar da pilha nas posições de hoje: a coluna da direita, do rodapé até o topo do
            // "Voltar ao topo", com o espaço entre os botões incluído.
            const cruza = r.left < largura - MARGEM * rem
                && r.right > largura - (MARGEM + LARGURA) * rem
                && r.top < altura - MARGEM * rem
                && r.bottom > altura - MARGEM * rem - alturaDaPilha;

            if (! cruza) {
                limpar();

                return;
            }

            html.style.setProperty('--kit-pilha-base', `${Math.round(altura - r.top + ESPACO * rem)}px`);
            html.toggleAttribute('data-kit-pilha-oculta', dock.dataset.mode === 'speed-dial' && dock.dataset.open === 'true');
        }

        function agendar() {
            if (quadro === null) {
                quadro = requestAnimationFrame(calcular);
            }
        }

        function ligar() {
            const dock = document.querySelector('.qad-root');

            if (dock !== dockObservado) {
                observadorDoDock.disconnect();
                dockObservado?.removeEventListener('transitionend', agendar);
                dockObservado = dock;

                if (dock) {
                    observadorDoDock.observe(dock, {
                        attributes: true,
                        attributeFilter: ['style', 'data-open', 'data-mode', 'data-placed'],
                    });

                    // O pacote anima o `transform` ao mudar de canto (`data-settling`): medido
                    // no meio da animação, o retângulo ainda é o do canto anterior. O fim da
                    // transição traz a posição final.
                    dock.addEventListener('transitionend', agendar);
                }
            }

            agendar();
        }

        // O dock e o chat podem entrar no DOM depois deste script (render hooks e morph do
        // Livewire): a árvore é observada até o dock aparecer ou ser trocado. Na navegação SPA
        // o Livewire troca o `<body>` inteiro, então o observador passa para o novo.
        const observadorDoBody = new MutationObserver(ligar);
        const observarBody = () => {
            observadorDoBody.disconnect();
            observadorDoBody.observe(document.body, { childList: true, subtree: true });
            ligar();
        };
        window.addEventListener('resize', agendar);
        document.addEventListener('livewire:navigated', observarBody);
        observarBody();
    })();
</script>
