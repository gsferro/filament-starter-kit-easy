@php
    use Filament\Sentinel\Support\Sentinel;

    $code = 403;
    $number = Sentinel::messageNumber($code, $exception ?? null);
    $auth = Sentinel::authorization($exception ?? null);
    $supportUrl = Sentinel::supportUrl();
    $copy = trim(($number ? $number.' · ' : '').__('sentinel::sentinel.labels.request_id').' '.Sentinel::requestId());
@endphp

@extends('filament-sentinel::errors.layout', [
    'code' => $code,
    'number' => $number,
    'tone' => 'warning',
    'icon' => 'heroicon-o-lock-closed',
    'title' => __('sentinel::sentinel.403.title'),
    'body' => __('sentinel::sentinel.403.body'),
])

@section('content')
    @if ($auth !== [])
        <x-filament::section class="sn-detail">
            <dl class="sn-rows">
                @isset($auth['guard'])
                    <dt>{{ __('sentinel::sentinel.labels.guard') }}</dt>
                    <dd>{{ $auth['guard'] }}</dd>
                @endisset
                @isset($auth['user'])
                    <dt>{{ __('sentinel::sentinel.labels.account') }}</dt>
                    <dd>{{ $auth['user'] }}</dd>
                @endisset
                @isset($auth['missing_permissions'])
                    <dt>{{ trans_choice('sentinel::sentinel.labels.missing_permissions', count($auth['missing_permissions'])) }}</dt>
                    <dd>{{ implode(', ', $auth['missing_permissions']) }}</dd>
                @endisset
                @isset($auth['kind'])
                    <dt>{{ __('sentinel::sentinel.labels.permission_kind') }}</dt>
                    <dd style="font-family:inherit">{{ __('sentinel::sentinel.kinds.'.$auth['kind']) }}</dd>
                @endisset
                @isset($auth['missing_roles'])
                    <dt>{{ trans_choice('sentinel::sentinel.labels.missing_roles', count($auth['missing_roles'])) }}</dt>
                    <dd>{{ implode(', ', $auth['missing_roles']) }}</dd>
                @endisset
                @isset($auth['roles'])
                    <dt>{{ __('sentinel::sentinel.labels.roles') }}</dt>
                    <dd>{{ implode(', ', $auth['roles']) }}</dd>
                @endisset
                @isset($auth['reason'])
                    <dt>{{ __('sentinel::sentinel.labels.reason') }}</dt>
                    <dd style="font-family:inherit">{{ $auth['reason'] }}</dd>
                @endisset
            </dl>
        </x-filament::section>
    @endif

    <div class="sn-actions">
        <x-filament::button tag="a" href="{{ Sentinel::homeUrl() }}" icon="heroicon-m-home">
            {{ __('sentinel::sentinel.labels.go_home') }}
        </x-filament::button>

        <x-filament::button tag="button" type="button" color="gray" icon="heroicon-m-arrow-uturn-left" onclick="history.back()">
            {{ __('sentinel::sentinel.labels.go_back') }}
        </x-filament::button>

        <x-filament-sentinel::copy-button :text="$copy" />
    </div>

    <x-filament-sentinel::note color="info">
        {{ __('sentinel::sentinel.403.note') }}
        @if ($supportUrl)
            {{ __('sentinel::sentinel.labels.support_prompt') }}
            <a href="{{ $supportUrl }}">{{ __('sentinel::sentinel.labels.support_word') }}</a>.
        @endif
    </x-filament-sentinel::note>
@endsection
