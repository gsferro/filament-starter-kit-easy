@php
    use Illuminate\Support\Str;

    $frames = [];
    $throwFrame = null;
    $rawTrace = $lastOccurrence?->exception;

    if (filled($rawTrace)) {
        // String-cast exceptions start with "Class: message in /path/file.php:42"
        // before the "Stack trace:" block — surface that as the throw location.
        $parts = preg_split('/\r?\nStack trace:\r?\n/', $rawTrace, 2);

        if (count($parts) === 2 && preg_match('/ in (.+?):(\d+)\s*$/', trim($parts[0]), $m)) {
            $throwFrame = [
                'file' => $m[1],
                'line' => (int) $m[2],
                'vendor' => str_contains($m[1], '/vendor/'),
            ];
        }

        foreach (preg_split('/\r?\n/', $rawTrace) as $line) {
            if (preg_match('/^#(\d+)\s+(.*?)\((\d+)\): (.*)$/', $line, $m)) {
                $frames[] = [
                    'index' => (int) $m[1],
                    'file' => $m[2],
                    'line' => (int) $m[3],
                    'call' => $m[4],
                    'vendor' => str_contains($m[2], '/vendor/'),
                ];
            } elseif (preg_match('/^#(\d+)\s+(.*)$/', $line, $m)) {
                $frames[] = [
                    'index' => (int) $m[1],
                    'file' => null,
                    'line' => null,
                    'call' => $m[2],
                    'vendor' => true,
                ];
            }
        }
    }

    $vendorFramesCount = count(array_filter($frames, fn (array $frame): bool => $frame['vendor']));
@endphp

