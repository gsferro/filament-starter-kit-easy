@php
    $retryAfter = null;
    if (isset($exception) && method_exists($exception, 'getHeaders')) {
        $value = $exception->getHeaders()['Retry-After'] ?? null;
        $retryAfter = is_numeric($value) ? (int) $value : null;
    }
@endphp

@extends('errors.sentinel-layout', [
    'code' => 503,
    'tone' => 'warning',
    'title' => 'Down for maintenance',
    'body' => 'The system is temporarily unavailable for maintenance or high load. We are working to restore service as quickly as possible.',
    'exception' => $exception ?? null,
])

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/></svg>
@endsection

@section('content')
    @if ($retryAfter)
        <div class="sn-detail" style="text-align:center">Estimated back in {{ $retryAfter }} s</div>
    @endif

    <div class="sn-actions">
        <a class="sn-btn sn-btn-primary" href="#" onclick="window.location.reload(); return false;">Check status</a>
        <a class="sn-btn sn-btn-gray" href="#" onclick="history.back(); return false;">Go back</a>
    </div>

    <div class="sn-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        <span>Scheduled maintenance in progress. Your data is safe and the service will be back shortly.</span>
    </div>
@endsection
