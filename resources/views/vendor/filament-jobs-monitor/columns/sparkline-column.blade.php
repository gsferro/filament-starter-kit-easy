@php
    $record = $getRecord();
    $points = $record->dailyCounts(7);
    $width = 88;
    $height = 22;
    $max = max(max($points), 1);
    $step = $width / (count($points) - 1);
    $color = $record->isResolved() ? '#16a34a' : '#e11d48';
    $gradientId = 'fjm-spark-'.$record->getKey();

    $coords = [];
    foreach ($points as $i => $value) {
        $coords[] = [
            round($i * $step, 1),
            round($height - ($value / $max) * ($height - 2) - 1, 1),
        ];
    }

    $path = '';
    foreach ($coords as $i => [$x, $y]) {
        $path .= ($i === 0 ? 'M' : 'L').$x.' '.$y.' ';
    }
    $area = trim($path)." L{$width} {$height} L0 {$height} Z";
    [$lastX, $lastY] = end($coords);
@endphp

<div class="px-3 py-4" title="{{ implode(' · ', $points) }}">
    <svg width="{{ $width }}" height="{{ $height }}" style="display: block;" role="img" aria-label="{{ __('filament-jobs-monitor::translations.trend_7d') }}: {{ implode(', ', $points) }}">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="{{ $color }}" stop-opacity=".25" />
                <stop offset="100%" stop-color="{{ $color }}" stop-opacity="0" />
            </linearGradient>
        </defs>
        <path d="{{ $area }}" fill="url(#{{ $gradientId }})" />
        <path d="{{ trim($path) }}" stroke="{{ $color }}" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round" />
        <circle cx="{{ $lastX }}" cy="{{ $lastY }}" r="2" fill="{{ $color }}" />
    </svg>
</div>
