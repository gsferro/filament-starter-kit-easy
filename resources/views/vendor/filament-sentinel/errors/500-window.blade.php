@php
    use Filament\Sentinel\Support\Sentinel;

    $code = 500;
    $brand = Sentinel::brand();
    $number = Sentinel::messageNumber($code, $exception ?? null);
    $details = Sentinel::exceptionDetails($exception ?? null);
    $trace = Sentinel::trace($exception ?? null);
    $requestId = Sentinel::requestId();
    $position = Sentinel::windowPosition();
    $width = Sentinel::windowWidth();
    $copy = trim(($number ? $number.' · ' : '').__('sentinel::sentinel.labels.request_id').' '.$requestId);
    $time = now()->format('H:i:s');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('filament-sentinel::errors.partials.head', ['code' => $code, 'brand' => $brand])

    <script>
        // When a Livewire/panel action fails, Filament shows this page inside a
        // modal over the real page. There we keep the background transparent so
        // the actual page stays visible behind the minimized window. On a direct
        // visit (nothing behind us) we fall back to the panel's own background.
        if (window.self === window.top) {
            document.documentElement.classList.add('sn-standalone')
        }
    </script>

    {{-- Page-level styles (standalone visit only). This block is intentionally
         free of any .sn5-* selector so the Livewire overlay never copies it into
         the host page — it would otherwise blank the real page's background. --}}
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; background: transparent; }
        html.sn-standalone body { background: var(--gray-50); }
        html.sn-standalone.dark body { background: var(--gray-950); }
    </style>

    {{-- Window styles. Fully self-contained: every custom variable is scoped to
         .sn5-dock (never :root), so injecting this block over the real page can
         only affect the window, never the page. --}}
    <style>
        .sn5-dock {
            position: fixed; width: var(--w, 400px); max-width: calc(100vw - 40px); --gap: 20px;
            --panel: #ffffff; --muted: var(--gray-500); --faint: var(--gray-400);
            --ring: var(--gray-200); --hover: var(--gray-100); --code-bg: var(--gray-100);
            --danger: var(--danger-600); --danger-bg: color-mix(in oklab, var(--danger-500) 10%, transparent);
        }
        .sn5-dock[data-position="bottom-left"]   { left: var(--gap); bottom: var(--gap); }
        .sn5-dock[data-position="bottom-right"]  { right: var(--gap); bottom: var(--gap); }
        .sn5-dock[data-position="top-left"]      { left: var(--gap); top: var(--gap); }
        .sn5-dock[data-position="top-right"]     { right: var(--gap); top: var(--gap); }
        .sn5-dock[data-position="top-center"]    { top: var(--gap); left: 50%; transform: translateX(-50%); }
        .sn5-dock[data-position="bottom-center"] { bottom: var(--gap); left: 50%; transform: translateX(-50%); }
        .sn5-dock[data-position="center"]        { top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .sn5-dock[data-position="slide-right"]   { top: 0; right: 0; bottom: 0; max-width: 100vw; }
        .sn5-dock[data-position="slide-left"]    { top: 0; left: 0; bottom: 0; max-width: 100vw; }
        html.dark .sn5-dock {
            --panel: var(--gray-900); --muted: var(--gray-400); --faint: var(--gray-500);
            --ring: var(--gray-800); --hover: var(--gray-800); --code-bg: var(--gray-950);
            --danger: var(--danger-400);
        }
        @keyframes sn5-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        @keyframes sn5-slide-right { from { transform: translateX(100%); } to { transform: none; } }
        @keyframes sn5-slide-left { from { transform: translateX(-100%); } to { transform: none; } }
        .sn5-window { background: var(--panel); border: 1px solid var(--ring); border-radius: 12px; box-shadow: 0 16px 48px rgba(9,9,11,.28); overflow: hidden; animation: sn5-in .18s ease-out; }
        .sn5-dock[data-position="slide-right"] .sn5-window,
        .sn5-dock[data-position="slide-left"] .sn5-window { height: 100vh; border-radius: 0; display: flex; flex-direction: column; }
        .sn5-dock[data-position="slide-right"] .sn5-window { border-width: 0 0 0 1px; animation: sn5-slide-right .24s ease-out; }
        .sn5-dock[data-position="slide-left"] .sn5-window { border-width: 0 1px 0 0; animation: sn5-slide-left .24s ease-out; }
        .sn5-dock[data-position="slide-right"] .sn5-scroll,
        .sn5-dock[data-position="slide-left"] .sn5-scroll { max-height: none; flex: 1; }
        .sn5-head { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border-bottom: 1px solid var(--ring); }
        .sn5-badge { font-family: ui-monospace, Menlo, monospace; font-size: 11px; font-weight: 700; color: var(--danger); background: var(--danger-bg); border-radius: 5px; padding: 2px 7px; }
        .sn5-time { flex: 1; font-size: 12px; color: var(--muted); font-family: ui-monospace, Menlo, monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sn5-x { width: 30px; height: 30px; border-radius: 7px; display: flex; align-items: center; justify-content: center; color: var(--muted); }
        .sn5-x:hover { background: var(--hover); }
        .sn5-scroll { max-height: 56vh; overflow-y: auto; padding: 16px 18px; display: flex; flex-direction: column; gap: 14px; }
        .sn5-titlerow { display: flex; gap: 10px; align-items: flex-start; }
        .sn5-title { font-size: 14px; font-weight: 700; line-height: 1.45; color: var(--gray-950); }
        html.dark .sn5-title { color: var(--gray-50); }
        .sn5-code { background: var(--code-bg); border: 1px solid var(--ring); border-radius: 8px; padding: 10px 12px; font-family: ui-monospace, Menlo, monospace; font-size: 11.5px; line-height: 1.6; color: var(--muted); white-space: pre-wrap; word-break: break-word; }
        .sn5-label { font-size: 12.5px; font-weight: 700; margin-bottom: 4px; color: var(--gray-950); }
        html.dark .sn5-label { color: var(--gray-50); }
        .sn5-text { font-size: 13px; color: var(--muted); line-height: 1.6; }
        .sn5-more { display: flex; flex-direction: column; gap: 6px; padding-top: 12px; border-top: 1px dashed var(--ring); }
        .sn5-more[hidden] { display: none; }
        .sn5-rows { display: grid; grid-template-columns: auto 1fr; gap: 4px 14px; margin: 0; font-size: 12px; }
        .sn5-rows dt { color: var(--faint); }
        .sn5-rows dd { margin: 0; color: var(--muted); font-family: ui-monospace, Menlo, monospace; word-break: break-word; }
        .sn5-trace { border: 1px solid var(--ring); border-radius: 8px; overflow: hidden; font-family: ui-monospace, Menlo, monospace; }
        .sn5-trace-head { padding: 8px 10px; background: var(--danger-bg); border-bottom: 1px solid var(--ring); }
        .sn5-trace-class { display: block; font-size: 11px; font-weight: 700; color: var(--danger); }
        .sn5-trace-msg { display: block; margin-top: 2px; font-size: 11.5px; line-height: 1.5; color: var(--gray-950); word-break: break-word; }
        html.dark .sn5-trace-msg { color: var(--gray-50); }
        .sn5-frame { display: grid; grid-template-columns: 16px 1fr; column-gap: 8px; padding: 6px 10px; background: var(--code-bg); border-bottom: 1px solid var(--ring); font-size: 11px; line-height: 1.5; }
        .sn5-frame:last-child { border-bottom: 0; }
        .sn5-frame-i { grid-row: span 2; color: var(--faint); text-align: right; user-select: none; }
        .sn5-frame-loc { color: var(--muted); word-break: break-all; }
        .sn5-frame-ln { color: var(--faint); }
        .sn5-frame-call { color: var(--faint); word-break: break-all; }
        .sn5-frame.is-app { background: color-mix(in oklab, var(--primary-500) 8%, var(--code-bg)); }
        .sn5-frame.is-app .sn5-frame-loc { color: var(--gray-950); font-weight: 600; }
        html.dark .sn5-frame.is-app .sn5-frame-loc { color: var(--gray-50); }
        .sn5-foot { display: flex; flex-direction: column; align-items: stretch; gap: 8px; padding: 10px 14px; border-top: 1px solid var(--ring); background: var(--code-bg); }
        .sn5-msgno { font-size: 11.5px; color: var(--faint); font-family: ui-monospace, Menlo, monospace; }
        .sn5-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
    </style>
</head>
<body class="fi-body">
    <div class="sn5-dock" data-position="{{ $position }}" style="--w: {{ $width }}">
        <div class="sn5-window">
            <div class="sn5-head">
                <span class="sn5-badge">500</span>
                <div class="sn5-time">{{ $time }}@if ($requestId) · request-id {{ $requestId }}@endif</div>
                <a class="sn5-x" href="{{ Sentinel::homeUrl() }}" title="{{ __('sentinel::sentinel.labels.go_home') }}" data-sentinel="dismiss">
                    @svg('heroicon-m-x-mark', '', ['style' => 'width:16px;height:16px'])
                </a>
            </div>

            <div class="sn5-scroll">
                <div class="sn5-titlerow">
                    @svg('heroicon-o-exclamation-triangle', '', ['style' => 'width:19px;height:19px;flex:none;margin-top:1px;color:var(--danger)'])
                    <div class="sn5-title">{{ __('sentinel::sentinel.500.title') }}</div>
                </div>

                @if ($details)
                    <div class="sn5-code">{{ $details['class'] }}
{{ $details['file'] }}:{{ $details['line'] }}</div>
                @endif

                @foreach (['diagnosis', 'response', 'procedure'] as $block)
                    <div>
                        <div class="sn5-label">{{ __('sentinel::sentinel.labels.'.($block === 'response' ? 'system_response' : $block)) }}</div>
                        <div class="sn5-text">{{ __('sentinel::sentinel.500.'.$block) }}</div>
                    </div>
                @endforeach

                {{-- Expanded by "View more": consolidated technical context to quote to
                     support, plus the real stack trace when running in debug mode. --}}
                <div class="sn5-more" hidden>
                    <div class="sn5-label">{{ __('sentinel::sentinel.labels.technical_detail') }}</div>
                    <dl class="sn5-rows">
                        @if ($number)
                            <dt>{{ __('sentinel::sentinel.labels.message_no') }}</dt><dd>{{ $number }}</dd>
                        @endif
                        @if ($requestId)
                            <dt>{{ __('sentinel::sentinel.labels.request_id') }}</dt><dd>{{ $requestId }}</dd>
                        @endif
                        <dt>{{ __('sentinel::sentinel.labels.status') }}</dt><dd>HTTP {{ $code }}</dd>
                    </dl>

                    @if ($trace)
                        <div class="sn5-label" style="margin-top:8px">{{ __('sentinel::sentinel.labels.stack_trace') }}</div>
                        <div class="sn5-trace">
                            <div class="sn5-trace-head">
                                <span class="sn5-trace-class">{{ class_basename($trace['class']) }}</span>
                                <span class="sn5-trace-msg">{{ $trace['message'] }}</span>
                            </div>
                            @foreach ($trace['frames'] as $i => $frame)
                                <div class="sn5-frame @if ($frame['app']) is-app @endif">
                                    <span class="sn5-frame-i">{{ $i + 1 }}</span>
                                    <span class="sn5-frame-loc">@if ($frame['file']){{ $frame['file'] }}<span class="sn5-frame-ln">:{{ $frame['line'] }}</span>@else <em>[internal function]</em>@endif</span>
                                    <span class="sn5-frame-call">{{ $frame['call'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif (Sentinel::supportUrl())
                        <div class="sn5-text" style="margin-top:4px">
                            {{ __('sentinel::sentinel.labels.support_prompt') }}
                            <a href="{{ Sentinel::supportUrl() }}" style="color:var(--danger);text-decoration:underline">{{ __('sentinel::sentinel.labels.support_word') }}</a>.
                        </div>
                    @endif
                </div>
            </div>

            <div class="sn5-foot">
                @if ($number)
                    <span class="sn5-msgno">{{ __('sentinel::sentinel.labels.message_no') }} {{ $number }}</span>
                @endif
                <div class="sn5-actions">
                    <x-filament::button
                        tag="button"
                        type="button"
                        color="gray"
                        size="sm"
                        icon="heroicon-m-chevron-down"
                        data-more="{{ __('sentinel::sentinel.labels.view_more') }}"
                        data-less="{{ __('sentinel::sentinel.labels.view_less') }}"
                        onclick="var w = this.closest('.sn5-window'); var m = w.querySelector('.sn5-more'); var open = m.hasAttribute('hidden'); if (open) { m.removeAttribute('hidden'); } else { m.setAttribute('hidden', ''); } var l = this.querySelector('.sn-more-lbl'); if (l) { l.textContent = open ? this.dataset.less : this.dataset.more; }"
                    >
                        <span class="sn-more-lbl">{{ __('sentinel::sentinel.labels.view_more') }}</span>
                    </x-filament::button>
                    <x-filament-sentinel::copy-button :text="$copy" />
                    <x-filament::button tag="a" href="{{ url()->current() }}" size="sm" icon="heroicon-m-arrow-path" data-sentinel="reload">
                        {{ __('sentinel::sentinel.labels.retry') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>

    @filamentScripts
</body>
</html>
