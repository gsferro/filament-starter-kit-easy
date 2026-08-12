{{-- Shared <head> bits for every Sentinel error page. Expects $code and $brand. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $code }} · {{ $brand }}</title>

{{-- Match the panel's light/dark choice the same way Filament itself does. --}}
<script>
    (() => {
        const theme = localStorage.getItem('theme') ?? 'system'
        if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    })()
</script>

{{-- Filament's published base stylesheet carries the fi-* component and colour
     utilities. A standalone error page renders outside the panel, so
     @filamentStyles alone (fonts + inline styles) is not enough. --}}
@if (is_file(public_path('css/filament/filament/app.css')))
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
@endif

@filamentStyles
