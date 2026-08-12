@props([
    'color' => 'info',
    'icon' => 'heroicon-o-information-circle',
])

<div class="sn-note" data-tone="{{ $color }}">
    @svg($icon, '', ['style' => 'width:1.1rem;height:1.1rem'])
    <div>{{ $slot }}</div>
</div>
