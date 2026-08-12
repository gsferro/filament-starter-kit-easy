<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col gap-4">
            <div class="flex max-h-[28rem] flex-col gap-3 overflow-y-auto">
                @forelse ($this->messages as $message)
                    <div @class([
                        'rounded-lg p-3 text-sm whitespace-pre-wrap',
                        'bg-primary-50 dark:bg-primary-500/10 self-end' => $message['role'] === 'user',
                        'bg-gray-50 dark:bg-white/5 self-start' => $message['role'] !== 'user',
                    ])>
                        {{ $message['content'] }}
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Envie uma mensagem para começar. Configure o provedor de IA no arquivo <code>.env</code>.
                    </p>
                @endforelse
            </div>

            <form wire:submit="send" class="flex items-end gap-2">
                <div class="flex-1">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="prompt"
                            placeholder="Pergunte algo ao assistente..."
                        />
                    </x-filament::input.wrapper>
                </div>
                <x-filament::button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>Enviar</span>
                    <span wire:loading>Pensando...</span>
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>
</x-filament-panels::page>
