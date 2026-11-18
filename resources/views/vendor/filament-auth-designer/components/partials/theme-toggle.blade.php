@props(['position'])

{{--
    Sobrescrita do kit (wiki feat/debitos-da-aparencia-das-telas, débito H): o alternador de tema
    do Filament nasceu para o menu do usuário, e os botões dele são role="menuitemradio" dentro de um
    role="group" — o "menu" pai vem do menu do usuário. Solto nas telas de acesso, o axe acusava
    "aria-required-parent" em todo login e recuperação de senha. O wrapper passa a ser o role="menu"
    que faltava, com o mesmo rótulo. O resto é idêntico ao partial do pacote.
--}}
@php
    use Filament\Facades\Filament;

    $styles = '';

    if (is_array($position)) {
        $styles = 'style="';
        foreach ($position as $key => $value) {
            $styles .= "--ad-theme-switcher-{$key}: {$value}; ";
        }
        $styles .= '"';
    }

    $hasDarkMode = Filament::hasDarkMode();
    $hasDarkModeForced = Filament::hasDarkModeForced();
@endphp

@if ($hasDarkMode && !$hasDarkModeForced)
    <div class="fi-auth-theme-switcher-wrapper" role="menu" aria-label="{{ __('filament-panels::layout.actions.theme_switcher.label') }}" {!! $styles !!}>
        <x-filament-panels::theme-switcher />
    </div>
@endif
