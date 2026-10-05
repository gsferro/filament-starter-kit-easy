{{--
    A marca composta do topo dos painéis: nome do projeto | nome do painel (ou da organização
    aberta) | logo da marca. Renderizada por `App\Support\CabecalhoDoPainel::marca()` e entregue
    ao Filament como `Htmlable` no `brandLogo()`, que a envolve numa `div.fi-logo` (ADR-01).

    Só classes do kit (`kit-cabecalho*`, em `resources/css/filament/kit.css`): utilitária Tailwind
    emitida por blade do kit não existe na página (`.ai/rules/css-filament.md`). As duas imagens
    carregam as classes nativas do swap do Filament, `fi-logo-light`/`fi-logo-dark`, e é o CSS
    dele que escolhe a certa pela classe `dark` do html — o servidor nunca conhece o tema.

    Tudo com chaves duplas: nome do projeto, da organização e da aplicação são texto de terceiro.
--}}
@php
    $alt = (string) config('app.name');
    $temTexto = filled($projeto) || filled($painel);
@endphp
<span class="kit-cabecalho">
    @if (filled($projeto))
        <span class="kit-cabecalho__projeto">{{ $projeto }}</span>
    @endif
    @if (filled($projeto) && filled($painel))
        <span class="kit-cabecalho__sep" aria-hidden="true"></span>
    @endif
    @if (filled($painel))
        <span class="kit-cabecalho__painel">{{ $painel }}</span>
    @endif
    @if (filled($logo_clara))
        @if ($temTexto)
            <span class="kit-cabecalho__sep" aria-hidden="true"></span>
        @endif
        {{-- height inline: fora do painel (página de erro do Sentinel) o CSS do kit pode não estar carregado, e sem altura a imagem sai no tamanho do arquivo. --}}
        <img src="{{ $logo_clara }}" alt="{{ $alt }}" class="kit-cabecalho__logo fi-logo{{ filled($logo_escura) ? ' fi-logo-light' : '' }}" style="height:2rem;width:auto" />
        @if (filled($logo_escura))
            <img src="{{ $logo_escura }}" alt="{{ $alt }}" class="kit-cabecalho__logo fi-logo fi-logo-dark" style="height:2rem;width:auto" />
        @endif
    @endif
</span>
