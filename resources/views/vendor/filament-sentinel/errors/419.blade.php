@php
    use Filament\Sentinel\Support\Sentinel;
    use Illuminate\Support\Facades\Route;

    $code = 419;
    $number = Sentinel::messageNumber($code, $exception ?? null);
    $loginUrl = Route::has('login') ? route('login') : Sentinel::homeUrl();
@endphp

@extends('filament-sentinel::errors.layout', [
    'code' => $code,
    'number' => $number,
    'tone' => 'info',
    'icon' => 'heroicon-o-clock',
    'title' => __('sentinel::sentinel.419.title'),
    'body' => __('sentinel::sentinel.419.body'),
])

@section('content')
    <div class="sn-actions">
        <x-filament::button tag="a" href="{{ $loginUrl }}" icon="heroicon-m-arrow-right-end-on-rectangle">
            {{ __('sentinel::sentinel.419.relogin') }}
        </x-filament::button>

        <x-filament::button tag="a" href="{{ url()->current() }}" color="gray" icon="heroicon-m-arrow-path">
            {{ __('sentinel::sentinel.labels.reload') }}
        </x-filament::button>
    </div>

    <x-filament-sentinel::note color="info" icon="heroicon-o-shield-check">
        {{ __('sentinel::sentinel.419.note') }}
    </x-filament-sentinel::note>
@endsection
