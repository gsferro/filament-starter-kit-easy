@php
    use Filament\Sentinel\Support\Sentinel;

    /** @var int $code */
    /** @var string $tone */
    /** @var string $icon */
    /** @var string $title */
    /** @var string $body */
    /** @var string|null $number */

    $requestId = Sentinel::requestId();
    $locale = str_replace('_', '-', app()->getLocale());
    $brand = Sentinel::brand();
    $logoLight = Sentinel::brandLogo(false);
    $logoDark = Sentinel::brandLogo(true);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    @include('filament-sentinel::errors.partials.head', ['code' => $code, 'brand' => $brand])

    <style>
        /* Everything below uses Filament's own colour variables so the pages
           match the panel's real theme exactly. The body background and text
           colour come from Filament's own .fi-body class. */
        :root { color-scheme: light dark; }
        body { margin: 0; }
        .sn-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.25rem; box-sizing: border-box; }
        .sn-wrap { width: 100%; max-width: 32rem; }

        .sn-card-box {
            background: #ffffff;
            border: 1px solid var(--gray-200);
            border-radius: 1rem;
            box-shadow: 0 10px 40px -14px rgba(9,9,11,.20);
            overflow: hidden;
        }
        html.dark .sn-card-box { background: var(--gray-900); border-color: var(--gray-800); box-shadow: 0 12px 44px -14px rgba(0,0,0,.6); }

        .sn-head { padding: 1.75rem 2rem 1.25rem; text-align: center; border-bottom: 1px solid var(--gray-100); }
        html.dark .sn-head { border-color: var(--gray-800); }
        .sn-logo-circle { width: 4rem; height: 4rem; margin: 0 auto .7rem; border-radius: 9999px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; }
        html.dark .sn-logo-circle { background: var(--gray-800); }
        .sn-brandmark { display: flex; align-items: center; justify-content: center; min-height: 2.25rem; }
        .sn-brandname { font-size: 1rem; font-weight: 600; color: var(--gray-700); }
        html.dark .sn-brandname { color: var(--gray-300); }
        .sn-logo { display: inline-flex; align-items: center; }
        .sn-logo-dark { display: none; }
        html.dark .sn-logo-light { display: none; }
        html.dark .sn-logo-dark { display: inline-flex; }

        .sn-content { padding: 1.75rem 2rem 2rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; text-align: center; }
        .sn-icon { width: 3.5rem; height: 3.5rem; display: flex; align-items: center; justify-content: center; border-radius: 9999px; flex: none; }
        .sn-icon[data-tone="danger"] { background: color-mix(in oklab, var(--danger-500) 12%, transparent); color: var(--danger-600); }
        html.dark .sn-icon[data-tone="danger"] { color: var(--danger-400); }
        .sn-icon[data-tone="warning"] { background: color-mix(in oklab, var(--warning-500) 12%, transparent); color: var(--warning-600); }
        html.dark .sn-icon[data-tone="warning"] { color: var(--warning-400); }
        .sn-icon[data-tone="info"] { background: color-mix(in oklab, var(--info-500) 12%, transparent); color: var(--info-600); }
        html.dark .sn-icon[data-tone="info"] { color: var(--info-400); }
        .sn-icon[data-tone="gray"] { background: color-mix(in oklab, var(--gray-500) 12%, transparent); color: var(--gray-500); }
        html.dark .sn-icon[data-tone="gray"] { color: var(--gray-400); }

        .sn-bigcode { font-family: ui-monospace, Menlo, monospace; font-size: 3.25rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; }
        .sn-bigcode[data-tone="danger"] { color: var(--danger-600); }
        html.dark .sn-bigcode[data-tone="danger"] { color: var(--danger-400); }
        .sn-bigcode[data-tone="warning"] { color: var(--warning-600); }
        html.dark .sn-bigcode[data-tone="warning"] { color: var(--warning-400); }
        .sn-bigcode[data-tone="info"] { color: var(--info-600); }
        html.dark .sn-bigcode[data-tone="info"] { color: var(--info-400); }
        .sn-bigcode[data-tone="gray"] { color: var(--gray-600); }
        html.dark .sn-bigcode[data-tone="gray"] { color: var(--gray-400); }

        .sn-title { margin: 0; font-size: 1.35rem; font-weight: 800; letter-spacing: -.02em; }
        .sn-body { margin: 0; max-width: 26rem; line-height: 1.65; color: var(--gray-500); }
        html.dark .sn-body { color: var(--gray-400); }

        .sn-actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center; }
        .sn-detail { width: 100%; text-align: start; }
        .sn-rows { display: grid; grid-template-columns: auto 1fr; gap: .55rem 1.25rem; font-size: .82rem; margin: 0; }
        .sn-rows dt { color: var(--gray-400); font-weight: 600; }
        .sn-rows dd { margin: 0; font-family: ui-monospace, Menlo, monospace; word-break: break-word; }
        .sn-mono { font-family: ui-monospace, Menlo, monospace; }

        .sn-note { display: flex; gap: .6rem; width: 100%; box-sizing: border-box; padding: .8rem .9rem; border-radius: .7rem; font-size: .82rem; line-height: 1.55; text-align: start; }
        .sn-note svg { flex: none; margin-top: .1rem; }
        .sn-note[data-tone="info"] { background: color-mix(in oklab, var(--info-500) 9%, transparent); color: var(--info-700); border: 1px solid color-mix(in oklab, var(--info-500) 20%, transparent); }
        html.dark .sn-note[data-tone="info"] { color: var(--info-300); }
        .sn-note[data-tone="warning"] { background: color-mix(in oklab, var(--warning-500) 9%, transparent); color: var(--warning-700); border: 1px solid color-mix(in oklab, var(--warning-500) 20%, transparent); }
        html.dark .sn-note[data-tone="warning"] { color: var(--warning-300); }
        .sn-note a { font-weight: 600; text-decoration: underline; }

        .sn-code {
            width: 100%; box-sizing: border-box; text-align: start;
            background: var(--gray-100); border: 1px solid var(--gray-200); border-radius: .6rem;
            padding: .75rem .9rem; font-family: ui-monospace, Menlo, monospace; font-size: .75rem;
            line-height: 1.6; color: var(--gray-500); white-space: pre-wrap; word-break: break-word;
        }
        html.dark .sn-code { background: var(--gray-950); border-color: var(--gray-800); color: var(--gray-400); }

        .sn-foot { margin-top: 1.25rem; text-align: center; display: flex; flex-direction: column; gap: .35rem; }
        .sn-copy { font-size: .78rem; color: var(--gray-400); }
        .sn-ref { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .3rem .7rem; font-size: .7rem; color: var(--gray-400); font-family: ui-monospace, Menlo, monospace; }
    </style>
