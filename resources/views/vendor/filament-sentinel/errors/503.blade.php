@php
    use Filament\Sentinel\Support\Sentinel;

    $code = 503;
    $number = Sentinel::messageNumber($code, $exception ?? null);
    $retryAfter = Sentinel::retryAfter($exception ?? null);
@endphp

@extends('filament-sentinel::errors.layout', [
    'code' => $code,
    'number' => $number,
    'tone' => 'gray',
    'icon' => 'heroicon-o-wrench-screwdriver',
    'title' => __('sentinel::sentinel.503.title'),
    'body' => __('sentinel::sentinel.503.body'),
])

@section('content')
    @if ($retryAfter)
        <x-filament::badge color="warning" icon="heroicon-m-clock">
            {{ __('sentinel::sentinel.503.eta', ['seconds' => $retryAfter]) }}
        </x-filament::badge>
    @endif

    <div class="sn-actions">
        <x-filament::button tag="a" href="{{ url()->current() }}" icon="heroicon-m-arrow-path">
            {{ __('sentinel::sentinel.labels.check_status') }}
        </x-filament::button>

        <x-filament::button tag="button" type="button" color="gray" icon="heroicon-m-arrow-uturn-left" onclick="history.back()">
            {{ __('sentinel::sentinel.labels.go_back') }}
        </x-filament::button>
    </div>

    <x-filament-sentinel::note color="info" icon="heroicon-o-wrench-screwdriver">
        {{ __('sentinel::sentinel.503.note') }}
    </x-filament-sentinel::note>
@endsection
