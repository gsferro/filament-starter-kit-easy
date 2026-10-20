{{--
    O dock (`hoceineel/filament-quick-action-dock`) medido pela área visível, não pelo `innerHeight`.

    O pacote calcula o viewport com `{width: documentElement.clientWidth, height: window.innerHeight}`
    (`vendor/hoceineel/filament-quick-action-dock/dist/quick-action-dock.js`, função `b()`), e o
    elemento `fixed` vive no LAYOUT viewport, que é o `clientHeight`. Quando o navegador reporta
    `innerHeight` maior que a área visível — medido num Chrome desktop: 867 = `outerHeight`, com a
    área visível de 780 —, todo reposicionamento leva o `bottom-center` para fora da tela
    (y = 867 − 56 − 24 = 787). O sintoma é o botão que "nasce e some", e mover o botão não corrige: só
    troca a conta pela fração lembrada.

    O pacote não exporta a fábrica do componente; registra `Alpine.data('quickActionDock', …)` no
    `alpine:init`. Este script vem no `HEAD_END`, então o listener dele roda ANTES do do pacote
    (que está no fim do body) e embrulha o registro: `env()` — por onde passam `place()`, o snap, o
    arraste e o teclado — devolve a altura do `clientHeight`. Sem o pacote, nada acontece.

    Remover quando o pacote medir pela área visível. Wiki: `wikis/specs/fix/dock-altura-do-viewport/`.
--}}
<script>
    document.addEventListener('alpine:init', () => {
        const registrar = window.Alpine.data.bind(window.Alpine);

        window.Alpine.data = (nome, fabrica) => registrar(nome, nome !== 'quickActionDock' ? fabrica : (...argumentos) => {
            const componente = fabrica(...argumentos);
            const medirOriginal = componente.env;

            componente.env = function () {
                const medida = medirOriginal.call(this);

                medida.viewport = {...medida.viewport, height: document.documentElement.clientHeight || window.innerHeight};

                return medida;
            };

            return componente;
        });
    });
</script>
