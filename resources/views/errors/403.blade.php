@php
    $auth = [];
    $user = auth()->user();
    $e = $exception ?? null;

    if ($user) {
        $auth['Account'] = $user->getAuthIdentifier();
    }

    // What you are missing beats what you already have. Spatie's
    // UnauthorizedException carries what the request wanted; a bare
    // AuthorizationException does not, so we never guess.
    $required = static function (?Throwable $e, string $method): array {
        if (! $e || ! method_exists($e, $method)) {
            return [];
        }
        try {
            return array_values(array_filter(array_map('strval', (array) $e->{$method}())));
        } catch (Throwable) {
            return [];
        }
    };

    $missing = array_values(array_filter(
        $required($e, 'getRequiredPermissions'),
        fn (string $p): bool => ! $user || ! $user->can($p),
    ));

    $missingRoles = array_values(array_filter(
        $required($e, 'getRequiredRoles'),
        fn (string $r): bool => ! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole($r),
    ));

    if ($missing) {
        $auth['Missing permission'] = implode(', ', $missing);

        // Shield's underscore style names the kind outright; anything else is
        // custom as far as a standalone view can tell.
        $auth['Guards'] = match (true) {
            str_starts_with($missing[0], 'page_') => 'Page',
            str_starts_with($missing[0], 'widget_') => 'Widget',
            default => 'Custom permission',
        };
    }

    if ($missingRoles) {
        $auth['Missing role'] = implode(', ', $missingRoles);
    }

    // Fall back to the roles held only when the denial did not name what it wanted.
    if (! $missing && ! $missingRoles && $user && method_exists($user, 'getRoleNames')) {
        $roles = $user->getRoleNames()->all();
        if ($roles) {
            $auth['Your roles'] = implode(', ', $roles);
        }
    }

    if ($e && $e->getMessage() !== '') {
        $auth['Reason'] = $e->getMessage();
    }
@endphp

@extends('errors.sentinel-layout', [
    'code' => 403,
    'tone' => 'warning',
    'title' => 'Access denied',
    'body' => 'You do not have permission to access this resource. Access was denied by a security policy or missing privileges.',
    'exception' => $exception ?? null,
])

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
@endsection

@section('content')
    @if ($auth !== [])
        <div class="sn-detail">
            <dl class="sn-rows">
                @foreach ($auth as $label => $value)
                    <dt>{{ $label }}</dt>
                    <dd class="{{ in_array($label, ['Reason', 'Guards'], true) ? '' : 'mono' }}">{{ $value }}</dd>
                @endforeach
            </dl>
        </div>
    @endif

    <div class="sn-actions">
        <a class="sn-btn sn-btn-primary" href="/">Back to safety</a>
        <a class="sn-btn sn-btn-gray" href="#" onclick="history.back(); return false;">Go back</a>
    </div>

    <div class="sn-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        <span>Need access to this resource? Ask your administrator for the right permissions.</span>
    </div>
@endsection
