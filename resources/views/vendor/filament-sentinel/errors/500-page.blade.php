@php
    use Filament\Sentinel\Support\Sentinel;

    $code = 500;
    $number = Sentinel::messageNumber($code, $exception ?? null);
    $details = Sentinel::exceptionDetails($exception ?? null);
    $trace = Sentinel::trace($exception ?? null);
    $supportUrl = Sentinel::supportUrl();
    $copy = trim(($number ? $number.' · ' : '').__('sentinel::sentinel.labels.request_id').' '.Sentinel::requestId());
@endphp

@extends('filament-sentinel::errors.layout', [
    'code' => $code,
    'number' => $number,
    'tone' => 'danger',
    'icon' => 'heroicon-o-exclamation-triangle',
    'title' => __('sentinel::sentinel.500.title'),
    'body' => __('sentinel::sentinel.500.body'),
])

@section('content')
    @if ($details)
        <div class="sn-code">{{ $details['class'] }}
{{ $details['file'] }}:{{ $details['line'] }}</div>
    @endif

    <x-filament::section class="sn-detail">
        <dl class="sn-rows">
            <dt>{{ __('sentinel::sentinel.labels.diagnosis') }}</dt>
            <dd style="font-family:inherit">{{ __('sentinel::sentinel.500.diagnosis') }}</dd>
            <dt>{{ __('sentinel::sentinel.labels.system_response') }}</dt>
            <dd style="font-family:inherit">{{ __('sentinel::sentinel.500.response') }}</dd>
            <dt>{{ __('sentinel::sentinel.labels.procedure') }}</dt>
            <dd style="font-family:inherit">{{ __('sentinel::sentinel.500.procedure') }}</dd>
        </dl>
    </x-filament::section>

    @if ($trace)
        <details style="width:100%">
            <summary style="cursor:pointer; font-size:.82rem; font-weight:600; color:var(--gray-500)">{{ __('sentinel::sentinel.labels.stack_trace') }}</summary>
            <div class="sn-code" style="margin-top:.5rem"><strong>{{ class_basename($trace['class']) }}</strong>: {{ $trace['message'] }}
@foreach ($trace['frames'] as $i => $frame)
{{ $i + 1 }}. @if ($frame['file']){{ $frame['file'] }}:{{ $frame['line'] }}@else [internal function]@endif  {{ $frame['call'] }}
@endforeach</div>
        </details>
    @endif

    <div class="sn-actions">
        <x-filament::button tag="a" href="{{ url()->current() }}" icon="heroicon-m-arrow-path">
            {{ __('sentinel::sentinel.labels.retry') }}
        </x-filament::button>

        <x-filament::button tag="a" href="{{ Sentinel::homeUrl() }}" color="gray" icon="heroicon-m-home">
            {{ __('sentinel::sentinel.labels.go_home') }}
        </x-filament::button>

        <x-filament-sentinel::copy-button :text="$copy" />
    </div>

    @if ($supportUrl)
        <x-filament-sentinel::note color="info">
            {{ __('sentinel::sentinel.labels.support_prompt') }}
            <a href="{{ $supportUrl }}">{{ __('sentinel::sentinel.labels.support_word') }}</a>.
        </x-filament-sentinel::note>
    @endif
@endsection
