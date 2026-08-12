{{--
    Gatilho da busca ⌘K.

    Reusa a MARCAÇÃO do campo nativo do Filament (`fi-global-search-field` +
    `x-filament::input.wrapper` com lupa inline e sufixo de atalho), igual a
    vendor/filament/filament/resources/views/livewire/global-search.blade.php.
    A topbar mantém a aparência de sempre; o que muda é o que acontece ao
    interagir: em vez de digitar aqui, abre o overlay do Spotlight.

    Nenhuma classe nova: tudo já é compilado pelo Filament, então o tema não
    precisa de `@source` adicional.

    `readonly` (não `disabled`): mantém o campo focável por Tab e sem o estilo
    apagado — quem navega por teclado precisa alcançar a busca.

    O overlay abre em `setTimeout`, FORA da interação que o pediu. Esse é o
    ponto: o pacote fecha o painel com `x-on:click.outside`, um listener no
    `document`, e um clique aqui é "fora do painel" para ele. Abrindo em outro
    task, o `document` já processou o clique inteiro enquanto o overlay ainda
    estava fechado, e o guard do Alpine descarta o evento. Tentativas que NÃO
    resolvem: abrir no `focus` (o foco dispara no mousedown e o `click`
    seguinte fecha), `mousedown.prevent` (impede o foco, não o clique) e
    `click.stop` (para o bubbling, mas não o listener no document).

    O handler fica no WRAPPER, não no input: clicar na lupa, no sufixo ou no
    padding também abre.

    `open-spotlight` é o evento que o blade do pacote escuta.
    `data-spotlight-trigger` é o seletor usado nos testes — preserve ao mexer.
--}}
<div
    x-data="{ abrir() { setTimeout(() => window.dispatchEvent(new CustomEvent('open-spotlight'))) } }"
    x-id="['spotlight-trigger']"
    x-on:click.stop.prevent="abrir()"
    class="fi-global-search-field"
>
    <label x-bind:for="$id('spotlight-trigger')" class="fi-sr-only">
        {{ __('filament-panels::global-search.field.label') }}
    </label>

    <x-filament::input.wrapper
        :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
        :prefix-icon-alias="\Filament\View\PanelsIconAlias::GLOBAL_SEARCH_FIELD"
        inline-prefix
        inline-suffix
    >
        <x-slot name="suffix">
            <span x-data="{ mac: navigator.platform.toUpperCase().includes('MAC') }" x-text="mac ? '⌘K' : 'Ctrl+K'"></span>
        </x-slot>

        <input
            data-spotlight-trigger
            type="search"
            readonly
            autocomplete="off"
            placeholder="{{ __('filament-search-spotlight::spotlight.placeholder') }}"
            x-bind:id="$id('spotlight-trigger')"
            x-on:keydown.enter.prevent.stop="abrir()"
            class="fi-input fi-input-has-inline-prefix cursor-pointer"
        />
    </x-filament::input.wrapper>
</div>