</head>
<body class="fi-body">
    <div class="sn-shell">
        <div class="sn-wrap">
            <div class="sn-card-box">
                <div class="sn-head">
                    @if ($logoLight || $logoDark)
                        <div class="sn-brandmark">
                            @if ($logoLight)
                                <span class="sn-logo sn-logo-light">{!! Sentinel::logoHtml($logoLight, '2rem', $brand) !!}</span>
                            @endif
                            @if ($logoDark)
                                <span class="sn-logo sn-logo-dark">{!! Sentinel::logoHtml($logoDark, '2rem', $brand) !!}</span>
                            @endif
                        </div>
                    @else
                        <div class="sn-logo-circle">
                            @svg('heroicon-o-building-office-2', '', ['style' => 'width:1.75rem;height:1.75rem;color:#71717a'])
                        </div>
                        <div class="sn-brandname">{{ $brand }}</div>
                    @endif
                </div>

                <div class="sn-content">
                    <div class="sn-icon" data-tone="{{ $tone }}">
                        @svg($icon, '', ['style' => 'width:1.6rem;height:1.6rem'])
                    </div>

                    <div class="sn-bigcode" data-tone="{{ $tone }}">{{ $code }}</div>

                    <h1 class="sn-title">{{ $title }}</h1>
                    <p class="sn-body">{{ $body }}</p>

                    @yield('content')
                </div>
            </div>

            <div class="sn-foot">
                @if ($number || $requestId)
                    <div class="sn-ref">
                        @if ($number)
                            <span>{{ __('sentinel::sentinel.labels.message_no') }} {{ $number }}</span>
                        @endif
                        @if ($number && $requestId)
                            <span aria-hidden="true">·</span>
                        @endif
                        @if ($requestId)
                            <span>{{ __('sentinel::sentinel.labels.request_id') }} {{ $requestId }}</span>
                        @endif
                    </div>
                @endif
                <div class="sn-copy">&copy; {{ now()->year }} {{ $brand }}</div>
            </div>
        </div>
    </div>

    @filamentScripts
</body>
</html>
