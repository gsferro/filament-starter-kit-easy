{{--
    Self-contained Sentinel error layout.

    Published by `php artisan vendor:publish --tag=sentinel-errors` into
    resources/views/errors/. It has NO dependency on the filament-sentinel
    package (or even on Filament) — only Laravel core — so these pages keep
    working after the plugin is removed. Edit freely; it is now yours.

    @var int         $code
    @var string      $tone   danger | warning | info | gray
    @var string      $title
    @var string      $body
    @var \Throwable|null $exception
--}}
@php
    $root = function ($e) {
        while ($e && $e->getPrevious()) {
            $e = $e->getPrevious();
        }

        return $e;
    };

    $rootException = isset($exception) ? $root($exception) : null;

    $signatureSeed = $rootException
        ? get_class($rootException).'|'.$rootException->getFile().'|'.$rootException->getLine()
        : (string) $code;
    $number = 'SNT-'.$code.'-'.str_pad((string) (crc32($signatureSeed) % 1000), 3, '0', STR_PAD_LEFT);

    $requestId = request()->headers->get('X-Request-Id')
        ?? substr(str_replace('-', '', (string) \Illuminate\Support\Str::uuid()), 0, 12);

    $brand = config('app.name', 'Laravel');
    $locale = str_replace('_', '-', app()->getLocale());
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} · {{ $brand }}</title>

    <script>
        // Match a Filament panel's light/dark choice if it is still around;
        // harmless (defaults to light) if it is not.
        try {
            const theme = localStorage.getItem('theme') ?? 'system'
            if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            }
        } catch (e) {}
    </script>

    <style>
        :root {
            --bg: #f4f4f5; --card: #ffffff; --border: #e4e4e7; --border-soft: #f1f1f3;
            --text: #18181b; --muted: #71717a; --faint: #a1a1aa;
            --code-bg: #f4f4f5; --code-text: #52525b;
            --danger: #dc2626; --danger-soft: rgba(220,38,38,.10);
            --warning: #d97706; --warning-soft: rgba(217,119,6,.10);
            --info: #2563eb; --info-soft: rgba(37,99,235,.10); --info-border: rgba(37,99,235,.22);
            --gray-soft: rgba(113,113,122,.12);
            --accent: #f59e0b; --accent-ink: #442c05;
        }
        html.dark {
            --bg: #09090b; --card: #18181b; --border: #27272a; --border-soft: #27272a;
            --text: #fafafa; --muted: #a1a1aa; --faint: #71717a;
            --code-bg: #09090b; --code-text: #a1a1aa;
            --danger: #f87171; --danger-soft: rgba(248,113,113,.12);
            --warning: #fbbf24; --warning-soft: rgba(251,191,36,.12);
            --info: #60a5fa; --info-soft: rgba(96,165,250,.12); --info-border: rgba(96,165,250,.28);
            --gray-soft: rgba(161,161,170,.14);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .sn-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.25rem; }
        .sn-wrap { width: 100%; max-width: 32rem; }
        .sn-card { background: var(--card); border: 1px solid var(--border); border-radius: 1rem; box-shadow: 0 10px 40px -14px rgba(9,9,11,.2); overflow: hidden; }
        html.dark .sn-card { box-shadow: 0 12px 44px -14px rgba(0,0,0,.6); }

        .sn-head { padding: 1.6rem 2rem 1.15rem; text-align: center; border-bottom: 1px solid var(--border-soft); }
        .sn-brand { font-size: 1rem; font-weight: 700; color: var(--text); letter-spacing: -.01em; }

        .sn-content { padding: 1.75rem 2rem 2rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; text-align: center; }
        .sn-icon { width: 3.5rem; height: 3.5rem; display: flex; align-items: center; justify-content: center; border-radius: 9999px; }
        .sn-icon svg { width: 1.6rem; height: 1.6rem; }
        .sn-icon[data-tone="danger"] { background: var(--danger-soft); color: var(--danger); }
        .sn-icon[data-tone="warning"] { background: var(--warning-soft); color: var(--warning); }
        .sn-icon[data-tone="info"] { background: var(--info-soft); color: var(--info); }
        .sn-icon[data-tone="gray"] { background: var(--gray-soft); color: var(--muted); }

        .sn-code-big { font-family: ui-monospace, Menlo, monospace; font-size: 3.25rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; }
        .sn-code-big[data-tone="danger"] { color: var(--danger); }
        .sn-code-big[data-tone="warning"] { color: var(--warning); }
        .sn-code-big[data-tone="info"] { color: var(--info); }
        .sn-code-big[data-tone="gray"] { color: var(--muted); }

        .sn-title { margin: 0; font-size: 1.35rem; font-weight: 800; letter-spacing: -.02em; }
        .sn-body { margin: 0; max-width: 26rem; line-height: 1.65; color: var(--muted); }

        .sn-detail { width: 100%; text-align: start; border: 1px solid var(--border); border-radius: .75rem; padding: 1rem 1.1rem; }
        .sn-rows { display: grid; grid-template-columns: auto 1fr; gap: .55rem 1.25rem; font-size: .82rem; margin: 0; }
        .sn-rows dt { color: var(--faint); font-weight: 600; }
        .sn-rows dd { margin: 0; word-break: break-word; }
        .sn-rows dd.mono { font-family: ui-monospace, Menlo, monospace; }

        .sn-codeblock { width: 100%; text-align: start; background: var(--code-bg); border: 1px solid var(--border); border-radius: .6rem; padding: .75rem .9rem; font-family: ui-monospace, Menlo, monospace; font-size: .75rem; line-height: 1.6; color: var(--code-text); white-space: pre-wrap; word-break: break-word; }
        .sn-codeblock strong { color: var(--danger); }

        details { width: 100%; }
        summary { cursor: pointer; font-size: .8rem; font-weight: 600; color: var(--muted); text-align: center; }

        .sn-actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: center; }
        .sn-btn { display: inline-flex; align-items: center; gap: .45rem; font-size: .85rem; font-weight: 600; padding: .55rem 1rem; border-radius: .6rem; text-decoration: none; border: 1px solid transparent; cursor: pointer; }
        .sn-btn svg { width: 1.05rem; height: 1.05rem; }
        .sn-btn-primary { background: var(--accent); color: var(--accent-ink); }
        .sn-btn-gray { background: var(--card); color: var(--text); border-color: var(--border); }

        .sn-note { display: flex; gap: .6rem; width: 100%; padding: .8rem .9rem; border-radius: .7rem; font-size: .82rem; line-height: 1.55; text-align: start; background: var(--info-soft); color: var(--info); border: 1px solid var(--info-border); }
        .sn-note svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .1rem; }
        .sn-note a { color: inherit; font-weight: 600; }

        .sn-foot { margin-top: 1.25rem; text-align: center; display: flex; flex-direction: column; gap: .35rem; }
        .sn-ref { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .3rem .7rem; font-size: .7rem; color: var(--faint); font-family: ui-monospace, Menlo, monospace; }
        .sn-copy { font-size: .78rem; color: var(--faint); }
    </style>
</head>
<body>
    <div class="sn-shell">
        <div class="sn-wrap">
            <div class="sn-card">
                <div class="sn-head">
                    <div class="sn-brand">{{ $brand }}</div>
                </div>
                <div class="sn-content">
                    <div class="sn-icon" data-tone="{{ $tone }}">@yield('icon')</div>
                    <div class="sn-code-big" data-tone="{{ $tone }}">{{ $code }}</div>
                    <h1 class="sn-title">{{ $title }}</h1>
                    <p class="sn-body">{{ $body }}</p>
                    @yield('content')
                </div>
            </div>

            <div class="sn-foot">
                <div class="sn-ref">
                    <span>Mensagem nº {{ $number }}</span>
                    <span aria-hidden="true">·</span>
                    <span>ID da requisição {{ $requestId }}</span>
                </div>
                <div class="sn-copy">&copy; {{ date('Y') }} {{ $brand }}</div>
            </div>
        </div>
    </div>
</body>
</html>
