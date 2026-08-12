<x-filament-panels::page>
    @php($results = $this->getCheckResults())

    @if (empty($results))
        <x-filament::section>
            Nenhum resultado armazenado ainda. Clique em "Executar checks".
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($results as $result)
                <x-filament::section>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold">{{ $result['label'] ?? $result['name'] }}</span>
                        <x-filament::badge :color="match ($result['status'] ?? 'skipped') {
                            'ok' => 'success',
                            'warning' => 'warning',
                            'failed', 'crashed' => 'danger',
                            default => 'gray',
                        }">
                            {{ $result['status'] ?? '—' }}
                        </x-filament::badge>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ $result['notificationMessage'] ?: ($result['shortSummary'] ?? '') }}
                    </p>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
