{{--
    Gatilho visível da busca ⌘K.

    O painel desliga a busca nativa do Filament (`disableDefaultGlobalSearch()`)
    para não ter dois campos disputando a mesma tecla — mas sem um gatilho o
    recurso fica invisível: só descobre quem já sabe do atalho. Este botão
    dispara o mesmo evento que o overlay do pacote escuta (`open-spotlight`).
--}}
<button
    type="button"
    x-data
    x-on:click="window.dispatchEvent(new CustomEvent('open-spotlight'))"
    title="Buscar (Ctrl/⌘ + K)"
    class="fi-icon-btn relative flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-500 outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
>
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
    </svg>

    <span class="hidden md:inline">Buscar</span>

    <kbd class="hidden rounded border border-gray-300 px-1.5 py-0.5 font-sans text-xs text-gray-400 md:inline dark:border-white/10 dark:text-gray-500">
        ⌘K
    </kbd>
</button>
