@php
    $root = function ($e) {
        while ($e && $e->getPrevious()) {
            $e = $e->getPrevious();
        }

        return $e;
    };

    $ex = (config('app.debug') && isset($exception)) ? $root($exception) : null;
    $base = base_path().DIRECTORY_SEPARATOR;

    $frames = [];
    if ($ex) {
        foreach (array_slice($ex->getTrace(), 0, 12) as $f) {
            $frames[] = [
                'file' => isset($f['file']) ? str_replace($base, '', $f['file']) : null,
                'line' => $f['line'] ?? null,
                'call' => ($f['class'] ?? '').($f['type'] ?? '').($f['function'] ?? '').'()',
            ];
        }
    }
@endphp

@extends('errors.sentinel-layout', [
    'code' => 500,
    'tone' => 'danger',
    'title' => 'Something went wrong',
    'body' => 'An unexpected error interrupted your request.',
    'exception' => $exception ?? null,
])

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
@endsection

@section('content')
    @if ($ex)
        <div class="sn-codeblock">{{ get_class($ex) }}
{{ str_replace($base, '', $ex->getFile()) }}:{{ $ex->getLine() }}</div>
    @endif

    <div class="sn-detail">
        <dl class="sn-rows">
            <dt>Diagnosis</dt>
            <dd>The application hit an unexpected error while handling your request.</dd>
            <dt>System response</dt>
            <dd>Nothing was saved — the operation was rolled back safely.</dd>
            <dt>What to do</dt>
            <dd>Try again in a moment. If it keeps happening, quote the message number below to support.</dd>
        </dl>
    </div>

    @if ($ex)
        <details>
            <summary>Stack trace</summary>
            <div class="sn-codeblock" style="margin-top:.5rem"><strong>{{ class_basename($ex) }}</strong>: {{ $ex->getMessage() }}
@foreach ($frames as $i => $f)
{{ $i + 1 }}. @if ($f['file']){{ $f['file'] }}:{{ $f['line'] }}@else [internal]@endif  {{ $f['call'] }}
@endforeach</div>
        </details>
    @endif

    <div class="sn-actions">
        <a class="sn-btn sn-btn-primary" href="{{ url()->current() }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m11.667-6.667a8.25 8.25 0 0 0-13.803-3.7L2.985 8.649m0 0V4.356m0 4.293h4.293"/></svg>
            Try again
        </a>
        <a class="sn-btn sn-btn-gray" href="/">Back to safety</a>
    </div>
@endsection
