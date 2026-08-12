{{--
    The 500 has two presentations, chosen by config('sentinel.window.style'):
      'window' — a small docked message window (over the real page on a Livewire
                 error), positioned by config('sentinel.window.position').
      'page'   — a full-page card, exactly like the other error codes.
--}}
@php
    use Filament\Sentinel\Support\Sentinel;

    $variant = Sentinel::rendersFullPage() ? '500-page' : '500-window';
@endphp
@include('filament-sentinel::errors.'.$variant, ['exception' => $exception ?? null])
