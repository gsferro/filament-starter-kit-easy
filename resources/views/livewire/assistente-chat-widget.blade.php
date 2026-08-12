{{--
    Widget de chat do Assistente. Botão flutuante + slide-over presentes em toda tela do
    painel (render hook BODY_END). A saída do LLM é conteúdo NÃO confiável: markdown com HTML
    escapado (`html_input: escape`), nunca `{!! !!}` sobre texto cru do modelo.
--}}
<div>
    @if ($disponivel)
        <div
            x-data="{ aberto: @entangle('aberto') }"
            @keydown.escape.window="aberto = false"
        >
            {{-- Botão flutuante. Ocupa o canto inferior direito; se o seu painel já tiver algo
                 ali (botão "voltar ao topo", chat de suporte), ajuste o `bottom-*` aqui. --}}
            <button
                type="button"
                x-show="!aberto"
                x-cloak
                @click="aberto = true; $nextTick(() => $refs.entrada?.focus())"
                aria-label="Abrir assistente"
                class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-5 6l-4 1 1-4V6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H9l-1 4z" />
                </svg>
            </button>

            {{-- Slide-over --}}
            <section
                x-show="aberto"
                x-cloak
                x-transition.opacity
                role="dialog"
                aria-label="Assistente"
                class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900"
            >
                {{-- Header --}}
                <header class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-white/10">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Assistente</h2>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            wire:click="novaConversa"
                            @click="$refs.entrada?.focus()"
                            class="rounded-md px-2 py-1 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-400/10 focus:outline-none focus:ring-2 focus:ring-primary-600"
                        >
                            Nova conversa
                        </button>
                        <button
                            type="button"
                            @click="aberto = false"
                            aria-label="Fechar assistente"
                            class="rounded-md p-1 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:text-gray-400 dark:hover:bg-white/5"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </header>

                {{-- Histórico de conversas do próprio usuário --}}
                @if ($historico->isNotEmpty())
                    <details class="border-b border-gray-200 px-4 py-2 text-sm dark:border-white/10">
                        <summary class="cursor-pointer font-medium text-gray-700 dark:text-gray-300">Conversas anteriores</summary>
                        <ul class="mt-2 space-y-1">
                            @foreach ($historico as $conversa)
                                <li
                                    wire:key="conversa-{{ $conversa->id }}"
                                    x-data="{ editando: false, titulo: @js($conversa->title) }"
                                    class="flex items-center gap-1"
                                >
                                    <template x-if="!editando">
                                        <div class="flex min-w-0 flex-1 items-center gap-1">
                                            <button
                                                type="button"
                                                wire:click="retomarConversa('{{ $conversa->id }}')"
                                                {{-- $refs não atravessa o x-data do <li>; o id do textarea resolve --}}
                                                @click="document.getElementById('assistente-mensagem')?.focus()"
                                                class="flex min-w-0 flex-1 items-center justify-between gap-2 rounded-md px-2 py-1 text-left hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:hover:bg-white/5"
                                            >
                                                <span class="truncate text-gray-800 dark:text-gray-200">{{ $conversa->title }}</span>
                                                <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ $conversa->updated_at?->diffForHumans() }}</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="editando = true; $nextTick(() => $refs.tituloInput.focus())"
                                                aria-label="Renomear conversa"
                                                class="shrink-0 rounded-md p-1 text-gray-500 hover:bg-gray-100 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:hover:bg-white/5"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.1 2.1 0 112.97 2.97L7.5 19.79 3 21l1.21-4.5L16.862 4.487z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="editando">
                                        <div class="flex min-w-0 flex-1 items-center gap-1">
                                            <input
                                                type="text"
                                                x-ref="tituloInput"
                                                x-model="titulo"
                                                maxlength="100"
                                                aria-label="Novo título da conversa"
                                                @keydown.enter.prevent="$wire.renomearConversa('{{ $conversa->id }}', titulo); editando = false"
                                                @keydown.escape.stop="titulo = @js($conversa->title); editando = false"
                                                class="min-w-0 flex-1 rounded-md border border-gray-300 px-2 py-1 text-sm text-gray-900 focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                                            />
                                            <button
                                                type="button"
                                                @click="$wire.renomearConversa('{{ $conversa->id }}', titulo); editando = false"
                                                aria-label="Salvar título"
                                                class="shrink-0 rounded-md px-2 py-1 text-xs font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-400/10 focus:outline-none focus:ring-2 focus:ring-primary-600"
                                            >
                                                Salvar
                                            </button>
                                            <button
                                                type="button"
                                                @click="titulo = @js($conversa->title); editando = false"
                                                aria-label="Cancelar renomeação"
                                                class="shrink-0 rounded-md px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:hover:bg-white/5"
                                            >
                                                Cancelar
                                            </button>
                                        </div>
                                    </template>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                {{-- Corpo: mensagens (o auto-scroll cobre re-renders e deltas do wire:stream) --}}
                <div
                    x-init="new MutationObserver(() => $el.scrollTop = $el.scrollHeight).observe($el, { childList: true, subtree: true, characterData: true })"
                    class="flex-1 space-y-4 overflow-y-auto px-4 py-4"
                >
                    @forelse ($mensagens as $mensagem)
                        {{-- Wrapper sem fundo: a bolha é o filho, a hora é hint FORA dela. --}}
                        <div wire:key="msg-{{ $mensagem->id }}" @class([
                            'ml-8' => $mensagem->role === 'user',
                            'mr-8' => $mensagem->role === 'assistant',
                        ])>
                            <div @class([
                                'rounded-lg px-3 py-2 text-sm',
                                'bg-primary-50 text-gray-900 dark:bg-primary-400/10 dark:text-gray-100' => $mensagem->role === 'user',
                                'bg-gray-100 text-gray-900 dark:bg-white/5 dark:text-gray-100' => $mensagem->role === 'assistant',
                            ])>
                                @if ($mensagem->role === 'assistant')
                                    {{-- `soft_break`: sem ele o CommonMark colapsa quebra simples em espaço e o
                                         texto que chegou quebrado durante o streaming vira um bloco corrido ao
                                         persistir. `resposta-md` é a classe de estilo local (definida no
                                         @assets abaixo) — não depende do plugin @tailwindcss/typography. --}}
                                    <div class="resposta-md">
                                        {!! \Illuminate\Support\Str::markdown($mensagem->content, [
                                            'html_input'         => 'escape',
                                            'allow_unsafe_links' => false,
                                            'renderer'           => ['soft_break' => "<br>\n"],
                                        ]) !!}
                                    </div>
                                @else
                                    {{ $mensagem->content }}
                                @endif
                            </div>

                            {{-- Hora do banco; alinhada ao lado da própria bolha. --}}
                            <time
                                @class(['chat-hora', 'chat-hora-fim' => $mensagem->role === 'user'])
                                datetime="{{ $mensagem->created_at?->toIso8601String() }}"
                                title="{{ $mensagem->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}"
                            >{{ $mensagem->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                        </div>
                    @empty
                        @if ($mensagemPendente === null)
                            <p class="px-1 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Pergunte sobre o uso da aplicação — o assistente responde com o que as
                                ferramentas liberadas conseguem consultar.
                            </p>
                        @endif
                    @endforelse

                    {{-- Bolha otimista: mensagem do usuário antes da persistência do vendor. Hora
                         local porque ainda não existe linha no banco. --}}
                    @if ($mensagemPendente !== null)
                        <div class="ml-8">
                            <div class="rounded-lg bg-primary-50 px-3 py-2 text-sm text-gray-900 dark:bg-primary-400/10 dark:text-gray-100">{{ $mensagemPendente }}</div>
                            <time class="chat-hora chat-hora-fim" title="{{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}">{{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
                        </div>
                    @endif

                    {{-- Resposta em andamento (streaming ao vivo). `chat-stream` esconde a bolha
                         enquanto ela está vazia: sem isso fica um retângulo cinza vazio ao lado da
                         espera durante o turno inteiro. --}}
                    <div
                        wire:stream="resposta-parcial"
                        wire:loading.class.remove="hidden"
                        wire:target="responder"
                        class="chat-stream mr-8 hidden whitespace-pre-wrap rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-900 dark:bg-white/5 dark:text-gray-100"
                    ></div>

                    {{-- Espera: spinner + mensagem rotativa + caret. Com inferência local um turno
                         pode levar de 30s a 75s, então o feedback precisa ser mais que um texto
                         fixo. `aria-live` para leitor de tela. A visibilidade é do Alpine, não do
                         `wire:loading`: o loading só desliga no fim do request, e a espera precisa
                         sair no PRIMEIRO token do stream. --}}
                    <div
                        class="flex items-center gap-2 px-1 text-xs text-gray-500 dark:text-gray-400"
                        x-data="assistenteAguardando()"
                        x-show="aguardando"
                        x-cloak
                        role="status"
                        aria-live="polite"
                    >
                        <span class="chat-spinner" aria-hidden="true"></span>
                        <span><span x-text="mensagem">Processando…</span><span class="chat-caret" aria-hidden="true">▋</span></span>
                    </div>

                    {{-- Nível 1: recusa por guardrail — causa honesta, não "indisponível" --}}
                    @if ($recusa !== null)
                        <p role="alert" class="rounded-lg bg-warning-50 px-3 py-2 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                            {{ $recusa }}
                        </p>
                    @endif

                    {{-- Nível 2: indisponibilidade honesta (nunca resposta inventada) --}}
                    @if ($indisponivel)
                        <p role="alert" class="rounded-lg bg-danger-50 px-3 py-2 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">
                            Assistente indisponível no momento, tente mais tarde.
                        </p>
                    @endif
                </div>

                {{-- Input --}}
                <form wire:submit="enviar" class="border-t border-gray-200 p-3 dark:border-white/10">
                    <label for="assistente-mensagem" class="sr-only">Sua pergunta</label>
                    <div class="flex items-end gap-2">
                        <textarea
                            id="assistente-mensagem"
                            x-ref="entrada"
                            wire:model="mensagem"
                            rows="2"
                            maxlength="2000"
                            placeholder="Digite sua pergunta…"
                            {{-- `wire:model` é um x-model sobre $wire: `$wire.mensagem` já está atualizado
                                 no browser a cada tecla (o que é diferido é só o request). Por isso o
                                 Enter e o botão conseguem barrar o envio vazio sem roundtrip. --}}
                            @keydown.enter="$event.shiftKey || ($event.preventDefault(), $wire.mensagem.trim() && $wire.enviar())"
                            wire:loading.attr="disabled"
                            wire:target="responder"
                            class="flex-1 resize-none rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-primary-600 focus:outline-none focus:ring-1 focus:ring-primary-600 dark:border-white/10 dark:bg-gray-800 dark:text-white"
                        ></textarea>
                        {{-- `:disabled` cobre os dois casos e substitui o wire:loading.attr: durante o
                             turno `mensagem` está vazia (o enviar() limpa), então o botão já sai
                             desabilitado. Misturar os dois deixaria o botão habilitado com campo vazio
                             ao fim do request, porque o wire:loading remove o atributo sem o Alpine
                             reavaliar. --}}
                        <button
                            type="submit"
                            :disabled="! $wire.mensagem.trim()"
                            class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Enviar
                        </button>
                    </div>
                    @error('mensagem')
                        <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @enderror
                </form>
            </section>
        </div>
    @endif
</div>

@assets
    {{-- CSS local do widget: fica aqui (e não no theme do painel) para o chat funcionar em
         qualquer painel sem passo de build. --}}
    <style>
        .resposta-md > :first-child { margin-top: 0; }
        .resposta-md > :last-child { margin-bottom: 0; }
        .resposta-md p { margin-block: 0.5rem; }
        .resposta-md strong { font-weight: 600; }
        .resposta-md em { font-style: italic; }
        .resposta-md ul,
        .resposta-md ol { margin-block: 0.5rem; padding-left: 1.25rem; }
        .resposta-md ul { list-style: disc; }
        .resposta-md ol { list-style: decimal; }
        .resposta-md li { margin-block: 0.125rem; }
        .resposta-md li > ul,
        .resposta-md li > ol { margin-block: 0.125rem; }
        .resposta-md h1,
        .resposta-md h2,
        .resposta-md h3,
        .resposta-md h4 { margin-block: 0.75rem 0.25rem; font-weight: 600; font-size: 1em; }
        .resposta-md a { text-decoration: underline; text-underline-offset: 2px; }
        .resposta-md code {
            padding: 0.1em 0.3em;
            border-radius: 0.25rem;
            background-color: color-mix(in oklab, currentColor 10%, transparent);
            font-size: 0.9em;
        }
        .resposta-md pre {
            margin-block: 0.5rem;
            padding: 0.5rem 0.75rem;
            overflow-x: auto;
            border-radius: 0.375rem;
            background-color: color-mix(in oklab, currentColor 8%, transparent);
        }
        .resposta-md pre code { padding: 0; background: none; }
        .resposta-md blockquote {
            margin-block: 0.5rem;
            padding-left: 0.75rem;
            border-left: 2px solid color-mix(in oklab, currentColor 25%, transparent);
        }

        /* Hora como hint FORA da bolha, logo abaixo dela. Assistente à esquerda (default),
           usuário à direita (`.chat-hora-fim`). */
        .chat-hora {
            display: block;
            margin-top: 0.1875rem;
            padding-inline: 0.25rem;
            font-size: 0.625rem;
            line-height: 1;
            text-align: left;
            opacity: 0.55;
        }

        .chat-hora-fim { text-align: right; }

        /* Bolha do streaming: enquanto não chega token, não existe bolha. */
        .chat-stream:empty { display: none; }

        .chat-spinner {
            width: 0.875rem;
            height: 0.875rem;
            flex-shrink: 0;
            border: 2px solid color-mix(in oklab, currentColor 25%, transparent);
            border-top-color: var(--primary-500, currentColor);
            border-radius: 50%;
            animation: chat-spin 0.7s linear infinite;
        }

        @keyframes chat-spin { to { transform: rotate(360deg); } }

        .chat-caret {
            display: inline-block;
            width: 0.5rem;
            margin-left: 1px;
            animation: chat-blink 1s step-end infinite;
        }

        @keyframes chat-blink { 50% { opacity: 0; } }

        /* A11Y: quem pediu menos movimento recebe spinner lento e nenhum piscar. */
        @media (prefers-reduced-motion: reduce) {
            .chat-spinner { animation-duration: 2s; }
            .chat-caret { animation: none; }
        }
    </style>

    <script>
        // Mensagens rotativas enquanto o modelo processa. Descrevem a INTENÇÃO do turno — nunca
        // um progresso falso em porcentagem, porque o widget não sabe em que passo o agente
        // está. Os timers morrem com o componente via destroy().
        function assistenteAguardando() {
            const frases = [
                'Consultando as informações…',
                'Verificando as suas permissões…',
                'Conferindo os dados cadastrados…',
                'Organizando a resposta…',
            ];

            const semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            return {
                aguardando: false,
                mensagem: '',
                _atual: -1,
                _proxima: null,
                _digitador: null,

                // Dois sinais delimitam a espera, e nenhum deles é o wire:loading:
                // `mensagemPendente` deixar de ser null é o começo do turno (fase 1 do
                // two-phase), e o primeiro delta do wire:stream é o fim da espera — dali em
                // diante o usuário já está lendo a resposta. Voltar a null (fim, recusa ou
                // falha) também para, então nenhum caminho deixa a espera presa na tela.
                init() {
                    this.$wire.$watch('mensagemPendente', (pendente) => {
                        pendente === null ? this.parar() : this.iniciar();
                    });

                    Livewire.hook('stream', ({ name }) => {
                        if (name === 'resposta-parcial') {
                            this.parar();
                        }
                    });
                },

                iniciar() {
                    this.aguardando = true;
                    this.sortear();

                    if (! semMovimento) {
                        this.agendar();
                    }
                },

                parar() {
                    this.aguardando = false;
                    clearTimeout(this._proxima);
                    clearInterval(this._digitador);
                },

                // Intervalo com jitter (~9–12s): sem sincronia visível entre turnos.
                agendar() {
                    this._proxima = setTimeout(() => {
                        this.sortear();
                        this.agendar();
                    }, 9000 + Math.random() * 3000);
                },

                // Sorteia uma frase diferente da atual e a digita.
                sortear() {
                    let indice;

                    do {
                        indice = Math.floor(Math.random() * frases.length);
                    } while (frases.length > 1 && indice === this._atual);

                    this._atual = indice;
                    this.digitar(frases[indice]);
                },

                // Digitação char-a-char: dá a sensação de processamento em curso, não de tela
                // congelada — a espera real pode passar de um minuto. Instantâneo quando o
                // sistema pede menos movimento.
                digitar(texto) {
                    clearInterval(this._digitador);

                    if (semMovimento) {
                        this.mensagem = texto;

                        return;
                    }

                    this.mensagem = '';
                    let i = 0;

                    this._digitador = setInterval(() => {
                        this.mensagem = texto.slice(0, ++i);

                        if (i >= texto.length) {
                            clearInterval(this._digitador);
                        }
                    }, 45);
                },

                destroy() {
                    this.parar();
                },
            };
        }
    </script>
@endassets
