@php
    $depth = $depth ?? 0;
@endphp

<div @class(['ps-4' => $depth > 0])>
    @forelse ($data as $key => $value)
        @if (is_array($value))
            <details @if($depth < 1) open @endif>
                <summary class="cursor-pointer select-none text-gray-700 dark:text-gray-200">
                    {{ $key }} <span class="text-gray-400 dark:text-gray-500">{{ '{'.count($value).'}' }}</span>
                </summary>
                @include('filament-jobs-monitor::partials.json-tree', ['data' => $value, 'depth' => $depth + 1])
            </details>
        @else
            <div class="flex items-baseline gap-1.5">
                <span class="text-gray-700 dark:text-gray-200">{{ $key }}<span class="text-gray-400">:</span></span>
                @if (is_null($value))
                    <span class="italic text-gray-400 dark:text-gray-500">null</span>
                @elseif (is_bool($value))
                    <span class="text-warning-600 dark:text-warning-400">{{ $value ? 'true' : 'false' }}</span>
                @elseif (is_numeric($value))
                    <span class="text-primary-600 dark:text-primary-400">{{ $value }}</span>
                @else
                    <span class="break-all text-success-700 dark:text-success-400">"{{ \Illuminate\Support\Str::limit((string) $value, 200) }}"</span>
                @endif
            </div>
        @endif
    @empty
        <span class="italic text-gray-400 dark:text-gray-500">{{ '{}' }}</span>
    @endforelse
</div>
