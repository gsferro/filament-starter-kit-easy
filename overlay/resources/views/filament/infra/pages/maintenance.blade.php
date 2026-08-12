<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->commands() as $command => $description)
            <x-filament::section>
                <div class="flex flex-col gap-3">
                    <div>
                        <p class="font-semibold">{{ $description }}</p>
                        <code class="text-xs text-gray-500 dark:text-gray-400">php artisan {{ $command }}</code>
                    </div>
                    <div>
                        <x-filament::button wire:click="run('{{ $command }}')" size="sm" outlined>
                            Executar
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
