{{--
    O painel de marca do layout "painel" das telas de acesso (wiki `feat/aparencia-das-telas-de-acesso`).

    Renderizado pelo partial "media" do auth-designer, que o kit sobrescreve: o pacote só inclui
    esse partial com mídia, então a configuração do layout "painel" sempre leva uma (ADR-03).

    Tudo aqui sai escapado. O que entra num atributo style são cores, e elas já chegam validadas:
    hexadecimal de seis dígitos ou uma constante da paleta do Filament (P-05).
    As classes são do kit e estão em resources/css/filament/kit.css: utilitária Tailwind emitida
    por blade do kit não existe na página (.ai/rules/css-filament.md).

    Três faixas na vertical: o topo (logo ou nome), o meio (o destaque, P-20) e a base (frase,
    apoio e o botão de pausar a ilustração). No celular só o topo aparece (P-22).

    A ilustração "rede" (P-21) é um SVG decorativo (aria-hidden). Com movimento, um componente
    Alpine desloca os pontos em torno da posição desenhada aqui; sem JavaScript, ou com "reduzir
    movimento" no sistema, a rede fica parada e o botão de pausar continua escondido. O laço só
    roda com a rede à vista: abaixo de 1024 px (onde o CSS a esconde, P-22), com a aba oculta,
    pausado ou com "reduzir movimento" — também quando a preferência muda com a tela aberta —,
    nenhum quadro é pedido (RD-A1 e CX-A3 da revisão do adendo).
--}}
@php
    $fundo = $aparencia['fundo'] ?? 'cor';
    $fundoArte = $fundo === 'arte';
    $cor = method_exists($livewire, 'corDoPainelDeMarca') ? $livewire->corDoPainelDeMarca() : null;
    $gradiente = $fundo === 'gradiente' && method_exists($livewire, 'gradienteDoPainelDeMarca') ? $livewire->gradienteDoPainelDeMarca() : null;
    $formato = $aparencia['formato_dos_textos'] ?? 'texto';
    $logos = ($aparencia['logo_no_painel'] ?? true) && method_exists($livewire, 'logosDoPainelDeMarca')
        ? $livewire->logosDoPainelDeMarca()
        : ['clara' => null, 'escura' => null];
    $destaque = method_exists($livewire, 'destaqueDoPainelDeMarca') ? $livewire->destaqueDoPainelDeMarca() : null;
    $nome = (string) config('app.name');
    $frase = \App\Support\AparenciaDasTelasDeAcesso::textoComoHtml($aparencia['frase'] ?? null, $formato);
    $apoio = \App\Support\AparenciaDasTelasDeAcesso::textoComoHtml($aparencia['apoio'] ?? null, $formato);
    $comRede = ($aparencia['ilustracao'] ?? 'nenhuma') === 'rede';
    $emMovimento = $comRede && (bool) ($aparencia['movimento'] ?? true);
    $corDaRede = $comRede ? ($aparencia['cor_da_ilustracao'] ?? null) : null;
    $rede = $comRede ? \App\Support\AparenciaDasTelasDeAcesso::rede() : null;
    $tamanhoDoDestaque = $aparencia['tamanho_do_destaque'] ?? 'medio';
@endphp

<div
    @class([
        'kit-painel-de-marca',
        'kit-painel-de-marca--arte' => $fundoArte,
        'kit-painel-de-marca--cor' => $fundo === 'cor',
        'kit-painel-de-marca--gradiente' => filled($gradiente),
        'kit-painel-de-marca--claro' => $fundo === 'cor' && \App\Support\AparenciaDasTelasDeAcesso::fundoClaro($cor),
    ])
    @if (filled($cor)) style="--kit-painel-fundo: {{ $cor }}@if (filled($gradiente)); --kit-painel-gradiente: {{ $gradiente }}@endif" @endif
    data-kit-aparencia="painel"
    @if ($emMovimento)
        x-data="{
            pausado: false,
            quadro: null,
            inicio: null,
            init() {
                const pontos = [...this.$refs.rede.querySelectorAll('circle')].map((circulo, i) => ({
                    circulo,
                    x: Number(circulo.getAttribute('cx')),
                    y: Number(circulo.getAttribute('cy')),
                    fase: i * 1.7,
                    velocidade: 0.00035 + (i % 4) * 0.00008,
                }))
                const linhas = [...this.$refs.rede.querySelectorAll('line')].map((linha) => ({
                    linha,
                    a: pontos[Number(linha.dataset.a)],
                    b: pontos[Number(linha.dataset.b)],
                }))
                const passo = (agora) => {
                    if (this.inicio === null) {
                        this.inicio = agora
                    }
                    const t = agora - this.inicio
                    for (const p of pontos) {
                        p.atualX = p.x + Math.sin(t * p.velocidade + p.fase) * 12
                        p.atualY = p.y + Math.cos(t * p.velocidade * 0.8 + p.fase) * 9
                        p.circulo.setAttribute('cx', p.atualX.toFixed(1))
                        p.circulo.setAttribute('cy', p.atualY.toFixed(1))
                    }
                    for (const l of linhas) {
                        l.linha.setAttribute('x1', l.a.atualX.toFixed(1))
                        l.linha.setAttribute('y1', l.a.atualY.toFixed(1))
                        l.linha.setAttribute('x2', l.b.atualX.toFixed(1))
                        l.linha.setAttribute('y2', l.b.atualY.toFixed(1))
                    }
                    this.quadro = requestAnimationFrame(passo)
                }
                const largo = window.matchMedia('(min-width: 1024px)')
                const reduzido = window.matchMedia('(prefers-reduced-motion: reduce)')
                this.sincronizar = () => {
                    cancelAnimationFrame(this.quadro)
                    this.$refs.pausa.hidden = reduzido.matches
                    if (largo.matches && ! reduzido.matches && ! this.pausado && ! document.hidden) {
                        this.quadro = requestAnimationFrame(passo)
                    }
                }
                largo.addEventListener('change', this.sincronizar)
                reduzido.addEventListener('change', this.sincronizar)
                document.addEventListener('visibilitychange', this.sincronizar)
                this.sincronizar()
            },
            alternar() {
                this.pausado = ! this.pausado
                this.sincronizar()
            },
        }"
    @endif
