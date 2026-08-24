{{--
    Botao "Entrar com Google", abaixo do formulario de login dos tres paineis.

    Chega aqui pelo render hook AUTH_LOGIN_FORM_AFTER, registrado uma unica vez em
    App\Providers\KitServiceProvider::configureTelaDeLogin() e SEM escopo de painel — uma
    registracao cobre os tres. Ver ADR-05 da wiki login-social-google.

    Quando o login social nao esta disponivel este arquivo renderiza VAZIO, e nao um bloco
    escondido: o botao e a unica porta do login social, e presenca no HTML com display
    desligado seria uma affordance para um caminho que responde 404.

    O icone e SVG inline, com as quatro cores da marca. Heroicons nao tem logo de marca, e o
    kit nao ganha um pacote de ~3.000 icones para usar UM. Ver ADR-04.

    ponytail: estilo inline em vez de uma regra nova em resources/css/filament/kit.css. Tres
    propriedades nao justificam um arquivo de CSS mais um `php artisan filament:assets` no
    fluxo de quem instala o kit. currentColor com opacity segue o tema claro/escuro sozinho,
    sem uma variavel de cor escrita a mao. Teto conhecido: se o bloco crescer, ele vira uma
    regra escopada em .fi-login-social naquele arquivo.
--}}
@if (\App\Support\ConfiguracaoDoLogin::googleDisponivel())
    <div class="fi-login-social" style="margin-top:1.5rem">
        {{-- Divisor com rotulo. A linha usa currentColor para acompanhar o tema. --}}
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem" aria-hidden="true">
            <span style="flex:1;border-top:1px solid currentColor;opacity:.15"></span>
            <span style="font-size:.75rem;opacity:.6">ou</span>
            <span style="flex:1;border-top:1px solid currentColor;opacity:.15"></span>
        </div>

        <x-filament::button
            tag="a"
            :href="route('auth.google.redirect')"
            color="gray"
            size="lg"
            outlined
            aria-label="Entrar com Google"
            style="width:100%"
        >
            <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true" focusable="false">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Entrar com Google
        </x-filament::button>
    </div>
@endif
