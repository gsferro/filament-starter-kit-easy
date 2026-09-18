{{--
    Cabeçalho do menu do usuário: avatar + nome + e-mail + badge do papel.

    Registrada nos TRÊS painéis por `PanelsRenderHook::USER_MENU_PROFILE_BEFORE`, que emite
    DENTRO do dropdown, logo acima do item "Meu perfil"
    (`vendor/filament/filament/resources/views/components/user-menu.blade.php:92`, e de novo em
    `:110`, `:133` e `:148`, um por variação de layout do menu).

    Não confundir com o irmão `USER_MENU_BEFORE`: ele é emitido em `:38`, ANTES e FORA do
    `<x-filament::dropdown>` que abre na linha 40 — ou seja, na topbar, colado ao avatar, e não
    dentro do menu. Até 2026-09-18 esta blade e os três providers afirmavam o contrário.

    Blade puro, sem estado, na raiz de `views/filament/` pelo mesmo motivo do
    `spotlight-trigger`: é conteúdo de painel, não de página, e serve os três.

    O avatar é `x-filament-panels::avatar.user`, o componente do próprio Filament —
    ele já consome `User::getFilamentAvatarUrl()` e, quando não há upload, cai no
    `defaultAvatarProvider` do painel. Montar um `<img>` aqui seria reescrever o fallback.

    Esse fallback deixou de ser o `ui-avatars.com` em 2026-09-18: os três painéis declaram
    `->defaultAvatarProvider(App\Support\AvatarDeIniciais::class)`, que desenha as iniciais num
    SVG embutido. Antes disso, este componente fazia o navegador de cada pessoa requisitar um
    domínio de terceiro em toda tela.

    `truncate` + `title` em nome e e-mail: o dropdown tem largura fixa, e um nome
    longo o alargaria por cima do resto da topbar.

    `data-user-menu-header` é gancho de teste, não estilo. Está aqui porque o CT-B
    precisa afirmar "o cabeçalho ficou VISÍVEL ao abrir o dropdown", e o nome do
    usuário não serve de âncora: ele também aparece no `AccountWidget` do dashboard,
    na mesma página, então um `assertSee` do nome passaria com o dropdown fechado.
    Ver ADR-06 da feature.
--}}
@php
    $usuarioDoCabecalho = filament()->auth()->user();
@endphp

@if ($usuarioDoCabecalho)
    <div data-user-menu-header class="flex items-center gap-3 px-3 py-2">
        <x-filament-panels::avatar.user :user="$usuarioDoCabecalho" size="lg" loading="lazy" />

        <div class="grid min-w-0 gap-1">
            <span
                class="truncate text-sm font-semibold text-gray-950 dark:text-white"
                title="{{ $usuarioDoCabecalho->name }}"
            >
                {{ $usuarioDoCabecalho->name }}
            </span>

            <span
                class="truncate text-xs text-gray-500 dark:text-gray-400"
                title="{{ $usuarioDoCabecalho->email }}"
            >
                {{ $usuarioDoCabecalho->email }}
            </span>

            @include('filament.perfil-indicator')
        </div>
    </div>
@endif
