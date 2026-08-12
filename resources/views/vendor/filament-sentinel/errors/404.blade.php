@php
    use Filament\Sentinel\Support\Sentinel;

    $code = 404;
    $number = Sentinel::messageNumber($code, $exception ?? null);
    $path = '/'.ltrim(request()->path(), '/');
@endphp

@extends('filament-sentinel::errors.layout', [
    'code' => $code,
    'number' => $number,
    'tone' => 'gray',
    'icon' => 'heroicon-o-magnifying-glass',
    'title' => __('sentinel::sentinel.404.title'),
    'body' => __('sentinel::sentinel.404.body'),
])

@section('content')
    <x-filament::badge color="gray" class="sn-mono">{{ $path }}</x-filament::badge>

    <div class="sn-actions">
        <x-filament::button tag="a" href="{{ Sentinel::homeUrl() }}" icon="heroicon-m-home">
            {{ __('sentinel::sentinel.labels.go_home') }}
        </x-filament::button>

        <x-filament::button tag="button" type="button" color="gray" icon="heroicon-m-arrow-uturn-left" onclick="history.back()">
            {{ __('sentinel::sentinel.labels.go_back') }}
        </x-filament::button>
    </div>
@endsection
