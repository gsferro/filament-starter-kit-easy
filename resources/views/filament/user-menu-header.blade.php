{{--
    Cabeçalho do menu do usuário: avatar + nome + e-mail + badge do papel.

    Registrada nos TRÊS painéis por `PanelsRenderHook::USER_MENU_PROFILE_BEFORE`, que
    emite DENTRO do dropdown, logo acima do item "Meu perfil".

    Atenção ao ler o provider: o hook irmão logo acima (`GLOBAL_SEARCH_BEFORE`, do
    gatilho ⌘K) tem um comentário dizendo que `USER_MENU_BEFORE` foi REJEITADO por
    renderizar dentro do dropdown. Não é contradição — é o mesmo fato usado ao
    contrário. Lá o conteúdo tinha de ficar na topbar; aqui ele tem de ficar dentro.

    Blade puro, sem estado, na raiz de `views/filament/` pelo mesmo motivo do
    `spotlight-trigger`: é conteúdo de painel, não de página, e serve os três.

    O avatar é `x-filament-panels::avatar.user`, o componente do próprio Filament —
    ele já consome `User::getFilamentAvatarUrl()` e cai no fallback ui-avatars quando
    não há upload. Montar um `<img>` aqui seria reescrever o fallback.

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
