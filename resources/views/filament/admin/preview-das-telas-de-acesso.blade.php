{{--
    O preview do editor de "Telas de acesso" (wiki `feat/aparencia-das-telas-de-acesso`, ADR-04).

    O iframe abre a página de demonstração com o rascunho que o editor acabou de gravar. Quando o
    rascunho muda, a URL muda (parâmetro v) e a chave do iframe muda junto: o Livewire troca o
    elemento e o navegador carrega o preview novo sozinho. Tela e
    largura são escolhas só do navegador: trocam um parâmetro da URL e a largura do quadro, sem
    ida ao servidor.

    Estilo inline e classes do kit: utilitária Tailwind emitida por blade do kit não existe na
    página (.ai/rules/css-filament.md).
--}}
@php
    $telas = \App\Filament\Admin\Schemas\EditorDeAparenciaDasTelas::telas();
@endphp

<div
    x-data="{
        tela: 'login',
        largura: 'computador',
        escala: 1,
        medir() {
            const disponivel = this.$refs.moldura?.clientWidth ?? 0
            const base = this.largura === 'celular' ? 390 : 1440
            this.escala = disponivel > 0 ? Math.min(1, disponivel / base) : 1
        },
        get estiloDoQuadro() {
            const [largura, altura] = this.largura === 'celular' ? [390, 844] : [1440, 900]
            return `width: ${largura}px; height: ${altura}px; transform: scale(${this.escala}); transform-origin: top left`
        },
        get alturaDaMoldura() {
            const altura = this.largura === 'celular' ? 844 : 900
            const largura = this.largura === 'celular' ? 390 : 1440
            return `height: ${Math.ceil(altura * this.escala)}px; width: ${Math.ceil(largura * this.escala)}px`
        },
        srcPara(base) {
            const url = new URL(base, window.location.origin)
            url.searchParams.set('tela', this.tela)
            return url.toString()
        },
    }"
    x-init="medir(); new ResizeObserver(() => medir()).observe($refs.moldura); $watch('largura', () => $nextTick(() => medir()))"
    class="kit-preview-das-telas"
    data-kit-preview-das-telas
>
    <div class="kit-preview-das-telas__controles">
        <label class="kit-preview-das-telas__campo">
            <span>Tela</span>
            <select x-model="tela" class="kit-preview-das-telas__select">
                @foreach ($telas as $valor => $rotulo)
                    <option value="{{ $valor }}">{{ $rotulo }}</option>
                @endforeach
            </select>
        </label>

        <button type="button" x-on:click="$wire.$refresh().then(() => $refs.quadro?.contentWindow?.location.reload())" class="kit-preview-das-telas__atualizar">Atualizar preview</button>

        <div class="kit-preview-das-telas__larguras" role="group" aria-label="Largura do preview">
            <button type="button" x-on:click="largura = 'computador'" x-bind:aria-pressed="largura === 'computador'" class="kit-preview-das-telas__largura">Computador</button>
            <button type="button" x-on:click="largura = 'celular'" x-bind:aria-pressed="largura === 'celular'" class="kit-preview-das-telas__largura">Celular</button>
        </div>
    </div>

    {{-- O quadro tem o tamanho de uma tela de verdade (1440×900 ou 390×844) e é ESCALADO para caber:
         no tamanho da coluna (~900 px) a página cairia abaixo de 1024 px e mostraria o layout de
         celular mesmo em "Computador". --}}
    <div class="kit-preview-das-telas__quadro" x-ref="moldura">
        <div class="kit-preview-das-telas__palco" x-bind:style="alturaDaMoldura">
        <iframe
            x-ref="quadro"
            wire:key="kit-preview-{{ hash('xxh128', $url) }}"
            data-base="{{ $url }}"
            x-bind:src="srcPara($el.dataset.base)"
            x-bind:style="estiloDoQuadro"
            title="Pré-visualização das telas de acesso"
            loading="lazy"
            class="kit-preview-das-telas__iframe"
        ></iframe>
        </div>
    </div>

    <p class="kit-preview-das-telas__nota">
        Pré-visualização com as alterações ainda não salvas. Os campos são de demonstração e não enviam nada. A cor dos botões do formulário segue o painel em que você está; botões de login social e o desafio anti-robô não aparecem aqui.
    </p>
</div>