>
    @if ($fundoArte)
        <img class="kit-painel-de-marca__arte" src="{{ $config->media }}" alt="" />
        <div class="kit-painel-de-marca__veu" aria-hidden="true"></div>
    @endif

    @if ($comRede)
        <svg
            class="kit-painel-de-marca__rede"
            viewBox="0 0 600 400"
            preserveAspectRatio="xMidYMin meet"
            aria-hidden="true"
            focusable="false"
            data-kit-ilustracao="rede"
            @if ($emMovimento) data-kit-movimento x-ref="rede" @endif
            @if (filled($corDaRede)) style="color: {{ $corDaRede }}" @endif
        >
            @foreach ($rede['ligacoes'] as [$a, $b])
                <line
                    x1="{{ $rede['pontos'][$a]['x'] }}" y1="{{ $rede['pontos'][$a]['y'] }}"
                    x2="{{ $rede['pontos'][$b]['x'] }}" y2="{{ $rede['pontos'][$b]['y'] }}"
                    data-a="{{ $a }}" data-b="{{ $b }}"
                />
            @endforeach
            @foreach ($rede['pontos'] as $ponto)
                <circle cx="{{ $ponto['x'] }}" cy="{{ $ponto['y'] }}" r="{{ $ponto['r'] }}" />
            @endforeach
        </svg>
    @endif

    <div class="kit-painel-de-marca__conteudo">
        <div class="kit-painel-de-marca__topo">
            @if (filled($logos['clara']))
                <span class="kit-painel-de-marca__selo">
                    <img class="kit-painel-de-marca__logo" src="{{ $logos['clara'] }}" alt="{{ $nome }}" />
                </span>
            @else
                <span class="kit-painel-de-marca__nome">{{ $nome }}</span>
            @endif
        </div>

        <div class="kit-painel-de-marca__meio">
            @if (($destaque['tipo'] ?? null) === 'imagem')
                <img
                    class="kit-painel-de-marca__destaque kit-painel-de-marca__destaque--imagem kit-painel-de-marca__destaque--{{ $tamanhoDoDestaque }}"
                    src="{{ $destaque['url'] }}"
                    alt=""
                    data-kit-destaque="imagem"
                />
            @elseif (($destaque['tipo'] ?? null) === 'logo')
                @if (filled($destaque['clara']))
                    <span class="kit-painel-de-marca__selo kit-painel-de-marca__selo--destaque" data-kit-destaque="logo">
                        <img class="kit-painel-de-marca__destaque kit-painel-de-marca__destaque--{{ $tamanhoDoDestaque }}" src="{{ $destaque['clara'] }}" alt="{{ $nome }}" />
                    </span>
                @else
                    <span class="kit-painel-de-marca__nome-destaque kit-painel-de-marca__nome-destaque--{{ $tamanhoDoDestaque }}" data-kit-destaque="nome">{{ $nome }}</span>
                @endif
            @endif
        </div>

        <div class="kit-painel-de-marca__base">
            @if (filled($frase) || filled($apoio))
                <div class="kit-painel-de-marca__textos">
                    @if (filled($frase))
                        <div class="kit-painel-de-marca__frase">{{ $frase }}</div>
                    @endif

                    @if (filled($apoio))
                        <div class="kit-painel-de-marca__apoio">{{ $apoio }}</div>
                    @endif
                </div>
            @endif

            @if ($emMovimento)
                <button
                    type="button"
                    class="kit-painel-de-marca__pausa"
                    x-ref="pausa"
                    x-on:click="alternar()"
                    data-kit-pausa
                    hidden
                >
                    <span x-text="pausado ? 'Retomar o movimento' : 'Pausar o movimento'">Pausar o movimento</span>
                </button>
            @endif
        </div>
    </div>
</div>
