{{--
    O painel de marca do layout "painel" das telas de acesso (wiki `feat/aparencia-das-telas-de-acesso`).

    Renderizado pelo partial "media" do auth-designer, que o kit sobrescreve: o pacote só inclui
    esse partial com mídia, então a configuração do layout "painel" sempre leva uma (ADR-03).

    Tudo aqui sai escapado. A única coisa que entra num atributo style é a cor, e ela já chega
    validada: hexadecimal de seis dígitos ou uma constante da paleta do Filament (P-05).
    As classes são do kit e estão em resources/css/filament/kit.css: utilitária Tailwind emitida
    por blade do kit não existe na página (.ai/rules/css-filament.md).
--}}
@php
    $fundoArte = ($aparencia['fundo'] ?? 'cor') === 'arte';
    $cor = method_exists($livewire, 'corDoPainelDeMarca') ? $livewire->corDoPainelDeMarca() : null;
    $logos = ($aparencia['logo_no_painel'] ?? true) && method_exists($livewire, 'logosDoPainelDeMarca')
        ? $livewire->logosDoPainelDeMarca()
        : ['clara' => null, 'escura' => null];
    $nome = (string) config('app.name');
    $frase = $aparencia['frase'] ?? null;
    $apoio = $aparencia['apoio'] ?? null;
@endphp

<div
    @class([
        'kit-painel-de-marca',
        'kit-painel-de-marca--arte' => $fundoArte,
        'kit-painel-de-marca--cor' => ! $fundoArte,
        'kit-painel-de-marca--claro' => ! $fundoArte && \App\Support\AparenciaDasTelasDeAcesso::fundoClaro($cor),
    ])
    @if (filled($cor)) style="--kit-painel-fundo: {{ $cor }}" @endif
    data-kit-aparencia="painel"
>
    @if ($fundoArte)
        <img class="kit-painel-de-marca__arte" src="{{ $config->media }}" alt="" />
        <div class="kit-painel-de-marca__veu" aria-hidden="true"></div>
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

        @if (filled($frase) || filled($apoio))
            <div class="kit-painel-de-marca__textos">
                @if (filled($frase))
                    <p class="kit-painel-de-marca__frase">{{ $frase }}</p>
                @endif

                @if (filled($apoio))
                    <p class="kit-painel-de-marca__apoio">{{ $apoio }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