<div class="space-y-6 text-sm">
    {{-- meta strip --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            [__('filament-jobs-monitor::translations.occurrences'), number_format($group->occurrences_count)],
            [__('filament-jobs-monitor::translations.queue'), $group->queue ?? '—'],
            [__('filament-jobs-monitor::translations.first_seen'), $group->first_occurred_at?->diffForHumans() ?? '—'],
            [__('filament-jobs-monitor::translations.last_seen'), $group->last_occurred_at?->diffForHumans() ?? '—'],
        ] as [$label, $value])
            <div class="rounded-lg bg-gray-50 px-3 py-2 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $label }}</div>
                <div class="mt-0.5 truncate font-mono text-sm font-semibold text-gray-950 dark:text-white" title="{{ $value }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    @if ($group->job_class)
        <div class="font-mono text-xs text-gray-500 dark:text-gray-400">
            {{ $group->job_class }}
            @if ($group->isResolved())
                · <span class="font-medium text-success-600 dark:text-success-400">{{ __('filament-jobs-monitor::translations.resolved') }} {{ $group->resolved_at?->diffForHumans() }}</span>
            @endif
        </div>
    @endif

    {{-- stack trace --}}
    <div x-data="{ mode: 'app' }" class="overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-3 py-2 dark:border-white/10">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('filament-jobs-monitor::translations.stack_trace') }}</span>
            @if (count($frames))
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ trans_choice('filament-jobs-monitor::translations.frames_count', count($frames), ['count' => count($frames)]) }}</span>
            @endif
            <span class="ms-auto flex gap-1.5">
                @foreach (['app' => __('filament-jobs-monitor::translations.app_frames'), 'all' => __('filament-jobs-monitor::translations.all_frames'), 'raw' => __('filament-jobs-monitor::translations.raw')] as $value => $label)
                    <button
                        type="button"
                        x-on:click="mode = '{{ $value }}'"
                        x-bind:class="mode === '{{ $value }}' ? 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200' : 'text-gray-500 dark:text-gray-400'"
                        class="rounded px-2 py-0.5 text-xs font-medium"
                    >{{ $label }}</button>
                @endforeach
            </span>
        </div>

        @if (count($frames) || $throwFrame)
            <div x-show="mode !== 'raw'" class="divide-y divide-gray-100 font-mono text-xs dark:divide-white/5">
                @if ($throwFrame)
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-danger-50 dark:bg-danger-950">
                        <span class="font-semibold text-danger-600 dark:text-danger-400">!</span>
                        <span class="truncate font-medium text-gray-950 dark:text-white">{{ class_basename($group->exception_class) }}</span>
                        <span class="ms-auto truncate text-gray-500 dark:text-gray-400" title="{{ $throwFrame['file'] }}:{{ $throwFrame['line'] }}">
                            {{ $throwFrame['file'] }}<span class="text-gray-300 dark:text-gray-600">:</span><span class="text-danger-600 dark:text-danger-400">{{ $throwFrame['line'] }}</span>
                        </span>
                    </div>
                @endif

                @foreach ($frames as $frame)
                    <div
                        @if ($frame['vendor']) x-show="mode === 'all'" @endif
                        @class([
                            'flex items-center gap-2 px-3 py-1.5',
                            'bg-danger-50 dark:bg-danger-950' => ! $frame['vendor'] && $loop->first && ! $throwFrame,
                        ])
                    >
                        <span class="tabular-nums text-gray-400 dark:text-gray-500">#{{ $frame['index'] }}</span>
                        <span @class([
                            'truncate',
                            'font-medium text-gray-950 dark:text-white' => ! $frame['vendor'],
                            'text-gray-500 dark:text-gray-400' => $frame['vendor'],
                        ])>{{ $frame['call'] }}</span>
                        @if ($frame['file'])
                            <span class="ms-auto truncate text-gray-400 dark:text-gray-500" title="{{ $frame['file'] }}:{{ $frame['line'] }}">
                                {{ $frame['file'] }}<span class="text-gray-300 dark:text-gray-600">:</span><span class="text-danger-600 dark:text-danger-400">{{ $frame['line'] }}</span>
                            </span>
                        @endif
                    </div>
                @endforeach

                @if ($vendorFramesCount > 0)
                    <div x-show="mode === 'app'" class="px-3 py-2 text-gray-400 dark:text-gray-500">
                        {{ trans_choice('filament-jobs-monitor::translations.vendor_frames_hidden', $vendorFramesCount, ['count' => $vendorFramesCount]) }}
                    </div>
                @endif
            </div>

            <pre x-show="mode === 'raw'" x-cloak class="max-h-96 overflow-auto px-3 py-2 font-mono text-xs leading-relaxed text-gray-600 dark:text-gray-300">{{ $rawTrace }}</pre>
        @else
            <div class="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">
                {{ $lastOccurrence?->exception_message ?? __('filament-jobs-monitor::translations.no_stack_trace') }}
            </div>
        @endif
    </div>

    {{-- payload --}}
    <div class="overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="border-b border-gray-200 px-3 py-2 dark:border-white/10">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('filament-jobs-monitor::translations.job_payload') }}</span>
        </div>
        <div class="max-h-96 overflow-auto px-3 py-2.5 font-mono text-xs leading-relaxed">
            @if (is_array($payload) && count($payload))
                @include('filament-jobs-monitor::partials.json-tree', ['data' => $payload, 'depth' => 0])
            @else
                <span class="italic text-gray-400 dark:text-gray-500">{{ __('filament-jobs-monitor::translations.no_payload') }}</span>
            @endif
        </div>
    </div>

    {{-- recent occurrences --}}
    <div class="overflow-hidden rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="border-b border-gray-200 px-3 py-2 dark:border-white/10">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ __('filament-jobs-monitor::translations.recent_occurrences') }}</span>
        </div>
        @if ($recentOccurrences->isNotEmpty())
            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($recentOccurrences as $occurrence)
                    <div class="flex items-center gap-3 px-3 py-2 font-mono text-xs">
                        <span class="truncate text-gray-500 dark:text-gray-400" title="{{ $occurrence->job_id }}">{{ Str::limit($occurrence->job_id, 12, '…') }}</span>
                        <span class="text-gray-400 dark:text-gray-500">{{ __('filament-jobs-monitor::translations.attempts') }} {{ $occurrence->attempt }}</span>
                        @if ($occurrence->queue)
                            <span class="text-gray-400 dark:text-gray-500">{{ $occurrence->queue }}</span>
                        @endif
                        <span class="ms-auto whitespace-nowrap text-gray-500 dark:text-gray-400">{{ $occurrence->started_at?->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-3 py-3 text-xs italic text-gray-400 dark:text-gray-500">—</div>
        @endif
    </div>
</div>
