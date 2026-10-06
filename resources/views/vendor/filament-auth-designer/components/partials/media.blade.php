{{-- Override autoral do kit, entregue pelo kit:update (issue #148). --}}
@props([
    'config',
    'imageClass' => '',
    'videoClass' => '',
])

@php
    /*
     * Override do pacote (ver ADR-01 de `feat/logo-dark-mode`): a página que
     * resolve um PAR de logos — hoje `TelaBloqueio` — declara `urlsDasLogos()`
     * e recebe a dupla <img> com as classes nativas do swap do Filament. O
     * cheque é pelo método e não pelo FQCN: qualquer página que implemente o
     * par ganha o mesmo tratamento, sem a view conhecer a classe.
     *
     * `clara` vazia NUNCA cai aqui: `getAuthDesignerConfig()` só coloca a logo
     * em `media` quando ela resolve — sem ela, a mídia é a arte, que fica no
     * ramo do vendor abaixo, byte a byte (a arte é `cover`; a logo é `contain`).
     */
    $logos = method_exists($livewire, 'urlsDasLogos') ? $livewire->urlsDasLogos() : null;
    $ehLogo = filled($logos['clara'] ?? null) && $logos['clara'] === $config->media;
@endphp

@if($ehLogo)
    {{-- A logo da marca: centrada e contida — ela é o elemento, não a arte. --}}
    <div style="display:flex;justify-content:center;align-items:center;width:100%;height:100%">
        <img
            src="{{ $logos['clara'] }}"
            alt="{{ $config->mediaAlt ?? 'Logo da marca' }}"
            class="fi-logo fi-logo-light"
            style="margin:0;max-width:100%;max-height:100%;object-fit:contain"
        />
        @if(filled($logos['escura']))
            <img
                src="{{ $logos['escura'] }}"
                alt="{{ $config->mediaAlt ?? 'Logo da marca' }}"
                class="fi-logo fi-logo-dark"
                style="margin:0;max-width:100%;max-height:100%;object-fit:contain"
            />
        @endif
    </div>
@elseif($config->isVideo())
    <video
        autoplay
        loop
        muted
        playsinline
        class="{{ $videoClass }}"
    >
        <source src="{{ $config->media }}" type="{{ $config->mediaMimeType }}">
    </video>
@else
    <img
        src="{{ $config->media }}"
        alt="{{ $config->mediaAlt ?? 'Authentication' }}"
        class="{{ $imageClass }}"
    />
@endif
