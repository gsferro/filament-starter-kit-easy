{{--
    O widget do desafio anti-robo — um blade para os tres provedores (App\Support\ProvedorAntiRobo).

    Os tres falam o mesmo contrato no navegador: script com `render=explicit` e `onload={fn}`, um
    objeto global com `render(el, opcoes)` que devolve um id, e `reset(id)`. O que muda e a URL e o
    nome do objeto, e os dois vem do provedor.

    O token vai para o estado do Livewire por `$wire.set(caminho, token, false)` — o terceiro
    argumento adia o envio ao servidor, entao marcar a caixa nao gera request; o token viaja junto
    com o envio do formulario. `wire:ignore` no container mantem o widget vivo entre re-renders (o
    Filament re-renderiza o formulario a cada erro de validacao).

    Depois de cada verificacao o servidor despacha o evento de redefinicao (o token e de uso unico),
    e o `x-on` abaixo chama `reset(id)`. Ver ADR-06 da wiki recaptcha-nas-telas-publicas.

    So a chave do SITE chega aqui — a view nao tem metodo para a secreta, de proposito.

    ATENCAO ao editar: comentario de blade NAO protege diretiva. Nunca escreva o nome de uma
    diretiva com arroba aqui dentro. Ver .ai/rules/views.md.

    ponytail: o `theme` segue a classe `dark` do <html>, que e como o Filament marca o tema; sem
    observar mudanca de tema em tempo real. Se alguem alternar o tema com o widget ja renderizado,
    ele fica na cor anterior ate o proximo carregamento — o `reset()` nao muda tema.
--}}
@php
    $provedor = $getProvedor();
    $objeto = $provedor->objetoJs();
    $script = $provedor->urlDoScript().'?render=explicit&onload=kitAntiRoboPronto';
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="{
            id: null,
            render() {
                if (this.id !== null) {
                    return
                }

                this.id = window[@js($objeto)].render(this.$refs.widget, {
                    sitekey: @js($getChaveDoSite()),
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    callback: (token) => $wire.set(@js($getStatePath()), token, false),
                    'expired-callback': () => $wire.set(@js($getStatePath()), null, false),
                    'error-callback': () => $wire.set(@js($getStatePath()), null, false),
                })
            },
            redefinir() {
                if (this.id === null) {
                    return
                }

                window[@js($objeto)].reset(this.id)
                $wire.set(@js($getStatePath()), null, false)
            },
            init() {
                if (window[@js($objeto)]?.render) {
                    this.render()

                    return
                }

                window.kitAntiRoboPronto = () => this.render()

                if (document.querySelector('script[data-kit-anti-robo]')) {
                    return
                }

                const script = document.createElement('script')
                script.src = '{{ $script }}'
                script.async = true
                script.defer = true
                script.dataset.kitAntiRobo = @js($provedor->value)
                document.head.appendChild(script)
            },
        }"
        x-on:{{ \App\Filament\Forms\Components\CampoAntiRobo::EVENTO_REDEFINIR }}.window="redefinir()"
        class="fi-fo-anti-robo"
        data-anti-robo="{{ $provedor->value }}"
    >
        <div x-ref="widget"></div>
    </div>
</x-dynamic-component>
